import type { H3Event } from 'h3'
import { db } from '../db'
import type { AuthUser } from './auth'

export interface ProjectContext {
  projectId: string
  folderId: string
  ownerId: string
  companyId: string | null
  userRole: 'owner' | 'admin' | 'editor' | 'viewer'
}

/**
 * Evaluates the 4-stage permission pipeline strictly according to Taskster specifications:
 * Stage 1: Company Policy Check
 * Stage 2: Project Membership Check (404 on failure to avoid leaking project existence)
 * Stage 3: List Scope Check (404 on failure)
 * Stage 4: Role Action Check (Viewer can only read, write returns 403)
 */
export function evaluateProjectAccess(
  user: AuthUser,
  projectId: string,
  event?: H3Event,
  action: 'read' | 'write' = 'read'
): ProjectContext {
  // If platform superadmin, full unrestricted access
  if (user.is_superadmin) {
    const prj = db.prepare(`
      SELECT p.id, p.folder_id, pf.owner_id, pf.company_id
      FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE p.id = ?
    `).get(projectId) as any

    if (!prj) {
      throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
    }

    return {
      projectId: prj.id,
      folderId: prj.folder_id,
      ownerId: prj.owner_id,
      companyId: prj.company_id,
      userRole: 'owner'
    }
  }

  // Find project and folder
  const prj = db.prepare(`
    SELECT p.id, p.folder_id, pf.owner_id, pf.company_id
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE p.id = ?
  `).get(projectId) as any

  if (!prj) {
    throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
  }

  // --- STAGE 1: Company Policy Check ---
  if (prj.company_id) {
    const company = db.prepare('SELECT settings FROM companies WHERE id = ?').get(prj.company_id) as any
    if (company && company.settings) {
      try {
        const settings = JSON.parse(company.settings)
        if (settings.access_restricted && !user.is_pro && !user.company_id) {
          throw createError({ statusCode: 403, statusMessage: 'Unternehmensrichtlinie untersagt Zugriff' })
        }
      } catch {
        // ignore JSON parse issue
      }
    }
  }

  // --- STAGE 2: Project Membership Check ---
  // Owner of the folder has full project ownership
  let role: 'owner' | 'admin' | 'editor' | 'viewer' | null = null

  if (prj.owner_id === user.id) {
    role = 'owner'
  } else if (user.company_id && user.company_id === prj.company_id && user.company_role === 'admin') {
    role = 'admin'
  } else {
    const member = db.prepare('SELECT role FROM project_members WHERE project_id = ? AND user_id = ?').get(projectId, user.id) as any
    if (member) {
      role = member.role as 'editor' | 'viewer'
    }
  }

  // Failure: Must return 404 NOT FOUND (never 403) to avoid leaking existence of projects!
  if (!role) {
    throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
  }

  // --- STAGE 4: Role Action Check ---
  if (action === 'write' && role === 'viewer') {
    throw createError({
      statusCode: 403,
      statusMessage: 'Viewer besitzen nur Leseberechtigung. Schreibzugriff verweigert.'
    })
  }

  return {
    projectId: prj.id,
    folderId: prj.folder_id,
    ownerId: prj.owner_id,
    companyId: prj.company_id,
    userRole: role
  }
}

/**
 * Stage 3: List Scope Check
 * If access_mode == 'inherit': project membership grants access.
 * If access_mode == 'custom': only folder owner, company admin, or users with list_access.is_visible == 1 have access.
 * Fails with 404 Not Found to prevent information leakage.
 */
export function evaluateListAccess(
  user: AuthUser,
  listId: string,
  event?: H3Event,
  action: 'read' | 'write' = 'read'
) {
  const list = db.prepare(`
    SELECT l.id, l.project_id, l.access_mode, l.title, p.folder_id, pf.owner_id, pf.company_id
    FROM lists l
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE l.id = ?
  `).get(listId) as any

  if (!list) {
    throw createError({ statusCode: 404, statusMessage: 'Liste nicht gefunden' })
  }

  // Project access check
  const projectContext = evaluateProjectAccess(user, list.project_id, event, action)

  if (list.access_mode === 'custom') {
    // Owner and company admin always see custom lists
    if (projectContext.userRole !== 'owner' && projectContext.userRole !== 'admin' && !user.is_superadmin) {
      const access = db.prepare('SELECT is_visible FROM list_access WHERE list_id = ? AND user_id = ?').get(listId, user.id) as any
      if (!access || access.is_visible !== 1) {
        throw createError({ statusCode: 404, statusMessage: 'Liste nicht gefunden' })
      }
    }
  }

  return { list, projectContext }
}

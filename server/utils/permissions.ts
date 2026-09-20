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
  // Find project and folder
  const prj = db.prepare(`
    SELECT p.id, p.folder_id, p.visibility as project_visibility, pf.owner_id, pf.company_id, pf.visibility as folder_visibility
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

  // --- STAGE 2: Project Membership & Group Access Check ---
  const ROLE_RANK: Record<string, number> = { viewer: 1, editor: 2, admin: 3, owner: 4 }
  const candidateRoles: Array<'owner' | 'admin' | 'editor' | 'viewer'> = []

  // 1. Owner of the folder has full project ownership
  if (prj.owner_id === user.id) {
    candidateRoles.push('owner')
  }

  // 2. Direct project membership
  const member = db.prepare('SELECT role FROM project_members WHERE project_id = ? AND user_id = ?').get(projectId, user.id) as any
  if (member?.role && member.role in ROLE_RANK) {
    candidateRoles.push(member.role as 'owner' | 'admin' | 'editor' | 'viewer')
  }

  // 3. Direct folder membership
  const folderMember = db.prepare('SELECT role FROM folder_members WHERE folder_id = ? AND user_id = ?').get(prj.folder_id, user.id) as any
  if (folderMember?.role && folderMember.role in ROLE_RANK) {
    candidateRoles.push(folderMember.role as 'owner' | 'admin' | 'editor' | 'viewer')
  }

  // 4. Project-level group access
  const projectGroupRoles = db.prepare(`
    SELECT pga.role
    FROM project_group_access pga
    JOIN user_group_members ugm ON ugm.group_id = pga.group_id
    WHERE pga.project_id = ? AND ugm.user_id = ?
  `).all(projectId, user.id) as { role: string }[]
  for (const grp of projectGroupRoles) {
    if (grp.role in ROLE_RANK) {
      candidateRoles.push(grp.role as any)
    }
  }

  // 5. Folder-level group access
  const folderGroupRoles = db.prepare(`
    SELECT fga.role
    FROM folder_group_access fga
    JOIN user_group_members ugm ON ugm.group_id = fga.group_id
    WHERE fga.folder_id = ? AND ugm.user_id = ?
  `).all(prj.folder_id, user.id) as { role: string }[]
  for (const grp of folderGroupRoles) {
    if (grp.role in ROLE_RANK) {
      candidateRoles.push(grp.role as any)
    }
  }

  // 6. Company-level visibility
  if (
    user.company_id && user.company_id === prj.company_id &&
    (prj.project_visibility === 'company' || prj.folder_visibility === 'company')
  ) {
    candidateRoles.push('editor')
  }

  // Determine highest role
  let role: 'owner' | 'admin' | 'editor' | 'viewer' | null = null
  let maxRank = 0
  for (const r of candidateRoles) {
    const rank = ROLE_RANK[r] || 0
    if (rank > maxRank) {
      maxRank = rank
      role = r
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

export interface FolderContext {
  folderId: string
  ownerId: string
  companyId: string | null
  userRole: 'owner' | 'admin' | 'editor' | 'viewer'
}

/**
 * Evaluates access to a project folder including direct membership,
 * company visibility, and group memberships.
 */
export function evaluateFolderAccess(
  user: AuthUser,
  folderId: string,
  event?: H3Event,
  action: 'read' | 'write' = 'read'
): FolderContext {
  const folder = db.prepare(`
    SELECT id, owner_id, company_id, visibility
    FROM project_folders
    WHERE id = ?
  `).get(folderId) as any

  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  const ROLE_RANK: Record<string, number> = { viewer: 1, editor: 2, admin: 3, owner: 4 }
  const candidateRoles: Array<'owner' | 'admin' | 'editor' | 'viewer'> = []

  // 1. Folder owner
  if (folder.owner_id === user.id) {
    candidateRoles.push('owner')
  }

  // 2. Direct folder membership
  const member = db.prepare('SELECT role FROM folder_members WHERE folder_id = ? AND user_id = ?').get(folderId, user.id) as any
  if (member?.role && member.role in ROLE_RANK) {
    candidateRoles.push(member.role as any)
  }

  // 3. Folder group access
  const groupRoles = db.prepare(`
    SELECT fga.role
    FROM folder_group_access fga
    JOIN user_group_members ugm ON ugm.group_id = fga.group_id
    WHERE fga.folder_id = ? AND ugm.user_id = ?
  `).all(folderId, user.id) as { role: string }[]
  for (const grp of groupRoles) {
    if (grp.role in ROLE_RANK) {
      candidateRoles.push(grp.role as any)
    }
  }

  // 4. Company visibility
  if (user.company_id && user.company_id === folder.company_id && folder.visibility === 'company') {
    candidateRoles.push('editor')
  }

  let role: 'owner' | 'admin' | 'editor' | 'viewer' | null = null
  let maxRank = 0
  for (const r of candidateRoles) {
    const rank = ROLE_RANK[r] || 0
    if (rank > maxRank) {
      maxRank = rank
      role = r
    }
  }

  if (!role) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  if (action === 'write' && role === 'viewer') {
    throw createError({
      statusCode: 403,
      statusMessage: 'Viewer besitzen nur Leseberechtigung. Schreibzugriff verweigert.'
    })
  }

  return {
    folderId: folder.id,
    ownerId: folder.owner_id,
    companyId: folder.company_id,
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
    // Owner and project admin always see custom lists
    if (projectContext.userRole !== 'owner' && projectContext.userRole !== 'admin') {
      const access = db.prepare('SELECT is_visible FROM list_access WHERE list_id = ? AND user_id = ?').get(listId, user.id) as any
      if (!access || access.is_visible !== 1) {
        throw createError({ statusCode: 404, statusMessage: 'Liste nicht gefunden' })
      }
    }
  }

  return { list, projectContext }
}

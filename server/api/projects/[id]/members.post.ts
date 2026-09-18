import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { email, role } = body

  if (!email) {
    throw createError({ statusCode: 400, statusMessage: 'E-Mail ist erforderlich' })
  }

  // Must be owner or admin to add members
  const context = evaluateProjectAccess(user, projectId, event, 'write')
  if (context.userRole !== 'owner' && context.userRole !== 'admin' && !user.is_superadmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Projekt-Owner oder Admins dürfen Teammitglieder einladen' })
  }

  // Check target user
  const targetUser = db.prepare('SELECT id, name, email FROM users WHERE LOWER(email) = LOWER(?)').get(email) as any
  if (!targetUser) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer mit dieser E-Mail ist noch nicht bei Taskster registriert' })
  }

  // Free plan limit check:
  // "Pro Projekt können im free Plan 5 Team Mitglieder inkl. owner drin sein."
  const project = db.prepare(`
    SELECT p.*, pf.owner_id, pf.company_id
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE p.id = ?
  `).get(projectId) as any

  if (!project.company_id) {
    const owner = db.prepare('SELECT is_pro FROM users WHERE id = ?').get(project.owner_id) as any
    if (!owner || !owner.is_pro) {
      const currentMemberCount = (db.prepare('SELECT COUNT(*) as c FROM project_members WHERE project_id = ?').get(projectId) as any).c
      // Current count + 1 (owner is not in project_members table, so owner + members = currentMemberCount + 1)
      if (currentMemberCount + 1 >= 5) {
        throw createError({
          statusCode: 403,
          statusMessage: 'Free-Plan Limit erreicht: Im Free Plan sind maximal 5 Teammitglieder inkl. Owner pro Projekt erlaubt. Bitte auf Pro upgraden.'
        })
      }
    }
  }

  const existingMember = db.prepare('SELECT id FROM project_members WHERE project_id = ? AND user_id = ?').get(projectId, targetUser.id)
  if (existingMember) {
    db.prepare('UPDATE project_members SET role = ? WHERE project_id = ? AND user_id = ?').run(role || 'editor', projectId, targetUser.id)
  } else {
    const memberId = 'pm_' + randomUUID().substring(0, 8)
    db.prepare(`
      INSERT INTO project_members (id, project_id, user_id, role)
      VALUES (?, ?, ?, ?)
    `).run(memberId, projectId, targetUser.id, role || 'editor')
  }

  return { success: true, user: targetUser }
})

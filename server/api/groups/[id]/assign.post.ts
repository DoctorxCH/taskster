import { randomUUID } from 'crypto'
import { db } from '../../../db'
import { requireAuth } from '../../../utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '../../../utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const groupId = getRouterParam(event, 'id')
  const body = await readBody(event).catch(() => ({}))

  const group = db.prepare('SELECT * FROM user_groups WHERE id = ?').get(groupId) as any
  if (!group) {
    throw createError({ statusCode: 404, statusMessage: 'Gruppe nicht gefunden' })
  }

  const type = body?.type as 'project' | 'folder'
  const targetId = body?.target_id as string
  const role = body?.role as 'admin' | 'editor' | 'viewer' | 'none'

  if (!type || !targetId || !role) {
    throw createError({ statusCode: 400, statusMessage: 'Typ (project/folder), target_id und role sind erforderlich' })
  }

  if (type === 'project') {
    // Prüfen, ob der Nutzer Berechtigung hat, Rechte zu vergeben
    const ctx = evaluateProjectAccess(user, targetId, event, 'write')
    if (ctx.userRole !== 'owner' && ctx.userRole !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Nur Besitzer oder Administratoren dürfen Gruppenrechte vergeben.' })
    }

    if (role === 'none') {
      db.prepare('DELETE FROM project_group_access WHERE project_id = ? AND group_id = ?').run(targetId, groupId)
    } else {
      const existing = db.prepare('SELECT id FROM project_group_access WHERE project_id = ? AND group_id = ?').get(targetId, groupId) as any
      if (existing) {
        db.prepare('UPDATE project_group_access SET role = ? WHERE id = ?').run(role, existing.id)
      } else {
        const id = 'pga_' + randomUUID().substring(0, 8)
        db.prepare('INSERT INTO project_group_access (id, project_id, group_id, role) VALUES (?, ?, ?, ?)').run(id, targetId, groupId, role)
      }
    }
  } else if (type === 'folder') {
    const ctx = evaluateFolderAccess(user, targetId, event, 'write')
    if (ctx.userRole !== 'owner' && ctx.userRole !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Nur Besitzer oder Administratoren dürfen Gruppenrechte vergeben.' })
    }

    if (role === 'none') {
      db.prepare('DELETE FROM folder_group_access WHERE folder_id = ? AND group_id = ?').run(targetId, groupId)
    } else {
      const existing = db.prepare('SELECT id FROM folder_group_access WHERE folder_id = ? AND group_id = ?').get(targetId, groupId) as any
      if (existing) {
        db.prepare('UPDATE folder_group_access SET role = ? WHERE id = ?').run(role, existing.id)
      } else {
        const id = 'fga_' + randomUUID().substring(0, 8)
        db.prepare('INSERT INTO folder_group_access (id, folder_id, group_id, role) VALUES (?, ?, ?, ?)').run(id, targetId, groupId, role)
      }
    }
  } else {
    throw createError({ statusCode: 400, statusMessage: 'Ungültiger Typ' })
  }

  return { success: true }
})

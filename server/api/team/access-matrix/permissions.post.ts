import { randomUUID } from 'crypto'
import { db } from '../../../db'
import { requireAuth } from '../../../utils/auth'
import { evaluateFolderAccess, evaluateProjectAccess } from '../../../utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event).catch(() => ({}))

  const targetUserId = body?.user_id as string
  const type = body?.type as 'folder' | 'project'
  const targetId = body?.target_id as string
  const role = body?.role as 'admin' | 'editor' | 'viewer' | 'none'

  if (!targetUserId || !type || !targetId || !role) {
    throw createError({ statusCode: 400, statusMessage: 'user_id, type, target_id und role sind erforderlich' })
  }

  // Sicherstellen, dass der Ziel-Benutzer existiert
  const targetUser = db.prepare('SELECT id, name FROM users WHERE id = ?').get(targetUserId)
  if (!targetUser) {
    throw createError({ statusCode: 404, statusMessage: 'Ziel-Benutzer nicht gefunden' })
  }

  if (type === 'folder') {
    const ctx = evaluateFolderAccess(user, targetId, event, 'write')
    if (ctx.userRole !== 'owner' && ctx.userRole !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Nur Besitzer oder Administratoren dürfen Ordnerrechte vergeben.' })
    }

    if (role === 'none') {
      db.prepare('DELETE FROM folder_members WHERE folder_id = ? AND user_id = ?').run(targetId, targetUserId)
    } else {
      const existing = db.prepare('SELECT id FROM folder_members WHERE folder_id = ? AND user_id = ?').get(targetId, targetUserId) as any
      if (existing) {
        db.prepare('UPDATE folder_members SET role = ? WHERE id = ?').run(role, existing.id)
      } else {
        const id = 'fm_' + randomUUID().substring(0, 8)
        db.prepare('INSERT INTO folder_members (id, folder_id, user_id, role) VALUES (?, ?, ?, ?)').run(id, targetId, targetUserId, role)
      }
    }
  } else if (type === 'project') {
    const ctx = evaluateProjectAccess(user, targetId, event, 'write')
    if (ctx.userRole !== 'owner' && ctx.userRole !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Nur Besitzer oder Administratoren dürfen Projektrechte vergeben.' })
    }

    if (role === 'none') {
      db.prepare('DELETE FROM project_members WHERE project_id = ? AND user_id = ?').run(targetId, targetUserId)
    } else {
      const existing = db.prepare('SELECT id FROM project_members WHERE project_id = ? AND user_id = ?').get(targetId, targetUserId) as any
      if (existing) {
        db.prepare('UPDATE project_members SET role = ? WHERE id = ?').run(role, existing.id)
      } else {
        const id = 'pm_' + randomUUID().substring(0, 8)
        db.prepare('INSERT INTO project_members (id, project_id, user_id, role) VALUES (?, ?, ?, ?)').run(id, targetId, targetUserId, role)
      }
    }
  } else {
    throw createError({ statusCode: 400, statusMessage: 'Ungültiger Typ' })
  }

  return { success: true }
})

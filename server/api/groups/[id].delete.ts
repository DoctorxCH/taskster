import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const groupId = getRouterParam(event, 'id')

  const group = db.prepare('SELECT * FROM user_groups WHERE id = ?').get(groupId) as any
  if (!group) {
    throw createError({ statusCode: 404, statusMessage: 'Gruppe nicht gefunden' })
  }

  const isOwner = group.owner_id === user.id
  const isCompanyAdmin = user.company_id && user.company_id === group.company_id && user.company_role === 'admin'
  if (!isOwner && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Löschen dieser Gruppe' })
  }

  db.prepare('DELETE FROM user_group_members WHERE group_id = ?').run(groupId)
  db.prepare('DELETE FROM project_group_access WHERE group_id = ?').run(groupId)
  db.prepare('DELETE FROM folder_group_access WHERE group_id = ?').run(groupId)
  db.prepare('DELETE FROM user_groups WHERE id = ?').run(groupId)

  return { success: true }
})

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const listId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { title, access_mode, sort_order } = body

  // Checks Stage 1-4. Viewer receives 403, unauthorized receives 404!
  const { list } = evaluateListAccess(user, listId, event, 'write')

  const newTitle = title !== undefined ? title.trim() : list.title
  const newMode = access_mode !== undefined ? access_mode : list.access_mode
  const newSort = sort_order !== undefined ? sort_order : list.sort_order

  db.prepare(`
    UPDATE lists
    SET title = ?, access_mode = ?, sort_order = ?
    WHERE id = ?
  `).run(newTitle, newMode, newSort, listId)

  return { success: true }
})

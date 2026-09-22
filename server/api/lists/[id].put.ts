import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const listId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { title, access_mode, sort_order, color, is_completed_target } = body

  // Checks Stage 1-4. Viewer receives 403, unauthorized receives 404!
  const { list } = evaluateListAccess(user, listId, event, 'write')

  const newTitle = title !== undefined ? title.trim() : list.title
  const newMode = access_mode !== undefined ? access_mode : list.access_mode
  const newSort = sort_order !== undefined ? sort_order : list.sort_order
  const newColor = color !== undefined ? (color || null) : list.color
  const newTarget = is_completed_target !== undefined ? (is_completed_target ? 1 : 0) : (list.is_completed_target ?? 0)

  if (newTarget === 1) {
    db.prepare('UPDATE lists SET is_completed_target = 0 WHERE project_id = ?').run(list.project_id)
  }

  db.prepare(`
    UPDATE lists
    SET title = ?, access_mode = ?, sort_order = ?, color = ?, is_completed_target = ?
    WHERE id = ?
  `).run(newTitle, newMode, newSort, newColor, newTarget, listId)

  return { success: true }
})

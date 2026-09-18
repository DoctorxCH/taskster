import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const listId = getRouterParam(event, 'id')

  // Checks Stage 1-4 permissions
  evaluateListAccess(user, listId, event, 'write')

  // Delete all tasks in this list first, then the list
  db.prepare('DELETE FROM tasks WHERE list_id = ?').run(listId)
  db.prepare('DELETE FROM lists WHERE id = ?').run(listId)

  return { success: true }
})

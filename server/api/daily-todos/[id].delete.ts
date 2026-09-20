import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * DELETE /api/daily-todos/:id
 * Löscht ein eigenes Tages-Todo.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')

  db.prepare('DELETE FROM daily_todos WHERE id = ? AND user_id = ?').run(id, user.id)

  return { success: true }
})
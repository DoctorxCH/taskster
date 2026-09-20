import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * POST /api/notifications/:id/read
 * Markiert eine Benachrichtigung als gelesen. Echtzeit-Einträge (due_/budget_)
 * existieren nicht in der DB und werden stillschweigend akzeptiert.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')

  if (id && !id.startsWith('due_') && !id.startsWith('budget_')) {
    db.prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?').run(id, user.id)
  }

  return { success: true }
})
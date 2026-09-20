import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * POST /api/notifications/read-all
 * Markiert alle gespeicherten Benachrichtigungen des Benutzers als gelesen.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  db.prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?').run(user.id)
  return { success: true }
})
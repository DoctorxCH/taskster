import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  const items = db.prepare(`
    SELECT id, to_email, to_name, subject, status, error, attempts, created_at, sent_at
    FROM email_outbox
    ORDER BY created_at DESC
    LIMIT 50
  `).all()

  return { outbox: items }
})

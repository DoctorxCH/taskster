import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  const templates = db.prepare(`
    SELECT * FROM email_templates ORDER BY name ASC
  `).all().map((t: any) => ({
    ...t,
    is_active: Boolean(t.is_active),
    variables: typeof t.variables === 'string' ? JSON.parse(t.variables || '[]') : (t.variables || [])
  }))

  return { templates }
})

import { db } from '~/server/db'
import { requireSuperadmin } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireSuperadmin(event)

  const users = db.prepare(`
    SELECT u.id, u.name, u.email, u.company_id, u.company_role, u.is_superadmin, u.is_pro, u.created_at,
           c.name as company_name, c.subscription_plan as company_plan
    FROM users u
    LEFT JOIN companies c ON c.id = u.company_id
    ORDER BY u.created_at DESC
  `).all().map((u: any) => ({
    ...u,
    is_superadmin: Boolean(u.is_superadmin),
    is_pro: Boolean(u.is_pro)
  }))

  return { users }
})


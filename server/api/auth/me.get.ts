import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const authUser = requireAuth(event)

  const user = db.prepare(`
    SELECT u.id, u.company_id, u.company_role, u.is_superadmin, u.is_pro, u.name, u.email,
           c.name as company_name, c.subscription_plan as company_plan, c.settings as company_settings
    FROM users u
    LEFT JOIN companies c ON c.id = u.company_id
    WHERE u.id = ?
  `).get(authUser.id) as any

  if (!user) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer nicht gefunden' })
  }

  let parsedSettings = {}
  if (user.company_settings) {
    try {
      parsedSettings = JSON.parse(user.company_settings)
    } catch {}
  }

  return {
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      company_id: user.company_id,
      company_role: user.company_role,
      company_name: user.company_name,
      company_plan: user.company_plan,
      company_settings: parsedSettings,
      is_superadmin: Boolean(user.is_superadmin),
      is_pro: Boolean(user.is_pro)
    }
  }
})


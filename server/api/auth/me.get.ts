import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { readUserSettings } from '~/server/utils/userSettings'

export default defineEventHandler((event) => {
  const authUser = requireAuth(event)

  const user = db.prepare(`
    SELECT u.id, u.company_id, u.company_role, u.is_superadmin, u.is_pro, u.name, u.email,
           u.hourly_rate, u.currency, u.admin_permissions, u.settings,
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

  // Nur Superadmin erhält Plattform-Permissions. Company Admins nutzen company_role
  // und verwalten ihre Firma im /company Portal.
  let perms: string[] = []
  if (user.admin_permissions) {
    try {
      perms = typeof user.admin_permissions === 'string'
        ? JSON.parse(user.admin_permissions)
        : user.admin_permissions
    } catch { perms = [] }
  }
  if (!Array.isArray(perms)) perms = []
  if (user.is_superadmin) {
    perms = ['manage_users', 'finance', 'company_settings', 'manage_templates', 'audit_logs', 'all']
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
      hourly_rate: Number(user.hourly_rate) || 0,
      currency: user.currency || 'CHF',
      settings: readUserSettings(user.settings),
      is_superadmin: Boolean(user.is_superadmin),
      is_pro: Boolean(user.is_pro),
      admin_permissions: perms
    }
  }
})


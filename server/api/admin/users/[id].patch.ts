import { db } from '~/server/db'
import { requireAdminPermission, hashPassword } from '~/server/utils/auth'
import { logAuditEvent } from '~/server/utils/audit'

export default defineEventHandler(async (event) => {
  requireAdminPermission(event, 'manage_users')
  const userId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(userId) as any
  if (!user) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer nicht gefunden' })
  }

  const {
    name,
    email,
    password,
    plan,
    is_pro,
    is_superadmin,
    company_id,
    company_role,
    admin_permissions
  } = body

  const newName = name !== undefined ? name.trim() : user.name
  const newEmail = email !== undefined ? email.trim().toLowerCase() : user.email
  
  // Determine is_pro status: if plan is enterprise or pro -> is_pro = 1
  let newIsPro = user.is_pro
  if (plan !== undefined) {
    newIsPro = (plan === 'pro' || plan === 'enterprise') ? 1 : 0
  } else if (is_pro !== undefined) {
    newIsPro = is_pro ? 1 : 0
  }

  const newIsSuper = is_superadmin !== undefined ? (is_superadmin ? 1 : 0) : user.is_superadmin
  const newCompId = company_id !== undefined ? company_id : user.company_id
  const newCompRole = company_role !== undefined ? company_role : user.company_role

  // Handle User Settings (storing the exact plan 'basic' | 'pro' | 'enterprise')
  let currentSettings: Record<string, any> = {}
  try {
    if (user.settings) currentSettings = typeof user.settings === 'string' ? JSON.parse(user.settings) : user.settings
  } catch (_) {}

  if (plan !== undefined) {
    currentSettings.plan = plan
  }
  const newSettingsJson = JSON.stringify(currentSettings)

  // Handle Admin Permissions
  let newPermsJson = user.admin_permissions
  if (admin_permissions !== undefined) {
    newPermsJson = typeof admin_permissions === 'string' ? admin_permissions : JSON.stringify(admin_permissions)
  }

  // Handle Password Hash if updated
  if (password && typeof password === 'string' && password.trim().length > 0) {
    const pwHash = hashPassword(password.trim())
    db.prepare(`
      UPDATE users
      SET name = ?, email = ?, password_hash = ?, is_pro = ?, is_superadmin = ?, company_id = ?, company_role = ?, admin_permissions = ?, settings = ?
      WHERE id = ?
    `).run(newName, newEmail, pwHash, newIsPro, newIsSuper, newCompId, newCompRole, newPermsJson, newSettingsJson, userId)
  } else {
    db.prepare(`
      UPDATE users
      SET name = ?, email = ?, is_pro = ?, is_superadmin = ?, company_id = ?, company_role = ?, admin_permissions = ?, settings = ?
      WHERE id = ?
    `).run(newName, newEmail, newIsPro, newIsSuper, newCompId, newCompRole, newPermsJson, newSettingsJson, userId)
  }

  logAuditEvent(event, {
    action: 'user.update',
    entityType: 'user',
    entityId: userId,
    details: {
      target_user: newEmail,
      plan: plan || (newIsPro ? 'pro' : 'basic'),
      is_pro: newIsPro,
      is_superadmin: newIsSuper,
      company_id: newCompId,
      company_role: newCompRole
    }
  })

  return { success: true }
})

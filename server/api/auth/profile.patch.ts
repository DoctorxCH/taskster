import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import bcrypt from 'bcryptjs'

export default defineEventHandler(async (event) => {
  const authUser = requireAuth(event)
  const body = await readBody(event)

  const name = body.name !== undefined ? String(body.name).trim() : null
  const hourlyRate = body.hourly_rate !== undefined ? Math.max(0, parseFloat(body.hourly_rate) || 0) : null
  const currency = body.currency !== undefined ? String(body.currency).toUpperCase().trim() : null
  const currentPassword = body.current_password
  const newPassword = body.new_password

  const existingUser = db.prepare('SELECT * FROM users WHERE id = ?').get(authUser.id) as any
  if (!existingUser) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer nicht gefunden' })
  }

  // Password change if requested
  if (newPassword) {
    if (!currentPassword || !bcrypt.compareSync(currentPassword, existingUser.password_hash)) {
      throw createError({ statusCode: 400, statusMessage: 'Das aktuelle Passwort ist nicht korrekt' })
    }
    if (newPassword.length < 8) {
      throw createError({ statusCode: 400, statusMessage: 'Das neue Passwort muss mindestens 8 Zeichen lang sein' })
    }
    const newHash = bcrypt.hashSync(newPassword, 10)
    db.prepare('UPDATE users SET password_hash = ? WHERE id = ?').run(newHash, authUser.id)
  }

  if (name) {
    db.prepare('UPDATE users SET name = ? WHERE id = ?').run(name, authUser.id)
  }

  if (hourlyRate !== null) {
    db.prepare('UPDATE users SET hourly_rate = ? WHERE id = ?').run(hourlyRate, authUser.id)
  }

  if (currency) {
    db.prepare('UPDATE users SET currency = ? WHERE id = ?').run(currency, authUser.id)
  }

  const updatedUser = db.prepare(`
    SELECT u.id, u.company_id, u.company_role, u.is_superadmin, u.is_pro, u.name, u.email,
           u.hourly_rate, u.currency,
           c.name as company_name, c.subscription_plan as company_plan
    FROM users u
    LEFT JOIN companies c ON c.id = u.company_id
    WHERE u.id = ?
  `).get(authUser.id) as any

  return {
    success: true,
    user: {
      id: updatedUser.id,
      name: updatedUser.name,
      email: updatedUser.email,
      company_id: updatedUser.company_id,
      company_role: updatedUser.company_role,
      company_name: updatedUser.company_name,
      company_plan: updatedUser.company_plan,
      hourly_rate: Number(updatedUser.hourly_rate) || 0,
      currency: updatedUser.currency || 'CHF',
      is_superadmin: Boolean(updatedUser.is_superadmin),
      is_pro: Boolean(updatedUser.is_pro)
    }
  }
})

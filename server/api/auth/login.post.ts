import { db } from '~/server/db'
import { comparePassword, generateToken } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const { email, password } = body

  if (!email || !password) {
    throw createError({
      statusCode: 400,
      statusMessage: 'E-Mail und Passwort sind erforderlich'
    })
  }

  const user = db.prepare(`
    SELECT u.id, u.company_id, u.company_role, u.is_superadmin, u.is_pro, u.name, u.email, u.password_hash,
           c.name as company_name, c.subscription_plan as company_plan
    FROM users u
    LEFT JOIN companies c ON c.id = u.company_id
    WHERE LOWER(u.email) = LOWER(?)
  `).get(email) as any

  if (!user || !comparePassword(password, user.password_hash)) {
    throw createError({
      statusCode: 401,
      statusMessage: 'Ungültige Zugangsdaten'
    })
  }

  const token = generateToken({
    id: user.id,
    name: user.name,
    email: user.email,
    company_id: user.company_id,
    company_role: user.company_role,
    is_superadmin: user.is_superadmin,
    is_pro: user.is_pro
  })

  return {
    token,
    user: {
      id: user.id,
      name: user.name,
      email: user.email,
      company_id: user.company_id,
      company_role: user.company_role,
      company_name: user.company_name,
      company_plan: user.company_plan,
      is_superadmin: Boolean(user.is_superadmin),
      is_pro: Boolean(user.is_pro)
    }
  }
})


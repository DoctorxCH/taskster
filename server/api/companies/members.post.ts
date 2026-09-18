import { db } from '~/server/db'
import { requireAuth, hashPassword } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { email, name, role } = body

  if (!email || !name) {
    throw createError({ statusCode: 400, statusMessage: 'Name und E-Mail erforderlich' })
  }

  // Must be company admin or superadmin
  if (!user.is_superadmin && (!user.company_id || user.company_role !== 'admin')) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Company-Admins dürfen Mitarbeiter einladen' })
  }

  const companyId = user.company_id
  if (!companyId && !user.is_superadmin) {
    throw createError({ statusCode: 400, statusMessage: 'Keine Company zugewiesen' })
  }

  const existing = db.prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)').get(email) as any

  if (existing) {
    // Member joins company and inherits company paid tier
    db.prepare(`
      UPDATE users
      SET company_id = ?, company_role = ?, is_pro = 1
      WHERE id = ?
    `).run(companyId, role || 'member', existing.id)
    return { success: true, user: { id: existing.id, email, name, updated: true } }
  } else {
    const newUserId = 'usr_' + randomUUID().substring(0, 8)
    const initialPw = hashPassword('taskster2026!')
    db.prepare(`
      INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash)
      VALUES (?, ?, ?, 0, 1, ?, ?, ?)
    `).run(newUserId, companyId, role || 'member', name.trim(), email.trim().toLowerCase(), initialPw)

    return {
      success: true,
      user: { id: newUserId, email, name, temp_password: 'taskster2026!' }
    }
  }
})


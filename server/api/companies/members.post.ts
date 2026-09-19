import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID, randomBytes } from 'crypto'

/**
 * POST /api/companies/members
 * Lädt einen Mitarbeiter ein:
 *  - Bereits registriert -> sofort dem Unternehmen zuordnen (action: 'added')
 *  - Nicht registriert   -> Einladung mit Token anlegen (action: 'invited')
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const email = String(body?.email || '').trim().toLowerCase()
  const role = body?.role === 'admin' ? 'admin' : 'member'

  if (!email) {
    throw createError({ statusCode: 400, statusMessage: 'E-Mail erforderlich' })
  }

  // Nur Company Admin (company_role === 'admin') oder Superadmin
  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin'
  if (!isSuperadmin && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Company-Admins dürfen Mitarbeiter einladen' })
  }

  const companyId = user.company_id || body?.company_id
  if (!companyId) {
    throw createError({ statusCode: 400, statusMessage: 'Kein Unternehmen zugewiesen' })
  }

  const existing = db.prepare('SELECT id, email, name FROM users WHERE LOWER(email) = ?').get(email) as any

  if (existing) {
    // Bereits registriert -> direkt dem Unternehmen zuordnen
    db.prepare('UPDATE users SET company_id = ?, company_role = ?, is_pro = 1 WHERE id = ?')
      .run(companyId, role, existing.id)
    return {
      success: true,
      action: 'added',
      user: { id: existing.id, email: existing.email, name: existing.name }
    }
  }

  // Nicht registriert -> Einladung mit Token
  const token = randomBytes(24).toString('hex')
  const invId = 'inv_' + randomUUID().substring(0, 8)

  db.prepare('DELETE FROM company_invitations WHERE company_id = ? AND LOWER(email) = ?')
    .run(companyId, email)
  db.prepare(`
    INSERT INTO company_invitations (id, company_id, email, role, token, invited_by, status)
    VALUES (?, ?, ?, ?, ?, ?, 'pending')
  `).run(invId, companyId, email, role, token, user.id)

  return { success: true, action: 'invited', token, email }
})


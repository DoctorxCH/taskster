import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/companies/members
 * Listet alle Mitarbeiter des eigenen Unternehmens.
 * Nur Company Admin (company_role === 'admin') oder Superadmin.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin'

  if (!isSuperadmin && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Company-Admins dürfen Mitarbeiter einsehen' })
  }

  const companyId = user.company_id || (getQuery(event).company_id as string | undefined)
  if (!companyId) {
    return { members: [] }
  }

  const members = db.prepare(`
    SELECT id, name, email, company_role, is_pro, created_at
    FROM users
    WHERE company_id = ?
    ORDER BY (company_role = 'admin') DESC, name ASC
  `).all(companyId)

  return { members }
})
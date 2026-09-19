import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/companies/invitations
 * Listet die offenen Einladungen des eigenen Unternehmens.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin'

  if (!isSuperadmin && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Company-Admins dürfen Einladungen einsehen' })
  }

  const companyId = user.company_id || (getQuery(event).company_id as string | undefined)
  if (!companyId) {
    return { invitations: [] }
  }

  const invitations = db.prepare(`
    SELECT id, email, role, token, status, created_at
    FROM company_invitations
    WHERE company_id = ?
    ORDER BY created_at DESC
  `).all(companyId)

  return { invitations }
})
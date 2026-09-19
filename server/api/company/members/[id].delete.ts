import { db } from '~/server/db'
import { requireCompanyAdmin, resolveCompanyId } from '~/server/utils/company'

/**
 * DELETE /api/company/members/:id
 * Entfernt einen Mitarbeiter aus dem Unternehmen.
 */
export default defineEventHandler((event) => {
  const user = requireCompanyAdmin(event)
  const targetId = getRouterParam(event, 'id')
  const companyId = resolveCompanyId(user, getQuery(event).company_id as string | undefined)

  if (targetId === user.id) {
    throw createError({ statusCode: 400, statusMessage: 'Du kannst dich nicht selbst entfernen' })
  }

  const target = db.prepare('SELECT id, company_id FROM users WHERE id = ?').get(targetId) as any
  if (!target || target.company_id !== companyId) {
    throw createError({ statusCode: 404, statusMessage: 'Mitarbeiter nicht gefunden' })
  }

  db.prepare('UPDATE users SET company_id = NULL, company_role = NULL, is_pro = 0 WHERE id = ?')
    .run(targetId)

  return { success: true }
})
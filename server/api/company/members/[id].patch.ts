import { db } from '~/server/db'
import { requireCompanyAdmin, resolveCompanyId } from '~/server/utils/company'

/**
 * PATCH /api/company/members/:id
 * Ändert die Rolle eines Mitarbeiters (Co-Admin <-> Mitarbeiter).
 */
export default defineEventHandler(async (event) => {
  const user = requireCompanyAdmin(event)
  const body = await readBody(event)
  const targetId = getRouterParam(event, 'id')
  const companyId = resolveCompanyId(user, body?.company_id)

  const target = db.prepare('SELECT id, company_id FROM users WHERE id = ?').get(targetId) as any
  if (!target || target.company_id !== companyId) {
    throw createError({ statusCode: 404, statusMessage: 'Mitarbeiter nicht gefunden' })
  }

  if (typeof body?.role !== 'string') {
    throw createError({ statusCode: 400, statusMessage: 'Keine Änderungen übergeben' })
  }

  const role = body.role === 'admin' ? 'admin' : 'member'
  if (target.id === user.id && role !== 'admin') {
    throw createError({ statusCode: 400, statusMessage: 'Du kannst dich nicht selbst zum Mitarbeiter herabstufen' })
  }

  db.prepare('UPDATE users SET company_role = ? WHERE id = ?').run(role, targetId)
  return { success: true, role }
})
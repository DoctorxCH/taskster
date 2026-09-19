import { db } from '~/server/db'
import {
  requireCompanyAdmin,
  resolveCompanyId,
  getCompanyOr404,
  notifySuperadmins
} from '~/server/utils/company'

const VALID_PLANS = ['starter', 'pro', 'enterprise']

/**
 * POST /api/company/upgrade
 * Erstellt eine Plan-Upgrade-/Sitzplatz-Anfrage für das Unternehmen.
 */
export default defineEventHandler(async (event) => {
  const user = requireCompanyAdmin(event)
  const body = await readBody(event)
  const companyId = resolveCompanyId(user, body?.company_id)
  const company = getCompanyOr404(companyId)

  const requestedPlan = String(body?.plan || '').toLowerCase()
  if (!VALID_PLANS.includes(requestedPlan)) {
    throw createError({ statusCode: 400, statusMessage: 'Ungültiger Plan' })
  }

  const request = {
    id: 'req_' + Math.random().toString(36).slice(2, 10),
    plan: requestedPlan,
    seats: body?.seats ? Number(body.seats) : null,
    note: String(body?.note || '').trim(),
    requested_by: user.id,
    requested_by_name: user.name,
    requested_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    status: 'pending'
  }

  const settings = { ...company.settings }
  settings.upgrade_requests = [...(settings.upgrade_requests ?? []), request]
  db.prepare('UPDATE companies SET settings = ? WHERE id = ?')
    .run(JSON.stringify(settings), companyId)

  notifySuperadmins(
    'system',
    'Plan-Upgrade-Anfrage',
    `${company.name} möchte auf ${requestedPlan} wechseln.`,
    companyId
  )

  return { success: true, message: 'Upgrade-Anfrage übermittelt', id: request.id }
})
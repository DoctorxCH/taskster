import { db } from '~/server/db'
import {
  requireCompanyAdmin,
  resolveCompanyId,
  getCompanyOr404,
  notifySuperadmins
} from '~/server/utils/company'

const VALID_PRIORITIES = ['low', 'normal', 'high', 'urgent']

/**
 * POST /api/company/support
 * Erstellt ein Support-Ticket für das Unternehmen.
 */
export default defineEventHandler(async (event) => {
  const user = requireCompanyAdmin(event)
  const body = await readBody(event)
  const companyId = resolveCompanyId(user, body?.company_id)
  const company = getCompanyOr404(companyId)

  const subject = String(body?.subject || '').trim()
  const message = String(body?.message || '').trim()
  const priority = VALID_PRIORITIES.includes(body?.priority) ? body.priority : 'normal'

  if (!subject || !message) {
    throw createError({ statusCode: 400, statusMessage: 'Betreff und Nachricht erforderlich' })
  }

  const ticket = {
    id: 'tkt_' + Math.random().toString(36).slice(2, 10),
    subject,
    message,
    priority,
    created_by: user.id,
    created_by_name: user.name,
    created_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    status: 'open'
  }

  const settings = { ...company.settings }
  settings.support_tickets = [...(settings.support_tickets ?? []), ticket]
  db.prepare('UPDATE companies SET settings = ? WHERE id = ?')
    .run(JSON.stringify(settings), companyId)

  notifySuperadmins('system', `Support-Anfrage: ${subject}`, `${company.name}: ${message.slice(0, 120)}`, companyId)

  return { success: true, ticket_id: ticket.id }
})
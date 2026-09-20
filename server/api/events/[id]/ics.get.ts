import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { canAccessEvent, buildIcs } from '~/server/utils/calendar'

/**
 * GET /api/events/:id/ics
 * Liefert die ICS-Datei zum Download (Import in Outlook/Google/Apple).
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')

  const evt = db.prepare(`
    SELECT e.*, u.name AS owner_name, u.email AS owner_email
    FROM calendar_events e
    LEFT JOIN users u ON u.id = e.owner_id
    WHERE e.id = ?
  `).get(id) as any

  if (!evt) throw createError({ statusCode: 404, statusMessage: 'Termin nicht gefunden' })
  if (!canAccessEvent(user, evt)) {
    throw createError({ statusCode: 404, statusMessage: 'Termin nicht gefunden' })
  }

  const attendees = db.prepare(
    'SELECT email, name, status FROM event_attendees WHERE event_id = ?'
  ).all(id) as any[]

  const ics = buildIcs({
    id: evt.id,
    title: evt.title,
    description: evt.description,
    location: evt.location,
    start_at: evt.start_at,
    end_at: evt.end_at,
    all_day: evt.all_day,
    organizerName: evt.owner_name,
    organizerEmail: evt.owner_email,
    attendees,
    method: 'PUBLISH'
  })

  setHeader(event, 'Content-Type', 'text/calendar; charset=utf-8')
  setHeader(event, 'Content-Disposition', `attachment; filename="termin-${evt.id}.ics"`)
  return ics
})

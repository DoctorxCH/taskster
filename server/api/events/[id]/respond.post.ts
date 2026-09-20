import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * POST /api/events/:id/respond
 * Body: { status: 'accepted' | 'declined' | 'tentative' }
 *
 * Der eingeladene Nutzer antwortet auf eine Einladung.
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const status = ['accepted', 'declined', 'tentative'].includes(body?.status) ? body.status : null
  if (!status) throw createError({ statusCode: 400, statusMessage: 'Ungültiger Status' })

  const evt = db.prepare('SELECT * FROM calendar_events WHERE id = ?').get(id) as any
  if (!evt) throw createError({ statusCode: 404, statusMessage: 'Termin nicht gefunden' })

  const attendee = db.prepare(
    'SELECT id FROM event_attendees WHERE event_id = ? AND (user_id = ? OR LOWER(email) = LOWER(?))'
  ).get(id, user.id, user.email) as any

  if (!attendee) throw createError({ statusCode: 404, statusMessage: 'Du bist nicht zu diesem Termin eingeladen' })

  db.prepare('UPDATE event_attendees SET status = ?, responded_at = datetime(\'now\') WHERE id = ?')
    .run(status, attendee.id)

  // Organisator informieren
  try {
    const label = status === 'accepted' ? 'zugesagt' : status === 'declined' ? 'abgesagt' : 'mit Vorbehalt zugesagt'
    db.prepare(`
      INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
      VALUES (?, ?, 'calendar_response', ?, ?, 'event', ?, 0, datetime('now'))
    `).run(
      'notif_' + Math.random().toString(36).slice(2, 10),
      evt.owner_id,
      `Antwort: ${evt.title}`,
      `${user.name} hat ${label}.`,
      id
    )
  } catch { /* ignore */ }

  return { success: true, status }
})

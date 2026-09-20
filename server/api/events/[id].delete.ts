import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { canEditEvent, buildIcs, queueEmail } from '~/server/utils/calendar'

/**
 * DELETE /api/events/:id
 * Löscht einen Termin und sendet Absagen an alle Teilnehmer.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')

  const existing = db.prepare('SELECT * FROM calendar_events WHERE id = ?').get(id) as any
  // Zero-Trust: fehlende Berechtigung wird wie "nicht gefunden" behandelt (kein Info-Leak)
  if (!existing || !canEditEvent(user, existing)) {
    throw createError({ statusCode: 404, statusMessage: 'Termin nicht gefunden' })
  }

  const attendees = db.prepare(
    'SELECT email, name, user_id FROM event_attendees WHERE event_id = ? AND is_organizer = 0'
  ).all(id) as any[]

  // Absage-ICS + Mail
  if (attendees.length > 0) {
    const ics = buildIcs({
      id,
      title: existing.title,
      description: existing.description,
      location: existing.location,
      start_at: existing.start_at,
      end_at: existing.end_at,
      all_day: existing.all_day,
      organizerName: user.name,
      organizerEmail: user.email,
      attendees: attendees.map((a) => ({ email: a.email, name: a.name })),
      status: 'cancelled',
      method: 'CANCEL',
      sequence: 2
    })

    for (const a of attendees) {
      if (a.user_id) {
        try {
          db.prepare(`
            INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
            VALUES (?, ?, 'calendar_cancel', ?, ?, 'event', ?, 0, datetime('now'))
          `).run(
            'notif_' + Math.random().toString(36).slice(2, 10),
            a.user_id,
            `Termin abgesagt: ${existing.title}`,
            `${user.name} hat den Termin abgesagt.`,
            id
          )
        } catch { /* ignore */ }
      }
      queueEmail({
        to: a.email,
        toName: a.name,
        subject: `Termin abgesagt: ${existing.title}`,
        body: `${user.name} hat den Termin "${existing.title}" abgesagt.\n\nBeginn war: ${existing.start_at}\n\n— Taskster`,
        ics
      })
    }
  }

  db.prepare('DELETE FROM calendar_events WHERE id = ?').run(id)

  return { success: true, notified: attendees.length }
})

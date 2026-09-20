import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { canEditEvent, buildIcs, buildInviteBody, queueEmail } from '~/server/utils/calendar'

/**
 * PUT /api/events/:id
 *
 * Aktualisiert einen Termin (auch Drag & Drop: nur start_at/end_at).
 * Bei Zeitänderung werden alle Teilnehmer erneut benachrichtigt.
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const existing = db.prepare('SELECT * FROM calendar_events WHERE id = ?').get(id) as any
  // Zero-Trust: fehlende Berechtigung wird wie "nicht gefunden" behandelt (kein Info-Leak)
  if (!existing || !canEditEvent(user, existing)) {
    throw createError({ statusCode: 404, statusMessage: 'Termin nicht gefunden' })
  }

  const title = body?.title !== undefined ? String(body.title).trim() : existing.title
  const startAt = body?.start_at !== undefined ? String(body.start_at) : existing.start_at
  const endAt = body?.end_at !== undefined ? String(body.end_at) : existing.end_at

  if (!title) throw createError({ statusCode: 400, statusMessage: 'Betreff erforderlich' })
  if (endAt < startAt) throw createError({ statusCode: 400, statusMessage: 'Ende darf nicht vor dem Start liegen' })

  const timeChanged = startAt !== existing.start_at || endAt !== existing.end_at

  const latitude = body?.latitude !== undefined
    ? (body.latitude === null || body.latitude === '' ? null : Number(body.latitude))
    : existing.latitude
  const longitude = body?.longitude !== undefined
    ? (body.longitude === null || body.longitude === '' ? null : Number(body.longitude))
    : existing.longitude

  db.prepare(`
    UPDATE calendar_events SET
      title = ?, description = ?, location = ?, latitude = ?, longitude = ?,
      start_at = ?, end_at = ?,
      all_day = ?, priority = ?, visibility = ?, category_id = ?, project_id = ?,
      color = ?, reminder_minutes = ?, updated_at = datetime('now')
    WHERE id = ?
  `).run(
    title,
    body?.description !== undefined ? body.description : existing.description,
    body?.location !== undefined ? body.location : existing.location,
    latitude,
    longitude,
    startAt,
    endAt,
    body?.all_day !== undefined ? (body.all_day ? 1 : 0) : existing.all_day,
    body?.priority !== undefined ? body.priority : existing.priority,
    body?.visibility !== undefined ? body.visibility : existing.visibility,
    body?.category_id !== undefined ? body.category_id : existing.category_id,
    body?.project_id !== undefined ? body.project_id : existing.project_id,
    body?.color !== undefined ? body.color : existing.color,
    body?.reminder_minutes !== undefined ? body.reminder_minutes : existing.reminder_minutes,
    id
  )

  // --- Teilnehmer aktualisieren (falls übergeben) ------------------------
  if (Array.isArray(body?.attendees)) {
    const keepEmails = new Set<string>([String(user.email).toLowerCase()])
    const newInvites: any[] = []

    for (const a of body.attendees) {
      const email = String(a?.email || '').trim().toLowerCase()
      if (!email) continue
      keepEmails.add(email)

      const existingAtt = db.prepare('SELECT id FROM event_attendees WHERE event_id = ? AND LOWER(email) = ?')
        .get(id, email) as any

      if (existingAtt) {
        db.prepare('UPDATE event_attendees SET role = ?, name = COALESCE(?, name) WHERE id = ?')
          .run(a?.role === 'optional' ? 'optional' : 'required', a?.name || null, existingAtt.id)
      } else {
        const u = db.prepare('SELECT id, name FROM users WHERE LOWER(email) = ?').get(email) as any
        db.prepare(`
          INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
          VALUES (?, ?, ?, ?, ?, ?, 'pending', 0)
        `).run(
          'att_' + Math.random().toString(36).slice(2, 10),
          id, u?.id || null, email, a?.name || u?.name || null,
          a?.role === 'optional' ? 'optional' : 'required'
        )
        newInvites.push({ email, name: a?.name || u?.name || null, user_id: u?.id || null })
      }
    }

    // Entfernte Teilnehmer löschen
    const all = db.prepare('SELECT id, email, is_organizer FROM event_attendees WHERE event_id = ?').all(id) as any[]
    for (const a of all) {
      if (a.is_organizer) continue
      if (!keepEmails.has(String(a.email).toLowerCase())) {
        db.prepare('DELETE FROM event_attendees WHERE id = ?').run(a.id)
      }
    }

    // Neue Teilnehmer benachrichtigen
    for (const i of newInvites) {
      if (i.user_id) {
        try {
          db.prepare(`
            INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
            VALUES (?, ?, 'calendar_invite', ?, ?, 'event', ?, 0, datetime('now'))
          `).run(
            'notif_' + Math.random().toString(36).slice(2, 10),
            i.user_id, `Einladung: ${title}`, `${user.name} lädt dich ein – ${startAt}`, id
          )
        } catch { /* ignore */ }
      }
      queueEmail({
        to: i.email,
        toName: i.name,
        subject: `Einladung: ${title}`,
        body: buildInviteBody({
          organizerName: user.name, title, startAt, endAt,
          location: body?.location ?? existing.location,
          description: body?.description ?? existing.description,
          allDay: Boolean(body?.all_day ?? existing.all_day)
        }),
        ics: buildIcs({
          id, title,
          description: body?.description ?? existing.description,
          location: body?.location ?? existing.location,
          start_at: startAt, end_at: endAt,
          all_day: body?.all_day ?? existing.all_day,
          organizerName: user.name, organizerEmail: user.email,
          method: 'REQUEST'
        })
      })
    }
  }

  // --- Bei Zeitänderung alle benachrichtigen -----------------------------
  if (timeChanged) {
    const attendees = db.prepare(
      'SELECT email, name, user_id FROM event_attendees WHERE event_id = ? AND is_organizer = 0'
    ).all(id) as any[]

    const ics = buildIcs({
      id, title,
      description: body?.description ?? existing.description,
      location: body?.location ?? existing.location,
      start_at: startAt, end_at: endAt,
      all_day: body?.all_day ?? existing.all_day,
      organizerName: user.name, organizerEmail: user.email,
      attendees: attendees.map((a) => ({ email: a.email, name: a.name })),
      method: 'REQUEST',
      sequence: 1
    })

    for (const a of attendees) {
      if (a.user_id) {
        try {
          db.prepare(`
            INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
            VALUES (?, ?, 'calendar_update', ?, ?, 'event', ?, 0, datetime('now'))
          `).run(
            'notif_' + Math.random().toString(36).slice(2, 10),
            a.user_id, `Termin verschoben: ${title}`, `Neuer Zeitpunkt: ${startAt}`, id
          )
        } catch { /* ignore */ }
      }
      queueEmail({
        to: a.email,
        toName: a.name,
        subject: `Termin verschoben: ${title}`,
        body: buildInviteBody({
          organizerName: user.name, title, startAt, endAt,
          location: body?.location ?? existing.location,
          description: body?.description ?? existing.description,
          all_day: Boolean(body?.all_day ?? existing.all_day)
        }),
        ics
      })
    }
  }

  return { success: true, timeChanged }
})

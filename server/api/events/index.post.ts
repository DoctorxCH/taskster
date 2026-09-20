import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { buildIcs, buildInviteBody, queueEmail } from '~/server/utils/calendar'

/**
 * POST /api/events
 *
 * Erstellt einen Termin. Optional mit Teilnehmern (Einladungen).
 *
 * Body:
 *   title, description, location, start_at, end_at, all_day,
 *   priority, visibility, category_id, project_id, color, reminder_minutes,
 *   attendees: [{ email, name?, role? }]
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const title = String(body?.title || '').trim()
  const startAt = String(body?.start_at || '').trim()
  const endAt = String(body?.end_at || '').trim()

  if (!title) throw createError({ statusCode: 400, statusMessage: 'Betreff erforderlich' })
  if (!startAt || !endAt) throw createError({ statusCode: 400, statusMessage: 'Start und Ende erforderlich' })
  if (endAt < startAt) throw createError({ statusCode: 400, statusMessage: 'Ende darf nicht vor dem Start liegen' })

  const id = 'evt_' + Math.random().toString(36).slice(2, 10)
  const allDay = body?.all_day ? 1 : 0
  const priority = ['niedrig', 'normal', 'hoch', 'dringend'].includes(body?.priority) ? body.priority : 'normal'
  const visibility = ['private', 'company'].includes(body?.visibility) ? body.visibility : 'private'

  const latitude = Number.isFinite(Number(body?.latitude)) && body?.latitude !== null && body?.latitude !== '' ? Number(body.latitude) : null
  const longitude = Number.isFinite(Number(body?.longitude)) && body?.longitude !== null && body?.longitude !== '' ? Number(body.longitude) : null

  db.prepare(`
    INSERT INTO calendar_events
      (id, owner_id, company_id, project_id, category_id, title, description, location,
       latitude, longitude, start_at, end_at, all_day, priority, status, visibility, color, reminder_minutes)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)
  `).run(
    id,
    user.id,
    user.company_id || null,
    body?.project_id || null,
    body?.category_id || null,
    title,
    body?.description || null,
    body?.location || null,
    latitude,
    longitude,
    startAt,
    endAt,
    allDay,
    priority,
    visibility,
    body?.color || null,
    body?.reminder_minutes != null ? Number(body.reminder_minutes) : null
  )

  // --- Organisator als Teilnehmer eintragen ------------------------------
  db.prepare(`
    INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
    VALUES (?, ?, ?, ?, ?, 'required', 'accepted', 1)
  `).run('att_' + Math.random().toString(36).slice(2, 10), id, user.id, user.email, user.name)

  // --- Teilnehmer einladen ----------------------------------------------
  const attendees: any[] = Array.isArray(body?.attendees) ? body.attendees : []
  const invited: any[] = []

  for (const a of attendees) {
    const email = String(a?.email || '').trim().toLowerCase()
    if (!email || email === String(user.email).toLowerCase()) continue

    // Existiert der Nutzer in Taskster?
    const existing = db.prepare('SELECT id, name FROM users WHERE LOWER(email) = ?').get(email) as any
    const name = a?.name || existing?.name || null
    const role = ['required', 'optional'].includes(a?.role) ? a.role : 'required'

    try {
      db.prepare(`
        INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', 0)
      `).run('att_' + Math.random().toString(36).slice(2, 10), id, existing?.id || null, email, name, role)
    } catch {
      continue // Duplikat
    }

    invited.push({ email, name, user_id: existing?.id || null })

    // In-App-Benachrichtigung
    if (existing?.id) {
      try {
        db.prepare(`
          INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
          VALUES (?, ?, 'calendar_invite', ?, ?, 'event', ?, 0, datetime('now'))
        `).run(
          'notif_' + Math.random().toString(36).slice(2, 10),
          existing.id,
          `Einladung: ${title}`,
          `${user.name} lädt dich ein – ${startAt}`,
          id
        )
      } catch { /* ignore */ }
    }
  }

  // --- E-Mail-Einladungen (Outbox + ICS) ---------------------------------
  if (invited.length > 0) {
    const ics = buildIcs({
      id,
      title,
      description: body?.description,
      location: body?.location,
      start_at: startAt,
      end_at: endAt,
      all_day: allDay,
      organizerName: user.name,
      organizerEmail: user.email,
      attendees: invited.map((i) => ({ email: i.email, name: i.name, status: 'pending' })),
      method: 'REQUEST'
    })

    const mailBody = buildInviteBody({
      organizerName: user.name,
      title,
      startAt,
      endAt,
      location: body?.location,
      description: body?.description,
      allDay: Boolean(allDay)
    })

    for (const i of invited) {
      queueEmail({
        to: i.email,
        toName: i.name,
        subject: `Einladung: ${title}`,
        body: mailBody,
        ics
      })
    }
  }

  return {
    success: true,
    id,
    invited: invited.length,
    message: invited.length > 0
      ? `Termin erstellt, ${invited.length} Einladung(en) versandt.`
      : 'Termin erstellt.'
  }
})

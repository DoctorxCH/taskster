import { db } from '~/server/db'

/**
 * Kalender-Hilfsfunktionen: ICS-Export, E-Mail-Outbox, Termin-Formatierung.
 *
 * E-Mail-Architektur:
 *   Es gibt KEINEN SMTP-Versand (Shared Hosting). Stattdessen:
 *   1. In-App-Benachrichtigung (immer, sofort)
 *   2. Eintrag in `email_outbox` mit ICS-Anhang (optional, per Cron/Worker versendbar)
 *   3. ICS-Datei kann der Nutzer direkt herunterladen und in Outlook/Google importieren
 */

// ---------------------------------------------------------------------------
// ICS (iCalendar) — RFC 5545
// ---------------------------------------------------------------------------

/** Formatiert ein Datum als ICS-Zeitstempel (UTC): 20260920T140000Z */
export function toIcsDate(value: string | Date): string {
  const d = typeof value === 'string' ? new Date(value.replace(' ', 'T')) : value
  if (isNaN(d.getTime())) return ''
  const p = (n: number) => String(n).padStart(2, '0')
  return (
    d.getUTCFullYear() +
    p(d.getUTCMonth() + 1) +
    p(d.getUTCDate()) +
    'T' +
    p(d.getUTCHours()) +
    p(d.getUTCMinutes()) +
    p(d.getUTCSeconds()) +
    'Z'
  )
}

/** Formatiert ein Datum als ICS-Ganztaq (YYYYMMDD). */
export function toIcsDateOnly(value: string | Date): string {
  const d = typeof value === 'string' ? new Date(value.replace(' ', 'T')) : value
  if (isNaN(d.getTime())) return ''
  const p = (n: number) => String(n).padStart(2, '0')
  return d.getUTCFullYear() + p(d.getUTCMonth() + 1) + p(d.getUTCDate())
}

/** Escaped Sonderzeichen für ICS-Texte. */
function icsEscape(text: string): string {
  return String(text || '')
    .replace(/\\/g, '\\\\')
    .replace(/;/g, '\\;')
    .replace(/,/g, '\\,')
    .replace(/\r?\n/g, '\\n')
}

/** Faltet lange Zeilen nach RFC 5545 (max. 75 Oktette). */
function foldLine(line: string): string {
  if (line.length <= 75) return line
  const parts: string[] = []
  let rest = line
  parts.push(rest.slice(0, 75))
  rest = rest.slice(75)
  while (rest.length > 0) {
    parts.push(' ' + rest.slice(0, 74))
    rest = rest.slice(74)
  }
  return parts.join('\r\n')
}

export interface IcsEvent {
  id: string
  title: string
  description?: string | null
  location?: string | null
  start_at: string
  end_at: string
  all_day?: number | boolean
  organizerName?: string
  organizerEmail?: string
  attendees?: { email: string; name?: string | null; status?: string }[]
  status?: string
  sequence?: number
  method?: 'REQUEST' | 'CANCEL' | 'PUBLISH'
}

/**
 * Erzeugt eine ICS-Datei für einen Termin.
 * `method` steuert den Zweck: REQUEST (Einladung), CANCEL (Absage), PUBLISH (Export).
 */
export function buildIcs(event: IcsEvent): string {
  const allDay = Boolean(event.all_day)
  const method = event.method || 'REQUEST'
  const now = toIcsDate(new Date())

  const lines: string[] = [
    'BEGIN:VCALENDAR',
    'VERSION:2.0',
    'PRODID:-//Taskster//Kalender//DE',
    'CALSCALE:GREGORIAN',
    `METHOD:${method}`
  ]

  lines.push('BEGIN:VEVENT')
  const icsDomain = event.organizerEmail && event.organizerEmail.includes('@') ? event.organizerEmail.split('@')[1] : 'kurka.ch'
  lines.push(`UID:${event.id}@${icsDomain}`)
  lines.push(`DTSTAMP:${now}`)

  if (allDay) {
    lines.push(`DTSTART;VALUE=DATE:${toIcsDateOnly(event.start_at)}`)
    // DTEND ist bei ganztägigen Terminen exklusiv → +1 Tag
    const end = new Date(String(event.end_at).replace(' ', 'T'))
    end.setDate(end.getDate() + 1)
    lines.push(`DTEND;VALUE=DATE:${toIcsDateOnly(end)}`)
  } else {
    lines.push(`DTSTART:${toIcsDate(event.start_at)}`)
    lines.push(`DTEND:${toIcsDate(event.end_at)}`)
  }

  lines.push(foldLine(`SUMMARY:${icsEscape(event.title)}`))
  if (event.description) lines.push(foldLine(`DESCRIPTION:${icsEscape(event.description)}`))
  if (event.location) lines.push(foldLine(`LOCATION:${icsEscape(event.location)}`))

  lines.push(`STATUS:${event.status === 'cancelled' ? 'CANCELLED' : 'CONFIRMED'}`)
  lines.push(`SEQUENCE:${event.sequence ?? 0}`)

  if (event.organizerEmail) {
    const cn = event.organizerName ? `;CN=${icsEscape(event.organizerName)}` : ''
    lines.push(foldLine(`ORGANIZER${cn}:mailto:${event.organizerEmail}`))
  }

  for (const a of event.attendees || []) {
    const cn = a.name ? `;CN=${icsEscape(a.name)}` : ''
    const partstat =
      a.status === 'accepted' ? 'ACCEPTED'
      : a.status === 'declined' ? 'DECLINED'
      : a.status === 'tentative' ? 'TENTATIVE'
      : 'NEEDS-ACTION'
    lines.push(foldLine(`ATTENDEE${cn};PARTSTAT=${partstat};ROLE=REQ-PARTICIPANT:mailto:${a.email}`))
  }

  lines.push('END:VEVENT')
  lines.push('END:VCALENDAR')

  return lines.join('\r\n')
}

// ---------------------------------------------------------------------------
// E-Mail-Outbox
// ---------------------------------------------------------------------------

export interface OutboxMail {
  to: string
  toName?: string | null
  subject: string
  body: string
  ics?: string | null
}

/**
 * Legt eine E-Mail in die Outbox. Der eigentliche Versand erfolgt später
 * (Cron/Worker) — so bleibt die App auf Shared Hosting funktionsfähig.
 */
export function queueEmail(mail: OutboxMail): string {
  const id = 'mail_' + Math.random().toString(36).slice(2, 10)
  try {
    db.prepare(`
      INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status)
      VALUES (?, ?, ?, ?, ?, ?, 'pending')
    `).run(id, mail.to, mail.toName || null, mail.subject, mail.body, mail.ics || null)
    
    // Asynchron im Hintergrund verarbeiten
    import('./mailer').then(({ sendSmtpEmail }) => {
      sendSmtpEmail({
        to: mail.to,
        toName: mail.toName,
        subject: mail.subject,
        bodyHtml: mail.body,
        icsContent: mail.ics,
        triggerEvent: 'calendar_invite'
      }).then(() => {
        try {
          db.prepare("UPDATE email_outbox SET status = 'sent', sent_at = datetime('now') WHERE id = ?").run(id)
        } catch {}
      }).catch((e) => {
        try {
          db.prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?").run(e.message, id)
        } catch {}
      })
    })
  } catch {
    // Outbox-Fehler dürfen die Hauptaktion nicht blockieren
  }
  return id
}

/** Erzeugt eine lesbare Text-Einladung. */
export function buildInviteBody(opts: {
  organizerName: string
  title: string
  startAt: string
  endAt: string
  location?: string | null
  description?: string | null
  allDay?: boolean
}): string {
  const fmt = (v: string) => {
    const d = new Date(v.replace(' ', 'T'))
    if (isNaN(d.getTime())) return v
    return d.toLocaleString('de-CH', {
      weekday: 'long', day: '2-digit', month: 'long', year: 'numeric',
      hour: '2-digit', minute: '2-digit'
    })
  }

  const lines = [
    `${opts.organizerName} lädt dich zu einem Termin ein:`,
    '',
    `Betreff:  ${opts.title}`,
    `Beginn:   ${opts.allDay ? fmt(opts.startAt).split(',')[0] + ' (ganztägig)' : fmt(opts.startAt)}`,
    `Ende:     ${opts.allDay ? fmt(opts.endAt).split(',')[0] + ' (ganztägig)' : fmt(opts.endAt)}`
  ]
  if (opts.location) lines.push(`Ort:      ${opts.location}`)
  if (opts.description) {
    lines.push('', 'Beschreibung:', opts.description)
  }
  lines.push(
    '',
    'Die angehängte Datei (termin.ics) kannst du direkt in Outlook, Google Kalender',
    'oder Apple Kalender öffnen, um den Termin zu übernehmen.',
    '',
    '— Taskster'
  )
  return lines.join('\n')
}

// ---------------------------------------------------------------------------
// Zugriffsprüfung
// ---------------------------------------------------------------------------

/**
 * Prüft, ob der Nutzer einen Termin sehen darf.
 * Sichtbar, wenn: Organisator, Eingeladener, oder Firmen-Termin der eigenen Firma.
 */
export function canAccessEvent(user: any, event: any): boolean {
  if (!event) return false
  if (event.owner_id === user.id) return true
  if (user.is_superadmin) return true

  // Eingeladen?
  const attendee = db.prepare(
    'SELECT 1 FROM event_attendees WHERE event_id = ? AND (user_id = ? OR LOWER(email) = LOWER(?))'
  ).get(event.id, user.id, user.email)
  if (attendee) return true

  // Firmen-Termin der eigenen Firma
  if (event.visibility === 'company' && event.company_id && event.company_id === user.company_id) {
    return true
  }

  return false
}

/** Prüft, ob der Nutzer einen Termin bearbeiten darf (Organisator oder Superadmin). */
export function canEditEvent(user: any, event: any): boolean {
  if (!event) return false
  if (event.owner_id === user.id) return true
  if (user.is_superadmin) return true
  return false
}

/**
 * Persönliche Benutzer-Einstellungen (users.settings, JSON).
 *
 * Diese Struktur ist die Single Source of Truth für alle persönlichen
 * Voreinstellungen. Sie wird serverseitig normalisiert, damit fehlende oder
 * manipulierte Werte nie zu ungültigen Zuständen im Client führen.
 */

export interface UserSettings {
  // --- Darstellung ---
  language: 'de' | 'en' | 'sk'
  whisper_language: 'de' | 'de-CH' | 'en' | 'fr' | 'it' | 'auto'
  theme: 'light' | 'dark' | 'system'
  density: 'comfortable' | 'compact'
  start_page: 'dashboard' | 'calendar' | 'time' | 'contacts'
  timezone: string

  // --- Kalender ---
  calendar: {
    default_view: 'month' | 'week' | 'day'
    week_start: 0 | 1 // 0 = Sonntag, 1 = Montag
    show_week_numbers: boolean
    show_weekends: boolean
    workday_start: string // "07:00"
    workday_end: string // "17:00"
    slot_minutes: 15 | 30 | 60
    default_duration_minutes: number
    default_reminder_minutes: number | null
    default_category_id: string | null
    default_visibility: 'private' | 'company'
    show_tasks: boolean
    show_declined: boolean
    time_format: '24h' | '12h'
  }

  // --- Benachrichtigungen ---
  notifications: {
    browser: boolean
    email: boolean
    in_app: boolean
    sound: boolean
    digest: 'off' | 'daily' | 'weekly'
    events: {
      calendar_invite: boolean
      calendar_change: boolean
      calendar_cancel: boolean
      calendar_reminder: boolean
      task_assigned: boolean
      task_due: boolean
      task_comment: boolean
      mention: boolean
      budget_warning: boolean
    }
  }
}

export const DEFAULT_SETTINGS: UserSettings = {
  language: 'de',
  whisper_language: 'de',
  theme: 'light',
  density: 'comfortable',
  start_page: 'dashboard',
  timezone: 'Europe/Zurich',

  calendar: {
    default_view: 'month',
    week_start: 1,
    show_week_numbers: false,
    show_weekends: true,
    workday_start: '07:00',
    workday_end: '17:00',
    slot_minutes: 30,
    default_duration_minutes: 60,
    default_reminder_minutes: 15,
    default_category_id: null,
    default_visibility: 'private',
    show_tasks: true,
    show_declined: false,
    time_format: '24h'
  },

  notifications: {
    browser: false,
    email: true,
    in_app: true,
    sound: false,
    digest: 'off',
    events: {
      calendar_invite: true,
      calendar_change: true,
      calendar_cancel: true,
      calendar_reminder: true,
      task_assigned: true,
      task_due: true,
      task_comment: true,
      mention: true,
      budget_warning: true
    }
  }
}

const HHMM = /^([01]\d|2[0-3]):([0-5]\d)$/

function pickEnum<T extends string>(value: any, allowed: readonly T[], fallback: T): T {
  return allowed.includes(value) ? (value as T) : fallback
}

function pickBool(value: any, fallback: boolean): boolean {
  return typeof value === 'boolean' ? value : fallback
}

function pickInt(value: any, allowed: readonly number[], fallback: number): number {
  const n = Number(value)
  return allowed.includes(n) ? n : fallback
}

function pickTime(value: any, fallback: string): string {
  return typeof value === 'string' && HHMM.test(value) ? value : fallback
}

function pickTimezone(value: any, fallback: string): string {
  if (typeof value !== 'string' || !value.trim()) return fallback
  try {
    Intl.DateTimeFormat(undefined, { timeZone: value.trim() })
    return value.trim()
  } catch {
    return fallback
  }
}

/**
 * Führt gespeicherte Einstellungen mit den Defaults zusammen und validiert
 * jeden Wert. Unbekannte Schlüssel werden verworfen.
 */
export function normalizeSettings(raw: any): UserSettings {
  const d = DEFAULT_SETTINGS
  const src = raw && typeof raw === 'object' ? raw : {}
  const cal = src.calendar && typeof src.calendar === 'object' ? src.calendar : {}
  const notif = src.notifications && typeof src.notifications === 'object' ? src.notifications : {}
  const ev = notif.events && typeof notif.events === 'object' ? notif.events : {}

  const duration = Number(cal.default_duration_minutes)
  const reminder = cal.default_reminder_minutes

  return {
    language: pickEnum(src.language, ['de', 'en', 'sk'] as const, d.language),
    whisper_language: pickEnum(src.whisper_language, ['de', 'de-CH', 'en', 'fr', 'it', 'auto'] as const, d.whisper_language),
    theme: pickEnum(src.theme, ['light', 'dark', 'system'] as const, d.theme),
    density: pickEnum(src.density, ['comfortable', 'compact'] as const, d.density),
    start_page: pickEnum(src.start_page, ['dashboard', 'calendar', 'time', 'contacts'] as const, d.start_page),
    timezone: pickTimezone(src.timezone, d.timezone),

    calendar: {
      default_view: pickEnum(cal.default_view, ['month', 'week', 'day'] as const, d.calendar.default_view),
      week_start: pickInt(cal.week_start, [0, 1], d.calendar.week_start) as 0 | 1,
      show_week_numbers: pickBool(cal.show_week_numbers, d.calendar.show_week_numbers),
      show_weekends: pickBool(cal.show_weekends, d.calendar.show_weekends),
      workday_start: pickTime(cal.workday_start, d.calendar.workday_start),
      workday_end: pickTime(cal.workday_end, d.calendar.workday_end),
      slot_minutes: pickInt(cal.slot_minutes, [15, 30, 60], d.calendar.slot_minutes) as 15 | 30 | 60,
      default_duration_minutes: Number.isFinite(duration) && duration >= 5 && duration <= 1440
        ? Math.round(duration)
        : d.calendar.default_duration_minutes,
      default_reminder_minutes: reminder === null
        ? null
        : (Number.isFinite(Number(reminder)) && Number(reminder) >= 0 ? Math.round(Number(reminder)) : d.calendar.default_reminder_minutes),
      default_category_id: typeof cal.default_category_id === 'string' && cal.default_category_id
        ? cal.default_category_id
        : null,
      default_visibility: pickEnum(cal.default_visibility, ['private', 'company'] as const, d.calendar.default_visibility),
      show_tasks: pickBool(cal.show_tasks, d.calendar.show_tasks),
      show_declined: pickBool(cal.show_declined, d.calendar.show_declined),
      time_format: pickEnum(cal.time_format, ['24h', '12h'] as const, d.calendar.time_format)
    },

    notifications: {
      browser: pickBool(notif.browser, d.notifications.browser),
      email: pickBool(notif.email, d.notifications.email),
      in_app: pickBool(notif.in_app, d.notifications.in_app),
      sound: pickBool(notif.sound, d.notifications.sound),
      digest: pickEnum(notif.digest, ['off', 'daily', 'weekly'] as const, d.notifications.digest),
      events: {
        calendar_invite: pickBool(ev.calendar_invite, d.notifications.events.calendar_invite),
        calendar_change: pickBool(ev.calendar_change, d.notifications.events.calendar_change),
        calendar_cancel: pickBool(ev.calendar_cancel, d.notifications.events.calendar_cancel),
        calendar_reminder: pickBool(ev.calendar_reminder, d.notifications.events.calendar_reminder),
        task_assigned: pickBool(ev.task_assigned, d.notifications.events.task_assigned),
        task_due: pickBool(ev.task_due, d.notifications.events.task_due),
        task_comment: pickBool(ev.task_comment, d.notifications.events.task_comment),
        mention: pickBool(ev.mention, d.notifications.events.mention),
        budget_warning: pickBool(ev.budget_warning, d.notifications.events.budget_warning)
      }
    }
  }
}

/** Liest die Einstellungen eines Users aus der DB (bereits normalisiert). */
export function readUserSettings(raw: any): UserSettings {
  if (!raw) return normalizeSettings(null)
  if (typeof raw === 'object') return normalizeSettings(raw)
  try {
    return normalizeSettings(JSON.parse(raw))
  } catch {
    return normalizeSettings(null)
  }
}
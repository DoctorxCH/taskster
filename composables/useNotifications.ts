/**
 * Globaler Benachrichtigungsdienst.
 *
 * Lädt die Benachrichtigungen regelmässig nach und meldet neue Einträge über
 * die in den Einstellungen aktivierten Kanäle:
 *   - In-App  : die Liste selbst (Dashboard)
 *   - Browser : Notification API des Betriebssystems
 *   - Ton     : kurzer Signalton
 *
 * Die persönlichen Einstellungen (users.settings.notifications) steuern,
 * welche Ereignisse überhaupt gemeldet werden.
 */

/** Zuordnung von API-Typ zu Einstellungs-Schlüssel. */
const TYPE_TO_SETTING: Record<string, string> = {
  calendar_invite: 'calendar_invite',
  calendar_change: 'calendar_change',
  calendar_cancel: 'calendar_cancel',
  calendar_reminder: 'calendar_reminder',
  task_assigned: 'task_assigned',
  due_soon: 'task_due',
  new_comment: 'task_comment',
  mention: 'mention',
  budget_exceeded: 'budget_warning'
}

export const useNotifications = () => {
  const { user, authHeaders } = useAuth()

  const items = useState<any[]>('notif_items', () => [])
  const unreadCount = useState<number>('notif_unread', () => 0)
  const loading = useState<boolean>('notif_loading', () => false)

  /** Bereits gemeldete IDs – verhindert doppelte Browser-Hinweise. */
  const announced = useState<Set<string>>('notif_announced', () => new Set())

  /** Sperre gegen parallele Refresh-Zyklen (App-Start + Navbar). */
  let inFlight = false

  const settings = computed(() => {
    const s = user.value?.settings?.notifications || {}
    return {
      in_app: s.in_app !== false,
      browser: s.browser === true,
      email: s.email === true,
      sound: s.sound === true,
      events: s.events || {}
    }
  })

  function shouldAnnounce(type: string): boolean {
    const key = TYPE_TO_SETTING[type]
    if (!key) return true // unbekannte Typen nicht unterdrücken
    return settings.value.events[key] !== false
  }

  // ---------------------------------------------------------------------------
  // Ton
  // ---------------------------------------------------------------------------
  let audioCtx: AudioContext | null = null

  function playChime() {
    if (!import.meta.client || !settings.value.sound) return
    try {
      const Ctx = window.AudioContext || (window as any).webkitAudioContext
      if (!Ctx) return
      audioCtx = audioCtx || new Ctx()
      if (audioCtx.state === 'suspended') audioCtx.resume()

      // Zwei kurze Töne – dezent, kein Alarm
      const now = audioCtx.currentTime
      for (const [i, freq] of [880, 1174.66].entries()) {
        const osc = audioCtx.createOscillator()
        const gain = audioCtx.createGain()
        osc.type = 'sine'
        osc.frequency.value = freq
        const start = now + i * 0.12
        gain.gain.setValueAtTime(0.0001, start)
        gain.gain.exponentialRampToValueAtTime(0.08, start + 0.02)
        gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.11)
        osc.connect(gain)
        gain.connect(audioCtx.destination)
        osc.start(start)
        osc.stop(start + 0.12)
      }
    } catch {
      // Ton ist optional – Fehler dürfen nichts blockieren
    }
  }

  // ---------------------------------------------------------------------------
  // Browser-Benachrichtigung
  // ---------------------------------------------------------------------------
  function canUseBrowser(): boolean {
    return import.meta.client
      && settings.value.browser
      && typeof Notification !== 'undefined'
      && Notification.permission === 'granted'
  }

  function announceBrowser(n: any) {
    if (!canUseBrowser()) return
    try {
      const body = String(n.message || '').replace(/^[^\wÄÖÜäöü]*/, '').slice(0, 180)
      const notif = new Notification(String(n.title || 'Taskster'), {
        body,
        icon: '/favicon.ico',
        tag: n.id,
        silent: true // Ton spielen wir selbst, damit die Einstellung greift
      })
      notif.onclick = () => {
        window.focus()
        if (n.project_id && n.reference_type === 'task') {
          window.location.href = `/projects/${n.project_id}?task=${n.reference_id}`
        } else if (n.reference_type === 'event') {
          window.location.href = '/calendar'
        } else {
          window.location.href = '/dashboard'
        }
        notif.close()
      }
    } catch {
      // ignore
    }
  }

  // ---------------------------------------------------------------------------
  // Laden
  // ---------------------------------------------------------------------------
  async function load(silent = false) {
    if (!user.value) return
    if (!silent) loading.value = true
    try {
      const res = await $fetch<{ notifications: any[]; unreadCount: number }>('/api/notifications', {
        headers: authHeaders()
      })
      items.value = res.notifications || []
      unreadCount.value = res.unreadCount || 0
    } catch {
      if (!silent) {
        items.value = []
        unreadCount.value = 0
      }
    } finally {
      loading.value = false
    }
  }

  /**
   * Meldet neue, noch nicht angekündigte Einträge und merkt sie vor.
   * Beim ersten Laden werden alle vorhandenen Einträge nur registriert,
   * damit ein Seitenaufruf nicht sofort eine Flut von Hinweisen auslöst.
   */
  function processNew(isFirstRun: boolean) {
    const fresh: any[] = []
    for (const n of items.value) {
      if (announced.value.has(n.id)) continue
      announced.value.add(n.id)
      if (n.is_read) continue
      if (!shouldAnnounce(n.type)) continue
      fresh.push(n)
    }
    if (isFirstRun || !fresh.length) return

    // Nur die neuesten melden, um Überlagerungen zu vermeiden
    for (const n of fresh.slice(0, 3)) {
      if (settings.value.in_app) announceBrowser(n)
    }
    if (settings.value.in_app && settings.value.sound) playChime()
  }

  async function refresh(silent = true) {
    // Parallele Aufrufe (z. B. App-Start + Navbar) dürfen nicht doppelt melden
    if (inFlight) return
    inFlight = true
    try {
      const isFirstRun = announced.value.size === 0
      await load(silent)
      processNew(isFirstRun)
    } finally {
      inFlight = false
    }
  }

  // ---------------------------------------------------------------------------
  // Aktionen
  // ---------------------------------------------------------------------------
  async function markRead(n: any) {
    if (n.is_read) return
    n.is_read = true
    if (unreadCount.value > 0) unreadCount.value--
    try {
      await $fetch(`/api/notifications/${n.id}/read`, { method: 'POST', headers: authHeaders() })
    } catch {
      // ignore
    }
  }

  async function markAllRead() {
    for (const n of items.value) n.is_read = true
    unreadCount.value = 0
    try {
      await $fetch('/api/notifications/read-all', { method: 'POST', headers: authHeaders() })
    } catch {
      // ignore
    }
  }

  const recent = computed(() => items.value.slice(0, 8))

  return {
    items,
    recent,
    unreadCount,
    loading,
    load,
    refresh,
    markRead,
    markAllRead,
    playChime,
    announceBrowser
  }
}
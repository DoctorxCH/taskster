import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/notifications
 * Liefert gespeicherte Benachrichtigungen (Kommentare, Termineinladungen,
 * Antworten) sowie in Echtzeit abgeleitete Hinweise:
 *  - due_soon:        Aufgaben, die in <= 3 Tagen fällig sind
 *  - budget_exceeded: Projekte, deren Budget erreicht/überschritten ist
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)

  const notifications: any[] = []

  // 1. Gespeicherte Benachrichtigungen
  const dbNotifs = db.prepare(`
    SELECT * FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 50
  `).all(user.id) as any[]

  for (const n of dbNotifs) {
    notifications.push({
      id: n.id,
      type: n.type,
      title: n.title,
      message: n.message,
      reference_type: n.reference_type,
      reference_id: n.reference_id,
      project_id: n.project_id,
      is_read: Boolean(n.is_read),
      created_at: n.created_at,
      is_realtime: false
    })
  }

  // 2. Echtzeit: bald fällige Aufgaben (nächste 3 Tage)
  const dueTasks = db.prepare(`
    SELECT t.id, t.title, t.due_date, p.id AS project_id, p.title AS project_title,
           julianday(date(t.due_date)) - julianday(date('now')) AS days_left
    FROM tasks t
    JOIN lists l ON l.id = t.list_id
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE (t.assigned_to = ? OR pf.owner_id = ? OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?))
      AND t.status != 'done'
      AND t.due_date IS NOT NULL
      AND t.due_date != ''
      AND date(t.due_date) >= date('now')
      AND date(t.due_date) <= date('now', '+3 day')
    ORDER BY t.due_date ASC
    LIMIT 10
  `).all(user.id, user.id, user.id) as any[]

  for (const t of dueTasks) {
    const days = Math.max(0, Math.round(Number(t.days_left) || 0))
    const dayText = days === 0 ? 'Heute fällig' : (days === 1 ? 'Morgen fällig' : `Fällig in ${days} Tagen`)
    const d = String(t.due_date).slice(0, 10).split('-')
    notifications.push({
      id: `due_${t.id}`,
      type: 'due_soon',
      title: `⏰ ${dayText}: ${t.title}`,
      message: `Aufgabe im Projekt "${t.project_title}" ist fällig am ${d[2]}.${d[1]}.${d[0]}.`,
      reference_type: 'task',
      reference_id: t.id,
      project_id: t.project_id,
      is_read: false,
      created_at: `${String(t.due_date).slice(0, 10)} 08:00:00`,
      is_realtime: true
    })
  }

  // 3. Echtzeit: Budget erreicht
  const budgetProjects = db.prepare(`
    SELECT p.id, p.title, p.currency, p.budget_hours, p.budget_amount,
           COALESCE((SELECT SUM(duration_minutes) FROM time_entries WHERE project_id = p.id), 0) AS tracked_minutes,
           COALESCE((SELECT SUM(duration_minutes * hourly_rate / 60) FROM time_entries WHERE project_id = p.id), 0) AS tracked_cost
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.owner_id = ?
      AND (COALESCE(p.budget_hours, 0) > 0 OR COALESCE(p.budget_amount, 0) > 0)
  `).all(user.id) as any[]

  for (const p of budgetProjects) {
    const bHours = Number(p.budget_hours || 0)
    const bAmount = Number(p.budget_amount || 0)
    const spentHours = Math.round((Number(p.tracked_minutes || 0) / 60) * 10) / 10
    const spentCost = Math.round(Number(p.tracked_cost || 0) * 100) / 100

    let reason = ''
    if (bHours > 0 && spentHours >= bHours) {
      reason = `Stundenbudget erreicht: ${spentHours} / ${bHours} h`
    } else if (bAmount > 0 && spentCost >= bAmount) {
      reason = `Kostenbudget erreicht: ${spentCost} / ${bAmount} ${p.currency || 'CHF'}`
    }
    if (!reason) continue

    notifications.push({
      id: `budget_${p.id}`,
      type: 'budget_exceeded',
      title: `💰 Budgetwarnung: ${p.title}`,
      message: `${reason} im Projekt "${p.title}".`,
      reference_type: 'project',
      reference_id: p.id,
      project_id: p.id,
      is_read: false,
      created_at: new Date().toISOString().replace('T', ' ').slice(0, 19),
      is_realtime: true
    })
  }

  // Neueste zuerst
  notifications.sort((a, b) => String(b.created_at).localeCompare(String(a.created_at)))

  return {
    notifications,
    unreadCount: notifications.filter((n) => !n.is_read).length
  }
})
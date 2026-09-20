import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/calendar?year=2026&month=9
 *
 * Liefert die Tage eines Monats mit Termin-Markierungen für die Sidebar-Miniatur.
 * Quelle: Aufgaben-Fälligkeiten (due_date) in zugänglichen Projekten.
 *
 * Antwort:
 *   { year, month, days: { "2026-09-20": { count, overdue, items: [...] } } }
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)

  const now = new Date()
  const year = Number(query.year) || now.getFullYear()
  const month = Number(query.month) || now.getMonth() + 1 // 1-12

  if (month < 1 || month > 12) {
    throw createError({ statusCode: 400, statusMessage: 'Ungültiger Monat' })
  }

  // Monatsgrenzen als ISO-Strings (YYYY-MM-DD)
  const pad = (n: number) => String(n).padStart(2, '0')
  const firstDay = `${year}-${pad(month)}-01`
  const lastDay = `${year}-${pad(month)}-${pad(new Date(year, month, 0).getDate())}`

  const companyId = user.company_id || '__none__'

  // Aufgaben mit Fälligkeit im Monat, in zugänglichen Projekten
  const rows = db.prepare(`
    SELECT t.id, t.title, t.due_date, t.status, t.priority,
           p.id AS project_id, p.title AS project_title
    FROM tasks t
    JOIN lists l ON l.id = t.list_id
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE t.due_date IS NOT NULL
      AND t.due_date >= ? AND t.due_date <= ?
      AND (
        pf.owner_id = ?
        OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
        OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
      )
    ORDER BY t.due_date ASC
  `).all(firstDay, lastDay, user.id, user.id, companyId) as any[]

  // Zero-Trust: 'custom'-Abschnitte ausschliessen
  const visibleListIds = new Set<string>(
    (db.prepare('SELECT list_id FROM list_access WHERE user_id = ? AND is_visible = 1')
      .all(user.id) as any[]).map((r) => r.list_id)
  )

  const today = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}`
  const days: Record<string, any> = {}

  for (const r of rows) {
    const dateKey = String(r.due_date).slice(0, 10)
    if (!days[dateKey]) {
      days[dateKey] = { count: 0, overdue: 0, items: [] }
    }
    const isOverdue = dateKey < today && r.status !== 'done'
    days[dateKey].count++
    if (isOverdue) days[dateKey].overdue++
    if (days[dateKey].items.length < 5) {
      days[dateKey].items.push({
        id: r.id,
        title: r.title,
        status: r.status,
        priority: r.priority,
        project_id: r.project_id,
        project_title: r.project_title,
        overdue: isOverdue
      })
    }
  }

  return {
    year,
    month,
    today,
    total: rows.length,
    days
  }
})

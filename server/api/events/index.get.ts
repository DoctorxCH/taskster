import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/events?from=YYYY-MM-DD&to=YYYY-MM-DD&project_id=&category_id=
 *
 * Liefert alle Termine im Zeitraum, die der Nutzer sehen darf.
 * Zusätzlich: Aufgaben mit Fälligkeit (als schreibgeschützte Termine).
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const q = getQuery(event)

  const from = String(q.from || '').slice(0, 10)
  const to = String(q.to || '').slice(0, 10)
  const projectId = q.project_id ? String(q.project_id) : null
  const categoryId = q.category_id ? String(q.category_id) : null

  if (!from || !to) {
    throw createError({ statusCode: 400, statusMessage: 'from und to sind erforderlich' })
  }

  const companyId = user.company_id || '__none__'

  // --- Termine -----------------------------------------------------------
  const conditions: string[] = [
    `e.start_at <= ? AND e.end_at >= ?`,
    `(
      e.owner_id = ?
      OR e.visibility = 'company' AND e.company_id = ?
      OR EXISTS (SELECT 1 FROM event_attendees a WHERE a.event_id = e.id AND (a.user_id = ? OR LOWER(a.email) = LOWER(?)))
    )`
  ]
  const params: any[] = [`${to} 23:59:59`, `${from} 00:00:00`, user.id, companyId, user.id, user.email]

  if (projectId) {
    conditions.push('e.project_id = ?')
    params.push(projectId)
  }
  if (categoryId) {
    conditions.push('e.category_id = ?')
    params.push(categoryId)
  }

  const rows = db.prepare(`
    SELECT e.*,
           c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
           u.name AS owner_name, u.email AS owner_email,
           p.title AS project_title,
           (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
    FROM calendar_events e
    LEFT JOIN event_categories c ON c.id = e.category_id
    LEFT JOIN users u ON u.id = e.owner_id
    LEFT JOIN projects p ON p.id = e.project_id
    WHERE ${conditions.join(' AND ')}
    ORDER BY e.start_at ASC
  `).all(...params) as any[]

  const events = rows.map((e) => {
    const attendees = db.prepare(`
      SELECT id, user_id, email, name, role, status, is_organizer
      FROM event_attendees WHERE event_id = ? ORDER BY is_organizer DESC, name ASC
    `).all(e.id) as any[]

    const myAttendee = attendees.find(
      (a) => a.user_id === user.id || String(a.email).toLowerCase() === String(user.email).toLowerCase()
    )

    return {
      id: e.id,
      type: 'event',
      title: e.title,
      description: e.description,
      location: e.location,
      start: e.start_at,
      end: e.end_at,
      allDay: Boolean(e.all_day),
      priority: e.priority,
      status: e.status,
      visibility: e.visibility,
      color: e.color || e.category_color || '#0891B2',
      category_id: e.category_id,
      category_name: e.category_name,
      category_icon: e.category_icon,
      project_id: e.project_id,
      project_title: e.project_title,
      owner_id: e.owner_id,
      owner_name: e.owner_name,
      is_organizer: e.owner_id === user.id,
      my_status: myAttendee?.status || null,
      attendee_count: e.attendee_count,
      attendees,
      editable: e.owner_id === user.id || Boolean(user.is_superadmin)
    }
  })

  // --- Aufgaben mit Fälligkeit (schreibgeschützt) ------------------------
  const taskRows = db.prepare(`
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
  `).all(from, to, user.id, user.id, companyId) as any[]

  const tasks = taskRows.map((t) => ({
    id: 'task_' + t.id,
    task_id: t.id,
    type: 'task',
    title: t.title,
    start: t.due_date,
    end: t.due_date,
    allDay: true,
    status: t.status,
    priority: t.priority,
    color: t.status === 'done' ? '#059669' : '#64748B',
    project_id: t.project_id,
    project_title: t.project_title,
    editable: false
  }))

  return { events, tasks, from, to }
})

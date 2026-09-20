import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/daily-todos
 * Führt den Rollover durch (unvollendete Todos früherer Tage wandern auf heute)
 * und liefert die heutigen Tages-Todos samt verfügbarer Projekte.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)

  // Rollover: unvollendete Todos vergangener Tage auf heute verschieben
  try {
    db.prepare(`
      UPDATE daily_todos
      SET rollover_count = rollover_count + MAX(CAST(julianday(date('now')) - julianday(date(target_date)) AS INTEGER), 1),
          original_date = COALESCE(original_date, target_date),
          target_date = date('now')
      WHERE user_id = ?
        AND is_completed = 0
        AND date(target_date) < date('now')
    `).run(user.id)
  } catch {
    // Rollover ist Komfort – Fehler dürfen das Laden nicht verhindern
  }

  const rawTodos = db.prepare(`
    SELECT dt.*, p.title AS project_title, pf.name AS folder_name, pf.icon AS folder_icon
    FROM daily_todos dt
    LEFT JOIN projects p ON p.id = dt.project_id
    LEFT JOIN project_folders pf ON pf.id = p.folder_id
    WHERE dt.user_id = ?
      AND (date(dt.target_date) = date('now')
           OR (dt.is_completed = 1 AND date(dt.completed_at) = date('now')))
    ORDER BY dt.is_completed ASC, dt.created_at DESC
  `).all(user.id) as any[]

  const todos = rawTodos.map((t) => ({
    ...t,
    is_completed: Boolean(t.is_completed),
    rollover_count: Number(t.rollover_count) || 0
  }))

  const availableProjects = db.prepare(`
    SELECT p.id, p.title, pf.name AS folder_name, pf.icon AS folder_icon
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.owner_id = ?
       OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)
       OR pf.id IN (SELECT folder_id FROM folder_members WHERE user_id = ?)
    ORDER BY p.title ASC
  `).all(user.id, user.id, user.id)

  return {
    todos,
    date: new Date().toISOString().slice(0, 10),
    availableProjects
  }
})
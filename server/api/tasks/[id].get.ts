import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) {
    throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })
  }

  // Permission check via list
  evaluateListAccess(user, task.list_id, event, 'read')

  // Parse JSON fields
  task.custom_data = JSON.parse(task.custom_data || '{}')
  task.tags = JSON.parse(task.tags || '[]')
  task.checklist = JSON.parse(task.checklist || '[]')

  // Assignee info
  let assignee = null
  if (task.assigned_to) {
    assignee = db.prepare('SELECT id, name, email FROM users WHERE id = ?').get(task.assigned_to) as any
  }

  // Subtasks
  const subtasks = db.prepare(
    'SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY sort_order ASC, created_at ASC'
  ).all(taskId) as any[]

  // Comments with author info
  const comments = db.prepare(`
    SELECT tc.*, u.name as author_name, u.avatar as author_avatar
    FROM task_comments tc
    JOIN users u ON u.id = tc.author_id
    WHERE tc.task_id = ?
    ORDER BY tc.created_at ASC
  `).all(taskId) as any[]

  // Documents for this task
  const documents = db.prepare(`
    SELECT pd.*, u.name as uploaded_by_name
    FROM project_documents pd
    JOIN users u ON u.id = pd.id -- Note: project_documents doesn't have uploaded_by, using id as fallback
    WHERE pd.task_id = ?
    ORDER BY pd.created_at DESC
  `).all(taskId) as any[]

  // Time entries for this task
  const timeEntries = db.prepare(`
    SELECT te.*, u.name as user_name, u.email as user_email
    FROM time_entries te
    JOIN users u ON u.id = te.user_id
    WHERE te.task_id = ?
    ORDER BY te.entry_date DESC, te.created_at DESC
  `).all(taskId) as any[]

  const trackedMinutes = timeEntries.reduce((sum, e) => sum + (Number(e.duration_minutes) || 0), 0)

  return {
    task: {
      ...task,
      budget_hours: Number(task.budget_hours) || 0,
      budget_amount: Number(task.budget_amount) || 0,
      tracked_minutes: trackedMinutes,
      tracked_hours: Number((trackedMinutes / 60).toFixed(2)),
      assignee,
    },
    subtasks,
    comments,
    documents,
    timeEntries: timeEntries.map(e => ({
      ...e,
      is_manual: Boolean(e.is_manual),
      duration_hours: Number(((Number(e.duration_minutes) || 0) / 60).toFixed(2)),
      calculated_amount: Number((((Number(e.duration_minutes) || 0) / 60) * (Number(e.hourly_rate) || 0)).toFixed(2))
    }))
  }
})

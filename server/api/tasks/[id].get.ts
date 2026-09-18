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
    SELECT tc.*, u.name as author_name
    FROM task_comments tc
    JOIN users u ON u.id = tc.author_id
    WHERE tc.task_id = ?
    ORDER BY tc.created_at ASC
  `).all(taskId) as any[]

  return {
    task: {
      ...task,
      assignee,
    },
    subtasks,
    comments,
  }
})

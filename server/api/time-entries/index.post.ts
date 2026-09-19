import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateListAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const projectId = body.project_id ? String(body.project_id) : null
  const taskId = body.task_id ? String(body.task_id) : null

  if (!projectId && !taskId) {
    throw createError({ statusCode: 400, statusMessage: 'project_id oder task_id erforderlich' })
  }

  let finalProjectId = projectId

  if (taskId) {
    const task = db.prepare('SELECT t.*, l.project_id FROM tasks t JOIN lists l ON l.id = t.list_id WHERE t.id = ?').get(taskId) as any
    if (!task) {
      throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })
    }
    finalProjectId = task.project_id
    // Evaluate permission to mutate task/list
    evaluateListAccess(user, task.list_id, event, 'write')
  } else if (finalProjectId) {
    evaluateProjectAccess(user, finalProjectId, event, 'write')
  }

  // Get project info for currency
  const project = db.prepare('SELECT currency FROM projects WHERE id = ?').get(finalProjectId) as any
  const projectCurrency = project?.currency || 'CHF'

  // Get user info for default hourly rate
  const userRecord = db.prepare('SELECT hourly_rate, currency FROM users WHERE id = ?').get(user.id) as any
  const defaultHourlyRate = Number(userRecord?.hourly_rate) || 0

  const durationMinutes = Math.max(1, parseInt(body.duration_minutes) || Math.round((parseFloat(body.duration_hours) || 0) * 60) || 0)
  if (!durationMinutes) {
    throw createError({ statusCode: 400, statusMessage: 'Gültige Dauer erforderlich (duration_minutes oder duration_hours)' })
  }

  const hourlyRate = body.hourly_rate !== undefined ? Math.max(0, parseFloat(body.hourly_rate) || 0) : defaultHourlyRate
  const currency = body.currency || projectCurrency
  const description = body.description ? String(body.description).trim() : ''
  const entryDate = body.entry_date ? String(body.entry_date).substring(0, 10) : new Date().toISOString().substring(0, 10)
  const isManual = body.is_manual !== undefined ? (body.is_manual ? 1 : 0) : 1
  const startedAt = body.started_at || null
  const endedAt = body.ended_at || null

  const id = 'time_' + randomUUID()

  db.prepare(`
    INSERT INTO time_entries (
      id, project_id, task_id, user_id, duration_minutes,
      hourly_rate, currency, description, entry_date,
      is_manual, started_at, ended_at, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'))
  `).run(
    id, finalProjectId, taskId, user.id, durationMinutes,
    hourlyRate, currency, description, entryDate,
    isManual, startedAt, endedAt
  )

  const created = db.prepare(`
    SELECT te.*, u.name as user_name, u.email as user_email, t.title as task_title
    FROM time_entries te
    JOIN users u ON u.id = te.user_id
    LEFT JOIN tasks t ON t.id = te.task_id
    WHERE te.id = ?
  `).get(id) as any

  return {
    entry: {
      ...created,
      is_manual: Boolean(created.is_manual),
      duration_hours: Number(((Number(created.duration_minutes) || 0) / 60).toFixed(2)),
      calculated_amount: Number((((Number(created.duration_minutes) || 0) / 60) * (Number(created.hourly_rate) || 0)).toFixed(2))
    }
  }
})

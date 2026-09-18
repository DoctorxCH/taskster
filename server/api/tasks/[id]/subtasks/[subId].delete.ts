import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const subId = getRouterParam(event, 'subId')

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  evaluateListAccess(user, task.list_id, event, 'write')

  const sub = db.prepare('SELECT * FROM task_subtasks WHERE id = ? AND task_id = ?').get(subId, taskId) as any
  if (!sub) throw createError({ statusCode: 404, statusMessage: 'Unteraufgabe nicht gefunden' })

  db.prepare('DELETE FROM task_subtasks WHERE id = ?').run(subId)
  return { success: true }
})

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { title } = body

  if (!title?.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Unteraufgaben-Titel erforderlich' })
  }

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  evaluateListAccess(user, task.list_id, event, 'write')

  const count = (db.prepare('SELECT COUNT(*) as c FROM task_subtasks WHERE task_id = ?').get(taskId) as any).c
  const subId = 'sub_' + randomUUID().substring(0, 8)

  db.prepare(
    'INSERT INTO task_subtasks (id, task_id, title, is_done, sort_order) VALUES (?, ?, ?, 0, ?)'
  ).run(subId, taskId, title.trim(), count + 1)

  return {
    subtask: {
      id: subId,
      task_id: taskId,
      title: title.trim(),
      is_done: 0,
      sort_order: count + 1
    }
  }
})

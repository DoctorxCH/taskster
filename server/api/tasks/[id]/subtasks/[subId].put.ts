import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const subId = getRouterParam(event, 'subId')
  const body = await readBody(event)

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  evaluateListAccess(user, task.list_id, event, 'write')

  const sub = db.prepare('SELECT * FROM task_subtasks WHERE id = ? AND task_id = ?').get(subId, taskId) as any
  if (!sub) throw createError({ statusCode: 404, statusMessage: 'Unteraufgabe nicht gefunden' })

  const { is_done, title } = body
  const updatedDone = is_done !== undefined ? (is_done ? 1 : 0) : sub.is_done
  const updatedTitle = title !== undefined ? title.trim() : sub.title

  db.prepare('UPDATE task_subtasks SET is_done = ?, title = ? WHERE id = ?')
    .run(updatedDone, updatedTitle, subId)

  return { subtask: { id: subId, task_id: taskId, title: updatedTitle, is_done: updatedDone } }
})

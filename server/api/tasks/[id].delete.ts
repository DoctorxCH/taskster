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

  evaluateListAccess(user, task.list_id, event, 'write')

  db.prepare('DELETE FROM tasks WHERE id = ?').run(taskId)

  return { success: true }
})

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const docId = getRouterParam(event, 'docId')

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  evaluateListAccess(user, task.list_id, event, 'write')

  const doc = db.prepare('SELECT * FROM project_documents WHERE id = ? AND task_id = ?').get(docId, taskId) as any
  if (!doc) throw createError({ statusCode: 404, statusMessage: 'Dokument nicht gefunden' })

  db.prepare('DELETE FROM project_documents WHERE id = ?').run(docId)
  return { success: true }
})
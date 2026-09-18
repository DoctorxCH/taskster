import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const body = await readBody(event)
  
  const { file_name, mime_type, file_size, storage_path } = body

  if (!file_name || !mime_type || !file_size || !storage_path) {
    throw createError({ statusCode: 400, statusMessage: 'Alle Felder erforderlich' })
  }

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  evaluateListAccess(user, task.list_id, event, 'write')

  const docId = 'doc_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO project_documents (id, project_id, task_id, file_name, mime_type, file_size, storage_path, version)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
  `).run(docId, task.project_id || task.list_id, taskId, file_name, mime_type, file_size, storage_path)

  return {
    document: {
      id: docId,
      project_id: task.project_id || task.list_id,
      task_id: taskId,
      file_name,
      mime_type,
      file_size,
      storage_path,
      version: 1,
      created_at: new Date().toISOString(),
      uploaded_by_name: user.name
    }
  }
})
import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { content } = body

  if (!content?.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Kommentar darf nicht leer sein' })
  }

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })

  // Need at least read access (members can comment)
  evaluateListAccess(user, task.list_id, event, 'read')

  const commentId = 'cmt_' + randomUUID().substring(0, 8)
  db.prepare(
    'INSERT INTO task_comments (id, task_id, author_id, content) VALUES (?, ?, ?, ?)'
  ).run(commentId, taskId, user.id, content.trim())

  return {
    comment: {
      id: commentId,
      task_id: taskId,
      author_id: user.id,
      author_name: user.name,
      content: content.trim(),
      created_at: new Date().toISOString()
    }
  }
})

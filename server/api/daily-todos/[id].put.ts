import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * PUT /api/daily-todos/:id
 * Ändert Status, Titel oder Projektzuordnung eines Tages-Todos.
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const existing = db.prepare('SELECT * FROM daily_todos WHERE id = ? AND user_id = ?').get(id, user.id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Tages-Todo nicht gefunden' })
  }

  const isCompleted = body?.is_completed !== undefined
    ? (body.is_completed ? 1 : 0)
    : existing.is_completed
  const title = body?.title !== undefined ? String(body.title).trim() : existing.title
  const projectId = body?.project_id !== undefined
    ? (body.project_id || null)
    : existing.project_id
  const completedAt = isCompleted ? new Date().toISOString().replace('T', ' ').slice(0, 19) : null

  db.prepare(`
    UPDATE daily_todos
    SET is_completed = ?, completed_at = ?, title = ?, project_id = ?
    WHERE id = ? AND user_id = ?
  `).run(isCompleted, completedAt, title, projectId, id, user.id)

  return { success: true }
})
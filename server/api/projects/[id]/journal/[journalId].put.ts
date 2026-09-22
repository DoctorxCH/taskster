import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string
  const journalId = getRouterParam(event, 'journalId') as string
  const body = await readBody(event)

  evaluateProjectAccess(user, projectId, event, 'write')

  const existing = db.prepare(`SELECT * FROM project_journals WHERE id = ? AND project_id = ?`).get(journalId, projectId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Journaleintrag nicht gefunden' })
  }

  const title = body.title !== undefined ? String(body.title).trim() : existing.title
  const content = body.content !== undefined ? String(body.content).trim() : existing.content
  const category = body.category !== undefined ? String(body.category).trim() : existing.category
  const visibility = body.visibility !== undefined ? body.visibility : existing.visibility
  const allowedGroupId = body.allowed_group_id !== undefined ? body.allowed_group_id : existing.allowed_group_id
  const taskId = body.task_id !== undefined ? (body.task_id ? String(body.task_id).trim() : null) : existing.task_id

  let metaJson = existing.metadata
  if (body.metadata !== undefined) {
    metaJson = typeof body.metadata === 'object' ? JSON.stringify(body.metadata) : body.metadata
  }

  db.prepare(`
    UPDATE project_journals
    SET title = ?, content = ?, category = ?, visibility = ?, allowed_group_id = ?, task_id = ?, metadata = ?, updated_at = datetime('now')
    WHERE id = ? AND project_id = ?
  `).run(title, content, category, visibility, allowedGroupId, taskId, metaJson, journalId, projectId)

  return { success: true }
})

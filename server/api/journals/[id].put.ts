import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const journalId = getRouterParam(event, 'id') as string
  const body = await readBody(event)

  const existing = db.prepare(`SELECT * FROM project_journals WHERE id = ?`).get(journalId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Journaleintrag nicht gefunden' })
  }

  if (existing.project_id) {
    evaluateProjectAccess(user, existing.project_id, event, 'write')
  } else if (existing.folder_id) {
    evaluateFolderAccess(user, existing.folder_id, event, 'write')
  } else {
    const isAuthor = (existing.author_id && existing.author_id === user.id) || (existing.user_id && existing.user_id === user.id)
    if (!isAuthor && !user.is_superadmin) {
      throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten dieses Eintrags' })
    }
  }

  const title = body.title !== undefined ? String(body.title).trim() : existing.title
  const content = body.content !== undefined ? String(body.content).trim() : existing.content
  const category = body.category !== undefined ? String(body.category).trim() : existing.category
  const visibility = body.visibility !== undefined ? body.visibility : existing.visibility
  const allowedGroupId = body.allowed_group_id !== undefined ? body.allowed_group_id : existing.allowed_group_id
  const taskId = body.task_id !== undefined ? (body.task_id ? String(body.task_id).trim() : null) : existing.task_id
  const projectId = body.project_id !== undefined ? (body.project_id ? String(body.project_id).trim() : null) : existing.project_id

  let metaJson = existing.metadata
  if (body.metadata !== undefined) {
    metaJson = typeof body.metadata === 'object' ? JSON.stringify(body.metadata) : body.metadata
  }

  db.prepare(`
    UPDATE project_journals
    SET title = ?, content = ?, category = ?, visibility = ?, allowed_group_id = ?, task_id = ?, project_id = ?, metadata = ?, updated_at = datetime('now')
    WHERE id = ?
  `).run(title, content, category, visibility, allowedGroupId, taskId, projectId, metaJson, journalId)

  return { success: true }
})

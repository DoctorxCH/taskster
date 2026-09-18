import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)
  const projectId = query.project_id as string

  if (!projectId) {
    throw createError({ statusCode: 400, statusMessage: 'Projekt-ID erforderlich' })
  }

  evaluateProjectAccess(user, projectId, event, 'read')

  const entries = db.prepare(`
    SELECT j.*, u.name as author_name, t.title as task_title
    FROM project_journals j
    JOIN users u ON u.id = j.author_id
    LEFT JOIN tasks t ON t.id = j.task_id
    WHERE j.project_id = ?
    ORDER BY j.created_at DESC
  `).all(projectId).map((e: any) => ({
    ...e,
    metadata: e.metadata ? JSON.parse(e.metadata) : {}
  }))

  return { entries }
})


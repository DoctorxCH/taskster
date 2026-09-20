import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)
  const projectId = query.project_id as string

  let entries: any[] = []

  if (projectId) {
    evaluateProjectAccess(user, projectId, event, 'read')
    entries = db.prepare(`
      SELECT j.*, u.name as author_name, t.title as task_title
      FROM project_journals j
      JOIN users u ON u.id = j.author_id
      LEFT JOIN tasks t ON t.id = j.task_id
      WHERE j.project_id = ?
      ORDER BY j.created_at DESC
    `).all(projectId)
  } else {
    entries = db.prepare(`
      SELECT j.*, u.name as author_name, t.title as task_title, p.title as project_title
      FROM project_journals j
      JOIN users u ON u.id = j.author_id
      LEFT JOIN tasks t ON t.id = j.task_id
      LEFT JOIN projects p ON p.id = j.project_id
      WHERE j.author_id = ?
      ORDER BY j.created_at DESC
      LIMIT 100
    `).all(user.id)
  }

  return {
    entries: entries.map((e: any) => ({
      ...e,
      metadata: e.metadata ? JSON.parse(e.metadata) : {}
    }))
  }
})


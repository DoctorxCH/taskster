import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * POST /api/daily-todos
 * Legt ein neues Tages-Todo für heute an.
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const title = String(body?.title ?? '').trim()
  const projectId = body?.project_id ? String(body.project_id) : null

  if (!title) {
    throw createError({ statusCode: 400, statusMessage: 'Titel darf nicht leer sein' })
  }

  // Zero-Trust: nur Projekte, auf die der Nutzer Zugriff hat
  if (projectId) {
    const access = db.prepare(`
      SELECT p.id
      FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE p.id = ?
        AND (pf.owner_id = ?
             OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)
             OR pf.id IN (SELECT folder_id FROM folder_members WHERE user_id = ?))
    `).get(projectId, user.id, user.id, user.id)

    if (!access) {
      throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
    }
  }

  const id = 'dt_' + Math.random().toString(16).slice(2, 10)
  const today = new Date().toISOString().slice(0, 10)

  db.prepare(`
    INSERT INTO daily_todos (id, user_id, project_id, title, target_date, is_completed, rollover_count, created_at)
    VALUES (?, ?, ?, ?, ?, 0, 0, datetime('now'))
  `).run(id, user.id, projectId, title, today)

  let projectTitle: string | null = null
  if (projectId) {
    const p = db.prepare('SELECT title FROM projects WHERE id = ?').get(projectId) as any
    projectTitle = p?.title ?? null
  }

  return {
    success: true,
    todo: {
      id,
      user_id: user.id,
      project_id: projectId,
      project_title: projectTitle,
      title,
      target_date: today,
      is_completed: false,
      rollover_count: 0,
      created_at: new Date().toISOString().replace('T', ' ').slice(0, 19)
    }
  }
})
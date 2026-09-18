import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { folder_id, title } = body

  if (!folder_id || !title || !title.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Ordner-ID und Projekttitel sind erforderlich' })
  }

  const folder = db.prepare('SELECT * FROM project_folders WHERE id = ?').get(folder_id) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Check write access to folder
  const canCreate = user.is_superadmin || folder.owner_id === user.id || (user.company_id && user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canCreate) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zur Projekterstellung in diesem Ordner' })
  }

  // Free user limit check: "Free user darf max. in 3 projekten gleichzeitig mitarbeiten."
  if (!user.is_pro && !user.company_id && !user.is_superadmin) {
    const activeProjectsCount = (db.prepare(`
      SELECT COUNT(*) as count FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      LEFT JOIN project_members pm ON pm.project_id = p.id
      WHERE (pf.owner_id = ? OR pm.user_id = ?) AND p.status = 'active'
    `).get(user.id, user.id) as any).count

    if (activeProjectsCount >= 3) {
      throw createError({
        statusCode: 403,
        statusMessage: 'Free-Plan Limit erreicht: Im kostenlosen Plan darfst du maximal in 3 Projekten gleichzeitig mitarbeiten. Bitte auf Pro upgraden oder einer Company beitreten.'
      })
    }
  }

  const projectId = 'prj_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO projects (id, folder_id, title, status)
    VALUES (?, ?, ?, 'active')
  `).run(projectId, folder_id, title.trim())

  // Default initial list
  const listId = 'lst_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, 'Aufgabenliste 1', 'inherit', 1)
  `).run(listId, projectId)

  return {
    project: {
      id: projectId,
      folder_id,
      title: title.trim(),
      status: 'active'
    }
  }
})


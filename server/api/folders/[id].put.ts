import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const folder = db.prepare('SELECT * FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Zero-Trust: Only owner (or superadmin) can modify the folder
  if (!user.is_superadmin && folder.owner_id !== user.id) {
    throw createError({ statusCode: 403, statusMessage: 'Nur der Eigentümer kann diesen Projektordner bearbeiten' })
  }

  const name = body.name !== undefined ? String(body.name).trim() : folder.name
  const icon = body.icon !== undefined ? String(body.icon).trim() : (folder.icon || '📁')

  if (!name) {
    throw createError({ statusCode: 400, statusMessage: 'Name des Ordners darf nicht leer sein' })
  }

  db.prepare(`
    UPDATE project_folders
    SET name = ?, icon = ?
    WHERE id = ?
  `).run(name, icon, folderId)

  const updatedFolder = db.prepare(`
    SELECT pf.*, u.name as owner_name, c.name as company_name
    FROM project_folders pf
    JOIN users u ON u.id = pf.owner_id
    LEFT JOIN companies c ON c.id = pf.company_id
    WHERE pf.id = ?
  `).get(folderId)

  return { success: true, folder: updatedFolder }
})

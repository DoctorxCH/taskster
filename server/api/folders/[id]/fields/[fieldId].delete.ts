import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const fieldId = getRouterParam(event, 'fieldId')

  const folder = db.prepare('SELECT owner_id, company_id FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  const canEdit = folder.owner_id === user.id || (user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten der Felddefinitionen' })
  }

  const existingField = db.prepare('SELECT field_key, entity_type FROM folder_field_definitions WHERE id = ? AND folder_id = ?').get(fieldId, folderId) as any
  if (!existingField) {
    throw createError({ statusCode: 404, statusMessage: 'Feld nicht gefunden' })
  }

  // Prevent deletion if the field has populated values anywhere
  if (existingField.entity_type === 'project') {
    const checkProjects = db.prepare(`
      SELECT COUNT(*) as c FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE pf.id = ? AND json_extract(p.custom_data, '$.' || ?) IS NOT NULL AND json_extract(p.custom_data, '$.' || ?) != ''
    `).get(folderId, existingField.field_key, existingField.field_key) as any
    if (checkProjects && checkProjects.c > 0) {
      throw createError({ statusCode: 400, statusMessage: 'Feld kann nicht gelöscht werden, da es in aktiven Projekten verwendet wird.' })
    }
  } else {
    const checkTasks = db.prepare(`
      SELECT COUNT(*) as c FROM tasks t
      JOIN lists l ON l.id = t.list_id
      JOIN projects p ON p.id = l.project_id
      WHERE p.folder_id = ? AND json_extract(t.custom_data, '$.' || ?) IS NOT NULL AND json_extract(t.custom_data, '$.' || ?) != ''
    `).get(folderId, existingField.field_key, existingField.field_key) as any
    if (checkTasks && checkTasks.c > 0) {
      throw createError({ statusCode: 400, statusMessage: 'Feld kann nicht gelöscht werden, da es in aktiven Aufgaben verwendet wird.' })
    }
  }

  db.prepare('DELETE FROM folder_field_definitions WHERE id = ? AND folder_id = ?').run(fieldId, folderId)

  return { success: true }
})

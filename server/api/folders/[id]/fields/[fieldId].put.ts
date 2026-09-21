import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const fieldId = getRouterParam(event, 'fieldId')
  const body = await readBody(event)
  const label = String(body.label || '').trim()

  if (!label) {
    throw createError({ statusCode: 400, statusMessage: 'Feld-Bezeichnung ist erforderlich' })
  }

  const folder = db.prepare('SELECT owner_id, company_id FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  // Only owner or company admin can add/edit field definitions
  const canEdit = folder.owner_id === user.id || (user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten der Felddefinitionen' })
  }

  // Check if field exists in this folder
  const existing = db.prepare('SELECT id, field_type, entity_type FROM folder_field_definitions WHERE folder_id = ? AND id = ?').get(folderId, fieldId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Feld nicht gefunden' })
  }

  // field_type and entity_type shouldn't be edited once created because of data integrity
  const options = Array.isArray(body.options) ? JSON.stringify(body.options) : '[]'
  const logicRules = body.logic_rules ? JSON.stringify(body.logic_rules) : '{}'

  db.prepare(`
    UPDATE folder_field_definitions
    SET label = ?, options = ?, logic_rules = ?
    WHERE id = ? AND folder_id = ?
  `).run(
    label,
    options,
    logicRules,
    fieldId,
    folderId
  )

  return { success: true }
})

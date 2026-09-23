import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const fieldId = getRouterParam(event, 'fieldId')
  const body = await readBody(event)

  const folder = db.prepare('SELECT owner_id, company_id FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  const canEdit = folder.owner_id === user.id || (user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten der Felddefinitionen' })
  }

  const existingField = db.prepare('SELECT * FROM folder_field_definitions WHERE id = ? AND folder_id = ?').get(fieldId, folderId) as any
  if (!existingField) {
    throw createError({ statusCode: 404, statusMessage: 'Feld nicht gefunden' })
  }

  const label = body.label !== undefined ? String(body.label).trim() : existingField.label
  const fieldType = body.field_type !== undefined ? String(body.field_type).trim() : (existingField.field_type || 'text')
  const entityType = body.entity_type !== undefined ? (body.entity_type === 'project' ? 'project' : 'task') : (existingField.entity_type || 'task')
  const isRequired = body.is_required !== undefined ? (body.is_required ? 1 : 0) : (existingField.is_required || 0)
  const options = Array.isArray(body.options) ? JSON.stringify(body.options) : (typeof body.options === 'string' ? body.options : existingField.options)
  const logicRules = body.logic_rules !== undefined ? (typeof body.logic_rules === 'string' ? body.logic_rules : JSON.stringify(body.logic_rules || {})) : existingField.logic_rules

  if (!label) {
    throw createError({ statusCode: 400, statusMessage: 'Feld-Beschriftung darf nicht leer sein' })
  }

  db.prepare(`
    UPDATE folder_field_definitions
    SET label = ?, field_type = ?, entity_type = ?, is_required = ?, options = ?, logic_rules = ?
    WHERE id = ? AND folder_id = ?
  `).run(label, fieldType, entityType, isRequired, options, logicRules, fieldId, folderId)

  return { success: true }
})

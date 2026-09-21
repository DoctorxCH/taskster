import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const label = String(body.label || '').trim()

  if (!label) {
    throw createError({ statusCode: 400, statusMessage: 'Feld-Bezeichnung ist erforderlich' })
  }

  const rawKey = String(body.field_key || label).trim().toLowerCase().replace(/[^a-z0-9_]/g, '_').replace(/^_+|_+$/g, '')
  const fieldKey = rawKey || 'custom_field'

  const folder = db.prepare('SELECT owner_id, company_id FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  // Only owner or company admin can add field definitions
  const canEdit = folder.owner_id === user.id || (user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten der Felddefinitionen' })
  }

  // Check if field_key already exists in this folder
  const existing = db.prepare('SELECT id, field_key, label FROM folder_field_definitions WHERE folder_id = ? AND field_key = ?').get(folderId, fieldKey) as any
  if (existing) {
    return { success: true, fieldId: existing.id, fieldKey: existing.field_key }
  }

  const fieldId = 'fld_def_' + randomUUID().substring(0, 8)
  const count = (db.prepare('SELECT COUNT(*) as c FROM folder_field_definitions WHERE folder_id = ?').get(folderId) as any).c
  const entityType = body.entity_type === 'project' ? 'project' : 'task'
  const options = Array.isArray(body.options) ? JSON.stringify(body.options) : '[]'
  const logicRules = body.logic_rules ? JSON.stringify(body.logic_rules) : '{}'

  db.prepare(`
    INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, entity_type, is_pro_only, options, logic_rules, is_required, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    fieldId,
    folderId,
    fieldKey,
    label,
    body.field_type || 'text',
    entityType,
    body.is_pro_only ? 1 : 0,
    options,
    logicRules,
    body.is_required ? 1 : 0,
    count + 1
  )

  return { success: true, fieldId, fieldKey }
})

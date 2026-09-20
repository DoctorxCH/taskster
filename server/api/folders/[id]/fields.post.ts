import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')
  const body = await readBody(event)
  const { field_key, label, field_type, is_pro_only, options, is_required } = body

  if (!field_key || !label) {
    throw createError({ statusCode: 400, statusMessage: 'Feld-Schlüssel und Bezeichnung sind erforderlich' })
  }

  const folder = db.prepare('SELECT owner_id, company_id FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Ordner nicht gefunden' })
  }

  // Only owner or company admin can add field definitions
  const canEdit = folder.owner_id === user.id || (user.company_id === folder.company_id && user.company_role === 'admin')
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten der Felddefinitionen' })
  }

  const fieldId = 'fld_def_' + randomUUID().substring(0, 8)
  const count = (db.prepare('SELECT COUNT(*) as c FROM folder_field_definitions WHERE folder_id = ?').get(folderId) as any).c

  db.prepare(`
    INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, is_pro_only, options, is_required, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    fieldId,
    folderId,
    field_key.trim().toLowerCase().replace(/[^a-z0-9_]/g, '_'),
    label.trim(),
    field_type || 'text',
    is_pro_only ? 1 : 0,
    JSON.stringify(options || []),
    is_required ? 1 : 0,
    count + 1
  )

  return { success: true, fieldId }
})

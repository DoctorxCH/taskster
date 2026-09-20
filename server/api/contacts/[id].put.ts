import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = event.context.params?.id
  const body = await readBody(event)

  if (!id) {
    throw createError({ statusCode: 400, statusMessage: 'Kontakt-ID erforderlich' })
  }

  const existing = db.prepare('SELECT * FROM contacts WHERE id = ?').get(id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Kontakt nicht gefunden' })
  }

  const canEdit = Boolean(
    user.is_superadmin ||
    existing.user_id === user.id ||
    (user.company_id && user.company_id === existing.company_id && user.company_role === 'admin')
  )
  if (!canEdit) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten dieses Kontakts' })
  }

  const lastName = 'last_name' in body ? (body.last_name || '').trim() : existing.last_name
  if (!lastName) {
    throw createError({ statusCode: 400, statusMessage: 'Nachname ist erforderlich' })
  }

  const firstName = 'first_name' in body ? (body.first_name || '').trim() : existing.first_name
  const companyName = 'company_name' in body ? (body.company_name || '').trim() : existing.company_name
  const roleFunction = 'role_function' in body ? (body.role_function || '').trim() : existing.role_function
  const phone = 'phone' in body ? (body.phone || '').trim() : existing.phone
  const mobile = 'mobile' in body ? (body.mobile || '').trim() : existing.mobile
  const email = 'email' in body ? (body.email || '').trim() : existing.email
  const projectId = 'project_id' in body ? (body.project_id ? String(body.project_id).trim() : null) : existing.project_id
  const categoryGroup = 'category_group' in body ? (body.category_group || '').trim() : existing.category_group
  const address = 'address' in body ? (body.address || '').trim() : existing.address
  const website = 'website' in body ? (body.website || '').trim() : existing.website
  const latitude = 'latitude' in body
    ? (body.latitude === null || body.latitude === '' ? null : Number(body.latitude))
    : existing.latitude
  const longitude = 'longitude' in body
    ? (body.longitude === null || body.longitude === '' ? null : Number(body.longitude))
    : existing.longitude
  const notes = 'notes' in body ? (body.notes || '').trim() : existing.notes
  const shareScope = 'share_scope' in body && ['company', 'private'].includes(body.share_scope) ? body.share_scope : existing.share_scope

  let tags = existing.tags
  if ('tags' in body) {
    tags = Array.isArray(body.tags) ? JSON.stringify(body.tags) : '[]'
  }

  if (projectId && projectId !== existing.project_id) {
    evaluateProjectAccess(user, projectId, event, 'write')
  }

  db.prepare(`
    UPDATE contacts
    SET first_name = ?, last_name = ?, company_name = ?, role_function = ?,
        phone = ?, mobile = ?, email = ?, project_id = ?, category_group = ?,
        address = ?, website = ?, latitude = ?, longitude = ?, tags = ?, notes = ?, share_scope = ?, updated_at = datetime('now')
    WHERE id = ?
  `).run(
    firstName || null,
    lastName,
    companyName || null,
    roleFunction || null,
    phone || null,
    mobile || null,
    email || null,
    projectId,
    categoryGroup || null,
    address || null,
    website || null,
    latitude,
    longitude,
    tags,
    notes || null,
    shareScope,
    id
  )

  const updated = db.prepare(`
    SELECT c.*,
           p.title AS project_title,
           pf.name AS folder_name,
           u.name AS creator_name
    FROM contacts c
    LEFT JOIN projects p ON p.id = c.project_id
    LEFT JOIN project_folders pf ON pf.id = p.folder_id
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.id = ?
  `).get(id) as any

  let parsedTags = []
  try {
    parsedTags = JSON.parse(updated.tags || '[]')
  } catch {
    parsedTags = []
  }

  return {
    contact: {
      ...updated,
      tags: parsedTags,
      can_edit: true
    }
  }
})

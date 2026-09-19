import { randomBytes } from 'crypto'
import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const lastName = (body.last_name || '').trim()
  if (!lastName) {
    throw createError({ statusCode: 400, statusMessage: 'Nachname ist erforderlich' })
  }

  const id = 'contact_' + randomBytes(8).toString('hex')
  const firstName = (body.first_name || '').trim()
  const companyName = (body.company_name || '').trim()
  const roleFunction = (body.role_function || '').trim()
  const phone = (body.phone || '').trim()
  const mobile = (body.mobile || '').trim()
  const email = (body.email || '').trim()
  const projectId = body.project_id ? String(body.project_id).trim() : null
  const categoryGroup = (body.category_group || '').trim()
  const address = (body.address || '').trim()
  const website = (body.website || '').trim()
  const tags = Array.isArray(body.tags) ? JSON.stringify(body.tags) : '[]'
  const notes = (body.notes || '').trim()
  const shareScope = ['company', 'private'].includes(body.share_scope) ? body.share_scope : 'private'

  if (projectId) {
    evaluateProjectAccess(user, projectId, event, 'write')
  }

  const insertStmt = db.prepare(`
    INSERT INTO contacts (
      id, user_id, company_id, project_id, first_name, last_name,
      company_name, role_function, phone, mobile, email,
      category_group, address, website, tags, notes, share_scope, created_at
    ) VALUES (
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?, datetime('now')
    )
  `)

  insertStmt.run(
    id,
    user.id,
    user.company_id || null,
    projectId || null,
    firstName || null,
    lastName,
    companyName || null,
    roleFunction || null,
    phone || null,
    mobile || null,
    email || null,
    categoryGroup || null,
    address || null,
    website || null,
    tags,
    notes || null,
    shareScope
  )

  const newContact = db.prepare(`
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
    parsedTags = JSON.parse(newContact.tags || '[]')
  } catch {
    parsedTags = []
  }

  return {
    contact: {
      ...newContact,
      tags: parsedTags,
      can_edit: true
    }
  }
})

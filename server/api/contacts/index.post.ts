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
  const latitude = Number.isFinite(Number(body.latitude)) && body.latitude !== null && body.latitude !== '' ? Number(body.latitude) : null
  const longitude = Number.isFinite(Number(body.longitude)) && body.longitude !== null && body.longitude !== '' ? Number(body.longitude) : null
  const tags = Array.isArray(body.tags) ? JSON.stringify(body.tags) : '[]'
  const notes = (body.notes || '').trim()
  const shareScope = ['company', 'private'].includes(body.share_scope) ? body.share_scope : 'private'

  if (projectId) {
    evaluateProjectAccess(user, projectId, event, 'write')
  }

  // Duplicate check
  const forceDuplicate = Boolean(body.force_duplicate)
  if (!forceDuplicate) {
    const dupConditions: string[] = []
    const dupParams: any[] = [user.id]
    let companyPart = ''
    if (user.company_id) {
      companyPart = ' OR company_id = ?'
      dupParams.push(user.company_id)
    }

    if (email) {
      dupConditions.push("(email IS NOT NULL AND email != '' AND LOWER(email) = LOWER(?))")
      dupParams.push(email)
    }
    if (mobile) {
      const cleanMob = mobile.replace(/\D+/g, '')
      if (cleanMob.length >= 6) {
        dupConditions.push("(mobile IS NOT NULL AND mobile != '' AND mobile LIKE ?)")
        dupParams.push(`%${cleanMob.slice(-7)}`)
      }
    }
    if (lastName && firstName) {
      dupConditions.push("(LOWER(last_name) = LOWER(?) AND LOWER(first_name) = LOWER(?))")
      dupParams.push(lastName, firstName)
    }

    if (dupConditions.length > 0) {
      const dupQuery = `
        SELECT id, first_name, last_name, company_name, email, mobile, phone, category_group
        FROM contacts
        WHERE (user_id = ?${companyPart})
          AND (${dupConditions.join(' OR ')})
        LIMIT 1
      `
      const existingDup = db.prepare(dupQuery).get(...dupParams) as any
      if (existingDup) {
        const name = [existingDup.first_name, existingDup.last_name].filter(Boolean).join(' ')
        const extra = existingDup.company_name ? ` (${existingDup.company_name})` : ''
        throw createError({
          statusCode: 409,
          statusMessage: `Mögliches Duplikat erkannt: ${name}${extra}`,
          data: {
            error: 'duplicate_found',
            existing_contact: existingDup
          }
        })
      }
    }
  }

  const insertStmt = db.prepare(`
    INSERT INTO contacts (
      id, user_id, company_id, project_id, first_name, last_name,
      company_name, role_function, phone, mobile, email,
      category_group, address, website, latitude, longitude, tags, notes, share_scope, created_at
    ) VALUES (
      ?, ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?,
      ?, ?, ?, ?, ?, ?, ?, ?, datetime('now')
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
    latitude,
    longitude,
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

import { db } from '~/server/db'
import { hashPassword, generateToken } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const body = await readBody(event)
  const { name, email, password, company_name } = body

  if (!name || !email || !password) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Name, E-Mail und Passwort sind erforderlich'
    })
  }

  const existing = db.prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)').get(email)
  if (existing) {
    throw createError({
      statusCode: 400,
      statusMessage: 'Ein Benutzer mit dieser E-Mail existiert bereits'
    })
  }

  const userId = 'usr_' + randomUUID().substring(0, 8)
  const passwordHash = hashPassword(password)
  let companyId: string | null = null
  let companyRole: string | null = null

  // If registering with a new company
  if (company_name && company_name.trim().length > 0) {
    companyId = 'comp_' + randomUUID().substring(0, 8)
    companyRole = 'admin'
    db.prepare(`
      INSERT INTO companies (id, name, subscription_plan, settings)
      VALUES (?, ?, 'starter', ?)
    `).run(
      companyId,
      company_name.trim(),
      JSON.stringify({ allow_document_upload: true, require_2fa: false, max_seats: 10 })
    )
  }

  db.prepare(`
    INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash)
    VALUES (?, ?, ?, 0, ?, ?, ?, ?)
  `).run(
    userId,
    companyId,
    companyRole,
    companyId ? 1 : 0, // company users inherit paid plan tier
    name.trim(),
    email.trim().toLowerCase(),
    passwordHash
  )

  // Automatically create 1 default project folder for onboarding
  const folderId = 'fld_' + randomUUID().substring(0, 8)
  const folderName = company_name ? `${company_name.trim()} - Hauptordner` : `${name.trim()}s Projekte`
  db.prepare(`
    INSERT INTO project_folders (id, owner_id, company_id, name)
    VALUES (?, ?, ?, ?)
  `).run(folderId, userId, companyId, folderName)

  // Create 1 default project
  const projectId = 'prj_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO projects (id, folder_id, title, status)
    VALUES (?, ?, 'Erstes Projekt', 'active')
  `).run(projectId, folderId)

  // Create default list
  const listId = 'lst_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, 'Zu erledigen', 'inherit', 1)
  `).run(listId, projectId)

  const token = generateToken({
    id: userId,
    name,
    email,
    company_id: companyId,
    company_role: companyRole,
    is_superadmin: 0,
    is_pro: companyId ? 1 : 0
  })

  return {
    token,
    user: {
      id: userId,
      name,
      email,
      company_id: companyId,
      company_role: companyRole,
      is_superadmin: false,
      is_pro: Boolean(companyId)
    }
  }
})


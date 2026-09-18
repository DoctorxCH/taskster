import { db } from '~/server/db'
import { requireSuperadmin, hashPassword } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  requireSuperadmin(event)
  const body = await readBody(event)
  const { name, subscription_plan, admin_name, admin_email, admin_password, settings } = body

  if (!name || !name.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Name des Unternehmens ist erforderlich' })
  }

  const companyId = 'comp_' + randomUUID().substring(0, 8)
  const companySettings = JSON.stringify(settings || {
    allow_document_upload: true,
    require_2fa: false,
    max_seats: subscription_plan === 'enterprise' ? 100 : 25
  })

  db.prepare(`
    INSERT INTO companies (id, name, subscription_plan, settings)
    VALUES (?, ?, ?, ?)
  `).run(companyId, name.trim(), subscription_plan || 'starter', companySettings)

  let createdAdmin = null
  if (admin_email && admin_name) {
    const existing = db.prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?)').get(admin_email)
    if (existing) {
      // Assign existing user as company admin
      db.prepare('UPDATE users SET company_id = ?, company_role = "admin", is_pro = 1 WHERE id = ?').run(companyId, (existing as any).id)
      createdAdmin = existing
    } else {
      const adminId = 'usr_' + randomUUID().substring(0, 8)
      const pwHash = hashPassword(admin_password || 'taskster2026!')
      db.prepare(`
        INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash)
        VALUES (?, ?, 'admin', 0, 1, ?, ?, ?)
      `).run(adminId, companyId, admin_name.trim(), admin_email.trim().toLowerCase(), pwHash)
      createdAdmin = { id: adminId, email: admin_email, name: admin_name }
    }
  }

  // Create primary project folder for company
  const folderId = 'fld_' + randomUUID().substring(0, 8)
  const ownerId = createdAdmin ? createdAdmin.id : 'user-superadmin-01'
  db.prepare(`
    INSERT INTO project_folders (id, owner_id, company_id, name)
    VALUES (?, ?, ?, ?)
  `).run(folderId, ownerId, companyId, `${name.trim()} - Hauptordner`)

  return {
    success: true,
    company: {
      id: companyId,
      name: name.trim(),
      subscription_plan: subscription_plan || 'starter',
      admin: createdAdmin
    }
  }
})


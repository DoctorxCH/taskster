import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'company_settings')

  const companies = db.prepare(`
    SELECT c.*,
      (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
      (SELECT COUNT(*) FROM project_folders pf WHERE pf.company_id = c.id) as folder_count
    FROM companies c
    ORDER BY c.created_at DESC
  `).all().map((c: any) => ({
    ...c,
    settings: c.settings ? JSON.parse(c.settings) : {}
  }))

  return { companies }
})


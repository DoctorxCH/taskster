import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)

  let folders = []

  // On personal workspace dashboard: users (including superadmin) only see their company or owned folders, or folders where they are a project member.
  // System-wide administrative overview across all companies/users is strictly reserved for /admin.
  if (user.company_id) {
    folders = db.prepare(`
      SELECT pf.*, u.name as owner_name, c.name as company_name,
        (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
      FROM project_folders pf
      JOIN users u ON u.id = pf.owner_id
      LEFT JOIN companies c ON c.id = pf.company_id
      WHERE pf.company_id = ? OR pf.owner_id = ?
      ORDER BY pf.created_at DESC
    `).all(user.company_id, user.id)
  } else {
    // Private user (or admin in personal private mode): only owned folders or folders where user is a project member
    folders = db.prepare(`
      SELECT pf.*, u.name as owner_name, NULL as company_name,
        (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
      FROM project_folders pf
      JOIN users u ON u.id = pf.owner_id
      WHERE pf.owner_id = ? OR pf.id IN (
        SELECT p.folder_id FROM projects p
        JOIN project_members pm ON pm.project_id = p.id
        WHERE pm.user_id = ?
      )
      ORDER BY pf.created_at DESC
    `).all(user.id, user.id)
  }

  return { folders }
})


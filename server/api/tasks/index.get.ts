import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)

  // Query assigned or relevant tasks across accessible projects
  let tasks = []

  // Return tasks where user is owner of project, or member of project, or in private folder
  // Superadmins only see their own workspace tasks here; system-wide tasks remain in admin area.
  tasks = db.prepare(`
    SELECT t.*, l.title as list_title, p.id as project_id, p.title as project_title, pf.id as folder_id, pf.name as folder_name, pf.icon as folder_icon
    FROM tasks t
    JOIN lists l ON l.id = t.list_id
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE p.owner_id = ? OR pf.owner_id = ? OR p.id IN (
      SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?
    )
    ORDER BY t.created_at DESC
    LIMIT 20
  `).all(user.id, user.id, user.id)

  // Parse JSON custom_data
  const formattedTasks = tasks.map((t: any) => {
    let custom = {}
    try {
      if (t.custom_data) custom = JSON.parse(t.custom_data)
    } catch {}
    return {
      ...t,
      custom_data: custom
    }
  })

  return { tasks: formattedTasks }
})

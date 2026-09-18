import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id')

  // Run the 4-stage evaluation pipeline. Throws 404 if user has no access!
  const context = evaluateProjectAccess(user, projectId, event, 'read')

  const project = db.prepare(`
    SELECT p.*, pf.name as folder_name, pf.owner_id, pf.company_id, c.name as company_name
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    LEFT JOIN companies c ON c.id = pf.company_id
    WHERE p.id = ?
  `).get(projectId) as any

  // Get folder custom field definitions
  const fields = db.prepare(`
    SELECT * FROM folder_field_definitions
    WHERE folder_id = ?
    ORDER BY sort_order ASC
  `).all(context.folderId).map((f: any) => ({
    ...f,
    options: f.options ? JSON.parse(f.options) : [],
    logic_rules: f.logic_rules ? JSON.parse(f.logic_rules) : {}
  }))

  // Get project lists according to Stage 3 (List Scope Check)
  // If list is custom, only owner/superadmin or users with list_access.is_visible == 1 see it!
  const allLists = db.prepare(`
    SELECT l.* FROM lists l
    WHERE l.project_id = ?
    ORDER BY l.sort_order ASC
  `).all(projectId) as any[]

  const accessibleLists = allLists.filter((l) => {
    if (l.access_mode === 'inherit') return true
    if (context.userRole === 'owner' || context.userRole === 'admin' || user.is_superadmin) return true

    // Check list_access table
    const access = db.prepare('SELECT is_visible FROM list_access WHERE list_id = ? AND user_id = ?').get(l.id, user.id) as any
    return access && access.is_visible === 1
  })

  // Load tasks for accessible lists
  const listsWithTasks = accessibleLists.map((l) => {
    const tasks = db.prepare(`
      SELECT * FROM tasks
      WHERE list_id = ?
      ORDER BY sort_order ASC, created_at DESC
    `).all(l.id).map((t: any) => ({
      ...t,
      custom_data: t.custom_data ? JSON.parse(t.custom_data) : {}
    }))

    return {
      ...l,
      tasks
    }
  })

  // Get members
  const members = db.prepare(`
    SELECT pm.id, pm.role, u.id as user_id, u.name, u.email, u.company_role
    FROM project_members pm
    JOIN users u ON u.id = pm.user_id
    WHERE pm.project_id = ?
  `).all(projectId)

  return {
    project,
    userRole: context.userRole,
    fields,
    lists: listsWithTasks,
    members
  }
})

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

  // Project time tracking summary
  const timeSummary = db.prepare(`
    SELECT COALESCE(SUM(duration_minutes), 0) as total_minutes,
           COUNT(*) as entry_count
    FROM time_entries
    WHERE project_id = ?
  `).get(projectId) as any

  const timeEntries = db.prepare(`
    SELECT duration_minutes, hourly_rate FROM time_entries WHERE project_id = ?
  `).all(projectId) as any[]

  const totalMinutes = Number(timeSummary?.total_minutes) || 0
  const totalCost = timeEntries.reduce((sum, e) => {
    const hours = (Number(e.duration_minutes) || 0) / 60
    const rate = Number(e.hourly_rate) || 0
    return sum + (hours * rate)
  }, 0)

  const parsedProject = {
    ...project,
    currency: project.currency || 'CHF',
    budget_hours: Number(project.budget_hours) || 0,
    budget_amount: Number(project.budget_amount) || 0,
    custom_data: typeof project.custom_data === 'string' ? JSON.parse(project.custom_data || '{}') : (project.custom_data || {}),
    tracked_minutes: totalMinutes,
    tracked_hours: Number((totalMinutes / 60).toFixed(2)),
    tracked_cost: Number(totalCost.toFixed(2)),
    time_entry_count: Number(timeSummary?.entry_count) || 0
  }

  // Load tasks for accessible lists
  const listsWithTasks = accessibleLists.map((l) => {
    const tasks = db.prepare(`
      SELECT t.*,
             (SELECT COALESCE(SUM(te.duration_minutes), 0) FROM time_entries te WHERE te.task_id = t.id) as tracked_minutes
      FROM tasks t
      WHERE t.list_id = ?
      ORDER BY t.sort_order ASC, t.created_at DESC
    `).all(l.id).map((t: any) => ({
      ...t,
      budget_hours: Number(t.budget_hours) || 0,
      budget_amount: Number(t.budget_amount) || 0,
      tracked_minutes: Number(t.tracked_minutes) || 0,
      tracked_hours: Number(((Number(t.tracked_minutes) || 0) / 60).toFixed(2)),
      custom_data: t.custom_data ? (typeof t.custom_data === 'string' ? JSON.parse(t.custom_data) : t.custom_data) : {},
      tags: t.tags ? (typeof t.tags === 'string' ? JSON.parse(t.tags) : t.tags) : [],
      checklist: t.checklist ? (typeof t.checklist === 'string' ? JSON.parse(t.checklist) : t.checklist) : []
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
    project: parsedProject,
    userRole: context.userRole,
    fields,
    lists: listsWithTasks,
    members
  }
})

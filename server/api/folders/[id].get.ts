import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')

  const folder = db.prepare(`
    SELECT pf.*, u.name as owner_name, c.name as company_name
    FROM project_folders pf
    JOIN users u ON u.id = pf.owner_id
    LEFT JOIN companies c ON c.id = pf.company_id
    WHERE pf.id = ?
  `).get(folderId) as any

  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Check access: owner, same company, superadmin, or member of at least one project inside
  const isOwner = folder.owner_id === user.id
  const isCompanyPeer = Boolean(user.company_id && user.company_id === folder.company_id)
  const isProjectMember = Boolean(db.prepare(`
    SELECT 1 FROM project_members pm
    JOIN projects p ON p.id = pm.project_id
    WHERE p.folder_id = ? AND pm.user_id = ?
  `).get(folderId, user.id))

  if (!isOwner && !isCompanyPeer && !isProjectMember) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Get field definitions
  const fields = db.prepare(`
    SELECT * FROM folder_field_definitions
    WHERE folder_id = ?
    ORDER BY sort_order ASC
  `).all(folderId).map((f: any) => ({
    ...f,
    options: f.options ? JSON.parse(f.options) : [],
    logic_rules: f.logic_rules ? JSON.parse(f.logic_rules) : {}
  }))

  // Get projects inside this folder with time tracking metrics
  const projectsRaw = db.prepare(`
    SELECT p.*,
      (SELECT COUNT(*) FROM lists l WHERE l.project_id = p.id) as list_count,
      (SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id WHERE l.project_id = p.id) as task_count,
      (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count,
      (SELECT COALESCE(SUM(te.duration_minutes), 0) FROM time_entries te WHERE te.project_id = p.id) as tracked_minutes,
      (SELECT COUNT(*) FROM time_entries te WHERE te.project_id = p.id) as time_entry_count
    FROM projects p
    WHERE p.folder_id = ?
    ORDER BY p.created_at DESC
  `).all(folderId) as any[]

  let folderTotalMinutes = 0
  let folderTotalCost = 0
  let folderTotalBudgetHours = 0
  let folderTotalBudgetAmount = 0

  const projects = projectsRaw.map(p => {
    const minutes = Number(p.tracked_minutes) || 0
    const budgetHours = Number(p.budget_hours) || 0
    const budgetAmount = Number(p.budget_amount) || 0
    const currency = p.currency || 'CHF'

    // Calculate project cost
    const entries = db.prepare('SELECT duration_minutes, hourly_rate FROM time_entries WHERE project_id = ?').all(p.id) as any[]
    const projectCost = entries.reduce((sum, e) => {
      const hours = (Number(e.duration_minutes) || 0) / 60
      const rate = Number(e.hourly_rate) || 0
      return sum + (hours * rate)
    }, 0)

    folderTotalMinutes += minutes
    folderTotalCost += projectCost
    folderTotalBudgetHours += budgetHours
    folderTotalBudgetAmount += budgetAmount

    return {
      ...p,
      currency,
      budget_hours: budgetHours,
      budget_amount: budgetAmount,
      tracked_minutes: minutes,
      tracked_hours: Number((minutes / 60).toFixed(2)),
      tracked_cost: Number(projectCost.toFixed(2)),
      custom_data: p.custom_data ? (typeof p.custom_data === 'string' ? JSON.parse(p.custom_data) : p.custom_data) : {}
    }
  })

  return {
    folder,
    fields,
    projects,
    timeSummary: {
      total_minutes: folderTotalMinutes,
      total_hours: Number((folderTotalMinutes / 60).toFixed(2)),
      total_cost: Number(folderTotalCost.toFixed(2)),
      total_budget_hours: Number(folderTotalBudgetHours.toFixed(2)),
      total_budget_amount: Number(folderTotalBudgetAmount.toFixed(2))
    }
  }
})

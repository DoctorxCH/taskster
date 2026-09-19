import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)

  const projectId = query.project_id ? String(query.project_id) : null
  const taskId = query.task_id ? String(query.task_id) : null
  const folderId = query.folder_id ? String(query.folder_id) : null

  let whereClauses: string[] = []
  let params: any[] = []

  if (taskId) {
    whereClauses.push('te.task_id = ?')
    params.push(taskId)
  } else if (projectId) {
    // Check project read access
    evaluateProjectAccess(user, projectId, event, 'read')
    whereClauses.push('te.project_id = ?')
    params.push(projectId)
  } else if (folderId) {
    whereClauses.push('p.folder_id = ?')
    params.push(folderId)
  } else {
    throw createError({ statusCode: 400, statusMessage: 'project_id, task_id oder folder_id erforderlich' })
  }

  const sql = `
    SELECT te.*,
           u.name as user_name, u.email as user_email,
           t.title as task_title,
           p.title as project_title,
           p.currency as project_currency
    FROM time_entries te
    JOIN users u ON u.id = te.user_id
    JOIN projects p ON p.id = te.project_id
    LEFT JOIN tasks t ON t.id = te.task_id
    WHERE ${whereClauses.join(' AND ')}
    ORDER BY te.entry_date DESC, te.created_at DESC
  `

  const entries = db.prepare(sql).all(...params) as any[]

  // Calculate totals
  const totalMinutes = entries.reduce((sum, e) => sum + (Number(e.duration_minutes) || 0), 0)
  const totalCost = entries.reduce((sum, e) => {
    const hours = (Number(e.duration_minutes) || 0) / 60
    const rate = Number(e.hourly_rate) || 0
    return sum + (hours * rate)
  }, 0)

  return {
    entries: entries.map(e => ({
      ...e,
      is_manual: Boolean(e.is_manual),
      duration_hours: Number(((Number(e.duration_minutes) || 0) / 60).toFixed(2)),
      calculated_amount: Number((((Number(e.duration_minutes) || 0) / 60) * (Number(e.hourly_rate) || 0)).toFixed(2))
    })),
    summary: {
      total_minutes: totalMinutes,
      total_hours: Number((totalMinutes / 60).toFixed(2)),
      total_cost: Number(totalCost.toFixed(2)),
      count: entries.length
    }
  }
})

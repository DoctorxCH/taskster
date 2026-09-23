import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { parseImportDate } from '~/server/utils/dateParser'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id')
  const body = await readBody(event)

  // Requires write permission on project
  evaluateProjectAccess(user, projectId, event, 'write')

  const project = db.prepare('SELECT * FROM projects WHERE id = ?').get(projectId) as any
  if (!project) {
    throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
  }

  const title = body.title !== undefined ? String(body.title).trim() : project.title
  const status = body.status !== undefined ? String(body.status).trim() : project.status
  const customData = body.custom_data !== undefined
    ? (typeof body.custom_data === 'string' ? body.custom_data : JSON.stringify(body.custom_data))
    : project.custom_data
  const currency = body.currency !== undefined ? String(body.currency).toUpperCase().trim() : (project.currency || 'CHF')
  const budgetHours = body.budget_hours !== undefined ? Math.max(0, parseFloat(body.budget_hours) || 0) : (Number(project.budget_hours) || 0)
  const budgetAmount = body.budget_amount !== undefined ? Math.max(0, parseFloat(body.budget_amount) || 0) : (Number(project.budget_amount) || 0)
  const dueDate = body.due_date !== undefined ? (body.due_date ? parseImportDate(body.due_date) : null) : project.due_date

  db.prepare(`
    UPDATE projects
    SET title = ?,
        status = ?,
        custom_data = ?,
        currency = ?,
        budget_hours = ?,
        budget_amount = ?,
        due_date = ?
    WHERE id = ?
  `).run(title, status, customData, currency, budgetHours, budgetAmount, dueDate, projectId)

  const updated = db.prepare('SELECT * FROM projects WHERE id = ?').get(projectId) as any
  return {
    project: {
      ...updated,
      custom_data: typeof updated.custom_data === 'string' ? JSON.parse(updated.custom_data || '{}') : updated.custom_data
    }
  }
})

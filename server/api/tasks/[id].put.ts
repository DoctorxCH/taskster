import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const taskId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const task = db.prepare('SELECT * FROM tasks WHERE id = ?').get(taskId) as any
  if (!task) {
    throw createError({ statusCode: 404, statusMessage: 'Aufgabe nicht gefunden' })
  }

  // Must have write access to list
  evaluateListAccess(user, task.list_id, event, 'write')

  const { title, description, status, custom_data, due_date, list_id, sort_order,
          assigned_to, priority, color, tags, checklist, budget_hours, budget_amount } = body

  // If moving task to another list, check destination list access as well
  const targetListId = list_id || task.list_id
  if (targetListId !== task.list_id) {
    evaluateListAccess(user, targetListId, event, 'write')
  }

  const updatedTitle = title !== undefined ? title.trim() : task.title
  const updatedDesc = description !== undefined ? description : task.description
  const updatedStatus = status !== undefined ? status : task.status
  const updatedCustom = custom_data !== undefined ? JSON.stringify(custom_data) : task.custom_data
  const updatedDueDate = due_date !== undefined ? parseImportDate(due_date) : task.due_date
  const updatedSort = sort_order !== undefined ? sort_order : task.sort_order
  const updatedAssignedTo = assigned_to !== undefined ? (assigned_to || null) : task.assigned_to
  const updatedPriority = priority !== undefined ? priority : (task.priority || 'normal')
  const updatedColor = color !== undefined ? (color || null) : task.color
  const updatedTags = tags !== undefined ? JSON.stringify(tags) : (task.tags || '[]')
  const updatedChecklist = checklist !== undefined ? JSON.stringify(checklist) : (task.checklist || '[]')
  const updatedBudgetHours = budget_hours !== undefined ? Math.max(0, parseFloat(budget_hours) || 0) : (Number(task.budget_hours) || 0)
  const updatedBudgetAmount = budget_amount !== undefined ? Math.max(0, parseFloat(budget_amount) || 0) : (Number(task.budget_amount) || 0)

  db.prepare(`
    UPDATE tasks
    SET title = ?, description = ?, status = ?, custom_data = ?, due_date = ?,
        list_id = ?, sort_order = ?, assigned_to = ?, priority = ?, color = ?,
        tags = ?, checklist = ?, budget_hours = ?, budget_amount = ?
    WHERE id = ?
  `).run(
    updatedTitle, updatedDesc, updatedStatus, updatedCustom, updatedDueDate,
    targetListId, updatedSort, updatedAssignedTo, updatedPriority, updatedColor,
    updatedTags, updatedChecklist, updatedBudgetHours, updatedBudgetAmount, taskId
  )

  return {
    success: true,
    task: {
      id: taskId,
      list_id: targetListId,
      title: updatedTitle,
      description: updatedDesc,
      status: updatedStatus,
      custom_data: JSON.parse(updatedCustom || '{}'),
      due_date: updatedDueDate,
      sort_order: updatedSort,
      assigned_to: updatedAssignedTo,
      priority: updatedPriority,
      color: updatedColor,
      tags: JSON.parse(updatedTags),
      checklist: JSON.parse(updatedChecklist),
      budget_hours: updatedBudgetHours,
      budget_amount: updatedBudgetAmount
    }
  }
})


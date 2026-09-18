import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateListAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { list_id, title, description, status, custom_data, due_date, assigned_to, priority, color, tags, checklist } = body

  if (!list_id || !title || !title.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Listen-ID und Aufgabentitel erforderlich' })
  }

  // Evaluates 4-stage pipeline: viewer gets 403, unauthorized gets 404
  evaluateListAccess(user, list_id, event, 'write')

  const taskId = 'tsk_' + randomUUID().substring(0, 8)
  const count = (db.prepare('SELECT COUNT(*) as c FROM tasks WHERE list_id = ?').get(list_id) as any).c

  db.prepare(`
    INSERT INTO tasks (id, list_id, title, description, status, custom_data, due_date, sort_order, assigned_to, priority, color, tags, checklist)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    taskId,
    list_id,
    title.trim(),
    description || '',
    status || 'todo',
    JSON.stringify(custom_data || {}),
    due_date || null,
    count + 1,
    assigned_to || null,
    priority || 'normal',
    color || null,
    JSON.stringify(tags || []),
    JSON.stringify(checklist || [])
  )

  return {
    task: {
      id: taskId,
      list_id,
      title: title.trim(),
      description: description || '',
      status: status || 'todo',
      custom_data: custom_data || {},
      due_date: due_date || null,
      sort_order: count + 1,
      assigned_to: assigned_to || null,
      priority: priority || 'normal',
      color: color || null,
      tags: tags || [],
      checklist: checklist || []
    }
  }
})


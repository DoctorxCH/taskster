import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { project_id, title, access_mode } = body

  if (!project_id || !title || !title.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Projekt-ID und Titel erforderlich' })
  }

  // Must have write permission in project (viewer will get 403)
  evaluateProjectAccess(user, project_id, event, 'write')

  const count = (db.prepare('SELECT COUNT(*) as c FROM lists WHERE project_id = ?').get(project_id) as any).c
  const listId = 'lst_' + randomUUID().substring(0, 8)

  db.prepare(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, ?, ?, ?)
  `).run(listId, project_id, title.trim(), access_mode || 'inherit', count + 1)

  return {
    list: {
      id: listId,
      project_id,
      title: title.trim(),
      access_mode: access_mode || 'inherit',
      sort_order: count + 1,
      tasks: []
    }
  }
})


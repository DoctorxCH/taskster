import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { project_id, task_id, entry_type, title, content, metadata } = body

  if (!project_id || !title || !content) {
    throw createError({ statusCode: 400, statusMessage: 'Projekt-ID, Titel und Inhalt sind erforderlich' })
  }

  // Stage 1-4 validation: viewer cannot write
  evaluateProjectAccess(user, project_id, event, 'write')

  const journalId = 'jrn_' + randomUUID().substring(0, 8)

  db.prepare(`
    INSERT INTO project_journals (id, project_id, task_id, author_id, entry_type, title, content, metadata)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    journalId,
    project_id,
    task_id || null,
    user.id,
    entry_type || 'manual',
    title.trim(),
    content.trim(),
    JSON.stringify(metadata || {})
  )

  return {
    entry: {
      id: journalId,
      project_id,
      task_id,
      author_id: user.id,
      author_name: user.name,
      entry_type: entry_type || 'manual',
      title: title.trim(),
      content: content.trim(),
      metadata: metadata || {},
      created_at: new Date().toISOString()
    }
  }
})


import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  const { task_id, entry_type, title, content, metadata } = body
  let projectId = body.project_id

  if (!title || !content) {
    throw createError({ statusCode: 400, statusMessage: 'Titel und Inhalt sind erforderlich' })
  }

  // If no project_id provided, resolve or create default project for user
  if (!projectId) {
    const existing = db.prepare(`
      SELECT p.id FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE pf.company_id = ? OR pf.user_id = ?
      ORDER BY p.is_default DESC, p.created_at ASC
      LIMIT 1
    `).get(user.company_id || '', user.id) as any

    if (existing?.id) {
      projectId = existing.id
    } else {
      // Fallback: any accessible project or create a default folder & project
      const anyProject = db.prepare('SELECT id FROM projects LIMIT 1').get() as any
      if (anyProject?.id) {
        projectId = anyProject.id
      } else {
        const folderId = 'fld_' + randomUUID().substring(0, 8)
        db.prepare(`
          INSERT INTO project_folders (id, user_id, company_id, name, visibility)
          VALUES (?, ?, ?, ?, ?)
        `).run(folderId, user.id, user.company_id || null, 'Persönliche Notizen', 'private')

        projectId = 'prj_' + randomUUID().substring(0, 8)
        db.prepare(`
          INSERT INTO projects (id, folder_id, title, description, is_default, visibility)
          VALUES (?, ?, ?, ?, 1, 'private')
        `).run(projectId, folderId, 'Meine Notizen', 'Automatisch angelegte Notizenablage')
      }
    }
  }

  // Validate write access
  if (projectId) {
    evaluateProjectAccess(user, projectId, event, 'write')
  }

  const journalId = 'jrn_' + randomUUID().substring(0, 8)

  db.prepare(`
    INSERT INTO project_journals (id, project_id, task_id, author_id, entry_type, title, content, metadata)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    journalId,
    projectId,
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
      project_id: projectId,
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


import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'
import { cleanOleResidue } from '~/utils/emailParser'
import { matchProjectByText } from '~/utils/projectMatcher'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)
  let {
    folder_id,
    project_id,
    task_id,
    type,
    category,
    entry_type,
    title,
    content,
    visibility,
    allowed_group_id,
    metadata,
    attachments,
    attendees
  } = body

  content = cleanOleResidue((content || '').trim())
  if (!content && !title) {
    throw createError({ statusCode: 400, statusMessage: 'Inhalt oder Titel ist erforderlich' })
  }

  // Auto-generate title if empty (e.g. for quick notes)
  if (!title || !title.trim()) {
    const firstLine = content.split('\n')[0].trim()
    title = firstLine.substring(0, 60) || `Notiz (${new Date().toLocaleDateString('de-CH')})`
  } else {
    title = title.trim()
  }

  type = type === 'note' ? 'note' : 'entry'
  category = category || (type === 'note' ? 'notiz' : 'allgemein')
  visibility = ['only_me', 'group', 'company', 'all'].includes(visibility) ? visibility : 'all'

  // Auto project assignment if project_id is 'auto' or empty
  if (!project_id || project_id === 'auto') {
    let candidateProjects: any[] = []
    if (folder_id) {
      candidateProjects = db.prepare(`
        SELECT id, title, folder_id, custom_data FROM projects WHERE folder_id = ?
      `).all(folder_id) as any[]
    } else {
      candidateProjects = db.prepare(`
        SELECT p.id, p.title, p.folder_id, p.custom_data FROM projects p
        JOIN project_folders pf ON pf.id = p.folder_id
        WHERE pf.company_id = ? OR pf.owner_id = ?
      `).all(user.company_id || '', user.id) as any[]
    }

    const match = matchProjectByText(candidateProjects, title + ' ' + content)
    if (match) {
      project_id = match.project.id
      if (!folder_id && match.project.folder_id) {
        folder_id = match.project.folder_id
      }
    } else if (folder_id) {
      // Pick default project or first project in folder, or keep null
      const defProj = db.prepare(`SELECT id FROM projects WHERE folder_id = ? ORDER BY is_default DESC, created_at ASC LIMIT 1`).get(folder_id) as any
      project_id = defProj?.id || null
    } else {
      // No project match in global scope: reset 'auto' to null so it doesn't fail permission check
      project_id = null
    }
  }

  // Validate access
  if (project_id) {
    try {
      evaluateProjectAccess(user, project_id, event, 'write')
      if (!folder_id) {
        const prjRow = db.prepare('SELECT folder_id FROM projects WHERE id = ?').get(project_id) as any
        folder_id = prjRow?.folder_id || null
      }
    } catch (e: any) {
      throw e
    }
  } else if (folder_id) {
    try {
      evaluateFolderAccess(user, folder_id, event, 'write')
    } catch (e: any) {
      throw e
    }
  }

  // Auto task assignment if task_id === 'auto' and project_id exists
  if (task_id === 'auto' && project_id) {
    const openTasks = db.prepare(`
      SELECT t.id, t.title FROM tasks t
      JOIN lists l ON l.id = t.list_id
      WHERE l.project_id = ? AND t.is_completed = 0
    `).all(project_id) as any[]

    const searchTarget = (title + ' ' + content).toLowerCase()
    let matchedTaskId: string | null = null
    for (const t of openTasks) {
      const tTitle = (t.title || '').trim().toLowerCase()
      if (tTitle && searchTarget.includes(tTitle)) {
        matchedTaskId = t.id
        break
      }
    }
    task_id = matchedTaskId || null
  } else if (task_id === 'auto' || task_id === '') {
    task_id = null
  }

  const journalId = 'jrn_' + randomUUID().substring(0, 8)
  const now = new Date().toISOString()

  db.prepare(`
    INSERT INTO project_journals (
      id, company_id, folder_id, project_id, user_id, author_id, task_id,
      type, category, entry_type, title, content, visibility, allowed_group_id, metadata, created_at, updated_at
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    journalId,
    user.company_id || null,
    folder_id || null,
    project_id || null,
    user.id,
    user.id,
    task_id || null,
    type,
    category,
    entry_type || 'manual',
    title,
    content,
    visibility,
    visibility === 'group' ? allowed_group_id : null,
    JSON.stringify(metadata || {}),
    now,
    now
  )

  // Attachments
  if (Array.isArray(attachments) && attachments.length > 0) {
    const insertAtt = db.prepare(`
      INSERT INTO project_journal_attachments (id, journal_id, file_name, file_path, file_type, file_size, created_at)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `)
    for (const att of attachments) {
      if (att && att.file_name) {
        insertAtt.run(
          'att_' + randomUUID().substring(0, 8),
          journalId,
          att.file_name,
          att.file_path || '',
          att.file_type || 'application/octet-stream',
          att.file_size || 0,
          now
        )
      }
    }
  }

  // Attendees
  if (Array.isArray(attendees) && attendees.length > 0) {
    const insertAtd = db.prepare(`
      INSERT INTO project_journal_attendees (id, journal_id, contact_id, name, email, role, present)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `)
    for (const atd of attendees) {
      if (atd && atd.name) {
        insertAtd.run(
          'atd_' + randomUUID().substring(0, 8),
          journalId,
          atd.contact_id || null,
          atd.name,
          atd.email || null,
          atd.role || null,
          atd.present !== false ? 1 : 0
        )
      }
    }
  }

  return {
    success: true,
    entry: {
      id: journalId,
      company_id: user.company_id || null,
      folder_id,
      project_id,
      task_id,
      user_id: user.id,
      author_id: user.id,
      type,
      category,
      title,
      content,
      visibility,
      metadata: metadata || {},
      created_at: now
    }
  }
})

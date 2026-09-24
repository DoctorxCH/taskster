import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'
import { cleanOleResidue } from '~/utils/emailParser'

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

  // Auto project assignment if project_id is 'auto' or empty with folder_id
  if ((!project_id || project_id === 'auto') && folder_id) {
    const folderProjects = db.prepare(`
      SELECT id, title, custom_data FROM projects WHERE folder_id = ?
    `).all(folder_id) as any[]

    const searchTarget = (title + ' ' + content).toLowerCase()
    let matchedId: string | null = null

    for (const fp of folderProjects) {
      const pTitle = (fp.title || '').trim().toLowerCase()
      if (pTitle && searchTarget.includes(pTitle)) {
        matchedId = fp.id
        break
      }
      if (fp.custom_data) {
        try {
          const cd = typeof fp.custom_data === 'string' ? JSON.parse(fp.custom_data) : fp.custom_data
          if (cd && typeof cd === 'object') {
            for (const val of Object.values(cd)) {
              const valStr = String(val).trim().toLowerCase()
              if (valStr.length >= 3 && searchTarget.includes(valStr)) {
                matchedId = fp.id
                break
              }
            }
          }
        } catch (_) {}
      }
      if (matchedId) break
    }

    if (matchedId) {
      project_id = matchedId
    } else {
      // Pick default project or first project in folder, or keep null
      const defProj = db.prepare(`SELECT id FROM projects WHERE folder_id = ? ORDER BY is_default DESC, created_at ASC LIMIT 1`).get(folder_id) as any
      project_id = defProj?.id || null
    }
  }

  // If still no project_id and no folder_id, find or fallback to user default
  if (!project_id && !folder_id) {
    const existing = db.prepare(`
      SELECT p.id, p.folder_id FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE pf.company_id = ? OR pf.owner_id = ?
      ORDER BY p.is_default DESC, p.created_at ASC
      LIMIT 1
    `).get(user.company_id || '', user.id) as any

    if (existing?.id) {
      project_id = existing.id
      folder_id = existing.folder_id
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

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string
  const body = await readBody(event)

  evaluateProjectAccess(user, projectId, event, 'write')

  const title = (body.title || '').trim()
  const content = (body.content || '').trim()
  const type = ['entry', 'note'].includes(body.type) ? body.type : 'entry'
  const category = (body.category || 'allgemein').trim()
  const visibility = ['only_me', 'group', 'company', 'all'].includes(body.visibility) ? body.visibility : 'all'
  const allowedGroupId = body.allowed_group_id || null
  const taskId = body.task_id || null
  const metadata = body.metadata || {}
  const attachments = Array.isArray(body.attachments) ? body.attachments : []
  const attendees = Array.isArray(body.attendees) ? body.attendees : []

  if (!title || !content) {
    throw createError({ statusCode: 400, statusMessage: 'Titel und Inhalt sind erforderlich' })
  }

  const jrnId = 'jrn_' + randomUUID().substring(0, 8)
  const metaJson = JSON.stringify(metadata)

  db.prepare(`
    INSERT INTO project_journals (id, company_id, project_id, user_id, author_id, task_id, type, category, entry_type, title, content, visibility, allowed_group_id, metadata, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
  `).run(
    jrnId,
    user.company_id || null,
    projectId,
    user.id,
    user.id,
    taskId,
    type,
    category,
    type === 'note' ? 'note' : 'manual',
    title,
    content,
    visibility,
    allowedGroupId,
    metaJson
  )

  const savedAttachments: any[] = []
  if (attachments.length > 0) {
    const attInsert = db.prepare(`
      INSERT INTO project_journal_attachments (id, journal_id, file_name, file_path, file_type, file_size, created_at)
      VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    `)
    for (const att of attachments) {
      if (att.file_name && att.file_path) {
        const attId = 'pja_' + randomUUID().substring(0, 8)
        attInsert.run(
          attId,
          jrnId,
          att.file_name,
          att.file_path,
          att.file_type || 'application/octet-stream',
          Number(att.file_size || 0)
        )
        savedAttachments.push({
          id: attId,
          journal_id: jrnId,
          file_name: att.file_name,
          file_path: att.file_path,
          file_type: att.file_type || 'application/octet-stream',
          file_size: Number(att.file_size || 0),
          created_at: new Date().toISOString()
        })
      }
    }
  }

  const savedAttendees: any[] = []
  if (type === 'entry' && attendees.length > 0) {
    const atdInsert = db.prepare(`
      INSERT INTO project_journal_attendees (id, journal_id, contact_id, name, email, role, present)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `)
    for (const atd of attendees) {
      if (atd.name && atd.name.trim()) {
        const atdId = 'pjat_' + randomUUID().substring(0, 8)
        const isPresent = atd.present === undefined || atd.present === true || atd.present === 1 ? 1 : 0
        atdInsert.run(
          atdId,
          jrnId,
          atd.contact_id || null,
          atd.name.trim(),
          atd.email ? atd.email.trim() : null,
          atd.role ? atd.role.trim() : null,
          isPresent
        )
        savedAttendees.push({
          id: atdId,
          journal_id: jrnId,
          contact_id: atd.contact_id || null,
          name: atd.name.trim(),
          email: atd.email ? atd.email.trim() : null,
          role: atd.role ? atd.role.trim() : null,
          present: !!isPresent
        })
      }
    }
  }

  return {
    success: true,
    entry: {
      id: jrnId,
      company_id: user.company_id || null,
      project_id: projectId,
      user_id: user.id,
      author_name: user.name,
      author_email: user.email,
      type,
      category,
      title,
      content,
      visibility,
      allowed_group_id: allowedGroupId,
      metadata,
      attachments: savedAttachments,
      attendees: savedAttendees,
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
  }
})

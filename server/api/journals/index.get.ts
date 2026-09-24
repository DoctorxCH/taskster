import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)
  const projectId = query.project_id as string | undefined
  const folderId = query.folder_id as string | undefined

  let entries: any[] = []

  if (projectId) {
    evaluateProjectAccess(user, projectId, event, 'read')
    entries = db.prepare(`
      SELECT j.*, 
             COALESCE(u.name, 'Unbekannt') as author_name, 
             u.email as author_email,
             t.title as task_title,
             p.title as project_title,
             pf.name as folder_name
      FROM project_journals j
      LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
      LEFT JOIN tasks t ON t.id = j.task_id
      LEFT JOIN projects p ON p.id = j.project_id
      LEFT JOIN project_folders pf ON pf.id = COALESCE(j.folder_id, p.folder_id)
      WHERE j.project_id = ?
      ORDER BY j.created_at DESC
    `).all(projectId)
  } else if (folderId) {
    evaluateFolderAccess(user, folderId, event, 'read')
    entries = db.prepare(`
      SELECT j.*, 
             COALESCE(u.name, 'Unbekannt') as author_name, 
             u.email as author_email,
             t.title as task_title,
             p.title as project_title,
             pf.name as folder_name
      FROM project_journals j
      LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
      LEFT JOIN tasks t ON t.id = j.task_id
      LEFT JOIN projects p ON p.id = j.project_id
      LEFT JOIN project_folders pf ON pf.id = COALESCE(j.folder_id, p.folder_id)
      WHERE (j.folder_id = ? OR j.project_id IN (SELECT id FROM projects WHERE folder_id = ?))
      ORDER BY j.created_at DESC
    `).all(folderId, folderId)
  } else {
    // Return all accessible journals for user's company or user
    const isSuperadmin = !!user.is_superadmin
    const companyId = user.company_id || ''

    if (isSuperadmin) {
      entries = db.prepare(`
        SELECT j.*, 
               COALESCE(u.name, 'Unbekannt') as author_name, 
               u.email as author_email,
               t.title as task_title,
               p.title as project_title,
               pf.name as folder_name
        FROM project_journals j
        LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
        LEFT JOIN tasks t ON t.id = j.task_id
        LEFT JOIN projects p ON p.id = j.project_id
        LEFT JOIN project_folders pf ON pf.id = COALESCE(j.folder_id, p.folder_id)
        ORDER BY j.created_at DESC
        LIMIT 500
      `).all()
    } else if (companyId) {
      entries = db.prepare(`
        SELECT j.*, 
               COALESCE(u.name, 'Unbekannt') as author_name, 
               u.email as author_email,
               t.title as task_title,
               p.title as project_title,
               pf.name as folder_name
        FROM project_journals j
        LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
        LEFT JOIN tasks t ON t.id = j.task_id
        LEFT JOIN projects p ON p.id = j.project_id
        LEFT JOIN project_folders pf ON pf.id = COALESCE(j.folder_id, p.folder_id)
        WHERE (
          j.company_id = ?
          OR COALESCE(j.user_id, j.author_id) = ?
          OR j.folder_id IN (SELECT id FROM project_folders WHERE company_id = ? OR owner_id = ?)
          OR j.project_id IN (SELECT p2.id FROM projects p2 JOIN project_folders pf2 ON pf2.id = p2.folder_id WHERE pf2.company_id = ? OR pf2.owner_id = ?)
        )
        ORDER BY j.created_at DESC
        LIMIT 500
      `).all(companyId, user.id, companyId, user.id, companyId, user.id)
    } else {
      entries = db.prepare(`
        SELECT j.*, 
               COALESCE(u.name, 'Unbekannt') as author_name, 
               u.email as author_email,
               t.title as task_title,
               p.title as project_title,
               pf.name as folder_name
        FROM project_journals j
        LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
        LEFT JOIN tasks t ON t.id = j.task_id
        LEFT JOIN projects p ON p.id = j.project_id
        LEFT JOIN project_folders pf ON pf.id = COALESCE(j.folder_id, p.folder_id)
        WHERE (
          COALESCE(j.user_id, j.author_id) = ?
          OR j.folder_id IN (SELECT id FROM project_folders WHERE owner_id = ?)
          OR j.project_id IN (SELECT p2.id FROM projects p2 JOIN project_folders pf2 ON pf2.id = p2.folder_id WHERE pf2.owner_id = ?)
        )
        ORDER BY j.created_at DESC
        LIMIT 500
      `).all(user.id, user.id, user.id)
    }
  }

  // Fetch attachments & attendees for these entries
  const journalIds = entries.map((e: any) => e.id)
  let attachmentsByJournal: Record<string, any[]> = {}
  let attendeesByJournal: Record<string, any[]> = {}

  if (journalIds.length > 0) {
    try {
      const placeholders = journalIds.map(() => '?').join(',')
      const atts = db.prepare(`SELECT * FROM project_journal_attachments WHERE journal_id IN (${placeholders}) ORDER BY created_at ASC`).all(...journalIds) as any[]
      for (const att of atts) {
        if (!attachmentsByJournal[att.journal_id]) attachmentsByJournal[att.journal_id] = []
        attachmentsByJournal[att.journal_id].push(att)
      }
    } catch (_) {}

    try {
      const placeholders = journalIds.map(() => '?').join(',')
      const atds = db.prepare(`SELECT * FROM project_journal_attendees WHERE journal_id IN (${placeholders}) ORDER BY id ASC`).all(...journalIds) as any[]
      for (const atd of atds) {
        if (!attendeesByJournal[atd.journal_id]) attendeesByJournal[atd.journal_id] = []
        attendeesByJournal[atd.journal_id].push({
          ...atd,
          present: !!atd.present
        })
      }
    } catch (_) {}
  }

  return {
    entries: entries.map((e: any) => {
      let meta = {}
      if (e.metadata) {
        try {
          meta = typeof e.metadata === 'string' ? JSON.parse(e.metadata) : e.metadata
        } catch (_) {}
      }
      return {
        ...e,
        metadata: meta,
        attachments: attachmentsByJournal[e.id] || [],
        attendees: attendeesByJournal[e.id] || []
      }
    })
  }
})

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string

  // Evaluate project read access
  evaluateProjectAccess(user, projectId, event, 'read')

  const isSuperadmin = user.is_superadmin ? 1 : 0
  const userId = user.id
  const companyId = user.company_id || ''

  // Visibility-Matrix SQL Query
  const entriesQuery = db.prepare(`
    SELECT j.*, 
           COALESCE(u.name, 'Unbekannt') as author_name,
           u.email as author_email,
           t.title as task_title
    FROM project_journals j
    LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
    LEFT JOIN tasks t ON t.id = j.task_id
    WHERE j.project_id = ?
      AND (
        ? = 1
        OR COALESCE(j.user_id, j.author_id) = ?
        OR j.visibility = 'all'
        OR (j.visibility = 'company' AND j.company_id = ? AND ? != '')
        OR (j.visibility = 'group' AND j.allowed_group_id IS NOT NULL AND EXISTS (
            SELECT 1 FROM user_group_members ugm 
            WHERE ugm.group_id = j.allowed_group_id AND ugm.user_id = ?
        ))
      )
    ORDER BY j.created_at DESC
  `)

  const rawEntries = entriesQuery.all(projectId, isSuperadmin, userId, companyId, companyId, userId) as any[]

  if (rawEntries.length === 0) {
    return { entries: [] }
  }

  const journalIds = rawEntries.map(e => e.id)
  const inClause = journalIds.map(() => '?').join(',')

  // Load attachments
  const attStmt = db.prepare(`SELECT * FROM project_journal_attachments WHERE journal_id IN (${inClause}) ORDER BY created_at ASC`)
  const allAtts = attStmt.all(...journalIds) as any[]
  const attachmentsByJournal: Record<string, any[]> = {}
  for (const att of allAtts) {
    if (!attachmentsByJournal[att.journal_id]) attachmentsByJournal[att.journal_id] = []
    attachmentsByJournal[att.journal_id].push(att)
  }

  // Load attendees
  const atdStmt = db.prepare(`SELECT * FROM project_journal_attendees WHERE journal_id IN (${inClause}) ORDER BY id ASC`)
  const allAtds = atdStmt.all(...journalIds) as any[]
  const attendeesByJournal: Record<string, any[]> = {}
  for (const atd of allAtds) {
    atd.present = !!atd.present
    if (!attendeesByJournal[atd.journal_id]) attendeesByJournal[atd.journal_id] = []
    attendeesByJournal[atd.journal_id].push(atd)
  }

  const entries = rawEntries.map(e => {
    let metadata: any = {}
    if (e.metadata) {
      try {
        metadata = typeof e.metadata === 'string' ? JSON.parse(e.metadata) : e.metadata
      } catch (_) {
        metadata = {}
      }
    }
    return {
      ...e,
      metadata,
      attachments: attachmentsByJournal[e.id] || [],
      attendees: attendeesByJournal[e.id] || []
    }
  })

  return { entries }
})

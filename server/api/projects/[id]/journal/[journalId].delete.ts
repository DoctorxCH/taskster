import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string
  const journalId = getRouterParam(event, 'journalId') as string

  evaluateProjectAccess(user, projectId, event, 'write')

  try { db.prepare(`DELETE FROM project_journal_attachments WHERE journal_id = ?`).run(journalId) } catch (_) {}
  try { db.prepare(`DELETE FROM project_journal_attendees WHERE journal_id = ?`).run(journalId) } catch (_) {}
  db.prepare(`DELETE FROM project_journals WHERE id = ? AND project_id = ?`).run(journalId, projectId)

  return { success: true }
})

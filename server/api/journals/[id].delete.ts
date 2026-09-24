import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess, evaluateFolderAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const journalId = getRouterParam(event, 'id') as string

  const journal = db.prepare('SELECT * FROM project_journals WHERE id = ?').get(journalId) as any
  if (!journal) {
    throw createError({ statusCode: 404, statusMessage: 'Journal-Eintrag nicht gefunden' })
  }

  // Permission check:
  const isAuthor = journal.author_id === user.id || journal.user_id === user.id
  const isSuperadmin = !!user.is_superadmin
  const isCompanyAdmin = user.company_role === 'admin' && user.company_id && user.company_id === journal.company_id

  if (!isAuthor && !isSuperadmin && !isCompanyAdmin) {
    let hasAccess = false
    if (journal.project_id) {
      try {
        evaluateProjectAccess(user, journal.project_id, event, 'write')
        hasAccess = true
      } catch (_) {}
    }
    if (!hasAccess && journal.folder_id) {
      try {
        evaluateFolderAccess(user, journal.folder_id, event, 'write')
        hasAccess = true
      } catch (_) {}
    }
    if (!hasAccess) {
      throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Löschen dieses Eintrags' })
    }
  }

  try { db.prepare(`DELETE FROM project_journal_attachments WHERE journal_id = ?`).run(journalId) } catch (_) {}
  try { db.prepare(`DELETE FROM project_journal_attendees WHERE journal_id = ?`).run(journalId) } catch (_) {}
  db.prepare(`DELETE FROM project_journals WHERE id = ?`).run(journalId)

  return { success: true }
})

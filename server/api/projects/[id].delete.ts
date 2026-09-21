import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id')

  // Check project access
  const context = evaluateProjectAccess(user, projectId, event, 'write')

  const canDelete = context.userRole === 'owner' || context.userRole === 'admin' || context.ownerId === user.id || Boolean(user.is_superadmin)
  if (!canDelete) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Löschen dieses Projekts' })
  }

  const deleteTransaction = db.transaction(() => {
    const lists = db.prepare('SELECT id FROM lists WHERE project_id = ?').all(projectId) as any[]
    for (const lst of lists) {
      db.prepare('DELETE FROM tasks WHERE list_id = ?').run(lst.id)
      try { db.prepare('DELETE FROM list_access WHERE list_id = ?').run(lst.id) } catch {}
    }
    db.prepare('DELETE FROM lists WHERE project_id = ?').run(projectId)
    try { db.prepare('DELETE FROM time_entries WHERE project_id = ?').run(projectId) } catch {}
    try { db.prepare('DELETE FROM project_journals WHERE project_id = ?').run(projectId) } catch {}
    try { db.prepare('DELETE FROM project_documents WHERE project_id = ?').run(projectId) } catch {}
    try { db.prepare('DELETE FROM project_group_access WHERE project_id = ?').run(projectId) } catch {}
    try { db.prepare('DELETE FROM project_members WHERE project_id = ?').run(projectId) } catch {}
    try { db.prepare('UPDATE calendar_events SET project_id = NULL WHERE project_id = ?').run(projectId) } catch {}
    db.prepare('DELETE FROM projects WHERE id = ?').run(projectId)
  })

  deleteTransaction()

  return { success: true }
})

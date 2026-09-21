import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const folderId = getRouterParam(event, 'id')

  const folder = db.prepare('SELECT * FROM project_folders WHERE id = ?').get(folderId) as any
  if (!folder) {
    throw createError({ statusCode: 404, statusMessage: 'Projektordner nicht gefunden' })
  }

  // Zero-Trust: Only owner or superadmin can delete folder
  if (folder.owner_id !== user.id && !user.is_superadmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur der Eigentümer kann diesen Projektordner löschen' })
  }

  const deleteTransaction = db.transaction(() => {
    // Find all projects in folder
    const projects = db.prepare('SELECT id FROM projects WHERE folder_id = ?').all(folderId) as any[]
    for (const prj of projects) {
      const lists = db.prepare('SELECT id FROM lists WHERE project_id = ?').all(prj.id) as any[]
      for (const lst of lists) {
        db.prepare('DELETE FROM tasks WHERE list_id = ?').run(lst.id)
        try { db.prepare('DELETE FROM list_access WHERE list_id = ?').run(lst.id) } catch {}
      }
      db.prepare('DELETE FROM lists WHERE project_id = ?').run(prj.id)
      try { db.prepare('DELETE FROM time_entries WHERE project_id = ?').run(prj.id) } catch {}
      try { db.prepare('DELETE FROM project_journals WHERE project_id = ?').run(prj.id) } catch {}
      try { db.prepare('DELETE FROM project_documents WHERE project_id = ?').run(prj.id) } catch {}
      try { db.prepare('DELETE FROM project_group_access WHERE project_id = ?').run(prj.id) } catch {}
      try { db.prepare('DELETE FROM project_members WHERE project_id = ?').run(prj.id) } catch {}
      try { db.prepare('UPDATE calendar_events SET project_id = NULL WHERE project_id = ?').run(prj.id) } catch {}
      db.prepare('DELETE FROM projects WHERE id = ?').run(prj.id)
    }

    try { db.prepare('DELETE FROM folder_members WHERE folder_id = ?').run(folderId) } catch {}
    try { db.prepare('DELETE FROM folder_field_definitions WHERE folder_id = ?').run(folderId) } catch {}
    try { db.prepare('DELETE FROM folder_group_access WHERE folder_id = ?').run(folderId) } catch {}
    db.prepare('DELETE FROM project_folders WHERE id = ?').run(folderId)
  })

  deleteTransaction()

  return { success: true }
})

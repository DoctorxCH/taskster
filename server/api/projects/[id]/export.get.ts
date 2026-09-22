import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')
  const query = getQuery(event)
  const format = String(query.format || 'json').toLowerCase()

  // Enterprise plan check
  const isEnterprise = Boolean(
    user.is_superadmin ||
    (user.company_id && (user.company_role === 'admin' || (user as any).license_type === 'enterprise'))
  )

  if (!isEnterprise) {
    throw createError({
      statusCode: 403,
      statusMessage: 'Projekt-Export ist exklusiv für den Enterprise-Tarif verfügbar.'
    })
  }

  const project = db.prepare('SELECT * FROM projects WHERE id = ?').get(id) as any
  if (!project) {
    throw createError({ statusCode: 404, statusMessage: 'Projekt nicht gefunden' })
  }

  const lists = db.prepare('SELECT * FROM lists WHERE project_id = ? ORDER BY sort_order ASC').all(id) as any[]
  const listIds = lists.map((l) => l.id)

  let tasks: any[] = []
  if (listIds.length > 0) {
    const placeholders = listIds.map(() => '?').join(',')
    tasks = db.prepare(`SELECT * FROM tasks WHERE list_id IN (${placeholders}) ORDER BY sort_order ASC`).all(...listIds) as any[]
  }

  const members = db.prepare(`
    SELECT pm.role, u.id, u.name, u.email
    FROM project_members pm
    JOIN users u ON u.id = pm.user_id
    WHERE pm.project_id = ?
  `).all(id) as any[]

  if (format === 'csv') {
    const safeTitle = (project.title || 'project').replace(/[^a-zA-Z0-9_-]/g, '_')
    setResponseHeaders(event, {
      'Content-Type': 'text/csv; charset=utf-8',
      'Content-Disposition': `attachment; filename="${safeTitle}_export.csv"`
    })

    const rows: string[] = []
    rows.push('Projekt;Abschnitt;Aufgabe;Status;Priorität;Fälligkeitsdatum;Erstellt_am')
    for (const t of tasks) {
      const parentList = lists.find((l) => l.id === t.list_id)
      const pTitle = `"${(project.title || '').replace(/"/g, '""')}"`
      const lTitle = `"${(parentList?.title || '').replace(/"/g, '""')}"`
      const tTitle = `"${(t.title || '').replace(/"/g, '""')}"`
      const tStatus = t.status || 'todo'
      const tPrio = t.priority || 'normal'
      const tDue = t.due_date || ''
      const tCreated = t.created_at || ''
      rows.push([pTitle, lTitle, tTitle, tStatus, tPrio, tDue, tCreated].join(';'))
    }
    // Return UTF-8 BOM + rows
    return '\uFEFF' + rows.join('\r\n')
  }

  return {
    project,
    lists,
    tasks,
    members
  }
})

import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  const query = getQuery(event)
  const limit = Math.min(Math.max(parseInt(String(query.limit || 50)), 1), 200)
  const offset = Math.max(parseInt(String(query.offset || 0)), 0)
  const search = query.search ? String(query.search).trim() : ''
  const actionFilter = query.action ? String(query.action).trim() : ''

  let whereClauses: string[] = []
  let params: any[] = []

  if (actionFilter) {
    whereClauses.push('a.action = ?')
    params.push(actionFilter)
  }

  if (search) {
    whereClauses.push('(a.action LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR c.name LIKE ? OR a.ip_address LIKE ?)')
    const s = `%${search}%`
    params.push(s, s, s, s, s)
  }

  const whereSql = whereClauses.length > 0 ? `WHERE ${whereClauses.join(' AND ')}` : ''

  const countRow = db.prepare(`
    SELECT COUNT(*) as total
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN companies c ON c.id = a.company_id
    ${whereSql}
  `).get(...params) as any

  const logs = db.prepare(`
    SELECT a.id, a.user_id, a.company_id, a.action, a.entity_type, a.entity_id,
           a.ip_address, a.user_agent, a.details, a.created_at,
           u.name as user_name, u.email as user_email,
           c.name as company_name
    FROM audit_logs a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN companies c ON c.id = a.company_id
    ${whereSql}
    ORDER BY a.created_at DESC
    LIMIT ? OFFSET ?
  `).all(...params, limit, offset) as any[]

  const formattedLogs = logs.map((log) => {
    let parsedDetails = {}
    try {
      if (log.details) parsedDetails = typeof log.details === 'string' ? JSON.parse(log.details) : log.details
    } catch (_) {}
    return {
      ...log,
      details: parsedDetails
    }
  })

  // Action options for filter dropdown
  const actions = db.prepare(`
    SELECT DISTINCT action FROM audit_logs ORDER BY action ASC
  `).all().map((r: any) => r.action)

  return {
    total: countRow ? countRow.total : 0,
    limit,
    offset,
    logs: formattedLogs,
    actions
  }
})

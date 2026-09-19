import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)

  const projectId = query.project_id ? String(query.project_id).trim() : ''
  const group = query.group ? String(query.group).trim() : ''
  const scope = query.scope ? String(query.scope).trim() : ''
  const search = query.search ? String(query.search).trim() : ''

  const whereClauses: string[] = []
  const params: any[] = []

  // Base accessibility check:
  if (!user.is_superadmin) {
    const userConditions: string[] = []

    // 1. Created by this user
    userConditions.push('c.user_id = ?')
    params.push(user.id)

    // 2. Shared company contacts
    if (user.company_id) {
      userConditions.push('(c.share_scope = "company" AND c.company_id = ?)')
      params.push(user.company_id)
    }

    // 3. Project-linked contacts where user has access
    let projectScopeSql = `(c.project_id IS NOT NULL AND c.project_id IN (
      SELECT p_acc.id FROM projects p_acc
      JOIN project_folders pf_acc ON pf_acc.id = p_acc.folder_id
      WHERE pf_acc.owner_id = ?
         OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p_acc.id AND pm.user_id = ?)
         OR EXISTS (SELECT 1 FROM folder_members fm WHERE fm.folder_id = pf_acc.id AND fm.user_id = ?)
    `
    params.push(user.id, user.id, user.id)

    if (user.company_id) {
      projectScopeSql += ` OR (pf_acc.company_id = ? AND (p_acc.visibility = "company" OR pf_acc.visibility = "company"))`
      params.push(user.company_id)
    }
    projectScopeSql += `))`

    userConditions.push(projectScopeSql)
    whereClauses.push(`(${userConditions.join(' OR ')})`)
  }

  // Filters
  if (projectId) {
    whereClauses.push('c.project_id = ?')
    params.push(projectId)
  }

  if (group) {
    whereClauses.push('c.category_group = ?')
    params.push(group)
  }

  if (scope) {
    whereClauses.push('c.share_scope = ?')
    params.push(scope)
  }

  if (search) {
    const sTerm = `%${search}%`
    whereClauses.push(`(
      c.first_name LIKE ? OR c.last_name LIKE ? OR c.company_name LIKE ? OR
      c.role_function LIKE ? OR c.email LIKE ? OR c.phone LIKE ? OR
      c.mobile LIKE ? OR c.notes LIKE ?
    )`)
    params.push(sTerm, sTerm, sTerm, sTerm, sTerm, sTerm, sTerm, sTerm)
  }

  let sql = `
    SELECT c.*,
           p.title AS project_title,
           pf.name AS folder_name,
           u.name AS creator_name
    FROM contacts c
    LEFT JOIN projects p ON p.id = c.project_id
    LEFT JOIN project_folders pf ON pf.id = p.folder_id
    LEFT JOIN users u ON u.id = c.user_id
  `
  if (whereClauses.length > 0) {
    sql += ` WHERE ${whereClauses.join(' AND ')}`
  }
  sql += ` ORDER BY c.last_name ASC, c.first_name ASC`

  const rows = db.prepare(sql).all(...params) as any[]

  const contacts = rows.map((r) => {
    let tags = []
    try {
      tags = typeof r.tags === 'string' ? JSON.parse(r.tags) : (r.tags || [])
    } catch {
      tags = []
    }
    const canEdit = Boolean(
      user.is_superadmin ||
      r.user_id === user.id ||
      (user.company_id && user.company_id === r.company_id && user.company_role === 'admin')
    )
    return {
      ...r,
      tags: Array.isArray(tags) ? tags : [],
      can_edit: canEdit
    }
  })

  return { contacts }
})

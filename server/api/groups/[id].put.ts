import { randomUUID } from 'crypto'
import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const groupId = getRouterParam(event, 'id')
  const body = await readBody(event).catch(() => ({}))

  const group = db.prepare('SELECT * FROM user_groups WHERE id = ?').get(groupId) as any
  if (!group) {
    throw createError({ statusCode: 404, statusMessage: 'Gruppe nicht gefunden' })
  }

  // Berechtigung: Entweder Gruppen-Ersteller oder Company Admin
  const isOwner = group.owner_id === user.id
  const isCompanyAdmin = user.company_id && user.company_id === group.company_id && user.company_role === 'admin'
  if (!isOwner && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Bearbeiten dieser Gruppe' })
  }

  const name = body?.name?.trim() || group.name
  const description = body?.description !== undefined ? body.description?.trim() || null : group.description
  const color = body?.color?.trim() || group.color

  db.prepare(`
    UPDATE user_groups
    SET name = ?, description = ?, color = ?
    WHERE id = ?
  `).run(name, description, color, groupId)

  // Mitglieder synchronisieren falls übergeben
  if (Array.isArray(body?.member_ids)) {
    db.prepare('DELETE FROM user_group_members WHERE group_id = ?').run(groupId)
    const insertMember = db.prepare(`
      INSERT OR IGNORE INTO user_group_members (id, group_id, user_id)
      VALUES (?, ?, ?)
    `)
    for (const mid of body.member_ids) {
      if (typeof mid === 'string' && mid.trim()) {
        const memberLinkId = 'ugm_' + randomUUID().substring(0, 8)
        insertMember.run(memberLinkId, groupId, mid.trim())
      }
    }
  }

  const updatedGroup = db.prepare(`
    SELECT ug.*, u.name as owner_name, u.email as owner_email
    FROM user_groups ug
    JOIN users u ON u.id = ug.owner_id
    WHERE ug.id = ?
  `).get(groupId) as any

  const members = db.prepare(`
    SELECT u.id as user_id, u.name, u.email, ugm.created_at
    FROM user_group_members ugm
    JOIN users u ON u.id = ugm.user_id
    WHERE ugm.group_id = ?
    ORDER BY u.name ASC
  `).all(groupId)

  const folders = db.prepare(`
    SELECT pf.id as folder_id, pf.name as folder_name, fga.role
    FROM folder_group_access fga
    JOIN project_folders pf ON pf.id = fga.folder_id
    WHERE fga.group_id = ?
  `).all(groupId)

  const projects = db.prepare(`
    SELECT p.id as project_id, p.title as project_title, pga.role
    FROM project_group_access pga
    JOIN projects p ON p.id = pga.project_id
    WHERE pga.group_id = ?
  `).all(groupId)

  return {
    group: {
      ...updatedGroup,
      members,
      folders,
      projects
    }
  }
})

import { randomUUID } from 'crypto'
import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event).catch(() => ({}))

  const name = body?.name?.trim()
  if (!name) {
    throw createError({ statusCode: 400, statusMessage: 'Name der Gruppe ist erforderlich' })
  }

  const description = body?.description?.trim() || null
  const color = body?.color?.trim() || '#0891B2'
  const memberIds: string[] = Array.isArray(body?.member_ids) ? body.member_ids : []

  const groupId = 'grp_' + randomUUID().substring(0, 8)
  const companyId = user.company_id || null

  db.prepare(`
    INSERT INTO user_groups (id, owner_id, company_id, name, description, color)
    VALUES (?, ?, ?, ?, ?, ?)
  `).run(groupId, user.id, companyId, name, description, color)

  // Mitglieder einfügen
  const insertMember = db.prepare(`
    INSERT OR IGNORE INTO user_group_members (id, group_id, user_id)
    VALUES (?, ?, ?)
  `)

  for (const mid of memberIds) {
    if (typeof mid === 'string' && mid.trim()) {
      const memberLinkId = 'ugm_' + randomUUID().substring(0, 8)
      insertMember.run(memberLinkId, groupId, mid.trim())
    }
  }

  const group = db.prepare(`
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

  return {
    group: {
      ...group,
      members,
      folders: [],
      projects: []
    }
  }
})

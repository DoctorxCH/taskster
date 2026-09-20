import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)

  // Gruppen laden: Entweder vom eigenen Unternehmen oder vom Nutzer selbst erstellt (Free-Plan)
  let groupsQuery = `
    SELECT ug.*, u.name as owner_name, u.email as owner_email
    FROM user_groups ug
    JOIN users u ON u.id = ug.owner_id
    WHERE ug.owner_id = ?
  `
  const params: any[] = [user.id]

  if (user.company_id) {
    groupsQuery = `
      SELECT ug.*, u.name as owner_name, u.email as owner_email
      FROM user_groups ug
      JOIN users u ON u.id = ug.owner_id
      WHERE ug.owner_id = ? OR ug.company_id = ?
      ORDER BY ug.name ASC
    `
    params.push(user.company_id)
  }

  const rawGroups = db.prepare(groupsQuery).all(...params) as any[]

  // Details für jede Gruppe laden (Mitglieder, zugewiesene Ordner & Projekte)
  const groups = rawGroups.map((g) => {
    const members = db.prepare(`
      SELECT u.id as user_id, u.name, u.email, ugm.created_at
      FROM user_group_members ugm
      JOIN users u ON u.id = ugm.user_id
      WHERE ugm.group_id = ?
      ORDER BY u.name ASC
    `).all(g.id)

    const folders = db.prepare(`
      SELECT pf.id as folder_id, pf.name as folder_name, fga.role
      FROM folder_group_access fga
      JOIN project_folders pf ON pf.id = fga.folder_id
      WHERE fga.group_id = ?
    `).all(g.id)

    const projects = db.prepare(`
      SELECT p.id as project_id, p.title as project_title, pga.role
      FROM project_group_access pga
      JOIN projects p ON p.id = pga.project_id
      WHERE pga.group_id = ?
    `).all(g.id)

    return {
      ...g,
      members,
      folders,
      projects
    }
  })

  return { groups }
})

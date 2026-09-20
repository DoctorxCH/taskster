import { db } from '../../db'
import { requireAuth } from '../../utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const companyId = user.company_id || null

  // 1. Relevante Ordner und Projekte ermitteln (eigenes Eigentum oder eigene Firma)
  let foldersQuery = `
    SELECT pf.id, pf.name, pf.icon, pf.visibility, pf.owner_id, u.name as owner_name
    FROM project_folders pf
    JOIN users u ON u.id = pf.owner_id
    WHERE pf.owner_id = ?
  `
  const folderParams: any[] = [user.id]

  if (companyId) {
    foldersQuery = `
      SELECT pf.id, pf.name, pf.icon, pf.visibility, pf.owner_id, u.name as owner_name
      FROM project_folders pf
      JOIN users u ON u.id = pf.owner_id
      WHERE pf.owner_id = ? OR pf.company_id = ?
      ORDER BY pf.name ASC
    `
    folderParams.push(companyId)
  }

  const folders = db.prepare(foldersQuery).all(...folderParams) as any[]
  const folderIds = folders.map(f => f.id)

  let projects: any[] = []
  if (folderIds.length > 0) {
    const placeholders = folderIds.map(() => '?').join(',')
    projects = db.prepare(`
      SELECT p.id, p.folder_id, p.title, p.status, p.visibility, pf.owner_id
      FROM projects p
      JOIN project_folders pf ON pf.id = p.folder_id
      WHERE p.folder_id IN (${placeholders})
      ORDER BY p.title ASC
    `).all(...folderIds) as any[]
  }

  // 2. Benutzer sammeln (Firmenmitglieder + direkte Kollaborateure in Ordnern/Projekten)
  const userMap = new Map<string, any>()

  // Eigener User immer drin
  const self = db.prepare('SELECT id, name, email, company_id, company_role, created_at FROM users WHERE id = ?').get(user.id) as any
  userMap.set(self.id, { ...self, is_self: true })

  if (companyId) {
    const companyUsers = db.prepare(`
      SELECT id, name, email, company_id, company_role, created_at
      FROM users
      WHERE company_id = ?
    `).all(companyId) as any[]
    for (const u of companyUsers) {
      userMap.set(u.id, { ...u, is_self: u.id === user.id })
    }
  }

  // Kollaborateure aus geteilten Ordnern/Projekten
  if (folderIds.length > 0) {
    const placeholders = folderIds.map(() => '?').join(',')
    const folderCollabs = db.prepare(`
      SELECT u.id, u.name, u.email, u.company_id, u.company_role, u.created_at
      FROM folder_members fm
      JOIN users u ON u.id = fm.user_id
      WHERE fm.folder_id IN (${placeholders})
    `).all(...folderIds) as any[]
    for (const u of folderCollabs) {
      if (!userMap.has(u.id)) {
        userMap.set(u.id, { ...u, is_self: u.id === user.id })
      }
    }

    const projectCollabs = db.prepare(`
      SELECT u.id, u.name, u.email, u.company_id, u.company_role, u.created_at
      FROM project_members pm
      JOIN projects p ON p.id = pm.project_id
      JOIN users u ON u.id = pm.user_id
      WHERE p.folder_id IN (${placeholders})
    `).all(...folderIds) as any[]
    for (const u of projectCollabs) {
      if (!userMap.has(u.id)) {
        userMap.set(u.id, { ...u, is_self: u.id === user.id })
      }
    }
  }

  // 3. Offene Einladungen
  let invitations: any[] = []
  if (companyId) {
    invitations = db.prepare(`
      SELECT ci.id, ci.email, ci.role, ci.token, ci.status, ci.created_at, u.name as invited_by_name
      FROM company_invitations ci
      LEFT JOIN users u ON u.id = ci.invited_by
      WHERE ci.company_id = ?
      ORDER BY ci.created_at DESC
    `).all(companyId) as any[]
  }

  // 4. Gruppen & Gruppenmitgliedschaften
  let groupsQuery = 'SELECT id, name, color, owner_id, company_id FROM user_groups WHERE owner_id = ?'
  const groupParams: any[] = [user.id]
  if (companyId) {
    groupsQuery = 'SELECT id, name, color, owner_id, company_id FROM user_groups WHERE owner_id = ? OR company_id = ?'
    groupParams.push(companyId)
  }
  const groups = db.prepare(groupsQuery).all(...groupParams) as any[]
  const groupIds = groups.map(g => g.id)

  const groupMemberships: Record<string, string[]> = {}
  if (groupIds.length > 0) {
    const placeholders = groupIds.map(() => '?').join(',')
    const rows = db.prepare(`
      SELECT group_id, user_id
      FROM user_group_members
      WHERE group_id IN (${placeholders})
    `).all(...groupIds) as any[]
    for (const r of rows) {
      if (!groupMemberships[r.user_id]) groupMemberships[r.user_id] = []
      groupMemberships[r.user_id].push(r.group_id)
    }
  }

  // 5. Direkte Rechte & Gruppenrechte für Ordner und Projekte laden
  const directFolderMembers = db.prepare(`
    SELECT folder_id, user_id, role FROM folder_members
  `).all() as any[]
  const directProjectMembers = db.prepare(`
    SELECT project_id, user_id, role FROM project_members
  `).all() as any[]

  const folderGroupAccess = db.prepare(`
    SELECT folder_id, group_id, role FROM folder_group_access
  `).all() as any[]
  const projectGroupAccess = db.prepare(`
    SELECT project_id, group_id, role FROM project_group_access
  `).all() as any[]

  const ROLE_RANK: Record<string, number> = { viewer: 1, editor: 2, admin: 3, owner: 4 }

  // 6. Berechnete Zugriffsmatrix pro Benutzer
  const teamMembers = Array.from(userMap.values()).map(u => {
    const userGroupIds = groupMemberships[u.id] || []
    const userGroups = groups.filter(g => userGroupIds.includes(g.id))

    // Effektive Rechte auf alle Ordner berechnen
    const folderAccess: Record<string, { role: string; source: 'owner' | 'direct' | 'group' | 'company' | 'none' }> = {}
    for (const f of folders) {
      if (f.owner_id === u.id) {
        folderAccess[f.id] = { role: 'owner', source: 'owner' }
        continue
      }
      let bestRole: string = 'none'
      let bestRank = 0
      let source: any = 'none'

      // Direkt
      const dm = directFolderMembers.find(m => m.folder_id === f.id && m.user_id === u.id)
      if (dm && (ROLE_RANK[dm.role] || 0) > bestRank) {
        bestRank = ROLE_RANK[dm.role]
        bestRole = dm.role
        source = 'direct'
      }

      // Über Gruppen
      for (const gid of userGroupIds) {
        const ga = folderGroupAccess.find(a => a.folder_id === f.id && a.group_id === gid)
        if (ga && (ROLE_RANK[ga.role] || 0) > bestRank) {
          bestRank = ROLE_RANK[ga.role]
          bestRole = ga.role
          source = 'group'
        }
      }

      // Über Company-Sichtbarkeit
      if (bestRank === 0 && companyId && u.company_id === companyId && f.visibility === 'company') {
        bestRole = 'editor'
        source = 'company'
      }

      folderAccess[f.id] = { role: bestRole, source }
    }

    // Effektive Rechte auf alle Projekte berechnen
    const projectAccess: Record<string, { role: string; source: 'owner' | 'direct' | 'group' | 'folder' | 'company' | 'none' }> = {}
    for (const p of projects) {
      if (p.owner_id === u.id) {
        projectAccess[p.id] = { role: 'owner', source: 'owner' }
        continue
      }
      let bestRole: string = 'none'
      let bestRank = 0
      let source: any = 'none'

      // Direktes Projektmitglied
      const pm = directProjectMembers.find(m => m.project_id === p.id && m.user_id === u.id)
      if (pm && (ROLE_RANK[pm.role] || 0) > bestRank) {
        bestRank = ROLE_RANK[pm.role]
        bestRole = pm.role
        source = 'direct'
      }

      // Über Projekt-Gruppen
      for (const gid of userGroupIds) {
        const pga = projectGroupAccess.find(a => a.project_id === p.id && a.group_id === gid)
        if (pga && (ROLE_RANK[pga.role] || 0) > bestRank) {
          bestRank = ROLE_RANK[pga.role]
          bestRole = pga.role
          source = 'group'
        }
      }

      // Vererbung vom übergeordneten Ordner
      const parentFolderAccess = folderAccess[p.folder_id]
      if (parentFolderAccess && parentFolderAccess.role !== 'none' && (ROLE_RANK[parentFolderAccess.role] || 0) > bestRank) {
        bestRank = ROLE_RANK[parentFolderAccess.role]
        bestRole = parentFolderAccess.role
        source = 'folder'
      }

      // Über Company-Sichtbarkeit
      if (bestRank === 0 && companyId && u.company_id === companyId && (p.visibility === 'company' || folders.find(f => f.id === p.folder_id)?.visibility === 'company')) {
        bestRole = 'editor'
        source = 'company'
      }

      projectAccess[p.id] = { role: bestRole, source }
    }

    return {
      ...u,
      groups: userGroups,
      folder_access: folderAccess,
      project_access: projectAccess
    }
  })

  return {
    members: teamMembers,
    invitations,
    folders,
    projects,
    groups
  }
})

import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/projects
 * Liefert alle für den Benutzer sichtbaren Projekte inkl. Ordner-Metadaten.
 * Zero-Trust: Nur eigene, zugewiesene oder firmensichtbare Projekte.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event) as any

  const companyId = user.company_id || '__none__'
  const projects = db.prepare(`
    SELECT p.id, p.title, p.folder_id, p.currency, p.status, p.is_default,
           pf.name AS folder_name, pf.icon AS folder_icon, pf.owner_id
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.owner_id = ?
       OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)
       OR pf.id IN (SELECT folder_id FROM folder_members WHERE user_id = ?)
       OR (p.visibility = 'company' AND pf.company_id = ?)
       OR (pf.visibility = 'company' AND pf.company_id = ?)
    GROUP BY p.id
    ORDER BY p.title ASC
  `).all(user.id, user.id, user.id, companyId, companyId)

  return { projects }
})
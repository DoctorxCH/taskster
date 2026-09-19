import { db } from '~/server/db'
import { requireCompanyAdmin, resolveCompanyId } from '~/server/utils/company'

/**
 * GET /api/company/templates
 * Liefert ausschließlich die firmeneigenen Vorlagen (is_system = 0).
 */
export default defineEventHandler((event) => {
  const user = requireCompanyAdmin(event)
  const companyId = resolveCompanyId(user, getQuery(event).company_id as string | undefined)

  const rows = db.prepare(`
    SELECT * FROM project_templates
    WHERE company_id = ? AND is_system = 0
    ORDER BY category ASC, name ASC
  `).all(companyId) as any[]

  const templates = rows.map((t) => ({
    ...t,
    lists: t.lists ? JSON.parse(t.lists) : [],
    fields: t.fields ? JSON.parse(t.fields) : []
  }))

  return { templates }
})
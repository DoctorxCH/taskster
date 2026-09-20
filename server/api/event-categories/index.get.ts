import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/event-categories
 * Liefert System-Kategorien + eigene Firmen-/Nutzer-Kategorien.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const companyId = user.company_id || '__none__'

  const categories = db.prepare(`
    SELECT * FROM event_categories
    WHERE is_system = 1
       OR (company_id IS NOT NULL AND company_id = ?)
       OR owner_id = ?
    ORDER BY is_system DESC, sort_order ASC, name ASC
  `).all(companyId, user.id)

  return { categories }
})

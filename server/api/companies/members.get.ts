import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/companies/members
 * Listet alle Mitarbeiter des eigenen Unternehmens.
 *
 * Jeder authentifizierte Benutzer darf die Mitglieder seiner eigenen Firma
 * sehen — das ist nötig für Zuweisungen und Termineinladungen (der Kalender
 * nutzt diese Route für die "Aus dem Team"-Vorschläge).
 * Zero-Trust: Firmenfremde Mitglieder werden nie zurückgegeben.
 */
export default defineEventHandler((event) => {
  const user = requireAuth(event)

  const companyId = user.company_id || (getQuery(event).company_id as string | undefined)
  if (!companyId) {
    return { members: [] }
  }

  const members = db.prepare(`
    SELECT id, name, email, company_role, is_pro, created_at
    FROM users
    WHERE company_id = ?
    ORDER BY (company_role = 'admin') DESC, name ASC
  `).all(companyId)

  return { members }
})
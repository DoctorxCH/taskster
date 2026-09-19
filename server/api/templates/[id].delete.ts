import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin'

  if (!isSuperadmin && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Administratoren können Vorlagen löschen' })
  }

  const id = getRouterParam(event, 'id')
  const existing = db.prepare('SELECT * FROM project_templates WHERE id = ?').get(id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Vorlage nicht gefunden' })
  }

  // Zero-Trust: Company Admin darf nur eigene Firmenvorlagen löschen
  if (!isSuperadmin) {
    if (Number(existing.is_system) === 1 || existing.company_id !== user.company_id) {
      throw createError({ statusCode: 404, statusMessage: 'Vorlage nicht gefunden' })
    }
  }

  db.prepare('DELETE FROM project_templates WHERE id = ?').run(id)

  return { success: true }
})

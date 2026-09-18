import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  if (!user.is_superadmin && user.company_role !== 'admin') {
    throw createError({ statusCode: 403, statusMessage: 'Nur Administratoren können Vorlagen löschen' })
  }

  const id = getRouterParam(event, 'id')
  db.prepare('DELETE FROM project_templates WHERE id = ?').run(id)

  return { success: true }
})

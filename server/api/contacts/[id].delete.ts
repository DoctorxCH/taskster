import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = event.context.params?.id

  if (!id) {
    throw createError({ statusCode: 400, statusMessage: 'Kontakt-ID erforderlich' })
  }

  const existing = db.prepare('SELECT * FROM contacts WHERE id = ?').get(id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Kontakt nicht gefunden' })
  }

  const canDelete = Boolean(
    user.is_superadmin ||
    existing.user_id === user.id ||
    (user.company_id && user.company_id === existing.company_id && user.company_role === 'admin')
  )
  if (!canDelete) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Löschen dieses Kontakts' })
  }

  db.prepare('DELETE FROM contacts WHERE id = ?').run(id)

  return { success: true }
})

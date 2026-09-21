import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const categoryId = getRouterParam(event, 'id')

  const existing = db.prepare('SELECT owner_id, company_id, is_system FROM event_categories WHERE id = ?').get(categoryId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Kategorie nicht gefunden' })
  }

  if (existing.is_system) {
    throw createError({ statusCode: 403, statusMessage: 'Systemkategorien können nicht gelöscht werden' })
  }

  if (existing.company_id) {
    if (existing.company_id !== user.company_id || user.company_role !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
    }
  }

  if (!existing.company_id && existing.owner_id !== user.id) {
     throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
  }

  // Check if events are using this category
  const eventsCount = db.prepare('SELECT COUNT(*) as c FROM calendar_events WHERE category_id = ?').get(categoryId) as any
  if (eventsCount && eventsCount.c > 0) {
    throw createError({ statusCode: 400, statusMessage: 'Kategorie wird noch verwendet und kann nicht gelöscht werden' })
  }

  db.prepare('DELETE FROM event_categories WHERE id = ?').run(categoryId)

  return { success: true }
})

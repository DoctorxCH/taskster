import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const categoryId = getRouterParam(event, 'id')

  const existing = db.prepare('SELECT owner_id, company_id FROM event_categories WHERE id = ?').get(categoryId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Kategorie nicht gefunden' })
  }

  if (existing.company_id && (existing.company_id !== user.company_id || user.company_role !== 'admin')) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
  }

  if (!existing.company_id && existing.owner_id && existing.owner_id !== user.id) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
  }

  // Unlink events from category instead of failing
  db.prepare('UPDATE calendar_events SET category_id = NULL WHERE category_id = ?').run(categoryId)
  db.prepare('DELETE FROM event_categories WHERE id = ?').run(categoryId)

  return { success: true }
})

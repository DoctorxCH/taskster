import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * POST /api/event-categories
 * Body: { name, color, icon?, company_wide? }
 *
 * Erstellt eine eigene Kategorie (persönlich oder für die ganze Firma).
 */
export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const name = String(body?.name || '').trim()
  if (!name) throw createError({ statusCode: 400, statusMessage: 'Kategoriename erforderlich' })

  const color = /^#[0-9A-Fa-f]{6}$/.test(body?.color) ? body.color : '#0891B2'
  const icon = String(body?.icon || 'Calendar').slice(0, 40)
  const companyWide = Boolean(body?.company_wide) && Boolean(user.company_id)

  const id = 'cat_' + Math.random().toString(36).slice(2, 10)
  const maxSort = (db.prepare('SELECT COALESCE(MAX(sort_order), 0) as m FROM event_categories').get() as any).m

  db.prepare(`
    INSERT INTO event_categories (id, company_id, owner_id, name, color, icon, is_system, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, 0, ?)
  `).run(
    id,
    companyWide ? user.company_id : null,
    companyWide ? null : user.id,
    name, color, icon, maxSort + 1
  )

  return { success: true, id }
})

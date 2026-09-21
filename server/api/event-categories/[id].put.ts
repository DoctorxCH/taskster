import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const categoryId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const name = String(body?.name || '').trim()
  const color = /^#[0-9A-Fa-f]{6}$/.test(body?.color) ? body.color : '#0891B2'
  const companyWide = Boolean(body?.company_wide) && Boolean(user.company_id)

  if (!name) throw createError({ statusCode: 400, statusMessage: 'Kategoriename erforderlich' })

  const existing = db.prepare('SELECT owner_id, company_id, is_system FROM event_categories WHERE id = ?').get(categoryId) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Kategorie nicht gefunden' })
  }

  if (existing.is_system) {
    throw createError({ statusCode: 403, statusMessage: 'Systemkategorien können nicht bearbeitet werden' })
  }

  if (existing.company_id) {
    if (existing.company_id !== user.company_id || user.company_role !== 'admin') {
      throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
    }
  }

  if (!existing.company_id && existing.owner_id !== user.id) {
     throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung' })
  }

  db.prepare(`
    UPDATE event_categories
    SET name = ?, color = ?, company_id = ?, owner_id = ?
    WHERE id = ?
  `).run(
    name,
    color,
    companyWide ? user.company_id : null,
    companyWide ? null : user.id,
    categoryId
  )

  return { success: true }
})

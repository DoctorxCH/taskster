import { db } from '~/server/db'
import {
  requireCompanyAdmin,
  resolveCompanyId,
  getCompanyOr404
} from '~/server/utils/company'

/**
 * PATCH /api/company/details
 * Aktualisiert Firmenname und/oder Zero-Trust-Policies (settings).
 */
export default defineEventHandler(async (event) => {
  const user = requireCompanyAdmin(event)
  const body = await readBody(event)
  const companyId = resolveCompanyId(user, body?.company_id)
  const company = getCompanyOr404(companyId)

  const fields: string[] = []
  const params: any[] = []

  if (typeof body?.name === 'string') {
    const name = body.name.trim()
    if (!name) {
      throw createError({ statusCode: 400, statusMessage: 'Firmenname darf nicht leer sein' })
    }
    fields.push('name = ?')
    params.push(name)
  }

  if (body?.settings && typeof body.settings === 'object') {
    const merged = { ...company.settings, ...body.settings }
    fields.push('settings = ?')
    params.push(JSON.stringify(merged))
  }

  if (fields.length === 0) {
    throw createError({ statusCode: 400, statusMessage: 'Keine Änderungen übergeben' })
  }

  params.push(companyId)
  db.prepare(`UPDATE companies SET ${fields.join(', ')} WHERE id = ?`).run(...params)

  return { success: true }
})
import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  if (!user.is_superadmin && user.company_role !== 'admin') {
    throw createError({ statusCode: 403, statusMessage: 'Nur Administratoren können Vorlagen bearbeiten' })
  }

  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const existing = db.prepare('SELECT * FROM project_templates WHERE id = ?').get(id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Vorlage nicht gefunden' })
  }

  const name = body.name !== undefined ? body.name.trim() : existing.name
  const category = body.category !== undefined ? (body.category === 'private' ? 'private' : 'job') : existing.category
  const subcategory = body.subcategory !== undefined ? body.subcategory : existing.subcategory
  const description = body.description !== undefined ? body.description : existing.description
  const icon = body.icon !== undefined ? body.icon : existing.icon
  const lists = body.lists !== undefined ? JSON.stringify(body.lists) : existing.lists
  const fields = body.fields !== undefined ? JSON.stringify(body.fields) : existing.fields

  db.prepare(`
    UPDATE project_templates
    SET name = ?, category = ?, subcategory = ?, description = ?, icon = ?, lists = ?, fields = ?, updated_at = datetime('now')
    WHERE id = ?
  `).run(name, category, subcategory, description, icon, lists, fields, id)

  return { success: true }
})

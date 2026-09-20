import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  requireAdminPermission(event, 'any_admin')
  const id = getRouterParam(event, 'id')
  const body = await readBody(event)

  const { name, subject, body_html, body_text, is_active } = body

  if (!subject) {
    throw createError({ statusCode: 400, statusMessage: 'Betreff ist erforderlich' })
  }

  const existing = db.prepare('SELECT * FROM email_templates WHERE id = ?').get(id) as any
  if (!existing) {
    throw createError({ statusCode: 404, statusMessage: 'Vorlage nicht gefunden' })
  }

  db.prepare(`
    UPDATE email_templates
    SET name = ?, subject = ?, body_html = ?, body_text = ?, is_active = ?, updated_at = datetime('now')
    WHERE id = ?
  `).run(
    name !== undefined ? String(name).trim() : existing.name,
    String(subject).trim(),
    body_html !== undefined ? String(body_html) : existing.body_html,
    body_text !== undefined ? String(body_text) : existing.body_text,
    is_active !== undefined ? (is_active ? 1 : 0) : existing.is_active,
    id
  )

  const updated = db.prepare('SELECT * FROM email_templates WHERE id = ?').get(id) as any

  return {
    success: true,
    template: {
      ...updated,
      is_active: Boolean(updated.is_active),
      variables: typeof updated.variables === 'string' ? JSON.parse(updated.variables || '[]') : updated.variables
    }
  }
})

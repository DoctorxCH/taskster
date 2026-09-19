import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { randomUUID } from 'crypto'

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin'

  if (!isSuperadmin && !isCompanyAdmin) {
    throw createError({ statusCode: 403, statusMessage: 'Nur Administratoren können Vorlagen anlegen' })
  }

  const body = await readBody(event)
  const { name, category, subcategory, description, icon, lists, fields } = body

  if (!name || !name.trim()) {
    throw createError({ statusCode: 400, statusMessage: 'Vorlagenname ist erforderlich' })
  }

  const tmplId = 'tmpl_' + randomUUID().substring(0, 8)
  db.prepare(`
    INSERT INTO project_templates (id, name, category, subcategory, description, icon, is_system, company_id, lists, fields)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
  `).run(
    tmplId,
    name.trim(),
    category === 'private' ? 'private' : 'job',
    subcategory || null,
    description || null,
    icon || 'Folder',
    isSuperadmin ? 1 : 0,
    isSuperadmin ? null : user.company_id,
    JSON.stringify(lists || []),
    JSON.stringify(fields || [])
  )

  return { success: true, id: tmplId }
})

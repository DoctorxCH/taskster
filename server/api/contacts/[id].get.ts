import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = event.context.params?.id

  if (!id) {
    throw createError({ statusCode: 400, statusMessage: 'Kontakt-ID erforderlich' })
  }

  const contact = db.prepare(`
    SELECT c.*,
           p.title AS project_title,
           pf.name AS folder_name,
           u.name AS creator_name
    FROM contacts c
    LEFT JOIN projects p ON p.id = c.project_id
    LEFT JOIN project_folders pf ON pf.id = p.folder_id
    LEFT JOIN users u ON u.id = c.user_id
    WHERE c.id = ?
  `).get(id) as any

  if (!contact) {
    throw createError({ statusCode: 404, statusMessage: 'Kontakt nicht gefunden' })
  }

  let hasAccess = false
  if (user.is_superadmin || contact.user_id === user.id) {
    hasAccess = true
  } else if (user.company_id && contact.share_scope === 'company' && user.company_id === contact.company_id) {
    hasAccess = true
  } else if (contact.project_id) {
    try {
      evaluateProjectAccess(user, contact.project_id, event, 'read')
      hasAccess = true
    } catch {
      // not accessible
    }
  }

  if (!hasAccess) {
    throw createError({ statusCode: 404, statusMessage: 'Kontakt nicht gefunden' })
  }

  let parsedTags = []
  try {
    parsedTags = JSON.parse(contact.tags || '[]')
  } catch {
    parsedTags = []
  }

  const canEdit = Boolean(
    user.is_superadmin ||
    contact.user_id === user.id ||
    (user.company_id && user.company_id === contact.company_id && user.company_role === 'admin')
  )

  return {
    contact: {
      ...contact,
      tags: parsedTags,
      can_edit: canEdit
    }
  }
})

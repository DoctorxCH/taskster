import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAuth(event)
  const id = getRouterParam(event, 'id')

  const tmpl = db.prepare('SELECT * FROM project_templates WHERE id = ?').get(id) as any
  if (!tmpl) {
    throw createError({ statusCode: 404, statusMessage: 'Vorlage nicht gefunden' })
  }

  return {
    template: {
      ...tmpl,
      lists: tmpl.lists ? JSON.parse(tmpl.lists) : [],
      fields: tmpl.fields ? JSON.parse(tmpl.fields) : []
    }
  }
})

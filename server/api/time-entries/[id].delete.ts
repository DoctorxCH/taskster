import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const id = getRouterParam(event, 'id')

  const entry = db.prepare('SELECT * FROM time_entries WHERE id = ?').get(id) as any
  if (!entry) {
    throw createError({ statusCode: 404, statusMessage: 'Zeiteintrag nicht gefunden' })
  }

  // Permission: author or project editor/owner
  const context = evaluateProjectAccess(user, entry.project_id, event, 'write')
  const isAuthor = entry.user_id === user.id
  const isElevated = context.userRole === 'owner' || context.userRole === 'admin'

  if (!isAuthor && !isElevated) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Löschen dieses Eintrags' })
  }

  db.prepare('DELETE FROM time_entries WHERE id = ?').run(id)

  return { success: true }
})

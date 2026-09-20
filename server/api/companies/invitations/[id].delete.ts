import { db } from '../../../db'
import { requireAuth } from '../../../utils/auth'

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const inviteId = getRouterParam(event, 'id')

  const invite = db.prepare('SELECT * FROM company_invitations WHERE id = ?').get(inviteId) as any
  if (!invite) {
    throw createError({ statusCode: 404, statusMessage: 'Einladung nicht gefunden' })
  }

  const isSuperadmin = Boolean(user.is_superadmin)
  const isCompanyAdmin = Boolean(user.company_id) && user.company_role === 'admin' && user.company_id === invite.company_id
  const isInviter = invite.invited_by === user.id

  if (!isSuperadmin && !isCompanyAdmin && !isInviter) {
    throw createError({ statusCode: 403, statusMessage: 'Keine Berechtigung zum Widerrufen dieser Einladung' })
  }

  db.prepare('DELETE FROM company_invitations WHERE id = ?').run(inviteId)

  return { success: true, message: 'Einladung wurde erfolgreich widerrufen.' }
})

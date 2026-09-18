import { db } from '~/server/db'
import { requireSuperadmin } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  requireSuperadmin(event)
  const userId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const user = db.prepare('SELECT * FROM users WHERE id = ?').get(userId) as any
  if (!user) {
    throw createError({ statusCode: 404, statusMessage: 'Benutzer nicht gefunden' })
  }

  const { is_pro, is_superadmin, company_id, company_role } = body

  const newIsPro = is_pro !== undefined ? (is_pro ? 1 : 0) : user.is_pro
  const newIsSuper = is_superadmin !== undefined ? (is_superadmin ? 1 : 0) : user.is_superadmin
  const newCompId = company_id !== undefined ? company_id : user.company_id
  const newCompRole = company_role !== undefined ? company_role : user.company_role

  db.prepare(`
    UPDATE users
    SET is_pro = ?, is_superadmin = ?, company_id = ?, company_role = ?
    WHERE id = ?
  `).run(newIsPro, newIsSuper, newCompId, newCompRole, userId)

  return { success: true }
})

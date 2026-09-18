import { db } from '~/server/db'
import { requireSuperadmin } from '~/server/utils/auth'

export default defineEventHandler(async (event) => {
  requireSuperadmin(event)
  const companyId = getRouterParam(event, 'id')
  const body = await readBody(event)

  const company = db.prepare('SELECT * FROM companies WHERE id = ?').get(companyId) as any
  if (!company) {
    throw createError({ statusCode: 404, statusMessage: 'Unternehmen nicht gefunden' })
  }

  const { name, subscription_plan, settings } = body

  const newName = name !== undefined ? name.trim() : company.name
  const newPlan = subscription_plan !== undefined ? subscription_plan : company.subscription_plan
  const newSettings = settings !== undefined ? JSON.stringify(settings) : company.settings

  db.prepare(`
    UPDATE companies
    SET name = ?, subscription_plan = ?, settings = ?
    WHERE id = ?
  `).run(newName, newPlan, newSettings, companyId)

  return { success: true }
})

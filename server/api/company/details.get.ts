import { db } from '~/server/db'
import {
  requireCompanyAdmin,
  resolveCompanyId,
  getCompanyOr404,
  PLAN_PRICES
} from '~/server/utils/company'

/**
 * GET /api/company/details
 * Liefert Firmendaten, Plan, Statistiken und die letzten Anfragen.
 */
export default defineEventHandler((event) => {
  const user = requireCompanyAdmin(event)
  const companyId = resolveCompanyId(user, getQuery(event).company_id as string | undefined)
  const company = getCompanyOr404(companyId)

  const memberCount = (db.prepare('SELECT COUNT(*) as c FROM users WHERE company_id = ?')
    .get(companyId) as any).c
  const adminCount = (db.prepare("SELECT COUNT(*) as c FROM users WHERE company_id = ? AND company_role = 'admin'")
    .get(companyId) as any).c
  const projectCount = (db.prepare(`
    SELECT COUNT(*) as c FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.company_id = ?
  `).get(companyId) as any).c
  const taskCount = (db.prepare(`
    SELECT COUNT(*) as c FROM tasks t
    JOIN lists l ON l.id = t.list_id
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.company_id = ?
  `).get(companyId) as any).c
  const templateCount = (db.prepare('SELECT COUNT(*) as c FROM project_templates WHERE company_id = ? AND is_system = 0')
    .get(companyId) as any).c

  const planKey = String(company.subscription_plan || 'starter').toLowerCase()
  const plan = PLAN_PRICES[planKey] ?? { name: planKey, monthly: 0, seats: 10 }
  const maxSeats = Number(company.settings?.max_seats ?? plan.seats)

  return {
    company: {
      id: company.id,
      name: company.name,
      subscription_plan: planKey,
      plan_name: plan.name,
      plan_monthly: plan.monthly,
      currency: 'CHF',
      settings: company.settings,
      created_at: company.created_at
    },
    stats: {
      members: Number(memberCount),
      admins: Number(adminCount),
      projects: Number(projectCount),
      tasks: Number(taskCount),
      templates: Number(templateCount),
      max_seats: maxSeats,
      seats_used: Number(memberCount)
    },
    upgrade_requests: (company.settings?.upgrade_requests ?? []).slice(-10).reverse(),
    support_tickets: (company.settings?.support_tickets ?? []).slice(-10).reverse()
  }
})
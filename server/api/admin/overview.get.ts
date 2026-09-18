import { db } from '~/server/db'
import { requireSuperadmin } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireSuperadmin(event)

  const userCount = (db.prepare('SELECT COUNT(*) as c FROM users').get() as any).c
  const companyCount = (db.prepare('SELECT COUNT(*) as c FROM companies').get() as any).c
  const projectCount = (db.prepare('SELECT COUNT(*) as c FROM projects').get() as any).c
  const taskCount = (db.prepare('SELECT COUNT(*) as c FROM tasks').get() as any).c
  const journalCount = (db.prepare('SELECT COUNT(*) as c FROM project_journals').get() as any).c

  const planStats = db.prepare(`
    SELECT subscription_plan, COUNT(*) as count
    FROM companies
    GROUP BY subscription_plan
  `).all()

  const recentUsers = db.prepare(`
    SELECT u.id, u.name, u.email, u.company_role, u.is_pro, u.is_superadmin, u.created_at,
           c.name as company_name, c.subscription_plan as company_plan
    FROM users u
    LEFT JOIN companies c ON c.id = u.company_id
    ORDER BY u.created_at DESC
    LIMIT 10
  `).all()

  return {
    metrics: {
      users: userCount,
      companies: companyCount,
      projects: projectCount,
      tasks: taskCount,
      journals: journalCount
    },
    planStats,
    recentUsers
  }
})


import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import type { AuthUser } from '~/server/utils/auth'

export interface CompanyContext {
  companyId: string
}

/**
 * Stellt sicher, dass der Nutzer ein Firmen-Admin ist
 * (company_role === 'admin' mit company_id) oder Plattform-Superadmin.
 */
export function requireCompanyAdmin(event: any): AuthUser {
  const user = requireAuth(event)
  const isSuperadmin = Boolean((user as any).is_superadmin)
  if (!isSuperadmin) {
    if (!user.company_id || user.company_role !== 'admin') {
      throw createError({
        statusCode: 403,
        statusMessage: 'Nur Firmen-Administratoren haben Zugriff auf diesen Bereich'
      })
    }
  }
  return user
}

/** Liefert die effektive Company-ID (Company Admin: eigene Firma, Superadmin: per Query). */
export function resolveCompanyId(user: AuthUser, requested?: string | null): string {
  if ((user as any).is_superadmin) {
    const id = requested || user.company_id
    if (!id) throw createError({ statusCode: 400, statusMessage: 'Kein Unternehmen zugewiesen' })
    return id
  }
  if (!user.company_id) {
    throw createError({ statusCode: 400, statusMessage: 'Kein Unternehmen zugewiesen' })
  }
  return user.company_id
}

/** Plant-Definitionen (zentral, damit Frontend & Backend identisch rechnen). */
export const PLAN_PRICES: Record<string, { name: string; monthly: number; seats: number }> = {
  starter: { name: 'Starter Plan', monthly: 0, seats: 5 },
  pro: { name: 'Pro Business Plan', monthly: 49, seats: 25 },
  enterprise: { name: 'Enterprise Custom Plan', monthly: 189, seats: 100 }
}

/** Liest die Company-Settings als Objekt (robust gegen String/JSON/null). */
export function readCompanySettings(raw: any): Record<string, any> {
  if (!raw) return {}
  if (typeof raw === 'string') {
    try {
      const parsed = JSON.parse(raw)
      return parsed && typeof parsed === 'object' ? parsed : {}
    } catch {
      return {}
    }
  }
  return typeof raw === 'object' ? raw : {}
}

/** Holt die Company-Zeile inkl. Settings-Objekt oder wirft 404. */
export function getCompanyOr404(companyId: string): any {
  const company = db.prepare('SELECT * FROM companies WHERE id = ?').get(companyId) as any
  if (!company) {
    throw createError({ statusCode: 404, statusMessage: 'Unternehmen nicht gefunden' })
  }
  company.settings = readCompanySettings(company.settings)
  return company
}

/** Benachrichtigt alle Plattform-Superadmins. */
export function notifySuperadmins(type: string, title: string, message: string, refId?: string) {
  try {
    const admins = db.prepare('SELECT id FROM users WHERE is_superadmin = 1').all() as any[]
    const insert = db.prepare(`
      INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
      VALUES (?, ?, ?, ?, ?, 'company', ?, 0, datetime('now'))
    `)
    for (const a of admins) {
      insert.run(
        'notif_' + Math.random().toString(36).slice(2, 10),
        a.id,
        type,
        title,
        message,
        refId || null
      )
    }
  } catch {
    // Benachrichtigungen dürfen die Hauptaktion nie blockieren
  }
}
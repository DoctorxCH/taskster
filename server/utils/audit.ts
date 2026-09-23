import { randomUUID } from 'crypto'
import type { H3Event } from 'h3'
import { db } from '../db'
import { getUserFromToken } from './auth'

export interface LogAuditOptions {
  action: string
  entityType?: string
  entityId?: string
  userId?: string
  companyId?: string
  details?: Record<string, any>
}

export function logAuditEvent(event: H3Event | null, options: LogAuditOptions): void {
  try {
    let userId = options.userId || null
    let companyId = options.companyId || null
    let ipAddress: string | null = null
    let userAgent: string | null = null

    if (event) {
      // Extract IP & User Agent from request
      const reqIp = getHeader(event, 'x-forwarded-for') || getHeader(event, 'x-real-ip')
      ipAddress = Array.isArray(reqIp) ? reqIp[0] : (reqIp || null)
      userAgent = getHeader(event, 'user-agent') || null

      // If user/company not explicitly provided, try extracting from auth token
      if (!userId) {
        const authHeader = getHeader(event, 'authorization')
        if (authHeader && authHeader.startsWith('Bearer ')) {
          const user = getUserFromToken(authHeader.substring(7))
          if (user) {
            userId = user.id
            companyId = companyId || user.company_id
          }
        }
      }
    }

    const id = 'aud_' + randomUUID().substring(0, 12)
    const detailsJson = JSON.stringify(options.details || {})

    db.prepare(`
      INSERT INTO audit_logs (id, user_id, company_id, action, entity_type, entity_id, ip_address, user_agent, details)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    `).run(
      id,
      userId,
      companyId,
      options.action,
      options.entityType || null,
      options.entityId || null,
      ipAddress,
      userAgent,
      detailsJson
    )
  } catch (err) {
    console.error('[AuditLog Error]', err)
  }
}

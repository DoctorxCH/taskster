import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'
import { logAuditEvent } from '~/server/utils/audit'

export default defineEventHandler(async (event) => {
  requireAdminPermission(event, 'company_settings')
  const body = await readBody(event)

  if (!body || typeof body !== 'object') {
    throw createError({ statusCode: 400, statusMessage: 'Ungültige Anfragen-Daten' })
  }

  const stmt = db.prepare(`
    INSERT INTO system_settings (key, value, updated_at)
    VALUES (?, ?, datetime('now'))
    ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')
  `)

  for (const [k, v] of Object.entries(body)) {
    if (k.startsWith('website_')) {
      const stringValue = typeof v === 'string' ? v : JSON.stringify(v)
      stmt.run(k, stringValue)
    }
  }

  logAuditEvent(event, {
    action: 'website.settings_update',
    entityType: 'system_settings',
    details: { keys_updated: Object.keys(body).filter(k => k.startsWith('website_')) }
  })

  return { success: true, message: 'Webseiten-Einstellungen erfolgreich gespeichert' }
})

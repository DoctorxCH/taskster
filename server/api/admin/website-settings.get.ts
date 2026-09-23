import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  const rows = db.prepare('SELECT key, value FROM system_settings WHERE key LIKE "website_%"').all() as any[]
  
  const settings: Record<string, any> = {
    website_hero_title: 'Taskster – Das intelligente Bautagebuch & Projekt-Management',
    website_hero_subtitle: 'Verwalte Baustellen, Aufgaben, Zeiterfassung und Berichte nahtlos in einer Plattform.',
    website_contact_email: 'support@taskster.ch',
    website_contact_phone: '+41 44 123 45 67',
    website_pricing_basic_price: '0 CHF',
    website_pricing_pro_price: '29 CHF',
    website_pricing_enterprise_price: 'Auf Anfrage',
    website_announcement_active: false,
    website_announcement_text: 'Willkommen bei Taskster! Neue Version v2.4 ist live.',
    website_announcement_type: 'info',
    website_seo_title: 'Taskster – Bautagebuch & Handwerker Software Schweiz',
    website_seo_description: 'Software für Bauleiter, Handwerker und Projektteams. Digitalisiere dein Bautagebuch, Aufgaben und Zeiterfassung.',
    website_maintenance_mode: false
  }

  for (const r of rows) {
    try {
      settings[r.key] = JSON.parse(r.value)
    } catch {
      settings[r.key] = r.value
    }
  }

  return settings
})

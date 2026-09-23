import { db } from '~/server/db'

export default defineEventHandler(() => {
  const rows = db.prepare('SELECT key, value FROM system_settings WHERE key LIKE "website_%"').all() as any[]
  
  const settings: Record<string, any> = {
    website_title: 'Taskster - Professionelles Projekt- & Bauleitermanagement',
    website_hero_title: 'Modernes Projekt- & Team-Management für jedes Vorhaben',
    website_hero_subtitle: 'Strukturierte Aufgabenverwaltung, tiefe Projekthierarchien, nahtlose Teamkollaboration und granulares Rechtemanagement – von Einzelprojekten bis hin zu unternehmensweiten Teams.',
    website_contact_email: 'support@taskster.ch',
    website_contact_phone: '+41 44 123 45 67',
    website_pricing_basic_price: '0 CHF',
    website_pricing_pro_price: '29 CHF',
    website_pricing_enterprise_price: 'Auf Anfrage',
    website_announcement_active: false,
    website_announcement_text: '',
    website_announcement_type: 'info',
    website_seo_title: 'Taskster - Professionelles Projekt- & Bauleitermanagement',
    website_seo_description: 'Taskster - Enterprise Projekt- und Bauleitermanagement Plattform',
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

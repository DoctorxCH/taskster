export interface WebsiteSettingsData {
  website_title: string
  website_hero_title: string
  website_hero_subtitle: string
  website_contact_email: string
  website_contact_phone: string
  website_pricing_basic_price: string
  website_pricing_pro_price: string
  website_pricing_enterprise_price: string
  website_announcement_active: boolean
  website_announcement_text: string
  website_announcement_type: string
  website_seo_title: string
  website_seo_description: string
  website_maintenance_mode: boolean
}

export const useWebsiteSettings = () => {
  const settings = useState<WebsiteSettingsData>('taskster_website_settings', () => ({
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
  }))

  const loaded = useState<boolean>('taskster_website_settings_loaded', () => false)

  const fetchSettings = async () => {
    try {
      const res = await $fetch<Partial<WebsiteSettingsData>>('/api/website-settings')
      if (res && typeof res === 'object') {
        settings.value = { ...settings.value, ...res }
        loaded.value = true
      }
    } catch {
      // Keep sensible defaults on network or offline error
    }
  }

  // Dynamic Browser-Tab Title and Meta Tags
  const currentTitle = computed(() => settings.value.website_title || settings.value.website_seo_title || 'Taskster - Professionelles Projekt- & Bauleitermanagement')
  const currentDescription = computed(() => settings.value.website_seo_description || 'Taskster - Enterprise Projekt- und Bauleitermanagement Plattform')

  useHead(() => ({
    title: currentTitle.value,
    meta: [
      { name: 'description', content: currentDescription.value },
      { property: 'og:title', content: currentTitle.value },
      { property: 'og:description', content: currentDescription.value }
    ]
  }))

  return {
    settings,
    loaded,
    currentTitle,
    currentDescription,
    fetchSettings
  }
}

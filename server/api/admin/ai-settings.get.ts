import { db } from '~/server/db'
import { requireAdminPermission } from '~/server/utils/auth'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')

  const rows = db.prepare('SELECT key, value FROM system_settings WHERE key LIKE "ai_%"').all() as any[]
  
  const settings: Record<string, any> = {
    ai_enabled: true,
    ai_model: 'google/gemini-2.5-flash',
    ai_audio_model: 'openai/whisper-large-v3-turbo',
    ai_temperature: 0.3,
    ai_max_tokens: 2048,
    ai_system_prompt: 'Du bist ein präziser technischer Assistent für das Taskster-Projekt (Nuxt 3, Vue, PHP, MySQL, Zero-Trust-SaaS). Antworte kurz, konkret und auf Deutsch. Gib bei Code immer vollständige, lauffähige Ausschnitte.',
    ai_plan_basic_enabled: false,
    ai_plan_basic_monthly_limit: 10,
    ai_plan_basic_audio_enabled: false,
    ai_plan_pro_enabled: true,
    ai_plan_pro_monthly_limit: 500,
    ai_plan_pro_audio_enabled: true,
    ai_plan_enterprise_enabled: true,
    ai_plan_enterprise_monthly_limit: 5000,
    ai_plan_enterprise_audio_enabled: true,
    ai_plan_enterprise_custom_key_allowed: true
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

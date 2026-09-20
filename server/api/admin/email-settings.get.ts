import { requireAdminPermission } from '~/server/utils/auth'
import { getSmtpConfig } from '~/server/utils/mailer'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')
  const config = getSmtpConfig()

  return {
    settings: {
      ...config,
      smtp_password_set: Boolean(config.smtp_password),
      smtp_password: config.smtp_password || ''
    },
    config: {
      ...config,
      smtp_password_set: Boolean(config.smtp_password),
      smtp_password: config.smtp_password || ''
    }
  }
})

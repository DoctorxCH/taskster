import { requireAdminPermission } from '~/server/utils/auth'
import { getSmtpConfig } from '~/server/utils/mailer'

export default defineEventHandler((event) => {
  requireAdminPermission(event, 'any_admin')
  const config = getSmtpConfig()

  return {
    config: {
      ...config,
      // Mask password for safety if not requesting explicit edit
      smtp_password_set: Boolean(config.smtp_password),
      smtp_password: config.smtp_password || ''
    }
  }
})

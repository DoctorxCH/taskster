import { requireAdminPermission } from '~/server/utils/auth'
import { saveSmtpConfig, getSmtpConfig } from '~/server/utils/mailer'

export default defineEventHandler(async (event) => {
  requireAdminPermission(event, 'any_admin')
  const body = await readBody(event)

  const { mail_provider, resend_api_key, smtp_host, smtp_port, smtp_secure, smtp_user, smtp_password, smtp_from_email, smtp_from_name } = body

  const current = getSmtpConfig()

  saveSmtpConfig({
    mail_provider: mail_provider || current.mail_provider,
    resend_api_key: resend_api_key !== undefined && !resend_api_key.includes('••••') ? String(resend_api_key).trim() : current.resend_api_key,
    smtp_host: smtp_host ? String(smtp_host).trim() : current.smtp_host,
    smtp_port: parseInt(String(smtp_port || current.smtp_port), 10) || 465,
    smtp_secure: (smtp_secure as any) || current.smtp_secure || 'ssl',
    smtp_user: smtp_user ? String(smtp_user).trim() : current.smtp_user,
    smtp_password: smtp_password !== undefined && smtp_password !== '' && !smtp_password.includes('••••') ? String(smtp_password).trim() : current.smtp_password,
    smtp_from_email: smtp_from_email ? String(smtp_from_email).trim() : current.smtp_from_email,
    smtp_from_name: smtp_from_name ? String(smtp_from_name).trim() : current.smtp_from_name
  })

  return {
    success: true,
    message: 'E-Mail-Einstellungen erfolgreich gespeichert',
    config: getSmtpConfig()
  }
})

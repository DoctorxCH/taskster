import { requireAdminPermission } from '~/server/utils/auth'
import { saveSmtpConfig, getSmtpConfig } from '~/server/utils/mailer'

export default defineEventHandler(async (event) => {
  requireAdminPermission(event, 'any_admin')
  const body = await readBody(event)

  const { smtp_host, smtp_port, smtp_secure, smtp_user, smtp_password, smtp_from_email, smtp_from_name } = body

  if (!smtp_host || !smtp_port || !smtp_user) {
    throw createError({ statusCode: 400, statusMessage: 'Host, Port und Benutzername sind erforderlich' })
  }

  const current = getSmtpConfig()

  saveSmtpConfig({
    smtp_host: String(smtp_host).trim(),
    smtp_port: parseInt(String(smtp_port), 10) || 465,
    smtp_secure: (smtp_secure as any) || 'ssl',
    smtp_user: String(smtp_user).trim(),
    smtp_password: smtp_password !== undefined && smtp_password !== '' ? String(smtp_password).trim() : current.smtp_password,
    smtp_from_email: smtp_from_email ? String(smtp_from_email).trim() : String(smtp_user).trim(),
    smtp_from_name: smtp_from_name ? String(smtp_from_name).trim() : 'Taskster'
  })

  return {
    success: true,
    message: 'SMTP-Einstellungen erfolgreich gespeichert',
    config: getSmtpConfig()
  }
})

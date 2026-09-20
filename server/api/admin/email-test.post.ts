import { requireAdminPermission } from '~/server/utils/auth'
import { sendSmtpEmail, getSmtpConfig, SmtpConfig } from '~/server/utils/mailer'

export default defineEventHandler(async (event) => {
  const user = requireAdminPermission(event, 'any_admin')
  const body = await readBody(event)

  const toEmail = (body.to_email || user.email || '').trim()
  if (!toEmail) {
    throw createError({ statusCode: 400, statusMessage: 'Empfänger-E-Mail erforderlich' })
  }

  // If test configuration passed directly, use it; otherwise use saved config
  let testConfig: SmtpConfig | undefined
  if (body.config && body.config.smtp_host) {
    const current = getSmtpConfig()
    testConfig = {
      smtp_host: String(body.config.smtp_host).trim(),
      smtp_port: parseInt(String(body.config.smtp_port), 10) || 465,
      smtp_secure: body.config.smtp_secure || 'ssl',
      smtp_user: String(body.config.smtp_user).trim(),
      smtp_password: body.config.smtp_password ? String(body.config.smtp_password).trim() : current.smtp_password,
      smtp_from_email: body.config.smtp_from_email ? String(body.config.smtp_from_email).trim() : body.config.smtp_user,
      smtp_from_name: body.config.smtp_from_name ? String(body.config.smtp_from_name).trim() : 'Taskster'
    }
  }

  const subject = `[Taskster] Test-E-Mail von noreply@kurka.ch (${new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })})`
  const bodyText = `Hallo,\n\nDies ist eine erfolgreiche Test-E-Mail vom Taskster SMTP-Dienst (${testConfig?.smtp_host || getSmtpConfig().smtp_host}).\n\nZeitstempel: ${new Date().toISOString()}\n\nBeste Grüsse,\nDein Taskster System`
  const bodyHtml = `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 8px; background: #ffffff;">
      <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #00A3C4; margin: 0;">Taskster SMTP Test</h2>
      </div>
      <p style="font-size: 14px; color: #334155;">Hallo <strong>${toEmail}</strong>,</p>
      <div style="background: #f0fdfa; border: 1px solid #ccfbf1; border-radius: 6px; padding: 16px; margin: 16px 0;">
        <p style="color: #0f766e; font-size: 14px; font-weight: bold; margin: 0 0 8px 0;">
          ✓ Verbindung und Authentifizierung erfolgreich!
        </p>
        <p style="color: #115e59; font-size: 12px; margin: 0;">
          Host: <strong>${testConfig?.smtp_host || getSmtpConfig().smtp_host}</strong> (${testConfig?.smtp_port || getSmtpConfig().smtp_port})<br>
          Absender: <strong>${testConfig?.smtp_from_email || getSmtpConfig().smtp_from_email}</strong><br>
          Zeitstempel: <strong>${new Date().toLocaleString('de-CH')}</strong>
        </p>
      </div>
      <p style="font-size: 12px; color: #64748b;">Dein Taskster Benachrichtigungssystem ist jetzt einsatzbereit.</p>
    </div>
  `

  try {
    const result = await sendSmtpEmail({
      to: toEmail,
      toName: 'Taskster Admin',
      subject,
      bodyHtml,
      bodyText
    }, testConfig)

    return {
      success: true,
      message: `Test-E-Mail erfolgreich an ${toEmail} versendet!`,
      log: result.log
    }
  } catch (err: any) {
    throw createError({
      statusCode: 500,
      statusMessage: `SMTP-Fehler: ${err.message || String(err)}`
    })
  }
})

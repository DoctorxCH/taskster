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
  const custom = body.custom_config || body.config
  let testConfig: SmtpConfig | undefined
  const current = getSmtpConfig()

  if (custom) {
    testConfig = {
      mail_provider: custom.mail_provider || current.mail_provider,
      resend_api_key: custom.resend_api_key && !custom.resend_api_key.includes('••••') ? String(custom.resend_api_key).trim() : current.resend_api_key,
      smtp_host: custom.smtp_host ? String(custom.smtp_host).trim() : current.smtp_host,
      smtp_port: parseInt(String(custom.smtp_port || current.smtp_port), 10) || 465,
      smtp_secure: custom.smtp_secure || current.smtp_secure || 'ssl',
      smtp_user: custom.smtp_user ? String(custom.smtp_user).trim() : current.smtp_user,
      smtp_password: custom.smtp_password && !custom.smtp_password.includes('••••') ? String(custom.smtp_password).trim() : current.smtp_password,
      smtp_from_email: custom.smtp_from_email ? String(custom.smtp_from_email).trim() : current.smtp_from_email,
      smtp_from_name: custom.smtp_from_name ? String(custom.smtp_from_name).trim() : current.smtp_from_name
    }
  }

  const activeConfig = testConfig || current
  const isResend = activeConfig.mail_provider === 'resend'
  const subject = `[Taskster] Test-E-Mail von ${activeConfig.smtp_from_email} (${new Date().toLocaleTimeString('de-CH', { hour: '2-digit', minute: '2-digit' })})`
  const bodyText = `Hallo,\n\nDies ist eine erfolgreiche Test-E-Mail vom Taskster E-Mail-Dienst via ${isResend ? 'Resend API' : ('SMTP: ' + activeConfig.smtp_host)}.\n\nZeitstempel: ${new Date().toISOString()}\n\nBeste Grüsse,\nDein Taskster System`
  const bodyHtml = `
    <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #00A3C4; border-radius: 8px; background: #ffffff;">
      <div style="display: flex; align-items: center; margin-bottom: 20px;">
        <h2 style="color: #00A3C4; margin: 0;">Taskster E-Mail Test erfolgreich! 🎉</h2>
      </div>
      <p style="font-size: 14px; color: #334155;">Hallo <strong>${toEmail}</strong>,</p>
      <div style="background: #f0fdfa; border: 1px solid #ccfbf1; border-left: 4px solid #00A3C4; border-radius: 6px; padding: 16px; margin: 16px 0;">
        <p style="color: #0f766e; font-size: 14px; font-weight: bold; margin: 0 0 8px 0;">
          ✓ Versandmethode: <strong>${isResend ? 'Resend API (DKIM / SPF verifiziert)' : ('SMTP Server (' + activeConfig.smtp_host + ')')}</strong>
        </p>
        <p style="color: #115e59; font-size: 12px; margin: 0;">
          Absender: <strong>${activeConfig.smtp_from_name} &lt;${activeConfig.smtp_from_email}&gt;</strong><br>
          Empfänger: <strong>${toEmail}</strong><br>
          Zeitstempel: <strong>${new Date().toLocaleString('de-CH')}</strong>
        </p>
      </div>
      <p style="font-size: 12px; color: #64748b;">Dein Taskster Benachrichtigungssystem ist jetzt vollständig einsatzbereit.</p>
    </div>
  `

  try {
    const result = await sendSmtpEmail({
      to: toEmail,
      toName: user.name || 'Taskster Admin',
      subject,
      bodyHtml,
      bodyText
    }, testConfig)

    return {
      success: true,
      message: `Test-E-Mail erfolgreich via ${isResend ? 'Resend API' : 'SMTP'} an ${toEmail} versendet!`,
      log: result.log
    }
  } catch (err: any) {
    throw createError({
      statusCode: 500,
      statusMessage: `E-Mail-Fehler: ${err.message || String(err)}`
    })
  }
})

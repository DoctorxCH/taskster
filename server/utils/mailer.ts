import { db } from '~/server/db'
import { randomUUID } from 'crypto'
import * as net from 'net'
import * as tls from 'tls'
import { Resend } from 'resend'

export interface SmtpConfig {
  mail_provider?: 'resend' | 'smtp'
  resend_api_key?: string
  smtp_host: string
  smtp_port: number
  smtp_secure: 'ssl' | 'tls' | 'none'
  smtp_user: string
  smtp_password?: string
  smtp_from_email: string
  smtp_from_name: string
}

export function getSmtpConfig(): SmtpConfig {
  const rows = db.prepare("SELECT key, value FROM system_settings WHERE key LIKE 'smtp_%' OR key = 'mail_provider' OR key = 'resend_api_key'").all() as any[]
  const map: Record<string, string> = {}
  for (const r of rows) map[r.key] = r.value

  const envResend = process.env.RESEND_API_KEY
  return {
    mail_provider: (map.mail_provider as any) || (envResend || map.resend_api_key ? 'resend' : 'smtp'),
    resend_api_key: map.resend_api_key || envResend || '',
    smtp_host: map.smtp_host || 'mail.kurka.ch',
    smtp_port: parseInt(map.smtp_port || '465', 10),
    smtp_secure: (map.smtp_secure as any) || 'ssl',
    smtp_user: map.smtp_user || 'noreply@kurka.ch',
    smtp_password: map.smtp_password || process.env.SMTP_PASSWORD || '',
    smtp_from_email: map.smtp_from_email || 'noreply@kurka.ch',
    smtp_from_name: map.smtp_from_name || 'Taskster'
  }
}

export function saveSmtpConfig(config: Partial<SmtpConfig>) {
  const stmt = db.prepare(`
    INSERT INTO system_settings (key, value, updated_at)
    VALUES (?, ?, datetime('now'))
    ON CONFLICT(key) DO UPDATE SET value = excluded.value, updated_at = datetime('now')
  `)
  for (const [k, v] of Object.entries(config)) {
    if (v !== undefined) {
      stmt.run(k, String(v))
    }
  }
}

export type EmailPurpose = 'onboarding' | 'updates' | 'collaboration' | 'notify' | 'system'

export interface SenderIdentity {
  email: string
  name: string
  replyTo?: string
  description: string
}

export const EMAIL_SENDERS: Record<EmailPurpose, SenderIdentity> = {
  onboarding: {
    email: 'hey@kurka.ch',
    name: 'Taskster',
    replyTo: 'support@kurka.ch',
    description: 'Onboarding, Willkommensnachrichten, direkte Ansprache'
  },
  updates: {
    email: 'updates@kurka.ch',
    name: 'Taskster',
    description: 'Changelogs, Produkt-News, Newsletter'
  },
  collaboration: {
    email: 'team@kurka.ch',
    name: 'Taskster',
    description: 'Kollaborations-Ereignisse (Zuweisungen, Erwähnungen, Kommentare, Termine)'
  },
  notify: {
    email: 'notify@kurka.ch',
    name: 'Taskster',
    description: 'Allgemeine Benachrichtigungen, Fristen, Statusänderungen'
  },
  system: {
    email: 'system@kurka.ch',
    name: 'Taskster',
    description: 'Technische Transaktionsmails (Passwort-Resets, Sicherheitswarnungen, Account-Änderungen)'
  }
}

export function getSenderForTrigger(triggerEvent?: string, purpose?: EmailPurpose): { from: string; email: string; name: string; replyTo?: string } {
  let p: EmailPurpose = purpose || 'notify'

  if (!purpose && triggerEvent) {
    if (['company_invite', 'user_welcome', 'onboarding', 'invite'].includes(triggerEvent)) {
      p = 'onboarding'
    } else if (['updates', 'changelog', 'newsletter', 'product_news'].includes(triggerEvent)) {
      p = 'updates'
    } else if (['task_assigned', 'task_comment', 'mention', 'calendar_invite', 'calendar_change', 'calendar_cancel'].includes(triggerEvent)) {
      p = 'collaboration'
    } else if (['task_due', 'calendar_reminder', 'budget_warning', 'digest'].includes(triggerEvent)) {
      p = 'notify'
    } else if (['password_reset', 'security_alert', 'account_change', '2fa', 'auth_verification'].includes(triggerEvent)) {
      p = 'system'
    }
  }

  const sender = EMAIL_SENDERS[p] || EMAIL_SENDERS.notify
  return {
    from: `${sender.name} <${sender.email}>`,
    email: sender.email,
    name: sender.name,
    replyTo: sender.replyTo
  }
}

export interface MailOptions {
  to: string
  toName?: string
  subject: string
  bodyHtml?: string
  bodyText?: string
  icsContent?: string
  triggerEvent?: string
  purpose?: EmailPurpose
  from?: string
  replyTo?: string
}

/**
 * Sendet E-Mails über die Resend API mit dedizierten Absendern (@kurka.ch)
 */
export async function sendResendEmail(options: MailOptions, apiKey: string, customFrom?: string, customReplyTo?: string): Promise<{ success: boolean; log: string[] }> {
  const log: string[] = []
  const outboxId = 'out_' + randomUUID().substring(0, 8)
  const resend = new Resend(apiKey)
  
  const sender = getSenderForTrigger(options.triggerEvent, options.purpose)
  const fromAddress = customFrom || options.from || sender.from
  const replyTo = customReplyTo || options.replyTo || sender.replyTo

  // Log pending to email_outbox
  try {
    db.prepare(`
      INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status, attempts)
      VALUES (?, ?, ?, ?, ?, ?, 'pending', 1)
    `).run(
      outboxId,
      options.to,
      options.toName || null,
      options.subject,
      options.bodyHtml || options.bodyText || '',
      options.icsContent || null
    )
  } catch (_) {}

  log.push(`> [Resend API] Sende E-Mail an ${options.to} via ${fromAddress}${replyTo ? ` (Reply-To: ${replyTo})` : ''}`)

  try {
    const payload: any = {
      from: fromAddress,
      to: [options.to],
      subject: options.subject,
      html: options.bodyHtml || `<p>${(options.bodyText || '').replace(/\n/g, '<br>')}</p>`,
      text: options.bodyText || undefined
    }

    if (replyTo) {
      payload.reply_to = replyTo
    }

    if (options.icsContent) {
      payload.headers = {
        'Content-Class': 'urn:content-classes:calendarmessage'
      }
      payload.attachments = [
        {
          filename: 'invite.ics',
          content: Buffer.from(options.icsContent).toString('base64')
        }
      ]
    }

    const { data, error } = await resend.emails.send(payload)

    if (error) {
      const errMsg = `${error.name}: ${error.message}`
      log.push(`! [Resend Error] ${errMsg}`)
      updateOutboxError(outboxId, errMsg)
      return { success: false, log }
    }

    log.push(`< [Resend Success] id: ${data?.id}`)
    updateOutboxSuccess(outboxId)
    return { success: true, log }
  } catch (err: any) {
    const errMsg = err?.message || String(err)
    log.push(`! [Resend Exception] ${errMsg}`)
    updateOutboxError(outboxId, errMsg)
    return { success: false, log }
  }
}

/**
 * Native Socket/TLS SMTP Mailer mit automatischem Resend-Routing
 */
export async function sendSmtpEmail(options: MailOptions, customConfig?: SmtpConfig): Promise<{ success: boolean; log: string[] }> {
  const cfg = customConfig || getSmtpConfig()
  const resendApiKey = cfg.resend_api_key || process.env.RESEND_API_KEY
  if (cfg.mail_provider !== 'smtp' && resendApiKey && resendApiKey.startsWith('re_') && resendApiKey !== 're_xxxxxxxxx') {
    const sender = getSenderForTrigger(options.triggerEvent, options.purpose)
    const fromStr = options.from || (customConfig?.smtp_from_email ? `${customConfig.smtp_from_name || 'Taskster'} <${customConfig.smtp_from_email}>` : sender.from)
    const replyToStr = options.replyTo || sender.replyTo
    return sendResendEmail(options, resendApiKey, fromStr, replyToStr)
  }

  const log: string[] = []
  const outboxId = 'out_' + randomUUID().substring(0, 8)

  // Log pending to email_outbox
  try {
    db.prepare(`
      INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status, attempts)
      VALUES (?, ?, ?, ?, ?, ?, 'pending', 1)
    `).run(
      outboxId,
      options.to,
      options.toName || null,
      options.subject,
      options.bodyHtml || options.bodyText || '',
      options.icsContent || null
    )
  } catch (_) {}

  return new Promise((resolve, reject) => {
    let socket: net.Socket | tls.TLSSocket
    const host = cfg.smtp_host
    const port = cfg.smtp_port
    const isSsl = cfg.smtp_secure === 'ssl' || port === 465

    let step = 0
    let buffer = ''

    const sendLine = (line: string, maskInLog = false) => {
      log.push(`> ${maskInLog ? '********' : line}`)
      socket.write(line + '\r\n')
    }

    const onData = (data: Buffer) => {
      buffer += data.toString()
      const lines = buffer.split('\r\n')
      buffer = lines.pop() || ''

      for (const line of lines) {
        if (!line.trim()) continue
        log.push(`< ${line}`)
        const code = parseInt(line.substring(0, 3), 10)

        // Only process completion replies (e.g. "250 " or "220 ")
        if (line.length >= 4 && line.charAt(3) === '-') continue

        const fromDomain = cfg.smtp_from_email.includes('@') ? cfg.smtp_from_email.split('@')[1] : 'kurka.ch'
        const ehloDomain = cfg.smtp_host || fromDomain

        if (step === 0 && code === 220) {
          step = 1
          sendLine(`EHLO ${ehloDomain}`)
        } else if (step === 1 && code === 250) {
          if (cfg.smtp_user && cfg.smtp_password) {
            step = 2
            sendLine('AUTH LOGIN')
          } else {
            step = 4
            sendLine(`MAIL FROM:<${cfg.smtp_from_email}>`)
          }
        } else if (step === 2 && code === 334) {
          step = 3
          sendLine(Buffer.from(cfg.smtp_user).toString('base64'))
        } else if (step === 3 && code === 334) {
          step = 4
          sendLine(Buffer.from(cfg.smtp_password || '').toString('base64'), true)
        } else if (step === 4 && code === 235) {
          step = 5
          sendLine(`MAIL FROM:<${cfg.smtp_from_email}>`)
        } else if (step === 5 && code === 250) {
          step = 6
          sendLine(`RCPT TO:<${options.to}>`)
        } else if (step === 6 && code === 250) {
          step = 7
          sendLine('DATA')
        } else if (step === 7 && code === 354) {
          step = 8

          const boundary = '----=_Part_' + Date.now()
          const altBoundary = '----=_Alt_' + Date.now()
          const fromHeader = cfg.smtp_from_name ? `"${cfg.smtp_from_name}" <${cfg.smtp_from_email}>` : cfg.smtp_from_email
          const toHeader = options.toName ? `"${options.toName}" <${options.to}>` : options.to
          const messageId = `<${Date.now()}_${Math.random().toString(36).slice(2)}@${fromDomain}>`

          let message = `From: ${fromHeader}\r\n`
          message += `Reply-To: ${fromHeader}\r\n`
          message += `To: ${toHeader}\r\n`
          message += `Subject: =?UTF-8?B?${Buffer.from(options.subject).toString('base64')}?=\r\n`
          message += `Date: ${new Date().toUTCString()}\r\n`
          message += `MIME-Version: 1.0\r\n`
          message += `Message-ID: ${messageId}\r\n`
          message += `Auto-Submitted: auto-generated\r\n`
          message += `X-Mailer: Taskster\r\n`

          const textContent = options.bodyText || (options.bodyHtml ? options.bodyHtml.replace(/<[^>]*>/g, '') : '')
          const htmlContent = options.bodyHtml || (options.bodyText ? options.bodyText.replace(/\n/g, '<br>') : '')

          if (options.icsContent) {
            message += `Content-Type: multipart/alternative; boundary="${altBoundary}"\r\n`
            message += `Content-Class: urn:content-classes:calendarmessage\r\n\r\n`
            message += `--${altBoundary}\r\n`
            message += `Content-Type: text/plain; charset=UTF-8\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(textContent).toString('base64') + '\r\n\r\n'
            message += `--${altBoundary}\r\n`
            message += `Content-Type: text/html; charset=UTF-8\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(htmlContent).toString('base64') + '\r\n\r\n'
            message += `--${altBoundary}\r\n`
            message += `Content-Type: text/calendar; charset=UTF-8; method=REQUEST\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(options.icsContent).toString('base64') + '\r\n\r\n'
            message += `--${altBoundary}--\r\n`
          } else if (options.bodyHtml) {
            message += `Content-Type: multipart/alternative; boundary="${altBoundary}"\r\n\r\n`
            message += `--${altBoundary}\r\n`
            message += `Content-Type: text/plain; charset=UTF-8\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(textContent).toString('base64') + '\r\n\r\n'
            message += `--${altBoundary}\r\n`
            message += `Content-Type: text/html; charset=UTF-8\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(htmlContent).toString('base64') + '\r\n\r\n'
            message += `--${altBoundary}--\r\n`
          } else {
            message += `Content-Type: text/plain; charset=UTF-8\r\n`
            message += `Content-Transfer-Encoding: base64\r\n\r\n`
            message += Buffer.from(textContent).toString('base64') + '\r\n'
          }

          message += '\r\n.'
          sendLine(message)
        } else if (step === 8 && code === 250) {
          step = 9
          sendLine('QUIT')
        } else if (step === 9 && code === 221) {
          socket.end()
          updateOutboxSuccess(outboxId)
          resolve({ success: true, log })
        } else if (code >= 400) {
          const errMsg = `SMTP Fehler ${code}: ${line}`
          socket.destroy()
          updateOutboxError(outboxId, errMsg)
          reject(new Error(errMsg))
        }
      }
    }

    const onError = (err: Error) => {
      log.push(`! Fehler: ${err.message}`)
      updateOutboxError(outboxId, err.message)
      reject(err)
    }

    const timer = setTimeout(() => {
      socket.destroy()
      const timeoutErr = new Error(`SMTP Timeout nach 15 Sekunden bei ${host}:${port}`)
      updateOutboxError(outboxId, timeoutErr.message)
      reject(timeoutErr)
    }, 15000)

    try {
      if (isSsl) {
        socket = tls.connect({
          host,
          port,
          rejectUnauthorized: false
        }, () => {
          log.push(`* TLS/SSL Verbindung zu ${host}:${port} erfolgreich aufgebaut.`)
        })
      } else {
        socket = net.createConnection({ host, port }, () => {
          log.push(`* TCP Verbindung zu ${host}:${port} erfolgreich aufgebaut.`)
        })
      }

      socket.on('data', onData)
      socket.on('error', onError)
      socket.on('close', () => {
        clearTimeout(timer)
      })
    } catch (e: any) {
      clearTimeout(timer)
      updateOutboxError(outboxId, e.message)
      reject(e)
    }
  })
}

function updateOutboxSuccess(id: string) {
  try {
    db.prepare("UPDATE email_outbox SET status = 'sent', sent_at = datetime('now') WHERE id = ?").run(id)
  } catch (_) {}
}

function updateOutboxError(id: string, error: string) {
  try {
    db.prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?").run(error, id)
  } catch (_) {}
}

/**
 * Render and send trigger-based email with user preference check
 */
export async function sendTriggerEmail(
  triggerEvent: string,
  recipient: { id?: string; email: string; name?: string; settings?: any },
  data: Record<string, any>
) {
  // 1. Check if recipient wants this notification
  if (recipient.settings && recipient.settings.notifications) {
    if (recipient.settings.notifications.email === false) return null
    if (recipient.settings.notifications.events && recipient.settings.notifications.events[triggerEvent] === false) {
      return null
    }
  }

  // 2. Fetch template
  const template = db.prepare('SELECT * FROM email_templates WHERE trigger_event = ? AND is_active = 1').get(triggerEvent) as any
  if (!template) return null

  // 3. Compile variables
  let subject = template.subject
  let bodyHtml = template.body_html
  let bodyText = template.body_text

  const mergedData = {
    user_name: recipient.name || recipient.email,
    user_email: recipient.email,
    action_url: 'https://taskster.ch',
    ...data
  }

  for (const [k, v] of Object.entries(mergedData)) {
    const placeholder = new RegExp(`{{\\s*${k}\\s*}}`, 'g')
    subject = subject.replace(placeholder, String(v || ''))
    bodyHtml = bodyHtml.replace(placeholder, String(v || ''))
    bodyText = bodyText.replace(placeholder, String(v || ''))
  }

  return sendSmtpEmail({
    to: recipient.email,
    toName: recipient.name,
    subject,
    bodyHtml,
    bodyText,
    icsContent: data.ics_content,
    triggerEvent
  })
}

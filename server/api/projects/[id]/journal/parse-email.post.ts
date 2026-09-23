import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'
import { evaluateProjectAccess } from '~/server/utils/permissions'
import { randomUUID } from 'crypto'
import { readFileSync, existsSync } from 'fs'
import { join } from 'path'

function getAiConfig() {
  const configPath = join(process.cwd(), 'ai.config.json')
  if (existsSync(configPath)) {
    try {
      return JSON.parse(readFileSync(configPath, 'utf8'))
    } catch (_) {}
  }
  return {
    model: 'google/gemini-2.5-flash',
    provider: { allow_fallbacks: true },
    temperature: 0.2,
    max_tokens: 4096,
    timeout_seconds: 120
  }
}

function getApiKey(): string {
  if (process.env.OPENROUTER_API_KEY) return process.env.OPENROUTER_API_KEY
  const candidates = [
    join(process.cwd(), '.env'),
    join(process.cwd(), '..', '.env'),
    join(__dirname, '..', '..', '..', '..', '.env')
  ]
  for (const envPath of candidates) {
    if (existsSync(envPath)) {
      const content = readFileSync(envPath, 'utf8')
      const match = content.match(/^OPENROUTER_API_KEY\s*=\s*(.+)$/m)
      if (match) {
        return match[1].trim().replace(/^["']|["']$/g, '').trim()
      }
    }
  }
  return ''
}

function decodeMimeHeader(str: string): string {
  if (!str) return ''
  return str.replace(/=\?([^?]+)\?([BQ])\?([^?]+)\?=/gi, (_, charset, encoding, text) => {
    try {
      if (encoding.toUpperCase() === 'B') {
        return Buffer.from(text.replace(/\s+/g, ''), 'base64').toString(charset.toLowerCase().includes('utf') ? 'utf8' : 'latin1')
      } else if (encoding.toUpperCase() === 'Q') {
        const cleaned = text.replace(/_/g, ' ').replace(/=([0-9A-F]{2})/gi, (__, hex) => String.fromCharCode(parseInt(hex, 16)))
        return Buffer.from(cleaned, 'binary').toString('utf8')
      }
    } catch (_) {}
    return text
  })
}

function decodeQuotedPrintable(str: string): string {
  if (!str) return ''
  const clean = str.replace(/=\r?\n/g, '')
  const binary = clean.replace(/=([0-9A-F]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
  try {
    return Buffer.from(binary, 'binary').toString('utf8')
  } catch (_) {
    return binary
  }
}

function decodeBase64Utf8(base64Str: string): string {
  try {
    const clean = base64Str.replace(/\s+/g, '')
    return Buffer.from(clean, 'base64').toString('utf8')
  } catch (_) {
    return base64Str
  }
}

function parseRawEml(rawText: string) {
  let subject = ''
  let fromEmail = ''
  let fromName = ''
  let body = ''

  const subMatch = rawText.match(/^Subject:\s*(.+?)(?=\r?\n[^\s]|$)/im)
  if (subMatch && subMatch[1]) {
    subject = decodeMimeHeader(subMatch[1].replace(/\r?\n\s+/g, ' ').trim())
  }

  const fromMatch = rawText.match(/^From:\s*(.+?)(?=\r?\n[^\s]|$)/im)
  if (fromMatch && fromMatch[1]) {
    const rawFrom = decodeMimeHeader(fromMatch[1].replace(/\r?\n\s+/g, ' ').trim())
    const emailMatch = rawFrom.match(/<([^>]+)>/)
    if (emailMatch) {
      fromEmail = emailMatch[1].trim()
      fromName = rawFrom.replace(/<[^>]+>/, '').replace(/["']/g, '').trim()
    } else {
      fromEmail = rawFrom
      fromName = rawFrom.split('@')[0]
    }
  }

  const boundaryMatch = rawText.match(/boundary=["']?([^"';\r\n]+)["']?/i)
  if (boundaryMatch) {
    const boundary = boundaryMatch[1].trim()
    const parts = rawText.split(new RegExp(`--${boundary.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:--)?`))
    let plainTextPart = ''
    let htmlPart = ''

    for (const part of parts) {
      const trimmedPart = part.trim()
      if (!trimmedPart || trimmedPart === '--') continue

      const headerSplit = trimmedPart.split(/\r?\n\r?\n/)
      const partHeaders = headerSplit[0] || ''
      const partBody = headerSplit.slice(1).join('\n\n') || ''

      const isTextPlain = /Content-Type:\s*text\/plain/i.test(partHeaders)
      const isTextHtml = /Content-Type:\s*text\/html/i.test(partHeaders)
      const isBase64 = /Content-Transfer-Encoding:\s*base64/i.test(partHeaders)
      const isQP = /Content-Transfer-Encoding:\s*quoted-printable/i.test(partHeaders)

      let decodedBody = partBody
      if (isBase64) {
        decodedBody = decodeBase64Utf8(partBody)
      } else if (isQP) {
        decodedBody = decodeQuotedPrintable(partBody)
      }

      if (isTextPlain && !plainTextPart) {
        plainTextPart = decodedBody.trim()
      } else if (isTextHtml && !htmlPart) {
        htmlPart = decodedBody.trim()
      }
    }

    if (plainTextPart) {
      body = plainTextPart
    } else if (htmlPart) {
      body = htmlPart
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
        .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/p>/gi, '\n\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .trim()
    }
  }

  if (!body) {
    const isBase64 = /Content-Transfer-Encoding:\s*base64/i.test(rawText)
    const isQP = /Content-Transfer-Encoding:\s*quoted-printable/i.test(rawText)
    const split = rawText.split(/\r?\n\r?\n/)
    if (split.length > 1) {
      const potentialBody = split.slice(1).join('\n\n').trim()
      if (isBase64) {
        body = decodeBase64Utf8(potentialBody)
      } else if (isQP) {
        body = decodeQuotedPrintable(potentialBody)
      } else {
        body = potentialBody
      }
    } else {
      body = isBase64 ? decodeBase64Utf8(rawText) : rawText.trim()
    }
  }

  body = body
    .replace(/^--[a-zA-Z0-9_-]+[^\n]*\n?/gm, '')
    .replace(/^Content-(?:Type|Transfer-Encoding|Disposition):[^\n]*\n?/gim, '')
    .trim()

  return { subject, fromName, fromEmail, body }
}

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string
  const body = await readBody(event)

  evaluateProjectAccess(user, projectId, event, 'write')

  let emailText = (body.email_text || body.content || '').trim()
  if (!emailText) {
    throw createError({ statusCode: 400, statusMessage: 'E-Mail-Text erforderlich' })
  }

  const sender = body.sender || {}
  const recipients = Array.isArray(body.recipients) ? body.recipients : []
  let emailSubject = (body.subject || '').trim()
  const attachments = Array.isArray(body.attachments) ? body.attachments : []

  // Automatische MIME / Base64 Dekodierung
  if (emailText.includes('Content-Transfer-Encoding') || emailText.includes('Content-Type:') || /^--[a-zA-Z0-9_-]+/m.test(emailText)) {
    const parsed = parseRawEml(emailText)
    if (parsed.body) emailText = parsed.body
    if (!emailSubject && parsed.subject) emailSubject = parsed.subject
    if (!sender.email && parsed.fromEmail) {
      sender.email = parsed.fromEmail
      if (!sender.name && parsed.fromName) sender.name = parsed.fromName
    }
  }

  // 1. Kontaktprüfung & Erstellung
  let senderEmail = (sender.email || '').trim().toLowerCase()
  let senderName = (sender.name || '').trim()
  const senderRole = (sender.role || 'E-Mail Kontakt').trim()
  const senderCompany = (sender.company || '').trim()

  if (!senderEmail) {
    const fromMatch = emailText.match(/(?:From|Von):\s*(?:([^<\r\n]+)\s*<)?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i)
    if (fromMatch) {
      if (fromMatch[1]) senderName = fromMatch[1].replace(/["']/g, '').trim()
      senderEmail = fromMatch[2].toLowerCase().trim()
    }
  }

  if (!emailSubject) {
    const subMatch = emailText.match(/(?:Subject|Betreff):\s*(.+?)(?:\r?\n|$)/i)
    if (subMatch) {
      emailSubject = subMatch[1].trim()
    }
  }

  if (senderEmail) {
    const companyId = user.company_id || null
    const existingContact = db.prepare(`
      SELECT id FROM contacts 
      WHERE LOWER(email) = LOWER(?) 
        AND (company_id = ? OR (company_id IS NULL AND user_id = ?))
      LIMIT 1
    `).get(senderEmail, companyId, user.id) as any

    if (!existingContact) {
      const newContactId = 'cnt_' + randomUUID().substring(0, 8)
      const parts = senderName.split(/\s+/)
      const firstName = parts.length > 1 ? parts[0] : ''
      const lastName = parts.length > 1 ? parts.slice(1).join(' ') : (parts[0] || senderEmail)

      db.prepare(`
        INSERT INTO contacts (id, user_id, company_id, project_id, first_name, last_name, company_name, role_function, email, category_group, share_scope, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sonstige', ?, datetime('now'))
      `).run(
        newContactId,
        user.id,
        companyId,
        projectId,
        firstName,
        lastName,
        senderCompany,
        senderRole,
        senderEmail,
        companyId ? 'company' : 'private'
      )
    }
  }

  // 2. Projektkontext & verknüpfte Aufgabe laden
  let linkedTaskId = body.task_id || null
  const targetJournalId = body.journal_id || body.entry_id
  if (targetJournalId && !linkedTaskId) {
    const jRow = db.prepare(`SELECT task_id FROM project_journals WHERE id = ? AND project_id = ?`).get(targetJournalId, projectId) as any
    if (jRow?.task_id) {
      linkedTaskId = jRow.task_id
    }
  }

  let linkedTask: any = null
  if (linkedTaskId) {
    linkedTask = db.prepare(`
      SELECT t.id, t.list_id, t.title, t.description, t.status, t.due_date, l.title as list_title 
      FROM tasks t 
      LEFT JOIN lists l ON l.id = t.list_id 
      WHERE t.id = ?
    `).get(linkedTaskId) as any
  }

  const sections = db.prepare(`SELECT id, title FROM lists WHERE project_id = ? ORDER BY sort_order ASC`).all(projectId) as any[]
  const existingTasks = db.prepare(`
    SELECT t.id, t.list_id, t.title, t.description, t.status, t.due_date, t.custom_data
    FROM tasks t 
    JOIN lists l ON l.id = t.list_id 
    WHERE l.project_id = ?
  `).all(projectId) as any[]

  // Intelligente automatische Aufgabenerkennung anhand Adresse, Name, Kundennummer oder Feldern
  if (!linkedTaskId && existingTasks.length > 0) {
    const combinedText = ((emailSubject || '') + ' ' + (emailText || '')).toLowerCase()
    let bestTaskId: string | null = null
    let bestScore = 0

    for (const t of existingTasks) {
      let score = 0
      const tTitle = (t.title || '').trim().toLowerCase()
      if (tTitle) {
        if (combinedText.includes(tTitle)) {
          score += 50
        } else {
          const tokens = tTitle.split(/[\s\-_,./]+/).filter((tok: string) => tok.length >= 4)
          let matchCount = 0
          for (const tok of tokens) {
            if (combinedText.includes(tok)) matchCount++
          }
          if (matchCount >= 2) {
            score += matchCount * 15
          }
        }
      }

      if (t.custom_data) {
        try {
          const cd = typeof t.custom_data === 'string' ? JSON.parse(t.custom_data) : t.custom_data
          if (cd && typeof cd === 'object') {
            for (const [, v] of Object.entries(cd)) {
              if (v !== null && v !== undefined && (typeof v === 'string' || typeof v === 'number')) {
                const valStr = String(v).trim().toLowerCase()
                if (valStr.length >= 3 && combinedText.includes(valStr)) {
                  score += 35
                }
              }
            }
          }
        } catch (_) {}
      }

      if (score > bestScore && score >= 30) {
        bestScore = score
        bestTaskId = t.id
      }
    }

    if (bestTaskId) {
      linkedTaskId = bestTaskId
      if (targetJournalId) {
        try {
          db.prepare(`UPDATE project_journals SET task_id = ? WHERE id = ? AND project_id = ?`).run(linkedTaskId, targetJournalId, projectId)
        } catch (_) {}
      }
    }
  }

  if (linkedTaskId && !linkedTask) {
    linkedTask = db.prepare(`
      SELECT t.id, t.list_id, t.title, t.description, t.status, t.due_date, l.title as list_title 
      FROM tasks t 
      LEFT JOIN lists l ON l.id = t.list_id 
      WHERE t.id = ?
    `).get(linkedTaskId) as any
  }

  const sectionsContext = JSON.stringify(sections.map(s => ({ id: s.id, title: s.title })))
  const tasksContext = JSON.stringify(existingTasks.map(t => ({
    id: t.id,
    section_id: t.list_id,
    title: t.title,
    description: t.description ? String(t.description).substring(0, 100) : '',
    status: t.status,
    due_date: t.due_date
  })))

  let linkedTaskContext = ''
  if (linkedTask) {
    linkedTaskContext = `DIREKT VERKNÜPFTE AUFGABE (HÖCHSTE PRIORITÄT / HAUPTFOKUS):\n${JSON.stringify({
      id: linkedTask.id,
      section: linkedTask.list_title || linkedTask.list_id,
      title: linkedTask.title,
      description: linkedTask.description,
      status: linkedTask.status,
      due_date: linkedTask.due_date
    })}\n\n`
  }

  // 3. KI-Verarbeitung (OpenRouter / DeepSeek Engine)
  const systemPrompt = `Du bist ein intelligenter technischer Bauleiter-Assistent im System Taskster.
Analysiere den Inhalt des Journaleintrags, Protokolls oder der Mitteilung präzise im Kontext des Bauprojekts und generiere ein valides JSON-Objekt.
WICHTIGE REGELN:
1. summary: Sachliche, prägnante Zusammenfassung (max. 2-3 Sätze). Beschreibe neutral den baulichen/projektbezogenen Sachverhalt.
2. VERKNÜPFTE AUFGABE (HÖCHSTE PRIORITÄT):
   Falls dieser Journaleintrag mit einer bestehenden Aufgabe verknüpft ist (siehe 'DIREKT VERKNÜPFTE AUFGABE'):
   - Dieser Eintrag bezieht sich PRIMÄR auf genau diese verknüpfte Aufgabe!
   - Falls der Text die Erledigung, den Abschluss oder die Fertigstellung beschreibt (z.B. 'ersetzt', 'erledigt', 'kann abgeschlossen werden', 'fertiggestellt', 'in Betrieb', 'abgenommen', 'fertig'):
     -> Erzeuge zwingend ein 'complete_task' für diese verknüpfte Aufgabe (task_id: ID der verknüpften Aufgabe)!
     -> Erstelle in diesem Fall KEINE neue Aufgabe (create_task), sondern schliesse die verknüpfte Aufgabe ab!
   - Falls der Text Terminverschiebungen, Statusänderungen oder Details beschreibt:
     -> Erzeuge ein 'update_task' für diese verknüpfte Aufgabe.
3. ALLGEMEINE REGELN FÜR action_items:
   - type 'complete_task': 'task_id' (insb. die verknüpfte Aufgabe), 'reason': Grund für Abschluss.
   - type 'update_task': 'task_id', 'suggested_status', 'suggested_due_date', 'reason'.
   - type 'create_task': Nur falls KEINE passende bestehende/verknüpfte Aufgabe existiert und ein neuer Arbeitsschritt angelegt werden muss.
Gib AUSSCHLIESSLICH das JSON-Objekt zurück, ohne Markdown-Codeblock oder sonstige Erklärungen.`

  const userPrompt = `${linkedTaskContext}PROJEKT-ABSCHNITTE (SECTIONS):
${sectionsContext}

BESTEHENDE AUFGABEN (TASKS):
${tasksContext}

EINTRAGSTEXT:
"""
${emailText}
"""

Erzeuge das JSON im folgenden Format:
{
  "subject": "Treffender Titel",
  "summary": "Zusammenfassung in 2-3 Sätzen",
  "action_items": [
    { "type": "complete_task", "task_id": "task_id", "reason": "Abschlussgrund" },
    { "type": "update_task", "task_id": "task_id", "suggested_status": "in_progress", "suggested_due_date": null, "reason": "Begründung" },
    { "type": "create_task", "title": "Aufgabentitel", "section_id": "section_id", "priority": "normal", "due_date": null, "description": "Details" }
  ]
}`

  let aiResult: any = null
  const apiKey = getApiKey()
  if (apiKey) {
    try {
      const config = getAiConfig()
      const resp = await fetch('https://openrouter.ai/api/v1/chat/completions', {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
          'HTTP-Referer': 'https://taskster.kurka.ch',
          'X-Title': 'Taskster Project Journal'
        },
        body: JSON.stringify({
          model: config.model,
          messages: [
            { role: 'system', content: systemPrompt },
            { role: 'user', content: userPrompt }
          ],
          temperature: 0.2,
          max_tokens: 4096,
          provider: config.provider
        })
      })

      if (resp.ok) {
        const json = await resp.json()
        const text = json.choices?.[0]?.message?.content || ''
        const clean = text.replace(/^```(?:json)?\s*/i, '').replace(/```$/, '').trim()
        aiResult = JSON.parse(clean)
      }
    } catch (_) {}
  }

  if (!aiResult) {
    aiResult = {
      subject: emailSubject || 'E-Mail Import',
      summary: emailText.substring(0, 200) + '...',
      action_items: []
    }
  }

  const finalTitle = emailSubject || aiResult.subject || 'E-Mail Notiz'
  const summary = aiResult.summary || ''
  const actionItems = Array.isArray(aiResult.action_items) ? aiResult.action_items : []

  if (targetJournalId) {
    const existing = db.prepare(`SELECT metadata FROM project_journals WHERE id = ? AND project_id = ?`).get(targetJournalId, projectId) as any
    let existingMeta: any = {}
    if (existing?.metadata) {
      try {
        existingMeta = typeof existing.metadata === 'string' ? JSON.parse(existing.metadata) : existing.metadata
      } catch (_) {
        existingMeta = {}
      }
    }
    existingMeta.ai_summary = summary
    existingMeta.action_items = actionItems
    if (senderEmail) {
      existingMeta.sender = {
        name: senderName,
        email: senderEmail,
        role: senderRole
      }
    }

    db.prepare(`UPDATE project_journals SET metadata = ?, updated_at = datetime('now') WHERE id = ? AND project_id = ?`)
      .run(JSON.stringify(existingMeta), targetJournalId, projectId)

    return {
      success: true,
      metadata: existingMeta,
      summary,
      action_items: actionItems
    }
  }

  // 4. Persistierung als Notiz (type = note)
  const jrnId = 'jrn_' + randomUUID().substring(0, 8)
  const targetCategory = body.category || 'email'
  const targetVisibility = ['only_me', 'group', 'company', 'all'].includes(body.visibility) ? body.visibility : 'all'
  const targetAllowedGroup = body.allowed_group_id || null

  const metadata = {
    sender: {
      name: senderName,
      email: senderEmail,
      role: senderRole
    },
    recipients,
    ai_summary: summary,
    action_items: actionItems,
    raw_subject: emailSubject
  }

  db.prepare(`
    INSERT INTO project_journals (id, company_id, project_id, user_id, author_id, type, category, entry_type, title, content, visibility, allowed_group_id, metadata, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 'note', ?, ?, ?, ?, ?, ?, ?, datetime('now'), datetime('now'))
  `).run(
    jrnId,
    user.company_id || null,
    projectId,
    user.id,
    user.id,
    targetCategory,
    targetCategory === 'email' ? 'email' : 'note',
    finalTitle,
    emailText,
    targetVisibility,
    targetAllowedGroup,
    JSON.stringify(metadata)
  )

  const savedAttachments: any[] = []
  if (attachments.length > 0) {
    const attInsert = db.prepare(`
      INSERT INTO project_journal_attachments (id, journal_id, file_name, file_path, file_type, file_size, created_at)
      VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
    `)
    for (const att of attachments) {
      if (att.file_name && att.file_path) {
        const attId = 'pja_' + randomUUID().substring(0, 8)
        attInsert.run(
          attId,
          jrnId,
          att.file_name,
          att.file_path,
          att.file_type || 'application/octet-stream',
          Number(att.file_size || 0)
        )
        savedAttachments.push({
          id: attId,
          journal_id: jrnId,
          file_name: att.file_name,
          file_path: att.file_path,
          file_type: att.file_type || 'application/octet-stream',
          file_size: Number(att.file_size || 0),
          created_at: new Date().toISOString()
        })
      }
    }
  }

  return {
    success: true,
    entry: {
      id: jrnId,
      company_id: user.company_id || null,
      project_id: projectId,
      user_id: user.id,
      author_name: user.name,
      author_email: user.email,
      type: 'note',
      category: 'email',
      title: finalTitle,
      content: emailText,
      visibility: 'all',
      metadata,
      attachments: savedAttachments,
      attendees: [],
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString()
    }
  }
})

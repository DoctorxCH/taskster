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
    model: 'deepseek/deepseek-v4-flash-0731',
    provider: { only: ['baidu/fp8'], allow_fallbacks: false },
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

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const projectId = getRouterParam(event, 'id') as string
  const body = await readBody(event)

  evaluateProjectAccess(user, projectId, event, 'write')

  const emailText = (body.email_text || body.content || '').trim()
  if (!emailText) {
    throw createError({ statusCode: 400, statusMessage: 'E-Mail-Text erforderlich' })
  }

  const sender = body.sender || {}
  const recipients = Array.isArray(body.recipients) ? body.recipients : []
  let emailSubject = (body.subject || '').trim()
  const attachments = Array.isArray(body.attachments) ? body.attachments : []

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

  // 2. Projektkontext laden (Abschnitte & Aufgaben)
  const sections = db.prepare(`SELECT id, title FROM lists WHERE project_id = ? ORDER BY sort_order ASC`).all(projectId) as any[]
  const existingTasks = db.prepare(`
    SELECT t.id, t.list_id, t.title, t.status, t.due_date 
    FROM tasks t 
    JOIN lists l ON l.id = t.list_id 
    WHERE l.project_id = ?
  `).all(projectId) as any[]

  const sectionsContext = JSON.stringify(sections.map(s => ({ id: s.id, title: s.title })))
  const tasksContext = JSON.stringify(existingTasks.map(t => ({ id: t.id, section_id: t.list_id, title: t.title, status: t.status, due_date: t.due_date })))

  // 3. KI-Verarbeitung (OpenRouter / DeepSeek Engine)
  const systemPrompt = `Du bist ein intelligenter technischer Bauleiter-Assistent im System Taskster.
Analysiere den Inhalt der E-Mail präzise im Kontext des Bauprojekts und generiere ein valides JSON-Objekt.
Regeln:
1. summary: Sachliche, prägnante Zusammenfassung (max. 3-4 Sätze).
2. action_items: Liste relevanter Vorschläge basierend auf dem Mailtext:
   - type 'create_task': Falls eine neue Handlung, Bestellung, Mängelbehebung oder Frist nötig ist. 'section_id' MUSS einer der übergebenen Abschnitte sein. 'priority' ist 'normal', 'hoch' oder 'dringend'. 'due_date' im Format YYYY-MM-DD oder null.
   - type 'update_task': Falls eine bestehende Aufgabe aktualisiert werden muss (z.B. Terminverschiebung, Status). 'task_id' MUSS existieren.
   - type 'complete_task': Falls die E-Mail die Erledigung einer bestehenden Aufgabe bestätigt. 'task_id' MUSS existieren.
Gib AUSSCHLIESSLICH das JSON-Objekt zurück, ohne Markdown-Codeblock oder sonstige Erklärungen.`

  const userPrompt = `PROJEKT-ABSCHNITTE (SECTIONS):
${sectionsContext}

BESTEHENDE AUFGABEN (TASKS):
${tasksContext}

E-MAIL TEXT:
"""
${emailText}
"""

Erzeuge das JSON im folgenden Format:
{
  "subject": "Treffender Betreff",
  "summary": "Zusammenfassung in 3-4 Sätzen",
  "action_items": [
    { "type": "create_task", "title": "Aufgabentitel", "section_id": "section_id", "priority": "normal", "due_date": null, "description": "Details" },
    { "type": "update_task", "task_id": "task_id", "suggested_status": "in_progress", "suggested_due_date": null, "reason": "Begründung" },
    { "type": "complete_task", "task_id": "task_id", "reason": "Abschlussgrund" }
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

  // 4. Persistierung als Notiz (type = note, category = email)
  const jrnId = 'jrn_' + randomUUID().substring(0, 8)
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
    INSERT INTO project_journals (id, company_id, project_id, user_id, author_id, type, category, entry_type, title, content, visibility, metadata, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, 'note', 'email', 'email', ?, ?, 'all', ?, datetime('now'), datetime('now'))
  `).run(
    jrnId,
    user.company_id || null,
    projectId,
    user.id,
    user.id,
    finalTitle,
    emailText,
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

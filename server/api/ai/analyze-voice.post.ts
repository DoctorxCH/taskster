import { readFileSync, existsSync } from 'fs'
import { join } from 'path'
import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

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
    max_tokens: 2048,
    timeout_seconds: 60
  }
}

function getApiKey(): string {
  if (process.env.OPENROUTER_API_KEY) return process.env.OPENROUTER_API_KEY
  const candidates = [
    join(process.cwd(), '.env'),
    join(process.cwd(), '..', '.env'),
    join(__dirname, '..', '..', '..', '.env')
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
  const body = await readBody(event)
  const text = (body.text || '').trim()
  const currentProjectId = body.current_project_id || null

  if (!text) {
    throw createError({ statusCode: 400, statusMessage: 'Text erforderlich' })
  }

  // 1. Fetch user's accessible projects
  const projects = db.prepare(`
    SELECT DISTINCT p.id, p.title, p.description
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE pf.company_id = ? OR pf.user_id = ? OR p.visibility = 'public'
    ORDER BY p.is_default DESC, p.created_at DESC
    LIMIT 30
  `).all(user.company_id || '', user.id) as any[]

  // 2. Fetch accessible active tasks
  const projectIds = projects.map(p => p.id)
  let tasks: any[] = []
  if (projectIds.length > 0) {
    const placeholders = projectIds.map(() => '?').join(',')
    tasks = db.prepare(`
      SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
             t.checklist, t.list_id, l.title as list_title, p.id as project_id, p.title as project_title
      FROM tasks t
      JOIN lists l ON l.id = t.list_id
      JOIN projects p ON p.id = l.project_id
      WHERE p.id IN (${placeholders}) AND t.status != 'done'
      ORDER BY t.created_at DESC
      LIMIT 80
    `).all(...projectIds) as any[]
  }

  // Build context summary for AI
  const projectContext = projects.map(p => ({ id: p.id, title: p.title })).slice(0, 20)
  const taskContext = tasks.map(t => ({
    id: t.id,
    title: t.title,
    project_id: t.project_id,
    project_title: t.project_title,
    status: t.status,
    desc_snippet: (t.description || '').slice(0, 100)
  })).slice(0, 50)

  const apiKey = getApiKey()
  const config = getAiConfig()

  const systemPrompt = `Du bist der intelligente Sprachnotiz-Assistent von Taskster (Projekt- & Baustellenmanagement).
Deine Aufgabe: Analysiere den transkribierten Sprachnotiz-Text des Benutzers und erkenne, um welche Aufgabe oder welches Projekt es geht (z.B. nach Name, Nummer #, Adresse, Raum wie '2. OG', Bauteil wie 'Heizung', 'Elektro', etc.).

Kontext der vorhandenen Projekte:
${JSON.stringify(projectContext)}

Kontext der aktuell offenen Aufgaben:
${JSON.stringify(taskContext)}

Erkenne die Absicht des Nutzers:
- "complete_task": Der Nutzer sagt, dass eine Aufgabe erledigt, fertig, montiert, repariert oder abgeschlossen ist.
- "update_task": Der Nutzer hat Neuigkeiten, Status-Updates, Notizen oder Maße zu einer bestehenden Aufgabe.
- "add_checklist": Der Nutzer nennt Checklisten-Punkte oder Unteraufgaben, die gemacht werden müssen.
- "create_task": Der Nutzer beschreibt eine neue Aufgabe, die erledigt werden soll.
- "create_journal": Der Nutzer diktiert eine Notiz, Bautagebuch oder Beobachtung.

Antworte AUSSCHLIESSLICH als gültiges JSON-Objekt mit folgender Struktur:
{
  "summary": "1 prägnanter Satz auf Deutsch, was erkannt wurde (z.B. 'Aufgabe Heizkörper entlüften im Projekt Umbau erkannt.')",
  "intent": "complete_task" | "update_task" | "add_checklist" | "create_task" | "create_journal",
  "matched_task": { "id": "...", "title": "...", "project_id": "...", "project_title": "..." } | null,
  "matched_project": { "id": "...", "title": "..." } | null,
  "extracted_task_title": "Kurzer, prägnanter Titel für neue Aufgabe oder Update",
  "note_to_append": "Sauber formulierter Text aus der Sprachnotiz zum Anhängen",
  "checklist_items": ["Punkt 1", "Punkt 2"] // falls Checkliste/Schritte erkannt wurden, sonst leer [],
  "suggested_actions": [
    {
      "id": "action_key",
      "type": "update_task" | "complete_task" | "add_checklist" | "create_task" | "create_journal",
      "label": "Button-Text für den Benutzer (z.B. 'Aufgabe als erledigt markieren' oder 'Notiz an Aufgabe anhängen')",
      "description": "Kurze Erklärung für den Benutzer",
      "task_id": "...",
      "project_id": "..."
    }
  ]
}`

  let analysis: any = null

  if (apiKey) {
    try {
      const timeoutMs = (config.timeout_seconds || 45) * 1000
      const controller = new AbortController()
      const timer = setTimeout(() => controller.abort(), timeoutMs)

      const res = await fetch('https://openrouter.ai/api/v1/chat/completions', {
        method: 'POST',
        signal: controller.signal,
        headers: {
          Authorization: `Bearer ${apiKey}`,
          'Content-Type': 'application/json',
          'HTTP-Referer': 'https://taskster.ch',
          'X-Title': 'Taskster Voice Intent Analysis'
        },
        body: JSON.stringify({
          model: config.model || 'deepseek/deepseek-v4-flash-0731',
          messages: [
            { role: 'system', content: systemPrompt },
            { role: 'user', content: `Hier ist die Sprachnotiz des Nutzers:\n"${text}"\n\nAnalysiere und gib ausschliesslich das JSON-Objekt zurück.` }
          ],
          temperature: 0.1,
          max_tokens: 2048,
          response_format: { type: 'json_object' }
        })
      })

      clearTimeout(timer)

      if (res.ok) {
        const data = await res.json()
        const rawContent = data.choices?.[0]?.message?.content || ''
        const cleanJson = rawContent.replace(/^```json\s*/i, '').replace(/^```\s*/i, '').replace(/```$/i, '').trim()
        analysis = JSON.parse(cleanJson)
      }
    } catch (e) {
      console.warn('[Voice Analysis] OpenRouter AI error, using local heuristic:', e)
    }
  }

  // Fallback heuristic if AI call didn't complete
  if (!analysis) {
    const lowerText = text.toLowerCase()
    let matchedTask = null
    let matchedProject = null

    // Find task match
    for (const t of tasks) {
      const titleLower = t.title.toLowerCase()
      if (lowerText.includes(titleLower) || (titleLower.length > 5 && lowerText.includes(titleLower.slice(0, 8)))) {
        matchedTask = { id: t.id, title: t.title, project_id: t.project_id, project_title: t.project_title }
        matchedProject = { id: t.project_id, title: t.project_title }
        break
      }
    }

    if (!matchedProject && currentProjectId) {
      const cur = projects.find(p => p.id === currentProjectId)
      if (cur) matchedProject = { id: cur.id, title: cur.title }
    }

    const isDone = lowerText.includes('erledigt') || lowerText.includes('fertig') || lowerText.includes('abgeschlossen') || lowerText.includes('gemacht')
    const hasList = lowerText.includes('checkliste') || lowerText.includes('punkte') || lowerText.includes('schritte')

    let intent = 'create_journal'
    if (matchedTask && isDone) intent = 'complete_task'
    else if (matchedTask && hasList) intent = 'add_checklist'
    else if (matchedTask) intent = 'update_task'
    else if (lowerText.includes('aufgabe') || lowerText.includes('todo') || lowerText.includes('muss noch')) intent = 'create_task'

    const suggestedActions = []
    if (matchedTask) {
      if (isDone) {
        suggestedActions.push({
          id: 'complete_task',
          type: 'complete_task',
          label: `'${matchedTask.title}' als erledigt markieren`,
          description: 'Setzt den Status der Aufgabe auf Erledigt',
          task_id: matchedTask.id,
          project_id: matchedTask.project_id
        })
      }
      suggestedActions.push({
        id: 'update_task',
        type: 'update_task',
        label: `Notiz an '${matchedTask.title}' anhängen`,
        description: 'Ergänzt die Aufgabenbeschreibung um die Sprachnotiz',
        task_id: matchedTask.id,
        project_id: matchedTask.project_id
      })
    }

    if (matchedProject) {
      suggestedActions.push({
        id: 'create_task',
        type: 'create_task',
        label: `Neue Aufgabe in '${matchedProject.title}' erstellen`,
        description: 'Legt eine neue Aufgabe im Projekt an',
        project_id: matchedProject.id
      })
      suggestedActions.push({
        id: 'create_journal',
        type: 'create_journal',
        label: `Als Journal-Eintrag in '${matchedProject.title}' speichern`,
        description: 'Speichert die Aufnahme im Bautagebuch',
        project_id: matchedProject.id
      })
    } else {
      suggestedActions.push({
        id: 'create_journal',
        type: 'create_journal',
        label: 'Als persönliche Notiz speichern',
        description: 'Speichert die Aufnahme in deiner Notizablage'
      })
    }

    analysis = {
      summary: matchedTask ? `Aufgabe '${matchedTask.title}' erkannt.` : 'Sprachnotiz analysiert.',
      intent,
      matched_task: matchedTask,
      matched_project: matchedProject,
      extracted_task_title: text.length > 50 ? text.slice(0, 47) + '...' : text,
      note_to_append: text,
      checklist_items: [],
      suggested_actions: suggestedActions
    }
  }

  return {
    success: true,
    analysis
  }
})

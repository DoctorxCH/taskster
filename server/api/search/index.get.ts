import { db } from '~/server/db'
import { requireAuth } from '~/server/utils/auth'

/**
 * GET /api/search?q=<query>&limit=<n>
 *
 * Globale Suche über alle Entitäten, die der Nutzer sehen darf (Zero-Trust).
 * Durchsucht: Aufgaben, Projekte, Ordner, Listen/Abschnitte, Journaleinträge,
 * Tages-Todos, Team-Mitglieder, Vorlagen.
 *
 * Liefert gruppierte Treffer mit Kontext für die Command-Palette.
 */

interface SearchHit {
  id: string
  type: string
  title: string
  subtitle?: string
  context?: string
  status?: string
  priority?: string
  url: string
  icon: string
  score: number
}

/** Bewertet einen Treffer: exakter Anfang > Wortanfang > irgendwo im Text. */
function scoreMatch(text: string, query: string): number {
  if (!text) return 0
  const t = text.toLowerCase()
  const q = query.toLowerCase()
  if (t === q) return 100
  if (t.startsWith(q)) return 80
  // Wortanfang
  const words = t.split(/[\s\-_/.,;:()]+/)
  if (words.some((w) => w.startsWith(q))) return 60
  if (t.includes(q)) return 40
  return 0
}

export default defineEventHandler((event) => {
  const user = requireAuth(event)
  const query = getQuery(event)
  const q = String(query.q || '').trim()
  const limit = Math.min(Number(query.limit) || 8, 25)

  if (q.length < 1) {
    return { query: q, groups: [], total: 0 }
  }

  const like = `%${q}%`
  const companyId = user.company_id || '__none__'
  const hits: SearchHit[] = []

  // ---------------------------------------------------------------------
  // 1. AUFGABEN — nur in zugänglichen Projekten (eigener Ordner / Mitgliedschaft)
  //    Zusätzlich: Abschnitte mit access_mode='custom' nur für Owner/Admin
  //    oder explizit berechtigte Nutzer (Zero-Trust, wie evaluateListAccess).
  // ---------------------------------------------------------------------
  const tasks = db.prepare(`
    SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
           l.title AS list_title, l.access_mode AS list_access_mode,
           p.id AS project_id, p.title AS project_title,
           pf.id AS folder_id, pf.name AS folder_name, pf.owner_id AS folder_owner_id
    FROM tasks t
    JOIN lists l ON l.id = t.list_id
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE (t.title LIKE ? OR t.description LIKE ?)
      AND (
        pf.owner_id = ?
        OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
        OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
      )
    LIMIT 60
  `).all(like, like, user.id, user.id, companyId) as any[]

  // Sichtbarkeit für 'custom'-Abschnitte einmalig laden
  const visibleListIds = new Set<string>(
    (db.prepare('SELECT list_id FROM list_access WHERE user_id = ? AND is_visible = 1')
      .all(user.id) as any[]).map((r) => r.list_id)
  )

  for (const t of tasks) {
    // Zero-Trust: 'custom'-Abschnitt nur für Owner/Admin oder explizit Freigegebene
    if (t.list_access_mode === 'custom') {
      const isOwner = t.folder_owner_id === user.id
      if (!isOwner && !visibleListIds.has(t.id)) {
        // Prüfen, ob der Nutzer Projekt-Admin ist
        const isProjectAdmin = db.prepare(`
          SELECT 1 FROM project_members
          WHERE project_id = ? AND user_id = ? AND role IN ('owner', 'admin')
        `).get(t.project_id, user.id)
        if (!isProjectAdmin) continue
      }
    }

    const score = Math.max(scoreMatch(t.title, q), scoreMatch(t.description || '', q) * 0.6)
    if (score <= 0) continue
    hits.push({
      id: t.id,
      type: 'task',
      title: t.title,
      subtitle: t.description ? String(t.description).slice(0, 90) : undefined,
      context: `${t.folder_name} › ${t.project_title} › ${t.list_title}`,
      status: t.status,
      priority: t.priority,
      url: `/projects/${t.project_id}?task=${t.id}`,
      icon: 'ClipboardList',
      score: score + 5
    })
  }

  // ---------------------------------------------------------------------
  // 2. PROJEKTE
  // ---------------------------------------------------------------------
  const projects = db.prepare(`
    SELECT p.id, p.title, p.status, pf.name AS folder_name, pf.id AS folder_id
    FROM projects p
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE p.title LIKE ?
      AND (
        pf.owner_id = ?
        OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
        OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
      )
    LIMIT 40
  `).all(like, user.id, user.id, companyId) as any[]

  for (const p of projects) {
    const score = scoreMatch(p.title, q)
    if (score <= 0) continue
    hits.push({
      id: p.id,
      type: 'project',
      title: p.title,
      context: p.folder_name,
      status: p.status,
      url: `/projects/${p.id}`,
      icon: 'FolderKanban',
      score: score + 3
    })
  }

  // ---------------------------------------------------------------------
  // 3. ORDNER
  // ---------------------------------------------------------------------
  const folders = db.prepare(`
    SELECT pf.id, pf.name, pf.visibility, u.name AS owner_name
    FROM project_folders pf
    JOIN users u ON u.id = pf.owner_id
    WHERE pf.name LIKE ?
      AND (
        pf.owner_id = ?
        OR pf.company_id = ?
        OR pf.id IN (
          SELECT p.folder_id FROM projects p
          JOIN project_members pm ON pm.project_id = p.id
          WHERE pm.user_id = ?
        )
      )
    LIMIT 30
  `).all(like, user.id, companyId, user.id) as any[]

  for (const f of folders) {
    const score = scoreMatch(f.name, q)
    if (score <= 0) continue
    hits.push({
      id: f.id,
      type: 'folder',
      title: f.name,
      context: f.owner_name,
      url: `/folders/${f.id}`,
      icon: 'Folder',
      score: score + 2
    })
  }

  // ---------------------------------------------------------------------
  // 4. ABSCHNITTE / LISTEN (Zero-Trust: 'custom' nur für Berechtigte)
  // ---------------------------------------------------------------------
  const lists = db.prepare(`
    SELECT l.id, l.title, l.access_mode, p.id AS project_id, p.title AS project_title,
           pf.owner_id AS folder_owner_id
    FROM lists l
    JOIN projects p ON p.id = l.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE l.title LIKE ?
      AND (
        pf.owner_id = ?
        OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
        OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
      )
    LIMIT 30
  `).all(like, user.id, user.id, companyId) as any[]

  for (const l of lists) {
    if (l.access_mode === 'custom') {
      const isOwner = l.folder_owner_id === user.id
      if (!isOwner && !visibleListIds.has(l.id)) {
        const isProjectAdmin = db.prepare(`
          SELECT 1 FROM project_members
          WHERE project_id = ? AND user_id = ? AND role IN ('owner', 'admin')
        `).get(l.project_id, user.id)
        if (!isProjectAdmin) continue
      }
    }
    const score = scoreMatch(l.title, q)
    if (score <= 0) continue
    hits.push({
      id: l.id,
      type: 'list',
      title: l.title,
      context: l.project_title,
      url: `/projects/${l.project_id}`,
      icon: 'Columns3',
      score: score
    })
  }

  // ---------------------------------------------------------------------
  // 5. JOURNAL-EINTRÄGE
  // ---------------------------------------------------------------------
  const journals = db.prepare(`
    SELECT j.id, j.title, j.content, j.entry_type, p.id AS project_id, p.title AS project_title
    FROM project_journals j
    JOIN projects p ON p.id = j.project_id
    JOIN project_folders pf ON pf.id = p.folder_id
    WHERE (j.title LIKE ? OR j.content LIKE ?)
      AND (
        pf.owner_id = ?
        OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
        OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
      )
    LIMIT 30
  `).all(like, like, user.id, user.id, companyId) as any[]

  for (const j of journals) {
    const score = Math.max(scoreMatch(j.title, q), scoreMatch(j.content || '', q) * 0.5)
    if (score <= 0) continue
    hits.push({
      id: j.id,
      type: 'journal',
      title: j.title,
      subtitle: String(j.content || '').slice(0, 90),
      context: j.project_title,
      url: `/projects/${j.project_id}?view=journal`,
      icon: 'FileText',
      score: score
    })
  }

  // ---------------------------------------------------------------------
  // 6. TAGES-TODOS (nur eigene)
  // ---------------------------------------------------------------------
  const todos = db.prepare(`
    SELECT dt.id, dt.title, dt.is_completed, dt.target_date, p.title AS project_title
    FROM daily_todos dt
    LEFT JOIN projects p ON p.id = dt.project_id
    WHERE dt.user_id = ? AND dt.title LIKE ?
    LIMIT 20
  `).all(user.id, like) as any[]

  for (const t of todos) {
    const score = scoreMatch(t.title, q)
    if (score <= 0) continue
    hits.push({
      id: t.id,
      type: 'todo',
      title: t.title,
      context: t.project_title || 'Persönlich',
      status: t.is_completed ? 'done' : 'todo',
      url: '/dashboard',
      icon: 'Sun',
      score: score
    })
  }

  // ---------------------------------------------------------------------
  // 7. TEAM-MITGLIEDER (Firma + Projektmitglieder)
  // ---------------------------------------------------------------------
  const members = db.prepare(`
    SELECT DISTINCT u.id, u.name, u.email, u.company_role
    FROM users u
    WHERE (u.name LIKE ? OR u.email LIKE ?)
      AND (
        u.id = ?
        OR (u.company_id IS NOT NULL AND u.company_id = ?)
        OR u.id IN (
          SELECT pm.user_id FROM project_members pm
          JOIN projects p ON p.id = pm.project_id
          JOIN project_folders pf ON pf.id = p.folder_id
          WHERE pf.owner_id = ?
        )
      )
    LIMIT 20
  `).all(like, like, user.id, companyId, user.id) as any[]

  for (const m of members) {
    const score = Math.max(scoreMatch(m.name, q), scoreMatch(m.email, q) * 0.8)
    if (score <= 0) continue
    hits.push({
      id: m.id,
      type: 'member',
      title: m.name,
      subtitle: m.email,
      context: m.company_role === 'admin' ? 'Co-Admin' : 'Mitarbeiter',
      url: '/company',
      icon: 'User',
      score: score
    })
  }

  // ---------------------------------------------------------------------
  // 8. VORLAGEN (System + eigene Firma)
  // ---------------------------------------------------------------------
  const templates = db.prepare(`
    SELECT id, name, description, category, is_system
    FROM project_templates
    WHERE (name LIKE ? OR description LIKE ?)
      AND (is_system = 1 OR company_id = ?)
    LIMIT 20
  `).all(like, like, companyId) as any[]

  for (const t of templates) {
    const score = Math.max(scoreMatch(t.name, q), scoreMatch(t.description || '', q) * 0.5)
    if (score <= 0) continue
    hits.push({
      id: t.id,
      type: 'template',
      title: t.name,
      subtitle: t.description ? String(t.description).slice(0, 90) : undefined,
      context: t.is_system ? 'Systemvorlage' : 'Firmenvorlage',
      url: '/dashboard',
      icon: 'LayoutTemplate',
      score: score
    })
  }

  // ---------------------------------------------------------------------
  // Gruppieren & sortieren
  // ---------------------------------------------------------------------
  const TYPE_ORDER = ['task', 'project', 'folder', 'list', 'journal', 'todo', 'member', 'template']
  const TYPE_LABELS: Record<string, string> = {
    task: 'Aufgaben',
    project: 'Projekte',
    folder: 'Ordner',
    list: 'Abschnitte',
    journal: 'Journal',
    todo: 'Tages-Todos',
    member: 'Team',
    template: 'Vorlagen'
  }

  const groups = TYPE_ORDER
    .map((type) => {
      const items = hits
        .filter((h) => h.type === type)
        .sort((a, b) => b.score - a.score)
        .slice(0, limit)
      return { type, label: TYPE_LABELS[type], items }
    })
    .filter((g) => g.items.length > 0)

  return {
    query: q,
    total: hits.length,
    groups
  }
})

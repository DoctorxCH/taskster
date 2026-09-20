import Database from 'better-sqlite3'
import { join } from 'path'
import { readFileSync, existsSync, mkdirSync } from 'fs'
import { defaultProjectTemplates } from './default-templates'

const dataDir = join(process.cwd(), '.data')
if (!existsSync(dataDir)) {
  mkdirSync(dataDir, { recursive: true })
}

const dbPath = join(dataDir, 'taskster.db')
export const db = new Database(dbPath)

// Enable WAL mode for high performance and foreign keys
db.pragma('journal_mode = WAL')
db.pragma('foreign_keys = ON')

// Initialize schema
export function initDatabase() {
  const schemaPath = join(process.cwd(), 'server', 'db', 'schema.sql')
  const schemaSql = readFileSync(schemaPath, 'utf8')
  db.exec(schemaSql)

  // Idempotent column migrations
  const columnMigrations: string[] = [
    "ALTER TABLE project_folders ADD COLUMN icon TEXT DEFAULT '📁'",
    "ALTER TABLE tasks ADD COLUMN assigned_to TEXT",
    "ALTER TABLE tasks ADD COLUMN priority TEXT DEFAULT 'normal'",
    "ALTER TABLE tasks ADD COLUMN color TEXT",
    "ALTER TABLE tasks ADD COLUMN tags TEXT DEFAULT '[]'",
    "ALTER TABLE tasks ADD COLUMN checklist TEXT DEFAULT '[]'",
    "ALTER TABLE tasks ADD COLUMN budget_hours REAL DEFAULT 0",
    "ALTER TABLE tasks ADD COLUMN budget_amount REAL DEFAULT 0",
    "ALTER TABLE lists ADD COLUMN color TEXT DEFAULT NULL",
    "ALTER TABLE users ADD COLUMN hourly_rate REAL DEFAULT 0",
    "ALTER TABLE users ADD COLUMN currency TEXT DEFAULT 'CHF'",
    "ALTER TABLE projects ADD COLUMN currency TEXT DEFAULT 'CHF'",
    "ALTER TABLE projects ADD COLUMN budget_hours REAL DEFAULT 0",
    "ALTER TABLE projects ADD COLUMN budget_amount REAL DEFAULT 0",
    "ALTER TABLE users ADD COLUMN admin_permissions TEXT DEFAULT '[]'",
    "ALTER TABLE project_folders ADD COLUMN visibility TEXT NOT NULL DEFAULT 'private'",
    "ALTER TABLE projects ADD COLUMN visibility TEXT NOT NULL DEFAULT 'private'",
    "ALTER TABLE projects ADD COLUMN is_default INTEGER NOT NULL DEFAULT 0",
    "ALTER TABLE projects ADD COLUMN custom_data TEXT NOT NULL DEFAULT '{}'",
    "ALTER TABLE users ADD COLUMN settings TEXT NOT NULL DEFAULT '{}'",
    "ALTER TABLE contacts ADD COLUMN latitude REAL",
    "ALTER TABLE contacts ADD COLUMN longitude REAL",
    "ALTER TABLE calendar_events ADD COLUMN latitude REAL",
    "ALTER TABLE calendar_events ADD COLUMN longitude REAL",
  ]
  for (const sql of columnMigrations) {
    try { db.exec(sql) } catch (_) { /* column already exists */ }
  }

  // ROOT-CAUSE-FIX: Company Admins duerfen KEINE Plattform-admin_permissions haben.
  // Sie verwalten ihre Firma ueber company_role === 'admin' im /company Portal.
  try {
    db.exec("UPDATE users SET admin_permissions = '[]' WHERE is_superadmin = 0 AND company_role = 'admin'")
  } catch (_) { /* ignore */ }

  // New tables (idempotent via IF NOT EXISTS in schema)
  db.exec(`
    CREATE TABLE IF NOT EXISTS task_comments (
      id TEXT PRIMARY KEY,
      task_id TEXT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
      author_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      content TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS task_subtasks (
      id TEXT PRIMARY KEY,
      task_id TEXT NOT NULL REFERENCES tasks(id) ON DELETE CASCADE,
      title TEXT NOT NULL,
      is_done INTEGER NOT NULL DEFAULT 0,
      sort_order INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS time_entries (
      id TEXT PRIMARY KEY,
      project_id TEXT NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
      task_id TEXT REFERENCES tasks(id) ON DELETE SET NULL,
      user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      duration_minutes INTEGER NOT NULL,
      hourly_rate REAL NOT NULL DEFAULT 0,
      currency TEXT NOT NULL DEFAULT 'CHF',
      description TEXT,
      entry_date TEXT NOT NULL,
      is_manual INTEGER NOT NULL DEFAULT 1,
      started_at TEXT,
      ended_at TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT
    );

    CREATE TABLE IF NOT EXISTS company_invitations (
      id TEXT PRIMARY KEY,
      company_id TEXT NOT NULL REFERENCES companies(id) ON DELETE CASCADE,
      email TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'member',
      token TEXT NOT NULL,
      invited_by TEXT REFERENCES users(id) ON DELETE SET NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS notifications (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      type TEXT NOT NULL DEFAULT 'system',
      title TEXT NOT NULL,
      message TEXT,
      reference_type TEXT,
      reference_id TEXT,
      project_id TEXT,
      is_read INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS contacts (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      company_id TEXT REFERENCES companies(id) ON DELETE SET NULL,
      project_id TEXT REFERENCES projects(id) ON DELETE SET NULL,
      first_name TEXT,
      last_name TEXT NOT NULL,
      company_name TEXT,
      role_function TEXT,
      phone TEXT,
      mobile TEXT,
      email TEXT,
      category_group TEXT,
      address TEXT,
      website TEXT,
      tags TEXT DEFAULT '[]',
      notes TEXT,
      share_scope TEXT NOT NULL DEFAULT 'private',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT
    );

    -- Kalender / Termine
    CREATE TABLE IF NOT EXISTS event_categories (
      id TEXT PRIMARY KEY,
      company_id TEXT REFERENCES companies(id) ON DELETE CASCADE,
      owner_id TEXT REFERENCES users(id) ON DELETE CASCADE,
      name TEXT NOT NULL,
      color TEXT NOT NULL DEFAULT '#0891B2',
      icon TEXT DEFAULT 'Calendar',
      is_system INTEGER NOT NULL DEFAULT 0,
      sort_order INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS calendar_events (
      id TEXT PRIMARY KEY,
      owner_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      company_id TEXT REFERENCES companies(id) ON DELETE CASCADE,
      project_id TEXT REFERENCES projects(id) ON DELETE SET NULL,
      task_id TEXT REFERENCES tasks(id) ON DELETE SET NULL,
      category_id TEXT REFERENCES event_categories(id) ON DELETE SET NULL,
      title TEXT NOT NULL,
      description TEXT,
      location TEXT,
      start_at TEXT NOT NULL,
      end_at TEXT NOT NULL,
      all_day INTEGER NOT NULL DEFAULT 0,
      priority TEXT NOT NULL DEFAULT 'normal',
      status TEXT NOT NULL DEFAULT 'confirmed',
      visibility TEXT NOT NULL DEFAULT 'private',
      color TEXT,
      recurrence TEXT,
      reminder_minutes INTEGER,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS event_attendees (
      id TEXT PRIMARY KEY,
      event_id TEXT NOT NULL REFERENCES calendar_events(id) ON DELETE CASCADE,
      user_id TEXT REFERENCES users(id) ON DELETE CASCADE,
      email TEXT NOT NULL,
      name TEXT,
      role TEXT NOT NULL DEFAULT 'required',
      status TEXT NOT NULL DEFAULT 'pending',
      is_organizer INTEGER NOT NULL DEFAULT 0,
      responded_at TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      UNIQUE(event_id, email)
    );

    CREATE TABLE IF NOT EXISTS event_reminders (
      id TEXT PRIMARY KEY,
      event_id TEXT NOT NULL REFERENCES calendar_events(id) ON DELETE CASCADE,
      user_id TEXT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
      minutes_before INTEGER NOT NULL DEFAULT 15,
      sent_at TEXT,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS system_settings (
      key TEXT PRIMARY KEY,
      value TEXT NOT NULL,
      updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS email_templates (
      id TEXT PRIMARY KEY,
      trigger_event TEXT NOT NULL UNIQUE,
      name TEXT NOT NULL,
      subject TEXT NOT NULL,
      body_html TEXT NOT NULL,
      body_text TEXT NOT NULL,
      is_active INTEGER NOT NULL DEFAULT 1,
      variables TEXT NOT NULL DEFAULT '[]',
      updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    CREATE TABLE IF NOT EXISTS email_outbox (
      id TEXT PRIMARY KEY,
      to_email TEXT NOT NULL,
      to_name TEXT,
      subject TEXT NOT NULL,
      body TEXT NOT NULL,
      ics_content TEXT,
      status TEXT NOT NULL DEFAULT 'pending',
      error TEXT,
      attempts INTEGER NOT NULL DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      sent_at TEXT
    );
  `)

  // Standard-Kategorien einmalig anlegen
  try {
    const catCount = (db.prepare('SELECT COUNT(*) as c FROM event_categories WHERE is_system = 1').get() as any).c
    if (catCount === 0) {
      const defaults = [
        { id: 'cat_meeting', name: 'Besprechung', color: '#0891B2', icon: 'Users', sort: 1 },
        { id: 'cat_site', name: 'Baustelle', color: '#D97706', icon: 'HardHat', sort: 2 },
        { id: 'cat_deadline', name: 'Frist / Termin', color: '#DC2626', icon: 'AlertTriangle', sort: 3 },
        { id: 'cat_travel', name: 'Reise / Fahrt', color: '#7C3AED', icon: 'Car', sort: 4 },
        { id: 'cat_vacation', name: 'Ferien / Abwesenheit', color: '#059669', icon: 'Palmtree', sort: 5 },
        { id: 'cat_training', name: 'Schulung', color: '#2563EB', icon: 'GraduationCap', sort: 6 },
        { id: 'cat_private', name: 'Privat', color: '#64748B', icon: 'Home', sort: 7 },
      ]
      const ins = db.prepare(`
        INSERT INTO event_categories (id, company_id, owner_id, name, color, icon, is_system, sort_order)
        VALUES (?, NULL, NULL, ?, ?, ?, 1, ?)
      `)
      for (const d of defaults) ins.run(d.id, d.name, d.color, d.icon, d.sort)
    }
  } catch (_) { /* ignore */ }

  // Standard-System-Settings (SMTP noreply@kurka.ch) einmalig anlegen
  try {
    const smtpDefaults: Record<string, string> = {
      smtp_host: 'mail.kurka.ch',
      smtp_port: '465',
      smtp_secure: 'ssl',
      smtp_user: 'noreply@kurka.ch',
      smtp_password: 'Ckeesjb6&M',
      smtp_from_email: 'noreply@kurka.ch',
      smtp_from_name: 'Taskster'
    }
    const insSetting = db.prepare('INSERT OR IGNORE INTO system_settings (key, value) VALUES (?, ?)')
    for (const [k, v] of Object.entries(smtpDefaults)) {
      insSetting.run(k, v)
    }
  } catch (_) { /* ignore */ }

  // Standard-E-Mail-Vorlagen einmalig anlegen
  try {
    const defaultTemplates = [
      {
        id: 'tmpl_task_assigned',
        trigger_event: 'task_assigned',
        name: 'Aufgabe zugewiesen',
        subject: '[Taskster] Neue Aufgabe: {{task_title}}',
        variables: JSON.stringify(['user_name', 'task_title', 'project_title', 'assigned_by', 'due_date', 'action_url']),
        body_text: "Hallo {{user_name}},\n\nDir wurde die Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" zugewiesen.\nFälligkeitsdatum: {{due_date}}\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-bottom: 16px;">Neue Aufgabe zugewiesen</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p>Dir wurde eine neue Aufgabe zugewiesen:</p>
          <div style="background: #f8fafc; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
            <div style="font-size: 16px; font-weight: bold; color: #0f172a;">{{task_title}}</div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Projekt: {{project_title}}</div>
            <div style="font-size: 13px; color: #64748b;">Fällig am: {{due_date}}</div>
          </div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Aufgabe öffnen</a></p>
        </div>`
      },
      {
        id: 'tmpl_task_due',
        trigger_event: 'task_due',
        name: 'Aufgabe fällig',
        subject: '[Taskster] Erinnerung: Aufgabe {{task_title}} ist fällig',
        variables: JSON.stringify(['user_name', 'task_title', 'project_title', 'due_date', 'action_url']),
        body_text: "Hallo {{user_name}},\n\nDie Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" ist heute bzw. bald fällig ({{due_date}}).\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #ea580c; margin-bottom: 16px;">Aufgabe ist fällig</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p>Die folgende Aufgabe erfordert deine Aufmerksamkeit:</p>
          <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 12px; margin: 16px 0;">
            <div style="font-size: 16px; font-weight: bold; color: #9a3412;">{{task_title}}</div>
            <div style="font-size: 13px; color: #7c2d12; margin-top: 4px;">Projekt: {{project_title}} | Fällig: {{due_date}}</div>
          </div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #ea580c; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Jetzt bearbeiten</a></p>
        </div>`
      },
      {
        id: 'tmpl_task_comment',
        trigger_event: 'task_comment',
        name: 'Neuer Aufgaben-Kommentar',
        subject: '[Taskster] Neuer Kommentar zu {{task_title}}',
        variables: JSON.stringify(['user_name', 'author_name', 'task_title', 'comment_content', 'action_url']),
        body_text: "Hallo {{user_name}},\n\n{{author_name}} hat einen Kommentar zu \"{{task_title}}\" verfasst:\n\n\"{{comment_content}}\"\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-bottom: 16px;">Neuer Kommentar</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p><strong>{{author_name}}</strong> hat zu <em>{{task_title}}</em> geschrieben:</p>
          <blockquote style="background: #f8fafc; border-left: 4px solid #cbd5e1; padding: 10px 14px; margin: 14px 0; font-style: italic;">{{comment_content}}</blockquote>
          <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kommentar ansehen & antworten</a></p>
        </div>`
      },
      {
        id: 'tmpl_calendar_invite',
        trigger_event: 'calendar_invite',
        name: 'Termineinladung',
        subject: '[Taskster] Termineinladung: {{event_title}}',
        variables: JSON.stringify(['user_name', 'inviter_name', 'event_title', 'event_start', 'event_end', 'event_location', 'action_url']),
        body_text: "Hallo {{user_name}},\n\n{{inviter_name}} hat dich zu folgendem Termin eingeladen:\n\nTermin: {{event_title}}\nZeit: {{event_start}} bis {{event_end}}\nOrt: {{event_location}}\n\nZum Termin: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-bottom: 16px;">Termineinladung</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p><strong>{{inviter_name}}</strong> hat dich zu einem Termin eingeladen:</p>
          <div style="background: #f0fdfa; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
            <div style="font-size: 16px; font-weight: bold; color: #134e4a;">{{event_title}}</div>
            <div style="font-size: 13px; color: #115e59; margin-top: 4px;">📅 {{event_start}} - {{event_end}}</div>
            <div style="font-size: 13px; color: #115e59;">📍 {{event_location}}</div>
          </div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Termin im Kalender öffnen</a></p>
        </div>`
      },
      {
        id: 'tmpl_calendar_reminder',
        trigger_event: 'calendar_reminder',
        name: 'Terminerinnerung',
        subject: '[Taskster] Erinnerung: {{event_title}}',
        variables: JSON.stringify(['user_name', 'event_title', 'event_start', 'event_location', 'action_url']),
        body_text: "Hallo {{user_name}},\n\nErinnerung an deinen bevorstehenden Termin:\n\n{{event_title}}\nBeginn: {{event_start}}\nOrt: {{event_location}}\n\nZum Kalender: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #0284c7; margin-bottom: 16px;">Terminerinnerung</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p>Dein Termin beginnt in Kürze:</p>
          <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; margin: 16px 0;">
            <div style="font-size: 16px; font-weight: bold; color: #0369a1;">{{event_title}}</div>
            <div style="font-size: 13px; color: #0284c7; margin-top: 4px;">⏰ {{event_start}} | 📍 {{event_location}}</div>
          </div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #0284c7; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kalender anzeigen</a></p>
        </div>`
      },
      {
        id: 'tmpl_mention',
        trigger_event: 'mention',
        name: 'Erwähnung (@Name)',
        subject: '[Taskster] {{author_name}} hat dich erwähnt',
        variables: JSON.stringify(['user_name', 'author_name', 'context_title', 'mention_text', 'action_url']),
        body_text: "Hallo {{user_name}},\n\n{{author_name}} hat dich in \"{{context_title}}\" erwähnt:\n\n\"{{mention_text}}\"\n\nÖffnen: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #7c3aed; margin-bottom: 16px;">Du wurdest erwähnt</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p><strong>{{author_name}}</strong> hat dich in <em>{{context_title}}</em> erwähnt:</p>
          <div style="background: #faf5ff; border-left: 4px solid #7c3aed; padding: 12px; margin: 16px 0; font-style: italic;">{{mention_text}}</div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #7c3aed; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Zur Notiz / Aufgabe</a></p>
        </div>`
      },
      {
        id: 'tmpl_budget_warning',
        trigger_event: 'budget_warning',
        name: 'Budgetwarnung',
        subject: '[Taskster] Budget-Warnung: {{project_title}}',
        variables: JSON.stringify(['user_name', 'project_title', 'budget_percent', 'tracked_hours', 'budget_hours', 'action_url']),
        body_text: "Hallo {{user_name}},\n\nDas Projekt \"{{project_title}}\" hat {{budget_percent}}% des geplanten Budgets erreicht ({{tracked_hours}} von {{budget_hours}} Stunden gebucht).\n\nDetails: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #dc2626; margin-bottom: 16px;">Budgetwarnung</h2>
          <p>Hallo <strong>{{user_name}}</strong>,</p>
          <p>Das Projekt <strong>{{project_title}}</strong> hat die Budgetgrenze erreicht:</p>
          <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; margin: 16px 0;">
            <div style="font-size: 16px; font-weight: bold; color: #991b1b;">{{budget_percent}}% verbraucht</div>
            <div style="font-size: 13px; color: #b91c1c; margin-top: 4px;">{{tracked_hours}} von {{budget_hours}} Std. erfasst</div>
          </div>
          <p><a href="{{action_url}}" style="display: inline-block; background: #dc2626; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Controlling ansehen</a></p>
        </div>`
      },
      {
        id: 'tmpl_company_invite',
        trigger_event: 'company_invite',
        name: 'Unternehmen-Einladung',
        subject: 'Einladung zu {{company_name}} auf Taskster',
        variables: JSON.stringify(['inviter_name', 'company_name', 'invite_link']),
        body_text: "Hallo,\n\n{{inviter_name}} hat dich eingeladen, dem Unternehmen \"{{company_name}}\" auf Taskster beizutreten.\n\nKlicke auf den folgenden Link, um deine Registrierung abzuschliessen:\n{{invite_link}}\n\nBeste Grüsse,\nDein Taskster Team",
        body_html: `<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-bottom: 16px;">Willkommen bei Taskster</h2>
          <p>Hallo,</p>
          <p><strong>{{inviter_name}}</strong> hat dich eingeladen, dem Unternehmen <strong>{{company_name}}</strong> auf Taskster beizutreten.</p>
          <p style="margin: 24px 0;"><a href="{{invite_link}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold;">Einladung annehmen & registrieren</a></p>
          <p style="font-size: 12px; color: #64748b;">Oder kopiere diesen Link in deinen Browser:<br><span style="font-family: monospace; color: #0f172a;">{{invite_link}}</span></p>
        </div>`
      }
    ]

    const insTmpl = db.prepare(`
      INSERT OR IGNORE INTO email_templates (id, trigger_event, name, subject, variables, body_text, body_html, is_active)
      VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    `)
    for (const t of defaultTemplates) {
      insTmpl.run(t.id, t.trigger_event, t.name, t.subject, t.variables, t.body_text, t.body_html)
    }

    // Standard-Projektvorlagen anlegen / aktualisieren
    const insProjTmpl = db.prepare(`
      INSERT INTO project_templates (id, name, category, subcategory, description, icon, is_system, lists, fields)
      VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
      ON CONFLICT(id) DO UPDATE SET
        name = excluded.name,
        category = excluded.category,
        subcategory = excluded.subcategory,
        description = excluded.description,
        icon = excluded.icon,
        is_system = 1,
        lists = excluded.lists,
        fields = excluded.fields
    `)
    for (const pt of defaultProjectTemplates) {
      insProjTmpl.run(
        pt.id,
        pt.name,
        pt.category,
        pt.subcategory,
        pt.description,
        pt.icon,
        JSON.stringify(pt.lists),
        JSON.stringify(pt.fields)
      )
    }
  } catch (_) { /* ignore */ }
}

initDatabase()

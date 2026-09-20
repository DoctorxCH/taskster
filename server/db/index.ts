import Database from 'better-sqlite3'
import { join } from 'path'
import { readFileSync, existsSync, mkdirSync } from 'fs'

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
}

initDatabase()

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
  ]
  for (const sql of columnMigrations) {
    try { db.exec(sql) } catch (_) { /* column already exists */ }
  }

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
  `)
}

initDatabase()

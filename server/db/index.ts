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

  try {
    db.exec("ALTER TABLE project_folders ADD COLUMN icon TEXT DEFAULT '📁'")
  } catch (e) {
    // Column already exists
  }
}

initDatabase()

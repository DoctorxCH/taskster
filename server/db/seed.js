import Database from 'better-sqlite3'
import bcrypt from 'bcryptjs'
import { join } from 'path'
import { existsSync, mkdirSync, readFileSync } from 'fs'

const dataDir = join(process.cwd(), '.data')
if (!existsSync(dataDir)) {
  mkdirSync(dataDir, { recursive: true })
}

const db = new Database(join(dataDir, 'taskster.db'))
db.pragma('journal_mode = WAL')
db.pragma('foreign_keys = ON')

// Ensure schema
const schemaSql = readFileSync(join(process.cwd(), 'server', 'db', 'schema.sql'), 'utf8')
db.exec(schemaSql)

console.log('Seeding Taskster database...')

const passwordHash = bcrypt.hashSync('password123', 10)

// 1. Create Company
const companyId = 'comp-swiss-infra-01'
db.prepare(`
  INSERT OR REPLACE INTO companies (id, name, subscription_plan, settings, created_at)
  VALUES (?, ?, ?, ?, datetime('now'))
`).run(
  companyId,
  'Swisscom Infra Partner AG',
  'enterprise',
  JSON.stringify({
    allow_document_upload: true,
    require_2fa: false,
    max_seats: 50,
    compliance_strict: true,
    regional_data_residency: 'CH'
  })
)

// 2. Create Users
const superadminId = 'user-superadmin-01'
const companyAdminId = 'user-marc-01'
const editorId = 'user-sarah-02'
const viewerId = 'user-lukas-03'
const freeUserId = 'user-peter-free-04'

const insertUser = db.prepare(`
  INSERT OR REPLACE INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
`)

insertUser.run(superadminId, null, null, 1, 1, 'Taskster Admin', 'admin@kurka.ch', passwordHash)
insertUser.run(companyAdminId, companyId, 'admin', 0, 1, 'Marc Steiner (Bauleitung)', 'marc@kurka.ch', passwordHash)
insertUser.run(editorId, companyId, 'member', 0, 1, 'Sarah Keller (Projektleitung)', 'sarah.editor@kurka.ch', passwordHash)
insertUser.run(viewerId, null, null, 0, 0, 'Lukas Frey (Subunternehmer)', 'lukas.viewer@kurka.ch', passwordHash)
insertUser.run(freeUserId, null, null, 0, 0, 'Peter Muster (Free Plan)', 'peter@muster.ch', passwordHash)

// 3. Project Folder
const folderId = 'folder-limmattal-01'
db.prepare(`
  INSERT OR REPLACE INTO project_folders (id, owner_id, company_id, name)
  VALUES (?, ?, ?, ?)
`).run(folderId, companyAdminId, companyId, 'Limmattal FTTH Glasfaser Rollout')

// Free user folder
const freeFolderId = 'folder-free-peter-01'
db.prepare(`
  INSERT OR REPLACE INTO project_folders (id, owner_id, company_id, name)
  VALUES (?, ?, ?, ?)
`).run(freeFolderId, freeUserId, null, 'Peters Privates Renovationsprojekt')

// 4. Folder Custom Field Definitions
const insertField = db.prepare(`
  INSERT OR REPLACE INTO folder_field_definitions (id, folder_id, field_key, label, field_type, is_pro_only, options, is_required, sort_order)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
`)

insertField.run('field-gewerk', folderId, 'gewerk', 'Gewerk / Bereich', 'select', 0, JSON.stringify(['LWL-Spleissen', 'Tiefbau', 'Kabelzug', 'OTDR-Messung', 'Hausanschluss BEP']), 1, 1)
insertField.run('field-bauleiter', folderId, 'bauleiter', 'Verantw. Bauleiter', 'text', 0, '[]', 1, 2)
insertField.run('field-status', folderId, 'abnahme_status', 'Abnahmestatus', 'select', 0, JSON.stringify(['Offen', 'In Prüfung', 'Freigegeben', 'Mängel gemeldet']), 0, 3)
insertField.run('field-kosten', folderId, 'kosten_chf', 'Budget / Kosten (CHF)', 'number', 1, '[]', 0, 4)

// 5. Projects
const projectId = 'proj-schlieren-west-01'
db.prepare(`
  INSERT OR REPLACE INTO projects (id, folder_id, title, status)
  VALUES (?, ?, ?, ?)
`).run(projectId, folderId, 'Los 3 – Schlieren West (Trasse 410)', 'active')

const freeProjectId = 'proj-kuechenumbau-01'
db.prepare(`
  INSERT OR REPLACE INTO projects (id, folder_id, title, status)
  VALUES (?, ?, ?, ?)
`).run(freeProjectId, freeFolderId, 'Küchenumbau & Fliesenarbeiten', 'active')

// 6. Project Members
const insertMember = db.prepare(`
  INSERT OR REPLACE INTO project_members (id, project_id, user_id, role)
  VALUES (?, ?, ?, ?)
`)

insertMember.run('mem-sarah-01', projectId, editorId, 'editor')
insertMember.run('mem-lukas-01', projectId, viewerId, 'viewer')

// 7. Lists
const list1Id = 'list-vorbereitung-01'
const list2Id = 'list-bauphase-02'
const list3Id = 'list-intern-03' // Custom access! Restricted to owner/admins

const insertList = db.prepare(`
  INSERT OR REPLACE INTO lists (id, project_id, title, access_mode, sort_order)
  VALUES (?, ?, ?, ?, ?)
`)

insertList.run(list1Id, projectId, '1. Bewilligungen & Vorbereitung', 'inherit', 1)
insertList.run(list2Id, projectId, '2. Bauphase Vor-Ort (Trasse & Rohrbau)', 'inherit', 2)
insertList.run(list3Id, projectId, '3. Interne QS & Abrechnung (Vertraulich)', 'custom', 3)

// Give Sarah access to list 3, but NOT Lukas (viewer)
db.prepare(`
  INSERT OR REPLACE INTO list_access (id, list_id, user_id, is_visible)
  VALUES (?, ?, ?, ?)
`).run('la-sarah-list3', list3Id, editorId, 1)

// Free project list
insertList.run('list-free-todo', freeProjectId, 'Zu Erledigen', 'inherit', 1)

// 8. Tasks
const insertTask = db.prepare(`
  INSERT OR REPLACE INTO tasks (id, list_id, title, description, status, custom_data, due_date, sort_order)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?)
`)

insertTask.run(
  'task-bewilligung-01',
  list1Id,
  'Gemeinde Schlieren Aufgrabungsbewilligung einholen',
  'Genehmigungsunterlagen für Zürcherstrasse 45-89 inkl. Verkehrsleitkonzept bei der Baupolizei einreichen.',
  'done',
  JSON.stringify({ gewerk: 'Tiefbau', bauleiter: 'Marc Steiner', abnahme_status: 'Freigegeben', kosten_chf: 1250 }),
  '2026-09-01',
  1
)

insertTask.run(
  'task-rohrbau-02',
  list2Id,
  'Kabelzug Speedpipe 24x7/1.5 Trasse Ost',
  'Rohrverband einziehen und Kalibrierungsprüfung durchführen. Achtung Gasleitung Quereinschlag bei Schacht 14.',
  'in_progress',
  JSON.stringify({ gewerk: 'Kabelzug', bauleiter: 'Marc Steiner', abnahme_status: 'In Prüfung', kosten_chf: 8400 }),
  '2026-09-25',
  1
)

insertTask.run(
  'task-spleiss-03',
  list2Id,
  'Spleissung Muffe M-410 und OTDR Dämpfungsmessung',
  '144 Fasern auf Kassetten 1-6 ablegen, 1310/1550nm Messprotokoll erstellen und PDF ins Journal ablegen.',
  'todo',
  JSON.stringify({ gewerk: 'LWL-Spleissen', bauleiter: 'Marc Steiner', abnahme_status: 'Offen', kosten_chf: 3200 }),
  '2026-10-02',
  2
)

insertTask.run(
  'task-abrechnung-04',
  list3Id,
  'Schlussaufmass & Nachkalkulation Tiefbauunternehmer',
  'Interne Prüfung der Mehrkostenforderung für Felsaushub Trassenkilometer 1.4.',
  'todo',
  JSON.stringify({ gewerk: 'Tiefbau', bauleiter: 'Marc Steiner', abnahme_status: 'Mängel gemeldet', kosten_chf: 14500 }),
  '2026-10-15',
  1
)

// Free project task
insertTask.run(
  'task-fliesen-01',
  'list-free-todo',
  'Fliesen für Küchenrückwand bestellen',
  'Muster im Baumarkt vergleichen und 12qm Feinsteinzeug bestellen.',
  'todo',
  JSON.stringify({}),
  '2026-09-30',
  1
)

// 9. Project Journal Entries (Bautagebuch & Voice Notes)
const insertJournal = db.prepare(`
  INSERT OR REPLACE INTO project_journals (id, project_id, task_id, author_id, entry_type, title, content)
  VALUES (?, ?, ?, ?, ?, ?, ?)
`)

insertJournal.run(
  'journ-01',
  projectId,
  'task-rohrbau-02',
  companyAdminId,
  'voice',
  'Vor-Ort Sprachnotiz Schacht 14 Begehung',
  'Transkription: Bei Schacht 14 liegt die Gasleitung tiefer als im Plan eingezeichnet (ca. 1.20m). Haben den Baggerführer instruiert, im Handschacht-Verfahren weiterzugraben. Foto der Lage im Journal dokumentiert.'
)

insertJournal.run(
  'journ-02',
  projectId,
  null,
  companyAdminId,
  'system',
  'Bautagesbericht Wetter & Mannschaftsstärke',
  'Wetter: Sonnig, 19°C. Mannschaft: 1 Polier, 3 Tiefbau-Fachkräfte, 1 Spleisser. Keine Vorkommnisse gemäss SUVA-Sicherheitsleitfaden.'
)

console.log('Taskster database seeded successfully!')
console.log('Default credentials:')
console.log('1. Superadmin:    admin@kurka.ch          | password123')
console.log('2. Company Admin: marc@kurka.ch         | password123')
console.log('3. Editor:        sarah.editor@kurka.ch | password123')
console.log('4. Viewer:        lukas.viewer@kurka.ch | password123')
console.log('5. Free User:     peter@muster.ch            | password123')

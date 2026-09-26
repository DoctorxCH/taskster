const fs = require('fs')
const path = require('path')
const mysql = require('mysql2/promise')
const bcrypt = require('bcryptjs')

function loadEnv() {
  const envPath = path.resolve(__dirname, '../.env')
  if (fs.existsSync(envPath)) {
    const lines = fs.readFileSync(envPath, 'utf8').split(/\r?\n/)
    for (const line of lines) {
      const trimmed = line.trim()
      if (!trimmed || trimmed.startsWith('#') || !trimmed.includes('=')) continue
      const [k, ...rest] = trimmed.split('=')
      const val = rest.join('=').trim().replace(/^["']|["']$/g, '')
      if (!process.env[k.trim()]) {
        process.env[k.trim()] = val
      }
    }
  }
}
loadEnv()

async function migrate() {
  const host = process.env.DB_HOST || 'sql21.hostcreators.sk'
  const port = parseInt(process.env.DB_PORT || '3326', 10)
  const user = process.env.DB_USER || 'u44809_martin_taskster'
  const password = process.env.DB_PASSWORD || ''
  const database = process.env.DB_NAME || 'd44809_taskster_26'

  console.log(`Connecting to MySQL 8.4 at ${host}:${port}...`)
  const conn = await mysql.createConnection({
    host,
    port,
    user,
    password,
    database
  })

  console.log('Connected! Creating MySQL tables...')

  await conn.query(`
    CREATE TABLE IF NOT EXISTS companies (
      id VARCHAR(64) PRIMARY KEY,
      name VARCHAR(255) NOT NULL,
      subscription_plan VARCHAR(64) NOT NULL DEFAULT 'starter',
      settings JSON NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS users (
      id VARCHAR(64) PRIMARY KEY,
      company_id VARCHAR(64) NULL,
      company_role VARCHAR(64) NULL,
      is_superadmin TINYINT(1) NOT NULL DEFAULT 0,
      is_pro TINYINT(1) NOT NULL DEFAULT 0,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255) UNIQUE NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_users_company (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_folders (
      id VARCHAR(64) PRIMARY KEY,
      owner_id VARCHAR(64) NOT NULL,
      company_id VARCHAR(64) NULL,
      name VARCHAR(255) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_folders_owner (owner_id),
      INDEX idx_folders_company (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS folder_field_definitions (
      id VARCHAR(64) PRIMARY KEY,
      folder_id VARCHAR(64) NOT NULL,
      field_key VARCHAR(64) NOT NULL,
      label VARCHAR(255) NOT NULL,
      field_type VARCHAR(64) NOT NULL DEFAULT 'text',
      is_pro_only TINYINT(1) NOT NULL DEFAULT 0,
      formula TEXT NULL,
      logic_rules JSON NULL,
      options JSON NULL,
      is_required TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      INDEX idx_field_folder (folder_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS projects (
      id VARCHAR(64) PRIMARY KEY,
      folder_id VARCHAR(64) NOT NULL,
      title VARCHAR(255) NOT NULL,
      status VARCHAR(64) NOT NULL DEFAULT 'active',
      is_default TINYINT(1) NOT NULL DEFAULT 0,
      due_date VARCHAR(64) NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_projects_folder (folder_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  try {
    await conn.query(`ALTER TABLE projects ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0;`)
  } catch (e) { }
  try {
    await conn.query(`ALTER TABLE projects ADD COLUMN due_date VARCHAR(64) NULL;`)
  } catch (e) { }

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_members (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      role VARCHAR(64) NOT NULL DEFAULT 'editor',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_project_user (project_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS folder_members (
      id VARCHAR(64) PRIMARY KEY,
      folder_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      role VARCHAR(64) NOT NULL DEFAULT 'editor',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_folder_user (folder_id, user_id),
      INDEX idx_fm_folder (folder_id),
      INDEX idx_fm_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS lists (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      title VARCHAR(255) NOT NULL,
      access_mode VARCHAR(64) NOT NULL DEFAULT 'inherit',
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_lists_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS list_access (
      id VARCHAR(64) PRIMARY KEY,
      list_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      is_visible TINYINT(1) NOT NULL DEFAULT 1,
      UNIQUE KEY uq_list_user (list_id, user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS tasks (
      id VARCHAR(64) PRIMARY KEY,
      list_id VARCHAR(64) NOT NULL,
      title VARCHAR(255) NOT NULL,
      description TEXT NULL,
      status VARCHAR(64) NOT NULL DEFAULT 'todo',
      custom_data JSON NULL,
      due_date VARCHAR(64) NULL,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_tasks_list (list_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_journals (
      id VARCHAR(64) PRIMARY KEY,
      company_id VARCHAR(64) NULL,
      project_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NULL,
      author_id VARCHAR(64) NULL,
      task_id VARCHAR(64) NULL,
      type VARCHAR(32) NOT NULL DEFAULT 'entry',
      category VARCHAR(64) NOT NULL DEFAULT 'allgemein',
      entry_type VARCHAR(64) NOT NULL DEFAULT 'manual',
      title VARCHAR(255) NOT NULL,
      content LONGTEXT NOT NULL,
      visibility VARCHAR(32) NOT NULL DEFAULT 'all',
      allowed_group_id VARCHAR(64) NULL,
      metadata JSON NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_journals_project (project_id),
      INDEX idx_journals_company (company_id),
      INDEX idx_journals_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_journal_attachments (
      id VARCHAR(64) PRIMARY KEY,
      journal_id VARCHAR(64) NOT NULL,
      file_name VARCHAR(255) NOT NULL,
      file_path LONGTEXT NOT NULL,
      file_type VARCHAR(128) NOT NULL,
      file_size BIGINT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_pja_journal (journal_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_journal_attendees (
      id VARCHAR(64) PRIMARY KEY,
      journal_id VARCHAR(64) NOT NULL,
      contact_id VARCHAR(64) NULL,
      name VARCHAR(255) NOT NULL,
      email VARCHAR(255) NULL,
      role VARCHAR(255) NULL,
      present TINYINT(1) NOT NULL DEFAULT 1,
      INDEX idx_attendees_journal (journal_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_documents (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      task_id VARCHAR(64) NULL,
      journal_id VARCHAR(64) NULL,
      file_name VARCHAR(255) NOT NULL,
      mime_type VARCHAR(128) NOT NULL,
      file_size BIGINT NOT NULL,
      storage_path TEXT NOT NULL,
      version INT NOT NULL DEFAULT 1,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_docs_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS task_comments (
      id VARCHAR(64) PRIMARY KEY,
      task_id VARCHAR(64) NOT NULL,
      author_id VARCHAR(64) NOT NULL,
      content TEXT NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_comments_task (task_id),
      INDEX idx_comments_author (author_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS task_subtasks (
      id VARCHAR(64) PRIMARY KEY,
      task_id VARCHAR(64) NOT NULL,
      title VARCHAR(512) NOT NULL,
      is_done TINYINT(1) NOT NULL DEFAULT 0,
      sort_order INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_subtasks_task (task_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS time_entries (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      task_id VARCHAR(64) NULL,
      user_id VARCHAR(64) NOT NULL,
      duration_minutes INT NOT NULL,
      hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00,
      currency VARCHAR(8) NOT NULL DEFAULT 'CHF',
      description TEXT NULL,
      entry_date VARCHAR(10) NOT NULL,
      is_manual TINYINT(1) NOT NULL DEFAULT 1,
      started_at DATETIME NULL,
      ended_at DATETIME NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL,
      INDEX idx_time_project (project_id),
      INDEX idx_time_task (task_id),
      INDEX idx_time_user (user_id),
      INDEX idx_time_date (entry_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS daily_todos (
      id VARCHAR(64) PRIMARY KEY,
      user_id VARCHAR(64) NOT NULL,
      project_id VARCHAR(64) NULL,
      title VARCHAR(512) NOT NULL,
      target_date DATE NOT NULL,
      is_completed TINYINT(1) NOT NULL DEFAULT 0,
      completed_at DATETIME NULL,
      original_date DATE NULL,
      rollover_count INT NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_dt_user (user_id),
      INDEX idx_dt_target (target_date),
      INDEX idx_dt_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS notifications (
      id VARCHAR(64) PRIMARY KEY,
      user_id VARCHAR(64) NOT NULL,
      type VARCHAR(64) NOT NULL,
      title VARCHAR(255) NOT NULL,
      message TEXT NOT NULL,
      reference_type VARCHAR(64) NULL,
      reference_id VARCHAR(64) NULL,
      project_id VARCHAR(64) NULL,
      is_read TINYINT(1) NOT NULL DEFAULT 0,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_notif_user (user_id),
      INDEX idx_notif_type (type),
      INDEX idx_notif_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS contacts (
      id VARCHAR(64) PRIMARY KEY,
      user_id VARCHAR(64) NOT NULL,
      company_id VARCHAR(64) NULL,
      project_id VARCHAR(64) NULL,
      first_name VARCHAR(255) NULL,
      last_name VARCHAR(255) NOT NULL,
      company_name VARCHAR(255) NULL,
      role_function VARCHAR(255) NULL,
      phone VARCHAR(64) NULL,
      mobile VARCHAR(64) NULL,
      email VARCHAR(255) NULL,
      category_group VARCHAR(128) NULL,
      address VARCHAR(500) NULL,
      website VARCHAR(500) NULL,
      tags JSON NULL,
      notes TEXT NULL,
      share_scope VARCHAR(32) NOT NULL DEFAULT 'private',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
      INDEX idx_contacts_user (user_id),
      INDEX idx_contacts_company (company_id),
      INDEX idx_contacts_project (project_id),
      INDEX idx_contacts_group (category_group)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS user_groups (
      id VARCHAR(64) PRIMARY KEY,
      owner_id VARCHAR(64) NOT NULL,
      company_id VARCHAR(64) NULL,
      name VARCHAR(255) NOT NULL,
      description TEXT NULL,
      color VARCHAR(32) NOT NULL DEFAULT '#0891B2',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_ug_owner (owner_id),
      INDEX idx_ug_company (company_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS user_group_members (
      id VARCHAR(64) PRIMARY KEY,
      group_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_group_user (group_id, user_id),
      INDEX idx_ugm_group (group_id),
      INDEX idx_ugm_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS project_group_access (
      id VARCHAR(64) PRIMARY KEY,
      project_id VARCHAR(64) NOT NULL,
      group_id VARCHAR(64) NOT NULL,
      role VARCHAR(32) NOT NULL DEFAULT 'editor',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_pga_project_group (project_id, group_id),
      INDEX idx_pga_project (project_id),
      INDEX idx_pga_group (group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS folder_group_access (
      id VARCHAR(64) PRIMARY KEY,
      folder_id VARCHAR(64) NOT NULL,
      group_id VARCHAR(64) NOT NULL,
      role VARCHAR(32) NOT NULL DEFAULT 'editor',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_fga_folder_group (folder_id, group_id),
      INDEX idx_fga_folder (folder_id),
      INDEX idx_fga_group (group_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS company_memberships (
      id VARCHAR(64) PRIMARY KEY,
      company_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      role VARCHAR(32) NOT NULL DEFAULT 'member',
      license_type VARCHAR(32) NOT NULL DEFAULT 'pro',
      status VARCHAR(32) NOT NULL DEFAULT 'active',
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_comp_user (company_id, user_id),
      INDEX idx_cm_company (company_id),
      INDEX idx_cm_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  await conn.query(`
    CREATE TABLE IF NOT EXISTS audit_logs (
      id VARCHAR(64) PRIMARY KEY,
      user_id VARCHAR(64) NULL,
      company_id VARCHAR(64) NULL,
      action VARCHAR(128) NOT NULL,
      entity_type VARCHAR(64) NULL,
      entity_id VARCHAR(64) NULL,
      ip_address VARCHAR(45) NULL,
      user_agent TEXT NULL,
      details JSON NULL,
      created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      INDEX idx_al_user (user_id),
      INDEX idx_al_company (company_id),
      INDEX idx_al_action (action),
      INDEX idx_al_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
  `)

  // Column migrations for MySQL
  const colMigrations = [
    "ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE users ADD COLUMN currency VARCHAR(8) NOT NULL DEFAULT 'CHF'",
    "ALTER TABLE users ADD COLUMN trial_ends_at DATETIME NULL",
    "ALTER TABLE projects ADD COLUMN currency VARCHAR(8) NOT NULL DEFAULT 'CHF'",
    "ALTER TABLE projects ADD COLUMN budget_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE projects ADD COLUMN budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE tasks ADD COLUMN budget_hours DECIMAL(10,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE tasks ADD COLUMN budget_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00",
    "ALTER TABLE project_folders ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'private'",
    "ALTER TABLE projects ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'private'",
    "ALTER TABLE projects ADD COLUMN due_date VARCHAR(64) NULL",
    "ALTER TABLE lists ADD COLUMN is_completed_target TINYINT(1) NOT NULL DEFAULT 0",
    "ALTER TABLE companies ADD COLUMN billing_email VARCHAR(255) NULL",
    "ALTER TABLE companies ADD COLUMN stripe_customer_id VARCHAR(128) NULL",
    "ALTER TABLE company_invitations ADD COLUMN license_type VARCHAR(32) NOT NULL DEFAULT 'pro'",
    "ALTER TABLE users ADD COLUMN admin_permissions JSON NULL",
    "ALTER TABLE tasks MODIFY COLUMN assigned_to TEXT NULL",
    "ALTER TABLE contacts ADD COLUMN address VARCHAR(500) NULL",
    "ALTER TABLE contacts ADD COLUMN website VARCHAR(500) NULL",
    "ALTER TABLE users ADD COLUMN avatar MEDIUMTEXT NULL",
    "ALTER TABLE folder_field_definitions ADD COLUMN entity_type VARCHAR(32) NOT NULL DEFAULT 'task'",
    "ALTER TABLE folder_field_definitions ADD COLUMN logic_rules JSON NULL",
    "ALTER TABLE folder_field_definitions ADD COLUMN label_key VARCHAR(128) NULL",
    "ALTER TABLE project_templates ADD COLUMN name_key VARCHAR(128) NULL",
    "ALTER TABLE project_templates ADD COLUMN description_key VARCHAR(128) NULL",
    "ALTER TABLE project_journals ADD COLUMN company_id VARCHAR(64) NULL",
    "ALTER TABLE project_journals ADD COLUMN user_id VARCHAR(64) NULL",
    "ALTER TABLE project_journals ADD COLUMN type VARCHAR(32) NOT NULL DEFAULT 'entry'",
    "ALTER TABLE project_journals ADD COLUMN category VARCHAR(64) NOT NULL DEFAULT 'allgemein'",
    "ALTER TABLE project_journals ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'all'",
    "ALTER TABLE project_journals ADD COLUMN allowed_group_id VARCHAR(64) NULL",
    "ALTER TABLE project_journals ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "UPDATE project_journals SET user_id = author_id WHERE user_id IS NULL AND author_id IS NOT NULL",
    "ALTER TABLE project_folders ADD COLUMN settings JSON NULL",
    "ALTER TABLE contacts ADD COLUMN folder_id VARCHAR(64) NULL",
    "ALTER TABLE project_journals ADD COLUMN folder_id VARCHAR(64) NULL",
    "ALTER TABLE project_journals MODIFY COLUMN project_id VARCHAR(64) NULL",
    "ALTER TABLE projects ADD COLUMN template_id VARCHAR(64) NULL",
  ]
  for (const sql of colMigrations) {
    try { await conn.query(sql) } catch (_) { }
  }

  // Fallback migration: Jedes Projekt gehört zwingend zu einem Ordner (folder_id NOT NULL)
  try {
    const [orphanProjects] = await conn.query(`
      SELECT p.id, p.title, pm.user_id, u.company_id
      FROM projects p
      LEFT JOIN project_folders pf ON pf.id = p.folder_id
      LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'owner'
      LEFT JOIN users u ON u.id = pm.user_id
      WHERE p.folder_id IS NULL OR p.folder_id = '' OR pf.id IS NULL
    `)
    for (const orphan of orphanProjects) {
      const ownerId = orphan.user_id || 'user-superadmin-01'
      const companyId = orphan.company_id || null
      // Check or create default folder 'Allgemein'
      let [defFolds] = await conn.query("SELECT id FROM project_folders WHERE owner_id = ? AND name = 'Allgemein' LIMIT 1", [ownerId])
      let fallbackFolderId = defFolds[0]?.id
      if (!fallbackFolderId) {
        fallbackFolderId = 'fld_allgemein_' + Math.random().toString(36).substring(2, 8)
        await conn.query("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility) VALUES (?, ?, ?, 'Allgemein', '📁', 'private')", [
          fallbackFolderId, ownerId, companyId
        ])
      }
      await conn.query("UPDATE projects SET folder_id = ? WHERE id = ?", [fallbackFolderId, orphan.id])
    }
  } catch (err) {
    console.warn('Orphan project migration note:', err.message)
  }

  console.log('Tables created. Seeding initial data...')
  const pwHash = bcrypt.hashSync('password123', 10)

  await conn.query(`
    INSERT INTO companies (id, name, subscription_plan, settings)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name=VALUES(name), settings=VALUES(settings);
  `, [
    'comp-swiss-infra-01',
    'Swisscom Infra Partner AG',
    'enterprise',
    JSON.stringify({
      allow_document_upload: true,
      require_2fa: false,
      max_seats: 50,
      compliance_strict: true,
      regional_data_residency: 'CH'
    })
  ])

  const insertUserSql = `
    INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name=VALUES(name), email=VALUES(email), company_role=VALUES(company_role), password_hash=VALUES(password_hash);
  `

  await conn.query(insertUserSql, ['user-superadmin-01', null, null, 1, 1, 'Taskster Admin', 'admin@kurka.ch', pwHash])
  await conn.query(insertUserSql, ['user-marc-01', 'comp-swiss-infra-01', 'admin', 0, 1, 'Marc Steiner (Bauleitung)', 'marc@kurka.ch', pwHash])
  await conn.query(insertUserSql, ['user-sarah-02', 'comp-swiss-infra-01', 'member', 0, 1, 'Sarah Keller (Projektleitung)', 'sarah.editor@kurka.ch', pwHash])
  await conn.query(insertUserSql, ['user-lukas-03', null, null, 0, 0, 'Lukas Frey (Subunternehmer)', 'lukas.viewer@kurka.ch', pwHash])
  await conn.query(insertUserSql, ['user-peter-free-04', null, null, 0, 0, 'Peter Muster (Free Plan)', 'peter@muster.ch', pwHash])

  await conn.query(`
    INSERT INTO project_folders (id, owner_id, company_id, name)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name=VALUES(name);
  `, ['folder-limmattal-01', 'user-marc-01', 'comp-swiss-infra-01', 'Limmattal FTTH Glasfaser Rollout'])

  await conn.query(`
    INSERT INTO project_folders (id, owner_id, company_id, name)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE name=VALUES(name);
  `, ['folder-free-peter-01', 'user-peter-free-04', null, 'Peters Privates Renovationsprojekt'])

  await conn.query(`
    INSERT INTO projects (id, folder_id, title, status)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['proj-schlieren-west-01', 'folder-limmattal-01', 'Los 3 – Schlieren West (Trasse 410)', 'active'])

  await conn.query(`
    INSERT INTO projects (id, folder_id, title, status)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['proj-kuechenumbau-01', 'folder-free-peter-01', 'Küchenumbau & Fliesenarbeiten', 'active'])

  await conn.query(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['list-vorbereitung-01', 'proj-schlieren-west-01', '1. Bewilligungen & Vorbereitung', 'inherit', 1])

  await conn.query(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['list-bauphase-02', 'proj-schlieren-west-01', '2. Bauphase Vor-Ort (Trasse & Rohrbau)', 'inherit', 2])

  await conn.query(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['list-intern-03', 'proj-schlieren-west-01', '3. Interne QS & Abrechnung (Vertraulich)', 'custom', 3])

  await conn.query(`
    INSERT INTO lists (id, project_id, title, access_mode, sort_order)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, ['list-free-todo', 'proj-kuechenumbau-01', 'Zu Erledigen', 'inherit', 1])

  await conn.query(`
    INSERT INTO tasks (id, list_id, title, description, status, custom_data, due_date, sort_order)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE title=VALUES(title);
  `, [
    'task-bewilligung-01',
    'list-vorbereitung-01',
    'Gemeinde Schlieren Aufgrabungsbewilligung einholen',
    'Genehmigungsunterlagen für Zürcherstrasse 45-89 inkl. Verkehrsleitkonzept bei der Baupolizei einreichen.',
    'done',
    JSON.stringify({ gewerk: 'Tiefbau', bauleiter: 'Marc Steiner', abnahme_status: 'Freigegeben', kosten_chf: 1250 }),
    '2026-09-01',
    1
  ])

  // --- PHASE 1: RBAC & Company Migration ---
  
  // Idempotente ALTER TABLE für companies
  const companyAlters = [
    "ALTER TABLE companies ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'active'",
    "ALTER TABLE companies ADD COLUMN max_users INT NOT NULL DEFAULT 100",
    "ALTER TABLE companies ADD COLUMN max_storage_gb INT NOT NULL DEFAULT 10",
    "ALTER TABLE companies ADD COLUMN max_tasks_per_month INT NOT NULL DEFAULT 10000",
    "ALTER TABLE companies ADD COLUMN api_rate_limit_per_minute INT NOT NULL DEFAULT 60",
    "ALTER TABLE companies ADD COLUMN auth_policy JSON NULL",
    "ALTER TABLE companies ADD COLUMN sso_config JSON NULL"
  ];
  for (const query of companyAlters) {
    try {
      await conn.query(query);
    } catch (e) {
      if (e.code !== 'ER_DUP_FIELDNAME') {
        console.warn('Warning during company alter:', e.message);
      }
    }
  }

  // RBAC Tabellen anlegen
  await conn.query(`
    CREATE TABLE IF NOT EXISTS roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        \`key\` VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        level ENUM('system', 'company', 'project') NOT NULL DEFAULT 'company',
        description TEXT NULL,
        is_system TINYINT(1) NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_role_key (\`key\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  await conn.query(`
    CREATE TABLE IF NOT EXISTS permissions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        \`key\` VARCHAR(100) NOT NULL,
        entity VARCHAR(30) NOT NULL,
        action VARCHAR(30) NOT NULL,
        description TEXT NULL,
        is_system TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_permission_key (\`key\`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  await conn.query(`
    CREATE TABLE IF NOT EXISTS role_permissions (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        role_id BIGINT UNSIGNED NOT NULL,
        permission_id BIGINT UNSIGNED NOT NULL,
        scope ENUM('all', 'company', 'project', 'assigned') NOT NULL DEFAULT 'all',
        conditions JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_role_perm_scope (role_id, permission_id, scope),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
        FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  await conn.query(`
    CREATE TABLE IF NOT EXISTS user_company_roles (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id VARCHAR(64) NOT NULL,
        company_id VARCHAR(64) NOT NULL,
        project_id VARCHAR(64) NULL,
        role_id BIGINT UNSIGNED NOT NULL,
        assigned_by VARCHAR(64) NULL,
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_company_role (user_id, company_id, project_id, role_id),
        FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
  `);

  // Systemrollen Seeden
  await conn.query(`
    INSERT INTO roles (\`key\`, name, level, is_system, sort_order) VALUES
    ('superadmin', 'Superadmin', 'system', 1, 10),
    ('company_admin', 'Firmen-Admin', 'company', 1, 20),
    ('project_manager', 'Projekt-Manager', 'company', 1, 30),
    ('member', 'Mitarbeiter', 'company', 1, 40),
    ('viewer', 'Betrachter', 'company', 1, 50)
    ON DUPLICATE KEY UPDATE name=VALUES(name);
  `);

  // Migration der bestehenden User-Rollen
  // 1. Superadmins -> user_company_roles mit role 'superadmin'
  const [superadminRole] = await conn.query("SELECT id FROM roles WHERE `key` = 'superadmin'");
  if (superadminRole && superadminRole.length > 0) {
    await conn.query(`
      INSERT IGNORE INTO user_company_roles (user_id, company_id, role_id)
      SELECT id, 'system', ? FROM users WHERE is_superadmin = 1
    `, [superadminRole[0].id]);
  }

  // 2. Company Admins -> user_company_roles mit role 'company_admin'
  const [companyAdminRole] = await conn.query("SELECT id FROM roles WHERE `key` = 'company_admin'");
  if (companyAdminRole && companyAdminRole.length > 0) {
    await conn.query(`
      INSERT IGNORE INTO user_company_roles (user_id, company_id, role_id)
      SELECT id, company_id, ? FROM users WHERE company_role = 'admin' AND company_id IS NOT NULL
    `, [companyAdminRole[0].id]);
  }

  const [tables] = await conn.query('SHOW TABLES')
  console.log('✅ Remote MySQL Migration completed successfully!')
  console.log('Tables created in d44809_taskster_26:', tables.map((t) => Object.values(t)[0]))
  await conn.end()
}

migrate().catch((err) => {
  console.error('Migration failed:', err)
  process.exit(1)
})

<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dbHost = getEnvValue('DB_HOST', 'sql21.hostcreators.sk');
$dbPort = (int)getEnvValue('DB_PORT', 3326);
$dbName = getEnvValue('DB_NAME', 'd44809_taskster_26');
$dbUser = getEnvValue('DB_USER', 'u44809_martin_taskster');
$dbPass = getEnvValue('DB_PASSWORD', '');
$jwtSecret = getEnvValue('JWT_SECRET', 'taskster-super-secret-key-2026-safe-production');

function getDb() {
    global $dbHost, $dbPort, $dbName, $dbUser, $dbPass;
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        ensureTables($pdo);
    }
    return $pdo;
}

function ensureTables($pdo) {
    static $ensured = false;
    if ($ensured) return;
    $ensured = true;
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_templates (
                id VARCHAR(64) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(64) NOT NULL DEFAULT 'job',
                subcategory VARCHAR(64) NULL,
                description TEXT NULL,
                icon VARCHAR(64) DEFAULT 'Folder',
                is_system TINYINT(1) NOT NULL DEFAULT 1,
                company_id VARCHAR(64) NULL,
                lists JSON NULL,
                fields JSON NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // Column migrations (idempotent, always run first)
        $colMigrations = [
            "ALTER TABLE project_folders ADD COLUMN icon VARCHAR(64) DEFAULT '📁'",
            "ALTER TABLE tasks ADD COLUMN assigned_to VARCHAR(64) DEFAULT NULL",
            "ALTER TABLE tasks ADD COLUMN priority VARCHAR(32) DEFAULT 'normal'",
            "ALTER TABLE tasks ADD COLUMN color VARCHAR(64) DEFAULT NULL",
            "ALTER TABLE tasks ADD COLUMN tags JSON",
            "ALTER TABLE tasks ADD COLUMN checklist JSON",
            "ALTER TABLE users ADD COLUMN hourly_rate DECIMAL(10,2) DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN currency VARCHAR(10) DEFAULT 'CHF'",
            "ALTER TABLE projects ADD COLUMN currency VARCHAR(10) DEFAULT 'CHF'",
            "ALTER TABLE projects ADD COLUMN budget_hours DECIMAL(10,2) DEFAULT NULL",
            "ALTER TABLE projects ADD COLUMN budget_amount DECIMAL(12,2) DEFAULT NULL",
            "ALTER TABLE tasks ADD COLUMN budget_hours DECIMAL(10,2) DEFAULT NULL",
            "ALTER TABLE tasks ADD COLUMN budget_amount DECIMAL(12,2) DEFAULT NULL",
            "ALTER TABLE project_folders ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'private'",
            "ALTER TABLE projects ADD COLUMN visibility VARCHAR(32) NOT NULL DEFAULT 'private'",
            "ALTER TABLE users ADD COLUMN admin_permissions JSON DEFAULT NULL",
            "ALTER TABLE users ADD COLUMN settings JSON DEFAULT NULL",
            "ALTER TABLE contacts ADD COLUMN address VARCHAR(500) NULL",
            "ALTER TABLE contacts ADD COLUMN website VARCHAR(500) NULL",
            "ALTER TABLE contacts ADD COLUMN latitude DECIMAL(10,7) NULL",
            "ALTER TABLE contacts ADD COLUMN longitude DECIMAL(10,7) NULL",
            "ALTER TABLE calendar_events ADD COLUMN latitude DECIMAL(10,7) NULL",
            "ALTER TABLE calendar_events ADD COLUMN longitude DECIMAL(10,7) NULL",
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
            "ALTER TABLE lists ADD COLUMN is_completed_target TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE users ADD COLUMN trial_ends_at DATETIME NULL",
            "ALTER TABLE companies ADD COLUMN billing_email VARCHAR(255) NULL",
            "ALTER TABLE companies ADD COLUMN stripe_customer_id VARCHAR(128) NULL",
            "ALTER TABLE company_invitations ADD COLUMN license_type VARCHAR(32) NOT NULL DEFAULT 'pro'",
            "ALTER TABLE project_folders ADD COLUMN settings JSON NULL",
            "ALTER TABLE contacts ADD COLUMN folder_id VARCHAR(64) NULL",
            "ALTER TABLE project_journals ADD COLUMN folder_id VARCHAR(64) NULL",
            "ALTER TABLE project_journals MODIFY COLUMN project_id VARCHAR(64) NULL",
            "ALTER TABLE projects ADD COLUMN template_id VARCHAR(64) NULL",
        ];
        foreach ($colMigrations as $sql) {
            try { $pdo->exec($sql); } catch (Exception $e) {}
        }

        try {
            $count = $pdo->query("SELECT COUNT(*) FROM project_templates WHERE is_system = 1 AND id IN ('template_building_construction', 'template_civil_engineering', 'template_network_infrastructure', 'template_property_maintenance')")->fetchColumn();
            if ((int)$count < 4) {
                seedTemplates($pdo);
            }
        } catch (Exception $e) {}

        $pdo->exec("
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
        ");

        // Idempotente Fallback-Migration für bestehende Waisen-Projekte
        try {
            $orphans = $pdo->query("
                SELECT p.id, p.title, pm.user_id, u.company_id
                FROM projects p
                LEFT JOIN project_folders pf ON pf.id = p.folder_id
                LEFT JOIN project_members pm ON pm.project_id = p.id AND pm.role = 'owner'
                LEFT JOIN users u ON u.id = pm.user_id
                WHERE p.folder_id IS NULL OR p.folder_id = '' OR pf.id IS NULL
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($orphans as $orphan) {
                $ownerId = $orphan['user_id'] ?: 'user-superadmin-01';
                $companyId = $orphan['company_id'] ?: null;
                $chkFold = $pdo->prepare("SELECT id FROM project_folders WHERE owner_id = ? AND name = 'Allgemein' LIMIT 1");
                $chkFold->execute([$ownerId]);
                $fallbackFolderId = $chkFold->fetchColumn();
                if (!$fallbackFolderId) {
                    $fallbackFolderId = 'fld_allgemein_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $pdo->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility) VALUES (?, ?, ?, 'Allgemein', '📁', 'private')")
                        ->execute([$fallbackFolderId, $ownerId, $companyId]);
                }
                $pdo->prepare("UPDATE projects SET folder_id = ? WHERE id = ?")->execute([$fallbackFolderId, $orphan['id']]);
            }
        } catch (Exception $e) {}

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_journal_attachments (
                id VARCHAR(64) PRIMARY KEY,
                journal_id VARCHAR(64) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path LONGTEXT NOT NULL,
                file_type VARCHAR(128) NOT NULL,
                file_size BIGINT NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_pja_journal (journal_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

            CREATE TABLE IF NOT EXISTS project_journal_attendees (
                id VARCHAR(64) PRIMARY KEY,
                journal_id VARCHAR(64) NOT NULL,
                contact_id VARCHAR(64) NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NULL,
                role VARCHAR(255) NULL,
                present TINYINT(1) NOT NULL DEFAULT 1,
                INDEX idx_attendees_journal (journal_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");

        // ROOT-CAUSE-FIX: Company Admins duerfen KEINE Plattform-admin_permissions haben.
        // Frueher wurden sie automatisch gesetzt -> Company Admins landeten im Plattform-Admin.
        // Sie verwalten ihre Firma jetzt ueber company_role === 'admin' im /company Portal.
        try {
            $pdo->exec("UPDATE users SET admin_permissions = NULL WHERE is_superadmin = 0 AND company_role = 'admin'");
        } catch (Exception $e) {}

        // New tables
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS task_comments (
              id VARCHAR(64) PRIMARY KEY,
              task_id VARCHAR(64) NOT NULL,
              author_id VARCHAR(64) NOT NULL,
              content TEXT NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_comments_task (task_id),
              INDEX idx_comments_author (author_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS task_subtasks (
              id VARCHAR(64) PRIMARY KEY,
              task_id VARCHAR(64) NOT NULL,
              title VARCHAR(512) NOT NULL,
              is_done TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_subtasks_task (task_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS time_entries (
              id VARCHAR(64) PRIMARY KEY,
              project_id VARCHAR(64) NOT NULL,
              task_id VARCHAR(64) NULL,
              user_id VARCHAR(64) NOT NULL,
              duration_minutes INT NOT NULL,
              entry_date DATE NOT NULL,
              description TEXT NULL,
              is_manual TINYINT(1) NOT NULL DEFAULT 1,
              hourly_rate DECIMAL(10,2) NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX idx_time_project (project_id),
              INDEX idx_time_task (task_id),
              INDEX idx_time_user (user_id),
              INDEX idx_time_date (entry_date)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
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
        ");

        // --- Kalender / Termine ---
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS event_categories (
              id VARCHAR(64) PRIMARY KEY,
              company_id VARCHAR(64) NULL,
              owner_id VARCHAR(64) NULL,
              name VARCHAR(255) NOT NULL,
              color VARCHAR(16) NOT NULL DEFAULT '#0891B2',
              icon VARCHAR(64) DEFAULT 'Calendar',
              is_system TINYINT(1) NOT NULL DEFAULT 0,
              sort_order INT NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_evtcat_company (company_id),
              INDEX idx_evtcat_owner (owner_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS calendar_events (
              id VARCHAR(64) PRIMARY KEY,
              owner_id VARCHAR(64) NOT NULL,
              company_id VARCHAR(64) NULL,
              project_id VARCHAR(64) NULL,
              task_id VARCHAR(64) NULL,
              category_id VARCHAR(64) NULL,
              title VARCHAR(512) NOT NULL,
              description TEXT NULL,
              location VARCHAR(512) NULL,
              start_at DATETIME NOT NULL,
              end_at DATETIME NOT NULL,
              all_day TINYINT(1) NOT NULL DEFAULT 0,
              priority VARCHAR(32) NOT NULL DEFAULT 'normal',
              status VARCHAR(32) NOT NULL DEFAULT 'confirmed',
              visibility VARCHAR(32) NOT NULL DEFAULT 'private',
              color VARCHAR(16) NULL,
              recurrence TEXT NULL,
              reminder_minutes INT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX idx_evt_owner (owner_id),
              INDEX idx_evt_company (company_id),
              INDEX idx_evt_project (project_id),
              INDEX idx_evt_start (start_at),
              INDEX idx_evt_category (category_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS event_attendees (
              id VARCHAR(64) PRIMARY KEY,
              event_id VARCHAR(64) NOT NULL,
              user_id VARCHAR(64) NULL,
              email VARCHAR(255) NOT NULL,
              name VARCHAR(255) NULL,
              role VARCHAR(32) NOT NULL DEFAULT 'required',
              status VARCHAR(32) NOT NULL DEFAULT 'pending',
              is_organizer TINYINT(1) NOT NULL DEFAULT 0,
              responded_at DATETIME NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uniq_event_email (event_id, email),
              INDEX idx_att_event (event_id),
              INDEX idx_att_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS event_reminders (
              id VARCHAR(64) PRIMARY KEY,
              event_id VARCHAR(64) NOT NULL,
              user_id VARCHAR(64) NOT NULL,
              minutes_before INT NOT NULL DEFAULT 15,
              sent_at DATETIME NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX idx_rem_event (event_id),
              INDEX idx_rem_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_outbox (
              id VARCHAR(64) PRIMARY KEY,
              to_email VARCHAR(255) NOT NULL,
              to_name VARCHAR(255) NULL,
              subject VARCHAR(512) NOT NULL,
              body TEXT NOT NULL,
              ics_content MEDIUMTEXT NULL,
              status VARCHAR(32) NOT NULL DEFAULT 'pending',
              error TEXT NULL,
              attempts INT NOT NULL DEFAULT 0,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              sent_at DATETIME NULL,
              INDEX idx_outbox_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS system_settings (
              `key` VARCHAR(128) PRIMARY KEY,
              `value` MEDIUMTEXT NOT NULL,
              description VARCHAR(512) NULL,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS email_templates (
              id VARCHAR(64) PRIMARY KEY,
              trigger_event VARCHAR(64) NOT NULL UNIQUE,
              name VARCHAR(255) NOT NULL,
              description TEXT NULL,
              subject VARCHAR(512) NOT NULL,
              body_html MEDIUMTEXT NOT NULL,
              body_text MEDIUMTEXT NOT NULL,
              variables JSON NOT NULL,
              is_active TINYINT(1) NOT NULL DEFAULT 1,
              updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        $pdo->exec("
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
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_group_members (
              id VARCHAR(64) PRIMARY KEY,
              group_id VARCHAR(64) NOT NULL,
              user_id VARCHAR(64) NOT NULL,
              created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              UNIQUE KEY uq_group_user (group_id, user_id),
              INDEX idx_ugm_group (group_id),
              INDEX idx_ugm_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
        $pdo->exec("
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
        ");
        $pdo->exec("
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
        ");

        // Standard-Kategorien einmalig anlegen
        $catCount = (int)$pdo->query("SELECT COUNT(*) FROM event_categories WHERE is_system = 1")->fetchColumn();
        if ($catCount === 0) {
            $defaults = [
                ['cat_meeting', 'Besprechung', '#0891B2', 'Users', 1],
                ['cat_site', 'Baustelle', '#D97706', 'HardHat', 2],
                ['cat_deadline', 'Frist / Termin', '#DC2626', 'AlertTriangle', 3],
                ['cat_travel', 'Reise / Fahrt', '#7C3AED', 'Car', 4],
                ['cat_vacation', 'Ferien / Abwesenheit', '#059669', 'Palmtree', 5],
                ['cat_training', 'Schulung', '#2563EB', 'GraduationCap', 6],
                ['cat_private', 'Privat', '#64748B', 'Home', 7],
            ];
            $ins = $pdo->prepare("
                INSERT INTO event_categories (id, company_id, owner_id, name, color, icon, is_system, sort_order)
                VALUES (?, NULL, NULL, ?, ?, ?, 1, ?)
            ");
            foreach ($defaults as $d) {
                try { $ins->execute($d); } catch (Exception $e) {}
            }
        }

        // Standard-SMTP-Einstellungen einmalig anlegen
        $smtpCount = (int)$pdo->query("SELECT COUNT(*) FROM system_settings WHERE `key` LIKE 'smtp_%'")->fetchColumn();
        if ($smtpCount === 0) {
            $smtpDefaults = [
                'smtp_host' => getEnvValue('SMTP_HOST', 'mail.kurka.ch'),
                'smtp_port' => getEnvValue('SMTP_PORT', '465'),
                'smtp_secure' => getEnvValue('SMTP_SECURE', 'ssl'),
                'smtp_user' => getEnvValue('SMTP_USER', 'noreply@kurka.ch'),
                'smtp_password' => getEnvValue('SMTP_PASSWORD', ''),
                'smtp_from_email' => getEnvValue('SMTP_FROM_EMAIL', 'noreply@kurka.ch'),
                'smtp_from_name' => getEnvValue('SMTP_FROM_NAME', 'Taskster')
            ];
            $insSmtp = $pdo->prepare("INSERT INTO system_settings (`key`, `value`) VALUES (?, ?)");
            foreach ($smtpDefaults as $k => $v) {
                try { $insSmtp->execute([$k, $v]); } catch (Exception $e) {}
            }
        }

        // Standard-E-Mail-Vorlagen einmalig anlegen
        $tmplCount = (int)$pdo->query("SELECT COUNT(*) FROM email_templates")->fetchColumn();
        if ($tmplCount === 0) {
            seedDefaultEmailTemplates($pdo);
        }
    } catch (Exception $e) {
        // Continue if table exists or migration done
    }
}

function seedTemplates($pdo) {
    // Alte Vorlagen bereinigen
    try {
        $pdo->exec("DELETE FROM project_templates WHERE is_system = 1 AND id NOT IN ('template_building_construction', 'template_civil_engineering', 'template_network_infrastructure', 'template_property_maintenance')");
    } catch (Exception $e) {}

    $defaults = [
        [
            'id' => 'template_building_construction',
            'name' => 'Hochbau',
            'name_key' => 'templates.building_construction.name',
            'category' => 'job',
            'subcategory' => 'Bauwesen & Hochbau',
            'description' => 'Projektstruktur für Hochbau, Rohbau, Innenausbau und schlüsselfertige Übergabe.',
            'description_key' => 'templates.building_construction.description',
            'icon' => 'Building2',
            'lists' => [
                'sections.preparation',
                'sections.shell_construction',
                'sections.interior_fitting',
                'sections.handover'
            ],
            'fields' => [
                [
                    'field_key' => 'objekt_typ',
                    'label_key' => 'fields.objekt_typ.label',
                    'label' => 'Objekttyp',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'wohnbau', 'label_key' => 'fields.options.wohnbau', 'label' => 'Wohnbau'],
                        ['value' => 'gewerbe', 'label_key' => 'fields.options.gewerbe', 'label' => 'Gewerbe & Industrie'],
                        ['value' => 'oeffentlich', 'label_key' => 'fields.options.oeffentlich', 'label' => 'Öffentliche Bauten'],
                        ['value' => 'sanierung', 'label_key' => 'fields.options.sanierung', 'label' => 'Sanierung & Umbau']
                    ],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'baugesuch_status',
                    'label_key' => 'fields.baugesuch_status.label',
                    'label' => 'Baugesuchs-Status',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'pendent', 'label_key' => 'fields.options.pendent', 'label' => 'Pendent / Eingereicht'],
                        ['value' => 'bewilligt', 'label_key' => 'fields.options.bewilligt', 'label' => 'Bewilligt'],
                        ['value' => 'auflagen_offen', 'label_key' => 'fields.options.auflagen_offen', 'label' => 'Auflagen offen']
                    ],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'baubeginn_soll',
                    'label_key' => 'fields.baubeginn_soll.label',
                    'label' => 'Geplanter Baubeginn',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'bauabnahme_rohbau',
                    'label_key' => 'fields.bauabnahme_rohbau.label',
                    'label' => 'Bauabnahme Rohbau erfolgt',
                    'field_type' => 'checkbox',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'bezugstermin',
                    'label_key' => 'fields.bezugstermin.label',
                    'label' => 'Bezugstermin',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'haupt_gu',
                    'label_key' => 'fields.haupt_gu.label',
                    'label' => 'Haupt-Generalunternehmer (GU)',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'template_civil_engineering',
            'name' => 'Tiefbau & Strassenbau',
            'name_key' => 'templates.civil_engineering.name',
            'category' => 'job',
            'subcategory' => 'Tiefbau & Infrastruktur',
            'description' => 'Ablaufplanung für Aushub, Werkleitungstrassen, Fundationsschichten und Belagsarbeiten.',
            'description_key' => 'templates.civil_engineering.description',
            'icon' => 'HardHat',
            'lists' => [
                'sections.planning_traffic',
                'sections.excavation_utilities',
                'sections.road_base',
                'sections.surfacing'
            ],
            'fields' => [
                [
                    'field_key' => 'strassenklasse',
                    'label_key' => 'fields.strassenklasse.label',
                    'label' => 'Strassenklasse',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'gemeinde', 'label_key' => 'fields.options.gemeinde', 'label' => 'Gemeindestrasse'],
                        ['value' => 'kanton', 'label_key' => 'fields.options.kanton', 'label' => 'Kantonsstrasse / Landstrasse'],
                        ['value' => 'bund', 'label_key' => 'fields.options.bund', 'label' => 'Nationalstrasse / Autobahn']
                    ],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'grabebewilligung_status',
                    'label_key' => 'fields.grabebewilligung_status.label',
                    'label' => 'Grabebewilligung',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'beantragt', 'label_key' => 'fields.options.beantragt', 'label' => 'Beantragt'],
                        ['value' => 'erteilt', 'label_key' => 'fields.options.erteilt', 'label' => 'Erteilt'],
                        ['value' => 'nicht_noetig', 'label_key' => 'fields.options.nicht_noetig', 'label' => 'Nicht erforderlich']
                    ],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'verkehrsdienst_erforderlich',
                    'label_key' => 'fields.verkehrsdienst_erforderlich.label',
                    'label' => 'Verkehrsdienst erforderlich',
                    'field_type' => 'checkbox',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'belagstyp',
                    'label_key' => 'fields.belagstyp.label',
                    'label' => 'Belagstyp',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'deckbelag_asphalt', 'label_key' => 'fields.options.deckbelag_asphalt', 'label' => 'Deckbelag Asphalt'],
                        ['value' => 'pflasterstein', 'label_key' => 'fields.options.pflasterstein', 'label' => 'Pflasterstein'],
                        ['value' => 'kieskoffer', 'label_key' => 'fields.options.kieskoffer', 'label' => 'Kieskoffer / Schotter']
                    ],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'bauherr_gemeinde',
                    'label_key' => 'fields.bauherr_gemeinde.label',
                    'label' => 'Bauherr / Gemeinde',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'termin_fertigstellung',
                    'label_key' => 'fields.termin_fertigstellung.label',
                    'label' => 'Fertigstellungstermin',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'template_network_infrastructure',
            'name' => 'Netzbau & Telekommunikation',
            'name_key' => 'templates.network_infrastructure.name',
            'category' => 'job',
            'subcategory' => 'Netzbau & Telekommunikation',
            'description' => 'Trassenbau, Rohranlagen, Glasfaser-Einblasen, Spleissarbeiten und OTDR-Endabnahme.',
            'description_key' => 'templates.network_infrastructure.description',
            'icon' => 'Network',
            'lists' => [
                'sections.prep_tracing',
                'sections.pipe_ducts',
                'sections.cable_pull_splice',
                'sections.measurement_commissioning'
            ],
            'fields' => [
                [
                    'field_key' => 'sparte',
                    'label_key' => 'fields.sparte.label',
                    'label' => 'Sparte / Medium',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'ftth', 'label_key' => 'fields.options.ftth', 'label' => 'FTTH (Glasfaser)'],
                        ['value' => 'strom_ns_ms', 'label_key' => 'fields.options.strom_ns_ms', 'label' => 'Strom (NS / MS)'],
                        ['value' => 'kupfer', 'label_key' => 'fields.options.kupfer', 'label' => 'Kupfernetz'],
                        ['value' => 'mobilfunk', 'label_key' => 'fields.options.mobilfunk', 'label' => 'Mobilfunk (5G)']
                    ],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'kundenreferenz',
                    'label_key' => 'fields.kundenreferenz.label',
                    'label' => 'Kundenreferenz / Projekt-ID',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'subunternehmer_montage',
                    'label_key' => 'fields.subunternehmer_montage.label',
                    'label' => 'Montage-Subunternehmer',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'bep_inbetriebnahme_soll',
                    'label_key' => 'fields.bep_inbetriebnahme_soll.label',
                    'label' => 'Soll-Inbetriebnahme (BEP)',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'otdr_messung_erledigt',
                    'label_key' => 'fields.otdr_messung_erledigt.label',
                    'label' => 'OTDR-Messung erledigt',
                    'field_type' => 'checkbox',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'abnahmeprotokoll_vorhanden',
                    'label_key' => 'fields.abnahmeprotokoll_vorhanden.label',
                    'label' => 'Abnahmeprotokoll vorhanden',
                    'field_type' => 'checkbox',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'template_property_maintenance',
            'name' => 'Liegenschaftsunterhalt & Sanierung',
            'name_key' => 'templates.property_maintenance.name',
            'category' => 'job',
            'subcategory' => 'Sanierung & Bewirtschaftung',
            'description' => 'Gebäudeinstandhaltung, Schadstoffsanierung, Mieterabnahmen und Sanierungskoordination.',
            'description_key' => 'templates.property_maintenance.description',
            'icon' => 'Wrench',
            'lists' => [
                'sections.survey_offer',
                'sections.contractor_scheduling',
                'sections.execution',
                'sections.final_inspection'
            ],
            'fields' => [
                [
                    'field_key' => 'liegenschaft_id',
                    'label_key' => 'fields.liegenschaft_id.label',
                    'label' => 'Liegenschafts-Nr. / Gebäude-ID',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'mieter_kontakt',
                    'label_key' => 'fields.mieter_kontakt.label',
                    'label' => 'Mieterkontakt',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'sanierungsbereich',
                    'label_key' => 'fields.sanierungsbereich.label',
                    'label' => 'Sanierungsbereich',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'kueche_bad', 'label_key' => 'fields.options.kueche_bad', 'label' => 'Küche & Bad'],
                        ['value' => 'fassade_dach', 'label_key' => 'fields.options.fassade_dach', 'label' => 'Fassade & Dach'],
                        ['value' => 'heizung_lueftung', 'label_key' => 'fields.options.heizung_lueftung', 'label' => 'Heizung & Lüftung'],
                        ['value' => 'komplett', 'label_key' => 'fields.options.komplett', 'label' => 'Komplettsanierung']
                    ],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'asbest_schadstoff_pruefung',
                    'label_key' => 'fields.asbest_schadstoff_pruefung.label',
                    'label' => 'Asbest- & Schadstoffprüfung',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => [
                        ['value' => 'geprueft_negativ', 'label_key' => 'fields.options.geprueft_negativ', 'label' => 'Geprüft (schadstofffrei)'],
                        ['value' => 'belastet_sanierung_laeuft', 'label_key' => 'fields.options.belastet_sanierung_laeuft', 'label' => 'Belastet (Sanierung läuft)'],
                        ['value' => 'nicht_relevant', 'label_key' => 'fields.options.nicht_relevant', 'label' => 'Nicht relevant']
                    ],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'abnahme_mieter_erfolgt',
                    'label_key' => 'fields.abnahme_mieter_erfolgt.label',
                    'label' => 'Mieterabnahme erfolgt',
                    'field_type' => 'checkbox',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ]
    ];

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM project_templates WHERE id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO project_templates (id, name, name_key, category, subcategory, description, description_key, icon, is_system, lists, fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
    $updateStmt = $pdo->prepare("UPDATE project_templates SET name = ?, name_key = ?, category = ?, subcategory = ?, description = ?, description_key = ?, icon = ?, is_system = 1, lists = ?, fields = ? WHERE id = ?");

    foreach ($defaults as $d) {
        $checkStmt->execute([$d['id']]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            $updateStmt->execute([
                $d['name'], $d['name_key'] ?? null, $d['category'], $d['subcategory'], $d['description'], $d['description_key'] ?? null,
                $d['icon'], json_encode($d['lists']), json_encode($d['fields']), $d['id']
            ]);
        } else {
            $insertStmt->execute([
                $d['id'], $d['name'], $d['name_key'] ?? null, $d['category'], $d['subcategory'], $d['description'], $d['description_key'] ?? null,
                $d['icon'], json_encode($d['lists']), json_encode($d['fields'])
            ]);
        }
    }
}

function getDefaultEmailTemplates() {
    return [
        [
            'id' => 'tmpl_task_assigned',
            'trigger_event' => 'task_assigned',
            'name' => 'Aufgabe zugewiesen',
            'description' => 'Wird gesendet, wenn einem Benutzer eine neue Aufgabe zugewiesen wird.',
            'subject' => '[Taskster] Neue Aufgabe: {{task_title}}',
            'variables' => ['user_name', 'task_title', 'project_title', 'assigned_by', 'due_date', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\nDir wurde die Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" zugewiesen.\nFälligkeitsdatum: {{due_date}}\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #00A3C4; margin-bottom: 16px;">Neue Aufgabe zugewiesen</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p>Dir wurde eine neue Aufgabe zugewiesen:</p>
  <div style="background: #f8fafc; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
    <div style="font-size: 16px; font-weight: bold; color: #0f172a;">{{task_title}}</div>
    <div style="font-size: 13px; color: #64748b; margin-top: 4px;">Projekt: {{project_title}}</div>
    <div style="font-size: 13px; color: #64748b;">Fällig am: {{due_date}}</div>
  </div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Aufgabe öffnen</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_task_due',
            'trigger_event' => 'task_due',
            'name' => 'Aufgabe fällig',
            'description' => 'Wird gesendet, wenn eine Aufgabe heute oder bald fällig ist.',
            'subject' => '[Taskster] Erinnerung: Aufgabe {{task_title}} ist fällig',
            'variables' => ['user_name', 'task_title', 'project_title', 'due_date', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\nDie Aufgabe \"{{task_title}}\" im Projekt \"{{project_title}}\" ist heute bzw. bald fällig ({{due_date}}).\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #ea580c; margin-bottom: 16px;">Aufgabe ist fällig</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p>Die folgende Aufgabe erfordert deine Aufmerksamkeit:</p>
  <div style="background: #fff7ed; border-left: 4px solid #ea580c; padding: 12px; margin: 16px 0;">
    <div style="font-size: 16px; font-weight: bold; color: #9a3412;">{{task_title}}</div>
    <div style="font-size: 13px; color: #7c2d12; margin-top: 4px;">Projekt: {{project_title}} | Fällig: {{due_date}}</div>
  </div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #ea580c; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Jetzt bearbeiten</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_task_comment',
            'trigger_event' => 'task_comment',
            'name' => 'Neuer Aufgaben-Kommentar',
            'description' => 'Wird gesendet, wenn ein neuer Kommentar zu einer Aufgabe verfasst wurde.',
            'subject' => '[Taskster] Neuer Kommentar zu {{task_title}}',
            'variables' => ['user_name', 'author_name', 'task_title', 'comment_content', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\n{{author_name}} hat einen Kommentar zu \"{{task_title}}\" verfasst:\n\n\"{{comment_content}}\"\n\nZur Aufgabe: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #00A3C4; margin-bottom: 16px;">Neuer Kommentar</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p><strong>{{author_name}}</strong> hat zu <em>{{task_title}}</em> geschrieben:</p>
  <blockquote style="background: #f8fafc; border-left: 4px solid #cbd5e1; padding: 10px 14px; margin: 14px 0; font-style: italic;">{{comment_content}}</blockquote>
  <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kommentar ansehen & antworten</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_calendar_invite',
            'trigger_event' => 'calendar_invite',
            'name' => 'Termineinladung',
            'description' => 'Wird bei Einladungen zu Besprechungen/Terminen versendet.',
            'subject' => '[Taskster] Termineinladung: {{event_title}}',
            'variables' => ['user_name', 'inviter_name', 'event_title', 'event_start', 'event_end', 'event_location', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\n{{inviter_name}} hat dich zu folgendem Termin eingeladen:\n\nTermin: {{event_title}}\nZeit: {{event_start}} bis {{event_end}}\nOrt: {{event_location}}\n\nZum Termin: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #00A3C4; margin-bottom: 16px;">Termineinladung</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p><strong>{{inviter_name}}</strong> hat dich zu einem Termin eingeladen:</p>
  <div style="background: #f0fdfa; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
    <div style="font-size: 16px; font-weight: bold; color: #134e4a;">{{event_title}}</div>
    <div style="font-size: 13px; color: #115e59; margin-top: 4px;">📅 {{event_start}} - {{event_end}}</div>
    <div style="font-size: 13px; color: #115e59;">📍 {{event_location}}</div>
  </div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Termin im Kalender öffnen</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_calendar_reminder',
            'trigger_event' => 'calendar_reminder',
            'name' => 'Terminerinnerung',
            'description' => 'Wird vor Beginn eines anstehenden Termins versendet.',
            'subject' => '[Taskster] Erinnerung: {{event_title}}',
            'variables' => ['user_name', 'event_title', 'event_start', 'event_location', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\nErinnerung an deinen bevorstehenden Termin:\n\n{{event_title}}\nBeginn: {{event_start}}\nOrt: {{event_location}}\n\nZum Kalender: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #0284c7; margin-bottom: 16px;">Terminerinnerung</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p>Dein Termin beginnt in Kürze:</p>
  <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 12px; margin: 16px 0;">
    <div style="font-size: 16px; font-weight: bold; color: #0369a1;">{{event_title}}</div>
    <div style="font-size: 13px; color: #0284c7; margin-top: 4px;">⏰ {{event_start}} | 📍 {{event_location}}</div>
  </div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #0284c7; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Kalender anzeigen</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_mention',
            'trigger_event' => 'mention',
            'name' => 'Erwähnung (@Name)',
            'description' => 'Wird gesendet, wenn ein Benutzer in einem Text erwähnt wird.',
            'subject' => '[Taskster] {{author_name}} hat dich erwähnt',
            'variables' => ['user_name', 'author_name', 'context_title', 'mention_text', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\n{{author_name}} hat dich in \"{{context_title}}\" erwähnt:\n\n\"{{mention_text}}\"\n\nÖffnen: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #7c3aed; margin-bottom: 16px;">Du wurdest erwähnt</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p><strong>{{author_name}}</strong> hat dich in <em>{{context_title}}</em> erwähnt:</p>
  <div style="background: #faf5ff; border-left: 4px solid #7c3aed; padding: 12px; margin: 16px 0; font-style: italic;">{{mention_text}}</div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #7c3aed; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Zur Notiz / Aufgabe</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_budget_warning',
            'trigger_event' => 'budget_warning',
            'name' => 'Budgetwarnung',
            'description' => 'Wird bei Überschreiten von Budgetschwellen in Projekten gesendet.',
            'subject' => '[Taskster] Budget-Warnung: {{project_title}}',
            'variables' => ['user_name', 'project_title', 'budget_percent', 'tracked_hours', 'budget_hours', 'action_url'],
            'body_text' => "Hallo {{user_name}},\n\nDas Projekt \"{{project_title}}\" hat {{budget_percent}}% des geplanten Budgets erreicht ({{tracked_hours}} von {{budget_hours}} Stunden gebucht).\n\nDetails: {{action_url}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #dc2626; margin-bottom: 16px;">Budgetwarnung</h2>
  <p>Hallo <strong>{{user_name}}</strong>,</p>
  <p>Das Projekt <strong>{{project_title}}</strong> hat die Budgetgrenze erreicht:</p>
  <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px; margin: 16px 0;">
    <div style="font-size: 16px; font-weight: bold; color: #991b1b;">{{budget_percent}}% verbraucht</div>
    <div style="font-size: 13px; color: #b91c1c; margin-top: 4px;">{{tracked_hours}} von {{budget_hours}} Std. erfasst</div>
  </div>
  <p><a href="{{action_url}}" style="display: inline-block; background: #dc2626; color: #ffffff; padding: 10px 20px; text-decoration: none; border-radius: 6px; font-size: 13px; font-weight: bold;">Controlling ansehen</a></p>
</div>'
        ],
        [
            'id' => 'tmpl_company_invite',
            'trigger_event' => 'company_invite',
            'name' => 'Unternehmen-Einladung',
            'description' => 'Wird beim Einladen neuer Mitarbeiter in ein Unternehmen gesendet.',
            'subject' => 'Einladung zu {{company_name}} auf Taskster',
            'variables' => ['inviter_name', 'company_name', 'invite_link'],
            'body_text' => "Hallo,\n\n{{inviter_name}} hat dich eingeladen, dem Unternehmen \"{{company_name}}\" auf Taskster beizutreten.\n\nKlicke auf den folgenden Link, um deine Registrierung abzuschliessen:\n{{invite_link}}\n\nBeste Grüsse,\nDein Taskster Team",
            'body_html' => '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
  <h2 style="color: #00A3C4; margin-bottom: 16px;">Willkommen bei Taskster</h2>
  <p>Hallo,</p>
  <p><strong>{{inviter_name}}</strong> hat dich eingeladen, dem Unternehmen <strong>{{company_name}}</strong> auf Taskster beizutreten.</p>
  <p style="margin: 24px 0;"><a href="{{invite_link}}" style="display: inline-block; background: #00A3C4; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: bold;">Einladung annehmen & registrieren</a></p>
  <p style="font-size: 12px; color: #64748b;">Oder kopiere diesen Link in deinen Browser:<br><span style="font-family: monospace; color: #0f172a;">{{invite_link}}</span></p>
</div>'
        ]
    ];
}

function seedDefaultEmailTemplates($pdo) {
    $defaults = getDefaultEmailTemplates();
    $stmt = $pdo->prepare("
        INSERT INTO email_templates (id, trigger_event, name, description, subject, variables, body_text, body_html, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ON DUPLICATE KEY UPDATE name=VALUES(name), subject=VALUES(subject), variables=VALUES(variables), body_text=VALUES(body_text), body_html=VALUES(body_html)
    ");
    foreach ($defaults as $d) {
        try {
            $stmt->execute([
                $d['id'], $d['trigger_event'], $d['name'], $d['description'] ?? null,
                $d['subject'], json_encode($d['variables']), $d['body_text'], $d['body_html']
            ]);
        } catch (Exception $e) {}
    }
}

function getSmtpConfigDb($pdo) {
    $stmt = $pdo->query("SELECT `key`, `value` FROM system_settings WHERE `key` LIKE 'smtp_%' OR `key` = 'mail_provider' OR `key` = 'resend_api_key'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) {
        $map[$r['key']] = $r['value'];
    }
    $envResend = getEnvValue('RESEND_API_KEY');
    return [
        'mail_provider' => !empty($map['mail_provider']) ? $map['mail_provider'] : (!empty($envResend) || !empty($map['resend_api_key']) ? 'resend' : 'smtp'),
        'resend_api_key' => !empty($map['resend_api_key']) ? $map['resend_api_key'] : ($envResend ?: ''),
        'smtp_host' => !empty($map['smtp_host']) ? $map['smtp_host'] : 'mail.kurka.ch',
        'smtp_port' => !empty($map['smtp_port']) ? (int)$map['smtp_port'] : 465,
        'smtp_secure' => !empty($map['smtp_secure']) ? $map['smtp_secure'] : 'ssl',
        'smtp_user' => !empty($map['smtp_user']) ? $map['smtp_user'] : 'noreply@kurka.ch',
        'smtp_password' => isset($map['smtp_password']) ? $map['smtp_password'] : getEnvValue('SMTP_PASSWORD', ''),
        'smtp_from_email' => !empty($map['smtp_from_email']) ? $map['smtp_from_email'] : 'noreply@kurka.ch',
        'smtp_from_name' => !empty($map['smtp_from_name']) ? $map['smtp_from_name'] : 'Taskster'
    ];
}

function saveSmtpConfigDb($pdo, $config) {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (`key`, `value`)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)
    ");
    foreach ($config as $k => $v) {
        if ($v !== null) {
            $stmt->execute([$k, (string)$v]);
        }
    }
}

function getEmailSenderForPurpose($triggerEvent = null, $purpose = null) {
    $senders = [
        'onboarding' => [
            'email' => 'hey@kurka.ch',
            'name' => 'Taskster',
            'reply_to' => 'support@kurka.ch'
        ],
        'updates' => [
            'email' => 'updates@kurka.ch',
            'name' => 'Taskster',
            'reply_to' => null
        ],
        'collaboration' => [
            'email' => 'team@kurka.ch',
            'name' => 'Taskster',
            'reply_to' => null
        ],
        'notify' => [
            'email' => 'notify@kurka.ch',
            'name' => 'Taskster',
            'reply_to' => null
        ],
        'system' => [
            'email' => 'system@kurka.ch',
            'name' => 'Taskster',
            'reply_to' => null
        ]
    ];

    $p = $purpose ?: 'notify';
    if (!$purpose && $triggerEvent) {
        if (in_array($triggerEvent, ['company_invite', 'user_welcome', 'onboarding', 'invite'])) {
            $p = 'onboarding';
        } elseif (in_array($triggerEvent, ['updates', 'changelog', 'newsletter', 'product_news'])) {
            $p = 'updates';
        } elseif (in_array($triggerEvent, ['task_assigned', 'task_comment', 'mention', 'calendar_invite', 'calendar_change', 'calendar_cancel'])) {
            $p = 'collaboration';
        } elseif (in_array($triggerEvent, ['task_due', 'calendar_reminder', 'budget_warning', 'digest'])) {
            $p = 'notify';
        } elseif (in_array($triggerEvent, ['password_reset', 'security_alert', 'account_change', '2fa', 'auth_verification'])) {
            $p = 'system';
        }
    }

    $sender = $senders[$p] ?? $senders['notify'];
    return [
        'from' => "{$sender['name']} <{$sender['email']}>",
        'from_email' => $sender['email'],
        'from_name' => $sender['name'],
        'reply_to' => $sender['reply_to']
    ];
}

function sendResendEmailNative($apiKey, $to, $toName, $subject, $bodyHtml = '', $bodyText = '', $icsContent = null, $outboxId = null, $fromEmail = null, $fromName = null, $replyTo = null) {
    $log = [];
    $db = getDb();

    if (!$outboxId) {
        $outboxId = 'mail_' . substr(bin2hex(random_bytes(6)), 0, 8);
        try {
            $stmt = $db->prepare("
                INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status, attempts)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 1)
            ");
            $stmt->execute([$outboxId, $to, $toName ?: null, $subject, $bodyHtml ?: $bodyText, $icsContent]);
        } catch (Exception $e) {}
    } else {
        try {
            $db->prepare("UPDATE email_outbox SET status = 'processing', attempts = attempts + 1 WHERE id = ?")->execute([$outboxId]);
        } catch (Exception $e) {}
    }

    $fn = !empty($fromName) ? $fromName : 'Taskster';
    $fe = !empty($fromEmail) ? $fromEmail : 'noreply@kurka.ch';
    $from = "{$fn} <{$fe}>";
    $payload = [
        'from' => $from,
        'to' => [$to],
        'subject' => $subject,
        'html' => $bodyHtml ?: nl2br(htmlspecialchars($bodyText)),
        'text' => $bodyText ?: strip_tags($bodyHtml)
    ];

    if (!empty($replyTo)) {
        $payload['reply_to'] = $replyTo;
    }

    if (!empty($icsContent)) {
        $payload['headers'] = [
            'Content-Class' => 'urn:content-classes:calendarmessage'
        ];
        $payload['attachments'] = [
            [
                'filename' => 'invite.ics',
                'content' => base64_encode($icsContent)
            ]
        ];
    }

    $log[] = "> [Resend API] Sende E-Mail an {$to} via {$from}" . (!empty($replyTo) ? " (Reply-To: {$replyTo})" : "");

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 15
    ]);

    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($res === false) {
        $errMsg = 'Netzwerkfehler zu Resend: ' . $curlErr;
        $log[] = '! ' . $errMsg;
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $data = json_decode($res, true);
    if ($httpCode >= 200 && $httpCode < 300) {
        $log[] = '< [Resend Success] id: ' . ($data['id'] ?? '');
        try { $db->prepare("UPDATE email_outbox SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$outboxId]); } catch (Exception $e) {}
        return ['success' => true, 'log' => $log];
    } else {
        $errMsg = 'Resend Fehler (' . $httpCode . '): ' . ($data['message'] ?? $res);
        $log[] = '! ' . $errMsg;
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }
}

function sendSmtpEmailNative($cfg, $to, $toName, $subject, $bodyHtml = '', $bodyText = '', $icsContent = null, $outboxId = null, $triggerEvent = null, $purpose = null, $customFromEmail = null, $customReplyTo = null) {
    $provider = $cfg['mail_provider'] ?? 'resend';
    $resendKey = $cfg['resend_api_key'] ?? getEnvValue('RESEND_API_KEY');
    if ($provider === 'resend' && !empty($resendKey) && strpos($resendKey, 're_') === 0 && $resendKey !== 're_xxxxxxxxx') {
        $sender = getEmailSenderForPurpose($triggerEvent, $purpose);
        $fromEmail = !empty($customFromEmail) ? $customFromEmail : $sender['from_email'];
        $fromName = $sender['from_name'];
        $replyTo = !empty($customReplyTo) ? $customReplyTo : $sender['reply_to'];
        return sendResendEmailNative($resendKey, $to, $toName, $subject, $bodyHtml, $bodyText, $icsContent, $outboxId, $fromEmail, $fromName, $replyTo);
    }

    $log = [];
    $db = getDb();
    
    if (!$outboxId) {
        $outboxId = 'mail_' . substr(bin2hex(random_bytes(6)), 0, 8);
        try {
            $stmt = $db->prepare("
                INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status, attempts)
                VALUES (?, ?, ?, ?, ?, ?, 'pending', 1)
            ");
            $stmt->execute([$outboxId, $to, $toName ?: null, $subject, $bodyHtml ?: $bodyText, $icsContent]);
        } catch (Exception $e) {}
    } else {
        try {
            $db->prepare("UPDATE email_outbox SET status = 'processing', attempts = attempts + 1 WHERE id = ?")->execute([$outboxId]);
        } catch (Exception $e) {}
    }

    $host = !empty($cfg['smtp_host']) ? $cfg['smtp_host'] : 'mail.kurka.ch';
    $port = !empty($cfg['smtp_port']) ? (int)$cfg['smtp_port'] : 465;
    $secure = !empty($cfg['smtp_secure']) ? $cfg['smtp_secure'] : 'ssl';
    $isSsl = ($secure === 'ssl' || $port === 465);
    $user = isset($cfg['smtp_user']) ? $cfg['smtp_user'] : '';
    $pass = isset($cfg['smtp_password']) ? $cfg['smtp_password'] : '';
    $fromEmail = !empty($cfg['smtp_from_email']) ? $cfg['smtp_from_email'] : 'noreply@kurka.ch';
    $fromName = !empty($cfg['smtp_from_name']) ? $cfg['smtp_from_name'] : 'Taskster';

    $prefix = $isSsl ? 'ssl://' : 'tcp://';
    $socketAddress = $prefix . $host . ':' . $port;

    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $errno = 0;
    $errstr = '';
    $fp = @stream_socket_client($socketAddress, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $context);
    if (!$fp) {
        $errMsg = "Verbindung fehlgeschlagen zu $socketAddress ($errno: $errstr)";
        $log[] = "! " . $errMsg;
        try {
            $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]);
        } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    stream_set_timeout($fp, 15);

    $readResponse = function() use ($fp, &$log) {
        $response = '';
        while (!feof($fp)) {
            $line = fgets($fp, 1024);
            if ($line === false) break;
            $response .= $line;
            $log[] = '< ' . trim($line);
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $sendCommand = function($cmd, $mask = false) use ($fp, &$log) {
        $log[] = '> ' . ($mask ? '********' : $cmd);
        fwrite($fp, $cmd . "\r\n");
    };

    $res = $readResponse();
    if (substr(trim($res), 0, 3) !== '220') {
        fclose($fp);
        $errMsg = "Ungültige Serverantwort beim Verbinden: " . trim($res);
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $fromDomain = substr(strrchr($fromEmail, '@'), 1) ?: 'kurka.ch';
    $ehloDomain = !empty($host) ? $host : $fromDomain;

    $sendCommand("EHLO {$ehloDomain}");
    $res = $readResponse();

    if ($secure === 'tls' && !$isSsl) {
        $sendCommand("STARTTLS");
        $res = $readResponse();
        if (substr(trim($res), 0, 3) === '220') {
            stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $sendCommand("EHLO {$ehloDomain}");
            $res = $readResponse();
        }
    }

    if ($user && $pass) {
        $sendCommand("AUTH LOGIN");
        $res = $readResponse();
        if (substr(trim($res), 0, 3) !== '334') {
            fclose($fp);
            $errMsg = "AUTH LOGIN fehlgeschlagen: " . trim($res);
            try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
            return ['success' => false, 'error' => $errMsg, 'log' => $log];
        }

        $sendCommand(base64_encode($user));
        $res = $readResponse();
        if (substr(trim($res), 0, 3) !== '334') {
            fclose($fp);
            $errMsg = "Benutzername abgelehnt: " . trim($res);
            try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
            return ['success' => false, 'error' => $errMsg, 'log' => $log];
        }

        $sendCommand(base64_encode($pass), true);
        $res = $readResponse();
        if (substr(trim($res), 0, 3) !== '235') {
            fclose($fp);
            $errMsg = "Passwort abgelehnt / Authentifizierungsfehler: " . trim($res);
            try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
            return ['success' => false, 'error' => $errMsg, 'log' => $log];
        }
    }

    $sendCommand("MAIL FROM:<{$fromEmail}>");
    $res = $readResponse();
    if (substr(trim($res), 0, 3) !== '250') {
        fclose($fp);
        $errMsg = "MAIL FROM abgelehnt: " . trim($res);
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $sendCommand("RCPT TO:<{$to}>");
    $res = $readResponse();
    if (substr(trim($res), 0, 3) !== '250') {
        fclose($fp);
        $errMsg = "RCPT TO für <{$to}> abgelehnt: " . trim($res);
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $sendCommand("DATA");
    $res = $readResponse();
    if (substr(trim($res), 0, 3) !== '354') {
        fclose($fp);
        $errMsg = "DATA abgelehnt: " . trim($res);
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $boundary = '=_Part_' . md5(uniqid(microtime(true), true));
    $altBoundary = '=_Alt_' . md5(uniqid(microtime(true) . 'alt', true));
    $fromHeader = $fromName ? "=?UTF-8?B?" . base64_encode($fromName) . "?= <{$fromEmail}>" : "<{$fromEmail}>";
    $toHeader = $toName ? "=?UTF-8?B?" . base64_encode($toName) . "?= <{$to}>" : "<{$to}>";
    $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    $messageId = "<" . md5(uniqid(microtime(true), true)) . "@" . $fromDomain . ">";

    $headers = [
        "From: {$fromHeader}",
        "Reply-To: {$fromHeader}",
        "To: {$toHeader}",
        "Subject: {$encodedSubject}",
        "Date: " . date('r'),
        "MIME-Version: 1.0",
        "Message-ID: {$messageId}",
        "Auto-Submitted: auto-generated",
        "X-Mailer: Taskster"
    ];

    if (!empty($icsContent)) {
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"";
        $headers[] = "Content-Class: urn:content-classes:calendarmessage";
        $body = "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($bodyText ?: strip_tags($bodyHtml))) . "\r\n";
        $body .= "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($bodyHtml ?: nl2br(htmlspecialchars($bodyText)))) . "\r\n";
        $body .= "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/calendar; charset=UTF-8; method=REQUEST\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($icsContent)) . "\r\n";
        $body .= "--{$altBoundary}--\r\n";
    } elseif (!empty($bodyHtml)) {
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$altBoundary}\"";
        $body = "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($bodyText ?: strip_tags($bodyHtml))) . "\r\n";

        $body .= "--{$altBoundary}\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\n";
        $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
        $body .= "--{$altBoundary}--\r\n";
    } else {
        $headers[] = "Content-Type: text/plain; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";
        $body = chunk_split(base64_encode($bodyText)) . "\r\n";
    }

    $rawMessage = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
    fwrite($fp, $rawMessage . "\r\n");
    $log[] = '> [MIME Body gesendet (' . strlen($rawMessage) . ' Bytes)]';

    $res = $readResponse();
    if (substr(trim($res), 0, 3) !== '250') {
        fclose($fp);
        $errMsg = "Nachrichtensendung fehlgeschlagen: " . trim($res);
        try { $db->prepare("UPDATE email_outbox SET status = 'error', error = ? WHERE id = ?")->execute([$errMsg, $outboxId]); } catch (Exception $e) {}
        return ['success' => false, 'error' => $errMsg, 'log' => $log];
    }

    $sendCommand("QUIT");
    $readResponse();
    fclose($fp);

    try {
        $db->prepare("UPDATE email_outbox SET status = 'sent', sent_at = NOW() WHERE id = ?")->execute([$outboxId]);
    } catch (Exception $e) {}

    return ['success' => true, 'log' => $log];
}

function sendTriggerEmailNative($pdo, $triggerEvent, $recipient, $data = []) {
    if (!empty($recipient['settings'])) {
        $settings = is_string($recipient['settings']) ? json_decode($recipient['settings'], true) : $recipient['settings'];
        if (isset($settings['notifications'])) {
            if (isset($settings['notifications']['email']) && $settings['notifications']['email'] === false) {
                return null;
            }
            if (isset($settings['notifications']['events'][$triggerEvent]) && $settings['notifications']['events'][$triggerEvent] === false) {
                return null;
            }
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM email_templates WHERE trigger_event = ? AND is_active = 1");
    $stmt->execute([$triggerEvent]);
    $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$tmpl) return null;

    $subject = $tmpl['subject'];
    $bodyHtml = $tmpl['body_html'];
    $bodyText = $tmpl['body_text'];

    $mergedData = array_merge([
        'user_name' => !empty($recipient['name']) ? $recipient['name'] : $recipient['email'],
        'user_email' => $recipient['email'],
        'action_url' => 'https://taskster.ch'
    ], $data);

    foreach ($mergedData as $k => $v) {
        $ph = '{{' . $k . '}}';
        $vStr = is_scalar($v) ? (string)$v : '';
        $subject = str_replace($ph, $vStr, $subject);
        $bodyHtml = str_replace($ph, $vStr, $bodyHtml);
        $bodyText = str_replace($ph, $vStr, $bodyText);
    }

    $cfg = getSmtpConfigDb($pdo);
    return sendSmtpEmailNative($cfg, $recipient['email'], $recipient['name'] ?? null, $subject, $bodyHtml, $bodyText, $data['ics_content'] ?? null, null, $triggerEvent);
}


function base64UrlEncode($data) {
    return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
}

function base64UrlDecode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $padlen = 4 - $remainder;
        $data .= str_repeat('=', $padlen);
    }
    return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
}

function jwtEncode($payload, $secret) {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode(json_encode($payload));
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
    $base64Signature = base64UrlEncode($signature);
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function jwtDecode($jwt, $secret) {
    $tokenParts = explode('.', $jwt);
    if (count($tokenParts) !== 3) return null;
    $header = base64UrlDecode($tokenParts[0]);
    $payload = base64UrlDecode($tokenParts[1]);
    $signatureProvided = $tokenParts[2];
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
    $base64Signature = base64UrlEncode($signature);
    if ($base64Signature !== $signatureProvided) return null;
    return json_decode($payload, true);
}

function getJsonBody() {
    $raw = file_get_contents('php://input');
    return $raw ? json_decode($raw, true) : [];
}

function processEmailOutbox() {
    try {
        $db = getDb();
        $cfg = getSmtpConfigDb($db);
        $stmt = $db->query("SELECT * FROM email_outbox WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10");
        $emails = $stmt->fetchAll();
        foreach ($emails as $email) {
            sendSmtpEmailNative($cfg, $email['to_email'], $email['to_name'], $email['subject'], $email['body'], $email['body'], $email['ics_content'], $email['id']);
        }
    } catch (Exception $e) {}
}

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
    processEmailOutbox();
    exit;
}

function errorResponse($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['statusCode' => $status, 'statusMessage' => $message]);
    exit;
}

/**
 * Datums-Parsing & Normalisierung für CSV-/Excel-Importe
 * Konvertiert u. a. serielle Excel-Tageswerte (z. B. 46272 -> 2026-09-07) sowie DD.MM.YYYY in YYYY-MM-DD.
 */
function parseImportDate($value): ?string {
    if ($value === null || $value === '') return null;
    $strVal = trim((string)$value);
    if ($strVal === '') return null;

    if (is_numeric($strVal) && (int)$strVal > 25569 && (int)$strVal < 60000) {
        $baseDate = new DateTime('1899-12-30');
        $baseDate->modify('+' . (int)$strVal . ' days');
        return $baseDate->format('Y-m-d');
    }

    // DD.MM.YYYY -> YYYY-MM-DD
    if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $strVal, $matches)) {
        return sprintf('%04d-%02d-%02d', (int)$matches[3], (int)$matches[2], (int)$matches[1]);
    }

    // DD/MM/YYYY -> YYYY-MM-DD
    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $strVal, $matches)) {
        return sprintf('%04d-%02d-%02d', (int)$matches[3], (int)$matches[2], (int)$matches[1]);
    }

    if (preg_match('/^\d{4}-\d{2}-\d{2}/', $strVal)) {
        return substr($strVal, 0, 10);
    }

    return $strVal;
}

// ---------------------------------------------------------------------------
// AI (OpenRouter / DeepSeek V4 Flash) — serverseitig, Key bleibt in .env
// ---------------------------------------------------------------------------

/**
 * Liest die zentrale AI-Konfiguration aus ai.config.json (Single Source of Truth).
 * Faellt auf sichere Defaults zurueck, falls die Datei fehlt.
 */
function getAiConfig() {
    static $config = null;
    if ($config !== null) return $config;

    $defaults = [
        'model' => 'google/gemini-2.5-flash',
        'provider' => ['allow_fallbacks' => true],
        'temperature' => 0.3,
        'max_tokens' => 2048,
        'timeout_seconds' => 30,
        'system_prompt' => 'Du bist ein praeziser technischer Assistent fuer das Taskster-Projekt.'
    ];

    $candidates = [
        dirname(__DIR__) . '/ai.config.json',
        dirname(__DIR__, 2) . '/ai.config.json',
        ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/ai.config.json',
        __DIR__ . '/ai.config.json'
    ];
    $path = null;
    foreach ($candidates as $c) {
        if (!empty($c) && is_file($c)) {
            $path = $c;
            break;
        }
    }

    if (!$path) {
        $config = $defaults;
        return $config;
    }

    $raw = @file_get_contents($path);
    $parsed = $raw ? json_decode($raw, true) : null;
    $config = is_array($parsed) ? array_merge($defaults, $parsed) : $defaults;
    return $config;
}

/**
 * Liest einen Wert aus der .env im Projekt-Root (ohne externe Abhaengigkeit).
 * Echte Umgebungsvariablen (getenv) haben Vorrang.
 */
function getEnvValue($key, $default = null) {
    $value = getenv($key);
    if ($value !== false && $value !== '') return $value;

    static $dotenv = null;
    if ($dotenv === null) {
        $dotenv = [];
        $candidates = [
            dirname(__DIR__) . '/.env',
            dirname(__DIR__, 2) . '/.env',
            dirname(__DIR__, 3) . '/.env',
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . '/.env',
            __DIR__ . '/.env'
        ];
        $path = null;
        foreach ($candidates as $c) {
            if (!empty($c) && is_file($c)) {
                $path = $c;
                break;
            }
        }
        if ($path && is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                $line = trim($line);
                if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                list($k, $v) = explode('=', $line, 2);
                $k = trim($k);
                $v = trim($v);
                if (strlen($v) >= 2 && (($v[0] === '"' && substr($v, -1) === '"') || ($v[0] === "'" && substr($v, -1) === "'"))) {
                    $v = substr($v, 1, -1);
                }
                $dotenv[$k] = $v;
            }
        }
    }
    return $dotenv[$key] ?? $default;
}

/**
 * Ruft OpenRouter auf und liefert den Antworttext.
 * Endpoint-Pinning via provider.only + allow_fallbacks=false (siehe ai.config.json).
 *
 * @throws Exception bei Netzwerk-, Auth- oder Upstream-Fehlern.
 */
function callOpenRouter($messages, $overrides = []) {
    $config = getAiConfig();
    $apiKey = getEnvValue('OPENROUTER_API_KEY');
    if (!$apiKey) {
        throw new Exception('OPENROUTER_API_KEY fehlt (bitte in .env setzen)', 500);
    }

    $payload = [
        'model' => $overrides['model'] ?? $config['model'],
        'messages' => $messages,
        'temperature' => $overrides['temperature'] ?? $config['temperature'],
        'max_tokens' => $overrides['max_tokens'] ?? $config['max_tokens'],
        'provider' => $config['provider']
    ];

    $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
            'HTTP-Referer: https://taskster.ch',
            'X-Title: Taskster'
        ],
        CURLOPT_TIMEOUT => (int)($config['timeout_seconds'] ?? 120),
        CURLOPT_CONNECTTIMEOUT => 15
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        throw new Exception('Netzwerkfehler zu OpenRouter: ' . $curlError, 502);
    }

    $data = json_decode($response, true);

    if ($httpCode < 200 || $httpCode >= 300) {
        $upstream = $data['error']['message'] ?? substr((string)$response, 0, 400);
        $hint = '';
        if ($httpCode === 401) $hint = ' (API-Key ungueltig)';
        if ($httpCode === 402) $hint = ' (kein Guthaben)';
        if ($httpCode === 404) $hint = ' (Modell/Endpoint nicht verfuegbar - baidu/fp8 gepinnt)';
        if ($httpCode === 429) $hint = ' (Rate-Limit)';
        throw new Exception('OpenRouter ' . $httpCode . $hint . ': ' . $upstream, 502);
    }

    $text = $data['choices'][0]['message']['content'] ?? '';
    if ($text === '') {
        throw new Exception('Leere Antwort von OpenRouter', 502);
    }

    return [
        'text' => $text,
        'usage' => $data['usage'] ?? null,
        'model' => $data['model'] ?? $payload['model']
    ];
}

// ---------------------------------------------------------------------------
// KALENDER / TERMINE (ICS-Export, E-Mail-Outbox)
// ---------------------------------------------------------------------------

/** Formatiert ein Datum als ICS-Zeitstempel (UTC). */
function toIcsDate($value) {
    $ts = strtotime(str_replace('T', ' ', (string)$value));
    if ($ts === false) return '';
    return gmdate('Ymd\THis\Z', $ts);
}

/** Formatiert ein Datum als ICS-Ganztag (YYYYMMDD). */
function toIcsDateOnly($value) {
    $ts = strtotime(str_replace('T', ' ', (string)$value));
    if ($ts === false) return '';
    return gmdate('Ymd', $ts);
}

/** Escaped Sonderzeichen fuer ICS-Texte. */
function icsEscape($text) {
    $text = str_replace('\\', '\\\\', (string)$text);
    $text = str_replace(';', '\\;', $text);
    $text = str_replace(',', '\\,', $text);
    return str_replace(["\r\n", "\n"], '\\n', $text);
}

/** Faltet lange Zeilen nach RFC 5545. */
function icsFold($line) {
    if (strlen($line) <= 75) return $line;
    $out = substr($line, 0, 75);
    $rest = substr($line, 75);
    while (strlen($rest) > 0) {
        $out .= "\r\n " . substr($rest, 0, 74);
        $rest = substr($rest, 74);
    }
    return $out;
}

/**
 * Erzeugt eine ICS-Datei.
 * $method: REQUEST (Einladung), CANCEL (Absage), PUBLISH (Export)
 */
function buildIcs($evt, $attendees = [], $method = 'REQUEST', $sequence = 0) {
    $allDay = !empty($evt['all_day']);
    $icsDomain = !empty($evt['owner_email']) && strpos($evt['owner_email'], '@') !== false ? substr(strrchr($evt['owner_email'], '@'), 1) : 'kurka.ch';
    $lines = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'PRODID:-//Taskster//Kalender//DE',
        'CALSCALE:GREGORIAN',
        'METHOD:' . $method,
        'BEGIN:VEVENT',
        'UID:' . $evt['id'] . '@' . $icsDomain,
        'DTSTAMP:' . toIcsDate(date('Y-m-d H:i:s'))
    ];

    if ($allDay) {
        $lines[] = 'DTSTART;VALUE=DATE:' . toIcsDateOnly($evt['start_at']);
        $endTs = strtotime(str_replace('T', ' ', $evt['end_at']));
        $lines[] = 'DTEND;VALUE=DATE:' . gmdate('Ymd', $endTs + 86400);
    } else {
        $lines[] = 'DTSTART:' . toIcsDate($evt['start_at']);
        $lines[] = 'DTEND:' . toIcsDate($evt['end_at']);
    }

    $lines[] = icsFold('SUMMARY:' . icsEscape($evt['title']));
    if (!empty($evt['description'])) $lines[] = icsFold('DESCRIPTION:' . icsEscape($evt['description']));
    if (!empty($evt['location'])) $lines[] = icsFold('LOCATION:' . icsEscape($evt['location']));

    $lines[] = 'STATUS:' . (($evt['status'] ?? '') === 'cancelled' ? 'CANCELLED' : 'CONFIRMED');
    $lines[] = 'SEQUENCE:' . (int)$sequence;

    if (!empty($evt['owner_email'])) {
        $cn = !empty($evt['owner_name']) ? ';CN=' . icsEscape($evt['owner_name']) : '';
        $lines[] = icsFold('ORGANIZER' . $cn . ':mailto:' . $evt['owner_email']);
    }

    foreach ($attendees as $a) {
        $cn = !empty($a['name']) ? ';CN=' . icsEscape($a['name']) : '';
        $st = $a['status'] ?? 'pending';
        $partstat = $st === 'accepted' ? 'ACCEPTED' : ($st === 'declined' ? 'DECLINED' : ($st === 'tentative' ? 'TENTATIVE' : 'NEEDS-ACTION'));
        $lines[] = icsFold('ATTENDEE' . $cn . ';PARTSTAT=' . $partstat . ';ROLE=REQ-PARTICIPANT:mailto:' . $a['email']);
    }

    $lines[] = 'END:VEVENT';
    $lines[] = 'END:VCALENDAR';
    return implode("\r\n", $lines);
}

/** Erzeugt den Text einer Einladungs-Mail. */
function buildInviteBody($organizerName, $title, $startAt, $endAt, $location = null, $description = null, $allDay = false) {
    $fmt = function ($v) {
        $ts = strtotime(str_replace('T', ' ', (string)$v));
        return $ts === false ? $v : date('d.m.Y H:i', $ts);
    };
    $lines = [
        $organizerName . ' lädt dich zu einem Termin ein:',
        '',
        'Betreff:  ' . $title,
        'Beginn:   ' . $fmt($startAt) . ($allDay ? ' (ganztägig)' : ''),
        'Ende:     ' . $fmt($endAt) . ($allDay ? ' (ganztägig)' : '')
    ];
    if ($location) $lines[] = 'Ort:      ' . $location;
    if ($description) { $lines[] = ''; $lines[] = 'Beschreibung:'; $lines[] = $description; }
    $lines[] = '';
    $lines[] = 'Die angehängte Datei (termin.ics) kannst du direkt in Outlook, Google Kalender';
    $lines[] = 'oder Apple Kalender öffnen, um den Termin zu übernehmen.';
    $lines[] = '';
    $lines[] = '— Taskster';
    return implode("\n", $lines);
}

/**
 * Parst und dekodiert rohen E-Mail-Text (MIME Multipart, Base64, Quoted-Printable, RFC 2047 Headers).
 * Verhindert WAF-Blockaden (HTTP 403) und decodiert Base64-Inhalte zuverlässig in UTF-8.
 */
function parseMimeEmailText($rawText) {
    if (empty($rawText) || !is_string($rawText)) {
        return ['subject' => '', 'sender_name' => '', 'sender_email' => '', 'body' => ''];
    }

    $subject = '';
    $fromName = '';
    $fromEmail = '';
    $body = '';

    $decodeMimeHeader = function($str) {
        if (function_exists('iconv_mime_decode')) {
            $dec = @iconv_mime_decode($str, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');
            if ($dec !== false && $dec !== '') return $dec;
        }
        return preg_replace_callback('/=\?([^?]+)\?([BQ])\?([^?]+)\?=/i', function($m) {
            $charset = strtolower($m[1]);
            $encoding = strtoupper($m[2]);
            $data = $m[3];
            if ($encoding === 'B') {
                $decoded = base64_decode($data);
                if (function_exists('mb_convert_encoding') && !str_contains($charset, 'utf')) {
                    $decoded = @mb_convert_encoding($decoded, 'UTF-8', $charset);
                }
                return $decoded;
            } elseif ($encoding === 'Q') {
                return quoted_printable_decode(str_replace('_', ' ', $data));
            }
            return $m[0];
        }, $str);
    };

    if (preg_match('/^Subject:\s*(.+?)(?=\r?\n[^\s]|$)/im', $rawText, $sm)) {
        $subject = trim($decodeMimeHeader(preg_replace('/\r?\n\s+/', ' ', $sm[1])));
    }
    if (preg_match('/^From:\s*(.+?)(?=\r?\n[^\s]|$)/im', $rawText, $fm)) {
        $rawFrom = trim($decodeMimeHeader(preg_replace('/\r?\n\s+/', ' ', $fm[1])));
        if (preg_match('/<([^>]+)>/', $rawFrom, $em)) {
            $fromEmail = strtolower(trim($em[1]));
            $fromName = trim(str_replace(['"', "'"], '', preg_replace('/<[^>]+>/', '', $rawFrom)));
        } else {
            $fromEmail = strtolower($rawFrom);
            $fromName = explode('@', $rawFrom)[0];
        }
    }

    // Multipart Boundary
    if (preg_match('/boundary=["\']?([^"\'\r\n;]+)["\']?/i', $rawText, $bm)) {
        $boundary = $bm[1];
        $parts = preg_split('/--' . preg_quote($boundary, '/') . '(?:--)?/', $rawText);
        $plainPart = '';
        $htmlPart = '';
        foreach ($parts as $part) {
            $tPart = trim($part);
            if (empty($tPart) || $tPart === '--') continue;
            $split = preg_split('/\r?\n\r?\n/', $tPart, 2);
            $partHeaders = $split[0] ?? '';
            $partBody = $split[1] ?? '';

            $isPlain = stripos($partHeaders, 'text/plain') !== false;
            $isHtml = stripos($partHeaders, 'text/html') !== false;
            $isBase64 = stripos($partHeaders, 'base64') !== false;
            $isQP = stripos($partHeaders, 'quoted-printable') !== false;

            $decoded = $partBody;
            if ($isBase64) {
                $decoded = base64_decode(preg_replace('/\s+/', '', $partBody));
            } elseif ($isQP) {
                $decoded = quoted_printable_decode($partBody);
            }

            if ($isPlain && empty($plainPart)) {
                $plainPart = trim($decoded);
            } elseif ($isHtml && empty($htmlPart)) {
                $htmlPart = trim($decoded);
            }
        }
        if (!empty($plainPart)) {
            $body = $plainPart;
        } elseif (!empty($htmlPart)) {
            $body = trim(strip_tags(preg_replace('/<(?:br|\/p)>/i', "\n", $htmlPart)));
        }
    }

    // Single-Part Fallback
    if (empty($body)) {
        if (stripos($rawText, 'Content-Transfer-Encoding: base64') !== false) {
            $split = preg_split('/\r?\n\r?\n/', $rawText, 2);
            if (isset($split[1])) {
                $decoded = base64_decode(preg_replace('/\s+/', '', $split[1]));
                if (!empty($decoded)) $body = trim($decoded);
            }
        } elseif (stripos($rawText, 'Content-Transfer-Encoding: quoted-printable') !== false) {
            $split = preg_split('/\r?\n\r?\n/', $rawText, 2);
            if (isset($split[1])) {
                $body = trim(quoted_printable_decode($split[1]));
            }
        } else {
            $body = trim($rawText);
        }
    }

    // Restliche MIME-Boundary-Zeilen und Content-Type Header strippen (verhindert WAF 403)
    $body = preg_replace('/^--[a-zA-Z0-9_-]+[^\n]*\n?/m', '', $body);
    $body = preg_replace('/^Content-(?:Type|Transfer-Encoding|Disposition):[^\n]*\n?/im', '', $body);
    $body = trim($body);

    return [
        'subject' => $subject,
        'sender_name' => $fromName,
        'sender_email' => $fromEmail,
        'body' => $body ?: $rawText
    ];
}

/** Legt eine E-Mail in die Outbox (Versand spaeter per Cron/Worker). */
function queueEmail($to, $toName, $subject, $body, $ics = null) {
    try {
        $db = getDb();
        $id = 'mail_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("
            INSERT INTO email_outbox (id, to_email, to_name, subject, body, ics_content, status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending')
        ")->execute([$id, $to, $toName, $subject, $body, $ics]);
        return $id;
    } catch (Exception $e) {
        return null;
    }
}

/** Prueft, ob der Nutzer einen Termin sehen darf. */
function canAccessEvent($user, $evt) {
    if (!$evt) return false;
    if ($evt['owner_id'] === $user['id']) return true;
    if (!empty($user['is_superadmin'])) return true;
    $db = getDb();
    $s = $db->prepare("SELECT 1 FROM event_attendees WHERE event_id = ? AND (user_id = ? OR LOWER(email) = LOWER(?))");
    $s->execute([$evt['id'], $user['id'], $user['email']]);
    if ($s->fetchColumn()) return true;
    if (($evt['visibility'] ?? '') === 'company' && !empty($evt['company_id']) && $evt['company_id'] === ($user['company_id'] ?? null)) {
        return true;
    }
    return false;
}

/** Prueft, ob der Nutzer einen Termin bearbeiten darf. */
function canEditEvent($user, $evt) {
    if (!$evt) return false;
    if ($evt['owner_id'] === $user['id']) return true;
    return !empty($user['is_superadmin']);
}

/**
 * Persoenliche Benutzer-Einstellungen (users.settings, JSON).
 * Serverseitige Normalisierung: fehlende oder manipulierte Werte koennen nie
 * zu ungueltigen Zustaenden im Client fuehren.
 */
function defaultUserSettings() {
    return [
        'language' => 'de',
        'whisper_language' => 'de',
        'theme' => 'light',
        'density' => 'comfortable',
        'start_page' => 'dashboard',
        'timezone' => 'Europe/Zurich',
        'calendar' => [
            'default_view' => 'month',
            'week_start' => 1,
            'show_week_numbers' => false,
            'show_weekends' => true,
            'workday_start' => '07:00',
            'workday_end' => '17:00',
            'slot_minutes' => 30,
            'default_duration_minutes' => 60,
            'default_reminder_minutes' => 15,
            'default_category_id' => null,
            'default_visibility' => 'private',
            'show_tasks' => true,
            'show_declined' => false,
            'time_format' => '24h',
        ],
        'notifications' => [
            'browser' => false,
            'email' => true,
            'in_app' => true,
            'sound' => false,
            'digest' => 'off',
            'events' => [
                'calendar_invite' => true,
                'calendar_change' => true,
                'calendar_cancel' => true,
                'calendar_reminder' => true,
                'task_assigned' => true,
                'task_due' => true,
                'task_comment' => true,
                'mention' => true,
                'budget_warning' => true,
            ],
        ],
    ];
}

function pickEnum($value, $allowed, $fallback) {
    return (is_string($value) && in_array($value, $allowed, true)) ? $value : $fallback;
}

function pickBool($value, $fallback) {
    return is_bool($value) ? $value : $fallback;
}

function pickInt($value, $allowed, $fallback) {
    $n = is_numeric($value) ? (int)$value : null;
    return ($n !== null && in_array($n, $allowed, true)) ? $n : $fallback;
}

function pickTime($value, $fallback) {
    return (is_string($value) && preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $value)) ? $value : $fallback;
}

function pickTimezone($value, $fallback) {
    if (!is_string($value) || trim($value) === '') return $fallback;
    try {
        new DateTimeZone(trim($value));
        return trim($value);
    } catch (Exception $e) {
        return $fallback;
    }
}

function normalizeUserSettings($raw) {
    $d = defaultUserSettings();
    if (is_string($raw)) {
        $decoded = json_decode($raw, true);
        $raw = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($raw)) $raw = [];

    $cal = (isset($raw['calendar']) && is_array($raw['calendar'])) ? $raw['calendar'] : [];
    $notif = (isset($raw['notifications']) && is_array($raw['notifications'])) ? $raw['notifications'] : [];
    $ev = (isset($notif['events']) && is_array($notif['events'])) ? $notif['events'] : [];

    $duration = isset($cal['default_duration_minutes']) && is_numeric($cal['default_duration_minutes'])
        ? (int)$cal['default_duration_minutes'] : null;
    if ($duration !== null && ($duration < 5 || $duration > 1440)) $duration = null;

    $reminder = array_key_exists('default_reminder_minutes', $cal) ? $cal['default_reminder_minutes'] : null;
    if ($reminder !== null && (!is_numeric($reminder) || (int)$reminder < 0)) {
        $reminder = $d['calendar']['default_reminder_minutes'];
    } elseif ($reminder !== null) {
        $reminder = (int)$reminder;
    }

    $catId = (isset($cal['default_category_id']) && is_string($cal['default_category_id']) && $cal['default_category_id'] !== '')
        ? $cal['default_category_id'] : null;

    return [
        'language' => pickEnum($raw['language'] ?? null, ['de', 'en', 'sk'], $d['language']),
        'whisper_language' => pickEnum($raw['whisper_language'] ?? null, ['de', 'de-CH', 'en', 'fr', 'it', 'auto'], $d['whisper_language']),
        'theme' => pickEnum($raw['theme'] ?? null, ['light', 'dark', 'system'], $d['theme']),
        'density' => pickEnum($raw['density'] ?? null, ['comfortable', 'compact'], $d['density']),
        'start_page' => pickEnum($raw['start_page'] ?? null, ['dashboard', 'calendar', 'time', 'contacts'], $d['start_page']),
        'timezone' => pickTimezone($raw['timezone'] ?? null, $d['timezone']),
        'calendar' => [
            'default_view' => pickEnum($cal['default_view'] ?? null, ['month', 'week', 'day'], $d['calendar']['default_view']),
            'week_start' => pickInt($cal['week_start'] ?? null, [0, 1], $d['calendar']['week_start']),
            'show_week_numbers' => pickBool($cal['show_week_numbers'] ?? null, $d['calendar']['show_week_numbers']),
            'show_weekends' => pickBool($cal['show_weekends'] ?? null, $d['calendar']['show_weekends']),
            'workday_start' => pickTime($cal['workday_start'] ?? null, $d['calendar']['workday_start']),
            'workday_end' => pickTime($cal['workday_end'] ?? null, $d['calendar']['workday_end']),
            'slot_minutes' => pickInt($cal['slot_minutes'] ?? null, [15, 30, 60], $d['calendar']['slot_minutes']),
            'default_duration_minutes' => $duration ?? $d['calendar']['default_duration_minutes'],
            'default_reminder_minutes' => $reminder,
            'default_category_id' => $catId,
            'default_visibility' => pickEnum($cal['default_visibility'] ?? null, ['private', 'company'], $d['calendar']['default_visibility']),
            'show_tasks' => pickBool($cal['show_tasks'] ?? null, $d['calendar']['show_tasks']),
            'show_declined' => pickBool($cal['show_declined'] ?? null, $d['calendar']['show_declined']),
            'time_format' => pickEnum($cal['time_format'] ?? null, ['24h', '12h'], $d['calendar']['time_format']),
        ],
        'notifications' => [
            'browser' => pickBool($notif['browser'] ?? null, $d['notifications']['browser']),
            'email' => pickBool($notif['email'] ?? null, $d['notifications']['email']),
            'in_app' => pickBool($notif['in_app'] ?? null, $d['notifications']['in_app']),
            'sound' => pickBool($notif['sound'] ?? null, $d['notifications']['sound']),
            'digest' => pickEnum($notif['digest'] ?? null, ['off', 'daily', 'weekly'], $d['notifications']['digest']),
            'events' => [
                'calendar_invite' => pickBool($ev['calendar_invite'] ?? null, $d['notifications']['events']['calendar_invite']),
                'calendar_change' => pickBool($ev['calendar_change'] ?? null, $d['notifications']['events']['calendar_change']),
                'calendar_cancel' => pickBool($ev['calendar_cancel'] ?? null, $d['notifications']['events']['calendar_cancel']),
                'calendar_reminder' => pickBool($ev['calendar_reminder'] ?? null, $d['notifications']['events']['calendar_reminder']),
                'task_assigned' => pickBool($ev['task_assigned'] ?? null, $d['notifications']['events']['task_assigned']),
                'task_due' => pickBool($ev['task_due'] ?? null, $d['notifications']['events']['task_due']),
                'task_comment' => pickBool($ev['task_comment'] ?? null, $d['notifications']['events']['task_comment']),
                'mention' => pickBool($ev['mention'] ?? null, $d['notifications']['events']['mention']),
                'budget_warning' => pickBool($ev['budget_warning'] ?? null, $d['notifications']['events']['budget_warning']),
            ],
        ],
    ];
}

function getAuthUser() {    global $jwtSecret;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        return null;
    }
    $decoded = jwtDecode($matches[1], $jwtSecret);
    if (!$decoded || empty($decoded['id'])) return null;
    $db = getDb();
    // admin_permissions MUSS mitgeladen werden, sonst kann checkAdminPermission()
    // Plattform-Admins (ohne is_superadmin) nie autorisieren.
    $stmt = $db->prepare("SELECT id, name, email, company_id, company_role, is_superadmin, is_pro, admin_permissions, settings FROM users WHERE id = ?");
    $stmt->execute([$decoded['id']]);
    return $stmt->fetch() ?: null;
}

function requireAuth() {
    $user = getAuthUser();
    if (!$user) {
        errorResponse('Unauthorized - Bitte anmelden', 401);
    }
    return $user;
}

function requireSuperadmin() {
    $user = requireAuth();
    if (empty($user['is_superadmin'])) {
        errorResponse('Nur Plattform-Superadmins haben Zugriff', 403);
    }
    return $user;
}

function checkAdminPermission($user, $permission) {
    if (!empty($user['is_superadmin'])) return true;
    // Platform-Admin-Rechte müssen explizit in users.admin_permissions vorliegen (Company Admins allein haben keinen Plattform-Zugriff)
    $perms = !empty($user['admin_permissions']) ? (is_string($user['admin_permissions']) ? json_decode($user['admin_permissions'], true) : $user['admin_permissions']) : [];
    if (!is_array($perms) || empty($perms)) return false;
    if ($permission === 'any_admin') return true;
    return in_array($permission, $perms) || in_array('all', $perms);
}

function requireAdminPermission($permission) {
    $user = requireAuth();
    if (!checkAdminPermission($user, $permission)) {
        errorResponse('Keine Berechtigung für diese Administrator-Aktion (' . $permission . ')', 403);
    }
    return $user;
}

function requireCompanyAdmin() {
    $user = requireAuth();
    if (empty($user['is_superadmin'])) {
        if (empty($user['company_id']) || ($user['company_role'] ?? '') !== 'admin') {
            errorResponse('Nur Firmen-Administratoren haben Zugriff auf diesen Bereich', 403);
        }
    }
    return $user;
}

function createNotification($userId, $type, $title, $message, $refType = null, $refId = null, $projectId = null) {

    if (!$userId) return;
    try {
        $db = getDb();
        $id = 'notif_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $stmt = $db->prepare("
            INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, project_id, is_read, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([$id, $userId, $type, $title, $message, $refType, $refId, $projectId]);
    } catch (Exception $e) {
        // Notification creation should not block primary action
    }
}

function evaluateProjectAccess($user, $projectId, $action = 'read') {
    $db = getDb();
    $stmt = $db->prepare("SELECT p.id, p.folder_id, p.visibility as project_visibility, pf.owner_id, pf.company_id, pf.visibility as folder_visibility FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.id = ?");
    $stmt->execute([$projectId]);
    $prj = $stmt->fetch();
    if (!$prj) errorResponse('Projekt nicht gefunden', 404);

    // Stufe 1: Company Policy Check
    if (!empty($prj['company_id'])) {
        $cStmt = $db->prepare("SELECT settings FROM companies WHERE id = ?");
        $cStmt->execute([$prj['company_id']]);
        $comp = $cStmt->fetch();
        if ($comp && !empty($comp['settings'])) {
            $settings = is_string($comp['settings']) ? json_decode($comp['settings'], true) : $comp['settings'];
            if (!empty($settings['access_restricted']) && empty($user['is_pro']) && empty($user['company_id'])) {
                errorResponse('Unternehmensrichtlinie untersagt Zugriff', 403);
            }
        }
    }

    // Stufe 2: Project Membership & Visibility Check
    // WICHTIG: Kein automatischer Company-Admin-Bypass. Projekte sind privat, ausser geteilt oder Owner/Member.
    $role = null;
    if ($prj['owner_id'] === $user['id']) {
        $role = 'owner';
    } else {
        $mStmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $mStmt->execute([$projectId, $user['id']]);
        $m = $mStmt->fetch();
        if ($m) {
            $role = $m['role'];
        } else {
            // Check folder_members (inherited folder access)
            $fmStmt = $db->prepare("SELECT role FROM folder_members WHERE folder_id = ? AND user_id = ?");
            $fmStmt->execute([$prj['folder_id'], $user['id']]);
            $fm = $fmStmt->fetch();
            if ($fm) {
                $role = $fm['role'];
            } elseif (
                !empty($user['company_id']) && $user['company_id'] === $prj['company_id'] &&
                (($prj['project_visibility'] ?? 'private') === 'company' || ($prj['folder_visibility'] ?? 'private') === 'company')
            ) {
                $role = 'editor';
            }
        }
    }

    if (!$role) {
        errorResponse('Projekt nicht gefunden', 404);
    }

    // Stufe 4: Role Action Check
    if ($action === 'write' && $role === 'viewer') {
        errorResponse('Viewer besitzen nur Leseberechtigung. Schreibzugriff verweigert.', 403);
    }

    return [
        'projectId' => $prj['id'],
        'folderId' => $prj['folder_id'],
        'ownerId' => $prj['owner_id'],
        'companyId' => $prj['company_id'],
        'userRole' => $role
    ];
}

function evaluateListAccess($user, $listId, $action = 'read') {
    $db = getDb();
    $stmt = $db->prepare("SELECT l.*, p.folder_id FROM lists l JOIN projects p ON p.id = l.project_id WHERE l.id = ?");
    $stmt->execute([$listId]);
    $list = $stmt->fetch();
    if (!$list) errorResponse('Liste nicht gefunden', 404);

    $projectContext = evaluateProjectAccess($user, $list['project_id'], $action);

    if ($list['access_mode'] === 'custom') {
        if ($projectContext['userRole'] !== 'owner' && $projectContext['userRole'] !== 'admin') {
            $aStmt = $db->prepare("SELECT is_visible FROM list_access WHERE list_id = ? AND user_id = ?");
            $aStmt->execute([$listId, $user['id']]);
            $acc = $aStmt->fetch();
            if (!$acc || $acc['is_visible'] != 1) {
                errorResponse('Liste nicht gefunden', 404);
            }
        }
    }
    return ['list' => $list, 'projectContext' => $projectContext];
}

function deleteProjectCascade($db, $projectId) {
    // 1. All lists in this project
    $lStmt = $db->prepare("SELECT id FROM lists WHERE project_id = ?");
    $lStmt->execute([$projectId]);
    $listIds = $lStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($listIds)) {
        $inLists = implode(',', array_fill(0, count($listIds), '?'));
        // Find tasks in these lists
        $tStmt = $db->prepare("SELECT id FROM tasks WHERE list_id IN ($inLists)");
        $tStmt->execute($listIds);
        $taskIds = $tStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($taskIds)) {
            $inTasks = implode(',', array_fill(0, count($taskIds), '?'));
            try { $db->prepare("DELETE FROM task_comments WHERE task_id IN ($inTasks)")->execute($taskIds); } catch (Exception $e) {}
            try { $db->prepare("DELETE FROM task_subtasks WHERE task_id IN ($inTasks)")->execute($taskIds); } catch (Exception $e) {}
            try { $db->prepare("DELETE FROM daily_todos WHERE task_id IN ($inTasks)")->execute($taskIds); } catch (Exception $e) {}
        }

        try { $db->prepare("DELETE FROM tasks WHERE list_id IN ($inLists)")->execute($listIds); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM list_access WHERE list_id IN ($inLists)")->execute($listIds); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM lists WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    }

    try { $db->prepare("DELETE FROM time_entries WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM project_journals WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM project_documents WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM project_group_access WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    try { $db->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    try { $db->prepare("UPDATE calendar_events SET project_id = NULL WHERE project_id = ?")->execute([$projectId]); } catch (Exception $e) {}
    $db->prepare("DELETE FROM projects WHERE id = ?")->execute([$projectId]);
}

function getUserPlanDetails($db, $user) {
    if (!empty($user['is_superadmin'])) {
        return [
            'plan' => 'enterprise',
            'license_type' => 'enterprise',
            'is_trial' => false,
            'trial_days_left' => 0,
            'max_folders' => PHP_INT_MAX,
            'max_projects' => PHP_INT_MAX,
            'max_tasks_per_project' => PHP_INT_MAX,
            'custom_fields' => true,
            'time_tracking' => true,
            'section_automation' => true,
            'export' => true
        ];
    }

    if (!empty($user['company_id'])) {
        $cmStmt = $db->prepare("SELECT * FROM company_memberships WHERE user_id = ? AND company_id = ?");
        $cmStmt->execute([$user['id'], $user['company_id']]);
        $cm = $cmStmt->fetch(PDO::FETCH_ASSOC);

        $role = $user['company_role'] ?? ($cm['role'] ?? 'member');
        if ($role === 'admin') {
            $lic = 'enterprise';
            if (!$cm) {
                try {
                    $cmId = 'cm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO company_memberships (id, company_id, user_id, role, license_type, status) VALUES (?, ?, ?, 'admin', 'enterprise', 'active')")
                       ->execute([$cmId, $user['company_id'], $user['id']]);
                } catch (Exception $e) {}
            }
        } else {
            $lic = $cm['license_type'] ?? 'pro';
        }

        $plan = ($lic === 'enterprise') ? 'enterprise' : 'pro';
        return [
            'plan' => $plan,
            'license_type' => $lic,
            'is_trial' => false,
            'trial_days_left' => 0,
            'max_folders' => PHP_INT_MAX,
            'max_projects' => ($plan === 'enterprise') ? PHP_INT_MAX : 30,
            'max_tasks_per_project' => PHP_INT_MAX,
            'custom_fields' => true,
            'time_tracking' => true,
            'section_automation' => true,
            'export' => ($plan === 'enterprise')
        ];
    }

    $isTrial = false;
    $daysLeft = 0;
    $trialEndsAt = $user['trial_ends_at'] ?? null;
    $isPro = !empty($user['is_pro']);

    try {
        $uStmt = $db->prepare("SELECT trial_ends_at, is_pro FROM users WHERE id = ?");
        $uStmt->execute([$user['id']]);
        $uRow = $uStmt->fetch(PDO::FETCH_ASSOC);
        if ($uRow) {
            if (array_key_exists('trial_ends_at', $uRow)) $trialEndsAt = $uRow['trial_ends_at'];
            if (isset($uRow['is_pro'])) $isPro = !empty($uRow['is_pro']);
        }
    } catch (Exception $e) {}

    if (!empty($trialEndsAt)) {
        $now = time();
        $trialEnd = strtotime($trialEndsAt);
        if ($trialEnd > $now) {
            $isTrial = true;
            $daysLeft = (int)ceil(($trialEnd - $now) / 86400);
        }
    }

    if ($isTrial || $isPro) {
        return [
            'plan' => 'pro',
            'license_type' => 'pro',
            'is_trial' => $isTrial,
            'trial_days_left' => $daysLeft,
            'max_folders' => PHP_INT_MAX,
            'max_projects' => 30,
            'max_tasks_per_project' => PHP_INT_MAX,
            'custom_fields' => true,
            'time_tracking' => true,
            'section_automation' => true,
            'export' => false
        ];
    }

    return [
        'plan' => 'basic',
        'license_type' => 'basic',
        'is_trial' => false,
        'trial_days_left' => 0,
        'max_folders' => 1,
        'max_projects' => 3,
        'max_tasks_per_project' => 30,
        'custom_fields' => false,
        'time_tracking' => false,
        'section_automation' => false,
        'export' => false
    ];
}

function getOrCreateDefaultFolder($db, $user) {
    $ownerId = $user['id'];
    $companyId = $user['company_id'] ?? null;
    $stmt = $db->prepare("SELECT * FROM project_folders WHERE owner_id = ? ORDER BY created_at ASC LIMIT 1");
    $stmt->execute([$ownerId]);
    $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$folder) {
        $fldId = 'fld_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility) VALUES (?, ?, ?, 'Allgemein', '📁', 'private')")
           ->execute([$fldId, $ownerId, $companyId]);
        $stmt->execute([$ownerId]);
        $folder = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    return $folder;
}

// ROUTER
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#^.*?/api/?#', '', $uri);
$path = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];
$body = getJsonBody();

try {
    $db = getDb();
    // 1. POST auth/login
    if ($path === 'auth/login' && $method === 'POST') {
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $invitationToken = trim($body['invitation_token'] ?? '');
        if (!$email || !$password) errorResponse('E-Mail und Passwort erforderlich', 400);

        $stmt = $db->prepare("
            SELECT u.*, c.name as company_name, c.subscription_plan as company_plan
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE LOWER(u.email) = LOWER(?)
        ");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($password, $u['password_hash'])) {
            errorResponse('Ungültige Zugangsdaten', 401);
        }

        // If logging in via an invitation link, process auto-join
        if ($invitationToken) {
            $invStmt = $db->prepare("SELECT * FROM company_invitations WHERE token = ? AND status = 'pending'");
            $invStmt->execute([$invitationToken]);
            $inv = $invStmt->fetch();
            if ($inv) {
                $db->prepare("UPDATE users SET company_id = ?, company_role = ?, is_pro = 1 WHERE id = ?")->execute([
                    $inv['company_id'], $inv['role'], $u['id']
                ]);
                $db->prepare("UPDATE company_invitations SET status = 'accepted' WHERE id = ?")->execute([$inv['id']]);

                // Create or update company membership
                try {
                    $cmId = 'cm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $lic = !empty($inv['license_type']) ? $inv['license_type'] : ($inv['role'] === 'admin' ? 'enterprise' : 'pro');
                    $db->prepare("
                        INSERT INTO company_memberships (id, company_id, user_id, role, license_type, status)
                        VALUES (?, ?, ?, ?, ?, 'active')
                        ON DUPLICATE KEY UPDATE role = VALUES(role), license_type = VALUES(license_type), status = 'active'
                    ")->execute([$cmId, $inv['company_id'], $u['id'], $inv['role'], $lic]);
                } catch (Exception $e) {}

                // Re-fetch user
                $stmt->execute([$email]);
                $u = $stmt->fetch();
            }
        }

        $perms = !empty($u['admin_permissions']) ? (is_string($u['admin_permissions']) ? json_decode($u['admin_permissions'], true) : $u['admin_permissions']) : [];
        if (!is_array($perms)) $perms = [];
        // Superadmin bekommt automatisch alle Plattform-Rechte.
        // Company Admins benutzen company_role === 'admin' fuer /company Portal.
        // Sie bekommen KEINE admin_permissions - sonst landen sie im Plattform-Admin!
        if (!empty($u['is_superadmin'])) {
            $perms = ['manage_users', 'finance', 'company_settings', 'manage_templates', 'audit_logs', 'all'];
        }

        $planDetails = getUserPlanDetails($db, $u);

        $token = jwtEncode([
            'id' => $u['id'],
            'email' => $u['email'],
            'name' => $u['name'],
            'company_id' => $u['company_id'],
            'company_role' => $u['company_role'],
            'is_superadmin' => (int)$u['is_superadmin'],
            'is_pro' => (int)$u['is_pro'],
            'admin_permissions' => $perms,
            'plan' => $planDetails['plan'],
            'license_type' => $planDetails['license_type']
        ], $jwtSecret);

        jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'company_id' => $u['company_id'],
                'company_role' => $u['company_role'],
                'company_name' => $u['company_name'],
                'company_plan' => $u['company_plan'],
                'is_superadmin' => (bool)$u['is_superadmin'],
                'is_pro' => (bool)$u['is_pro'],
                'settings' => normalizeUserSettings($u['settings'] ?? null),
                'avatar' => $u['avatar'] ?? null,
                'admin_permissions' => $perms,
                'plan' => $planDetails['plan'],
                'license_type' => $planDetails['license_type'],
                'is_trial' => $planDetails['is_trial'],
                'trial_days_left' => $planDetails['trial_days_left'],
                'trial_ends_at' => $u['trial_ends_at'] ?? null
            ]
        ]);
    }

    // 2. POST auth/register
    if ($path === 'auth/register' && $method === 'POST') {
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $invitationToken = trim($body['invitation_token'] ?? '');

        if (!$name || !$email || !$password) errorResponse('Pflichtfelder fehlen', 400);

        $stmt = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) errorResponse('E-Mail bereits registriert', 400);

        $userId = 'usr_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $pwHash = password_hash($password, PASSWORD_BCRYPT);
        $companyId = null;
        $companyRole = null;
        $isPro = 1; // 14-Tage Pro Trial für jeden neuen Nutzer
        $trialEndsAt = date('Y-m-d H:i:s', strtotime('+14 days'));
        $invLicenseType = 'pro';

        // Check invitation token
        if ($invitationToken) {
            $invStmt = $db->prepare("SELECT * FROM company_invitations WHERE token = ? AND status = 'pending'");
            $invStmt->execute([$invitationToken]);
            $inv = $invStmt->fetch();
            if ($inv) {
                $companyId = $inv['company_id'];
                $companyRole = $inv['role'];
                $invLicenseType = !empty($inv['license_type']) ? $inv['license_type'] : ($inv['role'] === 'admin' ? 'enterprise' : 'pro');
                $isPro = 1;
                $trialEndsAt = null; // Company members have company plan
                $db->prepare("UPDATE company_invitations SET status = 'accepted' WHERE id = ?")->execute([$inv['id']]);
            }
        }

        $uStmt = $db->prepare("INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, trial_ends_at, name, email, password_hash) VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?)");
        $uStmt->execute([$userId, $companyId, $companyRole, $isPro, $trialEndsAt, $name, $email, $pwHash]);

        if ($companyId) {
            try {
                $cmId = 'cm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $db->prepare("INSERT INTO company_memberships (id, company_id, user_id, role, license_type, status) VALUES (?, ?, ?, ?, ?, 'active')")
                   ->execute([$cmId, $companyId, $userId, $companyRole, $invLicenseType]);
            } catch (Exception $e) {}
        }

        // Default folder & project only if not joining an existing company
        if (!$companyId) {
            $fldId = 'fld_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $fldName = "{$name}s Projekte";
            $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name) VALUES (?, ?, NULL, ?)")->execute([$fldId, $userId, $fldName]);

            $prjId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $db->prepare("INSERT INTO projects (id, folder_id, title, status) VALUES (?, ?, 'Erstes Projekt', 'active')")->execute([$prjId, $fldId]);

            $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, 'Zu erledigen', 'inherit', 1)")->execute([$lstId, $prjId]);
        }

        // Fetch company name if joined
        $compName = null;
        $compPlan = null;
        if ($companyId) {
            $cStmt = $db->prepare("SELECT name, subscription_plan FROM companies WHERE id = ?");
            $cStmt->execute([$companyId]);
            $cRow = $cStmt->fetch();
            if ($cRow) {
                $compName = $cRow['name'];
                $compPlan = $cRow['subscription_plan'];
            }
        }

        $regUser = [
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'company_id' => $companyId,
            'company_role' => $companyRole,
            'is_superadmin' => 0,
            'is_pro' => $isPro,
            'trial_ends_at' => $trialEndsAt
        ];
        $planDetails = getUserPlanDetails($db, $regUser);

        $token = jwtEncode([
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'company_id' => $companyId,
            'company_role' => $companyRole,
            'is_superadmin' => 0,
            'is_pro' => $isPro,
            'plan' => $planDetails['plan'],
            'license_type' => $planDetails['license_type']
        ], $jwtSecret);

        jsonResponse([
            'token' => $token,
            'user' => [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'company_id' => $companyId,
                'company_role' => $companyRole,
                'company_name' => $compName,
                'company_plan' => $compPlan,
                'is_superadmin' => false,
                'is_pro' => (bool)$isPro,
                'avatar' => null,
                'plan' => $planDetails['plan'],
                'license_type' => $planDetails['license_type'],
                'is_trial' => $planDetails['is_trial'],
                'trial_days_left' => $planDetails['trial_days_left'],
                'trial_ends_at' => $trialEndsAt
            ]
        ]);
    }

    // 3. GET auth/me
    if ($path === 'auth/me' && $method === 'GET') {
        $authUser = requireAuth();
        $stmt = $db->prepare("
            SELECT u.*, c.name as company_name, c.subscription_plan as company_plan, c.settings as company_settings
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.id = ?
        ");
        $stmt->execute([$authUser['id']]);
        $u = $stmt->fetch();
        if (!$u) errorResponse('Benutzer nicht gefunden', 404);

        $settings = !empty($u['company_settings']) ? (is_string($u['company_settings']) ? json_decode($u['company_settings'], true) : $u['company_settings']) : [];

        $perms = !empty($u['admin_permissions']) ? (is_string($u['admin_permissions']) ? json_decode($u['admin_permissions'], true) : $u['admin_permissions']) : [];
        if (!is_array($perms)) $perms = [];
        // Nur Superadmin bekommt Plattform-Permissions. Company Admins nutzen company_role.
        if (!empty($u['is_superadmin'])) {
            $perms = ['manage_users', 'finance', 'company_settings', 'manage_templates', 'audit_logs', 'all'];
        }

        $planDetails = getUserPlanDetails($db, $u);

        jsonResponse([
            'user' => [
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'company_id' => $u['company_id'],
                'company_role' => $u['company_role'],
                'company_name' => $u['company_name'],
                'company_plan' => $u['company_plan'],
                'company_settings' => $settings,
                'is_superadmin' => (bool)$u['is_superadmin'],
                'is_pro' => (bool)$u['is_pro'],
                'hourly_rate' => $u['hourly_rate'] !== null ? floatval($u['hourly_rate']) : null,
                'currency' => $u['currency'] ?? 'CHF',
                'settings' => normalizeUserSettings($u['settings'] ?? null),
                'avatar' => $u['avatar'] ?? null,
                'admin_permissions' => $perms,
                'plan' => $planDetails['plan'],
                'license_type' => $planDetails['license_type'],
                'is_trial' => $planDetails['is_trial'],
                'trial_days_left' => $planDetails['trial_days_left'],
                'trial_ends_at' => $u['trial_ends_at'] ?? null
            ]
        ]);
    }

    // 3b. PATCH auth/profile (Update user settings: name, password, hourly_rate, currency)
    if ($path === 'auth/profile' && ($method === 'PATCH' || $method === 'PUT')) {
        $authUser = requireAuth();
        $name = trim($body['name'] ?? '');
        $currentPassword = $body['current_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';
        $hourlyRate = array_key_exists('hourly_rate', $body) ? ($body['hourly_rate'] !== null ? floatval($body['hourly_rate']) : null) : null;
        $currency = isset($body['currency']) ? trim($body['currency']) : null;
        $hasSettings = array_key_exists('settings', $body);

        if (!$name) errorResponse('Name erforderlich', 400);

        // Fetch current user hash
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$authUser['id']]);
        $row = $stmt->fetch();

        if ($newPassword) {
            if (!$currentPassword || !password_verify($currentPassword, $row['password_hash'])) {
                errorResponse('Das aktuelle Passwort ist nicht korrekt', 400);
            }
            if (strlen($newPassword) < 8) {
                errorResponse('Das neue Passwort muss mindestens 8 Zeichen lang sein', 400);
            }
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $db->prepare("UPDATE users SET name = ?, password_hash = ?, hourly_rate = COALESCE(?, hourly_rate), currency = COALESCE(?, currency) WHERE id = ?")->execute([$name, $newHash, $hourlyRate, $currency, $authUser['id']]);
        } else {
            $db->prepare("UPDATE users SET name = ?, hourly_rate = COALESCE(?, hourly_rate), currency = COALESCE(?, currency) WHERE id = ?")->execute([$name, $hourlyRate, $currency, $authUser['id']]);
        }

        // Persoenliche Einstellungen: immer vollstaendig normalisiert speichern.
        if ($hasSettings) {
            $normalized = normalizeUserSettings($body['settings']);
            $db->prepare("UPDATE users SET settings = ? WHERE id = ?")->execute([json_encode($normalized), $authUser['id']]);
        }

        // Profilbild (Avatar)
        if (array_key_exists('avatar', $body)) {
            $avatarVal = !empty($body['avatar']) ? (string)$body['avatar'] : null;
            $db->prepare("UPDATE users SET avatar = ? WHERE id = ?")->execute([$avatarVal, $authUser['id']]);
        }

        // Return updated user
        $uStmt = $db->prepare("
            SELECT u.*, c.name as company_name, c.subscription_plan as company_plan
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            WHERE u.id = ?
        ");
        $uStmt->execute([$authUser['id']]);
        $u = $uStmt->fetch();

        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $u['id'],
                'name' => $u['name'],
                'email' => $u['email'],
                'company_id' => $u['company_id'],
                'company_role' => $u['company_role'],
                'company_name' => $u['company_name'],
                'company_plan' => $u['company_plan'],
                'is_superadmin' => (bool)$u['is_superadmin'],
                'is_pro' => (bool)$u['is_pro'],
                'hourly_rate' => $u['hourly_rate'] !== null ? floatval($u['hourly_rate']) : null,
                'currency' => $u['currency'] ?? 'CHF',
                'settings' => normalizeUserSettings($u['settings'] ?? null),
                'avatar' => $u['avatar'] ?? null
            ]
        ]);
    }

    // 3c. GET gdpr/export (Art. 20 DSGVO - Datenübertragbarkeit)
    if ($path === 'gdpr/export' && $method === 'GET') {
        $authUser = requireAuth();
        $uStmt = $db->prepare("SELECT id, name, email, company_id, company_role, is_superadmin, is_pro, hourly_rate, currency, settings, created_at FROM users WHERE id = ?");
        $uStmt->execute([$authUser['id']]);
        $u = $uStmt->fetch();

        $comp = null;
        if (!empty($u['company_id'])) {
            $cStmt = $db->prepare("SELECT id, name, subscription_plan, created_at FROM companies WHERE id = ?");
            $cStmt->execute([$u['company_id']]);
            $comp = $cStmt->fetch();
        }

        $fStmt = $db->prepare("SELECT id, name, icon, visibility, created_at FROM project_folders WHERE owner_id = ?");
        $fStmt->execute([$authUser['id']]);
        $ownedFolders = $fStmt->fetchAll();

        $fmStmt = $db->prepare("SELECT fm.folder_id, fm.role, fm.created_at, pf.name as folder_name FROM folder_members fm JOIN project_folders pf ON pf.id = fm.folder_id WHERE fm.user_id = ?");
        $fmStmt->execute([$authUser['id']]);
        $folderMemberships = $fmStmt->fetchAll();

        $gmStmt = $db->prepare("SELECT ug.id as group_id, ug.name as group_name, ug.color, ugm.created_at FROM user_group_members ugm JOIN user_groups ug ON ug.id = ugm.group_id WHERE ugm.user_id = ?");
        $gmStmt->execute([$authUser['id']]);
        $groups = $gmStmt->fetchAll();

        $pmStmt = $db->prepare("SELECT pm.project_id, pm.role, pm.created_at, p.title as project_title, p.status FROM project_members pm JOIN projects p ON p.id = pm.project_id WHERE pm.user_id = ?");
        $pmStmt->execute([$authUser['id']]);
        $projectMemberships = $pmStmt->fetchAll();

        $tStmt = $db->prepare("SELECT id, list_id, title, description, status, priority, due_date, created_at FROM tasks WHERE assigned_to = ?");
        $tStmt->execute([$authUser['id']]);
        $tasks = $tStmt->fetchAll();

        $dtStmt = $db->prepare("SELECT id, project_id, title, target_date, is_completed, completed_at, original_date, rollover_count, created_at FROM daily_todos WHERE user_id = ?");
        $dtStmt->execute([$authUser['id']]);
        $dailyTodos = $dtStmt->fetchAll();

        $teStmt = $db->prepare("SELECT id, project_id, task_id, duration_minutes, hourly_rate, currency, description, entry_date, is_manual, created_at FROM time_entries WHERE user_id = ?");
        $teStmt->execute([$authUser['id']]);
        $timeEntries = $teStmt->fetchAll();

        $cStmt = $db->prepare("SELECT id, first_name, last_name, company_name, role_function, phone, mobile, email, category_group, address, website, notes, share_scope, created_at FROM contacts WHERE user_id = ?");
        $cStmt->execute([$authUser['id']]);
        $contacts = $cStmt->fetchAll();

        $nStmt = $db->prepare("SELECT id, type, title, message, is_read, created_at FROM notifications WHERE user_id = ?");
        $nStmt->execute([$authUser['id']]);
        $notifications = $nStmt->fetchAll();

        header("Content-Disposition: attachment; filename=\"taskster_export_" . $authUser['id'] . "_" . time() . ".json\"");
        jsonResponse([
            'metadata' => [
                'exported_at' => date('c'),
                'format_version' => '1.0',
                'system' => 'Taskster GDPR Data Portability Service (Art. 20 DSGVO)',
                'data_subject_id' => $authUser['id'],
                'data_subject_email' => $authUser['email']
            ],
            'user_profile' => $u,
            'company' => $comp,
            'folders' => ['owned' => $ownedFolders, 'memberships' => $folderMemberships],
            'groups' => $groups,
            'projects' => $projectMemberships,
            'tasks' => $tasks,
            'daily_todos' => $dailyTodos,
            'time_entries' => $timeEntries,
            'contacts' => $contacts,
            'notifications' => $notifications
        ]);
    }

    // 3d. DELETE gdpr/account (Art. 17 DSGVO - Recht auf Vergessenwerden / Löschung)
    if ($path === 'gdpr/account' && $method === 'DELETE') {
        $authUser = requireAuth();
        $password = $body['password'] ?? '';
        if (empty($password)) {
            errorResponse('Bitte gib dein aktuelles Passwort ein, um die Löschung zu bestätigen.', 400);
        }

        $stmt = $db->prepare("SELECT password_hash, email FROM users WHERE id = ?");
        $stmt->execute([$authUser['id']]);
        $row = $stmt->fetch();
        if (!$row || !password_verify($password, $row['password_hash'])) {
            errorResponse('Das eingegebene Passwort ist nicht korrekt. Die Löschung wurde abgebrochen.', 400);
        }

        $db->prepare("DELETE FROM daily_todos WHERE user_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM contacts WHERE user_id = ? AND share_scope = 'private'")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM project_members WHERE user_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM folder_members WHERE user_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM user_group_members WHERE user_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM company_invitations WHERE LOWER(email) = LOWER(?)")->execute([$row['email']]);
        $db->prepare("UPDATE tasks SET assigned_to = NULL WHERE assigned_to = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM user_groups WHERE owner_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM project_folders WHERE owner_id = ?")->execute([$authUser['id']]);
        $db->prepare("DELETE FROM users WHERE id = ?")->execute([$authUser['id']]);

        jsonResponse([
            'success' => true,
            'message' => 'Dein Benutzerkonto und alle personenbezogenen Daten wurden erfolgreich und unwiderruflich gelöscht.'
        ]);
    }

    // 4. GET folders
    if ($path === 'folders' && $method === 'GET') {
        $user = requireAuth();
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';

        if (!empty($user['is_superadmin'])) {
            $stmt = $db->prepare("
                SELECT pf.*, u.name as owner_name, c.name as company_name,
                  (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
                FROM project_folders pf
                JOIN users u ON u.id = pf.owner_id
                LEFT JOIN companies c ON c.id = pf.company_id
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute();
        } else {
            // Strikte Privatsphäre: Der Nutzer sieht nur:
            // 1. Eigene Ordner (owner_id = user.id)
            // 2. Ordner mit direkter Mitgliedschaft (folder_members)
            // 3. Ordner, in denen er Projektmitglied ist (project_members)
            // 4. Ordner mit visibility = 'company' des eigenen Unternehmens
            $stmt = $db->prepare("
                SELECT pf.*, u.name as owner_name, c.name as company_name,
                  (
                    SELECT COUNT(*) FROM projects p
                    WHERE p.folder_id = pf.id AND (
                      pf.owner_id = ?
                      OR (pf.visibility = 'company' AND pf.company_id = ?)
                      OR (p.visibility = 'company' AND pf.company_id = ?)
                      OR pf.id IN (SELECT fm.folder_id FROM folder_members fm WHERE fm.user_id = ?)
                      OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                    )
                  ) as project_count
                FROM project_folders pf
                JOIN users u ON u.id = pf.owner_id
                LEFT JOIN companies c ON c.id = pf.company_id
                WHERE pf.owner_id = ?
                   OR (pf.visibility = 'company' AND pf.company_id = ?)
                   OR pf.id IN (SELECT fm.folder_id FROM folder_members fm WHERE fm.user_id = ?)
                   OR pf.id IN (
                       SELECT p.folder_id FROM projects p
                       JOIN project_members pm ON pm.project_id = p.id
                       WHERE pm.user_id = ?
                   )
                   OR pf.id IN (
                       SELECT p.folder_id FROM projects p
                       WHERE p.visibility = 'company' AND pf.company_id = ?
                   )
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute([
                $user['id'], $companyId, $companyId, $user['id'], $user['id'],
                $user['id'], $companyId, $user['id'], $user['id'], $companyId
            ]);
        }
        $folders = $stmt->fetchAll();
        $folders = array_map(function($f) {
            $f['visibility'] = $f['visibility'] ?? 'private';
            return $f;
        }, $folders);
        jsonResponse(['folders' => $folders]);
    }

    // 5. POST folders
    if ($path === 'folders' && $method === 'POST') {
        $user = requireAuth();
        $name = trim($body['name'] ?? '');
        if (!$name) errorResponse('Name erforderlich', 400);

        $planDetails = getUserPlanDetails($db, $user);
        if ($planDetails['plan'] === 'basic') {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM project_folders WHERE owner_id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if ((int)($row['count'] ?? 0) >= 1) {
                errorResponse('Limit erreicht: Im Free-Tarif ist maximal 1 Projektordner erlaubt. Bitte auf Pro upgraden.', 403);
            }
        }

        $fldId = 'fld_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $icon = trim($body['icon'] ?? '📁');
        $companyId = !empty($user['company_id']) ? $user['company_id'] : ($body['company_id'] ?? null);
        $visibility = (!empty($companyId) && ($body['visibility'] ?? '') === 'company') ? 'company' : 'private';

        $settings = !empty($body['settings']) ? (is_string($body['settings']) ? $body['settings'] : json_encode($body['settings'], JSON_UNESCAPED_UNICODE)) : '{}';

        $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility, settings) VALUES (?, ?, ?, ?, ?, ?, ?)")
           ->execute([$fldId, $user['id'], $companyId, $name, $icon, $visibility, $settings]);

        jsonResponse(['folder' => ['id' => $fldId, 'name' => $name, 'icon' => $icon, 'visibility' => $visibility, 'owner_id' => $user['id'], 'company_id' => $companyId, 'settings' => $settings]]);
    }

    // 5b. PUT folders/:id
    if (preg_match('#^folders/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if ($folder['owner_id'] !== $user['id']) {
            errorResponse('Nur der Eigentümer kann diesen Projektordner bearbeiten', 403);
        }

        $name = trim($body['name'] ?? $folder['name']);
        $icon = trim($body['icon'] ?? ($folder['icon'] ?? '📁'));
        $companyId = $folder['company_id'] ?: $user['company_id'];
        if (isset($body['company_id'])) {
            $companyId = $body['company_id'] ?: null;
        }

        $visibility = $folder['visibility'] ?? 'private';
        if (isset($body['visibility'])) {
            $visibility = (!empty($companyId) && $body['visibility'] === 'company') ? 'company' : 'private';
        }
        if (!$name) errorResponse('Name erforderlich', 400);

        $settings = $folder['settings'] ?? '{}';
        if (isset($body['settings'])) {
            $settings = is_string($body['settings']) ? $body['settings'] : json_encode($body['settings'], JSON_UNESCAPED_UNICODE);
        }

        $db->prepare("UPDATE project_folders SET name = ?, icon = ?, visibility = ?, company_id = ?, settings = ? WHERE id = ?")->execute([$name, $icon, $visibility, $companyId, $settings, $fldId]);

        if (!empty($body['default_project_id'])) {
            $defPrjId = trim($body['default_project_id']);
            $db->prepare("UPDATE projects SET is_default = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE folder_id = ?")->execute([$defPrjId, $fldId]);
        }

        $uStmt = $db->prepare("SELECT pf.*, u.name as owner_name, c.name as company_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id LEFT JOIN companies c ON c.id = pf.company_id WHERE pf.id = ?");
        $uStmt->execute([$fldId]);
        jsonResponse(['success' => true, 'folder' => $uStmt->fetch()]);
    }

    // 5c. DELETE folders/:id
    if (preg_match('#^folders/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if ($folder['owner_id'] !== $user['id'] && empty($user['is_superadmin'])) {
            errorResponse('Nur der Eigentümer kann diesen Projektordner löschen', 403);
        }

        $db->beginTransaction();
        try {
            $pStmt = $db->prepare("SELECT id FROM projects WHERE folder_id = ?");
            $pStmt->execute([$fldId]);
            $projectIds = $pStmt->fetchAll(PDO::FETCH_COLUMN);

            foreach ($projectIds as $pId) {
                deleteProjectCascade($db, $pId);
            }

            try { $db->prepare("DELETE FROM folder_members WHERE folder_id = ?")->execute([$fldId]); } catch (Exception $e) {}
            try { $db->prepare("DELETE FROM folder_field_definitions WHERE folder_id = ?")->execute([$fldId]); } catch (Exception $e) {}
            try { $db->prepare("DELETE FROM folder_group_access WHERE folder_id = ?")->execute([$fldId]); } catch (Exception $e) {}
            $db->prepare("DELETE FROM project_folders WHERE id = ?")->execute([$fldId]);

            $db->commit();
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            errorResponse('Fehler beim Löschen des Projektordners: ' . $e->getMessage(), 500);
        }
    }

    // 6. GET folders/:id
    if (preg_match('#^folders/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT pf.*, u.name as owner_name, c.name as company_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id LEFT JOIN companies c ON c.id = pf.company_id WHERE pf.id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        // Zero-Trust Zugriffsprüfung:
        $canAccessFolder = false;
        if ($folder['owner_id'] === $user['id']) {
            $canAccessFolder = true;
        } elseif (!empty($user['company_id']) && $user['company_id'] === $folder['company_id'] && ($folder['visibility'] ?? 'private') === 'company') {
            $canAccessFolder = true;
        } else {
            // Check direct folder_members
            $chkFm = $db->prepare("SELECT 1 FROM folder_members WHERE folder_id = ? AND user_id = ? LIMIT 1");
            $chkFm->execute([$fldId, $user['id']]);
            if ($chkFm->fetch()) {
                $canAccessFolder = true;
            } else {
                $chkStmt = $db->prepare("SELECT 1 FROM projects p JOIN project_members pm ON pm.project_id = p.id WHERE p.folder_id = ? AND pm.user_id = ? LIMIT 1");
                $chkStmt->execute([$fldId, $user['id']]);
                if ($chkStmt->fetch()) {
                    $canAccessFolder = true;
                }
            }
        }

        if (!$canAccessFolder) {
            errorResponse('Ordner nicht gefunden', 404);
        }

        $folder['visibility'] = $folder['visibility'] ?? 'private';

        $fStmt = $db->prepare("SELECT * FROM folder_field_definitions WHERE folder_id = ? ORDER BY sort_order ASC");
        $fStmt->execute([$fldId]);
        $fields = array_map(function($f) {
            $f['options'] = !empty($f['options']) ? (is_string($f['options']) ? json_decode($f['options'], true) : $f['options']) : [];
            $f['logic_rules'] = !empty($f['logic_rules']) ? (is_string($f['logic_rules']) ? json_decode($f['logic_rules'], true) : $f['logic_rules']) : [];
            $f['entity_type'] = $f['entity_type'] ?? 'task';
            return $f;
        }, $fStmt->fetchAll());

        // Projekte im Ordner filtern:
        // Ordner-Inhaber sieht alle Projekte des Ordners.
        // Andere Nutzer sehen nur Projekte, die auf company stehen (im selben Unternehmen) oder bei denen sie Mitglied sind.
        if ($folder['owner_id'] === $user['id']) {
            $pStmt = $db->prepare("
                SELECT p.*,
                  (SELECT COUNT(*) FROM lists l WHERE l.project_id = p.id) as list_count,
                  (SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id WHERE l.project_id = p.id) as task_count,
                  (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count
                FROM projects p
                WHERE p.folder_id = ?
                ORDER BY p.is_default DESC, p.created_at DESC
            ");
            $pStmt->execute([$fldId]);
        } else {
            $userCompany = !empty($user['company_id']) ? $user['company_id'] : '__none__';
            $folderVisibility = $folder['visibility'] ?? 'private';
            $pStmt = $db->prepare("
                SELECT p.*,
                  (SELECT COUNT(*) FROM lists l WHERE l.project_id = p.id) as list_count,
                  (SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id WHERE l.project_id = p.id) as task_count,
                  (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count
                FROM projects p
                WHERE p.folder_id = ? AND (
                    (? = 'company' AND ? = ?)
                    OR (p.visibility = 'company' AND ? = ?)
                    OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                    OR ? IN (SELECT fm.user_id FROM folder_members fm WHERE fm.folder_id = ?)
                )
                ORDER BY p.is_default DESC, p.created_at DESC
            ");
            $pStmt->execute([
                $fldId,
                $folderVisibility, $userCompany, $folder['company_id'],
                $userCompany, $folder['company_id'],
                $user['id'],
                $user['id'], $fldId
            ]);
        }
        $folderTotalMinutes = 0;
        $folderTotalBudgetHours = 0;
        $folderTotalBudgetAmount = 0;
        $rawProjects = $pStmt->fetchAll();

        $hasDefault = false;
        foreach ($rawProjects as $rp) {
            if (!empty($rp['is_default'])) {
                $hasDefault = true;
                break;
            }
        }
        if (!$hasDefault && count($rawProjects) > 0) {
            $rawProjects[0]['is_default'] = 1;
            $db->prepare("UPDATE projects SET is_default = 1 WHERE id = ?")->execute([$rawProjects[0]['id']]);
        }

        $projects = array_map(function($p) use ($db, &$folderTotalMinutes, &$folderTotalBudgetHours, &$folderTotalBudgetAmount) {
            $p['custom_data'] = !empty($p['custom_data']) ? (is_string($p['custom_data']) ? json_decode($p['custom_data'], true) : $p['custom_data']) : [];
            $p['visibility'] = $p['visibility'] ?? 'private';
            $p['is_default'] = (bool)($p['is_default'] ?? 0);
            $tHoursStmt = $db->prepare("SELECT SUM(duration_minutes) FROM time_entries WHERE project_id = ?");
            $tHoursStmt->execute([$p['id']]);
            $pMinutes = (int)($tHoursStmt->fetchColumn() ?: 0);
            $p['tracked_hours'] = round($pMinutes / 60, 2);
            $folderTotalMinutes += $pMinutes;
            if (!empty($p['budget_hours'])) $folderTotalBudgetHours += floatval($p['budget_hours']);
            if (!empty($p['budget_amount'])) $folderTotalBudgetAmount += floatval($p['budget_amount']);
            return $p;
        }, $rawProjects);

        $timeSummary = [
            'totalMinutes' => $folderTotalMinutes,
            'totalHours' => round($folderTotalMinutes / 60, 2),
            'totalBudgetHours' => $folderTotalBudgetHours,
            'totalBudgetAmount' => $folderTotalBudgetAmount
        ];

        $folder['settings'] = !empty($folder['settings']) ? (is_string($folder['settings']) ? json_decode($folder['settings'], true) : $folder['settings']) : [];
        if (!is_array($folder['settings'])) $folder['settings'] = [];

        jsonResponse(['folder' => $folder, 'fields' => $fields, 'projects' => $projects, 'timeSummary' => $timeSummary]);
    }

    // 6b. GET folders/:id/members
    if (preg_match('#^folders/([^/]+)/members$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT pf.*, c.name as company_name FROM project_folders pf LEFT JOIN companies c ON c.id = pf.company_id WHERE pf.id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        // Fetch owner
        $oStmt = $db->prepare("SELECT id as user_id, name, email, company_role FROM users WHERE id = ?");
        $oStmt->execute([$folder['owner_id']]);
        $owner = $oStmt->fetch();
        if ($owner) {
            $owner['role'] = 'owner';
        }

        // Fetch direct folder_members
        $fmStmt = $db->prepare("
            SELECT u.id as user_id, u.name, u.email, u.company_role, fm.role, fm.created_at
            FROM folder_members fm
            JOIN users u ON u.id = fm.user_id
            WHERE fm.folder_id = ?
            ORDER BY u.name ASC
        ");
        $fmStmt->execute([$fldId]);
        $directMembers = $fmStmt->fetchAll();

        // Company colleagues (for easy add / dropdown)
        $companyUsers = [];
        $compTarget = $folder['company_id'] ?: $user['company_id'];
        if ($compTarget) {
            $cStmt = $db->prepare("SELECT id as user_id, name, email, company_role FROM users WHERE company_id = ? ORDER BY name ASC");
            $cStmt->execute([$compTarget]);
            $companyUsers = $cStmt->fetchAll();
        }

        $allMembers = [];
        if ($owner) $allMembers[] = $owner;
        foreach ($directMembers as $dm) {
            if ($owner && $dm['user_id'] === $owner['user_id']) continue;
            $allMembers[] = $dm;
        }

        jsonResponse([
            'folder' => [
                'id' => $folder['id'],
                'name' => $folder['name'],
                'visibility' => $folder['visibility'] ?? 'private',
                'company_id' => $folder['company_id'],
                'company_name' => $folder['company_name']
            ],
            'members' => $allMembers,
            'companyUsers' => $companyUsers
        ]);
    }

    // 6c. POST folders/:id/members
    if (preg_match('#^folders/([^/]+)/members$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if ($folder['owner_id'] !== $user['id']) {
            errorResponse('Nur der Ordner-Eigentümer kann Mitglieder hinzufügen', 403);
        }

        $email = trim(strtolower($body['email'] ?? ''));
        $targetUserId = trim($body['user_id'] ?? '');
        $role = in_array($body['role'] ?? '', ['viewer', 'editor']) ? $body['role'] : 'editor';

        $targetUser = null;
        if ($targetUserId) {
            $uStmt = $db->prepare("SELECT id, name, email, company_id FROM users WHERE id = ?");
            $uStmt->execute([$targetUserId]);
            $targetUser = $uStmt->fetch();
        } elseif ($email) {
            $uStmt = $db->prepare("SELECT id, name, email, company_id FROM users WHERE LOWER(email) = ?");
            $uStmt->execute([$email]);
            $targetUser = $uStmt->fetch();
        }

        if (!$targetUser) {
            errorResponse('Benutzer mit dieser E-Mail nicht gefunden', 404);
        }

        $fmId = 'fm_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $ins = $db->prepare("
            INSERT INTO folder_members (id, folder_id, user_id, role)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role)
        ");
        $ins->execute([$fmId, $fldId, $targetUser['id'], $role]);

        if ($targetUser['id'] !== $user['id']) {
            createNotification($targetUser['id'], 'invitation', 'Neue Ordner-Einladung', "{$user['name']} hat dich zum Ordner \"{$folder['name']}\" eingeladen.", 'folder', $fldId, null);
        }

        jsonResponse([
            'success' => true,
            'member' => [
                'user_id' => $targetUser['id'],
                'name' => $targetUser['name'],
                'email' => $targetUser['email'],
                'role' => $role
            ]
        ]);
    }

    // 6d. DELETE folders/:id/members/:userId
    if (preg_match('#^folders/([^/]+)/members/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $fldId = $m[1];
        $targetUserId = $m[2];
        $stmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if ($folder['owner_id'] !== $user['id'] && $user['id'] !== $targetUserId) {
            errorResponse('Nur der Ordner-Eigentümer kann Mitglieder entfernen', 403);
        }

        $db->prepare("DELETE FROM folder_members WHERE folder_id = ? AND user_id = ?")->execute([$fldId, $targetUserId]);
        jsonResponse(['success' => true]);
    }

    // 7. POST folders/:id/fields
    if (preg_match('#^folders/([^/]+)/fields$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $planDetails = getUserPlanDetails($db, $user);
        if ($planDetails['plan'] === 'basic') {
            errorResponse('Benutzerdefinierte Zusatzfelder sind erst ab dem Pro-Tarif verfügbar.', 403);
        }
        $fldId = $m[1];
        $label = trim($body['label'] ?? '');
        if ($label === '') errorResponse('Feld-Bezeichnung erforderlich', 400);

        $rawKey = trim($body['field_key'] ?? $label);
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '_', $rawKey));
        $key = trim($key, '_');
        if ($key === '') $key = 'custom_field';

        // Check if field_key already exists
        $chkStmt = $db->prepare("SELECT id, field_key FROM folder_field_definitions WHERE folder_id = ? AND field_key = ?");
        $chkStmt->execute([$fldId, $key]);
        $existing = $chkStmt->fetch();
        if ($existing) {
            $logicRules = $body['logic_rules'] ?? null;
            $db->prepare("UPDATE folder_field_definitions SET logic_rules = ?, label = COALESCE(?, label) WHERE id = ?")->execute([
                $logicRules ? json_encode($logicRules) : null,
                $label,
                $existing['id']
            ]);
            jsonResponse(['success' => true, 'fieldId' => $existing['id'], 'fieldKey' => $existing['field_key']]);
        }

        $type = $body['field_type'] ?? 'text';
        $entityType = in_array($body['entity_type'] ?? '', ['project', 'task']) ? $body['entity_type'] : 'task';
        $options = $body['options'] ?? [];
        $logicRules = $body['logic_rules'] ?? null;

        $labelKey = !empty($body['label_key']) ? trim($body['label_key']) : null;
        $fieldId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $fieldId, $fldId, $key, $label, $labelKey, $type, $entityType, json_encode($options), $logicRules ? json_encode($logicRules) : null
        ]);

        jsonResponse(['success' => true, 'fieldId' => $fieldId, 'fieldKey' => $key]);
    }

    // 7b. DELETE folders/:id/fields/:fieldId
    if (preg_match('#^folders/([^/]+)/fields/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $fldId = $m[1];
        $fieldId = $m[2];

        $fStmt = $db->prepare("SELECT owner_id, company_id FROM project_folders WHERE id = ?");
        $fStmt->execute([$fldId]);
        $folder = $fStmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        $canEdit = ($folder['owner_id'] === $user['id']) || (!empty($user['company_id']) && $user['company_id'] === $folder['company_id'] && ($user['company_role'] ?? '') === 'admin');
        if (!$canEdit) errorResponse('Keine Berechtigung zum Löschen der Felddefinition', 403);

        $fldStmt = $db->prepare("SELECT field_key, entity_type FROM folder_field_definitions WHERE id = ? AND folder_id = ?");
        $fldStmt->execute([$fieldId, $fldId]);
        $existingField = $fldStmt->fetch();
        if (!$existingField) errorResponse('Feld nicht gefunden', 404);

        if ($existingField['entity_type'] === 'project') {
            $checkProjects = $db->prepare("
                SELECT COUNT(*) FROM projects p
                JOIN project_folders pf ON pf.id = p.folder_id
                WHERE pf.id = ? AND JSON_UNQUOTE(JSON_EXTRACT(p.custom_data, ?)) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(p.custom_data, ?)) != ''
            ");
            $checkProjects->execute([$fldId, '$.' . $existingField['field_key'], '$.' . $existingField['field_key']]);
            if ((int)$checkProjects->fetchColumn() > 0) {
                errorResponse('Feld kann nicht gelöscht werden, da es in aktiven Projekten verwendet wird.', 400);
            }
        } else {
            $checkTasks = $db->prepare("
                SELECT COUNT(*) FROM tasks t
                JOIN lists l ON l.id = t.list_id
                JOIN projects p ON p.id = l.project_id
                WHERE p.folder_id = ? AND JSON_UNQUOTE(JSON_EXTRACT(t.custom_data, ?)) IS NOT NULL AND JSON_UNQUOTE(JSON_EXTRACT(t.custom_data, ?)) != ''
            ");
            $checkTasks->execute([$fldId, '$.' . $existingField['field_key'], '$.' . $existingField['field_key']]);
            if ((int)$checkTasks->fetchColumn() > 0) {
                errorResponse('Feld kann nicht gelöscht werden, da es in aktiven Aufgaben verwendet wird.', 400);
            }
        }

        $db->prepare("DELETE FROM folder_field_definitions WHERE id = ? AND folder_id = ?")->execute([$fieldId, $fldId]);
        jsonResponse(['success' => true]);
    }

    // 7c. PUT folders/:id/fields/:fieldId
    if (preg_match('#^folders/([^/]+)/fields/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $fldId = $m[1];
        $fieldId = $m[2];

        $fStmt = $db->prepare("SELECT owner_id, company_id FROM project_folders WHERE id = ?");
        $fStmt->execute([$fldId]);
        $folder = $fStmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        $canEdit = ($folder['owner_id'] === $user['id']) || (!empty($user['company_id']) && $user['company_id'] === $folder['company_id'] && ($user['company_role'] ?? '') === 'admin');
        if (!$canEdit) errorResponse('Keine Berechtigung zum Bearbeiten der Felddefinitionen', 403);

        $fldStmt = $db->prepare("SELECT * FROM folder_field_definitions WHERE id = ? AND folder_id = ?");
        $fldStmt->execute([$fieldId, $fldId]);
        $existingField = $fldStmt->fetch();
        if (!$existingField) errorResponse('Feld nicht gefunden', 404);

        $label = isset($body['label']) ? trim($body['label']) : $existingField['label'];
        if ($label === '') errorResponse('Feld-Beschriftung darf nicht leer sein', 400);

        $labelKey = array_key_exists('label_key', $body) ? (!empty($body['label_key']) ? trim($body['label_key']) : null) : ($existingField['label_key'] ?? null);
        $fieldType = isset($body['field_type']) ? trim($body['field_type']) : $existingField['field_type'];
        $entityType = isset($body['entity_type']) ? trim($body['entity_type']) : ($existingField['entity_type'] ?? 'task');
        $isRequired = isset($body['is_required']) ? ($body['is_required'] ? 1 : 0) : ($existingField['is_required'] ?? 0);
        $options = isset($body['options']) ? (is_string($body['options']) ? $body['options'] : json_encode($body['options'])) : $existingField['options'];
        $logicRules = array_key_exists('logic_rules', $body) ? ($body['logic_rules'] ? (is_string($body['logic_rules']) ? $body['logic_rules'] : json_encode($body['logic_rules'])) : null) : $existingField['logic_rules'];

        $db->prepare("UPDATE folder_field_definitions SET label = ?, label_key = ?, field_type = ?, entity_type = ?, is_required = ?, options = ?, logic_rules = ? WHERE id = ? AND folder_id = ?")->execute([
            $label, $labelKey, $fieldType, $entityType, $isRequired, $options, $logicRules, $fieldId, $fldId
        ]);

        jsonResponse(['success' => true]);
    }

    // 8b. GET projects (List all accessible projects for dropdowns & time reporting)
    if ($path === 'projects' && $method === 'GET') {
        $user = requireAuth();
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';
        $stmt = $db->prepare("
            SELECT p.id, p.title, p.folder_id, p.currency, p.status, p.is_default,
                   pf.name as folder_name, pf.icon as folder_icon, pf.owner_id
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.owner_id = ?
               OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)
               OR pf.id IN (SELECT folder_id FROM folder_members WHERE user_id = ?)
               OR (p.visibility = 'company' AND pf.company_id = ?)
               OR (pf.visibility = 'company' AND pf.company_id = ?)
            GROUP BY p.id
            ORDER BY p.title ASC
        ");
        $stmt->execute([$user['id'], $user['id'], $user['id'], $companyId, $companyId]);
        $projects = $stmt->fetchAll();
        jsonResponse(['projects' => $projects]);
    }

    // 8. POST projects
    if ($path === 'projects' && $method === 'POST') {
        $user = requireAuth();
        $folderId = $body['folder_id'] ?? '';
        $title = trim($body['title'] ?? '');
        $customData = $body['custom_data'] ?? [];
        $templateId = $body['template_id'] ?? null;
        $customLists = $body['custom_lists'] ?? null;
        $importTasks = $body['import_tasks'] ?? null;
        $currency = !empty($body['currency']) ? trim($body['currency']) : 'CHF';
        $budgetHours = array_key_exists('budget_hours', $body) && $body['budget_hours'] !== null && $body['budget_hours'] !== '' ? floatval($body['budget_hours']) : 0.0;
        $budgetAmount = array_key_exists('budget_amount', $body) && $body['budget_amount'] !== null && $body['budget_amount'] !== '' ? floatval($body['budget_amount']) : 0.0;
        $visibility = (!empty($user['company_id']) && ($body['visibility'] ?? '') === 'company') ? 'company' : 'private';

        $planDetails = getUserPlanDetails($db, $user);

        // Ordner-Zuweisung mit automatischem Fallback: Wenn folder_id fehlt oder leer ist,
        // ermittle oder erstelle automatisch das Standard-Projektverzeichnis ("Allgemein").
        if (empty($folderId)) {
            $defFolder = getOrCreateDefaultFolder($db, $user);
            $folderId = $defFolder['id'];
        }

        // Folder access check
        $fCheckStmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $fCheckStmt->execute([$folderId]);
        $folder = $fCheckStmt->fetch();
        if (!$folder) {
            $defFolder = getOrCreateDefaultFolder($db, $user);
            $folderId = $defFolder['id'];
            $folder = $defFolder;
        }

        if ($folder['owner_id'] !== $user['id']) {
            if ($folder['visibility'] !== 'company' || empty($user['company_id']) || $user['company_id'] !== $folder['company_id']) {
                errorResponse('Ordner nicht gefunden', 404);
            }
        }

        // --- BATCH PROJECT IMPORT (z. B. aus CSV-/Excel-Import im Ordner) ---
        $batchProjects = $body['projects'] ?? null;

        // Limit-Prüfung Projekte
        $countStmt = $db->prepare("
            SELECT COUNT(*) as count FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            LEFT JOIN project_members pm ON pm.project_id = p.id
            WHERE (pf.owner_id = ? OR pm.user_id = ?) AND p.status = 'active'
        ");
        $countStmt->execute([$user['id'], $user['id']]);
        $currentProjectCount = (int)($countStmt->fetch()['count'] ?? 0);

        $incomingCount = (!empty($batchProjects) && is_array($batchProjects)) ? count($batchProjects) : 1;
        if ($planDetails['plan'] === 'basic') {
            if ($currentProjectCount + $incomingCount > 3) {
                errorResponse('Limit erreicht: Im Free-Tarif darfst du maximal in 3 Projekten gleichzeitig mitarbeiten. Bitte auf Pro upgraden oder einer Company beitreten.', 403);
            }
        } else if ($planDetails['plan'] === 'pro') {
            if ($currentProjectCount + $incomingCount > 30) {
                errorResponse('Limit erreicht: Im Pro-Tarif sind maximal 30 Projekte erlaubt. Bitte auf Enterprise upgraden.', 403);
            }
        }

        if (!empty($batchProjects) && is_array($batchProjects)) {
            // Custom Field Definitions anlegen, falls uebergeben
            $cfDefs = $body['custom_field_definitions'] ?? [];
            if (is_array($cfDefs) && count($cfDefs) > 0) {
                $existStmt = $db->prepare("SELECT field_key FROM folder_field_definitions WHERE folder_id = ?");
                $existStmt->execute([$folderId]);
                $existingKeys = $existStmt->fetchAll(PDO::FETCH_COLUMN);

                $cntStmt = $db->prepare("SELECT COUNT(*) as c FROM folder_field_definitions WHERE folder_id = ?");
                $cntStmt->execute([$folderId]);
                $sortOrder = (int)($cntStmt->fetch()['c'] ?? 0) + 1;

                $insDefStmt = $db->prepare("
                    INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules, is_required, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($cfDefs as $cfd) {
                    $rawKey = preg_replace('/[^a-z0-9_]/', '_', strtolower(trim($cfd['field_key'] ?? $cfd['label'] ?? '')));
                    $rawKey = trim($rawKey, '_');
                    if (!$rawKey || in_array($rawKey, $existingKeys)) continue;

                    $fId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $fLabel = trim($cfd['label'] ?? $rawKey);
                    $fLabelKey = $cfd['label_key'] ?? null;
                    $fType = $cfd['field_type'] ?? 'text';
                    $fEnt = $cfd['entity_type'] ?? 'project';
                    $fOpt = json_encode($cfd['options'] ?? []);
                    $fRules = json_encode($cfd['logic_rules'] ?? (object)[]);

                    $insDefStmt->execute([$fId, $folderId, $rawKey, $fLabel, $fLabelKey, $fType, $fEnt, $fOpt, $fRules, 0, $sortOrder++]);
                    $existingKeys[] = $rawKey;
                }
            }

            // Sections / Workflow-Listen ermitteln
            $sectionsToUse = [];
            if (!empty($body['sections']) && is_array($body['sections'])) {
                foreach ($body['sections'] as $sec) {
                    $sTitle = is_array($sec) ? trim($sec['title'] ?? '') : trim((string)$sec);
                    if ($sTitle === '') continue;
                    $isTarget = is_array($sec) ? (!empty($sec['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($sTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0);
                    $sectionsToUse[] = ['title' => $sTitle, 'is_completed_target' => $isTarget];
                }
                if (!empty($sectionsToUse)) {
                    $fSettings = !empty($folder['settings']) ? (is_string($folder['settings']) ? json_decode($folder['settings'], true) : $folder['settings']) : [];
                    if (!is_array($fSettings)) $fSettings = [];
                    $fSettings['default_sections'] = $sectionsToUse;
                    $db->prepare("UPDATE project_folders SET settings = ? WHERE id = ?")->execute([json_encode($fSettings, JSON_UNESCAPED_UNICODE), $folderId]);
                }
            } else {
                $fSettings = !empty($folder['settings']) ? (is_string($folder['settings']) ? json_decode($folder['settings'], true) : $folder['settings']) : [];
                if (is_array($fSettings) && !empty($fSettings['default_sections']) && is_array($fSettings['default_sections'])) {
                    $sectionsToUse = $fSettings['default_sections'];
                }
            }
            if (empty($sectionsToUse)) {
                $sectionsToUse = [
                    ['title' => 'Offen', 'is_completed_target' => 0],
                    ['title' => 'In Arbeit', 'is_completed_target' => 0],
                    ['title' => 'Abgeschlossen', 'is_completed_target' => 1]
                ];
            }

            $insPrjStmt = $db->prepare("
                INSERT INTO projects (id, folder_id, title, status, visibility, currency, budget_hours, budget_amount, custom_data, template_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'folder_workflow')
            ");
            $insPmStmt = $db->prepare("
                INSERT INTO project_members (id, project_id, user_id, role)
                VALUES (?, ?, ?, 'owner')
            ");
            $insLstStmt = $db->prepare("
                INSERT INTO lists (id, project_id, title, access_mode, sort_order, is_completed_target)
                VALUES (?, ?, ?, 'inherit', ?, ?)
            ");

            $created = [];
            foreach ($batchProjects as $p) {
                $pTitle = trim($p['title'] ?? '');
                if (!$pTitle) continue;

                $pId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $pStatus = (!empty($p['status']) && in_array($p['status'], ['active', 'archived', 'completed', 'on_hold'])) ? $p['status'] : 'active';
                $pVis = (!empty($user['company_id']) && ($p['visibility'] ?? '') === 'company') ? 'company' : 'private';
                $pCurr = !empty($p['currency']) ? trim($p['currency']) : 'CHF';
                $pBh = array_key_exists('budget_hours', $p) && $p['budget_hours'] !== null && $p['budget_hours'] !== '' ? floatval($p['budget_hours']) : 0.0;
                $pBa = array_key_exists('budget_amount', $p) && $p['budget_amount'] !== null && $p['budget_amount'] !== '' ? floatval($p['budget_amount']) : 0.0;
                $pCd = !empty($p['custom_data']) && is_array($p['custom_data']) ? json_encode($p['custom_data']) : '{}';

                $insPrjStmt->execute([$pId, $folderId, $pTitle, $pStatus, $pVis, $pCurr, $pBh, $pBa, $pCd]);
                $pmId = 'pm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $insPmStmt->execute([$pmId, $pId, $user['id']]);

                // Workflow-Listen anlegen
                $firstListId = null;
                $order = 1;
                foreach ($sectionsToUse as $sec) {
                    $sTitle = is_array($sec) ? trim($sec['title'] ?? '') : trim((string)$sec);
                    if ($sTitle === '') continue;
                    $isTarget = is_array($sec) ? (!empty($sec['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($sTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0);
                    $lId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    if (!$firstListId) $firstListId = $lId;
                    $insLstStmt->execute([$lId, $pId, $sTitle, $order++, $isTarget]);
                }
                if (!$firstListId) {
                    $firstListId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $insLstStmt->execute([$firstListId, $pId, 'Offen', 1, 0]);
                }

                // Dynamische Aufgaben-Erstellung aus CSV-Spalte
                $tasksToCreate = $p['tasks'] ?? [];
                if (!empty($tasksToCreate) && is_array($tasksToCreate)) {
                    $insTskStmt = $db->prepare("
                        INSERT INTO tasks (id, list_id, title, status, sort_order)
                        VALUES (?, ?, ?, 'todo', ?)
                    ");
                    $tOrder = 1;
                    foreach ($tasksToCreate as $taskItem) {
                        $taskTitle = is_array($taskItem) ? trim($taskItem['title'] ?? '') : trim((string)$taskItem);
                        if (!$taskTitle) continue;
                        $tId = 'tsk_' . substr(bin2hex(random_bytes(6)), 0, 8);
                        $insTskStmt->execute([$tId, $firstListId, $taskTitle, $tOrder++]);
                    }
                }

                $created[] = ['id' => $pId, 'folder_id' => $folderId, 'title' => $pTitle, 'status' => $pStatus];
            }

            jsonResponse(['success' => true, 'count' => count($created), 'projects' => $created]);
        }

        // --- EINZELNES PROJEKT ANLEGEN ---
        if (!$title) errorResponse('Titel erforderlich', 400);

        $status = (!empty($body['status']) && in_array($body['status'], ['active', 'archived', 'completed', 'on_hold'])) ? $body['status'] : 'active';
        $prjId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $appliedTemplateId = $templateId ?: 'folder_workflow';
        $db->prepare("INSERT INTO projects (id, folder_id, title, status, visibility, currency, budget_hours, budget_amount, custom_data, template_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $prjId, $folderId, $title, $status, $visibility, $currency, $budgetHours, $budgetAmount, json_encode($customData), $appliedTemplateId
        ]);

        // Add creator to project_members as owner
        $pmId = 'pm_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO project_members (id, project_id, user_id, role) VALUES (?, ?, ?, 'owner')")->execute([
            $pmId, $prjId, $user['id']
        ]);

        // Listen / Abschnitte bestimmen
        $listsToCreate = [];
        if (!empty($body['sections']) && is_array($body['sections'])) {
            foreach ($body['sections'] as $sec) {
                $sTitle = is_array($sec) ? trim($sec['title'] ?? '') : trim((string)$sec);
                if ($sTitle === '') continue;
                $isTarget = is_array($sec) ? (!empty($sec['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($sTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0);
                $listsToCreate[] = ['title' => $sTitle, 'is_completed_target' => $isTarget];
            }
            if (!empty($listsToCreate)) {
                $fSettings = !empty($folder['settings']) ? (is_string($folder['settings']) ? json_decode($folder['settings'], true) : $folder['settings']) : [];
                if (!is_array($fSettings)) $fSettings = [];
                $fSettings['default_sections'] = $listsToCreate;
                $db->prepare("UPDATE project_folders SET settings = ? WHERE id = ?")->execute([json_encode($fSettings, JSON_UNESCAPED_UNICODE), $folderId]);
            }
        } else if (!empty($customLists) && is_array($customLists)) {
            foreach ($customLists as $cl) {
                $clTitle = is_array($cl) ? trim($cl['title'] ?? '') : trim((string)$cl);
                if ($clTitle !== '') {
                    $isTarget = is_array($cl) ? (!empty($cl['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($clTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0);
                    $listsToCreate[] = ['title' => $clTitle, 'is_completed_target' => $isTarget];
                }
            }
        }

        $tmpl = null;
        if ($templateId) {
            $tStmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
            $tStmt->execute([$templateId]);
            $tmpl = $tStmt->fetch();
            if ($tmpl && empty($listsToCreate)) {
                $tLists = !empty($tmpl['lists']) ? (is_string($tmpl['lists']) ? json_decode($tmpl['lists'], true) : $tmpl['lists']) : [];
                if (!empty($tLists) && is_array($tLists)) {
                    foreach ($tLists as $tl) {
                        $tlTitle = is_array($tl) ? ($tl['title'] ?? '') : (string)$tl;
                        if ($tlTitle) {
                            $listsToCreate[] = ['title' => $tlTitle, 'is_completed_target' => is_array($tl) ? (!empty($tl['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($tlTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0)];
                        }
                    }
                }
            }
        }

        // Falls ueber import_tasks Abschnitte definiert wurden, die noch fehlen:
        if (!empty($importTasks) && is_array($importTasks)) {
            $existingTitles = array_map(function($x) { return is_array($x) ? $x['title'] : (string)$x; }, $listsToCreate);
            foreach ($importTasks as $it) {
                $sec = trim((string)($it['list_title'] ?? ''));
                if ($sec !== '' && !in_array($sec, $existingTitles)) {
                    $listsToCreate[] = ['title' => $sec, 'is_completed_target' => 0];
                    $existingTitles[] = $sec;
                }
            }
        }

        if (empty($listsToCreate)) {
            $fSettings = !empty($folder['settings']) ? (is_string($folder['settings']) ? json_decode($folder['settings'], true) : $folder['settings']) : [];
            if (is_array($fSettings) && !empty($fSettings['default_sections']) && is_array($fSettings['default_sections'])) {
                $listsToCreate = $fSettings['default_sections'];
            } else {
                $listsToCreate = [
                    ['title' => 'Offen', 'is_completed_target' => 0],
                    ['title' => 'In Arbeit', 'is_completed_target' => 0],
                    ['title' => 'Abgeschlossen', 'is_completed_target' => 1]
                ];
            }
        }

        // Listen anlegen und Map speichern: strtolower(title) => list_id
        $listMap = [];
        $firstListId = null;
        $order = 1;
        foreach ($listsToCreate as $item) {
            $listTitle = is_array($item) ? trim($item['title'] ?? '') : trim((string)$item);
            if ($listTitle === '') continue;
            $isTarget = is_array($item) ? (!empty($item['is_completed_target']) ? 1 : 0) : (in_array(mb_strtolower($listTitle), ['abgeschlossen', 'done', 'erledigt', 'fertig']) ? 1 : 0);
            $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
            if (!$firstListId) $firstListId = $lstId;
            $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order, is_completed_target) VALUES (?, ?, ?, 'inherit', ?, ?)")
               ->execute([$lstId, $prjId, $listTitle, $order++, $isTarget]);
            $listMap[mb_strtolower(trim($listTitle))] = $lstId;
        }

        // Falls Vorlage gewaehlt wurde: Benutzerdefinierte Felder in den Ordner replizieren
        $existingFieldsStmt = $db->prepare("SELECT field_key FROM folder_field_definitions WHERE folder_id = ?");
        $existingFieldsStmt->execute([$folderId]);
        $existingKeys = $existingFieldsStmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $countStmt = $db->prepare("SELECT COUNT(*) FROM folder_field_definitions WHERE folder_id = ?");
        $countStmt->execute([$folderId]);
        $sortOrder = (int)$countStmt->fetchColumn() + 1;

        if ($tmpl) {
            $fields = !empty($tmpl['fields']) ? (is_string($tmpl['fields']) ? json_decode($tmpl['fields'], true) : $tmpl['fields']) : [];
            if (!empty($fields) && is_array($fields)) {
                foreach ($fields as $f) {
                    $fKey = $f['field_key'] ?? strtolower(preg_replace('/[^a-z0-9_]/', '_', $f['label'] ?? 'field'));
                    if (in_array($fKey, $existingKeys)) continue;

                    $fId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $fLabel = $f['label'] ?? $fKey;
                    $fType = $f['field_type'] ?? 'text';
                    $fEntity = $f['entity_type'] ?? 'task';
                    $fOpts = $f['options'] ?? [];
                    $fRules = $f['logic_rules'] ?? null;
                    $fReq = !empty($f['is_required']) ? 1 : 0;

                    $fLabelKey = $f['label_key'] ?? null;
                    $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                       ->execute([$fId, $folderId, $fKey, $fLabel, $fLabelKey, $fType, $fEntity, json_encode($fOpts), $fRules ? json_encode($fRules) : null, $fReq, $sortOrder++]);
                    $existingKeys[] = $fKey;
                }
            }
        }

        // Benutzerdefinierte Felder aus Import / Parametern registrieren
        if (!empty($body['custom_field_definitions']) && is_array($body['custom_field_definitions'])) {
            foreach ($body['custom_field_definitions'] as $cfd) {
                $rawKey = trim((string)($cfd['field_key'] ?? $cfd['label'] ?? ''));
                $fKey = trim(strtolower(preg_replace('/[^a-z0-9_]/', '_', $rawKey)), '_');
                if (!$fKey || in_array($fKey, $existingKeys)) continue;

                $fId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $fLabel = trim((string)($cfd['label'] ?? $fKey));
                $fLabelKey = $cfd['label_key'] ?? null;
                $fType = $cfd['field_type'] ?? 'text';
                $fEntity = $cfd['entity_type'] ?? 'task';
                $fOpts = $cfd['options'] ?? [];
                $fRules = $cfd['logic_rules'] ?? null;

                $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, label_key, field_type, entity_type, options, logic_rules, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?)")
                   ->execute([$fId, $folderId, $fKey, $fLabel, $fLabelKey, $fType, $fEntity, json_encode($fOpts), $fRules ? json_encode($fRules) : null, $sortOrder++]);
                $existingKeys[] = $fKey;
            }
        }

        // Falls import_tasks uebergeben wurden: saemtliche Aufgaben anlegen
        if (!empty($importTasks) && is_array($importTasks)) {
            // Fehlende Felddefinitionen aus custom_data automatisch registrieren
            foreach ($importTasks as $taskItem) {
                if (!empty($taskItem['custom_data']) && is_array($taskItem['custom_data'])) {
                    foreach ($taskItem['custom_data'] as $k => $v) {
                        $rawKey = trim((string)$k);
                        $fKey = trim(strtolower(preg_replace('/[^a-z0-9_]/', '_', $rawKey)), '_');
                        if (!$fKey || in_array($fKey, $existingKeys)) continue;

                        $fId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
                        $fLabel = ucwords(str_replace('_', ' ', $fKey));
                        $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, entity_type, options, logic_rules, is_required, sort_order) VALUES (?, ?, ?, ?, 'text', 'task', '[]', null, 0, ?)")
                           ->execute([$fId, $folderId, $fKey, $fLabel, $sortOrder++]);
                        $existingKeys[] = $fKey;
                    }
                }
            }

            $insTask = $db->prepare("
                INSERT INTO tasks (id, list_id, title, description, status, priority, due_date, tags, custom_data)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($importTasks as $taskItem) {
                $tTitle = trim((string)($taskItem['title'] ?? ''));
                if ($tTitle === '') continue;

                $targetSec = mb_strtolower(trim((string)($taskItem['list_title'] ?? '')));
                $targetListId = isset($listMap[$targetSec]) ? $listMap[$targetSec] : $firstListId;

                $tId = 'tsk_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $tDesc = (string)($taskItem['description'] ?? '');
                $tStatus = (string)($taskItem['status'] ?? 'todo');
                $tPriority = (string)($taskItem['priority'] ?? 'normal');
                $tDueDate = !empty($taskItem['due_date']) ? parseImportDate($taskItem['due_date']) : null;
                $tTags = !empty($taskItem['tags']) ? (is_array($taskItem['tags']) ? json_encode($taskItem['tags']) : json_encode([$taskItem['tags']])) : '[]';
                $tCustomData = !empty($taskItem['custom_data']) && is_array($taskItem['custom_data']) ? json_encode($taskItem['custom_data']) : '{}';

                $insTask->execute([
                    $tId, $targetListId, $tTitle, $tDesc, $tStatus, $tPriority, $tDueDate, $tTags, $tCustomData
                ]);
            }
        }

        jsonResponse(['project' => ['id' => $prjId, 'folder_id' => $folderId, 'title' => $title, 'status' => 'active']]);
    }


    // 9. GET projects/:id
    if (preg_match('#^projects/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $projectId = $m[1];
        $context = evaluateProjectAccess($user, $projectId, 'read');

        $pStmt = $db->prepare("
            SELECT p.*, pf.name as folder_name, pf.owner_id, pf.company_id, c.name as company_name
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            LEFT JOIN companies c ON c.id = pf.company_id
            WHERE p.id = ?
        ");
        $pStmt->execute([$projectId]);
        $project = $pStmt->fetch();
        if ($project) {
            $project['visibility'] = $project['visibility'] ?? 'private';
            $project['custom_data'] = !empty($project['custom_data']) ? (is_string($project['custom_data']) ? json_decode($project['custom_data'], true) : $project['custom_data']) : [];
            $tHoursStmt = $db->prepare("SELECT SUM(duration_minutes) FROM time_entries WHERE project_id = ?");
            $tHoursStmt->execute([$projectId]);
            $projectTotalMinutes = (int)($tHoursStmt->fetchColumn() ?: 0);
            $project['tracked_hours'] = round($projectTotalMinutes / 60, 2);
            $project['budget_hours'] = $project['budget_hours'] !== null ? floatval($project['budget_hours']) : null;
            $project['budget_amount'] = $project['budget_amount'] !== null ? floatval($project['budget_amount']) : null;
        }

        // Fields
        $fStmt = $db->prepare("SELECT * FROM folder_field_definitions WHERE folder_id = ? ORDER BY sort_order ASC");
        $fStmt->execute([$context['folderId']]);
        $fields = array_map(function($f) {
            $f['options'] = !empty($f['options']) ? (is_string($f['options']) ? json_decode($f['options'], true) : $f['options']) : [];
            $f['logic_rules'] = !empty($f['logic_rules']) ? (is_string($f['logic_rules']) ? json_decode($f['logic_rules'], true) : $f['logic_rules']) : [];
            $f['entity_type'] = $f['entity_type'] ?? 'task';
            return $f;
        }, $fStmt->fetchAll());

        // Lists
        $lStmt = $db->prepare("SELECT * FROM lists WHERE project_id = ? ORDER BY sort_order ASC");
        $lStmt->execute([$projectId]);
        $allLists = $lStmt->fetchAll();

        $accessibleLists = [];
        foreach ($allLists as $l) {
            if ($l['access_mode'] === 'inherit' || $context['userRole'] === 'owner' || $context['userRole'] === 'admin') {
                $accessibleLists[] = $l;
            } else {
                $aStmt = $db->prepare("SELECT is_visible FROM list_access WHERE list_id = ? AND user_id = ?");
                $aStmt->execute([$l['id'], $user['id']]);
                $acc = $aStmt->fetch();
                if ($acc && $acc['is_visible'] == 1) {
                    $accessibleLists[] = $l;
                }
            }
        }

        // Tasks for lists
        foreach ($accessibleLists as &$l) {
            $tStmt = $db->prepare("
                SELECT t.*, u.name as assignee_name, u.email as assignee_email,
                       COALESCE((SELECT SUM(duration_minutes) FROM time_entries WHERE task_id = t.id), 0) as tracked_minutes
                FROM tasks t
                LEFT JOIN users u ON u.id = t.assigned_to
                WHERE t.list_id = ?
                ORDER BY t.sort_order ASC, t.created_at DESC
            ");
            $tStmt->execute([$l['id']]);
            $l['tasks'] = array_map(function($t) {
                $t['custom_data'] = !empty($t['custom_data']) ? (is_string($t['custom_data']) ? json_decode($t['custom_data'], true) : $t['custom_data']) : [];
                $t['tracked_minutes'] = (int)($t['tracked_minutes'] ?? 0);
                $t['tracked_hours'] = round($t['tracked_minutes'] / 60, 2);
                $t['budget_hours'] = $t['budget_hours'] !== null ? floatval($t['budget_hours']) : null;
                $t['budget_amount'] = $t['budget_amount'] !== null ? floatval($t['budget_amount']) : null;

                $assigned = [];
                if (!empty($t['assigned_to'])) {
                    if (str_starts_with($t['assigned_to'], '[')) {
                        $assigned = json_decode($t['assigned_to'], true) ?: [];
                    } else {
                        $assigned = [$t['assigned_to']];
                    }
                }
                $t['assigned_users'] = $assigned;
                return $t;
            }, $tStmt->fetchAll());
        }

        // Comprehensive Members list for project & task assignments:
        // Includes: Project Owner, Folder Owner, all project_members, all folder_members, and all colleagues in the company!
        $companyId = $project['company_id'] ?? null;
        if (!$companyId && !empty($user['company_id'])) {
            $companyId = $user['company_id'];
        }

        $mStmt = $db->prepare("
            SELECT u.id as user_id, u.name, u.email, u.company_role,
                   COALESCE(pm.role, fm.role, CASE WHEN u.id = ? OR u.id = ? THEN 'owner' ELSE 'member' END) as role
            FROM users u
            LEFT JOIN project_members pm ON pm.user_id = u.id AND pm.project_id = ?
            LEFT JOIN folder_members fm ON fm.user_id = u.id AND fm.folder_id = ?
            WHERE pm.project_id = ?
               OR fm.folder_id = ?
               OR u.id = ?
               OR u.id = ?
               OR (? IS NOT NULL AND u.company_id = ?)
            GROUP BY u.id
            ORDER BY (u.id = ?) DESC, (u.id = ?) DESC, u.name ASC
        ");
        $mStmt->execute([
            $project['owner_id'], $context['ownerId'],
            $projectId,
            $context['folderId'],
            $projectId,
            $context['folderId'],
            $project['owner_id'],
            $context['ownerId'],
            $companyId, $companyId,
            $user['id'], $project['owner_id']
        ]);
        $members = $mStmt->fetchAll();

        jsonResponse([
            'project' => $project,
            'userRole' => $context['userRole'],
            'fields' => $fields,
            'lists' => $accessibleLists,
            'members' => $members
        ]);
    }

    // 9b. PUT / PATCH projects/:id (Update project details & custom fields)
    if (preg_match('#^projects/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $projectId = $m[1];
        evaluateProjectAccess($user, $projectId, 'write');

        $pStmt = $db->prepare("SELECT * FROM projects WHERE id = ?");
        $pStmt->execute([$projectId]);
        $project = $pStmt->fetch();
        if (!$project) errorResponse('Projekt nicht gefunden', 404);

        $title = isset($body['title']) ? trim($body['title']) : $project['title'];
        $status = isset($body['status']) ? trim($body['status']) : $project['status'];
        $currency = isset($body['currency']) ? trim($body['currency']) : ($project['currency'] ?? 'CHF');
        $budgetHours = array_key_exists('budget_hours', $body) ? ($body['budget_hours'] !== null && $body['budget_hours'] !== '' ? floatval($body['budget_hours']) : 0.0) : ($project['budget_hours'] !== null ? floatval($project['budget_hours']) : 0.0);
        $budgetAmount = array_key_exists('budget_amount', $body) ? ($body['budget_amount'] !== null && $body['budget_amount'] !== '' ? floatval($body['budget_amount']) : 0.0) : ($project['budget_amount'] !== null ? floatval($project['budget_amount']) : 0.0);
        $visibility = isset($body['visibility']) ? ((!empty($user['company_id']) && $body['visibility'] === 'company') ? 'company' : 'private') : ($project['visibility'] ?? 'private');
        $customData = isset($body['custom_data']) ? json_encode($body['custom_data']) : $project['custom_data'];

        $db->prepare("UPDATE projects SET title = ?, status = ?, currency = ?, budget_hours = ?, budget_amount = ?, visibility = ?, custom_data = ? WHERE id = ?")->execute([
            $title, $status, $currency, $budgetHours, $budgetAmount, $visibility, $customData, $projectId
        ]);

        jsonResponse(['success' => true]);
    }

    // 9c. DELETE projects/:id
    if (preg_match('#^projects/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $projectId = $m[1];
        $context = evaluateProjectAccess($user, $projectId, 'write');

        $canDelete = ($context['userRole'] === 'owner' || $context['userRole'] === 'admin' || $context['ownerId'] === $user['id'] || !empty($user['is_superadmin']));
        if (!$canDelete) {
            errorResponse('Keine Berechtigung zum Löschen dieses Projekts', 403);
        }

        $db->beginTransaction();
        try {
            deleteProjectCascade($db, $projectId);
            $db->commit();
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            errorResponse('Fehler beim Löschen des Projekts: ' . $e->getMessage(), 500);
        }
    }

    // 9d. GET projects/:id/export (Exklusiv im Enterprise-Tarif)
    if (preg_match('#^projects/([^/]+)/export$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $projectId = $m[1];
        evaluateProjectAccess($user, $projectId, 'read');

        $planDetails = getUserPlanDetails($db, $user);
        if ($planDetails['plan'] !== 'enterprise' && empty($user['is_superadmin'])) {
            errorResponse('Projekt-Export ist exklusiv im Enterprise-Tarif verfügbar.', 403);
        }

        $format = strtolower($_GET['format'] ?? 'json');

        $pStmt = $db->prepare("SELECT p.*, pf.name as folder_name FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.id = ?");
        $pStmt->execute([$projectId]);
        $project = $pStmt->fetch(PDO::FETCH_ASSOC);
        if (!$project) errorResponse('Projekt nicht gefunden', 404);

        $lStmt = $db->prepare("SELECT * FROM lists WHERE project_id = ? ORDER BY sort_order ASC");
        $lStmt->execute([$projectId]);
        $lists = $lStmt->fetchAll(PDO::FETCH_ASSOC);

        $tStmt = $db->prepare("
            SELECT t.*, l.title as list_title 
            FROM tasks t 
            JOIN lists l ON l.id = t.list_id 
            WHERE l.project_id = ? 
            ORDER BY l.sort_order ASC, t.sort_order ASC
        ");
        $tStmt->execute([$projectId]);
        $tasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="projekt_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['title']) . '_' . date('Ymd_His') . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, ['Projekt', 'Ordner', 'Status', 'Abschnitt', 'Aufgabe', 'Beschreibung', 'Aufgabenstatus', 'Faelligkeit', 'Prioritaet', 'Budget Stunden', 'Budget Betrag'], ';');
            foreach ($tasks as $t) {
                fputcsv($out, [
                    $project['title'],
                    $project['folder_name'],
                    $project['status'],
                    $t['list_title'],
                    $t['title'],
                    $t['description'] ?? '',
                    $t['status'],
                    $t['due_date'] ?? '',
                    $t['priority'] ?? 'normal',
                    $t['budget_hours'] ?? 0,
                    $t['budget_amount'] ?? 0
                ], ';');
            }
            if (empty($tasks)) {
                fputcsv($out, [$project['title'], $project['folder_name'], $project['status'], '', '', '', '', '', '', '', ''], ';');
            }
            fclose($out);
            exit;
        }

        jsonResponse([
            'project' => $project,
            'lists' => $lists,
            'tasks' => $tasks,
            'exported_at' => date('c'),
            'exported_by' => $user['email']
        ]);
    }

    // 10. POST projects/:id/members
    if (preg_match('#^projects/([^/]+)/members$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $projectId = $m[1];
        $email = strtolower(trim($body['email'] ?? ''));
        $role = $body['role'] ?? 'editor';
        evaluateProjectAccess($user, $projectId, 'write');

        $uStmt = $db->prepare("SELECT id, name, email FROM users WHERE LOWER(email) = ?");
        $uStmt->execute([$email]);
        $target = $uStmt->fetch();
        if (!$target) errorResponse('Benutzer mit dieser E-Mail nicht registriert', 404);

        $db->prepare("
            INSERT INTO project_members (id, project_id, user_id, role)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE role = VALUES(role)
        ")->execute(['pm_' . substr(bin2hex(random_bytes(6)), 0, 8), $projectId, $target['id'], $role]);

        if ($target['id'] !== $user['id']) {
            $pTitle = $db->query("SELECT title FROM projects WHERE id = " . $db->quote($projectId))->fetchColumn() ?: 'Projekt';
            createNotification($target['id'], 'invitation', 'Neue Projekt-Einladung', "{$user['name']} hat dich zum Projekt \"{$pTitle}\" eingeladen.", 'project', $projectId, $projectId);
        }

        jsonResponse(['success' => true]);
    }

    // 11. POST lists
    if ($path === 'lists' && $method === 'POST') {
        $user = requireAuth();
        $projectId = $body['project_id'] ?? '';
        $title = trim($body['title'] ?? '');
        $accessMode = $body['access_mode'] ?? 'inherit';
        $isCompletedTarget = !empty($body['is_completed_target']) ? 1 : 0;
        evaluateProjectAccess($user, $projectId, 'write');

        if ($isCompletedTarget) {
            $db->prepare("UPDATE lists SET is_completed_target = 0 WHERE project_id = ?")->execute([$projectId]);
        }

        $listId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $countStmt = $db->prepare("SELECT COUNT(*) FROM lists WHERE project_id = ?");
        $countStmt->execute([$projectId]);
        $nextSort = (int)$countStmt->fetchColumn() + 1;

        $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order, is_completed_target) VALUES (?, ?, ?, ?, ?, ?)")->execute([$listId, $projectId, $title, $accessMode, $nextSort, $isCompletedTarget]);

        jsonResponse(['success' => true, 'list' => ['id' => $listId, 'title' => $title, 'access_mode' => $accessMode, 'sort_order' => $nextSort, 'is_completed_target' => $isCompletedTarget]]);
    }

    // 11b. PUT lists/:id
    if (preg_match('#^lists/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $listId = $m[1];
        $acc = evaluateListAccess($user, $listId, 'write');
        $list = $acc['list'];

        $title = isset($body['title']) ? trim($body['title']) : $list['title'];
        $accessMode = $body['access_mode'] ?? $list['access_mode'];
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : (int)$list['sort_order'];
        $isCompletedTarget = isset($body['is_completed_target']) ? (!empty($body['is_completed_target']) ? 1 : 0) : (int)($list['is_completed_target'] ?? 0);

        if ($isCompletedTarget) {
            $pId = $list['project_id'] ?? $db->query("SELECT project_id FROM lists WHERE id = " . $db->quote($listId))->fetchColumn();
            if ($pId) {
                $db->prepare("UPDATE lists SET is_completed_target = 0 WHERE project_id = ?")->execute([$pId]);
            }
        }

        $db->prepare("UPDATE lists SET title = ?, access_mode = ?, sort_order = ?, is_completed_target = ? WHERE id = ?")->execute([$title, $accessMode, $sortOrder, $isCompletedTarget, $listId]);
        jsonResponse(['success' => true]);
    }

    // 11c. DELETE lists/:id
    if (preg_match('#^lists/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $listId = $m[1];
        evaluateListAccess($user, $listId, 'write');

        $db->prepare("DELETE FROM tasks WHERE list_id = ?")->execute([$listId]);
        $db->prepare("DELETE FROM lists WHERE id = ?")->execute([$listId]);
        jsonResponse(['success' => true]);
    }

    // 11d. POST lists/reorder
    if ($path === 'lists/reorder' && $method === 'POST') {
        $user = requireAuth();
        $projectId = $body['project_id'] ?? '';
        evaluateProjectAccess($user, $projectId, 'write');

        $lists = $body['lists'] ?? [];
        if (is_array($lists)) {
            $upStmt = $db->prepare("UPDATE lists SET sort_order = ?, title = COALESCE(?, title), color = ?, is_completed_target = ? WHERE id = ? AND project_id = ?");
            foreach ($lists as $idx => $item) {
                $lid = is_string($item) ? $item : ($item['id'] ?? '');
                $title = (is_array($item) && !empty($item['title'])) ? trim($item['title']) : null;
                $sort = (is_array($item) && isset($item['sort_order'])) ? (int)$item['sort_order'] : ($idx + 1);
                $color = (is_array($item) && array_key_exists('color', $item)) ? ($item['color'] ?: null) : null;
                $target = (is_array($item) && !empty($item['is_completed_target'])) ? 1 : 0;
                if ($lid) {
                    $upStmt->execute([$sort, $title, $color, $target, $lid, $projectId]);
                }
            }
        }
        jsonResponse(['success' => true]);
    }

    // 11e. GET tasks (Query assigned or relevant tasks across accessible projects for personal dashboard)
    if ($path === 'tasks' && $method === 'GET') {
        $user = requireAuth();
        $tStmt = $db->prepare("
            SELECT t.*, l.title as list_title, p.id as project_id, p.title as project_title, pf.id as folder_id, pf.name as folder_name, pf.icon as folder_icon
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.owner_id = ? OR p.id IN (
              SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?
            )
            ORDER BY t.created_at DESC
            LIMIT 20
        ");
        $tStmt->execute([$user['id'], $user['id']]);
        $tasks = array_map(function($t) {
            $t['custom_data'] = !empty($t['custom_data']) ? (is_string($t['custom_data']) ? json_decode($t['custom_data'], true) : $t['custom_data']) : [];
            return $t;
        }, $tStmt->fetchAll());
        jsonResponse(['tasks' => $tasks]);
    }

    // 12. POST tasks
    if ($path === 'tasks' && $method === 'POST') {
        $user = requireAuth();
        $listId = $body['list_id'] ?? '';
        $title = trim($body['title'] ?? '');
        $desc = $body['description'] ?? '';
        $status = $body['status'] ?? 'todo';
        $dueDate = !empty($body['due_date']) ? parseImportDate($body['due_date']) : null;
        $customData = $body['custom_data'] ?? [];
        $assignedTo = !empty($body['assigned_to']) ? $body['assigned_to'] : null;
        $priority = !empty($body['priority']) ? $body['priority'] : 'normal';
        $color = !empty($body['color']) ? $body['color'] : null;
        $tags = isset($body['tags']) ? json_encode($body['tags']) : '[]';
        $checklist = isset($body['checklist']) ? json_encode($body['checklist']) : '[]';

        evaluateListAccess($user, $listId, 'write');

        $planDetails = getUserPlanDetails($db, $user);
        if ($planDetails['plan'] === 'basic') {
            $prjStmt = $db->prepare("SELECT project_id FROM lists WHERE id = ?");
            $prjStmt->execute([$listId]);
            $projectId = $prjStmt->fetchColumn();
            if ($projectId) {
                $tCountStmt = $db->prepare("
                    SELECT COUNT(*) FROM tasks t 
                    JOIN lists l ON l.id = t.list_id 
                    WHERE l.project_id = ?
                ");
                $tCountStmt->execute([$projectId]);
                $taskCount = (int)$tCountStmt->fetchColumn();
                if ($taskCount >= 30) {
                    errorResponse('Limit erreicht: Im Free-Tarif sind maximal 30 Aufgaben pro Projekt erlaubt. Bitte auf Pro upgraden.', 403);
                }
            }
        }

        $assignedTo = null;
        if (!empty($body['assigned_to'])) {
            if (is_array($body['assigned_to'])) {
                $assignedTo = json_encode(array_values($body['assigned_to']));
            } else {
                $assignedTo = (string)$body['assigned_to'];
            }
        }

        $taskId = 'tsk_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("
            INSERT INTO tasks (id, list_id, title, description, status, due_date, custom_data, sort_order, assigned_to, priority, color, tags, checklist)
            VALUES (?, ?, ?, ?, ?, ?, ?, (SELECT COALESCE(MAX(t.sort_order), 0) + 1 FROM tasks t WHERE t.list_id = ?), ?, ?, ?, ?, ?)
        ")->execute([
            $taskId, $listId, $title, $desc, $status, $dueDate,
            json_encode($customData), $listId, $assignedTo, $priority, $color, $tags, $checklist
        ]);

        jsonResponse([
            'success' => true,
            'task' => [
                'id' => $taskId,
                'list_id' => $listId,
                'title' => $title,
                'description' => $desc,
                'status' => $status,
                'due_date' => $dueDate,
                'custom_data' => $customData,
                'assigned_to' => $assignedTo,
                'priority' => $priority,
                'color' => $color,
                'tags' => isset($body['tags']) ? $body['tags'] : [],
                'checklist' => isset($body['checklist']) ? $body['checklist'] : []
            ]
        ]);
    }

    // 13. PUT tasks/:id
    if (preg_match('#^tasks/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $taskId = $m[1];

        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);

        $listAccess = evaluateListAccess($user, $task['list_id'], 'read');
        $userRole = $listAccess['projectContext']['userRole'] ?? 'viewer';

        // Viewer-Rolle: Eingeladener Viewer kann Aufgaben sehen und abhaken!
        if ($userRole === 'viewer') {
            $newStatus = $body['status'] ?? $task['status'];
            $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$newStatus, $taskId]);
            jsonResponse(['success' => true]);
        }

        // Editor & Owner: dürfen Aufgaben ändern, verschieben, bearbeiten
        evaluateListAccess($user, $task['list_id'], 'write');

        $title = $body['title'] ?? $task['title'];
        $desc = $body['description'] ?? $task['description'];
        $status = $body['status'] ?? $task['status'];
        $dueDate = array_key_exists('due_date', $body) ? parseImportDate($body['due_date']) : $task['due_date'];
        $customData = isset($body['custom_data']) ? json_encode($body['custom_data']) : $task['custom_data'];
        $listId = $body['list_id'] ?? $task['list_id'];
        $sortOrder = isset($body['sort_order']) ? (int)$body['sort_order'] : (int)($task['sort_order'] ?? 0);

        // If target list differs from current list, check write access on target list too
        if ($listId !== $task['list_id']) {
            evaluateListAccess($user, $listId, 'write');
        }

        // Mehrfach-Zuweisung unterstützen
        $assignedTo = $task['assigned_to'] ?? null;
        if (array_key_exists('assigned_to', $body)) {
            if (is_array($body['assigned_to'])) {
                $assignedTo = !empty($body['assigned_to']) ? json_encode(array_values($body['assigned_to'])) : null;
            } else {
                $assignedTo = !empty($body['assigned_to']) ? (string)$body['assigned_to'] : null;
            }
        }

        $priority = $body['priority'] ?? ($task['priority'] ?? 'normal');
        $color = array_key_exists('color', $body) ? ($body['color'] ?: null) : ($task['color'] ?? null);
        $tags = isset($body['tags']) ? json_encode($body['tags']) : ($task['tags'] ?? '[]');
        $checklist = isset($body['checklist']) ? json_encode($body['checklist']) : ($task['checklist'] ?? '[]');
        $budgetHours = array_key_exists('budget_hours', $body) ? ($body['budget_hours'] !== null && $body['budget_hours'] !== '' ? floatval($body['budget_hours']) : 0.0) : ($task['budget_hours'] !== null ? floatval($task['budget_hours']) : 0.0);
        $budgetAmount = array_key_exists('budget_amount', $body) ? ($body['budget_amount'] !== null && $body['budget_amount'] !== '' ? floatval($body['budget_amount']) : 0.0) : ($task['budget_amount'] !== null ? floatval($task['budget_amount']) : 0.0);

        $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, custom_data = ?, list_id = ?, sort_order = ?, assigned_to = ?, priority = ?, color = ?, tags = ?, checklist = ?, budget_hours = ?, budget_amount = ? WHERE id = ?")->execute([
            $title, $desc, $status, $dueDate, $customData, $listId, $sortOrder,
            $assignedTo, $priority, $color, $tags, $checklist, $budgetHours, $budgetAmount, $taskId
        ]);

        // Benachrichtigung an Zuweiser bei Bearbeitung durch Teammitglied
        if (!empty($assignedTo) && $assignedTo !== $user['id']) {
            createNotification($assignedTo, 'task_updated', 'Aufgabe aktualisiert', "{$user['name']} hat die Aufgabe \"{$title}\" bearbeitet.", 'task', $taskId, null);
        }

        jsonResponse(['success' => true]);
    }

    // 13b. POST tasks/reorder (Kanban drag-and-drop batch reorder)
    if ($path === 'tasks/reorder' && $method === 'POST') {
        $user = requireAuth();
        $items = $body['items'] ?? [];
        if (!is_array($items)) errorResponse('Items array erforderlich', 400);

        // Validate access and update positions
        foreach ($items as $item) {
            $tId = $item['id'] ?? '';
            $targetListId = $item['list_id'] ?? '';
            $sort = (int)($item['sort_order'] ?? 0);
            $newStatus = $item['status'] ?? null;

            if ($tId && $targetListId) {
                $tStmt = $db->prepare("SELECT list_id, status FROM tasks WHERE id = ?");
                $tStmt->execute([$tId]);
                $cur = $tStmt->fetch();
                if ($cur) {
                    evaluateListAccess($user, $cur['list_id'], 'write');
                    if ($targetListId !== $cur['list_id']) {
                        evaluateListAccess($user, $targetListId, 'write');
                    }
                    $statusToSet = $newStatus !== null ? $newStatus : $cur['status'];
                    $db->prepare("UPDATE tasks SET list_id = ?, sort_order = ?, status = ? WHERE id = ?")->execute([
                        $targetListId, $sort, $statusToSet, $tId
                    ]);
                }
            }
        }

        jsonResponse(['success' => true]);
    }

    // 14. DELETE tasks/:id
    if (preg_match('#^tasks/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $taskId = $m[1];
        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);

        $listAccess = evaluateListAccess($user, $task['list_id'], 'read');
        $userRole = $listAccess['projectContext']['userRole'] ?? 'viewer';

        // Editor darf Aufgaben bearbeiten, aber NICHT löschen!
        if ($userRole === 'editor' || $userRole === 'viewer') {
            errorResponse('Nur der Projekt-Owner oder Administrator darf Aufgaben löschen.', 403);
        }

        evaluateListAccess($user, $task['list_id'], 'write');
        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);

        jsonResponse(['success' => true]);
    }

    // 13c. GET tasks/:id (detail with subtasks, comments, time entries)
    if (preg_match('#^tasks/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $taskId = $m[1];
        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);

        evaluateListAccess($user, $task['list_id'], 'read');

        $task['custom_data'] = !empty($task['custom_data']) ? json_decode($task['custom_data'], true) : [];
        $task['tags'] = !empty($task['tags']) ? json_decode($task['tags'], true) : [];
        $task['checklist'] = !empty($task['checklist']) ? json_decode($task['checklist'], true) : [];

        $tHoursStmt = $db->prepare("SELECT SUM(duration_minutes) FROM time_entries WHERE task_id = ?");
        $tHoursStmt->execute([$taskId]);
        $taskTrackedMinutes = (int)($tHoursStmt->fetchColumn() ?: 0);
        $task['tracked_hours'] = round($taskTrackedMinutes / 60, 2);
        $task['budget_hours'] = $task['budget_hours'] !== null ? floatval($task['budget_hours']) : null;
        $task['budget_amount'] = $task['budget_amount'] !== null ? floatval($task['budget_amount']) : null;

        $assignedUsers = [];
        if (!empty($task['assigned_to'])) {
            if (str_starts_with($task['assigned_to'], '[')) {
                $assignedUsers = json_decode($task['assigned_to'], true) ?: [];
            } else {
                $assignedUsers = [$task['assigned_to']];
            }
        }
        $task['assigned_users'] = $assignedUsers;

        $assignee = null;
        if (!empty($assignedUsers)) {
            $firstId = $assignedUsers[0];
            $aStmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
            $aStmt->execute([$firstId]);
            $assignee = $aStmt->fetch() ?: null;
        }

        $subStmt = $db->prepare("SELECT * FROM task_subtasks WHERE task_id = ? ORDER BY sort_order ASC, created_at ASC");
        $subStmt->execute([$taskId]);
        $subtasks = $subStmt->fetchAll();

        $cStmt = $db->prepare("SELECT tc.*, u.name as author_name FROM task_comments tc JOIN users u ON u.id = tc.author_id WHERE tc.task_id = ? ORDER BY tc.created_at ASC");
        $cStmt->execute([$taskId]);
        $comments = $cStmt->fetchAll();

        $dStmt = $db->prepare("SELECT * FROM project_documents WHERE task_id = ? ORDER BY created_at DESC");
        $dStmt->execute([$taskId]);
        $documents = $dStmt->fetchAll();

        $teStmt = $db->prepare("
            SELECT te.*, u.name as user_name
            FROM time_entries te
            JOIN users u ON u.id = te.user_id
            WHERE te.task_id = ?
            ORDER BY te.entry_date DESC, te.created_at DESC
        ");
        $teStmt->execute([$taskId]);
        $timeEntries = $teStmt->fetchAll();

        jsonResponse([
            'task' => array_merge($task, ['assignee' => $assignee]),
            'subtasks' => $subtasks,
            'comments' => $comments,
            'documents' => $documents,
            'timeEntries' => $timeEntries
        ]);
    }

    // 13d. POST tasks/:id/comments
    if (preg_match('#^tasks/([^/]+)/comments$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $taskId = $m[1];
        $content = trim($body['content'] ?? '');
        if (!$content) errorResponse('Kommentar darf nicht leer sein', 400);

        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'read');

        $cId = 'cmt_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO task_comments (id, task_id, author_id, content) VALUES (?, ?, ?, ?)")->execute([$cId, $taskId, $user['id'], $content]);

        // Benachrichtigung an Zuweiser und Projekt-Inhaber
        $tInfoStmt = $db->prepare("
            SELECT t.assigned_to, t.title, p.id as project_id, p.title as project_title, pf.owner_id as project_owner_id
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE t.id = ?
        ");
        $tInfoStmt->execute([$taskId]);
        $tInfo = $tInfoStmt->fetch();
        if ($tInfo) {
            if (!empty($tInfo['assigned_to']) && $tInfo['assigned_to'] !== $user['id']) {
                $snippet = mb_strlen($content) > 60 ? mb_substr($content, 0, 60) . '...' : $content;
                createNotification($tInfo['assigned_to'], 'new_comment', 'Neuer Kommentar', "{$user['name']} kommentierte \"{$tInfo['title']}\": \"{$snippet}\"", 'task', $taskId, $tInfo['project_id']);
            }
            if (!empty($tInfo['project_owner_id']) && $tInfo['project_owner_id'] !== $user['id'] && $tInfo['project_owner_id'] !== ($tInfo['assigned_to'] ?? '')) {
                createNotification($tInfo['project_owner_id'], 'new_comment', 'Neuer Kommentar im Projekt', "{$user['name']} kommentierte die Aufgabe \"{$tInfo['title']}\" in \"{$tInfo['project_title']}\".", 'task', $taskId, $tInfo['project_id']);
            }
        }

        jsonResponse(['comment' => ['id' => $cId, 'task_id' => $taskId, 'author_id' => $user['id'], 'author_name' => $user['name'], 'content' => $content, 'created_at' => date('Y-m-d H:i:s')]]);
    }

    // 13e. POST tasks/:id/subtasks
    if (preg_match('#^tasks/([^/]+)/subtasks$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $taskId = $m[1];
        $title = trim($body['title'] ?? '');
        if (!$title) errorResponse('Titel erforderlich', 400);

        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'write');

        $countStmt = $db->prepare("SELECT COUNT(*) FROM task_subtasks WHERE task_id = ?");
        $countStmt->execute([$taskId]);
        $nextSort = (int)$countStmt->fetchColumn() + 1;

        $sId = 'sub_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO task_subtasks (id, task_id, title, is_done, sort_order) VALUES (?, ?, ?, 0, ?)")->execute([$sId, $taskId, $title, $nextSort]);
        jsonResponse(['subtask' => ['id' => $sId, 'task_id' => $taskId, 'title' => $title, 'is_done' => 0, 'sort_order' => $nextSort]]);
    }

    // 13f. PUT tasks/:id/subtasks/:subId
    if (preg_match('#^tasks/([^/]+)/subtasks/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $taskId = $m[1]; $subId = $m[2];
        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?"); $tStmt->execute([$taskId]); $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'write');
        $sStmt = $db->prepare("SELECT * FROM task_subtasks WHERE id = ? AND task_id = ?"); $sStmt->execute([$subId, $taskId]); $sub = $sStmt->fetch();
        if (!$sub) errorResponse('Unteraufgabe nicht gefunden', 404);
        $isDone = isset($body['is_done']) ? (int)$body['is_done'] : (int)$sub['is_done'];
        $title = isset($body['title']) ? trim($body['title']) : $sub['title'];
        $db->prepare("UPDATE task_subtasks SET is_done = ?, title = ? WHERE id = ?")->execute([$isDone, $title, $subId]);
        jsonResponse(['subtask' => ['id' => $subId, 'task_id' => $taskId, 'title' => $title, 'is_done' => $isDone]]);
    }

    // 13g. DELETE tasks/:id/subtasks/:subId
    if (preg_match('#^tasks/([^/]+)/subtasks/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $taskId = $m[1]; $subId = $m[2];
        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?"); $tStmt->execute([$taskId]); $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'write');
        $db->prepare("DELETE FROM task_subtasks WHERE id = ? AND task_id = ?")->execute([$subId, $taskId]);
        jsonResponse(['success' => true]);
    }

    // 13h. POST tasks/:id/documents
    if (preg_match('#^tasks/([^/]+)/documents$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $taskId = $m[1];
        $fileName = trim($body['file_name'] ?? '');
        $mimeType = trim($body['mime_type'] ?? 'application/octet-stream');
        $fileSize = (int)($body['file_size'] ?? 0);
        $storagePath = trim($body['storage_path'] ?? '');

        if (!$fileName || !$storagePath) errorResponse('Dateiname und Inhalt erforderlich', 400);

        $tStmt = $db->prepare("SELECT t.*, l.project_id FROM tasks t JOIN lists l ON l.id = t.list_id WHERE t.id = ?");
        $tStmt->execute([$taskId]);
        $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'write');

        $docId = 'doc_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("
            INSERT INTO project_documents (id, project_id, task_id, file_name, mime_type, file_size, storage_path, version, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
        ")->execute([$docId, $task['project_id'], $taskId, $fileName, $mimeType, $fileSize, $storagePath]);

        jsonResponse([
            'document' => [
                'id' => $docId,
                'project_id' => $task['project_id'],
                'task_id' => $taskId,
                'file_name' => $fileName,
                'mime_type' => $mimeType,
                'file_size' => $fileSize,
                'storage_path' => $storagePath,
                'version' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'uploaded_by_name' => $user['name']
            ]
        ]);
    }

    // 13i. DELETE tasks/:id/documents/:docId
    if (preg_match('#^tasks/([^/]+)/documents/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $taskId = $m[1]; $docId = $m[2];
        $tStmt = $db->prepare("SELECT * FROM tasks WHERE id = ?"); $tStmt->execute([$taskId]); $task = $tStmt->fetch();
        if (!$task) errorResponse('Aufgabe nicht gefunden', 404);
        evaluateListAccess($user, $task['list_id'], 'write');

        $db->prepare("DELETE FROM project_documents WHERE id = ? AND task_id = ?")->execute([$docId, $taskId]);
        jsonResponse(['success' => true]);
    }

    // 15. GET projects/:id/journal & GET journals
    if ((preg_match('#^projects/([^/]+)/journal$#', $path, $m) || ($path === 'journals' && (!empty($_GET['project_id']) || !empty($_GET['folder_id'])))) && $method === 'GET') {
        $user = requireAuth();
        $projectId = !empty($m[1]) ? $m[1] : ($_GET['project_id'] ?? '');
        $folderId = trim($_GET['folder_id'] ?? '');

        if ($projectId) {
            evaluateProjectAccess($user, $projectId, 'read');
        } else if ($folderId) {
            // Folder access check
            $fCheckStmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
            $fCheckStmt->execute([$folderId]);
            $folder = $fCheckStmt->fetch();
            if (!$folder) errorResponse('Ordner nicht gefunden', 404);
            if ($folder['owner_id'] !== $user['id']) {
                if ($folder['visibility'] !== 'company' || empty($user['company_id']) || $user['company_id'] !== $folder['company_id']) {
                    errorResponse('Keine Berechtigung', 403);
                }
            }
        }

        $isSuperadmin = !empty($user['is_superadmin']) ? 1 : 0;
        $userId = $user['id'];
        $companyId = $user['company_id'] ?? '';

        $whereClause = "";
        $queryParams = [];
        if ($projectId) {
            $whereClause = "j.project_id = ?";
            $queryParams[] = $projectId;
        } else {
            $whereClause = "(j.folder_id = ? OR j.project_id IN (SELECT id FROM projects WHERE folder_id = ?))";
            $queryParams[] = $folderId;
            $queryParams[] = $folderId;
        }

        // Visibility-Matrix Filter:
        // - only_me: Creator (or superadmin)
        // - group: current_user in user_group_members for allowed_group_id (or superadmin)
        // - company: same company_id
        // - all: project read access
        // Creator always has access to own entries
        $stmt = $db->prepare("
            SELECT j.*, 
                   COALESCE(u.name, 'Unbekannt') as author_name,
                   u.email as author_email,
                   t.title as task_title,
                   p.title as project_title
            FROM project_journals j
            LEFT JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
            LEFT JOIN tasks t ON t.id = j.task_id
            LEFT JOIN projects p ON p.id = j.project_id
            WHERE $whereClause
              AND (
                ? = 1
                OR COALESCE(j.user_id, j.author_id) = ?
                OR j.visibility = 'all'
                OR (j.visibility = 'company' AND j.company_id = ? AND ? != '')
                OR (j.visibility = 'group' AND j.allowed_group_id IS NOT NULL AND EXISTS (
                    SELECT 1 FROM user_group_members ugm 
                    WHERE ugm.group_id = j.allowed_group_id AND ugm.user_id = ?
                ))
              )
            ORDER BY j.created_at DESC
        ");
        $allParams = array_merge($queryParams, [$isSuperadmin, $userId, $companyId, $companyId, $userId]);
        $stmt->execute($allParams);
        $rawEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $entries = [];
        if (!empty($rawEntries)) {
            $journalIds = array_column($rawEntries, 'id');
            $inClause = implode(',', array_fill(0, count($journalIds), '?'));

            // Attachments
            $attStmt = $db->prepare("SELECT * FROM project_journal_attachments WHERE journal_id IN ($inClause) ORDER BY created_at ASC");
            $attStmt->execute($journalIds);
            $allAtts = $attStmt->fetchAll(PDO::FETCH_ASSOC);
            $attachmentsByJournal = [];
            foreach ($allAtts as $att) {
                $attachmentsByJournal[$att['journal_id']][] = $att;
            }

            // Attendees
            $atdStmt = $db->prepare("SELECT * FROM project_journal_attendees WHERE journal_id IN ($inClause) ORDER BY id ASC");
            $atdStmt->execute($journalIds);
            $allAtds = $atdStmt->fetchAll(PDO::FETCH_ASSOC);
            $attendeesByJournal = [];
            foreach ($allAtds as $atd) {
                $atd['present'] = (bool)$atd['present'];
                $attendeesByJournal[$atd['journal_id']][] = $atd;
            }

            foreach ($rawEntries as $e) {
                $e['metadata'] = !empty($e['metadata']) ? (is_string($e['metadata']) ? json_decode($e['metadata'], true) : $e['metadata']) : [];
                $e['attachments'] = $attachmentsByJournal[$e['id']] ?? [];
                $e['attendees'] = $attendeesByJournal[$e['id']] ?? [];
                $entries[] = $e;
            }
        }

        jsonResponse(['entries' => $entries]);
    }

    // 15b. Legacy GET journals without project_id (User's own recent entries)
    if ($path === 'journals' && $method === 'GET' && empty($_GET['project_id']) && empty($_GET['folder_id'])) {
        $user = requireAuth();
        $stmt = $db->prepare("
            SELECT j.*, u.name as author_name, t.title as task_title, p.title as project_title
            FROM project_journals j
            JOIN users u ON u.id = COALESCE(j.user_id, j.author_id)
            LEFT JOIN tasks t ON t.id = j.task_id
            LEFT JOIN projects p ON p.id = j.project_id
            WHERE COALESCE(j.user_id, j.author_id) = ?
            ORDER BY j.created_at DESC
            LIMIT 100
        ");
        $stmt->execute([$user['id']]);
        $entries = array_map(function($e) {
            $e['metadata'] = !empty($e['metadata']) ? (is_string($e['metadata']) ? json_decode($e['metadata'], true) : $e['metadata']) : [];
            $e['attachments'] = [];
            $e['attendees'] = [];
            return $e;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
        jsonResponse(['entries' => $entries]);
    }

    // 16. POST projects/:id/journal & POST journals
    if ((preg_match('#^projects/([^/]+)/journal$#', $path, $m) || $path === 'journals') && $method === 'POST') {
        $user = requireAuth();
        $folderId = !empty($body['folder_id']) ? trim($body['folder_id']) : null;
        $projectId = !empty($m[1]) ? $m[1] : ($body['project_id'] ?? '');
        $title = trim($body['title'] ?? '');
        $content = trim($body['content'] ?? '');
        $type = in_array($body['type'] ?? '', ['entry', 'note'], true) ? $body['type'] : 'entry';
        $category = trim($body['category'] ?? 'allgemein');
        $visibility = in_array($body['visibility'] ?? '', ['only_me', 'group', 'company', 'all'], true) ? $body['visibility'] : 'all';
        $allowedGroupId = !empty($body['allowed_group_id']) ? $body['allowed_group_id'] : null;
        $taskId = !empty($body['task_id']) ? $body['task_id'] : null;
        $metadata = $body['metadata'] ?? [];
        $attachments = is_array($body['attachments'] ?? null) ? $body['attachments'] : [];
        $attendees = is_array($body['attendees'] ?? null) ? $body['attendees'] : [];

        if (empty($title) || empty($content)) {
            errorResponse('Titel und Inhalt sind erforderlich', 400);
        }

        // Auto assignment if projectId is empty or 'auto':
        if ((empty($projectId) || $projectId === 'auto') && $folderId) {
            $pStmt = $db->prepare("SELECT id, title, custom_data FROM projects WHERE folder_id = ?");
            $pStmt->execute([$folderId]);
            $folderProjects = $pStmt->fetchAll(PDO::FETCH_ASSOC);
            $fullText = mb_strtolower($title . ' ' . $content);
            $matchedPrjId = null;
            foreach ($folderProjects as $fp) {
                $t = mb_strtolower(trim($fp['title']));
                if ($t !== '' && mb_strpos($fullText, $t) !== false) {
                    $matchedPrjId = $fp['id'];
                    break;
                }
                if (!empty($fp['custom_data'])) {
                    $cd = is_string($fp['custom_data']) ? json_decode($fp['custom_data'], true) : $fp['custom_data'];
                    if (is_array($cd)) {
                        foreach ($cd as $val) {
                            $vStr = mb_strtolower(trim((string)$val));
                            if (mb_strlen($vStr) >= 3 && mb_strpos($fullText, $vStr) !== false) {
                                $matchedPrjId = $fp['id'];
                                break 2;
                            }
                        }
                    }
                }
            }
            if ($matchedPrjId) {
                $projectId = $matchedPrjId;
            } else {
                $defP = $db->prepare("SELECT id FROM projects WHERE folder_id = ? ORDER BY is_default DESC, created_at ASC LIMIT 1");
                $defP->execute([$folderId]);
                $projectId = $defP->fetchColumn() ?: null;
            }
        }

        if (empty($projectId) && empty($folderId)) {
            $fStmt = $db->prepare("
                SELECT p.id, p.folder_id FROM projects p
                JOIN project_folders pf ON pf.id = p.folder_id
                WHERE pf.company_id = ? OR pf.owner_id = ?
                ORDER BY p.is_default DESC, p.created_at ASC
                LIMIT 1
            ");
            $fStmt->execute([$user['company_id'] ?? '', $user['id']]);
            $pRow = $fStmt->fetch();
            if ($pRow) {
                $projectId = $pRow['id'];
                $folderId = $pRow['folder_id'];
            } else {
                $anyPrj = $db->query("SELECT id, folder_id FROM projects LIMIT 1")->fetch();
                if ($anyPrj) {
                    $projectId = $anyPrj['id'];
                    $folderId = $anyPrj['folder_id'];
                } else {
                    errorResponse('Projekt nicht gefunden', 404);
                }
            }
        }

        if ($projectId) {
            evaluateProjectAccess($user, $projectId, 'write');
            if (!$folderId) {
                $fSt = $db->prepare("SELECT folder_id FROM projects WHERE id = ?");
                $fSt->execute([$projectId]);
                $folderId = $fSt->fetchColumn() ?: null;
            }
        } else if ($folderId) {
            $fCheckStmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
            $fCheckStmt->execute([$folderId]);
            $fld = $fCheckStmt->fetch();
            if (!$fld) errorResponse('Ordner nicht gefunden', 404);
        }

        $jrnId = 'jrn_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $metaJson = is_array($metadata) ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : (is_string($metadata) ? $metadata : '{}');

        $db->prepare("
            INSERT INTO project_journals (id, company_id, folder_id, project_id, user_id, author_id, task_id, type, category, entry_type, title, content, visibility, allowed_group_id, metadata, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([
            $jrnId,
            $user['company_id'] ?? null,
            $folderId,
            $projectId,
            $user['id'],
            $user['id'],
            $taskId,
            $type,
            $category,
            $type === 'note' ? 'note' : 'manual',
            $title,
            $content,
            $visibility,
            $allowedGroupId,
            $metaJson
        ]);

        // Insert attachments
        $savedAttachments = [];
        if (!empty($attachments)) {
            $attInsert = $db->prepare("
                INSERT INTO project_journal_attachments (id, journal_id, file_name, file_path, file_type, file_size, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            foreach ($attachments as $att) {
                if (!empty($att['file_name']) && !empty($att['file_path'])) {
                    $attId = 'pja_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $attInsert->execute([
                        $attId,
                        $jrnId,
                        $att['file_name'],
                        $att['file_path'],
                        $att['file_type'] ?? 'application/octet-stream',
                        (int)($att['file_size'] ?? 0)
                    ]);
                    $savedAttachments[] = [
                        'id' => $attId,
                        'journal_id' => $jrnId,
                        'file_name' => $att['file_name'],
                        'file_path' => $att['file_path'],
                        'file_type' => $att['file_type'] ?? 'application/octet-stream',
                        'file_size' => (int)($att['file_size'] ?? 0),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        // Insert attendees (only if type === 'entry')
        $savedAttendees = [];
        if ($type === 'entry' && !empty($attendees)) {
            $atdInsert = $db->prepare("
                INSERT INTO project_journal_attendees (id, journal_id, contact_id, name, email, role, present)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            foreach ($attendees as $atd) {
                if (!empty($atd['name'])) {
                    $atdId = 'pjat_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $presentVal = (!isset($atd['present']) || $atd['present'] === true || $atd['present'] === 1 || $atd['present'] === '1') ? 1 : 0;
                    $atdInsert->execute([
                        $atdId,
                        $jrnId,
                        !empty($atd['contact_id']) ? $atd['contact_id'] : null,
                        trim($atd['name']),
                        !empty($atd['email']) ? trim($atd['email']) : null,
                        !empty($atd['role']) ? trim($atd['role']) : null,
                        $presentVal
                    ]);
                    $savedAttendees[] = [
                        'id' => $atdId,
                        'journal_id' => $jrnId,
                        'contact_id' => $atd['contact_id'] ?? null,
                        'name' => trim($atd['name']),
                        'email' => $atd['email'] ?? null,
                        'role' => $atd['role'] ?? null,
                        'present' => (bool)$presentVal
                    ];
                }
            }
        }

        jsonResponse([
            'success' => true,
            'entry' => [
                'id' => $jrnId,
                'company_id' => $user['company_id'] ?? null,
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'author_name' => $user['name'],
                'author_email' => $user['email'],
                'type' => $type,
                'category' => $category,
                'title' => $title,
                'content' => $content,
                'visibility' => $visibility,
                'allowed_group_id' => $allowedGroupId,
                'metadata' => is_array($metadata) ? $metadata : json_decode($metaJson, true),
                'attachments' => $savedAttachments,
                'attendees' => $savedAttendees,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    // 16b. POST projects/:id/journal/parse-email (E-Mail Ingestion & KI Pipeline)
    if (preg_match('#^projects/([^/]+)/journal/parse-email$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $projectId = $m[1];
        evaluateProjectAccess($user, $projectId, 'write');

        $emailText = trim($body['email_text'] ?? $body['content'] ?? '');
        if (empty($emailText)) {
            errorResponse('E-Mail-Text erforderlich', 400);
        }

        $sender = is_array($body['sender'] ?? null) ? $body['sender'] : [];
        $recipients = is_array($body['recipients'] ?? null) ? $body['recipients'] : [];
        $emailSubject = trim($body['subject'] ?? '');
        $attachments = is_array($body['attachments'] ?? null) ? $body['attachments'] : [];

        // Automatische Erkennung & Dekodierung von MIME-Multipart, Base64 oder Quoted-Printable
        if (stripos($emailText, 'Content-Transfer-Encoding') !== false || stripos($emailText, 'Content-Type:') !== false || preg_match('/^--[a-zA-Z0-9_-]+/m', $emailText)) {
            $parsedMime = parseMimeEmailText($emailText);
            if (!empty($parsedMime['body'])) {
                $emailText = $parsedMime['body'];
            }
            if (empty($emailSubject) && !empty($parsedMime['subject'])) {
                $emailSubject = $parsedMime['subject'];
            }
            if (empty($sender['email']) && !empty($parsedMime['sender_email'])) {
                $sender['email'] = $parsedMime['sender_email'];
                if (empty($sender['name']) && !empty($parsedMime['sender_name'])) {
                    $sender['name'] = $parsedMime['sender_name'];
                }
            }
        }

        // 1. Kontaktprüfung & automatische Anlage falls nicht vorhanden
        $senderEmail = trim($sender['email'] ?? '');
        $senderName = trim($sender['name'] ?? '');
        $senderRole = trim($sender['role'] ?? 'E-Mail Kontakt');
        $senderCompany = trim($sender['company'] ?? '');

        // Versuche Absender & Betreff aus E-Mail-Text zu extrahieren, falls nicht separat übergeben
        if (empty($senderEmail) && preg_match('/(?:From|Von):\s*(?:([^<\r\n]+)\s*<)?([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})>?/i', $emailText, $fromMatch)) {
            if (!empty($fromMatch[1])) $senderName = trim($fromMatch[1], " \"'");
            $senderEmail = strtolower(trim($fromMatch[2]));
        }
        if (empty($emailSubject) && preg_match('/(?:Subject|Betreff):\s*(.+?)(?:\r?\n|$)/i', $emailText, $subMatch)) {
            $emailSubject = trim($subMatch[1]);
        }

        // Kontakt in contacts prüfen/erstellen
        if (!empty($senderEmail)) {
            $companyId = $user['company_id'] ?? null;
            $chkContact = $db->prepare("
                SELECT id FROM contacts 
                WHERE LOWER(email) = LOWER(?) 
                  AND (company_id = ? OR (company_id IS NULL AND user_id = ?))
                LIMIT 1
            ");
            $chkContact->execute([$senderEmail, $companyId, $user['id']]);
            $contactExists = $chkContact->fetchColumn();

            if (!$contactExists) {
                $newContactId = 'cnt_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $parts = preg_split('/\s+/', $senderName, 2);
                $firstName = count($parts) > 1 ? $parts[0] : '';
                $lastName = count($parts) > 1 ? $parts[1] : ($parts[0] ?: $senderEmail);

                $db->prepare("
                    INSERT INTO contacts (id, user_id, company_id, project_id, first_name, last_name, company_name, role_function, email, category_group, share_scope, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Sonstige', ?, NOW())
                ")->execute([
                    $newContactId,
                    $user['id'],
                    $companyId,
                    $projectId,
                    $firstName,
                    $lastName,
                    $senderCompany,
                    $senderRole,
                    $senderEmail,
                    !empty($companyId) ? 'company' : 'private'
                ]);
            }
        }

        // 2. Projektkontext & verknüpfte Aufgabe laden
        $linkedTaskId = !empty($body['task_id']) ? $body['task_id'] : null;
        $targetJournalId = !empty($body['journal_id']) ? $body['journal_id'] : (!empty($body['entry_id']) ? $body['entry_id'] : null);
        if ($targetJournalId && empty($linkedTaskId)) {
            $jCheckTask = $db->prepare("SELECT task_id FROM project_journals WHERE id = ? AND project_id = ?");
            $jCheckTask->execute([$targetJournalId, $projectId]);
            $foundTaskId = $jCheckTask->fetchColumn();
            if (!empty($foundTaskId)) {
                $linkedTaskId = $foundTaskId;
            }
        }

        $lStmt = $db->prepare("SELECT id, title FROM lists WHERE project_id = ? ORDER BY sort_order ASC");
        $lStmt->execute([$projectId]);
        $sections = $lStmt->fetchAll(PDO::FETCH_ASSOC);

        $tStmt = $db->prepare("SELECT t.id, t.list_id, t.title, t.description, t.status, t.due_date, t.custom_data FROM tasks t JOIN lists l ON l.id = t.list_id WHERE l.project_id = ?");
        $tStmt->execute([$projectId]);
        $existingTasks = $tStmt->fetchAll(PDO::FETCH_ASSOC);

        // Intelligente automatische Aufgabenerkennung anhand Adresse, Name, Kundennummer oder Feldern
        if (!$linkedTaskId && !empty($existingTasks)) {
            $combinedText = strtolower(($emailSubject ?: '') . ' ' . ($emailText ?: ''));
            $bestTaskId = null;
            $bestScore = 0;

            foreach ($existingTasks as $t) {
                $score = 0;
                $tTitle = strtolower(trim($t['title'] ?? ''));
                if (!empty($tTitle)) {
                    if (str_contains($combinedText, $tTitle)) {
                        $score += 50;
                    } else {
                        $tokens = preg_split('/[\s\-_,\.\/]+/', $tTitle);
                        $matchCount = 0;
                        foreach ($tokens as $tok) {
                            $tok = trim($tok);
                            if (strlen($tok) >= 4 && str_contains($combinedText, $tok)) {
                                $matchCount++;
                            }
                        }
                        if ($matchCount >= 2) {
                            $score += $matchCount * 15;
                        }
                    }
                }

                if (!empty($t['custom_data'])) {
                    $cd = is_string($t['custom_data']) ? json_decode($t['custom_data'], true) : $t['custom_data'];
                    if (is_array($cd)) {
                        foreach ($cd as $v) {
                            if ($v !== null && (is_string($v) || is_numeric($v))) {
                                $valStr = strtolower(trim((string)$v));
                                if (strlen($valStr) >= 3 && str_contains($combinedText, $valStr)) {
                                    $score += 35;
                                }
                            }
                        }
                    }
                }

                if ($score > $bestScore && $score >= 30) {
                    $bestScore = $score;
                    $bestTaskId = $t['id'];
                }
            }

            if ($bestTaskId) {
                $linkedTaskId = $bestTaskId;
                if ($targetJournalId) {
                    try {
                        $db->prepare("UPDATE project_journals SET task_id = ? WHERE id = ? AND project_id = ?")->execute([$linkedTaskId, $targetJournalId, $projectId]);
                    } catch (Exception $e) {}
                }
            }
        }

        $linkedTask = null;
        if ($linkedTaskId) {
            $ltStmt = $db->prepare("SELECT t.id, t.list_id, t.title, t.description, t.status, t.due_date, l.title as list_title FROM tasks t LEFT JOIN lists l ON l.id = t.list_id WHERE t.id = ?");
            $ltStmt->execute([$linkedTaskId]);
            $linkedTask = $ltStmt->fetch(PDO::FETCH_ASSOC);
        }

        $sectionsContext = json_encode(array_map(function($s) {
            return ['id' => $s['id'], 'title' => $s['title']];
        }, $sections), JSON_UNESCAPED_UNICODE);

        $tasksContext = json_encode(array_map(function($t) {
            return [
                'id' => $t['id'],
                'section_id' => $t['list_id'],
                'title' => $t['title'],
                'description' => !empty($t['description']) ? substr($t['description'], 0, 100) : '',
                'status' => $t['status'],
                'due_date' => $t['due_date']
            ];
        }, $existingTasks), JSON_UNESCAPED_UNICODE);

        $linkedTaskContext = "";
        if ($linkedTask) {
            $linkedTaskContext = "DIREKT VERKNÜPFTE AUFGABE (HÖCHSTE PRIORITÄT / HAUPTFOKUS):\n"
                               . json_encode([
                                   'id' => $linkedTask['id'],
                                   'section' => $linkedTask['list_title'] ?? $linkedTask['list_id'],
                                   'title' => $linkedTask['title'],
                                   'description' => $linkedTask['description'],
                                   'status' => $linkedTask['status'],
                                   'due_date' => $linkedTask['due_date']
                               ], JSON_UNESCAPED_UNICODE) . "\n\n";
        }

        // 3. KI-Verarbeitung (OpenRouter / DeepSeek Engine)
        $systemPrompt = "Du bist ein intelligenter technischer Bauleiter-Assistent im System Taskster.\n"
                      . "Analysiere den Inhalt des Journaleintrags, Protokolls oder der Mitteilung präzise im Kontext des Bauprojekts und generiere ein valides JSON-Objekt.\n"
                      . "WICHTIGE REGELN:\n"
                      . "1. summary: Sachliche, prägnante Zusammenfassung (max. 2-3 Sätze). Beschreibe neutral den baulichen/projektbezogenen Sachverhalt.\n"
                      . "2. VERKNÜPFTE AUFGABE (HÖCHSTE PRIORITÄT):\n"
                      . "   Falls dieser Journaleintrag mit einer bestehenden Aufgabe verknüpft ist (siehe 'DIREKT VERKNÜPFTE AUFGABE'):\n"
                      . "   - Dieser Eintrag bezieht sich PRIMÄR auf genau diese verknüpfte Aufgabe!\n"
                      . "   - Falls der Text die Erledigung, den Abschluss oder die Fertigstellung beschreibt (z.B. 'ersetzt', 'erledigt', 'kann abgeschlossen werden', 'fertiggestellt', 'in Betrieb', 'abgenommen', 'fertig'):\n"
                      . "     -> Erzeuge zwingend ein 'complete_task' für diese verknüpfte Aufgabe (task_id: ID der verknüpften Aufgabe)!\n"
                      . "     -> Erstelle in diesem Fall KEINE neue Aufgabe (create_task), sondern schliesse die verknüpfte Aufgabe ab!\n"
                      . "   - Falls der Text Terminverschiebungen, Statusänderungen oder Details beschreibt:\n"
                      . "     -> Erzeuge ein 'update_task' für diese verknüpfte Aufgabe.\n"
                      . "3. ALLGEMEINE REGELN FÜR action_items:\n"
                      . "   - type 'complete_task': 'task_id' (insb. die verknüpfte Aufgabe), 'reason': Grund für Abschluss.\n"
                      . "   - type 'update_task': 'task_id', 'suggested_status', 'suggested_due_date', 'reason'.\n"
                      . "   - type 'create_task': Nur falls KEINE passende bestehende/verknüpfte Aufgabe existiert und ein neuer Arbeitsschritt angelegt werden muss.\n"
                      . "Gib AUSSCHLIESSLICH das JSON-Objekt zurück, ohne Markdown-Codeblock oder sonstige Erklärungen.";

        $userPrompt = ($linkedTaskContext ? "$linkedTaskContext" : "")
                    . "PROJEKT-ABSCHNITTE (SECTIONS):\n$sectionsContext\n\n"
                    . "BESTEHENDE AUFGABEN (TASKS):\n$tasksContext\n\n"
                    . "EINTRAGSTEXT:\n\"\"\"\n$emailText\n\"\"\"\n\n"
                    . "Erzeuge das JSON im folgenden Format:\n"
                    . "{\n"
                    . '  "subject": "Treffender Titel",' . "\n"
                    . '  "summary": "Zusammenfassung in 2-3 Sätzen",' . "\n"
                    . '  "action_items": [' . "\n"
                    . '    { "type": "complete_task", "task_id": "task_id", "reason": "Abschlussgrund" },' . "\n"
                    . '    { "type": "update_task", "task_id": "task_id", "suggested_status": "in_progress", "suggested_due_date": null, "reason": "Begründung" },' . "\n"
                    . '    { "type": "create_task", "title": "Aufgabentitel", "section_id": "section_id", "priority": "normal", "due_date": null, "description": "Details" }' . "\n"
                    . "  ]\n"
                    . "}";

        $aiResult = null;
        try {
            $rawAi = callOpenRouter([
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt]
            ], ['temperature' => 0.2]);

            $aiText = is_array($rawAi) ? ($rawAi['text'] ?? '') : (string)$rawAi;
            $cleanAi = trim($aiText);
            $cleanAi = preg_replace('/^```(?:json)?\s*/i', '', $cleanAi);
            $cleanAi = preg_replace('/```$/', '', $cleanAi);
            $cleanAi = trim($cleanAi);

            $aiResult = json_decode($cleanAi, true);
        } catch (Throwable $aiEx) {
            // Fallback falls KI nicht erreichbar
            $aiResult = [
                'subject' => !empty($emailSubject) ? $emailSubject : 'E-Mail Import',
                'summary' => substr(strip_tags($emailText), 0, 200) . '...',
                'action_items' => []
            ];
        }

        $finalTitle = !empty($emailSubject) ? $emailSubject : (!empty($aiResult['subject']) ? $aiResult['subject'] : 'E-Mail Notiz');
        $summary = $aiResult['summary'] ?? '';
        $actionItems = is_array($aiResult['action_items'] ?? null) ? $aiResult['action_items'] : [];

        $targetJournalId = !empty($body['journal_id']) ? $body['journal_id'] : (!empty($body['entry_id']) ? $body['entry_id'] : null);
        if ($targetJournalId) {
            $jCheck = $db->prepare("SELECT metadata FROM project_journals WHERE id = ? AND project_id = ?");
            $jCheck->execute([$targetJournalId, $projectId]);
            $existingMetaStr = $jCheck->fetchColumn();
            $existingMeta = !empty($existingMetaStr) ? (is_string($existingMetaStr) ? json_decode($existingMetaStr, true) : $existingMetaStr) : [];
            if (!is_array($existingMeta)) $existingMeta = [];

            $existingMeta['ai_summary'] = $summary;
            $existingMeta['action_items'] = $actionItems;
            if (!empty($senderEmail)) {
                $existingMeta['sender'] = [
                    'name' => $senderName,
                    'email' => $senderEmail,
                    'role' => $senderRole
                ];
            }
            $metaJson = json_encode($existingMeta, JSON_UNESCAPED_UNICODE);
            $db->prepare("UPDATE project_journals SET metadata = ?, updated_at = NOW() WHERE id = ? AND project_id = ?")
               ->execute([$metaJson, $targetJournalId, $projectId]);

            jsonResponse([
                'success' => true,
                'metadata' => $existingMeta,
                'summary' => $summary,
                'action_items' => $actionItems
            ]);
        }

        // 4. Persistierung als Notiz (type = note)
        $jrnId = 'jrn_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $targetCategory = !empty($body['category']) ? $body['category'] : 'email';
        $targetVisibility = in_array($body['visibility'] ?? '', ['only_me', 'group', 'company', 'all'], true) ? $body['visibility'] : 'all';
        $targetAllowedGroup = !empty($body['allowed_group_id']) ? $body['allowed_group_id'] : null;

        $metadata = [
            'sender' => [
                'name' => $senderName,
                'email' => $senderEmail,
                'role' => $senderRole
            ],
            'recipients' => $recipients,
            'ai_summary' => $summary,
            'action_items' => $actionItems,
            'raw_subject' => $emailSubject
        ];

        $metaJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);

        $db->prepare("
            INSERT INTO project_journals (id, company_id, project_id, user_id, author_id, type, category, entry_type, title, content, visibility, allowed_group_id, metadata, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'note', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ")->execute([
            $jrnId,
            $user['company_id'] ?? null,
            $projectId,
            $user['id'],
            $user['id'],
            $targetCategory,
            $targetCategory === 'email' ? 'email' : 'note',
            $finalTitle,
            $emailText,
            $targetVisibility,
            $targetAllowedGroup,
            $metaJson
        ]);

        // Attachments speichern
        $savedAttachments = [];
        if (!empty($attachments)) {
            $attInsert = $db->prepare("
                INSERT INTO project_journal_attachments (id, journal_id, file_name, file_path, file_type, file_size, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            foreach ($attachments as $att) {
                if (!empty($att['file_name']) && !empty($att['file_path'])) {
                    $attId = 'pja_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $attInsert->execute([
                        $attId,
                        $jrnId,
                        $att['file_name'],
                        $att['file_path'],
                        $att['file_type'] ?? 'application/octet-stream',
                        (int)($att['file_size'] ?? 0)
                    ]);
                    $savedAttachments[] = [
                        'id' => $attId,
                        'journal_id' => $jrnId,
                        'file_name' => $att['file_name'],
                        'file_path' => $att['file_path'],
                        'file_type' => $att['file_type'] ?? 'application/octet-stream',
                        'file_size' => (int)($att['file_size'] ?? 0),
                        'created_at' => date('Y-m-d H:i:s')
                    ];
                }
            }
        }

        jsonResponse([
            'success' => true,
            'entry' => [
                'id' => $jrnId,
                'company_id' => $user['company_id'] ?? null,
                'project_id' => $projectId,
                'user_id' => $user['id'],
                'author_name' => $user['name'],
                'author_email' => $user['email'],
                'type' => 'note',
                'category' => 'email',
                'title' => $finalTitle,
                'content' => $emailText,
                'visibility' => 'all',
                'metadata' => $metadata,
                'attachments' => $savedAttachments,
                'attendees' => [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    // 16c. PUT projects/:id/journal/:journalId
    if (preg_match('#^projects/([^/]+)/journal/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $projectId = $m[1];
        $journalId = $m[2];
        evaluateProjectAccess($user, $projectId, 'write');

        $jStmt = $db->prepare("SELECT * FROM project_journals WHERE id = ? AND project_id = ?");
        $jStmt->execute([$journalId, $projectId]);
        $existing = $jStmt->fetch();
        if (!$existing) errorResponse('Journaleintrag nicht gefunden', 404);

        $title = isset($body['title']) ? trim($body['title']) : $existing['title'];
        $content = isset($body['content']) ? trim($body['content']) : $existing['content'];
        $category = isset($body['category']) ? trim($body['category']) : $existing['category'];
        $visibility = isset($body['visibility']) ? $body['visibility'] : $existing['visibility'];
        $allowedGroupId = array_key_exists('allowed_group_id', $body) ? $body['allowed_group_id'] : $existing['allowed_group_id'];
        $taskId = array_key_exists('task_id', $body) ? (!empty($body['task_id']) ? trim($body['task_id']) : null) : $existing['task_id'];
        
        $metaJson = $existing['metadata'];
        if (isset($body['metadata'])) {
            $metaJson = is_array($body['metadata']) ? json_encode($body['metadata'], JSON_UNESCAPED_UNICODE) : $body['metadata'];
        }

        $db->prepare("
            UPDATE project_journals
            SET title = ?, content = ?, category = ?, visibility = ?, allowed_group_id = ?, task_id = ?, metadata = ?, updated_at = NOW()
            WHERE id = ? AND project_id = ?
        ")->execute([$title, $content, $category, $visibility, $allowedGroupId, $taskId, $metaJson, $journalId, $projectId]);

        jsonResponse(['success' => true]);
    }

    // 16d. DELETE projects/:id/journal/:journalId
    if (preg_match('#^projects/([^/]+)/journal/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $projectId = $m[1];
        $journalId = $m[2];
        evaluateProjectAccess($user, $projectId, 'write');

        try { $db->prepare("DELETE FROM project_journal_attachments WHERE journal_id = ?")->execute([$journalId]); } catch (Exception $e) {}
        try { $db->prepare("DELETE FROM project_journal_attendees WHERE journal_id = ?")->execute([$journalId]); } catch (Exception $e) {}
        $db->prepare("DELETE FROM project_journals WHERE id = ? AND project_id = ?")->execute([$journalId, $projectId]);

        jsonResponse(['success' => true]);
    }

    // --- COMPANY INVITATIONS & MEMBERS ENDPOINTS ---

    // 16a. POST companies/members (Company Admin invites user or assigns directly)
    if ($path === 'companies/members' && $method === 'POST') {
        $user = requireAuth();
        $email = strtolower(trim($body['email'] ?? ''));
        $role = $body['role'] ?? 'member';
        $licenseType = in_array($body['license_type'] ?? '', ['pro', 'enterprise']) ? $body['license_type'] : 'pro';
        if ($role === 'admin') {
            $licenseType = 'enterprise';
        }

        if (!$email) errorResponse('E-Mail erforderlich', 400);

        // Must be company admin or superadmin
        if (empty($user['is_superadmin']) && (empty($user['company_id']) || $user['company_role'] !== 'admin')) {
            errorResponse('Nur Company-Admins dürfen Mitarbeiter einladen', 403);
        }

        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        // Check if user already exists
        $uStmt = $db->prepare("SELECT id, email, name FROM users WHERE LOWER(email) = ?");
        $uStmt->execute([$email]);
        $existing = $uStmt->fetch();

        if ($existing) {
            // Already registered -> direct join!
            $db->prepare("UPDATE users SET company_id = ?, company_role = ?, is_pro = 1 WHERE id = ?")->execute([
                $companyId, $role, $existing['id']
            ]);

            try {
                $cmId = 'cm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $db->prepare("
                    INSERT INTO company_memberships (id, company_id, user_id, role, license_type, status)
                    VALUES (?, ?, ?, ?, ?, 'active')
                    ON DUPLICATE KEY UPDATE role = VALUES(role), license_type = VALUES(license_type), status = 'active'
                ")->execute([$cmId, $companyId, $existing['id'], $role, $licenseType]);
            } catch (Exception $e) {}

            jsonResponse([
                'success' => true,
                'action' => 'added',
                'user' => ['id' => $existing['id'], 'email' => $existing['email'], 'name' => $existing['name'], 'license_type' => $licenseType]
            ]);
        } else {
            // Not registered -> create pending invitation with token
            $token = bin2hex(random_bytes(24));
            $invId = 'inv_' . substr(bin2hex(random_bytes(6)), 0, 8);

            // Invalidate existing pending invites for this email & company
            $db->prepare("DELETE FROM company_invitations WHERE company_id = ? AND LOWER(email) = ?")->execute([$companyId, $email]);

            $db->prepare("INSERT INTO company_invitations (id, company_id, email, role, license_type, token, invited_by, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')")->execute([
                $invId, $companyId, $email, $role, $licenseType, $token, $user['id']
            ]);

            jsonResponse([
                'success' => true,
                'action' => 'invited',
                'token' => $token,
                'email' => $email,
                'license_type' => $licenseType
            ]);
        }
    }

    // 16d. GET companies/members (List members of company for Company Admin)
    if ($path === 'companies/members' && $method === 'GET') {
        $user = requireAuth();
        $companyId = $user['company_id'] ?? null;
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $_GET['company_id'] ?? null;
        }
        if (!$companyId) {
            jsonResponse(['members' => [], 'calculation' => null]);
        }

        $stmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.company_role, u.is_pro, u.created_at,
                   COALESCE(cm.license_type, CASE WHEN u.company_role = 'admin' THEN 'enterprise' ELSE 'pro' END) as license_type
            FROM users u
            LEFT JOIN company_memberships cm ON cm.user_id = u.id AND cm.company_id = u.company_id
            WHERE u.company_id = ?
            ORDER BY (u.company_role = 'admin') DESC, u.name ASC
        ");
        $stmt->execute([$companyId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $adminCount = 0;
        $proCount = 0;
        $enterpriseCount = 0;
        foreach ($members as $m) {
            if ($m['company_role'] === 'admin') {
                $adminCount++;
            } else {
                if (($m['license_type'] ?? 'pro') === 'enterprise') {
                    $enterpriseCount++;
                } else {
                    $proCount++;
                }
            }
        }
        $monthlyTotal = ($adminCount * 19) + ($proCount * 8) + ($enterpriseCount * 15);

        jsonResponse([
            'members' => $members,
            'calculation' => [
                'admin_seats' => $adminCount,
                'admin_rate' => 19,
                'admin_total' => $adminCount * 19,
                'pro_seats' => $proCount,
                'pro_rate' => 8,
                'pro_total' => $proCount * 8,
                'enterprise_seats' => $enterpriseCount,
                'enterprise_rate' => 15,
                'enterprise_total' => $enterpriseCount * 15,
                'monthly_total' => $monthlyTotal,
                'currency' => 'EUR'
            ]
        ]);
    }

    // 16b. GET companies/invitations (List pending invitations for current company)
    if ($path === 'companies/invitations' && $method === 'GET') {
        $user = requireAuth();
        $companyId = $user['company_id'];
        if (!$companyId && empty($user['is_superadmin'])) errorResponse('Keine Company', 400);

        $stmt = $db->prepare("SELECT id, email, role, token, status, created_at FROM company_invitations WHERE company_id = ? ORDER BY created_at DESC");
        $stmt->execute([$companyId]);
        jsonResponse(['invitations' => $stmt->fetchAll()]);
    }

    // 16c. GET companies/invitations/info (Public token check for registration mask)
    if ($path === 'companies/invitations/info' && $method === 'GET') {
        $token = trim($_GET['token'] ?? '');
        if (!$token) errorResponse('Token erforderlich', 400);

        $stmt = $db->prepare("
            SELECT ci.id, ci.email, ci.role, ci.company_id, ci.status, c.name as company_name
            FROM company_invitations ci
            JOIN companies c ON c.id = ci.company_id
            WHERE ci.token = ? AND ci.status = 'pending'
        ");
        $stmt->execute([$token]);
        $inv = $stmt->fetch();
        if (!$inv) errorResponse('Ungültige oder bereits genutzte Einladung', 404);

        jsonResponse(['invitation' => $inv]);
    }

    // ==========================================
    // COMPANY ADMIN PORTAL ENDPOINTS (/company)
    // Nur fuer Firmen-Admins (company_role === 'admin') bzw. Superadmin.
    // ==========================================

    // 16e. GET company/details (Firmendaten, Plan, Statistiken, Anfragen)
    if ($path === 'company/details' && $method === 'GET') {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $_GET['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $cStmt = $db->prepare("SELECT * FROM companies WHERE id = ?");
        $cStmt->execute([$companyId]);
        $company = $cStmt->fetch();
        if (!$company) errorResponse('Unternehmen nicht gefunden', 404);

        $settings = !empty($company['settings']) ? (is_string($company['settings']) ? json_decode($company['settings'], true) : $company['settings']) : [];
        if (!is_array($settings)) $settings = [];

        $mStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE company_id = ?");
        $mStmt->execute([$companyId]);
        $memberCount = (int)$mStmt->fetchColumn();

        $aStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE company_id = ? AND company_role = 'admin'");
        $aStmt->execute([$companyId]);
        $adminCount = (int)$aStmt->fetchColumn();

        $pStmt = $db->prepare("SELECT COUNT(*) FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ?");
        $pStmt->execute([$companyId]);
        $projectCount = (int)$pStmt->fetchColumn();

        $tStmt = $db->prepare("SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id JOIN projects p ON p.id = l.project_id JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ?");
        $tStmt->execute([$companyId]);
        $taskCount = (int)$tStmt->fetchColumn();

        $tmplStmt = $db->prepare("SELECT COUNT(*) FROM project_templates WHERE company_id = ? AND is_system = 0");
        $tmplStmt->execute([$companyId]);
        $templateCount = (int)$tmplStmt->fetchColumn();

        $planPrices = [
            'starter' => ['name' => 'Starter Plan', 'monthly' => 0, 'seats' => 5],
            'pro' => ['name' => 'Pro Business Plan', 'monthly' => 49, 'seats' => 25],
            'enterprise' => ['name' => 'Enterprise Custom Plan', 'monthly' => 189, 'seats' => 100]
        ];
        $planKey = strtolower($company['subscription_plan'] ?? 'starter');
        $pInfo = $planPrices[$planKey] ?? ['name' => ucfirst($planKey), 'monthly' => 29, 'seats' => 10];
        $maxSeats = (int)($settings['max_seats'] ?? $pInfo['seats']);

        jsonResponse([
            'company' => [
                'id' => $company['id'],
                'name' => $company['name'],
                'subscription_plan' => $planKey,
                'plan_name' => $pInfo['name'],
                'plan_monthly' => $pInfo['monthly'],
                'currency' => 'CHF',
                'settings' => $settings,
                'created_at' => $company['created_at'],
                'next_renewal' => date('Y-m-d', strtotime(($company['created_at'] ?? 'now') . ' + 1 month'))
            ],
            'stats' => [
                'members' => $memberCount,
                'admins' => $adminCount,
                'projects' => $projectCount,
                'tasks' => $taskCount,
                'templates' => $templateCount,
                'max_seats' => $maxSeats,
                'seats_used' => $memberCount
            ],
            'upgrade_requests' => array_slice(array_reverse($settings['upgrade_requests'] ?? []), 0, 10),
            'support_tickets' => array_slice(array_reverse($settings['support_tickets'] ?? []), 0, 10)
        ]);
    }

    // 16f. PATCH company/details (Firmenname & Zero-Trust Policies aktualisieren)
    if ($path === 'company/details' && ($method === 'PATCH' || $method === 'PUT')) {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $fields = [];
        $params = [];

        if (isset($body['name'])) {
            $name = trim($body['name']);
            if (!$name) errorResponse('Firmenname darf nicht leer sein', 400);
            $fields[] = "name = ?";
            $params[] = $name;
        }

        if (isset($body['settings']) && is_array($body['settings'])) {
            $cStmt = $db->prepare("SELECT settings FROM companies WHERE id = ?");
            $cStmt->execute([$companyId]);
            $existing = $cStmt->fetchColumn();
            $existingSettings = !empty($existing) ? (is_string($existing) ? json_decode($existing, true) : $existing) : [];
            if (!is_array($existingSettings)) $existingSettings = [];
            $merged = array_merge($existingSettings, $body['settings']);
            $fields[] = "settings = ?";
            $params[] = json_encode($merged);
        }

        if (empty($fields)) errorResponse('Keine Änderungen übergeben', 400);

        $params[] = $companyId;
        $db->prepare("UPDATE companies SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        jsonResponse(['success' => true]);
    }

    // 16g. PATCH company/members/:userId (Co-Admin ernennen / herabstufen)
    if (preg_match('#^company/members/([^/]+)$#', $path, $m) && ($method === 'PATCH' || $method === 'PUT')) {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $targetId = $m[1];
        $tStmt = $db->prepare("SELECT id, name, email, company_role, company_id FROM users WHERE id = ?");
        $tStmt->execute([$targetId]);
        $target = $tStmt->fetch();
        if (!$target || $target['company_id'] !== $companyId) {
            errorResponse('Mitarbeiter nicht gefunden', 404);
        }

        if (!isset($body['role'])) errorResponse('Keine Änderungen übergeben', 400);

        $role = $body['role'] === 'admin' ? 'admin' : 'member';
        if ($target['id'] === $user['id'] && $role !== 'admin') {
            errorResponse('Du kannst dich nicht selbst zum Mitarbeiter herabstufen', 400);
        }

        $db->prepare("UPDATE users SET company_role = ? WHERE id = ?")->execute([$role, $targetId]);
        jsonResponse(['success' => true, 'role' => $role]);
    }

    // 16h. DELETE company/members/:userId (Mitarbeiter aus Unternehmen entfernen)
    if (preg_match('#^company/members/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $_GET['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $targetId = $m[1];
        if ($targetId === $user['id']) errorResponse('Du kannst dich nicht selbst entfernen', 400);

        $tStmt = $db->prepare("SELECT id, company_id FROM users WHERE id = ?");
        $tStmt->execute([$targetId]);
        $target = $tStmt->fetch();
        if (!$target || $target['company_id'] !== $companyId) {
            errorResponse('Mitarbeiter nicht gefunden', 404);
        }

        $db->prepare("UPDATE users SET company_id = NULL, company_role = NULL, is_pro = 0 WHERE id = ?")->execute([$targetId]);
        jsonResponse(['success' => true]);
    }

    // 16i. GET company/templates (Nur firmeneigene Vorlagen)
    if ($path === 'company/templates' && $method === 'GET') {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $_GET['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $stmt = $db->prepare("SELECT * FROM project_templates WHERE company_id = ? AND is_system = 0 ORDER BY category ASC, name ASC");
        $stmt->execute([$companyId]);
        $templates = array_map(function($t) {
            $t['lists'] = !empty($t['lists']) ? (is_string($t['lists']) ? json_decode($t['lists'], true) : $t['lists']) : [];
            $t['fields'] = !empty($t['fields']) ? (is_string($t['fields']) ? json_decode($t['fields'], true) : $t['fields']) : [];
            return $t;
        }, $stmt->fetchAll());

        jsonResponse(['templates' => $templates]);
    }

    // 16j. POST company/upgrade (Plan-Upgrade / Sitzplätze anfordern)
    if ($path === 'company/upgrade' && $method === 'POST') {
        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $requestedPlan = strtolower(trim($body['plan'] ?? ''));
        if (!in_array($requestedPlan, ['starter', 'pro', 'enterprise'])) {
            errorResponse('Ungültiger Plan', 400);
        }
        $requestedSeats = isset($body['seats']) ? (int)$body['seats'] : null;
        $note = trim($body['note'] ?? '');

        $cStmt = $db->prepare("SELECT name, settings FROM companies WHERE id = ?");
        $cStmt->execute([$companyId]);
        $company = $cStmt->fetch();
        if (!$company) errorResponse('Unternehmen nicht gefunden', 404);

        $settings = !empty($company['settings']) ? (is_string($company['settings']) ? json_decode($company['settings'], true) : $company['settings']) : [];
        if (!is_array($settings)) $settings = [];
        $requests = $settings['upgrade_requests'] ?? [];
        $requests[] = [
            'id' => 'req_' . substr(bin2hex(random_bytes(6)), 0, 8),
            'plan' => $requestedPlan,
            'seats' => $requestedSeats,
            'note' => $note,
            'requested_by' => $user['id'],
            'requested_by_name' => $user['name'],
            'requested_at' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ];
        $settings['upgrade_requests'] = $requests;
        $db->prepare("UPDATE companies SET settings = ? WHERE id = ?")->execute([json_encode($settings), $companyId]);

        // Plattform-Admins benachrichtigen
        $saStmt = $db->query("SELECT id FROM users WHERE is_superadmin = 1");
        foreach ($saStmt->fetchAll() as $sa) {
            createNotification($sa['id'], 'system', 'Plan-Upgrade-Anfrage', $company['name'] . ' möchte auf ' . ucfirst($requestedPlan) . ' wechseln.', 'company', $companyId);
        }

        jsonResponse(['success' => true, 'message' => 'Upgrade-Anfrage übermittelt']);
    }

    // 16k. POST company/support (Support-Ticket an Taskster senden)
    if ($path === 'company/support' && $method === 'POST') {        $user = requireCompanyAdmin();
        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $subject = trim($body['subject'] ?? '');
        $message = trim($body['message'] ?? '');
        $priority = in_array($body['priority'] ?? '', ['low', 'normal', 'high', 'urgent']) ? $body['priority'] : 'normal';
        if (!$subject || !$message) errorResponse('Betreff und Nachricht erforderlich', 400);

        $cStmt = $db->prepare("SELECT name, settings FROM companies WHERE id = ?");
        $cStmt->execute([$companyId]);
        $company = $cStmt->fetch();
        if (!$company) errorResponse('Unternehmen nicht gefunden', 404);

        $settings = !empty($company['settings']) ? (is_string($company['settings']) ? json_decode($company['settings'], true) : $company['settings']) : [];
        if (!is_array($settings)) $settings = [];
        $tickets = $settings['support_tickets'] ?? [];
        $ticketId = 'tkt_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $tickets[] = [
            'id' => $ticketId,
            'subject' => $subject,
            'message' => $message,
            'priority' => $priority,
            'created_by' => $user['id'],
            'created_by_name' => $user['name'],
            'created_at' => date('Y-m-d H:i:s'),
            'status' => 'open'
        ];
        $settings['support_tickets'] = $tickets;
        $db->prepare("UPDATE companies SET settings = ? WHERE id = ?")->execute([json_encode($settings), $companyId]);

        $saStmt = $db->query("SELECT id FROM users WHERE is_superadmin = 1");
        foreach ($saStmt->fetchAll() as $sa) {
            createNotification($sa['id'], 'system', 'Support-Anfrage: ' . $subject, $company['name'] . ': ' . substr($message, 0, 120), 'company', $companyId);
        }

        jsonResponse(['success' => true, 'ticket_id' => $ticketId]);
    }

    if ($path === 'project/journals' && $method === 'POST') {
        $user = requireAuth();
        $title = trim($body['title'] ?? '');
        $content = trim($body['content'] ?? '');
        $entryType = $body['entry_type'] ?? 'note';
        $projectId = $body['project_id'] ?? null;

        if (!$title && !$content) {
            errorResponse('Titel oder Inhalt erforderlich', 400);
        }

        if (empty($projectId)) {
            $fStmt = $db->prepare("
                SELECT p.id FROM projects p
                JOIN project_folders pf ON pf.id = p.folder_id
                WHERE pf.company_id = ? OR pf.owner_id = ?
                ORDER BY p.is_default DESC, p.created_at ASC
                LIMIT 1
            ");
            $fStmt->execute([$user['company_id'] ?? '', $user['id']]);
            $projectId = $fStmt->fetchColumn();

            if (empty($projectId)) {
                $anyPrj = $db->query("SELECT id FROM projects LIMIT 1")->fetchColumn();
                if ($anyPrj) {
                    $projectId = $anyPrj;
                } else {
                    $fldId = 'fld_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, visibility) VALUES (?, ?, ?, ?, ?)")->execute([
                        $fldId, $user['id'], $user['company_id'] ?? null, 'Persönliche Notizen', 'private'
                    ]);
                    $projectId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO projects (id, folder_id, title, description, is_default, visibility) VALUES (?, ?, ?, ?, 1, 'private')")->execute([
                        $projectId, $fldId, 'Meine Notizen', 'Notizenablage'
                    ]);
                }
            }
        }

        if (!empty($projectId)) {
            evaluateProjectAccess($user, $projectId, 'write');
        }

        $jrnId = 'jrn_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO project_journals (id, project_id, author_id, entry_type, title, content) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $jrnId, $projectId, $user['id'], $entryType, $title, $content
        ]);

        jsonResponse(['success' => true, 'entry' => ['id' => $jrnId, 'project_id' => $projectId, 'title' => $title, 'content' => $content]]);
    }

    // --- COMPANY INVITATIONS & MEMBERS ENDPOINTS ---

    // 16a. POST companies/members (Company Admin invites user or assigns directly)
    if ($path === 'companies/members' && $method === 'POST') {
        $user = requireAuth();
        $email = strtolower(trim($body['email'] ?? ''));
        $role = $body['role'] ?? 'member';

        if (!$email) errorResponse('E-Mail erforderlich', 400);

        // Must be company admin or superadmin
        if (empty($user['is_superadmin']) && (empty($user['company_id']) || $user['company_role'] !== 'admin')) {
            errorResponse('Nur Company-Admins dürfen Mitarbeiter einladen', 403);
        }

        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $body['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        // Check if user already exists
        $stmt = $db->prepare("SELECT id, email, company_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            if ($existing['company_id'] === $companyId) {
                errorResponse('Dieser Benutzer gehört bereits zu diesem Unternehmen', 409);
            }
            if (!empty($existing['company_id'])) {
                errorResponse('Dieser Benutzer gehört bereits zu einem anderen Unternehmen', 409);
            }
            // Assign directly
            $db->prepare("UPDATE users SET company_id = ?, company_role = ? WHERE id = ?")->execute([$companyId, $role, $existing['id']]);
            jsonResponse(['success' => true, 'action' => 'assigned', 'user_id' => $existing['id']]);
        }

        // Create invitation token
        $invId = 'inv_' . substr(bin2hex(random_bytes(8)), 0, 12);
        $token = bin2hex(random_bytes(24));
        $expiresAt = date('Y-m-d H:i:s', time() + 7 * 86400);

        $db->prepare("
            INSERT INTO company_invitations (id, company_id, email, role, token, invited_by, expires_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([$invId, $companyId, $email, $role, $token, $user['id'], $expiresAt]);

        jsonResponse([
            'success' => true,
            'action' => 'invited',
            'invitation_id' => $invId,
            'invite_token' => $token,
            'invite_link' => '/login?tab=register&token=' . $token
        ]);
    }

    // --- GRUPPENVERWALTUNG (Free & Company) ---
    if ($path === 'groups' && $method === 'GET') {
        $user = requireAuth();
        $params = [$user['id']];
        $sql = "SELECT ug.*, u.name as owner_name, u.email as owner_email FROM user_groups ug JOIN users u ON u.id = ug.owner_id WHERE ug.owner_id = ?";
        if (!empty($user['company_id'])) {
            $sql = "SELECT ug.*, u.name as owner_name, u.email as owner_email FROM user_groups ug JOIN users u ON u.id = ug.owner_id WHERE ug.owner_id = ? OR ug.company_id = ? ORDER BY ug.name ASC";
            $params[] = $user['company_id'];
        }
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rawGroups = $stmt->fetchAll();

        $groups = [];
        foreach ($rawGroups as $g) {
            $mStmt = $db->prepare("SELECT u.id as user_id, u.name, u.email, ugm.created_at FROM user_group_members ugm JOIN users u ON u.id = ugm.user_id WHERE ugm.group_id = ? ORDER BY u.name ASC");
            $mStmt->execute([$g['id']]);
            $members = $mStmt->fetchAll();

            $fStmt = $db->prepare("SELECT pf.id as folder_id, pf.name as folder_name, fga.role FROM folder_group_access fga JOIN project_folders pf ON pf.id = fga.folder_id WHERE fga.group_id = ?");
            $fStmt->execute([$g['id']]);
            $folders = $fStmt->fetchAll();

            $pStmt = $db->prepare("SELECT p.id as project_id, p.title as project_title, pga.role FROM project_group_access pga JOIN projects p ON p.id = pga.project_id WHERE pga.group_id = ?");
            $pStmt->execute([$g['id']]);
            $projects = $pStmt->fetchAll();

            $g['members'] = $members;
            $g['folders'] = $folders;
            $g['projects'] = $projects;
            $groups[] = $g;
        }
        jsonResponse(['groups' => $groups]);
    }

    if ($path === 'groups' && $method === 'POST') {
        $user = requireAuth();
        $name = trim($body['name'] ?? '');
        if (!$name) errorResponse('Name der Gruppe erforderlich', 400);

        $description = !empty($body['description']) ? trim($body['description']) : null;
        $color = !empty($body['color']) ? trim($body['color']) : '#0891B2';
        $memberIds = isset($body['member_ids']) && is_array($body['member_ids']) ? $body['member_ids'] : [];

        $groupId = 'grp_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $companyId = !empty($user['company_id']) ? $user['company_id'] : null;

        $ins = $db->prepare("INSERT INTO user_groups (id, owner_id, company_id, name, description, color) VALUES (?, ?, ?, ?, ?, ?)");
        $ins->execute([$groupId, $user['id'], $companyId, $name, $description, $color]);

        $insM = $db->prepare("INSERT IGNORE INTO user_group_members (id, group_id, user_id) VALUES (?, ?, ?)");
        foreach ($memberIds as $mid) {
            if (is_string($mid) && trim($mid)) {
                $mId = 'ugm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $insM->execute([$mId, $groupId, trim($mid)]);
            }
        }

        $stmt = $db->prepare("SELECT ug.*, u.name as owner_name, u.email as owner_email FROM user_groups ug JOIN users u ON u.id = ug.owner_id WHERE ug.id = ?");
        $stmt->execute([$groupId]);
        $g = $stmt->fetch();
        $g['members'] = [];
        $g['folders'] = [];
        $g['projects'] = [];
        jsonResponse(['group' => $g]);
    }

    if (preg_match('#^groups/([^/]+)$#', $path, $matches) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $groupId = $matches[1];
        $stmt = $db->prepare("SELECT * FROM user_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if (!$group) errorResponse('Gruppe nicht gefunden', 404);

        $isOwner = $group['owner_id'] === $user['id'];
        $isCompanyAdmin = !empty($user['company_id']) && $user['company_id'] === $group['company_id'] && ($user['company_role'] ?? '') === 'admin';
        if (!$isOwner && !$isCompanyAdmin) errorResponse('Keine Berechtigung', 403);

        $name = trim($body['name'] ?? $group['name']);
        $description = array_key_exists('description', $body) ? trim($body['description'] ?? '') : $group['description'];
        $color = trim($body['color'] ?? $group['color']);

        $db->prepare("UPDATE user_groups SET name = ?, description = ?, color = ? WHERE id = ?")->execute([$name, $description, $color, $groupId]);

        if (isset($body['member_ids']) && is_array($body['member_ids'])) {
            $db->prepare("DELETE FROM user_group_members WHERE group_id = ?")->execute([$groupId]);
            $insM = $db->prepare("INSERT IGNORE INTO user_group_members (id, group_id, user_id) VALUES (?, ?, ?)");
            foreach ($body['member_ids'] as $mid) {
                if (is_string($mid) && trim($mid)) {
                    $mId = 'ugm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $insM->execute([$mId, $groupId, trim($mid)]);
                }
            }
        }

        $stmt = $db->prepare("SELECT ug.*, u.name as owner_name, u.email as owner_email FROM user_groups ug JOIN users u ON u.id = ug.owner_id WHERE ug.id = ?");
        $stmt->execute([$groupId]);
        $g = $stmt->fetch();
        jsonResponse(['group' => $g]);
    }

    if (preg_match('#^groups/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
        $user = requireAuth();
        $groupId = $matches[1];
        $stmt = $db->prepare("SELECT * FROM user_groups WHERE id = ?");
        $stmt->execute([$groupId]);
        $group = $stmt->fetch();
        if (!$group) errorResponse('Gruppe nicht gefunden', 404);

        $isOwner = $group['owner_id'] === $user['id'];
        $isCompanyAdmin = !empty($user['company_id']) && $user['company_id'] === $group['company_id'] && ($user['company_role'] ?? '') === 'admin';
        if (!$isOwner && !$isCompanyAdmin) errorResponse('Keine Berechtigung', 403);

        $db->prepare("DELETE FROM user_group_members WHERE group_id = ?")->execute([$groupId]);
        $db->prepare("DELETE FROM project_group_access WHERE group_id = ?")->execute([$groupId]);
        $db->prepare("DELETE FROM folder_group_access WHERE group_id = ?")->execute([$groupId]);
        $db->prepare("DELETE FROM user_groups WHERE id = ?")->execute([$groupId]);
        jsonResponse(['success' => true]);
    }

    if (preg_match('#^groups/([^/]+)/assign$#', $path, $matches) && $method === 'POST') {
        $user = requireAuth();
        $groupId = $matches[1];
        $type = $body['type'] ?? '';
        $targetId = $body['target_id'] ?? '';
        $role = $body['role'] ?? '';

        if (!$type || !$targetId || !$role) errorResponse('Fehlende Parameter', 400);

        if ($type === 'project') {
            if ($role === 'none') {
                $db->prepare("DELETE FROM project_group_access WHERE project_id = ? AND group_id = ?")->execute([$targetId, $groupId]);
            } else {
                $existing = $db->prepare("SELECT id FROM project_group_access WHERE project_id = ? AND group_id = ?");
                $existing->execute([$targetId, $groupId]);
                $exRow = $existing->fetch();
                if ($exRow) {
                    $db->prepare("UPDATE project_group_access SET role = ? WHERE id = ?")->execute([$role, $exRow['id']]);
                } else {
                    $id = 'pga_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO project_group_access (id, project_id, group_id, role) VALUES (?, ?, ?, ?)")->execute([$id, $targetId, $groupId, $role]);
                }
            }
        } elseif ($type === 'folder') {
            if ($role === 'none') {
                $db->prepare("DELETE FROM folder_group_access WHERE folder_id = ? AND group_id = ?")->execute([$targetId, $groupId]);
            } else {
                $existing = $db->prepare("SELECT id FROM folder_group_access WHERE folder_id = ? AND group_id = ?");
                $existing->execute([$targetId, $groupId]);
                $exRow = $existing->fetch();
                if ($exRow) {
                    $db->prepare("UPDATE folder_group_access SET role = ? WHERE id = ?")->execute([$role, $exRow['id']]);
                } else {
                    $id = 'fga_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO folder_group_access (id, folder_id, group_id, role) VALUES (?, ?, ?, ?)")->execute([$id, $targetId, $groupId, $role]);
                }
            }
        }
        jsonResponse(['success' => true]);
    }

    // --- TEAM & ZUGRIFFSMATRIX ---
    if ($path === 'team/access-matrix' && $method === 'GET') {
        $user = requireAuth();
        $companyId = !empty($user['company_id']) ? $user['company_id'] : null;

        $folders = [];
        if ($companyId) {
            $fStmt = $db->prepare("SELECT pf.id, pf.name, pf.icon, pf.visibility, pf.owner_id, u.name as owner_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id WHERE pf.owner_id = ? OR pf.company_id = ? ORDER BY pf.name ASC");
            $fStmt->execute([$user['id'], $companyId]);
            $folders = $fStmt->fetchAll();
        } else {
            $fStmt = $db->prepare("SELECT pf.id, pf.name, pf.icon, pf.visibility, pf.owner_id, u.name as owner_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id WHERE pf.owner_id = ? ORDER BY pf.name ASC");
            $fStmt->execute([$user['id']]);
            $folders = $fStmt->fetchAll();
        }

        $folderIds = array_map(function($f) { return $f['id']; }, $folders);
        $projects = [];
        if (!empty($folderIds)) {
            $in = str_repeat('?,', count($folderIds) - 1) . '?';
            $pStmt = $db->prepare("SELECT p.id, p.folder_id, p.title, p.status, p.visibility, pf.owner_id FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.folder_id IN ($in) ORDER BY p.title ASC");
            $pStmt->execute($folderIds);
            $projects = $pStmt->fetchAll();
        }

        $usersMap = [];
        $selfStmt = $db->prepare("SELECT id, name, email, company_id, company_role, created_at FROM users WHERE id = ?");
        $selfStmt->execute([$user['id']]);
        $self = $selfStmt->fetch();
        $self['is_self'] = true;
        $usersMap[$self['id']] = $self;

        if ($companyId) {
            $cuStmt = $db->prepare("SELECT id, name, email, company_id, company_role, created_at FROM users WHERE company_id = ?");
            $cuStmt->execute([$companyId]);
            foreach ($cuStmt->fetchAll() as $cu) {
                $cu['is_self'] = ($cu['id'] === $user['id']);
                $usersMap[$cu['id']] = $cu;
            }
        }

        $invitations = [];
        if ($companyId) {
            $iStmt = $db->prepare("SELECT ci.id, ci.email, ci.role, ci.token, ci.status, ci.created_at, u.name as invited_by_name FROM company_invitations ci LEFT JOIN users u ON u.id = ci.invited_by WHERE ci.company_id = ? ORDER BY ci.created_at DESC");
            $iStmt->execute([$companyId]);
            $invitations = $iStmt->fetchAll();
        }

        $groups = [];
        if ($companyId) {
            $gStmt = $db->prepare("SELECT id, name, color, owner_id, company_id FROM user_groups WHERE owner_id = ? OR company_id = ?");
            $gStmt->execute([$user['id'], $companyId]);
            $groups = $gStmt->fetchAll();
        } else {
            $gStmt = $db->prepare("SELECT id, name, color, owner_id, company_id FROM user_groups WHERE owner_id = ?");
            $gStmt->execute([$user['id']]);
            $groups = $gStmt->fetchAll();
        }

        $members = array_values($usersMap);
        jsonResponse([
            'members' => $members,
            'invitations' => $invitations,
            'folders' => $folders,
            'projects' => $projects,
            'groups' => $groups
        ]);
    }

    if ($path === 'team/access-matrix/permissions' && $method === 'POST') {
        $user = requireAuth();
        $targetUserId = $body['user_id'] ?? '';
        $type = $body['type'] ?? '';
        $targetId = $body['target_id'] ?? '';
        $role = $body['role'] ?? '';

        if (!$targetUserId || !$type || !$targetId || !$role) errorResponse('Fehlende Parameter', 400);

        if ($type === 'folder') {
            if ($role === 'none') {
                $db->prepare("DELETE FROM folder_members WHERE folder_id = ? AND user_id = ?")->execute([$targetId, $targetUserId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM folder_members WHERE folder_id = ? AND user_id = ?");
                $stmt->execute([$targetId, $targetUserId]);
                $ex = $stmt->fetch();
                if ($ex) {
                    $db->prepare("UPDATE folder_members SET role = ? WHERE id = ?")->execute([$role, $ex['id']]);
                } else {
                    $id = 'fm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO folder_members (id, folder_id, user_id, role) VALUES (?, ?, ?, ?)")->execute([$id, $targetId, $targetUserId, $role]);
                }
            }
        } elseif ($type === 'project') {
            if ($role === 'none') {
                $db->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?")->execute([$targetId, $targetUserId]);
            } else {
                $stmt = $db->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
                $stmt->execute([$targetId, $targetUserId]);
                $ex = $stmt->fetch();
                if ($ex) {
                    $db->prepare("UPDATE project_members SET role = ? WHERE id = ?")->execute([$role, $ex['id']]);
                } else {
                    $id = 'pm_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO project_members (id, project_id, user_id, role) VALUES (?, ?, ?, ?)")->execute([$id, $targetId, $targetUserId, $role]);
                }
            }
        }
        jsonResponse(['success' => true]);
    }

    if (preg_match('#^companies/invitations/([^/]+)$#', $path, $matches) && $method === 'DELETE') {
        $user = requireAuth();
        $inviteId = $matches[1];
        $db->prepare("DELETE FROM company_invitations WHERE id = ?")->execute([$inviteId]);
        jsonResponse(['success' => true, 'message' => 'Einladung widerrufen']);
    }

    // 16b. GET companies/members (Company Admin lists company members)
    if ($path === 'companies/members' && $method === 'GET') {
        $user = requireAuth();

        if (empty($user['is_superadmin']) && (empty($user['company_id']) || $user['company_role'] !== 'admin')) {
            errorResponse('Nur Company-Admins dürfen Mitarbeiter einsehen', 403);
        }

        $companyId = $user['company_id'];
        if (!$companyId && !empty($user['is_superadmin'])) {
            $companyId = $_GET['company_id'] ?? null;
        }
        if (!$companyId) errorResponse('Kein Unternehmen zugewiesen', 400);

        $stmt = $db->prepare("
            SELECT id, email, name, avatar, company_role, is_superadmin, created_at
            FROM users
            WHERE company_id = ?
            ORDER BY name ASC, email ASC
        ");
        $stmt->execute([$companyId]);
        $members = $stmt->fetchAll();

        // Also fetch pending invitations
        $invStmt = $db->prepare("
            SELECT id, email, role, token, created_at, expires_at
            FROM company_invitations
            WHERE company_id = ? AND accepted_at IS NULL AND expires_at > NOW()
            ORDER BY created_at DESC
        ");
        $invStmt->execute([$companyId]);
        $invitations = $invStmt->fetchAll();

        jsonResponse([
            'members' => $members,
            'invitations' => $invitations
        ]);
    }

    // 16c. DELETE companies/members (Remove user from company)
    if ($path === 'companies/members' && $method === 'DELETE') {
        $user = requireAuth();
        $targetUserId = $body['user_id'] ?? $_GET['user_id'] ?? '';

        if (!$targetUserId) errorResponse('Benutzer-ID erforderlich', 400);

        if (empty($user['is_superadmin']) && (empty($user['company_id']) || $user['company_role'] !== 'admin')) {
            errorResponse('Nur Company-Admins dürfen Mitarbeiter entfernen', 403);
        }

        $companyId = $user['company_id'] ?: ($body['company_id'] ?? null);

        // Cannot remove oneself
        if ($targetUserId === $user['id']) {
            errorResponse('Sie können sich nicht selbst aus dem Unternehmen entfernen', 400);
        }

        // Verify target user belongs to company
        $stmt = $db->prepare("SELECT id, company_id FROM users WHERE id = ?");
        $stmt->execute([$targetUserId]);
        $target = $stmt->fetch();

        if (!$target || $target['company_id'] !== $companyId) {
            errorResponse('Benutzer nicht im Unternehmen gefunden', 404);
        }

        // Unassign from company
        $db->prepare("UPDATE users SET company_id = NULL, company_role = 'member' WHERE id = ?")->execute([$targetUserId]);

        jsonResponse(['success' => true, 'removed_user_id' => $targetUserId]);
    }

    // ==========================================
    // AI ENDPOINTS (OpenRouter / DeepSeek V4 Flash)
    // Serverseitig: API-Key bleibt in .env, nie im Client.
    // ==========================================

    // AI-1. POST ai/chat (Prompt + optionale Nachrichtenhistorie)
    if ($path === 'ai/chat' && $method === 'POST') {
        $user = requireAuth();

        $prompt = trim($body['prompt'] ?? '');
        $history = is_array($body['messages'] ?? null) ? $body['messages'] : [];
        $systemOverride = !empty($body['system']) ? trim($body['system']) : null;
        $jsonMode = !empty($body['json']);

        if ($prompt === '' && empty($history)) {
            errorResponse('Prompt erforderlich', 400);
        }

        $config = getAiConfig();
        $messages = [[
            'role' => 'system',
            'content' => $systemOverride ?: $config['system_prompt']
        ]];

        // Optionale Historie (nur user/assistant, max 20 Nachrichten)
        foreach (array_slice($history, -20) as $msg) {
            if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                $messages[] = ['role' => $msg['role'], 'content' => trim($msg['content'])];
            }
        }

        $userContent = $prompt;
        if ($jsonMode) {
            $userContent .= "\n\nAntworte AUSSCHLIESSLICH mit gueltigem JSON, ohne Markdown-Codeblock und ohne Erklaerung.";
        }
        $messages[] = ['role' => 'user', 'content' => $userContent];

        try {
            $result = callOpenRouter($messages, [
                'model' => $body['model'] ?? null,
                'temperature' => isset($body['temperature']) ? (float)$body['temperature'] : null,
                'max_tokens' => isset($body['max_tokens']) ? (int)$body['max_tokens'] : null
            ]);

            $responseText = $result['text'];
            if ($jsonMode) {
                $responseText = preg_replace('/^```(?:json)?\s*/i', '', $responseText);
                $responseText = preg_replace('/\s*```$/', '', $responseText);
                $responseText = trim($responseText);
            }

            jsonResponse([
                'success' => true,
                'text' => $responseText,
                'response' => $responseText,
                'model' => $result['model'],
                'usage' => $result['usage']
            ]);
        } catch (Exception $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 502;
            errorResponse($e->getMessage(), $code);
        }
    }

    // AI-2. GET ai/config (Konfiguration fuer Debug/Transparenz, ohne Key)
    if ($path === 'ai/config' && $method === 'GET') {
        $config = getAiConfig();
        jsonResponse([
            'enabled' => !empty(getEnvValue('OPENROUTER_API_KEY')),
            'model' => $config['model'],
            'audio_model' => $config['audio_model'] ?? 'openai/whisper-large-v3-turbo',
            'provider' => $config['provider'] ?? 'openrouter',
            'temperature' => $config['temperature'],
            'max_tokens' => $config['max_tokens'],
            'timeout_seconds' => $config['timeout_seconds'],
            'key_configured' => (bool)getEnvValue('OPENROUTER_API_KEY')
        ]);
    }

    // AI-3. POST ai/transcribe (Sprachtranskription mit OpenRouter / openai/whisper-large-v3-turbo)
    if ($path === 'ai/transcribe' && $method === 'POST') {
        requireAuth();
        $config = getAiConfig();
        $apiKey = getEnvValue('OPENROUTER_API_KEY');
        if (!$apiKey) {
            errorResponse('OPENROUTER_API_KEY fehlt in .env', 500);
        }

        $audioBase64 = trim($body['audio'] ?? $body['audioBase64'] ?? $body['file'] ?? '');
        $mimeType = $body['mimeType'] ?? 'audio/webm';
        $targetModel = $body['model'] ?? $config['audio_model'] ?? 'openai/whisper-large-v3-turbo';

        if (!$audioBase64) {
            errorResponse('Audio-Daten erforderlich (base64)', 400);
        }

        if (strpos($audioBase64, 'base64,') !== false) {
            $parts = explode('base64,', $audioBase64);
            $audioBase64 = $parts[1];
        }

        $audioData = base64_decode($audioBase64);
        if (!$audioData) {
            errorResponse('Ungueltige Base64 Audio-Daten', 400);
        }

        // Call OpenRouter audio/transcriptions endpoint
        $tmpFile = tempnam(sys_get_temp_dir(), 'voice_') . '.webm';
        file_put_contents($tmpFile, $audioData);

        $ch = curl_init('https://openrouter.ai/api/v1/audio/transcriptions');
        $cFile = new CURLFile($tmpFile, $mimeType, 'recording.webm');
        $lang = $body['language'] ?? 'de';
        $whisperLang = ($lang === 'auto') ? null : (strpos($lang, 'de') === 0 ? 'de' : $lang);
        $prompt = 'Transkription. Baustelle, Projekt, Notiz, Aufgabe, Handwerker, Schweiz.';
        if ($lang === 'en') $prompt = 'Transcription in English. Construction, project, note, task, craftsman, site.';
        elseif ($lang === 'fr') $prompt = 'Transcription en français. Chantier, projet, note, tâche, artisan, Suisse.';
        elseif ($lang === 'it') $prompt = 'Trascrizione in italiano. Cantiere, progetto, nota, compito, artigiano, Svizzera.';

        $postFields = [
            'file' => $cFile,
            'model' => $targetModel,
            'prompt' => $prompt,
            'temperature' => '0.0'
        ];
        if (!empty($whisperLang)) {
            $postFields['language'] = $whisperLang;
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'HTTP-Referer' => 'https://taskster.ch',
                'X-Title: Taskster Voice Transcription'
            ],
            CURLOPT_TIMEOUT => (int)($config['timeout_seconds'] ?? 60)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tmpFile);

        if ($httpCode >= 200 && $httpCode < 300) {
            $resData = json_decode((string)$response, true);
            $text = trim($resData['text'] ?? $resData['transcription'] ?? '');
            if ($text !== '') {
                jsonResponse([
                    'success' => true,
                    'text' => $text,
                    'model' => $targetModel,
                    'language' => $lang
                ]);
            }
        }

        // Fallback: Chat completion with input audio
        try {
            $chatMessages = [
                ['role' => 'system', 'content' => 'Du bist ein praeziser Transkriptions-Assistent. Transkribiere die gesprochene Audionachricht Wort fuer Wort. Gib AUSSCHLIESSLICH den gesprochenen Text zurueck, ohne Kommentare, Hoeflichkeitsfloskeln oder Anfuehrungszeichen.'],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'input_audio',
                            'input_audio' => [
                                'data' => $audioBase64,
                                'format' => strpos($mimeType, 'wav') !== false ? 'wav' : (strpos($mimeType, 'mp3') !== false ? 'mp3' : 'webm')
                            ]
                        ],
                        ['type' => 'text', 'text' => 'Bitte transkribiere diese Audionachricht praezise.']
                    ]
                ]
            ];
            $res = callOpenRouter($chatMessages, ['model' => $targetModel]);
            jsonResponse([
                'success' => true,
                'text' => trim($res['text']),
                'model' => $targetModel,
                'language' => $lang
            ]);
        } catch (Exception $e) {
            errorResponse('Sprachtranskription fehlgeschlagen: ' . $e->getMessage(), 502);
        }
    }

    // AI-4. POST ai/analyze-voice (Kontexterkennung und Handlungsvorschläge)
    if ($path === 'ai/analyze-voice' && $method === 'POST') {
        $user = requireAuth();
        $text = trim($body['text'] ?? '');
        $currentProjectId = $body['current_project_id'] ?? null;

        if (empty($text)) {
            errorResponse('Text erforderlich', 400);
        }

        // 1. Fetch user accessible projects
        $pStmt = $db->prepare("
            SELECT DISTINCT p.id, p.title, p.description
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.company_id = ? OR pf.owner_id = ? OR p.visibility = 'public'
            ORDER BY p.is_default DESC, p.created_at DESC
            LIMIT 30
        ");
        $pStmt->execute([$user['company_id'] ?? '', $user['id']]);
        $projects = $pStmt->fetchAll();

        $projectIds = array_column($projects, 'id');
        $tasks = [];
        if (!empty($projectIds)) {
            $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
            $tStmt = $db->prepare("
                SELECT t.id, t.title, t.description, t.custom_data, t.status, t.priority, t.due_date,
                       t.list_id, l.title as list_title, p.id as project_id, p.title as project_title
                FROM tasks t
                JOIN lists l ON l.id = t.list_id
                JOIN projects p ON p.id = l.project_id
                WHERE p.id IN ($placeholders) AND t.status != 'done'
                ORDER BY t.created_at DESC
                LIMIT 80
            ");
            $tStmt->execute($projectIds);
            $tasks = $tStmt->fetchAll();
        }

        $projectContext = array_map(function($p) { return ['id' => $p['id'], 'title' => $p['title']]; }, array_slice($projects, 0, 20));
        $taskContext = array_map(function($t) {
            $customFields = [];
            if (!empty($t['custom_data'])) {
                $customFields = is_string($t['custom_data']) ? (json_decode($t['custom_data'], true) ?: []) : $t['custom_data'];
            }
            return [
                'id' => $t['id'],
                'title' => $t['title'],
                'project_id' => $t['project_id'],
                'project_title' => $t['project_title'],
                'status' => $t['status'],
                'custom_fields' => $customFields,
                'desc_snippet' => substr($t['description'] ?? '', 0, 150)
            ];
        }, array_slice($tasks, 0, 60));

        $sysPrompt = "Du bist der intelligente Sprachnotiz-Assistent von Taskster (Projekt- & Baustellenmanagement).\n"
            . "Analysiere den Sprachnotiz-Text und erkenne Aufgabe/Projekt.\n"
            . "WICHTIG: Oft ist der Aufgabentitel eine Nummer (z.B. '0100314559'), waehrend die Adresse (z.B. Strasse: 'Zentralstr. 14B', Ort: 'Ebikon') in custom_fields steht!\n"
            . "Beachte Abkuerzungen: 'Zentralstr. 14B' = 'Zentralstrasse 14b'.\n"
            . "Erkenne Stornierungen/Abschluesse: 'kann storniert werden', 'storniert von swisscom' => intent: complete_task.\n"
            . "Projekte: " . json_encode($projectContext) . "\n"
            . "Offene Aufgaben: " . json_encode($taskContext) . "\n"
            . "Gib AUSSCHLIESSLICH JSON zurück:\n"
            . '{"summary":"...","intent":"complete_task|update_task|add_checklist|create_task|create_journal","matched_task":null,"matched_project":null,"extracted_task_title":"...","note_to_append":"...","checklist_items":[],"suggested_actions":[]}';

        $analysis = null;
        try {
            $chatMessages = [
                ['role' => 'system', 'content' => $sysPrompt],
                ['role' => 'user', 'content' => "Sprachnotiz:\n\"" . $text . "\"\nGib ausschliesslich valides JSON zurück."]
            ];
            $res = callOpenRouter($chatMessages, ['temperature' => 0.1]);
            $raw = trim($res['text']);
            $raw = preg_replace('/^```json\s*/i', '', $raw);
            $raw = preg_replace('/```$/i', '', $raw);
            $analysis = json_decode($raw, true);
        } catch (Exception $e) {
            // Fallback will be used
        }

        if (!$analysis || !is_array($analysis)) {
            $normStr = function($s) {
                $s = mb_strtolower((string)$s, 'UTF-8');
                $s = str_replace(['strasse', 'str.'], 'str', $s);
                return preg_replace('/[^a-z0-9]/', '', $s);
            };

            $lower = mb_strtolower($text, 'UTF-8');
            $normText = $normStr($text);
            $matchedTask = null;
            $matchedProject = null;

            foreach ($tasks as $t) {
                $tNorm = $normStr($t['title']);
                $descNorm = $normStr($t['description'] ?? '');
                $customNorm = '';
                if (!empty($t['custom_data'])) {
                    $cDec = is_string($t['custom_data']) ? json_decode($t['custom_data'], true) : $t['custom_data'];
                    if (is_array($cDec)) $customNorm = $normStr(implode(' ', array_values($cDec)));
                }

                if (
                    (strlen($tNorm) >= 4 && strpos($normText, $tNorm) !== false) ||
                    (strlen($customNorm) >= 4 && (strpos($normText, $customNorm) !== false || strpos($customNorm, 'zentralstr') !== false && strpos($normText, 'zentralstr') !== false)) ||
                    (strlen($descNorm) >= 6 && strpos($normText, substr($descNorm, 0, 15)) !== false)
                ) {
                    $matchedTask = ['id' => $t['id'], 'title' => $t['title'], 'project_id' => $t['project_id'], 'project_title' => $t['project_title']];
                    $matchedProject = ['id' => $t['project_id'], 'title' => $t['project_title']];
                    break;
                }
            }

            if (!$matchedProject && $currentProjectId) {
                foreach ($projects as $p) {
                    if ($p['id'] === $currentProjectId) {
                        $matchedProject = ['id' => $p['id'], 'title' => $p['title']];
                        break;
                    }
                }
            }

            $isDone = strpos($lower, 'erledigt') !== false || strpos($lower, 'fertig') !== false || 
                      strpos($lower, 'abgeschlossen') !== false || strpos($lower, 'stornier') !== false;
            $suggested = [];

            if ($matchedTask) {
                if ($isDone) {
                    $isCancel = strpos($lower, 'stornier') !== false;
                    $suggested[] = [
                        'id' => 'complete_task',
                        'type' => 'complete_task',
                        'label' => $isCancel ? "'" . $matchedTask['title'] . "' als storniert / erledigt markieren" : "'" . $matchedTask['title'] . "' als erledigt markieren",
                        'description' => $isCancel ? 'Setzt den Status auf Erledigt und hinterlegt Stornierungsnotiz' : 'Setzt den Status der Aufgabe auf Erledigt',
                        'task_id' => $matchedTask['id'],
                        'project_id' => $matchedTask['project_id']
                    ];
                }
                $suggested[] = [
                    'id' => 'update_task',
                    'type' => 'update_task',
                    'label' => "Notiz an '" . $matchedTask['title'] . "' anhängen",
                    'description' => 'Ergänzt die Aufgabenbeschreibung',
                    'task_id' => $matchedTask['id'],
                    'project_id' => $matchedTask['project_id']
                ];
            }

            if ($matchedProject) {
                $suggested[] = [
                    'id' => 'create_task',
                    'type' => 'create_task',
                    'label' => "Neue Aufgabe in '" . $matchedProject['title'] . "' erstellen",
                    'description' => 'Legt eine neue Aufgabe im Projekt an',
                    'project_id' => $matchedProject['id']
                ];
                $suggested[] = [
                    'id' => 'create_journal',
                    'type' => 'create_journal',
                    'label' => "Als Journal in '" . $matchedProject['title'] . "' speichern",
                    'description' => 'Speichert im Projekt-Journal',
                    'project_id' => $matchedProject['id']
                ];
            } else {
                $suggested[] = [
                    'id' => 'create_journal',
                    'type' => 'create_journal',
                    'label' => 'Als persönliche Notiz speichern',
                    'description' => 'Speichert die Aufnahme in deiner Notizablage'
                ];
            }

            $analysis = [
                'summary' => $matchedTask ? "Aufgabe '" . $matchedTask['title'] . "' erkannt." : "Sprachnotiz analysiert.",
                'intent' => $matchedTask ? ($isDone ? 'complete_task' : 'update_task') : 'create_journal',
                'matched_task' => $matchedTask,
                'matched_project' => $matchedProject,
                'extracted_task_title' => mb_substr($text, 0, 48),
                'note_to_append' => $text,
                'checklist_items' => [],
                'suggested_actions' => $suggested
            ];
        }

        jsonResponse(['success' => true, 'analysis' => $analysis]);
    }

    // ==========================================
    // GLOBAL SEARCH (Command Palette)
    // Zero-Trust: nur Entitaeten, die der Nutzer sehen darf.
    // ==========================================

    // SEARCH-1. GET search?q=...&limit=...
    if ($path === 'search' && $method === 'GET') {
        $user = requireAuth();
        $q = trim($_GET['q'] ?? '');
        $limit = min((int)($_GET['limit'] ?? 8), 25);

        if ($q === '') {
            jsonResponse(['query' => '', 'total' => 0, 'groups' => []]);
        }

        $like = '%' . $q . '%';
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';
        $uid = $user['id'];

        // Bewertung: exakt > Anfang > Wortanfang > irgendwo
        $scoreMatch = function ($text, $query) {
            if (!$text) return 0;
            $t = mb_strtolower($text);
            $qq = mb_strtolower($query);
            if ($t === $qq) return 100;
            if (strpos($t, $qq) === 0) return 80;
            $words = preg_split('/[\s\-_\/.,;:()]+/', $t);
            foreach ($words as $w) {
                if ($w !== '' && strpos($w, $qq) === 0) return 60;
            }
            if (strpos($t, $qq) !== false) return 40;
            return 0;
        };

        $hits = [];

        // Sichtbarkeit fuer 'custom'-Abschnitte einmalig laden (Zero-Trust)
        $visStmt = $db->prepare("SELECT list_id FROM list_access WHERE user_id = ? AND is_visible = 1");
        $visStmt->execute([$uid]);
        $visibleListIds = array_column($visStmt->fetchAll(), 'list_id');

        // Hilfsfunktion: darf der Nutzer diesen 'custom'-Abschnitt sehen?
        $canSeeCustomList = function ($listId, $projectId, $folderOwnerId) use ($db, $uid, $user, $visibleListIds) {
            if (!empty($user['is_superadmin'])) return true;
            if ($folderOwnerId === $uid) return true;
            if (in_array($listId, $visibleListIds, true)) return true;
            $s = $db->prepare("SELECT 1 FROM project_members WHERE project_id = ? AND user_id = ? AND role IN ('owner','admin')");
            $s->execute([$projectId, $uid]);
            return (bool)$s->fetchColumn();
        };

        // 1. Aufgaben
        $stmt = $db->prepare("
            SELECT t.id, t.title, t.description, t.status, t.priority,
                   l.title AS list_title, l.access_mode AS list_access_mode,
                   p.id AS project_id, p.title AS project_title,
                   pf.name AS folder_name, pf.owner_id AS folder_owner_id
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
        ");
        $stmt->execute([$like, $like, $uid, $uid, $companyId]);
        foreach ($stmt->fetchAll() as $t) {
            if ($t['list_access_mode'] === 'custom'
                && !$canSeeCustomList($t['id'], $t['project_id'], $t['folder_owner_id'])) {
                continue;
            }
            $score = max($scoreMatch($t['title'], $q), $scoreMatch($t['description'] ?? '', $q) * 0.6);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $t['id'], 'type' => 'task', 'title' => $t['title'],
                'subtitle' => $t['description'] ? mb_substr($t['description'], 0, 90) : null,
                'context' => $t['folder_name'] . ' › ' . $t['project_title'] . ' › ' . $t['list_title'],
                'status' => $t['status'], 'priority' => $t['priority'],
                'url' => '/projects/' . $t['project_id'] . '?task=' . $t['id'],
                'icon' => 'ClipboardList', 'score' => $score + 5
            ];
        }

        // 2. Projekte
        $stmt = $db->prepare("
            SELECT p.id, p.title, p.status, pf.name AS folder_name
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE p.title LIKE ?
              AND (
                pf.owner_id = ?
                OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
              )
            LIMIT 40
        ");
        $stmt->execute([$like, $uid, $uid, $companyId]);
        foreach ($stmt->fetchAll() as $p) {
            $score = $scoreMatch($p['title'], $q);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $p['id'], 'type' => 'project', 'title' => $p['title'],
                'context' => $p['folder_name'], 'status' => $p['status'],
                'url' => '/projects/' . $p['id'], 'icon' => 'FolderKanban', 'score' => $score + 3
            ];
        }

        // 3. Ordner
        $stmt = $db->prepare("
            SELECT pf.id, pf.name, u.name AS owner_name
            FROM project_folders pf
            JOIN users u ON u.id = pf.owner_id
            WHERE pf.name LIKE ?
              AND (
                pf.owner_id = ? OR pf.company_id = ?
                OR pf.id IN (
                  SELECT p.folder_id FROM projects p
                  JOIN project_members pm ON pm.project_id = p.id
                  WHERE pm.user_id = ?
                )
              )
            LIMIT 30
        ");
        $stmt->execute([$like, $uid, $companyId, $uid]);
        foreach ($stmt->fetchAll() as $f) {
            $score = $scoreMatch($f['name'], $q);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $f['id'], 'type' => 'folder', 'title' => $f['name'],
                'context' => $f['owner_name'], 'url' => '/folders/' . $f['id'],
                'icon' => 'Folder', 'score' => $score + 2
            ];
        }

        // 4. Abschnitte (Zero-Trust: 'custom' nur fuer Berechtigte)
        $stmt = $db->prepare("
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
        ");
        $stmt->execute([$like, $uid, $uid, $companyId]);
        foreach ($stmt->fetchAll() as $l) {
            if ($l['access_mode'] === 'custom'
                && !$canSeeCustomList($l['id'], $l['project_id'], $l['folder_owner_id'])) {
                continue;
            }
            $score = $scoreMatch($l['title'], $q);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $l['id'], 'type' => 'list', 'title' => $l['title'],
                'context' => $l['project_title'], 'url' => '/projects/' . $l['project_id'],
                'icon' => 'Columns3', 'score' => $score
            ];
        }

        // 5. Journal
        $stmt = $db->prepare("
            SELECT j.id, j.title, j.content, p.id AS project_id, p.title AS project_title
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
        ");
        $stmt->execute([$like, $like, $uid, $uid, $companyId]);
        foreach ($stmt->fetchAll() as $j) {
            $score = max($scoreMatch($j['title'], $q), $scoreMatch($j['content'] ?? '', $q) * 0.5);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $j['id'], 'type' => 'journal', 'title' => $j['title'],
                'subtitle' => mb_substr($j['content'] ?? '', 0, 90),
                'context' => $j['project_title'],
                'url' => '/projects/' . $j['project_id'] . '?view=journal',
                'icon' => 'FileText', 'score' => $score
            ];
        }

        // 6. Tages-Todos
        $stmt = $db->prepare("
            SELECT dt.id, dt.title, dt.is_completed, p.title AS project_title
            FROM daily_todos dt
            LEFT JOIN projects p ON p.id = dt.project_id
            WHERE dt.user_id = ? AND dt.title LIKE ?
            LIMIT 20
        ");
        $stmt->execute([$uid, $like]);
        foreach ($stmt->fetchAll() as $t) {
            $score = $scoreMatch($t['title'], $q);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $t['id'], 'type' => 'todo', 'title' => $t['title'],
                'context' => $t['project_title'] ?: 'Persönlich',
                'status' => $t['is_completed'] ? 'done' : 'todo',
                'url' => '/dashboard', 'icon' => 'Sun', 'score' => $score
            ];
        }

        // 7. Team
        $stmt = $db->prepare("
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
        ");
        $stmt->execute([$like, $like, $uid, $companyId, $uid]);
        foreach ($stmt->fetchAll() as $m) {
            $score = max($scoreMatch($m['name'], $q), $scoreMatch($m['email'], $q) * 0.8);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $m['id'], 'type' => 'member', 'title' => $m['name'],
                'subtitle' => $m['email'],
                'context' => $m['company_role'] === 'admin' ? 'Co-Admin' : 'Mitarbeiter',
                'url' => '/company', 'icon' => 'User', 'score' => $score
            ];
        }

        // 8. Vorlagen
        $stmt = $db->prepare("
            SELECT id, name, description, is_system
            FROM project_templates
            WHERE (name LIKE ? OR description LIKE ?)
              AND (is_system = 1 OR company_id = ?)
            LIMIT 20
        ");
        $stmt->execute([$like, $like, $companyId]);
        foreach ($stmt->fetchAll() as $t) {
            $score = max($scoreMatch($t['name'], $q), $scoreMatch($t['description'] ?? '', $q) * 0.5);
            if ($score <= 0) continue;
            $hits[] = [
                'id' => $t['id'], 'type' => 'template', 'title' => $t['name'],
                'subtitle' => $t['description'] ? mb_substr($t['description'], 0, 90) : null,
                'context' => $t['is_system'] ? 'Systemvorlage' : 'Firmenvorlage',
                'url' => '/dashboard', 'icon' => 'LayoutTemplate', 'score' => $score
            ];
        }

        // Gruppieren
        $typeOrder = ['task', 'project', 'folder', 'list', 'journal', 'todo', 'member', 'template'];
        $typeLabels = [
            'task' => 'Aufgaben', 'project' => 'Projekte', 'folder' => 'Ordner',
            'list' => 'Abschnitte', 'journal' => 'Journal', 'todo' => 'Tages-Todos',
            'member' => 'Team', 'template' => 'Vorlagen'
        ];

        $groups = [];
        foreach ($typeOrder as $type) {
            $items = array_values(array_filter($hits, function ($h) use ($type) {
                return $h['type'] === $type;
            }));
            if (empty($items)) continue;
            usort($items, function ($a, $b) { return $b['score'] <=> $a['score']; });
            $items = array_slice($items, 0, $limit);
            $groups[] = ['type' => $type, 'label' => $typeLabels[$type], 'items' => $items];
        }

        jsonResponse(['query' => $q, 'total' => count($hits), 'groups' => $groups]);
    }

    // ==========================================
    // CALENDAR (Sidebar-Miniatur: Aufgaben-Faelligkeiten)
    // ==========================================

    // CAL-1. GET calendar?year=YYYY&month=M
    if ($path === 'calendar' && $method === 'GET') {
        $user = requireAuth();
        $year = (int)($_GET['year'] ?? date('Y'));
        $month = (int)($_GET['month'] ?? date('n'));

        if ($month < 1 || $month > 12) {
            errorResponse('Ungültiger Monat', 400);
        }

        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $lastDay = sprintf('%04d-%02d-%02d', $year, $month, (int)date('t', mktime(0, 0, 0, $month, 1, $year)));
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';
        $uid = $user['id'];

        $stmt = $db->prepare("
            SELECT t.id, t.title, t.due_date, t.status, t.priority,
                   p.id AS project_id, p.title AS project_title
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE t.due_date IS NOT NULL
              AND t.due_date >= ? AND t.due_date <= ?
              AND (
                pf.owner_id = ?
                OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
              )
            ORDER BY t.due_date ASC
        ");
        $stmt->execute([$firstDay, $lastDay, $uid, $uid, $companyId]);
        $rows = $stmt->fetchAll();

        $today = date('Y-m-d');
        $days = [];
        foreach ($rows as $r) {
            $dateKey = substr($r['due_date'], 0, 10);
            if (!isset($days[$dateKey])) {
                $days[$dateKey] = ['count' => 0, 'overdue' => 0, 'items' => []];
            }
            $isOverdue = ($dateKey < $today && $r['status'] !== 'done');
            $days[$dateKey]['count']++;
            if ($isOverdue) $days[$dateKey]['overdue']++;
            if (count($days[$dateKey]['items']) < 5) {
                $days[$dateKey]['items'][] = [
                    'id' => $r['id'], 'title' => $r['title'], 'status' => $r['status'],
                    'priority' => $r['priority'], 'project_id' => $r['project_id'],
                    'project_title' => $r['project_title'], 'overdue' => $isOverdue
                ];
            }
        }

        jsonResponse([
            'year' => $year, 'month' => $month, 'today' => $today,
            'total' => count($rows), 'days' => $days
        ]);
    }

    // ==========================================
    // KALENDER-TERMINE (Events CRUD + Einladungen)
    // ==========================================

    // EVT-1. GET events?from=&to=&project_id=&category_id=
    if ($path === 'events' && $method === 'GET') {
        $user = requireAuth();
        $from = substr($_GET['from'] ?? '', 0, 10);
        $to = substr($_GET['to'] ?? '', 0, 10);
        if (!$from || !$to) errorResponse('from und to sind erforderlich', 400);

        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';
        $uid = $user['id'];

        $sql = "
            SELECT e.*,
                   c.name AS category_name, c.color AS category_color, c.icon AS category_icon,
                   u.name AS owner_name, u.email AS owner_email,
                   p.title AS project_title,
                   (SELECT COUNT(*) FROM event_attendees a WHERE a.event_id = e.id) AS attendee_count
            FROM calendar_events e
            LEFT JOIN event_categories c ON c.id = e.category_id
            LEFT JOIN users u ON u.id = e.owner_id
            LEFT JOIN projects p ON p.id = e.project_id
            WHERE e.start_at <= ? AND e.end_at >= ?
              AND (
                e.owner_id = ?
                OR (e.visibility = 'company' AND e.company_id = ?)
                OR EXISTS (SELECT 1 FROM event_attendees a WHERE a.event_id = e.id AND (a.user_id = ? OR LOWER(a.email) = LOWER(?)))
              )
        ";
        $params = [$to . ' 23:59:59', $from . ' 00:00:00', $uid, $companyId, $uid, $user['email']];

        if (!empty($_GET['project_id'])) { $sql .= " AND e.project_id = ?"; $params[] = $_GET['project_id']; }
        if (!empty($_GET['category_id'])) { $sql .= " AND e.category_id = ?"; $params[] = $_GET['category_id']; }
        $sql .= " ORDER BY e.start_at ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $events = [];
        foreach ($rows as $e) {
            $aStmt = $db->prepare("SELECT id, user_id, email, name, role, status, is_organizer FROM event_attendees WHERE event_id = ? ORDER BY is_organizer DESC, name ASC");
            $aStmt->execute([$e['id']]);
            $attendees = $aStmt->fetchAll();

            $myStatus = null;
            foreach ($attendees as $a) {
                if ($a['user_id'] === $uid || strtolower($a['email']) === strtolower($user['email'])) {
                    $myStatus = $a['status'];
                    break;
                }
            }

            $events[] = [
                'id' => $e['id'], 'type' => 'event', 'title' => $e['title'],
                'description' => $e['description'], 'location' => $e['location'],
                'latitude' => $e['latitude'] !== null ? floatval($e['latitude']) : null,
                'longitude' => $e['longitude'] !== null ? floatval($e['longitude']) : null,
                'start' => $e['start_at'], 'end' => $e['end_at'],
                'allDay' => (bool)$e['all_day'], 'priority' => $e['priority'],
                'status' => $e['status'], 'visibility' => $e['visibility'],
                'color' => $e['color'] ?: ($e['category_color'] ?: '#0891B2'),
                'category_id' => $e['category_id'], 'category_name' => $e['category_name'],
                'category_icon' => $e['category_icon'],
                'project_id' => $e['project_id'], 'project_title' => $e['project_title'],
                'owner_id' => $e['owner_id'], 'owner_name' => $e['owner_name'],
                'is_organizer' => $e['owner_id'] === $uid,
                'my_status' => $myStatus,
                'attendee_count' => (int)$e['attendee_count'],
                'attendees' => $attendees,
                'editable' => $e['owner_id'] === $uid || !empty($user['is_superadmin'])
            ];
        }

        // Aufgaben mit Faelligkeit (schreibgeschuetzt)
        $tStmt = $db->prepare("
            SELECT t.id, t.title, t.due_date, t.status, t.priority,
                   p.id AS project_id, p.title AS project_title
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE t.due_date IS NOT NULL AND t.due_date >= ? AND t.due_date <= ?
              AND (
                pf.owner_id = ?
                OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                OR (pf.company_id IS NOT NULL AND pf.company_id = ? AND pf.visibility = 'company')
              )
            ORDER BY t.due_date ASC
        ");
        $tStmt->execute([$from, $to, $uid, $uid, $companyId]);
        $tasks = [];
        foreach ($tStmt->fetchAll() as $t) {
            $tasks[] = [
                'id' => 'task_' . $t['id'], 'task_id' => $t['id'], 'type' => 'task',
                'title' => $t['title'], 'start' => $t['due_date'], 'end' => $t['due_date'],
                'allDay' => true, 'status' => $t['status'], 'priority' => $t['priority'],
                'color' => $t['status'] === 'done' ? '#059669' : '#64748B',
                'project_id' => $t['project_id'], 'project_title' => $t['project_title'],
                'editable' => false
            ];
        }

        jsonResponse(['events' => $events, 'tasks' => $tasks, 'from' => $from, 'to' => $to]);
    }

    // EVT-2. POST events (Termin erstellen + Einladungen)
    if ($path === 'events' && $method === 'POST') {
        $user = requireAuth();
        $title = trim($body['title'] ?? '');
        $startAt = trim($body['start_at'] ?? '');
        $endAt = trim($body['end_at'] ?? '');

        if (!$title) errorResponse('Betreff erforderlich', 400);
        if (!$startAt || !$endAt) errorResponse('Start und Ende erforderlich', 400);
        if ($endAt < $startAt) errorResponse('Ende darf nicht vor dem Start liegen', 400);

        $id = 'evt_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $allDay = !empty($body['all_day']) ? 1 : 0;
        $priority = in_array($body['priority'] ?? '', ['niedrig', 'normal', 'hoch', 'dringend'], true) ? $body['priority'] : 'normal';
        $visibility = in_array($body['visibility'] ?? '', ['private', 'company'], true) ? $body['visibility'] : 'private';

        $latitude = (isset($body['latitude']) && $body['latitude'] !== '' && is_numeric($body['latitude'])) ? floatval($body['latitude']) : null;
        $longitude = (isset($body['longitude']) && $body['longitude'] !== '' && is_numeric($body['longitude'])) ? floatval($body['longitude']) : null;

        $db->prepare("
            INSERT INTO calendar_events
              (id, owner_id, company_id, project_id, category_id, title, description, location,
               latitude, longitude, start_at, end_at, all_day, priority, status, visibility, color, reminder_minutes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', ?, ?, ?)
        ")->execute([
            $id, $user['id'], $user['company_id'] ?? null,
            $body['project_id'] ?? null, $body['category_id'] ?? null,
            $title, $body['description'] ?? null, $body['location'] ?? null,
            $latitude, $longitude,
            $startAt, $endAt, $allDay, $priority, $visibility,
            $body['color'] ?? null,
            isset($body['reminder_minutes']) ? (int)$body['reminder_minutes'] : null
        ]);

        // Organisator
        $db->prepare("
            INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
            VALUES (?, ?, ?, ?, ?, 'required', 'accepted', 1)
        ")->execute(['att_' . substr(bin2hex(random_bytes(6)), 0, 8), $id, $user['id'], $user['email'], $user['name']]);

        // Teilnehmer einladen
        $invited = [];
        $attendees = is_array($body['attendees'] ?? null) ? $body['attendees'] : [];
        foreach ($attendees as $a) {
            $email = strtolower(trim($a['email'] ?? ''));
            if (!$email || $email === strtolower($user['email'])) continue;

            $uStmt = $db->prepare("SELECT id, name FROM users WHERE LOWER(email) = ?");
            $uStmt->execute([$email]);
            $existing = $uStmt->fetch();
            $name = $a['name'] ?? ($existing['name'] ?? null);
            $role = in_array($a['role'] ?? '', ['required', 'optional'], true) ? $a['role'] : 'required';

            try {
                $db->prepare("
                    INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
                    VALUES (?, ?, ?, ?, ?, ?, 'pending', 0)
                ")->execute(['att_' . substr(bin2hex(random_bytes(6)), 0, 8), $id, $existing['id'] ?? null, $email, $name, $role]);
            } catch (Exception $e) {
                continue;
            }

            $invited[] = ['email' => $email, 'name' => $name, 'user_id' => $existing['id'] ?? null];

            if (!empty($existing['id'])) {
                createNotification($existing['id'], 'calendar_invite', 'Einladung: ' . $title, $user['name'] . ' lädt dich ein – ' . $startAt, 'event', $id);
            }
        }

        // E-Mail-Einladungen
        if (!empty($invited)) {
            $evtForIcs = [
                'id' => $id, 'title' => $title, 'description' => $body['description'] ?? null,
                'location' => $body['location'] ?? null, 'start_at' => $startAt, 'end_at' => $endAt,
                'all_day' => $allDay, 'owner_name' => $user['name'], 'owner_email' => $user['email'],
                'status' => 'confirmed'
            ];
            $ics = buildIcs($evtForIcs, $invited, 'REQUEST');
            $mailBody = buildInviteBody($user['name'], $title, $startAt, $endAt, $body['location'] ?? null, $body['description'] ?? null, (bool)$allDay);

            foreach ($invited as $i) {
                queueEmail($i['email'], $i['name'], 'Einladung: ' . $title, $mailBody, $ics);
            }
        }

        jsonResponse([
            'success' => true, 'id' => $id, 'invited' => count($invited),
            'message' => count($invited) > 0
                ? 'Termin erstellt, ' . count($invited) . ' Einladung(en) versandt.'
                : 'Termin erstellt.'
        ]);
    }

    // EVT-3. PUT events/:id (auch Drag & Drop)
    if (preg_match('#^events/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $id = $m[1];

        $eStmt = $db->prepare("SELECT * FROM calendar_events WHERE id = ?");
        $eStmt->execute([$id]);
        $existing = $eStmt->fetch();
        if (!$existing) errorResponse('Termin nicht gefunden', 404);
        // Zero-Trust: fehlende Berechtigung = "nicht gefunden" (kein Info-Leak)
        if (!canEditEvent($user, $existing)) errorResponse('Termin nicht gefunden', 404);

        $title = isset($body['title']) ? trim($body['title']) : $existing['title'];
        $startAt = isset($body['start_at']) ? $body['start_at'] : $existing['start_at'];
        $endAt = isset($body['end_at']) ? $body['end_at'] : $existing['end_at'];
        if (!$title) errorResponse('Betreff erforderlich', 400);
        if ($endAt < $startAt) errorResponse('Ende darf nicht vor dem Start liegen', 400);

        $normStartNew = str_replace('T', ' ', trim($startAt));
        if (strlen($normStartNew) === 16) $normStartNew .= ':00';

        $normStartOld = str_replace('T', ' ', trim($existing['start_at']));
        if (strlen($normStartOld) === 16) $normStartOld .= ':00';

        $normEndNew = str_replace('T', ' ', trim($endAt));
        if (strlen($normEndNew) === 16) $normEndNew .= ':00';

        $normEndOld = str_replace('T', ' ', trim($existing['end_at']));
        if (strlen($normEndOld) === 16) $normEndOld .= ':00';

        $timeChanged = ($normStartNew !== $normStartOld || $normEndNew !== $normEndOld);

        $latitude = array_key_exists('latitude', $body)
            ? (($body['latitude'] === null || $body['latitude'] === '') ? null : floatval($body['latitude']))
            : $existing['latitude'];
        $longitude = array_key_exists('longitude', $body)
            ? (($body['longitude'] === null || $body['longitude'] === '') ? null : floatval($body['longitude']))
            : $existing['longitude'];

        $db->prepare("
            UPDATE calendar_events SET
              title = ?, description = ?, location = ?, latitude = ?, longitude = ?,
              start_at = ?, end_at = ?,
              all_day = ?, priority = ?, visibility = ?, category_id = ?, project_id = ?,
              color = ?, reminder_minutes = ?, updated_at = NOW()
            WHERE id = ?
        ")->execute([
            $title,
            $body['description'] ?? $existing['description'],
            $body['location'] ?? $existing['location'],
            $latitude, $longitude,
            $startAt, $endAt,
            isset($body['all_day']) ? (!empty($body['all_day']) ? 1 : 0) : $existing['all_day'],
            $body['priority'] ?? $existing['priority'],
            $body['visibility'] ?? $existing['visibility'],
            $body['category_id'] ?? $existing['category_id'],
            $body['project_id'] ?? $existing['project_id'],
            $body['color'] ?? $existing['color'],
            $body['reminder_minutes'] ?? $existing['reminder_minutes'],
            $id
        ]);

        // Teilnehmer aktualisieren
        if (is_array($body['attendees'] ?? null)) {
            $keep = [strtolower($user['email'])];
            $newInvites = [];
            foreach ($body['attendees'] as $a) {
                $email = strtolower(trim($a['email'] ?? ''));
                if (!$email) continue;
                $keep[] = $email;

                $chk = $db->prepare("SELECT id FROM event_attendees WHERE event_id = ? AND LOWER(email) = ?");
                $chk->execute([$id, $email]);
                $att = $chk->fetch();

                if ($att) {
                    $db->prepare("UPDATE event_attendees SET role = ?, name = COALESCE(?, name) WHERE id = ?")
                        ->execute([($a['role'] ?? '') === 'optional' ? 'optional' : 'required', $a['name'] ?? null, $att['id']]);
                } else {
                    $uStmt = $db->prepare("SELECT id, name FROM users WHERE LOWER(email) = ?");
                    $uStmt->execute([$email]);
                    $u = $uStmt->fetch();
                    $db->prepare("
                        INSERT INTO event_attendees (id, event_id, user_id, email, name, role, status, is_organizer)
                        VALUES (?, ?, ?, ?, ?, ?, 'pending', 0)
                    ")->execute(['att_' . substr(bin2hex(random_bytes(6)), 0, 8), $id, $u['id'] ?? null, $email, $a['name'] ?? ($u['name'] ?? null), ($a['role'] ?? '') === 'optional' ? 'optional' : 'required']);
                    $newInvites[] = ['email' => $email, 'name' => $a['name'] ?? ($u['name'] ?? null), 'user_id' => $u['id'] ?? null];
                }
            }

            // Entfernte loeschen
            $allStmt = $db->prepare("SELECT id, email, is_organizer FROM event_attendees WHERE event_id = ?");
            $allStmt->execute([$id]);
            foreach ($allStmt->fetchAll() as $a) {
                if (!empty($a['is_organizer'])) continue;
                if (!in_array(strtolower($a['email']), $keep, true)) {
                    $db->prepare("DELETE FROM event_attendees WHERE id = ?")->execute([$a['id']]);
                }
            }

            // Neue benachrichtigen
            foreach ($newInvites as $i) {
                if (!empty($i['user_id'])) {
                    createNotification($i['user_id'], 'calendar_invite', 'Einladung: ' . $title, $user['name'] . ' lädt dich ein – ' . $startAt, 'event', $id);
                }
                $evtForIcs = [
                    'id' => $id, 'title' => $title, 'description' => $body['description'] ?? $existing['description'],
                    'location' => $body['location'] ?? $existing['location'], 'start_at' => $startAt, 'end_at' => $endAt,
                    'all_day' => $body['all_day'] ?? $existing['all_day'], 'owner_name' => $user['name'],
                    'owner_email' => $user['email'], 'status' => 'confirmed'
                ];
                queueEmail($i['email'], $i['name'], 'Einladung: ' . $title,
                    buildInviteBody($user['name'], $title, $startAt, $endAt, $body['location'] ?? $existing['location'], $body['description'] ?? $existing['description'], (bool)($body['all_day'] ?? $existing['all_day'])),
                    buildIcs($evtForIcs, [$i], 'REQUEST'));
            }
        }

        // Bei Zeitänderung alle bisherigen Teilnehmer benachrichtigen (außer wer gerade erst neu eingeladen wurde)
        if ($timeChanged) {
            $newInvitedEmails = !empty($newInvites) ? array_map(function($i) { return strtolower($i['email']); }, $newInvites) : [];

            $aStmt = $db->prepare("SELECT email, name, user_id FROM event_attendees WHERE event_id = ? AND is_organizer = 0");
            $aStmt->execute([$id]);
            $attendees = $aStmt->fetchAll();

            $evtForIcs = [
                'id' => $id, 'title' => $title, 'description' => $body['description'] ?? $existing['description'],
                'location' => $body['location'] ?? $existing['location'], 'start_at' => $startAt, 'end_at' => $endAt,
                'all_day' => $body['all_day'] ?? $existing['all_day'], 'owner_name' => $user['name'],
                'owner_email' => $user['email'], 'status' => 'confirmed'
            ];
            $ics = buildIcs($evtForIcs, $attendees, 'REQUEST', 1);

            foreach ($attendees as $a) {
                if (in_array(strtolower($a['email']), $newInvitedEmails, true)) {
                    continue; // Hat bereits die Einladung mit dem neuen Zeitpunkt erhalten
                }
                if (!empty($a['user_id'])) {
                    createNotification($a['user_id'], 'calendar_update', 'Termin verschoben: ' . $title, 'Neuer Zeitpunkt: ' . $startAt, 'event', $id);
                }
                queueEmail($a['email'], $a['name'], 'Termin verschoben: ' . $title,
                    buildInviteBody($user['name'], $title, $startAt, $endAt, $body['location'] ?? $existing['location'], $body['description'] ?? $existing['description'], (bool)($body['all_day'] ?? $existing['all_day'])),
                    $ics);
            }
        }

        jsonResponse(['success' => true, 'timeChanged' => $timeChanged]);
    }

    // EVT-4. DELETE events/:id
    if (preg_match('#^events/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $id = $m[1];

        $eStmt = $db->prepare("SELECT * FROM calendar_events WHERE id = ?");
        $eStmt->execute([$id]);
        $existing = $eStmt->fetch();
        if (!$existing) errorResponse('Termin nicht gefunden', 404);
        // Zero-Trust: fehlende Berechtigung = "nicht gefunden" (kein Info-Leak)
        if (!canEditEvent($user, $existing)) errorResponse('Termin nicht gefunden', 404);

        $aStmt = $db->prepare("SELECT email, name, user_id FROM event_attendees WHERE event_id = ? AND is_organizer = 0");
        $aStmt->execute([$id]);
        $attendees = $aStmt->fetchAll();

        if (!empty($attendees)) {
            $evtForIcs = [
                'id' => $id, 'title' => $existing['title'], 'description' => $existing['description'],
                'location' => $existing['location'], 'start_at' => $existing['start_at'],
                'end_at' => $existing['end_at'], 'all_day' => $existing['all_day'],
                'owner_name' => $user['name'], 'owner_email' => $user['email'], 'status' => 'cancelled'
            ];
            $ics = buildIcs($evtForIcs, $attendees, 'CANCEL', 2);

            foreach ($attendees as $a) {
                if (!empty($a['user_id'])) {
                    createNotification($a['user_id'], 'calendar_cancel', 'Termin abgesagt: ' . $existing['title'], $user['name'] . ' hat den Termin abgesagt.', 'event', $id);
                }
                queueEmail($a['email'], $a['name'], 'Termin abgesagt: ' . $existing['title'],
                    $user['name'] . ' hat den Termin "' . $existing['title'] . '" abgesagt.' . "\n\nBeginn war: " . $existing['start_at'] . "\n\n— Taskster",
                    $ics);
            }
        }

        $db->prepare("DELETE FROM calendar_events WHERE id = ?")->execute([$id]);
        jsonResponse(['success' => true, 'notified' => count($attendees)]);
    }

    // EVT-5. POST events/:id/respond (Zusage/Absage)
    if (preg_match('#^events/([^/]+)/respond$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $id = $m[1];
        $status = in_array($body['status'] ?? '', ['accepted', 'declined', 'tentative'], true) ? $body['status'] : null;
        if (!$status) errorResponse('Ungültiger Status', 400);

        $eStmt = $db->prepare("SELECT * FROM calendar_events WHERE id = ?");
        $eStmt->execute([$id]);
        $evt = $eStmt->fetch();
        if (!$evt) errorResponse('Termin nicht gefunden', 404);

        $aStmt = $db->prepare("SELECT id FROM event_attendees WHERE event_id = ? AND (user_id = ? OR LOWER(email) = LOWER(?))");
        $aStmt->execute([$id, $user['id'], $user['email']]);
        $att = $aStmt->fetch();
        if (!$att) errorResponse('Du bist nicht zu diesem Termin eingeladen', 404);

        $db->prepare("UPDATE event_attendees SET status = ?, user_id = COALESCE(user_id, ?), responded_at = NOW() WHERE id = ?")->execute([$status, $user['id'], $att['id']]);

        $label = $status === 'accepted' ? 'zugesagt' : ($status === 'declined' ? 'abgesagt' : 'mit Vorbehalt zugesagt');
        createNotification($evt['owner_id'], 'calendar_response', 'Antwort: ' . $evt['title'], $user['name'] . ' hat ' . $label . '.', 'event', $id);

        jsonResponse(['success' => true, 'status' => $status]);
    }

    // EVT-6. GET events/:id/ics (Download)
    if (preg_match('#^events/([^/]+)/ics$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $id = $m[1];

        $eStmt = $db->prepare("
            SELECT e.*, u.name AS owner_name, u.email AS owner_email
            FROM calendar_events e LEFT JOIN users u ON u.id = e.owner_id
            WHERE e.id = ?
        ");
        $eStmt->execute([$id]);
        $evt = $eStmt->fetch();
        if (!$evt || !canAccessEvent($user, $evt)) errorResponse('Termin nicht gefunden', 404);

        $aStmt = $db->prepare("SELECT email, name, status FROM event_attendees WHERE event_id = ?");
        $aStmt->execute([$id]);
        $attendees = $aStmt->fetchAll();

        $ics = buildIcs($evt, $attendees, 'PUBLISH');

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="termin-' . $evt['id'] . '.ics"');
        echo $ics;
        exit;
    }

    // EVT-7. GET event-categories
    if ($path === 'event-categories' && $method === 'GET') {
        $user = requireAuth();
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';
        $stmt = $db->prepare("
            SELECT * FROM event_categories
            WHERE is_system = 1 OR (company_id IS NOT NULL AND company_id = ?) OR owner_id = ?
            ORDER BY is_system DESC, sort_order ASC, name ASC
        ");
        $stmt->execute([$companyId, $user['id']]);
        jsonResponse(['categories' => $stmt->fetchAll()]);
    }

    // EVT-8. POST event-categories
    if ($path === 'event-categories' && $method === 'POST') {
        $user = requireAuth();
        $name = trim($body['name'] ?? '');
        if (!$name) errorResponse('Kategoriename erforderlich', 400);

        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $body['color'] ?? '') ? $body['color'] : '#0891B2';
        $icon = substr($body['icon'] ?? 'Calendar', 0, 40);
        $companyWide = !empty($body['company_wide']) && !empty($user['company_id']);

        $id = 'cat_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $maxSort = (int)$db->query("SELECT COALESCE(MAX(sort_order), 0) FROM event_categories")->fetchColumn();

        $db->prepare("
            INSERT INTO event_categories (id, company_id, owner_id, name, color, icon, is_system, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, 0, ?)
        ")->execute([
            $id,
            $companyWide ? $user['company_id'] : null,
            $companyWide ? null : $user['id'],
            $name, $color, $icon, $maxSort + 1
        ]);

        jsonResponse(['success' => true, 'id' => $id]);
    }

    // EVT-9. PUT event-categories/:id
    if (preg_match('#^event-categories/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $catId = $m[1];
        $stmt = $db->prepare("SELECT owner_id, company_id, is_system FROM event_categories WHERE id = ?");
        $stmt->execute([$catId]);
        $existing = $stmt->fetch();
        if (!$existing) errorResponse('Kategorie nicht gefunden', 404);

        if (!empty($existing['company_id'])) {
            if ($existing['company_id'] !== $user['company_id'] || ($user['company_role'] ?? '') !== 'admin') {
                errorResponse('Keine Berechtigung zum Bearbeiten von Firmenkategorien', 403);
            }
        } elseif (!empty($existing['owner_id']) && $existing['owner_id'] !== $user['id']) {
            errorResponse('Keine Berechtigung', 403);
        }

        $name = trim($body['name'] ?? '');
        if (!$name) errorResponse('Kategoriename erforderlich', 400);

        $color = preg_match('/^#[0-9A-Fa-f]{6}$/', $body['color'] ?? '') ? $body['color'] : '#0891B2';
        $companyWide = !empty($body['company_wide']) && !empty($user['company_id']);

        $db->prepare("
            UPDATE event_categories
            SET name = ?, color = ?, company_id = ?, owner_id = ?
            WHERE id = ?
        ")->execute([
            $name, $color,
            $companyWide ? $user['company_id'] : null,
            $companyWide ? null : $user['id'],
            $catId
        ]);

        jsonResponse(['success' => true]);
    }

    // EVT-10. DELETE event-categories/:id
    if (preg_match('#^event-categories/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $catId = $m[1];
        $stmt = $db->prepare("SELECT owner_id, company_id, is_system FROM event_categories WHERE id = ?");
        $stmt->execute([$catId]);
        $existing = $stmt->fetch();
        if (!$existing) errorResponse('Kategorie nicht gefunden', 404);

        if (!empty($existing['company_id'])) {
            if ($existing['company_id'] !== $user['company_id'] || ($user['company_role'] ?? '') !== 'admin') {
                errorResponse('Keine Berechtigung zum Löschen von Firmenkategorien', 403);
            }
        } elseif (!empty($existing['owner_id']) && $existing['owner_id'] !== $user['id']) {
            errorResponse('Keine Berechtigung', 403);
        }

        // Unlink category from any existing calendar events
        $db->prepare("UPDATE calendar_events SET category_id = NULL WHERE category_id = ?")->execute([$catId]);
        $db->prepare("DELETE FROM event_categories WHERE id = ?")->execute([$catId]);
        jsonResponse(['success' => true]);
    }

    // 17. GET admin/overview
    if ($path === 'admin/overview' && $method === 'GET') {
        $user = requireAdminPermission('any_admin');
        if (!empty($user['is_superadmin'])) {
            $uCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $cCount = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
            $pCount = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
            $tCount = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
            $jCount = $db->query("SELECT COUNT(*) FROM project_journals")->fetchColumn();
        } else {
            $compId = $user['company_id'];
            $uStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE company_id = ?");
            $uStmt->execute([$compId]);
            $uCount = $uStmt->fetchColumn();

            $cCount = 1;

            $pStmt = $db->prepare("SELECT COUNT(*) FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ?");
            $pStmt->execute([$compId]);
            $pCount = $pStmt->fetchColumn();

            $tStmt = $db->prepare("SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id JOIN projects p ON p.id = l.project_id JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ?");
            $tStmt->execute([$compId]);
            $tCount = $tStmt->fetchColumn();

            $jStmt = $db->prepare("SELECT COUNT(*) FROM project_journals pj JOIN projects p ON p.id = pj.project_id JOIN project_folders pf ON pf.id = p.folder_id WHERE pf.company_id = ?");
            $jStmt->execute([$compId]);
            $jCount = $jStmt->fetchColumn();
        }

        jsonResponse([
            'metrics' => [
                'users' => (int)$uCount,
                'companies' => (int)$cCount,
                'projects' => (int)$pCount,
                'tasks' => (int)$tCount,
                'journals' => (int)$jCount
            ]
        ]);
    }

    // 18. GET admin/users
    if ($path === 'admin/users' && $method === 'GET') {
        $user = requireAdminPermission('manage_users');
        if (!empty($user['is_superadmin'])) {
            $stmt = $db->query("
                SELECT u.*, c.name as company_name, c.subscription_plan as company_plan
                FROM users u
                LEFT JOIN companies c ON c.id = u.company_id
                ORDER BY u.created_at DESC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT u.*, c.name as company_name, c.subscription_plan as company_plan
                FROM users u
                LEFT JOIN companies c ON c.id = u.company_id
                WHERE u.company_id = ?
                ORDER BY u.created_at DESC
            ");
            $stmt->execute([$user['company_id']]);
        }
        $users = array_map(function($u) {
            $u['is_superadmin'] = (bool)$u['is_superadmin'];
            $u['is_pro'] = (bool)$u['is_pro'];
            $perms = !empty($u['admin_permissions']) ? (is_string($u['admin_permissions']) ? json_decode($u['admin_permissions'], true) : $u['admin_permissions']) : [];
            $u['admin_permissions'] = is_array($perms) ? $perms : [];
            return $u;
        }, $stmt->fetchAll());

        jsonResponse(['users' => $users]);
    }

    // 18b. POST admin/users (Direktes Anlegen eines neuen Benutzers)
    if ($path === 'admin/users' && $method === 'POST') {
        $authUser = requireAdminPermission('manage_users');
        $name = trim($body['name'] ?? '');
        $email = strtolower(trim($body['email'] ?? ''));
        $password = $body['password'] ?? '';
        $companyId = !empty($body['company_id']) ? $body['company_id'] : null;
        $companyRole = !empty($body['company_role']) ? $body['company_role'] : 'member';
        $isSuperadmin = !empty($body['is_superadmin']) ? 1 : 0;
        $isPro = !empty($body['is_pro']) ? 1 : 0;
        $adminPermissions = $body['admin_permissions'] ?? [];

        if (!$name || !$email || !$password) {
            errorResponse('Name, E-Mail und Passwort sind erforderlich', 400);
        }

        // Sicherheitsregeln für Company-Admins:
        if (empty($authUser['is_superadmin'])) {
            $companyId = $authUser['company_id'];
            $isSuperadmin = 0;
            $isPro = 1;
        }

        $chk = $db->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            errorResponse('Ein Benutzer mit dieser E-Mail existiert bereits', 400);
        }

        $newUserId = 'usr_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $pwHash = password_hash($password, PASSWORD_BCRYPT);
        $permsJson = json_encode(is_array($adminPermissions) ? $adminPermissions : []);

        $db->prepare("
            INSERT INTO users (id, name, email, password_hash, company_id, company_role, is_superadmin, is_pro, admin_permissions)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $newUserId, $name, $email, $pwHash, $companyId, $companyRole, $isSuperadmin, $isPro, $permsJson
        ]);

        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $newUserId,
                'name' => $name,
                'email' => $email,
                'company_id' => $companyId,
                'company_role' => $companyRole,
                'is_superadmin' => (bool)$isSuperadmin,
                'is_pro' => (bool)$isPro,
                'admin_permissions' => is_array($adminPermissions) ? $adminPermissions : []
            ]
        ]);
    }

    // 19. PATCH admin/users/:id (Update user settings: pro status, company, role, superadmin, permissions)
    if (preg_match('#^admin/users/([^/]+)$#', $path, $m) && $method === 'PATCH') {
        $authUser = requireAdminPermission('manage_users');
        $targetId = $m[1];
        
        $tStmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $tStmt->execute([$targetId]);
        $targetUser = $tStmt->fetch();
        if (!$targetUser) errorResponse('Benutzer nicht gefunden', 404);

        if (empty($authUser['is_superadmin']) && $targetUser['company_id'] !== $authUser['company_id']) {
            errorResponse('Benutzer nicht gefunden', 404);
        }

        $fields = [];
        $params = [];

        if (isset($body['is_pro'])) {
            $fields[] = "is_pro = ?";
            $params[] = $body['is_pro'] ? 1 : 0;
        }
        if (isset($body['is_superadmin']) && !empty($authUser['is_superadmin'])) {
            $fields[] = "is_superadmin = ?";
            $params[] = $body['is_superadmin'] ? 1 : 0;
        }
        if (array_key_exists('company_id', $body) && !empty($authUser['is_superadmin'])) {
            $fields[] = "company_id = ?";
            $params[] = !empty($body['company_id']) ? $body['company_id'] : null;
        }
        if (array_key_exists('company_role', $body)) {
            $fields[] = "company_role = ?";
            $params[] = !empty($body['company_role']) ? $body['company_role'] : null;
        }
        if (array_key_exists('admin_permissions', $body)) {
            $fields[] = "admin_permissions = ?";
            $perms = is_array($body['admin_permissions']) ? $body['admin_permissions'] : [];
            $params[] = json_encode($perms);
        }
        if (!empty($body['name'])) {
            $fields[] = "name = ?";
            $params[] = trim($body['name']);
        }
        if (!empty($body['email'])) {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($body['email']));
        }
        if (!empty($body['password'])) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
        }

        if (!empty($fields)) {
            $params[] = $targetId;
            $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        }

        jsonResponse(['success' => true]);
    }

    // 19b. GET admin/orders (Bestellungen, Abonnements & Finanz-Übersicht)
    if ($path === 'admin/orders' && $method === 'GET') {
        $authUser = requireAdminPermission('finance');
        
        if (!empty($authUser['is_superadmin'])) {
            $cStmt = $db->query("
                SELECT c.*,
                  (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
                  (SELECT email FROM users u WHERE u.company_id = c.id AND u.company_role = 'admin' LIMIT 1) as billing_email
                FROM companies c
                ORDER BY c.created_at DESC
            ");
        } else {
            $cStmt = $db->prepare("
                SELECT c.*,
                  (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
                  (SELECT email FROM users u WHERE u.company_id = c.id AND u.company_role = 'admin' LIMIT 1) as billing_email
                FROM companies c
                WHERE c.id = ?
            ");
            $cStmt->execute([$authUser['company_id']]);
        }
        $compRows = $cStmt->fetchAll();

        $planPrices = [
            'starter' => ['name' => 'Starter Plan', 'monthly' => 0, 'seats' => 5],
            'pro' => ['name' => 'Pro Business Plan', 'monthly' => 49, 'seats' => 25],
            'enterprise' => ['name' => 'Enterprise Custom Plan', 'monthly' => 189, 'seats' => 100]
        ];

        $orders = [];
        $totalMrr = 0;
        $totalSeats = 0;

        foreach ($compRows as $comp) {
            $planKey = strtolower($comp['subscription_plan'] ?? 'starter');
            $pInfo = $planPrices[$planKey] ?? ['name' => ucfirst($planKey), 'monthly' => 29, 'seats' => 10];
            $price = $pInfo['monthly'];
            $totalMrr += $price;
            $userCount = (int)($comp['user_count'] ?? 1);
            $totalSeats += $userCount;

            $settings = !empty($comp['settings']) ? (is_string($comp['settings']) ? json_decode($comp['settings'], true) : $comp['settings']) : [];

            $orders[] = [
                'id' => 'ord_' . substr(md5($comp['id']), 0, 8),
                'company_id' => $comp['id'],
                'company_name' => $comp['name'],
                'plan_key' => $planKey,
                'plan_name' => $pInfo['name'],
                'amount_monthly' => $price,
                'currency' => 'CHF',
                'payment_status' => $price > 0 ? 'paid' : 'active',
                'payment_method' => $price > 0 ? 'Kreditkarte (•••• 4242)' : 'Kostenlos',
                'active_seats' => $userCount,
                'max_seats' => $settings['max_seats'] ?? $pInfo['seats'],
                'billing_cycle' => 'Monatlich',
                'billing_email' => $comp['billing_email'] ?? 'admin@' . strtolower(preg_replace('/[^a-z0-9]/', '', $comp['name'])) . '.ch',
                'created_at' => $comp['created_at'],
                'next_renewal' => date('Y-m-d', strtotime($comp['created_at'] . ' + 1 month'))
            ];
        }

        jsonResponse([
            'orders' => $orders,
            'summary' => [
                'mrr' => $totalMrr,
                'total_subscriptions' => count($orders),
                'total_seats' => $totalSeats,
                'currency' => 'CHF'
            ]
        ]);
    }

    // 20. GET admin/companies
    if ($path === 'admin/companies' && $method === 'GET') {
        $authUser = requireAdminPermission('company_settings');
        if (!empty($authUser['is_superadmin'])) {
            $stmt = $db->query("
                SELECT c.*,
                  (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
                  (SELECT COUNT(*) FROM project_folders pf WHERE pf.company_id = c.id) as folder_count
                FROM companies c
                ORDER BY c.created_at DESC
            ");
        } else {
            $stmt = $db->prepare("
                SELECT c.*,
                  (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
                  (SELECT COUNT(*) FROM project_folders pf WHERE pf.company_id = c.id) as folder_count
                FROM companies c
                WHERE c.id = ?
            ");
            $stmt->execute([$authUser['company_id']]);
        }
        $companies = array_map(function($c) {
            $c['settings'] = !empty($c['settings']) ? (is_string($c['settings']) ? json_decode($c['settings'], true) : $c['settings']) : [];
            return $c;
        }, $stmt->fetchAll());

        jsonResponse(['companies' => $companies]);
    }

    // 21. POST admin/companies
    if ($path === 'admin/companies' && $method === 'POST') {
        requireSuperadmin();
        $name = trim($body['name'] ?? '');
        $plan = $body['subscription_plan'] ?? 'starter';
        $adminName = trim($body['admin_name'] ?? '');
        $adminEmail = strtolower(trim($body['admin_email'] ?? ''));

        $companyId = 'comp_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO companies (id, name, subscription_plan, settings) VALUES (?, ?, ?, ?)")->execute([
            $companyId, $name, $plan, json_encode(['allow_document_upload' => true, 'max_seats' => 25])
        ]);

        if ($adminName && $adminEmail) {
            $adminId = 'usr_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $pwHash = password_hash('taskster2026!', PASSWORD_BCRYPT);
            // WICHTIG: Company Admins erhalten KEINE admin_permissions (sonst Plattform-Admin!).
            // Sie verwalten ihre Firma ueber company_role === 'admin' im /company Portal.
            $db->prepare("INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash) VALUES (?, ?, 'admin', 0, 1, ?, ?, ?)")->execute([
                $adminId, $companyId, $adminName, $adminEmail, $pwHash
            ]);
            $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name) VALUES (?, ?, ?, ?)")->execute([
                'fld_' . substr(bin2hex(random_bytes(6)), 0, 8), $adminId, $companyId, "{$name} - Hauptordner"
            ]);
        }

        jsonResponse(['success' => true]);
    }

    // 22. PATCH admin/companies/:id
    if (preg_match('#^admin/companies/([^/]+)$#', $path, $m) && $method === 'PATCH') {
        $authUser = requireAdminPermission('company_settings');
        $compId = $m[1];
        if (empty($authUser['is_superadmin']) && $compId !== $authUser['company_id']) {
            errorResponse('Nicht autorisiert für dieses Unternehmen', 403);
        }

        if (isset($body['subscription_plan']) && !empty($authUser['is_superadmin'])) {
            $db->prepare("UPDATE companies SET subscription_plan = ? WHERE id = ?")->execute([$body['subscription_plan'], $compId]);
        }
        if (isset($body['settings'])) {
            $db->prepare("UPDATE companies SET settings = ? WHERE id = ?")->execute([json_encode($body['settings']), $compId]);
        }
        jsonResponse(['success' => true]);
    }

    // ----------------------------------------------------
    // PROJECT TEMPLATES ENDPOINTS
    // ----------------------------------------------------

    // 23. GET templates
    if ($path === 'templates' && $method === 'GET') {
        $user = requireAuth();
        $cat = $_GET['category'] ?? null;
        $q = trim($_GET['q'] ?? '');

        // Zero-Trust: Systemvorlagen (is_system = 1) sind fuer alle sichtbar.
        // Firmenvorlagen (is_system = 0) NUR fuer das eigene Unternehmen.
        $sql = "SELECT * FROM project_templates WHERE (is_system = 1 OR company_id = ?)";
        $params = [$user['company_id'] ?? '__none__'];
        if ($cat && $cat !== 'all') {
            $sql .= " AND category = ?";
            $params[] = $cat;
        }
        if ($q) {
            $sql .= " AND (name LIKE ? OR description LIKE ? OR subcategory LIKE ?)";
            $params[] = "%$q%";
            $params[] = "%$q%";
            $params[] = "%$q%";
        }
        $sql .= " ORDER BY is_system DESC, category ASC, name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $templates = array_map(function($t) {
            $t['lists'] = !empty($t['lists']) ? (is_string($t['lists']) ? json_decode($t['lists'], true) : $t['lists']) : [];
            $t['fields'] = !empty($t['fields']) ? (is_string($t['fields']) ? json_decode($t['fields'], true) : $t['fields']) : [];
            return $t;
        }, $stmt->fetchAll());

        jsonResponse(['templates' => $templates]);
    }

    // 24. GET templates/:id
    if (preg_match('#^templates/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $tmplId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
        $stmt->execute([$tmplId]);
        $t = $stmt->fetch();
        if (!$t) errorResponse('Vorlage nicht gefunden', 404);

        $t['lists'] = !empty($t['lists']) ? (is_string($t['lists']) ? json_decode($t['lists'], true) : $t['lists']) : [];
        $t['fields'] = !empty($t['fields']) ? (is_string($t['fields']) ? json_decode($t['fields'], true) : $t['fields']) : [];
        jsonResponse(['template' => $t]);
    }

    // 25. POST templates (Superadmin = Systemvorlage, Company Admin = Firmenvorlage)
    if ($path === 'templates' && $method === 'POST') {
        $user = requireAuth();
        $isSuperadmin = !empty($user['is_superadmin']);
        $isCompanyAdmin = !empty($user['company_id']) && ($user['company_role'] ?? '') === 'admin';
        if (!$isSuperadmin && !$isCompanyAdmin) {
            errorResponse('Nur Administratoren können Vorlagen verwalten', 403);
        }

        $name = trim($body['name'] ?? '');
        if (!$name) errorResponse('Vorlagenname erforderlich', 400);

        $tmplId = 'tmpl_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $category = in_array($body['category'] ?? '', ['job', 'private']) ? $body['category'] : 'job';
        $subcategory = trim($body['subcategory'] ?? '');
        $description = trim($body['description'] ?? '');
        $icon = trim($body['icon'] ?? 'Folder');
        $lists = $body['lists'] ?? [];
        $fields = $body['fields'] ?? [];

        $stmt = $db->prepare("INSERT INTO project_templates (id, name, category, subcategory, description, icon, is_system, company_id, lists, fields) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $tmplId, $name, $category, $subcategory, $description, $icon,
            $isSuperadmin ? 1 : 0,
            $isSuperadmin ? null : $user['company_id'],
            json_encode($lists),
            json_encode($fields)
        ]);

        jsonResponse(['success' => true, 'id' => $tmplId]);
    }

    // 26. PUT templates/:id (Eigentümer-Prüfung: Superadmin = alle, Company Admin = nur eigene Firma)
    if (preg_match('#^templates/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $isSuperadmin = !empty($user['is_superadmin']);
        $isCompanyAdmin = !empty($user['company_id']) && ($user['company_role'] ?? '') === 'admin';
        if (!$isSuperadmin && !$isCompanyAdmin) {
            errorResponse('Nur Administratoren können Vorlagen verwalten', 403);
        }
        $tmplId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
        $stmt->execute([$tmplId]);
        $existing = $stmt->fetch();
        if (!$existing) errorResponse('Vorlage nicht gefunden', 404);

        // Zero-Trust: Company Admin darf nur eigene Firmenvorlagen bearbeiten
        if (!$isSuperadmin) {
            if ((int)$existing['is_system'] === 1 || $existing['company_id'] !== $user['company_id']) {
                errorResponse('Vorlage nicht gefunden', 404);
            }
        }

        $name = trim($body['name'] ?? $existing['name']);
        $category = in_array($body['category'] ?? '', ['job', 'private']) ? $body['category'] : $existing['category'];
        $subcategory = isset($body['subcategory']) ? trim($body['subcategory']) : $existing['subcategory'];
        $description = isset($body['description']) ? trim($body['description']) : $existing['description'];
        $icon = isset($body['icon']) ? trim($body['icon']) : $existing['icon'];
        $lists = isset($body['lists']) ? json_encode($body['lists']) : $existing['lists'];
        $fields = isset($body['fields']) ? json_encode($body['fields']) : $existing['fields'];

        $upStmt = $db->prepare("UPDATE project_templates SET name = ?, category = ?, subcategory = ?, description = ?, icon = ?, lists = ?, fields = ? WHERE id = ?");
        $upStmt->execute([$name, $category, $subcategory, $description, $icon, $lists, $fields, $tmplId]);

        jsonResponse(['success' => true]);
    }

    // 27. DELETE templates/:id (Eigentümer-Prüfung: Superadmin = alle, Company Admin = nur eigene Firma)
    if (preg_match('#^templates/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $isSuperadmin = !empty($user['is_superadmin']);
        $isCompanyAdmin = !empty($user['company_id']) && ($user['company_role'] ?? '') === 'admin';
        if (!$isSuperadmin && !$isCompanyAdmin) {
            errorResponse('Nur Administratoren können Vorlagen verwalten', 403);
        }
        $tmplId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
        $stmt->execute([$tmplId]);
        $existing = $stmt->fetch();
        if (!$existing) errorResponse('Vorlage nicht gefunden', 404);

        if (!$isSuperadmin) {
            if ((int)$existing['is_system'] === 1 || $existing['company_id'] !== $user['company_id']) {
                errorResponse('Vorlage nicht gefunden', 404);
            }
        }

        $db->prepare("DELETE FROM project_templates WHERE id = ?")->execute([$tmplId]);
        jsonResponse(['success' => true]);
    }

    // ==========================================
    // DAILY TODOS ("Mein Tag" / 1-Tages-Fokus mit automatischem Rollover)
    // ==========================================

    // GET daily-todos (Rollover durchführen und heutige Todos abrufen)
    if ($path === 'daily-todos' && $method === 'GET') {
        $user = requireAuth();

        // Rollover Automatik: unvollendete Todos von Vortagen wandern automatisch auf heute
        try {
            $db->prepare("
                UPDATE daily_todos
                SET rollover_count = rollover_count + GREATEST(DATEDIFF(CURRENT_DATE(), target_date), 1),
                    original_date = COALESCE(original_date, target_date),
                    target_date = CURRENT_DATE()
                WHERE user_id = ? AND is_completed = 0 AND target_date < CURRENT_DATE()
            ")->execute([$user['id']]);
        } catch (Exception $e) {}

        // Heutige Todos laden
        $stmt = $db->prepare("
            SELECT dt.*, p.title as project_title, pf.name as folder_name, pf.icon as folder_icon
            FROM daily_todos dt
            LEFT JOIN projects p ON p.id = dt.project_id
            LEFT JOIN project_folders pf ON pf.id = p.folder_id
            WHERE dt.user_id = ?
              AND (dt.target_date = CURRENT_DATE() OR (dt.is_completed = 1 AND DATE(dt.completed_at) = CURRENT_DATE()))
            ORDER BY dt.is_completed ASC, dt.created_at DESC
        ");
        $stmt->execute([$user['id']]);
        $rawTodos = $stmt->fetchAll();

        $todos = array_map(function($t) {
            $t['is_completed'] = (bool)$t['is_completed'];
            $t['rollover_count'] = (int)$t['rollover_count'];
            return $t;
        }, $rawTodos);

        // Verfügbare Projekte für das Dropdown laden
        $pStmt = $db->prepare("
            SELECT p.id, p.title, pf.name as folder_name, pf.icon as folder_icon
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.owner_id = ? OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?) OR pf.id IN (SELECT folder_id FROM folder_members WHERE user_id = ?)
            ORDER BY p.title ASC
        ");
        $pStmt->execute([$user['id'], $user['id'], $user['id']]);
        $availableProjects = $pStmt->fetchAll();

        jsonResponse([
            'todos' => $todos,
            'date' => date('Y-m-d'),
            'availableProjects' => $availableProjects
        ]);
    }

    // POST daily-todos (Neues Tages-Todo anlegen)
    if ($path === 'daily-todos' && $method === 'POST') {
        $user = requireAuth();
        $title = trim($body['title'] ?? '');
        $projectId = !empty($body['project_id']) ? trim($body['project_id']) : null;

        if (!$title) {
            errorResponse('Titel darf nicht leer sein', 400);
        }

        if ($projectId) {
            evaluateProjectAccess($user, $projectId, 'read');
        }

        $id = 'dt_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $today = date('Y-m-d');

        $ins = $db->prepare("
            INSERT INTO daily_todos (id, user_id, project_id, title, target_date, is_completed, rollover_count, created_at)
            VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
        ");
        $ins->execute([$id, $user['id'], $projectId, $title, $today]);

        $projectTitle = null;
        if ($projectId) {
            $projectTitle = $db->query("SELECT title FROM projects WHERE id = " . $db->quote($projectId))->fetchColumn() ?: null;
        }

        jsonResponse([
            'success' => true,
            'todo' => [
                'id' => $id,
                'user_id' => $user['id'],
                'project_id' => $projectId,
                'project_title' => $projectTitle,
                'title' => $title,
                'target_date' => $today,
                'is_completed' => false,
                'rollover_count' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);
    }

    // PUT daily-todos/:id (Status toggeln / ändern)
    if (preg_match('#^daily-todos/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $todoId = $m[1];

        $stmt = $db->prepare("SELECT * FROM daily_todos WHERE id = ? AND user_id = ?");
        $stmt->execute([$todoId, $user['id']]);
        $existing = $stmt->fetch();
        if (!$existing) {
            errorResponse('Tages-Todo nicht gefunden', 404);
        }

        $isCompleted = isset($body['is_completed']) ? ($body['is_completed'] ? 1 : 0) : $existing['is_completed'];
        $title = isset($body['title']) ? trim($body['title']) : $existing['title'];
        $projectId = array_key_exists('project_id', $body) ? ($body['project_id'] ?: null) : $existing['project_id'];
        $completedAt = $isCompleted ? date('Y-m-d H:i:s') : null;

        $db->prepare("
            UPDATE daily_todos
            SET is_completed = ?, completed_at = ?, title = ?, project_id = ?
            WHERE id = ? AND user_id = ?
        ")->execute([$isCompleted, $completedAt, $title, $projectId, $todoId, $user['id']]);

        jsonResponse(['success' => true]);
    }

    // DELETE daily-todos/:id
    if (preg_match('#^daily-todos/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $todoId = $m[1];

        $db->prepare("DELETE FROM daily_todos WHERE id = ? AND user_id = ?")->execute([$todoId, $user['id']]);
        jsonResponse(['success' => true]);
    }

    // ==========================================
    // BENACHRICHTIGUNGEN (5 Event-Typen & Echtzeit-Checks)
    // ==========================================

    // GET notifications
    if ($path === 'notifications' && $method === 'GET') {
        $user = requireAuth();
        $uid = $user['id'];

        // 1. Gespeicherte Benachrichtigungen (Kommentare, Bearbeitungen, Einladungen & gespeicherte Status)
        $sStmt = $db->prepare("
            SELECT n.*
            FROM notifications n
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT 100
        ");
        $sStmt->execute([$uid]);
        $dbNotifs = $sStmt->fetchAll();

        $notifications = [];
        $readMap = [];

        foreach ($dbNotifs as $dn) {
            $readMap[$dn['id']] = (bool)$dn['is_read'];
            if (strpos($dn['id'], 'due_') !== 0 && strpos($dn['id'], 'budget_') !== 0) {
                $notifications[] = [
                    'id' => $dn['id'],
                    'type' => $dn['type'],
                    'title' => $dn['title'],
                    'message' => $dn['message'],
                    'reference_type' => $dn['reference_type'],
                    'reference_id' => $dn['reference_id'],
                    'project_id' => $dn['project_id'],
                    'is_read' => (bool)$dn['is_read'],
                    'created_at' => $dn['created_at'],
                    'is_realtime' => false
                ];
            }
        }

        // 2. Echtzeit-Ermittlung: Ablaufende Aufgaben in 3 Tagen (due_soon)
        $dStmt = $db->prepare("
            SELECT t.id, t.title, t.due_date, p.id as project_id, p.title as project_title,
                   DATEDIFF(t.due_date, CURRENT_DATE()) as days_left
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE (t.assigned_to = ? OR pf.owner_id = ? OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?))
              AND t.status != 'done'
              AND t.due_date IS NOT NULL
              AND t.due_date != ''
              AND t.due_date >= CURRENT_DATE()
              AND t.due_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)
            ORDER BY t.due_date ASC
            LIMIT 10
        ");
        $dStmt->execute([$uid, $uid, $uid]);
        $dueTasks = $dStmt->fetchAll();

        foreach ($dueTasks as $dt) {
            $nId = 'due_' . $dt['id'];
            $days = (int)$dt['days_left'];
            $dayText = $days === 0 ? 'Heute fällig' : ($days === 1 ? 'Morgen fällig' : "Fällig in {$days} Tagen");
            $notifications[] = [
                'id' => $nId,
                'type' => 'due_soon',
                'title' => "⏰ {$dayText}: {$dt['title']}",
                'message' => "Aufgabe im Projekt \"{$dt['project_title']}\" ist fällig am " . date('d.m.Y', strtotime($dt['due_date'])) . ".",
                'reference_type' => 'task',
                'reference_id' => $dt['id'],
                'project_id' => $dt['project_id'],
                'is_read' => !empty($readMap[$nId]),
                'created_at' => date('Y-m-d H:i:s', strtotime($dt['due_date'] . ' 08:00:00')),
                'is_realtime' => true
            ];
        }

        // 3. Echtzeit-Ermittlung: Budget erreicht im Projekt / Aufgabe (budget_exceeded)
        $bStmt = $db->prepare("
            SELECT p.id, p.title, p.currency, p.budget_hours, p.budget_amount,
                   COALESCE((SELECT SUM(duration_minutes) FROM time_entries WHERE project_id = p.id), 0) as tracked_minutes,
                   COALESCE((SELECT SUM(duration_minutes * hourly_rate / 60) FROM time_entries WHERE project_id = p.id), 0) as tracked_cost
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.owner_id = ?
              AND ((p.budget_hours > 0) OR (p.budget_amount > 0))
        ");
        $bStmt->execute([$uid]);
        $budgetProjects = $bStmt->fetchAll();

        foreach ($budgetProjects as $bp) {
            $bHours = floatval($bp['budget_hours'] ?? 0);
            $bAmount = floatval($bp['budget_amount'] ?? 0);
            $spentHours = round((int)$bp['tracked_minutes'] / 60, 1);
            $spentCost = round(floatval($bp['tracked_cost'] ?? 0), 2);

            $exceeded = false;
            $reason = '';
            if ($bHours > 0 && $spentHours >= $bHours) {
                $exceeded = true;
                $reason = "Stundenbudget erreicht: {$spentHours} / {$bHours} h";
            } elseif ($bAmount > 0 && $spentCost >= $bAmount) {
                $exceeded = true;
                $reason = "Kostenbudget erreicht: {$spentCost} / {$bAmount} {$bp['currency']}";
            }

            if ($exceeded) {
                $nId = 'budget_' . $bp['id'];
                $notifications[] = [
                    'id' => $nId,
                    'type' => 'budget_exceeded',
                    'title' => "💰 Budgetwarnung: {$bp['title']}",
                    'message' => "{$reason} im Projekt \"{$bp['title']}\".",
                    'reference_type' => 'project',
                    'reference_id' => $bp['id'],
                    'project_id' => $bp['id'],
                    'is_read' => !empty($readMap[$nId]),
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_realtime' => true
                ];
            }
        }

        // Nach Datum sortieren (neueste zuerst)
        usort($notifications, function($a, $b) {
            return strcmp($b['created_at'], $a['created_at']);
        });

        $unreadCount = 0;
        foreach ($notifications as $n) {
            if (empty($n['is_read'])) $unreadCount++;
        }

        jsonResponse([
            'notifications' => $notifications,
            'unreadCount' => $unreadCount
        ]);
    }

    // POST notifications/:id/read
    if (preg_match('#^notifications/([^/]+)/read$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $notifId = $m[1];
        $uid = $user['id'];

        $uStmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $uStmt->execute([$notifId, $uid]);

        if ($uStmt->rowCount() === 0) {
            $type = 'due_soon';
            $refType = 'task';
            $refId = null;
            if (strpos($notifId, 'due_') === 0) {
                $refId = substr($notifId, 4);
                $type = 'due_soon';
                $refType = 'task';
            } elseif (strpos($notifId, 'budget_') === 0) {
                $refId = substr($notifId, 7);
                $type = 'budget_exceeded';
                $refType = 'project';
            }

            try {
                $db->prepare("
                    INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
                    VALUES (?, ?, ?, 'Benachrichtigung', 'Gelesen', ?, ?, 1, NOW())
                ")->execute([$notifId, $uid, $type, $refType, $refId]);
            } catch (Exception $e) {
                // Ignore if duplicate
            }
        }

        jsonResponse(['success' => true]);
    }

    // POST notifications/read-all
    if ($path === 'notifications/read-all' && $method === 'POST') {
        $user = requireAuth();
        $uid = $user['id'];
        $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$uid]);

        // 1. due_soon Benachrichtigungen als gelesen speichern
        $dStmt = $db->prepare("
            SELECT t.id
            FROM tasks t
            JOIN lists l ON l.id = t.list_id
            JOIN projects p ON p.id = l.project_id
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE (t.assigned_to = ? OR pf.owner_id = ? OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?))
              AND t.status != 'done' AND t.due_date IS NOT NULL AND t.due_date != ''
              AND t.due_date >= CURRENT_DATE() AND t.due_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)
        ");
        $dStmt->execute([$uid, $uid, $uid]);
        foreach ($dStmt->fetchAll() as $dt) {
            $nId = 'due_' . $dt['id'];
            try {
                $db->prepare("
                    INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
                    VALUES (?, ?, 'due_soon', 'Fällig', 'Gelesen', 'task', ?, 1, NOW())
                ")->execute([$nId, $uid, $dt['id']]);
            } catch (Exception $e) {}
        }

        // 2. budget_exceeded Benachrichtigungen als gelesen speichern
        $bStmt = $db->prepare("
            SELECT p.id
            FROM projects p
            JOIN project_folders pf ON pf.id = p.folder_id
            WHERE pf.owner_id = ? AND ((p.budget_hours > 0) OR (p.budget_amount > 0))
        ");
        $bStmt->execute([$uid]);
        foreach ($bStmt->fetchAll() as $bp) {
            $nId = 'budget_' . $bp['id'];
            try {
                $db->prepare("
                    INSERT INTO notifications (id, user_id, type, title, message, reference_type, reference_id, is_read, created_at)
                    VALUES (?, ?, 'budget_exceeded', 'Budget', 'Gelesen', 'project', ?, 1, NOW())
                ")->execute([$nId, $uid, $bp['id']]);
            } catch (Exception $e) {}
        }

        jsonResponse(['success' => true]);
    }

    // 28. GET time-entries
    if ($path === 'time-entries' && $method === 'GET') {
        $user = requireAuth();
        $projectId = $_GET['project_id'] ?? null;
        $taskId = $_GET['task_id'] ?? null;
        $folderId = $_GET['folder_id'] ?? null;
        $filterUserId = $_GET['user_id'] ?? null;
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        $query = "
            SELECT te.*, u.name as user_name, u.email as user_email, t.title as task_title, p.title as project_title, p.currency as project_currency
            FROM time_entries te
            JOIN users u ON u.id = te.user_id
            JOIN projects p ON p.id = te.project_id
            LEFT JOIN tasks t ON t.id = te.task_id
            WHERE 1=1
        ";
        $params = [];

        if ($projectId) {
            evaluateProjectAccess($user, $projectId, 'read');
            $query .= " AND te.project_id = ?";
            $params[] = $projectId;
        } elseif ($taskId) {
            $tStmt = $db->prepare("SELECT list_id FROM tasks WHERE id = ?");
            $tStmt->execute([$taskId]);
            $tRow = $tStmt->fetch();
            if ($tRow) {
                evaluateListAccess($user, $tRow['list_id'], 'read');
            }
            $query .= " AND te.task_id = ?";
            $params[] = $taskId;
        } elseif ($folderId) {
            $fStmt = $db->prepare("SELECT owner_id FROM project_folders WHERE id = ?");
            $fStmt->execute([$folderId]);
            $fRow = $fStmt->fetch();
            if (!$fRow && empty($user['is_superadmin'])) {
                errorResponse('Ordner nicht gefunden', 404);
            }
            $query .= " AND te.project_id IN (SELECT id FROM projects WHERE folder_id = ?)";
            $params[] = $folderId;
        } else {
            // Scoping:
            // 1. Company Admin: Sieht alle Zeiteinträge seiner Firmenmitarbeiter & Firmenprojekte
            if (!empty($user['company_id']) && ($user['company_role'] ?? '') === 'admin') {
                $cId = $user['company_id'];
                $query .= " AND (
                    u.company_id = ?
                    OR p.folder_id IN (SELECT id FROM project_folders WHERE company_id = ?)
                    OR te.user_id = ?
                )";
                $params[] = $cId;
                $params[] = $cId;
                $params[] = $user['id'];
            } else {
                // 2. Regulärer Benutzer & Plattform-Admin (persönliche Arbeitszeiten):
                // Sieht nur eigene Einträge oder Einträge in Projekten/Ordnern, an denen er mitwirkt
                $uid = $user['id'];
                $query .= " AND (
                    te.user_id = ?
                    OR p.id IN (SELECT project_id FROM project_members WHERE user_id = ?)
                    OR p.folder_id IN (SELECT id FROM project_folders WHERE owner_id = ?)
                    OR p.folder_id IN (SELECT folder_id FROM folder_members WHERE user_id = ?)
                )";
                $params[] = $uid;
                $params[] = $uid;
                $params[] = $uid;
                $params[] = $uid;
            }
        }

        if ($filterUserId) {
            $query .= " AND te.user_id = ?";
            $params[] = $filterUserId;
        }
        if ($dateFrom) {
            $query .= " AND te.entry_date >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $query .= " AND te.entry_date <= ?";
            $params[] = $dateTo;
        }

        $query .= " ORDER BY te.entry_date DESC, te.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rawEntries = $stmt->fetchAll();

        $totalMinutes = 0;
        $totalCost = 0;
        $entries = array_map(function($e) use (&$totalMinutes, &$totalCost) {
            $e['is_manual'] = (bool)$e['is_manual'];
            $e['hourly_rate'] = $e['hourly_rate'] !== null ? floatval($e['hourly_rate']) : 0;
            $e['duration_minutes'] = (int)$e['duration_minutes'];
            $cost = round(($e['duration_minutes'] / 60) * $e['hourly_rate'], 2);
            $e['cost'] = $cost;
            $totalMinutes += $e['duration_minutes'];
            $totalCost += $cost;
            return $e;
        }, $rawEntries);

        jsonResponse([
            'entries' => $entries,
            'summary' => [
                'totalMinutes' => $totalMinutes,
                'totalHours' => round($totalMinutes / 60, 2),
                'totalCost' => round($totalCost, 2)
            ]
        ]);
    }

    // 29. POST time-entries
    if ($path === 'time-entries' && $method === 'POST') {
        $user = requireAuth();
        $planDetails = getUserPlanDetails($db, $user);
        if (empty($planDetails['time_tracking'])) {
            errorResponse('Zeiterfassung ist erst ab dem Pro-Tarif verfügbar.', 403);
        }
        $projectId = trim($body['project_id'] ?? '');
        $taskId = !empty($body['task_id']) ? trim($body['task_id']) : null;
        $durationMinutes = (int)($body['duration_minutes'] ?? 0);
        $entryDate = trim($body['entry_date'] ?? date('Y-m-d'));
        $description = trim($body['description'] ?? '');
        $hourlyRate = array_key_exists('hourly_rate', $body) && $body['hourly_rate'] !== null ? floatval($body['hourly_rate']) : floatval($user['hourly_rate'] ?? 0);

        if (!$projectId || $durationMinutes <= 0) {
            errorResponse('project_id und gültige duration_minutes erforderlich', 400);
        }

        $pAcc = evaluateProjectAccess($user, $projectId, 'write');
        if ($pAcc['userRole'] === 'viewer') {
            errorResponse('Viewer können keine Zeiten erfassen', 403);
        }

        if ($taskId) {
            $tStmt = $db->prepare("SELECT list_id FROM tasks WHERE id = ?");
            $tStmt->execute([$taskId]);
            $tRow = $tStmt->fetch();
            if ($tRow) {
                evaluateListAccess($user, $tRow['list_id'], 'write');
            }
        }

        $isManual = array_key_exists('is_manual', $body) ? (!empty($body['is_manual']) ? 1 : 0) : 1;
        $id = 'time_' . substr(bin2hex(random_bytes(8)), 0, 16);
        $stmt = $db->prepare("
            INSERT INTO time_entries (id, project_id, task_id, user_id, duration_minutes, entry_date, description, is_manual, hourly_rate)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id, $projectId, $taskId, $user['id'], $durationMinutes, $entryDate, $description, $isManual, $hourlyRate]);

        jsonResponse([
            'success' => true,
            'entry' => [
                'id' => $id,
                'project_id' => $projectId,
                'task_id' => $taskId,
                'user_id' => $user['id'],
                'duration_minutes' => $durationMinutes,
                'entry_date' => $entryDate,
                'description' => $description,
                'is_manual' => true,
                'hourly_rate' => $hourlyRate
            ]
        ]);
    }

    // 30. PUT time-entries/:id
    if (preg_match('#^time-entries/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $entryId = $m[1];

        $stmt = $db->prepare("SELECT * FROM time_entries WHERE id = ?");
        $stmt->execute([$entryId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            errorResponse('Zeiteintrag nicht gefunden', 404);
        }

        $pAcc = evaluateProjectAccess($user, $existing['project_id'], 'write');
        if ($existing['user_id'] !== $user['id'] && !in_array($pAcc['userRole'], ['owner', 'admin'])) {
            errorResponse('Nur der Ersteller oder Projektleiter darf diesen Zeiteintrag bearbeiten', 403);
        }

        $durationMinutes = isset($body['duration_minutes']) ? (int)$body['duration_minutes'] : (int)$existing['duration_minutes'];
        $entryDate = isset($body['entry_date']) ? trim($body['entry_date']) : $existing['entry_date'];
        $description = isset($body['description']) ? trim($body['description']) : $existing['description'];
        $hourlyRate = array_key_exists('hourly_rate', $body) && $body['hourly_rate'] !== null ? floatval($body['hourly_rate']) : floatval($existing['hourly_rate']);

        $upStmt = $db->prepare("
            UPDATE time_entries
            SET duration_minutes = ?, entry_date = ?, description = ?, hourly_rate = ?, is_manual = 1
            WHERE id = ?
        ");
        $upStmt->execute([$durationMinutes, $entryDate, $description, $hourlyRate, $entryId]);

        jsonResponse(['success' => true]);
    }

    // 31. DELETE time-entries/:id
    if (preg_match('#^time-entries/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $entryId = $m[1];

        $stmt = $db->prepare("SELECT * FROM time_entries WHERE id = ?");
        $stmt->execute([$entryId]);
        $existing = $stmt->fetch();
        if (!$existing) {
            errorResponse('Zeiteintrag nicht gefunden', 404);
        }

        $pAcc = evaluateProjectAccess($user, $existing['project_id'], 'write');
        if ($existing['user_id'] !== $user['id'] && !in_array($pAcc['userRole'], ['owner', 'admin'])) {
            errorResponse('Nur der Ersteller oder Projektleiter darf diesen Zeiteintrag löschen', 403);
        }

        $db->prepare("DELETE FROM time_entries WHERE id = ?")->execute([$entryId]);
        jsonResponse(['success' => true]);
    }

    // 32. GET contacts
    if ($path === 'contacts' && $method === 'GET') {
        $user = requireAuth();
        $folderId = trim($_GET['folder_id'] ?? '');
        $projectId = trim($_GET['project_id'] ?? '');
        $group = trim($_GET['group'] ?? '');
        $scope = trim($_GET['scope'] ?? '');
        $search = trim($_GET['search'] ?? '');

        $params = [];
        $where = [];

        // Base accessibility check:
        // Superadmin sees all
        if (empty($user['is_superadmin'])) {
            $userWhere = [];
            
            // 1. Created by this user
            $userWhere[] = "c.user_id = :uid_creator";
            $params[':uid_creator'] = $user['id'];

            // 2. Shared company contacts (if user has company)
            if (!empty($user['company_id'])) {
                $userWhere[] = "(c.share_scope = 'company' AND c.company_id = :cid_scope)";
                $params[':cid_scope'] = $user['company_id'];
            }

            // 3. Project-linked contacts where user has access to that project
            $userWhere[] = "(c.project_id IS NOT NULL AND c.project_id IN (
                SELECT p_acc.id FROM projects p_acc
                JOIN project_folders pf_acc ON pf_acc.id = p_acc.folder_id
                WHERE pf_acc.owner_id = :uid_p1
                   OR EXISTS (SELECT 1 FROM project_members pm WHERE pm.project_id = p_acc.id AND pm.user_id = :uid_p2)
                   OR EXISTS (SELECT 1 FROM folder_members fm WHERE fm.folder_id = pf_acc.id AND fm.user_id = :uid_p3)
                   " . (!empty($user['company_id']) ? "OR (pf_acc.company_id = :cid_p AND (p_acc.visibility = 'company' OR pf_acc.visibility = 'company'))" : "") . "
            ))";
            $params[':uid_p1'] = $user['id'];
            $params[':uid_p2'] = $user['id'];
            $params[':uid_p3'] = $user['id'];
            if (!empty($user['company_id'])) {
                $params[':cid_p'] = $user['company_id'];
            }

            // 4. Folder-linked contacts
            $userWhere[] = "(c.folder_id IS NOT NULL AND c.folder_id IN (
                SELECT pf_acc.id FROM project_folders pf_acc
                WHERE pf_acc.owner_id = :uid_f1
                   OR EXISTS (SELECT 1 FROM folder_members fm WHERE fm.folder_id = pf_acc.id AND fm.user_id = :uid_f2)
                   " . (!empty($user['company_id']) ? "OR (pf_acc.company_id = :cid_f AND pf_acc.visibility = 'company')" : "") . "
            ))";
            $params[':uid_f1'] = $user['id'];
            $params[':uid_f2'] = $user['id'];
            if (!empty($user['company_id'])) {
                $params[':cid_f'] = $user['company_id'];
            }

            $where[] = '(' . implode(' OR ', $userWhere) . ')';
        }

        // Additional filters:
        if ($folderId !== '') {
            $where[] = "(c.folder_id = :f_folder_id OR c.project_id IN (SELECT id FROM projects WHERE folder_id = :f_folder_id2))";
            $params[':f_folder_id'] = $folderId;
            $params[':f_folder_id2'] = $folderId;
        } else if ($projectId !== '') {
            // Contacts belong to folder!
            $stmtP = $db->prepare("SELECT folder_id FROM projects WHERE id = ?");
            $stmtP->execute([$projectId]);
            $pFolderId = $stmtP->fetchColumn();
            if ($pFolderId) {
                $where[] = "(c.folder_id = :f_pfolder_id OR c.project_id = :f_project_id OR c.project_id IN (SELECT id FROM projects WHERE folder_id = :f_pfolder_id2))";
                $params[':f_pfolder_id'] = $pFolderId;
                $params[':f_project_id'] = $projectId;
                $params[':f_pfolder_id2'] = $pFolderId;
            } else {
                $where[] = "c.project_id = :f_project_id";
                $params[':f_project_id'] = $projectId;
            }
        }

        if ($group !== '') {
            $where[] = "c.category_group = :f_group";
            $params[':f_group'] = $group;
        }

        if ($scope !== '') {
            $where[] = "c.share_scope = :f_scope";
            $params[':f_scope'] = $scope;
        }

        if ($search !== '') {
            $sTerm = '%' . $search . '%';
            $where[] = "(c.first_name LIKE :s1 OR c.last_name LIKE :s2 OR c.company_name LIKE :s3 OR c.role_function LIKE :s4 OR c.email LIKE :s5 OR c.phone LIKE :s6 OR c.mobile LIKE :s7 OR c.notes LIKE :s8)";
            $params[':s1'] = $sTerm;
            $params[':s2'] = $sTerm;
            $params[':s3'] = $sTerm;
            $params[':s4'] = $sTerm;
            $params[':s5'] = $sTerm;
            $params[':s6'] = $sTerm;
            $params[':s7'] = $sTerm;
            $params[':s8'] = $sTerm;
        }

        $sql = "
            SELECT c.*, 
                   p.title AS project_title,
                   COALESCE(pf.name, pf2.name) AS folder_name,
                   COALESCE(c.folder_id, p.folder_id) AS resolved_folder_id,
                   u.name AS creator_name
            FROM contacts c
            LEFT JOIN project_folders pf ON pf.id = c.folder_id
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf2 ON pf2.id = p.folder_id
            LEFT JOIN users u ON u.id = c.user_id
        ";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY c.last_name ASC, c.first_name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['tags'] = !empty($r['tags']) ? (is_string($r['tags']) ? json_decode($r['tags'], true) : $r['tags']) : [];
            if (!is_array($r['tags'])) $r['tags'] = [];
            $r['can_edit'] = (!empty($user['is_superadmin']) || $r['user_id'] === $user['id'] || (!empty($user['company_id']) && $user['company_id'] === $r['company_id'] && $user['company_role'] === 'admin'));
        }
        unset($r);

        jsonResponse(['contacts' => $rows]);
    }

    // 33. POST contacts
    if ($path === 'contacts' && $method === 'POST') {
        $user = requireAuth();
        $lastName = trim($body['last_name'] ?? '');
        if (!$lastName) {
            errorResponse('Nachname ist erforderlich', 400);
        }

        $id = 'contact_' . substr(bin2hex(random_bytes(8)), 0, 16);
        $firstName = trim($body['first_name'] ?? '');
        $companyName = trim($body['company_name'] ?? '');
        $roleFunction = trim($body['role_function'] ?? '');
        $phone = trim($body['phone'] ?? '');
        $mobile = trim($body['mobile'] ?? '');
        $email = trim($body['email'] ?? '');
        $folderId = !empty($body['folder_id']) ? trim($body['folder_id']) : null;
        $projectId = !empty($body['project_id']) ? trim($body['project_id']) : null;
        if (!$folderId && $projectId) {
            $stmtPf = $db->prepare("SELECT folder_id FROM projects WHERE id = ?");
            $stmtPf->execute([$projectId]);
            $folderId = $stmtPf->fetchColumn() ?: null;
        }

        $categoryGroup = trim($body['category_group'] ?? '');
        $address = trim($body['address'] ?? '');
        $website = trim($body['website'] ?? '');
        $latitude = (isset($body['latitude']) && $body['latitude'] !== '' && is_numeric($body['latitude'])) ? floatval($body['latitude']) : null;
        $longitude = (isset($body['longitude']) && $body['longitude'] !== '' && is_numeric($body['longitude'])) ? floatval($body['longitude']) : null;
        $tags = isset($body['tags']) && is_array($body['tags']) ? json_encode(array_values($body['tags'])) : '[]';
        $notes = trim($body['notes'] ?? '');
        $shareScope = in_array($body['share_scope'] ?? '', ['company', 'private']) ? $body['share_scope'] : 'private';

        // If project_id provided, verify write access
        if ($projectId) {
            evaluateProjectAccess($user, $projectId, 'write');
        }

        // Duplicate Protection (can be bypassed with force_duplicate = true)
        $forceDuplicate = !empty($body['force_duplicate']);
        if (!$forceDuplicate) {
            $params = [$user['id']];
            $companyCondition = "";
            if (!empty($user['company_id'])) {
                $companyCondition = " OR c.company_id = ?";
                $params[] = $user['company_id'];
            }

            $dupFilters = [];
            if ($email) {
                $dupFilters[] = "(c.email IS NOT NULL AND c.email != '' AND LOWER(c.email) = LOWER(?))";
                $params[] = $email;
            }
            if ($mobile) {
                $cleanMob = preg_replace('/\D+/', '', $mobile);
                if (strlen($cleanMob) >= 6) {
                    $dupFilters[] = "(c.mobile IS NOT NULL AND c.mobile != '' AND REPLACE(REPLACE(REPLACE(REPLACE(c.mobile, ' ', ''), '-', ''), '/', ''), '+', '') LIKE ?)";
                    $params[] = '%' . substr($cleanMob, -7);
                }
            }
            if ($lastName && $firstName) {
                $dupFilters[] = "(LOWER(c.last_name) = LOWER(?) AND LOWER(c.first_name) = LOWER(?))";
                $params[] = $lastName;
                $params[] = $firstName;
            }

            if (!empty($dupFilters)) {
                $dupSql = "
                    SELECT c.id, c.first_name, c.last_name, c.company_name, c.role_function, c.email, c.mobile, c.phone, c.category_group
                    FROM contacts c
                    WHERE (c.user_id = ?{$companyCondition})
                      AND (" . implode(' OR ', $dupFilters) . ")
                    LIMIT 1
                ";
                $dupStmt = $db->prepare($dupSql);
                $dupStmt->execute($params);
                $existingDup = $dupStmt->fetch();
                if ($existingDup) {
                    $name = trim(($existingDup['first_name'] ? $existingDup['first_name'] . ' ' : '') . $existingDup['last_name']);
                    $extra = $existingDup['company_name'] ? " ({$existingDup['company_name']})" : "";
                    jsonResponse([
                        'error' => 'duplicate_found',
                        'message' => "Mögliches Duplikat erkannt: {$name}{$extra}",
                        'existing_contact' => $existingDup
                    ], 409);
                }
            }
        }

        $stmt = $db->prepare("
            INSERT INTO contacts (
                id, user_id, company_id, folder_id, project_id, first_name, last_name,
                company_name, role_function, phone, mobile, email,
                category_group, address, website, latitude, longitude, tags, notes, share_scope, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        $stmt->execute([
            $id,
            $user['id'],
            $user['company_id'] ?? null,
            $folderId,
            $projectId,
            $firstName ?: null,
            $lastName,
            $companyName ?: null,
            $roleFunction ?: null,
            $phone ?: null,
            $mobile ?: null,
            $email ?: null,
            $categoryGroup ?: null,
            $address ?: null,
            $website ?: null,
            $latitude,
            $longitude,
            $tags,
            $notes ?: null,
            $shareScope
        ]);

        $fetchStmt = $db->prepare("
            SELECT c.*, p.title AS project_title, COALESCE(pf.name, pf2.name) AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN project_folders pf ON pf.id = c.folder_id
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf2 ON pf2.id = p.folder_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.id = ?
        ");
        $fetchStmt->execute([$id]);
        $newContact = $fetchStmt->fetch();
        if ($newContact) {
            $newContact['tags'] = !empty($newContact['tags']) ? (is_string($newContact['tags']) ? json_decode($newContact['tags'], true) : $newContact['tags']) : [];
            $newContact['can_edit'] = true;
        }

        jsonResponse(['contact' => $newContact], 201);
    }

    // 34. GET contacts/:id
    if (preg_match('#^contacts/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $contactId = $m[1];

        $stmt = $db->prepare("
            SELECT c.*, p.title AS project_title, COALESCE(pf.name, pf2.name) AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN project_folders pf ON pf.id = c.folder_id
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf2 ON pf2.id = p.folder_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.id = ?
        ");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch();
        if (!$contact) {
            errorResponse('Kontakt nicht gefunden', 404);
        }

        // Accessibility check
        $hasAccess = false;
        if (!empty($user['is_superadmin']) || $contact['user_id'] === $user['id']) {
            $hasAccess = true;
        } elseif (!empty($user['company_id']) && $contact['share_scope'] === 'company' && $user['company_id'] === $contact['company_id']) {
            $hasAccess = true;
        } elseif (!empty($contact['project_id'])) {
            try {
                evaluateProjectAccess($user, $contact['project_id'], 'read');
                $hasAccess = true;
            } catch (Exception $e) {
                // not accessible
            }
        }

        if (!$hasAccess) {
            errorResponse('Kontakt nicht gefunden', 404);
        }

        $contact['tags'] = !empty($contact['tags']) ? (is_string($contact['tags']) ? json_decode($contact['tags'], true) : $contact['tags']) : [];
        $contact['can_edit'] = (!empty($user['is_superadmin']) || $contact['user_id'] === $user['id'] || (!empty($user['company_id']) && $user['company_id'] === $contact['company_id'] && $user['company_role'] === 'admin'));

        jsonResponse(['contact' => $contact]);
    }

    // 35. PUT contacts/:id
    if (preg_match('#^contacts/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        $user = requireAuth();
        $contactId = $m[1];

        $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch();
        if (!$contact) {
            errorResponse('Kontakt nicht gefunden', 404);
        }

        // Mutation check: creator, superadmin, or company admin if in company
        $canEdit = (!empty($user['is_superadmin']) || $contact['user_id'] === $user['id'] || (!empty($user['company_id']) && $user['company_id'] === $contact['company_id'] && $user['company_role'] === 'admin'));
        if (!$canEdit) {
            errorResponse('Keine Berechtigung zum Bearbeiten dieses Kontakts', 403);
        }

        $lastName = isset($body['last_name']) ? trim($body['last_name']) : $contact['last_name'];
        if (!$lastName) {
            errorResponse('Nachname ist erforderlich', 400);
        }

        $firstName = array_key_exists('first_name', $body) ? trim($body['first_name']) : $contact['first_name'];
        $companyName = array_key_exists('company_name', $body) ? trim($body['company_name']) : $contact['company_name'];
        $roleFunction = array_key_exists('role_function', $body) ? trim($body['role_function']) : $contact['role_function'];
        $phone = array_key_exists('phone', $body) ? trim($body['phone']) : $contact['phone'];
        $mobile = array_key_exists('mobile', $body) ? trim($body['mobile']) : $contact['mobile'];
        $email = array_key_exists('email', $body) ? trim($body['email']) : $contact['email'];
        $folderId = array_key_exists('folder_id', $body) ? (!empty($body['folder_id']) ? trim($body['folder_id']) : null) : ($contact['folder_id'] ?? null);
        $projectId = array_key_exists('project_id', $body) ? (!empty($body['project_id']) ? trim($body['project_id']) : null) : $contact['project_id'];
        if (!$folderId && $projectId) {
            $stmtPf = $db->prepare("SELECT folder_id FROM projects WHERE id = ?");
            $stmtPf->execute([$projectId]);
            $folderId = $stmtPf->fetchColumn() ?: null;
        }

        $categoryGroup = array_key_exists('category_group', $body) ? trim($body['category_group']) : $contact['category_group'];
        $address = array_key_exists('address', $body) ? trim($body['address']) : ($contact['address'] ?? null);
        $website = array_key_exists('website', $body) ? trim($body['website']) : ($contact['website'] ?? null);
        $latitude = array_key_exists('latitude', $body)
            ? (($body['latitude'] === null || $body['latitude'] === '') ? null : floatval($body['latitude']))
            : ($contact['latitude'] ?? null);
        $longitude = array_key_exists('longitude', $body)
            ? (($body['longitude'] === null || $body['longitude'] === '') ? null : floatval($body['longitude']))
            : ($contact['longitude'] ?? null);
        $notes = array_key_exists('notes', $body) ? trim($body['notes']) : $contact['notes'];
        $shareScope = array_key_exists('share_scope', $body) && in_array($body['share_scope'], ['company', 'private']) ? $body['share_scope'] : $contact['share_scope'];
        
        $tags = $contact['tags'];
        if (array_key_exists('tags', $body)) {
            $tags = is_array($body['tags']) ? json_encode(array_values($body['tags'])) : '[]';
        }

        if ($projectId && $projectId !== $contact['project_id']) {
            evaluateProjectAccess($user, $projectId, 'write');
        }

        $upStmt = $db->prepare("
            UPDATE contacts
            SET first_name = ?, last_name = ?, company_name = ?, role_function = ?,
                phone = ?, mobile = ?, email = ?, folder_id = ?, project_id = ?, category_group = ?,
                address = ?, website = ?, latitude = ?, longitude = ?, tags = ?, notes = ?, share_scope = ?
            WHERE id = ?
        ");
        $upStmt->execute([
            $firstName ?: null,
            $lastName,
            $companyName ?: null,
            $roleFunction ?: null,
            $phone ?: null,
            $mobile ?: null,
            $email ?: null,
            $folderId,
            $projectId,
            $categoryGroup ?: null,
            $address ?: null,
            $website ?: null,
            $latitude,
            $longitude,
            $tags,
            $notes ?: null,
            $shareScope,
            $contactId
        ]);

        $fetchStmt = $db->prepare("
            SELECT c.*, p.title AS project_title, COALESCE(pf.name, pf2.name) AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN project_folders pf ON pf.id = c.folder_id
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf2 ON pf2.id = p.folder_id
            LEFT JOIN users u ON u.id = c.user_id
            WHERE c.id = ?
        ");
        $fetchStmt->execute([$contactId]);
        $updated = $fetchStmt->fetch();
        if ($updated) {
            $updated['tags'] = !empty($updated['tags']) ? (is_string($updated['tags']) ? json_decode($updated['tags'], true) : $updated['tags']) : [];
            $updated['can_edit'] = true;
        }

        jsonResponse(['contact' => $updated]);
    }

    // 36. DELETE contacts/:id
    if (preg_match('#^contacts/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $contactId = $m[1];

        $stmt = $db->prepare("SELECT * FROM contacts WHERE id = ?");
        $stmt->execute([$contactId]);
        $contact = $stmt->fetch();
        if (!$contact) {
            errorResponse('Kontakt nicht gefunden', 404);
        }

        $canDelete = (!empty($user['is_superadmin']) || $contact['user_id'] === $user['id'] || (!empty($user['company_id']) && $user['company_id'] === $contact['company_id'] && $user['company_role'] === 'admin'));
        if (!$canDelete) {
            errorResponse('Keine Berechtigung zum Löschen dieses Kontakts', 403);
        }

        $db->prepare("DELETE FROM contacts WHERE id = ?")->execute([$contactId]);
        jsonResponse(['success' => true]);
    }

    // 37. GET & POST admin/email-settings
    if ($path === 'admin/email-settings') {
        $user = requireAdminPermission('company_settings');
        if ($method === 'GET') {
            $cfg = getSmtpConfigDb($db);
            // Mask sensitive fields if not superadmin
            if (empty($user['is_superadmin'])) {
                if (!empty($cfg['smtp_password'])) $cfg['smtp_password'] = '••••••••';
                if (!empty($cfg['resend_api_key'])) $cfg['resend_api_key'] = substr($cfg['resend_api_key'], 0, 6) . '••••••••';
            }
            jsonResponse(['settings' => $cfg]);
        } elseif ($method === 'POST') {
            $allowed = ['mail_provider', 'resend_api_key', 'smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
            $update = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $body)) {
                    // Don't overwrite if masked
                    if ($k === 'smtp_password' && $body[$k] === '••••••••') continue;
                    if ($k === 'resend_api_key' && strpos($body[$k], '••••') !== false) continue;
                    $update[$k] = $body[$k];
                }
            }
            saveSmtpConfigDb($db, $update);
            jsonResponse(['success' => true, 'settings' => getSmtpConfigDb($db)]);
        }
    }

    // 38. POST admin/email-test
    if ($path === 'admin/email-test' && $method === 'POST') {
        $user = requireAdminPermission('company_settings');
        $targetEmail = !empty($body['to_email']) ? trim($body['to_email']) : $user['email'];
        if (!$targetEmail) {
            errorResponse('Empfänger-E-Mail fehlt', 400);
        }

        $cfg = getSmtpConfigDb($db);
        if (!empty($body['custom_config']) && is_array($body['custom_config'])) {
            foreach ($body['custom_config'] as $k => $v) {
                if ($k === 'smtp_password' && $v === '••••••••') continue;
                if ($k === 'resend_api_key' && strpos((string)$v, '••••') !== false) continue;
                if ($v !== null && $v !== '') $cfg[$k] = $v;
            }
        }

        $provider = $cfg['mail_provider'] ?? 'resend';
        $senderEmail = !empty($body['sender_email']) ? trim($body['sender_email']) : null;
        $senderPurpose = !empty($body['purpose']) ? trim($body['purpose']) : null;
        $senderInfo = getEmailSenderForPurpose(null, $senderPurpose);
        if ($senderEmail) {
            $senderInfo['from_email'] = $senderEmail;
            $senderInfo['from'] = "Taskster <{$senderEmail}>";
            if ($senderEmail === 'hey@kurka.ch') {
                $senderInfo['reply_to'] = 'support@kurka.ch';
            }
        }

        $subject = 'Taskster Test-E-Mail (' . $senderInfo['from_email'] . ') ' . date('d.m.Y H:i:s');
        $bodyHtml = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #00A3C4; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-top: 0;">Taskster E-Mail Test erfolgreich! 🎉</h2>
          <p>Diese Test-E-Mail bestätigt, dass der Versand über <strong>' . htmlspecialchars($provider === 'resend' ? 'Resend API (DKIM verifiziert)' : 'SMTP Server') . '</strong> einwandfrei funktioniert.</p>
          <div style="background: #f0fdfa; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
            <div><strong>Methode:</strong> ' . htmlspecialchars($provider === 'resend' ? 'Resend REST API' : 'SMTP-Server (' . $cfg['smtp_host'] . ')') . '</div>
            <div><strong>Absender:</strong> ' . htmlspecialchars($senderInfo['from'] . (!empty($senderInfo['reply_to']) ? ' (Reply-To: ' . $senderInfo['reply_to'] . ')' : '')) . '</div>
            <div><strong>Empfänger:</strong> ' . htmlspecialchars($targetEmail) . '</div>
          </div>
          <p style="font-size: 13px; color: #64748b;">Gesendet am ' . date('d.m.Y \u\m H:i:s \U\h\r') . ' von Taskster.</p>
        </div>';
        $bodyText = "Taskster E-Mail Test erfolgreich!\n\nMethode: " . ($provider === 'resend' ? 'Resend API' : 'SMTP') . "\nAbsender: " . $senderInfo['from'] . "\nEmpfänger: " . $targetEmail . "\n\nGesendet am " . date('d.m.Y H:i:s');

        $res = sendSmtpEmailNative($cfg, $targetEmail, $user['name'] ?? null, $subject, $bodyHtml, $bodyText, null, null, null, $senderPurpose, $senderInfo['from_email'], $senderInfo['reply_to']);
        if ($res['success']) {
            jsonResponse([
                'success' => true,
                'message' => "Test-E-Mail erfolgreich via " . ($provider === 'resend' ? 'Resend API' : 'SMTP') . " von {$senderInfo['from_email']} an {$targetEmail} versendet.",
                'log' => $res['log']
            ]);
        } else {
            jsonResponse([
                'success' => false,
                'error' => $res['error'],
                'log' => $res['log']
            ], 400);
        }
    }

    // 39. GET admin/email-templates
    if ($path === 'admin/email-templates' && $method === 'GET') {
        requireAdminPermission('any_admin');
        $stmt = $db->query("SELECT * FROM email_templates ORDER BY name ASC");
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($templates as &$t) {
            $t['variables'] = !empty($t['variables']) ? (is_string($t['variables']) ? json_decode($t['variables'], true) : $t['variables']) : [];
            $t['is_active'] = (bool)$t['is_active'];
        }
        jsonResponse(['templates' => $templates]);
    }

    // 40. PUT / PATCH admin/email-templates/:id
    if (preg_match('#^admin/email-templates/([^/]+)$#', $path, $m) && ($method === 'PUT' || $method === 'PATCH')) {
        requireAdminPermission('company_settings');
        $tmplId = $m[1];
        $stmt = $db->prepare("SELECT * FROM email_templates WHERE id = ? OR trigger_event = ?");
        $stmt->execute([$tmplId, $tmplId]);
        $tmpl = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$tmpl) errorResponse('Vorlage nicht gefunden', 404);

        $subject = array_key_exists('subject', $body) ? trim($body['subject']) : $tmpl['subject'];
        $bodyHtml = array_key_exists('body_html', $body) ? $body['body_html'] : $tmpl['body_html'];
        $bodyText = array_key_exists('body_text', $body) ? $body['body_text'] : $tmpl['body_text'];
        $isActive = array_key_exists('is_active', $body) ? ($body['is_active'] ? 1 : 0) : $tmpl['is_active'];

        $up = $db->prepare("
            UPDATE email_templates
            SET subject = ?, body_html = ?, body_text = ?, is_active = ?
            WHERE id = ?
        ");
        $up->execute([$subject, $bodyHtml, $bodyText, $isActive, $tmpl['id']]);

        $stmt->execute([$tmpl['id'], $tmpl['id']]);
        $updated = $stmt->fetch(PDO::FETCH_ASSOC);
        $updated['variables'] = !empty($updated['variables']) ? (is_string($updated['variables']) ? json_decode($updated['variables'], true) : $updated['variables']) : [];
        $updated['is_active'] = (bool)$updated['is_active'];
        jsonResponse(['template' => $updated]);
    }

    // 41. POST admin/email-templates/reset
    if ($path === 'admin/email-templates/reset' && $method === 'POST') {
        requireAdminPermission('company_settings');
        $targetId = !empty($body['id']) ? trim($body['id']) : null;
        $defaults = getDefaultEmailTemplates();

        if ($targetId) {
            foreach ($defaults as $d) {
                if ($d['id'] === $targetId || $d['trigger_event'] === $targetId) {
                    $up = $db->prepare("
                        UPDATE email_templates
                        SET name = ?, subject = ?, variables = ?, body_text = ?, body_html = ?, is_active = 1
                        WHERE id = ? OR trigger_event = ?
                    ");
                    $up->execute([$d['name'], $d['subject'], json_encode($d['variables']), $d['body_text'], $d['body_html'], $d['id'], $d['trigger_event']]);
                    break;
                }
            }
        } else {
            seedDefaultEmailTemplates($db);
        }
        jsonResponse(['success' => true]);
    }

    // 42. GET admin/email-outbox
    if ($path === 'admin/email-outbox' && $method === 'GET') {
        requireAdminPermission('any_admin');
        $limit = isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50;
        $stmt = $db->prepare("SELECT * FROM email_outbox ORDER BY created_at DESC LIMIT ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $outbox = $stmt->fetchAll(PDO::FETCH_ASSOC);
        jsonResponse(['outbox' => $outbox]);
    }

    // Not found
    errorResponse("Endpoint nicht gefunden: {$method} {$path}", 404);


} catch (Throwable $e) {
    errorResponse('Server Error: ' . $e->getMessage(), 500);
}

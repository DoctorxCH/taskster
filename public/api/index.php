<?php
header("Content-Type: application/json; charset=utf-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$dbHost = 'sql21.hostcreators.sk';
$dbPort = 3326;
$dbName = 'd44809_taskster_26';
$dbUser = 'u44809_martin_taskster';
$dbPass = 'Ckeesjb6&M';
$jwtSecret = 'taskster-super-secret-key-2026-safe-production';

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

        $count = $pdo->query("SELECT COUNT(*) FROM project_templates WHERE is_system = 1")->fetchColumn();
        if ((int)$count < 12) {
            seedTemplates($pdo);
        }

        // Column migrations (idempotent)
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
        ];
        foreach ($colMigrations as $sql) {
            try { $pdo->exec($sql); } catch (Exception $e) {}
        }

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
                'smtp_host' => 'mail.kurka.ch',
                'smtp_port' => '465',
                'smtp_secure' => 'ssl',
                'smtp_user' => 'noreply@kurka.ch',
                'smtp_password' => 'Ckeesjb6&M',
                'smtp_from_email' => 'noreply@kurka.ch',
                'smtp_from_name' => 'Taskster'
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
    $defaults = [
        [
            'id' => 'tmpl_lwl_tiefbau',
            'name' => 'Neues Projekt',
            'category' => 'job',
            'subcategory' => 'Tiefbau & Glasfaser',
            'description' => 'Vorkonfigurierte Bauleitung für Telekommunikation, Grabenbau, Rohrverlegung, Spleissen und OTDR-Dämpfungsmessung.',
            'icon' => 'HardHat',
            'lists' => ["Planung & Trasse", "Tiefbau & Graben", "Rohrverlegung & Kalibrierung", "Einblasen & Spleissen", "Messung & Abnahme"],
            'fields' => [
                [
                    'field_key' => 'gewerk',
                    'label' => 'Gewerk / Bauabschnitt',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Tiefbau & Graben', 'LWL / Spleissen', 'Kupfermontage', 'Oberflächenwiederherstellung'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'baufirma',
                    'label' => 'Ausführendes Bauunternehmen',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'trassenlaenge_m',
                    'label' => 'Trassenlänge (Meter)',
                    'field_type' => 'number',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => ['depends_on_field' => 'gewerk', 'depends_on_value' => 'Tiefbau & Graben']
                ],
                [
                    'field_key' => 'otdr_messung_ok',
                    'label' => 'OTDR Dämpfungsmessung',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Ja (Messprotokoll abgelegt)', 'Nein (Mangel / Nachprüfung)', 'Nicht erforderlich'],
                    'is_required' => false,
                    'logic_rules' => ['depends_on_field' => 'gewerk', 'depends_on_value' => 'LWL / Spleissen']
                ],
                [
                    'field_key' => 'abnahme_status',
                    'label' => 'Bauabnahme Status',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Ausstehend', 'Mängelfrei abgenommen', 'Nachbesserung erforderlich'],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_it_software',
            'name' => 'IT-Systemhaus & Software-Entwicklung',
            'category' => 'job',
            'subcategory' => 'IT & Software',
            'description' => 'Agiles Aufgaben- und Ticketmanagement für IT-Projekte, Bugtracking, Code-Reviews und Deployments.',
            'icon' => 'Laptop',
            'lists' => ["Backlog & Anfragen", "In Bearbeitung (Sprint)", "Code Review & QA", "Deployment / Live"],
            'fields' => [
                [
                    'field_key' => 'ticket_typ',
                    'label' => 'Ticket-Typ',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Feature / Neuheit', 'Bug / Fehlfunktion', 'Support & Wartung', 'Dokumentation'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'prio',
                    'label' => 'Dringlichkeit (Prio)',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Prio 1 (Kritisch)', 'Prio 2 (Hoch)', 'Prio 3 (Mittel)', 'Prio 4 (Niedrig)'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'bug_severity',
                    'label' => 'Fehler-Schweregrad',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Blocker (Systemausfall)', 'Major (Kernfunktion gestört)', 'Minor (Kosmetisch / UI)'],
                    'is_required' => false,
                    'logic_rules' => ['depends_on_field' => 'ticket_typ', 'depends_on_value' => 'Bug / Fehlfunktion']
                ],
                [
                    'field_key' => 'aufwand_stunden',
                    'label' => 'Geschätzter Aufwand (h)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_elektro_handwerk',
            'name' => 'Handwerk & Elektroinstallation',
            'category' => 'job',
            'subcategory' => 'Handwerk & Montage',
            'description' => 'Strukturierte Projektabwicklung vom Auftragseingang über Materialbeschaffung bis zur Montage und SiNa-Prüfung.',
            'icon' => 'Wrench',
            'lists' => ["Auftragseingang", "Materialbestellung", "Montage vor Ort", "Messung & SiNa-Prüfung", "Rechnung gestellt"],
            'fields' => [
                [
                    'field_key' => 'auftraggeber_typ',
                    'label' => 'Auftraggeber-Kategorie',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Privatkunde', 'Gewerbekunde', 'Öffentliche Hand'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'sicherheitsnachweis_nr',
                    'label' => 'SiNa-Protokoll-Nummer',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'material_status',
                    'label' => 'Material-Status',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Material bestellt', 'Im Lager vorrätig', 'Vor Ort montiert'],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'stundenaufwand',
                    'label' => 'Geleistete Stunden',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_shk_gebaeudetechnik',
            'name' => 'Sanitär, Heizung & Haustechnik (SHK)',
            'category' => 'job',
            'subcategory' => 'Haustechnik',
            'description' => 'Projektsteuerung für Heizungstausch, Badumbau, Wärmepumpen-Installation und Abnahmedokumentation.',
            'icon' => 'Flame',
            'lists' => ["Offerte & Vor-Ort-Check", "Bestellung & Disposition", "Demontage Altbestand", "Installation & Montage", "Inbetriebnahme & Übergabe"],
            'fields' => [
                [
                    'field_key' => 'anlagenart',
                    'label' => 'Art der Anlage',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Wärmepumpe Luft/Wasser', 'Wärmepumpe Erdsonde', 'Sanitär & Badumbau', 'Pellet / Holzheizung', 'Lüftung & Klima'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'hersteller_geraet',
                    'label' => 'Hersteller & Modell',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'druckpruefung_ok',
                    'label' => 'Druckprüfung erfolgt',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Ja (Protokoll vorhanden)', 'Ausstehend', 'Nicht erforderlich'],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'foerdergelder_beantragt',
                    'label' => 'Fördergelder beantragt',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Eingereicht', 'Bewilligt', 'Nicht zutreffend'],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_marketing_social',
            'name' => 'Marketing, Kampagnen & Social Media',
            'category' => 'job',
            'subcategory' => 'Marketing & Medien',
            'description' => 'Redaktions- und Kampagnenplanung von Content-Erstellung bis zu Werbeschaltung und ROI-Erfolgsmessung.',
            'icon' => 'Megaphone',
            'lists' => ["Briefing & Ideen", "Texterstellung & Konzept", "Grafik & Video-Assets", "Review & Kundenfreigabe", "Veröffentlicht & Tracking"],
            'fields' => [
                [
                    'field_key' => 'plattform',
                    'label' => 'Kanal / Plattform',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Instagram & TikTok', 'LinkedIn & Xing', 'Website & Blog', 'E-Mail & Newsletter', 'Google Ads / Performance'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'werbebudget_chf',
                    'label' => 'Ad-Spend / Budget (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'veroeffentlichungsdatum',
                    'label' => 'Geplantes Go-Live-Datum',
                    'field_type' => 'date',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_immobilien_bewirtschaftung',
            'name' => 'Immobilien-Verkauf & Vermietung',
            'category' => 'job',
            'subcategory' => 'Immobilien',
            'description' => 'Vollständige Abwicklung von Objektakquise, Exposé-Erstellung, Besichtigungsterminen bis zum Notartermin.',
            'icon' => 'Building',
            'lists' => ["Objektaufnahme & Unterlagen", "Marketing & Exposé", "Besichtigungstermine", "Kaufvertrags-Vorbereitung", "Notartermin & Übergabe"],
            'fields' => [
                [
                    'field_key' => 'objekttyp',
                    'label' => 'Objekt-Art',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Einfamilienhaus', 'Eigentumswohnung', 'Mehrfamilienhaus / Anlage', 'Gewerbe & Büro', 'Baugrundstück'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'verkaufspreis_chf',
                    'label' => 'Richtpreis / Kaufpreis (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'grundbuch_auszug_vorhanden',
                    'label' => 'Grundbuchauszug vorhanden',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Aktuell vorliegend', 'Bestellt / Ausstehend', 'Noch nicht angefordert'],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_gastronomie_catering',
            'name' => 'Gastronomie & Event-Catering',
            'category' => 'job',
            'subcategory' => 'Gastronomie & Events',
            'description' => 'Planung von Banketten, Firmenfeiern, Menüabläufen, Personaleinsatz und Allergenmanagement.',
            'icon' => 'Utensils',
            'lists' => ["Anfrage & Menüauswahl", "Einkauf & Vorbereitung", "Equipment & Logistik", "Durchführung vor Ort", "Abrechnung & Feedback"],
            'fields' => [
                [
                    'field_key' => 'anzahl_gaeste',
                    'label' => 'Gästeanzahl (Personen)',
                    'field_type' => 'number',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'menue_typ',
                    'label' => 'Menü-Art',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Mehrgang-Menü serviert', 'Buffet & Flying Dinner', 'Apéro Riche / Fingerfood', 'BBQ / Live-Cooking'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'allergene_hinweise',
                    'label' => 'Diäten & Allergene',
                    'field_type' => 'text',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_iso_qm_audit',
            'name' => 'Qualitätsmanagement & ISO-Audit',
            'category' => 'job',
            'subcategory' => 'Qualitätsmanagement',
            'description' => 'Auditvorbereitung, Prüfpfade, Korrekturmassnahmen (CAPA) und Sicherheits-Dokumentation.',
            'icon' => 'ShieldCheck',
            'lists' => ["Norm-Anforderungen & Lücken", "Interne Prüfung", "Korrekturmassnahmen (CAPA)", "Zertifizierungsaudit", "Abgeschlossen"],
            'fields' => [
                [
                    'field_key' => 'iso_norm',
                    'label' => 'Standard / Zertifizierung',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['ISO 9001 (Qualität)', 'ISO 27001 (Informationssicherheit)', 'ISO 14001 (Umwelt)', 'SUVA / Arbeitssicherheit'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'audit_befund',
                    'label' => 'Audit-Befund',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Konform', 'Geringfügige Abweichung (Minor)', 'Schwere Abweichung (Major)', 'Empfehlung'],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'wirksamkeit_frist',
                    'label' => 'Frist für Wirksamkeitsprüfung',
                    'field_type' => 'date',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_hausbau_privat',
            'name' => 'Hausbau & Wohnungsrenovierung',
            'category' => 'private',
            'subcategory' => 'Renovierung & Bau',
            'description' => 'Perfekt für private Renovierungen, Sanierungen und Umbauten inklusive Gewerke- und Kostenübersicht.',
            'icon' => 'Home',
            'lists' => ["Ideen & Recherche", "Offerten / Angebote einholen", "In Ausführung", "Fertiggestellt & Abgenommen"],
            'fields' => [
                [
                    'field_key' => 'raum',
                    'label' => 'Zimmer / Bereich',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Wohnzimmer', 'Küche', 'Badezimmer', 'Schlafzimmer', 'Garten & Terrasse', 'Keller & Technik'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'ausfuehrung_durch',
                    'label' => 'Ausführung durch',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Eigenleistung', 'Handwerker / Extern', 'Familie & Freunde'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'handwerker_firma',
                    'label' => 'Beauftragte Firma / Handwerker',
                    'field_type' => 'text',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => ['depends_on_field' => 'ausfuehrung_durch', 'depends_on_value' => 'Handwerker / Extern']
                ],
                [
                    'field_key' => 'budget_chf',
                    'label' => 'Kostenbudget (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'ist_kosten_chf',
                    'label' => 'Tatsächliche Kosten (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_event_privat',
            'name' => 'Event- & Feierplanung (Hochzeit, Fest)',
            'category' => 'private',
            'subcategory' => 'Feier & Event',
            'description' => 'Organisation privater Feiern von Dienstleisterverträgen bis zum detaillierten Ablaufplan am Eventtag.',
            'icon' => 'Sparkles',
            'lists' => ["Planung & Inspiration", "Buchungen & Dienstleister", "Woche vor dem Event", "Tag des Events", "Nachbereitung & Danksagung"],
            'fields' => [
                [
                    'field_key' => 'kategorie',
                    'label' => 'Event-Kategorie',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Location & Catering', 'Musik / DJ / Band', 'Fotograf & Video', 'Deko & Floristik', 'Gäste & Einladungen'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'anzahlung_erledigt',
                    'label' => 'Anzahlung geleistet',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Ja (Quittung abgelegt)', 'Nein (Noch offen)', 'Nicht erforderlich'],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'preis_chf',
                    'label' => 'Kosten / Honorar (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'faelligkeit',
                    'label' => 'Fälligkeitsdatum',
                    'field_type' => 'date',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_umzug_privat',
            'name' => 'Privater Umzug & Wohnungswechsel',
            'category' => 'private',
            'subcategory' => 'Wohnen & Umzug',
            'description' => 'Reibungsloser Wohnungswechsel: Kündigungsfristen, Packliste, Transporter-Buchung und Adressänderungen.',
            'icon' => 'Truck',
            'lists' => ["Kündigungen & Verträge", "Vorbereitung & Kisten packen", "Umzugstag", "Neue Wohnung einrichten", "Behörden & Ummeldungen"],
            'fields' => [
                [
                    'field_key' => 'umzug_kategorie',
                    'label' => 'Aufgaben-Bereich',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Mietvertrag & Kündigung', 'Packen & Entrümpeln', 'Umzugshelfer / Transporter', 'Endreinigung & Abnahme', 'Ummeldung & Behörden'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'kisten_nummer',
                    'label' => 'Kisten-Nr. / Zielraum',
                    'field_type' => 'text',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'abnahmetermin',
                    'label' => 'Wohnungsübergabetermin',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ],
        [
            'id' => 'tmpl_finanzen_steuer',
            'name' => 'Finanzabschluss & Steuererklärung',
            'category' => 'private',
            'subcategory' => 'Finanzen & Vorsorge',
            'description' => 'Sämtliche Steuerbelege, Lohnausweise, Vorsorgenachweise und Fristen übersichtlich gesammelt.',
            'icon' => 'Calculator',
            'lists' => ["Belege & Dokumente sammeln", "Abzüge & Vorsorge prüfen", "Erfassung in Steuer-Software", "Eingereicht & Prüfbescheid"],
            'fields' => [
                [
                    'field_key' => 'steuerjahr',
                    'label' => 'Steuerjahr',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['2024', '2025', '2026', '2027'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'beleg_art',
                    'label' => 'Art des Nachweises',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Lohnausweis & Einkünfte', 'Säule 3a / Pensionskasseneinkauf', 'Krankheits- & Zahnarztkosten', 'Spendenbescheinigungen', 'Berufsauslagen & Weiterbildung', 'Liegenschaftskosten / Unterhalt'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'beleg_betrag_chf',
                    'label' => 'Betrag (CHF)',
                    'field_type' => 'number',
                    'entity_type' => 'task',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'einreichfrist',
                    'label' => 'Einreichungsfrist',
                    'field_type' => 'date',
                    'entity_type' => 'project',
                    'options' => [],
                    'is_required' => false,
                    'logic_rules' => []
                ]
            ]
        ]
    ];

    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM project_templates WHERE id = ?");
    $insertStmt = $pdo->prepare("INSERT INTO project_templates (id, name, category, subcategory, description, icon, is_system, lists, fields) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)");
    $updateStmt = $pdo->prepare("UPDATE project_templates SET name = ?, category = ?, subcategory = ?, description = ?, icon = ?, is_system = 1, lists = ?, fields = ? WHERE id = ?");

    foreach ($defaults as $d) {
        $checkStmt->execute([$d['id']]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            $updateStmt->execute([
                $d['name'], $d['category'], $d['subcategory'], $d['description'],
                $d['icon'], json_encode($d['lists']), json_encode($d['fields']), $d['id']
            ]);
        } else {
            $insertStmt->execute([
                $d['id'], $d['name'], $d['category'], $d['subcategory'], $d['description'],
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
    $stmt = $pdo->query("SELECT `key`, `value` FROM system_settings WHERE `key` LIKE 'smtp_%'");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $map = [];
    foreach ($rows as $r) {
        $map[$r['key']] = $r['value'];
    }
    return [
        'smtp_host' => !empty($map['smtp_host']) ? $map['smtp_host'] : 'mail.kurka.ch',
        'smtp_port' => !empty($map['smtp_port']) ? (int)$map['smtp_port'] : 465,
        'smtp_secure' => !empty($map['smtp_secure']) ? $map['smtp_secure'] : 'ssl',
        'smtp_user' => !empty($map['smtp_user']) ? $map['smtp_user'] : 'noreply@kurka.ch',
        'smtp_password' => isset($map['smtp_password']) ? $map['smtp_password'] : 'Ckeesjb6&M',
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

function sendSmtpEmailNative($cfg, $to, $toName, $subject, $bodyHtml = '', $bodyText = '', $icsContent = null, $outboxId = null) {
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
    return sendSmtpEmailNative($cfg, $recipient['email'], $recipient['name'] ?? null, $subject, $bodyHtml, $bodyText, $data['ics_content'] ?? null);
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
        'theme' => 'light',
        'density' => 'comfortable',
        'start_page' => 'dashboard',
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
        'language' => pickEnum($raw['language'] ?? null, ['de', 'en'], $d['language']),
        'theme' => pickEnum($raw['theme'] ?? null, ['light', 'dark', 'system'], $d['theme']),
        'density' => pickEnum($raw['density'] ?? null, ['comfortable', 'compact'], $d['density']),
        'start_page' => pickEnum($raw['start_page'] ?? null, ['dashboard', 'calendar', 'time', 'contacts'], $d['start_page']),
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
    if (!empty($user['is_superadmin'])) {
        $stmt = $db->prepare("SELECT p.id, p.folder_id, p.visibility as project_visibility, pf.owner_id, pf.company_id, pf.visibility as folder_visibility FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.id = ?");
        $stmt->execute([$projectId]);
        $prj = $stmt->fetch();
        if (!$prj) errorResponse('Projekt nicht gefunden', 404);
        return [
            'projectId' => $prj['id'],
            'folderId' => $prj['folder_id'],
            'ownerId' => $prj['owner_id'],
            'companyId' => $prj['company_id'],
            'userRole' => 'owner'
        ];
    }

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
        if ($projectContext['userRole'] !== 'owner' && $projectContext['userRole'] !== 'admin' && empty($user['is_superadmin'])) {
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

// ROUTER
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = preg_replace('#^.*?/api/?#', '', $uri);
$path = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'];
$body = getJsonBody();
$db = getDb();

try {
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

        $token = jwtEncode([
            'id' => $u['id'],
            'email' => $u['email'],
            'name' => $u['name'],
            'company_id' => $u['company_id'],
            'company_role' => $u['company_role'],
            'is_superadmin' => (int)$u['is_superadmin'],
            'is_pro' => (int)$u['is_pro'],
            'admin_permissions' => $perms
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
                'admin_permissions' => $perms
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
        $isPro = 0;

        // Check invitation token
        if ($invitationToken) {
            $invStmt = $db->prepare("SELECT * FROM company_invitations WHERE token = ? AND status = 'pending'");
            $invStmt->execute([$invitationToken]);
            $inv = $invStmt->fetch();
            if ($inv) {
                $companyId = $inv['company_id'];
                $companyRole = $inv['role'];
                $isPro = 1;
                $db->prepare("UPDATE company_invitations SET status = 'accepted' WHERE id = ?")->execute([$inv['id']]);
            }
        }

        $uStmt = $db->prepare("INSERT INTO users (id, company_id, company_role, is_superadmin, is_pro, name, email, password_hash) VALUES (?, ?, ?, 0, ?, ?, ?, ?)");
        $uStmt->execute([$userId, $companyId, $companyRole, $isPro, $name, $email, $pwHash]);

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

        $token = jwtEncode([
            'id' => $userId,
            'email' => $email,
            'name' => $name,
            'company_id' => $companyId,
            'company_role' => $companyRole,
            'is_superadmin' => 0,
            'is_pro' => $isPro
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
                'avatar' => null
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
                'admin_permissions' => $perms
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

        if (empty($user['is_pro']) && empty($user['company_id']) && empty($user['is_superadmin'])) {
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM project_folders WHERE owner_id = ?");
            $stmt->execute([$user['id']]);
            $row = $stmt->fetch();
            if ($row['count'] >= 1) {
                errorResponse('Free-Plan Limit: Maximal 1 Projektordner erlaubt.', 403);
            }
        }

        $fldId = 'fld_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $icon = trim($body['icon'] ?? '📁');
        $companyId = !empty($user['company_id']) ? $user['company_id'] : ($body['company_id'] ?? null);
        $visibility = (!empty($companyId) && ($body['visibility'] ?? '') === 'company') ? 'company' : 'private';

        $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$fldId, $user['id'], $companyId, $name, $icon, $visibility]);

        jsonResponse(['folder' => ['id' => $fldId, 'name' => $name, 'icon' => $icon, 'visibility' => $visibility, 'owner_id' => $user['id'], 'company_id' => $companyId]]);
    }

    // 5b. PUT folders/:id
    if (preg_match('#^folders/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if (empty($user['is_superadmin']) && $folder['owner_id'] !== $user['id']) {
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

        $db->prepare("UPDATE project_folders SET name = ?, icon = ?, visibility = ?, company_id = ? WHERE id = ?")->execute([$name, $icon, $visibility, $companyId, $fldId]);

        if (!empty($body['default_project_id'])) {
            $defPrjId = trim($body['default_project_id']);
            $db->prepare("UPDATE projects SET is_default = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE folder_id = ?")->execute([$defPrjId, $fldId]);
        }

        $uStmt = $db->prepare("SELECT pf.*, u.name as owner_name, c.name as company_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id LEFT JOIN companies c ON c.id = pf.company_id WHERE pf.id = ?");
        $uStmt->execute([$fldId]);
        jsonResponse(['success' => true, 'folder' => $uStmt->fetch()]);
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
        if (!empty($user['is_superadmin']) || $folder['owner_id'] === $user['id']) {
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
        // Ordner-Inhaber & Superadmins sehen alle Projekte des Ordners.
        // Andere Nutzer sehen nur Projekte, die auf company stehen (im selben Unternehmen) oder bei denen sie Mitglied sind.
        if ($folder['owner_id'] === $user['id'] || !empty($user['is_superadmin'])) {
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

        if (empty($user['is_superadmin']) && $folder['owner_id'] !== $user['id']) {
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

        if (empty($user['is_superadmin']) && $folder['owner_id'] !== $user['id'] && $user['id'] !== $targetUserId) {
            errorResponse('Nur der Ordner-Eigentümer kann Mitglieder entfernen', 403);
        }

        $db->prepare("DELETE FROM folder_members WHERE folder_id = ? AND user_id = ?")->execute([$fldId, $targetUserId]);
        jsonResponse(['success' => true]);
    }

    // 7. POST folders/:id/fields
    if (preg_match('#^folders/([^/]+)/fields$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $fldId = $m[1];
        $label = trim($body['label'] ?? '');
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '_', $label));
        $type = $body['field_type'] ?? 'text';
        $entityType = in_array($body['entity_type'] ?? '', ['project', 'task']) ? $body['entity_type'] : 'task';
        $options = $body['options'] ?? [];
        $logicRules = $body['logic_rules'] ?? null;

        $fieldId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, entity_type, options, logic_rules) VALUES (?, ?, ?, ?, ?, ?, ?, ?)")->execute([
            $fieldId, $fldId, $key, $label, $type, $entityType, json_encode($options), $logicRules ? json_encode($logicRules) : null
        ]);

        jsonResponse(['success' => true, 'fieldId' => $fieldId]);
    }

    // 7b. DELETE folders/:id/fields/:fieldId
    if (preg_match('#^folders/([^/]+)/fields/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        $fldId = $m[1];
        $fieldId = $m[2];
        $db->prepare("DELETE FROM folder_field_definitions WHERE id = ? AND folder_id = ?")->execute([$fieldId, $fldId]);
        jsonResponse(['success' => true]);
    }

    // 8b. GET projects (List all accessible projects for dropdowns & time reporting)
    if ($path === 'projects' && $method === 'GET') {
        $user = requireAuth();
        if (!empty($user['is_superadmin'])) {
            $stmt = $db->prepare("
                SELECT p.id, p.title, p.folder_id, p.currency, p.status, p.is_default,
                       pf.name as folder_name, pf.icon as folder_icon, pf.owner_id
                FROM projects p
                JOIN project_folders pf ON pf.id = p.folder_id
                ORDER BY p.title ASC
            ");
            $stmt->execute();
        } else {
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
        }
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

        if (!$folderId || !$title) errorResponse('Ordner und Titel erforderlich', 400);

        // Folder access check
        $fCheckStmt = $db->prepare("SELECT * FROM project_folders WHERE id = ?");
        $fCheckStmt->execute([$folderId]);
        $folder = $fCheckStmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        if ($folder['owner_id'] !== $user['id'] && empty($user['is_superadmin'])) {
            if ($folder['visibility'] !== 'company' || empty($user['company_id']) || $user['company_id'] !== $folder['company_id']) {
                errorResponse('Ordner nicht gefunden', 404);
            }
        }

        $prjId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO projects (id, folder_id, title, status, visibility, currency, budget_hours, budget_amount, custom_data) VALUES (?, ?, ?, 'active', ?, ?, ?, ?, ?)")->execute([
            $prjId, $folderId, $title, $visibility, $currency, $budgetHours, $budgetAmount, json_encode($customData)
        ]);

        // Add creator to project_members as owner
        $pmId = 'pm_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO project_members (id, project_id, user_id, role) VALUES (?, ?, ?, 'owner')")->execute([
            $pmId, $prjId, $user['id']
        ]);

        // Listen / Abschnitte bestimmen
        $listsToCreate = [];
        if (!empty($customLists) && is_array($customLists)) {
            foreach ($customLists as $cl) {
                $cl = trim((string)$cl);
                if ($cl !== '' && !in_array($cl, $listsToCreate)) {
                    $listsToCreate[] = $cl;
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
                    $listsToCreate = $tLists;
                }
            }
        }

        // Falls ueber import_tasks Abschnitte definiert wurden, die noch fehlen:
        if (!empty($importTasks) && is_array($importTasks)) {
            foreach ($importTasks as $it) {
                $sec = trim((string)($it['list_title'] ?? ''));
                if ($sec !== '' && !in_array($sec, $listsToCreate)) {
                    $listsToCreate[] = $sec;
                }
            }
        }

        if (empty($listsToCreate)) {
            $listsToCreate = ['Aufgabenliste 1'];
        }

        // Listen anlegen und Map speichern: strtolower(title) => list_id
        $listMap = [];
        $firstListId = null;
        $order = 1;
        foreach ($listsToCreate as $listTitle) {
            $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
            if (!$firstListId) $firstListId = $lstId;
            $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, ?, 'inherit', ?)")
               ->execute([$lstId, $prjId, $listTitle, $order++]);
            $listMap[mb_strtolower(trim($listTitle))] = $lstId;
        }

        // Falls Vorlage gewaehlt wurde: Benutzerdefinierte Felder in den Ordner replizieren
        if ($tmpl) {
            $fields = !empty($tmpl['fields']) ? (is_string($tmpl['fields']) ? json_decode($tmpl['fields'], true) : $tmpl['fields']) : [];
            if (!empty($fields) && is_array($fields)) {
                $existingFieldsStmt = $db->prepare("SELECT field_key FROM folder_field_definitions WHERE folder_id = ?");
                $existingFieldsStmt->execute([$folderId]);
                $existingKeys = $existingFieldsStmt->fetchAll(PDO::FETCH_COLUMN);

                $countStmt = $db->prepare("SELECT COUNT(*) FROM folder_field_definitions WHERE folder_id = ?");
                $countStmt->execute([$folderId]);
                $sortOrder = (int)$countStmt->fetchColumn() + 1;

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

                    $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, entity_type, options, logic_rules, is_required, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
                       ->execute([$fId, $folderId, $fKey, $fLabel, $fType, $fEntity, json_encode($fOpts), $fRules ? json_encode($fRules) : null, $fReq, $sortOrder++]);
                    $existingKeys[] = $fKey;
                }
            }
        }

        // Falls import_tasks uebergeben wurden: saemtliche Aufgaben anlegen
        if (!empty($importTasks) && is_array($importTasks)) {
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
                $tDueDate = !empty($taskItem['due_date']) ? (string)$taskItem['due_date'] : null;
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
            if ($l['access_mode'] === 'inherit' || $context['userRole'] === 'owner' || $context['userRole'] === 'admin' || !empty($user['is_superadmin'])) {
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
        evaluateProjectAccess($user, $projectId, 'write');

        $listId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $countStmt = $db->prepare("SELECT COUNT(*) FROM lists WHERE project_id = ?");
        $countStmt->execute([$projectId]);
        $nextSort = (int)$countStmt->fetchColumn() + 1;

        $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, ?, ?, ?)")->execute([$listId, $projectId, $title, $accessMode, $nextSort]);

        jsonResponse(['success' => true, 'list' => ['id' => $listId, 'title' => $title, 'access_mode' => $accessMode, 'sort_order' => $nextSort]]);
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

        $db->prepare("UPDATE lists SET title = ?, access_mode = ?, sort_order = ? WHERE id = ?")->execute([$title, $accessMode, $sortOrder, $listId]);
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
            $upStmt = $db->prepare("UPDATE lists SET sort_order = ?, title = COALESCE(?, title), color = ? WHERE id = ? AND project_id = ?");
            foreach ($lists as $idx => $item) {
                $lid = is_string($item) ? $item : ($item['id'] ?? '');
                $title = (is_array($item) && !empty($item['title'])) ? trim($item['title']) : null;
                $sort = (is_array($item) && isset($item['sort_order'])) ? (int)$item['sort_order'] : ($idx + 1);
                $color = (is_array($item) && array_key_exists('color', $item)) ? ($item['color'] ?: null) : null;
                if ($lid) {
                    $upStmt->execute([$sort, $title, $color, $lid, $projectId]);
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
        $dueDate = $body['due_date'] ?? null;
        $customData = $body['custom_data'] ?? [];
        $assignedTo = !empty($body['assigned_to']) ? $body['assigned_to'] : null;
        $priority = !empty($body['priority']) ? $body['priority'] : 'normal';
        $color = !empty($body['color']) ? $body['color'] : null;
        $tags = isset($body['tags']) ? json_encode($body['tags']) : '[]';
        $checklist = isset($body['checklist']) ? json_encode($body['checklist']) : '[]';

        evaluateListAccess($user, $listId, 'write');

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
        if ($userRole === 'viewer' && empty($user['is_superadmin'])) {
            $newStatus = $body['status'] ?? $task['status'];
            $db->prepare("UPDATE tasks SET status = ? WHERE id = ?")->execute([$newStatus, $taskId]);
            jsonResponse(['success' => true]);
        }

        // Editor & Owner: dürfen Aufgaben ändern, verschieben, bearbeiten
        evaluateListAccess($user, $task['list_id'], 'write');

        $title = $body['title'] ?? $task['title'];
        $desc = $body['description'] ?? $task['description'];
        $status = $body['status'] ?? $task['status'];
        $dueDate = $body['due_date'] ?? $task['due_date'];
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
        if (($userRole === 'editor' || $userRole === 'viewer') && empty($user['is_superadmin'])) {
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

    // 15. GET journals
    if ($path === 'journals' && $method === 'GET') {
        $user = requireAuth();
        $projectId = $_GET['project_id'] ?? '';
        
        if (!empty($projectId)) {
            evaluateProjectAccess($user, $projectId, 'read');
            $stmt = $db->prepare("
                SELECT j.*, u.name as author_name, t.title as task_title
                FROM project_journals j
                JOIN users u ON u.id = j.author_id
                LEFT JOIN tasks t ON t.id = j.task_id
                WHERE j.project_id = ?
                ORDER BY j.created_at DESC
            ");
            $stmt->execute([$projectId]);
        } else {
            $stmt = $db->prepare("
                SELECT j.*, u.name as author_name, t.title as task_title, p.title as project_title
                FROM project_journals j
                JOIN users u ON u.id = j.author_id
                LEFT JOIN tasks t ON t.id = j.task_id
                LEFT JOIN projects p ON p.id = j.project_id
                WHERE j.author_id = ?
                ORDER BY j.created_at DESC
                LIMIT 100
            ");
            $stmt->execute([$user['id']]);
        }

        $entries = array_map(function($e) {
            $e['metadata'] = !empty($e['metadata']) ? (is_string($e['metadata']) ? json_decode($e['metadata'], true) : $e['metadata']) : [];
            return $e;
        }, $stmt->fetchAll());

        jsonResponse(['entries' => $entries]);
    }

    // 16. POST journals
    if ($path === 'journals' && $method === 'POST') {
        $user = requireAuth();
        $projectId = $body['project_id'] ?? '';
        $title = trim($body['title'] ?? '');
        $content = trim($body['content'] ?? '');
        $entryType = $body['entry_type'] ?? 'manual';

        if (empty($title) || empty($content)) {
            errorResponse('Titel und Inhalt sind erforderlich', 400);
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
        $uStmt = $db->prepare("SELECT id, email, name FROM users WHERE LOWER(email) = ?");
        $uStmt->execute([$email]);
        $existing = $uStmt->fetch();

        if ($existing) {
            // Already registered -> direct join!
            $db->prepare("UPDATE users SET company_id = ?, company_role = ?, is_pro = 1 WHERE id = ?")->execute([
                $companyId, $role, $existing['id']
            ]);
            jsonResponse([
                'success' => true,
                'action' => 'added',
                'user' => ['id' => $existing['id'], 'email' => $existing['email'], 'name' => $existing['name']]
            ]);
        } else {
            // Not registered -> create pending invitation with token
            $token = bin2hex(random_bytes(24));
            $invId = 'inv_' . substr(bin2hex(random_bytes(6)), 0, 8);

            // Invalidate existing pending invites for this email & company
            $db->prepare("DELETE FROM company_invitations WHERE company_id = ? AND LOWER(email) = ?")->execute([$companyId, $email]);

            $db->prepare("INSERT INTO company_invitations (id, company_id, email, role, token, invited_by, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')")->execute([
                $invId, $companyId, $email, $role, $token, $user['id']
            ]);

            jsonResponse([
                'success' => true,
                'action' => 'invited',
                'token' => $token,
                'email' => $email
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
            jsonResponse(['members' => []]);
        }
        $stmt = $db->prepare("SELECT id, name, email, company_role, is_pro, created_at FROM users WHERE company_id = ? ORDER BY (company_role = 'admin') DESC, name ASC");
        $stmt->execute([$companyId]);
        jsonResponse(['members' => $stmt->fetchAll()]);
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
        if ($existing['user_id'] !== $user['id'] && !in_array($pAcc['userRole'], ['owner', 'admin']) && empty($user['is_superadmin'])) {
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
        if ($existing['user_id'] !== $user['id'] && !in_array($pAcc['userRole'], ['owner', 'admin']) && empty($user['is_superadmin'])) {
            errorResponse('Nur der Ersteller oder Projektleiter darf diesen Zeiteintrag löschen', 403);
        }

        $db->prepare("DELETE FROM time_entries WHERE id = ?")->execute([$entryId]);
        jsonResponse(['success' => true]);
    }

    // 32. GET contacts
    if ($path === 'contacts' && $method === 'GET') {
        $user = requireAuth();
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

            $where[] = '(' . implode(' OR ', $userWhere) . ')';
        }

        // Additional filters:
        if ($projectId !== '') {
            $where[] = "c.project_id = :f_project_id";
            $params[':f_project_id'] = $projectId;
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
                   pf.name AS folder_name,
                   u.name AS creator_name
            FROM contacts c
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf ON pf.id = p.folder_id
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
        $projectId = !empty($body['project_id']) ? trim($body['project_id']) : null;
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
                id, user_id, company_id, project_id, first_name, last_name,
                company_name, role_function, phone, mobile, email,
                category_group, address, website, latitude, longitude, tags, notes, share_scope, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )
        ");
        $stmt->execute([
            $id,
            $user['id'],
            $user['company_id'] ?? null,
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
            SELECT c.*, p.title AS project_title, pf.name AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf ON pf.id = p.folder_id
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
            SELECT c.*, p.title AS project_title, pf.name AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf ON pf.id = p.folder_id
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
        $projectId = array_key_exists('project_id', $body) ? (!empty($body['project_id']) ? trim($body['project_id']) : null) : $contact['project_id'];
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
                phone = ?, mobile = ?, email = ?, project_id = ?, category_group = ?,
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
            SELECT c.*, p.title AS project_title, pf.name AS folder_name, u.name AS creator_name
            FROM contacts c
            LEFT JOIN projects p ON p.id = c.project_id
            LEFT JOIN project_folders pf ON pf.id = p.folder_id
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
            // Mask password if not superadmin
            if (empty($user['is_superadmin']) && !empty($cfg['smtp_password'])) {
                $cfg['smtp_password'] = '••••••••';
            }
            jsonResponse(['settings' => $cfg]);
        } elseif ($method === 'POST') {
            $allowed = ['smtp_host', 'smtp_port', 'smtp_secure', 'smtp_user', 'smtp_password', 'smtp_from_email', 'smtp_from_name'];
            $update = [];
            foreach ($allowed as $k) {
                if (array_key_exists($k, $body)) {
                    // Don't overwrite password if masked
                    if ($k === 'smtp_password' && $body[$k] === '••••••••') continue;
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
                if ($v !== null && $v !== '') $cfg[$k] = $v;
            }
        }

        $subject = 'Taskster Test-E-Mail ' . date('d.m.Y H:i:s');
        $bodyHtml = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #00A3C4; border-radius: 8px;">
          <h2 style="color: #00A3C4; margin-top: 0;">Taskster SMTP-Test erfolgreich! 🎉</h2>
          <p>Diese Test-E-Mail bestätigt, dass die SMTP-Konfiguration ordnungsgemäss funktioniert.</p>
          <div style="background: #f0fdfa; border-left: 4px solid #00A3C4; padding: 12px; margin: 16px 0;">
            <div><strong>Host:</strong> ' . htmlspecialchars($cfg['smtp_host']) . '</div>
            <div><strong>Port:</strong> ' . htmlspecialchars((string)$cfg['smtp_port']) . '</div>
            <div><strong>Verschlüsselung:</strong> ' . htmlspecialchars($cfg['smtp_secure']) . '</div>
            <div><strong>Absender:</strong> ' . htmlspecialchars($cfg['smtp_from_name'] . ' <' . $cfg['smtp_from_email'] . '>') . '</div>
          </div>
          <p style="font-size: 13px; color: #64748b;">Gesendet am ' . date('d.m.Y \u\m H:i:s \U\h\r') . ' von Taskster.</p>
        </div>';
        $bodyText = "Taskster SMTP-Test erfolgreich!\n\nHost: {$cfg['smtp_host']}\nPort: {$cfg['smtp_port']}\nAbsender: {$cfg['smtp_from_email']}\n\nGesendet am " . date('d.m.Y H:i:s');

        $res = sendSmtpEmailNative($cfg, $targetEmail, $user['name'] ?? null, $subject, $bodyHtml, $bodyText);
        if ($res['success']) {
            jsonResponse([
                'success' => true,
                'message' => "Test-E-Mail erfolgreich an {$targetEmail} versendet.",
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


} catch (Exception $e) {
    errorResponse('Server Error: ' . $e->getMessage(), 500);
}

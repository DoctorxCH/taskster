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
$dbPass = '[REDACTED_SECRET]';
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

        $count = $pdo->query("SELECT COUNT(*) FROM project_templates")->fetchColumn();
        if ((int)$count === 0) {
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
        ];
        foreach ($colMigrations as $sql) {
            try { $pdo->exec($sql); } catch (Exception $e) {}
        }

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
    } catch (Exception $e) {
        // Continue if table exists or migration done
    }
}

function seedTemplates($pdo) {
    $defaults = [
        [
            'id' => 'tmpl_lwl_tiefbau',
            'name' => 'Bau- & Tiefbauleitung (LWL / Glasfaser)',
            'category' => 'job',
            'subcategory' => 'bau',
            'description' => 'Vorkonfigurierte Bauleitung für Telekommunikation, Grabenbau, Rohrverlegung, Spleissen und OTDR-Dämpfungsmessung.',
            'icon' => 'HardHat',
            'lists' => ["Planung / Trasse", "Tiefbau & Rohrverlegung", "Einblasen & Spleissen", "Messung & Abnahme", "Erledigt"],
            'fields' => [
                [
                    'field_key' => 'gewerk',
                    'label' => 'Gewerk / Bauabschnitt',
                    'field_type' => 'select',
                    'entity_type' => 'project',
                    'options' => ['Tiefbau & Graben', 'LWL / Spleissen', 'Kupfermontage'],
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
                    'options' => ['Ja (Protokoll angehängt)', 'Nein (Mangel)', 'Nicht erforderlich'],
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
            'subcategory' => 'it',
            'description' => 'Agiles Aufgaben- und Ticketmanagement für IT-Projekte, Bugtracking, Code-Reviews und Deployments.',
            'icon' => 'Laptop',
            'lists' => ["Backlog", "In Bearbeitung (Sprint)", "Code Review & QA", "Deployment / Live"],
            'fields' => [
                [
                    'field_key' => 'ticket_typ',
                    'label' => 'Ticket-Typ',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Feature', 'Bug / Fehler', 'Support / Wartung', 'Dokumentation'],
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
                    'label' => 'Bug Severity',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Blocker (Systemausfall)', 'Major (Fehlfunktion)', 'Minor (Kosmetisch)'],
                    'is_required' => false,
                    'logic_rules' => ['depends_on_field' => 'ticket_typ', 'depends_on_value' => 'Bug / Fehler']
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
            'subcategory' => 'handwerk',
            'description' => 'Strukturierte Projektabwicklung vom Auftragseingang über Materialbeschaffung bis zur Abnahme und SiNa-Prüfung.',
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
            'id' => 'tmpl_hausbau_privat',
            'name' => 'Hausbau & Wohnungsrenovierung',
            'category' => 'private',
            'subcategory' => 'renovierung',
            'description' => 'Perfekt für private Renovierungen, Sanierungen und Umbauten inklusive Gewerke- und Kostenübersicht.',
            'icon' => 'Home',
            'lists' => ["Ideen & Recherche", "Offerten / Angebote einholen", "In Ausführung", "Fertiggestellt"],
            'fields' => [
                [
                    'field_key' => 'raum',
                    'label' => 'Zimmer / Bereich',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Wohnzimmer', 'Küche', 'Badezimmer', 'Schlafzimmer', 'Garten / Terrasse', 'Keller / Technik'],
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
            'subcategory' => 'event',
            'description' => 'Organisation privater Feiern von Dienstleisterverträgen bis zum Ablaufplan am Eventtag.',
            'icon' => 'Sparkles',
            'lists' => ["Planung & Ideen", "Buchungen & Verträge", "Woche vor dem Event", "Tag des Events", "Nachbereitung"],
            'fields' => [
                [
                    'field_key' => 'kategorie',
                    'label' => 'Event-Kategorie',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Location & Catering', 'Musik / DJ', 'Fotograf & Video', 'Deko & Blumen', 'Gäste & Einladungen'],
                    'is_required' => true,
                    'logic_rules' => []
                ],
                [
                    'field_key' => 'anzahlung_erledigt',
                    'label' => 'Anzahlung geleistet',
                    'field_type' => 'select',
                    'entity_type' => 'task',
                    'options' => ['Ja (Quittung vorhanden)', 'Nein (Offen)', 'Nicht erforderlich'],
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
        ]
    ];

    $stmt = $pdo->prepare("
        INSERT INTO project_templates (id, name, category, subcategory, description, icon, is_system, lists, fields)
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)
    ");
    foreach ($defaults as $d) {
        $stmt->execute([
            $d['id'], $d['name'], $d['category'], $d['subcategory'], $d['description'],
            $d['icon'], json_encode($d['lists']), json_encode($d['fields'])
        ]);
    }
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

function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function errorResponse($message, $status = 400) {
    http_response_code($status);
    echo json_encode(['statusCode' => $status, 'statusMessage' => $message]);
    exit;
}

function getAuthUser() {
    global $jwtSecret;
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (!preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
        return null;
    }
    $decoded = jwtDecode($matches[1], $jwtSecret);
    if (!$decoded || empty($decoded['id'])) return null;
    $db = getDb();
    $stmt = $db->prepare("SELECT id, name, email, company_id, company_role, is_superadmin, is_pro FROM users WHERE id = ?");
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
        } elseif (
            !empty($user['company_id']) && $user['company_id'] === $prj['company_id'] &&
            ($prj['project_visibility'] ?? 'private') === 'company'
        ) {
            $role = 'editor';
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

        $token = jwtEncode([
            'id' => $u['id'],
            'email' => $u['email'],
            'name' => $u['name'],
            'company_id' => $u['company_id'],
            'company_role' => $u['company_role'],
            'is_superadmin' => (int)$u['is_superadmin'],
            'is_pro' => (int)$u['is_pro']
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
                'is_pro' => (bool)$u['is_pro']
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
                'is_pro' => (bool)$isPro
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
                'currency' => $u['currency'] ?? 'CHF'
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
                'currency' => $u['currency'] ?? 'CHF'
            ]
        ]);
    }

    // 4. GET folders
    if ($path === 'folders' && $method === 'GET') {
        $user = requireAuth();
        $companyId = !empty($user['company_id']) ? $user['company_id'] : '__none__';

        // Strikte Privatsphäre: Der Nutzer sieht nur:
        // 1. Eigene Ordner (owner_id = user.id)
        // 2. Ordner, in denen er Projektmitglied ist (project_members)
        // 3. Ordner mit visibility = 'company' des eigenen Unternehmens
        // Company Admins haben KEINEN automatischen Zugriff auf private Ordner!
        $stmt = $db->prepare("
            SELECT pf.*, u.name as owner_name, c.name as company_name,
              (
                SELECT COUNT(*) FROM projects p
                WHERE p.folder_id = pf.id AND (
                  pf.owner_id = ?
                  OR (p.visibility = 'company' AND pf.company_id = ?)
                  OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                )
              ) as project_count
            FROM project_folders pf
            JOIN users u ON u.id = pf.owner_id
            LEFT JOIN companies c ON c.id = pf.company_id
            WHERE pf.owner_id = ?
               OR pf.id IN (
                   SELECT p.folder_id FROM projects p
                   JOIN project_members pm ON pm.project_id = p.id
                   WHERE pm.user_id = ?
               )
               OR (pf.visibility = 'company' AND pf.company_id = ?)
            ORDER BY pf.created_at DESC
        ");
        $stmt->execute([
            $user['id'], $companyId, $user['id'],
            $user['id'], $user['id'], $companyId
        ]);
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
        // Standard ist verbindlich 'private', ausser bei Unternehmensmitgliedern explizit 'company' gewählt
        $visibility = (!empty($user['company_id']) && ($body['visibility'] ?? '') === 'company') ? 'company' : 'private';

        $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name, icon, visibility) VALUES (?, ?, ?, ?, ?, ?)")
           ->execute([$fldId, $user['id'], $user['company_id'], $name, $icon, $visibility]);

        jsonResponse(['folder' => ['id' => $fldId, 'name' => $name, 'icon' => $icon, 'visibility' => $visibility, 'owner_id' => $user['id'], 'company_id' => $user['company_id']]]);
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
        $visibility = $folder['visibility'] ?? 'private';
        if (isset($body['visibility'])) {
            $visibility = (!empty($user['company_id']) && $body['visibility'] === 'company') ? 'company' : 'private';
        }
        if (!$name) errorResponse('Name erforderlich', 400);

        $db->prepare("UPDATE project_folders SET name = ?, icon = ?, visibility = ? WHERE id = ?")->execute([$name, $icon, $visibility, $fldId]);

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
            $chkStmt = $db->prepare("SELECT 1 FROM projects p JOIN project_members pm ON pm.project_id = p.id WHERE p.folder_id = ? AND pm.user_id = ? LIMIT 1");
            $chkStmt->execute([$fldId, $user['id']]);
            if ($chkStmt->fetch()) {
                $canAccessFolder = true;
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
                ORDER BY p.created_at DESC
            ");
            $pStmt->execute([$fldId]);
        } else {
            $userCompany = !empty($user['company_id']) ? $user['company_id'] : '__none__';
            $pStmt = $db->prepare("
                SELECT p.*,
                  (SELECT COUNT(*) FROM lists l WHERE l.project_id = p.id) as list_count,
                  (SELECT COUNT(*) FROM tasks t JOIN lists l ON l.id = t.list_id WHERE l.project_id = p.id) as task_count,
                  (SELECT COUNT(*) FROM project_members pm WHERE pm.project_id = p.id) as member_count
                FROM projects p
                WHERE p.folder_id = ? AND (
                    (p.visibility = 'company' AND ? = ?)
                    OR p.id IN (SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?)
                )
                ORDER BY p.created_at DESC
            ");
            $pStmt->execute([
                $fldId,
                $userCompany, $folder['company_id'],
                $user['id']
            ]);
        }
        $folderTotalMinutes = 0;
        $folderTotalBudgetHours = 0;
        $folderTotalBudgetAmount = 0;
        $projects = array_map(function($p) use ($db, &$folderTotalMinutes, &$folderTotalBudgetHours, &$folderTotalBudgetAmount) {
            $p['custom_data'] = !empty($p['custom_data']) ? (is_string($p['custom_data']) ? json_decode($p['custom_data'], true) : $p['custom_data']) : [];
            $p['visibility'] = $p['visibility'] ?? 'private';
            $tHoursStmt = $db->prepare("SELECT SUM(duration_minutes) FROM time_entries WHERE project_id = ?");
            $tHoursStmt->execute([$p['id']]);
            $pMinutes = (int)($tHoursStmt->fetchColumn() ?: 0);
            $p['tracked_hours'] = round($pMinutes / 60, 2);
            $folderTotalMinutes += $pMinutes;
            if (!empty($p['budget_hours'])) $folderTotalBudgetHours += floatval($p['budget_hours']);
            if (!empty($p['budget_amount'])) $folderTotalBudgetAmount += floatval($p['budget_amount']);
            return $p;
        }, $pStmt->fetchAll());

        $timeSummary = [
            'totalMinutes' => $folderTotalMinutes,
            'totalHours' => round($folderTotalMinutes / 60, 2),
            'totalBudgetHours' => $folderTotalBudgetHours,
            'totalBudgetAmount' => $folderTotalBudgetAmount
        ];

        jsonResponse(['folder' => $folder, 'fields' => $fields, 'projects' => $projects, 'timeSummary' => $timeSummary]);
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

    // 8. POST projects
    if ($path === 'projects' && $method === 'POST') {
        $user = requireAuth();
        $folderId = $body['folder_id'] ?? '';
        $title = trim($body['title'] ?? '');
        $customData = $body['custom_data'] ?? [];
        $templateId = $body['template_id'] ?? null;
        $currency = !empty($body['currency']) ? trim($body['currency']) : 'CHF';
        $budgetHours = array_key_exists('budget_hours', $body) && $body['budget_hours'] !== null && $body['budget_hours'] !== '' ? floatval($body['budget_hours']) : null;
        $budgetAmount = array_key_exists('budget_amount', $body) && $body['budget_amount'] !== null && $body['budget_amount'] !== '' ? floatval($body['budget_amount']) : null;
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

        if ($templateId) {
            $tStmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
            $tStmt->execute([$templateId]);
            $tmpl = $tStmt->fetch();
            if ($tmpl) {
                $lists = !empty($tmpl['lists']) ? (is_string($tmpl['lists']) ? json_decode($tmpl['lists'], true) : $tmpl['lists']) : [];
                $fields = !empty($tmpl['fields']) ? (is_string($tmpl['fields']) ? json_decode($tmpl['fields'], true) : $tmpl['fields']) : [];

                if (!empty($lists)) {
                    $order = 1;
                    foreach ($lists as $listTitle) {
                        $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
                        $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, ?, 'inherit', ?)")
                           ->execute([$lstId, $prjId, $listTitle, $order++]);
                    }
                } else {
                    $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
                    $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, 'Aufgabenliste 1', 'inherit', 1)")->execute([$lstId, $prjId]);
                }

                if (!empty($fields)) {
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
            } else {
                $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
                $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, 'Aufgabenliste 1', 'inherit', 1)")->execute([$lstId, $prjId]);
            }
        } else {
            $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
            $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, 'Aufgabenliste 1', 'inherit', 1)")->execute([$lstId, $prjId]);
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
                SELECT t.*, COALESCE((SELECT SUM(duration_minutes) FROM time_entries WHERE task_id = t.id), 0) as tracked_minutes
                FROM tasks t
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
                return $t;
            }, $tStmt->fetchAll());
        }

        // Members
        $mStmt = $db->prepare("
            SELECT pm.id, pm.role, u.id as user_id, u.name, u.email, u.company_role
            FROM project_members pm
            JOIN users u ON u.id = pm.user_id
            WHERE pm.project_id = ?
        ");
        $mStmt->execute([$projectId]);
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
        $budgetHours = array_key_exists('budget_hours', $body) ? ($body['budget_hours'] !== null ? floatval($body['budget_hours']) : null) : ($project['budget_hours'] ?? null);
        $budgetAmount = array_key_exists('budget_amount', $body) ? ($body['budget_amount'] !== null ? floatval($body['budget_amount']) : null) : ($project['budget_amount'] ?? null);
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
            WHERE p.owner_id = ? OR pf.owner_id = ? OR p.id IN (
              SELECT pm.project_id FROM project_members pm WHERE pm.user_id = ?
            )
            ORDER BY t.created_at DESC
            LIMIT 20
        ");
        $tStmt->execute([$user['id'], $user['id'], $user['id']]);
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

        $taskId = 'tsk_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("
            INSERT INTO tasks (id, list_id, title, description, status, custom_data, due_date, sort_order, assigned_to, priority, color, tags, checklist)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)
        ")->execute([
            $taskId, $listId, $title, $desc, $status, json_encode($customData), $dueDate,
            $assignedTo, $priority, $color, $tags, $checklist
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

        $assignedTo = array_key_exists('assigned_to', $body) ? ($body['assigned_to'] ?: null) : ($task['assigned_to'] ?? null);
        $priority = $body['priority'] ?? ($task['priority'] ?? 'normal');
        $color = array_key_exists('color', $body) ? ($body['color'] ?: null) : ($task['color'] ?? null);
        $tags = isset($body['tags']) ? json_encode($body['tags']) : ($task['tags'] ?? '[]');
        $checklist = isset($body['checklist']) ? json_encode($body['checklist']) : ($task['checklist'] ?? '[]');
        $budgetHours = array_key_exists('budget_hours', $body) ? ($body['budget_hours'] !== null ? floatval($body['budget_hours']) : null) : ($task['budget_hours'] ?? null);
        $budgetAmount = array_key_exists('budget_amount', $body) ? ($body['budget_amount'] !== null ? floatval($body['budget_amount']) : null) : ($task['budget_amount'] ?? null);

        $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, custom_data = ?, list_id = ?, sort_order = ?, assigned_to = ?, priority = ?, color = ?, tags = ?, checklist = ?, budget_hours = ?, budget_amount = ? WHERE id = ?")->execute([
            $title, $desc, $status, $dueDate, $customData, $listId, $sortOrder,
            $assignedTo, $priority, $color, $tags, $checklist, $budgetHours, $budgetAmount, $taskId
        ]);

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

        $assignee = null;
        if (!empty($task['assigned_to'])) {
            $aStmt = $db->prepare("SELECT id, name, email FROM users WHERE id = ?");
            $aStmt->execute([$task['assigned_to']]);
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
        evaluateProjectAccess($user, $projectId, 'write');

        $jrnId = 'jrn_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO project_journals (id, project_id, author_id, entry_type, title, content) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $jrnId, $projectId, $user['id'], $entryType, $title, $content
        ]);

        jsonResponse(['success' => true, 'entry' => ['id' => $jrnId, 'title' => $title, 'content' => $content]]);
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

    // 17. GET admin/overview
    if ($path === 'admin/overview' && $method === 'GET') {
        requireSuperadmin();
        $uCount = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        $cCount = $db->query("SELECT COUNT(*) FROM companies")->fetchColumn();
        $pCount = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
        $tCount = $db->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
        $jCount = $db->query("SELECT COUNT(*) FROM project_journals")->fetchColumn();

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
        requireSuperadmin();
        $stmt = $db->query("
            SELECT u.*, c.name as company_name, c.subscription_plan as company_plan
            FROM users u
            LEFT JOIN companies c ON c.id = u.company_id
            ORDER BY u.created_at DESC
        ");
        $users = array_map(function($u) {
            $u['is_superadmin'] = (bool)$u['is_superadmin'];
            $u['is_pro'] = (bool)$u['is_pro'];
            return $u;
        }, $stmt->fetchAll());

        jsonResponse(['users' => $users]);
    }

    // 19. PATCH admin/users/:id (Update user settings: pro status, company, role, superadmin)
    if (preg_match('#^admin/users/([^/]+)$#', $path, $m) && $method === 'PATCH') {
        requireSuperadmin();
        $targetId = $m[1];
        
        $fields = [];
        $params = [];

        if (isset($body['is_pro'])) {
            $fields[] = "is_pro = ?";
            $params[] = $body['is_pro'] ? 1 : 0;
        }
        if (isset($body['is_superadmin'])) {
            $fields[] = "is_superadmin = ?";
            $params[] = $body['is_superadmin'] ? 1 : 0;
        }
        if (array_key_exists('company_id', $body)) {
            $fields[] = "company_id = ?";
            $params[] = !empty($body['company_id']) ? $body['company_id'] : null;
        }
        if (array_key_exists('company_role', $body)) {
            $fields[] = "company_role = ?";
            $params[] = !empty($body['company_role']) ? $body['company_role'] : null;
        }
        if (!empty($body['name'])) {
            $fields[] = "name = ?";
            $params[] = trim($body['name']);
        }
        if (!empty($body['email'])) {
            $fields[] = "email = ?";
            $params[] = strtolower(trim($body['email']));
        }

        if (!empty($fields)) {
            $params[] = $targetId;
            $db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        }

        jsonResponse(['success' => true]);
    }

    // 20. GET admin/companies
    if ($path === 'admin/companies' && $method === 'GET') {
        requireSuperadmin();
        $stmt = $db->query("
            SELECT c.*,
              (SELECT COUNT(*) FROM users u WHERE u.company_id = c.id) as user_count,
              (SELECT COUNT(*) FROM project_folders pf WHERE pf.company_id = c.id) as folder_count
            FROM companies c
            ORDER BY c.created_at DESC
        ");
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
        requireSuperadmin();
        $compId = $m[1];
        if (isset($body['subscription_plan'])) {
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

        $sql = "SELECT * FROM project_templates WHERE 1=1";
        $params = [];
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

    // 25. POST templates (Admin only)
    if ($path === 'templates' && $method === 'POST') {
        $user = requireAuth();
        if (empty($user['is_superadmin']) && ($user['company_role'] ?? '') !== 'admin') {
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
            !empty($user['is_superadmin']) ? 1 : 0,
            $user['company_id'] ?? null,
            json_encode($lists),
            json_encode($fields)
        ]);

        jsonResponse(['success' => true, 'id' => $tmplId]);
    }

    // 26. PUT templates/:id (Admin only)
    if (preg_match('#^templates/([^/]+)$#', $path, $m) && $method === 'PUT') {
        $user = requireAuth();
        if (empty($user['is_superadmin']) && ($user['company_role'] ?? '') !== 'admin') {
            errorResponse('Nur Administratoren können Vorlagen verwalten', 403);
        }
        $tmplId = $m[1];
        $stmt = $db->prepare("SELECT * FROM project_templates WHERE id = ?");
        $stmt->execute([$tmplId]);
        $existing = $stmt->fetch();
        if (!$existing) errorResponse('Vorlage nicht gefunden', 404);

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

    // 27. DELETE templates/:id (Admin only)
    if (preg_match('#^templates/([^/]+)$#', $path, $m) && $method === 'DELETE') {
        $user = requireAuth();
        if (empty($user['is_superadmin']) && ($user['company_role'] ?? '') !== 'admin') {
            errorResponse('Nur Administratoren können Vorlagen verwalten', 403);
        }
        $tmplId = $m[1];
        $db->prepare("DELETE FROM project_templates WHERE id = ?")->execute([$tmplId]);
        jsonResponse(['success' => true]);
    }

    // 28. GET time-entries
    if ($path === 'time-entries' && $method === 'GET') {
        $user = requireAuth();
        $projectId = $_GET['project_id'] ?? null;
        $taskId = $_GET['task_id'] ?? null;
        $folderId = $_GET['folder_id'] ?? null;

        if (!$projectId && !$taskId && !$folderId) {
            errorResponse('project_id, task_id oder folder_id erforderlich', 400);
        }

        $query = "
            SELECT te.*, u.name as user_name, t.title as task_title, p.title as project_title, p.currency as project_currency
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
        }

        if ($taskId) {
            $tStmt = $db->prepare("SELECT list_id FROM tasks WHERE id = ?");
            $tStmt->execute([$taskId]);
            $tRow = $tStmt->fetch();
            if ($tRow) {
                evaluateListAccess($user, $tRow['list_id'], 'read');
            }
            $query .= " AND te.task_id = ?";
            $params[] = $taskId;
        }

        if ($folderId) {
            $fStmt = $db->prepare("SELECT owner_id FROM project_folders WHERE id = ?");
            $fStmt->execute([$folderId]);
            $fRow = $fStmt->fetch();
            if (!$fRow && empty($user['is_superadmin'])) {
                errorResponse('Ordner nicht gefunden', 404);
            }
            $query .= " AND te.project_id IN (SELECT id FROM projects WHERE folder_id = ?)";
            $params[] = $folderId;
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

    // Not found
    errorResponse("Endpoint nicht gefunden: {$method} {$path}", 404);


} catch (Exception $e) {
    errorResponse('Server Error: ' . $e->getMessage(), 500);
}

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
    }
    return $pdo;
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
        $stmt = $db->prepare("SELECT p.id, p.folder_id, pf.owner_id, pf.company_id FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.id = ?");
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

    $stmt = $db->prepare("SELECT p.id, p.folder_id, pf.owner_id, pf.company_id FROM projects p JOIN project_folders pf ON pf.id = p.folder_id WHERE p.id = ?");
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

    // Stufe 2: Project Membership Check
    $role = null;
    if ($prj['owner_id'] === $user['id']) {
        $role = 'owner';
    } elseif (!empty($user['company_id']) && $user['company_id'] === $prj['company_id'] && $user['company_role'] === 'admin') {
        $role = 'admin';
    } else {
        $mStmt = $db->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
        $mStmt->execute([$projectId, $user['id']]);
        $m = $mStmt->fetch();
        if ($m) $role = $m['role'];
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
                'is_pro' => (bool)$u['is_pro']
            ]
        ]);
    }

    // 4. GET folders
    if ($path === 'folders' && $method === 'GET') {
        $user = requireAuth();
        if (!empty($user['is_superadmin'])) {
            $stmt = $db->query("
                SELECT pf.*, u.name as owner_name, c.name as company_name,
                  (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
                FROM project_folders pf
                JOIN users u ON u.id = pf.owner_id
                LEFT JOIN companies c ON c.id = pf.company_id
                ORDER BY pf.created_at DESC
            ");
            $folders = $stmt->fetchAll();
        } elseif (!empty($user['company_id'])) {
            $stmt = $db->prepare("
                SELECT pf.*, u.name as owner_name, c.name as company_name,
                  (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
                FROM project_folders pf
                JOIN users u ON u.id = pf.owner_id
                LEFT JOIN companies c ON c.id = pf.company_id
                WHERE pf.company_id = ? OR pf.owner_id = ?
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute([$user['company_id'], $user['id']]);
            $folders = $stmt->fetchAll();
        } else {
            $stmt = $db->prepare("
                SELECT pf.*, u.name as owner_name, NULL as company_name,
                  (SELECT COUNT(*) FROM projects p WHERE p.folder_id = pf.id) as project_count
                FROM project_folders pf
                JOIN users u ON u.id = pf.owner_id
                WHERE pf.owner_id = ? OR pf.id IN (
                  SELECT p.folder_id FROM projects p
                  JOIN project_members pm ON pm.project_id = p.id
                  WHERE pm.user_id = ?
                )
                ORDER BY pf.created_at DESC
            ");
            $stmt->execute([$user['id'], $user['id']]);
            $folders = $stmt->fetchAll();
        }
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
        $db->prepare("INSERT INTO project_folders (id, owner_id, company_id, name) VALUES (?, ?, ?, ?)")->execute([$fldId, $user['id'], $user['company_id'], $name]);

        jsonResponse(['folder' => ['id' => $fldId, 'name' => $name, 'owner_id' => $user['id'], 'company_id' => $user['company_id']]]);
    }

    // 6. GET folders/:id
    if (preg_match('#^folders/([^/]+)$#', $path, $m) && $method === 'GET') {
        $user = requireAuth();
        $fldId = $m[1];
        $stmt = $db->prepare("SELECT pf.*, u.name as owner_name, c.name as company_name FROM project_folders pf JOIN users u ON u.id = pf.owner_id LEFT JOIN companies c ON c.id = pf.company_id WHERE pf.id = ?");
        $stmt->execute([$fldId]);
        $folder = $stmt->fetch();
        if (!$folder) errorResponse('Ordner nicht gefunden', 404);

        $fStmt = $db->prepare("SELECT * FROM folder_field_definitions WHERE folder_id = ? ORDER BY sort_order ASC");
        $fStmt->execute([$fldId]);
        $fields = array_map(function($f) {
            $f['options'] = !empty($f['options']) ? (is_string($f['options']) ? json_decode($f['options'], true) : $f['options']) : [];
            $f['logic_rules'] = !empty($f['logic_rules']) ? (is_string($f['logic_rules']) ? json_decode($f['logic_rules'], true) : $f['logic_rules']) : [];
            return $f;
        }, $fStmt->fetchAll());

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
        $projects = $pStmt->fetchAll();

        jsonResponse(['folder' => $folder, 'fields' => $fields, 'projects' => $projects]);
    }

    // 7. POST folders/:id/fields
    if (preg_match('#^folders/([^/]+)/fields$#', $path, $m) && $method === 'POST') {
        $user = requireAuth();
        $fldId = $m[1];
        $label = trim($body['label'] ?? '');
        $key = strtolower(preg_replace('/[^a-z0-9_]/', '_', $label));
        $type = $body['field_type'] ?? 'text';
        $options = $body['options'] ?? [];

        $fieldId = 'fld_def_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO folder_field_definitions (id, folder_id, field_key, label, field_type, options) VALUES (?, ?, ?, ?, ?, ?)")->execute([
            $fieldId, $fldId, $key, $label, $type, json_encode($options)
        ]);

        jsonResponse(['success' => true, 'fieldId' => $fieldId]);
    }

    // 8. POST projects
    if ($path === 'projects' && $method === 'POST') {
        $user = requireAuth();
        $folderId = $body['folder_id'] ?? '';
        $title = trim($body['title'] ?? '');
        if (!$folderId || !$title) errorResponse('Ordner und Titel erforderlich', 400);

        $prjId = 'prj_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO projects (id, folder_id, title, status) VALUES (?, ?, ?, 'active')")->execute([$prjId, $folderId, $title]);

        $lstId = 'lst_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, 'Aufgabenliste 1', 'inherit', 1)")->execute([$lstId, $prjId]);

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

        // Fields
        $fStmt = $db->prepare("SELECT * FROM folder_field_definitions WHERE folder_id = ? ORDER BY sort_order ASC");
        $fStmt->execute([$context['folderId']]);
        $fields = array_map(function($f) {
            $f['options'] = !empty($f['options']) ? (is_string($f['options']) ? json_decode($f['options'], true) : $f['options']) : [];
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
            $tStmt = $db->prepare("SELECT * FROM tasks WHERE list_id = ? ORDER BY sort_order ASC, created_at DESC");
            $tStmt->execute([$l['id']]);
            $l['tasks'] = array_map(function($t) {
                $t['custom_data'] = !empty($t['custom_data']) ? (is_string($t['custom_data']) ? json_decode($t['custom_data'], true) : $t['custom_data']) : [];
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
        $db->prepare("INSERT INTO lists (id, project_id, title, access_mode, sort_order) VALUES (?, ?, ?, ?, 1)")->execute([$listId, $projectId, $title, $accessMode]);

        jsonResponse(['success' => true, 'list' => ['id' => $listId, 'title' => $title, 'access_mode' => $accessMode]]);
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

        evaluateListAccess($user, $listId, 'write');

        $taskId = 'tsk_' . substr(bin2hex(random_bytes(6)), 0, 8);
        $db->prepare("INSERT INTO tasks (id, list_id, title, description, status, custom_data, due_date, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, 1)")->execute([
            $taskId, $listId, $title, $desc, $status, json_encode($customData), $dueDate
        ]);

        jsonResponse(['success' => true, 'task' => ['id' => $taskId, 'title' => $title, 'status' => $status]]);
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

        $db->prepare("UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, custom_data = ?, list_id = ? WHERE id = ?")->execute([
            $title, $desc, $status, $dueDate, $customData, $listId, $taskId
        ]);

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

    // 19. PATCH admin/users/:id
    if (preg_match('#^admin/users/([^/]+)$#', $path, $m) && $method === 'PATCH') {
        requireSuperadmin();
        $targetId = $m[1];
        if (isset($body['is_pro'])) {
            $db->prepare("UPDATE users SET is_pro = ? WHERE id = ?")->execute([$body['is_pro'] ? 1 : 0, $targetId]);
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

    // Not found
    errorResponse("Endpoint nicht gefunden: {$method} {$path}", 404);

} catch (Exception $e) {
    errorResponse('Server Error: ' . $e->getMessage(), 500);
}

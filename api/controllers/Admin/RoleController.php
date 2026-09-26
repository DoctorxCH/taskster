<?php

class RoleController {
    private PermissionService $permissionService;

    public function __construct() {
        $this->permissionService = new PermissionService();
    }
    
    private function requireAuth(): array {
        // Mocked auth check, in reality this should use the JWT logic from index.php
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';
        if (!$auth) {
            throw new Exception('error.auth.unauthorized', 401);
        }
        return ['id' => 'mock-user-id', 'is_superadmin' => true];
    }

    public function index(): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.roles.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $roles = Database::fetchAll("SELECT id, `key`, name, level, description, is_system, sort_order FROM roles ORDER BY sort_order ASC");
        return ['roles' => $roles];
    }

    public function show(string $id): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.roles.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $role = Database::fetch("SELECT * FROM roles WHERE id = ?", [$id]);
        if (!$role) {
            throw new Exception('error.role.not_found', 404);
        }
        
        // Fetch permissions for this role
        $permissions = Database::fetchAll("
            SELECT p.id, p.`key`, p.entity, p.action, p.description, rp.scope, rp.conditions 
            FROM role_permissions rp
            JOIN permissions p ON rp.permission_id = p.id
            WHERE rp.role_id = ?
        ", [$id]);
        
        $role['permissions'] = $permissions;

        return ['role' => $role];
    }
}

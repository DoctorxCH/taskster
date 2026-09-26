<?php

class CompanyController {
    private PermissionService $permissionService;

    public function __construct() {
        $this->permissionService = new PermissionService();
    }

    public function index(): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.companies.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companies = Database::fetchAll("SELECT id, name, status, subscription_plan, max_users, max_storage_gb, max_tasks_per_month, api_rate_limit_per_minute, created_at FROM companies ORDER BY name ASC");
        return ['companies' => $companies];
    }

    public function show(string $id): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.companies.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $company = Database::fetch("SELECT * FROM companies WHERE id = ?", [$id]);
        if (!$company) {
            throw new Exception('error.company.not_found', 404);
        }
        
        $company['settings'] = json_decode($company['settings'] ?: '{}', true);
        $company['auth_policy'] = json_decode($company['auth_policy'] ?: '{}', true);
        $company['sso_config'] = json_decode($company['sso_config'] ?: '{}', true);

        return ['company' => $company];
    }

    public function update(array $body, string $id): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.companies.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $company = Database::fetch("SELECT id FROM companies WHERE id = ?", [$id]);
        if (!$company) {
            throw new Exception('error.company.not_found', 404);
        }

        $updates = [];
        $params = [];
        $allowedFields = ['name', 'status', 'subscription_plan', 'billing_email', 'stripe_customer_id', 'max_users', 'max_storage_gb', 'max_tasks_per_month', 'api_rate_limit_per_minute'];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $updates[] = "$field = ?";
                $params[] = $body[$field];
            }
        }
        
        if (isset($body['settings'])) {
            $updates[] = "settings = ?";
            $params[] = json_encode($body['settings']);
        }
        if (isset($body['auth_policy'])) {
            $updates[] = "auth_policy = ?";
            $params[] = json_encode($body['auth_policy']);
        }
        if (isset($body['sso_config'])) {
            $updates[] = "sso_config = ?";
            $params[] = json_encode($body['sso_config']);
        }

        if (empty($updates)) {
            throw new Exception('error.validation.no_updates', 400);
        }

        $params[] = $id;
        $sql = "UPDATE companies SET " . implode(', ', $updates) . " WHERE id = ?";
        Database::execute($sql, $params);

        return ['message' => 'success.company.updated'];
    }
}

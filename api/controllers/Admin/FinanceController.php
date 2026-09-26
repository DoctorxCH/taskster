<?php

class FinanceController {
    private PermissionService $permissionService;
    private FinanceService $financeService;

    public function __construct() {
        $this->permissionService = new PermissionService();
        $this->financeService = new FinanceService();
    }
    
    private function requireAuth(): array {
        // Mocked auth check
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';
        if (!$auth) {
            throw new Exception('error.auth.unauthorized', 401);
        }
        return ['id' => 'mock-user-id', 'company_id' => 'company-123'];
    }

    public function logTime(array $body): array {
        $user = $this->requireAuth();
        
        // Ensure user has basic time logging permission (could be implicit for any user, but let's check)
        if (!$this->permissionService->hasPermission($user['id'], 'time.log')) {
            throw new Exception('error.auth.forbidden', 403);
        }
        
        if (empty($body['duration_minutes'])) {
            throw new Exception('error.validation.missing_duration', 400);
        }

        return $this->financeService->logTime($body, $user['company_id'], $user['id']);
    }
    
    public function getRevenueReport(): array {
        $user = $this->requireAuth();
        
        // Only admins/finance roles should see this
        if (!$this->permissionService->hasPermission($user['id'], 'admin.finance.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }
        
        $startDate = $_GET['start'] ?? date('Y-m-d', strtotime('-30 days'));
        $endDate = $_GET['end'] ?? date('Y-m-d');

        return $this->financeService->getRevenueReport($user['company_id'], $startDate, $endDate);
    }
    
    public function setInvoiceRule(array $body): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.finance.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }
        
        if (empty($body['entity_type']) || empty($body['entity_id']) || !isset($body['hourly_rate'])) {
            throw new Exception('error.validation.missing_fields', 400);
        }
        
        $sql = "
            INSERT INTO invoice_rules (company_id, entity_type, entity_id, hourly_rate, currency)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE hourly_rate = VALUES(hourly_rate), currency = VALUES(currency)
        ";
        
        Database::execute($sql, [
            $user['company_id'],
            $body['entity_type'],
            $body['entity_id'],
            $body['hourly_rate'],
            $body['currency'] ?? 'CHF'
        ]);
        
        return ['message' => 'success.finance.rule_updated'];
    }
}

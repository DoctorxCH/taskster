<?php

class WorkflowController {
    private PermissionService $permissionService;

    public function __construct() {
        $this->permissionService = new PermissionService();
    }

    public function index(): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.workflow.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companyId = $user['company_id'];
        
        $statuses = Database::fetchAll("
            SELECT * FROM task_statuses 
            WHERE company_id = ? OR company_id IS NULL 
            ORDER BY sort_order ASC
        ", [$companyId]);
        
        $priorities = Database::fetchAll("
            SELECT * FROM task_priorities 
            WHERE company_id = ? OR company_id IS NULL 
            ORDER BY level ASC
        ", [$companyId]);

        return ['statuses' => $statuses, 'priorities' => $priorities];
    }
    
    public function updateStatus(array $body, string $id): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.workflow.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companyId = $user['company_id'];
        
        // Ensure the status belongs to this company and is not a system status
        $status = Database::fetch("SELECT id, is_system FROM task_statuses WHERE id = ? AND company_id = ?", [$id, $companyId]);
        if (!$status) {
            throw new Exception('error.workflow.not_found', 404);
        }
        if ($status['is_system']) {
            throw new Exception('error.workflow.cannot_edit_system', 400);
        }
        
        $updates = [];
        $params = [];
        $allowedFields = ['label_key', 'color', 'is_completed', 'sort_order'];

        foreach ($allowedFields as $field) {
            if (isset($body[$field])) {
                $updates[] = "$field = ?";
                $params[] = $body[$field];
            }
        }

        if (empty($updates)) {
            throw new Exception('error.validation.no_updates', 400);
        }

        $params[] = $id;
        $sql = "UPDATE task_statuses SET " . implode(', ', $updates) . " WHERE id = ?";
        Database::execute($sql, $params);

        return ['message' => 'success.workflow.status_updated'];
    }
}

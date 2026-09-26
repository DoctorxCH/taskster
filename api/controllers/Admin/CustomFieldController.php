<?php

class CustomFieldController {
    private PermissionService $permissionService;

    public function __construct() {
        $this->permissionService = new PermissionService();
    }

    public function index(): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.custom_fields.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        // We fetch fields that belong to a specific generic folder or globally. 
        // For now, let's fetch all template fields.
        // The real implementation would probably filter by a template folder ID for this company.
        $fields = Database::fetchAll("
            SELECT * FROM folder_field_definitions 
            ORDER BY sort_order ASC
        ");

        return ['fields' => $fields];
    }
    
    public function update(array $body, string $id): array {
        $user = requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.custom_fields.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }
        
        $field = Database::fetch("SELECT id, is_system FROM folder_field_definitions WHERE id = ?", [$id]);
        if (!$field) {
            throw new Exception('error.custom_fields.not_found', 404);
        }
        if ($field['is_system']) {
            throw new Exception('error.custom_fields.cannot_edit_system', 400);
        }
        
        $updates = [];
        $params = [];
        $allowedFields = ['label', 'label_key', 'is_required', 'options', 'formula'];

        foreach ($allowedFields as $f) {
            if (isset($body[$f])) {
                $updates[] = "$f = ?";
                $params[] = $body[$f];
            }
        }
        
        if (isset($body['logic_rules'])) {
            $updates[] = "logic_rules = ?";
            $params[] = json_encode($body['logic_rules']);
        }
        if (isset($body['validation_rules'])) {
            $updates[] = "validation_rules = ?";
            $params[] = json_encode($body['validation_rules']);
        }
        if (isset($body['visibility_conditions'])) {
            $updates[] = "visibility_conditions = ?";
            $params[] = json_encode($body['visibility_conditions']);
        }

        if (empty($updates)) {
            throw new Exception('error.validation.no_updates', 400);
        }

        $params[] = $id;
        $sql = "UPDATE folder_field_definitions SET " . implode(', ', $updates) . " WHERE id = ?";
        Database::execute($sql, $params);

        return ['message' => 'success.custom_fields.updated'];
    }
}

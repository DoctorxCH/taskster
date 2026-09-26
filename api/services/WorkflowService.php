<?php

class WorkflowService {
    private FileCache $cache;

    public function __construct() {
        $this->cache = new FileCache();
    }

    /**
     * Checks if a transition from one status to another is valid based on company rules.
     */
    public function canTransition(string $fromStatus, string $toStatus, ?string $companyId, string $userId): bool {
        // System defaults or company-specific transitions
        $sql = "
            SELECT required_role_id, conditions 
            FROM task_status_transitions 
            WHERE from_status_key = ? AND to_status_key = ? 
            AND (company_id = ? OR company_id IS NULL)
            ORDER BY company_id DESC 
            LIMIT 1
        ";
        
        $transition = Database::fetch($sql, [$fromStatus, $toStatus, $companyId]);
        
        // If no transition rule is defined, we assume it's allowed for backward compatibility
        // (In a strict mode, we'd return false here)
        if (!$transition) {
            return true;
        }
        
        // Check required role if one is set
        if ($transition['required_role_id']) {
            $hasRole = Database::fetch("
                SELECT 1 FROM user_company_roles 
                WHERE user_id = ? AND company_id = ? AND role_id = ?
            ", [$userId, $companyId, $transition['required_role_id']]);
            
            if (!$hasRole) {
                return false;
            }
        }
        
        // Additional JSON conditions could be evaluated here (e.g. required custom fields)
        if ($transition['conditions']) {
            // Evaluator logic...
        }
        
        return true;
    }

    /**
     * Retrieves visible and required custom fields for a specific task state.
     */
    public function getActiveCustomFields(string $folderId, string $currentStatusKey, ?string $companyId): array {
        $fields = Database::fetchAll("
            SELECT * FROM folder_field_definitions 
            WHERE folder_id = ? ORDER BY sort_order ASC
        ", [$folderId]);
        
        $activeFields = [];
        
        foreach ($fields as $field) {
            $isVisible = true;
            $isRequired = (bool)$field['is_required'];
            
            // Check visibility conditions
            if ($field['visibility_conditions']) {
                $cond = json_decode($field['visibility_conditions'], true) ?: [];
                if (isset($cond['visible_only_on_status']) && is_array($cond['visible_only_on_status'])) {
                    if (!in_array($currentStatusKey, $cond['visible_only_on_status'])) {
                        $isVisible = false;
                    }
                }
                if (isset($cond['required_when_status']) && is_array($cond['required_when_status'])) {
                    if (in_array($currentStatusKey, $cond['required_when_status'])) {
                        $isRequired = true;
                    }
                }
            }
            
            if ($isVisible) {
                $field['is_required'] = $isRequired;
                $activeFields[] = $field;
            }
        }
        
        return $activeFields;
    }
}

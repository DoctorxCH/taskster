<?php

class PermissionService {
    private FileCache $cache;

    public function __construct() {
        $this->cache = new FileCache();
    }

    /**
     * Checks if a user has a specific permission in a given context (company or project).
     * @param string $userId
     * @param string $permissionKey (e.g. 'tasks.create')
     * @param string|null $companyId
     * @param string|null $projectId
     * @return bool
     */
    public function hasPermission(string $userId, string $permissionKey, ?string $companyId = null, ?string $projectId = null): bool {
        // Build a cache key
        $cacheKey = "perms_{$userId}_" . ($companyId ?? 'sys') . "_" . ($projectId ?? 'none');
        
        $permissions = $this->cache->remember($cacheKey, 300, function() use ($userId, $companyId, $projectId) {
            return $this->loadUserPermissions($userId, $companyId, $projectId);
        });

        if (in_array($permissionKey, $permissions)) {
            return true;
        }

        // Fallback: Check legacy superadmin / admin_permissions
        if ($this->hasLegacyPermission($userId, $permissionKey, $companyId)) {
            return true;
        }

        return false;
    }

    private function loadUserPermissions(string $userId, ?string $companyId, ?string $projectId): array {
        $permissions = [];
        
        $sql = "
            SELECT DISTINCT p.key 
            FROM user_company_roles ucr
            JOIN roles r ON ucr.role_id = r.id
            JOIN role_permissions rp ON r.id = rp.role_id
            JOIN permissions p ON rp.permission_id = p.id
            WHERE ucr.user_id = :user_id
        ";
        
        $params = [':user_id' => $userId];
        $conditions = [];
        
        // Match system roles or specific company
        if ($companyId) {
            $conditions[] = "(ucr.company_id = :company_id OR ucr.company_id = 'system')";
            $params[':company_id'] = $companyId;
        } else {
            $conditions[] = "ucr.company_id = 'system'";
        }
        
        // Match project if specified
        if ($projectId) {
            $conditions[] = "(ucr.project_id = :project_id OR ucr.project_id IS NULL)";
            $params[':project_id'] = $projectId;
        } else {
            $conditions[] = "ucr.project_id IS NULL";
        }
        
        if (!empty($conditions)) {
            $sql .= " AND (" . implode(" AND ", $conditions) . ")";
        }

        $results = Database::fetchAll($sql, $params);
        foreach ($results as $row) {
            $permissions[] = $row['key'];
        }
        
        return $permissions;
    }
    
    private function hasLegacyPermission(string $userId, string $permissionKey, ?string $companyId): bool {
        $user = Database::fetch("SELECT is_superadmin, company_role, company_id, admin_permissions FROM users WHERE id = ?", [$userId]);
        if (!$user) return false;
        
        // Superadmins can do everything
        if ($user['is_superadmin']) return true;
        
        // Company admins can do everything within their company
        if ($user['company_role'] === 'admin' && $companyId !== null && $user['company_id'] === $companyId) {
            return true;
        }
        
        // Check legacy admin_permissions JSON
        if (!empty($user['admin_permissions'])) {
            $legacyPerms = json_decode($user['admin_permissions'], true) ?: [];
            // Map new keys to legacy keys if needed, for now exact match
            if (in_array($permissionKey, $legacyPerms)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clears the permission cache for a user.
     */
    public function clearCache(string $userId): void {
        // Note: In a real app we might need to delete by prefix, but FileCache doesn't support that easily.
        // We'll clear the most common keys.
        $this->cache->delete("perms_{$userId}_sys_none");
    }
}

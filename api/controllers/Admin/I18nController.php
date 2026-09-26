<?php

class I18nController {
    private PermissionService $permissionService;
    private TranslationService $translationService;

    public function __construct() {
        $this->permissionService = new PermissionService();
        $this->translationService = new TranslationService();
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

    public function index(): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.i18n.view')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companyId = $user['company_id'];
        
        // Fetch custom translations for this company
        $translations = Database::fetchAll("
            SELECT id, locale, `key`, value 
            FROM i18n_translations 
            WHERE company_id = ?
            ORDER BY `key` ASC, locale ASC
        ", [$companyId]);

        return ['translations' => $translations];
    }
    
    public function upsert(array $body): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.i18n.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companyId = $user['company_id'];
        
        if (empty($body['locale']) || empty($body['key']) || !isset($body['value'])) {
            throw new Exception('error.validation.missing_fields', 400);
        }

        $sql = "
            INSERT INTO i18n_translations (locale, `key`, value, company_id) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE value=VALUES(value)
        ";
        
        Database::execute($sql, [
            $body['locale'],
            $body['key'],
            $body['value'],
            $companyId
        ]);
        
        // Invalidate cache
        $this->translationService->clearCache($body['locale'], $companyId);

        return ['message' => 'success.i18n.upserted'];
    }
    
    public function delete(array $body, string $id): array {
        $user = $this->requireAuth();
        if (!$this->permissionService->hasPermission($user['id'], 'admin.i18n.edit')) {
            throw new Exception('error.auth.forbidden', 403);
        }

        $companyId = $user['company_id'];
        
        $trans = Database::fetch("SELECT locale FROM i18n_translations WHERE id = ? AND company_id = ?", [$id, $companyId]);
        if (!$trans) {
            throw new Exception('error.i18n.not_found', 404);
        }

        Database::execute("DELETE FROM i18n_translations WHERE id = ?", [$id]);
        
        $this->translationService->clearCache($trans['locale'], $companyId);

        return ['message' => 'success.i18n.deleted'];
    }
}

<?php

class SystemController {
    private TokenService $tokenService;
    private PermissionService $permissionService;

    public function __construct() {
        $this->tokenService = new TokenService();
        $this->permissionService = new PermissionService();
    }
    
    private function getAuthUser(): ?array {
        // Mocked auth logic. Normally decodes JWT.
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';
        if (!$auth) {
            return null;
        }
        return ['id' => 'mock-user-id', 'company_id' => 'company-123'];
    }

    /**
     * GET /api/system/bootstrap
     * Returns a consolidated payload of tokens, features, locales, and permissions
     * to prevent multiple round-trips when the SPA boots.
     */
    public function bootstrap(): array {
        $user = $this->getAuthUser();
        $companyId = $user ? $user['company_id'] : null;
        
        $tokens = $this->tokenService->getResolvedTokens($companyId);
        
        $payload = [
            'design_tokens' => $tokens,
            'features' => [],
            'locales_delta' => []
        ];
        
        if ($user) {
            // Include feature flags based on company settings
            $company = Database::fetch("SELECT settings FROM companies WHERE id = ?", [$companyId]);
            if ($company && $company['settings']) {
                $payload['features'] = json_decode($company['settings'], true) ?: [];
            }
            
            // Add global feature flags based on system environment if needed
            $payload['features']['RBAC_V2'] = true;
            $payload['features']['DESIGN_V2'] = true;
            
            // We would also fetch user permissions here later when the frontend is ready
            // $payload['permissions'] = ...
        }
        
        return $payload;
    }
}

<?php

class TokenService {
    private FileCache $cache;

    public function __construct() {
        $this->cache = new FileCache();
    }

    /**
     * Resolves the final design tokens for a given company.
     * System default tokens are overridden by company-specific tokens.
     */
    public function getResolvedTokens(?string $companyId): array {
        $cacheKey = 'design_tokens_' . ($companyId ?? 'system');
        
        return $this->cache->remember($cacheKey, 3600, function() use ($companyId) {
            $tokens = [];
            
            // 1. Fetch system defaults
            $systemTokens = Database::fetchAll("SELECT `key`, value, category, subcategory, mode FROM design_tokens WHERE is_system = 1 AND company_id IS NULL");
            foreach ($systemTokens as $t) {
                $tokens[$t['key']] = $t['value'];
            }
            
            // 2. Override with company-specific tokens if applicable
            if ($companyId) {
                $companyTokens = Database::fetchAll("SELECT `key`, value, category, subcategory, mode FROM design_tokens WHERE company_id = ?", [$companyId]);
                foreach ($companyTokens as $t) {
                    $tokens[$t['key']] = $t['value'];
                }
            }
            
            return $tokens;
        });
    }

    /**
     * Generates a CSS string containing CSS variables for the resolved tokens.
     * Helpful if the frontend wants the raw CSS block to inject into a <style> tag.
     */
    public function generateCssVariables(?string $companyId): string {
        $tokens = $this->getResolvedTokens($companyId);
        $css = ":root {\n";
        foreach ($tokens as $key => $value) {
            // Ensure valid CSS variable name formatting
            $varName = '--' . preg_replace('/[^a-zA-Z0-9-]/', '-', $key);
            $css .= "  {$varName}: {$value};\n";
        }
        $css .= "}\n";
        return $css;
    }
    
    public function clearCache(?string $companyId = null): void {
        $cacheKey = 'design_tokens_' . ($companyId ?? 'system');
        $this->cache->delete($cacheKey);
    }
}

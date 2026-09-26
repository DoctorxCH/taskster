<?php

class TranslationService {
    private FileCache $cache;

    public function __construct() {
        $this->cache = new FileCache();
    }

    /**
     * Resolves the final translations for a specific locale and company.
     * System defaults are overridden by company-specific translations.
     */
    public function getTranslations(string $locale, ?string $companyId): array {
        $cacheKey = "i18n_{$locale}_" . ($companyId ?? 'system');
        
        return $this->cache->remember($cacheKey, 3600, function() use ($locale, $companyId) {
            $translations = [];
            
            // 1. Fetch system defaults
            $systemTrans = Database::fetchAll("
                SELECT `key`, value FROM i18n_translations 
                WHERE locale = ? AND is_system = 1 AND company_id IS NULL
            ", [$locale]);
            
            foreach ($systemTrans as $t) {
                $translations[$t['key']] = $t['value'];
            }
            
            // 2. Override with company-specific translations if applicable
            if ($companyId) {
                $companyTrans = Database::fetchAll("
                    SELECT `key`, value FROM i18n_translations 
                    WHERE locale = ? AND company_id = ?
                ", [$locale, $companyId]);
                
                foreach ($companyTrans as $t) {
                    $translations[$t['key']] = $t['value'];
                }
            }
            
            return $translations;
        });
    }
    
    /**
     * Gets translation with fallback (e.g. from 'de-CH' to 'de' to 'en')
     */
    public function getTranslationsWithFallback(string $locale, ?string $companyId): array {
        $primary = $this->getTranslations($locale, $companyId);
        
        // Very basic fallback logic: if locale is like de-CH, try de, then en
        $parts = explode('-', $locale);
        $fallbacks = [];
        if (count($parts) > 1) {
            $fallbacks[] = $parts[0];
        }
        if ($parts[0] !== 'en') {
            $fallbacks[] = 'en';
        }
        
        // Merge from lowest priority to highest
        $finalTranslations = [];
        $fallbacks = array_reverse($fallbacks);
        
        foreach ($fallbacks as $fb) {
            $fbTrans = $this->getTranslations($fb, $companyId);
            $finalTranslations = array_merge($finalTranslations, $fbTrans);
        }
        
        // Primary locale wins
        return array_merge($finalTranslations, $primary);
    }

    public function clearCache(string $locale, ?string $companyId = null): void {
        $cacheKey = "i18n_{$locale}_" . ($companyId ?? 'system');
        $this->cache->delete($cacheKey);
    }
}

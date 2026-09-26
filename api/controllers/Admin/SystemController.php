<?php

use PDO;
use Exception;

class SystemController {
    public function bootstrap() {
        $pdo = getDb();
        
        $features = [
            'finance' => true,
            'workflow' => true,
            'custom_fields' => true,
            'companies' => true,
            'roles' => true
        ];
        
        $tokens = [
            'primary' => '#00A3C4'
        ];
        
        $localesDelta = [];
        
        try {
            $stmt = $pdo->query("SELECT `key`, `value` FROM i18n_translations");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $localesDelta[$row['key']] = $row['value'];
            }
        } catch (Exception $e) {
            // Ignore if table doesn't exist yet
        }
        
        return [
            'design_tokens' => $tokens,
            'features' => $features,
            'locales_delta' => $localesDelta
        ];
    }
}

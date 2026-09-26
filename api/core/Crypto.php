<?php

class Crypto {
    /**
     * Gets the encryption key from environment variable or uses a fallback.
     */
    private static function getKey(): string {
        $key = getEnvValue('APP_KEY', '');
        if (empty($key)) {
            // Use JWT_SECRET as a fallback if APP_KEY is not set
            $key = getEnvValue('JWT_SECRET', 'taskster-super-secret-key-2026-safe-production');
        }
        
        // Hash the key to ensure it's exactly 32 bytes for AES-256
        return hash('sha256', $key, true);
    }

    /**
     * Encrypts a string using AES-256-GCM.
     */
    public static function encrypt(string $plaintext): string {
        $key = self::getKey();
        $cipher = 'aes-256-gcm';
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $tag = '';
        
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
        
        // Combine IV, tag and ciphertext
        $combined = base64_encode($iv . $tag . $ciphertext_raw);
        return $combined;
    }

    /**
     * Decrypts a string using AES-256-GCM.
     */
    public static function decrypt(string $encrypted): ?string {
        try {
            $key = self::getKey();
            $cipher = 'aes-256-gcm';
            
            $c = base64_decode($encrypted);
            $ivlen = openssl_cipher_iv_length($cipher);
            
            if (strlen($c) < $ivlen + 16) {
                return null; // Invalid format
            }
            
            $iv = substr($c, 0, $ivlen);
            $tag = substr($c, $ivlen, 16);
            $ciphertext_raw = substr($c, $ivlen + 16);
            
            $plaintext = openssl_decrypt($ciphertext_raw, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
            
            return $plaintext !== false ? $plaintext : null;
        } catch (Exception $e) {
            return null;
        }
    }
}

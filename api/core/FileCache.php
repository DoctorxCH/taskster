<?php

class FileCache {
    private string $cacheDir;

    public function __construct() {
        // Use the existing .data directory in the project root
        $this->cacheDir = __DIR__ . '/../../.data/cache/';
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed {
        $file = $this->getFilePath($key);
        if (!file_exists($file)) {
            return $default;
        }

        $content = file_get_contents($file);
        $data = json_decode($content, true);

        if ($data === null || !isset($data['expires_at']) || !isset($data['value'])) {
            $this->delete($key);
            return $default;
        }

        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            $this->delete($key);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttlSeconds = 0): bool {
        $file = $this->getFilePath($key);
        $expiresAt = $ttlSeconds > 0 ? time() + $ttlSeconds : 0;
        
        $data = [
            'expires_at' => $expiresAt,
            'value' => $value
        ];
        
        return file_put_contents($file, json_encode($data)) !== false;
    }

    public function delete(string $key): bool {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }
    
    public function remember(string $key, int $ttlSeconds, callable $callback): mixed {
        $value = $this->get($key);
        if ($value !== null) {
            return $value;
        }
        
        $value = call_user_func($callback);
        $this->set($key, $value, $ttlSeconds);
        return $value;
    }

    private function getFilePath(string $key): string {
        // Sanitize key to prevent path traversal
        $safeKey = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        return $this->cacheDir . md5($safeKey) . '_' . $safeKey . '.json';
    }
}

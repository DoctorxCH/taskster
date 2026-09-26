<?php

class Autoloader {
    private string $baseDir;

    public function __construct(string $baseDir) {
        $this->baseDir = $baseDir;
    }

    public function register(): void {
        spl_autoload_register([$this, 'loadClass']);
    }

    public function loadClass(string $className): bool {
        // Only autoload classes from our namespace or without namespace in our directories
        // Assuming no namespaces for simplicity in this project, just map class name to file
        
        $directories = [
            $this->baseDir . '/core/',
            $this->baseDir . '/services/',
            $this->baseDir . '/controllers/',
            $this->baseDir . '/controllers/Admin/'
        ];

        foreach ($directories as $dir) {
            $file = $dir . $className . '.php';
            if (file_exists($file)) {
                require_once $file;
                return true;
            }
        }

        return false;
    }
}

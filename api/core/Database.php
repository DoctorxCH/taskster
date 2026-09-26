<?php

class Database {
    private static ?PDO $pdo = null;

    /**
     * Gibt die PDO-Verbindung zurück. Nutzt das bestehende getDb() aus index.php.
     */
    public static function connection(): PDO {
        if (self::$pdo === null) {
            self::$pdo = getDb();
        }
        return self::$pdo;
    }

    /**
     * Führt eine SQL-Query aus und gibt alle Ergebnisse als assoziatives Array zurück.
     */
    public static function fetchAll(string $sql, array $params = []): array {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Führt eine SQL-Query aus und gibt das erste Ergebnis (oder null) zurück.
     */
    public static function fetch(string $sql, array $params = []): ?array {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Führt eine SQL-Query aus (z.B. INSERT, UPDATE, DELETE) und gibt die Anzahl der betroffenen Zeilen zurück.
     */
    public static function execute(string $sql, array $params = []): int {
        $stmt = self::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    
    /**
     * Startet eine Datenbank-Transaktion.
     */
    public static function beginTransaction(): bool {
        return self::connection()->beginTransaction();
    }
    
    /**
     * Bestätigt eine Datenbank-Transaktion.
     */
    public static function commit(): bool {
        return self::connection()->commit();
    }
    
    /**
     * Macht eine Datenbank-Transaktion rückgängig.
     */
    public static function rollBack(): bool {
        return self::connection()->rollBack();
    }
    
    /**
     * Gibt die zuletzt eingefügte ID zurück.
     */
    public static function lastInsertId(): string {
        return self::connection()->lastInsertId();
    }
}

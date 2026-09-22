# 2026-09-23: Fix 'trial_ends_at' Spalte in users & MySQL Migration

## Fehleranalyse
- **Symptom:** Login schlägt auf dem Remote-Server fehl mit:
  `Server Error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 'trial_ends_at' in 'field list'`
- **Root Cause:**
  1. Auf der Remote-MariaDB/MySQL (`d44809_taskster_26` auf `sql21.hostcreators.sk`) fehlte noch die Spalte `trial_ends_at` in der Tabelle `users`.
  2. Im PHP-Backend (`ensureTables()`) wurden `$colMigrations` erst nach `seedTemplates()` ausgeführt. Da `seedTemplates()` beim ersten Aufruf auf einer bestehenden Tabelle scheiterte (bevor `name_key` und `description_key` hinzugefügt wurden), brach `ensureTables()` im äußeren `try`-Block ab, wodurch die `ALTER TABLE`-Befehle übersprungen wurden.
  3. `getUserPlanDetails()` fragte `SELECT trial_ends_at, is_pro FROM users WHERE id = ?` ohne defensiven Try-Catch ab.

## Durchgeführte Behebungen
1. **Remote-Datenbank migriert:**
   - `scripts/migrate-mysql.cjs` remote gegen Hostcreators MySQL ausgeführt.
   - Spalte `trial_ends_at DATETIME NULL` wurde der Tabelle `users` erfolgreich hinzugefügt und verifiziert.
2. **PHP-Backend gehärtet (`public/api/index.php`, `api/index.php`, `server-php/index.php`):**
   - `$colMigrations` in `ensureTables($pdo)` an den absoluten Beginn vor `seedTemplates()` verschoben.
   - `getUserPlanDetails()` mit defensivem `try-catch` abgesichert: Falls die Spalte unerwartet fehlen sollte, fällt das System transparent auf `$user['trial_ends_at']` bzw. Standardwerte zurück, ohne einen 500-Fehler zu werfen.
3. **Build & Deployment:**
   - `python scripts/check-php-syntax.py` erfolgreich ausgeführt.
   - `npm run build:dist` neu gebaut und synchronisiert.
   - Per Git Commit `876061f` nach `origin/main` gepusht.

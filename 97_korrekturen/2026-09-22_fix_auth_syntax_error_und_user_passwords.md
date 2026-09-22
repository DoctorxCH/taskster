# 2026-09-22 – Behebung Login- & Registrierungsfehler (PHP Syntax Error & MySQL Passwort-Sync)

## Problembeschreibung
- **Symptom:** Benutzer konnten sich weder anmelden noch registrieren (`POST /api/auth/login` und `POST /api/auth/register` lieferten HTTP 500).
- **Ursache 1:** In Commit `410519a` wurde in `api/index.php`, `server-php/index.php` und `public/api/index.php` in Funktion `parseMimeEmailText` auf Zeile 1790 eine unmaskierte Single-Quote in einem Single-Quoted Regex-String eingefügt:
  `preg_match('/boundary=["\']?([^"';\r\n]+)["\']?/i', ...)`
  Dies erzeugte einen fatalen PHP-Parse-Error (Syntax Error), wodurch der gesamte PHP-Interpreter auf dem Server abstürzte und jeden API-Request mit HTTP 500 abbrach.
- **Ursache 2:** Die Demo-Benutzerkonten (`admin@kurka.ch`, `marc@kurka.ch`, `sarah.editor@kurka.ch`, `lukas.viewer@kurka.ch`) in der entfernten MySQL-Datenbank enthielten noch alte Passwort-Hashes von vor der Secret-Bereinigung, die nicht mit dem neuen Standard-Kennwort `'password123'` übereinstimmten. Zudem war Sarah Kellers E-Mail in der DB noch `sarah.editor@swissinfra.ch` statt `sarah.editor@kurka.ch`.

## Durchgeführte Behebungen
1. **PHP Syntax-Korrektur (`api/index.php`, `server-php/index.php`, `public/api/index.php`):**
   - Zeile 1790: Single-Quote im Regex korrekt maskiert: `preg_match('/boundary=["\']?([^"\'\r\n;]+)["\']?/i', ...)`
   - Zeile 2244: `$db = getDb();` in den `try { ... }`-Block verschoben, damit Verbindungsfehler nicht unhandled abstürzen.
   - Zeile 8405: `catch (Exception $e)` zu `catch (Throwable $e)` erweitert, um auch DB/PDO-Exceptions sauber als JSON abzufangen.
   - Syntax-Prüfung via `npm run php:check` erfolgreich durchgeführt.
2. **MySQL Migration & User Seed (`scripts/migrate-mysql.cjs`):**
   - `ON DUPLICATE KEY UPDATE` um `email=VALUES(email)` erweitert.
   - `node scripts/migrate-mysql.cjs` gegen die Remote-MariaDB/MySQL ausgeführt.
   - Alle Passwörter (`admin@kurka.ch`, `marc@kurka.ch`, `sarah.editor@kurka.ch`, `lukas.viewer@kurka.ch`) erfolgreich auf `'password123'` synchronisiert.
3. **Lokale SQLite DB:**
   - `node server/db/seed.js` ausgeführt, lokale Passwörter ebenfalls auf `'password123'` synchronisiert.
4. **Build & Code Index:**
   - `npm run build:dist` ausgeführt, Client-Artefakte und HTML-Dateien synchronisiert.
   - `python generate_index.py` ausgeführt.

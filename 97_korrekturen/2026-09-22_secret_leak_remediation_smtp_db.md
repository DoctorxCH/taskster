# 2026-09-22 – Secret Leak Remediation: Entfernung hartkodierter SMTP- & DB-Zugangsdaten

## Kontext
- **Alarm:** GitGuardian Sicherheitswarnung ("SMTP credentials exposed on GitHub").
- **Gefahr:** Das Kennwort war im Klartext in mehreren Backend-Dateien, in `.env.example`, im Quick-Login-Button von `pages/login.vue` und in den kompilierten Client-Bundles (`_nuxt/*.js`) enthalten.

## Durchgeführte Behebungen
1. **Frontend (`pages/login.vue`):**
   - Quick-Login für `admin@kurka.ch` von echtem Passwort auf Standard-Entwicklungs-Kennwort `'password123'` umgestellt.
2. **Backend TypeScript (`server/utils/mailer.ts`, `server/db/index.ts`):**
   - Hartkodierte Fallbacks entfernt. Passwort wird primär aus der Datenbanktabelle `system_settings` bzw. `process.env.SMTP_PASSWORD` bezogen.
3. **Backend PHP (`server-php/index.php`, `public/api/index.php`, `api/index.php`):**
   - DB-Passwort und SMTP-Passwort aus hartkodierten Strings entfernt.
   - Initialisierung greift nun über `getEnvValue('DB_PASSWORD', '')` und `getEnvValue('SMTP_PASSWORD', '')` sicher auf die `.env` bzw. Server-Umgebungsvariablen zu.
4. **Migrationsskript (`scripts/migrate-mysql.cjs`):**
   - Verbindungsaufbau liest Zugangsdaten nun aus `.env` (`process.env.DB_PASSWORD`), kein Hardcoding mehr.
5. **Dokumentation & Vorlagen:**
   - `.env.example`: Passwort mit Dummy-Platzhalter (`your_db_password_here`, `your_smtp_password_here`) versehen.
   - `97_korrekturen/2026-09-20_admin_email_smtp_and_triggers.md`: Kennwort unkenntlich gemacht (`[REDACTED_SECRET]`).
6. **Git-Ignore & Lokale Umgebung:**
   - `.env` (durch `.gitignore` geschützt) speichert die Zugangsdaten sicher lokal.
7. **Production Build & Asset Regeneration:**
   - `npm run build:dist` ausgeführt. Veraltetes Bundle (`_nuxt/C8QYex3Q.js`) und statische HTML-Seiten wurden neu generiert und vollständig von Secret-Strings bereinigt.

## Dringende Folge-Empfehlung
- Da das Repository öffentlich auf GitHub liegt und das Secret bereits gepusht war, muss das Kennwort bei Hostcreators (Mailserver `mail.kurka.ch` / Postfach `noreply@kurka.ch` und ggf. MySQL) umgehend geändert werden, da automatisierte Bots exponierte Secrets innerhalb von Sekunden mitschneiden.

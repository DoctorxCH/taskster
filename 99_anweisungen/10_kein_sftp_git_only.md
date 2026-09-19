# Anweisung: Deployment-Workflow & Hosting-Architektur

## 1. Hosting-Beschränkungen (Hostcreators Shared-Hosting)
- Der Server unter `admin.kurka.ch@ssh.kurka.ch` (`/var/www18/p50824/kurka.ch/sub/taskster`) ist ein Apache/PHP-Shared-Hosting.
- Auf dem Server **fehlt der C-Compiler (`cc`)** und GLIBC 2.29 für native Node-Module (`better-sqlite3`).
- **Wichtig:** Auf dem Server kann **kein** `npm install` oder `npm run build` ausgeführt werden!

## 2. Verbindlicher Deployment-Ablauf für Agenten
1. **Git als Code-Source:** Alle Quellcode-Änderungen lokal committen und nach `origin/main` pushen.
2. **Lokaler Build:** Lokal `npm run generate` ausführen. Dies erzeugt den statischen Produktions-Build unter `.output/public/` mit allen vorgerenderten Seiten und `api/index.php`.
3. **Automatischer Sync:** `node scripts/deploy-sftp.cjs` ausführen. Dieses Skript überträgt den fertigen Build aus `.output/public` direkt per SFTP auf den Server in `/sub/taskster`.
4. **Datenbank-Migrationen:** Falls Schema-Änderungen anfallen, `node scripts/migrate-mysql.cjs` lokal ausführen, um die Remote-MySQL-Datenbank auf `sql21.hostcreators.sk:3326` zu aktualisieren.

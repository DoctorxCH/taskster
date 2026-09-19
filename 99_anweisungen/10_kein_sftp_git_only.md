# Anweisung: Deployment-Workflow & Hosting-Architektur

## 1. Hosting-Beschränkungen (Hostcreators Shared-Hosting)
- Der Server unter `admin.kurka.ch@ssh.kurka.ch` (`/var/www18/p50824/kurka.ch/sub/taskster`) ist ein Apache/PHP-Shared-Hosting.
- Auf dem Server **fehlt der C-Compiler (`cc`)** und GLIBC 2.29 für native Node-Module (`better-sqlite3`).
- **Wichtig:** Auf dem Server kann **kein** `npm install` oder `npm run build` ausgeführt werden!

## 2. Verbindlicher Deployment-Ablauf für Agenten (Git Only)
1. **Lokaler Build & Root-Sync:** Lokal `npm run build:dist` ausführen. Dies baut Nuxt (`nuxt generate`) und synchronisiert `.output/public` via `scripts/sync-dist.cjs` direkt in das Git-Root-Verzeichnis (inklusive `_nuxt/`, `time/`, `dashboard/`, `admin/`, `.htaccess`, `api/index.php`).
2. **Git Commit & Push:** Alle Quellcode-Dateien UND die Root-Build-Dateien stagen und pushen:
   ```bash
   git add -A; git commit -m "..."; git push origin main
   ```
3. **Server-Update:** Auf dem Server im Verzeichnis `/sub/taskster` wird einfach `git pull origin main` ausgeführt!
4. **Datenbank-Migrationen:** Falls Schema-Änderungen anfallen, `node scripts/migrate-mysql.cjs` lokal ausführen, um die Remote-MySQL-Datenbank auf `sql21.hostcreators.sk:3326` zu aktualisieren.

# Anweisung: Kein SFTP-Deployment – Git-Only Workflow

## Verbindliche Regel
1. **Kein SFTP:** Es wird nicht mehr per SFTP auf den Server synchronisiert. Jeglicher File-Transfer per SFTP erzeugt auf dem Server ungetrackte Dateien und Versionskonflikte in Git.
2. **Git als einziger Übertragungsweg:** Alle Code- und Design-Änderungen werden lokal committed und nach `origin/main` gepusht.
3. **Mitteilungspflicht bei Server-Kommandos:** Sobald nach einem Git-Pull auf dem Server Befehle wie z.B. `npm run generate`, `npm run build`, `npm install` oder Migrationsscripte notwendig sind, wird der User mit der genauen Befehlszeile informiert.

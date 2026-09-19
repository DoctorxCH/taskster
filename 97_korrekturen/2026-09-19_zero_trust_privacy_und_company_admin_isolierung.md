# 2026-09-19: Zero-Trust Privacy & Company-Admin Isolierung

## Kontext & Anforderung
Company-Zugehörigkeit dient ausschliesslich dazu, dass Benutzer den Company-Plan (Pro, Quotas) erhalten und beim Erstellen von Projektordnern/Projekten optional die Sichtbarkeit für das Unternehmen gewähren können.
Company-Admins dürfen **nicht** automatisch Projekte oder Ordner von Mitgliedern sehen, es sei denn, das Projekt/der Ordner wurde explizit geteilt oder auf Sichtbarkeit `company` gesetzt. Standardmässig sind alle Projektordner und Projekte strikt privat (`private`).

## Änderungen

### 1. Datenbank-Schema & Migration
- **Tabelle `project_folders`**: Spalte `visibility VARCHAR(32) NOT NULL DEFAULT 'private'` hinzugefügt.
- **Tabelle `projects`**: Spalte `visibility VARCHAR(32) NOT NULL DEFAULT 'private'` hinzugefügt.
- Migration via [scripts/migrate-mysql.cjs](file:///c:/Users/marti/Taskster/scripts/migrate-mysql.cjs) live auf Hostcreators MariaDB ausgeführt.
- [server/db/schema.sql](file:///c:/Users/marti/Taskster/server/db/schema.sql) aktualisiert.

### 2. Backend & Permission Pipeline
- [server-php/index.php](file:///c:/Users/marti/Taskster/server-php/index.php) & [server/utils/permissions.ts](file:///c:/Users/marti/Taskster/server/utils/permissions.ts):
  - In `evaluateProjectAccess()`: Automatischer Company-Admin-Bypass (`company_role === 'admin'`) vollständig entfernt.
  - Zugriff wird ausschliesslich gewährt, wenn:
    1. Der Nutzer Eigentümer (`owner_id === user.id`) ist.
    2. Der Nutzer explizit in `project_members` als Mitglied eingetragen ist.
    3. Das Projekt explizit `visibility = 'company'` hat UND der Nutzer im selben Unternehmen ist.
    4. Ansonsten wird strikt `404 Not Found` zurückgegeben (Zero-Trust, keine Information-Leaks).
  - `GET /api/folders`: Gibt nur noch Ordner zurück, deren Eigentümer der Nutzer ist, oder bei denen er Projektmitglied ist, oder die `visibility = 'company'` im Unternehmen haben.
  - `GET /api/folders/:id`: Überprüft Ordnerzugriff (404 bei Fremdzugriff) und filtert gelistete Projekte so, dass Nicht-Eigentümer nur Projekte mit `visibility = 'company'` oder eigener Mitgliedschaft sehen.
  - `POST /api/folders`: Akzeptiert `visibility` (`'company'` nur wählbar bei `company_id`, sonst `'private'`).
  - `PUT /api/folders/:id`: Erlaubt dem Ordner-Eigentümer das Umschalten der Sichtbarkeit.
  - `POST /api/projects`: Speichert `visibility` (Standard `'private'`). Trägt den Ersteller als `owner` in `project_members` ein.
  - `PUT /api/projects/:id`: Erlaubt Bearbeitung der Sichtbarkeit durch autorisierte Nutzer.

### 3. Frontend (Nuxt / Vue 3)
- [pages/dashboard.vue](file:///c:/Users/marti/Taskster/pages/dashboard.vue):
  - Sichtbarkeits-Badge (`🔒 Privat` / `🏢 Unternehmen`) auf Ordnerkacheln.
  - Radio-Auswahl für Ordner-Sichtbarkeit in den Modals "Neuen Projektordner anlegen" und "Projektordner anpassen" (bei Unternehmenszugehörigkeit).
- [pages/folders/[id].vue](file:///c:/Users/marti/Taskster/pages/folders/[id].vue):
  - Sichtbarkeits-Badge im Header-Banner und auf den Projektkarten/Tabelle.
  - Radio-Auswahl für Projekt-Sichtbarkeit im Modal "Neues Projekt".
  - Radio-Auswahl für Ordner-Sichtbarkeit im Modal "Ordner anpassen".
- [pages/projects/[id].vue](file:///c:/Users/marti/Taskster/pages/projects/[id].vue):
  - Sichtbarkeits-Badge im Projekt-Header.
  - Radio-Auswahl für Projekt-Sichtbarkeit im Reiter "Projekt-Einstellungen".

### 4. Deployment
- Lokaler Build: `npm run generate` erfolgreich.
- Remote-Sync: `node scripts/deploy-sftp.cjs` erfolgreich nach `/sub/taskster`.

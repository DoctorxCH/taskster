# Korrektur-Dokumentation: Rollenlogik Ordner/Projekt, Standard-Projekt & Aufgaben-Mehrfachzuweisung

- **Datum:** 2026-09-19
- **Betreff:** Verbindliche Rechtestruktur (Ordner vs Projekt vs Aufgaben), Standard-Projekt-Zwang, Editor/Viewer-Rechte & Mehrfach-Zuweisung

## 1. Problemstellung & Anforderung
1. **Ordner-Einstellungen:**
   - Der Projekt- bzw. Ordner-Owner kann einladen und sieht alles.
   - Einstellungen des Projektordners müssen umfassen:
     - Name
     - Icon
     - Sichtbarkeit innerhalb Company (falls in einer Company)
     - Einladen von Teammitgliedern
     - Standard-Projekt (genau ein Projekt im Ordner muss als Standard definiert sein)
2. **Projekt-Berechtigungen:**
   - `owner`: Kann alles.
   - `editor`: Kann Aufgaben erstellen, ändern, verschieben und bearbeiten – **ausser löschen**!
   - `viewer`: Kann Aufgaben sehen **und abhaken**!
3. **Aufgaben-Zuweisung:**
   - Aus den Eingeladenen im Ordner/Projekt kann **einer oder mehrere** Benutzer zugewiesen werden.

## 2. Durchgeführte Änderungen

### A. Datenbank (MariaDB)
- **Tabelle `projects`:**
  - Neue Spalte `is_default TINYINT(1) NOT NULL DEFAULT 0` per Migrationsskript (`scripts/migrate-mysql.cjs`) hinzugefügt.
- **Tabelle `tasks`:**
  - Spalte `assigned_to` von `VARCHAR(64)` auf `TEXT NULL` erweitert, um JSON-Arrays für Mehrfachzuweisungen (`["usr_1", "usr_2", ...]`) verlustfrei zu speichern.

### B. Backend API (`server-php/index.php`, `public/api/index.php`, `api/index.php`)
- **Standard-Projekt Logik:**
  - `PUT /api/folders/:id`: Unterstützt das Setzen von `default_project_id`. Setzt per atomarem SQL `is_default = CASE WHEN id = ? THEN 1 ELSE 0 END WHERE folder_id = ?`.
  - `GET /api/folders/:id`: Liefert `is_default` mit und sortiert standardmäßig das Default-Projekt an die erste Position. Falls noch kein Projekt als Standard markiert ist, wird automatisch das älteste Projekt als Standard gesetzt.
- **Rollenrechte:**
  - `PUT /api/tasks/:id`: 
    - Wenn `userRole === 'viewer'`, darf ausschließlich das Feld `status` (abhaken / Status ändern) aktualisiert werden. Alle anderen Änderungen werden geblockt.
    - Wenn `userRole === 'editor'`, dürfen alle Aufgabenfelder geändert und verschoben werden (inkl. Listenwechsel und Kanban Drag&Drop).
  - `DELETE /api/tasks/:id`: 
    - Wenn `userRole === 'editor'` oder `userRole === 'viewer'`, wird der Request mit HTTP 403 Forbidden ("Nur der Projekt-Owner oder Administrator darf Aufgaben löschen") abgewiesen.
  - `POST /api/tasks` & `PUT /api/tasks/:id`:
    - Unterstützen `assigned_to` sowohl als Array als auch als Einzelfeld, persistiert als JSON-kodiertes Array.
  - `GET /api/projects/:id` & `GET /api/tasks/:id`:
    - Parsen `assigned_to` in ein Array `assigned_users` zur sauberen Übergabe an das Frontend.

### C. Frontend
- **Ordneransicht (`pages/folders/[id].vue`):**
  - Standard-Projekt-Auswahl im Ordner-Bearbeitungs-Modal (`showEditFolderModal`).
  - Standard-Projekt-Badge (`⭐ Standard`) auf den Projektkarten in Kachel- und Listenansicht.
  - Direkte Verlinkung zum Einladen von Teammitgliedern aus den Ordner-Einstellungen heraus.
- **Projektansicht (`pages/projects/[id].vue`):**
  - **Aufgaben-Abhaken für Viewer:** Aufgabenkarten besitzen Checkboxen, die auch für Betrachter/Viewer bedienbar sind, um Aufgaben direkt als erledigt/offen zu markieren.
  - **Lösch-Schutz:** Lösch-Buttons in Drawer und Detailmodal sind für `editor` und `viewer` ausgeblendet (`v-if="userRole === 'owner' || userRole === 'admin' || user?.is_superadmin"`). Zudem sichern die TypeScript-Handler `deleteTaskFromDrawer` und `deleteTask` clientseitig gegen unbefugte Aufrufe ab.
  - **Mehrfach-Zuweisung:** 
    - Neuer Zuweisungs-Selector im Task-Drawer mit Tag-Chips, Avatar-Kürzeln, Entfernen-Buttons und aufklappbarer Mehrfachauswahl-Checkliste der Ordner-/Projektmitglieder.
    - Aufgabenkarten zeigen Avatar-Stapel aller zugewiesenen Nutzer.

### D. Deployment (Git Only)
- Erstellung von `scripts/sync-dist.cjs` und npm-Befehl `npm run build:dist`.
- Generierter Produktions-Build synchronisiert direkt in das Git-Root-Verzeichnis.

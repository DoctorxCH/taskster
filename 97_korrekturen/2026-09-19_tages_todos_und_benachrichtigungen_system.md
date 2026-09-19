# Korrektur-Dokumentation: Tages-Todos ("Mein Tag" mit Rollover) & Benachrichtigungssystem (5 Event-Typen)

- **Datum:** 2026-09-19
- **Betreff:** Tages-Todos 1-Tages-Fokus mit automatischem Folgetag-Übertrag & Benachrichtigungssystem für Ablaufende Aufgaben, Neue Kommentare, Bearbeitungen, Einladungen und Budgetgrenzen.

## 1. Problemstellung & Zielsetzung
Entsprechend den Anforderungen aus `99_anweisungen/next_prompts.md`:
1. **Benachrichtigungssystem:** Das Widget "Benachrichtigungen" im Dashboard war zuvor ein statischer Platzhalter. Es soll 5 definierte Event-Typen abdecken:
   - Ablaufende Aufgaben in 3 Tagen (`due_soon`)
   - Neue Kommentare (`new_comment`)
   - Neue Bearbeitungen durch Teammitglieder (`task_updated`)
   - Einladungen (`invitation`)
   - Kosten / Budget erreicht im Projekt oder Aufgabe (`budget_exceeded`)
2. **Neuer Aufgabentyp „Tages-Todos / Mein Tag“ (Dashboard Aufgaben-Widget):**
   - 1-Tages-Fokus für persönliche Aufgaben, die heute anfallen.
   - Kann optional einem Projekt zugeordnet oder frei erstellt werden.
   - Gilt immer für 1 Tag.
   - **Automatischer Übertrag (Rollover):** Unerledigte Aufgaben wandern bei Tageswechsel automatisch auf den Folgetag mit Kennzeichnung `🔁 Übertrag von gestern`.
   - Schnelles Abhaken per Checkbox und Fortschrittsbalken direkt im Widget.

## 2. Technische Umsetzung

### A. Datenbank (MariaDB / MySQL 8.4)
- Tabelle `daily_todos` angelegt (`scripts/migrate-mysql.cjs` & `server/db/schema.sql`):
  ```sql
  CREATE TABLE IF NOT EXISTS daily_todos (
    id VARCHAR(64) PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    project_id VARCHAR(64) NULL,
    title VARCHAR(512) NOT NULL,
    target_date DATE NOT NULL,
    is_completed TINYINT(1) NOT NULL DEFAULT 0,
    completed_at DATETIME NULL,
    original_date DATE NULL,
    rollover_count INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_dt_user (user_id),
    INDEX idx_dt_target (target_date),
    INDEX idx_dt_project (project_id)
  );
  ```
- Tabelle `notifications` angelegt:
  ```sql
  CREATE TABLE IF NOT EXISTS notifications (
    id VARCHAR(64) PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    type VARCHAR(64) NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    reference_type VARCHAR(64) NULL,
    reference_id VARCHAR(64) NULL,
    project_id VARCHAR(64) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notif_user (user_id),
    INDEX idx_notif_type (type),
    INDEX idx_notif_read (is_read)
  );
  ```
- Tabelle `task_comments` verifiziert & migriert.

### B. Backend API (`server-php/index.php`, `api/index.php`, `public/api/index.php`)
- **Tages-Todos Endpunkte:**
  - `GET /api/daily-todos`: Führt automatischen SQL-Rollover aus (`UPDATE daily_todos SET target_date = CURRENT_DATE(), rollover_count = rollover_count + DATEDIFF(...) WHERE user_id = ? AND is_completed = 0 AND target_date < CURRENT_DATE()`), lädt heutige Todos und liefert verfügbare Projekte für das Dropdown.
  - `POST /api/daily-todos`: Speichert neues Todo mit `target_date = CURRENT_DATE()`, optionalem `project_id`.
  - `PUT /api/daily-todos/:id`: Toggelt `is_completed` (setzt `completed_at`) oder aktualisiert Titel.
  - `DELETE /api/daily-todos/:id`: Löscht das Todo.
- **Benachrichtigungen Endpunkte & Trigger:**
  - `createNotification(...)` Hilfsfunktion zur ereignisgesteuerten Notification-Erzeugung.
  - `GET /api/notifications`:
    - Ruft gespeicherte Notifications ab.
    - Führt Echtzeit-Query für Aufgaben mit Fälligkeit in <= 3 Tagen aus (`due_soon`).
    - Führt Echtzeit-Query für Projekte mit überschrittenem Stunden- oder Kostenbudget aus (`budget_exceeded`).
    - Sortiert absteigend und liefert `unreadCount`.
  - `POST /api/notifications/:id/read` & `POST /api/notifications/read-all`: Setzt Gelesen-Status.
  - Triggerevents integriert in `POST /tasks/:id/comments`, `PUT /tasks/:id`, `POST /projects/:id/members`, `POST /folders/:id/members`.

### C. Frontend Dashboard (`pages/dashboard.vue`)
- **Widget 1 (Aufgaben):**
  - Neuer Tab-Umschalter im Kopf: `☀️ Mein Tag` (mit Badge für offene Tages-Todos) vs. `📁 Projekte` (mit Badge für zugewiesene Aufgaben).
  - Ansicht "Mein Tag":
    - Quick-Add-Leiste mit Titel-Input, Projekt-Dropdown (`Ohne Projekt` oder auswählbare Projekte) und Hinzufügen per Enter / Button.
    - Tages-Fortschrittsbalken mit Prozentanzeige (`Heute erledigt: X von Y`).
    - Interaktive Checkbox mit sofortiger visueller Rückmeldung (Durchstreichen).
    - Warn-Badge `🔁 Übertrag von gestern` / `vor X Tagen` bei verschobenen Aufgaben.
    - Projekt-Link-Badge für verknüpfte Projekte.
    - Schnelles Löschen per Papierkorb-Icon.
- **Widget 3 (Benachrichtigungen):**
  - Dynamischer Feed mit Status-Badges und Icons:
    - ⏰ `due_soon`
    - 💬 `new_comment`
    - ✏️ `task_updated`
    - 📩 `invitation`
    - 💰 `budget_exceeded`
  - Funktionale Tabs: `Alle`, `Kommentare & Einladungen`, `Fälligkeiten & Budget`.
  - Direktlinks zu den betroffenen Projekten/Ordnern.
  - Buttons zum Einzel- und Sammel-Gelesen-Markieren.

## 3. Build & Deployment
- MySQL-Migration via `scripts/migrate-mysql.cjs` remote ausgeführt.
- Statischer Build mit `npm run generate` erfolgreich ausgeführt (12 Prerender-Routen, 0 Fehler).
- SFTP-Deployment aller Dateien nach `/sub/taskster` erfolgreich abgeschlossen.
- Git Commit & Push nach `origin/main`.

# 2026-09-19 – Zeiterfassung auf Projekte & Aufgaben mit Budget-Controlling

## 1. Übersicht & Zielsetzung
- Vollständiges Zeiterfassungs- & Controlling-System für Taskster.
- Zeiten können direkt auf Aufgaben (`tasks`) oder auf das übergeordnete Gesamtprojekt (`projects`) gebucht werden.
- Auf Projektordner (`project_folders`) kann **nicht** rapportiert werden; Ordner dienen als reine Aggregations- & Controlling-Übersicht für alle enthaltenen Projekte.
- Manuell erfasste oder nachträglich angepasste Zeiten werden im Audit-Protokoll verbindlich mit einem roten Stern (`*`) ausgewiesen.

## 2. Datenbank-Schema & Migrationen
- **Tabelle `time_entries`**:
  - `id VARCHAR(64) PRIMARY KEY`
  - `project_id VARCHAR(64) NOT NULL` (FK zu `projects.id`)
  - `task_id VARCHAR(64) NULL` (FK zu `tasks.id`, `NULL` = Gesamtprojekt)
  - `user_id VARCHAR(64) NOT NULL` (FK zu `users.id`)
  - `duration_minutes INT NOT NULL`
  - `entry_date DATE NOT NULL`
  - `description TEXT NULL`
  - `is_manual TINYINT(1) NOT NULL DEFAULT 1`
  - `hourly_rate DECIMAL(10,2) NULL`
  - `created_at`, `updated_at`
- **Neue Spalten**:
  - `users`: `hourly_rate DECIMAL(10,2)`, `currency VARCHAR(10) DEFAULT 'CHF'`
  - `projects`: `currency VARCHAR(10) DEFAULT 'CHF'`, `budget_hours DECIMAL(10,2)`, `budget_amount DECIMAL(12,2)`
  - `tasks`: `budget_hours DECIMAL(10,2)`, `budget_amount DECIMAL(12,2)`
- **Migration**:
  - SQLite: Lokal via `server/db/index.ts` migriert.
  - MySQL: Remote via `scripts/migrate-mysql.cjs` auf `sql21.hostcreators.sk:3326` ausgeführt.

## 3. Backend APIs (Nuxt Nitro & PHP Dual-Stack)
- `GET /api/time-entries`: Filter nach `project_id`, `task_id` oder `folder_id`, liefert Buchungen, Mitarbeiter-Zuordnung und Summendaten (`totalMinutes`, `totalHours`, `totalCost`).
- `POST /api/time-entries`: Neue Buchung mit automatischer Kennzeichnung `is_manual = 1` und Stundensatz.
- `PUT /api/time-entries/:id`: Bearbeitung bestehender Zeiteinträge (setzt `is_manual = 1`).
- `DELETE /api/time-entries/:id`: Löschen durch Ersteller oder berechtigte Personen (Owner/Admin).
- `GET/PUT /api/auth/profile`: Verwaltung von persönlichem Stundensatz und Standard-Währung.
- `GET/PUT /api/projects/:id`: Budget (Stunden & Betrag), Währung und aggregierte Ist-Zeiten.
- `GET/PUT /api/tasks/:id`: Aufgaben-Budget (Stunden & Betrag), aggregierte Ist-Zeiten und Detail-Zeiterfassungsliste.
- `GET /api/folders/:id`: Berechnet Projekt-Budgets und liefert `timeSummary` für den Ordner.
- **PHP Mirrors**: `server-php/index.php`, `api/index.php` und `public/api/index.php` vollständig synchronisiert.

## 4. Benutzeroberfläche (Frontend)
- **Benutzereinstellungen (`pages/settings.vue`)**:
  - Neue Karte "⏱️ Zeiterfassung & Abrechnung" zur Konfiguration von Standard-Stundensatz und Standard-Währung.
- **Projektordner (`pages/folders/[id].vue`)**:
  - Neue Controlling-Kopfzeile mit Gesamtaufwand, Projekt-Budgets und Fortschrittsbalken.
  - Projektkarten (Grid) & Tabelle zeigen Ist-Stunden vs. Budget-Stunden mit farbigen Auslastungsbalken.
  - Keine Zeiterfassung auf Ordnerebene möglich (Architektur-Vorgabe).
- **Projektdetailseite (`pages/projects/[id].vue`)**:
  - **Header & Navigation**: Badge mit Gesamtaufwand & Budget, neuer Reiter `⏱️ Zeiterfassung`.
  - **View 5: Zeiterfassung & Controlling**:
    - 4 KPI-Karten (Gesamtaufwand, Stunden-Budget mit %, Gesamtkosten, Kosten-Budget mit %).
    - Button `+ Zeit erfassen` für Gesamtprojekt oder Aufgaben-Auswahl.
    - Filterleiste nach Aufgabe und nach Mitarbeiter.
    - Audit-Protokoll-Tabelle mit Datum, Wer, Rapportiert auf, Dauer mit **`*`** für manuelle Zeiten, Stundensatz, Kosten, Tätigkeit und Bearbeiten/Löschen-Aktionen.
  - **Task Detail Drawer**:
    - Neue Vollbreiten-Sektion `⏱️ Zeiterfassung & Budget` direkt im Task-Modal.
    - Eingabe von Aufgaben-Budget (Stunden & Betrag) mit visuellem Balken.
    - Schnellerfassungsmaske für Arbeitszeiten auf die aktive Aufgabe.
    - Liste der auf diese Aufgabe gebuchten Zeiten mit `*` und Edit/Delete.
  - **Projekt-Einstellungen Tab**:
    - Felder für Projekt-Währung, Budget (Stunden) und Budget (Betrag).

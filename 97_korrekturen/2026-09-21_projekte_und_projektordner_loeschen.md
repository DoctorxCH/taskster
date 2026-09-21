# 2026-09-21 – Kaskadierendes Löschen von Projekten und Projektordnern

## Betroffene Dateien
- `api/index.php` (sowie `public/api/index.php` und `server-php/index.php`)
- `server/api/folders/[id].delete.ts` [NEU]
- `server/api/projects/[id].delete.ts` [NEU]
- `pages/folders/[id].vue`
- `pages/projects/[id].vue`
- `pages/dashboard.vue`
- `i18n/locales/de.json`, `en.json`, `sk.json`

## Änderungen & Architektur
1. **Backend (PHP & Nitro API):**
   - Neue Funktion `deleteProjectCascade($db, $projectId)` zur sauberen Bereinigung aller abhängigen Datensätze:
     - `task_comments`, `task_subtasks`, `daily_todos`
     - `tasks`, `list_access`, `lists`
     - `time_entries`, `project_journals`, `project_documents`, `project_group_access`, `project_members`
     - `calendar_events` (Entkopplung via `project_id = NULL`)
     - `projects`
   - Neuer Endpunkt `DELETE folders/:id`:
     - Zero-Trust Autorisierungsprüfung (nur Ordner-Eigentümer oder Superadmin).
     - Transaktionales Löschen aller enthaltenen Projekte über `deleteProjectCascade`.
     - Bereinigung von `folder_members`, `folder_field_definitions`, `folder_group_access`, `project_folders`.
   - Neuer Endpunkt `DELETE projects/:id`:
     - Zero-Trust Autorisierungsprüfung (nur Ordner-Eigentümer, Projekt-Admin/Owner oder Superadmin).
     - Transaktionale Kaskadierung über `deleteProjectCascade`.

2. **Frontend UI:**
   - **`pages/folders/[id].vue`:**
     - Ordner löschen: Menüpunkt im "Mehr"-Header-Dropdown sowie Aktions-Button im "Ordner anpassen"-Modal.
     - Projekte löschen: Schneller Lösch-Button auf den Kachelkarten (`projectViewMode === 'grid'`) und in der Tabellenansicht (`projectViewMode === 'list'`).
     - Modals für Ordner- und Projektlöschung mit Bestätigungsdialog und Fehlerrückmeldung.
   - **`pages/projects/[id].vue`:**
     - Menüpunkt "Projekt löschen" im Header "Mehr"-Dropdown.
     - Dedizierte "Gefahrenzone" (Danger Zone) mit Warnhinweis am Ende des Tabs "Projekt-Einstellungen".
     - Bestätigungsdialog mit Weiterleitung zum Eltern-Ordner (oder Dashboard).
   - **`pages/dashboard.vue`:**
     - Direkter Lösch-Button für Ordner-Karten auf dem Dashboard.
     - Ordner löschen Button im Modal "Projektordner anpassen".
     - Projekt löschen Button für Free-User-Projektkarten.
     - Bestätigungsdialoge für Ordner und Projekte.

3. **Mehrsprachigkeit (i18n):**
   - Alle Buttons, Bestätigungstexte und Warnhinweise in Deutsch (`de`), Englisch (`en`) und Slowakisch (`sk`) hinterlegt.

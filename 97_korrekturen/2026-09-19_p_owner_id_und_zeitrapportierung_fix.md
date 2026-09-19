# Korrektur-Dokumentation: Beseitigung SQL-Fehler (p.owner_id), Zeitrapporte-Projektliste & Stoppuhr-Modal Viewport-Zentrierung

- **Datum:** 2026-09-19
- **Betreff:** Fix Unknown column 'p.owner_id', Ergänzung GET /api/projects, Teleport für StopwatchModal

## 1. Problemstellung
1. **Server Error `1054 Unknown column 'p.owner_id'`:**
   - Beim Absenden eines Kommentars (`POST tasks/:id/comments`), beim Abruf von Benachrichtigungen, Tages-Todos und Zeiteinträgen trat ein SQL-Fehler auf, da die Tabelle `projects` keine Spalte `owner_id` besitzt (der Inhaber ist `pf.owner_id` aus `project_folders`).
2. **Zeitrapporte `/time` Projektliste und Einträge leer:**
   - Durch den SQL-Fehler in `GET time-entries` schlugen Abfragen fehl.
   - `pages/time.vue` versuchte Projekte aus `f.projects` über `GET /api/folders` zu laden, was `undefined` ergab. Es gab keinen dedizierten `GET /api/projects`-Endpunkt.
3. **Stoppuhr-Abschlussmodal am oberen Bildschirmrand abgeschnitten:**
   - Das Modal `StopwatchModal.vue` war innerhalb von `components/Navbar.vue` (`header.liquid_glass`) eingebettet. Durch das CSS `backdrop-filter: blur(...)` des Headers entstand ein lokaler Containing Block, wodurch `fixed inset-0` sich nicht auf das Browser-Viewport, sondern auf die 64px Header-Höhe bezog und das Modal nach oben aus dem Bild schob.

## 2. Durchgeführte Änderungen

### A. Backend API (`server-php/index.php`, `public/api/index.php`, `api/index.php`)
- **SQL-Queries korrigiert (Spalte `p.owner_id` entfernt):**
  - `POST tasks/:id/comments`: `JOIN project_folders pf ON pf.id = p.folder_id` hinzugefügt und `pf.owner_id as project_owner_id` selektiert.
  - `GET tasks`: `WHERE pf.owner_id = ? OR p.id IN (...)` korrigiert.
  - `GET daily-todos`: `WHERE pf.owner_id = ? OR p.id IN (...)` korrigiert.
  - Realtime Notifications: Bei ablaufenden Aufgaben und Budgetgrenzen `pf.owner_id` via Folder-Join abgefragt.
  - `GET time-entries`: `OR p.owner_id = ?` entfernt und Berechtigung über `p.folder_id IN (SELECT id FROM project_folders WHERE owner_id = ?)` sichergestellt.
- **Neuer Endpunkt `GET /api/projects`:**
  - Gibt alle für den eingeloggten Nutzer sichtbaren Projekte zurück (eigene Projekte, Mitgliedschaften, Ordner-Mitgliedschaften oder Firmenprojekte).

### B. Frontend
- **`components/StopwatchModal.vue`:**
  - Mit `<Teleport to="body">` und `z-[9999]` versehen, sodass das Modal global im DOM gerendert wird und unabhängig von Glassmorphism-/Filter-Effekten im Header exakt im Viewport zentriert ist.
- **`pages/time.vue`:**
  - `loadAvailableProjects` greift nun direkt auf den neuen Endpunkt `/api/projects` zu.
  - Das Projekt-Dropdown im Filter sowie im Modal "Arbeitszeit manuell erfassen" wird vollständig befüllt.
  - Nach Beseitigung des Backend-Fehlers werden alle Zeiteinträge und Summenstatistiken fehlerfrei gerendert.

## 3. Build & Deployment
- `npm run build:dist` erfolgreich ausgeführt (Client & Server Build, Generierung und Asset-Synchronisation).
- Git Commit & Push auf `origin/main`.

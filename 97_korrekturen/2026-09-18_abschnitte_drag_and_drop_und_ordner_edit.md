# 2026-09-18 Abschnitte Drag & Drop, Reorder & Projektordner Anpassung

- **Typ:** Feature / UI & API Erweiterung
- **Bereich:** Board View, Modal Management, Ordner-Owner Berechtigungen, Icon Picker, API
- **Status:** Abgeschlossen

## 1. Ausgangslage & Anforderung
- In der Projektansicht (`/projects/:id`) fehlte ein "Bearbeiten"-Button für Abschnitte (Listen), um diese per Drag & Drop zu sortieren, umzubenennen, zu löschen oder neue hinzuzufügen.
- Auch auf dem Board sollten Spalten direkt per Drag & Drop verschoben werden können.
- Projektordner müssen bezüglich Name und Icon anpassbar sein, jedoch strikt nur dort, wo der Nutzer Eigentümer (`owner_id === user.id`) oder Superadmin ist.
- Das Icon soll aus einer thematisch passenden Liste (Job/Gewerbe & Privat) ausgewählt werden können.

## 2. Datenbank & Schema
- `project_folders`: Spalte `icon TEXT / VARCHAR(64) DEFAULT '📁'` ergänzt.
- Automatische Migration bei DB-Initialisierung in SQLite (`server/db/index.ts`) und MySQL (`public/api/index.php`, `server-php/index.php`).

## 3. Backend-API (Nitro & PHP Parität)
- **Ordner:**
  - `PUT /api/folders/:id`: Erlaubt Änderung von `name` und `icon`. Prüft serverseitig Eigentümerschaft (`owner_id === user.id` oder `is_superadmin`). Bei Unberechtigten: 403 Forbidden / 404 Not Found.
  - `POST /api/folders`: Übernimmt optionales `icon` beim Anlegen.
- **Listen / Abschnitte:**
  - `POST /api/lists/reorder`: Batch-Aktualisierung von `sort_order` und Titeln für alle Abschnitte eines Projekts.
  - `DELETE /api/lists/:id`: Löscht einen Abschnitt und kaskadierend alle darin enthaltenen Aufgaben.
  - `PUT /api/lists/:id`: Einzel-Aktualisierung von `title`, `access_mode`, `sort_order`.

## 4. Frontend & UI
- **Projektansicht (`pages/projects/[id].vue`):**
  - Neuer Button `✏️ Abschnitte bearbeiten` in der Aktionsleiste (konform mit `taskster_button_light px-6 text-xs h-[42px] rounded-lg`).
  - Umfassendes Modal «Projekt-Abschnitte verwalten»:
    - Vertikales Drag & Drop zum Sortieren von Abschnitten.
    - Pfeiltasten (⬆️ / ⬇️) für inkrementelles Verschieben.
    - Inline-Umbenennung von Abschnitten.
    - Löschen mit Sicherheitsabfrage.
    - Schnelles Hinzufügen weiterer Abschnitte im Modal.
  - **Board-Level Drag & Drop:** Spalten-Header im Board sind direkt horizontal verschiebbar (`draggable`), was die Sortierung sofort in der Datenbank speichert.
  - Schnell-Bearbeiten-Icon (✏️) an jedem Spaltenkopf.
- **Dashboard (`pages/dashboard.vue`) & Ordneransicht (`pages/folders/[id].vue`):**
  - Anzeige des gewählten Ordner-Icons (`folder.icon || '📁'`).
  - Eigentümer-Prüfung: Bearbeiten-Button (`✏️ Bearbeiten` / `✏️ Ordner anpassen`) wird nur angezeigt, wenn `user.id === folder.owner_id || user.is_superadmin`.
  - Icon-Auswahlliste mit 20 Icons für Job (Tiefbau 🏗️, IT 💻, Elektro ⚡, Netzwerk 🌐, B2B 🏢, etc.) und Privat (Hausbau 🏠, Garten 🏡, Event 🎂, etc.).
  - Modal zum Ändern von Name und Icon.

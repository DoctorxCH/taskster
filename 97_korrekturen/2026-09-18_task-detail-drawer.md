# 2026-09-18 – Task Detail Drawer (MeisterTask-Style)

## Neue Tabellen
- `task_comments(id, task_id, author_id, content, created_at)` – Kommentare pro Task
- `task_subtasks(id, task_id, title, is_done, sort_order, created_at)` – Unteraufgaben

## Neue Spalten in `tasks`
- `assigned_to TEXT` – User-ID der zugewiesenen Person
- `priority TEXT DEFAULT 'normal'` – niedrig/normal/hoch/dringend
- `color TEXT` – Hex-Farbwert
- `tags TEXT DEFAULT '[]'` – JSON-Array
- `checklist TEXT DEFAULT '[]'` – JSON-Array `[{id,text,done}]`

## Neue API-Endpoints
- `GET /api/tasks/:id` – Detail mit Unteraufgaben + Kommentaren + Assignee
- `PUT /api/tasks/:id` – um neue Felder erweitert
- `POST /api/tasks/:id/comments` – Kommentar erstellen
- `POST /api/tasks/:id/subtasks` – Unteraufgabe anlegen
- `PUT /api/tasks/:id/subtasks/:subId` – Toggle done/undone oder umbenennen
- `DELETE /api/tasks/:id/subtasks/:subId` – Unteraufgabe löschen

## Frontend
- `openEditTaskModal()` → `openTaskDrawer()` – Task-Klick öffnet jetzt Slide-in-Drawer von rechts
- Drawer-Inhalte: Titel (inline editierbar), Status, Priorität, Zuweisung, Fälligkeit, 8 Farbchips, Tags-Chips, Checkliste mit Fortschrittsbalken, Unteraufgaben, Custom Fields, Kommentar-Feed
- AutoSave: alle Felder speichern beim @blur/@change automatisch via PUT /api/tasks/:id
- Task-Karte zeigt neu: Farb-Streifen unten, Priorität-Badge, Assignee-Avatar

## PHP Sync
- `server-php/index.php`, `api/index.php`, `public/api/index.php` synchron aktualisiert

## Commit
eb51039 auf main, 18. September 2026

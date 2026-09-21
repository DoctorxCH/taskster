# 2026-09-21 – Systemkategorien-Bearbeitung & Unified Task Drawer Scrolling

## Betroffene Dateien
- `api/index.php` (sowie `public/api/index.php` und `server-php/index.php`)
- `server/api/event-categories/[id].put.ts`
- `server/api/event-categories/[id].delete.ts`
- `pages/calendar/index.vue`
- `pages/projects/[id].vue`

## Änderungen
1. **Systemkategorien bearbeitbar & löschbar sowie entkoppeltes Löschen:**
   - Aufhebung der Einschränkung `is_system` in den PUT/DELETE API-Routen im Backend (PHP & Nitro).
   - Beim Löschen einer Kategorie werden bestehende Termine nicht mehr blockiert (`400`), sondern deren `category_id` wird auf `NULL` gesetzt (`UPDATE calendar_events SET category_id = NULL WHERE category_id = ?`).
   - Ersetzen von nativen Browser-Popups (`confirm()`, `alert()`) durch ein eigens designtes Taskster-Bestätigungsmodal in `pages/calendar/index.vue`.
2. **Unified Task Drawer Scroll:**
   - Entfernen der separaten Scrollbars in den beiden Spalten des Task Drawers (`overflow-y-auto` auf den untergeordneten Divs entfernt).
   - Der gesamte Task Detail Drawer verhält sich nun wie eine einzige flüssige Scroll-Einheit ohne doppeltes Scrollen.

# 2026-09-21 – Systemkategorien-Bearbeitung & Unified Task Drawer Scrolling

## Betroffene Dateien
- `api/index.php` (sowie `public/api/index.php` und `server-php/index.php`)
- `server/api/event-categories/[id].put.ts`
- `server/api/event-categories/[id].delete.ts`
- `pages/calendar/index.vue`
- `pages/projects/[id].vue`

## Änderungen
1. **Systemkategorien bearbeitbar & löschbar:**
   - Aufhebung der Einschränkung `is_system` in den PUT/DELETE API-Routen im Backend (PHP & Nitro).
   - In `pages/calendar/index.vue` werden die Hover-Buttons (✏️ und ✕) jetzt auf allen Kategorien (auch Systemkategorien) für den Inhaber/Admin angezeigt.
2. **Unified Task Drawer Scroll:**
   - Entfernen der separaten Scrollbars in den beiden Spalten des Task Drawers (`overflow-y-auto` auf den untergeordneten Divs entfernt).
   - Der gesamte Task Detail Drawer verhält sich nun wie eine einzige flüssige Scroll-Einheit ohne doppeltes Scrollen.

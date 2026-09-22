# Korrektur: Projektordner Icon Darstellung & Modal Fix

- **Datum:** 2026-09-23
- **Betreff:** Ordner-Icon Anzeige in Dashboard, Header & Breadcrumbs

## Änderungen
1. `pages/folders/[id].vue`:
   - Header & Breadcrumbs verwenden nun `folder.icon` (ausgewähltes Emoji), falls definiert, anstelle der statischen Lucide `Folder` Komponente.
2. `pages/dashboard.vue`:
   - Ordnerkarten auf dem Dashboard rendern nun das gewählte Emoji (`folder.icon`).
   - Das Modal "Projektordner anpassen" auf dem Dashboard enthält nun auch den Icon-Selector und übergibt `icon` beim `PUT /api/folders/:id` Aufruf.

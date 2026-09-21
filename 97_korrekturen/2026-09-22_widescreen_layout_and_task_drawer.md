# 2026-09-22 – Widescreen-Optimierung & Aufgaben-Drawer auf min. 90% Viewport-Fläche

## Datum & Kontext
- **Datum:** 2026-09-22
- **Ziel:** Volle Ausnutzung breiter Monitore (Full HD, 1440p, 4K, Ultrawide) in der gesamten Anwendung und Vergrößerung des Aufgaben-Detail-Drawers auf über 90% der verfügbaren Bildschirmfläche.

## Durchgeführte Änderungen

1. **Aufgaben-Detail-Drawer (`pages/projects/[id].vue`):**
   - Popup-Container von fixer Begrenzung (`max-w-4xl max-h-[92vh]`) auf **`w-[94vw] max-w-[94vw] h-[94vh] max-h-[94vh]`** erweitert.
   - Nutzt nun 94% der Breite und Höhe des Bildschirms für maximale Übersicht bei Beschreibungen, Checklisten, Unteraufgaben und Kommentaren.
   - Rechtes Sidebar-Panel von `w-80` auf `w-full lg:w-96 xl:w-[420px]` verbreitert.
   - Zusatzfelder-Grid auf `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4` ausgebaut.

2. **Kanban-Board Spalten (`pages/projects/[id].vue`):**
   - Spalten-Grid von `lg:grid-cols-3` auf `xl:grid-cols-4 2xl:grid-cols-5 min-[2200px]:grid-cols-6` erweitert. Abschnitte nutzen breite Monitore optimal nebeneinander aus.

3. **Gesamte App-Container auf Widescreen erweitert:**
   - Vorherige Beschränkungen (`max-w-6xl` = 1152px / `max-w-7xl` = 1280px) auf **`w-full max-w-[1920px] 2xl:max-w-[2400px]`** angehoben:
     - `pages/projects/[id].vue`
     - `pages/dashboard.vue`
     - `pages/time.vue`
     - `pages/folders/[id].vue`
     - `pages/admin/index.vue`
     - `pages/company/index.vue`
     - `pages/contacts/index.vue`
     - `pages/calendar/index.vue`
     - `pages/settings.vue`

4. **Produktions-Build & Synchronisation:**
   - `npm run build:dist` erfolgreich ausgeführt und `.output/public` synchronisiert.

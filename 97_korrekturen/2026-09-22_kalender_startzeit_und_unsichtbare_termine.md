# Korrektur: Kalender-Startzeit 07:00, Badges & Header-Karten (Kalender, Kontakte, Einstellungen)

- **Datum:** 2026-09-22
- **Komponenten:** `pages/calendar/index.vue`, `pages/contacts/index.vue`, `pages/settings.vue`
- **Beschreibung:**
  1. Standard-Scrollposition beim Öffnen der Wochenansicht auf 07:00 Uhr (`workStartMin`) gesetzt (`scrollToStartHour()`).
  2. Dynamische Sticky-Indikatoren (`+N ▲` oben und `+N ▼` unten) mit fester Höhe (`h-7`) in den Tagesspalten für Termine außerhalb des sichtbaren Scrollbereichs.
  3. Header-Bereich in Kalender, Kontakte und Einstellungen in eine weiße Card-Komponente (`bg-white border border-slate-200 rounded-lg p-5 shadow-xs`) gepackt, um Lesbarkeit über Hintergrundbildern wie in der Zeitrapportierung zu gewährleisten.

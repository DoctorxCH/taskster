# Korrektur: Kalender-Startzeit 07:00 & Badges für unsichtbare Termine

- **Datum:** 2026-09-22
- **Komponente:** `pages/calendar/index.vue`
- **Beschreibung:**
  1. Standard-Scrollposition beim Öffnen der Wochenansicht wurde auf 07:00 Uhr (`workStartMin`) gesetzt (`scrollToStartHour()`), sodass keine manuelle Scroll-Aktion beim Öffnen nötig ist.
  2. Dynamische Sticky-Indikatoren (`+N ▲` oben und `+N ▼` unten) in den Tagesspalten hinzugefügt für Termine, die sich außerhalb des sichtbaren Scrollbereichs befinden.
  3. Klick auf einen Indikator führt einen sanften Scroll-Vorgang (`scrollToEarlierEvents` / `scrollToLaterEvents`) zu den versteckten Terminen durch.

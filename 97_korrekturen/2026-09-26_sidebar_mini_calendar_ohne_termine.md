# Dokumentation: Mini-Kalender im Sidepanel ohne Terminliste

**Datum:** 2026-09-26  
**Bereich:** Sidepanel / Mini-Kalender (`components/MiniCalendar.vue`, `app.vue`, `pages/calendar/index.vue`)  
**Typ:** UI/UX Optimierung

## 1. Problemstellung & Anforderung
- Im linken Sidepanel (`app.vue`) wurde unter dem Kalender-Monatsraster auch die vollständige Terminliste ("Nächste Fälligkeiten" / Termine des ausgewählten Tages) angezeigt.
- Dadurch war das Sidepanel vertikal stark überladen und verbrauchte viel Platz.
- Der Nutzer wünschte im Sidepanel ausschliesslich den Kalender (Monatsraster, Navigation, Terminpunkte) ohne die darunterliegende Liste von Terminen/Fristen.

## 2. Technische Umsetzung

### A. Eigenschaft `showEvents` für `MiniCalendar.vue`
- Prop `showEvents: boolean` hinzugefügt (Standardwert: `true` zur Rückwärtskompatibilität, z. B. auf dem Dashboard).
- Die Abschnitte "Ausgewählter Tag: Terminliste", "Ausgewählter Tag: Keine Termine" und "Standard: Nächste Fristen & Termine" wurden in `<template v-if="showEvents">` gekapselt.
- Auf dem Dashboard (`pages/dashboard.vue`) bleibt die Terminliste wie gewünscht aktiv (`showEvents = true`).

### B. Einbindung im Sidepanel (`app.vue`)
- In `app.vue` wird `<MiniCalendar :show-events="false" />` übergeben.
- Das Sidepanel zeigt nun ausschliesslich das Monatsraster mit Wochentagen, Navigation und Terminpunkten.

### C. Direkte Kalender-Interaktion bei Klick auf einen Tag
- Wenn `showEvents === false` ist, führt ein Klick auf einen Tag direkt zur Kalenderseite (`/calendar?date=YYYY-MM-DD`).
- In `pages/calendar/index.vue` wurde die Unterstützung für `route.query.date` ergänzt: Sowohl beim initialen Laden (`onMounted`) als auch per `watch` wird der Cursor direkt auf das ausgewählte Datum positioniert und der Tag selektiert.

## 3. Betroffene Dateien
- `components/MiniCalendar.vue`
- `app.vue`
- `pages/calendar/index.vue`

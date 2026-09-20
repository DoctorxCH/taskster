# Korrektur: Verhinderung von unbeabsichtigten "Termin verschoben"-E-Mails beim Öffnen/Verschieben

**Datum:** 2026-09-20  
**Komponenten/Dateien:**
- `pages/calendar/index.vue`
- `server-php/index.php`
- `public/api/index.php`
- `api/index.php`

## PROBLEM
Beim Anklicken/Öffnen eines Termins im Kalender wurden teilweise in Sekundenschnelle mehrere E-Mails ("Termin verschoben: Montag Arbeiten") an alle Teilnehmer verschickt.

## URSACHEN
1. **Frontend (`moveEvent`):** Bei Klicks oder minimalen Drag/Drop-Ereignissen über dem Stundenraster wurde `moveEvent()` aufgerufen, ohne vorher zu prüfen, ob sich der Zeitpunkt (Datum/Uhrzeit) überhaupt verändert hat.
2. **Backend (PHP `PUT /api/events/:id`):** Der Backend-Vergleich `$timeChanged = ($startAt !== $existing['start_at'] || ...)` verglich rohe Strings. Formatunterschiede (z.B. `"2026-09-21T07:00"` mit Trenner `"T"` vs. Datenbank-Wert `"2026-09-21 07:00:00"` mit Leerzeichen und Sekunden) führten dazu, dass das Backend ein angebliches Verschieben erkannte und E-Mail-Einladungen/Benachrichtigungen in die Queue legte, selbst wenn sich die Uhrzeit gar nicht verändert hatte.

## LÖSUNG & BEHEBUNG
1. **Frontend-Schutz (`pages/calendar/index.vue`):**
   - In `moveEvent()` wird nun vor dem Senden des API-Calls geprüft, ob `prevStart === nextStart && prevEnd === nextEnd` (auf Minutengenauigkeit normalisiert). Wenn sich die Zeit nicht geändert hat, wird die Ausführung sofort abgebrochen.
2. **Backend-Schutz (`server-php/index.php`, `public/api/index.php`, `api/index.php`):**
   - Datumsstrings werden in PHP vor dem Vergleich normalisiert (`str_replace('T', ' ', ...)` und Auffüllen von `:00` Sekunden).
   - E-Mail-Benachrichtigungen an Teilnehmer werden nun **nur noch dann getriggert**, wenn tatsächlich eine echte Zeitverschiebung stattgefunden hat.

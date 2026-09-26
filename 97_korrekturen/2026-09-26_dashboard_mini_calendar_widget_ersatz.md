# 2026-09-26: Dashboard – Ersatz "System & Workspace Status" durch interaktiven Mini-Kalender

## Problemstellung
Die Box "System & Workspace Status" im Dashboard (`pages/dashboard.vue`) enthielt lediglich redundante Metriken (Ordner- und Projektzähler, die bereits in der linken Spalte ersichtlich sind) und ein dekoratives Badge ("ZeroTrust Pipeline: Aktiv"). Sie bot im operativen Projektalltag keinen praktischen Nutzen.

## Durchgeführte Änderungen
1. **Entfernung der Status-Box:**
   - Die statische Box `dashboard.system_workspace_status` in `pages/dashboard.vue` wurde entfernt.

2. **Integration des interaktiven Kalender-Widgets (`MiniCalendar.vue`):**
   - Platzierung im Dashboard in der rechten Spalte direkt unter den Benachrichtigungen.
   - Header mit Datumsnavigation, Monatsbezeichnung und Direktlink zum Kalender (`/calendar`).
   - Interaktives Monatsraster mit Terminmarkierungen (cyanfarbene Punkte für reguläre Termine, rote Punkte für überfällige Aufgaben).
   - Bei Klick auf einen Tag: Detaillierte Terminliste des ausgewählten Tages mit Statuspunkten, Projektbezug und 1-Klick-Öffnen der Aufgabe im Task-Drawer (`?task=ID`).
   - Bei unselektiertem Zustand: Automatische Anzeige der "Nächsten Fälligkeiten" dieses Monats (sortiert nach Überfälligkeit und Fälligkeitsdatum).
   - "Heute"-Button zur schnellen Rückkehr in den aktuellen Monat.

3. **Design-System-Anpassung (`components/MiniCalendar.vue`):**
   - Vollständige Umstellung auf Taskster-Primärfarbe `#00A3C4` (zuvor `#0891B2`).
   - Dynamische Lokalisierung der Wochentage (DE: Mo-So, EN: Mon-Sun, SK: Po-Ne).
   - Datumsformatierung angepasst an die aktive Sprache (`de-CH`, `en-US`, `sk-SK`).

4. **i18n-Synchronisation:**
   - Neue Schlüssel hinzugefügt in `de.json`, `en.json` und `sk.json`:
     - `dashboard.kalender_termine`
     - `dashboard.ganzer_kalender`
     - `mini_cal.naechste_faelligkeiten`
     - `mini_cal.keine_termine`
     - `mini_cal.keine_anstehenden_fristen`
     - `mini_cal.heute`
     - `mini_cal.ueberfaellig`
   - Parität aller 3 Sprachdateien verifiziert: exakt 2.890 Schlüssel je Datei (0 fehlende Keys).

5. **Build & Indexierung:**
   - Statischer Build via `npm run build:dist` erfolgreich durchgelaufen (Code 0).
   - Synchronisation nach Git-Root abgeschlossen.
   - `.agent_index.json` via `python generate_index.py --stats` aktualisiert.

# Benutzer-Einstellungen: breites, kategorisiertes Layout + Kalender & Benachrichtigungen

**Datum:** 2026-09-20
**Betroffene Schichten:** DB (SQLite + MySQL), Nitro-API, PHP-API, Nuxt-Frontend

---

## 1. Ausgangslage

Die Einstellungsseite war ein schmaler Stapel (`max-w-4xl`) aus sechs Karten, die
**noch im Design v1** gebaut waren: `liquid_glass rounded-3xl`, `font-black`,
Emoji-Überschriften (🔒 ⚡ 🏢), `backdrop-blur`. Das widersprach dem verbindlichen
Design v2 (keine Glas-Effekte, Inter, Lucide-Icons, min. 11px Schrift).

Inhaltlich gab es nur Profil, Stundensatz, Passwort, Tarif und Firmenverwaltung —
**keine Kalender- und keine Benachrichtigungs-Einstellungen**, obwohl der Kalender
gerade fertiggestellt wurde.

---

## 2. Neues Layout

- Breite auf `max-w-[1400px]` erhöht
- **Linke Kategorien-Navigation** (sticky) mit 7 Bereichen:
  Profil · Kalender · Benachrichtigungen · Abrechnung · Sicherheit · Tarifplan · Unternehmen
- Darunter eine Konto-Karte mit Avatar, Name, E-Mail und Plan-Badge
- Rechts der jeweilige Bereich als Karte im Design v2
  (`bg-white border border-slate-200 rounded-lg`, Kopfzeile `h-14`)
- **Sammel-Speichern**: ein Button oben rechts speichert alle Bereiche gemeinsam.
  Ein Hinweis „Ungespeicherte Änderungen" erscheint, sobald etwas geändert wurde.
- Alle `liquid_glass`-Klassen und Emojis entfernt

---

## 3. Datenmodell

Neue Spalte **`users.settings`** (JSON, Default `{}`):

- `server/db/schema.sql` — Spalte in `CREATE TABLE users`
- `server/db/index.ts` — idempotente Migration
- `public/api/index.php` — MySQL-Migration (`ALTER TABLE users ADD COLUMN settings JSON`)

### Serverseitige Normalisierung

`server/utils/userSettings.ts` (Nitro) und `normalizeUserSettings()` (PHP) sind die
**Single Source of Truth**. Jeder Wert wird validiert; unbekannte Schlüssel werden
verworfen, ungültige Werte durch Defaults ersetzt. Dadurch kann ein manipulierter
Request nie einen ungültigen Zustand im Client erzeugen.

Geprüft werden u. a.:

- Enums (`theme`, `density`, `default_view`, `time_format`, `digest`, …)
- `week_start` ∈ {0, 1}
- `slot_minutes` ∈ {15, 30, 60}
- `workday_start` / `workday_end` gegen `HH:MM` (00:00–23:59)
- `default_duration_minutes` zwischen 5 und 1440
- `default_reminder_minutes` ≥ 0 oder `null`

---

## 4. Kalender-Einstellungen

| Einstellung | Wirkung |
|---|---|
| Standard-Ansicht | Monat / Woche / Tag beim Öffnen des Kalenders |
| Erster Wochentag | Montag oder Sonntag — verschiebt das gesamte Raster |
| Kalenderwochen anzeigen | KW-Spalte links (Monat) bzw. KW-Kopf (Woche), ISO 8601 |
| Wochenenden anzeigen | blendet Sa/So aus dem Raster aus |
| Zeitformat | 24 h (`17:00`) oder 12 h (`5:00 PM`) |
| Arbeitsbeginn / Arbeitsende | hebt die Arbeitszeit im Stundenraster hervor |
| Raster-Auflösung | 15 / 30 / 60 Minuten |
| Aufgaben anzeigen | Aufgaben mit Fälligkeitsdatum ein-/ausblenden |
| Abgesagte Termine anzeigen | selbst abgelehnte Termine darstellen |
| Standard-Dauer | Dauer neuer Termine |
| Standard-Erinnerung | Vorlauf neuer Termine |
| Standard-Kategorie | vorausgewählte Kategorie |
| Standard-Sichtbarkeit | privat oder firmensichtbar |

### Umsetzung im Kalender

- `weekStart` verschiebt Rasterberechnung **und** Wochentagsbeschriftung
  (`weekdays` ist jetzt ein `computed` statt eines festen Arrays)
- `visibleWeekdayIndexes` steuert die Spaltenzahl; `weekGridCols` / `monthGridCols`
  erzeugen die CSS-Grid-Vorlage dynamisch
- `isWorkHour(h)` färbt Arbeitsstunden weiss, übrige `bg-slate-50/70`
- `isoWeek()` liefert die KW-Nummer; `monthWeekNumbers` die Nummern je Rasterzeile
- `timeOf()` respektiert das Zeitformat
- `eventsForDay()` filtert Aufgaben und abgesagte Termine gemäss Einstellungen
- Das Termin-Modal erhält die Standardwerte über die neue Prop `defaults`

---

## 5. Benachrichtigungs-Einstellungen

**Kanäle:** In-App · Browser · E-Mail · Ton · Zusammenfassung (aus/täglich/wöchentlich)

**Ereignisse (9):** Termineinladungen, Terminänderungen, Terminabsagen,
Terminerinnerungen, Aufgabenzuweisung, fällige Aufgaben, Kommentare, Erwähnungen,
Budgetwarnungen — mit „Alle an / Alle aus".

### Browser-Berechtigung

Beim Aktivieren fragt die Seite `Notification.requestPermission()` ab. Wird die
Erlaubnis verweigert, springt der Schalter zurück und es erscheint ein Hinweis.
Der aktuelle Status (`granted` / `denied` / `default`) wird direkt am Feld angezeigt.

---

## 6. Benachrichtigungen funktionieren wirklich

Die Einstellungen wären ohne Gegenstück reine Dekoration gewesen. Deshalb neu:

### `composables/useNotifications.ts`

- Lädt `/api/notifications` und hält Liste + Ungelesen-Zähler global (`useState`)
- **`announced`-Set** verhindert doppelte Meldungen derselben ID
- **`inFlight`-Sperre** verhindert parallele Refresh-Zyklen (App-Start + Navbar)
- Beim **ersten** Laden werden vorhandene Einträge nur registriert — ein
  Seitenaufruf löst also keine Hinweis-Flut aus
- `TYPE_TO_SETTING` bildet API-Typen auf Einstellungs-Schlüssel ab
  (`due_soon` → `task_due`, `budget_exceeded` → `budget_warning`, …)
- **Browser-Hinweis** über die Notification API; Klick öffnet den passenden Ort
  (Termin → Kalender, Aufgabe → Projekt mit `?task=`)
- **Ton** über die Web Audio API: zwei kurze Sinustöne, dezent, kein Alarm.
  `silent: true` beim Browser-Hinweis, damit die Ton-Einstellung die Kontrolle behält

### `app.vue`

Startet das Polling nach dem Login und lädt alle 60 Sekunden nach — nur wenn der
Tab sichtbar ist (`document.visibilityState`). Der Timer wird beim Logout und beim
Unmount aufgeräumt.

### `components/Navbar.vue`

Glocke mit Ungelesen-Badge und Dropdown: Liste der letzten 8 Einträge mit relativer
Zeitangabe („vor 2 Std."), „Alle gelesen", Klick navigiert zum Ziel, Fusszeile
verlinkt ins Dashboard.

---

## 7. Nachgezogene fehlende Nitro-Routen

Beim Testen fiel auf, dass dem Nitro-Server Routen fehlten, die das Frontend längst
nutzte (der Vue-Router verschluckte die 404 still):

- `server/api/daily-todos/index.get.ts` — inkl. Rollover-Logik (unvollendete Todos
  früherer Tage wandern auf heute, `rollover_count` wird hochgezählt)
- `server/api/daily-todos/index.post.ts` — mit Zero-Trust-Projektprüfung
- `server/api/daily-todos/[id].put.ts` · `[id].delete.ts`

**Auch das Dashboard (`pages/dashboard.vue`) rief `/api/daily-todos` bereits auf und
lief bisher ins Leere.**

---

## 8. Getestete Szenarien (Browser, Dev-Server + SQLite)

- ✅ Seite lädt: 7 Navigationsbereiche, 41 Formularfelder, **0** `liquid_glass`-Elemente
- ✅ Kalender-Einstellungen: 3 Abschnitte, 8 Selects, 4 Schalter, 2 Zeitfelder
- ✅ Speichern: Werte kommen korrekt in `users.settings` an
- ✅ Kalender übernimmt: Standard-Ansicht „Woche", KW 38 sichtbar,
  12-Stunden-Format (`12:00 AM`), nur 5 Spalten (Mo–Fr),
  10 Arbeitsstunden hervorgehoben / 70 ausgegraut bei 08:00–18:00
- ✅ Termin-Modal: Start 08:00 (Arbeitsbeginn), 120 Min Dauer,
  Kategorie „Baustelle", Erinnerung 15 Min — alles aus den Einstellungen
- ✅ Benachrichtigungen: 13 Optionen (4 Kanäle + 9 Ereignisse)
- ✅ Glocke: Badge „2", Einladung + Terminverschiebung mit relativer Zeit
- ✅ Browser-Hinweis: ausgelöst mit korrektem Titel und Text
- ✅ Ton: Web-Audio-Pfad nachweislich ausgeführt
- ✅ **Genau eine** Meldung pro Ereignis (Doppelmeldung behoben)
- ✅ Ereignis-Filter: bei deaktivierten Termineinladungen **0** Popups
  (die In-App-Liste zeigt sie weiterhin — das ist der Posteingang)

### Behobener Fehler

Der „Ungespeicherte Änderungen"-Hinweis erschien nie und der Speichern-Button blieb
dauerhaft deaktiviert. Ursache: `baseline` war eine **nicht-reaktive** Variable
(`let baseline = ''`). Da `dirty` als `computed` die Kurzschluss-Auswertung
`baseline !== '' && …` nutzte, wurde die Abhängigkeit nie registriert und `dirty`
fror auf `false` ein. Behoben durch `const baseline = ref('')`.

---

## 9. Deployment

1. `npm run build:dist`
2. `git add -A; git commit; git push origin main`
3. Auf dem Server: `git pull origin main`
4. **Wichtig:** neue Spalte `users.settings` → einmalig
   `node scripts/migrate-mysql.cjs` gegen MySQL ausführen

Kein `npm install` / `npm run build` auf dem Server (Shared Hosting ohne C-Compiler).
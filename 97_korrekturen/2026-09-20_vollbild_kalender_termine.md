# Vollbild-Kalender mit Terminverwaltung & Einladungen

**Datum:** 2026-09-20
**Phase:** 10
**Betroffene Schichten:** DB (SQLite + MySQL), Nitro-API, PHP-API, Nuxt-Frontend

---

## 1. Ausgangslage & Anforderung

Der Sidebar-Mini-Kalender (Phase 9) zeigte nur Tage mit Punkten. Gewünscht war ein
**vollwertiger Kalender im Outlook-Stil**:

- Vollbild-Seite im gleichen Design wie die übrigen Seiten
- Monats-, Wochen- und Tagesansicht
- Termine per **Maus ziehen** erstellen, **verschieben** (Drag & Drop)
- Termine mit **Ort, Betreff, Beschreibung, Kategorie, Farbe, Priorität, Projekt, Erinnerung**
- **Personen einladen**, inklusive Benachrichtigung
- **Kategorien mit Farben** selbst anlegen
- Eingehende Einladungen beantworten (Zusagen / Absagen / Vorbehalt)

---

## 2. Architektur-Entscheid zum E-Mail-Versand (wichtig!)

Der Nutzer fragte: *"braucht dies eine eigene email adresse? … oder wir verbinden die mail."*

**Entscheid: Keine eigene Mailadresse, kein IMAP-Postfach.**

Begründung und Umsetzung:

| Kanal | Zweck | Abhängigkeit |
|---|---|---|
| **In-App-Benachrichtigung** | Einladung/Absage/Antwort erscheint sofort in Taskster | keine (funktioniert immer) |
| **ICS-Download** | Termin direkt in Outlook / Google / Apple importieren | keine |
| **`email_outbox`-Tabelle** | E-Mails werden vorgemerkt (inkl. ICS-Anhang) | späterer Cron/SMTP-Worker |

Damit ist das Feature **sofort voll funktionsfähig**, ohne dass Taskster ein Postfach
betreiben muss. Sobald ein SMTP-Zugang oder eine Projektadresse vorhanden ist, muss nur
ein Worker die `email_outbox` leeren — die Daten (Empfänger, Betreff, Text, ICS) liegen
bereits vollständig vor. Ein optionales Feld für Bauleiter-/Projektadressen kann später
ohne Schema-Bruch ergänzt werden.

---

## 3. Datenbank (5 neue Tabellen)

In `server/db/schema.sql` und `server/db/index.ts` (idempotente Migration) sowie in
`public/api/index.php` (`ensureTables()`, MySQL-Pendant):

| Tabelle | Zweck |
|---|---|
| `event_categories` | Kategorien mit Name, Farbe, Icon; `is_system`, `company_id`, `owner_id` |
| `calendar_events` | Termine: `title`, `description`, `location`, `start_at`, `end_at`, `all_day`, `priority`, `status`, `visibility`, `color`, `recurrence`, `reminder_minutes`, `project_id`, `task_id` |
| `event_attendees` | Teilnehmer je Termin, `UNIQUE(event_id, email)` – deckt auch firmenfremde Mailadressen ab |
| `event_reminders` | Vorlauf-Erinnerungen |
| `email_outbox` | Vorgemerkte Mails mit `ics_content`, `status`, `attempts`, `error` |

**7 System-Kategorien** werden automatisch angelegt:
Besprechung `#0891B2`, Baustelle `#D97706`, Frist/Termin `#DC2626`,
Reise/Fahrt `#7C3AED`, Ferien/Abwesenheit `#059669`, Schulung `#2563EB`, Privat `#64748B`.

---

## 4. API-Endpunkte

### Nitro (Entwicklung)

| Datei | Route |
|---|---|
| `server/api/events/index.get.ts` | `GET /api/events?from=&to=&project_id=&category_id=` |
| `server/api/events/index.post.ts` | `POST /api/events` |
| `server/api/events/[id].put.ts` | `PUT /api/events/:id` |
| `server/api/events/[id].delete.ts` | `DELETE /api/events/:id` |
| `server/api/events/[id]/respond.post.ts` | `POST /api/events/:id/respond` |
| `server/api/events/[id]/ics.get.ts` | `GET /api/events/:id/ics` |
| `server/api/event-categories/index.get.ts` · `index.post.ts` | `GET`/`POST /api/event-categories` |

`GET /api/events` liefert zusätzlich **Aufgaben mit Fälligkeitsdatum** im Zeitraum als
read-only Einträge (`type: 'task'`), damit der Kalender Arbeit und Termine zusammen zeigt.

### PHP (Produktion)

`public/api/index.php` (Build-Quelle) — zusätzlich 8 Endpunkte:
`EVT-1` bis `EVT-8`. Nach jeder Änderung **gespiegelt** nach `api/index.php` und
`server-php/index.php`.

### Nachgezogene, bisher fehlende Nitro-Routen

Bei den Tests fiel auf, dass dem Nitro-Server Routen fehlten, die im Frontend längst
verwendet wurden (bzw. deren 404 vom Vue-Router verschluckt wurde):

- `server/api/notifications/index.get.ts` — inkl. Echtzeit-Hinweisen `due_soon` (Aufgaben
  in ≤ 3 Tagen fällig) und `budget_exceeded` (Budget erreicht). **Auch das Dashboard
  (`pages/dashboard.vue`) nutzte diese Route bereits — sie lief bisher ins Leere.**
- `server/api/notifications/[id]/read.post.ts` und `read-all.post.ts`
- `server/api/projects/index.get.ts`
- `server/api/companies/members.get.ts` — die Rechteprüfung wurde gelockert: jeder
  authentifizierte Nutzer darf die **Mitglieder der eigenen Firma** sehen (nötig für
  Terminteilnehmer und Zuweisungen). Firmenfremde Mitglieder werden nie ausgeliefert.

---

## 5. Frontend

### `pages/calendar/index.vue`

- Drei Ansichten: **Monat / Woche / Tag** (Monatsraster startet Montag)
- **Drag & Drop**: Termine per Maus greifen und auf einen anderen Tag ziehen
  (Monatsansicht) bzw. in eine andere Stunde (Wochenansicht). Optimistisches Update
  mit Rollback bei API-Fehler.
- Klick auf Tageszahl oder Zelle → Termin anlegen (`openCreate(dayKey, hour?)`)
- Doppelklick auf Monatszelle → Termin an diesem Tag
- Klick auf Aufgabe → springt zum Projekt (`/projects/:id?task=:id`)
- Kategorie-Filter-Chips + "Alle"
- Modals: Termin-Detail bei übervollen Tagen, Kategorie anlegen (8 Farben)
- **Ganztägige Termine** haben in der Wochenansicht eine eigene Zeile über dem
  Stundenraster (wie Outlook). Zuvor landeten Aufgaben ohne Uhrzeit durch
  UTC-Datums-Parsing fälschlich um 02:00 Uhr im Raster — behoben durch
  `timedEventsForDay()` / `weekAllDay`.

### `components/CalendarEventModal.vue`

Betreff, Ganztägig-Schalter, Beginn/Ende (Feldtyp wechselt automatisch zwischen
`datetime-local` und `date`), Ort, Kategorie, Priorität, Projekt, Sichtbarkeit
(privat / firmensichtbar), Beschreibung, Erinnerung sowie **Teilnehmer** per
E-Mail-Eingabe oder Klick auf Team-Vorschläge. Beim Bearbeiten zusätzlich:
Antwort-Status aller Eingeladenen und Antwort-Buttons für den Nutzer selbst.

**ICS-Download:** Ein reiner `<a href>` kann den Bearer-Token nicht mitsenden (401).
Deshalb wird die Datei per `fetch` mit Auth-Header als Blob geladen und über einen
temporären Object-URL gespeichert.

---

## 6. ICS-Erzeugung

`server/utils/calendar.ts` (Nitro) und die entsprechenden PHP-Helfer:

- RFC-5545-konforme Zeilenfaltung (75 Oktette)
- `METHOD:REQUEST` (Einladung), `CANCEL` (Absage), `PUBLISH` (Download)
- `ORGANIZER` + `ATTENDEE`-Zeilen, `SEQUENCE` bei Änderungen (1) und Absagen (2)
- Ganztägige Termine mit exklusivem `DTEND` (Start + 1 Tag)

---

## 7. Zero-Trust

- **Unberechtigt = 404**, nie 403. Beim Test gab `DELETE` als Teilnehmer noch
  `403 "Keine Berechtigung für diesen Termin"` zurück — das verrät die Existenz des
  Termins und wurde in Nitro **und** PHP auf `404 "Termin nicht gefunden"` geändert.
- `canAccessEvent()`: Eigentümer **oder** Teilnehmer **oder** firmensichtbarer Termin
  **oder** Superadmin. Eingeladene sehen den Termin, dürfen ihn aber nicht ändern
  (`editable: false`).
- `canEditEvent()`: nur Eigentümer oder Superadmin.

---

## 8. Getestete Szenarien (Browser, Dev-Server + SQLite)

Alle als Company Admin `marc@kurka.ch`:

- ✅ Seite lädt, 35 Monatszellen, 8 Kategorie-Chips
- ✅ Termin erstellt: Betreff, Ort, Kategorie "Baustelle", Priorität, Beschreibung,
  Teilnehmerin `sarah.editor@kurka.ch` → erscheint farbig am korrekten Tag
- ✅ Drag & Drop: Termin 20.09. → 22.09. verschoben (`timeChanged: true`), Rollback-Pfad vorhanden
- ✅ Wochenansicht: Termin korrekt bei `top: 504px` (9 Uhr), 24 Stundenlabels
- ✅ Ganztägige Aufgabe in eigener "ganztägig"-Zeile (nicht mehr 02:00)
- ✅ Tagesansicht: Kopfzeile, "Keine Termine"-Zustand, "Termin erstellen"-Button
- ✅ ICS-Download: `200`, `text/calendar`, `BEGIN:VEVENT`, `ORGANIZER`,
  2 × `ATTENDEE`, korrektes `SUMMARY`
- ✅ Kategorie "Sitzung Bauherr" angelegt → 8 Kategorien, Chip erscheint
- ✅ Einladung bei Sarah: Benachrichtigung `calendar_invite`
  ("Marc Steiner lädt dich ein")
- ✅ Sarah sieht den Termin, `editable: false`, `my_status: pending`
- ✅ Zero-Trust: Sarahs `DELETE` → `404`

---

## 9. Deployment

Wie immer Git-only:

1. `npm run build:dist` (erzeugt `calendar/index.html`, `_nuxt/` etc. im Git-Root)
2. `git add -A; git commit; git push origin main`
3. Auf dem Server: `git pull origin main`
4. Bei Schema-Änderungen: `node scripts/migrate-mysql.cjs` gegen MySQL

**Kein `npm install`/`npm run build` auf dem Server** (Shared Hosting ohne C-Compiler).
# 2026-09-22: Modul Projektjournal & KI-Ingestion-Pipeline

## Kontext
Implementierung des neuen Moduls "Projektjournal" auf Projektebene (`pages/projects/[id].vue`) und im Backend (`api/index.php`, `public/api/index.php`, `server-php/index.php`, Nitro-Routen).

## Schema & Migrationen
- `project_journals`:
  - Neue Spalten: `company_id` (INT), `user_id` (VARCHAR), `type` (VARCHAR: `entry` | `note`), `category` (VARCHAR), `visibility` (VARCHAR: `only_me` | `group` | `company` | `all`), `allowed_group_id` (VARCHAR NULL), `metadata` (LONGTEXT / JSON), `created_at`, `updated_at`.
- `project_journal_attachments`:
  - `id`, `journal_id`, `file_name`, `file_path`, `file_type`, `file_size`, `created_at`.
- `project_journal_attendees`:
  - `id`, `journal_id`, `contact_id`, `name`, `email`, `role`, `present` (TINYINT/BOOLEAN).
- Migrationen synchronisiert in:
  - `server/db/schema.sql`
  - `server/db/index.ts`
  - `scripts/migrate-mysql.cjs`
  - `api/index.php`, `public/api/index.php`, `server-php/index.php`

## Backend Endpunkte
- `GET /api/projects/:id/journal`:
  - Sichtbarkeitsmatrix:
    - `only_me`: nur wenn `journal.user_id == current_user.id` (oder Superadmin)
    - `group`: nur wenn `current_user` Mitglied in `allowed_group_id` ist (oder Superadmin)
    - `company`: alle mit gleicher `company_id`
    - `all`: alle mit Leserecht auf das Projekt
  - Aggregation von `attachments` und `attendees`.
- `POST /api/projects/:id/journal`:
  - Speichert Journaleintrag (`entry`) oder Notiz (`note`), speichert Teilnehmer und Attachments.
- `POST /api/projects/:id/journal/parse-email`:
  - E-Mail Ingestion Pipeline:
    1. Absender/Empfänger Regex-Extraktion.
    2. Prüft Tabelle `contacts` (Mandant `company_id`), legt fehlende Kontakte automatisch an.
    3. KI-Verarbeitung (OpenRouter / DeepSeek Engine) mit Projektkontext (bestehende Abschnitte/Aufgaben).
    4. Gibt striktes JSON zurück (`summary`, `action_items` mit `create_task`, `update_task`, `complete_task`).
    5. Persistiert E-Mail als Journalnotiz (`category = email`) mit KI-Metadaten.
- `PUT /api/projects/:id/journal/:journalId`:
  - Aktualisiert Journaleintrag oder Metadaten (z. B. nach Anwenden einer Aktionskarte).
- `DELETE /api/projects/:id/journal/:journalId`:
  - Löscht Eintrag mit Kaskade auf Attachments und Attendees.
- Nitro-Routen äquivalent in `server/api/projects/[id]/journal/`.

## Frontend UI (`pages/projects/[id].vue`)
- Header:
  - Segmented Control Wechsler neben dem Titel: `[Kanban]` vs. `[Projektjournal]`.
- Timeline Feed:
  - Unterscheidung Bausitzung (strukturiertes Protokoll mit Anwesenheitsbadges) vs. E-Mail/Notiz.
  - E-Mail Header & DeepSeek KI-Zusammenfassungs-Box.
  - Interaktive KI-Aktionskarten (`[Aufgabe anlegen]`, `[Aufgabe aktualisieren]`, `[Aufgabe abschliessen]`) mit Direktanwendung im Board und Metadaten-Persistierung.
  - Dateianhänge Galerie mit Download und Dateityp-Icons.
- Modals:
  - `showNewEntryModal`: Bausitzung / Protokoll / Bautagebuch mit Teilnehmerverwaltung (Kontaktauswahl, Rolle, Anwesenheits-Checkbox) und Uploads.
  - `showNewNoteModal`: Schnelle Notiz / E-Mail-Import mit `.eml` Dropzone (automatisches Auslesen von Betreff, Absender und Body) und DeepSeek KI-Schalter.
- Design-System:
  - Einhaltung der Vorgaben: `taskster_button`, `taskster_button_light`, `taskster_button_accent`, `px-6 text-xs h-[42px] rounded-lg`, Liquid Glass Styling.
- i18n:
  - Vollständige Übersetzungsschlüssel in `de.json`, `en.json` und `sk.json` unter `journal.*`.

## Nachtrag & Hotfix (2026-09-22 01:36)
1. **Remote MySQL Migration (Fehler: 1054 Unknown column 'company_id')**:
   - `node scripts/migrate-mysql.cjs` remote gegen Hostcreators MySQL ausgeführt.
   - Spalten `company_id`, `user_id`, `type`, `category`, `visibility`, `allowed_group_id`, `updated_at` in `project_journals` sowie die Tabellen `project_journal_attachments` und `project_journal_attendees` wurden in der MySQL-Produktionsdatenbank angelegt.
2. **Teilnehmer-Auswahl Dropdown**:
   - Problem: Das Dropdown lud zuvor ausschliesslich `projectContacts` (mit Filter `project_id = thisProject`), wodurch globale Unternehmens- und Benutzerkontakte fehlten und das Dropdown leer blieb.
   - Lösung: Einführung von `availableContacts` und `attendeeContactOptions` (deduplizierte Zusammenführung von Projekt- und Unternehmenskontakten). Das Dropdown zeigt nun alle für den Benutzer berechtigten Kontakte mit Firmenname/Funktion an.
3. **KI-Verfügbarkeit & On-Demand Analyse (Wo ist die KI?)**:
   - Problem A (Backend): In `callOpenRouter()` lieferte die Funktion ein assoziatives Array `['text' => ..., 'model' => ...]`. Bei `trim($rawAi)` kam es unter PHP 8 zu einem `TypeError`, der in den leeren Fallback-Block lief und Aktionskarten/Zusammenfassungen verhinderte.
     - Lösung: `$aiText = is_array($rawAi) ? ($rawAi['text'] ?? '') : (string)$rawAi;` und Umstellung von `catch (Exception)` auf `catch (Throwable)`.
   - Problem B (Frontend-Sichtbarkeit): Die KI-Zusammenfassungs-Box und Aktionskarten waren im Template nur für `entry.type === 'note' && entry.category === 'email'` sichtbar.
     - Lösung: Bedingung verallgemeinert (`entry.metadata?.ai_summary || entry.metadata?.action_items`). Nun können alle Journaleintragstypen (Bausitzung, Notiz, E-Mail) KI-Ergebnisse darstellen.
   - Neu (On-Demand Analyse & Modal-Integration):
     - Direkter Button `[⚡ KI-Analyse]` / `[KI aktualisieren]` im Header jeder Eintragskarte.
     - KI-Analyse-Schalter ("Mit KI analysieren") im Bausitzung/Protokoll-Modal (`showNewEntryModal`).
     - Backend-Unterstützung für `journal_id` / `entry_id` in `/api/projects/:id/journal/parse-email` zur In-Place-Aktualisierung existierender Einträge ohne Duplizierung.
4. **Aufgaben-Sync & Proaktive Aktionskarten (KI ändert nichts an Aufgaben)**:
   - Problem: Der System-Prompt war strikt auf E-Mails und exakt existierende Aufgaben beschränkt. Bei Texten wie „Kontrollschacht ersetzt, Auftrag kann abgeschlossen werden“ generierte die KI zwar eine Zusammenfassung, aber `action_items: []` (leer). Da keine Aktionskarten generiert wurden, konnte der Nutzer weder etwas anklicken noch wurden Aufgaben verändert.
   - Prompt-Upgrade: Der System-Prompt wurde für alle Bauleitungstypen proaktiviert. Die KI schlägt nun verlässlich konkrete Handlungsschritte vor (Auftragsabschluss, Terminaktualisierung oder neue Nachbereitungsaufgabe im passenden Abschnitt).
   - 1-Klick Bulk-Sync:
     - Neuer Button `[⚡ Alle Aktionen ins Kanban-Board übernehmen]` über den Aktionskarten und im Karten-Header.
     - Checkbox `[✓] Vorgeschlagene Aufgaben automatisch direkt im Kanban-Board anlegen` in beiden Erstellungs-Modalen (Auto-Sync).
     - Direkte visuelle Rückmeldung und automatischer Reload der Projektdaten (`loadProjectData()`).
5. **Fokus auf verknüpfte Aufgabe (Linked Task Prioritization)**:
   - Problem: Wenn ein Journaleintrag mit einer bestehenden Aufgabe verknüpft war (wie bei 'Test 2' mit `0100313383 - Hauptstr. Meggen`), wurde die `task_id` nicht an die KI-Pipeline übergeben. Zudem enthielt die Aufgabenliste im Prompt keine Beschreibungen (`description` wie z.B. 'ES Deckel wechseln'), wodurch die KI nicht wusste, worum es sich bei der verknüpften Aufgabe handelt, und fälschlicherweise eine neue Aufgabe vorschlug statt die bestehende abzuschliessen.
   - Lösung:
     - `task_id` wird nun explizit in `triggerAiAnalysis`, `saveNewEntry` und `saveNewNote` an das Backend übergeben und aus `project_journals` geladen.
     - Im Backend (`api/index.php`, `public/api/index.php`, `server-php/index.php`, `server/api/projects/[id]/journal/parse-email.post.ts`) wird die verknüpfte Aufgabe mit Titel, Beschreibung, Abschnitt, Frist und aktuellem Status geladen und als `DIREKT VERKNÜPFTE AUFGABE (HÖCHSTE PRIORITÄT / HAUPTFOKUS)` an den KI-Prompt übergeben.
     - Der System-Prompt weist die KI strikt an: Bezieht sich der Text auf die verknüpfte Aufgabe (z.B. Erledigung/Ersatz/Abschluss), wird zwingend ein `complete_task` für genau diese `task_id` generiert statt einer neuen Aufgabe.
     - `newNoteForm` erhielt ebenfalls das Dropdown 'Verknüpfte Aufgabe (optional)'.

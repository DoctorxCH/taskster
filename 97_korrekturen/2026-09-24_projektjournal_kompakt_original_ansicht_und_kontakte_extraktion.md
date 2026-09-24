# Projektjournal Upgrade: Kompakte Ansicht, 5-Zeilen-Ausklappung, Original-Ansicht Popup & Vollständige KI-Kontaktextraktion

**Datum:** 24.09.2026  
**Bereich:** Projektjournal (`pages/journal.vue`, `pages/projects/[id].vue`, `components/JournalEntryModal.vue`, `server-php/index.php`, `public/api/index.php`, `api/index.php`, `utils/emailParser.ts`)  
**Status:** Erfolgreich implementiert & verifiziert  

---

## 1. Übersicht & Zielsetzung

Das Projektjournal wurde auf Benutzerwunsch umfassend erweitert und optimiert:
1. **Ordner- & Projektwahl unterhalb des Drag & Drop Fensters:** Die Ordner- und Projekt-Zuweisung im `JournalEntryModal` wurde direkt unter die Upload-Zone verschoben, damit sie beim Importieren nicht übersehen wird.
2. **Vollständige KI-Extraktion aus E-Mails & Dokumenten:** Die KI analysiert nun ganzheitlich die E-Mail:
   - Absender-Signatur (Firma, Position/Rolle, Adresse, Telefonnummern, E-Mail).
   - Alle im Text genannten Kontakte und Personen.
   - Alle erwähnten Termine und Datumsangaben.
   - Alle Aufgaben, Handlungsaufforderungen und Fristen.
   - **Automatisches Speichern / Anreichern aller Kontakte** in der `contacts`-Tabelle und Verknüpfung als Teilnehmer.
3. **Kompakte Zusammenfassung ohne Leerzeilen-Wüsten:** Automatische Bereinigung überflüssiger Umbrüche (`cleanContent`).
4. **Sanfte 5-Zeilen-Ausklappung:** Einträge, die länger als 5 Zeilen sind, werden mit einem weichen Fade-Out-Verlauf auf 5 Zeilen begrenzt und lassen sich animiert per *„Mehr anzeigen“* / *„Weniger anzeigen“* aufklappen.
5. **Original-Ansicht In-App Popup:** Klick auf *„Original-Ansicht“* öffnet ein elegantes In-App-Modal mit dem unformatierten Originaltext, Absender-, Empfänger- und Betreff-Metadaten, Kontakt-Karten sowie 1-Klick-Kopierfunktion in die Zwischenablage (keine nativen Browser-Popups!).
6. **Eintrag bearbeiten & Bearbeitet-Zeitstempel:** Einträge können direkt über den Stift-Button bearbeitet werden. Wurde ein Eintrag nachträglich editiert, erscheint neben dem Datum der Vermerk `(bearbeitet DD.MM.YYYY um HH:mm)`.
7. **Einheitliche Subseiten-Breite:** `pages/journal.vue` nutzt nun die identische Maximalbreite wie alle anderen Subseiten (`w-full max-w-[1920px] 2xl:max-w-[2400px] mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8 space-y-6`).
8. **Sortierung:** Neue Sortieroptionen nach Datum (Neueste zuerst, Älteste zuerst), Auftrag/Projekt (A-Z) und Kategorie.
9. **Dokumentformate:** Unterstützung für E-Mails (`.msg`, `.eml`), Textdateien (`.txt`) und PDF-Dateien (`.pdf`).

---

## 2. Technische Details

### A. Modal-Reorganisation (`components/JournalEntryModal.vue`)
- Die E-Mail-/Dokument-Drag-and-Drop-Zone steht nun ganz oben im Dialog.
- Direkt darunter befindet sich die Box zur Ordner- und Projektauswahl mit dem intelligenten Live-Match-Indikator.
- Unterstützung für PDF-Dokumente (`accept=".eml,.msg,.txt,.pdf"`).
- Prop `:entry-to-edit` implementiert: Wird ein existierender Eintrag übergeben, werden alle Felder vorbefüllt und per `PUT /api/journals/:id` aktualisiert.

### B. PDF- & Dokumenten-Parsing (`utils/emailParser.ts`)
- PDF-Stream-Extraktor `extractTextFromPdf(bytes)` decodiert Textblöcke und `TJ`-Arrays direkt clientseitig, sodass auch PDF-Dateien als Journal-Einträge importiert und von der KI analysiert werden können.

### C. KI-Pipeline & automatische Kontaktspeicherung (`server-php/index.php`, `public/api/index.php`, `api/index.php`)
- **System-Prompt:** Extrahiert strukturierte JSON-Daten mit `summary`, `contacts` (inkl. Vorname, Nachname, Firma, Funktion, Telefon, Adresse, E-Mail), `events` und `action_items`.
- **Kontakt-Synchronisation:** Jeder von der KI gefundene Kontakt wird in der Datenbank geprüft:
  - Existiert der Kontakt bereits, werden fehlende Felder (Telefon, Adresse, Funktion) angereichert.
  - Existiert der Kontakt noch nicht, wird ein neuer Eintrag in `contacts` angelegt.
  - Der Kontakt wird automatisch in `project_journal_attendees` mit dem Journaleintrag verknüpft.
- Originaltext (`raw_text`, `original_text`), KI-Zusammenfassung und Metadaten werden im JSON-Feld `metadata` des Journaleintrags persistiert.

### D. UI & Interaktion (`pages/journal.vue` & `pages/projects/[id].vue`)
- **5-Zeilen Begrenzung & Animation:**
  - CSS `max-h-[6.75rem]` im eingeklappten Zustand mit sanftem Farbverlauf-Overlay (`bg-gradient-to-t`).
  - Animierte Erweiterung auf bis zu `3000px` per Klick auf *„Mehr anzeigen“*.
- **Original-Ansicht Popup:**
  - Eigene In-App-Komponente mit Header, Badges, E-Mail-Metadaten (Von, An, Betreff, Datum), Kontaktekarten und Volltext-Ansicht.
  - 1-Klick Clipboard-Kopierfunktion mit visueller Bestätigung (`copiedText`).
- **Sortierfilter:**
  - `journalSortBy` dropdown mit reaktiver Sortierung im `filteredJournals`-Computed.
- **Bearbeitungs-Indikator:**
  - `isEdited(entry)` prüft Zeitdifferenz zwischen `created_at` und `updated_at` (> 1 Minute) und rendert `• bearbeitet DD.MM.YYYY um HH:mm`.
- **Breiten-Angleichung:**
  - `max-w-7xl` ersetzt durch modernes Widescreen-Grid (`max-w-[1920px] 2xl:max-w-[2400px]`).

---

## 3. Deployment & Verifikation
- PHP Syntaxprüfung erfolgreich durchgeführt (`python scripts/check-php-syntax.py`).
- Statischer Produktionsbuild (`npm run build:dist`) synchronisiert `.output/public` ins Root-Verzeichnis.
- Code-Index aktualisiert (`python generate_index.py`).

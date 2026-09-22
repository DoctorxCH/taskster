# 2026-09-22 – Datums-Parsing beim CSV-/Excel-Projektimport (Excel Serial Date)

## Problemstellung
Beim CSV-/Excel-Import von Projekten und Aufgaben (sowohl beim Anlegen eines neuen Projekts aus Datei in `pages/folders/[id].vue` als auch beim nachträglichen Aufgaben-Import in `pages/projects/[id].vue`) wurden Datumsangaben aus Excel-Exporten als ganzzahlige serielle Tageswerte (z. B. `46272`) importiert.
Der Wert `46272` repräsentiert die Anzahl der Tage seit der Excel-Epoche (`1899-12-30`) und entspricht dem Datum `2026-09-07`.
Die bisherige Parsing-Logik prüfte lediglich auf das Textformat `DD.MM.YYYY`, wodurch numerische Excel-Serial-Werte entweder unkonvertiert gespeichert wurden oder zu Fehlinterpretationen führten.

## Durchgeführte Änderungen

1. **Zentrale Datums-Parsing-Utilities:**
   - **Frontend:** [`utils/dateParser.ts`](file:///c:/Users/taakumao/Taskster/utils/dateParser.ts) implementiert `parseImportDate()`.
     - Konvertiert Excel-Serial-Werte im typischen Bereich `> 25569` (entspricht 1970-01-01) und `< 60000` (ca. Jahr 2064) über die Formel `(days - 25569) * 86400 * 1000` nach `YYYY-MM-DD`.
     - Unterstützt Datums-Strings im Format `DD.MM.YYYY` / `D.M.YYYY`, `DD/MM/YYYY`, Standard-ISO `YYYY-MM-DD` sowie ISO-Strings mit Zeitstempel (`YYYY-MM-DDTHH:mm:ssZ`).
   - **Nitro Server:** [`server/utils/dateParser.ts`](file:///c:/Users/taakumao/Taskster/server/utils/dateParser.ts) stellt die identische Funktion serverseitig für alle Nitro-Routen bereit.

2. **Backend PHP (`api/index.php`):**
   - Funktion `parseImportDate($value): ?string` hinzugefügt (mit `DateTime('1899-12-30')->modify("+$days days")`).
   - In `POST projects` (Import von Aufgaben aus `import_tasks`) wird `due_date` via `parseImportDate()` normalisiert.
   - In `POST tasks` und `PUT tasks/:id` wird `due_date` ebenfalls serverseitig normalisiert.

3. **Server Nitro Routen:**
   - [`server/api/projects/index.post.ts`](file:///c:/Users/taakumao/Taskster/server/api/projects/index.post.ts): `due_date` von `import_tasks` wird über `parseImportDate()` validiert und konvertiert.
   - [`server/api/tasks/index.post.ts`](file:///c:/Users/taakumao/Taskster/server/api/tasks/index.post.ts) & [`server/api/tasks/[id].put.ts`](file:///c:/Users/taakumao/Taskster/server/api/tasks/%5Bid%5D.put.ts): Normalisierung von `due_date`.

4. **Frontend Import-Flows & UI-Feedback:**
   - **Projekt-Import in Ordner ([`pages/folders/[id].vue`](file:///c:/Users/taakumao/Taskster/pages/folders/%5Bid%5D.vue)):**
     - Aufgaben-Loop normalisiert `due_date` via `parseImportDate(row[dueColIdx])`.
     - Auch benutzerdefinierte Felder (`customData`) vom Typ `date` werden automatisch über `parseImportDate` konvertiert.
     - In der Spaltenzuweisungs-Vorschau wird bei Zuweisung zu `due_date` eine visuelle Konvertierungsanzeige (z. B. `46272 → 2026-09-07`) eingeblendet.
   - **Aufgaben-Import in Projekt ([`pages/projects/[id].vue`](file:///c:/Users/taakumao/Taskster/pages/projects/%5Bid%5D.vue)):**
     - In `executeImport` wird `taskPayload.due_date = parseImportDate(cellVal)` ausgeführt.
     - Auch benutzerdefinierte Datumsfelder werden formatiert.
     - Die Mapping-Vorschau zeigt transformierte Datums-Werte mit Pfeil an.

## Verifikation
- Unit-Test mit Testfall `46272`:
  - `parseImportDate(46272)` -> `2026-09-07`
  - `parseImportDate('46272')` -> `2026-09-07`
  - `parseImportDate('46272.25')` -> `2026-09-07`
  - `parseImportDate('07.09.2026')` -> `2026-09-07`
  - `parseImportDate('2026-09-07')` -> `2026-09-07`
- PHP-Syntaxprüfung über `python scripts/check-php-syntax.py api/index.php` erfolgreich bestanden.

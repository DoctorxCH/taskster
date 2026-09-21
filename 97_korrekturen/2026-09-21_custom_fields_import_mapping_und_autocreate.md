# 2026-09-21 – Custom Fields bei Aufgaben- und Projekt-Import vollständig auswählbar & auto-generierbar

## Problem
Beim Importieren von Aufgaben (`pages/projects/[id].vue`) und Projekten (`pages/folders/[id].vue`) konnten den Spalten der importierten Tabelle keine benutzerdefinierten Zusatzfelder zugewiesen werden:
1. Wenn im Ordner/Projekt noch keine Zusatzfelder angelegt waren, wurde die Auswahlliste für Zusatzfelder (`<optgroup>`) gar nicht gerendert.
2. Es gab keine Möglichkeit, aus Spalten der Importdatei (z.B. „Bauleiter", „Kosten", „Gewerk", „Seriennummer") direkt ein neues Zusatzfeld anzulegen.
3. Vordefinierte Standard-Felder aus Branchen-Vorlagen (FTTH, Bau, IT) standen im Dropdown nicht zur schnellen Auswahl bereit.
4. Beim Anlegen neuer Felder über die API fehlten serverseitig Default-Werte für `field_key` und die Spalte `entity_type`, wodurch die Felder in manchen Fällen nicht persistiert wurden.
5. In `pages/projects/[id].vue` wurden bisher nur `.csv`-Dateien unterstützt, keine `.xlsx`/`.xls`-Dateien.

## Lösung

### 1. Erweiterte Spaltenzuweisung (Mapping-Dropdown)
In beiden Import-Masken (`folders/[id].vue` und `projects/[id].vue`):
- **Bestehende Zusatzfelder:** Zeigt alle im Ordner/Projekt vorhandenen Felder an.
- **✨ Als neues Zusatzfeld anlegen:** Bietet für jede Spalte der Datei die Option `✨ Neues Feld: "[Spaltenname]"` (`custom:[key]`).
- **📋 Vorlagen-Zusatzfelder:** Häufige Branchenfelder (Verantw. Bauleiter, Gewerk / Bereich, Kosten / Budget CHF, Kunde / Auftraggeber, Adresse / Standort, Abnahmestatus, Komponente, Story Points, Seriennummer, Lieferant, Messprotokoll-Nr., Anlage-Typ) stehen direkt zur Zuweisung bereit.
- **Auto-Erkennung:** Erkennungslogik matcht Spalten automatisch auf Standard-Felder, bestehende Zusatzfelder, Vorlagen-Zusatzfelder oder schlägt sie als neues Zusatzfeld vor.

### 2. Auto-Generierung von Felddefinitionen beim Import
- Beim Ausführen des Imports werden alle Spalten, die auf `custom:*` gemappt wurden und noch nicht in `folder_field_definitions` existieren, automatisch als echte Felddefinitionen registriert:
  - In `pages/projects/[id].vue`: vor der Task-Erstellung über `POST /api/folders/:id/fields`.
  - In `pages/folders/[id].vue`: über `custom_field_definitions` in der Payload von `POST /api/projects`.
  - Serverseitig in `server/api/projects/index.post.ts` und `public/api/index.php`: Auto-Registrierung fehlender Schlüssel aus `import_tasks[].custom_data`.

### 3. Excel-Unterstützung für Aufgaben-Import
- In `pages/projects/[id].vue` wird nun wie in der Ordneransicht die Bibliothek `xlsx` genutzt, sodass sowohl Excel (`.xlsx`, `.xls`) als auch Textdateien (`.csv`, `.tsv`, `.txt`) per Drag & Drop importiert werden können.

### 4. API & Schema-Robustheit
- `server/api/folders/[id]/fields.post.ts`: Leitet `field_key` bei Bedarf automatisch aus dem `label` ab, speichert `entity_type` und ist idempotent bei bestehenden Schlüsseln.
- Neuer Endpunkt `server/api/folders/[id]/fields/[fieldId].delete.ts` für Nitro.
- `public/api/index.php` (PHP): Idempotentes Anlegen, Abfangen von Duplikaten, Migration für `entity_type` und `logic_rules`.
- `scripts/migrate-mysql.cjs` und `server/db/index.ts`: Idempotente Migrationen für `entity_type` und `logic_rules`.

## Deployment
- Build synchronisiert und getestet.
- Commits `f607497` nach `origin/main` gepusht.
- Auf dem Server: `git pull origin main`.

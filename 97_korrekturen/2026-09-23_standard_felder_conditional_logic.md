# 2026-09-23 Standard-Felder Conditional Logic

## Änderung
Standard-Aufgabenfelder (Status, Priorität, Zuweisung, Fälligkeitsdatum, Farbmarkierung, Tags) wurden in die bedingte Logik-Engine integriert.

## Betroffene Dateien
- `pages/projects/[id].vue`

## Was wurde gemacht

### Logic Engine (Script)
- `STANDARD_TASK_FIELD_KEYS`: Konstante mit den 6 Standard-Feld-Keys
- `checkFieldRule(logicRules, task)`: Zentrale Evaluierungs-Funktion, unterstützt Standard- und Custom-Felder
  - `status` → direkt `task.status`
  - `priority` → direkt `task.priority`
  - `assigned_to` → `assigned`/`unassigned` Keywords oder user_id match
  - `due_date` → `set`/`not_set`/`overdue`/`today` Keywords oder exaktes Datum
  - `color` → `set`/`not_set` Keywords
  - `tags` → `set`/`not_set` Keywords
  - Custom fields → `task.custom_data[depField]`
- `isStandardFieldVisible(fieldKey, task)`: Prüft ob ein Standard-Feld für einen Task sichtbar sein soll (sucht in `fields` nach Logik-Regel)
- `visibleDrawerFields`: Verwendet `checkFieldRule` statt direktem custom_data Vergleich
- `taskCustomFields`: Filtert Standard-Feld-Keys aus (werden separat gerendert)
- `isFieldVisibleForTask`: Verwendet `checkFieldRule`

### Drawer UI (Template)
- Alle 6 Standard-Felder mit `v-show="isStandardFieldVisible(key, drawerTask)"`
- Felder werden dynamisch ausgeblendet wenn Bedingung nicht erfüllt

### Standard-Felder Modal
- `newFieldForcedKey`: Erzwingt spezifischen field_key beim Speichern
- `isStandardFieldModal`: Zeigt vereinfachtes Modal ohne Scope/Typ/Label
- `openStandardFieldLogicModal(fieldKey, label)`: Öffnet Modal für Standard-Feld
- Upsert-Logik: PUT wenn field_key bereits in DB, sonst POST mit forced key

### Settings Card 2 (Template)
- Neuer Abschnitt "Sichtbarkeit Standard-Felder" mit 6 Kacheln
- Amber-Highlight wenn Bedingung aktiv
- "Logik" Button öffnet `openStandardFieldLogicModal`
- Custom Fields Tabelle filtert Standard-Keys heraus

### Field Modal (Template)
- Kontextbewusster Titel ("Logik-Regel: Status" vs. "Neues Feld")
- Scope/Label/Typ/Optionen ausgeblendet wenn `isStandardFieldModal`
- Conditional Logic immer sichtbar im Standard-Modal
- "Bedingung entfernen" Button wenn Regel aktiv
- Kontextspezifische Wert-Dropdowns je nach `logicDependsOnField`:
  - `status` → Dropdown mit todo/in_progress/review/done
  - `priority` → Dropdown mit dringend/hoch/normal/niedrig
  - `assigned_to` → Dropdown mit assigned/unassigned
  - `due_date` → Dropdown mit set/not_set/overdue/today
  - `color` → Dropdown mit set/not_set
  - `tags` → Dropdown mit set/not_set
  - Custom fields → freies Textfeld (Fallback)

## Persistenz
Logik-Regeln für Standard-Felder werden als `folder_field_definitions` Einträge gespeichert (field_key = 'status' etc.), ohne Einfluss auf Custom-Felder.

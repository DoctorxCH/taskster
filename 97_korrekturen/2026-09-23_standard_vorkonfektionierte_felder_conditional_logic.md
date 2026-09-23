# 2026-09-23: Standard-Aufgabenfelder & Custom Fields Conditional Logic (Vorlagen, CSV-Import, Ordner)

## Kontext & Problem
Standard-Aufgabenfelder (Status, Priorität, Zuweisung, Fälligkeitsdatum, Farbmarkierung, Tags) waren nicht an das bedingte Sichtbarkeits-System (`logic_rules`) gekoppelt. Benutzerdefinierte Felder und Standardfelder konnten keine wechselseitigen Abhängigkeiten abbilden.
Zudem war die Logik in Projektvorlagen und beim CSV/Excel-Import nicht vorab konfigurier- und editierbar, wodurch Felder erst nach der Projekterstellung mühsam manuell nachkonfiguriert werden mussten.

## Durchgeführte Änderungen

1. **Standardfelder & Logik-Integration (`logic_rules`):**
   - Standard-Feldschlüssel `status`, `priority`, `assigned_to`, `due_date`, `color`, `tags` können jetzt eigene `logic_rules` besitzen.
   - Erweiterung der Logik-Engine (`checkFieldRule`):
     - `status`: Abgleich mit Task-Status (`todo`, `in_progress`, `review`, `done`).
     - `priority`: Abgleich mit Priorität (`niedrig`, `normal`, `hoch`, `dringend`).
     - `assigned_to`: Unterstützt Einzel- und Mehrfachzuweisungen (`assigned`, `unassigned` oder spezifische User-ID).
     - `due_date`: Dynamische Auswertung (`set`, `not_set`, `today`, `overdue` oder konkretes Datum).
     - `color`: Abgleich auf Farbmarkierung (`set`, `not_set` oder Farbcode).
     - `tags`: Abgleich auf vorhandene Tags (`set`, `not_set` oder Tag-Name).
     - Custom Fields: Typsicherer String-Vergleich für Zahlen, Strings und Booleans.

2. **Ordner-Ebene (`pages/folders/[id].vue` - Tab "Benutzerdefinierte Felder"):**
   - Neue Verwaltungskarte für Standard-Aufgabenfelder mit Schnell-Konfiguration von Abhängigkeiten und visuellen Status-Badges (`⚡ Logik aktiv`).
   - Tabelle zeigt neue Spalte "Bedingte Logik" mit konfigurierter Abhängigkeit.
   - Feld-Bearbeitungsmodal (`showFieldModal`):
     - Dynamisches Dropdown für Trigger-Felder (Standardfelder + vorhandene Custom Fields).
     - Kontextbezogene Werte-Eingabe (Presets für Status, Priorität, Datum etc.).
     - Spezifischer Modus für Standardfelder inklusive "Bedingung entfernen".

3. **Projektvorlagen (`projectCreationMode === 'template'`):**
   - Vorlagenfelder werden reaktiv gerendert mit Typ- und Logik-Badges.
   - Direktes Anpassen der Sichtbarkeitslogik via Modal, Entfernen von Logik oder Feldern.
   - Möglichkeit, vor Projektstart zusätzliche Custom Fields inkl. Logik zur Vorlage hinzuzufügen.

4. **CSV/Excel-Import (`projectCreationMode === 'import'`):**
   - Option zur Auswahl einer Vorlage direkt im CSV-Import-Dialog, die Abschnitte (Workflow-Listen) und vorkonfigurierte Felder mit Logik automatisch übernimmt.
   - Für gemappte Zusatzfelder (`custom:xxx`): Direkter Button `⚡ Logik festlegen` im Spalten-Mapping für sofortige Bedingungs-Definition vor dem Import.
   - Abschnitte und Felddefinitionen inkl. `logic_rules` werden beim Erstellen nahtlos registriert.

5. **Projektdetail & Task-Erfassung (`pages/projects/[id].vue`):**
   - Task-Drawer und Task-Erfassungsmodal (`showTaskModal`) prüfen Standardfelder via `isStandardFieldVisible(...)` und Zusatzfelder via `isFieldVisibleForTask(...)`.

6. **Backend-Synchronisation:**
   - Nitro & PHP Router (`server-php/index.php`, `public/api/index.php`, `api/index.php`):
     - `PUT /api/folders/{id}/fields/{fieldId}` aktualisiert `options` und `logic_rules`.
     - `POST /api/projects` unterstützt `custom_fields` aus Vorlagen und Spalten-Mapping und aktualisiert `logic_rules` für bestehende und neue Folder-Felddefinitionen.

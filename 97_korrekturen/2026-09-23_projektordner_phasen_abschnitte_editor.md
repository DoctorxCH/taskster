# Korrektur: Projektordner Phasen- / Abschnitte-Editor im Anpassungs-Modal

**Datum:** 2026-09-23  
**Status:** Abgeschlossen  

### Kontext & Problemstellung
Im Modal "Projektordner anpassen" (`showEditFolderModal`) konnten Nutzer bisher lediglich eine Vorlage aus der Dropdown-Liste auswählen. Es war jedoch nicht möglich, die Phasen bzw. Workflow-Abschnitte des Ordners individuell einzusehen, anzupassen, umzubenennen, neue Abschnitte hinzuzufügen, die Reihenfolge zu verändern oder die Ziel-Phase für erledigte Aufgaben festzulegen.

### Durchgeführte Änderungen

1. **Frontend (`pages/folders/[id].vue`):**
   - **Interaktiver Phasen-Editor:** Im Modal "Projektordner anpassen" wurde unter dem Vorlagen-Auswahldialog ein vollständiger Workflow-Phasen-Manager integriert.
   - **Abschnitte anpassen & ändern:**
     - Direktes Editieren des Phasen-Namens im Textfeld.
     - Sortier-Buttons (▲ / ▼) zum schnellen Verschieben der Phasen.
     - Toggle-Button "✓ Ziel Erledigt" zur Bestimmung des Ziel-Abschnitts für erledigte Aufgaben.
     - Löschen-Button (✕) mit Absicherung (mindestens 1 Phase muss erhalten bleiben).
   - **Abschnitte hinzufügen:** Eingabefeld `+ Neuer Abschnitt...` mit `+ Hinzufügen`-Button und Enter-Key-Support.
   - **Vorlagen-Synchronisation & Reset:**
     - Bei Auswahl einer Vorlage werden die Phasen der Vorlage automatisch in die bearbeitbare Liste geladen.
     - Neuer Button `↺ Aus Vorlage neu laden` bzw. `↺ Standard-Phasen` zum schnellen Zurücksetzen.
   - **Speichern:** Beim Klick auf "Änderungen speichern" werden die bereinigten Abschnitte in `settings.default_sections` persistiert und `importWorkflowSections` aktualisiert.
   - **Typ-Sicherheit bei Import:** In `loadFolderData` wird `default_sections` sicher zu Strings gemappt, falls Objekte vorhanden sind.

2. **Backend API (`server-php/index.php`, `public/api/index.php`, `api/index.php`):**
   - `POST /api/projects`: Bei Übergabe von `custom_lists` wird nun sowohl ein reines String-Array als auch ein Objekt-Array mit `{ title, is_completed_target }` fehlerfrei geparst.
   - PHP-Syntax-Check auf allen 3 Mirrors erfolgreich validiert.

3. **Build & Sync:**
   - `npm run build:dist` ausgeführt, `.output/public` in das Root-Verzeichnis synchronisiert.
   - `generate_index.py --stats` ausgeführt.

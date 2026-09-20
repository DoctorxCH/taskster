# 2026-09-21 Projekt-Import: Benutzerdefinierte Felder in Spaltenzuweisung

- **Typ:** Bugfix / Feature-Ergänzung
- **Bereich:** UI (`pages/folders/[id].vue`)
- **Status:** Abgeschlossen

## 1. Problem
Beim Excel/CSV-Projekt-Import im Projektordner (`pages/folders/[id].vue`) fehlten in der
Spaltenzuweisung (Mapping) die benutzerdefinierten Felder des Ordners. Es standen nur die
Standard-Felder (Phase, Titel, Beschreibung, Fälligkeit, Priorität, Status, Tags) zur Auswahl.

Im Gegensatz dazu funktionierte das Mapping benutzerdefinierter Felder auf der Projektseite
(`pages/projects/[id].vue`) bereits korrekt.

## 2. Ursache
- Die Mapping-Auswahl im Projekt-Import-Modal enthielt keinen `optgroup` für Zusatzfelder.
- Die Auto-Erkennung (`processImportFile`) prüfte nur Standard-Spaltennamen, nicht die
  Labels/Feld-Keys der benutzerdefinierten Felder.
- Beim Aufbau von `import_tasks` wurden benutzerdefinierte Werte nicht in `custom_data` übernommen.

## 3. Änderungen (`pages/folders/[id].vue`)
- **Neue Computed `taskCustomFields`:** liefert alle Felddefinitionen mit `entity_type !== 'project'`
  (die Folder-API `/api/folders/:id` liefert bereits alle Felddefinitionen).
- **Mapping-Auswahl:** Standard-Felder in `optgroup "Standard-Felder"` gruppiert; neuer
  `optgroup "Benutzerdefinierte Felder"` mit `custom:<field_key>`-Optionen (Label + Key).
- **Auto-Erkennung:** Fallback-Zweig ergänzt, der Spaltenüberschriften gegen `label` bzw.
  `field_key` der benutzerdefinierten Felder matcht und `custom:<field_key>` zuweist.
- **Import:** Beim Erzeugen der `import_tasks` werden alle `custom:`-Mappings in ein
  `custom_data`-Objekt geschrieben und mitgesendet.

## 4. Backend
Keine Änderung nötig — `POST /api/projects` (Nitro `server/api/projects/index.post.ts` und
PHP `public/api/index.php`) verarbeitet `import_tasks[].custom_data` bereits und speichert es
in der Spalte `tasks.custom_data`.

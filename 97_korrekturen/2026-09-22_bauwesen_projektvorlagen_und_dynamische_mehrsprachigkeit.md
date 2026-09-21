# 2026-09-22 – Bauwesen-Projektvorlagen & Dynamische Mehrsprachigkeit (i18n)

## Datum & Kontext
- **Datum:** 2026-09-22
- **Ziel:** Ersetzung der bisherigen Demo-/Standard-Projektvorlagen durch die 4 Hauptsektoren des Bauwesens (Hochbau, Tiefbau & Strassenbau, Netzbau & Telekommunikation, Liegenschaftsunterhalt & Sanierung) inklusive vollständiger dynamischer Mehrsprachigkeit (`de`, `en`, `sk`) für Vorlagennamen, Abschnitte, Standard-Zusatzfelder und Dropdown-Optionen sowie Kompatibilität mit dem CSV/Excel-Import.

## Durchgeführte Änderungen

1. **Vorlagendefinition & Single Source of Truth (`composables/useProjectTemplates.ts`):**
   - Neu angelegt als zentrale Schnittstelle für alle 4 Bauwesen-Vorlagen (`CONSTRUCTION_TEMPLATES`) und die 23 Vorlagen-Zusatzfelder (`TEMPLATE_CUSTOM_FIELDS`).
   - Vorlagen: `template_building_construction`, `template_civil_engineering`, `template_network_infrastructure`, `template_property_maintenance`.
   - Schlüssel-Logik: `name_key`, `description_key`, `lists` mit `sections.*`, `fields` mit `label_key` und Option-Objekten mit `label_key`.

2. **Lokalisierung (`i18n/locales/*.json` & `_i18n/**/messages.json`):**
   - 74 neue Übersetzungsschlüssel in allen drei Sprachen (`de`, `en`, `sk`) hinterlegt:
     - 4 Vorlagennamen & Beschreibungen (`templates.*`)
     - 16 Standard-Abschnitte (`sections.*`)
     - 23 Zusatzfeld-Labels (`fields.*.label`)
     - 31 Dropdown-Optionsbezeichner (`fields.options.*`)

3. **Frontend Integration & Render-Logik:**
   - `pages/projects/[id].vue`:
     - Vorlagen-Zusatzfelder im CSV-Import Dropdown nutzen aktuelle UI-Sprache: `tf.label_key ? $t(tf.label_key) : tf.label`.
     - Bestehende Zusatzfelder prüfen `field.label_key ? t(field.label_key) : field.label`.
     - Task Drawer, Task Modal & Projekt-Einstellungen übersetzen Labels und Dropdown-Optionen dynamisch.
     - Spalten-Auto-Matching im CSV-Import matcht lokalisierte Bezeichnungen und Keys in DE, EN und SK.
     - Listen-/Abschnittstitel werden dynamisch übersetzt (`getSectionTitle`).
   - `pages/folders/[id].vue`:
     - Vorlagen-Karten zeigen dynamisch übersetzte Namen und Beschreibungen (`tmpl.name_key ? $t(tmpl.name_key) : tmpl.name`).
     - Projektphasen und Zusatzfeld-Vorschau übersetzt.
     - Excel/CSV-Import verwendet lokalisierte Vorlagenfelder.

4. **Backend Seeding & Datenbank-Schema:**
   - `server/db/schema.sql`: Spalte `label_key` in `folder_field_definitions` und `name_key`, `description_key` in `project_templates` ergänzt.
   - `server/db/index.ts`: SQLite Spalten-Migrationen und Bereinigung alter Demo-Vorlagen (`is_system = 1`).
   - `server/db/default-templates.ts`: Aktualisiert auf die 4 Bausektoren.
   - `server/api/projects/index.post.ts`: Überträgt `label_key` in `folder_field_definitions` bei Projekterstellung und Import.
   - `server-php/index.php`, `public/api/index.php`, `api/index.php`: MySQL-Migrationen, Seeding der 4 Bausektoren und Replikation von `label_key`.

5. **Code-Index:**
   - `python generate_index.py` ausgeführt und aktualisiert.

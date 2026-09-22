# 2026-09-23: Projektordner Custom Fields, PJ-Erfassung, Import-Phasen & Ordner-Kontakte

## Betreff
Erweiterung der Projektordner- und Projekt-Detailansichten um strukturierte Zusatzfelder, direkte Projektjournal-Erfassung auf Ordnerebene, bearbeitbare Felddefinitionen, Phasen-Konfiguration beim CSV/Excel-Import sowie folder-scoped Kontakte.

## Änderungen

### 1. Datenmodell & Migrationen (`server/db/schema.sql`, `scripts/migrate-mysql.cjs`, `api/index.php`)
- `project_folders.settings` (JSON): Speicherung von Ordnereinstellungen wie `default_sections`.
- `contacts.folder_id` (VARCHAR(36), NULL): Zuordnung von Kontakten zu Projektordnern.
- `project_journals.folder_id` (VARCHAR(36), NULL): Zuordnung von Journal-Einträgen direkt zum Ordner.
- `project_journals.project_id`: Nullable gemacht für ordnerweite Journaleinträge (`auto`-Projektzuweisung möglich).
- `projects.template_id`: Speicherung der zugrundeliegenden Vorlage.

### 2. Backend API (`api/index.php`, `public/api/index.php`, `server-php/index.php`)
- `GET /api/folders/:id`: Lädt Ordner-Details inklusive `settings` (inkl. `default_sections`), Custom Fields und Zeitübersicht.
- `PUT /api/folders/:id`: Speichert `settings` mit validierten Abschnitten/Phasen (`default_sections`).
- `PUT /api/folders/:id/fields/:fieldId`: Entsperrt `field_type` und `entity_type` (z. B. Wechsel von `text` auf `textarea`).
- `POST /api/projects`: Unterstützt Übergabe von `sections` beim Massenimport bzw. Erstellung, erbt `default_sections` des Ordners bei Blanko-Projekten.
- `GET /api/contacts?folder_id=...`: Filtert Kontakte auf den angegebenen Projektordner.
- `POST /api/contacts` & `PUT /api/contacts/:id`: Speichert `folder_id` für folder-scoped Kontakte.
- `GET /api/journals?folder_id=...`: Gibt ordnerbezogene Journaleinträge zurück.
- `POST /api/journals`: Akzeptiert `folder_id` und `project_id: 'auto'` mit automatischer Heuristik basierend auf Erwähnung des Projektnamens im Text.

### 3. Frontend Projektordner (`pages/folders/[id].vue`)
- **Breadcrumbs:** In die obere weiße Card integriert.
- **PJ direkt erfassen:** `+ PJ erfassen`-Button im Header mit Schnellerfassungs-Modal (`showQuickJournalModal`) inklusive Projektzuweisung (`Automatisch erkennen` oder explizite Projektauswahl).
- **Ordner-Tabs:**
  - `Projekte`: Projektliste / Raster mit Filterleiste (Textsuche, Status-Filter 'Alle' | 'Aktiv' | 'Erledigt').
  - `Projektjournal`: Chronologische Liste aller Journaleinträge des Ordners mit Projekt-Badges und KI-Erfassungs-Option.
  - `Benutzerdefinierte Felder`: Vollständige Verwaltung von Feldern für Aufgaben (`task`) und Projekte (`project`) im Ordner. Editier-Modal (`showFieldModal`) entsperrt `field_type` (ermöglicht Textarea) und `entity_type`.
  - `Kontakte`: 1:1 Design analog `pages/contacts/index.vue` mit Filterung auf `folder_id`, vCard-Export und Neu-/Bearbeiten-Modal.
- **Strukturierte Zusatzfelder-Darstellung:**
  - Kompakte Felder (`text`, `select`, `date`, `number`) als übersichtliche Pill-Badges.
  - Mehrzeilige Notizen (`textarea`, `long_text`) als eigenständige Info-Karten mit `StickyNote`-Icon und `whitespace-pre-wrap`.
- **Import-Phasen-Konfigurator:**
  - Im Projekt-Import-Modal können Workflow-Abschnitte (Default: `['Offen', 'In Arbeit', 'Abgeschlossen']`) angepasst werden.
  - Neu erstellte und importierte Projekte erhalten automatisch diese Abschnitte.

### 4. Frontend Projekt-Detail (`pages/projects/[id].vue`)
- **Breadcrumbs:** In den Haupt-Container verschoben.
- **Stoppuhr-Design:** Großes schwarzes Banner auf Task-Karten entfernt; durch dezentes pulsierendes Icon (`Clock text-rose-500 animate-pulse`) ersetzt.
- **Aufgaben-Filterleiste:** Textsuche nach Titel/Beschreibung/Tags sowie Filter nach Priorität und Zuweisung.
- **Kaskaden-Fix (Board/Tabelle):** Die neue Filterleiste wurde zusammen mit Board und Tabelle in ein sauberes `<template v-else>` überführt, sodass die Abschnitte (Kanban & Tabellenansicht) bei vorhandenen Listen korrekt gerendert werden (zuvor unterbrach ein `v-if="lists.length > 0"` die `v-else-if`-Kette).
- **Zusatzfelder:** Aufteilung in kompakte Badges (`compactProjectFields`) und mehrzeilige Notiz-Karten (`multiLineProjectFields`).
- **Feld-Modal:** `editingFieldId` entsperrt zur nachträglichen Änderung des Feldtyps.

### 5. Qualitätssicherung & Sync
- Tag-Verschachtelung in `pages/folders/[id].vue` mit `html.parser` verifiziert und validiert.
- `npm run build:dist` erfolgreich ausgeführt, statische Dateien nach Root synchronisiert.
- PHP-Syntax aller 3 Mirror-Dateien mit `python scripts/check-php-syntax.py` bestätigt.
- `python generate_index.py --stats` ausgeführt.

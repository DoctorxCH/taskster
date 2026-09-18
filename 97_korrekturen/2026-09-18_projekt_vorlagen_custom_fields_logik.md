# 2026-09-18 Projekt-Vorlagen & Benutzerdefinierte Felder mit Bedingter Logik

- **Typ:** Feature / Architektur-Erweiterung
- **Bereich:** DB, Backend (Nitro + PHP), Admin-UI, Folder-UI
- **Status:** Abgeschlossen

## 1. Ausgangslage & Anforderung
- Benutzer benötigen Vorlagen zur schnellen Projekterstellung sowohl für gewerbliche Jobs (z.B. LWL-Tiefbau, IT-Software, Elektrohandwerk) als auch für den privaten Bereich (z.B. Hausbau/Sanierung, Eventplanung).
- Vorlagen müssen vorkonfigurierte Projektphasen/Listen und benutzerdefinierte Felder enthalten.
- Felder müssen bedingte Logik unterstützen (`logic_rules`, z.B. Feld nur anzeigen, wenn ein übergeordnetes Feld einen bestimmten Wert besitzt).
- Administratoren müssen Vorlagen erstellen, bearbeiten und löschen können.
- Benutzer können beim Erstellen eines Projekts diese Vorlagen durchsuchen, nach Kategorien filtern und auswählen.

## 2. Datenbank & Schema
- Tabelle `project_templates`:
  - `id` (VARCHAR(64), PK)
  - `name` (VARCHAR(255))
  - `category` (ENUM/VARCHAR: 'job', 'private')
  - `subcategory` (VARCHAR(255))
  - `description` (TEXT)
  - `icon` (VARCHAR(64))
  - `is_system` (BOOLEAN DEFAULT 0)
  - `company_id` (VARCHAR(64) NULL)
  - `lists` (TEXT/JSON, Array von Listennamen)
  - `fields` (TEXT/JSON, Array von Felddefinitionen inklusive `logic_rules`)
  - `created_at`, `updated_at`
- Automatische Tabellengenerierung und Seeding von 5 Standardvorlagen (LWL/Tiefbau, IT/Software, Elektro, Hausbau privat, Event privat) in `public/api/index.php`, `server-php/index.php` und `server/db/schema.sql`.

## 3. Backend-API (Parität Nitro & PHP)
- `GET /api/templates`: Listet Vorlagen mit optionalen Filtern (`category`, `q`).
- `GET /api/templates/:id`: Detailabfrage einer Vorlage.
- `POST /api/templates`: Admin-Erstellung neuer Vorlagen.
- `PUT /api/templates/:id`: Admin-Aktualisierung von Vorlagen.
- `DELETE /api/templates/:id`: Admin-Löschung von Vorlagen.
- `POST /api/projects`: Akzeptiert optional `template_id`. Kopiert die Phasen/Listen in `lists` und repliziert die benutzerdefinierten Felder in `folder_field_definitions` für den übergeordneten Ordner (ohne Schlüsselkollisionen).

## 4. UI / Frontend
- **Design-System:** Einhaltung der verbindlichen Taskster-Buttonklassen:
  - `taskster_button`: Primary (Blau)
  - `taskster_button_accent`: Destructive/Delete (Rot)
  - `taskster_button_light`: Ghost/Cancel (Weiß + 3px blauer Rand)
  - Sizing: `px-6 text-xs h-[42px] rounded-lg`
- **Admin-Bereich (`pages/admin/index.vue`):**
  - Neuer Tab "Projekt-Vorlagen (Job & Privat)".
  - Tabellarische / Kachel-Übersicht mit Kategoriefilter und Volltextsuche.
  - Modal zum Erstellen/Bearbeiten von Vorlagen mit dynamischer Listenverwaltung und visuellem Regel-Editor für Feld-Abhängigkeiten (`depends_on_field`, `depends_on_value`).
- **Projekt-Erstellungsmodal (`pages/folders/[id].vue`):**
  - Switcher zwischen "Aus Vorlage erstellen (Empfohlen)" und "Leeres Projekt (Blanko)".
  - Kategorie-Pills ("Alle", "💼 Job & Gewerbe", "🏠 Privat") und Suchleiste.
  - Detaillierte Kacheln mit Badges für Listen, Custom Fields und Logik-Indikator.
  - Interaktive Vorschau der enthaltenen Phasen und bedingten Felder.
  - Nach Projekterstellung direkte Navigation in das neue Projekt.

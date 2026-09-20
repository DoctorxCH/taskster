# 2026-09-21: Zeitzone, DSGVO (Export & Löschung), Teamverwaltung & Benutzergruppen

**Datum:** 2026-09-21  
**Betreff:** Zeitzone-Einstellungen, DSGVO Art. 17/20, Team-Zugriffsmatrix und gruppenbasierte Berechtigungen  
**Status:** Abgeschlossen  

---

## 1. Übersicht & Anforderungen

1. **Zeitzone-Einstellungen (Timezone Settings):**
   - Einstellbar in Benutzerprofil (`settings.timezone`).
   - Standard: `'Europe/Zurich'`.
   - Validierung über IANA-Timezones (`Intl.DateTimeFormat` / `DateTimeZone`).
   - Live-Vorschau der aktuellen Uhrzeit in der gewählten Zeitzone im Frontend.
   - Normalisierung in Nitro (`server/utils/userSettings.ts`) und PHP (`api/index.php`, `public/api/index.php`, `server-php/index.php`).

2. **DSGVO / GDPR (Datenschutz & Selbstbestimmung):**
   - **Art. 20 DSGVO (Datenübertragbarkeit):** Vollständiger JSON-Export aller persönlichen Daten (`GET /api/gdpr/export`), inklusive Profil, Einstellungen, persönliche Todos, Benachrichtigungen, private Kontakte, erstellte Ordner, Projekte und Zeiteinträge.
   - **Art. 17 DSGVO (Recht auf Löschung / Vergessenwerden):** Unwiderruflicher Account-Löschvorgang (`DELETE /api/gdpr/account`) mit Verifikation des aktuellen Passworts. Kaskadierendes Entfernen persönlicher Daten, Bereinigung von Projekt- und Ordnermitgliedschaften, Übergabe/Löschung abhängiger Ressourcen, gefolgt von automatischem Logout.
   - **Art. 13/14 DSGVO (Transparenz & Speicherung):** Aufklärung über ISO-zertifiziertes Hosting in der Schweiz (Hostcreators Shared-Hosting, kein Drittanbieter-Tracking) und gesetzliche 10-jährige Aufbewahrungspflicht für Geschäftsunterlagen (Abrechnungen/Zeiterfassungen).

3. **Teamverwaltung & Zugriffsmatrix (Access Matrix):**
   - Aggregierte Übersicht (`GET /api/team/access-matrix`) aller Mitglieder, Einladungen, Ordner, Projekte und Gruppen.
   - Berechnung der effektiven Berechtigungsstufe pro Ordner und Projekt (Direktmitgliedschaft, Gruppenberechtigung, Firmen-Sichtbarkeit).
   - Inline-Berechtigungsanpassung (`POST /api/team/access-matrix/permissions`) zur schnellen Rollenänderung oder Entziehung.
   - Einladungsverwaltung (Link kopieren, Widerrufen via `DELETE /api/companies/invitations/:id`).

4. **Benutzergruppen & Gruppenberechtigungen (User Groups):**
   - Unterstützung für **alle Nutzer** (sowohl Company-Admins als auch Free-Plan Nutzer mit eingeladenen Kollaborateuren).
   - Schema: `user_groups`, `user_group_members`, `project_group_access`, `folder_group_access`.
   - Endpunkte: `GET /api/groups`, `POST /api/groups`, `PUT /api/groups/:id`, `DELETE /api/groups/:id`, `POST /api/groups/:id/assign`.
   - Integration in Zero-Trust 4-Stufen-Pipeline: Gruppenrechte werden in `evaluateProjectAccess` und `evaluateFolderAccess` mit Rollen-Rangfolge (`owner` > `admin` > `editor` > `viewer`) berücksichtigt.
   - UI in `pages/settings.vue` (für alle Nutzer), `pages/company/index.vue` (für Firmen-Admins) und Ordner-Teilen-Modal in `pages/folders/[id].vue`.

---

## 2. Durchgeführte Änderungen

### 2.1 Datenbank-Schema & Migrationen
- Tabellen hinzugefügt in:
  - `server/db/schema.sql`
  - `server/db/index.ts`
  - `scripts/migrate-mysql.cjs`
  - `ensureTables()` in `api/index.php`, `public/api/index.php`, `server-php/index.php`

### 2.2 Backend (Nitro / TypeScript)
- `server/utils/userSettings.ts`: `timezone` in `UserSettings` Interface, Defaults und `normalizeSettings`.
- `server/utils/permissions.ts`: `evaluateProjectAccess` und `evaluateFolderAccess` um Gruppen-Lookups (`project_group_access`, `folder_group_access`) erweitert.
- `server/api/gdpr/export.get.ts`: JSON-Export aller Nutzerdaten.
- `server/api/gdpr/account.delete.ts`: DSGVO-Accountlöschung mit Passwort-Prüfung.
- `server/api/groups/index.get.ts`, `index.post.ts`, `[id].put.ts`, `[id].delete.ts`, `[id]/assign.post.ts`: Gruppen-CRUD und Rechte-Zuweisung.
- `server/api/team/access-matrix.get.ts`: Aggregation der Zugriffsmatrix.
- `server/api/team/access-matrix/permissions.post.ts`: Inline-Rollenanpassung.
- `server/api/companies/invitations/[id].delete.ts`: Einladungs-Widerruf.

### 2.3 Backend (PHP - Single Source of Truth)
- Synchronisiert in allen 3 PHP-Dateien (`api/index.php`, `public/api/index.php`, `server-php/index.php`):
  - `'timezone' => 'Europe/Zurich'` in `defaultUserSettings()`.
  - Routen: `gdpr/export` (GET), `gdpr/account` (DELETE).
  - Routen: `groups` (GET, POST), `groups/:id` (PUT, DELETE), `groups/:id/assign` (POST).
  - Routen: `team/access-matrix` (GET), `team/access-matrix/permissions` (POST).
  - Route: `companies/invitations/:id` (DELETE).
  - Syntax validiert mit `scripts/check-php-syntax.py`.

### 2.4 Frontend (Nuxt 3 / Vue 3)
- `pages/settings.vue`:
  - Neuer Bereich `privacy`: Export, Transparenzhinweise, Account-Löschung mit Passwort-Modal.
  - Neuer Bereich `team`: Subtabs `matrix` (Zugriffsmatrix) und `groups` (Gruppenverwaltung).
  - Zeitzonen-Dropdown mit Live-Uhrzeit-Anzeige im Profil.
  - Modale: Account löschen, Gruppe erstellen/bearbeiten, Gruppenrechte zuweisen, Benutzerrechte anpassen.
- `pages/company/index.vue`:
  - Neue Tabs `matrix` (Zugriffsmatrix) und `groups` (Gruppen & Berechtigungen).
  - Volle Integration der Gruppen- und Matrixverwaltung im Firmenportal.
- `pages/folders/[id].vue`:
  - Ordner-Teilen-Modal um Sektion "Gruppe zum Ordner berechtigen" erweitert.
  - Zuweisen von Gruppen mit Rollen (Editor, Viewer, Admin) und Entfernen zugewiesener Gruppen.
- `i18n/locales/de.json`, `en.json`, `sk.json`:
  - Alle Übersetzungen für Zeitzone, DSGVO, Zugriffsmatrix und Gruppen eingepflegt und syntaktisch validiert.

---

## 3. Verifikation & Build
- `python scripts/check-php-syntax.py` -> Alle 3 PHP-Dateien syntaktisch fehlerfrei.
- `node -e "..."` -> Alle 3 Locale-Dateien (DE, EN, SK) valides JSON.
- `python generate_index.py --stats` -> 136 Dateien, 98 Endpunkte erfasst.
- `npm run build:dist` -> Nuxt Generate & Sync erfolgreich.

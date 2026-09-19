# Korrektur- & Feature-Dokumentation: Kontakte-Verwaltung & Projekt-/Ordner-Sharing

- **Datum:** 2026-09-20
- **Betreff:** Neue Funktion Kontakte (Baustelle/Projekt/Firma), Berechtigungsvererbung bei Projekt- & Ordner-Freigaben, vCard-Export

## 1. Übersicht & Anforderung
User können Kontakte für Baustellen, Gewerke und Projekte erfassen und pflegen:
- Felder: Name, Vorname, Firma, Funktion/Gewerk, Festnetztelefon, Mobiltelefon, E-Mail, Projektzugehörigkeit (optional), Gruppe/Kategorie, Tags, Notizen, Freigabestatus (Im Unternehmen teilen / Privat).
- Sharing-Regeln:
  - Bei Aktivierung von `share_scope = 'company'` sehen alle Unternehmensmitglieder den Kontakt.
  - Bei Zuordnung einer `project_id` erhalten automatisch alle Benutzer Zugriff, die Zugriff auf das jeweilige Projekt haben (durch direkte Mitgliedschaft in `project_members`, Mitgliedschaft im Projektordner `folder_members`, Eigentümerschaft des Ordners oder unternehmensweite Sichtbarkeit `visibility = 'company'`).
  - Wenn ein Projekt oder Ordner mit neuen Mitgliedern geteilt wird, sehen diese sofort alle zugeordneten Projektkontakte.

## 2. Datenbank
- **MySQL 8.4 (`scripts/migrate-mysql.cjs`):**
  - Tabelle `contacts` mit UUID-ID, `user_id`, `company_id`, `project_id`, `first_name`, `last_name`, `company_name`, `role_function`, `phone`, `mobile`, `email`, `category_group`, `tags` (JSON), `notes`, `share_scope` (`private` | `company`), `created_at`, `updated_at`.
  - Indizes auf `user_id`, `company_id`, `project_id`, `category_group`.
- **SQLite (`server/db/schema.sql`, `server/db/index.ts`):**
  - Tabelle `contacts` synchron integriert für lokale Nitro-Entwicklung.

## 3. Backend API (PHP & Nitro)
- **PHP (`api/index.php`, `public/api/index.php`, `server-php/index.php`):**
  - `GET /api/contacts`: Filter nach `project_id`, `group`, `scope`, `search`. Prüft Zero-Trust-Sichtbarkeit inklusive rekursiver Projekt-/Ordner-Mitgliedschaften.
  - `POST /api/contacts`: Erstellt neuen Kontakt mit Validierung von `last_name` und Projektzugriff.
  - `GET /api/contacts/:id`: Detailabruf mit Zugriffskontrolle.
  - `PUT /api/contacts/:id`: Aktualisiert Kontaktdaten (Ersteller, Superadmin oder Company-Admin).
  - `DELETE /api/contacts/:id`: Löscht Kontakt (Ersteller, Superadmin oder Company-Admin).
- **Nitro (`server/api/contacts/`):**
  - `index.get.ts`, `index.post.ts`, `[id].get.ts`, `[id].put.ts`, `[id].delete.ts` mit identischen Berechtigungsprüfungen.

## 4. Frontend & UI
- **Hauptansicht Kontakte (`pages/contacts/index.vue`):**
  - Liquid-Glass MeisterTask UI mit Schnellstatistiken (Gesamt, Firma geteilt, Projektbezug, Privat).
  - Filterleiste: Textsuche, Gruppe, Projekt-Dropdown, Freigabe-Filter.
  - Umschaltung: Kartenansicht (Cards) und Tabellenansicht (Table).
  - Aktionen: Direktanruf (`tel:`), WhatsApp-Link, Mail (`mailto:`), Projekt-Verknüpfung, vCard-Download (.vcf), Bearbeiten, Löschen.
  - **Geschäftsadresse & Webseite mit OpenStreetMap-Miniatur:**
    - Neue Felder `address` und `website` in Haupt- und Projektkontakten.
    - Vollständig Open-Source & ohne API-Key: Klickbare Links zu OpenStreetMap & Google Maps.
    - Ausklappbare OpenStreetMap-Miniaturkarte (Leaflet/Mapnik iframe via OSM Nominatim Geocoding).
    - vCard-Export (.vcf) um `ADR;TYPE=WORK` und `URL` ergänzt.
    - KI-Autofill erkennt Adressen und Webseiten automatisch aus Signaturen/Texten.
  - **Duplikat-Schutz (Frontend & Backend):**
    - Echtzeit-Erkennung von Duplikaten bei Eingabe oder KI-Autofill anhand von E-Mail, Telefon/Mobilnummer (letzte 7 Ziffern) oder Vor-/Nachname.
    - Warnbanner im Modal mit 3 Schnellaktionen: "Bestehenden Kontakt bearbeiten", "Daten zusammenführen (Merge)" und "Trotzdem neu anlegen".
    - Backend-Schutz (HTTP 409 Conflict) verhindert versehentliches doppeltes Erfassen, außer `force_duplicate: true` wird explizit übergeben.
  - **KI-Autofill Assistent:** Im Kontakt-Erstellungsdialog integriert. Aus beliebigem unstrukturiertem Text (E-Mail-Signaturen, WhatsApp-Nachrichten, Notizen) extrahiert die angebundene DeepSeek V4 Flash API automatisch Vorname, Nachname, Firma, Funktion, Telefonnummern, E-Mail, Adresse, Webseite, Kategorie/Gruppe, Tags und Notizen und befüllt das Formular zur Überprüfung.
  - Standard Taskster-Buttons: `taskster_button`, `taskster_button_light`, `taskster_button_accent` (`px-6 text-xs h-[42px] rounded-lg`).
- **Projekt-Detailseite (`pages/projects/[id].vue`):**
  - Neuer Tab `📇 Kontakte (X)` im Projektheader.
  - Listet alle Kontakte der Baustelle/des Projekts inklusive Adresse, Webseite, OpenStreetMap-Link und Duplikatschutz.
  - Eigenes Modal mit KI-Autofill zum direkten Anlegen von Projektkontakten vor Ort.
- **Navigation (`components/Navbar.vue`, `app.vue`):**
  - Neuer Link `📇 Kontakte` in der oberen Navigationsleiste und der ausklappbaren rechten MeisterTask-Sidebar.

## 5. Deployment & Build
- `python scripts/check-php-syntax.py`: Bestätigt syntaktische Korrektheit aller 3 PHP-Dateien.
- `node scripts/migrate-mysql.cjs`: Spalten `address` und `website` auf Remote-MySQL erfolgreich migriert.
- `npm run build:dist`: Statischer Nuxt-Build erzeugt und in Git-Root synchronisiert.
- `python generate_index.py --stats`: 61 Endpunkte indexiert in `.agent_index.json`.

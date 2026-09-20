# 2026-09-20 Projektvorlagen Redesign & Excel/CSV Projekt-Import

- **Typ:** Feature / UX-Redesign / Backend-Erweiterung
- **Bereich:** UI (Folder, Admin, Company), Backend (PHP & Nitro), DB (SQLite & MariaDB), Import-Engine (XLSX)
- **Status:** Abgeschlossen & Getestet

## 1. Ausgangslage & Motivation
- Die bisherigen Projektvorlagen enthielten technische Entwickler-Begriffe (`(field_key)`, `[field_type]`, bedingte Logik als Mono-Code), die im Release für Anwender verwirrend waren.
- Die Vorlagenauswahl war auf 5 Basis-Vorlagen beschränkt.
- Der bisherige Excel-Import existierte nur für Aufgaben innerhalb eines einzelnen Abschnitts auf der Projektseite (`pages/projects/[id].vue`). Ein Import für komplette Projekte inklusive aller Phasen und Aufgaben fehlte.

## 2. Änderungen UI / Frontend
- **Bereinigung von Entwickler-Clutter:**
  - Technische Feld-Schlüssel und Mono-Tags vollständig entfernt.
  - Benutzerfreundliche Typ-Badges (`getFieldTypeLabel`: Auswahlfeld, Textfeld, Zahlenfeld, Datum, Ja/Nein, etc.).
  - Verständliche Klartext-Bedingungen ("Sichtbar bei: ...").
- **Erweiterung Projekt-Erstellungsmodal (`pages/folders/[id].vue`):**
  - **3-Modus-Switcher:**
    1. 📋 Aus Vorlage erstellen (Empfohlen)
    2. 📊 Excel / CSV Projekt-Import (Neu)
    3. 📝 Leeres Projekt (Blanko)
  - **Phasen-Anpassung ("Mehr Möglichkeiten"):**
    - Vorlagenauswahl zeigt interaktive Abschnitte/Phasen als Chips.
    - Benutzer kann vor der Erstellung Phasen entfernen (`✕`) oder neue Phasen hinzufügen (`+ Phase hinzufügen`).
  - **Excel / CSV Projekt-Import Engine:**
    - Drag & Drop Zone für `.xlsx`, `.xls`, `.csv`, `.tsv`.
    - Download-Button für fertige Beispieldatei (`Taskster_Projekt_Import_Muster.xlsx`).
    - Automatische Spaltenerkennung (Phase/Abschnitt, Aufgabentitel, Beschreibung, Fälligkeitsdatum, Priorität, Status, Tags).
    - Vorschau der ersten Zeilen und Zusammenfassung der erkannten Abschnitte.
    - Ein-Klick-Import mit automatischer Erstellung des Projekts, aller Phasen und Aufgaben mit direktem Redirect.
- **Admin- & Company-Portal:**
  - Bereinigung der Vorlagen-Karten in `pages/admin/index.vue` (saubere Typen und verständliche Bedingungen).

## 3. Änderungen Backend & DB
- **Vorlagen-Bibliothek (Erweiterung auf 12 Standard-Vorlagen):**
  - Gewerbe:
    1. Bau- & Tiefbauleitung (LWL / Glasfaser)
    2. IT-Systemhaus & Software-Entwicklung
    3. Handwerk & Elektroinstallation
    4. Sanitär, Heizung & Haustechnik (SHK) [NEU]
    5. Marketing, Kampagnen & Social Media [NEU]
    6. Immobilien-Verkauf & Vermietung [NEU]
    7. Gastronomie & Event-Catering [NEU]
    8. Qualitätsmanagement & ISO-Audit [NEU]
  - Privat:
    9. Hausbau & Wohnungsrenovierung
    10. Event- & Feierplanung (Hochzeit, Fest)
    11. Privater Umzug & Wohnungswechsel [NEU]
    12. Finanzabschluss & Steuererklärung [NEU]
- **Seed & Parität:**
  - Synchronisiert in `server-php/index.php`, `public/api/index.php`, `api/index.php`, `server/db/index.ts` und `server/db/default-templates.ts`.
  - Idempotenter Check (`is_system = 1 < 12`) mit `ON CONFLICT` / `ON DUPLICATE KEY UPDATE` bzw. Existenzprüfung.
- **Projekt-Erstellung (`POST /api/projects`):**
  - Akzeptiert optional `custom_lists` (vom Benutzer angepasste Phasen).
  - Akzeptiert `import_tasks` (Array von Aufgaben mit automatischer Zuordnung zur Ziel-Liste).

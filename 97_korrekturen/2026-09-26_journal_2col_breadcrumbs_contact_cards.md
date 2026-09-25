# Korrektur: Firmen-Admin Breadcrumbs, Journal 2-Spalten-Layout & Kompakte Kontaktkarten

Datum: 2026-09-26  
Status: Erfolgreich umgesetzt  
Bereiche: Firmen-Administration (`pages/company/index.vue`), Projektjournal (`pages/journal.vue`, `pages/folders/[id].vue`), Kontakte (`pages/contacts/index.vue`, `pages/folders/[id].vue`, `pages/projects/[id].vue`)

---

## 1. Problemstellung & Anforderungen
1. **Firmen-Admin Page (`pages/company/index.vue`)**:
   - Breadcrumbs waren außerhalb der Kopfkarte platziert und enthielten den Firmennamen nicht.
   - Ziel: Breadcrumbs direkt in die Kopfkarte integrieren (wie bei Ordner/Projekten) mit Format `Dashboard / Firmen-Administration / [Firmenname]`.

2. **Projektjournal & Logbuch (`pages/journal.vue` & `pages/folders/[id].vue`)**:
   - Die Einträge waren vollflächig gestapelt (Screenshot 1) und wirkten unkompakt im Vergleich zum Projektjournal (Screenshot 2, "Mobile Design").
   - Ziel: Einheitliches 2-Spalten-Layout (`grid grid-cols-1 lg:grid-cols-12 gap-5`):
     - **Links (8 Spalten):** Titel, Kategorie-Badge, Metadatenzeile (Autor, Datum, verknüpfte Aufgabe), KI-Zusammenfassungs-Box (`Sparkles`, Gradient, Badge `KI-Agent`, Button `Neu analysieren`), KI-Aktionskarten (`create_task`, `update_task`), Inhalt / Notizen mit 5-Zeilen-Verlauf und `Original-Ansicht`-Button.
     - **Rechts (4 Spalten):** Prominentes Widget `📌 Verknüpfte Aufgabe` mit Schnellzuweisung/Lösen im Dropdown und `Aufgabe öffnen`-Link, Widget `👥 TEILNEHMER` (mit Zähler und Status-Chips) sowie `Dateianhänge`.

3. **Kontakte-Karten (`pages/contacts/index.vue`, `pages/folders/[id].vue`, `pages/projects/[id].vue`)**:
   - Karten waren mit ~450px Höhe viel zu groß (mehrere große Innenabstände `p-5`, riesige `bg-slate-50`-Boxen für Telefon und Adresse, mehrzeilige Links).
   - Ziel: Extrem kompakte, moderne Kachelansicht (`p-3.5`, `w-8 h-8` Avatar, einzeilige Firmen- & Funktionszeile, kompakte Badges, kombinierte Informationsbox für Mobile/WhatsApp/Mail/Adresse mit Map-Direktlink, Höhe um über 55% reduziert).

---

## 2. Durchgeführte Änderungen

### A. Firmen-Admin (`pages/company/index.vue`)
- Breadcrumbs aus dem oberen Rand in den Header-Container (`bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl p-6 shadow-sm space-y-4 mb-6`) integriert.
- Pfad: `Dashboard / Firmen-Administration / {{ company?.name || 'Mein Unternehmen' }}`.
- Farbakzente auf `#00A3C4` vereinheitlicht.

### B. Projektjournal (`pages/journal.vue` & `pages/folders/[id].vue`)
- Journal-Einträge auf das 2-Spalten-Layout aus Screenshot 2 umgestellt.
- Dynamische Icon- und Badge-Zuordnung (`⏱️ Regiearbeit`, `🏛️ Bausitzung`, `📋 Bautagebuch`, `✉️ E-Mail`, etc.).
- Aufgabenverknüpfungs-Widget rechts mit Schnellwahl-Dropdown und Verlinkung zur Aufgabe im Projekt.
- Teilnehmerliste mit Anwesenheitsindikatoren und Dateianhänge-Vorschau.
- Unterstützung für Bearbeiten (`JournalEntryModal`) und KI-Aktualisierung (`triggerAiAnalysis`).

### C. Kontaktkarten (`pages/contacts/index.vue`, `pages/folders/[id].vue`, `pages/projects/[id].vue`)
- Grid auf `grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3.5` optimiert.
- Padding von `p-5` auf `p-3.5` verkleinert, Avatar auf `w-8 h-8 rounded-lg` reduziert.
- Firma und Funktion kompakt nebeneinander mit Bullet-Separator platziert.
- Einzelner, schlanker Informationscontainer (`p-2.5 rounded-xl bg-slate-50/80`) für Rufnummer, WhatsApp-Direktchat, E-Mail und Adresse mit Kartenlink.
- Kompakte Footer-Leiste für vCard-Export, Bearbeiten und Löschen.

---

## 3. Verifikation
- PHP-Syntax: Alle Dateien fehlerfrei geprüft (`python scripts/check-php-syntax.py`).
- Agent-Index: Neu indiziert (`python generate_index.py --stats`).
- Build: `npm run build:dist` synchronisiert `.output/public` zu Root für Git-Only Deployment.

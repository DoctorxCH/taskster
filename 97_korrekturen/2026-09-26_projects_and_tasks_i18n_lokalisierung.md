# Korrektur-Log: Projekte & Aufgaben (pages/projects/[id].vue) vollständige i18n-Lokalisierung

**Datum:** 2026-09-26  
**Betroffene Komponenten:** `pages/projects/[id].vue`, `i18n/locales/de.json`, `i18n/locales/en.json`, `i18n/locales/sk.json`  
**Thema:** Vollständige Entfernung von hardgecodeten Strings in der Projekt- und Aufgabenansicht (Kanban, Tabellenansicht, Task Drawer, Modals, Einstellungen, Controlling, Journal, Kontakte, Dialoge & Toasts) und 100%ige Synchronisation aller Sprachdateien (DE, EN, SK).

---

### Ursachenanalyse & Umfang
1. In `pages/projects/[id].vue` (über 9.500 Zeilen) waren weite Teile des Interfaces für Projekte und Aufgaben fest auf Deutsch hinterlegt:
   - Header, Breadcrumb, Modus-Umschalter, Schnellaktionsleiste und Navigationstabs.
   - Kanban-Board Spalten, Task-Karten, Status-Badges, Prioritäten, Inline-Timer und Zuweisungen.
   - Tabellen-/Listenansicht mit Tabellenköpfen, Aktionen und Aufwandsanzeigen.
   - Moderner Task Drawer / Detail-Modal (Aufgabentitel, Beschreibung, Zusatzfelder, Checklisten & Unteraufgaben, manuelle Zeiterfassung & Budget, Kommentare & Besprechungsnotizen, Dateianhänge, Seitenleiste mit Abschnitt, Status, Priorität, Zuweisungen, Fälligkeit, Tags, Farbauswahl, Lösch-Aktion).
   - Abschnittsverwaltung (Neuer Abschnitt, Sichtbarkeitsmodi, Farben, Sortierung, Ziel-Abschnitt).
   - Excel- / CSV-Import-Modal (Upload-Zone, Spalten-Mapping für Standard- & Zusatzfelder, Vorschau, Fortschritt).
   - Benutzerdefinierte Felder & Bedingte Logik (Gültigkeitsbereich, Feldtypen, Optionslisten, Abhängigkeitsregeln).
   - Zeiterfassungs- & Controlling-Tab (Gesamtaufwand, Budgets, Buchungsfilter, Zeiteintrag bearbeiten/erfassen).
   - Projektjournal & KI-Assistenten (E-Mail-Parser, vorgeschlagene Aufgaben, Dokumente, Schnellsuche).
   - Skript-Toasts, Warnungen, Fehler und Bestätigungs-Dialoge.
2. Beim Umschalten auf Englisch (`en`) oder Slowakisch (`sk`) blieb fast die gesamte Aufgaben- und Projektansicht auf Deutsch.

### Durchgeführte Maßnahmen
1. **Erweiterung & Bereinigung der Sprachdateien (`de.json`, `en.json`, `sk.json`):**
   - Hinzufügen von über 300 neuen und präzisierten Schlüsseln unter dem Namespace `projects.*`.
   - Alle drei Sprachdateien (`de.json`, `en.json`, `sk.json`) sind exakt synchronisiert und besitzen identisch jeweils 2.883 Übersetzungsschlüssel.
2. **Template- & Script-Lokalisierung in `pages/projects/[id].vue`:**
   - Nahezu 350 Stellen im Template durch `$t('projects....')`, `:placeholder="$t(...)"`, `:title="$t(...)"` ersetzt.
   - Alle Skript-Meldungen (`showToast(...)`, Bestätigungsdialoge `triggerConfirmModal(...)`) auf `t('projects....')` umgestellt.
   - 0 verbleibende hardgecodete deutsche Strings im Template und in Script-Meldungen.
3. **Validierung & Index-Aktualisierung:**
   - Statischer Build via `npm run build:dist` erfolgreich durchgeführt.
   - `.agent_index.json` via `python generate_index.py` aktualisiert.

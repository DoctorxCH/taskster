# 2026-09-25: Projektjournal Button-Umbenennung, Aufgaben-Suchfunktion & KI-Zusammenfassung in /journal

## Kontext
Optimierung des Moduls Projektjournal basierend auf Benutzeranforderungen:
1. Umbenennung der Erfassungs-Buttons zur klaren Unterscheidung zwischen regulärem Journaleintrag und komplexem E-Mail/Dokument-Import mit KI.
2. Integration einer interaktiven Live-Suchfunktion für verknüpfte Aufgaben im `JournalEntryModal`.
3. Anzeige der KI-Zusammenfassung und KI-Aktionskarten im globalen Projektjournal (`pages/journal.vue`) analog zur Projekt-Subseite.

## 1. Button-Umbenennung & Rollenverteilung
- Standard-Journaleintrag: `+ Journaleintrag` (Primary `taskster_button`, öffnet `JournalNoteModal`).
- Komplexer Datei-/Protokoll-/KI-Import: `+ Dokument / Protokoll (KI)` (Ghost `taskster_button_light`, öffnet `JournalEntryModal`).
- Konsistent synchronisiert in:
  - `pages/journal.vue` (Header & Empty-State)
  - `pages/folders/[id].vue` (Header, Journal-Tab & Empty-State)
  - `pages/projects/[id].vue` (Header & Empty-State)
  - `components/JournalNoteModal.vue` (Header-Titel: "Neuen Journaleintrag erfassen", Button: "Eintrag speichern")
  - `components/JournalEntryModal.vue` (Header-Titel: "Dokument / Protokoll erfassen (KI)", Button: "Dokument / Protokoll speichern")
  - `i18n/locales/de.json`, `en.json`, `sk.json`

## 2. Aufgaben-Suchfunktion im Dokument-/Protokoll-Modal (`JournalEntryModal.vue`)
- Ersatz des nativen `<select>`-Feldes für `Verknüpfte Aufgabe (optional)` durch eine responsive Live-Search Combobox mit Popover:
  - Live-Textfilterung nach Aufgabentitel und Abschnittsname (`filteredTasks`).
  - Auswahlmöglichkeiten: `-- Keine Verknüpfung --`, `✨ Automatisch zuweisen (anhand Text)`, gefilterte Aufgabenliste mit Häkchen-Indikator.
  - Anzeige des Task-Zählers (`filteredTasks.length`).
  - Schliessen bei Auswahl oder Klick ausserhalb.

## 3. KI-Zusammenfassung & Aktionskarten im globalen Journal (`pages/journal.vue`)
- Integration der `entry.metadata.ai_summary` Cyan-Gradient-Box mit Sparkles-Icon, `KI-Agent`-Badge und On-Demand `[Neu analysieren]` / `[⚡ KI-Analyse]` Trigger.
- Integration der interaktiven Aktionskarten `entry.metadata.action_items` (Aufgabe anlegen, aktualisieren, abschliessen) mit Statusanzeige.
- Implementierung der clientseitigen `triggerAiAnalysis(entry)`-Methode in `pages/journal.vue` zur dynamischen Aktualisierung über Nitro/PHP-Endpunkte.
- Strukturierte Aufteilung in Titel -> KI-Zusammenfassung -> Aktionskarten -> Inhalt/Notizen (mit 5-Zeilen-Ausklappung & Originalansicht-Modal).

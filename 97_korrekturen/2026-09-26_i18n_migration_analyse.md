# 2026-09-26 – i18n Migration Analyse & Checkliste erstellt

## Aktion
- Vollständige Analyse aller hardcodierten Strings im Taskster-Frontend durchgeführt
- Automatisiertes Scan-Script (`find_hardcoded.py`) geschrieben und ausgeführt
- Ergebnis: **1078 hardcodierte Strings in 24 Vue-Dateien** gefunden
- Detailliertes Handout mit Step-by-Step-Checkliste erstellt

## Betroffene Dateien (nach String-Anzahl)
| Datei | Strings |
|-------|---------|
| pages/admin/index.vue | 337 |
| pages/folders/[id].vue | 260 |
| pages/contacts/index.vue | 75 |
| pages/projects/[id].vue | 74 |
| pages/time.vue | 49 |
| pages/journal.vue | 47 |
| components/JournalEntryModal.vue | 41 |
| pages/company/index.vue | 41 |
| components/VoiceRecorderModal.vue | 27 |
| components/CalendarEventModal.vue | 23 |
| + 14 weitere Dateien | ~104 |

## Migrations-Phasen
1. **Phase 1** – Components (127 Strings, 9 Dateien)
2. **Phase 2** – Kern-Seiten wie time, journal, contacts, login (226 Strings, 10 Dateien)
3. **Phase 3** – Projekt- & Ordner-Detailseiten (334 Strings, 2 Dateien)
4. **Phase 4** – Admin & Firmen-Verwaltung (379 Strings, 2 Dateien)
5. **Phase 5** – app.vue & Sonstiges (12 Strings, 1 Datei)

## Referenz
- Checkliste: `99_anweisungen/i18n_migration_checkliste.md`
- Scan-Tool: `scripts/extract_i18n.py` (existierend)

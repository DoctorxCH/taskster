# Entfernung aller DeepSeek Modellreferenzen (Neutralisierung der KI-Engine)

**Datum:** 2026-09-26  
**Bereich:** Frontend UI, Modals, Backend API, Server Nitro Endpunkte, Scripts, Locales & Doku  
**Status:** Abgeschlossen & Verifiziert  

## Zusammenfassung
Auf Anweisung wurden sämtliche Nennungen und Referenzen des spezifischen KI-Modells "DeepSeek" aus dem gesamten Codebase, UI-Labels, Badges, Hilfetexten, Backend-Kommentaren, Default-Fallbacks, Skripten und Dokumentationen vollständig entfernt.

## Betroffene Komponenten & Dateien

1. **Frontend UI & Modals:**
   - `components/VoiceRecorderModal.vue`:
     - Badge von `Whisper + DeepSeek` auf neutrales `KI-Assistent` (`VoiceRecorderModal.whisper_ai`) umgestellt.
     - Status-Meldung von "DeepSeek erkennt..." auf "Die KI analysiert Aufgaben, Adressen und Handlungsschritte." neutralisiert.
   - `components/JournalEntryModal.vue`:
     - Badge von `DeepSeek AI Engine` auf `KI-Engine` geändert.
   - `pages/projects/[id].vue`:
     - Badges in `showNewEntryModal` und `showNewNoteModal` von `DeepSeek Engine` auf `KI-Engine` geändert.
     - Kommentare neutralisiert.
   - `pages/admin/index.vue`:
     - Placeholder und Empfehlungs-Text im Tab KI-Settings von DeepSeek bereinigt.

2. **Übersetzungen (i18n):**
   - `i18n/locales/de.json`: Schlüssel `VoiceRecorderModal.whisper_ai` = "KI-Assistent".
   - `i18n/locales/en.json`: Schlüssel `VoiceRecorderModal.whisper_ai` = "AI Assistant".
   - `i18n/locales/sk.json`: Schlüssel `VoiceRecorderModal.whisper_ai` = "AI Asistent".

3. **Backend & Server-Routen:**
   - `server/api/ai/analyze-voice.post.ts`: Fallback auf `config.model || 'google/gemini-2.5-flash'`.
   - `server/api/projects/[id]/journal/parse-email.post.ts`: Kommentar auf neutral `KI-Engine` gesetzt.
   - `api/index.php` (sowie Kopien in `public/api/index.php` und `server-php/index.php`): Header-Kommentare und Endpunkt-Titel auf neutral `OpenRouter` / `KI-Engine` bereinigt.

4. **CLI-Tools & Doku:**
   - `scripts/ai.cjs`: Dateikopf auf `Taskster AI-CLI (OpenRouter)` neutralisiert.
   - `97_korrekturen/2026-09-20_ai_anbindung_openrouter.md`: Umbenannt (von vormals `*_deepseek.md`) und Modell-Spezifika neutralisiert.
   - `AGENTS.md`, `UEBERSICHT.md`, `95_reports/01_dashboard_report.md`, `97_korrekturen/2026-09-20_kontakte_*.md`, `97_korrekturen/2026-09-20_voice_*.md`, `97_korrekturen/2026-09-22_projektjournal_*.md`, `i18n_report.md`: Alle Vorkommnisse neutralisiert.

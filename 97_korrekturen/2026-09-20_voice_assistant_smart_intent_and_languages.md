# 2026-09-20: KI-Sprachassistent: Kontexterkennung, Aktionen & Spracheinstellungen

**Datum:** 2026-09-20  
**Autor:** Antigravity AI Agent  
**Bereich:** Voice Assistant, AI Intent Recognition, Journal API, Settings, Whisper & Multi-Language  

## 1. Übersicht & Ziel
1. **Intelligente Aufgabenerkennung & Handlungsvorschläge:** Nach Aufnahme einer Sprachnotiz analysiert die KI (DeepSeek V4 Flash) den Inhalt im Kontext aller aktiven Projekte und offenen Aufgaben des Nutzers. Erkennt sie Aufgabennamen, Nummern (#12), Räume/Adressen oder Checklisten-Punkte, bietet sie interaktive 1-Klick-Aktionen an:
   - 📝 **Aufgabe ergänzen** (Hängt Notiz direkt an die erkannte Aufgabe an)
   - ✅ **Aufgabe abschliessen** (Setzt Status auf Erledigt + dokumentiert Notiz)
   - 📋 **Checkliste hinzufügen** (Ergänzt Unteraufgaben)
   - ➕ **Neue Aufgabe anlegen**
   - 📔 **Als Notiz/Journal speichern**
2. **Journal-Notiz ohne gewähltes Projekt:** Speichern als Notiz/Journal schlägt nicht mehr fehl, wenn kein Projekt gewählt ist. Das Backend (`/api/journals`) löst automatisch das Standardprojekt oder ein persönliches Notizenprojekt auf.
3. **App- & Whisper-Sprache in Einstellungen:**
   - App-Sprache: Deutsch, English, Français, Italiano
   - Whisper-Sprache: Deutsch (de), Schweizerdeutsch (de-CH), English (en), Français (fr), Italiano (it), Automatisch erkennen (auto)
   - Konfigurierbar in `pages/settings.vue` und direkt im `VoiceRecorderModal.vue`.

## 2. Geänderte Dateien
- `server/api/ai/analyze-voice.post.ts` (NEU: KI-Kontexterkennung mit DeepSeek V4 Flash und Fallback-Heuristik)
- `server/api/ai/transcribe.post.ts` (Dynamische Sprachen & Prompts für Whisper v3 Turbo)
- `server/api/journals/index.post.ts` & `server/api/journals/index.get.ts` (Unterstützung für Notizen ohne Projekt)
- `api/index.php`, `public/api/index.php`, `server-php/index.php` (PHP-Routen `ai/analyze-voice`, `ai/transcribe`, `journals`)
- `components/VoiceRecorderModal.vue` (Komplettes Upgrade auf interaktive KI-Vorschläge, Sprachauswahl, 1-Klick-Ausführung)
- `pages/settings.vue` (Profil > Darstellung & Sprache > App-Sprache & Whisper-Sprache)

## 3. Deployment & Build
- `generate_index.py` ausgeführt (81 API-Endpunkte indiziert).
- `npm run build:dist` erfolgreich generiert und nach Git-Root synchronisiert.

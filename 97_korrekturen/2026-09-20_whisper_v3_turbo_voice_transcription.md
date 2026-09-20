# 2026-09-20 Integration: OpenRouter Whisper Large V3 Turbo Sprachtranskription

## 1. Modell-Konfiguration (`ai.config.json`)
- Ergänzung von `"audio_model": "openai/whisper-large-v3-turbo"` als primäres Speech-to-Text-Modell über OpenRouter API.

## 2. Backend-Endpunkte
- **Nuxt Server Route**: `server/api/ai/transcribe.post.ts` empfängt Audio-Aufnahmen (Base64) und kommuniziert direkt mit OpenRouter (`https://openrouter.ai/api/v1/audio/transcriptions` und Multimodal Chat Fallback).
- **PHP API**: `POST /api/ai/transcribe` in `public/api/index.php`, `api/index.php` und `server-php/index.php` implementiert via cURL.

## 3. UI-Komponente & Workflow (`components/VoiceRecorderModal.vue`)
- Interaktiver Sprachnotiz-Dialog mit Live-Mikrofonaufnahme (`MediaRecorder` API), visualisiertem Aufnahme-Timer, Live-Statusindikator und Stopp-Funktion.
- Nach der Transkription durch `openai/whisper-large-v3-turbo` erscheint der bearbeitbare Text direkt im Modal.
- **Aktionen**:
  - Speichern als Projekt-Journal/Notiz (`POST /api/journals`).
  - Direktes Anlegen als neue Aufgabe in einem gewählten Projekt (`POST /api/tasks`).
  - Kopieren in die Zwischenablage.
- **Dashboard & Projektintegration**: "Neue Sprachnotiz"-Button im Dashboard und in den Projekt-Headern integriert.

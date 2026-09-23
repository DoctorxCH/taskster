# Taskster Coding Guidelines & Architektur-Regeln

1. **Stack:** Backend API-First (REST/JSON, typisiert), Frontend Vue/Nuxt 3 + Tailwind CSS, Mobile Capacitor (iOS/Android), DB MariaDB/PostgreSQL (JSONB).
2. **Security & Zero-Trust:** Serverseitige Autorisierung. Nicht berechtigte Ressourcen liefern ausnahmslos `404 Not Found` (nie `403`), um Existenz zu verschleiern. Company-Policy überschreibt Projekt- & Feldeinstellungen.
3. **Async File Jobs:** Kein E-Mail-Parsing (.msg/.eml) oder Audio-Transcoding im Request-Thread -> Queues nutzen. Kopfdaten, Body und Anhänge atomar trennen. MIME-Type serverseitig prüfen.
4. **UI/UX:** Viewer erhalten saubere Read-Only-Ansicht ohne Dummy-Controls. Bedingte Felder reaktiv ohne Latenz. Mobile Aktionen (Timer, Foto, Voice) <= 2 Klicks.
5. **Code-Index & Suche (Token-Disziplin):** Vor und nach jeder Arbeit `python generate_index.py` ausführen. Zur Lokalisierung von Endpunkten, Tabellen, Composables oder Funktionen **immer zuerst `.agent_index.json`** lesen. Grep/Repo-Suche ist erst als Fallback erlaubt, falls das Element nicht im Index steht.

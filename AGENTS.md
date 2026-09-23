# Taskster – Agenten-Architektur & Systemregeln

## 1. Wissensbasis (90er-Regel)
- **`99_anweisungen/` (Prio HOCH - Verbindlich):** Alle Spezifikationen & Schemata. Index: [00_INDEX.md](99_anweisungen/00_INDEX.md)
- **`98_Vorschläge/` (Prio MITTEL):** Verbesserungsvorschläge (`JJJJ-MM-TT_name_prioX.md`).
- **`97_korrekturen/` (Prio DOKU):** Changelog durchgeführter Fixes (`JJJJ-MM-TT_name.md`).
> *Hinweis Pfade:* Relative Links funktionieren arbeitsstationsunabhängig auf beiden Entwicklungs-PCs (`c:\Users\marti\...` und `c:\Users\taakumao\...`).

## 1b. Code-Index (Verbindlich für Agenten)
- **Skript:** `generate_index.py` (Projekt-Root) erzeugt `.agent_index.json` (kompaktes Format, 1 Zeile pro Datei, ~20 KB).
- **Such-Workflow (STRIKT EINZUHALTEN):**
  1. **Vor jeder Arbeit:** `python generate_index.py` ausführen.
  2. **Immer zuerst `.agent_index.json` lesen:** Bei jeder Suche nach Routen/Endpunkten, PHP-Klassen, Composables, exportierten Funktionen oder SQL-Tabellen MUSS der Agent **zuerst** in `.agent_index.json` nachschlagen.
  3. **Gezielter Dateizugriff:** Sobald die Zieldatei im Index identifiziert ist, wird **nur** diese Datei (und nur der relevante Code-Abschnitt) geöffnet.
  4. **Fallback auf `grep` nur bei Fehlschlag:** Ein Dateisystem-Suchlauf (`grep_search`) ist **erst dann erlaubt**, wenn das gesuchte Element (z. B. ein spezifischer UI-String, CSS-Klasse, unveröffentlichte interne Hilfsfunktion) im Index **nicht** gefunden wurde. Blinde Repo-weite Suchläufe ohne vorherigen Index-Check sind verboten!
  5. **Nach jeder Arbeit:** `python generate_index.py` erneut ausführen, damit der Index aktuell bleibt.
- **Inhalt:** API-Endpunkte (Nitro-Routen aus Dateipfaden + PHP-Routen), Klassen, exportierte TS/Vue-Funktionen & Composables, Vue-APIs (`defineProps`, etc.), referenzierte SQL-Tabellen.
- **Nutzen:** Der Agent liest zuerst `.agent_index.json` statt das Repo zu durchsuchen (0 Tokens für die Suche).
- **Befehle:**
  - `python generate_index.py` — Index neu erzeugen
  - `python generate_index.py --stats` — zusätzlich Statistik ausgeben
  - `python generate_index.py --quiet` — ohne Konsolenausgabe

## 1c. AI-Anbindung (OpenRouter)
- **Config:** `ai.config.json` (Single Source of Truth, **keine Secrets**).
- **Key:** `OPENROUTER_API_KEY` in `.env` (gitignored) — **niemals** in den Client.
- **Kein Provider-Pinning:** Keine Provider-Einschränkungen (`allow_fallbacks = true`). Niemand extern/im Frontend muss wissen, welche KI im Hintergrund genutzt wird.
- **CLI (VS Code):** `npm run ai -- "Frage"`, `--file <pfad>`, `--git [n]`, `--json`, `--check`.
- **Produktion (PHP / Nitro):** `POST /api/ai/chat` (Auth nötig), `GET /api/ai/config`.
- **VS Code Tasks:** `AI: Frage stellen`, `AI: Aktuelle Datei analysieren`, `AI: Letzte Git-Änderungen erklären`.
- **Doku:** [97_korrekturen/2026-09-20_ai_anbindung_openrouter_deepseek.md](97_korrekturen/2026-09-20_ai_anbindung_openrouter_deepseek.md)

## 2. Antigravity Subagents & Kernrollen
- **Architekt:** [.agents/agents/architect.md](.agents/agents/architect.md) / [.agents/rules/martin_persona.md](.agents/rules/martin_persona.md) (SaaS, Berechtigungen, B2B)
- **Delegation Runtime:** [.agents/rules/delegation_runtime.md](.agents/rules/delegation_runtime.md) (Aufruf via `invoke_subagent` oder `/agents` Panel)
- **Spezialisten (`.agents/agents/`):**
  `@orchestrator` (Koordination & Review), `@architect` (SaaS/B2B-Architektur), `@designer` (UI/Nuxt),
  `@backend` (API/DB), `@security` (Zero-Trust), `@mobile-sync` (Capacitor/Offline),
  `@ingestion` (.msg/Audio), `@export` (PDF/Excel), `@billing` (Quotas), `@qa` (Tests), `@devops` (CI/CD).

## 3. Tech-Stack & Design-System
- **Stack:** Vue.js/Nuxt 3, Tailwind CSS, API-First (PHP/TypeScript), MariaDB/PostgreSQL (JSONB), Capacitor.
- **Security:** Zero-Trust serverseitig; unberechtigt = `404 Not Found` (kein Info-Leak); Company-Policy überschreibt alles.
- **Design (VERBINDLICH):** [99_anweisungen/design-system.md](99_anweisungen/design-system.md) + [design-tokens.json](99_anweisungen/design-tokens.json). **Vor jeder UI-Änderung lesen.** Primärfarbe `#00A3C4` (nicht das ungenutzte `brand`-Grün in `tailwind.config.ts`).
- **Buttons (Standard: `px-6 text-xs h-[42px] rounded-lg`):**
  - Primary / Save: `taskster_button` (Blau)
  - Destructive / Accent: `taskster_button_accent` (Rot)
  - Ghost / Cancel: `taskster_button_light` (Weiß + blauer 3px Rand)
- **Keine Browser-Popups (VERBINDLICH):** Niemals native JavaScript-Browser-Popups (`alert()`, `confirm()`, `prompt()`) verwenden! Alle Feedback-Meldungen, Warnungen, Fehler und Bestätigungen müssen ausnahmslos als elegante In-App-Popups (Toast-Benachrichtigungen, Modals oder Inline-Banner) im Taskster-Design umgesetzt werden.

## 4. Deployment & Sync (Git Only - Verbindlich)
- **Git als Single Source of Truth:** Sowohl Quellcode als auch der lokal gebaute Produktions-Build (`index.html`, `200.html`, `_nuxt/`, etc.) werden direkt in Git committed und nach `origin/main` gepusht.
- **Server-Umgebung (Hostcreators Shared-Hosting):** Der Server besitzt keinen C-Compiler (`cc`) und ein inkompatibles GLIBC für native Module (`better-sqlite3`). Daher kann auf dem Server **kein** `npm install` oder `npm run build` ausgeführt werden.
- **Verbindlicher Deployment-Ablauf:**
  1. Änderungen lokal testen.
  2. Lokaler statischer Build & Sync: `npm run build:dist` (führt `nuxt generate` aus und synchronisiert `.output/public` direkt in das Git-Root-Verzeichnis).
  3. Git Commit & Push: `git add -A; git commit -m "..."; git push origin main`.
  4. Server-Sync: Auf dem Server wird einfach `git pull origin main` ausgeführt! (Kein SFTP nötig).
  5. Bei DB-Schema-Änderungen: `node scripts/migrate-mysql.cjs` remote gegen MySQL ausführen.

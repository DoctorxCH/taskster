# Taskster – Agenten-Architektur & Systemregeln

## 1. Wissensbasis (90er-Regel)
- **`99_anweisungen/` (Prio HOCH - Verbindlich):** Alle Spezifikationen & Schemata. Index: [00_INDEX.md](file:///c:/Users/marti/Taskster/99_anweisungen/00_INDEX.md)
- **`98_Vorschläge/` (Prio MITTEL):** Verbesserungsvorschläge (`JJJJ-MM-TT_name_prioX.md`).
- **`97_korrekturen/` (Prio DOKU):** Changelog durchgeführter Fixes (`JJJJ-MM-TT_name.md`).

## 1b. Code-Index (Verbindlich für Agenten)
- **Skript:** `generate_index.py` (Projekt-Root) erzeugt `.agent_index.json`.
- **Regel:** Der Agent führt **vor und nach jeder Arbeit** `python generate_index.py` aus, damit der Index aktuell ist.
- **Inhalt:** API-Endpunkte (Nitro-Routen aus Dateipfaden + PHP-Routen), Klassen, Funktionen, Vue-APIs, referenzierte SQL-Tabellen.
- **Nutzen:** Der Agent liest zuerst `.agent_index.json` statt das Repo zu durchsuchen (0 Tokens für die Suche).
- **Befehle:**
  - `python generate_index.py` — Index neu erzeugen
  - `python generate_index.py --stats` — zusätzlich Statistik ausgeben
  - `python generate_index.py --quiet` — ohne Konsolenausgabe

## 2. Kernrollen
- **Architekt:** [.agents/rules/martin_persona.md](file:///c:/Users/marti/Taskster/.agents/rules/martin_persona.md) (SaaS, Berechtigungen, B2B)
- **Delegation Runtime:** [.agents/rules/delegation_runtime.md](file:///c:/Users/marti/Taskster/.agents/rules/delegation_runtime.md)
- **Spezialisten (`.agents/agents/`):**
  `@agent-orchestrator` (Koordination), `@agent-designer` (UI/Nuxt), `@agent-backend` (API/DB),
  `@agent-security` (Zero-Trust), `@agent-mobile-sync` (Capacitor/Offline), `@agent-ingestion` (.msg/Audio),
  `@agent-export` (PDF/Excel), `@agent-billing` (Quotas), `@agent-qa` (Tests), `@agent-devops` (CI/CD).

## 3. Tech-Stack & Design-System
- **Stack:** Vue.js/Nuxt 3, Tailwind CSS, API-First (PHP/TypeScript), MariaDB/PostgreSQL (JSONB), Capacitor.
- **Security:** Zero-Trust serverseitig; unberechtigt = `404 Not Found` (kein Info-Leak); Company-Policy überschreibt alles.
- **Design:** Palette verbindlich aus `palette_preview.html`.
- **Buttons (Standard: `px-6 text-xs h-[42px] rounded-lg`):**
  - Primary / Save: `taskster_button` (Blau)
  - Destructive / Accent: `taskster_button_accent` (Rot)
  - Ghost / Cancel: `taskster_button_light` (Weiß + blauer 3px Rand)

## 4. Deployment & Sync (Git Only - Verbindlich)
- **Git als Single Source of Truth:** Sowohl Quellcode als auch der lokal gebaute Produktions-Build (`index.html`, `200.html`, `_nuxt/`, etc.) werden direkt in Git committed und nach `origin/main` gepusht.
- **Server-Umgebung (Hostcreators Shared-Hosting):** Der Server besitzt keinen C-Compiler (`cc`) und ein inkompatibles GLIBC für native Module (`better-sqlite3`). Daher kann auf dem Server **kein** `npm install` oder `npm run build` ausgeführt werden.
- **Verbindlicher Deployment-Ablauf:**
  1. Änderungen lokal testen.
  2. Lokaler statischer Build & Sync: `npm run build:dist` (führt `nuxt generate` aus und synchronisiert `.output/public` direkt in das Git-Root-Verzeichnis).
  3. Git Commit & Push: `git add -A; git commit -m "..."; git push origin main`.
  4. Server-Sync: Auf dem Server wird einfach `git pull origin main` ausgeführt! (Kein SFTP nötig).
  5. Bei DB-Schema-Änderungen: `node scripts/migrate-mysql.cjs` remote gegen MySQL ausführen.

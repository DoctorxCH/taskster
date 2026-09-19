# Taskster – Agenten-Architektur & Systemregeln

## 1. Wissensbasis (90er-Regel)
- **`99_anweisungen/` (Prio HOCH - Verbindlich):** Alle Spezifikationen & Schemata. Index: [00_INDEX.md](file:///c:/Users/marti/Taskster/99_anweisungen/00_INDEX.md)
- **`98_Vorschläge/` (Prio MITTEL):** Verbesserungsvorschläge (`JJJJ-MM-TT_name_prioX.md`).
- **`97_korrekturen/` (Prio DOKU):** Changelog durchgeführter Fixes (`JJJJ-MM-TT_name.md`).

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

## 4. Deployment & Sync (Verbindlich)
- **Git als Single Source of Truth:** Alle Quellcode-Änderungen werden committed und nach `origin/main` gepusht.
- **Server-Umgebung (Hostcreators Shared-Hosting):** Der Server besitzt keinen C-Compiler (`cc`) und ein inkompatibles GLIBC für native Module (`better-sqlite3`). Daher kann auf dem Server **kein** `npm install` oder `npm run build` ausgeführt werden.
- **Verbindlicher Deployment-Ablauf:**
  1. Änderungen lokal testen und committen (`git commit & push origin main`).
  2. Lokaler statischer Build: `npm run generate` (erstellt `.output/public/` mit allen Assets und `api/index.php`).
  3. Automatisches Deployment: `node scripts/deploy-sftp.cjs` synchronisiert `.output/public` direkt nach `/sub/taskster`.
  4. Bei DB-Schema-Änderungen: `node scripts/migrate-mysql.cjs` remote gegen MySQL ausführen.

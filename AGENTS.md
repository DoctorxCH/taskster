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
- **Kein SFTP-Upload mehr:** Dateien niemals direkt über SFTP auf den Server synchronisieren, da dies zu Konflikten und untracked files in Git führt.
- **Git als Single Source of Truth:** Deployments / Updates ausschließlich via `git commit & push origin main`.
- **Server-Befehle:** Falls auf dem Server `npm run ...` (z.B. Build) oder Datenbank-Migrationen ausgeführt werden müssen, den User direkt informieren, damit er dies im SSH-Terminal ausführen kann.

# Multi-Agent Delegation Runtime

- **Einstiegsrolle:** `@agent-orchestrator` koordiniert, zerlegt Anfragen und prüft Ergebnisse.
- **Workflow:** 1. Phasen-Check ([05_Phasenplan](file:///c:/Users/marti/Taskster/99_anweisungen/05_Phasenplan_und_Meilensteine.md)) -> 2. Delegation `[SWITCH ROLE: @agent-<name>]` -> 3. Scope-Review -> 4. Protokoll in `97_korrekturen/`.
- **Rollen-Registry (`.agents/agents/`):**
  `@agent-orchestrator` (Koordination), `@agent-designer` (UI/Nuxt, kein Backend), `@agent-backend` (API/DB, kein CSS), `@agent-security` (Zero-Trust/Auth), `@agent-mobile-sync` (Capacitor/Offline), `@agent-ingestion` (.msg/Audio), `@agent-export` (PDF/Excel), `@agent-billing` (Quotas/Seats), `@agent-qa` (Tests), `@agent-devops` (CI/CD/Deploy).

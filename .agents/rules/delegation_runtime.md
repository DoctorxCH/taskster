---
description: Multi-Agent Delegation Runtime für Antigravity Subagents in Taskster.
---

# Multi-Agent Delegation Runtime (Google Antigravity)

## 1. Rollen- & Delegations-Modell
- **Einstiegs- & Hauptrolle:** `orchestrator` (oder `architect`) koordiniert Anfragen, zerlegt sie in Teilaufgaben und delegiert gezielt.
- **Native Antigravity Delegation:** Subagents werden über das offizielle Tool `invoke_subagent` aufgerufen (bzw. interaktiv im Antigravity-Panel `/agents` / `Alt+J` oder per `@mention` adressiert).
- **Keine Pseudo-Befehle:** Befehle wie `[SWITCH ROLE]` sind veraltet und unzulässig. Subagents starten mit eigenem isolierten Kontextfenster und den im YAML-Frontmatter konfigurierten Tools.

## 2. Standard-Workflow
1. **Phasen- & Index-Check:**
   - Status prüfen in [99_anweisungen/05_Phasenplan_und_Meilensteine.md](file:///c:/Users/taakumao/Taskster/99_anweisungen/05_Phasenplan_und_Meilensteine.md).
   - `.agent_index.json` heranziehen (bei Bedarf `python generate_index.py` ausführen).
2. **Delegation (`invoke_subagent`):**
   - Subagent mit klar abgegrenztem Scope und relevanter Dateiliste aufrufen.
3. **Scope-Review & Boundary-Check:**
   - Orchestrator verifiziert die Änderungen auf Einhaltung der Fachgrenzen (z. B. keine DB-Eingriffe durch `designer`).
4. **Dokumentation:**
   - Signifikante Änderungen in [97_korrekturen/](file:///c:/Users/taakumao/Taskster/97_korrekturen/) erfassen.

## 3. Subagent-Registry (`.agents/agents/`)
- `orchestrator` ([orchestrator.md](file:///c:/Users/taakumao/Taskster/.agents/agents/orchestrator.md)): Hauptkoordination & Review
- `architect` ([architect.md](file:///c:/Users/taakumao/Taskster/.agents/agents/architect.md)): Martin – SaaS & Produktarchitektur, Zero-Trust
- `designer` ([designer.md](file:///c:/Users/taakumao/Taskster/.agents/agents/designer.md)): UI/UX, Vue 3, Nuxt 3, Tailwind CSS (kein Backend/DB)
- `backend` ([backend.md](file:///c:/Users/taakumao/Taskster/.agents/agents/backend.md)): Nitro/PHP REST-APIs, MariaDB/PostgreSQL, Formel-Engine (kein CSS/Templates)
- `security` ([security.md](file:///c:/Users/taakumao/Taskster/.agents/agents/security.md)): Zero-Trust 4-Stufen-Pipeline, 404-Verschleierung, Company-Policies
- `mobile-sync` ([mobile-sync.md](file:///c:/Users/taakumao/Taskster/.agents/agents/mobile-sync.md)): Capacitor Runtime, SQLite/IndexedDB Cache, Offline-Sync Queue
- `ingestion` ([ingestion.md](file:///c:/Users/taakumao/Taskster/.agents/agents/ingestion.md)): .msg/.eml Parsing, Audio-Transcoding, MIME Magic-Bytes, Hintergrund-Jobs
- `export` ([export.md](file:///c:/Users/taakumao/Taskster/.agents/agents/export.md)): PDF-Berichte mit Branding, Excel (.xlsx), Word (.docx), ZIP-Archive
- `billing` ([billing.md](file:///c:/Users/taakumao/Taskster/.agents/agents/billing.md)): Quotas, Free-Tier Limits (1 Ordner), Pro-Feature-Gates, B2B-Seats
- `qa` ([qa.md](file:///c:/Users/taakumao/Taskster/.agents/agents/qa.md)): Automatisierte Tests, 404-Verifikation, Offline-Stresstests
- `devops` ([devops.md](file:///c:/Users/taakumao/Taskster/.agents/agents/devops.md)): Git-Only Deployments, Migrationen, Capacitor Store-Builds

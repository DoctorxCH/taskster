# 06: Antigravity Multi-Agenten-System

## Antigravity Subagent-Matrix (`.agents/agents/`)
| Subagent | Datei | Aufgabe | Scope & Grenzen |
|:---|:---|:---|:---|
| `@orchestrator` | [orchestrator.md](file:///c:/Users/taakumao/Taskster/.agents/agents/orchestrator.md) | Main Coordinator & Reviewer | Analysiert Anfragen, zerlegt Aufgaben, delegiert via `invoke_subagent`, finale Abnahme. |
| `@architect` | [architect.md](file:///c:/Users/taakumao/Taskster/.agents/agents/architect.md) | SaaS & Product Architect (Martin) | B2B- & Multi-Tenant-Architektur, Berechtigungshierarchien, Domänen-Modell. |
| `@designer` | [designer.md](file:///c:/Users/taakumao/Taskster/.agents/agents/designer.md) | UI/UX & Frontend | Vue 3 / Nuxt 3, Tailwind CSS, Taskster Design-System. Keine Backend-/DB-Änderungen. |
| `@backend` | [backend.md](file:///c:/Users/taakumao/Taskster/.agents/agents/backend.md) | API & DB | Nitro/PHP REST-Schnittstellen, DB-Schema, Indizes, Migrationen. Kein Styling/CSS. |
| `@security` | [security.md](file:///c:/Users/taakumao/Taskster/.agents/agents/security.md) | Security & Auth | Zero-Trust, 4-Stufen-Pipeline, 404-Verschleierung, Mandanten-Policies. |
| `@mobile-sync` | [mobile-sync.md](file:///c:/Users/taakumao/Taskster/.agents/agents/mobile-sync.md) | Mobile & Offline | Capacitor Runtime, SQLite Cache, Offline-Queue-Sync, 2-Klick-Regel. |
| `@ingestion` | [ingestion.md](file:///c:/Users/taakumao/Taskster/.agents/agents/ingestion.md) | File & Media Processing | .msg/.eml Parsing, Audio-Encoding, MIME Magic-Bytes, asynchrone Queues. |
| `@export` | [export.md](file:///c:/Users/taakumao/Taskster/.agents/agents/export.md) | Reporting Engine | PDF (Bautagebuch), Excel (.xlsx), Word (.docx), ZIP-Generierung. Nur lesend. |
| `@billing` | [billing.md](file:///c:/Users/taakumao/Taskster/.agents/agents/billing.md) | Quotas & Licenses | Free-Tier Limits (1 Ordner), Pro-Feature-Gates, B2B-Seat-Verwaltung. |
| `@qa` | [qa.md](file:///c:/Users/taakumao/Taskster/.agents/agents/qa.md) | Testing & Validation | Unit-/Integrationstests, 404-Sicherheitsverifikation, Offline-Stresstests. |
| `@devops` | [devops.md](file:///c:/Users/taakumao/Taskster/.agents/agents/devops.md) | Release & CI/CD | Docker, DB-Migrationen, Capacitor Store-Builds, Git-Only Deployment. |

## Delegationsablauf in Google Antigravity
1. **Erkennung & Planung:**
   - Der Hauptagent (oder `@orchestrator`) zerlegt eine komplexe Anforderung in Teilaufgaben.
2. **Native Delegation (`invoke_subagent`):**
   - Subagents werden über das native Werkzeug `invoke_subagent` mit isoliertem Kontext aufgerufen.
   - Alternativ kann der Benutzer jederzeit im Antigravity-Panel (`Alt+J` oder `/agents`) direkt einen Subagenten auswählen oder per `@<name>` ansprechen.
3. **Typische Ausführungssequenz:**
   - `backend` / `ingestion` (API & Datenstrukturen) → `designer` (Frontend & UI-Komponenten) → `security` / `qa` (Rechteprüfung & Tests).
4. **Endabnahme:**
   - Review durch den `orchestrator` auf strikte Einhaltung der Grenzen und saubere Code-Struktur.

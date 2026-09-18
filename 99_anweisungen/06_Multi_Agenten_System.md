# 06: Multi-Agenten-System

## Rollen-Matrix
| Agent | Aufgabe | Scope & Grenzen |
|:---|:---|:---|
| `@agent-orchestrator` | Main Coordinator | Analysiert Anfragen, steuert Sequenz, finale Abnahme. |
| `@agent-designer` | UI/UX & Frontend | Vue.js/Nuxt, Tailwind CSS. Keine Backend-/DB-Änderungen. |
| `@agent-backend` | API & DB | REST/JSON-Schnittstellen, DB-Schema, Indizes. Kein Styling. |
| `@agent-security` | Security & Auth | Zero-Trust, 4-Stufen-Pipeline, Mandanten-Policies. |
| `@agent-mobile-sync` | Mobile & Offline | Capacitor, SQLite Cache, Offline-Queue-Sync. |
| `@agent-ingestion` | File Processing | .msg/.eml Parsing, Audio-Encoding, asynchrone Queues. |
| `@agent-export` | Reporting Engine | PDF (Bautagebuch), Excel, Word, ZIP-Generierung. |
| `@agent-billing` | Quotas & Licenses | Free-Tier Limits, Mandanten-Pläne, Rechnungslogik. |
| `@agent-qa` | Testing | Unit-/Integrationstests, Simulation von Offline-Zuständen. |
| `@agent-devops` | Release & CI/CD | Docker, DB-Migrationen, App-Store-Builds. |

## Delegationsablauf
1. **Klassifikation:** Orchestrator identifiziert Domäne und prüft Abhängigkeiten.
2. **Sequenz:** Backend/Ingestion (API/DB) → Designer (UI) → Security (Policies/Rechte).
3. **Endabnahme:** Orchestrator verifiziert Gesamtsystem vor Ausgabe.




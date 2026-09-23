# Taskster – Projektübersicht

Diese Übersicht fasst die wichtigsten Aspekte der Taskster-Plattform zusammen, basierend auf der Master-Spezifikation und den Architektur-Richtlinien.

## 1. Was ist Taskster?
Taskster ist eine Enterprise Projekt- und Bauleitermanagement-Lösung. Sie bietet eine robuste Mandantenfähigkeit (SaaS, B2B) sowie eine durchdachte Cross-Platform Mobile-Strategie. Die Applikation ist darauf ausgelegt, sensible Daten durch strikte Berechtigungsregeln und Ressourcenverschleierung zu schützen.

## 2. Tech-Stack & Deployment
- **Frontend:** Vue.js / Nuxt 3
- **Styling:** Tailwind CSS (mit einem verbindlichen, eigenen Design-System, Primärfarbe `#00A3C4`)
- **Backend/API:** API-First Architektur (PHP für bestimmte Routen / TypeScript Nitro)
- **Datenbank:** MariaDB / PostgreSQL (Nutzung von JSONB-Feldern z.B. für Custom Data in Aufgaben)
- **Mobile/Offline:** Cross-Platform App für iOS & Android via Capacitor 6 mit lokalem SQLite-Cache.
- **Deployment:** Git-Only Workflow. Der Build (`npm run build:dist`) wird lokal erstellt und ins Git eingecheckt, da auf dem Produktionsserver (Shared-Hosting) kein `npm install` ausgeführt werden kann. Updates erfolgen auf dem Server über `git pull`.

## 3. Architektur & Zero-Trust
Ein zentrales Konzept ist die **Ressourcenverschleierung**: Objekte, auf die ein Nutzer keinen Zugriff hat, liefern ein `404 Not Found` (niemals `403`), um deren Existenz zu verbergen.

**Hierarchie:**
`Company` → `project_folders` → `projects` → `lists` (`inherit` | `custom`) → `tasks` (`custom_data` JSON) + `sub_tasks`.

**4-Stufen Zero-Trust Berechtigungsarchitektur:**
1. **Company Policy Check:** Prüft globale Unternehmensregeln (z.B. Upload-Sperren). Bei Verstoß erfolgt ein `403 Forbidden`.
2. **Project Membership Check:** Prüft Ordnerinhaberschaft oder Projektzugehörigkeit. Nicht autorisiert: `404 Not Found`.
3. **List Scope Check:** Bei Listen mit `access_mode == 'custom'` müssen Nutzer Owner sein oder explizite Sichtbarkeit (`list_access`) besitzen. Sonst: `404 Not Found`.
4. **Role Action Check:** "Viewer" dürfen ausschließlich lesen (`GET`). Schreibaktionen (`POST`, `PUT`, `DELETE`) werden serverseitig mit `403 Forbidden` blockiert.

## 4. Datenbankstruktur (11 Kern-Tabellen)
1. **`companies`**: Mandanten & globale Policies.
2. **`users`**: Benutzerkonten & Rechte.
3. **`project_folders`**: Oberste Ordnerebene (Free-Plan: max. 1 Ordner).
4. **`folder_field_definitions`**: Dynamische Custom Fields pro Ordner (inkl. Formeln, Logik).
5. **`projects`**: Ausführungseinheiten.
6. **`project_members`**: Projektmitgliedschaft & Rollen (Editor / Viewer).
7. **`lists`**: Aufgabenlisten.
8. **`list_access`**: Sichtbarkeit für `custom`-Listen.
9. **`tasks`**: Aufgabenobjekte (mit `custom_data` für dynamische Felder).
10. **`project_journals`**: Protokolle (Systemevents, Mails, Voice).
11. **`project_documents`**: Dokumente und Anhänge.

## 5. Meilensteine & Phasenplan
- **M1:** Auth & Mandanten (Companies, Users, Rollen, Login)
- **M2:** Projekte & Listen (Zero-Trust, inherit/custom, Viewer-Schutz)
- **M3:** Dynamische Felder (Custom Fields, Formeln, reaktive Logik)
- **M4:** Journal & Ingestion (Outlook .msg Parser, Voice Memos)
- **M5:** Zeit & Export (Timer, Budget, PDF/Excel-Generator)
- **M6:** Mobile & Offline (Capacitor, lokaler SQLite Cache)

## 6. Multi-Agenten-System & KI
Die Entwicklung wird durch spezialisierte Antigravity-Subagents (z.B. `@orchestrator`, `@architect`, `@designer`, `@backend`, `@security`) unterstützt.
- **Wissensbasis:** Gespeichert im Ordner `99_anweisungen/` (Verbindlich).
- **Code-Index:** Ein fortlaufend aktualisierter Index der Codebase (`.agent_index.json`), der mit `python generate_index.py` neu generiert wird.
- **Provider:** OpenRouter / DeepSeek V4 Flash über das Skript `scripts/ai.cjs`.
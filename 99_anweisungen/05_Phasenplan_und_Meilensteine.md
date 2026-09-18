# 05: Phasenplan & Meilensteine

- **M1: Basis, Auth & Mandanten:** Tabellen `companies`, `users`; JWT Auth, Middleware; Vue/Nuxt Auth-Flow. *DoD:* User registriert/eingeloggt, Company zugeordnet, Settings konfigurierbar.
- **M2: Projekt- & Listenstruktur:** Tabellen `project_folders`, `projects`, `project_members`, `lists`, `list_access`; Free-Limit (1 Ordner), Rollen (Owner/Editor/Viewer), Listenzugriff (404 bei Verstoß). *DoD:* Unberechtigte sehen `custom`-Listen nicht (`404`), Viewer schreibgeschützt.
- **M3: Custom Fields & Logik:** Tabellen `folder_field_definitions`, `tasks.custom_data`; CRUD für Felder, Formelberechnung, dynamische Sichtbarkeit. *DoD:* Abhängige Felder reagieren verzögerungsfrei, Formeln berechnen automatisch.
- **M4: Journal, E-Mail & Voice:** Tabellen `project_journals`, `project_documents`; Compliance-Upload-Sperre, .msg/.eml Drag & Drop Parser, Audio Recorder (.opus/.m4a) + Player. *DoD:* Mail generiert Journaleintrag mit getrennten Anhängen, Voice Memo per Klick abhörbar.
- **M5: Zeiterfassung & Exporte:** 1-Klick-Timer, Stundensätze, Budget Soll/Ist; Exporte: PDF (Baustellenjournal), Excel (.xlsx), Word (.docx), ZIP. *DoD:* Zeiten kalkulieren Projektbudget, Exporte vollständig formatiert.
- **M6: Mobile & Offline:** Capacitor Integration (iOS/Android), lokaler SQLite-Cache, Queue-Synchronisation bei Reconnect. *DoD:* Vollständige App offline bedienbar, synchronisiert konfliktfrei online.

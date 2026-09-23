# 02: Technische Vorgaben & Coding Guidelines

1. **Stack:** Backend API-First (REST/JSON), Frontend Nuxt 3/Vue + Tailwind CSS, Mobile Capacitor, DB MariaDB/PostgreSQL (JSONB).
2. **Security & Zero-Trust:** Serverseitige Autorisierung; nicht berechtigte Ressourcen liefern `404 Not Found` (kein Info-Leak); Company-Policy hat Vorrang vor Projekt-/Feldeinstellungen; lokaler SQLite-Cache enthält nur berechtigte Nutzerdaten.
3. **Dateihandling:** Parsing (.msg/.eml) und Audiotranskodierung ausschließlich über asynchrone Jobs/Queues. Kopfdaten, Body und Anhänge atomar trennen. Strikte serverseitige MIME-Type-Prüfung.
4. **UI/UX:** Viewer erhalten aufgeräumte Read-Only-Ansicht (keine Dummy-Buttons). Reaktive Feldlogik ohne spürbare Latenz. Vor-Ort-Aktionen (Timer, Foto, Voice) innerhalb von max. 2 Klicks.
5. **Code-Index & Suche (Token-Disziplin):** Vor und nach jeder Arbeit `python generate_index.py` ausführen. Zur Lokalisierung von Endpunkten, Tabellen, Composables oder Funktionen **immer zuerst `.agent_index.json`** lesen. Grep/Repo-Suche ist erst als Fallback erlaubt, falls das Element nicht im Index steht.

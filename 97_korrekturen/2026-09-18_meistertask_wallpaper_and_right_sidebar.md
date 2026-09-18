# Korrektur- & Feature-Log: MeisterTask Wallpaper Hintergrund, Hero-Dashboard & Rechte Sidebar

- **Datum:** 2026-09-18
- **Anforderung:** Anlehnung an MeisterTask Design-Referenz (Screenshot):
  1. Dynamische Hintergrund-Wallpapers über den gesamten Viewport.
  2. MeisterTask Hero-Bereich (deutsches Wochentags-/Monatsdatum, motivierende Begrüßung `Sich anstrengen, Martin 🫡`, zentrierte Pill-Suchleiste).
  3. Schwebende, weiße Glasmorphismus-Widgets für `Aufgaben` (mit Counter & Quick Actions), `Projektordner & Initiativen` (mit Icon & Inhaber) sowie `Benachrichtigungen` (mit Filter-Tabs `Alle`, `Erwähnungen`, `Projekte`).
  4. **Sidebar zwingend rechts statt links** platziert: Schlanke Quick-Action-Rail auf der rechten Fensterseite (`border-l`, expandable mit Tooltips).
  5. Integrierter MeisterTask-Wallpaper-Picker (über Button `🎨 Anpassen` in der Navbar sowie in der rechten Sidebar) mit 15 Motiven und lokaler Speicherung.

- **Geänderte / Neu erstellte Dateien:**
  - `composables/useWallpaper.ts`: 15 Themen-Motive katalogisiert, reaktiver State & `localStorage`-Persistenz (`taskster_wallpaper`).
  - `app.vue`:
    - Fester Full-Screen-Wallpaper-Layer (`object-cover`, optimierte Brightness & sanfter Kontrast-Filter).
    - Rechte Navigations-Leiste (Quick Rail) mit Icons, Tooltips und Profilanzeige.
    - MeisterTask Wallpaper-Picker Modal mit Live-Vorschau aller 15 Motive.
  - `components/Navbar.vue`: MeisterTask `🎨 Anpassen` Button hinzugefügt.
  - `pages/dashboard.vue`:
    - Zentrierter Hero mit aktuellem Tagesdatum (`Freitag, 18. September`), dynamischer Begrüßung (`Sich anstrengen, {{ user?.name }} 🫡`) und MeisterTask-Suchfeld (`Strg + K`).
    - 2-Spalten-Widget-Layout mit schwebenden weißen MeisterTask-Karten für Aufgaben, Projektordner und Benachrichtigungen.
  - `server/api/tasks/index.get.ts`: Neuer Endpoint zum Abruf relevanter Aufgaben für das Dashboard-Widget.

- **Verifikation & Deployment:**
  - `npx nuxi generate`: Erfolgreich ohne Fehler generiert.
  - `deploy-sftp.cjs`: Vollständiger Sync auf Server `/sub/taskster` durchgeführt.
  - Git: Commit & Push nach `origin/main` (`e21bb50`).

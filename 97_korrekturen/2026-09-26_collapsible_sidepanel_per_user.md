# Dokumentation: Ausklappbares Sidepanel (Icon-Rail & Maximale Arbeitsfläche) mit User-Persistenz

**Datum:** 2026-09-26  
**Bereich:** Navigation / Layout (`app.vue`, `userSettings.ts`, `api/index.php`, i18n)  
**Typ:** UI/UX Upgrade & Backend Sync

## 1. Problemstellung & Anforderung
- Das linke Haupt-Sidepanel (`<aside>` in `app.vue`) war fest auf `w-60` (240px) fixiert.
- Der Nutzer wünschte eine Minimierung auf reine Symbole (Icon-Rail), sodass die Hauptarbeitsfläche vergrössert wird.
- Der Zustand (eingeklappt / ausgeklappt) muss individuell pro Benutzer gespeichert und wiederhergestellt werden (auch geräte- bzw. sitzungsübergreifend).

## 2. Technische Umsetzung

### A. Frontend (`app.vue`)
1. **Collapsible Aside & Breitenanimation:**
   - Standard: `w-60` (240px).
   - Minimiert: `w-16` (64px, zentrierte Ausrichtung).
   - CSS-Transition: `transition-all duration-300 ease-in-out` für flüssiges Ein- und Ausgleiten ohne Ruckeln.
2. **Toggle-Button im Header des Sidepanels:**
   - Button mit `PanelLeftClose` (bei ausgeklappt) bzw. `PanelLeftOpen` (bei eingeklappt).
   - Tooltip mit dynamischem i18n-Key (`app.sidebar_einklappen` / `app.sidebar_ausklappen`).
   - Im minimierten Zustand wird der Button zentriert dargestellt und das Taskster-Logo ausgeblendet.
3. **Kompaktmodus für Navigation & Widgets:**
   - Navigationslinks wechseln im minimierten Zustand von `justify-start px-3` zu `justify-center px-0 w-10 h-10 mx-auto rounded-lg`.
   - Tooltip (`title="..."`) an allen Links für barrierefreie Nutzbarkeit im Icon-Modus.
   - Admin-Submenü wird vertikal als kompakte Mini-Icon-Buttons (32x32) zentriert angezeigt.
   - Der MiniCalendar blendet sich im minimierten Zustand sauber aus und bietet stattdessen ein zentriertes Kalender-Icon mit Datums-Tooltip und Klick-Event zum Wiederaufklappen.
   - Desktop-Wallpaper Trigger passt sich mit dezentem Bild-Icon nahtlos an.

### B. Zweistufige Benutzer-Persistenz (Zero Layout Shift)
1. **Lokaler Client-Cache (Layer 1):**
   - Beim Mounten (`onMounted`) und bei User-Wechsel (`watch(user)`) wird `taskster_sidebar_collapsed_${uid}` aus `localStorage` gelesen.
   - Verhindert jegliches Flackern oder FOUC (Flash of Unstyled Content) vor Eintreffen der Serverdaten.
2. **Server- & Profil-Persistenz (Layer 2):**
   - Beim Betätigen des Toggles wird der Zustand an `PATCH /api/auth/profile` mit `{ settings: { ...settings, sidebar_collapsed } }` übermittelt.
   - DB-Schema & Fallbacks aktualisiert in:
     - `server/utils/userSettings.ts` (`UserSettings.sidebar_collapsed: boolean`)
     - `api/index.php` (`defaultUserSettings()` & `normalizeUserSettings()`)

### C. Lokalisierung (i18n)
- Neue Schlüssel hinzugefügt in `de.json`, `en.json`, `sk.json`:
  - `app.navigation`
  - `app.sidebar_einklappen`
  - `app.sidebar_ausklappen`
- Vollständige 100% Key-Parität über alle 3 Sprachdateien (2'893 Keys jeweils).

## 3. Betroffene Dateien
- `app.vue`
- `server/utils/userSettings.ts`
- `api/index.php`
- `i18n/locales/de.json`
- `i18n/locales/en.json`
- `i18n/locales/sk.json`

# Fix: Sprachwahl Persistenz (SK/EN) und flächendeckende Übersetzung

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `server/utils/userSettings.ts`
  - `api/index.php`, `public/api/index.php`, `server-php/index.php`
  - `i18n/locales/de.json`, `en.json`, `sk.json`
  - `components/Navbar.vue`
  - `pages/settings.vue`
  - `pages/login.vue`
  - `pages/dashboard.vue`
  - `app.vue`

## 1. Problembeschreibung
1. **Rückstellung auf Deutsch beim Speichern:**
   - Der Benutzer wählt Slowakisch (`sk`) oder Englisch (`en`). Die Seite wechselte clientseitig sofort auf die gewählte Sprache.
   - Nach einem Klick auf "Speichern" sprang die Sprache jedoch sofort wieder auf Deutsch (`de`) zurück.
2. **Unvollständige Übersetzung:**
   - Zahlreiche UI-Bereiche (Navbar-Badges, Benachrichtigungs-Dropdown, Admin-Submenü, Wallpaper-Modal, Dashboard-Begrüssung, dynamisches Datum, Suchfeld, Tabs und Schnellaktionen) enthielten noch fest verdrahtete deutsche Strings statt `$t(...)`-Bindings.

## 2. Ursachenanalyse
1. **Backend Whitelist Filter:**
   - In `server/utils/userSettings.ts` und in `api/index.php` (sowie dessen Kopien `public/api/index.php` und `server-php/index.php`) wurde `language` mittels `pickEnum($raw['language'], ['de', 'en'], 'de')` bzw. `UserSettings['language'] = 'de' | 'en'` validiert.
   - Wurde `sk` gesendet, verwarf das Backend den Wert und setzte ihn auf `de` zurück.
   - Die Serverantwort lieferte `{ user: { settings: { language: 'de' } } }`, wodurch `pages/settings.vue` beim Speichern `await setLocale(res.user.settings.language)` ausführte und die UI wieder auf Deutsch zurücksetzte.
2. **Whisper Language:**
   - `whisper_language` fehlte im Typ und in den Backend-Defaults von PHP/TypeScript.
3. **Hardcoded UI-Texte im Dashboard & Layouts:**
   - `pages/dashboard.vue`, `components/Navbar.vue`, `app.vue` und `pages/login.vue` hatten hardcoded deutsche Strings, sodass selbst bei aktivem `sk` oder `en` viele Elemente unverändert deutsch blieben.

## 3. Durchgeführte Änderungen
1. **Backend Persistenz-Reparatur:**
   - `server/utils/userSettings.ts`: `'sk'` zu `UserSettings['language']` (`'de' | 'en' | 'sk'`) hinzugefügt. `whisper_language` hinzugefügt.
   - `api/index.php`, `public/api/index.php`, `server-php/index.php`: Synchron `pickEnum($raw['language'] ?? null, ['de', 'en', 'sk'], 'de')` und `whisper_language` in `defaultUserSettings()` und `normalizeUserSettings()` aktualisiert.
2. **Wörterbücher erweitert:**
   - `i18n/locales/de.json`, `en.json`, `sk.json`: 59 neue Keys ergänzt (Navbar, Admin-Submenü, Wallpaper-Modal, Dashboard-Begrüssungen, Datum, Suchfeld, Tabs, Todos, Systemstatus, Zeitrapporte).
3. **Frontend Lokalisierung & Reaktivität:**
   - `pages/dashboard.vue`: Begrüssung (`greetingPrefix`), Datumsformatierung (`formattedDate`), Such-Leiste, Free-Plan-Hinweis, Aufgaben-Tabs ("Mein Tag" / "Projekte"), Schnell-Aktionen, Benachrichtigungs-Feed und System-Status auf `$t(...)` umgestellt.
   - `components/Navbar.vue`: Benachrichtigungs-Dropdown, Ungelesen-Tooltip, Live-Stoppuhr und Menü-Button lokalisiert.
   - `app.vue`: Admin-Submenü (Mobile), Wallpaper Picker Modal lokalisiert; `syncUserLanguage()` beim Start und im User-Watcher sichergestellt.
   - `pages/settings.vue`: Whisper-Sprachauswahl und Rollenbezeichnungen lokalisiert; Reaktivität bei Sprachwechsel und Speichern abgesichert.
   - `pages/login.vue`: Titel, Tabs, Demo-Logins lokalisiert und automatischen Locale-Wechsel bei Login/Registrierung aktiviert.
4. **Codebase-Indexierung & Build:**
   - `python generate_index.py --stats` ausgeführt.
   - `npm run build:dist` (`nuxt generate && node scripts/sync-dist.cjs`) gebaut und synchronisiert.

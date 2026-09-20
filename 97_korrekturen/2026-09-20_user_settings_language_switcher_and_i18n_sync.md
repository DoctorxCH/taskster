# Fix: Sprachauswahl nur in Benutzereinstellungen & Nuxt i18n Reaktivität

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `components/Navbar.vue`
  - `pages/settings.vue`
  - `app.vue`
  - `i18n/locales/de.json`, `en.json`, `sk.json`

## Anforderung
1. Keine Sprachauswahl mehr oben in der Navbar / Header.
2. Sprachauswahl ausschließlich in den Benutzer-Einstellungen (`/settings`).
3. Behebung des Problems, dass die Sprachauswahl (z. B. auf Englisch) keine Auswirkung hatte ("triggern nicht").

## Ursachenanalyse
1. In `components/Navbar.vue` war ein `<select v-model="$i18n.locale">` eingebaut.
2. In `pages/settings.vue` war die Sprachauswahl jedoch an `settings.language` gebunden, ohne jegliche Verbindung zu `@nuxtjs/i18n`:
   - Es wurde weder `setLocale()` aufgerufen, noch `$i18n.locale` aktualisiert.
   - Beim Neuladen oder Login (`initAuth()`) wurde die gespeicherte Benutzersprache nicht an Nuxt i18n übergeben.
   - In den Optionen waren `fr` und `it` aufgeführt, für die es gar keine Locale-Dateien gab, während `sk` (Slovenčina) fehlte.
3. Die UI-Komponenten (Sidebar, Navbar, Settings) hatten fest verdrahtete deutsche Strings statt `$t('...')`-Bindings, weshalb bei einem Locale-Wechsel rein optisch keine Textänderung sichtbar war.

## Durchgeführte Änderungen
1. **`components/Navbar.vue`:**
   - Dropdown für Sprachauswahl oben komplett entfernt.
   - Benutzer- und Abmelde-Aktionen auf `$t('common.mein_profil')` und `$t('common.abmelden')` umgestellt.
2. **`pages/settings.vue`:**
   - `const { t, setLocale } = useI18n()` eingebunden.
   - Sprach-Select auf verfügbare Sprachen bereinigt: `de` (Deutsch), `en` (English), `sk` (Slovenčina).
   - `@change="onLanguageChange"` triggert sofort `setLocale(settings.language)`.
   - Beim Laden (`applyUser()`) und Speichern (`saveAll()`) wird die Benutzersprache ebenfalls synchronisiert.
   - Header, Breadcrumb, Tabs und Ungespeichert-Hinweise nutzen `$t(...)`.
3. **`app.vue`:**
   - Desktop-Sidebar und Mobile Drawer Links nutzen jetzt `$t('common...')` (Dashboard, Zeitrapporte, Kontakte, Kalender, Administration, Einstellungen, Hintergrund).
   - Beim App-Start nach `initAuth()` synchronisiert `syncUserLanguage()` die gespeicherte Sprache des Benutzers automatisch mit `setLocale()`.
4. **Locale Dictionaries (`de.json`, `en.json`, `sk.json`):**
   - Gemeinsame Navigations- und Einstellungs-Keys (`common.dashboard`, `common.zeitrapporte`, `common.kontakte`, `common.kalender`, `common.administration`, `common.einstellungen`, `common.mein_profil`, `common.abmelden`, etc.) hinterlegt.

# Fix: UI-Verschwinden beim Ändern der Sprache in den Einstellungen

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `i18n/locales/de.json`
  - `i18n/locales/en.json`
  - `i18n/locales/sk.json`
  - `pages/settings.vue`

## Problembeschreibung
Beim Umschalten der Benutzersprache in `/settings` (z. B. auf Slowakisch `sk`) verschwand der gesamte Inhalt der Seite (`<NuxtPage />` wurde unmounted). Zurück blieb nur das Hintergrundbild und die statische Navigation. Der Speichern-Button war nicht mehr erreichbar.

## Ursachenanalyse
1. **Vue-i18n Compiler-Fehler bei `@` (Linked Message Syntax):**
   - In Vue-i18n leitet ein unescapetes `@` eine Linked Message ein (`@:path`).
   - Die Benachrichtigungseinstellung `settings.jemand_erwähnt_dich_mit_name` enthielt `@meno` (SK) bzw. `@Name` (DE) / `@name` (EN).
   - Beim Umschalten auf `sk` evaluierte das `computed`-Property `eventOptions` die Übersetzung `t('settings.jemand_erwähnt_dich_mit_name')`.
   - Der `@intlify/message-compiler` warf einen `SyntaxError: Message compilation error: Invalid linked format`.
   - Dieser unbehandelte Render-Fehler führte zum Absturz und Unmounten der Vue-Komponente `pages/settings.vue`.
2. **Weitere unescapete `@` und ungültige `${...}`-Tokens:**
   - Auch `contacts.beispiel_hans_peter_bauleiter_bei_s` und `projects.beispiel_hans_peter_bauleiter_bei_s` enthielten unescapete E-Mail-Adressen mit `@`.
   - 17 extrahierte Strings enthielten ungültige JS-Template-Syntax wie `${newUserForm.value.name}` oder verschachtelte Ausdrücke.
3. **UTF-8 BOM:**
   - `de.json`, `en.json` und `sk.json` enthielten ein UTF-8 BOM (`\xef\xbb\xbf`), was bei strikten JSON-Parsern Fehler verursachen kann.

## Durchgeführte Änderungen
1. **Locale-Dateien bereinigt (`de.json`, `en.json`, `sk.json`):**
   - Literal-`@` mit `{'@'}` escaped (z. B. `{'@'}meno`, `{'@'}Name`, `h.peter{'@'}steiner.ch`).
   - Alle ungültigen `${...}`-Tokens durch valide Vue-i18n-Platzhalter `{name}`, `{email}`, etc. ersetzt.
   - UTF-8 BOM aus allen JSON-Dateien entfernt.
   - Mit Testskript verifiziert: 0 Fehler / Crashes in allen 3 Sprachen.
2. **`pages/settings.vue`:**
   - `setLocale()`-Aufrufe in `onLanguageChange()`, `applyUser()` und `saveAll()` mit `try/catch` abgesichert, damit Sprachumschaltungen selbst bei unerwarteten Fehlern niemals das UI unmounten.

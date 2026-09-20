# Korrektur: Admin E-Mail-Einstellungen & Resend API Integration

- **Datum:** 2026-09-20
- **Betroffene Komponenten:** `pages/admin/index.vue`, `pages/settings.vue`, `nuxt.config.ts`, `server/api/admin/email-settings.*`, `server/api/admin/email-test.post.ts`, `server/utils/mailer.ts`, `server-php/index.php`, `public/api/index.php`, `api/index.php`

## Änderungen
1. **Admin-Bereich (`pages/admin/index.vue`):**
   - Tab "E-Mail & Benachrichtigungen" mit Sub-Tabs für Einstellungen, Trigger-Vorlagen und Versand-Protokoll.
   - Provider-Auswahlkarten: Resend API (Empfohlen & Aktiv) vs. Eigener SMTP-Server.
   - Konfigurationsfelder für Resend API-Key (mit Show/Hide-Toggle), Absender-Name und Absender-E-Mail (`noreply@kurka.ch`).
   - Domain-Status-Badge "kurka.ch verifiziert".
   - Test-E-Mail Modal mit Provider-Anzeige (Resend API oder SMTP).

2. **Backend (Nitro & PHP):**
   - `server/api/admin/email-settings.get.ts`: Rückgabe von `settings` und `config` für vollständige Kompatibilität.
   - `server/api/admin/email-test.post.ts`: Unterstützung für `custom_config`, Resend API-Key und dynamische Vorlagen.
   - `server/utils/mailer.ts`: Absender-Adresse und -Name aus Admin-Einstellungen übernehmbar, Resend API-Unterstützung.
   - `server-php/index.php`, `public/api/index.php`, `api/index.php`: 100% synchronisiert mit `sendResendEmailNative` und erweitertem From-Handling.

3. **Prerender & Build-Fix (`pages/settings.vue`, `nuxt.config.ts`):**
   - Icon-Import `Settings` als `SettingsIcon` umbenannt, um Rekursion des Vue-Komponentennamens `settings.vue` zu verhindern.
   - `routeRules: { '/settings': { ssr: false } }` in `nuxt.config.ts` ergänzt.
   - `npm run build:dist` erfolgreich durchgelaufen (Exit 0) und nach Git-Root synchronisiert.

# Korrektur: Dedizierte Absender-Identitäten (@kurka.ch) via Resend API

- **Datum:** 2026-09-20
- **Betroffene Komponenten:** `server/utils/mailer.ts`, `server/utils/calendar.ts`, `server-php/index.php`, `public/api/index.php`, `api/index.php`, `pages/admin/index.vue`, `server/api/admin/email-test.post.ts`

## Implementierte Identitäten
1. `Taskster <hey@kurka.ch>`: Onboarding, Willkommensnachrichten, direkte Ansprache (Reply-To: `support@kurka.ch`).
2. `Taskster <updates@kurka.ch>`: Changelogs, Produkt-News, Newsletter.
3. `Taskster <team@kurka.ch>`: Kollaborations-Ereignisse (Zuweisungen, Erwähnungen, Kommentare, Kalendereinladungen).
4. `Taskster <notify@kurka.ch>`: Allgemeine Benachrichtigungen, Fristen, Statusänderungen, Erinnerungen.
5. `Taskster <system@kurka.ch>`: Technische Transaktionsmails (Passwort-Resets, Sicherheitswarnungen, Account-Änderungen).

## Durchgeführte Arbeiten
- **Routing-Funktion:** `getSenderForTrigger` in TypeScript und `getEmailSenderForPurpose` in PHP ordnen Trigger-Events automatisch der passenden Absenderadresse zu.
- **Reply-To:** Bei `hey@kurka.ch` wird automatisch `reply_to: support@kurka.ch` übergeben.
- **Admin-UI:**
  - Übersicht der 5 Identitäten mit Zweckbeschreibung und direktem "Testen"-Button im E-Mail-Einstellungen-Tab.
  - Test-E-Mail Modal um Dropdown für die Absenderadresse erweitert.
  - Trigger-Vorlagen zeigen die zugehörige Absender-Adresse als Badge an.
- **Live-Verifikation:**
  - `hey@kurka.ch` (mit Reply-To `support@kurka.ch`) und `team@kurka.ch` erfolgreich an `martinkurka@outlook.com` via Resend API versendet (Resend IDs: `01a0c056-78e7-75d4-ba50-6d856141d229`, `01a0c056-7a20-74ef-85c0-95f315a1fb96`).

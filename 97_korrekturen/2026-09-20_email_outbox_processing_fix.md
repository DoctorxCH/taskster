# E-Mail Outbox Processing Fix

**Datum:** 2026-09-20
**Betreff:** Behebung des Problems, dass Kalendereinladungen (und andere Outbox-E-Mails) nicht versendet wurden.

## Ursache
Die Funktion `queueEmail` (in PHP und Nuxt) hat E-Mails lediglich in die Tabelle `email_outbox` mit dem Status 'pending' geschrieben. Da es auf dem Shared-Hosting Server keinen aktiven Cron-Job gab, der diese Tabelle abarbeitet, blieben die E-Mails dauerhaft in der Outbox stecken und wurden nie versendet.

## Lösung
1. **PHP (api/index.php & server-php/index.php & public/api/index.php):**
   - Funktion `processEmailOutbox()` hinzugefügt, welche die outbox (Limit 10) liest und über `sendSmtpEmailNative` versendet.
   - `jsonResponse()` angepasst, sodass am Ende jedes API-Aufrufs `fastcgi_finish_request()` aufgerufen wird (sofern verfügbar). Dies schliesst die Verbindung zum Client ohne Verzögerung.
   - Im Anschluss wird opportunistisch `processEmailOutbox()` im Hintergrund ausgeführt.
   - `sendSmtpEmailNative` wurde um einen optionalen Parameter `` erweitert, um Duplikate beim Einfügen zu verhindern, wenn eine bereits queued Mail versendet wird.
2. **Nuxt (server/utils/calendar.ts):**
   - `queueEmail` sendet E-Mails nun asynchron im Hintergrund über `sendSmtpEmail` und aktualisiert den DB-Status auf 'sent'/'error', ohne den Request zu blockieren.
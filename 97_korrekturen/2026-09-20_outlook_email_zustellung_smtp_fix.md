# Outlook / EOP E-Mail-Zustellung & SMTP-Konformität Fix

**Datum:** 2026-09-20  
**Betroffenes System:** SMTP E-Mail-Versand (`server-php/index.php`, `server/utils/mailer.ts`, `server/utils/calendar.ts`)  
**Status:** Behoben  

---

### Ursachenanalyse: Warum empfing Outlook (`daniela.kurka@outlook.com`) keine E-Mails?

1. **Ungültiges HELO/EHLO (`EHLO taskster.ch`):**
   - Die Domain `taskster.ch` besitzt keine DNS-Einträge (NXDOMAIN).
   - Microsoft Exchange Online Protection (EOP) und SmartScreen verwerfen Nachrichten sofort oder stufen sie mit maximalem Spam-Score ein, wenn der HELO/EHLO-Domainname im DNS nicht auflösbar ist (Anti-Spoofing / RFC 5321).
   - Gmail war hier toleranter und stellte die Nachricht trotzdem zu, da SPF für `kurka.ch` positiv war.

2. **Fehlerhafte Message-ID Domain:**
   - In PHP wurde `parse_url('noreply@kurka.ch', PHP_URL_HOST)` aufgerufen. Da E-Mail-Adressen kein URL-Schema besitzen, lieferte dies `null` und fiel auf `@taskster.ch` zurück.
   - Microsoft prüft die Domain in `Message-ID: <...@domain>`. Ungültige/unauflösbare Domains triggern DMARC- und Spoofing-Schutzfilter.

3. **MIME-Struktur bei Termineinladungen (RFC 6047 / iMIP):**
   - Einladungen wurden bisher als `multipart/mixed` mit `text/calendar` als Dateianhang (`Content-Disposition: attachment; filename="invite.ics"`) versendet.
   - Microsoft Exchange Online Protection (EOP / Outlook.com) blockiert oder verwirft externe `.ics`-Dateianhänge mit `method=REQUEST` oft als potenziell bösartige/nicht-autorisierte Kalender-Payloads, wenn kein DKIM vorhanden ist.
   - **RFC 6047 iMIP-Standard:** Eine interaktive Kalendereinladung darf **nicht** als Anhang in `multipart/mixed` verpackt sein, sondern muss als direkter Bestandteil einer `multipart/alternative` Nachricht (zusammen mit `text/plain` und `text/html`) übertragen werden. Zudem muss der Header `Content-Class: urn:content-classes:calendarmessage` gesetzt sein.

4. **iCalendar UID-Format:**
   - Die UID war als `UID: <id>@taskster` definiert (ohne TLD). Nach RFC 5545 muss die UID eine gültige Domain/FQDN enthalten (`<id>@kurka.ch`).

---

### Durchgeführte Änderungen

1. **`server-php/index.php`:**
   - Extraktion der Absender-Domain aus `$fromEmail` (Default: `kurka.ch`).
   - EHLO verwendet nun `$ehloDomain = !empty($host) ? $host : $fromDomain;` (z. B. `mail.kurka.ch`).
   - Header ergänzt: `Message-ID: <hash@domain>`, `Reply-To`, `Auto-Submitted: auto-generated`, `X-Mailer: Taskster` und `Content-Class: urn:content-classes:calendarmessage`.
   - **RFC 6047 Re-Architektur:** `multipart/alternative` als oberste Struktur bei Kalendereinladungen. `text/calendar` wird darin direkt als dritter Part eingebettet (ohne `Content-Disposition: attachment`).
   - UID in `buildIcs()`: Verwendet nun `@{$icsDomain}`.

2. **`server/utils/mailer.ts` & `server/utils/calendar.ts`:**
   - Gleiche Anpassungen für den Nitro-Entwicklungsserver implementiert.

3. **Synchronisation & Live-Deployment:**
   - `public/api/index.php` und `api/index.php` mit `server-php/index.php` synchronisiert.
   - PHP-Syntaxprüfung erfolgreich durchgeführt.
   - Per SFTP direkt nach `/sub/taskster/api/index.php` und `/sub/taskster/public/api/index.php` übertragen.

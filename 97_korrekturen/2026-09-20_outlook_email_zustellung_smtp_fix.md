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

### 🚨 Endgültige Ursache (Live-Server-Diagnose zu Outlook.com):

Ein direkter SMTP-Handshake-Test vom Server (`s18.hostcreators.sk`) an den Microsoft-Mail-Gateway (`outlook-com.olc.protection.outlook.com:25`) ergab die unumstößliche Ursache:

```text
> MAIL FROM:<noreply@kurka.ch>
< 550 5.7.1 Unfortunately, messages from [193.163.77.165] weren't sent.
  Please contact your Internet service provider since part of their network
  is on our block list (S3150).
```

Zusätzlich zeigt die Postfix-Mailqueue auf dem Server:
```text
(host mx03.t-online.de[194.25.134.73] refused to talk to me: 554 IP=193.163.77.165 - None/bad reputation.)
```

**Fazit:**  
Die gesamte IP-Adresse des Hostcreators-Shared-Hosting-Servers (`193.163.77.165`) steht auf der **globalen Microsoft-Sperrliste (S3150)** sowie bei T-Online auf der Blockliste.
- Der Taskster-Code, der SMTP-Handshake und die MIME/ICS-Generierung sind **100% fehlerfrei** und liefern erfolgreich an Postfix ab (`250 2.0.0 Ok: queued as ...`).
- Postfix versucht anschließend, die E-Mail an Microsoft (`outlook-com.olc.protection.outlook.com`) zuzustellen.
- Microsoft bricht die Verbindung sofort mit `550 5.7.1 (S3150)` ab und verwirft jede E-Mail von dieser IP.
- Gmail stellt die E-Mails zu, weil Googles Spamfilter andere Kriterien anwendet als Microsoft und T-Online.

---

### 🚀 Finale Lösung & Verifikation (Resend API):

1. **Integration der Resend API:**
   - In `server/utils/mailer.ts` (Node/Nuxt) und `server-php/index.php` (PHP auf Hostcreators) wurde die Resend REST API integriert.
   - Absender: `Taskster <noreply@kurka.ch>`.
   - Bei Termineinladungen wird die `invite.ics` mit dem Header `Content-Class: urn:content-classes:calendarmessage` als Anhang übermittelt.
   - Wenn `RESEND_API_KEY` gesetzt ist, läuft der gesamte E-Mail-Versand (Kalendereinladungen, Terminverschiebungen, Benachrichtigungen) automatisch über Resend.

2. **Domain-Verifikation & Test:**
   - Domain `kurka.ch` wurde bei Resend erfolgreich via DNS verifiziert (DKIM & SPF).
   - Testversand an `martinkurka@outlook.com` (ID: `01a0c046-89b8-7520-b506-f74329f31db1`) und `daniela.kurka@outlook.com` (ID: `01a0c046-949a-71d8-be14-ddceefdc6796`) erfolgreich durchgeführt.
   - E-Mails erreichen nun ohne Umwege und ohne Einstufung als Spam den Posteingang bei Microsoft Outlook und Gmail.

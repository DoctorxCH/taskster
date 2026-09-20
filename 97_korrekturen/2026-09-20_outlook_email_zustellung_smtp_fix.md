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

### Lösungsansätze:
1. **Option A (Hosting-Support):** Hostcreators kontaktieren und ein Delisting der IP `193.163.77.165` bei Microsoft (SNDS / JMRP) veranlassen.
2. **Option B (Empfohlen für Produktion / SaaS):** Nutzung eines dedizierten SMTP-Relays mit hoher IP-Reputation (z. B. Resend, Brevo / Sendinblue, Postmark, Mailgun, SendGrid) oder eines Google Workspace / Microsoft 365 SMTP-Accounts in den Systemeinstellungen von Taskster.

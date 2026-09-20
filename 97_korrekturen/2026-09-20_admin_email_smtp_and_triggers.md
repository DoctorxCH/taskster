# 2026-09-20 – Admin E-Mail (SMTP noreply@kurka.ch), Trigger-Vorlagen & Benachrichtigungen

## Datum & Kontext
- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `pages/admin/index.vue` (Neuer Tab "E-Mail & Benachrichtigungen" mit SMTP-Konfiguration, Vorlagen-Editor, Versandprotokoll und Test-Mail-Modal)
  - `pages/settings.vue` (Benutzer-Einstellungen für E-Mail-Kanal und Trigger-Event-Filter)
  - `server/db/schema.sql` & `server/db/index.ts` (`system_settings`, `email_templates`, `email_outbox`)
  - `server/utils/mailer.ts` (Nativer Socket/TLS SMTP Mailer ohne externe Abhängigkeiten, Template-Renderer)
  - `server/api/admin/email-settings.get.ts` & `.post.ts`
  - `server/api/admin/email-test.post.ts`
  - `server/api/admin/email-templates/index.get.ts`, `[id].put.ts`, `reset.post.ts`
  - `server/api/admin/email-outbox.get.ts`
  - `api/index.php`, `public/api/index.php`, `server-php/index.php` (Natives PHP Socket/TLS SMTP, Trigger-Mailer und Admin-API-Endpunkte)

## Durchgeführte Änderungen
1. **SMTP-Server Konfiguration (noreply@kurka.ch)**:
   - Voll konfigurierbar über Admin-Bereich (`Host`, `Port`, `Verschlüsselung SSL/TLS`, `User`, `Passwort`, `Absender-Name`, `Absender-E-Mail`).
   - Standard-Konfiguration initialisiert mit:
     - Host: `mail.kurka.ch`
     - Port: `465` (SSL)
     - User: `noreply@kurka.ch`
     - Pass: `Ckeesjb6&M`
     - From: `noreply@kurka.ch` / `Taskster Benachrichtigungen`
   - Test-E-Mail Funktion mit detaillierter SMTP-Kommunikations-Protokollierung (EHLO, AUTH, MAIL FROM, RCPT TO, DATA, MIME-Encoding).

2. **Trigger-Vorlagen (Admin)**:
   - 8 Standard-Trigger mit HTML- und Text-Vorlagen sowie dynamischen Platzhaltern angelegt:
     - `task_assigned`: Neue Aufgabe zugewiesen
     - `task_due`: Aufgabe fällig
     - `task_comment`: Neuer Aufgaben-Kommentar
     - `calendar_invite`: Termineinladung (inkl. .ics Kalenderdatei-Unterstützung)
     - `calendar_reminder`: Terminerinnerung
     - `mention`: Erwähnung (@Name)
     - `budget_warning`: Budgetwarnung bei Projekten
     - `company_invite`: Einladung zum Unternehmen
   - Einzelne oder alle Vorlagen können vom Administrator live editiert, aktiviert/deaktiviert und per Klick auf Standard zurückgesetzt werden.
   - HTML Live-Vorschau und Variable-Chip-Einfügung im Modal.

3. **Benutzer-Einstellungen (pages/settings.vue)**:
   - Nutzer können den E-Mail-Kanal global oder granular pro Ereignis an-/abwählen.
   - Server prüft vor dem Mailversand die Benutzereinstellungen und bricht ab, falls der Nutzer das Event deaktiviert hat.

4. **Versand-Protokoll (email_outbox)**:
   - Vollständiges Logging aller gesendeten, ausstehenden und fehlerhaften E-Mails in der Datenbank.

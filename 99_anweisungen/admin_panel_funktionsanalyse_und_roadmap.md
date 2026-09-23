# Taskster – Admin Panel: Funktionsanalyse, Fehlende Features & Entwicklungs-Roadmap

## 1. Executive Summary & Ist-Zustand

Das Admin-Panel von Taskster (`pages/admin/index.vue`) ist das zentrale Steuerungszentrum für Superadmins und Administratoren mit Teilberechtigungen (`admin_permissions`). Derzeit deckt das Admin-Panel grundlegende Mandanten- und Nutzerverwaltungsfunktionen ab, weist jedoch für den professionellen B2B-SaaS-Betrieb, Zero-Trust Security, KI-Kontingentierung und Systemüberwachung noch erhebliche Lücken auf.

### Aktuell implementierte Funktionen (Status Quo)

| Tab / Bereich | Inhalt & Features | Aktuelle Endpunkte |
| :--- | :--- | :--- |
| **1. Benutzerverwaltung (`users`)** | - Auflistung aller Kunden & Nutzer<br>- Anlegen neuer Benutzer mit Rollenzuweisung<br>- Bearbeiten von Benutzern (Plan, Rolle, Firma, Superadmin-Status)<br>- Vergabe von Sub-Permissions (`manage_users`, `manage_templates`, `company_settings`, `finance`) | `GET /api/admin/users`<br>`PATCH /api/admin/users/[id]`<br>`POST /api/auth/register` |
| **2. Unternehmen & B2B (`companies`)** | - Liste von Organisationen & Mandanten<br>- Anlegen neuer Firmen inkl. Firmen-Admin<br>- Zuweisung von Subscription-Plänen (Starter, Pro, Enterprise)<br>- Festlegung von Max Seats & Upload-Freigabe-Policies | `GET /api/admin/companies`<br>`POST /api/admin/companies`<br>`PATCH /api/admin/companies/[id]` |
| **3. Finanzen & MRR (`finance`)** | - Übersicht über monatlich wiederkehrenden Umsatz (MRR)<br>- Aktive Abonnements & kostenpflichtige Sitze<br>- Bestellungs- & Abonnementsliste | `GET /api/admin/overview`<br>`GET /api/orders` |
| **4. Firmeneinladungen (`invites`)** | - Übersicht offener & abgelaufener Firmeneinladungen<br>- Lizenztyp (`pro`/`free`) & Einladungstokens | `GET /api/companies/invitations` |
| **5. Systemvorlagen (`templates`)** | - Verwaltung systemweiter Projektvorlagen<br>- Unterscheidung zwischen Baugewerbe/Job & Privat-Vorlagen<br>- Erstellen, Bearbeiten & Löschen von Vorlagen-Listen & Aufgaben | `GET /api/templates`<br>`POST/PUT/DELETE /api/templates` |
| **6. E-Mail-System (`email`)** | - Umschaltung Mail-Provider (SMTP vs. Resend API)<br>- Bearbeitung von Transaktions-E-Mail-Vorlagen (HTML & Text)<br>- Versand-Protokoll (Outbox/Mail-Log) mit Status & Fehleranzeige<br>- Test-E-Mail Versand-Tool | `GET/POST /api/admin/email-settings`<br>`GET/PUT /api/admin/email-templates`<br>`GET /api/admin/email-outbox`<br>`POST /api/admin/email-test` |

---

## 2. Analyse der fehlenden Admin-Funktionen (Lückenmatrix)

Für den skalierbaren B2B-Betrieb fehlen im aktuellen Admin-Panel **6 essenzielle Kernel-Module**:

```
 ┌────────────────────────────────────────────────────────────────────────┐
 │                      FEHLENDE ADMIN-MODULE                             │
 ├───────────────────┬───────────────────┬────────────────────────────────┤
 │ 🛡️ Security Log   │ 🤖 AI & Quotas    │ 💾 Storage & Clean             │
 │ Audit Trails & IP │ Token-Limits &    │ Dateigrössen, Storage-Limits & │
 │ Session Management│ OpenRouter Stats  │ Orphaned File Removal          │
 ├───────────────────┼───────────────────┼────────────────────────────────┤
 │ 📢 Broadcasts     │ 🎨 Branding & CI  │ ⚙️ System Health               │
 │ Wartungsfenster & │ Custom Domains,   │ DB Migrationen, Uptime &       │
 │ In-App Ankündigung│ Logos & E-Mails   │ Queue Monitoring               │
 └───────────────────┴───────────────────┴────────────────────────────────┘
```

### Detaillierte Aufschlüsselung der fehlenden Funktionen:

### 🛡️ Modul A: Security & Audit Logging (Sicherheits- & Revisionsprotokoll)
- **Problem:** Es existiert derzeit kein zentrales Protokoll darüber, wer was geändert hat. Das ist kritisch für B2B-Compliance (DSGVO / GoBD).
- **Fehlende Features:**
  1. **Systemweites Audit Log:** Aufzeichnung von Login-Versuchen (Erfolg/Fehlschlag), Passwortänderungen, Rollenanpassungen, Datenlöschungen und Exporten.
  2. **Session & Token Management:** Anzeige aktiver Benutzersessions; Möglichkeit für Superadmins, verdächtige Sessions per Klick zu beenden.
  3. **IP Blacklisting & Rate-Limiting Overrides:** Sperren auffälliger IP-Adressen oder Freigeben von Whitelists.

### 🤖 Modul B: KI-Steuerung, Models & Token-Quotas (AI Metering)
- **Problem:** KI-Funktionen nutzen OpenRouter (`baidu/fp8`). Es fehlen Transparenz und Limitierungen pro Mandant.
- **Fehlende Features:**
  1. **Token-Verbrauchs-Monitoring:** Welches Unternehmen verbraucht wie viele KI-Tokens pro Monat?
  2. **Quotas & Limits per Plan:** Zuweisung von KI-Token-Budgets (z.B. Starter: 50.000 Tokens/Monat, Pro: 500.000 Tokens/Monat, Enterprise: unbegrenzt).
  3. **Model & Fallback Configurator:** Visuelle Steuerung der in `ai.config.json` definierten Provider & Fallbacks.

### 💾 Modul C: Storage & Datei-Management (Upload-Kontrolle & Bereinigung)
- **Problem:** Dateien (Journal-Fotos, Pläne, E-Mail-Anhänge) belegen Speicher ohne systemweite Übersicht oder Limits.
- **Fehlende Features:**
  1. **Speicherplatz-Dashboard:** Speicherverbrauch pro Firma und Gesamtsystem.
  2. **Storage Limits per Plan:** Soft & Hard Limits festlegen (z.B. 10 GB Pro Firma, Warnung bei 80%).
  3. **Orphaned Files Cleaner:** Ein-Klick-Bereinigung von verwaisten Uploads (Dateien im Speicher, die in der DB gelöscht wurden).
  4. **Dateitypen-Policy:** Sperren gefährlicher Dateiendungen (`.exe`, `.bat`, etc.) auf Plattformebene.

### 📢 Modul D: System-Ankündigungen & Wartungsmodus (Broadcasts & Maintenance)
- **Problem:** Nutzer können bei Wartungsarbeiten oder Releases nicht direkt in der App informiert werden.
- **Fehlende Features:**
  1. **Systemweite Ankündigungen (Banner):** Erstellen von In-App Banners (Info, Warnung, Kritisch) mit Gültigkeitszeitraum.
  2. **Wartungsmodus (Maintenance Switch):** Master-Schalter zur temporären Deaktivierung der App für normale Nutzer (Superadmins behalten Zugriff).
  3. **Targeted Announcements:** Banner nur an bestimmte Unternehmen oder Rollen senden.

### 🎨 Modul E: Mandanten-Branding & Corporate Identity (B2B Whitelabeling)
- **Problem:** B2B-Kunden wünschen eigenes Branding auf Bautagebücher-PDFs und E-Mails.
- **Fehlende Features:**
  1. **Custom Branding pro Firma:** Upload von Firmenlogos, Festlegen der primären Accent-Farbe für Exporte.
  2. **E-Mail-Header Branding:** Unternehmensspezifischer E-Mail-Kopf in Transaktionsmails.
  3. **Feature Flags per Company:** Aktivieren/Deaktivieren einzelner Module (z.B. Zeiterfassung, E-Mail-Import, Voice Memos) pro Mandant.

### ⚙️ Modul F: System Health & Datenbank-Migrations-Steuerung
- **Problem:** Prüfungen von DB-Migrationen (`migrate-mysql.cjs`) geschehen manuell per CLI.
- **Fehlende Features:**
  1. **DB Status & Schema Inspector:** Status-Anzeige aller SQL-Tabellen, Indizes und fehlender Spalten.
  2. **Health Status Indicators:** Uptime, Mail-Queue Auslastung, Fehler-Rate im Mail-Outbox.
  3. **Export & DSGVO Löschanfragen:** Ein-Klick DSGVO-Datenexport (ZIP/JSON) und rechtssichere Benutzer-Anonymisierung.

---

## 3. Technische Architektur & Datenmodell-Erweiterungen

Zur Umsetzung der fehlenden Funktionen werden folgende Tabellen in `server/db/schema.sql` und `scripts/migrate-mysql.cjs` hinzugefügt:

### DB-Schema Erweiterungen:

```sql
-- 1. Security & Audit Logs
CREATE TABLE IF NOT EXISTS audit_logs (
  id VARCHAR(64) PRIMARY KEY,
  user_id VARCHAR(64) NULL,
  company_id VARCHAR(64) NULL,
  action VARCHAR(128) NOT NULL,
  entity_type VARCHAR(64) NULL,
  entity_id VARCHAR(64) NULL,
  ip_address VARCHAR(45) NULL,
  user_agent TEXT NULL,
  details JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 2. System Ankündigungen
CREATE TABLE IF NOT EXISTS system_announcements (
  id VARCHAR(64) PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(32) NOT NULL DEFAULT 'info', -- info, warning, critical
  target_scope VARCHAR(32) NOT NULL DEFAULT 'all', -- all, company, pro_users
  target_company_id VARCHAR(64) NULL,
  starts_at DATETIME NOT NULL,
  ends_at DATETIME NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- 3. KI-Nutzung & Quotas
CREATE TABLE IF NOT EXISTS ai_usage_logs (
  id VARCHAR(64) PRIMARY KEY,
  company_id VARCHAR(64) NULL,
  user_id VARCHAR(64) NOT NULL,
  prompt_tokens INT NOT NULL DEFAULT 0,
  completion_tokens INT NOT NULL DEFAULT 0,
  model VARCHAR(64) NOT NULL,
  action_type VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

## 4. Umsetzungs-Roadmap (Phasenplan)

```
┌────────────────────────────────────────────────────────────────────────┐
│                        UMSETZUNGS-PHASEN                               │
├────────────────────────────────────────────────────────────────────────┤
│ PHASE 1: Datenbank-Basis & Audit Logging (Security & Logs Tab)          │
│ PHASE 2: System-Ankündigungen & Wartungsmodus (Broadcasts Tab)         │
│ PHASE 3: KI-Verbrauchssteuerung & Token Quotas (AI Management Tab)    │
│ PHASE 4: Storage-Dashboard & Cleanup Tools (Storage Tab)              │
│ PHASE 5: Mandanten-Branding & Whitelabeling (Company CI Tab)           │
│ PHASE 6: System Health & DSGVO Compliance Tools (System Health Tab)    │
└────────────────────────────────────────────────────────────────────────┘
```

### Detailplanung Phase 1 (Nächste Schritte):
1. **Schema-Migration:** Hinzufügen der `audit_logs` Tabelle in SQLite & MySQL.
2. **Backend Utilities:** Erstellung von `logAuditEvent()` in `server/utils/audit.ts`.
3. **Admin API:** `GET /api/admin/audit-logs` mit Filterung nach Datum, User, Aktion.
4. **UI Tab in `pages/admin/index.vue`:** Neuer Tab "Security & Audit Logs" mit Liquid Glass Filtertabelle.

---
*Erstellt am: 2026-09-23 | Taskster Admin-Architektur*

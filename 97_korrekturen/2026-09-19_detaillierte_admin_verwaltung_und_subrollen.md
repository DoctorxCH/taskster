# Detaillierte Admin-Verwaltung & Granulare Subrollen (Multi-Tier Architecture)

**Datum:** 2026-09-19  
**Typ:** Feature / Security / Admin Management  
**Status:** Abgeschlossen & Live bereitgestellt

## 1. Übersicht & Ziel
Implementierung einer mehrstufigen und granularen Administrator-Verwaltung:
1. **Superadmin:** Uneingeschränkter Vollzugriff auf die gesamte Plattform, alle Unternehmen, Benutzer, Vorlagen, Finanzen und System-Policies.
2. **Company Administrator mit Subrollen / Berechtigungen (`admin_permissions`):**
   - Kann gezielte Unterrollen erhalten (`manage_users`, `finance`, `company_settings`, `manage_templates`, `audit_logs`).
   - Bleibt streng auf sein eigenes Unternehmen isoliert (`company_id`).
   - Kann neue Benutzer manuell anlegen (`POST /api/admin/users`), bestehende Benutzer editieren, Passwörter vergeben/zurücksetzen und Berechtigungen erteilen (jedoch niemals Superadmin-Rechte vergeben oder Nutzer fremder Firmen einsehen/bearbeiten).
3. **Finance-Admin:** Sieht über den neuen Tab "Finanzen & Bestellungen" MRR, Abonnements, Sitze und Rechnungsstatus.

## 2. Datenbank-Schema-Erweiterung
- **Tabelle:** `users`
- **Feld:** `admin_permissions JSON NULL`
- **Migration:**
  - Remote MariaDB via `scripts/migrate-mysql.cjs`:
    `ALTER TABLE users ADD COLUMN admin_permissions JSON NULL;`
  - SQL Schema: [server/db/schema.sql](file:///c:/Users/marti/Taskster/server/db/schema.sql)
  - PHP Idempotente Migration: `$colMigrations` in [server-php/index.php](file:///c:/Users/marti/Taskster/server-php/index.php).

## 3. Backend-APIs (PHP Zero-Trust)
- **Helper:**
  - `checkAdminPermission($user, $permission)`: Prüft Superadmin oder Vorhandensein des Permission-Keys im decodierten `admin_permissions` Array.
  - `requireAdminPermission($permission)`: Sendet HTTP 403 Forbidden bei unzureichenden Rechten.
- **Endpoints:**
  - `GET /api/admin/overview`: Erlaubt für jeden berechtigten Admin; Metriken werden automatisch auf das eigene Unternehmen gefiltert, falls kein Superadmin.
  - `GET /api/admin/users`: Erfordert `manage_users`. Gibt Benutzerliste gefiltert nach Firma zurück (Superadmin sieht alle).
  - `POST /api/admin/users`: Erfordert `manage_users`. Erstellt neuen Benutzer mit Name, E-Mail, Passwort (gehasht via `password_hash`), Plan, Rolle und `admin_permissions`. Nicht-Superadmins können nur Nutzer für die eigene Firma anlegen und keine Superadmins erzeugen.
  - `PATCH /api/admin/users/:id`: Erfordert `manage_users`. Erlaubt Aktualisierung von Daten, Rollen, Berechtigungen und optional Passwort-Änderung.
  - `GET /api/admin/orders`: Erfordert `finance`. Liefert Firmenabos, Einzellizenzen, Sitze, monatliche Beträge, Zahlungsstatus und MRR-Zusammenfassung.
  - `GET /api/admin/companies`: Erfordert `company_settings`.
  - `PATCH /api/admin/companies/:id`: Erfordert `company_settings`.
  - `POST /api/auth/login` & `GET /api/auth/me`: Liefern `admin_permissions` im JWT Token und User-Objekt.

## 4. Frontend (Nuxt 3 / Tailwind)
- **Admin Index ([pages/admin/index.vue](file:///c:/Users/marti/Taskster/pages/admin/index.vue)):**
  - Granulare Tab-Sichtbarkeit basierend auf Rechten (`manage_users`, `finance`, `company_settings`, `manage_templates`).
  - Neuer Tab "💳 Finanzen & Bestellungen" mit MRR-Karte, aktiven Abonnements, zahlungspflichtigen Sitzen und B2B-Bestelltabelle im Liquid Glass Design.
  - Benutzer-Tabelle zeigt Rollen & Berechtigungs-Badges (`SUPERADMIN`, `Administrator` mit Badges für `👤 User-Mgmt`, `💳 Finanzen`, `🏢 Firmen`, `📋 Vorlagen`, `📜 Audit`).
  - Neuer Dialog "+ Neuen Benutzer anlegen" mit Zufallspasswort-Generator (`🎲 Zufallspasswort`), Presets ("Alle", "User-Mgmt", "Finanzen", "Keine") und Berechtigungs-Checkboxen.
  - Edit-Dialog erweitert um Berechtigungs-Verwaltung und Passwort-Änderung.
  - `onMounted` Schutz angepasst: Erlaubt Zugriff für alle berechtigten Admin-Stufen und öffnet automatisch den ersten freigeschalteten Tab.
- **Navbar ([components/Navbar.vue](file:///c:/Users/marti/Taskster/components/Navbar.vue)):**
  - "Admin-Bereich"-Link und `ADMIN` Badge für Superadmins und alle Benutzer mit Admin-Rollen bzw. `admin_permissions` freigeschaltet.

## 5. Deployment
- Statischer Nuxt-Build: `npm run generate`
- SFTP-Deploy: `node scripts/deploy-sftp.cjs` -> `/sub/taskster`
- Git Commit & Push: `origin/main`

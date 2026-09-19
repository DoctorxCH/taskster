# Firmen-Admin-Portal `/company` & Trennung vom Plattform-Admin

**Datum:** 2026-09-19
**Typ:** Feature + Root-Cause-Fix (Security / Rollen-Trennung)
**Referenz:** `.gemini/.../implementation_plan.md` (Gemini-Plan, nach Quota-Abbruch vollendet)

## Problem (Root Cause)

Company Admins (z.B. Marc Steiner, `marc@kurka.ch`) landeten beim Klick auf "Admin-Bereich"
im **Plattform-Admin** (`/admin`) und sahen dort fremde Mandanten – oder wurden ausgesperrt.

**Ursache:** In `auth/login` und `auth/me` wurde bei einem Company Admin automatisch
`admin_permissions = ['manage_users', 'finance', ...]` gesetzt. Dadurch galt er als
Plattform-Admin. Zusätzlich lud `getAuthUser()` die Spalte `admin_permissions` gar nicht,
wodurch echte Plattform-Admins (ohne `is_superadmin`) nie autorisiert werden konnten.

**Zweiter Fund:** Die Admin-Middleware las `localStorage.getItem('taskster_auth')`,
gespeichert wird aber nur `taskster_token` → Middleware lief ins Leere.

**Dritter Fund (Zero-Trust):** `GET /api/templates` lieferte **alle** Firmenvorlagen
aller Mandanten aus (Info-Leak).

## Lösung

### A. Rollen-Trennung (Zero-Trust)

| Portal | Route | Zugriff |
|---|---|---|
| Plattform-Admin | `/admin` | `is_superadmin = 1` **oder** explizite `admin_permissions` |
| Firmen-Admin | `/company` | `company_role === 'admin'` + `company_id` (oder Superadmin mit Firma) |

`admin_permissions` steht **nur noch** Plattform-Admins zu. Company Admins verwalten ihre
Firma ausschließlich über `company_role === 'admin'` im neuen `/company` Portal.

### B. Backend — PHP (`public/api/index.php` → Spiegel `api/`, `server-php/`)

**Fix:**
- `getAuthUser()` lädt jetzt `admin_permissions` mit.
- `auth/login` + `auth/me`: automatische `admin_permissions`-Zuweisung für Company Admins entfernt.
- `POST /admin/companies`: legt Company Admin ohne `admin_permissions` an.
- Migration `ensureTables()`: bereinigt bestehende Company Admins
  (`UPDATE users SET admin_permissions = NULL WHERE is_superadmin = 0 AND company_role = 'admin'`).
- `requireCompanyAdmin()` ergänzt (Company Admin oder Superadmin).
- `GET /templates`: Systemvorlagen + **nur eigene** Firmenvorlagen.
- `POST/PUT/DELETE /templates`: Eigentümer-Prüfung (Company Admin nur eigene Firma, 404 statt 403 → kein Info-Leak).

**Neue Endpunkte (`/api/company/*`):**
- `GET  /company/details` — Firmendaten, Plan, Statistiken, Anfragen-Historie
- `PATCH /company/details` — Firmenname + Zero-Trust-Policies
- `PATCH /company/members/:id` — Co-Admin ernennen / herabstufen
- `DELETE /company/members/:id` — Mitarbeiter entfernen
- `GET  /company/templates` — nur firmeneigene Vorlagen
- `POST /company/upgrade` — Plan-/Sitzplatz-Anfrage (benachrichtigt Superadmins)
- `POST /company/support` — Support-Ticket (benachrichtigt Superadmins)

### C. Backend — Nitro/TS (lokale Dev-Parität)

- Neu: `server/utils/company.ts` (Guards, Plan-Definitionen, Settings-Helper, Notify).
- Neu: `server/api/company/{details.get,details.patch,templates.get,upgrade.post,support.post}.ts`
- Neu: `server/api/company/members/[id].{patch,delete}.ts`
- Neu: `server/api/companies/{members.get,invitations.get}.ts`
- `server/utils/auth.ts`: `admin_permissions` in `AuthUser`, `checkAdminPermission()`, `requireAdminPermission()`.
- Admin-Routen (`users`, `companies`, `overview`) nutzen jetzt `requireAdminPermission` statt `requireSuperadmin`.
- `auth/login` + `auth/me`: gleiche `admin_permissions`-Logik wie PHP.
- `templates/*`: Mandanten-Isolation + Eigentümer-Prüfung.
- `tasks/index.get.ts`: **Bugfix** `no such column: p.owner_id` (Eigentümer liegt auf `project_folders`).
- `server/db/index.ts`: Migration `admin_permissions` + Cleanup, Tabellen `company_invitations`, `notifications`.

### D. Frontend

- **Neu: `pages/company/index.vue`** — vollständiges Firmen-Portal im Liquid-Glass-Design mit
  5 Tabs: Mitarbeiter & Co-Admins, Firmenvorlagen, Plan & Lizenzen, Firmen-Einstellungen, Support.
  Buttons nach Standard (`taskster_button`, `taskster_button_light`, `taskster_button_accent`,
  `px-6 text-xs h-[42px] rounded-lg`).
- **`components/Navbar.vue`** — `isPlatformAdmin` → "Admin-Bereich" (`/admin`);
  `isCompanyAdmin` → "🏢 Firmen-Admin" (`/company`). `initAuth()` in `onMounted` ergänzt.
- **`app.vue`** — gleiche Logik in der rechten Seitenleiste.
- **`pages/admin/index.vue`** — Middleware nutzt `taskster_token` + `initAuth()` und
  akzeptiert nur echte Plattform-Admins (Company Admins → `/dashboard`).

### E. Hilfsmittel

- `scripts/check-php-syntax.py` — leichte PHP-Syntax-Sanity-Prüfung (Klammern/Strings/Kommentare),
  da auf dem Entwicklungsrechner kein PHP-Binary vorhanden ist.

## Verifikation

Automatisierter API-Test (11/11 grün):

| # | Test | Ergebnis |
|---|---|---|
| 1 | Login Company Admin | `admin_permissions: []` ✅ |
| 2 | `GET /company/details` | 200, echte Firmendaten ✅ |
| 3 | `GET /company/templates` | 200 ✅ |
| 4 | `GET /companies/members` | 200, 2 Mitglieder ✅ |
| 5 | Company Admin → `/api/admin/users` | **403** ✅ |
| 6 | `POST /company/support` | 200, Ticket-ID ✅ |
| 7 | `POST /company/upgrade` | 200, Request-ID ✅ |
| 8 | `PATCH /company/details` | 200 ✅ |
| 9 | Firmenvorlage erstellen (is_system=0, eigene Firma) | 200 ✅ |
| 10 | Superadmin → `/api/admin/overview` | 200 ✅ |
| 11 | Cleanup Vorlage löschen | 200 ✅ |

UI-Verifikation im Browser:
- Login Marc Steiner → Navbar zeigt **🏢 Firmen-Admin** (kein "Admin-Bereich"), Badge **COMPANY ADMIN**.
- `/company` lädt: "Swisscom Infra Partner AG", Enterprise Plan, 2/50 Sitze, Mitgliederliste
  mit Rollen-Toggles, Upgrade- und Support-Historie.
- `/admin` als Company Admin → Redirect `/dashboard`.
- Login Superadmin → Navbar zeigt "Admin-Bereich"; `/admin` lädt Plattform-Administration.

## Deployment

1. `python generate_index.py` — Index aktualisiert (76 Dateien, 53 Endpunkte).
2. `python scripts/check-php-syntax.py` — alle PHP-Dateien OK.
3. `npm run build:dist` — Build + Sync ins Git-Root.
4. Git Commit & Push nach `origin/main`; Server: `git pull origin main`.
5. Bei MySQL-Schema: `node scripts/migrate-mysql.cjs` (Spalte `admin_permissions` existiert bereits;
   die Bereinigungs-Migration läuft automatisch in `ensureTables()`).

## Offene Punkte (nicht Teil dieser Änderung)

- Nitro-Routen für `/api/daily-todos` und `/api/notifications` fehlen weiterhin
  (nur in PHP implementiert) → 404 im lokalen Dev, in Produktion via PHP unkritisch.
- `/api/admin/orders` existiert nur in PHP.

# Korrektur-Dokumentation: Trennung Company-Admin von Taskster-Plattform-Admin, Zeitrapport-Scoping & Bereinigung statischer Admin-Tabs

- **Datum:** 2026-09-19
- **Betreff:** Strikte Isolierung von Company-Admins (kein Zugriff auf `/admin`), Zeitrapporte-Einschränkung für Plattform-Admins vs Company-Admins, Entfernung unanpassbarer Infotabs

## 1. Problemstellung & Anforderung
1. **Company Admin im Plattform-Admin:**
   - Ein Company Admin (`company_role === 'admin'`) sah in der Navbar den Link "Admin-Bereich" und konnte auf `/admin` navigieren.
   - Vorgabe: Ein Company Admin darf ausschließlich sein eigenes Unternehmen administrieren, hat jedoch keinerlei Bezug oder Zugriff auf die globale Taskster-Plattform-Administration.
2. **Zeitrapporte Überfüllung für Plattform-Admins:**
   - Ein Taskster-Admin/Superadmin sah in `/time` die Stundenbuchungen aller Kunden und Projekte der gesamten Datenbank.
   - Vorgabe: Ein Company Admin soll die Stunden seiner eigenen Mitarbeiter sehen, aber der Plattform-Admin möchte in seiner persönlichen Rapportierung nicht mit sämtlichen fremden Kundenbuchungen überhäuft werden.
3. **Statische Infokarten ("wofür sehen wir das wenn wir nichts anpassen können?"):**
   - Im Admin-Bereich unter "Sicherheit & Policys" (`activeTab === 'policies'`) wurden rein informative, statische Beschreibungen (4-Stufen-Pipeline, Free-Plan-Regeln) angezeigt, an denen nichts konfiguriert werden konnte.

## 2. Durchgeführte Änderungen

### A. Backend API (`server-php/index.php`, `public/api/index.php`, `api/index.php`)
- **Strikte Admin-Berechtigungsprüfung (`checkAdminPermission`):**
  - Company Admins (`company_role === 'admin'`) erhalten keinen automatischen Zugriff mehr auf `/api/admin/*`.
  - Nur Benutzer mit `is_superadmin = 1` oder explizit vergebenen Plattform-Subrollen in `users.admin_permissions` sind berechtigt.
- **Zeitrapporte Scoping (`GET /api/time-entries`):**
  - **Company Admin:** Sieht Zeiteinträge seiner eigenen Unternehmensmitarbeiter (`u.company_id = ?`) und Unternehmensprojekte (`pf.company_id = ?`).
  - **Plattform-Admin & reguläre Benutzer:** Sehen nur ihre eigenen Einträge (`te.user_id = ?`) bzw. Einträge in Projekten/Ordnern, an denen sie aktiv mitarbeiten oder Inhaber sind.
- **Neuer Endpunkt `GET /api/companies/members`:**
  - Liefert für Company Admins die Liste aller Mitarbeiter des eigenen Unternehmens.

### B. Frontend
- **Navbar (`components/Navbar.vue`):**
  - "Admin-Bereich"-Link wird nur noch für `user.is_superadmin` oder Benutzer mit `user.admin_permissions` angezeigt. Company Admins sehen diesen Button nicht mehr.
- **Plattform-Admin (`pages/admin/index.vue`):**
  - `isAnyAdmin` entfernt `company_role === 'admin'`. Unbefugte Aufrufe werden sofort auf `/dashboard` umgeleitet.
  - Tab "Sicherheit & Policys" und die statischen Textkarten ("Aktive Sicherheitsarchitektur" & "Tarifregeln") wurden vollständig entfernt.
- **Benutzer-Einstellungen (`pages/settings.vue`):**
  - Für Company Admins wurde eine dedizierte Sektion "🏢 Unternehmens-Verwaltung ({{ user.company_name }})" integriert.
  - Hier kann der Company Admin neue Mitarbeiter per E-Mail einladen (`POST /api/companies/members`) und die aktuelle Mitarbeiterliste der Firma einsehen.

## 3. Build & Deployment
- `npm run build:dist` erfolgreich ausgeführt.
- Git Commit & Push auf `origin/main`.

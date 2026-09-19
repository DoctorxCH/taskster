# Dokumentation: Zeitrapportierung Online & Git-Pull Merge Conflict Lösung

**Datum:** 2026-09-19  
**Betroffene Bereiche:** Zeitrapportierung (`/time`), Navigation (Navbar, Sidebar, Dashboard), API (`GET /api/time-entries`), Apache Routing (`.htaccess`), Server Deployment

---

## 1. Problemstellung

1. **`api/index.php` Merge Conflict beim Server `git pull origin main`**:
   - Auf dem Server `/sub/taskster` meldete Git beim `git pull`:
     `error: Your local changes to the following files would be overwritten by merge: api/index.php`.
   - **Ursache:** Das lokale Deployment-Skript `scripts/deploy-sftp.cjs` kopiert die gebaute `api/index.php` per SFTP direkt auf den Webserver in `/sub/taskster/api/index.php`. Da der Server-Ordner gleichzeitig ein Git-Checkout ist, sieht Git die SFTP-Datei als lokale Modifikation und bricht den Pull ab.
   - **Lösung:** Ausführen von `git checkout api/index.php && git pull origin main` auf dem Server.

2. **"Zeitrapportierung ist nicht online"**:
   - Es existierte bisher keine eigenständige, übergeordnete Seite `/time` für Zeitrapporte (Zeiterfassung war bisher nur als Sub-Tab innerhalb einzelner Projekte aufrufbar).
   - In `.htaccess` leitete der Fallback auf `/index.html` (Landingpage) statt auf `200.html` (Nuxt SPA Router Fallback).

---

## 2. Durchgeführte Änderungen

### A. Dedicated Zeitrapportierung Seite (`pages/time.vue`)
- Vollständiges Controlling- & Reporting-Cockpit:
  - **KPI-Metriken:** Gesamtdauer (`Std. Min.`), verrechenbares Volumen (`CHF`), Ø Stundensatz, Buchungsanzahl (aufgeschlüsselt nach Stoppuhr vs. manuell).
  - **Filterleiste:** Zeitraum-Presets (`Heute`, `Diese Woche`, `Dieser Monat`, `Gesamt`, `Benutzerdefiniert`), Datum von/bis, Projektfilter, Freitextsuche.
  - **Tabelle:** Datum, Mitarbeiter, Projekt & Aufgabe (verlinkt), Erfassungs-Badge (`⏱️ Stoppuhr` / `✏️ Manuell`), Notiz, Dauer, Ansatz, Betrag, Aktionen.
  - **Exporte:** CSV/Excel-Export mit UTF-8 BOM, Druckansicht (`window.print()`).
  - **Modal "Zeit erfassen" & "Bearbeiten":** Schnelle Erfassung und Korrektur von Zeiteinträgen.

### B. Navigation & Verlinkungen
- **[components/Navbar.vue](file:///c:/Users/marti/Taskster/components/Navbar.vue):** Direkter Navigationslink `⏱️ Zeitrapporte`.
- **[app.vue](file:///c:/Users/marti/Taskster/app.vue):** Sidebar-Link `⏱️ Zeitrapporte`.
- **[pages/dashboard.vue](file:///c:/Users/marti/Taskster/pages/dashboard.vue):** Shortcut-Button im Aufgabenbereich.

### C. Backend API Scoping & Filter (`GET /api/time-entries`)
- Ermöglicht Abfrage ohne feste `project_id`.
- Zero-Trust Scoping: Superadmin sieht alles; Standard-Nutzer sehen alle Einträge ihrer zugänglichen Projekte bzw. eigene Buchungen.
- Filter nach `project_id`, `user_id`, `date_from`, `date_to`.
- Synchronisiert in `server-php/index.php`, `public/api/index.php`, `api/index.php` und `server/api/time-entries/index.get.ts`.

### D. Apache Routing (.htaccess)
- Umstellung von `RewriteRule . /index.html [L]` auf `RewriteRule . /200.html [L]`, um direktes Neuladen von Unterseiten und SPA-Routen sicherzustellen.

---

## 3. Deployment & Status
- Statischer Build via `npm run generate` erfolgreich.
- Per SFTP via `scripts/deploy-sftp.cjs` auf Hostcreators `/sub/taskster` synchronisiert.
- Git Repository committet und nach `origin/main` gepusht.

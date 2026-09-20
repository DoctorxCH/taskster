# 2026-09-20 Profilbild (Avatar) für Benutzer

- **Typ:** Feature / UX-Erweiterung / Backend & DB
- **Bereich:** UI (Settings, Navbar), Backend (PHP & Nitro), DB (MySQL & SQLite)
- **Status:** Abgeschlossen & Getestet

## 1. Ausgangslage & Ziel
- Benutzer hatten bisher keine Möglichkeit, ein individuelles Profilbild hochzuladen. In der Navbar und in den Einstellungen wurden lediglich Namensinitialen angezeigt.
- Ziel war eine performante, elegante Profilbild-Verwaltung ohne Dateisystem-Konflikte auf dem Shared-Hosting (Hostcreators).

## 2. Änderungen Datenbank & Backend
- **MySQL (`sql21.hostcreators.sk:3326` & `scripts/migrate-mysql.cjs`):**
  - Spalte `avatar MEDIUMTEXT NULL` zur Tabelle `users` hinzugefügt.
- **SQLite (`server/db/index.ts`):**
  - Migration `ALTER TABLE users ADD COLUMN avatar TEXT NULL` ergänzt.
- **PHP-Backend (`server-php/index.php`, `public/api/index.php`, `api/index.php`):**
  - In `PATCH auth/profile`: Feld `avatar` entgegennehmen und in `users.avatar` persistieren (oder bei `null` / Leerwert löschen).
  - In `POST auth/login`, `POST auth/register`, `GET auth/me` und `PATCH auth/profile`: Feld `avatar` im Benutzerobjekt serialisieren.
- **Nitro Backend (`server/api/auth/profile.patch.ts`, `server/api/auth/me.get.ts`):**
  - Feld `avatar` im Nitro-Endpoint gespeichert und im User-Payload zurückgegeben.

## 3. Änderungen Frontend & UI
- **`composables/useAuth.ts`:**
  - Interface `User` um `avatar?: string | null` erweitert.
- **`pages/settings.vue`:**
  - Neue Profilbild-Sektion unter *Einstellungen -> Profil* mit Live-Vorschau.
  - Client-seitiger Bildzuschnitt & Komprimierung: Bilder werden per HTML5-Canvas zentriert auf quadratische 256×256 px als optimiertes WebP (oder JPEG) skaliert (~15–30 KB).
  - Buttons: *"Bild hochladen"* (`taskster_button_light`) und *"Entfernen"* (`taskster_button_accent`), wenn ein Avatar vorhanden ist.
  - Synchronisation: Beim Speichern wird das Profilbild in den Auth-State und `localStorage` übernommen.
  - Sidebar *Konto-Info*: Zeigt das Profilbild statt der Initiale.
- **`components/Navbar.vue`:**
  - Oben rechts im Benutzer-Dropdown/Profilbutton wird das Profilbild mit `object-cover` angezeigt; Fallback auf Initiale, falls kein Bild vorhanden ist.

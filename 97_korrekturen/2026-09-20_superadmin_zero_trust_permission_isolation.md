# 2026-09-20: Superadmin Zero-Trust Isolierung & Projekt-Sichtbarkeits-Fix

**Datum:** 2026-09-20  
**Betreff:** Superadmin darf nicht automatisch private Projekte/Ordner von Benutzern sehen oder bearbeiten  
**Status:** Abgeschlossen  

---

## 1. Problem & Anforderung
Superadmin (Plattform-Administrator) hatte in diversen Endpunkten und Frontend-Komponenten automatische Bypasses, wodurch er alle privaten Projekte, Ordner, Listen, Aufgaben und Zeiteinträge aller Benutzer systemweit sehen und bearbeiten konnte.
Gemäß Zero-Trust-Architektur und Martin-Persona gilt:
- Superadmin ist ausschliesslich für die System- und Plattformverwaltung in `/admin` (Benutzerverwaltung, Mandanten, Billing, Vorlagen, Audit-Logs) zuständig.
- Im normalen Arbeitsbereich (Dashboard, Projekte, Ordner, Aufgaben, Zeiterfassung) gilt die strikte 4-Stufen-Rechte-Pipeline ohne Superadmin-Ausnahme.
- Private Ordner und Projekte anderer Nutzer sind für den Superadmin unsichtbar (`404 Not Found`, kein Info-Leak).
- Superadmin hat keine automatische `owner`-Rolle auf fremden Projekten.

## 2. Durchgeführte Änderungen

### Backend (Nitro / TypeScript & PHP: api/index.php, server-php/index.php, public/api/index.php)
1. **`evaluateProjectAccess()`**:
   - `if (user.is_superadmin)` / `if (!empty($user['is_superadmin']))` Bypass komplett entfernt.
   - Superadmin durchläuft dieselbe Zugriffsprüfung wie jeder Nutzer (Owner, Project Member, Folder Member, Company Visibility).
2. **`GET /api/projects`**:
   - `SELECT all projects` für Superadmin entfernt. Superadmin erhält nur noch seine eigenen/zugewiesenen Projekte.
3. **`GET /api/folders/:id`**:
   - Superadmin-Bypass entfernt. Zugriff nur bei Owner, Company-Peer oder Mitgliedschaft.
4. **`PUT /api/folders/:id`**:
   - Superadmin-Bypass entfernt. Nur der tatsächliche Ordner-Eigentümer (`owner_id === user.id`) darf den Ordner bearbeiten.
5. **`POST /api/folders/:id/members` & `DELETE .../members/:userId`**:
   - Superadmin-Bypass entfernt. Nur Ordner-Eigentümer darf Ordner-Mitglieder verwalten.
6. **`POST /api/folders/:id/fields`**:
   - Superadmin-Bypass entfernt. Nur Ordner-Eigentümer oder Company-Admin (bei Company-Ordner).
7. **`POST /api/projects`**:
   - Superadmin-Bypass für Ordner-Schreibzugriff entfernt.
8. **`POST /api/projects/:id/members`**:
   - Superadmin-Bypass entfernt. Nur Projekt-Owner oder Projekt-Admin dürfen Mitglieder einladen.
9. **`GET /api/projects/:id` (Listen-Filterung)**:
   - Superadmin-Bypass für `custom`-Listen entfernt.
10. **`PUT /api/tasks/:id` & `DELETE /api/tasks/:id`**:
    - Superadmin-Bypass entfernt. Berechtigung basiert auf Projekt-Rolle (`owner`, `admin`, `editor`, `viewer`).
11. **`GET /api/time-entries`, `PUT /api/time-entries/:id`, `DELETE /api/time-entries/:id`**:
    - Superadmin-Bypasses entfernt. Zeitrapporte sind auf autorisierte Projekte gescopt.
12. **`GET /api/search`**:
    - Superadmin-Bypass bei `custom`-Listen und Tasks entfernt.

### Frontend (Nuxt / Vue 3)
1. **`pages/dashboard.vue`**:
   - Ordner-Bearbeiten-Button: `v-if="user?.id === folder.owner_id"` (ohne `|| user?.is_superadmin`).
   - Sichtbarkeits-Radio im Ordner-Modal nur bei Unternehmenszugehörigkeit (`user?.company_id`).
2. **`pages/folders/[id].vue`**:
   - Ordner-Teilen und Ordner-Anpassen Buttons: Nur für `user?.id === folder.owner_id`.
   - Mitglied-Entfernen Button: Nur für `user?.id === folder.owner_id`.
   - Sichtbarkeits-Auswahl nur bei Unternehmenszugehörigkeit.
3. **`pages/projects/[id].vue`**:
   - Projekt-Einstellungen Tab: `v-if="userRole === 'owner' || userRole === 'admin'"`.
   - Mitglied einladen Button: `v-if="userRole === 'owner' || userRole === 'admin'"`.
   - Zeiteintrag bearbeiten/löschen: Nur eigener Eintrag oder `owner`/`admin`.
   - Aufgabe löschen: `v-if="userRole === 'owner' || userRole === 'admin'"`.

## 3. Build & Deployment
- `python generate_index.py` ausgeführt (Code 0).
- `npm run build:dist` (`nuxt generate && node scripts/sync-dist.cjs`) erfolgreich ausgeführt (Code 0).
- Alle 3 PHP-Dateien synchronisiert.

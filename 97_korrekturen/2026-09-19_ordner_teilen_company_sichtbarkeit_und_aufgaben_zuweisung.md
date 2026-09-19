# Korrektur-Dokumentation: Ordner teilen, Company-Sichtbarkeit & Zuweisung in Aufgaben

- **Datum:** 2026-09-19
- **Betreff:** Ordner-Sharing Modal, Ordner-Mitglieder DB & API, Company-Sichtbarkeit Schalter Fix, Zuweisungs-Dropdown Vollständigkeit

## 1. Problemstellung
1. **Ordner-Sharing fehlte:** Nutzer konnten zwar Projekte teilen, aber Projektordner nicht mit Kollegen teilen oder Mitglieder auf Ordnerebene verwalten.
2. **Company-Sichtbarkeit nicht sichtbar:** Die Option, einen Projektordner für das gesamte Unternehmen freizugeben (`visibility = 'company'`), wurde ausgeblendet (`v-if="user?.company_id"`), wenn beim eingeloggten Benutzer `company_id` NULL war (z. B. Superadmin oder Standalone-Benutzer).
3. **Aufgaben-Zuweisung war leer:** Im Aufgaben-Detail-Drawer war das Dropdown "Zuweisen an" leer oder unvollständig, da `GET /api/projects/:id` bisher nur direkte `project_members` lieferte. Wenn keine separaten Projektmitglieder eingeladen waren, fehlten sowohl Inhaber als auch Unternehmenskollegen und Ordner-Mitglieder.

## 2. Änderungen

### A. Datenbank (MariaDB)
- Neue Tabelle `folder_members` per Remote-Migration (`scripts/migrate-mysql.cjs`) erstellt und in `server/db/schema.sql` synchronisiert:
  ```sql
  CREATE TABLE IF NOT EXISTS folder_members (
      id VARCHAR(64) PRIMARY KEY,
      folder_id VARCHAR(64) NOT NULL,
      user_id VARCHAR(64) NOT NULL,
      role VARCHAR(64) DEFAULT 'editor',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uq_folder_user (folder_id, user_id),
      INDEX idx_fm_folder (folder_id),
      INDEX idx_fm_user (user_id)
  );
  ```

### B. Backend API (`server-php/index.php`, `api/index.php`, `public/api/index.php`)
- **Zugriffsberechtigungen (`evaluateProjectAccess`):**
  - Prüft nun auch `folder_members` sowie übergeordnete `folder_visibility === 'company'`. Wenn ein Ordner auf `company` steht, erben alle Projekte im Ordner automatisch Leserechte für Unternehmensmitglieder.
- **Ordner Endpunkte:**
  - `GET /api/folders`: Gibt Ordner zurück, wenn Nutzer Inhaber ist, in `folder_members` eingetragen ist, oder der Ordner auf `company` steht.
  - `POST /api/folders` & `PUT /api/folders/:id`: Speichern `visibility` (`private` / `company`) und `company_id` zuverlässig ab.
  - `GET /api/folders/:id/members`: Liefert Ordnerdetails, Inhaber, direkte Ordner-Mitglieder sowie auswählbare Firmenmitglieder (`companyUsers`).
  - `POST /api/folders/:id/members`: Fügt Nutzer via Email oder ID mit Rolle `editor` oder `viewer` zum Ordner hinzu.
  - `DELETE /api/folders/:id/members/:userId`: Entfernt Nutzer aus Ordner.
- **Projekt & Aufgaben Zuweisung (`GET /api/projects/:id`):**
  - Aufgaben-Query erweitert um `LEFT JOIN users u ON u.id = t.assigned_to` -> liefert `assignee_name` und `assignee_email`.
  - Mitglieder-Rückgabe konsolidiert: Kombiniert Projekt-Inhaber, Ordner-Inhaber, alle `project_members`, alle `folder_members` und alle aktiven Unternehmensmitglieder (`company_id`). Dadurch ist das Zuweisungs-Dropdown für Aufgaben nie mehr leer.

### C. Frontend
- **Ordner-Detailseite (`pages/folders/[id].vue`):**
  - Neuer Button "👥 Ordner teilen" in der Ordner-Toolbar.
  - Komplettes Modal `showShareFolderModal` mit:
    - Firmenweiter Sichtbarkeitsschalter (`🔒 Privat` vs `🏢 Gesamtes Unternehmen`).
    - Einladungs-Formular mit Schnell-Auswahl aus Firmenkollegen, E-Mail-Eingabe und Rollen-Dropdown (`Editor` / `Betrachter`).
    - Dynamische Mitgliederliste mit Rollenanzeige und "Entfernen"-Aktion.
  - Ordner-Bearbeiten-Modal: Sichtbarkeitsumschaltung bleibt sichtbar für Superadmins bzw. wenn der Ordner/User einer Company zugewiesen ist, mit erklärendem Hinweis für Einzelnutzer.
- **Dashboard (`pages/dashboard.vue`):**
  - Modals für neuen Ordner und Ordner-Bearbeiten um Company-Sichtbarkeitsauswahl für Superadmins / Company-User ergänzt.
- **Projekt-Detailseite (`pages/projects/[id].vue`):**
  - Zuweisungs-Dropdown im Task-Drawer zeigt saubere Formatierung: Name, E-Mail, `(Du)` Kennzeichnung und Rollenindikator.

## 3. Build & Deployment
- Lokaler statischer Build mit `npm run generate` erfolgreich.
- Synchronisation via SFTP nach `/sub/taskster` erfolgreich abgeschlossen.
- Git commit & push nach `origin/main`.

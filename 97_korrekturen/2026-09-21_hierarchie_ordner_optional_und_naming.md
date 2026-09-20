# 2026-09-21 – Hierarchie: Ordner-Ebene optional + Naming vereinheitlicht

## Ausgangslage
Bewertung der Hierarchie `Company → project_folders → projects → lists → tasks` ergab zwei Schwächen:
1. **Begriffs-Wildwuchs:** DB-Tabelle `lists`, UI meist „Abschnitt", Spec „Aufgabenliste".
2. **Zwangsebene Ordner:** Free-/Single-User (ohne Company) mussten durch zwei Ebenen, bevor sie eine Aufgabe erfassen konnten – bei max. 1 Ordner reine Reibung.

## Teil A – Naming vereinheitlicht (UI auf „Abschnitt")
Entscheidung: DB-Tabelle `lists` und API-Route `/api/lists` bleiben intern unverändert (kein Migrationsrisiko). Nur die sichtbare UI/i18n wird auf **„Abschnitt"** vereinheitlicht.

### i18n (`i18n/locales/{de,en,sk}.json`)
- `admin.vordefinierte_abschnitte_listen` → „Vordefinierte Abschnitte"
- `admin.vordefinierte_abschnitte_listen_1` → „Vordefinierte Abschnitte"
- `admin.diese_listen_werden_automatisch_ang` → „Diese Abschnitte werden …"
- `admin.in_listen_gepflegt` → „In Abschnitten gepflegt"
- `admin.verwalte_strukturierte_vorlagen_mit` → „… mit Standard-Abschnitten …"
- `company.listen_spalten_im_board` → „Abschnitte (Spalten im Board)"
- `company.listenname_hinzufügen` → „Abschnittsname hinzufügen"
- `company.in_listen_gepflegt` → „in Abschnitten gepflegt"
- `company.erstelle_eigene_vorlagen_mit_listen` → „… mit Abschnitten …"
- `folders.listen` → „📂 Abschnitte"
- `common.ordner_unterprojekte_meilensteine_l` → „Ordner → Projekte → Abschnitte → Aufgaben"
- `common.granulare_zugriffssteuerung_auf_pro` → „… Projekt- und Abschnittsebene"

### Vue-Dateien
- `pages/admin/index.vue`: Labels „Vordefinierte Abschnitte", „In Abschnitten gepflegt", Vorlagen-Beschreibung
- `pages/company/index.vue`: „Abschnitte (Spalten im Board)", „Abschnittsname hinzufügen", Statistik, Vorlagen-Beschreibung
- `pages/folders/[id].vue`: Kachel-Statistik „Abschnitte", Import-Mapping „(Erzeugt Abschnitte)"
- `pages/index.vue`: Marketing-Texte
- `components/VoiceRecorderModal.vue`: Fehlermeldung „… noch keine Abschnitte"

**Bewusst NICHT geändert** (andere Bedeutung): „Auswahlliste (Dropdown)", „Checkliste", „Listen-Dichte" (Ansicht), Ansicht-Umschalter „Kacheln/Liste", „Ergebnisliste", „Terminliste".

## Teil B – Ordner-Ebene für Free-/Single-User optional
Free-/Single-User = `!is_pro && !company_id && !is_superadmin`. Für diese entfällt die Ordner-Ebene in der UI; Projekte erscheinen direkt. Intern bleibt `projects.folder_id NOT NULL` – es wird ein **impliziter Standard-Ordner** („Meine Projekte") automatisch angelegt.

### Server (Nitro)
- **Neu:** `server/utils/defaultFolder.ts` – `getOrCreateDefaultFolder(user)` legt bei Bedarf einen Ordner an.
- `server/api/projects/index.post.ts`: `folder_id` ist jetzt optional. Ohne `folder_id` wird bei Free-Usern der Standard-Ordner verwendet/angelegt; bei Company/Pro bleibt `folder_id` Pflicht.

### Server (PHP, Produktion)
- `public/api/index.php` (Quelle) + Mirrors `api/index.php`, `server-php/index.php`: identische Logik im `POST projects`-Handler.

### Frontend
- `pages/dashboard.vue`:
  - Neuer Computed `isFreeUser`, State `projects`, `loadProjects()`, `createProject()`, `openNewProjectModal()`.
  - Widget 2 zeigt für Free-User eine **direkte Projektliste** (statt Ordner-Grid) mit „Neues Projekt"-Button.
  - Neues Modal „Neues Projekt".
  - Empty-Tasks-CTA verlinkt für Free-User direkt auf das erste Projekt.
  - Deep-Link `?new=project` bzw. `?new=folder` öffnet das passende Modal.
- `pages/projects/[id].vue`: Breadcrumb-Ordner-Crumb und „Ordner:"-Zeile für Free-User ausgeblendet (auch im Task-Drawer).
- `pages/folders/[id].vue`: Free-User werden per `onMounted` auf `/dashboard` umgeleitet.
- `components/CommandPalette.vue`: „Neuer Projektordner"-Schnellaktion für Free-User entfernt.
- Free-Plan-Hinweis angepasst (kein „1 Projektordner" mehr).

## Deployment
- `npm run build:dist` → Commit `b0b3877` → Push nach `origin/main`.
- PHP-Syntax geprüft (`scripts/check-php-syntax.py`): alle drei Dateien OK.
- Server: `git pull origin main`.

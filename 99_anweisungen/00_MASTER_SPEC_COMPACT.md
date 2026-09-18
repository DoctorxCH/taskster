# Taskster – Master-Spezifikation (Kompakt / Low-Token)

## 1. Produkt & Architektur
- **Hierarchie:** `Company` → `project_folders` → `projects` → `lists` (`inherit` | `custom`) → `tasks` (`custom_data` JSON) + `sub_tasks`.
- **Stack:** Vue.js/Nuxt 3, Tailwind CSS, PHP API-Router / TypeScript Nitro, MariaDB/PostgreSQL (JSONB), Capacitor.
- **Ressourcenverschleierung:** Nicht berechtigte Objekte liefern `404 Not Found` (nie `403`), um Existenz zu verbergen.

## 2. Datenbank-Schema (11 Tabellen)
| Tabelle | Primäre Spalten | Zweck |
|:---|:---|:---|
| `companies` | `id` (UUID), `name`, `subscription_plan`, `settings` (JSON), `created_at` | Mandanten & globale Policies (z.B. Upload-Sperre). |
| `users` | `id` (UUID), `company_id`, `company_role` (`admin`\|`member`), `is_superadmin`, `is_pro`, `email`, `password_hash` | Benutzerkonto & Rechte. |
| `project_folders` | `id`, `owner_id`, `company_id`, `name`, `created_at` | Oberste Ordnerebene (Free: max. 1 Ordner). |
| `folder_field_definitions` | `id`, `folder_id`, `field_key`, `label`, `field_type`, `is_pro_only`, `formula`, `logic_rules`, `options`, `is_required`, `sort_order` | Dynamische Custom Fields pro Ordner. |
| `projects` | `id`, `folder_id`, `title`, `status`, `created_at` | Ausführungseinheiten. |
| `project_members` | `id`, `project_id`, `user_id`, `role` (`editor`\|`viewer`) | Projektmitgliedschaft. |
| `lists` | `id`, `project_id`, `title`, `access_mode` (`inherit`\|`custom`), `sort_order` | Aufgabenlisten. |
| `list_access` | `id`, `list_id`, `user_id`, `is_visible` | Sichtbarkeit bei `custom`-Listen. |
| `tasks` | `id`, `list_id`, `title`, `description`, `status`, `custom_data` (JSON), `due_date`, `sort_order` | Aufgabenobjekt. |
| `project_journals` | `id`, `project_id`, `task_id`, `author_id`, `entry_type`, `title`, `content`, `metadata` | Protokoll (System, Mail, Voice). |
| `project_documents` | `id`, `project_id`, `task_id`, `journal_id`, `file_name`, `mime_type`, `file_size`, `storage_path`, `version` | Dokumente & Anhänge. |

## 3. Berechtigungs-Pipeline (4 Stufen)
1. **Company Policy:** `companies.settings` prüfen -> `403 Forbidden` bei Verstoß (z.B. Upload global gesperrt).
2. **Project Membership:** User muss Ordner-Owner oder in `project_members` sein -> sonst `404 Not Found`.
3. **List Scope:** Wenn `access_mode='custom'`, User muss Owner sein oder `list_access.is_visible=true` -> sonst `404 Not Found`.
4. **Role Action:** Viewer dürfen nur lesen -> `403 Forbidden` bei Schreibaktionen.

## 4. Kernfeatures & Limits
- **Custom Fields:** Text, Zahl, Dropdown, Datum, Formel (`Stunden * Satz`), bedingte Sichtbarkeit.
- **Ingestion & Media:** .msg/.eml Drag-and-Drop (asynchron geparst), Audioaufnahme (.opus/.m4a) + Player.
- **Controlling:** 1-Klick-Timer, Stundensätze, Budget Soll/Ist, Export (PDF, Excel, ZIP).
- **Free-Tier Limits:** Max. 1 Projektordner, max. 3 Mitglieder, Basisfelder (keine Formeln/Pro-Logik).

## 5. Meilensteine (Phasenplan)
- **M1: Auth & Mandanten** (Companies, Users, Rollen, Login)
- **M2: Projekte & Listen** (Zero-Trust, inherit/custom, Viewer-Schutz)
- **M3: Dynamische Felder** (Custom Fields, Formeln, reaktive Logik)
- **M4: Journal & Ingestion** (Outlook .msg Parser, Voice Memos)
- **M5: Zeit & Export** (Timer, Budget, PDF/Excel-Generator)
- **M6: Mobile & Offline** (Capacitor, lokaler SQLite Cache)

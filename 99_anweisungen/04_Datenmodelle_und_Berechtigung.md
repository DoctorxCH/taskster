# 04: Datenmodelle & Berechtigung

## 1. Schema-Definition (11 Tabellen)

| Tabelle | Primäre Spalten | Zweck |
|:---|:---|:---|
| `companies` | `id` (UUID), `name`, `subscription_plan`, `settings` (JSON), `created_at` | Mandanten & globale Policies. |
| `users` | `id` (UUID), `company_id`, `company_role` (`admin`\|`member`), `is_superadmin`, `is_pro`, `email`, `password_hash` | Benutzerkonto & Rechte. |
| `project_folders` | `id`, `owner_id`, `company_id`, `name`, `created_at` | Ordner-Ebene (Free: max. 1 Ordner). |
| `folder_field_definitions` | `id`, `folder_id`, `field_key`, `label`, `field_type`, `is_pro_only`, `formula`, `logic_rules`, `options`, `is_required`, `sort_order` | Custom Fields & Formellogik. |
| `projects` | `id`, `folder_id`, `title`, `status`, `created_at` | Projektausführungseinheit. |
| `project_members` | `id`, `project_id`, `user_id`, `role` (`editor`\|`viewer`) | Mitgliedschaft & Rolle. |
| `lists` | `id`, `project_id`, `title`, `access_mode` (`inherit`\|`custom`), `sort_order` | Aufgabenlisten. |
| `list_access` | `id`, `list_id`, `user_id`, `is_visible` | Sichtbarkeit bei `custom`-Listen. |
| `tasks` | `id`, `list_id`, `title`, `description`, `status`, `custom_data` (JSON), `due_date`, `sort_order` | Aufgaben. |
| `project_journals` | `id`, `project_id`, `task_id`, `author_id`, `entry_type`, `title`, `content`, `metadata` | Journal-Einträge & History. |
| `project_documents` | `id`, `project_id`, `task_id`, `journal_id`, `file_name`, `mime_type`, `file_size`, `storage_path`, `version` | Dokumentenverwaltung. |

## 2. Berechtigungs-Pipeline (4 Stufen)
1. **Company Policy:** `companies.settings` prüfen -> `403 Forbidden` bei Verstoß.
2. **Project Membership:** User muss Ordner-Owner oder in `project_members` sein -> sonst `404 Not Found`.
3. **List Scope:** Bei `access_mode='custom'` muss User Owner oder in `list_access.is_visible=true` sein -> sonst `404 Not Found`.
4. **Role Action:** Viewer haben ausschließlich Leserechte -> `403 Forbidden` bei Mutationsversuchen.

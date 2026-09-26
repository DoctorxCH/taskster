# 02 Dynamische Task-, Workflow- & Projekt-Engine

**Ziel:** Keine fest codierten Enums in PHP/MySQL für Status, Prioritäten, Kategorien. Alles basiert auf Lookup-Tabellen mit Parent-Child- und State-Machine-Konfiguration. Superadmin konfiguriert alles im Admin-Panel.

---

## 1. Datenbank-Schema

### Tabelle: `task_statuses`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) UNIQUE | Slug: `draft`, `in_progress`, `review`, `completed`, `cancelled`, `blocked` |
| `name` | VARCHAR(100) | Anzeigename |
| `badge_bg` | VARCHAR(20) | Hex-Farbe für Badge-Hintergrund |
| `badge_fg` | VARCHAR(20) | Hex-Farbe für Badge-Schrift |
| `icon` | VARCHAR(50) | Lucide/Heroicons Identifier (z. B. `circle`, `loader`, `check-circle`) |
| `sort_order` | INT DEFAULT 0 | Sortierung in Listen |
| `is_initial` | BOOLEAN DEFAULT FALSE | Start-Status |
| `is_in_progress` | BOOLEAN DEFAULT FALSE | In-Arbeit-Kennzeichen |
| `is_blocked` | BOOLEAN DEFAULT FALSE | Blockiert-Kennzeichen |
| `is_completed` | BOOLEAN DEFAULT FALSE | Abgeschlossen-Kennzeichen |
| `is_cancelled` | BOOLEAN DEFAULT FALSE | Abgebrochen-Kennzeichen |
| `auto_timestamp_field` | VARCHAR(50) NULL | Feldname für Auto-Timestamp bei Eintritt (z. B. `completed_at`) |
| `auto_archive_after_days` | INT NULL | Auto-Archivierung nach X Tagen in diesem Status |
| `required_fields_on_exit` | JSON NULL | Pflichtfelder beim Verlassen: `["description", "assignee_id"]` |
| `description` | TEXT NULL | |
| `is_system` | BOOLEAN DEFAULT FALSE | Nicht löschbar |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `task_status_transitions` (State-Machine)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `from_status_id` | BIGINT UNSIGNED FK | `task_statuses.id` |
| `to_status_id` | BIGINT UNSIGNED FK | `task_statuses.id` |
| `required_role` | VARCHAR(50) NULL | Rolle die Transition darf (optional) |
| `condition` | TEXT NULL | JSON-Logik/Condition (z. B. `{"field": "assignee_id", "operator": "not_null"}`) |
| `created_at` | TIMESTAMP | |

### Tabelle: `task_priorities`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(30) UNIQUE | `low`, `medium`, `high`, `urgent`, `critical` |
| `name` | VARCHAR(50) | Anzeigename |
| `weight` | INT | Numerisches Gewicht für Sortierung (10, 20, 30, 40, 50) |
| `sort_order` | INT | Alternative Sortierung |
| `color` | VARCHAR(20) | Hex-Farbe |
| `icon` | VARCHAR(50) | Icon-Identifier |
| `is_default` | BOOLEAN DEFAULT FALSE | Standard bei Schnellerfassung |
| `sla_response_hours` | INT NULL | Reaktionsfrist in Stunden |
| `sla_resolution_hours` | INT NULL | Lösungsfrist in Stunden |
| `description` | TEXT NULL | |
| `is_system` | BOOLEAN DEFAULT FALSE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `categories` (hierarchisch für Tasks & Projekte)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `parent_id` | BIGINT UNSIGNED NULL FK | Selbstreferenz für Hierarchie |
| `key` | VARCHAR(50) | Eindeutiger Key |
| `name` | VARCHAR(100) | Anzeigename |
| `color` | VARCHAR(20) NULL | Hex-Farbe |
| `icon` | VARCHAR(50) NULL | Icon |
| `entity_type` | ENUM('task','project','both') DEFAULT 'both' | Wofür gilt die Kategorie |
| `workspace_id` | BIGINT UNSIGNED NULL FK | Mandanten-spezifisch (NULL = global) |
| `tag_mode` | ENUM('strict','free') DEFAULT 'strict' | `strict`: nur Admin erstellt Tags, `free`: User darf freie Tags |
| `sort_order` | INT DEFAULT 0 | |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `custom_fields` (EAV/JSON-Engine)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) UNIQUE | Technischer Key (z. B. `budget`, `external_id`) |
| `label` | VARCHAR(100) | Anzeigename |
| `type` | ENUM('string','text','number','decimal','boolean','date','datetime','single_select','multi_select','user_reference','file_attachment') | Feldtyp |
| `config` | JSON NULL | Konfiguration je Typ: `options` (select), `min`/`max` (number), `regex` (string), `date_format` (date), `allowed_mime_types` (file) |
| `validation_rules` | JSON NULL | `{"required": true, "min_length": 3, "max_length": 255, "pattern": "^[A-Z]"}` |
| `visibility_conditions` | JSON NULL | `{"global_required": false, "required_per_project_type": {"type_a": true}, "required_when_status": ["in_progress"]}` |
| `entity_type` | ENUM('task','project','time_entry','contact') | Für welche Entität |
| `project_type_id` | BIGINT UNSIGNED NULL FK | Nur für bestimmten Projekttyp (optional) |
| `sort_order` | INT DEFAULT 0 | |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `is_system` | BOOLEAN DEFAULT FALSE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `custom_field_values` (Werte-Speicherung)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `custom_field_id` | BIGINT UNSIGNED FK | |
| `entity_type` | VARCHAR(30) | `task`, `project`, `time_entry`, `contact` |
| `entity_id` | BIGINT UNSIGNED | ID der Entität |
| `value` | JSON | Wert (String, Number, Boolean, Array, Object) |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`custom_field_id`, `entity_type`, `entity_id`) | |

### Tabelle: `project_types`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) UNIQUE | `standard`, `agile`, `waterfall`, `support`, `internal` |
| `name` | VARCHAR(100) | |
| `description` | TEXT NULL | |
| `folder_structure` | ENUM('forced','flat','optional') DEFAULT 'optional' | Ordner-Zwang |
| `default_phases` | JSON | Phasen-Array: `[{"key":"planning","name":"Planung","sort":1},{"key":"execution","name":"Umsetzung","sort":2}]` |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `project_phases` (Instanzen pro Projekt)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `project_id` | BIGINT UNSIGNED FK | |
| `key` | VARCHAR(50) | Aus Project-Type Phasen |
| `name` | VARCHAR(100) | |
| `sort_order` | INT | |
| `status` | ENUM('pending','active','completed','skipped') DEFAULT 'pending' | |
| `started_at`, `completed_at` | TIMESTAMP NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Task Statuses** |
| GET | `/api/admin/task-statuses` | Liste mit Transitionen |
| POST | `/api/admin/task-statuses` | Erstellen |
| PUT | `/api/admin/task-statuses/{id}` | Aktualisieren |
| DELETE | `/api/admin/task-statuses/{id}` | Löschen (nur wenn nicht `is_system` und nicht referenziert) |
| POST | `/api/admin/task-statuses/{id}/transitions` | Transition hinzufügen |
| DELETE | `/api/admin/task-statuses/transitions/{id}` | Transition entfernen |
| **Priorities** |
| GET/POST/PUT/DELETE | `/api/admin/task-priorities` | CRUD |
| **Categories** |
| GET | `/api/admin/categories?entity_type=task&workspace_id=1` | Baum-Struktur (nested) |
| POST | `/api/admin/categories` | Erstellen (mit Parent) |
| PUT | `/api/admin/categories/{id}` | Aktualisieren |
| DELETE | `/api/admin/categories/{id}` | Löschen (Cascade oder Block wenn Kinder) |
| POST | `/api/admin/categories/reorder` | Drag&Drop Sortierung |
| **Custom Fields** |
| GET | `/api/admin/custom-fields?entity_type=task` | Liste |
| POST | `/api/admin/custom-fields` | Erstellen mit Validierung |
| PUT | `/api/admin/custom-fields/{id}` | Aktualisieren |
| DELETE | `/api/admin/custom-fields/{id}` | Löschen |
| **Project Types & Phases** |
| GET/POST/PUT/DELETE | `/api/admin/project-types` | CRUD |
| GET | `/api/admin/projects/{id}/phases` | Phasen eines Projekts |
| PUT | `/api/admin/projects/{id}/phases/{phaseId}` | Phase aktualisieren (Status, Dates) |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/workflow
├── Statuses/
│   ├── Index.vue          # Liste + State-Machine Visualisierung
│   ├── StatusForm.vue     # Create/Edit Modal
│   └── TransitionEditor.vue # Graph-Editor für Übergänge
├── Priorities/
│   ├── Index.vue
│   └── PriorityForm.vue
├── Categories/
│   ├── Index.vue          # Tree-View mit Drag&Drop
│   └── CategoryForm.vue
├── CustomFields/
│   ├── Index.vue          # Gruppiert nach Entity-Type
│   ├── FieldForm.vue      # Dynamisches Formular je Typ
│   └── FieldConfigEditor.vue # JSON-Editor für config/validation
└── ProjectTypes/
    ├── Index.vue
    ├── TypeForm.vue
    └── PhaseEditor.vue    # Phasen pro Typ definieren
```

### Besondere UI-Elemente
- **State-Machine Visualisierung:** Mermaid.js oder Cytoscape.js Graph für Status-Übergänge
- **Tree-View Categories:** `vue-draggable-next` für Drag&Drop Hierarchie
- **FieldForm:** Dynamische Felder basierend auf `type` (Color-Picker, Select-Options-Editor, Regex-Tester, Date-Format-Preview)
- **Validation Preview:** Live-Test der Validierungsregeln mit Sample-Daten

---

## 4. Business-Logik

### Status-Transition Service
```php
class TaskStatusTransitionService {
    public function canTransition(Task $task, string $toStatusKey, User $user): bool {
        $transition = TaskStatusTransition::where('from_status_id', $task->status_id)
            ->whereHas('toStatus', fn($q) => $q->where('key', $toStatusKey))
            ->first();
        
        if (!$transition) return false;
        
        // Role Check
        if ($transition->required_role && !$user->hasRole($transition->required_role)) {
            return false;
        }
        
        // Condition Check (JSON Logic)
        if ($transition->condition && !$this->evaluateCondition($task, $transition->condition)) {
            return false;
        }
        
        return true;
    }
    
    public function executeTransition(Task $task, string $toStatusKey, User $user): Task {
        if (!$this->canTransition($task, $toStatusKey, $user)) {
            throw new InvalidTransitionException();
        }
        
        $toStatus = TaskStatus::where('key', $toStatusKey)->firstOrFail();
        
        // Required Fields Check
        if ($task->status->required_fields_on_exit) {
            $this->validateRequiredFields($task, $task->status->required_fields_on_exit);
        }
        
        // Auto-Timestamp
        if ($toStatus->auto_timestamp_field) {
            $task->{$toStatus->auto_timestamp_field} = now();
        }
        
        $task->status_id = $toStatus->id;
        $task->save();
        
        // Events feuern
        event(new TaskStatusChanged($task, $task->status, $toStatus));
        
        // Auto-Archive Job schedulen
        if ($toStatus->auto_archive_after_days) {
            ArchiveTaskJob::dispatch($task)->delay(now()->addDays($toStatus->auto_archive_after_days));
        }
        
        return $task;
    }
}
```

### Custom Field Validation
```php
class CustomFieldValidator {
    public function validate(CustomField $field, mixed $value, Entity $entity): array {
        $errors = [];
        $rules = $field->validation_rules ?? [];
        
        // Type-spezifische Validierung
        switch ($field->type) {
            case 'string':
                if (isset($rules['min_length']) && mb_strlen($value) < $rules['min_length']) {
                    $errors[] = "Mindestlänge: {$rules['min_length']}";
                }
                if (isset($rules['pattern']) && !preg_match($rules['pattern'], $value)) {
                    $errors[] = "Format ungültig";
                }
                break;
            case 'number':
            case 'decimal':
                if (isset($rules['min']) && $value < $rules['min']) $errors[] = "Mindestwert: {$rules['min']}";
                if (isset($rules['max']) && $value > $rules['max']) $errors[] = "Maximalwert: {$rules['max']}";
                break;
            case 'single_select':
            case 'multi_select':
                $options = $field->config['options'] ?? [];
                $values = (array)$value;
                foreach ($values as $v) {
                    if (!in_array($v, array_column($options, 'value'))) {
                        $errors[] = "Ungültige Option: $v";
                    }
                }
                break;
        }
        
        // Visibility Conditions
        if ($this->isFieldRequired($field, $entity)) {
            if (empty($value)) $errors[] = "Pflichtfeld";
        }
        
        return $errors;
    }
}
```

### Cache-Strategie
- `task_statuses`, `task_priorities`, `categories` → Redis, TTL 24h, Invalidation bei Admin-Änderung
- `custom_fields` pro Entity-Type → Redis Hash, TTL 1h
- `project_types` mit Phasen → Redis, TTL 24h

---

## 5. Tests

### Unit
- `TaskStatusTransitionServiceTest` – alle Transition-Regeln, Conditions, Auto-Timestamp, Archive
- `CustomFieldValidatorTest` – alle Feldtypen, Edge-Cases, Visibility-Conditions
- `CategoryTreeTest` – Hierarchie, Move, Delete mit Kindern

### Integration
- API CRUD für alle Entitäten
- Status-Transition über API → Event gefeuert → Job geschedult
- Custom Field Wert speichern/laden → JSON korrekt

### E2E
- Superadmin: Status erstellen → Transition definieren → Task erstellen → Transition testen (erlaubt/verboten)
- Custom Field: Number-Feld mit Min/Max → Task-Form zeigt Validierung
- Category: Baum per Drag&Drop umsortieren → Persistiert

---

## 6. Rollout & Migration

### Migrationen (Reihenfolge wichtig!)
1. `create_task_statuses_table` + Seeder (Defaults)
2. `create_task_status_transitions_table`
3. `create_task_priorities_table` + Seeder
4. `create_categories_table` + Seeder (bestehende Kategorien migrieren)
5. `create_custom_fields_table` + `create_custom_field_values_table`
6. `create_project_types_table` + Seeder
7. `create_project_phases_table`
8. **Bestehende Tabellen anpassen:** `tasks.status_id` FK → `task_statuses`, `tasks.priority_id` FK → `task_priorities`, `projects.type_id` FK → `project_types`

### Feature-Flags
- `FEATURE_DYNAMIC_WORKFLOW` – schaltet Lookup-Tabellen ein (Fallback: alte Enums)
- `FEATURE_CUSTOM_FIELDS` – EAV-Engine aktivieren

### Deployment
1. Migrationen & Seeders
2. API & Services
3. Admin-UI
4. Feature-Flags nacheinander aktivieren
5. Alte Enum-Spalten nach Verifikation droppen

---

## 7. Offene Fragen

- [ ] **Transition Conditions:** JSON Logic (php-json-logic) vs. PHP-Callables? (Empfehlung: JSON Logic für Admin-Editierbarkeit)
- [ ] **Custom Field Performance:** JSON-Spalte vs. separate Wert-Tabelle? (Aktuell: separate Tabelle für Indexierung)
- [ ] **Kategorie-Vererbung:** Sollen Kind-Kategorien Parent-Farbe/Icon erben? (Ja, optional)
- [ ] **Projekt-Phasen:** Automatischer Status-Wechsel bei Task-Status-Änderung in Phase? (Später, v2)

---

*Status: **Geplant** – Bereit für Implementierung nach Design-Tokens.*
# 04 Zero-Trust Berechtigungsmatrix, Rollen & Mandanten

**Ziel:** Multi-Tenancy mit Quotas, Feature-Flags, granulare RBAC+ABAC, Feld-Level-Permissions, Auth-Policies (Passwort, 2FA, Session, SSO). Superadmin verwaltet alles zentral.

---

## 1. Datenbank-Schema

### Tabelle: `tenants` (Mandanten)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `name` | VARCHAR(100) | Anzeigename |
| `slug` | VARCHAR(50) UNIQUE | URL-Slug |
| `status` | ENUM('active','suspended','read_only','archived') DEFAULT 'active' | |
| `max_users` | INT DEFAULT 100 | User-Quota |
| `max_storage_gb` | INT DEFAULT 10 | Speicher-Quota |
| `max_tasks_per_month` | INT DEFAULT 10000 | Task-Limit/Monat |
| `api_rate_limit_per_minute` | INT DEFAULT 60 | API Rate Limit |
| `settings` | JSON | Feature-Flags: `{"time_tracking":true,"budgeting":false,"gantt":true,"ai_assistant":true}` |
| `billing_email` | VARCHAR(150) NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `roles`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(50) | Technischer Key: `superadmin`, `tenant_admin`, `project_manager`, `member`, `viewer` |
| `name` | VARCHAR(100) | Anzeigename |
| `level` | ENUM('system','tenant','project') | Geltungsbereich |
| `description` | TEXT NULL | |
| `is_system` | BOOLEAN DEFAULT FALSE | Nicht löschbar |
| `sort_order` | INT DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `permissions` (Berechtigungs-Definitionen)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(100) UNIQUE | `tasks.create`, `tasks.read_all`, `tasks.read_assigned`, `tasks.update_all`, `tasks.update_assigned`, `tasks.delete_soft`, `tasks.delete_hard`, `tasks.export`, `projects.*`, `budgets.*`, `time_entries.*`, `documents.*` |
| `entity` | VARCHAR(30) | `task`, `project`, `budget`, `time_entry`, `document`, `user`, `settings` |
| `action` | VARCHAR(30) | `create`, `read_all`, `read_assigned`, `update_all`, `update_assigned`, `delete_soft`, `delete_hard`, `export`, `manage` |
| `description` | TEXT NULL | |
| `is_system` | BOOLEAN DEFAULT FALSE | |
| `created_at` | TIMESTAMP | |

### Tabelle: `role_permissions` (RBAC Matrix)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `role_id` | BIGINT UNSIGNED FK | |
| `permission_id` | BIGINT UNSIGNED FK | |
| `scope` | ENUM('all','tenant','project','assigned') DEFAULT 'all' | Geltungsbereich der Permission |
| `conditions` | JSON NULL | ABAC-Bedingungen: `{"field": "project_id", "operator": "in", "value": "user.project_ids"}` |
| `created_at` | TIMESTAMP | |
| **Unique Index** | (`role_id`, `permission_id`, `scope`) | |

### Tabelle: `field_permissions` (Feld-Level Permissions)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `role_id` | BIGINT UNSIGNED FK | |
| `entity_type` | VARCHAR(30) | `task`, `project`, `time_entry`, `user` |
| `field_name` | VARCHAR(50) | `hourly_rate`, `internal_notes`, `margin`, `salary` |
| `can_read` | BOOLEAN DEFAULT FALSE | |
| `can_write` | BOOLEAN DEFAULT FALSE | |
| `created_at` | TIMESTAMP | |
| **Unique Index** | (`role_id`, `entity_type`, `field_name`) | |

### Tabelle: `auth_policies` (Mandanten-spezifisch)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED NULL FK | NULL = System-Default |
| `password_min_length` | INT DEFAULT 12 | |
| `password_require_uppercase` | BOOLEAN DEFAULT TRUE | |
| `password_require_lowercase` | BOOLEAN DEFAULT TRUE | |
| `password_require_numbers` | BOOLEAN DEFAULT TRUE | |
| `password_require_symbols` | BOOLEAN DEFAULT TRUE | |
| `password_expiry_days` | INT DEFAULT 90 | 0 = nie |
| `password_pwned_check` | BOOLEAN DEFAULT TRUE | HaveIBeenPwned API |
| `mfa_policy` | ENUM('disabled','optional','required_all','required_privileged') DEFAULT 'optional' | |
| `mfa_methods` | JSON DEFAULT '["totp"]' | `["totp","webauthn"]` |
| `session_idle_timeout_minutes` | INT DEFAULT 30 | |
| `session_absolute_lifetime_hours` | INT DEFAULT 12 | |
| `block_concurrent_sessions` | BOOLEAN DEFAULT FALSE | |
| `bind_session_to_ip` | BOOLEAN DEFAULT TRUE | |
| `sso_enabled` | BOOLEAN DEFAULT FALSE | |
| `sso_config` | JSON NULL | `{"provider":"saml","entity_id":"...","metadata_xml":"...","client_id":"...","client_secret":"...","role_mapping":{"admin":"tenant_admin"}}` |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `user_tenant_roles` (User-Rolle pro Mandant/Projekt)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED FK | |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `project_id` | BIGINT UNSIGNED NULL FK | NULL = Tenant-Ebene |
| `role_id` | BIGINT UNSIGNED FK | |
| `assigned_by` | BIGINT UNSIGNED FK | |
| `expires_at` | TIMESTAMP NULL | |
| `created_at` | TIMESTAMP | |
| **Unique Index** | (`user_id`, `tenant_id`, `project_id`, `role_id`) | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Tenants** |
| GET/POST/PUT/DELETE | `/api/admin/tenants` | CRUD + Status-Change |
| GET | `/api/admin/tenants/{id}/usage` | Aktuelle Quota-Nutzung |
| **Roles & Permissions** |
| GET | `/api/admin/roles?level=tenant` | Liste gefiltert nach Level |
| POST/PUT/DELETE | `/api/admin/roles` | CRUD (System-Roles read-only) |
| GET | `/api/admin/permissions` | Alle Permissions gruppiert nach Entity |
| POST | `/api/admin/roles/{id}/permissions` | Permission zuweisen (mit Scope & Conditions) |
| DELETE | `/api/admin/roles/{id}/permissions/{permId}` | Entziehen |
| GET | `/api/admin/roles/{id}/matrix` | Vollständige Matrix als JSON |
| **Field Permissions** |
| GET | `/api/admin/field-permissions?role_id=1&entity=task` | Liste |
| POST/PUT/DELETE | `/api/admin/field-permissions` | CRUD |
| **Auth Policies** |
| GET/PUT | `/api/admin/auth-policies?tenant_id=1` | Lesen/Schreiben (System-Default oder Tenant) |
| POST | `/api/admin/auth-policies/test-password` | Passwort gegen Policy prüfen |
| **User Assignments** |
| GET | `/api/admin/users/{id}/assignments` | Alle Rollen eines Users |
| POST | `/api/admin/users/{id}/assignments` | Rolle zuweisen (Tenant/Project) |
| DELETE | `/api/admin/users/{id}/assignments/{assignmentId}` | Entziehen |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/access
├── Tenants/
│   ├── Index.vue          # Tabelle mit Status, Quotas, Feature-Flags Toggle
│   ├── TenantForm.vue     # Quotas, Feature-Flags (Checkbox-Grid)
│   └── TenantUsage.vue    # Usage-Dashboard (Charts)
├── Roles/
│   ├── Index.vue          # Tabs: System / Tenant / Project
│   ├── RoleForm.vue       # Key, Name, Level, Beschreibung
│   └── PermissionMatrix.vue # **Kern-Feature**: Grid Role × Permission mit Scope-Select
├── FieldPermissions/
│   ├── Index.vue          # Nach Entity gruppiert, Rollen als Spalten
│   └── FieldPermissionForm.vue
├── AuthPolicies/
│   ├── Index.vue          # System-Default + Tenant-Overrides
│   ├── PolicyForm.vue     # Alle Passwort/2FA/Session/SSO Settings
│   └── SSOConfigEditor.vue # XML/JSON Editor für SAML/OIDC Metadaten
└── UserAssignments/
    ├── UserSearch.vue     # User finden
    └── AssignmentManager.vue # Rollen pro Tenant/Projekt zuweisen
```

### PermissionMatrix.vue – Kern-Komponente
- **Zeilen:** Permissions (gruppiert nach Entity)
- **Spalten:** Rollen
- **Zellen:** Dropdown `None | All | Tenant | Project | Assigned` + Condition-Editor (JSON)
- **Bulk-Actions:** Ganze Zeile/Spalte setzen, Vorlagen laden (Presets: "Admin", "Manager", "Member", "Viewer")
- **Visualisierung:** Farbcodierung (Grün=All, Gelb=Scoped, Rot=None)

---

## 4. Business-Logik

### Permission Checker (Runtime)
```php
class PermissionChecker {
    public function can(User $user, string $permissionKey, ?Model $resource = null): bool {
        // 1. Superadmin bypass
        if ($user->hasSystemRole('superadmin')) return true;
        
        // 2. Tenant-Status prüfen
        $tenant = $user->currentTenant;
        if ($tenant->status !== 'active') return false;
        
        // 3. Rollen des Users für relevanten Scope sammeln
        $roles = $this->getEffectiveRoles($user, $resource);
        
        // 4. RBAC Check
        foreach ($roles as $role) {
            $rp = RolePermission::where('role_id', $role->id)
                ->whereHas('permission', fn($q) => $q->where('key', $permissionKey))
                ->first();
            
            if (!$rp) continue;
            
            // Scope prüfen
            if ($this->matchesScope($rp, $user, $resource)) {
                // ABAC Conditions prüfen
                if ($rp->conditions && !$this->evaluateConditions($rp->conditions, $user, $resource)) {
                    continue;
                }
                return true;
            }
        }
        
        return false;
    }
    
    public function canReadField(User $user, string $entityType, string $fieldName): bool {
        $roles = $this->getEffectiveRoles($user);
        foreach ($roles as $role) {
            $fp = FieldPermission::where('role_id', $role->id)
                ->where('entity_type', $entityType)
                ->where('field_name', $fieldName)
                ->first();
            if ($fp && $fp->can_read) return true;
        }
        return false;
    }
}
```

### Zero-Trust Enforcement (Middleware)
```php
// app/Http/Middleware/ZeroTrust.php
public function handle(Request $request, Closure $next, string $permission) {
    $user = $request->user();
    
    if (!$user || !app(PermissionChecker::class)->can($user, $permission, $this->getResource($request))) {
        // Zero-Trust: 404 statt 403 (kein Info-Leak)
        abort(404);
    }
    
    // Feld-Level Filterung für Responses
    $response = $next($request);
    return $this->filterResponseFields($response, $user);
}
```

### Quota Enforcement
```php
class QuotaEnforcer {
    public function checkTenantQuota(Tenant $tenant, string $resource): bool {
        return match($resource) {
            'users' => $tenant->users()->count() < $tenant->max_users,
            'storage' => $this->getStorageUsage($tenant) < $tenant->max_storage_gb * 1024**3,
            'tasks_monthly' => $this->getMonthlyTaskCount($tenant) < $tenant->max_tasks_per_month,
            'api_rate' => $this->getCurrentApiRate($tenant) < $tenant->api_rate_limit_per_minute,
            default => true
        };
    }
}
```

---

## 5. Tests

### Unit
- `PermissionCheckerTest` – RBAC, Scopes, ABAC-Conditions, Feld-Level
- `QuotaEnforcerTest` – Alle Quota-Typen, Edge-Cases
- `AuthPolicyTest` – Passwort-Validierung, MFA, Session, SSO-Config-Validierung

### Integration
- API: Tenant CRUD, Role-Permission Matrix, Field-Permissions
- Middleware: 404 bei fehlender Permission, Feld-Filterung in JSON-Response
- SSO: SAML/OIDC Login Flow (Mock IdP)

### E2E
- Superadmin: Tenant erstellen → Quotas setzen → Feature-Flags → User zuweisen → Login als Tenant-User → Prüfen was sichtbar
- Role Matrix: Custom Role erstellen → Permissions mit Scopes setzen → User zuweisen → API-Calls testen
- Field Permissions: `hourly_rate` für "Member" auf `can_read=false` → API Response prüft Feld fehlt

---

## 6. Rollout & Migration

### Migrationen
1. `create_tenants_table` + Seeder (Default-Tenant für bestehende Daten)
2. `create_roles_table` + Seeder (System-Roles)
3. `create_permissions_table` + Seeder (Alle Entity-Action Kombinationen)
4. `create_role_permissions_table` + Seeder (Default-Matrix)
5. `create_field_permissions_table` + Seeder (Sensible Felder: `hourly_rate`, `salary`, `margin`, `internal_notes`)
6. `create_auth_policies_table` + Seeder (System-Default)
7. `create_user_tenant_roles_table` + Migration bestehender User-Rollen
8. **Bestehende Tabellen:** `users.tenant_id` FK, `projects.tenant_id` FK

### Feature-Flags
- `FEATURE_MULTI_TENANCY` – Mandanten-Isolation
- `FEATURE_RBAC_ABAC` – Neue Permission-Engine
- `FEATURE_FIELD_PERMISSIONS` – Feld-Level
- `FEATURE_SSO` – SAML/OIDC

### Deployment
1. Migrationen & Seeders
2. Permission Checker Service + Middleware registrieren
3. API & Admin-UI
4. Feature-Flags schrittweise aktivieren (erst RBAC, dann Field, dann SSO)
5. Alte Permission-Logik entfernen

---

## 7. Offene Fragen

- [ ] **ABAC Condition Language:** JSON Logic vs. Laravel Gate/Custom? (JSON Logic für Admin-Editierbarkeit)
- [ ] **Permission Caching:** Pro User+Tenant Cache-Key, TTL 5min, Invalidation bei Rollen-Änderung?
- [ ] **SSO Role Mapping:** Dynamisch per Attribut (SAML Assertion / OIDC Claim) oder statisch? (Beides unterstützen)
- [ ] **Tenant-Switching:** User mit mehreren Tenants – UI für Switcher? (Ja, Header-Dropdown)

---

*Status: **Geplant***
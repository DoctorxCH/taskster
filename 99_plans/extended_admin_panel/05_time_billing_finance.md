# 05 Zeiterfassungs-, Abrechnungs- & Finanzlogik

**Ziel:** Buchungsregeln, Rundung, Limits, Stundensatz-Hierarchie, Zeittypen, Währungen – alles konfigurierbar pro Mandant/Projekt.

---

## 1. Datenbank-Schema

### Tabelle: `time_tracking_settings` (pro Mandant)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED FK | NULL = System-Default |
| `entry_mode` | ENUM('stopwatch','manual','hybrid') DEFAULT 'hybrid' | Erfassungsmodus |
| `rounding_rule` | ENUM('none','5min','10min','15min','30min') DEFAULT 'none' | Aufrunden auf |
| `max_hours_per_task_per_day` | DECIMAL(4,2) DEFAULT 12.00 | Max pro Task/Tag |
| `max_hours_per_day` | DECIMAL(4,2) DEFAULT 10.00 | Max pro Tag (Warnung) |
| `block_over_max_hours` | BOOLEAN DEFAULT FALSE | Blockieren statt Warnen |
| `allow_past_entries_days` | INT DEFAULT 3 | Rückwirkende Erfassung (Tage) |
| `allow_future_entries` | BOOLEAN DEFAULT FALSE | Zukunftseinträge |
| `require_description` | BOOLEAN DEFAULT TRUE | Tätigkeitsbeschreibung Pflicht |
| `require_subtask_or_cost_center` | BOOLEAN DEFAULT TRUE | Unteraufgabe/Kostenstelle Pflicht |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `hourly_rates` (Hierarchie: Global → Tenant → Project → Role → User)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `level` | ENUM('global','tenant','project','role','user') | Ebene |
| `tenant_id` | BIGINT UNSIGNED NULL FK | Bei level=tenant |
| `project_id` | BIGINT UNSIGNED NULL FK | Bei level=project |
| `role_id` | BIGINT UNSIGNED NULL FK | Bei level=role |
| `user_id` | BIGINT UNSIGNED NULL FK | Bei level=user |
| `currency` | CHAR(3) DEFAULT 'EUR' | |
| `amount` | DECIMAL(10,2) | Stundensatz |
| `valid_from` | DATE | Gültig ab |
| `valid_until` | DATE NULL | Gültig bis |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`level`, `tenant_id`, `project_id`, `role_id`, `user_id`, `valid_from`) | |

### Tabelle: `time_types` (Zeittypen mit Multiplikatoren)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `key` | VARCHAR(30) UNIQUE | `normal`, `overtime`, `weekend`, `holiday`, `night`, `standby` |
| `name` | VARCHAR(50) | Anzeigename |
| `multiplier` | DECIMAL(3,2) | `1.00`, `1.25`, `1.50`, `2.00`, `0.50` |
| `color` | VARCHAR(20) | Hex für UI |
| `icon` | VARCHAR(50) | Icon |
| `is_billable` | BOOLEAN DEFAULT TRUE | Abrechenbar |
| `sort_order` | INT DEFAULT 0 | |
| `is_system` | BOOLEAN DEFAULT FALSE | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `time_entries` (Erweitert)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `user_id` | BIGINT UNSIGNED FK | |
| `task_id` | BIGINT UNSIGNED NULL FK | |
| `project_id` | BIGINT UNSIGNED NULL FK | |
| `time_type_id` | BIGINT UNSIGNED FK | Default: `normal` |
| `started_at` | TIMESTAMP | |
| `ended_at` | TIMESTAMP NULL | NULL = laufende Stoppuhr |
| `duration_minutes` | INT | Berechneter/manueller Wert |
| `description` | TEXT | Tätigkeitsbeschreibung |
| `subtask_id` | BIGINT UNSIGNED NULL FK | |
| `cost_center_id` | BIGINT UNSIGNED NULL FK | |
| `hourly_rate_snapshot` | DECIMAL(10,2) NULL | Satz zum Buchungszeitpunkt |
| `currency_snapshot` | CHAR(3) NULL | Währung zum Buchungszeitpunkt |
| `billed_amount` | DECIMAL(12,2) NULL | `duration * rate * multiplier` |
| `status` | ENUM('draft','submitted','approved','rejected','billed') DEFAULT 'draft' | |
| `approved_by` | BIGINT UNSIGNED NULL FK | |
| `approved_at` | TIMESTAMP NULL | |
| `invoice_id` | BIGINT UNSIGNED NULL FK | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `invoices` (Rechnungen)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `tenant_id` | BIGINT UNSIGNED FK | |
| `project_id` | BIGINT UNSIGNED NULL FK | |
| `client_id` | BIGINT UNSIGNED FK | Kontakt/Kunde |
| `number` | VARCHAR(50) UNIQUE | Rechnungsnummer |
| `status` | ENUM('draft','sent','paid','overdue','cancelled') DEFAULT 'draft' | |
| `currency` | CHAR(3) | |
| `subtotal` | DECIMAL(12,2) | Netto |
| `tax_rate` | DECIMAL(5,2) | MwSt-Satz |
| `tax_amount` | DECIMAL(12,2) | |
| `total` | DECIMAL(12,2) | Brutto |
| `issued_at` | DATE | |
| `due_at` | DATE | |
| `paid_at` | DATE NULL | |
| `pdf_path` | VARCHAR(500) NULL | Generiertes PDF |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `invoice_items`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `invoice_id` | BIGINT UNSIGNED FK | |
| `time_entry_id` | BIGINT UNSIGNED NULL FK | Referenz |
| `description` | TEXT | |
| `quantity` | DECIMAL(6,2) | Stunden |
| `unit_price` | DECIMAL(10,2) | Stundensatz |
| `total` | DECIMAL(12,2) | |
| `sort_order` | INT | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Time Tracking Settings** |
| GET/PUT | `/api/admin/time-tracking-settings?tenant_id=1` | Lesen/Schreiben |
| **Hourly Rates** |
| GET | `/api/admin/hourly-rates?level=project&project_id=5` | Gefiltert |
| POST/PUT/DELETE | `/api/admin/hourly-rates` | CRUD |
| GET | `/api/admin/hourly-rates/resolve?user_id=1&project_id=5&date=2026-01-15` | Effektiven Satz auflösen |
| **Time Types** |
| GET/POST/PUT/DELETE | `/api/admin/time-types` | CRUD |
| **Time Entries (Admin)** |
| GET | `/api/admin/time-entries?user_id=1&status=submitted&from=2026-01-01&to=2026-01-31` | Liste mit Filtern |
| PUT | `/api/admin/time-entries/{id}/approve` | Genehmigen |
| PUT | `/api/admin/time-entries/{id}/reject` | Ablehnen |
| POST | `/api/admin/time-entries/bulk-approve` | Bulk genehmigen |
| **Invoices** |
| GET/POST/PUT/DELETE | `/api/admin/invoices` | CRUD |
| POST | `/api/admin/invoices/generate` | Aus genehmigten Einträgen generieren |
| POST | `/api/admin/invoices/{id}/send` | Versenden (E-Mail) |
| GET | `/api/admin/invoices/{id}/pdf` | PDF generieren/herunterladen |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/finance
├── TimeTracking/
│   ├── SettingsForm.vue       # Modus, Rundung, Limits, Pflichtfelder
│   └── TimeTypes.vue          # Zeittypen mit Multiplikatoren
├── HourlyRates/
│   ├── Index.vue              # Hierarchie-Baum: Global → Tenant → Project → Role → User
│   ├── RateForm.vue           # Satz, Währung, Gültigkeitszeitraum
│   └── RateResolver.vue       # Test-Tool: User+Project+Datum → effektiver Satz
├── TimeEntries/
│   ├── Index.vue              # Filterbare Tabelle, Status-Badges
│   ├── EntryDetail.vue        # Detail mit Historie
│   └── BulkActions.vue        # Genehmigen/Ablehnen/Export
└── Invoices/
    ├── Index.vue
    ├── InvoiceForm.vue        # Positionen aus Time-Entries picker
    ├── InvoiceDetail.vue      # PDF Preview, Status-Timeline
    └── InvoicePDF.vue         # Template-Editor für PDF-Layout
```

### Besondere Features
- **Rate Resolver:** Visueller Baum der Hierarchie, zeigt welcher Satz gewinnt
- **Time Entry Approval Workflow:** Kanban-Board (Draft → Submitted → Approved → Billed)
- **Invoice PDF Template:** Blade/Twig Editor mit Live-Preview

---

## 4. Business-Logik

### Hourly Rate Resolver (Ketten-Auflösung)
```php
class HourlyRateResolver {
    public function resolve(User $user, Project $project, Carbon $date): ?HourlyRate {
        $rates = HourlyRate::where('is_active', true)
            ->where('valid_from', '<=', $date)
            ->where(function($q) use ($date) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', $date);
            })
            ->orderByRaw("CASE level 
                WHEN 'user' THEN 5 
                WHEN 'role' THEN 4 
                WHEN 'project' THEN 3 
                WHEN 'tenant' THEN 2 
                WHEN 'global' THEN 1 
                END DESC")
            ->get();
        
        foreach ($rates as $rate) {
            if ($this->matches($rate, $user, $project)) {
                return $rate;
            }
        }
        return null;
    }
    
    private function matches(HourlyRate $rate, User $user, Project $project): bool {
        return match($rate->level) {
            'user' => $rate->user_id === $user->id,
            'role' => $user->roles()->where('id', $rate->role_id)->exists(),
            'project' => $rate->project_id === $project->id,
            'tenant' => $rate->tenant_id === $project->tenant_id,
            'global' => true,
        };
    }
}
```

### Time Entry Calculation
```php
class TimeEntryCalculator {
    public function calculate(TimeEntry $entry): array {
        $rate = $entry->hourly_rate_snapshot ?? 
            app(HourlyRateResolver::class)->resolve($entry->user, $entry->project, $entry->started_at);
        
        $timeType = $entry->timeType;
        $multiplier = $timeType->multiplier;
        
        $hours = $entry->duration_minutes / 60;
        $billedAmount = round($hours * $rate->amount * $multiplier, 2);
        
        return [
            'hours' => $hours,
            'rate' => $rate->amount,
            'multiplier' => $multiplier,
            'billed_amount' => $billedAmount,
            'currency' => $rate->currency,
        ];
    }
}
```

### Rounding Service
```php
class RoundingService {
    public function roundMinutes(int $minutes, string $rule): int {
        return match($rule) {
            'none' => $minutes,
            '5min' => ceil($minutes / 5) * 5,
            '10min' => ceil($minutes / 10) * 10,
            '15min' => ceil($minutes / 15) * 15,
            '30min' => ceil($minutes / 30) * 30,
            default => $minutes,
        };
    }
}
```

---

## 5. Tests

### Unit
- `HourlyRateResolverTest` – Hierarchie, Gültigkeitszeiträume, Fallbacks
- `TimeEntryCalculatorTest` – Multiplikatoren, Währungen, Snapshots
- `RoundingServiceTest` – Alle Regeln, Edge-Cases (0, negative)
- `QuotaEnforcerTest` – Max Hours/Tag/Task, Past/Future Limits

### Integration
- Time Entry CRUD + Approval Workflow
- Invoice Generation aus genehmigten Einträgen
- PDF Generation mit Template

### E2E
- Admin: Time Tracking Settings setzen → User bucht Zeit → Rundung prüfen → Limit-Warnung
- Hourly Rates: Global setzen → Project Override → User bucht → Korrekter Satz in Entry
- Invoice: Entries genehmigen → Rechnung generieren → PDF prüfen → Versenden

---

## 6. Rollout & Migration

### Migrationen
1. `create_time_tracking_settings_table` + Seeder (Defaults)
2. `create_hourly_rates_table` + Seeder (Global Default)
3. `create_time_types_table` + Seeder (Normal, Overtime, Weekend, Holiday)
4. `create_time_entries_table` (Erweiterung bestehender Tabelle)
5. `create_invoices_table` + `create_invoice_items_table`

### Feature-Flags
- `FEATURE_TIME_TRACKING_ADVANCED` – Rundung, Limits, Hierarchie
- `FEATURE_INVOICING` – Rechnungswesen

---

## 7. Offene Fragen

- [ ] **Währungs-Umrechnung:** Historische Kurse speichern oder Live-API? (Snapshot bei Buchung)
- [ ] **Budget-Überwachung:** Projekt-Budget vs. gebuchte Stunden → Alerts? (Später, v2)
- [ ] **Kostenstellen:** Separate Entität oder Custom Field? (Custom Field für Flexibilität)

---

*Status: **Geplant***
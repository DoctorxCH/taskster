# 03 I18n, Lokalisierung & Content-Management (CMS)

**Ziel:** Dynamisches Übersetzungssystem mit DB-Speicher, Redis-Caching, Live-Bearbeitung aller UI-Strings, Platzhalter-Interpolation, Fallback-Kaskade, Rechtstexte, Banner.

---

## 1. Datenbank-Schema

### Tabelle: `locales`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `code` | VARCHAR(10) UNIQUE | `de`, `de-CH`, `en`, `fr`, `it` |
| `name` | VARCHAR(50) | `Deutsch`, `Deutsch (Schweiz)`, `English` |
| `native_name` | VARCHAR(50) | `Deutsch`, `Deutsch (Schweiz)`, `English` |
| `fallback_code` | VARCHAR(10) NULL | Fallback-Locale (z. B. `de-CH` → `de`) |
| `currency` | CHAR(3) | ISO 4217: `EUR`, `CHF`, `USD` |
| `currency_symbol_position` | ENUM('prefix','suffix') DEFAULT 'suffix' | `€100` vs `100€` |
| `decimal_separator` | CHAR(1) DEFAULT ',' | |
| `thousands_separator` | CHAR(1) DEFAULT '.' | |
| `date_format` | VARCHAR(20) DEFAULT 'DD.MM.YYYY' | |
| `time_format` | ENUM('12h','24h') DEFAULT '24h' | |
| `week_start` | TINYINT DEFAULT 1 | 1=Montag, 0=Sonntag |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `is_default` | BOOLEAN DEFAULT FALSE | Eine Default-Locale |
| `sort_order` | INT DEFAULT 0 | |
| `created_at`, `updated_at` | TIMESTAMP | |

### Tabelle: `translations`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `locale_code` | VARCHAR(10) FK | `locales.code` |
| `key` | VARCHAR(200) | Übersetzungsschlüssel (z. B. `tasks.create.success`, `validation.required`) |
| `value` | TEXT | Übersetzter Text mit Platzhaltern (`:attribute`, `:count`, `:user`) |
| `context` | VARCHAR(100) NULL | Kontext-Gruppe: `ui`, `validation`, `email`, `tooltip`, `error` |
| `description` | TEXT NULL | Erklärung für Übersetzer |
| `is_plural` | BOOLEAN DEFAULT FALSE | Plural-Form vorhanden |
| `plural_forms` | JSON NULL | `{"one": "Eine Aufgabe", "other": ":count Aufgaben"}` |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`locale_code`, `key`) | |

### Tabelle: `cms_pages` (Rechtstexte & statische Inhalte)
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `slug` | VARCHAR(100) UNIQUE | `impressum`, `datenschutz`, `agb`, `nutzungsbedingungen` |
| `title` | VARCHAR(200) | |
| `content` | LONGTEXT | Markdown/HTML |
| `locale_code` | VARCHAR(10) FK | |
| `version` | INT DEFAULT 1 | Versionierung |
| `is_published` | BOOLEAN DEFAULT FALSE | |
| `published_at` | TIMESTAMP NULL | |
| `created_at`, `updated_at` | TIMESTAMP | |
| **Unique Index** | (`slug`, `locale_code`) | |

### Tabelle: `announcement_banners`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | |
| `type` | ENUM('info','warning','critical') | |
| `title` | VARCHAR(200) | |
| `message` | TEXT | Markdown erlaubt |
| `locale_code` | VARCHAR(10) NULL | NULL = alle Locales |
| `starts_at` | TIMESTAMP NULL | |
| `ends_at` | TIMESTAMP NULL | |
| `is_dismissible` | BOOLEAN DEFAULT TRUE | User kann schließen |
| `target_roles` | JSON NULL | `["admin","user"]` oder NULL für alle |
| `target_workspaces` | JSON NULL | Mandanten-IDs oder NULL für alle |
| `is_active` | BOOLEAN DEFAULT TRUE | |
| `created_at`, `updated_at` | TIMESTAMP | |

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| **Locales** |
| GET/POST/PUT/DELETE | `/api/admin/locales` | CRUD |
| POST | `/api/admin/locales/{code}/set-default` | Default setzen |
| **Translations** |
| GET | `/api/admin/translations?locale=de&context=ui&q=search` | Liste mit Filter, Suche |
| GET | `/api/admin/translations/export?locale=de` | Export JSON/CSV |
| POST | `/api/admin/translations/import` | Import (Merge/Override) |
| POST | `/api/admin/translations/bulk` | Bulk-Update (Inline-Edit) |
| GET | `/api/admin/translations/missing?locale=de` | Fehlende Keys vs. Default-Locale |
| **CMS Pages** |
| GET/POST/PUT/DELETE | `/api/admin/cms-pages` | CRUD mit Versionierung |
| POST | `/api/admin/cms-pages/{id}/publish` | Veröffentlichen |
| GET | `/api/admin/cms-pages/{slug}/history` | Versionshistorie |
| **Announcement Banners** |
| GET/POST/PUT/DELETE | `/api/admin/announcements` | CRUD |
| POST | `/api/admin/announcements/{id}/toggle` | Aktiv/Inaktiv |

---

## 3. Admin-UI

### Seitenstruktur
```
/admin/settings/i18n
├── Locales/
│   ├── Index.vue          # Liste, Default setzen, Fallback-Kaskade visualisieren
│   └── LocaleForm.vue     # Formate, Währung, Separatoren
├── Translations/
│   ├── Index.vue          # Tabelle mit Inline-Edit, Filter (Context, Missing), Suche
│   ├── TranslationForm.vue # Modal mit Platzhalter-Vorschau
│   ├── ImportExport.vue   # JSON/CSV Import/Export
│   └── MissingKeys.vue    # Fehlende Keys vs. Default anzeigen
├── CmsPages/
│   ├── Index.vue
│   ├── PageEditor.vue     # Markdown-Editor (Toast UI Editor / TipTap) + Live-Preview
│   └── VersionHistory.vue
└── Announcements/
    ├── Index.vue
    └── BannerForm.vue     # Typ, Zeitraum, Zielgruppen, Dismissible
```

### Besondere Features
- **Platzhalter-Vorschau:** Bei `:attribute`, `:count`, `:user`, `:time` Live-Beispiel rendern
- **Plural-Form Editor:** ICU MessageFormat Support (`{count, plural, one {# Aufgabe} other {# Aufgaben}}`)
- **Fallback-Visualisierung:** Baum `de-CH → de → en` mit Farbcodierung (grün=vorhanden, rot=fehlend)
- **Translation Memory:** Ähnliche Keys vorschlagen (Fuzzy-Search)

---

## 4. Business-Logik

### Translation Loader (Runtime)
```php
class TranslationLoader {
    public function load(string $locale): array {
        $cacheKey = "translations:{$locale}";
        $cached = Redis::get($cacheKey);
        if ($cached) return json_decode($cached, true);
        
        // Fallback-Kaskade auflösen
        $chain = $this->getFallbackChain($locale); // ['de-CH', 'de', 'en']
        $translations = [];
        
        foreach ($chain as $loc) {
            $rows = Translation::where('locale_code', $loc)->get(['key', 'value', 'is_plural', 'plural_forms']);
            foreach ($rows as $row) {
                if (!isset($translations[$row->key])) {
                    $translations[$row->key] = $row->is_plural ? $row->plural_forms : $row->value;
                }
            }
        }
        
        Redis::setex($cacheKey, 3600, json_encode($translations));
        return $translations;
    }
    
    private function getFallbackChain(string $locale): array {
        $chain = [$locale];
        $current = $locale;
        while ($fallback = Locale::where('code', $current)->value('fallback_code')) {
            $chain[] = $fallback;
            $current = $fallback;
        }
        return $chain;
    }
}
```

### Vue I18n Integration
```ts
// composables/useI18n.ts
export const useI18n = () => {
  const { locale } = useI18nVue() // Vue I18n
  const translations = ref({})
  
  const loadLocale = async (loc: string) => {
    const data = await $fetch(`/api/i18n/load/${loc}`)
    translations.value = data
    locale.value = loc
  }
  
  const t = (key: string, params?: Record<string, any>) => {
    let text = translations.value[key] || key
    if (params) {
      Object.entries(params).forEach(([k, v]) => {
        text = text.replace(new RegExp(`:${k}`, 'g'), String(v))
      })
    }
    return text
  }
  
  return { t, loadLocale, locale }
}
```

### Cache-Invalidierung
- Bei Translation-Änderung: `redis.del("translations:*")`
- Bei Locale-Änderung: `redis.del("locales:*", "fallback_chain:*")`
- WebSocket Event `i18n:updated` → Clients laden neu

---

## 5. Tests

### Unit
- `TranslationLoaderTest` – Fallback-Kaskade, Plural-Formen, Cache
- `LocaleFormatTest` – Währung, Datum, Zeit, Separatoren
- `PlaceholderInterpolationTest` – Einfache, verschachtelte, fehlende Params

### Integration
- API CRUD + Import/Export Roundtrip
- CMS Page Versionierung (Publish → History → Rollback)
- Banner Targeting (Rollen, Mandanten, Zeitraum)

### E2E
- Superadmin: Locale hinzufügen → Fallback setzen → Translations übersetzen → Frontend Sprache wechseln → Texte prüfen
- CMS: Impressum bearbeiten → Veröffentlichen → Frontend anzeigen
- Banner: Critical Banner erstellen → User sieht es → Dismiss → Nicht mehr angezeigt

---

## 6. Rollout & Migration

### Migrationen
1. `create_locales_table` + Seeder (de, de-CH, en, fr, it)
2. `create_translations_table` + Seeder (bestehende JSON-Files importieren)
3. `create_cms_pages_table` + Seeder (Impressum, Datenschutz, AGB)
4. `create_announcement_banners_table`

### Bestehende i18n-Migration
- `scripts/migrate-i18n-to-db.php` – Liest `locales/*.json` und befüllt `translations`
- Validierung: Alle Keys in Default-Locale (`en`) vorhanden?

### Feature-Flag
- `FEATURE_DB_I18N` – Umschaltung File-basiert → DB-basiert

---

## 7. Offene Fragen

- [ ] **ICU MessageFormat vs. einfache Platzhalter:** Vollständiges ICU (Plural, Select, Date/Number Format) oder nur `:key`? (Empfehlung: ICU für Plural, Rest einfach)
- [ ] **Übersetzungs-Workflow:** Externe Übersetzer einladen (Read-only Links)? (Später, v2)
- [ ] **CMS Versionierung:** Diff-View für Markdown/HTML? (Ja, `diff-match-patch`)

---

*Status: **Geplant***
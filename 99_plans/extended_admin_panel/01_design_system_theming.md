# 01 Design-System, Theming & UI-Tokens

**Ziel:** Alle Styling-Parameter als persistierte CSS-Variablen/Tokens über die Datenbank laden und serverseitig bzw. beim App-Boot in `:root` injizieren. Superadmin kann alle Tokens im Admin-Panel bearbeiten.

---

## 1. Datenbank-Schema

### Tabelle: `design_tokens`
| Spalte | Typ | Beschreibung |
|--------|-----|--------------|
| `id` | BIGINT UNSIGNED PK | Auto-Increment |
| `key` | VARCHAR(100) UNIQUE | Token-Key (z. B. `color-primary-500`, `blur-lg`, `radius-md`) |
| `value` | TEXT | Token-Wert (z. B. `#00A3C4`, `12px`, `saturate(150%)`) |
| `category` | VARCHAR(50) | Kategorie: `color`, `blur`, `opacity`, `saturate`, `border_gradient`, `shadow`, `typography`, `radius`, `branding` |
| `subcategory` | VARCHAR(50) NULL | Unterkategorie (z. B. `primary`, `secondary`, `success`, `elevation_1`) |
| `mode` | ENUM('light','dark','high_contrast','all') DEFAULT 'all' | Für welchen Modus gilt der Token |
| `description` | TEXT NULL | Beschreibung für Admin-UI |
| `is_system` | BOOLEAN DEFAULT FALSE | System-Tokens (nicht löschbar) |
| `created_at` | TIMESTAMP | |
| `updated_at` | TIMESTAMP | |

### Indizes
- `idx_category_mode` ON (`category`, `mode`)
- `idx_key` UNIQUE ON (`key`)

### Seed-Daten (Defaults aus `design-tokens.json` + `design-system.md`)
- Primärfarbe `#00A3C4` (nicht das ungenutzte `brand`-Grün)
- Buttons: `taskster_button`, `taskster_button_accent`, `taskster_button_light`
- Radius: `sm`=4px, `md`=8px, `lg`=12px, `xl`=16px, `full`=9999px
- Shadows: elevation 1–5
- Blur: `none`=0, `sm`=4px, `md`=8px, `lg`=16px, `xl`=24px
- Opacity: 0.00–1.00 Steps
- Saturate: 0%–200%

---

## 2. API-Endpoints

| Methode | Route | Beschreibung |
|---------|-------|--------------|
| GET | `/api/admin/design-tokens` | Alle Tokens (gefiltert nach Kategorie/Mode) |
| GET | `/api/admin/design-tokens/{key}` | Einzelnen Token abrufen |
| POST | `/api/admin/design-tokens` | Token erstellen (Validierung: Key-Format, Wert-Typ) |
| PUT | `/api/admin/design-tokens/{key}` | Token aktualisieren |
| DELETE | `/api/admin/design-tokens/{key}` | Token löschen (nur wenn `is_system=false`) |
| POST | `/api/admin/design-tokens/bulk` | Bulk-Update (für Import/Export) |
| GET | `/api/admin/design-tokens/export` | Export als JSON (für Backup/Transfer) |
| POST | `/api/admin/design-tokens/import` | Import aus JSON (Validierung, Merge-Strategie) |

**Auth:** Nur Superadmin (`role=superadmin`)

**Validierung:**
- `key`: Regex `^[a-z0-9_-]+(-[a-z0-9_-]+)*$` (kebab-case)
- `value`: Je nach Kategorie (Farb-Hex, px/rem, CSS-Funktionen)
- `category`: Enum aus erlaubter Liste

---

## 3. Admin-UI (Vue + Tailwind)

### Seitenstruktur
```
/admin/settings/design-tokens
├── Index.vue          # Übersicht mit Tabs pro Kategorie
├── TokenForm.vue      # Modal/Formular für Create/Edit
├── TokenTable.vue     # Tabelle mit Inline-Edit, Filter, Suche
├── ImportExport.vue   # Import/Export Buttons
└── Preview.vue        # Live-Preview der Änderungen
```

### Komponenten-Details

**Index.vue (Tabs pro Kategorie)**
- Tabs: `Farben`, `Blur/Opacity/Saturate`, `Border Gradients`, `Shadows`, `Typografie`, `Radius`, `Branding`
- Pro Tab: Tabelle mit Spalten `Key`, `Wert`, `Mode`, `Beschreibung`, `Aktionen`
- Filter: Mode (Light/Dark/High-Contrast/All), Suche nach Key
- Bulk-Aktionen: Export, Import, Reset auf Defaults

**TokenForm.vue**
- Felder: Key (readonly bei Edit), Kategorie (Select), Subkategorie (Select), Mode (Radio), Wert (Input je nach Kategorie: Color-Picker, Number+Unit, Textarea für CSS), Beschreibung
- Validierung client- & serverseitig
- Live-Preview rechts neben Formular

**Preview.vue**
- Zeigt Buttons, Cards, Inputs, Alerts mit aktuellen Tokens
- Umschaltbar Light/Dark/High-Contrast
- Aktualisiert sich bei Änderungen im Formular (Reaktivität via Pinia Store)

### State Management (Pinia Store `useDesignTokensStore`)
- `tokens: DesignToken[]` – alle Tokens
- `categories: string[]` – eindeutige Kategorien
- `activeMode: 'light'|'dark'|'high_contrast'`
- `fetchTokens()`, `saveToken()`, `deleteToken()`, `bulkUpdate()`, `importTokens()`, `exportTokens()`
- `generateCSS()` – erzeugt CSS-Variablen-String für `:root`

---

## 4. Business-Logik & Integration

### CSS-Injection beim App-Boot
**Datei:** `app.vue` oder `plugins/design-tokens.client.ts`

```ts
// Pseudocode
export default defineNuxtPlugin(async () => {
  const tokens = await $fetch('/api/admin/design-tokens?mode=all')
  const cssVars = tokens.map(t => `--${t.key}: ${t.value};`).join('\n')
  const style = document.createElement('style')
  style.textContent = `:root { ${cssVars} }`
  document.documentElement.appendChild(style)
})
```

**Alternative (SSR-freundlich):** Server-seitig in `server/routes/design-tokens.ts` CSS-String rendern und in `app.html` via `{% head %}` injizieren.

### Cache-Invalidierung
- Redis Key: `design:tokens:{mode}` → TTL 1 Stunde
- Bei POST/PUT/DELETE: `redis.del('design:tokens:*')`
- Client-seitig: Store-Invalidierung + `window.dispatchEvent(new Event('design-tokens-updated'))`

### Tailwind-Integration
- `tailwind.config.ts` liest CSS-Variablen zur Build-Zeit **nicht** (dynamisch)
- Stattdessen: Tailwind `theme.extend` leer lassen, alle Werte via `var(--token-key)` in Komponenten nutzen
- Beispiel: `bg-[var(--color-primary-500)]`, `rounded-[var(--radius-md)]`

### Fallback bei fehlenden Tokens
- CSS `@property` mit `initial-value` für kritische Tokens
- Oder: Default-Werte in `:root` im Global-CSS definieren, DB-Tokens überschreiben diese

---

## 5. Tests

### Unit Tests (Vitest)
- `designTokensStore.test.ts` – Store-Actions, CSS-Generierung
- `tokenValidation.test.ts` – Key-Format, Wert-Validierung pro Kategorie

### Integration Tests
- API-Endpoints (CRUD, Bulk, Import/Export)
- CSS-Injection: Prüfen, ob `:root` korrekte Variablen enthält nach Boot
- Mode-Switch: Light → Dark → High-Contrast Tokens werden korrekt geladen

### E2E Tests (Playwright)
- Superadmin login → Design-Tokens Seite → Token ändern → Preview prüfen → Speichern → Reload → Token persistent
- Import/Export Roundtrip
- Reset auf Defaults

---

## 6. Rollout & Migration

### Migration (Laravel/PHP Migration)
```php
Schema::create('design_tokens', function (Blueprint $table) {
    $table->id();
    $table->string('key', 100)->unique();
    $table->text('value');
    $table->string('category', 50);
    $table->string('subcategory', 50)->nullable();
    $table->enum('mode', ['light','dark','high_contrast','all'])->default('all');
    $table->text('description')->nullable();
    $table->boolean('is_system')->default(false);
    $table->timestamps();
    $table->index(['category', 'mode']);
});
```

### Seeder
- `DesignTokensSeeder.php` – liest `design-tokens.json` + `design-system.md` und befüllt Tabelle mit `is_system=true`

### Feature-Flag
- `FEATURE_DESIGN_TOKENS_DB` – schaltet DB-Tokens ein/aus (Fallback: statische CSS-Variablen in `assets/css/tokens.css`)

### Deployment-Schritte
1. Migration & Seeder ausführen
2. API-Endpoints deployen
3. Admin-UI deployen (Feature-Flag aus)
4. CSS-Injection Plugin aktivieren
5. Feature-Flag einschalten
6. Smoke-Test: Admin-Panel → Design-Tokens → Preview → Live-Seite prüfen

---

## 7. Offene Fragen / Entscheidungen

- [ ] **SSR vs. Client-only Injection:** Nuxt 3 `useHead` vs. Client-Plugin? (Empfehlung: Client-Plugin für dynamische Updates ohne Rebuild)
- [ ] **Token-Naming-Konvention:** `color-primary-500` vs. `primary-500`? (Vorschlag: Präfix `color-`, `blur-`, `shadow-` für Klarheit)
- [ ] **Vererbung Light→Dark:** Sollen Dark-Mode-Tokens optional sein und von Light fallen? (Ja, `mode='all'` als Default, Dark nur bei Abweichung)
- [ ] **Versionierung:** Sollen Token-Änderungen versioniert werden (Audit-Log)? (Ja, separate Tabelle `design_token_versions`)

---

## 8. Nächste Schritte (für Session 1)

1. **Migration & Seeder erstellen** → `php artisan make:migration create_design_tokens_table`
2. **API-Controller & Routes** → `php artisan make:controller Api/Admin/DesignTokenController --api`
3. **Pinia Store & Types** → `composables/useDesignTokensStore.ts`, `types/design-token.ts`
4. **Admin-UI Grundgerüst** → Seiten unter `pages/admin/settings/design-tokens/`
5. **CSS-Injection Plugin** → `plugins/design-tokens.client.ts`
6. **Tests schreiben** → Unit + Integration
7. **Dokumentation im Admin-Panel** → Tooltips, Hilfetexte

---

*Status: **Geplant** – Bereit für Implementierung in Session 1.*
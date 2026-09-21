# Korrektur: Behebung aller IDE-Typfehler & Nuxt Auto-Imports

- **Datum:** 2026-09-22
- **Betroffene Dateien:**
  - `tsconfig.json` (Neu angelegt mit Verweis auf `.nuxt/tsconfig.json` und `.nuxt/nuxt.d.ts`)
  - `pages/admin/index.vue`
  - `pages/projects/[id].vue`
  - `pages/dashboard.vue`

## Ursachen & Durchgeführte Maßnahmen
1. **Root `tsconfig.json`, `types/global.d.ts` & Nuxt 3 Ambient Types:**
   - Nuxt generiert `.nuxt/tsconfig.json` und `.nuxt/nuxt.d.ts`.
   - `types/global.d.ts` angelegt mit Referenzen auf `.nuxt/types/imports.d.ts`, `.nuxt/types/plugins.d.ts`, `.nuxt/types/i18n-plugin.d.ts` und einer expliziten Erweiterung von `ComponentCustomProperties` für `$t`, `$rt`, `$n`, `$d`, `$tm`, `$te`, `$i18n`, damit Volar in Vue-Templates `$t(...)` direkt auf der Instanz erkennt.
   - `tsconfig.json` konfiguriert mit `include: [".nuxt/nuxt.d.ts", ".nuxt/imports.d.ts", ".nuxt/types/**/*", "types/**/*", "**/*"]`.

2. **`pages/admin/index.vue`:**
   - Typ für `activeTab` um `'invites'` erweitert (`'users' | 'companies' | 'finance' | 'templates' | 'email' | 'invites'`), da der Tab für Mitarbeiter-Einladungen existiert und verglichen wurde (`activeTab === 'invites'`).
   - `activeSectionBadge`, `activeSectionTitle`, `activeSectionDescription`, `setTab` und `syncTabFromRoute` um `'invites'` ergänzt.

3. **`pages/projects/[id].vue`:**
   - Typo `loadProject()` in `onVoiceNoteSaved` zu `loadProjectData()` korrigiert.
   - Template-Ref `$refs.csvFileInput?.click()` zu `csvFileInput?.click()` korrigiert, wodurch die typisierte Referenz genutzt wird.
   - Semicolon/Syntax-Konflikt bei `const taskPayload = { ... }` gefolgt von `(Object.entries(...))` aufgelöst durch Umwandlung in eine saubere `for (const [colIdxStr, targetField] of mappingEntries)` Schleife.
   - Typ-Casting für `Object.entries(importColumnMapping.value) as [string, string][]` ergänzt, sodass `.startsWith()` und `.replace()` auf `targetField` typkonform aufgerufen werden.
   - Explizite Typ-Annotationen für Lambda-Parameter ergänzt (`c: any`, `o: string`, `acc: number, l: any`, `entry: any`, `item: any`, `s: string`, `sec: any, idx: number`, `u: any`, `d: any`, `header: string, idx: number`, `val: string`, `id: any`).

4. **`pages/dashboard.vue`:**
   - Explizite Typ-Annotationen für Lambda-Parameter ergänzt (`t: any`, `acc: number, f: any`, `a: any, b: any`, `n: any`).

5. **Validierung & Deployment:**
   - `npm run build:dist` erfolgreich ohne Fehler ausgeführt (`vite v7.3.6`, nitro static prerenderer, sync nach Git-Root).
   - `python generate_index.py` aktualisiert.

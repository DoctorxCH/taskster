# Fix: Zusammengestauchtes Layout nach Hard-Reload (CTRL + SHIFT + R)

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `pages/admin/index.vue`
  - `nuxt.config.ts`

## Problembeschreibung
Nach einem Hard-Reload mit Cache-Bypass (`CTRL + SHIFT + R`) auf `/admin` erschien die gesamte Seite in einer extrem schmalen, zentrierten Box (~400px / `max-w-md`) zusammengestaucht, Tabelleninhalte und Metriken liefen über. Nach einem normalen Reload (`F5`) war die Darstellung wieder korrekt bei `max-w-6xl` (1152px).

## Ursachenanalyse
1. **Hydration Mismatch durch Multi-Root Fragment mit `v-if` / `v-else`:**
   - In `pages/admin/index.vue` lagen `v-if="isAnyAdmin"` (`class="max-w-6xl ..."`) und `v-else` (`class="max-w-md ..."`) direkt auf der Root-Ebene des `<template>`.
   - Beim statischen Build (`nuxt generate`) ohne angemeldeten Benutzer wurde `v-else` ("Zugriff verweigert") mit `class="max-w-md mx-auto py-24 text-center"` in `admin/index.html` vorgerendert.
   - Beim Hard-Reload lädt der Browser die Datei `admin/index.html` direkt vom Server.
   - Vue hydratisierte das Root-Element. Sobald der Login-Status aus dem Client-State (`useAuth`) ermittelt war, schaltete Vue auf `v-if` um.
   - Durch den Hydration-Mismatch auf Root-Fragmentebene wurde die Klasse `max-w-md` nicht vom DOM-Knoten gelöscht, sondern verblieb darauf.
   - Da im CSS (Tailwind) `max-w-md` (`max-width: 28rem = 448px`) nach `max-w-6xl` (`max-width: 72rem = 1152px`) deklariert ist, überschrieb `max-w-md` die Breite und stauchte das gesamte Dashboard auf 448px zusammen.
2. **Fehlende `ssr: false` Deklaration für geschützte App-Routen:**
   - In `nuxt.config.ts` war nur `/settings` als `ssr: false` definiert.
   - Geschützte Routen (`/admin`, `/company`, `/dashboard`, etc.) wurden vom SSR-Generator vorgerendert, obwohl Benutzerdaten rein clientseitig (`localStorage`) vorliegen.

## Durchgeführte Änderungen
1. **`pages/admin/index.vue`:**
   - Single-Root Wrapper `<div class="w-full">` um das Template gelegt.
   - `v-if="isAnyAdmin"` und `v-else` sind nun Kind-Elemente; ein Hydration-Klassen-Merge auf das Hauptlayout ist damit strukturell unmöglich.
2. **`nuxt.config.ts`:**
   - `routeRules` erweitert: Alle geschützten, tokenbasierten Anwendungsrouten (`/admin/**`, `/company/**`, `/dashboard`, `/calendar`, `/contacts/**`, `/time`, `/settings`, `/projects/**`, `/folders/**`) auf `ssr: false` gesetzt.
   - Dadurch wird bei Hard-Reloads ein sauberer SPA-Shell-Container ohne vorgerenderte Zugriff-Verweigert-Box ausgeliefert.

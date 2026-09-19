# Taskster Design System — Verbindliche Style-Referenz

> **Für KI-Agenten:** Diese Datei ist die vollständige Design-Referenz. Lies sie, bevor du
> UI änderst. Maschinenlesbare Version: [`design-tokens.json`](./design-tokens.json).
> **Bei jeder Design-Änderung beide Dateien aktualisieren.**

---

## 1. Design-Sprache

**MeisterTask-inspiriert + Liquid Glass.**

Das Besondere an Taskster: Alle Flächen liegen über einem **wechselnden Hintergrundbild**
(15 Wallpapers, vom Nutzer wählbar). Deshalb ist Lesbarkeit die härteste Anforderung —
jede Karte braucht Transparenz + `backdrop-blur` oder ausreichende Deckkraft.

```
┌─────────────────────────────────────────┐
│  Wallpaper (z-0, fixed, brightness .92) │
│  ┌───────────────────────────────────┐  │
│  │ Overlay bg-slate-900/15           │  │
│  │  ┌─────────────────────────────┐  │  │
│  │  │ Navbar (z-40, liquid_glass) │  │  │
│  │  ├─────────────────────────────┤  │  │
│  │  │ Content (z-10)  │ Sidebar   │  │  │
│  │  │ liquid_glass    │ (z-30)    │  │  │
│  │  └─────────────────────────────┘  │  │
│  └───────────────────────────────────┘  │
└─────────────────────────────────────────┘
```

---

## 2. Farben

### Markenfarbe

| Token | Wert | Verwendung |
|---|---|---|
| **Primary** | `#00A3C4` | Buttons, Links, aktive Tabs, Fokus |
| **Primary Hover** | `#008ba8` | Hover-Zustand |
| **Accent (Destructive)** | `rose-600` / `rose-500` | Löschen, Stoppen, Warnungen |

> ⚠️ **Achtung:** In `tailwind.config.ts` ist ein `brand`-Objekt (grün) definiert — das ist
> **ungenutzt**. Die echte Markenfarbe ist `#00A3C4` (Cyan/Teal).

### Gradienten

| Name | Klassen |
|---|---|
| Avatar | `bg-gradient-to-tr from-cyan-600 to-teal-500` |
| Login-Panel | `bg-gradient-to-br from-cyan-600 to-teal-700` |

### Text

| Rolle | Klasse |
|---|---|
| Überschrift | `text-slate-900` |
| Fließtext | `text-slate-700` |
| Sekundär | `text-slate-600` |
| Meta | `text-slate-500` |
| Gedämpft | `text-slate-400` |
| Auf dunkel | `text-white` |

### Status (Aufgaben)

| Status | Hintergrund | Text | Border | Label |
|---|---|---|---|---|
| `todo` | `slate-100` | `slate-600` | `slate-200` | Zu erledigen |
| `in_progress` | `cyan-50` | `cyan-700` | `cyan-200` | In Arbeit |
| `review` | `amber-50` | `amber-700` | `amber-200` | In Prüfung |
| `done` | `emerald-50` | `emerald-700` | `emerald-200` | Abgeschlossen |

### Priorität

| Wert | Hintergrund | Text | Border | Emoji |
|---|---|---|---|---|
| `niedrig` | `emerald-50` | `emerald-800` | `emerald-300` | 🟢 |
| `normal` | `white` | `slate-800` | `slate-300` | 🔵 |
| `hoch` | `amber-50` | `amber-800` | `amber-300` | 🟠 |
| `dringend` | `rose-50` | `rose-800` | `rose-300` | 🔴 |

### Rollen-Badges

| Rolle | Klassen | Label |
|---|---|---|
| Superadmin | `bg-purple-100 text-purple-700 border-purple-200` | SUPERADMIN |
| Plattform-Admin | `bg-purple-100 text-purple-700 border-purple-200` | PLATFORM ADMIN |
| Company Admin | `bg-emerald-100 text-emerald-800 border-emerald-300` | COMPANY ADMIN |
| Firmenmitglied | `bg-emerald-50 text-emerald-700 border-emerald-200` | \<Firmenname\> |
| Pro | `bg-amber-50 text-amber-700 border-amber-200` | PRO PLAN |
| Free | `bg-slate-100 text-slate-600 border-slate-200` | FREE PLAN |

### Semantische Flächen

| Zweck | Klassen |
|---|---|
| Erfolg | `bg-emerald-500/15 border-emerald-300 text-emerald-950` |
| Fehler | `bg-rose-500/15 border-rose-300 text-rose-950` |
| Warnung | `bg-amber-50 border-amber-200 text-amber-800` |
| Info | `bg-cyan-50 border-cyan-200 text-cyan-900` |

### Aufgaben-Farbpalette (Nutzer-wählbar)

`#00A3C4` Cyan · `#8B5CF6` Lila · `#F59E0B` Amber · `#10B981` Smaragd ·
`#EF4444` Rot · `#EC4899` Pink · `#6366F1` Indigo · `#64748B` Slate

### Abschnitts-Pastellfarben

`rgba(238,242,255,.95)` Indigo · `rgba(236,253,245,.95)` Mint · `rgba(254,243,199,.95)` Amber ·
`rgba(255,241,242,.95)` Rose · `rgba(243,232,255,.95)` Lila · `rgba(240,253,250,.95)` Cyan ·
`rgba(241,245,249,.95)` Slate

---

## 3. Typografie

**Font:** `font-sans` (Tailwind default), `antialiased`

| Rolle | Klassen | Verwendung |
|---|---|---|
| Seiten-Titel | `text-2xl sm:text-3xl font-black tracking-tight` | H1 |
| Hero-Titel | `text-2xl sm:text-4xl font-black tracking-tight` | Dashboard |
| Sektions-Titel | `text-base font-black tracking-tight` | Karten-Überschrift |
| Karten-Titel | `text-sm font-black` | Spalten, Karten |
| Fließtext | `text-xs` | Standard |
| Fließtext groß | `text-xs sm:text-sm` | Drawer/Modal |
| Meta | `text-[11px]` | Hilfstexte |
| Mikro | `text-[10px]` | Tabellen-Header, Badges |
| Nano | `text-[9px]` | Chips |
| KPI-Zahl | `text-2xl sm:text-3xl font-black tracking-tight` | Metriken |
| Mono | `font-mono font-black` | Timer, IDs |

**Gewichte:** `font-black` (Überschriften/Badges) · `font-bold` (Labels) ·
`font-semibold` (Buttons) · `font-medium` (Hilfstexte)

**Tracking:** `tracking-tight` (Überschriften) · `tracking-wider` (UPPERCASE-Labels)

**UPPERCASE-Label-Standard:**
```
text-[10px] font-black uppercase tracking-wider text-slate-500
```

---

## 4. Abstände & Größen

| Element | Klassen |
|---|---|
| Seiten-Container | `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8` |
| Karten-Padding | `p-6 sm:p-8` |
| Karten-Padding kompakt | `p-4 sm:p-6` |
| Sektions-Abstand | `space-y-6` / `space-y-8` |
| Grid-Abstand | `gap-4` / `gap-5` / `gap-6` |
| Navbar-Höhe | `h-16` |
| Sidebar | `w-16 hover:w-56`, `h-[calc(100vh-4rem)]` |

### Control-Höhen

| Größe | Klasse | Verwendung |
|---|---|---|
| Standard | `h-[42px]` | Alle Haupt-Buttons |
| Kompakt | `h-[38px]` | Modal-Footer |
| Klein | `h-[36px]` | Inline-Aktionen |
| Winzig | `h-[34px]` | Tabellen-Buttons |
| Mikro | `h-[30px]` | Mini-Buttons |

---

## 5. Radien & Schatten

| Element | Radius |
|---|---|
| Karte | `rounded-3xl` |
| Innere Fläche | `rounded-2xl` |
| Control | `rounded-xl` |
| Klein | `rounded-lg` |
| Chip | `rounded-md` |
| Pill | `rounded-full` |

| Zweck | Schatten |
|---|---|
| Karte | `shadow-xl` |
| Karte weich | `shadow-lg` |
| Control | `shadow-sm` |
| Mikro | `shadow-xs` |
| Modal | `shadow-2xl` |
| Primary-Button | `shadow-sm shadow-cyan-900/10` |
| Accent-Button | `shadow-sm shadow-rose-900/10` |

---

## 6. Komponenten

### 6.1 Buttons — **VERBINDLICHER STANDARD**

> **Standardgröße: `px-6 text-xs h-[42px] rounded-lg`**
> Abweichungen nur bei Icon-Buttons oder Tabellen-Aktionen.

| Typ | Klasse | Verwendung |
|---|---|---|
| **Primary** | `taskster_button` | Speichern, Erstellen, Bestätigen |
| **Accent** | `taskster_button_accent` | Löschen, Entfernen, Stoppen |
| **Light** | `taskster_button_light` | Abbrechen, Sekundär, Filter |

```html
<!-- Primary -->
<button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
  <span>+ Aufgabe erfassen</span>
</button>

<!-- Accent (destruktiv) -->
<button class="taskster_button_accent px-6 text-xs h-[42px] rounded-lg">
  Löschen
</button>

<!-- Light (Abbrechen) -->
<button class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">
  Abbrechen
</button>

<!-- Tabellen-Aktion -->
<button class="taskster_button_light px-4 text-xs h-[34px] rounded-lg shadow-xs">
  Einstellungen
</button>
```

**Definitionen** (in `app.vue`):

```css
.taskster_button {
  @apply bg-[#00A3C4] hover:bg-[#008ba8] text-white font-semibold transition
         inline-flex items-center justify-center space-x-2 shadow-sm shadow-cyan-900/10
         cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_accent {
  @apply bg-rose-600 hover:bg-rose-500 text-white font-semibold transition
         inline-flex items-center justify-center space-x-2 shadow-sm shadow-rose-900/10
         cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
.taskster_button_light {
  @apply bg-white hover:bg-slate-50 text-slate-800 border-[3px] border-[#00A3C4] font-semibold
         transition inline-flex items-center justify-center space-x-2 shadow-sm
         cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed active:scale-[0.99];
}
```

### 6.2 Liquid Glass — drei Varianten

| Klasse | Deckkraft | Blur | Verwendung |
|---|---|---|---|
| `liquid_glass` | 30 % | 28px | Große Container, Hero, Board-Spalten |
| `liquid_glass_pill` | 88 % | 20px | Breadcrumbs, Tab-Leisten, Banner |
| `liquid_glass_card` | 90 % | 24px | KPI-Kacheln, kleine Karten |

```css
.liquid_glass {
  background: rgba(255, 255, 255, 0.30);
  backdrop-filter: blur(28px) saturate(180%);
}
.liquid_glass_pill {
  background: rgba(255, 255, 255, 0.88);
  backdrop-filter: blur(20px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.80);
  box-shadow: 0 4px 16px 0 rgba(0,0,0,0.06), 0 1px 0 0 rgba(255,255,255,0.95) inset;
}
.liquid_glass_card {
  background: rgba(255, 255, 255, 0.90);
  backdrop-filter: blur(24px) saturate(180%);
  border: 1px solid rgba(255, 255, 255, 0.85);
  box-shadow: 0 10px 24px 0 rgba(0,0,0,0.07), 0 1px 0 0 rgba(255,255,255,0.98) inset;
}
```

**Faustregel:** Je mehr Inhalt, desto höher die Deckkraft.

### 6.3 Navbar

```html
<header class="liquid_glass sticky top-0 z-40 text-slate-900 shadow-md
               transition-colors border-b border-white/60">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
```

| Zustand | Klassen |
|---|---|
| Link aktiv | `bg-white text-[#00A3C4] shadow-sm font-extrabold` |
| Link inaktiv | `text-slate-600 hover:text-slate-900 hover:bg-white/60` |

### 6.4 Sidebar (rechts, ausklappbar)

```html
<aside class="hidden md:flex flex-col w-16 hover:w-56 bg-white/80 hover:bg-white/95
              backdrop-blur-xl border-l border-white/50 shadow-2xl
              transition-all duration-300 ease-in-out group/sidebar z-30
              sticky top-16 h-[calc(100vh-4rem)] select-none shrink-0">
```

| Zustand | Klassen |
|---|---|
| Item aktiv | `bg-cyan-50 text-[#00A3C4] font-bold shadow-xs` |
| Item inaktiv | `text-slate-700 hover:text-[#00A3C4] hover:bg-cyan-50/80` |
| Label | `hidden group-hover/sidebar:inline text-xs font-bold whitespace-nowrap` |

### 6.5 Karten

| Typ | Klassen |
|---|---|
| Glass | `liquid_glass rounded-3xl p-6 sm:p-8 shadow-xl` |
| Solid | `bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm` |
| KPI | `p-4 rounded-2xl liquid_glass_card text-center` |
| KPI farbig | `p-4 rounded-2xl bg-cyan-50/50 border border-cyan-100` |
| Hover | `liquid_glass_card hover:border-[#00A3C4] rounded-3xl p-6 shadow-md hover:shadow-xl transition` |

### 6.6 Inputs

```html
<!-- Auf Wallpaper (Standard) -->
<input class="w-full px-3.5 py-2.5 bg-white/70 border border-white/60 rounded-xl
              text-xs text-slate-900 focus:bg-white/90 focus:outline-none
              focus:border-cyan-600 backdrop-blur-sm" />

<!-- Auf weißer Karte -->
<input class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-xl
              text-xs text-slate-900 focus:bg-white focus:outline-none
              focus:border-cyan-600" />

<!-- Disabled -->
<input class="w-full px-3.5 py-2.5 bg-white/30 border border-white/40 rounded-xl
              text-xs text-slate-600 cursor-not-allowed font-medium backdrop-blur-sm" />
```

| Element | Klassen |
|---|---|
| Label | `block text-xs font-bold text-slate-700 mb-1` |
| Hinweis | `text-[11px] text-slate-500 mt-1` |
| Checkbox | `w-4 h-4 rounded border-slate-300 text-cyan-600 focus:ring-0 cursor-pointer` |

### 6.7 Tabellen

| Element | Klassen |
|---|---|
| Wrapper | `overflow-x-auto` |
| Table | `w-full text-left text-xs` |
| Thead | `bg-white/60 text-slate-600 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80` |
| Th | `py-3.5 px-4` |
| Tbody | `divide-y divide-slate-200/60 text-slate-800` |
| Row | `hover:bg-white/60 transition` |
| Td | `py-3.5 px-4` |

### 6.8 Badges

```html
<!-- Pill -->
<span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border">
  COMPANY ADMIN
</span>

<!-- Chip -->
<span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-white/90
             border border-slate-200 text-slate-700">
  3 Listen
</span>

<!-- Zähler -->
<span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-white/90
             text-slate-700 shadow-xs border border-slate-200/60">
  12
</span>
```

### 6.9 Tabs

**Variante A — Pill-Leiste:**

```html
<div class="liquid_glass_pill rounded-2xl px-4 py-1.5 mb-6 flex items-center
            space-x-3 overflow-x-auto shadow-sm">
  <button class="py-2 px-3 rounded-xl text-xs font-bold transition flex items-center
                 space-x-2 cursor-pointer shrink-0 bg-white text-purple-800 shadow-sm">
    <span>👤</span><span>Benutzerverwaltung</span>
  </button>
</div>
```

**Variante B — Unterstrich:**

```html
<button class="py-3.5 text-xs border-b-2 transition flex items-center space-x-1.5
               whitespace-nowrap cursor-pointer border-[#00A3C4] text-[#00A3C4] font-black">
```

### 6.10 Modals

```html
<div class="fixed inset-0 z-50 flex items-center justify-center p-4
            bg-slate-900/50 backdrop-blur-sm">
  <div class="bg-white border border-slate-200 rounded-3xl max-w-md w-full
              p-6 sm:p-8 shadow-2xl">
    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-6">
      <h3 class="text-lg font-black text-slate-900">Titel</h3>
      <button class="text-slate-400 hover:text-slate-700 text-lg font-bold p-1 rounded-lg">✕</button>
    </div>
    <!-- Inhalt -->
    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-slate-100">
      <button class="taskster_button_light px-6 text-xs h-[42px] rounded-lg">Abbrechen</button>
      <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">Speichern</button>
    </div>
  </div>
</div>
```

**Großes Detail-Modal (Task-Drawer):** `max-w-4xl max-h-[92vh] flex flex-col overflow-hidden`,
Overlay `bg-slate-950/60 backdrop-blur-md`.

### 6.11 Breadcrumb

```html
<div class="inline-flex items-center space-x-2 text-xs text-slate-700 font-semibold
            px-4 py-2 rounded-2xl liquid_glass_pill">
  <NuxtLink to="/dashboard" class="hover:text-cyan-700 transition flex items-center space-x-1">
    <span>🏠</span><span>Dashboard</span>
  </NuxtLink>
  <span class="text-slate-400">/</span>
  <span class="text-slate-900 font-bold flex items-center space-x-1">
    <span>🏢</span><span>Firmen-Administration</span>
  </span>
</div>
```

### 6.12 Feedback-Banner

```html
<div class="mb-6 p-4 rounded-2xl bg-emerald-500/15 border border-emerald-300
            text-emerald-950 text-xs font-bold liquid_glass_pill">
  Gespeichert.
</div>
```

### 6.13 Avatare

| Typ | Klassen |
|---|---|
| Gradient | `w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-600 to-teal-500 text-white flex items-center justify-center text-xs font-black shadow-sm` |
| Stack | `inline-block w-5 h-5 rounded-full ring-1 ring-white bg-gradient-to-tr from-cyan-600 to-teal-500 text-white text-[9px] font-black flex items-center justify-center` |
| Initialen | `w-9 h-9 rounded-full bg-cyan-100 text-cyan-800 font-bold flex items-center justify-center text-xs border border-cyan-200` |

### 6.14 Fortschrittsbalken

```html
<div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
  <div class="h-1.5 rounded-full transition-all bg-cyan-600" style="width: 65%"></div>
</div>
```

| Zustand | Farbe |
|---|---|
| OK | `bg-cyan-600` |
| Warnung (≥70 %) | `bg-amber-500` |
| Überschritten | `bg-rose-500` |

### 6.15 Stoppuhr-Widget

```html
<div class="flex items-center space-x-2.5 px-3.5 py-1.5 rounded-2xl bg-slate-900/90
            border border-cyan-500/50 text-white shadow-lg backdrop-blur-md">
  <span class="w-2.5 h-2.5 rounded-full bg-rose-500 animate-pulse"></span>
  <span class="font-mono font-black text-xs sm:text-sm tracking-wider text-cyan-300">01:23:45</span>
  <button class="px-2.5 py-1 rounded-lg bg-rose-600 hover:bg-rose-500 text-white
                 font-black text-[11px] shadow-xs transition transform hover:scale-105">
    ⏹️ Stoppen
  </button>
</div>
```

### 6.16 Empty State

```html
<div class="text-center py-16 px-6">
  <div class="w-16 h-16 rounded-2xl bg-cyan-50 text-[#00A3C4] flex items-center
              justify-center text-2xl font-black mx-auto mb-3 shadow-xs">⏱️</div>
  <h3 class="text-sm font-black text-slate-800">Keine Einträge gefunden</h3>
  <p class="text-xs text-slate-500 max-w-sm mx-auto mt-1 mb-6">Beschreibung…</p>
  <button class="taskster_button px-6 text-xs h-[42px] rounded-lg">Aktion</button>
</div>
```

---

## 7. Layout & z-Index

| Ebene | z-Index | Element |
|---|---|---|
| Wallpaper | `z-0` | `fixed inset-0 pointer-events-none` |
| Content | `z-10` | Hauptbereich |
| Sidebar | `z-30` | Rechte Leiste |
| Navbar | `z-40` | `sticky top-0` |
| Modal | `z-50` | Overlay |

```html
<!-- App-Shell -->
<div class="min-h-screen relative flex flex-col font-sans antialiased
            text-slate-900 selection:bg-cyan-500 selection:text-white">
  <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
    <img class="w-full h-full object-cover object-center filter
                brightness-[0.92] contrast-[1.03] transition-all duration-700 ease-out" />
    <div class="absolute inset-0 bg-slate-900/15 backdrop-blur-[0.5px]"></div>
  </div>
  <div class="relative z-10 flex flex-col min-h-screen">
    <!-- Navbar + Content + Sidebar -->
  </div>
</div>
```

---

## 8. Wallpapers

- **Storage-Key:** `taskster_wallpaper`
- **Default:** `/wallpapers/mountain-lake.jpg`
- **Anzahl:** 15 (Kategorien: `nature`, `abstract`, `space`)
- **Regel:** Design muss auf **allen** Wallpapers lesbar bleiben.

---

## 9. Regeln

### ✅ Immer

1. Buttons in Standardgröße `px-6 text-xs h-[42px] rounded-lg` (außer Icon/Tabelle).
2. Primärfarbe ist `#00A3C4` — keine andere Blau-Nuance.
3. Flächen über dem Wallpaper: Liquid-Glass-Klasse **oder** `bg-white` mit Deckkraft.
4. Überschriften `font-black tracking-tight`, Labels `uppercase tracking-wider`.
5. Status-Farben exakt aus Abschnitt 2.
6. Priorität exakt aus Abschnitt 2.
7. Modals: Overlay `bg-slate-900/50 backdrop-blur-sm`, Panel `bg-white rounded-3xl shadow-2xl`.
8. Abbrechen = `taskster_button_light`, Bestätigen = `taskster_button`.
9. Löschen = `taskster_button_accent`.
10. Deutsche UI-Texte, `de-CH`-Format, Währung CHF als Default.

### ❌ Nie

1. Neue Farben erfinden.
2. `bg-white` ohne Liquid Glass auf großen Flächen.
3. Eckige Ecken (`rounded-none`) — Minimum `rounded-lg`.
4. Schriftgrößen unter `text-[9px]`.
5. `font-normal` für Überschriften oder Badges.
6. Inline-Styles für Farben (außer dynamische Werte wie `task.color`).
7. Dark-Mode-Code (definiert, aber nicht implementiert).

### ♿ Barrierefreiheit

- Text auf Liquid Glass: `slate-900`/`slate-700` (nicht `slate-400`).
- Interaktive Elemente: `cursor-pointer`.
- Disabled: `disabled:opacity-50 disabled:cursor-not-allowed`.
- Fokus: `focus:outline-none focus:border-cyan-600` bzw. `focus:ring-2 focus:ring-[#00A3C4]`.
- Icon-Buttons brauchen `title`.

---

## 10. Quelldateien

| Datei | Inhalt |
|---|---|
| `app.vue` (`<style>`) | `taskster_button*`, `liquid_glass*` |
| `tailwind.config.ts` | `brand`/`taskster` (brand ungenutzt) |
| `composables/useWallpaper.ts` | Wallpaper-Liste |
| `pages/dashboard.vue` | Referenz: Karten, KPI, Todos |
| `pages/projects/[id].vue` | Referenz: Board, Drawer, Modals, Tabellen |
| `pages/time.vue` | Referenz: KPI-Karten, Filter, Tabelle |
| `pages/settings.vue` | Referenz: Formulare |
| `pages/admin/index.vue` | Referenz: Tabs, Tabellen, Modals |
| `pages/company/index.vue` | Referenz: Tabs, Karten, Formulare |
| `pages/login.vue` | Referenz: Split-Layout, Gradient |
| `components/Navbar.vue` | Referenz: Navbar, Badges, Stoppuhr |

---

## 11. Prompt-Vorlage für KI

> „Passe das Design an: **[Beschreibung]**.
> Halte dich strikt an `99_anweisungen/design-tokens.json` und
> `99_anweisungen/design-system.md`. Verwende ausschließlich die definierten
> Farben, Button-Klassen (`taskster_button*`), Liquid-Glass-Varianten und
> Größenstandards. Keine neuen Farben, keine eckigen Ecken, keine
> Schriftgrößen unter 9px. UI-Texte auf Deutsch."

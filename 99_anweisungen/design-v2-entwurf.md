# Taskster Design v2 — Entwurf (Clean & Professional)

> **Status:** ENTWURF — noch nicht implementiert.
> **Richtung:** A — Clean & Professional (Vorbilder: Linear, Notion, Stripe, Height)
> **Wallpaper:** optional (Standard: dezenter fester Hintergrund)
> **Icons:** Lucide (`lucide-vue-next`, bereits installiert)
> **Ziel:** Ruhig, seriös, gut lesbar. Weg von Emoji-Spielerei und Glas-Optik.

---

## 1. Warum ein Redesign?

### Kritik am aktuellen Design

| Problem | Aktuell | Auswirkung |
|---|---|---|
| **Emoji-Überladung** | 115 Emojis im Code (🫡 📅 🔍 ⚡ 🏠 📁 ⏱️ ⚙️ 👤 🖼️ 🎨) | Wirkt kindlich, inkonsistent über OS/Browser |
| **Wallpaper-Zwang** | 15 Hintergründe, Design muss überall lesbar sein | Erzwingt Glas + hohe Deckkraft → Kompromisse überall |
| **Farb-Chaos** | cyan, teal, purple, emerald, amber, rose, indigo, pink, slate gleichzeitig | Keine klare Hierarchie, unruhig |
| **Zu kleine Schrift** | `text-[9px]`, `text-[10px]` als Standard | Schlecht lesbar, besonders auf Baustelle/mobil |
| **Radien-Wildwuchs** | `rounded-3xl/2xl/xl/lg/md/full` gemischt | Kein erkennbares System |
| **Schatten-Inflation** | `shadow-2xl/xl/lg/md/sm/xs` gleichzeitig | „Schwebende" Optik, unruhig |
| **MeisterTask-Kopie** | „MeisterTask-Style" 30× im Code | Kein eigenes Profil |
| **Spielerei** | `animate-bounce` auf Begrüssungs-Emoji | Unprofessionell für B2B/Bauleitung |

### Design-Prinzipien v2

1. **Ruhe vor Effekt** — Weissraum und Hierarchie statt Glas und Schatten.
2. **Eine Akzentfarbe** — Farbe bedeutet etwas, sie dekoriert nicht.
3. **Lesbarkeit zuerst** — Minimum 12px, Standard 14px.
4. **Konsistenz** — 3 Radien, 2 Schatten, 1 Icon-Set.
5. **Inhalt trägt** — keine Emojis als Icons, keine Animationen ohne Zweck.

---

## 2. Farben

### 2.1 Neutrale Basis (90 % der Fläche)

| Token | Hex | Tailwind | Verwendung |
|---|---|---|---|
| `bg` | `#F8FAFC` | `slate-50` | Seitenhintergrund |
| `surface` | `#FFFFFF` | `white` | Karten, Panels |
| `surface-sunken` | `#F1F5F9` | `slate-100` | Eingebettete Bereiche |
| `border` | `#E2E8F0` | `slate-200` | Standard-Rahmen |
| `border-strong` | `#CBD5E1` | `slate-300` | Hover, Fokus-Rahmen |
| `text` | `#0F172A` | `slate-900` | Überschriften |
| `text-body` | `#334155` | `slate-700` | Fließtext |
| `text-muted` | `#64748B` | `slate-500` | Sekundär |
| `text-subtle` | `#94A3B8` | `slate-400` | Platzhalter, Meta |

### 2.2 Akzentfarbe (10 % der Fläche)

| Token | Hex | Verwendung |
|---|---|---|
| `accent` | `#0891B2` | Primär-Buttons, Links, aktive Zustände |
| `accent-hover` | `#0E7490` | Hover |
| `accent-subtle` | `#ECFEFF` | Aktiver Hintergrund (Tabs, Zeilen) |
| `accent-border` | `#A5F3FC` | Aktiver Rahmen |

> **Änderung:** `#00A3C4` → `#0891B2`. Etwas tiefer und ruhiger, wirkt seriöser.
> Markenanker bleibt erhalten (gleiche Farbfamilie).

### 2.3 Semantische Farben (gedämpft)

| Zweck | Text | Hintergrund | Rahmen |
|---|---|---|---|
| Erfolg | `#047857` | `#ECFDF5` | `#A7F3D0` |
| Warnung | `#B45309` | `#FFFBEB` | `#FDE68A` |
| Fehler | `#BE123C` | `#FFF1F2` | `#FECDD3` |
| Info | `#0E7490` | `#ECFEFF` | `#A5F3FC` |

> **Änderung:** Kein `emerald-500/15` mehr — stattdessen **feste, deckende** Flächen.
> Kein Glas, keine Transparenz-Tricks.

### 2.4 Status (Aufgaben)

| Status | Text | Hintergrund | Rahmen | Icon (Lucide) |
|---|---|---|---|---|
| `todo` | `slate-600` | `slate-100` | `slate-200` | `Circle` |
| `in_progress` | `#0E7490` | `#ECFEFF` | `#A5F3FC` | `CircleDot` |
| `review` | `#B45309` | `#FFFBEB` | `#FDE68A` | `Eye` |
| `done` | `#047857` | `#ECFDF5` | `#A7F3D0` | `CheckCircle2` |

### 2.5 Priorität

| Wert | Text | Hintergrund | Icon (Lucide) |
|---|---|---|---|
| `niedrig` | `slate-500` | `slate-100` | `ArrowDown` |
| `normal` | `slate-600` | `slate-100` | `Minus` |
| `hoch` | `#B45309` | `#FFFBEB` | `ArrowUp` |
| `dringend` | `#BE123C` | `#FFF1F2` | `AlertTriangle` |

> **Änderung:** Keine farbigen Emoji-Kreise (🟢🔵🟠🔴) mehr — Icons + Text.

### 2.6 Rollen-Badges (dezent)

| Rolle | Stil |
|---|---|
| Superadmin | `bg-slate-900 text-white` |
| Plattform-Admin | `bg-slate-800 text-white` |
| Company Admin | `bg-cyan-50 text-cyan-800 border-cyan-200` |
| Firmenmitglied | `bg-slate-100 text-slate-700 border-slate-200` |
| Pro | `bg-amber-50 text-amber-800 border-amber-200` |
| Free | `bg-slate-100 text-slate-600 border-slate-200` |

> **Änderung:** Nur noch **eine** auffällige Farbe (Superadmin = dunkel).
> Kein Purple für Admins mehr — Purple war willkürlich.

### 2.7 Nutzer-Farben (Aufgaben/Abschnitte)

Reduziert von 8 auf **6** klar unterscheidbare Töne:

`#0891B2` Cyan · `#7C3AED` Violett · `#D97706` Amber · `#059669` Grün ·
`#DC2626` Rot · `#475569` Grau

---

## 3. Typografie

### 3.1 Schriftart

**Inter** (statt System-Font). Begründung: beste Lesbarkeit bei kleinen Größen,
tabellarische Ziffern für Timer/Beträge, professioneller Charakter.

```css
font-family: 'Inter', system-ui, -apple-system, sans-serif;
font-feature-settings: 'cv02', 'cv03', 'cv04', 'tnum';
```

### 3.2 Skala (vergrössert!)

| Rolle | v2 | v1 (alt) | Verwendung |
|---|---|---|---|
| Display | `28px / 700` | `text-4xl` | Login-Hero |
| H1 | `24px / 700` | `text-3xl` | Seitentitel |
| H2 | `18px / 600` | `text-base` | Karten-Titel |
| H3 | `16px / 600` | `text-sm` | Abschnitts-Titel |
| Body | `14px / 400` | `text-xs` | **Standard-Fließtext** |
| Body-sm | `13px / 400` | `text-xs` | Tabellen, dichte Listen |
| Label | `12px / 600` | `text-[10px]` | Formular-Labels, Badges |
| Caption | `12px / 400` | `text-[11px]` | Meta, Hilfstexte |
| Micro | `11px / 600` | `text-[9px]` | Nur Chips (Minimum!) |

> **Wichtigste Änderung:** Standard von **12px → 14px**.
> Minimum von **9px → 11px**. Kein Text mehr unter 11px.

### 3.3 Gewichte

| Gewicht | Wert | Verwendung |
|---|---|---|
| Regular | 400 | Fließtext |
| Medium | 500 | Betonung im Text |
| Semibold | 600 | Labels, Buttons, Titel |
| Bold | 700 | Nur H1/Display |

> **Änderung:** `font-black` (900) entfällt komplett. Zu schwer, zu laut.

### 3.4 Zeilenhöhe

| Kontext | Wert |
|---|---|
| Überschriften | `1.25` |
| Fließtext | `1.5` |
| Dichte Listen | `1.4` |

---

## 4. Abstände & Layout

### 4.1 4px-Raster

Alle Abstände sind Vielfache von 4px: `4 · 8 · 12 · 16 · 20 · 24 · 32 · 40 · 48 · 64`

### 4.2 Layout

| Element | v2 | v1 (alt) |
|---|---|---|
| Seiten-Container | `max-w-6xl mx-auto px-6 py-8` | `max-w-7xl px-4 py-8` |
| Karten-Padding | `p-5` | `p-6 sm:p-8` |
| Sektions-Abstand | `space-y-5` | `space-y-6 / space-y-8` |
| Grid-Abstand | `gap-4` | `gap-4 / gap-5 / gap-6` |
| Navbar-Höhe | `h-14` (56px) | `h-16` (64px) |
| Sidebar | `w-60` (fest) | `w-16 hover:w-56` (springt) |

> **Änderung:** Sidebar ist **fest** statt hover-ausklappend.
> Springende Navigation ist unruhig und schwer bedienbar.

### 4.3 Control-Höhen (vereinfacht)

| Grösse | v2 | Verwendung |
|---|---|---|
| Standard | `h-9` (36px) | Alle Buttons, Inputs |
| Kompakt | `h-8` (32px) | Tabellen-Aktionen |
| Klein | `h-7` (28px) | Chips, Icon-Buttons |

> **Änderung:** Von 5 Höhen (`42/38/36/34/30px`) auf **3** (`36/32/28px`).
> `h-[42px]` war ungewöhnlich gross.

---

## 5. Radien & Schatten

### 5.1 Radien (3 Stufen)

| Token | Wert | Verwendung |
|---|---|---|
| `radius-sm` | `6px` | Chips, Badges, kleine Buttons |
| `radius-md` | `10px` | Buttons, Inputs, Karten |
| `radius-lg` | `14px` | Modals, grosse Panels |

> **Änderung:** Von 6 Stufen (`3xl/2xl/xl/lg/md/full`) auf **3**.
> `rounded-3xl` (24px) entfällt — zu rund, wirkt verspielt.

### 5.2 Schatten (2 Stufen)

| Token | Wert | Verwendung |
|---|---|---|
| `shadow-sm` | `0 1px 2px rgba(15,23,42,.06)` | Karten im Ruhezustand |
| `shadow-md` | `0 4px 12px rgba(15,23,42,.10)` | Modals, Dropdowns, Hover |

> **Änderung:** Von 6 Stufen auf **2**.
> Karten nutzen primär **Rahmen** statt Schatten („flat with borders").

---

## 6. Komponenten

### 6.1 Buttons

**Standard:** `h-9 px-4 text-sm font-semibold rounded-md`

| Typ | Stil | Verwendung |
|---|---|---|
| **Primary** | `bg-[#0891B2] text-white hover:bg-[#0E7490]` | Speichern, Erstellen |
| **Secondary** | `bg-white text-slate-700 border border-slate-300 hover:bg-slate-50` | Abbrechen, Sekundär |
| **Danger** | `bg-[#BE123C] text-white hover:bg-[#9F1239]` | Löschen |
| **Ghost** | `text-slate-600 hover:bg-slate-100` | Icon-Buttons, Toolbar |
| **Link** | `text-[#0891B2] hover:underline` | Inline-Aktionen |

```html
<!-- Primary -->
<button class="h-9 px-4 text-sm font-semibold rounded-md bg-[#0891B2]
               text-white hover:bg-[#0E7490] transition-colors
               disabled:opacity-50 disabled:cursor-not-allowed">
  Aufgabe erfassen
</button>

<!-- Secondary (Abbrechen) -->
<button class="h-9 px-4 text-sm font-semibold rounded-md bg-white
               text-slate-700 border border-slate-300 hover:bg-slate-50
               transition-colors">
  Abbrechen
</button>

<!-- Danger -->
<button class="h-9 px-4 text-sm font-semibold rounded-md bg-[#BE123C]
               text-white hover:bg-[#9F1239] transition-colors">
  Löschen
</button>

<!-- Ghost (Icon) -->
<button class="h-8 w-8 flex items-center justify-center rounded-md
               text-slate-500 hover:bg-slate-100 hover:text-slate-900
               transition-colors" title="Einstellungen">
  <Settings class="w-4 h-4" />
</button>
```

> **Änderung:** Kein `border-[3px]` mehr (der blaue 3px-Rand war sehr dominant).
> Kein `active:scale-[0.99]` (unnötige Bewegung).

### 6.2 Karten

```html
<!-- Standard-Karte: Rahmen statt Schatten -->
<div class="bg-white border border-slate-200 rounded-lg p-5">
  <h2 class="text-lg font-semibold text-slate-900">Titel</h2>
  <p class="text-sm text-slate-600 mt-1">Beschreibung</p>
</div>

<!-- Interaktive Karte -->
<div class="bg-white border border-slate-200 rounded-lg p-5
            hover:border-slate-300 hover:shadow-sm transition-all cursor-pointer">
```

> **Änderung:** Kein `liquid_glass`, kein `backdrop-blur`, kein `shadow-xl`.
> Karten sind **deckend weiss** mit Rahmen.

### 6.3 KPI-Kacheln

```html
<div class="bg-white border border-slate-200 rounded-lg p-4">
  <div class="flex items-center gap-2 text-slate-500">
    <Clock class="w-4 h-4" />
    <span class="text-xs font-semibold uppercase tracking-wide">Erfasste Zeit</span>
  </div>
  <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">42.5 h</div>
  <div class="text-xs text-slate-500 mt-0.5">+12 % zur Vorwoche</div>
</div>
```

> **Änderung:** Icon statt Emoji, `tabular-nums` für Zahlen, Trend-Zeile statt Deko.

### 6.4 Inputs

```html
<label class="block text-xs font-semibold text-slate-700 mb-1.5">
  Firmenname
</label>
<input class="w-full h-9 px-3 text-sm rounded-md bg-white
              border border-slate-300 text-slate-900
              placeholder:text-slate-400
              focus:outline-none focus:border-[#0891B2] focus:ring-2
              focus:ring-[#0891B2]/15 transition-shadow" />
<p class="text-xs text-slate-500 mt-1.5">Hilfetext</p>
```

> **Änderung:** Kein `bg-white/70 backdrop-blur-sm` mehr.
> Fokus über Ring statt nur Rahmenfarbe.

### 6.5 Tabellen

```html
<table class="w-full text-sm">
  <thead>
    <tr class="border-b border-slate-200">
      <th class="text-left py-2.5 px-3 text-xs font-semibold
                 text-slate-500 uppercase tracking-wide">Name</th>
    </tr>
  </thead>
  <tbody class="divide-y divide-slate-100">
    <tr class="hover:bg-slate-50 transition-colors">
      <td class="py-3 px-3 text-slate-700">Wert</td>
    </tr>
  </tbody>
</table>
```

> **Änderung:** `text-sm` statt `text-xs`, `divide-slate-100` statt `/60`.

### 6.6 Navigation

**Sidebar (fest, links):**

```html
<aside class="w-60 shrink-0 bg-white border-r border-slate-200
              h-[calc(100vh-3.5rem)] sticky top-14">
  <nav class="p-3 space-y-0.5">
    <a class="flex items-center gap-3 px-3 h-9 rounded-md text-sm
              font-medium text-slate-600 hover:bg-slate-100
              hover:text-slate-900 transition-colors">
      <LayoutDashboard class="w-4 h-4" />
      <span>Dashboard</span>
    </a>
    <a class="flex items-center gap-3 px-3 h-9 rounded-md text-sm
              font-medium bg-cyan-50 text-cyan-800 transition-colors">
      <Clock class="w-4 h-4" />
      <span>Zeitrapporte</span>
    </a>
  </nav>
</aside>
```

> **Änderung:** Sidebar **links** statt rechts, **fest** statt springend.
> Aktiver Zustand: `bg-cyan-50 text-cyan-800` (statt `bg-cyan-50 text-[#00A3C4] font-bold`).

**Navbar (oben, schlank):**

```html
<header class="h-14 sticky top-0 z-40 bg-white border-b border-slate-200">
  <div class="h-full px-6 flex items-center justify-between">
    <!-- Logo links, Suche mittig, User rechts -->
  </div>
</header>
```

> **Änderung:** `bg-white` statt `liquid_glass`, `h-14` statt `h-16`.

### 6.7 Tabs

```html
<div class="flex gap-1 border-b border-slate-200">
  <button class="px-3 h-9 text-sm font-medium text-slate-600
                 hover:text-slate-900 border-b-2 border-transparent
                 transition-colors">
    Übersicht
  </button>
  <button class="px-3 h-9 text-sm font-semibold text-[#0891B2]
                 border-b-2 border-[#0891B2] transition-colors">
    Mitglieder
  </button>
</div>
```

> **Änderung:** Nur noch **eine** Tab-Variante (Unterstrich).
> Die Pill-Leiste (`liquid_glass_pill`) entfällt.

### 6.8 Modals

```html
<div class="fixed inset-0 z-50 flex items-center justify-center p-4
            bg-slate-900/40">
  <div class="bg-white rounded-lg shadow-md w-full max-w-lg
              max-h-[85vh] flex flex-col">
    <div class="flex items-center justify-between px-5 h-14
                border-b border-slate-200">
      <h2 class="text-base font-semibold text-slate-900">Titel</h2>
      <button class="h-8 w-8 flex items-center justify-center rounded-md
                     text-slate-400 hover:bg-slate-100 hover:text-slate-700">
        <X class="w-4 h-4" />
      </button>
    </div>
    <div class="p-5 overflow-y-auto flex-1"><!-- Inhalt --></div>
    <div class="flex justify-end gap-2 px-5 h-16 items-center
                border-t border-slate-200">
      <button class="...secondary">Abbrechen</button>
      <button class="...primary">Speichern</button>
    </div>
  </div>
</div>
```

> **Änderung:** Kein `backdrop-blur-sm` (Performance!), Overlay `slate-900/40`.
> Feste Header/Footer-Höhen, nur Inhalt scrollt.

### 6.9 Badges

```html
<!-- Status -->
<span class="inline-flex items-center gap-1.5 h-6 px-2 rounded-sm
             text-xs font-medium bg-cyan-50 text-cyan-800
             border border-cyan-200">
  <CircleDot class="w-3 h-3" />
  In Arbeit
</span>
```

> **Änderung:** Kein `uppercase tracking-wider` mehr (schwer lesbar).
> Icon + normaler Text.

### 6.10 Empty States

```html
<div class="text-center py-16">
  <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center
              justify-center mx-auto mb-4">
    <Inbox class="w-6 h-6 text-slate-400" />
  </div>
  <h3 class="text-sm font-semibold text-slate-900">Keine Einträge</h3>
  <p class="text-sm text-slate-500 mt-1 max-w-xs mx-auto">
    Erfasse deine erste Zeit, um hier Auswertungen zu sehen.
  </p>
  <button class="mt-5 ...primary">Zeit erfassen</button>
</div>
```

> **Änderung:** Graues Icon statt farbiges Emoji, ruhigere Grössen.

---

## 7. Wallpaper (optional)

### Standard: dezenter Hintergrund

```css
body { background: #F8FAFC; }
```

### Optional aktivierbar (Einstellungen)

Wenn der Nutzer ein Wallpaper wählt:

```html
<div class="fixed inset-0 -z-10">
  <img class="w-full h-full object-cover" />
  <!-- STARKER Overlay für Lesbarkeit -->
  <div class="absolute inset-0 bg-white/92"></div>
</div>
```

> **Wichtig:** Overlay `white/92` (statt `slate-900/15`).
> Das Wallpaper ist dann nur noch **Ahnung**, keine Design-Grundlage mehr.
> Dadurch funktioniert das Design **mit und ohne** Wallpaper identisch.

**Empfehlung:** Wallpaper standardmässig **aus**. Nur 3–4 statt 15 Bilder.

---

## 8. Icons (Lucide)

### Ersetzungs-Tabelle

| Emoji | Lucide | Verwendung |
|---|---|---|
| 🏠 | `LayoutDashboard` | Dashboard |
| 📁 | `Folder` | Projektordner |
| ⏱️ | `Clock` | Zeitrapporte |
| ⚙️ | `Settings` | Einstellungen |
| 👤 | `User` | Profil |
| 🏢 | `Building2` | Firma |
| 👥 | `Users` | Team |
| 📋 | `ClipboardList` | Aufgaben |
| 📝 | `FileText` | Journal |
| 💳 | `CreditCard` | Plan |
| 💬 | `MessageSquare` | Support |
| 🔍 | `Search` | Suche |
| 🔔 | `Bell` | Benachrichtigungen |
| 🎨 | `Palette` | Design |
| 🖼️ | `Image` | Wallpaper |
| ✅ | `CheckCircle2` | Erledigt |
| 🗑️ | `Trash2` | Löschen |
| ✏️ | `Pencil` | Bearbeiten |
| ⏹️ | `Square` | Stoppen |
| 📊 | `BarChart3` | Statistik |
| 📤 | `Upload` | Hochladen |
| 📎 | `Paperclip` | Anhang |
| 🔒 | `Lock` | Privat |
| ⚡ | `Zap` | Pro/Upgrade |
| 🫡 | — | **ersatzlos entfernen** |

### Icon-Grössen

| Kontext | Grösse |
|---|---|
| In Buttons | `w-4 h-4` |
| In Navigation | `w-4 h-4` |
| In Badges | `w-3 h-3` |
| Empty States | `w-6 h-6` |
| KPI-Kacheln | `w-4 h-4` |

### Icon-Farbe

Icons erben die Textfarbe (`currentColor`). Keine eigenen Farben.

---

## 9. Animationen

| Zweck | Erlaubt |
|---|---|
| Hover-Farbwechsel | `transition-colors duration-150` |
| Karte-Hover | `transition-all duration-150` |
| Modal-Erscheinen | `transition-opacity duration-150` |
| Ladezustand | `animate-spin` (Spinner) |
| Stoppuhr-Punkt | `animate-pulse` |

**Verboten:**
- `animate-bounce` (Begrüssungs-Emoji)
- `hover:scale-105` / `active:scale-[0.99]` (Bewegung ohne Zweck)
- `transition-all duration-700` (zu langsam)
- `animate-in fade-in zoom-in-95` (Tailwind-Plugin, inkonsistent)

---

## 10. Vorher / Nachher

### Beispiel: KPI-Kachel

**Vorher:**
```html
<div class="liquid_glass p-5 rounded-3xl border border-white/70 shadow-lg
            relative overflow-hidden group">
  <div class="flex items-center justify-between">
    <span class="text-[11px] font-black tracking-wider uppercase text-slate-500">
      Erfasste Zeit
    </span>
    <span class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-700 flex items-center
                 justify-center text-sm font-black shadow-xs">⏱️</span>
  </div>
  <div class="mt-3">
    <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">
      42.5 h
    </div>
    <div class="text-xs font-semibold text-cyan-800 mt-0.5">
      2550 Dezimalminuten
    </div>
  </div>
</div>
```

**Nachher:**
```html
<div class="bg-white border border-slate-200 rounded-lg p-4">
  <div class="flex items-center gap-2 text-slate-500">
    <Clock class="w-4 h-4" />
    <span class="text-xs font-semibold uppercase tracking-wide">Erfasste Zeit</span>
  </div>
  <div class="text-2xl font-bold text-slate-900 mt-2 tabular-nums">42.5 h</div>
  <div class="text-xs text-slate-500 mt-0.5">2550 Minuten</div>
</div>
```

**Was sich ändert:**
- Glas → deckend weiss
- `rounded-3xl` → `rounded-lg`
- `shadow-lg` → Rahmen
- Emoji → Lucide-Icon
- `font-black` → `font-bold`
- `text-[11px]` → `text-xs`
- `tracking-wider` → `tracking-wide`
- Zahlen mit `tabular-nums`

### Beispiel: Button

**Vorher:**
```html
<button class="taskster_button px-6 text-xs h-[42px] rounded-lg">
  <span>+ Aufgabe erfassen</span>
</button>
```

**Nachher:**
```html
<button class="h-9 px-4 text-sm font-semibold rounded-md bg-[#0891B2]
               text-white hover:bg-[#0E7490] transition-colors
               inline-flex items-center gap-2">
  <Plus class="w-4 h-4" />
  <span>Aufgabe erfassen</span>
</button>
```

---

## 11. Umsetzungs-Reihenfolge (wenn freigegeben)

| Phase | Inhalt | Aufwand |
|---|---|---|
| **1** | Tokens in `tailwind.config.ts` + `app.vue` (Farben, Radien, Schatten) | klein |
| **2** | Inter einbinden, Typo-Skala global | klein |
| **3** | Button-Klassen ersetzen (`taskster_button*` → neue Klassen) | mittel |
| **4** | Navbar + Sidebar umbauen (links, fest) | mittel |
| **5** | Karten/Inputs/Tabellen seitenweise migrieren | gross |
| **6** | Emojis → Lucide (115 Stellen) | gross |
| **7** | Wallpaper auf optional umstellen | klein |
| **8** | Modals vereinheitlichen | mittel |

**Empfehlung:** Phase 1–4 zuerst (Fundament + Navigation), dann seitenweise.

---

## 12. Offene Fragen

1. **Sidebar links statt rechts?** — Ich empfehle links (Standard bei Linear/Notion).
2. **Inter als Schriftart?** — Erfordert Einbindung (lokal oder Google Fonts).
3. **Wallpaper ganz entfernen** oder als Option behalten? — Aktuell: optional.
4. **`#0891B2` statt `#00A3C4`?** — Oder Markenfarbe exakt behalten?
5. **Dark Mode** — jetzt mitdenken oder später?

---

## 13. Prompt-Vorlage

> „Setze Design v2 um: **[Bereich]**.
> Halte dich an `99_anweisungen/design-v2-entwurf.md` und
> `99_anweisungen/design-v2-tokens.json`.
> Keine Emojis (Lucide-Icons), keine Glas-Effekte, keine `font-black`,
> Standard-Schriftgrösse 14px, Buttons `h-9 px-4 text-sm rounded-md`."

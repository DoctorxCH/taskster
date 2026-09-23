# 2026-09-23: Abgeschlossene Projekte Leuchtend Grün & Deckend (Nicht Durchsichtig)

## Kontext & Problem
Abgeschlossene Projekte (`status === 'completed'`) wurden in Projektordnern (`pages/folders/[id].vue`), im Dashboard (`pages/dashboard.vue`) und in der Projektdetailseite (`pages/projects/[id].vue`) mit transparenter Hintergrundfarbe gerendert (`bg-emerald-500/10` bzw. `bg-emerald-50/60` / `bg-emerald-50/50`).
Dadurch schien das Hintergrund-Wallpaper durch ("durchsichtig") und der visuelle Status "Abgeschlossen" hob sich unzureichend ab.

## Durchgeführte Änderungen
1. **Volle Deckkraft (Nicht durchsichtig):**
   - Wechsel von `bg-emerald-500/10` / `bg-emerald-50/60` auf solides `bg-emerald-50` (100% Deckkraft, kein Alphakanal-Blur).
   - In Tabellenzeilen: `bg-emerald-50 hover:bg-emerald-100/80` plus linker Akzentstreifen `border-l-4 border-l-emerald-500`.

2. **Grüner Leuchteffekt (Glow):**
   - Rahmen & Glow: `border border-emerald-400 ring-2 ring-emerald-400/50 shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/30 hover:border-emerald-500`.
   - Icon-Box: `bg-emerald-100 border-emerald-300 text-emerald-700 shadow-xs`.
   - Status-Badge: `bg-emerald-100 text-emerald-900 border border-emerald-300 font-bold shadow-xs` mit animiertem grünen Puls-Indikator `<span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>`.

3. **Betroffene Dateien:**
   - [pages/folders/[id].vue](file:///c:/Users/marti/Taskster/pages/folders/[id].vue) (Grid-Karten, Listen-Tabelle, Controlling-Breakdown)
   - [pages/projects/[id].vue](file:///c:/Users/marti/Taskster/pages/projects/[id].vue) (Header-Karte & Status-Badge)
   - [pages/dashboard.vue](file:///c:/Users/marti/Taskster/pages/dashboard.vue) (Projekt-Kacheln)

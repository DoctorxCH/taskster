# 2026-09-20 Taskster Design v2 Redesign

## Übersicht
- **Ziel:** Umstellung auf Design v2 gemäss `99_anweisungen/design-v2-entwurf.md` & `99_anweisungen/design-v2-tokens.json`.
- **Richtung:** Clean & Professional (Linear, Notion, Stripe-Stil).
- **Geräte-Unterstützung:** Optimiert für Desktop, Apple iOS/iPadOS & Android Geräte inklusive Tablets (responsive Layout & Touch-Targets).

## Änderungen
1. **Design Tokens & Theme:**
   - Primary Accent: `#0891B2` (Hover `#0E7490`, Subtle `#ECFEFF`, Border `#A5F3FC`).
   - Font: Inter (`font-family: 'Inter', system-ui, -apple-system, sans-serif; font-feature-settings: 'cv02', 'cv03', 'cv04', 'tnum'`).
   - Radien: `radius-sm` (6px), `radius-md` (10px), `radius-lg` (14px).
   - Schatten: `shadow-sm`, `shadow-md` (flache Karten mit feinen Rahmen `border-slate-200`).
2. **Globales Button-System:**
   - `.taskster_button`: `bg-[#0891B2] text-white hover:bg-[#0E7490] h-9 px-4 text-sm font-semibold rounded-md`.
   - `.taskster_button_accent`: `bg-[#BE123C] text-white hover:bg-[#9F1239] h-9 px-4 text-sm font-semibold rounded-md`.
   - `.taskster_button_light`: `bg-white text-slate-700 border border-slate-300 hover:bg-slate-50 h-9 px-4 text-sm font-semibold rounded-md`.
3. **Navigation & Sidebar:**
   - Header: Schlanke Höhe `h-14` (56px) mit `bg-white border-b border-slate-200`.
   - Desktop Navigation: Feste linke Sidebar (`w-60 shrink-0 bg-white border-r border-slate-200`).
   - Mobile/Tablet Navigation: Responsive Hamburger Drawer-Navigation.
4. **Icons & Wallpaper:**
   - Lucide Icons (`lucide-vue-next`) anstelle aller Emojis.
   - Wallpaper standardmäßig deaktiviert (`#F8FAFC`), bei Aktivierung mit `white/92` Overlay für maximale Lesbarkeit.

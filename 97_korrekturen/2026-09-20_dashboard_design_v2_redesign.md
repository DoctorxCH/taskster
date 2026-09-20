# 2026-09-20 Dashboard & Page Icons Design v2 Migration

## Betreff
Refactoring der Dashboard-Seite (`pages/dashboard.vue`), Login-Seite (`pages/login.vue`) und Startseite (`pages/index.vue`) auf Taskster Design v2.

## Änderungen
- **Dashboard Refactoring (`pages/dashboard.vue`)**:
  - Sämtliche Emojis (`📅`, `🫡`, `🔍`, `⚡`, `📋`, `☀️`, `📁`, `⏱️`, `🔄`, `🎯`, `✨`, `🔔`, `🎉`, `⏰`, `💬`, `✏️`, `📩`, `💰`) durch Lucide-Icons aus `lucide-vue-next` ersetzt (`Calendar`, `Sparkles`, `Search`, `Zap`, `ClipboardList`, `Sun`, `Folder`, `Clock`, `RotateCcw`, `Target`, `Check`, `Trash2`, `ArrowRight`, `Building2`, `Lock`, `Pencil`, `Bell`, `PartyPopper`, `MessageSquare`, `Mail`, `CreditCard`, `X`).
  - Glassmorphism-Klassen (`liquid_glass`, `liquid_glass_card`, `liquid_glass_pill`, `backdrop-blur-*`) entfernt.
  - Karten auf flache, saubere Design v2 White Cards (`bg-white border border-slate-200 rounded-lg shadow-xs p-5`) umgestellt.
  - Buttons auf einheitliche Standards (`taskster_button`, `taskster_button_light`, `taskster_button_accent`, `h-9 px-4 text-xs font-semibold rounded-md`) umgestellt.
  - Typografie von `font-black` auf `font-bold` / Inter 14px angepasst.
- **Login- & Index-Icons (`pages/login.vue`, `pages/index.vue`)**:
  - Alle Emojis (`✨`, `⚡`, `📁`, `🛡️`, `🏢`) durch Lucide Icons (`Sparkles`, `Zap`, `Folder`, `Shield`, `Building2`) ausgetauscht.
- **Index & Build**:
  - `python generate_index.py --quiet` zur Aktualisierung von `.agent_index.json` ausgeführt.
  - Statischer Build & Sync via `npm run build:dist` angestoßen.

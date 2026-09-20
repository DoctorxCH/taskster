# 2026-09-20 Projekt- & Ordner-Ansicht UX-Refactoring

## Betreff
Bereinigung der Projekt-Header-Aktionen & Kompaktierung der Zeiterfassungs- & Controlling-Übersicht im Projektordner.

## Durchgeführte Optimierungen

### 1. Projekt-Ansicht (`pages/projects/[id].vue`)
- **Entfernung der doppelten Einstellungen-Icons**: Das verwirrende Zahnrad-Icon (`⚙️`) in der oberen Aktionsleiste wurde entfernt. Die Einstellungen befinden sich nun einzig und sauber im Tab `Projekt-Einstellungen` mit Lucide-Icon (`Settings`).
- **Aufgeräumte Aktionsleiste**:
  - Primäre Aktion: `+ Aufgabe erfassen` (`taskster_button`)
  - Sekundäre Aktion: `+ Abschnitt` (`taskster_button_light`)
  - Ansichtsumschalter: Clean `Kacheln` (`LayoutGrid`) / `Liste` (`List`) Pill
  - Zusatzfunktionen: Clean `Import` (`Upload`) und `Projekt-Stoppuhr` (`Clock`) Buttons.
- **Lucide-Icons in allen Tabs**: Tabs verwenden einheitlich Lucide-Icons (`ClipboardList`, `Clock`, `Activity`, `Users`, `Contact`, `Settings`) anstelle von Emojis.

### 2. Ordner-Ansicht (`pages/folders/[id].vue`)
- **Kompakte Zeiterfassung & Controlling-Übersicht**:
  - Der massive 80-Zeilen-Banner wurde in eine **dezente, 1-zeilige Controlling-Zeile** umgewandelt.
  - Mit dem neuen Button `Details anzeigen / einklappen` können Projekt-Budget-Details bei Bedarf ausgeklappt werden.
  - Dadurch stehen die **"Projekte in diesem Ordner"** als zentraler Fokus direkt an oberster Stelle.
- **Vollständiges Design v2 Styling**:
  - Glassmorphism (`liquid_glass`) und Emojis (`⏱️`, `📋`, `⭐`, `🏢`, `🔒`, `👥`, `✏️`) wurden komplett entfernt und durch saubere Design v2 White Cards (`bg-white border border-slate-200 rounded-lg p-5 shadow-xs`) sowie Lucide Vektor-Icons ersetzt.

### 3. Build & Sync
- `python generate_index.py --quiet` zur Aktualisierung von `.agent_index.json` ausgeführt.
- Statischer Build & Sync via `npm run build:dist` durchgeführt und nach `origin/main` gepusht.

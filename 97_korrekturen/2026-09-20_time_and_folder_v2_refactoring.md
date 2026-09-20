# 2026-09-20 Refactoring: Zeiterfassung Tab Fix, /time Design v2 Unification, Folder Header Consolidation

## 1. Zeiterfassung Tab Fix (`pages/projects/[id].vue`)
- **Ursache**: Der `v-else-if="currentView === 'time'"`-Block lag versehentlich ausserhalb des Haupt-`v-else-if="project"`-Containers. Da `project` nach dem Laden immer truthy war, evaluierte Vue den inneren Block und ignorierte alle nachfolgenden `v-else-if`-Geschwister ausserhalb des Elements.
- **Lösung**: `VIEW 5: ZEITERFASSUNG` und `VIEW 6: PROJEKT-KONTAKTE` wieder ordnungsgemäss in das `<div v-else-if="project">`-Haupt-Element verschoben. Die Tab-Inhalte laden nun sofort bei Klick auf "Zeiterfassung".

## 2. /time Zeitrapportierung & Controlling Design v2 (`pages/time.vue`)
- **Header Card**: Titel, Breadcrumbs und Aktions-Buttons in eine flache, weisse Container-Karte (`bg-white border border-slate-200 rounded-lg p-5 shadow-xs`) gewrappt. Das verhindert unlesbaren Text auf dunklem Hintergrund-Wallpaper.
- **Filter- & Tabellenbereich**: `liquid_glass`-Styling durch saubere Design v2 Cards (`bg-white border border-slate-200 rounded-lg shadow-xs`) ersetzt.
- **Icons & Buttons**: Sämtliche Emojis (`⏱️`, `🔍`, `🎯`, `✏️`, `🗑️`) durch Lucide Icons (`Clock`, `Search`, `Target`, `Pencil`, `Trash2`, `LayoutDashboard`) ausgetauscht. `taskster_button`-Klassen angewendet.

## 3. Ordner-Kopfbereich Konsolidierung (`pages/folders/[id].vue`)
- **Unification**: Die zuvor 3 gestapelten Kacheln (Ordner-Banner, Controlling-Aufwandszeile und "Projekte in diesem Ordner"-Leiste) wurden in eine einzige, kompakte Kopfzeilen-Karte zusammengefasst:
  - Obere Zeile: Ordner-Icon, Titel, Projektanzahl-Badge, Owner/Sichtbarkeits-Badges, Ansichts-Umschalter (Kacheln/Liste) sowie Aktions-Buttons (`Teilen`, `Anpassen`, `+ Neues Projekt`).
  - Untere Zeile (integriert): Controlling-Aufwandssumme mit aufklappbaren Projekt-Details (`Details anzeigen / einklappen`).

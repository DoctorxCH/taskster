# 2026-09-19 – Task Detail Großes Popup & Lesbarkeits-Upgrade

## 1. Fehlerbehebung Datenbank
- **Problem**: Bei Klick auf eine Aufgabe meldete MySQL auf dem Server: `Base table or view not found: 1146 Table 'd44809_taskster_26.task_comments' doesn't exist` und `task_subtasks doesn't exist`.
- **Ursache**: Die neuen Tabellen für Kommentare und Unteraufgaben waren auf dem MySQL-Server (Hostcreators) noch nicht angelegt.
- **Lösung**: 
  - `scripts/migrate-mysql.cjs` erweitert und direkt auf `sql21.hostcreators.sk:3326` ausgeführt.
  - Tabellen `task_comments` und `task_subtasks` mit sauberen Fremdschlüssel-Indizes erfolgreich erstellt.
  - `api/index.php`, `public/api/index.php` und `server-php/index.php` abgesichert.

## 2. Großes modales Popup statt Slide-in Drawer
- **Umstellung**: Statt des schmalen Slide-in-Drawers von rechts wurde ein großzügiges, zentriertes Popup (`max-w-4xl`, bis zu 92vh, zentrierter Backdrop mit `backdrop-blur-md`) implementiert:
  - **Kopfbereich**: Farbiger Akzentbalken bei gesetzter Task-Farbe, Breadcrumb (Ordner > Projekt > Abschnitt), großer inline-editierbarer Titel, Schließen-Button.
  - **Linke Hauptspalte (62%)**:
    - Beschreibung (Textarea mit AutoSave)
    - Checkliste mit Fortschrittsbalken in %, Abhaken, Text-Edit und Löschen
    - Unteraufgaben (Subtasks) mit Abhaken und Löschen
    - Kommentare & Besprechungsnotizen (chronologischer Feed mit Zeitstempel & Avatar, Schnelleingabe per Strg+Enter)
  - **Rechte Seitenleiste (38%)**:
    - Abschnitt-Wechsel (Aufgabe direkt in anderen Abschnitt verschiebbar)
    - Status-Dropdown (Todo, In Arbeit, In Prüfung, Done)
    - Priorität (Niedrig, Normal, Hoch, Dringend) mit deutlichen Farbmarkierungen
    - Zuweisung an Projektmitglieder mit Avatar
    - Fälligkeitsdatum (Datepicker)
    - 8 Farb-Chips zur Task-Farbcodierung
    - Tags (Inline-Chips hinzufügen und entfernen)
    - Dynamische Zusatzfelder mit bedingter Sichtbarkeit
    - Aufgabe löschen (mit Bestätigung)
  - **Fußbereich**: "Änderungen werden automatisch gespeichert" + Schließen-Button.

## 3. Globale Lesbarkeits-Verbesserung
- **`app.vue`**:
  - `.liquid_glass`: Deckkraft von 22% auf 82% erhöht (`rgba(255, 255, 255, 0.82)` mit 28px Blur) – dadurch hat jeder Text auf allen Wallpapern (auch dunklen/bunten wie dem Chamäleon) perfekten Kontrast.
  - `.liquid_glass_pill` auf 88% und `.liquid_glass_card` auf 90% angepasst.
  - Wallpaper-Overlay sanfter eingestellt (`bg-slate-900/15 backdrop-blur-[0.5px]`), um trübe Schattierungen unter Glasflächen zu verhindern.
- **`pages/projects/[id].vue`**:
  - Tabs mit kräftigerem Kontrast (`text-slate-700 hover:text-slate-950 font-bold`).
  - Kanban-Board Spalten und leere Dropzonen heller und klar lesbar gestaltet.
- **`pages/settings.vue`**:
  - Tarifplan-Vergleichskarten von milchig-transparentem `bg-white/20` auf solides `bg-white` mit Schatten und Rand aufgewertet.

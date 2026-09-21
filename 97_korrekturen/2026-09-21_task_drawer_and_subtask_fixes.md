# 2026-09-21 Task Detail Drawer, Subtasks, Comments & Field Checks Fixes

## Übersicht der Optimierungen
- **Task Drawer Scroll-Verhalten (`pages/projects/[id].vue`):** Zusatzfelder wurden in den gemeinsamen scrollbaren Container verschoben. Nur der Titel-Header bleibt fixiert.
- **Unteraufgaben & Checklisten (`pages/projects/[id].vue`):** Unteraufgaben werden direkt unter Checklisten-Einträgen gerendert und verwaltet.
- **Kommentar-Eingabe (`pages/projects/[id].vue`):** Textfeld wird nach Absenden zuverlässig geleert.
- **Task-Erstellung & Highlight (`pages/projects/[id].vue`):** Drawer schließt sich automatisch bei Erstellung; die neue Aufgabe leuchtet für 3 Sekunden blau auf.
- **Abschnittswechsel (`pages/projects/[id].vue`):** Beim Ändern des Abschnitts im Drawer wird die Aufgabe sofort ohne Reload im Board verschoben.
- **Zusatzfelder-Löschprüfung (`pages/projects/[id].vue`):** Zusatzfelder werden vor dem Löschen geprüft, ob sie in aktiven Tasks/Projekten verwendet werden.
- **Ordner-Löschbestätigung (`pages/folders/[id].vue`):** Ordner löschen erfordert die Eingabe des Ordnernamens in ein Modal.

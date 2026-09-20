# Korrektur: Task-Drawer Zusatzfelder Positionierung & kompakte Zeiterfassung

**Datum:** 2026-09-20  
**Betroffene Komponenten:** `pages/projects/[id].vue`

## Änderungen
1. **Zusatzfelder unter Aufgabentitel platziert:**
   - Die projektspezifischen Zusatzfelder (`visibleDrawerFields`) werden nun direkt im Header-Bereich unter dem Aufgabennamen als horizontales Grid gerendert.
   - Aus der rechten Seitenleiste wurden die Zusatzfelder entfernt, um Redundanzen zu vermeiden.

2. **Kompaktes Zeiterfassungs- & Budget-Menü:**
   - Der Bereich "Zeiterfassung & Budget" in der linken Spalte wurde stark verdichtet: Standardmässig wird eine 1-zeilige Statusleiste mit bisher erfasstem Aufwand und Budgetbalken angezeigt.
   - Budget-Eingaben, manuelle Zeiterfassung und die Liste bereits gebuchter Zeiten sind unter dem Menüpunkt `[ Budget & Manuell buchen ▼ ]` versteckt und können bei Bedarf ein- und ausgeklappt werden.

3. **Stoppuhr & Erfasste Zeit oben rechts:**
   - Im oberen rechten Header des Aufgaben-Drawers (direkt neben dem Schliessen-Button) wird nun die aktive Stoppuhr (Start/Stopp mit Live-Sekunden und Stoppen & Buchen) sowie der bisher auf die Aufgabe gebuchte Gesamtaufwand (`⏱️ X Std. / Yh`) prominent und platzsparend angezeigt.

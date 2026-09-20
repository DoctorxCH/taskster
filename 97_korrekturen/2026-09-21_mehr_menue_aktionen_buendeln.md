# 2026-09-21 – Aktionen in „Mehr"-Menü bündeln

## Problem
Die Kopfzeilen von Ordner- und Projektseite waren mit vielen gleichrangigen Buttons überladen
(Teilen, Anpassen, Importieren, Ansicht-Umschalter, Projekt-Stoppuhr, Import, Sprachnotiz, Abschnitt).
Wichtige Aktionen waren dadurch nicht auf Anhieb sichtbar.

## Lösung
Sekundäre Aktionen wandern in ein Dropdown-Menü („Mehr", Icon `MoreVertical`).
Nur die Primäraktion bleibt als sichtbarer Button.

### `pages/folders/[id].vue`
- Sichtbar: **Neues Projekt** (Primary).
- Im „Mehr"-Menü:
  - Abschnitt **Ansicht**: Kacheln / Liste (aktive Option mit Häkchen + Cyan-Hervorhebung)
  - **Ordner teilen** (nur Owner)
  - **Ordner anpassen** (nur Owner)
  - **Projekt importieren**

### `pages/projects/[id].vue`
- Sichtbar: **Aufgabe erfassen** (Primary) + laufende Stoppuhr (muss zum Stoppen erreichbar bleiben).
- Im „Mehr"-Menü:
  - Abschnitt **Ansicht**: Kacheln / Liste
  - **Projekt-Stoppuhr starten** (ausgeblendet, wenn die Stoppuhr für dieses Projekt läuft)
  - **Aufgaben importieren**
  - **Sprachnotiz**
  - **Neuer Abschnitt**

## Technische Details
- Neuer State `showActionsMenu = ref(false)` in beiden Seiten.
- Overlay (`fixed inset-0 z-40`) schließt das Menü bei Klick außerhalb.
- Menü-Panel: `absolute right-0 top-full mt-1 w-56 bg-white border border-slate-200 rounded-lg shadow-lg z-50`.
- Neue Icon-Imports: `MoreVertical` (beide Seiten).
- Design-System-konform: `taskster_button` (Primary), `taskster_button_light` (Mehr), Primärfarbe `#0891B2`.

## Deployment
- `npm run build:dist` → Commit → Push nach `origin/main` (Commit `2fd1f87`).

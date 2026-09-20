# 04: UI/UX Anpassungen nach Apple HIG (Human Interface Guidelines)

Um im App Store als hochqualitative native iOS-App wahrgenommen und zugelassen zu werden, muss sich das Interface wie eine echte iPhone-App anfühlen. Hier sind die verbindlichen Design- und UI-Anpassungen.

---

## 1. Safe-Area-Insets (Notch & Dynamic Island)

iPhones besitzen am oberen Rand die Dynamic Island bzw. die Notch und am unteren Rand den Home-Indicator-Balken. Inhalte dürfen niemals hinter diesen Elementen verschwinden.

### CSS-Einbindung:
```css
/* Globale Safe-Area-Variablen */
:root {
  --sat: env(safe-area-inset-top);
  --sab: env(safe-area-inset-bottom);
  --sal: env(safe-area-inset-left);
  --sar: env(safe-area-inset-right);
}

/* Header & Status-Bar Anpassung */
header.app-header {
  padding-top: max(12px, env(safe-area-inset-top));
}

/* Bottom-Navigation & Timer-Bar Anpassung */
.bottom-nav, .mobile-timer-dock {
  padding-bottom: max(16px, env(safe-area-inset-bottom));
}
```

---

## 2. Touch-Targets & Button-Standards (HIG Konform)

- **Mindestgröße für Touch-Ziele:** Apple HIG fordert mindestens **44 × 44 pt** für alle interaktiven Elemente (Buttons, Checkboxen, Icons).
- **Taskster Button Standard:**
  - Standard-Klasse: `taskster_button` (Blau `#00A3C4`) / `taskster_button_accent` (Rot) / `taskster_button_light` (Ghost).
  - Tailwind-Sizing: `px-6 text-xs h-[42px] rounded-lg` erfüllt fast die 44pt; auf Mobilgeräten wird die Höhe via Safe-Padding auf `h-[44px]` bzw. mit Min-Click-Area von 44x44px per CSS `after`-Pseudoelement sichergestellt.
  - Kein Text-Markierungs-Flackern: Globales `-webkit-user-select: none;` (außer in echten Eingabefeldern und Beschreibungen).

---

## 3. Mobile Navigation: Bottom Navigation Bar statt Desktop-Sidebar

Auf dem Desktop besitzt Taskster eine linke Navigationsleiste. Auf dem iPhone wechselt die Navigation in eine feste **iOS Bottom Tab Bar**:

| Tab | Icon | Funktion |
|:---|:---|:---|
| **Heute / Dashboard** | `LayoutDashboard` | Schnellüberblick, anstehende Aufgaben, Tages-Todos. |
| **Projekte** | `FolderKanban` | Projektordner, Listen, Kanban-Board & Detailansichten. |
| **Zeiterfassung** | `Clock` | Live-Stoppuhr, Tagesrapport, Schnellbuchung. |
| **Kontakte** | `Users` | Baustellenkontakte, Telefon/Mail Schnellwahl. |
| **Mehr / Profil** | `Menu` | Einstellungen, Company Admin, Sync-Status, Logout/Delete. |

> **Floating Action Button (FAB):** In der Mitte oder unten rechts befindet sich ein schneller Aktions-Button für die **Mobile 2-Klick-Regel**: *"+ Neue Aufgabe"*, *"+ Baustellenfoto"*, *"+ Sprachmemo"*.

---

## 4. Haptisches Feedback (UIFeedbackGenerator)

Natives Tastgefühl erhöht die Wertigkeit der App drastisch. Folgende Interaktionen lösen Haptik aus:
- **Checkbox abhaken (Task erledigt):** `ImpactStyle.Light` (spürbarer kleiner Klick).
- **Stoppuhr Start / Stop:** `ImpactStyle.Medium`.
- **Fehlermeldung / Validierungsfehler:** `NotificationType.Error`.
- **Statuswechsel im Kanban (Drop):** `SelectionChanged`.

---

## 5. Modals als iOS Bottom Sheets

Große Desktop-Popups wirken auf dem Smartphone deplatziert. 
- Modale Fenster (wie `VoiceRecorderModal.vue`, Task-Details oder Zeiterfassungs-Popup) werden auf iOS als **Bottom Sheet** animiert, das von unten einslidet.
- Besitzt oben einen dezenten grauen Grifffalz (`drag-indicator`) und lässt sich per Wischgeste nach unten schließen.
- Geschmeidiges iOS-Keyboard-Scrolling: Eingabefelder springen automatisch über die virtuelle iOS-Tastatur, ohne den Speichern-Button zu verdecken.

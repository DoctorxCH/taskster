# 05: UI/UX Anpassungen (Apple HIG & Android Material 3)

Eine erstklassige Cross-Platform App muss sich auf beiden Betriebssystemen "zu Hause" anfühlen. Taskster kombiniert das eigene Design-System (Primärfarbe `#00A3C4`, Liquid Glass, `taskster_button`) harmonisch mit den Richtlinien von **Apple Human Interface Guidelines (HIG)** und **Google Material Design 3**.

---

## 1. Das entscheidende Android-Feature: Hardware- & Gesten-Zurücktaste

Auf iOS wischen Nutzer vom linken Bildschirmrand oder tippen auf Schließen-Schaltflächen. Auf Android nutzen Anwender die universelle **System-Zurücktaste** (Gesten oder Navigationsleiste).

> **Achtung vor dem "Exit-App-Bug":** Wird der native Back-Button in einer Web-App nicht abgefangen, beendet ein Druck auf "Zurück" die gesamte App, anstatt ein geöffnetes Modal zu schließen.

### Verbindliche Back-Button-Logik in Taskster:
```typescript
import { App } from '@capacitor/app'
import { useRouter } from 'vue-router'

export function setupAndroidBackButton(activeModalRef: Ref<any>) {
  App.addListener('backButton', ({ canGoBack }) => {
    // 1. Priorität: Offenes Modal/Drawer schließen (z.B. VoiceRecorderModal)
    if (activeModalRef.value) {
      activeModalRef.value.close()
      return
    }

    // 2. Priorität: In der App-Historie zurückgehen
    const router = useRouter()
    if (router.currentRoute.value.path !== '/dashboard' && canGoBack) {
      router.back()
      return
    }

    // 3. Priorität: Auf Hauptseite App minimieren (nicht killen)
    App.minimizeApp()
  })
}
```

---

## 2. Safe-Area-Insets: Dynamic Island (iOS) & Display Cutouts (Android)

Sowohl moderne iPhones (Notch/Dynamic Island) als auch Android-Smartphones (Kamera-Punch-Holes und Edge-to-Edge Gestenleisten) erfordern dynamische Innenabstände.

```css
/* Globale Safe-Area-Variablen für beide Betriebssysteme */
:root {
  --sat: env(safe-area-inset-top, 0px);
  --sab: env(safe-area-inset-bottom, 0px);
}

/* Header & Statusleiste */
header.app-header {
  padding-top: max(12px, env(safe-area-inset-top));
}

/* Bottom Navigation Bar & Stoppuhr-Docking */
.bottom-nav, .mobile-timer-dock {
  padding-bottom: max(16px, env(safe-area-inset-bottom));
}
```

---

## 3. Touch-Targets: HIG (44pt) vs. Material 3 (48dp)

- **Touch-Flächen:** Android verlangt standardmäßig mindestens **48 × 48 dp**, Apple mindestens **44 × 44 pt**.
- **Taskster Standard:**
  - `taskster_button`: `px-6 text-xs h-[42px] rounded-lg`.
  - **Mobile-Regel:** Auf Touch-Geräten wird die klickbare Mindestfläche über ein transparentes Pseudoelement auf mindestens `48 × 48 px` vergrößert, um versehentliches Danebentippen auf der Baustelle (z. B. mit Arbeitshandschuhen) zu verhindern.

---

## 4. Mobile Navigation: Bottom Navigation Bar statt Desktop-Sidebar

Auf dem Smartphone (iOS & Android) wird die Desktop-Sidebar durch eine feste untere **Bottom Tab Bar** ersetzt:

```
┌─────────────────────────────────────────────────────────────┐
│                    Taskster Mobile Header                   │
│          [Logo]          [Offline-Icon]      [Profil]       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│                    Aktiver Seiteninhalt                     │
│               (Kanban / Aufgaben / Kalender)                │
│                                                             │
│                                           ┌───────────┐     │
│                                           │  (+) FAB  │     │
│                                           └───────────┘     │
├─────────────────────────────────────────────────────────────┤
│   [Heute]      [Projekte]      [Zeit]     [Kontakte]  [Mehr]│
│      ▲              ▲             ▲            ▲         ▲  │
└──────┴──────────────┴─────────────┴────────────┴─────────┴──┘
```

- **Floating Action Button (FAB):** Ein prägnanter Plus-Button unten rechts für Vor-Ort-Aktionen nach der **2-Klick-Regel**:
  - *Neuer Task*
  - *Foto aufnehmen*
  - *Sprachmemo diktieren*

---

## 5. Haptisches Feedback (Plattformübergreifend)

Über `@capacitor/haptics` wird auf beiden Plattformen natives Feedback erzeugt:
- **iOS:** Ansprache des `UIFeedbackGenerator` der Taptic Engine.
- **Android:** Ansprache des `Vibrator` Dienstes mit `VibrationEffect`.
- **Einsatz:**
  - Task als erledigt markiert: Leichter Klick (`ImpactStyle.Light`).
  - Timer gestartet / gestoppt: Spürbarer Impuls (`ImpactStyle.Medium`).
  - Validierungsfehler / Warnung: Vibrationsmuster (`NotificationType.Warning`).

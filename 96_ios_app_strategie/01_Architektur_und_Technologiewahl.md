# 01: Architektur & Technologiewahl

## 1. Technologievergleich: Der Weg zur echten iOS App

Um Taskster als native App in den Apple App Store zu bringen, stehen prinzipiell drei Ansätze zur Verfügung:

| Kriterium | Reines SwiftUI (100% Nativ) | React Native / Flutter | Capacitor 6 (Native Bridge + Nuxt SPA) |
|:---|:---|:---|:---|
| **Codebasis** | 2 getrennte Frontends (Web + iOS) | 2 getrennte Frontends (Vue Web + RN/Flutter) | **1 gemeinsame Codebasis (Vue 3 / Nuxt)** |
| **Bestehende UI/UX** | Komplett neu bauen | Komplett neu bauen | **100% Wiederverwendbar (Tailwind, Liquid Glass)** |
| **Entwicklungsaufwand** | Sehr hoch (6–9 Monate) | Hoch (4–6 Monate) | **Gering–Mittel (3–4 Wochen)** |
| **App Store Zulassung** | Garantiert | Garantiert | **Vollwertig gewährleistet (bei nativer Integration)** |
| **Hardware-Zugriff** | Direkt via iOS SDK | Via RN-Bridges | **Via native Swift/Capacitor-Plugins** |
| **Wartung & Sync** | Features müssen doppelt gebaut werden | Features müssen doppelt gebaut werden | **Features sind sofort plattformübergreifend live** |

### Empfehlung & Entscheidung: **Capacitor 6 Enterprise Bridge**
Reines SwiftUI erfordert die doppelte Pflege aller Features (Custom Fields, Formelrechner, Kanban-Logik, Zeiterfassung, Mandanten-Verwaltung). 
Mit **Capacitor 6** wird aus der Nuxt 3 Anwendung ein echtes natives Xcode-Projekt (`.xcworkspace`), das in Swift kompiliert, native iOS-Frameworks (`AVFoundation`, `LocalAuthentication`, `Photos`, `UserNotifications`, `CoreData/SQLite`) anspricht und **lokal aus dem App-Bundle** (`capacitor://localhost`) ausgeführt wird.

---

## 2. Bundle- & Laufzeit-Architektur

Ein Hauptgrund für die Ablehnung von Apps im App Store ist das bloße Laden einer Remote-URL (z. B. ein WKWebView, das auf `https://taskster.ch` zeigt). 

### Das lokale App-Bundle-Prinzip:
```
[ iPhone / iPad Hardware & iOS Kernel ]
                   ▲
                   │ Native Swift APIs (AVFoundation, LocalAuthentication, APNs)
                   ▼
┌─────────────────────────────────────────────────────────────┐
│  Xcode Native App (.ipa Container)                          │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Capacitor 6 Swift Bridge                              │  │
│  │ (JS ⇄ Swift RPC, Secure Storage, Hardware Access)     │  │
│  └───────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌───────────────────────────────────────────────────────┐  │
│  │ Lokale Web-Assets (capacitor://localhost)             │  │
│  │ • Pre-rendered Vue 3 SPA (Nuxt generate)              │  │
│  │ • Tailwind CSS + Design Tokens                        │  │
│  │ • Offline SQLite Database & Cache Engine              │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘
                   ▲
                   │ Gesicherte REST-Calls (HTTPS / JWT / Zero-Trust)
                   ▼
[ Remote Production Backend: PHP REST API / MariaDB / Cloud ]
```

1. **Kein Remote-Iframe / Kein Web-Bookmark:** Der HTML-, CSS- und JS-Code liegt physisch im Dateisystem der iOS-App auf dem Gerät.
2. **Instant Startup (0 ms Netzwerklatenz beim Öffnen):** Die App startet sofort, selbst im Flugmodus oder im Funkloch auf der Baustelle.
3. **Sichere Kommunikation:** API-Anfragen gehen gezielt an die konfigurierte Server-API (`https://taskster.ch/api/...`). Token werden nicht im unsicheren `localStorage` abgelegt, sondern in der **iOS Keychain**.

---

## 3. Nuxt 3 Konfiguration für iOS Build

Für den iOS-Build wird Nuxt im reinen Client-Side / SPA-Modus generiert:

```typescript
// capacitor-nuxt.config.ts (oder Target-Switch)
export default defineNuxtConfig({
  ssr: false, // Reines SPA-Routing für lokale Native Ausführung
  app: {
    baseURL: './', // Relative Pfad-Referenzen für capacitor://localhost
    buildAssetsDir: '/_nuxt/',
  },
  router: {
    options: {
      hashMode: true // Garantiert stabiles Deep-Linking ohne Server-Fallback
    }
  }
})
```

Build-Workflow:
```bash
# 1. Statische Client-Assets generieren
npx nuxi generate --dotenv .env.mobile

# 2. Assets in das native Xcode-Projekt synchronisieren
npx cap sync ios

# 3. Xcode öffnen und native App kompilieren
npx cap open ios
```

---

## 4. Performance & Touch-Optimierung für iOS

Um das typische "Webview-Gefühl" vollständig zu eliminieren und 60/120 FPS Flüssigkeit zu garantieren:
- **Deaktivierung von Rubber-Banding / Web-Bounce:** Im WKWebView wird Über-Scrollen per CSS (`overscroll-behavior-y: none;`) und nativer Konfiguration unterbunden.
- **Entfernung von 300ms Click-Delays & Tap-Highlights:** `-webkit-tap-highlight-color: transparent;` und CSS Touch-Actions (`touch-action: manipulation;`).
- **GPU-Beschleunigtes Rendering:** CSS-Transitions nutzen ausschließlich `transform` und `opacity` mit `will-change`.
- **Keyboard-Vermeidung:** Integration von `@capacitor/keyboard` mit automatischem Scrollen aktiver Inputs über die native iOS-Tastatur.

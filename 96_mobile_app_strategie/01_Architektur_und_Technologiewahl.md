# 01: Architektur & Technologiewahl (Capacitor 6 Cross-Platform)

## 1. Technologievergleich: Der Weg zur gemeinsamen iOS & Android App

Um Taskster als vollwertige native App in den **Apple App Store** und den **Google Play Store** zu bringen, haben wir uns für **Capacitor 6** entschieden.

| Kriterium | Reines Native (SwiftUI + Jetpack Compose) | React Native / Flutter | Capacitor 6 (Cross-Platform Native Bridge) |
|:---|:---|:---|:---|
| **Codebasis** | 3 Frontends (Web + iOS + Android) | 2 Frontends (Web + RN/Flutter) | **1 gemeinsame Codebasis (Vue 3 / Nuxt 3)** |
| **Bestehende UI/UX** | Komplett neu bauen | Komplett neu bauen | **100% Wiederverwendbar (Tailwind, Liquid Glass)** |
| **Entwicklungsaufwand** | Extrem hoch (9–12 Monate) | Hoch (5–8 Monate) | **Gering–Mittel (3–4 Wochen)** |
| **Store-Zulassung** | Apple & Google | Apple & Google | **Vollwertig für App Store & Play Store** |
| **Hardware-Zugriff** | Direkt via SDKs | Via RN-Bridges | **Via native Swift- & Kotlin-Plugins** |
| **Parallele Releases** | Hoher Synchronisationsaufwand | Mittlerer Aufwand | **Zeitgleiche Updates für Web, iOS & Android** |

### Warum Capacitor 6 die optimale Wahl für Taskster ist:
1. **Single Source of Truth:** Das gesamte Bauleiter- und Projektmanagement (Kanban-Boards, Custom Fields, Stoppuhr, Journal, Kalender, Kontakte) bleibt in einer einzigen Vue 3 / Nuxt 3 Codebasis.
2. **Echte native Container:** Capacitor erzeugt kein simples "Webview-Lesezeichen", sondern:
   - Ein vollständiges **Xcode-Projekt** (Swift) für iOS.
   - Ein vollständiges **Android Studio / Gradle-Projekt** (Kotlin/Java) für Android.
3. **Lokales App-Bundle:** Alle UI-Assets (HTML, CSS, JS, SVGs) liegen im lokalen Dateisystem des Smartphones (`capacitor://localhost` auf iOS, `https://localhost` auf Android).
4. **Keine Remote-Latenz:** Beim Start der App wird kein Server angefragt – die Benutzeroberfläche öffnet sich sofort (0 ms Ladezeit), selbst im Flugmodus oder im Funkloch auf der Baustelle.

---

## 2. Laufzeit- & Bridge-Architektur

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                    Taskster Vue 3 / Nuxt 3 SPA Frontend                     │
│               Tailwind CSS, Liquid Glass Design, Taskster Buttons            │
│                         Lokale SQLite Offline-Engine                        │
└──────────────────────────────────────┬──────────────────────────────────────┘
                                       │
                         Capacitor 6 Native Bridge RPC
                                       │
           ┌───────────────────────────┴───────────────────────────┐
           ▼                                                       ▼
┌──────────────────────────────────────┐  ┌───────────────────────────────────┐
│           iOS Plattform              │  │        Android Plattform          │
│ • Xcode Projekt (.xcworkspace)       │  │ • Android Studio / Gradle Projekt │
│ • Swift Plugins                      │  │ • Kotlin / Java Plugins           │
│ • URL-Schema: capacitor://localhost  │  │ • URL-Schema: https://localhost   │
│ • WKWebView mit iOS Optimierung      │  │ • Android System WebView (Chromium│
│ • Apple Keychain (Sicherer Speicher) │  │ • Android Keystore & Encrypted SP │
│ • AVFoundation & APNs                │  │ • CameraX, MediaRecorder & FCM    │
│ • Apple Human Interface Guidelines   │  │ • Material 3 & Hardware-Back-Taste│
└──────────────────────────────────────┘  └───────────────────────────────────┘
                                       │
                  Gesicherte HTTPS / REST-Pipeline (Zero-Trust)
                                       ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│               Taskster Backend API (PHP REST Router / MariaDB)               │
│               Auth via JWT, Role-Based Access Control, Cloud Storage        │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Nuxt 3 Konfiguration für Cross-Platform Mobile Builds

Für den mobilen Build wird Nuxt im Single-Page-Application (SPA) Modus statisch generiert:

```typescript
// capacitor-nuxt.config.ts (oder Target-Switch via Nuxt Config)
export default defineNuxtConfig({
  ssr: false, // Reines Client-Side SPA-Routing für lokale native Ausführung
  app: {
    baseURL: './', // Relative Asset-Pfade für capacitor:// und https://localhost
    buildAssetsDir: '/_nuxt/',
  },
  router: {
    options: {
      hashMode: true // Garantiert stabiles Deep-Linking ohne Server-Routing-Konflikte
    }
  }
})
```

### Vereinheitlichter Build- & Sync-Workflow:
```bash
# 1. Statische Assets für Mobile generieren
npm run build:mobile

# 2. Assets in BEIDE nativen Projekte übertragen
npx cap sync

# 3. Plattform öffnen
npx cap open ios      # Öffnet Xcode auf macOS
npx cap open android  # Öffnet Android Studio auf Windows/macOS/Linux
```

---

## 4. Performance & Touch-Optimierung (iOS & Android)

Um Webview-Verzögerungen vollständig auszuschließen:
- **Kein Bouncing / Rubber-Banding:** `overscroll-behavior-y: none;` verhindert störendes Nachfedern der Seite.
- **Keine Klick-Verzögerung:** `-webkit-tap-highlight-color: transparent;` und CSS `touch-action: manipulation;` eliminieren den 300ms Klick-Lag auf beiden Plattformen.
- **Hardwarebeschleunigung:** Weiche Übergänge für Modals und Drawer über `transform: translate3d(...)` mit GPU-Unterstützung.
- **Tastatur-Verhalten:** Automatisches Anheben von Inputs via `@capacitor/keyboard`, damit Eingabefelder auf Android und iOS niemals von der Tastatur verdeckt werden.

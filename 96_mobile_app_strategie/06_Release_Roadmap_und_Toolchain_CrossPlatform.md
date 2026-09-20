# 06: Release-Roadmap & Toolchain (iOS & Android)

Dieses Dokument definiert die technische Toolchain, die Kontenanforderungen und den schrittweisen Phasenplan für den gleichzeitigen Release von Taskster im **Apple App Store** und im **Google Play Store**.

---

## 1. Voraussetzungen & Konten

| Plattform | Konto / Zugang | Gebühren | Wichtige Besonderheit |
|:---|:---|:---|:---|
| **Apple iOS** | Apple Developer Program (Organisationskonto) | 99 USD / Jahr | D-U-N-S Nummer erforderlich; Mac mit Xcode zur Kompilierung zwingend nötig. |
| **Google Android** | Google Play Console (Organisationskonto) | 25 USD einmalig | Firmenkonto wählen, um die **20-Tester-Pflicht** für 14 Tage zu umgehen. |
| **Push Backend** | Apple APNs Auth Key (`.p8`) + Google Firebase (`google-services.json`) | Kostenlos | Ermöglicht zentralen Push-Versand vom Taskster-Server an beide Betriebssysteme. |

---

## 2. Technische Toolchain & Build-Umgebungen

```
                               ┌────────────────────────────────┐
                               │   Nuxt 3 SPA (Single Source)   │
                               │      npm run build:mobile      │
                               └───────────────┬────────────────┘
                                               │
                                       npx cap sync
                                               │
                      ┌────────────────────────┴────────────────────────┐
                      ▼                                                 ▼
        ┌───────────────────────────┐                     ┌───────────────────────────┐
        │       iOS Toolchain       │                     │     Android Toolchain     │
        │ • macOS (Silicon M1-M4)   │                     │ • Windows / macOS / Linux │
        │ • Xcode 15 / 16           │                     │ • Android Studio (Koala+) │
        │ • CocoaPods / SPM         │                     │ • Gradle 8.x + JDK 17/21  │
        │ • iOS Simulator / iPhone  │                     │ • Android SDK / Emulator  │
        └─────────────┬─────────────┘                     └─────────────┬─────────────┘
                      │                                                 │
                      ▼                                                 ▼
        ┌───────────────────────────┐                     ┌───────────────────────────┐
        │  Apple TestFlight (.ipa)  │                     │ Google Play Internal Track│
        │   fastlane ios beta       │                     │    fastlane android beta  │
        └─────────────┬─────────────┘                     └─────────────┬─────────────┘
                      │                                                 │
                      ▼                                                 ▼
        ┌───────────────────────────┐                     ┌───────────────────────────┐
        │   Apple App Store Live    │                     │  Google Play Store Live   │
        └───────────────────────────┘                     └───────────────────────────┘
```

---

## 3. Parallele 6-Phasen-Roadmap bis zum Dual-Store Go-Live

### Phase 1: Web-Codebasis Mobile-Ready (Dauer: ca. 3–5 Tage)
- [ ] Safe-Area-Insets (`--sat`, `--sab`) im Tailwind-Layout einbinden.
- [ ] Responsive Bottom Navigation Bar für Smartphones integrieren.
- [ ] Android Hardware-Back-Button Listener (`App.addListener('backButton')`) implementieren.
- [ ] Zwingende Account-Löschung unter Profil/Einstellungen einbauen (Guideline 5.1.1(v) für Apple & Google Play).
- [ ] Externe Stripe-/Kauf-Verlinkungen für Mobile-Target ausblenden (Multiplatform SaaS).

### Phase 2: Capacitor 6 Setup für iOS & Android (Dauer: ca. 1–2 Tage)
- [ ] Installation der Capacitor Pakete:
  ```bash
  npm i @capacitor/core @capacitor/cli @capacitor/ios @capacitor/android
  ```
- [ ] Initialisierung der App:
  ```bash
  npx cap init "Taskster" "ch.kurka.taskster"
  ```
- [ ] Plattformen hinzufügen:
  ```bash
  npx cap add ios
  npx cap add android
  ```
- [ ] Generierung einheitlicher App-Icons (1024x1024px) und Splash-Screens für iOS & Android via `@capacitor/assets`.

### Phase 3: Cross-Platform Hardware-Plugins (Dauer: ca. 3–4 Tage)
- [ ] `@capacitor/camera`: Baustellenkamera mit Vorab-Kompression auf 2048px / 600 KB.
- [ ] `@capacitor-community/media-recorder`: Audioaufnahme mit Backend-Transkription (Whisper Turbo).
- [ ] `@capgo/capacitor-native-biometric`: Biometrie (Face ID auf iOS, BiometricPrompt auf Android).
- [ ] `@capacitor/haptics`: Taktiles Vibrationsfeedback.
- [ ] `@capacitor/push-notifications`: Push-Registrierung (APNs + FCM).
- [ ] Konfiguration von `Info.plist` (iOS) und `AndroidManifest.xml` (Android).

### Phase 4: Offline-Engine & SQLite Sync (Meilenstein M6, ca. 5–7 Tage)
- [ ] Einbindung von `@capacitor-community/sqlite` für lokale persistente Datenspeicherung.
- [ ] Lokale `mutation_queue` für Offline-Änderungen auf Baustellen.
- [ ] Asynchroner Sync-Worker bei Wiederverbindung (`@capacitor/network`).

### Phase 5: Parallele Beta-Phase (Dauer: ca. 1–2 Wochen)
- [ ] **iOS:** Upload via Xcode / Fastlane zu **Apple TestFlight**.
- [ ] **Android:** Upload des signierten `.aab` in den **Google Play Closed / Internal Testing Track**.
- [ ] Echter Härtetest auf Baustellen durch Martin und ausgewählte Bauleiter (iPhones & Samsung/Pixel-Geräte).

### Phase 6: Dual-Store Submission & Review (Dauer: ca. 3–5 Tage)
- [ ] **App Store Connect:**
  - Screenshots für 6.7" und 6.5" iPhones.
  - Reviewer-Notizen mit Demo-Account (`apple-review@taskster.ch`).
  - Einreichung zur Prüfung (Dauer: ca. 24–48 Std.).
- [ ] **Google Play Console:**
  - Screenshots für Telefon und Tablet.
  - Ausfüllen des Fragebogens zur Datensicherheit (Data Safety).
  - Einreichung zur Prüfung (Dauer: ca. 24–72 Std.).
- [ ] **Gleichzeitige Freigabe im Apple App Store & Google Play Store.**

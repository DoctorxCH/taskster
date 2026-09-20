# 2026-09-20: Erweiterung & Vereinheitlichung Mobile Strategie (iOS & Android via Capacitor 6)

## Kontext & Anforderung
Erweiterung der mobilen Bereitstellungsstrategie auf Google Android (Google Play Store) und vollständige Verknüpfung beider Plattformen über eine gemeinsame Capacitor 6 Architektur.
Umbenennung und Strukturierung des 90er-Ordners zu `96_mobile_app_strategie/`.

## Durchgeführte Arbeiten
1. **Ordner `96_mobile_app_strategie/` etabliert & verknüpft:**
   - `00_INDEX.md`: Master-Index für iOS & Android.
   - `01_Architektur_und_Technologiewahl.md`: Capacitor 6 Single-Source-of-Truth für Nuxt 3 SPA; lokales Asset-Hosting (`capacitor://` und `https://localhost`); 0 ms Latenz.
   - `02_iOS_App_Store_Compliance_und_Guidelines.md`: Apple App Store Prüfung, Guideline 4.2, 3.1.3(a), Account Deletion 5.1.1(v), Privacy Manifests.
   - `03_Android_Play_Store_Compliance_und_Policies.md`: Google Play Webview-Richtlinie; Organisationskonto zur Umgehung der 20-Tester-Pflicht; Target SDK 34; Android App Bundle (`.aab`); Datensicherheit & Android-Berechtigungen.
   - `04_Native_Hardware_Features_CrossPlatform.md`: Vereinheitlichte Hardware-Bridges: Kamera mit Vorab-Kompression (2048px); Voice-Recorder (.m4a AAC) mit KI-Transkription (Whisper Turbo); APNs & FCM Push; Biometrie (Face ID + Fingerabdruck/BiometricPrompt); Lokale SQLite-Sync-Queue (M6).
   - `05_UI_UX_HIG_und_Material_Anpassungen.md`: Adaptive UI (Apple HIG & Google Material 3); Android Hardware-Back-Button Listener (verhindert versehentliches Schließen); Safe-Areas; Mobile Bottom Navigation & 2-Klick FAB.
   - `06_Release_Roadmap_und_Toolchain_CrossPlatform.md`: Build-Toolchain (Xcode + Android Studio/Gradle), Fastlane Beta-Pipelines (TestFlight + Play Console Internal), 6-Phasen-Roadmap bis zum Dual-Store Go-Live.
2. **Index-Aktualisierung:**
   - `99_anweisungen/00_INDEX.md` aktualisiert auf `96_mobile_app_strategie/`.

## Maschinenlesbare Zusammenfassung
- **Typ:** ARCHITEKTUR / DOKU / CROSS-PLATFORM
- **Pfad:** `96_mobile_app_strategie/`
- **Framework:** Capacitor 6 (iOS & Android)
- **Status:** Vollständige Konzeption für App Store & Google Play Store abgeschlossen

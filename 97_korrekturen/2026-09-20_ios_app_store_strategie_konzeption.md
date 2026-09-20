# 2026-09-20: Konzeption & Strategie iOS App Store Bereitstellung

## Kontext & Anforderung
Erstellung einer vollständigen Strategie zur Veröffentlichung von Taskster im offiziellen Apple App Store (keine reine PWA/Webapp).
Einrichtung des neuen 90er Ordners `96_ios_app_strategie/` inklusive aller Richtlinien, Architektur-, Hardware- und Compliance-Dokumente.

## Durchgeführte Arbeiten
1. **Ordner `96_ios_app_strategie/` etabliert:**
   - `00_INDEX.md`: Inhaltsverzeichnis und Übersicht.
   - `01_Architektur_und_Technologiewahl.md`: Capacitor 6 + Nuxt 3 SPA vs. SwiftUI; lokales Bundle (`capacitor://localhost`); 0 ms Latenz; Touch/Scroll-Optimierung.
   - `02_App_Store_Compliance_und_Guidelines.md`: Umgehung von Guideline 4.2 ("No Repackaged Websites"); B2B SaaS Multiplatform-Regeln (Guideline 3.1.3(a)); Zwingende In-App Account-Löschung (Guideline 5.1.1(v)); Info.plist Berechtigungsbegründungen und `PrivacyInfo.xcprivacy`.
   - `03_Native_Features_und_Anforderungen.md`: Hardware-Integration für Baustellen (Kamera mit On-Device-Kompression auf 2048px; Voice Recorder .m4a AAC; APNs Push-Notifications; Face ID / Biometrie; Lokale Offline-Engine mit SQLite & Sync-Queue für Meilenstein M6).
   - `04_UI_UX_HIG_Anpassungen.md`: Apple Human Interface Guidelines (Safe-Area-Insets, 44x44pt Touch-Targets, Mobile Bottom Tab Bar statt Desktop-Sidebar, UIFeedbackGenerator Haptik, iOS Bottom Sheets für Modals wie VoiceRecorder).
   - `05_Release_Roadmap_und_Toolchain.md`: Toolchain (Capacitor, Xcode, Fastlane), Apple Developer Voraussetzungen, 6-Phasen-Roadmap bis zum Go-Live.
2. **Aktualisierung Index:**
   - `99_anweisungen/00_INDEX.md` erweitert um Verlinkung auf `96_ios_app_strategie/`.

## Maschinenlesbare Zusammenfassung
- **Typ:** ARCHITEKTUR / DOKU / KONZEPT
- **Pfad:** `96_ios_app_strategie/`
- **Ziel:** Apple App Store Zulassung ohne Ablehnung
- **Status:** Strategie abgeschlossen; bereit für Implementierung bei Start von Meilenstein M6

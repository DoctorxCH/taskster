# Taskster – iOS App Store Strategie & Spezifikation

Dieses Verzeichnis enthält die vollständige Strategie, Anforderungsanalyse, Compliance-Vorgaben und die technische Roadmap, um aus Taskster eine native, im offiziellen Apple App Store vertriebene iOS-App zu machen.

---

## 📑 Inhaltsübersicht

| Dokument | Fokus & Inhalt |
|:---|:---|
| **[01_Architektur_und_Technologiewahl.md](file:///c:/Users/marti/Taskster/96_ios_app_strategie/01_Architektur_und_Technologiewahl.md)** | Capacitor 6 + Nuxt 3 SPA vs. SwiftUI; Bundle-Architektur (`capacitor://localhost`); Lokale Runtime; Performance-Optimierung. |
| **[02_App_Store_Compliance_und_Guidelines.md](file:///c:/Users/marti/Taskster/96_ios_app_strategie/02_App_Store_Compliance_und_Guidelines.md)** | Apple Review Guidelines; Ablehnungsfallen vermeiden (Guideline 4.2 "No Repackaged Websites"); B2B SaaS Monopolisierung (Guideline 3.1.3(a)); Account-Löschung (5.1.1(v)); Privacy Manifests. |
| **[03_Native_Features_und_Anforderungen.md](file:///c:/Users/marti/Taskster/96_ios_app_strategie/03_Native_Features_und_Anforderungen.md)** | Native Hardware-Integration: Baustellen-Kamera & Bildkompression; Voice-Recorder (.m4a AAC); APNs Push-Notifications; Face ID / Biometrie; Lokale Offline-Engine mit SQLite & Sync-Queue. |
| **[04_UI_UX_HIG_Anpassungen.md](file:///c:/Users/marti/Taskster/96_ios_app_strategie/04_UI_UX_HIG_Anpassungen.md)** | Apple Human Interface Guidelines (HIG); Safe-Area-Insets (Notch / Dynamic Island); Native Touch Targets (min. 44x44pt); Haptik (UIFeedbackGenerator); iOS Navigation & Gesten. |
| **[05_Release_Roadmap_und_Toolchain.md](file:///c:/Users/marti/Taskster/96_ios_app_strategie/05_Release_Roadmap_und_Toolchain.md)** | Apple Developer Enterprise/Organization Account; Xcode-Konfiguration; TestFlight Beta-Testing; Fastlane CI/CD; Meilenstein-Plan bis zum Go-Live. |

---

## 🎯 Kernziel & Rahmenbedingungen
1. **Keine bloße Webapp / Kein PWA-Bookmark:** Apple weist reine Webapp-Wrapper (Apps ohne spürbare native Geräteintegration) rigoros nach **Guideline 4.2** ab. Die Taskster iOS App nutzt tiefgreifende native APIs (Kamera, Diktat/Audio, Biometrie, Push, SQLite-Offline-Sync).
2. **Single-Source-of-Truth:** Maximale Wiederverwendung der existierenden Vue 3 / Nuxt 3 Komponenten und des Tailwind Design-Systems (`#00A3C4`, Liquid Glass, `taskster_button`).
3. **B2B / Multiplatform SaaS:** Einhaltung der Apple Payment Richtlinien nach Guideline 3.1.3(a) (Reader / Multiplatform Services), um 30% Apple-Tax auf Unternehmenskonten zu vermeiden.

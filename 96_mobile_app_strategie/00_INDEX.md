# Taskster – Cross-Platform Mobile Strategie (iOS & Android)

Dieses Verzeichnis enthält die vollständige Strategie, Anforderungsanalyse, Compliance-Vorgaben und die technische Roadmap, um aus Taskster über **Capacitor 6** native Apps für den **Apple App Store** und den **Google Play Store** bereitzustellen.

---

## 📑 Inhaltsübersicht

| Dokument | Fokus & Inhalt |
|:---|:---|
| **[01_Architektur_und_Technologiewahl.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/01_Architektur_und_Technologiewahl.md)** | **Capacitor 6 Cross-Platform:** 1 gemeinsame Nuxt 3 SPA-Codebasis für iOS & Android; lokales Bundle (`capacitor://` und `https://localhost`); 0 ms Latenz; Touch/Scroll-Optimierung. |
| **[02_iOS_App_Store_Compliance_und_Guidelines.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/02_iOS_App_Store_Compliance_und_Guidelines.md)** | **Apple App Store Guidelines:** Ablehnungsfallen vermeiden (Guideline 4.2 "No Repackaged Websites"); B2B Multiplatform-Modell (3.1.3(a)); Zwingende Account-Löschung (5.1.1(v)); Privacy Manifests. |
| **[03_Android_Play_Store_Compliance_und_Policies.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/03_Android_Play_Store_Compliance_und_Policies.md)** | **Google Play Store Policies:** Webview-Richtlinie; Umgehung der 14-Tage / 20-Tester-Pflicht durch Organisationskonto; Target SDK 34; Android App Bundle (`.aab`); Datensicherheit & Berechtigungen. |
| **[04_Native_Hardware_Features_CrossPlatform.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/04_Native_Hardware_Features_CrossPlatform.md)** | **Native Hardware-Bridges:** Baustellen-Kamera (Kompression auf 2048px); Voice-Recorder (.m4a AAC) mit KI-Transkription; Push (APNs + FCM); Biometrie (Face ID + Fingerabdruck); Lokale SQLite-Sync-Queue (M6). |
| **[05_UI_UX_HIG_und_Material_Anpassungen.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/05_UI_UX_HIG_und_Material_Anpassungen.md)** | **Adaptive UI/UX (Apple HIG & Material 3):** Android Hardware-Back-Button Handling (Modals schließen); Safe Areas (Notch, Dynamic Island, Cutouts); Mobile Bottom Navigation Bar & FAB; Taktiles Feedback. |
| **[06_Release_Roadmap_und_Toolchain_CrossPlatform.md](file:///c:/Users/marti/Taskster/96_mobile_app_strategie/06_Release_Roadmap_und_Toolchain_CrossPlatform.md)** | **Build-Toolchains & Release:** Xcode (macOS) & Android Studio (Windows/Mac); Apple TestFlight & Google Play Testing; Fastlane CI/CD; 6-Phasen-Roadmap bis zum gleichzeitigen Store-Launch. |

---

## 🎯 Kernprinzipien
1. **Kein Webview-Lesezeichen:** Apple und Google fordern spürbare native Gerätefunktionen. Taskster nutzt native Kameras, Audio-Diktate, Biometrie, Push-Dienste und eine robuste Offline-Engine.
2. **Single Source of Truth:** Vue 3, Nuxt 3 und Tailwind CSS bleiben der Standard. Neue Features stehen sofort im Web, auf iOS und auf Android zur Verfügung.
3. **B2B SaaS Konformität:** Schutz vor der 30%-Abgabe von Apple und Google durch konsequente Nutzung der Multiplatform-Service-Richtlinien.

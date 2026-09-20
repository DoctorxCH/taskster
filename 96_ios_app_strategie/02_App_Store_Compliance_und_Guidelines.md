# 02: Apple App Store Compliance & Guidelines

Um von den Apple App Reviewern ohne Verzögerung oder Ablehnung zugelassen zu werden, müssen die Richtlinien der **App Store Review Guidelines** strikt eingehalten werden. Hier sind die kritischsten Prüfpunkte für Taskster und die verbindliche Umsetzungsstrategie.

---

## 1. Guideline 4.2: Mindestfunktionalität ("No Repackaged Websites")

> **App Store Regel:** *"Your app should include features, content, and UI that elevate it beyond a repackaged website. If your app is not particularly useful, unique, or 'app-like,' it doesn't belong on the App Store."*

Reine Web-Wrapper scheitern ausnahmslos an dieser Regel. Taskster besteht die Prüfung durch folgende native Mehrwerte:

| Natives Feature | Nachweis für Apple Reviewer | Nutzen im Bau- & Projektalltag |
|:---|:---|:---|
| **Lokale Offline-Engine (M6)** | Flugmodus-Test: App bleibt voll bedienbar, Aufgaben können angelegt werden, Timer läuft. | Zuverlässiges Arbeiten in Funklöchern (z. B. Keller, Rohbau). |
| **Native Kamera & Geotagging** | Direkte Hardware-Steuerung, EXIF/GPS-Erfassung, Bildkompression vor dem Upload. | Fotodokumentation von Baumängeln direkt in Tasks. |
| **Natives Audio-Diktat** | Zugriff auf `AVAudioSession`, hardwarebeschleunigte .m4a AAC Kompression. | Freisprech-Sprachnotizen für Bauleiter auf der Baustelle. |
| **Biometrischer Login (Face ID)** | Schnellanmeldung via iOS `LocalAuthentication` Framework. | Schneller Zugriff ohne Tastatur-Gefummel bei Schmutz/Handschuhen. |
| **Push-Notifications (APNs)** | System-Benachrichtigungen bei Aufgabenzuweisungen und Stoppuhr-Warnungen. | Termintreue und Live-Statusupdates ohne geöffnete App. |
| **Haptisches Feedback** | Physische Vibrationen via `UIFeedbackGenerator`. | Spürbare Rückmeldung bei Aktionen (Abhaken, Speichern). |

---

## 2. Guideline 3.1.1 & 3.1.3(a): Zahlungen & B2B SaaS Monopolisierung

Apple verlangt bei digitalen Gütern grundsätzlich 15–30 % Umsatzbeteiligung via In-App Purchases (IAP). Für B2B-SaaS-Lösungen wie Taskster greift jedoch **Guideline 3.1.3(a) (Multiplatform Services)**:

### Die saubere B2B-SaaS-Strategie:
1. **Keine externen Bezahllinks in der iOS-App:**
   - Es darf in der App **keinen** Button geben mit "Jetzt Pro auf unserer Website für 19€ buchen" oder einen Link direkt zum Stripe-Checkout. Apple verbietet Verweise auf externe Bezahlmethoden.
2. **Multiplatform-Login-Modell:**
   - Die App fungiert als Arbeits-Client für bestehende Firmenkonten. Der Willkommensbildschirm bietet den Login mit bestehenden Zugangsdaten oder Single Sign-On (SSO).
   - In den Accounteinstellungen wird der Status angezeigt (z. B. *"Plan: Pro (Verwaltet durch Firma XYZ)"*).
3. **Optional (für Einzel-Nutzer):**
   - Falls Einzel-Abonnements (z. B. "Taskster Pro für Einzelunternehmer") direkt in der iOS-App gekauft werden sollen, muss hierfür StoreKit 2 (Apple In-App Purchase) integriert werden. Unternehmenskunden buchen weiterhin über die Webplattform.

---

## 3. Guideline 5.1.1(v): Zwingende In-App Account-Löschung

Seit dem 30. Juni 2022 gilt für alle Apps mit Registrierung:
- Wenn Benutzer in der App ein Konto erstellen können, **müssen** sie dieses auch direkt in der App löschen können.
- **Anforderung an Taskster:**
  - Unter `Einstellungen -> Mein Profil -> Konto` muss ein gut sichtbarer Button vorhanden sein: `Account dauerhaft löschen` (im Stil `taskster_button_accent`).
  - Ein 2-Stufen-Bestätigungsdialog klärt über die Löschung auf und führt die DSGVO-konforme Löschung des Nutzers serverseitig durch (`DELETE /api/users/me`).

---

## 4. Guideline 5.1.2: Privacy Manifest & Info.plist Berechtigungen

Ab iOS 17 verlangt Apple eine Datei `PrivacyInfo.xcprivacy` sowie verständliche Begründungen in der `Info.plist`:

```xml
<!-- Info.plist Ausschnitt -->
<key>NSCameraUsageDescription</key>
<string>Taskster benötigt Zugriff auf die Kamera, um Fotos von Baustellen und Mängeln direkt an Aufgaben anzuhängen.</string>

<key>NSMicrophoneUsageDescription</key>
<string>Taskster benötigt das Mikrofon, um Sprachmemos und Diktate vor Ort aufzunehmen.</string>

<key>NSPhotoLibraryUsageDescription</key>
<string>Ermöglicht das Auswählen bestehender Baustellenfotos und Baupläne aus Ihrer Fotomediathek.</string>

<key>NSFaceIDUsageDescription</key>
<string>Ermöglicht das sichere und schnelle Entsperren von Taskster mittels Face ID.</string>

<key>NSLocationWhenInUseUsageDescription</key>
<string>Wird verwendet, um Mängelfotos automatisch dem exakten Baustellenstandort zuzuordnen.</string>
```

> **Wichtig:** Generische Texte wie *"Kamerazugriff erforderlich"* führen zur sofortigen Ablehnung. Der Zweck muss präzise auf den Anwendungsfall bezogen sein.

---

## 5. Guideline 2.1: Vorbereitung für den Apple Reviewer

Damit der Apple-Tester die App prüfen kann, muss in **App Store Connect** folgendes hinterlegt sein:
1. **Dedizierter Demo-Account:**
   - Ein voll funktionsfähiger Benutzer (z. B. `apple-review@taskster.ch`) mit voreingestellten Demo-Projekten, Aufgaben, Checklisten und einem Beispielfoto.
2. **Reviewer-Hinweise (Notes):**
   - Kurze Erklärung, dass es sich um ein Projektmanagement-System für Baustellen und Teams handelt.
   - Anleitung zur Nutzung der Offline-Funktion (z. B. Flugmodus aktivieren und Task erstellen).
   - Test-Instruktion für Audioaufnahme und Kamera.

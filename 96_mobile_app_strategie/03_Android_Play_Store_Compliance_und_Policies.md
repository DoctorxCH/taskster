# 03: Google Play Store Compliance & Richtlinien (Android)

Um Taskster erfolgreich und ohne Verzögerung im **Google Play Store** zu veröffentlichen, müssen die Google Play Developer Program Policies strikt eingehalten werden. Hier sind die spezifischen Anforderungen und Best Practices für Android.

---

## 1. Google Play Richtlinie: Mindestfunktionalität & Webview-Vorgaben

> **Google Play Richtlinie:** *"Apps, die lediglich eine Website einbetten, ohne gerätespezifische Mehrwerte zu bieten (Webview-Wrapper), oder eine schlechte Nutzererfahrung aufweisen, sind im Google Play Store nicht zulässig."*

Genau wie Apple weist Google reine Website-Verpackungen ab. Taskster erfüllt die Google Play Qualitätskriterien durch:
1. **Lokale Offline-Fähigkeit:** Volle Bedienbarkeit auch ohne Internetverbindung (Baustellenkeller / Funkloch).
2. **Native Kamera- & Dateianbindung (CameraX):** Direkte Hardware-Steuerung mit automatischer Bildkompression vor dem Upload.
3. **Hardware-Mikrofonzugriff:** Sprachmemos und Diktate mit nativer Tonaufnahme.
4. **Push-Benachrichtigungen via FCM:** Zeitkritische Benachrichtigungen über Firebase Cloud Messaging.
5. **Biometrische Schnellanmeldung (BiometricPrompt):** Fingerabdruck / Gesichtserkennung via Android Biometrics.
6. **Native System-Integration:** Behandlung der Android-Hardware-Zurücktaste (Back Button).

---

## 2. Google Play Developer Account & Die "20-Tester-Regel"

Google hat die Registrierung neuer Entwicklerkonten verschärft:

| Kontotyp | Anforderungen & Test-Pflicht | Empfehlung für Taskster |
|:---|:---|:---|
| **Privates Konto (Einzelperson)** | **Strenge Hürde:** Mindestens **20 Tester** müssen die App für **mindestens 14 Tage ununterbrochen** im Closed-Test installiert haben, bevor die Freigabe für den Play Store überhaupt beantragt werden darf! | **Nicht empfohlen** für professionelle B2B-Software. |
| **Organisationskonto (Firma / Unternehmen)** | Verifikation über Handelsregisterauszug und **D-U-N-S Nummer**. **Keine 14-Tage / 20-Tester-Pflicht** vor der ersten Produktionsveröffentlichung! | **Verbindlich empfohlen!** Registrierung als Schweizer Unternehmen (Kurka / Taskster). |

---

## 3. Google Play Billing vs. B2B Enterprise SaaS

Google verlangt für In-App-Käufe digitaler Inhalte grundsätzlich Google Play Billing (15–30 % Gebühr). 

### Ausnahmeregelung für B2B Multiplattform-Software:
- Nach Google Play Richtlinie dürfen Benutzer auf digitale Inhalte und Abonnements zugreifen, die sie außerhalb der App (z. B. im Web auf `taskster.ch` oder per B2B-Rechnung) erworben haben (**Multiplatform Services**).
- **Verbindliche Regel für die Android App:**
  - Die App bietet einen Login für bestehende Konten.
  - Es dürfen in der App **keine externen Zahlungslinks** platziert werden (z. B. *"Jetzt auf taskster.ch/upgrade upgraden"* führt zur Ablehnung).
  - Lizenzstatus und Company-Zuordnung werden informativ dargestellt (z. B. *"Lizenz: Business"*).

---

## 4. Ziel-API-Level & Android App Bundle (AAB)

1. **Target SDK Requirement:**
   - Google verlangt, dass neue Apps mindestens das jeweils aktuelle Android SDK adressieren (derzeit **Target SDK 34 / Android 14**). Capacitor 6 erfüllt dies standardmäßig.
2. **64-Bit-Unterstützung:**
   - Alle nativen Binaries müssen 64-Bit-Architekturen (`arm64-v8a`, `x86_64`) unterstützen.
3. **Android App Bundle (.aab):**
   - Google akzeptiert für neue Apps im Play Store **keine reinen .apk-Dateien** mehr. Der Build muss als `.aab` erzeugt werden. Google Play generiert daraus optimierte, kleinere Downloads für jedes Endgerät.

---

## 5. Android Berechtigungen & `AndroidManifest.xml`

Ab Android 13 (API 33) gelten strengere Berechtigungsregeln. Es dürfen nur Berechtigungen angefordert werden, die zwingend für die Funktion benötigt werden.

```xml
<!-- AndroidManifest.xml Ausschnitt -->
<manifest xmlns:android="http://schemas.android.com/apk/res/android">

    <!-- Internet & Netzwerkstatus für Offline-Sync -->
    <uses-permission android:name="android.permission.INTERNET" />
    <uses-permission android:name="android.permission.ACCESS_NETWORK_STATE" />

    <!-- Baustellenkamera & Fotos -->
    <uses-permission android:name="android.permission.CAMERA" />
    
    <!-- Audioaufnahme für Voice-Memos -->
    <uses-permission android:name="android.permission.RECORD_AUDIO" />

    <!-- Push-Notifications (Zwingend ab Android 13 / API 33) -->
    <uses-permission android:name="android.permission.POST_NOTIFICATIONS" />

    <!-- Biometrie für Fingerabdruck / Face Unlock -->
    <uses-permission android:name="android.permission.USE_BIOMETRIC" />

    <!-- Haptisches Feedback -->
    <uses-permission android:name="android.permission.VIBRATE" />

</manifest>
```

> **Hinweis zu Scoped Storage:** Die veraltete Berechtigung `WRITE_EXTERNAL_STORAGE` wird unter modernen Android-Versionen **nicht** mehr benötigt, da Bilder und Audios in der privaten App-Sandbox verarbeitet werden.

---

## 6. Google Play "Datensicherheit" (Data Safety Section)

Vor der Veröffentlichung muss in der Google Play Console das Formular zur Datensicherheit ausgefüllt werden:

| Datentyp | Gesammelt? | Zweck | Verschlüsselt übertragen? |
|:---|:---|:---|:---|
| **Fotos & Videos** | Ja (vom Nutzer hochgeladen) | App-Funktionalität (Baustellenmängel) | Ja (HTTPS / TLS 1.3) |
| **Sprach- / Audioaufnahmen** | Ja (Diktatfunktion) | App-Funktionalität (Sprachnotizen) | Ja (HTTPS / TLS 1.3) |
| **Standort** | Optional (Baustellenort) | Baustellendokumentation | Ja (HTTPS / TLS 1.3) |
| **Nutzerkennung & E-Mail** | Ja (Login-Konto) | Kontoverwaltung & Auth | Ja (HTTPS / TLS 1.3) |
| **Diagnosedaten / Crashes** | Ja (anonymisiert) | Fehlerbehebung & Stabilität | Ja (HTTPS / TLS 1.3) |

Zudem muss der Link zur offiziellen **Datenschutzerklärung** (Privacy Policy, z. B. `https://taskster.ch/privacy`) hinterlegt werden.

# 04: Native Hardware-Features & Cross-Platform Bridges

Capacitor 6 fungiert als Brücke zwischen der gemeinsamen Vue 3 / Nuxt 3 Codebasis und den nativen Hardware-APIs von iOS und Android. Durch eine einheitliche TypeScript-Schnittstelle greift der Code plattformunabhängig auf die Hardware zu, während darunter die jeweiligen nativen Betriebssystem-Frameworks ausgeführt werden.

---

## 1. Übersicht der Cross-Platform Bridges

| Feature | TypeScript Plugin | Native iOS Implementierung | Native Android Implementierung |
|:---|:---|:---|:---|
| **Baustellenkamera** | `@capacitor/camera` | `AVFoundation` / Photos UI | `CameraX` / ImageCapture Intent |
| **Voice-Recorder** | `@capacitor-community/media-recorder` | `AVAudioSession` (.m4a AAC) | `MediaRecorder` (.m4a AAC / Opus) |
| **Push-Notifications**| `@capacitor/push-notifications` | Apple Push Service (APNs) | Firebase Cloud Messaging (FCM) |
| **Biometrie** | `@capgo/capacitor-native-biometric` | `LocalAuthentication` (Face ID) | `BiometricPrompt` & Keystore |
| **Offline SQLite** | `@capacitor-community/sqlite` | Native SQLite Library (iOS) | Android SQLite & Room Database |
| **Haptik** | `@capacitor/haptics` | `UIFeedbackGenerator` | `VibrationEffect` / Vibrator |
| **Netzwerkstatus** | `@capacitor/network` | `NWPathMonitor` | `ConnectivityManager` |
| **Tastatur** | `@capacitor/keyboard` | InputAccessoryView / Safe Layout | WindowInsets & AdjustResize |

---

## 2. Baustellen-Kamera mit automatischer Vorab-Kompression

Auf Baustellen herrschen widrige Bedingungen: schlechter Empfang und riesige Rohbild-Dateien (bis zu 48 MP auf iPhone 15/16 oder Samsung S24). 

### Plattformübergreifende Logik:
1. **On-Device Downscaling:** Maximale Kantenlänge 2048 Pixel.
2. **Kompression:** JPEG mit 85 % Qualität.
3. **Dateigrößen-Reduktion:** Von ~15 MB auf ca. 400–600 KB bei gestochen scharfer Lesbarkeit von Rissen, Bauplänen und Mängeln.

```typescript
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera'

export async function captureSitePhoto() {
  const photo = await Camera.getPhoto({
    quality: 85,
    allowEditing: false,
    resultType: CameraResultType.Uri,
    source: CameraSource.Camera,
    width: 2048,
    correctOrientation: true,
    saveToGallery: false // Kein Zumüllen der privaten Fotogalerie
  })

  return {
    path: photo.webPath,
    format: photo.format
  }
}
```

---

## 3. Voice-Recorder & KI-Transkription (Whisper Turbo)

Die Sprachaufnahme ermöglicht die Mobile-First 2-Klick-Regel für Bauleiter.

1. **Audio-Encoding:**
   - **iOS:** Kodierung in AAC (`.m4a`) mit 64 kbit/s.
   - **Android:** Kodierung in AAC (`.m4a`) oder WebM/Opus.
2. **KI-Pipeline:**
   - Die temporäre Audiodatei wird nach Beendigung der Aufnahme via `FormData` an das Backend gesendet:
   - `POST /api/ai/transcribe` (Whisper Turbo Modell).
   - Der transkribierte Text landet ohne Tipparbeit direkt im Task oder im Baustellenjournal.

---

## 4. Push-Notifications: APNs (Apple) & FCM (Google)

Zur Benachrichtigung bei Aufgaben-Zuweisungen, Fristen und dem 4-Stunden-Stoppuhr-Wächter:

```typescript
import { PushNotifications } from '@capacitor/push-notifications'

export async function initPushNotifications() {
  const permission = await PushNotifications.requestPermissions()
  if (permission.receive === 'granted') {
    await PushNotifications.register()
  }

  // Token-Registrierung (funktioniert auf iOS via APNs und Android via FCM identisch)
  PushNotifications.addListener('registration', async (token) => {
    await $fetch('/api/users/device-token', {
      method: 'POST',
      body: {
        token: token.value,
        platform: Capacitor.getPlatform() // 'ios' | 'android'
      }
    })
  })
}
```

- **Backend-Zustellung:** Der Taskster-Server unterscheidet anhand des Plattformfelds, ob die Push-Nachricht über Apple APNs (HTTP/2 mit `.p8` Key) oder Google FCM (`firebase-admin` HTTP v1) versendet wird.

---

## 5. Biometrie: Face ID & Fingerabdruck mit Token-Verschlüsselung

Passworteingaben sind auf Baustellen unpraktisch. Nach dem ersten Login wird der Zugang biometrisch geschützt:

1. **Plattformunabhängige Abfrage:**
```typescript
import { NativeBiometric } from '@capgo/capacitor-native-biometric'

export async function verifyBiometric(): Promise<boolean> {
  const result = await NativeBiometric.isAvailable()
  if (!result.isAvailable) return false

  const verified = await NativeBiometric.verifyIdentity({
    reason: 'Taskster mit Biometrie entsperren',
    title: 'Biometrische Anmeldung',
    subtitle: 'Bestätigen Sie Ihre Identität',
    description: 'Face ID / Fingerabdruck verwenden'
  })

  return verified.status === 'SUCCESS'
}
```
2. **Sichere Token-Ablage:**
   - **iOS:** Hinterlegung im hardware-verschlüsselten `Keychain` Speicher.
   - **Android:** Hinterlegung in den `EncryptedSharedPreferences` unter Nutzung des `Android Keystore`.

---

## 6. Lokale Offline-Engine & SQLite Synchronisation (Meilenstein M6)

Die Offline-Engine garantiert lückenloses Arbeiten bei Verbindungsabbrüchen.

```
┌─────────────────────────────────────────────────────────────┐
│                 Taskster Vue 3 Anwendung                    │
│    (Erstellt Task "Riss im Keller abdichten" im Funkloch)   │
└──────────────────────────────┬──────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Lokaler SQLite-Speicher (@capacitor-community/sqlite)       │
│ • Optimistisches UI: Task erscheint sofort im Board         │
│ • Eintrag in lokaler Tabelle 'mutation_queue'               │
│   (uuid, action='CREATE_TASK', payload={...}, timestamp)    │
└──────────────────────────────┬──────────────────────────────┘
                               │
                     Wiederverbindung erkannt
                     (@capacitor/network Event)
                               │
                               ▼
┌─────────────────────────────────────────────────────────────┐
│ Asynchroner Reconnect-Sync-Worker                           │
│ • Replay aller anstehenden Einträge an /api/sync/batch      │
│ • Konfliktbehandlung (Server-Timestamp prüft Rechte & Locks)│
│ • Bereinigung der lokalen Queue nach Server-Bestätigung     │
└─────────────────────────────────────────────────────────────┘
```

- **Strikte Datenhygiene:** Der Offline-Cache speichert ausschließlich Datensätze von Projekten, auf die der angemeldete Nutzer Zugriff hat (Zero-Trust-Prinzip).

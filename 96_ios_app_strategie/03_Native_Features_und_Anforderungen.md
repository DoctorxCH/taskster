# 03: Native Features & Hardware-Integration

Für eine echte iOS-App auf Profi-Niveau müssen Kernfunktionen direkt mit den Hardware-Schnittstellen des iPhones und iPads interagieren. Hier sind die Spezifikationen für die nativen Komponenten.

---

## 1. Baustellen-Kamera & Intelligente Bildkompression

### Herausforderung auf Baustellen
Moderne iPhones nehmen Fotos mit bis zu 48 Megapixeln im HEIC-Format auf (Dateigrößen 10–25 MB). Ein Upload über schwache 3G/Edge-Netze im Rohbau schlägt fehl oder verbraucht massiv Datenvolumen.

### Native Lösung via `@capacitor/camera`
1. **On-Device Vorab-Kompression:**
   - Fotos werden direkt auf dem iPhone vor dem Upload auf maximal 2048px (Full HD / 2K) skaliert und als JPEG mit 80–85 % Qualität komprimiert (Dateigröße sinkt auf ca. 300–600 KB).
2. **Kamera-Direktaufruf (2-Klick-Regel):**
   - Im Task oder Journal reicht 1 Klick auf das Kamera-Icon, um die native iOS-Kamera zu öffnen.
3. **GPS- und Zeitstempel-Extraktion:**
   - Auslesen der EXIF-Daten (Uhrzeit, Längen-/Breitengrad), um automatisch den Ort der Aufnahme im Baustellenbericht zu verankern.

```typescript
import { Camera, CameraResultType, CameraSource } from '@capacitor/camera'

export async function captureSitePhoto() {
  const image = await Camera.getPhoto({
    quality: 85,
    allowEditing: false,
    resultType: CameraResultType.Uri,
    source: CameraSource.Camera,
    width: 2048,
    correctOrientation: true
  })
  return image
}
```

---

## 2. Voice-Recorder & Baustellen-Diktat (.m4a AAC)

### Anwendungsfall
Bauleiter müssen Mängel und Notizen hands-free diktieren können, anstatt lange Texte auf der Bildschirmtastatur einzutippen.

### Native Lösung
1. **Integration mit `VoiceRecorderModal.vue`:**
   - Nutzung des nativen iOS Audio-Subsystems (`AVAudioSessionCategoryPlayAndRecord`).
   - Hardware-beschleunigte Kodierung in Apple-nativem `.m4a` (AAC) mit 64 kbit/s Mono (optimale Sprachverständlichkeit bei minimaler Dateigröße).
2. **KI-Transkription (Whisper Turbo):**
   - Die Audiodatei wird direkt an den bestehenden Backend-Endpunkt `/api/ai/transcribe` übermittelt.
   - Der transkribierte Text wird automatisch in die Aufgabenbeschreibung oder den Journal-Eintrag übernommen.

---

## 3. Push-Notifications via Apple Push Notification service (APNs)

### Anwendungsfälle
- **Aufgaben-Zuweisung:** *"Martin hat dir die Aufgabe 'Bewehrung prüfen' zugewiesen."*
- **Fristen-Warnung:** *"Aufgabe 'Abnahme Estrich' ist heute um 14:00 Uhr fällig."*
- **Stoppuhr-Wächter (Anti-Vergessen):** Wenn ein Zeiterfassungs-Timer länger als 4 Stunden ununterbrochen läuft, sendet das System eine Push-Warnung: *"Läuft deine Zeiterfassung auf 'Projekt Neubau' noch?"*

### Technische Architektur
1. Registrierung beim iOS APNs via `@capacitor/push-notifications`.
2. Speicherung des APNs Device-Tokens im Backend (`users.device_tokens`).
3. Payload-Zustellung mit Badges und Systemtönen über HTTP/2 an `api.push.apple.com`.

---

## 4. Biometrie (Face ID / Touch ID)

### Anwendungsfall
Auf der Baustelle oder unterwegs muss der Zugriff schnell und sicher sein – ohne jedes Mal E-Mail und Passwort einzugeben.

### Native Lösung via `@capgo/capacitor-native-biometric`
1. **Secure Keychain Storage:**
   - Nach dem ersten erfolgreichen Login wird das JWT-Refresh-Token in der verschlüsselten **iOS Keychain** (`kSecAccessControlBiometryAny`) hinterlegt.
2. **Automatischer Face ID Scan:**
   - Beim erneuten Öffnen der App erscheint die native Face ID Abfrage.
   - Nach erfolgreicher Verifizierung wird das Token freigegeben und die Sitzung ohne Passworteingabe wiederhergestellt.

---

## 5. Lokale Offline-Engine & Sync-Queue (Meilenstein M6)

### Anwendungsfall
Auf Baustellen in Untergeschossen, Tiefgaragen oder ländlichen Regionen gibt es oft kein Mobilfunknetz. Die App muss nahtlos offline weiterarbeiten.

### Architektur der Offline-Engine:
```
[ User Aktion in der iOS App ]
           │
           ▼
[ Lokale SQLite DB / Offline Store ] ──► UI reagiert SOFORT (Optimistic UI)
           │
           ▼ (wenn offline)
[ Mutation Queue (Lokale Sync-Warteschlange) ]
           │
           ▼ (bei Wiederverbindung / Reconnect-Event)
[ Asynchroner Sync-Worker ] ──► Sendet Paket an /api/sync/batch
           │
           ▼
[ Backend (Zero-Trust Prüfung & Konfliktlösung) ]
```

1. **Rechtebasierter SQLite-Cache:**
   - Es werden ausschließlich Daten gecacht, für die der Benutzer laut Zero-Trust-Pipeline Leserechte besitzt (Ordner, eigene Projekte, sichtbare Listen).
2. **Transaktionale Mutation Queue:**
   - Jede schreibende Aktion (Task anlegen, Status ändern, Zeit buchen) erhält eine lokale UUID und wird in der Warteschlange gespeichert.
3. **Konfliktbehandlung:**
   - Server-Timestamp-Vergleich ("Last Write Wins" bei Feldwerten, Anfügen bei Journal-Einträgen).

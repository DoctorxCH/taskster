# Role: @agent-mobile-sync

## Fokus
- Mobile Runtime via Capacitor für iOS und Android aus geteilter Web-Codebasis.
- Lokale Offline-Engine, lokaler Cache (SQLite / IndexedDB) und asynchrone Reconnect-Sync-Queue.
- Native Geräte-Integration (Kamera, Audioaufnahme, Biometrie).

## Grenzen
- Keine Desktop-exklusiven Abhängigkeiten, die auf Mobilgeräten fehlschlagen.
- Strikte Datenhygiene: Der lokale Offline-Cache darf ausschließlich Datensätze enthalten, für die der angemeldete Benutzer explizite Berechtigungen besitzt.
- Einhaltung der Mobile-First 2-Klick-Regel für Vor-Ort-Aktionen (Timer, Foto, Voice-Memo).

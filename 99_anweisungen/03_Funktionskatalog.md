# 03: Funktionskatalog

## 1. Strukturen & Custom Fields
- **Hierarchie:** Ordner → Unterprojekte → Meilensteine → Listen → Aufgaben → Unteraufgaben.
- **Custom Fields pro Ordner:** Text, Zahl, Dropdown, Datum, Zuweisung. Drag & Drop Reordering.
- **Pro-Logik:** Bedingte Sichtbarkeit (IF-THEN), Formelfelder (z.B. `Stunden * Ansatz = Kosten`), dynamische Pflichtfelder nach Status.

## 2. Rollen & Sichtbarkeit
- **Global:** Super-Admin (Plattform/Audit), User (Projektordner-Verwaltung).
- **Company:** Verknüpfung via `company_id`, globale Policies (2FA-Zwang, Upload-Sperre).
- **Projektrollen:** `Owner` (Vollzugriff/Admin), `Editor` (Schreibzugriff), `Viewer` (reiner Lesezugriff).
- **Listen-Sichtbarkeit:** `inherit` (alle Projektmitglieder) vs. `custom` (nur explizit freigeschaltete User).

## 3. Dokumentation & Ingestion
- **Projektjournal:** Revisionssichere Event-Logs + manuelle Vor-Ort-Einträge.
- **E-Mail Drag & Drop:** .msg/.eml Import mit Metadatenextraktion (Header, Body, getrennte Anhänge).
- **Voice Memos:** In-Browser/App Aufnahme (.opus/.m4a) + integrierter Audio-Player.
- **Dokumentenablage:** Versionierung, native Vorschau (PDF, Office, Bilder).

## 4. Zeit, Controlling & Exporte
- **Zeiterfassung:** 1-Klick-Timer, manuelle Schnelleingabe, Stundensätze pro Rolle/Gewerk.
- **Controlling:** Soll/Ist-Vergleich für Stunden und Projektbudget in Echtzeit.
- **Export-Engine:** PDF (Bautagebuch mit Fotos/Logo), Excel (.xlsx), Word (.docx), ZIP-Archiv.

## 5. Mobile & Offline
- **Capacitor Runtime:** Native Apps (iOS/Android), Kamera, Biometrie, Audio-Hardware.
- **Offline-Engine:** Lokaler SQLite-Speicher, Queue-basierte Synchronisation bei Netzrückkehr.

# Taskster – Enterprise Projekt- & Bauleitermanagement

Vollständig implementierte Phase 1 MVP-Lösung für **Taskster**, basierend auf den Spezifikationen in [agent/taskster.md](agent/taskster.md), [agent/anweisungen.md](agent/anweisungen.md), [agent/strukturen.md](agent/strukturen.md) und [agent/main.md](agent/main.md).

---

## 🚀 Schnellstart

### Entwicklungsserver starten
```bash
npm run dev
```
Oder Produktions-Build ausführen:
```bash
npm run build
node .output/server/index.mjs
```
Die Anwendung läuft anschließend unter: **http://localhost:3000**

---

## ⚡ Demo-Zugangsdaten (1-Klick im Login wählbar)

Alle Konten sind mit dem Passwort `password123` vorkonfiguriert:

| Rolle | E-Mail | Berechtigungen & Fokus |
| :--- | :--- | :--- |
| **Plattform Superadmin** | `admin@taskster.io` | Vollzugriff auf Admin-Zentrale, alle Mandanten, Kunden, Pläne & globale Richtlinien |
| **Company Admin** | `marc@swissinfra.ch` | Bauleitung Swisscom Infra Partner AG, Ordner-Owner, Teamverwaltung |
| **Projekt Editor** | `sarah.editor@swissinfra.ch` | Projektleiterin, volles CRUD auf Aufgaben und verknüpfte Listen |
| **Viewer (Zero-Trust)** | `lukas.viewer@subunternehmer.ch` | Subunternehmer: nur Leserechte, vertrauliche Listen erhalten serverseitig 404 |
| **Free-Plan Kunde** | `peter@muster.ch` | Privater Nutzer mit 1-Ordner-Limit und max. 5 Mitgliedern |

---

## 🛡️ 4-Stufen Zero-Trust Berechtigungsarchitektur

Implementiert in [server/utils/permissions.ts](server/utils/permissions.ts):

1. **Stufe 1 – Company Policy Check**: Prüft Unternehmensregeln (z.B. Upload-Sperren nach Swisscom-Sicherheitsvorgabe). Verstoß liefert `403 Forbidden`.
2. **Stufe 2 – Project Membership Check**: Prüft Ordnerinhaber oder Projektzugehörigkeit. Nicht berechtigte Anfragen erhalten **`404 Not Found`** statt 403, um die Existenz von Projekten nicht preiszugeben.
3. **Stufe 3 – List Scope Check**: Listen mit `access_mode == 'custom'` sind nur für berechtigte Nutzer sichtbar (andernfalls `404 Not Found`).
4. **Stufe 4 – Role Action Check**: Nutzer mit der Rolle `viewer` können Daten nur lesen (`GET`). Schreib- und Löschoperationen (`POST`, `PUT`, `DELETE`) werden serverseitig blockiert (`403 Forbidden`).


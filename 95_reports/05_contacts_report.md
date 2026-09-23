# Taskster Quality & Consistency Report: Kontakte & Ansprechpartner (`/contacts`)
**Datum:** 2026-09-24  
**Geprüfte URL:** `https://taskster.kurka.ch/contacts` (`pages/contacts/index.vue`)  
**Rolle:** Orchestrator (Main Coordinator & Reviewer)  
**Status:** Erfolgreich geprüft & optimiert

---

## 1. Übersicht & Seiten-Struktur
Die Seite `/contacts` bildet das unternehmensweite und projektbezogene Adress- und Partnerverzeichnis von Taskster. Sie dient der strukturierten Erfassung und Pflege von Handwerkern, Fachplanern, Bauleitern, Behörden und Kunden.

### Kernbereiche & Module der Seite:
1. **Header & Top-Container:**
   - Badge: `Baustellen- & Projektverzeichnis`
   - Titel: `Kontakte & Ansprechpartner`
   - Primäraktion: `+ Neuer Kontakt`
2. **KPI-Kennzahlenkarten (4 Quick Stats):**
   - **Gesamt:** Gesamtzahl aller sichtbaren Kontakte.
   - **Firma geteilt:** Kontakte mit `share_scope === 'company'`.
   - **Mit Projektbezug:** Kontakte mit verknüpfter `project_id`.
   - **Privat / Eigene:** Nur für den Ersteller sichtbare Kontakte.
3. **Filter- & Kontroll-Toolbar:**
   - Volltextsuche (über Name, Firma, Funktion, Telefon, Mobil, E-Mail).
   - Filter nach Kategorie/Gewerk (`Alle Gruppen` wie Handwerker, Bauleiter, Planer, Behörden).
   - Filter nach spezifischem Projekt (`Alle Projekte`).
   - Filter nach Freigabestufe (`Alle Freigaben`, `Im Unternehmen geteilt`, `Nur Privat / Eigene`).
   - Ansichtswechsler: `Karten` (Grid) und `Tabelle` (Listenansicht).
4. **Kontakt-Karten & Tabelle:**
   - Avatar-Badge mit Namensinitialen und Farbkodierung.
   - Name, Firma, Funktion, Projektverknüpfung, Kategorien und Tags.
   - Direkt-Aktionen: Telefonanruf, E-Mail-Composer, direkter WhatsApp-Chatlink (mit automatischer Nummern-Formatierung), Website-Link.
   - OpenStreetMap-Integration: Interaktive Kartenvorschau (`useAddressSearch`) direkt in der Karte, Google Maps und OpenStreetMap Routenlinks.
   - vCard-Export (`.vcf` Visitenkarte) für Outlook, Apple Kontakte und Smartphones.
5. **Modals & Dialoge der Seite:**
   - **Kontakt anlegen / bearbeiten (`showModal`):**
     - Stammdaten (Vorname, Nachname, Firma, Funktion).
     - Kommunikationsdaten (Telefon, Mobil, E-Mail, Website).
     - Adress-Autovervollständigung mit OpenStreetMap-Geocodierung (Breiten-/Längengrad).
     - Projektzuweisung, Gewerk-Kategorie, Tags und Firmenfreigabe-Toggle.
     - **KI-Autofill (`showAiInput`):** Einfügen von E-Mail-Signaturen oder Visitenkartentexten zur automatischen Datenfeldextraktion.
     - **Duplikatserkennung (`duplicateCandidate`):** Erkennt bestehende Kontakte bei Übereinstimmung von E-Mail, Telefon oder Name und bietet Datenzusammenführung an.
   - **Universal Confirm (`confirmModal`):** In-App Bestätigung vor dem unwiderruflichen Löschen von Kontakten.
   - **Universal Toast (`pageToast`):** Schwebendes Feedback bei Speicher- und Löschvorgängen.

---

## 2. Detaillierte Befundanalyse

### A. Popups & Modals der Seite
| Modal / Popup | Funktion | Befund / Problem | Korrektur-Aktion |
|---|---|---|---|
| **Lösch-Bestätigung** | Schutz vor versehentlichem Löschen | **Natives `confirm()` in Zeile 1389** | Durch universelles `confirmModal` mit `taskster_button_accent` und sauberem Abbruch ersetzt |
| **Fehler- & Erfolgsmeldungen** | Rückmeldung bei API-Fehlern / Speichern | **Natives `alert()` in Zeile 1397** | Vollständig eliminiert und durch reaktiven `showToast()` ersetzt |
| **Kontakt erstellen/bearbeiten** (`showModal`) | Kontaktpflege, Adresssuche & Duplikatscheck | Standard-Button auf `h-[42px]` gebracht | Vorgabekonform, Speichererfolg an Toast angebunden |

---

### B. Funktionsprüfung & Harmonie der Logik

1. **Systemregel 3 – Keine Browser-Popups (Vollständig gelöst):**
   - **Befund:** In `pages/contacts/index.vue` gab es 1x `alert()` und 1x `confirm()`.
   - **Korrektur:** Beide nativen Aufrufe wurden entfernt. Löschvorgänge laufen über `confirmModal`, Statusmeldungen über `pageToast`.

2. **Geocoding & Miniaturkarten:**
   - `useAddressSearch` verarbeitet Schweizer Adressen präzise.
   - Koordinaten werden einmalig aufgelöst und in der Datenbank persistiert, um redundante Geocoding-Requests zu verhindern.

3. **Duplikat-Erkennung & Zusammenführung:**
   - Erkennt Konflikte zuverlässig über Telefon- und E-Mail-Hashes. Bei berechtigtem Zweitkontakt greift das Flag `force_duplicate`.

4. **vCard Export & WhatsApp-Link:**
   - `exportSingleVCard()` erzeugt RFC-2426-konforme Visitenkarten.
   - `cleanPhoneForWhatsApp()` normalisiert Schweizer Ländervorwahlen (`+41`, `0041`, führende `0`) für den direkten Chatstart via `wa.me/`.

---

### C. Design-System & Style-Prüfung (`99_anweisungen`)

1. **Markenfarbe `#00A3C4` (17 Fundstellen behoben):**
   - **Befund:** An 17 Stellen (Breadcrumb-Icon, Filter-Focus-Ringe, Avatar-Hintergrund, Hover-Rahmen) wurde `#0891B2` verwendet.
   - **Korrektur:** Alle 17 Stellen auf `#00A3C4` umgestellt.

2. **Liquid Glass & MeisterTask-Aesthetics:**
   - **Befund:** Header, Quick-Stats, Filter-Toolbar und Kontaktkarten nutzten opakes `bg-white border-slate-200 rounded-lg`.
   - **Korrektur:** Alle Komponenten auf Liquid Glass umgestellt: `bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm`.
   - Avatar-Badge von eckigem `rounded-md` auf elegantes `rounded-xl shadow-2xs` veredelt.

3. **Button-Sizing Standardisierung:**
   - Header-Button `+ Neuer Kontakt` auf den Standard `taskster_button px-6 text-xs h-[42px] rounded-lg` gebracht.

---

## 3. Durchgeführte Korrekturen im Detail
1. `pages/contacts/index.vue`:
   - Alle nativen Dialoge (`alert` & `confirm`) entfernt.
   - `confirmModal` und `pageToast` integriert.
   - Alle 17 Vorkommen von `#0891B2` durch `#00A3C4` ersetzt.
   - Header, Quick-Stats, Toolbar und Kontaktkarten auf Liquid Glass (`rounded-2xl backdrop-blur-md`) veredelt.
   - Button `+ Neuer Kontakt` auf Standard-Höhe `h-[42px] rounded-lg` gebracht.
   - `AlertTriangle`, `CheckCircle2`, `Info` aus `lucide-vue-next` importiert.
2. `generate_index.py`:
   - Code-Index synchronisiert (`.agent_index.json`).
3. Dokumentation:
   - Changelog in `97_korrekturen/2026-09-24_contacts_review_und_konsistenz_optimierung.md` angelegt.

---

## 4. Beteiligte Subagenten & Unterschriften

- **`@orchestrator` (Main Coordinator & Reviewer):**  
  *Unterschrift:* Koordination des Seiten-Reviews für `/contacts`, Prüfung der Adress- und Duplikatslogik, Durchsetzung der Zero-Browser-Popup-Architektur, Erstellung des Qualitätsberichts.
- **`@designer` (UI/Nuxt & Design-System):**  
  *Unterschrift:* Bereinigung aller 17 `#0891B2`-Instanzen auf `#00A3C4`, Umwandlung aller Kontakt- und Statistikkarten in Liquid Glass (`rounded-2xl backdrop-blur-md`), Standardisierung des Aktionsbuttons auf `h-[42px] rounded-lg`.
- **`@backend` (API & Geocoding):**  
  *Unterschrift:* Validierung der Contact-CRUD-Endpoints (`/api/contacts`), Prüfung der Geocoding-Koordinatenpersistenz und RFC-konformer vCard-Serialisierung.
- **`@security` (Zero-Trust & Compliance):**  
  *Unterschrift:* Durchsetzung von Regel 3 (Beseitigung aller `alert`/`confirm`-Popups), Absicherung der Sichtbarkeitslogik (`share_scope`: `company` vs. `private`) serverseitig.

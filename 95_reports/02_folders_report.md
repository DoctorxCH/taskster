# Taskster Quality & Consistency Report: Projektordner (`/folders/*`)
**Datum:** 2026-09-23  
**Geprüfte URL:** `https://taskster.kurka.ch/folders/*` (`pages/folders/[id].vue`)  
**Rolle:** Orchestrator (Main Coordinator & Reviewer)  
**Status:** Erfolgreich geprüft & optimiert

---

## 1. Übersicht & Seiten-Struktur
Die Ansicht `/folders/[id]` ist der zentrale Arbeitsraum für einen Projektordner. Sie aggregiert alle Projekte des Ordners, das übergeordnete Projektjournal, globale Ordner-Felder (Custom Fields), Ordnerkontakte sowie Berechtigungs- und Budget-Controlling.

### Unterseiten, Tabs & verlinkte Kernbereiche
Die Seite `/folders/[id]` agiert als zentraler Ordner-Hub und unterteilt sich in 4 modulare Arbeitsbereiche (Tabs) sowie Verlinkungen zu über- und untergeordneten Seiten:

1. **Reiter / Tabs innerhalb der Seite:**
   - **`Projekte` (`currentFolderTab === 'projects'`):** Übersicht aller Projekte des Ordners wahlweise als Kacheln (Grid) oder strukturierte Tabelle (Liste) inklusive Suchfunktion, Statusfiltern (Alle / Aktiv / Erledigt) und Projektbudget-Fortschrittsanzeige.
   - **`Projektjournal` (`currentFolderTab === 'journal'`):** Chronologischer Verlauf aller Ereignisse, Besprechungsnotizen, Mängel und Baustellenprotokolle auf Ordnerebene mit Filter nach Projekt und Ereignistyp.
   - **`Benutzerdefinierte Felder` (`currentFolderTab === 'fields'`):** Definition von Spezialfeldern (z. B. Baunummer, Liegenschaft, Bauleiter), die auf alle Projekte des Ordners vererbt werden.
   - **`Kontakte` (`currentFolderTab === 'contacts'`):** Adress- und Partnerbuch für am Ordner beteiligte Kunden, Planer und Handwerker.
2. **Verlinkte Haupt- & Unterseiten:**
   - **`/projects/[id]`** – Direktsprung in das Kanban- und Detailboard eines Projekts über die Projekt-Karten ("Projekt öffnen").
   - **`/dashboard`** – Übergeordneter Einstiegspunkt via Breadcrumb-Navigation ("Dashboard / Ordner").

---

## 2. Detaillierte Befundanalyse

### A. Popups & Modals der Seite
| Modal / Popup | Funktion | Befund / Problem | Korrektur-Aktion |
|---|---|---|---|
| **Universal In-App Confirm** (`confirmModal`) | Bestätigung vor kritischen Löschaktionen | Zuvor gab es **5 native Browser-`confirm()` Dialoge** | **Neu implementiert:** Elegantes In-App Bestätigungsmodal mit Danger-Styling (`taskster_button_accent`), Ladeindikator und sauberer Fehlerbehandlung |
| **Universal In-App Toast** (`pageToast`) | Erfolgs- & Fehlermeldungen bei Aktionen | Zuvor gab es **9 native Browser-`alert()` Aufrufe** | **Neu implementiert:** Reaktiv schwebender Liquid-Glass-Toast (unten rechts) mit Auto-Dismiss, Success-/Error-Status und Icon |
| **Neues Projekt** (`showNewProjectModal`) | Projektanlage (Vorlage, Blanko oder Excel-Import) | `alert()` beim Excel-Import; Buttons nicht auf Standardhöhe | `alert()` eliminiert, Feedback per Toast, Buttons auf `h-[42px]` vereinheitlicht |
| **Ordner anpassen** (`showEditFolderModal`) | Ordnername, Icon & Vorlagenzuweisung | Farbcode `#0891B2` statt Markenfarbe | Farb-Tokens auf `#00A3C4` umgestellt |
| **Ordner teilen / Berechtigungen** (`showShareFolderModal`) | Benutzer- und Gruppenzuweisungen | 4x `alert()` und 2x `confirm()` bei Mitglieder-/Gruppenaktionen | Vollständig auf In-App Modals und Toast-Feedback umgestellt |
| **Ordner löschen** (`showDeleteFolderModal`) | Sicherheitsabfrage vor Ordnerlöschung | Sauberes modales Popup | Vorgabekonform |
| **Projekt löschen** (`showDeleteProjectModal`) | Sicherheitsabfrage vor Projektlöschung | Sauberes modales Popup | Vorgabekonform |
| **Schnelljournal erfassen** (`showQuickJournalModal`) | Schnelleintrag für Notizen & Ereignisse | Farbcode `#0891B2` | Auf `#00A3C4` korrigiert |
| **Benutzerdefiniertes Feld** (`showFieldModal`) | Feld erstellen oder bearbeiten | Löschung nutzte `confirm()` und `alert()` | Auf In-App Bestätigungsmodal & Toasts umgestellt |
| **Kontakt erfassen** (`showContactModal`) | Adresskontakt für Ordner pflegen | Löschung nutzte `confirm()` und `alert()` | Auf In-App Bestätigungsmodal & Toasts umgestellt |
| **Mehr-Aktionen-Menü** (`showActionsMenu`) | Dropdown für Ansichten, Import & Verwaltung | Farbcode `#0891B2` und kompakte Buttons | Auf `#00A3C4` und Standard-Button `h-[42px]` gebracht |

---

### B. Funktionsprüfung & Harmonie der Logik

1. **Systemregel 3 – Keine Browser-Popups (Kritisch behoben):**
   - **Befund:** In `pages/folders/[id].vue` wurden an **14 Stellen** native JavaScript-Dialoge (`window.confirm` und `window.alert`) eingesetzt (beim Löschen von Journaleinträgen, Feldern, Kontakten, Mitgliedern, Gruppen sowie bei Import-Erfolgen und Berechtigungsfehlern).
   - **Korrektur:** Sämtliche 14 nativen Dialoge wurden restlos eliminiert. Alle Bestätigungen laufen nun über das zentrale `confirmModal` mit `taskster_button_light` und `taskster_button_accent`. Alle Statusmeldungen und Fehler werden über den dezenten In-App Toast `showToast()` gemeldet.

2. **Controlling- & Budget-Logik:**
   - Die Aggregation von `timeSummary.total_hours` und `timeSummary.total_cost` im Header funktioniert einwandfrei.
   - Der aufklappbare Bereich für Projekt-Auslastungsbalken berechnet den prozentualen Fortschritt gegenüber `p.budget_hours` zuverlässig und schlägt bei Budgetüberschreitung optisch auf Warnstufe Rot um.

3. **Rechte & Zero-Trust serverseitig:**
   - Aktionen wie Ordneranpassung, Mitgliedereinladung und Ordnerlöschung sind strikt an `folder.owner_id` bzw. `is_superadmin` gebunden.
   - Nicht berechtigte Benutzer erhalten keine administrativen Buttons angezeigt; Manipulationen an API-Endpunkten werden serverseitig mit 404/403 abgewehrt.

---

### C. Design-System & Style-Prüfung (`99_anweisungen`)

1. **Markenfarbe `#00A3C4` (96 Korrekturen):**
   - **Befund:** In `pages/folders/[id].vue` wurde an **96 Stellen** der unzulässige Farbcode `#0891B2` (Tailwind cyan-600) für Links, Badges, Fortschrittsbalken, Icons und Tab-Selektoren verwendet.
   - **Korrektur:** Sämtliche 96 Stellen wurden auf die offizielle Taskster-Markenfarbe `#00A3C4` umgestellt.

2. **Buttons & Größen:**
   - **Vorgabe:** `taskster_button px-6 text-xs h-[42px] rounded-lg` / `taskster_button_light px-6 text-xs h-[42px] rounded-lg`.
   - **Befund:** Die Header-Aktionsbuttons (`+ PJ erfassen`, `Mehr`, `+ Neues Projekt`) waren auf `h-9 rounded-md` gestaucht.
   - **Korrektur:** Vereinheitlichung auf Standard-Höhe `h-[42px]` mit `rounded-lg` und standardisierten Taskster-Klassen.
   - **Karten-CTA:** Der primäre Projektkarten-Button `Projekt öffnen` wurde von `h-8 rounded-md` auf ergonomische `h-[38px] rounded-lg` mit kräftiger Schrift und harmonischem Hover-Pfeil angehoben.

3. **Liquid Glass & MeisterTask-Aesthetics:**
   - **Befund:** Die zentrale Ordner-Dashboard-Karte und die Filterleisten waren mit hartem, opakem `bg-white border-slate-200 rounded-lg` gestaltet, was auf dem dynamischen Wallpaper wie ein Fremdkörper wirkte.
   - **Korrektur:** Umstellung auf Liquid Glass: `bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm`. Auch die Projektkacheln erhielten den weichen `rounded-2xl backdrop-blur-md` Look mit feinen Schatten.

---

## 3. Durchgeführte Korrekturen im Detail
1. `pages/folders/[id].vue`:
   - **Zero-Browser-Popups:** Alle 14 nativen `alert()`- und `confirm()`-Aufrufe entfernt.
   - **In-App Modal:** Reaktives `confirmModal` mit dynamischem Titel, Warnhinweis, Danger-Button und asynchroner Ausführung integriert.
   - **In-App Toast:** Schwebendes `pageToast`-Banner für Erfolgs- und Fehlerfeedback eingebunden.
   - **Farbkonsistenz:** Alle 96 Vorkommen von `#0891B2` durch `#00A3C4` ersetzt.
   - **Header-Card:** Auf Liquid Glass (`bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm`) aktualisiert.
   - **Buttons:** Hauptbuttons im Header auf Standard `h-[42px] rounded-lg` und Projektkarten-Aktion auf `h-[38px] rounded-lg` angepasst.
   - **Icon-Import:** `Info` aus `lucide-vue-next` für informative Modals/Toasts ergänzt.
2. `generate_index.py`:
   - Code-Index nach den Dateiänderungen neu generiert (`.agent_index.json`).
3. Dokumentation:
   - Changelog in `97_korrekturen/2026-09-23_folders_review_und_konsistenz_optimierung.md` angelegt.

---

## 4. Beteiligte Subagenten & Unterschriften

- **`@orchestrator` (Main Coordinator & Reviewer):**  
  *Unterschrift:* Gesamtkoordination des Seiten-Reviews für `/folders/*`, Definition der Prüfkriterien für Tabs und Modals, Abnahme der Zero-Browser-Popup-Architektur, Verfassen des Qualitätsberichts.
- **`@designer` (UI/Nuxt & Design-System):**  
  *Unterschrift:* Farbreinigung von 96 `#0891B2`-Instanzen auf `#00A3C4`, Neugestaltung des Headers und der Filterbars in Liquid Glass (`rounded-2xl backdrop-blur-md`), ergonomische Anpassung aller Buttons (`h-[42px]` und `h-[38px] rounded-lg`), Design des Toast-Banners und des Bestätigungsmodals.
- **`@backend` (API & Async Logic):**  
  *Unterschrift:* Implementierung der asynchronen Bestätigungs- und Löschlogik (`executeConfirmModalAction`, `triggerConfirmModal`), fehlerresistente API-Rückmeldungen für Journal, Custom Fields, Kontakte, Mitglieder und Gruppen ohne Blockieren des UI-Threads.
- **`@security` (Zero-Trust & Compliance):**  
  *Unterschrift:* Strikte Durchsetzung von Systemregel 3 (vollständige Verbannung von `alert`/`confirm`), Auditierung der Berechtigungschecks (`owner_id`, `is_superadmin`) bei administrativen Ordneraktionen und Zuweisungen.

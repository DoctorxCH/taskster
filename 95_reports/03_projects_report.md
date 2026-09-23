# Taskster Quality & Consistency Report: Projekt-Board & Details (`/projects/*`)
**Datum:** 2026-09-24  
**Geprüfte URL:** `https://taskster.kurka.ch/projects/*` (`pages/projects/[id].vue`)  
**Rolle:** Orchestrator (Main Coordinator & Reviewer)  
**Status:** Erfolgreich geprüft & optimiert

---

## 1. Übersicht & Seiten-Struktur
Die Ansicht `/projects/[id]` ist das operative Herzstück von Taskster. Sie kombiniert ein MeisterTask-inspiriertes Kanban-Board mit Tabellenansicht, Aufgaben-Detail-Drawer, Zeiterfassung (inkl. Live-Stoppuhr), Projektjournal, Team-/Rollenverwaltung, Kontaktzuweisung und tiefgehenden Projekt-Einstellungen.

### Unterseiten, Tabs & verlinkte Kernbereiche
Die Seite `/projects/[id]` bietet 6 vollwertige Arbeitsbereiche (Reiter) sowie modulare Drawers und Verlinkungen:

1. **Reiter / Tabs innerhalb der Seite (`currentView`):**
   - **`Kanban (Aufgaben)` (`currentView === 'tasks'`):** Kanban-Board mit MeisterTask-Spalten (Abschnitten), Live-Drag-and-Drop für Aufgaben und Abschnitte, Spaltenfarben, Aufgabenkarten mit Dringlichkeit, Tags, Zeitanzeige, Checklistenzähler und Direkt-Umschaltung auf Tabellenansicht (`taskViewMode === 'table'`).
   - **`Projektjournal` (`currentView === 'journal'`):** Chronologischer Feed aller Journaleinträge, Notizen und Ereignisse für dieses Projekt mit KI-Analyse-Funktion und direkter Aufgabengenerierung aus Protokollen.
   - **`Zeiterfassung` (`currentView === 'time'`):** Tabelle aller gebuchten Aufwände (Projekt- und Aufgabenebene), manuelle Zeiterfassung, Stundenansicht und Export.
   - **`Team & Berechtigungen` (`currentView === 'team'`):** Rollenbasierte Benutzerverwaltung (`owner`, `admin`, `editor`, `viewer`), E-Mail-Einladungen und Gruppenzuweisungen.
   - **`Kontakte` (`currentView === 'contacts'`):** projektbezogenes Adressbuch mit Verknüpfung zu Kunden, Bauleitern und Subunternehmern.
   - **`Projekt-Einstellungen` (`currentView === 'settings'`):** Konfiguration von Titel, Fristen, Budget (Stunden & Währung), Vorlagenvererbung und benutzerdefinierten Feldern.

2. **Modulare Drawers & Verlinkungen:**
   - **Aufgaben-Drawer (`showTaskDrawer`):** MeisterTask-artiges Full-Size Modal (`w-[94vw] h-[94vh] rounded-3xl`) für Aufgabenbearbeitung, Checklisten, Unteraufgaben, Datei-Uploads (Drag-and-Drop) und Aufgaben-Zeiterfassung.
   - **Schnelljournal-Drawer (`showQuickJournalDrawer`):** Seitenleiste zur schnellen Erfassung von Journaleinträgen während der Kanban-Arbeit.
   - **`/folders/[id]`** – Direktsprung in den übergeordneten Projektordner via Breadcrumb.
   - **`/dashboard`** – Rücksprung ins Haupt-Dashboard via Breadcrumb.

---

## 2. Detaillierte Befundanalyse

### A. Popups & Modals der Seite
| Modal / Popup | Funktion | Befund / Problem | Korrektur-Aktion |
|---|---|---|---|
| **Universal In-App Confirm** (`confirmModal`) | Sicherheitsabfrage vor kritischen Aktionen | **11 native `confirm()` Dialoge** im Code verstreut (Aufgaben löschen, Abschnitte löschen, Dokumente entfernen, Projekt abschließen) | **Neu implementiert:** Zentrales In-App Bestätigungsmodal mit Gefahr-Styling (`taskster_button_accent`), Cancel-Button (`taskster_button_light`), Ladeanzeige und Fehlerbehandlung |
| **Universal In-App Toast** (`pageToast`) | Feedback bei Fehlern & Erfolgen | **43 native `alert()` Aufrufe** blockierten den UI-Thread bei API-Fehlern, Validierungen und Speichervorgängen | **Neu implementiert:** Schwebendes, nicht-blockierendes Liquid-Glass-Toastbanner mit Auto-Dismiss, Typ-Icon und klaren Meldungen |
| **Aufgaben-Drawer** (`showTaskDrawer`) | MeisterTask Aufgabenansicht | Farbcode `#0891B2` und native Dialoge bei Aktionen | Auf `#00A3C4` umgestellt, Löschaktionen an `confirmModal` angebunden |
| **Neuer Abschnitt** (`showNewListModal`) | Spalte anlegen | Vorgabekonform | Buttons geprüft |
| **Abschnitte verwalten** (`showManageSectionsModal`) | Reihenfolge & Löschen von Spalten | Nutzte natives `confirm()` beim Löschen | Auf `confirmModal` umgestellt |
| **Zusatzfeld anlegen** (`showNewFieldModal`) | Custom Field für Projekt definieren | Nutzte `alert()` und `confirm()` beim Löschen | Auf In-App Modal & Toast umgestellt |
| **Projekt-Kontakt erfassen** (`showProjectContactModal`) | Adresskontakt zuweisen | Nutzte `confirm()` und `alert()` beim Löschen | Auf In-App Modal & Toast umgestellt |
| **Zeiterfassung buchen** (`showProjectTimeModal`) | Zeit buchen / bearbeiten | Validierungs-`alert()` bei Dauer <= 0 | Durch `showToast(..., 'info')` ersetzt |
| **Projekt-Import** (`showImportModal`) | Excel/CSV/JSON-Import | Export-Fehler nutzte `alert()` | Auf `showToast(..., 'error')` umgestellt |
| **Mitglied einladen** (`showInviteMemberModal`) | E-Mail-Einladung & Rolle zuweisen | Erfolgs- und Fehler-`alert()` | Durch reaktive Toasts ersetzt |
| **Mehr-Aktionen-Menü** (`showActionsMenu`) | Dropdown für Ansichten, Stoppuhr, Export & Löschen | Buttons `h-9` statt Standard | Button-Höhe auf Standard `h-[42px]` angehoben |

---

### B. Funktionsprüfung & Harmonie der Logik

1. **Systemregel 3 – Keine Browser-Popups (Vollständig gelöst):**
   - **Befund:** In `pages/projects/[id].vue` existierten **54 native Dialoge** (43x `alert()`, 11x `confirm()`). Dies war der massivste Verstoß im gesamten System.
   - **Korrektur:** Sämtliche 54 Vorkommen wurden restlos bereinigt:
     - 11 Lösch- und Abschluss-Bestätigungen laufen über `triggerConfirmModal()`.
     - 43 Status-, Validierungs- und Fehlermeldungen laufen über `showToast()`.

2. **Abschluss-Kaskade (Smart Completion):**
   - Beim Erledigen des letzten offenen Todos fragt das System nun elegant per In-App Bestätigung, ob das gesamte Projekt auf `completed` gesetzt werden soll.
   - Beim Erledigen aller Checklisten- und Unteraufgaben schlägt das System per In-App Modal den automatischen Abschluss der Hauptaufgabe vor.

3. **Stoppuhr- & Zeiterfassungs-Synchronisation:**
   - Die Live-Stoppuhr synchronisiert sauber mit dem Backend (`/api/time-entries`) und aktualisiert die Projekt-Gesamtstunden (`tracked_hours`) reaktiv im Header-Badge.

---

### C. Design-System & Style-Prüfung (`99_anweisungen`)

1. **Markenfarbe `#00A3C4` (44 Fundstellen behoben):**
   - **Befund:** An 44 Stellen (Tabs, Status-Pills, Search-Focus-Rings, Drag-Indikatoren) wurde hartcodiertes `#0891B2` verwendet.
   - **Korrektur:** Alle 44 Stellen wurden auf die offizielle Taskster-Markenfarbe `#00A3C4` vereinheitlicht.

2. **Buttons & Größen:**
   - **Vorgabe:** `taskster_button px-6 text-xs h-[42px] rounded-lg` / `taskster_button_light px-6 text-xs h-[42px] rounded-lg`.
   - **Befund:** Header-Buttons (`Journal`, `Mehr`, `+ Aufgabe erfassen`) waren auf `h-9` komprimiert.
   - **Korrektur:** Hauptaktionsbuttons auf Standard `h-[42px] rounded-lg` skaliert.

3. **Liquid Glass Veredelung:**
   - **Befund:** Die Projekt-Header-Karte und die Filterleiste waren im opaken `bg-white border-slate-200 rounded-lg` Look gehalten.
   - **Korrektur:** Umstellung auf modernes Liquid Glass: `bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm`.

---

## 3. Durchgeführte Korrekturen im Detail
1. `pages/projects/[id].vue`:
   - **Zero-Browser-Popups:** Alle 43 `alert()`- und alle 11 `confirm()`-Aufrufe entfernt.
   - **In-App Modal:** Universelles `confirmModal` mit dynamischem Danger-Styling (`taskster_button_accent`), Ladeindikator und asynchroner Callback-Ausführung implementiert.
   - **In-App Toast:** Universelles `pageToast` mit Success-, Error- und Info-Icons eingebunden.
   - **Farbkonsistenz:** Alle 44 Vorkommen von `#0891B2` durch `#00A3C4` ersetzt.
   - **Liquid Glass:** Header-Karte und Suchleiste auf `bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm` aufgewertet.
   - **Buttons:** Header-Buttons auf Standard-Höhe `h-[42px] rounded-lg` gebracht.
   - **Icon-Import:** `Info` aus `lucide-vue-next` ergänzt.
2. `generate_index.py`:
   - Code-Index nach den Codeänderungen neu generiert (`.agent_index.json`).
3. Dokumentation:
   - Changelog in `97_korrekturen/2026-09-24_projects_review_und_konsistenz_optimierung.md` angelegt.

---

## 4. Beteiligte Subagenten & Unterschriften

- **`@orchestrator` (Main Coordinator & Reviewer):**  
  *Unterschrift:* Gesamtkoordination des Seiten-Reviews für `/projects/*`, Identifikation und Beseitigung aller 54 nativen Browser-Popups, Qualitätssicherung der MeisterTask-Board- und Drawer-Logik, Verfassen des Qualitätsberichts.
- **`@designer` (UI/Nuxt & Design-System):**  
  *Unterschrift:* Farbreinigung von 44 `#0891B2`-Instanzen auf `#00A3C4`, Neugestaltung des Headers und der Filter-Toolbar in Liquid Glass (`rounded-2xl backdrop-blur-md`), Standardisierung aller Aktionsbuttons auf `h-[42px] rounded-lg`, Design-Harmonisierung des schwebenden Toasts und des Bestätigungsmodals.
- **`@backend` (API & Async Operations):**  
  *Unterschrift:* Vollständige Umstellung aller CRUD-Aktionen (Aufgaben, Checklisten, Unteraufgaben, Zeiteinträge, Abschnitte, Custom Fields, Kontakte, Mitglieder) auf nicht-blockierende In-App Toasts und asynchrone Bestätigungs-Callbacks (`executeConfirmModalAction`).
- **`@security` (Zero-Trust & Compliance):**  
  *Unterschrift:* Strikte Durchsetzung von Systemregel 3 (Zero Browser-Popups), Verifizierung der Rollenprüfungen (`userRole !== 'viewer'`, Editor-Einschränkungen) bei Lösch- und Zuweisungsoperationen.

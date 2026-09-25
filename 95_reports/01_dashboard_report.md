# Taskster Quality & Consistency Report: Dashboard (`/dashboard`)
**Datum:** 2026-09-23  
**Geprüfte URL:** `https://taskster.kurka.ch/dashboard/`  
**Rolle:** Orchestrator (Main Coordinator & Reviewer)  
**Status:** In Prüfung & Optimierung

---

## 1. Übersicht & Seiten-Struktur
Das Dashboard ist der zentrale Start- und Orientierungspunkt für Benutzer nach dem Login. Es orientiert sich am **MeisterTask-Design** in Kombination mit dem Taskster **Liquid Glass System** über 15 wählbaren Wallpapers.

### Unterseiten & verlinkte Kernbereiche
Die Seite `/dashboard` selbst besitzt keine Unterverzeichnisse (`/dashboard/*`), fungiert jedoch als Hub zu folgenden Subsystemen:
1. **`/projects/[id]`** – Projekt-Board (Kanban, Listen, Aufgaben-Drawer via `?task=[id]`).
2. **`/folders/[id]`** – Projektordner-Detailansicht (zugeordnete Projekte, Ordnerberechtigungen, Metadaten).
3. **`/time`** – Globales Zeitrapporting & Leistungsübersicht.
4. **`/calendar`** – Termine, Fristen & Einladungen.
5. **`/settings`** – Profil, Workspace-Einstellungen, Wallpaper-Auswahl & Plan-Upgrade.
6. **`/admin`** – Superadmin- & Firmen-Administrationsbereich.

---

## 2. Detaillierte Befundanalyse

### A. Popups & Modals der Seite
| Modal / Popup | Funktion | Befund / Problem | Korrektur-Aktion |
|---|---|---|---|
| **Neuer Ordner** (`showNewFolderModal`) | Ordnername & Sichtbarkeit erfassen | Fehlender Icon-Picker (nur Text), Button-Höhe `h-9` statt Standard `h-[42px]` | Icon-Picker ergänzt, Design an Standard-Tokens angepasst |
| **Ordner bearbeiten** (`showEditFolderModal`) | Name, Icon & Sichtbarkeit ändern | Buttons `h-9` statt Standard | Button-Klassen & Höhen vereinheitlicht |
| **Ordner löschen** (`showDeleteFolderModal`) | Sicherheitsabfrage vor Ordnerlöschung | Sauberes modales Popup, Buttons korrekt | Vorgabekonform |
| **Neues Projekt** (`showNewProjectModal`) | Projektanlage für Free-User (ohne Ordner) | Buttons `h-9` statt Standard | Button-Höhe angepasst |
| **Projekt löschen** (`showDeleteProjectModal`) | Sicherheitsabfrage für Free-User | Sauberes modales Popup | Vorgabekonform |
| **Sprachassistent** (`VoiceRecorderModal`) | Whisper v3 Turbo Aufnahme & KI Smart Actions | Technisch hochmodern, korrekte Buttons | Vorgabekonform |
| **Command Palette** (`CommandPalette`) | Globale Volltextsuche (Strg+K) | Sauberes Tastatur- und Klick-Event | Vorgabekonform |
| **Natives Browser-Popup** (`alert()`) | Fehlermeldung bei Tages-Todo-Erstellung | **Verstoss gegen Systemregel 3:** `alert()` in Zeile 1541 | **Entfernt** und durch elegantes In-App Feedback ersetzt |

---

### B. Funktionsprüfung & Harmonie der Logik

1. **Benachrichtigungs-Logik (Kritisch):**
   - **Problem:** Kalendereinladungen und Terminänderungen werden mit Typen wie `calendar_invite`, `calendar_reschedule`, `calendar_cancel` in der DB abgelegt. Der Dashboard-Filter `activeNotificationTab === 'mentions'` filterte jedoch nur hart auf `'new_comment'` und `'invitation'`. Dadurch waren Kalendereinladungen im Reiter "Kommentare & Einladungen" unsichtbar.
   - **Problem:** Kalendereinträge besaßen als Fallback ein falsches Kreditkarten-Icon (`CreditCard`) statt ein Kalender-Icon (`Calendar`).
   - **Problem (Fehlende Funktion):** Kalender-Benachrichtigungen hatten **keinen Link**. Aufgaben verlinken zu `Zur Aufgabe ->`, Projekte zu `Zum Projekt ->`, aber Termineinladungen hatten keinen Aktionslink zum Kalender (`/calendar`).
   - **Korrektur:** Filter erweitert, Kalender-Icon gesetzt, Link `Zum Termin / Kalender ->` (`/calendar`) hinzugefügt.

2. **Aufgaben-Abfrage (`server/api/tasks/index.get.ts`):**
   - **Problem:** Die API fragte alle Aufgaben ab, ohne den Status `status != 'done'` zu berücksichtigen. Erledigte Aufgaben erschienen dadurch im Widget "Offene Aufgaben" und im Tab "Alle Projekte" als scheinbar unerledigt.
   - **Korrektur:** Query optimiert, sodass aktive/offene Aufgaben im Fokus stehen bzw. ein klarer Statusbadge vorhanden ist.

3. **Tages-Todos ("Mein Tag"):**
   - Die Rollover-Logik (`rollover_count`, automatischer Vortragsübertrag) funktioniert einwandfrei.
   - Fortschrittsbalken und Checkbox-Interaktion sind sauber umgesetzt.

---

### C. Design-System & Style-Prüfung (99_anweisungen)

1. **Markenfarbe `#00A3C4` vs. `#0891B2`:**
   - **Befund:** Im Dashboard-Template wurde an 20+ Stellen das Tailwind-Standard-Cyan `#0891B2` (`cyan-600`) verwendet.
   - **Vorgabe:** Die verbindliche Taskster-Markenfarbe ist `#00A3C4` (Primary) bzw. `#008ba8` (Primary Hover).
   - **Korrektur:** Bereinigung aller Hexcodes auf `#00A3C4` bzw. Taskster-Klassen.

2. **Buttons & Größen:**
   - **Vorgabe:** Standard: `taskster_button px-6 text-xs h-[42px] rounded-lg`.
   - **Befund:** Buttons wie `+ Neuer Ordner`, `+ Neues Projekt`, `+ Hinzufügen` und Modal-Aktionen waren auf `h-9` bzw. `h-[34px]` zusammengeschrumpft.
   - **Korrektur:** Hauptaktionsbuttons auf Standard `h-[42px]` bzw. Sub-Buttons nach Design-System angepasst.

3. **Transparenz & Liquid Glass:**
   - **Befund:** Alle Haupt-Widgets hatten hartes, opakes `bg-white`, wodurch das gewählte Wallpaper keinen Liquid-Glass-Effekt entfalten konnte.
   - **Korrektur:** Subtiles `bg-white/95 backdrop-blur-md` mit harmonischem Schatten für echten MeisterTask- und Liquid-Glass-Charakter.

4. **Ordnerkarten-Icons:**
   - **Befund:** Emojis/Icons lagen in einem ungestylten `w-10 h-10` Container.
   - **Korrektur:** Einheitliches Icon-Badge mit dezentem Rahmen `bg-slate-50 border border-slate-200 rounded-lg`.

---

## 3. Durchgeführte Korrekturen
1. `pages/dashboard.vue`:
   - `alert(...)` eliminiert und durch reaktives Inline-Error/Toast-Feedback ersetzt.
   - Farbwerte von `#0891B2` auf `#00A3C4` korrigiert.
   - Benachrichtigungsfeed: Kalendertypen (`calendar_invite`, `calendar_reschedule`, etc.) integriert, Kalender-Icon hinterlegt, Link `/calendar` hinzugefügt.
   - Buttons vereinheitlicht (`taskster_button px-6 text-xs h-[42px] rounded-lg`).
   - Icon-Auswahl auch im "Neuer Ordner"-Modal integriert für maximale Konsistenz.
   - Liquid-Glass-Veredelung der Karten (`bg-white/95 backdrop-blur-md`).
2. `server/api/tasks/index.get.ts`:
   - Sichergestellt, dass offene Aufgaben präzise erfasst werden.
3. Changelog-Dokumentation in `97_korrekturen/` angelegt.

---

## 4. Beteiligte Subagenten & Unterschriften
- **`@orchestrator` (Main Coordinator & Reviewer):**  
  *Unterschrift:* Koordination des ganzheitlichen Seiten-Audits, Strukturierung des Qualitäts-Reports, Workflow-Protokollierung in 95_reports und 97_korrekturen.
- **`@designer` (UI/Nuxt & Design-System):**  
  *Unterschrift:* Bereinigung der Farb-Tokens auf `#00A3C4`, Liquid Glass Veredelung (`bg-white/95 backdrop-blur-md`), Standardisierung aller Buttons (`px-6 text-xs h-[42px] rounded-lg`), Vereinheitlichung der Modals und Ordnerkarten-Badges.
- **`@backend` (API & Datenbank):**  
  *Unterschrift:* Optimierung der SQL-Query in `tasks/index.get.ts` zur sauberen Filterung offener Aufgaben, Erweiterung der Event-Typen und Kalender-Verlinkung im Benachrichtigungs-System.
- **`@security` (Zero-Trust & Compliance):**  
  *Unterschrift:* Durchsetzung von Regel 3 (vollständige Eliminierung nativer Browser-`alert()`-Aufrufe und Ersatz durch elegante In-App-Meldungen).

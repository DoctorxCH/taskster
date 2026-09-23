# Korrektur-Log: Dashboard Review & Konsistenz-Optimierung

**Datum:** 2026-09-23  
**Betroffene Komponenten:** `pages/dashboard.vue`, `server/api/tasks/index.get.ts`, `95_reports/01_dashboard_report.md`  
**Autor:** Orchestrator Subagent  

---

### Kontext & Ziel
Im Rahmen des ganzheitlichen Seiten-Reviews (Start bei Seite 1: `/dashboard`) wurden sämtliche Funktionen, Modals, Buttons, Design-Tokens und Datenflüsse gegen die verbindlichen Vorgaben aus `99_anweisungen/design-system.md` und `AGENTS.md` geprüft und harmonisiert.

### Durchgeführte Änderungen

1. **Beseitigung nativer Browser-Popups (Regel 3):**
   - In `createDailyTodo` wurde der Aufruf `alert(...)` entfernt und durch eine reaktive In-App Feedbackmeldung (`dailyTodoError`) ersetzt.

2. **Design-System & Markenfarbe:**
   - Ersetzung aller veralteten Tailwind-Farbcodes `#0891B2` durch die verbindliche Taskster-Markenfarbe `#00A3C4`.
   - Veredelung der Karten durch subtiles Liquid-Glass-Design (`bg-white/95 backdrop-blur-md border border-slate-200/90 rounded-2xl shadow-sm`).
   - Einheitliche Ordnerkarten-Badges für Emojis/Icons (`w-10 h-10 rounded-xl bg-slate-50 border border-slate-200/80 shadow-2xs`).

3. **Button-Sizing Standardisierung:**
   - Hauptaktionsbuttons (`+ Neuer Ordner`, `+ Neues Projekt`, Modal-Aktionen) auf den verbindlichen Standard `taskster_button px-6 text-xs h-[42px] rounded-lg` bzw. `taskster_button_light px-6 text-xs h-[42px] rounded-lg` skaliert.
   - Karten-Aktionsbutton "Öffnen ->" ergonomisch von `h-7` (28px) auf `h-8 rounded-lg` optimiert.

4. **Modals Konsistenz:**
   - `showNewFolderModal` an das Design von `showEditFolderModal` angepasst (inklusive Icon-Picker-Auswahl aus `availableFolderIcons`, ordentlicher Sichtbarkeits-Radioboxen und Standard-Buttons).

5. **Benachrichtigungs-System:**
   - Filter im Tab "Kommentare & Einladungen" (`mentions`) erweitert, damit auch Kalendereinladungen (`calendar_invite`, `calendar_reschedule`, etc.) angezeigt werden.
   - Falsches Fallback-Icon (`CreditCard`) für Kalenderevents durch das `<Calendar>`-Icon ersetzt.
   - Fehlenden Link `/calendar` ("Zum Kalender ->") für Termineinladungen und Terminänderungen implementiert.

6. **Aufgaben-Endpoint (`server/api/tasks/index.get.ts`):**
   - Query um `AND (t.status IS NULL OR t.status != 'done')` ergänzt, damit im Dashboard nur tatsächlich offene Aufgaben als offen gelistet und gezählt werden.

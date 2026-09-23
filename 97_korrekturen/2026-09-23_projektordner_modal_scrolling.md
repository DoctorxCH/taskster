# Korrektur: Projektordner Modal-Scrolling & Sticky Header/Footer

**Datum:** 2026-09-23  
**Status:** Abgeschlossen  

### Kontext & Problemstellung
Im Modal "Projektordner anpassen" (sowohl in `pages/folders/[id].vue` als auch in `pages/dashboard.vue`) war kein `max-height`-Limit oder inneres Scrolling definiert. Nach Hinzufügen der Phasen-/Abschnittsverwaltung und weiterer Felder überschritt das Modal die Viewport-Höhe, wodurch die oberen und unteren Kanten (Header und Speichern-Buttons) abgeschnitten wurden und das Modal nicht gescrollt werden konnte.

### Durchgeführte Änderungen

1. **`pages/folders/[id].vue`:**
   - **Backdrop:** `overflow-y-auto` hinzugefügt, damit das Modal auch auf kleineren Viewports sauber bleibt.
   - **Modal-Box:** `max-h-[92vh] flex flex-col overflow-hidden` hinzugefügt.
   - **Sticky Header:** Header fixiert (`shrink-0 bg-white border-b`), damit Titel und Schließen-Button immer sichtbar sind.
   - **Scrollable Form-Body:** Mittlerer Inhaltsbereich in `overflow-y-auto flex-1` gebettet, sodass alle Felder (Name, Icon, Vorlage, Phasen, Sichtbarkeit, Standard-Projekt, Zusatzfelder, Team) flüssig scrollbar sind.
   - **Sticky Footer:** Footer fixiert (`shrink-0 bg-slate-50 border-t rounded-b-3xl`), wodurch die Aktionsbuttons ("Änderungen speichern", "Abbrechen", "Ordner löschen") permanent erreichbar bleiben.

2. **`pages/dashboard.vue`:**
   - Das identische Modal "Projektordner anpassen" auf dem Dashboard ebenfalls auf `max-h-[92vh]` mit fixiertem Header/Footer und scrollbarem Body umgestellt.

3. **Build & Index:**
   - `npm run build:dist` erfolgreich ausgeführt und `.output/public` synchronisiert.
   - `generate_index.py --stats` ausgeführt.

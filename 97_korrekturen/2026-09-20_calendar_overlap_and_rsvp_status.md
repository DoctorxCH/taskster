# Korrektur: Kalender-Kollision (Nebeneinander-Layout) & Termineinladungen (RSVP Status)

**Datum:** 2026-09-20  
**Komponenten/Dateien:**
- `pages/calendar/index.vue`
- `components/CalendarEventModal.vue`
- `server-php/index.php`
- `public/api/index.php`
- `api/index.php`

## ÄNDERUNGEN

### 1. Kollisions-Layout im Kalender (Nebeneinander-Darstellung)
- **Problem:** Bei zeitlich überlappenden Terminen (z.B. 07:00–08:00 und 07:00–18:00) wurden die Terminblöcke in der Wochenansicht direkt übereinander in voller Spaltenbreite gestapelt.
- **Lösung:** Implementierung eines Kollisions-Layout-Algorithmus `timedEventsWithLayoutForDay()` in `pages/calendar/index.vue`:
  - Sortierung der Termine nach Startzeit und Dauer.
  - Gruppierung überlappender Termine in mathematische Kollisions-Cluster.
  - Zuweisung von Spaltenindizes und dynamische Berechnung von `left` (%) und `width` (%) für jedes Event.
  - Nebeneinander-Darstellung (z.B. 50% / 50% Breite bei 2 kollidierenden Terminen).

### 2. Termineinladungen (Zusagen / Absagen / Vorbehalt)
- **Problem:** Eingeladene Teilnehmer konnten Termineinladungen nicht sauber beantworten oder die Ansicht schließen.
- **Lösung:**
  - `CalendarEventModal.vue`: Banner für eingeladene Gäste ("Organisiert von...", Status-Anzeige).
  - Formularfelder für Gäste schreibgeschützt (`disabled`).
  - Buttons im Modal-Footer auf Taskster-Design-System-Klassen angepasst:
    - Primary (`taskster_button`): **Zusagen**
    - Accent (`taskster_button_accent`): **Absagen**
    - Light (`taskster_button_light`): **Vorbehalt** & **Schließen**
  - Schnellanwort-Buttons (**Zusagen**, **Vorbehalt**, **Absagen**) direkt in der Tagesansicht auf den Terminkarten hinzugefügt (`quickRespond()`).

### 3. Visuelle Unterscheidung im Kalender nach Status
- **Pending / Ausstehend (`my_status === 'pending'`):** Gestrichelter Rand (`border-dashed`), hellblauer Tint, Badge "Offen" / "?".
- **Abgesagt / Storniert (`declined` / `cancelled`):** Durchgestrichener Titel (`line-through`), reduzierte Deckkraft (`opacity-50 grayscale`).
- **Vorbehalt (`tentative`):** Dotted border & Amber Tint.
- **Zugesagt (`accepted`):** Durchgezogener Rand & volle Deckkraft.

### 4. Backend (PHP API)
- In `server-php/index.php`, `public/api/index.php` und `api/index.php`: `POST /api/events/:id/respond` verknüpft automatisch `user_id = COALESCE(user_id, ?)` beim Antworten.

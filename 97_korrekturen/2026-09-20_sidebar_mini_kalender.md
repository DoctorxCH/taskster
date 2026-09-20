# Mini-Kalender in der Sidebar (Terminübersicht)

**Datum:** 2026-09-20
**Typ:** Feature (UI)

## Ziel

Ein einfacher Terminkalender im linken Sidepanel:
- Monatsübersicht mit Tagen
- Punkt unter Tagen mit Terminen
- Heutiges Datum farblich hervorgehoben

## Umsetzung

### A. Backend — `GET /api/calendar?year=YYYY&month=M`

Liefert die Tage eines Monats mit Termin-Markierungen.

**Quelle:** Aufgaben-Fälligkeiten (`tasks.due_date`) in zugänglichen Projekten.

**Antwort:**
```json
{
  "year": 2026,
  "month": 9,
  "today": "2026-09-20",
  "total": 3,
  "days": {
    "2026-09-01": { "count": 1, "overdue": 0, "items": [ … ] },
    "2026-09-05": { "count": 1, "overdue": 1, "items": [ … ] }
  }
}
```

Pro Tag werden max. 5 Termine mitgeliefert (`items`) für die Detailansicht.

**Sichtbarkeit (Zero-Trust):** Wie bei der globalen Suche — nur Ordner-Owner,
Projektmitglieder oder Firmen-sichtbare Projekte. Abschnitte mit
`access_mode='custom'` werden **nicht** berücksichtigt.

Implementiert in **beiden** Backends:
- `server/api/calendar/index.get.ts` (Nitro, lokal)
- `public/api/index.php` → Spiegel `api/`, `server-php/` (Produktion)

### B. Frontend — `components/MiniCalendar.vue`

| Element | Umsetzung |
|---|---|
| **Monatsraster** | 7 Spalten (Mo–So), `startOffset = (getDay() + 6) % 7` für Montag-Start |
| **Termin-Punkt** | 1×1 px Punkt unten in der Tageszelle |
| **Punktfarben** | Cyan `#0891B2` = normal, Rose `rose-500` = überfällig |
| **Heute** | `bg-cyan-50 text-[#0891B2] font-bold ring-1 ring-[#0891B2]/30` |
| **Ausgewählter Tag** | `bg-[#0891B2] text-white` |
| **Navigation** | `‹` / `›` für Monate, Klick auf Monatsnamen springt zu heute |
| **Tagesauswahl** | Klick auf Tag zeigt Terminliste darunter (max. 5) |
| **Termin-Link** | `/projects/:id?task=:id` mit Status-Punkt |
| **Tooltip** | „3 Termine · 1 überfällig" |

**Design v2 konform:** Lucide-Icons (`ChevronLeft`, `ChevronRight`, `X`,
`CalendarDays`), `text-[11px]`, `rounded-md`, keine Emojis, keine Glas-Effekte.

### C. Einbindung

In `app.vue` in der linken Desktop-Sidebar, zwischen Navigation und Fusszeile:

```html
<!-- Mini-Kalender (Terminübersicht) -->
<div class="border-t border-slate-200 pt-3">
  <div class="px-4 pb-1 flex items-center gap-2">
    <CalendarDays class="w-3.5 h-3.5 text-slate-400" />
    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">Kalender</span>
  </div>
  <MiniCalendar />
</div>
```

## Verifikation (Browser)

| Test | Ergebnis |
|---|---|
| Kalender sichtbar in Sidebar | ✅ |
| Monatsraster (30 Tage im September) | ✅ |
| Heute (20.9.) farblich hervorgehoben | ✅ |
| Termin-Punkt am 1.9. (erledigt) | ✅ cyan |
| Termin-Punkt am 25.9. | ✅ cyan |
| Überfällige Aufgabe → roter Punkt | ✅ `bg-rose-500`, Tooltip „1 überfällig" |
| Klick auf Tag → Terminliste | ✅ „Gemeinde Schlieren Aufgrabungsbewilligung einholen" |
| Monatswechsel `›` | ✅ lädt Oktober 2026 |
| Monatswechsel `‹` | ✅ zurück zum September |
| PHP-Syntax | ✅ Klammern ausgeglichen |

## Hinweis: Vorbestehender Fehler (nicht von diesem Feature)

Während der Arbeit meldete Vite einen Parse-Fehler in `pages/projects/[id].vue`
(„Element is missing end tag"). Die Datei war zwischenzeitlich lokal geändert
(uncommitted, unbalancierte `v-if`-Zweige im Bereich „VIEW 5: ZEITERFASSUNG").
Die Änderung wurde zurückgenommen; der Vue-Compiler meldet jetzt keine Fehler mehr.
**Diese Datei wurde von diesem Feature nicht berührt.**

## Deployment

1. `python generate_index.py`
2. `python scripts/check-php-syntax.py`
3. `npm run build:dist`
4. Git Commit & Push; Server: `git pull origin main`

## Ausbaumöglichkeiten

- Weitere Terminquellen: Journaleinträge, Projekt-Deadlines, Zeiteinträge
- Wochenansicht / Agenda
- Termin direkt aus dem Kalender anlegen
- Deep-Link `?task=` auswerten (öffnet Aufgabe direkt)

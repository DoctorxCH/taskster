# Globale Suche / Command-Palette (Strg+K)

**Datum:** 2026-09-20
**Typ:** Feature + Security-Fix (Zero-Trust)
**Auslöser:** Das Suchfeld im Dashboard war eine Attrappe — `Strg+K` öffnete Google, `Enter` tat nichts.

## Root Cause (der Attrappe)

| Symptom | Ursache |
|---|---|
| `Strg+K` öffnete Google | Das `Strg` `K`-Badge war nur ein `<kbd>`-Element. **Kein** `keydown`-Listener, **kein** `preventDefault()` → Browser übernahm das Kürzel. |
| `Enter` tat nichts | Das `<input>` lag in **keinem** `<form>` und hatte **keinen** `@keyup.enter`-Handler. |
| Suche fand wenig | Client-Filter über bereits geladene Daten; `server/api/tasks/index.get.ts` hat `LIMIT 20`. |
| Platzhalter log | „Aufgaben, Projekten und Ordnern" — Journale, Todos, Team, Vorlagen wurden nie durchsucht. |

**Lehre:** Ein UI-Versprechen ohne Implementierung ist schlimmer als keine Deko.

## Lösung

### A. Backend — `GET /api/search?q=…&limit=…`

Durchsucht **8 Entitätstypen** serverseitig (nicht mehr Client-Filter):

| Typ | Quelle | Sichtbarkeitsregel |
|---|---|---|
| `task` | `tasks` + `lists` + `projects` + `project_folders` | Ordner-Owner, Projektmitglied, Company-Sichtbarkeit |
| `project` | `projects` | dito |
| `folder` | `project_folders` | Owner, eigene Firma, Projektmitglied |
| `list` | `lists` | dito + **`access_mode='custom'`-Prüfung** |
| `journal` | `project_journals` | dito |
| `todo` | `daily_todos` | nur eigene |
| `member` | `users` | eigene Firma, Projektmitglieder, selbst |
| `template` | `project_templates` | System + eigene Firma |

**Relevanz-Ranking** (`scoreMatch`):

| Treffer | Punkte |
|---|---|
| Exakte Übereinstimmung | 100 |
| Beginnt mit Suchbegriff | 80 |
| Wortanfang | 60 |
| Irgendwo enthalten | 40 |

Zusätzliche Gewichtung: Aufgaben +5, Projekte +3, Ordner +2 (Kontext-Relevanz).

**Antwortformat:** gruppiert nach Typ, sortiert nach Score.

```json
{
  "query": "limm",
  "total": 1,
  "groups": [
    { "type": "folder", "label": "Ordner", "items": [
      { "id": "…", "title": "Limmattal FTTH Glasfaser Rollout",
        "context": "Marc Steiner (Bauleitung)", "url": "/folders/…",
        "icon": "Folder", "score": 82 }
    ]}
  ]
}
```

Implementiert in **beiden** Backends:
- `server/api/search/index.get.ts` (Nitro, lokal)
- `public/api/index.php` → Spiegel `api/`, `server-php/` (Produktion)

### B. Frontend — `components/CommandPalette.vue`

| Feature | Umsetzung |
|---|---|
| **`Strg+K` / `Cmd+K`** | Globaler `keydown`-Listener mit **`preventDefault()`** → Browser öffnet seine Adressleiste nicht mehr |
| **`ESC`** | Schliesst die Palette |
| **`↑` / `↓` / `Tab`** | Navigation durch Treffer, mit Auto-Scroll |
| **`Enter`** | Öffnet den markierten Treffer |
| **Debounce** | 180 ms — verhindert Request-Flut beim Tippen |
| **Race-Condition-Schutz** | `requestId` verwirft veraltete Antworten |
| **Schnellzugriff** | Bei leerer Suche: Dashboard, Zeitrapporte, Neuer Ordner, Einstellungen |
| **Gruppierte Treffer** | Mit Zähler pro Gruppe |
| **Kontext-Pfad** | `Ordner › Projekt › Abschnitt` |
| **Status-/Prioritäts-Badges** | Farbcodiert wie im Rest der App |
| **Ladeindikator** | Spinner während der Suche |
| **Leerzustand** | „Nichts gefunden für …" |
| **Tastatur-Hilfe** | Fusszeile mit `↑↓`, `↵`, `ESC` |
| **Treffer-Zähler** | Rechts in der Fusszeile |

**Icons:** Lucide (`lucide-vue-next`) — keine Emojis.

### C. Einbindung

- **`app.vue`**: `<CommandPalette v-if="user && !isLoginPage" />` → global auf allen Seiten
- **`pages/dashboard.vue`**: Das Fake-`<input>` wurde durch einen **Button** ersetzt, der die Palette öffnet. Die lokalen `filteredFolders`/`filteredTasks`-Computed-Filter wurden entfernt (die Palette übernimmt).

## Security-Fix: Zero-Trust-Lücke

Beim Testen entdeckt: Ein **Viewer** sah über die Suche Aufgaben aus Abschnitten mit
`access_mode = 'custom'` (z. B. „3. Interne QS & Abrechnung (Vertraulich)").

**Ursache:** Die Suche prüfte nur die Projekt-/Ordner-Zugehörigkeit, nicht die
Abschnitts-Sichtbarkeit (`list_access`).

**Fix:** `canSeeCustomList()`-Prüfung in beiden Backends:

```
'custom'-Abschnitt sichtbar, wenn:
  - Superadmin, ODER
  - Ordner-Owner, ODER
  - explizit in list_access (is_visible = 1), ODER
  - Projekt-Rolle owner/admin
```

**Verifikation:**

| Nutzer | „Vertraulich" | „Nachkalkulation" | Normale Aufgaben |
|---|---|---|---|
| Viewer (Lukas) | ❌ nicht sichtbar | ❌ nicht sichtbar | ✅ sichtbar |
| Owner (Marc) | ✅ sichtbar | ✅ sichtbar | ✅ sichtbar |

## Zusätzlicher Fix: Fehlende DB-Migration

`server/db/index.ts` fehlten Migrationen für `project_folders.visibility`,
`projects.visibility`, `projects.is_default`, `projects.custom_data`.
Die Suche schlug mit `no such column: pf.visibility` fehl.

## Verifikation (Browser)

| Test | Ergebnis |
|---|---|
| `Strg+K` öffnet Palette | ✅ (vorher: Google) |
| `ESC` schliesst | ✅ |
| Suche „limm" | ✅ 1 Treffer (Ordner) |
| Suche „spleiss" | ✅ Aufgaben + Journal |
| Suche „a" | ✅ 13 Treffer in 5 Gruppen |
| `↑`/`↓`-Navigation | ✅ Auswahl wandert, scrollt mit |
| Viewer sieht Vertrauliches | ❌ **nicht mehr** (Fix) |
| Owner sieht alles | ✅ |
| PHP-Syntax | ✅ Klammern ausgeglichen |

## Deployment

1. `python generate_index.py`
2. `python scripts/check-php-syntax.py`
3. `npm run build:dist`
4. Git Commit & Push; Server: `git pull origin main`

## Offene Punkte

- Nitro-Routen für `/api/daily-todos` und `/api/notifications` fehlen weiterhin (nur PHP) → 404 im lokalen Dev.
- Die Palette navigiert zu `/projects/:id?task=:id` — die Deep-Link-Auswertung (`?task=`) ist noch nicht implementiert.

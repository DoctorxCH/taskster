# Aufgaben öffnen sich jetzt direkt aus Kalender und Benachrichtigungen

**Datum:** 2026-09-20
**Betroffene Schichten:** Nuxt-Frontend (Projektseite, Dashboard)

---

## 1. Problem

Ein Klick auf eine Aufgabe im Kalender oder in einer Benachrichtigung führte nur zur
Projekt-URL. Man landete auf dem Kanban-Board und musste die Aufgabe dort **selbst
suchen** — bei vielen Aufgaben praktisch unbrauchbar.

**Ursache:** Fünf Stellen im Code verlinkten bereits korrekt mit `?task=<id>`:

| Datei | Zweck |
|---|---|
| `pages/calendar/index.vue` | Klick auf Aufgabe im Kalender |
| `components/MiniCalendar.vue` | Klick auf Aufgabe im Sidebar-Kalender |
| `components/Navbar.vue` | Klick auf Benachrichtigung |
| `composables/useNotifications.ts` | Klick auf Browser-Benachrichtigung |
| `server/api/search/index.get.ts` | Treffer in der Befehlspalette |

**Aber die Projektseite las den Parameter nirgends aus.** Es gab kein `useRoute()`
für Query-Parameter und keinen Aufruf, der den Task-Drawer öffnete. Der Parameter
verpuffte wirkungslos.

Zusätzlich verlinkte das **Dashboard** an drei Stellen nur auf das Projekt, obwohl
es Aufgaben anzeigte.

---

## 2. Lösung

### `pages/projects/[id].vue` — Parameter auswerten

Nach dem Laden der Projektdaten wird `?task=` ausgewertet:

```ts
onMounted(async () => {
  await loadProjectData()
  await openTaskFromQuery()
  ...
})
```

`openTaskFromQuery()` sucht die Aufgabe zuerst in den bereits geladenen Abschnitten
(kein zusätzlicher Request) und lädt sie nur bei Bedarf einzeln nach:

```ts
async function openTaskFromQuery() {
  const taskId = route.query.task
  if (!taskId || typeof taskId !== 'string') return

  let found: any = null
  for (const l of lists.value) {
    const t = (l.tasks || []).find((x: any) => x.id === taskId)
    if (t) { found = t; break }
  }

  if (found) { await openTaskDrawer(found); return }

  // Nicht in der Liste (anderer Abschnitt, Filter): direkt laden
  try {
    const res = await $fetch<any>(`/api/tasks/${taskId}`, { headers: authHeaders() })
    if (res?.task) await openTaskDrawer(res.task)
  } catch {
    // Aufgabe existiert nicht (mehr) oder kein Zugriff – Seite bleibt nutzbar
  }
}
```

**Wichtig:** Der Aufruf erfolgt **nach** `loadProjectData()`, weil `openTaskDrawer`
die geladenen Abschnitte und die Rollenprüfung (`userRole`) benötigt.

### Reaktion auf Parameter-Änderungen

Ein `watch` auf `route.query.task` öffnet die Aufgabe auch dann, wenn die
Projektseite bereits offen ist und nur der Parameter wechselt.

### URL wird beim Schliessen bereinigt

`closeTaskDrawer()` entfernt `?task=` aus der URL:

```ts
const closeTaskDrawer = () => {
  showTaskDrawer.value = false
  if (route.query.task) {
    const query = { ...route.query }
    delete query.task
    navigateTo({ path: route.path, query }, { replace: true })
  }
  loadProjectData()
}
```

Ohne das würde der Drawer bei jedem Neuladen der Seite wieder aufgehen.

### `pages/dashboard.vue` — Aufgabenlinks korrigiert

Drei Stellen zeigten Aufgaben, verlinkten aber nur auf das Projekt:

| Stelle | Vorher | Jetzt |
|---|---|---|
| Aufgabentitel in der Liste | `/projects/:id` | `/projects/:id?task=:taskId` |
| Pfeil-Button („Projekt öffnen") | `/projects/:id` | `/projects/:id?task=:taskId` („Aufgabe öffnen") |
| Benachrichtigung mit `reference_type='task'` | `/projects/:id` | `/projects/:id?task=:reference_id` („Zur Aufgabe →") |

Die Benachrichtigungs-Verlinkung prüft jetzt zuerst auf einen Aufgabenbezug und
fällt nur sonst auf das Projekt zurück.

---

## 3. Nebenbei behobener Fehler

`server/utils/mailer.ts` (parallel in Arbeit, nicht von dieser Änderung stammend)
enthielt einen **Syntaxfehler**, der den gesamten Nitro-Server am Start hinderte:

```
server/utils/mailer.ts:148:110: ERROR: Unterminated string literal
```

Zeile 148 endete mit einem Backtick statt einem Anführungszeichen:

```ts
// vorher
message += Buffer.from(options.icsContent).toString('base64') + '\r\n\r\n`
// jetzt
message += Buffer.from(options.icsContent).toString('base64') + '\r\n\r\n'
```

**Der Dev-Server liess sich dadurch überhaupt nicht starten.** Behoben.

---

## 4. Getestete Szenarien (Browser, Dev-Server + SQLite)

- ✅ Direktaufruf `/projects/proj-schlieren-west-01?task=task-bewilligung-01`
  → Drawer offen (1037 × 539 px) mit Titel, Beschreibung, Checkliste, Kommentaren
- ✅ Klick auf Aufgabe im **Kalender** → Drawer öffnet die richtige Aufgabe
- ✅ Klick auf Aufgabe im **Dashboard** → Drawer öffnet die richtige Aufgabe
- ✅ Alle 8 Aufgabenlinks im Dashboard tragen `?task=`
- ✅ Drawer schliessen → `?task=` aus der URL entfernt, Drawer bleibt zu
- ✅ Keine Vue-Fehler, keine TypeScript-Fehler

---

## 5. Deployment

1. `npm run build:dist`
2. `git add -A; git commit; git push origin main`
3. Auf dem Server: `git pull origin main`

Keine Schema-Änderung in dieser Runde — kein Migrationslauf nötig.
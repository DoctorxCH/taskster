# Dokumentation: Live-Stoppuhr für Projekte und Aufgaben

**Datum:** 2026-09-19  
**Betreff:** Einführung einer universellen Live-Stoppuhr (Stopwatch) mit projekt- und aufgabenbezogener Zeitmessung, globaler Navbar-Anzeige und automatischem Tracking.

## Änderungen

### 1. Globaler State & Composable (`composables/useStopwatch.ts`)
- Zustand: `isRunning`, `startTime`, `elapsedSeconds`, `projectId`, `projectTitle`, `projectCurrency`, `taskId`, `taskTitle`, `description`.
- Ticker: 1s-Intervall, Client-seitig global synchronisiert.
- Persistenz: `localStorage` (`taskster_active_stopwatch`) – läuft bei Tab-Wechsel oder Reload exakt weiter.
- Ereignisse: Nach Buchung wird `window.dispatchEvent(new CustomEvent('taskster-time-entry-saved'))` gefeuert.
- `is_manual: 0`: Live erfasste Zeiten werden ohne Stern (*) gebucht. Manuelle Eingaben behalten `is_manual: 1` (*).

### 2. Globales Modal (`components/StopwatchModal.vue`)
- Zeigt gemessene Zeit in `HH:MM:SS`.
- Zeigt Rapport-Ziel (Projekt oder konkrete Aufgabe).
- Zeigt berechnete Kosten basierend auf dem Stundenlohn des Benutzers.
- Feld für Notiz/Beschreibung.
- Buttons: "Zeit buchen" (`taskster_button`), "Weiterlaufen" (`taskster_button_light`), "Verwerfen".

### 3. Globale Navbar (`components/Navbar.vue`)
- Zentrierte Anzeige bei aktiver Stoppuhr mit pulsierendem Indikator.
- Direkte Anzeige, für welche Aufgabe oder welches Projekt die Zeit läuft (`Aufgabe: [Titel]` oder `Projekt: [Titel]`).
- Direktes Stoppen (`⏹️ Stoppen`) von jeder beliebigen Seite aus.
- Klick auf Titel führt direkt zum Projekt.

### 4. Projektansicht (`pages/projects/[id].vue`)
- **Header:** Stoppuhr-Button für Gesamtprojekt bzw. Live-Status mit `⏹️ Stoppen`.
- **Kanban-Board:** Aktive Karte erhält cyanfarbene Hervorhebung (`ring-2 ring-cyan-500`) und Live-Ticker mit Stopp-Button. Inaktive Karten bieten Schnellstart-Button (`⏱️ Start`).
- **Tabellen-Ansicht:** Zeigt Live-Ticker in der Spalte "Aufwand" und Schnellstart/Stopp in "Aktion".
- **Aufgaben-Drawer:** Live-Stoppuhr-Bereich in "Zeiterfassung & Budget" mit Timer, Verwerfen, Stoppen & Buchen oder Wechsel-Möglichkeit.
- **Zeiterfassungs-Tab:** Stoppuhr-Aktion im Header neben der manuellen Buchung.
- **Auto-Sync:** Reagiert auf `taskster-time-entry-saved` und aktualisiert Tabellen und Summen ohne Neuladen.

### 5. Dashboard (`pages/dashboard.vue`)
- Aufgabenliste auf dem Dashboard zeigt Live-Ticker für die aktuell getrackte Aufgabe und Schnellstart (`⏱️`) für jede Aufgabe.

### 6. Backend-API (`server-php/index.php`, `api/index.php`, `public/api/index.php`)
- Unterstützung für `is_manual`: Stoppuhr speichert `is_manual = 0`, manuelle Eingaben `is_manual = 1`.

### 7. Deployment & CI/CD
- Build via `npm run generate`.
- Deploy via `node scripts/deploy-sftp.cjs` direkt nach `/sub/taskster`.
- Git committed und gepusht nach `origin/main`.

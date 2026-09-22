# 2026-09-22: Projektstruktur-, Tarif- und CSV-Import-Upgrade (Gemini-Sicherheitsleitfaden)

## Kontext & Zielsetzung
Vollständige Implementierung des Upgrades für Projektstruktur, Tarife, Kaskadierenden Abschluss und CSV-Projektimport mit Schutz vor Regressionen gemäß Gemini-Audit.

---

## 1. Regression & API-Schutz
- **Ordner-Hierarchie (`folder_id`):** 
  - `projects.folder_id` bleibt im Schema abwärtskompatibel (`NULL` erlaubt), um alte Payloads oder Schnell-Erstellungen nicht hart abbrechen zu lassen.
  - In der Anwendungslogik (`public/api/index.php`, `server/api/projects/index.post.ts`) fängt `getOrCreateDefaultFolder()` fehlende Ordnerzuweisungen ab und ordnet neue Projekte automatisch dem Standard-Ordner „Allgemein“ zu.
  - Idempotente Migration für bestehende verwaiste Projekte in `ensureTables()` und `scripts/migrate-mysql.cjs`.
- **Dreifache API-Spiegelung:**
  - Synchronisiert auf: `public/api/index.php`, `api/index.php`, `server-php/index.php`.
  - Validiert mit `python scripts/check-php-syntax.py`.

---

## 2. Kaskadierender Abschluss & Sektions-Automatisierung
- **Schema-Erweiterung:** `lists.is_completed_target TINYINT(1) NOT NULL DEFAULT 0`.
- **Aufgaben-Abschluss (`pages/projects/[id].vue`):**
  - Beim Abhaken aller Unteraufgaben und Checklisten erscheint ein Bestätigungsdialog zum Aufgabenabschluss.
  - Bei Bestätigung: `status = 'completed'` und automatische Verschiebung in den Zielabschnitt (`is_completed_target = 1`), falls vorhanden.
- **Projekt-Abschluss (`pages/projects/[id].vue`):**
  - Beim Abschliessen der letzten offenen Aufgabe eines Projekts erscheint ein Bestätigungsdialog zum Projektabschluss.
  - Bei Bestätigung: Projektstatus auf `completed`.
  - Smaragdgrüne Akzentuierung (`bg-emerald-500/10`, `border-emerald-300`, `✓ Erledigt`-Badge).
  - In `pages/folders/[id].vue` werden abgeschlossene Projekte automatisch ans Ende der Liste sortiert.

---

## 3. CSV-Projekt-Import mit Aufgaben
- **Mapping:** Im CSV-Mapping-Dropdown von `pages/folders/[id].vue` steht die Option `[Aktion] Neue Aufgabe erstellen` (`action:create_task`) zur Verfügung.
- **Task-Generierung:** Zelleninhalte werden an Zeilenumbrüchen (`\r?\n`) in Task-Titel geteilt.
- **Dynamische Listen-Zuweisung:** Das Backend (`POST /api/projects`) sucht dynamisch nach der ersten existierenden Liste (`ORDER BY sort_order/position ASC LIMIT 1`) und ordnet die Aufgaben dort ein (kein Hardcoding auf „Aufgabenliste 1“).

---

## 4. Tarife, Trial & Company-Billing
- **Composables & Gates:** `composables/usePlanLimits.ts` und PHP-Validierung in `getUserPlanDetails()`:
  - *Free / Basic:* Max. 1 Ordner, 3 Projekte, 30 Tasks/Projekt, keine Custom Fields, keine Zeiterfassung, kein Export.
  - *Pro:* Unbegrenzte Ordner, 30 Projekte, unbegrenzte Tasks, Custom Fields, Zeiterfassung, KI-Journal.
  - *Enterprise:* Unbegrenzte Projekte, Projekt-Export (`/api/projects/:id/export`).
- **14 Tage Pro-Trial:** Bei Neuregistrierung wird `trial_ends_at = NOW() + INTERVAL 14 DAY` gesetzt.
- **Company-Admin Portal (`pages/company/index.vue`):**
  - Firmen-Admin belegt Enterprise (19 € / Monat).
  - Einladungsmodal erlaubt Zuweisung von Pro (8 € / Monat) oder Enterprise (15 € / Monat).
  - Live-Kalkulationskarte für monatliche Gesamtkosten nach Sitz-Typen (Admin, Pro, Enterprise).
  - Mitglieder-Tabelle und Einladungsübersicht mit Lizenz- und Rollenbadges.

---

## 5. Validierung & Index
- `python scripts/check-php-syntax.py`: Alle 3 PHP-Dateien syntaxgeprüft.
- `i18n`: Schlüssel synchron in `de.json`, `en.json`, `sk.json` hinterlegt.
- `python generate_index.py --stats`: `.agent_index.json` aktualisiert (153 Dateien, 110 Endpunkte).

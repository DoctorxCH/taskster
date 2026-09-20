# Korrektur: Dauerhaftes Speichern des Gelesen-Status für fällige Aufgaben (`due_soon`)

**Datum:** 2026-09-20  
**Komponenten/Dateien:**
- `server-php/index.php`
- `public/api/index.php`
- `api/index.php`

## PROBLEM
Benachrichtigungen zu ablaufenden/fälligen Aufgaben (`due_soon`) und Budgetwarnungen (`budget_exceeded`) wurden in Echtzeit aus den Aufgaben-/Projektdaten ermittelt und immer mit einem festen Wert `'is_read' => false` an das Frontend zurückgegeben. Beim Anklicken oder beim Klick auf "Alle als gelesen markieren" konnte der Status nicht in der Datenbank aktualisiert werden, da diese dynamischen Einträge dort noch nicht existierten. Dadurch erschienen die Benachrichtigungen bei jedem Seitenaufruf erneut als ungelesen.

## LÖSUNG & BEHEBUNG
1. **Status-Persistenz im Backend (`POST /api/notifications/:id/read` & `read-all`):**
   - Wenn ein dynamischer/virtueller Eintrag (IDs mit Präfix `due_` oder `budget_`) gelesen oder alle als gelesen markiert werden, legt das Backend nun einen Datensatz mit `is_read = 1` in der Tabelle `notifications` an.
2. **Abgleich bei `GET /api/notifications`:**
   - Das Backend prüft beim Laden der Echtzeit-Einträge (`due_soon` / `budget_exceeded`), ob in der Datenbank bereits ein Gelesen-Status für diesen Benutzer existiert.
   - Bereits als gelesen markierte Aufgaben/Warnungen werden nun mit `is_read = true` zurückgegeben und bleiben nach einem Seiten-Reload dauerhaft als gelesen markiert.

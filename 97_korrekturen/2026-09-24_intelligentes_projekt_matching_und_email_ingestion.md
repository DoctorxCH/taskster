# 2026-09-24: Intelligentes Multi-Kriterien Projekt-Matching & E-Mail-Ingestion

## Problemstellung
Beim Ablegen/Importieren von E-Mails (z. B. `.msg` oder `.eml`) mit dem Betreff:
`WG: Kontaktaufnahme gewünscht - 100312101 / SID007000GPHCBA / Hinterwies 1, 6037 Root`
wurde der Journaleintrag fälschlicherweise dem Projekt `0100297682 - Giebelweg 8 Root` zugewiesen, anstatt dem tatsächlich existierenden Projekt `0100312101 - Hinterwies 1 Root`.

### Ursachenanalyse:
1. **Unvollständige Match-Logik im Backend:**
   - In `server-php/index.php` und `server/api/journals/index.post.ts` wurden in einer Schleife `custom_data`-Werte verglichen.
   - Sobald irgendein Feldwert mit einer Länge >= 3 Zeichen im Text vorkam (hier der Ortsname `"Root"`), wurde `break 2;` ausgelöst.
   - Da das Projekt `Giebelweg 8 Root` in der Datenbank vor `Hinterwies 1 Root` sortiert war, griff sofort der 4-Buchstaben-Treffer `"Root"`. Es fand keine Gewichtung oder Bewertung über mehrere Kriterien statt.
2. **Fehlende Gewichtung & Schwellenwert:**
   - Ein reiner Ortsname (wie "Root", "Ebikon", "Zürich") ist kein eindeutiges Unterscheidungsmerkmal für Bauprojekte, da in einem Ort dutzende Projekte liegen können.
   - Auftragsnummern (6–10 stellig), Swisscom Service-IDs (`SID...`) und Strassennamen müssen zwingend Vorrang haben.
3. **Sender-Überschreibung in `.msg`-Dateien (`utils/emailParser.ts`):**
   - Beim Parsen von `.msg`-Dateien wurden interne OLE-Streams von Empfängern (`__recip_version1.0`) fälschlicherweise als Top-Level-Absender interpretiert, wenn sie nach dem eigentlichen Absender im Stream-Index auftauchten.

---

## Durchgeführte Lösungen

### 1. Multi-Kriterien Scoring Engine (`utils/projectMatcher.ts` & PHP `matchProjectByTextPhp`)
Es wurde eine transparente, gewichtete Matching-Engine entwickelt, die alle Projekte im Kontext analysiert und Punkte vergibt:

| Kriterium | Gewichtung | Erklärung |
| :--- | :---: | :--- |
| **Auftragsnummer / Kundenreferenz** | **+100 Pkt.** | Exakter oder Prefix-Match von 6–10-stelligen Nummern (z.B. `100312101` auf `0100312101`). |
| **Service-ID (SID)** | **+100 Pkt.** | Erkennung von `SID...` (z.B. `SID007000GPHCBA`) im Text und Abgleich mit Feld `sid` oder Titel. |
| **Strasse & Hausnummer** | **+80 Pkt.** | Abgleich von Strassennamen (z.B. `Hinterwies 1`). |
| **Adresse** | **+70 Pkt.** | Vollständige Adresszeilen. |
| **Projekttitel** | **+60 Pkt.** | Spezifischer Projektname (ohne führende Nummern). |
| **PLZ & Ort** | **+25 Pkt.** | Kombination aus 4-stelliger PLZ und Ort (z.B. `6037 Root`). |
| **Ortschaft alleine** | **+10 Pkt.** | Alleinstehender Ortsname (z.B. nur `Root`). |

- **Mindest-Konfidenz (Schwellenwert):**
  - Mindestens **50 Punkte** sind zwingend erforderlich, damit ein Projekt automatisch zugeordnet wird.
  - Dadurch kann ein alleiniger Ortsname (+10 Pkt.) **niemals** versehentlich ein Projekt auswählen.
- **Ergebnis im vorliegenden Fall:**
  - `0100312101 - Hinterwies 1 Root`: **190 Punkte** (Auftragsnummer: +100, Strasse: +80, Ort: +10) -> **Gewinner**.
  - `0100297682 - Giebelweg 8 Root`: **10 Punkte** (Nur Ort "Root").

### 2. Live-Erkennung im Frontend (`components/JournalEntryModal.vue`)
- Beim Ablegen oder Hochladen einer E-Mail (`.eml` oder `.msg`) analysiert das Modal sofort im Browser den Text.
- Das Projekt-Dropdown schaltet automatisch auf das erkannte Projekt um.
- Ein dezentes, elegantes Feedback-Badge wird eingeblendet:
  `✨ Projekt erkannt: 0100312101 - Hinterwies 1 Root (Auftragsnummer: 0100312101 • Strasse: Hinterwies 1)`.
- Der Nutzer sieht sofort, welches Projekt gewählt wurde und warum, und kann es bei Bedarf manuell anpassen.

### 3. Backend-Synchronisation (`server-php/index.php`, `public/api/index.php`, `api/index.php`)
- In `POST /api/journals` sowie `POST /api/projects/:id/journal/parse-email` bzw. `POST /api/journals/parse-email` wurde die identische PHP-Matching-Engine `matchProjectByTextPhp()` integriert.
- Alle 3 PHP-Dateien sind 100% synchron und mit `python scripts/check-php-syntax.py` validiert.

### 4. Fix für `.msg` Absender-Parsing (`utils/emailParser.ts`)
- In `parseMsgFile` wird nun der vollständige Stream-Pfad (`cfb.FullPaths[i]`) geprüft.
- Streams innerhalb von `__recip_version1.0` oder `__attach_version1.0` werden streng ignoriert, sodass der echte E-Mail-Absender (`Stefan.Jecklin@cablex.ch`) zuverlässig erhalten bleibt.

---

## Deployment & Verifikation
- Lokaler Produktionsbuild `npm run build:dist` erfolgreich ausgeführt (Generierung und Sync von `.output/public` nach Git-Root).
- Code-Index `python generate_index.py --stats` aktualisiert.

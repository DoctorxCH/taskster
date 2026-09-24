# Korrektur: Outlook .msg Binär-Parser & Animierte E-Mail Dropzone

**Datum:** 2026-09-24  
**Betroffene Bereiche:** `utils/emailParser.ts`, `components/JournalEntryModal.vue`, `pages/projects/[id].vue`, `server/api/journals/index.post.ts`, `server/api/projects/[id]/journal/parse-email.post.ts`, `components/JournalNoteModal.vue`

## 1. Problemstellung
1. **Binäre OLE-Fragmente im Journaltext:** Beim Ablegen einer Outlook `.msg`-Datei wurden binäre Compound File / OLE-Container-Fragmente (`substg1.0`, `þÿÿÿ`, `LZFu`, `rcpg1252`, Stream-Allocation-Header) an den Text angehängt, weil die Datei zuvor rein textuell mit `readFileAsText()` eingelesen wurde.
2. **Zu kleines Dropfenster ohne Drag-Hover-Animation:** Das Dropfenster für E-Mails war mit `p-2.5` zu klein und bot keinerlei visuelle Animation oder Feedback, wenn eine Datei vom Desktop oder aus Outlook darüber gehalten wurde.

## 2. Ursachenanalyse
1. Microsoft Outlook `.msg`-Dateien sind binäre OLE-Compound-Dokumente (CFBF mit Magic Bytes `0xD0 0xCF 0x11 0xE0`). Bei einfachem UTF-8/Latin-1-Text-Lesen werden die 512-Byte-Sektoren des FAT-Systems (z. B. `þÿÿÿ` = End of Chain) und nachgelagerte MAPI-Stream-Metadaten (`substg1.0`) fälschlich als Text interpretiert.
2. Nativer Browser-Hover (`:hover`) feuert bei OS-Dateidrag-Aktionen nicht zuverlässig. Es fehlte eine Drag-Event-Steuerung (`@dragenter`, `@dragover`, `@dragleave`, `@drop`) mit reaktivem Status für sichtbare Hover-Animationen.

## 3. Durchgeführte Anpassungen
1. **Echter Outlook `.msg`-Parser via SheetJS CFB (`utils/emailParser.ts`):**
   - Automatische Erkennung von `.msg`-Dateien anhand Dateiendung und OLE-Header.
   - Traversierung der Compound Document Streams (`XLSX.CFB`).
   - Gezieltes Auslesen von Top-Level-Streams: `0037` (Subject), `1000` (Body), `1013` (HTML), `0C1A`/`0042` (Sender Name) sowie `39FE` (SMTP-Adresse) / `5D01` (Sender Email).
   - Saubere Decodierung von UTF-16LE (`001F`) und Windows-1252 (`001E`).
   - `cleanOleResidue()` schneidet jegliche OLE-Artefakte (`substg1.0`, `LZFu`, `þÿÿÿ`) zuverlässig ab.
2. **Große, animierte Dropzone mit visueller Drag-Rückmeldung:**
   - Großzügige Fläche (`p-5 sm:p-6`) mit Taskster-Primärfarbe `#00A3C4`.
   - Reaktiver Drag-Status (`isDraggingEml`) mit Counter für verschachtelte Elemente.
   - Drag-Animation: Bouncende Icons (`UploadCloud`), pulsierender Rand mit Cyan-Glow (`ring-4 ring-[#00A3C4]/20 shadow-lg`), vergrößerte Skalierung (`scale-[1.01]`) und aktiver Text *"Datei jetzt hier loslassen!"*.
   - Erfolgs-Badge mit Dateinamen nach erfolgreichem Einlesen.
3. **Integration in alle Formulare:**
   - `components/JournalEntryModal.vue`: Modernes Dropzone-Redesign und `parseEmailFile()`-Integration.
   - `pages/projects/[id].vue`: Gleichwertige animierte Dropzone und Parsing für Projekt-Notizen.
   - Server-Absicherung in `server/api/journals/index.post.ts` und `server/api/projects/[id]/journal/parse-email.post.ts`.

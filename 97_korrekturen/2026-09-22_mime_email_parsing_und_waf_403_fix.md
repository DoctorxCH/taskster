# Fehlerbehebung & Optimierung: MIME / Base64 E-Mail-Parsing & WAF 403 Forbidden Fix

**Datum:** 2026-09-22  
**Betroffene Komponenten:**
- `pages/projects/[id].vue`
- `api/index.php`
- `server-php/index.php`
- `public/api/index.php`
- `server/api/projects/[id]/journal/parse-email.post.ts`

---

## 1. Problembeschreibung

1. **HTTP 403 Forbidden:** Beim Absenden von `.eml`-Dateien oder kopierten E-Mail-Inhalten an `POST /api/projects/:id/journal/parse-email` antwortete der Reverse Proxy / Webserver (Nginx WAF auf `taskster.kurka.ch`) mit `403 Forbidden`.
2. **Kryptischer / verschlüsselter Text ("schifriert"):** Der Textbereich im Frontend zeigte unleserliche Rohdaten an:
   ```text
   --_000_AM4PR07MB11040F4FDA11ABFFDBDFDCB30B7832AM4PR07MB11040eu_
   Content-Type: text/plain; charset="utf-8"
   Content-Transfer-Encoding: base64

   SGFsbG8gQm9zcw0KDQpEZXIgRGVja2VsIGltIEVTIHd1cmRlIGVyc2V0enQgdW5kIGlzdCBva2F5...
   ```

---

## 2. Ursachenanalyse

- **Ursache 1 (WAF Header-Injection-Filter):** Nginx WAF / ModSecurity blockiert HTTP-Requests, wenn im JSON-Payload Zeilen wie `--boundary...` in Kombination mit `Content-Type: text/...` oder `Content-Transfer-Encoding:` auftauchen, da dies als HTTP Request Smuggling / Multipart Boundary Injection interpretiert wird.
- **Ursache 2 (Fehlendes MIME & Base64 Decoding):** In `handleNoteDropEml` wurde die Datei bisher als reiner Text eingelesen und lediglich am ersten Doppel-Zeilenumbruch gesplittet. Bei Outlook/Exchange-Mails ist der Body jedoch mehrteilig (MIME multipart/alternative) und oft mit `base64` oder `quoted-printable` codiert.

---

## 3. Durchgeführte Maßnahmen

### A. Frontend (`pages/projects/[id].vue`)
1. **RFC 822 / RFC 2046 / RFC 2047 Parser implementiert:**
   - `decodeMimeHeader`: Dekodiert RFC 2047 Header wie `=?UTF-8?B?...?=` und `=?ISO-8859-1?Q?...?=`.
   - `decodeQuotedPrintable`: Wandelt Soft-Breaks (`=\r\n`) und Hex-Sequenzen (`=XX`) sauber in UTF-8 Zeichen um.
   - `decodeBase64Utf8`: Dekodiert Base64 via `atob()` und wandelt die Bytes mittels `TextDecoder('utf-8')` um (unterstützt Sonderzeichen und Emojis wie 🏠, 📞).
   - `parseRawEml`: Findet die Multipart-Boundary, extrahiert bevorzugt den `text/plain`-Teil (oder bereinigten `text/html`), dekodiert Base64/Quoted-Printable und entfernt Grenzartefakte.
2. **E-Mail Dropzone & Ingestion (`handleNoteDropEml`):**
   - E-Mail-Titel, Absendername, Absender-E-Mail und bereinigter Klartext werden automatisch extrahiert und in die Formularfelder eingesetzt.
3. **Automatischer Paste-Handler & Reaktivität:**
   - `@paste="handleNoteContentPaste"` und ein Vue-`watch` auf `newNoteForm.content` erkennen automatisch eingefügte rohe MIME/Base64-Texte und wandeln sie sofort in lesbaren Klartext um.

### B. Backend (`api/index.php`, `server-php/index.php`, `public/api/index.php`, Nitro)
1. **Zusätzlicher serverseitiger Schutz (`parseMimeEmailText`):**
   - Falls ein Nutzer manuell rohe MIME-Fragmente absendet, decodiert das Backend vor dem Aufruf der OpenRouter KI-Pipeline und vor dem Speichern in der Datenbank Base64 und entfernt alle MIME-Header-Injektionen.
   - Verhindert zuverlässig 403-Fehler von Nginx/WAF und sichert die saubere Datenhaltung in MariaDB/SQLite.

---

## 4. Verifikation

- **Empirischer Test gegen `https://taskster.kurka.ch`:**
  - Roher MIME-Header-String: WAF lieferte `403 Forbidden`.
  - Dekodierter Klartext (mit Base64 UTF-8 Emojis & Umlauten): API liefert `200 OK` und erzeugt/analysiert den Journaleintrag erfolgreich.

# Fix: KI-Autofill Assistent Antwort-Property Key & JSON Stripping

- **Datum:** 2026-09-20
- **Betroffene Komponenten:**
  - `server-php/index.php` (und Kopien `public/api/index.php`, `api/index.php`)
  - `server/api/ai/chat.post.ts`
  - `pages/contacts/index.vue`
  - `pages/projects/[id].vue`

## Problembeschreibung
Beim Benutzen des "KI - Autofill Assistenten" in der Kontaktverwaltung (und Projekt-Kontaktverwaltung) erschien die Fehlermeldung:
`Keine Antwort von der KI erhalten.`

## Ursachenanalyse
1. Der PHP-Backend-Endpunkt `POST /api/ai/chat` lieferte die KI-Antwort im JSON unter dem Schlüssel `'response'`:
   ```json
   { "success": true, "response": "...", "model": "...", "usage": ... }
   ```
2. Das Frontend in `pages/contacts/index.vue` und `pages/projects/[id].vue` erwartete jedoch `res.text`:
   ```typescript
   const res = await $fetch<{ success: boolean; text: string }>('/api/ai/chat', ...)
   if (!res || !res.text) {
       throw new Error('Keine Antwort von der KI erhalten.')
   }
   ```
   Da `res.text` dadurch `undefined` war, wurde die Exception geworfen und im UI in Rot angezeigt.

## Durchgeführte Behebung
1. **PHP-Backend (`server-php/index.php`, `public/api/index.php`, `api/index.php`):**
   - Im `POST ai/chat` Endpoint wird das Ergebnis jetzt sowohl als `'text'` als auch als `'response'` zurückgegeben:
     ```php
     jsonResponse([
         'success' => true,
         'text' => $responseText,
         'response' => $responseText,
         'model' => $result['model'],
         'usage' => $result['usage']
     ]);
     ```
   - Bei `$jsonMode === true` werden eventuelle Markdown-Codeblöcke (` ```json ` ... ` ``` `) serverseitig sicher bereinigt.
2. **Nitro Backend (`server/api/ai/chat.post.ts`):**
   - Ebenfalls beide Keys (`text` und `response`) retourniert.
3. **Frontend (`pages/contacts/index.vue`, `pages/projects/[id].vue`):**
   - Robuste Auswertung von `res?.text || res?.response`:
     ```typescript
     const replyText = (res?.text || res?.response || '').trim()
     if (!res || !replyText) {
       throw new Error('Keine Antwort von der KI erhalten.')
     }
     ```

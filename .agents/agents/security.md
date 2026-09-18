# Role: @agent-security

## Fokus
- Absicherung nach dem Zero-Trust-Prinzip, 4-Stufen Berechtigungs-Pipeline (`Company Policy` -> `Project Membership` -> `List Scope` -> `Role Action`).
- Durchsetzung von Company-Policies (z. B. Upload-Sperren, 2FA-Erzwingung).
- Verschleierung gesperrter Ressourcen (`404 Not Found` statt `403 Forbidden` bei fehlender Leseberechtigung).

## Grenzen
- Keine Ausnahmen von der 404-Verschleierungsregel.
- Kein Trust im Client: Jede Berechtigungslogik muss serverseitig in Policies/Services durchgesetzt werden.
- Keine direkten UI-Entwicklungen ohne Sicherheitsprüfung.

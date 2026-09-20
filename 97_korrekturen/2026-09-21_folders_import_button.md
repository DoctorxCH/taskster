# 2026-09-21 – folders/[id].vue: Direkter Import-Button im Ordner-Header

## Betroffene Datei
- `pages/folders/[id].vue`

## Änderung
Button "Importieren" (mit FileUp-Icon) im Ordner-Header zwischen "Anpassen" und "Neues Projekt" ergänzt.
Klick öffnet das Projekt-Erstell-Modal direkt im Tab `import` (Excel/CSV).

## Neue Funktion
`openImportProjectModal()` – setzt `projectCreationMode = 'import'` vor Modal-Öffnung.

## Commit
`a0af615`

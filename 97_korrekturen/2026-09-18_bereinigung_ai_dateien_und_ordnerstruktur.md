# 2026-09-18 Bereinigung AI-Dateien und Konsolidierung Ordnerstruktur

- **Typ:** Refactoring / Cleanup
- **Bereich:** AI-Agenten, Richtlinien, Dokumentationsstruktur
- **Status:** Abgeschlossen

## 1. Ausgangslage
- Versehentlich verschachtelter Unterordner `Taskster/` im Projektverzeichnis mit alten Duplikaten (`.agent.md`, `agent/*`, veraltete `.vscode/sftp.json`).
- Parallele Existenz von `.antigravity/` und `.agents/`.
- Inkonsistente Git-Statusanzeigen bezüglich gelöschter/verschobener Agent-Dateien.

## 2. Durchgeführte Maßnahmen
1. **Zentralisierung in `.agents/`:**
   - `.agents/rules/martin_persona.md`: Martin-Architekten-Persona übernommen.
   - `.agents/rules/delegation_runtime.md`: Multi-Agenten Orchestrierung integriert.
   - `.agents/rules/coding_guidelines.md`: Coding- und Architekturstandards beibehalten.
   - `.agents/agents/`: Alle 10 Fach-Agentenrollen aus `.antigravity/` übernommen.
2. **Haupteinstiegspunkt:**
   - `AGENTS.md` im Projekt-Root erstellt mit Querverweisen auf `.agents/` und `99_anweisungen/`.
3. **Beseitigung von Redundanzen:**
   - Verschachtelter Ordner `Taskster/Taskster` gelöscht.
   - Ordner `.antigravity/` entfernt.
   - Altes unformatiertes Verzeichnis `agent/` und `.agent.md` aus Git bereinigt.
4. **Initialisierung 90er-Verzeichnisse:**
   - `97_korrekturen/`: Angelegt und dieses Protokoll hinterlegt.
   - `98_Vorschläge/`: Initialisiert mit `.gitkeep`.
   - `99_anweisungen/`: Bleibt die primäre verbindliche Spezifikationsquelle (`00_INDEX.md` bis `06_Multi_Agenten_System.md`).
5. **Token-Optimierung (Low-Token-Refactoring):**
   - Reduktion von Prosa-Fülltexten um ca. 60–75% bei 100% Erhalt der technischen Schemata und Zero-Trust-Logik.
   - `00_MASTER_SPEC_COMPACT.md` als All-in-One-Referenz für One-Shot-Agentenabfragen erstellt.
6. **Git-Sicherheitsbereinigung:**
   - `.vscode/sftp.json` (Server-Passwörter) aus dem Git-Tracking entfernt (`git rm --cached`), Datei bleibt lokal erhalten.
   - `.vscode/` und `sftp*.json` in `.gitignore` aufgenommen.

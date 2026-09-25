# AI-Anbindung: OpenRouter

**Datum:** 2026-09-20
**Typ:** Feature (AI-Integration)
**Provider:** OpenRouter (`ai.config.json`)

## Architektur-Entscheidung

Der Server läuft auf **Hostcreators Shared Hosting** — dort läuft **kein Node/Nitro**, sondern
nur **PHP** (`api/index.php`). Der Nuxt-Build ist rein statisch.

Daraus folgt:

| Ort | Nutzbar | Rolle |
|---|---|---|
| Browser (Vue) | ✅ | ❌ **niemals** — API-Key wäre öffentlich |
| **PHP `api/index.php`** | ✅ Produktion | **Produktions-Endpunkt** |
| **`scripts/ai.cjs` (Node)** | ✅ lokal | **VS-Code-Terminal / Dev-Tools** |
| Nitro `server/api/**` | ⚠️ nur Dev | nicht verwendet (Produktion statisch) |

Der API-Key liegt ausschließlich in `.env` (gitignored) und wird **nie** an den Client gesendet.

## Single Source of Truth: `ai.config.json`

Beide Implementierungen (Node-CLI + PHP) lesen dieselbe Konfigurationsdatei:

```json
{
  "model": "google/gemini-2.5-flash",
  "provider": { "allow_fallbacks": true },
  "temperature": 0.3,
  "max_tokens": 2048,
  "timeout_seconds": 30,
  "system_prompt": "..."
}
```

**Keine Secrets** in dieser Datei — nur Modell- und Routing-Parameter.

## Setup

1. Key erstellen: https://openrouter.ai/keys
2. In `.env` (Projekt-Root) eintragen:
   ```
   OPENROUTER_API_KEY=sk-or-v1-...
   ```
3. Prüfen: `npm run ai:check`

## Verwendung in VS Code

### Terminal (CLI)

```powershell
# Einfache Frage
npm run ai -- "Wie viele r sind in strawberry?"

# Datei als Kontext
node scripts/ai.cjs --file pages/company/index.vue "Fasse diese Komponente zusammen"

# Mehrere Dateien
node scripts/ai.cjs --file api/index.php --file server/utils/auth.ts "Sind die Guards konsistent?"

# Letzte Git-Änderungen erklären lassen
node scripts/ai.cjs --git "Erkläre meine letzten Änderungen"

# Letzte 3 Commits
node scripts/ai.cjs --git 3 "Fasse die Änderungen zusammen"

# Strukturierte JSON-Antwort
node scripts/ai.cjs --json "Liste alle API-Endpunkte als JSON-Array"

# Aus stdin (Pipe)
Get-Content fehler.log | node scripts/ai.cjs "Analysiere diesen Fehler"

# Konfiguration prüfen (ohne API-Call)
npm run ai:check
```

### VS Code Tasks (`Strg+Shift+P` → "Tasks: Run Task")

> **Hinweis:** `.vscode/` ist in `.gitignore` — die Tasks sind lokal. Bei Bedarf
> `Strg+Shift+P` → "Tasks: Configure Task" und die Einträge aus der Tabelle anlegen.

| Task | Beschreibung |
|---|---|
| **AI: Frage stellen** | Prompt-Dialog, dann Antwort im Terminal |
| **AI: Aktuelle Datei analysieren** | Schickt die gerade offene Datei mit |
| **AI: Letzte Git-Änderungen erklären** | Commit + Diff als Kontext |
| **AI: Konfiguration prüfen** | Modell, Provider, Key-Status |
| **Code-Index aktualisieren** | `generate_index.py --stats` |
| **PHP-Syntax prüfen** | `check-php-syntax.py` |

### CLI-Optionen

| Option | Wirkung |
|---|---|
| `--file <pfad>` | Datei als Kontext (mehrfach möglich, max 200 KB) |
| `--git [n]` | Letzte n Commits + Diff (Standard: 1) |
| `--system <text>` | System-Prompt überschreiben |
| `--model <id>` | Modell überschreiben |
| `--json` | Erwartet reines JSON, formatiert die Ausgabe |
| `--raw` | Kein Streaming, nur Rohtext |
| `--quiet` | Keine Statusmeldungen |
| `--check` | Nur Konfiguration/Key prüfen |

## Produktions-Endpunkte (PHP)

### `POST /api/ai/chat`

Erfordert Auth (`Bearer`-Token).

```json
{
  "prompt": "Fasse das Projekt zusammen",
  "messages": [{ "role": "user", "content": "..." }],
  "system": "Optionaler System-Prompt",
  "json": false,
  "model": "optional override",
  "temperature": 0.3,
  "max_tokens": 4096
}
```

Antwort:
```json
{
  "success": true,
  "text": "...",
  "model": "google/gemini-2.5-flash",
  "usage": { "prompt_tokens": 42, "completion_tokens": 128, "total_tokens": 170 }
}
```

### `GET /api/ai/config`

Liefert die aktive Konfiguration **ohne Key** (nur `key_configured: true/false`).
Nützlich für Debug und Transparenz.

## Implementierungsdetails

### Node (`scripts/ai.cjs`)
- Streaming via `fetch` + `ReadableStream` (Reasoning-Tokens aus dem letzten Chunk).
- `.env`-Parser ohne externe Abhängigkeit; echte Umgebungsvariablen haben Vorrang.
- Klare Fehlermeldungen je HTTP-Status (401/402/404/429) inkl. Hinweis.
- Timeout via `AbortController`.

### PHP (`api/index.php`)
- `getAiConfig()` liest `ai.config.json` (Fallback auf Defaults).
- `getEnvValue()` liest `.env` (Fallback, `getenv()` hat Vorrang).
- `callOpenRouter()` via cURL mit Timeout + Connect-Timeout.
- `provider`-Block wird 1:1 aus der Config übernommen → Endpoint-Pinning greift identisch.

## Verifikation

| Test | Ergebnis |
|---|---|
| `npm run ai:check` | Konfiguration korrekt gelesen ✅ |
| CLI ohne Prompt | Klare Fehlermeldung ✅ |
| CLI mit ungültigem Key | `OpenRouter 401` + Hinweis "API-Key ungültig" ✅ |
| Provider-Pinning | `provider: baidu/fp8 \| fallbacks: false` im Log ✅ |
| PHP-Syntax | Klammern ausgeglichen ✅ |
| `.env` in Git | Korrekt ignoriert (`git check-ignore` bestätigt) ✅ |

**Offen:** Live-Aufruf mit echtem Key (Key wird vom Nutzer nachgetragen).

## Sicherheit

- API-Key **nur** in `.env` (gitignored) — nie in `ai.config.json`, nie im Client.
- `GET /api/ai/config` gibt den Key **nicht** aus, nur `key_configured`.
- `POST /api/ai/chat` erfordert Authentifizierung.
- Historie auf 20 Nachrichten begrenzt (Kosten-/Missbrauchsschutz).
- Datei-Kontext im CLI auf 200 KB begrenzt, Git-Diff auf 60 KB.

## Deployment

1. `python generate_index.py` — Index aktualisieren.
2. `python scripts/check-php-syntax.py` — PHP validieren.
3. `npm run build:dist` — Build + Sync ins Git-Root.
4. Git Commit & Push; Server: `git pull origin main`.
5. **Auf dem Server** `OPENROUTER_API_KEY` in `.env` eintragen (nicht im Repo!).

#!/usr/bin/env node
/**
 * ai.cjs — Taskster AI-CLI (OpenRouter / DeepSeek V4 Flash)
 * =========================================================
 * Direkter Aufruf aus dem VS-Code-Terminal. Nutzt exakt die Konfiguration aus
 * `ai.config.json` und pinnt den Baidu-Qianfan-Endpoint (`baidu/fp8`) via
 * `provider.only` + `allow_fallbacks: false`.
 *
 * Der API-Key wird NUR aus der Umgebung gelesen und nie geloggt oder committet.
 *
 * Aufrufe:
 *   node scripts/ai.cjs "Wie viele r sind in strawberry?"
 *   node scripts/ai.cjs --file pages/company/index.vue "Fasse diese Komponente zusammen"
 *   node scripts/ai.cjs --git "Erklaere meine letzten Aenderungen"
 *   node scripts/ai.cjs --json "Antworte nur mit JSON"
 *   echo "Frage" | node scripts/ai.cjs
 *   node scripts/ai.cjs --check      # Konfiguration & Key pruefen (ohne API-Call)
 *
 * Optionen:
 *   --file <pfad>      Dateiinhalt als Kontext mitschicken (mehrfach moeglich)
 *   --git [n]          Letzte n Commits + Diff als Kontext (Standard: 1)
 *   --system <text>    System-Prompt ueberschreiben
 *   --model <id>       Modell ueberschreiben
 *   --json             Erwartet reines JSON, gibt es formatiert aus
 *   --raw              Keine Streaming-Ausgabe, nur Rohtext
 *   --quiet            Unterdrueckt Statusmeldungen auf stderr
 *   --check            Nur Konfiguration/Key pruefen
 */

'use strict'

const fs = require('fs')
const path = require('path')
const { spawnSync } = require('child_process')

const ROOT = path.resolve(__dirname, '..')
const CONFIG_PATH = path.join(ROOT, 'ai.config.json')
const OPENROUTER_URL = 'https://openrouter.ai/api/v1/chat/completions'

// ---------------------------------------------------------------------------
// Hilfsfunktionen
// ---------------------------------------------------------------------------

function log(...args) {
  if (!process.argv.includes('--quiet')) console.error(...args)
}

function fail(message, code = 1) {
  console.error(`\x1b[31m✖ ${message}\x1b[0m`)
  process.exit(code)
}

/**
 * Liest die .env-Datei aus dem Projekt-Root (ohne externe Abhaengigkeit).
 * Bereits gesetzte Umgebungsvariablen haben Vorrang.
 */
function loadDotEnv() {
  const envPath = path.join(ROOT, '.env')
  if (!fs.existsSync(envPath)) return {}
  const result = {}
  const content = fs.readFileSync(envPath, 'utf8')
  for (const rawLine of content.split(/\r?\n/)) {
    const line = rawLine.trim()
    if (!line || line.startsWith('#')) continue
    const eq = line.indexOf('=')
    if (eq === -1) continue
    const key = line.slice(0, eq).trim()
    let value = line.slice(eq + 1).trim()
    // Umschliessende Anfuehrungszeichen entfernen
    if ((value.startsWith('"') && value.endsWith('"')) || (value.startsWith("'") && value.endsWith("'"))) {
      value = value.slice(1, -1)
    }
    result[key] = value
  }
  return result
}

function readConfig() {
  if (!fs.existsSync(CONFIG_PATH)) {
    fail(`ai.config.json nicht gefunden: ${CONFIG_PATH}`)
  }
  try {
    return JSON.parse(fs.readFileSync(CONFIG_PATH, 'utf8'))
  } catch (err) {
    fail(`ai.config.json ist kein gueltiges JSON: ${err.message}`)
  }
}

function getApiKey(dotenv) {
  const key = process.env.OPENROUTER_API_KEY || dotenv.OPENROUTER_API_KEY
  if (!key) {
    fail(
      'OPENROUTER_API_KEY fehlt.\n' +
      '  Lege eine .env im Projekt-Root an mit:\n' +
      '    OPENROUTER_API_KEY=sk-or-v1-...\n' +
      '  (.env ist bereits in .gitignore und wird nicht committet.)'
    )
  }
  return key
}

// ---------------------------------------------------------------------------
// Argument-Parsing
// ---------------------------------------------------------------------------

function parseArgs(argv) {
  const opts = {
    promptParts: [],
    files: [],
    git: null,
    system: null,
    model: null,
    json: false,
    raw: false,
    check: false
  }

  for (let i = 0; i < argv.length; i++) {
    const arg = argv[i]
    if (arg === '--file') {
      const next = argv[++i]
      if (!next) fail('--file benoetigt einen Pfad')
      opts.files.push(next)
    } else if (arg === '--git') {
      const next = argv[i + 1]
      if (next && /^\d+$/.test(next)) {
        opts.git = parseInt(next, 10)
        i++
      } else {
        opts.git = 1
      }
    } else if (arg === '--system') {
      opts.system = argv[++i] || null
    } else if (arg === '--model') {
      opts.model = argv[++i] || null
    } else if (arg === '--json') {
      opts.json = true
    } else if (arg === '--raw') {
      opts.raw = true
    } else if (arg === '--check') {
      opts.check = true
    } else if (arg === '--quiet') {
      // wird in log() ausgewertet
    } else {
      opts.promptParts.push(arg)
    }
  }

  return opts
}

function readStdin() {
  if (process.stdin.isTTY) return ''
  try {
    return fs.readFileSync(0, 'utf8').trim()
  } catch {
    return ''
  }
}

function readFileContext(files) {
  const parts = []
  for (const rel of files) {
    const abs = path.isAbsolute(rel) ? rel : path.join(ROOT, rel)
    if (!fs.existsSync(abs)) {
      log(`\x1b[33m! Datei nicht gefunden, wird uebersprungen: ${rel}\x1b[0m`)
      continue
    }
    const stat = fs.statSync(abs)
    if (stat.size > 200 * 1024) {
      log(`\x1b[33m! Datei zu gross (>200 KB), wird uebersprungen: ${rel}\x1b[0m`)
      continue
    }
    const content = fs.readFileSync(abs, 'utf8')
    const ext = path.extname(abs).replace('.', '') || 'text'
    parts.push(`### Datei: ${rel}\n\`\`\`${ext}\n${content}\n\`\`\``)
  }
  return parts.join('\n\n')
}

function readGitContext(count) {
  const run = (args) => {
    const res = spawnSync('git', args, { cwd: ROOT, encoding: 'utf8' })
    if (res.status !== 0) return null
    return res.stdout.trim()
  }

  const logOut = run(['log', `-${count}`, '--stat', '--oneline'])
  const diffOut = run(['diff', 'HEAD~' + count, 'HEAD'])
  if (!logOut) return null

  let ctx = `### Letzte ${count} Commit(s)\n\`\`\`\n${logOut}\n\`\`\``
  if (diffOut) {
    const limited = diffOut.length > 60000
      ? diffOut.slice(0, 60000) + '\n... (Diff gekuerzt)'
      : diffOut
    ctx += `\n\n### Diff\n\`\`\`diff\n${limited}\n\`\`\``
  }
  return ctx
}

// ---------------------------------------------------------------------------
// API-Aufruf (Streaming)
// ---------------------------------------------------------------------------

async function streamChat({ apiKey, model, messages, config, onDelta }) {
  const timeoutMs = (config.timeout_seconds || 120) * 1000
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeoutMs)

  let response
  try {
    response = await fetch(OPENROUTER_URL, {
      method: 'POST',
      signal: controller.signal,
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
        // OpenRouter-Attribution (optional, aber empfohlen)
        'HTTP-Referer': 'https://taskster.ch',
        'X-Title': 'Taskster AI CLI'
      },
      body: JSON.stringify({
        model,
        messages,
        stream: true,
        temperature: config.temperature ?? 0.3,
        max_tokens: config.max_tokens ?? 4096,
        provider: config.provider
      })
    })
  } catch (err) {
    clearTimeout(timer)
    if (err.name === 'AbortError') fail(`Timeout nach ${config.timeout_seconds}s`)
    fail(`Netzwerkfehler: ${err.message}`)
  }

  if (!response.ok) {
    clearTimeout(timer)
    const body = await response.text().catch(() => '')
    let hint = ''
    if (response.status === 401) hint = '\n  → API-Key ungueltig oder abgelaufen.'
    if (response.status === 402) hint = '\n  → Kein Guthaben auf dem OpenRouter-Konto.'
    if (response.status === 404) hint = '\n  → Modell/Endpoint nicht verfuegbar.'
    if (response.status === 429) hint = '\n  → Rate-Limit erreicht. Kurz warten.'
    fail(`OpenRouter ${response.status} ${response.statusText}${hint}\n${body.slice(0, 600)}`)
  }

  const reader = response.body.getReader()
  const decoder = new TextDecoder()
  let buffer = ''
  let full = ''
  let usage = null

  try {
    while (true) {
      const { done, value } = await reader.read()
      if (done) break

      buffer += decoder.decode(value, { stream: true })
      const lines = buffer.split('\n')
      buffer = lines.pop() || ''

      for (const line of lines) {
        const trimmed = line.trim()
        if (!trimmed || !trimmed.startsWith('data:')) continue
        const payload = trimmed.slice(5).trim()
        if (payload === '[DONE]') continue

        let parsed
        try { parsed = JSON.parse(payload) } catch { continue }

        if (parsed.error) {
          fail(`Upstream-Fehler: ${parsed.error.message || JSON.stringify(parsed.error)}`)
        }

        const delta = parsed.choices?.[0]?.delta?.content
        if (delta) {
          full += delta
          if (onDelta) onDelta(delta)
        }
        if (parsed.usage) usage = parsed.usage
      }
    }
  } finally {
    clearTimeout(timer)
  }

  return { text: full, usage }
}

// ---------------------------------------------------------------------------
// Main
// ---------------------------------------------------------------------------

async function main() {
  const opts = parseArgs(process.argv.slice(2))
  const dotenv = loadDotEnv()
  const config = readConfig()

  // --- Check-Modus -------------------------------------------------------
  if (opts.check) {
    const key = process.env.OPENROUTER_API_KEY || dotenv.OPENROUTER_API_KEY
    console.log('Konfiguration:')
    console.log('  Modell          :', config.model)
    console.log('  Provider.only   :', JSON.stringify(config.provider?.only))
    console.log('  Allow fallbacks :', config.provider?.allow_fallbacks)
    console.log('  Temperature     :', config.temperature)
    console.log('  Max Tokens      :', config.max_tokens)
    console.log('  Timeout         :', config.timeout_seconds + 's')
    console.log('  API-Key         :', key ? `gesetzt (${key.slice(0, 8)}...${key.slice(-4)})` : '\x1b[31mFEHLT\x1b[0m')
    process.exit(key ? 0 : 1)
  }

  // --- Prompt zusammenbauen ---------------------------------------------
  let prompt = opts.promptParts.join(' ').trim()
  if (!prompt) {
    prompt = readStdin()
  }
  if (!prompt) {
    fail('Kein Prompt uebergeben. Nutzung: node scripts/ai.cjs "Deine Frage"')
  }

  const contextParts = []
  if (opts.files.length) {
    const ctx = readFileContext(opts.files)
    if (ctx) contextParts.push(ctx)
  }
  if (opts.git !== null) {
    const ctx = readGitContext(opts.git)
    if (ctx) contextParts.push(ctx)
  }

  const userContent = contextParts.length
    ? `${contextParts.join('\n\n')}\n\n### Aufgabe\n${prompt}`
    : prompt

  if (opts.json) {
    prompt += '\n\nAntworte AUSSCHLIESSLICH mit gueltigem JSON, ohne Markdown-Codeblock und ohne Erklaerung.'
  }

  const messages = [
    { role: 'system', content: opts.system || config.system_prompt },
    { role: 'user', content: opts.json ? `${userContent}\n\nAntworte AUSSCHLIESSLICH mit gueltigem JSON, ohne Markdown-Codeblock und ohne Erklaerung.` : userContent }
  ]

  const model = opts.model || config.model
  log(`\x1b[36m→ ${model}\x1b[0m \x1b[2m(provider: ${(config.provider?.only || []).join(', ')} | fallbacks: ${config.provider?.allow_fallbacks})\x1b[0m`)

  // --- Aufruf -----------------------------------------------------------
  const useStream = !opts.raw && process.stdout.isTTY
  const start = Date.now()

  const result = await streamChat({
    apiKey: getApiKey(dotenv),
    model,
    messages,
    config,
    onDelta: useStream ? (d) => process.stdout.write(d) : null
  })

  if (useStream) {
    process.stdout.write('\n')
  } else if (opts.json) {
    // JSON-Ausgabe robust formatieren (falls Modell Codeblock liefert)
    let text = result.text.trim()
    text = text.replace(/^```(?:json)?\s*/i, '').replace(/```$/, '').trim()
    try {
      console.log(JSON.stringify(JSON.parse(text), null, 2))
    } catch {
      console.log(text)
    }
  } else {
    console.log(result.text)
  }

  // --- Usage ------------------------------------------------------------
  const elapsed = ((Date.now() - start) / 1000).toFixed(1)
  const u = result.usage
  if (u) {
    const reasoning = u.completion_tokens_details?.reasoning_tokens
    log(
      `\x1b[2m⏱  ${elapsed}s | prompt: ${u.prompt_tokens ?? '?'} | completion: ${u.completion_tokens ?? '?'}` +
      (reasoning ? ` | reasoning: ${reasoning}` : '') +
      ` | total: ${u.total_tokens ?? '?'}\x1b[0m`
    )
  } else {
    log(`\x1b[2m⏱  ${elapsed}s\x1b[0m`)
  }
}

main().catch((err) => fail(err.stack || String(err)))

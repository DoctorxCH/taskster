import { readFileSync, existsSync } from 'fs'
import { join } from 'path'
import { requireAuth } from '~/server/utils/auth'

function getAiConfig() {
  const configPath = join(process.cwd(), 'ai.config.json')
  if (existsSync(configPath)) {
    try {
      return JSON.parse(readFileSync(configPath, 'utf8'))
    } catch (_) {}
  }
  return {
    model: 'deepseek/deepseek-v4-flash-0731',
    provider: { only: ['baidu/fp8'], allow_fallbacks: false },
    temperature: 0.3,
    max_tokens: 4096,
    timeout_seconds: 120,
    system_prompt: 'Du bist ein praeziser technischer Assistent fuer das Taskster-Projekt.'
  }
}

function getApiKey(): string {
  if (process.env.OPENROUTER_API_KEY) return process.env.OPENROUTER_API_KEY
  const candidates = [
    join(process.cwd(), '.env'),
    join(process.cwd(), '..', '.env'),
    join(__dirname, '..', '..', '..', '.env')
  ]
  for (const envPath of candidates) {
    if (existsSync(envPath)) {
      const content = readFileSync(envPath, 'utf8')
      const match = content.match(/^OPENROUTER_API_KEY\s*=\s*(.+)$/m)
      if (match) {
        return match[1].trim().replace(/^["']|["']$/g, '').trim()
      }
    }
  }
  return ''
}

export default defineEventHandler(async (event) => {
  const user = requireAuth(event)
  const body = await readBody(event)

  const prompt = (body.prompt || '').trim()
  const history = Array.isArray(body.messages) ? body.messages : []
  const systemOverride = body.system ? String(body.system).trim() : null
  const jsonMode = Boolean(body.json)

  if (!prompt && history.length === 0) {
    throw createError({ statusCode: 400, statusMessage: 'Prompt erforderlich' })
  }

  const apiKey = getApiKey()
  if (!apiKey) {
    throw createError({ statusCode: 500, statusMessage: 'OPENROUTER_API_KEY fehlt in .env' })
  }

  const config = getAiConfig()
  const messages: any[] = [
    {
      role: 'system',
      content: systemOverride || config.system_prompt
    }
  ]

  for (const msg of history.slice(-20)) {
    if (['user', 'assistant'].includes(msg.role) && msg.content) {
      messages.push({ role: msg.role, content: String(msg.content).trim() })
    }
  }

  let userContent = prompt
  if (jsonMode) {
    userContent += '\n\nAntworte AUSSCHLIESSLICH mit gueltigem JSON, ohne Markdown-Codeblock und ohne Erklaerung.'
  }
  messages.push({ role: 'user', content: userContent })

  const timeoutMs = (config.timeout_seconds || 120) * 1000
  const controller = new AbortController()
  const timer = setTimeout(() => controller.abort(), timeoutMs)

  try {
    const res = await fetch('https://openrouter.ai/api/v1/chat/completions', {
      method: 'POST',
      signal: controller.signal,
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
        'HTTP-Referer': 'https://taskster.ch',
        'X-Title': 'Taskster AI'
      },
      body: JSON.stringify({
        model: body.model || config.model,
        messages,
        temperature: typeof body.temperature === 'number' ? body.temperature : config.temperature,
        max_tokens: typeof body.max_tokens === 'number' ? body.max_tokens : config.max_tokens,
        provider: config.provider
      })
    })

    clearTimeout(timer)

    if (!res.ok) {
      const errText = await res.text().catch(() => '')
      throw createError({
        statusCode: res.status >= 400 && res.status < 600 ? res.status : 502,
        statusMessage: `OpenRouter ${res.status}: ${errText.slice(0, 300)}`
      })
    }

    const data = await res.json()
    let text = data.choices?.[0]?.message?.content || ''
    if (jsonMode) {
      text = text.replace(/^```(?:json)?\s*/i, '').replace(/```$/, '').trim()
    }

    return {
      success: true,
      text,
      model: data.model,
      usage: data.usage
    }
  } catch (err: any) {
    clearTimeout(timer)
    if (err.name === 'AbortError') {
      throw createError({ statusCode: 504, statusMessage: `Timeout nach ${config.timeout_seconds}s` })
    }
    throw err
  }
})

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
    model: 'google/gemini-2.5-flash',
    audio_model: 'openai/whisper-large-v3-turbo',
    temperature: 0.3,
    max_tokens: 2048,
    timeout_seconds: 60
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
  requireAuth(event)
  const body = await readBody(event)

  // Audio input: base64 audio data URI or raw base64 string
  let audioBase64 = (body.audio || body.audioBase64 || body.file || '').trim()
  const mimeType = body.mimeType || 'audio/webm'
  const targetModel = body.model || getAiConfig().audio_model || 'openai/whisper-large-v3-turbo'

  if (!audioBase64) {
    throw createError({ statusCode: 400, statusMessage: 'Audio-Daten erforderlich (base64)' })
  }

  // Strip data:audio/...;base64, prefix if present
  if (audioBase64.includes('base64,')) {
    audioBase64 = audioBase64.split('base64,')[1]
  }

  const apiKey = getApiKey()
  if (!apiKey) {
    throw createError({ statusCode: 500, statusMessage: 'OPENROUTER_API_KEY fehlt in .env' })
  }

  const audioBuffer = Buffer.from(audioBase64, 'base64')
  const config = getAiConfig()
  const timeoutMs = (config.timeout_seconds || 60) * 1000

  // 1. Try OpenRouter /api/v1/audio/transcriptions (OpenAI-compatible)
  try {
    const formData = new FormData()
    const blob = new Blob([audioBuffer], { type: mimeType })
    formData.append('file', blob, `recording.${mimeType.split('/')[1] || 'webm'}`)
    formData.append('model', targetModel)

    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), timeoutMs)

    const res = await fetch('https://openrouter.ai/api/v1/audio/transcriptions', {
      method: 'POST',
      signal: controller.signal,
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'HTTP-Referer': 'https://taskster.ch',
        'X-Title': 'Taskster Voice Transcription'
      },
      body: formData
    })

    clearTimeout(timer)

    if (res.ok) {
      const data = await res.json()
      const text = data.text || data.transcription || ''
      if (text.trim()) {
        return {
          success: true,
          text: text.trim(),
          model: targetModel
        }
      }
    }
  } catch (e) {
    console.warn('[Whisper] Standard audio/transcriptions endpoint failed, trying chat fallback:', e)
  }

  // 2. Fallback: Multimodal OpenRouter Chat Completion with base64 audio URI
  try {
    const dataUri = `data:${mimeType};base64,${audioBase64}`
    const controller = new AbortController()
    const timer = setTimeout(() => controller.abort(), timeoutMs)

    const res = await fetch('https://openrouter.ai/api/v1/chat/completions', {
      method: 'POST',
      signal: controller.signal,
      headers: {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
        'HTTP-Referer': 'https://taskster.ch',
        'X-Title': 'Taskster Voice Transcription'
      },
      body: JSON.stringify({
        model: targetModel,
        messages: [
          {
            role: 'system',
            content: 'Du bist ein präziser Transkriptions-Assistent. Transkribiere die gesprochene Audionachricht Wort für Wort auf Deutsch. Gib AUSSCHLIESSLICH den gesprochenen Text zurück, ohne Kommentare, Höflichkeitsfloskeln oder Anführungszeichen.'
          },
          {
            role: 'user',
            content: [
              {
                type: 'input_audio',
                input_audio: {
                  data: audioBase64,
                  format: mimeType.includes('wav') ? 'wav' : (mimeType.includes('mp3') ? 'mp3' : 'webm')
                }
              },
              {
                type: 'text',
                text: 'Bitte transkribiere diese Audionachricht präzise.'
              }
            ]
          }
        ],
        temperature: 0.1,
        max_tokens: 2048
      })
    })

    clearTimeout(timer)

    if (!res.ok) {
      const errText = await res.text().catch(() => '')
      throw createError({
        statusCode: res.status >= 400 && res.status < 600 ? res.status : 502,
        statusMessage: `OpenRouter Whisper ${res.status}: ${errText.slice(0, 300)}`
      })
    }

    const data = await res.json()
    const text = (data.choices?.[0]?.message?.content || '').trim()

    return {
      success: true,
      text: text || 'Kein gesprochener Text erkannt.',
      model: targetModel
    }
  } catch (err: any) {
    if (err.statusCode) throw err
    throw createError({
      statusCode: 500,
      statusMessage: `Sprachtranskription fehlgeschlagen: ${err.message || String(err)}`
    })
  }
})

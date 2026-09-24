/**
 * Taskster Email Parser Utility
 * Robust RFC 2047 MIME header decoding and Quoted-Printable / Base64 body parsing.
 * Supports UTF-8, ISO-8859-1 (Latin-1), Windows-1252, and European charsets.
 */

export function normalizeCharset(charset: string): string {
  const cs = (charset || '').trim().toLowerCase()
  if (cs.includes('utf-8') || cs.includes('utf8')) return 'utf-8'
  if (cs.includes('1252') || cs.includes('cp1252')) return 'windows-1252'
  if (cs.includes('8859-15') || cs.includes('latin9')) return 'iso-8859-15'
  if (cs.includes('8859-1') || cs.includes('latin1')) return 'iso-8859-1'
  if (cs.includes('1250') || cs.includes('cp1250')) return 'windows-1250'
  if (cs.includes('8859-2') || cs.includes('latin2')) return 'iso-8859-2'
  if (cs.includes('ascii')) return 'us-ascii'
  return cs || ''
}

export function safeDecodeBytes(bytes: Uint8Array, preferredCharset?: string): string {
  const norm = normalizeCharset(preferredCharset || '')
  if (norm && norm !== 'utf-8') {
    try {
      return new TextDecoder(norm, { fatal: false }).decode(bytes)
    } catch (_) {}
  }
  // Try UTF-8 first (strict to detect single-byte encodings like Latin-1 / Windows-1252)
  try {
    return new TextDecoder('utf-8', { fatal: true }).decode(bytes)
  } catch (_) {
    // If invalid UTF-8 byte sequences are present, fall back to windows-1252 (superset of ISO-8859-1)
    try {
      return new TextDecoder('windows-1252', { fatal: false }).decode(bytes)
    } catch {
      return new TextDecoder('utf-8', { fatal: false }).decode(bytes)
    }
  }
}

export function decodeQEncodedBytes(text: string): Uint8Array {
  const bytes: number[] = []
  for (let i = 0; i < text.length; i++) {
    const ch = text[i]
    if (ch === '_') {
      bytes.push(0x20) // In RFC 2047 Q-encoding, underscore = space
    } else if (ch === '=' && i + 2 < text.length && /^[0-9A-Fa-f]{2}$/.test(text.substring(i + 1, i + 3))) {
      bytes.push(parseInt(text.substring(i + 1, i + 3), 16))
      i += 2
    } else {
      bytes.push(ch.charCodeAt(0) & 0xff)
    }
  }
  return new Uint8Array(bytes)
}

export function decodeBase64ToBytes(base64Str: string): Uint8Array {
  const clean = (base64Str || '').replace(/\s+/g, '')
  if (typeof Buffer !== 'undefined') {
    return Buffer.from(clean, 'base64')
  }
  const binary = atob(clean)
  const bytes = new Uint8Array(binary.length)
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i)
  }
  return bytes
}

/**
 * Recovers raw/loose Quoted-Printable residues (e.g. '=FC' -> 'ü', '=DF' -> 'ß', and '_' underscores between words)
 * when a header or text was partially unpacked or unadorned.
 */
export function cleanResidualQp(text: string): string {
  if (!text) return ''
  if (/=[0-9A-Fa-f]{2}/.test(text)) {
    let processed = text
    if (/[a-zA-Z0-9]_+[a-zA-Z0-9]/.test(processed) && /=[0-9A-Fa-f]{2}/.test(processed)) {
      processed = processed.replace(/_/g, ' ')
    }
    const bytes: number[] = []
    for (let i = 0; i < processed.length; i++) {
      const ch = processed[i]
      if (ch === '=' && i + 2 < processed.length && /^[0-9A-Fa-f]{2}$/.test(processed.substring(i + 1, i + 3))) {
        bytes.push(parseInt(processed.substring(i + 1, i + 3), 16))
        i += 2
      } else {
        bytes.push(ch.charCodeAt(0) & 0xff)
      }
    }
    return safeDecodeBytes(new Uint8Array(bytes))
  }
  return text
}

export function decodeMimeHeader(str: string): string {
  if (!str) return ''
  // 1. Unfold linebreaks (RFC 822/2822 header unfolding)
  let cleaned = str.replace(/\r?\n\s+/g, ' ')
  // 2. Ignore linear whitespace between adjacent MIME encoded-words (RFC 2047 Section 6.2)
  cleaned = cleaned.replace(/\?=\s+=\?/g, '?==?')

  // 3. Decode RFC 2047 encoded-words: =?charset?encoding?encoded-text?=
  const decoded = cleaned.replace(/=\?([^?]+)\?([BQ])\?([^?]+)\?=/gi, (_, charset, encoding, text) => {
    try {
      const enc = encoding.toUpperCase()
      if (enc === 'B') {
        const bytes = decodeBase64ToBytes(text)
        return safeDecodeBytes(bytes, charset)
      } else if (enc === 'Q') {
        const bytes = decodeQEncodedBytes(text)
        return safeDecodeBytes(bytes, charset)
      }
    } catch (_) {}
    return text
  })

  // 4. Catch loose QP escapes and underscore word separators if present
  return cleanResidualQp(decoded)
}

export function decodeQuotedPrintable(str: string, charset: string = 'utf-8'): string {
  if (!str) return ''
  // Remove soft linebreaks
  const clean = str.replace(/=(?:\r?\n|$)/g, '')
  const bytes: number[] = []
  for (let i = 0; i < clean.length; i++) {
    const ch = clean[i]
    if (ch === '=' && i + 2 < clean.length && /^[0-9A-Fa-f]{2}$/.test(clean.substring(i + 1, i + 3))) {
      bytes.push(parseInt(clean.substring(i + 1, i + 3), 16))
      i += 2
    } else {
      bytes.push(ch.charCodeAt(0) & 0xff)
    }
  }
  return safeDecodeBytes(new Uint8Array(bytes), charset)
}

export function decodeBase64Utf8(base64Str: string, charset: string = 'utf-8'): string {
  try {
    const bytes = decodeBase64ToBytes(base64Str)
    return safeDecodeBytes(bytes, charset)
  } catch (_) {
    return base64Str
  }
}

export function parseRawEml(rawText: string): { subject: string; fromName: string; fromEmail: string; body: string } {
  let subject = ''
  let fromEmail = ''
  let fromName = ''
  let body = ''

  // 1. Subject header
  const subMatch = rawText.match(/^Subject:\s*(.+?)(?=\r?\n[^\s]|$)/im)
  if (subMatch && subMatch[1]) {
    subject = decodeMimeHeader(subMatch[1].replace(/\r?\n\s+/g, ' ').trim())
  }

  // 2. From header
  const fromMatch = rawText.match(/^From:\s*(.+?)(?=\r?\n[^\s]|$)/im)
  if (fromMatch && fromMatch[1]) {
    const rawFrom = decodeMimeHeader(fromMatch[1].replace(/\r?\n\s+/g, ' ').trim())
    const emailMatch = rawFrom.match(/<([^>]+)>/)
    if (emailMatch) {
      fromEmail = emailMatch[1].trim()
      fromName = rawFrom.replace(/<[^>]+>/, '').replace(/["']/g, '').trim()
    } else {
      fromEmail = rawFrom
      fromName = rawFrom.split('@')[0]
    }
  }

  // 3. Multipart boundary extraction
  const boundaryMatch = rawText.match(/boundary=["']?([^"';\r\n]+)["']?/i)
  if (boundaryMatch) {
    const boundary = boundaryMatch[1].trim()
    const parts = rawText.split(new RegExp(`--${boundary.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}(?:--)?`))
    let plainTextPart = ''
    let htmlPart = ''

    for (const part of parts) {
      const trimmedPart = part.trim()
      if (!trimmedPart || trimmedPart === '--') continue

      const headerSplit = trimmedPart.split(/\r?\n\r?\n/)
      const partHeaders = headerSplit[0] || ''
      const partBody = headerSplit.slice(1).join('\n\n') || ''

      const isTextPlain = /Content-Type:\s*text\/plain/i.test(partHeaders)
      const isTextHtml = /Content-Type:\s*text\/html/i.test(partHeaders)
      const isBase64 = /Content-Transfer-Encoding:\s*base64/i.test(partHeaders)
      const isQP = /Content-Transfer-Encoding:\s*quoted-printable/i.test(partHeaders)

      let partCharset = 'utf-8'
      const charsetMatch = partHeaders.match(/charset=["']?([^"';\r\n]+)["']?/i)
      if (charsetMatch && charsetMatch[1]) {
        partCharset = charsetMatch[1]
      }

      let decodedBody = partBody
      if (isBase64) {
        decodedBody = decodeBase64Utf8(partBody, partCharset)
      } else if (isQP) {
        decodedBody = decodeQuotedPrintable(partBody, partCharset)
      } else {
        decodedBody = cleanResidualQp(partBody)
      }

      if (isTextPlain && !plainTextPart) {
        plainTextPart = decodedBody.trim()
      } else if (isTextHtml && !htmlPart) {
        htmlPart = decodedBody.trim()
      }
    }

    if (plainTextPart) {
      body = plainTextPart
    } else if (htmlPart) {
      body = htmlPart
        .replace(/<style[^>]*>[\s\S]*?<\/style>/gi, '')
        .replace(/<script[^>]*>[\s\S]*?<\/script>/gi, '')
        .replace(/<br\s*[\/]?>/gi, '\n')
        .replace(/<\/p>/gi, '\n\n')
        .replace(/<[^>]+>/g, '')
        .replace(/&nbsp;/g, ' ')
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&auml;/gi, 'ä')
        .replace(/&ouml;/gi, 'ö')
        .replace(/&uuml;/gi, 'ü')
        .replace(/&Auml;/gi, 'Ä')
        .replace(/&Ouml;/gi, 'Ö')
        .replace(/&Uuml;/gi, 'Ü')
        .replace(/&szlig;/gi, 'ß')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'")
        .trim()
    }
  }

  // 4. Single-part fallback
  if (!body) {
    let mainCharset = 'utf-8'
    const charsetMatch = rawText.match(/charset=["']?([^"';\r\n]+)["']?/i)
    if (charsetMatch && charsetMatch[1]) {
      mainCharset = charsetMatch[1]
    }
    const isBase64 = /Content-Transfer-Encoding:\s*base64/i.test(rawText)
    const isQP = /Content-Transfer-Encoding:\s*quoted-printable/i.test(rawText)
    const split = rawText.split(/\r?\n\r?\n/)
    if (split.length > 1) {
      const potentialBody = split.slice(1).join('\n\n').trim()
      if (isBase64) {
        body = decodeBase64Utf8(potentialBody, mainCharset)
      } else if (isQP) {
        body = decodeQuotedPrintable(potentialBody, mainCharset)
      } else {
        body = cleanResidualQp(potentialBody)
      }
    } else {
      body = isBase64
        ? decodeBase64Utf8(rawText, mainCharset)
        : (isQP ? decodeQuotedPrintable(rawText, mainCharset) : cleanResidualQp(rawText.trim()))
    }
  }

  // 5. Clean MIME boundaries and header artifacts
  body = body
    .replace(/^--[a-zA-Z0-9_-]+[^\n]*\n?/gm, '')
    .replace(/^Content-(?:Type|Transfer-Encoding|Disposition):[^\n]*\n?/gim, '')
    .trim()

  return { subject, fromName, fromEmail, body }
}

/**
 * Reads a File/Blob as text with automatic charset detection (supporting ISO-8859-1 / Windows-1252 / UTF-8)
 */
export async function readFileAsText(file: Blob): Promise<string> {
  if (typeof file.arrayBuffer === 'function') {
    try {
      const buffer = await file.arrayBuffer()
      const bytes = new Uint8Array(buffer)
      let detectedCharset: string | undefined
      const maxSample = Math.min(bytes.length, 2048)
      let sampleAscii = ''
      for (let i = 0; i < maxSample; i++) {
        sampleAscii += String.fromCharCode(bytes[i] & 0x7f)
      }
      const csMatch = sampleAscii.match(/charset=["']?([^"';\r\n]+)["']?/i)
      if (csMatch && csMatch[1]) {
        detectedCharset = csMatch[1]
      }
      return safeDecodeBytes(bytes, detectedCharset)
    } catch (_) {}
  }
  return file.text()
}

export function readFileAsDataUrl(file: any): Promise<string> {
  return new Promise((resolve, reject) => {
    if (typeof FileReader === 'undefined') {
      return reject(new Error('FileReader is not supported in this environment'))
    }
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result as string)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

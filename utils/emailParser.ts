export function decodeMimeHeader(str: string): string {
  if (!str) return ''
  return str.replace(/=\?([^?]+)\?([BQ])\?([^?]+)\?=/gi, (_, charset, encoding, text) => {
    try {
      if (encoding.toUpperCase() === 'B') {
        return atob(text.replace(/\s+/g, ''))
      } else if (encoding.toUpperCase() === 'Q') {
        const cleaned = text.replace(/_/g, ' ').replace(/=([0-9A-F]{2})/gi, (__, hex) => String.fromCharCode(parseInt(hex, 16)))
        return decodeURIComponent(escape(cleaned))
      }
    } catch (_) {}
    return text
  })
}

export function decodeQuotedPrintable(str: string): string {
  if (!str) return ''
  const clean = str.replace(/=\r?\n/g, '')
  try {
    return decodeURIComponent(clean.replace(/=([0-9A-F]{2})/gi, (_, hex) => '%' + hex))
  } catch (_) {
    return clean.replace(/=([0-9A-F]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16)))
  }
}

export function decodeBase64Utf8(base64Str: string): string {
  try {
    const clean = base64Str.replace(/\s+/g, '')
    return decodeURIComponent(escape(atob(clean)))
  } catch (_) {
    try {
      return atob(base64Str.replace(/\s+/g, ''))
    } catch (_) {
      return base64Str
    }
  }
}

export function parseRawEml(rawText: string): { subject: string; fromName: string; fromEmail: string; body: string } {
  let subject = ''
  let fromEmail = ''
  let fromName = ''
  let body = ''

  const subMatch = rawText.match(/^Subject:\s*(.+?)(?=\r?\n[^\s]|$)/im)
  if (subMatch && subMatch[1]) {
    subject = decodeMimeHeader(subMatch[1].replace(/\r?\n\s+/g, ' ').trim())
  }

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

      let decodedBody = partBody
      if (isBase64) {
        decodedBody = decodeBase64Utf8(partBody)
      } else if (isQP) {
        decodedBody = decodeQuotedPrintable(partBody)
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
        .trim()
    }
  }

  if (!body) {
    const isBase64 = /Content-Transfer-Encoding:\s*base64/i.test(rawText)
    const isQP = /Content-Transfer-Encoding:\s*quoted-printable/i.test(rawText)
    const split = rawText.split(/\r?\n\r?\n/)
    if (split.length > 1) {
      const potentialBody = split.slice(1).join('\n\n').trim()
      if (isBase64) {
        body = decodeBase64Utf8(potentialBody)
      } else if (isQP) {
        body = decodeQuotedPrintable(potentialBody)
      } else {
        body = potentialBody
      }
    } else {
      body = isBase64 ? decodeBase64Utf8(rawText) : rawText.trim()
    }
  }

  body = body
    .replace(/^--[a-zA-Z0-9_-]+[^\n]*\n?/gm, '')
    .replace(/^Content-(?:Type|Transfer-Encoding|Disposition):[^\n]*\n?/gim, '')
    .trim()

  return { subject, fromName, fromEmail, body }
}

export function readFileAsDataUrl(file: File): Promise<string> {
  return new Promise((resolve, reject) => {
    const reader = new FileReader()
    reader.onload = () => resolve(reader.result as string)
    reader.onerror = reject
    reader.readAsDataURL(file)
  })
}

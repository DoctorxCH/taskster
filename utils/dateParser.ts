/**
 * Taskster — Datums-Parsing & Normalisierung für CSV-/Excel-Importe
 *
 * Konvertiert Eingabewerte in ein standardisiertes 'YYYY-MM-DD' Datumsformat.
 * Erkennt und konvertiert insbesondere:
 * - Excel Serial Date (numerische Tage seit 1899-12-30, z. B. 46272 -> 2026-09-07)
 * - DD.MM.YYYY / D.M.YYYY (Schweizer / Deutsches Format)
 * - DD/MM/YYYY
 * - ISO-Strings (YYYY-MM-DD oder vollständige ISO-Timestamps)
 */
export function parseImportDate(value: string | number | Date | null | undefined): string | null {
  if (value === null || value === undefined) return null

  if (value instanceof Date) {
    return !isNaN(value.getTime()) ? value.toISOString().split('T')[0] : null
  }

  const strVal = String(value).trim()
  if (!strVal) return null

  // 1. Numerischer Excel-Serial-Wert (Tage seit 1899-12-30)
  // 25569 = 1970-01-01 (Unix-Epoche), 60000 = ca. Mai 2064
  const num = Number(strVal)
  if (!isNaN(num) && num > 25569 && num < 60000) {
    const days = Math.floor(num)
    const date = new Date(Math.round((days - 25569) * 86400 * 1000))
    if (!isNaN(date.getTime())) {
      return date.toISOString().split('T')[0]
    }
  }

  // 2. Format DD.MM.YYYY (oder D.M.YYYY)
  if (/^\d{1,2}\.\d{1,2}\.\d{4}$/.test(strVal)) {
    const parts = strVal.split('.')
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
  }

  // 3. Format DD/MM/YYYY
  if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(strVal)) {
    const parts = strVal.split('/')
    return `${parts[2]}-${parts[1].padStart(2, '0')}-${parts[0].padStart(2, '0')}`
  }

  // 4. Bereits im Format YYYY-MM-DD
  if (/^\d{4}-\d{2}-\d{2}$/.test(strVal)) {
    return strVal
  }

  // 5. ISO-Format mit Zeitstempel (z. B. 2026-09-07T14:30:00Z)
  if (/^\d{4}-\d{2}-\d{2}[T\s]/.test(strVal)) {
    const parsed = Date.parse(strVal)
    if (!isNaN(parsed)) {
      try {
        return new Date(parsed).toISOString().split('T')[0]
      } catch {
        return strVal.substring(0, 10)
      }
    }
    return strVal.substring(0, 10)
  }

  return strVal
}

/**
 * Taskster Intelligent Multi-Criteria Project Matching Engine
 * Accurately scores and identifies projects based on Auftragsnummer / Kundenreferenz,
 * Service ID (SID), Street, House Number, Postal Code, City, and Project Titles.
 */

export interface ProjectMatchCandidate {
  id: string
  title: string
  folder_id?: string
  custom_data?: any
}

export interface ProjectMatchResult {
  project: ProjectMatchCandidate
  score: number
  matchedCriteria: string[]
}

export function matchProjectByText(
  projects: ProjectMatchCandidate[],
  text: string
): ProjectMatchResult | null {
  if (!projects || projects.length === 0 || !text || !text.trim()) {
    return null
  }

  const normalizedText = text.toLowerCase()

  // 1. Extract potential numbers (6 to 10 digits) e.g. 100312101 or 0100312101
  const numberTokens = (text.match(/\b\d{6,10}\b/g) || []).map(n => n.replace(/^0+/, ''))

  // 2. Extract potential SIDs e.g. SID007000GPHCBA
  const sidTokens = (text.match(/\bSID[0-9A-Z]{4,}\b/gi) || []).map(s => s.toUpperCase())

  // 3. Extract Swiss postal code + city e.g. 6037 Root, 6030 Ebikon
  const zipCityTokens = (text.match(/\b(?:CH-)?([1-9]\d{3})\s+([A-Za-zÄÖÜäöüß]+)\b/g) || []).map(zc => zc.toLowerCase())

  let bestCandidate: ProjectMatchResult | null = null

  for (const prj of projects) {
    let score = 0
    const matchedCriteria: string[] = []
    const pTitle = (prj.title || '').trim().toLowerCase()
    const pTitleNormalized = pTitle.replace(/^0+/, '')

    // Parse custom data
    let cd: Record<string, any> = {}
    if (prj.custom_data) {
      try {
        cd = typeof prj.custom_data === 'string' ? JSON.parse(prj.custom_data) : prj.custom_data
      } catch (_) {}
    }

    // --- CRITERION 1: Auftragsnummer / Kundenreferenz / Order Number (Score: 100) ---
    const rawRef = String(cd.kundenreferenz || cd.kundenreferenz_oder_projekt_id || cd.order_number || cd.auftragsnummer || cd.auftrag_id || '').trim()
    const cleanRef = rawRef.replace(/^0+/, '')

    for (const num of numberTokens) {
      if (cleanRef && (cleanRef === num || cleanRef.includes(num))) {
        score += 100
        matchedCriteria.push(`Auftragsnummer (${rawRef})`)
        break
      }
      if (pTitleNormalized.includes(num)) {
        score += 100
        matchedCriteria.push(`Projektnummer im Titel (${num})`)
        break
      }
    }

    // --- CRITERION 2: Service-ID (SID) (Score: 100) ---
    const pSid = String(cd.sid || cd.service_id || cd.leitungs_id || '').toUpperCase().trim()
    for (const sid of sidTokens) {
      if (pSid && (pSid === sid || pSid.includes(sid))) {
        score += 100
        matchedCriteria.push(`Service-ID (${sid})`)
        break
      }
      if (pTitle.toUpperCase().includes(sid)) {
        score += 100
        matchedCriteria.push(`Service-ID im Titel (${sid})`)
        break
      }
    }

    // --- CRITERION 3: Strasse & Hausnummer (Score: 80) ---
    const rawStrasse = String(cd.strasse || cd.street || '').trim().toLowerCase()
    if (rawStrasse && rawStrasse.length >= 4 && normalizedText.includes(rawStrasse)) {
      score += 80
      matchedCriteria.push(`Strasse (${cd.strasse})`)
    }

    const rawAdresse = String(cd.adresse || cd.address || '').trim().toLowerCase()
    if (rawAdresse && rawAdresse.length >= 5 && normalizedText.includes(rawAdresse)) {
      score += 70
      matchedCriteria.push(`Adresse (${cd.adresse})`)
    }

    // --- CRITERION 4: Ortschaft / PLZ (Low weight: 10 points only) ---
    // A city alone (like "Root") must NEVER decide a project by itself!
    const rawOrt = String(cd.ort || cd.city || '').trim().toLowerCase()
    if (rawOrt && rawOrt.length >= 3 && normalizedText.includes(rawOrt)) {
      // Only award city points if it's a specific match
      score += 10
      matchedCriteria.push(`Ort (${cd.ort})`)
    }

    for (const zc of zipCityTokens) {
      if (rawAdresse.includes(zc) || pTitle.includes(zc)) {
        score += 25
        matchedCriteria.push(`PLZ/Ort (${zc})`)
        break
      }
    }

    // --- CRITERION 5: Projekttitel Substring / Words (Score: 30 - 60) ---
    if (pTitle) {
      // Exclude pure order prefix like "0100312101 - "
      const cleanTitleName = pTitle.replace(/^[\d\s\-_.]+/, '').trim()
      if (cleanTitleName.length >= 4 && normalizedText.includes(cleanTitleName)) {
        score += 60
        matchedCriteria.push(`Projektname (${cleanTitleName})`)
      } else {
        // Individual specific words (length >= 5, not common stop words)
        const words = cleanTitleName.split(/[\s\-_,./]+/).filter(w => w.length >= 5 && !['root', 'ebikon', 'luzern', 'zuerich', 'bern', 'ftth', 'ftts', 'order'].includes(w))
        let wordHits = 0
        for (const w of words) {
          if (normalizedText.includes(w)) {
            wordHits++
          }
        }
        if (wordHits >= 1) {
          score += wordHits * 25
          matchedCriteria.push(`Titel-Stichwort (${wordHits} Treffer)`)
        }
      }
    }

    // Update best candidate
    if (!bestCandidate || score > bestCandidate.score) {
      bestCandidate = {
        project: prj,
        score,
        matchedCriteria
      }
    }
  }

  // Minimum threshold: score must be >= 50 (e.g. requires at least an Auftragsnummer, SID, or Street match)
  // A simple city match (score 10) is rejected as insufficient!
  if (bestCandidate && bestCandidate.score >= 50) {
    return bestCandidate
  }

  return null
}

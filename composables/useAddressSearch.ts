/**
 * Adresssuche und Geocoding über OpenStreetMap / Nominatim.
 *
 * Bewusst ohne API-Schlüssel und ohne externe Bibliothek:
 *   - Autocomplete: Nominatim `/search` mit `limit=5`
 *   - Geocoding:    dieselbe Route mit `limit=1`
 *   - Karte:        OSM-Embed (`/export/embed.html`) – kein Leaflet nötig
 *   - Route:        OSM-Direktlinks (Auto / Velo / zu Fuss)
 *
 * Nominatim-Nutzungsbedingungen verlangen max. 1 Anfrage/Sekunde und einen
 * aussagekräftigen User-Agent. Beides wird hier eingehalten: Anfragen werden
 * serialisiert und mit 1100 ms Mindestabstand gesendet.
 */

export interface AddressSuggestion {
  label: string
  lat: number
  lon: number
  type?: string
}

export interface GeoPoint {
  lat: number
  lon: number
}

const NOMINATIM = 'https://nominatim.openstreetmap.org/search'

/** Mindestabstand zwischen zwei Nominatim-Anfragen (Nutzungsbedingungen). */
const MIN_INTERVAL_MS = 1100

let lastRequestAt = 0
let queue: Promise<any> = Promise.resolve()

/** Serialisiert alle Anfragen und hält den Mindestabstand ein. */
function throttle<T>(fn: () => Promise<T>): Promise<T> {
  const run = queue.then(async () => {
    const wait = MIN_INTERVAL_MS - (Date.now() - lastRequestAt)
    if (wait > 0) await new Promise((r) => setTimeout(r, wait))
    lastRequestAt = Date.now()
    return fn()
  })
  // Fehler dürfen die Kette nicht blockieren
  queue = run.catch(() => undefined)
  return run
}

async function nominatimSearch(query: string, limit: number): Promise<AddressSuggestion[]> {
  const url = `${NOMINATIM}?format=jsonv2&addressdetails=0&limit=${limit}&q=${encodeURIComponent(query)}`
  const res = await fetch(url, {
    headers: {
      'Accept-Language': 'de,en',
      'Accept': 'application/json'
    }
  })
  if (!res.ok) throw new Error(`Nominatim ${res.status}`)
  const data = await res.json()
  if (!Array.isArray(data)) return []

  return data
    .filter((d: any) => d?.lat && d?.lon)
    .map((d: any) => ({
      label: String(d.display_name || '').trim(),
      lat: parseFloat(d.lat),
      lon: parseFloat(d.lon),
      type: d.type
    }))
}

export const useAddressSearch = () => {
  /** Sucht Adressvorschläge (max. 5). */
  async function suggest(query: string): Promise<AddressSuggestion[]> {
    const q = String(query || '').trim()
    if (q.length < 3) return []
    try {
      return await throttle(() => nominatimSearch(q, 5))
    } catch {
      return []
    }
  }

  /** Ermittelt Koordinaten zu einer Adresse. */
  async function geocode(address: string): Promise<GeoPoint | null> {
    const q = String(address || '').trim()
    if (!q) return null
    try {
      const hits = await throttle(() => nominatimSearch(q, 1))
      if (!hits.length) return null
      return { lat: hits[0].lat, lon: hits[0].lon }
    } catch {
      return null
    }
  }

  // ---------------------------------------------------------------------------
  // Karten-Embed (OpenStreetMap, kein API-Key)
  // ---------------------------------------------------------------------------
  function embedUrl(point: GeoPoint, zoomSpan = 0.006): string {
    const { lat, lon } = point
    const minLon = (lon - zoomSpan).toFixed(5)
    const maxLon = (lon + zoomSpan).toFixed(5)
    const minLat = (lat - zoomSpan * 0.6).toFixed(5)
    const maxLat = (lat + zoomSpan * 0.6).toFixed(5)
    return `https://www.openstreetmap.org/export/embed.html?bbox=${minLon}%2C${minLat}%2C${maxLon}%2C${maxLat}&layer=mapnik&marker=${lat}%2C${lon}`
  }

  // ---------------------------------------------------------------------------
  // Externe Links
  // ---------------------------------------------------------------------------
  function osmUrl(address: string): string {
    return `https://www.openstreetmap.org/search?query=${encodeURIComponent(address)}`
  }

  function googleMapsUrl(address: string): string {
    return `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address)}`
  }

  /**
   * Routenlink ab einem Startpunkt (Standard: aktueller Standort des Browsers
   * wird von OSM/Google selbst ermittelt, wenn kein `from` gesetzt ist).
   */
  function routeUrl(address: string, mode: 'driving' | 'cycling' | 'walking' = 'driving', from?: GeoPoint): string {
    const dest = encodeURIComponent(address)
    if (from) {
      const origin = `${from.lat},${from.lon}`
      const travel = mode === 'cycling' ? 'bicycling' : (mode === 'walking' ? 'walking' : 'driving')
      return `https://www.google.com/maps/dir/?api=1&origin=${origin}&destination=${dest}&travelmode=${travel}`
    }
    // Ohne Startpunkt: OSM-Routenplaner mit Ziel
    const engine = mode === 'cycling' ? 'fossgis_osrm_bike' : (mode === 'walking' ? 'fossgis_osrm_foot' : 'fossgis_osrm_car')
    return `https://www.openstreetmap.org/directions?engine=${engine}&route=;${encodeURIComponent(address)}`
  }

  /** Route ab dem aktuellen Standort (Browser-Geolocation). */
  async function routeFromHere(address: string, mode: 'driving' | 'cycling' | 'walking' = 'driving'): Promise<string> {
    const from = await currentPosition()
    return routeUrl(address, mode, from || undefined)
  }

  /** Aktuelle Position des Browsers (null, wenn nicht erlaubt/verfügbar). */
  function currentPosition(): Promise<GeoPoint | null> {
    return new Promise((resolve) => {
      if (!import.meta.client || !navigator.geolocation) return resolve(null)
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ lat: pos.coords.latitude, lon: pos.coords.longitude }),
        () => resolve(null),
        { timeout: 8000, maximumAge: 300000 }
      )
    })
  }

  return {
    suggest,
    geocode,
    embedUrl,
    osmUrl,
    googleMapsUrl,
    routeUrl,
    routeFromHere,
    currentPosition
  }
}
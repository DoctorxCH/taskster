# Adress-Autocomplete, Karten und Routenfunktion

**Datum:** 2026-09-20
**Betroffene Schichten:** DB (SQLite + MySQL), Nitro-API, PHP-API, Nuxt-Frontend

---

## 1. Ausgangslage

### Die Karte in den Kontakten war verschwunden

Die Kontaktkarte (OpenStreetMap-Miniaturkarte) wurde in Commit `e197800` eingeführt.
Beim Design-v2-Umbau (`9a4aeca`) wurde der **Template-Block entfernt**, die
zugehörigen Funktionen (`toggleMap`, `resolveCoordinates`, `getOsmEmbedUrl`,
`mapCoordinates`, `openMaps`, `mapLoading`) blieben jedoch als **toter Code** in der
Datei stehen. Deshalb war die Karte nicht mehr sichtbar, obwohl die Logik existierte.

### Kein Adress-Autocomplete

Adressen wurden als reine Textfelder erfasst. Es gab keine Vorschläge, keine
Validierung und keine Koordinaten — die Karte musste die Adresse bei jedem Öffnen
erneut über Nominatim geocodieren.

---

## 2. Datenbank

Neue Spalten (SQLite-Schema, Nitro-Migration, PHP-Migration):

| Tabelle | Spalten |
|---|---|
| `contacts` | `latitude REAL`, `longitude REAL` |
| `calendar_events` | `latitude REAL`, `longitude REAL` |

MySQL-Pendant: `DECIMAL(10,7)` — ausreichend für ~1 cm Genauigkeit.

**Nutzen:** Einmal geocodierte Adressen werden gespeichert. Die Karte öffnet danach
sofort, ohne externe Anfrage. Das schont auch die Nominatim-Nutzungsbedingungen.

---

## 3. Adressdienst: `composables/useAddressSearch.ts`

Bewusst **ohne API-Schlüssel und ohne externe Bibliothek** (kein Leaflet, kein Google):

| Funktion | Umsetzung |
|---|---|
| `suggest(query)` | Nominatim `/search` mit `limit=5` → Autocomplete |
| `geocode(address)` | Nominatim `/search` mit `limit=1` → Koordinaten |
| `embedUrl(point)` | OSM-Embed (`/export/embed.html`) mit Marker |
| `osmUrl` / `googleMapsUrl` | Direktlinks zur Adresse |
| `routeUrl(address, mode, from)` | Routenlink (Auto / Velo / zu Fuss) |
| `routeFromHere(address, mode)` | Route ab aktuellem Standort (Browser-Geolocation) |

### Nominatim-Nutzungsbedingungen

Nominatim erlaubt **max. 1 Anfrage pro Sekunde**. Das wird strikt eingehalten:

- Alle Anfragen laufen durch eine **Promise-Kette** (`queue`)
- Vor jeder Anfrage wird ein **Mindestabstand von 1100 ms** erzwungen
- `Accept-Language: de,en` für deutsche Ortsnamen

Ohne diese Drosselung hätte Nominatim die Anfragen nach kurzer Zeit blockiert.

---

## 4. Komponente `AddressAutocomplete.vue`

Ersetzt das reine Textfeld in Kontakten und Terminen.

- **Debounce 350 ms** — tippt man weiter, wird die laufende Suche verworfen
- **Sequenznummer** (`requestSeq`) verwirft verspätete Antworten
- **Tastatursteuerung**: ↑ ↓ zur Auswahl, Enter übernimmt, Esc schliesst
- **Adresse geändert → Koordinaten werden geleert**, damit nie eine alte Position
  zu einer neuen Adresse gespeichert wird
- **Enter ohne Auswahl** geocodiert die Eingabe direkt
- **Altdaten**: Beim Öffnen eines Formulars mit Adresse, aber ohne Koordinaten,
  werden diese einmalig nachgeladen
- Statuszeile „Standort erkannt" mit Link zur Kartenprüfung
- Fallback „Keine Adresse gefunden" mit OSM-Suchlink

---

## 5. Kontakte

### Karte wiederhergestellt

Der Template-Block ist zurück — jetzt im Design v2 (`rounded-md`, `border-slate-200`,
`Loader2` statt Emoji-Spinner) und mit erweiterten Aktionen:

**Karte anzeigen** · **Google Maps** · **OpenStreetMap** · **Route**

Die alte, duplizierte Geocoding-Logik wurde durch `useAddressSearch()` ersetzt.
`ensureCoordinates()` nutzt zuerst die **gespeicherten Koordinaten** und geocodiert
nur, wenn keine vorhanden sind.

### Autocomplete

Das Feld „Geschäftsadresse" nutzt jetzt `AddressAutocomplete`. Die Koordinaten
werden über `v-model:latitude` / `v-model:longitude` gebunden und mitgespeichert.

---

## 6. Termine

### Im Termin-Modal

- Ort-Feld ist jetzt `AddressAutocomplete`
- Darunter **Karte anzeigen** · **Google Maps** · **OpenStreetMap** · **Route**
- Karte lädt erst auf Klick (kein unnötiger externer Aufruf beim Öffnen)

### In der Termin-Übersicht (Tagesansicht)

Termine mit Ort zeigen dieselben Aktionen direkt in der Liste — Karte, Google Maps,
OpenStreetMap und Route. So sieht man den Treffpunkt, ohne das Bearbeiten-Modal zu
öffnen.

---

## 7. Nebenbei behobener Fehler

`GET /api/contacts` lieferte **500**:

```
no such column: "company" - should this be a string literal in single-quotes?
```

In `server/api/contacts/index.get.ts` wurden zwei SQL-Vergleiche mit **doppelten**
Anführungszeichen geschrieben (`= "company"`). SQLite interpretiert das als
**Spaltennamen**, nicht als String-Literal. Behoben durch einfache Anführungszeichen.

**Das betraf die gesamte Kontaktliste** — sie war vor dieser Änderung nicht ladbar.

---

## 8. Getestete Szenarien (Browser, Dev-Server + SQLite)

- ✅ Kontaktliste lädt wieder (200 statt 500)
- ✅ Karten-Buttons sichtbar: Karte, Route, Google Maps, OpenStreetMap
- ✅ Autocomplete: „Bahnhofstrasse 1, 8001 Zürich" → 2 echte OSM-Vorschläge
- ✅ Auswahl setzt Adresse + Koordinaten, Statuszeile „Standort erkannt"
- ✅ Speichern: `lat 47.3672965`, `lon 8.5398712` in der DB
- ✅ Karte lädt mit Marker (`marker=47.3672965,8.53…`)
- ✅ Termin-Modal: Autocomplete, Karte im Modal, Koordinaten gespeichert
- ✅ Termin-Tagesansicht: Karte + Route + beide Kartendienste
- ✅ Keine Vue-Warnungen mehr (`Loader2` fehlte in den Kontakt-Imports)

---

## 9. Deployment

1. `npm run build:dist`
2. `git add -A; git commit; git push origin main`
3. Auf dem Server: `git pull origin main`
4. **Wichtig:** vier neue Spalten → einmalig `node scripts/migrate-mysql.cjs`

Kein `npm install` / `npm run build` auf dem Server (Shared Hosting ohne C-Compiler).

---

## 10. Hinweis zur Kartenqualität

Die Karten stammen von **OpenStreetMap** und benötigen keinen API-Schlüssel und
kein Abrechnungskonto. Für Baustellenadressen in der Schweiz ist die Abdeckung gut.
Falls später Satellitenbilder oder Verkehrsdaten gewünscht sind, wäre ein
Google-Maps- oder Mapbox-Schlüssel nötig — die Struktur (`useAddressSearch`) ist
so gebaut, dass nur die URL-Bauer ausgetauscht werden müssten.
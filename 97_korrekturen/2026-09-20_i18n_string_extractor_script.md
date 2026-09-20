# Tooling: Python Script für i18n String-Extraktion (Mehrsprachigkeit)

**Datum:** 2026-09-20  
**Skripte:** `scripts/extract_i18n.py`, `package.json`

## Zweck
Erkennung und Extraktion aller fest verdrahteten (hardcoded) UI-Texte, Beschriftungen, Tooltips, Dialog-Meldungen (`alert`, `confirm`), Fehlermeldungen und Input-Placeholders aus Vue-Templates und Skripten (`pages/`, `components/`, `layouts/`, `composables/`, `app.vue`).

## Features
- **Robuster HTML/Vue Tokenizer:** Beachtet `>` in JavaScript-Ausdrücken (wie `v-if="count > 0"`).
- **Attribut-Scanning:** Erfasst `placeholder`, `title`, `aria-label`, `label`, `alt` (ignoriert dynamische `:placeholder` etc.).
- **Code-Filterung:** Sortiert CSS-Klassen, Hex-Farbcodes, SVG-Pfade, URLs, API-Routen, Zahlen und Code-Symbole aus.
- **Export Formate:**
  1. `locales/de.json`: Strukturierte Key-Value JSON-Datei, direkt nutzbar für `@nuxtjs/i18n` oder `vue-i18n`.
  2. `i18n_report.md`: Detaillierter Markdown-Report gruppiert nach Datei und Zeilennummer.

## Ausführung
```bash
npm run i18n:extract
# oder
python scripts/extract_i18n.py
python scripts/extract_i18n.py --stats
python scripts/extract_i18n.py -o locales/de.json -r i18n_report.md
```

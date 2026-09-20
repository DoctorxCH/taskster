# Erweiterte Zusatzfeld-Typen (Custom Fields)

**Datum:** 2026-09-20  
**Betroffene Bereiche:** Projektansicht (`pages/projects/[id].vue`), Ordneransicht (`pages/folders/[id].vue`), Unternehmens-Vorlagen (`pages/company/index.vue`), Admin-Vorlagen (`pages/admin/index.vue`)

## Problem / Anforderung
Bisher gab es nur Standard-Zusatzfelder (Textzeile, Zahl, Datum, Auswahlliste). Es fehlten insbesondere Felder für längeren mehrzeiligen Text (Notizen, Beschreibungen, Baudokumentation) sowie spezialisierte Typen wie Checkboxen, URLs (mit Direktlink), E-Mail-Adressen (mit mailto) und Telefonnummern (mit Anruf-Aktion).

## Durchgeführte Änderungen

1. **Unterstützte Feldtypen erweitert auf 9 Typen:**
   - `text`: Kurze Textzeile
   - `textarea`: Längerer mehrzeiliger Text / Notizfeld (unterstützt dynamische Höhenanpassung)
   - `number`: Zahl / Währung / Messwert (mit Dezimalschrittweite)
   - `select`: Auswahlliste (Dropdown) mit dynamischen Optionen
   - `date`: Datumsauswahl
   - `checkbox`: Ja / Nein Schalter (boolesch)
   - `url`: Weblink / URL mit Schnellzugriff-Button zum Öffnen im neuen Tab
   - `email`: E-Mail-Adresse mit Direktlink (`mailto:`)
   - `phone`: Telefonnummer mit Direktanruf (`tel:`)

2. **Integration in UI-Komponenten:**
   - **Task-Drawer (`pages/projects/[id].vue`):**
     - Mehrzeilige Notizfelder (`textarea`) spannen automatisch über mehrere Spalten (`sm:col-span-2 md:col-span-3 lg:col-span-4`), um übersichtlich und ergonomisch bedienbar zu sein.
     - Interaktive Icons für Web-Links, E-Mail-Aktionen und Telefonate direkt am Eingabefeld.
     - Saubere Checkbox-Darstellung mit Statusanzeige (`✓ Ja` / `Nein`).
   - **Task-Modal (Erstellen / Bearbeiten) & Projekt-Settings (`pages/projects/[id].vue`):**
     - Vollständige Unterstützung aller 9 Typen beim Bearbeiten und Anlegen von Aufgaben sowie beim Verwalten von Projekt-Zusatzfeldern.
     - Formatierung von Booleschen Werten auf Task-Karten (Kanban/Liste).
   - **Ordner-Projektanlage (`pages/folders/[id].vue`):**
     - Beim Anlegen neuer Projekte über Ordner-Vorlagen werden alle 9 Typen korrekt gerendert.
   - **Vorlagen-Verwaltung (`pages/company/index.vue` & `pages/admin/index.vue`):**
     - Auswahl aller 9 Typen bei der Definition von Unternehmens- und globalen Feldvorlagen.

3. **Deployment & Kompatibilität:**
   - Keine DB-Schema-Änderung erforderlich, da `field_type` in `folder_field_definitions` bereits `VARCHAR(64)` ist und Werte strukturiert als JSON in `custom_data` persistiert werden.

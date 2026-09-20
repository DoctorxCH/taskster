# Dokumentation: Vollständige Lokalisierung pages/dashboard.vue (DE, EN, SK)

**Datum:** 2026-09-20  
**Betreff:** pages/dashboard.vue i18n Übersetzung vervollständigt  
**Status:** Abgeschlossen  

---

## 1. Problem
Im Dashboard (`pages/dashboard.vue`) waren mehrere UI-Strings, Labels, Placeholders, Tooltips, Modalfelder und Benachrichtigungs-Tabs fest auf Deutsch codiert.

## 2. Durchgeführte Änderungen
1. **Tooltips & Actions:**
   - `:title="$t('dashboard.sprachaufnahme_via_openaiwhisperlar')"`
   - `:title="$t('dashboard.zur_globalen_zeitrapportierung')"`
   - `:title="$t('common.aktualisieren')"`
   - `:title="$t('dashboard.stoppen_buchen')"`
   - `{{ $t('dashboard.stopp') }}`
   - `:title="$t('dashboard.stoppuhr_auf_diese_aufgabe_starten')"`
   - `:title="$t('dashboard.aufgabe_öffnen')"`
   - `:title="$t('dashboard.als_gelesen_markieren')"`
   - `:title="$t('dashboard.projektordner_anpassen_name')"`

2. **Ordner- & Aufgaben-Labels:**
   - `{{ folder.visibility === 'company' ? $t('common.unternehmen') : $t('dashboard.privat') }}`
   - `{{ folder.project_count }} {{ folder.project_count === 1 ? $t('dashboard.projekt') : $t('dashboard.projekte') }}`
   - Status Badge: `{{ $t('dashboard.zerotrust_pipeline') }}` / `{{ $t('common.aktiv') }}`

3. **Benachrichtigungen Widget:**
   - Tabs: `{{ $t('dashboard.alle') }}`, `{{ $t('dashboard.kommentare_einladungen') }}`, `{{ $t('dashboard.fälligkeiten_budget') }}`
   - Links: `{{ $t('dashboard.zur_aufgabe') }}`, `{{ $t('dashboard.zum_projekt') }}`, `{{ $t('dashboard.zum_ordner') }}`

4. **Modals (Neuer Ordner / Ordner bearbeiten):**
   - Titel & Beschreibung: `$t('dashboard.neuen_projektordner_anlegen')`, `$t('dashboard.projektordner_bilden_die_oberste_or')`, `$t('dashboard.projektordner_anpassen')`, `$t('dashboard.passe_den_namen_und_die_sichtbarkei')`
   - Formularfelder & Placeholders: `$t('dashboard.name_des_projektordners')`, `:placeholder="$t('dashboard.zb_ftth_glasfaserausbau_region_nord')"`, `:placeholder="$t('dashboard.zb_privates_renovationsprojekt')"`
   - Sichtbarkeits-Optionen & Beschreibungen: `$t('dashboard.sichtbarkeit_des_ordners')`, `$t('dashboard.privat_standard')`, `$t('common.unternehmen')`, `$t('dashboard.privater_ordner_desc')`, `$t('dashboard.unternehmens_ordner_desc')`, `$t('dashboard.hinweis')`, `$t('dashboard.dieser_ordner_ist_standardmäßig_pri')`, `$t('dashboard.ordner_teilen')`, `$t('dashboard.für_kollegen_freigeben')`
   - Modal Buttons: `$t('common.abbrechen')`, `$t('dashboard.erstelle')` / `$t('dashboard.ordner_erstellen')`, `$t('dashboard.speichern_dot')` / `$t('dashboard.änderungen_speichern')`

5. **Script Error Fallbacks:**
   - `t('dashboard.tagestodo_konnte_nicht_erstellt_wer')`
   - `t('dashboard.ordner_konnte_nicht_erstellt_werden')`
   - `t('dashboard.ordner_konnte_nicht_aktualisiert_we')`
   - `t('dashboard.projekt')`

6. **Locale-Dateien synchronisiert:**
   - `i18n/locales/de.json`
   - `i18n/locales/en.json`
   - `i18n/locales/sk.json`

7. **Build & Indexierung:**
   - `npm run build:dist` erfolgreich ausgeführt (Code 0).
   - `python generate_index.py` aktualisiert.

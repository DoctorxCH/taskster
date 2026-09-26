# 🌍 Taskster i18n Migration – Vollständiges Handout & Checkliste

> **Stand:** 2026-09-26  
> **Gefundene hardcodierte Strings:** 1078  
> **Betroffene Dateien:** 24  
> **Ziel-Sprachen:** Deutsch (de) ✅, Englisch (en) ✅, Slowakisch (sk) 🔧

---

## 📋 Anleitung für jeden Agenten

### Workflow pro Datei:
1. **Datei öffnen** und zur angegebenen Zeile navigieren
2. **Hardcodierten String** durch `$t('key')` bzw. `:placeholder="$t('key')"` ersetzen
3. **Key in `de.json`** eintragen (deutsch)
4. **Key in `en.json`** eintragen (englisch)
5. **Key in `sk.json`** eintragen (slowakisch)
6. **Checkbox abhaken** ✅

### Template-Ersetzungsregeln:
| Vorher | Nachher |
|--------|---------|
| `<span>Mein Text</span>` | `<span>{{ $t("key") }}</span>` |
| `placeholder="Text"` | `:placeholder="$t('key')"` |
| `title="Text"` | `:title="$t('key')"` |
| `label="Text"` | `:label="$t('key')"` |
| `"Hardcoded"` (Script) | `t("key")` (mit `const { t } = useI18n()`) |

### Namenskonvention für Keys:
- Format: `{seite}.{beschreibung}` (z.B. `time.csv_export`, `contacts.neuer_kontakt`)
- Bestehende Prefixes nutzen: `common.*`, `dashboard.*`, `project.*`, `folder.*`, `journal.*` etc.

---

## 📊 Fortschritts-Übersicht

| Phase | Bereich | Dateien | Strings | Status |
|-------|---------|---------|---------|--------|
| Phase 1: Components (wiederverwendbar) | 9 Dateien | 9 | 127 | ⬜ Offen |
| Phase 2: Kern-Seiten (täglicher User-Kontakt) | 10 Dateien | 10 | 226 | ⬜ Offen |
| Phase 3: Projekt- & Ordner-Detailseiten | 2 Dateien | 2 | 334 | ⬜ Offen |
| Phase 4: Admin & Firmen-Verwaltung | 2 Dateien | 2 | 379 | ⬜ Offen |
| Phase 5: Sonstige (app.vue etc.) | 1 Dateien | 1 | 12 | ⬜ Offen |
| **GESAMT** | | **24** | **1078** | |

---

## Phase 1: Components (wiederverwendbar)
*127 Strings in 9 Dateien*

### 📄 `components/AddressAutocomplete.vue` (5 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 1 | ⬜ | L23 | `attr:title` | Adresse leeren | `comp_AddressAutocomplete.adresse_leeren` |
| 2 | ⬜ | L53 | `template_text` | Keine Adresse gefunden. | `comp_AddressAutocomplete.keine_adresse_gefunden` |
| 3 | ⬜ | L59 | `template_text` | Auf OpenStreetMap suchen → | `comp_AddressAutocomplete.auf_openstreetmap_suchen` |
| 4 | ⬜ | L66 | `template_text` | Standort erkannt | `comp_AddressAutocomplete.standort_erkannt` |
| 5 | ⬜ | L72 | `template_text` | auf Karte prüfen | `comp_AddressAutocomplete.auf_karte_prüfen` |

### 📄 `components/CalendarEventModal.vue` (23 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 6 | ⬜ | L19 | `template_text` | Organisiert von | `comp_CalendarEventModal.organisiert_von` |
| 7 | ⬜ | L20 | `template_text` | · Dein Status: | `comp_CalendarEventModal.dein_status` |
| 8 | ⬜ | L39 | `template_text` | Ganztägiger Termin | `comp_CalendarEventModal.ganztägiger_termin` |
| 9 | ⬜ | L69 | `attr:placeholder` | z.B. Baustelle Zürcherstrasse 45 | `comp_CalendarEventModal.zb_baustelle_zürcherstrasse_45` |
| 10 | ⬜ | L88 | `template_text` | Google Maps | `comp_CalendarEventModal.google_maps` |
| 11 | ⬜ | L102 | `attr:title` | Route ab meinem Standort | `comp_CalendarEventModal.route_ab_meinem_standort` |
| 12 | ⬜ | L115 | `template_text` | Karte wird geladen… | `comp_CalendarEventModal.karte_wird_geladen` |
| 13 | ⬜ | L120 | `attr:title` | OpenStreetMap Karte | `comp_CalendarEventModal.openstreetmap_karte` |
| 14 | ⬜ | L124 | `template_text` | Standort konnte nicht ermittelt werden. | `comp_CalendarEventModal.standort_konnte_nicht_ermittelt_werden` |
| 15 | ⬜ | L142 | `template_text` | Priorität | `comp_CalendarEventModal.priorität` |
| 16 | ⬜ | L157 | `template_text` | Projekt (optional) | `comp_CalendarEventModal.projekt_optional` |
| 17 | ⬜ | L165 | `template_text` | Kein Projekt | `comp_CalendarEventModal.kein_projekt` |
| 18 | ⬜ | L175 | `template_text` | Privat (nur ich & Eingeladene) | `comp_CalendarEventModal.privat_nur_ich_eingeladene` |
| 19 | ⬜ | L175 | `template_text` | Für Firma sichtbar | `comp_CalendarEventModal.für_firma_sichtbar` |
| 20 | ⬜ | L183 | `attr:placeholder` | Agenda, Notizen, Anforderungen… | `comp_CalendarEventModal.agenda_notizen_anforderungen` |
| 21 | ⬜ | L195 | `template_text` | Teilnehmer () | `comp_CalendarEventModal.teilnehmer` |
| 22 | ⬜ | L197 | `template_text` | Eingeladene erhalten eine Benachrichtigung | `comp_CalendarEventModal.eingeladene_erhalten_eine_benachrichtigu` |
| 23 | ⬜ | L215 | `attr:placeholder` | E-Mail-Adresse eingeben… | `comp_CalendarEventModal.emailadresse_eingeben` |
| 24 | ⬜ | L230 | `template_text` | Aus dem Team | `comp_CalendarEventModal.aus_dem_team` |
| 25 | ⬜ | L287 | `template_text` | Löschen | `comp_CalendarEventModal.löschen` |
| 26 | ⬜ | L303 | `template_text` | Schließen | `comp_CalendarEventModal.schließen` |
| 27 | ⬜ | L600 | `script_literal` | wirklich löschen? Alle Teilnehmer werden informiert. | `comp_CalendarEventModal.wirklich_löschen_alle_teilnehmer_werden` |
| 28 | ⬜ | L608 | `script_literal` | Termin konnte nicht gelöscht werden | `comp_CalendarEventModal.termin_konnte_nicht_gelöscht_werden` |

### 📄 `components/CommandPalette.vue` (4 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 29 | ⬜ | L32 | `attr:placeholder` | Aufgaben, Projekte, Ordner, Personen durchsuchen… | `comp_CommandPalette.aufgaben_projekte_ordner_personen_durchs` |
| 30 | ⬜ | L76 | `template_text` | Keine Treffer | `comp_CommandPalette.keine_treffer` |
| 31 | ⬜ | L78 | `template_text` | Nichts gefunden für „" | `comp_CommandPalette.nichts_gefunden_für` |
| 32 | ⬜ | L285 | `script_literal` | Prüfung | `comp_CommandPalette.prüfung` |

### 📄 `components/JournalEntryModal.vue` (41 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 33 | ⬜ | L38 | `template_text` | E-Mail / Dokument importieren & mit KI analysieren | `comp_JournalEntryModal.email_dokument_importieren_mit_ki_analys` |
| 34 | ⬜ | L40 | `template_text` | Füge Text ein oder ziehe eine .eml, .msg oder .pdf Datei hinein. | `comp_JournalEntryModal.füge_text_ein_oder_ziehe_eine_eml_msg_od` |
| 35 | ⬜ | L73 | `template_text` | Datei jetzt hier loslassen! | `comp_JournalEntryModal.datei_jetzt_hier_loslassen` |
| 36 | ⬜ | L76 | `template_text` | E-Mail (.msg / .eml) oder PDF wird automatisch ausgelesen & vorstrukturiert | `comp_JournalEntryModal.email_msg_eml_oder_pdf_wird_automatisch` |
| 37 | ⬜ | L87 | `template_text` | .eml, Outlook .msg oder .pdf Datei hierher ziehen | `comp_JournalEntryModal.eml_outlook_msg_oder_pdf_datei_hierher_z` |
| 38 | ⬜ | L90 | `template_text` | Drag & Drop | `comp_JournalEntryModal.drag_drop` |
| 39 | ⬜ | L92 | `template_text` | • Betreff, Absender, Kontakte & Text werden automatisch übernommen | `comp_JournalEntryModal.betreff_absender_kontakte_text_werden_a` |
| 40 | ⬜ | L100 | `template_text` | Geladen: | `comp_JournalEntryModal.geladen` |
| 41 | ⬜ | L104 | `template_text` | Absender Name | `comp_JournalEntryModal.absender_name` |
| 42 | ⬜ | L114 | `template_text` | Absender E-Mail | `comp_JournalEntryModal.absender_email` |
| 43 | ⬜ | L130 | `template_text` | Mit KI analysieren & Aufgaben / Termine synchronisieren | `comp_JournalEntryModal.mit_ki_analysieren_aufgaben_termine_sync` |
| 44 | ⬜ | L132 | `template_text` | Generiert kompakte Zusammenfassung, übernimmt Signatur-Kontaktdaten und schlägt  | `comp_JournalEntryModal.generiert_kompakte_zusammenfassung_übern` |
| 45 | ⬜ | L138 | `template_text` | Ordner wählen | `comp_JournalEntryModal.ordner_wählen` |
| 46 | ⬜ | L144 | `template_text` | Alle Ordner / Ordnerübergreifend | `comp_JournalEntryModal.alle_ordner_ordnerübergreifend` |
| 47 | ⬜ | L151 | `template_text` | Projekt verfügbar | `comp_JournalEntryModal.projekt_verfügbar` |
| 48 | ⬜ | L168 | `template_text` | 📂 Nur Ordner-Journal (Kein spezifisches Projekt) | `comp_JournalEntryModal.nur_ordnerjournal_kein_spezifisches_pro` |
| 49 | ⬜ | L177 | `attr:placeholder` | Projekt suchen (z.B. Name, Adresse, ID)... | `comp_JournalEntryModal.projekt_suchen_zb_name_adresse_id` |
| 50 | ⬜ | L191 | `template_text` | Automatisch zuweisen (anhand Text/Titel) | `comp_JournalEntryModal.automatisch_zuweisen_anhand_texttitel` |
| 51 | ⬜ | L205 | `template_text` | Nur Ordner-Journal (Allgemein) | `comp_JournalEntryModal.nur_ordnerjournal_allgemein` |
| 52 | ⬜ | L211 | `template_text` | Kein Projekt gefunden | `comp_JournalEntryModal.kein_projekt_gefunden` |
| 53 | ⬜ | L232 | `template_text` | Projekt erkannt: | `comp_JournalEntryModal.projekt_erkannt` |
| 54 | ⬜ | L242 | `template_text` | Das System ordnet den Eintrag automatisch dem passenden Projekt zu (z.B. nach Ku | `comp_JournalEntryModal.das_system_ordnet_den_eintrag_automatisc` |
| 55 | ⬜ | L244 | `template_text` | Titel / Betreff | `comp_JournalEntryModal.titel_betreff` |
| 56 | ⬜ | L247 | `attr:placeholder` | z. B. 14. Bausitzung Los 3 oder Bauabnahme Keller... | `comp_JournalEntryModal.z_b_14_bausitzung_los_3_oder_bauabnahme` |
| 57 | ⬜ | L291 | `template_text` | 🌐 Öffentlich (Projektleser) | `comp_JournalEntryModal.öffentlich_projektleser` |
| 58 | ⬜ | L295 | `template_text` | 👥 Nur ausgewählte Gruppe | `comp_JournalEntryModal.nur_ausgewählte_gruppe` |
| 59 | ⬜ | L301 | `template_text` | Berechtigte Gruppe | `comp_JournalEntryModal.berechtigte_gruppe` |
| 60 | ⬜ | L306 | `template_text` | -- Gruppe auswählen -- | `comp_JournalEntryModal.gruppe_auswählen` |
| 61 | ⬜ | L311 | `template_text` | Verknüpfte Aufgabe (optional) | `comp_JournalEntryModal.verknüpfte_aufgabe_optional` |
| 62 | ⬜ | L331 | `template_text` | -- Keine Verknüpfung -- | `comp_JournalEntryModal.keine_verknüpfung` |
| 63 | ⬜ | L346 | `attr:placeholder` | Aufgabe suchen... | `comp_JournalEntryModal.aufgabe_suchen` |
| 64 | ⬜ | L359 | `template_text` | Keine Verknüpfung | `comp_JournalEntryModal.keine_verknüpfung` |
| 65 | ⬜ | L371 | `template_text` | Automatisch zuweisen (anhand Text) | `comp_JournalEntryModal.automatisch_zuweisen_anhand_text` |
| 66 | ⬜ | L374 | `template_text` | Keine Aufgaben gefunden | `comp_JournalEntryModal.keine_aufgaben_gefunden` |
| 67 | ⬜ | L401 | `template_text` | Teilnehmer & Anwesenheit | `comp_JournalEntryModal.teilnehmer_anwesenheit` |
| 68 | ⬜ | L401 | `template_text` | Wähle bestehende Projektkontakte aus oder füge neue Teilnehmer hinzu. | `comp_JournalEntryModal.wähle_bestehende_projektkontakte_aus_ode` |
| 69 | ⬜ | L417 | `template_text` | Kontakt auswählen... () | `comp_JournalEntryModal.kontakt_auswählen` |
| 70 | ⬜ | L473 | `template_text` | Protokolltext / Inhalt | `comp_JournalEntryModal.protokolltext_inhalt` |
| 71 | ⬜ | L479 | `attr:placeholder` | Besprochene Punkte, Beschlüsse, Sachverhalt oder Notizen... | `comp_JournalEntryModal.besprochene_punkte_beschlüsse_sachverhal` |
| 72 | ⬜ | L486 | `template_text` | Dateianhänge (Fotos, Pläne, PDFs) | `comp_JournalEntryModal.dateianhänge_fotos_pläne_pdfs` |
| 73 | ⬜ | L505 | `template_text` | Dateien hier ablegen oder zum Auswählen klicken (max. 10 MB) | `comp_JournalEntryModal.dateien_hier_ablegen_oder_zum_auswählen` |

### 📄 `components/JournalNoteModal.vue` (13 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 74 | ⬜ | L13 | `template_text` | Neuen Journaleintrag erfassen | `comp_JournalNoteModal.neuen_journaleintrag_erfassen` |
| 75 | ⬜ | L15 | `template_text` | Schneller Journaleintrag mit automatischer Projekt- und Aufgabenzuweisung. | `comp_JournalNoteModal.schneller_journaleintrag_mit_automatisch` |
| 76 | ⬜ | L34 | `template_text` | Journaleintrag / Inhalt | `comp_JournalNoteModal.journaleintrag_inhalt` |
| 77 | ⬜ | L40 | `attr:placeholder` | Schreibe deinen Journaleintrag, Feststellung, Mangel oder Notiz hier rein... | `comp_JournalNoteModal.schreibe_deinen_journaleintrag_feststell` |
| 78 | ⬜ | L77 | `attr:placeholder` | Projekt suchen (z.B. Balmstr, Name)... | `comp_JournalNoteModal.projekt_suchen_zb_balmstr_name` |
| 79 | ⬜ | L93 | `template_text` | Automatisch zuweisen (anhand Text) | `comp_JournalNoteModal.automatisch_zuweisen_anhand_text` |
| 80 | ⬜ | L105 | `template_text` | Nur Ordner-Journal (Kein Projekt) | `comp_JournalNoteModal.nur_ordnerjournal_kein_projekt` |
| 81 | ⬜ | L135 | `template_text` | -- Keine Verknüpfung -- | `comp_JournalNoteModal.keine_verknüpfung` |
| 82 | ⬜ | L147 | `attr:placeholder` | Aufgabe suchen... | `comp_JournalNoteModal.aufgabe_suchen` |
| 83 | ⬜ | L161 | `template_text` | Keine Verknüpfung | `comp_JournalNoteModal.keine_verknüpfung` |
| 84 | ⬜ | L182 | `template_text` | Passende Aufgabe erkannt (Klick zum Zuweisen): | `comp_JournalNoteModal.passende_aufgabe_erkannt_klick_zum_zuwei` |
| 85 | ⬜ | L205 | `template_text` | 📝 Notiz | `comp_JournalNoteModal.notiz` |
| 86 | ⬜ | L236 | `template_text` | Eintrag speichern | `comp_JournalNoteModal.eintrag_speichern` |

### 📄 `components/MiniCalendar.vue` (5 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 87 | ⬜ | L7 | `attr:title` | Vorheriger Monat | `comp_MiniCalendar.vorheriger_monat` |
| 88 | ⬜ | L16 | `attr:title` | Kalender öffnen | `comp_MiniCalendar.kalender_öffnen` |
| 89 | ⬜ | L24 | `attr:title` | Nächster Monat | `comp_MiniCalendar.nächster_monat` |
| 90 | ⬜ | L70 | `attr:title` | Auswahl aufheben | `comp_MiniCalendar.auswahl_aufheben` |
| 91 | ⬜ | L109 | `template_text` | – keine Termine | `comp_MiniCalendar.keine_termine` |

### 📄 `components/Navbar.vue` (1 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 92 | ⬜ | L148 | `template_text` | Company Admin | `comp_Navbar.company_admin` |

### 📄 `components/StopwatchModal.vue` (8 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 93 | ⬜ | L13 | `template_text` | Zeiterfassung abschließen | `comp_StopwatchModal.zeiterfassung_abschließen` |
| 94 | ⬜ | L18 | `attr:title` | Weiterlaufen lassen | `comp_StopwatchModal.weiterlaufen_lassen` |
| 95 | ⬜ | L30 | `template_text` | Gemessene Zeit | `comp_StopwatchModal.gemessene_zeit` |
| 96 | ⬜ | L36 | `template_text` | Rapportiert auf | `comp_StopwatchModal.rapportiert_auf` |
| 97 | ⬜ | L44 | `template_text` | Geschätzte Kosten | `comp_StopwatchModal.geschätzte_kosten` |
| 98 | ⬜ | L54 | `template_text` | Tätigkeit / Beschreibung | `comp_StopwatchModal.tätigkeit_beschreibung` |
| 99 | ⬜ | L58 | `attr:placeholder` | Was wurde während dieser Zeit erledigt? | `comp_StopwatchModal.was_wurde_während_dieser_zeit_erledigt` |
| 100 | ⬜ | L126 | `script_literal` | Möchtest du diese Zeitmessung wirklich verwerfen? | `comp_StopwatchModal.möchtest_du_diese_zeitmessung_wirklich_v` |

### 📄 `components/VoiceRecorderModal.vue` (27 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 101 | ⬜ | L15 | `template_text` | Sprachassistent & Notiz | `comp_VoiceRecorderModal.sprachassistent_notiz` |
| 102 | ⬜ | L20 | `template_text` | Sprache: | `comp_VoiceRecorderModal.sprache` |
| 103 | ⬜ | L52 | `template_text` | Sprachaufnahme starten | `comp_VoiceRecorderModal.sprachaufnahme_starten` |
| 104 | ⬜ | L55 | `template_text` | Sprich deine Aufgabe, Notiz oder Statusmeldung ein. Die KI erkennt Aufgabenname, | `comp_VoiceRecorderModal.sprich_deine_aufgabe_notiz_oder_statusme` |
| 105 | ⬜ | L59 | `template_text` | Spracheingabe: | `comp_VoiceRecorderModal.spracheingabe` |
| 106 | ⬜ | L67 | `template_text` | Français | `comp_VoiceRecorderModal.franais` |
| 107 | ⬜ | L81 | `template_text` | Aufnahme starten | `comp_VoiceRecorderModal.aufnahme_starten` |
| 108 | ⬜ | L95 | `template_text` | Aufnahme läuft... () | `comp_VoiceRecorderModal.aufnahme_läuft` |
| 109 | ⬜ | L120 | `template_text` | Stoppen & Analysieren | `comp_VoiceRecorderModal.stoppen_analysieren` |
| 110 | ⬜ | L148 | `template_text` | KI-Erkennung: | `comp_VoiceRecorderModal.kierkennung` |
| 111 | ⬜ | L158 | `template_text` | Zugeordnete Aufgabe | `comp_VoiceRecorderModal.zugeordnete_aufgabe` |
| 112 | ⬜ | L166 | `template_text` | Projekt: | `comp_VoiceRecorderModal.projekt` |
| 113 | ⬜ | L174 | `template_text` | Erkannte Checklisten-Punkte: | `comp_VoiceRecorderModal.erkannte_checklistenpunkte` |
| 114 | ⬜ | L183 | `template_text` | Empfohlene Aktionen (1-Klick): | `comp_VoiceRecorderModal.empfohlene_aktionen_1klick` |
| 115 | ⬜ | L217 | `template_text` | Transkribierter Text (bearbeitbar): | `comp_VoiceRecorderModal.transkribierter_text_bearbeitbar` |
| 116 | ⬜ | L219 | `template_text` | Whisper v3 Turbo | `comp_VoiceRecorderModal.whisper_v3_turbo` |
| 117 | ⬜ | L227 | `attr:placeholder` | Erkannter Text... | `comp_VoiceRecorderModal.erkannter_text` |
| 118 | ⬜ | L232 | `template_text` | Projekt zuordnen (optional) | `comp_VoiceRecorderModal.projekt_zuordnen_optional` |
| 119 | ⬜ | L238 | `template_text` | Kein Projekt (Allgemeine Notiz / Journal) | `comp_VoiceRecorderModal.kein_projekt_allgemeine_notiz_journal` |
| 120 | ⬜ | L265 | `template_text` | Als neue Aufgabe anlegen | `comp_VoiceRecorderModal.als_neue_aufgabe_anlegen` |
| 121 | ⬜ | L283 | `attr:title` | Erneut von KI analysieren lassen | `comp_VoiceRecorderModal.erneut_von_ki_analysieren_lassen` |
| 122 | ⬜ | L287 | `template_text` | Neu analysieren | `comp_VoiceRecorderModal.neu_analysieren` |
| 123 | ⬜ | L493 | `script_literal` | Mikrofonzugriff verweigert oder nicht unterstützt. Bitte erteile Mikrofon-Berech | `comp_VoiceRecorderModal.mikrofonzugriff_verweigert_oder_nicht_un` |
| 124 | ⬜ | L615 | `script_literal` | Aufgabe ergänzen | `comp_VoiceRecorderModal.aufgabe_ergänzen` |
| 125 | ⬜ | L678 | `script_literal` | angehängt! | `comp_VoiceRecorderModal.angehängt` |
| 126 | ⬜ | L726 | `script_literal` | Fehler beim Ausführen der Aktion | `comp_VoiceRecorderModal.fehler_beim_ausführen_der_aktion` |
| 127 | ⬜ | L772 | `script_literal` | Das gewählte Projekt hat noch keine Abschnitte | `comp_VoiceRecorderModal.das_gewählte_projekt_hat_noch_keine_absc` |

---

## Phase 2: Kern-Seiten (täglicher User-Kontakt)
*226 Strings in 10 Dateien*

### 📄 `pages/calendar/index.vue` (7 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 128 | ⬜ | L11 | `template_text` | Termine planen, einladen und mit Projekten verknüpfen | `calendar.termine_planen_einladen_und_mit_projekte` |
| 129 | ⬜ | L32 | `attr:title` | Zurück | `calendar.zurück` |
| 130 | ⬜ | L84 | `attr:title` | Löschen | `calendar.löschen` |
| 131 | ⬜ | L103 | `template_text` | Termine werden geladen… | `calendar.termine_werden_geladen` |
| 132 | ⬜ | L156 | `attr:title` | Termin an diesem Tag | `calendar.termin_an_diesem_tag` |
| 133 | ⬜ | L182 | `attr:title` | Noch nicht beantwortet | `calendar.noch_nicht_beantwortet` |
| 134 | ⬜ | L1354 | `script_literal` | Kategorie konnte nicht gelöscht werden | `calendar.kategorie_konnte_nicht_gelöscht_werden` |

### 📄 `pages/contacts/index.vue` (75 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 135 | ⬜ | L8 | `template_text` | Baustellen- & Projektverzeichnis | `contacts.baustellen_projektverzeichnis` |
| 136 | ⬜ | L10 | `template_text` | Kontakte & Ansprechpartner | `contacts.kontakte_ansprechpartner` |
| 137 | ⬜ | L13 | `template_text` | Verwalte Handwerker, Bauleiter, Planer und Behörden. Geteilte Kontakte stehen de | `contacts.verwalte_handwerker_bauleiter_planer_und` |
| 138 | ⬜ | L25 | `template_text` | Neuer Kontakt | `contacts.neuer_kontakt` |
| 139 | ⬜ | L48 | `template_text` | Firma geteilt | `contacts.firma_geteilt` |
| 140 | ⬜ | L58 | `template_text` | Mit Projektbezug | `contacts.mit_projektbezug` |
| 141 | ⬜ | L68 | `template_text` | Privat / Eigene | `contacts.privat_eigene` |
| 142 | ⬜ | L79 | `attr:placeholder` | Nach Name, Firma, Funktion, Telefon oder E-Mail suchen... | `contacts.nach_name_firma_funktion_telefon_oder_em` |
| 143 | ⬜ | L94 | `template_text` | Alle Gruppen | `contacts.alle_gruppen` |
| 144 | ⬜ | L105 | `template_text` | Alle Projekte | `contacts.alle_projekte` |
| 145 | ⬜ | L116 | `template_text` | Alle Freigaben | `contacts.alle_freigaben` |
| 146 | ⬜ | L116 | `template_text` | Im Unternehmen geteilt | `contacts.im_unternehmen_geteilt` |
| 147 | ⬜ | L116 | `template_text` | Nur Privat / Eigene | `contacts.nur_privat_eigene` |
| 148 | ⬜ | L150 | `template_text` | Kontakte werden geladen... | `contacts.kontakte_werden_geladen` |
| 149 | ⬜ | L158 | `template_text` | Keine Kontakte gefunden | `contacts.keine_kontakte_gefunden` |
| 150 | ⬜ | L164 | `template_text` | Ersten Kontakt anlegen | `contacts.ersten_kontakt_anlegen` |
| 151 | ⬜ | L220 | `attr:title` | Zum Projekt wechseln | `contacts.zum_projekt_wechseln` |
| 152 | ⬜ | L239 | `attr:title` | WhatsApp Chat öffnen | `contacts.whatsapp_chat_öffnen` |
| 153 | ⬜ | L263 | `attr:title` | Karte ein-/ausblenden | `contacts.karte_einausblenden` |
| 154 | ⬜ | L271 | `attr:title` | In Google Maps öffnen | `contacts.in_google_maps_öffnen` |
| 155 | ⬜ | L282 | `template_text` | Karte wird geladen… | `contacts.karte_wird_geladen` |
| 156 | ⬜ | L292 | `attr:title` | OpenStreetMap Karte | `contacts.openstreetmap_karte` |
| 157 | ⬜ | L294 | `template_text` | Keine Kontaktdaten hinterlegt | `contacts.keine_kontaktdaten_hinterlegt` |
| 158 | ⬜ | L310 | `attr:title` | Als digitale Visitenkarte (.vcf) herunterladen | `contacts.als_digitale_visitenkarte_vcf_herunterla` |
| 159 | ⬜ | L324 | `attr:title` | Kontakt bearbeiten | `contacts.kontakt_bearbeiten` |
| 160 | ⬜ | L331 | `attr:title` | Kontakt löschen | `contacts.kontakt_löschen` |
| 161 | ⬜ | L345 | `template_text` | Name / Firma | `contacts.name_firma` |
| 162 | ⬜ | L346 | `template_text` | Telefon & Mobile | `contacts.telefon_mobile` |
| 163 | ⬜ | L387 | `attr:title` | Auf OpenStreetMap anzeigen | `contacts.auf_openstreetmap_anzeigen` |
| 164 | ⬜ | L460 | `attr:title` | Löschen | `contacts.löschen` |
| 165 | ⬜ | L488 | `template_text` | Erfasse alle Kontaktdaten für die Baustelle oder das Projekt. | `contacts.erfasse_alle_kontaktdaten_für_die_bauste` |
| 166 | ⬜ | L508 | `template_text` | KI-Autofill Assistent | `contacts.kiautofill_assistent` |
| 167 | ⬜ | L522 | `template_text` | Füge eine E-Mail-Signatur, Notizen von der Baustelle oder rohen Text ein. Die KI | `contacts.füge_eine_emailsignatur_notizen_von_der` |
| 168 | ⬜ | L526 | `attr:placeholder` | Beispiel: Hans Peter, Bauleiter bei Steiner Tiefbau AG in Zürich, Tel 044 123 45 | `contacts.beispiel_hans_peter_bauleiter_bei_steine` |
| 169 | ⬜ | L539 | `template_text` | Texte werden sicher verarbeitet | `contacts.texte_werden_sicher_verarbeitet` |
| 170 | ⬜ | L557 | `template_text` | Duplikat-Schutz: Ähnlicher Kontakt existiert bereits | `contacts.duplikatschutz_ähnlicher_kontakt_existie` |
| 171 | ⬜ | L561 | `template_text` | Bereits vorhanden | `contacts.bereits_vorhanden` |
| 172 | ⬜ | L567 | `template_text` | Ein Kontakt mit ähnlichen Merkmalen (Name, E-Mail oder Telefon) existiert bereit | `contacts.ein_kontakt_mit_ähnlichen_merkmalen_name` |
| 173 | ⬜ | L586 | `template_text` | ✏️ Bestehenden Kontakt bearbeiten | `contacts.bestehenden_kontakt_bearbeiten` |
| 174 | ⬜ | L591 | `template_text` | ⚡ Daten zusammenführen (Merge) | `contacts.daten_zusammenführen_merge` |
| 175 | ⬜ | L597 | `template_text` | Trotzdem neu anlegen | `contacts.trotzdem_neu_anlegen` |
| 176 | ⬜ | L602 | `template_text` | Nachname / Name | `contacts.nachname_name` |
| 177 | ⬜ | L605 | `attr:placeholder` | z.B. Müller | `contacts.zb_müller` |
| 178 | ⬜ | L628 | `template_text` | Firma / Organisation | `contacts.firma_organisation` |
| 179 | ⬜ | L639 | `template_text` | Funktion / Gewerk | `contacts.funktion_gewerk` |
| 180 | ⬜ | L652 | `template_text` | Mobiltelefon (Handy) | `contacts.mobiltelefon_handy` |
| 181 | ⬜ | L660 | `template_text` | Festnetz / Telefon | `contacts.festnetz_telefon` |
| 182 | ⬜ | L681 | `template_text` | Geschäftsadresse | `contacts.geschäftsadresse` |
| 183 | ⬜ | L684 | `attr:placeholder` | z.B. Flurstrasse 30, 8048 Zürich | `contacts.zb_flurstrasse_30_8048_zürich` |
| 184 | ⬜ | L701 | `template_text` | Projektzugehörigkeit (optional) | `contacts.projektzugehörigkeit_optional` |
| 185 | ⬜ | L709 | `template_text` | -- Keine Projektzuordnung -- | `contacts.keine_projektzuordnung` |
| 186 | ⬜ | L715 | `template_text` | Mitglieder dieses Projekts oder Ordners können diesen Kontakt automatisch einseh | `contacts.mitglieder_dieses_projekts_oder_ordners` |
| 187 | ⬜ | L720 | `template_text` | Gruppe / Kategorie | `contacts.gruppe_kategorie` |
| 188 | ⬜ | L733 | `template_text` | Tags / Schlagwörter | `contacts.tags_schlagwörter` |
| 189 | ⬜ | L748 | `attr:placeholder` | Tag tippen + Enter... | `contacts.tag_tippen_enter` |
| 190 | ⬜ | L758 | `template_text` | Notizen & Bemerkungen | `contacts.notizen_bemerkungen` |
| 191 | ⬜ | L760 | `attr:placeholder` | Besondere Absprachen, Arbeitszeiten, Erreichbarkeit... | `contacts.besondere_absprachen_arbeitszeiten_errei` |
| 192 | ⬜ | L780 | `template_text` | Wenn aktiviert, können alle Kollegen deines Unternehmens diesen Kontakt sehen un | `contacts.wenn_aktiviert_können_alle_kollegen_dein` |
| 193 | ⬜ | L939 | `script_literal` | Bestätigen | `contacts.bestätigen` |
| 194 | ⬜ | L978 | `script_literal` | Löschen | `contacts.löschen` |
| 195 | ⬜ | L994 | `script_literal` | Fehler beim Ausführen der Aktion | `contacts.fehler_beim_ausführen_der_aktion` |
| 196 | ⬜ | L1117 | `script_literal` | Behörden & Ämter | `contacts.behörden_ämter` |
| 197 | ⬜ | L1118 | `script_literal` | Bauträger & Eigentümer | `contacts.bauträger_eigentümer` |
| 198 | ⬜ | L1255 | `script_literal` | Fehler beim Zusammenführen des Kontakts | `contacts.fehler_beim_zusammenführen_des_kontakts` |
| 199 | ⬜ | L1343 | `script_literal` | Behörden & Ämter | `contacts.behörden_ämter` |
| 200 | ⬜ | L1343 | `script_literal` | Bauträger & Eigentümer | `contacts.bauträger_eigentümer` |
| 201 | ⬜ | L1352 | `script_literal` | Du bist ein intelligenter Assistent für Baudokumentation und Kontaktmanagement.  | `contacts.du_bist_ein_intelligenter_assistent_für` |
| 202 | ⬜ | L1366 | `script_literal` | KI-Rückgabe konnte nicht als JSON interpretiert werden. | `contacts.kirückgabe_konnte_nicht_als_json_interpr` |
| 203 | ⬜ | L1391 | `script_literal` | ✓ Daten erfolgreich erkannt und ins Formular übertragen! | `contacts.daten_erfolgreich_erkannt_und_ins_formu` |
| 204 | ⬜ | L1476 | `script_literal` | Duplikat erkannt: Ein ähnlicher Kontakt existiert bereits. | `contacts.duplikat_erkannt_ein_ähnlicher_kontakt_e` |
| 205 | ⬜ | L1488 | `script_literal` | Kontakt löschen | `contacts.kontakt_löschen` |
| 206 | ⬜ | L1490 | `script_literal` | Möchtest du den Kontakt | `contacts.möchtest_du_den_kontakt` |
| 207 | ⬜ | L1490 | `script_literal` | wirklich unwiderruflich löschen? | `contacts.wirklich_unwiderruflich_löschen` |
| 208 | ⬜ | L1491 | `script_literal` | Kontakt löschen | `contacts.kontakt_löschen` |
| 209 | ⬜ | L1499 | `script_literal` | Kontakt erfolgreich gelöscht | `contacts.kontakt_erfolgreich_gelöscht` |

### 📄 `pages/dashboard.vue` (2 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 210 | ⬜ | L1472 | `script_literal` | Fehler beim Löschen des Ordners | `dashboard.fehler_beim_löschen_des_ordners` |
| 211 | ⬜ | L1502 | `script_literal` | Fehler beim Löschen des Projekts | `dashboard.fehler_beim_löschen_des_projekts` |

### 📄 `pages/forgot-password.vue` (4 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 212 | ⬜ | L10 | `template_text` | Passwort vergessen? | `forgot-password.passwort_vergessen` |
| 213 | ⬜ | L11 | `template_text` | Gib deine E-Mail-Adresse ein. Wir senden dir einen sicheren Link zum Zurücksetze | `forgot-password.gib_deine_emailadresse_ein_wir_senden_di` |
| 214 | ⬜ | L24 | `template_text` | E-Mail gesendet | `forgot-password.email_gesendet` |
| 215 | ⬜ | L55 | `template_text` | Zurück zur Anmeldung | `forgot-password.zurück_zur_anmeldung` |

### 📄 `pages/index.vue` (9 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 216 | ⬜ | L9 | `template_text` | Taskster Modern Work Management | `index.taskster_modern_work_management` |
| 217 | ⬜ | L24 | `template_text` | Direkt starten & Demo testen | `index.direkt_starten_demo_testen` |
| 218 | ⬜ | L30 | `template_text` | Kostenlos registrieren | `index.kostenlos_registrieren` |
| 219 | ⬜ | L43 | `template_text` | Mehrstufige Struktur | `index.mehrstufige_struktur` |
| 220 | ⬜ | L44 | `template_text` | Ordner → Projekte → Abschnitte → Aufgaben. Logische Organisation über Ebenen hin | `index.ordner_projekte_abschnitte_aufgaben_logi` |
| 221 | ⬜ | L53 | `template_text` | Präzise Rollen & Rechte | `index.präzise_rollen_rechte` |
| 222 | ⬜ | L54 | `template_text` | Granulare Zugriffssteuerung auf Projekt- und Abschnittsebene. Jeder sieht und be | `index.granulare_zugriffssteuerung_auf_projekt` |
| 223 | ⬜ | L63 | `template_text` | Flexible Team-Verwaltung | `index.flexible_teamverwaltung` |
| 224 | ⬜ | L64 | `template_text` | Nahtlose Mandantenverwaltung, flexible Pläne, Mitarbeiter-Einladungen und anpass | `index.nahtlose_mandantenverwaltung_flexible_pl` |

### 📄 `pages/journal.vue` (47 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 225 | ⬜ | L9 | `template_text` | Projektjournal & Logbuch | `journal.projektjournal_logbuch` |
| 226 | ⬜ | L10 | `template_text` | Zentrale Übersicht aller Bausitzungen, Notizen und Journaleinträge über alle Ord | `journal.zentrale_übersicht_aller_bausitzungen_no` |
| 227 | ⬜ | L40 | `template_text` | Ordner wählen | `journal.ordner_wählen` |
| 228 | ⬜ | L49 | `template_text` | 📁 Alle Ordner anzeigen (Gesamtübersicht) | `journal.alle_ordner_anzeigen_gesamtübersicht` |
| 229 | ⬜ | L59 | `template_text` | Projekt filtern | `journal.projekt_filtern` |
| 230 | ⬜ | L66 | `template_text` | Alle Projekte im Ordner | `journal.alle_projekte_im_ordner` |
| 231 | ⬜ | L66 | `template_text` | Nur reine Ordner-Einträge (ohne Projekt) | `journal.nur_reine_ordnereinträge_ohne_projekt` |
| 232 | ⬜ | L72 | `template_text` | Journal durchsuchen | `journal.journal_durchsuchen` |
| 233 | ⬜ | L79 | `attr:placeholder` | Im Journal, Text, Titel, Autor oder Projekt suchen... | `journal.im_journal_text_titel_autor_oder_projekt` |
| 234 | ⬜ | L105 | `template_text` | Alle Einträge | `journal.alle_einträge` |
| 235 | ⬜ | L133 | `template_text` | Kategorie: | `journal.kategorie` |
| 236 | ⬜ | L142 | `template_text` | Alle Kategorien | `journal.alle_kategorien` |
| 237 | ⬜ | L149 | `template_text` | 📝 Notiz | `journal.notiz` |
| 238 | ⬜ | L153 | `template_text` | Sortierung: | `journal.sortierung` |
| 239 | ⬜ | L161 | `template_text` | 📅 Datum (Neueste zuerst) | `journal.datum_neueste_zuerst` |
| 240 | ⬜ | L161 | `template_text` | 📅 Datum (Älteste zuerst) | `journal.datum_älteste_zuerst` |
| 241 | ⬜ | L164 | `template_text` | 📁 Auftrag / Projekt (A-Z) | `journal.auftrag_projekt_az` |
| 242 | ⬜ | L169 | `template_text` | Journaleinträge werden geladen... | `journal.journaleinträge_werden_geladen` |
| 243 | ⬜ | L184 | `template_text` | Keine passenden Journaleinträge gefunden | `journal.keine_passenden_journaleinträge_gefunden` |
| 244 | ⬜ | L185 | `template_text` | Erfasse eine Bausitzung, ein Bautagebuch oder importiere eine E-Mail mit automat | `journal.erfasse_eine_bausitzung_ein_bautagebuch` |
| 245 | ⬜ | L240 | `template_text` | • Verknüpft: | `journal.verknüpft` |
| 246 | ⬜ | L247 | `attr:title` | Zum Ordner springen | `journal.zum_ordner_springen` |
| 247 | ⬜ | L259 | `attr:title` | Zum Projekt springen | `journal.zum_projekt_springen` |
| 248 | ⬜ | L282 | `attr:title` | Eintrag bearbeiten | `journal.eintrag_bearbeiten` |
| 249 | ⬜ | L300 | `attr:title` | Eintrag löschen | `journal.eintrag_löschen` |
| 250 | ⬜ | L334 | `template_text` | Vorgeschlagene Aktionen (KI-Agent) (): | `journal.vorgeschlagene_aktionen_kiagent` |
| 251 | ⬜ | L390 | `template_text` | Inhalt / Notizen | `journal.inhalt_notizen` |
| 252 | ⬜ | L396 | `attr:title` | Vollständiges Original-Dokument / E-Mail ansehen | `journal.vollständiges_originaldokument_email_ans` |
| 253 | ⬜ | L439 | `template_text` | Verknüpfte Aufgabe | `journal.verknüpfte_aufgabe` |
| 254 | ⬜ | L459 | `template_text` | Aufgabe im Projekt öffnen | `journal.aufgabe_im_projekt_öffnen` |
| 255 | ⬜ | L471 | `template_text` | -- Verknüpfung lösen -- | `journal.verknüpfung_lösen` |
| 256 | ⬜ | L479 | `template_text` | Aufgabe zuweisen: | `journal.aufgabe_zuweisen` |
| 257 | ⬜ | L486 | `template_text` | -- Aufgabe auswählen -- | `journal.aufgabe_auswählen` |
| 258 | ⬜ | L535 | `template_text` | Dateianhänge () | `journal.dateianhänge` |
| 259 | ⬜ | L607 | `template_text` | Originalansicht des importierten Dokuments / der E-Mail | `journal.originalansicht_des_importierten_dokumen` |
| 260 | ⬜ | L622 | `template_text` | Absender: | `journal.absender` |
| 261 | ⬜ | L630 | `template_text` | Empfänger: | `journal.empfänger` |
| 262 | ⬜ | L636 | `template_text` | Betreff: | `journal.betreff` |
| 263 | ⬜ | L644 | `template_text` | Datum: | `journal.datum` |
| 264 | ⬜ | L656 | `template_text` | Erkannte Kontakte (in Kontakte synchronisiert): | `journal.erkannte_kontakte_in_kontakte_synchronis` |
| 265 | ⬜ | L702 | `template_text` | Schließen | `journal.schließen` |
| 266 | ⬜ | L722 | `template_text` | Journal-Eintrag löschen | `journal.journaleintrag_löschen` |
| 267 | ⬜ | L723 | `template_text` | Dieser Vorgang kann nicht rückgängig gemacht werden | `journal.dieser_vorgang_kann_nicht_rückgängig_gem` |
| 268 | ⬜ | L727 | `template_text` | Möchtest du diesen Journal-Eintrag wirklich unwiderruflich löschen? | `journal.möchtest_du_diesen_journaleintrag_wirkli` |
| 269 | ⬜ | L1151 | `script_literal` | Journal-Eintrag erfolgreich gelöscht | `journal.journaleintrag_erfolgreich_gelöscht` |
| 270 | ⬜ | L1153 | `script_literal` | Fehler beim Löschen des Eintrags | `journal.fehler_beim_löschen_des_eintrags` |
| 271 | ⬜ | L1230 | `script_literal` | Aufgaben-Verknüpfung aktualisiert | `journal.aufgabenverknüpfung_aktualisiert` |

### 📄 `pages/login.vue` (12 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 272 | ⬜ | L7 | `attr:alt` | Inspiring Workspace | `login.inspiring_workspace` |
| 273 | ⬜ | L11 | `attr:alt` | Taskster Logo | `login.taskster_logo` |
| 274 | ⬜ | L14 | `template_text` | Einfach. Klar. | `login.einfach_klar` |
| 275 | ⬜ | L16 | `template_text` | Organisiere Aufgaben, Abschnitte und Teamarbeit in einer fröhlichen, modernen Um | `login.organisiere_aufgaben_abschnitte_und_team` |
| 276 | ⬜ | L24 | `template_text` | Zero-Trust Architektur & Schweizer Präzision | `login.zerotrust_architektur_schweizer_präzisio` |
| 277 | ⬜ | L64 | `template_text` | Einladung zu | `login.einladung_zu` |
| 278 | ⬜ | L64 | `template_text` | Du wurdest eingeladen, diesem Unternehmen beizutreten. | `login.du_wurdest_eingeladen_diesem_unternehmen` |
| 279 | ⬜ | L93 | `template_text` | Passwort vergessen? | `login.passwort_vergessen` |
| 280 | ⬜ | L114 | `template_text` | Vollständiger Name | `login.vollständiger_name` |
| 281 | ⬜ | L120 | `attr:placeholder` | Max Mustermann | `login.max_mustermann` |
| 282 | ⬜ | L139 | `attr:placeholder` | Mindestens 6 Zeichen | `login.mindestens_6_zeichen` |
| 283 | ⬜ | L203 | `script_literal` | Ungültige oder abgelaufene Einladung | `login.ungültige_oder_abgelaufene_einladung` |

### 📄 `pages/reset-password.vue` (14 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 284 | ⬜ | L10 | `template_text` | Neues Passwort festlegen | `reset-password.neues_passwort_festlegen` |
| 285 | ⬜ | L11 | `template_text` | Hallo ! Gib hier dein neues Passwort ein. | `reset-password.hallo_gib_hier_dein_neues_passwort_ein` |
| 286 | ⬜ | L18 | `template_text` | Sicherheits-Link wird überprüft... | `reset-password.sicherheitslink_wird_überprüft` |
| 287 | ⬜ | L23 | `template_text` | Link ungültig oder abgelaufen | `reset-password.link_ungültig_oder_abgelaufen` |
| 288 | ⬜ | L27 | `template_text` | Neuen Link anfordern | `reset-password.neuen_link_anfordern` |
| 289 | ⬜ | L35 | `template_text` | Passwort geändert! | `reset-password.passwort_geändert` |
| 290 | ⬜ | L41 | `template_text` | Jetzt anmelden | `reset-password.jetzt_anmelden` |
| 291 | ⬜ | L48 | `template_text` | Neues Passwort | `reset-password.neues_passwort` |
| 292 | ⬜ | L51 | `attr:placeholder` | Mindestens 8 Zeichen | `reset-password.mindestens_8_zeichen` |
| 293 | ⬜ | L57 | `template_text` | Passwort wiederholen | `reset-password.passwort_wiederholen` |
| 294 | ⬜ | L83 | `template_text` | Zurück zur Anmeldung | `reset-password.zurück_zur_anmeldung` |
| 295 | ⬜ | L118 | `script_literal` | Dieser Link ist ungültig oder abgelaufen. | `reset-password.dieser_link_ist_ungültig_oder_abgelaufen` |
| 296 | ⬜ | L132 | `script_literal` | Die Passwörter stimmen nicht überein. | `reset-password.die_passwörter_stimmen_nicht_überein` |
| 297 | ⬜ | L148 | `script_literal` | Passwort konnte nicht zurückgesetzt werden. | `reset-password.passwort_konnte_nicht_zurückgesetzt_werd` |

### 📄 `pages/settings.vue` (7 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 298 | ⬜ | L177 | `template_text` | Deutsch (DE) | `settings.deutsch_de` |
| 299 | ⬜ | L177 | `template_text` | English (EN) | `settings.english_en` |
| 300 | ⬜ | L178 | `template_text` | Slovenčina (SK) | `settings.slovenina_sk` |
| 301 | ⬜ | L1378 | `script_literal` | Zürich, Bern, Genf (MEZ/MESZ) | `settings.zürich_bern_genf_mezmesz` |
| 302 | ⬜ | L1862 | `script_literal` | Konto konnte nicht gelöscht werden. Bitte Passwort prüfen. | `settings.konto_konnte_nicht_gelöscht_werden_bitte` |
| 303 | ⬜ | L2005 | `script_literal` | Möchtest du diese Gruppe wirklich löschen? | `settings.möchtest_du_diese_gruppe_wirklich_lösche` |
| 304 | ⬜ | L2013 | `script_literal` | Fehler beim Löschen der Gruppe. | `settings.fehler_beim_löschen_der_gruppe` |

### 📄 `pages/time.vue` (49 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 305 | ⬜ | L18 | `template_text` | Zeitrapportierung & Controlling | `time.zeitrapportierung_controlling` |
| 306 | ⬜ | L21 | `template_text` | Alle erfassten Arbeitszeiten, Budgets und abrechenbaren Leistungen im Gesamtüber | `time.alle_erfassten_arbeitszeiten_budgets_und` |
| 307 | ⬜ | L32 | `attr:title` | Als CSV-Datei herunterladen | `time.als_csvdatei_herunterladen` |
| 308 | ⬜ | L35 | `template_text` | CSV Export | `time.csv_export` |
| 309 | ⬜ | L43 | `attr:title` | Druckansicht öffnen | `time.druckansicht_öffnen` |
| 310 | ⬜ | L54 | `template_text` | Zeit erfassen | `time.zeit_erfassen` |
| 311 | ⬜ | L66 | `template_text` | Erfasste Zeit | `time.erfasste_zeit` |
| 312 | ⬜ | L79 | `template_text` | Abrechenbarer Wert | `time.abrechenbarer_wert` |
| 313 | ⬜ | L83 | `template_text` | nach hinterlegten Stundensätzen | `time.nach_hinterlegten_stundensätzen` |
| 314 | ⬜ | L93 | `template_text` | Ø Stundensatz | `time.stundensatz` |
| 315 | ⬜ | L98 | `template_text` | Mischsatz aller Einträge | `time.mischsatz_aller_einträge` |
| 316 | ⬜ | L110 | `template_text` | Stoppuhr,  manuell | `time.stoppuhr_manuell` |
| 317 | ⬜ | L123 | `template_text` | Zeitraum: | `time.zeitraum` |
| 318 | ⬜ | L132 | `template_text` | von  Einträgen angezeigt | `time.von_einträgen_angezeigt` |
| 319 | ⬜ | L144 | `attr:placeholder` | Suche nach Text, Aufgabe... | `time.suche_nach_text_aufgabe` |
| 320 | ⬜ | L158 | `template_text` | Alle Projekte () | `time.alle_projekte` |
| 321 | ⬜ | L163 | `template_text` | Von: | `time.von` |
| 322 | ⬜ | L177 | `template_text` | Bis: | `time.bis` |
| 323 | ⬜ | L189 | `template_text` | Zeitrapporte werden geladen... | `time.zeitrapporte_werden_geladen` |
| 324 | ⬜ | L199 | `template_text` | Keine Zeiteinträge gefunden | `time.keine_zeiteinträge_gefunden` |
| 325 | ⬜ | L203 | `template_text` | Im ausgewählten Zeitraum liegen keine Buchungen vor. Starte die Live-Stoppuhr ob | `time.im_ausgewählten_zeitraum_liegen_keine_bu` |
| 326 | ⬜ | L208 | `template_text` | Zeit manuell eintragen | `time.zeit_manuell_eintragen` |
| 327 | ⬜ | L216 | `template_text` | Projekt & Aufgabe | `time.projekt_aufgabe` |
| 328 | ⬜ | L265 | `attr:title` | Über Live-Stoppuhr gestoppt | `time.über_livestoppuhr_gestoppt` |
| 329 | ⬜ | L275 | `attr:title` | Manuelle Zeiterfassung | `time.manuelle_zeiterfassung` |
| 330 | ⬜ | L310 | `attr:title` | Eintrag bearbeiten | `time.eintrag_bearbeiten` |
| 331 | ⬜ | L316 | `attr:title` | Eintrag löschen | `time.eintrag_löschen` |
| 332 | ⬜ | L325 | `template_text` | Summe der gefilterten Auswahl | `time.summe_der_gefilterten_auswahl` |
| 333 | ⬜ | L352 | `template_text` | Arbeitszeit manuell erfassen | `time.arbeitszeit_manuell_erfassen` |
| 334 | ⬜ | L369 | `template_text` | Projekt auswählen... | `time.projekt_auswählen` |
| 335 | ⬜ | L388 | `template_text` | Dauer (Minuten) | `time.dauer_minuten` |
| 336 | ⬜ | L395 | `attr:placeholder` | z.B. 90 (für 1.5h) | `time.zb_90_für_15h` |
| 337 | ⬜ | L400 | `template_text` | =  Stunden | `time.stunden` |
| 338 | ⬜ | L407 | `template_text` | Stundensatz (CHF / EUR) | `time.stundensatz_chf_eur` |
| 339 | ⬜ | L416 | `template_text` | Tätigkeitsbeschreibung / Notiz | `time.tätigkeitsbeschreibung_notiz` |
| 340 | ⬜ | L423 | `attr:placeholder` | Welche Arbeiten wurden ausgeführt? | `time.welche_arbeiten_wurden_ausgeführt` |
| 341 | ⬜ | L460 | `template_text` | Zeiteintrag bearbeiten | `time.zeiteintrag_bearbeiten` |
| 342 | ⬜ | L480 | `template_text` | Dauer (Minuten) | `time.dauer_minuten` |
| 343 | ⬜ | L493 | `template_text` | =  Stunden | `time.stunden` |
| 344 | ⬜ | L497 | `template_text` | Stundensatz (CHF / EUR) | `time.stundensatz_chf_eur` |
| 345 | ⬜ | L507 | `template_text` | Tätigkeitsbeschreibung / Notiz | `time.tätigkeitsbeschreibung_notiz` |
| 346 | ⬜ | L663 | `script_literal` | Bestätigen | `time.bestätigen` |
| 347 | ⬜ | L702 | `script_literal` | Löschen | `time.löschen` |
| 348 | ⬜ | L718 | `script_literal` | Fehler beim Ausführen der Aktion | `time.fehler_beim_ausführen_der_aktion` |
| 349 | ⬜ | L963 | `script_literal` | Zeiteintrag löschen | `time.zeiteintrag_löschen` |
| 350 | ⬜ | L964 | `script_literal` | Dieser Vorgang kann nicht rückgängig gemacht werden | `time.dieser_vorgang_kann_nicht_rückgängig_gem` |
| 351 | ⬜ | L965 | `script_literal` | Möchtest du diesen Zeiteintrag wirklich unwiderruflich löschen? | `time.möchtest_du_diesen_zeiteintrag_wirklich` |
| 352 | ⬜ | L966 | `script_literal` | Eintrag löschen | `time.eintrag_löschen` |
| 353 | ⬜ | L974 | `script_literal` | Zeiteintrag erfolgreich gelöscht | `time.zeiteintrag_erfolgreich_gelöscht` |

---

## Phase 3: Projekt- & Ordner-Detailseiten
*334 Strings in 2 Dateien*

### 📄 `pages/folders/[id].vue` (259 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 354 | ⬜ | L414 | `attr:title` | Standard-Projekt dieses Ordners | `folders.standardprojekt_dieses_ordners` |
| 355 | ⬜ | L728 | `template_text` | 📝 Notiz | `folders.notiz` |
| 356 | ⬜ | L814 | `attr:title` | Zum Projekt springen | `folders.zum_projekt_springen` |
| 357 | ⬜ | L847 | `attr:title` | Eintrag bearbeiten | `folders.eintrag_bearbeiten` |
| 358 | ⬜ | L851 | `attr:title` | Eintrag löschen | `folders.eintrag_löschen` |
| 359 | ⬜ | L888 | `template_text` | Vorgeschlagene Aktionen (KI-Agent) (): | `folders.vorgeschlagene_aktionen_kiagent` |
| 360 | ⬜ | L1013 | `template_text` | -- Verknüpfung lösen -- | `folders.verknüpfung_lösen` |
| 361 | ⬜ | L1020 | `template_text` | Aufgabe zuweisen: | `folders.aufgabe_zuweisen` |
| 362 | ⬜ | L1030 | `template_text` | -- Aufgabe auswählen -- | `folders.aufgabe_auswählen` |
| 363 | ⬜ | L1111 | `template_text` | Definiere eigene Attribute für Projekte und Aufgaben. Diese stehen allen Projekt | `folders.definiere_eigene_attribute_für_projekte` |
| 364 | ⬜ | L1124 | `template_text` | Standard-Aufgabenfelder &amp; Sichtbarkeits-Logik | `folders.standardaufgabenfelder_amp_sichtbarkeits` |
| 365 | ⬜ | L1126 | `template_text` | Steuere die Sichtbarkeit und Abhängigkeiten der vorkonfektionierten Standardfeld | `folders.steuere_die_sichtbarkeit_und_abhängigkei` |
| 366 | ⬜ | L1169 | `template_text` | Immer sichtbar | `folders.immer_sichtbar` |
| 367 | ⬜ | L1172 | `template_text` | Logik anpassen | `folders.logik_anpassen` |
| 368 | ⬜ | L1186 | `template_text` | Erstelle strukturierte Attribute wie Bauleiter, Vorgangsnummer, Fertigstellungst | `folders.erstelle_strukturierte_attribute_wie_bau` |
| 369 | ⬜ | L1255 | `template_text` | Mehrzeiliges Notizfeld | `folders.mehrzeiliges_notizfeld` |
| 370 | ⬜ | L1263 | `attr:title` | Feld bearbeiten | `folders.feld_bearbeiten` |
| 371 | ⬜ | L1273 | `attr:title` | Feld löschen | `folders.feld_löschen` |
| 372 | ⬜ | L1286 | `template_text` | Kontakte gelten pro Projektordner und stehen in allen zugehörigen Projekten zur  | `folders.kontakte_gelten_pro_projektordner_und_st` |
| 373 | ⬜ | L1305 | `template_text` | Erfasse Bauleiter, Handwerker, Ingenieure oder Eigentümer für diesen Ordner. | `folders.erfasse_bauleiter_handwerker_ingenieure` |
| 374 | ⬜ | L1381 | `attr:title` | WhatsApp Chat öffnen | `folders.whatsapp_chat_öffnen` |
| 375 | ⬜ | L1408 | `template_text` | Keine Kontaktdaten hinterlegt | `folders.keine_kontaktdaten_hinterlegt` |
| 376 | ⬜ | L1438 | `attr:title` | Löschen | `folders.löschen` |
| 377 | ⬜ | L1449 | `template_text` | Neues Projekt erstellen | `folders.neues_projekt_erstellen` |
| 378 | ⬜ | L1453 | `template_text` | Erstelle ein Projekt im Ordner | `folders.erstelle_ein_projekt_im_ordner` |
| 379 | ⬜ | L1485 | `template_text` | Aus Vorlage (Empfohlen) | `folders.aus_vorlage_empfohlen` |
| 380 | ⬜ | L1500 | `template_text` | Excel / CSV Import | `folders.excel_csv_import` |
| 381 | ⬜ | L1508 | `template_text` | Leeres Projekt (Blanko) | `folders.leeres_projekt_blanko` |
| 382 | ⬜ | L1525 | `template_text` | Alle Vorlagen () | `folders.alle_vorlagen` |
| 383 | ⬜ | L1534 | `template_text` | Job & Gewerbe () | `folders.job_gewerbe` |
| 384 | ⬜ | L1545 | `template_text` | Privat & Familie () | `folders.privat_familie` |
| 385 | ⬜ | L1549 | `attr:placeholder` | 🔍 Vorlage suchen... | `folders.vorlage_suchen` |
| 386 | ⬜ | L1555 | `template_text` | Vorlagen werden geladen... | `folders.vorlagen_werden_geladen` |
| 387 | ⬜ | L1560 | `template_text` | Keine passenden Vorlagen gefunden. | `folders.keine_passenden_vorlagen_gefunden` |
| 388 | ⬜ | L1608 | `template_text` | f.logic_rules && f.logic_rules.depends_on_field)"                     class="px- | `folders.flogicrules_flogicrulesdependsonfield_cl` |
| 389 | ⬜ | L1622 | `template_text` | ✓ Gewählte Vorlage: | `folders.gewählte_vorlage` |
| 390 | ⬜ | L1626 | `template_text` | Konfiguration anpassen | `folders.konfiguration_anpassen` |
| 391 | ⬜ | L1630 | `template_text` | Projektphasen / Abschnitte (): | `folders.projektphasen_abschnitte` |
| 392 | ⬜ | L1633 | `template_text` | Du kannst Phasen vor der Erstellung anpassen oder entfernen | `folders.du_kannst_phasen_vor_der_erstellung_anpa` |
| 393 | ⬜ | L1649 | `attr:title` | Phase entfernen | `folders.phase_entfernen` |
| 394 | ⬜ | L1654 | `attr:placeholder` | + Phase hinzufügen... | `folders.phase_hinzufügen` |
| 395 | ⬜ | L1670 | `template_text` | Zusatzfelder dieser Vorlage (): | `folders.zusatzfelder_dieser_vorlage` |
| 396 | ⬜ | L1672 | `template_text` | Felder und Logikregeln können vor Projektstart angepasst werden | `folders.felder_und_logikregeln_können_vor_projek` |
| 397 | ⬜ | L1677 | `template_text` | Keine Zusatzfelder für diese Vorlage definiert. | `folders.keine_zusatzfelder_für_diese_vorlage_def` |
| 398 | ⬜ | L1698 | `attr:title` | Feld entfernen | `folders.feld_entfernen` |
| 399 | ⬜ | L1711 | `template_text` | Sichtbar wenn: | `folders.sichtbar_wenn` |
| 400 | ⬜ | L1718 | `template_text` | Ändern | `folders.ändern` |
| 401 | ⬜ | L1728 | `attr:title` | Logik entfernen | `folders.logik_entfernen` |
| 402 | ⬜ | L1732 | `template_text` | Immer sichtbar | `folders.immer_sichtbar` |
| 403 | ⬜ | L1740 | `template_text` | + Logik hinzufügen | `folders.logik_hinzufügen` |
| 404 | ⬜ | L1779 | `template_text` | Datei hochladen / ablegen | `folders.datei_hochladen_ablegen` |
| 405 | ⬜ | L1789 | `template_text` | Text / CSV einfügen (Strg+V) | `folders.text_csv_einfügen_strgv` |
| 406 | ⬜ | L1809 | `template_text` | Excel (.xlsx, .xls) oder CSV / TSV Datei auswählen oder hier ablegen | `folders.excel_xlsx_xls_oder_csv_tsv_datei_auswäh` |
| 407 | ⬜ | L1814 | `template_text` | Importiert mehrere Projekte auf einen Klick direkt in den Ordner «». | `folders.importiert_mehrere_projekte_auf_einen_kl` |
| 408 | ⬜ | L1821 | `template_text` | Muster-Excel herunterladen | `folders.musterexcel_herunterladen` |
| 409 | ⬜ | L1834 | `template_text` | CSV- oder aus Excel kopierte Tabellendaten hier einfügen: | `folders.csv_oder_aus_excel_kopierte_tabellendate` |
| 410 | ⬜ | L1839 | `template_text` | + Muster-Daten einfügen | `folders.musterdaten_einfügen` |
| 411 | ⬜ | L1843 | `attr:placeholder` | Spalte1;Spalte2;Spalte3&#10;Wert1;Wert2;Wert3&#10;(Oder einfach Zeilen aus Excel | `folders.spalte1spalte2spalte310wert1wert2wert310` |
| 412 | ⬜ | L1852 | `template_text` | Unterstützt Semikolon (;), Komma (,), Tabulatoren (Excel-Kopien) und Pipe (\|). | `folders.unterstützt_semikolon_komma_tabulatoren` |
| 413 | ⬜ | L1863 | `template_text` | Daten jetzt analysieren | `folders.daten_jetzt_analysieren` |
| 414 | ⬜ | L1874 | `template_text` | Datenquelle: | `folders.datenquelle` |
| 415 | ⬜ | L1882 | `template_text` | ↺ Neu einfügen / wechseln | `folders.neu_einfügen_wechseln` |
| 416 | ⬜ | L1891 | `template_text` | Bitte weise mindestens einer Spalte das Feld | `folders.bitte_weise_mindestens_einer_spalte_das` |
| 417 | ⬜ | L1894 | `template_text` | zu, um den Import durchzuführen. | `folders.zu_um_den_import_durchzuführen` |
| 418 | ⬜ | L1902 | `template_text` | Workflow-Vorlage anwenden (optional): | `folders.workflowvorlage_anwenden_optional` |
| 419 | ⬜ | L1905 | `template_text` | Übernimmt Phasen &amp; Vorlagen-Zusatzfelder inkl. Logik | `folders.übernimmt_phasen_amp_vorlagenzusatzfelde` |
| 420 | ⬜ | L1915 | `template_text` | -- Keine Vorlage (Eigene Phasen &amp; Standard nutzen) -- | `folders.keine_vorlage_eigene_phasen_amp_standar` |
| 421 | ⬜ | L1924 | `template_text` | Spaltenzuweisung (Mapping): | `folders.spaltenzuweisung_mapping` |
| 422 | ⬜ | L1926 | `template_text` | Projekttitel ist Pflichtfeld | `folders.projekttitel_ist_pflichtfeld` |
| 423 | ⬜ | L1936 | `template_text` | Spalte in Excel / CSV | `folders.spalte_in_excel_csv` |
| 424 | ⬜ | L1939 | `template_text` | Beispielwert (Zeile 1) | `folders.beispielwert_zeile_1` |
| 425 | ⬜ | L1940 | `template_text` | Zuweisung an Taskster Projekt-Feld | `folders.zuweisung_an_taskster_projektfeld` |
| 426 | ⬜ | L1964 | `template_text` | -- Nicht importieren -- | `folders.nicht_importieren` |
| 427 | ⬜ | L1965 | `attr:label` | Aktionen & Aufgaben | `folders.aktionen_aufgaben` |
| 428 | ⬜ | L1966 | `template_text` | ✅ [Aktion] Neue Aufgabe erstellen | `folders.aktion_neue_aufgabe_erstellen` |
| 429 | ⬜ | L1967 | `attr:label` | Standard Projekt-Felder | `folders.standard_projektfelder` |
| 430 | ⬜ | L1971 | `template_text` | 📅 Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 431 | ⬜ | L1971 | `template_text` | 🔄 Status (active/archived/completed) | `folders.status_activearchivedcompleted` |
| 432 | ⬜ | L1976 | `template_text` | 💰 Währung (CHF, EUR, USD) | `folders.währung_chf_eur_usd` |
| 433 | ⬜ | L1976 | `template_text` | ⏱️ Budget Stunden | `folders.budget_stunden` |
| 434 | ⬜ | L1979 | `attr:label` | Bestehende Zusatzfelder dieses Ordners | `folders.bestehende_zusatzfelder_dieses_ordners` |
| 435 | ⬜ | L2016 | `template_text` | Vorschau der ersten Datenzeilen: | `folders.vorschau_der_ersten_datenzeilen` |
| 436 | ⬜ | L2030 | `template_text` | Workflow-Abschnitte (Phasen) für importierte Projekte: | `folders.workflowabschnitte_phasen_für_importiert` |
| 437 | ⬜ | L2036 | `template_text` | ↺ Auf Standard (Offen, In Arbeit, Abgeschlossen) | `folders.auf_standard_offen_in_arbeit_abgeschlos` |
| 438 | ⬜ | L2040 | `template_text` | Diese Phasen werden für alle importierten Projekte erstellt und als Standard-Vor | `folders.diese_phasen_werden_für_alle_importierte` |
| 439 | ⬜ | L2060 | `attr:title` | Abschnitt entfernen | `folders.abschnitt_entfernen` |
| 440 | ⬜ | L2083 | `template_text` | Projekttitel / Name | `folders.projekttitel_name` |
| 441 | ⬜ | L2088 | `attr:placeholder` | z.B. FTTH Ausbau Bern Süd oder Wohnzimmer Renovation | `folders.zb_ftth_ausbau_bern_süd_oder_wohnzimmer` |
| 442 | ⬜ | L2095 | `template_text` | Gib dem Projekt eine aussagekräftige Bezeichnung. | `folders.gib_dem_projekt_eine_aussagekräftige_bez` |
| 443 | ⬜ | L2097 | `template_text` | 📅 Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 444 | ⬜ | L2104 | `template_text` | Standard-Fälligkeitsdatum für dieses Projekt (optional). | `folders.standardfälligkeitsdatum_für_dieses_proj` |
| 445 | ⬜ | L2110 | `template_text` | Sichtbarkeit des Projekts | `folders.sichtbarkeit_des_projekts` |
| 446 | ⬜ | L2120 | `template_text` | 🔒 Privat (Standard) | `folders.privat_standard` |
| 447 | ⬜ | L2143 | `template_text` | Projekt-Felder dieses Ordners | `folders.projektfelder_dieses_ordners` |
| 448 | ⬜ | L2159 | `template_text` | -- Nicht ausgewählt -- | `folders.nicht_ausgewählt` |
| 449 | ⬜ | L2171 | `attr:placeholder` | Details, Notizen oder Beschreibung... | `folders.details_notizen_oder_beschreibung` |
| 450 | ⬜ | L2258 | `template_text` | Projektordner anpassen | `folders.projektordner_anpassen` |
| 451 | ⬜ | L2262 | `template_text` | Passe den Namen, das Icon, die Vorlage und die Phasen dieses Projektordners an. | `folders.passe_den_namen_das_icon_die_vorlage_und` |
| 452 | ⬜ | L2277 | `template_text` | Name des Projektordners | `folders.name_des_projektordners` |
| 453 | ⬜ | L2291 | `template_text` | Icon aus Liste auswählen | `folders.icon_aus_liste_auswählen` |
| 454 | ⬜ | L2307 | `template_text` | Ausgewähltes Icon: | `folders.ausgewähltes_icon` |
| 455 | ⬜ | L2321 | `template_text` | Projektvorlage für diesen Ordner | `folders.projektvorlage_für_diesen_ordner` |
| 456 | ⬜ | L2331 | `template_text` | Keine Vorlage (Freie / Manuelle Abschnitte) | `folders.keine_vorlage_freie_manuelle_abschnitte` |
| 457 | ⬜ | L2335 | `template_text` | Info: | `folders.info` |
| 458 | ⬜ | L2342 | `template_text` | Wähle eine Branchen-Vorlage (z. B. Hochbau, Tiefbau, FTTH, Gebäudeautomation) od | `folders.wähle_eine_branchenvorlage_z_b_hochbau_t` |
| 459 | ⬜ | L2345 | `template_text` | Workflow-Phasen / Abschnitte () | `folders.workflowphasen_abschnitte` |
| 460 | ⬜ | L2352 | `template_text` | ↺ Aus Vorlage neu laden | `folders.aus_vorlage_neu_laden` |
| 461 | ⬜ | L2358 | `template_text` | ↺ Standard-Phasen | `folders.standardphasen` |
| 462 | ⬜ | L2399 | `attr:title` | Nach oben verschieben | `folders.nach_oben_verschieben` |
| 463 | ⬜ | L2408 | `attr:title` | Nach unten verschieben | `folders.nach_unten_verschieben` |
| 464 | ⬜ | L2416 | `attr:title` | Abschnitt entfernen | `folders.abschnitt_entfernen` |
| 465 | ⬜ | L2423 | `attr:placeholder` | + Neuer Abschnitt (z.B. Zwischenprüfung, Abnahme)... | `folders.neuer_abschnitt_zb_zwischenprüfung_abna` |
| 466 | ⬜ | L2433 | `template_text` | + Hinzufügen | `folders.hinzufügen` |
| 467 | ⬜ | L2442 | `template_text` | Sichtbarkeit des Ordners | `folders.sichtbarkeit_des_ordners` |
| 468 | ⬜ | L2451 | `template_text` | 🔒 Privat (Standard) | `folders.privat_standard` |
| 469 | ⬜ | L2471 | `template_text` | Dieser Ordner ist standardmäßig privat. Nutze den Button | `folders.dieser_ordner_ist_standardmäßig_privat_n` |
| 470 | ⬜ | L2472 | `template_text` | , um Kollegen oder Partner gezielt per E-Mail einzuladen. | `folders.um_kollegen_oder_partner_gezielt_per_em` |
| 471 | ⬜ | L2477 | `template_text` | ⭐ Standard-Projekt festlegen (1 Projekt muss Standard sein) | `folders.standardprojekt_festlegen_1_projekt_mus` |
| 472 | ⬜ | L2487 | `template_text` | Ein Projekt innerhalb des Ordners muss als Standard definiert sein. | `folders.ein_projekt_innerhalb_des_ordners_muss_a` |
| 473 | ⬜ | L2493 | `template_text` | Zusatzfelder in diesem Ordner () | `folders.zusatzfelder_in_diesem_ordner` |
| 474 | ⬜ | L2500 | `template_text` | 0"                 class="text-[#00A3C4] hover:underline text-[11px] font-bold"  | `folders.0_classtext00a3c4_hoverunderline_text11p` |
| 475 | ⬜ | L2517 | `template_text` | Optionen: | `folders.optionen` |
| 476 | ⬜ | L2528 | `template_text` | Noch keine Zusatzfelder für diesen Ordner definiert. | `folders.noch_keine_zusatzfelder_für_diesen_ordne` |
| 477 | ⬜ | L2536 | `template_text` | Kollegen zu diesem Ordner einladen (Editor oder Viewer) | `folders.kollegen_zu_diesem_ordner_einladen_edito` |
| 478 | ⬜ | L2566 | `template_text` | Projektordner teilen | `folders.projektordner_teilen` |
| 479 | ⬜ | L2579 | `template_text` | Sichtbarkeit im Unternehmen | `folders.sichtbarkeit_im_unternehmen` |
| 480 | ⬜ | L2590 | `template_text` | Wenn für das Unternehmen freigegeben, haben alle Mitglieder des Unternehmens ()  | `folders.wenn_für_das_unternehmen_freigegeben_hab` |
| 481 | ⬜ | L2606 | `template_text` | Mitglied zum Ordner hinzufügen | `folders.mitglied_zum_ordner_hinzufügen` |
| 482 | ⬜ | L2612 | `template_text` | Kollege aus Unternehmen auswählen | `folders.kollege_aus_unternehmen_auswählen` |
| 483 | ⬜ | L2619 | `template_text` | -- Oder per E-Mail unten eingeben -- | `folders.oder_per_email_unten_eingeben` |
| 484 | ⬜ | L2648 | `template_text` | Editor (Bearbeiten) | `folders.editor_bearbeiten` |
| 485 | ⬜ | L2649 | `template_text` | Viewer (Nur Lesen) | `folders.viewer_nur_lesen` |
| 486 | ⬜ | L2664 | `template_text` | Gruppe zum Ordner berechtigen | `folders.gruppe_zum_ordner_berechtigen` |
| 487 | ⬜ | L2670 | `template_text` | Gruppe auswählen | `folders.gruppe_auswählen` |
| 488 | ⬜ | L2675 | `template_text` | -- Gruppe wählen -- | `folders.gruppe_wählen` |
| 489 | ⬜ | L2682 | `template_text` | Rolle für die Gruppe | `folders.rolle_für_die_gruppe` |
| 490 | ⬜ | L2688 | `template_text` | Editor (Bearbeiten) | `folders.editor_bearbeiten` |
| 491 | ⬜ | L2688 | `template_text` | Viewer (Nur Lesen) | `folders.viewer_nur_lesen` |
| 492 | ⬜ | L2688 | `template_text` | Admin (Vollzugriff) | `folders.admin_vollzugriff` |
| 493 | ⬜ | L2704 | `template_text` | Zugewiesene Gruppen: | `folders.zugewiesene_gruppen` |
| 494 | ⬜ | L2725 | `attr:title` | Gruppe entfernen | `folders.gruppe_entfernen` |
| 495 | ⬜ | L2731 | `template_text` | Personen mit Zugriff () | `folders.personen_mit_zugriff` |
| 496 | ⬜ | L2737 | `template_text` | Lade Mitglieder... | `folders.lade_mitglieder` |
| 497 | ⬜ | L2773 | `attr:title` | Mitglied entfernen | `folders.mitglied_entfernen` |
| 498 | ⬜ | L2795 | `template_text` | Gib den Ordnernamen | `folders.gib_den_ordnernamen` |
| 499 | ⬜ | L2798 | `template_text` | zur Bestätigung ein: | `folders.zur_bestätigung_ein` |
| 500 | ⬜ | L2904 | `template_text` | Definiere, unter welcher Bedingung das Standardfeld «» für Aufgaben in «» sichtb | `folders.definiere_unter_welcher_bedingung_das_st` |
| 501 | ⬜ | L2907 | `template_text` | Definiere ein Attribut für Aufgaben oder Projekte in «». | `folders.definiere_ein_attribut_für_aufgaben_oder` |
| 502 | ⬜ | L2923 | `template_text` | Gültigkeitsbereich | `folders.gültigkeitsbereich` |
| 503 | ⬜ | L2944 | `template_text` | Feld-Bezeichnung (Label) | `folders.feldbezeichnung_label` |
| 504 | ⬜ | L2960 | `template_text` | Textzeile (kurz) | `folders.textzeile_kurz` |
| 505 | ⬜ | L2963 | `template_text` | Mehrzeiliger Text / Notizfeld | `folders.mehrzeiliger_text_notizfeld` |
| 506 | ⬜ | L2966 | `template_text` | Auswahlliste (Dropdown) | `folders.auswahlliste_dropdown` |
| 507 | ⬜ | L2968 | `template_text` | Ja / Nein (Checkbox) | `folders.ja_nein_checkbox` |
| 508 | ⬜ | L2969 | `template_text` | Link / URL | `folders.link_url` |
| 509 | ⬜ | L2978 | `template_text` | Optionen für Auswahlliste | `folders.optionen_für_auswahlliste` |
| 510 | ⬜ | L3006 | `attr:placeholder` | + Option eingeben und Enter drücken | `folders.option_eingeben_und_enter_drücken` |
| 511 | ⬜ | L3009 | `template_text` | Hinzufügen | `folders.hinzufügen` |
| 512 | ⬜ | L3017 | `template_text` | Pflichtfeld (Eingabe erforderlich) | `folders.pflichtfeld_eingabe_erforderlich` |
| 513 | ⬜ | L3027 | `template_text` | Bedingte Sichtbarkeit (Logik) | `folders.bedingte_sichtbarkeit_logik` |
| 514 | ⬜ | L3027 | `template_text` | Dieses Feld nur anzeigen, wenn eine Bedingung erfüllt ist. | `folders.dieses_feld_nur_anzeigen_wenn_eine_bedin` |
| 515 | ⬜ | L3039 | `template_text` | Nur anzeigen wenn dieses Feld: | `folders.nur_anzeigen_wenn_dieses_feld` |
| 516 | ⬜ | L3046 | `template_text` | -- Feld auswählen -- | `folders.feld_auswählen` |
| 517 | ⬜ | L3047 | `template_text` | 🔄 Status | `folders.status` |
| 518 | ⬜ | L3047 | `attr:label` | Standard Aufgabenfelder | `folders.standard_aufgabenfelder` |
| 519 | ⬜ | L3048 | `template_text` | ⚡ Priorität | `folders.priorität` |
| 520 | ⬜ | L3048 | `template_text` | 📅 Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 521 | ⬜ | L3057 | `attr:label` | Benutzerdefinierte Felder | `folders.benutzerdefinierte_felder` |
| 522 | ⬜ | L3069 | `template_text` | Diesen Wert hat: | `folders.diesen_wert_hat` |
| 523 | ⬜ | L3076 | `template_text` | Zu erledigen (todo) | `folders.zu_erledigen_todo` |
| 524 | ⬜ | L3077 | `template_text` | In Bearbeitung (in_progress) | `folders.in_bearbeitung_inprogress` |
| 525 | ⬜ | L3078 | `template_text` | In Prüfung (review) | `folders.in_prüfung_review` |
| 526 | ⬜ | L3079 | `template_text` | Abgeschlossen (done) | `folders.abgeschlossen_done` |
| 527 | ⬜ | L3098 | `template_text` | Jemand zugewiesen | `folders.jemand_zugewiesen` |
| 528 | ⬜ | L3099 | `template_text` | Niemand zugewiesen (Offen) | `folders.niemand_zugewiesen_offen` |
| 529 | ⬜ | L3108 | `template_text` | Datum ist gesetzt | `folders.datum_ist_gesetzt` |
| 530 | ⬜ | L3111 | `template_text` | Kein Datum gesetzt | `folders.kein_datum_gesetzt` |
| 531 | ⬜ | L3112 | `template_text` | Heute fällig | `folders.heute_fällig` |
| 532 | ⬜ | L3112 | `template_text` | Überfällig | `folders.überfällig` |
| 533 | ⬜ | L3125 | `template_text` | Farbe ist gesetzt | `folders.farbe_ist_gesetzt` |
| 534 | ⬜ | L3127 | `template_text` | Keine Farbe gesetzt | `folders.keine_farbe_gesetzt` |
| 535 | ⬜ | L3135 | `template_text` | Mindestens ein Tag vorhanden | `folders.mindestens_ein_tag_vorhanden` |
| 536 | ⬜ | L3137 | `template_text` | Keine Tags vorhanden | `folders.keine_tags_vorhanden` |
| 537 | ⬜ | L3146 | `template_text` | -- Option wählen -- | `folders.option_wählen` |
| 538 | ⬜ | L3155 | `attr:placeholder` | Erwarteter Wert... | `folders.erwarteter_wert` |
| 539 | ⬜ | L3191 | `template_text` | Bedingte Logik festlegen | `folders.bedingte_logik_festlegen` |
| 540 | ⬜ | L3195 | `template_text` | Feld nur anzeigen, wenn eine Bedingung erfüllt ist. | `folders.feld_nur_anzeigen_wenn_eine_bedingung_er` |
| 541 | ⬜ | L3200 | `template_text` | Abhängig von Feld: | `folders.abhängig_von_feld` |
| 542 | ⬜ | L3208 | `attr:label` | Standard Aufgabenfelder | `folders.standard_aufgabenfelder` |
| 543 | ⬜ | L3209 | `template_text` | 🔄 Status | `folders.status` |
| 544 | ⬜ | L3210 | `template_text` | ⚡ Priorität | `folders.priorität` |
| 545 | ⬜ | L3210 | `template_text` | 📅 Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 546 | ⬜ | L3214 | `attr:label` | Weitere Felder | `folders.weitere_felder` |
| 547 | ⬜ | L3223 | `template_text` | Bedingungswert (Muss übereinstimmen): | `folders.bedingungswert_muss_übereinstimmen` |
| 548 | ⬜ | L3232 | `template_text` | Zu erledigen (todo) | `folders.zu_erledigen_todo` |
| 549 | ⬜ | L3232 | `template_text` | In Bearbeitung (in_progress) | `folders.in_bearbeitung_inprogress` |
| 550 | ⬜ | L3232 | `template_text` | In Prüfung (review) | `folders.in_prüfung_review` |
| 551 | ⬜ | L3234 | `template_text` | Abgeschlossen (done) | `folders.abgeschlossen_done` |
| 552 | ⬜ | L3252 | `template_text` | Jemand zugewiesen | `folders.jemand_zugewiesen` |
| 553 | ⬜ | L3252 | `template_text` | Niemand zugewiesen | `folders.niemand_zugewiesen` |
| 554 | ⬜ | L3263 | `template_text` | Datum gesetzt | `folders.datum_gesetzt` |
| 555 | ⬜ | L3265 | `template_text` | Kein Datum gesetzt | `folders.kein_datum_gesetzt` |
| 556 | ⬜ | L3266 | `template_text` | Heute fällig | `folders.heute_fällig` |
| 557 | ⬜ | L3266 | `template_text` | Überfällig | `folders.überfällig` |
| 558 | ⬜ | L3274 | `template_text` | Farbe gesetzt | `folders.farbe_gesetzt` |
| 559 | ⬜ | L3275 | `template_text` | Keine Farbe gesetzt | `folders.keine_farbe_gesetzt` |
| 560 | ⬜ | L3286 | `template_text` | Tags vorhanden | `folders.tags_vorhanden` |
| 561 | ⬜ | L3287 | `template_text` | Keine Tags | `folders.keine_tags` |
| 562 | ⬜ | L3293 | `attr:placeholder` | Erwarteter Wert... | `folders.erwarteter_wert` |
| 563 | ⬜ | L3304 | `template_text` | Logik entfernen | `folders.logik_entfernen` |
| 564 | ⬜ | L3313 | `template_text` | Übernehmen | `folders.übernehmen` |
| 565 | ⬜ | L3327 | `template_text` | Gilt für Ordner | `folders.gilt_für_ordner` |
| 566 | ⬜ | L3329 | `template_text` | und alle zugehörigen Projekte. | `folders.und_alle_zugehörigen_projekte` |
| 567 | ⬜ | L3340 | `template_text` | Name / Vollständiger Name | `folders.name_vollständiger_name` |
| 568 | ⬜ | L3353 | `template_text` | Firma / Unternehmen | `folders.firma_unternehmen` |
| 569 | ⬜ | L3364 | `template_text` | Funktion / Rolle | `folders.funktion_rolle` |
| 570 | ⬜ | L3375 | `template_text` | Mobiltelefon (WhatsApp) | `folders.mobiltelefon_whatsapp` |
| 571 | ⬜ | L3384 | `template_text` | Telefon Festnetz | `folders.telefon_festnetz` |
| 572 | ⬜ | L3402 | `template_text` | Strasse & Hausnr. | `folders.strasse_hausnr` |
| 573 | ⬜ | L3407 | `attr:placeholder` | Hauptstrasse 12 | `folders.hauptstrasse_12` |
| 574 | ⬜ | L3412 | `template_text` | PLZ & Ort | `folders.plz_ort` |
| 575 | ⬜ | L3424 | `template_text` | Notizen / Bemerkungen | `folders.notizen_bemerkungen` |
| 576 | ⬜ | L3428 | `attr:placeholder` | Wichtige Hinweise oder Erreichbarkeit... | `folders.wichtige_hinweise_oder_erreichbarkeit` |
| 577 | ⬜ | L3724 | `script_literal` | Bestätigen | `folders.bestätigen` |
| 578 | ⬜ | L3763 | `script_literal` | Löschen | `folders.löschen` |
| 579 | ⬜ | L3779 | `script_literal` | Fehler beim Ausführen der Aktion | `folders.fehler_beim_ausführen_der_aktion` |
| 580 | ⬜ | L4004 | `script_literal` | Aufgaben-Verknüpfung aktualisiert | `folders.aufgabenverknüpfung_aktualisiert` |
| 581 | ⬜ | L4111 | `script_literal` | Journal-Eintrag löschen | `folders.journaleintrag_löschen` |
| 582 | ⬜ | L4112 | `script_literal` | Dieser Vorgang kann nicht rückgängig gemacht werden | `folders.dieser_vorgang_kann_nicht_rückgängig_gem` |
| 583 | ⬜ | L4113 | `script_literal` | Möchtest du diesen Journal-Eintrag wirklich unwiderruflich löschen? | `folders.möchtest_du_diesen_journaleintrag_wirkli` |
| 584 | ⬜ | L4114 | `script_literal` | Eintrag löschen | `folders.eintrag_löschen` |
| 585 | ⬜ | L4122 | `script_literal` | Journal-Eintrag erfolgreich gelöscht | `folders.journaleintrag_erfolgreich_gelöscht` |
| 586 | ⬜ | L4148 | `script_literal` | Priorität | `folders.priorität` |
| 587 | ⬜ | L4150 | `script_literal` | Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 588 | ⬜ | L4177 | `script_literal` | Priorität | `folders.priorität` |
| 589 | ⬜ | L4179 | `script_literal` | Fälligkeitsdatum | `folders.fälligkeitsdatum` |
| 590 | ⬜ | L4327 | `script_literal` | Benutzerdefiniertes Feld löschen | `folders.benutzerdefiniertes_feld_löschen` |
| 591 | ⬜ | L4329 | `script_literal` | Dieses benutzerdefinierte Feld wirklich löschen? Alle zugewiesenen Werte in den  | `folders.dieses_benutzerdefinierte_feld_wirklich` |
| 592 | ⬜ | L4330 | `script_literal` | Feld löschen | `folders.feld_löschen` |
| 593 | ⬜ | L4338 | `script_literal` | Benutzerdefiniertes Feld gelöscht | `folders.benutzerdefiniertes_feld_gelöscht` |
| 594 | ⬜ | L4463 | `script_literal` | Möchtest du den Kontakt | `folders.möchtest_du_den_kontakt` |
| 595 | ⬜ | L4472 | `script_literal` | Kontakt erfolgreich gelöscht | `folders.kontakt_erfolgreich_gelöscht` |
| 596 | ⬜ | L4801 | `script_literal` | Der eingegebene Ordnername stimmt nicht überein. | `folders.der_eingegebene_ordnername_stimmt_nicht` |
| 597 | ⬜ | L4814 | `script_literal` | Fehler beim Löschen des Ordners | `folders.fehler_beim_löschen_des_ordners` |
| 598 | ⬜ | L4852 | `script_literal` | Fehler beim Löschen des Projekts | `folders.fehler_beim_löschen_des_projekts` |
| 599 | ⬜ | L4922 | `script_literal` | Fehler beim Ändern der Sichtbarkeit | `folders.fehler_beim_ändern_der_sichtbarkeit` |
| 600 | ⬜ | L4945 | `script_literal` | Fehler beim Hinzufügen des Mitglieds | `folders.fehler_beim_hinzufügen_des_mitglieds` |
| 601 | ⬜ | L4955 | `script_literal` | Möchtest du dieses Mitglied wirklich aus dem Projektordner entfernen? | `folders.möchtest_du_dieses_mitglied_wirklich_aus` |
| 602 | ⬜ | L5027 | `script_literal` | Möchtest du diese Gruppe wirklich von diesem Ordner entfernen? | `folders.möchtest_du_diese_gruppe_wirklich_von_di` |
| 603 | ⬜ | L5407 | `script_literal` | Währung | `folders.währung` |
| 604 | ⬜ | L5409 | `script_literal` | Sanierung Bürogebäude Nord | `folders.sanierung_bürogebäude_nord` |
| 605 | ⬜ | L5508 | `script_literal` | fällig | `folders.fällig` |
| 606 | ⬜ | L5516 | `script_literal` | währung | `folders.währung` |
| 607 | ⬜ | L5599 | `script_literal` | Konnte keine gültigen Tabellendaten erkennen. Bitte stelle sicher, dass eine Kop | `folders.konnte_keine_gültigen_tabellendaten_erke` |
| 608 | ⬜ | L5617 | `script_literal` | Excel-Bibliothek (XLSX) steht nicht zur Verfügung. | `folders.excelbibliothek_xlsx_steht_nicht_zur_ver` |
| 609 | ⬜ | L5624 | `script_literal` | Excel-Dienstprogramme unvollständig geladen. | `folders.exceldienstprogramme_unvollständig_gelad` |
| 610 | ⬜ | L5628 | `script_literal` | Die Datei enthält keine Datenzeilen (mindestens 1 Kopfzeile und 1 Datenzeile erf | `folders.die_datei_enthält_keine_datenzeilen_mind` |
| 611 | ⬜ | L5814 | `script_literal` | öffentlich | `folders.öffentlich` |
| 612 | ⬜ | L5874 | `script_literal` | Keine gültigen Projekte in der Datei gefunden. | `folders.keine_gültigen_projekte_in_der_datei_gef` |

### 📄 `pages/projects/[id].vue` (75 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 613 | ⬜ | L4 | `template_text` | Lade Projektdaten... | `projects.lade_projektdaten` |
| 614 | ⬜ | L5783 | `script_literal` | Bestätigen | `projects.bestätigen` |
| 615 | ⬜ | L5822 | `script_literal` | Löschen | `projects.löschen` |
| 616 | ⬜ | L5838 | `script_literal` | Fehler beim Ausführen der Aktion | `projects.fehler_beim_ausführen_der_aktion` |
| 617 | ⬜ | L5891 | `script_literal` | Projekt-Export ist exklusiv für den Enterprise-Tarif verfügbar. | `projects.projektexport_ist_exklusiv_für_den_enter` |
| 618 | ⬜ | L5919 | `script_literal` | Projekt-Export ist exklusiv für den Enterprise-Tarif verfügbar. | `projects.projektexport_ist_exklusiv_für_den_enter` |
| 619 | ⬜ | L6017 | `script_literal` | Fehler beim Zusammenführen des Kontakts | `projects.fehler_beim_zusammenführen_des_kontakts` |
| 620 | ⬜ | L6299 | `script_literal` | ist in aktiven Aufgaben oder im Projekt ausgefüllt und kann solange nicht gelösc | `projects.ist_in_aktiven_aufgaben_oder_im_projekt` |
| 621 | ⬜ | L6304 | `script_literal` | Zusatzfeld löschen | `projects.zusatzfeld_löschen` |
| 622 | ⬜ | L6306 | `script_literal` | Möchtest du das Zusatzfeld | `projects.möchtest_du_das_zusatzfeld` |
| 623 | ⬜ | L6306 | `script_literal` | wirklich löschen? | `projects.wirklich_löschen` |
| 624 | ⬜ | L6307 | `script_literal` | Feld löschen | `projects.feld_löschen` |
| 625 | ⬜ | L6316 | `script_literal` | Zusatzfeld erfolgreich gelöscht | `projects.zusatzfeld_erfolgreich_gelöscht` |
| 626 | ⬜ | L6537 | `script_literal` | Fehler beim Aktualisieren der Aufgabenverknüpfung | `projects.fehler_beim_aktualisieren_der_aufgabenve` |
| 627 | ⬜ | L7236 | `script_literal` | Behörden & Ämter | `projects.behörden_ämter` |
| 628 | ⬜ | L7236 | `script_literal` | Bauträger & Eigentümer | `projects.bauträger_eigentümer` |
| 629 | ⬜ | L7244 | `script_literal` | Du bist ein intelligenter Assistent für Baudokumentation und Kontaktmanagement.  | `projects.du_bist_ein_intelligenter_assistent_für` |
| 630 | ⬜ | L7258 | `script_literal` | KI-Rückgabe konnte nicht als JSON interpretiert werden. | `projects.kirückgabe_konnte_nicht_als_json_interpr` |
| 631 | ⬜ | L7279 | `script_literal` | ✓ Daten erfolgreich erkannt und ins Formular übertragen! | `projects.daten_erfolgreich_erkannt_und_ins_formu` |
| 632 | ⬜ | L7333 | `script_literal` | Duplikat erkannt: Ein ähnlicher Kontakt existiert bereits in diesem Projekt. | `projects.duplikat_erkannt_ein_ähnlicher_kontakt_e` |
| 633 | ⬜ | L7347 | `script_literal` | Möchtest du den Kontakt | `projects.möchtest_du_den_kontakt` |
| 634 | ⬜ | L7347 | `script_literal` | wirklich löschen? | `projects.wirklich_löschen` |
| 635 | ⬜ | L7348 | `script_literal` | Kontakt löschen | `projects.kontakt_löschen` |
| 636 | ⬜ | L7356 | `script_literal` | Kontakt erfolgreich gelöscht | `projects.kontakt_erfolgreich_gelöscht` |
| 637 | ⬜ | L7419 | `script_literal` | Bitte eine Dauer grösser als 0 angeben. | `projects.bitte_eine_dauer_grösser_als_0_angeben` |
| 638 | ⬜ | L7458 | `script_literal` | Bitte eine Dauer grösser als 0 angeben. | `projects.bitte_eine_dauer_grösser_als_0_angeben` |
| 639 | ⬜ | L7486 | `script_literal` | Zeiteintrag löschen | `projects.zeiteintrag_löschen` |
| 640 | ⬜ | L7487 | `script_literal` | Dieser Vorgang kann nicht rückgängig gemacht werden | `projects.dieser_vorgang_kann_nicht_rückgängig_gem` |
| 641 | ⬜ | L7488 | `script_literal` | Möchtest du diesen Zeiteintrag wirklich löschen? | `projects.möchtest_du_diesen_zeiteintrag_wirklich` |
| 642 | ⬜ | L7489 | `script_literal` | Zeiteintrag löschen | `projects.zeiteintrag_löschen` |
| 643 | ⬜ | L7503 | `script_literal` | Zeiteintrag gelöscht | `projects.zeiteintrag_gelöscht` |
| 644 | ⬜ | L7512 | `script_literal` | Bitte eine Dauer grösser als 0 angeben. | `projects.bitte_eine_dauer_grösser_als_0_angeben` |
| 645 | ⬜ | L7608 | `script_literal` | Fehler beim Löschen des Projekts | `projects.fehler_beim_löschen_des_projekts` |
| 646 | ⬜ | L7676 | `script_literal` | Feld löschen | `projects.feld_löschen` |
| 647 | ⬜ | L7677 | `script_literal` | Möchtest du dieses benutzerdefinierte Feld wirklich löschen? | `projects.möchtest_du_dieses_benutzerdefinierte_fe` |
| 648 | ⬜ | L7678 | `script_literal` | Feld löschen | `projects.feld_löschen` |
| 649 | ⬜ | L7686 | `script_literal` | Feld gelöscht | `projects.feld_gelöscht` |
| 650 | ⬜ | L7795 | `script_literal` | Fehler beim Hinzufügen | `projects.fehler_beim_hinzufügen` |
| 651 | ⬜ | L7804 | `script_literal` | Möchtest du den Abschnitt | `projects.möchtest_du_den_abschnitt` |
| 652 | ⬜ | L7804 | `script_literal` | wirklich löschen? | `projects.wirklich_löschen` |
| 653 | ⬜ | L7806 | `script_literal` | Abschnitt löschen | `projects.abschnitt_löschen` |
| 654 | ⬜ | L7809 | `script_literal` | Abschnitt löschen | `projects.abschnitt_löschen` |
| 655 | ⬜ | L7820 | `script_literal` | Abschnitt gelöscht | `projects.abschnitt_gelöscht` |
| 656 | ⬜ | L7933 | `script_literal` | Projekt abschließen | `projects.projekt_abschließen` |
| 657 | ⬜ | L7935 | `script_literal` | Alle Aufgaben in diesem Projekt sind erledigt! Möchtest du das gesamte Projekt a | `projects.alle_aufgaben_in_diesem_projekt_sind_erl` |
| 658 | ⬜ | L7936 | `script_literal` | Projekt abschließen | `projects.projekt_abschließen` |
| 659 | ⬜ | L7976 | `script_literal` | Fehler beim Ändern des Aufgabenstatus | `projects.fehler_beim_ändern_des_aufgabenstatus` |
| 660 | ⬜ | L8233 | `script_literal` | Aufgabe abschließen | `projects.aufgabe_abschließen` |
| 661 | ⬜ | L8235 | `script_literal` | Alle Unterpunkte und Checklistenpunkte sind erledigt. Möchtest du diese Aufgabe  | `projects.alle_unterpunkte_und_checklistenpunkte_s` |
| 662 | ⬜ | L8236 | `script_literal` | Aufgabe abschließen | `projects.aufgabe_abschließen` |
| 663 | ⬜ | L8343 | `script_literal` | hast du keine Berechtigung, Aufgaben zu löschen. | `projects.hast_du_keine_berechtigung_aufgaben_zu_l` |
| 664 | ⬜ | L8347 | `script_literal` | Aufgabe löschen | `projects.aufgabe_löschen` |
| 665 | ⬜ | L8349 | `script_literal` | Möchtest du diese Aufgabe wirklich unwiderruflich löschen? | `projects.möchtest_du_diese_aufgabe_wirklich_unwid` |
| 666 | ⬜ | L8350 | `script_literal` | Aufgabe löschen | `projects.aufgabe_löschen` |
| 667 | ⬜ | L8356 | `script_literal` | Aufgabe erfolgreich gelöscht | `projects.aufgabe_erfolgreich_gelöscht` |
| 668 | ⬜ | L8396 | `script_literal` | ist größer als 10 MB. | `projects.ist_größer_als_10_mb` |
| 669 | ⬜ | L8445 | `script_literal` | Möchtest du diese Datei wirklich entfernen? | `projects.möchtest_du_diese_datei_wirklich_entfern` |
| 670 | ⬜ | L8531 | `script_literal` | Excel-Bibliothek (XLSX) steht nicht zur Verfügung. | `projects.excelbibliothek_xlsx_steht_nicht_zur_ver` |
| 671 | ⬜ | L8538 | `script_literal` | Excel-Dienstprogramme unvollständig geladen. | `projects.exceldienstprogramme_unvollständig_gelad` |
| 672 | ⬜ | L8547 | `script_literal` | Die ausgewählte Datei ist leer. | `projects.die_ausgewählte_datei_ist_leer` |
| 673 | ⬜ | L8583 | `script_literal` | fällig | `projects.fällig` |
| 674 | ⬜ | L8657 | `script_literal` | Bitte wähle einen Ziel-Abschnitt für die Aufgaben aus. | `projects.bitte_wähle_einen_zielabschnitt_für_die` |
| 675 | ⬜ | L8720 | `script_literal` | prüf | `projects.prüf` |
| 676 | ⬜ | L8787 | `script_literal` | Fehler während des Imports: | `projects.fehler_während_des_imports` |
| 677 | ⬜ | L8831 | `script_literal` | hast du keine Berechtigung, Aufgaben zu löschen. | `projects.hast_du_keine_berechtigung_aufgaben_zu_l` |
| 678 | ⬜ | L8835 | `script_literal` | Aufgabe löschen | `projects.aufgabe_löschen` |
| 679 | ⬜ | L8836 | `script_literal` | Möchtest du diese Aufgabe wirklich löschen? | `projects.möchtest_du_diese_aufgabe_wirklich_lösch` |
| 680 | ⬜ | L8837 | `script_literal` | Aufgabe löschen | `projects.aufgabe_löschen` |
| 681 | ⬜ | L8846 | `script_literal` | Aufgabe erfolgreich gelöscht | `projects.aufgabe_erfolgreich_gelöscht` |
| 682 | ⬜ | L9125 | `script_literal` | Bitte gib einen Titel für den Journaleintrag an. | `projects.bitte_gib_einen_titel_für_den_journalein` |
| 683 | ⬜ | L9184 | `script_literal` | Bitte gib einen Inhalt für die Notiz an. | `projects.bitte_gib_einen_inhalt_für_die_notiz_an` |
| 684 | ⬜ | L9250 | `script_literal` | Journaleintrag löschen | `projects.journaleintrag_löschen` |
| 685 | ⬜ | L9253 | `script_literal` | Eintrag löschen | `projects.eintrag_löschen` |
| 686 | ⬜ | L9268 | `script_literal` | Journaleintrag erfolgreich gelöscht | `projects.journaleintrag_erfolgreich_gelöscht` |
| 687 | ⬜ | L9504 | `script_literal` | Mitglied erfolgreich hinzugefügt! | `projects.mitglied_erfolgreich_hinzugefügt` |

---

## Phase 4: Admin & Firmen-Verwaltung
*379 Strings in 2 Dateien*

### 📄 `pages/admin/index.vue` (338 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 688 | ⬜ | L33 | `template_text` | Neuen Benutzer anlegen | `admin.neuen_benutzer_anlegen` |
| 689 | ⬜ | L42 | `template_text` | Neues Unternehmen | `admin.neues_unternehmen` |
| 690 | ⬜ | L60 | `template_text` | Neue Vorlage | `admin.neue_vorlage` |
| 691 | ⬜ | L90 | `template_text` | Kunden & User | `admin.kunden_user` |
| 692 | ⬜ | L109 | `template_text` | In Abschnitten gepflegt | `admin.in_abschnitten_gepflegt` |
| 693 | ⬜ | L114 | `template_text` | Journal-Einträge | `admin.journaleinträge` |
| 694 | ⬜ | L116 | `template_text` | Aktivitätsnotizen | `admin.aktivitätsnotizen` |
| 695 | ⬜ | L124 | `template_text` | Organisationen & Mandanten | `admin.organisationen_mandanten` |
| 696 | ⬜ | L127 | `template_text` | Mitarbeiter zugewiesen | `admin.mitarbeiter_zugewiesen` |
| 697 | ⬜ | L136 | `template_text` | Uploads freigegeben | `admin.uploads_freigegeben` |
| 698 | ⬜ | L143 | `template_text` | Monatlicher Umsatz (MRR) | `admin.monatlicher_umsatz_mrr` |
| 699 | ⬜ | L146 | `template_text` | Wiederkehrender monatlicher Umsatz | `admin.wiederkehrender_monatlicher_umsatz` |
| 700 | ⬜ | L149 | `template_text` | Aktive Abonnements | `admin.aktive_abonnements` |
| 701 | ⬜ | L154 | `template_text` | Unternehmen & PRO-Nutzer | `admin.unternehmen_pronutzer` |
| 702 | ⬜ | L157 | `template_text` | Kostenpflichtige Sitze | `admin.kostenpflichtige_sitze` |
| 703 | ⬜ | L161 | `template_text` | Zugewiesene Mitarbeiter-Lizenzen | `admin.zugewiesene_mitarbeiterlizenzen` |
| 704 | ⬜ | L170 | `template_text` | Vorlagen Gesamt | `admin.vorlagen_gesamt` |
| 705 | ⬜ | L172 | `template_text` | Systemweite Vorlagen | `admin.systemweite_vorlagen` |
| 706 | ⬜ | L174 | `template_text` | Job & Gewerblich | `admin.job_gewerblich` |
| 707 | ⬜ | L178 | `template_text` | Baufirmen & Business | `admin.baufirmen_business` |
| 708 | ⬜ | L184 | `template_text` | Bauherren & Privatnutzer | `admin.bauherren_privatnutzer` |
| 709 | ⬜ | L197 | `template_text` | Aktive Vorlagen | `admin.aktive_vorlagen` |
| 710 | ⬜ | L201 | `template_text` | System-Trigger bereit | `admin.systemtrigger_bereit` |
| 711 | ⬜ | L205 | `template_text` | Versendete E-Mails | `admin.versendete_emails` |
| 712 | ⬜ | L209 | `template_text` | Protokollierte Einträge | `admin.protokollierte_einträge` |
| 713 | ⬜ | L216 | `template_text` | Webseite Status | `admin.webseite_status` |
| 714 | ⬜ | L218 | `template_text` | Öffentliche Landingpage | `admin.öffentliche_landingpage` |
| 715 | ⬜ | L222 | `template_text` | Ankündigung | `admin.ankündigung` |
| 716 | ⬜ | L226 | `template_text` | Live In-App Hinweise | `admin.live_inapp_hinweise` |
| 717 | ⬜ | L230 | `template_text` | SEO & Metadaten | `admin.seo_metadaten` |
| 718 | ⬜ | L233 | `template_text` | Google & OpenGraph | `admin.google_opengraph` |
| 719 | ⬜ | L241 | `template_text` | Audit Log-Einträge | `admin.audit_logeinträge` |
| 720 | ⬜ | L242 | `template_text` | Systemweit protokolliert | `admin.systemweit_protokolliert` |
| 721 | ⬜ | L244 | `template_text` | Aktivitäts-Typen | `admin.aktivitätstypen` |
| 722 | ⬜ | L248 | `template_text` | Verschiedene Event-Klassen | `admin.verschiedene_eventklassen` |
| 723 | ⬜ | L250 | `template_text` | Security Standard | `admin.security_standard` |
| 724 | ⬜ | L252 | `template_text` | Zero-Trust Audit | `admin.zerotrust_audit` |
| 725 | ⬜ | L254 | `template_text` | Revisionssicher geloggt | `admin.revisionssicher_geloggt` |
| 726 | ⬜ | L263 | `template_text` | OpenRouter LLM | `admin.openrouter_llm` |
| 727 | ⬜ | L265 | `template_text` | Audio Transkriptor | `admin.audio_transkriptor` |
| 728 | ⬜ | L268 | `template_text` | Whisper Voice Engine | `admin.whisper_voice_engine` |
| 729 | ⬜ | L270 | `template_text` | Pro & Enterprise | `admin.pro_enterprise` |
| 730 | ⬜ | L271 | `template_text` | Vollzugriff aktiv | `admin.vollzugriff_aktiv` |
| 731 | ⬜ | L273 | `template_text` | Tarif-Gating aktiv | `admin.tarifgating_aktiv` |
| 732 | ⬜ | L278 | `template_text` | Alle registrierten Kunden und Benutzer | `admin.alle_registrierten_kunden_und_benutzer` |
| 733 | ⬜ | L282 | `template_text` | Verwalte Berechtigungen, Pro-Status, Subrollen und Firmenzuweisungen. | `admin.verwalte_berechtigungen_prostatus_subrol` |
| 734 | ⬜ | L285 | `template_text` | + Neuen Benutzer anlegen | `admin.neuen_benutzer_anlegen` |
| 735 | ⬜ | L290 | `template_text` | Name & E-Mail | `admin.name_email` |
| 736 | ⬜ | L292 | `template_text` | Unternehmen / Organisation | `admin.unternehmen_organisation` |
| 737 | ⬜ | L296 | `template_text` | Plan & Status | `admin.plan_status` |
| 738 | ⬜ | L297 | `template_text` | Rolle & Berechtigungen | `admin.rolle_berechtigungen` |
| 739 | ⬜ | L311 | `template_text` | Privatkunde (Einzelbenutzer) | `admin.privatkunde_einzelbenutzer` |
| 740 | ⬜ | L355 | `attr:title` | Benutzer-Einstellungen bearbeiten (Plan, Rolle, Subrollen, Firma) | `admin.benutzereinstellungen_bearbeiten_plan_ro` |
| 741 | ⬜ | L370 | `template_text` | Unternehmen, Mandanten & B2B-Kunden | `admin.unternehmen_mandanten_b2bkunden` |
| 742 | ⬜ | L376 | `template_text` | Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien. | `admin.verwalte_subscriptionpläne_uploadrestrik` |
| 743 | ⬜ | L388 | `template_text` | Nutzer & Ordner | `admin.nutzer_ordner` |
| 744 | ⬜ | L392 | `template_text` | Dateiuploads (Zero Trust) | `admin.dateiuploads_zero_trust` |
| 745 | ⬜ | L407 | `template_text` | Starter Plan | `admin.starter_plan` |
| 746 | ⬜ | L409 | `template_text` | Pro Plan | `admin.pro_plan` |
| 747 | ⬜ | L411 | `template_text` | Enterprise Plan | `admin.enterprise_plan` |
| 748 | ⬜ | L435 | `template_text` | Abonnements & Bestellungen | `admin.abonnements_bestellungen` |
| 749 | ⬜ | L440 | `template_text` | Übersicht aller aktiven Firmenabos, Einzellizenzen und Zahlungsmodalitäten. | `admin.übersicht_aller_aktiven_firmenabos_einze` |
| 750 | ⬜ | L451 | `template_text` | Lade Bestelldaten und Abonnements... | `admin.lade_bestelldaten_und_abonnements` |
| 751 | ⬜ | L454 | `template_text` | Keine aktiven Bestellungen oder Abonnements hinterlegt. | `admin.keine_aktiven_bestellungen_oder_abonneme` |
| 752 | ⬜ | L465 | `template_text` | Kunde / Organisation | `admin.kunde_organisation` |
| 753 | ⬜ | L466 | `template_text` | Plan / Tarif | `admin.plan_tarif` |
| 754 | ⬜ | L498 | `template_text` | / Monat | `admin.monat` |
| 755 | ⬜ | L525 | `template_text` | Mitarbeiter zu  einladen | `admin.mitarbeiter_zu_einladen` |
| 756 | ⬜ | L529 | `template_text` | Bereits registrierte Nutzer werden sofort dem Unternehmen zugewiesen. Nicht regi | `admin.bereits_registrierte_nutzer_werden_sofor` |
| 757 | ⬜ | L550 | `template_text` | Rolle im Unternehmen | `admin.rolle_im_unternehmen` |
| 758 | ⬜ | L558 | `template_text` | Mitglied (Member) | `admin.mitglied_member` |
| 759 | ⬜ | L558 | `template_text` | Company Administrator | `admin.company_administrator` |
| 760 | ⬜ | L569 | `template_text` | Einladung erfolgreich generiert! | `admin.einladung_erfolgreich_generiert` |
| 761 | ⬜ | L571 | `template_text` | Für nicht registrierte Nutzer kann dieser direkte Registrierungslink weitergegeb | `admin.für_nicht_registrierte_nutzer_kann_diese` |
| 762 | ⬜ | L587 | `template_text` | Offene Einladungen | `admin.offene_einladungen` |
| 763 | ⬜ | L592 | `template_text` | Keine offenen Einladungen vorhanden. | `admin.keine_offenen_einladungen_vorhanden` |
| 764 | ⬜ | L605 | `template_text` | Rolle:  • Erstellt: | `admin.rolle_erstellt` |
| 765 | ⬜ | L613 | `template_text` | Projekt-Vorlagen (Gewerbe, Jobs & Privat) | `admin.projektvorlagen_gewerbe_jobs_privat` |
| 766 | ⬜ | L615 | `template_text` | Verwalte strukturierte Vorlagen mit Standard-Abschnitten und benutzerdefinierten | `admin.verwalte_strukturierte_vorlagen_mit_stan` |
| 767 | ⬜ | L627 | `template_text` | + Neue Vorlage erstellen | `admin.neue_vorlage_erstellen` |
| 768 | ⬜ | L637 | `template_text` | Alle Vorlagen () | `admin.alle_vorlagen` |
| 769 | ⬜ | L650 | `template_text` | Job & Gewerbe () | `admin.job_gewerbe` |
| 770 | ⬜ | L658 | `template_text` | Privat () | `admin.privat` |
| 771 | ⬜ | L659 | `attr:placeholder` | Vorlage suchen... | `admin.vorlage_suchen` |
| 772 | ⬜ | L668 | `template_text` | Keine Vorlagen gefunden | `admin.keine_vorlagen_gefunden` |
| 773 | ⬜ | L670 | `template_text` | Erstelle deine erste Vorlage oder passe den Suchfilter an. | `admin.erstelle_deine_erste_vorlage_oder_passe` |
| 774 | ⬜ | L709 | `template_text` | Vordefinierte Abschnitte () | `admin.vordefinierte_abschnitte` |
| 775 | ⬜ | L724 | `template_text` | Benutzerdefinierte Felder () | `admin.benutzerdefinierte_felder` |
| 776 | ⬜ | L765 | `template_text` | Löschen | `admin.löschen` |
| 777 | ⬜ | L785 | `template_text` | E-Mail & Versand (Resend / SMTP) | `admin.email_versand_resend_smtp` |
| 778 | ⬜ | L813 | `template_text` | Test-E-Mail senden | `admin.testemail_senden` |
| 779 | ⬜ | L819 | `template_text` | Zentrale E-Mail-Einstellungen | `admin.zentrale_emaileinstellungen` |
| 780 | ⬜ | L819 | `template_text` | Alle automatischen E-Mails, Kalendereinladungen und Benachrichtigungen werden üb | `admin.alle_automatischen_emails_kalendereinlad` |
| 781 | ⬜ | L830 | `template_text` | Aktiv: | `admin.aktiv` |
| 782 | ⬜ | L843 | `template_text` | E-Mail Versand-Methode | `admin.email_versandmethode` |
| 783 | ⬜ | L860 | `template_text` | Resend API | `admin.resend_api` |
| 784 | ⬜ | L862 | `template_text` | Empfohlen & Aktiv | `admin.empfohlen_aktiv` |
| 785 | ⬜ | L865 | `template_text` | 100% Zustellrate zu Outlook, Gmail und Apple Mail mit kryptografischer DKIM/SPF- | `admin.100_zustellrate_zu_outlook_gmail_und_app` |
| 786 | ⬜ | L877 | `template_text` | Eigener SMTP-Server | `admin.eigener_smtpserver` |
| 787 | ⬜ | L879 | `template_text` | Manuelle SMTP-Verbindung über Postfix/Exim (z.B. mail.kurka.ch oder Firmen-Mails | `admin.manuelle_smtpverbindung_über_postfixexim` |
| 788 | ⬜ | L888 | `template_text` | Resend Konfiguration | `admin.resend_konfiguration` |
| 789 | ⬜ | L893 | `template_text` | Domain kurka.ch verifiziert | `admin.domain_kurkach_verifiziert` |
| 790 | ⬜ | L897 | `template_text` | Resend API-Key * | `admin.resend_apikey` |
| 791 | ⬜ | L915 | `template_text` | Key von | `admin.key_von` |
| 792 | ⬜ | L920 | `template_text` | Absender-E-Mail (From Address) * | `admin.absenderemail_from_address` |
| 793 | ⬜ | L930 | `template_text` | Muss eine Adresse der verifizierten Domain kurka.ch sein. | `admin.muss_eine_adresse_der_verifizierten_doma` |
| 794 | ⬜ | L932 | `template_text` | Absender-Name (From Name) * | `admin.absendername_from_name` |
| 795 | ⬜ | L944 | `template_text` | Dedizierte Absender-Identitäten (@kurka.ch) | `admin.dedizierte_absenderidentitäten_kurkach` |
| 796 | ⬜ | L944 | `template_text` | Dank verifizierter Domain sofort einsatzbereit ohne separate Postfächer. | `admin.dank_verifizierter_domain_sofort_einsatz` |
| 797 | ⬜ | L967 | `template_text` | Onboarding, Willkommensnachrichten &amp; direkte Ansprache. | `admin.onboarding_willkommensnachrichten_amp_di` |
| 798 | ⬜ | L971 | `template_text` | Reply-To: support@kurka.ch | `admin.replyto_supportkurkach` |
| 799 | ⬜ | L984 | `template_text` | Changelogs, Produkt-News &amp; Newsletter. | `admin.changelogs_produktnews_amp_newsletter` |
| 800 | ⬜ | L989 | `template_text` | Kein Reply-To | `admin.kein_replyto` |
| 801 | ⬜ | L1000 | `template_text` | Kollaboration: Zuweisungen, Erwähnungen, Kommentare, Termine. | `admin.kollaboration_zuweisungen_erwähnungen_ko` |
| 802 | ⬜ | L1005 | `template_text` | Kein Reply-To | `admin.kein_replyto` |
| 803 | ⬜ | L1017 | `template_text` | Benachrichtigungen, Fristen, Statusänderungen &amp; Erinnerungen. | `admin.benachrichtigungen_fristen_statusänderun` |
| 804 | ⬜ | L1022 | `template_text` | Kein Reply-To | `admin.kein_replyto` |
| 805 | ⬜ | L1033 | `template_text` | Transaktionsmails: Passwort-Resets, Sicherheitswarnungen, Account. | `admin.transaktionsmails_passwortresets_sicherh` |
| 806 | ⬜ | L1039 | `template_text` | Kein Reply-To | `admin.kein_replyto` |
| 807 | ⬜ | L1047 | `template_text` | Manuelle SMTP-Server Konfiguration | `admin.manuelle_smtpserver_konfiguration` |
| 808 | ⬜ | L1050 | `template_text` | SMTP Host / Server * | `admin.smtp_host_server` |
| 809 | ⬜ | L1056 | `template_text` | z.B. mail.kurka.ch oder smtp.ihredomain.ch | `admin.zb_mailkurkach_oder_smtpihredomainch` |
| 810 | ⬜ | L1058 | `template_text` | Port & Verschlüsselung * | `admin.port_verschlüsselung` |
| 811 | ⬜ | L1077 | `template_text` | SSL / TLS (Port 465) | `admin.ssl_tls_port_465` |
| 812 | ⬜ | L1078 | `template_text` | STARTTLS (Port 587) | `admin.starttls_port_587` |
| 813 | ⬜ | L1079 | `template_text` | Keine Verschlüsselung (Port 25) | `admin.keine_verschlüsselung_port_25` |
| 814 | ⬜ | L1081 | `template_text` | Empfohlen: SSL (465) oder STARTTLS (587) | `admin.empfohlen_ssl_465_oder_starttls_587` |
| 815 | ⬜ | L1085 | `template_text` | SMTP Benutzername * | `admin.smtp_benutzername` |
| 816 | ⬜ | L1095 | `template_text` | SMTP Passwort * | `admin.smtp_passwort` |
| 817 | ⬜ | L1100 | `template_text` | Passwort verbergen | `admin.passwort_verbergen` |
| 818 | ⬜ | L1100 | `template_text` | Passwort anzeigen | `admin.passwort_anzeigen` |
| 819 | ⬜ | L1107 | `attr:placeholder` | SMTP Kennwort | `admin.smtp_kennwort` |
| 820 | ⬜ | L1113 | `template_text` | Absender-E-Mail (From Address) * | `admin.absenderemail_from_address` |
| 821 | ⬜ | L1123 | `template_text` | Absender-Name (From Name) * | `admin.absendername_from_name` |
| 822 | ⬜ | L1138 | `template_text` | Test-E-Mail senden | `admin.testemail_senden` |
| 823 | ⬜ | L1144 | `template_text` | Wird gespeichert... | `admin.wird_gespeichert` |
| 824 | ⬜ | L1144 | `template_text` | Einstellungen speichern | `admin.einstellungen_speichern` |
| 825 | ⬜ | L1153 | `template_text` | E-Mail Trigger & Vorlagen | `admin.email_trigger_vorlagen` |
| 826 | ⬜ | L1155 | `template_text` | Automatische Benachrichtigungen für Aktionen wie Zuweisungen, Fristen, Kommentar | `admin.automatische_benachrichtigungen_für_akti` |
| 827 | ⬜ | L1162 | `template_text` | Alle auf Standard zurücksetzen | `admin.alle_auf_standard_zurücksetzen` |
| 828 | ⬜ | L1187 | `template_text` | Absender: | `admin.absender` |
| 829 | ⬜ | L1223 | `template_text` | Verfügbare Variablen | `admin.verfügbare_variablen` |
| 830 | ⬜ | L1232 | `template_text` | Zurücksetzen | `admin.zurücksetzen` |
| 831 | ⬜ | L1236 | `template_text` | Vorlage bearbeiten | `admin.vorlage_bearbeiten` |
| 832 | ⬜ | L1243 | `template_text` | E-Mail Versand-Protokoll (Outbox) | `admin.email_versandprotokoll_outbox` |
| 833 | ⬜ | L1245 | `template_text` | Verlauf der letzten E-Mail-Sendungen und Status. | `admin.verlauf_der_letzten_emailsendungen_und_s` |
| 834 | ⬜ | L1260 | `template_text` | Empfänger | `admin.empfänger` |
| 835 | ⬜ | L1263 | `template_text` | Erstellt am | `admin.erstellt_am` |
| 836 | ⬜ | L1263 | `template_text` | Gesendet am / Fehler | `admin.gesendet_am_fehler` |
| 837 | ⬜ | L1273 | `template_text` | Noch keine E-Mails im Protokoll vorhanden. | `admin.noch_keine_emails_im_protokoll_vorhanden` |
| 838 | ⬜ | L1318 | `template_text` | Sicherheits- & Revisionsprotokoll (Audit Trail) | `admin.sicherheits_revisionsprotokoll_audit_tra` |
| 839 | ⬜ | L1320 | `template_text` | Vollständige Aufzeichnung aller Benutzeraktionen, Berechtigungsänderungen und Sy | `admin.vollständige_aufzeichnung_aller_benutzer` |
| 840 | ⬜ | L1328 | `attr:placeholder` | Suche (Aktion, User, IP...) | `admin.suche_aktion_user_ip` |
| 841 | ⬜ | L1344 | `template_text` | Alle Aktionen | `admin.alle_aktionen` |
| 842 | ⬜ | L1350 | `template_text` | Neu laden | `admin.neu_laden` |
| 843 | ⬜ | L1358 | `template_text` | Benutzer & Organisation | `admin.benutzer_organisation` |
| 844 | ⬜ | L1358 | `template_text` | Entität | `admin.entität` |
| 845 | ⬜ | L1360 | `template_text` | IP-Adresse & Client | `admin.ipadresse_client` |
| 846 | ⬜ | L1367 | `template_text` | Protokolle werden geladen... | `admin.protokolle_werden_geladen` |
| 847 | ⬜ | L1373 | `template_text` | Keine Audit-Logs gefunden. | `admin.keine_auditlogs_gefunden` |
| 848 | ⬜ | L1400 | `template_text` | System / Anonym | `admin.system_anonym` |
| 849 | ⬜ | L1425 | `template_text` | Audit Log Details | `admin.audit_log_details` |
| 850 | ⬜ | L1433 | `template_text` | Log-ID: | `admin.logid` |
| 851 | ⬜ | L1438 | `template_text` | Zeitstempel: | `admin.zeitstempel` |
| 852 | ⬜ | L1447 | `template_text` | Benutzer: | `admin.benutzer` |
| 853 | ⬜ | L1451 | `template_text` | IP-Adresse: | `admin.ipadresse` |
| 854 | ⬜ | L1456 | `template_text` | Details & Payload: | `admin.details_payload` |
| 855 | ⬜ | L1472 | `template_text` | Webseiten-Verwaltung & CMS | `admin.webseitenverwaltung_cms` |
| 856 | ⬜ | L1473 | `template_text` | Steuere Inhalte der öffentlichen Webseite, Preismodelle, Ankündigungs-Banner und | `admin.steuere_inhalte_der_öffentlichen_webseit` |
| 857 | ⬜ | L1487 | `template_text` | Webseiten-Einstellungen erfolgreich aktualisiert! | `admin.webseiteneinstellungen_erfolgreich_aktua` |
| 858 | ⬜ | L1496 | `template_text` | Landingpage & Allgemeine Webseiten-Informationen | `admin.landingpage_allgemeine_webseiteninformat` |
| 859 | ⬜ | L1501 | `template_text` | Webseiten- & Browser-Titel (&lt;title&gt;) | `admin.webseiten_browsertitel_lttitlegt` |
| 860 | ⬜ | L1504 | `template_text` | Browser-Tab & HTML &lt;title&gt; | `admin.browsertab_html_lttitlegt` |
| 861 | ⬜ | L1506 | `attr:placeholder` | z. B. Taskster – Professionelles Projekt- & Bauleitermanagement | `admin.z_b_taskster_professionelles_projekt_bau` |
| 862 | ⬜ | L1513 | `template_text` | Dieser globale Titel erscheint oben im Browser-Tab, in Bookmarks sowie als Haupt | `admin.dieser_globale_titel_erscheint_oben_im_b` |
| 863 | ⬜ | L1518 | `template_text` | Hero Hauptüberschrift | `admin.hero_hauptüberschrift` |
| 864 | ⬜ | L1525 | `template_text` | Hero Untertitel / Beschreibung | `admin.hero_untertitel_beschreibung` |
| 865 | ⬜ | L1531 | `template_text` | Support E-Mail Adresse | `admin.support_email_adresse` |
| 866 | ⬜ | L1537 | `template_text` | Support Telefonnummer | `admin.support_telefonnummer` |
| 867 | ⬜ | L1549 | `template_text` | Live-Vorschau: Browser-Tab | `admin.livevorschau_browsertab` |
| 868 | ⬜ | L1566 | `template_text` | Preismodelle & Tarife auf der Webseite | `admin.preismodelle_tarife_auf_der_webseite` |
| 869 | ⬜ | L1571 | `template_text` | Free / Basic Plan | `admin.free_basic_plan` |
| 870 | ⬜ | L1579 | `template_text` | PRO Plan | `admin.pro_plan` |
| 871 | ⬜ | L1584 | `template_text` | ENTERPRISE Plan | `admin.enterprise_plan` |
| 872 | ⬜ | L1589 | `attr:placeholder` | Auf Anfrage | `admin.auf_anfrage` |
| 873 | ⬜ | L1595 | `template_text` | In-App Ankündigungs-Banner & Wartungsmodus | `admin.inapp_ankündigungsbanner_wartungsmodus` |
| 874 | ⬜ | L1612 | `template_text` | Ankündigung-Banner auf der Webseite anzeigen | `admin.ankündigungbanner_auf_der_webseite_anzei` |
| 875 | ⬜ | L1615 | `attr:placeholder` | Banner-Nachricht eingeben... | `admin.bannernachricht_eingeben` |
| 876 | ⬜ | L1628 | `template_text` | Wartungsmodus (Maintenance Switch) | `admin.wartungsmodus_maintenance_switch` |
| 877 | ⬜ | L1639 | `template_text` | Wenn aktiviert, können sich nur Superadmins in die Plattform einloggen. Normale  | `admin.wenn_aktiviert_können_sich_nur_superadmi` |
| 878 | ⬜ | L1646 | `template_text` | SEO & Suchmaschinen-Optimierung | `admin.seo_suchmaschinenoptimierung` |
| 879 | ⬜ | L1651 | `template_text` | Globaler Meta-Titel | `admin.globaler_metatitel` |
| 880 | ⬜ | L1659 | `template_text` | Globaler Meta-Beschreibungstext | `admin.globaler_metabeschreibungstext` |
| 881 | ⬜ | L1665 | `template_text` | Google Suchergebnis-Vorschau | `admin.google_suchergebnisvorschau` |
| 882 | ⬜ | L1690 | `template_text` | Taskster AI-Engine & Tarif-Berechtigungen | `admin.taskster_aiengine_tarifberechtigungen` |
| 883 | ⬜ | L1692 | `template_text` | Steuere hier die KI-Funktionen für Bautagebuch, Aufgaben-Generierung, Sprachnoti | `admin.steuere_hier_die_kifunktionen_für_bautag` |
| 884 | ⬜ | L1710 | `template_text` | Tarif-Freischaltung & Quotas (Was ist in welchem Plan freigeschaltet?) | `admin.tariffreischaltung_quotas_was_ist_in_wel` |
| 885 | ⬜ | L1712 | `template_text` | Zero-Trust serverseitig erzwungen | `admin.zerotrust_serverseitig_erzwungen` |
| 886 | ⬜ | L1720 | `template_text` | Free / Basic | `admin.free_basic` |
| 887 | ⬜ | L1733 | `template_text` | Kostenlose Accounts & Probe-Nutzer | `admin.kostenlose_accounts_probenutzer` |
| 888 | ⬜ | L1738 | `template_text` | Max. Anfragen / Monat | `admin.max_anfragen_monat` |
| 889 | ⬜ | L1747 | `template_text` | Audio-Transkription (Whisper) | `admin.audiotranskription_whisper` |
| 890 | ⬜ | L1753 | `template_text` | Pro Plan | `admin.pro_plan` |
| 891 | ⬜ | L1760 | `template_text` | Handwerker, Bauleiter & Einzelfirmen | `admin.handwerker_bauleiter_einzelfirmen` |
| 892 | ⬜ | L1764 | `template_text` | Max. Anfragen / Monat | `admin.max_anfragen_monat` |
| 893 | ⬜ | L1772 | `template_text` | Audio-Transkription (Whisper) | `admin.audiotranskription_whisper` |
| 894 | ⬜ | L1782 | `template_text` | Enterprise Plan | `admin.enterprise_plan` |
| 895 | ⬜ | L1789 | `template_text` | Großunternehmen & Generalunternehmer | `admin.großunternehmen_generalunternehmer` |
| 896 | ⬜ | L1793 | `template_text` | Max. Anfragen / Monat | `admin.max_anfragen_monat` |
| 897 | ⬜ | L1801 | `template_text` | Audio-Transkription (Whisper) | `admin.audiotranskription_whisper` |
| 898 | ⬜ | L1809 | `template_text` | Eigene API-Keys erlauben (BYOK) | `admin.eigene_apikeys_erlauben_byok` |
| 899 | ⬜ | L1812 | `template_text` | Globale KI-Modelle & Parameter | `admin.globale_kimodelle_parameter` |
| 900 | ⬜ | L1815 | `template_text` | Standard LLM Modell (OpenRouter Identifier) | `admin.standard_llm_modell_openrouter_identifie` |
| 901 | ⬜ | L1825 | `template_text` | Empfohlen: | `admin.empfohlen` |
| 902 | ⬜ | L1825 | `template_text` | Audio Transkriptions-Modell | `admin.audio_transkriptionsmodell` |
| 903 | ⬜ | L1832 | `template_text` | Empfohlen: | `admin.empfohlen` |
| 904 | ⬜ | L1834 | `template_text` | Max. Tokens pro Antwort: | `admin.max_tokens_pro_antwort` |
| 905 | ⬜ | L1843 | `template_text` | Kreativität / Temperatur: | `admin.kreativität_temperatur` |
| 906 | ⬜ | L1852 | `template_text` | Globaler System-Prompt (Rolle für Bau- & Projektassistenten) | `admin.globaler_systemprompt_rolle_für_bau_proj` |
| 907 | ⬜ | L1865 | `template_text` | Live KI-Testkonsole | `admin.live_kitestkonsole` |
| 908 | ⬜ | L1867 | `attr:placeholder` | Test-Prompt eingeben... | `admin.testprompt_eingeben` |
| 909 | ⬜ | L1910 | `template_text` | Vorlagen-Name * | `admin.vorlagenname` |
| 910 | ⬜ | L1921 | `template_text` | Kategorie * | `admin.kategorie` |
| 911 | ⬜ | L1927 | `template_text` | 🏡 Privat / Persönlich | `admin.privat_persönlich` |
| 912 | ⬜ | L1931 | `template_text` | Unterkategorie / Branche | `admin.unterkategorie_branche` |
| 913 | ⬜ | L1945 | `attr:placeholder` | Kurze Zusammenfassung des Einsatzbereichs | `admin.kurze_zusammenfassung_des_einsatzbereich` |
| 914 | ⬜ | L1954 | `template_text` | Vordefinierte Abschnitte | `admin.vordefinierte_abschnitte` |
| 915 | ⬜ | L1957 | `template_text` | Diese Abschnitte werden automatisch angelegt, wenn ein Projekt mit dieser Vorlag | `admin.diese_abschnitte_werden_automatisch_ange` |
| 916 | ⬜ | L1975 | `attr:placeholder` | Neuen Abschnitt eingeben (z.B. In Prüfung)... | `admin.neuen_abschnitt_eingeben_zb_in_prüfung` |
| 917 | ⬜ | L1985 | `template_text` | + Hinzufügen | `admin.hinzufügen` |
| 918 | ⬜ | L1990 | `template_text` | Benutzerdefinierte Felder mit Logik | `admin.benutzerdefinierte_felder_mit_logik` |
| 919 | ⬜ | L1993 | `template_text` | Felder für Aufgaben oder das gesamte Projekt inkl. bedingter Abhängigkeiten. | `admin.felder_für_aufgaben_oder_das_gesamte_pro` |
| 920 | ⬜ | L2035 | `template_text` | Löschen | `admin.löschen` |
| 921 | ⬜ | L2040 | `template_text` | + Neues Feld zur Vorlage hinzufügen | `admin.neues_feld_zur_vorlage_hinzufügen` |
| 922 | ⬜ | L2058 | `template_text` | Textzeile (kurz) | `admin.textzeile_kurz` |
| 923 | ⬜ | L2058 | `template_text` | Längerer Text / Notizfeld | `admin.längerer_text_notizfeld` |
| 924 | ⬜ | L2058 | `template_text` | Zahl / Währung / Messwert | `admin.zahl_währung_messwert` |
| 925 | ⬜ | L2059 | `template_text` | Auswahlliste (Dropdown) | `admin.auswahlliste_dropdown` |
| 926 | ⬜ | L2062 | `template_text` | Checkbox (Ja / Nein) | `admin.checkbox_ja_nein` |
| 927 | ⬜ | L2063 | `template_text` | Weblink / URL | `admin.weblink_url` |
| 928 | ⬜ | L2082 | `template_text` | Dropdown-Optionen (Komma-getrennt) | `admin.dropdownoptionen_kommagetrennt` |
| 929 | ⬜ | L2085 | `attr:placeholder` | Ja, Nein, Ausstehend | `admin.ja_nein_ausstehend` |
| 930 | ⬜ | L2097 | `template_text` | ⚡ Bedingte Sichtbarkeit (Abhängig von anderem Feld) | `admin.bedingte_sichtbarkeit_abhängig_von_ande` |
| 931 | ⬜ | L2102 | `template_text` | Abhängig von Feld-Key | `admin.abhängig_von_feldkey` |
| 932 | ⬜ | L2106 | `template_text` | -- Feld auswählen -- | `admin.feld_auswählen` |
| 933 | ⬜ | L2114 | `template_text` | Erwarteter Wert | `admin.erwarteter_wert` |
| 934 | ⬜ | L2128 | `template_text` | + Feld hinzufügen | `admin.feld_hinzufügen` |
| 935 | ⬜ | L2140 | `template_text` | Vorlage speichern | `admin.vorlage_speichern` |
| 936 | ⬜ | L2148 | `template_text` | Neues Unternehmen anlegen | `admin.neues_unternehmen_anlegen` |
| 937 | ⬜ | L2152 | `template_text` | Erstellt ein Unternehmens-Profil mit Company Admin und initialem Hauptordner. | `admin.erstellt_ein_unternehmensprofil_mit_comp` |
| 938 | ⬜ | L2157 | `template_text` | Name des Unternehmens | `admin.name_des_unternehmens` |
| 939 | ⬜ | L2180 | `template_text` | Starter Plan | `admin.starter_plan` |
| 940 | ⬜ | L2180 | `template_text` | Pro Plan | `admin.pro_plan` |
| 941 | ⬜ | L2183 | `template_text` | Enterprise Plan | `admin.enterprise_plan` |
| 942 | ⬜ | L2188 | `template_text` | Company Admin Zugangsdaten | `admin.company_admin_zugangsdaten` |
| 943 | ⬜ | L2193 | `template_text` | Name des Admins | `admin.name_des_admins` |
| 944 | ⬜ | L2201 | `attr:placeholder` | Beat Meier | `admin.beat_meier` |
| 945 | ⬜ | L2205 | `template_text` | E-Mail des Admins | `admin.email_des_admins` |
| 946 | ⬜ | L2225 | `template_text` | Unternehmen erstellen | `admin.unternehmen_erstellen` |
| 947 | ⬜ | L2239 | `template_text` | Neuer Benutzer | `admin.neuer_benutzer` |
| 948 | ⬜ | L2241 | `template_text` | Benutzerkonto manuell erstellen | `admin.benutzerkonto_manuell_erstellen` |
| 949 | ⬜ | L2251 | `template_text` | Name * | `admin.name` |
| 950 | ⬜ | L2262 | `template_text` | E-Mail-Adresse * | `admin.emailadresse` |
| 951 | ⬜ | L2278 | `template_text` | Passwort * | `admin.passwort` |
| 952 | ⬜ | L2289 | `attr:placeholder` | Initiales Login-Passwort (mind. 6 Zeichen) | `admin.initiales_loginpasswort_mind_6_zeichen` |
| 953 | ⬜ | L2298 | `template_text` | Benutzer-Plan (Tarif) | `admin.benutzerplan_tarif` |
| 954 | ⬜ | L2308 | `template_text` | Taskster Free / Basic Plan (Basis: max. 1 Ordner, 3 Projekte) | `admin.taskster_free_basic_plan_basis_max_1_ord` |
| 955 | ⬜ | L2310 | `template_text` | Taskster PRO Plan (30 Projekte, Zeitersparnis & Vorlagen) | `admin.taskster_pro_plan_30_projekte_zeiterspar` |
| 956 | ⬜ | L2314 | `template_text` | Taskster ENTERPRISE Plan (Unbegrenzte Projekte & Export) | `admin.taskster_enterprise_plan_unbegrenzte_pro` |
| 957 | ⬜ | L2318 | `template_text` | Unternehmen zuweisen | `admin.unternehmen_zuweisen` |
| 958 | ⬜ | L2326 | `template_text` | Keine (Privatkunde / Einzelnutzer) | `admin.keine_privatkunde_einzelnutzer` |
| 959 | ⬜ | L2335 | `template_text` | Rolle im Unternehmen | `admin.rolle_im_unternehmen` |
| 960 | ⬜ | L2346 | `template_text` | Mitarbeiter (member) | `admin.mitarbeiter_member` |
| 961 | ⬜ | L2347 | `template_text` | Administrator (admin) | `admin.administrator_admin` |
| 962 | ⬜ | L2356 | `template_text` | Admin-Berechtigungen & Subrollen | `admin.adminberechtigungen_subrollen` |
| 963 | ⬜ | L2409 | `template_text` | Ermöglicht uneingeschränkten Plattform-Vollzugriff auf alle Firmen und Daten. | `admin.ermöglicht_uneingeschränkten_plattformvo` |
| 964 | ⬜ | L2477 | `template_text` | Neues Passwort (optional) | `admin.neues_passwort_optional` |
| 965 | ⬜ | L2494 | `attr:placeholder` | Leer lassen, falls Passwort unverändert bleiben soll | `admin.leer_lassen_falls_passwort_unverändert_b` |
| 966 | ⬜ | L2499 | `template_text` | Benutzer-Plan (Tarif) | `admin.benutzerplan_tarif` |
| 967 | ⬜ | L2506 | `template_text` | Taskster Free / Basic Plan (Basis: max. 1 Ordner, 3 Projekte) | `admin.taskster_free_basic_plan_basis_max_1_ord` |
| 968 | ⬜ | L2510 | `template_text` | Taskster PRO Plan (30 Projekte, Zeitersparnis & Vorlagen) | `admin.taskster_pro_plan_30_projekte_zeiterspar` |
| 969 | ⬜ | L2512 | `template_text` | Taskster ENTERPRISE Plan (Unbegrenzte Projekte & Export) | `admin.taskster_enterprise_plan_unbegrenzte_pro` |
| 970 | ⬜ | L2518 | `template_text` | Unternehmen / Organisation zuweisen | `admin.unternehmen_organisation_zuweisen` |
| 971 | ⬜ | L2530 | `template_text` | Keine (Privatkunde / Einzelnutzer) | `admin.keine_privatkunde_einzelnutzer` |
| 972 | ⬜ | L2539 | `template_text` | Rolle im Unternehmen | `admin.rolle_im_unternehmen` |
| 973 | ⬜ | L2546 | `template_text` | Mitarbeiter (member) | `admin.mitarbeiter_member` |
| 974 | ⬜ | L2548 | `template_text` | Unternehmens-Administrator (admin) | `admin.unternehmensadministrator_admin` |
| 975 | ⬜ | L2557 | `template_text` | Admin-Berechtigungen & Subrollen | `admin.adminberechtigungen_subrollen` |
| 976 | ⬜ | L2608 | `template_text` | Ermöglicht Vollzugriff auf diesen Administrationsbereich und alle Plattformdaten | `admin.ermöglicht_vollzugriff_auf_diesen_admini` |
| 977 | ⬜ | L2639 | `template_text` | E-Mail Verbindung testen | `admin.email_verbindung_testen` |
| 978 | ⬜ | L2640 | `template_text` | Versand über | `admin.versand_über` |
| 979 | ⬜ | L2651 | `template_text` | Absender-Identität (@kurka.ch) | `admin.absenderidentität_kurkach` |
| 980 | ⬜ | L2660 | `template_text` | Taskster &lt;hey@kurka.ch&gt; — Onboarding &amp; Willkommen (Reply-To: support@k | `admin.taskster_ltheykurkachgt_onboarding_amp_w` |
| 981 | ⬜ | L2662 | `template_text` | Taskster &lt;updates@kurka.ch&gt; — Changelogs &amp; News | `admin.taskster_ltupdateskurkachgt_changelogs_a` |
| 982 | ⬜ | L2665 | `template_text` | Taskster &lt;team@kurka.ch&gt; — Kollaboration &amp; Zuweisungen | `admin.taskster_ltteamkurkachgt_kollaboration_a` |
| 983 | ⬜ | L2667 | `template_text` | Taskster &lt;notify@kurka.ch&gt; — Benachrichtigungen &amp; Fristen | `admin.taskster_ltnotifykurkachgt_benachrichtig` |
| 984 | ⬜ | L2669 | `template_text` | Taskster &lt;system@kurka.ch&gt; — Transaktionsmails &amp; Sicherheit | `admin.taskster_ltsystemkurkachgt_transaktionsm` |
| 985 | ⬜ | L2671 | `template_text` | Taskster &lt;&gt; — Standard | `admin.taskster_ltgt_standard` |
| 986 | ⬜ | L2677 | `template_text` | Wird direkt als Absender im Mail-Header gesetzt. | `admin.wird_direkt_als_absender_im_mailheader_g` |
| 987 | ⬜ | L2681 | `template_text` | Empfänger-E-Mail-Adresse * | `admin.empfängeremailadresse` |
| 988 | ⬜ | L2700 | `template_text` | ❌ Fehler: | `admin.fehler` |
| 989 | ⬜ | L2704 | `template_text` | Versand-Protokoll anzeigen ( Zeilen) | `admin.versandprotokoll_anzeigen_zeilen` |
| 990 | ⬜ | L2718 | `template_text` | Wird gesendet... | `admin.wird_gesendet` |
| 991 | ⬜ | L2718 | `template_text` | Jetzt Test-Mail senden | `admin.jetzt_testmail_senden` |
| 992 | ⬜ | L2730 | `template_text` | Trigger: | `admin.trigger` |
| 993 | ⬜ | L2733 | `template_text` | E-Mail-Vorlage anpassen | `admin.emailvorlage_anpassen` |
| 994 | ⬜ | L2747 | `template_text` | Vorlage aktiv (E-Mails für diesen Trigger versenden) | `admin.vorlage_aktiv_emails_für_diesen_trigger` |
| 995 | ⬜ | L2753 | `template_text` | E-Mail-Betreff * | `admin.emailbetreff` |
| 996 | ⬜ | L2765 | `template_text` | Klick zum Einfügen einer Variable in Betreff / Body: | `admin.klick_zum_einfügen_einer_variable_in_bet` |
| 997 | ⬜ | L2794 | `template_text` | Vorschau (HTML) | `admin.vorschau_html` |
| 998 | ⬜ | L2839 | `template_text` | Vorlage speichern | `admin.vorlage_speichern` |
| 999 | ⬜ | L2844 | `template_text` | Zugriff verweigert | `admin.zugriff_verweigert` |
| 1000 | ⬜ | L2846 | `template_text` | Dieser Bereich ist ausschließlich autorisierten Taskster-Plattformadministratore | `admin.dieser_bereich_ist_ausschließlich_autori` |
| 1001 | ⬜ | L2852 | `template_text` | Zurück zum Dashboard | `admin.zurück_zum_dashboard` |
| 1002 | ⬜ | L3081 | `script_literal` | Bestätigung erforderlich | `admin.bestätigung_erforderlich` |
| 1003 | ⬜ | L3083 | `script_literal` | Bestätigen | `admin.bestätigen` |
| 1004 | ⬜ | L3094 | `script_literal` | Bestätigung erforderlich | `admin.bestätigung_erforderlich` |
| 1005 | ⬜ | L3104 | `script_literal` | Bestätigung erforderlich | `admin.bestätigung_erforderlich` |
| 1006 | ⬜ | L3162 | `script_literal` | Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien. | `admin.verwalte_subscriptionpläne_uploadrestrik` |
| 1007 | ⬜ | L3164 | `script_literal` | Vordefinierte Vorlagen für geschäftliche und private Bau- & Projektorganisation. | `admin.vordefinierte_vorlagen_für_geschäftliche` |
| 1008 | ⬜ | L3167 | `script_literal` | Steuere Landingpage-Texte, Tarife auf der Webseite, Ankündigungsbanner, SEO & Wa | `admin.steuere_landingpagetexte_tarife_auf_der` |
| 1009 | ⬜ | L3169 | `script_literal` | Revisionssichere Protokollierung aller Sicherheits-Events, Benutzeraktionen und  | `admin.revisionssichere_protokollierung_aller_s` |
| 1010 | ⬜ | L3170 | `script_literal` | Kundenübersicht, Benutzerverwaltung, Company-Pläne, Zugriffsregeln und Systemgre | `admin.kundenübersicht_benutzerverwaltung_compa` |
| 1011 | ⬜ | L3271 | `script_literal` | Fasse die heutigen Vorkommnisse auf der Baustelle zusammen: Betonlieferung mit 3 | `admin.fasse_die_heutigen_vorkommnisse_auf_der` |
| 1012 | ⬜ | L3815 | `script_literal` | Möchtest du diese Vorlage wirklich löschen? | `admin.möchtest_du_diese_vorlage_wirklich_lösch` |
| 1013 | ⬜ | L3822 | `script_literal` | Vorlage erfolgreich gelöscht! | `admin.vorlage_erfolgreich_gelöscht` |
| 1014 | ⬜ | L3824 | `script_literal` | Fehler beim Löschen der Vorlage | `admin.fehler_beim_löschen_der_vorlage` |
| 1015 | ⬜ | L3896 | `script_literal` | Zugriff nur für autorisierte Administratoren gestattet. | `admin.zugriff_nur_für_autorisierte_administrat` |
| 1016 | ⬜ | L3948 | `script_literal` | Fehler beim Ändern des Pro-Status | `admin.fehler_beim_ändern_des_prostatus` |
| 1017 | ⬜ | L4079 | `script_literal` | FTTH Ausbau Zürich Nord | `admin.ftth_ausbau_zürich_nord` |
| 1018 | ⬜ | L4087 | `script_literal` | Zentralstrasse 14, 8003 Zürich | `admin.zentralstrasse_14_8003_zürich` |
| 1019 | ⬜ | L4089 | `script_literal` | Bitte bis morgen prüfen | `admin.bitte_bis_morgen_prüfen` |
| 1020 | ⬜ | L4143 | `script_literal` | Möchtest du diese Vorlage wirklich auf den Systemstandard zurücksetzen? | `admin.möchtest_du_diese_vorlage_wirklich_auf_d` |
| 1021 | ⬜ | L4151 | `script_literal` | Vorlage erfolgreich auf Systemstandard zurückgesetzt! | `admin.vorlage_erfolgreich_auf_systemstandard_z` |
| 1022 | ⬜ | L4153 | `script_literal` | Fehler beim Zurücksetzen der Vorlage | `admin.fehler_beim_zurücksetzen_der_vorlage` |
| 1023 | ⬜ | L4158 | `script_literal` | Möchtest du wirklich ALLE Vorlagen unwiderruflich auf den Systemstandard zurücks | `admin.möchtest_du_wirklich_alle_vorlagen_unwid` |
| 1024 | ⬜ | L4165 | `script_literal` | Alle Vorlagen erfolgreich zurückgesetzt! | `admin.alle_vorlagen_erfolgreich_zurückgesetzt` |
| 1025 | ⬜ | L4167 | `script_literal` | Fehler beim Zurücksetzen aller Vorlagen | `admin.fehler_beim_zurücksetzen_aller_vorlagen` |

### 📄 `pages/company/index.vue` (41 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 1026 | ⬜ | L9 | `template_text` | Zugriff verweigert | `company.zugriff_verweigert` |
| 1027 | ⬜ | L10 | `template_text` | Dieser Bereich ist ausschließlich Firmen-Administratoren des eigenen Unternehmen | `company.dieser_bereich_ist_ausschließlich_firmen` |
| 1028 | ⬜ | L49 | `template_text` | Mitarbeiter, Firmenvorlagen, Plan &amp; Lizenzen, Richtlinien und Support – alle | `company.mitarbeiter_firmenvorlagen_plan_amp_lize` |
| 1029 | ⬜ | L65 | `template_text` | Mitarbeiter einladen | `company.mitarbeiter_einladen` |
| 1030 | ⬜ | L92 | `template_text` | mit Vollzugriff | `company.mit_vollzugriff` |
| 1031 | ⬜ | L110 | `template_text` | nur für diese Firma | `company.nur_für_diese_firma` |
| 1032 | ⬜ | L133 | `template_text` | Lizenzen &amp; Monatliche Gesamtkosten | `company.lizenzen_amp_monatliche_gesamtkosten` |
| 1033 | ⬜ | L137 | `template_text` | Übersicht der aktiven Arbeitsplatzlizenzen deines Unternehmens. | `company.übersicht_der_aktiven_arbeitsplatzlizenz` |
| 1034 | ⬜ | L141 | `template_text` | Monatlicher Gesamtbetrag | `company.monatlicher_gesamtbetrag` |
| 1035 | ⬜ | L145 | `template_text` | / Monat | `company.monat` |
| 1036 | ⬜ | L158 | `template_text` | 19 € / Monat pro Sitz | `company.19_monat_pro_sitz` |
| 1037 | ⬜ | L169 | `template_text` | Mitarbeiter Pro | `company.mitarbeiter_pro` |
| 1038 | ⬜ | L172 | `template_text` | 8 € / Monat pro Sitz | `company.8_monat_pro_sitz` |
| 1039 | ⬜ | L184 | `template_text` | Mitarbeiter Enterprise | `company.mitarbeiter_enterprise` |
| 1040 | ⬜ | L187 | `template_text` | 15 € / Monat pro Sitz | `company.15_monat_pro_sitz` |
| 1041 | ⬜ | L198 | `template_text` | Mitarbeiter &amp; Co-Administratoren | `company.mitarbeiter_amp_coadministratoren` |
| 1042 | ⬜ | L202 | `template_text` | Co-Admins dürfen dieses Firmen-Portal ebenfalls verwalten. | `company.coadmins_dürfen_dieses_firmenportal_eben` |
| 1043 | ⬜ | L215 | `template_text` | Name &amp; E-Mail | `company.name_amp_email` |
| 1044 | ⬜ | L218 | `template_text` | Rolle &amp; Lizenz | `company.rolle_amp_lizenz` |
| 1045 | ⬜ | L251 | `template_text` | Enterprise (19 € / Mt.) | `company.enterprise_19_mt` |
| 1046 | ⬜ | L257 | `template_text` | Enterprise (15 € / Mt.) | `company.enterprise_15_mt` |
| 1047 | ⬜ | L262 | `template_text` | Pro (8 € / Mt.) | `company.pro_8_mt` |
| 1048 | ⬜ | L284 | `attr:title` | Zum Co-Admin ernennen | `company.zum_coadmin_ernennen` |
| 1049 | ⬜ | L286 | `template_text` | Zum Co-Admin | `company.zum_coadmin` |
| 1050 | ⬜ | L301 | `template_text` | Noch keine Mitarbeiter im Unternehmen. | `company.noch_keine_mitarbeiter_im_unternehmen` |
| 1051 | ⬜ | L312 | `template_text` | Offene Einladungen | `company.offene_einladungen` |
| 1052 | ⬜ | L317 | `template_text` | Diese Personen wurden eingeladen und haben sich noch nicht registriert. | `company.diese_personen_wurden_eingeladen_und_hab` |
| 1053 | ⬜ | L324 | `template_text` | Rolle:  ·                   Lizenz:  ·                   eingeladen am | `company.rolle_lizenz_eingeladen_am` |
| 1054 | ⬜ | L330 | `template_text` | Link kopieren | `company.link_kopieren` |
| 1055 | ⬜ | L342 | `template_text` | Zugriffsmatrix &amp; Berechtigungsübersicht | `company.zugriffsmatrix_amp_berechtigungsübersich` |
| 1056 | ⬜ | L346 | `template_text` | Wer wurde wo eingeladen, wer hat Zugriff auf welche Ordner und Projekte. | `company.wer_wurde_wo_eingeladen_wer_hat_zugriff` |
| 1057 | ⬜ | L350 | `attr:placeholder` | Nach Name oder E-Mail filtern... | `company.nach_name_oder_email_filtern` |
| 1058 | ⬜ | L362 | `template_text` | Zugriff (Ordner &amp; Projekte) | `company.zugriff_ordner_amp_projekte` |
| 1059 | ⬜ | L1581 | `script_literal` | Möchtest du diese Gruppe wirklich löschen? | `company.möchtest_du_diese_gruppe_wirklich_lösche` |
| 1060 | ⬜ | L1587 | `script_literal` | Gruppe gelöscht. | `company.gruppe_gelöscht` |
| 1061 | ⬜ | L1590 | `script_literal` | Fehler beim Löschen der Gruppe | `company.fehler_beim_löschen_der_gruppe` |
| 1062 | ⬜ | L1899 | `script_literal` | Fehler beim Ändern der Rolle | `company.fehler_beim_ändern_der_rolle` |
| 1063 | ⬜ | L2012 | `script_literal` | wirklich löschen? | `company.wirklich_löschen` |
| 1064 | ⬜ | L2018 | `script_literal` | Firmenvorlage gelöscht. | `company.firmenvorlage_gelöscht` |
| 1065 | ⬜ | L2021 | `script_literal` | Fehler beim Löschen der Vorlage | `company.fehler_beim_löschen_der_vorlage` |
| 1066 | ⬜ | L2039 | `script_literal` | Upgrade-Anfrage wurde an das Taskster-Team übermittelt. | `company.upgradeanfrage_wurde_an_das_tasksterteam` |

---

## Phase 5: Sonstige (app.vue etc.)
*12 Strings in 1 Dateien*

### 📄 `app.vue` (12 Strings)

| # | ✅ | Zeile | Typ | Hardcodierter Text | Vorgeschlagener Key |
|---|---|-------|-----|-------------------|-------------------|
| 1067 | ⬜ | L4 | `attr:alt` | Taskster Wallpaper | `common.taskster_wallpaper` |
| 1068 | ⬜ | L27 | `template_text` | Menü | `common.menü` |
| 1069 | ⬜ | L179 | `template_text` | Webseite & CMS | `common.webseite_cms` |
| 1070 | ⬜ | L190 | `attr:title` | KI & AI-Pläne | `common.ki_aipläne` |
| 1071 | ⬜ | L191 | `template_text` | KI & AI-Pläne | `common.ki_aipläne` |
| 1072 | ⬜ | L202 | `attr:title` | Security & Audit Logs | `common.security_audit_logs` |
| 1073 | ⬜ | L203 | `template_text` | Audit Logs | `common.audit_logs` |
| 1074 | ⬜ | L386 | `template_text` | Webseite & CMS | `common.webseite_cms` |
| 1075 | ⬜ | L394 | `attr:title` | KI & AI-Pläne | `common.ki_aipläne` |
| 1076 | ⬜ | L397 | `template_text` | KI & AI-Pläne | `common.ki_aipläne` |
| 1077 | ⬜ | L405 | `attr:title` | Security & Audit Logs | `common.security_audit_logs` |
| 1078 | ⬜ | L408 | `template_text` | Audit Logs | `common.audit_logs` |

---

## 🔧 Nach Abschluss jeder Phase

1. `python generate_index.py` ausführen
2. `npm run dev` starten und Sprachumschaltung testen
3. Alle 3 Sprachen durchschalten: DE → EN → SK
4. Phase-Status in dieser Checkliste auf ✅ setzen

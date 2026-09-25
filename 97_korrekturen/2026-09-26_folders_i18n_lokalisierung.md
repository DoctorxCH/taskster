# Korrektur-Log: Ordneransicht (pages/folders/[id].vue) i18n-Lokalisierung

**Datum:** 2026-09-26  
**Betroffene Komponenten:** `pages/folders/[id].vue`, `i18n/locales/de.json`, `i18n/locales/en.json`, `i18n/locales/sk.json`  
**Thema:** Entfernung von hardgecodeten Strings in der Ordner-Detailansicht und Synchronisation der Sprachdateien.

---

### Ursachenanalyse & Umfang
1. In `pages/folders/[id].vue` waren zahlreiche UI-Strings (Header-Statistiken, Vorlagen-Buttons, Dropdown-Menü, Controlling-KPIs, Projektkarten-Labels, Tabellenkopfzeilen, Journal-Feed, benutzerdefinierte Felder und Kontakt-Karten) fest auf Deutsch hinterlegt.
2. Beim Umschalten auf Englisch (`en`) oder Slowakisch (`sk`) blieben diese Komponenten unübersetzt.

### Durchgeführte Maßnahmen
1. **Erweiterung der Sprachdateien:**
   - Hinzufügen aller benötigten Übersetzungsschlüssel unter den Namespaces `folders.*`, `journal.*` und `common.*` in `de.json`, `en.json` und `sk.json`.
   - Alle 3 Sprachdateien sind nun mit identischer Schlüsselanzahl (1649 Keys) synchronisiert.
2. **Template-Lokalisierung in `pages/folders/[id].vue`:**
   - 109 Stellen im Template durch reaktive `$t(...)`-Übersetzungsaufrufe ersetzt:
     - Header & Breadcrumbs: `folders.projekte`, `folders.projekt`, `folders.du_owner`, `folders.owner`, `folders.ordner_badge`, `folders.vorlage`, `folders.ordner_vorlage_zuweisen`, `folders.ordner_vorlage_title`
     - Action-Buttons & Dropdown: `folders.plus_journaleintrag`, `folders.plus_dokument_ki`, `folders.neuen_journaleintrag_erfassen`, `folders.dokument_ki_analysieren_title`, `folders.weitere_aktionen`, `folders.mehr`, `folders.ansicht`, `folders.kacheln`, `folders.liste`, `folders.ordner_teilen_btn`, `folders.ordner_anpassen`, `folders.projekt_importieren`, `folders.neues_projekt`
     - Controlling-Leiste: `folders.controlling`, `folders.std_gesamtaufwand`, `folders.details_anzeigen`, `folders.details_einklappen`, `folders.ist`, `folders.oeffnen`, `folders.auslastung`, `folders.budget_ueberschritten`, `folders.verbleibend`, `folders.kein_stundenbudget_festgelegt`, `folders.kostenbudget`
     - Tab-Leiste: `folders.projekte`, `folders.projektjournal`, `folders.benutzerdefinierte_felder`, `common.kontakte`
     - Projektsuche, Filter & Ansichten: `folders.projekte_durchsuchen_placeholder`, `folders.alle`, `folders.aktiv`, `folders.erledigt`, `folders.kachelansicht`, `folders.listenansicht`, `folders.keine_passenden_projekte`, `folders.noch_keine_projekte_in_diesem_ordne`, `folders.passe_suchkriterien_an`, `folders.erstelle_jetzt_dein_erstes_projekt_`, `folders.neues_projekt_anlegen`, `folders.filter_zuruecksetzen`
     - Projekt-Karten & Tabellen-Headers: `folders.standard`, `folders.abschnitte`, `folders.aufgaben`, `folders.team`, `folders.aufwand`, `folders.projekt_oeffnen`, `folders.projekttitel`, `common.status`, `folders.abschnitte_aufgaben`, `folders.aufwand_budget`, `folders.projekt_felder`, `folders.aktion`, `folders.in_abschnitten`
     - Journal-Tab: `folders.projektjournal_und_logbuch`, `folders.journal_subline`, `folders.bausitzungen_protokolle`, `folders.notizen_emails`, `folders.journal_suche_placeholder`, `folders.alle_kategorien`, `folders.alle_projekte_im_ordner`, `folders.keine_passenden_journaleintraege`, `folders.journal_empty_desc`, `journal.von`, `journal.verknuepft`, `journal.ki_aktualisieren`, `journal.ki_analyse`, `journal.ki_agent`, `journal.neu_analysieren`, `journal.inhalt_notizen`, `journal.weniger_anzeigen`, `journal.mehr_anzeigen`, `journal.verknuepfte_aufgabe`, `journal.offen`, `journal.aufgabe_im_projekt_oeffnen`, `journal.teilnehmer`, `journal.dateianhaenge`
     - Custom-Fields-Tab: `folders.benutzerdefinierte_felder_fuer_ordner`, `folders.benutzerdefinierte_felder_desc`, `folders.neues_feld_anlegen`, `folders.noch_keine_felder`, `folders.noch_keine_felder_desc`, `folders.erstes_feld_anlegen`, `folders.feld_bezeichnung`, `folders.bereich`, `folders.feldtyp`, `folders.pflichtfeld`, `folders.bedingte_logik`, `folders.details_optionen`, `folders.aktionen`, `folders.projekt_feld`, `folders.aufgaben_feld`, `common.ja`, `common.nein`, `common.bearbeiten`
     - Kontakte-Tab: `folders.ordner_kontakte`, `folders.neuer_kontakt`, `folders.noch_keine_kontakte`, `folders.noch_keine_kontakte_desc`, `folders.ersten_kontakt_anlegen`, `folders.ordner_badge`
3. **Build & Validierung:**
   - `npm run build:dist` erfolgreich ausgeführt (Generierung und Sync nach Git-Root).
   - `.agent_index.json` via `python generate_index.py --stats` aktualisiert.

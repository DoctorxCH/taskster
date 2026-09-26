#!/usr/bin/env python3
"""
Taskster i18n – Update Folder Translations
Localizes pages/folders/[id].vue completely into de, en, and sk.
"""
import json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCALES = ROOT / "i18n" / "locales"

# Full dictionary of translations for pages/folders/[id].vue
TRANSLATIONS = [
    # Placeholders
    ("folders.suche_vorlage_placeholder", "🔍 Vorlage suchen...", "🔍 Search template...", "🔍 Hľadať šablónu..."),
    ("folders.phase_hinzufuegen_placeholder", "+ Phase hinzufügen...", "+ Add phase...", "+ Pridať fázu..."),
    ("folders.csv_paste_placeholder", "Spalte1;Spalte2;Spalte3\nWert1;Wert2;Wert3\n(Oder einfach Zeilen aus Excel kopieren und Strg+V drücken)", "Column1;Column2;Column3\nVal1;Val2;Val3\n(Or copy rows from Excel and press Ctrl+V)", "Stĺpec1;Stĺpec2;Stĺpec3\nHodn1;Hodn2;Hodn3\n(Alebo skopírujte riadky z Excelu a stlačte Ctrl+V)"),
    ("folders.projekt_name_placeholder", "z.B. FTTH Ausbau Bern Süd oder Wohnzimmer Renovation", "e.g. Fiber expansion South or Living room renovation", "napr. Výstavba optickej siete Juh alebo Rekonštrukcia obývačky"),
    ("folders.projekt_details_placeholder", "Details, Notizen oder Beschreibung...", "Details, notes or description...", "Podrobnosti, poznámky alebo popis..."),
    ("folders.phasenname_placeholder", "Phasenname", "Phase name", "Názov fázy"),
    ("folders.neuer_abschnitt_placeholder", "+ Neuer Abschnitt (z.B. Zwischenprüfung, Abnahme)...", "+ New section (e.g. Interim inspection, Acceptance)...", "+ Nová sekcia (napr. Priebežná kontrola, Prevzatie)..."),
    ("folders.option_eingeben_placeholder", "+ Option eingeben und Enter drücken", "+ Enter option and press Enter", "+ Zadajte možnosť a stlačte Enter"),
    ("folders.erwarteter_wert_placeholder", "Erwarteter Wert...", "Expected value...", "Očakávaná hodnota..."),
    ("folders.strasse_placeholder", "Hauptstrasse 12", "Main Street 12", "Hlavná ulica 12"),
    ("folders.hinweise_placeholder", "Wichtige Hinweise oder Erreichbarkeit...", "Important notes or availability...", "Dôležité upozornenia alebo dostupnosť..."),

    # Titles / Tooltips
    ("folders.standard_projekt_title", "Standard-Projekt dieses Ordners", "Default project of this folder", "Predvolený projekt tohto priečinka"),
    ("folders.zum_projekt_title", "Zum Projekt springen", "Jump to project", "Prejsť na projekt"),
    ("folders.eintrag_bearbeiten_title", "Eintrag bearbeiten", "Edit entry", "Upraviť záznam"),
    ("folders.eintrag_loeschen_title", "Eintrag löschen", "Delete entry", "Odstrániť záznam"),
    ("folders.feld_bearbeiten_title", "Feld bearbeiten", "Edit field", "Upraviť pole"),
    ("folders.feld_loeschen_title", "Feld löschen", "Delete field", "Odstrániť pole"),
    ("folders.whatsapp_title", "WhatsApp Chat öffnen", "Open WhatsApp chat", "Otvoriť WhatsApp chat"),
    ("folders.bearbeiten_title", "Bearbeiten", "Edit", "Upraviť"),
    ("folders.loeschen_title", "Löschen", "Delete", "Odstrániť"),
    ("folders.phase_entfernen_title", "Phase entfernen", "Remove phase", "Odstrániť fázu"),
    ("folders.feld_entfernen_title", "Feld entfernen", "Remove field", "Odstrániť pole"),
    ("folders.logik_entfernen_title", "Logik entfernen", "Remove logic", "Odstrániť logiku"),
    ("folders.abschnitt_entfernen_title", "Abschnitt entfernen", "Remove section", "Odstrániť sekciu"),
    ("folders.nach_oben_title", "Nach oben verschieben", "Move up", "Posunúť nahor"),
    ("folders.nach_unten_title", "Nach unten verschieben", "Move down", "Posunúť nadol"),
    ("folders.gruppe_entfernen_title", "Gruppe entfernen", "Remove group", "Odstrániť skupinu"),
    ("folders.mitglied_entfernen_title", "Mitglied entfernen", "Remove member", "Odstrániť člena"),

    # Template UI Texts
    ("folders.notiz_badge", "📝 Notiz", "📝 Note", "📝 Poznámka"),
    ("folders.vorgeschlagene_aktionen", "Vorgeschlagene Aktionen (KI-Agent)", "Suggested actions (AI agent)", "Navrhované akcie (AI agent)"),
    ("folders.verknuepfung_loesen", "-- Verknüpfung lösen --", "-- Unlink --", "-- Zrušiť prepojenie --"),
    ("folders.aufgabe_zuweisen_label", "Aufgabe zuweisen:", "Assign task:", "Priradiť úlohu:"),
    ("folders.aufgabe_auswaehlen_opt", "-- Aufgabe auswählen --", "-- Select task --", "-- Vybrať úlohu --"),
    ("folders.standard_felder_logik", "Standard-Aufgabenfelder & Sichtbarkeits-Logik", "Default task fields & visibility logic", "Štandardné polia úloh a logika viditeľnosti"),
    ("folders.immer_sichtbar", "Immer sichtbar", "Always visible", "Vždy viditeľné"),
    ("folders.neues_projekt_erstellen", "Neues Projekt erstellen", "Create new project", "Vytvoriť nový projekt"),
    ("folders.aus_vorlage_empfohlen", "Aus Vorlage (Empfohlen)", "From template (Recommended)", "Zo šablóny (Odporúčané)"),
    ("folders.excel_csv_import", "Excel / CSV Import", "Excel / CSV Import", "Import z Excel / CSV"),
    ("folders.leeres_projekt_blanko", "Leeres Projekt (Blanko)", "Empty project (Blank)", "Prázdny projekt (Čistý)"),
    ("folders.job_gewerbe", "Job & Gewerbe", "Job & Business", "Práca a podnikanie"),
    ("folders.privat_familie", "Privat & Familie", "Private & Family", "Súkromné a rodina"),
    ("folders.gewaehlte_vorlage", "✓ Gewählte Vorlage:", "✓ Selected template:", "✓ Vybraná šablóna:"),
    ("folders.konfiguration_anpassen", "Konfiguration anpassen", "Customize configuration", "Prispôsobiť konfiguráciu"),
    ("folders.phasen_anpassen_info", "Du kannst Phasen vor der Erstellung anpassen oder entfernen", "You can customize or remove phases before creation", "Pred vytvorením môžete fázy prispôsobiť alebo odstrániť"),
    ("folders.felder_anpassen_info", "Felder und Logikregeln können vor Projektstart angepasst werden", "Fields and logic rules can be adjusted before project start", "Polia a pravidlá logiky je možné upraviť pred začiatkom projektu"),
    ("folders.sichtbar_wenn", "Sichtbar wenn:", "Visible when:", "Viditeľné keď:"),
    ("folders.logik_hinzufuegen", "+ Logik hinzufügen", "+ Add logic", "+ Pridať logiku"),
    ("folders.datei_hochladen_ablegen", "Datei hochladen / ablegen", "Upload / drop file", "Nahrať / presunúť súbor"),
    ("folders.text_csv_einfuegen", "Text / CSV einfügen (Strg+V)", "Paste text / CSV (Ctrl+V)", "Vložiť text / CSV (Ctrl+V)"),
    ("folders.muster_excel_herunterladen", "Muster-Excel herunterladen", "Download sample Excel", "Stiahnuť vzorový Excel"),
    ("folders.csv_oder_excel_einfuegen", "CSV- oder aus Excel kopierte Tabellendaten hier einfügen:", "Paste CSV or tabular data copied from Excel here:", "Sem vložte CSV alebo tabuľkové údaje skopírované z Excelu:"),
    ("folders.daten_jetzt_analysieren", "Daten jetzt analysieren", "Analyze data now", "Analyzovať údaje"),
    ("folders.datenquelle", "Datenquelle:", "Data source:", "Zdroj údajov:"),
    ("folders.direkt_eingefuegter_text", "Direkt eingefügter Text", "Directly pasted text", "Priamo vložený text"),
    ("folders.projekte_gefunden_spalten", "Projekt(e) gefunden, {cols} Spalten", "project(s) found, {cols} columns", "projektov nájdených, {cols} stĺpcov"),
    ("folders.bitte_weise_mindestens_hinweis", "Bitte weise mindestens einer Spalte das Feld", "Please assign the field", "Priraďte aspoň jednému stĺpcu pole"),
    ("folders.projekttitel_pflicht_hinweis", "«📌 Projekttitel (Pflicht)»", "«📌 Project title (Required)»", "«📌 Názov projektu (Povinné)»"),
    ("folders.zu_um_import_hinweis", "zu, um den Import durchzuführen.", "to perform the import.", "pre vykonanie importu."),
    ("folders.workflow_vorlage_anwenden", "Workflow-Vorlage anwenden (optional):", "Apply workflow template (optional):", "Použiť šablónu pracovného postupu (voliteľné):"),
    ("folders.uebernimmt_phasen_info", "Übernimmt Phasen & Vorlagen-Zusatzfelder inkl. Logik", "Applies phases & template custom fields incl. logic", "Prevezme fázy a vlastné polia šablóny vrátane logiky"),
    ("folders.keine_vorlage_option", "-- Keine Vorlage (Eigene Phasen & Standard nutzen) --", "-- No template (Use own phases & defaults) --", "-- Žiadna šablóna (Použiť vlastné fázy a predvolené) --"),
    ("folders.projekttitel_pflicht", "Projekttitel ist Pflichtfeld", "Project title is a required field", "Názov projektu je povinné pole"),
    ("folders.spalte_in_excel_csv", "Spalte in Excel / CSV", "Column in Excel / CSV", "Stĺpec v Excel / CSV"),
    ("folders.beispielwert_zeile1", "Beispielwert (Zeile 1)", "Sample value (Row 1)", "Príklad hodnoty (1. riadok)"),
    ("folders.zuweisung_an_taskster", "Zuweisung an Taskster Projekt-Feld", "Assignment to Taskster project field", "Priradenie k poľu projektu Taskster"),
    ("folders.nicht_importieren", "-- Nicht importieren --", "-- Do not import --", "-- Neimportovať --"),
    ("folders.aktion_neue_aufgabe", "✅ [Aktion] Neue Aufgabe erstellen", "✅ [Action] Create new task", "✅ [Akcia] Vytvoriť novú úlohu"),
    ("folders.faelligkeitsdatum_opt", "📅 Fälligkeitsdatum", "📅 Due date", "📅 Dátum splatnosti"),
    ("folders.status_opt", "🔄 Status (active/archived/completed)", "🔄 Status (active/archived/completed)", "🔄 Stav (aktívny/archivovaný/dokončený)"),
    ("folders.waehrung_opt", "💰 Währung (CHF, EUR, USD)", "💰 Currency (CHF, EUR, USD)", "💰 Mena (CHF, EUR, USD)"),
    ("folders.budget_stunden_opt", "⏱️ Budget Stunden", "⏱️ Budget hours", "⏱️ Rozpočtované hodiny"),
    ("folders.workflow_abschnitte_import", "Workflow-Abschnitte (Phasen) für importierte Projekte:", "Workflow sections (phases) for imported projects:", "Sekcie pracovného postupu (fázy) pre importované projekty:"),
    ("folders.sichtbarkeit_projekts", "Sichtbarkeit des Projekts", "Project visibility", "Viditeľnosť projektu"),
    ("folders.privat_standard", "🔒 Privat (Standard)", "🔒 Private (Default)", "🔒 Súkromné (Predvolené)"),
    ("folders.nicht_ausgewaehlt", "-- Nicht ausgewählt --", "-- Not selected --", "-- Nevybrané --"),
    ("folders.projektordner_anpassen", "Projektordner anpassen", "Customize project folder", "Prispôsobiť priečinok projektu"),
    ("folders.name_des_projektordners", "Name des Projektordners", "Name of the project folder", "Názov priečinka projektu"),
    ("folders.icon_aus_liste", "Icon aus Liste auswählen", "Select icon from list", "Vybrať ikonu zo zoznamu"),
    ("folders.ausgewaehltes_icon", "Ausgewähltes Icon:", "Selected icon:", "Vybraná ikona:"),
    ("folders.projektvorlage_fuer_ordner", "Projektvorlage für diesen Ordner", "Project template for this folder", "Šablóna projektu pre tento priečinok"),
    ("folders.keine_vorlage_freie_abschnitte", "Keine Vorlage (Freie / Manuelle Abschnitte)", "No template (Free / Manual sections)", "Žiadna šablóna (Voľné / Manuálne sekcie)"),
    ("folders.info_label", "Info:", "Info:", "Informácia:"),
    ("folders.workflow_phasen_abschnitte", "Workflow-Phasen / Abschnitte", "Workflow phases / sections", "Fázy / sekcie pracovného postupu"),
    ("folders.sichtbarkeit_ordners", "Sichtbarkeit des Ordners", "Folder visibility", "Viditeľnosť priečinka"),
    ("folders.dieser_ordner_standard_privat", "Dieser Ordner ist standardmäßig privat. Nutze den Button", "This folder is private by default. Use the button", "Tento priečinok je predvolene súkromný. Použite tlačidlo"),
    ("folders.standard_projekt_festlegen", "⭐ Standard-Projekt festlegen (1 Projekt muss Standard sein)", "⭐ Set default project (1 project must be default)", "⭐ Nastaviť predvolený projekt (1 projekt musí byť predvolený)"),
    ("folders.zusatzfelder_in_diesem_ordner", "Zusatzfelder in diesem Ordner", "Custom fields in this folder", "Vlastné polia v tomto priečinku"),
    ("folders.optionen_label", "Optionen:", "Options:", "Možnosti:"),
    ("folders.kollegen_einladen_info", "Kollegen zu diesem Ordner einladen (Editor oder Viewer)", "Invite colleagues to this folder (Editor or Viewer)", "Pozvať kolegov do tohto priečinka (Editor alebo Čitateľ)"),
    ("folders.projektordner_teilen", "Projektordner teilen", "Share project folder", "Zdieľať priečinok projektu"),
    ("folders.sichtbarkeit_im_unternehmen", "Sichtbarkeit im Unternehmen", "Visibility in the company", "Viditeľnosť v spoločnosti"),
    ("folders.mitglied_hinzufuegen", "Mitglied zum Ordner hinzufügen", "Add member to folder", "Pridať člena do priečinka"),
    ("folders.kollege_auswaehlen", "Kollege aus Unternehmen auswählen", "Select colleague from company", "Vybrať kolegu z firmy"),
    ("folders.oder_per_email", "-- Oder per E-Mail unten eingeben --", "-- Or enter email below --", "-- Alebo zadajte e-mail nižšie --"),
    ("folders.rolle_editor", "Editor (Bearbeiten)", "Editor (Edit)", "Editor (Úprava)"),
    ("folders.rolle_viewer", "Viewer (Nur Lesen)", "Viewer (Read only)", "Čitateľ (Len na čítanie)"),
    ("folders.gruppe_berechtigen", "Gruppe zum Ordner berechtigen", "Authorize group for folder", "Oprávniť skupinu pre priečinok"),
    ("folders.gruppe_auswaehlen", "Gruppe auswählen", "Select group", "Vybrať skupinu"),
    ("folders.gruppe_waehlen_opt", "-- Gruppe wählen --", "-- Select group --", "-- Vybrať skupinu --"),
    ("folders.rolle_fuer_gruppe", "Rolle für die Gruppe", "Role for group", "Rola pre skupinu"),
    ("folders.rolle_admin", "Admin (Vollzugriff)", "Admin (Full access)", "Správca (Plný prístup)"),
    ("folders.zugewiesene_gruppen", "Zugewiesene Gruppen:", "Assigned groups:", "Priradené skupiny:"),
    ("folders.personen_mit_zugriff", "Personen mit Zugriff", "People with access", "Osoby s prístupom"),
    ("folders.gueltigkeitsbereich", "Gültigkeitsbereich", "Scope", "Rozsah platnosti"),
    ("folders.typ_textzeile", "Textzeile (kurz)", "Text line (short)", "Textový riadok (krátky)"),
    ("folders.typ_mehrzeilig", "Mehrzeiliger Text / Notizfeld", "Multiline text / Note field", "Viacriadkový text / Poznámka"),
    ("folders.typ_auswahlliste", "Auswahlliste (Dropdown)", "Selection list (Dropdown)", "Výberový zoznam (Rozbaľovací)"),
    ("folders.typ_checkbox", "Ja / Nein (Checkbox)", "Yes / No (Checkbox)", "Áno / Nie (Začiarkavacie pole)"),
    ("folders.typ_link", "Link / URL", "Link / URL", "Odkaz / URL"),
    ("folders.optionen_fuer_auswahlliste", "Optionen für Auswahlliste", "Options for selection list", "Možnosti pre výberový zoznam"),
    ("folders.pflichtfeld_eingabe", "Pflichtfeld (Eingabe erforderlich)", "Required field (input mandatory)", "Povinné pole (vyžaduje sa zadanie)"),
    ("folders.bedingte_sichtbarkeit", "Bedingte Sichtbarkeit (Logik)", "Conditional visibility (Logic)", "Podmienená viditeľnosť (Logika)"),
    ("folders.nur_anzeigen_wenn_erfuellt", "Dieses Feld nur anzeigen, wenn eine Bedingung erfüllt ist.", "Only show this field when a condition is met.", "Zobraziť toto pole iba pri splnení podmienky."),
    ("folders.feld_auswaehlen_opt", "-- Feld auswählen --", "-- Select field --", "-- Vybrať pole --"),
    ("folders.status_field", "🔄 Status", "🔄 Status", "🔄 Stav"),
    ("folders.prioritaet_field", "⚡ Priorität", "⚡ Priority", "⚡ Priorita"),
    ("folders.faelligkeit_field", "📅 Fälligkeitsdatum", "📅 Due date", "📅 Dátum splatnosti"),
    ("folders.status_todo", "Zu erledigen (todo)", "To do (todo)", "Na vybavenie (todo)"),
    ("folders.status_in_progress", "In Bearbeitung (in_progress)", "In progress (in_progress)", "V riešení (in_progress)"),
    ("folders.status_review", "In Prüfung (review)", "Under review (review)", "Na kontrole (review)"),
    ("folders.status_done", "Abgeschlossen (done)", "Completed (done)", "Dokončené (done)"),
    ("folders.jemand_zugewiesen", "Jemand zugewiesen", "Someone assigned", "Niekto priradený"),
    ("folders.niemand_zugewiesen", "Niemand zugewiesen (Offen)", "No one assigned (Open)", "Nikto nepriradený (Otvorené)"),
    ("folders.niemand_zugewiesen_opt", "Niemand zugewiesen", "No one assigned", "Nikto nepriradený"),
    ("folders.datum_gesetzt", "Datum ist gesetzt", "Date is set", "Dátum je nastavený"),
    ("folders.datum_gesetzt_opt", "Datum gesetzt", "Date set", "Dátum nastavený"),
    ("folders.kein_datum_gesetzt", "Kein Datum gesetzt", "No date set", "Žiadny dátum"),
    ("folders.heute_faellig", "Heute fällig", "Due today", "Splatné dnes"),
    ("folders.ueberfaellig", "Überfällig", "Overdue", "Po splatnosti"),
    ("folders.farbe_gesetzt", "Farbe ist gesetzt", "Color is set", "Farba je nastavená"),
    ("folders.farbe_gesetzt_opt", "Farbe gesetzt", "Color set", "Farba nastavená"),
    ("folders.keine_farbe_gesetzt", "Keine Farbe gesetzt", "No color set", "Žiadna farba"),
    ("folders.tag_vorhanden", "Mindestens ein Tag vorhanden", "At least one tag present", "Aspoň jeden štítok"),
    ("folders.tags_vorhanden_opt", "Tags vorhanden", "Tags present", "Štítky prítomné"),
    ("folders.keine_tags", "Keine Tags vorhanden", "No tags present", "Žiadne štítky"),
    ("folders.keine_tags_opt", "Keine Tags", "No tags", "Žiadne štítky"),
    ("folders.option_waehlen_opt", "-- Option wählen --", "-- Select option --", "-- Vybrať možnosť --"),
    ("folders.bedingte_logik_festlegen", "Bedingte Logik festlegen", "Set conditional logic", "Nastaviť podmienenú logiku"),
    ("folders.abhaengig_von_feld", "Abhängig von Feld:", "Dependent on field:", "Závislé od poľa:"),
    ("folders.bedingungswert_label", "Bedingungswert (Muss übereinstimmen):", "Condition value (Must match):", "Hodnota podmienky (Musí sa zhodovať):"),
    ("folders.firma_unternehmen", "Firma / Unternehmen", "Company / Business", "Firma / Spoločnosť"),
    ("folders.funktion_rolle", "Funktion / Rolle", "Function / Role", "Funkcia / Rola"),
    ("folders.mobiltelefon_wa", "Mobiltelefon (WhatsApp)", "Mobile phone (WhatsApp)", "Mobilný telefón (WhatsApp)"),
    ("folders.telefon_festnetz", "Telefon Festnetz", "Landline phone", "Pevná linka"),
    ("folders.strasse_hausnr", "Strasse & Hausnr.", "Street & house no.", "Ulica a číslo domu"),
    ("folders.plz_ort", "PLZ & Ort", "ZIP & City", "PSČ a mesto"),
    ("folders.notizen_bemerkungen", "Notizen / Bemerkungen", "Notes / Remarks", "Poznámky / Pripomienky"),

    # Script Literals & Modals
    ("folders.kat_regiearbeit", "Regiearbeit", "Day work", "Réžijné práce"),
    ("folders.kat_email", "E-Mail", "Email", "E-mail"),
    ("folders.kat_bausitzung", "Bausitzung", "Site meeting", "Stavebná porada"),
    ("folders.kat_bautagebuch", "Bautagebuch", "Construction diary", "Stavebný denník"),
    ("folders.kat_notiz", "Notiz", "Note", "Poznámka"),
    ("folders.kat_allgemein", "Allgemein", "General", "Všeobecné"),
    ("folders.journal_loeschen_title", "Journal-Eintrag löschen", "Delete journal entry", "Odstrániť žurnálový záznam"),
    ("folders.vorgang_unwiderruflich", "Dieser Vorgang kann nicht rückgängig gemacht werden", "This action cannot be undone", "Túto akciu nie je možné vrátiť späť"),
    ("folders.journal_loeschen_confirm", "Möchtest du diesen Journal-Eintrag wirklich unwiderruflich löschen?", "Do you really want to permanently delete this journal entry?", "Naozaj chcete natrvalo odstrániť tento žurnálový záznam?"),
    ("folders.eintrag_loeschen_btn", "Eintrag löschen", "Delete entry", "Odstrániť záznam"),
    ("folders.feld_loeschen_title", "Benutzerdefiniertes Feld löschen", "Delete custom field", "Odstrániť vlastné pole"),
    ("folders.auswirkung_projekte", "Auswirkung auf Projekte", "Impact on projects", "Vplyv na projekty"),
    ("folders.feld_loeschen_confirm", "Dieses benutzerdefinierte Feld wirklich löschen? Alle zugewiesenen Werte in den Projekten dieses Ordners gehen dabei verloren.", "Really delete this custom field? All assigned values in projects of this folder will be lost.", "Naozaj odstrániť toto vlastné pole? Všetky priradené hodnoty v projektoch tohto priečinka sa stratia."),
    ("folders.feld_loeschen_btn", "Feld löschen", "Delete field", "Odstrániť pole"),
    ("folders.kontakt_entfernen_title", "Kontakt entfernen", "Remove contact", "Odstrániť kontakt"),
    ("folders.kontakt_entfernen_confirm", "Möchtest du den Kontakt wirklich entfernen?", "Do you really want to remove this contact?", "Naozaj chcete odstrániť tento kontakt?"),
    ("folders.kontakt_entfernen_btn", "Kontakt entfernen", "Remove contact", "Odstrániť kontakt"),
    ("folders.mitglied_entfernen_title", "Mitglied entfernen", "Remove member", "Odstrániť člena"),
    ("folders.zugriffsrechte_entziehen", "Zugriffsrechte entziehen", "Revoke access rights", "Odobrať prístupové práva"),
    ("folders.mitglied_entfernen_confirm", "Möchtest du dieses Mitglied wirklich aus dem Projektordner entfernen?", "Do you really want to remove this member from the project folder?", "Naozaj chcete tohto člena odstrániť z priečinka projektu?"),
    ("folders.mitglied_entfernen_btn", "Mitglied entfernen", "Remove member", "Odstrániť člena"),
    ("folders.gruppe_entfernen_title", "Gruppe entfernen", "Remove group", "Odstrániť skupinu"),
    ("folders.gruppenzuweisung_aufheben", "Gruppenzuweisung aufheben", "Remove group assignment", "Zrušiť priradenie skupiny"),
    ("folders.gruppe_entfernen_confirm", "Möchtest du diese Gruppe wirklich von diesem Ordner entfernen?", "Do you really want to remove this group from this folder?", "Naozaj chcete odstrániť túto skupinu z tohto priečinka?"),
    ("folders.gruppe_entfernen_btn", "Gruppe entfernen", "Remove group", "Odstrániť skupinu"),
    
    # Template Categories
    ("folders.tpl_standard", "Standard Ordner", "Default Folder", "Štandardný priečinok"),
    ("folders.tpl_bau_tiefbau", "Bau & Tiefbau", "Construction & Civil Engineering", "Stavebníctvo a inžinierske stavby"),
    ("folders.tpl_it_software", "IT & Software", "IT & Software", "IT a softvér"),
    ("folders.tpl_architektur_planung", "Architektur & Planung", "Architecture & Planning", "Architektúra a plánovanie"),
    ("folders.tpl_elektro_energie", "Elektro & Energie", "Electrical & Energy", "Elektro a energetika"),
    ("folders.tpl_montage_service", "Montage & Service", "Assembly & Service", "Montáž a servis"),
    ("folders.tpl_logistik_transport", "Logistik & Transport", "Logistics & Transport", "Logistika a doprava"),
    ("folders.tpl_finanzen_controlling", "Finanzen & Controlling", "Finance & Controlling", "Financie a controlling"),
    ("folders.tpl_recht_notariat", "Recht & Notariat", "Law & Notary", "Právo a notárstvo"),
    ("folders.tpl_gesundheit_praxis", "Gesundheit & Praxis", "Healthcare & Practice", "Zdravotníctvo a ambulancia"),
    ("folders.tpl_immobilien", "Immobilien & Liegenschaften", "Real Estate & Properties", "Nehnuteľnosti a majetok"),
    ("folders.tpl_haus_umbau", "Haus & Umbau", "House & Renovation", "Dom a rekonštrukcia"),
    ("folders.tpl_garten_aussen", "Garten & Aussen", "Garden & Outdoor", "Záhrada a exteriér"),
    ("folders.tpl_wohnen_interior", "Wohnen & Interior", "Living & Interior", "Bývanie a interiér"),
    ("folders.tpl_event_feier", "Event & Feier", "Event & Celebration", "Udalosti a oslavy"),
    ("folders.tpl_reisen_urlaub", "Reisen & Urlaub", "Travel & Vacation", "Cestovanie a dovolenka"),
    ("folders.tpl_fahrzeuge_garage", "Fahrzeuge & Garage", "Vehicles & Garage", "Vozidlá a garáž"),
    ("folders.tpl_privat_steuern", "Privat & Steuern", "Private & Taxes", "Súkromné a dane"),
    ("folders.tpl_ziele_plaene", "Ziele & Pläne", "Goals & Plans", "Ciele a plány"),
    ("folders.tpl_umzug_lager", "Umzug & Lager", "Relocation & Storage", "Sťahovanie a sklad"),
    ("folders.tpl_kreativ_hobby", "Kreativ & Hobby", "Creative & Hobby", "Kreativita a hobby"),
]

def load_json(p):
    with open(p, 'r', encoding='utf-8') as f: return json.load(f)

def save_json(p, d):
    with open(p, 'w', encoding='utf-8') as f: json.dump(d, f, ensure_ascii=False, indent=2)

def update_locales():
    de = load_json(LOCALES / "de.json")
    en = load_json(LOCALES / "en.json")
    sk = load_json(LOCALES / "sk.json")
    added = 0
    for key, de_val, en_val, sk_val in TRANSLATIONS:
        if key not in de:
            de[key] = de_val
            added += 1
        if key not in en:
            en[key] = en_val
        if key not in sk:
            sk[key] = sk_val
    save_json(LOCALES / "de.json", de)
    save_json(LOCALES / "en.json", en)
    save_json(LOCALES / "sk.json", sk)
    print(f"[i18n] {added} new keys added to de/en/sk locales.")

def update_folder_file():
    folder_path = ROOT / "pages" / "folders" / "[id].vue"
    content = folder_path.read_text(encoding='utf-8')
    orig = content

    # Template interpolations / complex texts
    content = content.replace(
        "<span>Vorgeschlagene Aktionen (KI-Agent) ({{ entry.metadata.action_items.length }}):</span>",
        "<span>{{ $t('folders.vorgeschlagene_aktionen') }} ({{ entry.metadata.action_items.length }}):</span>"
    )
    content = content.replace(
        "<span>Job & Gewerbe ({{ templates.filter(t => t.category === 'job').length }})</span>",
        "<span>{{ $t('folders.job_gewerbe') }} ({{ templates.filter(t => t.category === 'job').length }})</span>"
    )
    content = content.replace(
        "<span>Privat & Familie ({{ templates.filter(t => t.category === 'private').length }})</span>",
        "<span>{{ $t('folders.privat_familie') }} ({{ templates.filter(t => t.category === 'private').length }})</span>"
    )
    content = content.replace(
        '<span class="text-cyan-700 font-bold text-sm">✓ Gewählte Vorlage:</span>',
        '<span class="text-cyan-700 font-bold text-sm">{{ $t(\'folders.gewaehlte_vorlage\') }}</span>'
    )
    content = content.replace(
        "<span>Sichtbar wenn: {{ getLogicDescription(cf.logic_rules) }}</span>",
        "<span>{{ $t('folders.sichtbar_wenn') }} {{ getLogicDescription(cf.logic_rules) }}</span>"
    )
    content = content.replace(
        "<span>Datenquelle: <strong>{{ importFileName || 'Direkt eingefügter Text' }}</strong> ({{ importParsedRows.length }} Projekt(e) gefunden, {{ importHeaders.length }} Spalten)</span>",
        "<span>{{ $t('folders.datenquelle') }} <strong>{{ importFileName || $t('folders.direkt_eingefuegter_text') }}</strong> ({{ importParsedRows.length }} {{ $t('folders.projekte_gefunden') }}, {{ importHeaders.length }} {{ $t('folders.spalten') }})</span>"
    )
    content = content.replace(
        "<span>Bitte weise mindestens einer Spalte das Feld <strong>«📌 Projekttitel (Pflicht)»</strong> zu, um den Import durchzuführen.</span>",
        "<span>{{ $t('folders.bitte_weise_mindestens_hinweis') }} <strong>{{ $t('folders.projekttitel_pflicht_hinweis') }}</strong> {{ $t('folders.zu_um_import_hinweis') }}</span>"
    )
    content = content.replace(
        '<p class="text-[11px] text-slate-500 mt-1 font-medium">Ausgewähltes Icon: <span class="text-slate-900 text-base font-bold mr-1">{{ editFolderIcon }}</span></p>',
        '<p class="text-[11px] text-slate-500 mt-1 font-medium">{{ $t(\'folders.ausgewaehltes_icon\') }} <span class="text-slate-900 text-base font-bold mr-1">{{ editFolderIcon }}</span></p>'
    )
    content = content.replace(
        "<p><strong>Info:</strong> {{ selectedEditTemplate.description }}</p>",
        "<p><strong>{{ $t('folders.info_label') }}</strong> {{ selectedEditTemplate.description }}</p>"
    )
    content = content.replace(
        "Workflow-Phasen / Abschnitte ({{ editFolderSections.length }})",
        "{{ $t('folders.workflow_phasen_abschnitte') }} ({{ editFolderSections.length }})"
    )
    content = content.replace(
        "Zusatzfelder in diesem Ordner ({{ fields.length }})",
        "{{ $t('folders.zusatzfelder_in_diesem_ordner') }} ({{ fields.length }})"
    )
    content = content.replace(
        '<span v-if="f.options && (Array.isArray(f.options) ? f.options.length : true)" class="text-[10px] text-cyan-700 block italic">Optionen: {{ formatFieldOptions(f.options) }}</span>',
        '<span v-if="f.options && (Array.isArray(f.options) ? f.options.length : true)" class="text-[10px] text-cyan-700 block italic">{{ $t(\'folders.optionen_label\') }} {{ formatFieldOptions(f.options) }}</span>'
    )
    content = content.replace(
        "Personen mit Zugriff ({{ folderMembers.length }})",
        "{{ $t('folders.personen_mit_zugriff') }} ({{ folderMembers.length }})"
    )
    content = content.replace(
        '<option value="unassigned">Niemand zugewiesen</option>',
        '<option value="unassigned">{{ $t(\'folders.niemand_zugewiesen_opt\') }}</option>'
    )
    content = content.replace(
        '<option value="set">Datum gesetzt</option>',
        '<option value="set">{{ $t(\'folders.datum_gesetzt_opt\') }}</option>'
    )
    content = content.replace(
        '<option value="set">Farbe gesetzt</option>',
        '<option value="set">{{ $t(\'folders.farbe_gesetzt_opt\') }}</option>'
    )
    content = content.replace(
        '<option value="has_tags">Tags vorhanden</option>',
        '<option value="has_tags">{{ $t(\'folders.tags_vorhanden_opt\') }}</option>'
    )
    content = content.replace(
        '<option value="no_tags">Keine Tags</option>',
        '<option value="no_tags">{{ $t(\'folders.keine_tags_opt\') }}</option>'
    )

    # Subtitles, messages, and button texts in modal triggers
    content = content.replace(
        "subtitle: 'Dieser Vorgang kann nicht rückgängig gemacht werden',",
        "subtitle: t('folders.vorgang_unwiderruflich'),"
    )
    content = content.replace(
        "message: 'Möchtest du diesen Journal-Eintrag wirklich unwiderruflich löschen?',",
        "message: t('folders.journal_loeschen_confirm'),"
    )
    content = content.replace(
        "confirmText: 'Eintrag löschen',",
        "confirmText: t('folders.eintrag_loeschen_btn'),"
    )
    content = content.replace(
        "subtitle: 'Auswirkung auf Projekte',",
        "subtitle: t('folders.auswirkung_projekte'),"
    )
    content = content.replace(
        "message: 'Dieses benutzerdefinierte Feld wirklich löschen? Alle zugewiesenen Werte in den Projekten dieses Ordners gehen dabei verloren.',",
        "message: t('folders.feld_loeschen_confirm'),"
    )
    content = content.replace(
        "confirmText: 'Feld löschen',",
        "confirmText: t('folders.feld_loeschen_btn'),"
    )
    content = content.replace(
        "subtitle: 'Zugriffsrechte entziehen',",
        "subtitle: t('folders.zugriffsrechte_entziehen'),"
    )
    content = content.replace(
        "message: 'Möchtest du dieses Mitglied wirklich aus dem Projektordner entfernen?',",
        "message: t('folders.mitglied_entfernen_confirm'),"
    )
    content = content.replace(
        "confirmText: 'Mitglied entfernen',",
        "confirmText: t('folders.mitglied_entfernen_btn'),"
    )
    content = content.replace(
        "subtitle: 'Gruppenzuweisung aufheben',",
        "subtitle: t('folders.gruppenzuweisung_aufheben'),"
    )
    content = content.replace(
        "message: 'Möchtest du diese Gruppe wirklich von diesem Ordner entfernen?',",
        "message: t('folders.gruppe_entfernen_confirm'),"
    )
    content = content.replace(
        "confirmText: 'Gruppe entfernen',",
        "confirmText: t('folders.gruppe_entfernen_btn'),"
    )
    content = content.replace(
        "confirmText: 'Kontakt entfernen',",
        "confirmText: t('folders.kontakt_entfernen_btn'),"
    )
    content = content.replace(
        "label: 'Regiearbeit'",
        "label: t('folders.kat_regiearbeit')"
    )
    content = content.replace(
        "label: 'E-Mail'",
        "label: t('folders.kat_email')"
    )
    content = content.replace(
        "label: 'Bausitzung'",
        "label: t('folders.kat_bausitzung')"
    )
    content = content.replace(
        "label: 'Bautagebuch'",
        "label: t('folders.kat_bautagebuch')"
    )
    content = content.replace(
        "label: 'Notiz'",
        "label: t('folders.kat_notiz')"
    )
    content = content.replace(
        "label: 'Allgemein'",
        "label: t('folders.kat_allgemein')"
    )

    if content != orig:
        folder_path.write_text(content, encoding='utf-8')
        print("[OK] pages/folders/[id].vue successfully updated (pass 2)!")
    else:
        print("[WARN] No further changes made to pages/folders/[id].vue")

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    print("=== Taskster Folder i18n Migration ===")
    update_locales()
    update_folder_file()

if __name__ == '__main__':
    main()

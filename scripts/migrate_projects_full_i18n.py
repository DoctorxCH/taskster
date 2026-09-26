#!/usr/bin/env python3
"""
Comprehensive i18n Migration for pages/projects/[id].vue and i18n locale files (de, en, sk)
"""
import json
import re
import sys
from pathlib import Path

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ROOT = Path(__file__).resolve().parent.parent
LOCALES_DIR = ROOT / "i18n" / "locales"
PROJ_FILE = ROOT / "pages" / "projects" / "[id].vue"

# Definition of all translation keys needed for Projects & Tasks:
# (key, de, en, sk)
TRANSLATIONS = [
    # Header & Breadcrumbs & General
    ("projects.lade_projektdaten", "Lade Projektdaten...", "Loading project data...", "Načítavanie údajov projektu..."),
    ("projects.erfasste_zeit", "Erfasste Zeit", "Tracked time", "Zaznamenaný čas"),
    ("projects.budget", "Budget", "Budget", "Rozpočet"),
    ("projects.faellig", "Fällig", "Due", "Termín"),
    ("projects.ueberfaellig", "Überfällig", "Overdue", "Po termíne"),
    ("projects.journal_sidebar_title", "Projektjournal Seitenleiste öffnen", "Open project journal sidebar", "Otvoriť bočný panel projektového denníka"),
    ("projects.journal", "Journal", "Journal", "Denník"),
    ("projects.weitere_aktionen", "Weitere Aktionen", "More actions", "Ďalšie akcie"),
    ("projects.mehr", "Mehr", "More", "Viac"),
    ("projects.ansicht", "Ansicht", "View", "Zobrazenie"),
    ("projects.kacheln", "Kacheln", "Tiles", "Dlaždice"),
    ("projects.liste", "Liste", "List", "Zoznam"),
    ("projects.projekt_stoppuhr_starten", "Projekt-Stoppuhr starten", "Start project stopwatch", "Spustiť projektové stopky"),
    ("projects.aufgaben_importieren", "Aufgaben importieren", "Import tasks", "Importovať úlohy"),
    ("projects.sprachnotiz", "Sprachnotiz", "Voice note", "Hlasová poznámka"),
    ("projects.neuer_abschnitt", "Neuer Abschnitt", "New section", "Nová sekcia"),
    ("projects.export_enterprise", "Export (Enterprise)", "Export (Enterprise)", "Export (Enterprise)"),
    ("projects.projekt_als_csv", "Projekt als CSV", "Project as CSV", "Projekt ako CSV"),
    ("projects.projekt_als_json", "Projekt als JSON", "Project as JSON", "Projekt ako JSON"),
    ("projects.tab_zeiterfassung", "Zeiterfassung", "Time tracking", "Zaznamenávanie času"),
    ("projects.tab_team", "Team & Berechtigungen", "Team & Permissions", "Tím a oprávnenia"),
    ("projects.tab_einstellungen", "Projekt-Einstellungen", "Project settings", "Nastavenia projektu"),
    ("projects.viewermodus", "Viewer-Modus:", "Viewer mode:", "Režim prehliadača:"),
    ("projects.du_besitzt_leserechte_für_dieses_pr", "Du besitzt Leserechte für dieses Projekt.", "You have read-only access to this project.", "Pre tento projekt máte práva iba na čítanie."),
    ("projects.noch_keine_abschnitte_in_diesem_pro", "Noch keine Abschnitte in diesem Projekt", "No sections in this project yet", "V tomto projekte zatiaľ nie sú žiadne sekcie"),
    ("projects.erstelle_den_ersten_abschnitt_zb_ge", "Erstelle den ersten Abschnitt (z.B. \"Geplant\", \"In Bearbeitung\", \"Abgeschlossen\").", "Create the first section (e.g., \"Planned\", \"In Progress\", \"Completed\").", "Vytvorte prvú sekciu (napr. „Plánované“, „V riešení“, „Dokončené“)."),
    ("projects.ersten_abschnitt_erstellen", "+ Ersten Abschnitt erstellen", "+ Create first section", "+ Vytvoriť prvú sekciu"),

    # Kanban & Table Search & Filters
    ("projects.search_tasks_placeholder", "Aufgaben durchsuchen (Titel, Notizen, Tags)...", "Search tasks (title, notes, tags)...", "Prehľadávať úlohy (názov, poznámky, štítky)..."),
    ("projects.alle_prioritaeten", "Alle Prioritäten", "All priorities", "Všetky priority"),
    ("projects.alle_zustaendigen", "Alle Zuständigen", "All assignees", "Všetci zodpovední"),
    ("projects.nicht_zugewiesen", "Nicht zugewiesen", "Unassigned", "Nepriradené"),
    ("projects.filter_zuruecksetzen", "Filter zurücksetzen", "Reset filters", "Resetovať filtre"),
    ("projects.kanban_board", "Kanban Board", "Kanban Board", "Kanban nástenka"),
    ("projects.board", "Board", "Board", "Nástenka"),
    ("projects.tabellen_ansicht", "Tabellen-Ansicht", "Table view", "Tabuľkové zobrazenie"),
    ("projects.tabelle", "Tabelle", "Table", "Tabuľka"),
    ("projects.abschnitt_ziehen_um_spalte_zu_versc", "Abschnitt ziehen, um Spalte zu verschieben", "Drag section to move column", "Potiahnite sekciu pre presun stĺpca"),
    ("projects.aufgabe_abhaken_status_ändern", "Aufgabe abhaken / Status ändern", "Check off task / change status", "Odškrtnúť úlohu / zmeniť stav"),
    ("projects.zeiterfassung_laeuft", "Zeiterfassung läuft...", "Time tracking running...", "Čas sa zaznamenáva..."),
    ("projects.ziehen_zum_verschieben", "Ziehen zum Verschieben", "Drag to move", "Potiahnite pre presun"),
    ("projects.stoppuhr_auf_diese_aufgabe_starten", "Stoppuhr auf diese Aufgabe starten", "Start stopwatch for this task", "Spustiť stopky pre túto úlohu"),
    ("projects.start", "Start", "Start", "Štart"),
    ("projects.details", "Details →", "Details →", "Podrobnosti →"),
    ("projects.noch_keine_aufgaben", "Noch keine Aufgaben", "No tasks yet", "Zatiaľ žiadne úlohy"),
    ("projects.aufgabe_hinzufügen", "+ Aufgabe hinzufügen", "+ Add task", "+ Pridať úlohu"),
    ("projects.aufgabe_erfassen_1", "+ Aufgabe erfassen", "+ Create task", "+ Zadať úlohu"),
    ("projects.keine_aufgaben_in_diesem_abschnitt", "Keine Aufgaben in diesem Abschnitt.", "No tasks in this section.", "V tejto sekcii nie sú žiadne úlohy."),
    ("projects.keine_aufgaben_gefunden", "Keine Aufgaben gefunden.", "No tasks found.", "Nenašli sa žiadne úlohy."),
    ("projects.titel_beschreibung", "Titel & Beschreibung", "Title & Description", "Názov a popis"),
    ("projects.aufwand_budget", "Aufwand & Budget", "Effort & Budget", "Prácnosť a rozpočet"),
    ("projects.felder", "Felder", "Fields", "Polia"),
    ("projects.stoppuhr_anhalten_zeit_buchen", "Stoppuhr anhalten & Zeit buchen", "Stop stopwatch & book time", "Zastaviť stopky a zaúčtovať čas"),
    ("projects.stoppen", "⏹️ Stoppen", "⏹️ Stop", "⏹️ Zastaviť"),
    ("projects.öffnen", "Öffnen →", "Open →", "Otvoriť →"),

    # Task Priorities
    ("projects.prio_urgent", "🔴 Dringend", "🔴 Urgent", "🔴 Naliehavé"),
    ("projects.prio_high", "🟠 Hoch", "🟠 High", "🟠 Vysoká"),
    ("projects.prio_medium", "🟡 Mittel", "🟡 Medium", "🟡 Stredná"),
    ("projects.prio_low", "🟢 Niedrig", "🟢 Low", "🟢 Nízka"),
    ("projects.prio_normal", "🔵 Normal", "🔵 Normal", "🔵 Normálna"),

    # Task Statuses
    ("projects.status_todo", "Zu erledigen (Todo)", "To do", "Na vybavenie"),
    ("projects.status_in_progress", "In Arbeit (In Progress)", "In progress", "V riešení"),
    ("projects.status_review", "In Prüfung (Review)", "In review", "Na kontrolu"),
    ("projects.status_done", "Abgeschlossen (Done)", "Completed", "Dokončené"),
    ("projects.status_todo_icon", "📋 Zu erledigen (Todo)", "📋 To do", "📋 Na vybavenie"),
    ("projects.status_in_progress_icon", "🔄 In Arbeit (In Progress)", "🔄 In progress", "🔄 V riešení"),
    ("projects.status_review_icon", "🔍 In Prüfung (Review)", "🔍 In review", "🔍 Na kontrolu"),
    ("projects.status_done_icon", "✅ Abgeschlossen (Done)", "✅ Completed", "✅ Dokončené"),

    # Task Drawer & Task Details
    ("projects.aufgabe_bearbeiten", "Aufgabe bearbeiten", "Edit task", "Upraviť úlohu"),
    ("projects.neue_aufgabe_erfassen", "Neue Aufgabe erfassen", "Create new task", "Zadať novú úlohu"),
    ("projects.viewer_readonly", "Viewer Read-Only", "Viewer Read-Only", "Prehliadač iba na čítanie"),
    ("projects.aufgabentitel", "Aufgabentitel", "Task title", "Názov úlohy"),
    ("projects.aufgabentitel_placeholder", "z.B. Konzeptentwurf finalisieren", "e.g. Finalize concept draft", "napr. Dokončiť návrh konceptu"),
    ("projects.aufgabentitel_inline_placeholder", "Aufgabentitel eingeben...", "Enter task title...", "Zadajte názov úlohy..."),
    ("projects.beschreibung", "Beschreibung", "Description", "Popis"),
    ("projects.beschreibung_placeholder", "Detaillierte Aufgabenbeschreibung, Anforderungen oder Zwischenziele...", "Detailed task description, requirements or intermediate goals...", "Podrobný popis úlohy, požiadavky alebo čiastkové ciele..."),
    ("projects.faelligkeitsdatum", "Fälligkeitsdatum", "Due date", "Dátum splatnosti"),
    ("projects.zusatzfelder", "Zusatzfelder", "Custom fields", "Vlastné polia"),
    ("projects.nicht_ausgewaehlt", "-- Nicht ausgewählt --", "-- Not selected --", "-- Nevybrané --"),
    ("projects.keine_auswahl", "-- Keine Auswahl --", "-- No selection --", "-- Žiadny výber --"),
    ("projects.laengeren_text_eingeben", "Längeren Text / Notizen eingeben...", "Enter long text / notes...", "Zadajte dlhší text / poznámky..."),
    ("projects.details_notizen_placeholder", "Details, Notizen oder Beschreibung...", "Details, notes or description...", "Podrobnosti, poznámky alebo popis..."),
    ("projects.neu", "Neu", "New", "Nové"),
    ("projects.gesamtaufwand_title", "Bisher erfasster Gesamtaufwand auf dieser Aufgabe", "Total time tracked on this task so far", "Doteraz zaznamenaný celkový čas na tejto úlohe"),
    ("projects.stoppen_und_buchen", "Stoppen & Buchen", "Stop & Book", "Zastaviť a zaúčtovať"),
    ("projects.stopp", "Stopp", "Stop", "Stop"),
    ("projects.timer_verwerfen", "Timer verwerfen", "Discard timer", "Zahodiť časovač"),
    ("projects.timer_umschalten_title", "Stoppuhr auf diese Aufgabe umschalten", "Switch stopwatch to this task", "Prepnúť stopky na túto úlohu"),
    ("projects.hierher_wechseln", "Hierher wechseln", "Switch here", "Prepnúť sem"),
    ("projects.wechseln", "Wechseln", "Switch", "Prepnúť"),
    ("projects.stoppuhr_starten_title", "Stoppuhr für diese Aufgabe starten", "Start stopwatch for this task", "Spustiť stopky pre túto úlohu"),
    ("projects.schliessen", "Schliessen", "Close", "Zavrieť"),
    ("projects.checkliste_unteraufgaben", "Checkliste & Unteraufgaben", "Checklist & Subtasks", "Kontrolný zoznam a podúlohy"),
    ("projects.erledigt_count", "erledigt", "completed", "hotovo"),
    ("projects.keine_checklisten_punkte", "Keine Checklisten-Punkte oder Unteraufgaben vorhanden.", "No checklist items or subtasks yet.", "Zatiaľ žiadne položky kontrolného zoznamu ani podúlohy."),
    ("projects.hauptpunkt_loeschen", "Hauptpunkt löschen", "Delete main item", "Odstrániť hlavnú položku"),
    ("projects.unterpunkt_loeschen", "Unterpunkt löschen", "Delete subtask", "Odstrániť podpoložku"),
    ("projects.unterpunkt_placeholder", "+ Unterpunkt zur Checkliste hinzufügen...", "+ Add subtask to checklist...", "+ Pridať podpoložku do kontrolného zoznamu..."),
    ("projects.unterpunkt_btn", "+ Unterpunkt", "+ Subtask", "+ Podpoložka"),
    ("projects.hauptpunkt_placeholder", "+ Neuer Haupt-Checklistenpunkt...", "+ New main checklist item...", "+ Nová hlavná položka..."),
    ("projects.hauptpunkt_btn", "+ Hauptpunkt", "+ Main item", "+ Hlavná položka"),

    # Task Drawer: Time & Budget
    ("projects.zeiterfassung_budget", "Zeiterfassung & Budget", "Time tracking & Budget", "Zaznamenávanie času a rozpočet"),
    ("projects.menu_verbergen", "Menü verbergen", "Hide menu", "Skryť ponuku"),
    ("projects.budget_manuell_buchen", "Budget & Manuell buchen", "Budget & Manual booking", "Rozpočet a manuálne zaúčtovanie"),
    ("projects.aufgaben_budget_stunden", "Aufgaben-Budget (Stunden)", "Task budget (hours)", "Rozpočet úlohy (hodiny)"),
    ("projects.aufgaben_budget_betrag", "Aufgaben-Budget (Betrag in {currency})", "Task budget (amount in {currency})", "Rozpočet úlohy (suma v {currency})"),
    ("projects.manuell_zeit_buchen", "Manuell Zeit auf diese Aufgabe buchen", "Log time manually on this task", "Manuálne zaúčtovať čas na túto úlohu"),
    ("projects.wird_mit_stern_markiert", "Wird mit * markiert", "Marked with *", "Označuje sa s *"),
    ("projects.dauer_std", "Dauer (Std.)", "Duration (hrs)", "Trvanie (hod.)"),
    ("projects.datum", "Datum", "Date", "Dátum"),
    ("projects.stundensatz", "Stundensatz", "Hourly rate", "Hodinová sadzba"),
    ("projects.beschreibung_notiz_placeholder", "Beschreibung / Notiz...", "Description / note...", "Popis / poznámka..."),
    ("projects.buchen_btn", "+ Buchen", "+ Book", "+ Zaúčtovať"),
    ("projects.aendern", "Ändern", "Edit", "Upraviť"),
    ("projects.noch_keine_zeiten_gebucht", "Noch keine Zeiten auf diese Aufgabe gebucht.", "No time booked on this task yet.", "Na túto úlohu zatiaľ nebol zaúčtovaný žiadny čas."),

    # Task Drawer: Comments & Attachments
    ("projects.kommentare_notizen", "Kommentare & Besprechungsnotizen", "Comments & Meeting notes", "Komentáre a poznámky z porád"),
    ("projects.noch_keine_kommentare", "Noch keine Kommentare oder Notizen vorhanden.", "No comments or notes yet.", "Zatiaľ žiadne komentáre ani poznámky."),
    ("projects.kommentar_placeholder", "Kommentar schreiben... (Strg+Enter zum Senden)", "Write comment... (Ctrl+Enter to send)", "Napísať komentár... (Ctrl+Enter pre odoslanie)"),
    ("projects.tipp_strg_enter", "Tipp: Mit Strg+Enter absenden", "Tip: Press Ctrl+Enter to send", "Tip: Odoslať pomocou Ctrl+Enter"),
    ("projects.senden", "Senden", "Send", "Odoslať"),
    ("projects.dateianhaenge", "Dateianhänge", "File attachments", "Prílohy"),
    ("projects.dateien_hierher_ziehen", "Dateien hierher ziehen oder klicken zum Auswählen", "Drag files here or click to select", "Presuňte súbory sem alebo kliknite pre výber"),
    ("projects.max_file_size_desc", "Max. 10 MB pro Datei · Bilder, PDFs, Office-Dokumente", "Max. 10 MB per file · Images, PDFs, Office documents", "Max. 10 MB na súbor · Obrázky, PDF, dokumenty Office"),
    ("projects.noch_keine_dateien", "Noch keine Dateien angehängt.", "No files attached yet.", "Zatiaľ nie sú priložené žiadne súbory."),
    ("projects.von_user", "von", "by", "od"),
    ("projects.herunterladen", "Herunterladen", "Download", "Stiahnuť"),
    ("projects.loeschen", "Löschen", "Delete", "Zmazať"),

    # Task Drawer: Sidebar
    ("projects.abschnitt", "Abschnitt", "Section", "Sekcia"),
    ("projects.prioritaet", "Priorität", "Priority", "Priorita"),
    ("projects.zuweisung_label", "👥 Zuweisung", "👥 Assignment", "👥 Priradenie"),
    ("projects.mehrfachauswahl_moeglich", "Mehrfachauswahl möglich", "Multiple selection possible", "Možný viacnásobný výber"),
    ("projects.mitglied_zuweisen_aendern", "+ Mitglied zuweisen / ändern...", "+ Assign / change member...", "+ Priradiť / zmeniť člena..."),
    ("projects.faelligkeitsdatum_label", "📅 Fälligkeitsdatum", "📅 Due date", "📅 Dátum splatnosti"),
    ("projects.farbmarkierung_label", "🎨 Farbmarkierung", "🎨 Color label", "🎨 Farebné označenie"),
    ("projects.farbe_entfernen", "Entfernen", "Remove", "Odstrániť"),
    ("projects.tags_label", "🏷️ Tags", "🏷️ Tags", "🏷️ Štítky"),
    ("projects.keine_tags", "Keine Tags", "No tags", "Žiadne štítky"),
    ("projects.tag_input_placeholder", "Tag + Enter...", "Tag + Enter...", "Štítok + Enter..."),
    ("projects.aufgabe_loeschen_btn", "Aufgabe löschen", "Delete task", "Odstrániť úlohu"),
    ("projects.drawer_create_notice", "Aufgabe wird beim Speichern/Schliessen angelegt", "Task will be created upon save/close", "Úloha sa vytvorí pri uložení/zatvorení"),
    ("projects.drawer_autosave_notice", "Änderungen werden automatisch gespeichert", "Changes are saved automatically", "Zmeny sa ukladajú automaticky"),
    ("projects.aufgabe_erstellen_btn", "Aufgabe erstellen", "Create task", "Vytvoriť úlohu"),

    # Section Management Modal
    ("projects.abschnitt_anlegen_title", "Neuen Abschnitt anlegen", "Create new section", "Vytvoriť novú sekciu"),
    ("projects.abschnitt_titel_label", "Titel des Abschnitts", "Section title", "Názov sekcie"),
    ("projects.abschnitt_titel_placeholder", "z.B. Vorbereitung, In Bearbeitung oder Abnahme", "e.g. Preparation, In Progress or Acceptance", "napr. Príprava, V riešení alebo Prevzatie"),
    ("projects.sichtbarkeits_modus", "Sichtbarkeits-Modus", "Visibility mode", "Režim viditeľnosti"),
    ("projects.sichtbarkeit_standard", "Standard (Alle Projektmitglieder haben Zugriff)", "Standard (All project members have access)", "Štandardné (Všetci členovia projektu majú prístup)"),
    ("projects.sichtbarkeit_eingeschraenkt", "Eingeschränkt (Nur Owner & explizit berechtigte Personen)", "Restricted (Only owner & explicitly authorized persons)", "Obmedzené (Iba vlastník a výslovne oprávnené osoby)"),
    ("projects.abschnitte_verwalten_title", "Projekt-Abschnitte verwalten", "Manage project sections", "Spravovať sekcie projektu"),
    ("projects.abschnittsbezeichnung", "Abschnittsbezeichnung", "Section name", "Názov sekcie"),
    ("projects.farbe_zuruecksetzen", "Farbe zurücksetzen", "Reset color", "Obnoviť farbu"),
    ("projects.nach_oben", "Nach oben verschieben", "Move up", "Posunúť nahor"),
    ("projects.nach_unten", "Nach unten verschieben", "Move down", "Posunúť nadol"),
    ("projects.weiterer_abschnitt_placeholder", "+ Weiterer Abschnitt (z.B. Zwischenprüfung, Abnahme)...", "+ Another section (e.g. interim check, acceptance)...", "+ Ďalšia sekcia (napr. priebežná kontrola, prevzatie)..."),

    # Excel / CSV Import Modal
    ("projects.import_modal_title", "Aufgaben aus Excel / CSV importieren", "Import tasks from Excel / CSV", "Importovať úlohy z Excelu / CSV"),
    ("projects.import_modal_desc", "Lade eine CSV- oder Tabellendatei hoch und weise die Spalten flexibel den Feldern in Taskster zu.", "Upload a CSV or spreadsheet file and flexibly map columns to Taskster fields.", "Nahrajte súbor CSV alebo tabuľku a flexibilne priraďte stĺpce k poliam v Tasksteri."),
    ("projects.import_drag_drop_title", "Excel- (.xlsx, .xls) oder CSV-Datei auswählen oder hierher ziehen", "Select Excel (.xlsx, .xls) or CSV file or drag here", "Vyberte súbor Excel (.xlsx, .xls) alebo CSV alebo ho potiahnite sem"),
    ("projects.import_formats_desc", "Unterstützt Formate: Excel (.xlsx, .xls) sowie CSV, TSV (Trennzeichen: Komma, Semikolon, Tab)", "Supported formats: Excel (.xlsx, .xls) and CSV, TSV (delimiters: comma, semicolon, tab)", "Podporované formáty: Excel (.xlsx, .xls) ako aj CSV, TSV (oddeľovače: čiarka, bodkočiarka, tabulátor)"),
    ("projects.import_file_detected", "Datei erkannt:", "File detected:", "Rozpoznaný súbor:"),
    ("projects.import_rows_found", "Zeilen gefunden", "rows found", "nájdených riadkov"),
    ("projects.import_choose_other_file", "Andere Datei wählen", "Choose another file", "Vybrať iný súbor"),
    ("projects.import_target_section", "Ziel-Abschnitt für importierte Aufgaben:", "Target section for imported tasks:", "Cieľová sekcia pre importované úlohy:"),
    ("projects.import_mapping_title", "Spaltenzuweisung (Mapping):", "Column mapping:", "Priradenie stĺpcov (mapovanie):"),
    ("projects.import_title_mandatory", "Titel-Spalte ist Pflichtfeld", "Title column is required", "Stĺpec s názvom je povinný"),
    ("projects.import_create_custom_field", "+ Eigenes Feld anlegen", "+ Create custom field", "+ Vytvoriť vlastné pole"),
    ("projects.import_col_in_file", "Spalte in Datei", "Column in file", "Stĺpec v súbore"),
    ("projects.import_example_value", "Beispielwert (Zeile 1)", "Example value (row 1)", "Príklad hodnoty (riadok 1)"),
    ("projects.import_mapped_to", "Wird zugewiesen an Feld", "Assigned to field", "Priradí sa k poľu"),
    ("projects.import_ignore", "-- Ignorieren --", "-- Ignore --", "-- Ignorovať --"),
    ("projects.import_standard_fields", "Standard-Felder", "Standard fields", "Štandardné polia"),
    ("projects.import_field_title", "📌 Aufgabentitel (Pflicht)", "📌 Task title (required)", "📌 Názov úlohy (povinné)"),
    ("projects.import_field_desc", "📋 Beschreibung", "📋 Description", "📋 Popis"),
    ("projects.import_field_status", "Status (todo/in_progress/done)", "Status (todo/in_progress/done)", "Stav (todo/in_progress/done)"),
    ("projects.import_field_due", "📅 Fälligkeitsdatum", "📅 Due date", "📅 Dátum splatnosti"),
    ("projects.import_field_priority", "Priorität (niedrig/normal/hoch/dringend)", "Priority (low/normal/high/urgent)", "Priorita (nízka/normálna/vysoká/naliehavá)"),
    ("projects.import_field_tags", "🏷️ Tags", "🏷️ Tags", "🏷️ Štítky"),
    ("projects.import_existing_custom_fields", "Bestehende Zusatzfelder", "Existing custom fields", "Existujúce vlastné polia"),
    ("projects.import_create_as_new_field", "✨ Als neues Zusatzfeld anlegen", "✨ Create as new custom field", "✨ Vytvoriť ako nové vlastné pole"),
    ("projects.import_template_custom_fields", "📋 Vorlagen-Zusatzfelder", "📋 Template custom fields", "📋 Šablónové vlastné polia"),
    ("projects.import_preview_rows", "Vorschau der ersten Zeilen:", "Preview of first rows:", "Náhľad prvých riadkov:"),
    ("projects.import_success_title", "Import erfolgreich abgeschlossen!", "Import completed successfully!", "Import bol úspešne dokončený!"),
    ("projects.import_success_desc", "Es wurden {count} Aufgaben erfolgreich in den Abschnitt eingepflegt.", "{count} tasks were successfully imported into the section.", "Do sekcie bolo úspešne pridaných {count} úloh."),
    ("projects.import_start_btn", "Import starten ({count} Aufgaben)", "Start import ({count} tasks)", "Spustiť import ({count} úloh)"),
    ("projects.import_running", "Importiere...", "Importing...", "Importuje sa..."),

    # Custom Fields Modal & Settings
    ("projects.cf_modal_rule_title", "⚡ Logik-Regel: {label}", "⚡ Logic rule: {label}", "⚡ Pravidlo logiky: {label}"),
    ("projects.cf_modal_edit_field", "Feld bearbeiten", "Edit field", "Upraviť pole"),
    ("projects.cf_modal_new_field", "Neues benutzerdefiniertes Feld", "New custom field", "Nové vlastné pole"),
    ("projects.cf_modal_rule_desc", "Bestimme, unter welcher Bedingung dieses Standard-Feld sichtbar ist.", "Define under which condition this standard field is visible.", "Určte, za akej podmienky je toto štandardné pole viditeľné."),
    ("projects.cf_modal_new_desc", "Definiere ein Attribut für Aufgaben oder das Projekt.", "Define an attribute for tasks or the project.", "Definujte atribút pre úlohy alebo projekt."),
    ("projects.cf_gueltigkeitsbereich", "Gültigkeitsbereich", "Scope", "Rozsah platnosti"),
    ("projects.cf_aufgaben_feld", "Aufgaben-Feld", "Task field", "Pole úlohy"),
    ("projects.cf_projekt_feld", "Projekt-Feld", "Project field", "Pole projektu"),
    ("projects.cf_feld_bezeichnung", "Feld-Bezeichnung (Label)", "Field label", "Označenie poľa"),
    ("projects.cf_feldtyp", "Feldtyp", "Field type", "Typ poľa"),
    ("projects.cf_optionen_komma", "Optionen (Komma-getrennt)", "Options (comma-separated)", "Možnosti (oddelené čiarkou)"),
    ("projects.cf_bedingte_logik", "Bedingte Logik (Feld nur unter Bedingung anzeigen)", "Conditional logic (only show field under condition)", "Podmienená logika (zobraziť pole iba za podmienky)"),
    ("projects.cf_bedingung_definieren", "Bedingung definieren", "Define condition", "Definovať podmienku"),
    ("projects.cf_bedingung_entfernen", "Bedingung entfernen", "Remove condition", "Odstrániť podmienku"),
    ("projects.cf_abhaengig_von_feld", "Abhängig von Feld", "Depends on field", "Závisí od poľa"),
    ("projects.cf_feld_auswaehlen", "-- Feld auswählen --", "-- Select field --", "-- Vybrať pole --"),
    ("projects.cf_standard_aufgabenfelder", "Standard-Aufgabenfelder", "Standard task fields", "Štandardné polia úloh"),
    ("projects.cf_eigene_felder", "Eigene Felder", "Custom fields", "Vlastné polia"),
    ("projects.cf_nur_anzeigen_wenn", "Nur anzeigen wenn Wert gleich:", "Only show if value equals:", "Zobraziť iba vtedy, ak sa hodnota rovná:"),
    ("projects.cf_wert_auswaehlen", "-- Wert auswählen --", "-- Select value --", "-- Vybrať hodnotu --"),
    ("projects.cf_mind_eine_person", "Mind. eine Person zugewiesen", "At least one person assigned", "Priradená aspoň jedna osoba"),
    ("projects.cf_keine_person", "Keine Person zugewiesen", "No person assigned", "Nepriradená žiadna osoba"),
    ("projects.cf_datum_gesetzt", "Datum ist gesetzt", "Date is set", "Dátum je nastavený"),
    ("projects.cf_kein_datum", "Kein Datum", "No date", "Žiadny dátum"),
    ("projects.cf_farbe_gesetzt", "Farbe gesetzt", "Color set", "Farba nastavená"),
    ("projects.cf_keine_farbe", "Keine Farbe", "No color", "Bez farby"),
    ("projects.cf_mind_ein_tag", "Mind. ein Tag gesetzt", "At least one tag set", "Nastavený aspoň jeden štítok"),
    ("projects.cf_keine_tags", "Keine Tags", "No tags", "Žiadne štítky"),
    ("projects.cf_regel_speichern", "Regel speichern", "Save rule", "Uložiť pravidlo"),
    ("projects.cf_feld_speichern", "Feld speichern", "Save field", "Uložiť pole"),

    # Settings Tab & Time Tracking Tab
    ("projects.settings_general_title", "Allgemeine Projekt-Einstellungen", "General project settings", "Všeobecné nastavenia projektu"),
    ("projects.settings_general_desc", "Passe den Projektnamen, den Status und projektweite Eigenschaften an.", "Customize project name, status and project-wide properties.", "Upravte názov projektu, stav a vlastnosti celého projektu."),
    ("projects.projekttitel", "Projekttitel", "Project title", "Názov projektu"),
    ("projects.projekt_status", "Projekt-Status", "Project status", "Stav projektu"),
    ("projects.sichtbarkeit_des_projekts", "Sichtbarkeit des Projekts", "Project visibility", "Viditeľnosť projektu"),
    ("projects.projekt_waehrung", "Projekt-Währung", "Project currency", "Mena projektu"),
    ("projects.budget_stunden", "Budget (Stunden)", "Budget (hours)", "Rozpočet (hodiny)"),
    ("projects.budget_betrag", "Budget (Betrag)", "Budget (amount)", "Rozpočet (suma)"),
    ("projects.benutzerdefinierte_felder_logik", "Benutzerdefinierte Felder & Logik", "Custom fields & Logic", "Vlastné polia a logika"),
    ("projects.sichtbarkeit_standard_felder", "Sichtbarkeit Standard-Felder", "Standard fields visibility", "Viditeľnosť štandardných polí"),
    ("projects.sichtbarkeit_standard_desc", "Hier kannst du Standard-Felder unter bestimmten Bedingungen ausblenden.", "Here you can hide standard fields under certain conditions.", "Tu môžete skryť štandardné polia za určitých podmienok."),
    ("projects.immer_sichtbar", "Immer sichtbar", "Always visible", "Vždy viditeľné"),
    ("projects.gesamtaufwand", "Gesamtaufwand", "Total effort", "Celková prácnosť"),
    ("projects.stunden_budget", "Stunden-Budget", "Hours budget", "Hodinový rozpočet"),
    ("projects.gesamtkosten", "Gesamtkosten", "Total cost", "Celkové náklady"),
    ("projects.kosten_budget", "Kosten-Budget", "Cost budget", "Rozpočet nákladov"),
    ("projects.alle_buchungen", "Alle Buchungen (Projekt & Aufgaben)", "All bookings (project & tasks)", "Všetky záznamy (projekt a úlohy)"),
    ("projects.nur_gesamtprojekt", "Nur Gesamtprojekt (ohne Aufgabe)", "Project only (without tasks)", "Iba celý projekt (bez úloh)"),
    ("projects.alle_mitarbeiter", "Alle Mitarbeiter", "All employees", "Všetci pracovníci"),
    ("projects.rapportiert_auf", "Rapportiert auf", "Reported on", "Vykázané na"),
    ("projects.taetigkeit_notiz", "Tätigkeit / Notiz", "Activity / Note", "Činnosť / poznámka"),
    ("projects.zeiteintrag_anpassen", "Zeiteintrag anpassen", "Edit time entry", "Upraviť časový záznam"),
    ("projects.zeit_erfassen", "Zeit erfassen", "Log time", "Zaznamenať čas"),

    # Team & Contacts Modals
    ("projects.teammitglied_einladen", "Teammitglied ins Projekt einladen", "Invite team member to project", "Pozvať člena tímu do projektu"),
    ("projects.email_des_nutzers", "E-Mail des Nutzers", "User's email", "E-mail používateľa"),
    ("projects.rolle_im_projekt", "Rolle im Projekt", "Role in project", "Rola v projekte"),
    ("projects.rolle_editor_desc", "Editor (Darf Aufgaben erstellen & bearbeiten)", "Editor (Can create & edit tasks)", "Editor (Môže vytvárať a upravovať úlohy)"),
    ("projects.rolle_viewer_desc", "Viewer (Nur Leserechte)", "Viewer (Read-only)", "Prehliadač (Iba na čítanie)"),
    ("projects.mitglied_hinzufuegen_btn", "Mitglied hinzufügen", "Add member", "Pridať člena"),
    ("projects.ki_autofill_assistent", "KI-Autofill Assistent", "AI Autofill Assistant", "Asistent automatického vypĺňania pomocou umelej inteligencie"),
    ("projects.ki_autofill_placeholder", "Beispiel: Hans Peter, Bauleiter bei Steiner Tiefbau AG in Zürich, Tel 044 123 45 67, Mobile 079 987 65 43, h.peter@steiner.ch", "Example: John Doe, Site Manager at Steiner AG, Phone +41 44 123 45 67, Mobile +41 79 987 65 43, j.doe@steiner.ch", "Príklad: Ján Novák, stavbyvedúci v Steiner AG, Tel +41 44 123 45 67, Mobil +41 79 987 65 43, j.novak@steiner.ch"),
    ("projects.texte_sicher_verarbeitet", "Texte werden sicher verarbeitet", "Texts are processed securely", "Texty sú spracované bezpečne"),
    ("projects.vorname", "Vorname", "First name", "Meno"),
    ("projects.nachname", "Nachname", "Last name", "Priezvisko"),
    ("projects.firma_unternehmen", "Firma / Unternehmen", "Company / Organization", "Firma / Spoločnosť"),
    ("projects.funktion_gewerk", "Funktion / Gewerk", "Role / Trade", "Funkcia / Remeslo"),
    ("projects.mobile_handy", "Mobile (Handy)", "Mobile phone", "Mobil"),
    ("projects.telefon_festnetz", "Telefon Festnetz", "Landline phone", "Pevná linka"),
    ("projects.geschaeftsadresse", "Geschäftsadresse", "Business address", "Firemná adresa"),
    ("projects.webseite", "Webseite", "Website", "Webová stránka"),
    ("projects.gruppe_kategorie", "Gruppe / Kategorie", "Group / Category", "Skupina / Kategória"),
    ("projects.kontakt_anlegen_btn", "Kontakt anlegen", "Create contact", "Vytvoriť kontakt"),
    ("projects.kontakt_speichern_btn", "Kontakt speichern", "Save contact", "Uložiť kontakt"),

    # Script Notifications & Toasts
    ("projects.fehler_aktion", "Fehler beim Ausführen der Aktion", "Error executing action", "Chyba pri vykonávaní akcie"),
    ("projects.export_enterprise_only", "Projekt-Export ist exklusiv für den Enterprise-Tarif verfügbar.", "Project export is exclusively available in the Enterprise plan.", "Export projektu je dostupný výhradne v tarife Enterprise."),
    ("projects.fehler_export", "Fehler beim Exportieren des Projekts", "Error exporting project", "Chyba pri exporte projektu"),
    ("projects.fehler_kontakt_merge", "Fehler beim Zusammenführen des Kontakts", "Error merging contact", "Chyba pri zlučovaní kontaktu"),
    ("projects.fehler_zusatzfeld_speichern", "Fehler beim Speichern des Zusatzfeldes", "Error saving custom field", "Chyba pri ukladaní vlastného poľa"),
    ("projects.zusatzfeld_geloescht", "Zusatzfeld erfolgreich gelöscht", "Custom field deleted successfully", "Vlastné pole bolo úspešne vymazané"),
    ("projects.journal_aktualisiert", "Journal-Eintrag erfolgreich aktualisiert", "Journal entry updated successfully", "Záznam v denníku bol úspešne aktualizovaný"),
    ("projects.fehler_task_link", "Fehler beim Aktualisieren der Aufgabenverknüpfung", "Error updating task link", "Chyba pri aktualizácii prepojenia úlohy"),
    ("projects.fehler_task_move", "Konnte Aufgabe nicht verschieben", "Could not move task", "Úlohu sa nepodarilo presunúť"),
    ("projects.zugriff_verweigert", "Zugriff verweigert oder Projekt nicht gefunden.", "Access denied or project not found.", "Prístup zamietnutý alebo projekt nebol nájdený."),
    ("projects.ki_daten_erkannt", "✓ Daten erfolgreich erkannt und ins Formular übertragen!", "✓ Data successfully recognized and transferred to form!", "✓ Údaje boli úspešne rozpoznané a prenesené do formulára!"),
    ("projects.ki_erkennung_fehler", "Fehler bei der KI-Erkennung.", "Error during AI recognition.", "Chyba pri rozpoznávaní umelou inteligenciou."),
    ("projects.bitte_nachname_eingeben", "Bitte mindestens einen Nachnamen eingeben.", "Please enter at least a last name.", "Zadajte aspoň priezvisko."),
    ("projects.duplikat_kontakt", "Duplikat erkannt: Ein ähnlicher Kontakt existiert bereits in diesem Projekt.", "Duplicate detected: A similar contact already exists in this project.", "Zistený duplikát: Podobný kontakt už v tomto projekte existuje."),
    ("projects.fehler_kontakt_speichern", "Fehler beim Speichern des Kontakts", "Error saving contact", "Chyba pri ukladaní kontaktu"),
    ("projects.kontakt_geloescht", "Kontakt erfolgreich gelöscht", "Contact deleted successfully", "Kontakt bol úspešne vymazaný"),
    ("projects.bitte_dauer_groesser_null", "Bitte eine Dauer grösser als 0 angeben.", "Please enter a duration greater than 0.", "Zadajte trvanie väčšie ako 0."),
    ("projects.fehler_zeit_erfassen", "Fehler beim Erfassen der Zeit", "Error logging time", "Chyba pri zaznamenávaní času"),
    ("projects.fehler_zeit_aktualisieren", "Fehler beim Aktualisieren der Zeit", "Error updating time", "Chyba pri aktualizácii času"),
    ("projects.zeiteintrag_geloescht", "Zeiteintrag gelöscht", "Time entry deleted", "Časový záznam bol vymazaný"),
    ("projects.settings_gespeichert", "Projekt-Einstellungen erfolgreich gespeichert!", "Project settings saved successfully!", "Nastavenia projektu boli úspešne uložené!"),
    ("projects.fehler_settings_speichern", "Fehler beim Speichern der Einstellungen", "Error saving settings", "Chyba pri ukladaní nastavení"),
    ("projects.fehler_projekt_loeschen", "Fehler beim Löschen des Projekts", "Error deleting project", "Chyba pri mazaní projektu"),
    ("projects.feld_geloescht", "Feld gelöscht", "Field deleted", "Pole bolo vymazané"),
    ("projects.fehler_abschnitt_erstellen", "Fehler beim Erstellen des Abschnitts", "Error creating section", "Chyba pri vytváraní sekcie"),
    ("projects.abschnitt_geloescht", "Abschnitt gelöscht", "Section deleted", "Sekcia bola vymazaná"),
    ("projects.fehler_abschnitte_speichern", "Fehler beim Speichern der Abschnitte", "Error saving sections", "Chyba pri ukladaní sekcií"),
    ("projects.projekt_abgeschlossen_toast", "Projekt erfolgreich als abgeschlossen markiert!", "Project successfully marked as completed!", "Projekt bol úspešne označený ako dokončený!"),
    ("projects.fehler_task_status", "Fehler beim Ändern des Aufgabenstatus", "Error changing task status", "Chyba pri zmene stavu úlohy"),
    ("projects.bitte_titel_eingeben", "Bitte gib mindestens einen Aufgabentitel ein.", "Please enter at least a task title.", "Zadajte aspoň názov úlohy."),
    ("projects.fehler_task_erstellen", "Fehler beim Erstellen der Aufgabe", "Error creating task", "Chyba pri vytváraní úlohy"),
    ("projects.aufgabe_erledigt_toast", "Aufgabe als erledigt markiert", "Task marked as completed", "Úloha bola označená ako dokončená"),
    ("projects.fehler_subtask_erstellen", "Fehler beim Erstellen der Unteraufgabe", "Error creating subtask", "Chyba pri vytváraní podúlohy"),
    ("projects.fehler_kommentar_senden", "Fehler beim Senden des Kommentars", "Error sending comment", "Chyba pri odosielaní komentára"),
    ("projects.keine_berechtigung_task_loeschen", "Als Betrachter hast du keine Berechtigung, Aufgaben zu löschen.", "As a viewer, you do not have permission to delete tasks.", "Ako prehliadač nemáte oprávnenie mazať úlohy."),
    ("projects.aufgabe_geloescht_toast", "Aufgabe erfolgreich gelöscht", "Task deleted successfully", "Úloha bola úspešne vymazaná"),
    ("projects.datei_entfernt_toast", "Datei erfolgreich entfernt", "File removed successfully", "Súbor bol úspešne odstránený"),
    ("projects.xlsx_not_available", "Excel-Bibliothek (XLSX) steht nicht zur Verfügung.", "Excel library (XLSX) is not available.", "Knižnica Excel (XLSX) nie je k dispozícii."),
    ("projects.file_is_empty", "Die ausgewählte Datei ist leer.", "The selected file is empty.", "Vybraný súbor je prázdny."),
    ("projects.fehler_file_parse", "Fehler beim Parsen der Datei:", "Error parsing file:", "Chyba pri spracovaní súboru:"),
    ("projects.pflichtfeld_titel_zuweisen", "Bitte weise mindestens einer Spalte das Pflichtfeld \"Aufgabentitel\" zu.", "Please assign the required field \"Task title\" to at least one column.", "Priraďte povinné pole „Názov úlohy“ aspoň jednému stĺpcu."),
    ("projects.ziel_abschnitt_waehlen", "Bitte wähle einen Ziel-Abschnitt für die Aufgaben aus.", "Please select a target section for the tasks.", "Vyberte cieľovú sekciu pre úlohy."),
    ("projects.fehler_import", "Fehler während des Imports:", "Error during import:", "Chyba počas importu:"),
    ("projects.fehler_task_speichern", "Fehler beim Speichern der Aufgabe", "Error saving task", "Chyba pri ukladaní úlohy"),
    ("projects.bitte_journal_titel", "Bitte gib einen Titel für den Journaleintrag an.", "Please enter a title for the journal entry.", "Zadajte názov záznamu v denníku."),
    ("projects.fehler_journal_speichern", "Fehler beim Speichern des Journaleintrags", "Error saving journal entry", "Chyba pri ukladaní záznamu v denníku."),
    ("projects.bitte_notiz_inhalt", "Bitte gib einen Inhalt für die Notiz an.", "Please enter content for the note.", "Zadajte obsah poznámky."),
    ("projects.fehler_notiz_speichern", "Fehler beim Speichern der Notiz", "Error saving note", "Chyba pri ukladaní poznámky."),
    ("projects.journal_geloescht_toast", "Journaleintrag erfolgreich gelöscht", "Journal entry deleted successfully", "Záznam v denníku bol úspešne vymazaný"),
    ("projects.fehler_ki_analyse", "Fehler bei der KI-Analyse", "Error during AI analysis", "Chyba pri analýze umelou inteligenciou."),
    ("projects.kein_abschnitt_vorhanden", "Kein Abschnitt im Projekt vorhanden, um eine Aufgabe anzulegen.", "No section available in project to create a task.", "V projekte nie je k dispozícii žiadna sekcia na vytvorenie úlohy."),
    ("projects.mitglied_hinzugefuegt_toast", "Mitglied erfolgreich hinzugefügt!", "Member added successfully!", "Člen bol úspešne pridaný!"),
    ("projects.fehler_mitglied_einladen", "Fehler beim Einladen des Mitglieds", "Error inviting member", "Chyba pri pozývaní člena"),
]

def load_json(p):
    with open(p, 'r', encoding='utf-8') as f:
        return json.load(f)

def save_json(p, d):
    with open(p, 'w', encoding='utf-8') as f:
        json.dump(d, f, ensure_ascii=False, indent=2)

def main():
    print("=== Taskster i18n: Projects & Tasks Full Migration ===")
    de = load_json(LOCALES_DIR / "de.json")
    en = load_json(LOCALES_DIR / "en.json")
    sk = load_json(LOCALES_DIR / "sk.json")

    added_de = 0
    added_en = 0
    added_sk = 0

    for key, de_val, en_val, sk_val in TRANSLATIONS:
        if key not in de:
            de[key] = de_val
            added_de += 1
        if key not in en:
            en[key] = en_val
            added_en += 1
        if key not in sk:
            sk[key] = sk_val
            added_sk += 1

    save_json(LOCALES_DIR / "de.json", de)
    save_json(LOCALES_DIR / "en.json", en)
    save_json(LOCALES_DIR / "sk.json", sk)

    print(f"[i18n] Keys added: DE +{added_de}, EN +{added_en}, SK +{added_sk}")
    print(f"[i18n] Total keys in locales: DE={len(de)}, EN={len(en)}, SK={len(sk)}")

if __name__ == '__main__':
    main()

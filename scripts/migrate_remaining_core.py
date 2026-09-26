#!/usr/bin/env python3
"""
Taskster i18n – Migrate Remaining Core Pages and Components
Localizes:
- app.vue
- components/JournalEntryModal.vue
- components/JournalNoteModal.vue
- components/VoiceRecorderModal.vue
- components/CalendarEventModal.vue
- components/MiniCalendar.vue
- components/CommandPalette.vue
- pages/calendar/index.vue
- pages/company/index.vue
- pages/contacts/index.vue
- pages/dashboard.vue
- pages/forgot-password.vue
- pages/index.vue
- pages/journal.vue
- pages/login.vue
- pages/projects/[id].vue
- pages/reset-password.vue
- pages/settings.vue
"""
import json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCALES = ROOT / "i18n" / "locales"

TRANSLATIONS = [
    # app.vue
    ("app.wallpaper_alt", "Taskster Wallpaper", "Taskster Wallpaper", "Tapeta Taskster"),
    ("app.menu", "Menü", "Menu", "Menu"),
    ("app.einladungen", "Einladungen", "Invitations", "Pozvánky"),
    ("app.webseite_cms", "Webseite & CMS", "Website & CMS", "Webová stránka & CMS"),
    ("app.ai_plaene", "KI & AI-Pläne", "AI & AI Plans", "AI a AI plány"),
    ("app.security_audit", "Security & Audit Logs", "Security & Audit Logs", "Bezpečnosť a protokoly auditu"),
    ("app.audit_logs", "Audit Logs", "Audit Logs", "Protokoly auditu"),

    # JournalEntryModal.vue
    ("journal.email_geladen", "Geladen:", "Loaded:", "Načítané:"),
    ("journal.projekt_erkannt_label", "Projekt erkannt:", "Project detected:", "Projekt rozpoznaný:"),
    ("journal.projekt_auto_desc", "Das System ordnet den Eintrag automatisch dem passenden Projekt zu (z.B. nach Kundennummer, Adresse oder Namen).", "The system automatically assigns the entry to the matching project (e.g. by customer number, address or name).", "Systém automaticky priradí záznam k zodpovedajúcemu projektu (napr. podľa čísla zákazníka, adresy alebo mena)."),
    ("journal.titel_betreff_label", "Titel / Betreff", "Title / Subject", "Názov / Predmet"),
    ("journal.oeffentlich_leser", "🌐 Öffentlich (Projektleser)", "🌐 Public (Project readers)", "🌐 Verejné (Čitatelia projektu)"),
    ("journal.nur_gruppe", "👥 Nur ausgewählte Gruppe", "👥 Selected group only", "👥 Iba vybraná skupina"),
    ("journal.berechtigte_gruppe", "Berechtigte Gruppe", "Authorized group", "Oprávnená skupina"),
    ("journal.gruppe_waehlen_opt", "-- Gruppe auswählen --", "-- Select group --", "-- Vybrať skupinu --"),
    ("journal.verknuepfte_aufgabe_opt", "Verknüpfte Aufgabe (optional)", "Linked task (optional)", "Prepojená úloha (voliteľné)"),
    ("journal.keine_verknuepfung_opt", "-- Keine Verknüpfung --", "-- No link --", "-- Žiadne prepojenie --"),
    ("journal.aufgabe_suchen_placeholder", "Aufgabe suchen...", "Search task...", "Hľadať úlohu..."),
    ("journal.keine_verknuepfung_label", "Keine Verknüpfung", "No link", "Žiadne prepojenie"),
    ("journal.auto_zuweisen_text", "Automatisch zuweisen (anhand Text)", "Assign automatically (from text)", "Priradiť automaticky (podľa textu)"),
    ("journal.keine_aufgaben_gefunden", "Keine Aufgaben gefunden", "No tasks found", "Nenašli sa žiadne úlohy"),
    ("journal.teilnehmer_anwesenheit", "Teilnehmer & Anwesenheit", "Participants & Attendance", "Účastníci a dochádzka"),
    ("journal.teilnehmer_desc", "Wähle bestehende Projektkontakte aus oder füge neue Teilnehmer hinzu.", "Select existing project contacts or add new participants.", "Vyberte existujúce kontakty projektu alebo pridajte nových účastníkov."),
    ("journal.kontakt_waehlen_opt", "Kontakt auswählen...", "Select contact...", "Vybrať kontakt..."),
    ("journal.protokolltext_inhalt", "Protokolltext / Inhalt", "Minutes text / Content", "Text zápisnice / Obsah"),
    ("journal.protokoll_placeholder", "Besprochene Punkte, Beschlüsse, Sachverhalt oder Notizen...", "Discussed points, decisions, facts or notes...", "Prerokované body, rozhodnutia, skutočnosti alebo poznámky..."),
    ("journal.dateianhaenge_label", "Dateianhänge (Fotos, Pläne, PDFs)", "File attachments (Photos, plans, PDFs)", "Prílohy súborov (Fotografie, plány, PDF)"),
    ("journal.dateien_ablegen_info", "Dateien hier ablegen oder zum Auswählen klicken (max. 10 MB)", "Drop files here or click to select (max. 10 MB)", "Sem presuňte súbory alebo kliknite pre výber (max. 10 MB)"),
    ("journal.betreff_placeholder", "z. B. 14. Bausitzung Los 3 oder Bauabnahme Keller...", "e.g. 14th site meeting lot 3 or basement acceptance...", "napr. 14. kontrolný deň objekt 3 alebo prevzatie suterénu..."),
    ("journal.name_placeholder", "Name", "Name", "Meno"),

    # JournalNoteModal.vue
    ("journal_note.titel", "Neuen Journaleintrag erfassen", "Create new journal entry", "Zaznamenať nový žurnálový záznam"),
    ("journal_note.desc", "Schneller Journaleintrag mit automatischer Projekt- und Aufgabenzuweisung.", "Quick journal entry with automatic project and task assignment.", "Rýchly žurnálový záznam s automatickým priradením k projektu a úlohe."),
    ("journal_note.inhalt_label", "Journaleintrag / Inhalt", "Journal entry / Content", "Žurnálový záznam / Obsah"),
    ("journal_note.placeholder", "Schreibe deinen Journaleintrag, Feststellung, Mangel oder Notiz hier rein...", "Write your journal entry, observation, defect or note here...", "Sem napíšte svoj žurnálový záznam, zistenie, nedostatok alebo poznámku..."),
    ("journal_note.projekt_suchen_placeholder", "Projekt suchen (z.B. Balmstr, Name)...", "Search project (e.g. Main street, Name)...", "Hľadať projekt (napr. Hlavná, Názov)..."),
    ("journal_note.nur_ordner_journal", "Nur Ordner-Journal (Kein Projekt)", "Folder journal only (No project)", "Iba priečinkový žurnál (Žiadny projekt)"),
    ("journal_note.passende_aufgabe", "Passende Aufgabe erkannt (Klick zum Zuweisen):", "Matching task detected (Click to assign):", "Rozpoznaná zodpovedajúca úloha (Kliknite pre priradenie):"),
    ("journal_note.speichern_btn", "Eintrag speichern", "Save entry", "Uložiť záznam"),

    # VoiceRecorderModal.vue
    ("voice.sprachassistent_notiz", "Sprachassistent & Notiz", "Voice Assistant & Note", "Hlasový asistent a poznámka"),
    ("voice.aufnahme_starten_title", "Sprachaufnahme starten", "Start voice recording", "Spustiť hlasový záznam"),
    ("voice.aufnahme_desc", "Sprich deine Aufgabe, Notiz oder Statusmeldung ein. Die KI erkennt Aufgabenname, Nummer oder Adresse.", "Speak your task, note or status update. AI recognizes task name, number or address.", "Nahrajte svoju úlohu, poznámku alebo správu o stave. AI rozpozná názov úlohy, číslo alebo adresu."),
    ("voice.spracheingabe_label", "Spracheingabe:", "Voice input:", "Hlasový vstup:"),
    ("voice.keine_audiodaten", "Keine Audio-Daten aufgenommen.", "No audio data recorded.", "Neboli nahrané žiadne zvukové údaje."),
    ("voice.keine_sprache", "Keine Sprache erkannt. Bitte lauter und deutlicher ins Mikrofon sprechen.", "No speech recognized. Please speak louder and clearer into the microphone.", "Nebola rozpoznaná žiadna reč. Hovorte prosím hlasnejšie a zreteľnejšie do mikrofónu."),
    ("voice.mikrofon_verweigert", "Mikrofonzugriff verweigert oder nicht unterstützt. Bitte erteile Mikrofon-Berechtigung im Browser.", "Microphone access denied or unsupported. Please grant microphone permission in your browser.", "Prístup k mikrofónu bol zamietnutý alebo nie je podporovaný. Povoľte prístup k mikrofónu v prehliadači."),
    ("voice.praefix_assistent", "Aus Sprachassistent (openai/whisper-large-v3-turbo):\n\n", "From voice assistant (openai/whisper-large-v3-turbo):\n\n", "Z hlasového asistenta (openai/whisper-large-v3-turbo):\n\n"),

    # CalendarEventModal & calendar/index
    ("calendar.dein_status_prefix", "· Dein Status:", "· Your status:", "· Tvoj stav:"),
    ("calendar.ics_fehler", "ICS-Datei konnte nicht geladen werden.", "ICS file could not be loaded.", "Súbor ICS sa nepodarilo načítať."),
    ("calendar.weiter_title", "Weiter", "Next", "Ďalej"),
    ("calendar.monat_view", "Monat", "Month", "Mesiac"),
    ("calendar.woche_view", "Woche", "Week", "Týždeň"),
    ("calendar.tag_view", "Tag", "Day", "Deň"),

    # CommandPalette.vue
    ("command.dashboard", "Dashboard", "Dashboard", "Prehľad"),
    ("command.zeitrapporte", "Zeitrapporte", "Time reports", "Časové výkazy"),
    ("command.neuer_ordner", "Neuer Projektordner", "New project folder", "Nový priečinok projektu"),
    ("command.einstellungen", "Einstellungen", "Settings", "Nastavenia"),

    # MiniCalendar.vue
    ("mini_cal.kalender_oeffnen", "Kalender öffnen", "Open calendar", "Otvoriť kalendár"),
    ("mini_cal.auswahl_aufheben", "Auswahl aufheben", "Clear selection", "Zrušiť výber"),

    # pages/company/index.vue
    ("company.zugriff_verweigert", "Zugriff verweigert", "Access denied", "Prístup zamietnutý"),
    ("company.nur_firmen_admins", "Dieser Bereich ist ausschließlich Firmen-Administratoren des eigenen Unternehmens vorbehalten.", "This area is reserved exclusively for company administrators of your company.", "Táto oblasť je vyhradená výhradne pre správcov vašej spoločnosti."),
    ("company.portal_subtitle", "Mitarbeiter, Firmenvorlagen, Plan & Lizenzen, Richtlinien und Support – alles an einem Ort.", "Employees, company templates, plan & licenses, policies and support – all in one place.", "Zamestnanci, firemné šablóny, plán a licencie, pravidlá a podpora – všetko na jednom mieste."),
    ("company.mitarbeiter_einladen", "Mitarbeiter einladen", "Invite employee", "Pozvať zamestnanca"),
    ("company.mit_vollzugriff", "mit Vollzugriff", "with full access", "s plným prístupom"),
    ("company.nur_fuer_diese_firma", "nur für diese Firma", "for this company only", "iba pre túto spoločnosť"),
    ("company.lizenzen_gesamtkosten", "Lizenzen & Monatliche Gesamtkosten", "Licenses & Monthly Total Costs", "Licencie a celkové mesačné náklady"),
    ("company.uebersicht_lizenzen", "Übersicht der aktiven Arbeitsplatzlizenzen deines Unternehmens.", "Overview of active workspace licenses for your company.", "Prehľad aktívnych licencií pracovných miest vašej spoločnosti."),
    ("company.monatlicher_gesamtbetrag", "Monatlicher Gesamtbetrag", "Monthly total amount", "Celková mesačná suma"),
    ("company.pro_monat", "/ Monat", "/ Month", "/ Mesiac"),
    ("company.pro_monat_pro_sitz", "19 € / Monat pro Sitz", "19 € / month per seat", "19 € / mesiac na používateľa"),
    ("company.mitarbeiter_pro", "Mitarbeiter Pro", "Employee Pro", "Zamestnanec Pro"),
    ("company.pro_preis", "8 € / Monat pro Sitz", "8 € / month per seat", "8 € / mesiac na používateľa"),
    ("company.mitarbeiter_enterprise", "Mitarbeiter Enterprise", "Employee Enterprise", "Zamestnanec Enterprise"),
    ("company.enterprise_preis", "15 € / Monat pro Sitz", "15 € / month per seat", "15 € / mesiac na používateľa"),
    ("company.mitarbeiter_co_admins", "Mitarbeiter & Co-Administratoren", "Employees & Co-Administrators", "Zamestnanci a spolusprávcovia"),
    ("company.co_admins_info", "Co-Admins dürfen dieses Firmen-Portal ebenfalls verwalten.", "Co-admins may also manage this company portal.", "Spolusprávcovia môžu tiež spravovať tento firemný portál."),
    ("company.name_email", "Name & E-Mail", "Name & Email", "Meno a e-mail"),
    ("company.rolle_lizenz", "Rolle & Lizenz", "Role & License", "Rola a licencia"),
    ("company.enterprise_19", "Enterprise (19 € / Mt.)", "Enterprise (19 € / mo.)", "Enterprise (19 € / mes.)"),
    ("company.enterprise_15", "Enterprise (15 € / Mt.)", "Enterprise (15 € / mo.)", "Enterprise (15 € / mes.)"),
    ("company.pro_8", "Pro (8 € / Mt.)", "Pro (8 € / mo.)", "Pro (8 € / mes.)"),
    ("company.zum_co_admin", "Zum Co-Admin ernennen", "Promote to Co-Admin", "Vymenovať za spolusprávcu"),
    ("company.zum_co_admin_btn", "Zum Co-Admin", "To Co-Admin", "Na spolusprávcu"),
    ("company.noch_keine_mitarbeiter", "Noch keine Mitarbeiter im Unternehmen.", "No employees in company yet.", "V spoločnosti zatiaľ nie sú žiadni zamestnanci."),
    ("company.offene_einladungen", "Offene Einladungen", "Open invitations", "Otvorené pozvánky"),
    ("company.einladungen_info", "Diese Personen wurden eingeladen und haben sich noch nicht registriert.", "These people have been invited and have not registered yet.", "Tieto osoby boli pozvané a zatiaľ sa nezaregistrovali."),
    ("company.link_kopieren", "Link kopieren", "Copy link", "Kopírovať odkaz"),
    ("company.zugriffsmatrix_titel", "Zugriffsmatrix & Berechtigungsübersicht", "Access Matrix & Permission Overview", "Matica prístupov a prehľad oprávnení"),
    ("company.zugriffsmatrix_desc", "Wer wurde wo eingeladen, wer hat Zugriff auf welche Ordner und Projekte.", "Who was invited where, who has access to which folders and projects.", "Kto bol kde pozvaný, kto má prístup ku ktorým priečinkom a projektom."),
    ("company.filter_placeholder", "Nach Name oder E-Mail filtern...", "Filter by name or email...", "Filtrovať podľa mena alebo e-mailu..."),
    ("company.zugriff_ordner_projekte", "Zugriff (Ordner & Projekte)", "Access (Folders & Projects)", "Prístup (Priečinky a projekty)"),
    ("company.gruppe_loeschen_confirm", "Möchtest du diese Gruppe wirklich löschen?", "Do you really want to delete this group?", "Naozaj chcete odstrániť túto skupinu?"),
    ("company.gruppe_geloescht", "Gruppe gelöscht.", "Group deleted.", "Skupina odstránená."),
    ("company.gruppe_loeschen_fehler", "Fehler beim Löschen der Gruppe", "Error deleting group", "Chyba pri odstraňovaní skupiny"),
    ("company.rolle_aendern_fehler", "Fehler beim Ändern der Rolle", "Error changing role", "Chyba pri zmene roly"),
    ("company.vorlage_loeschen_confirm", "wirklich löschen?", "really delete?", "naozaj odstrániť?"),
    ("company.vorlage_geloescht", "Firmenvorlage gelöscht.", "Company template deleted.", "Firemná šablóna odstránená."),
    ("company.vorlage_loeschen_fehler", "Fehler beim Löschen der Vorlage", "Error deleting template", "Chyba pri odstraňovaní šablóny"),
    ("company.upgrade_angefragt", "Upgrade-Anfrage wurde an das Taskster-Team übermittelt.", "Upgrade request submitted to Taskster team.", "Žiadosť o upgrade bola odoslaná tímu Taskster."),

    # contacts/index.vue remaining
    ("contacts.kartenansicht_title", "Kartenansicht", "Map view", "Zobrazenie mapy"),
    ("contacts.tabellenansicht_title", "Tabellenansicht", "Table view", "Tabuľkové zobrazenie"),
    ("contacts.loeschen_modal_title", "Kontakt löschen", "Delete contact", "Odstrániť kontakt"),
    ("contacts.loeschen_modal_confirm", "Möchtest du den Kontakt wirklich unwiderruflich löschen?", "Do you really want to permanently delete this contact?", "Naozaj chcete natrvalo odstrániť tento kontakt?"),

    # journal.vue remaining
    ("journal.original_ansehen_title", "Vollständiges Original-Dokument / E-Mail ansehen", "View complete original document / email", "Zobraziť kompletný pôvodný dokument / e-mail"),
    ("journal.aufgabe_im_projekt_oeffnen", "Aufgabe im Projekt öffnen", "Open task in project", "Otvoriť úlohu v projekte"),

    # login.vue remaining
    ("login.name_placeholder", "Max Mustermann", "John Doe", "Ján Novák"),
    ("login.pw_min_6", "Mindestens 6 Zeichen", "At least 6 characters", "Minimálne 6 znakov"),
    ("login.zero_trust_claim", "Zero-Trust Architektur & Schweizer Präzision", "Zero-Trust Architecture & Swiss Precision", "Zero-Trust architektúra a švajčiarska precíznosť"),
    ("login.einladung_zu", "Einladung zu", "Invitation to", "Pozvánka do"),
    ("login.einladung_desc", "Du wurdest eingeladen, diesem Unternehmen beizutreten.", "You were invited to join this company.", "Boli ste pozvaní pripojiť sa k tejto spoločnosti."),
    ("login.vollstaendiger_name", "Vollständiger Name", "Full name", "Celé meno"),

    # reset-password.vue
    ("reset.kein_token", "Kein Sicherheitstoken angegeben.", "No security token provided.", "Nebol poskytnutý žiadny bezpečnostný token."),
    ("reset.pw_min_8", "Das Passwort muss mindestens 8 Zeichen lang sein.", "Password must be at least 8 characters long.", "Heslo musí mať aspoň 8 znakov."),

    # index.vue marketing
    ("index.modern_work", "Taskster Modern Work Management", "Taskster Modern Work Management", "Taskster Modern Work Management"),
    ("index.mehrstufige_struktur_titel", "Mehrstufige Struktur", "Multi-level Structure", "Viacúrovňová štruktúra"),
    ("index.praezise_rollen_titel", "Präzise Rollen & Rechte", "Precise Roles & Permissions", "Presné roly a oprávnenia"),
    ("index.flexible_team_titel", "Flexible Team-Verwaltung", "Flexible Team Management", "Flexibilná správa tímu"),

    # settings.vue
    ("settings.einladung_widerrufen_confirm", "Einladung wirklich widerrufen?", "Really revoke invitation?", "Naozaj odvolať pozvánku?"),

    # projects/[id].vue
    ("projects.zusatzfeld_loeschen_title", "Zusatzfeld löschen", "Delete custom field", "Odstrániť vlastné pole"),
    ("projects.zusatzfeld_loeschen_confirm", "Möchtest du das Zusatzfeld wirklich löschen?", "Do you really want to delete this custom field?", "Naozaj chcete odstrániť toto vlastné pole?"),
    ("projects.feld_loeschen_btn", "Feld löschen", "Delete field", "Odstrániť pole"),
    ("projects.feld_geloescht", "Zusatzfeld erfolgreich gelöscht", "Custom field successfully deleted", "Vlastné pole úspešne odstránené"),
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

def update_file(rel_path, replacements):
    f_path = ROOT / rel_path
    if not f_path.exists():
        print(f"[SKIP] {rel_path} does not exist")
        return
    content = f_path.read_text(encoding='utf-8')
    orig = content
    for old, new in replacements:
        content = content.replace(old, new)
    if content != orig:
        f_path.write_text(content, encoding='utf-8')
        print(f"[OK]   {rel_path} updated")
    else:
        print(f"[NOOP] {rel_path} no match")

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    print("=== Migrating Remaining Core Pages & Components ===")
    update_locales()

    # app.vue
    update_file("app.vue", [
        ('title="Einladungen"', ':title="$t(\'app.einladungen\')"'),
        ('title="Webseiten-Verwaltung"', ':title="$t(\'app.webseite_cms\')"'),
        ('title="KI & AI-Pläne"', ':title="$t(\'app.ai_plaene\')"'),
        ('title="Security & Audit Logs"', ':title="$t(\'app.security_audit\')"'),
        ('alt="Taskster Wallpaper"', ':alt="$t(\'app.wallpaper_alt\')"'),
        ('>Menü</span>', '>{{ $t(\'app.menu\') }}</span>'),
        ('>Webseite & CMS</span>', '>{{ $t(\'app.webseite_cms\') }}</span>'),
        ('>KI & AI-Pläne</span>', '>{{ $t(\'app.ai_plaene\') }}</span>'),
        ('>Audit Logs</span>', '>{{ $t(\'app.audit_logs\') }}</span>'),
    ])

    # JournalEntryModal.vue
    update_file("components/JournalEntryModal.vue", [
        ('placeholder="z. B. 14. Bausitzung Los 3 oder Bauabnahme Keller..."', ':placeholder="$t(\'journal.betreff_placeholder\')"'),
        ('placeholder="Aufgabe suchen..."', ':placeholder="$t(\'journal.aufgabe_suchen_placeholder\')"'),
        ('placeholder="Name"', ':placeholder="$t(\'journal.name_placeholder\')"'),
        ('placeholder="Besprochene Punkte, Beschlüsse, Sachverhalt oder Notizen..."', ':placeholder="$t(\'journal.protokoll_placeholder\')"'),
        ('Geladen: {{ loadedEmailFileName }}', '{{ $t(\'journal.email_geladen\') }} {{ loadedEmailFileName }}'),
        ('Projekt erkannt: {{ autoMatchedProjectInfo.title }}', '{{ $t(\'journal.projekt_erkannt_label\') }} {{ autoMatchedProjectInfo.title }}'),
        ('Das System ordnet den Eintrag automatisch dem passenden Projekt zu (z.B. nach Kundennummer, Adresse oder Namen).', '{{ $t(\'journal.projekt_auto_desc\') }}'),
        ('>Titel / Betreff<', '>{{ $t(\'journal.titel_betreff_label\') }}<'),
        ('>🌐 Öffentlich (Projektleser)<', '>{{ $t(\'journal.oeffentlich_leser\') }}<'),
        ('>👥 Nur ausgewählte Gruppe<', '>{{ $t(\'journal.nur_gruppe\') }}<'),
        ('>Berechtigte Gruppe<', '>{{ $t(\'journal.berechtigte_gruppe\') }}<'),
        ('>-- Gruppe auswählen --<', '>{{ $t(\'journal.gruppe_waehlen_opt\') }}<'),
        ('>Verknüpfte Aufgabe (optional)<', '>{{ $t(\'journal.verknuepfte_aufgabe_opt\') }}<'),
        ('>-- Keine Verknüpfung --<', '>{{ $t(\'journal.keine_verknuepfung_opt\') }}<'),
        ('>Keine Verknüpfung<', '>{{ $t(\'journal.keine_verknuepfung_label\') }}<'),
        ('>Automatisch zuweisen (anhand Text)<', '>{{ $t(\'journal.auto_zuweisen_text\') }}<'),
        ('>Keine Aufgaben gefunden<', '>{{ $t(\'journal.keine_aufgaben_gefunden\') }}<'),
        ('>Teilnehmer & Anwesenheit<', '>{{ $t(\'journal.teilnehmer_anwesenheit\') }}<'),
        ('Wähle bestehende Projektkontakte aus oder füge neue Teilnehmer hinzu.', '{{ $t(\'journal.teilnehmer_desc\') }}'),
        ('>Protokolltext / Inhalt<', '>{{ $t(\'journal.protokolltext_inhalt\') }}<'),
        ('>Dateianhänge (Fotos, Pläne, PDFs)<', '>{{ $t(\'journal.dateianhaenge_label\') }}<'),
        ('Dateien hier ablegen oder zum Auswählen klicken (max. 10 MB)', '{{ $t(\'journal.dateien_ablegen_info\') }}'),
    ])

    # JournalNoteModal.vue
    update_file("components/JournalNoteModal.vue", [
        ('placeholder="Schreibe deinen Journaleintrag, Feststellung, Mangel oder Notiz hier rein..."', ':placeholder="$t(\'journal_note.placeholder\')"'),
        ('placeholder="Projekt suchen (z.B. Balmstr, Name)..."', ':placeholder="$t(\'journal_note.projekt_suchen_placeholder\')"'),
        ('>Neuen Journaleintrag erfassen<', '>{{ $t(\'journal_note.titel\') }}<'),
        ('Schneller Journaleintrag mit automatischer Projekt- und Aufgabenzuweisung.', '{{ $t(\'journal_note.desc\') }}'),
        ('>Journaleintrag / Inhalt<', '>{{ $t(\'journal_note.inhalt_label\') }}<'),
        ('>Automatisch zuweisen (anhand Text)<', '>{{ $t(\'journal.auto_zuweisen_text\') }}<'),
        ('>Nur Ordner-Journal (Kein Projekt)<', '>{{ $t(\'journal_note.nur_ordner_journal\') }}<'),
        ('>-- Keine Verknüpfung --<', '>{{ $t(\'journal.keine_verknuepfung_opt\') }}<'),
        ('>Keine Verknüpfung<', '>{{ $t(\'journal.keine_verknuepfung_label\') }}<'),
        ('>Passende Aufgabe erkannt (Klick zum Zuweisen):<', '>{{ $t(\'journal_note.passende_aufgabe\') }}<'),
        ('>Eintrag speichern<', '>{{ $t(\'journal_note.speichern_btn\') }}<'),
    ])

    # VoiceRecorderModal.vue
    update_file("components/VoiceRecorderModal.vue", [
        ('>Sprachassistent & Notiz<', '>{{ $t(\'voice.sprachassistent_notiz\') }}<'),
        ('>Sprachaufnahme starten<', '>{{ $t(\'voice.aufnahme_starten_title\') }}<'),
        ('Sprich deine Aufgabe, Notiz oder Statusmeldung ein. Die KI erkennt Aufgabenname, Nummer oder Adresse.', '{{ $t(\'voice.aufnahme_desc\') }}'),
        ('>Spracheingabe:<', '>{{ $t(\'voice.spracheingabe_label\') }}<'),
        ("'Keine Audio-Daten aufgenommen.'", "t('voice.keine_audiodaten')"),
        ("'Keine Sprache erkannt. Bitte lauter und deutlicher ins Mikrofon sprechen.'", "t('voice.keine_sprache')"),
        ("'Mikrofonzugriff verweigert oder nicht unterstützt. Bitte erteile Mikrofon-Berechtigung im Browser.'", "t('voice.mikrofon_verweigert')"),
        ("'Aus Sprachassistent (openai/whisper-large-v3-turbo):\\n\\n'", "t('voice.praefix_assistent')"),
    ])

    # CalendarEventModal.vue
    update_file("components/CalendarEventModal.vue", [
        ('· Dein Status:', '· {{ $t(\'calendar.dein_status\') }}'),
        ("'ICS-Datei konnte nicht geladen werden.'", "t('calendar.ics_fehler')"),
    ])

    # MiniCalendar.vue
    update_file("components/MiniCalendar.vue", [
        ('title="Kalender öffnen"', ':title="$t(\'mini_cal.kalender_oeffnen\')"'),
        ('title="Auswahl aufheben"', ':title="$t(\'mini_cal.auswahl_aufheben\')"'),
    ])

    # CommandPalette.vue
    update_file("components/CommandPalette.vue", [
        ("title: 'Dashboard'", "title: t('command.dashboard')"),
        ("title: 'Zeitrapporte'", "title: t('command.zeitrapporte')"),
        ("title: 'Neuer Projektordner'", "title: t('command.neuer_ordner')"),
        ("title: 'Einstellungen'", "title: t('command.einstellungen')"),
    ])

    # pages/calendar/index.vue
    update_file("pages/calendar/index.vue", [
        ('title="Weiter"', ':title="$t(\'calendar.weiter_title\')"'),
        ("{ id: 'month', label: 'Monat'", "{ id: 'month', label: t('calendar.monat_view')"),
        ("{ id: 'week', label: 'Woche'", "{ id: 'week', label: t('calendar.woche_view')"),
        ("{ id: 'day', label: 'Tag'", "{ id: 'day', label: t('calendar.tag_view')"),
    ])

    # pages/company/index.vue
    update_file("pages/company/index.vue", [
        ('placeholder="Nach Name oder E-Mail filtern..."', ':placeholder="$t(\'company.filter_placeholder\')"'),
        ('title="Zum Co-Admin ernennen"', ':title="$t(\'company.zum_co_admin\')"'),
        ('>Zugriff verweigert<', '>{{ $t(\'company.zugriff_verweigert\') }}<'),
        ('Dieser Bereich ist ausschließlich Firmen-Administratoren des eigenen Unternehmens vorbehalten.', '{{ $t(\'company.nur_firmen_admins\') }}'),
        ('Mitarbeiter, Firmenvorlagen, Plan &amp; Lizenzen, Richtlinien und Support – alles an einem Ort.', '{{ $t(\'company.portal_subtitle\') }}'),
        ('Mitarbeiter, Firmenvorlagen, Plan & Lizenzen, Richtlinien und Support – alles an einem Ort.', '{{ $t(\'company.portal_subtitle\') }}'),
        ('>Mitarbeiter einladen<', '>{{ $t(\'company.mitarbeiter_einladen\') }}<'),
        ('>mit Vollzugriff<', '>{{ $t(\'company.mit_vollzugriff\') }}<'),
        ('>nur für diese Firma<', '>{{ $t(\'company.nur_fuer_diese_firma\') }}<'),
        ('>Lizenzen &amp; Monatliche Gesamtkosten<', '>{{ $t(\'company.lizenzen_gesamtkosten\') }}<'),
        ('>Lizenzen & Monatliche Gesamtkosten<', '>{{ $t(\'company.lizenzen_gesamtkosten\') }}<'),
        ('Übersicht der aktiven Arbeitsplatzlizenzen deines Unternehmens.', '{{ $t(\'company.uebersicht_lizenzen\') }}'),
        ('>Monatlicher Gesamtbetrag<', '>{{ $t(\'company.monatlicher_gesamtbetrag\') }}<'),
        ('> / Monat<', '> {{ $t(\'company.pro_monat\') }}<'),
        ('>19 € / Monat pro Sitz<', '>{{ $t(\'company.pro_monat_pro_sitz\') }}<'),
        ('>Mitarbeiter Pro<', '>{{ $t(\'company.mitarbeiter_pro\') }}<'),
        ('>8 € / Monat pro Sitz<', '>{{ $t(\'company.pro_preis\') }}<'),
        ('>Mitarbeiter Enterprise<', '>{{ $t(\'company.mitarbeiter_enterprise\') }}<'),
        ('>15 € / Monat pro Sitz<', '>{{ $t(\'company.enterprise_preis\') }}<'),
        ('>Mitarbeiter &amp; Co-Administratoren<', '>{{ $t(\'company.mitarbeiter_co_admins\') }}<'),
        ('>Mitarbeiter & Co-Administratoren<', '>{{ $t(\'company.mitarbeiter_co_admins\') }}<'),
        ('Co-Admins dürfen dieses Firmen-Portal ebenfalls verwalten.', '{{ $t(\'company.co_admins_info\') }}'),
        ('>Name &amp; E-Mail<', '>{{ $t(\'company.name_email\') }}<'),
        ('>Name & E-Mail<', '>{{ $t(\'company.name_email\') }}<'),
        ('>Rolle &amp; Lizenz<', '>{{ $t(\'company.rolle_lizenz\') }}<'),
        ('>Rolle & Lizenz<', '>{{ $t(\'company.rolle_lizenz\') }}<'),
        ('>Enterprise (19 € / Mt.)<', '>{{ $t(\'company.enterprise_19\') }}<'),
        ('>Enterprise (15 € / Mt.)<', '>{{ $t(\'company.enterprise_15\') }}<'),
        ('>Pro (8 € / Mt.)<', '>{{ $t(\'company.pro_8\') }}<'),
        ('>Zum Co-Admin<', '>{{ $t(\'company.zum_co_admin_btn\') }}<'),
        ('Noch keine Mitarbeiter im Unternehmen.', '{{ $t(\'company.noch_keine_mitarbeiter\') }}'),
        ('>Offene Einladungen<', '>{{ $t(\'company.offene_einladungen\') }}<'),
        ('Diese Personen wurden eingeladen und haben sich noch nicht registriert.', '{{ $t(\'company.einladungen_info\') }}'),
        ('>Link kopieren<', '>{{ $t(\'company.link_kopieren\') }}<'),
        ('>Zugriffsmatrix &amp; Berechtigungsübersicht<', '>{{ $t(\'company.zugriffsmatrix_titel\') }}<'),
        ('>Zugriffsmatrix & Berechtigungsübersicht<', '>{{ $t(\'company.zugriffsmatrix_titel\') }}<'),
        ('Wer wurde wo eingeladen, wer hat Zugriff auf welche Ordner und Projekte.', '{{ $t(\'company.zugriffsmatrix_desc\') }}'),
        ('>Zugriff (Ordner &amp; Projekte)<', '>{{ $t(\'company.zugriff_ordner_projekte\') }}<'),
        ('>Zugriff (Ordner & Projekte)<', '>{{ $t(\'company.zugriff_ordner_projekte\') }}<'),
        ("message: 'Möchtest du diese Gruppe wirklich löschen?'", "message: t('company.gruppe_loeschen_confirm')"),
        ("pageToast('Gruppe gelöscht.')", "pageToast(t('company.gruppe_geloescht'))"),
        ("pageToast('Fehler beim Löschen der Gruppe')", "pageToast(t('company.gruppe_loeschen_fehler'))"),
        ("pageToast('Fehler beim Ändern der Rolle')", "pageToast(t('company.rolle_aendern_fehler'))"),
        ("message: `Möchtest du die Firmenvorlage \"${tpl.name}\" wirklich löschen?`", "message: `${t('common.moechtest_du')} \"${tpl.name}\" ${t('company.vorlage_loeschen_confirm')}`"),
        ("pageToast('Firmenvorlage gelöscht.')", "pageToast(t('company.vorlage_geloescht'))"),
        ("pageToast('Fehler beim Löschen der Vorlage')", "pageToast(t('company.vorlage_loeschen_fehler'))"),
        ("pageToast('Upgrade-Anfrage wurde an das Taskster-Team übermittelt.')", "pageToast(t('company.upgrade_angefragt'))"),
    ])

    # pages/contacts/index.vue
    update_file("pages/contacts/index.vue", [
        ('title="Kartenansicht"', ':title="$t(\'contacts.kartenansicht_title\')"'),
        ('title="Tabellenansicht"', ':title="$t(\'contacts.tabellenansicht_title\')"'),
        ("title: 'Kontakt löschen'", "title: t('contacts.loeschen_modal_title')"),
        ("message: 'Möchtest du den Kontakt", "message: t('contacts.loeschen_modal_confirm')"),
    ])

    # pages/journal.vue
    update_file("pages/journal.vue", [
        ('title="Zum Ordner springen"', ':title="$t(\'journal.zum_ordner_springen\')"'),
        ('title="Zum Projekt springen"', ':title="$t(\'journal.zum_projekt_springen\')"'),
        ('title="Eintrag bearbeiten"', ':title="$t(\'journal.eintrag_bearbeiten\')"'),
        ('title="Eintrag löschen"', ':title="$t(\'journal.eintrag_loeschen\')"'),
        ('title="Vollständiges Original-Dokument / E-Mail ansehen"', ':title="$t(\'journal.original_ansehen_title\')"'),
        ('• Verknüpft: {{ entry.task_title }}', '• {{ $t(\'journal.aufgabe_verknuepft\') }} {{ entry.task_title }}'),
        ('<span>Vorgeschlagene Aktionen (KI-Agent) ({{ entry.metadata.action_items.length }}):</span>', '<span>{{ $t(\'journal.vorgeschlagene_aktionen\') }} ({{ entry.metadata.action_items.length }}):</span>'),
        ('>Inhalt / Notizen<', '>{{ $t(\'journal.inhalt_notizen\') }}<'),
        ('>Verknüpfte Aufgabe<', '>{{ $t(\'journal.aufgabe_verknuepft\') }}<'),
        ('>Aufgabe im Projekt öffnen<', '>{{ $t(\'journal.aufgabe_im_projekt_oeffnen\') }}<'),
    ])

    # pages/login.vue
    update_file("pages/login.vue", [
        ('placeholder="Max Mustermann"', ':placeholder="$t(\'login.name_placeholder\')"'),
        ('placeholder="Mindestens 6 Zeichen"', ':placeholder="$t(\'login.pw_min_6\')"'),
        ('>Zero-Trust Architektur &amp; Schweizer Präzision<', '>{{ $t(\'login.zero_trust_claim\') }}<'),
        ('>Zero-Trust Architektur & Schweizer Präzision<', '>{{ $t(\'login.zero_trust_claim\') }}<'),
        ('Einladung zu {{ invitationInfo.company_name }}', '{{ $t(\'login.einladung_zu\') }} {{ invitationInfo.company_name }}'),
        ('Du wurdest eingeladen, diesem Unternehmen beizutreten.', '{{ $t(\'login.einladung_desc\') }}'),
        ('>Vollständiger Name<', '>{{ $t(\'login.vollstaendiger_name\') }}<'),
    ])

    # pages/reset-password.vue
    update_file("pages/reset-password.vue", [
        ("error.value = 'Kein Sicherheitstoken angegeben.'", "error.value = t('reset.kein_token')"),
        ("error.value = 'Das Passwort muss mindestens 8 Zeichen lang sein.'", "error.value = t('reset.pw_min_8')"),
    ])

    # pages/index.vue
    update_file("pages/index.vue", [
        ('>Taskster Modern Work Management<', '>{{ $t(\'index.modern_work\') }}<'),
        ('>Mehrstufige Struktur<', '>{{ $t(\'index.mehrstufige_struktur_titel\') }}<'),
        ('>Präzise Rollen &amp; Rechte<', '>{{ $t(\'index.praezise_rollen_titel\') }}<'),
        ('>Präzise Rollen & Rechte<', '>{{ $t(\'index.praezise_rollen_titel\') }}<'),
        ('>Flexible Team-Verwaltung<', '>{{ $t(\'index.flexible_team_titel\') }}<'),
    ])

    # pages/settings.vue
    update_file("pages/settings.vue", [
        ("confirmModal('Einladung wirklich widerrufen?')", "confirmModal(t('settings.einladung_widerrufen_confirm'))"),
    ])

    # pages/projects/[id].vue
    update_file("pages/projects/[id].vue", [
        ("title: 'Zusatzfeld löschen',", "title: t('projects.zusatzfeld_loeschen_title'),"),
        ("confirmText: 'Feld löschen',", "confirmText: t('projects.feld_loeschen_btn'),"),
        ("pageToast('Zusatzfeld erfolgreich gelöscht')", "pageToast(t('projects.feld_geloescht'))"),
    ])

if __name__ == '__main__':
    main()

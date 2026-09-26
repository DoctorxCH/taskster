#!/usr/bin/env python3
"""
Final pass for complete i18n localization of pages/projects/[id].vue
"""
import json
import sys
from pathlib import Path

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ROOT = Path(__file__).resolve().parent.parent
LOCALES_DIR = ROOT / "i18n" / "locales"
PROJ_FILE = ROOT / "pages" / "projects" / "[id].vue"

FINAL_TRANSLATIONS = [
    ("projects.journal_search_placeholder", "Im Journal, E-Mails & Aufgaben suchen...", "Search in journal, emails & tasks...", "Hľadať v denníku, e-mailoch a úlohách..."),
    ("projects.aufgaben_alle_zuordnungen", "🔗 Aufgaben: Alle Zuordnungen", "🔗 Tasks: All assignments", "🔗 Úlohy: Všetky priradenia"),
    ("projects.nur_verknuepfte_eintraege", "📌 Nur verknüpfte Einträge", "📌 Linked entries only", "📌 Iba prepojené záznamy"),
    ("projects.datum_aelteste_zuerst", "📅 Datum (Älteste zuerst)", "📅 Date (oldest first)", "📅 Dátum (od najstarších)"),
    ("projects.keine_passenden_journaleintraege", "Keine passenden Journaleinträge gefunden", "No matching journal entries found", "Nenašli sa žiadne vyhovujúce záznamy v denníku"),
    ("projects.alle_erkannten_aufgaben_uebertragen", "Alle erkannten Aufgaben ins Kanban-Board übertragen", "Transfer all recognized tasks to Kanban board", "Preniesť všetky rozpoznané úlohy na Kanban nástenku"),
    ("projects.eintrag_loeschen", "Eintrag löschen", "Delete entry", "Odstrániť záznam"),
    ("projects.original_ansehen", "Vollständiges Original-Dokument / E-Mail ansehen", "View full original document / email", "Zobraziť celý pôvodný dokument / e-mail"),
    ("projects.verknuepfte_aufgabe", "Verknüpfte Aufgabe", "Linked task", "Prepojená úloha"),
    ("projects.aufgabe_oeffnen", "Aufgabe öffnen", "Open task", "Otvoriť úlohu"),
    ("projects.verknuepfung_loesen", "-- Verknüpfung lösen --", "-- Unlink task --", "-- Zrušiť prepojenie --"),
    ("projects.aufgabe_manuell_zuweisen", "Aufgabe manuell zuweisen:", "Assign task manually:", "Manuálne priradiť úlohu:"),
    ("projects.aufgabe_auswaehlen", "-- Aufgabe auswählen --", "-- Select task --", "-- Vybrať úlohu --"),
    ("projects.projektteam_berechtigungen", "Projektteam & Berechtigungen", "Project team & permissions", "Projektový tím a oprávnenia"),
    ("projects.projektinhaber_zugriff", "Projektinhaber (Voller administrativer Zugriff)", "Project owner (full administrative access)", "Vlastník projektu (plný administratívny prístup)"),
    ("projects.status_completed_option", "Abgeschlossen (Completed)", "Completed", "Dokončené"),
    ("projects.schluessel_key", "Schlüssel (Key)", "Key", "Kľúč"),
    ("projects.stoppuhr_projekt_starten_title", "Stoppuhr für dieses Projekt starten", "Start stopwatch for this project", "Spustiť stopky pre tento projekt"),
    ("projects.kontakte_fuer_projekt", "Kontakte & Ansprechpartner für dieses Projekt", "Contacts & representatives for this project", "Kontakty a kontaktné osoby pre tento projekt"),
    ("projects.lade_projektkontakte", "Lade Projektkontakte...", "Loading project contacts...", "Načítavanie projektových kontaktov..."),
    ("projects.noch_keine_kontakte", "Noch keine Kontakte für dieses Projekt", "No contacts for this project yet", "Pre tento projekt zatiaľ nie sú žiadne kontakty"),
    ("projects.whatsapp_chat_oeffnen", "WhatsApp Chat öffnen", "Open WhatsApp chat", "Otvoriť WhatsApp chat"),
    ("projects.osm_oeffnen", "In OpenStreetMap öffnen", "Open in OpenStreetMap", "Otvoriť v OpenStreetMap"),
    ("projects.vcard_herunterladen", "vCard herunterladen", "Download vCard", "Stiahnuť vCard"),
    ("projects.link_oeffnen", "Link öffnen", "Open link", "Otvoriť odkaz"),
    ("projects.feldtyp_textarea", "Längerer Text / Notizfeld (mehrzeilig)", "Long text / note field (multiline)", "Dlhší text / pole poznámky (viacriadkové)"),
    ("projects.feldtyp_number", "Zahl / Währung / Messwert", "Number / Currency / Value", "Číslo / Mena / Nameraná hodnota"),
    ("projects.status_done_logic", "Abgeschlossen (done)", "Completed (done)", "Dokončené (done)"),
    ("projects.verknuepfte_aufgabe_opt", "Verknüpfte Aufgabe (optional)", "Linked task (optional)", "Prepojená úloha (voliteľné)"),
    ("projects.keine_verknuepfung", "-- Keine Verknüpfung --", "-- No link --", "-- Žiadne prepojenie --"),
    ("projects.vorgeschlagene_aufgaben", "✨ Vorgeschlagene Aufgaben (Klick zum Zuweisen):", "✨ Suggested tasks (click to assign):", "✨ Navrhované úlohy (kliknite pre priradenie):"),
    ("projects.aufgaben_auto_kanban", "Vorgeschlagene Aufgaben automatisch direkt im Kanban-Board anlegen", "Automatically create suggested tasks directly on Kanban board", "Automaticky vytvoriť navrhované úlohy priamo na Kanban nástenke"),
    ("projects.email_notiz_placeholder", "Notiz oder Inhalt der E-Mail hier einfügen...", "Insert note or email content here...", "Vložte sem poznámku alebo obsah e-mailu..."),
    ("projects.gesamtprojekt_ohne_aufgabe", "🏢 Gesamtprojekt (ohne Aufgabe)", "🏢 Entire project (without task)", "🏢 Celý projekt (bez úlohy)"),
    ("projects.taetigkeit_beschreibung", "Tätigkeit / Beschreibung", "Activity / Description", "Činnosť / Popis"),
    ("projects.adresse_placeholder", "z.B. Bahnhofstrasse 12, 8001 Zürich", "e.g. Station Street 12, Zurich", "napr. Hlavná 12, Bratislava"),
    ("projects.gruppe_placeholder", "z.B. Handwerker, Planer, Behörde", "e.g. Craftsman, Planner, Authority", "napr. Remeselník, Projektant, Úrad"),
    ("projects.notizen_placeholder", "Notizen zur Baustelle, Schlüsselzugang, etc.", "Notes on job site, key access, etc.", "Poznámky k stavenisku, prístupu ku kľúčom atď."),
    ("projects.projektjournal", "Projektjournal", "Project journal", "Projektový denník"),
    ("projects.schnellsuche_zuweisen", "Schnellsuche & Einträge zuweisen", "Quick search & assign entries", "Rýchle vyhľadávanie a priraďovanie záznamov"),
    ("projects.aufgabe_colon", "Aufgabe:", "Task:", "Úloha:"),
    ("projects.aufgabe_oeffnen_arrow", "Aufgabe öffnen →", "Open task →", "Otvoriť úlohu →"),
    ("projects.empfaenger", "Empfänger:", "Recipient:", "Príjemca:"),
    ("common.bestaetigen", "Bestätigen", "Confirm", "Potvrdiť"),
]

def main():
    # 1. Update Locales
    de = json.loads((LOCALES_DIR / "de.json").read_text(encoding='utf-8'))
    en = json.loads((LOCALES_DIR / "en.json").read_text(encoding='utf-8'))
    sk = json.loads((LOCALES_DIR / "sk.json").read_text(encoding='utf-8'))

    added = 0
    for k, de_v, en_v, sk_v in FINAL_TRANSLATIONS:
        if k not in de:
            de[k] = de_v
            added += 1
        if k not in en:
            en[k] = en_v
        if k not in sk:
            sk[k] = sk_v

    (LOCALES_DIR / "de.json").write_text(json.dumps(de, ensure_ascii=False, indent=2), encoding='utf-8')
    (LOCALES_DIR / "en.json").write_text(json.dumps(en, ensure_ascii=False, indent=2), encoding='utf-8')
    (LOCALES_DIR / "sk.json").write_text(json.dumps(sk, ensure_ascii=False, indent=2), encoding='utf-8')
    print(f"[i18n] Final pass added {added} keys. Total: {len(de)}")

    # 2. Update projects/[id].vue
    content = PROJ_FILE.read_text(encoding='utf-8')
    orig = content

    replacements = [
        ('placeholder="Im Journal, E-Mails & Aufgaben suchen..."', ':placeholder="$t(\'projects.journal_search_placeholder\')"'),
        ('>🔗 Aufgaben: Alle Zuordnungen<', '>{{ $t(\'projects.aufgaben_alle_zuordnungen\') }}<'),
        ('>📌 Nur verknüpfte Einträge<', '>{{ $t(\'projects.nur_verknuepfte_eintraege\') }}<'),
        ('>📅 Datum (Älteste zuerst)<', '>{{ $t(\'projects.datum_aelteste_zuerst\') }}<'),
        ('>Keine passenden Journaleinträge gefunden<', '>{{ $t(\'projects.keine_passenden_journaleintraege\') }}<'),
        ('title="Alle erkannten Aufgaben ins Kanban-Board übertragen"', ':title="$t(\'projects.alle_erkannten_aufgaben_uebertragen\')"'),
        ('title="Eintrag löschen"', ':title="$t(\'projects.eintrag_loeschen\')"'),
        ('title="Vollständiges Original-Dokument / E-Mail ansehen"', ':title="$t(\'projects.original_ansehen\')"'),
        ('>Verknüpfte Aufgabe<', '>{{ $t(\'projects.verknuepfte_aufgabe\') }}<'),
        ('>Aufgabe öffnen<', '>{{ $t(\'projects.aufgabe_oeffnen\') }}<'),
        ('>-- Verknüpfung lösen --<', '>{{ $t(\'projects.verknuepfung_loesen\') }}<'),
        ('>Aufgabe manuell zuweisen:<', '>{{ $t(\'projects.aufgabe_manuell_zuweisen\') }}<'),
        ('>-- Aufgabe auswählen --<', '>{{ $t(\'projects.aufgabe_auswaehlen\') }}<'),
        ('>Projektteam & Berechtigungen<', '>{{ $t(\'projects.projektteam_berechtigungen\') }}<'),
        ('>Projektinhaber (Voller administrativer Zugriff)<', '>{{ $t(\'projects.projektinhaber_zugriff\') }}<'),
        ('>Abgeschlossen (Completed)<', '>{{ $t(\'projects.status_completed_option\') }}<'),
        ('>📅 Fälligkeitsdatum<', '>{{ $t(\'projects.faelligkeitsdatum_label\') }}<'),
        ('>Schlüssel (Key)<', '>{{ $t(\'projects.schluessel_key\') }}<'),
        ('title="Stoppuhr für dieses Projekt starten"', ':title="$t(\'projects.stoppuhr_projekt_starten_title\')"'),
        ('>Stoppuhr starten<', '>{{ $t(\'projects.stoppuhr_starten\') }}<'),
        ('>Kontakte & Ansprechpartner für dieses Projekt<', '>{{ $t(\'projects.kontakte_fuer_projekt\') }}<'),
        ('>Lade Projektkontakte...<', '>{{ $t(\'projects.lade_projektkontakte\') }}<'),
        ('>Noch keine Kontakte für dieses Projekt<', '>{{ $t(\'projects.noch_keine_kontakte\') }}<'),
        ('title="WhatsApp Chat öffnen"', ':title="$t(\'projects.whatsapp_chat_oeffnen\')"'),
        ('title="In OpenStreetMap öffnen"', ':title="$t(\'projects.osm_oeffnen\')"'),
        ('title="vCard herunterladen"', ':title="$t(\'projects.vcard_herunterladen\')"'),
        ('title="Link öffnen"', ':title="$t(\'projects.link_oeffnen\')"'),
        ('>Längerer Text / Notizfeld (mehrzeilig)<', '>{{ $t(\'projects.feldtyp_textarea\') }}<'),
        ('>Zahl / Währung / Messwert<', '>{{ $t(\'projects.feldtyp_number\') }}<'),
        ('>Abgeschlossen (done)<', '>{{ $t(\'projects.status_done_logic\') }}<'),
        ('>Verknüpfte Aufgabe (optional)<', '>{{ $t(\'projects.verknuepfte_aufgabe_opt\') }}<'),
        ('>-- Keine Verknüpfung --<', '>{{ $t(\'projects.keine_verknuepfung\') }}<'),
        ('>✨ Vorgeschlagene Aufgaben (Klick zum Zuweisen):<', '>{{ $t(\'projects.vorgeschlagene_aufgaben\') }}<'),
        ('>Vorgeschlagene Aufgaben automatisch direkt im Kanban-Board anlegen<', '>{{ $t(\'projects.aufgaben_auto_kanban\') }}<'),
        ('placeholder="Notiz oder Inhalt der E-Mail hier einfügen..."', ':placeholder="$t(\'projects.email_notiz_placeholder\')"'),
        ('>🏢 Gesamtprojekt (ohne Aufgabe)<', '>{{ $t(\'projects.gesamtprojekt_ohne_aufgabe\') }}<'),
        ('>Tätigkeit / Beschreibung<', '>{{ $t(\'projects.taetigkeit_beschreibung\') }}<'),
        ('placeholder="z.B. Bahnhofstrasse 12, 8001 Zürich"', ':placeholder="$t(\'projects.adresse_placeholder\')"'),
        ('placeholder="z.B. Handwerker, Planer, Behörde"', ':placeholder="$t(\'projects.gruppe_placeholder\')"'),
        ('placeholder="Notizen zur Baustelle, Schlüsselzugang, etc."', ':placeholder="$t(\'projects.notizen_placeholder\')"'),
        ('>Projektjournal<', '>{{ $t(\'projects.projektjournal\') }}<'),
        ('>Schnellsuche & Einträge zuweisen<', '>{{ $t(\'projects.schnellsuche_zuweisen\') }}<'),
        ('>Aufgabe:<', '>{{ $t(\'projects.aufgabe_colon\') }}<'),
        ('>Aufgabe öffnen →<', '>{{ $t(\'projects.aufgabe_oeffnen_arrow\') }}<'),
        ('>Empfänger:<', '>{{ $t(\'projects.empfaenger\') }}<'),

        # Script dialogs & toasts
        ("confirmText: 'Bestätigen'", "confirmText: t('common.bestaetigen')"),
        ("title: 'Kontakt löschen'", "title: t('projects.kontakt_entfernen_title')"),
        ("showToast('Bitte eine Dauer grösser als 0 angeben.', 'warning')", "showToast(t('projects.bitte_dauer_groesser_null'), 'warning')"),
        ("title: 'Zeiteintrag löschen'", "title: t('projects.zeiteintrag_loeschen_title')"),
        ("title: 'Abschnitt löschen'", "title: t('projects.abschnitt_loeschen_title')"),
        ("confirmText: 'Projekt abschließen'", "confirmText: t('projects.projekt_abschliessen_title')"),
        ("showToast('Bitte gib mindestens einen Aufgabentitel ein.', 'warning')", "showToast(t('projects.bitte_titel_eingeben'), 'warning')"),
        ("showToast('Kein Abschnitt im Projekt vorhanden, um eine Aufgabe anzulegen.', 'warning')", "showToast(t('projects.kein_abschnitt_vorhanden'), 'warning')"),
    ]

    applied = 0
    for old, new in replacements:
        if old in content:
            content = content.replace(old, new)
            applied += 1

    print(f"Applied {applied} of {len(replacements)} final replacements.")

    if content != orig:
        PROJ_FILE.write_text(content, encoding='utf-8')
        print("[OK] pages/projects/[id].vue final pass saved successfully!")
    else:
        print("[WARN] No changes made in final pass.")

if __name__ == '__main__':
    main()

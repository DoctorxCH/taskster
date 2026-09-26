#!/usr/bin/env python3
"""
Second pass of replacements for pages/projects/[id].vue
Handles remaining template strings and all script setup notifications & confirms.
"""
import sys
from pathlib import Path

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

PROJ_FILE = Path(__file__).resolve().parent.parent / "pages" / "projects" / "[id].vue"

def main():
    content = PROJ_FILE.read_text(encoding='utf-8')
    orig = content

    replacements = [
        # --- Remaining Template Strings ---
        ('text-lg font-black text-slate-900 mb-1">Neuen Abschnitt anlegen</h3>', 'text-lg font-black text-slate-900 mb-1">{{ $t(\'projects.abschnitt_anlegen_title\') }}</h3>'),
        ('<option value="inherit">Standard (Alle Projektmitglieder haben Zugriff)</option>', '<option value="inherit">{{ $t(\'projects.sichtbarkeit_standard\') }}</option>'),
        ('text-lg font-black text-slate-900">Projekt-Abschnitte verwalten</h3>', 'text-lg font-black text-slate-900">{{ $t(\'projects.abschnitte_verwalten_title\') }}</h3>'),
        ('text-base font-black text-slate-900">Allgemeine Projekt-Einstellungen</h3>', 'text-base font-black text-slate-900">{{ $t(\'projects.settings_general_title\') }}</h3>'),
        ('<p class="text-xs text-slate-500">Passe den Projektnamen, den Status und projektweite Eigenschaften an.</p>', '<p class="text-xs text-slate-500">{{ $t(\'projects.settings_general_desc\') }}</p>'),
        ('text-xs font-bold text-slate-800">Sichtbarkeit des Projekts</label>', 'text-xs font-bold text-slate-800">{{ $t(\'projects.sichtbarkeit_des_projekts\') }}</label>'),
        ('<span>Benutzerdefinierte Felder & Logik</span>', '<span>{{ $t(\'projects.benutzerdefinierte_felder_logik\') }}</span>'),
        ('uppercase tracking-wider">Sichtbarkeit Standard-Felder</h4>', 'uppercase tracking-wider">{{ $t(\'projects.sichtbarkeit_standard_felder\') }}</h4>'),
        ('<p class="text-[11px] text-slate-400 mt-0.5">Hier kannst du Standard-Felder unter bestimmten Bedingungen ausblenden.</p>', '<p class="text-[11px] text-slate-400 mt-0.5">{{ $t(\'projects.sichtbarkeit_standard_desc\') }}</p>'),
        ('<div class="text-[10px] text-slate-400 mt-0.5">Immer sichtbar</div>', '<div class="text-[10px] text-slate-400 mt-0.5">{{ $t(\'projects.immer_sichtbar\') }}</div>'),
        ('uppercase tracking-wider text-cyan-800">Gesamtaufwand</span>', 'uppercase tracking-wider text-cyan-800">{{ $t(\'projects.gesamtaufwand\') }}</span>'),
        ('uppercase tracking-wider text-slate-600">Stunden-Budget</span>', 'uppercase tracking-wider text-slate-600">{{ $t(\'projects.stunden_budget\') }}</span>'),
        ('uppercase tracking-wider text-emerald-800">Gesamtkosten</span>', 'uppercase tracking-wider text-emerald-800">{{ $t(\'projects.gesamtkosten\') }}</span>'),
        ('uppercase tracking-wider text-slate-600">Kosten-Budget</span>', 'uppercase tracking-wider text-slate-600">{{ $t(\'projects.kosten_budget\') }}</span>'),
        ('<option value="">Alle Buchungen (Projekt & Aufgaben)</option>', '<option value="">{{ $t(\'projects.alle_buchungen\') }}</option>'),
        ('<option value="__project__">Nur Gesamtprojekt (ohne Aufgabe)</option>', '<option value="__project__">{{ $t(\'projects.nur_gesamtprojekt\') }}</option>'),
        ('<th class="py-3 px-4">Rapportiert auf</th>', '<th class="py-3 px-4">{{ $t(\'projects.rapportiert_auf\') }}</th>'),
        ('<th class="py-3 px-4">Tätigkeit / Notiz</th>', '<th class="py-3 px-4">{{ $t(\'projects.taetigkeit_notiz\') }}</th>'),
        ('text-lg font-black text-slate-900 mb-1">✏️ Zeiteintrag anpassen</h3>', 'text-lg font-black text-slate-900 mb-1">{{ $t(\'projects.zeiteintrag_anpassen\') }}</h3>'),
        ('text-lg font-black text-slate-900 mb-1">⏱️ Zeit erfassen</h3>', 'text-lg font-black text-slate-900 mb-1">{{ $t(\'projects.zeit_erfassen\') }}</h3>'),
        ('text-lg font-black text-slate-900 mb-1">Teammitglied ins Projekt einladen</h3>', 'text-lg font-black text-slate-900 mb-1">{{ $t(\'projects.teammitglied_einladen\') }}</h3>'),
        ('else class="text-[10px] text-slate-400">Texte werden sicher verarbeitet</span>', 'else class="text-[10px] text-slate-400">{{ $t(\'projects.texte_sicher_verarbeitet\') }}</span>'),
        ('text-xs font-bold text-slate-800 mb-1">Vorname</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.vorname\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">\n                Nachname / Name <span class="text-rose-500">*</span>', 'text-xs font-bold text-slate-800 mb-1">\n                {{ $t(\'projects.nachname\') }} <span class="text-rose-500">*</span>'),
        ('text-xs font-bold text-slate-800 mb-1">Firma / Unternehmen</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.firma_unternehmen\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Funktion / Gewerk</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.funktion_gewerk\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Mobile (Handy)</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.mobile_handy\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Telefon Festnetz</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.telefon_festnetz\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Geschäftsadresse</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.geschaeftsadresse\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Webseite</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.webseite\') }}</label>'),
        ('text-xs font-bold text-slate-800 mb-1">Gruppe / Kategorie</label>', 'text-xs font-bold text-slate-800 mb-1">{{ $t(\'projects.gruppe_kategorie\') }}</label>'),
        ("jectContact ? 'Änderungen speichern' : 'Kontakt speichern'", "jectContact ? $t('common.speichern') : $t('projects.kontakt_speichern_btn')"),
        ("<span>+ Kontakt anlegen</span>", "<span>{{ $t('projects.kontakt_anlegen_btn') }}</span>"),

        # --- Script Setup Notifications, Toasts & Dialogs ---
        ("showToast('Projekt erfolgreich als abgeschlossen markiert!', 'success')", "showToast(t('projects.projekt_abgeschlossen_toast'), 'success')"),
        ("showToast('Fehler beim Ändern des Aufgabenstatus', 'error')", "showToast(t('projects.fehler_task_status'), 'error')"),
        ("showToast('Bitte gib mindestens einen Aufgabentitel ein.', 'warning')", "showToast(t('projects.bitte_titel_eingeben'), 'warning')"),
        ("showToast('Fehler beim Erstellen der Aufgabe', 'error')", "showToast(t('projects.fehler_task_erstellen'), 'error')"),
        ("confirmText: 'Aufgabe abschließen'", "confirmText: t('projects.aufgabe_abschliessen_title')"),
        ("showToast('Aufgabe als erledigt markiert', 'success')", "showToast(t('projects.aufgabe_erledigt_toast'), 'success')"),
        ("showToast('Fehler beim Erstellen der Unteraufgabe', 'error')", "showToast(t('projects.fehler_subtask_erstellen'), 'error')"),
        ("showToast('Fehler beim Senden des Kommentars', 'error')", "showToast(t('projects.fehler_kommentar_senden'), 'error')"),
        ("confirmText: 'Aufgabe löschen'", "confirmText: t('projects.aufgabe_loeschen_title')"),
        ("showToast('Aufgabe erfolgreich gelöscht', 'success')", "showToast(t('projects.aufgabe_geloescht_toast'), 'success')"),
        ("showToast('Datei erfolgreich entfernt', 'success')", "showToast(t('projects.datei_entfernt_toast'), 'success')"),
        ("showToast('Excel-Bibliothek (XLSX) steht nicht zur Verfügung.', 'error')", "showToast(t('projects.xlsx_not_available'), 'error')"),
        ("showToast('Die ausgewählte Datei ist leer.', 'warning')", "showToast(t('projects.file_is_empty'), 'warning')"),
        ("showToast('Bitte weise mindestens einer Spalte das Pflichtfeld \"Aufgabentitel\" zu.', 'warning')", "showToast(t('projects.pflichtfeld_titel_zuweisen'), 'warning')"),
        ("showToast('Bitte wähle einen Ziel-Abschnitt für die Aufgaben aus.', 'warning')", "showToast(t('projects.ziel_abschnitt_waehlen'), 'warning')"),
        ("showToast('Fehler während des Imports: ' + (err as any)?.message", "showToast(t('projects.fehler_import') + ' ' + (err as any)?.message"),
        ("showToast('Fehler beim Speichern der Aufgabe', 'error')", "showToast(t('projects.fehler_task_speichern'), 'error')"),
        ("showToast('Bitte gib einen Titel für den Journaleintrag an.', 'warning')", "showToast(t('projects.bitte_journal_titel'), 'warning')"),
        ("showToast('Fehler beim Speichern des Journaleintrags', 'error')", "showToast(t('projects.fehler_journal_speichern'), 'error')"),
        ("showToast('Bitte gib einen Inhalt für die Notiz an.', 'warning')", "showToast(t('projects.bitte_notiz_inhalt'), 'warning')"),
        ("showToast('Fehler beim Speichern der Notiz', 'error')", "showToast(t('projects.fehler_notiz_speichern'), 'error')"),
        ("confirmText: 'Eintrag löschen'", "confirmText: t('projects.journaleintrag_loeschen_title')"),
        ("message: 'Möchtest du diesen Journaleintrag wirklich unwiderruflich löschen?'", "message: t('projects.vorgang_unwiderruflich')"),
        ("showToast('Journaleintrag erfolgreich gelöscht', 'success')", "showToast(t('projects.journal_geloescht_toast'), 'success')"),
        ("showToast('Fehler bei der KI-Analyse', 'error')", "showToast(t('projects.fehler_ki_analyse'), 'error')"),
        ("showToast('Kein Abschnitt im Projekt vorhanden, um eine Aufgabe anzulegen.', 'warning')", "showToast(t('projects.kein_abschnitt_vorhanden'), 'warning')"),
        ("showToast('Mitglied erfolgreich hinzugefügt!', 'success')", "showToast(t('projects.mitglied_hinzugefuegt_toast'), 'success')"),
        ("showToast('Fehler beim Einladen des Mitglieds', 'error')", "showToast(t('projects.fehler_mitglied_einladen'), 'error')"),
        ("showToast('Projekt-Einstellungen erfolgreich gespeichert!', 'success')", "showToast(t('projects.settings_gespeichert'), 'success')"),
        ("showToast('Fehler beim Speichern der Einstellungen', 'error')", "showToast(t('projects.fehler_settings_speichern'), 'error')"),
        ("showToast('Fehler beim Löschen des Projekts', 'error')", "showToast(t('projects.fehler_projekt_loeschen'), 'error')"),
        ("showToast('Fehler beim Speichern des Feldes', 'error')", "showToast(t('projects.fehler_zusatzfeld_speichern'), 'error')"),
        ("showToast('Feld gelöscht', 'success')", "showToast(t('projects.feld_geloescht'), 'success')"),
        ("showToast('Fehler beim Erstellen des Abschnitts', 'error')", "showToast(t('projects.fehler_abschnitt_erstellen'), 'error')"),
        ("showToast('Abschnitt gelöscht', 'success')", "showToast(t('projects.abschnitt_geloescht'), 'success')"),
        ("showToast('Fehler beim Speichern der Abschnitte', 'error')", "showToast(t('projects.fehler_abschnitte_speichern'), 'error')"),
        ("showToast('Kontakt erfolgreich gelöscht', 'success')", "showToast(t('projects.kontakt_geloescht'), 'success')"),
        ("showToast('Bitte eine Dauer grösser als 0 angeben.', 'warning')", "showToast(t('projects.bitte_dauer_groesser_null'), 'warning')"),
        ("showToast('Fehler beim Erfassen der Zeit', 'error')", "showToast(t('projects.fehler_zeit_erfassen'), 'error')"),
        ("showToast('Fehler beim Aktualisieren der Zeit', 'error')", "showToast(t('projects.fehler_zeit_aktualisieren'), 'error')"),
        ("showToast('Zeiteintrag gelöscht', 'success')", "showToast(t('projects.zeiteintrag_geloescht'), 'success')"),
        ("showToast('✓ Daten erfolgreich erkannt und ins Formular übertragen!', 'success')", "showToast(t('projects.ki_daten_erkannt'), 'success')"),
        ("showToast('Fehler bei der KI-Erkennung.', 'error')", "showToast(t('projects.ki_erkennung_fehler'), 'error')"),
        ("showToast('Bitte mindestens einen Nachnamen eingeben.', 'warning')", "showToast(t('projects.bitte_nachname_eingeben'), 'warning')"),
        ("showToast('Duplikat erkannt: Ein ähnlicher Kontakt existiert bereits in diesem Projekt.', 'info')", "showToast(t('projects.duplikat_kontakt'), 'info')"),
        ("showToast('Fehler beim Speichern des Kontakts', 'error')", "showToast(t('projects.fehler_kontakt_speichern'), 'error')"),
        ("showToast('Fehler beim Ausführen der Aktion', 'error')", "showToast(t('projects.fehler_aktion'), 'error')"),
        ("showToast('Projekt-Export ist exklusiv für den Enterprise-Tarif verfügbar.', 'warning')", "showToast(t('projects.export_enterprise_only'), 'warning')"),
        ("showToast('Fehler beim Exportieren des Projekts', 'error')", "showToast(t('projects.fehler_export'), 'error')"),
        ("showToast('Fehler beim Zusammenführen des Kontakts', 'error')", "showToast(t('projects.fehler_kontakt_merge'), 'error')"),
        ("showToast('Fehler beim Speichern des Zusatzfeldes', 'error')", "showToast(t('projects.fehler_zusatzfeld_speichern'), 'error')"),
        ("showToast('Zusatzfeld erfolgreich gelöscht', 'success')", "showToast(t('projects.zusatzfeld_geloescht'), 'success')"),
        ("showToast('Journal-Eintrag erfolgreich aktualisiert', 'success')", "showToast(t('projects.journal_aktualisiert'), 'success')"),
        ("showToast('Fehler beim Aktualisieren der Aufgabenverknüpfung', 'error')", "showToast(t('projects.fehler_task_link'), 'error')"),
        ("showToast('Konnte Aufgabe nicht verschieben', 'error')", "showToast(t('projects.fehler_task_move'), 'error')"),
        ("showToast('Zugriff verweigert oder Projekt nicht gefunden.', 'error')", "showToast(t('projects.zugriff_verweigert'), 'error')"),
    ]

    applied = 0
    for old, new in replacements:
        if old in content:
            content = content.replace(old, new)
            applied += 1

    print(f"Pass 2: Applied {applied} of {len(replacements)} substitutions.")

    if content != orig:
        PROJ_FILE.write_text(content, encoding='utf-8')
        print("[OK] pages/projects/[id].vue pass 2 applied successfully!")
    else:
        print("[WARN] No changes in pass 2.")

if __name__ == '__main__':
    main()

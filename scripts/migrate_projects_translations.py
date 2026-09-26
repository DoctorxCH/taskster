#!/usr/bin/env python3
"""
Taskster i18n – Migrate pages/projects/[id].vue dialogs, modals & color labels
"""
import json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCALES = ROOT / "i18n" / "locales"

PROJECT_TRANSLATIONS = [
    ("projects.kontakt_entfernen_title", "Kontakt entfernen", "Remove contact", "Odstrániť kontakt"),
    ("projects.zeiteintrag_loeschen_title", "Zeiteintrag löschen", "Delete time entry", "Odstrániť časový záznam"),
    ("projects.zeiteintrag_loeschen_confirm", "Möchtest du diesen Zeiteintrag wirklich löschen?", "Do you really want to delete this time entry?", "Naozaj chcete odstrániť tento časový záznam?"),
    ("projects.feld_loeschen_title", "Feld löschen", "Delete field", "Odstrániť pole"),
    ("projects.feld_loeschen_confirm", "Möchtest du dieses benutzerdefinierte Feld wirklich löschen?", "Do you really want to delete this custom field?", "Naozaj chcete odstrániť toto vlastné pole?"),
    ("projects.abschnitt_loeschen_title", "Abschnitt löschen", "Delete section", "Odstrániť sekciu"),
    ("projects.zugewiesen", "Zugewiesen", "Assigned", "Priradené"),
    ("projects.projekt_abschliessen_title", "Projekt abschließen", "Complete project", "Dokončiť projekt"),
    ("projects.alle_aufgaben_erledigt", "Alle Aufgaben erledigt!", "All tasks completed!", "Všetky úlohy sú hotové!"),
    ("projects.projekt_abschliessen_confirm", "Alle Aufgaben in diesem Projekt sind erledigt! Möchtest du das gesamte Projekt abschließen?", "All tasks in this project are completed! Do you want to mark the entire project as completed?", "Všetky úlohy v tomto projekte sú hotové! Chcete označiť celý projekt ako dokončený?"),
    ("projects.aufgabe_abschliessen_title", "Aufgabe abschließen", "Complete task", "Dokončiť úlohu"),
    ("projects.alle_unterpunkte_erledigt", "Alle Unterpunkte erledigt", "All sub-items completed", "Všetky podbody sú hotové"),
    ("projects.aufgabe_abschliessen_confirm", "Alle Unterpunkte und Checklistenpunkte sind erledigt. Möchtest du diese Aufgabe als erledigt markieren?", "All sub-items and checklist items are done. Do you want to mark this task as completed?", "Všetky podbody a položky kontrolného zoznamu sú hotové. Chcete označiť túto úlohu ako dokončenú?"),
    ("projects.aufgabe_loeschen_title", "Aufgabe löschen", "Delete task", "Odstrániť úlohu"),
    ("projects.aufgabe_loeschen_unwiderruflich_confirm", "Möchtest du diese Aufgabe wirklich unwiderruflich löschen?", "Do you really want to permanently delete this task?", "Naozaj chcete natrvalo odstrániť túto úlohu?"),
    ("projects.aufgabe_loeschen_confirm", "Möchtest du diese Aufgabe wirklich löschen?", "Do you really want to delete this task?", "Naozaj chcete odstrániť túto úlohu?"),
    ("projects.datei_entfernen_title", "Datei entfernen", "Remove file", "Odstrániť súbor"),
    ("projects.datei_entfernen_confirm", "Möchtest du diese Datei wirklich entfernen?", "Do you really want to remove this file?", "Naozaj chcete odstrániť tento súbor?"),
    ("projects.journaleintrag_loeschen_title", "Journaleintrag löschen", "Delete journal entry", "Odstrániť žurnálový záznam"),
    ("projects.vorgang_unwiderruflich", "Dieser Vorgang kann nicht rückgängig gemacht werden", "This action cannot be undone", "Túto akciu nie je možné vrátiť späť"),
    # Colors
    ("colors.indigo_soft", "Indigo Soft", "Soft Indigo", "Mäkká indigová"),
    ("colors.mint_soft", "Mint Soft", "Soft Mint", "Mäkká mätová"),
    ("colors.amber_soft", "Amber Soft", "Soft Amber", "Mäkká jantárová"),
    ("colors.rose_soft", "Rose Soft", "Soft Rose", "Mäkká ružová"),
    ("colors.lila_soft", "Lila Soft", "Soft Purple", "Mäkká fialová"),
    ("colors.cyan_soft", "Cyan Soft", "Soft Cyan", "Mäkká tyrkysová"),
    ("colors.slate_soft", "Slate Soft", "Soft Slate", "Mäkká bridlicová"),
    ("colors.cyan", "Cyan", "Cyan", "Tyrkysová"),
    ("colors.lila", "Lila", "Purple", "Fialová"),
    ("colors.amber", "Amber", "Amber", "Jantárová"),
    ("colors.smaragd", "Smaragd", "Emerald", "Smaragdová"),
    ("colors.rot", "Rot", "Red", "Červená"),
    ("colors.pink", "Pink", "Pink", "Ružová"),
    ("colors.indigo", "Indigo", "Indigo", "Indigová"),
    ("colors.slate", "Slate", "Slate", "Bridlicová"),
]

def load_json(p):
    with open(p, 'r', encoding='utf-8') as f: return json.load(f)

def save_json(p, d):
    with open(p, 'w', encoding='utf-8') as f: json.dump(d, f, ensure_ascii=False, indent=2)

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    print("=== Migrating Projects Translations ===")

    de = load_json(LOCALES / "de.json")
    en = load_json(LOCALES / "en.json")
    sk = load_json(LOCALES / "sk.json")
    added = 0
    for key, de_val, en_val, sk_val in PROJECT_TRANSLATIONS:
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
    print(f"[i18n] {added} new keys added to locales.")

    proj_path = ROOT / "pages" / "projects" / "[id].vue"
    content = proj_path.read_text(encoding='utf-8')
    orig = content

    reps = [
        ("title: 'Kontakt entfernen'", "title: t('projects.kontakt_entfernen_title')"),
        ("title: 'Zeiteintrag löschen'", "title: t('projects.zeiteintrag_loeschen_title')"),
        ("subtitle: 'Dieser Vorgang kann nicht rückgängig gemacht werden'", "subtitle: t('projects.vorgang_unwiderruflich')"),
        ("message: 'Möchtest du diesen Zeiteintrag wirklich löschen?'", "message: t('projects.zeiteintrag_loeschen_confirm')"),
        ("title: 'Feld löschen'", "title: t('projects.feld_loeschen_title')"),
        ("message: 'Möchtest du dieses benutzerdefinierte Feld wirklich löschen?'", "message: t('projects.feld_loeschen_confirm')"),
        ("title: 'Abschnitt löschen'", "title: t('projects.abschnitt_loeschen_title')"),
        ("label: 'Zugewiesen'", "label: t('projects.zugewiesen')"),
        ("title: 'Projekt abschließen'", "title: t('projects.projekt_abschliessen_title')"),
        ("subtitle: 'Alle Aufgaben erledigt!'", "subtitle: t('projects.alle_aufgaben_erledigt')"),
        ("message: 'Alle Aufgaben in diesem Projekt sind erledigt! Möchtest du das gesamte Projekt abschließen?'", "message: t('projects.projekt_abschliessen_confirm')"),
        ("title: 'Aufgabe abschließen'", "title: t('projects.aufgabe_abschliessen_title')"),
        ("subtitle: 'Alle Unterpunkte erledigt'", "subtitle: t('projects.alle_unterpunkte_erledigt')"),
        ("message: 'Alle Unterpunkte und Checklistenpunkte sind erledigt. Möchtest du diese Aufgabe als erledigt markieren?'", "message: t('projects.aufgabe_abschliessen_confirm')"),
        ("title: 'Aufgabe löschen'", "title: t('projects.aufgabe_loeschen_title')"),
        ("message: 'Möchtest du diese Aufgabe wirklich unwiderruflich löschen?'", "message: t('projects.aufgabe_loeschen_unwiderruflich_confirm')"),
        ("message: 'Möchtest du diese Aufgabe wirklich löschen?'", "message: t('projects.aufgabe_loeschen_confirm')"),
        ("title: 'Datei entfernen'", "title: t('projects.datei_entfernen_title')"),
        ("message: 'Möchtest du diese Datei wirklich entfernen?'", "message: t('projects.datei_entfernen_confirm')"),
        ("title: 'Journaleintrag löschen'", "title: t('projects.journaleintrag_loeschen_title')"),
        ("name: 'Indigo Soft'", "name: t('colors.indigo_soft')"),
        ("name: 'Mint Soft'", "name: t('colors.mint_soft')"),
        ("name: 'Amber Soft'", "name: t('colors.amber_soft')"),
        ("name: 'Rose Soft'", "name: t('colors.rose_soft')"),
        ("name: 'Lila Soft'", "name: t('colors.lila_soft')"),
        ("name: 'Cyan Soft'", "name: t('colors.cyan_soft')"),
        ("name: 'Slate Soft'", "name: t('colors.slate_soft')"),
        ("name: 'Cyan'", "name: t('colors.cyan')"),
        ("name: 'Lila'", "name: t('colors.lila')"),
        ("name: 'Amber'", "name: t('colors.amber')"),
        ("name: 'Smaragd'", "name: t('colors.smaragd')"),
        ("name: 'Rot'", "name: t('colors.rot')"),
        ("name: 'Pink'", "name: t('colors.pink')"),
        ("name: 'Indigo'", "name: t('colors.indigo')"),
        ("name: 'Slate'", "name: t('colors.slate')"),
    ]

    for old, new in reps:
        content = content.replace(old, new)

    if content != orig:
        proj_path.write_text(content, encoding='utf-8')
        print("[OK] pages/projects/[id].vue successfully updated!")
    else:
        print("[WARN] No changes made to pages/projects/[id].vue")

if __name__ == '__main__':
    main()

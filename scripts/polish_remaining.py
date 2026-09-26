#!/usr/bin/env python3
"""
Polish final remaining script and template occurrences in pages/projects/[id].vue
"""
import json
import sys
from pathlib import Path

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

ROOT = Path(__file__).resolve().parent.parent
LOCALES_DIR = ROOT / "i18n" / "locales"
PROJ_FILE = ROOT / "pages" / "projects" / "[id].vue"

EXTRA_KEYS = [
    ("projects.kontakt_loeschen_confirm", "Möchtest du den Kontakt \"{name}\" wirklich löschen?", "Do you really want to delete contact \"{name}\"?", "Naozaj chcete odstrániť kontakt „{name}“?"),
    ("projects.abschnitt_loeschen_confirm", "Möchtest du den Abschnitt \"{title}\" wirklich löschen?", "Do you really want to delete section \"{title}\"?", "Naozaj chcete odstrániť sekciu „{title}“?"),
    ("projects.abschnitt_loeschen_mit_aufgaben_confirm", "Abschnitt \"{title}\" enthält {count} Aufgabe(n). Möchtest du diesen Abschnitt und alle darin enthaltenen Aufgaben wirklich unwiderruflich löschen?", "Section \"{title}\" contains {count} task(s). Do you really want to permanently delete this section and all tasks in it?", "Sekcia „{title}“ obsahuje {count} úloh. Naozaj chcete natrvalo odstrániť túto sekciu a všetky úlohy v nej?"),
    ("projects.neue_aufgabe_aus_journal", "Neue Aufgabe aus Journal", "New task from journal", "Nová úloha z denníka"),
]

def main():
    de = json.loads((LOCALES_DIR / "de.json").read_text(encoding='utf-8'))
    en = json.loads((LOCALES_DIR / "en.json").read_text(encoding='utf-8'))
    sk = json.loads((LOCALES_DIR / "sk.json").read_text(encoding='utf-8'))

    for k, d, e, s in EXTRA_KEYS:
        if k not in de: de[k] = d
        if k not in en: en[k] = e
        if k not in sk: sk[k] = s

    (LOCALES_DIR / "de.json").write_text(json.dumps(de, ensure_ascii=False, indent=2), encoding='utf-8')
    (LOCALES_DIR / "en.json").write_text(json.dumps(en, ensure_ascii=False, indent=2), encoding='utf-8')
    (LOCALES_DIR / "sk.json").write_text(json.dumps(sk, ensure_ascii=False, indent=2), encoding='utf-8')

    content = PROJ_FILE.read_text(encoding='utf-8')

    # 1. Line 5435
    content = content.replace(
        '<span class="text-[11px] text-slate-500">Aufgabe: <strong class="text-slate-800">{{ entry.task_title || getTaskTitle(entry.task_id) }}</strong></span>',
        '<span class="text-[11px] text-slate-500">{{ $t(\'projects.aufgabe_colon\') }} <strong class="text-slate-800">{{ entry.task_title || getTaskTitle(entry.task_id) }}</strong></span>'
    )

    # 2. Line 7347-7348
    content = content.replace(
        'message: `Möchtest du den Kontakt "${name}" wirklich löschen?`,\n    confirmText: \'Kontakt löschen\',',
        'message: t(\'projects.kontakt_loeschen_confirm\', { name }),\n    confirmText: t(\'projects.kontakt_entfernen_title\'),'
    )

    # 3. Line 7419, 7458, 7512
    content = content.replace(
        "showToast('Bitte eine Dauer grösser als 0 angeben.', 'info')",
        "showToast(t('projects.bitte_dauer_groesser_null'), 'info')"
    )

    # 4. Line 7489
    content = content.replace(
        "confirmText: 'Zeiteintrag löschen',",
        "confirmText: t(\'projects.zeiteintrag_loeschen_title\'),"
    )

    # 5. Line 7803-7809
    content = content.replace(
        'const msg = count > 0\n    ? `Abschnitt "${sec.title}" enthält ${count} Aufgabe(n). Möchtest du diesen Abschnitt und alle darin enthaltenen Aufgaben wirklich unwiderruflich löschen?`\n    : `Möchtest du den Abschnitt "${sec.title}" wirklich löschen?`',
        'const msg = count > 0\n    ? t(\'projects.abschnitt_loeschen_mit_aufgaben_confirm\', { title: sec.title, count })\n    : t(\'projects.abschnitt_loeschen_confirm\', { title: sec.title })'
    )
    content = content.replace(
        "confirmText: 'Abschnitt löschen',",
        "confirmText: t(\'projects.abschnitt_loeschen_title\'),"
    )

    # 6. Line 8009
    content = content.replace(
        "showToast('Bitte gib mindestens einen Aufgabentitel ein.', 'info')",
        "showToast(t('projects.bitte_titel_eingeben'), 'info')"
    )

    # 7. Line 9335 & 9345
    content = content.replace(
        "showToast('Kein Abschnitt im Projekt vorhanden, um eine Aufgabe anzulegen.', 'info')",
        "showToast(t('projects.kein_abschnitt_vorhanden'), 'info')"
    )
    content = content.replace(
        "title: item.title || 'Neue Aufgabe aus Journal',",
        "title: item.title || t('projects.neue_aufgabe_aus_journal'),"
    )

    PROJ_FILE.write_text(content, encoding='utf-8')
    print("[OK] Final polish applied successfully!")

if __name__ == '__main__':
    main()

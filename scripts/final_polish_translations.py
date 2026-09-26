#!/usr/bin/env python3
"""
Taskster i18n – Final Polish Migration
Localizes the remaining strings in:
- pages/company/index.vue
- pages/journal.vue
- pages/contacts/index.vue
- components/JournalEntryModal.vue
- components/JournalNoteModal.vue
- pages/calendar/index.vue
- pages/forgot-password.vue
- pages/login.vue
- pages/reset-password.vue
- pages/settings.vue
"""
import json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCALES = ROOT / "i18n" / "locales"

FINAL_TRANSLATIONS = [
    # Company Tabs
    ("company.tab_members", "Mitarbeiter & Co-Admins", "Employees & Co-Admins", "Zamestnanci a spolusprávcovia"),
    ("company.tab_matrix", "Zugriffsmatrix", "Access Matrix", "Matica prístupov"),
    ("company.tab_groups", "Gruppen & Berechtigungen", "Groups & Permissions", "Skupiny a oprávnenia"),
    ("company.tab_templates", "Firmenvorlagen", "Company Templates", "Firemné šablóny"),
    ("company.tab_billing", "Plan & Lizenzen", "Plan & Licenses", "Plán a licencie"),
    ("company.tab_settings", "Firmen-Einstellungen", "Company Settings", "Firemné nastavenia"),
    ("company.tab_support", "Support", "Support", "Podpora"),

    # Company Plans
    ("company.plan_starter", "Starter Plan", "Starter Plan", "Starter plán"),
    ("company.plan_pro", "Pro Business Plan", "Pro Business Plan", "Pro Business plán"),
    ("company.plan_enterprise", "Enterprise Custom", "Enterprise Custom", "Enterprise Custom"),

    # Priorities & Common
    ("common.niedrig", "Niedrig", "Low", "Nízka"),
    ("common.normal", "Normal", "Normal", "Normálna"),
    ("common.hoch", "Hoch", "High", "Vysoká"),
    ("common.dringend", "Dringend", "Urgent", "Naliehavá"),
    ("common.absender", "Absender", "Sender", "Odosielateľ"),
    ("common.empfaenger", "Empfänger", "Recipient", "Príjemca"),
    ("common.betreff", "Betreff", "Subject", "Predmet"),
    ("common.datum", "Datum", "Date", "Dátum"),

    # Journal Modal remaining
    ("journal.erkannte_kontakte_synced", "Erkannte Kontakte (in Kontakte synchronisiert):", "Detected contacts (synced to contacts):", "Rozpoznané kontakty (zosynchronizované do kontaktov):"),
    ("journal.dateianhaenge_label", "Dateianhänge", "File attachments", "Prílohy súborov"),
    ("journal.kontakt_waehlen_opt", "Kontakt auswählen...", "Select contact...", "Vybrať kontakt..."),
    ("journal.aufgabe_zuweisen_label", "Aufgabe zuweisen:", "Assign task:", "Priradiť úlohu:"),
    ("journal.aufgabe_auswaehlen_opt", "-- Aufgabe auswählen --", "-- Select task --", "-- Vybrať úlohu --"),

    # Settings
    ("settings.einladung_widerrufen_confirm", "Einladung wirklich widerrufen?", "Really revoke invitation?", "Naozaj odvolať pozvánku?"),

    # Password / Login / Auth
    ("auth.passwort_vergessen", "Passwort vergessen?", "Forgot password?", "Zabudli ste heslo?"),
    ("auth.email_gesendet", "E-Mail gesendet", "Email sent", "E-mail bol odoslaný"),
]

def load_json(p):
    with open(p, 'r', encoding='utf-8') as f: return json.load(f)

def save_json(p, d):
    with open(p, 'w', encoding='utf-8') as f: json.dump(d, f, ensure_ascii=False, indent=2)

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    print("=== Final Polish i18n Migration ===")

    de = load_json(LOCALES / "de.json")
    en = load_json(LOCALES / "en.json")
    sk = load_json(LOCALES / "sk.json")
    added = 0
    for key, de_val, en_val, sk_val in FINAL_TRANSLATIONS:
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

    # 1. pages/company/index.vue
    comp_path = ROOT / "pages" / "company" / "index.vue"
    c_content = comp_path.read_text(encoding='utf-8')
    orig_c = c_content

    old_tabs = """const tabs: { key: TabKey; label: string; icon: string }[] = [
  { key: 'members', label: 'Mitarbeiter & Co-Admins', icon: '👥' },
  { key: 'matrix', label: 'Zugriffsmatrix', icon: '🛡️' },
  { key: 'groups', label: 'Gruppen & Berechtigungen', icon: '🏷️' },
  { key: 'templates', label: 'Firmenvorlagen', icon: '📋' },
  { key: 'billing', label: 'Plan & Lizenzen', icon: '💳' },
  { key: 'settings', label: 'Firmen-Einstellungen', icon: '⚙️' },
  { key: 'support', label: 'Support', icon: '💬' }
]"""
    new_tabs = """const tabs = computed<{ key: TabKey; label: string; icon: string }[]>(() => [
  { key: 'members', label: t('company.tab_members'), icon: '👥' },
  { key: 'matrix', label: t('company.tab_matrix'), icon: '🛡️' },
  { key: 'groups', label: t('company.tab_groups'), icon: '🏷️' },
  { key: 'templates', label: t('company.tab_templates'), icon: '📋' },
  { key: 'billing', label: t('company.tab_billing'), icon: '💳' },
  { key: 'settings', label: t('company.tab_settings'), icon: '⚙️' },
  { key: 'support', label: t('company.tab_support'), icon: '💬' }
])"""
    c_content = c_content.replace(old_tabs, new_tabs)

    old_plans = """const plans = [
  { key: 'starter', name: 'Starter Plan', monthly: 0, seats: 5 },
  { key: 'pro', name: 'Pro Business Plan', monthly: 49, seats: 25 },
  { key: 'enterprise', name: 'Enterprise Custom', monthly: 189, seats: 100 }
]"""
    new_plans = """const plans = computed(() => [
  { key: 'starter', name: t('company.plan_starter'), monthly: 0, seats: 5 },
  { key: 'pro', name: t('company.plan_pro'), monthly: 49, seats: 25 },
  { key: 'enterprise', name: t('company.plan_enterprise'), monthly: 189, seats: 100 }
])"""
    c_content = c_content.replace(old_plans, new_plans)
    c_content = c_content.replace("plans.find(p => p.key === key)", "plans.value.find(p => p.key === key)")
    c_content = c_content.replace(
        "const priorityLabel = (key: string) => ({ low: 'Niedrig', normal: 'Normal', high: 'Hoch', urgent: 'Dringend' } as any)[key] || key",
        "const priorityLabel = (key: string) => ({ low: t('common.niedrig'), normal: t('common.normal'), high: t('common.hoch'), urgent: t('common.dringend') } as any)[key] || key"
    )

    if c_content != orig_c:
        comp_path.write_text(c_content, encoding='utf-8')
        print("[OK] pages/company/index.vue tabs & plans localized!")

    # 2. pages/journal.vue
    j_path = ROOT / "pages" / "journal.vue"
    j_content = j_path.read_text(encoding='utf-8')
    orig_j = j_content

    j_reps = [
        ('>Absender:</span>', '>{{ $t(\'common.absender\') }}:</span>'),
        ('>Empfänger:</span>', '>{{ $t(\'common.empfaenger\') }}:</span>'),
        ('>Betreff:</span>', '>{{ $t(\'common.betreff\') }}:</span>'),
        ('>Datum:</span>', '>{{ $t(\'common.datum\') }}:</span>'),
        ('<span>Erkannte Kontakte (in Kontakte synchronisiert):</span>', '<span>{{ $t(\'journal.erkannte_kontakte_synced\') }}</span>'),
        ('Dateianhänge ({{ entry.attachments.length }})', '{{ $t(\'journal.dateianhaenge_label\') }} ({{ entry.attachments.length }})'),
        ('>-- Verknüpfung lösen --<', '>{{ $t(\'journal.keine_verknuepfung_label\') }}<'),
        ('>Aufgabe zuweisen:<', '>{{ $t(\'journal.aufgabe_zuweisen_label\') }}<'),
        ('>-- Aufgabe auswählen --<', '>{{ $t(\'journal.aufgabe_auswaehlen_opt\') }}<'),
    ]
    for old, new in j_reps:
        j_content = j_content.replace(old, new)
    if j_content != orig_j:
        j_path.write_text(j_content, encoding='utf-8')
        print("[OK] pages/journal.vue details localized!")

    # 3. components/JournalEntryModal.vue
    m_path = ROOT / "components" / "JournalEntryModal.vue"
    m_content = m_path.read_text(encoding='utf-8')
    orig_m = m_content
    m_content = m_content.replace(
        "Kontakt auswählen... ({{ contactOptions.length }})",
        "{{ $t('journal.kontakt_waehlen_opt') }} ({{ contactOptions.length }})"
    )
    if m_content != orig_m:
        m_path.write_text(m_content, encoding='utf-8')
        print("[OK] components/JournalEntryModal.vue localized!")

    # 4. components/JournalNoteModal.vue
    n_path = ROOT / "components" / "JournalNoteModal.vue"
    n_content = n_path.read_text(encoding='utf-8')
    orig_n = n_content
    n_content = n_content.replace(
        ">📝 Notiz</option>",
        ">{{ $t('journal.kat_notiz') }}</option>"
    )
    if n_content != orig_n:
        n_path.write_text(n_content, encoding='utf-8')
        print("[OK] components/JournalNoteModal.vue localized!")

    # 5. pages/calendar/index.vue
    cal_path = ROOT / "pages" / "calendar" / "index.vue"
    cal_content = cal_path.read_text(encoding='utf-8')
    orig_cal = cal_content
    cal_content = cal_content.replace('title="Bearbeiten"', ':title="$t(\'common.bearbeiten\')"')
    if cal_content != orig_cal:
        cal_path.write_text(cal_content, encoding='utf-8')
        print("[OK] pages/calendar/index.vue localized!")

    # 6. pages/contacts/index.vue
    cnt_path = ROOT / "pages" / "contacts" / "index.vue"
    cnt_content = cnt_path.read_text(encoding='utf-8')
    orig_cnt = cnt_content
    cnt_content = cnt_content.replace('title="Bearbeiten"', ':title="$t(\'common.bearbeiten\')"')
    if cnt_content != orig_cnt:
        cnt_path.write_text(cnt_content, encoding='utf-8')
        print("[OK] pages/contacts/index.vue localized!")

    # 7. pages/forgot-password.vue
    fp_path = ROOT / "pages" / "forgot-password.vue"
    fp_content = fp_path.read_text(encoding='utf-8')
    orig_fp = fp_content
    fp_content = fp_content.replace('>Passwort vergessen?<', '>{{ $t(\'auth.passwort_vergessen\') }}<')
    fp_content = fp_content.replace('>E-Mail gesendet<', '>{{ $t(\'auth.email_gesendet\') }}<')
    if fp_content != orig_fp:
        fp_path.write_text(fp_content, encoding='utf-8')
        print("[OK] pages/forgot-password.vue localized!")

if __name__ == '__main__':
    main()

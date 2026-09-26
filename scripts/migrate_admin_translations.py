#!/usr/bin/env python3
"""
Taskster i18n – Admin Portal Migration Pass 2
Localizes remaining strings in pages/admin/index.vue into German, English, and Slovak.
"""
import json, re, sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
LOCALES = ROOT / "i18n" / "locales"

EXTRA_ADMIN_TRANSLATIONS = [
    ("admin.suche_placeholder", "Suche (Aktion, User, IP...)", "Search (Action, user, IP...)", "Hľadať (akcia, používateľ, IP...)"),
    ("admin.vorlage_suchen_placeholder", "Vorlage suchen...", "Search template...", "Hľadať šablónu..."),
    ("admin.smtp_passwort_placeholder", "SMTP Kennwort", "SMTP password", "Heslo SMTP"),
    ("admin.user_edit_title", "Benutzer-Einstellungen bearbeiten (Plan, Rolle, Subrollen, Firma)", "Edit user settings (Plan, role, sub-roles, company)", "Upraviť nastavenia používateľa (plán, rola, podradené roly, firma)"),
    ("admin.th_rolle_rechte", "Rolle & Berechtigungen", "Role & Permissions", "Rola a oprávnenia"),
    ("admin.privatkunde_einzel", "Privatkunde (Einzelbenutzer)", "Private customer (Individual user)", "Súkromný zákazník (Jednotlivec)"),
    ("admin.unternehmen_b2b_titel", "Unternehmen, Mandanten & B2B-Kunden", "Companies, Tenants & B2B Customers", "Spoločnosti, mandanti a B2B zákazníci"),
    ("admin.unternehmen_b2b_desc", "Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien für Firmen.", "Manage subscription plans, upload restrictions, and security policies for companies.", "Spravujte plány predplatného, obmedzenia nahrávania a bezpečnostné pravidlá pre firmy."),
    ("admin.nutzer_ordner", "Nutzer & Ordner", "Users & Folders", "Používatelia a priečinky"),
    ("admin.dateiuploads_zerotrust", "Dateiuploads (Zero Trust)", "File uploads (Zero Trust)", "Nahrávanie súborov (Zero Trust)"),
    ("admin.starter_plan", "Starter Plan", "Starter Plan", "Starter plán"),
    ("admin.pro_plan", "Pro Plan", "Pro Plan", "Pro plán"),
    ("admin.enterprise_plan", "Enterprise Plan", "Enterprise Plan", "Enterprise plán"),
    ("admin.abos_bestellungen", "Abonnements & Bestellungen", "Subscriptions & Orders", "Predplatné a objednávky"),
    ("admin.kunde_org", "Kunde / Organisation", "Customer / Organization", "Zákazník / Organizácia"),
    ("admin.plan_tarif", "Plan / Tarif", "Plan / Tier", "Plán / Tarifa"),
    ("admin.pro_monat", "/ Monat", "/ Month", "/ Mesiac"),
    ("admin.mitarbeiter_einladen_titel", "Mitarbeiter einladen", "Invite employee", "Pozvať zamestnanca"),
    ("admin.rolle_im_unternehmen", "Rolle im Unternehmen", "Role in company", "Rola v spoločnosti"),
    ("admin.mitglied_member", "Mitglied (Member)", "Member (Member)", "Člen (Member)"),
    ("admin.company_admin", "Company Administrator", "Company Administrator", "Správca spoločnosti"),
    ("admin.einladung_generiert", "Einladung erfolgreich generiert!", "Invitation successfully generated!", "Pozvánka bola úspešne vygenerovaná!"),
    ("admin.einladung_link_desc", "Für nicht registrierte Nutzer kann dieser direkte Registrierungslink weitergegeben werden:", "For unregistered users, this direct registration link can be shared:", "Pre neregistrovaných používateľov je možné odovzdať tento priamy registračný odkaz:"),
    ("admin.offene_einladungen", "Offene Einladungen", "Open invitations", "Otvorené pozvánky"),
    ("admin.projektvorlagen_full_title", "Projekt-Vorlagen (Gewerbe, Jobs & Privat)", "Project Templates (Commercial, Jobs & Private)", "Šablóny projektov (Podnikanie, práca a súkromné)"),
    ("admin.neue_vorlage_erstellen_btn", "+ Neue Vorlage erstellen", "+ Create new template", "+ Vytvoriť novú šablónu"),
    ("admin.keine_vorlagen", "Keine Vorlagen gefunden", "No templates found", "Nenašli sa žiadne šablóny"),
    ("admin.keine_vorlagen_desc", "Erstelle deine erste Vorlage oder passe den Suchfilter an.", "Create your first template or adjust search filter.", "Vytvorte svoju prvú šablónu alebo upravte filter vyhľadávania."),
    ("admin.vordefinierte_abschnitte", "Vordefinierte Abschnitte", "Predefined sections", "Preddefinované sekcie"),
    ("admin.benutzerdefinierte_felder", "Benutzerdefinierte Felder", "Custom fields", "Vlastné polia"),
    ("admin.email_versand_titel", "E-Mail & Versand (Resend / SMTP)", "Email & Delivery (Resend / SMTP)", "E-mail a odosielanie (Resend / SMTP)"),
    ("admin.test_email_senden_btn", "Test-E-Mail senden", "Send test email", "Odoslať testovací e-mail"),
    ("admin.zentrale_email_einstellungen", "Zentrale E-Mail-Einstellungen", "Central Email Settings", "Centrálne nastavenia e-mailu"),
    ("admin.email_versand_methode", "E-Mail Versand-Methode", "Email delivery method", "Spôsob odosielania e-mailov"),
    ("admin.resend_api", "Resend API", "Resend API", "Resend API"),
    ("admin.empfohlen_aktiv", "Empfohlen & Aktiv", "Recommended & Active", "Odporúčané a aktívne"),
    ("admin.eigener_smtp", "Eigener SMTP-Server", "Custom SMTP server", "Vlastný SMTP server"),
    ("admin.resend_konfig", "Resend Konfiguration", "Resend configuration", "Konfigurácia Resend"),
    ("admin.domain_verifiziert", "Domain kurka.ch verifiziert", "Domain kurka.ch verified", "Doména kurka.ch overená"),
    ("admin.resend_key_label", "Resend API-Key *", "Resend API Key *", "API kľúč Resend *"),
    ("admin.from_email_label", "Absender-E-Mail (From Address) *", "Sender email (From Address) *", "E-mail odosielateľa (From Address) *"),
    ("admin.from_email_hint", "Muss eine Adresse der verifizierten Domain kurka.ch sein.", "Must be an address of the verified domain kurka.ch.", "Musí byť adresa z overenej domény kurka.ch."),
    ("admin.from_name_label", "Absender-Name (From Name) *", "Sender name (From Name) *", "Meno odosielateľa (From Name) *"),
    ("admin.dedizierte_identitaeten", "Dedizierte Absender-Identitäten (@kurka.ch)", "Dedicated sender identities (@kurka.ch)", "Vyhradené identity odosielateľa (@kurka.ch)"),
    ("admin.manuelle_smtp_konfig", "Manuelle SMTP-Server Konfiguration", "Manual SMTP Server Configuration", "Manuálna konfigurácia SMTP servera"),
    ("admin.smtp_host_label", "SMTP Host / Server *", "SMTP Host / Server *", "SMTP Hostiteľ / Server *"),
    ("admin.smtp_port_enc_label", "Port & Verschlüsselung *", "Port & Encryption *", "Port a šifrovanie *"),
    ("admin.enc_ssl_tls", "SSL / TLS (Port 465)", "SSL / TLS (Port 465)", "SSL / TLS (Port 465)"),
    ("admin.enc_starttls", "STARTTLS (Port 587)", "STARTTLS (Port 587)", "STARTTLS (Port 587)"),
    ("admin.enc_none", "Keine Verschlüsselung (Port 25)", "No encryption (Port 25)", "Bez šifrovania (Port 25)"),
    ("admin.smtp_user_label", "SMTP Benutzername *", "SMTP Username *", "Používateľské meno SMTP *"),
    ("admin.smtp_pass_label", "SMTP Passwort *", "SMTP Password *", "Heslo SMTP *"),
    ("admin.passwort_verbergen", "Passwort verbergen", "Hide password", "Skryť heslo"),
    ("admin.passwort_anzeigen", "Passwort anzeigen", "Show password", "Zobraziť heslo"),
    ("admin.einstellungen_speichern_btn", "Einstellungen speichern", "Save settings", "Uložiť nastavenia"),
    ("admin.wird_gespeichert", "Wird gespeichert...", "Saving...", "Ukladá sa..."),
    ("admin.email_trigger_vorlagen", "E-Mail Trigger & Vorlagen", "Email Triggers & Templates", "Spúšťače e-mailov a šablóny"),
    ("admin.verfuegbare_variablen", "Verfügbare Variablen", "Available variables", "Dostupné premenné"),
    ("admin.email_outbox_titel", "E-Mail Versand-Protokoll (Outbox)", "Email Delivery Log (Outbox)", "Protokol odosielania e-mailov (Outbox)"),
    ("admin.email_outbox_desc", "Verlauf der letzten E-Mail-Sendungen und Status.", "History of recent email dispatches and status.", "História posledných odoslaných e-mailov a stav."),
    ("admin.th_empfaenger", "Empfänger", "Recipient", "Príjemca"),
    ("admin.th_gesendet_fehler", "Gesendet am / Fehler", "Sent on / Error", "Odoslané dňa / Chyba"),
    ("admin.noch_keine_emails_log", "Noch keine E-Mails im Protokoll vorhanden.", "No emails in log yet.", "V protokole zatiaľ nie sú žiadne e-maily."),
    ("admin.audit_trail_titel", "Sicherheits- & Revisionsprotokoll (Audit Trail)", "Security & Audit Trail", "Bezpečnostný a revízny protokol (Audit Trail)"),
    ("admin.audit_trail_desc", "Vollständige Aufzeichnung aller Benutzeraktionen, Berechtigungsänderungen und Systemereignisse.", "Full record of all user actions, permission changes, and system events.", "Kompletný záznam všetkých akcií používateľov, zmien oprávnení a systémových udalostí."),
    ("admin.bestaetigung_erforderlich", "Bestätigung erforderlich", "Confirmation required", "Vyžaduje sa potvrdenie"),
]

def load_json(p):
    with open(p, 'r', encoding='utf-8') as f: return json.load(f)

def save_json(p, d):
    with open(p, 'w', encoding='utf-8') as f: json.dump(d, f, ensure_ascii=False, indent=2)

def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')
    print("=== Admin i18n Migration Pass 2 ===")
    
    de = load_json(LOCALES / "de.json")
    en = load_json(LOCALES / "en.json")
    sk = load_json(LOCALES / "sk.json")
    added = 0
    for key, de_val, en_val, sk_val in EXTRA_ADMIN_TRANSLATIONS:
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

    admin_path = ROOT / "pages" / "admin" / "index.vue"
    content = admin_path.read_text(encoding='utf-8')
    orig = content

    replacements = [
        ('placeholder="Vorlage suchen..."', ':placeholder="$t(\'admin.vorlage_suchen_placeholder\')"'),
        ('placeholder="SMTP Kennwort"', ':placeholder="$t(\'admin.smtp_passwort_placeholder\')"'),
        ('placeholder="Suche (Aktion, User, IP...)"', ':placeholder="$t(\'admin.suche_placeholder\')"'),
        ('title="Benutzer-Einstellungen bearbeiten (Plan, Rolle, Subrollen, Firma)"', ':title="$t(\'admin.user_edit_title\')"'),
        ('>Rolle &amp; Berechtigungen<', '>{{ $t(\'admin.th_rolle_rechte\') }}<'),
        ('>Rolle & Berechtigungen<', '>{{ $t(\'admin.th_rolle_rechte\') }}<'),
        ('>Privatkunde (Einzelbenutzer)<', '>{{ $t(\'admin.privatkunde_einzel\') }}<'),
        ('>Unternehmen, Mandanten &amp; B2B-Kunden<', '>{{ $t(\'admin.unternehmen_b2b_titel\') }}<'),
        ('>Unternehmen, Mandanten & B2B-Kunden<', '>{{ $t(\'admin.unternehmen_b2b_titel\') }}<'),
        ('Verwalte Subscription-Pläne, Upload-Restriktionen und Sicherheitsrichtlinien für Firmen.', '{{ $t(\'admin.unternehmen_b2b_desc\') }}'),
        ('>Nutzer &amp; Ordner<', '>{{ $t(\'admin.nutzer_ordner\') }}<'),
        ('>Nutzer & Ordner<', '>{{ $t(\'admin.nutzer_ordner\') }}<'),
        ('>Dateiuploads (Zero Trust)<', '>{{ $t(\'admin.dateiuploads_zerotrust\') }}<'),
        ('>Starter Plan<', '>{{ $t(\'admin.starter_plan\') }}<'),
        ('>Pro Plan<', '>{{ $t(\'admin.pro_plan\') }}<'),
        ('>Enterprise Plan<', '>{{ $t(\'admin.enterprise_plan\') }}<'),
        ('>Abonnements &amp; Bestellungen<', '>{{ $t(\'admin.abos_bestellungen\') }}<'),
        ('>Abonnements & Bestellungen<', '>{{ $t(\'admin.abos_bestellungen\') }}<'),
        ('>Kunde / Organisation<', '>{{ $t(\'admin.kunde_org\') }}<'),
        ('>Plan / Tarif<', '>{{ $t(\'admin.plan_tarif\') }}<'),
        ('> / Monat<', '> {{ $t(\'admin.pro_monat\') }}<'),
        ('>Rolle im Unternehmen<', '>{{ $t(\'admin.rolle_im_unternehmen\') }}<'),
        ('>Mitglied (Member)<', '>{{ $t(\'admin.mitglied_member\') }}<'),
        ('>Company Administrator<', '>{{ $t(\'admin.company_admin\') }}<'),
        ('>Einladung erfolgreich generiert!<', '>{{ $t(\'admin.einladung_generiert\') }}<'),
        ('Für nicht registrierte Nutzer kann dieser direkte Registrierungslink weitergegeben werden:', '{{ $t(\'admin.einladung_link_desc\') }}'),
        ('>Offene Einladungen<', '>{{ $t(\'admin.offene_einladungen\') }}<'),
        ('>Projekt-Vorlagen (Gewerbe, Jobs &amp; Privat)<', '>{{ $t(\'admin.projektvorlagen_full_title\') }}<'),
        ('>Projekt-Vorlagen (Gewerbe, Jobs & Privat)<', '>{{ $t(\'admin.projektvorlagen_full_title\') }}<'),
        ('>+ Neue Vorlage erstellen<', '>{{ $t(\'admin.neue_vorlage_erstellen_btn\') }}<'),
        ('>Keine Vorlagen gefunden<', '>{{ $t(\'admin.keine_vorlagen\') }}<'),
        ('Erstelle deine erste Vorlage oder passe den Suchfilter an.', '{{ $t(\'admin.keine_vorlagen_desc\') }}'),
        ('Vordefinierte Abschnitte ({{ tmpl.lists?.length || 0 }})', '{{ $t(\'admin.vordefinierte_abschnitte\') }} ({{ tmpl.lists?.length || 0 }})'),
        ('Benutzerdefinierte Felder ({{ tmpl.fields?.length || 0 }})', '{{ $t(\'admin.benutzerdefinierte_felder\') }} ({{ tmpl.fields?.length || 0 }})'),
        ('>E-Mail &amp; Versand (Resend / SMTP)<', '>{{ $t(\'admin.email_versand_titel\') }}<'),
        ('>E-Mail & Versand (Resend / SMTP)<', '>{{ $t(\'admin.email_versand_titel\') }}<'),
        ('>Test-E-Mail senden<', '>{{ $t(\'admin.test_email_senden_btn\') }}<'),
        ('>Zentrale E-Mail-Einstellungen<', '>{{ $t(\'admin.zentrale_email_einstellungen\') }}<'),
        ('>E-Mail Versand-Methode<', '>{{ $t(\'admin.email_versand_methode\') }}<'),
        ('>Resend API<', '>{{ $t(\'admin.resend_api\') }}<'),
        ('>Empfohlen &amp; Aktiv<', '>{{ $t(\'admin.empfohlen_aktiv\') }}<'),
        ('>Empfohlen & Aktiv<', '>{{ $t(\'admin.empfohlen_aktiv\') }}<'),
        ('>Eigener SMTP-Server<', '>{{ $t(\'admin.eigener_smtp\') }}<'),
        ('>Resend Konfiguration<', '>{{ $t(\'admin.resend_konfig\') }}<'),
        ('>Domain kurka.ch verifiziert<', '>{{ $t(\'admin.domain_verifiziert\') }}<'),
        ('>Resend API-Key *<', '>{{ $t(\'admin.resend_key_label\') }}<'),
        ('>Absender-E-Mail (From Address) *<', '>{{ $t(\'admin.from_email_label\') }}<'),
        ('Muss eine Adresse der verifizierten Domain kurka.ch sein.', '{{ $t(\'admin.from_email_hint\') }}'),
        ('>Absender-Name (From Name) *<', '>{{ $t(\'admin.from_name_label\') }}<'),
        ('>Dedizierte Absender-Identitäten (@kurka.ch)<', '>{{ $t(\'admin.dedizierte_identitaeten\') }}<'),
        ('>Manuelle SMTP-Server Konfiguration<', '>{{ $t(\'admin.manuelle_smtp_konfig\') }}<'),
        ('>SMTP Host / Server *<', '>{{ $t(\'admin.smtp_host_label\') }}<'),
        ('>Port &amp; Verschlüsselung *<', '>{{ $t(\'admin.smtp_port_enc_label\') }}<'),
        ('>Port & Verschlüsselung *<', '>{{ $t(\'admin.smtp_port_enc_label\') }}<'),
        ('>SSL / TLS (Port 465)<', '>{{ $t(\'admin.enc_ssl_tls\') }}<'),
        ('>STARTTLS (Port 587)<', '>{{ $t(\'admin.enc_starttls\') }}<'),
        ('>Keine Verschlüsselung (Port 25)<', '>{{ $t(\'admin.enc_none\') }}<'),
        ('>SMTP Benutzername *<', '>{{ $t(\'admin.smtp_user_label\') }}<'),
        ('>SMTP Passwort *<', '>{{ $t(\'admin.smtp_pass_label\') }}<'),
        ('>Passwort verbergen<', '>{{ $t(\'admin.passwort_verbergen\') }}<'),
        ('>Passwort anzeigen<', '>{{ $t(\'admin.passwort_anzeigen\') }}<'),
        ('>Wird gespeichert...<', '>{{ $t(\'admin.wird_gespeichert\') }}<'),
        ('>Einstellungen speichern<', '>{{ $t(\'admin.einstellungen_speichern_btn\') }}<'),
        ('>E-Mail Trigger &amp; Vorlagen<', '>{{ $t(\'admin.email_trigger_vorlagen\') }}<'),
        ('>E-Mail Trigger & Vorlagen<', '>{{ $t(\'admin.email_trigger_vorlagen\') }}<'),
        ('>Verfügbare Variablen<', '>{{ $t(\'admin.verfuegbare_variablen\') }}<'),
        ('>E-Mail Versand-Protokoll (Outbox)<', '>{{ $t(\'admin.email_outbox_titel\') }}<'),
        ('Verlauf der letzten E-Mail-Sendungen und Status.', '{{ $t(\'admin.email_outbox_desc\') }}'),
        ('>Empfänger<', '>{{ $t(\'admin.th_empfaenger\') }}<'),
        ('>Gesendet am / Fehler<', '>{{ $t(\'admin.th_gesendet_fehler\') }}<'),
        ('>Noch keine E-Mails im Protokoll vorhanden.<', '>{{ $t(\'admin.noch_keine_emails_log\') }}<'),
        ('>Sicherheits- &amp; Revisionsprotokoll (Audit Trail)<', '>{{ $t(\'admin.audit_trail_titel\') }}<'),
        ('>Sicherheits- & Revisionsprotokoll (Audit Trail)<', '>{{ $t(\'admin.audit_trail_desc\') }}<'),
        ('Vollständige Aufzeichnung aller Benutzeraktionen, Berechtigungsänderungen und Systemereignisse.', '{{ $t(\'admin.audit_trail_desc\') }}'),
        ("'Bestätigung erforderlich'", "t('admin.bestaetigung_erforderlich')"),
    ]

    for old, new in replacements:
        content = content.replace(old, new)

    if content != orig:
        admin_path.write_text(content, encoding='utf-8')
        print("[OK] pages/admin/index.vue successfully updated (pass 2)!")
    else:
        print("[WARN] No changes in pass 2")

if __name__ == '__main__':
    main()

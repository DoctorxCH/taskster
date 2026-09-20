# 2026-09-21 – settings.vue i18n: 27 fehlende Locale-Keys ergänzt

## Betroffene Dateien
- `i18n/locales/de.json`
- `i18n/locales/en.json`
- `i18n/locales/sk.json`

## Ursache
`pages/settings.vue` referenzierte 221 `$t()`-Keys. 27 davon fehlten in allen drei Sprachen (de/en/sk), wodurch Vue-i18n die Rohschlüssel als Text darstellte.

## Ergänzte Keys

### common.*
- `common.aktionen`
- `common.benutzer`
- `common.fertig`
- `common.ordner`
- `common.projekt`
- `common.status`

### settings.* – Datenschutz/DSGVO-Sektion
- `settings.datenschutz_dsgvo`
- `settings.datenübertragbarkeit_art_20`
- `settings.datenspeicherung_sicherheit`
- `settings.hosting_standort`
- `settings.verschlüsselung`
- `settings.aufbewahrungsfristen`
- `settings.recht_auf_loeschung_art_17`
- `settings.rechenzentrum_hostcreators_ch_eu`
- `settings.tls_13_in_transit_bcrypt_fuer_pa`
- `settings.zeiterfassungen_10_jahre_geset`

### settings.* – Export / Account
- `settings.daten_als_json_exportieren`
- `settings.export_wird_erstellt`
- `settings.account_endgueltig_loeschen`
- `settings.loescht_dein_konto_und_alle_zuge`
- `settings.du_hast_das_recht_auf_eine_kopie_a`

### settings.* – Team/Gruppen
- `settings.team_und_gruppen`
- `settings.mitglieder`
- `settings.noch_keine_mitglieder_zugeordnet`
- `settings.zugewiesene_rechte`
- `settings.zugriffsübersicht`
- `settings.gruppe_löschen`

## Verifikation
`python scratch_check_locale.py` → 0 fehlende Keys in de/en/sk.

## Deploy
`npm run build:dist` → Build OK → `git push origin main` (Commit `b17aae2`)

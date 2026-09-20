import re, json

# settings.vue Keys extrahieren
with open('pages/settings.vue', 'r', encoding='utf-8') as f:
    content = f.read()

keys = set(re.findall(r"\$t\('([^']+)'\)", content))
print(f'Keys in settings.vue: {len(keys)}')

# Locale-Dateien laden
locales = {}
for lang in ['de', 'en', 'sk']:
    with open(f'i18n/locales/{lang}.json', 'r', encoding='utf-8') as f:
        locales[lang] = set(json.load(f).keys())

# Fehlende Keys pro Sprache
for lang in ['de', 'en', 'sk']:
    missing = sorted(k for k in keys if k not in locales[lang])
    print(f'\n=== FEHLEND in {lang}.json ({len(missing)}) ===')
    for k in missing:
        print(f'  {k}')

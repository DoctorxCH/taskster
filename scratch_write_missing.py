import re, json

with open('pages/settings.vue', 'r', encoding='utf-8') as f:
    content = f.read()

keys = set(re.findall(r"\$t\('([^']+)'\)", content))

with open('i18n/locales/de.json', 'r', encoding='utf-8') as f:
    de = set(json.load(f).keys())

missing = sorted(k for k in keys if k not in de)
with open('scratch_missing_keys.txt', 'w', encoding='utf-8') as f:
    for k in missing:
        f.write(k + '\n')
print(f"Geschrieben: {len(missing)} Keys")

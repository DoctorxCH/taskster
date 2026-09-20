import re, json

with open('pages/settings.vue', 'r', encoding='utf-8') as f:
    content = f.read()

keys = set(re.findall(r"\$t\('([^']+)'\)", content))

with open('i18n/locales/de.json', 'r', encoding='utf-8') as f:
    de = set(json.load(f).keys())

missing = sorted(k for k in keys if k not in de)
print("FEHLEND in de.json:")
for k in missing:
    print(f"  {repr(k)}")

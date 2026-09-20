import re, json

with open('pages/settings.vue', 'r', encoding='utf-8') as f:
    content = f.read()

keys = set(re.findall(r"\$t\('([^']+)'\)", content))
# Zeige Keys mit Sonderzeichen
for k in sorted(keys):
    if any(ord(c) > 127 for c in k):
        print(repr(k))

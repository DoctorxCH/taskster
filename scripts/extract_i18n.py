#!/usr/bin/env python3
"""
Taskster i18n String Extractor
==============================
Finds and extracts hardcoded UI strings (German text, placeholders, labels,
alerts, titles) across Vue components, pages, and TypeScript/JavaScript files
to prepare localization / multi-language support (Mehrsprachigkeit).

Usage:
  python scripts/extract_i18n.py
  python scripts/extract_i18n.py --stats
  python scripts/extract_i18n.py --output locales/de.json --report i18n_report.md
"""

import os
import sys
import re
import json
import argparse
from pathlib import Path
from collections import defaultdict

# Directories to include in scan
SCAN_DIRS = [
    'pages',
    'components',
    'layouts',
    'composables',
    'app.vue'
]

# Directories to ignore
IGNORE_DIRS = {
    'node_modules',
    '.git',
    '.nuxt',
    '.output',
    'dist',
    'public',
    '_nuxt',
    '.agents'
}

# Regex to detect German special characters or common German words
GERMAN_CHARS_RE = re.compile(r'[äöüÄÖÜß]')
GERMAN_WORDS_RE = re.compile(
    r'\b(und|oder|für|mit|ohne|nicht|bitte|erfolgreich|fehler|aufgabe|projekt|'
    r'speichern|abbrechen|löschen|schliessen|erstellen|ändern|bearbeiten|'
    r'hinzufügen|auswählen|suchen|filtern|zurück|weiter|anmelden|abmelden|'
    r'einstellungen|benutzer|stunden|dauer|aufwand|datum|notiz|zeit|erfassen|'
    r'neu|fertig|in arbeit|überfällig|status|priorität|keine|kein|alle|mehr|weniger)\b',
    re.IGNORECASE
)

# Common non-translatable patterns to filter out
CODE_PATTERNS = [
    re.compile(r'^[a-zA-Z0-9_\-\.\/]+$'), # single identifiers, file paths, slugs, css classes
    re.compile(r'^#[0-9a-fA-F]{3,8}$'), # Hex colors
    re.compile(r'^(https?:\/\/|\/api\/|\/)[^\s]*$'), # URLs and API paths
    re.compile(r'^[0-9\s\.,:\-+/*%€$CHF]+$'), # numbers, currency symbols, pure math
    re.compile(r'^[MmLlHhVvCcSsQqTtAaZz0-9\s,\.\-]+$'), # SVG path data
    re.compile(r'^(px|rem|em|vh|vw|ms|s|deg|fr|%)'), # CSS units
    re.compile(r'^[a-z]+-[a-z0-9\-]+$'), # Tailwind / kebab-case classes
    re.compile(r'^[A-Z_0-9]+$'), # UPPERCASE CONSTANTS
    re.compile(r'^(true|false|null|undefined|NaN)$'),
    re.compile(r'^[a-z]+:[a-z0-9\-]+$'), # namespaced tags or icons
    re.compile(r'^(SELECT|INSERT|UPDATE|DELETE|FROM|WHERE|JOIN)\b', re.IGNORECASE), # SQL
]

# Attribute names that usually contain user-facing text
TRANSLATABLE_ATTRS = ['placeholder', 'title', 'aria-label', 'label', 'alt']


def is_valid_ui_text(text: str) -> bool:
    """Checks if a string looks like human-readable UI text rather than code."""
    if not text:
        return False
    
    t = text.strip()
    if len(t) < 2 or len(t) > 300:
        return False

    # Ignore pure symbols / whitespace
    if re.fullmatch(r'[\s\d\W_]+', t) and not any(c.isalnum() for c in t):
        return False

    # Check against code patterns
    for pat in CODE_PATTERNS:
        if pat.fullmatch(t):
            # If it's pure ASCII and matches code pattern without spaces, reject
            if ' ' not in t and not GERMAN_CHARS_RE.search(t):
                return False

    # Contains spaces and words -> likely UI text
    if ' ' in t:
        words = t.split()
        if len(words) >= 1:
            # Check if majority of characters are normal text
            alpha_count = sum(1 for c in t if c.isalpha() or c in 'äöüÄÖÜß')
            if alpha_count >= 2:
                return True

    # Single word: check if capitalized or contains German chars
    if t[0].isupper() and len(t) >= 3 and not t.isupper():
        return True

    if GERMAN_CHARS_RE.search(t) or GERMAN_WORDS_RE.search(t):
        return True

    return False


def clean_text(text: str) -> str:
    """Cleans up text extracted from templates or scripts."""
    # Remove Vue interpolations {{ ... }}
    cleaned = re.sub(r'\{\{.*?\}\}', ' ', text)
    # Collapse multiple whitespace/newlines
    cleaned = re.sub(r'\s+', ' ', cleaned).strip()
    return cleaned


def slugify_key(category: str, text: str) -> str:
    """Generates a clean translation key from text."""
    # Strip emojis and special chars
    clean = re.sub(r'[^\w\s]', '', text).strip().lower()
    clean = re.sub(r'\s+', '_', clean)[:35]
    if not clean:
        clean = "msg"
    return f"{category}.{clean}"


class I18nExtractor:
    def __init__(self, root_dir: str):
        self.root_dir = Path(root_dir).resolve()
        self.results = [] # list of dicts: {file, line, text, context, type}
        self.unique_texts = defaultdict(list) # text -> list of locations

    def scan_project(self):
        """Scans the configured project directories."""
        for target in SCAN_DIRS:
            target_path = self.root_dir / target
            if not target_path.exists():
                continue
            if target_path.is_file():
                self.scan_file(target_path)
            else:
                for file_path in target_path.rglob('*'):
                    if file_path.is_file() and file_path.suffix in ['.vue', '.ts', '.js']:
                        # Check if inside ignore dirs
                        if any(ignored in file_path.parts for ignored in IGNORE_DIRS):
                            continue
                        self.scan_file(file_path)

    def scan_file(self, file_path: Path):
        """Scans an individual file for hardcoded strings."""
        rel_path = file_path.relative_to(self.root_dir).as_posix()
        try:
            content = file_path.read_text(encoding='utf-8')
        except Exception:
            return

        lines = content.splitlines()

        if file_path.suffix == '.vue':
            self.scan_vue_file(rel_path, content, lines)
        else:
            self.scan_script_content(rel_path, content, lines, offset_line=1)

    def scan_vue_file(self, rel_path: str, content: str, lines: list):
        """Extracts strings from Vue template and script tags."""
        # 1. Template section
        template_match = re.search(r'<template>(.*)</template>', content, re.DOTALL)
        if template_match:
            template_content = template_match.group(1)
            # Find line offset
            template_start_pos = template_match.start()
            start_line = content[:template_start_pos].count('\n') + 1
            self.scan_template_content(rel_path, template_content, start_line)

        # 2. Script sections
        for script_match in re.finditer(r'<script.*?>\n?(.*?)</script>', content, re.DOTALL):
            script_content = script_match.group(1)
            start_line = content[:script_match.start(1)].count('\n') + 1
            self.scan_script_content(rel_path, script_content, lines, offset_line=start_line)

    def scan_template_content(self, rel_path: str, template_content: str, start_line: int):
        """Parses Vue template using robust tag/text tokenization."""
        tokens = self.tokenize_template(template_content)

        for token_type, content, pos in tokens:
            line_num = start_line + template_content[:pos].count('\n')
            
            if token_type == 'tag':
                # Check for translatable literal attributes (e.g. placeholder="...", title="...")
                # Ensure attribute is NOT dynamic (e.g. :placeholder or v-bind:placeholder)
                for attr in TRANSLATABLE_ATTRS:
                    # Matches placeholder="Text" or title='Text', not :title= or @title=
                    attr_pat = re.compile(rf'(?<![:@\w\-])\b{attr}=(["\'])(.*?)\1', re.IGNORECASE)
                    for m in attr_pat.finditer(content):
                        val = m.group(2).strip()
                        if is_valid_ui_text(val):
                            attr_line = line_num + content[:m.start()].count('\n')
                            self.add_result(rel_path, attr_line, val, f"attribute:{attr}", "template_attr")

            elif token_type == 'text':
                cleaned = clean_text(content)
                if is_valid_ui_text(cleaned):
                    self.add_result(rel_path, line_num, cleaned, "tag_content", "template_text")

    def tokenize_template(self, template_str: str) -> list:
        """Tokenizes template into tags and text, ignoring '>' inside attribute quotes."""
        pos = 0
        length = len(template_str)
        tokens = []

        while pos < length:
            if template_str[pos] == '<':
                # Comments <!-- ... -->
                if template_str.startswith('<!--', pos):
                    end_comment = template_str.find('-->', pos)
                    if end_comment == -1:
                        break
                    pos = end_comment + 3
                    continue

                # HTML / Vue Tag
                tag_start = pos
                pos += 1
                in_quote = None
                while pos < length:
                    ch = template_str[pos]
                    if in_quote:
                        if ch == in_quote and template_str[pos - 1] != '\\':
                            in_quote = None
                    else:
                        if ch in ('"', "'"):
                            in_quote = ch
                        elif ch == '>':
                            pos += 1
                            break
                    pos += 1
                tag_content = template_str[tag_start:pos]
                tokens.append(('tag', tag_content, tag_start))
            else:
                # Text node until next '<'
                text_start = pos
                next_tag = template_str.find('<', pos)
                if next_tag == -1:
                    text_content = template_str[text_start:]
                    pos = length
                else:
                    text_content = template_str[text_start:next_tag]
                    pos = next_tag
                if text_content.strip():
                    tokens.append(('text', text_content, text_start))

        return tokens

    def scan_script_content(self, rel_path: str, script_content: str, all_lines: list, offset_line: int = 1):
        """Extracts user-facing strings from script/TypeScript code."""
        # 1. alert('...'), confirm('...'), prompt('...')
        dialog_pat = re.compile(r'\b(alert|confirm|prompt)\s*\(\s*(["\'`])(.*?)\2\s*\)', re.DOTALL)
        for m in dialog_pat.finditer(script_content):
            val = m.group(3).strip()
            if is_valid_ui_text(val):
                line_num = offset_line + script_content[:m.start()].count('\n')
                self.add_result(rel_path, line_num, val, f"dialog:{m.group(1)}", "script_dialog")

        # 2. statusMessage / toast / error literal strings
        msg_pat = re.compile(r'(?:statusMessage|errorMessage|message|error|title|label)\s*:\s*(["\'`])(.*?)\1')
        for m in msg_pat.finditer(script_content):
            val = m.group(2).strip()
            if is_valid_ui_text(val):
                line_num = offset_line + script_content[:m.start()].count('\n')
                self.add_result(rel_path, line_num, val, "property_string", "script_property")

        # 3. String literals containing German characters or keywords in script
        string_literal_pat = re.compile(r'(["\'`])((?:(?!\1)[^\\]|\\.)*)\1')
        for m in string_literal_pat.finditer(script_content):
            val = m.group(2).strip()
            # Must contain German characters or recognized words to avoid grabbing code constants
            if (GERMAN_CHARS_RE.search(val) or GERMAN_WORDS_RE.search(val)) and is_valid_ui_text(val):
                line_num = offset_line + script_content[:m.start()].count('\n')
                # Avoid duplicates from alert/property patterns already grabbed on same line
                self.add_result(rel_path, line_num, val, "code_literal", "script_literal")

    def add_result(self, file: str, line: int, text: str, context: str, item_type: str):
        # Deduplicate same file, same line, same text
        for item in self.results:
            if item['file'] == file and item['line'] == line and item['text'] == text:
                return

        entry = {
            'file': file,
            'line': line,
            'text': text,
            'context': context,
            'type': item_type
        }
        self.results.append(entry)
        self.unique_texts[text].append((file, line))

    def export_json(self, output_file: str):
        """Exports unique strings as a structured key-value JSON dictionary for i18n."""
        out_path = Path(output_file)
        out_path.parent.mkdir(parents=True, exist_ok=True)

        i18n_dict = {}
        for text, locations in self.unique_texts.items():
            # Choose category based on file
            primary_file = locations[0][0]
            category = primary_file.replace('pages/', '').replace('components/', '').split('/')[0].split('.')[0]
            if not category or category in ['app', 'index']:
                category = 'common'
            
            key = slugify_key(category, text)
            
            # Ensure unique keys
            orig_key = key
            counter = 1
            while key in i18n_dict and i18n_dict[key] != text:
                key = f"{orig_key}_{counter}"
                counter += 1

            i18n_dict[key] = text

        with open(out_path, 'w', encoding='utf-8') as f:
            json.dump(i18n_dict, f, ensure_ascii=False, indent=2)

        return len(i18n_dict)

    def export_markdown_report(self, report_file: str):
        """Generates a detailed Markdown report categorized by file."""
        rep_path = Path(report_file)
        rep_path.parent.mkdir(parents=True, exist_ok=True)

        files_map = defaultdict(list)
        for r in self.results:
            files_map[r['file']].append(r)

        total_strings = len(self.results)
        unique_count = len(self.unique_texts)

        md = []
        md.append("# Taskster i18n Extrahierte UI-Texte")
        md.append(f"\n**Gesamtanzahl Fundstellen:** {total_strings}  ")
        md.append(f"**Eindeutige Texte:** {unique_count}  ")
        md.append(f"**Gescannte Dateien mit Texten:** {len(files_map)}\n")
        md.append("---")

        for f_path in sorted(files_map.keys()):
            items = files_map[f_path]
            md.append(f"\n### 📄 `{f_path}` ({len(items)} Texte)")
            md.append("| Zeile | Typ | Kontext | Extrahierter Text |")
            md.append("| :--- | :--- | :--- | :--- |")
            for item in sorted(items, key=lambda x: x['line']):
                clean_display = item['text'].replace('|', '\\|').replace('\n', ' ')
                md.append(f"| L{item['line']} | `{item['type']}` | `{item['context']}` | {clean_display} |")

        rep_path.write_text('\n'.join(md), encoding='utf-8')
        return len(files_map)


def main():
    if hasattr(sys.stdout, 'reconfigure'):
        sys.stdout.reconfigure(encoding='utf-8', errors='replace')

    parser = argparse.ArgumentParser(description="Taskster i18n String Extractor")
    parser.add_argument('--output', '-o', default='locales/de.json', help='Pfad fuer die erzeugte de.json')
    parser.add_argument('--report', '-r', default='i18n_report.md', help='Pfad fuer den Markdown-Bericht')
    parser.add_argument('--stats', action='store_true', help='Nur Statistiken ausgeben')
    args = parser.parse_args()

    root = Path(__file__).resolve().parent.parent if 'scripts' in str(Path(__file__).parent) else Path(__file__).resolve().parent
    extractor = I18nExtractor(str(root))
    extractor.scan_project()

    num_keys = extractor.export_json(args.output)
    num_files = extractor.export_markdown_report(args.report)

    print("==================================================")
    print("[i18n] Taskster i18n Extraktion abgeschlossen")
    print("==================================================")
    print(f"  * Gefundene Fundstellen : {len(extractor.results)}")
    print(f"  * Eindeutige UI-Texte   : {len(extractor.unique_texts)}")
    print(f"  * Betroffene Dateien    : {num_files}")
    print(f"  * JSON-Woerterbuch      : {args.output} ({num_keys} Keys)")
    print(f"  * Markdown-Report       : {args.report}")
    print("==================================================")


if __name__ == '__main__':
    main()

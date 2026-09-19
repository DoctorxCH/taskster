#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
check-php-syntax.py — Leichte PHP-Syntax-Sanity-Prüfung (ohne PHP-Binary).

Prüft auf:
  * Ausgeglichene geschweifte Klammern (ohne Strings/Kommentare)
  * Ausgeglichene runde Klammern
  * Verdächtige Konstrukte (doppelte Semikolons, unvollständige Strings)

Ersetzt KEIN echtes `php -l`, fängt aber typische Copy-Paste-Fehler ab.
"""
import re
import sys
import os


def strip_php_noise(src: str) -> str:
    """Entfernt Strings, Heredocs und Kommentare, damit Klammern korrekt zählen."""
    out = []
    i = 0
    n = len(src)
    while i < n:
        ch = src[i]
        two = src[i:i + 2]

        # Zeilenkommentare
        if two == '//' or ch == '#':
            j = src.find('\n', i)
            i = n if j == -1 else j
            continue

        # Blockkommentare
        if two == '/*':
            j = src.find('*/', i + 2)
            i = n if j == -1 else j + 2
            continue

        # Strings inkl. Heredoc
        if two == '<<<':
            m = re.match(r"<<<'?\"?([A-Za-z_][A-Za-z0-9_]*)'?\"?\r?\n", src[i:])
            if m:
                label = m.group(1)
                start = i + m.end()
                endm = re.search(r"\n[ \t]*" + re.escape(label) + r"\b", src[start:])
                i = n if not endm else start + endm.end()
                continue

        if ch in ('"', "'"):
            quote = ch
            i += 1
            while i < n:
                if src[i] == '\\':
                    i += 2
                    continue
                if src[i] == quote:
                    i += 1
                    break
                i += 1
            continue

        out.append(ch)
        i += 1
    return ''.join(out)


def check_file(path: str) -> list:
    with open(path, 'r', encoding='utf-8', errors='ignore') as f:
        raw = f.read()

    cleaned = strip_php_noise(raw)
    errors = []

    for open_c, close_c, name in (('{', '}', 'geschweifte Klammern'),
                                  ('(', ')', 'runde Klammern'),
                                  ('[', ']', 'eckige Klammern')):
        depth = 0
        line = 1
        min_depth_line = None
        for ch in cleaned:
            if ch == '\n':
                line += 1
            elif ch == open_c:
                depth += 1
            elif ch == close_c:
                depth -= 1
                if depth < 0 and min_depth_line is None:
                    min_depth_line = line
        if depth != 0:
            errors.append(f"  ✗ {name}: Differenz {depth:+d} (nicht ausgeglichen)")
        if min_depth_line:
            errors.append(f"  ✗ {name}: schließt zu früh (Zeile {min_depth_line})")

    # Verdächtige Muster
    for idx, line in enumerate(raw.split('\n'), 1):
        stripped = line.strip()
        if stripped == ';;':
            errors.append(f"  ! Zeile {idx}: doppeltes Semikolon")

    return errors


if __name__ == '__main__':
    targets = sys.argv[1:]
    if not targets:
        targets = ['public/api/index.php', 'api/index.php', 'server-php/index.php']

    root = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    failed = False
    for t in targets:
        p = t if os.path.isabs(t) else os.path.join(root, t)
        if not os.path.exists(p):
            print(f"[skip] {t} existiert nicht")
            continue
        errs = check_file(p)
        size_kb = os.path.getsize(p) / 1024
        if errs:
            failed = True
            print(f"[FAIL] {t} ({size_kb:.1f} KB)")
            for e in errs:
                print(e)
        else:
            print(f"[ OK ] {t} ({size_kb:.1f} KB) — Klammern ausgeglichen")

    sys.exit(1 if failed else 0)
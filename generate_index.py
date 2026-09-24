#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generate_index.py — Taskster Code-Indexer mit Zeilennummern-Tracking
Erzeugt .agent_index.json im kompakten Format (1 Zeile pro Datei)
mit exakten Zeilennummern für Funktionen, Endpunkte, Klassen, Vue-APIs und SQL-Tabellen.
"""

import os
import re
import sys
import json
import time

ROOT_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_FILE = os.path.join(ROOT_DIR, ".agent_index.json")

IGNORE_DIRS = {
    "node_modules", "vendor", "storage", ".git", "dist", ".output", ".nuxt",
    ".nitro", ".cache", ".data", ".idea", ".vscode", "__pycache__",
    "public/build", "public/storage", "bilder gemini",
    "_nuxt", "public/_nuxt", "public/api", "server-php",
}

ALLOWED_EXTENSIONS = {".php", ".js", ".ts", ".vue", ".mjs", ".cjs"}
IGNORE_FILES = {"package-lock.json", "pnpm-lock.yaml", "yarn.lock"}

NOISE_WORDS = {
    "setup", "render", "data", "mounted", "created", "index", "show", "store",
    "update", "destroy", "constructor", "computed", "ref", "reactive", "watch",
    "watcheffect", "onmounted", "onunmounted", "nexttick", "t", "d", "to", "from",
    "select", "where", "set", "values", "table", "if", "not", "exists", "current_timestamp",
    "localstorage", "sessionstorage", "dual", "null", "true", "false", "import", "export",
    "und", "oder", "abgelehnt", "von", "nach", "fuer", "mit", "auf", "aus", "mail"
}

# PHP Regex
PHP_FUNCTION_PATTERN = re.compile(r'^\s*(?:public|protected|private|static|\s)*\s*\bfunction\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(', re.M)
PHP_CLASS_PATTERN = re.compile(r'^\s*(?:abstract\s+|final\s+)?\b(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)', re.M)

PHP_ROUTE_PATTERNS = [
    re.compile(r"if\s*\(\s*\$path\s*===\s*['\"]([^'\"]+)['\"]\s*&&\s*\$method\s*===\s*['\"]([A-Z]+)['\"]"),
    re.compile(r"if\s*\(\s*\$method\s*===\s*['\"]([A-Z]+)['\"]\s*&&\s*\$path\s*===\s*['\"]([^'\"]+)['\"]"),
    re.compile(r"preg_match\s*\(\s*['\"]#\^?([^#$]+)\$?#['\"].*?\$path.*?\)\s*&&\s*\$method\s*===\s*['\"]([A-Z]+)['\"]"),
    re.compile(r"if\s*\(\s*\$method\s*===\s*['\"]([A-Z]+)['\"]\s*&&\s*preg_match\s*\(\s*['\"]#\^?([^#$]+)\$?#['\"].*?\$path"),
    re.compile(r"(?:Route::|router->)(get|post|put|delete|patch|options)\s*\(\s*['\"]([^'\"]+)['\"]", re.I)
]

# JS/TS/Vue Regex
JS_FUNCTION_PATTERN = re.compile(r'^\s*(?:export\s+)?(?:async\s+)?function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*[\(<]', re.M)
JS_CONST_FN_PATTERN = re.compile(r'^\s*(?:export\s+)?(?:const|let)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*(?:async\s*)?(?:\((?:[a-zA-Z0-9_$,\s:?<>={}\[\]\'\"])*\)|[a-zA-Z_$][a-zA-Z0-9_$]*)\s*(?::\s*[^=]+)?=>', re.M)
JS_CLASS_PATTERN = re.compile(r'^\s*(?:export\s+)?(?:default\s+)?(?:class|interface)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)', re.M)
VUE_DEFINE_PATTERN = re.compile(r'\bdefine(Props|Emits|Expose|Model|Slots)\b\s*[\(<]')

NITRO_HANDLER_PATTERN = re.compile(r'\bdefineEventHandler\b')

# SQL Patterns
SQL_PATS = [
    re.compile(r'\b(?:FROM|JOIN|INTO)\s+[`"]?([a-zA-Z_][a-zA-Z0-9_]*)[`"]?', re.I),
    re.compile(r'\bUPDATE\s+[`"]?([a-zA-Z_][a-zA-Z0-9_]*)[`"]?\s+SET\b', re.I),
    re.compile(r'\bTABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?[`"]?([a-zA-Z_][a-zA-Z0-9_]*)[`"]?', re.I),
]

NITRO_METHODS = {
    "get": "GET", "post": "POST", "put": "PUT", "delete": "DELETE",
    "patch": "PATCH", "head": "HEAD", "options": "OPTIONS",
}

def nitro_route_from_path(rel_path):
    norm = rel_path.replace("\\", "/")
    if not norm.startswith("server/api/"):
        return None

    body = norm[len("server/api/"):]
    body = re.sub(r'\.(ts|js|mjs|cjs)$', '', body)
    parts = body.split("/")
    last = parts[-1]
    method = None
    
    if "." in last:
        maybe = last.rsplit(".", 1)[1].lower()
        if maybe in NITRO_METHODS:
            method = NITRO_METHODS[maybe]
            last = last.rsplit(".", 1)[0]
    parts[-1] = last

    if parts and parts[-1] == "index":
        parts = parts[:-1]

    parts = [re.sub(r'^\[\.\.\.(.+)\]$', r':\1*', p) for p in parts]
    parts = [re.sub(r'^\[(.+)\]$', r':\1', p) for p in parts]

    route = "/api/" + "/".join(parts)
    route = route.rstrip("/") or "/api"
    return f"{method or 'ANY'} {route}"

def scan_file(file_path, rel_path):
    try:
        with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception:
        return None

    lines = content.splitlines(keepends=True)
    ext = os.path.splitext(file_path)[1].lower()
    entry = {"path": rel_path.replace("\\", "/")}
    norm_path = entry["path"]

    endpoints = {}
    classes = {}
    functions = {}
    vue_apis = {}
    tables = {}

    # 1. Nitro Route (aus Dateipfad für server/api/)
    nitro_route = nitro_route_from_path(rel_path)
    if nitro_route:
        h_line = 1
        for idx, line in enumerate(lines, 1):
            if NITRO_HANDLER_PATTERN.search(line):
                h_line = idx
                break
        endpoints[nitro_route] = h_line

    # 2. PHP Analyse
    if ext == ".php":
        for m in PHP_CLASS_PATTERN.finditer(content):
            cname = m.group(1)
            if cname not in classes and cname.lower() not in NOISE_WORDS:
                classes[cname] = content.count('\n', 0, m.start()) + 1

        for m in PHP_FUNCTION_PATTERN.finditer(content):
            fname = m.group(1)
            if fname not in functions and fname.lower() not in NOISE_WORDS:
                functions[fname] = content.count('\n', 0, m.start()) + 1

        for idx, line in enumerate(lines, 1):
            for p_idx, pat in enumerate(PHP_ROUTE_PATTERNS):
                rm = pat.search(line)
                if rm:
                    if p_idx == 0:
                        ep = f"{rm.group(2)} /api/{rm.group(1)}"
                    elif p_idx == 1:
                        ep = f"{rm.group(1)} /api/{rm.group(2)}"
                    elif p_idx == 2:
                        p = rm.group(1)
                        p = re.sub(r'\([^)]+\)', ':id', p)
                        ep = f"{rm.group(2)} /api/{p}"
                    elif p_idx == 3:
                        p = rm.group(2)
                        p = re.sub(r'\([^)]+\)', ':id', p)
                        ep = f"{rm.group(1)} /api/{p}"
                    elif p_idx == 4:
                        ep = f"{rm.group(1).upper()} {rm.group(2)}"
                    if ep not in endpoints:
                        endpoints[ep] = idx

    # 3. JS / TS / Vue Analyse
    else:
        for m in JS_CLASS_PATTERN.finditer(content):
            cname = m.group(1)
            if cname not in classes and cname.lower() not in NOISE_WORDS:
                classes[cname] = content.count('\n', 0, m.start()) + 1

        for m in JS_FUNCTION_PATTERN.finditer(content):
            fname = m.group(1)
            if fname not in functions and fname.lower() not in NOISE_WORDS:
                functions[fname] = content.count('\n', 0, m.start()) + 1

        for m in JS_CONST_FN_PATTERN.finditer(content):
            fname = m.group(1)
            if fname not in functions and fname.lower() not in NOISE_WORDS:
                functions[fname] = content.count('\n', 0, m.start()) + 1

        for m in VUE_DEFINE_PATTERN.finditer(content):
            api_name = f"define{m.group(1)}"
            if api_name not in vue_apis:
                vue_apis[api_name] = content.count('\n', 0, m.start()) + 1

    # 4. SQL-Tabellen (Backend, Migrationen, Scripts oder DB-Dateien)
    is_backend = any(norm_path.startswith(p) for p in ("api/", "server/", "scripts/"))
    if is_backend and any(k in content for k in ("SELECT", "INSERT", "UPDATE", "DELETE", "CREATE TABLE", "db.prepare", "pdo->", "query(")):
        for idx, line in enumerate(lines, 1):
            sline = line.strip()
            if sline.startswith(("//", "/*", "*", "#", "import ", "from ")):
                continue
            for pat in SQL_PATS:
                for sm in pat.finditer(line):
                    tbl = sm.group(1).lower()
                    if tbl not in NOISE_WORDS and len(tbl) > 2 and not tbl.startswith(("this", "window", "process", "res", "req", "err")):
                        if tbl not in tables:
                            tables[tbl] = idx

    if endpoints:
        entry["endpoints"] = endpoints
    if classes:
        entry["classes"] = classes
    if functions:
        entry["functions"] = functions
    if vue_apis:
        entry["vue_api"] = vue_apis
    if tables:
        entry["tables"] = tables

    if any(k in entry for k in ("endpoints", "classes", "functions", "vue_api", "tables")):
        return entry
    return None

def build_index(quiet=False):
    start = time.time()
    index_data = {
        "generated_at": time.strftime("%Y-%m-%d %H:%M:%S"),
        "root": os.path.basename(ROOT_DIR),
        "files_count": 0,
        "endpoints_count": 0,
        "files": [],
    }

    for root, dirs, files in os.walk(ROOT_DIR):
        dirs[:] = [d for d in dirs if d not in IGNORE_DIRS]

        for file in files:
            if file in IGNORE_FILES:
                continue
            ext = os.path.splitext(file)[1].lower()
            if ext not in ALLOWED_EXTENSIONS:
                continue

            abs_path = os.path.join(root, file)
            rel_path = os.path.relpath(abs_path, ROOT_DIR)

            if any(rel_path.replace("\\", "/").startswith(ig) for ig in IGNORE_DIRS):
                continue

            res = scan_file(abs_path, rel_path)
            if res:
                index_data["files"].append(res)

    index_data["files"].sort(key=lambda e: e["path"])
    index_data["files_count"] = len(index_data["files"])
    index_data["endpoints_count"] = sum(len(e.get("endpoints", {})) for e in index_data["files"])

    # Kompaktes JSON-Format: Metadaten oben, dann ein Array, in dem jedes Dateiobjekt exakt eine Zeile einnimmt
    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        f.write(f'{{"generated_at":"{index_data["generated_at"]}","root":"{index_data["root"]}",')
        f.write(f'"files_count":{index_data["files_count"]},"endpoints_count":{index_data["endpoints_count"]},"files":[\n')
        
        file_lines = [json.dumps(file_obj, ensure_ascii=False, separators=(',', ':')) for file_obj in index_data["files"]]
        f.write(",\n".join(file_lines))
        f.write('\n]}')

    elapsed = (time.time() - start) * 1000
    if not quiet:
        size_kb = os.path.getsize(OUTPUT_FILE) / 1024
        print(f"[index] {index_data['files_count']} Dateien, {index_data['endpoints_count']} Endpunkte -> {os.path.basename(OUTPUT_FILE)} ({size_kb:.1f} KB, {elapsed:.0f} ms)")
    return index_data

def print_stats(index_data):
    endpoints = []
    total_functions = 0
    total_classes = 0
    all_tables = set()

    for e in index_data["files"]:
        endpoints += list(e.get("endpoints", {}).keys())
        total_functions += len(e.get("functions", {}))
        total_classes += len(e.get("classes", {}))
        all_tables.update(e.get("tables", {}).keys())

    print("\n--- Index-Statistik (mit Zeilennummern) ---")
    print(f"Dateien mit Logik : {index_data['files_count']}")
    print(f"Endpunkte gesamt  : {len(endpoints)}")
    print(f"Funktionen gesamt : {total_functions}")
    print(f"Klassen/Typen     : {total_classes}")
    print(f"SQL-Tabellen      : {len(all_tables)}")

    by_method = {}
    for ep in endpoints:
        m = ep.split(" ", 1)[0]
        by_method[m] = by_method.get(m, 0) + 1
    for m in sorted(by_method):
        print(f"  {m:<7}: {by_method[m]}")

    print("\nTop-Dateien (nach Funktionsanzahl):")
    ranked = sorted(index_data["files"], key=lambda e: len(e.get("functions", {})), reverse=True)[:10]
    for e in ranked:
        print(f"  {len(e.get('functions', {})):>3}  {e['path']}")

if __name__ == "__main__":
    quiet = "--quiet" in sys.argv
    data = build_index(quiet=quiet)
    if "--stats" in sys.argv:
        print_stats(data)
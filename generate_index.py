#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generate_index.py — Taskster Code-Indexer
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
NOISE_WORDS = {"setup", "render", "data", "mounted", "created", "index", "show", "store", "update", "destroy", "constructor"}

PHP_FUNCTION_PATTERN = re.compile(r'(?:public|protected|static|\s)*\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(')
PHP_CLASS_PATTERN = re.compile(r'\b(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)')
PHP_ROUTE_PATTERN = re.compile(r'(?:Route::|router\.)(get|post|put|delete|patch|match|any)\s*\(\s*[\'"]([^\'"]+)', re.IGNORECASE)

# JS/TS/Vue beschränkt auf Exports
JS_FUNCTION_PATTERN = re.compile(r'export\s+(?:async\s+)?function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\(')
JS_ARROW_PATTERN = re.compile(r'export\s+const\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*(?:async\s*)?(?:\([^)]*\)|[a-zA-Z_$][a-zA-Z0-9_$]*)\s*=>')
VUE_DEFINE_PATTERN = re.compile(r'define(Props|Emits|Expose|Model|Slots)\s*\(')

SQL_TABLE_PATTERN = re.compile(r'(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|FROM|JOIN|INTO|UPDATE)\s+[`"]?([a-z_][a-z0-9_]*)', re.IGNORECASE)

NITRO_METHODS = {
    "get": "GET", "post": "POST", "put": "PUT", "delete": "DELETE",
    "patch": "PATCH", "head": "HEAD", "options": "OPTIONS",
}

def _dedupe(seq, limit=None):
    seen = set()
    out = []
    for item in seq:
        if item and item not in seen and item.lower() not in NOISE_WORDS:
            seen.add(item)
            out.append(item)
    return out[:limit] if limit else out

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

    ext = os.path.splitext(file_path)[1].lower()
    entry = {"path": rel_path.replace("\\", "/")}

    route = nitro_route_from_path(rel_path)
    if route:
        entry["endpoints"] = [route]

    if ext == ".php":
        classes = PHP_CLASS_PATTERN.findall(content)
        if classes:
            entry["classes"] = _dedupe(classes, 5)

        routes = PHP_ROUTE_PATTERN.findall(content)
        if routes:
            entry.setdefault("endpoints", [])
            entry["endpoints"] = _dedupe(entry["endpoints"] + [f"{m.upper()} {p}" for m, p in routes], 15)

        funcs = PHP_FUNCTION_PATTERN.findall(content)
        if funcs:
            entry["functions"] = _dedupe(funcs, 15)

    else:
        funcs = []
        funcs += JS_FUNCTION_PATTERN.findall(content)
        funcs += JS_ARROW_PATTERN.findall(content)
        funcs = _dedupe(funcs, 15)
        if funcs:
            entry["functions"] = funcs

        defines = VUE_DEFINE_PATTERN.findall(content)
        if defines:
            entry["vue_api"] = _dedupe([f"define{d}" for d in defines], 5)

    tables = SQL_TABLE_PATTERN.findall(content)
    if tables:
        noise = {"select", "where", "set", "values", "table", "if", "not", "exists", "current_timestamp", "localstorage", "sessionstorage", "dual"}
        tables = [t.lower() for t in tables if t.lower() not in noise]
        tables = _dedupe(tables, 10)
        if tables:
            entry["tables"] = tables

    if any(k in entry for k in ("classes", "endpoints", "functions", "vue_api", "tables")):
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
    index_data["endpoints_count"] = sum(len(e.get("endpoints", [])) for e in index_data["files"])

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
    for e in index_data["files"]:
        endpoints += e.get("endpoints", [])

    print("\n--- Index-Statistik ---")
    print(f"Dateien mit Logik : {index_data['files_count']}")
    print(f"Endpunkte gesamt  : {len(endpoints)}")

    by_method = {}
    for ep in endpoints:
        m = ep.split(" ", 1)[0]
        by_method[m] = by_method.get(m, 0) + 1
    for m in sorted(by_method):
        print(f"  {m:<7}: {by_method[m]}")

    print("\nTop-Dateien (nach Funktionsanzahl):")
    ranked = sorted(index_data["files"], key=lambda e: len(e.get("functions", [])), reverse=True)[:10]
    for e in ranked:
        print(f"  {len(e.get('functions', [])):>3}  {e['path']}")

if __name__ == "__main__":
    quiet = "--quiet" in sys.argv
    data = build_index(quiet=quiet)
    if "--stats" in sys.argv:
        print_stats(data)
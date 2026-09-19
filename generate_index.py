#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
generate_index.py — Taskster Code-Indexer
=========================================
Erzeugt eine kompakte Funktions-/Routen-Übersicht des Projekts in
`.agent_index.json`. Der Agent liest danach nur noch diese eine Datei,
statt das ganze Repo zu durchsuchen (0 Tokens für die Suche).

Aufruf:
    python generate_index.py            # Index neu erzeugen
    python generate_index.py --stats    # Zusätzlich Statistik ausgeben
    python generate_index.py --quiet    # Keine Konsolenausgabe

Erkennt:
  * Nuxt/Nitro API-Routen aus Dateipfaden (server/api/**/*.get.ts -> GET /api/...)
  * PHP-Funktionen, Klassen, Interfaces, Traits und Routen
  * Vue/TS-Funktionen, Composables, refs/reactive/computed, defineProps/Emits
  * SQL-Tabellen (CREATE TABLE / FROM / JOIN) als grobe Referenz
"""

import os
import re
import sys
import json
import time

ROOT_DIR = os.path.dirname(os.path.abspath(__file__))
OUTPUT_FILE = os.path.join(ROOT_DIR, ".agent_index.json")

# ---------------------------------------------------------------------------
# Konfiguration
# ---------------------------------------------------------------------------

IGNORE_DIRS = {
    "node_modules", "vendor", "storage", ".git", "dist", ".output", ".nuxt",
    ".nitro", ".cache", ".data", ".idea", ".vscode", "__pycache__",
    "public/build", "public/storage", "bilder gemini",
    # Build-Artefakte (generiert, nicht Teil des Quellcodes)
    "_nuxt", "public/_nuxt", "public/api", "server-php",
}

# Dateiendungen, die indexiert werden
ALLOWED_EXTENSIONS = {".php", ".js", ".ts", ".vue", ".mjs", ".cjs"}

# Dateien, die nie relevant sind
IGNORE_FILES = {
    "package-lock.json", "pnpm-lock.yaml", "yarn.lock",
}

# ---------------------------------------------------------------------------
# Regex-Muster
# ---------------------------------------------------------------------------

PHP_FUNCTION_PATTERN = re.compile(
    r'(?:public|private|protected|static|\s)*\s*function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\('
)
PHP_CLASS_PATTERN = re.compile(r'\b(?:class|interface|trait|enum)\s+([A-Za-z_][A-Za-z0-9_]*)')
PHP_ROUTE_PATTERN = re.compile(
    r'(?:Route::|router\.)(get|post|put|delete|patch|match|any)\s*\(\s*[\'"]([^\'"]+)',
    re.IGNORECASE,
)

# JS/TS/Vue
JS_FUNCTION_PATTERN = re.compile(
    r'(?:export\s+)?(?:async\s+)?function\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*\('
)
JS_ARROW_PATTERN = re.compile(
    r'(?:export\s+)?const\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*(?:async\s*)?'
    r'(?:\([^)]*\)|[a-zA-Z_$][a-zA-Z0-9_$]*)\s*=>'
)
JS_REACTIVE_PATTERN = re.compile(
    r'(?:export\s+)?const\s+([a-zA-Z_$][a-zA-Z0-9_$]*)\s*=\s*'
    r'(?:ref|reactive|computed|shallowRef|toRef|useState|useFetch|useAsyncData)\s*\('
)
JS_EXPORT_PATTERN = re.compile(
    r'export\s+(?:default\s+)?(?:const|let|var|function|class)\s+([a-zA-Z_$][a-zA-Z0-9_$]*)'
)
VUE_DEFINE_PATTERN = re.compile(r'define(Props|Emits|Expose|Model|Slots)\s*\(')

# SQL-Tabellen (grobe Referenz)
SQL_TABLE_PATTERN = re.compile(
    r'(?:CREATE\s+TABLE(?:\s+IF\s+NOT\s+EXISTS)?|FROM|JOIN|INTO|UPDATE)\s+[`"]?([a-z_][a-z0-9_]*)',
    re.IGNORECASE,
)

# HTTP-Methoden aus Nitro-Dateinamen
NITRO_METHODS = {
    "get": "GET", "post": "POST", "put": "PUT", "delete": "DELETE",
    "patch": "PATCH", "head": "HEAD", "options": "OPTIONS",
}

# ---------------------------------------------------------------------------
# Hilfsfunktionen
# ---------------------------------------------------------------------------

def _dedupe(seq, limit=None):
    """Duplikate entfernen, Reihenfolge erhalten, optional begrenzen."""
    seen = set()
    out = []
    for item in seq:
        if item and item not in seen:
            seen.add(item)
            out.append(item)
    return out[:limit] if limit else out


def nitro_route_from_path(rel_path):
    """
    Leitet aus einem Nitro-Dateipfad die HTTP-Route ab.
    server/api/tasks/index.get.ts        -> GET /api/tasks
    server/api/tasks/[id].get.ts         -> GET /api/tasks/:id
    server/api/tasks/[id]/index.put.ts   -> PUT /api/tasks/:id
    server/api/admin/companies.post.ts   -> POST /api/admin/companies
    """
    norm = rel_path.replace("\\", "/")
    if not norm.startswith("server/api/"):
        return None

    body = norm[len("server/api/"):]
    # Endung entfernen
    body = re.sub(r'\.(ts|js|mjs|cjs)$', '', body)

    # Methode aus letztem Segment lesen
    parts = body.split("/")
    last = parts[-1]
    method = None
    if "." in last:
        maybe = last.rsplit(".", 1)[1].lower()
        if maybe in NITRO_METHODS:
            method = NITRO_METHODS[maybe]
            last = last.rsplit(".", 1)[0]
    parts[-1] = last

    # index entfernen
    if parts and parts[-1] == "index":
        parts = parts[:-1]

    # [id] -> :id
    parts = [re.sub(r'^\[\.\.\.(.+)\]$', r':\1*', p) for p in parts]
    parts = [re.sub(r'^\[(.+)\]$', r':\1', p) for p in parts]

    route = "/api/" + "/".join(parts)
    route = route.rstrip("/") or "/api"
    return f"{method or 'ANY'} {route}"


def scan_file(file_path, rel_path):
    """Analysiert eine Datei und liefert einen Index-Eintrag oder None."""
    try:
        with open(file_path, "r", encoding="utf-8", errors="ignore") as f:
            content = f.read()
    except Exception:
        return None

    ext = os.path.splitext(file_path)[1].lower()
    entry = {"path": rel_path.replace("\\", "/")}

    # --- Nitro-Route aus Dateipfad ---
    route = nitro_route_from_path(rel_path)
    if route:
        entry["endpoints"] = [route]

    # --- PHP-spezifisch ---
    if ext == ".php":
        classes = PHP_CLASS_PATTERN.findall(content)
        if classes:
            entry["classes"] = _dedupe(classes, 10)

        routes = PHP_ROUTE_PATTERN.findall(content)
        if routes:
            entry.setdefault("endpoints", [])
            entry["endpoints"] = _dedupe(
                entry["endpoints"] + [f"{m.upper()} {p}" for m, p in routes], 30
            )

        funcs = PHP_FUNCTION_PATTERN.findall(content)
        if funcs:
            entry["functions"] = _dedupe(funcs, 30)

    # --- JS / TS / Vue ---
    else:
        funcs = []
        funcs += JS_FUNCTION_PATTERN.findall(content)
        funcs += JS_ARROW_PATTERN.findall(content)
        funcs += JS_REACTIVE_PATTERN.findall(content)
        funcs += JS_EXPORT_PATTERN.findall(content)
        funcs = _dedupe(funcs, 40)
        if funcs:
            entry["functions"] = funcs

        defines = VUE_DEFINE_PATTERN.findall(content)
        if defines:
            entry["vue_api"] = _dedupe([f"define{d}" for d in defines], 10)

    # --- SQL-Tabellen (grobe Referenz) ---
    tables = SQL_TABLE_PATTERN.findall(content)
    if tables:
        # Reservierte Wörter / offensichtliche Fehltreffer filtern
        noise = {
            "select", "where", "set", "values", "table", "if", "not", "exists",
            "current_timestamp", "localstorage", "sessionstorage", "dual",
        }
        tables = [t.lower() for t in tables if t.lower() not in noise]
        tables = _dedupe(tables, 15)
        if tables:
            entry["tables"] = tables

    # Nur aufnehmen, wenn relevante Logik gefunden wurde
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
        # Ignorierte Ordner ausfiltern (in-place, damit os.walk sie überspringt)
        dirs[:] = [d for d in dirs if d not in IGNORE_DIRS]

        for file in files:
            if file in IGNORE_FILES:
                continue
            ext = os.path.splitext(file)[1].lower()
            if ext not in ALLOWED_EXTENSIONS:
                continue

            abs_path = os.path.join(root, file)
            rel_path = os.path.relpath(abs_path, ROOT_DIR)

            # Sicherheitsnetz: ignorierte Pfadpräfixe
            if any(rel_path.replace("\\", "/").startswith(ig) for ig in IGNORE_DIRS):
                continue

            res = scan_file(abs_path, rel_path)
            if res:
                index_data["files"].append(res)

    # Sortieren für stabile, diff-freundliche Ausgabe
    index_data["files"].sort(key=lambda e: e["path"])
    index_data["files_count"] = len(index_data["files"])
    index_data["endpoints_count"] = sum(
        len(e.get("endpoints", [])) for e in index_data["files"]
    )

    with open(OUTPUT_FILE, "w", encoding="utf-8") as f:
        json.dump(index_data, f, indent=2, ensure_ascii=False)

    elapsed = (time.time() - start) * 1000
    if not quiet:
        size_kb = os.path.getsize(OUTPUT_FILE) / 1024
        print(
            f"[index] {index_data['files_count']} Dateien, "
            f"{index_data['endpoints_count']} Endpunkte -> "
            f"{os.path.basename(OUTPUT_FILE)} ({size_kb:.1f} KB, {elapsed:.0f} ms)"
        )
    return index_data


def print_stats(index_data):
    """Kurze Statistik über den erzeugten Index ausgeben."""
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
    ranked = sorted(
        index_data["files"],
        key=lambda e: len(e.get("functions", [])),
        reverse=True,
    )[:10]
    for e in ranked:
        print(f"  {len(e.get('functions', [])):>3}  {e['path']}")


if __name__ == "__main__":
    quiet = "--quiet" in sys.argv
    data = build_index(quiet=quiet)
    if "--stats" in sys.argv:
        print_stats(data)
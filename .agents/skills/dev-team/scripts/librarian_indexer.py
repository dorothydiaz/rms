#!/usr/bin/env python3
"""
The Librarian: Global Structural Codebase Indexer (Python Implementation)
Part of the AEC DevTeam System (/dev-team)

Generates .agents/artifacts/librarian_index.json mapping all symbols
(classes, methods, functions, routes, imports) to exact file paths and line numbers.

Usage:
    python librarian_indexer.py [--output path/to/index.json]
"""

import os
import re
import json
import time
import sys
from datetime import datetime

ROOT_DIR = os.path.abspath(os.getcwd())
ARTIFACTS_DIR = os.path.join(ROOT_DIR, '.agents', 'artifacts')
DEFAULT_OUTPUT = os.path.join(ARTIFACTS_DIR, 'librarian_index.json')

IGNORED_DIRS = {
    'node_modules', 'vendor', '.git', 'storage', 'bootstrap/cache',
    'public/build', 'public/storage', '.idea', '.vscode', '.archify',
    'dist', 'coverage', '__pycache__', 'venv'
}

INDEXED_EXTENSIONS = (
    '.php', '.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs', '.vue',
    '.py', '.go', '.json'
)

def parse_php(lines, rel_path, index):
    current_namespace = ""
    current_class = None

    class_regex = re.compile(r'\b(class|interface|trait|enum)\s+([A-Za-z0-9_]+)', re.IGNORECASE)
    method_regex = re.compile(r'\b(?:(public|protected|private)\s+)?(?:static\s+)?function\s+([A-Za-z0-9_]+)\s*\(', re.IGNORECASE)
    route_regex = re.compile(r'\bRoute::(get|post|put|patch|delete|resource)\s*\(\s*[\'"]([^\'"]+)[\'"]', re.IGNORECASE)
    name_regex = re.compile(r'->name\(\s*[\'"]([^\'"]+)[\'"]\s*\)')
    use_regex = re.compile(r'^use\s+([A-Za-z0-9_\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?;', re.IGNORECASE)
    ns_regex = re.compile(r'^namespace\s+([A-Za-z0-9_\\]+);', re.IGNORECASE)

    file_imports = []
    file_symbols = []

    for line_num, line in enumerate(lines, 1):
        trimmed = line.strip()
        if not trimmed or trimmed.startswith(('//', '*', '/*')):
            continue

        ns_match = ns_regex.match(trimmed)
        if ns_match:
            current_namespace = ns_match.group(1)
            continue

        use_match = use_regex.match(trimmed)
        if use_match:
            full_use = use_match.group(1)
            alias = use_match.group(2) or full_use.split('\\')[-1]
            file_imports.append({"full": full_use, "alias": alias, "line": line_num})
            continue

        class_match = class_regex.search(trimmed)
        if class_match:
            entity_type = class_match.group(1).lower()
            entity_name = class_match.group(2)
            current_class = entity_name
            record = {
                "file": rel_path,
                "line": line_num,
                "type": entity_type,
                "namespace": current_namespace
            }
            index["symbols"].setdefault(entity_name, []).append(record)
            file_symbols.append({"name": entity_name, "line": line_num, "type": entity_type})
            continue

        method_match = method_regex.search(trimmed)
        if method_match:
            visibility = method_match.group(1) or "public"
            func_name = method_match.group(2)
            record = {
                "file": rel_path,
                "line": line_num,
                "type": "method" if current_class else "function",
                "visibility": visibility,
                "class": current_class,
                "namespace": current_namespace
            }
            index["symbols"].setdefault(func_name, []).append(record)
            file_symbols.append({"name": func_name, "line": line_num, "type": record["type"], "class": current_class})
            continue

        route_match = route_regex.search(trimmed)
        if route_match:
            http_method = route_match.group(1).upper()
            route_uri = route_match.group(2)
            name_match = name_regex.search(trimmed)
            route_name = name_match.group(1) if name_match else None
            index["routes"].append({
                "method": http_method,
                "uri": route_uri,
                "name": route_name,
                "file": rel_path,
                "line": line_num
            })
            if route_name:
                index["symbols"].setdefault(route_name, []).append({
                    "file": rel_path,
                    "line": line_num,
                    "type": "route_name",
                    "uri": route_uri
                })

    if file_imports:
        index["file_imports"][rel_path] = file_imports
    if file_symbols:
        index["file_symbols"][rel_path] = file_symbols

def generate_index(output_path=DEFAULT_OUTPUT):
    start_time = time.time()
    print("📖 [The Librarian (Python)] Scanning repository and generating structural symbol index...")

    index = {
        "metadata": {
            "generated_at": datetime.utcnow().isoformat() + "Z",
            "root_dir": ROOT_DIR,
            "version": "1.0.0",
            "total_symbols": 0,
            "total_unique_symbols": 0,
            "total_files_indexed": 0,
            "total_routes": 0,
            "duration_ms": 0
        },
        "symbols": {},
        "routes": [],
        "file_imports": {},
        "file_symbols": {}
    }

    files_indexed = 0

    for root, dirs, files in os.walk(ROOT_DIR):
        # Exclude directories in place
        dirs[:] = [d for d in dirs if d not in IGNORED_DIRS and not any(ign in os.path.join(root, d) for ign in IGNORED_DIRS)]

        for f in files:
            if f.endswith('.blade.php') or f.endswith(INDEXED_EXTENSIONS):
                full_path = os.path.join(root, f)
                rel_path = os.path.relpath(full_path, ROOT_DIR).replace('\\', '/')

                try:
                    with open(full_path, 'r', encoding='utf-8', errors='ignore') as fp:
                        lines = fp.readlines()
                except Exception:
                    continue

                if f.endswith('.php'):
                    parse_php(lines, rel_path, index)
                files_indexed += 1

    duration_ms = int((time.time() - start_time) * 1000)
    unique_symbols = len(index["symbols"])
    total_symbols = sum(len(v) for v in index["symbols"].values())

    index["metadata"]["total_symbols"] = total_symbols
    index["metadata"]["total_unique_symbols"] = unique_symbols
    index["metadata"]["total_files_indexed"] = files_indexed
    index["metadata"]["total_routes"] = len(index["routes"])
    index["metadata"]["duration_ms"] = duration_ms

    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    with open(output_path, 'w', encoding='utf-8') as out_f:
        json.dump(index, out_f, indent=2)

    print(f"✅ [The Librarian (Python)] Index generated in {duration_ms}ms at {output_path}!")
    return index

if __name__ == '__main__':
    out_target = DEFAULT_OUTPUT
    if len(sys.argv) > 2 and sys.argv[1] == '--output':
        out_target = sys.argv[2]
    generate_index(out_target)

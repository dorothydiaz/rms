# Token Efficiency & Fast Codebase Search Directives

## 1. Core Mandate: Zero Line-by-Line Scanning
Every tool call incurs cognitive and token overhead. Under no circumstances should an agent browse, scan, or read files line by line without pinpoint reconnaissance. Sequential scrolling through codebases is strictly forbidden.
The search and modification pipeline is strictly directional:
**4-Pillar Fast Search (Ripgrep / BM25 / AST / RAG) -> Targeted `view_file` (≤ 80 lines) -> `multi_replace_file_content` -> Inline Verification Script**

---

## 2. The 4-Pillar Fast Codebase Search Engine

### Pillar 1: Ripgrep Integration (Fast Keyword & Symbol Search)
1. **Never perform blind reads:** Use `grep_search` or `git grep` / `rg` first to locate exact lines.
2. **Multi-Target Regex Search:** Always combine multiple function, variable, or selector lookups into a single regex query using pipe delimiters:
   ```json
   {
     "Query": "targetA|targetB|targetC",
     "IsRegex": true,
     "MatchPerLine": true,
     "CaseInsensitive": true
   }
   ```
3. **Scope Filtering:** Constrain searches with `Includes` (e.g. `["*.blade.php", "*.php"]`) to eliminate noise.

### Pillar 2: File Tree Indexing & BM25 (High-Level Map Matching)
1. **Top-Level Map:** When navigating unfamiliar domain logic, use BM25 keyword matching against repository paths rather than recursive folder listing.
2. **Instant Path Resolution:** Run `node .agents/skills/dev-team/scripts/fast_codebase_search.cjs bm25 "<query tokens>"` to score and rank target files immediately.

### Pillar 3: Structural Parsing with Tree-sitter & AST Outlining
1. **Scope Over Syntax:** Never load 500+ lines to understand a class or module.
2. **Signature Extraction:** Run `node .agents/skills/dev-team/scripts/fast_codebase_search.cjs ast <filepath>` to extract class names, method signatures, return types, and routes in milliseconds.

### Pillar 4: Vector Embeddings & Code RAG (Semantic & Intent Search)
1. **Intent-Based Retrieval:** When keywords are unknown ("Where is session expiry handled?"), use hybrid BM25 + architectural reference specs (`.agents/skills/dev-team/references/`) to isolate domain logic before inspecting code.

---

## 3. Targeted Slice Reads (Max 80 Lines)
1. Once grep/AST returns the line numbers, compute: `StartLine = match_line - 5`, `EndLine = match_line + 40` (or function scope).
2. Max limit per read is 80 lines. Never call `view_file` on large files (e.g., 500+ lines) with default 800-line pagination.

---

## 4. Sed-Style Surgical Edits
1. **Single Call Batching:** Always use `multi_replace_file_content` when editing non-contiguous sections in a file. Never call `replace_file_content` in rapid sequential loops on the same file.
2. **Minimal Unique Target Content:** Keep `TargetContent` anchored by 3-5 unique lines; do not quote dozens of unchanged lines.
3. **Bound Range Safety:** Always specify `StartLine` and `EndLine` bounding the target snippet.

---

## 5. Verification Protocol: Cheap Shell Execution
1. After editing, do **NOT** re-read files with `view_file` to verify edits.
2. Run an inline validation script or command:
   - PHP: `php -l <path_to_file>`
   - JS / Node: `node -e "require('fs').readFileSync('...').includes('expected_string')"`
3. If the syntax and invariant checks pass, immediately declare verified.

# Token Efficiency & Grep-First Architecture Directives

## 1. Core Mandate
Every tool call incurs cognitive and token overhead. Under no circumstances should an agent browse, scan, or read files arbitrarily without pinpoint reconnaissance. The tool pipeline is strictly directional:
**`grep_search` -> Targeted `view_file` (≤ 80 lines) -> `multi_replace_file_content` -> Inline Verification Script**

---

## 2. Reconnaissance: Grep-First Protocol
1. **Never perform blind reads:** Calling `view_file` on unverified line ranges is strictly prohibited.
2. **Multi-Target Regex Search:** Always combine multiple function, variable, or selector lookups into a single regex query using pipe delimiters:
   ```json
   {
     "Query": "targetA|targetB|targetC",
     "IsRegex": true,
     "MatchPerLine": true,
     "CaseInsensitive": true
   }
   ```
3. **Scope Filtering:** Constrain searches with `Includes` (e.g. `["*.blade.php", "*.js"]`) to eliminate vendor/node_modules/cache noise.
4. **Shell Fallback:** When `grep_search` is unavailable or on binary boundaries, use Windows PowerShell:
   ```powershell
   Select-String -Path "resources\views\**\*.blade.php" -Pattern "myFunction|mySelector" | Select-Object Filename, LineNumber, Line
   ```

---

## 3. Targeted Slice Reads (Max 80 Lines)
1. Once grep returns the line numbers, compute: `StartLine = grep_line - 10`, `EndLine = grep_line + 50` (or function scope).
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

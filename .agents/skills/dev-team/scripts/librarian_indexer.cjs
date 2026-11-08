#!/usr/bin/env node
/**
 * The Librarian: Global Structural & Eloquent Codebase Indexer (v2.0)
 * Part of the AEC DevTeam System (/dev-team)
 *
 * Generates .agents/artifacts/librarian_index.json mapping:
 *  - PHP/JS Classes, Interfaces, Traits, Enums
 *  - Methods & Functions with exact { line, end_line, signature }
 *  - Eloquent Model DNA ($table, $fillable, $casts, relationships)
 *  - Laravel Routes with [Controller::class, 'action'] bindings
 *  - File Dependency / Use Import Graph
 *
 * Usage:
 *   node .agents/skills/dev-team/scripts/librarian_indexer.cjs [--output <path>]
 */

const fs = require('fs');
const path = require('path');

const ROOT_DIR = process.cwd();
const ARTIFACTS_DIR = path.join(ROOT_DIR, '.agents', 'artifacts');
const DEFAULT_OUTPUT = path.join(ARTIFACTS_DIR, 'librarian_index.json');

const IGNORED_DIRS = new Set([
  'node_modules', 'vendor', '.git', 'storage', 'bootstrap/cache',
  'public/build', 'public/storage', '.idea', '.vscode', '.archify',
  'dist', 'coverage'
]);

const INDEXED_EXTENSIONS = new Set([
  '.php', '.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs', '.vue',
  '.blade.php', '.css', '.py', '.go', '.json'
]);

const JS_CONTROL_KEYWORDS = new Set([
  'if', 'for', 'while', 'switch', 'catch', 'function', 'return', 'typeof',
  'instanceof', 'delete', 'void', 'throw', 'new', 'super', 'import', 'export', 'else'
]);

const PHP_IGNORE_CALLS = new Set([
  'if', 'for', 'foreach', 'while', 'switch', 'catch', 'return', 'isset', 'empty',
  'unset', 'array', 'list', 'echo', 'print', 'die', 'exit', 'match', 'fn', 'function',
  'compact', 'dd', 'dump', 'now', 'collect', 'response', 'view', 'redirect', 'back',
  'abort', 'auth', 'config', 'env', 'session', 'request', 'trans', '__', 'app'
]);

/**
 * Extracts 1-level-down function/method/route calls from a logical block's lines.
 */
function extractBlockCalls(blockLines, ext) {
  const calls = new Set();
  for (const rawLine of blockLines) {
    const line = rawLine.trim();
    if (!line || line.startsWith('//') || line.startsWith('*') || line.startsWith('#')) continue;

    if (ext === '.php') {
      // $this->methodName(
      const thisCalls = line.matchAll(/\$this->([A-Za-z0-9_]+)\s*\(/g);
      for (const m of thisCalls) {
        if (!PHP_IGNORE_CALLS.has(m[1])) calls.add(`$this->${m[1]}`);
      }
      // Static or Model/Service calls: ClassName::methodName(
      const staticCalls = line.matchAll(/\b([A-Za-z0-9_]+)::([A-Za-z0-9_]+)\s*\(/g);
      for (const m of staticCalls) {
        if (!['self', 'static', 'parent'].includes(m[1])) {
          calls.add(`${m[1]}::${m[2]}`);
        } else {
          calls.add(`$this->${m[2]}`);
        }
      }
      // Action / Service variable calls: $service->methodName( (excluding common $request/$query/$table)
      const varCalls = line.matchAll(/\$([A-Za-z0-9_]+)->([A-Za-z0-9_]+)\s*\(/g);
      for (const m of varCalls) {
        const v = m[1];
        const fn = m[2];
        if (!['this', 'request', 'query', 'q', 'table', 'blueprint', 'validator'].includes(v) &&
            !['where', 'orWhere', 'whereIn', 'select', 'orderBy', 'get', 'first', 'pluck', 'count', 'find', 'findOrFail', 'with', 'load', 'toArray', 'input', 'validated', 'boolean', 'integer', 'string', 'all', 'has', 'filled'].includes(fn)) {
          calls.add(`$${v}->${fn}`);
        }
      }
    } else {
      // JS / Blade: this.methodName(
      const jsThisCalls = line.matchAll(/\bthis\.([A-Za-z0-9_]+)\s*\(/g);
      for (const m of jsThisCalls) {
        if (!JS_CONTROL_KEYWORDS.has(m[1])) calls.add(`this.${m[1]}`);
      }
      // Laravel route('name') references
      const routeRefs = line.matchAll(/\broute\(\s*['"]([^'"]+)['"]/g);
      for (const m of routeRefs) {
        calls.add(`route:${m[1]}`);
      }
      // fetch(...) or axios.(get|post|put|patch|delete)(...)
      const fetchRefs = line.matchAll(/\bfetch\(\s*[`'"]([^`'"]+)[`'"]/g);
      for (const m of fetchRefs) {
        calls.add(`fetch:${m[1]}`);
      }
      const axiosRefs = line.matchAll(/\baxios\.(get|post|put|patch|delete)\(\s*[`'"]([^`'"]+)[`'"]/g);
      for (const m of axiosRefs) {
        calls.add(`axios.${m[1]}:${m[2]}`);
      }
    }
  }
  return Array.from(calls).slice(0, 25);
}

function collectFiles(dir = ROOT_DIR, fileList = []) {
  let entries = [];
  try {
    entries = fs.readdirSync(dir, { withFileTypes: true });
  } catch (e) {
    return fileList;
  }

  for (const entry of entries) {
    const fullPath = path.join(dir, entry.name);
    const relPath = path.relative(ROOT_DIR, fullPath).replace(/\\/g, '/');

    if (entry.isDirectory()) {
      if (!IGNORED_DIRS.has(entry.name) && !IGNORED_DIRS.has(relPath)) {
        collectFiles(fullPath, fileList);
      }
    } else if (entry.isFile()) {
      const isBlade = entry.name.endsWith('.blade.php');
      const ext = isBlade ? '.blade.php' : path.extname(entry.name).toLowerCase();
      if (INDEXED_EXTENSIONS.has(ext)) {
        fileList.push({ fullPath, relPath, ext });
      }
    }
  }
  return fileList;
}

/**
 * Counts net brace delta `{` minus `}` on a line after stripping basic string literals and line comments.
 */
function countBraces(line) {
  const cleaned = line
    .replace(/\/\/.*$/, '')
    .replace(/'(?:\\.|[^'\\])*'/g, '""')
    .replace(/"(?:\\.|[^"\\])*"/g, '""');
  let open = 0;
  let close = 0;
  for (let i = 0; i < cleaned.length; i++) {
    if (cleaned[i] === '{') open++;
    else if (cleaned[i] === '}') close++;
  }
  return { open, close, delta: open - close };
}

/**
 * Extracts Eloquent Model metadata ($table, $fillable, $casts, relationships) from PHP model content.
 */
function extractEloquentModelMeta(content, relPath, className, namespace, fileSymbols) {
  const isModel =
    relPath.startsWith('app/Models/') ||
    /\bextends\s+(?:Model|Authenticatable|Pivot)\b/.test(content);
  if (!isModel || !className) return null;

  let table = null;
  const tableMatch = content.match(/protected\s+\$table\s*=\s*['"]([^'"]+)['"]/);
  if (tableMatch) {
    table = tableMatch[1];
  }

  const fillable = [];
  const fillableMatch = content.match(/protected\s+\$fillable\s*=\s*\[([\s\S]*?)\];/);
  if (fillableMatch) {
    const items = fillableMatch[1].match(/['"]([^'"]+)['"]/g) || [];
    items.forEach(item => fillable.push(item.replace(/['"]/g, '')));
  }

  const casts = {};
  const castsPropMatch = content.match(/protected\s+\$casts\s*=\s*\[([\s\S]*?)\];/);
  const castsFnMatch = content.match(/protected\s+function\s+casts\s*\(\s*\)\s*(?::\s*array)?\s*\{[\s\S]*?return\s*\[([\s\S]*?)\];/);
  const rawCasts = (castsPropMatch && castsPropMatch[1]) || (castsFnMatch && castsFnMatch[1]) || '';
  if (rawCasts) {
    const pairRegex = /['"]([^'"]+)['"]\s*=>\s*(['"][^'"]+['"]|[A-Za-z0-9_\\]+::class)/g;
    let m;
    while ((m = pairRegex.exec(rawCasts)) !== null) {
      casts[m[1]] = m[2].replace(/^['"]|['"]$/g, '');
    }
  }

  // Extract Eloquent relationships by scanning methods for $this->belongsTo / hasMany / etc.
  const relationships = [];
  const lines = content.split(/\r?\n/);
  const relRegex = /\$this->(belongsTo|hasMany|hasOne|belongsToMany|morphTo|morphMany|morphOne|hasManyThrough|hasOneThrough)\s*\(\s*(?:([A-Za-z0-9_\\]+)::class|['"]([^'"]+)['"])?/;

  for (const sym of fileSymbols) {
    if (sym.type !== 'method' || !sym.line) continue;
    const endLine = sym.end_line || Math.min(lines.length, sym.line + 15);
    const bodySlice = lines.slice(sym.line - 1, endLine).join('\n');
    const relMatch = bodySlice.match(relRegex);
    if (relMatch) {
      relationships.push({
        name: sym.name,
        type: relMatch[1],
        related: relMatch[2] || relMatch[3] || 'polymorphic',
        line: sym.line,
        end_line: sym.end_line || sym.line
      });
    }
  }

  return {
    class: className,
    namespace,
    file: relPath,
    table,
    fillable,
    casts,
    relationships
  };
}

function parseFile(fileObj, index) {
  let content = '';
  try {
    content = fs.readFileSync(fileObj.fullPath, 'utf8');
  } catch (e) {
    return;
  }

  const lines = content.split(/\r?\n/);
  const { relPath, ext } = fileObj;
  const fileImports = [];
  const fileSymbols = [];
  let currentNamespace = '';
  let currentClass = null;

  // Track active method for end_line computation
  let activeMethodRecord = null;
  let activeMethodFileSymbol = null;
  let methodBraceDepth = 0;
  let methodOpenedBrace = false;

  for (let idx = 0; idx < lines.length; idx++) {
    const lineNum = idx + 1;
    const line = lines[idx];
    const trimmed = line.trim();

    // Update active method brace tracking if inside a method
    if (activeMethodRecord) {
      const { open, delta } = countBraces(line);
      if (open > 0) methodOpenedBrace = true;
      methodBraceDepth += delta;
      if (methodOpenedBrace && methodBraceDepth <= 0) {
        activeMethodRecord.end_line = lineNum;
        if (activeMethodFileSymbol) activeMethodFileSymbol.end_line = lineNum;
        activeMethodRecord = null;
        activeMethodFileSymbol = null;
        methodBraceDepth = 0;
        methodOpenedBrace = false;
      }
    }

    if (!trimmed || trimmed.startsWith('//') || trimmed.startsWith('*') || trimmed.startsWith('/*')) {
      continue;
    }

    // ==========================================
    // PHP Parsing
    // ==========================================
    if (ext === '.php') {
      // Namespace
      const nsMatch = trimmed.match(/^namespace\s+([A-Za-z0-9_\\]+);/i);
      if (nsMatch) {
        currentNamespace = nsMatch[1];
        continue;
      }

      // Imports / Use statements
      const useMatch = trimmed.match(/^use\s+([A-Za-z0-9_\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?;/i);
      if (useMatch && !currentClass) {
        const fullUse = useMatch[1];
        const alias = useMatch[2] || fullUse.split('\\').pop();
        fileImports.push({ full: fullUse, alias, line: lineNum });
        continue;
      }

      // Class / Interface / Trait / Enum
      const classMatch = trimmed.match(/\b(class|interface|trait|enum)\s+([A-Za-z0-9_]+)/i);
      if (classMatch) {
        const entityType = classMatch[1].toLowerCase();
        const entityName = classMatch[2];
        currentClass = entityName;

        const record = {
          file: relPath,
          line: lineNum,
          type: entityType,
          namespace: currentNamespace
        };
        addSymbol(index.symbols, entityName, record);
        fileSymbols.push({ name: entityName, line: lineNum, type: entityType });
        continue;
      }

      // Methods / Functions (supports both single-line and multi-line parameter signatures)
      const funcStartMatch = trimmed.match(/\b(?:(public|protected|private)\s+)?(?:static\s+)?function\s+([A-Za-z0-9_]+)\s*\(/i);
      if (funcStartMatch) {
        const visibility = funcStartMatch[1] || 'public';
        const funcName = funcStartMatch[2];
        const sigWindow = lines.slice(idx, Math.min(lines.length, idx + 8)).map(l => l.trim()).join(' ');
        const fullSigMatch = sigWindow.match(/\bfunction\s+([A-Za-z0-9_]+)\s*\(([^)]*)\)(?:\s*:\s*([?A-Za-z0-9_\\|]+))?/i);
        const params = fullSigMatch ? (fullSigMatch[2] || '').trim().replace(/\s+/g, ' ') : '';
        const returnType = fullSigMatch && fullSigMatch[3] ? `: ${fullSigMatch[3]}` : '';
        const signature = `${funcName}(${params})${returnType}`;

        const record = {
          file: relPath,
          line: lineNum,
          end_line: lineNum,
          type: currentClass ? 'method' : 'function',
          visibility,
          signature,
          class: currentClass || null,
          namespace: currentNamespace
        };
        const fileSym = {
          name: funcName,
          line: lineNum,
          end_line: lineNum,
          type: record.type,
          signature,
          class: currentClass
        };
        addSymbol(index.symbols, funcName, record);
        fileSymbols.push(fileSym);

        // Start tracking brace depth for method end_line
        const { open, delta } = countBraces(line);
        activeMethodRecord = record;
        activeMethodFileSymbol = fileSym;
        methodBraceDepth = delta;
        methodOpenedBrace = open > 0;
        if (methodOpenedBrace && methodBraceDepth <= 0) {
          record.end_line = lineNum;
          fileSym.end_line = lineNum;
          activeMethodRecord = null;
          activeMethodFileSymbol = null;
        }
        continue;
      }

      // Routes: Route::get('path', [Controller::class, 'method']) or Route::name('...')
      const routeMatch = trimmed.match(/\bRoute::(get|post|put|patch|delete|any|resource)\s*\(\s*['"]([^'"]+)['"]/i);
      if (routeMatch) {
        const httpMethod = routeMatch[1].toUpperCase();
        const routeUri = routeMatch[2];
        // Look ahead up to 3 lines for controller action & route name on multi-line route definitions
        const routeWindow = lines.slice(idx, Math.min(lines.length, idx + 4)).join(' ');
        const nameMatch = routeWindow.match(/->name\(\s*['"]([^'"]+)['"]\s*\)/);
        const routeName = nameMatch ? nameMatch[1] : null;
        const actionArrMatch = routeWindow.match(/\[\s*([A-Za-z0-9_\\]+)::class\s*,\s*['"]([^'"]+)['"]\s*\]/);
        const controller = actionArrMatch ? actionArrMatch[1].split('\\').pop() : null;
        const action = actionArrMatch ? actionArrMatch[2] : null;

        const routeEntry = {
          method: httpMethod,
          uri: routeUri,
          name: routeName,
          controller,
          action,
          file: relPath,
          line: lineNum
        };
        index.routes.push(routeEntry);
        if (routeName) {
          addSymbol(index.symbols, routeName, {
            file: relPath,
            line: lineNum,
            type: 'route_name',
            uri: routeUri,
            method: httpMethod,
            controller,
            action
          });
        }
      }
    }

    // ==========================================
    // JavaScript / TypeScript Parsing
    // ==========================================
    else if (['.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs'].includes(ext)) {
      const importMatch = trimmed.match(/^import\s+(?:\{([^}]+)\}|([A-Za-z0-9_]+)|\*\s+as\s+([A-Za-z0-9_]+))\s+from\s+['"]([^'"]+)['"]/i);
      if (importMatch) {
        const source = importMatch[4];
        fileImports.push({ source, line: lineNum, raw: trimmed });
      }

      const alpineMatch = trimmed.match(/\bAlpine\.data\(\s*['"]([^'"]+)['"]/);
      if (alpineMatch) {
        const compName = alpineMatch[1];
        currentClass = compName;
        addSymbol(index.symbols, compName, { file: relPath, line: lineNum, type: 'alpine_component' });
        fileSymbols.push({ name: compName, line: lineNum, type: 'alpine_component' });
      }

      const windowFnMatch = trimmed.match(/\bwindow\.([A-Za-z0-9_]+)\s*=\s*(?:async\s+)?function\s*\(([^)]*)\)/);
      if (windowFnMatch) {
        const fnName = windowFnMatch[1];
        const params = (windowFnMatch[2] || '').trim();
        const signature = `${fnName}(${params})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'function', signature };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'function', signature };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);
        const { open, delta } = countBraces(line);
        activeMethodRecord = record;
        activeMethodFileSymbol = fileSym;
        methodBraceDepth = delta;
        methodOpenedBrace = open > 0;
        continue;
      }

      const classMatch = trimmed.match(/\b(?:export\s+)?(?:default\s+)?class\s+([A-Za-z0-9_]+)/);
      if (classMatch) {
        const className = classMatch[1];
        currentClass = className;
        addSymbol(index.symbols, className, { file: relPath, line: lineNum, type: 'class' });
        fileSymbols.push({ name: className, line: lineNum, type: 'class' });
        continue;
      }

      const fnMatch = trimmed.match(/\b(?:export\s+)?(?:async\s+)?function\s+([A-Za-z0-9_]+)\s*\(([^)]*)\)/);
      if (fnMatch) {
        const fnName = fnMatch[1];
        const params = (fnMatch[2] || '').trim();
        const signature = `${fnName}(${params})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);

        const { open, delta } = countBraces(line);
        activeMethodRecord = record;
        activeMethodFileSymbol = fileSym;
        methodBraceDepth = delta;
        methodOpenedBrace = open > 0;
        continue;
      }

      const arrowMatch = trimmed.match(/\b(?:export\s+)?(?:const|let|var)\s+([A-Za-z0-9_]+)\s*=\s*(?:async\s*)?\(([^)]*)\)\s*=>/);
      if (arrowMatch) {
        const fnName = arrowMatch[1];
        const signature = `${fnName}(${(arrowMatch[2] || '').trim()})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);
        const { open, delta } = countBraces(line);
        if (open > 0 && delta > 0) {
          activeMethodRecord = record;
          activeMethodFileSymbol = fileSym;
          methodBraceDepth = delta;
          methodOpenedBrace = true;
        }
        continue;
      }

      // Object / Alpine / Class method shorthand: methodName(args) {
      const objMethodMatch = trimmed.match(/^(?:async\s+)?([A-Za-z_$][A-Za-z0-9_$]*)\s*\(([^)]*)\)\s*\{$/);
      if (objMethodMatch && !JS_CONTROL_KEYWORDS.has(objMethodMatch[1])) {
        const fnName = objMethodMatch[1];
        const signature = `${fnName}(${(objMethodMatch[2] || '').trim()})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'method', signature, class: currentClass };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'method', signature, class: currentClass };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);
        if (!activeMethodRecord) {
          const { open, delta } = countBraces(line);
          activeMethodRecord = record;
          activeMethodFileSymbol = fileSym;
          methodBraceDepth = delta;
          methodOpenedBrace = open > 0;
        }
        continue;
      }
    }

    // ==========================================
    // Blade Template Parsing (Directives, x-data, Modals, Routes, Inline JS)
    // ==========================================
    else if (ext === '.blade.php') {
      const sectionMatch = trimmed.match(/@(section|push|component|include|extends)\(\s*['"]([^'"]+)['"]/);
      if (sectionMatch) {
        const directive = sectionMatch[1];
        const name = sectionMatch[2];
        if (directive === 'include' || directive === 'extends' || directive === 'component') {
          fileImports.push({ source: `view:${name}`, line: lineNum, raw: `@${directive}('${name}')` });
        } else {
          addSymbol(index.symbols, name, { file: relPath, line: lineNum, type: 'blade_directive' });
        }
      }

      const xDataMatch = trimmed.match(/\bx-data=["']\s*([A-Za-z0-9_]+)\s*\(/);
      if (xDataMatch && xDataMatch[1] !== 'true' && xDataMatch[1] !== 'false') {
        const comp = xDataMatch[1];
        currentClass = comp;
        addSymbol(index.symbols, comp, { file: relPath, line: lineNum, type: 'alpine_xdata' });
        fileSymbols.push({ name: comp, line: lineNum, type: 'alpine_xdata' });
      }

      const modalMatch = trimmed.match(/\bid=["']([^"']*(?:Modal|Drawer|Form|Table|Container|Panel)[^"']*)["']/i);
      if (modalMatch && !/(?:Title|Label|Body|Footer|Close|Btn|Button|Text|Icon)$/i.test(modalMatch[1])) {
        const domId = `#${modalMatch[1]}`;
        addSymbol(index.symbols, domId, { file: relPath, line: lineNum, type: 'dom_id' });
        fileSymbols.push({ name: domId, line: lineNum, type: 'dom_id' });
      }

      const bladeFnMatch = trimmed.match(/^(?:async\s+)?function\s+([A-Za-z0-9_]+)\s*\(([^)]*)\)/);
      if (bladeFnMatch) {
        const fnName = bladeFnMatch[1];
        const signature = `${fnName}(${(bladeFnMatch[2] || '').trim()})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'function', signature, class: currentClass };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);
        const { open, delta } = countBraces(line);
        activeMethodRecord = record;
        activeMethodFileSymbol = fileSym;
        methodBraceDepth = delta;
        methodOpenedBrace = open > 0;
      }

      const bladeMethodMatch = trimmed.match(/^(?:async\s+)?([A-Za-z_$][A-Za-z0-9_$]*)\s*\(([^)]*)\)\s*\{$/);
      if (bladeMethodMatch && !JS_CONTROL_KEYWORDS.has(bladeMethodMatch[1])) {
        const fnName = bladeMethodMatch[1];
        const signature = `${fnName}(${(bladeMethodMatch[2] || '').trim()})`;
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'method', signature, class: currentClass };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'method', signature, class: currentClass };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);
        if (!activeMethodRecord) {
          const { open, delta } = countBraces(line);
          activeMethodRecord = record;
          activeMethodFileSymbol = fileSym;
          methodBraceDepth = delta;
          methodOpenedBrace = open > 0;
        }
      }
    }

    // ==========================================
    // CSS Stylesheet Parsing (Selectors & Tokens)
    // ==========================================
    else if (ext === '.css') {
      const tokenMatch = trimmed.match(/^(--[A-Za-z0-9_-]+)\s*:/);
      if (tokenMatch) {
        const tokenName = tokenMatch[1];
        addSymbol(index.symbols, tokenName, { file: relPath, line: lineNum, type: 'css_token' });
        fileSymbols.push({ name: tokenName, line: lineNum, type: 'css_token' });
        continue;
      }

      const selectorMatch = trimmed.match(/^([.#][A-Za-z0-9_-]+)(?:\s*[,{:]|\s+[.#A-Za-z])/);
      if (selectorMatch && trimmed.includes('{')) {
        const sel = selectorMatch[1];
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'css_selector' };
        const fileSym = { name: sel, line: lineNum, end_line: lineNum, type: 'css_selector' };
        addSymbol(index.symbols, sel, record);
        fileSymbols.push(fileSym);
        if (!activeMethodRecord) {
          const { open, delta } = countBraces(line);
          activeMethodRecord = record;
          activeMethodFileSymbol = fileSym;
          methodBraceDepth = delta;
          methodOpenedBrace = open > 0;
        }
      }
    }

    // ==========================================
    // Python Parsing
    // ==========================================
    else if (ext === '.py') {
      const pyClassMatch = trimmed.match(/^class\s+([A-Za-z0-9_]+)/);
      if (pyClassMatch) {
        const className = pyClassMatch[1];
        currentClass = className;
        addSymbol(index.symbols, className, { file: relPath, line: lineNum, type: 'class' });
        fileSymbols.push({ name: className, line: lineNum, type: 'class' });
        continue;
      }

      const pyDefMatch = trimmed.match(/^def\s+([A-Za-z0-9_]+)\s*\(/);
      if (pyDefMatch) {
        const fnName = pyDefMatch[1];
        addSymbol(index.symbols, fnName, { file: relPath, line: lineNum, type: currentClass ? 'method' : 'function', class: currentClass });
        fileSymbols.push({ name: fnName, line: lineNum, type: 'function' });
      }
    }
  }

  // Extract 1-level-down calls for each method/function in this file
  for (const sym of fileSymbols) {
    if ((sym.type === 'method' || sym.type === 'function') && sym.line) {
      const endLine = sym.end_line && sym.end_line >= sym.line ? sym.end_line : Math.min(lines.length, sym.line + 35);
      const blockCalls = extractBlockCalls(lines.slice(sym.line - 1, endLine), ext);
      if (blockCalls.length > 0) {
        sym.calls = blockCalls;
        const symList = index.symbols[sym.name] || [];
        for (const rec of symList) {
          if (rec.file === relPath && rec.line === sym.line) {
            rec.calls = blockCalls;
          }
        }
      }
    }
  }

  // Also capture file-level route('...') references in Blade/JS files
  if (ext === '.blade.php' || ext === '.js') {
    const fileRouteCalls = extractBlockCalls(lines, ext).filter(c => c.startsWith('route:') || c.startsWith('fetch:') || c.startsWith('axios.'));
    if (fileRouteCalls.length > 0) {
      fileRouteCalls.forEach(rc => fileImports.push({ source: rc, line: 1 }));
    }
  }

  // Extract Eloquent Model DNA if PHP Model
  if (ext === '.php' && currentClass) {
    const modelMeta = extractEloquentModelMeta(content, relPath, currentClass, currentNamespace, fileSymbols);
    if (modelMeta) {
      index.models[currentClass] = modelMeta;
    }
  }

  // Compute 1-line file role description for fast Code-Navigation Hypothesize & Output
  const methodCount = fileSymbols.filter(s => s.type === 'method' || s.type === 'function').length;
  if (relPath.startsWith('app/Http/Controllers/')) {
    index.file_roles[relPath] = `Laravel Controller (${currentClass || path.basename(relPath, '.php')}: ${methodCount} action/helper methods)`;
  } else if (relPath.startsWith('app/Models/') || (currentClass && index.models[currentClass])) {
    const m = index.models[currentClass];
    index.file_roles[relPath] = `Eloquent Model (${currentClass}, table: ${m && m.table ? m.table : 'default'}, ${m ? m.relationships.length : 0} relations)`;
  } else if (relPath.startsWith('app/Services/') || relPath.startsWith('app/Actions/')) {
    index.file_roles[relPath] = `Domain Service/Action (${currentClass || path.basename(relPath, '.php')}: ${methodCount} methods)`;
  } else if (relPath.startsWith('routes/')) {
    index.file_roles[relPath] = `Laravel Route Definitions (${path.basename(relPath)})`;
  } else if (ext === '.blade.php') {
    const modals = fileSymbols.filter(s => s.type === 'dom_id').length;
    index.file_roles[relPath] = `Blade View Template (${methodCount} inline JS handlers, ${modals} modal/container IDs)`;
  } else if (['.js', '.ts', '.mjs', '.cjs'].includes(ext)) {
    index.file_roles[relPath] = `Frontend JS Module (${currentClass ? `Alpine/Class ${currentClass}, ` : ''}${methodCount} functions/methods)`;
  } else if (ext === '.css') {
    const selCount = fileSymbols.filter(s => s.type === 'css_selector').length;
    const tokCount = fileSymbols.filter(s => s.type === 'css_token').length;
    index.file_roles[relPath] = `CSS Stylesheet (${selCount} selectors, ${tokCount} CSS custom properties)`;
  } else if (relPath.startsWith('database/migrations/')) {
    index.file_roles[relPath] = `Database Schema Migration (${path.basename(relPath)})`;
  } else if (relPath.startsWith('tests/')) {
    index.file_roles[relPath] = `Automated Test Suite (${currentClass || path.basename(relPath)}: ${methodCount} tests)`;
  } else {
    index.file_roles[relPath] = `Source Module (${ext} file, ${fileSymbols.length} indexed symbols)`;
  }

  if (fileImports.length > 0) {
    index.file_imports[relPath] = fileImports;
  }
  if (fileSymbols.length > 0) {
    index.file_symbols[relPath] = fileSymbols;
  }
}

function addSymbol(symbolMap, name, record) {
  if (!symbolMap[name]) {
    symbolMap[name] = [];
  }
  symbolMap[name].push(record);
}

/**
 * Post-processes 1-level-up caller graph (`called_by`) and reverse import map (`imported_by`).
 */
function buildDependencyGraphs(index) {
  const calledBy = {};
  const importedBy = {};

  const addCaller = (targetSym, entry) => {
    if (!targetSym) return;
    if (!calledBy[targetSym]) calledBy[targetSym] = [];
    if (calledBy[targetSym].length < 25) {
      const exists = calledBy[targetSym].some(
        e => e.file === entry.file && e.caller === entry.caller && e.line === entry.line
      );
      if (!exists) calledBy[targetSym].push(entry);
    }
  };

  // 1. Reverse file_imports -> imported_by
  for (const [file, imports] of Object.entries(index.file_imports)) {
    for (const imp of imports) {
      const key = imp.alias || (imp.full ? imp.full.split('\\').pop() : imp.source);
      if (!key) continue;
      if (!importedBy[key]) importedBy[key] = [];
      if (importedBy[key].length < 30 && !importedBy[key].includes(file)) {
        importedBy[key].push(file);
      }
      if (imp.source && imp.source.startsWith('route:')) {
        const routeName = imp.source.slice(6);
        addCaller(routeName, { caller: 'Blade/JS View Reference', file, line: imp.line || 1, via: `route('${routeName}')` });
      }
    }
  }

  // 2. Routes -> Controller@action (1-level-up HTTP entrypoint)
  for (const r of index.routes) {
    if (r.action) {
      addCaller(r.action, {
        caller: `${r.method} /${r.uri.replace(/^\//, '')}`,
        file: r.file,
        line: r.line,
        via: r.name ? `route('${r.name}') -> ${r.controller}@${r.action}` : `${r.controller}@${r.action}`
      });
    }
  }

  // 3. Method/Function 1-level-down `calls` -> invert into 1-level-up `called_by`
  for (const [file, syms] of Object.entries(index.file_symbols)) {
    for (const sym of syms) {
      if (!sym.calls || sym.calls.length === 0) continue;
      const callerLabel = sym.class ? `${sym.class}::${sym.name}` : sym.name;
      for (const rawCall of sym.calls) {
        let targetName = rawCall;
        if (rawCall.startsWith('$this->')) targetName = rawCall.slice(7);
        else if (rawCall.startsWith('this.')) targetName = rawCall.slice(5);
        else if (rawCall.includes('::')) targetName = rawCall.split('::').pop();
        else if (rawCall.includes('->')) targetName = rawCall.split('->').pop();
        else if (rawCall.startsWith('route:')) targetName = rawCall.slice(6);

        addCaller(targetName, {
          caller: callerLabel,
          file,
          line: sym.line,
          via: rawCall
        });
      }
    }
  }

  index.imported_by = importedBy;
  index.call_graph = { called_by: calledBy };
}

function buildIndex(outputPath = DEFAULT_OUTPUT) {
  const startTime = Date.now();
  console.log('📖 [The Librarian] Building Advance Index v3.0 (Symbols + Call Graph + Eloquent DNA + UI/CSS)...');

  const index = {
    metadata: {
      generated_at: new Date().toISOString(),
      root_dir: ROOT_DIR,
      version: '3.0.0',
      total_symbols: 0,
      total_unique_symbols: 0,
      total_files_indexed: 0,
      total_routes: 0,
      total_models: 0,
      duration_ms: 0
    },
    symbols: {},
    models: {},
    routes: [],
    file_roles: {},
    file_imports: {},
    imported_by: {},
    file_symbols: {},
    call_graph: { called_by: {} }
  };

  const files = collectFiles();
  for (const f of files) {
    parseFile(f, index);
  }

  buildDependencyGraphs(index);

  const duration = Date.now() - startTime;
  const uniqueSymbols = Object.keys(index.symbols).length;
  let totalSymbolEntries = 0;
  for (const list of Object.values(index.symbols)) {
    totalSymbolEntries += list.length;
  }
  const totalModels = Object.keys(index.models).length;

  index.metadata.total_symbols = totalSymbolEntries;
  index.metadata.total_unique_symbols = uniqueSymbols;
  index.metadata.total_files_indexed = files.length;
  index.metadata.total_routes = index.routes.length;
  index.metadata.total_models = totalModels;
  index.metadata.duration_ms = duration;

  const targetDir = path.dirname(outputPath);
  if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
  }

  fs.writeFileSync(outputPath, JSON.stringify(index, null, 2), 'utf8');

  console.log(`✅ [The Librarian] Advance Index v3.0 built in ${duration}ms!`);
  console.log(`   - Indexed Files    : ${files.length} (with 1-line roles)`);
  console.log(`   - Unique Symbols   : ${uniqueSymbols} (${totalSymbolEntries} total refs: PHP, JS/Alpine, Blade, CSS)`);
  console.log(`   - Eloquent Models  : ${totalModels} (with $fillable, $casts & relationships)`);
  console.log(`   - Laravel Routes   : ${index.routes.length}`);
  console.log(`   - Call Graph Nodes : ${Object.keys(index.call_graph.called_by).length} 1-level-up caller targets`);
  console.log(`   - Artifact Output  : ${outputPath}\n`);

  return index;
}

if (require.main === module) {
  const args = process.argv.slice(2);
  let outPath = DEFAULT_OUTPUT;
  const outIdx = args.indexOf('--output');
  if (outIdx !== -1 && args[outIdx + 1]) {
    outPath = path.resolve(args[outIdx + 1]);
  }
  buildIndex(outPath);
}

module.exports = { buildIndex, DEFAULT_OUTPUT };

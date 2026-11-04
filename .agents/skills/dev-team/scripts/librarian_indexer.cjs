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
  '.blade.php', '.py', '.go', '.json'
]);

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

      const classMatch = trimmed.match(/\b(?:export\s+)?(?:default\s+)?class\s+([A-Za-z0-9_]+)/);
      if (classMatch) {
        const className = classMatch[1];
        currentClass = className;
        addSymbol(index.symbols, className, { file: relPath, line: lineNum, type: 'class' });
        fileSymbols.push({ name: className, line: lineNum, type: 'class' });
        continue;
      }

      const fnMatch = trimmed.match(/\b(?:export\s+)?(?:async\s+)?function\s+([A-Za-z0-9_]+)\s*\(/);
      if (fnMatch) {
        const fnName = fnMatch[1];
        const record = { file: relPath, line: lineNum, end_line: lineNum, type: 'function' };
        const fileSym = { name: fnName, line: lineNum, end_line: lineNum, type: 'function' };
        addSymbol(index.symbols, fnName, record);
        fileSymbols.push(fileSym);

        const { open, delta } = countBraces(line);
        activeMethodRecord = record;
        activeMethodFileSymbol = fileSym;
        methodBraceDepth = delta;
        methodOpenedBrace = open > 0;
        continue;
      }

      const arrowMatch = trimmed.match(/\b(?:export\s+)?(?:const|let|var)\s+([A-Za-z0-9_]+)\s*=\s*(?:async\s*)?\([^)]*\)\s*=>/);
      if (arrowMatch) {
        const fnName = arrowMatch[1];
        addSymbol(index.symbols, fnName, { file: relPath, line: lineNum, type: 'function' });
        fileSymbols.push({ name: fnName, line: lineNum, type: 'function' });
        continue;
      }
    }

    // ==========================================
    // Blade Template Parsing
    // ==========================================
    else if (ext === '.blade.php') {
      const sectionMatch = trimmed.match(/@(section|push|component)\(\s*['"]([^'"]+)['"]/);
      if (sectionMatch) {
        const name = sectionMatch[2];
        addSymbol(index.symbols, name, { file: relPath, line: lineNum, type: 'blade_directive' });
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

  // Extract Eloquent Model DNA if PHP Model
  if (ext === '.php' && currentClass) {
    const modelMeta = extractEloquentModelMeta(content, relPath, currentClass, currentNamespace, fileSymbols);
    if (modelMeta) {
      index.models[currentClass] = modelMeta;
    }
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

function buildIndex(outputPath = DEFAULT_OUTPUT) {
  const startTime = Date.now();
  console.log('📖 [The Librarian] Scanning repository and building global symbol + Eloquent DNA index...');

  const index = {
    metadata: {
      generated_at: new Date().toISOString(),
      root_dir: ROOT_DIR,
      version: '2.0.0',
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
    file_imports: {},
    file_symbols: {}
  };

  const files = collectFiles();
  for (const f of files) {
    parseFile(f, index);
  }

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

  console.log(`✅ [The Librarian] Symbol + Eloquent DNA index v2.0 built in ${duration}ms!`);
  console.log(`   - Indexed Files    : ${files.length}`);
  console.log(`   - Unique Symbols   : ${uniqueSymbols} (${totalSymbolEntries} total refs)`);
  console.log(`   - Eloquent Models  : ${totalModels} (with $fillable, $casts & relationships)`);
  console.log(`   - Laravel Routes   : ${index.routes.length}`);
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

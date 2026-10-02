#!/usr/bin/env node
/**
 * The Librarian: Global Structural Codebase Indexer
 * Part of the AEC DevTeam System (/dev-team)
 * 
 * Generates .agents/artifacts/librarian_index.json mapping all symbols
 * (classes, methods, functions, routes, imports) to exact file paths and line numbers.
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

  for (let idx = 0; idx < lines.length; idx++) {
    const lineNum = idx + 1;
    const line = lines[idx];
    const trimmed = line.trim();
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
      if (useMatch) {
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

      // Methods / Functions
      const funcMatch = trimmed.match(/\b(?:(public|protected|private)\s+)?(?:static\s+)?function\s+([A-Za-z0-9_]+)\s*\(/i);
      if (funcMatch) {
        const visibility = funcMatch[1] || 'public';
        const funcName = funcMatch[2];
        const record = {
          file: relPath,
          line: lineNum,
          type: currentClass ? 'method' : 'function',
          visibility,
          class: currentClass || null,
          namespace: currentNamespace
        };
        addSymbol(index.symbols, funcName, record);
        fileSymbols.push({ name: funcName, line: lineNum, type: record.type, class: currentClass });
        continue;
      }

      // Routes: Route::get('path', [Controller::class, 'method']) or Route::name('...')
      const routeMatch = trimmed.match(/\bRoute::(get|post|put|patch|delete|resource)\s*\(\s*['"]([^'"]+)['"]/i);
      if (routeMatch) {
        const httpMethod = routeMatch[1].toUpperCase();
        const routeUri = routeMatch[2];
        const nameMatch = trimmed.match(/->name\(\s*['"]([^'"]+)['"]\s*\)/);
        const routeName = nameMatch ? nameMatch[1] : null;

        index.routes.push({
          method: httpMethod,
          uri: routeUri,
          name: routeName,
          file: relPath,
          line: lineNum
        });
        if (routeName) {
          addSymbol(index.symbols, routeName, { file: relPath, line: lineNum, type: 'route_name', uri: routeUri });
        }
      }
    }

    // ==========================================
    // JavaScript / TypeScript Parsing
    // ==========================================
    else if (['.js', '.ts', '.jsx', '.tsx', '.mjs', '.cjs'].includes(ext)) {
      // Imports
      const importMatch = trimmed.match(/^import\s+(?:\{([^}]+)\}|([A-Za-z0-9_]+)|\*\s+as\s+([A-Za-z0-9_]+))\s+from\s+['"]([^'"]+)['"]/i);
      if (importMatch) {
        const source = importMatch[4];
        fileImports.push({ source, line: lineNum, raw: trimmed });
      }

      // Classes
      const classMatch = trimmed.match(/\b(?:export\s+)?(?:default\s+)?class\s+([A-Za-z0-9_]+)/);
      if (classMatch) {
        const className = classMatch[1];
        currentClass = className;
        addSymbol(index.symbols, className, { file: relPath, line: lineNum, type: 'class' });
        fileSymbols.push({ name: className, line: lineNum, type: 'class' });
        continue;
      }

      // Functions
      const fnMatch = trimmed.match(/\b(?:export\s+)?(?:async\s+)?function\s+([A-Za-z0-9_]+)\s*\(/);
      if (fnMatch) {
        const fnName = fnMatch[1];
        addSymbol(index.symbols, fnName, { file: relPath, line: lineNum, type: 'function' });
        fileSymbols.push({ name: fnName, line: lineNum, type: 'function' });
        continue;
      }

      // Const / let arrow functions
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
      // Named sections & pushes
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
  console.log('📖 [The Librarian] Scanning repository and building global symbol index...');

  const index = {
    metadata: {
      generated_at: new Date().toISOString(),
      root_dir: ROOT_DIR,
      version: '1.0.0',
      total_symbols: 0,
      total_unique_symbols: 0,
      total_files_indexed: 0,
      total_routes: 0,
      duration_ms: 0
    },
    symbols: {},
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

  index.metadata.total_symbols = totalSymbolEntries;
  index.metadata.total_unique_symbols = uniqueSymbols;
  index.metadata.total_files_indexed = files.length;
  index.metadata.total_routes = index.routes.length;
  index.metadata.duration_ms = duration;

  // Ensure directory exists
  const targetDir = path.dirname(outputPath);
  if (!fs.existsSync(targetDir)) {
    fs.mkdirSync(targetDir, { recursive: true });
  }

  fs.writeFileSync(outputPath, JSON.stringify(index, null, 2), 'utf8');

  console.log(`✅ [The Librarian] Symbol index successfully built in ${duration}ms!`);
  console.log(`   - Indexed Files    : ${files.length}`);
  console.log(`   - Unique Symbols   : ${uniqueSymbols}`);
  console.log(`   - Total References : ${totalSymbolEntries}`);
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

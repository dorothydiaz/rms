#!/usr/bin/env node
/**
 * The Librarian: Runtime Context Router & Cartographer
 * Part of the AEC DevTeam System (/dev-team)
 * 
 * Handles STAGE 0: Context Hydration & Cartography Gate.
 * Hydrates workspace state, performs O(1) symbol resolution from librarian_index.json,
 * and emits the standardized Librarian Cartography Header.
 * 
 * Usage:
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs resolve "<query>" [--file <active_file>]
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs symbol <symbol_name>
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs imports <filepath>
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT_DIR = process.cwd();
const INDEX_PATH = path.join(ROOT_DIR, '.agents', 'artifacts', 'librarian_index.json');

function loadIndex() {
  if (!fs.existsSync(INDEX_PATH)) {
    // Attempt automatic generation if missing
    try {
      const { buildIndex } = require('./librarian_indexer.cjs');
      return buildIndex(INDEX_PATH);
    } catch (e) {
      console.error('Error: librarian_index.json not found and could not be built automatically.');
      process.exit(1);
    }
  }
  try {
    return JSON.parse(fs.readFileSync(INDEX_PATH, 'utf8'));
  } catch (e) {
    console.error(`Error parsing index at ${INDEX_PATH}: ${e.message}`);
    process.exit(1);
  }
}

function getGitSummary() {
  try {
    const status = execSync('git status --short', { cwd: ROOT_DIR, encoding: 'utf8' }).trim();
    if (!status) return 'Clean working tree (no uncommitted changes)';
    const lines = status.split('\n');
    return `${lines.length} uncommitted file(s):\n` + lines.slice(0, 5).map(l => `  ${l}`).join('\n');
  } catch (e) {
    return 'Git status unavailable';
  }
}

function tokenize(text) {
  return text
    .replace(/([a-z])([A-Z])/g, '$1 $2')
    .replace(/[_\-\.\/\\:()\[\]'"]/g, ' ')
    .toLowerCase()
    .split(/\s+/)
    .filter(t => t.length > 2);
}

function resolveQuery(query, activeFile = null) {
  const index = loadIndex();
  const tokens = tokenize(query);
  const matchedSymbols = {};
  const matchedFiles = new Set();

  // 1. Direct O(1) lookup on query tokens against index
  for (const rawWord of query.split(/\s+/)) {
    const clean = rawWord.replace(/[^A-Za-z0-9_]/g, '');
    if (clean && index.symbols[clean]) {
      matchedSymbols[clean] = index.symbols[clean];
      index.symbols[clean].forEach(entry => matchedFiles.add(entry.file));
    }
  }

  // 2. Case-insensitive token lookup if exact didn't match
  const lowerSymbolMap = new Map();
  Object.keys(index.symbols).forEach(k => lowerSymbolMap.set(k.toLowerCase(), k));

  for (const token of tokens) {
    if (lowerSymbolMap.has(token)) {
      const exactKey = lowerSymbolMap.get(token);
      if (!matchedSymbols[exactKey]) {
        matchedSymbols[exactKey] = index.symbols[exactKey];
        index.symbols[exactKey].forEach(entry => matchedFiles.add(entry.file));
      }
    }
  }

  // 3. Resolve active file details if provided
  let activeFileInfo = null;
  if (activeFile) {
    const relActive = path.relative(ROOT_DIR, path.resolve(ROOT_DIR, activeFile)).replace(/\\/g, '/');
    const imports = index.file_imports[relActive] || [];
    const symbols = index.file_symbols[relActive] || [];
    activeFileInfo = {
      file: relActive,
      defined_symbols: symbols.map(s => `${s.type} ${s.name} (L:${s.line})`),
      imports: imports.map(i => i.full || i.source)
    };
  }

  // 4. Output the standardized Stage 0 Librarian Cartography Header
  console.log('\n================================================================');
  console.log('🏛️  [THE LIBRARIAN] — STAGE 0 CONTEXT HYDRATION & CARTOGRAPHY GATE');
  console.log('================================================================');
  
  if (activeFileInfo) {
    console.log(`📌 ACTIVE SCOPE: ${activeFileInfo.file}`);
    if (activeFileInfo.defined_symbols.length > 0) {
      console.log(`   Defined In File : ${activeFileInfo.defined_symbols.slice(0, 8).join(', ')}`);
    }
    if (activeFileInfo.imports.length > 0) {
      console.log(`   Dependencies    : ${activeFileInfo.imports.slice(0, 6).join(', ')}`);
    }
  } else {
    console.log('📌 ACTIVE SCOPE: Global Repository Context');
  }

  console.log(`\n🔄 WORKSPACE STATE (Git Buffer):\n${getGitSummary()}`);

  const symbolKeys = Object.keys(matchedSymbols);
  console.log(`\n🎯 PRE-MATCHED SYMBOLS (${symbolKeys.length} match(es) for query):`);
  if (symbolKeys.length === 0) {
    console.log('   (No direct symbol matches found. Route to Pillar 1 Ripgrep or Pillar 2 BM25.)');
  } else {
    symbolKeys.forEach(sym => {
      console.log(`   • ${sym}:`);
      matchedSymbols[sym].slice(0, 3).forEach(loc => {
        const extra = loc.class ? ` [${loc.class}]` : (loc.namespace ? ` [${loc.namespace}]` : '');
        console.log(`       ↳ ${loc.file}:${loc.line} (${loc.type}${extra})`);
      });
      if (matchedSymbols[sym].length > 3) {
        console.log(`       ↳ ... and ${matchedSymbols[sym].length - 3} more definition(s)`);
      }
    });
  }

  if (matchedFiles.size > 0) {
    console.log(`\n📂 TARGET CANDIDATE FILES (${matchedFiles.size}):`);
    Array.from(matchedFiles).slice(0, 6).forEach(f => console.log(`   - ${f}`));
  }

  console.log('\n🧭 CARTOGRAPHY DIRECTIVE FOR DOWNSTREAM PERSONAS:');
  console.log('   - [Senior BA] & [Devs]: Target the exact lines listed above. DO NOT scan files sequentially.');
  console.log('   - Read slices limited to ≤ 80 lines using view_file(StartLine, EndLine).');
  console.log('================================================================\n');
}

function lookupSymbol(symbolName) {
  const index = loadIndex();
  const matches = index.symbols[symbolName];
  if (!matches || matches.length === 0) {
    // Try case-insensitive
    const lower = symbolName.toLowerCase();
    const found = Object.keys(index.symbols).find(k => k.toLowerCase() === lower);
    if (found) {
      return lookupSymbol(found);
    }
    console.log(`Symbol "${symbolName}" not found in librarian_index.json.`);
    return;
  }

  console.log(`\n=== The Librarian: Symbol Lookup for "${symbolName}" (${matches.length} location(s)) ===`);
  matches.forEach(m => {
    const parent = m.class ? ` in ${m.class}` : '';
    console.log(`- ${m.file}:${m.line} [${m.type}${parent}]`);
  });
  console.log('=====================================================================\n');
}

function inspectImports(filePath) {
  const index = loadIndex();
  const relPath = path.relative(ROOT_DIR, path.resolve(ROOT_DIR, filePath)).replace(/\\/g, '/');
  const imports = index.file_imports[relPath];

  console.log(`\n=== The Librarian: Dependency Graph for "${relPath}" ===`);
  if (!imports || imports.length === 0) {
    console.log('No registered imports/use statements in this file.');
  } else {
    imports.forEach(imp => console.log(`- ${imp.full || imp.source} (L:${imp.line || '?'})`));
  }
  console.log('=======================================================\n');
}

// CLI Dispatcher
const args = process.argv.slice(2);
const command = args[0];

if (!command || command === '--help' || command === '-h') {
  console.log(`
The Librarian: Runtime Context Router & Cartographer
Usage:
  node librarian_router.cjs resolve "<query>" [--file <active_file>]
  node librarian_router.cjs symbol <symbol_name>
  node librarian_router.cjs imports <filepath>
`);
  process.exit(0);
}

switch (command) {
  case 'resolve': {
    let query = args[1] || '';
    let activeFile = null;
    const fileIdx = args.indexOf('--file');
    if (fileIdx !== -1 && args[fileIdx + 1]) {
      activeFile = args[fileIdx + 1];
    }
    resolveQuery(query, activeFile);
    break;
  }
  case 'symbol':
  case 'lookup':
    lookupSymbol(args[1]);
    break;
  case 'imports':
  case 'deps':
    inspectImports(args[1]);
    break;
  default:
    console.error(`Unknown command: ${command}. Use --help.`);
    process.exit(1);
}

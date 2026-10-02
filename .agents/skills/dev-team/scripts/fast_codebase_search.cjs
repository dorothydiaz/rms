#!/usr/bin/env node
/**
 * Fast Codebase Search & AST Outlining Utility
 * Part of the AEC Token Efficiency Engine (/dev-team)
 * 
 * Commands:
 *   node fast_codebase_search.cjs bm25 "query tokens" [--limit 7]
 *   node fast_codebase_search.cjs ast path/to/file.php
 *   node fast_codebase_search.cjs rg "regex pattern"
 *   node fast_codebase_search.cjs map
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT_DIR = process.cwd();
const IGNORED_DIRS = new Set([
  'node_modules', 'vendor', '.git', 'storage', 'bootstrap/cache',
  'public/build', 'public/storage', '.idea', '.vscode'
]);

// -------------------------------------------------------------
// 1. BM25 File Tree & Path Indexer
// -------------------------------------------------------------
function collectRepoFiles(dir = ROOT_DIR, fileList = []) {
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
        collectRepoFiles(fullPath, fileList);
      }
    } else if (entry.isFile()) {
      fileList.push(relPath);
    }
  }
  return fileList;
}

function tokenize(text) {
  return text
    .replace(/([a-z])([A-Z])/g, '$1 $2') // split camelCase
    .replace(/[_\-\.\/\\:]/g, ' ')      // split delimiters
    .toLowerCase()
    .split(/\s+/)
    .filter(t => t.length > 1);
}

function runBM25(query, limit = 7) {
  const files = collectRepoFiles();
  const queryTokens = tokenize(query);

  if (queryTokens.length === 0) {
    console.log('No valid search tokens provided.');
    return;
  }

  // Build document representations
  const docs = files.map(file => {
    const tokens = tokenize(file);
    const tf = new Map();
    for (const t of tokens) {
      tf.set(t, (tf.get(t) || 0) + 1);
    }
    return { file, tokens, tf, len: tokens.length };
  });

  const N = docs.length;
  const avgdl = docs.reduce((acc, d) => acc + d.len, 0) / (N || 1);
  const k1 = 1.5;
  const b = 0.75;

  // Compute Document Frequency (DF)
  const df = new Map();
  for (const qt of queryTokens) {
    let count = 0;
    for (const d of docs) {
      if (d.tf.has(qt)) count++;
    }
    df.set(qt, count);
  }

  // Score each document with BM25
  const scored = [];
  for (const d of docs) {
    let score = 0;
    for (const qt of queryTokens) {
      const docFreq = df.get(qt) || 0;
      if (docFreq === 0) continue;
      const idf = Math.log(1 + (N - docFreq + 0.5) / (docFreq + 0.5));
      const termFreq = d.tf.get(qt) || 0;
      const num = termFreq * (k1 + 1);
      const denom = termFreq + k1 * (1 - b + b * (d.len / avgdl));
      score += idf * (num / denom);
    }
    if (score > 0) {
      scored.push({ file: d.file, score });
    }
  }

  scored.sort((a, b) => b.score - a.score);
  const top = scored.slice(0, limit);

  console.log(`\n=== BM25 File Tree Search: "${query}" ===`);
  if (top.length === 0) {
    console.log('No matching files found. Fallback to ripgrep (rg).');
    return;
  }
  top.forEach((res, idx) => {
    console.log(`[${idx + 1}] (${res.score.toFixed(3)}) ${res.file}`);
  });
  console.log('============================================\n');
}

// -------------------------------------------------------------
// 2. Structural AST / Outline Extractor
// -------------------------------------------------------------
function extractAstOutline(filePath) {
  if (!filePath) {
    console.error('Error: Please provide a target file path.');
    return;
  }

  const fullPath = path.isAbsolute(filePath) ? filePath : path.join(ROOT_DIR, filePath);
  if (!fs.existsSync(fullPath)) {
    console.error(`File not found: ${filePath}`);
    return;
  }

  const content = fs.readFileSync(fullPath, 'utf8');
  const lines = content.split(/\r?\n/);
  const ext = path.extname(filePath).toLowerCase();

  console.log(`\n=== Structural AST Outline: ${filePath} (${lines.length} lines total) ===`);

  const outline = [];

  lines.forEach((line, idx) => {
    const lineNum = idx + 1;
    const trimmed = line.trim();

    // PHP / Classes / Methods
    if (ext === '.php') {
      if (/^(namespace|use)\s+[^;]+;/i.test(trimmed) && lineNum <= 25) {
        outline.push({ line: lineNum, text: trimmed });
      } else if (/\b(class|interface|trait|enum)\s+[A-Za-z0-9_]+/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `[DECLARATION] ${trimmed.replace(/\{.*/, '')}` });
      } else if (/\b(public|protected|private)?\s*(static\s+)?function\s+[A-Za-z0-9_]+\s*\(/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `  ↳ [METHOD L:${lineNum}] ${trimmed.replace(/\{.*/, '')}` });
      } else if (/\bRoute::(get|post|put|patch|delete|resource)\s*\(/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `[ROUTE L:${lineNum}] ${trimmed}` });
      }
    }
    // JS / TS / Vue
    else if (['.js', '.ts', '.jsx', '.tsx', '.mjs', '.vue', '.cjs'].includes(ext)) {
      if (/^import\s+.*from\s+['"][^'"]+['"]/i.test(trimmed) && lineNum <= 20) {
        outline.push({ line: lineNum, text: trimmed });
      } else if (/\b(export\s+)?(class|interface|type|enum)\s+[A-Za-z0-9_]+/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `[TYPE/CLASS] ${trimmed.replace(/\{.*/, '')}` });
      } else if (/\b(export\s+)?(async\s+)?function\s*[A-Za-z0-9_]*\s*\(/i.test(trimmed) ||
                 /\b(const|let|var)\s+[A-Za-z0-9_]+\s*=\s*(async\s*)?\([^)]*\)\s*=>/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `  ↳ [FUNCTION L:${lineNum}] ${trimmed.slice(0, 80)}` });
      }
    }
    // Blade Templates / HTML
    else if (ext === '.blade.php' || ext === '.html') {
      if (/@(extends|section|push|component|include|slot)\b/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `[DIRECTIVE L:${lineNum}] ${trimmed}` });
      } else if (/<form\b|<table\b|<x-[a-z0-9\-_.]+/i.test(trimmed)) {
        outline.push({ line: lineNum, text: `  ↳ [BLOCK L:${lineNum}] ${trimmed.slice(0, 80)}` });
      }
    }
  });

  if (outline.length === 0) {
    console.log('No top-level structural declarations detected.');
  } else {
    outline.forEach(item => console.log(`${String(item.line).padStart(4)}: ${item.text}`));
  }
  console.log('=========================================================\n');
}

// -------------------------------------------------------------
// 3. Fast Ripgrep / Git Grep Invoker
// -------------------------------------------------------------
function runRipgrep(pattern) {
  if (!pattern) {
    console.error('Error: Please provide a regex search pattern.');
    return;
  }

  // Use git grep which respects .gitignore and runs in milliseconds
  const cmd = `git grep -n -I -i -E "${pattern.replace(/"/g, '\\"')}"`;

  try {
    const output = execSync(cmd, { cwd: ROOT_DIR, encoding: 'utf8', maxBuffer: 10 * 1024 * 1024 });
    const lines = output.trim().split('\n').filter(Boolean);
    console.log(`\n=== Fast Ripgrep Results for "${pattern}" (${lines.length} matches) ===`);
    lines.slice(0, 30).forEach(l => console.log(l));
    if (lines.length > 30) {
      console.log(`... and ${lines.length - 30} more matches. Narrow search with specific tokens.`);
    }
    console.log('===================================================\n');
  } catch (err) {
    console.log(`No matches found for pattern "${pattern}".`);
  }
}

// -------------------------------------------------------------
// 4. Lightweight Map
// -------------------------------------------------------------
function generateRepoMap() {
  const files = collectRepoFiles();
  const summary = {};
  for (const f of files) {
    const topDir = f.includes('/') ? f.split('/')[0] : '(root)';
    summary[topDir] = (summary[topDir] || 0) + 1;
  }
  console.log(`\n=== Repository Map (${files.length} total tracked files) ===`);
  for (const [dir, count] of Object.entries(summary)) {
    console.log(`- ${dir.padEnd(25)} : ${count} files`);
  }
  console.log('====================================================\n');
}

// -------------------------------------------------------------
// CLI Dispatcher
// -------------------------------------------------------------
const args = process.argv.slice(2);
const command = args[0];

if (!command || command === '--help' || command === '-h') {
  console.log(`
AEC Fast Codebase Search & Structural AST Engine
Usage:
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs bm25 <query>      Rank repository files using BM25
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs ast <filepath>    Extract class/method structural outline
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs rg <pattern>      Fast keyword / regex ripgrep search
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs symbol <name>     Instant O(1) Librarian symbol lookup
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs index             Rebuild Librarian global symbol index
  node .agents/skills/dev-team/scripts/fast_codebase_search.cjs map               Print top-level repository file count map
`);
  process.exit(0);
}

switch (command) {
  case 'bm25':
    runBM25(args.slice(1).join(' '));
    break;
  case 'ast':
  case 'outline':
    extractAstOutline(args[1]);
    break;
  case 'rg':
  case 'grep':
    runRipgrep(args[1]);
    break;
  case 'symbol':
  case 'lookup': {
    const { execSync } = require('child_process');
    const routerScript = path.join(__dirname, 'librarian_router.cjs');
    execSync(`node "${routerScript}" symbol "${args[1]}"`, { stdio: 'inherit' });
    break;
  }
  case 'index': {
    const { buildIndex } = require('./librarian_indexer.cjs');
    buildIndex();
    break;
  }
  case 'map':
    generateRepoMap();
    break;
  default:
    console.error(`Unknown command: ${command}. Use --help for usage.`);
    process.exit(1);
}

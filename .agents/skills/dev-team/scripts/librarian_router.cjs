#!/usr/bin/env node
/**
 * The Librarian: Simultaneous Co-Pilot & Backend Master Context Router (v2.0)
 * Part of the AEC DevTeam System (/dev-team)
 *
 * Streams live symbol coordinates (exact start..end lines), Eloquent Model DNA
 * ($table, $fillable, $casts, relationships), Route bindings, and dependency graphs
 * directly to [Backend Dev 1 & 2] and [Frontend Dev 1 & 2] simultaneously.
 *
 * Usage:
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs backend-packet <Controller|Method|Model|Route>
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs ui-packet <ModuleOrBladeFile>
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs model <ModelName>
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs resolve "<query>" [--file <active_file>]
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs symbol <symbol_name>
 *   node .agents/skills/dev-team/scripts/librarian_router.cjs imports <filepath>
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT_DIR = process.cwd();
const INDEX_PATH = path.join(ROOT_DIR, '.agents', 'artifacts', 'librarian_index.json');

function isIndexStale(indexMtimeMs) {
  try {
    const out = execSync('git status --porcelain', { cwd: ROOT_DIR, encoding: 'utf8' });
    const lines = out.split(/\r?\n/).filter(Boolean);
    for (const line of lines) {
      const relFile = line.slice(3).trim().replace(/^"|"$/g, '');
      if (!relFile || relFile.startsWith('.agents/')) continue;
      if (!/\.(php|js|ts|vue|cjs|mjs)$/i.test(relFile)) continue;
      const fullPath = path.join(ROOT_DIR, relFile);
      if (fs.existsSync(fullPath)) {
        const stat = fs.statSync(fullPath);
        if (stat.mtimeMs > indexMtimeMs) {
          return true;
        }
      }
    }
  } catch (e) {}
  return false;
}

function loadIndex() {
  if (!fs.existsSync(INDEX_PATH)) {
    try {
      const { buildIndex } = require('./librarian_indexer.cjs');
      return buildIndex(INDEX_PATH);
    } catch (e) {
      console.error('Error: librarian_index.json not found and could not be built automatically.');
      process.exit(1);
    }
  }
  try {
    const indexStat = fs.statSync(INDEX_PATH);
    const parsed = JSON.parse(fs.readFileSync(INDEX_PATH, 'utf8'));
    // Auto-rebuild if older index version or if any modified source file is newer than index
    if (!parsed.models || !parsed.call_graph || !parsed.file_roles || isIndexStale(indexStat.mtimeMs)) {
      const { buildIndex } = require('./librarian_indexer.cjs');
      return buildIndex(INDEX_PATH);
    }
    return parsed;
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
    return `${lines.length} uncommitted file(s): ` + lines.slice(0, 4).map(l => l.trim()).join(', ');
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

function formatLineSpan(loc) {
  if (loc.end_line && loc.end_line > loc.line) {
    return `L:${loc.line}–${loc.end_line} (${loc.end_line - loc.line + 1} lines)`;
  }
  return `L:${loc.line}`;
}

function formatModelDNA(modelMeta) {
  const lines = [];
  const tableStr = modelMeta.table ? `table="${modelMeta.table}"` : 'table=(convention)';
  lines.push(`   🧬 Model ${modelMeta.class} [${ modelMeta.file }] (${tableStr}):`);
  if (modelMeta.fillable && modelMeta.fillable.length > 0) {
    lines.push(`      • $fillable (${modelMeta.fillable.length}): [${modelMeta.fillable.join(', ')}]`);
  } else {
    lines.push(`      • $fillable: (none or $guarded used)`);
  }
  const castKeys = Object.keys(modelMeta.casts || {});
  if (castKeys.length > 0) {
    const castPairs = castKeys.map(k => `${k}: ${modelMeta.casts[k]}`).join(', ');
    lines.push(`      • $casts: { ${castPairs} }`);
  }
  if (modelMeta.relationships && modelMeta.relationships.length > 0) {
    const relStr = modelMeta.relationships
      .map(r => `${r.name}() -> ${r.type}(${r.related}) [L:${r.line}–${r.end_line}]`)
      .join('; ');
    lines.push(`      • Relationships: ${relStr}`);
  }
  return lines.join('\n');
}

/**
 * Simultaneous Librarian x Backend Master Packet Generator
 * Given a Controller name, method name, Model name, or query, emits:
 * 1. Exact Method/Class coordinates with Start..End line bounds (for 1-shot view_file slices)
 * 2. Matched Laravel Route bindings (HTTP verb, URI, route name -> Controller@action)
 * 3. Imported Eloquent Models + full Model Schema DNA ($fillable, $casts, relationships)
 */
function buildBackendPacket(targetQuery) {
  const index = loadIndex();
  const tokens = tokenize(targetQuery);
  const rawClean = targetQuery.trim().replace(/[^A-Za-z0-9_\\.]/g, '');

  const matchedSymbols = {};
  const candidateFiles = new Set();
  const relatedModels = new Set();
  const matchedRoutes = [];

  const lowerSymbolMap = new Map();
  Object.keys(index.symbols).forEach(k => lowerSymbolMap.set(k.toLowerCase(), k));

  const lowerModelMap = new Map();
  Object.keys(index.models || {}).forEach(k => lowerModelMap.set(k.toLowerCase(), k));

  // 1. Direct symbol or case-insensitive match
  const searchTerms = new Set([rawClean, ...targetQuery.split(/\s+/), ...tokens]);
  for (const term of searchTerms) {
    if (!term) continue;
    const exactKey = index.symbols[term] ? term : lowerSymbolMap.get(term.toLowerCase());
    if (exactKey && index.symbols[exactKey]) {
      matchedSymbols[exactKey] = index.symbols[exactKey];
      index.symbols[exactKey].forEach(loc => {
        if (loc.file) candidateFiles.add(loc.file);
        if (loc.class && index.models[loc.class]) relatedModels.add(loc.class);
      });
    }
    const exactModel = index.models[term] ? term : lowerModelMap.get(term.toLowerCase());
    if (exactModel) {
      relatedModels.add(exactModel);
      candidateFiles.add(index.models[exactModel].file);
    }
  }

  // 2. Partial substring match on symbols/models if nothing matched directly
  if (Object.keys(matchedSymbols).length === 0 && relatedModels.size === 0) {
    const qLower = rawClean.toLowerCase();
    for (const [symName, locs] of Object.entries(index.symbols)) {
      if (symName.toLowerCase().includes(qLower)) {
        matchedSymbols[symName] = locs;
        locs.forEach(l => l.file && candidateFiles.add(l.file));
      }
    }
    for (const modelName of Object.keys(index.models || {})) {
      if (modelName.toLowerCase().includes(qLower)) {
        relatedModels.add(modelName);
        candidateFiles.add(index.models[modelName].file);
      }
    }
  }

  // 3. Extract imported Eloquent Models from any matched Controller/Service/Action files
  for (const file of Array.from(candidateFiles)) {
    const imports = index.file_imports[file] || [];
    for (const imp of imports) {
      const shortName = imp.alias || (imp.full ? imp.full.split('\\').pop() : '');
      if (shortName && index.models[shortName]) {
        relatedModels.add(shortName);
      }
    }
  }

  // 4. Match routes linked to matched controllers, actions, or route names
  const matchedSymbolNames = new Set(Object.keys(matchedSymbols));
  for (const r of index.routes || []) {
    if (
      (r.name && (r.name === targetQuery.trim() || matchedSymbolNames.has(r.name))) ||
      (r.controller && matchedSymbolNames.has(r.controller)) ||
      (r.action && matchedSymbolNames.has(r.action)) ||
      (r.uri && r.uri.toLowerCase().includes(rawClean.toLowerCase()))
    ) {
      matchedRoutes.push(r);
      candidateFiles.add(r.file);
    }
  }

  console.log('\n========================================================================');
  console.log('⚡ [LIBRARIAN × BACKEND MASTER] — SIMULTANEOUS CO-EXECUTION PACKET');
  console.log('========================================================================');
  console.log(`🔒 ALLOWED FILES CANDIDATES (${candidateFiles.size}):`);
  Array.from(candidateFiles).slice(0, 8).forEach(f => console.log(`   - ${f}`));

  const symKeys = Object.keys(matchedSymbols);
  console.log(`\n🎯 EXACT SYMBOL & METHOD SPAN COORDINATES (${symKeys.length}):`);
  if (symKeys.length === 0) {
    console.log('   (No direct PHP symbol match — run fast_codebase_search.cjs rg or bm25)');
  } else {
    symKeys.slice(0, 10).forEach(sym => {
      matchedSymbols[sym].slice(0, 4).forEach(loc => {
        const span = formatLineSpan(loc);
        const sig = loc.signature ? ` :: ${loc.signature}` : '';
        const cls = loc.class ? ` [${loc.class}]` : '';
        console.log(`   • ${sym}${cls} -> ${loc.file} (${span})${sig}`);
      });
    });
  }

  // If a controller class was matched, also list its top methods with exact line spans
  for (const sym of symKeys) {
    const classLoc = matchedSymbols[sym].find(l => l.type === 'class');
    if (classLoc && classLoc.file && index.file_symbols[classLoc.file]) {
      const methods = index.file_symbols[classLoc.file].filter(s => s.type === 'method');
      if (methods.length > 0) {
        console.log(`\n📐 METHODS IN ${sym} (${classLoc.file}):`);
        methods.slice(0, 20).forEach(m => {
          const span = formatLineSpan(m);
          console.log(`   ↳ ${m.signature || m.name} [${span}]`);
        });
        if (methods.length > 20) {
          console.log(`   ↳ ... +${methods.length - 20} more methods`);
        }
      }
    }
  }

  if (matchedRoutes.length > 0) {
    console.log(`\n🛣️  BOUND LARAVEL ROUTES (${matchedRoutes.length}):`);
    matchedRoutes.slice(0, 12).forEach(r => {
      const target = r.controller ? `${r.controller}@${r.action}` : '(closure)';
      const rName = r.name ? ` [name: "${r.name}"]` : '';
      console.log(`   • ${r.method} /${r.uri.replace(/^\//, '')} -> ${target}${rName} (${r.file}:L${r.line})`);
    });
  }

  console.log(`\n🗄️  ELOQUENT MODEL SCHEMA DNA (${relatedModels.size} linked model(s)):`);
  if (relatedModels.size === 0) {
    console.log('   (No linked Eloquent Models detected in imports)');
  } else {
    Array.from(relatedModels).slice(0, 8).forEach(mName => {
      console.log(formatModelDNA(index.models[mName]));
    });
  }

  console.log('\n🤝 CO-EXECUTION DIRECTIVE FOR [BACKEND DEV 1 & 2]:');
  console.log('   1. Read ONLY the exact method span above via view_file(StartLine, EndLine).');
  console.log('   2. Reuse the $fillable, $casts, and relationship names above — DO NOT open Model files unless editing them.');
  console.log('   3. Enforce FormRequest/DTO bounds (Dev 1) + DB::transaction / lockForUpdate / strict Eloquent (Dev 2).');
  console.log('========================================================================\n');
}

function lookupModel(modelName) {
  const index = loadIndex();
  const lowerMap = new Map();
  Object.keys(index.models || {}).forEach(k => lowerMap.set(k.toLowerCase(), k));
  const exact = index.models[modelName] ? modelName : lowerMap.get((modelName || '').toLowerCase());
  if (!exact) {
    console.log(`Eloquent Model "${modelName}" not found in librarian_index.json.`);
    return;
  }
  console.log('\n=== The Librarian: Eloquent Model Schema DNA ===');
  console.log(formatModelDNA(index.models[exact]));
  console.log('================================================\n');
}

/**
 * Simultaneous Librarian x Frontend Power Duo Packet Generator (ui-packet)
 * Given a module slug (e.g., "vendor-masterlist", "request-quotations") or Blade path, emits:
 * 1. Paired Module Trio: Blade View + CSS Module (resources/css/modules/*.css) + JS Module
 * 2. Mandatory RMS Theme Tokens & Existing CSS Classes in the file (Anti-Rogue-Theme Lock)
 * 3. Modal / Form Input Audit & UX Input-Diet Check (Anti-Form-Bloat Gate)
 */
function buildUiPacket(targetQuery) {
  const slug = targetQuery
    .trim()
    .replace(/\\/g, '/')
    .replace(/^resources\/views\//, '')
    .replace(/\.blade\.php$/, '')
    .replace(/\.css$/, '')
    .replace(/\.js$/, '')
    .split('/')
    .pop();

  // Locate matching Blade, CSS, and JS files
  const walkFiles = (dir, list = []) => {
    if (!fs.existsSync(dir)) return list;
    for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
      const full = path.join(dir, entry.name);
      if (entry.isDirectory()) walkFiles(full, list);
      else list.push(path.relative(ROOT_DIR, full).replace(/\\/g, '/'));
    }
    return list;
  };

  const views = walkFiles(path.join(ROOT_DIR, 'resources', 'views'));
  const cssFiles = walkFiles(path.join(ROOT_DIR, 'resources', 'css'));
  const jsFiles = walkFiles(path.join(ROOT_DIR, 'resources', 'js'));

  const qLower = slug.toLowerCase();
  const matchedViews = views.filter(f => f.toLowerCase().includes(qLower));
  const matchedCss = cssFiles.filter(f => f.toLowerCase().includes(qLower));
  const matchedJs = jsFiles.filter(f => f.toLowerCase().includes(qLower));

  console.log('\n========================================================================');
  console.log('🎨 [LIBRARIAN × FRONTEND POWER DUO] — THEME LOCK & UX INPUT-DIET PACKET');
  console.log('========================================================================');
  console.log('🔒 PAIRED MODULE TRIO (ALLOWED FILES CANDIDATES):');
  matchedViews.slice(0, 4).forEach(f => console.log(`   • Blade View : ${f}`));
  matchedCss.slice(0, 4).forEach(f => console.log(`   • Module CSS : ${f}`));
  matchedJs.slice(0, 4).forEach(f => console.log(`   • Module JS  : ${f}`));
  if (matchedViews.length === 0 && matchedCss.length === 0) {
    console.log(`   (No exact match for "${slug}" — run fast_codebase_search.cjs bm25 "${targetQuery}")`);
  }

  // Dynamically extract :root CSS variables from global theme-tokens.css and module CSS
  const extractCssVars = (relCssPath, maxVars = 10) => {
    const full = path.join(ROOT_DIR, relCssPath);
    if (!fs.existsSync(full)) return [];
    try {
      const txt = fs.readFileSync(full, 'utf8');
      const vars = [];
      const re = /(--[a-zA-Z0-9_-]+)\s*:\s*([^;]+);/g;
      let m;
      while ((m = re.exec(txt)) !== null && vars.length < maxVars) {
        vars.push(`${m[1]}: ${m[2].trim()}`);
      }
      return vars;
    } catch (e) {
      return [];
    }
  };

  console.log('\n🎨 LIVE RMS THEME TOKENS FOR [FRONTEND DEV 1] (ZERO ROGUE THEMES):');
  const globalTokensPath = 'public/assets/css/theme-tokens.css';
  const globalVars = extractCssVars(globalTokensPath, 8);
  console.log(`   • Global Theme (${globalTokensPath}):`);
  if (globalVars.length > 0) {
    console.log(`     ${globalVars.join(' | ')}`);
  }
  console.log('   • Standard Classes : .hr-card / .erp-card, .hr-table-card, .hr-btn (.hr-btn-primary / .hr-btn-secondary), .hr-input, .hr-select, .hr-badge, [data-tooltip]');
  console.log('   • UOM Standard     : ALWAYS use <x-uom-dropdown> / UomRegistry (plain <select> for UOM is FORBIDDEN)');

  // Inspect primary matched CSS file for existing module :root tokens and class prefixes
  if (matchedCss.length > 0) {
    try {
      // THEME PRECEDENCE LAW: Global theme-tokens.css ALWAYS wins.
      // Any module :root color token whose hex value is not in the global palette = LEGACY DRIFT.
      const globalFull = path.join(ROOT_DIR, globalTokensPath);
      const globalHex = new Set();
      const brandHex = new Set();
      if (fs.existsSync(globalFull)) {
        const globalTxt = fs.readFileSync(globalFull, 'utf8');
        (globalTxt.match(/#[0-9a-fA-F]{3,8}\b/g) || []).forEach(h => globalHex.add(h.toLowerCase()));
        // Brand palette = hexes used by --brand-* and --gradient-* tokens only
        const brandRe = /--(?:brand|gradient)-[a-zA-Z0-9_-]+\s*:\s*([^;]+);/g;
        let bm;
        while ((bm = brandRe.exec(globalTxt)) !== null) {
          (bm[1].match(/#[0-9a-fA-F]{3,8}\b/g) || []).forEach(h => brandHex.add(h.toLowerCase()));
        }
      }
      const allModVars = extractCssVars(matchedCss[0], 200);
      const driftVars = allModVars.filter(v => {
        const name = v.split(':')[0];
        const hexes = (v.match(/#[0-9a-fA-F]{3,8}\b/g) || []).map(h => h.toLowerCase());
        if (hexes.length === 0) return false;
        // Primary/brand/accent roles must come from the brand palette, not semantic status colors
        if (/primary|brand|accent|focus/i.test(name)) {
          return hexes.some(h => !brandHex.has(h));
        }
        return hexes.some(h => !globalHex.has(h));
      });
      if (driftVars.length > 0) {
        console.log(`   ⚠️  LEGACY TOKEN DRIFT (${driftVars.length} token(s) in ${matchedCss[0]} NOT in global palette):`);
        console.log(`     ${driftVars.slice(0, 8).join(' | ')}${driftVars.length > 8 ? ` ... +${driftVars.length - 8} more` : ''}`);
        console.log('     -> Global theme WINS. When touching this module, re-point these tokens to var(--brand-*) / var(--gradient-*) from theme-tokens.css.');
        console.log('     -> NEVER add new module-level color tokens or reuse these drifted values in new markup.');
      } else if (allModVars.length > 0) {
        console.log(`   ✅ Module tokens (${matchedCss[0]}) conform to the global palette.`);
      }
      const cssContent = fs.readFileSync(path.join(ROOT_DIR, matchedCss[0]), 'utf8');
      const classMatches = Array.from(new Set((cssContent.match(/\.[a-zA-Z][a-zA-Z0-9_-]{2,}/g) || []).slice(0, 18)));
      console.log(`   • Existing Module CSS Classes (${matchedCss[0]}):`);
      console.log(`     ${classMatches.join(', ')}`);
    } catch (e) {}
  }

  // Inspect primary matched Blade file for per-modal & per-form input breakdown (UX Input-Diet Audit)
  if (matchedViews.length > 0) {
    try {
      const bladePath = matchedViews[0];
      const bladeLines = fs.readFileSync(path.join(ROOT_DIR, bladePath), 'utf8').split(/\r?\n/);
      const containers = [];
      let currentModal = null;
      let totalVisibleInputs = 0;

      bladeLines.forEach((line, idx) => {
        const lNum = idx + 1;
        const modalIdMatch = line.match(/id=["']([^"']*(?:modal|Modal|drawer|Drawer)[^"']*)["']/);
        if (modalIdMatch && !/(?:Title|Label|Body|Footer|Close|Btn|Button|Text|Icon)$/i.test(modalIdMatch[1])) {
          if (currentModal) currentModal.end_line = lNum - 1;
          currentModal = { id: modalIdMatch[1], start_line: lNum, end_line: bladeLines.length, inputs: [] };
          containers.push(currentModal);
        }

        if (/<(input|select|textarea)\b/i.test(line) && !/type=["'](?:hidden|checkbox|radio)["']/i.test(line)) {
          totalVisibleInputs++;
          const nameMatch = line.match(/(?:name|id)=["']([^"']+)["']/);
          const fieldName = nameMatch ? nameMatch[1] : `input@L${lNum}`;
          if (currentModal) {
            currentModal.inputs.push(`${fieldName} (L:${lNum})`);
          }
        }
      });

      console.log(`\n📐 PER-MODAL STRUCTURE & UX INPUT-DIET AUDIT (${bladePath}, ${bladeLines.length} lines):`);
      console.log(`   • Total Page Inputs : ${totalVisibleInputs} visible text/select/textarea field(s)`);
      if (containers.length > 0) {
        console.log(`   • Modal / Drawer Breakdown (${containers.length} container(s)):`);
        containers.forEach(c => {
          const count = c.inputs.length;
          const warn = count > 6 ? ' ⚠️ [EXCEEDS 6-INPUT CAP — APPLY 3-QUESTION GUILLOTINE]' : ' ✅ (within cap)';
          console.log(`     ↳ #${c.id} [L:${c.start_line}–${c.end_line}]: ${count} input(s)${warn}`);
          if (count > 0) {
            console.log(`       Fields: ${c.inputs.slice(0, 10).join(', ')}${count > 10 ? ` ... +${count - 10} more` : ''}`);
          }
        });
      }
    } catch (e) {}
  }
  console.log('========================================================================\n');
}

function resolveQuery(query, activeFile = null) {
  buildBackendPacket(query);
  if (activeFile) {
    inspectImports(activeFile);
  }
}

function lookupSymbol(symbolName) {
  const index = loadIndex();
  const matches = index.symbols[symbolName];
  if (!matches || matches.length === 0) {
    const lower = (symbolName || '').toLowerCase();
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
    const span = formatLineSpan(m);
    const sig = m.signature ? ` :: ${m.signature}` : '';
    console.log(`- ${m.file} (${span}) [${m.type}${parent}]${sig}`);
    if (m.type === 'class' && index.models && index.models[symbolName]) {
      console.log(formatModelDNA(index.models[symbolName]));
    }
  });
  console.log('=====================================================================\n');
}

function inspectImports(filePath) {
  const index = loadIndex();
  const relPath = path.relative(ROOT_DIR, path.resolve(ROOT_DIR, filePath)).replace(/\\/g, '/');
  const imports = index.file_imports[relPath];

  console.log(`\n=== The Librarian: Dependency Graph & Linked Models for "${relPath}" ===`);
  if (!imports || imports.length === 0) {
    console.log('No registered imports/use statements in this file.');
  } else {
    imports.forEach(imp => {
      const shortName = imp.alias || (imp.full ? imp.full.split('\\').pop() : '');
      const isModel = shortName && index.models && index.models[shortName] ? ' [ELOQUENT MODEL]' : '';
      console.log(`- ${imp.full || imp.source} (L:${imp.line || '?'})${isModel}`);
    });
  }
  console.log('========================================================================\n');
}

/**
 * Code-Navigation Agent Engine (Advance Index v3.0)
 * Executes Steps 1 (Hypothesize), 2 (Locate Core Block), 3 (Trace 1-Level Up & Down),
 * and 4 (Bounded Slice Coordinates) without scanning files line-by-line.
 */
function runCodeNavigation(rawQuery, entryFile = null) {
  const index = loadIndex();
  const qTokens = tokenize(rawQuery);
  const rawTerms = rawQuery
    .split(/[\s,|]+/)
    .map(t => t.trim())
    .filter(Boolean);

  const lowerSymbolMap = new Map();
  Object.keys(index.symbols).forEach(k => lowerSymbolMap.set(k.toLowerCase(), k));

  const fileScores = new Map(); // file -> { score, reasons: Set, subHits: number }
  const matchedBlocks = [];     // candidate core logical blocks
  const isCssQuery = /[.#]|--|css|style|theme|token|modal/i.test(rawQuery);

  const addFileScore = (file, pts, reason, isSubHit = false) => {
    if (!file) return;
    if (!fileScores.has(file)) {
      fileScores.set(file, { score: 0, reasons: new Set(), subHits: 0 });
    }
    const entry = fileScores.get(file);
    if (isSubHit) {
      if (entry.subHits >= 4) return; // cap substring accumulation per file
      entry.subHits++;
    }
    entry.score += pts;
    if (reason && entry.reasons.size < 4) entry.reasons.add(reason);
  };

  if (entryFile) {
    const cleanEntry = entryFile.replace(/^@/, '').replace(/\\/g, '/');
    for (const knownFile of Object.keys(index.file_roles || {})) {
      if (knownFile === cleanEntry || knownFile.endsWith(cleanEntry)) {
        addFileScore(knownFile, 120, 'Explicit entry file starting point');
      }
    }
  }

  // 1. Direct Symbol Lookup (Highest Priority)
  const seenSyms = new Set();
  for (const term of [...rawTerms, ...qTokens]) {
    const cleanTerm = term.replace(/^[@]/, '');
    if (cleanTerm.length < 2) continue;
    const exactKey = index.symbols[cleanTerm] ? cleanTerm : lowerSymbolMap.get(cleanTerm.toLowerCase());
    if (exactKey && index.symbols[exactKey] && !seenSyms.has(exactKey)) {
      seenSyms.add(exactKey);
      const isCompoundTerm = cleanTerm.length >= 6 || /[A-Z_.-]/.test(term);
      const basePts = isCompoundTerm ? 250 : 90;
      for (const rec of index.symbols[exactKey]) {
        addFileScore(rec.file, basePts, `Defines exact symbol \`${exactKey}\` (${rec.type} at ${formatLineSpan(rec)})`);
        matchedBlocks.push({ name: exactKey, ...rec, matchScore: isCompoundTerm ? 1000 : 300 });
      }
    }
  }

  // 2. Substring symbol match (capped per file so large CSS/JS files don't drown exact matches)
  for (const [symName, locs] of Object.entries(index.symbols)) {
    if (seenSyms.has(symName)) continue;
    if ((symName.startsWith('.') || symName.startsWith('--')) && !isCssQuery) continue;
    const lowerSym = symName.toLowerCase();
    let hitCount = 0;
    for (const tok of qTokens) {
      if (tok.length >= 3 && lowerSym.includes(tok)) hitCount++;
    }
    if (hitCount > 0) {
      const pts = hitCount >= 2 ? hitCount * 25 : 10;
      for (const rec of locs.slice(0, 2)) {
        addFileScore(rec.file, pts, `Contains ${rec.type} \`${symName}\` (${formatLineSpan(rec)})`, true);
        matchedBlocks.push({ name: symName, ...rec, matchScore: hitCount * 40 });
      }
    }
  }

  // 3. File path BM25-style token scoring
  for (const file of Object.keys(index.file_roles || {})) {
    const pathTokens = tokenize(file);
    let overlap = 0;
    for (const qt of qTokens) {
      if (pathTokens.includes(qt)) overlap++;
    }
    if (overlap > 0) {
      addFileScore(file, overlap * 25, `File path matches ${overlap} query token(s)`);
    }
  }

  // Sort top 5 hypothesized files
  const topFiles = Array.from(fileScores.entries())
    .sort((a, b) => b[1].score - a[1].score)
    .slice(0, 5);

  // Sort candidate Core Logical Blocks (exact symbol match + method/function priority)
  const dedupedBlocks = [];
  const blockKeys = new Set();
  matchedBlocks
    .sort((a, b) => {
      const fileA = Math.min(200, (fileScores.get(a.file) || { score: 0 }).score);
      const fileB = Math.min(200, (fileScores.get(b.file) || { score: 0 }).score);
      const typeBoostA = (a.type === 'method' || a.type === 'function' || a.type === 'class') ? 80 : 0;
      const typeBoostB = (b.type === 'method' || b.type === 'function' || b.type === 'class') ? 80 : 0;
      return (b.matchScore + fileB + typeBoostB) - (a.matchScore + fileA + typeBoostA);
    })
    .forEach(b => {
      const k = `${b.file}::${b.name}`;
      if (!blockKeys.has(k)) {
        blockKeys.add(k);
        dedupedBlocks.push(b);
      }
    });

  const primaryBlock = dedupedBlocks[0] || null;

  console.log('\n========================================================================');
  console.log(`🧭 CODE-NAVIGATION AGENT PACKET (ADVANCE INDEX v3.0): "${rawQuery}"`);
  console.log('========================================================================');

  // STEP 1: HYPOTHESIZE
  console.log('\n1️⃣ HYPOTHESIZE (Top 3–5 Most Likely Files + 1-Line Role & Reasoning):');
  if (topFiles.length === 0) {
    console.log('   • No indexed files matched. Try broader domain keywords or `fast_codebase_search.cjs rg`.');
  } else {
    topFiles.forEach(([file, info], idx) => {
      const role = (index.file_roles && index.file_roles[file]) || 'Source file';
      const why = Array.from(info.reasons).join('; ');
      console.log(`   ${idx + 1}. ${file}`);
      console.log(`      • Role      : ${role}`);
      console.log(`      • Reasoning : ${why} [score: ${info.score}]`);
    });
  }

  // STEP 2: LOCATE CORE LOGICAL BLOCK
  console.log('\n2️⃣ LOCATE (Core Logical Block Owning Behavior):');
  if (!primaryBlock) {
    console.log('   • No specific function/method symbol matched directly. Inspect top hypothesized file AST.');
  } else {
    const span = formatLineSpan(primaryBlock);
    const owner = primaryBlock.class ? `${primaryBlock.class}::${primaryBlock.name}` : primaryBlock.name;
    const sig = primaryBlock.signature ? ` — \`${primaryBlock.signature}\`` : '';
    console.log(`   • Core Block : \`${owner}\` [${primaryBlock.type}]${sig}`);
    console.log(`   • File Path  : ${primaryBlock.file} (${span})`);
    if (dedupedBlocks.length > 1) {
      console.log('   • Secondary Blocks:');
      dedupedBlocks.slice(1, 5).forEach(b => {
        const bOwner = b.class ? `${b.class}::${b.name}` : b.name;
        console.log(`     ↳ \`${bOwner}\` in ${b.file} (${formatLineSpan(b)}) [${b.type}]`);
      });
    }
  }

  // STEP 3: TRACE DEPENDENCIES (1-LEVEL UP & 1-LEVEL DOWN)
  console.log('\n3️⃣ TRACE DEPENDENCIES (1-Level Up Callers ↑ | File Imports ↔ | 1-Level Down Callees ↓):');
  if (primaryBlock) {
    const symName = primaryBlock.name;
    const callers = (index.call_graph && index.call_graph.called_by && index.call_graph.called_by[symName]) || [];
    const classDependents = primaryBlock.class && index.imported_by ? (index.imported_by[primaryBlock.class] || []) : [];

    console.log(`   ↑ [1-Level Up — What Calls / Triggers \`${symName}\`]:`);
    if (callers.length === 0 && classDependents.length === 0) {
      console.log('     • Entrypoint / UI Event Handler (no direct indexed internal callers)');
    } else {
      callers.slice(0, 8).forEach(c => {
        console.log(`     • ${c.caller} in ${c.file} (L:${c.line}) via \`${c.via}\``);
      });
      if (classDependents.length > 0) {
        console.log(`     • Class \`${primaryBlock.class}\` imported by: ${classDependents.slice(0, 6).join(', ')}`);
      }
    }

    const fileImports = (index.file_imports && index.file_imports[primaryBlock.file]) || [];
    console.log(`   ↔ [Imports in ${primaryBlock.file}]:`);
    if (fileImports.length === 0) {
      console.log('     • None');
    } else {
      console.log(`     • ${fileImports.slice(0, 10).map(i => i.full || i.source).join(', ')}`);
    }

    const callees = primaryBlock.calls || [];
    console.log(`   ↓ [1-Level Down — What \`${symName}\` Calls Inside ${formatLineSpan(primaryBlock)}]:`);
    if (callees.length === 0) {
      console.log('     • Leaf block (no external method/service/route calls detected)');
    } else {
      callees.forEach(callee => {
        const cleanTarget = callee.replace(/^\$this->|^this\.|^route:|^fetch:/, '').split('::').pop().split('->').pop();
        const targetLocs = index.symbols[cleanTarget] || [];
        const locHint = targetLocs.length > 0 ? ` -> ${targetLocs[0].file} (${formatLineSpan(targetLocs[0])})` : '';
        console.log(`     • \`${callee}\`${locHint}`);
      });
    }
  } else if (topFiles.length > 0) {
    const topFile = topFiles[0][0];
    const fileImports = (index.file_imports && index.file_imports[topFile]) || [];
    console.log(`   ↔ [Imports in ${topFile}]: ${fileImports.map(i => i.full || i.source).join(', ') || 'None'}`);
  }

  // STEP 4: BOUNDED SLICE CONFIRMATION TARGET (<= 80 LINES)
  console.log('\n4️⃣ CONFIRMATION TARGET (Bounded Slice Read ≤ 80 Lines — DO NOT SCAN LINE-BY-LINE):');
  if (primaryBlock && primaryBlock.line) {
    const startLine = Math.max(1, primaryBlock.line - 3);
    const rawEnd = primaryBlock.end_line && primaryBlock.end_line >= primaryBlock.line
      ? primaryBlock.end_line + 3
      : primaryBlock.line + 45;
    const endLine = Math.min(startLine + 79, rawEnd);
    console.log(`   • Read Exact Slice: read_file(Path: "${primaryBlock.file}", StartLine: ${startLine}, EndLine: ${endLine})`);
  } else if (topFiles.length > 0) {
    console.log(`   • Inspect AST first: node .agents/skills/dev-team/scripts/fast_codebase_search.cjs ast ${topFiles[0][0]}`);
  }
  console.log('========================================================================\n');
}

// CLI Dispatcher
const args = process.argv.slice(2);
const command = args[0];

if (!command || command === '--help' || command === '-h') {
  console.log(`
The Librarian: Simultaneous Co-Pilot & Code-Navigation Router (v3.0)
Usage:
  node librarian_router.cjs navigate "<keywords|symbol>" [--entry <filepath>]
  node librarian_router.cjs backend-packet <Controller|Method|Model|Route>
  node librarian_router.cjs ui-packet <ModuleSlug|BladeView>
  node librarian_router.cjs model <ModelName>
  node librarian_router.cjs resolve "<query>" [--file <active_file>]
  node librarian_router.cjs symbol <symbol_name>
  node librarian_router.cjs imports <filepath>
`);
  process.exit(0);
}

switch (command) {
  case 'navigate':
  case 'nav':
  case 'trace': {
    const entryIdx = args.indexOf('--entry');
    let entryFile = null;
    const queryParts = [];
    for (let i = 1; i < args.length; i++) {
      if (i === entryIdx) {
        entryFile = args[i + 1] || null;
        i++;
      } else {
        queryParts.push(args[i]);
      }
    }
    runCodeNavigation(queryParts.join(' '), entryFile);
    break;
  }
  case 'backend-packet':
  case 'packet':
    buildBackendPacket(args.slice(1).join(' '));
    break;
  case 'ui-packet':
  case 'ui':
    buildUiPacket(args.slice(1).join(' '));
    break;
  case 'model':
    lookupModel(args[1]);
    break;
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

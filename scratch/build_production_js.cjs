const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/production.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1089 (line 1090) to index 1655 (line 1656)
let jsLines = lines.slice(1089, 1656);

// Extract declared functions
const funcRegex = /^(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let line of jsLines) {
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}

let transformedLines = jsLines.map(line => {
  let l = line;
  if (l.includes('@json($initialOrders ?? []);')) {
    return '    let rawOrders = window.ProductionConfig?.data?.initialOrders || [];';
  }
  if (l.includes('@json($initialBoms ?? []);')) {
    return '    let rawBoms = window.ProductionConfig?.data?.initialBoms || [];';
  }
  if (l.includes('@json($initialProducts ?? []);')) {
    return '    let rawProducts = window.ProductionConfig?.data?.initialProducts || [];';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.create-production-batch') }}\",")) {
    return '        const res = await fetch(window.ProductionConfig?.routes?.createProductionBatch || \'/inventory/api/create-production-batch\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.production-data') }}\")")) {
    return '        const res = await fetch(window.ProductionConfig?.routes?.productionData || \'/inventory/api/production-data\');';
  }
  return l;
});

// Check for remaining Blade tokens
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Production JS:', remainingBlade);

// Build window exports for ES module / Vite parity
const windowExports = `
// ==========================================================================
// WINDOW EXPORTS FOR FULL BACKWARD-COMPATIBILITY & INLINE HANDLERS
// ==========================================================================
Object.assign(window, {
  ${declaredFuncs.join(',\n  ')}
});
`;

const finalJs = transformedLines.join('\n') + '\n' + windowExports;

fs.mkdirSync('resources/js/modules', { recursive: true });
fs.writeFileSync('resources/js/modules/production.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/production.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

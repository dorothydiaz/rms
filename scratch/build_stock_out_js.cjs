const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stock-out.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1341 (line 1342) to index 2441 (line 2442)
let jsLines = lines.slice(1341, 2442);

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
  if (l.includes('@json($initialOrders);')) {
    return '    let rawOrders = window.StockOutConfig?.data?.initialOrders || [];';
  }
  if (l.includes('@json($initialProducts);')) {
    return '    let rawProducts = window.StockOutConfig?.data?.initialProducts || [];';
  }
  if (l.includes('@json($initialVendors);')) {
    return '    let rawVendors = window.StockOutConfig?.data?.initialVendors || [];';
  }
  if (l.includes('@json($initialCategories);')) {
    return '    let rawCategories = window.StockOutConfig?.data?.initialCategories || [];';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.create-stock-out-draft') }}\",")) {
    return '        const response = await fetch(window.StockOutConfig?.routes?.createStockOutDraft || \'/inventory/api/create-stock-out-draft\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.update-stock-out-pack') }}\",")) {
    return '        const res = await fetch(window.StockOutConfig?.routes?.updateStockOutPack || \'/inventory/api/update-stock-out-pack\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.confirm-ship-stock-out') }}\",")) {
    return '        const res = await fetch(window.StockOutConfig?.routes?.confirmShipStockOut || \'/inventory/api/confirm-ship-stock-out\', {';
  }
  if (l.includes("href=\"{{ route('inventory.stocks-overview') }}\"")) {
    return l.replace("{{ route('inventory.stocks-overview') }}", "${window.StockOutConfig?.routes?.stocksOverview || '/inventory/stocks-overview'}");
  }
  if (l.includes("fetch(\"{{ route('inventory.api.stock-out-data') }}\")")) {
    return '        const res = await fetch(window.StockOutConfig?.routes?.stockOutData || \'/inventory/api/stock-out-data\');';
  }
  return l;
});

// Check for remaining Blade tokens
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Stock Out JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/stock-out.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/stock-out.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

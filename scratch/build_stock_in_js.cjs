const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stock-in.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1546 (line 1547) to index 3245 (line 3246)
let jsLines = lines.slice(1546, 3246);

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
  if (l.includes('@json($initialPurchaseOrders ?? []);')) {
    return '    const serverPos = window.StockInConfig?.data?.initialPurchaseOrders || [];';
  }
  if (l.includes('@json($initialProducts ?? []);')) {
    return '    const serverProd = window.StockInConfig?.data?.initialProducts || [];';
  }
  if (l.includes('@json($initialVendors ?? []);')) {
    return '    const serverVend = window.StockInConfig?.data?.initialVendors || [];';
  }
  if (l.includes('@json($initialReceipts ?? []);')) {
    return '    const serverGrn = window.StockInConfig?.data?.initialReceipts || [];';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.stock-in-data') }}\")")) {
    return '        const res = await fetch(window.StockInConfig?.routes?.stockInData || \'/inventory/api/stock-in-data\');';
  }
  if (l.includes("window.location.href = \"{{ route('purchase.purchase-orders') }}?edit=\"")) {
    return '    window.location.href = (window.StockInConfig?.routes?.purchaseOrders || \'/purchase/purchase-orders\') + "?edit=" + encodeURIComponent(poNumber);';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.receive-stock') }}\",")) {
    return '        const res = await fetch(window.StockInConfig?.routes?.receiveStock || \'/inventory/api/receive-stock\', {';
  }
  if (l.includes("actor: '{{ auth()->user()->name ?? \"Warehouse Logistics Supervisor\" }}'")) {
    return "            actor: window.StockInConfig?.user?.name || 'Warehouse Logistics Supervisor'";
  }
  return l;
});

// Check for remaining Blade tokens
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Stock In JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/stock-in.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/stock-in.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

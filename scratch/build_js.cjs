const fs = require('fs');

const src = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = src.split(/\r?\n/);
// In purchase-orders.blade.php:
// Line 2616 (idx 2615) is @push('scripts')
// Line 2617 (idx 2616) is <script>
// Line 5923 (idx 5922) is </script>
// Line 5924 (idx 5923) is @endpush
// So the pure JS code starts at index 2617 (line 2618) and ends at index 5922 (line 5922)
let jsLines = lines.slice(2617, 5922);

// Identify all function declarations
const funcRegex = /^function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let line of jsLines) {
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}

// Convert the lines, applying the 8 Blade bridges
let transformedLines = jsLines.map((line) => {
  let l = line;
  // 1. Line 2856-2858
  if (l.includes('const serverPos = @json($initialPurchaseOrders ?? []);')) {
    return '    const serverPos = window.PurchaseOrderConfig?.data?.initialPurchaseOrders || [];';
  }
  if (l.includes('const serverVendors = @json($initialVendors ?? []);')) {
    return '    const serverVendors = window.PurchaseOrderConfig?.data?.initialVendors || [];';
  }
  if (l.includes('const serverItems = @json($initialItems ?? []);')) {
    return '    const serverItems = window.PurchaseOrderConfig?.data?.initialItems || [];';
  }
  // 2. Line 3715
  if (l.includes("fetch('{{ route(\"purchase.api.orders.create\") }}', {")) {
    return "    fetch(window.PurchaseOrderConfig?.routes?.ordersCreate || '/purchase/api/orders/create', {";
  }
  // 3. Line 4219
  if (l.includes("{{ route('inventory.stock-in') }}")) {
    return l.replace("{{ route('inventory.stock-in') }}", "${window.PurchaseOrderConfig?.routes?.inventoryStockIn || '/inventory/stock-in'}");
  }
  // 4. Line 5031
  if (l.includes("actor: '{{ auth()->user()->name ?? \"Dorothy Diaz\" }}'")) {
    return "        actor: window.PurchaseOrderConfig?.user?.name || 'Dorothy Diaz',";
  }
  // 5. Line 5128
  if (l.includes("actor: '{{ auth()->user()->name ?? \"Dorothy Diaz\" }}'")) {
    return "                actor: window.PurchaseOrderConfig?.user?.name || 'Dorothy Diaz',";
  }
  // 6. Line 5825
  if (l.includes("actor: '{{ auth()->user()->name ?? \"Procurement Specialist\" }}'")) {
    return "        actor: window.PurchaseOrderConfig?.user?.name || 'Procurement Specialist'";
  }
  return l;
});

// Check if any Blade tokens remain
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/purchase-orders.js', finalJs, 'utf8');
console.log('Successfully written resources/js/modules/purchase-orders.js');
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

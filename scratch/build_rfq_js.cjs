const fs = require('fs');

const src = fs.readFileSync('resources/views/purchase/request-quotations.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// In request-quotations.blade.php:
// Line 3723 is @push('scripts')
// Line 3724 is <script>
// Line 9606 is </script>
// Line 9607 is @endpush
// So the pure JS code starts at index 3724 (line 3725) and ends at index 9605 (line 9605)
let jsLines = lines.slice(3724, 9605);

// Extract top-level function names
const funcRegex = /^function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let line of jsLines) {
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}

// Transform the lines replacing the 8 Blade expressions
let transformedLines = jsLines.map((line) => {
  let l = line;
  if (l.includes('@json($initialRfqs ?? []);')) {
    return '    const serverRfqs = window.__SERVER_RFQS__ = window.RfqConfig?.data?.initialRfqs || [];';
  }
  if (l.includes('@json($initialVendors ?? []);')) {
    return '    const serverVendors = window.__SERVER_VENDORS__ = window.RfqConfig?.data?.initialVendors || [];';
  }
  if (l.includes('@json($initialItems ?? []);')) {
    return '    const serverItems = window.__SERVER_ITEMS__ = window.RfqConfig?.data?.initialItems || [];';
  }
  if (l.includes('{{ route("purchase.api.rfq.transition-status") }}')) {
    return '        const transitionUrl = window.RfqConfig?.routes?.transitionStatus || \'/purchase/api/rfq/transition-status\';';
  }
  if (l.includes("fetch(\"{{ route('purchase.request-quotations.send-email') }}\",")) {
    return '        const res = await fetch(window.RfqConfig?.routes?.sendEmail || \'/purchase/request-quotations/send-email\', {';
  }
  if (l.includes("{{ route('purchase.purchase-orders') }}")) {
    return l.replace("{{ route('purchase.purchase-orders') }}", "${window.RfqConfig?.routes?.purchaseOrders || '/purchase/purchase-orders'}");
  }
  if (l.includes("actor: '{{ auth()->user()->name ?? \"Dorothy Diaz\" }}'")) {
    return "        actor: window.RfqConfig?.user?.name || 'Dorothy Diaz',";
  }
  return l;
});

// Check for any remaining Blade expressions
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in RFQ JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/request-quotations.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/request-quotations.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

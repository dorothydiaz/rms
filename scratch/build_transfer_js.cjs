const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/internal-transfer.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// In internal-transfer.blade.php:
// Line 1040 is @push('scripts')
// Line 1041 is <script>
// Line 1744 is </script>
// Line 1745 is @endpush
// So the pure JS code starts at index 1041 (line 1042) and ends at index 1743 (line 1743)
let jsLines = lines.slice(1041, 1743);

// Extract top-level function names (including async function)
const funcRegex = /^(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let line of jsLines) {
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}

// Transform the lines replacing the 10 Blade expressions
let transformedLines = jsLines.map((line) => {
  let l = line;
  if (l.includes('@json($initialTransfers);')) {
    return '    let rawTransfers = window.InternalTransferConfig?.data?.initialTransfers || [];';
  }
  if (l.includes('@json($initialProducts);')) {
    return '    let rawProducts = window.InternalTransferConfig?.data?.initialProducts || [];';
  }
  if (l.includes('@json($initialBranches);')) {
    return '    let rawBranches = window.InternalTransferConfig?.data?.initialBranches || [];';
  }
  if (l.includes('@json($initialCategories);')) {
    return '    let rawCategories = window.InternalTransferConfig?.data?.initialCategories || [];';
  }
  if (l.includes("dispatched_by: '{{ auth()->user()->full_name ?? auth()->user()->username ?? \"Commissary Lead\" }}'")) {
    return "        dispatched_by: window.InternalTransferConfig?.user?.commissaryLead || 'Commissary Lead',";
  }
  if (l.includes("fetch(\"{{ route('inventory.api.dispatch-transfer') }}\",")) {
    return '        const res = await fetch(window.InternalTransferConfig?.routes?.dispatchTransfer || \'/inventory/api/dispatch-transfer\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.create-custom-transfer') }}\",")) {
    return '        const res = await fetch(window.InternalTransferConfig?.routes?.createCustomTransfer || \'/inventory/api/create-custom-transfer\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.internal-transfer-data') }}\")")) {
    return '        const res = await fetch(window.InternalTransferConfig?.routes?.internalTransferData || \'/inventory/api/internal-transfer-data\');';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.receive-transfer') }}\",")) {
    return '        const res = await fetch(window.InternalTransferConfig?.routes?.receiveTransfer || \'/inventory/api/receive-transfer\', {';
  }
  if (l.includes("received_by: '{{ auth()->user()->full_name ?? auth()->user()->username ?? \"Branch Receiving Lead\" }}'")) {
    return "            received_by: window.InternalTransferConfig?.user?.receivingLead || 'Branch Receiving Lead',";
  }
  return l;
});

// Check for any remaining Blade expressions
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Internal Transfer JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/internal-transfer.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/internal-transfer.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Declared function names:', declaredFuncs);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

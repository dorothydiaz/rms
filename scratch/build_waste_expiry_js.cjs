const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/waste-expiry.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1233 (line 1234) to index 1603 (line 1604)
let jsLines = lines.slice(1233, 1604);

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
  if (l.includes("fetch(\"{{ route('inventory.api.create-waste-record') }}\",")) {
    return '        const res = await fetch(window.WasteExpiryConfig?.routes?.createWasteRecord || \'/inventory/api/create-waste-record\', {';
  }
  if (l.includes("fetch(\"{{ route('inventory.api.update-waste-status') }}\",")) {
    return '        const res = await fetch(window.WasteExpiryConfig?.routes?.updateWasteStatus || \'/inventory/api/update-waste-status\', {';
  }
  return l;
});

// Check for remaining Blade tokens
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Waste Expiry JS:', remainingBlade);

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
fs.writeFileSync('resources/js/modules/waste-expiry.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/waste-expiry.js');
console.log('Total declared functions:', declaredFuncs.length);
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

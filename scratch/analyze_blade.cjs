const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== CSS SECTION ANALYSIS (lines 6-1576) ===');
let cssComments = [];
for (let i = 5; i < 1576; i++) {
  const line = lines[i];
  if (line.includes('/*') && (line.includes('===') || line.includes('---') || line.trim().startsWith('/*'))) {
    cssComments.push((i + 1) + ': ' + line.trim());
  }
}
console.log('Found CSS structural markers:');
cssComments.slice(0, 40).forEach(c => console.log(c));

console.log('\n=== INLINE ON[EVENT] IN HTML (lines 1578-2615) ===');
let onEvents = [];
for (let i = 1577; i < 2615; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'][^"']*["']/gi);
  if (m) {
    onEvents.push({ line: i + 1, matches: m, text: line.trim() });
  }
}
console.log('Total on[event] occurrences in HTML: ' + onEvents.length);
onEvents.forEach(e => console.log(e.line + ': ' + e.matches.join(', ') + ' --> ' + e.text));

console.log('\n=== INLINE STYLES IN HTML (lines 1578-2615) ===');
let inlineStyles = [];
for (let i = 1577; i < 2615; i++) {
  const line = lines[i];
  const m = line.match(/\bstyle\s*=\s*["'][^"']*["']/gi);
  if (m) {
    inlineStyles.push({ line: i + 1, matches: m, text: line.trim() });
  }
}
console.log('Total style=\'...\' occurrences in HTML: ' + inlineStyles.length);
inlineStyles.forEach(e => console.log(e.line + ': ' + e.matches.join(' | ') + ' --> ' + e.text.slice(0, 100)));

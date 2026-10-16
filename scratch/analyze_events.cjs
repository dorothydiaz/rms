const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== ALL ON[EVENT] IN HTML (lines 1578-2615) ===');
let onEvents = [];
for (let i = 1577; i < 2615; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'][^"']*["']/gi);
  if (m) {
    onEvents.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim());
  }
}
console.log('Total on[event] in HTML: ' + onEvents.length);
onEvents.forEach(e => console.log(e));

console.log('\n=== ALL ON[EVENT] IN JS DYNAMIC STRINGS (lines 2617-5923) ===');
let onEventsJS = [];
for (let i = 2616; i < 5923; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'\\][^"'\\]*["'\\]/gi);
  if (m) {
    onEventsJS.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim().slice(0, 100));
  }
}
console.log('Total on[event] in JS templates: ' + onEventsJS.length);
onEventsJS.forEach(e => console.log(e));

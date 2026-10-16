const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/request-quotations.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== 1. CHECKING BLADE IN STYLE (lines 6-2688) ===');
let bladeInStyle = [];
for (let i = 5; i < 2688; i++) {
  const line = lines[i];
  const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) {
    const realBlade = m.filter(tok => !tok.startsWith('@media') && !tok.startsWith('@keyframes') && !tok.startsWith('@charset') && !tok.startsWith('@import') && !tok.startsWith('@container'));
    if (realBlade.length > 0) {
      bladeInStyle.push((i + 1) + ': ' + realBlade.join(', ') + ' --> ' + line.trim());
    }
  }
}
console.log('Real Blade in style:', bladeInStyle);

console.log('\n=== 2. CHECKING BLADE IN SCRIPT (lines 3724-9606) ===');
let bladeInScript = [];
for (let i = 3723; i < 9606; i++) {
  const line = lines[i];
  const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) {
    bladeInScript.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim());
  }
}
console.log('Total Blade tokens in script: ' + bladeInScript.length);
bladeInScript.forEach(b => console.log(b));

console.log('\n=== 3. CHECKING INLINE EVENTS IN HTML (lines 2691-3721) ===');
let onEventsHtml = [];
for (let i = 2690; i < 3721; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'][^"']*["']/gi);
  if (m) {
    onEventsHtml.push((i + 1) + ': ' + m.join(', '));
  }
}
console.log('Total on[event] in HTML:', onEventsHtml.length);
onEventsHtml.slice(0, 25).forEach(e => console.log(e));

console.log('\n=== 4. CHECKING INLINE STYLES IN HTML (lines 2691-3721) ===');
let inlineStylesHtml = [];
for (let i = 2690; i < 3721; i++) {
  const line = lines[i];
  const m = line.match(/\bstyle\s*=\s*["'][^"']*["']/gi);
  if (m) {
    inlineStylesHtml.push((i + 1) + ': ' + m.join(' | '));
  }
}
console.log('Total style=\'...\' in HTML:', inlineStylesHtml.length);
inlineStylesHtml.slice(0, 25).forEach(s => console.log(s));

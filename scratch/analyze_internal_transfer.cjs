const fs = require('fs');

const content = fs.readFileSync('resources/views/inventory/internal-transfer.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== 1. CHECKING BLADE IN STYLE (lines 6-586 & 590-611) ===');
let bladeInStyle = [];
const checkStyleRange = (start, end) => {
  for (let i = start; i < end; i++) {
    const line = lines[i];
    const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
    if (m) {
      const realBlade = m.filter(tok => !tok.startsWith('@media') && !tok.startsWith('@keyframes') && !tok.startsWith('@charset') && !tok.startsWith('@import') && !tok.startsWith('@container'));
      if (realBlade.length > 0) {
        bladeInStyle.push((i + 1) + ': ' + realBlade.join(', ') + ' --> ' + line.trim());
      }
    }
  }
};
checkStyleRange(5, 586);
checkStyleRange(589, 611);
console.log('Real Blade in style:', bladeInStyle);

console.log('\n=== 2. CHECKING BLADE IN SCRIPT (lines 1041-1744) ===');
let bladeInScript = [];
for (let i = 1040; i < 1744; i++) {
  const line = lines[i];
  const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) {
    bladeInScript.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim());
  }
}
console.log('Total Blade tokens in script: ' + bladeInScript.length);
bladeInScript.forEach(b => console.log(b));

console.log('\n=== 3. CHECKING INLINE EVENTS IN HTML (lines 612-1039) ===');
let onEventsHtml = [];
for (let i = 611; i < 1039; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'][^"']*["']/gi);
  if (m) {
    onEventsHtml.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim());
  }
}
console.log('Total on[event] in HTML:', onEventsHtml.length);
onEventsHtml.forEach(e => console.log(e));

console.log('\n=== 4. CHECKING INLINE STYLES IN HTML (lines 612-1039) ===');
let inlineStylesHtml = [];
for (let i = 611; i < 1039; i++) {
  const line = lines[i];
  const m = line.match(/\bstyle\s*=\s*["'][^"']*["']/gi);
  if (m) {
    inlineStylesHtml.push((i + 1) + ': ' + m.join(' | ') + ' --> ' + line.trim().slice(0, 80));
  }
}
console.log('Total style=\'...\' in HTML:', inlineStylesHtml.length);
inlineStylesHtml.slice(0, 30).forEach(s => console.log(s));

console.log('\n=== 5. CHECKING TOP-LEVEL FUNCTIONS IN SCRIPT ===');
const funcRegex = /^function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let i = 1040; i < 1744; i++) {
  const line = lines[i];
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}
console.log('Total top-level functions in Internal Transfer:', declaredFuncs.length);
console.log(declaredFuncs);

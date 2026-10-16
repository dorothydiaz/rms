const fs = require('fs');

const content = fs.readFileSync('resources/views/inventory/stock-out.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== 1. CHECKING BLADE IN STYLE (lines 6-683 & 687-708) ===');
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
checkStyleRange(5, 683);
checkStyleRange(686, 708);
console.log('Real Blade in style:', bladeInStyle);

console.log('\n=== 2. CHECKING BLADE IN SCRIPT (lines 1341-2443) ===');
let bladeInScript = [];
for (let i = 1340; i < 2443; i++) {
  const line = lines[i];
  const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) {
    bladeInScript.push((i + 1) + ': ' + m.join(', ') + ' --> ' + line.trim());
  }
}
console.log('Total Blade tokens in script: ' + bladeInScript.length);
bladeInScript.forEach(b => console.log(b));

console.log('\n=== 3. CHECKING TOP-LEVEL FUNCTIONS IN SCRIPT ===');
const funcRegex = /^(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/;
let declaredFuncs = [];
for (let i = 1340; i < 2443; i++) {
  const line = lines[i];
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}
console.log('Total top-level functions in Stock Out:', declaredFuncs.length);
console.log(declaredFuncs);

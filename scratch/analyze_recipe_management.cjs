const fs = require('fs');

const content = fs.readFileSync('resources/views/inventory/recipe-management.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== 1. CHECKING BLADE IN STYLE (lines 6-1126) ===');
let bladeInStyle = [];
for (let i = 5; i < 1126; i++) {
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

console.log('\n=== 2. CHECKING BLADE IN SCRIPT (lines 1448-2678) ===');
let bladeInScript = [];
for (let i = 1447; i < 2678; i++) {
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
for (let i = 1447; i < 2678; i++) {
  const line = lines[i];
  const m = line.match(funcRegex);
  if (m) {
    declaredFuncs.push(m[1]);
  }
}
console.log('Total top-level functions in Recipe Management:', declaredFuncs.length);
console.log(declaredFuncs);

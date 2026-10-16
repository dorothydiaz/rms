const fs = require('fs');

const orig = fs.readFileSync('resources/views/inventory/stock-out.blade.php.bak', 'utf8');
const refBlade = fs.readFileSync('resources/views/inventory/stock-out.blade.php', 'utf8');
const modCss = fs.readFileSync('resources/css/modules/stock-out.css', 'utf8');
const modJs = fs.readFileSync('resources/js/modules/stock-out.js', 'utf8');

console.log('=== STOCK OUT 1:1 VERIFICATION AUDIT ===');

// 1. DOM IDs
const idRegex = /id=["']([^"']+)["']/g;
const origIds = new Set();
let match;
const origHtml = orig.split(/\r?\n/).slice(708, 1339).join('\n');
while ((match = idRegex.exec(origHtml)) !== null) {
  origIds.add(match[1]);
}

const refIds = new Set();
while ((match = idRegex.exec(refBlade)) !== null) {
  refIds.add(match[1]);
}

console.log('Original DOM IDs count:', origIds.size);
console.log('Refactored Blade DOM IDs count:', refIds.size);
let missingIds = [...origIds].filter(id => !refIds.has(id));
console.log('Missing DOM IDs:', missingIds.length === 0 ? 'NONE (100% Match)' : missingIds);

// 2. Functions
const origFuncs = [];
const funcRegex = /^(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/gm;
while ((match = funcRegex.exec(orig)) !== null) {
  origFuncs.push(match[1]);
}

const jsFuncs = [];
while ((match = funcRegex.exec(modJs)) !== null) {
  jsFuncs.push(match[1]);
}

console.log('Original Function count:', origFuncs.length);
console.log('Refactored Module JS Function count:', jsFuncs.length);
let missingFuncs = origFuncs.filter(fn => !jsFuncs.includes(fn));
console.log('Missing Functions:', missingFuncs.length === 0 ? 'NONE (100% Match)' : missingFuncs);

// 3. Blade tokens in JS
const bladeInJs = modJs.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
console.log('Blade tokens left in external JS:', bladeInJs ? bladeInJs : 'ZERO (Clean)');

// 4. Blade tokens in CSS
const bladeInCss = modCss.match(/\{\{.*?\}\}|\{!!.*?!!\}/g);
console.log('Blade tokens in external CSS:', bladeInCss ? bladeInCss : 'ZERO (Clean)');

// 5. Check window exports in JS
const hasObjectAssign = modJs.includes('Object.assign(window,');
console.log('Window exports present in JS:', hasObjectAssign);

// 6. Check @vite directives in Blade
const hasViteCss = refBlade.includes("@vite('resources/css/modules/stock-out.css')");
const hasViteJs = refBlade.includes("@vite('resources/js/modules/stock-out.js')");
const hasBladeData = refBlade.includes("@push('blade-data')");
console.log('Blade has @vite CSS:', hasViteCss);
console.log('Blade has @vite JS:', hasViteJs);
console.log('Blade has @push(blade-data):', hasBladeData);

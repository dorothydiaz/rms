const fs = require('fs');

const origContent = fs.readFileSync('scratch/uom-configuration.blade.php.original', 'utf8');
const origLines = origContent.split(/\r?\n/);

const newBlade = fs.readFileSync('resources/views/inventory/uom-configuration.blade.php', 'utf8');
const newCss = fs.readFileSync('resources/css/modules/uom-configuration.css', 'utf8');
const newJs = fs.readFileSync('resources/js/modules/uom-configuration.js', 'utf8');

console.log('=== AUDITING UOM CONFIGURATION ASSETS ===');

// 1. CSS Audit
const origCssLines = origLines.slice(6, 775);
const newCssLines = newCss.split(/\r?\n/);
console.log('Original CSS lines:', origCssLines.length, 'Extracted CSS lines:', newCssLines.length);
if (origCssLines.length !== newCssLines.length) {
    console.error('ERROR: CSS line count mismatch!');
} else {
    let cssDiff = 0;
    for (let i = 0; i < origCssLines.length; i++) {
        if (origCssLines[i] !== newCssLines[i]) {
            cssDiff++;
            if (cssDiff <= 5) console.log(`CSS line ${i + 1} diff:`, origCssLines[i], 'vs', newCssLines[i]);
        }
    }
    console.log('CSS line diff count:', cssDiff);
    if (cssDiff === 0) console.log('PASS: 100% exact match for CSS!');
}

// 2. HTML Audit
const origHtmlLines = origLines.slice(779, 1037);
console.log('Original HTML lines:', origHtmlLines.length);
let missingHtml = 0;
origHtmlLines.forEach((line, idx) => {
    if (line.trim() && !newBlade.includes(line.trim())) {
        missingHtml++;
        if (missingHtml <= 5) console.log(`Missing HTML line ${idx + 780}:`, line.trim());
    }
});
console.log('Missing HTML lines:', missingHtml);
if (missingHtml === 0) console.log('PASS: 100% HTML preserved!');

// 3. JS Audit - Check for remaining Blade syntax
const bladeMatches = newJs.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g) || [];
console.log('Remaining Blade tokens in JS:', bladeMatches);
if (bladeMatches.length === 0) {
    console.log('PASS: 0 Blade tokens in external JS!');
} else {
    console.error('ERROR: Blade tokens found in JS!');
}

// 4. Check for nested style/script in new Blade
const bladeHasStyle = /<style/i.test(newBlade);
const bladeHasInlineScript = (newBlade.match(/<script/gi) || []).length;
console.log('Blade has <style> tag:', bladeHasStyle);
console.log('Blade <script> count (should be 1 for blade-data):', bladeHasInlineScript);

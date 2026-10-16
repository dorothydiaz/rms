const fs = require('fs');

const origContent = fs.readFileSync('scratch/vendor-masterlist.blade.php.original', 'utf8');
const origLines = origContent.split(/\r?\n/);

const newBlade = fs.readFileSync('resources/views/purchase/vendor-masterlist.blade.php', 'utf8');
const newCss = fs.readFileSync('resources/css/modules/vendor-masterlist.css', 'utf8');
const newJs = fs.readFileSync('resources/js/modules/vendor-masterlist.js', 'utf8');

console.log('=== AUDITING VENDOR MASTERLIST ASSETS ===');

// 1. CSS Audit
const origCssLines = origLines.slice(6, 1795);
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
const origHtmlLines = origLines.slice(1799, 2907);
console.log('Original HTML lines:', origHtmlLines.length);
let missingHtml = 0;
origHtmlLines.forEach((line, idx) => {
    if (line.trim() && !newBlade.includes(line.trim())) {
        missingHtml++;
        if (missingHtml <= 5) console.log(`Missing HTML line ${idx + 1800}:`, line.trim());
    }
});
console.log('Missing HTML lines:', missingHtml);
if (missingHtml === 0) console.log('PASS: 100% HTML preserved!');

// 3. JS Audit
const origJsLines = origLines.slice(2911, 6521);
console.log('Original JS lines:', origJsLines.length);
let missingJs = 0;
origJsLines.forEach((line, idx) => {
    if (line.trim() && !newJs.includes(line.trim())) {
        missingJs++;
        if (missingJs <= 5) console.log(`Missing JS line ${idx + 2912}:`, line.trim());
    }
});
console.log('Missing JS lines:', missingJs);
if (missingJs === 0) console.log('PASS: 100% JS preserved!');

// 4. Check for nested style/script in new Blade
const bladeHasStyle = /<style/i.test(newBlade);
const bladeHasInlineScript = (newBlade.match(/<script/gi) || []).length;
console.log('Blade has <style> tag:', bladeHasStyle);
console.log('Blade <script> count (should be 1 for blade-data):', bladeHasInlineScript);

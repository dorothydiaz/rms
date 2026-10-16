const fs = require('fs');

const srcPath = 'resources/views/hr/attendance/timekeeping.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split('\n');

console.log('Total original lines:', lines.length);

// 1. Locate blocks
let styleStart = -1;
let styleEnd = -1;
let scriptStart = -1;
let scriptEnd = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('@push(\'styles\')')) {
        styleStart = i;
    }
    if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('@endpush')) {
        styleEnd = i;
    }
    if (lines[i].includes('@push(\'scripts\')')) {
        scriptStart = i;
    }
    if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) {
        scriptEnd = i;
    }
}

console.log('Style block lines:', styleStart + 1, 'to', styleEnd + 1);
console.log('Script block lines:', scriptStart + 1, 'to', scriptEnd + 1);

// HTML content is lines from styleEnd + 1 up to scriptStart - 1
const htmlLines = lines.slice(styleEnd + 1, scriptStart);

// Style extraction: strip <style> and </style>
const rawStyleLines = lines.slice(styleStart, styleEnd + 1);
let styleLines = [];
let insideStyle = false;
for (const l of rawStyleLines) {
    if (l.includes('<style>')) {
        insideStyle = true;
        continue;
    }
    if (l.includes('</style>')) {
        insideStyle = false;
        continue;
    }
    if (insideStyle) {
        styleLines.push(l);
    }
}

// Script extraction: strip <script> and </script>
const rawScriptLines = lines.slice(scriptStart, scriptEnd + 1);
let scriptLines = [];
let insideScript = false;
for (const l of rawScriptLines) {
    if (l.includes('<script>')) {
        insideScript = true;
        continue;
    }
    if (l.includes('</script>')) {
        insideScript = false;
        continue;
    }
    if (insideScript) {
        scriptLines.push(l);
    }
}

console.log('Extracted style lines:', styleLines.length);
console.log('Extracted script lines:', scriptLines.length);
console.log('Extracted HTML lines:', htmlLines.length);

const cssText = styleLines.join('\n');
let jsText = scriptLines.join('\n');

const topLevelFunctions = [
    'openModal',
    'closeModal',
    '_tkLevenshtein',
    '_tkFuzzyMatch',
    'tkGetPerPage',
    'tkChangePerPage',
    'tkGoToPage',
    'renderTkPage',
    'filterTimekeepingRows',
    'clearTkSearch'
];

const windowExports = `
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
${topLevelFunctions.map(f => `    ${f},`).join('\n')}
});
`;

jsText += windowExports;

// Save backup before writing
fs.copyFileSync(srcPath, 'scratch/timekeeping.blade.php.bak');

// Write CSS and JS files
fs.writeFileSync('resources/css/modules/hr-timekeeping.css', cssText, 'utf8');
fs.writeFileSync('resources/js/modules/hr-timekeeping.js', jsText, 'utf8');

// Build Clean Blade View
const cleanBlade = `@extends('layouts.app')

@section('title', 'Staff Timekeeping - Time & Attendance Management')

@push('styles')
    @vite('resources/css/modules/hr-timekeeping.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-timekeeping.js')
@endpush

${htmlLines.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully wrote hr-timekeeping.css, hr-timekeeping.js, and clean timekeeping.blade.php');

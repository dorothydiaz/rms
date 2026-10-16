const fs = require('fs');

const srcPath = 'resources/views/hr/attendance/dtr.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split('\n');

console.log('Total original lines:', lines.length);

// 1. Locate script block
let scriptStart = -1;
let scriptEnd = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('<script>')) {
        scriptStart = i;
    }
    if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('</script>')) {
        scriptEnd = i;
    }
}

console.log('Script block lines:', scriptStart + 1, 'to', scriptEnd + 1);

// HTML content is lines from 0 to scriptStart - 1
const htmlLines = lines.slice(0, scriptStart);

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

console.log('Extracted script lines:', scriptLines.length);
console.log('Extracted HTML lines:', htmlLines.length);

let jsText = scriptLines.join('\n');

// Replace Blade-in-JS tokens
jsText = jsText.replace(
    "fetch('{{ route('hr.attendance.dtr.tags') }}')",
    "fetch(window.HrDtrConfig?.routes?.tags || '')"
);

// Check for any remaining Blade tokens
const remainingBlade = [];
jsText.split('\n').forEach((l, idx) => {
    if (l.includes('{{') || l.includes('@json') || l.includes('{!!') || l.includes('@php')) {
        remainingBlade.push(`${idx + 1}: ${l}`);
    }
});
console.log('Remaining Blade tokens in JS:', remainingBlade.length, remainingBlade);

const topLevelFunctions = [
    'openDtrExportModal',
    'closeDtrExportModal',
    'selectDtrScope',
    'focusDtrTagSearch',
    'setCutoffPreset',
    'loadDtrTags',
    'renderTagDropdown',
    'addDtrTag',
    'removeDtrTag',
    'clearAllDtrTags',
    'renderDtrTagPills',
    'updateDtrMatchCount',
    'escapeHtml',
    'escapeJs'
];

const windowExports = `
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
${topLevelFunctions.map(f => `    ${f},`).join('\n')}
});
`;

jsText += windowExports;

// Save backup before writing
fs.copyFileSync(srcPath, 'scratch/dtr.blade.php.bak');

// Write CSS and JS files
fs.writeFileSync('resources/css/modules/hr-dtr.css', '/* HR Daily Time Record (DTR) Module Styles */\n', 'utf8');
fs.writeFileSync('resources/js/modules/hr-dtr.js', jsText, 'utf8');

// Build Clean Blade View
// Notice in htmlLines:
// Line 1: @extends('layouts.app')
// Line 3: @section('title', 'Daily Time Record (DTR) - Time & Attendance Management')
// Line 5: @section('content')
// So we insert @push('styles'), @push('blade-data'), and @push('scripts') right after @section('title')!

const titleIdx = htmlLines.findIndex(l => l.includes("@section('title'"));
const beforeContent = htmlLines.slice(0, titleIdx + 1);
const contentAndRest = htmlLines.slice(titleIdx + 1);

const cleanBlade = `${beforeContent.join('\n')}

@push('styles')
    @vite('resources/css/modules/hr-dtr.css')
@endpush

@push('blade-data')
<script>
    window.HrDtrConfig = Object.freeze({
        routes: {
            tags: @json(route('hr.attendance.dtr.tags')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-dtr.js')
@endpush

${contentAndRest.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully wrote hr-dtr.css, hr-dtr.js, and clean dtr.blade.php');

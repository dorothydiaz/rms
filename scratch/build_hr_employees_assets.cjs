const fs = require('fs');

const srcPath = 'resources/views/hr/people/employees.blade.php';
const content = fs.readFileSync('scratch/employees.blade.php.bak', 'utf8');
const lines = content.split('\n');

console.log('Total original lines:', lines.length);

// 1. Locate style block: lines 510 to 658 (0-indexed 509 to 657)
let styleStart = -1;
let styleEnd = -1;
let scriptStart = -1;
let scriptEnd = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('<style>')) {
        styleStart = i;
    }
    if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('</style>')) {
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

// HTML part 1: lines 6 to styleStart - 1
const htmlPart1 = lines.slice(5, styleStart); // after @section('content') up to styleStart
// HTML part 2: lines styleEnd + 1 up to scriptStart - 1
const htmlPart2 = lines.slice(styleEnd + 1, scriptStart);

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
console.log('Extracted HTML part 1 lines:', htmlPart1.length);
console.log('Extracted HTML part 2 lines:', htmlPart2.length);

const cssText = styleLines.join('\n');
let jsText = scriptLines.join('\n');

const topLevelFunctions = [
    'openModal',
    'closeModal',
    'previewAddPhoto',
    'empGetPerPage',
    'empChangePerPage',
    'renderEmpPage',
    'empGoToPage',
    'toggleMoreFilters',
    'filterEmployeesDirectory',
    'resetEmployeesDirectory',
    'applyEmpSortFromSelect',
    'sortEmployeesDirectory',
    'syncAddPrimaryCheckbox',
    'toggleEmpActionMenu',
    'updateCpQuickCount',
    'updateTrQuickCounts',
    'syncCpQuickPrimary',
    'openChangePositionModal',
    'openTransferModal',
    'openChangeStatusModal',
    'openEditEmployeeModal',
    'populateEditEmployeeModal',
    'syncEditEmpPrimaryCheckbox',
    'toggleEditEmpSource',
    'previewEditEmpPhoto'
];

const windowExports = `
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
${topLevelFunctions.map(f => `    ${f},`).join('\n')}
});
`;

jsText += windowExports;

// Backup preserved in scratch/employees.blade.php.bak

// Write CSS and JS files
fs.writeFileSync('resources/css/modules/hr-employees.css', cssText, 'utf8');
fs.writeFileSync('resources/js/modules/hr-employees.js', jsText, 'utf8');

// Build Clean Blade View
const cleanBlade = `@extends('layouts.app')

@section('title', 'Employee Management - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/hr-employees.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-employees.js')
@endpush

@section('content')
${htmlPart1.join('\n').trim()}

${htmlPart2.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully wrote hr-employees.css, hr-employees.js, and clean employees.blade.php');

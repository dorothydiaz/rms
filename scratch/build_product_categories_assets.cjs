const fs = require('fs');
const path = require('path');

const srcPath = 'resources/views/inventory/product-categories.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split(/\r?\n/);

console.log('Source total lines: ' + lines.length);

// 1. Locate Style boundaries
const styleStartLine = 6; // 1-based line of <style>
const styleEndLine = 2279; // 1-based line of </style>
console.log('Style tag at line ' + styleStartLine + ': ' + lines[styleStartLine - 1]);
console.log('Style close tag at line ' + styleEndLine + ': ' + lines[styleEndLine - 1]);

if (!lines[styleStartLine - 1].includes('<style') || !lines[styleEndLine - 1].includes('</style>')) {
    throw new Error('Style boundary mismatch!');
}

// Slice exact CSS lines between <style> and </style>
const cssLines = lines.slice(styleStartLine, styleEndLine - 1);
const cssContent = cssLines.join('\n');
console.log('Extracted CSS lines: ' + cssLines.length);

const targetCss = 'resources/css/modules/product-categories.css';
fs.writeFileSync(targetCss, cssContent, 'utf8');
console.log('Wrote CSS to: ' + targetCss + ' (' + fs.statSync(targetCss).size + ' bytes)');

// 2. Locate Script boundaries
const scriptStartLine = 2871; // 1-based line of <script>
const scriptEndLine = 5364; // 1-based line of </script>
console.log('Script tag at line ' + scriptStartLine + ': ' + lines[scriptStartLine - 1]);
console.log('Script close tag at line ' + scriptEndLine + ': ' + lines[scriptEndLine - 1]);

if (!lines[scriptStartLine - 1].includes('<script') || !lines[scriptEndLine - 1].includes('</script>')) {
    throw new Error('Script boundary mismatch!');
}

// Slice exact JS lines between <script> and </script>
let jsLines = lines.slice(scriptStartLine, scriptEndLine - 1);

// Inside the IIFE, right before `})();`, add the missing window assignments:
// Line 5363 is `})();`
// Let's find `})();` in jsLines:
let iifeEndIdx = -1;
for (let i = jsLines.length - 1; i >= 0; i--) {
    if (jsLines[i].trim() === '})();') {
        iifeEndIdx = i;
        break;
    }
}
console.log('Found IIFE closing at relative index: ' + iifeEndIdx);

const exportSnippet = `
    // Expose internal functions to window for inline HTML event handlers & ERP cross-module compatibility
    Object.assign(window, {
        saveProductChanges,
        switchPrimaryTab,
        switchSubTab,
        updateMarginCalculation,
        syncCategoryControls,
        syncSubcategoryDropdown,
        renderTableHeaders,
        renderMasterList,
        renderProductAuditLogs,
        renderCategoryIconGrid,
        renderCategoryColorGrid,
        updateCategoryLivePreview,
        hideCategoryDuplicateWarning,
        hideCategoryAutocomplete,
    });
`;

if (iifeEndIdx !== -1) {
    jsLines.splice(iifeEndIdx, 0, exportSnippet);
} else {
    jsLines.push(exportSnippet);
}

const jsContent = jsLines.join('\n');
const targetJs = 'resources/js/modules/product-categories.js';
fs.writeFileSync(targetJs, jsContent, 'utf8');
console.log('Wrote JS to: ' + targetJs + ' (' + fs.statSync(targetJs).size + ' bytes)');

// 3. Extract HTML content (between line 2282 and line 2869)
// Line 2282 is `@section('content')`
// Line 2869 is before `@push('scripts')` (line 2870)
const htmlLines = lines.slice(2282, 2869); // 0-based: 2282 is line 2283
console.log('HTML line 2283: ' + lines[2282]);
console.log('HTML line 2869: ' + lines[2868]);

const cleanBlade = `@extends('layouts.app')

@section('title', 'Item Master - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/product-categories.css')
@endpush

@push('blade-data')
<script>
    window.ProductCategoriesConfig = Object.freeze({
        routes: {
            stocksOverview: @json(route('inventory.stocks-overview')),
            recipeManagement: @json(route('inventory.recipe-management')),
            stockIn: @json(route('inventory.stock-in')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/product-categories.js')
@endpush

@section('content')
` + htmlLines.join('\n') + `
@endsection
`;

const targetBlade = 'resources/views/inventory/product-categories.blade.php';
fs.writeFileSync(targetBlade, cleanBlade, 'utf8');
console.log('Wrote Clean Blade to: ' + targetBlade + ' (' + fs.statSync(targetBlade).size + ' bytes)');

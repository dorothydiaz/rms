const fs = require('fs');

const srcPath = 'resources/views/inventory/uom-configuration.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split(/\r?\n/);

console.log('Source total lines: ' + lines.length);

// 1. Locate Style boundaries
const styleStartLine = 6; // 1-based line of <style>
const styleEndLine = 776; // 1-based line of </style>
console.log('Style tag at line ' + styleStartLine + ': ' + lines[styleStartLine - 1]);
console.log('Style close tag at line ' + styleEndLine + ': ' + lines[styleEndLine - 1]);

if (!lines[styleStartLine - 1].includes('<style') || !lines[styleEndLine - 1].includes('</style>')) {
    throw new Error('Style boundary mismatch!');
}

// Slice exact CSS lines between <style> and </style>
const cssLines = lines.slice(styleStartLine, styleEndLine - 1);
const cssContent = cssLines.join('\n');
console.log('Extracted CSS lines: ' + cssLines.length);

const targetCss = 'resources/css/modules/uom-configuration.css';
fs.writeFileSync(targetCss, cssContent, 'utf8');
console.log('Wrote CSS to: ' + targetCss + ' (' + fs.statSync(targetCss).size + ' bytes)');

// 2. Locate Script boundaries
const scriptStartLine = 1041; // 1-based line of <script>
const scriptEndLine = 2068; // 1-based line of </script>
console.log('Script tag at line ' + scriptStartLine + ': ' + lines[scriptStartLine - 1]);
console.log('Script close tag at line ' + scriptEndLine + ': ' + lines[scriptEndLine - 1]);

if (!lines[scriptStartLine - 1].includes('<script') || !lines[scriptEndLine - 1].includes('</script>')) {
    throw new Error('Script boundary mismatch!');
}

// Slice exact JS lines between <script> and </script>
let jsLines = lines.slice(scriptStartLine, scriptEndLine - 1);

// Replace Blade expressions with window.UomConfig
let replacedJs = jsLines.map((line, idx) => {
    let l = line;
    // 1048-1049: let UOM_DATA = @json($initialUnits); / let UOM_CATEGORIES = @json($initialCategories);
    if (l.includes('@json($initialUnits)')) {
        return 'const config = window.UomConfig || {};\nlet UOM_DATA = config.initialUnits || [];';
    }
    if (l.includes('@json($initialCategories)')) {
        return 'let UOM_CATEGORIES = config.initialCategories || [];';
    }
    // CSRF token in payload
    l = l.replace("_token: '{{ csrf_token() }}'", '_token: (window.UomConfig && window.UomConfig.csrfToken) || ""');
    // CSRF token in headers
    l = l.replace("'X-CSRF-TOKEN': '{{ csrf_token() }}'", "'X-CSRF-TOKEN': (window.UomConfig && window.UomConfig.csrfToken) || ''");
    // URLs
    l = l.replace("`{{ url('/inventory/api/uoms') }}/${id}`", "`${window.UomConfig.routes.uomsApi}/${id}`");
    l = l.replace("`{{ route('inventory.api.uoms.store') }}`", "window.UomConfig.routes.uomsStore");
    l = l.replace("`{{ url('/inventory/api/uoms') }}/${id}/toggle-active`", "`${window.UomConfig.routes.uomsApi}/${id}/toggle-active`");
    l = l.replace("`{{ url('/inventory/api/uom-categories') }}/${targetId}/toggle-active`", "`${window.UomConfig.routes.uomCategoriesApi}/${targetId}/toggle-active`");
    l = l.replace("`{{ route('inventory.api.uom-categories.store') }}`", "window.UomConfig.routes.uomCategoriesStore");
    l = l.replace("`{{ route('inventory.api.uoms') }}?all=1`", "`${window.UomConfig.routes.uomsList}?all=1`");

    return l;
});

// Expose all functions to window for inline HTML handlers & cross-module integration
const exportSnippet = `
// Expose functions to window for inline HTML event handlers & cross-module calls
Object.assign(window, {
    initColumnConfig,
    saveColumnConfig,
    resetColumnDefaults,
    toggleAllColumns,
    updateSettingsGearCounter,
    renderTableHeader,
    toggleSettingsDropdown,
    populateSettingsSwitchList,
    filterColumnSettingsList,
    handleColumnSwitch,
    toggleCategoryExpansion,
    getFilteredCategories,
    renderCategoryDirectory,
    getCategoryBadgeClass,
    formatNumber,
    formatMultiplier,
    escapeHtml,
    renderDirectoryPagination,
    goToPage,
    changePageSize,
    handleUomSearch,
    openNewUomDrawer,
    openNewUomDrawerForCategory,
    openEditUomDrawer,
    closeUomDrawer,
    handleIsBaseToggle,
    handleDrawerCategoryChange,
    updateDrawerFormulaPreview,
    handleSaveUom,
    toggleUomActive,
    toggleCategoryActive,
    openNewCategoryModal,
    closeCategoryModal,
    handleSaveCategory,
    reloadDataFromServer,
    showToast
});
`;

replacedJs.push(exportSnippet);

const jsContent = replacedJs.join('\n');
const targetJs = 'resources/js/modules/uom-configuration.js';
fs.writeFileSync(targetJs, jsContent, 'utf8');
console.log('Wrote JS to: ' + targetJs + ' (' + fs.statSync(targetJs).size + ' bytes)');

// 3. Extract HTML content (between line 779 and line 1038)
// Line 779 is `@section('content')`
// Line 1038 is `@endsection`
const htmlLines = lines.slice(779, 1037); // 0-based: 779 is line 780
console.log('HTML line 780: ' + lines[779]);
console.log('HTML line 1037: ' + lines[1036]);

const cleanBlade = `@extends('layouts.app')

@section('title', 'Unit of Measure Configuration - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/uom-configuration.css')
@endpush

@push('blade-data')
<script>
    window.UomConfig = Object.freeze({
        initialUnits: @json($initialUnits),
        initialCategories: @json($initialCategories),
        csrfToken: @json(csrf_token()),
        routes: {
            uomsApi: @json(url('/inventory/api/uoms')),
            uomsStore: @json(route('inventory.api.uoms.store')),
            uomCategoriesApi: @json(url('/inventory/api/uom-categories')),
            uomCategoriesStore: @json(route('inventory.api.uom-categories.store')),
            uomsList: @json(route('inventory.api.uoms')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/uom-configuration.js')
@endpush

@section('content')
` + htmlLines.join('\n') + `
@endsection
`;

const targetBlade = 'resources/views/inventory/uom-configuration.blade.php';
fs.writeFileSync(targetBlade, cleanBlade, 'utf8');
console.log('Wrote Clean Blade to: ' + targetBlade + ' (' + fs.statSync(targetBlade).size + ' bytes)');

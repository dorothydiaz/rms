const fs = require('fs');

const srcPath = 'resources/views/purchase/vendor-masterlist.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split(/\r?\n/);

console.log('Source total lines: ' + lines.length);

// 1. Locate Style boundaries
const styleStartLine = 6; // 1-based line of <style>
const styleEndLine = 1796; // 1-based line of </style>
console.log('Style tag at line ' + styleStartLine + ': ' + lines[styleStartLine - 1]);
console.log('Style close tag at line ' + styleEndLine + ': ' + lines[styleEndLine - 1]);

if (!lines[styleStartLine - 1].includes('<style') || !lines[styleEndLine - 1].includes('</style>')) {
    throw new Error('Style boundary mismatch!');
}

// Slice exact CSS lines between <style> and </style>
const cssLines = lines.slice(styleStartLine, styleEndLine - 1);
const cssContent = cssLines.join('\n');
console.log('Extracted CSS lines: ' + cssLines.length);

const targetCss = 'resources/css/modules/vendor-masterlist.css';
fs.writeFileSync(targetCss, cssContent, 'utf8');
console.log('Wrote CSS to: ' + targetCss + ' (' + fs.statSync(targetCss).size + ' bytes)');

// 2. Locate Script boundaries
const scriptStartLine = 2911; // 1-based line of <script>
const scriptEndLine = 6522; // 1-based line of </script>
console.log('Script tag at line ' + scriptStartLine + ': ' + lines[scriptStartLine - 1]);
console.log('Script close tag at line ' + scriptEndLine + ': ' + lines[scriptEndLine - 1]);

if (!lines[scriptStartLine - 1].includes('<script') || !lines[scriptEndLine - 1].includes('</script>')) {
    throw new Error('Script boundary mismatch!');
}

// Slice exact JS lines between <script> and </script>
let jsLines = lines.slice(scriptStartLine, scriptEndLine - 1);

// Expose all functions & variables to window for inline HTML event handlers & cross-module interoperability
const exportSnippet = `
// Expose functions and databases to window for inline HTML event handlers & ERP cross-module calls
Object.assign(window, {
    ITEM_MASTER_DICTIONARY,
    SEEDED_VENDORS,
    VENDOR_DATABASE,
    currentVendorId,
    activeFilterCategory,
    activeTabId,
    DEFAULT_VENDOR_CATEGORIES,
    CUSTOM_VENDOR_CATEGORIES,
    loadCustomCategories,
    saveCustomCategories,
    sanitizeVendor,
    getActiveVendorCategories,
    renderModularCategoryFilters,
    scrollFilterPills,
    updatePillScrollControls,
    saveToLocalStorage,
    loadFromLocalStorage,
    resetVendorDatabaseToDefaults,
    updateDirectoryMetrics,
    populateItemMasterDropdown,
    handleItemMasterSelectChange,
    renderKanbanCards,
    filterKanbanCategory,
    handleCategorySelectChange,
    handleKanbanSearch,
    clearKanbanSearch,
    handleCatalogSearch,
    openVendorProfile,
    closeVendorProfile,
    cycleVendor,
    hydrateVendorProfile,
    setText,
    setVal,
    handleSaveGeneralInfo,
    handleSavePurchasingTerms,
    renderContactsGrid,
    renderCatalogTable,
    extractUomPrefix,
    syncSingleItemToItemMaster,
    syncAllCatalogPricesToItemMaster,
    switchVendorTab,
    openModal,
    closeModal,
    openAddCatalogModal,
    openEditCatalogModal,
    updateConversionPreview,
    handleSaveCatalogItem,
    deleteCatalogItem,
    openAddContactModal,
    openEditContactModal,
    handleSaveContact,
    deleteContact,
    setDefaultContact,
    toggleSameAsBilling,
    openCategoryManagerModal,
    renderCategoryManagerList,
    addNewCategoryFromModal,
    selectCategoryFromModal,
    renameCategoryFromModal,
    deleteCategoryFromModal,
    refreshAllCategoryDropdowns,
    promptAddNewCategoryFromProfile,
    updateVendorNavCounter,
    updateBannerStatusDisplay,
    toggleQuickStatusDropdown,
    quickChangeVendorStatus,
    copyToClipboard,
    fallbackCopy,
    handleQuickPhoneCall,
    handleQuickEmail,
    saveCurrentVendorProfileForm,
    setDeliveryDaysPreset,
    exportVendorDossier,
    openCreatePOModalForCurrentVendor,
    calculatePOTotals,
    handleCreatePOSubmit,
    openAddVendorModal,
    populateAddVendorCategorySelect,
    handleNewVendorCategoryChange,
    handleSaveNewVendor,
    exportVendorDirectory,
    formatMoney,
    showToast
});
`;

jsLines.push(exportSnippet);

const jsContent = jsLines.join('\n');
const targetJs = 'resources/js/modules/vendor-masterlist.js';
fs.writeFileSync(targetJs, jsContent, 'utf8');
console.log('Wrote JS to: ' + targetJs + ' (' + fs.statSync(targetJs).size + ' bytes)');

// 3. Extract HTML content (between line 1800 and line 2908)
// Line 1799 is `@section('content')`
// Line 2908 is `@endsection`
const htmlLines = lines.slice(1799, 2907); // 0-based: 1799 is line 1800, 2906 is line 2907
console.log('HTML line 1800: ' + lines[1799]);
console.log('HTML line 2907: ' + lines[2906]);

const cleanBlade = `@extends('layouts.app')

@section('title', 'Vendor Masterlist & Partner Directory - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/vendor-masterlist.css')
@endpush

@push('blade-data')
<script>
    window.VendorMasterlistConfig = Object.freeze({
        routes: {
            purchaseOrders: @json(route('purchase.purchase-orders')),
            rfq: @json(route('purchase.request-quotations')),
            productCategories: @json(route('inventory.product-categories')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/vendor-masterlist.js')
@endpush

@section('content')
` + htmlLines.join('\n') + `
@endsection
`;

const targetBlade = 'resources/views/purchase/vendor-masterlist.blade.php';
fs.writeFileSync(targetBlade, cleanBlade, 'utf8');
console.log('Wrote Clean Blade to: ' + targetBlade + ' (' + fs.statSync(targetBlade).size + ' bytes)');

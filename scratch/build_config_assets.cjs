/**
 * build_config_assets.cjs
 * Modularizes:
 * 1. config/business-settings.blade.php
 * 2. config/companies-branches.blade.php
 * 3. config/account-settings.blade.php
 */

const fs = require('fs');
const path = require('path');

// ==========================================
// 1. config/business-settings.blade.php
// ==========================================
console.log('Processing config/business-settings.blade.php...');
const bizPath = 'resources/views/config/business-settings.blade.php';
const bizRaw = fs.readFileSync(bizPath, 'utf8');
const bizLines = bizRaw.split('\n');

// Style is lines 968 to 1839 (0-indexed: 967 to 1839)
const bizCss = bizLines.slice(967, 1839).join('\n').trim() + '\n';

// Script is lines 1843 to 1905 (0-indexed: 1842 to 1905)
const bizJsRaw = bizLines.slice(1842, 1905).join('\n');

const bizJs = `/**
 * Business Settings Module
 */

${bizJsRaw}

Object.assign(window, {
    previewImage,
    openUploadDocModal,
    closeUploadDocModal,
    openAddBranchModal,
    closeAddBranchModal,
    openEditBranchModal,
    closeEditBranchModal,
});
`;

// HTML content is lines 6 to 965 (0-indexed: 5 to 965)
const bizHtml = bizLines.slice(5, 965).join('\n');

const newBizBlade = `@extends('layouts.app')

@section('title', 'Business Settings - Company & Branch Management')

@push('styles')
    @vite(['resources/css/modules/business-settings.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/business-settings.js'])
@endpush

@section('content')
${bizHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/business-settings.css', bizCss, 'utf8');
fs.writeFileSync('resources/js/modules/business-settings.js', bizJs, 'utf8');
fs.writeFileSync(bizPath, newBizBlade, 'utf8');
console.log('Saved business-settings.blade.php, business-settings.css, business-settings.js');


// ==========================================
// 2. config/companies-branches.blade.php
// ==========================================
console.log('Processing config/companies-branches.blade.php...');
const cbPath = 'resources/views/config/companies-branches.blade.php';
const cbRaw = fs.readFileSync(cbPath, 'utf8');
const cbLines = cbRaw.split('\n');

// Style is lines 9 to 71 (0-indexed: 8 to 71)
const cbCss = cbLines.slice(8, 71).join('\n').trim() + '\n';

// Script is lines 1029 to 1898 (0-indexed: 1028 to 1898)
let cbJsRaw = cbLines.slice(1028, 1898).join('\n');

// Bridge route('config.companies-branches.index')
cbJsRaw = cbJsRaw.replace(
    "`{{ route('config.companies-branches.index') }}?view=details&company_id=${companyId}`",
    "`${window.CompaniesBranchesConfig?.indexUrl || '/config/companies-branches'}?view=details&company_id=${companyId}`"
);

const cbJs = `/**
 * Companies & Branches Management Module
 */

${cbJsRaw}

// Global window exposure
Object.assign(window, {
    loadSavedDirectoryPreferences,
    saveDirectoryPreferences,
    renderDirectoryTableHeader,
    renderDirectoryColDropdownChecklist,
    toggleDirectoryColVisibility,
    showAllDirectoryColumns,
    resetDirectoryColumnDefaults,
    resetDirectoryColumnWidths,
    handleDirectoryColSearch,
    toggleCompaniesTableSettingsDropdown,
    renderCompaniesDirectory,
    renderPaginationBar,
    changeDirectoryPage,
    changeDirectoryPageSize,
    handleCompaniesDirectorySearch,
    switchViewMode,
    selectCompanyForDetails,
    switchCompanySubTab,
    handleLogoFileSelect,
    handleBrandColorChange,
    selectBrandColorPreset,
    openCreateCompanyModal,
    closeCreateCompanyModal,
    openCreateBranchModal,
    closeCreateBranchModal,
    editBranch,
    closeEditBranchModal,
    handleDeleteFromEditBranchModal,
    confirmDeleteCompany,
    closeConfirmDeleteCompanyModal,
    confirmDeleteBranch,
    closeConfirmDeleteBranchModal,
    handleHeaderDragStart,
    handleHeaderDragOver,
    handleHeaderDrop,
    initColResize,
    escapeHtml,
});
`;

// HTML content is lines 6 to 7 (0-indexed: 5 to 7) and lines 74 to 1018 (0-indexed: 73 to 1018)
const cbHtml = [
    ...cbLines.slice(5, 7),
    ...cbLines.slice(73, 1018)
].join('\n');

const newCbBlade = `@extends('layouts.app')

@section('title', 'Companies & Branches - Business Settings')

@push('blade-data')
<script>
    window.COMPANIES_DATA = @json($companies);
    window.BRANCHES_DATA = @json($allBranches);
    window.SELECTED_COMPANY_ID = {{ $selectedCompany ? $selectedCompany->company_id : 'null' }};
    window.CompaniesBranchesConfig = Object.freeze({
        indexUrl: @json(route('config.companies-branches.index'))
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/companies-branches.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/companies-branches.js'])
@endpush

@section('content')
${cbHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/companies-branches.css', cbCss, 'utf8');
fs.writeFileSync('resources/js/modules/companies-branches.js', cbJs, 'utf8');
fs.writeFileSync(cbPath, newCbBlade, 'utf8');
console.log('Saved companies-branches.blade.php, companies-branches.css, companies-branches.js');


// ==========================================
// 3. config/account-settings.blade.php
// ==========================================
console.log('Processing config/account-settings.blade.php...');
const accPath = 'resources/views/config/account-settings.blade.php';
const accRaw = fs.readFileSync(accPath, 'utf8');
const accLines = accRaw.split('\n');

// Style is lines 266 to 363 (0-indexed: 265 to 363)
const accCss = accLines.slice(265, 363).join('\n').trim() + '\n';

// Script is lines 369 to 386 (0-indexed: 368 to 386)
const accJsRaw = accLines.slice(368, 386).join('\n');

const accJs = `/**
 * Account Settings Module
 */

${accJsRaw}

Object.assign(window, {
    togglePassVisibility,
});
`;

// HTML content is lines 6 to 262 (0-indexed: 5 to 262)
const accHtml = accLines.slice(5, 262).join('\n');

const newAccBlade = `@extends('layouts.app')

@section('title', 'Account Settings - Restaurant Management System')

@push('styles')
    @vite(['resources/css/modules/account-settings.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/account-settings.js'])
@endpush

@section('content')
${accHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/account-settings.css', accCss, 'utf8');
fs.writeFileSync('resources/js/modules/account-settings.js', accJs, 'utf8');
fs.writeFileSync(accPath, newAccBlade, 'utf8');
console.log('Saved account-settings.blade.php, account-settings.css, account-settings.js');

console.log('ALL 3 CONFIG MODULES COMPLETED SUCCESSFULLY!');

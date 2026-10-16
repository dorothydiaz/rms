@extends('layouts.app')

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
        },
        /* Module specifications: CATEGORY_COLUMNS, toggleCategoryExpansion, uom-switch */
        CATEGORY_COLUMNS: 'CATEGORY_COLUMNS',
        toggleCategoryExpansion: 'toggleCategoryExpansion',
        switchClass: 'uom-switch'
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/uom-configuration.js')
@endpush

@section('content')
<div class="uom-page-container">

    <!-- 1. Page Header -->
    <div class="uom-header-row">
        <div class="uom-title-area">
            <h1><i class="ph ph-scales" style="color: var(--uom-primary);"></i> Unit of Measurement (UOM) Configuration</h1>
            <p>Master catalog of measurement categories and expandable conversion units for recipes, purchase orders, and inventory.</p>
        </div>
        <div class="uom-header-actions">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="openNewCategoryModal()">
                <i class="ph ph-folder-plus"></i> <span>New Category</span>
            </button>
            <button type="button" class="uom-btn uom-btn-primary" onclick="openNewUomDrawer()">
                <i class="ph ph-plus-circle"></i> <span>Add UOM</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Metrics Grid -->
    <div class="uom-metrics-grid">
        <div class="uom-metric-card">
            <div class="uom-metric-icon blue"><i class="ph ph-folder"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiTotalCategories">{{ count($initialCategories) }}</div>
                <div class="uom-metric-lbl">Measurement Categories</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon purple"><i class="ph ph-scales"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiTotalUnits">{{ count($initialUnits) }}</div>
                <div class="uom-metric-lbl">Total Measurements</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon emerald"><i class="ph ph-check-circle"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiActiveUnits">{{ $initialUnits->where('uom_is_active', true)->count() }}</div>
                <div class="uom-metric-lbl">Active in Dropdowns</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon amber"><i class="ph ph-star"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiBaseUnits">{{ $initialUnits->where('is_base_unit', true)->count() }}</div>
                <div class="uom-metric-lbl">Reference (Base) Units</div>
            </div>
        </div>
    </div>

    <!-- 3. Toolbar (No Category Tabs) -->
    <div class="uom-toolbar-card">
        <div class="uom-search-box">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="uomSearchInput" class="uom-search-input" placeholder="Search categories, measurement codes, or descriptive names..." oninput="handleUomSearch(this.value)">
        </div>
        <div style="font-size: 0.74rem; color: var(--uom-text-muted); display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-info"></i>
            <span>Click the <strong><i class="ph ph-caret-right"></i></strong> button on any category to view and manage its units.</span>
        </div>
    </div>

    <!-- 4. Category-First Primary Table with Expandable Accordion Subtables -->
    <div class="uom-table-container">
        <div class="uom-table-responsive">
            <table class="uom-table" id="uomDirectoryTable">
                <thead id="uomThead">
                    <tr id="uomTheadRow">
                        <th class="uom-th-stacked lock-visible" style="width: 220px;">
                            <span class="uom-th-title">Category Info</span>
                            <span class="uom-th-sub">Code &amp; Name</span>
                        </th>
                        <th class="uom-th-stacked" style="width: 140px;">
                            <span class="uom-th-title">Base Unit</span>
                            <span class="uom-th-sub">Standard UOM</span>
                        </th>
                        <th class="uom-th-stacked" style="width: 130px;">
                            <span class="uom-th-title">Total Units</span>
                            <span class="uom-th-sub">Count of Units</span>
                        </th>
                        <th class="uom-th-stacked" style="width: 110px;">
                            <span class="uom-th-title">Status</span>
                            <span class="uom-th-sub">Category State</span>
                        </th>
                        <th class="uom-th-stacked uom-th-actions lock-visible" style="width: 120px; text-align: right;">
                            <div class="uom-th-actions-header">
                                <span class="uom-th-title">Actions</span>
                                <button type="button" class="uom-th-settings-btn" id="uomSettingsGearBtn" title="Customize Visible Table Columns" onclick="toggleSettingsDropdown(event)">
                                    <i class="ph ph-gear-six"></i>
                                </button>
                            </div>
                            <span class="uom-th-sub">Manage &amp; Setup</span>
                        </th>
                    </tr>
                </thead>
                <tbody id="uomTbody">
                    <!-- Rendered dynamically by renderCategoryDirectory() -->
                </tbody>
                <!-- Template for dynamic accordion subtable rendering -->
                <template id="uomAccordionRowTemplate">
                    <tr class="uom-line-expansion-row">
                        <td colspan="5">
                            <button type="button" class="uom-row-expand-btn"></button>
                            <div class="uom-subtable-container">
                                <table class="uom-subtable"></table>
                            </div>
                        </td>
                    </tr>
                </template>
            </table>

            <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear (Switchable List) -->
            <div id="uomTableSettingsDropdown" class="uom-table-settings-dropdown">
                <div class="uom-settings-title-row">
                    <div class="uom-settings-title">
                        <i class="ph ph-gear-six" style="color: var(--uom-primary);"></i>
                        <span>Visible Columns & Setup</span>
                    </div>
                    <span class="uom-col-counter" id="uomColActiveCounter">5 of 6</span>
                </div>
                <div class="uom-settings-links-row">
                    <button type="button" class="uom-settings-link-btn" onclick="resetColumnDefaults()">Reset to Default</button>
                    <button type="button" class="uom-settings-link-btn" onclick="toggleAllColumns(true)">Select All</button>
                </div>
                <input type="text" class="uom-settings-search-input" placeholder="Filter columns..." oninput="filterColumnSettingsList(this.value)">
                <div class="uom-settings-col-list" id="uomSettingsCheckboxesList">
                    <!-- Populated dynamically with switchable toggles -->
                </div>
            </div>
        </div>

        <!-- Pagination Bar with Rows-Per-Page in Footer -->
        <div class="uom-pagination-bar" id="uomPaginationBar">
            <div class="uom-pagination-info" id="uomPaginationInfo">
                Showing <strong>1</strong> to <strong>{{ count($initialCategories) }}</strong> of <strong>{{ count($initialCategories) }}</strong> categories
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 0.74rem; color: var(--uom-text-muted);">
                    <span>Show</span>
                    <select id="uomPageSize" class="uom-form-select" style="width: auto; padding: 3px 8px; font-size: 0.75rem; border-radius: 6px;" onchange="changePageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>rows</span>
                </div>
                <div class="uom-pagination-controls" id="uomPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

</div>

<!-- 5. Slide-out Drawer: Add / Edit UOM -->
<div class="uom-drawer-overlay" id="uomDrawerOverlay" onclick="closeUomDrawer()"></div>
<div class="uom-drawer" id="uomDrawer">
    <div class="uom-drawer-header">
        <div class="uom-drawer-title">
            <i class="ph ph-scales" style="color: var(--uom-primary);"></i>
            <span id="uomDrawerTitle">Add Unit of Measurement</span>
        </div>
        <button type="button" class="uom-drawer-close" onclick="closeUomDrawer()"><i class="ph ph-x"></i></button>
    </div>
    <form id="uomForm" onsubmit="handleSaveUom(event)">
        <input type="hidden" id="formUomId">
        <div class="uom-drawer-body">

            <div class="uom-form-row-2">
                <div class="uom-form-group">
                    <label for="formUomCode">UoM Short Code <span class="req">*</span></label>
                    <input type="text" id="formUomCode" class="uom-form-input" required placeholder="e.g. PCS, BOX-10, KG" style="text-transform: uppercase;" oninput="updateDrawerFormulaPreview()">
                </div>
                <div class="uom-form-group">
                    <label for="formUomCategory">Category <span class="req">*</span></label>
                    <div style="display: flex; gap: 6px;">
                        <select id="formUomCategory" class="uom-form-select" required onchange="handleDrawerCategoryChange()">
                            @foreach($initialCategories as $cat)
                                <option value="{{ $cat->category_code }}" data-base="{{ $cat->base_uom_code }}">{{ $cat->category_code }} ({{ $cat->category_name }})</option>
                            @endforeach
                        </select>
                        <button type="button" class="uom-btn uom-btn-secondary" style="padding: 0 8px;" onclick="openNewCategoryModal()" title="Add New Category">
                            <i class="ph ph-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="uom-form-group">
                <label for="formUomName">Descriptive Measurement Name <span class="req">*</span></label>
                <input type="text" id="formUomName" class="uom-form-input" required placeholder="e.g. Pieces / Each, Box of 10 Cans, Kilogram">
            </div>

            <!-- Reference / Base Checkbox -->
            <div style="background: #f8fafc; border: 1px solid var(--uom-border-subtle); border-radius: 8px; padding: 10px 14px; display: flex; align-items: flex-start; gap: 10px;">
                <input type="checkbox" id="formIsBase" style="margin-top: 3px;" onchange="handleIsBaseToggle(this.checked)">
                <div>
                    <label for="formIsBase" style="font-weight: 700; cursor: pointer; color: var(--uom-text-strong);">Is Category Reference (Base) Unit?</label>
                    <div style="font-size: 0.72rem; color: var(--uom-text-muted);">
                        The reference unit is the foundational 1.0 multiplier for this category (e.g. Gram for Weight, Piece for Count). All other units convert relative to it.
                    </div>
                </div>
            </div>

            <!-- Ratio / Multiplier Section -->
            <div id="uomRatioSection">
                <div class="uom-form-row-2">
                    <div class="uom-form-group">
                        <label for="formBaseRatio">Measurement Quantity / Ratio <span class="req">*</span></label>
                        <input type="number" step="any" min="0.000001" id="formBaseRatio" class="uom-form-input" value="1.0" required oninput="updateDrawerFormulaPreview()">
                    </div>
                    <div class="uom-form-group">
                        <label for="formPrecision">Rounding Precision</label>
                        <input type="number" step="0.0001" min="0.0001" id="formPrecision" class="uom-form-input" value="0.0100">
                    </div>
                </div>

                <div class="uom-ratio-preview-box" id="uomFormulaPreviewBox">
                    <i class="ph ph-calculator" style="font-size: 18px; color: var(--uom-primary);"></i>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--uom-text-muted);">Conversion Formula Preview:</div>
                        <div class="uom-ratio-formula" id="uomFormulaPreviewText">1 BOX-10 = 10 PCS</div>
                    </div>
                </div>
            </div>

            <!-- Active in Dropdowns Toggle -->
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #f1f5f9;">
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; color: var(--uom-text-strong);">Active in Dropdowns</div>
                    <div style="font-size: 0.72rem; color: var(--uom-text-muted);">Deactivating hides this unit from new selections without breaking historical records.</div>
                </div>
                <label class="uom-switch">
                    <input type="checkbox" id="formIsActive" checked>
                    <span class="uom-slider"></span>
                </label>
            </div>

        </div>
        <div class="uom-drawer-footer">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="closeUomDrawer()">Cancel</button>
            <button type="submit" class="uom-btn uom-btn-primary" id="uomSubmitBtn">
                <i class="ph ph-check"></i> <span id="uomSubmitBtnText">Save Measurement</span>
            </button>
        </div>
    </form>
</div>

<!-- 6. Modal: Create New Category -->
<div class="uom-drawer-overlay" id="uomCategoryModalOverlay" onclick="closeCategoryModal()"></div>
<div class="uom-modal" id="uomCategoryModal">
    <div class="uom-drawer-header">
        <div class="uom-drawer-title">
            <i class="ph ph-folder-plus" style="color: var(--uom-primary);"></i>
            <span>Create Measurement Category</span>
        </div>
        <button type="button" class="uom-drawer-close" onclick="closeCategoryModal()"><i class="ph ph-x"></i></button>
    </div>
    <form id="categoryForm" onsubmit="handleSaveCategory(event)">
        <div class="uom-drawer-body">
            <div class="uom-form-group">
                <label for="catCode">Category Code <span class="req">*</span></label>
                <input type="text" id="catCode" class="uom-form-input" required placeholder="e.g. CONTAINER, PACKAGING, AREA" style="text-transform: uppercase;">
            </div>
            <div class="uom-form-group">
                <label for="catName">Category Descriptive Name <span class="req">*</span></label>
                <input type="text" id="catName" class="uom-form-input" required placeholder="e.g. Shipping & Storage Containers">
            </div>
            <div class="uom-form-group">
                <label for="catBase">Default Base Unit Short Code</label>
                <input type="text" id="catBase" class="uom-form-input" placeholder="e.g. CONTAINER (Leave blank to set on first unit)">
            </div>
            <div class="uom-form-group">
                <label for="catDesc">Category Description</label>
                <textarea id="catDesc" class="uom-form-textarea" rows="2" placeholder="Explain the physical dimension or operational purpose of this category..."></textarea>
            </div>
        </div>
        <div class="uom-drawer-footer">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="closeCategoryModal()">Cancel</button>
            <button type="submit" class="uom-btn uom-btn-primary" id="catSubmitBtn">
                <i class="ph ph-check"></i> <span>Create Category</span>
            </button>
        </div>
    </form>
</div>

<!-- Soft Toast Alert -->
<div class="uom-toast" id="uomToast">
    <i class="ph ph-check-circle" id="uomToastIcon"></i>
    <span id="uomToastText">Operation successful</span>
</div>
@endsection

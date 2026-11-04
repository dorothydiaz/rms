@extends('layouts.app')

@section('title', 'Warehouse & Storage Area - Inventory Configuration')

@push('styles')
    @vite('resources/css/modules/warehouse-storage.css')
@endpush

@push('blade-data')
<script>
    window.WarehouseStorageConfig = Object.freeze({
        initialWarehouses: @json($initialWarehouses ?? []),
        initialStorageAreas: @json($initialStorageAreas ?? []),
        initialBoundaryAreas: @json($initialBoundaryAreas ?? []),
        csrfToken: @json(csrf_token()),
        routes: {
            warehousesApi: @json(route('inventory.api.warehouses')),
            movementFlowsPage: @json(route('inventory.movement-flows')),
            stocksOverviewPage: @json(route('inventory.stocks-overview')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/warehouse-storage.js')
@endpush

@section('content')
<div class="wh-workspace" id="whStorageApp">

    <!-- Toast Notifications Container -->
    <div class="wh-toast-container" id="whToastContainer"></div>

    <!-- 1. Page Header (Consistent Flat ERP Title Row) -->
    <div class="hr-parent-title" style="margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="wh-title-icon">
                <i class="ph ph-warehouse"></i>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 1.32rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">Warehouse & Storage Area</h1>
                <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Physical facility directory, nested storage hierarchy (Zones, Aisles, Racks, Bins), handheld scanner barcodes & bulk bin generator</p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <a href="{{ route('inventory.movement-flows') }}" class="wh-btn wh-btn-outline" style="text-decoration: none;">
                <i class="ph ph-git-branch"></i>
                <span>Movement Types & Supply Flows</span>
            </a>
            <button type="button" class="wh-btn wh-btn-secondary" onclick="openBulkGeneratorModal()">
                <i class="ph ph-lightning"></i>
                <span>Bulk Bin Generator</span>
            </button>
            <button type="button" class="wh-btn wh-btn-primary" onclick="openWarehouseModal()">
                <i class="ph ph-plus-circle"></i>
                <span>New Warehouse</span>
            </button>
        </div>
    </div>

    <!-- 2. Top KPI Strip & Handheld Barcode Scanner Lookup Bar -->
    <div class="wh-top-strip">
        <div class="wh-kpi-grid">
            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Active Warehouses</span>
                    <span class="wh-kpi-icon purple"><i class="ph ph-buildings"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiActiveWarehouses">0</div>
                <div class="wh-kpi-sub" id="whKpiTotalWarehousesSub">Auto-seeded with Dock & Stock zones</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Physical Storage Areas</span>
                    <span class="wh-kpi-icon blue"><i class="ph ph-tree-structure"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiTotalAreas">0</div>
                <div class="wh-kpi-sub" id="whKpiBehaviorBreakdownSub">0 Storage • 0 Staging • 0 Dock</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Barcoded Bins & Racks</span>
                    <span class="wh-kpi-icon emerald"><i class="ph ph-barcode"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiBarcodedBins">0</div>
                <div class="wh-kpi-sub">100% unique handheld scanner codes</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Stocked Areas Protected</span>
                    <span class="wh-kpi-icon amber"><i class="ph ph-shield-check"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiStockedAreas">0</div>
                <div class="wh-kpi-sub" id="whKpiStockedUnitsSub">Non-negative physical balance enforced</div>
            </div>
        </div>

        <!-- Handheld Scanner Barcode Lookup Bar -->
        <div class="wh-scanner-bar">
            <div class="wh-scanner-left">
                <div class="wh-scanner-badge">
                    <i class="ph ph-barcode"></i>
                    <span>Handheld Scanner Test & Barcode Lookup</span>
                </div>
                <div class="wh-scanner-input-wrap">
                    <i class="ph ph-CornersOut"></i>
                    <input type="text" id="whBarcodeScannerInput" class="wh-scanner-input" placeholder="Scan or type area barcode (e.g. WH-MAIN-ZA-RA1-B01) and press Enter..." autocomplete="off">
                    <button type="button" class="wh-btn wh-btn-primary wh-btn-sm" onclick="triggerBarcodeScanLookup()">
                        <i class="ph ph-magnifying-glass"></i> Locate Area
                    </button>
                </div>
            </div>
            <div class="wh-scanner-samples" id="whScannerSamplePills">
                <!-- Populated dynamically with quick-test barcodes -->
            </div>
        </div>
    </div>

    <!-- 3. Split-View Workspace: Left Warehouse List / Right Nested Storage Tree -->
    <div class="wh-split-layout">

        <!-- LEFT PANEL: Warehouses List & System Boundary Notice -->
        <div class="wh-left-panel">
            <div class="wh-panel-card">
                <div class="wh-panel-header">
                    <div class="wh-panel-title">
                        <i class="ph ph-buildings"></i>
                        <span>Warehouses</span>
                        <span class="wh-count-pill" id="whListCountBadge">0</span>
                    </div>
                    <button type="button" class="wh-btn wh-btn-outline wh-btn-xs" onclick="openWarehouseModal()">
                        <i class="ph ph-plus"></i> Add
                    </button>
                </div>

                <div class="wh-left-filter-bar">
                    <div class="wh-search-box">
                        <i class="ph ph-magnifying-glass"></i>
                        <input type="text" id="whDirectorySearch" placeholder="Search code, name, address..." oninput="renderWarehouseList()">
                    </div>
                </div>

                <div class="wh-warehouse-list" id="whWarehouseListContainer">
                    <!-- Populated dynamically by renderWarehouseList() -->
                </div>

                <!-- Boundary Areas System Integrity Box (Hidden from Physical Tree by Design) -->
                <div class="wh-boundary-info-box">
                    <div class="wh-boundary-head" onclick="toggleBoundaryDrawerInfo()">
                        <div style="display: flex; align-items: center; gap: 6px;">
                            <i class="ph ph-shield-star"></i>
                            <span>4 Virtual Boundary Areas (Ledger-Only)</span>
                        </div>
                        <i class="ph ph-caret-down" id="whBoundaryCaret"></i>
                    </div>
                    <div class="wh-boundary-body" id="whBoundaryBody">
                        <p class="wh-boundary-desc">
                            Boundary areas belong to no physical warehouse and never appear in the storage tree. They act as immutable ledger counterparts so every stock movement has a valid <strong>From</strong> and <strong>To</strong>:
                        </p>
                        <div class="wh-boundary-pills" id="whBoundaryPillsList">
                            <!-- Populated dynamically from initialBoundaryAreas -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: Selected Warehouse Storage Area Tree -->
        <div class="wh-right-panel">
            <div class="wh-panel-card wh-tree-panel">
                <!-- Selected Warehouse Banner & Quick Area Creation Buttons -->
                <div class="wh-tree-header">
                    <div class="wh-tree-wh-identity">
                        <div class="wh-tree-wh-top">
                            <span class="wh-code-badge large" id="selectedWhCodeBadge">WH-MAIN</span>
                            <h2 class="wh-tree-wh-name" id="selectedWhNameTitle">Central Commissary & Dry Warehouse</h2>
                            <span class="wh-status-pill active" id="selectedWhStatusBadge">Active</span>
                        </div>
                        <div class="wh-tree-wh-address" id="selectedWhAddressText">
                            <i class="ph ph-map-pin"></i>
                            <span>Bldg 4 Industrial Logistics Park, Pasig Blvd, Metro Manila</span>
                        </div>
                    </div>

                    <div class="wh-tree-quick-actions">
                        <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="openStorageAreaModal({ presetType: 'Zone', presetBehavior: 'Storage' })">
                            <i class="ph ph-squares-four"></i> + Add Zone
                        </button>
                        <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="openStorageAreaModal({ presetType: 'Rack', presetBehavior: 'Storage' })">
                            <i class="ph ph-rows"></i> + Add Rack / Aisle
                        </button>
                        <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="openStorageAreaModal({ presetType: 'Bin', presetBehavior: 'Storage' })">
                            <i class="ph ph-package"></i> + Add Bin
                        </button>
                        <button type="button" class="wh-btn wh-btn-primary wh-btn-sm" onclick="openBulkGeneratorModal()">
                            <i class="ph ph-lightning"></i> Bulk Generate Bins
                        </button>
                    </div>
                </div>

                <!-- Tree Filter & Behavior Legend Bar -->
                <div class="wh-tree-toolbar">
                    <div class="wh-behavior-filters" id="whBehaviorFilterGroup">
                        <button type="button" class="wh-filter-chip active" data-behavior="ALL" onclick="setAreaBehaviorFilter('ALL')">
                            All Physical Areas <span id="countBehAll">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip storage" data-behavior="Storage" onclick="setAreaBehaviorFilter('Storage')" title="Stock rests here (Zones, Racks, Bins)">
                            <span class="dot storage"></span> Storage <span id="countBehStorage">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip staging" data-behavior="Staging" onclick="setAreaBehaviorFilter('Staging')" title="Stock passes through here (Packing Tables, QA Benches)">
                            <span class="dot staging"></span> Staging <span id="countBehStaging">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip dock" data-behavior="Dock" onclick="setAreaBehaviorFilter('Dock')" title="Where stock enters or leaves the building (Receiving / Shipping Docks)">
                            <span class="dot dock"></span> Dock <span id="countBehDock">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip stocked" data-behavior="STOCKED" onclick="setAreaBehaviorFilter('STOCKED')" title="Areas currently holding physical stock (> 0 units)">
                            <i class="ph ph-lock-key"></i> Holds Stock <span id="countBehStocked">0</span>
                        </button>
                    </div>

                    <div class="wh-tree-controls">
                        <div class="wh-tree-search">
                            <i class="ph ph-magnifying-glass"></i>
                            <input type="text" id="whTreeSearchInput" placeholder="Filter area or barcode..." oninput="renderSelectedWarehouseTree()">
                        </div>
                        <button type="button" class="wh-icon-btn" onclick="expandOrCollapseAllTree(true)" title="Expand All Nodes">
                            <i class="ph ph-arrows-out-line-vertical"></i>
                        </button>
                        <button type="button" class="wh-icon-btn" onclick="expandOrCollapseAllTree(false)" title="Collapse All Nodes">
                            <i class="ph ph-arrows-in-line-vertical"></i>
                        </button>
                    </div>
                </div>

                <!-- Interactive Nested Storage Tree Container -->
                <div class="wh-tree-viewport" id="whStorageTreeViewport">
                    <!-- Populated dynamically by renderSelectedWarehouseTree() -->
                </div>

                <!-- Tree Footer Guardrail Notice -->
                <div class="wh-tree-footer">
                    <div class="wh-tree-footer-rule">
                        <i class="ph ph-shield-check"></i>
                        <span><strong>Trust Invariant:</strong> Physical areas can never hold negative stock. Any bin or rack holding stock (> 0) cannot be deleted—only deactivated.</span>
                    </div>
                    <div id="whTreeSummaryCount" class="wh-tree-footer-meta">0 physical areas</div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- ==========================================================================
     MODAL 1: CREATE / EDIT WAREHOUSE
     ========================================================================== -->
<div class="wh-modal-backdrop" id="whWarehouseModal" role="dialog" aria-modal="true" aria-labelledby="whModalTitle">
    <div class="wh-modal-card" style="max-width: 540px;">
        <div class="wh-modal-header">
            <div class="wh-modal-title-group">
                <div class="wh-modal-icon"><i class="ph ph-warehouse"></i></div>
                <div>
                    <div class="wh-modal-title" id="whModalTitle">Create Warehouse</div>
                    <div class="wh-modal-subtitle">Set up a physical building and auto-provision default dock & storage areas</div>
                </div>
            </div>
            <button type="button" class="wh-icon-btn" onclick="closeWarehouseModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="wh-modal-body">
            <input type="hidden" id="whFormId" value="">
            <div class="wh-form-grid">
                <div class="wh-field">
                    <label class="wh-label" for="whFormName">Warehouse Building Name *</label>
                    <input type="text" id="whFormName" class="wh-input" placeholder="e.g. South Commissary & Distribution Hub">
                </div>
                <div class="wh-field">
                    <label class="wh-label" for="whFormCode">Short Code * <span class="wh-hint">(e.g. WH-MAIN)</span></label>
                    <input type="text" id="whFormCode" class="wh-input mono" placeholder="e.g. WH-SOUTH" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '')">
                </div>
                <div class="wh-field full">
                    <label class="wh-label" for="whFormAddress">Physical Building Address *</label>
                    <input type="text" id="whFormAddress" class="wh-input" placeholder="e.g. Lot 12 Industrial Ave, Carmona, Cavite">
                </div>
                <div class="wh-field full">
                    <label class="wh-checkbox-row">
                        <input type="checkbox" id="whFormActive" checked>
                        <span><strong>Warehouse is Active</strong> (Available for receiving, picking, and supply flows)</span>
                    </label>
                </div>
                <div class="wh-field full" id="whAutoSeedNoticeBox">
                    <div class="wh-autoseed-banner">
                        <i class="ph ph-sparkle"></i>
                        <div>
                            <div style="font-weight: 800; font-size: 0.78rem; color: #5b21b6;">Automatic Default Storage Areas</div>
                            <div style="font-size: 0.72rem; color: #6d28d9; margin-top: 2px;">
                                Upon creation, the system automatically seeds <strong>Receiving Dock</strong> (Dock), <strong>Shipping Dock</strong> (Dock), and <strong>Stock Zone</strong> (Storage) with scanner barcodes so operations can start immediately.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="wh-modal-footer">
            <button type="button" class="wh-btn wh-btn-outline" onclick="closeWarehouseModal()">Cancel</button>
            <button type="button" class="wh-btn wh-btn-primary" onclick="saveWarehouseForm()">
                <i class="ph ph-check-circle"></i> Save Warehouse
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: CREATE / EDIT PHYSICAL STORAGE AREA
     ========================================================================== -->
<div class="wh-modal-backdrop" id="whStorageAreaModal" role="dialog" aria-modal="true" aria-labelledby="areaModalTitle">
    <div class="wh-modal-card" style="max-width: 600px;">
        <div class="wh-modal-header">
            <div class="wh-modal-title-group">
                <div class="wh-modal-icon"><i class="ph ph-tree-structure"></i></div>
                <div>
                    <div class="wh-modal-title" id="areaModalTitle">Add Physical Storage Area</div>
                    <div class="wh-modal-subtitle">Configure nesting parent, physical behavior, and unique scanner barcode</div>
                </div>
            </div>
            <button type="button" class="wh-icon-btn" onclick="closeStorageAreaModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="wh-modal-body">
            <input type="hidden" id="areaFormId" value="">
            <div class="wh-form-grid">
                <div class="wh-field">
                    <label class="wh-label" for="areaFormWarehouse">Warehouse Facility</label>
                    <input type="text" id="areaFormWarehouse" class="wh-input mono" readonly style="background: #f8fafc; color: #475569;">
                </div>
                <div class="wh-field">
                    <label class="wh-label" for="areaFormParent">Nested Inside (Parent Area)</label>
                    <select id="areaFormParent" class="wh-select" onchange="autoSuggestAreaBarcode()">
                        <!-- Populated dynamically with cycle-prevention -->
                    </select>
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormType">Structural Level / Type *</label>
                    <select id="areaFormType" class="wh-select" onchange="handleAreaTypeChange(this.value)">
                        <option value="Zone">Zone (e.g. Dry Zone A, Cold Room)</option>
                        <option value="Aisle">Aisle (e.g. Aisle A, Aisle B)</option>
                        <option value="Rack">Rack / Shelf (e.g. Rack B, Shelf 2)</option>
                        <option value="Bin">Bin (e.g. Bin B-12)</option>
                        <option value="Packing Table">Packing / QA Table</option>
                        <option value="Dock Door">Dock Door (Receiving / Shipping)</option>
                    </select>
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormBehavior">Area Behavior *</label>
                    <select id="areaFormBehavior" class="wh-select">
                        <option value="Storage">Storage — Stock rests here (Bins, Racks, Zones)</option>
                        <option value="Staging">Staging — Stock passes through (Packing, QC Table)</option>
                        <option value="Dock">Dock — Stock enters or leaves building</option>
                    </select>
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormName">Area Name *</label>
                    <input type="text" id="areaFormName" class="wh-input" placeholder="e.g. Bin B-12" oninput="autoSuggestAreaBarcode()">
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormCode">Area Code *</label>
                    <input type="text" id="areaFormCode" class="wh-input mono" placeholder="e.g. BIN-B12" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, ''); autoSuggestAreaBarcode();">
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormBarcode">Unique Handheld Scanner Barcode *</label>
                    <input type="text" id="areaFormBarcode" class="wh-input mono" placeholder="e.g. WH-MAIN-ZA-RB-B12" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, '')">
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="areaFormStockQty">Current Physical Stock (Units) <span class="wh-hint">(≥ 0 only)</span></label>
                    <input type="number" id="areaFormStockQty" class="wh-input" min="0" step="1" value="0">
                </div>

                <div class="wh-field full">
                    <label class="wh-checkbox-row">
                        <input type="checkbox" id="areaFormActive" checked>
                        <span><strong>Storage Area is Active</strong> (Can receive scans and hold inventory)</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="wh-modal-footer">
            <button type="button" class="wh-btn wh-btn-outline" onclick="closeStorageAreaModal()">Cancel</button>
            <button type="button" class="wh-btn wh-btn-primary" onclick="saveStorageAreaForm()">
                <i class="ph ph-check-circle"></i> Save Storage Area
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: BULK STORAGE BIN GENERATOR ("Aisle A, Racks 1-10, Bins 1-5" -> 50 Bins)
     ========================================================================== -->
<div class="wh-modal-backdrop" id="whBulkGeneratorModal" role="dialog" aria-modal="true" aria-labelledby="bulkModalTitle">
    <div class="wh-modal-card" style="max-width: 640px;">
        <div class="wh-modal-header">
            <div class="wh-modal-title-group">
                <div class="wh-modal-icon"><i class="ph ph-lightning"></i></div>
                <div>
                    <div class="wh-modal-title" id="bulkModalTitle">Bulk Rack & Bin Barcode Generator</div>
                    <div class="wh-modal-subtitle">Generate nested Racks/Shelves and Barcoded Bins in one batch (e.g. Aisle A, Shelves 1–10, Bins 1–5 = 50 Bins)</div>
                </div>
            </div>
            <button type="button" class="wh-icon-btn" onclick="closeBulkGeneratorModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="wh-modal-body">
            <div class="wh-form-grid">
                <div class="wh-field">
                    <label class="wh-label" for="bulkParentZoneSelect">Target Parent Zone / Area *</label>
                    <select id="bulkParentZoneSelect" class="wh-select" onchange="updateBulkGeneratorPreview()">
                        <!-- Populated dynamically -->
                    </select>
                </div>

                <div class="wh-field">
                    <label class="wh-label" for="bulkAisleCode">Aisle / Section Code *</label>
                    <input type="text" id="bulkAisleCode" class="wh-input mono" value="A" placeholder="e.g. A" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, ''); updateBulkGeneratorPreview();">
                </div>

                <div class="wh-field">
                    <label class="wh-label">Rack / Shelf Number Range *</label>
                    <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 6px;">
                        <input type="number" id="bulkRackStart" class="wh-input" min="1" max="100" value="1" oninput="updateBulkGeneratorPreview()">
                        <span style="font-size: 12px; color: #64748b; font-weight: 700;">to</span>
                        <input type="number" id="bulkRackEnd" class="wh-input" min="1" max="100" value="10" oninput="updateBulkGeneratorPreview()">
                    </div>
                </div>

                <div class="wh-field">
                    <label class="wh-label">Bins per Rack / Shelf Range *</label>
                    <div style="display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 6px;">
                        <input type="number" id="bulkBinStart" class="wh-input" min="1" max="100" value="1" oninput="updateBulkGeneratorPreview()">
                        <span style="font-size: 12px; color: #64748b; font-weight: 700;">to</span>
                        <input type="number" id="bulkBinEnd" class="wh-input" min="1" max="100" value="5" oninput="updateBulkGeneratorPreview()">
                    </div>
                </div>
            </div>

            <!-- Live Preview Box -->
            <div class="wh-bulk-preview-box">
                <div class="wh-bulk-preview-head">
                    <span><i class="ph ph-barcode"></i> Live Generation Preview</span>
                    <span class="wh-count-pill" id="bulkPreviewTotalBadge">50 Bins + 10 Racks</span>
                </div>
                <div class="wh-bulk-preview-summary" id="bulkPreviewSummaryText">
                    Creates <strong>Aisle A</strong> → <strong>Racks R01–R10</strong> → <strong>50 Storage Bins</strong> with unique handheld scanner barcodes:
                </div>
                <div class="wh-bulk-sample-barcodes" id="bulkPreviewBarcodesList">
                    <!-- Populated dynamically -->
                </div>
            </div>
        </div>

        <div class="wh-modal-footer">
            <button type="button" class="wh-btn wh-btn-outline" onclick="closeBulkGeneratorModal()">Cancel</button>
            <button type="button" class="wh-btn wh-btn-primary" onclick="executeBulkBinGeneration()">
                <i class="ph ph-lightning"></i> Generate Storage Hierarchy
            </button>
        </div>
    </div>
</div>
@endsection


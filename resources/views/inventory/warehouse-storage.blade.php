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

    <div id="whDirectoryView" class="wh-directory-view">
    <!-- 1. Page Header (Consistent Flat ERP Title Row) -->
    <div class="hr-parent-title" style="margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="wh-title-icon">
                <i class="ph ph-warehouse"></i>
            </div>
            <div>
                <h1 style="margin: 0; font-size: 1.32rem; font-weight: 800; color: #0f172a; letter-spacing: -0.02em;">Warehouse & Storage Area</h1>
                <p style="margin: 2px 0 0 0; font-size: 0.8rem; color: #64748b;">Physical facility directory, nested storage locations (Physical &amp; Virtual), and unique scanner barcodes</p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
            <button type="button" class="wh-btn wh-btn-primary" onclick="openWarehouseModal()">
                <i class="ph ph-plus-circle"></i>
                <span>New Warehouse</span>
            </button>
        </div>
    </div>

    <!-- 2. Top KPI Strip -->
    <div class="wh-top-strip">
        <div class="wh-kpi-grid">
            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Active Warehouses</span>
                    <span class="wh-kpi-icon purple"><i class="ph ph-buildings"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiActiveWarehouses">0</div>
                <div class="wh-kpi-sub" id="whKpiTotalWarehousesSub">Auto-seeded with Physical &amp; Virtual Stock</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Storage Locations</span>
                    <span class="wh-kpi-icon blue"><i class="ph ph-tree-structure"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiTotalAreas">0</div>
                <div class="wh-kpi-sub" id="whKpiBehaviorBreakdownSub">0 Physical &bull; 0 Virtual</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Virtual Locations</span>
                    <span class="wh-kpi-icon emerald"><i class="ph ph-cube-transparent"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiVirtualLocations">0</div>
                <div class="wh-kpi-sub" id="whKpiVirtualSub">Active virtual ledger counterparts</div>
            </div>

            <div class="wh-kpi-card">
                <div class="wh-kpi-head">
                    <span class="wh-kpi-label">Stocked Locations</span>
                    <span class="wh-kpi-icon amber"><i class="ph ph-package"></i></span>
                </div>
                <div class="wh-kpi-val" id="whKpiStockedAreas">0</div>
                <div class="wh-kpi-sub" id="whKpiStockedUnitsSub">0 units tracked across locations</div>
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
            </div>
        </div>

        <!-- RIGHT PANEL: Selected Warehouse Location Directory -->
        <div class="wh-right-panel">
            <div class="wh-panel-card wh-tree-panel">
                <!-- Selected Warehouse Banner & Quick Location Creation Buttons -->
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
                        <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="openStorageAreaModal({ presetKind: 'Virtual', presetType: 'Virtual Location', presetBehavior: 'Virtual' })">
                            <i class="ph ph-cube-transparent"></i> + Add Virtual Location
                        </button>
                        <button type="button" class="wh-btn wh-btn-primary wh-btn-sm" onclick="openStorageAreaModal({ presetKind: 'Physical', presetType: 'Zone', presetBehavior: 'Storage' })">
                            <i class="ph ph-plus-circle"></i> + Add Physical Location
                        </button>
                    </div>
                </div>

                <!-- Filter Tabs, Holds Stock Toggle & Search Toolbar -->
                <div class="wh-tree-toolbar">
                    <div class="wh-behavior-filters" id="whBehaviorFilterGroup">
                        <button type="button" class="wh-filter-chip active" data-behavior="ALL" onclick="setAreaBehaviorFilter('ALL')">
                            All <span id="countBehAll">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip storage" data-behavior="Storage" onclick="setAreaBehaviorFilter('Storage')" title="Physical stock rests here (Zones, Racks, Bins)">
                            <span class="dot storage"></span> Storage <span id="countBehStorage">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip staging" data-behavior="Staging" onclick="setAreaBehaviorFilter('Staging')" title="Stock passes through here (Packing Tables, QA Benches)">
                            <span class="dot staging"></span> Staging <span id="countBehStaging">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip dock" data-behavior="Dock" onclick="setAreaBehaviorFilter('Dock')" title="Where stock enters or leaves the building">
                            <span class="dot dock"></span> Dock <span id="countBehDock">0</span>
                        </button>
                        <button type="button" class="wh-filter-chip virtual" data-behavior="Virtual" onclick="setAreaBehaviorFilter('Virtual')" title="Virtual ledger locations (Virtual Stock, Transit, Loss/Adjustment)">
                            <span class="dot virtual"></span> Virtual <span id="countBehVirtual">0</span>
                        </button>
                    </div>

                    <div class="wh-tree-controls">
                        <label class="wh-stocked-toggle" title="Show only locations holding stock (> 0 units)">
                            <input type="checkbox" id="whHoldsStockToggle" onchange="toggleHoldsStockFilter(this.checked)">
                            <span>Holds stock</span>
                            <span class="wh-stocked-count" id="countBehStocked">0</span>
                        </label>
                        <div class="wh-tree-search">
                            <i class="ph ph-magnifying-glass"></i>
                            <input type="text" id="whTreeSearchInput" placeholder="Search name, code, or scan barcode…" oninput="renderSelectedWarehouseTree()" onkeydown="handleSmartLocationSearchKey(event)">
                        </div>
                        <div class="wh-expand-pair" role="group" aria-label="Hierarchy expand controls">
                            <button type="button" class="wh-tree-text-btn" onclick="expandOrCollapseAllTree(true)" title="Expand all hierarchy levels">Expand all</button>
                            <span class="wh-expand-sep"></span>
                            <button type="button" class="wh-tree-text-btn" onclick="expandOrCollapseAllTree(false)" title="Collapse all hierarchy levels">Collapse all</button>
                        </div>
                    </div>
                </div>

                <!-- Column-Aligned Location Table Container (Full Height + RFQ Column Settings) -->
                <div class="wh-table-shell">
                    <div class="wh-table-responsive" id="whStorageTreeViewport">
                        <table class="rfq-table wh-location-table" id="whLocationsTable">
                            <thead id="whLocationsThead"></thead>
                            <tbody id="whLocationsTbody"></tbody>
                        </table>
                    </div>
                    <div id="whColSettingsDropdown" class="rfq-table-settings-dropdown"></div>
                    <div id="whRowActionMenu" class="wh-row-action-menu"></div>
                </div>

                <!-- Quiet Utility Footer -->
                <div class="wh-tree-footer">
                    <div class="wh-tree-footer-rule">
                        <i class="ph ph-info"></i>
                        <span>Press <strong>Enter</strong> in search to locate a scanned barcode across facilities.</span>
                    </div>
                    <div id="whTreeSummaryCount" class="wh-tree-footer-meta">0 locations</div>
                </div>
            </div>
        </div>

    </div>

    </div><!-- /#whDirectoryView -->

<!-- Shared Half-Screen Drawer Backdrop (Starts strictly below top .main-header) -->
<div class="wh-drawer-backdrop" id="whDrawerBackdrop" onclick="closeActiveWhDrawers()" aria-hidden="true"></div>

<!-- ==========================================================================
     DRAWER 1: CREATE / EDIT WAREHOUSE (Half-Screen Below Header)
     ========================================================================== -->
<aside class="wh-page-workspace wh-drawer-panel" id="whWarehouseModal" aria-labelledby="whModalTitle" aria-hidden="true">
    <!-- 1. Sticky Drawer Header -->
    <div class="wh-drawer-header">
        <div class="wh-hero-left">
            <div class="wh-hero-icon"><i class="ph ph-warehouse"></i></div>
            <div style="min-width: 0;">
                <div class="wh-hero-title-row">
                    <h2 class="wh-hero-title" id="whModalTitle">Create Warehouse</h2>
                    <span class="wh-inline-code" id="whPageCodePreview">WH-NEW</span>
                    <span class="wh-status-pill active" id="whPageStatusPill">Active</span>
                </div>
                <p class="wh-hero-sub" id="whModalSubtitle">Configure facility identity, default Stock counterparts, and storage location hierarchy</p>
            </div>
        </div>
        <div class="wh-drawer-head-actions">
            <button type="button" class="wh-btn wh-btn-danger-outline wh-btn-sm" id="btnDeleteWhFromPage" style="display: none;" onclick="deleteWarehouseFromPage()" title="Delete Warehouse">
                <i class="ph ph-trash"></i>
                <span>Delete</span>
            </button>
            <button type="button" class="wh-drawer-close" onclick="closeWarehouseModal()" title="Close Drawer (Esc)" aria-label="Close drawer">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>

    <!-- 2. RFQ-Style Tab Strip -->
    <div class="wh-drawer-tabs-strip">
        <nav class="rfq-nav-tabs-bar wh-page-tabs-bar" aria-label="Warehouse Configuration Tabs">
            <button type="button" class="rfq-tab-btn wh-page-tab-btn active" data-wh-tab="general" onclick="switchWhPageTab('general')">
                <i class="ph ph-buildings"></i>
                <span>General &amp; Facility Info</span>
            </button>
            <button type="button" class="rfq-tab-btn wh-page-tab-btn" data-wh-tab="defaults" onclick="switchWhPageTab('defaults')">
                <i class="ph ph-sparkle"></i>
                <span>Default Locations &amp; Rules</span>
            </button>
            <button type="button" class="rfq-tab-btn wh-page-tab-btn" data-wh-tab="hierarchy" onclick="switchWhPageTab('hierarchy')">
                <i class="ph ph-tree-structure"></i>
                <span>Storage Hierarchy</span>
                <span class="rfq-tab-count wh-tab-count" id="whPageHierarchyTabCount">0</span>
            </button>
        </nav>
    </div>

    <!-- 3. Scrollable Drawer Body -->
    <div class="wh-drawer-body wh-page-body">
        <input type="hidden" id="whFormId" value="">

        <!-- TAB 1: General & Facility Info -->
        <div class="wh-page-tab-pane active" id="whTabPane-general">
            <div class="wh-bento-grid">
                <div class="wh-bento-card">
                    <div class="wh-bento-head">
                        <div class="wh-bento-title"><i class="ph ph-identification-card"></i><span>Facility Identity &amp; Contact</span></div>
                        <span class="wh-bento-badge">Required Master Fields</span>
                    </div>
                    <div class="wh-bento-body">
                        <div class="wh-form-grid">
                            <div class="wh-field">
                                <label class="wh-label" for="whFormName">Warehouse Building Name *</label>
                                <input type="text" id="whFormName" class="wh-input" placeholder="e.g. South Commissary &amp; Distribution Hub" oninput="updateWhPageLivePreview()">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="whFormCode">Short Code * <span class="wh-hint">(e.g. WH-MAIN)</span></label>
                                <input type="text" id="whFormCode" class="wh-input mono" placeholder="e.g. WH-SOUTH" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, ''); updateWhPageLivePreview();">
                            </div>
                            <div class="wh-field full">
                                <label class="wh-label" for="whFormAddress">Physical Building Address *</label>
                                <input type="text" id="whFormAddress" class="wh-input" placeholder="e.g. Lot 12 Industrial Ave, Carmona, Cavite">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="whFormManager">Facility Manager / Supervisor</label>
                                <input type="text" id="whFormManager" class="wh-input" placeholder="e.g. Juan Dela Cruz">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="whFormPhone">Contact Phone / Local Ext.</label>
                                <input type="text" id="whFormPhone" class="wh-input" placeholder="e.g. +63 917 555 0142">
                            </div>
                            <div class="wh-field full">
                                <label class="wh-label" for="whFormNotes">Receiving Hours &amp; Operational Notes</label>
                                <textarea id="whFormNotes" class="wh-textarea" rows="3" placeholder="Dock hours, cold chain compliance notes, or dispatch instructions..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wh-bento-side">
                    <div class="wh-bento-card">
                        <div class="wh-bento-head">
                            <div class="wh-bento-title"><i class="ph ph-toggle-right"></i><span>Operational Status</span></div>
                        </div>
                        <div class="wh-bento-body">
                            <label class="wh-switch-card">
                                <input type="checkbox" id="whFormActive" checked onchange="updateWhPageLivePreview()">
                                <div>
                                    <div class="wh-switch-title">Warehouse is Active</div>
                                    <div class="wh-switch-desc">Available for receiving, picking, transfers, and inventory ledger movements.</div>
                                </div>
                            </label>

                            <div id="whAutoSeedNoticeBox" class="wh-autoseed-banner" style="margin-top: 12px;">
                                <i class="ph ph-sparkle"></i>
                                <div>
                                    <div style="font-weight: 800; font-size: 0.78rem; color: #5b21b6;">Automatic Default Stock Locations</div>
                                    <div style="font-size: 0.72rem; color: #6d28d9; margin-top: 2px;">
                                        Upon creation, the system automatically seeds <strong>Stock</strong> (Physical Storage) and <strong>Virtual Stock</strong> (Virtual Counterpart) locations so operations can start immediately.
                                    </div>
                                </div>
                            </div>

                            <div id="whPageEditMetricsBox" class="wh-metrics-box" style="display: none; margin-top: 12px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Default Locations & Rules -->
        <div class="wh-page-tab-pane" id="whTabPane-defaults">
            <div class="wh-bento-grid">
                <div class="wh-bento-card">
                    <div class="wh-bento-head">
                        <div class="wh-bento-title"><i class="ph ph-package"></i><span>Default System Locations</span></div>
                        <span class="wh-bento-badge">Physical &amp; Virtual Stock</span>
                    </div>
                    <div class="wh-bento-body" id="whPageDefaultsList">
                        <!-- Populated dynamically by renderWarehousePageSubTabs() -->
                    </div>
                </div>

                <div class="wh-bento-side">
                    <div class="wh-bento-card">
                        <div class="wh-bento-head">
                            <div class="wh-bento-title"><i class="ph ph-shield-check"></i><span>Facility Ledger &amp; Barcode Rules</span></div>
                        </div>
                        <div class="wh-bento-body">
                            <ul class="wh-rule-list">
                                <li><i class="ph ph-barcode"></i><span><strong>Unique Prefixing:</strong> Barcodes inherit the warehouse code (e.g. <code>WH-MAIN-STOCK</code>) to prevent cross-site scan collisions.</span></li>
                                <li><i class="ph ph-arrows-left-right"></i><span><strong>Physical + Virtual Pairing:</strong> Every active facility maintains a physical <em>Stock</em> root alongside a <em>Virtual Stock</em> ledger counterpart.</span></li>
                                <li><i class="ph ph-scales"></i><span><strong>Non-Negative Balance:</strong> Stock quantities on physical and virtual storage nodes can never drop below zero.</span></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Storage Hierarchy -->
        <div class="wh-page-tab-pane" id="whTabPane-hierarchy">
            <div class="wh-bento-card">
                <div class="wh-bento-head">
                    <div class="wh-bento-title"><i class="ph ph-tree-structure"></i><span>Warehouse Storage Hierarchy</span></div>
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;" id="whPageHierarchyActions">
                        <button type="button" class="wh-btn wh-btn-outline wh-btn-xs" onclick="openStorageAreaFromWhPage('Virtual')">
                            <i class="ph ph-cube-transparent"></i> + Add Virtual Location
                        </button>
                        <button type="button" class="wh-btn wh-btn-primary wh-btn-xs" onclick="openStorageAreaFromWhPage('Physical')">
                            <i class="ph ph-plus-circle"></i> + Add Physical Location
                        </button>
                    </div>
                </div>
                <div class="wh-bento-body" style="padding: 0;" id="whPageHierarchyList">
                    <!-- Populated dynamically by renderWarehousePageSubTabs() -->
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Sticky Drawer Footer -->
    <div class="wh-drawer-footer">
        <span class="wh-page-breadcrumb">
            <span>Warehouses</span>
            <i class="ph ph-caret-right"></i>
            <strong id="whPageBreadcrumbLabel">New Warehouse</strong>
        </span>
        <div class="wh-drawer-footer-actions">
            <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="closeWarehouseModal()">Cancel</button>
            <button type="button" class="wh-btn wh-btn-primary wh-btn-sm" onclick="saveWarehouseForm()">
                <i class="ph ph-floppy-disk"></i>
                <span>Save Warehouse</span>
            </button>
        </div>
    </div>
</aside>

<!-- ==========================================================================
     DRAWER 2: CREATE / EDIT STORAGE LOCATION (Half-Screen Below Header)
     ========================================================================== -->
<aside class="wh-page-workspace wh-drawer-panel" id="whStorageAreaModal" aria-labelledby="areaModalTitle" aria-hidden="true">
    <!-- 1. Sticky Drawer Header -->
    <div class="wh-drawer-header">
        <div class="wh-hero-left">
            <div class="wh-hero-icon" id="areaPageHeroIcon"><i class="ph ph-tree-structure"></i></div>
            <div style="min-width: 0;">
                <div class="wh-hero-title-row">
                    <h2 class="wh-hero-title" id="areaModalTitle">Add Storage Location</h2>
                    <span class="wh-inline-code" id="areaPageCodeChip">NEW</span>
                    <span class="wh-kind-tag physical" id="areaPageKindPill">Physical</span>
                    <span class="wh-beh-tag Storage" id="areaPageBehaviorPill">Storage</span>
                    <span class="wh-status-pill active" id="areaPageStatusPill">Active</span>
                </div>
                <p class="wh-hero-sub" id="areaPageSubtitle">Configure location kind, nesting placement, operational behavior, capacity, and scanner barcode</p>
            </div>
        </div>
        <div class="wh-drawer-head-actions">
            <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" id="btnAddChildFromAreaPage" style="display: none;" onclick="addChildFromAreaPage()">
                <i class="ph ph-plus"></i>
                <span>+ Child</span>
            </button>
            <button type="button" class="wh-btn wh-btn-danger-outline wh-btn-sm" id="btnDeleteAreaFromPage" style="display: none;" onclick="deleteAreaFromPage()" title="Delete Location">
                <i class="ph ph-trash"></i>
            </button>
            <button type="button" class="wh-drawer-close" onclick="closeStorageAreaModal()" title="Close Drawer (Esc)" aria-label="Close drawer">
                <i class="ph ph-x"></i>
            </button>
        </div>
    </div>

    <!-- 2. RFQ-Style Tab Strip -->
    <div class="wh-drawer-tabs-strip">
        <nav class="rfq-nav-tabs-bar wh-page-tabs-bar" aria-label="Storage Location Tabs">
            <button type="button" class="rfq-tab-btn wh-page-tab-btn active" data-area-tab="general" onclick="switchAreaPageTab('general')">
                <i class="ph ph-sliders"></i>
                <span>Identity &amp; Hierarchy</span>
            </button>
            <button type="button" class="rfq-tab-btn wh-page-tab-btn" data-area-tab="stock" onclick="switchAreaPageTab('stock')">
                <i class="ph ph-package"></i>
                <span>Stock &amp; Capacity Specs</span>
            </button>
            <button type="button" class="rfq-tab-btn wh-page-tab-btn" data-area-tab="children" onclick="switchAreaPageTab('children')">
                <i class="ph ph-tree-structure"></i>
                <span>Sub-Locations</span>
                <span class="rfq-tab-count wh-tab-count" id="areaPageChildrenTabCount">0</span>
            </button>
        </nav>
    </div>

    <!-- 3. Scrollable Drawer Body -->
    <div class="wh-drawer-body wh-page-body">
        <input type="hidden" id="areaFormId" value="">

        <!-- TAB 1: Identity & Hierarchy -->
        <div class="wh-page-tab-pane active" id="areaTabPane-general">
            <div class="wh-bento-grid">
                <div class="wh-bento-card">
                    <div class="wh-bento-head">
                        <div class="wh-bento-title"><i class="ph ph-map-pin-area"></i><span>Location Kind, Identity &amp; Nesting</span></div>
                        <span class="wh-bento-badge">Required Structure</span>
                    </div>
                    <div class="wh-bento-body">
                        <div class="wh-field full" style="margin-bottom: 14px;">
                            <label class="wh-label">Location Kind *</label>
                            <div class="wh-kind-selector-grid">
                                <button type="button" id="kindCardPhysical" class="wh-kind-card active" onclick="selectAreaKindCard('Physical')">
                                    <div class="wh-kind-card-head">
                                        <span class="wh-kind-card-icon"><i class="ph ph-buildings"></i></span>
                                        <span class="wh-kind-card-title">Physical Location</span>
                                    </div>
                                    <div class="wh-kind-card-desc">Real warehouse space such as a Zone, Aisle, Rack, Bin, Packing Table, or Dock Door.</div>
                                </button>
                                <button type="button" id="kindCardVirtual" class="wh-kind-card" onclick="selectAreaKindCard('Virtual')">
                                    <div class="wh-kind-card-head">
                                        <span class="wh-kind-card-icon virtual"><i class="ph ph-cloud"></i></span>
                                        <span class="wh-kind-card-title">Virtual Location</span>
                                    </div>
                                    <div class="wh-kind-card-desc">Logical ledger counterpart such as Virtual Stock, Transit, Quarantine, or Loss/Adjustment.</div>
                                </button>
                            </div>
                            <select id="areaFormKind" class="wh-select" onchange="handleAreaKindChange(this.value)" style="display: none;">
                                <option value="Physical">Physical Location</option>
                                <option value="Virtual">Virtual Location</option>
                            </select>
                        </div>

                        <div class="wh-form-grid">
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormName">Location Name *</label>
                                <input type="text" id="areaFormName" class="wh-input" placeholder="e.g. Bin B-12 or Virtual Transit" oninput="autoSuggestAreaBarcode(); updateAreaPageLivePreview();">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormCode">Location Code *</label>
                                <input type="text" id="areaFormCode" class="wh-input mono" placeholder="e.g. BIN-B12 or VRT-TRN" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, ''); autoSuggestAreaBarcode(); updateAreaPageLivePreview();">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormWarehouse">Warehouse Facility</label>
                                <input type="text" id="areaFormWarehouse" class="wh-input mono" readonly style="background: #f8fafc !important; color: #475569 !important;">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormParent">Nested Inside (Parent Location)</label>
                                <select id="areaFormParent" class="wh-select" onchange="autoSuggestAreaBarcode(); updateAreaPageLivePreview();">
                                    <!-- Populated dynamically with cycle-prevention -->
                                </select>
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormType">Structural Level / Type *</label>
                                <select id="areaFormType" class="wh-select" onchange="handleAreaTypeChange(this.value); updateAreaPageLivePreview();">
                                    <option value="Zone">Zone (e.g. Dry Zone A, Cold Room)</option>
                                    <option value="Aisle">Aisle (e.g. Aisle A, Aisle B)</option>
                                    <option value="Rack">Rack / Shelf (e.g. Rack B, Shelf 2)</option>
                                    <option value="Bin">Bin (e.g. Bin B-12)</option>
                                    <option value="Packing Table">Packing / QA Table</option>
                                    <option value="Dock Door">Dock Door (Receiving / Shipping)</option>
                                    <option value="Virtual Location">Virtual Location (e.g. Virtual Stock)</option>
                                    <option value="Virtual Transit">Virtual Transit / Quarantine</option>
                                    <option value="Virtual Loss / Adj">Virtual Loss / Inventory Adjustment</option>
                                </select>
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormBehavior">Area Behavior *</label>
                                <select id="areaFormBehavior" class="wh-select" onchange="updateAreaPageLivePreview()">
                                    <option value="Storage">Storage — Stock rests here (Bins, Racks, Zones)</option>
                                    <option value="Staging">Staging — Stock passes through (Packing, QC Table)</option>
                                    <option value="Dock">Dock — Stock enters or leaves building</option>
                                    <option value="Virtual">Virtual — Logical / Ledger counterpart</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wh-bento-side">
                    <div class="wh-bento-card">
                        <div class="wh-bento-head">
                            <div class="wh-bento-title"><i class="ph ph-barcode"></i><span>Handheld Scanner Barcode *</span></div>
                        </div>
                        <div class="wh-bento-body">
                            <div class="wh-barcode-preview-card">
                                <div class="wh-barcode-bars" aria-hidden="true"></div>
                                <div class="wh-barcode-preview-val" id="areaBarcodePreviewText">WH-MAIN-LOC</div>
                            </div>

                            <div class="wh-field" style="margin-top: 10px;">
                                <label class="wh-label" for="areaFormBarcode">Unique Scanner Barcode *</label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <input type="text" id="areaFormBarcode" class="wh-input mono" style="flex: 1;" placeholder="e.g. WH-MAIN-ZA-RB-B12" oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, ''); updateAreaPageLivePreview();">
                                    <button type="button" class="wh-btn wh-btn-outline wh-btn-xs" onclick="autoSuggestAreaBarcode(true); updateAreaPageLivePreview();" title="Auto-generate from parent and code">
                                        <i class="ph ph-sparkle"></i> Auto
                                    </button>
                                    <button type="button" class="wh-btn wh-btn-outline wh-btn-xs" onclick="copyAreaBarcode(document.getElementById('areaFormBarcode').value)" title="Copy barcode">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="wh-bento-card">
                        <div class="wh-bento-head">
                            <div class="wh-bento-title"><i class="ph ph-toggle-right"></i><span>Operational Controls</span></div>
                        </div>
                        <div class="wh-bento-body" style="display: flex; flex-direction: column; gap: 10px;">
                            <label class="wh-switch-card">
                                <input type="checkbox" id="areaFormActive" checked onchange="updateAreaPageLivePreview()">
                                <div>
                                    <div class="wh-switch-title">Location is Active</div>
                                    <div class="wh-switch-desc">Enabled for stock counts, putaway, picking, and scanner lookups.</div>
                                </div>
                            </label>
                            <label class="wh-switch-card">
                                <input type="checkbox" id="areaFormDefault">
                                <div>
                                    <div class="wh-switch-title">Mark as Default Location</div>
                                    <div class="wh-switch-desc">Flags this node as a primary default destination in its warehouse.</div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 2: Stock & Capacity Specs -->
        <div class="wh-page-tab-pane" id="areaTabPane-stock">
            <div class="wh-bento-grid">
                <div class="wh-bento-card">
                    <div class="wh-bento-head">
                        <div class="wh-bento-title"><i class="ph ph-sliders-horizontal"></i><span>Stock Level, Capacity &amp; Handling Specs</span></div>
                    </div>
                    <div class="wh-bento-body">
                        <div class="wh-form-grid">
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormStockQty">Direct Unit Quantity <span class="wh-hint">(&ge; 0 only)</span></label>
                                <input type="number" id="areaFormStockQty" class="wh-input" min="0" step="1" value="0" oninput="updateAreaPageLivePreview()">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormMaxCapacity">Max Unit Capacity <span class="wh-hint">(optional)</span></label>
                                <input type="number" id="areaFormMaxCapacity" class="wh-input" min="0" step="1" placeholder="Unlimited">
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormTempZone">Environment / Storage Class</label>
                                <select id="areaFormTempZone" class="wh-select">
                                    <option value="Ambient / Dry">Ambient / Dry Storage</option>
                                    <option value="Chilled (2°C – 8°C)">Chilled (2°C – 8°C)</option>
                                    <option value="Frozen (-18°C)">Frozen (-18°C)</option>
                                    <option value="Controlled / Hazmat">Controlled / Security Cage</option>
                                    <option value="Virtual / N/A">Virtual / Not Applicable</option>
                                </select>
                            </div>
                            <div class="wh-field">
                                <label class="wh-label" for="areaFormPickStrategy">Removal / Picking Strategy</label>
                                <select id="areaFormPickStrategy" class="wh-select">
                                    <option value="FEFO">FEFO (First Expired, First Out)</option>
                                    <option value="FIFO">FIFO (First In, First Out)</option>
                                    <option value="LIFO">LIFO (Last In, First Out)</option>
                                    <option value="Manual">Manual / Directed Putaway</option>
                                </select>
                            </div>
                            <div class="wh-field full">
                                <label class="wh-label" for="areaFormNotes">Handling &amp; Operational Notes</label>
                                <textarea id="areaFormNotes" class="wh-textarea" rows="3" placeholder="Forklift clearance, allergen separation, or virtual ledger usage notes..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="wh-bento-side">
                    <div class="wh-bento-card">
                        <div class="wh-bento-head">
                            <div class="wh-bento-title"><i class="ph ph-chart-bar"></i><span>Live Stock Roll-Up Summary</span></div>
                        </div>
                        <div class="wh-bento-body">
                            <div class="wh-stock-summary-rows">
                                <div class="wh-summary-row">
                                    <span>Direct On-Hand Stock</span>
                                    <strong id="areaSummaryDirectStock">0</strong>
                                </div>
                                <div class="wh-summary-row">
                                    <span>Nested Sub-Locations Stock</span>
                                    <strong id="areaSummaryNestedStock">0</strong>
                                </div>
                                <div class="wh-summary-row total">
                                    <span>Total Subtree Stock</span>
                                    <strong id="areaSummaryTotalStock">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: Sub-Locations -->
        <div class="wh-page-tab-pane" id="areaTabPane-children">
            <div class="wh-bento-card">
                <div class="wh-bento-head">
                    <div class="wh-bento-title"><i class="ph ph-tree-structure"></i><span>Nested Sub-Locations</span></div>
                    <button type="button" class="wh-btn wh-btn-primary wh-btn-xs" id="btnTabAddChildArea" onclick="addChildFromAreaPage()">
                        <i class="ph ph-plus"></i> Add Child Location
                    </button>
                </div>
                <div class="wh-bento-body" style="padding: 0;" id="areaPageChildrenList">
                    <!-- Populated dynamically by renderAreaPageChildrenTab() -->
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Sticky Drawer Footer -->
    <div class="wh-drawer-footer">
        <div class="wh-page-breadcrumb" id="areaPageBreadcrumb">
            <span>WH-MAIN</span>
            <i class="ph ph-caret-right"></i>
            <strong>New Location</strong>
        </div>
        <div class="wh-drawer-footer-actions">
            <button type="button" class="wh-btn wh-btn-outline wh-btn-sm" onclick="closeStorageAreaModal()">Cancel</button>
            <button type="button" class="wh-btn wh-btn-primary wh-btn-sm" onclick="saveStorageAreaForm()">
                <i class="ph ph-floppy-disk"></i>
                <span>Save Location</span>
            </button>
        </div>
    </div>
</aside>

</div><!-- /#whStorageApp -->

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


@extends('layouts.app')

@section('title', 'Reordering Rules & Replenishment Control Tower - RMS Inventory')

@push('styles')
    @vite('resources/css/modules/reorder-rules.css')
@endpush

@push('blade-data')
<script>
    window.ReorderRulesConfig = Object.freeze({
        initialRules: @json($initialRules ?? []),
        initialSimulation: @json($initialSimulation ?? ['summary' => [], 'items' => []]),
        branches: @json($branches ?? []),
        items: @json($items ?? []),
        vendors: @json($vendors ?? []),
        warehouses: @json($warehouses ?? []),
        csrfToken: @json(csrf_token()),
        routes: {
            rulesApi: @json(route('inventory.api.reorder-rules')),
            saveRuleApi: @json(route('inventory.api.reorder-rules.save')),
            toggleRuleApi: @json(url('/inventory/api/reorder-rules')),
            deleteRuleApi: @json(url('/inventory/api/reorder-rules')),
            simulateApi: @json(route('inventory.api.reorder-rules.simulate')),
            generateDraftsApi: @json(route('inventory.api.reorder-rules.generate-drafts')),
            purchaseOrdersPage: @json(route('purchase.purchase-orders')),
            internalTransfersPage: @json(route('inventory.internal-transfer')),
            itemMasterPage: @json(route('inventory.product-categories')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/reorder-rules.js')
@endpush

@section('content')
<div class="rr-workspace" id="reorderRulesApp">

    <!-- Toast Notifications Container -->
    <div class="rr-toast-container" id="rrToastContainer"></div>

    <!-- 1. Standardized RFQ / Item Master Flat Header -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrows-clockwise"></i>
                    <span>Reordering Rules &amp; Replenishment Control Tower</span>
                </h1>
                <p class="inv-parent-subtitle hr-parent-subtitle">Automated Min-Max par levels, commissary push routes, category bulk rules, and 1-click PO / Transfer generator</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-folder-simple"></i>
                    <span>Item Master</span>
                </a>
                <button type="button" class="hr-btn hr-btn-secondary" onclick="runSimulationCheck()">
                    <i class="ph ph-lightning"></i>
                    <span>Run Simulator</span>
                </button>
                <button type="button" class="hr-btn hr-btn-secondary" onclick="openBatchDraftModal()">
                    <i class="ph ph-sparkle"></i>
                    <span>Generate Drafts</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="openCreateRuleModal()">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Reorder Rule</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Full-Width Canonical RFQ Tab Wrapper Bar (Directly Below Subtitle) -->
    <nav class="rfq-nav-tabs-bar rr-nav-tabs" id="rrTabsBar" aria-label="Reordering Rules Sections">
        <button type="button" class="rfq-tab-btn rr-tab-btn active" data-tab="rules" onclick="switchRrTab('rules')">
            <i class="ph ph-list-dashes"></i>
            <span>All Reordering Rules</span>
            <span class="rfq-tab-count rr-tab-badge" id="tabBadgeRules">0</span>
        </button>
        <button type="button" class="rfq-tab-btn rr-tab-btn" data-tab="simulator" onclick="switchRrTab('simulator')">
            <i class="ph ph-radar"></i>
            <span>Replenishment Control Tower</span>
            <span class="rfq-tab-count rr-tab-badge is-alert" id="tabBadgeSimulator">0</span>
        </button>
        <button type="button" class="rfq-tab-btn rr-tab-btn" data-tab="history" onclick="switchRrTab('history')">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Execution History &amp; Drafts</span>
            <span class="rfq-tab-count rr-tab-badge" id="tabBadgeHistory">0</span>
        </button>
    </nav>

    <!-- =================================================================== -->
    <!-- TAB PANE 1: ALL REORDERING RULES                                    -->
    <!-- Sequence: KPI Grid -> Detached .inv-filter-bar -> Detached .inv-grid-card -->
    <!-- =================================================================== -->
    <div class="rr-tab-pane active" id="pane-rules">

        <!-- KPI Metrics Ribbon -->
        <div class="rfq-kpi-grid rr-kpi-grid">
            <div class="rfq-kpi-card" onclick="applyStatusQuickFilter('active')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Active Reorder Rules</span>
                    <div class="rfq-kpi-icon"><i class="ph ph-git-branch"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiActiveRules">0</div>
                <div class="rfq-kpi-sub" id="kpiActiveSub">Across all branch locations</div>
            </div>
            <div class="rfq-kpi-card" onclick="switchRrTab('simulator')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Under-Par Deficits</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-warning-circle"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiTriggeredCount">0</div>
                <div class="rfq-kpi-sub" id="kpiTriggeredSub">Action required: stock below min par</div>
            </div>
            <div class="rfq-kpi-card">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Est. PO Reorder Value</span>
                    <div class="rfq-kpi-icon" style="color: #2563eb; background: rgba(37, 99, 235, 0.12);"><i class="ph ph-shopping-cart-simple"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiPoValue">₱0.00</div>
                <div class="rfq-kpi-sub" id="kpiPoSub">Suggested vendor purchase total</div>
            </div>
            <div class="rfq-kpi-card">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Commissary Push Units</span>
                    <div class="rfq-kpi-icon awarded"><i class="ph ph-arrows-left-right"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiTransferUnits">0 Units</div>
                <div class="rfq-kpi-sub" id="kpiTransferSub">Pending internal transfers from hub</div>
            </div>
        </div>

        <!-- Detached Industrial Filter & Action Toolbar (Identical to Item Master / RFQ) -->
        <section class="inv-filter-bar rr-toolbar" aria-label="Reordering Rules Filter Bar">
            <div class="inv-filter-left">
                <div class="inv-search-wrapper">
                    <i class="ph ph-magnifying-glass inv-search-icon"></i>
                    <input type="text" id="ruleSearchInput" class="inv-search-input" placeholder="Search rule code, SKU, item, category..." oninput="handleRuleSearch(this.value)" autocomplete="off">
                </div>

                <select id="branchGlobalFilter" class="rr-select" onchange="filterRulesByBranch(this.value)" title="Filter by Branch">
                    <option value="all">All Branches</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->branch_id }}">{{ $b->branch_name }}</option>
                    @endforeach
                </select>

                <select id="categoryFilter" class="rr-select" onchange="applyFilters()" title="Filter by Category">
                    <option value="all">All Categories</option>
                </select>

                <select id="strategyFilter" class="rr-select" onchange="applyFilters()" title="Filter by Strategy">
                    <option value="all">All Strategies</option>
                    <option value="min_max">Min-Max Par</option>
                    <option value="commissary_push">Commissary Push</option>
                    <option value="jit_back_to_back">JIT Demand</option>
                </select>

                <select id="routeFilter" class="rr-select" onchange="applyFilters()" title="Filter by Route">
                    <option value="all">All Routes</option>
                    <option value="purchase_order">Purchase Order (PO)</option>
                    <option value="internal_transfer">Internal Transfer (TRF)</option>
                </select>

                <select id="statusFilter" class="rr-select" onchange="applyFilters()" title="Filter by Status">
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>
            </div>

            <div class="inv-filter-right">
                <div class="inv-filter-stats">
                    <span id="filteredRuleCount">0 Rules</span>
                </div>
            </div>
        </section>

        <!-- Detached Table Card Container -->
        <div class="inv-grid-card">
            <div class="inv-table-responsive" id="masterTableResponsiveContainer">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th style="width: 48px; text-align: center;">Active</th>
                            <th>Rule Code</th>
                            <th>Branch Location</th>
                            <th>Product SKU &amp; Item Name</th>
                            <th>Category</th>
                            <th>Strategy</th>
                            <th class="num">Min Par</th>
                            <th class="num">Max Par</th>
                            <th class="num">MOQ</th>
                            <th>Route &amp; Fulfillment Partner</th>
                            <th>Lead Time</th>
                            <th>Auto-Draft</th>
                            <th style="text-align: right; width: 84px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rulesTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- TAB PANE 2: REPLENISHMENT CONTROL TOWER (LIVE SIMULATOR)            -->
    <!-- =================================================================== -->
    <div class="rr-tab-pane" id="pane-simulator">
        <div class="inv-filter-bar rr-sim-banner">
            <div class="rr-sim-banner-text">
                <i class="ph ph-info" style="font-size: 1.1rem;"></i>
                <span>
                    <strong>Net Available Stock Formula:</strong>
                    <code>Net Available = On Hand + In-Transit (POs &amp; Transfers) - Committed</code>.
                    When <code>Net Available &le; Min Par</code>, order quantity rounds up to MOQ:
                    <code>Ceil((Max - Net) / MOQ) &times; MOQ</code>.
                </span>
            </div>
            <div class="inv-filter-right" style="gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="runSimulationCheck()">
                    <i class="ph ph-arrows-clockwise"></i>
                    <span>Re-evaluate</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openBatchDraftModal()">
                    <i class="ph ph-sparkle"></i>
                    <span>Draft All Triggered</span>
                </button>
            </div>
        </div>

        <div class="inv-grid-card">
            <div class="inv-table-responsive">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Health State</th>
                            <th>Branch</th>
                            <th>Product &amp; SKU</th>
                            <th class="num">On-Hand</th>
                            <th class="num">In-Transit</th>
                            <th class="num" style="background:#faf5ff;">Net Available</th>
                            <th class="num">Min Reorder</th>
                            <th class="num">Max Par</th>
                            <th class="num" style="font-weight: 800; color: #9333ea;">Order Qty</th>
                            <th class="num">Est. Cost</th>
                            <th>Fulfillment Route</th>
                            <th style="text-align: right; width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="simulatorTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- TAB PANE 3: EXECUTION HISTORY & DRAFTS                              -->
    <!-- =================================================================== -->
    <div class="rr-tab-pane" id="pane-history">
        <div class="inv-filter-bar">
            <div class="inv-filter-left">
                <span style="font-size: 0.78rem; color: #475569; font-weight: 500;">
                    <i class="ph ph-clock-counter-clockwise" style="color: #7c3aed; vertical-align: middle;"></i>
                    Log of automated replenishment runs, generated Purchase Order drafts, and Internal Transfer requisitions.
                </span>
            </div>
            <div class="inv-filter-right" style="gap: 8px;">
                <a href="{{ route('purchase.purchase-orders') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                    <i class="ph ph-arrow-square-out"></i>
                    <span>Purchase Orders</span>
                </a>
                <a href="{{ route('inventory.internal-transfer') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                    <i class="ph ph-arrow-square-out"></i>
                    <span>Internal Transfers</span>
                </a>
            </div>
        </div>

        <div class="inv-grid-card">
            <div class="inv-table-responsive">
                <table class="rr-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Document Reference</th>
                            <th>Document Type</th>
                            <th>Source &rarr; Destination</th>
                            <th class="num">Item Count</th>
                            <th class="num">Total Value / Units</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="historyTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =================================================================== -->
    <!-- 5. CANONICAL HALF-SCREEN DRAWER: CREATE / EDIT REORDERING RULE      -->
    <!-- Supports Single Product/SKU OR Category / Multi-SKU Bulk Assignment  -->
    <!-- =================================================================== -->
    <div class="rms-drawer-backdrop" id="ruleDrawerBackdrop" onclick="closeRuleModal()" aria-hidden="true"></div>

    <aside class="rms-drawer-panel" id="ruleModalBackdrop" aria-labelledby="ruleModalTitle" aria-hidden="true">

        <!-- 1. Sticky Drawer Header -->
        <div class="rms-drawer-header">
            <div class="rms-drawer-head-left">
                <div class="rms-drawer-icon"><i class="ph ph-sliders-horizontal"></i></div>
                <div style="min-width: 0;">
                    <div class="rms-drawer-title-row">
                        <h2 class="rms-drawer-title" id="ruleModalTitle">Configure Reordering Rule</h2>
                        <span class="rr-mono-chip" id="drawerRuleCodeChip">NEW-RULE</span>
                        <span class="rr-badge strat-minmax" id="drawerStrategyChip">Min-Max</span>
                        <span class="rr-status-pill active" id="drawerStatusPill">Active</span>
                    </div>
                    <p class="rms-drawer-sub" id="ruleModalSubtitle">Apply par thresholds across specific SKUs or entire Product Categories in one step.</p>
                </div>
            </div>
            <div class="rms-drawer-head-actions">
                <button type="button" class="rr-btn rr-btn-danger-outline" id="btnDeleteRuleInDrawer" style="display: none; padding: 5px 10px; font-size: 0.74rem;" onclick="deleteEditingRuleFromDrawer()" title="Delete Rule">
                    <i class="ph ph-trash"></i>
                    <span>Delete</span>
                </button>
                <button type="button" class="rms-drawer-close" onclick="closeRuleModal()" title="Close Drawer (Esc)" aria-label="Close drawer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <!-- 2. RFQ-Style Navigation Tabs Strip -->
        <div class="rms-drawer-tabs-strip">
            <nav class="rfq-nav-tabs-bar rms-drawer-nav" aria-label="Reordering Rule Sections">
                <button type="button" class="rfq-tab-btn active" data-rr-drawer-tab="general" onclick="switchRuleDrawerTab('general')">
                    <i class="ph ph-package"></i>
                    <span>Products, Categories &amp; Strategy</span>
                    <span class="rfq-tab-count" id="drawerScopeTabCount">0 SKUs</span>
                </button>
                <button type="button" class="rfq-tab-btn" data-rr-drawer-tab="thresholds" onclick="switchRuleDrawerTab('thresholds')">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Par Thresholds &amp; Order Math</span>
                </button>
                <button type="button" class="rfq-tab-btn" data-rr-drawer-tab="routing" onclick="switchRuleDrawerTab('routing')">
                    <i class="ph ph-arrows-split"></i>
                    <span>Fulfillment Route &amp; Auto-Draft</span>
                </button>
            </nav>
        </div>

        <!-- 3. Scrollable Drawer Body -->
        <form id="ruleForm" class="rms-drawer-body" novalidate onsubmit="handleRuleFormSubmit(event)">
            <input type="hidden" id="formRuleId" name="id">

            <!-- TAB 1: Products, Categories, Branch & Strategy -->
            <div class="rms-drawer-tab-pane active" id="rrDrawerPane-general">
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <span class="rms-drawer-card-title"><i class="ph ph-compass"></i><span>Strategy &amp; Branch Facility</span></span>
                        <span class="rms-drawer-card-badge">Master Rule Setup</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="rr-form-group" style="margin-bottom: 12px;">
                            <label class="rr-form-label">Replenishment Strategy <strong style="color:#ef4444;">*</strong></label>
                            <div class="rr-strategy-cards" id="rrStrategyCards">
                                <button type="button" class="rr-select-card active" data-strat="min_max" onclick="selectRuleStrategyCard('min_max')">
                                    <div class="rr-select-card-head">
                                        <span class="rr-select-icon purple"><i class="ph ph-chart-bar"></i></span>
                                        <span class="rr-select-title">Min-Max Par</span>
                                    </div>
                                    <span class="rr-select-desc">Order up to Max when Net &le; Min</span>
                                </button>
                                <button type="button" class="rr-select-card" data-strat="commissary_push" onclick="selectRuleStrategyCard('commissary_push')">
                                    <div class="rr-select-card-head">
                                        <span class="rr-select-icon emerald"><i class="ph ph-truck"></i></span>
                                        <span class="rr-select-title">Commissary Push</span>
                                    </div>
                                    <span class="rr-select-desc">Replenish branch from Central Hub</span>
                                </button>
                                <button type="button" class="rr-select-card" data-strat="jit_back_to_back" onclick="selectRuleStrategyCard('jit_back_to_back')">
                                    <div class="rr-select-card-head">
                                        <span class="rr-select-icon amber"><i class="ph ph-lightning"></i></span>
                                        <span class="rr-select-title">JIT Demand</span>
                                    </div>
                                    <span class="rr-select-desc">Exact-MOQ demand replenishment</span>
                                </button>
                            </div>
                            <select id="formStrategy" class="rr-form-select" style="display: none;" onchange="updateRuleDrawerLivePreview()">
                                <option value="min_max">Min-Max (Order Point)</option>
                                <option value="commissary_push">Central Commissary Push</option>
                                <option value="jit_back_to_back">JIT / Demand Driven</option>
                            </select>
                        </div>

                        <div class="rr-form-grid">
                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formBranchSelect">
                                    <span>Target Branch Location <strong style="color: #ef4444;">*</strong></span>
                                </label>
                                <select id="formBranchSelect" class="rr-form-select" required onchange="updateRuleDrawerLivePreview()">
                                    @foreach($branches as $b)
                                        <option value="{{ $b->branch_id }}" data-name="{{ $b->branch_name }}">{{ $b->branch_name }} ({{ $b->branch_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formTargetWarehouseSelect">
                                    <span>Receiving Warehouse Facility</span>
                                </label>
                                <select id="formTargetWarehouseSelect" class="rr-form-select" onchange="updateRuleDrawerLivePreview()">
                                    <option value="">-- Branch Default Storage --</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->warehouse_id }}">{{ $wh->warehouse_code }} — {{ $wh->warehouse_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Product / Category Multi-Select Card (Shown on Create) + Single SKU Edit Fallback -->
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <span class="rms-drawer-card-title"><i class="ph ph-tag"></i><span>Target Products &amp; Categories</span></span>
                        <span class="rms-drawer-card-badge" id="drawerSelectedSkuBadge">0 SKUs Selected</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <!-- Single SKU Select Mode (Visible when editing 1 rule, or synced automatically) -->
                        <div id="drawerSingleSkuSection" style="display: none;">
                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formProductSelect">
                                    <span>Product / SKU <strong style="color: #ef4444;">*</strong></span>
                                </label>
                                <select id="formProductSelect" class="rr-form-select" onchange="handleFormProductChange(this.value)">
                                    <option value="">-- Select Product Master --</option>
                                    @foreach($items as $it)
                                        <option value="{{ $it->id }}" data-sku="{{ $it->sku }}" data-name="{{ $it->name }}" data-category="{{ $it->category }}" data-uom="{{ $it->uom }}" data-cost="{{ $it->cost_price }}" data-stock="{{ $it->current_stock }}">
                                            {{ $it->sku }} — {{ $it->name }} ({{ $it->uom }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Multi-SKU & Category Bulk Selection Mode (Shown when creating new rule) -->
                        <div id="drawerBulkScopeSection">
                            <div class="rr-scope-header-row">
                                <span style="font-size: 0.74rem; font-weight: 700; color: #334155;">Quick Select by Product Category (Bulk Apply):</span>
                                <div style="display: flex; gap: 6px;">
                                    <button type="button" class="rr-btn rr-btn-outline" style="padding: 3px 8px; font-size: 0.7rem;" onclick="selectAllVisibleScopeSkus()">Select All</button>
                                    <button type="button" class="rr-btn rr-btn-outline" style="padding: 3px 8px; font-size: 0.7rem;" onclick="clearScopeSelection()">Clear</button>
                                </div>
                            </div>

                            <!-- Interactive Category Chips -->
                            <div class="rr-cat-chips-wrap" id="drawerCategoryChips">
                                <!-- Populated dynamically with product counts per category -->
                            </div>

                            <!-- Searchable SKU Checklist -->
                            <div class="rr-scope-search-row">
                                <div class="rr-search-box" style="width: 100%;">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" id="drawerSkuFilterInput" class="rr-search-input" placeholder="Filter products by SKU, name, or category..." oninput="renderDrawerSkuChecklist()">
                                </div>
                            </div>

                            <div class="rr-sku-checklist" id="drawerScopeSkuChecklist">
                                <!-- Populated dynamically with checkboxes -->
                            </div>
                        </div>

                        <!-- Selected Product Lookup Chip (when single product selected) -->
                        <div class="rr-lookup-chip" id="drawerProductSummaryChip" style="display: none; margin-top: 12px;">
                            <div class="rr-lookup-chip-item"><span>SKU</span><strong id="chipProdSku" style="font-family: monospace;">—</strong></div>
                            <div class="rr-lookup-chip-item"><span>Category</span><strong id="chipProdCategory">—</strong></div>
                            <div class="rr-lookup-chip-item"><span>Base UOM</span><strong id="chipProdUom">—</strong></div>
                            <div class="rr-lookup-chip-item"><span>Unit Cost</span><strong id="chipProdCost">₱0.00</strong></div>
                            <div class="rr-lookup-chip-item"><span>Global Stock</span><strong id="chipProdStock">0</strong></div>
                        </div>

                        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap;">
                            <label class="rr-switch-card">
                                <input type="checkbox" id="formIsActive" checked onchange="updateRuleDrawerLivePreview()">
                                <div>
                                    <div class="rr-switch-title">Rule Active in Replenishment Simulator</div>
                                    <div class="rr-switch-desc">Paused rules remain saved without generating low-stock triggers.</div>
                                </div>
                            </label>
                            <button type="button" class="rr-btn rr-btn-secondary" onclick="switchRuleDrawerTab('thresholds')">
                                <span>Next: Par Thresholds</span>
                                <i class="ph ph-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Par Thresholds & Order Math -->
            <div class="rms-drawer-tab-pane" id="rrDrawerPane-thresholds">
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <span class="rms-drawer-card-title"><i class="ph ph-sliders"></i><span>Min / Max Boundaries &amp; Packaging Multiples</span></span>
                        <span class="rms-drawer-card-badge">Applied to Selected Scope</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="rr-form-grid">
                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formMinStock">
                                    <span>Min Stock / Reorder Point <strong style="color: #ef4444;">*</strong></span>
                                </label>
                                <input type="number" step="0.01" min="0" id="formMinStock" class="rr-form-input" placeholder="e.g. 15.00" oninput="updateRuleDrawerLivePreview()">
                                <span class="rr-form-hint">Triggers replenishment when Net Available &le; Min</span>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formMaxStock">
                                    <span>Max Target Par Stock <strong style="color: #ef4444;">*</strong></span>
                                </label>
                                <input type="number" step="0.01" min="0.01" id="formMaxStock" class="rr-form-input" placeholder="e.g. 50.00" oninput="updateRuleDrawerLivePreview()">
                                <span class="rr-form-hint">Must be greater than Min Stock</span>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formSafetyBuffer">
                                    <span>Safety Buffer (Critical Floor)</span>
                                </label>
                                <input type="number" step="0.01" min="0" id="formSafetyBuffer" class="rr-form-input" value="0" placeholder="Auto (30% of Min)" oninput="updateRuleDrawerLivePreview()">
                                <span class="rr-form-hint">Marks item CRITICAL UNDER PAR below this floor</span>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formMoq">
                                    <span>Minimum Order Qty (MOQ) <strong style="color: #ef4444;">*</strong></span>
                                </label>
                                <input type="number" step="0.01" min="1" id="formMoq" class="rr-form-input" value="1.00" oninput="updateRuleDrawerLivePreview()">
                                <span class="rr-form-hint">Rounds deficit up to crate / box / sack multiples</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Math Formula Sandbox -->
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <span class="rms-drawer-card-title"><i class="ph ph-calculator"></i><span>Live Replenishment Formula Preview</span></span>
                        <span class="rms-drawer-card-badge" id="drawerMathBadge">Healthy / Check</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="rr-math-preview-grid">
                            <div class="rr-math-box">
                                <span>Est. Branch On-Hand</span>
                                <strong id="mathEstOnHand">0</strong>
                            </div>
                            <div class="rr-math-box">
                                <span>Trigger Point (&le; Min)</span>
                                <strong id="mathTriggerAt">0</strong>
                            </div>
                            <div class="rr-math-box accent">
                                <span>Replenish to Max (MOQ)</span>
                                <strong id="mathSuggestedQty">0</strong>
                            </div>
                            <div class="rr-math-box">
                                <span>Projected Order Value</span>
                                <strong id="mathEstCost">₱0.00</strong>
                            </div>
                        </div>
                        <p class="rr-form-hint" style="margin: 10px 0 0 0;">
                            Formula: <code>Order Qty = Ceil((Max - Net Available) / MOQ) &times; MOQ</code> when <code>Net Available &le; Min Par</code>.
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Fulfillment Route & Auto-Draft -->
            <div class="rms-drawer-tab-pane" id="rrDrawerPane-routing">
                <div class="rms-drawer-card">
                    <div class="rms-drawer-card-head">
                        <span class="rms-drawer-card-title"><i class="ph ph-git-fork"></i><span>Fulfillment Source &amp; Supply Route</span></span>
                        <span class="rms-drawer-card-badge">PO or Internal Transfer</span>
                    </div>
                    <div class="rms-drawer-card-body">
                        <div class="rr-form-group" style="margin-bottom: 14px;">
                            <label class="rr-form-label">Action Fulfillment Route <strong style="color:#ef4444;">*</strong></label>
                            <div class="rr-route-cards" id="rrRouteCards">
                                <button type="button" class="rr-select-card active" data-route="purchase_order" onclick="selectRuleRouteCard('purchase_order')">
                                    <div class="rr-select-card-head">
                                        <span class="rr-select-icon blue"><i class="ph ph-shopping-cart"></i></span>
                                        <span class="rr-select-title">Purchase Order (External Vendor)</span>
                                    </div>
                                    <span class="rr-select-desc">Creates draft vendor PO with 12% VAT computation</span>
                                </button>
                                <button type="button" class="rr-select-card" data-route="internal_transfer" onclick="selectRuleRouteCard('internal_transfer')">
                                    <div class="rr-select-card-head">
                                        <span class="rr-select-icon purple"><i class="ph ph-arrows-left-right"></i></span>
                                        <span class="rr-select-title">Internal Transfer (Commissary)</span>
                                    </div>
                                    <span class="rr-select-desc">Creates draft stock transfer from central hub</span>
                                </button>
                            </div>
                            <select id="formActionRoute" class="rr-form-select" style="display: none;" onchange="handleFormRouteToggle(this.value)">
                                <option value="purchase_order">Purchase Order (External Vendor)</option>
                                <option value="internal_transfer">Internal Transfer (Central Commissary)</option>
                            </select>
                        </div>

                        <div class="rr-form-grid">
                            <div class="rr-form-group full" id="groupVendorSection">
                                <label class="rr-form-label" for="formVendorSelect">
                                    <span>Preferred Vendor Partner</span>
                                </label>
                                <select id="formVendorSelect" class="rr-form-select" onchange="updateRuleDrawerLivePreview()">
                                    <option value="">-- Direct Supply / Default Vendor --</option>
                                    @foreach($vendors as $v)
                                        <option value="{{ $v->id }}" data-name="{{ $v->legal_name }}">{{ $v->legal_name }} ({{ $v->vendor_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rr-form-group full" id="groupCommissarySection" style="display: none;">
                                <label class="rr-form-label" for="formWarehouseSelect">
                                    <span>Source Central Commissary / Warehouse</span>
                                </label>
                                <select id="formWarehouseSelect" class="rr-form-select" onchange="updateRuleDrawerLivePreview()">
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->warehouse_id }}" data-name="{{ $wh->warehouse_name }}">{{ $wh->warehouse_name }} ({{ $wh->warehouse_code }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formLeadTime">
                                    <span>Lead Time (Days) <strong style="color:#ef4444;">*</strong></span>
                                </label>
                                <input type="number" min="0" id="formLeadTime" class="rr-form-input" value="2" oninput="updateRuleDrawerLivePreview()">
                                <span class="rr-form-hint">Days required from trigger to dock arrival</span>
                            </div>

                            <div class="rr-form-group">
                                <label class="rr-form-label" for="formNotes">Operational Notes</label>
                                <input type="text" id="formNotes" class="rr-form-input" placeholder="e.g. Keep extra weekend buffer">
                            </div>

                            <div class="rr-form-group full">
                                <label class="rr-switch-card">
                                    <input type="checkbox" id="formAutoDraft" onchange="updateRuleDrawerLivePreview()">
                                    <div>
                                        <div class="rr-switch-title">Enable Automated Batch Draft Generation</div>
                                        <div class="rr-switch-desc">Automatically queues selected items in PO / Internal Transfer replenishment runs when under par.</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <!-- 4. Sticky Drawer Footer -->
        <div class="rms-drawer-footer">
            <div class="rms-drawer-footer-meta">
                <span>Scope: <strong id="drawerFooterScopeSummary">0 SKUs</strong></span>
                <span>·</span>
                <span>Par: <strong id="drawerFooterParSummary">0 / 0</strong></span>
                <span>·</span>
                <span>Route: <strong id="drawerFooterRouteSummary">PO</strong></span>
            </div>
            <div class="rms-drawer-footer-actions">
                <button type="button" class="rr-btn rr-btn-secondary" onclick="closeRuleModal()">Cancel</button>
                <button type="submit" form="ruleForm" class="rr-btn rr-btn-primary" id="btnSaveRule">
                    <i class="ph ph-floppy-disk"></i>
                    <span id="btnSaveRuleLabel">Save Rule</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- 6. BATCH DRAFT CONFIRMATION MODAL -->
    <div class="rr-modal-backdrop" id="batchModalBackdrop" onclick="handleBatchBackdropClick(event)">
        <div class="rr-modal-card" style="width: 720px;">
            <div class="rr-modal-header">
                <div class="rr-modal-title">
                    <i class="ph ph-sparkle" style="color: var(--rr-brand);"></i>
                    <span>Generate Replenishment Drafts</span>
                </div>
                <button type="button" class="rr-modal-close-btn" onclick="closeBatchDraftModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
            <div class="rr-modal-body">
                <div id="batchModalSummaryText" style="font-size: 0.8rem; color: #475569; line-height: 1.5;">
                    Evaluating live par level deficits...
                </div>
                <div class="rr-table-container" style="max-height: 280px; overflow-y: auto; margin-top: 10px;">
                    <table class="rr-table">
                        <thead>
                            <tr>
                                <th>Item &amp; SKU</th>
                                <th>Branch</th>
                                <th class="num">Net Avail</th>
                                <th class="num">Min</th>
                                <th class="num">Order Qty</th>
                                <th>Route</th>
                                <th>Target Partner / Source</th>
                            </tr>
                        </thead>
                        <tbody id="batchModalTableBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="rr-modal-footer">
                <button type="button" class="rr-btn rr-btn-secondary" onclick="closeBatchDraftModal()">Cancel</button>
                <button type="button" class="rr-btn rr-btn-primary" id="btnConfirmBatchDraft" onclick="executeBatchDraftGeneration()">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Confirm &amp; Create Drafts</span>
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

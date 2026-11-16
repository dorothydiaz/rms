@extends('layouts.app')

@section('title', 'Movement Types & Supply Routes - Inventory Configuration')

@push('styles')
    @vite('resources/css/modules/movement-supply-flows.css')
@endpush

@push('blade-data')
<script>
    window.MovementFlowsConfig = Object.freeze({
        initialWarehouses: @json($initialWarehouses ?? []),
        initialStorageAreas: @json($initialStorageAreas ?? []),
        initialBoundaryAreas: @json($initialBoundaryAreas ?? []),
        initialMovementTypes: @json($initialMovementTypes ?? []),
        initialSupplyFlows: @json($initialSupplyFlows ?? []),
        initialFlowRules: @json($initialFlowRules ?? []),
        initialCategories: @json($initialCategories ?? []),
        initialProducts: @json($initialProducts ?? []),
        csrfToken: @json(csrf_token()),
        routes: {
            warehousesPage: @json(route('inventory.warehouses')),
            movementFlowsApi: @json(route('inventory.api.movement-flows')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/movement-supply-flows.js')
@endpush

@section('content')
<div class="msf-workspace" id="msfAppRoot">

    <!-- Toast Notifications Container -->
    <div class="msf-toast-container" id="msfToastContainer"></div>

    <!-- =================================================================== -->
    <!-- DIRECTORY VIEW: Header, KPIs, Modern Controls Bar & 5-Per-Row Cards  -->
    <!-- =================================================================== -->
    <div id="msfDirectoryView" class="msf-directory-view">

        <!-- 1. Standardized RFQ-Style Flat Page Header -->
        <div class="hr-parent-header">
            <div class="hr-parent-title-row">
                <div>
                    <h1 class="hr-parent-title">
                        <i class="ph ph-arrows-split"></i>
                        <span>Movement Types &amp; Supply Routes</span>
                    </h1>
                    <p class="hr-parent-subtitle">Standard warehouse operation templates with configurable multi-step routing paths, category rules, and scanner validations</p>
                </div>
                <div class="hr-page-actions">
                    <a href="{{ route('inventory.warehouses') }}" class="hr-btn hr-btn-secondary">
                        <i class="ph ph-warehouse"></i>
                        <span>Warehouse &amp; Storage Area</span>
                    </a>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="openMovementTypeModal()">
                        <i class="ph ph-plus-circle"></i>
                        <span>New Movement Type</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- 2. Clean 3-Card KPI Summary Bar -->
        <div class="msf-kpi-grid">
            <div class="msf-kpi-card" onclick="filterByKind('ALL')">
                <div class="msf-kpi-head">
                    <span class="msf-kpi-label">Active Movement Types</span>
                    <span class="msf-kpi-icon purple"><i class="ph ph-barcode"></i></span>
                </div>
                <div class="msf-kpi-val" id="msfKpiTotalTypes">0</div>
                <div class="msf-kpi-sub" id="msfKpiTotalSub">Standard operations available across warehouses</div>
            </div>

            <div class="msf-kpi-card">
                <div class="msf-kpi-head">
                    <span class="msf-kpi-label">Configured Operations</span>
                    <span class="msf-kpi-icon blue"><i class="ph ph-flow-arrow"></i></span>
                </div>
                <div class="msf-kpi-val" id="msfKpiOpsBreakdown">0 In · 0 Out</div>
                <div class="msf-kpi-sub">Receipts, dispatches, transfers &amp; adjustments</div>
            </div>

            <div class="msf-kpi-card">
                <div class="msf-kpi-head">
                    <span class="msf-kpi-label">Facilities &amp; Chain Integrity</span>
                    <span class="msf-kpi-icon emerald"><i class="ph ph-buildings"></i></span>
                </div>
                <div class="msf-kpi-val" id="msfKpiFacilities">0 Facilities</div>
                <div class="msf-kpi-sub" id="msfKpiFacilitiesList">Central, Cold-Chain &amp; Branch Hubs</div>
            </div>
        </div>

        <!-- 3. Modern Single-Row Controls Bar -->
        <div class="msf-controls-bar" role="region" aria-label="Movement Type Filters and Search">
            <div class="msf-controls-left">
                <!-- Operation Kind Segmented Pills -->
                <div class="msf-segmented-track" id="msfKindPills" role="tablist" aria-label="Filter by operation kind">
                    <button type="button" class="msf-seg-btn active" data-kind="ALL" onclick="filterByKind('ALL')">
                        <span>All</span>
                        <span class="msf-seg-count" id="msfCountAll">0</span>
                    </button>
                    <button type="button" class="msf-seg-btn" data-kind="Inbound Receipt" onclick="filterByKind('Inbound Receipt')">
                        <span class="msf-seg-dot inbound"></span>
                        <span>Inbound</span>
                        <span class="msf-seg-count" id="msfCountInbound">0</span>
                    </button>
                    <button type="button" class="msf-seg-btn" data-kind="Outbound Dispatch" onclick="filterByKind('Outbound Dispatch')">
                        <span class="msf-seg-dot outbound"></span>
                        <span>Outbound</span>
                        <span class="msf-seg-count" id="msfCountOutbound">0</span>
                    </button>
                    <button type="button" class="msf-seg-btn" data-kind="Internal Transfer" onclick="filterByKind('Internal Transfer')">
                        <span class="msf-seg-dot transfer"></span>
                        <span>Transfers</span>
                        <span class="msf-seg-count" id="msfCountTransfer">0</span>
                    </button>
                    <button type="button" class="msf-seg-btn" data-kind="Adjustment / Scrap" onclick="filterByKind('Adjustment / Scrap')">
                        <span class="msf-seg-dot adjustment"></span>
                        <span>Adjustments</span>
                        <span class="msf-seg-count" id="msfCountAdjust">0</span>
                    </button>
                </div>

                <span class="msf-controls-divider" aria-hidden="true"></span>

                <!-- Facility Scope Pills -->
                <div class="msf-facility-filter">
                    <span class="msf-facility-label" title="Filter by warehouse facility"><i class="ph ph-warehouse"></i></span>
                    <div class="msf-facility-pills" id="msfWarehousePills">
                        <button type="button" class="msf-fac-btn active" data-wh="ALL" onclick="filterByWarehouse('ALL')">All</button>
                    </div>
                </div>
            </div>

            <!-- Right Search Input -->
            <div class="msf-search-wrap">
                <div class="msf-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="msfSearchInput" placeholder="Search code, route, category, or area..." oninput="onSearchInput(this.value)">
                    <button type="button" class="msf-search-clear" id="msfSearchClearBtn" onclick="clearMsfSearch()" title="Clear search" style="display: none;">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- 4. Movement Types & Configured Routes Cards List -->
        <div class="msf-cards-container" id="msfCardsContainer">
            <!-- Rendered dynamically by renderMovementTypeCards() -->
        </div>

    </div>

    <!-- =================================================================== -->
    <!-- HALF-SCREEN DRAWER (Below Top Main Header): Configure Route Editor   -->
    <!-- =================================================================== -->
    <div class="msf-drawer-backdrop" id="msfDrawerBackdrop" onclick="closeMovementTypeModal()" aria-hidden="true"></div>

    <aside class="msf-drawer-panel" id="msfMovementTypeModal" aria-labelledby="msfModalTitle" aria-hidden="true">

        <!-- 1. Sticky Drawer Header -->
        <div class="msf-drawer-header">
            <div class="msf-drawer-head-left">
                <div class="msf-hero-icon" id="msfHeroIcon">
                    <i class="ph ph-arrows-split"></i>
                </div>
                <div style="min-width: 0;">
                    <div class="msf-hero-title-row">
                        <h2 class="msf-hero-title" id="msfModalTitle">Configure Movement Type &amp; Route</h2>
                        <span class="msf-card-code" id="msfHeroCodeChip">NEW-ROUTE</span>
                        <span class="msf-kind-indicator inbound" id="msfHeroKindChip">
                            <span class="msf-kind-dot"></span>
                            <span>Inbound Receipt</span>
                        </span>
                        <span class="msf-status-pill active" id="msfHeroStatusPill">Active</span>
                    </div>
                    <p class="msf-hero-sub" id="msfModalSubtitle">Define operation identity, configure multi-step hand-offs, and assign category rules.</p>
                </div>
            </div>

            <div class="msf-drawer-head-actions">
                <div class="msf-chain-pill ok" id="msfHeroChainBadge">
                    <i class="ph ph-link"></i>
                    <span>Continuous</span>
                </div>
                <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" id="msfWorkspaceDuplicateBtn" onclick="duplicateEditingMovementType()" style="display: none;" title="Duplicate Route">
                    <i class="ph ph-copy"></i>
                    <span>Duplicate</span>
                </button>
                <button type="button" class="msf-btn msf-btn-danger-outline msf-btn-sm" id="msfWorkspaceDeleteBtn" onclick="deleteEditingMovementType()" style="display: none;" title="Delete Route">
                    <i class="ph ph-trash"></i>
                </button>
                <button type="button" class="msf-drawer-close" onclick="closeMovementTypeModal()" title="Close Drawer (Esc)" aria-label="Close drawer">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <!-- 2. RFQ-Style Tab Navigation Bar -->
        <div class="msf-drawer-tabs-strip">
            <nav class="rfq-nav-tabs-bar msf-drawer-nav" aria-label="Movement Type Configuration Tabs">
                <button type="button" class="rfq-tab-btn msf-page-tab-btn active" data-msf-tab="general" onclick="switchMsfWorkspaceTab('general')">
                    <i class="ph ph-identification-card"></i>
                    <span>General &amp; Operation Spec</span>
                </button>
                <button type="button" class="rfq-tab-btn msf-page-tab-btn" data-msf-tab="pipeline" onclick="switchMsfWorkspaceTab('pipeline')">
                    <i class="ph ph-git-fork"></i>
                    <span>Route Pipeline &amp; Step Sequence</span>
                    <span class="rfq-tab-count msf-tab-count" id="msfTabStepCount">0</span>
                </button>
                <button type="button" class="rfq-tab-btn msf-page-tab-btn" data-msf-tab="rules" onclick="switchMsfWorkspaceTab('rules')">
                    <i class="ph ph-sliders-horizontal"></i>
                    <span>Category Rules &amp; Policies</span>
                    <span class="rfq-tab-count msf-tab-count" id="msfTabRuleCount">All</span>
                </button>
            </nav>
        </div>

        <!-- 3. Scrollable Drawer Body -->
        <form id="msfMovementTypeForm" class="msf-drawer-body" onsubmit="event.preventDefault(); saveMovementType();">
            <input type="hidden" id="msfFormId" value="">

            <!-- TAB 1: GENERAL & OPERATION SPECIFICATION -->
            <div class="msf-page-tab-pane active" id="msfPaneGeneral">
                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <span class="msf-bento-title">
                            <i class="ph ph-identification-card"></i>
                            <span>Operation Identity &amp; Facility Assignment</span>
                        </span>
                        <span class="msf-bento-badge">Required Master Fields</span>
                    </div>
                    <div class="msf-bento-body">
                        <div class="msf-form-group" style="margin-bottom: 14px;">
                            <label class="msf-label">Operation Classification <span class="req">*</span></label>
                            <div class="msf-kind-selector-grid" id="msfKindSelectorCards">
                                <button type="button" class="msf-kind-select-card active" data-kind-val="Inbound Receipt" onclick="selectModalOperationKind('Inbound Receipt')">
                                    <div class="msf-kind-select-head">
                                        <span class="msf-kind-select-icon inbound"><i class="ph ph-arrow-down-left"></i></span>
                                        <span class="msf-kind-select-title">Inbound Receipt</span>
                                    </div>
                                    <span class="msf-kind-select-desc">+ Stock In from suppliers or returns</span>
                                </button>

                                <button type="button" class="msf-kind-select-card" data-kind-val="Outbound Dispatch" onclick="selectModalOperationKind('Outbound Dispatch')">
                                    <div class="msf-kind-select-head">
                                        <span class="msf-kind-select-icon outbound"><i class="ph ph-arrow-up-right"></i></span>
                                        <span class="msf-kind-select-title">Outbound Dispatch</span>
                                    </div>
                                    <span class="msf-kind-select-desc">- Stock Out for branch delivery or POS</span>
                                </button>

                                <button type="button" class="msf-kind-select-card" data-kind-val="Internal Transfer" onclick="selectModalOperationKind('Internal Transfer')">
                                    <div class="msf-kind-select-head">
                                        <span class="msf-kind-select-icon transfer"><i class="ph ph-arrows-left-right"></i></span>
                                        <span class="msf-kind-select-title">Internal Transfer</span>
                                    </div>
                                    <span class="msf-kind-select-desc">Bin-to-bin relocation or staging move</span>
                                </button>

                                <button type="button" class="msf-kind-select-card" data-kind-val="Stock Adjustment" onclick="selectModalOperationKind('Stock Adjustment')">
                                    <div class="msf-kind-select-head">
                                        <span class="msf-kind-select-icon adjustment"><i class="ph ph-trash"></i></span>
                                        <span class="msf-kind-select-title">Adjustment / Scrap</span>
                                    </div>
                                    <span class="msf-kind-select-desc">Spoilage write-offs or cycle counts</span>
                                </button>
                            </div>

                            <select id="msfFormKind" class="msf-select" style="display: none;" onchange="onModalKindChange()">
                                <option value="Inbound Receipt">Inbound Receipt (+ Stock In)</option>
                                <option value="Outbound Dispatch">Outbound Dispatch (- Stock Out)</option>
                                <option value="Internal Transfer">Internal Transfer (Relocation)</option>
                                <option value="Stock Adjustment">Adjustment / Scrap (Write-Off)</option>
                            </select>
                        </div>

                        <div class="msf-form-grid-2">
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormName">Operation Name <span class="req">*</span></label>
                                <input type="text" id="msfFormName" class="msf-input" placeholder="e.g. Vendor Goods Receipt (Dry Goods)" required oninput="updateLivePreview()">
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormCode">Short Code <span class="req">*</span> <span class="msf-hint">(e.g. WH-MAIN-IN)</span></label>
                                <input type="text" id="msfFormCode" class="msf-input mono" placeholder="e.g. WH-MAIN-IN" required oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9-]/g, ''); updateLivePreview();">
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormWarehouse">Target Warehouse Facility <span class="req">*</span></label>
                                <select id="msfFormWarehouse" class="msf-select" required onchange="onModalWarehouseChange()">
                                    <!-- Populated dynamically -->
                                </select>
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormTriggerDoc">Source ERP Document Trigger</label>
                                <select id="msfFormTriggerDoc" class="msf-select" onchange="updateLivePreview()">
                                    <option value="PO Goods Receipt (GRN)">PO Goods Receipt (GRN)</option>
                                    <option value="POS / Sales Order Dispatch">POS / Sales Order Dispatch</option>
                                    <option value="Branch Transfer Requisition">Branch Transfer Requisition</option>
                                    <option value="Internal Bin Relocation">Internal Bin Relocation</option>
                                    <option value="Cycle Count / Scrap Write-Off">Cycle Count / Scrap Write-Off</option>
                                    <option value="Vendor Return (RMA)">Vendor Return (RMA)</option>
                                </select>
                            </div>
                            <div class="msf-form-group full">
                                <label class="msf-label" for="msfFormHandlingNote">Operator Handling &amp; Compliance Instructions</label>
                                <textarea id="msfFormHandlingNote" class="msf-textarea" rows="2" placeholder="e.g. Enforce FEFO expiry verification &amp; core temperature check below -18°C before put-away..." oninput="updateLivePreview()"></textarea>
                            </div>
                        </div>

                        <div class="msf-tab-footer-cta">
                            <label class="msf-switch-card">
                                <input type="checkbox" id="msfFormIsActive" checked onchange="updateLivePreview()">
                                <div>
                                    <div class="msf-switch-title">Operation Active for Warehouse Execution</div>
                                    <div class="msf-switch-desc">Inactive movement types are hidden from handheld scanners and automatic triggers.</div>
                                </div>
                            </label>
                            <button type="button" class="msf-btn msf-btn-secondary" onclick="switchMsfWorkspaceTab('pipeline')">
                                <span>Next: Route Pipeline &amp; Steps</span>
                                <i class="ph ph-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Live Operation Blueprint Card -->
                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <span class="msf-bento-title"><i class="ph ph-clipboard-text"></i><span>Live Operation Blueprint</span></span>
                        <span class="msf-bento-badge" id="msfSummaryActivePill">Active</span>
                    </div>
                    <div class="msf-bento-body">
                        <div class="msf-summary-rows-2col">
                            <div class="msf-summary-row"><span>Operation Code</span><strong class="mono" id="msfSumCode">—</strong></div>
                            <div class="msf-summary-row"><span>Target Facility</span><strong id="msfSumWarehouse">—</strong></div>
                            <div class="msf-summary-row"><span>Operation Kind</span><strong id="msfSumKind">—</strong></div>
                            <div class="msf-summary-row"><span>Ledger Effect</span><strong id="msfSumEffect">—</strong></div>
                            <div class="msf-summary-row"><span>ERP Trigger</span><strong id="msfSumTrigger">—</strong></div>
                            <div class="msf-summary-row"><span>Category Scope</span><strong id="msfSumCategories">All Categories</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: ROUTE PIPELINE & STEP SEQUENCE -->
            <div class="msf-page-tab-pane" id="msfPanePipeline">
                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="msf-bento-title">
                                <i class="ph ph-flow-arrow"></i>
                                <span>Live Route Pipeline</span>
                            </span>
                            <span class="msf-chain-pill ok" id="msfChainStatusBanner">
                                <i class="ph ph-check-circle"></i>
                                <span>Continuous Handoffs</span>
                            </span>
                        </div>

                        <div class="msf-presets-bar">
                            <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('1-Step Direct')">
                                <i class="ph ph-arrow-right"></i> 1-Step
                            </button>
                            <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('2-Step Staged')">
                                <i class="ph ph-arrows-split"></i> 2-Step
                            </button>
                            <button type="button" class="msf-preset-btn" onclick="applyRoutePreset('3-Step Quality Check')">
                                <i class="ph ph-shield-check"></i> 3-Step QA
                            </button>
                            <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" id="msfAutoChainBtn" onclick="autoChainModalSteps()" title="Sync each step's Origin Area to match the previous step's Destination Area">
                                <i class="ph ph-link"></i>
                                <span>Auto-Chain</span>
                            </button>
                        </div>
                    </div>

                    <div class="msf-bento-body">
                        <!-- Live Draggable Pipeline Preview -->
                        <div class="msf-modal-route-preview" id="msfModalRoutePreview">
                            <!-- Populated by updateModalRoutePreview() -->
                        </div>

                        <!-- Draggable Step Sequence Builder -->
                        <div class="msf-steps-toolbar">
                            <span class="msf-steps-toolbar-title">
                                <i class="ph ph-dots-six-vertical"></i>
                                <span>Step Sequence &amp; Scanner Checkpoints (Drag cards or use arrows to reorder)</span>
                            </span>
                            <button type="button" class="msf-btn msf-btn-secondary msf-btn-sm" onclick="addNewStepToModal()">
                                <i class="ph ph-plus"></i>
                                <span>Add Next Step</span>
                            </button>
                        </div>

                        <div class="msf-steps-container" id="msfModalStepsContainer">
                            <!-- Populated dynamically by renderModalSteps() -->
                        </div>
                    </div>
                </div>

                <!-- Topology Diagnostics Card -->
                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <span class="msf-bento-title"><i class="ph ph-git-commit"></i><span>Topology Diagnostics</span></span>
                    </div>
                    <div class="msf-bento-body">
                        <div class="msf-summary-rows-2col">
                            <div class="msf-summary-row"><span>Total Steps</span><strong id="msfSumSteps">0 Steps</strong></div>
                            <div class="msf-summary-row"><span>Chain Continuity</span><strong id="msfSumChain">Continuous</strong></div>
                            <div class="msf-summary-row"><span>Initial Origin</span><strong id="msfDiagOrigin">—</strong></div>
                            <div class="msf-summary-row"><span>Final Destination</span><strong id="msfDiagDest">—</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 3: CATEGORY AUTO-ROUTING & EXECUTION POLICIES -->
            <div class="msf-page-tab-pane" id="msfPaneRules">
                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <span class="msf-bento-title">
                            <i class="ph ph-tag"></i>
                            <span>Product Categories Covered by This Route</span>
                        </span>
                        <button type="button" class="msf-btn msf-btn-outline msf-btn-sm" onclick="clearModalCategories()">
                            <span>Reset to All Categories</span>
                        </button>
                    </div>
                    <div class="msf-bento-body">
                        <p class="msf-field-help" style="margin: 0 0 12px 0;">
                            Select specific product categories to automatically route matching SKUs through this operation's checkpoints. Leave unselected to apply across all categories.
                        </p>
                        <div class="msf-category-chips-grid" id="msfCategoryRulesGrid">
                            <!-- Populated dynamically by renderModalCategoryChips() -->
                        </div>
                    </div>
                </div>

                <div class="msf-bento-card">
                    <div class="msf-bento-head">
                        <span class="msf-bento-title">
                            <i class="ph ph-sliders"></i>
                            <span>Allocation &amp; Exception Policies</span>
                        </span>
                        <span class="msf-bento-badge">Execution Governance</span>
                    </div>
                    <div class="msf-bento-body">
                        <div class="msf-form-grid-2">
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormAllocation">Bin Removal / Put-Away Strategy</label>
                                <select id="msfFormAllocation" class="msf-select" onchange="updateLivePreview()">
                                    <option value="FEFO (Earliest Expiry First)">FEFO — Earliest Expiry First (Perishables)</option>
                                    <option value="FIFO (Oldest Receipt First)">FIFO — Oldest Receipt First (Standard)</option>
                                    <option value="Closest Available Bin">Closest Available Bin (Fastest Pick)</option>
                                </select>
                            </div>
                            <div class="msf-form-group">
                                <label class="msf-label" for="msfFormShortagePolicy">Step Quantity Variance Policy</label>
                                <select id="msfFormShortagePolicy" class="msf-select" onchange="updateLivePreview()">
                                    <option value="Create Backorder">Allow Partial &amp; Create Backorder</option>
                                    <option value="Cancel Remainder">Allow Partial &amp; Cancel Remainder</option>
                                    <option value="Strict Full Qty">Require Full Scheduled Quantity</option>
                                </select>
                            </div>
                        </div>

                        <div class="msf-summary-rows-2col" style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #f1f5f9;">
                            <div class="msf-summary-row"><span>Allocation Rule</span><strong id="msfRuleSumAlloc">FEFO (Earliest Expiry First)</strong></div>
                            <div class="msf-summary-row"><span>Shortage Policy</span><strong id="msfRuleSumShortage">Create Backorder</strong></div>
                            <div class="msf-summary-row"><span>Category Filter</span><strong id="msfRuleSumCats">All Categories</strong></div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <!-- 4. Sticky Drawer Footer -->
        <div class="msf-drawer-footer">
            <div class="msf-drawer-footer-metrics">
                <span class="msf-footer-stat"><strong id="msfMetricStepCount">0</strong> Steps</span>
                <span class="msf-footer-sep">·</span>
                <span class="msf-footer-stat"><strong id="msfMetricScanCount">0</strong> Scans</span>
                <span class="msf-footer-sep">·</span>
                <span class="msf-footer-stat">Chain: <strong id="msfMetricChainState">OK</strong></span>
            </div>

            <div class="msf-drawer-footer-actions">
                <button type="button" class="msf-btn msf-btn-outline" onclick="closeMovementTypeModal()">Cancel</button>
                <button type="button" class="msf-btn msf-btn-primary" onclick="saveMovementType()">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Movement Type &amp; Route</span>
                </button>
            </div>
        </div>

    </aside>

</div>
@endsection

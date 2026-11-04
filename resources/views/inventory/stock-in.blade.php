@extends('layouts.app')

@section('title', 'Stock In / Receiving (GRN) - Inventory Operations')

@push('styles')
    @vite([
        'resources/css/modules/request-quotations.css',
        'resources/css/modules/purchase-orders.css',
        'resources/css/modules/stock-in.css'
    ])
@endpush

@push('blade-data')
<script>
    window.StockInConfig = Object.freeze({
        routes: {
            stockInData: @json(route('inventory.api.stock-in-data')),
            receiveStock: @json(route('inventory.api.receive-stock')),
            purchaseOrders: @json(route('purchase.purchase-orders')),
            warehousesPage: @json(route('inventory.warehouses')),
            movementFlowsPage: @json(route('inventory.movement-flows')),
        },
        company: {
            name: @json($companyName ?? config('app.name', 'RMS')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Warehouse Logistics Supervisor'),
            email: @json(auth()->user()->email ?? 'warehouse@rms.ph'),
        },
        data: {
            initialPurchaseOrders: @json($initialPurchaseOrders ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialReceipts: @json($initialReceipts ?? []),
            initialWarehouses: @json($initialWarehouses ?? []),
            initialStorageAreas: @json($initialStorageAreas ?? []),
            initialMovementTypes: @json($initialMovementTypes ?? []),
            csrfToken: @json(csrf_token()),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stock-in.js')
@endpush

@section('content')
<div class="rfq-workspace-container" id="grnAppRoot">

    <!-- Global Pessimistic UI Locking Spinner Overlay -->
    <div class="rfq-lock-overlay" id="grnGlobalLockOverlay" style="display: none;" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div style="font-weight: 700; font-size: 1.05rem;" id="grnLockMessage">Processing Intake & Ledger Commit...</div>
        <div style="font-size: 0.82rem; opacity: 0.8;">Applying atomic database locks to update PO fulfillment & write immutable stock ledger</div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="grnToastContainer"></div>

    <!-- 1. Page Header — HR-Style Flat Title Row (No Box Ribbon Container) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-tray-arrow-down"></i>
                    <span>Stock In / Receiving (GRN)</span>
                </h1>
                <p class="hr-parent-subtitle">Purchase Order Intake, Barcode Verification, Backlog Tracking & Physical Putaway</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Stocks Overview">
                    <i class="ph ph-squares-four"></i>
                    <span>Stocks Overview</span>
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-folder-simple"></i>
                    <span>Item Master</span>
                </a>
                <a href="{{ route('purchase.purchase-orders') }}" class="hr-btn hr-btn-secondary" title="View Purchase Orders Directory">
                    <i class="ph ph-receipt"></i>
                    <span>Purchase Orders</span>
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="startDirectBypassIntake()" title="Receive Direct Inbound Spot Stock without a PO">
                    <i class="ph ph-plus-circle"></i>
                    <span>Direct Inbound</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Segmented Nav Tabs Bar (RFQ Design System) -->
    <nav class="rfq-nav-tabs-bar" id="grnTabsBar">
        <button type="button" class="rfq-tab-btn active" id="tabBtnOrdersQueue" onclick="switchGrnMode('queue')">
            <i class="ph ph-list-dashes"></i>
            <span>Incoming Orders Queue</span>
            <span class="rfq-tab-count" id="badgeQueueCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="tabBtnIntakeBuilder" onclick="switchGrnMode('builder')">
            <i class="ph ph-barcode"></i>
            <span id="tabBuilderTitle">Stock Intake Sheet (GRN)</span>
            <span class="rfq-tab-badge" id="badgeIntakeStatus">New Intake</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: INCOMING ORDERS QUEUE (PO TRACKER & BACKLOG MONITOR)
         ===================================================================== -->
    <div class="rfq-tab-pane active" id="panelOrdersQueue">
        <!-- KPI Metrics Grid (Uniform Border & Styling across all 4 Cards) -->
        <div class="rfq-kpi-grid">
            <div class="rfq-kpi-card" data-kpi-filter="ALL" onclick="filterQueueByStatus('ALL')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Pending Intake Orders</span>
                    <div class="rfq-kpi-icon purple"><i class="ph ph-clock-countdown"></i></div>
                </div>
                <div class="rfq-kpi-val" id="grnKpiPendingOrders">0 Ready</div>
                <div class="rfq-kpi-sub">Approved POs ready for dock intake</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="FULFILLED" onclick="filterQueueByStatus('FULFILLED')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Units Received Today</span>
                    <div class="rfq-kpi-icon emerald"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="rfq-kpi-val" id="grnKpiUnitsToday">0.00</div>
                <div class="rfq-kpi-sub">Total physical units verified at dock</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="PARTIAL" onclick="filterQueueByStatus('PARTIAL')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Active Backorders</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-arrows-split"></i></div>
                </div>
                <div class="rfq-kpi-val" id="grnKpiActiveBackorders">0 Orders</div>
                <div class="rfq-kpi-sub">Partially delivered POs with pending balances</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="READY" onclick="filterQueueByStatus('READY')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Receiving Facilities</span>
                    <div class="rfq-kpi-icon draft"><i class="ph ph-warehouse"></i></div>
                </div>
                <div class="rfq-kpi-val" id="grnKpiHubsCount">3 Facilities</div>
                <div class="rfq-kpi-sub" id="grnKpiHubsList">WH-MAIN · WH-COLD · WH-BGC</div>
            </div>
        </div>

        <!-- RFQ Panel Card for Incoming Orders Table -->
        <div class="rfq-panel-card" style="overflow: visible;">
            <!-- Clean Single-Row Filter & Search Toolbar (RFQ & PO Directory Parity) -->
            <div class="rfq-items-toolbar grn-queue-toolbar">
                <div class="rfq-toolbar-left grn-queue-toolbar-left">
                    <div class="grn-queue-search-wrap">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" class="rfq-input" id="queueSearchInput" placeholder="Search PO #, vendor partner, warehouse, or SKU... (/)" oninput="onQueueSearchInput(this.value)">
                    </div>

                    <select id="queueWarehouseFilter" class="rfq-select grn-queue-filter-select" onchange="onQueueWarehouseFilterChange(this.value)">
                        <option value="ALL">All Receiving Hubs</option>
                    </select>

                    <select id="queueStatusFilter" class="rfq-select grn-queue-filter-select" onchange="onQueueStatusFilterChange(this.value)">
                        <option value="ALL">All Delivery Statuses</option>
                        <option value="READY">Ready to Receive</option>
                        <option value="PARTIAL">Partially Received (Backlog)</option>
                        <option value="FULFILLED">Fully Received</option>
                    </select>

                    <div id="queueActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
                </div>

                <div class="rfq-toolbar-right grn-queue-toolbar-right">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnQueueColConfig" onclick="toggleQueueTableSettingsDropdown(event)" title="Customize visible columns & order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-gear-six"></i>
                        <span>Columns</span>
                        <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="initStockInModule()" title="Refresh Incoming Orders" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-arrows-clockwise"></i>
                        <span>Refresh</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startDirectBypassIntake()" style="padding: 5px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i>
                        <span>Direct Inbound</span>
                    </button>
                </div>
            </div>

            <!-- RFQ Responsive Table with Stacked Headers & Gear Settings Button -->
            <div class="rfq-table-responsive" style="position: relative; overflow-x: auto;">
                <table class="rfq-table" id="queueTable">
                    <thead id="queueTableThead">
                        <tr id="queueTableTheadRow">
                            <!-- Rendered dynamically by renderQueueTableHeader() with rfq-th-settings-btn gear icon -->
                        </tr>
                    </thead>
                    <tbody id="queueTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>

                <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear (RFQ Directory Parity) -->
                <div id="queueTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display: none;" onclick="event.stopPropagation()">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Table Columns & Sequence</span>
                            </div>
                            <span class="rfq-col-badge-default" id="queueColActiveCounter">10 of 13 visible</span>
                        </div>
                        <div class="rfq-settings-links-row">
                            <button type="button" class="rfq-settings-link-btn" onclick="resetQueueColumnDefaults()">Reset to Default</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="showAllQueueColumns()">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetQueueColumnWidths()">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="queueColSearchInput" placeholder="Filter columns..." oninput="handleQueueColSearch(this.value)">
                    </div>
                    <div class="rfq-settings-col-list" id="queueColCheckboxesList">
                        <!-- Populated dynamically with draggable items -->
                    </div>
                </div>
            </div>

            <!-- RFQ Pagination Bar -->
            <div class="rfq-pagination-bar" id="queuePaginationBar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 12px; color: #64748b;" id="queuePaginationInfo">
                    Showing <strong id="queueCountVisible" style="color: #0f172a;">0</strong> of <strong id="queueCountTotal" style="color: #0f172a;">0</strong> incoming purchase orders
                </div>
                <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-info" style="color: #9333ea;"></i>
                    <span>Click any PO Reference or <strong>Receive Intake</strong> to open in the Phase 3 Stock Intake Sheet</span>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: STOCK INTAKE SHEET (GRN) — PURCHASE ORDER BUILDER (PHASE 3) LAYOUT
         (Stock-Focused Physical Intake: Cost & Amount Hidden in Gear Menu by Default)
         ===================================================================== -->
    <div class="po-tab-pane rfq-tab-pane grn-builder" id="panelIntakeBuilder" style="display: none;">

        <!-- ZONE 1: Live Inbound Route Pipeline + Read-only Document Status -->
        <div class="po-lifecycle-bar-container rfq-lifecycle-bar-container stage-theme-issued grn-zone1" id="grnLifecycleStepperWrap">
            <div class="grn-zone1-status">
                <span class="grn-status-chip" id="grnStatusChip" data-state="draft">
                    <i class="ph ph-circle-dashed" aria-hidden="true"></i>
                    <span id="grnStatusChipText">Draft · Not Posted</span>
                </span>
                <span class="grn-draft-indicator" id="grnDraftIndicator" hidden>
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    <span id="grnDraftIndicatorText">Draft saved</span>
                </span>
            </div>

            <!-- Receiving -> (QC if route has inspection) -> Storage -> Status; rendered by renderGrnChevronPipeline() -->
            <div class="po-chevron-pipeline rfq-chevron-pipeline" id="grnLifecycleProgressBar" role="list" aria-label="Inbound route stages"></div>
        </div>

        <!-- ZONE 2: Identity Ribbon + Operational Form Grid -->
        <div class="po-panel-card rfq-panel-card grn-header-card" id="grnTopBannerCard">
            <!-- Identity ribbon: GRN ref · single PO pill (selector + open link) · mode -->
            <div class="grn-ribbon">
                <div class="grn-ribbon-left">
                    <div class="grn-ref-pill">
                        <i class="ph ph-tray-arrow-down" aria-hidden="true"></i>
                        <span class="grn-ref-label">GRN</span>
                        <span id="intakeDocNoDisplay" class="grn-ref-value">GRN-DIRECT</span>
                        <button type="button" class="grn-icon-btn" onclick="copyGrnReference()" title="Copy GRN reference" aria-label="Copy GRN reference">
                            <i class="ph ph-copy" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="grn-po-pill">
                        <i class="ph ph-receipt" aria-hidden="true"></i>
                        <label class="grn-ref-label" for="intakePoSelect">PO</label>
                        <select id="intakePoSelect" class="grn-po-select" onchange="onIntakePoSelected(this.value)">
                            <option value="">Direct Inbound (Bypass PO)</option>
                        </select>
                        <button type="button" class="grn-icon-btn" id="grnOpenPoBtn" onclick="openActivePoInPurchaseModule()" title="Open in Purchase Order module" aria-label="Open linked purchase order in Purchase Order module">
                            <i class="ph ph-arrow-square-out" aria-hidden="true"></i>
                        </button>
                    </div>

                    <span class="status-pill sent grn-mode-pill" id="intakeModeBadge">
                        <i class="ph ph-dot" aria-hidden="true"></i>
                        <span id="intakeModeText">Direct Spot Inbound</span>
                    </span>
                </div>
                <div class="grn-ribbon-right">
                    <span class="grn-hint-text"><kbd>F2</kbd> Scan · <kbd>F8</kbd> Preview · <kbd>F10</kbd> Confirm</span>
                </div>
            </div>

            <!-- Operational grid: Row 1 Vendor · Movement Type · ETA · Arrival | Row 2 Route (span 2) · DR # · Notes -->
            <div class="grn-form-grid">
                <div class="grn-field">
                    <label class="grn-label" for="intakeVendorSelect">
                        <span>Vendor / Supplier <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge">Masterlist</span>
                    </label>
                    <select id="intakeVendorSelect" class="grn-control" onchange="onIntakeVendorChanged()" required></select>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="intakeMovementTypeSelect">
                        <span>Inbound Movement Type <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge is-accent" id="routePresetBadge">Route</span>
                    </label>
                    <select id="intakeMovementTypeSelect" class="grn-control" onchange="onIntakeMovementTypeChanged(this.value)" required></select>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="intakeExpectedDelivery">
                        <span>Expected Delivery (ETA)</span>
                        <span class="grn-label-badge">Scheduled</span>
                    </label>
                    <input type="date" id="intakeExpectedDelivery" class="grn-control" onchange="onIntakeHeaderChanged()">
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="intakeReceivedAt">
                        <span>Arrival Date &amp; Time <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge" id="grnTimelinessBadge" data-state="none">Dock Stamp</span>
                    </label>
                    <input type="datetime-local" id="intakeReceivedAt" class="grn-control" value="{{ now()->format('Y-m-d\TH:i') }}" onchange="onIntakeHeaderChanged()" required>
                </div>

                <div class="grn-field grn-field-span-2">
                    <span class="grn-label" id="grnRouteLabel">
                        <span>Route: Source <i class="ph ph-arrow-right" aria-hidden="true"></i> Destination <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge is-success" id="grnRouteStageBadge">Storage</span>
                    </span>
                    <div class="grn-route-field" role="group" aria-labelledby="grnRouteLabel">
                        <select id="intakeSourceLocationSelect" class="grn-control grn-route-src" aria-label="Source location" onchange="onIntakeSourceLocationChanged(this.value)"></select>
                        <span class="grn-route-arrow" aria-hidden="true"><i class="ph ph-arrow-right"></i></span>
                        <select id="intakeDestinationLocationSelect" class="grn-control grn-route-dest" aria-label="Destination location" onchange="onIntakeDestinationLocationChanged(this.value)"></select>
                    </div>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="intakeDeliverySlip">
                        <span>Delivery Slip (DR #)</span>
                        <span class="grn-label-badge">Logistics</span>
                    </label>
                    <input type="text" id="intakeDeliverySlip" class="grn-control" maxlength="100" placeholder="e.g. DR-2026-9921" oninput="onIntakeHeaderChanged()">
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="intakeDeliveryNotes">
                        <span>Receiving Notes</span>
                        <span class="grn-label-badge">Optional</span>
                    </label>
                    <input type="text" id="intakeDeliveryNotes" class="grn-control" maxlength="300" placeholder="e.g. Lalamove 4W, seals intact" oninput="onIntakeHeaderChanged()">
                </div>
            </div>

            <!-- Hidden compatibility inputs -->
            <input type="hidden" id="intakeWarehouseSelect" value="WH-MAIN">
            <input type="hidden" id="intakeCarrier" value="">
            <input type="hidden" id="intakeReceiverName" value="{{ auth()->user()->name ?? 'Warehouse Supervisor' }}">
        </div>

        <!-- ZONE 3: Items Panel (full width on <1440px) + GRN Mockup (sticky column / drawer) -->
        <div class="po-builder-split-body rfq-builder-split-body grn-split-body" id="grnBuilderSplitBody">

            <div class="po-items-panel-col rfq-items-panel-col">
                <div class="po-items-panel rfq-items-panel">
                    <!-- Items toolbar: Scan bar (primary focus) · counter · unplanned item · overflow -->
                    <div class="grn-items-toolbar">
                        <div class="grn-scan-bar">
                            <i class="ph ph-barcode" aria-hidden="true"></i>
                            <label for="grnScanInput" class="sr-only">Scan barcode or type SKU</label>
                            <input type="text" id="grnScanInput" class="grn-scan-input" autocomplete="off" spellcheck="false" placeholder="Scan barcode or type SKU, then Enter…" onkeydown="onGrnScanKeydown(event)">
                            <kbd class="grn-kbd">F2</kbd>
                        </div>

                        <div class="grn-items-toolbar-right">
                            <span class="po-items-counter-pill rfq-items-counter-pill" id="intakeItemCountBadge">0 Line Items</span>
                            <button type="button" class="grn-btn grn-btn-soft" onclick="addAdHocIntakeRow()" title="Add an item that is not on the PO">
                                <i class="ph ph-plus-circle" aria-hidden="true"></i>
                                <span>Unplanned Item</span>
                            </button>
                            <button type="button" class="grn-btn grn-btn-ghost grn-mockup-toggle" id="grnMockupToggleBtn" onclick="toggleGrnMockupDrawer()" aria-controls="grnMockupPanelCol" aria-expanded="false" title="Show GRN document panel">
                                <i class="ph ph-file-text" aria-hidden="true"></i>
                                <span>GRN Panel</span>
                            </button>
                            <div class="grn-overflow">
                                <button type="button" class="grn-icon-btn grn-icon-btn-lg" id="grnOverflowBtn" onclick="toggleGrnOverflowMenu(event)" aria-haspopup="menu" aria-expanded="false" aria-label="More actions">
                                    <i class="ph ph-dots-three-vertical" aria-hidden="true"></i>
                                </button>
                                <div class="grn-overflow-menu" id="grnOverflowMenu" role="menu" hidden onclick="event.stopPropagation()">
                                    <button type="button" role="menuitem" onclick="openGrnDocumentPreview(); toggleGrnOverflowMenu();">
                                        <i class="ph ph-printer" aria-hidden="true"></i> Print preview <kbd>F8</kbd>
                                    </button>
                                    <button type="button" role="menuitem" onclick="discardGrnDraft()">
                                        <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i> Discard saved draft
                                    </button>
                                    <button type="button" role="menuitem" class="is-danger" onclick="confirmClearIntakeSheet()">
                                        <i class="ph ph-trash" aria-hidden="true"></i> Clear all lines…
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table viewport with gear column settings (cost hidden by default) -->
                    <div class="po-table-responsive rfq-table-responsive grn-table-viewport" id="grnTableContainer">
                        <table class="po-table rfq-table grn-items-table" id="intakeTable">
                            <thead id="intakeTableThead">
                                <tr id="intakeItemsTheadRow"></tr>
                            </thead>
                            <tbody id="intakeTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Compact footer: metrics · progress · single primary CTA -->
                    <div class="grn-footer">
                        <div class="grn-footer-metrics">
                            <div class="grn-metric">
                                <span class="grn-metric-label">Lines</span>
                                <span class="grn-metric-val" id="summaryGrnLines">0</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Ordered</span>
                                <span class="grn-metric-val" id="sumOrderedUnits">0.00</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Receiving Now</span>
                                <span class="grn-metric-val is-success" id="sumReceivedNowUnits">0.00</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Backlog</span>
                                <span class="grn-metric-val is-warning" id="sumBalanceUnits">0.00</span>
                            </div>
                            <div class="grn-metric" id="grnOverReceiptMetric" hidden>
                                <span class="grn-metric-label">Over-receipt</span>
                                <span class="grn-metric-val is-danger" id="sumOverUnits">0.00</span>
                            </div>
                            <div class="grn-metric" id="grnOptionalCostSummaryRow" hidden>
                                <span class="grn-metric-label">Valuation</span>
                                <span class="grn-metric-val is-accent" id="sumIntakeValuation">₱ 0.00</span>
                            </div>
                        </div>

                        <div class="grn-footer-progress">
                            <div class="grn-progress-head">
                                <span>Fulfillment</span>
                                <strong id="fulfillmentRateText">0.0%</strong>
                            </div>
                            <div class="grn-progress-track" role="progressbar" aria-label="PO fulfillment rate" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" id="fulfillmentProgressTrack">
                                <div class="grn-progress-fill" id="fulfillmentProgressBar"></div>
                            </div>
                        </div>

                        <button type="button" class="grn-btn grn-btn-primary grn-btn-cta" id="btnConfirmIntake" onclick="confirmAndCommitStockIntake()" title="Confirm receipt & post to SYS_LEDGER (F10)">
                            <i class="ph ph-check-circle" aria-hidden="true"></i>
                            <span>Confirm GRN</span>
                            <kbd class="grn-kbd grn-kbd-contrast">F10</kbd>
                        </button>
                    </div>
                </div>
            </div>

            <!-- GRN Document Mockup (sticky column ≥1440px, slide-in drawer below) -->
            <aside class="po-mockup-panel-col rfq-mockup-panel-col grn-mockup-col" id="grnMockupPanelCol" aria-label="Goods receipt document preview">
                <div class="po-panel-card po-mockup-card rfq-panel-card rfq-mockup-card">
                    <div class="grn-mockup-head">
                        <div class="grn-mockup-title">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span>Goods Receipt (GRN)</span>
                            <span class="grn-mockup-pages" id="grnMockupPageCountBadge">1 Page</span>
                            <span class="grn-live-dot" aria-hidden="true"></span>
                            <span class="grn-live-text">Live</span>
                        </div>
                        <div class="grn-mockup-actions">
                            <button type="button" class="grn-btn grn-btn-ghost" onclick="openGrnDocumentPreview()" title="Print preview (F8)">
                                <i class="ph ph-printer" aria-hidden="true"></i>
                                <span>Preview</span>
                                <kbd class="grn-kbd">F8</kbd>
                            </button>
                            <button type="button" class="grn-icon-btn grn-icon-btn-lg grn-mockup-close" onclick="toggleGrnMockupDrawer(false)" aria-label="Close GRN panel">
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="po-mockup-content-wrap rfq-mockup-content-wrap" id="grnMockupContentWrap">
                        <div class="po-live-mockup-container rfq-live-mockup-container" id="grnLiveMockupSheet"></div>
                    </div>
                </div>
            </aside>
            <div class="grn-drawer-backdrop" id="grnDrawerBackdrop" onclick="toggleGrnMockupDrawer(false)" hidden></div>

        </div>
    </div><!-- /#panelIntakeBuilder -->

    <!-- =====================================================================
         POPUP MODAL: LOT NUMBER & EXPIRY SPECIFICATION
         ===================================================================== -->
    <div id="grnLotExpiryModal" class="grn-modal-overlay" style="display: none;" onclick="closeLotExpiryModalOnBackdrop(event)">
        <div class="grn-modal-card" role="dialog" aria-modal="true" aria-labelledby="lotModalHeading" aria-describedby="lotModalItemTitle" onclick="event.stopPropagation()" onkeydown="onLotModalKeydown(event)">
            <div class="grn-modal-head">
                <div class="grn-modal-head-left">
                    <div class="grn-modal-icon"><i class="ph ph-calendar-plus" aria-hidden="true"></i></div>
                    <div>
                        <h3 class="grn-modal-title" id="lotModalHeading">Lot &amp; Expiry Specification</h3>
                        <div class="grn-modal-sub" id="lotModalItemTitle">SKU • Item Name</div>
                    </div>
                </div>
                <button type="button" class="grn-icon-btn grn-icon-btn-lg" onclick="closeLotExpiryModal()" aria-label="Close dialog">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="grn-modal-body">
                <input type="hidden" id="lotModalRowIdx" value="">
                <div class="grn-field">
                    <div class="grn-label">
                        <label for="lotModalLotNumber">Batch / Lot Number <span class="grn-req" aria-hidden="true">*</span></label>
                        <button type="button" class="grn-btn grn-btn-ghost grn-btn-xs" onclick="generateRandomLotNumber()">
                            <i class="ph ph-magic-wand" aria-hidden="true"></i> Auto Generate
                        </button>
                    </div>
                    <input type="text" id="lotModalLotNumber" class="grn-control grn-mono" maxlength="100" placeholder="e.g. LOT-261101-01">
                </div>

                <div class="grn-modal-grid-2">
                    <div class="grn-field">
                        <label class="grn-label" for="lotModalExpiryDate"><span>Expiry Date</span></label>
                        <input type="date" id="lotModalExpiryDate" class="grn-control">
                    </div>
                    <div class="grn-field">
                        <label class="grn-label" for="lotModalMfgDate"><span>Production / Mfg Date</span></label>
                        <input type="date" id="lotModalMfgDate" class="grn-control">
                    </div>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="lotModalNotes"><span>Quality &amp; Storage Notes</span></label>
                    <textarea id="lotModalNotes" class="grn-control grn-textarea" rows="2" maxlength="180" placeholder="e.g. Seal verified, temp probe 2.8°C, grade A"></textarea>
                </div>
                <div class="grn-modal-error" id="lotModalError" role="alert" hidden></div>
            </div>
            <div class="grn-modal-foot">
                <button type="button" class="grn-btn grn-btn-ghost" onclick="closeLotExpiryModal()">Cancel</button>
                <button type="button" class="grn-btn grn-btn-primary" onclick="saveLotExpiryModal()">
                    <i class="ph ph-check-circle" aria-hidden="true"></i> Save Details
                </button>
            </div>
        </div>
    </div>

    <!-- Draft resume banner (Frontend Rule 16) -->
    <div class="grn-resume-banner" id="grnResumeBanner" role="status" hidden>
        <i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i>
        <span id="grnResumeBannerText">Unsaved intake draft found.</span>
        <button type="button" class="grn-btn grn-btn-primary grn-btn-xs" onclick="resumeGrnDraft()">Resume</button>
        <button type="button" class="grn-btn grn-btn-ghost grn-btn-xs" onclick="discardGrnDraft()">Discard</button>
    </div>

</div>
@endsection

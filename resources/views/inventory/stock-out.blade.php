@extends('layouts.app')

@section('title', 'Stock Out / Usage - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/stock-out.css')
@endpush

@push('blade-data')
<script>
    window.StockOutConfig = Object.freeze({
        routes: {
            createStockOutDraft: @json(route('inventory.api.create-stock-out-draft')),
            updateStockOutPack: @json(route('inventory.api.update-stock-out-pack')),
            confirmShipStockOut: @json(route('inventory.api.confirm-ship-stock-out')),
            stocksOverview: @json(route('inventory.stocks-overview')),
            stockOutData: @json(route('inventory.api.stock-out-data')),
            warehousesPage: @json(route('inventory.warehouses')),
            movementFlowsPage: @json(route('inventory.movement-flows')),
        },
        company: {
            name: @json($companyName ?? config('app.name', 'RMS')),
        },
        user: {
            name: @json(auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? 'Warehouse Lead'),
            email: @json(auth()->user()->email ?? 'warehouse@rms.ph'),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialCategories: @json($initialCategories ?? []),
            initialWarehouses: @json($initialWarehouses ?? []),
            initialStorageAreas: @json($initialStorageAreas ?? []),
            initialMovementTypes: @json($initialMovementTypes ?? []),
            csrfToken: @json(csrf_token()),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/stock-out.js')
@endpush

@section('content')
<div class="rfq-workspace-container out-workspace" id="outAppRoot">

    <!-- Global Pessimistic UI Locking Spinner Overlay -->
    <div class="rfq-lock-overlay" id="outGlobalLockOverlay" style="display: none;" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div style="font-weight: 700; font-size: 1.05rem;" id="outLockMessage">Processing Outbound Dispatch &amp; Ledger Commit...</div>
        <div style="font-size: 0.82rem; opacity: 0.8;">Applying atomic database locks to deduct on-hand balances &amp; append immutable stock ledger</div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="outToastContainer"></div>

    <!-- 1. Page Header — Flat Title Row (Stock In / RFQ Design System) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrow-up-right"></i>
                    <span>Stock Out / Usage</span>
                </h1>
                <p class="hr-parent-subtitle">Kitchen Requisitions, Inter-Branch Transfers, Vendor RMAs, Disposal Write-Offs &amp; Physical Pick-Pack Verification</p>
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
                <button type="button" class="hr-btn hr-btn-primary" onclick="startDirectOutboundMode()" title="Direct 1-Step Stock Issue & Ledger Deduction">
                    <i class="ph ph-plus-circle"></i>
                    <span>Direct Outbound</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Segmented Nav Tabs Bar (2 Tabs — Exact Stock In Parity) -->
    <nav class="rfq-nav-tabs-bar" id="stockOutTabsBar">
        <button type="button" class="rfq-tab-btn tab-btn active" id="tabBtnList" onclick="switchOutTab('tab-out-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Dispatches Order Queue</span>
            <span class="rfq-tab-count" id="badgeListCount">{{ ($initialOrders ?? collect())->count() }}</span>
        </button>
        <button type="button" class="rfq-tab-btn tab-btn" id="tabBtnPickPackShip" onclick="switchOutTab('tab-pick-pack-ship')">
            <i class="ph ph-barcode"></i>
            <span id="tabBuilderTitle">Stock Out / Usage Sheet</span>
            <span class="rfq-tab-badge" id="badgePendingPickCount" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">
                {{ ($initialOrders ?? collect())->whereIn('status', ['DRAFT', 'PICKING', 'PACKED'])->count() > 0 ? ($initialOrders ?? collect())->whereIn('status', ['DRAFT', 'PICKING', 'PACKED'])->count() . ' Active' : 'New Requisition' }}
            </span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: LIST OF DISPATCHES (QUEUE & BACKLOG MONITOR)
         ===================================================================== -->
    <div class="rfq-tab-pane out-tab-panel active" id="tab-out-list">
        <!-- 4-Card KPI Metrics Grid -->
        <div class="rfq-kpi-grid">
            <div class="rfq-kpi-card" data-kpi-filter="DRAFT" onclick="filterOrdersByKpi('DRAFT')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Draft Requisitions</span>
                    <div class="rfq-kpi-icon draft"><i class="ph ph-file-text"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiDraftCount">0</div>
                <div class="rfq-kpi-sub">Queued kitchen &amp; transfer requests</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="PICKING" onclick="filterOrdersByKpi('PICKING')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">In Picking / Packed</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-package"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiPickingCount">0</div>
                <div class="rfq-kpi-sub">Active orders being staged at dock</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="SHIPPED" onclick="filterOrdersByKpi('SHIPPED')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Dispatched / Shipped</span>
                    <div class="rfq-kpi-icon emerald"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiShippedCount">0</div>
                <div class="rfq-kpi-sub">Committed to SYS_LEDGER stock out</div>
            </div>

            <div class="rfq-kpi-card" data-kpi-filter="ALL" onclick="filterOrdersByKpi('ALL')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Dispatched Valuation</span>
                    <div class="rfq-kpi-icon purple"><i class="ph ph-currency-circle-dollar"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiValuation">₱0.00</div>
                <div class="rfq-kpi-sub">Cumulative shipped usage value</div>
            </div>
        </div>

        <!-- RFQ Panel Card for Dispatches Queue -->
        <div class="rfq-panel-card" style="overflow: visible;">
            <div class="rfq-items-toolbar grn-queue-toolbar">
                <div class="rfq-toolbar-left grn-queue-toolbar-left">
                    <div class="grn-queue-search-wrap">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" class="rfq-input" id="orderSearchInput" placeholder="Search SO #, requester, destination, or SKU... (/)" oninput="filterOrdersList()" autocomplete="off">
                    </div>

                    <select id="warehouseFilter" class="rfq-select grn-queue-filter-select" onchange="filterOrdersList()" title="Filter by Source Warehouse">
                        <option value="ALL">All Source Warehouses</option>
                    </select>

                    <select id="typeFilter" class="rfq-select grn-queue-filter-select" onchange="filterOrdersList()" title="Filter by Usage Purpose">
                        <option value="ALL">All Usage Types</option>
                        <option value="KITCHEN_USAGE">Kitchen Operations Usage</option>
                        <option value="BRANCH_TRANSFER">Inter-Branch Transfer</option>
                        <option value="VENDOR_RETURN">Vendor Return (RMA)</option>
                        <option value="SPOILAGE_DISPOSAL">Spoilage / Disposal</option>
                        <option value="STAFF_MEALS">Staff Meals</option>
                        <option value="SAMPLE_TASTING">Sample Tasting</option>
                    </select>

                    <select id="statusFilter" class="rfq-select grn-queue-filter-select" onchange="filterOrdersList()" title="Filter by Fulfillment Status">
                        <option value="ALL">All Dispatch Statuses</option>
                        <option value="DRAFT">Draft Requisitions</option>
                        <option value="PICKING">In Picking</option>
                        <option value="PACKED">Packed &amp; Ready</option>
                        <option value="SHIPPED">Dispatched / Shipped</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <div id="outQueueActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: nowrap;"></div>
                </div>

                <div class="rfq-toolbar-right grn-queue-toolbar-right">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnOutQueueColConfig" onclick="toggleOutQueueSettingsDropdown(event)" title="Customize visible columns &amp; order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-gear-six"></i>
                        <span>Columns</span>
                        <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fetchLiveStockOutData()" title="Refresh Live Dispatches" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-arrows-clockwise"></i>
                        <span>Refresh</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startNewOutRequisition()" style="padding: 5px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i>
                        <span>New Requisition</span>
                    </button>
                </div>
            </div>

            <!-- Responsive Table with Stacked Headers & Gear Settings Button -->
            <div class="rfq-table-responsive" style="position: relative; overflow-x: auto;">
                <table class="rfq-table out-table" id="ordersMasterTable">
                    <thead id="ordersTableThead">
                        <tr id="ordersTableTheadRow"></tr>
                    </thead>
                    <tbody id="ordersTableBody"></tbody>
                </table>

                <!-- Floating Column Settings Dropdown Menu -->
                <div id="outQueueTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display: none;" onclick="event.stopPropagation()">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Table Columns &amp; Sequence</span>
                            </div>
                            <span class="rfq-col-badge-default" id="outQueueColActiveCounter">9 of 12 visible</span>
                        </div>
                        <div class="rfq-settings-links-row">
                            <button type="button" class="rfq-settings-link-btn" onclick="resetOutQueueColumnDefaults()">Reset to Default</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="showAllOutQueueColumns()">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetOutQueueColumnWidths()">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="outQueueColSearchInput" placeholder="Filter columns..." oninput="handleOutQueueColSearch(this.value)">
                    </div>
                    <div class="rfq-settings-col-list" id="outQueueColCheckboxesList"></div>
                </div>
            </div>

            <div id="noOrdersPrompt" style="display: none;"></div>

            <!-- Pagination & Hint Strip -->
            <div class="rfq-pagination-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 12px; color: #64748b;">
                    Showing <strong id="outQueueCountVisible" style="color: #0f172a;">0</strong> of <strong id="outQueueCountTotal" style="color: #0f172a;">0</strong> stock-out orders
                </div>
                <div style="font-size: 11.5px; color: #64748b; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-info" style="color: #9333ea;"></i>
                    <span>Click any <strong>SO #</strong> for details or <strong>Pick / Pack</strong> to stage and confirm stock deduction</span>
                </div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: UNIFIED STOCK OUT & USAGE BUILDER + PICK PACK SHIP WORKSPACE
         (Matches Stock In Phase 3 GRN Builder Architecture)
         ===================================================================== -->
    <div class="po-tab-pane rfq-tab-pane grn-builder out-tab-panel" id="tab-pick-pack-ship" style="display: none;">

        <!-- ZONE 1: Live Outbound Route Pipeline + Document Status Chip -->
        <div class="po-lifecycle-bar-container rfq-lifecycle-bar-container stage-theme-issued grn-zone1" id="outLifecycleStepperWrap">
            <div class="grn-zone1-status">
                <span class="grn-status-chip" id="outStatusChip" data-state="draft">
                    <i class="ph ph-circle-dashed" aria-hidden="true"></i>
                    <span id="outStatusChipText">Draft · Not Dispatched</span>
                </span>
                <span class="grn-draft-indicator" id="outDraftIndicator" hidden>
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    <span id="outDraftIndicatorText">Draft autosaved</span>
                </span>
            </div>

            <!-- Rendered by renderOutChevronPipeline() -->
            <div class="po-chevron-pipeline rfq-chevron-pipeline" id="outLifecycleProgressBar" role="list" aria-label="Outbound route stages"></div>
        </div>

        <!-- ZONE 2: Identity Ribbon + Operational Form Grid -->
        <div class="po-panel-card rfq-panel-card grn-header-card" id="outTopBannerCard">
            <div class="grn-ribbon">
                <div class="grn-ribbon-left">
                    <div class="grn-ref-pill">
                        <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                        <span class="grn-ref-label">SO</span>
                        <span id="draftRefDisplay" class="grn-ref-value">SO-{{ date('Y') }}-NEW</span>
                        <button type="button" class="grn-icon-btn" onclick="copyOutReference()" title="Copy SO reference" aria-label="Copy SO reference">
                            <i class="ph ph-copy" aria-hidden="true"></i>
                        </button>
                    </div>

                    <div class="grn-po-pill">
                        <i class="ph ph-package" aria-hidden="true"></i>
                        <label class="grn-ref-label" for="pickOrderSelect">ORDER</label>
                        <select id="pickOrderSelect" class="grn-po-select" onchange="loadOrderIntoPickPack(this.value)">
                            <option value="">-- New Requisition / Direct Outbound --</option>
                            @foreach(($initialOrders ?? collect())->whereIn('status', ['DRAFT', 'PICKING', 'PACKED']) as $ord)
                                <option value="{{ $ord->id }}">
                                    {{ $ord->order_number }} [{{ $ord->status }}] - {{ $ord->destination_location }} ({{ $ord->total_items_count }} items)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <span class="status-pill sent grn-mode-pill" id="outModeBadge">
                        <i class="ph ph-dot" aria-hidden="true"></i>
                        <span id="outModeText">New Requisition Draft</span>
                    </span>
                    <span class="hr-badge hr-badge-neutral" id="partnerTypeBadge">Internal Station</span>
                    <span class="hr-badge hr-badge-neutral" id="pickActiveStatusBadge" style="display: none;">DRAFT</span>
                    <span id="pickActiveOrderNum" style="display: none;">NO ORDER SELECTED</span>
                    <span id="pickActiveDestDisplay" style="display: none;">-</span>
                    <span id="pickActiveReqDisplay" style="display: none;">-</span>
                    <span id="pickActiveTypeDisplay" style="display: none;">-</span>
                </div>
                <div class="grn-ribbon-right">
                    <span class="grn-hint-text"><kbd>F2</kbd> Scan · <kbd>F8</kbd> Preview · <kbd>F10</kbd> Confirm</span>
                </div>
            </div>

            <!-- 2-Row Operational Grid matching Stock In -->
            <div class="grn-form-grid">
                <div class="grn-field">
                    <label class="grn-label" for="draftOrderType">
                        <span>Usage / Reason Code <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge">Purpose</span>
                    </label>
                    <select id="draftOrderType" class="grn-control" onchange="handleOrderTypeChange(this.value)">
                        <option value="KITCHEN_USAGE">Kitchen Operations Line Usage</option>
                        <option value="BRANCH_TRANSFER">Inter-Branch Transfer Out</option>
                        <option value="VENDOR_RETURN">Vendor Return / Supplier RMA</option>
                        <option value="SPOILAGE_DISPOSAL">Spoilage / Disposal Write-Off</option>
                        <option value="STAFF_MEALS">Staff Meals &amp; Banquet</option>
                        <option value="SAMPLE_TASTING">Sample Tasting &amp; R&amp;D</option>
                    </select>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="outMovementTypeSelect">
                        <span>Outbound Movement Type <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge is-accent" id="outRoutePresetBadge">2-Step Route</span>
                    </label>
                    <select id="outMovementTypeSelect" class="grn-control" onchange="onOutMovementTypeChanged(this.value)"></select>
                </div>

                <div class="grn-field">
                    <!-- Dynamic Section A: Vendor Return -->
                    <div id="vendorReturnBlock" style="display: none; flex-direction: column; gap: 6px;">
                        <label class="grn-label" for="draftVendorSelect">
                            <span>Vendor Partner (RMA) <span class="grn-req" aria-hidden="true">*</span></span>
                            <span class="grn-label-badge is-accent">Masterlist</span>
                        </label>
                        <select id="draftVendorSelect" class="grn-control" onchange="handleVendorSelect(this.value)">
                            <option value="">-- Choose Vendor from Masterlist --</option>
                            @foreach(($initialVendors ?? []) as $v)
                                <option value="{{ $v->id }}" data-name="{{ $v->legal_name }}" data-trade="{{ $v->trade_name }}" data-contact="{{ $v->contact_person }}" data-phone="{{ $v->phone }}" data-email="{{ $v->email }}" data-address="{{ $v->address }}">
                                    {{ $v->vendor_code }} - {{ $v->legal_name }} ({{ $v->category }})
                                </option>
                            @endforeach
                        </select>
                        <div class="vendor-pill-box" id="vendorPillBox" style="display: none;">
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 6px;">
                                <strong id="vPillName" style="color: #701a75; font-size: 11.5px;">Vendor Name</strong>
                                <span class="hr-badge hr-badge-purple" id="vPillTrade" style="font-size: 10px; padding: 1px 6px;">Partner</span>
                            </div>
                            <div style="display: flex; gap: 8px; flex-wrap: wrap; color: #475569; font-size: 10.5px;">
                                <span><i class="ph ph-user"></i> <span id="vPillContact">-</span></span>
                                <span><i class="ph ph-phone"></i> <span id="vPillPhone">-</span></span>
                            </div>
                            <div style="color: #64748b; font-size: 10.5px;"><i class="ph ph-map-pin"></i> <span id="vPillAddress">-</span></div>
                        </div>
                    </div>

                    <!-- Dynamic Section B: Branch Transfer -->
                    <div id="branchTransferBlock" style="display: none; flex-direction: column; gap: 6px;">
                        <label class="grn-label" for="draftBranchSelect">
                            <span>Destination Branch <span class="grn-req" aria-hidden="true">*</span></span>
                            <span class="grn-label-badge">Store Hub</span>
                        </label>
                        <select id="draftBranchSelect" class="grn-control" onchange="onOutHeaderChanged()">
                            <option value="Branch 1 - Makati Bistro Kitchen">Branch 1 - Makati Bistro Kitchen</option>
                            <option value="Branch 2 - BGC Bistro & Dining">Branch 2 - BGC Bistro &amp; Dining</option>
                            <option value="Branch 3 - Ortigas Commissary Hub">Branch 3 - Ortigas Commissary Hub</option>
                            <option value="Alabang Satellite Kiosk">Alabang Satellite Kiosk</option>
                        </select>
                    </div>

                    <!-- Dynamic Section C: Kitchen Prep Station (Default) -->
                    <div id="kitchenLineBlock" style="display: flex; flex-direction: column; gap: 6px;">
                        <label class="grn-label" for="draftKitchenStation">
                            <span>Kitchen Station / Dept <span class="grn-req" aria-hidden="true">*</span></span>
                            <span class="grn-label-badge">Operations</span>
                        </label>
                        <select id="draftKitchenStation" class="grn-control" onchange="onOutHeaderChanged()">
                            <option value="Main Kitchen - Hot Line (Saute & Grill)">Main Kitchen - Hot Line (Saute &amp; Grill)</option>
                            <option value="Cold Kitchen & Salad Pantry">Cold Kitchen &amp; Salad Pantry</option>
                            <option value="Bakery & Pastry Production">Bakery &amp; Pastry Production</option>
                            <option value="Bar & Craft Beverage Line">Bar &amp; Craft Beverage Line</option>
                            <option value="Central Commissary Prep Dock">Central Commissary Prep Dock</option>
                        </select>
                    </div>

                    <!-- Dynamic Section D: Spoilage / Disposal -->
                    <div id="disposalBlock" style="display: none; flex-direction: column; gap: 6px;">
                        <label class="grn-label" for="draftDisposalVault">
                            <span>Disposal Sink / Protocol <span class="grn-req" aria-hidden="true">*</span></span>
                            <span class="grn-label-badge">Write-Off</span>
                        </label>
                        <select id="draftDisposalVault" class="grn-control" onchange="onOutHeaderChanged()">
                            <option value="Condemned Meat & Perishables Freezer">Condemned Meat &amp; Perishables Freezer</option>
                            <option value="Kitchen Organic Compost Bin">Kitchen Organic Compost Bin</option>
                            <option value="Hazardous Waste Sanitation Vault">Hazardous Waste Sanitation Vault</option>
                        </select>
                    </div>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="draftRequiredAt">
                        <span>Required / Dispatch Stamp <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge" id="outScheduleBadge">Schedule</span>
                    </label>
                    <input type="datetime-local" id="draftRequiredAt" class="grn-control" value="{{ now()->format('Y-m-d\TH:i') }}" onchange="onOutHeaderChanged()">
                </div>

                <!-- Row 2: Route (span 2) + Requester/Priority + Picker/Ref -->
                <div class="grn-field grn-field-span-2">
                    <span class="grn-label" id="outRouteLabel">
                        <span>Route: Source Bin <i class="ph ph-arrow-right" aria-hidden="true"></i> Destination <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge is-success" id="outRouteStageBadge">Dispatch</span>
                    </span>
                    <div class="grn-route-field" role="group" aria-labelledby="outRouteLabel">
                        <select id="outSourceLocationSelect" class="grn-control grn-route-src" aria-label="Source bin location" onchange="onOutSourceLocationChanged(this.value)"></select>
                        <span class="grn-route-arrow" aria-hidden="true"><i class="ph ph-arrow-right"></i></span>
                        <select id="outDestinationLocationSelect" class="grn-control grn-route-dest" aria-label="Destination area" onchange="onOutDestinationLocationChanged(this.value)"></select>
                    </div>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="draftRequestedBy">
                        <span>Requester &amp; Priority <span class="grn-req" aria-hidden="true">*</span></span>
                        <span class="grn-label-badge">Custody</span>
                    </label>
                    <div class="out-inline-pair">
                        <input type="text" id="draftRequestedBy" class="grn-control" value="{{ auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? 'Executive Chef' }}" placeholder="Requester name" oninput="onOutHeaderChanged()">
                        <select id="draftPriority" class="grn-control out-compact-select" aria-label="Priority" onchange="onOutHeaderChanged()">
                            <option value="NORMAL">Normal</option>
                            <option value="URGENT">Urgent</option>
                            <option value="EMERGENCY">Emergency</option>
                        </select>
                    </div>
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="pickPickerName">
                        <span>Warehouse Picker &amp; Ref #</span>
                        <span class="grn-label-badge">Fulfillment</span>
                    </label>
                    <div class="out-inline-pair">
                        <input type="text" id="pickPickerName" class="grn-control" value="{{ auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? 'Warehouse Picker' }}" placeholder="Picker / Verifier" oninput="onOutHeaderChanged()">
                        <input type="text" id="draftReferenceNo" class="grn-control out-compact-ref" placeholder="Ticket / Ref #" oninput="onOutHeaderChanged()">
                    </div>
                </div>
            </div>

            <!-- Collapsible Optional Logistics & Notes Drawer (3-Question Input Guillotine) -->
            <details class="out-optional-details" id="outOptionalLogistics">
                <summary class="out-optional-summary">
                    <i class="ph ph-sliders"></i>
                    <span>Carrier / Waybill &amp; Special Handling Notes (Optional)</span>
                    <span class="out-optional-summary-chip" id="outOptionalSummaryChip">Default Logistics</span>
                </summary>
                <div class="out-optional-grid">
                    <div class="grn-field">
                        <label class="grn-label" for="pickCarrierName"><span>Carrier / Vehicle Plate</span></label>
                        <input type="text" id="pickCarrierName" class="grn-control" placeholder="e.g. Van B (NDF 8812)" oninput="onOutHeaderChanged()">
                    </div>
                    <div class="grn-field">
                        <label class="grn-label" for="pickWaybill"><span>Shipping Waybill / Slip #</span></label>
                        <input type="text" id="pickWaybill" class="grn-control" placeholder="e.g. WB-2026-9041" oninput="onOutHeaderChanged()">
                    </div>
                    <div class="grn-field">
                        <label class="grn-label" for="pickDispatchedAt"><span>Dispatch Stamp</span></label>
                        <input type="datetime-local" id="pickDispatchedAt" class="grn-control" value="{{ now()->format('Y-m-d\TH:i') }}" onchange="onOutHeaderChanged()">
                    </div>
                    <div class="grn-field">
                        <label class="grn-label" for="draftNotes"><span>Special Handling / Kitchen Notes</span></label>
                        <input type="text" id="draftNotes" class="grn-control" maxlength="300" placeholder="e.g. Keep chilled at -2°C, FIFO lot priority" oninput="onOutHeaderChanged()">
                    </div>
                </div>
            </details>
        </div>

        <!-- ZONE 3: Split Body (7fr Items Table + 3fr Sticky Live Issue Slip Mockup) -->
        <div class="po-builder-split-body rfq-builder-split-body grn-split-body" id="outBuilderSplitBody">

            <div class="po-items-panel-col rfq-items-panel-col">
                <div class="po-items-panel rfq-items-panel">
                    <!-- Items Toolbar: Barcode Scan (F2) + Catalog Quick-Pick + Match All + Overflow -->
                    <div class="grn-items-toolbar">
                        <div class="grn-scan-bar">
                            <i class="ph ph-barcode" aria-hidden="true"></i>
                            <label for="outScanInput" class="sr-only">Scan barcode or type SKU</label>
                            <input type="text" id="outScanInput" class="grn-scan-input" autocomplete="off" spellcheck="false" placeholder="Scan barcode or SKU, Enter to add / +1..." onkeydown="onOutScanKeydown(event)">
                            <kbd class="grn-kbd">F2</kbd>
                        </div>

                        <div class="out-catalog-quickpick">
                            <select id="draftCatalogPicker" class="grn-control out-catalog-select" aria-label="Quick pick from Item Master">
                                <option value="">-- Quick Pick from Item Master --</option>
                                @foreach(($initialProducts ?? []) as $p)
                                    <option value="{{ $p->sku }}" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-category="{{ $p->category }}" data-uom="{{ $p->uom }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->current_stock }}" data-location="{{ $p->storage_location }}">
                                        [{{ $p->sku }}] {{ $p->name }} — Avail: {{ number_format($p->current_stock, 2) }} {{ $p->uom }} (₱{{ number_format($p->cost_price, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" id="draftQuickQty" class="grn-control out-quick-qty" placeholder="Qty" min="0.01" step="0.01" value="1.00" aria-label="Quick add quantity">
                            <button type="button" class="grn-btn grn-btn-soft" onclick="addPickedCatalogItem()" title="Add selected product to Stock Out sheet">
                                <i class="ph ph-plus-circle" aria-hidden="true"></i>
                                <span>Add Item</span>
                            </button>
                        </div>

                        <div class="grn-items-toolbar-right">
                            <span class="po-items-counter-pill rfq-items-counter-pill" id="draftItemCountBadge">0 Line Items</span>
                            <span id="pickItemsCountBadge" style="display: none;">0 Items</span>
                            <button type="button" class="grn-btn grn-btn-ghost" onclick="autoMatchAllPackQuantities()" title="Auto-fill Pack Qty to 100% of Requested Qty">
                                <i class="ph ph-check-square-offset" aria-hidden="true"></i>
                                <span>100% Match</span>
                            </button>
                            <button type="button" class="grn-btn grn-btn-ghost grn-mockup-toggle" id="outMockupToggleBtn" onclick="toggleOutMockupDrawer()" aria-controls="outMockupPanelCol" aria-expanded="false" title="Show Stock Issue Slip panel">
                                <i class="ph ph-file-text" aria-hidden="true"></i>
                                <span>Issue Slip</span>
                            </button>
                            <div class="grn-overflow">
                                <button type="button" class="grn-icon-btn grn-icon-btn-lg" id="outOverflowBtn" onclick="toggleOutOverflowMenu(event)" aria-haspopup="menu" aria-expanded="false" aria-label="More actions">
                                    <i class="ph ph-dots-three-vertical" aria-hidden="true"></i>
                                </button>
                                <div class="grn-overflow-menu" id="outOverflowMenu" role="menu" hidden onclick="event.stopPropagation()">
                                    <button type="button" role="menuitem" onclick="openOutDocumentPreview(); toggleOutOverflowMenu();">
                                        <i class="ph ph-printer" aria-hidden="true"></i> Print preview <kbd>F8</kbd>
                                    </button>
                                    <button type="button" role="menuitem" onclick="clearAllPackQuantities(); toggleOutOverflowMenu();">
                                        <i class="ph ph-eraser" aria-hidden="true"></i> Zero out packed qty
                                    </button>
                                    <button type="button" role="menuitem" onclick="discardOutDraft(); toggleOutOverflowMenu();">
                                        <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i> Discard local draft
                                    </button>
                                    <button type="button" role="menuitem" class="is-danger" onclick="confirmClearOutSheet()">
                                        <i class="ph ph-trash" aria-hidden="true"></i> Reset / Clear sheet…
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unified Builder & Pick-Pack Table Viewport with Column Settings Gear -->
                    <div class="po-table-responsive rfq-table-responsive grn-table-viewport" id="outTableContainer">
                        <table class="po-table rfq-table grn-items-table out-table" id="outBuilderTable">
                            <thead id="outBuilderThead">
                                <tr id="outBuilderTheadRow"></tr>
                            </thead>
                            <tbody id="outBuilderTableBody"></tbody>
                        </table>
                        <div id="draftNoItemsPrompt" style="display: none;"></div>
                        <div id="pickNoItemsPrompt" style="display: none;"></div>
                    </div>

                    <!-- Compact Footer: Metrics + Fulfillment Progress + Action CTAs -->
                    <div class="grn-footer">
                        <div class="grn-footer-metrics">
                            <div class="grn-metric">
                                <span class="grn-metric-label">Lines</span>
                                <span class="grn-metric-val" id="summaryOutLines">0</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Requested</span>
                                <span class="grn-metric-val" id="draftTotalUnitsDisplay">0.00</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Issue Now</span>
                                <span class="grn-metric-val is-success" id="pickTotalPackedDisplay">0.00</span>
                            </div>
                            <div class="grn-metric">
                                <span class="grn-metric-label">Remaining</span>
                                <span class="grn-metric-val is-warning" id="sumOutBalanceUnits">0.00</span>
                            </div>
                            <div class="grn-metric" id="outOverIssueMetric" hidden>
                                <span class="grn-metric-label">Excess / Deficit</span>
                                <span class="grn-metric-val is-danger" id="sumOutOverUnits">0.00</span>
                            </div>
                            <div class="grn-metric" id="outOptionalCostSummaryRow">
                                <span class="grn-metric-label">Valuation</span>
                                <span class="grn-metric-val is-accent" id="pickTotalValueDisplay">₱0.00</span>
                            </div>
                            <span id="pickTotalReqDisplay" style="display: none;">0.00</span>
                            <span id="draftTotalValueDisplay" style="display: none;">₱0.00</span>
                        </div>

                        <div class="grn-footer-progress">
                            <div class="grn-progress-head">
                                <span>Fulfillment</span>
                                <strong id="pickGaugeBadge">0% Packed</strong>
                            </div>
                            <div class="grn-progress-track" role="progressbar" aria-label="Outbound fulfillment progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div class="grn-progress-fill" id="pickOverallProgressBar"></div>
                            </div>
                            <div id="pickFulfillmentAlert" class="out-fulfillment-alert-mini">
                                Ready to request or stage items for outbound ledger commit.
                            </div>
                        </div>

                        <div class="out-footer-cta-group">
                            <button type="button" class="grn-btn grn-btn-ghost" id="btnSaveDraftOrder" onclick="saveDraftOrder(false)" title="Save Requisition as Draft">
                                <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                                <span>Save Draft</span>
                            </button>
                            <button type="button" class="grn-btn grn-btn-ghost" id="btnSavePickProgress" onclick="savePickPackProgress()" style="display: none;" title="Save Pick & Pack staging progress">
                                <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                                <span>Save Progress</span>
                            </button>
                            <button type="button" class="grn-btn grn-btn-primary grn-btn-cta" id="btnConfirmOutPrimary" onclick="handlePrimaryOutboundAction()" title="Confirm outbound action (F10)">
                                <i class="ph ph-check-circle" aria-hidden="true"></i>
                                <span id="btnConfirmOutPrimaryLabel">Approve &amp; Move to Pick Pack Ship</span>
                                <kbd class="grn-kbd grn-kbd-contrast">F10</kbd>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Live Outbound Issue / Packing Slip Mockup (sticky column >=1440px, drawer below) -->
            <aside class="po-mockup-panel-col rfq-mockup-panel-col grn-mockup-col" id="outMockupPanelCol" aria-label="Stock issue &amp; packing slip preview">
                <div class="po-panel-card po-mockup-card rfq-panel-card rfq-mockup-card">
                    <div class="grn-mockup-head">
                        <div class="grn-mockup-title">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span>Stock Issue / Packing Slip</span>
                            <span class="grn-mockup-pages" id="outMockupPageCountBadge">1 Page</span>
                            <span class="grn-live-dot" aria-hidden="true"></span>
                            <span class="grn-live-text">Live</span>
                        </div>
                        <div class="grn-mockup-actions">
                            <button type="button" class="grn-btn grn-btn-ghost" onclick="openOutDocumentPreview()" title="Print preview (F8)">
                                <i class="ph ph-printer" aria-hidden="true"></i>
                                <span>Preview</span>
                                <kbd class="grn-kbd">F8</kbd>
                            </button>
                            <button type="button" class="grn-icon-btn grn-icon-btn-lg grn-mockup-close" onclick="toggleOutMockupDrawer(false)" aria-label="Close issue slip panel">
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="po-mockup-content-wrap rfq-mockup-content-wrap" id="outMockupContentWrap">
                        <div class="po-live-mockup-container rfq-live-mockup-container" id="outLiveMockupSheet"></div>
                    </div>
                </div>
            </aside>
            <div class="grn-drawer-backdrop" id="outDrawerBackdrop" onclick="toggleOutMockupDrawer(false)" hidden></div>

        </div>
    </div><!-- /#tab-pick-pack-ship -->

    <!-- =====================================================================
         POPUP MODAL: BATCH / LOT # SPECIFICATION (Stock In Parity)
         ===================================================================== -->
    <div id="outLotExpiryModal" class="grn-modal-overlay" style="display: none;" onclick="closeOutLotModalOnBackdrop(event)">
        <div class="grn-modal-card" role="dialog" aria-modal="true" aria-labelledby="outLotModalHeading" aria-describedby="outLotModalItemTitle" onclick="event.stopPropagation()" onkeydown="onOutLotModalKeydown(event)">
            <div class="grn-modal-head">
                <div class="grn-modal-head-left">
                    <div class="grn-modal-icon"><i class="ph ph-barcode" aria-hidden="true"></i></div>
                    <div>
                        <h3 class="grn-modal-title" id="outLotModalHeading">Pick Batch / Lot Specification</h3>
                        <div class="grn-modal-sub" id="outLotModalItemTitle">SKU • Item Name</div>
                    </div>
                </div>
                <button type="button" class="grn-icon-btn grn-icon-btn-lg" onclick="closeOutLotModal()" aria-label="Close dialog">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="grn-modal-body">
                <input type="hidden" id="outLotModalRowIdx" value="">
                <div class="grn-field">
                    <div class="grn-label">
                        <label for="outLotModalLotNumber">Picked Batch / Lot Number <span class="grn-req" aria-hidden="true">*</span></label>
                        <button type="button" class="grn-btn grn-btn-ghost grn-btn-xs" onclick="generateOutLotNumber()">
                            <i class="ph ph-magic-wand" aria-hidden="true"></i> Auto Generate
                        </button>
                    </div>
                    <input type="text" id="outLotModalLotNumber" class="grn-control grn-mono" maxlength="100" placeholder="e.g. LOT-261101-01">
                </div>

                <div class="grn-field">
                    <label class="grn-label" for="outLotModalNotes"><span>Pick / Handling Notes</span></label>
                    <textarea id="outLotModalNotes" class="grn-control grn-textarea" rows="2" maxlength="180" placeholder="e.g. Pulled FIFO from Chiller Shelf B-2, seal intact"></textarea>
                </div>
                <div class="grn-modal-error" id="outLotModalError" role="alert" hidden></div>
            </div>
            <div class="grn-modal-foot">
                <button type="button" class="grn-btn grn-btn-ghost" onclick="closeOutLotModal()">Cancel</button>
                <button type="button" class="grn-btn grn-btn-primary" onclick="saveOutLotModal()">
                    <i class="ph ph-check-circle" aria-hidden="true"></i> Save Lot Details
                </button>
            </div>
        </div>
    </div>

    <!-- Draft Resume Banner (Frontend Rule 16) -->
    <div class="grn-resume-banner" id="outResumeBanner" role="status" hidden>
        <i class="ph ph-clock-counter-clockwise" aria-hidden="true"></i>
        <span id="outResumeBannerText">Unsaved outbound draft found.</span>
        <button type="button" class="grn-btn grn-btn-primary grn-btn-xs" onclick="resumeOutDraft()">Resume</button>
        <button type="button" class="grn-btn grn-btn-ghost grn-btn-xs" onclick="discardOutDraft()">Discard</button>
    </div>

</div>

<!-- =====================================================================
     VIEW ORDER DETAILS & PACKING SLIP MODAL
     ===================================================================== -->
<div class="rms-modal-overlay" id="orderDetailModal" onclick="if(event.target === this) closeOrderDetailModal()">
    <div class="rms-modal-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-receipt" style="font-size: 24px; color: var(--brand-primary, #9333ea);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;" id="modalOrderNum">SO-2026-0001</h3>
                    <div style="font-size: 11.5px; color: #64748b;" id="modalOrderDate">Dispatched at -</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="printPackingSlip()">
                    <i class="ph ph-printer"></i> Print Slip
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeOrderDetailModal()">
                    <i class="ph ph-x"></i> Close
                </button>
            </div>
        </div>

        <div id="modalPrintArea">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <div><strong>Destination / Station:</strong> <span id="modalDest">-</span></div>
                    <div><strong>Usage Purpose:</strong> <span id="modalType">-</span></div>
                    <div><strong>Requester:</strong> <span id="modalReq">-</span></div>
                </div>
                <div>
                    <div><strong>Warehouse Picker:</strong> <span id="modalPicker">-</span></div>
                    <div><strong>Carrier / Driver:</strong> <span id="modalCarrier">-</span></div>
                    <div><strong>Tracking / Waybill:</strong> <span id="modalWaybill">-</span></div>
                </div>
            </div>

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="out-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>UOM</th>
                            <th style="text-align: right;">Requested</th>
                            <th style="text-align: right;">Packed / Shipped</th>
                            <th style="text-align: right;">Unit Cost (₱)</th>
                            <th style="text-align: right;">Total Cost (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTableBody"></tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
                Total Dispatched Valuation: <span id="modalTotalValDisplay" style="color: var(--brand-primary, #9333ea); margin-left: 8px;">₱0.00</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; text-align: center;">
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Requisitioner Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Warehouse Picker Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Recipient Acknowledged</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

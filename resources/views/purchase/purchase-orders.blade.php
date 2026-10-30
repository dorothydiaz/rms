@extends('layouts.app')

@section('title', 'Purchase Orders (PO) - Restaurant Management System')

@push('styles')
    @vite(['resources/css/modules/request-quotations.css', 'resources/css/modules/purchase-orders.css'])
@endpush

@push('blade-data')
<script>
    window.PurchaseOrderConfig = Object.freeze({
        routes: {
            ordersIndex: @json(route('purchase.api.orders')),
            ordersCreate: @json(route('purchase.api.orders.create')),
            saveDraft: @json(route('purchase.api.orders.save-draft')),
            transitionStatus: @json(route('purchase.api.orders.transition-status')),
            batchDelete: @json(route('purchase.api.orders.batch-delete')),
            batchArchive: @json(route('purchase.api.orders.batch-archive')),
            rfqConvertPo: @json(route('purchase.api.rfq.convert-po')),
            requestQuotations: @json(route('purchase.request-quotations')),
            vendorMasterlist: @json(route('purchase.vendor-masterlist')),
            inventoryStockIn: @json(route('inventory.stock-in')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Procurement Specialist'),
        },
        data: {
            initialPurchaseOrders: @json($initialPurchaseOrders ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialItems: @json($initialItems ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/purchase-orders.js')
@endpush

@section('content')
<div class="po-workspace-container rfq-workspace-container" id="poAppWorkspace">

    <!-- Global Pessimistic UI Locking Spinner Overlay -->
    <div class="po-lock-overlay rfq-lock-overlay" id="poGlobalLockOverlay" aria-hidden="true">
        <div class="po-lock-card rfq-lock-card">
            <i class="ph ph-spinner-gap ph-spin" style="font-size: 26px; color: #9333ea;"></i>
            <div>
                <div style="font-weight: 700; font-size: 1.02rem; color: #0f172a;" id="poLockOverlayMessage">Processing Purchase Order...</div>
                <div style="font-size: 0.78rem; color: #64748b;">Synchronizing procurement commitment & ledger state</div>
            </div>
        </div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="poToastContainer"></div>

    <!-- 1. Page Header — HR-Style Flat Title Row (RFQ Parity) -->
    <div class="hr-parent-title" style="margin-bottom: 2px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8); flex-shrink: 0;">
                <i class="ph ph-receipt"></i>
            </div>
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2;">Purchase Orders (PO)</h1>
                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0 0;">Official procurement commitments, RFQ award continuation, receiving dock tracking & vendor settlement</p>
            </div>
        </div>
    </div>

    <!-- PO Navigation Tabs Bar -->
    <nav class="po-nav-tabs-bar rfq-nav-tabs-bar" id="poTabsBar">
        <button type="button" class="po-tab-btn rfq-tab-btn active" id="tabBtnPoList" data-tab="tab-po-list" onclick="switchPoTab('tab-po-list')">
            <i class="ph ph-table"></i>
            <span>Purchase Order Tracker</span>
            <span class="po-tab-count rfq-tab-count" id="tabPoCount">0</span>
        </button>
        <button type="button" class="po-tab-btn rfq-tab-btn" id="tabBtnPoBuilder" data-tab="tab-po-builder" onclick="handlePoBuilderTabClick()">
            <i class="ph ph-file-plus"></i>
            <span id="tabPoBuilderTitle">Purchase Order Builder</span>
            <span class="po-tab-badge rfq-tab-badge" id="tabPoBuilderBadge">New PO</span>
        </button>
    </nav>

    <!-- ====================================================================
         TAB 1: PURCHASE ORDER TRACKER (DIRECTORY)
         ==================================================================== -->
    <div class="po-tab-pane rfq-tab-pane active" id="pane-tab-po-list">
        <!-- 5-Card KPI Metrics Cluster (RFQ Parity) -->
        <div class="rfq-kpi-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom: 12px;">
            <div class="po-kpi-card rfq-kpi-card is-active-filter" data-kpi-filter="all" onclick="filterPoStatus('all')" style="cursor: pointer;">
                <div class="po-kpi-header rfq-kpi-header">
                    <span class="po-kpi-label rfq-kpi-label">Total Purchase Orders</span>
                    <div class="po-kpi-icon rfq-kpi-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb;"><i class="ph ph-files"></i></div>
                </div>
                <div class="po-kpi-val rfq-kpi-val" id="kpiPoTotal">0</div>
                <div class="po-kpi-sub rfq-kpi-sub">All active PO commitments</div>
            </div>

            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="Draft PO" onclick="filterPoStatus('Draft PO')" style="cursor: pointer;">
                <div class="po-kpi-header rfq-kpi-header">
                    <span class="po-kpi-label rfq-kpi-label">Draft & Approval</span>
                    <div class="po-kpi-icon rfq-kpi-icon" style="background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706;"><i class="ph ph-pencil-simple-line"></i></div>
                </div>
                <div class="po-kpi-val rfq-kpi-val" id="kpiPoDrafts">0</div>
                <div class="po-kpi-sub rfq-kpi-sub">In preparation or manager review</div>
            </div>

            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="Approved / Issued" onclick="filterPoStatus('Approved / Issued')" style="cursor: pointer;">
                <div class="po-kpi-header rfq-kpi-header">
                    <span class="po-kpi-label rfq-kpi-label">Issued / In-Transit</span>
                    <div class="po-kpi-icon rfq-kpi-icon" style="background: linear-gradient(135deg, #fff7ed, #ffedd5); color: #ea580c;"><i class="ph ph-truck"></i></div>
                </div>
                <div class="po-kpi-val rfq-kpi-val" id="kpiPoPending">0</div>
                <div class="po-kpi-sub rfq-kpi-sub">Dispatched awaiting receiving dock</div>
            </div>

            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="Fully Received" onclick="filterPoStatus('Fully Received')" style="cursor: pointer;">
                <div class="po-kpi-header rfq-kpi-header">
                    <span class="po-kpi-label rfq-kpi-label">Received (GRN)</span>
                    <div class="po-kpi-icon rfq-kpi-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="po-kpi-val rfq-kpi-val" id="kpiPoReceived">0</div>
                <div class="po-kpi-sub rfq-kpi-sub">Fulfilled & posted to Stock-In</div>
            </div>

            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="Paid in Full / Cash Out" onclick="filterPoPayment('Paid in Full / Cash Out')" style="cursor: pointer;">
                <div class="po-kpi-header rfq-kpi-header">
                    <span class="po-kpi-label rfq-kpi-label">Settled / Paid</span>
                    <div class="po-kpi-icon rfq-kpi-icon" style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); color: #7c3aed;"><i class="ph ph-currency-circle-dollar"></i></div>
                </div>
                <div class="po-kpi-val rfq-kpi-val" id="kpiPoPaid">0</div>
                <div class="po-kpi-sub rfq-kpi-sub" id="kpiPoBalanceSub">Financial cash-out / AP status</div>
            </div>
        </div>

        <!-- Filter, Search & Batch Action Toolbar -->
        <div class="po-panel-card rfq-panel-card" style="overflow: visible;">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-bottom: 1px solid var(--po-border-subtle); flex-wrap: nowrap; gap: 10px; overflow-x: auto; scrollbar-width: none;">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; flex-wrap: nowrap; min-width: 0;">
                    <div style="position: relative; flex: 1; min-width: 220px; max-width: 340px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" class="po-input rfq-input" id="poSearchInput" oninput="handlePoSearch(this.value)" placeholder="Search PO #, RFQ #, vendor partner, contact..." style="padding: 5px 10px 5px 30px; font-size: 12px; height: 32px;">
                    </div>
                    <div id="poActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>

                    <!-- Multi-Selection Batch Action Bar in Table Toolbar (RFQ Parity) -->
                    <div id="poBatchActionBar" class="po-batch-toolbar-bar rfq-batch-toolbar-bar" style="display: none;" role="region" aria-label="PO Batch actions toolbar">
                        <div style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: #9333ea; color: #fff; font-size: 10px;">
                                <i class="ph ph-check-bold"></i>
                            </span>
                            <span><strong id="poBatchSelectedCount">0</strong> selected</span>
                        </div>
                        <div style="display: inline-flex; align-items: center; gap: 6px;">
                            <button type="button" class="po-batch-btn po-batch-btn-danger rfq-batch-btn rfq-batch-btn-danger" id="btnBatchDeletePo" onclick="executePoBatchDelete()" title="Delete selected Draft POs">
                                <i class="ph ph-trash"></i> Delete
                            </button>
                            <button type="button" class="po-batch-btn rfq-batch-btn" id="btnBatchArchivePo" onclick="executePoBatchArchive()" title="Archive selected POs">
                                <i class="ph ph-archive"></i> Archive
                            </button>
                            <button type="button" class="po-batch-btn rfq-batch-btn" id="btnBatchExportPo" onclick="executePoBatchExport()" title="Export selected POs to CSV">
                                <i class="ph ph-download-simple"></i> Export CSV
                            </button>
                            <button type="button" class="po-batch-btn rfq-batch-btn" onclick="clearPoBatchSelection()" title="Clear selection" style="padding: 4px 7px;">
                                <i class="ph ph-x"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 8px; flex-shrink: 0;">
                    <!-- Column Visibility Filter Trigger -->
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnPoColConfig" onclick="togglePoTableSettingsDropdown(event)" title="Customize visible columns & order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-columns"></i> Columns
                    </button>
                    <!-- Backward compatibility dropdown node -->
                    <div class="po-col-dropdown" id="poColConfigDropdown" style="display: none;">
                        <div id="poColChecklist"></div>
                    </div>

                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openImportRfqModal()" title="Convert Awarded RFQ to Purchase Order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-link"></i> Import Awarded RFQ
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startNewPoFromDirectory()" style="padding: 5px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i> + Create PO
                    </button>
                </div>
            </div>

            <!-- PO Directory Table -->
            <div class="po-table-responsive rfq-table-responsive" style="overflow-x: auto; position: relative;">
                <table class="po-table rfq-table" id="poDirectoryTable">
                    <thead id="poDirectoryThead">
                        <tr id="poDirectoryTheadRow">
                            <th style="width: 38px; text-align: center;">
                                <input type="checkbox" id="selectAllPoCheckbox" onchange="toggleSelectAllPos(this.checked)" title="Select all POs on this page" aria-label="Select all POs">
                            </th>
                            <!-- Rendered dynamically by renderPoTableHeader() with stacked headers, column resizers & drag/drop -->
                        </tr>
                    </thead>
                    <tbody id="poDirectoryTbody">
                        <!-- Rendered dynamically by renderPoDirectory() -->
                    </tbody>
                </table>
                <div id="poHeaderFilterPopover" class="po-header-filter-popover rfq-header-filter-popover" style="display: none;"></div>

                <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear -->
                <div id="poTableSettingsDropdown" class="po-table-settings-dropdown rfq-table-settings-dropdown" style="display: none;" onclick="event.stopPropagation()">
                    <div style="padding: 10px 14px; border-bottom: 1px solid #f1f5f9; background: #f8fafc; display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 6px; font-weight: 800; font-size: 12px; color: #0f172a;">
                            <i class="ph ph-sliders-horizontal" style="color: #9333ea; font-size: 14px;"></i>
                            <span>Tracker Columns</span>
                        </div>
                        <span class="rfq-col-badge-default" id="poColActiveCounter" style="font-size: 10.5px; font-weight: 700; color: #7e22ce; background: #f3e8ff; padding: 2px 7px; border-radius: 10px;">9 of 18 visible</span>
                    </div>
                    <div style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px;">
                        <input type="text" class="po-input rfq-settings-search-input" id="poDirectoryColSearchInput" placeholder="Filter tracker columns..." oninput="handlePoDirectoryColSearch(this.value)" style="height: 28px; font-size: 11.5px; padding: 4px 8px;">
                        <div style="display: flex; align-items: center; justify-content: space-between; gap: 4px;">
                            <button type="button" class="inv-col-quick-link" onclick="showAllPoColumns()" style="font-size: 11px; padding: 2px 6px;"><i class="ph ph-check-square"></i> Show All</button>
                            <button type="button" class="inv-col-quick-link" onclick="resetPoColumnDefaults()" style="font-size: 11px; padding: 2px 6px;"><i class="ph ph-columns"></i> Defaults</button>
                            <button type="button" class="inv-col-quick-link is-reset" onclick="resetPoColumnWidths()" style="font-size: 11px; padding: 2px 6px;"><i class="ph ph-arrows-out-line-horizontal"></i> Widths</button>
                        </div>
                    </div>
                    <div class="rfq-settings-col-list" id="poDirectoryColCheckboxesList" style="max-height: 280px; overflow-y: auto; padding: 6px 10px;">
                        <!-- Populated dynamically with draggable items -->
                    </div>
                </div>
            </div>

            <!-- Table Pagination Bar -->
            <div class="po-table-pagination-bar rfq-pagination-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px; font-size: 12px; color: #64748b;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>Showing <strong id="poPaginationStart" style="color: #0f172a;">0</strong> to <strong id="poPaginationEnd" style="color: #0f172a;">0</strong> of <strong id="poPaginationTotal" style="color: #0f172a;">0</strong> purchase orders</span>
                    <span>•</span>
                    <label for="poPageSizeSelect" style="display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px;">
                        <span>Rows per page:</span>
                        <select class="po-select hr-select" id="poPageSizeSelect" onchange="changePoPageSize(this.value)" style="height: 26px; padding: 2px 8px; font-size: 11.5px; width: auto; border-radius: 6px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
                <div class="po-pagination-controls rfq-pagination-controls" id="poPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: PURCHASE ORDER BUILDER (REDESIGNED RFQ ARCHITECTURE)
         ==================================================================== -->
    <div class="po-tab-pane rfq-tab-pane" id="pane-tab-po-builder" style="display: none;">

        <!-- ZONE 1: PO Procurement Lifecycle Header Bar: Buttons & 4-Chevron Progress Bar Side-by-Side -->
        <div class="po-lifecycle-bar-container rfq-lifecycle-bar-container stage-theme-draft" id="poLifecycleStepperWrap">
            <!-- Left: Action Buttons Group for Current Stage -->
            <div class="po-stage-actions-group rfq-stage-actions-group" id="poStageActionsGroup">
                <!-- Rendered dynamically by updatePoLifecycleProgressBar() -->
                <span class="rfq-stage-badge-count" id="poAttachedCountBadge">0</span>
            </div>

            <!-- Right: 4-Stage Chevron Pipeline Progress Bar (RFQ Chevron UI) -->
            <div class="po-chevron-pipeline rfq-chevron-pipeline" id="poLifecycleProgressBar">
                <!-- Stage 1: Draft -->
                <div class="po-chevron-step rfq-chevron-step is-active stage-draft" id="poStepIndicatorDraft" data-stage="Draft" onclick="handlePoStepClick('Draft')">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">1</span></div>
                    <div class="rfq-chevron-info">
                        <span class="rfq-chevron-title">Drafting</span>
                        <span class="rfq-chevron-desc">Preparation</span>
                    </div>
                </div>
                <!-- Stage 2: Approval -->
                <div class="po-chevron-step rfq-chevron-step stage-approval" id="poStepIndicatorApproval" data-stage="Approval" onclick="handlePoStepClick('Approval')">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">2</span></div>
                    <div class="rfq-chevron-info">
                        <span class="rfq-chevron-title">Approval</span>
                        <span class="rfq-chevron-desc">Sign-Off</span>
                    </div>
                </div>
                <!-- Stage 3: Issued -->
                <div class="po-chevron-step rfq-chevron-step stage-submitted" id="poStepIndicatorIssued" data-stage="Issued" onclick="handlePoStepClick('Issued')">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">3</span></div>
                    <div class="rfq-chevron-info">
                        <span class="rfq-chevron-title">Issued</span>
                        <span class="rfq-chevron-desc">Sent & Receiving</span>
                    </div>
                </div>
                <!-- Stage 4: Status (Fulfilled / Settled) -->
                <div class="po-chevron-step rfq-chevron-step stage-status" id="poStepIndicatorStatus" data-stage="Status" onclick="handlePoStepClick('Status')">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">4</span></div>
                    <div class="rfq-chevron-info">
                        <span class="rfq-chevron-title" id="poStepStatusTitle">Status</span>
                        <span class="rfq-chevron-desc" id="poStepStatusDesc">GRN & Settled</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stage Subtitle & Context Ribbon -->
        <div class="po-stage-helper-ribbon rfq-stage-helper-ribbon stage-draft" id="poStageBanner">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span class="rfq-stage-helper-icon" id="poStageBannerIcon"><i class="ph ph-pencil-simple-line"></i></span>
                <strong class="rfq-stage-helper-title" id="poStageBannerTitle">Stage 1: Drafting</strong>
                <span>—</span>
                <span class="rfq-stage-helper-desc" id="poStageBannerSub">Prepare Purchase Order specifications, or import an awarded RFQ package to continue procurement.</span>
            </div>
            <div class="rfq-stage-helper-right" id="poStageBannerActions" style="display: flex; align-items: center; gap: 8px;">
                <span class="rfq-stage-indicator-pill draft" id="poActiveStagePill">
                    <i class="ph ph-circle-dashed"></i> Drafting Mode
                </span>
            </div>
        </div>

        <!-- ZONE 2: Top Section (Full Width - 100%): Main Reference & Operational Header Banner -->
        <div class="po-panel-card rfq-panel-card po-top-banner-card rfq-top-banner-card" id="poTopBannerCard">
            <!-- 1. Top Identity & Status Ribbon Strip (Zero wrapping, zero collision) -->
            <div class="rfq-identity-ribbon-strip" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 10px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <div style="display: inline-flex; align-items: center; gap: 6px; background: #faf5ff; border: 1px solid #e9d5ff; padding: 4px 10px; border-radius: 8px;">
                        <i class="ph ph-receipt" style="color: #9333ea; font-size: 16px;"></i>
                        <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">PO Ref:</span>
                        <span id="poRefDisplay" class="po-ref-id-display rfq-ref-id-display">PO-2026-0105</span>
                        <input type="hidden" id="poNumberInput" value="PO-2026-0105">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copyPoReference()" title="Copy PO Reference to Clipboard" style="padding: 2px 6px; font-size: 11px; height: 22px;">
                            <i class="ph ph-copy"></i>
                        </button>
                    </div>

                    <!-- Interactive Linked RFQ Continuation Pill -->
                    <span id="poLinkedRfqPill" onclick="if(window.PoStore?.activePo?.rfqReference) openLinkedRfqModal(window.PoStore.activePo.rfqReference)" style="display: none; cursor: pointer; font-size: 11.5px; padding: 3px 9px; background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.28); border-radius: 6px; font-family: monospace; font-weight: 700;" title="Click to inspect source Awarded RFQ"></span>

                    <!-- PO Lifecycle Status Badge -->
                    <span class="status-pill draft" id="poStatusBadge" style="font-size: 11px; padding: 3px 9px;">
                        <i class="ph ph-dot"></i> Draft PO
                    </span>

                    <!-- Financial Settlement Quick Status Pill -->
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPaymentSettlementModal()" title="Open Financial Tracking & Payment Settlement Ledger" style="padding: 3px 9px; font-size: 11px; height: 26px; display: inline-flex; align-items: center; gap: 5px; border-radius: 6px;">
                        <i class="ph ph-bank" style="color: #7c3aed;"></i>
                        <span>Settlement:</span>
                        <span id="poSummaryPayBadge" class="pay-badge unpaid" style="font-size: 10px; padding: 1px 6px;">Unpaid</span>
                    </button>
                </div>

                <div style="display: flex; align-items: center; gap: 8px;">
                    <a href="/purchase/vendor-masterlist" id="poVendorDirectLink" class="rfq-vendor-label-link" target="_blank" style="display: none; font-size: 11.5px; font-weight: 700; color: #7e22ce; text-decoration: none; align-items: center; gap: 4px;" title="Open Vendor Profile in Vendor Masterlist">
                        <i class="ph ph-buildings"></i>
                        <span id="poVendorDirectLinkText">View Vendor Profile</span>
                        <i class="ph ph-arrow-square-out"></i>
                    </a>
                </div>
            </div>

            <!-- 2. Operational Form Parameters Grid (Clean 2-Row Multi-Column Grid — Identical to RFQ Builder) -->
            <div class="rfq-operational-grid" style="display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 12px;">
                <!-- Row 1, Col 1-2: Vendor Selection * (Masterlist Combobox + ">" Redirect Button) -->
                <div class="po-field rfq-field" style="grid-column: span 2;">
                    <label class="po-label rfq-label" for="poVendorSelect">
                        <span>Target Vendor Partner *</span>
                        <span class="po-label-badge rfq-label-badge" id="poVendorCatalogBadge">Masterlist Integrated</span>
                    </label>
                    <select class="po-select rfq-select" id="poVendorSelect" style="display: none;" onchange="handlePoVendorSelect(this.value)">
                        <option value="">-- Choose Approved Vendor from Masterlist --</option>
                    </select>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <div class="po-vendor-combobox rfq-vendor-combobox" id="poVendorCombobox" style="flex: 1; min-width: 0;">
                            <button type="button" class="po-vendor-combo-trigger rfq-vendor-combo-trigger" id="poVendorComboTrigger" onclick="togglePoVendorCombobox(event)">
                                <span id="poVendorComboTriggerText" style="color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">-- Choose Approved Vendor from Masterlist --</span>
                                <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8; flex-shrink: 0;"></i>
                            </button>

                            <div class="po-vendor-combo-dropdown rfq-vendor-combo-dropdown" id="poVendorComboDropdown">
                                <div class="po-vendor-combo-search-wrap rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" class="po-vendor-combo-search-input rfq-vendor-combo-search-input" id="poVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterPoVendorCombobox(this.value)" autocomplete="off">
                                </div>
                                <div class="po-vendor-combo-list rfq-vendor-combo-list" id="poVendorComboList">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>
                        </div>

                        <!-- Redirect to Vendor Masterlist Button ">" -->
                        <a href="/purchase/vendor-masterlist" id="poVendorRedirectBtn" class="po-vendor-redirect-btn rfq-vendor-redirect-btn" target="_blank" title="Redirect to Vendor Details in Vendor Masterlist" onclick="return handlePoVendorRedirectClick(event)">
                            <i class="ph ph-caret-right" style="font-size: 15px; font-weight: 800;"></i>
                        </a>
                    </div>
                </div>

                <!-- Row 1, Col 3-4: Order Purpose / Title Description -->
                <div class="po-field rfq-field" style="grid-column: span 2;">
                    <label class="po-label rfq-label" for="poOrderTitle">
                        <span>Order Purpose / Description</span>
                        <span class="po-label-badge rfq-label-badge">Project Reference</span>
                    </label>
                    <input type="text" class="po-input rfq-input" id="poOrderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" oninput="handlePoTitleChange(this.value)">
                </div>

                <!-- Row 2, Col 1: Destination / Ship-To Warehouse -->
                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="poDeliveryLocation">
                        <span>Ship-To Warehouse / Destination</span>
                        <span class="po-label-badge rfq-label-badge">Receiving</span>
                    </label>
                    <select class="po-select rfq-select" id="poDeliveryLocation" onchange="syncPoFormInputsToState(); updatePoLiveDocumentMockup();">
                        <option value="Central Commissary - Receiving Dock 1">Central Commissary - Dock 1</option>
                        <option value="Central Commissary - Receiving Dock A">Central Commissary - Dock A</option>
                        <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                        <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                        <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                        <option value="Central Warehouse - Cold Storage">Central Warehouse - Cold Storage</option>
                    </select>
                </div>

                <!-- Row 2, Col 2: Payment Terms -->
                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="poPaymentTerms">
                        <span>Payment Terms</span>
                        <span class="po-label-badge rfq-label-badge">Commercial</span>
                    </label>
                    <select class="po-select rfq-select" id="poPaymentTerms" onchange="handlePaymentTermsChange(this.value)">
                        <option value="Net 30 Days">Net 30 Days (Standard)</option>
                        <option value="Net 15 Days">Net 15 Days</option>
                        <option value="Net 7 Days">Net 7 Days</option>
                        <option value="COD">Cash on Delivery (COD)</option>
                        <option value="Advance Payment">100% Advance Payment</option>
                        <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                    </select>
                </div>

                <!-- Row 2, Col 3: Order Date (Issued) -->
                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="poOrderDate">
                        <span>Order Date</span>
                        <span class="po-label-badge rfq-label-badge">Issued</span>
                    </label>
                    <input type="date" class="po-input rfq-input" id="poOrderDate" onchange="syncPoFormInputsToState(); updatePoLiveDocumentMockup();">
                </div>

                <!-- Row 2, Col 4: Expected Delivery Date * -->
                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="poExpectedDelivery">
                        <span>Expected Delivery *</span>
                        <span class="po-label-badge rfq-label-badge" style="color: #0369a1;">ETA Due</span>
                    </label>
                    <input type="date" class="po-input rfq-input" id="poExpectedDelivery" onchange="syncPoFormInputsToState(); updatePoLiveDocumentMockup();">
                </div>
            </div>

            <!-- Streamlined Hidden Inputs for State Integrity & Backward-Compatibility -->
            <input type="hidden" id="poContactPerson" value="">
            <input type="hidden" id="poContactTitle" value="">
            <input type="hidden" id="poContactEmail" value="">
            <input type="hidden" id="poContactPhone" value="">
            <input type="hidden" id="poFullAddress" value="">
            <input type="hidden" id="poAmountPaid" value="0">
            <input type="hidden" id="poPaymentStatus" value="Unpaid / Credit">
            <input type="hidden" id="poPaymentMethod" value="Trade Credit (Net 30/15)">
            <input type="hidden" id="poPaymentRef" value="">
            <input type="hidden" id="poPaymentDate" value="">
            <input type="hidden" id="poFundSource" value="Main Commissary Checking Acct">
            <input type="hidden" id="poPaymentRemarks" value="">
            <input type="hidden" id="poSpecialNotes" value="">

            <div id="poVendorSummaryBox" style="display: none !important;">
                <span id="poVendorAvatarText">VM</span>
                <span id="poVendorTradeName">--</span>
                <span id="poVendorCategoryTag">Category</span>
                <span id="poVendorTinDisplay">--</span>
                <span id="poVendorLocationDisplay">--</span>
            </div>
        </div>

        <!-- ZONE 3: Main Body Split (70% Line Items Table Panel / 30% Sticky Live PO Document Mockup) -->
        <div class="po-builder-split-body rfq-builder-split-body" id="poBuilderSplitBody">

            <!-- Left Side (70% Width): Stage-Progressive Line Items & Specifications Table Panel -->
            <div class="po-items-panel-col rfq-items-panel-col">
                <div class="po-items-panel rfq-items-panel">
                    <div class="po-items-toolbar rfq-items-toolbar">
                        <div class="po-toolbar-left rfq-toolbar-left">
                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 8px;">
                                <i class="ph ph-list-numbers" style="color: var(--po-primary); font-size: 18px;"></i>
                                <span>Purchase Order Line Items & Fulfillment Specs</span>
                            </div>
                            <span class="po-items-counter-pill rfq-items-counter-pill" id="poItemCountBadge">0 Line Items</span>
                        </div>

                        <div class="po-toolbar-right rfq-toolbar-right" style="display: flex; align-items: center; gap: 6px;">
                            <button type="button" class="po-btn po-btn-outline rfq-btn rfq-btn-outline" onclick="openImportRfqModal()" title="Import Awarded RFQ lines into this PO" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-link"></i> Import RFQ
                            </button>
                            <button type="button" class="po-btn po-btn-outline rfq-btn rfq-btn-outline" onclick="openItemMasterQuickPicker()" title="Quick add items from Item Master (F2)" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-magnifying-glass"></i> Select Master <span class="po-hotkey-badge rfq-hotkey-badge">F2</span>
                            </button>
                            <button type="button" class="po-btn po-btn-teal rfq-btn rfq-btn-teal" id="btnAddCustomPoRow" onclick="addNewBlankPoItemRow()" title="Add custom non-catalog item specification" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-plus-circle"></i> Add Custom Row
                            </button>
                            <button type="button" class="po-btn po-btn-outline rfq-btn rfq-btn-danger" id="btnClearAllPoRows" style="color: var(--po-danger); padding: 5px 8px; font-size: 11.5px;" onclick="clearAllPoItems()" title="Remove all items from table">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table Container with Horizontal & Vertical Scroll -->
                    <div class="po-table-responsive rfq-table-responsive" id="poTableContainer" style="position: relative;">
                        <table class="po-table rfq-table" id="poItemsTable">
                            <colgroup id="poColgroup">
                                <!-- Populated dynamically based on column widths & visibility -->
                            </colgroup>
                            <thead id="poItemsThead">
                                <tr id="poItemsTheadRow">
                                    <!-- Populated dynamically by renderPoBuilderTableHeader() with Floating Gear Column Settings -->
                                </tr>
                            </thead>
                            <tbody id="poItemsTbody">
                                <!-- Populated dynamically by renderPoItemsTable() -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Items Table Footer Metric Summary -->
                    <div class="po-summary-bar rfq-summary-bar">
                        <div class="po-metrics-cluster rfq-metrics-cluster">
                            <div class="po-metric-unit rfq-metric-unit">
                                <span class="po-metric-label rfq-metric-label">Lines</span>
                                <span class="po-metric-val rfq-metric-val" id="summaryPoLines" style="font-size: 1.05rem;">0</span>
                            </div>
                            <div class="po-metric-unit rfq-metric-unit">
                                <span class="po-metric-label rfq-metric-label">Ordered Units</span>
                                <span class="po-metric-val rfq-metric-val" id="summaryPoUnits" style="font-size: 1.05rem;">0.00</span>
                            </div>
                            <div class="po-metric-unit rfq-metric-unit">
                                <span class="po-metric-label rfq-metric-label">Order Gross Total</span>
                                <span class="po-metric-val highlight rfq-metric-val" id="summaryPoGross" style="font-size: 1.12rem;">₱0.00</span>
                            </div>
                        </div>

                        <div class="rfq-table-hint" style="display: flex; align-items: center; gap: 6px; font-size: 0.76rem; color: #64748b;">
                            <i class="ph ph-broadcast" style="color: #059669;"></i> Live synced with PO Mockup
                        </div>
                    </div>
                </div><!-- /.po-items-panel -->
            </div><!-- /.po-items-panel-col -->

            <!-- Right Side (30% Width): Sticky Live Purchase Order Mockup & Accessible Action Buttons -->
            <div class="po-mockup-panel-col rfq-mockup-panel-col" id="poMockupPanelCol">
                <div class="po-panel-card po-mockup-card rfq-panel-card rfq-mockup-card">
                    <!-- Action Buttons Header: Strictly Single Row via 3-Column Grid -->
                    <div class="po-mockup-header-actions rfq-mockup-header-actions">
                        <button type="button" class="po-btn po-btn-outline rfq-btn rfq-btn-outline rfq-mockup-action-btn" id="btnTogglePoAudit" onclick="openPoAuditDrawer()" title="View Procurement Audit Trail & Activity Log">
                            <i class="ph ph-clock-counter-clockwise"></i>
                            <span>Audit Trail</span>
                        </button>
                        <button type="button" class="po-btn po-btn-outline rfq-btn rfq-btn-outline rfq-mockup-action-btn" onclick="openPoDocumentPreview()" title="Open formal printable PO document preview (F8)">
                            <i class="ph ph-eye"></i>
                            <span>Preview</span>
                            <span class="po-hotkey-badge rfq-hotkey-badge">F8</span>
                        </button>
                        <button type="button" class="po-btn po-btn-primary rfq-btn rfq-btn-primary rfq-mockup-action-btn" id="btnDispatchPoVendor" onclick="issuePurchaseOrderSubmit()" title="Approve & Issue Purchase Order to Vendor (F10)">
                            <i class="ph ph-paper-plane-tilt"></i>
                            <span>Send to Vendor</span>
                            <span class="po-hotkey-badge rfq-hotkey-badge rfq-hotkey-contrast">F10</span>
                        </button>
                    </div>

                    <!-- Sticky Mockup Sheet Container with Dedicated Scrollbar -->
                    <div class="po-mockup-content-wrap rfq-mockup-content-wrap" id="poMockupContentWrap">
                        <div class="rfq-mockup-top-strip" style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px;">
                            <div class="rfq-mockup-pill-tag" style="display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 800; color: #475569; text-transform: uppercase;">
                                <i class="ph ph-file-text" style="color: #9333ea;"></i>
                                <span>Mockup of Purchase Order</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="rfq-mockup-pages-badge" id="poMockupPageCountBadge" style="font-size: 10.5px; font-weight: 700; background: #e2e8f0; color: #334155; padding: 2px 7px; border-radius: 6px;">1 Page</span>
                                <span class="rfq-mockup-live-status" style="font-size: 11px; font-weight: 700; color: #059669; display: inline-flex; align-items: center; gap: 4px;">
                                    <span class="rfq-live-dot" style="width: 7px; height: 7px; border-radius: 50%; background: #10b981; display: inline-block;"></span> Live Sync
                                </span>
                            </div>
                        </div>

                        <!-- Rendered Document Mockup Paper Sheet Container -->
                        <div class="po-live-mockup-container rfq-live-mockup-container" id="poLiveMockupSheet">
                            <!-- Populated dynamically in real time by updatePoLiveDocumentMockup() -->
                        </div>
                    </div>
                </div>
            </div><!-- /.po-mockup-panel-col -->

        </div><!-- /.po-builder-split-body -->

    </div><!-- /#pane-tab-po-builder -->

</div>


<!-- ==========================================================================
     DRAWER: PURCHASE ORDER AUDIT TRAIL RIGHT DRAWER
     ========================================================================== -->
<div id="poAuditDrawerOverlay" class="hr-drawer-overlay" onclick="closePoAuditDrawer()"></div>
<div id="poAuditDrawer" class="hr-drawer" style="max-width: 620px; width: 100%;">
    <div class="hr-drawer-header">
        <button type="button" class="hr-drawer-close" onclick="closePoAuditDrawer()" title="Close Drawer">
            <i class="ph ph-x"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                <i class="ph ph-clock-counter-clockwise"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Purchase Order Audit Trail</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Financial & fulfillment logs • <span id="tabPoAuditCount" style="font-weight: 700; color: #9333ea;">0</span> events
                </div>
            </div>
        </div>
    </div>
    <div class="hr-drawer-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto;">
        <!-- Target Scope Indicator Bar: Restricts index to Current PO / RFQ for process optimization -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: rgba(147, 51, 234, 0.05); border: 1px solid rgba(147, 51, 234, 0.16); border-radius: 8px; padding: 6px 12px; font-size: 11.5px;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span style="color: #64748b; font-weight: 600;">Active PO Index:</span>
                <span id="poAuditTargetRefBadge" style="font-family: monospace; font-weight: 800; color: #9333ea; background: #ffffff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(147, 51, 234, 0.25);">PO-2026-0105</span>
                <span id="poAuditTargetRfqBadge" style="font-family: monospace; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(2, 132, 199, 0.25); display: none;"></span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <button type="button" class="audit-filter-pill active" id="btnPoAuditScopeCurrent" onclick="setPoAuditScope('CURRENT')" title="Limit processing strictly to active PO / RFQ" style="padding: 2px 8px; font-size: 10.5px;">Current PO Only</button>
                <button type="button" class="audit-filter-pill" id="btnPoAuditScopeAll" onclick="setPoAuditScope('ALL')" title="Expand index to all historical records" style="padding: 2px 8px; font-size: 10.5px;">All Records</button>
            </div>
        </div>

        <!-- Audit KPI Summary Cards -->
        <div class="audit-overview-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: rgba(168, 85, 247, 0.12); color: #9333ea; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTotal" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Total PO Events</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #fff7ed; color: #ea580c; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-storefront"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiWetMarket" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Wet Market Runs</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfdf5; color: #059669; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-currency-circle-dollar"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiPayments" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Settlements</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #f5f3ff; color: #7c3aed; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTransfers" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Transfers & Issues</div>
                </div>
            </div>
        </div>

        <!-- Audit Toolbar & Filter Bar -->
        <div class="audit-toolbar" style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="position: relative; width: 100%;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--po-text-muted); font-size: 15px;"></i>
                <input type="text" class="po-input" id="poAuditSearchInput" placeholder="Search PO #, supplier/stall, payment ref..." style="padding-left: 32px; height: 32px; font-size: 0.80rem;" oninput="onPoAuditSearchChange(this.value)">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" class="audit-filter-pill active" data-module="ALL" onclick="filterPoAuditByModule('ALL', this)" style="padding: 2px 8px; font-size: 11px;">All</button>
                    <button type="button" class="audit-filter-pill" data-module="PO" onclick="filterPoAuditByModule('PO', this)" style="padding: 2px 8px; font-size: 11px;">PO Only</button>
                    <button type="button" class="audit-filter-pill" data-module="RFQ" onclick="filterPoAuditByModule('RFQ', this)" style="padding: 2px 8px; font-size: 11px;">RFQ Only</button>
                </div>
                <div class="audit-filter-pills" id="poAuditActionFilterPills" style="display: flex; gap: 4px; flex-wrap: wrap;">
                    <button type="button" class="audit-filter-pill active" data-action="ALL" onclick="filterPoAuditByAction('ALL', this)" style="padding: 2px 7px; font-size: 10.5px;">All</button>
                    <button type="button" class="audit-filter-pill" data-action="PAYMENT_RECORDED" onclick="filterPoAuditByAction('PAYMENT_RECORDED', this)" style="padding: 2px 7px; font-size: 10.5px;">Paid</button>
                    <button type="button" class="audit-filter-pill" data-action="WET_MARKET_CREATED" onclick="filterPoAuditByAction('WET_MARKET_CREATED', this)" style="padding: 2px 7px; font-size: 10.5px;">Market</button>
                    <button type="button" class="audit-filter-pill" data-action="PO_TRANSFERRED" onclick="filterPoAuditByAction('PO_TRANSFERRED', this)" style="padding: 2px 7px; font-size: 10.5px;">RFQ Transfers</button>
                </div>
            </div>
        </div>

        <!-- Chronological Timeline Container -->
        <div class="audit-timeline-container" id="poAuditTimeline">
            <!-- Dynamically populated via renderPoAuditTrail -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: LINKED RFQ QUICK DETAILS INSPECTOR
     ========================================================================== -->
<div class="po-modal-backdrop" id="poLinkedRfqModal" role="dialog" aria-modal="true" aria-labelledby="linkedRfqModalTitle">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a; display: flex; align-items: center; gap: 10px;" id="linkedRfqModalTitle">
                <i class="ph ph-link" style="color: #9333ea;"></i>
                <span>Originating Tender & Quotation Approval Details</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeLinkedRfqModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" id="linkedRfqModalBody" style="max-height: 70vh; overflow-y: auto; padding: 18px;">
            <!-- Dynamically populated via openLinkedRfqModal -->
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeLinkedRfqModal()">Close Inspector</button>
            <a href="{{ route('purchase.request-quotations') }}" class="po-btn po-btn-primary" target="_blank" style="text-decoration: none;">
                <i class="ph ph-arrow-square-out"></i> Open in RFQ Module
            </a>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: IMPORT FROM AWARDED RFQ LIST
     ========================================================================== -->
<div class="po-modal-backdrop" id="poImportRfqModal" role="dialog" aria-modal="true" aria-labelledby="importRfqModalTitle">
    <div class="po-modal-card" style="max-width: 820px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a; display: flex; align-items: center; gap: 10px;" id="importRfqModalTitle">
                <i class="ph ph-link" style="color: #9333ea;"></i>
                <span>Import Approved / Awarded RFQ into Purchase Order</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeImportRfqModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" style="max-height: 68vh; overflow-y: auto; padding: 16px;">
            <div style="font-size: 12.5px; color: #64748b; margin-bottom: 12px;">
                Select any approved quotation tender to automatically populate vendor profile, logistics terms, and awarded item specifications into the Purchase Order Builder:
            </div>
            <div id="importRfqListContainer" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Populated dynamically via openImportRfqModal -->
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeImportRfqModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: FINANCIAL TRACKING & PAYMENT SETTLEMENT
     ========================================================================== -->
<div class="po-modal-backdrop" id="poPaymentSettlementModal" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
    <div class="po-modal-card" style="max-width: 880px; width: 100%;">
        <div class="po-modal-header" style="background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 9px; background: linear-gradient(135deg, #0284c7, #0d9488); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);">
                    <i class="ph ph-bank"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.12rem; color: #0f172a;" id="payModalTitle">Financial Tracking & Payment Settlement</div>
                    <div style="font-size: 0.76rem; color: #64748b;">Disbursement schedule, installments & trade credit tracking by reference</div>
                </div>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePaymentSettlementModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div class="po-modal-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; max-height: 75vh; overflow-y: auto;">
            <!-- Reference Filter & Context Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Active Target Reference:</span>
                    <span id="payModalActiveRef" style="font-family: monospace; font-size: 1.05rem; font-weight: 800; color: var(--po-primary-dark); background: var(--po-primary-subtle); padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(2, 132, 199, 0.25);">PO-2026-0105</span>
                    <span id="payModalLinkedRfqBadge" style="display: none; font-size: 11px; padding: 2px 7px; background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 5px; font-family: monospace; font-weight: 700;"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="payModalRefSelect" style="font-size: 0.76rem; color: #64748b; font-weight: 600;">Filter by Reference:</label>
                    <select id="payModalRefSelect" class="po-select" style="max-width: 200px; padding: 4px 8px; font-size: 12px; height: 30px;" onchange="filterPaymentsByReference(this.value)">
                        <!-- Populated dynamically -->
                    </select>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetPaymentViewToActivePo()" title="View Current Active PO" style="height: 30px; font-size: 11.5px; padding: 0 8px;">
                        <i class="ph ph-arrow-counter-clockwise"></i> Active PO
                    </button>
                </div>
            </div>

            <!-- Financial Calculation KPI Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Commitment (Gross)</div>
                    <div id="payModalGross" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #94a3b8;">Contracted item lines</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Amount Paid</div>
                    <div id="payModalPaid" style="font-size: 1.25rem; font-weight: 800; color: #059669; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #059669;">Recorded disbursements</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Remaining Balance Due</div>
                    <div id="payModalBalance" style="font-size: 1.25rem; font-weight: 800; color: #dc2626; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #dc2626;">Net unsettled liability</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Settlement Status</div>
                    <div>
                        <span id="payModalStatusBadge" class="pay-badge unpaid" style="font-size: 12px; padding: 3px 10px;">Unpaid / Credit</span>
                    </div>
                    <div id="payModalPercentPaid" style="font-size: 0.68rem; color: #64748b;">0% settled</div>
                </div>
            </div>

            <!-- Payments Ledger History Table -->
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <div style="font-weight: 800; font-size: 0.86rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-list-dashes" style="color: var(--po-primary);"></i>
                        <span>Disbursement History for <span id="payLedgerRefLabel" style="font-family: monospace;">--</span></span>
                    </div>
                    <span id="payLedgerCountBadge" style="font-size: 11px; background: #e2e8f0; color: #475569; padding: 1px 7px; border-radius: 8px; font-weight: 700;">0 Entries</span>
                </div>
                <div style="max-height: 180px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left; color: #475569;">
                                <th style="padding: 7px 10px; width: 40px; text-align: center;">#</th>
                                <th style="padding: 7px 10px; width: 95px;">Date</th>
                                <th style="padding: 7px 10px; width: 130px;">Method</th>
                                <th style="padding: 7px 10px;">Reference / OR #</th>
                                <th style="padding: 7px 10px; width: 140px;">Fund Source</th>
                                <th style="padding: 7px 10px; text-align: right; width: 110px;">Amount (₱)</th>
                                <th style="padding: 7px 10px; text-align: center; width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="payModalLedgerTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Record New Payment Disbursement Card -->
            <div style="background: #ffffff; border: 1.5px solid var(--po-primary); border-radius: 10px; padding: 12px 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <div style="font-weight: 800; font-size: 0.88rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-plus-circle" style="color: var(--po-primary);"></i>
                        <span>Record Payment Disbursement against Reference</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fillFullRemainingBalance()" style="font-size: 11px; padding: 2px 8px; height: 24px; color: #059669; border-color: #a7f3d0; background: #ecfdf5;">
                        <i class="ph ph-lightning"></i> Pay Full Balance
                    </button>
                </div>

                <div class="po-form-row">
                    <div class="po-field">
                        <label class="po-label" for="newPayAmount">Amount to Disburse (₱) *</label>
                        <input type="number" step="0.01" min="0.01" class="po-input" id="newPayAmount" placeholder="0.00" style="font-weight: 700; font-size: 0.95rem;">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayMethod">Disbursement Method *</label>
                        <select class="po-select" id="newPayMethod">
                            <option value="Cash / Petty Cash">Cash / Petty Cash (Direct Run)</option>
                            <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15 Days Invoice)</option>
                            <option value="Company Check">Company Check (Spot / Post-Dated)</option>
                            <option value="Bank Transfer / Electronic">Bank Transfer / Electronic (PESONet / InstaPay)</option>
                            <option value="GCash / Maya">GCash / Maya (Mobile e-Wallet)</option>
                            <option value="Corporate Credit Card">Corporate Credit Card</option>
                        </select>
                    </div>
                </div>

                <div class="po-form-row" style="margin-top: 6px;">
                    <div class="po-field">
                        <label class="po-label" for="newPayRef">OR # / Check # / Receipt Ref</label>
                        <input type="text" class="po-input" id="newPayRef" placeholder="e.g. Cash Slip #4912, Check #00412, GCash 88214">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayDate">Payment Date</label>
                        <input type="date" class="po-input" id="newPayDate">
                    </div>
                </div>

                <div class="po-form-row" style="margin-top: 6px;">
                    <div class="po-field">
                        <label class="po-label" for="newPayFundSource">Disbursing Fund Source</label>
                        <select class="po-select" id="newPayFundSource">
                            <option value="Main Commissary Checking Acct">Main Commissary Checking Account</option>
                            <option value="Branch Petty Cash Fund">Branch Petty Cash Fund</option>
                            <option value="Purchaser Cash Advance">Purchaser Cash Advance Fund</option>
                            <option value="Corporate Card Account">Corporate Card Account</option>
                        </select>
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayRemarks">Financial Remarks</label>
                        <input type="text" class="po-input" id="newPayRemarks" placeholder="e.g. 50% initial downpayment for batch production">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="hr-btn hr-btn-primary" onclick="addPaymentDisbursement()" style="padding: 6px 16px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-plus-circle"></i> Add Disbursement Entry
                    </button>
                </div>
            </div>
        </div>

        <div class="po-modal-footer" style="padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 11.5px; color: #64748b;">
                <i class="ph ph-info"></i> Recorded disbursements are automatically synchronized with the active Purchase Order.
            </div>
            <button type="button" class="po-btn po-btn-primary" onclick="closePaymentSettlementModal()" style="padding: 6px 18px; font-size: 12px;">
                Done
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: FORMAL PRINTABLE PURCHASE ORDER PREVIEW
     ========================================================================== -->
<div class="po-modal-backdrop" id="poDocumentModal" role="dialog" aria-modal="true" aria-labelledby="poModalTitle">
    <div class="po-modal-card">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.15rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 10px;" id="poModalTitle">
                <i class="ph ph-receipt" style="color: var(--po-primary);"></i>
                <span>Formal Purchase Order Document Preview</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePoDocumentPreview()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body">
            <div class="po-doc-paper" id="poDocumentPaper">
                <div class="po-doc-header">
                    <div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;">RESTAURANT MANAGEMENT SYSTEM</div>
                        <div style="font-size: 0.84rem; color: #64748b; margin-top: 3px;">Central Commissary & Logistics Purchasing Division</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Lopez Center, Pasig City • VAT Reg. TIN: 009-887-124-000</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.4rem; font-weight: 900; color: var(--po-primary-dark); text-transform: uppercase;">PURCHASE ORDER</div>
                        <div style="font-size: 0.85rem; margin-top: 4px;">PO #: <strong id="docPoNumber">PO-2026-0105</strong></div>
                        <div style="font-size: 0.85rem;">Date Issued: <strong id="docPoDate">--</strong></div>
                        <div style="font-size: 0.85rem;">Delivery Due: <strong id="docPoDeliveryDue" style="color: var(--po-teal-dark);">--</strong></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
                        <div style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                            VENDOR / SOURCING PARTNER
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a;" id="docPoVendor">--</div>
                        <div style="font-size: 0.82rem; color: #475569; margin-top: 4px;" id="docPoVendorDetails">
                            Attn: -- • Phone: --<br>
                            Location: --
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
                        <div style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                            SHIP-TO & FINANCIAL SETTLEMENT
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a;" id="docPoDeliveryDest">Central Commissary</div>
                        <div style="font-size: 0.82rem; color: #475569; margin-top: 4px;">
                            <strong>Payment Status:</strong> <span id="docPoPaymentStatus">Unpaid</span><br>
                            <strong>Method:</strong> <span id="docPoPaymentMethod">Cash / Credit</span> | <strong>Ref:</strong> <span id="docPoPaymentRef">N/A</span>
                        </div>
                    </div>
                </div>

                <table class="po-doc-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th style="width: 100px;">SKU</th>
                            <th>Description & Specifications</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 90px; text-align: right;">Quantity</th>
                            <th style="width: 120px; text-align: right;">Unit Price (₱)</th>
                            <th style="width: 130px; text-align: right;">Amount (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="docPoItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>

                <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                    <div style="width: 280px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                            <span>Gross Total:</span>
                            <strong id="docPoGross">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; color: #059669;">
                            <span>Amount Paid:</span>
                            <strong id="docPoPaid">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1rem; font-weight: 900; border-top: 1.5px solid #cbd5e1; padding-top: 6px;">
                            <span>Balance Due:</span>
                            <strong style="color: #dc2626;" id="docPoBalance">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 32px; padding-top: 16px; border-top: 1px solid #cbd5e1;">
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">PREPARED BY:</div>
                        <div style="font-weight: 800; margin-top: 20px;">{{ auth()->user()->name ?? 'Procurement Officer' }}</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Procurement Specialist</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">FINANCIAL APPROVAL:</div>
                        <div style="font-weight: 800; margin-top: 20px;" id="docPoApprover">System Administrator</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Finance / General Manager</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">RECEIVED / VENDOR CONFORME:</div>
                        <div style="font-style: italic; color: #94a3b8; margin-top: 20px;">Signature & Date Stamp</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Receiving Staff / Market Stallholder</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="window.print()">
                <i class="ph ph-printer"></i> Print / Download PDF
            </button>
            <button type="button" class="po-btn po-btn-teal" onclick="closePoDocumentPreview()">
                Done
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: ITEM MASTER QUICK PICKER FOR PO
     ========================================================================== -->
<div class="po-modal-backdrop" id="poItemMasterModal" role="dialog" aria-modal="true">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.15rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-database" style="color: var(--po-primary);"></i>
                <span>Item Master Quick Catalog</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeItemMasterQuickPicker()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body">
            <div style="margin-bottom: 14px;">
                <input type="text" class="po-input" id="poItemMasterSearch" oninput="filterPoItemMaster(this.value)" placeholder="Search SKU, item name, specs (e.g. Ribeye, Butter, Packaging)...">
            </div>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--po-border-subtle); border-radius: 8px;">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">SKU</th>
                            <th>Item Name & Description</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 110px; text-align: right;">Benchmark</th>
                            <th style="width: 90px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="poItemMasterTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeItemMasterQuickPicker()">Close</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: SINGLE-ITEM PURCHASE ORDER AUDIT TRAIL MODAL
     ========================================================================== -->
<div class="po-modal-backdrop" id="poSingleAuditModal" role="dialog" aria-modal="true" aria-labelledby="singlePoAuditModalTitle">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div class="po-modal-title" id="singlePoAuditModalTitle">
                <i class="ph ph-clock-counter-clockwise" style="color: var(--po-primary);"></i>
                <span id="singlePoAuditHeaderTitle">Purchase Order Audit Trail</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePoSingleAuditModal()" aria-label="Close Audit Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong id="singlePoAuditRefTitle" style="font-size: 1rem; color: var(--po-text-strong);">PO-2026-XXXX</strong>
                    <div id="singlePoAuditSubTitle" style="font-size: 0.8rem; color: var(--po-text-muted);">Procurement Activity & Financial Settlement Trail</div>
                </div>
                <span class="audit-badge badge-payment" id="singlePoAuditStatusBadge">Financial Audit</span>
            </div>

            <!-- Single Item Chronological Timeline -->
            <div class="audit-timeline-container" id="singlePoAuditTimelineContainer">
                <!-- Dynamically rendered -->
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closePoSingleAuditModal()">Close Audit Log</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 4: ATTACHED PROCUREMENT FILES & SUPPORTING DOCUMENTS MODAL
     ========================================================================== -->
<div class="po-modal-backdrop" id="poAttachedFilesModal" role="dialog" aria-modal="true" aria-labelledby="poAttachedFilesModalTitle">
    <div class="po-modal-card" style="max-width: 620px;">
        <div class="po-modal-header">
            <div class="po-modal-title" id="poAttachedFilesModalTitle" style="display: flex; align-items: center; gap: 8px; font-weight: 800;">
                <i class="ph ph-paperclip" style="color: var(--po-primary);"></i>
                <span>Purchase Order Attached Documents & Specs</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePoAttachedFilesModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body">
            <div style="border: 1.5px dashed #cbd5e1; border-radius: 10px; padding: 16px; text-align: center; background: #f8fafc; margin-bottom: 14px;">
                <input type="file" id="poFileUploadInput" multiple style="display: none;" onchange="handlePoFileUpload(this.files)">
                <button type="button" class="po-btn po-btn-outline" onclick="document.getElementById('poFileUploadInput').click()">
                    <i class="ph ph-upload-simple"></i> Attach Signed Quote, COA, or Delivery Spec
                </button>
                <div style="font-size: 11px; color: #64748b; margin-top: 6px;">Supports PDF, PNG, JPG, XLSX, DOCX</div>
            </div>
            <div id="poAttachedFilesList" style="display: flex; flex-direction: column; gap: 8px; max-height: 260px; overflow-y: auto;">
                <!-- Populated dynamically -->
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closePoAttachedFilesModal()">Done</button>
        </div>
    </div>
</div>

@endsection

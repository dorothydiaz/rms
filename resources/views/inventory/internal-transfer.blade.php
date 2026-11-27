@extends('layouts.app')

@section('title', 'Stock Transfer & Branch Requests - Inventory Operations')

@push('styles')
    @vite('resources/css/modules/internal-transfer.css')
@endpush

@push('blade-data')
<script>
    window.InternalTransferConfig = Object.freeze({
        routes: {
            internalTransferData: @json(route('inventory.api.internal-transfer-data')),
            transferDetailBase: @json(url('/inventory/api/internal-transfer')),
            branchStock: @json(route('inventory.api.transfer-branch-stock')),
            lowStockSuggestions: @json(route('inventory.api.transfer-low-stock')),
            createRequest: @json(route('inventory.api.create-transfer-request')),
            createCustomTransfer: @json(route('inventory.api.create-custom-transfer')),
            approveTransfer: @json(route('inventory.api.approve-transfer')),
            rejectTransfer: @json(route('inventory.api.reject-transfer')),
            dispatchTransfer: @json(route('inventory.api.dispatch-transfer')),
            receiveTransfer: @json(route('inventory.api.receive-transfer')),
            cancelTransfer: @json(route('inventory.api.cancel-transfer')),
            settlementMatrix: @json(route('inventory.api.transfer-settlement-matrix')),
            settleTransfers: @json(route('inventory.api.transfer-settle')),
        },
        user: {
            name: @json(auth()->user()->full_name ?? auth()->user()->name ?? auth()->user()->username ?? 'Branch Supervisor'),
        },
        branchContext: @json($branchContext ?? []),
        data: {
            initialPayload: @json($initialPayload ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialBranches: @json($initialBranches ?? []),
            initialCategories: @json($initialCategories ?? []),
            csrfToken: @json(csrf_token()),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/internal-transfer.js')
@endpush

@section('content')
@php
    $stats = $initialPayload['stats'] ?? [];
    $activeBranchName = $branchContext['activeBranchName'] ?? null;
    $isConsolidated = (bool) ($branchContext['isConsolidated'] ?? false);
@endphp

<div class="rfq-workspace-container" id="trfAppRoot">

    <!-- Global Pessimistic Lock Overlay -->
    <div class="rfq-lock-overlay trf-lock-overlay" id="trfGlobalLockOverlay" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div class="trf-lock-title" id="trfLockMessage">Processing Stock Transfer &amp; Ledger Commit...</div>
        <div class="trf-lock-sub">Applying atomic branch locks &amp; recording immutable inventory ledger</div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="trfToastContainer"></div>

    <!-- 1. Page Header — Flat HR Title Row with Grouped Action Hierarchy -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrows-left-right" aria-hidden="true"></i>
                    <span>Stock Transfer &amp; Branch Requests</span>
                </h1>
                <p class="hr-parent-subtitle">
                    <span>Inter-Branch Stock Requests (Pull), Direct Transfers (Push), Transit Custody &amp; Destination Acceptance Receipts</span>
                    @if($isConsolidated)
                        <span class="trf-branch-ctx-pill"><i class="ph ph-stack" aria-hidden="true"></i> Consolidated HQ View</span>
                    @elseif($activeBranchName)
                        <span class="trf-branch-ctx-pill"><i class="ph ph-storefront" aria-hidden="true"></i> Active: {{ $activeBranchName }}</span>
                    @endif
                </p>
            </div>
            <div class="hr-page-actions trf-header-actions">
                <div class="trf-header-util-group">
                    <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary trf-btn-ghost" title="View Stocks Overview">
                        <i class="ph ph-squares-four" aria-hidden="true"></i>
                        <span>Stocks Overview</span>
                    </a>
                    <a href="{{ route('inventory.stock-in') }}" class="hr-btn hr-btn-secondary trf-btn-ghost" title="Open Stock In / Receiving">
                        <i class="ph ph-tray-arrow-down" aria-hidden="true"></i>
                        <span>Stock In</span>
                    </a>
                    <button type="button" class="hr-btn hr-btn-secondary trf-btn-ghost" onclick="openSettlementModal()" title="View Inter-Branch Due-To / Due-From Settlement Matrix">
                        <i class="ph ph-scales" aria-hidden="true"></i>
                        <span>Settlements</span>
                    </button>
                </div>
                <span class="trf-header-divider" aria-hidden="true"></span>
                <div class="trf-header-cta-group">
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="startNewTransferWorkbench('BRANCH_REQUEST')" title="Request stock from another branch into your branch">
                        <i class="ph ph-hand-grabbing" aria-hidden="true"></i>
                        <span>Request Stock</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="startNewTransferWorkbench('CUSTOM_PUSH')" title="Directly dispatch stock from your branch to another branch">
                        <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                        <span>Direct Transfer</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Segmented Nav Tabs Bar (2 Clean Tabs) -->
    <nav class="rfq-nav-tabs-bar" id="trfTabsBar" aria-label="Stock transfer views">
        <button type="button" class="rfq-tab-btn active" id="tabBtnTrfQueue" onclick="switchTrfTab('queue')">
            <i class="ph ph-list-dashes" aria-hidden="true"></i>
            <span>Transfers &amp; Requests Queue</span>
            <span class="rfq-tab-count" id="badgeQueueTotal">{{ $stats['totalTransfers'] ?? 0 }}</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="tabBtnTrfBuilder" onclick="switchTrfTab('builder')">
            <i class="ph ph-arrows-left-right" aria-hidden="true"></i>
            <span id="tabBuilderTitle">Transfer &amp; Request Workbench</span>
            <span class="rfq-tab-badge" id="badgeBuilderStatus">New Request</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: TRANSFERS & REQUESTS QUEUE (O(1) Server-Paginated + SQL KPIs)
         ===================================================================== -->
    <div class="rfq-tab-pane active" id="panelTrfQueue">
        <!-- 4 Accessible Interactive KPI Cards (Click to filter) -->
        <div class="rfq-kpi-grid">
            <button type="button" class="rfq-kpi-card trf-kpi-btn" data-kpi-filter="to_fulfill" onclick="applyKpiQuickFilter('to_fulfill')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Incoming Requests (To Fulfill)</span>
                    <div class="rfq-kpi-icon purple"><i class="ph ph-tray-arrow-down" aria-hidden="true"></i></div>
                </div>
                <div class="rfq-kpi-val" id="trfKpiIncoming">{{ $stats['incomingRequestsCount'] ?? 0 }} Open</div>
                <div class="rfq-kpi-sub">Branch requests waiting for source approval / dispatch</div>
            </button>

            <button type="button" class="rfq-kpi-card trf-kpi-btn" data-kpi-filter="my_requests" onclick="applyKpiQuickFilter('my_requests')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">My Outgoing Requests</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-clock-countdown" aria-hidden="true"></i></div>
                </div>
                <div class="rfq-kpi-val" id="trfKpiOutgoing">{{ $stats['outgoingRequestsCount'] ?? 0 }} Pending</div>
                <div class="rfq-kpi-sub">Requisitions your branch requested from other branches</div>
            </button>

            <button type="button" class="rfq-kpi-card trf-kpi-btn" data-kpi-filter="to_receive" onclick="applyKpiQuickFilter('to_receive')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">In Transit (To Accept)</span>
                    <div class="rfq-kpi-icon draft"><i class="ph ph-truck" aria-hidden="true"></i></div>
                </div>
                <div class="rfq-kpi-val" id="trfKpiTransit">{{ $stats['toReceiveCount'] ?? 0 }} En Route</div>
                <div class="rfq-kpi-sub" id="trfKpiTransitSub">{{ $stats['inTransitCount'] ?? 0 }} total in-transit shipments</div>
            </button>

            <button type="button" class="rfq-kpi-card trf-kpi-btn" data-kpi-filter="completed" onclick="applyKpiQuickFilter('completed')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Completed &amp; Variance</span>
                    <div class="rfq-kpi-icon emerald"><i class="ph ph-check-circle" aria-hidden="true"></i></div>
                </div>
                <div class="rfq-kpi-val" id="trfKpiCompleted">{{ $stats['completedCount'] ?? 0 }} Done</div>
                <div class="rfq-kpi-sub" id="trfKpiVarianceSub">{{ $stats['discrepancyCount'] ?? 0 }} with transit variance</div>
            </button>
        </div>

        <div class="rfq-panel-card trf-panel-card">
            <!-- Branch Perspective Bar -->
            <div class="trf-perspective-bar" id="trfPerspectiveBar">
                <button type="button" class="trf-persp-btn active" data-persp="all" onclick="setPerspectiveFilter('all')">
                    <i class="ph ph-squares-four" aria-hidden="true"></i> All Activity
                </button>
                <button type="button" class="trf-persp-btn" data-persp="action_needed" onclick="setPerspectiveFilter('action_needed')">
                    <i class="ph ph-lightning" aria-hidden="true"></i> Action Needed
                </button>
                <button type="button" class="trf-persp-btn" data-persp="to_fulfill" onclick="setPerspectiveFilter('to_fulfill')">
                    <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i> To Fulfill (Outbound)
                </button>
                <button type="button" class="trf-persp-btn" data-persp="to_receive" onclick="setPerspectiveFilter('to_receive')">
                    <i class="ph ph-package" aria-hidden="true"></i> To Receive (Inbound)
                </button>
                <button type="button" class="trf-persp-btn" data-persp="my_requests" onclick="setPerspectiveFilter('my_requests')">
                    <i class="ph ph-hand-grabbing" aria-hidden="true"></i> My Requests
                </button>
            </div>

            <!-- Single-Row Filter Toolbar -->
            <div class="trf-queue-toolbar">
                <div class="trf-queue-toolbar-left">
                    <div class="trf-queue-search-wrap">
                        <label for="trfSearchInput" class="sr-only">Search transfers by reference, branch, waybill, or SKU</label>
                        <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                        <input type="text" class="rfq-input" id="trfSearchInput" aria-label="Search transfers by reference, branch, waybill, or SKU" placeholder="Search Transfer #, Branch, or SKU... (/)" oninput="onTrfSearchInput(this.value)">
                    </div>

                    <label for="trfStatusFilter" class="sr-only">Filter by status</label>
                    <select id="trfStatusFilter" class="rfq-select trf-queue-filter-select" onchange="onTrfFilterChanged()">
                        <option value="ALL">All Statuses</option>
                        <option value="DRAFT">Draft</option>
                        <option value="REQUESTED">Requested</option>
                        <option value="APPROVED">Approved &amp; Reserved</option>
                        <option value="IN_TRANSIT">In Transit</option>
                        <option value="PARTIALLY_RECEIVED">Partially Received</option>
                        <option value="COMPLETED">Completed</option>
                        <option value="DISCREPANCY">Received w/ Variance</option>
                        <option value="REJECTED">Rejected</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <label for="trfTypeFilter" class="sr-only">Filter by flow type</label>
                    <select id="trfTypeFilter" class="rfq-select trf-queue-filter-select" onchange="onTrfFilterChanged()">
                        <option value="ALL">All Flows</option>
                        <option value="BRANCH_REQUEST">Branch Request (Pull)</option>
                        <option value="CUSTOM_PUSH">Direct Transfer (Push)</option>
                        <option value="BACKORDER_REQUEST">Backorder Split</option>
                        <option value="COMMISSARY_REPLENISHMENT">Auto Replenishment</option>
                    </select>

                    <label for="trfBranchFilter" class="sr-only">Filter by branch</label>
                    <select id="trfBranchFilter" class="rfq-select trf-queue-filter-select" onchange="onTrfFilterChanged()">
                        <option value="">All Branches</option>
                        @foreach($initialBranches as $br)
                            <option value="{{ $br['branch_id'] }}">{{ $br['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="trf-queue-toolbar-right">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fetchTransferQueue(1)" title="Refresh Queue">
                        <i class="ph ph-arrows-clockwise" aria-hidden="true"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Responsive Table + Gear Settings -->
            <div class="rfq-table-responsive trf-table-wrap">
                <table class="rfq-table" id="trfQueueTable">
                    <colgroup id="trfQueueColgroup"></colgroup>
                    <thead id="trfQueueThead">
                        <tr id="trfQueueTheadRow"></tr>
                    </thead>
                    <tbody id="trfQueueTableBody"></tbody>
                </table>
            </div>

            <!-- Gear Column Settings Dropdown -->
            <div id="trfTableSettingsDropdown" class="rfq-table-settings-dropdown trf-gear-dropdown">
                <div class="rfq-settings-dropdown-header">
                    <div class="rfq-settings-title-row">
                        <div class="rfq-settings-title">
                            <i class="ph ph-gear-six" aria-hidden="true"></i>
                            <span>Table Columns</span>
                        </div>
                        <span class="rfq-col-badge-default" id="trfColActiveCounter">8 of 9 visible</span>
                    </div>
                    <div class="rfq-settings-links-row">
                        <button type="button" class="rfq-settings-link-btn" onclick="resetTrfQueueCols()">Reset to Default</button>
                        <button type="button" class="rfq-settings-link-btn" onclick="showAllTrfQueueCols()">Select All</button>
                    </div>
                </div>
                <div class="rfq-settings-col-list" id="trfColCheckboxesList"></div>
            </div>

            <!-- Server-Side Pagination Footer -->
            <div class="trf-pagination-bar">
                <div class="trf-pagination-info" id="trfPaginationInfo">
                    Showing <strong id="trfCountVisible" class="trf-pagination-strong">0</strong> of <strong id="trfCountTotal" class="trf-pagination-strong">0</strong> transfers
                </div>
                <div class="trf-pager-btns" id="trfPagerButtons"></div>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: UNIFIED TRANSFER & REQUEST WORKBENCH (3-Zone Stock In Sheet)
         ===================================================================== -->
    <div class="po-tab-pane rfq-tab-pane trf-panel-hidden" id="panelTrfBuilder">

        <!-- ZONE 1: Action Toolbar + Chevron Lifecycle Pipeline -->
        <div class="trf-zone1">
            <div class="trf-zone1-actions" role="toolbar" aria-label="Transfer lifecycle actions">
                <button type="button" class="trf-top-action-btn is-neutral" id="btnTrfBackToQueue" onclick="switchTrfTab('queue')" title="Back to Queue">
                    <i class="ph ph-arrow-left" aria-hidden="true"></i>
                    <span class="trf-top-btn-sep" aria-hidden="true"></span>
                    <span>Queue</span>
                </button>

                <button type="button" class="trf-top-action-btn is-neutral" id="btnTrfSaveDraft" onclick="submitWorkbenchDraft()" title="Save request as draft">
                    <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                    <span class="trf-top-btn-sep" aria-hidden="true"></span>
                    <span>Save Draft</span>
                </button>

                <button type="button" class="trf-top-action-btn is-danger" id="btnTrfSecondaryDanger" onclick="openReasonModalForCurrentDoc()" title="Reject or cancel transfer" hidden>
                    <i class="ph ph-x-circle" aria-hidden="true"></i>
                    <span class="trf-top-btn-sep" aria-hidden="true"></span>
                    <span id="btnTrfSecondaryDangerLabel">Cancel</span>
                </button>

                <button type="button" class="trf-top-action-btn is-secondary-purple" id="btnTrfApproveAndDispatch" onclick="handleApproveAndDispatchShortcut()" title="Approve & immediately dispatch shipment to transit" hidden>
                    <i class="ph ph-truck" aria-hidden="true"></i>
                    <span class="trf-top-btn-sep" aria-hidden="true"></span>
                    <span>Approve &amp; Dispatch Now</span>
                </button>

                <button type="button" class="trf-top-action-btn is-primary" id="btnTrfPrimaryAction" onclick="handlePrimaryWorkbenchAction()" title="Execute primary step action (F10)">
                    <span class="trf-top-btn-check" aria-hidden="true"><i class="ph-bold ph-check"></i></span>
                    <span class="trf-top-btn-sep" aria-hidden="true"></span>
                    <span id="btnTrfPrimaryActionLabel">Submit Stock Request →</span>
                </button>
            </div>

            <div class="po-chevron-pipeline rfq-chevron-pipeline" id="trfLifecyclePipeline" role="list" aria-label="Transfer workflow stages"></div>
        </div>

        <!-- Live Transit Simulation Banner -->
        <div class="trf-transit-banner" id="trfTransitBanner" role="status" aria-live="polite">
            <div class="trf-transit-node is-source">
                <span class="trf-transit-node-icon" aria-hidden="true"><i class="ph ph-warehouse"></i></span>
                <div class="trf-transit-node-text">
                    <span class="trf-transit-kicker">FROM SOURCE BRANCH</span>
                    <strong id="trfBannerSourceLabel">Select Source Branch</strong>
                </div>
            </div>

            <div class="trf-transit-track-wrap">
                <div class="trf-transit-track">
                    <div class="trf-transit-fill" id="trfBannerTrackFill"></div>
                    <div class="trf-transit-shuttle">
                        <i class="ph-fill ph-package" aria-hidden="true"></i>
                        <span id="trfBannerUnitsBadge">0.00 Units</span>
                    </div>
                </div>
                <span class="trf-banner-stage" id="trfBannerStageLabel">Drafting Stock Request</span>
            </div>

            <div class="trf-transit-node is-dest">
                <span class="trf-transit-node-icon" aria-hidden="true"><i class="ph ph-storefront"></i></span>
                <div class="trf-transit-node-text">
                    <span class="trf-transit-kicker">TO DESTINATION BRANCH</span>
                    <strong id="trfBannerDestLabel">Select Destination Branch</strong>
                </div>
            </div>
        </div>

        <!-- Custody Separation Notice Banner -->
        <div class="trf-custody-banner" id="trfCustodyNoticeBanner" hidden>
            <div class="trf-custody-msg">
                <i class="ph ph-shield-check trf-custody-icon" aria-hidden="true"></i>
                <span id="trfCustodyNoticeText">Custody check active.</span>
            </div>
            <span class="hr-badge hr-badge-neutral" id="trfCustodyNoticeTag">Custody Locked</span>
        </div>

        <!-- ZONE 2: Identity Ribbon + Anti-Bloat 4-Field Operational Grid -->
        <div class="trf-header-card">
            <div class="trf-ribbon">
                <div class="trf-identity-strip">
                    <div class="trf-id-seg">
                        <span class="trf-id-key">Transfer Ref</span>
                        <span class="trf-id-val trf-mono" id="wbTransferNumber">REQ-TRF-AUTO</span>
                    </div>
                    <div class="trf-id-seg" id="wbReceiptSeg" hidden>
                        <span class="trf-id-key">Receipt Voucher</span>
                        <span class="trf-id-val trf-mono trf-receipt-val" id="wbReceiptNumber">—</span>
                    </div>
                    <div class="trf-id-seg">
                        <span class="trf-id-key">Workflow Mode</span>
                        <div class="trf-mode-wrap">
                            <span class="trf-type-tag is-pull" id="wbModeTag">Branch Request (Pull)</span>
                        </div>
                    </div>
                    <div class="trf-id-seg">
                        <label class="trf-id-key" for="wbPrioritySelect">Priority</label>
                        <select id="wbPrioritySelect" class="trf-control trf-priority-select" onchange="onWorkbenchHeaderChanged()">
                            <option value="NORMAL">Normal</option>
                            <option value="HIGH">High</option>
                            <option value="URGENT">Urgent</option>
                            <option value="EMERGENCY">Emergency</option>
                        </select>
                    </div>
                </div>

                <div class="trf-ribbon-badges">
                    <span class="hr-badge hr-badge-neutral" id="wbPricingTag">At Cost (0%)</span>
                    <span class="trf-status-pill is-draft" id="wbStatusPill">Draft</span>
                </div>
            </div>

            <!-- 4 Visible Header Inputs -->
            <div class="trf-form-grid-4">
                <div class="trf-field">
                    <label class="trf-label" for="wbSourceBranchSelect">
                        <i class="ph ph-warehouse" aria-hidden="true"></i>
                        <span>From Branch (Source) *</span>
                    </label>
                    <select id="wbSourceBranchSelect" class="trf-control" onchange="onSourceBranchChanged(this.value)"></select>
                </div>

                <div class="trf-field">
                    <label class="trf-label" for="wbDestBranchSelect">
                        <i class="ph ph-storefront" aria-hidden="true"></i>
                        <span>To Branch (Destination) *</span>
                    </label>
                    <select id="wbDestBranchSelect" class="trf-control" onchange="onDestBranchChanged(this.value)"></select>
                </div>

                <div class="trf-field">
                    <label class="trf-label" for="wbRequiredAtInput">
                        <i class="ph ph-calendar-blank" aria-hidden="true"></i>
                        <span>Target / Required Date</span>
                    </label>
                    <input type="date" id="wbRequiredAtInput" class="trf-control" onchange="onWorkbenchHeaderChanged()">
                </div>

                <div class="trf-field">
                    <label class="trf-label" for="wbReasonCodeSelect">
                        <i class="ph ph-tag" aria-hidden="true"></i>
                        <span>Reason Code</span>
                    </label>
                    <select id="wbReasonCodeSelect" class="trf-control" onchange="onWorkbenchHeaderChanged()">
                        <option value="Branch Replenishment Request">Branch Replenishment Request</option>
                        <option value="Commissary Bulk Batch Push">Commissary Bulk Batch Push</option>
                        <option value="Emergency Kitchen Shortage">Emergency Kitchen Shortage</option>
                        <option value="Excess Stock Rebalancing">Excess Stock Rebalancing</option>
                        <option value="New Menu Rollout">New Menu Rollout</option>
                    </select>
                </div>
            </div>

            <!-- Collapsible Optional Logistics & Notes -->
            <details class="trf-logistics-details" id="wbLogisticsDetails">
                <summary>
                    <i class="ph ph-truck" aria-hidden="true"></i>
                    <span>Dispatch Logistics, Waybill &amp; Custody Notes (Optional — Required for Courier Tracking)</span>
                </summary>
                <div class="trf-logistics-grid">
                    <div class="trf-field">
                        <label class="trf-label" for="wbCarrierInput">Carrier / Driver</label>
                        <input type="text" id="wbCarrierInput" aria-label="Carrier or Driver" class="trf-control" maxlength="255" placeholder="e.g. Commissary Van 02" oninput="onWorkbenchHeaderChanged()">
                    </div>
                    <div class="trf-field">
                        <label class="trf-label" for="wbPlateInput">Vehicle Plate #</label>
                        <input type="text" id="wbPlateInput" aria-label="Vehicle Plate Number" class="trf-control" maxlength="100" placeholder="e.g. NGA-4821" oninput="onWorkbenchHeaderChanged()">
                    </div>
                    <div class="trf-field">
                        <label class="trf-label" for="wbWaybillInput">Waybill / Trip Ticket</label>
                        <input type="text" id="wbWaybillInput" aria-label="Waybill or Trip Ticket" class="trf-control" maxlength="100" placeholder="e.g. WB-2026-019" oninput="onWorkbenchHeaderChanged()">
                    </div>
                    <div class="trf-field">
                        <label class="trf-label" for="wbNotesInput">Remarks / Instructions</label>
                        <input type="text" id="wbNotesInput" aria-label="Remarks or Instructions" class="trf-control" maxlength="500" placeholder="Special handling or receiving note..." oninput="onWorkbenchHeaderChanged()">
                    </div>
                </div>
            </details>
        </div>

        <!-- ZONE 3: Split Body — High-Density Line Grid + Collapsible Slip Preview -->
        <div class="po-builder-split-body rfq-builder-split-body" id="trfSplitBody">
            <div class="po-items-panel-col rfq-items-panel-col">
                <div class="po-items-panel rfq-items-panel">
                    <!-- Toolbar: Quick-Scan + Add Item + 1-Click Accelerators -->
                    <div class="trf-items-toolbar">
                        <div class="trf-scan-bar">
                            <i class="ph ph-barcode trf-scan-icon" aria-hidden="true"></i>
                            <label for="trfScanInput" class="sr-only">Scan barcode or SKU</label>
                            <input type="text" id="trfScanInput" aria-label="Scan barcode or SKU" class="trf-scan-input" placeholder="Scan barcode or type SKU + Enter... (F2)" autocomplete="off" onkeydown="onTrfScanKeydown(event)">
                        </div>

                        <div class="trf-items-toolbar-right">
                            <span class="po-items-counter-pill rfq-items-counter-pill" id="wbLinesCountBadge">0 Line Items</span>

                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnPullLowStock" onclick="pullLowStockSuggestions()" title="Auto-fill items below par level at destination with surplus at source">
                                <i class="ph ph-sparkle" aria-hidden="true"></i>
                                <span>Pull Low-Stock Items</span>
                            </button>

                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnFillAvailable" onclick="fillAllAvailableQuantities()" title="Auto-fill dispatch quantities up to source available stock" hidden>
                                <i class="ph ph-checks" aria-hidden="true"></i>
                                <span>Fill Available</span>
                            </button>

                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnReceiveAllShipped" onclick="receiveAllAsShipped()" title="Set received quantities equal to remaining shipped quantities" hidden>
                                <i class="ph ph-check-square-offset" aria-hidden="true"></i>
                                <span>Receive All as Shipped</span>
                            </button>

                            <div class="trf-item-popover-wrap" id="wbAddItemWrap">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnOpenItemPicker" onclick="toggleTrfItemPicker(event)">
                                    <i class="ph ph-plus" aria-hidden="true"></i>
                                    <span>Add Item</span>
                                </button>
                                <div class="trf-item-popover" id="trfItemPickerDropdown" hidden>
                                    <div class="trf-popover-search">
                                        <label for="trfItemPickerSearch" class="sr-only">Search catalog items by SKU or name</label>
                                        <input type="text" id="trfItemPickerSearch" aria-label="Search catalog items by SKU or name" class="trf-control trf-picker-search" placeholder="Search SKU or item name..." oninput="renderTrfItemPickerList(this.value)" autocomplete="off">
                                    </div>
                                    <div class="trf-popover-list" id="trfItemPickerList"></div>
                                </div>
                            </div>

                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm trf-mockup-toggle" onclick="toggleTrfSlipDrawer()">
                                <i class="ph ph-file-text" aria-hidden="true"></i>
                                <span>Slip Preview</span>
                            </button>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div class="po-table-responsive rfq-table-responsive trf-items-table-wrap">
                        <table class="po-table rfq-table" id="wbItemsTable">
                            <colgroup id="wbItemsColgroup"></colgroup>
                            <thead>
                                <tr id="wbItemsTheadRow"></tr>
                            </thead>
                            <tbody id="wbItemsTableBody"></tbody>
                        </table>
                    </div>

                    <!-- Compact Footer Metrics -->
                    <div class="trf-footer">
                        <div class="trf-footer-metrics">
                            <div class="trf-metric">
                                <span class="trf-metric-label">Lines</span>
                                <span class="trf-metric-val" id="wbSumLines">0</span>
                            </div>
                            <div class="trf-metric">
                                <span class="trf-metric-label">Requested</span>
                                <span class="trf-metric-val" id="wbSumRequested">0.00</span>
                            </div>
                            <div class="trf-metric">
                                <span class="trf-metric-label">Dispatched</span>
                                <span class="trf-metric-val trf-metric-dispatched" id="wbSumDispatched">0.00</span>
                            </div>
                            <div class="trf-metric">
                                <span class="trf-metric-label">Accepted</span>
                                <span class="trf-metric-val trf-metric-received" id="wbSumReceived">0.00</span>
                            </div>
                            <div class="trf-metric" id="wbVarianceMetric" hidden>
                                <span class="trf-metric-label">Variance</span>
                                <span class="trf-metric-val trf-metric-variance" id="wbSumVariance">0.00</span>
                            </div>
                            <div class="trf-metric">
                                <span class="trf-metric-label">Est. Value</span>
                                <span class="trf-metric-val" id="wbSumValuation">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Transfer Waybill & Acceptance Receipt Preview -->
            <aside class="po-mockup-panel-col rfq-mockup-panel-col trf-mockup-col" id="trfSlipPanelCol" aria-label="Transfer Slip Preview">
                <div class="po-panel-card po-mockup-card rfq-panel-card rfq-mockup-card">
                    <div class="trf-slip-head">
                        <div class="trf-slip-title">
                            <i class="ph ph-file-text" aria-hidden="true"></i>
                            <span>Transfer Slip &amp; Custody</span>
                        </div>
                        <div class="trf-slip-actions">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="printTransferSlip()">
                                <i class="ph ph-printer" aria-hidden="true"></i> Print
                            </button>
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm trf-mockup-close" onclick="toggleTrfSlipDrawer(false)" aria-label="Close slip preview">
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>
                    <div class="trf-slip-sheet" id="trfSlipPreviewContent"></div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Partial Dispatch Backorder Choice Modal -->
    <div class="trf-modal-backdrop" id="trfBackorderModal" hidden>
        <div class="trf-modal-card" role="dialog" aria-modal="true" aria-labelledby="trfBoModalTitle">
            <div class="trf-modal-head">
                <div class="trf-modal-head-title" id="trfBoModalTitle">
                    <i class="ph ph-arrows-split trf-modal-icon" aria-hidden="true"></i>
                    <span>Partial Dispatch — Remaining Balance</span>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeBackorderModal()" aria-label="Close dialog">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="trf-modal-body">
                <p class="trf-modal-desc" id="trfBoModalSummary"></p>
                <div class="trf-modal-callout">
                    Choose whether to create an open <strong>Backorder Request</strong> for the unfulfilled items or short-close this request after dispatch.
                </div>
            </div>
            <div class="trf-modal-foot">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="confirmPartialDispatchChoice(false)">
                    Short-Close Balance
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="confirmPartialDispatchChoice(true)">
                    <i class="ph ph-plus-circle" aria-hidden="true"></i> Dispatch &amp; Create Backorder
                </button>
            </div>
        </div>
    </div>

    <!-- Reject / Cancel Reason Modal -->
    <div class="trf-modal-backdrop" id="trfReasonModal" hidden>
        <div class="trf-modal-card" role="dialog" aria-modal="true" aria-labelledby="trfReasonModalTitle">
            <div class="trf-modal-head">
                <div class="trf-modal-head-title" id="trfReasonModalTitle">
                    <i class="ph ph-warning-circle trf-modal-icon trf-modal-icon--danger" aria-hidden="true"></i>
                    <span id="trfReasonModalHeading">Confirm Action</span>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeReasonModal()" aria-label="Close dialog">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="trf-modal-body">
                <label class="trf-label" for="trfReasonTextarea">Reason / Audit Remark *</label>
                <textarea id="trfReasonTextarea" class="trf-control trf-modal-textarea" maxlength="500" placeholder="Enter reason for audit trail..."></textarea>
            </div>
            <div class="trf-modal-foot">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeReasonModal()">Back</button>
                <button type="button" class="hr-btn hr-btn-primary trf-btn-danger" id="btnConfirmReasonModal" onclick="submitReasonModal()">Confirm</button>
            </div>
        </div>
    </div>

    <!-- Inter-Branch Due-To / Due-From Settlement Matrix Modal -->
    <div class="trf-modal-backdrop" id="trfSettlementModal" hidden>
        <div class="trf-modal-card trf-modal-card--wide" role="dialog" aria-modal="true" aria-labelledby="trfSettlementModalTitle">
            <div class="trf-modal-head">
                <div class="trf-modal-head-title" id="trfSettlementModalTitle">
                    <i class="ph ph-scales trf-modal-icon" aria-hidden="true"></i>
                    <span>Inter-Branch Settlement Matrix (Due-To / Due-From GL)</span>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeSettlementModal()" aria-label="Close dialog">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </div>
            <div class="trf-modal-body trf-settlement-body" id="trfSettlementModalBody">
                <div class="trf-settlement-loading">Loading inter-branch balances...</div>
            </div>
            <div class="trf-modal-foot">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeSettlementModal()">Close</button>
            </div>
        </div>
    </div>

</div>
@endsection

@extends('layouts.app')

@section('title', 'Request for Quotations (RFQ) - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/request-quotations.css')
@endpush

@push('blade-data')
<script>
    window.RfqConfig = Object.freeze({
        routes: {
            transitionStatus: @json(route('purchase.api.rfq.transition-status')),
            sendEmail: @json(route('purchase.request-quotations.send-email')),
            purchaseOrders: @json(route('purchase.purchase-orders')),
            createAlternatives: @json(route('purchase.api.rfq.create-alternatives')),
            groupBids: @json(url('/purchase/api/rfq/group')),
            groupAward: @json(route('purchase.api.rfq.group-award')),
            batchDelete: @json(route('purchase.api.rfq.batch-delete')),
            batchArchive: @json(route('purchase.api.rfq.batch-archive')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Dorothy Diaz'),
        },
        data: {
            initialRfqs: @json($initialRfqs ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialItems: @json($initialItems ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/request-quotations.js')
@endpush

@section('content')
<div class="rfq-workspace-container" id="rfqAppWorkspace">

    <!-- Global Pessimistic UI Locking Spinner Overlay -->
    <div class="rfq-lock-overlay" id="rfqGlobalLockOverlay" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div style="font-weight: 700; font-size: 1.05rem;" id="rfqLockOverlayMessage">Processing RFQ Payload...</div>
        <div style="font-size: 0.82rem; opacity: 0.8;">Applying strict transactional lock to prevent double dispatch</div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="rfqToastContainer"></div>

    <!-- 1. Page Header — HR-Style Flat Title Row (No Box Ribbon) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-handshake"></i>
                    <span>Request for Quotations (RFQ)</span>
                </h1>
                <p class="hr-parent-subtitle">Procurement tender workspace, quote comparisons, supplier bid scoring, and conversion to Purchase Orders.</p>
            </div>
            <div class="hr-page-actions">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="exportRfqDirectoryCSV()" data-tooltip="Export Tender Records to CSV">
                    <i class="ph ph-download-simple"></i>
                    <span>Export</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="createNewRfq(false);" data-tooltip="Create a New Request for Quotation">
                    <i class="ph ph-plus-circle"></i>
                    <span>New RFQ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- RFQ Tab Wrapper Bar -->
    <nav class="rfq-nav-tabs-bar" id="rfqTabsBar">
        <button type="button" class="rfq-tab-btn active" id="tabBtnList" data-tab="tab-list" onclick="switchRfqTab('tab-list')">
            <i class="ph ph-list-dashes"></i>
            <span>RFQ Directory & Status Tracker</span>
            <span class="rfq-tab-count" id="tabRfqListCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="tabBtnBuilder" data-tab="tab-builder" onclick="handleBuilderTabClick()">
            <i class="ph ph-plus-circle"></i>
            <span id="tabBuilderTitle">Request for Quotation Builder</span>
            <span class="rfq-tab-badge" id="tabBuilderBadge">New RFQ</span>
        </button>
    </nav>

    <!-- ====================================================================
         TAB 1: RFQ DIRECTORY & STATUS TRACKER
         ==================================================================== -->
    <div class="rfq-tab-pane active" id="pane-tab-list">
        <!-- KPI Metrics Cluster -->
        <div class="rfq-kpi-grid">
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('all')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Total RFQs</span>
                    <div class="rfq-kpi-icon"><i class="ph ph-files"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiTotalRfqs">0</div>
                <div class="rfq-kpi-sub">All active procurement tenders</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Draft')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Draft Sessions</span>
                    <div class="rfq-kpi-icon draft"><i class="ph ph-pencil-simple"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiDraftRfqs">0</div>
                <div class="rfq-kpi-sub">Unsent in-progress tenders</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Submitted')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Submitted / Sent</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-paper-plane-tilt"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiAwaitingRfqs">0</div>
                <div class="rfq-kpi-sub">Dispatched to vendor contacts</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Approval')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">In Review / Quote Rcvd</span>
                    <div class="rfq-kpi-icon in-review" style="color: #2563eb; background: rgba(37, 99, 235, 0.12);"><i class="ph ph-scales"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiApprovalRfqs">0</div>
                <div class="rfq-kpi-sub">Validating quotation inputs</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Approved')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Approved / Awarded</span>
                    <div class="rfq-kpi-icon awarded"><i class="ph ph-seal-check"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiAwardedRfqs">0</div>
                <div class="rfq-kpi-sub">Ready / Converted to PO</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="rfq-panel-card">
            <div class="rfq-items-toolbar" style="overflow-x: auto; scrollbar-width: none; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <div class="rfq-toolbar-left" style="display: flex; align-items: center; gap: 8px; flex: 1; min-width: 0; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; min-width: 200px; max-width: 320px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" class="rfq-input" id="rfqDirectorySearch" oninput="handleRfqDirectorySearch(this.value)" placeholder="Search RFQ #, vendor partner, contact..." style="padding: 5px 10px 5px 30px; font-size: 12px; height: 32px;">
                    </div>
                    <div id="rfqActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>

                    <!-- Multi-Selection Batch Action Bar in Table Toolbar (Light Theme) -->
                    <div id="rfqBatchActionBar" class="rfq-batch-toolbar-bar" style="display: none;" role="region" aria-label="Batch actions toolbar">
                        <div class="rfq-batch-bar-container light-theme">
                            <div class="rfq-batch-info">
                                <span class="rfq-batch-badge">
                                    <i class="ph ph-check"></i>
                                </span>
                                <span class="rfq-batch-counter">
                                    <strong id="rfqBatchSelectedCount">0</strong> selected
                                </span>
                            </div>

                            <div class="rfq-batch-separator"></div>

                            <div class="rfq-batch-actions">
                                <button type="button" class="rfq-batch-btn rfq-batch-btn-danger" id="btnBatchDeleteRfq" onclick="executeBatchDelete()" title="Delete selected tenders">
                                    <i class="ph ph-trash"></i>
                                    <span>Delete</span>
                                </button>
                                <button type="button" class="rfq-batch-btn rfq-batch-btn-secondary" id="btnBatchArchiveRfq" onclick="executeBatchArchive()" title="Archive selected tenders">
                                    <i class="ph ph-archive"></i>
                                    <span>Archive</span>
                                </button>
                                <button type="button" class="rfq-batch-btn rfq-batch-btn-primary" id="btnBatchExportRfq" onclick="executeBatchExport()" title="Export selected tenders to CSV">
                                    <i class="ph ph-download-simple"></i>
                                    <span>Export</span>
                                </button>
                            </div>

                            <div class="rfq-batch-separator"></div>

                            <button type="button" class="rfq-batch-btn-clear" id="btnBatchClearRfq" onclick="clearRfqSelection()" title="Clear tender selection">
                                <i class="ph ph-x"></i>
                                <span>Deselect</span>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="rfq-toolbar-right" style="position: relative; display: flex; align-items: center; gap: 8px;">
                    <button type="button" class="rfq-btn rfq-btn-primary" onclick="startNewRfqFromDirectory()" style="padding: 6px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i> <span>Create RFQ</span>
                    </button>
                </div>
            </div>

            <!-- RFQ Directory Table -->
            <div class="rfq-table-responsive" style="position: relative;">
                <table class="rfq-table" id="rfqDirectoryTable">
                    <thead id="rfqDirectoryThead">
                        <tr id="rfqDirectoryTheadRow">
                            <th class="rfq-th-select" style="width: 44px; min-width: 44px; text-align: center; vertical-align: middle;">
                                <input type="checkbox" id="selectAllRfqCheckbox" onchange="toggleSelectAllRfqs(this.checked)" title="Select all tenders on this page" aria-label="Select all tenders">
                            </th>
                            <!-- Rendered dynamically by renderDirectoryTableHeader() with modular stacked headers, column resizers & drag/drop -->
                        </tr>
                    </thead>
                    <tbody id="rfqDirectoryTbody">
                        <!-- Rendered dynamically by renderRfqDirectory() -->
                    </tbody>
                </table>
                <div id="rfqHeaderFilterPopover" class="rfq-header-filter-popover" style="display: none;"></div>

                <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear -->
                <div id="rfqTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display: none;">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Table Columns & Sequence</span>
                            </div>
                            <span class="rfq-col-badge-default" id="directoryColActiveCounter">8 of 29 visible</span>
                        </div>
                        <div class="rfq-settings-links-row">
                            <button type="button" class="rfq-settings-link-btn" onclick="resetDirectoryColumnDefaults()">Reset to Default</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="showAllDirectoryColumns()">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetDirectoryColumnWidths()">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="directoryColSearchInput" placeholder="Filter columns..." oninput="handleDirectoryColSearch(this.value)">
                    </div>
                    <div class="rfq-settings-col-list" id="directoryColCheckboxesList">
                        <!-- Populated dynamically with draggable items -->
                    </div>
                </div>
            </div>

            <!-- Client-Side Pagination Container -->
            <div class="rfq-pagination-bar" id="rfqDirectoryPaginationBar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 12px; color: #64748b;" id="rfqPaginationInfo">
                    Showing <strong>1</strong> to <strong>10</strong> of <strong>0</strong> tenders
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b;">
                        <span>Show</span>
                        <select id="rfqPageSizeSelect" onchange="changeDirectoryPageSize(this.value)" class="hr-select" style="padding: 3px 8px; font-size: 11.5px; border-radius: 6px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>rows</span>
                    </div>
                    <div class="rfq-pagination-controls" id="rfqPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: CREATION / EDITOR OF RFQ
         ==================================================================== -->
    <div class="rfq-tab-pane" id="pane-tab-builder" style="display: none;">

    <!-- RFQ Procurement Lifecycle Stepper / Progress Bar: | Draft > Submitted > Approval > Status | -->
    <div class="rfq-lifecycle-stepper-wrap stage-theme-draft" id="rfqLifecycleStepperWrap">
        <div class="rfq-lifecycle-stepper" id="rfqLifecycleProgressBar">
            <!-- Step 1: Draft -->
            <div class="rfq-stepper-step is-active stage-draft" id="stepIndicatorDraft" data-stage="Draft" onclick="handleStepClick('Draft')">
                <div class="rfq-stepper-circle">
                    <i class="ph ph-check"></i>
                    <span class="rfq-step-num">1</span>
                </div>
                <div class="rfq-stepper-info">
                    <span class="rfq-step-title">Draft</span>
                    <span class="rfq-step-desc">Create &amp; Edit</span>
                </div>
            </div>

            <div class="rfq-stepper-line" id="stepTrack1"></div>

            <!-- Step 2: Submitted -->
            <div class="rfq-stepper-step" id="stepIndicatorSubmitted" data-stage="Submitted" onclick="handleStepClick('Submitted')">
                <div class="rfq-stepper-circle">
                    <i class="ph ph-check"></i>
                    <span class="rfq-step-num">2</span>
                </div>
                <div class="rfq-stepper-info">
                    <span class="rfq-step-title">Submitted</span>
                    <span class="rfq-step-desc">Locked for Bids</span>
                </div>
            </div>

            <div class="rfq-stepper-line" id="stepTrack2"></div>

            <!-- Step 3: Approval -->
            <div class="rfq-stepper-step" id="stepIndicatorApproval" data-stage="Approval" onclick="handleStepClick('Approval')">
                <div class="rfq-stepper-circle">
                    <i class="ph ph-check"></i>
                    <span class="rfq-step-num">3</span>
                </div>
                <div class="rfq-stepper-info">
                    <span class="rfq-step-title">Approval</span>
                    <span class="rfq-step-desc">Quote Validation</span>
                </div>
            </div>

            <div class="rfq-stepper-line" id="stepTrack3"></div>

            <!-- Step 4: Status -->
            <div class="rfq-stepper-step" id="stepIndicatorStatus" data-stage="Status" onclick="handleStepClick('Status')">
                <div class="rfq-stepper-circle">
                    <i class="ph ph-check"></i>
                    <span class="rfq-step-num">4</span>
                </div>
                <div class="rfq-stepper-info">
                    <span class="rfq-step-title" id="stepStatusTitle">Status</span>
                    <span class="rfq-step-desc" id="stepStatusDesc">Pending</span>
                </div>
            </div>
        </div>

        <!-- Stage Context & Instruction Banner -->
        <div class="rfq-stage-banner stage-draft" id="rfqStageBanner">
            <div class="rfq-stage-banner-icon" id="rfqStageBannerIcon">
                <i class="ph ph-pencil-simple-line"></i>
            </div>
            <div class="rfq-stage-banner-content">
                <div class="rfq-stage-banner-title" id="rfqStageBannerTitle">Stage 1: RFQ Draft Creation &amp; Specification</div>
                <div class="rfq-stage-banner-sub" id="rfqStageBannerSub">Buyer can add, edit, or adjust items, quantities, and benchmark prices. Vendor quotation inputs remain inactive until tender submission.</div>
            </div>
            <div class="rfq-stage-banner-actions" id="rfqStageBannerActions">
                <button type="button" class="rfq-btn" onclick="submitActiveRfqToVendor()" style="background: #9333ea; color: #ffffff; padding: 6px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(147, 51, 234, 0.3);">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Submit &rarr; Submitted</span>
                </button>
            </div>
        </div>

        <!-- Bidding Package Group Bar (Revealed when active RFQ belongs to a group or has alternatives) -->
        <div class="rfq-bidding-group-bar" id="rfqBiddingGroupBar" style="display: none;">
            <div class="rfq-group-meta">
                <div class="rfq-group-icon"><i class="ph ph-git-fork"></i></div>
                <div>
                    <div class="rfq-group-label">Bidding Package / Sibling Alternatives</div>
                    <div class="rfq-group-title" id="rfqGroupPackageTitle">Package: RFQ-2026-0042 (1 Vendor)</div>
                </div>
            </div>
            <div class="rfq-group-pills" id="rfqGroupPillsContainer">
                <!-- Dynamically populated: [Base: San Miguel (A)] [Alt: URC (B)] [Alt: Century (C)] -->
            </div>
            <div class="rfq-group-actions">
                <button type="button" class="rfq-btn rfq-btn-outline hr-btn-sm" onclick="openCreateAlternativeModal()" title="Add another alternative supplier to this tender">
                    <i class="ph ph-plus-circle"></i> <span>Add Vendor</span>
                </button>
                <button type="button" class="rfq-btn rfq-btn-teal hr-btn-sm" onclick="openCompareBidsModal()" id="btnCompareBidsGroup" title="Open side-by-side bid comparison matrix">
                    <i class="ph ph-scales"></i> <span>Compare Bids</span>
                    <span class="rfq-tab-badge" id="badgeCompareCount" style="margin-left: 4px; background: rgba(255,255,255,0.25); color: #fff;">0</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Workspace Grid: Asymmetric 4.2fr Left (Cards 1 & 2) / 5.8fr Right (Card 3 Items Panel) -->
    <div class="rfq-builder-workspace-grid">

        <!-- Left Column: Vendor Selection & RFQ Terms -->
        <div class="rfq-builder-left-col">

            <!-- Tender Reference & Identity Header Block (Prominent Placement with Description) -->
            <div class="rfq-panel-card rfq-tender-ref-card" style="border-left: 4px solid #9333ea; background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.65)); padding: 10px 14px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 3px 10px rgba(168, 85, 247, 0.28);">
                            <i class="ph ph-file-text"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Tender Reference Number</div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="rfqRefDisplay" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #9333ea; letter-spacing: -0.01em;">RFQ-2026-0044</span>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copyRfqReference()" title="Copy Reference to Clipboard" style="padding: 2px 6px; font-size: 11px; height: 22px;">
                                    <i class="ph ph-copy"></i>
                                </button>
                                <button type="button" class="hr-btn hr-btn-outline hr-btn-sm" onclick="createNewRfq()" title="Create New RFQ" style="padding: 2px 8px; font-size: 11px; height: 22px; color: #9333ea; border-color: rgba(147, 51, 234, 0.3); display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-plus-circle"></i>
                                    <span>New</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="status-pill draft" id="rfqStatusBadge" style="font-size: 11px; padding: 3px 9px;">
                            <i class="ph ph-dot"></i> Draft RFQ
                        </span>
                    </div>
                </div>

                <!-- Tender Purpose / Description Field -->
                <div class="rfq-field" style="margin-top: 2px;">
                    <label class="rfq-label" for="rfqTenderTitle" style="font-size: 0.70rem; color: #475569; margin-bottom: 3px;">
                        <span>Tender Purpose / Title Description</span>
                        <span class="rfq-label-badge" style="font-size: 0.62rem;">Project Reference</span>
                    </label>
                    <input type="text" class="rfq-input" id="rfqTenderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" style="font-size: 0.84rem; padding: 5px 10px; font-weight: 500;" oninput="handleRfqTitleChange(this.value)">
                </div>
            </div>

            <!-- Card 1: Vendor Selection & Company Profile Details -->
            <div class="rfq-panel-card">
                <div class="rfq-panel-header">
                    <div class="rfq-panel-title">
                        <i class="ph ph-buildings"></i>
                        <span>Vendor Selection & Partner Profile</span>
                    </div>
                    <span class="rfq-label-badge" id="vendorCatalogBadge">Catalog Integrated</span>
                </div>
                <div class="rfq-panel-body">
                    <div class="rfq-field">
                        <label class="rfq-label" for="vendorSelect">
                            <span>Select Target Vendor *</span>
                            <span class="rfq-label-badge">Searchable Masterlist</span>
                        </label>
                        <!-- Hidden select for form bindings & backwards-compatibility -->
                        <select class="rfq-select" id="vendorSelect" style="display: none;" onchange="handleVendorSelection(this.value)">
                            <option value="">-- Choose Approved Vendor from Masterlist --</option>
                            <!-- Populated dynamically -->
                        </select>

                        <!-- Custom Searchable Combobox -->
                        <div class="rfq-vendor-combobox" id="rfqVendorCombobox">
                            <button type="button" class="rfq-vendor-combo-trigger" id="rfqVendorComboTrigger" onclick="toggleVendorCombobox(event)">
                                <span id="rfqVendorComboTriggerText" class="rfq-vendor-trigger-placeholder">
                                    <i class="ph ph-buildings"></i>
                                    <span>-- Choose Approved Vendor from Masterlist --</span>
                                </span>
                                <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8;"></i>
                            </button>

                            <div class="rfq-vendor-combo-dropdown" id="rfqVendorComboDropdown">
                                <div class="rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" class="rfq-vendor-combo-search-input" id="rfqVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterVendorCombobox(this.value)" autocomplete="off">
                                </div>
                                <div class="rfq-vendor-combo-list" id="rfqVendorComboList">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Streamlined Partner Profile Metadata Ribbon (No duplicated vendor name or avatar) -->
                    <div class="rfq-vendor-meta-strip" id="vendorMetaStrip" style="display: none;">
                        <div class="rfq-meta-chip">
                            <i class="ph ph-identification-card"></i>
                            <span class="rfq-meta-chip-label">TIN:</span>
                            <strong id="vendorTinDisplay" class="rfq-meta-chip-val">--</strong>
                        </div>
                        <div class="rfq-meta-chip">
                            <i class="ph ph-tag"></i>
                            <span class="rfq-meta-chip-label">Category:</span>
                            <span id="vendorCategoryTag" class="rfq-meta-chip-val">Supplies</span>
                        </div>
                        <div class="rfq-meta-chip">
                            <i class="ph ph-clock"></i>
                            <span class="rfq-meta-chip-label">Terms:</span>
                            <span id="vendorTermsDisplay" class="rfq-meta-chip-val">Net 30 Days</span>
                        </div>
                        <div class="rfq-meta-chip">
                            <i class="ph ph-map-pin"></i>
                            <span class="rfq-meta-chip-label">Region:</span>
                            <span id="vendorLocationDisplay" class="rfq-meta-chip-val">--</span>
                        </div>
                    </div>

                    <!-- Hidden legacy anchors for backwards compatibility -->
                    <span id="vendorTradeName" style="display: none;"></span>
                    <span id="vendorAvatarText" style="display: none;"></span>
                    <div id="vendorSummaryBox" style="display: none;"></div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorContactPerson">Contact Person</label>
                            <input type="text" class="rfq-input" id="vendorContactPerson" placeholder="Primary Contact Person" title="Primary Contact Person">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorContactTitle">Designation</label>
                            <input type="text" class="rfq-input" id="vendorContactTitle" placeholder="Contact Designation / Title" title="Contact Designation / Title">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorEmail">
                                <span>Recipient Email *</span>
                            </label>
                            <input type="email" class="rfq-input" id="vendorEmail" placeholder="vendor@example.com" title="Vendor Recipient Email" required>
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorPhone">Phone / Mobile</label>
                            <input type="text" class="rfq-input" id="vendorPhone" placeholder="Direct Phone" title="Contact Direct Phone">
                        </div>
                    </div>

                    <div class="rfq-field">
                        <label class="rfq-label" for="vendorAddress">Physical / Billing Address</label>
                        <input type="text" class="rfq-input" id="vendorAddress" placeholder="Street, Building, City, ZIP">
                    </div>
                </div>
            </div>

            <!-- Card 2: Procurement Terms & Delivery Schedules -->
            <div class="rfq-panel-card">
                <div class="rfq-panel-header">
                    <div class="rfq-panel-title">
                        <i class="ph ph-calendar-check"></i>
                        <span>RFQ Terms & Delivery Schedule</span>
                    </div>
                    <span class="rfq-label-badge" id="rfqTermsLockBadge">Auto Lead-Time Sync</span>
                </div>
                <div class="rfq-panel-body">
                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqNumberInput">RFQ Reference #</label>
                            <input type="text" class="rfq-input" id="rfqNumberInput" value="RFQ-2026-0042" readonly style="font-family: monospace; font-weight: 700; color: #9333ea;">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqDateIssued">Date Issued</label>
                            <input type="date" class="rfq-input" id="rfqDateIssued">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqDueDate">
                                <span>Quotation Due *</span>
                                <span class="rfq-label-badge" style="color: #b45309;">Submission</span>
                            </label>
                            <input type="date" class="rfq-input" id="rfqDueDate">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqQuoteValidityDate">
                                <span>Quote Validity Date</span>
                                <span class="rfq-label-badge" style="color: #6366f1;">Valid Until</span>
                            </label>
                            <input type="date" class="rfq-input" id="rfqQuoteValidityDate">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqExpectedDelivery">
                                <span>Expected Delivery *</span>
                                <span class="rfq-label-badge" id="leadTimeBadge">Lead Time</span>
                            </label>
                            <input type="date" class="rfq-input" id="rfqExpectedDelivery">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqPaymentTerms">Payment Terms</label>
                            <select class="rfq-select" id="rfqPaymentTerms">
                                <option value="Net 30 Days">Net 30 Days (Standard)</option>
                                <option value="Net 15 Days">Net 15 Days</option>
                                <option value="Net 7 Days">Net 7 Days</option>
                                <option value="COD">Cash on Delivery (COD)</option>
                                <option value="Advance Payment">100% Advance Payment</option>
                                <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                            </select>
                        </div>
                    </div>

                    <div class="rfq-field">
                        <label class="rfq-label" for="rfqDeliveryLocation">Destination Facility</label>
                        <select class="rfq-select" id="rfqDeliveryLocation">
                            <option value="Central Commissary - Main Dock">Central Commissary - Dock A</option>
                            <option value="Central Commissary - Main Cold Dock">Central Commissary - Main Cold Dock</option>
                            <option value="Central Warehouse - Dry Storage">Central Warehouse - Dry Storage</option>
                            <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                            <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                            <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                        </select>
                    </div>

                    <div class="rfq-field">
                        <label class="rfq-label" for="rfqSpecialInstructions">Bidding Instructions / Notes</label>
                        <textarea class="rfq-textarea" id="rfqSpecialInstructions" placeholder="e.g. VAT-inclusive pricing; attach COA for dairy/meats."></textarea>
                    </div>
                </div>
            </div>

        </div><!-- /.rfq-builder-left-col -->

        <!-- Right Column: Card 3 Line Items Panel -->
        <div class="rfq-builder-right-col">
            <!-- Quotation Line Items & Specifications Table (With Floating Column Filter) -->
            <div class="rfq-items-panel">
                <div class="rfq-items-toolbar">
                    <div class="rfq-toolbar-left">
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--rfq-text-strong); display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-list-numbers" style="color: var(--rfq-primary); font-size: 18px;"></i>
                            <span>Quotation Line Items & Specifications</span>
                        </div>
                        <span class="rfq-items-counter-pill" id="rfqItemsBadge">0 Line Items</span>
                    </div>

                    <div class="rfq-toolbar-right">
                        <button type="button" class="rfq-btn rfq-btn-outline" onclick="openItemMasterQuickSelectModal()" title="Quick add items from Item Master (F2)" style="padding: 5px 10px; font-size: 11.5px;">
                            <i class="ph ph-magnifying-glass"></i> Select Master <span class="rfq-hotkey-badge">F2</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-teal" onclick="addNewBlankItemRow()" title="Add custom non-catalog item specification" style="padding: 5px 10px; font-size: 11.5px;">
                            <i class="ph ph-plus-circle"></i> Add Custom Row
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-danger" onclick="clearAllItemRows()" title="Remove all items from table" style="padding: 5px 8px; font-size: 11.5px;">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Container with Horizontal & Vertical Scroll -->
                <div class="rfq-table-responsive" id="rfqTableContainer">
                    <table class="rfq-table" id="rfqMasterTable">
                        <colgroup id="rfqColgroup">
                            <!-- Populated dynamically based on column widths & visibility -->
                        </colgroup>
                        <thead id="rfqThead">
                            <tr id="rfqTheadRow">
                                <!-- Populated dynamically with Floating Column Filter Dropdown anchored after Action Header -->
                            </tr>
                        </thead>
                        <tbody id="rfqTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Items Table Footer Metric Summary -->
                <div class="rfq-summary-bar" style="padding: 10px 16px;">
                    <div class="rfq-metrics-cluster" style="gap: 16px;">
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Lines</span>
                            <span class="rfq-metric-val" id="summaryTotalLines" style="font-size: 1.05rem;">0</span>
                        </div>
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Total Units</span>
                            <span class="rfq-metric-val" id="summaryTotalUnits" style="font-size: 1.05rem;">0.00</span>
                        </div>
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Benchmark Budget</span>
                            <span class="rfq-metric-val highlight" id="summaryEstimatedBudget" style="font-size: 1.12rem;">₱0.00</span>
                        </div>
                    </div>

                    <div class="rfq-actions-group" style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="rfq-btn rfq-btn-outline" id="btnBuilderCreateAlt" onclick="openCreateAlternativeModal()" title="Add alternative supplier to compare quotes" style="padding: 5px 12px; font-size: 11.5px; border-radius: 8px;">
                            <i class="ph ph-git-fork"></i> <span>Create Alternative</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-teal" id="btnBuilderCompareBids" onclick="openCompareBidsModal()" title="Compare vendor quotes side-by-side" style="padding: 5px 12px; font-size: 11.5px; border-radius: 8px; display: none;">
                            <i class="ph ph-scales"></i> <span>Compare Bids</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-outline" id="btnToggleBuilderAudit" onclick="openRfqAuditDrawer()" title="View Procurement Audit Trail & Activity Log" style="padding: 5px 12px; font-size: 11.5px; border-radius: 8px;">
                            <i class="ph ph-clock-counter-clockwise"></i> <span>Audit Trail</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-outline" onclick="openRfqDocumentPreview()" title="Open formal printable RFQ document preview (F8)" style="padding: 5px 12px; font-size: 11.5px; border-radius: 8px;">
                            <i class="ph ph-eye"></i> <span>Preview</span> <span class="rfq-hotkey-badge">F8</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-primary" id="btnDispatchEmail" onclick="openSendEmailModal()" title="Send RFQ package directly to vendor contact (F10)" style="padding: 5px 14px; font-size: 11.5px; border-radius: 8px;">
                            <i class="ph ph-paper-plane-tilt"></i> <span>Send to Vendor</span> <span class="rfq-hotkey-badge" style="background: rgba(255,255,255,0.25);">F10</span>
                        </button>
                    </div>
                </div>
            </div>
        </div><!-- /.rfq-builder-right-col -->
    </div><!-- /.rfq-builder-workspace-grid -->
</div><!-- /#pane-tab-builder -->

</div>

<!-- ==========================================================================
     DRAWER: PROCUREMENT AUDIT TRAIL RIGHT DRAWER
     ========================================================================== -->
<div id="rfqAuditDrawerOverlay" class="hr-drawer-overlay" onclick="closeRfqAuditDrawer()"></div>
<div id="rfqAuditDrawer" class="hr-drawer" style="max-width: 620px; width: 100%;">
    <div class="hr-drawer-header">
        <button type="button" class="hr-drawer-close" onclick="closeRfqAuditDrawer()" title="Close Drawer">
            <i class="ph ph-x"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                <i class="ph ph-clock-counter-clockwise"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Procurement Audit Trail</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Activity history & event log • <span id="tabRfqAuditCount" style="font-weight: 700; color: #9333ea;">0</span> events
                </div>
            </div>
        </div>
    </div>
    <div class="hr-drawer-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto;">
        <!-- Target Scope Indicator Bar: Restricts index to Current PO / RFQ for process optimization -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: rgba(147, 51, 234, 0.05); border: 1px solid rgba(147, 51, 234, 0.16); border-radius: 8px; padding: 6px 12px; font-size: 11.5px;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span style="color: #64748b; font-weight: 600;">Active Tender Index:</span>
                <span id="auditTargetRefBadge" style="font-family: monospace; font-weight: 800; color: #9333ea; background: #ffffff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(147, 51, 234, 0.25);">RFQ-2026-0044</span>
                <span id="auditTargetPoBadge" style="font-family: monospace; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(2, 132, 199, 0.25); display: none;"></span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <button type="button" class="audit-filter-pill active" id="btnAuditScopeCurrent" onclick="setAuditScope('CURRENT')" title="Limit processing strictly to current RFQ / PO" style="padding: 2px 8px; font-size: 10.5px;">Current PO/RFQ Only</button>
                <button type="button" class="audit-filter-pill" id="btnAuditScopeAll" onclick="setAuditScope('ALL')" title="Expand index to all historical records" style="padding: 2px 8px; font-size: 10.5px;">All Records</button>
            </div>
        </div>

        <!-- Audit KPI Summary Cards -->
        <div class="audit-overview-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiTotalCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Total Logged Actions</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfdf5; color: #059669; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiApprovalsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Manager Approvals</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-pencil-line"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiEditsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Creations & Edits</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfeff; color: #0891b2; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-envelope-simple"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiEmailsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Email Dispatches</div>
                </div>
            </div>
        </div>

        <!-- Audit Toolbar & Filter Bar -->
        <div class="audit-toolbar" style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="position: relative; width: 100%;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--rfq-text-muted); font-size: 15px;"></i>
                <input type="text" class="rfq-input" id="auditSearchInput" placeholder="Search audit trail by Ref, vendor, user..." style="padding-left: 32px; height: 32px; font-size: 0.80rem;" oninput="onAuditSearchChange(this.value)">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" class="audit-filter-pill active" data-module="ALL" onclick="filterAuditByModule('ALL', this)" style="padding: 2px 8px; font-size: 11px;">All</button>
                    <button type="button" class="audit-filter-pill" data-module="RFQ" onclick="filterAuditByModule('RFQ', this)" style="padding: 2px 8px; font-size: 11px;">RFQ</button>
                    <button type="button" class="audit-filter-pill" data-module="PO" onclick="filterAuditByModule('PO', this)" style="padding: 2px 8px; font-size: 11px;">PO</button>
                </div>
                <div class="audit-filter-pills" id="auditActionFilterPills" style="display: flex; gap: 4px; flex-wrap: wrap;">
                    <button type="button" class="audit-filter-pill active" data-action="ALL" onclick="filterAuditByAction('ALL', this)" style="padding: 2px 7px; font-size: 10.5px;">All</button>
                    <button type="button" class="audit-filter-pill" data-action="APPROVED" onclick="filterAuditByAction('APPROVED', this)" style="padding: 2px 7px; font-size: 10.5px;">Approvals</button>
                    <button type="button" class="audit-filter-pill" data-action="CREATED" onclick="filterAuditByAction('CREATED', this)" style="padding: 2px 7px; font-size: 10.5px;">Created</button>
                    <button type="button" class="audit-filter-pill" data-action="EDITED" onclick="filterAuditByAction('EDITED', this)" style="padding: 2px 7px; font-size: 10.5px;">Edited</button>
                    <button type="button" class="audit-filter-pill" data-action="EMAIL_SENT" onclick="filterAuditByAction('EMAIL_SENT', this)" style="padding: 2px 7px; font-size: 10.5px;">Emails</button>
                </div>
            </div>
        </div>

        <!-- Audit Chronological Timeline List -->
        <div class="audit-timeline-container" id="rfqAuditTimeline">
            <!-- Dynamically populated via renderRfqAuditTrail -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: FORMAL DOCUMENT PREVIEW MODAL (PRINTABLE / PDF EXPORT)
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqDocumentPreviewModal" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
    <div class="rfq-modal-card" style="max-width: 920px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="previewModalTitle">
                <i class="ph ph-file-text"></i>
                <span>Formal Request for Quotation Document Preview</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeRfqDocumentPreview()" aria-label="Close Preview">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <!-- Formal Printable RFQ Document Paper -->
            <div class="rfq-doc-paper" id="rfqDocumentPreviewPaper">
                <div class="rfq-doc-header">
                    <div>
                        <div class="rfq-doc-company-title">RESTAURANT MANAGEMENT SYSTEM</div>
                        <div class="rfq-doc-company-sub">Procurement, Logistics & Supply Chain Division</div>
                        <div class="rfq-doc-company-sub">Headquarters: Lopez Center, Pasig City • VAT Reg. TIN: 009-887-124-000</div>
                    </div>
                    <div class="rfq-doc-badge-col">
                        <div class="rfq-doc-main-badge">REQUEST FOR QUOTATION</div>
                        <div class="rfq-doc-meta-row">RFQ Number: <strong id="docRfqNumber">RFQ-2026-0042</strong></div>
                        <div class="rfq-doc-meta-row">Date Issued: <strong id="docDateIssued">--</strong></div>
                        <div class="rfq-doc-meta-row">Quotation Deadline: <strong id="docQuotationDeadline" style="color: #dc2626;">--</strong></div>
                    </div>
                </div>

                <div class="rfq-doc-parties">
                    <div class="rfq-doc-party-box">
                        <div class="rfq-doc-party-label">VENDOR / SUPPLIER RECIPIENT</div>
                        <div class="rfq-doc-party-name" id="docVendorName">--</div>
                        <div class="rfq-doc-party-text">
                            <strong>Attn:</strong> <span id="docVendorContact">--</span> (<span id="docVendorTitle">--</span>)<br>
                            <strong>Email:</strong> <span id="docVendorEmail">--</span> | <strong>Phone:</strong> <span id="docVendorPhone">--</span><br>
                            <strong>Address:</strong> <span id="docVendorAddress">--</span>
                        </div>
                    </div>

                    <div class="rfq-doc-party-box">
                        <div class="rfq-doc-party-label">SHIP TO & PROCUREMENT CONDITIONS</div>
                        <div class="rfq-doc-party-name" id="docDeliveryLocation">Central Commissary</div>
                        <div class="rfq-doc-party-text">
                            <strong>Target Delivery Date:</strong> <span id="docTargetDeliveryDate" style="font-weight: 700;">--</span><br>
                            <strong>Payment Terms:</strong> <span id="docPaymentTerms">Net 30 Days</span><br>
                            <strong>Currency:</strong> Philippine Peso (PHP ₱)
                        </div>
                    </div>
                </div>

                <!-- Printable Items Table Displaying Active Visible Columns -->
                <table class="rfq-doc-table" id="docItemsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 110px;">SKU</th>
                            <th>Item Description & Specifications</th>
                            <th style="width: 90px; text-align: center;">UOM</th>
                            <th style="width: 100px; text-align: right;">Quantity</th>
                            <th style="width: 140px; text-align: right;">Vendor Quoted Unit Price (₱)</th>
                            <th style="width: 120px; text-align: center;">Availability / Lead Time</th>
                        </tr>
                    </thead>
                    <tbody id="docItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>

                <div class="rfq-doc-terms">
                    <strong>Instructions to Bidders:</strong><br>
                    1. Please fill in your quoted unit price (VAT Inclusive) and committed delivery lead time in the designated columns.<br>
                    2. Quotations must remain valid for a minimum of thirty (30) calendar days from submission deadline.<br>
                    3. Return signed quotation via email to <strong id="docBuyerEmail">{{ auth()->user()->email ?? 'procurement@rms.ph' }}</strong> before the deadline.<br>
                    4. Special Instructions: <span id="docSpecialInstructions">Standard receiving quality standards apply.</span>
                </div>

                <div class="rfq-doc-signatures">
                    <div class="rfq-doc-sig-box">
                        <div class="rfq-doc-sig-title">Prepared By (Procurement Officer):</div>
                        <div style="margin-top: 20px; font-weight: 700; color: #0f172a;">{{ auth()->user()->name ?? 'System Administrator' }}</div>
                        <div>Date: <span id="docSignDate">--</span></div>
                    </div>
                    <div class="rfq-doc-sig-box">
                        <div class="rfq-doc-sig-title">Authorized Vendor Representative Signature:</div>
                        <div style="margin-top: 20px; color: #94a3b8; font-style: italic;">(Sign & Affix Company Seal)</div>
                        <div>Authorized Printed Name & Date</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="window.print()">
                <i class="ph ph-printer"></i> Print / Save as PDF
            </button>
            <button type="button" class="rfq-btn rfq-btn-primary" onclick="closeRfqDocumentPreview(); openSendEmailModal();">
                <i class="ph ph-paper-plane-tilt"></i> Proceed to Send Email
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: SEND QUOTATION TO EMAIL DISPATCH MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqSendEmailModal" role="dialog" aria-modal="true" aria-labelledby="sendEmailModalTitle">
    <div class="rfq-modal-card" style="max-width: 620px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="sendEmailModalTitle">
                <i class="ph ph-envelope-simple"></i>
                <span>Send Request for Quotation Document</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeSendEmailModal()" aria-label="Close Send Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <form id="rfqEmailForm" onsubmit="handleSendEmailSubmit(event)" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="rfq-field">
                    <label class="rfq-label" for="emailRecipient">
                        <span>Recipient Email (Vendor Contact) *</span>
                        <span class="rfq-label-badge">Required</span>
                    </label>
                    <input type="email" class="rfq-input" id="emailRecipient" required placeholder="vendor.rep@company.com">
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailCc">
                        <span>CC (Carbon Copy)</span>
                        <span class="rfq-label-badge">Internal Audit</span>
                    </label>
                    <input type="text" class="rfq-input" id="emailCc" value="procurement-audit@rms.ph, central.warehouse@rms.ph">
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailSubject">Subject Line *</label>
                    <input type="text" class="rfq-input" id="emailSubject" required>
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailBodyText">Cover Message & Bidding Notice</label>
                    <textarea class="rfq-textarea" id="emailBodyText" style="min-height: 110px;"></textarea>
                </div>

                <!-- Attached Preview Attachment Badge -->
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="ph ph-file-pdf" style="font-size: 26px; color: #dc2626;"></i>
                        <div>
                            <div style="font-weight: 700; font-size: 0.85rem;" id="emailAttachmentName">RFQ-2026-0042_Request_for_Quotation.pdf</div>
                            <div style="font-size: 0.75rem; color: #64748b;">Formal System-Generated RFQ Document Preview (Ready)</div>
                        </div>
                    </div>
                    <button type="button" class="rfq-btn rfq-btn-outline" style="padding: 4px 10px; font-size: 0.78rem;" onclick="closeSendEmailModal(); openRfqDocumentPreview();">
                        <i class="ph ph-eye"></i> View Attachment
                    </button>
                </div>
            </form>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeSendEmailModal()">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnConfirmSendEmail" onclick="executeEmailDispatch()">
                <i class="ph ph-paper-plane-tilt"></i> Dispatch Email Now
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: ITEM MASTER QUICK SELECT MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqItemMasterModal" role="dialog" aria-modal="true" aria-labelledby="itemMasterModalTitle">
    <div class="rfq-modal-card" style="max-width: 760px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="itemMasterModalTitle">
                <i class="ph ph-database"></i>
                <span>Item Master Catalog Quick Picker</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeItemMasterQuickSelectModal()" aria-label="Close Picker">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="margin-bottom: 14px;">
                <input type="text" class="rfq-input" id="itemMasterSearchInput" oninput="filterItemMasterModalList(this.value)" placeholder="Search item by SKU, Name, or Category (e.g. Arabica, Milk, Flour)..." autofocus>
            </div>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--rfq-border-subtle); border-radius: 8px;">
                <table class="rfq-table" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">SKU</th>
                            <th>Item Name & Specifications</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 110px; text-align: right;">Benchmark</th>
                            <th style="width: 90px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemMasterModalTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeItemMasterQuickSelectModal()">Done</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 4: RECENT RFQ DISPATCH LOG & HISTORY
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqHistoryModal" role="dialog" aria-modal="true" aria-labelledby="historyModalTitle">
    <div class="rfq-modal-card" style="max-width: 820px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="historyModalTitle">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Recent Requests for Quotation History Log</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeRecentRfqHistoryModal()" aria-label="Close History">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--rfq-border-subtle); border-radius: 8px;">
                <table class="rfq-table" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th style="width: 130px;">RFQ #</th>
                            <th>Vendor Name</th>
                            <th style="width: 120px;">Date Sent</th>
                            <th style="width: 80px; text-align: center;">Items</th>
                            <th style="width: 110px; text-align: right;">Est. Value</th>
                            <th style="width: 100px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="rfqHistoryTbody">
                        <!-- Populated dynamically from localStorage -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeRecentRfqHistoryModal()">Close</button>
        </div>
    </div>
<!-- ==========================================================================
     MODAL 5: MANAGER RFQ APPROVAL & PO TRANSFER DIALOG
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqApprovalModal" role="dialog" aria-modal="true" aria-labelledby="approvalModalTitle">
    <div class="rfq-modal-card" style="max-width: 700px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="approvalModalTitle">
                <i class="ph ph-seal-check" style="color: var(--rfq-teal);"></i>
                <span>Manager RFQ Approval & PO Authorization</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeApprovalModal()" aria-label="Close Approval Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div id="approvalStatusBanner" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="font-weight: 800; font-size: 0.95rem; color: #166534;" id="approvalRfqTitle">RFQ-2026-XXXX • Vendor Partner</div>
                <div style="font-size: 0.8rem; color: #15803d; margin-top: 2px;" id="approvalBannerSubtitle">
                    Authorized Procurement Managers can toggle RFQ approval, record evaluation notes, or award and generate an official Purchase Order.
                </div>
            </div>

            <!-- Pending Approval Input Section -->
            <div id="approvalInputSection">
                <div class="rfq-form-row" style="margin-bottom: 14px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalOfficer">Approving Manager *</label>
                        <input type="text" class="rfq-input" id="approvalOfficer" value="{{ auth()->user()->name ?? 'Procurement Manager' }}" required>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalPoDeliveryDate">Committed Delivery Date *</label>
                        <input type="date" class="rfq-input" id="approvalPoDeliveryDate" required>
                    </div>
                </div>

                <div class="rfq-form-row" style="margin-bottom: 14px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalPaymentTerms">Agreed Payment Terms</label>
                        <select class="rfq-select" id="approvalPaymentTerms">
                            <option value="Net 30 Days">Net 30 Days (Standard)</option>
                            <option value="Net 15 Days">Net 15 Days</option>
                            <option value="COD">Cash on Delivery (COD)</option>
                            <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalDestination">Receiving Facility / Dock</label>
                        <input type="text" class="rfq-input" id="approvalDestination" value="Central Commissary - Main Dock">
                    </div>
                </div>

                <div class="rfq-field" style="margin-bottom: 14px;">
                    <label class="rfq-label" for="approvalNotes">Approval Justification / Bidding Evaluation Notes</label>
                    <textarea class="rfq-textarea" id="approvalNotes" placeholder="e.g. Evaluated lowest compliant bidder. Quality standards certified. Approved for PO authorization." style="min-height: 75px;"></textarea>
                </div>
            </div>

            <!-- Already Approved Details Box (Shown when active) -->
            <div id="alreadyApprovedDetailsSection" style="display: none; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: 800; font-size: 0.9rem; color: #047857; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-check-circle-fill"></i> Manager Approval Granted
                    </span>
                    <span id="approvedTimestampBadge" style="font-size: 0.75rem; color: #065f46; font-weight: 600;"></span>
                </div>
                <div style="font-size: 0.82rem; color: #064e3b;" id="approvedOfficerNameText"></div>
                <div style="font-size: 0.8rem; color: #065f46; margin-top: 6px; font-style: italic;" id="approvedNotesText"></div>
                <div id="approvedPoLinkedBadge" style="margin-top: 10px; display: none;"></div>
            </div>

            <!-- Items & Financial Summary Box -->
            <div style="background: #f8fafc; border: 1px solid var(--rfq-border-subtle); border-radius: 8px; padding: 12px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--rfq-text-muted);">Line Items Count:</span>
                    <strong id="approvalItemCount" style="color: var(--rfq-text-strong);">0 lines</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                    <span style="font-size: 0.88rem; font-weight: 800; color: var(--rfq-text-strong);">Total Approved Purchase Order Value:</span>
                    <strong id="approvalTotalValue" style="font-size: 1.15rem; color: var(--rfq-teal); font-variant-numeric: tabular-nums;">₱0.00</strong>
                </div>
            </div>
        </div>
        <div class="rfq-modal-footer" id="approvalModalFooter">
            <!-- Dynamic action buttons rendered via openManagerApprovalModal -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 6: SINGLE-ITEM PROCUREMENT AUDIT TRAIL MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqSingleAuditModal" role="dialog" aria-modal="true" aria-labelledby="singleAuditModalTitle">
    <div class="rfq-modal-card" style="max-width: 760px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="singleAuditModalTitle">
                <i class="ph ph-clock-counter-clockwise" style="color: var(--rfq-primary);"></i>
                <span id="singleAuditHeaderTitle">Audit Trail & History</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeSingleAuditModal()" aria-label="Close Audit Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong id="singleAuditRefTitle" style="font-size: 1rem; color: var(--rfq-text-strong);">RFQ-2026-XXXX</strong>
                    <div id="singleAuditSubTitle" style="font-size: 0.8rem; color: var(--rfq-text-muted);">Procurement Activity Log</div>
                </div>
                <span class="audit-badge badge-approved" id="singleAuditStatusBadge">Audited Record</span>
            </div>

            <!-- Single Item Chronological Timeline -->
            <div class="audit-timeline-container" id="singleAuditTimelineContainer">
                <!-- Dynamically rendered -->
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeSingleAuditModal()">Close Audit Log</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 7: CREATE RFQ ALTERNATIVES (MULTI-VENDOR SOURCING)
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqCreateAlternativeModal" role="dialog" aria-modal="true" aria-labelledby="altModalTitle">
    <div class="rfq-modal-card" style="max-width: 740px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="altModalTitle">
                <i class="ph ph-git-fork" style="color: #9333ea;"></i>
                <span>Create RFQ Alternatives (Multi-Vendor Sourcing)</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeCreateAlternativeModal()" aria-label="Close Alternatives Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <!-- Context Banner -->
            <div class="rfq-alt-context-banner">
                <div style="font-weight: 800; font-size: 0.92rem; color: #581c87; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-info"></i>
                    <span>Tender Package Duplication & Line Parity</span>
                </div>
                <div style="font-size: 0.8rem; color: #6b21a8; margin-top: 4px; line-height: 1.45;">
                    Select one or more alternative suppliers below. The system will automatically clone all line items, requested quantities, and benchmark specifications into sibling RFQ drafts linked by an immutable bidding package ID.
                </div>
                <div class="rfq-alt-base-info-row" id="altBaseInfoRow">
                    <div><strong>Base RFQ:</strong> <span id="altBaseRfqNum">--</span></div>
                    <div><strong>Current Vendor:</strong> <span id="altBaseVendorName">--</span></div>
                    <div><strong>Items to Clone:</strong> <span id="altBaseItemCount">0 items</span></div>
                </div>
            </div>

            <!-- Search & Category Filters -->
            <div style="display: flex; gap: 10px; margin-bottom: 12px; align-items: center;">
                <div style="position: relative; flex: 1;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                    <input type="text" class="rfq-input" id="altVendorSearchInput" oninput="filterAltVendorsList(this.value)" placeholder="Search supplier by name, code, or category..." style="padding-left: 30px; font-size: 12px; height: 34px;">
                </div>
                <div class="rfq-alt-category-chips" id="altCategoryChips">
                    <button type="button" class="audit-filter-pill active" onclick="filterAltVendorCategory('ALL', this)">All</button>
                    <button type="button" class="audit-filter-pill" onclick="filterAltVendorCategory('Raw Ingredients', this)">Raw Ingredients</button>
                    <button type="button" class="audit-filter-pill" onclick="filterAltVendorCategory('Packaging', this)">Packaging</button>
                    <button type="button" class="audit-filter-pill" onclick="filterAltVendorCategory('Beverages', this)">Beverages</button>
                </div>
            </div>

            <!-- Vendor Selection List -->
            <div class="rfq-alt-vendor-list-container" id="altVendorChecklistContainer">
                <!-- Dynamically populated with vendor cards -->
            </div>

            <!-- Planned Sibling Generation Preview Box -->
            <div class="rfq-alt-preview-box" id="altSiblingsPreviewBox" style="display: none;">
                <div style="font-size: 0.76rem; font-weight: 700; color: #475569; text-transform: uppercase; margin-bottom: 6px;">
                    Planned Sibling RFQ Generations:
                </div>
                <div class="rfq-alt-preview-pills" id="altPreviewPills"></div>
            </div>
        </div>
        <div class="rfq-modal-footer" style="display: flex; justify-content: space-between; align-items: center;">
            <div style="font-size: 0.82rem; font-weight: 600; color: #64748b;" id="altSelectedVendorsCounter">
                0 alternative vendor(s) selected
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeCreateAlternativeModal()">Cancel</button>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnConfirmCreateAlt" onclick="submitCreateAlternatives()" disabled style="display: inline-flex; align-items: center; gap: 6px;">
                    <i class="ph ph-git-fork"></i>
                    <span>Generate Draft Alternatives</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 8: VENDOR PRICE COMPARISON MATRIX & BID TABULATION
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqCompareBidsModal" role="dialog" aria-modal="true" aria-labelledby="compareModalTitle">
    <div class="rfq-modal-card rfq-compare-modal-card" style="max-width: 95vw; width: 1360px; height: 92vh; display: flex; flex-direction: column;">
        <div class="rfq-modal-header" style="flex-shrink: 0;">
            <div class="rfq-modal-title" id="compareModalTitle">
                <i class="ph ph-scales" style="color: #0d9488;"></i>
                <span>Vendor Price Comparison Matrix &amp; Bid Tabulation</span>
                <span class="rfq-tab-badge" id="matrixPackageBadge" style="background: rgba(13, 148, 136, 0.15); color: #0d9488; font-weight: 700; margin-left: 8px;">Package</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <!-- Award Mode Switcher -->
                <div class="rfq-award-mode-toggle">
                    <span style="font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Award Mode:</span>
                    <button type="button" class="rfq-mode-btn active" id="btnModeSplitAward" onclick="setAwardMode('SPLIT')" title="Cherry-pick lowest price per item across multiple vendors">
                        <i class="ph ph-arrows-split"></i> <span>Split Award (Cherry-Pick)</span>
                    </button>
                    <button type="button" class="rfq-mode-btn" id="btnModeFullAward" onclick="setAwardMode('FULL')" title="Award entire tender package to one winning vendor">
                        <i class="ph ph-trophy"></i> <span>Full Award</span>
                    </button>
                </div>
                <button type="button" class="rfq-modal-close" onclick="closeCompareBidsModal()" aria-label="Close Comparison Modal">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <div class="rfq-modal-body" style="flex: 1; overflow-y: auto; padding: 14px 20px; display: flex; flex-direction: column; gap: 14px;">
            <!-- KPI Summary Strip -->
            <div class="rfq-matrix-kpi-strip">
                <div class="rfq-matrix-kpi-card">
                    <span class="label">Benchmark Budget</span>
                    <div class="val" id="matrixBudgetTotal">₱0.00</div>
                    <span class="sub" id="matrixBudgetSub">Target Cost</span>
                </div>
                <div class="rfq-matrix-kpi-card highlight-green">
                    <span class="label">Lowest Combined Bid</span>
                    <div class="val" id="matrixLowestCombined">₱0.00</div>
                    <span class="sub" id="matrixSavingsSub">Max Potential Savings</span>
                </div>
                <div class="rfq-matrix-kpi-card highlight-teal">
                    <span class="label">Active Selection PO Value</span>
                    <div class="val" id="matrixSelectedTotal">₱0.00</div>
                    <span class="sub" id="matrixSelectedVendorsCount">0 vendors selected</span>
                </div>
                <div class="rfq-matrix-kpi-card">
                    <span class="label">Legend &amp; Visual Badges</span>
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                        <span class="bid-badge lowest-price"><i class="ph ph-trend-down"></i> Lowest Price</span>
                        <span class="bid-badge fastest-lead"><i class="ph ph-lightning"></i> Fastest Lead Time</span>
                    </div>
                </div>
            </div>

            <!-- Comparison Matrix Scrollable Table Container -->
            <div class="rfq-matrix-table-wrap">
                <table class="rfq-matrix-table" id="rfqMatrixTable">
                    <thead id="rfqMatrixThead">
                        <!-- Populated dynamically: 2 rows (Vendor Summary Headers + Item Column Headers) -->
                    </thead>
                    <tbody id="rfqMatrixTbody">
                        <!-- Populated dynamically with side-by-side row items -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sticky Footer with Action Controls -->
        <div class="rfq-modal-footer" style="flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--rfq-border-subtle); background: #ffffff;">
            <div class="rfq-matrix-footer-summary">
                <div style="font-weight: 700; font-size: 0.88rem; color: #0f172a;" id="matrixFooterStatusText">
                    10 of 10 line items selected for awarding
                </div>
                <div style="font-size: 0.78rem; color: #64748b;" id="matrixFooterVendorBreakdown">
                    Calculating Purchase Order breakdown...
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeCompareBidsModal()">Cancel</button>
                <button type="button" class="rfq-btn rfq-btn-primary" id="btnConfirmAwardAndPo" onclick="executeBiddingAward()" style="background: linear-gradient(135deg, #0d9488, #059669); font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="ph ph-check-circle"></i>
                    <span>Confirm Award &amp; Generate Purchase Orders</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 9: TENDER AWARD SUCCESS & PO GENERATION SUMMARY
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqAwardSuccessModal" role="dialog" aria-modal="true" aria-labelledby="awardSuccessModalTitle">
    <div class="rfq-modal-card" style="max-width: 680px;">
        <div class="rfq-modal-header" style="background: #f0fdf4; border-bottom: 1px solid #bbf7d0;">
            <div class="rfq-modal-title" id="awardSuccessModalTitle" style="color: #166534;">
                <i class="ph ph-check-circle-fill" style="color: #16a34a; font-size: 24px;"></i>
                <span>Tender Awarding Completed Successfully</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeAwardSuccessModal()" aria-label="Close Success Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="font-weight: 800; font-size: 0.95rem; color: #065f46;">Official Purchase Order(s) Issued</div>
                <div style="font-size: 0.8rem; color: #047857; margin-top: 3px;">
                    Winning items have been committed into binding Purchase Orders with status <strong>Approved / Issued</strong>. Non-awarded vendor tender quotes in this package have been archived with status <strong>Closed</strong>.
                </div>
            </div>

            <div style="font-size: 0.78rem; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 8px;">
                Generated Purchase Orders:
            </div>
            <div id="awardSuccessPoListContainer" style="display: flex; flex-direction: column; gap: 8px; max-height: 280px; overflow-y: auto;">
                <!-- Populated dynamically -->
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeAwardSuccessModal()">Close</button>
            <a href="{{ route('purchase.purchase-orders') }}" class="rfq-btn rfq-btn-primary" style="text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="ph ph-shopping-cart"></i>
                <span>Go to Purchase Orders Workspace</span>
            </a>
        </div>
    </div>
</div>

@endsection

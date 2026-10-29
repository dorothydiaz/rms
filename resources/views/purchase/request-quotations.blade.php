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
            <div class="rfq-items-toolbar" style="overflow-x: auto; scrollbar-width: none;">
                <div class="rfq-toolbar-left" style="flex: 1; flex-wrap: nowrap; gap: 8px; min-width: 0;">
                    <div style="position: relative; flex: 1; min-width: 220px; max-width: 360px;">
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
                                <button type="button" class="rfq-batch-btn" id="btnBatchArchiveRfq" onclick="executeBatchArchive()" title="Archive selected tenders">
                                    <i class="ph ph-archive"></i>
                                    <span>Archive</span>
                                </button>
                                <button type="button" class="rfq-batch-btn" id="btnBatchExportRfq" onclick="executeBatchExport()" title="Export selected tenders to CSV">
                                    <i class="ph ph-download-simple"></i>
                                    <span>Export</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="rfq-toolbar-right" style="position: relative; display: flex; align-items: center; gap: 8px;">
                    <!-- Column Visibility Filter Trigger -->
                    <div style="position: relative;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnDirectoryColFilter" onclick="toggleRfqTableSettingsDropdown(event)" data-tooltip="Customize visible columns & order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                            <i class="ph ph-columns"></i>
                            <span>Columns</span>
                            <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                        </button>
                    </div>

                    <button type="button" class="rfq-btn rfq-btn-primary" onclick="startNewRfqFromDirectory()" style="padding: 6px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i> <span>Create RFQ</span>
                    </button>
                </div>
            </div>

            <!-- RFQ Directory Table -->
            <div class="rfq-table-responsive">
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

    <!-- RFQ Procurement Lifecycle Header Bar: Buttons & Chevron Progress Bar Side-by-Side (Ref Image 2) -->
    <div class="rfq-lifecycle-bar-container stage-theme-draft" id="rfqLifecycleStepperWrap">
        <!-- Left: Action Buttons Group for Current Stage -->
        <div class="rfq-stage-actions-group" id="rfqStageActionsGroup">
            <!-- Rendered dynamically by updateLifecycleProgressBar() -->
            <button type="button" class="rfq-stage-action-btn rfq-btn-stage-attach" onclick="openRfqAttachedFilesModal()" title="View & Attach Technical Specs, Drawings & Documents">
                <i class="ph ph-paperclip"></i>
                <span>Attached Files</span>
                <span class="rfq-stage-badge-count" id="rfqAttachedCountBadge">0</span>
            </button>
            <button type="button" class="rfq-stage-action-btn rfq-btn-stage-submit" onclick="submitActiveRfqToVendor()" title="Submit Tender for Vendor Bidding">
                <i class="ph ph-paper-plane-tilt"></i>
                <span>Submit</span>
            </button>
            <button type="button" class="rfq-stage-action-btn rfq-btn-stage-cancel" onclick="cancelRfqDraft()" title="Cancel / Clear Current Draft">
                <i class="ph ph-x-circle"></i>
                <span>Cancel</span>
            </button>
        </div>

        <!-- Right: Chevron Pipeline Progress Bar (Side-by-Side) -->
        <div class="rfq-chevron-pipeline" id="rfqLifecycleProgressBar">
            <!-- Stage 1: Drafting -->
            <div class="rfq-chevron-step is-active stage-draft" id="stepIndicatorDraft" data-stage="Draft" onclick="handleStepClick('Draft')">
                <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">1</span></div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">Drafting</span>
                    <span class="rfq-chevron-desc">Preparation</span>
                </div>
            </div>

            <!-- Stage 2: Submitted -->
            <div class="rfq-chevron-step stage-submitted" id="stepIndicatorSubmitted" data-stage="Submitted" onclick="handleStepClick('Submitted')">
                <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">2</span></div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">Submitted</span>
                    <span class="rfq-chevron-desc">Vendor Quotes</span>
                </div>
            </div>

            <!-- Stage 3: Approval -->
            <div class="rfq-chevron-step stage-approval" id="stepIndicatorApproval" data-stage="Approval" onclick="handleStepClick('Approval')">
                <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">3</span></div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title">Approval</span>
                    <span class="rfq-chevron-desc">Sign-Off</span>
                </div>
            </div>

            <!-- Stage 4: Status -->
            <div class="rfq-chevron-step stage-status" id="stepIndicatorStatus" data-stage="Status" onclick="handleStepClick('Status')">
                <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">4</span></div>
                <div class="rfq-chevron-info">
                    <span class="rfq-chevron-title" id="stepStatusTitle">Status</span>
                    <span class="rfq-chevron-desc" id="stepStatusDesc">Award / PO</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stage Subtitle & Context Ribbon (Unobtrusive context bar) -->
    <div class="rfq-stage-helper-ribbon stage-draft" id="rfqStageBanner">
        <div class="rfq-stage-helper-left">
            <span class="rfq-stage-helper-icon" id="rfqStageBannerIcon"><i class="ph ph-pencil-simple-line"></i></span>
            <span class="rfq-stage-helper-title" id="rfqStageBannerTitle">Stage 1: Drafting</span>
            <span class="rfq-stage-helper-sep">&bull;</span>
            <span class="rfq-stage-helper-desc" id="rfqStageBannerSub">For internal preparation, gathering info, and consulting.</span>
        </div>
        <div class="rfq-stage-helper-right" id="rfqStageBannerActions">
            <!-- Stage quick indicator badge -->
            <span class="rfq-stage-indicator-pill draft" id="rfqActiveStagePill">
                <i class="ph ph-circle-dashed"></i> Drafting Mode
            </span>
        </div>
    </div>

    <!-- Top Section (Full Width - 100%): Main Reference & Operational Header Banner -->
    <div class="rfq-panel-card rfq-top-banner-card" id="rfqTopBannerCard">
        <!-- 1. Top Identity & Status Ribbon Strip (Guarantees zero wrapping, zero collision) -->
        <div class="rfq-top-identity-row">
            <div class="rfq-ref-identity-box">
                <div class="rfq-ref-badge-icon">
                    <i class="ph ph-file-text"></i>
                </div>
                <div class="rfq-ref-meta-cluster">
                    <span class="rfq-ref-label">Tender Reference #</span>
                    <div class="rfq-ref-value-row">
                        <span id="rfqRefDisplay" class="rfq-ref-id-display">RFQ-2026-0044</span>
                        <input type="hidden" id="rfqNumberInput" value="RFQ-2026-0044">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm rfq-copy-ref-btn" onclick="copyRfqReference()" title="Copy Reference to Clipboard">
                            <i class="ph ph-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="rfq-status-badge-wrap">
                    <span class="status-pill draft" id="rfqStatusBadge">
                        <i class="ph ph-dot"></i> Draft RFQ
                    </span>
                </div>
            </div>

            <div class="rfq-identity-meta-right">
                <a href="/purchase/vendor-masterlist" id="rfqVendorDirectLink" class="rfq-vendor-label-link" target="_blank" style="display: none;" title="Open Vendor Profile in Vendor Masterlist">
                    <i class="ph ph-arrow-square-out"></i> Vendor Masterlist
                </a>
            </div>
        </div>

        <!-- 2. Operational Form Parameters Grid (Clean, Responsive Multi-Column Grid) -->
        <div class="rfq-top-banner-split rfq-top-banner-grid">
            <!-- Row 1, Col 1: Vendor Selection * (Masterlist Combobox + Redirect) -->
            <div class="rfq-field rfq-vendor-col">
                <label class="rfq-label" for="vendorSelect">
                    <span>Vendor Selection *</span>
                    <span class="rfq-label-badge">Masterlist Search</span>
                </label>
                <!-- Hidden select for form bindings & backwards-compatibility -->
                <select class="rfq-select" id="vendorSelect" style="display: none;" onchange="handleVendorSelection(this.value)">
                    <option value="">-- Choose Approved Vendor from Masterlist --</option>
                </select>

                <!-- Custom Searchable Combobox with ">" Redirect Button -->
                <div class="rfq-vendor-combo-wrapper">
                    <div class="rfq-vendor-combobox" id="rfqVendorCombobox">
                        <button type="button" class="rfq-vendor-combo-trigger" id="rfqVendorComboTrigger" onclick="toggleVendorCombobox(event)">
                            <span id="rfqVendorComboTriggerText" style="color: #64748b;">-- Choose Approved Vendor from Masterlist --</span>
                            <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8;"></i>
                        </button>

                        <div class="rfq-vendor-combo-dropdown" id="rfqVendorComboDropdown">
                            <div class="rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                <i class="ph ph-magnifying-glass"></i>
                                <input type="text" class="rfq-vendor-combo-search-input" id="rfqVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterVendorCombobox(this.value)" autocomplete="off">
                            </div>
                            <div class="rfq-vendor-combo-list" id="rfqVendorComboList"></div>
                        </div>
                    </div>

                    <!-- Redirect to Vendor Masterlist Button ">" -->
                    <a href="/purchase/vendor-masterlist" id="rfqVendorRedirectBtn" class="rfq-vendor-redirect-btn" target="_blank" title="Redirect to Vendor Details in Vendor Masterlist" onclick="return handleVendorRedirectClick(event)">
                        <i class="ph ph-caret-right"></i>
                    </a>
                </div>
            </div>

            <!-- Row 1, Col 2: Notes / Tender Purpose Description -->
            <div class="rfq-field rfq-notes-field">
                <label class="rfq-label" for="rfqTenderTitle">
                    <span>Notes / Tender Purpose</span>
                    <span class="rfq-label-badge">Description</span>
                </label>
                <input type="text" class="rfq-input rfq-title-input" id="rfqTenderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" oninput="handleRfqTitleChange(this.value)">
            </div>

            <!-- Row 2, Col 1: Destination / Location -->
            <div class="rfq-field rfq-destination-col">
                <label class="rfq-label" for="rfqDeliveryLocation">
                    <span>Destination / Location</span>
                    <span class="rfq-label-badge">Logistics</span>
                </label>
                <select class="rfq-select" id="rfqDeliveryLocation" onchange="syncFormInputsToState(); updateLiveDocumentMockup();">
                    <option value="Central Commissary - Main Dock">Central Commissary - Dock A</option>
                    <option value="Central Commissary - Main Cold Dock">Central Commissary - Main Cold Dock</option>
                    <option value="Central Warehouse - Dry Storage">Central Warehouse - Dry Storage</option>
                    <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                    <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                    <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                </select>
            </div>

            <!-- Row 2, Col 2: RFQ Terms -->
            <div class="rfq-field rfq-terms-col">
                <label class="rfq-label" for="rfqPaymentTerms">
                    <span>RFQ Terms</span>
                    <span class="rfq-label-badge">Commercial</span>
                </label>
                <select class="rfq-select" id="rfqPaymentTerms" onchange="syncFormInputsToState(); updateLiveDocumentMockup();">
                    <option value="Net 30 Days">Net 30 Days (Standard)</option>
                    <option value="Net 15 Days">Net 15 Days</option>
                    <option value="Net 7 Days">Net 7 Days</option>
                    <option value="COD">Cash on Delivery (COD)</option>
                    <option value="Advance Payment">100% Advance Payment</option>
                    <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                </select>
            </div>

            <!-- Row 2, Col 3: Created Date -->
            <div class="rfq-field rfq-created-col">
                <label class="rfq-label" for="rfqDateIssued">
                    <span>Created Date</span>
                    <span class="rfq-label-badge">Issued</span>
                </label>
                <input type="date" class="rfq-input" id="rfqDateIssued" onchange="syncFormInputsToState(); updateLiveDocumentMockup();">
            </div>

            <!-- Row 2, Col 4: Due Date * -->
            <div class="rfq-field rfq-due-col">
                <label class="rfq-label" for="rfqDueDate">
                    <span>Due Date *</span>
                    <span class="rfq-label-badge" style="color: #b45309;">Submission</span>
                </label>
                <input type="date" class="rfq-input" id="rfqDueDate" onchange="syncFormInputsToState(); updateLiveDocumentMockup();">
            </div>
        </div>

        <!-- Automated & Streamlined Hidden Inputs for State Integrity & Backward-Compatibility -->
        <input type="hidden" id="vendorContactPerson" value="">
        <input type="hidden" id="vendorContactTitle" value="">
        <input type="hidden" id="vendorEmail" value="">
        <input type="hidden" id="vendorPhone" value="">
        <input type="hidden" id="vendorAddress" value="">
        <input type="hidden" id="rfqQuoteValidityDate" value="">
        <input type="hidden" id="rfqExpectedDelivery" value="">
        <input type="hidden" id="rfqSpecialInstructions" value="">
        <span id="leadTimeBadge" style="display: none;"></span>
        <span id="rfqTermsLockBadge" style="display: none;"></span>

        <!-- Hidden backward-compatibility elements for retired vendor summary card -->
        <div id="vendorSummaryBox" style="display: none !important;">
            <span id="vendorAvatarText"></span>
            <span id="vendorTradeName"></span>
            <span id="vendorCategoryTag"></span>
            <span id="vendorTinDisplay"></span>
            <span id="vendorLocationDisplay"></span>
        </div>
        <div id="vendorEmptyPrompt" style="display: none !important;"></div>
        <span id="vendorCatalogBadge" style="display: none !important;"></span>
    </div>

    <!-- Main Body Split (70% Items Table Panel / 30% Sticky Live Document Mockup) -->
    <div class="rfq-builder-split-body" id="rfqBuilderSplitBody">

        <!-- Left Side (70% Width): Line Items & Specifications Table Panel -->
        <div class="rfq-items-panel-col">
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
                <div class="rfq-summary-bar">
                    <div class="rfq-metrics-cluster">
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
                    <div class="rfq-table-hint">
                        <i class="ph ph-broadcast"></i> Live synced with Mockup
                    </div>
                </div>
            </div>
        </div><!-- /.rfq-items-panel-col -->

        <!-- Right Side (30% Width): Sticky Live Document Mockup & Accessible Action Buttons -->
        <div class="rfq-mockup-panel-col" id="rfqMockupPanelCol">
            <div class="rfq-panel-card rfq-mockup-card">
                <!-- Action Buttons Header: Strictly Single Row via 3-Column Grid -->
                <div class="rfq-mockup-header-actions">
                    <button type="button" class="rfq-btn rfq-btn-outline rfq-mockup-action-btn" id="btnToggleBuilderAudit" onclick="openRfqAuditDrawer()" title="View Procurement Audit Trail & Activity Log">
                        <i class="ph ph-clock-counter-clockwise"></i>
                        <span>Audit Trail</span>
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-outline rfq-mockup-action-btn" onclick="openRfqDocumentPreview()" title="Open formal printable RFQ document preview (F8)">
                        <i class="ph ph-eye"></i>
                        <span>Preview</span>
                        <span class="rfq-hotkey-badge">F8</span>
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary rfq-mockup-action-btn" id="btnDispatchEmail" onclick="openSendEmailModal()" title="Send RFQ package directly to vendor contact (F10)">
                        <i class="ph ph-paper-plane-tilt"></i>
                        <span>Send to Vendor</span>
                        <span class="rfq-hotkey-badge rfq-hotkey-contrast">F10</span>
                    </button>
                </div>

                <!-- Sticky Mockup Sheet Container with Dedicated Scrollbar -->
                <div class="rfq-mockup-content-wrap" id="rfqMockupContentWrap">
                    <div class="rfq-mockup-top-strip">
                        <div class="rfq-mockup-pill-tag">
                            <i class="ph ph-file-text"></i>
                            <span>Mock up of the Request</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="rfq-mockup-pages-badge" id="mockupPageCountBadge">1 Page</span>
                            <span class="rfq-mockup-live-status">
                                <span class="rfq-live-dot"></span> Live Sync
                            </span>
                        </div>
                    </div>

                    <!-- Rendered Document Mockup Paper Sheet Container (Multi-Page Capable) -->
                    <div class="rfq-live-mockup-container" id="rfqLiveMockupSheet">
                        <!-- Populated dynamically via updateLiveDocumentMockup() -->
                    </div>
                </div><!-- /.rfq-mockup-content-wrap -->
            </div><!-- /.rfq-mockup-card -->
        </div><!-- /.rfq-mockup-panel-col -->

    </div><!-- /.rfq-builder-split-body -->
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
<!-- ==========================================================================
     MODAL 7: ATTACHED FILES & SPECIFICATIONS MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqAttachedFilesModal" role="dialog" aria-modal="true" aria-labelledby="attachedFilesModalTitle">
    <div class="rfq-modal-card" style="max-width: 660px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="attachedFilesModalTitle">
                <i class="ph ph-paperclip" style="color: #9333ea;"></i>
                <span>Tender File Attachments &amp; Specifications</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeRfqAttachedFilesModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="background: #faf5ff; border: 1px solid #e9d5ff; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <div style="font-weight: 800; font-size: 0.9rem; color: #6b21a8;" id="attachedFilesModalRfqRef">RFQ-2026-0044</div>
                    <div style="font-size: 0.76rem; color: #7e22ce;">Attach bill of quantities, technical drawings, spec sheets, or consulting notes.</div>
                </div>
                <span class="status-pill draft" id="attachedFilesStageBadge">Drafting</span>
            </div>

            <!-- Upload Dropzone -->
            <div class="rfq-file-dropzone" id="rfqAttachmentDropzone" onclick="document.getElementById('rfqAttachmentFileInput').click()">
                <input type="file" id="rfqAttachmentFileInput" style="display: none;" multiple onchange="handleRfqFileUpload(event)" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.zip">
                <div class="rfq-dropzone-icon"><i class="ph ph-cloud-arrow-up"></i></div>
                <div style="font-size: 0.88rem; font-weight: 700; color: #334155;">Click or Drag files to attach</div>
                <div style="font-size: 0.75rem; color: #94a3b8; margin-top: 2px;">PDF, Word, Excel, Images, ZIP (up to 25MB each)</div>
            </div>

            <!-- Attached Files List -->
            <div style="margin-top: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-size: 0.78rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.04em;">Current Attachments</span>
                    <span style="font-size: 0.75rem; color: #64748b;" id="attachedFilesCountLabel">0 files</span>
                </div>
                <div id="attachedFilesListContainer" style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                    <!-- Rendered dynamically -->
                </div>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-primary" onclick="closeRfqAttachedFilesModal()" style="padding: 6px 20px;">Done</button>
        </div>
    </div>
</div>

@endsection

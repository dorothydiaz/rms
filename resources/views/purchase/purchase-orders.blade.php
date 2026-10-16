@extends('layouts.app')

@section('title', 'Purchase Orders (PO) - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/purchase-orders.css')
@endpush

@push('blade-data')
<script>
    window.PurchaseOrderConfig = Object.freeze({
        routes: {
            ordersCreate: @json(route('purchase.api.orders.create')),
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
<div class="po-workspace-container" id="poAppWorkspace">

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="poToastContainer"></div>

    <!-- Top Action Header / Breadcrumb (HR Operations Standard - Clean Identity) -->
    <div class="hr-parent-title" style="margin-bottom: 2px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8); flex-shrink: 0;">
                <i class="ph ph-receipt"></i>
            </div>
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2;">Purchase Orders (PO)</h1>
                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0 0;">Official procurement commitments, receiving dock tracking & vendor settlement</p>
            </div>
        </div>
    </div>

    <!-- PO Navigation Tabs Bar -->
    <nav class="po-nav-tabs-bar" id="poTabsBar">
        <button type="button" class="po-tab-btn active" id="tabBtnPoList" data-tab="tab-po-list" onclick="switchPoTab('tab-po-list')">
            <i class="ph ph-receipt"></i>
            <span>PO Directory & Status Tracker</span>
            <span class="po-tab-count" id="tabPoCount">0</span>
        </button>
        <button type="button" class="po-tab-btn" id="tabBtnPoBuilder" data-tab="tab-po-builder" onclick="switchPoTab('tab-po-builder')">
            <i class="ph ph-plus-circle"></i>
            <span id="tabPoBuilderTitle">Purchase Order Builder</span>
            <span class="po-tab-badge" id="tabPoBuilderBadge">New PO</span>
        </button>
    </nav>

    <!-- ====================================================================
         TAB 1: PO DIRECTORY & STATUS TRACKER
         ==================================================================== -->
    <div class="po-tab-pane active" id="pane-tab-po-list">
        <!-- KPI Metrics Grid -->
        <div class="po-kpi-grid">
            <div class="po-kpi-card" onclick="filterPoStatus('all')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Total Purchase Orders</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb;"><i class="ph ph-files"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoTotal">0</div>
                <div class="po-kpi-sub">Total procurement commitments</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Approved / Issued')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Pending Delivery</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #fff7ed, #ffedd5); color: #ea580c;"><i class="ph ph-truck"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPending">0</div>
                <div class="po-kpi-sub">Issued orders awaiting receiving dock</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Fully Received')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Fully Received</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoReceived">0</div>
                <div class="po-kpi-sub">Inventoried and stocked in commissary</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoPayment('Paid in Full / Cash Out')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Settled / Paid</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); color: #7c3aed;"><i class="ph ph-currency-dollar"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPaid">0</div>
                <div class="po-kpi-sub">Financial cash-out / settled invoices</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="po-panel-card">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--po-border-subtle); flex-wrap: nowrap; gap: 10px; overflow-x: auto; scrollbar-width: none;">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; flex-wrap: nowrap; min-width: 0;">
                    <div style="position: relative; flex: 1; min-width: 220px; max-width: 360px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px;"></i>
                        <input type="text" class="po-input" id="poSearchInput" oninput="handlePoSearch(this.value)" placeholder="Search PO #, vendor, RFQ ref, stall..." style="padding-left: 34px; height: 32px; font-size: 0.82rem;">
                    </div>
                    <div id="poActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Column Visibility Selector Dropdown -->
                    <div style="position: relative;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnPoColConfig" onclick="togglePoColumnConfigDropdown(event)" style="padding: 4px 10px; font-size: 11.5px; height: 32px;" title="Customize visible table columns">
                            <i class="ph ph-columns"></i> Columns <i class="ph ph-caret-down" style="font-size: 10px;"></i>
                        </button>
                        <div class="po-col-dropdown" id="poColConfigDropdown" style="display: none;" onclick="event.stopPropagation()">
                            <div style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-weight: 700; font-size: 12px; color: #0f172a; display: flex; align-items: center; gap: 5px;">
                                        <i class="ph ph-funnel" style="color: #9333ea;"></i> Column Visibility
                                    </span>
                                    <button type="button" class="inv-col-quick-link is-reset" onclick="resetPoColumnWidths()" title="Reset all column widths to defaults" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Widths
                                    </button>
                                </div>
                                <div class="inv-col-dropdown-quick-links" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <button type="button" class="inv-col-quick-link" onclick="showAllPoColumns()" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-check-square"></i> Show All
                                    </button>
                                    <button type="button" class="inv-col-quick-link" onclick="resetPoColumnDefaults()" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-columns"></i> Defaults
                                    </button>
                                </div>
                            </div>
                            <div class="po-col-checklist" id="poColChecklist">
                                <!-- Populated dynamically -->
                            </div>
                            <div style="padding: 6px 12px; border-top: 1px solid #f1f5f9; background: #fafafa; font-size: 11px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
                                <span id="poColActiveCounter">0 visible</span>
                                <span>Drag headers to resize</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openImportRfqModal()" title="Convert Awarded RFQ to PO" style="padding: 4px 10px; font-size: 11.5px; height: 32px;">
                        <i class="ph ph-link"></i> Import RFQ
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startNewPoFromDirectory()" style="padding: 4px 12px; font-size: 11.5px; height: 32px;">
                        <i class="ph ph-plus-circle"></i> + Create PO
                    </button>
                </div>
            </div>

            <!-- PO Directory Table -->
            <div class="po-table-responsive" style="overflow-x: auto;">
                <table class="po-table" id="poDirectoryTable">
                    <thead id="poDirectoryThead">
                        <tr id="poDirectoryTheadRow">
                            <!-- Populated dynamically via renderPoTableHeader -->
                        </tr>
                    </thead>
                    <tbody id="poDirectoryTbody">
                        <!-- Rendered dynamically -->
                    </tbody>
                </table>
                <div id="poHeaderFilterPopover" class="po-header-filter-popover" style="display: none;"></div>
            </div>

            <!-- Table Pagination Bar -->
            <div class="po-table-pagination-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid var(--po-border-subtle); flex-wrap: wrap; gap: 10px; font-size: 0.8rem; color: #64748b;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>Showing <strong id="poPaginationStart" style="color: #0f172a;">0</strong> to <strong id="poPaginationEnd" style="color: #0f172a;">0</strong> of <strong id="poPaginationTotal" style="color: #0f172a;">0</strong> purchase orders</span>
                    <span>•</span>
                    <label for="poPageSizeSelect" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem;">
                        <span>Per page:</span>
                        <select class="po-select" id="poPageSizeSelect" onchange="changePoPageSize(this.value)" style="height: 26px; padding: 2px 8px; font-size: 0.78rem; width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
                <div class="po-pagination-controls" id="poPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: CREATE NEW PURCHASE ORDER (PO BUILDER)
         ==================================================================== -->
    <div class="po-tab-pane" id="pane-tab-po-builder" style="display: none;">

        <!-- Command Bar -->
        <div class="po-command-bar">
            <div class="po-command-left">
                <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; letter-spacing: 0.08em; text-transform: uppercase; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-sliders-horizontal" style="color: var(--po-primary); font-size: 14px;"></i> PO Workspace
                </span>
            </div>

            <div class="po-actions-group" style="display: flex; align-items: center; gap: 6px;">
                <button type="button" class="po-btn po-btn-outline" onclick="switchPoTab('tab-po-list')" title="Back to PO Directory" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-arrow-left"></i> Directory
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="resetPoForm()" title="Clear form & start new PO" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
                <button type="button" class="po-btn po-btn-outline" id="btnTogglePoAudit" onclick="openPoAuditDrawer()" title="View Procurement Audit Trail & Activity Log" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-clock-counter-clockwise"></i> Audit Trail
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="openImportRfqModal()" title="Import lines from Awarded RFQ" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-link"></i> Import Awarded RFQ
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="openPoDocumentPreview()" title="Open formal printable PO preview (F8)" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-eye"></i> Preview <span class="po-hotkey-badge">F8</span>
                </button>
                <button type="button" class="po-btn po-btn-primary" onclick="issuePurchaseOrderSubmit()" title="Approve & save purchase order (F10)" style="padding: 4px 12px; font-size: 11.5px; background: linear-gradient(135deg, #0284c7, #0d9488);">
                    <i class="ph ph-check-circle"></i> Issue Purchase Order <span class="po-hotkey-badge" style="background: rgba(255,255,255,0.25);">F10</span>
                </button>
            </div>
        </div>

        <!-- Workspace Grid: Asymmetric 4.2fr Left (Cards 1 & 2) / 5.8fr Right (Card 3 Items Panel & Settlement) -->
        <div class="po-builder-workspace-grid">

            <!-- Left Column: Reference Header + Vendor Profile + PO Terms -->
            <div class="po-builder-left-col">

                <!-- Tender Reference & Identity Header Block (Prominent Placement with Description) -->
                <div class="po-panel-card po-tender-ref-card" style="border-left: 4px solid var(--po-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65)); padding: 10px 14px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 3px 10px rgba(168, 85, 247, 0.15);">
                                <i class="ph ph-receipt"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Purchase Order Reference Number</div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span id="poRefDisplay" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: var(--po-primary-dark); letter-spacing: -0.01em;">PO-2026-0105</span>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copyPoReference()" title="Copy Reference to Clipboard" style="padding: 2px 6px; font-size: 11px; height: 22px;">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                    <span id="poLinkedRfqPill" style="display: none; font-size: 11px; padding: 2px 7px; background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 5px; font-family: monospace; font-weight: 700;"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="status-pill draft" id="poStatusBadge" style="font-size: 11px; padding: 3px 9px;">
                                <i class="ph ph-dot"></i> Draft PO
                            </span>
                        </div>
                    </div>

                    <!-- PO Purpose / Description Field -->
                    <div class="po-field" style="margin-top: 2px;">
                        <label class="po-label" for="poOrderTitle" style="font-size: 0.70rem; color: #475569; margin-bottom: 3px;">
                            <span>Order Purpose / Title Description</span>
                            <span class="po-label-badge" style="font-size: 0.62rem;">Project Reference</span>
                        </label>
                        <input type="text" class="po-input" id="poOrderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" style="font-size: 0.84rem; padding: 5px 10px; font-weight: 500;" oninput="handlePoTitleChange(this.value)">
                    </div>
                </div>

                <!-- Card 1: Vendor Selection & Company Profile Details -->
                <div class="po-panel-card">
                    <div class="po-panel-header">
                        <div class="po-panel-title">
                            <i class="ph ph-buildings"></i>
                            <span>Vendor Selection & Partner Profile</span>
                        </div>
                        <span class="po-label-badge" id="poVendorCatalogBadge">Catalog Integrated</span>
                    </div>
                    <div class="po-panel-body">
                        <div class="po-field">
                            <label class="po-label" for="poVendorSelect">
                                <span>Select Target Vendor *</span>
                                <span class="po-label-badge">Searchable Masterlist</span>
                            </label>
                            <!-- Hidden select for form bindings & backwards-compatibility -->
                            <select class="po-select" id="poVendorSelect" style="display: none;" onchange="handlePoVendorSelect(this.value)">
                                <option value="">-- Choose Approved Vendor from Masterlist --</option>
                            </select>

                            <!-- Custom Searchable Combobox -->
                            <div class="po-vendor-combobox" id="poVendorCombobox">
                                <button type="button" class="po-vendor-combo-trigger" id="poVendorComboTrigger" onclick="togglePoVendorCombobox(event)">
                                    <span id="poVendorComboTriggerText" style="color: #64748b;">-- Choose Approved Vendor from Masterlist --</span>
                                    <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8;"></i>
                                </button>

                                <div class="po-vendor-combo-dropdown" id="poVendorComboDropdown">
                                    <div class="po-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                        <i class="ph ph-magnifying-glass"></i>
                                        <input type="text" class="po-vendor-combo-search-input" id="poVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterPoVendorCombobox(this.value)" autocomplete="off">
                                    </div>
                                    <div class="po-vendor-combo-list" id="poVendorComboList">
                                        <!-- Populated dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Vendor Summary Badge Box -->
                        <div class="po-vendor-pill-box" id="poVendorSummaryBox" style="display: none;">
                            <div class="po-vendor-avatar" id="poVendorAvatarText">VM</div>
                            <div class="po-vendor-summary">
                                <div class="po-vendor-name-row">
                                    <span id="poVendorTradeName">--</span>
                                    <span class="po-label-badge" id="poVendorCategoryTag">Category</span>
                                </div>
                                <div class="po-vendor-detail-row">
                                    <span><i class="ph ph-identification-card"></i> TIN: <strong id="poVendorTinDisplay">--</strong></span>
                                    <span>•</span>
                                    <span><i class="ph ph-map-pin"></i> <span id="poVendorLocationDisplay">--</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poContactPerson">Contact Person</label>
                                <input type="text" class="po-input" id="poContactPerson" placeholder="Contact Person" readonly title="Primary Contact Person">
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poContactTitle">Designation</label>
                                <input type="text" class="po-input" id="poContactTitle" placeholder="Designation" readonly title="Contact Designation / Title">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poContactEmail">
                                    <span>Recipient Email *</span>
                                </label>
                                <input type="email" class="po-input" id="poContactEmail" placeholder="vendor@example.com" title="Vendor Recipient Email" required>
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poContactPhone">Phone / Mobile</label>
                                <input type="text" class="po-input" id="poContactPhone" placeholder="Direct Phone" title="Contact Direct Phone">
                            </div>
                        </div>

                        <div class="po-field">
                            <label class="po-label" for="poFullAddress">Physical / Billing Address</label>
                            <input type="text" class="po-input" id="poFullAddress" placeholder="Street, Building, City, ZIP">
                        </div>
                    </div>
                </div>

                <!-- Card 2: Procurement Terms & Delivery Schedules -->
                <div class="po-panel-card">
                    <div class="po-panel-header">
                        <div class="po-panel-title">
                            <i class="ph ph-calendar-check"></i>
                            <span>PO Terms & Delivery Schedule</span>
                        </div>
                        <span class="po-label-badge">Auto Lead-Time Sync</span>
                    </div>
                    <div class="po-panel-body">
                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poNumberInput">PO Reference #</label>
                                <input type="text" class="po-input" id="poNumberInput" value="PO-2026-0105" readonly style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poOrderDate">Date Issued</label>
                                <input type="date" class="po-input" id="poOrderDate">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poPaymentTerms">
                                    <span>Payment Terms</span>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPaymentSettlementModal()" title="Open Financial Tracking & Payment Settlement" style="font-size: 11px; padding: 2px 7px; height: 22px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="ph ph-bank" style="color: var(--po-primary);"></i> Financial Settlement
                                    </button>
                                </label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <select class="po-select" id="poPaymentTerms" style="flex: 1;" onchange="handlePaymentTermsChange(this.value)">
                                        <option value="Net 30 Days">Net 30 Days (Standard)</option>
                                        <option value="Net 15 Days">Net 15 Days</option>
                                        <option value="Net 7 Days">Net 7 Days</option>
                                        <option value="COD">Cash on Delivery (COD)</option>
                                        <option value="Advance Payment">100% Advance Payment</option>
                                        <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                                    </select>
                                    <button type="button" class="hr-btn hr-btn-secondary" onclick="openPaymentSettlementModal()" title="View and calculate payments filtered by this PO reference" style="padding: 5px 9px; font-size: 11.5px; height: 32px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
                                        <span id="poSummaryPayBadge" class="pay-badge unpaid" style="font-size: 10px; padding: 2px 6px;">Unpaid</span>
                                    </button>
                                </div>
                            </div>
                            <div class="po-field">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <label class="po-label" for="poAmountPaid"><span>Amount Paid / Disbursed (₱)</span></label>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fillFullRemainingBalance()" style="font-size: 10px; padding: 1px 6px; height: 18px; color: #059669; border-color: #a7f3d0; background: #ecfdf5;" title="Fill total remaining balance"><i class="ph ph-lightning"></i> Full</button>
                                </div>
                                <input type="number" step="0.01" min="0" class="po-input" id="poAmountPaid" placeholder="0.00" oninput="handlePoInlineAmountPaid(this.value)" style="font-weight: 700;">
                                <input type="hidden" id="poPaymentStatus" value="Unpaid / Credit">
                                <input type="hidden" id="poPaymentMethod" value="Trade Credit (Net 30/15)">
                                <input type="hidden" id="poPaymentRef" value="">
                                <input type="hidden" id="poPaymentDate" value="">
                                <input type="hidden" id="poFundSource" value="Main Commissary Checking Acct">
                                <input type="hidden" id="poPaymentRemarks" value="">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poExpectedDelivery">
                                    <span>Expected Delivery *</span>
                                    <span class="po-label-badge" style="color: #0369a1;">Lead Time</span>
                                </label>
                                <input type="date" class="po-input" id="poExpectedDelivery">
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poDeliveryLocation">Destination Facility</label>
                                <select class="po-select" id="poDeliveryLocation">
                                    <option value="Central Commissary - Receiving Dock A">Central Commissary - Dock A</option>
                                    <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                                    <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                                    <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                                </select>
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poDeliveryLocation">Destination Facility</label>
                                <select class="po-select" id="poDeliveryLocation">
                                    <option value="Central Commissary - Receiving Dock A">Central Commissary - Dock A</option>
                                    <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                                    <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                                    <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                                    <option value="Central Warehouse - Cold Storage">Central Warehouse - Cold Storage</option>
                                </select>
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poPaymentMethod">Payment Method</label>
                                <select class="po-select" id="poPaymentMethod">
                                    <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15 Days)</option>
                                    <option value="Company Check">Company Check</option>
                                    <option value="Bank Transfer / Electronic">Bank Transfer / Electronic</option>
                                    <option value="Cash / Petty Cash">Cash / Petty Cash</option>
                                    <option value="GCash / Maya">GCash / Maya</option>
                                </select>
                            </div>
                        </div>

                        <div class="po-field">
                            <label class="po-label" for="poSpecialNotes">Purchase Order Instructions / Notes</label>
                            <textarea class="po-textarea" id="poSpecialNotes" placeholder="e.g. Inspect temperature upon receiving dock. Meat cuts must be vacuum sealed and weighing ticket attached."></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /.po-builder-left-col -->

            <!-- Right Column: Card 3 Line Items Panel & Card 4 Financial Settlement -->
            <div class="po-builder-right-col">

                <!-- Purchase Order Line Items & Specifications -->
                <div class="po-items-panel">
                    <div class="po-items-toolbar">
                        <div class="po-toolbar-left">
                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 8px;">
                                <i class="ph ph-list-numbers" style="color: var(--po-primary); font-size: 18px;"></i>
                                <span>Purchase Order Line Items & Specifications</span>
                            </div>
                            <span class="po-items-counter-pill" id="poItemCountBadge">0 Line Items</span>
                        </div>

                        <div class="po-toolbar-right">
                            <button type="button" class="po-btn po-btn-outline" onclick="openItemMasterQuickPicker()" title="Quick add items from Item Master (F2)" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-magnifying-glass"></i> Select Master <span class="po-hotkey-badge">F2</span>
                            </button>
                            <button type="button" class="po-btn po-btn-teal" onclick="addNewBlankPoItemRow()" title="Add custom non-catalog item specification" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-plus-circle"></i> Add Custom Row
                            </button>
                            <button type="button" class="po-btn po-btn-outline" style="color: var(--po-danger); padding: 5px 8px; font-size: 11.5px;" onclick="clearAllPoItems()" title="Remove all items from table">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table Container with Horizontal & Vertical Scroll -->
                    <div class="po-table-responsive" id="poTableContainer">
                        <table class="po-table" id="poItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 42px; text-align: center;">#</th>
                                    <th style="min-width: 220px;">Item Name & Specifications *</th>
                                    <th style="width: 90px; text-align: center;">UOM / Pack</th>
                                    <th style="width: 105px; text-align: right;">Ordered Qty *</th>
                                    <th style="width: 120px; text-align: right;">Unit Price (₱) *</th>
                                    <th style="width: 130px; text-align: right;">Line Total</th>
                                    <th style="width: 60px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="poItemsTbody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Items Table Footer Metric Summary -->
                    <div class="po-summary-bar">
                        <div class="po-metrics-cluster">
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Lines</span>
                                <span class="po-metric-val" id="summaryPoLines">0</span>
                            </div>
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Total Units</span>
                                <span class="po-metric-val" id="summaryPoUnits">0.00</span>
                            </div>
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Grand Total</span>
                                <span class="po-metric-val highlight" id="summaryPoGross">₱0.00</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="font-size: 0.74rem; color: var(--po-text-muted);">
                                <i class="ph ph-info"></i> All procurement totals reflect agreed contracted pricing.
                            </div>
                        </div>
                    </div>
                </div><!-- /.po-items-panel -->

            </div><!-- /.po-builder-right-col -->

        </div><!-- /.po-builder-workspace-grid -->

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

@endsection

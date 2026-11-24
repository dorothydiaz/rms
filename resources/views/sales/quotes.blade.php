@extends('layouts.app')

@section('title', 'Sales Quotes - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/sales-quotes.css')
@endpush

@push('blade-data')
<script>
    window.SalesQuoteConfig = Object.freeze({
        routes: {
            quotesIndex: @json(route('sales.api.quotes.index')),
            saveDraft: @json(route('sales.api.quotes.save-draft')),
            transitionStatus: @json(route('sales.api.quotes.transition-status')),
            reviseQuote: @json(route('sales.api.quotes.revise')),
            convertOrder: @json(route('sales.api.quotes.convert-order')),
            batchArchive: @json(route('sales.api.quotes.batch-archive')),
            batchDelete: @json(route('sales.api.quotes.batch-delete')),
            customerMasterlist: @json(route('sales.customer-masterlist')),
            salesOrders: @json(route('sales.orders')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Sales Specialist'),
        },
        data: {
            initialQuotes: @json($initialQuotes ?? []),
            initialCustomers: @json($initialCustomers ?? []),
            initialItems: @json($initialItems ?? []),
            initialWarehouses: @json($initialWarehouses ?? []),
            initialTaxCodes: @json($initialTaxCodes ?? []),
            linkedOrderMap: @json($linkedOrderMap ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/sales-quotes.js')
@endpush

@section('content')
<div class="rfq-workspace-container" id="sqAppWorkspace">

    <div class="rfq-lock-overlay" id="sqGlobalLockOverlay" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div style="font-weight: 700; font-size: 1.05rem;" id="sqLockOverlayMessage">Processing Sales Quote...</div>
        <div style="font-size: 0.82rem; opacity: 0.8;">Applying transactional lock and syncing pricing</div>
    </div>

    <div class="rfq-toast-container" id="sqToastContainer"></div>

    <!-- 1. Page Header (Exact RFQ Parity) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-file-text"></i>
                    <span>Sales Quotes</span>
                </h1>
                <p class="hr-parent-subtitle">Prepare customer price proposals, manage revisions &amp; validity dates, and convert confirmed quotes into Sales Orders.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}?new=1" class="hr-btn hr-btn-secondary">
                    <i class="ph ph-lightning"></i>
                    <span>Direct Sales Order</span>
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="createNewSalesQuote()">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Quote</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Tabs Bar (Exact RFQ Parity) -->
    <nav class="rfq-nav-tabs-bar" id="sqTabsBar">
        <button type="button" class="rfq-tab-btn active" id="sqTabBtnList" data-tab="tab-list" onclick="switchSqTab('tab-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Quote Directory &amp; Status Tracker</span>
            <span class="rfq-tab-count" id="sqTabListCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="sqTabBtnBuilder" data-tab="tab-builder" onclick="switchSqTab('tab-builder')">
            <i class="ph ph-plus-circle"></i>
            <span id="sqBuilderTabTitle">Quote Builder</span>
            <span class="rfq-tab-badge" id="sqBuilderTabBadge">New Quote</span>
        </button>
    </nav>

    <!-- ===================================================================== -->
    <!-- TAB 1: QUOTE DIRECTORY -->
    <!-- ===================================================================== -->
    <div class="rfq-tab-pane active" id="sq-pane-list">
        <div class="rfq-kpi-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom: 12px;">
            <div class="rfq-kpi-card is-active-filter" data-kpi-filter="all" onclick="filterSqByStatus('all')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Total Quotes</span><div class="rfq-kpi-icon"><i class="ph ph-files"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSqTotal">0</div>
                <div class="rfq-kpi-sub">All active proposals</div>
            </div>
            <div class="rfq-kpi-card" data-kpi-filter="DRAFT" onclick="filterSqByStatus('DRAFT')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Drafting</span><div class="rfq-kpi-icon draft"><i class="ph ph-pencil-simple"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSqDraft">0</div>
                <div class="rfq-kpi-sub">In internal preparation</div>
            </div>
            <div class="rfq-kpi-card" data-kpi-filter="SENT" onclick="filterSqByStatus('SENT')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Sent to Customer</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-paper-plane-tilt"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSqSent">0</div>
                <div class="rfq-kpi-sub">Awaiting client confirmation</div>
            </div>
            <div class="rfq-kpi-card" data-kpi-filter="CONFIRMED" onclick="filterSqByStatus('CONFIRMED')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Confirmed</span><div class="rfq-kpi-icon" style="color:#0369a1;background:rgba(14,165,233,0.12);"><i class="ph ph-check-circle"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSqConfirmed">0</div>
                <div class="rfq-kpi-sub">Ready to convert to SO</div>
            </div>
            <div class="rfq-kpi-card" data-kpi-filter="CONVERTED" onclick="filterSqByStatus('CONVERTED')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Converted to SO</span><div class="rfq-kpi-icon awarded"><i class="ph ph-seal-check"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSqConverted">0</div>
                <div class="rfq-kpi-sub">Linked to Sales Orders</div>
            </div>
        </div>

        <div class="rfq-panel-card">
            <div class="rfq-items-toolbar">
                <div class="rfq-toolbar-left" style="flex:1;min-width:0;">
                    <div style="position:relative;flex:1;min-width:220px;max-width:340px;">
                        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                        <input type="text" class="rfq-input" id="sqDirectorySearch" oninput="handleSqSearch(this.value)" placeholder="Search Quote #, customer, ref..." style="padding:5px 10px 5px 30px;font-size:12px;height:32px;">
                    </div>
                    <div id="sqBatchActionBar" class="rfq-batch-toolbar-bar" style="display:none;">
                        <span id="sqSelectedCount">0</span> selected
                        <button type="button" class="rfq-btn rfq-btn-outline" style="padding:3px 8px;font-size:11px;" onclick="batchArchiveQuotes()">Archive</button>
                        <button type="button" class="rfq-btn rfq-btn-danger" style="padding:3px 8px;font-size:11px;" onclick="batchDeleteQuotes()">Delete</button>
                    </div>
                </div>
                <div class="rfq-toolbar-right">
                    <button type="button" class="rfq-btn rfq-btn-primary" onclick="createNewSalesQuote()">
                        <i class="ph ph-plus"></i> New Quote
                    </button>
                </div>
            </div>

            <div class="rfq-table-responsive">
                <table class="rfq-table" id="sqDirectoryTable">
                    <thead>
                        <tr>
                            <th style="width:36px;"><input type="checkbox" id="sqSelectAll" onchange="toggleSelectAllQuotes(this.checked)"></th>
                            <th>Quote #</th>
                            <th>Customer</th>
                            <th>Terms</th>
                            <th>Valid Until</th>
                            <th style="text-align:right;">Gross Total</th>
                            <th>Margin</th>
                            <th>Status / SO Link</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="sqDirectoryBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: QUOTE BUILDER (RFQ 70/30 Split Parity) -->
    <!-- ===================================================================== -->
    <div class="rfq-tab-pane" id="sq-pane-builder">

        <!-- Lifecycle Stage & Action Bar -->
        <div class="rfq-lifecycle-bar-container stage-theme-draft" id="sqLifecycleBar">
            <div class="rfq-stage-actions-left">
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-save" onclick="saveSalesQuoteDraft()">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-submit" id="sqBtnSendQuote" onclick="transitionActiveQuote('SENT')">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span>Send Quote</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="sqBtnConfirmQuote" onclick="transitionActiveQuote('CONFIRMED')" style="background:#0284c7;color:#fff;">
                    <i class="ph ph-check-circle"></i>
                    <span>Customer Confirmed</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="sqBtnConvertOrder" onclick="convertActiveQuoteToOrder()" style="background:#059669;color:#fff;display:none;">
                    <i class="ph ph-arrow-right-bold"></i>
                    <span>Convert to Sales Order</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-cancel" id="sqBtnReviseQuote" onclick="reviseActiveQuote()" style="display:none;background:#f1f5f9;color:#334155;">
                    <i class="ph ph-copy"></i>
                    <span>Clone Revision</span>
                </button>
            </div>

            <!-- 4-Stage Chevron Pipeline -->
            <div class="rfq-chevron-pipeline" id="sqChevronPipeline">
                <div class="rfq-chevron-step is-active stage-draft" data-stage="DRAFT">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">1</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Drafting</span><span class="rfq-chevron-desc">Pricing &amp; Items</span></div>
                </div>
                <div class="rfq-chevron-step stage-submitted" data-stage="SENT">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">2</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Sent</span><span class="rfq-chevron-desc">Customer Review</span></div>
                </div>
                <div class="rfq-chevron-step stage-approval" data-stage="CONFIRMED">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">3</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Confirmed</span><span class="rfq-chevron-desc">Client Approved</span></div>
                </div>
                <div class="rfq-chevron-step stage-status" data-stage="CONVERTED">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">4</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Sales Order</span><span class="rfq-chevron-desc">Converted</span></div>
                </div>
            </div>
        </div>

        <!-- Top Identity & Operational Form Card (Anti-Bloat 4-5 Inputs + Collapsible Details) -->
        <div class="rfq-panel-card rfq-top-banner-card" id="sqTopBannerCard">
            <div class="rfq-top-identity-row">
                <div class="rfq-ref-identity-box">
                    <div class="rfq-ref-badge-icon"><i class="ph ph-file-text"></i></div>
                    <div class="rfq-ref-meta-cluster">
                        <span class="rfq-ref-label">Sales Quote #</span>
                        <div class="rfq-ref-value-row">
                            <span id="sqRefDisplay" class="rfq-ref-id-display">NEW DRAFT</span>
                            <span id="sqRevisionBadge" class="rfq-label-badge" style="display:none;">v1</span>
                        </div>
                    </div>
                    <div class="rfq-status-badge-wrap">
                        <span class="status-pill draft" id="sqStatusBadge"><i class="ph ph-dot"></i> Draft Quote</span>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <span class="sq-credit-chip" id="sqCustomerCreditChip" style="display:none;"></span>
                    <a href="#" id="sqLinkedOrderChip" class="rfq-vendor-label-link" style="display:none;"><i class="ph ph-arrow-square-out"></i> View Sales Order</a>
                </div>
            </div>

            <div class="rfq-top-banner-split rfq-top-banner-grid">
                <!-- Col 1: Searchable Customer Combobox + ">" Redirect -->
                <div class="rfq-field rfq-vendor-col">
                    <label class="rfq-label" for="sqCustomerSearchInput">
                        <span>Customer *</span>
                        <span class="rfq-label-badge">AR Masterlist</span>
                    </label>
                    <input type="hidden" id="sqCustomerIdInput" value="">
                    <div class="rfq-vendor-combo-wrapper">
                        <div class="rfq-vendor-combobox" id="sqCustomerCombobox">
                            <button type="button" class="rfq-vendor-combo-trigger" id="sqCustomerComboTrigger" onclick="toggleSqCustomerCombo(event)">
                                <span id="sqCustomerComboText" style="color:#64748b;">-- Choose Customer from Masterlist --</span>
                                <i class="ph ph-caret-down" style="font-size:14px;color:#94a3b8;"></i>
                            </button>
                            <div class="rfq-vendor-combo-dropdown" id="sqCustomerComboDropdown">
                                <div class="rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" class="rfq-vendor-combo-search-input" id="sqCustomerSearchInput" placeholder="Search customer name, code, TIN..." oninput="filterSqCustomers(this.value)" autocomplete="off">
                                </div>
                                <div class="rfq-vendor-combo-list" id="sqCustomerComboList"></div>
                            </div>
                        </div>
                        <a href="{{ route('sales.customer-masterlist') }}" class="rfq-vendor-redirect-btn" target="_blank" title="Open Customer Masterlist">
                            <i class="ph ph-caret-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Customer Reference / Proposal Title -->
                <div class="rfq-field rfq-notes-field">
                    <label class="rfq-label" for="sqCustomerRefInput">
                        <span>Customer Ref / Proposal Purpose</span>
                        <span class="rfq-label-badge">Optional</span>
                    </label>
                    <input type="text" class="rfq-input" id="sqCustomerRefInput" placeholder="e.g. Corporate Banquet Supply / Client Inquiry #104" oninput="syncSqMockup()">
                </div>

                <!-- Col 3: Source Warehouse -->
                <div class="rfq-field rfq-destination-col">
                    <label class="rfq-label" for="sqWarehouseSelect"><span>Source Warehouse</span></label>
                    <select class="rfq-select" id="sqWarehouseSelect" onchange="syncSqMockup()">
                        @foreach($initialWarehouses ?? [] as $wh)
                            <option value="{{ $wh['name'] }}" data-wh-id="{{ $wh['id'] }}">{{ $wh['code'] }} — {{ $wh['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Col 4: Payment Terms -->
                <div class="rfq-field rfq-terms-col">
                    <label class="rfq-label" for="sqTermsSelect"><span>Payment Terms</span></label>
                    <select class="rfq-select" id="sqTermsSelect" onchange="syncSqMockup()">
                        <option value="NET_30">Net 30 Days</option>
                        <option value="NET_15">Net 15 Days</option>
                        <option value="NET_7">Net 7 Days</option>
                        <option value="COD">Cash on Delivery (COD)</option>
                        <option value="ADVANCE">100% Advance Payment</option>
                    </select>
                </div>

                <!-- Col 5: Valid Until Date -->
                <div class="rfq-field rfq-due-col">
                    <label class="rfq-label" for="sqValidUntilInput"><span>Valid Until *</span></label>
                    <input type="date" class="rfq-input" id="sqValidUntilInput" value="{{ now()->addDays(14)->toDateString() }}" onchange="syncSqMockup()">
                </div>
            </div>

            <details style="margin-top:8px;">
                <summary style="cursor:pointer;font-size:0.75rem;font-weight:700;color:#64748b;">Additional Options (Header Discount, Quote Date, Notes &amp; Terms)</summary>
                <div style="display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:10px;margin-top:8px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="sqQuoteDateInput">Quote Date</label>
                        <input type="date" class="rfq-input" id="sqQuoteDateInput" value="{{ now()->toDateString() }}" onchange="syncSqMockup()">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="sqHeaderDiscType">Header Discount</label>
                        <select class="rfq-select" id="sqHeaderDiscType" onchange="recalcSqTotals()">
                            <option value="NONE">None</option>
                            <option value="PERCENT">Percent (%)</option>
                            <option value="FIXED">Fixed Amount (₱)</option>
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="sqHeaderDiscVal">Discount Value</label>
                        <input type="number" step="0.01" min="0" class="rfq-input" id="sqHeaderDiscVal" value="0" oninput="recalcSqTotals()">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="sqNotesInput">Internal / Print Notes</label>
                        <input type="text" class="rfq-input" id="sqNotesInput" placeholder="Delivery remarks..." oninput="syncSqMockup()">
                    </div>
                </div>
            </details>
        </div>

        <!-- Main Body Split (70% Items Table / 30% Sticky Live Quote Mockup) -->
        <div class="rfq-builder-split-body">
            <div class="rfq-items-panel-col">
                <div class="rfq-items-panel">
                    <div class="rfq-items-toolbar">
                        <div class="rfq-toolbar-left">
                            <div style="font-weight:800;font-size:0.95rem;color:#0f172a;display:flex;align-items:center;gap:8px;">
                                <i class="ph ph-list-numbers" style="color:var(--rfq-primary);font-size:18px;"></i>
                                <span>Quote Line Items &amp; Pricing</span>
                            </div>
                            <span class="rfq-items-counter-pill" id="sqItemsCountBadge">0 Line Items</span>
                        </div>
                        <div class="rfq-toolbar-right">
                            <button type="button" class="rfq-btn rfq-btn-teal" onclick="addSqItemRow()" style="padding:5px 10px;font-size:11.5px;">
                                <i class="ph ph-plus-circle"></i> Add Item Row
                            </button>
                        </div>
                    </div>

                    <div class="rfq-table-responsive">
                        <table class="rfq-table" id="sqItemsTable" style="min-width:900px;">
                            <thead>
                                <tr>
                                    <th style="width:36px;">#</th>
                                    <th style="min-width:210px;">Item / SKU</th>
                                    <th style="width:90px;">ATP</th>
                                    <th style="width:155px;">UOM</th>
                                    <th style="width:85px;">Qty</th>
                                    <th style="width:110px;">Unit Price</th>
                                    <th style="width:75px;">Disc %</th>
                                    <th style="width:125px;">Tax</th>
                                    <th style="width:115px;text-align:right;">Line Total</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="sqItemsTbody"></tbody>
                        </table>
                    </div>

                    <div class="rfq-summary-bar">
                        <div class="rfq-metrics-cluster">
                            <div class="rfq-metric-unit">
                                <span class="rfq-metric-label">Subtotal</span>
                                <span class="rfq-metric-val" id="sqSumSubtotal">₱0.00</span>
                            </div>
                            <div class="rfq-metric-unit">
                                <span class="rfq-metric-label">Output VAT</span>
                                <span class="rfq-metric-val" id="sqSumTax">₱0.00</span>
                            </div>
                            <div class="rfq-metric-unit">
                                <span class="rfq-metric-label">Gross Total</span>
                                <span class="rfq-metric-val highlight" id="sqSumGross">₱0.00</span>
                            </div>
                            <div class="rfq-metric-unit">
                                <span class="rfq-metric-label">Est. Margin</span>
                                <span class="rfq-metric-val" id="sqSumMargin">0.0%</span>
                            </div>
                        </div>
                        <div class="rfq-table-hint" id="sqMarginWarningHint" style="display:none;color:#dc2626;font-weight:700;">
                            <i class="ph ph-warning"></i> Below-cost line detected!
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sticky Live Mockup -->
            <div class="rfq-mockup-panel-col">
                <div class="rfq-panel-card rfq-mockup-card">
                    <div class="rfq-mockup-header-actions" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                        <button type="button" class="rfq-btn rfq-btn-outline rfq-mockup-action-btn" onclick="window.print()">
                            <i class="ph ph-printer"></i> <span>Print Quote</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-primary rfq-mockup-action-btn" onclick="saveSalesQuoteDraft()">
                            <i class="ph ph-floppy-disk"></i> <span>Save Quote</span>
                        </button>
                    </div>
                    <div class="rfq-mockup-content-wrap">
                        <div class="rfq-mockup-top-strip">
                            <div class="rfq-mockup-pill-tag"><i class="ph ph-file-text"></i><span>Customer Quotation Preview</span></div>
                            <span class="rfq-mockup-live-status"><span class="rfq-live-dot"></span> Live Sync</span>
                        </div>
                        <div id="sqLiveMockupBody" style="background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:16px;font-size:11.5px;color:#0f172a;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

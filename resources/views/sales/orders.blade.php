@extends('layouts.app')

@section('title', 'Sales Quotes & Orders - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/purchase-orders.css')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@push('blade-data')
<script>
    window.SalesOrderConfig = Object.freeze({
        initialStageFilter: @json($initialStageFilter ?? 'all'),
        routes: {
            ordersIndex: @json(route('sales.api.orders.index')),
            saveDraft: @json(route('sales.api.orders.save-draft')),
            confirmOrder: @json(route('sales.api.orders.confirm')),
            quoteStage: @json(url('/sales/api/orders/__ID__/quote-stage')),
            reviseQuote: @json(url('/sales/api/orders/__ID__/revise-quote')),
            creditReview: @json(url('/sales/api/orders/__ID__/credit-review')),
            creditCheck: @json(url('/sales/api/customers/__ID__/credit-check')),
            createDelivery: @json(url('/sales/api/orders/__ID__/create-delivery')),
            createBill: @json(url('/sales/api/orders/__ID__/create-bill')),
            cancelOrder: @json(url('/sales/api/orders/__ID__/cancel')),
            batchArchive: @json(route('sales.api.orders.batch-archive')),
            validateVoucher: @json(route('sales.api.vouchers.validate')),
            explodeBundle: @json(url('/sales/api/bundles/__ID__/explode')),
            customerMasterlist: @json(route('sales.customer-masterlist')),
            bundlePromotions: @json(route('sales.bundle-promotions')),
            discountConfig: @json(route('sales.discount-config')),
            voucherConfig: @json(route('sales.voucher-config')),
            inventoryProduction: @json(url('/inventory/production')),
            purchaseRfq: @json(url('/purchase/request-quotations')),
            inventoryStockOut: @json(route('inventory.stock-out', ['order_type' => 'SALES_DELIVERY'])),
            accountingArCredit: @json(route('accounting.ar', ['tab' => 'credit-review'])),
            accountingArInvoices: @json(route('accounting.ar', ['tab' => 'invoices'])),
        },
        user: {
            name: @json(auth()->user()->full_name ?? auth()->user()->name ?? 'Sales Specialist'),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialCustomers: @json($initialCustomers ?? []),
            initialItems: @json($initialItems ?? []),
            initialWarehouses: @json($initialWarehouses ?? []),
            initialTaxCodes: @json($initialTaxCodes ?? []),
            initialPaymentTerms: @json($initialPaymentTerms ?? []),
            initialDiscounts: @json($initialDiscounts ?? []),
            initialVouchers: @json($initialVouchers ?? []),
            initialBundles: @json($initialBundles ?? []),
            initialEmployees: @json($initialEmployees ?? []),
            bomItemIds: @json($bomItemIds ?? (object) []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/sales-orders.js')
@endpush

@section('content')
<div class="po-workspace-container rfq-workspace-container" id="soAppWorkspace">

    <!-- Global Pessimistic Lock Overlay (PO Parity) -->
    <div class="po-lock-overlay rfq-lock-overlay" id="soGlobalLockOverlay" aria-hidden="true">
        <div class="po-lock-card rfq-lock-card">
            <i class="ph ph-spinner-gap ph-spin" style="font-size:26px;color:#9333ea;"></i>
            <div>
                <div style="font-weight:700;font-size:1.02rem;color:#0f172a;" id="soLockOverlayMessage">Processing Sales Record...</div>
                <div style="font-size:0.78rem;color:#64748b;">Synchronizing ATP reservations, Accounting taxes, credit exposure &amp; ledger state</div>
            </div>
        </div>
    </div>

    <div class="rfq-toast-container" id="soToastContainer"></div>

    <!-- 1. Unified Header (Sales Quotes + Sales Orders) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-clipboard-text"></i>
                    <span>Sales Quotes &amp; Orders (O2C)</span>
                </h1>
                <p class="hr-parent-subtitle">Unified Order-to-Cash workspace — Quote proposals (SQ), customer confirmation, Accounting Credit Review &amp; Taxes, Delivery (DO) &amp; AR Billing (SI).</p>
            </div>
            <div class="hr-page-actions">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="createNewSalesOrder(null, 'QUOTE')">
                    <i class="ph ph-file-plus"></i>
                    <span>New Quote (SQ)</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="createNewSalesOrder(null, 'ORDER')">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Sales Order (SO)</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs Bar -->
    <nav class="po-nav-tabs-bar rfq-nav-tabs-bar" id="soTabsBar">
        <button type="button" class="po-tab-btn rfq-tab-btn active" id="soTabBtnList" onclick="switchSoTab('tab-list')">
            <i class="ph ph-table"></i>
            <span>Quotes &amp; Orders Tracker</span>
            <span class="po-tab-count rfq-tab-count" id="soTabListCount">0</span>
        </button>
        <button type="button" class="po-tab-btn rfq-tab-btn" id="soTabBtnBuilder" onclick="switchSoTab('tab-builder')">
            <i class="ph ph-file-plus"></i>
            <span id="soBuilderTabTitle">Workspace Builder</span>
            <span class="po-tab-badge rfq-tab-badge" id="soBuilderTabBadge">New Order</span>
        </button>
    </nav>

    <!-- ===================================================================== -->
    <!-- TAB 1: UNIFIED TRACKER (STRICTLY SEPARATED VIA .so-tab-pane)          -->
    <!-- ===================================================================== -->
    <div class="so-tab-pane is-active" id="so-pane-list" style="display:flex;">
        <div class="rfq-kpi-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom: 12px;">
            <div class="po-kpi-card rfq-kpi-card is-active-filter" data-kpi-filter="all" onclick="filterSoByStatus('all')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">All Active Records</span><div class="rfq-kpi-icon"><i class="ph ph-files"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSoTotal">0</div>
                <div class="rfq-kpi-sub">Quotes &amp; Sales Orders</div>
            </div>
            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="QUOTE" onclick="filterSoByStatus('QUOTE')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Open Quotes (SQ)</span><div class="rfq-kpi-icon" style="color:#0284c7;background:#ecfeff;"><i class="ph ph-file-text"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSoQuotes">0</div>
                <div class="rfq-kpi-sub">Draft, Sent &amp; Confirmed SQ</div>
            </div>
            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="CREDIT_HOLD" onclick="filterSoByStatus('CREDIT_HOLD')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Credit Review</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-shield-warning"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSoCreditHold">0</div>
                <div class="rfq-kpi-sub">Pending Accounting AR approval</div>
            </div>
            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="APPROVED" onclick="filterSoByStatus('APPROVED')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Ready / Delivering</span><div class="rfq-kpi-icon" style="color:#2563eb;background:#eff6ff;"><i class="ph ph-truck"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSoReady">0</div>
                <div class="rfq-kpi-sub">ATP reserved for dispatch</div>
            </div>
            <div class="po-kpi-card rfq-kpi-card" data-kpi-filter="BILLED" onclick="filterSoByStatus('BILLED')" style="cursor:pointer;">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Delivered / Billed</span><div class="rfq-kpi-icon awarded"><i class="ph ph-currency-circle-dollar"></i></div></div>
                <div class="rfq-kpi-val" id="kpiSoBilled">0</div>
                <div class="rfq-kpi-sub">Shipped, Invoiced &amp; Settled</div>
            </div>
        </div>

        <div class="po-panel-card rfq-panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #e2e8f0;gap:10px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;flex:1;">
                    <div style="position:relative;min-width:220px;max-width:300px;flex:1;">
                        <i class="ph ph-magnifying-glass" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:14px;"></i>
                        <input type="text" class="rfq-input" id="soSearchInput" oninput="handleSoSearch(this.value)" placeholder="Search SQ #, SO #, Customer, Rep..." style="padding:5px 10px 5px 30px;font-size:12px;height:32px;">
                    </div>
                    <div class="so-stage-pills" id="soStageFilterPills">
                        <button type="button" class="so-stage-pill-btn is-active" data-stage-filter="all" onclick="filterSoByStatus('all')">All</button>
                        <button type="button" class="so-stage-pill-btn" data-stage-filter="QUOTE" onclick="filterSoByStatus('QUOTE')">Quotes</button>
                        <button type="button" class="so-stage-pill-btn" data-stage-filter="CREDIT_HOLD" onclick="filterSoByStatus('CREDIT_HOLD')">Credit Hold</button>
                        <button type="button" class="so-stage-pill-btn" data-stage-filter="APPROVED" onclick="filterSoByStatus('APPROVED')">Active SO</button>
                        <button type="button" class="so-stage-pill-btn" data-stage-filter="BILLED" onclick="filterSoByStatus('BILLED')">Delivered &amp; Billed</button>
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <button type="button" class="rfq-btn rfq-btn-outline" onclick="createNewSalesOrder(null, 'QUOTE')">
                        <i class="ph ph-file-plus"></i> New Quote
                    </button>
                    <button type="button" class="rfq-btn rfq-btn-primary" onclick="createNewSalesOrder(null, 'ORDER')">
                        <i class="ph ph-plus"></i> New Sales Order
                    </button>
                </div>
            </div>

            <div class="rfq-table-responsive">
                <table class="rfq-table" id="soDirectoryTable">
                    <thead>
                        <tr>
                            <th>Document # / Stage</th>
                            <th>Customer &amp; Terms</th>
                            <th>Credit Check</th>
                            <th>Fulfillment &amp; DO</th>
                            <th>Billing &amp; AR</th>
                            <th style="text-align:right;">Total</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="soDirectoryBody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- TAB 2: UNIFIED BUILDER WORKSPACE (6-STAGE O2C PIPELINE)               -->
    <!-- ===================================================================== -->
    <div class="so-tab-pane" id="so-pane-builder" style="display:none;">

        <!-- Lifecycle Stage & Action Bar -->
        <div class="rfq-lifecycle-bar-container so-lifecycle-bar stage-theme-draft" id="soLifecycleBar">
            <div class="rfq-stage-actions-left">
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-save" id="soBtnSaveDraft" onclick="saveSalesOrderDraft()">
                    <i class="ph ph-floppy-disk"></i>
                    <span id="soBtnSaveDraftLabel">Save Draft</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnSendQuote" onclick="transitionQuoteStage('QUOTE_SENT')" style="display:none;background:#0284c7;color:#fff;">
                    <i class="ph ph-paper-plane"></i>
                    <span>Mark Quote Sent</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnConfirmQuote" onclick="transitionQuoteStage('QUOTE_CONFIRMED')" style="display:none;background:#16a34a;color:#fff;">
                    <i class="ph ph-check-square"></i>
                    <span>Customer Confirmed</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnReviseQuote" onclick="cloneQuoteRevision()" style="display:none;background:#475569;color:#fff;">
                    <i class="ph ph-copy"></i>
                    <span>Clone Revision</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-submit" id="soBtnConfirmOrder" onclick="confirmActiveSalesOrder()">
                    <i class="ph ph-paper-plane-tilt"></i>
                    <span id="soBtnConfirmOrderLabel">Confirm Order</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnOpenCreditDrawer" onclick="openSoCreditReviewDrawer()" style="display:none;background:#d97706;color:#fff;">
                    <i class="ph ph-shield-check"></i>
                    <span>Credit Review</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnOpenDeliveryModal" onclick="openSoDeliveryDrawer()" style="display:none;background:#0284c7;color:#fff;">
                    <i class="ph ph-truck"></i>
                    <span>Create Delivery (DO)</span>
                </button>
                <button type="button" class="rfq-stage-action-btn" id="soBtnPostBill" onclick="postOrCreateSoBill()" style="display:none;background:#7c3aed;color:#fff;">
                    <i class="ph ph-receipt"></i>
                    <span id="soBtnPostBillLabel">Create / Post Bill</span>
                </button>
                <button type="button" class="rfq-stage-action-btn rfq-btn-stage-cancel" id="soBtnCancelOrder" onclick="openSoCancelDrawer()" style="display:none;">
                    <i class="ph ph-x-circle"></i>
                    <span>Cancel</span>
                </button>
            </div>

            <!-- 6-Stage Unified O2C Chevron Pipeline -->
            <div class="rfq-chevron-pipeline so-chevron-pipeline" id="soChevronPipeline">
                <div class="rfq-chevron-step is-active stage-draft" data-stage="QUOTE">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">1</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Quote</span><span class="rfq-chevron-desc">SQ Proposal</span></div>
                </div>
                <div class="rfq-chevron-step stage-draft" data-stage="QUOTE_CONFIRMED">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">2</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Confirmed</span><span class="rfq-chevron-desc">Buyer Agreed</span></div>
                </div>
                <div class="rfq-chevron-step stage-submitted" data-stage="CREDIT_HOLD">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">3</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Credit Review</span><span class="rfq-chevron-desc">Accounting AR</span></div>
                </div>
                <div class="rfq-chevron-step stage-approval" data-stage="DELIVERY">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">4</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Delivery</span><span class="rfq-chevron-desc">Stock-Out + COGS</span></div>
                </div>
                <div class="rfq-chevron-step stage-status" data-stage="BILLED">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">5</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Bills</span><span class="rfq-chevron-desc">AR Invoice</span></div>
                </div>
                <div class="rfq-chevron-step stage-status" data-stage="CLOSED">
                    <div class="rfq-chevron-badge"><i class="ph ph-check"></i><span class="rfq-chevron-num">6</span></div>
                    <div class="rfq-chevron-info"><span class="rfq-chevron-title">Settled</span><span class="rfq-chevron-desc">Paid / Closed</span></div>
                </div>
            </div>
        </div>

        <!-- Stage Context Helper Ribbon (PO/RFQ Parity) -->
        <div class="rfq-stage-helper-ribbon stage-draft" id="soStageBanner">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <span class="rfq-stage-helper-icon" id="soStageBannerIcon"><i class="ph ph-pencil-simple-line"></i></span>
                <strong class="rfq-stage-helper-title" id="soStageBannerTitle">Stage 1: Draft Entry</strong>
                <span>—</span>
                <span class="rfq-stage-helper-desc" id="soStageBannerSub">Select customer, pick Item Master SKUs or Bundles, apply Accounting taxes, then Confirm Order or Send Quote.</span>
            </div>
            <div class="rfq-stage-helper-right">
                <span class="rfq-stage-indicator-pill draft" id="soDirtyIndicator" style="display:none;color:#b45309;background:#fffbeb;border-color:#fde68a;">Unsaved changes</span>
                <span class="rfq-stage-indicator-pill draft" id="soActiveStagePill"><i class="ph ph-circle-dashed"></i> Draft Mode</span>
            </div>
        </div>

        <!-- Live Credit Exposure Banner -->
        <div class="so-credit-banner is-ok" id="soCreditExposureBanner" style="display:none;"></div>

        <!-- Top Identity & Operational Form Card (PO/RFQ Balanced 2-Row Grid) -->
        <div class="po-panel-card rfq-panel-card po-top-banner-card rfq-top-banner-card" id="soTopBannerCard">
            <!-- Identity Ribbon Strip -->
            <div class="rfq-identity-ribbon-strip" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding-bottom:10px;margin-bottom:12px;border-bottom:1px solid #f1f5f9;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <div style="display:inline-flex;align-items:center;gap:6px;background:#faf5ff;border:1px solid #e9d5ff;padding:4px 10px;border-radius:8px;">
                        <i class="ph ph-clipboard-text" style="color:#9333ea;font-size:16px;"></i>
                        <span id="soDocTypeLabel" style="font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;">Sales Order #:</span>
                        <span id="soRefDisplay" class="po-ref-id-display rfq-ref-id-display">NEW ORDER</span>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copySoReference()" title="Copy Document Reference" style="padding:2px 6px;font-size:11px;height:22px;">
                            <i class="ph ph-copy"></i>
                        </button>
                    </div>
                    <span class="status-pill draft" id="soStatusBadge" style="font-size:11px;padding:3px 9px;"><i class="ph ph-dot"></i> Draft</span>
                    <div id="soLinkedDocumentsBar" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;"></div>
                </div>
                <a href="{{ route('sales.customer-masterlist') }}" id="soCustomerProfileLink" target="_blank" style="display:none;font-size:11.5px;font-weight:700;color:#7e22ce;text-decoration:none;align-items:center;gap:4px;">
                    <i class="ph ph-buildings"></i> <span id="soCustomerProfileText">Customer Masterlist</span> <i class="ph ph-arrow-square-out"></i>
                </a>
            </div>

            <!-- 2-Row 4-Column Operational Grid (PO Parity — No Overflow) -->
            <div class="rfq-operational-grid" style="display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:12px;">
                <div class="po-field rfq-field" style="grid-column:span 2;">
                    <label class="po-label rfq-label" for="soCustomerSearchInput">
                        <span>Customer *</span>
                        <span class="po-label-badge rfq-label-badge">AR Masterlist</span>
                    </label>
                    <input type="hidden" id="soCustomerIdInput" value="">
                    <div style="display:flex;align-items:center;gap:6px;">
                        <div class="po-vendor-combobox rfq-vendor-combobox" style="flex:1;min-width:0;position:relative;">
                            <button type="button" class="po-vendor-combo-trigger rfq-vendor-combo-trigger" onclick="toggleSoCustomerCombo(event)">
                                <span id="soCustomerComboText" style="color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">-- Choose Customer --</span>
                                <i class="ph ph-caret-down" style="font-size:14px;color:#94a3b8;flex-shrink:0;"></i>
                            </button>
                            <div class="po-vendor-combo-dropdown rfq-vendor-combo-dropdown" id="soCustomerComboDropdown">
                                <div class="po-vendor-combo-search-wrap rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" class="po-vendor-combo-search-input rfq-vendor-combo-search-input" id="soCustomerSearchInput" placeholder="Search by name, code, TIN..." oninput="filterSoCustomers(this.value)" autocomplete="off">
                                </div>
                                <div class="po-vendor-combo-list rfq-vendor-combo-list" id="soCustomerComboList"></div>
                            </div>
                        </div>
                        <a href="{{ route('sales.customer-masterlist') }}" class="po-vendor-redirect-btn rfq-vendor-redirect-btn" target="_blank" title="Open Customer Masterlist">
                            <i class="ph ph-caret-right" style="font-size:15px;font-weight:800;"></i>
                        </a>
                    </div>
                </div>

                <div class="po-field rfq-field" style="grid-column:span 2;">
                    <label class="po-label rfq-label" for="soCustomerPoInput">
                        <span>Customer PO / Reference</span>
                        <span class="po-label-badge rfq-label-badge">Buyer Ref</span>
                    </label>
                    <input type="text" class="po-input rfq-input" id="soCustomerPoInput" placeholder="Buyer PO # or Event Reference" oninput="markSoDirty(); syncSoMockup()">
                </div>

                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="soWarehouseSelect"><span>Source Warehouse</span></label>
                    <select class="po-select rfq-select" id="soWarehouseSelect" onchange="markSoDirty(); syncSoMockup()">
                        @foreach($initialWarehouses ?? [] as $wh)
                            <option value="{{ $wh['name'] }}" data-wh-id="{{ $wh['id'] }}">{{ $wh['code'] }} — {{ $wh['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="soTermsSelect">
                        <span>Payment Terms</span>
                        <span class="po-label-badge rfq-label-badge">Accounting</span>
                    </label>
                    <select class="po-select rfq-select" id="soTermsSelect" onchange="markSoDirty(); refreshLiveCreditCheck(); syncSoMockup();">
                        @php $seenTermCodes = []; @endphp
                        @foreach($initialPaymentTerms ?? [] as $pt)
                            @php
                                $tc = strtoupper((string) $pt->term_code);
                                $seenTermCodes[] = $tc;
                            @endphp
                            <option value="{{ $tc }}" data-due-days="{{ (int) $pt->due_days }}">{{ $pt->term_name }}</option>
                        @endforeach
                        @if(!in_array('ADVANCE', $seenTermCodes, true))
                            <option value="ADVANCE" data-due-days="0">100% Advance Payment</option>
                        @endif
                        @if(empty($initialPaymentTerms) || count($initialPaymentTerms) === 0)
                            <option value="NET_30" data-due-days="30">Net 30 Days</option>
                            <option value="NET_15" data-due-days="15">Net 15 Days</option>
                            <option value="NET_7" data-due-days="7">Net 7 Days</option>
                            <option value="COD" data-due-days="0">Cash on Delivery (COD)</option>
                        @endif
                    </select>
                </div>

                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="soOrderDateInput"><span id="soOrderDateLabel">Document Date</span></label>
                    <input type="date" class="po-input rfq-input" id="soOrderDateInput" value="{{ now()->toDateString() }}" onchange="markSoDirty(); syncSoMockup()">
                </div>

                <div class="po-field rfq-field">
                    <label class="po-label rfq-label" for="soDeliveryDateInput"><span id="soDeliveryDateLabel">Target Delivery *</span></label>
                    <input type="date" class="po-input rfq-input" id="soDeliveryDateInput" value="{{ now()->addDays(3)->toDateString() }}" onchange="markSoDirty(); syncSoMockup()">
                </div>
            </div>

            <details style="margin-top:10px;border-top:1px solid #f1f5f9;padding-top:8px;">
                <summary style="cursor:pointer;font-size:0.75rem;font-weight:700;color:#64748b;">Additional Options (Shipping Address, Quote Expiry, HR Salesperson, Manual Discount &amp; Notes)</summary>
                <div style="display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:10px;margin-top:8px;">
                    <div class="rfq-field" style="grid-column: span 2;">
                        <label class="rfq-label" for="soShippingAddressInput">Shipping / Delivery Address</label>
                        <input type="text" class="rfq-input" id="soShippingAddressInput" placeholder="Auto-filled from Customer Masterlist" oninput="markSoDirty(); syncSoMockup()">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="soSalespersonSelect">Salesperson (HR)</label>
                        <select class="rfq-select" id="soSalespersonSelect" onchange="markSoDirty(); syncSoMockup()">
                            <option value="">-- Current Actor --</option>
                            @foreach($initialEmployees ?? [] as $emp)
                                <option value="{{ $emp['employee_id'] }}" data-name="{{ $emp['full_name'] }}">{{ $emp['employee_code'] }} — {{ $emp['full_name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="soValidUntilInput">Quote Valid Until</label>
                        <input type="date" class="rfq-input" id="soValidUntilInput" value="{{ now()->addDays(14)->toDateString() }}" onchange="markSoDirty(); syncSoMockup()">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="soHeaderDiscType">Manual Header Discount</label>
                        <select class="rfq-select" id="soHeaderDiscType" onchange="onManualDiscountChanged()">
                            <option value="NONE">None</option>
                            <option value="PERCENT">Percent (%)</option>
                            <option value="FIXED">Fixed (₱)</option>
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="soHeaderDiscVal">Discount Value</label>
                        <input type="number" step="0.01" min="0" class="rfq-input" id="soHeaderDiscVal" value="0" oninput="markSoDirty(); recalcSoTotals()">
                    </div>
                    <div class="rfq-field" style="grid-column: span 2;">
                        <label class="rfq-label" for="soSpecialNotesInput">Special Instructions / Remarks</label>
                        <input type="text" class="rfq-input" id="soSpecialNotesInput" placeholder="Delivery window, gate pass, BIR notes..." oninput="markSoDirty(); syncSoMockup()">
                    </div>
                </div>
            </details>
        </div>

        <!-- Main Body Split (70% Line Items Table / 30% Sticky Live Mockup) -->
        <div class="po-builder-split-body rfq-builder-split-body">
            <div class="po-items-panel-col rfq-items-panel-col">
                <div class="po-items-panel rfq-items-panel">
                    <div class="po-items-toolbar rfq-items-toolbar" style="flex-wrap:wrap;gap:8px;">
                        <div class="rfq-toolbar-left">
                            <div style="font-weight:800;font-size:0.95rem;color:#0f172a;display:flex;align-items:center;gap:8px;">
                                <i class="ph ph-list-numbers" style="color:var(--rfq-primary);font-size:18px;"></i>
                                <span>Order Lines, Bundles &amp; ATP Reservation</span>
                            </div>
                            <span class="rfq-items-counter-pill" id="soItemsCountBadge">0 Line Items</span>
                        </div>
                        <div class="rfq-toolbar-right" style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <button type="button" class="rfq-btn rfq-btn-outline" id="soBtnItemPicker" onclick="openSoItemPickerDrawer()" title="Select items from Item Master (F2)" style="padding:5px 10px;font-size:11.5px;">
                                <i class="ph ph-magnifying-glass"></i> Select Master <span class="po-hotkey-badge rfq-hotkey-badge">F2</span>
                            </button>
                            <select class="rfq-select" id="soBundleAddSelect" style="width:190px;height:30px;font-size:11.5px;">
                                <option value="">-- Select Bundle Promo --</option>
                                @foreach($initialBundles ?? [] as $bnd)
                                    <option value="{{ $bnd['bundle_id'] }}">[{{ $bnd['bundle_code'] }}] {{ $bnd['bundle_name'] }} (₱{{ number_format($bnd['bundle_price'], 2) }} • ATP {{ number_format($bnd['bundle_atp'] ?? 0, 0) }})</option>
                                @endforeach
                            </select>
                            <button type="button" class="rfq-btn rfq-btn-outline" id="soBtnExplodeBundle" onclick="addSoBundleRows()" style="padding:5px 10px;font-size:11.5px;">
                                <i class="ph ph-gift"></i> + Add Bundle
                            </button>
                            <button type="button" class="rfq-btn rfq-btn-teal" id="soBtnAddItemRow" onclick="addSoItemRow()" style="padding:5px 10px;font-size:11.5px;">
                                <i class="ph ph-plus-circle"></i> + Custom Row
                            </button>
                        </div>
                    </div>

                    <!-- Accounting Taxes & Promotions Strip -->
                    <div class="so-promo-strip">
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <label class="rfq-label" for="soDiscountRuleSelect" style="margin:0;white-space:nowrap;">Discount Rule:</label>
                            <select class="rfq-select" id="soDiscountRuleSelect" onchange="onDiscountRuleChanged()" style="width:210px;height:30px;font-size:11.5px;">
                                <option value="">-- No Promo Rule --</option>
                                @foreach($initialDiscounts ?? [] as $disc)
                                    <option value="{{ $disc->discount_id }}"
                                        data-type="{{ $disc->discount_type }}"
                                        data-val="{{ $disc->discount_value }}"
                                        data-vat-exempt="{{ $disc->vat_exempt_override ? '1' : '0' }}"
                                        data-cap="{{ $disc->max_discount_cap ?? '' }}">
                                        [{{ $disc->discount_code }}] {{ $disc->discount_name }} ({{ $disc->discount_type === 'PERCENT' ? $disc->discount_value . '%' : '₱' . number_format($disc->discount_value, 2) }}{{ $disc->vat_exempt_override ? ' + VAT-Exempt' : '' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                            <label class="rfq-label" for="soVoucherCodeInput" style="margin:0;white-space:nowrap;">Voucher:</label>
                            <input type="text" class="rfq-input" id="soVoucherCodeInput" list="soActiveVouchersList" placeholder="e.g. WELCOME500" style="width:140px;height:30px;font-size:11.5px;text-transform:uppercase;">
                            <datalist id="soActiveVouchersList">
                                @foreach($initialVouchers ?? [] as $vc)
                                    <option value="{{ $vc->voucher_code }}">{{ $vc->voucher_name }}</option>
                                @endforeach
                            </datalist>
                            <button type="button" class="rfq-btn rfq-btn-outline" id="soBtnApplyVoucher" onclick="validateAndApplyVoucher()" style="padding:4px 10px;font-size:11.5px;">Apply</button>
                            <button type="button" class="rfq-btn rfq-btn-outline" id="soBtnClearVoucher" onclick="clearVoucher()" style="display:none;padding:4px 8px;font-size:11px;color:#b91c1c;">Clear</button>
                            <span id="soVoucherStatusChip" style="font-size:11px;font-weight:700;color:#047857;"></span>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <label class="rfq-label" for="soCwtEstimateSelect" style="margin:0;white-space:nowrap;">BIR 2307 CWT (Est.):</label>
                            <select class="rfq-select" id="soCwtEstimateSelect" onchange="recalcSoTotals()" style="width:175px;height:30px;font-size:11.5px;">
                                <option value="0" data-code="">None (0% Withholding)</option>
                                @foreach($initialTaxCodes ?? [] as $tc)
                                    @if(strtoupper((string) $tc->tax_type) === 'CWT')
                                        <option value="{{ (float) $tc->rate_pct }}" data-code="{{ $tc->tax_code }}">{{ $tc->tax_code }} ({{ number_format((float) $tc->rate_pct, 1) }}%)</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="rfq-table-responsive">
                        <table class="rfq-table" id="soItemsTable">
                            <thead>
                                <tr>
                                    <th style="width:34px;">#</th>
                                    <th style="min-width:225px;">Item / SKU</th>
                                    <th style="width:100px;">ATP / Supply</th>
                                    <th style="width:140px;">UOM</th>
                                    <th style="width:82px;text-align:right;">Qty</th>
                                    <th style="width:92px;">Deliv / Bill</th>
                                    <th style="width:100px;text-align:right;">Unit Price</th>
                                    <th style="width:68px;text-align:right;">Disc %</th>
                                    <th style="width:145px;">Tax (Accounting)</th>
                                    <th style="width:105px;text-align:right;">Line Total</th>
                                    <th style="width:36px;"></th>
                                </tr>
                            </thead>
                            <tbody id="soItemsTbody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Separate PO-Parity Financial Breakdown Card -->
                <div class="po-summary-bar rfq-summary-bar po-summary-bar-clean" style="margin-top:12px;">
                    <div class="po-footer-left-meta">
                        <div class="po-metrics-cluster rfq-metrics-cluster">
                            <div class="po-metric-unit rfq-metric-unit">
                                <span class="po-metric-label rfq-metric-label">Lines</span>
                                <span class="po-metric-val rfq-metric-val" id="soSumLinesCount" style="font-size:1rem;">0</span>
                            </div>
                            <div class="po-metric-unit rfq-metric-unit">
                                <span class="po-metric-label rfq-metric-label">Total Units</span>
                                <span class="po-metric-val rfq-metric-val" id="soSumUnitsCount" style="font-size:1rem;">0.00</span>
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px;font-size:0.72rem;color:#64748b;">
                            <i class="ph ph-broadcast" style="color:#059669;"></i> Live synced with Document Preview &amp; Accounting GL Taxes
                        </div>
                    </div>

                    <div class="po-fin-breakdown-panel" id="soFinBreakdownPanel">
                        <div class="po-fin-row">
                            <span class="po-fin-label">Lines Subtotal (Ex-VAT):</span>
                            <span class="po-fin-value strong" id="soSumGrossSubtotal">₱0.00</span>
                        </div>
                        <div class="po-fin-row">
                            <span class="po-fin-label">Discounts / Voucher (4090):</span>
                            <span class="po-fin-value" id="soSumDiscounts" style="color:#b45309;">₱0.00</span>
                        </div>
                        <div class="po-fin-row">
                            <span class="po-fin-label">Net Taxable Subtotal:</span>
                            <span class="po-fin-value strong" id="soSumSubtotal">₱0.00</span>
                        </div>
                        <div id="soTaxBreakdownRows"></div>
                        <div class="po-fin-row">
                            <span class="po-fin-label">Total Output Tax:</span>
                            <span class="po-fin-value" id="soSumTax">₱0.00</span>
                        </div>
                        <div class="po-fin-divider"></div>
                        <div class="po-fin-row total-row">
                            <span class="po-fin-label total-label">Grand Total:</span>
                            <span class="po-fin-value total-value" id="soSumGross">₱0.00</span>
                        </div>
                        <div class="po-fin-row" id="soSumCwtRow" style="display:none;">
                            <span class="po-fin-label" style="color:#047857;">Less Est. CWT (2307):</span>
                            <span class="po-fin-value" id="soSumCwtVal" style="color:#047857;">(₱0.00)</span>
                        </div>
                        <div class="po-fin-row balance-row">
                            <span class="po-fin-label balance-label">Billed / Paid:</span>
                            <span class="po-fin-value balance-value" id="soSumPaid">₱0.00 / ₱0.00</span>
                        </div>
                    </div>
                </div>

                <!-- Linked Deliveries & Bills / Collections Panel -->
                <div class="po-payment-panel" id="soFulfillmentBillingPanel" style="margin-top:14px;display:none;">
                    <div class="po-payment-header">
                        <strong style="font-size:0.9rem;color:#0f172a;"><i class="ph ph-arrows-left-right"></i> Linked Inventory Deliveries, AR Bills &amp; Collections</strong>
                        <a href="{{ route('accounting.ar', ['tab' => 'receipts']) }}" class="rfq-btn rfq-btn-outline" style="padding:4px 10px;font-size:11px;text-decoration:none;">Record AR Collection</a>
                    </div>
                    <div style="padding:12px 16px;display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:12px;" id="soLinkedPanelsGrid"></div>
                </div>
            </div>

            <!-- Right Sticky Live Mockup -->
            <div class="rfq-mockup-panel-col">
                <div class="rfq-panel-card rfq-mockup-card">
                    <div class="rfq-mockup-header-actions" style="grid-template-columns:repeat(2, minmax(0, 1fr));">
                        <button type="button" class="rfq-btn rfq-btn-outline rfq-mockup-action-btn" onclick="window.print()"><i class="ph ph-printer"></i> <span>Print Document</span></button>
                        <a href="{{ route('accounting.ar', ['tab' => 'credit-review']) }}" class="rfq-btn rfq-btn-outline rfq-mockup-action-btn" style="text-decoration:none;"><i class="ph ph-shield-check"></i> <span>AR Credit Queue</span></a>
                    </div>
                    <div class="rfq-mockup-content-wrap">
                        <div class="rfq-mockup-top-strip">
                            <div class="rfq-mockup-pill-tag"><i class="ph ph-clipboard-text"></i><span id="soMockupTitleTag">Live Document Preview</span></div>
                            <span class="rfq-mockup-live-status"><span class="rfq-live-dot"></span> Live Sync</span>
                        </div>
                        <div id="soLiveMockupBody" class="po-live-mockup-container"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CANONICAL SLIDE-OVER DRAWERS (Zero Popup Modals)                           -->
<!-- ========================================================================= -->
<div class="bom-drawer-backdrop" id="soDrawerBackdrop" onclick="closeAllSoDrawers()"></div>

<!-- Drawer 1: Delivery Order (Stock-Out) Dispatch Drawer -->
<aside class="bom-drawer-container" id="soDeliveryDrawer" role="dialog" aria-modal="true" style="--bom-drawer-width: 660px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title"><i class="ph ph-truck"></i> Create Delivery Order (Stock-Out)</h3>
            <p class="bom-drawer-subtitle">Dispatch full or partial quantities; immediate shipping deducts stock, posts COGS GL &amp; drafts AR Bill</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeAllSoDrawers()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div class="rfq-table-responsive" style="max-height:300px;">
            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:right;">Remaining</th>
                        <th style="text-align:right;">On Hand</th>
                        <th style="width:120px;text-align:right;">Dispatch Qty</th>
                    </tr>
                </thead>
                <tbody id="soDeliveryLinesBody"></tbody>
            </table>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;">
            <div class="rfq-field"><label class="rfq-label">Carrier / Rider</label><input type="text" class="rfq-input" id="soDelCarrier" placeholder="In-House Van / Grab"></div>
            <div class="rfq-field"><label class="rfq-label">Tracking / Waybill #</label><input type="text" class="rfq-input" id="soDelWaybill" placeholder="Optional"></div>
        </div>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeAllSoDrawers()">Cancel</button>
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="submitSoDelivery(false)"><i class="ph ph-package"></i> Queue Draft in Stock-Out</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="submitSoDelivery(true)"><i class="ph ph-truck"></i> Dispatch &amp; Ship Now</button>
    </div>
</aside>

<!-- Drawer 2: Item Master Quick Picker Drawer (F2) -->
<aside class="bom-drawer-container" id="soItemPickerDrawer" role="dialog" aria-modal="true" style="--bom-drawer-width: 620px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title"><i class="ph ph-package"></i> Select Item Master SKUs</h3>
            <p class="bom-drawer-subtitle">Add catalog products with live Inventory ATP availability &amp; selling prices</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeAllSoDrawers()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div style="display:flex;gap:8px;">
            <input type="text" class="rfq-input" id="soPickerSearchInput" oninput="renderSoPickerList()" placeholder="Search SKU, item name, category..." style="flex:1;">
        </div>
        <div id="soPickerList" style="display:flex;flex-direction:column;gap:8px;"></div>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeAllSoDrawers()">Done</button>
    </div>
</aside>

<!-- Drawer 3: Accounting Credit Review Drawer -->
<aside class="bom-drawer-container" id="soCreditReviewDrawer" role="dialog" aria-modal="true" style="--bom-drawer-width: 580px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title"><i class="ph ph-shield-check"></i> Accounting AR Credit Review</h3>
            <p class="bom-drawer-subtitle">Evaluate exposure, raise credit limit or approve override to release ATP stock reservations</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeAllSoDrawers()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div id="soCreditDrawerSummary" class="sp-meta-banner" style="flex-direction:column;align-items:flex-start;gap:6px;"></div>
        <div class="rfq-field">
            <label class="rfq-label" for="soCreditRemarksInput">Review Decision Remarks</label>
            <textarea class="rfq-input" id="soCreditRemarksInput" rows="3" placeholder="Required for credit-limit overrides or rejections..."></textarea>
        </div>
        <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;">
            <div class="rfq-field">
                <label class="rfq-label" for="soCreditNewLimitInput">Raise Credit Limit (Optional ₱)</label>
                <input type="number" step="0.01" min="0" class="rfq-input" id="soCreditNewLimitInput" placeholder="Leave blank to keep">
            </div>
            <div class="rfq-field" style="display:flex;align-items:flex-end;">
                <label style="font-size:12px;display:flex;align-items:center;gap:6px;padding-bottom:8px;">
                    <input type="checkbox" id="soCreditClearHoldCheck"> Clear Customer Credit Hold Flag
                </label>
            </div>
        </div>
    </div>
    <div class="bom-drawer-footer" style="flex-wrap:wrap;">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="submitSoCreditReview('RETURN_TO_DRAFT')">Return to Draft</button>
        <button type="button" class="rfq-btn rfq-btn-danger" onclick="submitSoCreditReview('REJECT')">Reject Order</button>
        <button type="button" class="rfq-btn rfq-btn-outline" style="border-color:#d97706;color:#b45309;" onclick="submitSoCreditReview('APPROVE_OVERRIDE')">Approve Override</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="submitSoCreditReview('APPROVE')"><i class="ph ph-check"></i> Approve Credit</button>
    </div>
</aside>

<!-- Drawer 4: Cancel Order Drawer -->
<aside class="bom-drawer-container" id="soCancelDrawer" role="dialog" aria-modal="true" style="--bom-drawer-width: 500px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title"><i class="ph ph-x-circle" style="color:#dc2626;"></i> Cancel Sales Record</h3>
            <p class="bom-drawer-subtitle">Releases active ATP stock reservations &amp; promo voucher redemptions</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeAllSoDrawers()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div class="sp-meta-banner" style="background:#fef2f2;border-color:#fecaca;color:#991b1b;">
            Cancelling <strong id="soCancelOrderLabel">this order</strong> will release all reserved ATP inventory back to available stock and refund any locked voucher quota.
        </div>
        <div class="rfq-field">
            <label class="rfq-label" for="soCancelReasonInput">Cancellation Reason *</label>
            <textarea class="rfq-input" id="soCancelReasonInput" rows="3" placeholder="e.g. Customer rescheduled event, duplicate entry..."></textarea>
        </div>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeAllSoDrawers()">Keep Order</button>
        <button type="button" class="rfq-btn rfq-btn-danger" onclick="submitSoCancel()"><i class="ph ph-trash"></i> Confirm Cancellation</button>
    </div>
</aside>
@endsection

@extends('layouts.app')

@section('title', 'Customers & CRM 360° - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/vendor-masterlist.css')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@push('blade-data')
<script>
    window.CustomerMasterlistConfig = Object.freeze({
        routes: {
            customersIndex: @json(route('sales.api.customers.index')),
            customersSave: @json(route('sales.api.customers.save')),
            checkDuplicates: @json(route('sales.api.customers.check-duplicates')),
            profile: @json(url('/sales/api/customers/__ID__/profile')),
            convert: @json(url('/sales/api/customers/__ID__/convert')),
            archive: @json(url('/sales/api/customers/__ID__/archive')),
            toggleHold: @json(url('/sales/api/customers/__ID__/toggle-hold')),
            saveContact: @json(url('/sales/api/customers/__ID__/contacts/save')),
            deleteContact: @json(url('/sales/api/customers/__ID__/contacts/__SUBID__')),
            saveAddress: @json(url('/sales/api/customers/__ID__/addresses/save')),
            deleteAddress: @json(url('/sales/api/customers/__ID__/addresses/__SUBID__')),
            savePriceList: @json(url('/sales/api/customers/__ID__/price-list/save')),
            deletePriceList: @json(url('/sales/api/customers/__ID__/price-list/__SUBID__')),
            saveDocument: @json(url('/sales/api/customers/__ID__/documents/save')),
            deleteDocument: @json(url('/sales/api/customers/__ID__/documents/__SUBID__')),
            saveActivity: @json(url('/sales/api/customers/__ID__/activities/save')),
            salesQuotes: @json(route('sales.quotes')),
            salesOrders: @json(route('sales.orders')),
            accountingAr: @json(route('accounting.ar', ['tab' => 'ledger'])),
        },
        data: {
            initialCustomers: @json($initialCustomers ?? []),
            initialTaxCodes: @json($initialTaxCodes ?? []),
            initialPaymentTerms: @json($initialPaymentTerms ?? []),
            initialItems: @json($initialItems ?? []),
            initialEmployees: @json($initialEmployees ?? []),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/customer-masterlist.js')
@endpush

@section('content')
<div class="vendor-page-container" id="customerMasterlistRoot">
    <div class="rfq-toast-container" id="customerToastContainer"></div>

    <!-- ===================================================================== -->
    <!-- DIRECTORY VIEW                                                        -->
    <!-- ===================================================================== -->
    <div id="customerDirectorySection" class="vendor-kanban-section">
        <div class="hr-parent-header">
            <div class="hr-parent-title-row">
                <div>
                    <h1 class="hr-parent-title">
                        <i class="ph ph-address-book"></i>
                        <span>Customer Masterlist &amp; 360° CRM</span>
                    </h1>
                    <p class="hr-parent-subtitle">Enterprise MDM with duplicate detection, account hierarchies, contract price lists, AR aging, compliance vault &amp; CLV analytics.</p>
                </div>
                <div class="hr-page-actions">
                    <a href="{{ route('accounting.ar', ['tab' => 'credit-review']) }}" class="hr-btn hr-btn-secondary cm-no-underline">
                        <i class="ph ph-shield-check"></i><span>AR Credit Queue</span>
                    </a>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="openCustomerDrawer(null, 'PROSPECT')">
                        <i class="ph ph-user-plus"></i><span>New Prospect</span>
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="openCustomerDrawer(null, 'ACTIVE')">
                        <i class="ph ph-plus-circle"></i><span>New Customer</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Executive Directory KPI Strip (Interactive Quick Filters) -->
        <div class="cm-kpi-grid">
            <button type="button" class="rfq-kpi-card cm-kpi-card is-active-filter" data-kpi-tier="ALL" onclick="setCustomerTierFilter('ALL')">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Accounts</span><div class="rfq-kpi-icon cm-kpi-icon-brand"><i class="ph ph-users-three"></i></div></div>
                <div class="rfq-kpi-val cm-tabular" id="kpiCustActive">0</div>
                <div class="rfq-kpi-sub">Verified trading accounts</div>
            </button>
            <button type="button" class="rfq-kpi-card cm-kpi-card" data-kpi-tier="PROSPECT" onclick="setCustomerTierFilter('PROSPECT')">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Open Prospects</span><div class="rfq-kpi-icon cm-kpi-icon-info"><i class="ph ph-sparkle"></i></div></div>
                <div class="rfq-kpi-val cm-tabular" id="kpiCustProspects">0</div>
                <div class="rfq-kpi-sub">Ready for 1-click convert</div>
            </button>
            <button type="button" class="rfq-kpi-card cm-kpi-card" data-kpi-tier="CREDIT_HOLD" onclick="setCustomerTierFilter('CREDIT_HOLD')">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">On Credit Hold</span><div class="rfq-kpi-icon cm-kpi-icon-warning"><i class="ph ph-shield-warning"></i></div></div>
                <div class="rfq-kpi-val cm-tabular" id="kpiCustHold">0</div>
                <div class="rfq-kpi-sub">Blocked pending AR sign-off</div>
            </button>
            <button type="button" class="rfq-kpi-card cm-kpi-card" data-kpi-tier="PORTFOLIO" onclick="setCustomerTierFilter('ALL')">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Portfolio Open AR</span><div class="rfq-kpi-icon cm-kpi-icon-brand"><i class="ph ph-currency-circle-dollar"></i></div></div>
                <div class="rfq-kpi-val cm-tabular" id="kpiCustOpenAr">₱0.00</div>
                <div class="rfq-kpi-sub">CLV: <span id="kpiCustTotalClv" class="cm-tabular">₱0.00</span></div>
            </button>
            <button type="button" class="rfq-kpi-card cm-kpi-card" data-kpi-tier="DOC_ALERTS" onclick="setCustomerTierFilter('DOC_ALERTS')">
                <div class="rfq-kpi-header"><span class="rfq-kpi-label">Compliance Alerts</span><div class="rfq-kpi-icon cm-kpi-icon-danger"><i class="ph ph-file-warning"></i></div></div>
                <div class="rfq-kpi-val cm-tabular" id="kpiCustDocAlerts">0</div>
                <div class="rfq-kpi-sub">Expiring / expired docs</div>
            </button>
        </div>

        <!-- Toolbar: Search, Result Counter, View Mode Switcher & Segment Pills -->
        <div class="cm-toolbar">
            <div class="cm-toolbar-top">
                <div class="cm-search-wrap">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="customerSearchInput" class="cm-search-input" aria-label="Search customers by name, code, TIN, email, or contact" placeholder="Search customer name, code, TIN, email, contact..." oninput="filterCustomerDirectory(this.value)">
                    <button type="button" id="custSearchClearBtn" class="cm-search-clear cm-hidden" onclick="clearCustomerSearch()" aria-label="Clear search">
                        <i class="ph ph-x"></i>
                    </button>
                </div>
                <div class="cm-toolbar-right">
                    <span class="cm-result-count" id="custResultCount">0 customers</span>
                    <div class="cm-view-toggle" role="group" aria-label="Directory layout">
                        <button type="button" class="cm-view-btn is-active" id="custViewToggleList" aria-pressed="true" onclick="setCustomerViewMode('list')">
                            <i class="ph ph-list-bullets"></i><span>List</span>
                        </button>
                        <button type="button" class="cm-view-btn" id="custViewToggleCards" aria-pressed="false" onclick="setCustomerViewMode('cards')">
                            <i class="ph ph-squares-four"></i><span>Cards</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="cm-filter-pills" id="customerTierPills">
                <button type="button" class="cm-filter-pill active" data-tier="ALL" onclick="setCustomerTierFilter('ALL', this)">All Active <span class="cm-pill-count" data-pill-count="ALL">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="PROSPECT" onclick="setCustomerTierFilter('PROSPECT', this)">Prospects <span class="cm-pill-count" data-pill-count="PROSPECT">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="CREDIT_HOLD" onclick="setCustomerTierFilter('CREDIT_HOLD', this)">On Credit Hold <span class="cm-pill-count" data-pill-count="CREDIT_HOLD">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="CORPORATE" onclick="setCustomerTierFilter('CORPORATE', this)">Corporate / B2B <span class="cm-pill-count" data-pill-count="CORPORATE">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="WHOLESALE" onclick="setCustomerTierFilter('WHOLESALE', this)">Wholesale <span class="cm-pill-count" data-pill-count="WHOLESALE">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="VIP" onclick="setCustomerTierFilter('VIP', this)">VIP <span class="cm-pill-count" data-pill-count="VIP">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="DOC_ALERTS" onclick="setCustomerTierFilter('DOC_ALERTS', this)">Doc Alerts <span class="cm-pill-count" data-pill-count="DOC_ALERTS">0</span></button>
                <button type="button" class="cm-filter-pill" data-tier="ARCHIVED" onclick="setCustomerTierFilter('ARCHIVED', this)">Archived <span class="cm-pill-count" data-pill-count="ARCHIVED">0</span></button>
            </div>
        </div>

        <!-- Default Enterprise List View (Table) -->
        <div class="rfq-panel-card cm-list-panel" id="customerListTableWrap">
            <div class="rfq-table-responsive">
                <table class="rfq-table cm-list-table" id="customerListTable">
                    <thead>
                        <tr>
                            <th>Customer &amp; Account</th>
                            <th>Stage &amp; Terms</th>
                            <th>Credit Utilization</th>
                            <th class="cm-th-right">CLV &amp; Orders</th>
                            <th>Compliance</th>
                            <th class="cm-th-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="customerListTbody"></tbody>
                </table>
            </div>
        </div>

        <!-- Optional Cards View -->
        <div class="cm-cards-grid cm-hidden" id="customerKanbanGrid"></div>
    </div>

    <!-- ===================================================================== -->
    <!-- CUSTOMER 360° PROFILE WRAPPER                                         -->
    <!-- ===================================================================== -->
    <div class="vendor-profile-wrapper" id="customerProfileWrapper" style="display:none;">

        <!-- Top Breadcrumb & Navigation Bar -->
        <div class="vendor-detail-nav-bar">
            <div class="vendor-breadcrumb-left">
                <button type="button" class="vendor-back-btn" onclick="closeCustomerProfile()" title="Back to Directory [Esc]">
                    <i class="ph ph-arrow-left"></i><span>Back to All Customers</span><kbd class="vendor-kbd-badge">Esc</kbd>
                </button>
                <span class="vendor-nav-divider">/</span>
                <span class="vendor-nav-category-badge" id="custProfileTierBadge">STANDARD</span>
                <span class="vendor-nav-divider">/</span>
                <span class="vendor-nav-current-name" id="custProfileBreadcrumbName">—</span>
            </div>
            <div class="vendor-nav-actions-right">
                <div class="vendor-nav-counter" id="custProfileCounter">Customer 1 of 1</div>
                <div class="vendor-nav-cycle-btns">
                    <button type="button" class="vendor-btn-secondary vendor-btn-sm" onclick="cycleCustomer(-1)"><i class="ph ph-caret-left"></i> Prev</button>
                    <button type="button" class="vendor-btn-secondary vendor-btn-sm" onclick="cycleCustomer(1)">Next <i class="ph ph-caret-right"></i></button>
                </div>
            </div>
        </div>

        <!-- Executive Hero Banner -->
        <div class="vendor-profile-banner" id="custProfileBanner">
            <div class="vendor-banner-identity">
                <div class="vendor-big-logo-wrap">
                    <div class="vendor-big-logo" id="custBannerAvatar"><span>CU</span></div>
                </div>
                <div class="vendor-banner-info-col">
                    <div class="vendor-banner-title-row">
                        <h2 id="custBannerName">—</h2>
                        <span class="status-pill approved" id="custBannerStagePill">ACTIVE</span>
                    </div>
                    <div class="vendor-banner-meta-pills" id="custBannerMetaPills"></div>
                    <div class="vendor-banner-contact-strip" id="custBannerContactStrip"></div>
                </div>
            </div>

            <!-- 4 Analytics KPI Cards (CLV, Margin, DSO, Credit Avail) -->
            <div class="vendor-banner-kpis">
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box cm-kpi-clv"><i class="ph ph-coins"></i></div>
                    <div>
                        <span class="vendor-kpi-val cm-tabular" id="custKpiClv">₱0.00</span>
                        <span class="vendor-kpi-lbl">Customer LTV (<span id="custKpiFreq">0</span> orders)</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box cm-kpi-margin"><i class="ph ph-trend-up"></i></div>
                    <div>
                        <span class="vendor-kpi-val cm-tabular cm-kpi-margin" id="custKpiMargin">0.0%</span>
                        <span class="vendor-kpi-lbl">Realized Margin</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box cm-kpi-dso"><i class="ph ph-clock"></i></div>
                    <div>
                        <span class="vendor-kpi-val cm-tabular cm-kpi-dso" id="custKpiDso">0d</span>
                        <span class="vendor-kpi-lbl">DSO (Avg Pay Days)</span>
                    </div>
                </div>
                <div class="vendor-kpi-card">
                    <div class="vendor-kpi-icon-box cm-kpi-avail"><i class="ph ph-shield-check"></i></div>
                    <div>
                        <span class="vendor-kpi-val cm-tabular" id="custKpiAvailCredit">₱0.00</span>
                        <span class="vendor-kpi-lbl">Avail Credit / Limit</span>
                    </div>
                </div>
            </div>

            <div class="vendor-banner-actions">
                <button type="button" class="hr-btn cm-btn-convert vendor-hero-cta cm-hidden" id="custBtnConvertProspect" onclick="convertCurrentProspect()">
                    <i class="ph ph-check-circle"></i><span>Convert to Active</span>
                </button>
                <a id="custBtnLaunchOrder" class="hr-btn hr-btn-primary vendor-hero-cta cm-no-underline" href="{{ route('sales.orders') }}">
                    <i class="ph ph-plus-circle"></i><span>Create Sales Order</span>
                </a>
                <div class="cm-banner-action-row">
                    <a id="custBtnLaunchQuote" class="hr-btn hr-btn-secondary cm-banner-sub-btn" href="{{ route('sales.quotes') }}">
                        <i class="ph ph-file-text"></i><span>Quote</span>
                    </a>
                    <button type="button" class="hr-btn hr-btn-secondary cm-banner-sub-btn" onclick="editCurrentCustomer()">
                        <i class="ph ph-pencil-simple"></i><span>Edit</span>
                    </button>
                </div>
                <div class="cm-banner-action-row">
                    <button type="button" class="hr-btn hr-btn-secondary cm-banner-sub-btn" id="custBtnToggleHold" onclick="toggleCurrentCustomerHold()">Hold</button>
                    <button type="button" class="hr-btn hr-btn-secondary cm-banner-sub-btn" id="custBtnToggleArchive" onclick="toggleCurrentCustomerArchive()">Archive</button>
                </div>
            </div>
        </div>

        <!-- 5 Interactive 360° Tabs -->
        <nav class="rfq-nav-tabs-bar" id="custProfileTabsBar">
            <button type="button" class="rfq-tab-btn active" id="custTabBtnOverview" onclick="switchCustomerTab('tab-overview')">
                <i class="ph ph-buildings"></i><span>Overview, Contacts &amp; Branches</span>
            </button>
            <button type="button" class="rfq-tab-btn" id="custTabBtnPipeline" onclick="switchCustomerTab('tab-pipeline')">
                <i class="ph ph-tag"></i><span>Price List &amp; O2C Pipeline</span>
            </button>
            <button type="button" class="rfq-tab-btn" id="custTabBtnAr" onclick="switchCustomerTab('tab-ar')">
                <i class="ph ph-receipt"></i><span>AR, Credit &amp; Aging (SOA)</span>
            </button>
            <button type="button" class="rfq-tab-btn" id="custTabBtnCompliance" onclick="switchCustomerTab('tab-compliance')">
                <i class="ph ph-shield-check"></i><span>Tax &amp; Compliance Vault</span>
            </button>
            <button type="button" class="rfq-tab-btn" id="custTabBtnCrm" onclick="switchCustomerTab('tab-crm')">
                <i class="ph ph-chat-circle-dots"></i><span>CRM Timeline &amp; Audit</span>
            </button>
        </nav>

        <!-- TAB 1: OVERVIEW, CONTACTS, BRANCHES & ACCOUNT HIERARCHY -->
        <div class="so-tab-pane is-active" id="custPaneOverview">
            <div class="cm-grid-2">
                <div class="rfq-panel-card cm-panel">
                    <h3 class="cm-section-title"><i class="ph ph-identification-card"></i> MDM Profile &amp; Account Hierarchy</h3>
                    <div id="custOverviewDetailsGrid" class="cm-overview-grid"></div>
                    <div id="custHierarchySection" class="cm-hierarchy-box"></div>
                </div>

                <!-- Unlimited Contacts -->
                <div class="rfq-panel-card cm-panel">
                    <div class="cm-section-head">
                        <h3 class="cm-section-title"><i class="ph ph-users"></i> Contacts Directory</h3>
                    </div>
                    <div class="cm-inline-form-contacts">
                        <input type="text" class="rfq-input" id="cContactName" aria-label="Contact full name" placeholder="Full Name *">
                        <input type="text" class="rfq-input" id="cContactRole" aria-label="Contact role title" placeholder="Role (AP Clerk, Buyer)">
                        <input type="text" class="rfq-input" id="cContactPhone" aria-label="Contact phone number" placeholder="Phone">
                        <input type="email" class="rfq-input" id="cContactEmail" aria-label="Contact email address" placeholder="Email">
                        <select class="rfq-select" id="cContactDept" aria-label="Contact department"><option value="PURCHASING">Purchasing</option><option value="AP">Accounts Payable</option><option value="EXECUTIVE">Executive</option><option value="RECEIVING">Receiving</option></select>
                        <button type="button" class="rfq-btn rfq-btn-primary cm-inline-submit-btn" onclick="saveCustomerContact()">+ Add Contact</button>
                    </div>
                    <div class="rfq-table-responsive">
                        <table class="rfq-table">
                            <thead><tr><th>Contact &amp; Role</th><th>Email / Phone</th><th>Flags</th><th class="cm-th-act"></th></tr></thead>
                            <tbody id="custContactsTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Multi-Address / Subsidiary Branches -->
            <div class="rfq-panel-card cm-panel">
                <h3 class="cm-section-title"><i class="ph ph-map-pin"></i> Billing &amp; Multi-Branch Shipping Locations</h3>
                <div class="cm-inline-form-addresses">
                    <select class="rfq-select" id="cAddrType" aria-label="Address type"><option value="SHIPPING">Shipping</option><option value="BILLING">Billing</option><option value="BOTH">Both</option></select>
                    <input type="text" class="rfq-input" id="cAddrLabel" aria-label="Branch or hub label" placeholder="Branch / Hub Label *">
                    <input type="text" class="rfq-input" id="cAddrLine" aria-label="Street address and city" placeholder="Street Address, City *">
                    <input type="text" class="rfq-input" id="cAddrRecContact" aria-label="Receiving contact person" placeholder="Receiving Contact">
                    <input type="text" class="rfq-input" id="cAddrWindow" aria-label="Delivery window hours" placeholder="Window (8AM-5PM)">
                    <button type="button" class="rfq-btn rfq-btn-primary cm-inline-submit-btn" onclick="saveCustomerAddress()">+ Add Branch</button>
                </div>
                <div class="rfq-table-responsive">
                    <table class="rfq-table">
                        <thead><tr><th>Type</th><th>Branch Label</th><th>Address</th><th>Receiving Contact</th><th>Window</th><th class="cm-th-act"></th></tr></thead>
                        <tbody id="custAddressesTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: REFERENCE PRICE LIST, CUSTOMER SKUs & LIVE O2C PIPELINE -->
        <div class="so-tab-pane" id="custPanePipeline" style="display:none;">
            <div class="rfq-panel-card cm-panel">
                <div class="cm-section-head">
                    <div>
                        <h3 class="cm-section-title"><i class="ph ph-tag"></i> Customer Contract Price List &amp; Buyer SKU Mapping</h3>
                        <p class="cm-section-sub">Reference negotiated pricing &amp; customer SKU cross-references for Sales Quotes and Orders.</p>
                    </div>
                </div>
                <div class="cm-inline-form-pricelist">
                    <select class="rfq-select" id="cPlItemSelect" aria-label="Item Master SKU" onchange="onPriceListItemChange(this.value)">
                        <option value="">-- Select Item Master SKU --</option>
                        @foreach($initialItems ?? [] as $it)
                            <option value="{{ $it->id }}" data-price="{{ (float) $it->selling_price }}" data-uom="{{ $it->uom ?: 'PCS' }}">[{{ $it->sku }}] {{ $it->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" class="rfq-input" id="cPlCustomerSku" aria-label="Buyer SKU code" placeholder="Buyer SKU Code">
                    <select class="form-control" id="cPlUomSelect" aria-label="Unit of Measure" data-uom-select data-uom-category="ALL" data-uom-current="PCS"></select>
                    <input type="number" step="0.01" min="0" class="rfq-input" id="cPlPrice" aria-label="Contract price" placeholder="Contract ₱ *">
                    <input type="date" class="rfq-input" id="cPlValidFrom" aria-label="Valid from date" title="Valid From">
                    <input type="date" class="rfq-input" id="cPlValidUntil" aria-label="Valid until date" title="Valid Until">
                    <button type="button" class="rfq-btn rfq-btn-primary cm-inline-submit-btn" onclick="saveCustomerPriceList()">+ Save Rate</button>
                </div>
                <div class="rfq-table-responsive">
                    <table class="rfq-table">
                        <thead><tr><th>Internal SKU &amp; Item</th><th>Customer SKU</th><th>UOM</th><th class="cm-th-right">Std Price</th><th class="cm-th-right">Contract Price</th><th>Savings</th><th>Validity</th><th class="cm-th-act"></th></tr></thead>
                        <tbody id="custPriceListTbody"></tbody>
                    </table>
                </div>
            </div>

            <div class="cm-grid-2">
                <div class="rfq-panel-card cm-panel">
                    <h3 class="cm-section-title"><i class="ph ph-file-text"></i> Open Quotes &amp; Active Sales Orders</h3>
                    <div class="rfq-table-responsive">
                        <table class="rfq-table">
                            <thead><tr><th>Doc #</th><th>Date</th><th class="cm-th-right">Total</th><th>Status</th></tr></thead>
                            <tbody id="custPipelineOrdersTbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="rfq-panel-card cm-panel">
                    <h3 class="cm-section-title"><i class="ph ph-truck"></i> Backorders &amp; Delivery History</h3>
                    <div class="rfq-table-responsive">
                        <table class="rfq-table">
                            <thead><tr><th>Delivery #</th><th>SO Ref</th><th class="cm-th-right">Cost Value</th><th>Status</th></tr></thead>
                            <tbody id="custPipelineDeliveriesTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 3: AR, CREDIT & AGING BUCKETS + PRINTABLE SOA -->
        <div class="so-tab-pane" id="custPaneAr" style="display:none;">
            <div class="cm-section-head">
                <h3 class="cm-section-title">Live Accounts Receivable Aging &amp; Statement of Account</h3>
                <button type="button" class="rfq-btn rfq-btn-primary" onclick="openSoaDrawer()"><i class="ph ph-printer"></i> View &amp; Print Statement of Account (SOA)</button>
            </div>
            <div class="cm-kpi-grid" id="custAgingBucketsGrid"></div>

            <div class="cm-grid-2">
                <div class="rfq-panel-card cm-panel">
                    <h4 class="cm-sub-title">Sales Invoices</h4>
                    <div class="rfq-table-responsive">
                        <table class="rfq-table">
                            <thead><tr><th>Invoice #</th><th>Due Date</th><th class="cm-th-right">Total</th><th class="cm-th-right">Balance Due</th><th>Status</th></tr></thead>
                            <tbody id="custInvoicesTbody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="rfq-panel-card cm-panel">
                    <h4 class="cm-sub-title">Collection Receipts &amp; Credit/Debit Memos</h4>
                    <div class="rfq-table-responsive">
                        <table class="rfq-table">
                            <thead><tr><th>Ref #</th><th>Date</th><th>Method / Type</th><th class="cm-th-right">Amount</th><th>Status</th></tr></thead>
                            <tbody id="custReceiptsMemosTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- TAB 4: TAX & COMPLIANCE DOCUMENT VAULT -->
        <div class="so-tab-pane" id="custPaneCompliance" style="display:none;">
            <div class="rfq-panel-card cm-panel">
                <h3 class="cm-section-title"><i class="ph ph-shield-check"></i> Tax Classification &amp; Compliance Document Repository</h3>
                <div id="custTaxSummaryStrip" class="sp-meta-banner cm-tax-strip"></div>
                <div class="cm-inline-form-docs">
                    <select class="rfq-select" id="cDocType" aria-label="Compliance document type">
                        <option value="BIR_2303_COR">BIR 2303 (COR)</option>
                        <option value="TAX_EXEMPTION">Tax Exemption Cert</option>
                        <option value="CONTRACT">Signed Contract / MSA</option>
                        <option value="NDA">Non-Disclosure (NDA)</option>
                        <option value="CREDIT_APP">Credit Application</option>
                    </select>
                    <input type="text" class="rfq-input" id="cDocTitle" aria-label="Document title" placeholder="Document Title *">
                    <input type="text" class="rfq-input" id="cDocRef" aria-label="Certificate or reference number" placeholder="Cert / Ref #">
                    <input type="date" class="rfq-input" id="cDocIssue" aria-label="Document issue date" title="Issue Date">
                    <input type="date" class="rfq-input" id="cDocExpiry" aria-label="Document expiry date" title="Expiry Date (for alerts)">
                    <input type="file" class="rfq-input cm-file-input" id="cDocFile" aria-label="Attach compliance document file">
                    <button type="button" class="rfq-btn rfq-btn-primary cm-inline-submit-btn" onclick="saveCustomerDocument()">+ Save Doc</button>
                </div>
                <div class="rfq-table-responsive">
                    <table class="rfq-table">
                        <thead><tr><th>Type</th><th>Title &amp; Reference</th><th>Issue Date</th><th>Expiry Status</th><th>Uploaded By / File</th><th class="cm-th-act"></th></tr></thead>
                        <tbody id="custDocsTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 5: CRM TIMELINE & AUDIT -->
        <div class="so-tab-pane" id="custPaneCrm" style="display:none;">
            <div class="rfq-panel-card cm-panel">
                <h3 class="cm-section-title"><i class="ph ph-chat-circle-dots"></i> Communication, Dunning Reminders &amp; Audit Timeline</h3>
                <div class="cm-inline-form-crm">
                    <select class="rfq-select" id="cActType" aria-label="CRM activity type">
                        <option value="CALL">Phone Call</option>
                        <option value="EMAIL">Email</option>
                        <option value="MEETING">Meeting</option>
                        <option value="REMINDER">Payment Reminder</option>
                        <option value="NOTE">Internal Note</option>
                    </select>
                    <input type="text" class="rfq-input" id="cActSubject" aria-label="Activity subject" placeholder="Subject (e.g. SOA Follow-up) *">
                    <input type="text" class="rfq-input" id="cActDesc" aria-label="Activity summary notes" placeholder="Summary notes / action items...">
                    <input type="date" class="rfq-input" id="cActFollowUp" aria-label="Activity follow-up date" title="Follow-Up Date">
                    <button type="button" class="rfq-btn rfq-btn-primary cm-inline-submit-btn" onclick="saveCustomerActivity()">+ Log Activity</button>
                </div>
                <div class="rfq-table-responsive">
                    <table class="rfq-table">
                        <thead><tr><th>Timestamp</th><th>Type</th><th>Subject &amp; Details</th><th>Follow-Up</th><th>Actor</th></tr></thead>
                        <tbody id="custActivitiesTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- CANONICAL SLIDE-OVER DRAWERS (Zero Popup Modals)                           -->
<!-- ========================================================================= -->
<div class="bom-drawer-backdrop" id="custDrawerBackdrop" role="presentation" aria-hidden="true"></div>

<!-- Drawer 1: Register / Edit Customer MDM -->
<aside class="bom-drawer-container" id="customerFormDrawer" role="dialog" aria-modal="true" aria-labelledby="customerDrawerTitle">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title" id="customerDrawerTitle"><i class="ph ph-user-plus"></i> Register Customer</h3>
            <p class="bom-drawer-subtitle">MDM profile with real-time duplicate detection, hierarchy &amp; Accounting defaults</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeCustomerDrawer()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div id="custDuplicateAlert" class="sp-meta-banner cm-dup-alert"></div>
        <input type="hidden" id="custIdInput" value="">

        <div class="cm-drawer-grid-2">
            <div class="rfq-field">
                <label class="rfq-label" for="custNameInput">Business / Legal Name *</label>
                <input type="text" class="rfq-input" id="custNameInput" placeholder="e.g. Manila Catering Corp." oninput="debounceDuplicateCheck()">
            </div>
            <div class="rfq-field">
                <label class="rfq-label" for="custStageInput">Account Stage</label>
                <select class="rfq-select" id="custStageInput">
                    <option value="ACTIVE">Active Customer</option>
                    <option value="PROSPECT">Lead / Prospect</option>
                </select>
            </div>
            <div class="rfq-field">
                <label class="rfq-label" for="custTinInput">TIN (Tax ID)</label>
                <input type="text" class="rfq-input" id="custTinInput" placeholder="000-000-000-000" oninput="debounceDuplicateCheck()">
            </div>
            <div class="rfq-field">
                <label class="rfq-label" for="custEmailInput">Billing Email</label>
                <input type="email" class="rfq-input" id="custEmailInput" placeholder="billing@company.ph" oninput="debounceDuplicateCheck()">
            </div>
            <div class="rfq-field">
                <label class="rfq-label" for="custTermsInput">Payment Terms (Accounting)</label>
                <select class="rfq-select" id="custTermsInput">
                    @foreach($initialPaymentTerms ?? [] as $pt)
                        <option value="{{ strtoupper((string) $pt->term_code) }}">{{ $pt->term_name }}</option>
                    @endforeach
                    @if(empty($initialPaymentTerms) || count($initialPaymentTerms) === 0)
                        <option value="NET_30">Net 30 Days</option>
                        <option value="NET_15">Net 15 Days</option>
                        <option value="COD">Cash on Delivery (COD)</option>
                    @endif
                </select>
            </div>
            <div class="rfq-field">
                <label class="rfq-label" for="custCreditLimitInput">Credit Limit (₱)</label>
                <input type="number" step="0.01" min="0" class="rfq-input" id="custCreditLimitInput" value="50000">
            </div>
        </div>

        <details class="cm-drawer-details">
            <summary class="cm-drawer-summary">Hierarchy (Parent HQ), Contact, Tax Default &amp; Address</summary>
            <div class="cm-drawer-details-grid">
                <div class="rfq-field">
                    <label class="rfq-label" for="custCodeInput">Customer Code</label>
                    <input type="text" class="rfq-input" id="custCodeInput" placeholder="Auto-generated if blank">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custParentInput">Parent Company (HQ)</label>
                    <select class="rfq-select" id="custParentInput"><option value="">-- Standalone / HQ --</option></select>
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custTierInput">Customer Tier</label>
                    <select class="rfq-select" id="custTierInput"><option value="STANDARD">Standard</option><option value="CORPORATE">Corporate / B2B</option><option value="WHOLESALE">Wholesale</option><option value="VIP">VIP</option></select>
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custTaxInput">Default Output Tax (Accounting)</label>
                    <select class="rfq-select" id="custTaxInput">
                        @foreach($initialTaxCodes ?? [] as $tc)
                            @if(in_array(strtoupper((string) $tc->tax_type), ['VAT_OUTPUT', 'EXEMPT', 'ZERO_RATED'], true))
                                <option value="{{ $tc->tax_code }}">{{ $tc->tax_code }} — {{ $tc->tax_name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custContactInput">Primary Contact Person</label>
                    <input type="text" class="rfq-input" id="custContactInput" placeholder="Contact name">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custPhoneInput">Phone</label>
                    <input type="text" class="rfq-input" id="custPhoneInput" placeholder="+63 917 ...">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custSalesRepInput">Assigned Sales Rep (HR)</label>
                    <select class="rfq-select" id="custSalesRepInput">
                        <option value="">-- Unassigned --</option>
                        @foreach($initialEmployees ?? [] as $emp)
                            <option value="{{ $emp['full_name'] }}">{{ $emp['full_name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custTaxCertInput">Tax Exemption Cert #</label>
                    <input type="text" class="rfq-input" id="custTaxCertInput" placeholder="Optional">
                </div>
                <div class="rfq-field cm-span-2">
                    <label class="rfq-label" for="custAddressInput">Billing / Primary Address</label>
                    <input type="text" class="rfq-input" id="custAddressInput" placeholder="Street, City, Metro Manila">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custIndustryInput">Industry / Segment</label>
                    <input type="text" class="rfq-input" id="custIndustryInput" placeholder="Hospitality, Catering, Retail...">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custGraceInput">Credit Grace Days</label>
                    <input type="number" min="0" max="365" class="rfq-input" id="custGraceInput" value="15">
                </div>
                <div class="rfq-field cm-span-2">
                    <label class="rfq-label" for="custNotesInput">Internal Notes</label>
                    <input type="text" class="rfq-input" id="custNotesInput">
                </div>
            </div>
        </details>

        <div id="custAllowDupWrap" class="cm-allow-dup-box">
            <label class="cm-allow-dup-label" for="custAllowDupCheck">
                <input type="checkbox" id="custAllowDupCheck"> Ignore duplicate warning and save as a separate account
            </label>
        </div>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeCustomerDrawer()">Cancel</button>
        <button type="button" class="rfq-btn rfq-btn-primary" id="btnSaveCustomer" onclick="submitCustomerForm()"><i class="ph ph-check"></i> Save Customer</button>
    </div>
</aside>

<!-- Drawer 2: Printable Statement of Account (SOA) -->
<aside class="bom-drawer-container" id="customerSoaDrawer" role="dialog" aria-modal="true" aria-labelledby="customerSoaTitle">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title" id="customerSoaTitle"><i class="ph ph-file-text"></i> Monthly Statement of Account (SOA)</h3>
            <p class="bom-drawer-subtitle">Chronological AR ledger with Invoices, Collection Receipts &amp; Memos</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeCustomerDrawer()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body" id="customerSoaBody"></div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeCustomerDrawer()">Close</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="window.print()"><i class="ph ph-printer"></i> Print SOA</button>
    </div>
</aside>
@endsection

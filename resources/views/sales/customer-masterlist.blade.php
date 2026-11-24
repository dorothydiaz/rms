@extends('layouts.app')

@section('title', 'Customer Masterlist & Credit Directory - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/vendor-masterlist.css')
@endpush

@push('blade-data')
<script>
    window.CustomerMasterlistConfig = Object.freeze({
        routes: {
            customersIndex: @json(route('sales.api.customers.index')),
            customersSave: @json(route('sales.api.customers.save')),
            toggleHold: @json(url('/sales/api/customers/__ID__/toggle-hold')),
            salesQuotes: @json(route('sales.quotes')),
            salesOrders: @json(route('sales.orders')),
            accountingAr: @json(route('accounting.ar', ['tab' => 'ledger'])),
        },
        data: {
            initialCustomers: @json($initialCustomers ?? []),
            initialTaxCodes: @json($initialTaxCodes ?? []),
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

    <!-- 1. Header Bar (Vendor Masterlist Parity) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-address-book"></i>
                    <span>Customer Masterlist &amp; Credit Directory</span>
                </h1>
                <p class="hr-parent-subtitle">Single Source of Truth shared with Accounting AR — manage customer credit limits, payment terms, and instant O2C launch.</p>
            </div>
            <div class="vendor-header-actions">
                <a href="{{ route('accounting.ar', ['tab' => 'credit-review']) }}" class="vendor-btn-secondary" style="text-decoration:none;">
                    <i class="ph ph-shield-check"></i>
                    <span>AR Credit Queue</span>
                </a>
                <button type="button" class="vendor-btn-primary" onclick="openCustomerModal()">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Customer</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. Search & Tier Filter Toolbar -->
    <div class="vendor-kanban-section">
        <div class="vendor-kanban-toolbar">
            <div class="vendor-search-wrap">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="customerSearchInput" class="vendor-search-field" placeholder="Search customer name, code, TIN, contact..." oninput="filterCustomerDirectory(this.value)">
            </div>
            <div class="vendor-filter-container">
                <div class="vendor-filter-pills" id="customerTierPills">
                    <button type="button" class="vendor-filter-pill active" data-tier="ALL" onclick="setCustomerTierFilter('ALL', this)">All Customers</button>
                    <button type="button" class="vendor-filter-pill" data-tier="CREDIT_HOLD" onclick="setCustomerTierFilter('CREDIT_HOLD', this)">On Credit Hold</button>
                    <button type="button" class="vendor-filter-pill" data-tier="CORPORATE" onclick="setCustomerTierFilter('CORPORATE', this)">Corporate / B2B</button>
                    <button type="button" class="vendor-filter-pill" data-tier="WHOLESALE" onclick="setCustomerTierFilter('WHOLESALE', this)">Wholesale</button>
                    <button type="button" class="vendor-filter-pill" data-tier="STANDARD" onclick="setCustomerTierFilter('STANDARD', this)">Standard</button>
                </div>
            </div>
        </div>

        <!-- Customer Directory Cards Grid -->
        <div class="vendor-kanban-grid" id="customerKanbanGrid"></div>
    </div>
</div>

<!-- Add / Edit Customer Modal (Anti-Bloat 6 Primary Inputs + Collapsible Details) -->
<div class="rfq-modal-backdrop" id="customerFormModal" style="display:none;align-items:center;justify-content:center;">
    <div class="rfq-modal" style="max-width:620px;width:95%;">
        <div class="rfq-modal-header">
            <h3 class="rfq-modal-title" id="customerModalTitle"><i class="ph ph-user-plus"></i> Register Customer</h3>
            <button type="button" class="rfq-modal-close" onclick="closeCustomerModal()">&times;</button>
        </div>
        <div class="rfq-modal-body" style="padding:18px 20px;">
            <input type="hidden" id="custIdInput" value="">
            <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:12px;">
                <div class="rfq-field">
                    <label class="rfq-label" for="custNameInput">Customer Name *</label>
                    <input type="text" class="rfq-input" id="custNameInput" placeholder="e.g. Manila Catering Corp.">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custCodeInput">Customer Code</label>
                    <input type="text" class="rfq-input" id="custCodeInput" placeholder="Auto-generated if blank">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custTermsInput">Payment Terms</label>
                    <select class="rfq-select" id="custTermsInput">
                        <option value="NET_30">Net 30 Days</option>
                        <option value="NET_15">Net 15 Days</option>
                        <option value="NET_7">Net 7 Days</option>
                        <option value="COD">Cash on Delivery (COD)</option>
                        <option value="ADVANCE">100% Advance Payment</option>
                    </select>
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custCreditLimitInput">Credit Limit (PHP)</label>
                    <input type="number" step="0.01" min="0" class="rfq-input" id="custCreditLimitInput" value="50000">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custTinInput">TIN (Tax ID)</label>
                    <input type="text" class="rfq-input" id="custTinInput" placeholder="000-000-000-000">
                </div>
                <div class="rfq-field">
                    <label class="rfq-label" for="custContactInput">Contact Person &amp; Phone / Email</label>
                    <input type="text" class="rfq-input" id="custContactInput" placeholder="Contact name">
                </div>
            </div>

            <details style="margin-top:12px;border:1px solid #e2e8f0;border-radius:8px;padding:10px 12px;background:#f8fafc;">
                <summary style="cursor:pointer;font-weight:700;font-size:0.80rem;color:#475569;">Optional Address, Tier, Email &amp; Tax Defaults</summary>
                <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;margin-top:10px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="custEmailInput">Email</label>
                        <input type="email" class="rfq-input" id="custEmailInput" placeholder="billing@company.ph">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="custPhoneInput">Phone</label>
                        <input type="text" class="rfq-input" id="custPhoneInput" placeholder="+63 917 ...">
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="custTierInput">Customer Tier</label>
                        <select class="rfq-select" id="custTierInput">
                            <option value="STANDARD">Standard</option>
                            <option value="CORPORATE">Corporate / B2B</option>
                            <option value="WHOLESALE">Wholesale</option>
                            <option value="VIP">VIP</option>
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="custTaxInput">Default Tax Code</label>
                        <select class="rfq-select" id="custTaxInput">
                            <option value="VAT12_OUT">12% Output VAT (VAT12_OUT)</option>
                            <option value="VAT_ZERO">0% Zero-Rated (VAT_ZERO)</option>
                            <option value="VAT_EXEMPT">VAT Exempt (VAT_EXEMPT)</option>
                        </select>
                    </div>
                    <div class="rfq-field" style="grid-column: span 2;">
                        <label class="rfq-label" for="custAddressInput">Billing / Delivery Address</label>
                        <input type="text" class="rfq-input" id="custAddressInput" placeholder="Street, City, Metro Manila">
                    </div>
                </div>
            </details>
        </div>
        <div class="rfq-modal-footer" style="padding:12px 20px;display:flex;justify-content:flex-end;gap:8px;border-top:1px solid #e2e8f0;">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeCustomerModal()">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnSaveCustomer" onclick="submitCustomerForm()">
                <i class="ph ph-check"></i> Save Customer
            </button>
        </div>
    </div>
</div>
@endsection

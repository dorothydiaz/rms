@extends('layouts.app')

@section('title', 'Discount Configuration - Sales Promotions')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@push('blade-data')
<script>
    window.SalesPromoConfig = Object.freeze({
        mode: 'discounts',
        routes: {
            saveDiscount: @json(route('sales.api.discounts.save')),
            toggleDiscount: @json(url('/sales/api/discounts/__ID__/toggle')),
            telemetry: @json(route('sales.api.promotions.telemetry', ['module' => 'discounts'])),
            salesOrderUrl: @json(route('sales.orders')),
        },
        initialDiscounts: @json($initialDiscounts ?? []),
        initialTelemetry: @json($initialTelemetry ?? ['usage_summary' => [], 'usage_logs' => [], 'audit_logs' => []]),
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/sales-promotions.js')
@endpush

@section('content')
<div class="rfq-workspace-container">
    <div class="rfq-toast-container" id="spToastContainer"></div>

    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title"><i class="ph ph-percent"></i><span>Discount Configuration</span></h1>
                <p class="hr-parent-subtitle">Commercial promotions, scheduled discount windows, BIR Senior/PWD VAT-exempt rules, live usage &amp; audit trail.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}" class="hr-btn hr-btn-secondary"><i class="ph ph-arrow-left"></i><span>Back to Orders</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="openDiscountDrawer()"><i class="ph ph-plus-circle"></i><span>New Discount Rule</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Configured Rules</span><div class="rfq-kpi-icon"><i class="ph ph-tag"></i></div></div><div class="rfq-kpi-val" id="kpiDiscTotal">0</div><div class="rfq-kpi-sub">Active: <span id="kpiDiscActive">0</span></div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">BIR VAT-Exempt</span><div class="rfq-kpi-icon" style="background:#fef3c7;color:#92400e;"><i class="ph ph-seal-check"></i></div></div><div class="rfq-kpi-val" id="kpiDiscVatExempt">0</div><div class="rfq-kpi-sub">Senior / PWD tax overrides</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Scheduled / Expired</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-calendar-check"></i></div></div><div class="rfq-kpi-val" id="kpiDiscScheduled">0</div><div class="rfq-kpi-sub">Time-bounded promos</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Confirmed Orders Used</span><div class="rfq-kpi-icon awarded"><i class="ph ph-chart-bar"></i></div></div><div class="rfq-kpi-val" id="kpiDiscUsesTotal">0</div><div class="rfq-kpi-sub">Savings: <span id="kpiDiscImpactTotal">₱0.00</span></div></div>
    </div>

    <nav class="rfq-nav-tabs-bar" id="spTabsBar">
        <button type="button" class="rfq-tab-btn active" id="spTabBtnCatalog" onclick="switchSpTab('tab-catalog')">
            <i class="ph ph-tag"></i><span>Discount Rules</span><span class="rfq-tab-count" id="spTabCatalogCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="spTabBtnUsage" onclick="switchSpTab('tab-usage')">
            <i class="ph ph-chart-pie-slice"></i><span>Usage per Module</span><span class="rfq-tab-count" id="spTabUsageCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="spTabBtnAudit" onclick="switchSpTab('tab-audit')">
            <i class="ph ph-clock-counter-clockwise"></i><span>Audit Trail</span><span class="rfq-tab-count" id="spTabAuditCount">0</span>
        </button>
    </nav>

    <!-- TAB 1: CATALOG -->
    <div class="so-tab-pane is-active" id="sp-pane-catalog" style="display:flex;">
        <div class="rfq-panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #e2e8f0;gap:10px;flex-wrap:wrap;">
                <input type="text" class="rfq-input" oninput="filterSpSearch(this.value)" placeholder="Search code, rule name, category, author..." style="max-width:320px;height:32px;font-size:12px;">
                <button type="button" class="rfq-btn rfq-btn-primary" onclick="openDiscountDrawer()"><i class="ph ph-plus"></i> New Discount Rule</button>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Code &amp; Category</th>
                            <th>Rule Name</th>
                            <th>Rate / Value</th>
                            <th>Duration &amp; Schedule</th>
                            <th>Usage</th>
                            <th>Created / Updated By</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="spDiscountTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 2: USAGE PER MODULE -->
    <div class="so-tab-pane" id="sp-pane-usage" style="display:none;">
        <div class="rfq-panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #e2e8f0;gap:10px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <select class="rfq-select" id="spUsagePromoFilter" onchange="renderUsageTab()" style="width:220px;height:32px;font-size:12px;">
                        <option value="ALL">All Discount Rules</option>
                    </select>
                    <input type="text" class="rfq-input" id="spUsageSearchInput" oninput="renderUsageTab()" placeholder="Search SO #, customer..." style="width:220px;height:32px;font-size:12px;">
                </div>
                <div style="font-size:12px;font-weight:700;color:#475569;" id="spUsageTotalsSummary"></div>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Discount Code</th>
                            <th>Sales Order #</th>
                            <th>Customer</th>
                            <th style="text-align:right;">Discount Given</th>
                            <th style="text-align:right;">Order Gross</th>
                            <th>SO Status</th>
                            <th>Used At</th>
                            <th>Actor</th>
                        </tr>
                    </thead>
                    <tbody id="spUsageTbody"></tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- TAB 3: AUDIT TRAIL -->
    <div class="so-tab-pane" id="sp-pane-audit" style="display:none;">
        <div class="rfq-panel-card">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 16px;border-bottom:1px solid #e2e8f0;gap:10px;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <select class="rfq-select" id="spAuditActionFilter" onchange="renderAuditTab()" style="width:200px;height:32px;font-size:12px;">
                        <option value="ALL">All Actions</option>
                        <option value="CREATED">Created</option>
                        <option value="UPDATED">Updated</option>
                        <option value="ACTIVATED">Activated</option>
                        <option value="DISABLED">Disabled</option>
                        <option value="APPLIED_TO_ORDER">Applied to Order</option>
                    </select>
                    <input type="text" class="rfq-input" id="spAuditSearchInput" oninput="renderAuditTab()" placeholder="Search code, actor, details..." style="width:240px;height:32px;font-size:12px;">
                </div>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Timestamp</th>
                            <th>Actor</th>
                            <th>Action</th>
                            <th>Promo Code &amp; Name</th>
                            <th>Summary</th>
                        </tr>
                    </thead>
                    <tbody id="spAuditTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- SLIDE-OVER DRAWER (Replaces legacy modal popup) -->
<div class="bom-drawer-backdrop" id="spDrawerBackdrop" onclick="closePromoDrawer()"></div>
<aside class="bom-drawer-container" id="spPromoDrawer" role="dialog" aria-modal="true" aria-labelledby="spDrawerTitle" style="--bom-drawer-width: 620px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title" id="spDrawerTitle"><i class="ph ph-percent"></i> Discount Rule</h3>
            <p class="bom-drawer-subtitle">Configure pricing rate, promo duration window &amp; BIR tax override</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closePromoDrawer()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div class="sp-meta-banner" id="spDrawerMetaBanner" style="display:none;"></div>
        <input type="hidden" id="spDiscId">

        <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;">
            <div class="rfq-field"><label class="rfq-label">Rule Code *</label><input type="text" class="rfq-input" id="spDiscCode" placeholder="SENIOR20" style="text-transform:uppercase;"></div>
            <div class="rfq-field"><label class="rfq-label">Category</label><select class="rfq-select" id="spDiscCat"><option value="COMMERCIAL">Commercial Promo</option><option value="SENIOR_CITIZEN">BIR Senior Citizen</option><option value="PWD">BIR PWD</option><option value="VIP_TIER">VIP / Wholesale</option></select></div>
            <div class="rfq-field" style="grid-column:span 2;"><label class="rfq-label">Rule Name *</label><input type="text" class="rfq-input" id="spDiscName" placeholder="20% Senior Citizen Statutory Discount"></div>
            <div class="rfq-field"><label class="rfq-label">Type</label><select class="rfq-select" id="spDiscType"><option value="PERCENT">Percent (%)</option><option value="FIXED">Fixed Amount (₱)</option></select></div>
            <div class="rfq-field"><label class="rfq-label">Value *</label><input type="number" step="0.01" min="0" class="rfq-input" id="spDiscVal"></div>
        </div>

        <!-- Promo Duration & Schedule -->
        <div class="sp-duration-box">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                <strong style="font-size:12px;color:#0f172a;"><i class="ph ph-calendar-blank"></i> Promo Duration &amp; Schedule</strong>
                <div class="sp-duration-toggle">
                    <button type="button" class="sp-duration-mode-btn is-active" id="spDurBtnAlways" onclick="setDurationMode('ALWAYS')">Always Active</button>
                    <button type="button" class="sp-duration-mode-btn" id="spDurBtnScheduled" onclick="setDurationMode('SCHEDULED')">Scheduled Dates</button>
                </div>
            </div>
            <input type="hidden" id="spDurMode" value="ALWAYS">
            <div id="spDurScheduleFields" style="display:none;flex-direction:column;gap:8px;">
                <div class="sp-duration-presets">
                    <span style="font-size:11px;color:#64748b;font-weight:600;">Quick Duration:</span>
                    <button type="button" class="sp-preset-chip" onclick="applyDurationPreset('7d')">+7 Days</button>
                    <button type="button" class="sp-preset-chip" onclick="applyDurationPreset('14d')">+14 Days</button>
                    <button type="button" class="sp-preset-chip" onclick="applyDurationPreset('30d')">+30 Days</button>
                    <button type="button" class="sp-preset-chip" onclick="applyDurationPreset('month')">This Month</button>
                </div>
                <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;">
                    <div class="rfq-field"><label class="rfq-label">Start Date</label><input type="date" class="rfq-input" id="spDurValidFrom" oninput="updateDurationPreview()"></div>
                    <div class="rfq-field"><label class="rfq-label">End Date</label><input type="date" class="rfq-input" id="spDurValidUntil" oninput="updateDurationPreview()"></div>
                </div>
            </div>
            <div class="sp-duration-preview" id="spDurationPreviewText"><i class="ph ph-infinity"></i> Always Active (no expiration window)</div>
        </div>

        <details>
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748b;">Thresholds, Customer Tier &amp; BIR VAT-Exempt Controls</summary>
            <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;margin-top:8px;">
                <div class="rfq-field"><label class="rfq-label">Min Order Amount (₱)</label><input type="number" step="0.01" min="0" class="rfq-input" id="spDiscMinOrder" value="0"></div>
                <div class="rfq-field"><label class="rfq-label">Max Discount Cap (₱)</label><input type="number" step="0.01" min="0" class="rfq-input" id="spDiscCap" placeholder="Unlimited"></div>
                <div class="rfq-field"><label class="rfq-label">Customer Tier</label><select class="rfq-select" id="spDiscTier"><option value="ALL">All Tiers</option><option value="RETAIL">Retail</option><option value="WHOLESALE">Wholesale</option><option value="VIP">VIP</option></select></div>
                <div class="rfq-field" style="display:flex;flex-direction:column;justify-content:center;gap:6px;">
                    <label style="font-size:12px;"><input type="checkbox" id="spDiscVatExempt"> BIR VAT-Exempt Override</label>
                    <label style="font-size:12px;"><input type="checkbox" id="spDiscMgrApproval"> Require Manager Approval</label>
                </div>
                <div class="rfq-field" style="grid-column:span 2;"><label class="rfq-label">Notes</label><input type="text" class="rfq-input" id="spDiscNotes"></div>
            </div>
        </details>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closePromoDrawer()">Cancel</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="saveDiscountRule()"><i class="ph ph-check"></i> Save Rule</button>
    </div>
</aside>
@endsection

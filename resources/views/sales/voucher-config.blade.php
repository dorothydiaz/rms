@extends('layouts.app')

@section('title', 'Voucher Configuration - Sales Promotions')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@push('blade-data')
<script>
    window.SalesPromoConfig = Object.freeze({
        mode: 'vouchers',
        routes: {
            saveVoucher: @json(route('sales.api.vouchers.save')),
            toggleVoucher: @json(url('/sales/api/vouchers/__ID__/toggle')),
            telemetry: @json(route('sales.api.promotions.telemetry', ['module' => 'vouchers'])),
            salesOrderUrl: @json(route('sales.orders')),
        },
        initialVouchers: @json($initialVouchers ?? []),
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
                <h1 class="hr-parent-title"><i class="ph ph-ticket"></i><span>Voucher Configuration</span></h1>
                <p class="hr-parent-subtitle">Trackable promo vouchers with scheduled duration windows, atomic quotas, redemption tracking &amp; audit trail.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}" class="hr-btn hr-btn-secondary"><i class="ph ph-arrow-left"></i><span>Back to Orders</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="openVoucherDrawer()"><i class="ph ph-plus-circle"></i><span>New Voucher</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Total Vouchers</span><div class="rfq-kpi-icon"><i class="ph ph-ticket"></i></div></div><div class="rfq-kpi-val" id="kpiVchTotal">0</div><div class="rfq-kpi-sub">Active: <span id="kpiVchActive">0</span></div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Active Redemptions</span><div class="rfq-kpi-icon awarded"><i class="ph ph-users"></i></div></div><div class="rfq-kpi-val" id="kpiVchRedeemed">0</div><div class="rfq-kpi-sub">Discount: <span id="kpiVchImpactTotal">₱0.00</span></div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Scheduled / Expiring</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-calendar-check"></i></div></div><div class="rfq-kpi-val" id="kpiVchScheduled">0</div><div class="rfq-kpi-sub">Date-gated campaigns</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Customer-Exclusive</span><div class="rfq-kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-user-focus"></i></div></div><div class="rfq-kpi-val" id="kpiVchTargeted">0</div><div class="rfq-kpi-sub">VIP / Partner targeted</div></div>
    </div>

    <nav class="rfq-nav-tabs-bar" id="spTabsBar">
        <button type="button" class="rfq-tab-btn active" id="spTabBtnCatalog" onclick="switchSpTab('tab-catalog')">
            <i class="ph ph-ticket"></i><span>Promo Vouchers</span><span class="rfq-tab-count" id="spTabCatalogCount">0</span>
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
                <input type="text" class="rfq-input" oninput="filterSpSearch(this.value)" placeholder="Search voucher code, campaign, author..." style="max-width:320px;height:32px;font-size:12px;">
                <button type="button" class="rfq-btn rfq-btn-primary" onclick="openVoucherDrawer()"><i class="ph ph-plus"></i> New Voucher</button>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Campaign &amp; Target</th>
                            <th>Reward</th>
                            <th>Duration &amp; Schedule</th>
                            <th>Quota / Usage</th>
                            <th>Created / Updated By</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="spVoucherTbody"></tbody>
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
                        <option value="ALL">All Promo Vouchers</option>
                    </select>
                    <input type="text" class="rfq-input" id="spUsageSearchInput" oninput="renderUsageTab()" placeholder="Search SO #, customer..." style="width:220px;height:32px;font-size:12px;">
                </div>
                <div style="font-size:12px;font-weight:700;color:#475569;" id="spUsageTotalsSummary"></div>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Voucher Code</th>
                            <th>Sales Order #</th>
                            <th>Customer</th>
                            <th style="text-align:right;">Discount Applied</th>
                            <th>Redemption Status</th>
                            <th>Redeemed At</th>
                            <th>Released At</th>
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
                        <option value="REDEEMED">Redeemed</option>
                        <option value="RELEASED">Released</option>
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
                            <th>Voucher Code &amp; Name</th>
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
            <h3 class="bom-drawer-title" id="spDrawerTitle"><i class="ph ph-ticket"></i> Promo Voucher</h3>
            <p class="bom-drawer-subtitle">Configure voucher code reward, redemption quotas &amp; promo duration</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closePromoDrawer()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div class="sp-meta-banner" id="spDrawerMetaBanner" style="display:none;"></div>
        <input type="hidden" id="spVchId">

        <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;">
            <div class="rfq-field"><label class="rfq-label">Voucher Code *</label><input type="text" class="rfq-input" id="spVchCode" placeholder="WELCOME500" style="text-transform:uppercase;"></div>
            <div class="rfq-field"><label class="rfq-label">Voucher Name *</label><input type="text" class="rfq-input" id="spVchName" placeholder="Welcome Catering Rebate"></div>
            <div class="rfq-field"><label class="rfq-label">Discount Type</label><select class="rfq-select" id="spVchType"><option value="FIXED">Fixed Amount (₱)</option><option value="PERCENT">Percent (%)</option></select></div>
            <div class="rfq-field"><label class="rfq-label">Value *</label><input type="number" step="0.01" min="0.01" class="rfq-input" id="spVchVal"></div>
            <div class="rfq-field"><label class="rfq-label">Min Spend (₱)</label><input type="number" step="0.01" min="0" class="rfq-input" id="spVchMinSpend" value="0"></div>
            <div class="rfq-field"><label class="rfq-label">Global Max Uses</label><input type="number" min="1" class="rfq-input" id="spVchMaxTotal" placeholder="Unlimited"></div>
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
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748b;">Per-Customer Limits, Targeting &amp; Notes</summary>
            <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:10px;margin-top:8px;">
                <div class="rfq-field"><label class="rfq-label">Max Uses / Customer</label><input type="number" min="1" class="rfq-input" id="spVchMaxCust" value="1"></div>
                <div class="rfq-field"><label class="rfq-label">Max Discount Cap (₱)</label><input type="number" step="0.01" min="0" class="rfq-input" id="spVchMaxDisc" placeholder="Optional"></div>
                <div class="rfq-field" style="grid-column:span 2;">
                    <label class="rfq-label">Restrict to Customer (Optional)</label>
                    <select class="rfq-select" id="spVchCustomer">
                        <option value="">-- Open to All Customers --</option>
                        @foreach($initialCustomers ?? [] as $c)
                            <option value="{{ $c->customer_id }}">{{ $c->customer_code }} — {{ $c->customer_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rfq-field" style="grid-column:span 2;"><label class="rfq-label">Campaign Notes</label><input type="text" class="rfq-input" id="spVchNotes"></div>
            </div>
        </details>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closePromoDrawer()">Cancel</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="saveVoucherRule()"><i class="ph ph-check"></i> Save Voucher</button>
    </div>
</aside>
@endsection

@extends('layouts.app')

@section('title', 'Bundle Promotions - Sales Promotions')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@push('blade-data')
<script>
    window.SalesPromoConfig = Object.freeze({
        mode: 'bundles',
        routes: {
            saveBundle: @json(route('sales.api.bundles.save')),
            toggleBundle: @json(url('/sales/api/bundles/__ID__/toggle')),
            telemetry: @json(route('sales.api.promotions.telemetry', ['module' => 'bundles'])),
            salesOrderUrl: @json(route('sales.orders')),
        },
        initialBundles: @json($initialBundles ?? []),
        initialItems: @json($initialItems ?? []),
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
                <h1 class="hr-parent-title"><i class="ph ph-gift"></i><span>Bundle Promotions</span></h1>
                <p class="hr-parent-subtitle">Sellable SKU bundles with real-time Inventory ATP bottleneck, duration windows, usage per module &amp; audit trail.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}" class="hr-btn hr-btn-secondary"><i class="ph ph-arrow-left"></i><span>Back to Orders</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="openBundleDrawer()"><i class="ph ph-plus-circle"></i><span>New Bundle Promo</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(4, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Configured Bundles</span><div class="rfq-kpi-icon"><i class="ph ph-gift"></i></div></div><div class="rfq-kpi-val" id="kpiBndTotal">0</div><div class="rfq-kpi-sub">Active: <span id="kpiBndActive">0</span></div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Component Shortage</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-warning"></i></div></div><div class="rfq-kpi-val" id="kpiBndLowAtp">0</div><div class="rfq-kpi-sub">0 ATP bottlenecks</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Scheduled / Expired</span><div class="rfq-kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-calendar-check"></i></div></div><div class="rfq-kpi-val" id="kpiBndScheduled">0</div><div class="rfq-kpi-sub">Time-limited promos</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Confirmed Orders Used</span><div class="rfq-kpi-icon awarded"><i class="ph ph-chart-bar"></i></div></div><div class="rfq-kpi-val" id="kpiBndUsesTotal">0</div><div class="rfq-kpi-sub">Revenue: <span id="kpiBndImpactTotal">₱0.00</span></div></div>
    </div>

    <nav class="rfq-nav-tabs-bar" id="spTabsBar">
        <button type="button" class="rfq-tab-btn active" id="spTabBtnCatalog" onclick="switchSpTab('tab-catalog')">
            <i class="ph ph-gift"></i><span>Bundle Promos</span><span class="rfq-tab-count" id="spTabCatalogCount">0</span>
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
                <input type="text" class="rfq-input" oninput="filterSpSearch(this.value)" placeholder="Search bundle code, name, author..." style="max-width:320px;height:32px;font-size:12px;">
                <button type="button" class="rfq-btn rfq-btn-primary" onclick="openBundleDrawer()"><i class="ph ph-plus"></i> New Bundle Promo</button>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Bundle Code</th>
                            <th>Package &amp; Components</th>
                            <th>Price / Savings</th>
                            <th>Margin &amp; Cost</th>
                            <th>Duration &amp; Schedule</th>
                            <th>Usage &amp; ATP</th>
                            <th>Created / Updated By</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="spBundleTbody"></tbody>
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
                        <option value="ALL">All Bundle Promos</option>
                    </select>
                    <input type="text" class="rfq-input" id="spUsageSearchInput" oninput="renderUsageTab()" placeholder="Search SO #, customer..." style="width:220px;height:32px;font-size:12px;">
                </div>
                <div style="font-size:12px;font-weight:700;color:#475569;" id="spUsageTotalsSummary"></div>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Bundle Code</th>
                            <th>Sales Order #</th>
                            <th>Customer</th>
                            <th style="text-align:right;">Components</th>
                            <th style="text-align:right;">Bundle Revenue</th>
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
                        <option value="APPLIED_TO_ORDER">Sold on Order</option>
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
                            <th>Bundle Code &amp; Name</th>
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
<aside class="bom-drawer-container" id="spPromoDrawer" role="dialog" aria-modal="true" aria-labelledby="spDrawerTitle" style="--bom-drawer-width: 720px;">
    <div class="bom-drawer-header">
        <div>
            <h3 class="bom-drawer-title" id="spDrawerTitle"><i class="ph ph-gift"></i> Bundle Promo Builder</h3>
            <p class="bom-drawer-subtitle">Package SKUs with component UOMs &amp; optional promo schedule window</p>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closePromoDrawer()" aria-label="Close drawer">&times;</button>
    </div>
    <div class="bom-drawer-body">
        <div class="sp-meta-banner" id="spDrawerMetaBanner" style="display:none;"></div>
        <input type="hidden" id="spBndId">

        <div style="display:grid;grid-template-columns:repeat(4, minmax(0, 1fr));gap:10px;">
            <div class="rfq-field"><label class="rfq-label">Bundle Code *</label><input type="text" class="rfq-input" id="spBndCode" placeholder="BND-FEAST" style="text-transform:uppercase;"></div>
            <div class="rfq-field" style="grid-column:span 2;"><label class="rfq-label">Bundle Name *</label><input type="text" class="rfq-input" id="spBndName" placeholder="Party Tray Combo"></div>
            <div class="rfq-field"><label class="rfq-label">Promo Price (₱) *</label><input type="number" step="0.01" min="0.01" class="rfq-input" id="spBndPrice"></div>
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
            <summary style="cursor:pointer;font-size:12px;font-weight:700;color:#64748b;">Tax Code &amp; Description</summary>
            <div style="display:grid;grid-template-columns:repeat(3, minmax(0, 1fr));gap:10px;margin-top:8px;">
                <div class="rfq-field"><label class="rfq-label">Tax Code</label><select class="rfq-select" id="spBndTax"><option value="VAT12_OUT">12% Output VAT</option><option value="VAT_ZERO">0% Zero-Rated</option><option value="VAT_EXEMPT">VAT-Exempt</option></select></div>
                <div class="rfq-field" style="grid-column:span 2;"><label class="rfq-label">Description</label><input type="text" class="rfq-input" id="spBndDesc"></div>
            </div>
        </details>

        <div style="display:flex;align-items:center;justify-content:space-between;margin-top:4px;">
            <strong style="font-size:0.85rem;">Component SKUs (Pro-Rated Upon Order Explosion)</strong>
            <button type="button" class="rfq-btn rfq-btn-teal" style="padding:4px 10px;font-size:11.5px;" onclick="addBundleCompRow()"><i class="ph ph-plus"></i> Add Component</button>
        </div>
        <div class="rfq-table-responsive" style="max-height:260px;">
            <table class="rfq-table">
                <thead><tr><th>Component Item SKU</th><th style="width:100px;">Qty / Bundle</th><th style="width:150px;">UOM</th><th style="width:45px;"></th></tr></thead>
                <tbody id="spBundleLinesTbody"></tbody>
            </table>
        </div>
    </div>
    <div class="bom-drawer-footer">
        <button type="button" class="rfq-btn rfq-btn-outline" onclick="closePromoDrawer()">Cancel</button>
        <button type="button" class="rfq-btn rfq-btn-primary" onclick="saveBundleRule()"><i class="ph ph-check"></i> Save Bundle</button>
    </div>
</aside>
@endsection

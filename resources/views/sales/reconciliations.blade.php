@extends('layouts.app')

@section('title', '3-Way O2C Reconciliations - Sales Control')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@section('content')
@php $kpis = $report['kpis'] ?? []; @endphp
<div class="rfq-workspace-container">
    <div class="rfq-toast-container" id="reconToastContainer"></div>

    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title"><i class="ph ph-arrows-clockwise"></i><span>3-Way Order-to-Cash Reconciliations</span></h1>
                <p class="hr-parent-subtitle">Real-time control tower across Sales Orders ↔ Inventory Deliveries (Stock-Out) ↔ AR Customer Bills &amp; Collections.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}" class="hr-btn hr-btn-secondary"><i class="ph ph-clipboard-text"></i><span>Sales Workspace</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="location.reload()"><i class="ph ph-arrows-clockwise"></i><span>Refresh Audit</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(5, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Orders Audited</span><div class="rfq-kpi-icon"><i class="ph ph-files"></i></div></div><div class="rfq-kpi-val">{{ $kpis['total_orders_audited'] ?? 0 }}</div><div class="rfq-kpi-sub">Active &amp; Closed SOs</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Approved Unshipped</span><div class="rfq-kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-truck"></i></div></div><div class="rfq-kpi-val">{{ $kpis['approved_unshipped_count'] ?? 0 }}</div><div class="rfq-kpi-sub">Pending Inventory DO</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Delivered Unbilled</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-warning"></i></div></div><div class="rfq-kpi-val">{{ $kpis['delivered_unbilled_count'] ?? 0 }}</div><div class="rfq-kpi-sub">Revenue leakage queue</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Open Unpaid AR</span><div class="rfq-kpi-icon" style="background:#f5f3ff;color:#6d28d9;"><i class="ph ph-currency-circle-dollar"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_uncollected_ar'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">{{ $kpis['billed_unpaid_count'] ?? 0 }} open SO balances</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Ledger Variances</span><div class="rfq-kpi-icon" style="background:#fef2f2;color:#b91c1c;"><i class="ph ph-shield-warning"></i></div></div><div class="rfq-kpi-val">{{ $kpis['variance_count'] ?? 0 }}</div><div class="rfq-kpi-sub">SO vs AR drift alerts</div></div>
    </div>

    <!-- 1. Delivered But Unbilled (Revenue Leakage Guard with 1-Click Post Draft Bill) -->
    <div class="rfq-panel-card" style="margin-bottom:14px;">
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;color:#92400e;">
            <i class="ph ph-receipt"></i> Delivered-But-Unbilled Queue (1-Click Post Draft AR Bill)
        </div>
        <div class="rfq-table-responsive">
            <table class="rfq-table">
                <thead><tr><th>Order #</th><th>Customer</th><th style="text-align:right;">Delivered</th><th style="text-align:right;">Invoiced</th><th>Draft AR Bill</th><th style="text-align:right;">Draft Amt</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>
                    @forelse($report['delivered_unbilled'] ?? [] as $row)
                        <tr>
                            <td><a href="{{ route('sales.orders', ['order_id' => $row['sales_order_id']]) }}" style="font-family:monospace;font-weight:700;color:#7c3aed;">{{ $row['order_number'] }}</a></td>
                            <td><strong>{{ $row['customer_name'] }}</strong></td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ number_format($row['delivered_qty'], 2) }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">{{ number_format($row['invoiced_qty'], 2) }}</td>
                            <td>@if($row['draft_invoice_number'])<span class="so-linked-chip is-bill">{{ $row['draft_invoice_number'] }}</span>@else<span style="color:#64748b;">Ready to Bill</span>@endif</td>
                            <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">₱{{ number_format($row['draft_amount'], 2) }}</td>
                            <td style="text-align:right;">
                                <button type="button" class="rfq-btn rfq-btn-primary" style="padding:4px 10px;font-size:11px;" onclick="reconPostBill('{{ $row['sales_order_id'] }}', '{{ $row['draft_invoice_id'] ?? '' }}')">Post Bill Now</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" style="text-align:center;padding:22px;color:#047857;">All delivered orders have been billed cleanly.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. Ledger / Quantity Variances + 1-Click Re-Sync -->
    <div class="rfq-panel-card" style="margin-bottom:14px;">
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;color:#b91c1c;">
            <i class="ph ph-scales"></i> SO vs AR Ledger Drift &amp; Variance Audit
        </div>
        <div class="rfq-table-responsive">
            <table class="rfq-table">
                <thead><tr><th>Order #</th><th>Customer</th><th>Detected Variance</th><th style="text-align:right;">Action</th></tr></thead>
                <tbody>
                    @forelse($report['variance_audit'] ?? [] as $v)
                        <tr>
                            <td style="font-family:monospace;font-weight:700;">{{ $v['order_number'] }}</td>
                            <td>{{ $v['customer_name'] }}</td>
                            <td style="color:#b91c1c;font-weight:600;">{{ $v['reasons'] }}</td>
                            <td style="text-align:right;"><button type="button" class="rfq-btn rfq-btn-outline" style="padding:4px 10px;font-size:11px;" onclick="reconResync('{{ $v['sales_order_id'] }}')">Re-Sync Ledger</button></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" style="text-align:center;padding:22px;color:#047857;">Zero Order-to-Cash variances detected.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- 3. Approved Unshipped & Billed Unpaid Split -->
    <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:14px;">
        <div class="rfq-panel-card">
            <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Approved / Unshipped Backlog</div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead><tr><th>Order #</th><th>Customer</th><th style="text-align:right;">Remaining Qty</th><th>Status</th></tr></thead>
                    <tbody>
                        @forelse($report['approved_unshipped'] ?? [] as $au)
                            <tr><td><a href="{{ route('sales.orders', ['order_id' => $au['sales_order_id']]) }}" style="font-family:monospace;font-weight:700;">{{ $au['order_number'] }}</a></td><td>{{ $au['customer_name'] }}</td><td style="text-align:right;">{{ number_format($au['remaining_qty'], 2) }}</td><td><span class="status-pill approved">{{ $au['status'] }}</span></td></tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8;">No unshipped approved backlog.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-panel-card">
            <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Billed / Unpaid Open AR</div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead><tr><th>Order #</th><th>Customer</th><th style="text-align:right;">Billed</th><th style="text-align:right;">Balance Due</th></tr></thead>
                    <tbody>
                        @forelse($report['billed_unpaid'] ?? [] as $bu)
                            <tr><td><a href="{{ route('sales.orders', ['order_id' => $bu['sales_order_id']]) }}" style="font-family:monospace;font-weight:700;">{{ $bu['order_number'] }}</a></td><td>{{ $bu['customer_name'] }}</td><td style="text-align:right;">₱{{ number_format($bu['amount_billed'], 2) }}</td><td style="text-align:right;font-weight:700;color:#b45309;">₱{{ number_format($bu['balance_due'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;padding:20px;color:#94a3b8;">No open AR balances on Sales Orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    async function reconCall(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body || {}),
        });
        const json = await res.json();
        if (!res.ok || !json.success) { alert(json.message || 'Operation failed'); return; }
        location.reload();
    }
    window.reconPostBill = (salesOrderId, invoiceId) => {
        const url = @json(url('/sales/api/orders/__ID__/create-bill')).replace('__ID__', encodeURIComponent(salesOrderId));
        reconCall(url, { invoice_id: invoiceId || null, target_status: 'POSTED' });
    };
    window.reconResync = (salesOrderId) => {
        const url = @json(url('/sales/api/reconciliations/__ID__/resync')).replace('__ID__', encodeURIComponent(salesOrderId));
        reconCall(url, {});
    };
})();
</script>
@endsection

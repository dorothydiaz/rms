@extends('layouts.app')

@section('title', 'Payment & Collection Report - Sales Analytics')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@section('content')
@php
    $kpis = $report['kpis'] ?? [];
    $filters = $report['filters'] ?? [];
@endphp
<div class="rfq-workspace-container">
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title"><i class="ph ph-credit-card"></i><span>Payment &amp; Collection Report</span></h1>
                <p class="hr-parent-subtitle">Live AR Collection Receipts, BIR Form 2307 Creditable Withholding Tax (CWT), PDC checks, DAIF alerts &amp; Credit Memos.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('accounting.ar', ['tab' => 'receipts']) }}" class="hr-btn hr-btn-secondary"><i class="ph ph-currency-circle-dollar"></i><span>Record Receipt (AR)</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="window.print()"><i class="ph ph-printer"></i><span>Print Report</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-panel-card" style="padding:12px 16px;margin-bottom:14px;">
        <form method="GET" action="{{ route('sales.payment-report') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            <div class="rfq-field"><label class="rfq-label">From Date</label><input type="date" name="from" class="rfq-input" value="{{ $filters['from'] ?? now()->startOfMonth()->toDateString() }}"></div>
            <div class="rfq-field"><label class="rfq-label">To Date</label><input type="date" name="to" class="rfq-input" value="{{ $filters['to'] ?? now()->toDateString() }}"></div>
            <div class="rfq-field">
                <label class="rfq-label">Payment Method</label>
                <select name="method" class="rfq-select">
                    <option value="">All Methods</option>
                    <option value="CASH" {{ ($filters['method'] ?? '') === 'CASH' ? 'selected' : '' }}>CASH</option>
                    <option value="BANK_TRANSFER" {{ ($filters['method'] ?? '') === 'BANK_TRANSFER' ? 'selected' : '' }}>BANK TRANSFER / INSTAPAY</option>
                    <option value="CHECK" {{ ($filters['method'] ?? '') === 'CHECK' ? 'selected' : '' }}>CHECK / PDC</option>
                    <option value="CARD" {{ ($filters['method'] ?? '') === 'CARD' ? 'selected' : '' }}>CARD</option>
                </select>
            </div>
            <button type="submit" class="rfq-btn rfq-btn-primary" style="height:36px;"><i class="ph ph-funnel"></i> Filter Collections</button>
        </form>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(5, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Net Cash Collected</span><div class="rfq-kpi-icon awarded"><i class="ph ph-money"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_net_cash'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">{{ $kpis['receipt_count'] ?? 0 }} Official Receipts</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Gross AR Settled</span><div class="rfq-kpi-icon"><i class="ph ph-check-square"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_gross_collected'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Total receivable cleared</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">BIR Form 2307 (CWT)</span><div class="rfq-kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-file-text"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_cwt_2307'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Creditable withholding tax</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Pending PDC Checks</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-clock"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['pending_pdc_amount'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Received / Deposited checks</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">DAIF / Credit Memos</span><div class="rfq-kpi-icon" style="background:#fef2f2;color:#b91c1c;"><i class="ph ph-warning-octagon"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['daif_bounced_amount'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Credit Memos: ₱{{ number_format($kpis['credit_memos_issued'] ?? 0, 2) }}</div></div>
    </div>

    <div class="rfq-panel-card" style="margin-bottom:14px;">
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Collections by Payment Method</div>
        <div class="rfq-table-responsive">
            <table class="rfq-table">
                <thead><tr><th>Method</th><th style="text-align:right;">Receipts</th><th style="text-align:right;">Gross AR Settled</th><th style="text-align:right;">CWT (2307)</th><th style="text-align:right;">Net Cash Collected</th></tr></thead>
                <tbody>
                    @forelse($report['by_method'] ?? [] as $bm)
                        <tr>
                            <td><span class="rfq-label-badge">{{ $bm['method'] }}</span></td>
                            <td style="text-align:right;">{{ $bm['count'] }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($bm['gross_amount'], 2) }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($bm['cwt_amount'], 2) }}</td>
                            <td style="text-align:right;font-weight:700;color:#047857;font-variant-numeric:tabular-nums;">₱{{ number_format($bm['net_amount'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" style="text-align:center;padding:24px;color:#94a3b8;">No collection receipts in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="rfq-panel-card">
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Official Collection Receipts &amp; Applied Bills</div>
        <div class="rfq-table-responsive">
            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Method &amp; Ref</th>
                        <th>Invoices / Sales Orders</th>
                        <th style="text-align:right;">CWT</th>
                        <th style="text-align:right;">Net Cash</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['receipts'] ?? [] as $rcp)
                        <tr>
                            <td style="font-family:monospace;font-weight:700;color:#7c3aed;">{{ $rcp['receipt_number'] }}</td>
                            <td style="font-family:monospace;">{{ $rcp['receipt_date'] }}</td>
                            <td><strong>{{ $rcp['customer_name'] }}</strong></td>
                            <td>{{ $rcp['payment_method'] }} <div style="font-size:11px;color:#64748b;">Ref: {{ $rcp['reference_number'] }}</div></td>
                            <td>
                                @foreach($rcp['invoice_numbers'] as $invNum)<span class="so-linked-chip is-bill">{{ $invNum }}</span>@endforeach
                                @foreach($rcp['sales_order_numbers'] as $soNum)<span class="so-linked-chip">{{ $soNum }}</span>@endforeach
                            </td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($rcp['cwt_amount'], 2) }}</td>
                            <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">₱{{ number_format($rcp['net_amount'], 2) }}</td>
                            <td><span class="status-pill approved">{{ $rcp['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:26px;color:#94a3b8;">No receipts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

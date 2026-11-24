@extends('layouts.app')

@section('title', 'Daily Sales Report - Sales Analytics')

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
                <h1 class="hr-parent-title"><i class="ph ph-chart-line-up"></i><span>Daily Sales &amp; Gross Margin Report</span></h1>
                <p class="hr-parent-subtitle">Live posted AR Sales Invoices reconciled with perpetual Stock-Out COGS, HR Salespersons &amp; Branches.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.orders') }}" class="hr-btn hr-btn-secondary"><i class="ph ph-arrow-left"></i><span>Quotes &amp; Orders</span></a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="window.print()"><i class="ph ph-printer"></i><span>Print Report</span></button>
            </div>
        </div>
    </div>

    <div class="rfq-panel-card" style="padding:12px 16px;margin-bottom:14px;">
        <form method="GET" action="{{ route('sales.daily-sales') }}" style="display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap;">
            <div class="rfq-field"><label class="rfq-label">From Date</label><input type="date" name="from" class="rfq-input" value="{{ $filters['from'] ?? now()->startOfMonth()->toDateString() }}"></div>
            <div class="rfq-field"><label class="rfq-label">To Date</label><input type="date" name="to" class="rfq-input" value="{{ $filters['to'] ?? now()->toDateString() }}"></div>
            <div class="rfq-field">
                <label class="rfq-label">Branch</label>
                <select name="branch_id" class="rfq-select">
                    <option value="">All Branches</option>
                    @foreach($branches ?? [] as $br)
                        <option value="{{ $br->branch_id }}" {{ ($filters['branch_id'] ?? '') == $br->branch_id ? 'selected' : '' }}>{{ $br->branch_code }} — {{ $br->branch_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="rfq-field">
                <label class="rfq-label">Salesperson (HR)</label>
                <select name="salesperson" class="rfq-select">
                    <option value="">All Representatives</option>
                    @foreach($employees ?? [] as $emp)
                        <option value="{{ $emp['full_name'] }}" {{ ($filters['salesperson'] ?? '') === $emp['full_name'] ? 'selected' : '' }}>{{ $emp['full_name'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rfq-btn rfq-btn-primary" style="height:36px;"><i class="ph ph-funnel"></i> Apply Filter</button>
        </form>
    </div>

    <div class="rfq-kpi-grid" style="grid-template-columns:repeat(5, minmax(0, 1fr));margin-bottom:14px;">
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Net Sales (Ex-VAT)</span><div class="rfq-kpi-icon"><i class="ph ph-trend-up"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['net_sales'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Discounts: ₱{{ number_format($kpis['total_discounts'] ?? 0, 2) }}</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Total Billed (Incl VAT)</span><div class="rfq-kpi-icon" style="background:#eff6ff;color:#2563eb;"><i class="ph ph-receipt"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_billed'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">{{ $kpis['invoice_count'] ?? 0 }} Posted Invoices</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Cost of Goods (COGS)</span><div class="rfq-kpi-icon awaiting"><i class="ph ph-package"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['total_cogs'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Perpetual Stock-Out value</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Gross Profit</span><div class="rfq-kpi-icon awarded"><i class="ph ph-coins"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['gross_profit'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Margin: {{ number_format($kpis['gross_margin_pct'] ?? 0, 1) }}%</div></div>
        <div class="rfq-kpi-card"><div class="rfq-kpi-header"><span class="rfq-kpi-label">Output VAT &amp; Avg Ticket</span><div class="rfq-kpi-icon"><i class="ph ph-scales"></i></div></div><div class="rfq-kpi-val" style="font-variant-numeric:tabular-nums;">₱{{ number_format($kpis['output_vat'] ?? 0, 2) }}</div><div class="rfq-kpi-sub">Avg Ticket: ₱{{ number_format($kpis['avg_ticket'] ?? 0, 2) }}</div></div>
    </div>

    <div class="rfq-panel-card" style="margin-bottom:14px;">
        <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Daily Revenue &amp; Margin Breakdown</div>
        <div class="rfq-table-responsive">
            <table class="rfq-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th style="text-align:right;">Invoices</th>
                        <th style="text-align:right;">Gross Sales</th>
                        <th style="text-align:right;">Discounts</th>
                        <th style="text-align:right;">Net Sales</th>
                        <th style="text-align:right;">Output VAT</th>
                        <th style="text-align:right;">COGS</th>
                        <th style="text-align:right;">Gross Profit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['daily_rows'] ?? [] as $row)
                        <tr>
                            <td style="font-family:monospace;font-weight:700;">{{ $row['date'] }}</td>
                            <td style="text-align:right;">{{ $row['invoices_count'] }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($row['gross_sales'], 2) }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;color:#b45309;">(₱{{ number_format($row['discounts'], 2) }})</td>
                            <td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">₱{{ number_format($row['net_sales'], 2) }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($row['output_vat'], 2) }}</td>
                            <td style="text-align:right;font-variant-numeric:tabular-nums;">₱{{ number_format($row['cogs'], 2) }}</td>
                            <td style="text-align:right;font-weight:700;color:#047857;font-variant-numeric:tabular-nums;">₱{{ number_format($row['gross_profit'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" style="text-align:center;padding:26px;color:#94a3b8;">No posted AR invoices in this date range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:14px;">
        <div class="rfq-panel-card">
            <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Top Customers</div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead><tr><th>Customer</th><th style="text-align:right;">Invoices</th><th style="text-align:right;">Net Sales</th></tr></thead>
                    <tbody>
                        @forelse($report['top_customers'] ?? [] as $tc)
                            <tr><td><strong>{{ $tc['customer_name'] }}</strong></td><td style="text-align:right;">{{ $tc['invoice_count'] }}</td><td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">₱{{ number_format($tc['net_sales'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:20px;color:#94a3b8;">No customer sales yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-panel-card">
            <div style="padding:12px 16px;border-bottom:1px solid #e2e8f0;font-weight:800;font-size:0.9rem;">Top SKUs Sold</div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead><tr><th>SKU / Item</th><th style="text-align:right;">Qty</th><th style="text-align:right;">Net Sales</th></tr></thead>
                    <tbody>
                        @forelse($report['top_skus'] ?? [] as $ts)
                            <tr><td><span style="font-family:monospace;color:#7c3aed;">{{ $ts['item_code'] }}</span> {{ $ts['description'] }}</td><td style="text-align:right;">{{ number_format($ts['qty_sold'], 2) }}</td><td style="text-align:right;font-weight:700;font-variant-numeric:tabular-nums;">₱{{ number_format($ts['net_sales'], 2) }}</td></tr>
                        @empty
                            <tr><td colspan="3" style="text-align:center;padding:20px;color:#94a3b8;">No SKU movement yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

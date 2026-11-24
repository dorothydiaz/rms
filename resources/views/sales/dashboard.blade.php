@extends('layouts.app')

@section('title', 'Sales Dashboard - Restaurant Management System')

@push('styles')
    @vite('resources/css/modules/sales-orders.css')
@endpush

@section('content')
<div class="rfq-workspace-container">
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-chart-line-up"></i>
                    <span>Sales &amp; Order-to-Cash Dashboard</span>
                </h1>
                <p class="hr-parent-subtitle">Live pipeline visibility from Sales Quotes, Accounting Credit Review, Inventory Delivery, and AR Customer Billing.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('sales.customer-masterlist') }}" class="hr-btn hr-btn-secondary">
                    <i class="ph ph-address-book"></i>
                    <span>Customers</span>
                </a>
                <a href="{{ route('sales.quotes') }}" class="hr-btn hr-btn-secondary">
                    <i class="ph ph-file-text"></i>
                    <span>New Sales Quote</span>
                </a>
                <a href="{{ route('sales.orders') }}?new=1" class="hr-btn hr-btn-primary">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Sales Order</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 5-Card O2C Funnel KPI Strip -->
    <div class="rfq-kpi-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom: 14px;">
        <a href="{{ route('sales.quotes') }}" class="rfq-kpi-card" style="text-decoration:none;color:inherit;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Active Quotes</span>
                <div class="rfq-kpi-icon"><i class="ph ph-file-text"></i></div>
            </div>
            <div class="rfq-kpi-val">{{ (int) ($kpis['activeQuotes'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">{{ (int) ($kpis['confirmedQuotes'] ?? 0) }} confirmed &amp; ready to convert</div>
        </a>

        <a href="{{ route('accounting.ar', ['tab' => 'credit-review']) }}" class="rfq-kpi-card" style="text-decoration:none;color:inherit;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Pending Credit Review</span>
                <div class="rfq-kpi-icon awaiting"><i class="ph ph-shield-warning"></i></div>
            </div>
            <div class="rfq-kpi-val" style="color:{{ ($kpis['creditHoldCount'] ?? 0) > 0 ? '#b45309' : 'inherit' }};">{{ (int) ($kpis['creditHoldCount'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Awaiting Accounting AR clearance</div>
        </a>

        <a href="{{ route('sales.orders') }}" class="rfq-kpi-card" style="text-decoration:none;color:inherit;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Ready to Deliver</span>
                <div class="rfq-kpi-icon awarded"><i class="ph ph-truck"></i></div>
            </div>
            <div class="rfq-kpi-val">{{ (int) ($kpis['readyToDeliverCount'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Approved orders with ATP reserved</div>
        </a>

        <a href="{{ route('accounting.ar', ['tab' => 'invoices']) }}" class="rfq-kpi-card" style="text-decoration:none;color:inherit;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">Unbilled Draft Bills</span>
                <div class="rfq-kpi-icon draft"><i class="ph ph-receipt"></i></div>
            </div>
            <div class="rfq-kpi-val">{{ (int) ($kpis['unbilledDraftCount'] ?? 0) }}</div>
            <div class="rfq-kpi-sub">Delivered orders awaiting AR posting</div>
        </a>

        <a href="{{ route('accounting.ar', ['tab' => 'ledger']) }}" class="rfq-kpi-card" style="text-decoration:none;color:inherit;">
            <div class="rfq-kpi-header">
                <span class="rfq-kpi-label">MTD Net Revenue</span>
                <div class="rfq-kpi-icon awarded"><i class="ph ph-currency-circle-dollar"></i></div>
            </div>
            <div class="rfq-kpi-val" style="font-size:1.35rem;">₱{{ number_format((float) ($kpis['mtdRevenue'] ?? 0), 2) }}</div>
            <div class="rfq-kpi-sub">Open AR: ₱{{ number_format((float) ($kpis['openReceivables'] ?? 0), 2) }}</div>
        </a>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2, minmax(0, 1fr));gap:14px;">
        <!-- Credit Hold Queue Snapshot -->
        <div class="rfq-panel-card" style="padding:14px 16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h3 style="margin:0;font-size:0.95rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:6px;">
                    <i class="ph ph-shield-check" style="color:#d97706;"></i> Accounting Credit Review Queue
                </h3>
                <a href="{{ route('accounting.ar', ['tab' => 'credit-review']) }}" class="rfq-btn rfq-btn-outline" style="padding:4px 10px;font-size:11.5px;">Open AR Queue</a>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Terms</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($creditHoldOrders as $co)
                            <tr>
                                <td style="font-family:monospace;font-weight:700;color:#7c3aed;">{{ $co->order_number }}</td>
                                <td>{{ $co->customer_name }}</td>
                                <td><span class="status-pill credit_hold">{{ $co->payment_terms_code }}</span></td>
                                <td style="text-align:right;font-weight:700;">₱{{ number_format((float) $co->gross_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:18px;">No orders waiting on credit review.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ready for Delivery & Draft Bills -->
        <div class="rfq-panel-card" style="padding:14px 16px;">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                <h3 style="margin:0;font-size:0.95rem;font-weight:700;color:#0f172a;display:flex;align-items:center;gap:6px;">
                    <i class="ph ph-truck" style="color:#2563eb;"></i> Ready for Delivery &amp; Billing
                </h3>
                <a href="{{ route('sales.orders') }}" class="rfq-btn rfq-btn-outline" style="padding:4px 10px;font-size:11.5px;">Manage Orders</a>
            </div>
            <div class="rfq-table-responsive">
                <table class="rfq-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th style="text-align:right;">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($readyOrders as $ro)
                            <tr>
                                <td style="font-family:monospace;font-weight:700;color:#2563eb;">{{ $ro->order_number }}</td>
                                <td>{{ $ro->customer_name }}</td>
                                <td><span class="status-pill {{ strtolower($ro->status) }}">{{ $ro->status }}</span></td>
                                <td style="text-align:right;font-weight:700;">₱{{ number_format((float) $ro->gross_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:18px;">No approved orders waiting for delivery.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

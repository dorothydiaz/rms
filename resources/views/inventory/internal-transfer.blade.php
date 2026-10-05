@extends('layouts.app')

@section('title', 'Internal Transfer - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   INTERNAL TRANSFER MODULE - ENTERPRISE HR THEME TOKENS & WORKSPACE
   Adhering to docs/HR_THEME_DESIGN_SYSTEM.md & assets/css/theme-tokens.css
   ========================================================================== */
:root {
    --trf-canvas-bg: #faf7fd;
    --trf-surface-card: rgba(255, 255, 255, 0.96);
    --trf-surface-subtle: #f8fafc;
    --trf-surface-hover: rgba(245, 243, 255, 0.65);
    --trf-border: rgba(226, 232, 240, 0.9);
    --trf-border-focus: #c084fc;

    --trf-primary: #9333ea;
    --trf-primary-dark: #7c3aed;
    --trf-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --trf-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);
    --trf-primary-glow: rgba(168, 85, 247, 0.28);
    --trf-accent-strip: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);

    --trf-text-strong: #0f172a;
    --trf-text-medium: #334155;
    --trf-text-muted: #64748b;
    --trf-text-subtle: #94a3b8;

    --trf-shadow-card: 0 10px 32px rgba(148, 163, 184, 0.08), 0 2px 8px rgba(0, 0, 0, 0.02);
    --trf-shadow-card-hover: 0 14px 32px rgba(168, 85, 247, 0.14), 0 4px 10px rgba(236, 72, 153, 0.08);
}

.trf-workspace {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background-color: var(--trf-canvas-bg);
    min-height: calc(100vh - 72px);
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--trf-text-strong);
}

.trf-card-strip {
    position: relative;
    background: var(--trf-surface-card);
    border: 1px solid var(--trf-border);
    border-radius: 16px;
    box-shadow: var(--trf-shadow-card);
    overflow: hidden;
}

.trf-card-strip::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--trf-accent-strip);
}

/* Page Header */
.trf-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}

.trf-page-title-group h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--trf-text-strong);
    margin: 0 0 4px 0;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    gap: 10px;
}

.trf-page-subtitle {
    font-size: 0.875rem;
    color: var(--trf-text-muted);
    margin: 0;
}

/* Tabs Navigation */
.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(241, 245, 249, 0.85);
    padding: 6px;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    width: fit-content;
}

.tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    color: var(--trf-text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    white-space: nowrap;
}

.tab-btn:hover {
    color: var(--trf-primary);
    background: rgba(255, 255, 255, 0.6);
}

.tab-btn.active {
    background: #ffffff;
    color: var(--trf-primary);
    box-shadow: 0 4px 12px rgba(147, 51, 234, 0.12), 0 1px 3px rgba(0, 0, 0, 0.05);
}

.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 20px;
    padding: 0 6px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: #f1f5f9;
    color: var(--trf-text-muted);
}

.tab-btn.active .tab-count {
    background: #faf5ff;
    color: var(--trf-primary);
}

.tab-badge-pill {
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: #faf5ff;
    color: var(--trf-primary);
    border: 1px solid #f3e8ff;
}

/* KPI Summary Cards */
.trf-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.trf-kpi-card {
    background: #ffffff;
    border: 1px solid var(--trf-border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--trf-shadow-card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.trf-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--trf-shadow-card-hover);
}

.trf-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.trf-kpi-content {
    flex: 1;
}

.trf-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--trf-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.trf-kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--trf-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.trf-kpi-meta {
    font-size: 11.5px;
    color: var(--trf-text-muted);
    margin-top: 3px;
}

/* Toolbar & Filters */
.trf-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.trf-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 420px;
}

.trf-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--trf-text-subtle);
    font-size: 16px;
}

.trf-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 13px;
    color: var(--trf-text-strong);
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.trf-search-input:focus {
    border-color: var(--trf-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

.trf-select {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: var(--trf-text-strong);
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.trf-select:focus {
    border-color: var(--trf-border-focus);
}

/* Data Table */
.trf-table-wrapper {
    overflow-x: auto;
}

.trf-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 12.5px;
}

.trf-table th {
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.trf-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--trf-text-medium);
    vertical-align: middle;
}

.trf-table tr:hover td {
    background-color: var(--trf-surface-hover);
}

/* Badges */
.hr-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}

.hr-badge-purple { background: #faf5ff; color: #9333ea; border: 1px solid #f3e8ff; }
.hr-badge-green { background: #ecfdf5; color: #059669; border: 1px solid #d1fae5; }
.hr-badge-amber { background: #fffbeb; color: #d97706; border: 1px solid #fef3c7; }
.hr-badge-red { background: #fef2f2; color: #dc2626; border: 1px solid #fee2e2; }
.hr-badge-blue { background: #f0f9ff; color: #0284c7; border: 1px solid #e0f2fe; }
.hr-badge-neutral { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

/* Buttons */
.hr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 8px 16px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    border: none;
    text-decoration: none;
    white-space: nowrap;
}

.hr-btn-primary {
    background: var(--trf-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
}

.hr-btn-primary:hover {
    background: var(--trf-primary-gradient-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.4);
}

.hr-btn-secondary {
    background: #ffffff;
    color: var(--trf-text-medium);
    border: 1px solid #cbd5e1;
}

.hr-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: var(--trf-text-strong);
}

.hr-btn-sm {
    padding: 5px 10px;
    font-size: 11.5px;
    border-radius: 8px;
}

/* Asymmetric Workspace Grid */
.workspace-asymmetric-grid {
    display: grid;
    grid-template-columns: 4.2fr 5.8fr;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1080px) {
    .workspace-asymmetric-grid {
        grid-template-columns: 1fr;
    }
}

.panel-col {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.panel-card {
    background: #ffffff;
    border: 1px solid var(--trf-border);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: var(--trf-shadow-card);
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}

.panel-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--trf-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
}

.panel-title i {
    color: var(--trf-primary);
    font-size: 17px;
}

/* Quantity Validation Warning Box */
.qty-warning-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 8px 12px;
    color: #b45309;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Progress Fill */
.trf-progress-track {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
}

.trf-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s ease;
}

.trf-tab-panel {
    display: none;
    animation: fadeIn 0.2s ease-in-out;
}

.trf-tab-panel.active {
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Modal Overlay */
.rms-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

.rms-modal-overlay.open {
    display: flex;
}

.rms-modal-card {
    background: #ffffff;
    border-radius: 18px;
    max-width: 820px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    position: relative;
    border: 1px solid #e2e8f0;
}
</style>
@endpush

@section('content')
<div class="trf-workspace">

    <!-- =====================================================================
         PAGE HEADER
         ===================================================================== -->
    <div class="trf-page-header">
        <div class="trf-page-title-group">
            <h1>
                <i class="ph ph-arrows-left-right" style="color: var(--trf-primary);"></i>
                <span>Internal Transfer</span>
            </h1>
            <p class="trf-page-subtitle">
                Manage commissary-to-branch logistics, pending branch transfer requests & backlogs, and direct custom dispatches with real-time stock validation.
            </p>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Current Stock Levels">
                <i class="ph ph-squares-four"></i> Stocks Overview
            </a>
            <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master">
                <i class="ph ph-folder-simple"></i> Item Master
            </a>
            <button type="button" class="hr-btn hr-btn-primary" onclick="switchTrfTab('tab-custom-transfer')">
                <i class="ph ph-paper-plane-tilt"></i> New Custom Transfer
            </button>
        </div>
    </div>

    <!-- =====================================================================
         PRIMARY TABS NAVIGATION (Renamed & Styled Elegantly)
         1. Transfer Register & Masterlist (List)
         2. Pending Branch Requests & Backlog (Pending Transfer Request)
         3. Direct Push Transfer (Custom Transfer)
         ===================================================================== -->
    <nav class="tabs-wrapper" id="internalTransferTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnList" onclick="switchTrfTab('tab-transfer-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Transfer Register & Masterlist</span>
            <span class="tab-count" id="badgeListCount">{{ $initialTransfers->count() }}</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPendingRequests" onclick="switchTrfTab('tab-pending-requests')">
            <i class="ph ph-clock-countdown"></i>
            <span>Pending Branch Requests & Backlog</span>
            <span class="tab-badge-pill" id="badgePendingCount" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">
                {{ $initialTransfers->where('status', 'PENDING')->count() }} Backlog
            </span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCustomTransfer" onclick="switchTrfTab('tab-custom-transfer')">
            <i class="ph ph-paper-plane-tilt"></i>
            <span>Direct Push Transfer</span>
            <span class="tab-badge-pill">No Request Required</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: TRANSFER REGISTER & MASTERLIST (List)
         ===================================================================== -->
    <div id="tab-transfer-list" class="trf-tab-panel active">
        <!-- KPI Strip -->
        <div class="trf-kpi-grid">
            <div class="trf-kpi-card">
                <div class="trf-kpi-icon" style="background: #faf5ff; color: var(--trf-primary);">
                    <i class="ph ph-truck"></i>
                </div>
                <div class="trf-kpi-content">
                    <div class="trf-kpi-label">Total Transfers MTD</div>
                    <div class="trf-kpi-value" id="kpiTotalTransfers">{{ $initialTransfers->count() }}</div>
                    <div class="trf-kpi-meta">All internal routes</div>
                </div>
            </div>

            <div class="trf-kpi-card">
                <div class="trf-kpi-icon" style="background: #fffbeb; color: #d97706;">
                    <i class="ph ph-clock-countdown"></i>
                </div>
                <div class="trf-kpi-content">
                    <div class="trf-kpi-label">Pending / Backlogs</div>
                    <div class="trf-kpi-value" id="kpiPendingCount">{{ $initialTransfers->where('status', 'PENDING')->count() }}</div>
                    <div class="trf-kpi-meta">Awaiting dispatch</div>
                </div>
            </div>

            <div class="trf-kpi-card">
                <div class="trf-kpi-icon" style="background: #f0f9ff; color: #0284c7;">
                    <i class="ph ph-arrows-clockwise"></i>
                </div>
                <div class="trf-kpi-content">
                    <div class="trf-kpi-label">In-Transit Shipments</div>
                    <div class="trf-kpi-value" id="kpiInTransitCount">{{ $initialTransfers->where('status', 'IN_TRANSIT')->count() }}</div>
                    <div class="trf-kpi-meta">On delivery truck</div>
                </div>
            </div>

            <div class="trf-kpi-card">
                <div class="trf-kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="ph ph-coins"></i>
                </div>
                <div class="trf-kpi-content">
                    <div class="trf-kpi-label">Transferred Valuation</div>
                    <div class="trf-kpi-value" id="kpiValuation">₱{{ number_format($initialTransfers->whereIn('status', ['IN_TRANSIT', 'COMPLETED'])->sum('total_valuation'), 2) }}</div>
                    <div class="trf-kpi-meta">Dispatched value</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="trf-card-strip" style="margin-top: 16px;">
            <div class="trf-filter-bar">
                <div class="trf-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="transferSearchInput" class="trf-search-input" placeholder="Search by Transfer #, Destination, Carrier, or SKU... (/)" oninput="filterTransfersList()">
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="transferStatusFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Statuses</option>
                        <option value="PENDING">Pending Requisition</option>
                        <option value="IN_TRANSIT">In-Transit / Dispatched</option>
                        <option value="COMPLETED">Completed / Received</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <select id="transferTypeFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Transfer Types</option>
                        <option value="BRANCH_REQUEST">Branch Requisition</option>
                        <option value="CUSTOM_PUSH">Direct Push (No Request)</option>
                    </select>

                    <select id="transferBranchFilter" class="trf-select" onchange="filterTransfersList()">
                        <option value="ALL">All Destination Branches</option>
                        @foreach($initialBranches as $br)
                            <option value="{{ $br->name }}">{{ $br->name }}</option>
                        @endforeach
                    </select>

                    <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveTransferData()" title="Refresh Data">
                        <i class="ph ph-arrows-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Master Table -->
            <div class="trf-table-wrapper">
                <table class="trf-table" id="transfersMasterTable">
                    <thead>
                        <tr>
                            <th>Transfer #</th>
                            <th>Type</th>
                            <th>Origin / Source</th>
                            <th>Destination Branch</th>
                            <th>Items & Qty</th>
                            <th>Dispatched Units</th>
                            <th style="text-align: right;">Valuation (₱)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="transfersTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div id="noTransfersPrompt" style="display: none; padding: 40px; text-align: center; color: var(--trf-text-muted);">
                <i class="ph ph-arrows-left-right" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                No internal transfers match your search criteria.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: PENDING BRANCH REQUESTS & BACKLOG
         ===================================================================== -->
    <div id="tab-pending-requests" class="trf-tab-panel">
        <div class="trf-card-strip" style="padding: 20px;">
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-clock-countdown" style="font-size: 22px; color: #d97706;"></i>
                    <div>
                        <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;">Branch Requisitions & Backlog Queue</h3>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">Review items requested by retail branches, compare against central inventory, and fulfill dispatches.</p>
                    </div>
                </div>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fetchLiveTransferData()">
                    <i class="ph ph-arrows-clockwise"></i> Refresh Queue
                </button>
            </div>

            <div id="pendingRequestsContainer" style="display: flex; flex-direction: column; gap: 16px;">
                <!-- Populated dynamically via JS -->
            </div>

            <div id="noPendingPrompt" style="display: none; padding: 40px; text-align: center; color: var(--trf-text-muted);">
                <i class="ph ph-check-circle" style="font-size: 40px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                <strong>All branch requisitions are fulfilled!</strong> There are no pending transfer requests or backlogs in queue.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: CUSTOM TRANSFER (Direct Push Delivery - No Request Required)
         ===================================================================== -->
    <div id="tab-custom-transfer" class="trf-tab-panel">
        <div class="trf-card-strip" style="padding: 22px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--trf-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-paper-plane-tilt"></i> Direct Push Commissary Workspace
                    </span>
                    <span class="hr-badge hr-badge-neutral">Internal delivery without prior branch request</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetCustomTransferForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" id="btnDispatchCustomTransfer" onclick="submitCustomTransfer()">
                        <i class="ph ph-truck"></i> ✓ Dispatch Now & Deduct Stock (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.2fr): Logistics & Delivery Route -->
                <div class="panel-col">

                    <!-- Card 1: Route & Origin -->
                    <div class="panel-card" style="border-left: 4px solid var(--trf-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Transfer Code</div>
                                <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--trf-primary);">DIR-TRF-{{ date('Y') }}-AUTO</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Push</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Target Destination Branch *</label>
                            <select id="customDestBranch" class="trf-select" style="font-size: 13px; font-weight: 600;">
                                @foreach($initialBranches as $br)
                                    <option value="{{ $br->id }}" data-name="{{ $br->name }}">{{ $br->name }} ({{ $br->address }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Origin / Source Storage *</label>
                            <input type="text" id="customSourceLoc" class="trf-search-input" value="Central Commissary Main Storage" readonly style="background: #f8fafc; font-weight: 600;">
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Transfer Reason Code *</label>
                            <select id="customReasonCode" class="trf-select">
                                <option value="Commissary Bulk Batch Push">Central Commissary Scheduled Bulk Push</option>
                                <option value="Excess Stock Rebalancing">Excess Stock Rebalancing between Locations</option>
                                <option value="New Menu Seasonal Push">New Menu Seasonal Promo Ingredients Kit</option>
                                <option value="Emergency Kitchen Replenishment">Emergency Direct Replenishment</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Transport & Driver Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-steering-wheel"></i>
                                <span>Carrier, Vehicle & Custody</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Dispatcher / Supervisor *</label>
                                <input type="text" id="customDispatchedBy" class="trf-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Commissary Lead' }}">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Logistics Carrier / Vehicle</label>
                                <input type="text" id="customCarrierName" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. In-House Refrigerated Van 1">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Vehicle Plate #</label>
                                <input type="text" id="customDriverPlate" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. NDF 8820">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Shipping Waybill #</label>
                                <input type="text" id="customWaybill" class="trf-search-input" style="padding-left: 10px;" placeholder="e.g. WB-TRF-2026-004">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Delivery Notes & Special Handling</label>
                            <textarea id="customNotes" class="trf-search-input" style="padding: 8px 10px; height: 50px; resize: vertical;" placeholder="e.g., Maintain cold chain at 2°C to 4°C during transit..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Item Master Catalog Picker & Quantity Validation Grid -->
                <div class="panel-col">

                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-package"></i>
                                <span>Transfer Line Items & Stock Validation</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="customItemCountBadge">0 Items</span>
                        </div>

                        <!-- Catalog Quick-Add (Connected to Item Master & Stocks Overview) -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="customCatalogPicker" class="trf-select" style="width: 100%;">
                                    <option value="">-- Select Product from Item Master --</option>
                                    @foreach($initialProducts as $p)
                                        <option value="{{ $p->sku }}" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-category="{{ $p->category }}" data-uom="{{ $p->uom }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->current_stock }}" data-location="{{ $p->storage_location }}">
                                            [{{ $p->sku }}] {{ $p->name }} — Available: {{ number_format($p->current_stock, 2) }} {{ $p->uom }} (Cost: ₱{{ number_format($p->cost_price, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="number" id="customQuickQty" class="trf-search-input" style="width: 80px; padding-left: 10px;" placeholder="Qty" min="0.01" step="0.01" value="1.00">
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addCustomTransferItem()">
                                <i class="ph ph-plus"></i> Add Line
                            </button>
                        </div>

                        <!-- Real-time Quantity Validation Alert Banner (if any item exceeds stock) -->
                        <div id="customOverdraftBanner" class="qty-warning-box" style="display: none;">
                            <i class="ph ph-warning-circle" style="font-size: 18px; color: #dc2626;"></i>
                            <div>
                                <strong style="color: #dc2626;">Stock Overdraft Warning!</strong>
                                <span id="customOverdraftMsg">One or more items exceed current central inventory. Adjust quantities to proceed.</span>
                            </div>
                        </div>

                        <!-- Items Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="trf-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>UOM</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center; width: 110px;">Transfer Qty *</th>
                                        <th style="text-align: right;">Unit Cost (₱)</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="customItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="customNoItemsPrompt" style="padding: 30px; text-align: center; color: var(--trf-text-muted); font-size: 13px;">
                            <i class="ph ph-arrows-clockwise" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Select products from the Item Master above to configure your custom direct transfer.
                        </div>

                        <!-- Valuation Summary Strip -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Total Transfer Units: <strong id="customTotalUnitsDisplay" style="color: #0f172a;">0.00</strong>
                            </div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <span>Transfer Valuation:</span>
                                <span style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--trf-primary);" id="customTotalValDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

</div>

<!-- =====================================================================
     TRANSFER WAYBILL & RECEIVING MODAL
     ===================================================================== -->
<div class="rms-modal-overlay" id="transferModal">
    <div class="rms-modal-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-paper-plane-tilt" style="font-size: 24px; color: var(--trf-primary);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;" id="modalTrfNum">TRF-2026-0001</h3>
                    <div style="font-size: 11.5px; color: #64748b;" id="modalTrfDates">Dispatched at -</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="window.print()">
                    <i class="ph ph-printer"></i> Print Waybill
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeTransferModal()">
                    <i class="ph ph-x"></i> Close
                </button>
            </div>
        </div>

        <div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <div><strong>Origin:</strong> <span id="modalSource">-</span></div>
                    <div><strong>Destination Branch:</strong> <span id="modalDest">-</span></div>
                    <div><strong>Transfer Type:</strong> <span id="modalType">-</span></div>
                </div>
                <div>
                    <div><strong>Carrier / Van:</strong> <span id="modalCarrier">-</span></div>
                    <div><strong>Driver Plate:</strong> <span id="modalPlate">-</span></div>
                    <div><strong>Waybill #:</strong> <span id="modalWaybill">-</span></div>
                </div>
            </div>

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="trf-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>UOM</th>
                            <th style="text-align: right;">Requested</th>
                            <th style="text-align: right;">Dispatched</th>
                            <th style="text-align: right;">Unit Cost (₱)</th>
                            <th style="text-align: right;">Total Cost (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTbody">
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
                Total Transfer Valuation: <span id="modalValuationDisplay" style="color: var(--trf-primary); margin-left: 8px;">₱0.00</span>
            </div>

            <div id="modalReceiveActionSection" style="display: none; background: #faf5ff; padding: 14px; border-radius: 12px; border: 1px solid #f3e8ff; margin-bottom: 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="color: var(--trf-primary);">Destination Branch Acknowledgment</strong>
                        <div style="font-size: 11.5px; color: #64748b;">Confirm physical inspection and receive stock into branch storage.</div>
                    </div>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="confirmReceiveModalTransfer()">
                        <i class="ph ph-check-fat"></i> Confirm Receipt & Close
                    </button>
                </div>
            </div>

            <!-- Signatures Strip for Print -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; text-align: center;">
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Commissary Dispatcher Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Destination Branch Receiving Manager</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ==========================================================================
   INTERNAL TRANSFER JAVASCRIPT ENGINE
   Connected to Stocks Overview and Item Master with strict quantity validation
   ========================================================================== */

let rawTransfers = @json($initialTransfers);
let rawProducts = @json($initialProducts);
let rawBranches = @json($initialBranches);
let rawCategories = @json($initialCategories);

// Custom Direct Transfer State
let customItems = [];
let activeModalTransferId = null;

document.addEventListener('DOMContentLoaded', function() {
    renderTransfersList();
    renderPendingRequestsList();
    renderCustomTransferTable();
});

/* --------------------------------------------------------------------------
   TAB NAVIGATION
   -------------------------------------------------------------------------- */
function switchTrfTab(tabId) {
    document.querySelectorAll('.trf-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.add('active');

    if (tabId === 'tab-transfer-list') document.getElementById('tabBtnList').classList.add('active');
    if (tabId === 'tab-pending-requests') document.getElementById('tabBtnPendingRequests').classList.add('active');
    if (tabId === 'tab-custom-transfer') document.getElementById('tabBtnCustomTransfer').classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* --------------------------------------------------------------------------
   TAB 1: TRANSFER REGISTER & MASTERLIST (List)
   -------------------------------------------------------------------------- */
function renderTransfersList() {
    const tbody = document.getElementById('transfersTableBody');
    const searchVal = (document.getElementById('transferSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('transferStatusFilter')?.value || 'ALL';
    const typeVal = document.getElementById('transferTypeFilter')?.value || 'ALL';
    const branchVal = document.getElementById('transferBranchFilter')?.value || 'ALL';

    const filtered = rawTransfers.filter(trf => {
        if (statusVal !== 'ALL' && trf.status !== statusVal) return false;
        if (typeVal !== 'ALL' && trf.transfer_type !== typeVal) return false;
        if (branchVal !== 'ALL' && (trf.destination_location || '') !== branchVal) return false;
        if (searchVal) {
            const num = (trf.transfer_number || '').toLowerCase();
            const dest = (trf.destination_location || '').toLowerCase();
            const car = (trf.carrier_name || '').toLowerCase();
            const hasSku = (trf.items || []).some(it => (it.sku || '').toLowerCase().includes(searchVal) || (it.item_name || '').toLowerCase().includes(searchVal));
            if (!num.includes(searchVal) && !dest.includes(searchVal) && !car.includes(searchVal) && !hasSku) {
                return false;
            }
        }
        return true;
    });

    updateKpis();

    const noPrompt = document.getElementById('noTransfersPrompt');
    if (filtered.length === 0) {
        tbody.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    filtered.forEach(trf => {
        const reqQty = parseFloat(trf.total_requested_qty) || 0;
        const sentQty = parseFloat(trf.total_transferred_qty) || 0;

        let statusBadge = '<span class="hr-badge hr-badge-amber">Pending</span>';
        if (trf.status === 'IN_TRANSIT') statusBadge = '<span class="hr-badge hr-badge-blue"><i class="ph ph-truck"></i> In-Transit</span>';
        if (trf.status === 'COMPLETED') statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Completed</span>';
        if (trf.status === 'CANCELLED') statusBadge = '<span class="hr-badge hr-badge-red">Cancelled</span>';

        let typeBadge = trf.transfer_type === 'CUSTOM_PUSH'
            ? '<span class="hr-badge hr-badge-purple">Direct Push</span>'
            : '<span class="hr-badge hr-badge-neutral">Branch Requisition</span>';

        html += `
            <tr>
                <td>
                    <strong style="color: var(--trf-primary); cursor: pointer;" onclick="openTransferModal(${trf.id})">
                        ${trf.transfer_number}
                    </strong>
                    <div style="font-size: 11px; color: #94a3b8;">${formatDate(trf.created_at)}</div>
                </td>
                <td>${typeBadge}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #1e293b;">
                        <i class="ph ph-storefront" style="color: #64748b;"></i>
                        <span>${escapeHtml(trf.source_location)}</span>
                    </div>
                </td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; color: #0f172a;">
                        <i class="ph ph-map-pin" style="color: var(--trf-primary);"></i>
                        <span>${escapeHtml(trf.destination_location)}</span>
                    </div>
                </td>
                <td>
                    <div><strong>${trf.total_items_count || 0}</strong> items</div>
                    <div style="font-size: 11px; color: #64748b;">Req: ${reqQty.toFixed(2)}</div>
                </td>
                <td>
                    <strong>${sentQty.toFixed(2)}</strong> units
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${parseFloat(trf.total_valuation || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">${statusBadge}</td>
                <td style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openTransferModal(${trf.id})" title="View Details">
                            <i class="ph ph-eye"></i> View
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterTransfersList() {
    renderTransfersList();
}

function updateKpis() {
    const total = rawTransfers.length;
    const pending = rawTransfers.filter(t => t.status === 'PENDING').length;
    const inTransit = rawTransfers.filter(t => t.status === 'IN_TRANSIT').length;
    const val = rawTransfers.filter(t => ['IN_TRANSIT', 'COMPLETED'].includes(t.status))
                            .reduce((acc, t) => acc + (parseFloat(t.total_valuation) || 0), 0);

    const elTotal = document.getElementById('kpiTotalTransfers');
    if (elTotal) elTotal.textContent = total;

    const elPending = document.getElementById('kpiPendingCount');
    if (elPending) elPending.textContent = pending;

    const elTransit = document.getElementById('kpiInTransitCount');
    if (elTransit) elTransit.textContent = inTransit;

    const elVal = document.getElementById('kpiValuation');
    if (elVal) elVal.textContent = '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const badgePending = document.getElementById('badgePendingCount');
    if (badgePending) badgePending.textContent = pending + ' Backlog';

    const badgeList = document.getElementById('badgeListCount');
    if (badgeList) badgeList.textContent = total;
}

/* --------------------------------------------------------------------------
   TAB 2: PENDING BRANCH REQUESTS & BACKLOG
   -------------------------------------------------------------------------- */
function renderPendingRequestsList() {
    const container = document.getElementById('pendingRequestsContainer');
    const noPrompt = document.getElementById('noPendingPrompt');

    const pendingTransfers = rawTransfers.filter(t => t.status === 'PENDING');
    if (pendingTransfers.length === 0) {
        container.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    pendingTransfers.forEach(trf => {
        let itemsRows = '';
        let hasShortage = false;

        (trf.items || []).forEach(it => {
            const p = rawProducts.find(prod => prod.sku === it.sku);
            const stockOnHand = p ? parseFloat(p.current_stock) : 0;
            const req = parseFloat(it.requested_qty) || 0;
            const isShort = req > stockOnHand;
            if (isShort) hasShortage = true;

            itemsRows += `
                <tr>
                    <td><strong>${escapeHtml(it.item_name)}</strong> <span style="font-family: monospace; color: var(--trf-primary);">(${it.sku})</span></td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums;">${req.toFixed(2)} ${it.uom}</td>
                    <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: ${isShort ? '#dc2626' : '#059669'};">
                        ${stockOnHand.toFixed(2)} ${it.uom}
                    </td>
                    <td style="text-align: center;">
                        ${isShort
                            ? `<span class="hr-badge hr-badge-red"><i class="ph ph-warning"></i> Deficit: -${(req - stockOnHand).toFixed(2)}</span>`
                            : `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> In Stock</span>`
                        }
                    </td>
                </tr>
            `;
        });

        html += `
            <div class="panel-card" style="border-left: 4px solid #f59e0b;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <span class="hr-badge hr-badge-amber">PENDING REQUISITION</span>
                        <strong style="font-size: 1.1rem; color: #0f172a; margin-left: 8px;">${trf.transfer_number}</strong>
                        <span style="font-size: 12px; color: #64748b; margin-left: 8px;">(Ref: ${escapeHtml(trf.requisition_reference || 'N/A')})</span>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="dispatchPendingRequestPrompt(${trf.id})" ${hasShortage ? 'title="Warning: Item shortage detected"' : ''}>
                            <i class="ph ph-truck"></i> Fulfill & Dispatch
                        </button>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; font-size: 12px; background: #f8fafc; padding: 10px 14px; border-radius: 8px;">
                    <div><strong>Requesting Branch:</strong> ${escapeHtml(trf.destination_location)}</div>
                    <div><strong>Requisitioner:</strong> ${escapeHtml(trf.requested_by || 'Branch Store Manager')}</div>
                    <div><strong>Date Requested:</strong> ${formatDate(trf.created_at)}</div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="trf-table" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Item & SKU</th>
                                <th style="text-align: right;">Requested Qty</th>
                                <th style="text-align: right;">Commissary Stock on Hand</th>
                                <th style="text-align: center;">Stock Availability</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${itemsRows}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    });

    container.innerHTML = html;
}

async function dispatchPendingRequestPrompt(transferId) {
    const trf = rawTransfers.find(t => t.id == transferId);
    if (!trf) return;

    // Check inventory availability
    for (let it of (trf.items || [])) {
        const prod = rawProducts.find(p => p.sku === it.sku);
        const onHand = prod ? parseFloat(prod.current_stock) : 0;
        const req = parseFloat(it.requested_qty) || 0;
        if (req > onHand) {
            const confirmShort = await Swal.fire({
                title: 'Insufficient Inventory at Commissary!',
                html: `Item <strong>${it.item_name}</strong> requested <strong>${req}</strong>, but current central stock on hand is only <strong>${onHand}</strong>.<br><br>Adjust transfer quantity or replenish commissary before dispatching.`,
                icon: 'warning',
                confirmButtonColor: '#9333ea'
            });
            return;
        }
    }

    const { value: formValues } = await Swal.fire({
        title: `Dispatch Transfer ${trf.transfer_number}`,
        html: `
            <div style="text-align: left; font-size: 13px; display: flex; flex-direction: column; gap: 10px;">
                <div>
                    <label><strong>Carrier / Delivery Vehicle:</strong></label>
                    <input id="swalCarrier" class="trf-search-input" value="In-House Logistics Van 1">
                </div>
                <div>
                    <label><strong>Driver Plate #:</strong></label>
                    <input id="swalPlate" class="trf-search-input" placeholder="e.g. NDF 9012">
                </div>
                <div>
                    <label><strong>Waybill / Tracking #:</strong></label>
                    <input id="swalWaybill" class="trf-search-input" value="WB-TRF-${trf.transfer_number.replace(/\D/g,'')}">
                </div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: '✓ Confirm Dispatch & Deduct Stock',
        confirmButtonColor: '#059669',
        preConfirm: () => {
            return {
                carrier: document.getElementById('swalCarrier').value,
                plate: document.getElementById('swalPlate').value,
                waybill: document.getElementById('swalWaybill').value,
            }
        }
    });

    if (!formValues) return;

    const payload = {
        transfer_id: trf.id,
        dispatched_by: '{{ auth()->user()->full_name ?? auth()->user()->username ?? "Commissary Lead" }}',
        carrier_name: formValues.carrier,
        driver_plate: formValues.plate,
        waybill_number: formValues.waybill,
        items: trf.items.map(it => ({
            sku: it.sku,
            transferred_qty: parseFloat(it.requested_qty) || 0
        }))
    };

    try {
        Swal.fire({ title: 'Dispatching...', text: 'Deducting commissary stock and logging ledger entries...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.dispatch-transfer') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch transfer.');

        await fetchLiveTransferData();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            text: `Transfer ${trf.transfer_number} is now in-transit to ${trf.destination_location}.`,
            confirmButtonColor: '#9333ea'
        });
        switchTrfTab('tab-transfer-list');
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Dispatch Error', text: e.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   TAB 3: CUSTOM TRANSFER (Direct Push Delivery)
   -------------------------------------------------------------------------- */
function addCustomTransferItem() {
    const picker = document.getElementById('customCatalogPicker');
    const sku = picker.value;
    if (!sku) {
        Swal.fire({ icon: 'warning', title: 'Select Product', text: 'Please pick an item from Item Master first.', confirmButtonColor: '#9333ea' });
        return;
    }

    const opt = picker.options[picker.selectedIndex];
    const qtyInput = document.getElementById('customQuickQty');
    const qty = parseFloat(qtyInput.value) || 1.0;
    const stockOnHand = parseFloat(opt.dataset.stock) || 0.0;

    if (qty <= 0) {
        Swal.fire({ icon: 'error', title: 'Invalid Quantity', text: 'Transfer quantity must be greater than zero.', confirmButtonColor: '#9333ea' });
        return;
    }

    // Check if adding exceeds current stock
    const existing = customItems.find(it => it.sku === sku);
    if (existing) {
        existing.transferred_qty += qty;
        existing.subtotal = existing.transferred_qty * existing.unit_cost;
    } else {
        customItems.push({
            sku: sku,
            inventory_item_id: parseInt(opt.dataset.id) || null,
            item_name: opt.dataset.name,
            category: opt.dataset.category || 'General',
            uom: opt.dataset.uom || 'Unit',
            unit_cost: parseFloat(opt.dataset.cost) || 0.0,
            available_stock: stockOnHand,
            storage_location: opt.dataset.location || 'Central Commissary',
            transferred_qty: qty,
            subtotal: qty * (parseFloat(opt.dataset.cost) || 0.0)
        });
    }

    picker.value = '';
    qtyInput.value = '1.00';
    renderCustomTransferTable();
}

function renderCustomTransferTable() {
    const tbody = document.getElementById('customItemsTableBody');
    const prompt = document.getElementById('customNoItemsPrompt');
    const countBadge = document.getElementById('customItemCountBadge');
    const overdraftBanner = document.getElementById('customOverdraftBanner');
    const btnDispatch = document.getElementById('btnDispatchCustomTransfer');

    countBadge.textContent = customItems.length + ' Items';

    if (customItems.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        overdraftBanner.style.display = 'none';
        btnDispatch.disabled = false;
        document.getElementById('customTotalUnitsDisplay').textContent = '0.00';
        document.getElementById('customTotalValDisplay').textContent = '₱0.00';
        return;
    }
    prompt.style.display = 'none';

    let html = '';
    let totalUnits = 0;
    let totalVal = 0;
    let hasOverdraft = false;

    customItems.forEach((it, idx) => {
        totalUnits += it.transferred_qty;
        const lineTotal = it.transferred_qty * it.unit_cost;
        totalVal += lineTotal;

        const isOverdraft = it.transferred_qty > it.available_stock;
        if (isOverdraft) hasOverdraft = true;

        html += `
            <tr style="${isOverdraft ? 'background: #fef2f2;' : ''}">
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--trf-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-archive"></i> ${escapeHtml(it.storage_location)}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${it.uom}</span></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: ${isOverdraft ? '#dc2626' : '#059669'};">
                        ${it.available_stock.toFixed(2)}
                    </span>
                </td>
                <td style="text-align: center;">
                    <input type="number" class="trf-search-input" style="width: 80px; text-align: right; padding: 4px 6px; font-weight: 700; ${isOverdraft ? 'border-color: #dc2626; color: #dc2626;' : ''}"
                           value="${it.transferred_qty.toFixed(2)}" min="0.01" step="0.01"
                           onchange="updateCustomItemQty(${idx}, this.value)">
                    ${isOverdraft ? `<div style="font-size: 10px; color: #dc2626; font-weight: 700; margin-top: 2px;">Overdraft!</div>` : ''}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ₱${it.unit_cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #dc2626; border-color: #fee2e2;" onclick="removeCustomItem(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('customTotalUnitsDisplay').textContent = totalUnits.toFixed(2);
    document.getElementById('customTotalValDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (hasOverdraft) {
        overdraftBanner.style.display = 'flex';
        btnDispatch.disabled = true;
        btnDispatch.style.opacity = '0.5';
    } else {
        overdraftBanner.style.display = 'none';
        btnDispatch.disabled = false;
        btnDispatch.style.opacity = '1';
    }
}

function updateCustomItemQty(idx, val) {
    const q = parseFloat(val) || 0;
    if (q <= 0) {
        removeCustomItem(idx);
        return;
    }
    customItems[idx].transferred_qty = q;
    customItems[idx].subtotal = q * customItems[idx].unit_cost;
    renderCustomTransferTable();
}

function removeCustomItem(idx) {
    customItems.splice(idx, 1);
    renderCustomTransferTable();
}

function resetCustomTransferForm() {
    customItems = [];
    renderCustomTransferTable();
    document.getElementById('customNotes').value = '';
    document.getElementById('customWaybill').value = '';
    document.getElementById('customCarrierName').value = '';
    document.getElementById('customDriverPlate').value = '';
}

async function submitCustomTransfer() {
    if (customItems.length === 0) {
        Swal.fire({ icon: 'warning', title: 'Empty Transfer', text: 'Please add items before dispatching.', confirmButtonColor: '#9333ea' });
        return;
    }

    // Check for any overdraft
    for (let it of customItems) {
        if (it.transferred_qty > it.available_stock) {
            Swal.fire({
                icon: 'error',
                title: 'Quantity Validation Error',
                text: `Transfer quantity for '${it.item_name}' (${it.transferred_qty}) exceeds available stock (${it.available_stock}). Please adjust.`,
                confirmButtonColor: '#9333ea'
            });
            return;
        }
    }

    const branchSel = document.getElementById('customDestBranch');
    const branchId = parseInt(branchSel.value);
    const branchName = branchSel.options[branchSel.selectedIndex].dataset.name || branchSel.options[branchSel.selectedIndex].text;

    const payload = {
        transfer_type: 'CUSTOM_PUSH',
        destination_branch_id: branchId,
        destination_location: branchName,
        source_location: document.getElementById('customSourceLoc').value,
        reason_code: document.getElementById('customReasonCode').value,
        dispatched_by: document.getElementById('customDispatchedBy').value || 'Commissary Lead',
        carrier_name: document.getElementById('customCarrierName').value || null,
        driver_plate: document.getElementById('customDriverPlate').value || null,
        waybill_number: document.getElementById('customWaybill').value || null,
        priority: 'NORMAL',
        notes: document.getElementById('customNotes').value || null,
        items: customItems.map(it => ({
            sku: it.sku,
            inventory_item_id: it.inventory_item_id,
            item_name: it.item_name,
            category: it.category,
            uom: it.uom,
            transferred_qty: it.transferred_qty,
            unit_cost: it.unit_cost,
            storage_location: it.storage_location
        }))
    };

    try {
        Swal.fire({ title: 'Dispatching Transfer...', text: 'Validating central stock and updating ledger...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.create-custom-transfer') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch custom transfer.');

        await fetchLiveTransferData();
        resetCustomTransferForm();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
                <p>Custom Transfer <strong>${data.data.transfer.transfer_number}</strong> has been dispatched to <strong>${branchName}</strong>.</p>
                <p style="font-size: 12px; color: #64748b;">Central inventory has been decremented and logged to Master Stock Ledger.</p>
            `,
            confirmButtonColor: '#9333ea'
        });

        switchTrfTab('tab-transfer-list');
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Transfer Error', text: e.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveTransferData() {
    try {
        const res = await fetch("{{ route('inventory.api.internal-transfer-data') }}");
        const json = await res.json();
        if (json.success && json.data) {
            rawTransfers = json.data.transfers || [];
            rawProducts = json.data.products || [];
            rawBranches = json.data.branches || [];
            rawCategories = json.data.categories || [];
            renderTransfersList();
            renderPendingRequestsList();
        }
    } catch (e) {
        console.error('Failed to sync internal transfer data:', e);
    }
}

/* --------------------------------------------------------------------------
   MODAL & RECEIVING ACTIONS
   -------------------------------------------------------------------------- */
function openTransferModal(transferId) {
    const trf = rawTransfers.find(t => t.id == transferId);
    if (!trf) return;
    activeModalTransferId = trf.id;

    document.getElementById('modalTrfNum').textContent = trf.transfer_number + ' [' + trf.status + ']';
    document.getElementById('modalTrfDates').textContent = 'Created: ' + formatDate(trf.created_at) + (trf.dispatched_at ? ' | Dispatched: ' + formatDate(trf.dispatched_at) : '');
    document.getElementById('modalSource').textContent = trf.source_location;
    document.getElementById('modalDest').textContent = trf.destination_location;
    document.getElementById('modalType').textContent = trf.transfer_type === 'CUSTOM_PUSH' ? 'Direct Push Transfer' : 'Branch Requisition';
    document.getElementById('modalCarrier').textContent = trf.carrier_name || 'Standard Courier';
    document.getElementById('modalPlate').textContent = trf.driver_plate || '-';
    document.getElementById('modalWaybill').textContent = trf.waybill_number || '-';

    const tbody = document.getElementById('modalItemsTbody');
    let html = '';
    let totalVal = 0;

    (trf.items || []).forEach(it => {
        const req = parseFloat(it.requested_qty) || 0;
        const sent = parseFloat(it.transferred_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        const lineVal = sent > 0 ? (sent * cost) : (req * cost);
        totalVal += lineVal;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--trf-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td><span class="hr-badge hr-badge-neutral">${it.uom}</span></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">${req.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #059669;">${sent.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${cost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${lineVal.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('modalValuationDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const receiveSec = document.getElementById('modalReceiveActionSection');
    if (trf.status === 'IN_TRANSIT') {
        receiveSec.style.display = 'block';
    } else {
        receiveSec.style.display = 'none';
    }

    document.getElementById('transferModal').classList.add('open');
}

function closeTransferModal() {
    document.getElementById('transferModal').classList.remove('open');
    activeModalTransferId = null;
}

async function confirmReceiveModalTransfer() {
    if (!activeModalTransferId) return;

    try {
        Swal.fire({ title: 'Confirming Delivery...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.receive-transfer') }}", {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({
                transfer_id: activeModalTransferId,
                received_by: '{{ auth()->user()->full_name ?? auth()->user()->username ?? "Branch Receiving Lead" }}',
            })
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to confirm receipt.');

        await fetchLiveTransferData();
        closeTransferModal();

        Swal.fire({
            icon: 'success',
            title: 'Transfer Completed!',
            text: 'Delivery confirmed and receipt logged.',
            confirmButtonColor: '#9333ea'
        });
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'Receipt Error', text: e.message, confirmButtonColor: '#9333ea' });
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch(e) {
        return dateStr;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return str.toString()
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
@endpush
@endsection

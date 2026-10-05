@extends('layouts.app')

@section('title', 'Waste & Expiry Management - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   WASTE & EXPIRY MANAGEMENT MODULE - UNIVERSAL HR THEME
   Adhering to docs/HR_THEME_DESIGN_SYSTEM.md & assets/css/theme-tokens.css
   ========================================================================== */
:root {
    --wst-canvas-bg: #faf7fd;
    --wst-surface-card: rgba(255, 255, 255, 0.96);
    --wst-surface-subtle: #f8fafc;
    --wst-surface-hover: rgba(245, 243, 255, 0.65);
    --wst-border: rgba(226, 232, 240, 0.9);
    --wst-border-focus: #c084fc;

    --wst-primary: #9333ea;
    --wst-primary-dark: #7c3aed;
    --wst-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --wst-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);
    --wst-primary-glow: rgba(168, 85, 247, 0.28);
    --wst-accent-strip: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);

    --wst-danger: #ef4444;
    --wst-danger-dark: #dc2626;
    --wst-danger-subtle: #fef2f2;

    --wst-warning: #f59e0b;
    --wst-warning-dark: #d97706;
    --wst-warning-subtle: #fffbeb;

    --wst-success: #10b981;
    --wst-success-dark: #059669;
    --wst-success-subtle: #ecfdf5;

    --wst-info: #3b82f6;
    --wst-info-dark: #2563eb;
    --wst-info-subtle: #eff6ff;

    --wst-text-strong: #0f172a;
    --wst-text-medium: #334155;
    --wst-text-muted: #64748b;
    --wst-text-subtle: #94a3b8;

    --wst-shadow-card: 0 10px 32px rgba(148, 163, 184, 0.08), 0 2px 8px rgba(0, 0, 0, 0.02);
    --wst-shadow-card-hover: 0 14px 32px rgba(168, 85, 247, 0.14), 0 4px 10px rgba(236, 72, 153, 0.08);
}

.wst-workspace {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background-color: var(--wst-canvas-bg);
    min-height: calc(100vh - 72px);
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--wst-text-strong);
}

.wst-card-strip {
    position: relative;
    background: var(--wst-surface-card);
    border: 1px solid var(--wst-border);
    border-radius: 16px;
    box-shadow: var(--wst-shadow-card);
    overflow: hidden;
}

.wst-card-strip::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--wst-accent-strip);
    z-index: 2;
}

/* Page Header */
/* ==========================================================================
   HR-STYLE FLAT PAGE HEADER & TITLE (Synced with HR Design System)
   ========================================================================== */
.hr-parent-header {
    margin-bottom: 8px;
    width: 100%;
}
.hr-parent-title-row,
.wst-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0px;
    flex-wrap: wrap;
}
.hr-parent-title,
.wst-page-title {
    font-family: var(--font-heading, 'Poppins', sans-serif);
    font-size: 21px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
    letter-spacing: -0.02em;
    margin: 0;
}
.hr-parent-title i {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20));
    border: 1px solid rgba(168, 85, 247, 0.32);
    color: #9333ea;
    font-size: 18px;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    flex-shrink: 0;
}
.hr-parent-subtitle,
.wst-page-subtitle {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 400;
    margin: 3px 0 0 0;
}
.hr-page-actions,
.wst-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* ==========================================================================
   PRIMARY TABS-WRAPPER BAR (Synced with HR Design System)
   ========================================================================== */
.hr-tabs-wrapper,
.tabs-wrapper {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 12px;
    padding: 4px 6px;
    margin-top: 4px;
    margin-bottom: 14px;
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    display: flex;
    align-items: center;
    gap: 6px;
    max-width: 100%;
    min-width: 0;
    overflow-x: auto;
    flex-wrap: wrap;
    position: relative;
    z-index: 20;
}
.tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 9px;
    font-family: inherit;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    white-space: nowrap;
    position: relative;
    flex-shrink: 0;
    text-decoration: none;
}
.tab-btn i {
    font-size: 16px;
    color: #94a3b8;
    transition: color 0.2s ease, transform 0.2s ease;
}
.tab-btn:hover {
    color: #9333ea;
    background: rgba(168, 85, 247, 0.08);
}
.tab-btn:hover i {
    color: #9333ea;
    transform: scale(1.08);
}
.tab-btn.active {
    background: #ffffff;
    color: #9333ea;
    font-weight: 700;
    border-color: rgba(168, 85, 247, 0.28);
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.14), 0 1px 3px rgba(0, 0, 0, 0.04);
}
.tab-btn.active i {
    background: linear-gradient(135deg, #ec4899, #a855f7);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
}
.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -6px;
    left: 20%;
    right: 20%;
    height: 3px;
    background: linear-gradient(90deg, #ec4899, #a855f7);
    border-radius: 3px 3px 0 0;
}
.tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 20px;
    height: 18px;
    padding: 0 6px;
    font-size: 11px;
    font-weight: 700;
    border-radius: 9px;
    background: #f1f5f9;
    color: #64748b;
    transition: all 0.2s ease;
}
.tab-btn.active .tab-count {
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
}

/* Tab Panels */
.wst-tab-panel {
    display: none;
}

.wst-tab-panel.active {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* KPI Summary Cards */
.wst-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.wst-kpi-card {
    background: #ffffff;
    border: 1px solid var(--wst-border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--wst-shadow-card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.wst-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--wst-shadow-card-hover);
}

.wst-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.wst-kpi-content {
    flex: 1;
}

.wst-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--wst-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.wst-kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--wst-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.wst-kpi-meta {
    font-size: 11.5px;
    color: var(--wst-text-muted);
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.kpi-icon-purple { background: rgba(147, 51, 234, 0.12); color: #9333ea; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }
.kpi-icon-red { background: rgba(239, 68, 68, 0.12); color: #dc2626; }
.kpi-icon-blue { background: rgba(59, 130, 246, 0.12); color: #2563eb; }

/* Filter & Search Bar */
.wst-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    background: #ffffff;
    border: 1px solid var(--wst-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.wst-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 440px;
}

.wst-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--wst-text-subtle);
    font-size: 16px;
}

.wst-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 13px;
    color: var(--wst-text-strong);
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.wst-search-input:focus {
    border-color: var(--wst-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

.wst-select {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: var(--wst-text-strong);
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.wst-select:focus {
    border-color: var(--wst-border-focus);
}

/* Data Table */
.wst-table-wrapper {
    overflow-x: auto;
    background: #ffffff;
    border: 1px solid var(--wst-border);
    border-radius: 14px;
    box-shadow: var(--wst-shadow-card);
}

.wst-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 12.5px;
}

.wst-table th {
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

.wst-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--wst-text-medium);
    vertical-align: middle;
}

.wst-table tr:hover td {
    background-color: var(--wst-surface-hover);
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
    background: var(--wst-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
}

.hr-btn-primary:hover {
    background: var(--wst-primary-gradient-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.4);
}

.hr-btn-secondary {
    background: #ffffff;
    color: var(--wst-text-medium);
    border: 1px solid #cbd5e1;
}

.hr-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: var(--wst-text-strong);
}

.hr-btn-danger {
    background: #fee2e2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.hr-btn-danger:hover {
    background: #fecaca;
    color: #b91c1c;
}

.hr-btn-sm {
    padding: 5px 11px;
    font-size: 12px;
    border-radius: 8px;
}

/* Two Column Form Grid */
.wst-form-grid {
    display: grid;
    grid-template-columns: 440px 1fr;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1024px) {
    .wst-form-grid {
        grid-template-columns: 1fr;
    }
}

.wst-form-card {
    background: #ffffff;
    border: 1px solid var(--wst-border);
    border-radius: 14px;
    padding: 22px;
    box-shadow: var(--wst-shadow-card);
}

.wst-form-title {
    margin: 0 0 16px 0;
    font-family: 'Outfit', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--wst-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}

.wst-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.wst-form-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--wst-text-medium);
}

.wst-form-label small {
    color: var(--wst-text-muted);
    font-weight: normal;
}

.wst-form-control {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
    transition: all 0.2s;
    background: #ffffff;
    font-family: inherit;
    color: var(--wst-text-strong);
}

.wst-form-control:focus {
    border-color: var(--wst-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

textarea.wst-form-control {
    height: 80px;
    padding: 10px 12px;
    resize: vertical;
}

/* Item Selection Preview Card */
.wst-item-preview {
    background: #faf5ff;
    border: 1px dashed #d8b4fe;
    border-radius: 12px;
    padding: 16px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.wst-preview-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 12.5px;
}

.wst-preview-val {
    font-weight: 700;
    font-family: 'Outfit', sans-serif;
    color: var(--wst-primary);
}

/* Modal styles */
.wst-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.wst-modal-backdrop.open {
    display: flex;
}

.wst-modal-box {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 640px;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
    border: 1px solid #e2e8f0;
}

.wst-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid #f1f5f9;
}

.wst-modal-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--wst-text-strong);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.wst-modal-body {
    padding: 24px;
    font-size: 13px;
    line-height: 1.6;
}

.wst-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    border-top: 1px solid #f1f5f9;
    background: #f8fafc;
    border-bottom-left-radius: 16px;
    border-bottom-right-radius: 16px;
}
</style>
@endpush

@section('content')
<style>
/* Compact Spacing Overrides (Employees Directory parity) */
.wst-workspace {
    gap: 8px !important;
    padding: 12px 16px !important;
}
.hr-parent-header { margin-bottom: 6px !important; }
.hr-parent-header .hr-parent-title-row { margin-bottom: 4px !important; }
.hr-parent-header .hr-parent-title { font-size: 20px !important; gap: 8px !important; }
.hr-parent-header .hr-parent-title i { width: 32px !important; height: 32px !important; font-size: 17px !important; border-radius: 8px !important; }
.hr-parent-header .hr-parent-subtitle { font-size: 12px !important; margin-top: 2px !important; }
.hr-tabs-wrapper { margin-top: 6px !important; margin-bottom: 8px !important; padding: 3px 5px !important; gap: 4px !important; border-radius: 10px !important; }
.hr-tabs-wrapper .tab-btn { padding: 5px 12px !important; font-size: 12.5px !important; }
.wst-tab-panel.active { gap: 8px !important; }
.wst-filter-bar { padding: 8px 12px !important; gap: 8px !important; }
</style>

<div class="wst-workspace">

    <!-- Top Header -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row wst-page-header">
            <div>
                <h1 class="hr-parent-title wst-page-title">
                    <i class="ph ph-trash-simple"></i>
                    <span>Waste, Defect & Expiry Tracking</span>
                    <span class="hr-badge hr-badge-purple">Kitchen Audit</span>
                </h1>
                <p class="hr-parent-subtitle wst-page-subtitle">Track spoilage, handling defects, batch expirations, and stock write-offs synchronized with Item Masterlist and Stocks Overview.</p>
            </div>
            <div class="hr-page-actions wst-header-actions">
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Live Stocks Overview">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <button class="hr-btn hr-btn-primary" onclick="switchTab('tab-log-waste')">
                    <i class="ph ph-plus-circle"></i> Log Waste / Defect
                </button>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <nav class="hr-tabs-wrapper tabs-wrapper">
        <button type="button" class="tab-btn active" id="btnTabRecords" onclick="switchTab('tab-records')">
            <i class="ph ph-list-dashes"></i> Waste & Defect Logs
            <span class="tab-count" id="badgeCountRecords">{{ $wasteRecords->count() }}</span>
        </button>
        <button type="button" class="tab-btn" id="btnTabLogWaste" onclick="switchTab('tab-log-waste')">
            <i class="ph ph-pencil-simple-line"></i> Log Waste / Defect Entry
            <span class="tab-count"><i class="ph ph-plus" style="font-size: 10px;"></i></span>
        </button>
        <button type="button" class="tab-btn" id="btnTabExpiryWatchlist" onclick="switchTab('tab-expiry-watchlist')">
            <i class="ph ph-clock-countdown"></i> Expiry & At-Risk Watchlist
            <span class="tab-count" id="badgeCountWatchlist">{{ $atRiskLots->count() }}</span>
        </button>
    </nav>

    <!-- =========================================================================
         TAB 1: WASTE & DEFECT LOGS / REGISTRY
         ========================================================================= -->
    <div class="wst-tab-panel active" id="tab-records">
        <!-- Search & Filter Controls -->
        <div class="wst-filter-bar">
            <div class="wst-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="wstSearchInput" class="wst-search-input" placeholder="Search by SKU, item name, voucher #, or reporter..." oninput="filterWasteTable()">
            </div>

            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <select id="wstTypeFilter" class="wst-select" onchange="filterWasteTable()">
                    <option value="ALL">All Incident Types</option>
                    <option value="SPOILAGE">Spoilage & Curdling</option>
                    <option value="EXPIRED">Expired Batch</option>
                    <option value="DAMAGED">Handling Damage</option>
                    <option value="DEFECTIVE">Packaging Defect</option>
                    <option value="PREP_FALLOUT">Prep Kitchen Fallout</option>
                    <option value="STORAGE_FAILURE">Cold Storage Failure</option>
                </select>

                <select id="wstStatusFilter" class="wst-select" onchange="filterWasteTable()">
                    <option value="ALL">All Statuses</option>
                    <option value="APPROVED">Approved / Written Off</option>
                    <option value="PENDING_REVIEW">Pending Review</option>
                    <option value="DISPOSED">Physically Disposed</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>

                <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetWasteFilters()">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
            </div>
        </div>

        <!-- Table Card -->
        <div class="wst-table-wrapper">
            <table class="wst-table" id="wasteLogsTable">
                <thead>
                    <tr>
                        <th>Waste Voucher #</th>
                        <th>Incident Type</th>
                        <th>Items & Primary SKU</th>
                        <th>Qty / UOM</th>
                        <th>Cost Valuation</th>
                        <th>Disposal Method</th>
                        <th>Reported By / Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="wasteLogsTbody">
                    @forelse($wasteRecords as $w)
                    @php
                        $firstItem = $w->items->first();
                        $itemSummary = $firstItem ? $firstItem->item_name : 'No items';
                        if ($w->items->count() > 1) {
                            $itemSummary .= ' (+' . ($w->items->count() - 1) . ' more)';
                        }
                        $skuSummary = $firstItem ? $firstItem->sku : '-';
                        $qtySummary = $firstItem ? number_format($firstItem->quantity, 2) . ' ' . $firstItem->uom : '-';
                    @endphp
                    <tr data-type="{{ $w->waste_type }}" data-status="{{ $w->status }}" data-search="{{ strtolower($w->waste_number . ' ' . $skuSummary . ' ' . $itemSummary . ' ' . $w->reported_by . ' ' . ($firstItem->reason_code ?? '')) }}">
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif; color: var(--wst-primary);">{{ $w->waste_number }}</strong>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $w->storage_location }}</div>
                        </td>
                        <td>
                            @if($w->waste_type === 'EXPIRED')
                                <span class="hr-badge hr-badge-red"><i class="ph ph-calendar-x"></i> Expired</span>
                            @elseif($w->waste_type === 'SPOILAGE')
                                <span class="hr-badge hr-badge-amber"><i class="ph ph-drop-half-bottom"></i> Spoilage</span>
                            @elseif($w->waste_type === 'DAMAGED')
                                <span class="hr-badge hr-badge-purple"><i class="ph ph-hammer"></i> Damaged</span>
                            @elseif($w->waste_type === 'DEFECTIVE')
                                <span class="hr-badge hr-badge-blue"><i class="ph ph-warning"></i> Defect</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $w->waste_type }}</span>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--wst-text-strong);">{{ $itemSummary }}</div>
                            <div style="font-size: 11px; color: var(--wst-text-muted);"><span style="background: #f1f5f9; padding: 1px 6px; border-radius: 4px;">{{ $skuSummary }}</span> @if($firstItem && $firstItem->reason_code) &bull; {{ $firstItem->reason_code }} @endif</div>
                        </td>
                        <td>
                            <span style="font-weight: 700; font-family: 'Outfit', sans-serif;">{{ $qtySummary }}</span>
                        </td>
                        <td>
                            <strong style="color: var(--wst-danger); font-family: 'Outfit', sans-serif;">-₱{{ number_format($w->total_cost, 2) }}</strong>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: var(--wst-text-medium);">{{ $w->disposal_method }}</span>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ $w->reported_by }}</div>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ \Carbon\Carbon::parse($w->waste_date)->format('M d, Y') }}</div>
                        </td>
                        <td>
                            @if($w->status === 'APPROVED')
                                <span class="hr-badge hr-badge-green"><i class="ph ph-check-circle"></i> Approved</span>
                            @elseif($w->status === 'PENDING_REVIEW')
                                <span class="hr-badge hr-badge-amber"><i class="ph ph-clock"></i> In Review</span>
                            @elseif($w->status === 'DISPOSED')
                                <span class="hr-badge hr-badge-blue"><i class="ph ph-trash"></i> Disposed</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $w->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="viewWasteDetail({{ json_encode($w) }})">
                                <i class="ph ph-eye"></i> Details
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 48px; color: var(--wst-text-muted);">
                            <i class="ph ph-trash-simple" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                            No waste or defect records found. Use the "Log Waste / Defect Entry" tab to record write-offs.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         TAB 2: LOG WASTE / DEFECT ENTRY FORM
         ========================================================================= -->
    <div class="wst-tab-panel" id="tab-log-waste">
        <div class="wst-form-grid">
            <!-- Left Column: Master Form Inputs -->
            <div class="wst-form-card">
                <h3 class="wst-form-title">
                    <i class="ph ph-pencil-simple-line" style="color: var(--wst-primary);"></i>
                    Record New Incident / Loss
                </h3>

                <form id="formLogWaste" onsubmit="submitWasteRecord(event)">
                    @csrf

                    <!-- Incident Classification -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Incident Classification <small>(Required)</small></label>
                        <select id="inputWasteType" class="wst-form-control" required onchange="onWasteTypeChange()">
                            <option value="SPOILAGE">Spoilage & Curdling (Biological)</option>
                            <option value="EXPIRED">Expired Batch (Past Expiry Date)</option>
                            <option value="DAMAGED">Handling / Dropped Damage</option>
                            <option value="DEFECTIVE">Packaging / Seal Defect</option>
                            <option value="PREP_FALLOUT">Prep Kitchen Fallout / Trim</option>
                            <option value="STORAGE_FAILURE">Cold Storage / Temperature Breach</option>
                            <option value="OTHER">Other Unforeseen Loss</option>
                        </select>
                    </div>

                    <!-- Target Item Selection from Masterlist -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Select Item from Masterlist <small>(Required)</small></label>
                        <select id="inputInventoryItem" class="wst-form-control" required onchange="onSelectItem(this.value)">
                            <option value="">-- Choose Item / SKU --</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}" 
                                    data-sku="{{ $p->sku }}"
                                    data-name="{{ $p->name }}"
                                    data-category="{{ $p->category }}"
                                    data-uom="{{ $p->uom }}"
                                    data-stock="{{ $p->current_stock }}"
                                    data-cost="{{ $p->cost_price }}"
                                    data-location="{{ $p->storage_location }}">
                                    [{{ $p->sku }}] {{ $p->name }} — On Hand: {{ number_format($p->current_stock, 2) }} {{ $p->uom }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Live Item Preview Box -->
                    <div class="wst-item-preview" id="itemPreviewBox" style="display: none;">
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Current Physical Stock:</span>
                            <span class="wst-preview-val" id="prevStock">-</span>
                        </div>
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Unit Cost:</span>
                            <span class="wst-preview-val" id="prevCost">-</span>
                        </div>
                        <div class="wst-preview-row">
                            <span style="color: var(--wst-text-muted);">Storage Location:</span>
                            <span style="font-weight: 600;" id="prevLocation">-</span>
                        </div>
                    </div>

                    <!-- Quantity & Reason Code Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Write-Off Quantity <small>(Required)</small></label>
                            <input type="number" step="0.01" min="0.01" id="inputQuantity" class="wst-form-control" required placeholder="0.00" oninput="calculateLossValuation()">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Loss Valuation <small>(Computed)</small></label>
                            <input type="text" id="inputValuation" class="wst-form-control" readonly style="background: #f8fafc; font-weight: 700; color: var(--wst-danger);" value="₱0.00">
                        </div>
                    </div>

                    <div class="wst-form-group">
                        <label class="wst-form-label">Reason Code <small>(Required)</small></label>
                        <select id="inputReasonCode" class="wst-form-control" required>
                            <option value="Expired on Shelf">Expired on Shelf</option>
                            <option value="Mold / Spoilage">Mold / Spoilage</option>
                            <option value="Cold Chain Temperature Breach">Cold Chain Temperature Breach</option>
                            <option value="Physical Handling Damage">Physical Handling Damage</option>
                            <option value="Packaging Defect">Packaging Defect</option>
                            <option value="Preparation Fallout">Preparation Fallout</option>
                            <option value="Pest Contamination">Pest Contamination</option>
                        </select>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Batch / Lot # <small>(Optional)</small></label>
                            <input type="text" id="inputBatchLot" class="wst-form-control" placeholder="e.g. LOT-202610-01">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Expiry Date <small>(If applicable)</small></label>
                            <input type="date" id="inputExpiryDate" class="wst-form-control">
                        </div>
                    </div>

                    <!-- Disposal Action -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Disposal Action <small>(Required)</small></label>
                        <select id="inputDisposalMethod" class="wst-form-control" required>
                            <option value="Discarded / Trashed">Discarded / Trashed</option>
                            <option value="Composted">Composted</option>
                            <option value="Supplier Return / Credit Claim">Supplier Return / Credit Claim</option>
                            <option value="Hazardous Disposal">Hazardous Disposal</option>
                            <option value="Animal Feed / Bio-waste">Animal Feed / Bio-waste</option>
                        </select>
                    </div>

                    <!-- Reporter & Date Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                        <div class="wst-form-group">
                            <label class="wst-form-label">Reported By</label>
                            <input type="text" id="inputReportedBy" class="wst-form-control" required value="{{ auth()->user()->full_name ?? 'Kitchen Staff' }}">
                        </div>

                        <div class="wst-form-group">
                            <label class="wst-form-label">Incident Date</label>
                            <input type="date" id="inputWasteDate" class="wst-form-control" required value="{{ date('Y-m-d') }}">
                        </div>
                    </div>

                    <!-- Investigation Notes -->
                    <div class="wst-form-group">
                        <label class="wst-form-label">Incident Notes & Evidence</label>
                        <textarea id="inputNotes" class="wst-form-control" placeholder="Describe root cause, storage conditions, or immediate corrective action..."></textarea>
                    </div>

                    <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; padding: 12px; font-size: 14px;">
                        <i class="ph ph-check-circle"></i> Commit Waste Write-Off & Deduct Stock
                    </button>
                </form>
            </div>

            <!-- Right Column: Standard Operating Procedures & Guidelines -->
            <div style="display: flex; flex-direction: column; gap: 20px;">
                <div class="wst-form-card">
                    <h3 class="wst-form-title">
                        <i class="ph ph-book-open" style="color: var(--wst-primary);"></i>
                        Loss Logging & Audit Principles
                    </h3>
                    <div style="font-size: 13px; color: var(--wst-text-medium); display: flex; flex-direction: column; gap: 14px;">
                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">1</div>
                            <div>
                                <strong>Direct Stock Ledger Impact (SYS_LEDGER)</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Every logged incident automatically appends an immutable record with negative quantity to the central Stock Ledger with reference number <code>WST-XXXX</code>.</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">2</div>
                            <div>
                                <strong>Stocks Overview Synchronization</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Reflects live under <strong>Col 6: Waste / Loss (-)</strong> in Stocks Overview, segmented by damaged vs. expired quantities.</p>
                            </div>
                        </div>

                        <div style="display: flex; gap: 12px;">
                            <div style="width: 28px; height: 28px; border-radius: 8px; background: rgba(147, 51, 234, 0.12); color: var(--wst-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: 700;">3</div>
                            <div>
                                <strong>Supplier Return & Credit Claims</strong>
                                <p style="margin: 3px 0 0 0; color: var(--wst-text-muted);">Defects discovered at receiving dock can be assigned to <code>Supplier Return / Credit Claim</code> for reimbursement against outstanding vendor bills.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Write-Offs Quick Card -->
                <div class="wst-form-card">
                    <h3 class="wst-form-title">
                        <i class="ph ph-clock-counter-clockwise" style="color: var(--wst-primary);"></i>
                        Recent Approved Write-Offs
                    </h3>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        @foreach($wasteRecords->take(4) as $rec)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: #f8fafc; border-radius: 8px;">
                            <div>
                                <strong style="font-size: 12.5px; color: var(--wst-text-strong);">{{ $rec->waste_number }}</strong>
                                <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $rec->waste_type }} &bull; {{ $rec->reported_by }}</div>
                            </div>
                            <span style="font-family: 'Outfit', sans-serif; font-weight: 700; color: var(--wst-danger); font-size: 13px;">
                                -₱{{ number_format($rec->total_cost, 2) }}
                            </span>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         TAB 3: EXPIRY & AT-RISK WATCHLIST
         ========================================================================= -->
    <div class="wst-tab-panel" id="tab-expiry-watchlist">
        <!-- Watchlist Filter Bar -->
        <div class="wst-filter-bar">
            <div class="wst-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="watchlistSearchInput" class="wst-search-input" placeholder="Search at-risk lot numbers or SKU..." oninput="filterWatchlistTable()">
            </div>

            <div style="display: flex; align-items: center; gap: 10px;">
                <select id="watchlistRiskFilter" class="wst-select" onchange="filterWatchlistTable()">
                    <option value="ALL">All Risk Levels</option>
                    <option value="EXPIRED">Expired (&lt; 0 Days)</option>
                    <option value="CRITICAL">Critical (0 - 3 Days)</option>
                    <option value="WARNING">Warning (4 - 7 Days)</option>
                    <option value="HEALTHY">Healthy (&gt; 7 Days)</option>
                </select>
            </div>
        </div>

        <!-- Table Card -->
        <div class="wst-table-wrapper">
            <table class="wst-table" id="expiryWatchlistTable">
                <thead>
                    <tr>
                        <th>Batch / Lot Number</th>
                        <th>Item & SKU</th>
                        <th>Estimated Balance</th>
                        <th>Expiry Date</th>
                        <th>Days Remaining</th>
                        <th>Risk Assessment</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="expiryWatchlistTbody">
                    @forelse($atRiskLots as $lot)
                    <tr data-risk="{{ $lot->risk_status }}" data-search="{{ strtolower(($lot->batch_lot_no ?? '') . ' ' . $lot->sku . ' ' . $lot->item_name) }}">
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif; color: var(--wst-primary);">{{ $lot->batch_lot_no ?? 'UNBATCHED' }}</strong>
                            <div style="font-size: 11px; color: var(--wst-text-muted);">{{ $lot->storage_location ?? 'Commissary Chiller' }}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--wst-text-strong);">{{ $lot->item_name }}</div>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 10px;">{{ $lot->sku }}</span>
                        </td>
                        <td>
                            <strong style="font-family: 'Outfit', sans-serif;">{{ number_format($lot->est_quantity ?? 0, 2) }}</strong>
                        </td>
                        <td>
                            <div style="font-weight: 600;">{{ \Carbon\Carbon::parse($lot->expiry_date)->format('M d, Y') }}</div>
                        </td>
                        <td>
                            @if($lot->days_remaining < 0)
                                <strong style="color: var(--wst-danger);">Expired {{ abs($lot->days_remaining) }} days ago</strong>
                            @elseif($lot->days_remaining === 0)
                                <strong style="color: var(--wst-danger);">Expires today!</strong>
                            @else
                                <span style="font-weight: 600;">{{ $lot->days_remaining }} days left</span>
                            @endif
                        </td>
                        <td>
                            <span class="hr-badge {{ $lot->badge_class }}">
                                @if($lot->risk_status === 'EXPIRED')
                                    <i class="ph ph-warning-octagon"></i> Expired
                                @elseif($lot->risk_status === 'CRITICAL')
                                    <i class="ph ph-alarm"></i> Critical (Urgent)
                                @elseif($lot->risk_status === 'WARNING')
                                    <i class="ph ph-hourglass-high"></i> High Risk
                                @else
                                    <i class="ph ph-check-circle"></i> Healthy
                                @endif
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <button type="button" class="hr-btn hr-btn-danger hr-btn-sm" onclick="quickWriteOffLot({{ json_encode($lot) }})">
                                <i class="ph ph-trash"></i> Write Off Loss
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px; color: var(--wst-text-muted);">
                            <i class="ph ph-shield-check" style="font-size: 36px; color: #10b981; display: block; margin-bottom: 8px;"></i>
                            All monitored inventory lots are well within their safety shelf life. No near-expiry items detected.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL: WASTE RECORD DETAIL AUDIT
     ========================================================================= -->
<div class="wst-modal-backdrop" id="wasteDetailModal">
    <div class="wst-modal-box">
        <div class="wst-modal-header">
            <h3 class="wst-modal-title">
                <i class="ph ph-file-text" style="color: var(--wst-primary);"></i>
                <span id="modalWasteNumber">WST-XXXX</span>
            </h3>
            <button type="button" style="background: none; border: none; font-size: 20px; cursor: pointer; color: var(--wst-text-muted);" onclick="closeWasteModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="wst-modal-body">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px;">
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Incident Type:</span>
                    <strong id="modalWasteType">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Date Recorded:</span>
                    <strong id="modalWasteDate">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Reported By:</span>
                    <strong id="modalReportedBy">-</strong>
                </div>
                <div>
                    <span style="color: var(--wst-text-muted); display: block; font-size: 11px;">Disposal Method:</span>
                    <strong id="modalDisposalMethod">-</strong>
                </div>
            </div>

            <h4 style="font-family: 'Outfit', sans-serif; margin: 16px 0 8px 0;">Item Write-Off Breakdown</h4>
            <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                    <thead style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <tr>
                            <th style="padding: 8px 12px; text-align: left;">Item</th>
                            <th style="padding: 8px 12px; text-align: center;">Qty</th>
                            <th style="padding: 8px 12px; text-align: right;">Unit Cost</th>
                            <th style="padding: 8px 12px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTbody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 20px; padding: 14px; background: #f8fafc; border-radius: 10px;">
                <span style="font-weight: 600; color: var(--wst-text-muted); font-size: 11.5px; display: block; margin-bottom: 4px;">Auditor & Investigation Notes:</span>
                <p id="modalNotes" style="margin: 0; color: var(--wst-text-medium); font-size: 12.5px;">-</p>
            </div>
        </div>
        <div class="wst-modal-footer">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="closeWasteModal()">Close</button>
            <button type="button" class="hr-btn hr-btn-danger" id="btnCancelRecord" onclick="cancelWasteRecord()">
                <i class="ph ph-prohibit"></i> Cancel & Reverse Write-Off
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// =========================================================================
// TAB SWITCHING CONTROLLER
// =========================================================================
function switchTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.wst-tab-panel').forEach(panel => panel.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) {
        targetPanel.classList.add('active');
    }

    if (tabId === 'tab-records') document.getElementById('btnTabRecords')?.classList.add('active');
    else if (tabId === 'tab-log-waste') document.getElementById('btnTabLogWaste')?.classList.add('active');
    else if (tabId === 'tab-expiry-watchlist') document.getElementById('btnTabExpiryWatchlist')?.classList.add('active');
}

// =========================================================================
// FORM CONTROLS & DYNAMIC PREVIEWS
// =========================================================================
let currentSelectedProduct = null;

function onSelectItem(productId) {
    const sel = document.getElementById('inputInventoryItem');
    const opt = sel.options[sel.selectedIndex];

    if (!productId || !opt) {
        currentSelectedProduct = null;
        document.getElementById('itemPreviewBox').style.display = 'none';
        calculateLossValuation();
        return;
    }

    currentSelectedProduct = {
        id: productId,
        sku: opt.dataset.sku,
        name: opt.dataset.name,
        category: opt.dataset.category,
        uom: opt.dataset.uom,
        stock: parseFloat(opt.dataset.stock) || 0,
        cost: parseFloat(opt.dataset.cost) || 0,
        location: opt.dataset.location || 'Main Storage'
    };

    document.getElementById('prevStock').textContent = `${currentSelectedProduct.stock.toFixed(2)} ${currentSelectedProduct.uom}`;
    document.getElementById('prevCost').textContent = `₱${currentSelectedProduct.cost.toFixed(2)} / ${currentSelectedProduct.uom}`;
    document.getElementById('prevLocation').textContent = currentSelectedProduct.location;
    document.getElementById('itemPreviewBox').style.display = 'flex';

    calculateLossValuation();
}

function calculateLossValuation() {
    const qty = parseFloat(document.getElementById('inputQuantity').value) || 0;
    const cost = currentSelectedProduct ? currentSelectedProduct.cost : 0;
    const total = qty * cost;
    document.getElementById('inputValuation').value = `₱${total.toFixed(2)}`;
}

function onWasteTypeChange() {
    const type = document.getElementById('inputWasteType').value;
    const reasonSel = document.getElementById('inputReasonCode');

    if (type === 'EXPIRED') {
        reasonSel.value = 'Expired on Shelf';
    } else if (type === 'SPOILAGE') {
        reasonSel.value = 'Mold / Spoilage';
    } else if (type === 'STORAGE_FAILURE') {
        reasonSel.value = 'Cold Chain Temperature Breach';
    } else if (type === 'DAMAGED') {
        reasonSel.value = 'Physical Handling Damage';
    } else if (type === 'DEFECTIVE') {
        reasonSel.value = 'Packaging Defect';
    } else if (type === 'PREP_FALLOUT') {
        reasonSel.value = 'Preparation Fallout';
    }
}

// =========================================================================
// SUBMIT LOG WASTE RECORD
// =========================================================================
async function submitWasteRecord(e) {
    e.preventDefault();

    if (!currentSelectedProduct) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Select Item',
            text: 'You must select an item from the Item Masterlist.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const qty = parseFloat(document.getElementById('inputQuantity').value) || 0;
    if (qty <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Invalid Quantity',
            text: 'Write-off quantity must be greater than zero.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    // Shortage check warning
    let forceOverride = false;
    if (qty > currentSelectedProduct.stock) {
        const confirmResult = await Swal.fire({
            icon: 'warning',
            title: 'Stock Warning',
            html: `Physical stock on hand for <strong>${currentSelectedProduct.name}</strong> is <strong>${currentSelectedProduct.stock.toFixed(2)} ${currentSelectedProduct.uom}</strong>.<br><br>You requested write-off of <strong>${qty.toFixed(2)} ${currentSelectedProduct.uom}</strong>. Do you want to proceed with administrative override?`,
            showCancelButton: true,
            confirmButtonText: 'Yes, Force Write-Off',
            cancelButtonText: 'Cancel & Re-check',
            confirmButtonColor: '#9333ea',
            cancelButtonColor: '#64748b'
        });

        if (!confirmResult.isConfirmed) {
            return;
        }
        forceOverride = true;
    }

    const payload = {
        waste_type: document.getElementById('inputWasteType').value,
        storage_location: currentSelectedProduct.location,
        disposal_method: document.getElementById('inputDisposalMethod').value,
        reported_by: document.getElementById('inputReportedBy').value,
        waste_date: document.getElementById('inputWasteDate').value,
        notes: document.getElementById('inputNotes').value,
        force_override: forceOverride,
        items: [{
            inventory_item_id: currentSelectedProduct.id,
            quantity: qty,
            reason_code: document.getElementById('inputReasonCode').value,
            batch_lot_no: document.getElementById('inputBatchLot').value || null,
            expiry_date: document.getElementById('inputExpiryDate').value || null,
            action_taken: document.getElementById('inputDisposalMethod').value,
            notes: document.getElementById('inputNotes').value || null,
        }]
    };

    try {
        Swal.fire({
            title: 'Logging Waste...',
            text: 'Updating Stock Ledger and deducting on-hand stock...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const res = await fetch("{{ route('inventory.api.create-waste-record') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Waste Record Created',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Failed to record waste entry.',
                confirmButtonColor: '#9333ea'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Server Error',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

// =========================================================================
// QUICK WRITE-OFF FOR EXPIRY WATCHLIST LOT
// =========================================================================
function quickWriteOffLot(lot) {
    switchTab('tab-log-waste');

    // Attempt to pre-select item
    const sel = document.getElementById('inputInventoryItem');
    for (let i = 0; i < sel.options.length; i++) {
        if (sel.options[i].dataset.sku === lot.sku) {
            sel.selectedIndex = i;
            onSelectItem(sel.options[i].value);
            break;
        }
    }

    document.getElementById('inputWasteType').value = 'EXPIRED';
    document.getElementById('inputReasonCode').value = 'Expired on Shelf';
    document.getElementById('inputBatchLot').value = lot.batch_lot_no || '';
    if (lot.expiry_date) {
        document.getElementById('inputExpiryDate').value = lot.expiry_date.split('T')[0];
    }
    if (lot.est_quantity) {
        document.getElementById('inputQuantity').value = parseFloat(lot.est_quantity);
        calculateLossValuation();
    }
}

// =========================================================================
// DETAIL MODAL & AUDIT
// =========================================================================
let activeViewingRecord = null;

function viewWasteDetail(rec) {
    activeViewingRecord = rec;
    document.getElementById('modalWasteNumber').textContent = rec.waste_number;
    document.getElementById('modalWasteType').textContent = rec.waste_type;
    document.getElementById('modalWasteDate').textContent = rec.waste_date;
    document.getElementById('modalReportedBy').textContent = rec.reported_by;
    document.getElementById('modalDisposalMethod').textContent = rec.disposal_method;
    document.getElementById('modalNotes').textContent = rec.notes || 'No specific notes recorded.';

    const tbody = document.getElementById('modalItemsTbody');
    tbody.innerHTML = '';

    if (rec.items && rec.items.length > 0) {
        rec.items.forEach(it => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9;">
                    <strong>${it.item_name}</strong>
                    <div style="font-size: 11px; color: var(--wst-text-muted);">SKU: ${it.sku} &bull; Reason: ${it.reason_code}</div>
                </td>
                <td style="padding: 8px 12px; text-align: center; border-bottom: 1px solid #f1f5f9;">
                    ${parseFloat(it.quantity).toFixed(2)} ${it.uom}
                </td>
                <td style="padding: 8px 12px; text-align: right; border-bottom: 1px solid #f1f5f9;">
                    ₱${parseFloat(it.unit_cost).toFixed(2)}
                </td>
                <td style="padding: 8px 12px; text-align: right; font-weight: 700; color: var(--wst-danger); border-bottom: 1px solid #f1f5f9;">
                    -₱${parseFloat(it.total_cost).toFixed(2)}
                </td>
            `;
            tbody.appendChild(tr);
        });
    }

    const cancelBtn = document.getElementById('btnCancelRecord');
    if (rec.status === 'CANCELLED') {
        cancelBtn.style.display = 'none';
    } else {
        cancelBtn.style.display = 'inline-flex';
    }

    document.getElementById('wasteDetailModal').classList.add('open');
}

function closeWasteModal() {
    document.getElementById('wasteDetailModal').classList.remove('open');
    activeViewingRecord = null;
}

async function cancelWasteRecord() {
    if (!activeViewingRecord) return;

    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'Cancel & Reverse Write-Off?',
        text: `Are you sure you want to cancel ${activeViewingRecord.waste_number}? This will restore the written-off quantities back into physical on-hand stock and log a reversing transaction in Stock Ledger.`,
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel Write-Off',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b'
    });

    if (!confirm.isConfirmed) return;

    try {
        const res = await fetch("{{ route('inventory.api.update-waste-status') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                waste_record_id: activeViewingRecord.id,
                status: 'CANCELLED',
                notes: 'Cancelled by user audit reversal'
            })
        });

        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'Record Cancelled',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
            window.location.reload();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message,
                confirmButtonColor: '#9333ea'
            });
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

// =========================================================================
// TABLE SEARCH & FILTERS
// =========================================================================
function filterWasteTable() {
    const q = document.getElementById('wstSearchInput').value.toLowerCase().trim();
    const type = document.getElementById('wstTypeFilter').value;
    const status = document.getElementById('wstStatusFilter').value;

    const rows = document.querySelectorAll('#wasteLogsTbody tr[data-search]');
    rows.forEach(r => {
        const matchesQ = !q || r.dataset.search.includes(q);
        const matchesType = (type === 'ALL') || (r.dataset.type === type);
        const matchesStatus = (status === 'ALL') || (r.dataset.status === status);

        if (matchesQ && matchesType && matchesStatus) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function resetWasteFilters() {
    document.getElementById('wstSearchInput').value = '';
    document.getElementById('wstTypeFilter').value = 'ALL';
    document.getElementById('wstStatusFilter').value = 'ALL';
    filterWasteTable();
}

function filterWatchlistTable() {
    const q = document.getElementById('watchlistSearchInput').value.toLowerCase().trim();
    const risk = document.getElementById('watchlistRiskFilter').value;

    const rows = document.querySelectorAll('#expiryWatchlistTbody tr[data-search]');
    rows.forEach(r => {
        const matchesQ = !q || r.dataset.search.includes(q);
        const matchesRisk = (risk === 'ALL') || (r.dataset.risk === risk);

        if (matchesQ && matchesRisk) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}
</script>
@endpush
@endsection

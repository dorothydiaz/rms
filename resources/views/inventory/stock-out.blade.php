@extends('layouts.app')

@section('title', 'Stock Out / Usage - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   STOCK OUT / USAGE MODULE - ENTERPRISE HR THEME TOKENS & WORKSPACE
   Adhering to docs/HR_THEME_DESIGN_SYSTEM.md & assets/css/theme-tokens.css
   ========================================================================== */
:root {
    --out-canvas-bg: #faf7fd;
    --out-surface-card: rgba(255, 255, 255, 0.96);
    --out-surface-subtle: #f8fafc;
    --out-surface-hover: rgba(245, 243, 255, 0.65);
    --out-border: rgba(226, 232, 240, 0.9);
    --out-border-focus: #c084fc;

    --out-primary: #9333ea;
    --out-primary-dark: #7c3aed;
    --out-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --out-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);
    --out-primary-glow: rgba(168, 85, 247, 0.28);
    --out-accent-strip: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);

    --out-text-strong: #0f172a;
    --out-text-medium: #334155;
    --out-text-muted: #64748b;
    --out-text-subtle: #94a3b8;

    --out-success: #059669;
    --out-success-bg: rgba(16, 185, 129, 0.12);
    --out-success-border: rgba(16, 185, 129, 0.32);

    --out-warning: #d97706;
    --out-warning-bg: rgba(245, 158, 11, 0.12);
    --out-warning-border: rgba(245, 158, 11, 0.32);

    --out-danger: #dc2626;
    --out-danger-bg: rgba(239, 68, 68, 0.12);
    --out-danger-border: rgba(239, 68, 68, 0.32);

    --out-info: #0284c7;
    --out-info-bg: rgba(14, 165, 233, 0.12);
    --out-info-border: rgba(14, 165, 233, 0.32);

    --out-shadow-card: 0 10px 32px rgba(148, 163, 184, 0.08), 0 2px 8px rgba(0, 0, 0, 0.02);
    --out-shadow-card-hover: 0 14px 32px rgba(168, 85, 247, 0.14), 0 4px 10px rgba(236, 72, 153, 0.08);
}

.out-workspace {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background-color: var(--out-canvas-bg);
    min-height: calc(100vh - 72px);
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--out-text-strong);
}

.out-card-strip {
    position: relative;
    background: var(--out-surface-card);
    border: 1px solid var(--out-border);
    border-radius: 16px;
    box-shadow: var(--out-shadow-card);
    overflow: hidden;
}

.out-card-strip::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--out-accent-strip);
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
.out-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0px;
    flex-wrap: wrap;
}
.hr-parent-title,
.out-page-title-group h1 {
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
.hr-parent-title i,
.out-page-title-group h1 i {
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
.out-page-subtitle {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 400;
    margin: 3px 0 0 0;
}
.hr-page-actions {
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
.tab-badge-pill {
    padding: 2px 8px;
    font-size: 10px;
    font-weight: 700;
    border-radius: 8px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18));
    border: 1px solid rgba(168, 85, 247, 0.28);
    color: #9333ea;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

/* KPI Summary Cards */
.out-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.out-kpi-card {
    background: #ffffff;
    border: 1px solid var(--out-border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--out-shadow-card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.out-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--out-shadow-card-hover);
}

.out-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.out-kpi-content {
    flex: 1;
}

.out-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--out-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.out-kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--out-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.out-kpi-meta {
    font-size: 11.5px;
    color: var(--out-text-muted);
    margin-top: 3px;
}

/* Filter & Search Toolbar */
.out-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    border-bottom: 1px solid #f1f5f9;
}

.out-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 420px;
}

.out-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--out-text-subtle);
    font-size: 16px;
}

.out-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 13px;
    color: var(--out-text-strong);
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.out-search-input:focus {
    border-color: var(--out-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

.out-select {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: var(--out-text-strong);
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.out-select:focus {
    border-color: var(--out-border-focus);
}

/* Enterprise Data Table */
.out-table-wrapper {
    overflow-x: auto;
}

.out-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 12.5px;
}

.out-table th {
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

.out-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--out-text-medium);
    vertical-align: middle;
}

.out-table tr:hover td {
    background-color: var(--out-surface-hover);
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
    background: var(--out-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
}

.hr-btn-primary:hover {
    background: var(--out-primary-gradient-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.4);
}

.hr-btn-secondary {
    background: #ffffff;
    color: var(--out-text-medium);
    border: 1px solid #cbd5e1;
}

.hr-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: var(--out-text-strong);
}

.hr-btn-sm {
    padding: 5px 10px;
    font-size: 11.5px;
    border-radius: 8px;
}

/* Asymmetric Workspace Grid (Modeled like RFQ Builder / Create new PO to received) */
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
    border: 1px solid var(--out-border);
    border-radius: 14px;
    padding: 18px 20px;
    box-shadow: var(--out-shadow-card);
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
    color: var(--out-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
}

.panel-title i {
    color: var(--out-primary);
    font-size: 17px;
}

/* Vendor Pill Profile */
.vendor-pill-box {
    background: linear-gradient(135deg, #fdf4ff 0%, #fae8ff 100%);
    border: 1px solid #f0abfc;
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    flex-direction: column;
    gap: 6px;
    font-size: 12px;
}

/* Real-Time Progress Bar on Line Items */
.line-progress-container {
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 140px;
}

.line-progress-track {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 999px;
    overflow: hidden;
    position: relative;
}

.line-progress-fill {
    height: 100%;
    border-radius: 999px;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), background-color 0.3s ease;
}

.line-progress-fill.fill-gray {
    background: #cbd5e1;
}

.line-progress-fill.fill-partial {
    background: linear-gradient(90deg, #38bdf8 0%, #818cf8 100%);
}

.line-progress-fill.fill-matched {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
    box-shadow: 0 0 8px rgba(16, 185, 129, 0.4);
}

.line-progress-fill.fill-exceeds {
    background: repeating-linear-gradient(45deg, #f59e0b, #f59e0b 8px, #d97706 8px, #d97706 16px);
    box-shadow: 0 0 8px rgba(245, 158, 11, 0.4);
}

.line-progress-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11px;
    font-weight: 600;
}

/* Pack Quantity Input */
.pack-qty-input-group {
    display: flex;
    align-items: center;
    gap: 4px;
}

.pack-qty-input {
    width: 72px;
    padding: 6px 8px;
    border-radius: 8px;
    border: 1.5px solid #cbd5e1;
    font-size: 13px;
    font-weight: 700;
    text-align: right;
    font-variant-numeric: tabular-nums;
    outline: none;
    transition: all 0.2s;
}

.pack-qty-input:focus {
    border-color: var(--out-primary);
    box-shadow: 0 0 0 3px var(--out-primary-glow);
}

.pack-quick-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    border: 1px solid #cbd5e1;
    background: #f8fafc;
    color: #475569;
    font-size: 11px;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.pack-quick-btn:hover {
    background: #ede9fe;
    color: var(--out-primary);
    border-color: #c4b5fd;
}

/* Tab Panels */
.out-tab-panel {
    display: none;
    animation: fadeIn 0.2s ease-in-out;
}

.out-tab-panel.active {
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
<style>
/* Compact Spacing Overrides (Employees Directory parity) */
.out-workspace {
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
.out-tab-panel.active { gap: 8px !important; }
.out-filter-bar { padding: 8px 12px !important; gap: 8px !important; }
</style>

<div class="out-workspace">

    <!-- =====================================================================
         PAGE HEADER
         ===================================================================== -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row out-page-header">
            <div class="out-page-title-group">
                <h1 class="hr-parent-title">
                    <i class="ph ph-arrow-up-right"></i>
                    <span>Stock Out / Usage</span>
                </h1>
                <p class="hr-parent-subtitle out-page-subtitle">Manage kitchen requisitions, inter-branch transfers, vendor returns (RMA), and live stock-out fulfillment with physical pick & pack verification.</p>
            </div>
            <div class="hr-page-actions">
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Current Stock Levels">
                    <i class="ph ph-squares-four"></i> Stocks Overview
                </a>
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master">
                    <i class="ph ph-folder-simple"></i> Item Master
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchOutTab('tab-create-draft')">
                    <i class="ph ph-plus-circle"></i> New Requisition Draft
                </button>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         PRIMARY TABS NAVIGATION
         1. List of Dispatches  2. Create Draft Requisition  3. Pick Pack Ship
         ===================================================================== -->
    <nav class="hr-tabs-wrapper tabs-wrapper" id="stockOutTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnList" onclick="switchOutTab('tab-out-list')">
            <i class="ph ph-list-dashes"></i>
            <span>List of Dispatches</span>
            <span class="tab-count" id="badgeListCount">{{ $initialOrders->count() }}</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCreateDraft" onclick="switchOutTab('tab-create-draft')">
            <i class="ph ph-file-plus"></i>
            <span>Create Draft Requisition</span>
            <span class="tab-badge-pill">New Requisition</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPickPackShip" onclick="switchOutTab('tab-pick-pack-ship')">
            <i class="ph ph-package"></i>
            <span>Pick Pack Ship</span>
            <span class="tab-badge-pill" id="badgePendingPickCount" style="background: #fef3c7; color: #b45309; border-color: #fde68a;">
                {{ $initialOrders->whereIn('status', ['DRAFT', 'PICKING', 'PACKED'])->count() }} Active
            </span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: LIST OF DISPATCHES (Master Register)
         ===================================================================== -->
    <div id="tab-out-list" class="out-tab-panel active">
        <!-- Filter & Search Toolbar Strip -->
        <div class="out-card-strip" style="margin-top: 16px;">
            <div class="out-filter-bar">
                <div class="out-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="orderSearchInput" class="out-search-input" placeholder="Search orders by SO #, Requester, Destination, or SKU... (/)" oninput="filterOrdersList()">
                </div>

                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="statusFilter" class="out-select" onchange="filterOrdersList()">
                        <option value="ALL">All Statuses</option>
                        <option value="DRAFT">Draft Requisitions</option>
                        <option value="PICKING">In Picking</option>
                        <option value="PACKED">Packed & Ready</option>
                        <option value="SHIPPED">Dispatched / Shipped</option>
                        <option value="CANCELLED">Cancelled</option>
                    </select>

                    <select id="typeFilter" class="out-select" onchange="filterOrdersList()">
                        <option value="ALL">All Usage Types</option>
                        <option value="KITCHEN_USAGE">Kitchen Operations Usage</option>
                        <option value="BRANCH_TRANSFER">Inter-Branch Transfer</option>
                        <option value="VENDOR_RETURN">Vendor Return (RMA)</option>
                        <option value="SPOILAGE_DISPOSAL">Spoilage / Disposal</option>
                        <option value="STAFF_MEALS">Staff Meals</option>
                        <option value="SAMPLE_TASTING">Sample Tasting</option>
                    </select>

                    <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveStockOutData()" title="Refresh Data">
                        <i class="ph ph-arrows-clockwise"></i> Refresh
                    </button>
                </div>
            </div>

            <!-- Master Table -->
            <div class="out-table-wrapper">
                <table class="out-table" id="ordersMasterTable">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Usage Purpose</th>
                            <th>Destination / Recipient</th>
                            <th>Requester & Dept</th>
                            <th>Items & Qty</th>
                            <th style="min-width: 150px;">Fulfillment Progress</th>
                            <th style="text-align: right;">Valuation (₱)</th>
                            <th style="text-align: center;">Status</th>
                            <th style="text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>

            <div id="noOrdersPrompt" style="display: none; padding: 40px; text-align: center; color: var(--out-text-muted);">
                <i class="ph ph-tray" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                No stock-out dispatches match your filter criteria.
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: CREATION OF DRAFT (Asymmetric Workspace like "Create new PO to received")
         ===================================================================== -->
    <div id="tab-create-draft" class="out-tab-panel">
        <div class="out-card-strip" style="padding: 22px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--out-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-sliders-horizontal"></i> Direct Outbound Requisition Workspace
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetDraftForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="saveDraftOrder(false)">
                        <i class="ph ph-floppy-disk"></i> Save as Draft
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="saveDraftOrder(true)">
                        <i class="ph ph-arrow-right"></i> Approve & Move to Pick Pack Ship (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.2fr): Outbound Identity, Destination & Logistics -->
                <div class="panel-col">

                    <!-- Card 1: Requisition Reference & Usage Code -->
                    <div class="panel-card" style="border-left: 4px solid var(--out-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Stock Out Reference</div>
                                <div style="font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--out-primary);" id="draftRefDisplay">SO-{{ date('Y') }}-AUTO</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Stock Out</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Stock Out / Usage Reason Code *</label>
                            <select id="draftOrderType" class="out-select" style="font-size: 12.5px; padding: 8px 10px;" onchange="handleOrderTypeChange(this.value)">
                                <option value="KITCHEN_USAGE">Kitchen Operations Line Usage / Daily Production</option>
                                <option value="BRANCH_TRANSFER">Inter-Branch Transfer Out</option>
                                <option value="VENDOR_RETURN">Vendor Return / Supplier RMA (Connect to Vendors)</option>
                                <option value="SPOILAGE_DISPOSAL">Spoilage / Damaged Goods Disposal</option>
                                <option value="STAFF_MEALS">Staff Meals & Banquet Consumables</option>
                                <option value="SAMPLE_TASTING">Sample Tasting & Marketing Demo</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Destination & Partner Selection -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-map-pin"></i>
                                <span>Destination & Partner Profile</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="partnerTypeBadge">Internal Station</span>
                        </div>

                        <!-- Dynamic Section A: Vendor Return (Connected to Procurement Vendors) -->
                        <div id="vendorReturnBlock" style="display: none; flex-direction: column; gap: 8px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Select Registered Vendor from Masterlist *</label>
                            <select id="draftVendorSelect" class="out-select" onchange="handleVendorSelect(this.value)">
                                <option value="">-- Choose Vendor from Vendor Masterlist --</option>
                                @foreach($initialVendors as $v)
                                    <option value="{{ $v->id }}" data-name="{{ $v->legal_name }}" data-trade="{{ $v->trade_name }}" data-contact="{{ $v->contact_person }}" data-phone="{{ $v->phone }}" data-email="{{ $v->email }}" data-address="{{ $v->address }}">
                                        {{ $v->vendor_code }} - {{ $v->legal_name }} ({{ $v->category }})
                                    </option>
                                @endforeach
                            </select>

                            <div class="vendor-pill-box" id="vendorPillBox" style="display: none;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <strong id="vPillName" style="color: #701a75; font-size: 13px;">Vendor Name</strong>
                                    <span class="hr-badge hr-badge-purple" id="vPillTrade">Trade Name</span>
                                </div>
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 4px; color: #475569;">
                                    <div><i class="ph ph-user"></i> <span id="vPillContact">-</span></div>
                                    <div><i class="ph ph-phone"></i> <span id="vPillPhone">-</span></div>
                                </div>
                                <div style="color: #64748b; font-size: 11px;"><i class="ph ph-map-pin"></i> <span id="vPillAddress">-</span></div>
                            </div>
                        </div>

                        <!-- Dynamic Section B: Branch Transfer Destination -->
                        <div id="branchTransferBlock" style="display: none; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Target Destination Branch *</label>
                            <select id="draftBranchSelect" class="out-select">
                                <option value="Branch 1 - Makati Bistro Kitchen">Branch 1 - Makati Bistro Kitchen</option>
                                <option value="Branch 2 - BGC Bistro & Dining">Branch 2 - BGC Bistro & Dining</option>
                                <option value="Branch 3 - Ortigas Commissary Hub">Branch 3 - Ortigas Commissary Hub</option>
                                <option value="Alabang Satellite Kiosk">Alabang Satellite Kiosk</option>
                            </select>
                        </div>

                        <!-- Dynamic Section C: Kitchen Line Destination -->
                        <div id="kitchenLineBlock" style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Kitchen Prep Station / Department *</label>
                            <select id="draftKitchenStation" class="out-select">
                                <option value="Main Kitchen - Hot Line (Saute & Grill)">Main Kitchen - Hot Line (Saute & Grill)</option>
                                <option value="Cold Kitchen & Salad Pantry">Cold Kitchen & Salad Pantry</option>
                                <option value="Bakery & Pastry Production">Bakery & Pastry Production</option>
                                <option value="Bar & Craft Beverage Line">Bar & Craft Beverage Line</option>
                                <option value="Central Commissary Prep Dock">Central Commissary Prep Dock</option>
                            </select>
                        </div>

                        <!-- Dynamic Section D: Spoilage / Disposal Destination -->
                        <div id="disposalBlock" style="display: none; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Disposal Holding Vault / Protocol</label>
                            <select id="draftDisposalVault" class="out-select">
                                <option value="Condemned Meat & Perishables Freezer">Condemned Meat & Perishables Freezer</option>
                                <option value="Kitchen Organic Compost Bin">Kitchen Organic Compost Bin</option>
                                <option value="Hazardous Waste Sanitation Vault">Hazardous Waste Sanitation Vault</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 3: Logistics, Requester & Priority Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-identification-badge"></i>
                                <span>Requisition Schedule & Requester</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Requested By (Chef/Lead) *</label>
                                <input type="text" id="draftRequestedBy" class="out-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Executive Chef' }}">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Priority Level</label>
                                <select id="draftPriority" class="out-select">
                                    <option value="NORMAL">Normal Priority</option>
                                    <option value="URGENT">Urgent (Dinner Rush)</option>
                                    <option value="EMERGENCY">Emergency Expedited Run</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Requisition / Ticket #</label>
                                <input type="text" id="draftReferenceNo" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. REQ-KT-8021">
                            </div>

                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Required Date & Time</label>
                                <input type="datetime-local" id="draftRequiredAt" class="out-search-input" style="padding-left: 10px;" value="{{ date('Y-m-d\TH:i') }}">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Special Handling / Kitchen Notes</label>
                            <textarea id="draftNotes" class="out-search-input" style="padding: 8px 10px; height: 58px; resize: vertical;" placeholder="e.g., Keep refrigerated at -2°C, prioritize primal cut selection..."></textarea>
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Item Master Quick-Add, Line Items Grid & Valuation -->
                <div class="panel-col">

                    <!-- Card 4: Items Panel & Product Catalog Quick-Add -->
                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-shopping-cart"></i>
                                <span>Requisition Line Items & Inventory Balance</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="draftItemCountBadge">0 Items Added</span>
                        </div>

                        <!-- Catalog Quick-Add Bar (Connected to Item Master & Stocks Overview) -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="draftCatalogPicker" class="out-select" style="width: 100%;">
                                    <option value="">-- Quick Pick from Product Catalog (Item Master) --</option>
                                    @foreach($initialProducts as $p)
                                        <option value="{{ $p->sku }}" data-id="{{ $p->id }}" data-name="{{ $p->name }}" data-category="{{ $p->category }}" data-uom="{{ $p->uom }}" data-cost="{{ $p->cost_price }}" data-stock="{{ $p->current_stock }}" data-location="{{ $p->storage_location }}">
                                            [{{ $p->sku }}] {{ $p->name }} — Available: {{ number_format($p->current_stock, 2) }} {{ $p->uom }} (₱{{ number_format($p->cost_price, 2) }}/{{ $p->uom }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="number" id="draftQuickQty" class="out-search-input" style="width: 80px; padding-left: 10px;" placeholder="Qty" min="0.01" step="0.01" value="1.00">
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addPickedCatalogItem()">
                                <i class="ph ph-plus"></i> Add Item
                            </button>
                        </div>

                        <!-- Items Grid Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="out-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>UOM</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center; width: 100px;">Req Qty *</th>
                                        <th style="text-align: right;">Unit Cost (₱)</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="draftItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="draftNoItemsPrompt" style="padding: 28px; text-align: center; color: var(--out-text-muted); font-size: 13px;">
                            <i class="ph ph-basket" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Select a product from the Item Master catalog above to add requisition lines.
                        </div>

                        <!-- Valuation Summary Strip -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Total Requisitioned Units: <strong id="draftTotalUnitsDisplay" style="color: #0f172a;">0.00</strong>
                            </div>
                            <div style="font-size: 14px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <span>Estimated Valuation:</span>
                                <span style="font-family: 'Outfit', sans-serif; font-size: 1.25rem; color: var(--out-primary);" id="draftTotalValueDisplay">₱0.00</span>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: PICK PACK SHIP (Execution Workspace with Pack Qty & Line Progress Bar)
         ===================================================================== -->
    <div id="tab-pick-pack-ship" class="out-tab-panel">
        <div class="out-card-strip" style="padding: 22px;">

            <!-- Order Fulfillment Selector Strip -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <span style="font-size: 12px; font-weight: 800; color: var(--out-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-package"></i> Pick Pack Ship Engine
                    </span>

                    <div style="display: flex; align-items: center; gap: 6px;">
                        <label style="font-size: 12px; font-weight: 600; color: #475569;">Select Order to Fulfill:</label>
                        <select id="pickOrderSelect" class="out-select" style="min-width: 280px;" onchange="loadOrderIntoPickPack(this.value)">
                            <option value="">-- Choose Active Dispatch Order --</option>
                            @foreach($initialOrders->whereIn('status', ['DRAFT', 'PICKING', 'PACKED']) as $ord)
                                <option value="{{ $ord->id }}">
                                    {{ $ord->order_number }} [{{ $ord->status }}] - {{ $ord->destination_location }} ({{ $ord->total_items_count }} items)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="autoMatchAllPackQuantities()">
                        <i class="ph ph-check-square-offset"></i> Auto-Fill Matched (100%)
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="clearAllPackQuantities()">
                        <i class="ph ph-x-circle"></i> Clear Packed
                    </button>
                    <button type="button" class="hr-btn hr-btn-secondary" onclick="savePickPackProgress()">
                        <i class="ph ph-floppy-disk"></i> Save Progress
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="confirmShipOrder()">
                        <i class="ph ph-truck"></i> ✓ Confirm Ship & Deduct Stock (F10)
                    </button>
                </div>
            </div>

            <!-- Asymmetric Grid -->
            <div class="workspace-asymmetric-grid">

                <!-- Left Column (4.0fr): Dispatch Logistics & Shipping Details -->
                <div class="panel-col">

                    <!-- Active Order Summary Card -->
                    <div class="panel-card" style="border-left: 4px solid var(--out-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Active Dispatch</div>
                                <div style="font-family: monospace; font-size: 1.3rem; font-weight: 800; color: var(--out-primary);" id="pickActiveOrderNum">NO ORDER SELECTED</div>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="pickActiveStatusBadge">Awaiting Selection</span>
                        </div>

                        <div style="font-size: 12px; color: #475569; display: flex; flex-direction: column; gap: 4px; margin-top: 4px;">
                            <div><strong>Destination:</strong> <span id="pickActiveDestDisplay">-</span></div>
                            <div><strong>Requisitioner:</strong> <span id="pickActiveReqDisplay">-</span></div>
                            <div><strong>Usage Code:</strong> <span id="pickActiveTypeDisplay">-</span></div>
                        </div>
                    </div>

                    <!-- Dispatch Shipping Logistics Details -->
                    <div class="panel-card">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-truck"></i>
                                <span>Logistics & Custody Transfer</span>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Warehouse Picker / Verifier Name *</label>
                            <input type="text" id="pickPickerName" class="out-search-input" style="padding-left: 10px;" value="{{ auth()->user()->full_name ?? auth()->user()->username ?? 'Warehouse Picker 1' }}">
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Carrier / Vehicle Plate</label>
                                <input type="text" id="pickCarrierName" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. Van B (NDF 8812)">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Shipping Waybill / Slip #</label>
                                <input type="text" id="pickWaybill" class="out-search-input" style="padding-left: 10px;" placeholder="e.g. WB-2026-9041">
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 4px;">
                            <label style="font-size: 11px; font-weight: 600; color: #475569;">Dispatch Timestamp</label>
                            <input type="datetime-local" id="pickDispatchedAt" class="out-search-input" style="padding-left: 10px;" value="{{ date('Y-m-d\TH:i') }}">
                        </div>
                    </div>

                    <!-- Overall Fulfillment Progress Gauge Card -->
                    <div class="panel-card" style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-chart-donut"></i>
                                <span>Overall Order Fulfillment</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="pickGaugeBadge">0% Packed</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            <div class="line-progress-track" style="height: 12px;">
                                <div class="line-progress-fill fill-partial" id="pickOverallProgressBar" style="width: 0%;"></div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px; color: #475569;">
                                <div style="background: #ffffff; padding: 8px 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 10.5px; color: #64748b;">Total Requested</div>
                                    <strong style="font-size: 14px; color: #0f172a;" id="pickTotalReqDisplay">0.00</strong>
                                </div>
                                <div style="background: #ffffff; padding: 8px 10px; border-radius: 8px; border: 1px solid #e2e8f0;">
                                    <div style="font-size: 10.5px; color: #64748b;">Total Packed</div>
                                    <strong style="font-size: 14px; color: var(--out-primary);" id="pickTotalPackedDisplay">0.00</strong>
                                </div>
                            </div>

                            <div id="pickFulfillmentAlert" style="font-size: 11.5px; padding: 8px 12px; border-radius: 8px; background: #faf5ff; color: var(--out-primary); border: 1px solid #f3e8ff;">
                                <i class="ph ph-info"></i> Pack physical quantities to verify against requested amounts before confirming dispatch.
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (6.0fr): Physical Pick & Pack Verification Table -->
                <div class="panel-col">

                    <div class="panel-card" style="flex: 1;">
                        <div class="panel-header">
                            <div class="panel-title">
                                <i class="ph ph-check-circle"></i>
                                <span>Line Items Verification & Progress</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral" id="pickItemsCountBadge">0 Items</span>
                        </div>

                        <!-- Pick & Pack Table with NEW Pack Qty Column and Line Progress Bar -->
                        <div style="overflow-x: auto; max-height: 480px; overflow-y: auto;">
                            <table class="out-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th style="text-align: right;">Stock on Hand</th>
                                        <th style="text-align: center;">Req Qty</th>
                                        <th style="text-align: center; width: 140px;">Pack Qty *</th>
                                        <th style="min-width: 170px;">Progress & Status</th>
                                        <th>Lot / Batch #</th>
                                        <th style="text-align: right;">Value (₱)</th>
                                        <th style="text-align: center;">Match</th>
                                    </tr>
                                </thead>
                                <tbody id="pickItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="pickNoItemsPrompt" style="padding: 36px; text-align: center; color: var(--out-text-muted); font-size: 13px;">
                            <i class="ph ph-clipboard-text" style="font-size: 36px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No order selected. Choose an active order from the top dropdown to load the pick, pack & ship workspace.
                        </div>

                        <!-- Footer Actions -->
                        <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                            <div style="font-size: 12px; color: #64748b;">
                                Dispatch Valuation: <strong id="pickTotalValueDisplay" style="color: var(--out-primary); font-size: 1.15rem;">₱0.00</strong>
                            </div>
                            <button type="button" class="hr-btn hr-btn-primary" onclick="confirmShipOrder()">
                                <i class="ph ph-check-fat"></i> Confirm Ship & Deduct Stock
                            </button>
                        </div>
                    </div>

                </div>

            </div>

        </div>
    </div>

</div>

<!-- =====================================================================
     VIEW ORDER DETAILS & PACKING SLIP MODAL
     ===================================================================== -->
<div class="rms-modal-overlay" id="orderDetailModal">
    <div class="rms-modal-card" style="padding: 24px;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-receipt" style="font-size: 24px; color: var(--out-primary);"></i>
                <div>
                    <h3 style="margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a;" id="modalOrderNum">SO-2026-0001</h3>
                    <div style="font-size: 11.5px; color: #64748b;" id="modalOrderDate">Dispatched at -</div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="printPackingSlip()">
                    <i class="ph ph-printer"></i> Print Slip
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeOrderDetailModal()">
                    <i class="ph ph-x"></i> Close
                </button>
            </div>
        </div>

        <div id="modalPrintArea">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; background: #f8fafc; padding: 14px; border-radius: 12px; margin-bottom: 16px; font-size: 12px;">
                <div>
                    <div><strong>Destination / Station:</strong> <span id="modalDest">-</span></div>
                    <div><strong>Usage Purpose:</strong> <span id="modalType">-</span></div>
                    <div><strong>Requester:</strong> <span id="modalReq">-</span></div>
                </div>
                <div>
                    <div><strong>Warehouse Picker:</strong> <span id="modalPicker">-</span></div>
                    <div><strong>Carrier / Driver:</strong> <span id="modalCarrier">-</span></div>
                    <div><strong>Tracking / Waybill:</strong> <span id="modalWaybill">-</span></div>
                </div>
            </div>

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="out-table" style="font-size: 12px;">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Item Name</th>
                            <th>Category</th>
                            <th>UOM</th>
                            <th style="text-align: right;">Requested</th>
                            <th style="text-align: right;">Packed / Shipped</th>
                            <th style="text-align: right;">Unit Cost (₱)</th>
                            <th style="text-align: right;">Total Cost (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="modalItemsTableBody">
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 20px;">
                Total Dispatched Valuation: <span id="modalTotalValDisplay" style="color: var(--out-primary); margin-left: 8px;">₱0.00</span>
            </div>

            <!-- Signatures Strip for Print -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; padding-top: 16px; border-top: 1px dashed #cbd5e1; font-size: 11px; text-align: center;">
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Requisitioner Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Warehouse Picker Signature</strong></div>
                </div>
                <div>
                    <div style="height: 35px; border-bottom: 1px solid #94a3b8; margin-bottom: 4px;"></div>
                    <div><strong>Recipient Acknowledged</strong></div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
/* ==========================================================================
   STOCK OUT / USAGE APPLICATION SCRIPT
   Seamlessly connected to Stocks Overview, Item Master, and Vendors
   ========================================================================== */

// Hydrated Global State
let rawOrders = @json($initialOrders);
let rawProducts = @json($initialProducts);
let rawVendors = @json($initialVendors);
let rawCategories = @json($initialCategories);

// Draft Builder State
let draftItems = [];

// Pick Pack Ship State
let activePickOrder = null;

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    renderOrdersList();
    renderDraftTable();
    setupKeyboardShortcuts();
});

/* --------------------------------------------------------------------------
   TAB NAVIGATION
   -------------------------------------------------------------------------- */
function switchOutTab(tabId) {
    document.querySelectorAll('.out-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const targetPanel = document.getElementById(tabId);
    if (targetPanel) targetPanel.classList.add('active');

    if (tabId === 'tab-out-list') document.getElementById('tabBtnList').classList.add('active');
    if (tabId === 'tab-create-draft') document.getElementById('tabBtnCreateDraft').classList.add('active');
    if (tabId === 'tab-pick-pack-ship') document.getElementById('tabBtnPickPackShip').classList.add('active');

    // Scroll window smoothly to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* --------------------------------------------------------------------------
   TAB 1: LIST OF DISPATCHES
   -------------------------------------------------------------------------- */
function renderOrdersList() {
    const tbody = document.getElementById('ordersTableBody');
    const searchVal = (document.getElementById('orderSearchInput')?.value || '').toLowerCase().trim();
    const statusVal = document.getElementById('statusFilter')?.value || 'ALL';
    const typeVal = document.getElementById('typeFilter')?.value || 'ALL';

    const filtered = rawOrders.filter(ord => {
        if (statusVal !== 'ALL' && ord.status !== statusVal) return false;
        if (typeVal !== 'ALL' && ord.order_type !== typeVal) return false;
        if (searchVal) {
            const num = (ord.order_number || '').toLowerCase();
            const req = (ord.requested_by || '').toLowerCase();
            const dest = (ord.destination_location || '').toLowerCase();
            const hasSku = (ord.items || []).some(it => (it.sku || '').toLowerCase().includes(searchVal) || (it.item_name || '').toLowerCase().includes(searchVal));
            if (!num.includes(searchVal) && !req.includes(searchVal) && !dest.includes(searchVal) && !hasSku) {
                return false;
            }
        }
        return true;
    });

    // Update KPI counters
    updateKpis();

    const noPrompt = document.getElementById('noOrdersPrompt');
    if (filtered.length === 0) {
        tbody.innerHTML = '';
        noPrompt.style.display = 'block';
        return;
    }
    noPrompt.style.display = 'none';

    let html = '';
    filtered.forEach(ord => {
        const reqQty = parseFloat(ord.total_requested_qty) || 0;
        const packQty = parseFloat(ord.total_packed_qty) || 0;
        const pct = reqQty > 0 ? Math.min(100, Math.round((packQty / reqQty) * 100)) : 0;

        let statusBadge = '<span class="hr-badge hr-badge-neutral">Draft</span>';
        if (ord.status === 'PICKING') statusBadge = '<span class="hr-badge hr-badge-blue"><i class="ph ph-arrows-clockwise"></i> Picking</span>';
        if (ord.status === 'PACKED') statusBadge = '<span class="hr-badge hr-badge-amber"><i class="ph ph-package"></i> Packed</span>';
        if (ord.status === 'SHIPPED') statusBadge = '<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Shipped</span>';
        if (ord.status === 'CANCELLED') statusBadge = '<span class="hr-badge hr-badge-red"><i class="ph ph-x"></i> Cancelled</span>';

        let typeBadge = `<span class="hr-badge hr-badge-purple">${formatOrderType(ord.order_type)}</span>`;
        let destIcon = 'ph-map-pin';
        if (ord.order_type === 'VENDOR_RETURN') destIcon = 'ph-buildings';
        if (ord.order_type === 'BRANCH_TRANSFER') destIcon = 'ph-share-network';

        html += `
            <tr>
                <td>
                    <strong style="color: var(--out-primary); cursor: pointer;" onclick="openOrderDetailModal(${ord.id})">
                        ${ord.order_number}
                    </strong>
                    <div style="font-size: 11px; color: #94a3b8;">${formatDate(ord.created_at)}</div>
                </td>
                <td>${typeBadge}</td>
                <td>
                    <div style="display: flex; align-items: center; gap: 6px; font-weight: 600; color: #1e293b;">
                        <i class="ph ${destIcon}" style="color: var(--out-primary);"></i>
                        <span>${escapeHtml(ord.destination_location || 'Main Kitchen')}</span>
                    </div>
                    ${ord.vendor_name ? `<div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.vendor_name)}</div>` : ''}
                </td>
                <td>
                    <strong>${escapeHtml(ord.requested_by || 'Chef')}</strong>
                    <div style="font-size: 11px; color: #64748b;">${escapeHtml(ord.department || 'Kitchen Operations')}</div>
                </td>
                <td>
                    <div><strong>${ord.total_items_count || 0}</strong> lines</div>
                    <div style="font-size: 11px; color: #64748b;">Req: ${reqQty.toFixed(2)}</div>
                </td>
                <td>
                    <div class="line-progress-container">
                        <div class="line-progress-track">
                            <div class="line-progress-fill ${pct >= 100 ? 'fill-matched' : (pct > 0 ? 'fill-partial' : 'fill-gray')}" style="width: ${pct}%;"></div>
                        </div>
                        <div class="line-progress-meta">
                            <span style="color: #64748b;">${packQty.toFixed(1)} / ${reqQty.toFixed(1)}</span>
                            <span style="color: ${pct >= 100 ? '#059669' : 'var(--out-primary)'};">${pct}%</span>
                        </div>
                    </div>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${parseFloat(ord.total_cost_value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">${statusBadge}</td>
                <td style="text-align: center;">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                        ${ord.status !== 'SHIPPED' && ord.status !== 'CANCELLED' ? `
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="jumpToPickPack(${ord.id})" title="Pick & Pack this order">
                                <i class="ph ph-package"></i> Pick
                            </button>
                        ` : ''}
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openOrderDetailModal(${ord.id})" title="View Details">
                            <i class="ph ph-eye"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function filterOrdersList() {
    renderOrdersList();
}

function updateKpis() {
    const shipped = rawOrders.filter(o => o.status === 'SHIPPED');
    const drafts = rawOrders.filter(o => o.status === 'DRAFT');
    const picking = rawOrders.filter(o => o.status === 'PICKING' || o.status === 'PACKED');
    const val = shipped.reduce((acc, o) => acc + (parseFloat(o.total_cost_value) || 0), 0);

    const elShipped = document.getElementById('kpiShippedCount');
    if (elShipped) elShipped.textContent = shipped.length;

    const elDraft = document.getElementById('kpiDraftCount');
    if (elDraft) elDraft.textContent = drafts.length;

    const elPicking = document.getElementById('kpiPickingCount');
    if (elPicking) elPicking.textContent = picking.length;

    const elVal = document.getElementById('kpiValuation');
    if (elVal) elVal.textContent = '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const badgePending = document.getElementById('badgePendingPickCount');
    if (badgePending) badgePending.textContent = (drafts.length + picking.length) + ' Active';

    const badgeList = document.getElementById('badgeListCount');
    if (badgeList) badgeList.textContent = rawOrders.length;
}

/* --------------------------------------------------------------------------
   TAB 2: CREATION OF DRAFT (Asymmetric Builder Layout)
   -------------------------------------------------------------------------- */
function handleOrderTypeChange(val) {
    const vBlock = document.getElementById('vendorReturnBlock');
    const bBlock = document.getElementById('branchTransferBlock');
    const kBlock = document.getElementById('kitchenLineBlock');
    const dBlock = document.getElementById('disposalBlock');
    const pBadge = document.getElementById('partnerTypeBadge');

    vBlock.style.display = 'none';
    bBlock.style.display = 'none';
    kBlock.style.display = 'none';
    dBlock.style.display = 'none';

    if (val === 'VENDOR_RETURN') {
        vBlock.style.display = 'flex';
        pBadge.textContent = 'Vendor Masterlist';
        pBadge.className = 'hr-badge hr-badge-purple';
    } else if (val === 'BRANCH_TRANSFER') {
        bBlock.style.display = 'flex';
        pBadge.textContent = 'Branch Destination';
        pBadge.className = 'hr-badge hr-badge-blue';
    } else if (val === 'SPOILAGE_DISPOSAL') {
        dBlock.style.display = 'flex';
        pBadge.textContent = 'Waste & Disposal Vault';
        pBadge.className = 'hr-badge hr-badge-amber';
    } else {
        kBlock.style.display = 'flex';
        pBadge.textContent = 'Internal Station';
        pBadge.className = 'hr-badge hr-badge-neutral';
    }
}

function handleVendorSelect(vendorId) {
    const pill = document.getElementById('vendorPillBox');
    if (!vendorId) {
        pill.style.display = 'none';
        return;
    }

    const sel = document.getElementById('draftVendorSelect');
    const opt = sel.options[sel.selectedIndex];
    if (opt) {
        document.getElementById('vPillName').textContent = opt.dataset.name || 'Vendor';
        document.getElementById('vPillTrade').textContent = opt.dataset.trade || 'Commercial Partner';
        document.getElementById('vPillContact').textContent = opt.dataset.contact || 'No Contact';
        document.getElementById('vPillPhone').textContent = opt.dataset.phone || '-';
        document.getElementById('vPillAddress').textContent = opt.dataset.address || 'Standard Warehouse Receiving Dock';
        pill.style.display = 'flex';
    }
}

function addPickedCatalogItem() {
    const picker = document.getElementById('draftCatalogPicker');
    const sku = picker.value;
    if (!sku) {
        Swal.fire({
            icon: 'warning',
            title: 'Please Select Product',
            text: 'Choose an item from the Item Master catalog before clicking Add Item.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const opt = picker.options[picker.selectedIndex];
    const qtyInput = document.getElementById('draftQuickQty');
    const qty = parseFloat(qtyInput.value) || 1.0;

    if (qty <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Invalid Quantity',
            text: 'Requested quantity must be greater than zero.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    // Check if item already exists in draft lines
    const existing = draftItems.find(it => it.sku === sku);
    if (existing) {
        existing.requested_qty += qty;
        existing.subtotal = existing.requested_qty * existing.unit_cost;
    } else {
        const itemObj = {
            sku: sku,
            inventory_item_id: parseInt(opt.dataset.id) || null,
            item_name: opt.dataset.name,
            category: opt.dataset.category || 'Raw Ingredients',
            uom: opt.dataset.uom || 'Unit',
            unit_cost: parseFloat(opt.dataset.cost) || 0.00,
            available_stock: parseFloat(opt.dataset.stock) || 0.00,
            storage_location: opt.dataset.location || 'Main Storage',
            requested_qty: qty,
            subtotal: qty * (parseFloat(opt.dataset.cost) || 0.00)
        };
        draftItems.push(itemObj);
    }

    // Reset picker
    picker.value = '';
    qtyInput.value = '1.00';

    renderDraftTable();
}

function renderDraftTable() {
    const tbody = document.getElementById('draftItemsTableBody');
    const prompt = document.getElementById('draftNoItemsPrompt');
    const countBadge = document.getElementById('draftItemCountBadge');

    countBadge.textContent = draftItems.length + ' Items Added';

    if (draftItems.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        document.getElementById('draftTotalUnitsDisplay').textContent = '0.00';
        document.getElementById('draftTotalValueDisplay').textContent = '₱0.00';
        return;
    }
    prompt.style.display = 'none';

    let html = '';
    let totalUnits = 0;
    let totalValue = 0;

    draftItems.forEach((it, idx) => {
        totalUnits += it.requested_qty;
        const lineTotal = it.requested_qty * it.unit_cost;
        totalValue += lineTotal;

        const isLow = it.requested_qty > it.available_stock;

        html += `
            <tr>
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--out-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-map-pin"></i> ${escapeHtml(it.storage_location)}</div>
                </td>
                <td>
                    <span class="hr-badge hr-badge-neutral">${it.uom}</span>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: ${isLow ? '#dc2626' : '#059669'};">
                        ${it.available_stock.toFixed(2)}
                    </span>
                    ${isLow ? `<div style="font-size: 10px; color: #dc2626; font-weight: 700;">Deficit: ${(it.available_stock - it.requested_qty).toFixed(2)}</div>` : ''}
                </td>
                <td style="text-align: center;">
                    <input type="number" class="out-search-input" style="width: 80px; text-align: right; padding: 4px 6px; font-weight: 700;"
                           value="${it.requested_qty.toFixed(2)}" min="0.01" step="0.01"
                           onchange="updateDraftItemQty(${idx}, this.value)">
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ₱${it.unit_cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ₱${lineTotal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #dc2626; border-color: #fee2e2;" onclick="removeDraftItem(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('draftTotalUnitsDisplay').textContent = totalUnits.toFixed(2);
    document.getElementById('draftTotalValueDisplay').textContent = '₱' + totalValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateDraftItemQty(idx, val) {
    const q = parseFloat(val) || 0;
    if (q <= 0) {
        removeDraftItem(idx);
        return;
    }
    draftItems[idx].requested_qty = q;
    draftItems[idx].subtotal = q * draftItems[idx].unit_cost;
    renderDraftTable();
}

function removeDraftItem(idx) {
    draftItems.splice(idx, 1);
    renderDraftTable();
}

function resetDraftForm() {
    draftItems = [];
    renderDraftTable();
    document.getElementById('draftNotes').value = '';
    document.getElementById('draftReferenceNo').value = '';
    document.getElementById('draftVendorSelect').value = '';
    document.getElementById('vendorPillBox').style.display = 'none';
}

async function saveDraftOrder(moveToPick) {
    if (draftItems.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Requisition',
            text: 'Please add at least one line item from Item Master before saving.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const orderType = document.getElementById('draftOrderType').value;
    let destLocation = 'Main Kitchen Line';
    let vendorId = null;
    let vendorName = null;

    if (orderType === 'VENDOR_RETURN') {
        const vSel = document.getElementById('draftVendorSelect');
        vendorId = vSel.value ? parseInt(vSel.value) : null;
        if (!vendorId) {
            Swal.fire({ icon: 'warning', title: 'Vendor Required', text: 'Please select a registered vendor for vendor returns.', confirmButtonColor: '#9333ea' });
            return;
        }
        const opt = vSel.options[vSel.selectedIndex];
        vendorName = opt.dataset.name || 'Vendor';
        destLocation = opt.dataset.address || ('Vendor RMA: ' + vendorName);
    } else if (orderType === 'BRANCH_TRANSFER') {
        destLocation = document.getElementById('draftBranchSelect').value;
    } else if (orderType === 'SPOILAGE_DISPOSAL') {
        destLocation = document.getElementById('draftDisposalVault').value;
    } else {
        destLocation = document.getElementById('draftKitchenStation').value;
    }

    const payload = {
        order_type: orderType,
        destination_type: orderType === 'VENDOR_RETURN' ? 'VENDOR' : (orderType === 'BRANCH_TRANSFER' ? 'BRANCH' : 'INTERNAL_KITCHEN'),
        destination_location: destLocation,
        vendor_id: vendorId,
        vendor_name: vendorName,
        requested_by: document.getElementById('draftRequestedBy').value || 'Executive Chef',
        department: 'Kitchen Operations',
        priority: document.getElementById('draftPriority').value || 'NORMAL',
        reference_no: document.getElementById('draftReferenceNo').value || null,
        required_at: document.getElementById('draftRequiredAt').value || null,
        notes: document.getElementById('draftNotes').value || null,
        items: draftItems.map(it => ({
            sku: it.sku,
            inventory_item_id: it.inventory_item_id,
            item_name: it.item_name,
            category: it.category,
            uom: it.uom,
            requested_qty: it.requested_qty,
            unit_cost: it.unit_cost,
            storage_location: it.storage_location
        }))
    };

    try {
        Swal.fire({
            title: 'Creating Requisition...',
            text: 'Saving draft and syncing with Item Master',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const response = await fetch("{{ route('inventory.api.create-stock-out-draft') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const result = await response.json();
        if (!response.ok || !result.success) {
            throw new Error(result.message || 'Failed to create requisition draft.');
        }

        const newOrder = result.data.order;
        rawOrders.unshift(newOrder);

        // Update pick selector
        updatePickOrderDropdown();

        resetDraftForm();
        renderOrdersList();

        if (moveToPick) {
            Swal.fire({
                icon: 'success',
                title: 'Requisition Created!',
                text: `${newOrder.order_number} is ready for Pick, Pack & Ship.`,
                confirmButtonColor: '#9333ea',
                timer: 1600
            });
            jumpToPickPack(newOrder.id);
        } else {
            Swal.fire({
                icon: 'success',
                title: 'Draft Saved!',
                text: `Requisition ${newOrder.order_number} saved as Draft.`,
                confirmButtonColor: '#9333ea'
            });
            switchOutTab('tab-out-list');
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error Saving Requisition',
            text: err.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

/* --------------------------------------------------------------------------
   TAB 3: PICK PACK SHIP (Fulfillment Engine with Pack Qty & Line Progress Bar)
   -------------------------------------------------------------------------- */
function updatePickOrderDropdown() {
    const sel = document.getElementById('pickOrderSelect');
    if (!sel) return;
    const activeOrders = rawOrders.filter(o => ['DRAFT', 'PICKING', 'PACKED'].includes(o.status));
    let optHtml = '<option value="">-- Choose Active Dispatch Order --</option>';
    activeOrders.forEach(ord => {
        optHtml += `<option value="${ord.id}">${ord.order_number} [${ord.status}] - ${escapeHtml(ord.destination_location)} (${ord.total_items_count} items)</option>`;
    });
    sel.innerHTML = optHtml;
}

function jumpToPickPack(orderId) {
    updatePickOrderDropdown();
    document.getElementById('pickOrderSelect').value = orderId;
    loadOrderIntoPickPack(orderId);
    switchOutTab('tab-pick-pack-ship');
}

function loadOrderIntoPickPack(orderId) {
    if (!orderId) {
        activePickOrder = null;
        document.getElementById('pickActiveOrderNum').textContent = 'NO ORDER SELECTED';
        document.getElementById('pickActiveStatusBadge').textContent = 'Awaiting Selection';
        document.getElementById('pickActiveDestDisplay').textContent = '-';
        document.getElementById('pickActiveReqDisplay').textContent = '-';
        document.getElementById('pickActiveTypeDisplay').textContent = '-';
        document.getElementById('pickItemsTableBody').innerHTML = '';
        document.getElementById('pickNoItemsPrompt').style.display = 'block';
        document.getElementById('pickTotalValueDisplay').textContent = '₱0.00';
        updatePickOverallProgress();
        return;
    }

    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    activePickOrder = JSON.parse(JSON.stringify(order)); // clone

    document.getElementById('pickActiveOrderNum').textContent = activePickOrder.order_number;
    document.getElementById('pickActiveStatusBadge').textContent = activePickOrder.status;
    document.getElementById('pickActiveDestDisplay').textContent = activePickOrder.destination_location;
    document.getElementById('pickActiveReqDisplay').textContent = activePickOrder.requested_by + ' (' + (activePickOrder.department || 'Operations') + ')';
    document.getElementById('pickActiveTypeDisplay').textContent = formatOrderType(activePickOrder.order_type);

    if (activePickOrder.picker_name) document.getElementById('pickPickerName').value = activePickOrder.picker_name;
    if (activePickOrder.carrier_name) document.getElementById('pickCarrierName').value = activePickOrder.carrier_name;
    if (activePickOrder.tracking_waybill) document.getElementById('pickWaybill').value = activePickOrder.tracking_waybill;

    renderPickPackTable();
}

function renderPickPackTable() {
    const tbody = document.getElementById('pickItemsTableBody');
    const prompt = document.getElementById('pickNoItemsPrompt');
    const countBadge = document.getElementById('pickItemsCountBadge');

    if (!activePickOrder || !activePickOrder.items || activePickOrder.items.length === 0) {
        tbody.innerHTML = '';
        prompt.style.display = 'block';
        countBadge.textContent = '0 Items';
        updatePickOverallProgress();
        return;
    }
    prompt.style.display = 'none';
    countBadge.textContent = activePickOrder.items.length + ' Items';

    let html = '';
    let totalValuation = 0;

    activePickOrder.items.forEach((it, idx) => {
        const reqQty = parseFloat(it.requested_qty) || 0;
        const packedQty = parseFloat(it.packed_qty) || 0;
        const unitCost = parseFloat(it.unit_cost) || 0;
        const lineVal = packedQty * unitCost;
        totalValuation += lineVal;

        // Progress Calculation & Match Status
        const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
        const displayPct = Math.round(pct);

        let progressClass = 'fill-gray';
        let statusBadge = '<span class="hr-badge hr-badge-neutral">Unpicked</span>';

        if (packedQty > 0 && packedQty < reqQty) {
            progressClass = 'fill-partial';
            statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% (Partial)</span>`;
        } else if (packedQty > 0 && packedQty === reqQty) {
            progressClass = 'fill-matched';
            statusBadge = `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>`;
        } else if (packedQty > reqQty) {
            progressClass = 'fill-exceeds';
            const excess = (packedQty - reqQty).toFixed(2);
            statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${excess})</span>`;
        }

        const barWidth = Math.min(100, displayPct);

        html += `
            <tr id="pickRow_${idx}">
                <td>
                    <strong style="color: #0f172a;">${escapeHtml(it.item_name)}</strong>
                    <div style="font-size: 11px; font-family: monospace; color: var(--out-primary);">${it.sku}</div>
                    <div style="font-size: 10.5px; color: #94a3b8;"><i class="ph ph-archive"></i> ${escapeHtml(it.storage_location || 'Main Storage')}</div>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    <span style="font-weight: 700; color: #059669;">
                        ${(parseFloat(it.available_stock) || 0).toFixed(2)}
                    </span>
                    <div style="font-size: 10.5px; color: #64748b;">${it.uom}</div>
                </td>
                <td style="text-align: center; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;">
                    ${reqQty.toFixed(2)}
                    <span style="font-size: 10.5px; color: #64748b;">${it.uom}</span>
                </td>
                <td style="text-align: center;">
                    <div class="pack-qty-input-group" style="justify-content: center;">
                        <button type="button" class="pack-quick-btn" onclick="stepPackQty(${idx}, -1)" title="Decrement 1">-</button>
                        <input type="number" class="pack-qty-input" id="packInput_${idx}"
                               value="${packedQty.toFixed(2)}" min="0" step="0.01"
                               oninput="handlePackQtyInput(${idx}, this.value)">
                        <button type="button" class="pack-quick-btn" onclick="stepPackQty(${idx}, 1)" title="Increment 1">+</button>
                    </div>
                </td>
                <td>
                    <div class="line-progress-container">
                        <div class="line-progress-track">
                            <div class="line-progress-fill ${progressClass}" id="lineBar_${idx}" style="width: ${barWidth}%;"></div>
                        </div>
                        <div class="line-progress-meta">
                            <span id="lineMetaUnits_${idx}" style="color: #64748b;">${packedQty.toFixed(2)} / ${reqQty.toFixed(2)}</span>
                            <div id="lineMetaBadge_${idx}">${statusBadge}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" class="out-search-input" style="padding: 4px 8px; font-size: 11.5px; width: 100px;"
                           placeholder="LOT-2026..." value="${it.batch_lot_no || ''}"
                           onchange="activePickOrder.items[${idx}].batch_lot_no = this.value">
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: #0f172a;" id="lineVal_${idx}">
                    ₱${lineVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="matchSingleItem(${idx})" title="Match Requested Qty">
                        <i class="ph ph-check"></i> 100%
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('pickTotalValueDisplay').textContent = '₱' + totalValuation.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    updatePickOverallProgress();
}

function handlePackQtyInput(idx, val) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    const q = parseFloat(val) || 0;
    activePickOrder.items[idx].packed_qty = Math.max(0, q);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function stepPackQty(idx, delta) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    let curr = parseFloat(activePickOrder.items[idx].packed_qty) || 0;
    curr = Math.max(0, curr + delta);
    activePickOrder.items[idx].packed_qty = curr;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = curr.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function matchSingleItem(idx) {
    if (!activePickOrder || !activePickOrder.items[idx]) return;
    const req = parseFloat(activePickOrder.items[idx].requested_qty) || 0;
    activePickOrder.items[idx].packed_qty = req;
    const input = document.getElementById(`packInput_${idx}`);
    if (input) input.value = req.toFixed(2);
    updateSingleRowUI(idx);
    updatePickOverallProgress();
}

function updateSingleRowUI(idx) {
    const it = activePickOrder.items[idx];
    const reqQty = parseFloat(it.requested_qty) || 0;
    const packedQty = parseFloat(it.packed_qty) || 0;
    const unitCost = parseFloat(it.unit_cost) || 0;
    const lineVal = packedQty * unitCost;

    const pct = reqQty > 0 ? (packedQty / reqQty) * 100 : 0;
    const displayPct = Math.round(pct);
    const barWidth = Math.min(100, displayPct);

    let progressClass = 'fill-gray';
    let statusBadge = '<span class="hr-badge hr-badge-neutral">Unpicked</span>';

    if (packedQty > 0 && packedQty < reqQty) {
        progressClass = 'fill-partial';
        statusBadge = `<span class="hr-badge hr-badge-blue">${displayPct}% (Partial)</span>`;
    } else if (packedQty > 0 && packedQty === reqQty) {
        progressClass = 'fill-matched';
        statusBadge = `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Matched</span>`;
    } else if (packedQty > reqQty) {
        progressClass = 'fill-exceeds';
        const excess = (packedQty - reqQty).toFixed(2);
        statusBadge = `<span class="hr-badge hr-badge-amber"><i class="ph ph-warning"></i> Exceeds (+${excess})</span>`;
    }

    const bar = document.getElementById(`lineBar_${idx}`);
    if (bar) {
        bar.className = 'line-progress-fill ' + progressClass;
        bar.style.width = barWidth + '%';
    }

    const metaUnits = document.getElementById(`lineMetaUnits_${idx}`);
    if (metaUnits) metaUnits.textContent = `${packedQty.toFixed(2)} / ${reqQty.toFixed(2)}`;

    const metaBadge = document.getElementById(`lineMetaBadge_${idx}`);
    if (metaBadge) metaBadge.innerHTML = statusBadge;

    const valCell = document.getElementById(`lineVal_${idx}`);
    if (valCell) valCell.textContent = '₱' + lineVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updatePickOverallProgress() {
    if (!activePickOrder || !activePickOrder.items || activePickOrder.items.length === 0) {
        document.getElementById('pickOverallProgressBar').style.width = '0%';
        document.getElementById('pickGaugeBadge').textContent = '0% Packed';
        document.getElementById('pickTotalReqDisplay').textContent = '0.00';
        document.getElementById('pickTotalPackedDisplay').textContent = '0.00';
        return;
    }

    let totalReq = 0;
    let totalPacked = 0;
    let totalVal = 0;
    let hasExceeded = false;

    activePickOrder.items.forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        totalReq += r;
        totalPacked += p;
        totalVal += (p * (parseFloat(it.unit_cost) || 0));
        if (p > r) hasExceeded = true;
    });

    const pct = totalReq > 0 ? Math.min(100, Math.round((totalPacked / totalReq) * 100)) : 0;
    const bar = document.getElementById('pickOverallProgressBar');
    bar.style.width = pct + '%';
    if (pct >= 100) {
        bar.className = hasExceeded ? 'line-progress-fill fill-exceeds' : 'line-progress-fill fill-matched';
    } else {
        bar.className = 'line-progress-fill fill-partial';
    }

    document.getElementById('pickGaugeBadge').textContent = pct + '% Packed' + (hasExceeded ? ' (Exceeds)' : '');
    document.getElementById('pickTotalReqDisplay').textContent = totalReq.toFixed(2);
    document.getElementById('pickTotalPackedDisplay').textContent = totalPacked.toFixed(2);
    document.getElementById('pickTotalValueDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    const alertBox = document.getElementById('pickFulfillmentAlert');
    if (pct >= 100 && !hasExceeded) {
        alertBox.innerHTML = '<i class="ph ph-check-circle" style="color: #059669;"></i> <strong>All Line Items 100% Matched!</strong> Ready for final dispatch authorization and stock deduction.';
        alertBox.style.background = '#ecfdf5';
        alertBox.style.borderColor = '#d1fae5';
        alertBox.style.color = '#059669';
    } else if (hasExceeded) {
        alertBox.innerHTML = '<i class="ph ph-warning" style="color: #d97706;"></i> <strong>Line Items Exceed Requested Quantities!</strong> Please verify if additional units were authorized by kitchen chef.';
        alertBox.style.background = '#fffbeb';
        alertBox.style.borderColor = '#fef3c7';
        alertBox.style.color = '#d97706';
    } else {
        alertBox.innerHTML = `<i class="ph ph-info"></i> Pack physical quantities to verify against requested amounts before confirming dispatch. (${totalPacked.toFixed(1)} / ${totalReq.toFixed(1)} packed)`;
        alertBox.style.background = '#faf5ff';
        alertBox.style.borderColor = '#f3e8ff';
        alertBox.style.color = 'var(--out-primary)';
    }
}

function autoMatchAllPackQuantities() {
    if (!activePickOrder || !activePickOrder.items) return;
    activePickOrder.items.forEach((it, idx) => {
        it.packed_qty = parseFloat(it.requested_qty) || 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = it.packed_qty.toFixed(2);
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
}

function clearAllPackQuantities() {
    if (!activePickOrder || !activePickOrder.items) return;
    activePickOrder.items.forEach((it, idx) => {
        it.packed_qty = 0;
        const input = document.getElementById(`packInput_${idx}`);
        if (input) input.value = '0.00';
        updateSingleRowUI(idx);
    });
    updatePickOverallProgress();
}

async function savePickPackProgress() {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an order first before saving.', confirmButtonColor: '#9333ea' });
        return;
    }

    const payload = {
        order_id: activePickOrder.id,
        picker_name: document.getElementById('pickPickerName').value || null,
        carrier_name: document.getElementById('pickCarrierName').value || null,
        tracking_waybill: document.getElementById('pickWaybill').value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null
        }))
    };

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.update-stock-out-pack') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to save progress.');

        // Update in global array
        const idx = rawOrders.findIndex(o => o.id == activePickOrder.id);
        if (idx !== -1) rawOrders[idx] = data.data.order;

        renderOrdersList();
        updatePickOrderDropdown();
        document.getElementById('pickOrderSelect').value = activePickOrder.id;

        Swal.fire({
            icon: 'success',
            title: 'Progress Saved',
            text: `Pick & Pack progress recorded for ${activePickOrder.order_number}.`,
            timer: 1400,
            showConfirmButton: false
        });
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Error Saving Progress', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

async function confirmShipOrder() {
    if (!activePickOrder) {
        Swal.fire({ icon: 'warning', title: 'No Order Selected', text: 'Select an active order first before dispatching.', confirmButtonColor: '#9333ea' });
        return;
    }

    const totalPacked = activePickOrder.items.reduce((acc, it) => acc + (parseFloat(it.packed_qty) || 0), 0);
    if (totalPacked <= 0) {
        Swal.fire({
            icon: 'error',
            title: 'Zero Packed Items',
            text: 'Cannot ship an order with 0 packed items. Please enter packed quantities.',
            confirmButtonColor: '#9333ea'
        });
        return;
    }

    const confirmRes = await Swal.fire({
        title: 'Confirm Dispatch & Deduct Stock?',
        html: `
            <div style="font-size: 13px; text-align: left; background: #f8fafc; padding: 12px; border-radius: 8px; margin-top: 8px;">
                <div><strong>Order #:</strong> ${activePickOrder.order_number}</div>
                <div><strong>Destination:</strong> ${escapeHtml(activePickOrder.destination_location)}</div>
                <div><strong>Total Packed Units:</strong> ${totalPacked.toFixed(2)}</div>
                <div style="margin-top: 8px; color: #dc2626; font-size: 12px;">
                    <i class="ph ph-warning"></i> This action will atomically deduct on-hand quantities from Item Master and post an immutable <strong>STOCK_OUT</strong> transaction to the Master Stock Ledger.
                </div>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '✓ Yes, Dispatch & Deduct Stock',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#059669',
        cancelButtonColor: '#64748b'
    });

    if (!confirmRes.isConfirmed) return;

    const payload = {
        order_id: activePickOrder.id,
        picker_name: document.getElementById('pickPickerName').value || 'Warehouse Lead',
        carrier_name: document.getElementById('pickCarrierName').value || null,
        tracking_waybill: document.getElementById('pickWaybill').value || null,
        items: activePickOrder.items.map(it => ({
            sku: it.sku,
            packed_qty: parseFloat(it.packed_qty) || 0,
            batch_lot_no: it.batch_lot_no || null
        }))
    };

    try {
        Swal.fire({
            title: 'Processing Dispatch...',
            text: 'Deducting inventory balances and creating ledger records',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.confirm-ship-stock-out') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();
        if (!res.ok || !data.success) throw new Error(data.message || 'Failed to dispatch order.');

        const shippedOrder = data.data.order;

        // Replace in rawOrders
        const idx = rawOrders.findIndex(o => o.id == shippedOrder.id);
        if (idx !== -1) rawOrders[idx] = shippedOrder;

        // Refresh live data from backend to ensure Item Master & Stocks Overview balances sync
        await fetchLiveStockOutData();

        Swal.fire({
            icon: 'success',
            title: 'Dispatched Successfully!',
            html: `
                <p>Order <strong>${shippedOrder.order_number}</strong> has been marked as SHIPPED.</p>
                <p style="font-size: 12px; color: #64748b;">Physical stock has been decremented and logged in Master Stock Ledger.</p>
                <div style="margin-top: 14px; display: flex; justify-content: center; gap: 10px;">
                    <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                        <i class="ph ph-squares-four"></i> View in Stocks Overview
                    </a>
                </div>
            `,
            confirmButtonColor: '#9333ea'
        });

        // Clear active order and switch to list
        loadOrderIntoPickPack(null);
        updatePickOrderDropdown();
        switchOutTab('tab-out-list');

    } catch (err) {
        Swal.fire({ icon: 'error', title: 'Dispatch Error', text: err.message, confirmButtonColor: '#9333ea' });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveStockOutData() {
    try {
        const res = await fetch("{{ route('inventory.api.stock-out-data') }}");
        const json = await res.json();
        if (json.success && json.data) {
            rawOrders = json.data.orders || [];
            rawProducts = json.data.products || [];
            rawVendors = json.data.vendors || [];
            rawCategories = json.data.categories || [];
            renderOrdersList();
            updatePickOrderDropdown();
        }
    } catch (e) {
        console.error('Failed to sync live stock-out data:', e);
    }
}

/* --------------------------------------------------------------------------
   DETAIL & PRINTING MODAL
   -------------------------------------------------------------------------- */
function openOrderDetailModal(orderId) {
    const order = rawOrders.find(o => o.id == orderId);
    if (!order) return;

    document.getElementById('modalOrderNum').textContent = order.order_number + ' [' + order.status + ']';
    document.getElementById('modalOrderDate').textContent = 'Created: ' + formatDate(order.created_at) + (order.dispatched_at ? ' | Dispatched: ' + formatDate(order.dispatched_at) : '');
    document.getElementById('modalDest').textContent = order.destination_location;
    document.getElementById('modalType').textContent = formatOrderType(order.order_type);
    document.getElementById('modalReq').textContent = order.requested_by + ' (' + (order.department || 'Kitchen') + ')';
    document.getElementById('modalPicker').textContent = order.picker_name || 'Pending assignment';
    document.getElementById('modalCarrier').textContent = order.carrier_name || 'Standard In-House Logistics';
    document.getElementById('modalWaybill').textContent = order.tracking_waybill || 'N/A';

    const tbody = document.getElementById('modalItemsTableBody');
    let html = '';
    let totalVal = 0;

    (order.items || []).forEach(it => {
        const r = parseFloat(it.requested_qty) || 0;
        const p = parseFloat(it.packed_qty) || 0;
        const cost = parseFloat(it.unit_cost) || 0;
        const lineT = p > 0 ? (p * cost) : (r * cost);
        totalVal += lineT;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--out-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td><span class="hr-badge hr-badge-neutral">${escapeHtml(it.category || 'Raw')}</span></td>
                <td>${it.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">${r.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700; color: ${p >= r ? '#059669' : '#0f172a'};">${p.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${cost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${lineT.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
    document.getElementById('modalTotalValDisplay').textContent = '₱' + totalVal.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    document.getElementById('orderDetailModal').classList.add('open');
}

function closeOrderDetailModal() {
    document.getElementById('orderDetailModal').classList.remove('open');
}

function printPackingSlip() {
    window.print();
}

/* --------------------------------------------------------------------------
   KEYBOARD SHORTCUTS & HELPERS
   -------------------------------------------------------------------------- */
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Press '/' to search
        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            const s = document.getElementById('orderSearchInput');
            if (s) s.focus();
        }

        // Press 'F10' for Primary Action
        if (e.key === 'F10') {
            e.preventDefault();
            const activeTab = document.querySelector('.out-tab-panel.active');
            if (activeTab && activeTab.id === 'tab-create-draft') {
                saveDraftOrder(true);
            } else if (activeTab && activeTab.id === 'tab-pick-pack-ship') {
                confirmShipOrder();
            }
        }
    });
}

function formatOrderType(type) {
    if (!type) return 'Kitchen Usage';
    switch (type) {
        case 'KITCHEN_USAGE': return 'Kitchen Line Usage';
        case 'BRANCH_TRANSFER': return 'Inter-Branch Transfer';
        case 'VENDOR_RETURN': return 'Vendor Return (RMA)';
        case 'SPOILAGE_DISPOSAL': return 'Spoilage / Disposal';
        case 'STAFF_MEALS': return 'Staff Meals';
        case 'SAMPLE_TASTING': return 'Sample Tasting';
        default: return type.replace(/_/g, ' ');
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

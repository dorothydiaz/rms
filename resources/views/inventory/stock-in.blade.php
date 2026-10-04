@extends('layouts.app')

@section('title', 'Stock In / Receiving (GRN) - Inventory Operations')

@push('styles')
<style>
/* ==========================================================================
   STOCK IN / RECEIVING MODULE - ENTERPRISE HR THEME TOKENS
   Adhering to docs/HR_THEME_DESIGN_SYSTEM.md & assets/css/theme-tokens.css
   ========================================================================== */
:root {
    --grn-canvas-bg: #faf7fd;
    --grn-surface-card: rgba(255, 255, 255, 0.94);
    --grn-surface-subtle: #f8fafc;
    --grn-surface-hover: rgba(245, 243, 255, 0.65);
    --grn-border: rgba(226, 232, 240, 0.9);
    --grn-border-focus: #c084fc;

    /* Universal HR Primary Gradient & Brand Accents */
    --grn-primary: #9333ea;
    --grn-primary-dark: #7c3aed;
    --grn-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --grn-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);
    --grn-primary-glow: rgba(168, 85, 247, 0.28);
    --grn-accent-strip: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);

    --grn-text-strong: #0f172a;
    --grn-text-medium: #334155;
    --grn-text-muted: #64748b;
    --grn-text-subtle: #94a3b8;

    /* Semantic Status Colors */
    --grn-success: #047857;
    --grn-success-bg: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.18));
    --grn-success-border: rgba(16, 185, 129, 0.32);

    --grn-warning: #b45309;
    --grn-warning-bg: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(217, 119, 6, 0.18));
    --grn-warning-border: rgba(245, 158, 11, 0.32);

    --grn-danger: #b91c1c;
    --grn-danger-bg: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.18));
    --grn-danger-border: rgba(239, 68, 68, 0.32);

    --grn-info: #0369a1;
    --grn-info-bg: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(14, 165, 233, 0.18));
    --grn-info-border: rgba(14, 165, 233, 0.32);

    --grn-purple-bg: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18));
    --grn-purple-border: rgba(168, 85, 247, 0.32);

    /* Shadows & Glassmorphic Elevation */
    --grn-shadow-card: 0 10px 32px rgba(148, 163, 184, 0.08), 0 2px 8px rgba(0, 0, 0, 0.02);
    --grn-shadow-card-hover: 0 14px 32px rgba(168, 85, 247, 0.14), 0 4px 10px rgba(236, 72, 153, 0.08);
    --grn-shadow-modal: 0 25px 60px -15px rgba(15, 23, 42, 0.35), 0 0 0 1px rgba(255, 255, 255, 0.2);

    --grn-drawer-width: min(940px, 96vw);
}

/* Page Root Wrapper */
.grn-workspace {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background-color: var(--grn-canvas-bg);
    min-height: calc(100vh - 72px);
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--grn-text-strong);
}

/* Accent Strip Header for Main Cards */
.grn-card-strip {
    position: relative;
    border-radius: 18px;
    background: var(--grn-surface-card);
    border: 1px solid var(--grn-border);
    box-shadow: var(--grn-shadow-card);
    backdrop-filter: blur(24px) saturate(180%);
    overflow: hidden;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.grn-card-strip::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3.5px;
    background: var(--grn-accent-strip);
}

/* Header & Breadcrumb Info */
.grn-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
}
.grn-title-group h1 {
    font-family: 'League Spartan', 'Poppins', sans-serif;
    font-size: 26px;
    font-weight: 800;
    margin: 0;
    letter-spacing: -0.02em;
    color: var(--grn-text-strong);
    display: flex;
    align-items: center;
    gap: 10px;
}
.grn-title-group h1 i {
    color: var(--grn-primary);
    font-size: 28px;
}
.grn-title-group p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: var(--grn-text-muted);
}
.grn-actions-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* KPI Summary Cards */
.grn-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 16px;
}
.grn-kpi-card {
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
}
.grn-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--grn-shadow-card-hover);
}
.grn-kpi-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.grn-kpi-label {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--grn-text-muted);
}
.grn-kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.02em;
    color: var(--grn-text-strong);
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
}
.grn-kpi-meta {
    font-size: 11.5px;
    color: var(--grn-text-subtle);
    display: flex;
    align-items: center;
    gap: 5px;
}
.grn-kpi-icon-wrap {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.grn-kpi-icon-purple {
    background: var(--grn-purple-bg);
    color: var(--grn-primary);
    border: 1px solid var(--grn-purple-border);
}
.grn-kpi-icon-green {
    background: var(--grn-success-bg);
    color: var(--grn-success);
    border: 1px solid var(--grn-success-border);
}
.grn-kpi-icon-amber {
    background: var(--grn-warning-bg);
    color: var(--grn-warning);
    border: 1px solid var(--grn-warning-border);
}
.grn-kpi-icon-sky {
    background: var(--grn-info-bg);
    color: var(--grn-info);
    border: 1px solid var(--grn-info-border);
}

/* ==========================================================================
   PRIMARY TABS-WRAPPER BAR
   List of the PO | Pending PO | Create new PO to received
   ========================================================================== */
.tabs-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    border-bottom: 2px solid #e2e8f0;
    padding-bottom: 2px;
    overflow-x: auto;
}
.tabs-wrapper .tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 22px;
    font-size: 14px;
    font-weight: 700;
    color: var(--grn-text-muted);
    border: none;
    background: transparent;
    cursor: pointer;
    border-radius: 12px 12px 0 0;
    position: relative;
    transition: all 0.2s ease;
    white-space: nowrap;
    font-family: inherit;
}
.tabs-wrapper .tab-btn:hover {
    color: var(--grn-primary);
    background: rgba(168, 85, 247, 0.06);
}
.tabs-wrapper .tab-btn.active {
    color: var(--grn-primary);
    background: #ffffff;
    box-shadow: 0 -3px 12px rgba(168, 85, 247, 0.08);
}
.tabs-wrapper .tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    right: 0;
    height: 3.5px;
    background: var(--grn-primary-gradient);
    border-radius: 3px 3px 0 0;
}
.tabs-wrapper .tab-count {
    padding: 2.5px 8px;
    font-size: 11px;
    font-weight: 800;
    border-radius: 12px;
    background: #f1f5f9;
    color: var(--grn-text-medium);
}
.tabs-wrapper .tab-btn.active .tab-count {
    background: var(--grn-purple-bg);
    color: var(--grn-primary);
}
.tabs-wrapper .tab-badge-pill {
    padding: 2.5px 8px;
    font-size: 10px;
    font-weight: 800;
    border-radius: 10px;
    background: var(--grn-primary-gradient);
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* Universal HR Buttons */
.hr-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 18px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid transparent;
    text-decoration: none;
    box-sizing: border-box;
    font-family: inherit;
    line-height: 1.4;
}
.hr-btn-primary {
    background: var(--grn-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.32), inset 0 1px 1px rgba(255, 255, 255, 0.4);
}
.hr-btn-primary:hover {
    background: var(--grn-primary-gradient-hover);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.42);
    transform: translateY(-1px);
}
.hr-btn-secondary {
    background: rgba(255, 255, 255, 0.85);
    border: 1px solid var(--grn-border);
    color: var(--grn-text-medium);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
}
.hr-btn-secondary:hover {
    background: #ffffff;
    border-color: #cbd5e1;
    color: var(--grn-primary);
    transform: translateY(-1px);
}
.hr-btn-success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.3);
}
.hr-btn-success:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    transform: translateY(-1px);
}
.hr-btn-danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.3);
}
.hr-btn-sm {
    padding: 6px 12px;
    font-size: 11.5px;
    border-radius: 8px;
    gap: 5px;
}

/* Filter Controls Bar */
.grn-filter-bar {
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}
.grn-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 440px;
}
.grn-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--grn-text-subtle);
    font-size: 17px;
}
.grn-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1px solid var(--grn-border);
    background: #ffffff;
    font-size: 13px;
    font-family: inherit;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.grn-search-input:focus {
    outline: none;
    border-color: var(--grn-border-focus);
    box-shadow: 0 0 0 3.5px rgba(168, 85, 247, 0.18);
}
.grn-select {
    padding: 9px 12px;
    border-radius: 10px;
    border: 1px solid var(--grn-border);
    background: #ffffff;
    font-size: 13px;
    color: var(--grn-text-medium);
    font-family: inherit;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease;
}
.grn-select:focus {
    border-color: var(--grn-border-focus);
    box-shadow: 0 0 0 3.5px rgba(168, 85, 247, 0.18);
}

/* Data Table Container */
.grn-table-container {
    overflow-x: auto;
    border-radius: 0 0 18px 18px;
    background: #ffffff;
}
.grn-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
    text-align: left;
}
.grn-table th {
    background: #f8fafc;
    padding: 12px 16px;
    font-size: 11.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
}
.grn-table td {
    padding: 13px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--grn-text-medium);
    vertical-align: middle;
}
.grn-table tr:hover td {
    background-color: var(--grn-surface-hover);
}
.grn-mono {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 12px;
    font-weight: 600;
}
.grn-currency {
    font-family: 'Outfit', sans-serif;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--grn-text-strong);
}

/* Badges */
.hr-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3.5px 9px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.hr-badge-success { background: var(--grn-success-bg); color: var(--grn-success); border: 1px solid var(--grn-success-border); }
.hr-badge-warning { background: var(--grn-warning-bg); color: var(--grn-warning); border: 1px solid var(--grn-warning-border); }
.hr-badge-danger { background: var(--grn-danger-bg); color: var(--grn-danger); border: 1px solid var(--grn-danger-border); }
.hr-badge-info { background: var(--grn-info-bg); color: var(--grn-info); border: 1px solid var(--grn-info-border); }
.hr-badge-purple { background: var(--grn-purple-bg); color: var(--grn-primary); border: 1px solid var(--grn-purple-border); }
.hr-badge-neutral { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

/* ==========================================================================
   TAB 3: REUSED "REQUEST FOR QUOTATION BUILDER" ASYMMETRIC WORKSPACE
   Asymmetric Grid: ~4.2fr Left Column / ~5.8fr Right Column
   ========================================================================== */
.rfq-builder-workspace-grid {
    display: grid;
    grid-template-columns: minmax(360px, 4.2fr) minmax(500px, 5.8fr);
    gap: 16px;
    align-items: stretch;
}
@media (max-width: 1150px) {
    .rfq-builder-workspace-grid {
        grid-template-columns: 1fr;
    }
}
.rfq-builder-left-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.rfq-builder-right-col {
    display: flex;
    flex-direction: column;
    gap: 14px;
    min-width: 0;
}
.rfq-panel-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    box-shadow: var(--grn-shadow-card);
    padding: 18px 20px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}
.rfq-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 10px;
    margin-bottom: 2px;
}
.rfq-panel-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--grn-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
}
.rfq-panel-title i {
    color: var(--grn-primary);
    font-size: 17px;
}

/* Searchable Combobox & Vendor Card */
.rfq-vendor-pill-box {
    display: flex;
    align-items: center;
    gap: 12px;
    background: #faf5ff;
    border: 1px solid #f3e8ff;
    border-radius: 10px;
    padding: 10px 14px;
}
.rfq-vendor-avatar {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    background: var(--grn-primary-gradient);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 14px;
    flex-shrink: 0;
}
.rfq-vendor-summary {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

/* Slide-Over Drawer for Inbound Receiving Inspection */
.grn-drawer-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 1000;
    display: none;
    opacity: 0;
    transition: opacity 0.25s ease;
}
.grn-drawer-overlay.active {
    display: flex;
    justify-content: flex-end;
    opacity: 1;
}
.grn-drawer {
    width: var(--grn-drawer-width);
    height: 100%;
    background: #ffffff;
    box-shadow: var(--grn-shadow-modal);
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}
.grn-drawer-overlay.active .grn-drawer {
    transform: translateX(0);
}
.grn-drawer-header {
    padding: 18px 24px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
}
.grn-drawer-header::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 3.5px;
    background: var(--grn-accent-strip);
}
.grn-drawer-close {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: var(--grn-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 18px;
}
.grn-drawer-close:hover {
    background: #fee2e2;
    color: #ef4444;
}
.grn-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: #faf7fd;
}
.grn-drawer-footer {
    padding: 16px 24px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #ffffff;
}

/* Modals */
.grn-modal-backdrop {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(6px);
    z-index: 1200;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.grn-modal-backdrop.active { display: flex; }
.grn-dialog {
    background: #ffffff;
    border-radius: 16px;
    width: 100%;
    max-width: 640px;
    box-shadow: var(--grn-shadow-modal);
    border: 1px solid #e2e8f0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}
.grn-dialog.wide { max-width: 860px; }
.grn-dialog-header {
    padding: 16px 22px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.grn-dialog-body {
    padding: 20px 22px;
    max-height: 75vh;
    overflow-y: auto;
    font-size: 13px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}
.grn-dialog-footer {
    padding: 14px 22px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    background: #f8fafc;
}
</style>
@endpush

@section('content')
<div class="grn-workspace">
    <!-- Header Row -->
    <div class="grn-header-row">
        <div class="grn-title-group">
            <h1><i class="ph ph-tray-arrow-down"></i> Stock In / Receiving (GRN)</h1>
            <p>Purchase Order Receiving, Backlog Tracking, and Direct Inbound Receiving Builder</p>
        </div>
        <div class="grn-actions-group">
            <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="exportOrdersToCsv()">
                <i class="ph ph-file-csv"></i> Export CSV
            </button>
            <button class="hr-btn hr-btn-primary" onclick="switchGrnTab('tab-create-po')">
                <i class="ph ph-plus-circle"></i> + Create New Receiving Order (F2)
            </button>
        </div>
    </div>

    <!-- KPI Summary Grid -->
    <div class="grn-kpi-grid">
        <div class="grn-card-strip grn-kpi-card" onclick="switchGrnTab('tab-po-list')">
            <div class="grn-kpi-info">
                <span class="grn-kpi-label">Total Purchase Orders</span>
                <span class="grn-kpi-value" id="kpiTotalPos">0</span>
                <span class="grn-kpi-meta"><i class="ph ph-receipt" style="color: #9333ea;"></i> Purchase Module Masterlist</span>
            </div>
            <div class="grn-kpi-icon-wrap grn-kpi-icon-purple">
                <i class="ph ph-files"></i>
            </div>
        </div>

        <div class="grn-card-strip grn-kpi-card" onclick="switchGrnTab('tab-pending-po')">
            <div class="grn-kpi-info">
                <span class="grn-kpi-label">Pending POs & Backlogs</span>
                <span class="grn-kpi-value" id="kpiPendingPos" style="color: #b45309;">0</span>
                <span class="grn-kpi-meta"><i class="ph ph-clock-countdown" style="color: #f59e0b;"></i> Orders awaiting delivery / partial</span>
            </div>
            <div class="grn-kpi-icon-wrap grn-kpi-icon-amber">
                <i class="ph ph-hourglass-high"></i>
            </div>
        </div>

        <div class="grn-card-strip grn-kpi-card" onclick="switchGrnTab('tab-po-list')">
            <div class="grn-kpi-info">
                <span class="grn-kpi-label">Fulfilled Deliveries</span>
                <span class="grn-kpi-value" id="kpiCompletedPos" style="color: #047857;">0</span>
                <span class="grn-kpi-meta"><i class="ph ph-check-circle" style="color: #10b981;"></i> Fully received stock</span>
            </div>
            <div class="grn-kpi-icon-wrap grn-kpi-icon-green">
                <i class="ph ph-package"></i>
            </div>
        </div>

        <div class="grn-card-strip grn-kpi-card" onclick="switchGrnTab('tab-create-po')">
            <div class="grn-kpi-info">
                <span class="grn-kpi-label">Direct Received Value</span>
                <span class="grn-kpi-value" id="kpiTotalReceivedValue">₱0.00</span>
                <span class="grn-kpi-meta"><i class="ph ph-coins" style="color: #0284c7;"></i> Capitalized to Stock Overview</span>
            </div>
            <div class="grn-kpi-icon-wrap grn-kpi-icon-sky">
                <i class="ph ph-trend-up"></i>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         PRIMARY TABS-WRAPPER BAR
         1. List of the PO  2. Pending PO  3. Create new PO to received
         ===================================================================== -->
    <nav class="tabs-wrapper" id="grnTabsBar">
        <button type="button" class="tab-btn active" id="tabBtnPoList" onclick="switchGrnTab('tab-po-list')">
            <i class="ph ph-list-dashes"></i>
            <span>List of the PO</span>
            <span class="tab-count" id="badgePoListCount">0</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnPendingPo" onclick="switchGrnTab('tab-pending-po')">
            <i class="ph ph-clock-countdown"></i>
            <span>Pending PO</span>
            <span class="tab-count" id="badgePendingPoCount">0</span>
        </button>

        <button type="button" class="tab-btn" id="tabBtnCreatePo" onclick="switchGrnTab('tab-create-po')">
            <i class="ph ph-plus-circle"></i>
            <span>Create new PO to received</span>
            <span class="tab-badge-pill">Bypass PO</span>
        </button>
    </nav>

    <!-- =====================================================================
         TAB 1: LIST OF THE PO (Master List connected to Purchase Module)
         ===================================================================== -->
    <div id="tab-po-list" class="grn-tab-panel">
        <div class="grn-card-strip">
            <div class="grn-filter-bar">
                <div class="grn-search-box">
                    <i class="ph ph-magnifying-glass"></i>
                    <input type="text" id="poListSearchInput" class="grn-search-input" placeholder="Search orders by PO #, Vendor, Stall, or Item... (/)" oninput="renderPoMasterList()">
                </div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <select id="poListStatusFilter" class="grn-select" onchange="renderPoMasterList()">
                        <option value="ALL">All Order Statuses</option>
                        <option value="Approved / Issued">Approved / Ready to Receive</option>
                        <option value="Partially Received">Partially Received (Backlog)</option>
                        <option value="Fully Received">Fully Received</option>
                        <option value="Draft / Pending Review">Draft / Pending</option>
                        <option value="Short-Closed / Completed">Short-Closed</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>

                    <select id="poListTypeFilter" class="grn-select" onchange="renderPoMasterList()">
                        <option value="ALL">All PO Types</option>
                        <option value="vendor">Commercial Vendor</option>
                        <option value="wet_market">Wet Market Direct</option>
                        <option value="ad_hoc">Direct / Bypass Order</option>
                    </select>
                </div>
            </div>

            <div class="grn-table-container">
                <table class="grn-table">
                    <thead>
                        <tr>
                            <th>PO Reference</th>
                            <th>Order Date</th>
                            <th>Vendor / Supplier</th>
                            <th>Sourcing Channel</th>
                            <th>Delivery Location</th>
                            <th style="text-align: center;">Line Items</th>
                            <th style="text-align: right;">Total Amount</th>
                            <th style="text-align: center;">Payment Status</th>
                            <th style="text-align: center;">Order Status</th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poMasterTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 2: PENDING PO (Backlogs & Open Deliveries Queue)
         ===================================================================== -->
    <div id="tab-pending-po" class="grn-tab-panel" style="display: none;">
        <div class="grn-card-strip">
            <div class="grn-filter-bar" style="background: #fffbeb; border-bottom: 1px solid #fde68a;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-warning-circle" style="font-size: 22px; color: #b45309;"></i>
                    <span style="font-size: 13px; font-weight: 600; color: #92400e;">
                        Pending Deliveries & Backlogs: Showing active Purchase Orders with pending shipments or partially received quantities awaiting dock fulfillment.
                    </span>
                </div>
            </div>

            <div class="grn-table-container">
                <table class="grn-table">
                    <thead>
                        <tr>
                            <th>PO Reference</th>
                            <th>Vendor / Supplier</th>
                            <th>Expected Delivery</th>
                            <th>Delivery Destination</th>
                            <th style="text-align: center;">Ordered Qty</th>
                            <th style="text-align: center;">Already Received</th>
                            <th style="text-align: center;">Backlog / Open Balance</th>
                            <th style="text-align: right;">Backlog Value</th>
                            <th style="text-align: center;">Delivery State</th>
                            <th style="text-align: center;">Fulfillment Action</th>
                        </tr>
                    </thead>
                    <tbody id="pendingPoTableBody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         TAB 3: CREATE NEW PO TO RECEIVED
         Reusing the Request for Quotation (RFQ) Builder Asymmetric Workspace
         ===================================================================== -->
    <div id="tab-create-po" class="grn-tab-panel" style="display: none;">
        <div class="grn-card-strip" style="padding: 20px;">
            <!-- Top Command Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--grn-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff;">
                        <i class="ph ph-sliders-horizontal"></i> Direct Inbound Receiving Workspace (Bypass PO)
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetDirectReceivingForm()">
                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Form
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary" onclick="submitDirectReceivingOrder()">
                        <i class="ph ph-check-circle"></i> ✓ Approve & Receive Stock In (F10)
                    </button>
                </div>
            </div>

            <!-- RFQ Builder Asymmetric Workspace Grid -->
            <div class="rfq-builder-workspace-grid">

                <!-- Left Column (4.2fr): Inbound Identity, Vendor & Settlement Details -->
                <div class="rfq-builder-left-col">

                    <!-- Card 1: Receiving Reference & Inflow Purpose -->
                    <div class="rfq-panel-card" style="border-left: 4px solid var(--grn-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.65));">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div>
                                <div style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Receiving Reference</div>
                                <div style="font-family: monospace; font-size: 1.2rem; font-weight: 800; color: var(--grn-primary);" id="directRecRefDisplay">DIR-REC-2026-0001</div>
                            </div>
                            <span class="hr-badge hr-badge-purple">Direct Dock Inflow</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 4px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Receipt Reason / Purpose Code *</label>
                            <select id="directReasonCode" class="grn-select" style="font-size: 12px; padding: 7px 10px;">
                                <option value="Direct Spot Purchase">Direct Spot Purchase / Walk-In</option>
                                <option value="Emergency Market Run">Emergency Wet Market Run</option>
                                <option value="Vendor Promotional Sample">Vendor Promotional Free Sample</option>
                                <option value="Customer Return / Exchange">Customer Return / Exchange</option>
                                <option value="Commissary Inter-Branch Inflow">Commissary Inter-Branch Transfer In</option>
                                <option value="Consignment Stock In">Consignment Stock In</option>
                            </select>
                        </div>
                    </div>

                    <!-- Card 2: Vendor Selection & Partner Profile -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-buildings"></i>
                                <span>Vendor Selection & Partner Profile</span>
                            </div>
                            <span class="hr-badge hr-badge-neutral">Vendor Masterlist</span>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Select Registered Vendor *</label>
                            <select id="directVendorSelect" class="grn-select" onchange="handleDirectVendorSelect(this.value)">
                                <option value="">-- Choose Approved Vendor from Masterlist --</option>
                                <!-- Populated dynamically -->
                            </select>
                        </div>

                        <!-- Vendor Pill Box -->
                        <div class="rfq-vendor-pill-box" id="directVendorPillBox" style="display: none;">
                            <div class="rfq-vendor-avatar" id="directVendorAvatar">VM</div>
                            <div class="rfq-vendor-summary">
                                <div style="font-weight: 700; font-size: 13px; color: #0f172a;" id="directVendorTradeName">Vendor Name</div>
                                <div style="font-size: 11px; color: #64748b;" id="directVendorCategoryTag">Category: Raw Ingredients</div>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Direct Stall / Walk-in Supplier Name (if unlisted)</label>
                            <input type="text" id="directCustomStallName" class="grn-search-input" style="padding-left: 12px;" placeholder="e.g. Farmer's Market Seafood Section Stall 8">
                        </div>
                    </div>

                    <!-- Card 3: Inbound Logistics & Delivery Slip -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-truck"></i>
                                <span>Inbound Logistics & Carrier</span>
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Supplier DR / Slip # *</label>
                                <input type="text" id="directDeliverySlip" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. DR-884920">
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Receiving Date & Time *</label>
                                <input type="datetime-local" id="directReceivedAt" class="grn-search-input" style="padding-left: 10px;">
                            </div>
                        </div>

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Delivery Location</label>
                                <select id="directDeliveryLocation" class="grn-select" style="font-size: 12px; padding: 6px 10px;">
                                    <option value="Central Commissary - Receiving Dock 1">Central Commissary - Dock 1</option>
                                    <option value="Branch 1 - Makati Bistro">Branch 1 - Makati Bistro</option>
                                    <option value="Branch 2 - BGC Bistro Kitchen">Branch 2 - BGC Bistro Kitchen</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Carrier / Driver & Plate</label>
                                <input type="text" id="directCarrier" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. Purchaser Vehicle (NDF 8812)">
                            </div>
                        </div>
                    </div>

                    <!-- Card 4: Financial Settlement & Payment Integration -->
                    <div class="rfq-panel-card">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-bank"></i>
                                <span>Financial Settlement & Payment</span>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 6px;">
                            <label style="font-size: 11.5px; font-weight: 600; color: #475569;">Settlement Method *</label>
                            <select id="directSettlementMode" class="grn-select" onchange="handleDirectSettlementChange(this.value)">
                                <option value="IMMEDIATE_COD">Immediate Settlement (Cash-on-Delivery / Dock Cash Out)</option>
                                <option value="CREDIT_NET30">Standard Credit (Net 30) - Generate Pending AP Bill</option>
                                <option value="ADVANCE_APPLIED">Pre-Paid / Advance Payment Applied</option>
                            </select>
                        </div>

                        <div id="directCodFields" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Payment Mode</label>
                                <select id="directPaymentMethod" class="grn-select" style="font-size: 12px; padding: 6px 10px;">
                                    <option value="Cash / Currency">Cash / Currency</option>
                                    <option value="Petty Cash Fund">Petty Cash Fund</option>
                                    <option value="GCash / Maya">GCash / Maya</option>
                                    <option value="Bank Transfer / Electronic">Bank Transfer / Electronic</option>
                                    <option value="Corporate Credit Card">Corporate Credit Card</option>
                                </select>
                            </div>
                            <div style="display: flex; flex-direction: column; gap: 4px;">
                                <label style="font-size: 11px; font-weight: 600; color: #475569;">Official Receipt (OR) #</label>
                                <input type="text" id="directPaymentRef" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. OR #882019">
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Right Column (5.8fr): Product Catalog, Receiving Line Items, Landed Costs & Valuation -->
                <div class="rfq-builder-right-col">

                    <!-- Card 5: Items Panel & Product Catalog Quick-Add -->
                    <div class="rfq-panel-card" style="flex: 1;">
                        <div class="rfq-panel-header">
                            <div class="rfq-panel-title">
                                <i class="ph ph-package"></i>
                                <span>Receiving Line Items & Inspection</span>
                            </div>
                            <span class="hr-badge hr-badge-purple" id="directItemCountBadge">0 Items Added</span>
                        </div>

                        <!-- Catalog Quick-Add Bar -->
                        <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 10px;">
                            <div style="flex: 1;">
                                <select id="directCatalogPicker" class="grn-select" style="width: 100%;">
                                    <option value="">-- Quick Pick from Product Catalog (Item Master) --</option>
                                    <!-- Populated from rms_inventory_products -->
                                </select>
                            </div>
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="addPickedCatalogItem()">
                                <i class="ph ph-plus"></i> Add Item
                            </button>
                        </div>

                        <!-- Items Grid Table -->
                        <div style="overflow-x: auto; max-height: 380px; overflow-y: auto;">
                            <table class="grn-table" style="font-size: 12px;">
                                <thead>
                                    <tr>
                                        <th>Item & SKU</th>
                                        <th>Purchasing UOM</th>
                                        <th style="text-align: center;">UOM Conv</th>
                                        <th style="text-align: center;">Received Qty</th>
                                        <th style="text-align: right;">Unit Cost (₱) *</th>
                                        <th style="text-align: right;">Subtotal (₱)</th>
                                        <th style="text-align: center;">Trace / Lot</th>
                                        <th style="text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="directItemsTableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <div id="directNoItemsPrompt" style="padding: 24px; text-align: center; color: var(--grn-text-muted); font-size: 13px;">
                            <i class="ph ph-shopping-cart-simple" style="font-size: 32px; display: block; margin-bottom: 6px; color: #cbd5e1;"></i>
                            No items added yet. Pick a product from the catalog above to add receiving lines.
                        </div>
                    </div>

                    <!-- Card 6: Landed Cost Allocation Surcharges (Collapsible) -->
                    <div class="rfq-panel-card">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="font-size: 13px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-calculator" style="color: var(--grn-primary);"></i>
                                <span>Landed Cost Surcharges (Freight & Handling)</span>
                            </div>
                            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="toggleDirectLandedCost()">
                                <i class="ph ph-caret-down"></i> Surcharges
                            </button>
                        </div>

                        <div id="directLandedCostPanel" style="display: none; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Freight / Delivery Fee (₱)</label>
                                    <input type="number" id="directFreightFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Customs / Tariffs (₱)</label>
                                    <input type="number" id="directCustomsFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 4px;">
                                    <label style="font-size: 11px; font-weight: 600; color: #475569;">Port Handling (₱)</label>
                                    <input type="number" id="directHandlingFee" class="grn-search-input" style="padding-left: 8px;" value="0" min="0" step="0.01" oninput="calculateDirectTotals()">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card 7: Accounting Capitalization & Summary Total -->
                    <div class="rfq-panel-card" style="background: linear-gradient(135deg, rgba(250, 245, 255, 0.9), rgba(255, 255, 255, 0.95)); border: 1.5px solid #e9d5ff;">
                        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
                            <div>
                                <div style="font-size: 11.5px; font-weight: 700; color: #7e22ce; text-transform: uppercase;">Real-time Asset Valuation:</div>
                                <div style="font-family: monospace; font-size: 11px; color: #4c1d95;">
                                    [DR] 1200 Inventory Asset &bull; [CR] 2150 GRNI / Cash Outflow
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase;">Total Capitalized Value</div>
                                <div style="font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 800; color: var(--grn-primary);" id="directTotalValuationDisplay">₱0.00</div>
                            </div>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     SLIDE-OVER DRAWER: INBOUND RECEIVING & INSPECTION FOR EXISTING POs
     ========================================================================= -->
<div class="grn-drawer-overlay" id="grnInspectionDrawer">
    <div class="grn-drawer">
        <div class="grn-drawer-header">
            <div>
                <h2 style="margin: 0; font-size: 18px; font-weight: 800; color: var(--grn-text-strong); display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-tray-arrow-down" style="color: var(--grn-primary);"></i> Inbound Receiving Inspection
                </h2>
                <div style="font-size: 12px; color: var(--grn-text-muted);" id="drawerPoSubtitle">Receiving fulfillment for Purchase Order</div>
            </div>
            <button class="grn-drawer-close" onclick="closeInspectionDrawer()"><i class="ph ph-x"></i></button>
        </div>

        <div class="grn-drawer-body">
            <!-- Inspection Header Info -->
            <div class="rfq-panel-card">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div>
                        <span style="font-size: 11px; color: #64748b;">Supplier / Vendor:</span>
                        <div style="font-weight: 700; font-size: 13px;" id="drawerVendorName">--</div>
                    </div>
                    <div>
                        <span style="font-size: 11px; color: #64748b;">Delivery Slip / DR # *</span>
                        <input type="text" id="drawerDeliverySlip" class="grn-search-input" style="padding-left: 10px;" placeholder="e.g. DR-SMF-9941">
                    </div>
                </div>
            </div>

            <!-- Over-Tolerance Warning Box -->
            <div id="drawerOverToleranceBox" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 12px; font-size: 12px; color: #92400e; align-items: center; justify-content: space-between;">
                <div><strong>⚠️ Over-Receiving Warning:</strong> Delivered units exceed standard 5% tolerance. Supervisor Override required.</div>
                <button type="button" class="hr-btn hr-btn-sm hr-btn-primary" onclick="openSupervisorOverrideModal()">Authorize</button>
            </div>

            <!-- Line Items Table -->
            <div class="rfq-panel-card">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 8px;">Line Items & Quantity Inspection Triad</div>
                <div style="overflow-x: auto;">
                    <table class="grn-table" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Item & SKU</th>
                                <th style="text-align: center;">Ordered</th>
                                <th style="text-align: center;">Prior Recv</th>
                                <th style="text-align: center;">Delivered</th>
                                <th style="text-align: center;">Accepted</th>
                                <th style="text-align: center;">Rejected</th>
                                <th>Rejection Reason</th>
                                <th style="text-align: right;">Unit Price</th>
                                <th style="text-align: center;">Short-Close</th>
                            </tr>
                        </thead>
                        <tbody id="drawerLinesTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grn-drawer-footer">
            <div>
                <span style="font-size: 11px; color: #64748b;">Total Received Asset Value:</span>
                <div style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: var(--grn-primary);" id="drawerTotalAssetValue">₱0.00</div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeInspectionDrawer()">Cancel</button>
                <button type="button" class="hr-btn hr-btn-success" id="btnConfirmDrawerReceive" onclick="commitInspectionReceipt()">
                    <i class="ph ph-check-circle"></i> ✓ Complete Stock In
                </button>
            </div>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: SUPERVISOR APPROVAL OVERRIDE
     ========================================================================= -->
<div class="grn-modal-backdrop" id="supervisorOverrideModal">
    <div class="grn-dialog">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-shield-check" style="font-size: 22px; color: #b45309;"></i>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Supervisor Over-Delivery Override</h3>
            </div>
            <button class="grn-drawer-close" onclick="closeSupervisorOverrideModal()"><i class="ph ph-x"></i></button>
        </div>
        <div class="grn-dialog-body">
            <div style="font-size: 12px; color: #64748b;">
                Delivered count exceeds standard 5% tolerance allowance. Please enter supervisor authorization PIN to permit inventory reception.
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Supervisor PIN (Default: 1234) *</label>
                <input type="password" id="supervisorPinInput" class="grn-search-input" style="padding-left: 10px;" placeholder="Enter PIN">
            </div>
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Supervisor Name</label>
                <input type="text" id="supervisorNameInput" class="grn-search-input" style="padding-left: 10px;" value="Engr. Roberto Santos (Warehouse Manager)">
            </div>
        </div>
        <div class="grn-dialog-footer">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeSupervisorOverrideModal()">Cancel</button>
            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="verifySupervisorPin()">Authorize</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: TRACEABILITY (Batch, Lot, Expiry)
     ========================================================================= -->
<div class="grn-modal-backdrop" id="traceModal">
    <div class="grn-dialog">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-barcode" style="font-size: 22px; color: var(--grn-primary);"></i>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700;">Batch & Traceability Parameters</h3>
            </div>
            <button class="grn-drawer-close" onclick="closeTraceModal()"><i class="ph ph-x"></i></button>
        </div>
        <div class="grn-dialog-body">
            <div style="display: flex; flex-direction: column; gap: 6px;">
                <label style="font-size: 11.5px; font-weight: 600;">Batch / Lot Number *</label>
                <input type="text" id="traceLotInput" class="grn-search-input" style="padding-left: 10px;">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11.5px; font-weight: 600;">Manufacturing Date (MFG)</label>
                    <input type="date" id="traceMfgInput" class="grn-search-input" style="padding-left: 10px;">
                </div>
                <div style="display: flex; flex-direction: column; gap: 4px;">
                    <label style="font-size: 11.5px; font-weight: 600;">Expiration Date (EXP) *</label>
                    <input type="date" id="traceExpInput" class="grn-search-input" style="padding-left: 10px;">
                </div>
            </div>
        </div>
        <div class="grn-dialog-footer">
            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="saveTraceModalData()">Save Traceability</button>
        </div>
    </div>
</div>

<!-- =========================================================================
     MODAL: PURCHASE ORDER LINE ITEMS VIEWER & BUILDER DISPATCHER
     ========================================================================= -->
<div class="grn-modal-backdrop" id="poItemsViewerModal">
    <div class="grn-dialog wide" style="max-height: 88vh;">
        <div class="grn-dialog-header">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: 10px; background: var(--grn-purple-bg); color: var(--grn-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--grn-text-strong);" id="poModalPoRef">PO-XXXX-XXXX</h3>
                        <span id="poModalStatus"></span>
                    </div>
                    <div style="font-size: 12px; color: var(--grn-text-muted);" id="poModalVendor">Vendor / Supplier</div>
                </div>
            </div>
            <button class="grn-drawer-close" onclick="closePoItemsModal()"><i class="ph ph-x"></i></button>
        </div>

        <div class="grn-dialog-body" style="gap: 16px;">
            <!-- Meta KPI Strip -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 10px; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Issued Date</span>
                    <div style="font-size: 12.5px; font-weight: 700; color: #0f172a;" id="poModalOrderDate">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Expected Delivery</span>
                    <div style="font-size: 12.5px; font-weight: 700; color: #0284c7;" id="poModalDeliveryDate">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Delivery Location</span>
                    <div style="font-size: 12px; font-weight: 600; color: #334155;" id="poModalLocation">--</div>
                </div>
                <div>
                    <span style="font-size: 10.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Gross Total</span>
                    <div style="font-size: 13.5px; font-weight: 800; color: #047857;" class="grn-currency" id="poModalTotalValue">₱0.00</div>
                </div>
            </div>

            <!-- Items Table Container -->
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #ffffff;">
                <div style="padding: 10px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 12.5px; font-weight: 700; color: var(--grn-text-strong);">
                        <i class="ph ph-package"></i> Ordered Line Items (<span id="poModalTotalItems">0 items</span>)
                    </span>
                    <span style="font-size: 12px; font-weight: 600; color: #b45309;">
                        Open Backlog: <strong id="poModalTotalOpen">0 units</strong>
                    </span>
                </div>
                <div style="overflow-x: auto; max-height: 360px;">
                    <table class="grn-table" style="margin: 0;">
                        <thead>
                            <tr style="background: #ffffff;">
                                <th>SKU</th>
                                <th>Item Name & Description</th>
                                <th style="text-align: center;">Ordered</th>
                                <th style="text-align: center;">Received</th>
                                <th style="text-align: center;">Pending / Open</th>
                                <th style="text-align: right;">Unit Cost</th>
                                <th style="text-align: right;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="poModalItemsTableBody">
                            <!-- Populated dynamically via JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="grn-dialog-footer" style="justify-content: space-between;">
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closePoItemsModal()">
                Close
            </button>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" id="poModalBuilderBtn" onclick="pushPoToReceivingBuilder(currentInspectedPoNumber)">
                    <i class="ph ph-arrow-fat-line-right"></i> Move to Receiving Builder (Tab 3)
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Element -->
<div id="grnToast" style="position: fixed; bottom: 24px; right: 28px; z-index: 9999; display: none; padding: 12px 20px; border-radius: 10px; background: #0f172a; color: #ffffff; font-size: 13px; font-weight: 600; box-shadow: 0 10px 30px rgba(0,0,0,0.25); display: flex; align-items: center; gap: 10px; opacity: 0; transition: opacity 0.25s ease;">
    <i id="grnToastIcon" class="ph ph-check-circle" style="font-size: 20px; color: #10b981;"></i>
    <span id="grnToastMsg">Notification message</span>
</div>

@push('scripts')
<script>
/**
 * ============================================================================
 * RMS STOCK IN & RECEIVING STATE ORCHESTRATOR
 * Features 3 Primary Tabs:
 * 1. List of the PO (Master PO list)
 * 2. Pending PO (Backlogs & Open queue)
 * 3. Create new PO to received (Bypass PO via RFQ Builder Layout)
 * ============================================================================
 */
window.StockInStore = {
    purchaseOrders: [],
    products: [],
    vendors: [],
    goodsReceipts: [],
    
    // Direct Bypass Inbound Form State (Tab 3)
    directForm: {
        refNumber: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        customStallName: '',
        reasonCode: 'Direct Spot Purchase',
        deliverySlip: '',
        carrier: '',
        receivedAt: '',
        deliveryLocation: 'Central Commissary - Receiving Dock 1',
        settlementMode: 'IMMEDIATE_COD',
        paymentMethod: 'Cash / Currency',
        paymentRef: '',
        freight: 0,
        customs: 0,
        handling: 0,
        items: []
    },

    // Active Inspection Drawer State (for receiving existing POs)
    activePoReceiving: null,
    activeTraceLineIndex: null
};

document.addEventListener('DOMContentLoaded', () => {
    initStockInModule();

    // Hotkey bindings
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            switchGrnTab('tab-create-po');
        } else if (e.key === 'F10') {
            const activeTab = document.querySelector('.tabs-wrapper .tab-btn.active')?.id;
            if (activeTab === 'tabBtnCreatePo') {
                e.preventDefault();
                submitDirectReceivingOrder();
            }
        } else if (e.key === 'Escape') {
            closeInspectionDrawer();
            closeSupervisorOverrideModal();
            closeTraceModal();
            closePoItemsModal();
        } else if (e.key === '/' && !['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) {
            e.preventDefault();
            const search = document.getElementById('poListSearchInput');
            if (search) search.focus();
        }
    });
});

let currentInspectedPoNumber = null;

function initStockInModule() {
    // 1. Prioritize Server-Hydrated Data from MySQL DB
    const serverPos = @json($initialPurchaseOrders ?? []);
    const serverProd = @json($initialProducts ?? []);
    const serverVend = @json($initialVendors ?? []);
    const serverGrn = @json($initialReceipts ?? []);

    if (serverPos && serverPos.length > 0) {
        window.StockInStore.purchaseOrders = serverPos.map(p => ({
            ...p,
            poNumber: p.po_number,
            poType: p.po_type,
            vendorId: p.vendor_id,
            vendorName: p.vendor_name,
            vendorTradeName: p.vendor_trade_name,
            orderDate: p.order_date,
            expectedDelivery: p.expected_delivery,
            deliveryLocation: p.delivery_location,
            paymentStatus: p.payment_status,
            paymentMethod: p.payment_method,
            grossTotal: parseFloat(p.gross_total) || 0,
            status: p.status,
            items: (p.items || []).map(it => ({
                ...it,
                name: it.item_name,
                unit: it.uom,
                quantity: parseFloat(it.quantity) || 0,
                unitPrice: parseFloat(it.unit_price) || 0,
                receivedQuantity: parseFloat(it.received_quantity) || 0,
            }))
        }));
    } else {
        const rawPos = localStorage.getItem('rms_purchase_orders');
        window.StockInStore.purchaseOrders = rawPos ? JSON.parse(rawPos) : getFallbackPurchaseOrders();
    }

    if (serverProd && serverProd.length > 0) {
        window.StockInStore.products = serverProd.map(p => ({
            ...p,
            unit: p.uom,
            stock: parseFloat(p.current_stock) || 0,
            costPrice: parseFloat(p.cost_price) || 0,
            sellingPrice: parseFloat(p.selling_price) || 0
        }));
    } else {
        const rawProd = localStorage.getItem('rms_inventory_products');
        window.StockInStore.products = rawProd ? JSON.parse(rawProd) : getFallbackProducts();
    }

    if (serverVend && serverVend.length > 0) {
        window.StockInStore.vendors = serverVend.map(v => ({
            id: v.vendor_code,
            code: v.vendor_code,
            legalName: v.legal_name,
            tradeName: v.trade_name,
            category: v.category,
            contactPerson: v.contact_person,
            email: v.email,
            phone: v.phone,
            address: v.address
        }));
    } else {
        const rawVend = localStorage.getItem('rms_vendors');
        window.StockInStore.vendors = rawVend ? JSON.parse(rawVend) : getFallbackVendors();
    }

    window.StockInStore.goodsReceipts = (serverGrn && serverGrn.length > 0) ? serverGrn : (JSON.parse(localStorage.getItem('rms_goods_receipts') || '[]'));

    // Cache to localStorage for offline resilience
    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.StockInStore.purchaseOrders));
    localStorage.setItem('rms_inventory_products', JSON.stringify(window.StockInStore.products));
    localStorage.setItem('rms_vendors', JSON.stringify(window.StockInStore.vendors));
    localStorage.setItem('rms_goods_receipts', JSON.stringify(window.StockInStore.goodsReceipts));

    // Populate UI Dropdowns
    populateVendorDropdown();
    populateCatalogPicker();
    resetDirectReceivingForm();

    // Render Views
    renderPoMasterList();
    renderPendingPoList();
    updateHeaderKpis();
}

/**
 * ----------------------------------------------------------------------------
 * PO LINE ITEMS VIEWER & RECEIVING BUILDER DISPATCHER
 * ----------------------------------------------------------------------------
 */
function openPoItemsModal(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    currentInspectedPoNumber = poNumber;

    document.getElementById('poModalPoRef').textContent = po.poNumber;
    document.getElementById('poModalVendor').textContent = po.vendorTradeName || po.vendorName || 'Vendor';
    document.getElementById('poModalStatus').innerHTML = getOrderStatusBadge(po.status);
    document.getElementById('poModalOrderDate').textContent = po.orderDate || 'N/A';
    document.getElementById('poModalDeliveryDate').textContent = po.expectedDelivery || 'Immediate / Scheduled';
    document.getElementById('poModalLocation').textContent = po.deliveryLocation || 'Central Commissary';

    const items = po.items || [];
    const tbody = document.getElementById('poModalItemsTableBody');
    if (!tbody) return;

    let totalOrdered = 0;
    let totalReceived = 0;
    let totalOpen = 0;
    let totalValue = 0;

    tbody.innerHTML = items.map((it) => {
        const ord = parseFloat(it.quantity) || 0;
        const rec = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ord - rec);
        const cost = parseFloat(it.unitPrice) || 0;
        const subtotal = ord * cost;

        totalOrdered += ord;
        totalReceived += rec;
        totalOpen += open;
        totalValue += subtotal;

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${it.sku || 'N/A'}</strong></td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${it.name || it.itemName}</div>
                    ${it.specs ? `<div style="font-size: 11px; color: var(--grn-text-muted);">${it.specs}</div>` : ''}
                </td>
                <td style="text-align: center; font-weight: 600;">${ord} ${it.unit || it.uom || 'Unit'}</td>
                <td style="text-align: center; font-weight: 600; color: #047857;">${rec} ${it.unit || it.uom || 'Unit'}</td>
                <td style="text-align: center; font-weight: 700; color: ${open > 0 ? '#b45309' : '#047857'};">
                    ${open > 0 ? `${open} ${it.unit || it.uom || 'Unit'}` : '<span class="hr-badge hr-badge-success" style="font-size: 10px;">Fulfilled</span>'}
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(cost)}</td>
                <td style="text-align: right; font-weight: 600;" class="grn-currency">₱${formatMoney(subtotal)}</td>
            </tr>
        `;
    }).join('');

    document.getElementById('poModalTotalItems').textContent = `${items.length} items`;
    document.getElementById('poModalTotalValue').textContent = `₱${formatMoney(totalValue)}`;
    document.getElementById('poModalTotalOpen').textContent = `${totalOpen} units`;

    const canReceive = ['Approved / Issued', 'Partially Received'].includes(po.status);
    const builderBtn = document.getElementById('poModalBuilderBtn');
    if (builderBtn) {
        builderBtn.style.display = canReceive ? 'inline-flex' : 'none';
    }

    document.getElementById('poItemsViewerModal')?.classList.add('active');
}

function closePoItemsModal() {
    document.getElementById('poItemsViewerModal')?.classList.remove('active');
}

function pushPoToReceivingBuilder(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) {
        showToast('Purchase Order not found.', 'error');
        return;
    }

    // 1. Close modal
    closePoItemsModal();

    // 2. Switch tab to Tab 3 (Create new PO to received)
    switchGrnTab('tab-create-po');

    // 3. Populate Direct Form State with PO Data
    window.StockInStore.directForm.poNumber = po.poNumber;
    window.StockInStore.directForm.vendorId = po.vendorId || '';
    window.StockInStore.directForm.vendorName = po.vendorName || '';
    window.StockInStore.directForm.vendorTradeName = po.vendorTradeName || po.vendorName || '';
    window.StockInStore.directForm.deliveryLocation = po.deliveryLocation || 'Central Commissary - Receiving Dock 1';
    window.StockInStore.directForm.settlementMode = po.paymentStatus || 'PURCHASE_ORDER';
    window.StockInStore.directForm.paymentMethod = po.paymentMethod || 'Trade Credit (Net 30/15)';
    window.StockInStore.directForm.reasonCode = `PO Intake: ${po.poNumber}`;

    // 4. Map PO Items to Builder Line Items (prefill with open/pending balance)
    const poItems = po.items || [];
    window.StockInStore.directForm.items = poItems.map(it => {
        const ord = parseFloat(it.quantity) || 0;
        const rec = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ord - rec);

        return {
            sku: it.sku || '',
            name: it.name || it.itemName || 'Item',
            category: it.category || 'Raw Ingredients',
            unit: it.unit || it.uom || 'Unit',
            purchasingUnit: it.unit || it.uom || 'Unit',
            uomMultiplier: 1.0,
            orderedQty: ord,
            alreadyReceived: rec,
            receivedQty: open > 0 ? open : ord,
            unitCost: parseFloat(it.unitPrice) || 0,
            lotNumber: '',
            expiryDate: '',
            notes: `From PO ${po.poNumber}`
        };
    });

    // 5. Update UI Controls in Tab 3
    const refDisplay = document.getElementById('directRecRefDisplay');
    if (refDisplay) refDisplay.textContent = `GRN-${po.poNumber}`;

    const vendorSelect = document.getElementById('directVendorSelect');
    if (vendorSelect) {
        let exists = Array.from(vendorSelect.options).some(o => o.value === po.vendorId);
        if (!exists && po.vendorId) {
            const opt = document.createElement('option');
            opt.value = po.vendorId;
            opt.textContent = `${po.vendorTradeName || po.vendorName} (From PO)`;
            vendorSelect.appendChild(opt);
        }
        vendorSelect.value = po.vendorId || '';
    }

    const pill = document.getElementById('directVendorPillBox');
    const pillName = document.getElementById('directVendorPillName');
    if (pill && pillName) {
        pillName.textContent = po.vendorTradeName || po.vendorName;
        pill.style.display = 'block';
    }

    const locSelect = document.getElementById('directDeliveryLocation');
    if (locSelect) locSelect.value = po.deliveryLocation || 'Central Commissary - Receiving Dock 1';

    // Show top banner in Tab 3 indicating PO fulfillment mode
    let poBanner = document.getElementById('builderPoFulfillmentBanner');
    if (!poBanner) {
        const rfqGrid = document.querySelector('.rfq-builder-workspace-grid');
        if (rfqGrid) {
            poBanner = document.createElement('div');
            poBanner.id = 'builderPoFulfillmentBanner';
            poBanner.style.cssText = 'margin-bottom: 14px; padding: 12px 18px; border-radius: 12px; background: linear-gradient(135deg, rgba(147, 51, 234, 0.08), rgba(236, 72, 153, 0.08)); border: 1.5px solid rgba(168, 85, 247, 0.35); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;';
            rfqGrid.parentNode.insertBefore(poBanner, rfqGrid);
        }
    }
    if (poBanner) {
        poBanner.style.display = 'flex';
        poBanner.innerHTML = `
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 10px; background: var(--grn-purple-bg); color: var(--grn-primary); display: flex; align-items: center; justify-content: center; font-size: 20px;">
                    <i class="ph ph-tray-arrow-down"></i>
                </div>
                <div>
                    <strong style="color: var(--grn-primary); font-size: 14px;">Fulfilling Active Purchase Order: ${po.poNumber}</strong>
                    <div style="font-size: 12px; color: var(--grn-text-muted);">
                        Vendor: <strong>${po.vendorTradeName || po.vendorName}</strong> &bull; Line items loaded with remaining open quantities.
                    </div>
                </div>
            </div>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetToAdHocBuilder()">
                <i class="ph ph-arrow-counter-clockwise"></i> Clear / Reset to Ad-Hoc
            </button>
        `;
    }

    // Render items & totals
    renderDirectItemsTable();
    calculateDirectTotals();

    showToast(`✓ Order ${po.poNumber} loaded into Receiving Builder. Review quantities and submit intake!`, 'success');
}

function resetToAdHocBuilder() {
    const poBanner = document.getElementById('builderPoFulfillmentBanner');
    if (poBanner) poBanner.style.display = 'none';
    resetDirectReceivingForm();
    showToast('Reset builder to standard ad-hoc intake mode.', 'info');
}

async function refreshStockInDataFromServer() {
    try {
        const res = await fetch('/inventory/api/stock-in-data');
        const json = await res.json();
        if (json.success && json.data) {
            const serverPos = json.data.purchaseOrders || [];
            window.StockInStore.purchaseOrders = serverPos.map(p => ({
                ...p,
                poNumber: p.po_number,
                poType: p.po_type,
                vendorId: p.vendor_id,
                vendorName: p.vendor_name,
                vendorTradeName: p.vendor_trade_name,
                orderDate: p.order_date,
                expectedDelivery: p.expected_delivery,
                deliveryLocation: p.delivery_location,
                paymentStatus: p.payment_status,
                paymentMethod: p.payment_method,
                grossTotal: parseFloat(p.gross_total) || 0,
                status: p.status,
                items: (p.items || []).map(it => ({
                    ...it,
                    name: it.item_name,
                    unit: it.uom,
                    quantity: parseFloat(it.quantity) || 0,
                    unitPrice: parseFloat(it.unit_price) || 0,
                    receivedQuantity: parseFloat(it.received_quantity) || 0,
                }))
            }));
            window.StockInStore.vendors = (json.data.vendors || []).map(v => ({
                id: v.vendor_code,
                code: v.vendor_code,
                legalName: v.legal_name,
                tradeName: v.trade_name,
                category: v.category,
                contactPerson: v.contact_person,
                email: v.email,
                phone: v.phone,
                address: v.address
            }));
            window.StockInStore.products = (json.data.products || []).map(p => ({
                ...p,
                unit: p.uom,
                stock: parseFloat(p.current_stock) || 0,
                costPrice: parseFloat(p.cost_price) || 0,
                sellingPrice: parseFloat(p.selling_price) || 0
            }));
            window.StockInStore.goodsReceipts = json.data.goodsReceipts || [];

            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.StockInStore.purchaseOrders));
            localStorage.setItem('rms_vendors', JSON.stringify(window.StockInStore.vendors));
            localStorage.setItem('rms_inventory_products', JSON.stringify(window.StockInStore.products));
            localStorage.setItem('rms_goods_receipts', JSON.stringify(window.StockInStore.goodsReceipts));

            renderPoMasterList();
            renderPendingPoList();
            updateHeaderKpis();
            resetDirectReceivingForm();
        }
    } catch(e) {
        console.warn('Could not refresh from server, using local state:', e);
    }
}

/**
 * ----------------------------------------------------------------------------
 * PRIMARY TABS NAVIGATION SWITCHER
 * ----------------------------------------------------------------------------
 */
function switchGrnTab(tabId) {
    document.querySelectorAll('.grn-tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tabs-wrapper .tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(tabId);
    if (panel) panel.style.display = 'block';

    if (tabId === 'tab-po-list') {
        document.getElementById('tabBtnPoList')?.classList.add('active');
        renderPoMasterList();
    } else if (tabId === 'tab-pending-po') {
        document.getElementById('tabBtnPendingPo')?.classList.add('active');
        renderPendingPoList();
    } else if (tabId === 'tab-create-po') {
        document.getElementById('tabBtnCreatePo')?.classList.add('active');
    }
}

/**
 * ----------------------------------------------------------------------------
 * KPI SUMMARY STATS
 * ----------------------------------------------------------------------------
 */
function updateHeaderKpis() {
    const pos = window.StockInStore.purchaseOrders || [];
    const totalPos = pos.length;
    const pendingPos = pos.filter(p => ['Approved / Issued', 'Partially Received'].includes(p.status)).length;
    const completedPos = pos.filter(p => p.status === 'Fully Received').length;

    const receipts = window.StockInStore.goodsReceipts || [];
    const totalReceivedVal = receipts.reduce((acc, r) => acc + (parseFloat(r.totalValuation) || 0), 0);

    document.getElementById('kpiTotalPos').textContent = totalPos;
    document.getElementById('kpiPendingPos').textContent = pendingPos;
    document.getElementById('kpiCompletedPos').textContent = completedPos;
    document.getElementById('kpiTotalReceivedValue').textContent = `₱${formatMoney(totalReceivedVal)}`;

    // Tab badges
    document.getElementById('badgePoListCount').textContent = totalPos;
    document.getElementById('badgePendingPoCount').textContent = pendingPos;
}

/**
 * ----------------------------------------------------------------------------
 * TAB 1: LIST OF THE PO (Master List connected to Purchase Module)
 * ----------------------------------------------------------------------------
 */
function renderPoMasterList() {
    const tbody = document.getElementById('poMasterTableBody');
    if (!tbody) return;

    const query = (document.getElementById('poListSearchInput')?.value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('poListStatusFilter')?.value || 'ALL';
    const typeFilter = document.getElementById('poListTypeFilter')?.value || 'ALL';

    const pos = (window.StockInStore.purchaseOrders || []).filter(p => {
        if (query) {
            const mPo = (p.poNumber || '').toLowerCase().includes(query);
            const mVnd = ((p.vendorTradeName || '') + ' ' + (p.vendorName || '')).toLowerCase().includes(query);
            if (!mPo && !mVnd) return false;
        }
        if (statusFilter !== 'ALL' && p.status !== statusFilter) return false;
        if (typeFilter !== 'ALL' && (p.poType || 'vendor') !== typeFilter) return false;
        return true;
    });

    if (pos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: var(--grn-text-muted);">
                    <i class="ph ph-receipt" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                    No purchase orders found matching the filter criteria.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pos.map(p => {
        const items = p.items || [];
        const gross = items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        const canReceive = ['Approved / Issued', 'Partially Received'].includes(p.status);

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${p.poNumber}</strong></td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.orderDate || 'N/A'}</td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${p.vendorTradeName || p.vendorName}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${p.poType === 'wet_market' ? 'Wet Market' : (p.poType === 'ad_hoc' ? 'Direct / Bypass' : 'Commercial')}</span></td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.deliveryLocation || 'Central Warehouse'}</td>
                <td style="text-align: center;">
                    <button type="button" class="hr-badge hr-badge-purple" style="cursor: pointer; border: 1px solid var(--grn-border); font-family: inherit; font-size: 11.5px; padding: 4px 8px; transition: all 0.2s;" onclick="openPoItemsModal('${p.poNumber}')" title="Click to view PO line items">
                        <i class="ph ph-list-bullets"></i> ${items.length} items
                    </button>
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(gross)}</td>
                <td style="text-align: center;">${getPaymentBadge(p.paymentStatus)}</td>
                <td style="text-align: center;">${getOrderStatusBadge(p.status)}</td>
                <td style="text-align: center;">
                    <div style="display: inline-flex; align-items: center; gap: 5px; flex-wrap: wrap; justify-content: center;">
                        <button type="button" class="hr-btn hr-btn-sm hr-btn-secondary" onclick="openPoItemsModal('${p.poNumber}')" title="Inspect items & quantities">
                            <i class="ph ph-eye"></i> View Items
                        </button>
                        ${canReceive ? `
                        <button type="button" class="hr-btn hr-btn-sm hr-btn-primary" onclick="pushPoToReceivingBuilder('${p.poNumber}')" title="Move this PO into Receiving Builder (Tab 3)">
                            <i class="ph ph-arrow-fat-line-right"></i> To Builder
                        </button>
                        <button type="button" class="hr-btn hr-btn-sm hr-btn-secondary" onclick="openReceiveForPo('${p.poNumber}')" title="Direct Dock Check-In Drawer">
                            <i class="ph ph-tray-arrow-down"></i> Dock
                        </button>` : ''}
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function getPaymentBadge(status) {
    status = status || 'Unpaid';
    if (status.includes('Paid in Full')) return `<span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Paid</span>`;
    if (status.includes('Partial')) return `<span class="hr-badge hr-badge-warning"><i class="ph ph-clock"></i> Partial</span>`;
    return `<span class="hr-badge hr-badge-info"><i class="ph ph-credit-card"></i> On Credit</span>`;
}

function getOrderStatusBadge(status) {
    status = status || 'Draft';
    if (status.includes('Fully Received')) return `<span class="hr-badge hr-badge-success"><i class="ph ph-check-circle"></i> Fully Received</span>`;
    if (status.includes('Partially Received')) return `<span class="hr-badge hr-badge-warning"><i class="ph ph-clock-countdown"></i> Partial Backlog</span>`;
    if (status.includes('Approved')) return `<span class="hr-badge hr-badge-purple"><i class="ph ph-truck"></i> Ready to Receive</span>`;
    if (status.includes('Short-Closed')) return `<span class="hr-badge hr-badge-neutral"><i class="ph ph-x-circle"></i> Short-Closed</span>`;
    return `<span class="hr-badge hr-badge-neutral">${status}</span>`;
}

/**
 * ----------------------------------------------------------------------------
 * TAB 2: PENDING PO (Backlogs & Open Deliveries Queue)
 * ----------------------------------------------------------------------------
 */
function renderPendingPoList() {
    const tbody = document.getElementById('pendingPoTableBody');
    if (!tbody) return;

    const pendingPos = (window.StockInStore.purchaseOrders || []).filter(p => ['Approved / Issued', 'Partially Received'].includes(p.status));

    if (pendingPos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; padding: 40px; color: var(--grn-text-muted);">
                    <i class="ph ph-check-circle" style="font-size: 32px; display: block; margin-bottom: 8px; color: #10b981;"></i>
                    No pending orders or backlogs! All purchase orders have been received.
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = pendingPos.map(p => {
        const items = p.items || [];
        const totalOrdered = items.reduce((acc, it) => acc + (parseFloat(it.quantity) || 0), 0);
        const totalReceived = items.reduce((acc, it) => acc + (parseFloat(it.receivedQuantity) || 0), 0);
        const backlogQty = Math.max(0, totalOrdered - totalReceived);

        const backlogValue = items.reduce((acc, it) => {
            const ord = parseFloat(it.quantity) || 0;
            const rec = parseFloat(it.receivedQuantity) || 0;
            const open = Math.max(0, ord - rec);
            return acc + (open * (parseFloat(it.unitPrice) || 0));
        }, 0);

        return `
            <tr>
                <td><strong class="grn-mono" style="color: var(--grn-primary);">${p.poNumber}</strong></td>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${p.vendorTradeName || p.vendorName}</div>
                </td>
                <td style="font-size: 12px; font-weight: 600; color: #0284c7;">${p.expectedDelivery || 'Scheduled'}</td>
                <td style="font-size: 11.5px; color: var(--grn-text-muted);">${p.deliveryLocation || 'Central Warehouse'}</td>
                <td style="text-align: center; font-weight: 600;">${totalOrdered}</td>
                <td style="text-align: center; font-weight: 600; color: #047857;">${totalReceived}</td>
                <td style="text-align: center; font-weight: 800; color: #b45309; font-size: 13.5px;">${backlogQty}</td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(backlogValue)}</td>
                <td style="text-align: center;">${getOrderStatusBadge(p.status)}</td>
                <td style="text-align: center;">
                    <button class="hr-btn hr-btn-sm hr-btn-primary" onclick="openReceiveForPo('${p.poNumber}')">
                        <i class="ph ph-tray-arrow-down"></i> Receive Inbound
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

/**
 * ----------------------------------------------------------------------------
 * TAB 3: CREATE NEW PO TO RECEIVED (Bypass PO via RFQ Builder Layout)
 * ----------------------------------------------------------------------------
 */
function resetDirectReceivingForm() {
    const year = new Date().getFullYear();
    const count = (window.StockInStore.goodsReceipts || []).length + 1;
    const ref = `DIR-REC-${year}-${String(count).padStart(4, '0')}`;

    window.StockInStore.directForm = {
        refNumber: ref,
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        customStallName: '',
        reasonCode: 'Direct Spot Purchase',
        deliverySlip: '',
        carrier: '',
        receivedAt: new Date().toISOString().slice(0, 16),
        deliveryLocation: 'Central Commissary - Receiving Dock 1',
        settlementMode: 'IMMEDIATE_COD',
        paymentMethod: 'Cash / Currency',
        paymentRef: '',
        freight: 0,
        customs: 0,
        handling: 0,
        items: []
    };

    document.getElementById('directRecRefDisplay').textContent = ref;
    document.getElementById('directReceivedAt').value = window.StockInStore.directForm.receivedAt;
    document.getElementById('directDeliverySlip').value = '';
    document.getElementById('directCarrier').value = '';
    document.getElementById('directCustomStallName').value = '';
    document.getElementById('directVendorSelect').value = '';
    document.getElementById('directVendorPillBox').style.display = 'none';

    renderDirectItemsTable();
    calculateDirectTotals();
}

function populateVendorDropdown() {
    const select = document.getElementById('directVendorSelect');
    if (!select) return;

    const vendors = window.StockInStore.vendors || [];
    select.innerHTML = '<option value="">-- Choose Approved Vendor from Masterlist --</option>' +
        vendors.map(v => `<option value="${v.id || v.code}">${v.tradeName || v.legalName} (${v.category || 'Supplier'})</option>`).join('');
}

function populateCatalogPicker() {
    const picker = document.getElementById('directCatalogPicker');
    if (!picker) return;

    const products = window.StockInStore.products || [];
    picker.innerHTML = '<option value="">-- Quick Pick from Product Catalog (Item Master) --</option>' +
        products.map(p => `<option value="${p.sku}">[${p.sku}] ${p.name} &bull; ${p.category} (Stock: ${p.stock} ${p.unit}) - Cost: ₱${formatMoney(p.costPrice)}</option>`).join('');
}

function handleDirectVendorSelect(vendorId) {
    if (!vendorId) {
        document.getElementById('directVendorPillBox').style.display = 'none';
        window.StockInStore.directForm.vendorId = '';
        window.StockInStore.directForm.vendorName = '';
        return;
    }

    const vendor = (window.StockInStore.vendors || []).find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    window.StockInStore.directForm.vendorId = vendor.id || vendor.code;
    window.StockInStore.directForm.vendorName = vendor.legalName || vendor.tradeName;
    window.StockInStore.directForm.vendorTradeName = vendor.tradeName || vendor.legalName;

    document.getElementById('directVendorTradeName').textContent = vendor.tradeName || vendor.legalName;
    document.getElementById('directVendorCategoryTag').textContent = `Category: ${vendor.category || 'General'}`;
    document.getElementById('directVendorAvatar').textContent = (vendor.tradeName || 'V').slice(0, 2).toUpperCase();
    document.getElementById('directVendorPillBox').style.display = 'flex';
}

function handleDirectSettlementChange(val) {
    window.StockInStore.directForm.settlementMode = val;
    const codBox = document.getElementById('directCodFields');
    if (codBox) {
        codBox.style.display = val === 'IMMEDIATE_COD' ? 'grid' : 'none';
    }
}

function toggleDirectLandedCost() {
    const panel = document.getElementById('directLandedCostPanel');
    if (!panel) return;
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function addPickedCatalogItem() {
    const picker = document.getElementById('directCatalogPicker');
    const sku = picker?.value;
    if (!sku) {
        alert('Please select a product from the catalog dropdown.');
        return;
    }

    const product = (window.StockInStore.products || []).find(p => p.sku === sku);
    if (!product) return;

    const idx = window.StockInStore.directForm.items.length + 1;
    window.StockInStore.directForm.items.push({
        id: `direct_item_${idx}_${Date.now()}`,
        sku: product.sku,
        name: product.name,
        category: product.category,
        unit: product.unit || 'Pc',
        purchasingUnit: product.unit || 'Pc',
        uomMultiplier: 1.0,
        receivedQty: 10,
        unitCost: parseFloat(product.costPrice) || 100.0,
        lotNumber: `LOT-DIR-${new Date().toISOString().slice(2,10).replace(/-/g,'')}-${idx}`,
        mfgDate: '',
        expiryDate: '',
        storageBin: 'DOCK-STAGING-01'
    });

    renderDirectItemsTable();
    calculateDirectTotals();
    picker.value = '';
}

function renderDirectItemsTable() {
    const tbody = document.getElementById('directItemsTableBody');
    const prompt = document.getElementById('directNoItemsPrompt');
    const countBadge = document.getElementById('directItemCountBadge');
    if (!tbody) return;

    const items = window.StockInStore.directForm.items || [];
    if (countBadge) countBadge.textContent = `${items.length} Items Added`;

    if (items.length === 0) {
        tbody.innerHTML = '';
        if (prompt) prompt.style.display = 'block';
        return;
    }
    if (prompt) prompt.style.display = 'none';

    tbody.innerHTML = items.map((it, idx) => {
        const subtotal = (parseFloat(it.receivedQty) || 0) * (parseFloat(it.unitCost) || 0);

        return `
            <tr>
                <td>
                    <div style="font-weight: 600; color: var(--grn-text-strong);">${it.name}</div>
                    <div class="grn-mono" style="font-size: 11px; color: var(--grn-text-muted);">SKU: ${it.sku} &bull; ${it.category}</div>
                </td>
                <td>
                    <select class="grn-select" style="padding: 4px 6px; font-size: 11.5px;" onchange="updateDirectUom(${idx}, this.value)">
                        <option value="${it.unit}" ${it.purchasingUnit === it.unit ? 'selected' : ''}>${it.unit} (Base)</option>
                        <option value="Box (24 Pcs)" ${it.purchasingUnit === 'Box (24 Pcs)' ? 'selected' : ''}>Box (24 Pcs)</option>
                        <option value="Carton (12 Pcs)" ${it.purchasingUnit === 'Carton (12 Pcs)' ? 'selected' : ''}>Carton (12 Pcs)</option>
                        <option value="Sack (50 Kg)" ${it.purchasingUnit === 'Sack (50 Kg)' ? 'selected' : ''}>Sack (50 Kg)</option>
                    </select>
                </td>
                <td style="text-align: center; font-size: 11.5px; font-weight: 600; color: #64748b;">
                    &times;${it.uomMultiplier || 1.0}
                </td>
                <td style="text-align: center;">
                    <input type="number" class="grn-search-input" style="width: 75px; text-align: center; padding: 4px;" value="${it.receivedQty}" min="0.01" step="any" oninput="updateDirectLineQty(${idx}, this.value)">
                </td>
                <td style="text-align: right;">
                    <input type="number" class="grn-search-input" style="width: 80px; text-align: right; padding: 4px;" value="${it.unitCost}" min="0.01" step="0.01" oninput="updateDirectLineCost(${idx}, this.value)">
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(subtotal)}</td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 3px 7px; font-size: 10.5px;" onclick="openTraceModalForLine(${idx})">
                        <i class="ph ph-barcode"></i> Lot
                    </button>
                </td>
                <td style="text-align: center;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #ef4444; padding: 3px 6px;" onclick="removeDirectLine(${idx})">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

function updateDirectUom(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;
    it.purchasingUnit = val;
    if (val === 'Box (24 Pcs)') it.uomMultiplier = 24.0;
    else if (val === 'Carton (12 Pcs)') it.uomMultiplier = 12.0;
    else if (val === 'Sack (50 Kg)') it.uomMultiplier = 50.0;
    else it.uomMultiplier = 1.0;

    renderDirectItemsTable();
    calculateDirectTotals();
}

function updateDirectLineQty(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (it) {
        it.receivedQty = Math.max(0.01, parseFloat(val) || 0);
        calculateDirectTotals();
    }
}

function updateDirectLineCost(idx, val) {
    const it = window.StockInStore.directForm.items[idx];
    if (it) {
        it.unitCost = Math.max(0.01, parseFloat(val) || 0);
        calculateDirectTotals();
    }
}

function removeDirectLine(idx) {
    window.StockInStore.directForm.items.splice(idx, 1);
    renderDirectItemsTable();
    calculateDirectTotals();
}

function calculateDirectTotals() {
    const items = window.StockInStore.directForm.items || [];
    const baseSubtotal = items.reduce((acc, it) => acc + ((parseFloat(it.receivedQty) || 0) * (parseFloat(it.unitCost) || 0)), 0);

    const freight = parseFloat(document.getElementById('directFreightFee')?.value) || 0;
    const customs = parseFloat(document.getElementById('directCustomsFee')?.value) || 0;
    const handling = parseFloat(document.getElementById('directHandlingFee')?.value) || 0;
    const totalSurcharges = freight + customs + handling;

    const grandValuation = baseSubtotal + totalSurcharges;
    window.StockInStore.directForm.totalValuation = grandValuation;

    document.getElementById('directTotalValuationDisplay').textContent = `₱${formatMoney(grandValuation)}`;
}

/**
 * Submit Direct Inbound Receiving (Bypassing standard PO creation)
 */
async function submitDirectReceivingOrder() {
    const form = window.StockInStore.directForm;

    // Validate inputs
    const slip = document.getElementById('directDeliverySlip')?.value;
    if (!slip) {
        alert('Supplier Delivery Slip / DR # is required.');
        document.getElementById('directDeliverySlip')?.focus();
        return;
    }

    const vendorId = document.getElementById('directVendorSelect')?.value;
    const customStall = document.getElementById('directCustomStallName')?.value;
    if (!vendorId && !customStall && !form.vendorName) {
        alert('Please select an approved vendor or enter a custom stall name.');
        return;
    }

    if (form.items.length === 0) {
        alert('Please add at least one line item from the product catalog.');
        return;
    }

    // Check mandatory unit cost
    const hasZeroCost = form.items.some(it => (parseFloat(it.unitCost) || 0) <= 0);
    if (hasZeroCost) {
        alert('Every line item must have a valid unit cost greater than ₱0.00 for inventory valuation.');
        return;
    }

    const finalVendorName = form.vendorTradeName || form.vendorName || customStall || 'Direct Spot Vendor';
    const recAt = document.getElementById('directReceivedAt')?.value || new Date().toISOString();
    const settlement = document.getElementById('directSettlementMode')?.value || 'IMMEDIATE_COD';
    const carrier = document.getElementById('directCarrier')?.value || 'Purchaser Transport';
    const loc = document.getElementById('directDeliveryLocation')?.value || 'Central Commissary - Receiving Dock 1';

    const freight = parseFloat(document.getElementById('directFreightFee')?.value) || 0;
    const customs = parseFloat(document.getElementById('directCustomsFee')?.value) || 0;
    const handling = parseFloat(document.getElementById('directHandlingFee')?.value) || 0;

    const payload = {
        po_number: form.poNumber || null,
        vendor_id: form.vendorId || vendorId || null,
        vendor_name: finalVendorName,
        vendor_trade_name: form.vendorTradeName || finalVendorName,
        delivery_slip: slip,
        carrier: carrier,
        reason_code: document.getElementById('directReasonCode')?.value || (form.poNumber ? `PO Fulfillment: ${form.poNumber}` : 'Direct Spot Purchase'),
        delivery_location: loc,
        settlement_mode: settlement,
        payment_method: document.getElementById('directPaymentMethod')?.value || 'Trade Credit (Net 30/15)',
        payment_ref: document.getElementById('directPaymentRef')?.value || '',
        freight: freight,
        customs: customs,
        handling: handling,
        items: form.items.map(it => ({
            sku: it.sku,
            name: it.name,
            unit: it.unit,
            purchasing_unit: it.purchasingUnit,
            uom_multiplier: it.uomMultiplier || 1.0,
            received_qty: parseFloat(it.receivedQty) || 0,
            unit_cost: parseFloat(it.unitCost) || 0,
            lot_number: it.lotNumber || null,
            expiry_date: it.expiryDate || null,
            notes: it.notes || null
        }))
    };

    // Pessimistic UI Locking
    const submitBtn = document.querySelector('button[onclick="submitDirectReceivingOrder()"]');
    const origHtml = submitBtn ? submitBtn.innerHTML : '';
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving to Database...';
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/inventory/api/receive-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Server rejected receiving transaction.');
        }

        showToast(`✓ Stock In Received! Voucher ${json.data.grn_number} posted to Stock Ledger & Live Inventory.`, 'success');

        const poBanner = document.getElementById('builderPoFulfillmentBanner');
        if (poBanner) poBanner.style.display = 'none';

        // Refresh live state from server
        await refreshStockInDataFromServer();
        switchGrnTab('tab-po-list');
    } catch(err) {
        console.error('Receiving transaction failed:', err);
        alert(`Error processing receiving transaction: ${err.message}`);
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origHtml;
        }
    }
}

/**
 * ----------------------------------------------------------------------------
 * EXISTING PO INBOUND RECEIVING INSPECTION DRAWER
 * ----------------------------------------------------------------------------
 */
function openReceiveForPo(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    window.StockInStore.activePoReceiving = JSON.parse(JSON.stringify(po));

    document.getElementById('drawerPoSubtitle').textContent = `Fulfilling Purchase Order: ${po.poNumber}`;
    document.getElementById('drawerVendorName').textContent = po.vendorTradeName || po.vendorName;
    document.getElementById('drawerDeliverySlip').value = '';
    document.getElementById('drawerOverToleranceBox').style.display = 'none';

    // Populate line items with open balance
    const items = po.items || [];
    const tbody = document.getElementById('drawerLinesTableBody');

    tbody.innerHTML = items.map((it, idx) => {
        const ordered = parseFloat(it.quantity) || 0;
        const prior = parseFloat(it.receivedQuantity) || 0;
        const open = Math.max(0, ordered - prior);

        return `
            <tr>
                <td>
                    <div style="font-weight: 600;">${it.name || it.itemName}</div>
                    <div class="grn-mono" style="font-size: 11px; color: #64748b;">SKU: ${it.sku} &bull; ${it.unit || it.uom}</div>
                </td>
                <td style="text-align: center; font-weight: 600;">${ordered}</td>
                <td style="text-align: center; color: #047857; font-weight: 600;">${prior}</td>
                <td style="text-align: center;">
                    <input type="number" class="grn-search-input" style="width: 70px; text-align: center; padding: 4px;" id="lineDelivered_${idx}" value="${open}" oninput="calcDrawerTriad(${idx})">
                </td>
                <td style="text-align: center;">
                    <input type="number" class="grn-search-input" style="width: 70px; text-align: center; padding: 4px; border-color: #10b981; color: #047857;" id="lineAccepted_${idx}" value="${open}" oninput="calcDrawerTriad(${idx})">
                </td>
                <td style="text-align: center;">
                    <input type="number" class="grn-search-input" style="width: 65px; text-align: center; padding: 4px; border-color: #ef4444; color: #b91c1c;" id="lineRejected_${idx}" value="0" oninput="calcDrawerTriad(${idx})">
                </td>
                <td>
                    <select class="grn-select" style="padding: 4px 6px; font-size: 11px;" id="lineReason_${idx}">
                        <option value="">None</option>
                        <option value="Damaged in Transit">Damaged in Transit</option>
                        <option value="Expired / Near Expiry">Expired</option>
                        <option value="Wrong Specification">Wrong Spec</option>
                    </select>
                </td>
                <td style="text-align: right;" class="grn-currency">₱${formatMoney(it.unitPrice)}</td>
                <td style="text-align: center;">
                    <input type="checkbox" id="lineShortClose_${idx}" title="Short-Close remaining units if supplier confirmed out of stock">
                </td>
            </tr>
        `;
    }).join('');

    calcDrawerTotalValuation();
    document.getElementById('grnInspectionDrawer')?.classList.add('active');
}

function closeInspectionDrawer() {
    document.getElementById('grnInspectionDrawer')?.classList.remove('active');
}

function calcDrawerTriad(idx) {
    const elDel = document.getElementById(`lineDelivered_${idx}`);
    const elAcc = document.getElementById(`lineAccepted_${idx}`);
    const elRej = document.getElementById(`lineRejected_${idx}`);

    const del = parseFloat(elDel?.value) || 0;
    const acc = parseFloat(elAcc?.value) || 0;
    const rej = Math.max(0, del - acc);
    if (elRej) elRej.value = rej;

    calcDrawerTotalValuation();
}

function calcDrawerTotalValuation() {
    const po = window.StockInStore.activePoReceiving;
    if (!po) return;

    let total = 0;
    (po.items || []).forEach((it, idx) => {
        const acc = parseFloat(document.getElementById(`lineAccepted_${idx}`)?.value) || 0;
        total += acc * (parseFloat(it.unitPrice) || 0);
    });

    document.getElementById('drawerTotalAssetValue').textContent = `₱${formatMoney(total)}`;
}

async function commitInspectionReceipt() {
    const po = window.StockInStore.activePoReceiving;
    if (!po) return;

    const slip = document.getElementById('drawerDeliverySlip')?.value;
    if (!slip) {
        alert('Please enter the Delivery Slip / DR #.');
        document.getElementById('drawerDeliverySlip')?.focus();
        return;
    }

    const itemsToReceive = [];
    (po.items || []).forEach((it, idx) => {
        const acc = parseFloat(document.getElementById(`lineAccepted_${idx}`)?.value) || 0;
        if (acc > 0) {
            itemsToReceive.push({
                sku: it.sku,
                name: it.name || it.itemName,
                unit: it.unit || it.uom || 'Unit',
                purchasing_unit: it.unit || it.uom || 'Unit',
                uom_multiplier: 1.0,
                received_qty: acc,
                unit_cost: parseFloat(it.unitPrice) || 0,
                lot_number: null,
                expiry_date: null,
                notes: `Received via Dock Inspection for ${po.poNumber}`
            });
        }
    });

    if (itemsToReceive.length === 0) {
        alert('Please accept at least one item quantity to receive.');
        return;
    }

    const payload = {
        po_number: po.poNumber,
        vendor_id: po.vendorId,
        vendor_name: po.vendorName,
        vendor_trade_name: po.vendorTradeName,
        delivery_slip: slip,
        carrier: 'Commercial Carrier',
        reason_code: `PO Receiving Dock: ${po.poNumber}`,
        delivery_location: po.deliveryLocation || 'Central Commissary - Receiving Dock 1',
        settlement_mode: po.paymentStatus || 'PURCHASE_ORDER',
        payment_method: po.paymentMethod || 'Trade Credit (Net 30/15)',
        payment_ref: '',
        freight: 0,
        customs: 0,
        handling: 0,
        items: itemsToReceive
    };

    const commitBtn = document.querySelector('button[onclick="commitInspectionReceipt()"]');
    const origHtml = commitBtn ? commitBtn.innerHTML : '';
    if (commitBtn) {
        commitBtn.disabled = true;
        commitBtn.innerHTML = '<i class="ph ph-spinner ph-spin"></i> Saving...';
    }

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        const res = await fetch('/inventory/api/receive-stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || ''
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Server error committing dock inspection.');
        }

        closeInspectionDrawer();
        showToast(`✓ Purchase Order ${po.poNumber} fulfilled! Voucher ${json.data.grn_number} saved to DB.`, 'success');
        await refreshStockInDataFromServer();
    } catch(err) {
        console.error('Dock inspection commit error:', err);
        alert(`Error: ${err.message}`);
    } finally {
        if (commitBtn) {
            commitBtn.disabled = false;
            commitBtn.innerHTML = origHtml;
        }
    }
}

/**
 * ----------------------------------------------------------------------------
 * STORAGE & LEDGER HELPERS
 * ----------------------------------------------------------------------------
 */
function updateProductsStock(grn) {
    (grn.items || []).forEach(it => {
        incrementProductStock(it.sku, it.baseQuantity || it.receivedQty, it.unitCost);
    });
}

function incrementProductStock(sku, qty, cost) {
    const products = window.StockInStore.products || [];
    const p = products.find(prod => prod.sku === sku);
    if (p) {
        const oldStock = parseFloat(p.stock) || 0;
        const oldCost = parseFloat(p.costPrice) || 0;
        const addQty = parseFloat(qty) || 0;
        const newCost = parseFloat(cost) || oldCost;

        const totalQty = oldStock + addQty;
        const movingAvg = totalQty > 0 ? ((oldStock * oldCost) + (addQty * newCost)) / totalQty : newCost;

        p.stock = totalQty;
        p.costPrice = Math.round(movingAvg * 100) / 100;
        localStorage.setItem('rms_inventory_products', JSON.stringify(products));
    }
}

function appendLedgerStockIn(grn) {
    let ledger = [];
    try {
        const raw = localStorage.getItem('rms_stocks_ledger');
        if (raw) ledger = JSON.parse(raw);
    } catch(e) {}

    const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

    (grn.items || []).forEach(it => {
        ledger.push({
            date: dateStr,
            ref: grn.grnNumber,
            type: `Stock In (${grn.receiptReasonCode})`,
            sku: it.sku,
            productName: it.name,
            qty: `+${it.baseQuantity || it.receivedQty}`,
            unit: it.unit,
            lotNumber: it.lotNumber,
            storageLocation: it.storageBin || 'DOCK-STAGING-01',
            note: `Direct receiving via ${grn.deliveryNote} from ${grn.vendorName}`
        });
    });

    localStorage.setItem('rms_stocks_ledger', JSON.stringify(ledger));
}

function logLedgerMovement(ref, sku, name, qty, cost, vendor) {
    let ledger = [];
    try {
        const raw = localStorage.getItem('rms_stocks_ledger');
        if (raw) ledger = JSON.parse(raw);
    } catch(e) {}

    const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    ledger.push({
        date: dateStr,
        ref: ref,
        type: 'Stock In (Purchase Order)',
        sku: sku,
        productName: name,
        qty: `+${qty}`,
        note: `Received from ${vendor}`
    });

    localStorage.setItem('rms_stocks_ledger', JSON.stringify(ledger));
}

function registerPaymentVoucher(poNumber, amount, method, ref) {
    let payments = [];
    try {
        const raw = localStorage.getItem('rms_po_payments');
        if (raw) payments = JSON.parse(raw);
    } catch(e) {}

    payments.push({
        id: `PAY-${Date.now()}`,
        poNumber: poNumber,
        amount: amount,
        paymentMethod: method,
        reference: ref,
        paymentDate: new Date().toISOString().slice(0, 10),
        remarks: 'Direct COD settlement at dock'
    });

    localStorage.setItem('rms_po_payments', JSON.stringify(payments));
}

/**
 * ----------------------------------------------------------------------------
 * TRACEABILITY MODAL FOR DIRECT LINES
 * ----------------------------------------------------------------------------
 */
function openTraceModalForLine(idx) {
    window.StockInStore.activeTraceLineIndex = idx;
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;

    document.getElementById('traceLotInput').value = it.lotNumber || '';
    document.getElementById('traceMfgInput').value = it.mfgDate || '';
    document.getElementById('traceExpInput').value = it.expiryDate || '';

    document.getElementById('traceModal')?.classList.add('active');
}

function closeTraceModal() {
    document.getElementById('traceModal')?.classList.remove('active');
}

function saveTraceModalData() {
    const idx = window.StockInStore.activeTraceLineIndex;
    if (idx === null || idx === undefined) return;
    const it = window.StockInStore.directForm.items[idx];
    if (!it) return;

    it.lotNumber = document.getElementById('traceLotInput')?.value || `LOT-${Date.now()}`;
    it.mfgDate = document.getElementById('traceMfgInput')?.value || '';
    it.expiryDate = document.getElementById('traceExpInput')?.value || '';

    closeTraceModal();
    showToast(`Traceability parameters saved for ${it.name}`, 'success');
}

function closeSupervisorOverrideModal() {
    document.getElementById('supervisorOverrideModal')?.classList.remove('active');
}

function openSupervisorOverrideModal() {
    document.getElementById('supervisorOverrideModal')?.classList.add('active');
}

function verifySupervisorPin() {
    const pin = document.getElementById('supervisorPinInput')?.value;
    if (pin === '1234') {
        closeSupervisorOverrideModal();
        document.getElementById('drawerOverToleranceBox').style.display = 'none';
        showToast('Supervisor override authorized', 'success');
    } else {
        alert('Invalid Supervisor PIN.');
    }
}

function viewPoSummaryModal(poNumber) {
    const po = (window.StockInStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    alert(`Purchase Order: ${po.poNumber}\nVendor: ${po.vendorTradeName || po.vendorName}\nStatus: ${po.status}\nItems: ${(po.items || []).length} items`);
}

function exportOrdersToCsv() {
    const pos = window.StockInStore.purchaseOrders || [];
    if (pos.length === 0) {
        alert('No orders to export.');
        return;
    }

    const headers = ['PO Number', 'Order Date', 'Vendor', 'Type', 'Delivery Location', 'Items Count', 'Status'];
    const rows = pos.map(p => [
        `"${p.poNumber}"`,
        `"${p.orderDate || ''}"`,
        `"${(p.vendorTradeName || p.vendorName || '').replace(/"/g, '""')}"`,
        `"${p.poType || 'vendor'}"`,
        `"${p.deliveryLocation || ''}"`,
        (p.items || []).length,
        `"${p.status}"`
    ]);

    const csvContent = 'data:text/csv;charset=utf-8,' + [headers.join(','), ...rows.map(e => e.join(','))].join('\n');
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `Purchase_Orders_Export_${new Date().toISOString().slice(0,10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

/**
 * ----------------------------------------------------------------------------
 * UTILITY HELPERS & SEED DATA
 * ----------------------------------------------------------------------------
 */
function formatMoney(amount) {
    const num = parseFloat(amount) || 0;
    return num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function showToast(msg, type = 'success') {
    const toast = document.getElementById('grnToast');
    const toastMsg = document.getElementById('grnToastMsg');
    const toastIcon = document.getElementById('grnToastIcon');
    if (!toast) return;

    toastMsg.textContent = msg;
    toastIcon.className = type === 'success' ? 'ph ph-check-circle' : 'ph ph-info';
    toastIcon.style.color = type === 'success' ? '#10b981' : '#38bdf8';

    toast.style.display = 'flex';
    setTimeout(() => { toast.style.opacity = '1'; }, 10);
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => { toast.style.display = 'none'; }, 250);
    }, 3500);
}

function getFallbackPurchaseOrders() {
    return [
        {
            poNumber: 'PO-2026-0103',
            poType: 'vendor',
            vendorName: 'EcoPack Solutions Philippines Corp.',
            vendorTradeName: 'EcoPack Packaging',
            orderDate: '2026-09-29',
            expectedDelivery: '2026-10-04',
            deliveryLocation: 'Central Warehouse - Dry Storage',
            paymentStatus: 'Paid in Full / Cash Out',
            status: 'Approved / Issued',
            items: [
                { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', category: 'Packaging', unit: 'Pc', quantity: 2000.0, receivedQuantity: 0, unitPrice: 4.50 },
                { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', category: 'Packaging', unit: 'Pc', quantity: 1000.0, receivedQuantity: 0, unitPrice: 8.20 }
            ]
        },
        {
            poNumber: 'PO-2026-0102',
            poType: 'wet_market',
            vendorName: 'Balintawak Central Wet Market Stall 12',
            vendorTradeName: 'Balintawak Fresh Meat Stall',
            orderDate: '2026-09-28',
            expectedDelivery: '2026-10-04',
            deliveryLocation: 'Commissary Butchery Cold Room',
            paymentStatus: 'Unpaid / On Credit Terms',
            status: 'Partially Received',
            items: [
                { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', category: 'Raw Ingredients', unit: 'Kg', quantity: 20.0, receivedQuantity: 10.0, unitPrice: 380.00 },
                { sku: 'WET-BONE', name: 'Beef Marrow Bones', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, receivedQuantity: 0, unitPrice: 90.00 }
            ]
        }
    ];
}

function getFallbackProducts() {
    return [
        { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', category: 'Packaging', unit: 'Pc', stock: 500, costPrice: 4.50 },
        { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', category: 'Packaging', unit: 'Pc', stock: 250, costPrice: 8.20 },
        { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', category: 'Raw Ingredients', unit: 'Kg', stock: 15, costPrice: 380.00 },
        { sku: 'WET-BONE', name: 'Beef Marrow Bones', category: 'Raw Ingredients', unit: 'Kg', stock: 8, costPrice: 90.00 },
        { sku: 'RAW-DAIRY-01', name: 'Fresh Whole Milk Barista Blend', category: 'Dairy & Eggs', unit: 'Liter', stock: 80, costPrice: 95.00 }
    ];
}

function getFallbackVendors() {
    return [
        { id: 'VND-001', code: 'VND-001', legalName: 'San Miguel Foods Inc.', tradeName: 'San Miguel Foods', category: 'Raw Ingredients' },
        { id: 'VND-002', code: 'VND-002', legalName: 'Universal Robina Corp.', tradeName: 'URC Commercial', category: 'Dry Goods' },
        { id: 'VND-ECO-004', code: 'VND-ECO-004', legalName: 'EcoPack Solutions Philippines Corp.', tradeName: 'EcoPack Packaging', category: 'Packaging' },
        { id: 'WET-MKT-001', code: 'WET-MKT-001', legalName: 'Balintawak Central Wet Market Stall 12', tradeName: 'Balintawak Fresh Meat Stall', category: 'Wet Market' }
    ];
}
</script>
@endpush
@endsection

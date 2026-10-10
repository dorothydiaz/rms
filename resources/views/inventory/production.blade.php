@extends('layouts.app')

@section('title', 'Production & Kitchen Assembly - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   PRODUCTION & COMMISSARY ASSEMBLY MODULE - UNIVERSAL HR THEME
   Adhering to docs/HR_THEME_DESIGN_SYSTEM.md & assets/css/theme-tokens.css
   ========================================================================== */
:root {
    --prd-canvas-bg: #faf7fd;
    --prd-surface-card: rgba(255, 255, 255, 0.96);
    --prd-surface-subtle: #f8fafc;
    --prd-surface-hover: rgba(245, 243, 255, 0.65);
    --prd-border: rgba(226, 232, 240, 0.9);
    --prd-border-focus: #c084fc;

    --prd-primary: #9333ea;
    --prd-primary-dark: #7c3aed;
    --prd-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --prd-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);
    --prd-primary-glow: rgba(168, 85, 247, 0.28);
    --prd-accent-strip: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);

    --prd-text-strong: #0f172a;
    --prd-text-medium: #334155;
    --prd-text-muted: #64748b;
    --prd-text-subtle: #94a3b8;

    --prd-shadow-card: 0 10px 32px rgba(148, 163, 184, 0.08), 0 2px 8px rgba(0, 0, 0, 0.02);
    --prd-shadow-card-hover: 0 14px 32px rgba(168, 85, 247, 0.14), 0 4px 10px rgba(236, 72, 153, 0.08);
}

.prd-workspace {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0;
    background-color: transparent;
    min-height: auto;
    width: 100%;
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
    color: var(--prd-text-strong);
}

.prd-card-strip {
    position: relative;
    background: var(--prd-surface-card);
    border: 1px solid var(--prd-border);
    border-radius: 16px;
    box-shadow: var(--prd-shadow-card);
    overflow: hidden;
}

.prd-card-strip::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--prd-accent-strip);
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
.prd-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0px;
    flex-wrap: wrap;
}
.hr-parent-title,
.prd-page-title {
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
.prd-page-subtitle {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 400;
    margin: 3px 0 0 0;
}
.hr-page-actions,
.prd-header-actions {
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
.prd-tab-panel {
    display: none;
}

.prd-tab-panel.active {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* KPI Summary Cards */
.prd-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}

.prd-kpi-card {
    background: #ffffff;
    border: 1px solid var(--prd-border);
    border-radius: 14px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: var(--prd-shadow-card);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.prd-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--prd-shadow-card-hover);
}

.prd-kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}

.prd-kpi-content {
    flex: 1;
}

.prd-kpi-label {
    font-size: 11.5px;
    font-weight: 600;
    color: var(--prd-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 4px;
}

.prd-kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--prd-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}

.prd-kpi-meta {
    font-size: 11.5px;
    color: var(--prd-text-muted);
    margin-top: 3px;
    display: flex;
    align-items: center;
    gap: 4px;
}

.kpi-icon-purple { background: rgba(147, 51, 234, 0.12); color: #9333ea; }
.kpi-icon-green { background: rgba(16, 185, 129, 0.12); color: #059669; }
.kpi-icon-blue { background: rgba(59, 130, 246, 0.12); color: #2563eb; }
.kpi-icon-amber { background: rgba(245, 158, 11, 0.12); color: #d97706; }

/* Filter & Search Bar */
.prd-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 16px 20px;
    background: #ffffff;
    border: 1px solid var(--prd-border);
    border-radius: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
}

.prd-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 440px;
}

.prd-search-box i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--prd-text-subtle);
    font-size: 16px;
}

.prd-search-input {
    width: 100%;
    padding: 9px 14px 9px 38px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 13px;
    color: var(--prd-text-strong);
    outline: none;
    transition: all 0.2s;
    box-sizing: border-box;
}

.prd-search-input:focus {
    border-color: var(--prd-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

.prd-select {
    padding: 8px 12px;
    border-radius: 10px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: var(--prd-text-strong);
    outline: none;
    cursor: pointer;
    transition: border-color 0.2s;
}

.prd-select:focus {
    border-color: var(--prd-border-focus);
}

/* Data Table */
.prd-table-wrapper {
    overflow-x: auto;
    background: #ffffff;
    border: 1px solid var(--prd-border);
    border-radius: 14px;
    box-shadow: var(--prd-shadow-card);
}

.prd-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 12.5px;
}

.prd-table th {
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

.prd-table td {
    padding: 14px 16px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--prd-text-medium);
    vertical-align: middle;
}

.prd-table tr:hover td {
    background-color: var(--prd-surface-hover);
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
    background: var(--prd-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.3);
}

.hr-btn-primary:hover {
    background: var(--prd-primary-gradient-hover);
    transform: translateY(-1px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.4);
}

.hr-btn-secondary {
    background: #ffffff;
    color: var(--prd-text-medium);
    border: 1px solid #cbd5e1;
}

.hr-btn-secondary:hover {
    background: #f8fafc;
    border-color: #94a3b8;
    color: var(--prd-text-strong);
}

.hr-btn-sm {
    padding: 5px 11px;
    font-size: 12px;
    border-radius: 8px;
}

/* Two Column Form Grid */
.prd-form-grid {
    display: grid;
    grid-template-columns: 420px 1fr;
    gap: 20px;
    align-items: start;
}

@media (max-width: 1024px) {
    .prd-form-grid {
        grid-template-columns: 1fr;
    }
}

.prd-form-card {
    background: #ffffff;
    border: 1px solid var(--prd-border);
    border-radius: 14px;
    padding: 22px;
    box-shadow: var(--prd-shadow-card);
}

.prd-form-title {
    margin: 0 0 16px 0;
    font-family: 'Outfit', sans-serif;
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--prd-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
    border-bottom: 1px solid #f1f5f9;
    padding-bottom: 12px;
}

.prd-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.prd-form-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--prd-text-medium);
}

.prd-form-label small {
    color: var(--prd-text-muted);
    font-weight: normal;
}

.prd-form-control {
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
    color: var(--prd-text-strong);
}

.prd-form-control:focus {
    border-color: var(--prd-border-focus);
    box-shadow: 0 0 0 3px rgba(192, 132, 252, 0.2);
}

.prd-form-control[readonly] {
    background: #f8fafc;
    color: var(--prd-text-muted);
}

.prd-recipe-pill-box {
    background: #faf5ff;
    border: 1px solid #f3e8ff;
    border-radius: 12px;
    padding: 14px;
    margin-bottom: 16px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Alert Deficit Banner */
.prd-deficit-alert-banner {
    background: #fff1f2;
    border: 1px solid #fecdd3;
    border-left: 4px solid #e11d48;
    border-radius: 12px;
    padding: 14px 18px;
    display: none;
    margin-bottom: 16px;
}

.prd-deficit-alert-title {
    font-size: 13px;
    font-weight: 700;
    color: #9f1239;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}

.prd-deficit-alert-desc {
    font-size: 12.5px;
    color: #be123c;
    line-height: 1.45;
    margin: 0;
}

/* Modal Ticket */
.prd-modal-backdrop {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.55);
    backdrop-filter: blur(4px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.prd-modal-backdrop.open {
    display: flex;
}

.prd-modal-content {
    background: #ffffff;
    border-radius: 18px;
    max-width: 720px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    display: flex;
    flex-direction: column;
}

@media print {
    body * { visibility: hidden; }
    #printableBatchTicket, #printableBatchTicket * { visibility: visible; }
    #printableBatchTicket { position: absolute; left: 0; top: 0; width: 100%; }
}
</style>
@endpush

@section('content')
<style>
/* Compact Spacing Overrides (Employees Directory parity) */
.prd-workspace {
    gap: 8px !important;
    padding: 0 !important;
    background-color: transparent !important;
    min-height: auto !important;
    width: 100% !important;
}
.prd-card-strip {
    margin-top: 0 !important;
}
.hr-parent-header { margin-bottom: 6px !important; }
.hr-parent-header .hr-parent-title-row { margin-bottom: 4px !important; }
.hr-parent-header .hr-parent-title { font-size: 20px !important; gap: 8px !important; }
.hr-parent-header .hr-parent-title i { width: 32px !important; height: 32px !important; font-size: 17px !important; border-radius: 8px !important; }
.hr-parent-header .hr-parent-subtitle { font-size: 12px !important; margin-top: 2px !important; }
.hr-tabs-wrapper { margin-top: 6px !important; margin-bottom: 8px !important; padding: 3px 5px !important; gap: 4px !important; border-radius: 10px !important; }
.hr-tabs-wrapper .tab-btn { padding: 5px 12px !important; font-size: 12.5px !important; }
.prd-tab-panel.active { gap: 8px !important; }
.prd-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }
</style>

<div class="prd-workspace">

    <!-- 1. Page Header -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row prd-page-header">
            <div>
                <h1 class="hr-parent-title prd-page-title">
                    <i class="ph ph-factory"></i>
                    <span>Production & Kitchen Assembly</span>
                    <span class="hr-badge hr-badge-purple" style="font-size: 11px;">BOM Connected</span>
                </h1>
                <p class="hr-parent-subtitle prd-page-subtitle">Batch assembly runs, recipe yield scaling, real-time ingredient stock lookup, and automated ledger deductions.</p>
            </div>
            <div class="hr-page-actions prd-header-actions">
                <a href="{{ route('inventory.product-categories') }}" class="hr-btn hr-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-package"></i>
                    <span>Item Master</span>
                </a>
                <a href="{{ route('inventory.recipe-management') }}" class="hr-btn hr-btn-secondary" title="Manage Bill of Materials & Recipe Formulations">
                    <i class="ph ph-cooking-pot"></i>
                    <span>Bill of Materials (BOM)</span>
                </a>
                <a href="{{ route('inventory.stocks-overview') }}" class="hr-btn hr-btn-secondary" title="View Live Stock Overview">
                    <i class="ph ph-squares-four"></i>
                    <span>Stocks Overview</span>
                </a>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchPrdTab('tab-new-batch')">
                    <i class="ph ph-plus-circle"></i>
                    <span>New Production Batch</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 3. Universal HR Tabs Bar -->
    <nav class="hr-tabs-wrapper tabs-wrapper">
        <button type="button" class="tab-btn active" id="btn-tab-batch-list" onclick="switchPrdTab('tab-batch-list')">
            <i class="ph ph-list-dashes"></i>
            <span>Production Batches & Runs</span>
            <span class="tab-count" id="badgeOrderCount">{{ count($initialOrders ?? []) }}</span>
        </button>
        <button type="button" class="tab-btn" id="btn-tab-new-batch" onclick="switchPrdTab('tab-new-batch')">
            <i class="ph ph-cooking-pot"></i>
            <span>New Production Batch (Assembly Run)</span>
        </button>
        <button type="button" class="tab-btn" id="btn-tab-bom-catalog" onclick="switchPrdTab('tab-bom-catalog')">
            <i class="ph ph-book-open"></i>
            <span>BOM Recipe Reference Catalog</span>
            <span class="tab-count" id="badgeBomCount">{{ count($initialBoms ?? []) }}</span>
        </button>
    </nav>

    <!-- =========================================================================
         TAB 1: PRODUCTION BATCHES & RUNS (MASTER REGISTER)
         ========================================================================= -->
    <div class="prd-tab-panel active" id="tab-batch-list">
        <div class="prd-filter-bar">
            <div class="prd-search-box">
                <i class="ph ph-magnifying-glass"></i>
                <input type="text" id="prdSearchInput" class="prd-search-input" placeholder="Search batch number, lot #, product, chef, station..." oninput="handlePrdFilter()">
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <select id="prdStatusFilter" class="prd-select" style="min-width: 160px;" onchange="handlePrdFilter()">
                    <option value="ALL">All Statuses</option>
                    <option value="COMPLETED">Completed</option>
                    <option value="IN_PROGRESS">In Progress</option>
                    <option value="PLANNED">Planned</option>
                    <option value="CANCELLED">Cancelled</option>
                </select>
                <button type="button" class="hr-btn hr-btn-secondary" onclick="fetchLiveProductionData()" title="Sync live batch list">
                    <i class="ph ph-arrows-clockwise"></i>
                    <span>Sync</span>
                </button>
            </div>
        </div>

        <div class="prd-table-wrapper">
            <table class="prd-table">
                <thead>
                    <tr>
                        <th>Batch / Lot Reference</th>
                        <th>Finished Product</th>
                        <th>Kitchen Station</th>
                        <th style="text-align: right;">Planned Qty</th>
                        <th style="text-align: right;">Actual Yield</th>
                        <th style="text-align: center;">Efficiency</th>
                        <th style="text-align: right;">Batch Cost (₱)</th>
                        <th>Date & Chef</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody id="prdOrdersTbody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- =========================================================================
         TAB 2: NEW PRODUCTION BATCH (BATCH RUN ASSEMBLY ENGINE)
         ========================================================================= -->
    <div class="prd-tab-panel" id="tab-new-batch">
        <!-- Shortage Deficit Banner -->
        <div class="prd-deficit-alert-banner" id="prdDeficitBanner">
            <div class="prd-deficit-alert-title">
                <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
                <span>Raw Material Shortage Detected!</span>
            </div>
            <p class="prd-deficit-alert-desc" id="prdDeficitDesc">
                One or more ingredients have insufficient inventory. You can adjust the batch size or proceed with supervisory shortage override.
            </p>
        </div>

        <div class="prd-form-grid">
            <!-- Left Column: Batch Setup & Formulation Configuration -->
            <div class="prd-form-card">
                <h3 class="prd-form-title">
                    <i class="ph ph-sliders" style="color: var(--prd-primary);"></i>
                    <span>Batch Setup & Target Output</span>
                </h3>

                <!-- Product / Recipe Selection -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Target Finished Product <span style="color: #e11d48;">*</span></label>
                    <select id="prdFinishedProductSelect" class="prd-form-control" onchange="handleProductSelection(this.value)">
                        <option value="">-- Choose Finished Product with BOM --</option>
                        @foreach($initialBoms ?? [] as $bom)
                            <option value="{{ $bom->id }}" data-product-id="{{ $bom->finished_item_id }}" data-sku="{{ $bom->sku }}" data-uom="{{ $bom->uom }}" data-yield="{{ $bom->yield_quantity }}" data-shelf-life="{{ $bom->shelf_life_days }}" data-station="{{ $bom->category }}">
                                {{ $bom->item_name }} ({{ $bom->sku }}) - Yield: {{ $bom->yield_quantity }} {{ $bom->uom }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Recipe Summary Card -->
                <div class="prd-recipe-pill-box" id="prdRecipePillBox" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-weight: 700; color: var(--prd-primary); font-size: 13px;" id="dispBomCode">BOM-CODE</span>
                        <span class="hr-badge hr-badge-purple" id="dispBomCategory">Category</span>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-medium); display: flex; justify-content: space-between;">
                        <span>Standard Recipe Yield:</span>
                        <strong id="dispBomYield">1.00 Portion</strong>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-medium); display: flex; justify-content: space-between;">
                        <span>Standard Unit Cost:</span>
                        <strong id="dispBomUnitCost">₱0.00</strong>
                    </div>
                    <div style="font-size: 12px; color: var(--prd-text-muted); font-style: italic;" id="dispBomInstructions">
                        Recipe instructions...
                    </div>
                </div>

                <!-- Target Output Quantity -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Planned Quantity <span style="color: #e11d48;">*</span></label>
                        <input type="number" id="prdPlannedQty" class="prd-form-control" min="0.1" step="0.1" value="10" oninput="recalculateBatchScaling()">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Actual Yield <small>(Produced)</small></label>
                        <input type="number" id="prdActualYield" class="prd-form-control" min="0.1" step="0.1" value="10" oninput="recalculateYieldEfficiency()">
                    </div>
                </div>

                <!-- Scaling & Efficiency Preview Pill -->
                <div style="display: flex; justify-content: space-between; align-items: center; background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 10px; padding: 10px 14px; margin-bottom: 16px;">
                    <div>
                        <span style="font-size: 11px; text-transform: uppercase; color: #7c3aed; font-weight: 700;">Scaling Factor</span>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; color: #9333ea;" id="dispBatchMultiplier">10.0x</div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; text-transform: uppercase; color: #7c3aed; font-weight: 700;">Yield Efficiency</span>
                        <div style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; color: #9333ea;" id="dispYieldEfficiency">100.0%</div>
                    </div>
                </div>

                <!-- Kitchen Station & Date -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Kitchen Station / Line</label>
                    <select id="prdKitchenStation" class="prd-form-control">
                        <option value="Central Commissary - Prep Kitchen">Central Commissary - Prep Kitchen</option>
                        <option value="Hot Line / Sauces & Soups">Hot Line / Sauces & Soups</option>
                        <option value="Butchery & Meat Prep Unit">Butchery & Meat Prep Unit</option>
                        <option value="Bakery & Pastry Studio">Bakery & Pastry Studio</option>
                        <option value="Beverage & Barista Station">Beverage & Barista Station</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Production Date</label>
                        <input type="date" id="prdProductionDate" class="prd-form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Expiry Date <small>(Auto)</small></label>
                        <input type="date" id="prdExpiryDate" class="prd-form-control">
                    </div>
                </div>

                <!-- Operator / Chef Info -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div class="prd-form-group">
                        <label class="prd-form-label">Produced By (Lead Chef)</label>
                        <input type="text" id="prdProducedBy" class="prd-form-control" value="{{ auth()->user()->full_name ?? auth()->user()->name ?? 'Executive Sous Chef' }}">
                    </div>
                    <div class="prd-form-group">
                        <label class="prd-form-label">Verified By (QA / Sous)</label>
                        <input type="text" id="prdVerifiedBy" class="prd-form-control" placeholder="Optional QA stamp">
                    </div>
                </div>

                <!-- Quality Notes -->
                <div class="prd-form-group">
                    <label class="prd-form-label">Batch Notes & Sensory QC</label>
                    <textarea id="prdQualityNotes" class="prd-form-control" style="height: 60px; padding: 8px 12px; resize: vertical;" placeholder="Organoleptic check, taste test notes, temperature log..."></textarea>
                </div>

                <!-- Cost Summary Card -->
                <div style="background: #faf5ff; border: 1px solid #f3e8ff; border-radius: 12px; padding: 14px; margin-bottom: 16px;">
                    <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                        <span>Estimated Raw Materials:</span>
                        <strong id="dispTotalRawCost">₱0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12.5px; margin-bottom: 4px;">
                        <span>Labor & Overhead:</span>
                        <strong id="dispLaborOverheadCost">₱0.00</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 14px; font-weight: 800; color: var(--prd-text-strong); border-top: 1px dashed #d8b4fe; padding-top: 6px; margin-top: 6px;">
                        <span>Total Batch Cost:</span>
                        <span style="color: var(--prd-primary);" id="dispTotalProductionCost">₱0.00</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 12px; color: var(--prd-text-muted); margin-top: 4px;">
                        <span>Calculated Unit Cost:</span>
                        <strong id="dispUnitProductionCost">₱0.00 / unit</strong>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="button" id="btnProduceBatch" class="hr-btn hr-btn-primary" style="width: 100%; height: 44px; font-size: 14px;" onclick="submitProductionBatch()">
                    <i class="ph ph-check-circle"></i>
                    <span>Produce & Complete Batch</span>
                </button>
            </div>

            <!-- Right Column: Live BOM Raw Materials Consumption & Stock Availability Grid -->
            <div class="prd-form-card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                    <h3 class="prd-form-title" style="margin: 0; border: none; padding: 0;">
                        <i class="ph ph-list-checks" style="color: var(--prd-primary);"></i>
                        <span>BOM Recipe Components & Live Stock Lookup</span>
                    </h3>
                    <span class="hr-badge hr-badge-purple" id="dispIngredientCountBadge">0 Ingredients</span>
                </div>

                <p style="font-size: 12.5px; color: var(--prd-text-muted); margin-top: -6px; margin-bottom: 14px;">
                    Ingredient quantities automatically scale proportionally with planned batch output. Available stock is verified live from Central Inventory.
                </p>

                <div class="prd-table-wrapper">
                    <table class="prd-table">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Raw Material / Ingredient</th>
                                <th style="text-align: right;">Required Qty</th>
                                <th style="text-align: right;">Stock On-Hand</th>
                                <th style="text-align: center;">Stock Status</th>
                                <th style="text-align: right;">Unit Cost</th>
                                <th style="text-align: right;">Line Total</th>
                            </tr>
                        </thead>
                        <tbody id="prdIngredientsTbody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                                    Please select a Target Finished Product on the left to load its Bill of Materials recipe.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- =========================================================================
         TAB 3: BOM RECIPE REFERENCE CATALOG
         ========================================================================= -->
    <div class="prd-tab-panel" id="tab-bom-catalog">
        <div class="prd-card-strip" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.15rem; font-weight: 700; color: var(--prd-text-strong);">Active Bill of Materials Formulas</h3>
                    <p style="margin: 2px 0 0 0; font-size: 12.5px; color: var(--prd-text-muted);">Standard recipes configured in the database linking finished items to raw ingredients.</p>
                </div>
                <a href="{{ route('inventory.recipe-management') }}" class="hr-btn hr-btn-secondary">
                    <i class="ph ph-pencil-simple"></i>
                    <span>Configure in Recipe Management</span>
                </a>
            </div>

            <div class="prd-table-wrapper">
                <table class="prd-table">
                    <thead>
                        <tr>
                            <th>BOM Code</th>
                            <th>Finished Product</th>
                            <th>Category</th>
                            <th style="text-align: right;">Standard Yield</th>
                            <th style="text-align: right;">Raw Material Cost</th>
                            <th style="text-align: right;">Labor + Overhead</th>
                            <th style="text-align: right;">Total Cost</th>
                            <th style="text-align: right;">Selling Price</th>
                            <th style="text-align: center;">Margin %</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="prdBomCatalogTbody">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<!-- =========================================================================
     MODAL: PRINTABLE KITCHEN BATCH WORK ORDER TICKET
     ========================================================================= -->
<div class="prd-modal-backdrop" id="prdTicketModal">
    <div class="prd-modal-content" id="printableBatchTicket">
        <div style="padding: 24px; border-bottom: 1px solid var(--prd-border); display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 12px; background: rgba(147, 51, 234, 0.12); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 1.2rem; font-weight: 700; color: var(--prd-text-strong);" id="modalTicketTitle">Kitchen Production Work Order</h3>
                    <span style="font-size: 12px; color: var(--prd-text-muted);" id="modalTicketBatchNo">PRD-20261005-0001</span>
                </div>
            </div>
            <div style="display: flex; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="window.print()">
                    <i class="ph ph-printer"></i>
                    <span>Print Ticket</span>
                </button>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closePrdTicketModal()">
                    <i class="ph ph-x"></i>
                </button>
            </div>
        </div>

        <div style="padding: 24px; display: flex; flex-direction: column; gap: 18px;">
            <!-- Metadata Grid -->
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; background: #faf5ff; padding: 14px; border-radius: 12px; border: 1px solid #f3e8ff; font-size: 12px;">
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Finished Item:</span>
                    <strong style="color: var(--prd-text-strong); font-size: 13px;" id="modalTicketProduct">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Batch Lot Number:</span>
                    <strong style="font-family: monospace; color: var(--prd-primary);" id="modalTicketLot">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Station / Prep Line:</span>
                    <strong id="modalTicketStation">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Planned Output:</span>
                    <strong id="modalTicketPlanned">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Actual Yield Produced:</span>
                    <strong style="color: #059669;" id="modalTicketActual">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Yield Efficiency:</span>
                    <strong id="modalTicketEfficiency">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Production Date:</span>
                    <strong id="modalTicketDate">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Shelf Life Expiry:</span>
                    <strong id="modalTicketExpiry">-</strong>
                </div>
                <div>
                    <span style="color: var(--prd-text-muted); display: block;">Prepared By:</span>
                    <strong id="modalTicketChef">-</strong>
                </div>
            </div>

            <!-- Scaled Ingredients Table -->
            <h4 style="margin: 0; font-size: 13px; font-weight: 700; color: var(--prd-text-strong);">Raw Materials & Ingredients Consumed</h4>
            <div class="prd-table-wrapper">
                <table class="prd-table">
                    <thead>
                        <tr>
                            <th>SKU</th>
                            <th>Raw Ingredient</th>
                            <th style="text-align: right;">Consumed Qty</th>
                            <th style="text-align: right;">Unit Cost</th>
                            <th style="text-align: right;">Total Cost</th>
                        </tr>
                    </thead>
                    <tbody id="modalTicketItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Signatures Section -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed #cbd5e1;">
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #475569; height: 35px; margin-bottom: 6px;"></div>
                    <span style="font-size: 11px; color: var(--prd-text-muted); text-transform: uppercase;">Lead Cook / Kitchen Operator Signature</span>
                </div>
                <div style="text-align: center;">
                    <div style="border-bottom: 1px solid #475569; height: 35px; margin-bottom: 6px;"></div>
                    <span style="font-size: 11px; color: var(--prd-text-muted); text-transform: uppercase;">Executive Sous Chef / QA Inspector Sign-Off</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
/* ==========================================================================
   PRODUCTION ENGINE - CLIENT DATA STORE & REACTIVE CONTROLLER
   ========================================================================== */
let rawOrders = @json($initialOrders ?? []);
let rawBoms = @json($initialBoms ?? []);
let rawProducts = @json($initialProducts ?? []);
let activeBom = null;
let currentShortages = [];

document.addEventListener('DOMContentLoaded', () => {
    renderProductionOrders();
    renderBomCatalog();
    
    // Auto-select first BOM if available
    const select = document.getElementById('prdFinishedProductSelect');
    if (select && select.options.length > 1) {
        select.selectedIndex = 1;
        handleProductSelection(select.value);
    }
});

/* --------------------------------------------------------------------------
   TAB SWITCHING
   -------------------------------------------------------------------------- */
function switchPrdTab(tabId) {
    document.querySelectorAll('.prd-tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    const panel = document.getElementById(tabId);
    if (panel) panel.classList.add('active');

    const btn = document.getElementById('btn-' + tabId);
    if (btn) btn.classList.add('active');
}

/* --------------------------------------------------------------------------
   TAB 1: RENDER PRODUCTION ORDERS
   -------------------------------------------------------------------------- */
function renderProductionOrders() {
    const tbody = document.getElementById('prdOrdersTbody');
    if (!tbody) return;

    const query = (document.getElementById('prdSearchInput')?.value || '').toLowerCase().trim();
    const statusFilter = document.getElementById('prdStatusFilter')?.value || 'ALL';

    const filtered = rawOrders.filter(o => {
        if (statusFilter !== 'ALL' && o.status !== statusFilter) return false;
        if (query) {
            const num = (o.production_number || '').toLowerCase();
            const lot = (o.batch_lot_number || '').toLowerCase();
            const item = (o.item_name || '').toLowerCase();
            const sku = (o.sku || '').toLowerCase();
            const station = (o.kitchen_station || '').toLowerCase();
            const chef = (o.produced_by || '').toLowerCase();
            if (!num.includes(query) && !lot.includes(query) && !item.includes(query) && !sku.includes(query) && !station.includes(query) && !chef.includes(query)) {
                return false;
            }
        }
        return true;
    });

    document.getElementById('badgeOrderCount').textContent = rawOrders.length;

    if (filtered.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; color: var(--prd-text-muted); padding: 36px;">
                    <i class="ph ph-factory" style="font-size: 32px; opacity: 0.35; display: block; margin-bottom: 8px; color: var(--prd-primary);"></i>
                    No production batches found matching filter criteria.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    filtered.forEach(o => {
        const planned = parseFloat(o.planned_quantity) || 0;
        const actual = parseFloat(o.actual_quantity) || 0;
        const eff = parseFloat(o.yield_efficiency_percent) || 100;
        const cost = parseFloat(o.total_production_cost) || 0;

        let effBadgeClass = 'hr-badge-green';
        if (eff < 95) effBadgeClass = 'hr-badge-amber';
        if (eff < 85) effBadgeClass = 'hr-badge-red';

        let statusClass = 'hr-badge-green';
        if (o.status === 'PLANNED') statusClass = 'hr-badge-blue';
        if (o.status === 'IN_PROGRESS') statusClass = 'hr-badge-amber';
        if (o.status === 'CANCELLED') statusClass = 'hr-badge-red';

        html += `
            <tr>
                <td>
                    <div style="font-weight: 700; color: var(--prd-text-strong);">${o.production_number}</div>
                    <div style="font-size: 11px; font-family: monospace; color: var(--prd-primary);">${o.batch_lot_number || '-'}</div>
                </td>
                <td>
                    <div style="font-weight: 600; color: var(--prd-text-strong);">${escapeHtml(o.item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted); font-family: monospace;">SKU: ${o.sku}</div>
                </td>
                <td>
                    <span class="hr-badge hr-badge-neutral">${escapeHtml(o.kitchen_station || 'Prep Kitchen')}</span>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">
                    ${planned.toFixed(1)} ${o.uom}
                </td>
                <td style="text-align: right; font-weight: 700; color: #059669; font-variant-numeric: tabular-nums;">
                    ${actual.toFixed(1)} ${o.uom}
                </td>
                <td style="text-align: center;">
                    <span class="hr-badge ${effBadgeClass}">${eff.toFixed(1)}%</span>
                </td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">
                    ₱${cost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                </td>
                <td>
                    <div style="font-size: 12px; font-weight: 600;">${formatDate(o.production_date)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted);">${escapeHtml(o.produced_by)}</div>
                </td>
                <td>
                    <span class="hr-badge ${statusClass}">${o.status}</span>
                    ${o.proceed_with_shortage ? '<span class="hr-badge hr-badge-red" style="margin-left: 3px;" title="Shortage override logged">Shortage</span>' : ''}
                </td>
                <td style="text-align: right;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPrdTicketModal(${o.id})" title="Print or view batch ticket">
                        <i class="ph ph-receipt"></i>
                        <span>Ticket</span>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function handlePrdFilter() {
    renderProductionOrders();
}

/* --------------------------------------------------------------------------
   TAB 2: PRODUCT SELECTION & BATCH FORMULATION
   -------------------------------------------------------------------------- */
function handleProductSelection(bomId) {
    activeBom = rawBoms.find(b => b.id == bomId);
    const pillBox = document.getElementById('prdRecipePillBox');

    if (!activeBom) {
        if (pillBox) pillBox.style.display = 'none';
        document.getElementById('prdIngredientsTbody').innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    Please select a Target Finished Product on the left to load its Bill of Materials recipe.
                </td>
            </tr>
        `;
        return;
    }

    if (pillBox) pillBox.style.display = 'flex';
    document.getElementById('dispBomCode').textContent = activeBom.bom_code;
    document.getElementById('dispBomCategory').textContent = activeBom.category || 'Recipe';
    document.getElementById('dispBomYield').textContent = `${parseFloat(activeBom.yield_quantity).toFixed(2)} ${activeBom.uom}`;
    document.getElementById('dispBomUnitCost').textContent = `₱${parseFloat(activeBom.unit_cost || 0).toFixed(2)} / ${activeBom.uom}`;
    document.getElementById('dispBomInstructions').textContent = activeBom.instructions || 'Standard culinary assembly procedure.';

    // Auto calculate expiry
    const shelfLife = parseInt(activeBom.shelf_life_days) || 7;
    const prodDateStr = document.getElementById('prdProductionDate').value;
    if (prodDateStr) {
        const d = new Date(prodDateStr);
        d.setDate(d.getDate() + shelfLife);
        document.getElementById('prdExpiryDate').value = d.toISOString().split('T')[0];
    }

    recalculateBatchScaling();
}

function recalculateBatchScaling() {
    if (!activeBom) return;

    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value) || 0;
    const baseYield = parseFloat(activeBom.yield_quantity) || 1.0;
    const scale = baseYield > 0 ? (plannedQty / baseYield) : 1.0;

    document.getElementById('dispBatchMultiplier').textContent = scale.toFixed(2) + 'x';
    
    // Auto sync actual yield with planned if unmodified
    const actualInput = document.getElementById('prdActualYield');
    if (!actualInput.dataset.manualEdited) {
        actualInput.value = plannedQty;
    }
    recalculateYieldEfficiency();

    // Render scaled ingredients table & lookup available stock
    renderScaledIngredients(scale);
}

function recalculateYieldEfficiency() {
    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value) || 0;
    const actualQty = parseFloat(document.getElementById('prdActualYield').value) || 0;
    const eff = plannedQty > 0 ? ((actualQty / plannedQty) * 100) : 100.0;
    document.getElementById('dispYieldEfficiency').textContent = eff.toFixed(1) + '%';
}

document.getElementById('prdActualYield')?.addEventListener('input', function() {
    this.dataset.manualEdited = "true";
});

function renderScaledIngredients(scale) {
    const tbody = document.getElementById('prdIngredientsTbody');
    const items = activeBom.items || [];
    document.getElementById('dispIngredientCountBadge').textContent = `${items.length} ${items.length === 1 ? 'Ingredient' : 'Ingredients'}`;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    This BOM recipe currently has no component ingredients registered.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    let totalRawCost = 0.0;
    currentShortages = [];

    items.forEach(it => {
        const reqQty = scale * (parseFloat(it.quantity) || 0);
        
        // Find real-time stock from rawProducts or it.raw_item
        const rawProd = rawProducts.find(p => p.id === it.raw_item_id) || it.raw_item || {};
        const onHand = parseFloat(rawProd.current_stock !== undefined ? rawProd.current_stock : (it.raw_item?.current_stock || 0));
        const unitCost = parseFloat(rawProd.cost_price || it.unit_cost || 0);
        const lineCost = reqQty * unitCost;
        totalRawCost += lineCost;

        const isShort = reqQty > onHand;
        if (isShort) {
            currentShortages.push({
                sku: it.sku,
                name: it.raw_item_name,
                uom: it.uom,
                required: reqQty,
                available: onHand,
                deficit: reqQty - onHand
            });
        }

        html += `
            <tr style="${isShort ? 'background-color: #fff1f2;' : ''}">
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${it.sku}</td>
                <td>
                    <div style="font-weight: 600;">${escapeHtml(it.raw_item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted);">${escapeHtml(it.notes || '')}</div>
                </td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">
                    ${reqQty.toFixed(4)} <small>${it.uom}</small>
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; ${isShort ? 'color: #e11d48; font-weight: 700;' : ''}">
                    ${onHand.toFixed(2)} <small>${it.uom}</small>
                </td>
                <td style="text-align: center;">
                    ${isShort ? 
                        `<span class="hr-badge hr-badge-red" title="Deficit: -${(reqQty - onHand).toFixed(2)} ${it.uom}"><i class="ph ph-warning"></i> Deficit</span>` : 
                        `<span class="hr-badge hr-badge-green"><i class="ph ph-check"></i> Sufficient</span>`
                    }
                </td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${unitCost.toFixed(2)}</td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">₱${lineCost.toFixed(2)}</td>
            </tr>
        `;
    });

    tbody.innerHTML = html;

    // Update cost calculations
    const laborCost = (parseFloat(activeBom.labor_cost) || 0) * scale;
    const overheadCost = (parseFloat(activeBom.overhead_cost) || 0) * scale;
    const totalProdCost = totalRawCost + laborCost + overheadCost;
    const actualQty = parseFloat(document.getElementById('prdActualYield').value) || 1.0;
    const unitProdCost = actualQty > 0 ? (totalProdCost / actualQty) : 0;

    document.getElementById('dispTotalRawCost').textContent = '₱' + totalRawCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispLaborOverheadCost').textContent = '₱' + (laborCost + overheadCost).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispTotalProductionCost').textContent = '₱' + totalProdCost.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('dispUnitProductionCost').textContent = '₱' + unitProdCost.toFixed(2) + ` / ${activeBom.uom}`;

    // Overdraft Alert Banner
    const banner = document.getElementById('prdDeficitBanner');
    const bannerDesc = document.getElementById('prdDeficitDesc');
    if (currentShortages.length > 0) {
        banner.style.display = 'block';
        bannerDesc.innerHTML = `
            <strong>Warning:</strong> ${currentShortages.length} raw material(s) have insufficient stock on hand:
            <ul style="margin: 6px 0 0 16px; padding: 0;">
                ${currentShortages.map(s => `<li><strong>${s.name}:</strong> Requires ${s.required.toFixed(2)} ${s.uom}, only ${s.available.toFixed(2)} ${s.uom} on hand (Deficit: -${s.deficit.toFixed(2)} ${s.uom})</li>`).join('')}
            </ul>
        `;
    } else {
        banner.style.display = 'none';
    }
}

/* --------------------------------------------------------------------------
   SUBMIT PRODUCTION BATCH (WITH POPUP SHORTAGE NOTIFICATION)
   -------------------------------------------------------------------------- */
async function submitProductionBatch(forceShortageOverride = false) {
    if (!activeBom) {
        Swal.fire({ icon: 'warning', title: 'Target Product Required', text: 'Please select a finished product with an active BOM.', confirmButtonColor: '#9333ea' });
        return;
    }

    const plannedQty = parseFloat(document.getElementById('prdPlannedQty').value);
    const actualQty = parseFloat(document.getElementById('prdActualYield').value);
    if (!plannedQty || plannedQty <= 0) {
        Swal.fire({ icon: 'warning', title: 'Invalid Quantity', text: 'Please enter a valid planned output quantity.', confirmButtonColor: '#9333ea' });
        return;
    }

    // 1. Check for shortages & Trigger popup notification if unavailable
    if (currentShortages.length > 0 && !forceShortageOverride) {
        let shortageListHtml = '<div style="text-align: left; font-size: 13px; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 12px; margin-top: 10px;">';
        shortageListHtml += '<strong style="color: #9f1239;">Deficient Ingredients:</strong><ul style="margin: 6px 0 0 16px; padding: 0;">';
        currentShortages.forEach(s => {
            shortageListHtml += `<li><strong>${escapeHtml(s.name)}:</strong> Need ${s.required.toFixed(2)} ${s.uom} &bull; On Hand: ${s.available.toFixed(2)} (Deficit: <span style="color: #e11d48; font-weight: 700;">-${s.deficit.toFixed(2)} ${s.uom}</span>)</li>`;
        });
        shortageListHtml += '</ul></div>';
        shortageListHtml += '<p style="margin-top: 12px; font-size: 12px; color: #475569;">Proceeding will deduct stock into negative balance or require emergency kitchen inventory replenishment. Do you still want to proceed?</p>';

        const result = await Swal.fire({
            icon: 'warning',
            title: 'Raw Material Shortage Detected!',
            html: shortageListHtml,
            showCancelButton: true,
            confirmButtonText: 'Proceed Anyway (Allow Shortage)',
            cancelButtonText: 'Cancel & Adjust Batch Size',
            confirmButtonColor: '#e11d48',
            cancelButtonColor: '#64748b',
            focusCancel: true
        });

        if (!result.isConfirmed) {
            return; // User canceled to adjust batch
        }
        forceShortageOverride = true;
    }

    // 2. Transmit batch run to backend
    const payload = {
        bill_of_materials_id: activeBom.id,
        planned_quantity: plannedQty,
        actual_quantity: actualQty,
        production_date: document.getElementById('prdProductionDate').value,
        expiry_date: document.getElementById('prdExpiryDate').value || null,
        kitchen_station: document.getElementById('prdKitchenStation').value,
        produced_by: document.getElementById('prdProducedBy').value || 'Executive Sous Chef',
        verified_by: document.getElementById('prdVerifiedBy').value || null,
        proceed_with_shortage: forceShortageOverride,
        quality_notes: document.getElementById('prdQualityNotes').value || null,
    };

    try {
        Swal.fire({
            title: 'Executing Production Batch...',
            text: 'Deducting raw materials and incrementing finished inventory in Master Stock Ledger.',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const res = await fetch("{{ route('inventory.api.create-production-batch') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const json = await res.json();
        if (!res.ok || !json.success) {
            throw new Error(json.message || 'Failed to complete production batch.');
        }

        await fetchLiveProductionData();

        Swal.fire({
            icon: 'success',
            title: 'Production Completed!',
            html: `
                <p>Batch <strong>${json.data.order.production_number}</strong> (${json.data.order.batch_lot_number}) successfully manufactured.</p>
                <p style="font-size: 12px; color: #64748b;">
                    Added <strong>+${actualQty} ${activeBom.uom}</strong> to <strong>${activeBom.item_name}</strong> in Stocks Overview.
                    All raw materials consumed and logged to Master Ledger.
                </p>
            `,
            confirmButtonColor: '#9333ea'
        });

        switchPrdTab('tab-batch-list');
    } catch (e) {
        Swal.fire({
            icon: 'error',
            title: 'Production Error',
            text: e.message,
            confirmButtonColor: '#9333ea'
        });
    }
}

/* --------------------------------------------------------------------------
   BACKEND SYNCHRONIZATION
   -------------------------------------------------------------------------- */
async function fetchLiveProductionData() {
    try {
        const res = await fetch("{{ route('inventory.api.production-data') }}");
        const json = await res.json();
        if (json.success && json.data) {
            rawOrders = json.data.orders || [];
            rawBoms = json.data.boms || [];
            rawProducts = json.data.products || [];

            renderProductionOrders();
            renderBomCatalog();
        }
    } catch (e) {
        console.error('Failed to sync live production data:', e);
    }
}

/* --------------------------------------------------------------------------
   TAB 3: BOM CATALOG
   -------------------------------------------------------------------------- */
function renderBomCatalog() {
    const tbody = document.getElementById('prdBomCatalogTbody');
    if (!tbody) return;
    document.getElementById('badgeBomCount').textContent = rawBoms.length;

    if (rawBoms.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="10" style="text-align: center; color: var(--prd-text-muted); padding: 30px;">
                    No BOM recipes configured yet.
                </td>
            </tr>
        `;
        return;
    }

    let html = '';
    rawBoms.forEach(b => {
        const yieldVal = parseFloat(b.yield_quantity) || 1.0;
        const rawCost = parseFloat(b.total_raw_cost) || 0;
        const laborOverhead = (parseFloat(b.labor_cost) || 0) + (parseFloat(b.overhead_cost) || 0);
        const totalCost = parseFloat(b.total_cost) || (rawCost + laborOverhead);
        const sellingPrice = parseFloat(b.selling_price) || 0;
        const margin = parseFloat(b.margin_percentage) || 0;

        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${b.bom_code}</td>
                <td>
                    <div style="font-weight: 700; color: var(--prd-text-strong);">${escapeHtml(b.item_name)}</div>
                    <div style="font-size: 11px; color: var(--prd-text-muted); font-family: monospace;">SKU: ${b.sku}</div>
                </td>
                <td><span class="hr-badge hr-badge-neutral">${b.category || 'General'}</span></td>
                <td style="text-align: right; font-weight: 600;">${yieldVal.toFixed(2)} ${b.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${rawCost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${laborOverhead.toFixed(2)}</td>
                <td style="text-align: right; font-weight: 700; font-variant-numeric: tabular-nums;">₱${totalCost.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${sellingPrice.toFixed(2)}</td>
                <td style="text-align: center;">
                    <span class="hr-badge hr-badge-green">${margin.toFixed(1)}%</span>
                </td>
                <td style="text-align: right;">
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="selectBomForBatch(${b.id})" title="Start production run for this recipe">
                        <i class="ph ph-plus-circle"></i>
                        <span>Produce</span>
                    </button>
                </td>
            </tr>
        `;
    });

    tbody.innerHTML = html;
}

function selectBomForBatch(bomId) {
    const select = document.getElementById('prdFinishedProductSelect');
    if (select) {
        select.value = bomId;
        handleProductSelection(bomId);
    }
    switchPrdTab('tab-new-batch');
}

/* --------------------------------------------------------------------------
   MODAL BATCH TICKET
   -------------------------------------------------------------------------- */
function openPrdTicketModal(orderId) {
    const o = rawOrders.find(ord => ord.id === orderId);
    if (!o) return;

    document.getElementById('modalTicketTitle').textContent = `Kitchen Batch Work Order: ${o.item_name}`;
    document.getElementById('modalTicketBatchNo').textContent = `${o.production_number} [${o.status}]`;
    document.getElementById('modalTicketProduct').textContent = `${o.item_name} (${o.sku})`;
    document.getElementById('modalTicketLot').textContent = o.batch_lot_number || '-';
    document.getElementById('modalTicketStation').textContent = o.kitchen_station || 'Prep Kitchen';
    document.getElementById('modalTicketPlanned').textContent = `${parseFloat(o.planned_quantity).toFixed(1)} ${o.uom}`;
    document.getElementById('modalTicketActual').textContent = `${parseFloat(o.actual_quantity).toFixed(1)} ${o.uom}`;
    document.getElementById('modalTicketEfficiency').textContent = `${parseFloat(o.yield_efficiency_percent || 100).toFixed(1)}%`;
    document.getElementById('modalTicketDate').textContent = formatDate(o.production_date);
    document.getElementById('modalTicketExpiry').textContent = formatDate(o.expiry_date);
    document.getElementById('modalTicketChef').textContent = o.produced_by;

    const tbody = document.getElementById('modalTicketItemsTbody');
    let html = '';
    (o.items || []).forEach(it => {
        const qty = parseFloat(it.actual_consumed_qty || it.required_qty) || 0;
        const unit = parseFloat(it.unit_cost) || 0;
        const line = parseFloat(it.total_cost) || (qty * unit);
        html += `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--prd-primary);">${it.sku}</td>
                <td><strong>${escapeHtml(it.item_name)}</strong></td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">${qty.toFixed(4)} ${it.uom}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums;">₱${unit.toFixed(2)}</td>
                <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${line.toFixed(2)}</td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    document.getElementById('prdTicketModal').classList.add('open');
}

function closePrdTicketModal() {
    document.getElementById('prdTicketModal').classList.remove('open');
}

/* --------------------------------------------------------------------------
   HELPERS
   -------------------------------------------------------------------------- */
function formatDate(dStr) {
    if (!dStr) return '-';
    try {
        const d = new Date(dStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    } catch (e) {
        return dStr;
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

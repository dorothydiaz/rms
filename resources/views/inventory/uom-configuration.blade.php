@extends('layouts.app')

@section('title', 'Unit of Measurement (UOM) Configuration - Inventory Operations')

@push('styles')
<style>
/* ==========================================================================
   UOM CONFIGURATION & RFQ-STYLE MODULAR DIRECTORY DESIGN SYSTEM
   RMS Industrial Ergonomics & Glassmorphism (WCAG 2.1 AA Compliant)
   ========================================================================== */
:root {
    --uom-surface-canvas: #f8fafc;
    --uom-surface-card: rgba(255, 255, 255, 0.92);
    --uom-surface-card-hover: rgba(255, 255, 255, 0.98);
    --uom-glass-bg: rgba(255, 255, 255, 0.85);
    --uom-glass-border: rgba(226, 232, 240, 0.85);
    
    --uom-primary: #a855f7;
    --uom-primary-dark: #9333ea;
    --uom-primary-light: #c084fc;
    --uom-primary-subtle: rgba(168, 85, 247, 0.12);
    --uom-primary-glow: rgba(168, 85, 247, 0.28);
    
    --uom-text-strong: #0f172a;
    --uom-text-medium: #334155;
    --uom-text-muted: #64748b;
    --uom-text-subtle: #94a3b8;
    
    --uom-border-subtle: #e2e8f0;
    --uom-border-focus: #c084fc;
    
    --uom-success: #10b981;
    --uom-success-subtle: rgba(16, 185, 129, 0.12);
    --uom-warning: #f59e0b;
    --uom-warning-subtle: rgba(245, 158, 11, 0.12);
    --uom-danger: #ef4444;
    --uom-danger-subtle: rgba(239, 68, 68, 0.12);
    --uom-info: #0ea5e9;
    --uom-info-subtle: rgba(14, 165, 233, 0.12);
}

.uom-page-container {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
    position: relative;
    padding-bottom: 40px;
}

/* 1. Header Section (Matching RMS Industrial Header) */
.uom-header-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}
.uom-title-area h1 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--uom-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 2px 0;
    letter-spacing: -0.01em;
}
.uom-title-area p {
    font-size: 0.8rem;
    color: var(--uom-text-muted);
    margin: 0;
}
.uom-header-actions {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Buttons */
.uom-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 14px;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
    text-decoration: none;
    white-space: nowrap;
}
.uom-btn-primary {
    background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px var(--uom-primary-glow);
}
.uom-btn-primary:hover {
    background: linear-gradient(135deg, #9333ea 0%, #7e22ce 100%);
    transform: translateY(-1px);
}
.uom-btn-secondary {
    background: #ffffff;
    border: 1px solid var(--uom-border-subtle);
    color: var(--uom-text-medium);
}
.uom-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--uom-text-strong);
}

/* 2. KPI Metrics Cards */
.uom-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 12px;
}
.uom-metric-card {
    background: var(--uom-surface-card);
    border: 1px solid var(--uom-border-subtle);
    border-radius: 12px;
    padding: 12px 16px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.uom-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
}
.uom-metric-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
}
.uom-metric-icon.purple { background: var(--uom-primary-subtle); color: var(--uom-primary); }
.uom-metric-icon.emerald { background: var(--uom-success-subtle); color: var(--uom-success); }
.uom-metric-icon.blue { background: var(--uom-info-subtle); color: var(--uom-info); }
.uom-metric-icon.amber { background: var(--uom-warning-subtle); color: var(--uom-warning); }

.uom-metric-info {
    display: flex;
    flex-direction: column;
}
.uom-metric-val {
    font-size: 1.3rem;
    font-weight: 800;
    color: var(--uom-text-strong);
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
}
.uom-metric-lbl {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--uom-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin-top: 3px;
}

/* 3. Toolbar (Without Category Tabs) */
.uom-toolbar-card {
    background: var(--uom-surface-card);
    border: 1px solid var(--uom-border-subtle);
    border-radius: 12px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.02);
    flex-wrap: wrap;
}
.uom-search-box {
    position: relative;
    flex: 1;
    min-width: 260px;
    max-width: 520px;
}
.uom-search-box i {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--uom-text-subtle);
    font-size: 15px;
}
.uom-search-input {
    width: 100%;
    padding: 7px 10px 7px 32px;
    font-size: 0.8rem;
    border-radius: 8px;
    border: 1px solid var(--uom-border-subtle);
    background: #ffffff;
    color: var(--uom-text-strong);
    box-sizing: border-box;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.uom-search-input:focus {
    outline: none;
    border-color: var(--uom-border-focus);
    box-shadow: 0 0 0 3px var(--uom-primary-subtle);
}

/* ==========================================================================
   RFQ-STYLE MODULAR STACKED TABLE & EXPANSION ACCORDION
   ========================================================================== */
.uom-table-container {
    background: #ffffff;
    border: 1px solid var(--uom-border-subtle);
    border-radius: 12px;
    overflow: visible;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.03);
    position: relative;
}
.uom-table-responsive {
    width: 100%;
    overflow-x: auto;
    min-height: 320px;
}
.uom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
    text-align: left;
}
.uom-table thead {
    background: #f8fafc;
    border-bottom: 1.5px solid var(--uom-border-subtle);
}
.uom-th-stacked {
    padding: 8px 12px !important;
    vertical-align: middle;
    user-select: none;
    position: relative;
}
.uom-th-content {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.uom-th-title {
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--uom-text-strong);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    white-space: nowrap;
}
.uom-th-sub {
    font-size: 0.67rem;
    font-weight: 500;
    color: var(--uom-text-muted);
    white-space: nowrap;
}

/* Expansion Button (Matching RFQ .rfq-row-expand-btn) */
.uom-row-expand-btn {
    width: 22px;
    height: 22px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
    background: #ffffff;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11px;
    transition: all 0.15s ease;
    margin-right: 8px;
    flex-shrink: 0;
}
.uom-row-expand-btn:hover {
    border-color: var(--uom-primary);
    color: var(--uom-primary);
    background: var(--uom-primary-subtle);
}
.uom-row-expand-btn.is-expanded {
    background: var(--uom-primary);
    border-color: var(--uom-primary);
    color: #ffffff;
}
.uom-row-expand-btn.is-expanded i {
    transform: rotate(90deg);
}

/* Expandable Multi-Line Subtable Accordion */
.uom-line-expansion-row {
    background: #fafafa !important;
}
.uom-subtable-container {
    padding: 12px 16px 16px 36px;
    background: #f8fafc;
    border-top: 1px dashed #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
}
.uom-subtable {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    font-size: 0.77rem;
}
.uom-subtable th {
    background: #f1f5f9;
    padding: 7px 10px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--uom-text-strong);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid #e2e8f0;
}
.uom-subtable td {
    padding: 8px 10px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
}
.uom-subtable tr:hover {
    background: #f8fafc;
}

/* Gear Button in Action Header */
.uom-header-action-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    position: relative;
}
.uom-th-settings-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--uom-border-subtle);
    color: var(--uom-text-medium);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.15s ease;
    padding: 0;
}
.uom-th-settings-btn:hover, .uom-th-settings-btn.is-active {
    background: var(--uom-primary-subtle);
    border-color: var(--uom-primary-light);
    color: var(--uom-primary-dark);
}

/* Table Column Settings Floating Dropdown Menu (Switchable List) */
.uom-table-settings-dropdown {
    position: fixed;
    width: 350px;
    max-width: min(350px, 92vw);
    background: #ffffff;
    border: 1.5px solid var(--uom-border-subtle);
    border-radius: 12px;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22), 0 4px 14px rgba(15, 23, 42, 0.08);
    padding: 12px 14px;
    z-index: 9999;
    display: none;
    text-align: left;
    box-sizing: border-box;
    font-size: 0.8rem;
}
.uom-table-settings-dropdown.is-active {
    display: block;
    animation: dropdownFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes dropdownFadeIn {
    from { opacity: 0; transform: translateY(-6px) scale(0.98); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
.uom-settings-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 6px;
}
.uom-settings-title {
    font-size: 0.82rem;
    font-weight: 700;
    color: var(--uom-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}
.uom-col-counter {
    font-size: 0.7rem;
    font-weight: 600;
    color: var(--uom-primary-dark);
    background: var(--uom-primary-subtle);
    padding: 1px 7px;
    border-radius: 12px;
}
.uom-settings-links-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid #f1f5f9;
}
.uom-settings-link-btn {
    background: none;
    border: none;
    color: var(--uom-primary);
    font-size: 0.72rem;
    font-weight: 600;
    cursor: pointer;
    padding: 0;
}
.uom-settings-link-btn:hover {
    text-decoration: underline;
}
.uom-settings-search-input {
    width: 100%;
    padding: 5px 8px;
    font-size: 0.76rem;
    border: 1px solid var(--uom-border-subtle);
    border-radius: 6px;
    margin-bottom: 8px;
    box-sizing: border-box;
}
.uom-settings-col-list {
    max-height: 240px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.uom-settings-col-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 8px;
    border-radius: 6px;
    transition: background 0.12s ease;
}
.uom-settings-col-item:hover {
    background: #f8fafc;
}
.uom-col-item-info {
    display: flex;
    flex-direction: column;
    gap: 1px;
}
.uom-col-item-title {
    font-size: 0.77rem;
    font-weight: 600;
    color: var(--uom-text-strong);
}
.uom-col-item-subtitle {
    font-size: 0.68rem;
    color: var(--uom-text-muted);
}

/* Table Body Stacked Rows */
.uom-table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}
.uom-table tbody tr:hover {
    background: rgba(248, 250, 252, 0.8);
}
.uom-td-stacked {
    padding: 10px 12px;
    vertical-align: middle;
}
.uom-cell-top {
    font-weight: 700;
    color: var(--uom-text-strong);
    line-height: 1.25;
}
.uom-cell-bottom {
    font-size: 0.71rem;
    color: var(--uom-text-muted);
    margin-top: 2px;
    line-height: 1.2;
}

/* Badges */
.uom-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.71rem;
    font-weight: 700;
    letter-spacing: 0.02em;
}
.uom-badge-cat-count { background: #e0e7ff; color: #3730a3; }
.uom-badge-cat-weight { background: #dcfce7; color: #166534; }
.uom-badge-cat-volume { background: #e0f2fe; color: #0369a1; }
.uom-badge-cat-portion { background: #ffedd5; color: #c2410c; }
.uom-badge-cat-length { background: #fef3c7; color: #92400e; }
.uom-badge-cat-area { background: #ccfbf1; color: #0f766e; }
.uom-badge-cat-time { background: #fae8ff; color: #86198f; }
.uom-badge-cat-overhead { background: #ede9fe; color: #6d28d9; }
.uom-badge-cat-custom { background: #f1f5f9; color: #334155; }

.uom-badge-base {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
    font-size: 0.68rem;
    padding: 1px 6px;
    font-weight: 700;
}
.uom-badge-active { background: var(--uom-success-subtle); color: #047857; }
.uom-badge-inactive { background: #f1f5f9; color: #64748b; }

/* Status Toggle Switch Pill */
.uom-switch {
    position: relative;
    display: inline-block;
    width: 34px;
    height: 18px;
}
.uom-switch input { opacity: 0; width: 0; height: 0; }
.uom-slider {
    position: absolute;
    cursor: pointer;
    top: 0; left: 0; right: 0; bottom: 0;
    background-color: #cbd5e1;
    transition: .2s;
    border-radius: 20px;
}
.uom-slider:before {
    position: absolute;
    content: "";
    height: 12px;
    width: 12px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .2s;
    border-radius: 50%;
}
input:checked + .uom-slider { background-color: var(--uom-success); }
input:checked + .uom-slider:before { transform: translateX(16px); }

/* Table Pagination Bar with Rows-per-Page in Footer */
.uom-pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 16px;
    border-top: 1px solid #f1f5f9;
    background: rgba(248, 250, 252, 0.7);
    flex-wrap: wrap;
    gap: 12px;
    border-radius: 0 0 12px 12px;
}
.uom-pagination-info { font-size: 0.75rem; color: var(--uom-text-muted); }
.uom-page-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--uom-border-subtle);
    color: var(--uom-text-medium);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.15s ease;
}
.uom-page-btn:hover:not(:disabled) {
    background: var(--uom-primary-subtle);
    border-color: var(--uom-primary-light);
    color: var(--uom-primary-dark);
}
.uom-page-btn.is-active {
    background: var(--uom-primary);
    border-color: var(--uom-primary);
    color: #ffffff;
    font-weight: 700;
}
.uom-page-btn:disabled { opacity: 0.4; cursor: not-allowed; }

/* 4. Slide-out Drawer / Modal for Add & Edit UOM */
.uom-drawer-overlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
    z-index: 10000;
    display: none;
    opacity: 0;
    transition: opacity 0.2s ease;
}
.uom-drawer-overlay.is-open {
    display: block;
    opacity: 1;
}
.uom-drawer {
    position: fixed;
    top: 0; right: 0;
    width: min(520px, 94vw);
    height: 100%;
    background: #ffffff;
    box-shadow: -8px 0 32px rgba(15, 23, 42, 0.18);
    z-index: 10001;
    transform: translateX(100%);
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
}
.uom-drawer.is-open { transform: translateX(0); }
.uom-drawer-header {
    padding: 16px 20px;
    border-bottom: 1px solid var(--uom-border-subtle);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: #f8fafc;
}
.uom-drawer-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--uom-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
}
.uom-drawer-close {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    background: #ffffff;
    border: 1px solid var(--uom-border-subtle);
    color: var(--uom-text-muted);
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}
.uom-drawer-close:hover {
    background: #fee2e2;
    border-color: #fca5a5;
    color: var(--uom-danger);
}
.uom-drawer-body {
    padding: 20px;
    overflow-y: auto;
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.uom-drawer-footer {
    padding: 14px 20px;
    border-top: 1px solid var(--uom-border-subtle);
    background: #f8fafc;
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
}

/* Form Groups */
.uom-form-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.uom-form-group label {
    font-size: 0.77rem;
    font-weight: 600;
    color: var(--uom-text-medium);
}
.uom-form-group label .req { color: var(--uom-danger); }
.uom-form-input, .uom-form-select, .uom-form-textarea {
    width: 100%;
    padding: 8px 12px;
    font-size: 0.82rem;
    border: 1px solid var(--uom-border-subtle);
    border-radius: 8px;
    background: #ffffff;
    color: var(--uom-text-strong);
    box-sizing: border-box;
    transition: all 0.15s ease;
}
.uom-form-input:focus, .uom-form-select:focus, .uom-form-textarea:focus {
    outline: none;
    border-color: var(--uom-border-focus);
    box-shadow: 0 0 0 3px var(--uom-primary-subtle);
}
.uom-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.uom-ratio-preview-box {
    background: #f8fafc;
    border: 1px dashed var(--uom-primary-light);
    border-radius: 8px;
    padding: 10px 14px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.8rem;
    color: var(--uom-text-medium);
}
.uom-ratio-formula {
    font-weight: 700;
    color: var(--uom-primary-dark);
}

/* Category Modal */
.uom-modal {
    position: fixed;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.95);
    width: min(440px, 92vw);
    background: #ffffff;
    border-radius: 14px;
    box-shadow: 0 24px 54px rgba(15, 23, 42, 0.25);
    z-index: 10002;
    display: none;
    flex-direction: column;
    overflow: hidden;
    transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
.uom-modal.is-open {
    display: flex;
    transform: translate(-50%, -50%) scale(1);
}

/* Toast */
.uom-toast {
    position: fixed;
    bottom: 24px; right: 24px;
    padding: 10px 16px;
    background: #0f172a;
    color: #ffffff;
    border-radius: 8px;
    font-size: 0.82rem;
    font-weight: 600;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    z-index: 100000;
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0;
    transform: translateY(10px);
    transition: all 0.2s ease;
    pointer-events: none;
}
.uom-toast.show {
    opacity: 1;
    transform: translateY(0);
}
.uom-toast.success { background: #047857; }
.uom-toast.danger { background: #b91c1c; }
</style>
@endpush

@section('content')
<div class="uom-page-container">

    <!-- 1. Page Header -->
    <div class="uom-header-row">
        <div class="uom-title-area">
            <h1><i class="ph ph-scales" style="color: var(--uom-primary);"></i> Unit of Measurement (UOM) Configuration</h1>
            <p>Master catalog of measurement categories and expandable conversion units for recipes, purchase orders, and inventory.</p>
        </div>
        <div class="uom-header-actions">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="openNewCategoryModal()">
                <i class="ph ph-folder-plus"></i> <span>New Category</span>
            </button>
            <button type="button" class="uom-btn uom-btn-primary" onclick="openNewUomDrawer()">
                <i class="ph ph-plus-circle"></i> <span>Add UOM</span>
            </button>
        </div>
    </div>

    <!-- 2. KPI Metrics Grid -->
    <div class="uom-metrics-grid">
        <div class="uom-metric-card">
            <div class="uom-metric-icon blue"><i class="ph ph-folder"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiTotalCategories">{{ count($initialCategories) }}</div>
                <div class="uom-metric-lbl">Measurement Categories</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon purple"><i class="ph ph-scales"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiTotalUnits">{{ count($initialUnits) }}</div>
                <div class="uom-metric-lbl">Total Measurements</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon emerald"><i class="ph ph-check-circle"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiActiveUnits">{{ $initialUnits->where('uom_is_active', true)->count() }}</div>
                <div class="uom-metric-lbl">Active in Dropdowns</div>
            </div>
        </div>
        <div class="uom-metric-card">
            <div class="uom-metric-icon amber"><i class="ph ph-star"></i></div>
            <div class="uom-metric-info">
                <div class="uom-metric-val" id="kpiBaseUnits">{{ $initialUnits->where('is_base_unit', true)->count() }}</div>
                <div class="uom-metric-lbl">Reference (Base) Units</div>
            </div>
        </div>
    </div>

    <!-- 3. Toolbar (No Category Tabs) -->
    <div class="uom-toolbar-card">
        <div class="uom-search-box">
            <i class="ph ph-magnifying-glass"></i>
            <input type="text" id="uomSearchInput" class="uom-search-input" placeholder="Search categories, measurement codes, or descriptive names..." oninput="handleUomSearch(this.value)">
        </div>
        <div style="font-size: 0.74rem; color: var(--uom-text-muted); display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-info"></i>
            <span>Click the <strong><i class="ph ph-caret-right"></i></strong> button on any category to view and manage its units.</span>
        </div>
    </div>

    <!-- 4. Category-First Primary Table with Expandable Accordion Subtables -->
    <div class="uom-table-container">
        <div class="uom-table-responsive">
            <table class="uom-table" id="uomDirectoryTable">
                <thead id="uomThead">
                    <tr id="uomTheadRow">
                        <!-- Rendered dynamically by renderTableHeader() with modular stacked headers -->
                    </tr>
                </thead>
                <tbody id="uomTbody">
                    <!-- Rendered dynamically by renderCategoryDirectory() -->
                </tbody>
            </table>

            <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear (Switchable List) -->
            <div id="uomTableSettingsDropdown" class="uom-table-settings-dropdown">
                <div class="uom-settings-title-row">
                    <div class="uom-settings-title">
                        <i class="ph ph-gear-six" style="color: var(--uom-primary);"></i>
                        <span>Visible Columns & Setup</span>
                    </div>
                    <span class="uom-col-counter" id="uomColActiveCounter">5 of 6</span>
                </div>
                <div class="uom-settings-links-row">
                    <button type="button" class="uom-settings-link-btn" onclick="resetColumnDefaults()">Reset to Default</button>
                    <button type="button" class="uom-settings-link-btn" onclick="toggleAllColumns(true)">Select All</button>
                </div>
                <input type="text" class="uom-settings-search-input" placeholder="Filter columns..." oninput="filterColumnSettingsList(this.value)">
                <div class="uom-settings-col-list" id="uomSettingsCheckboxesList">
                    <!-- Populated dynamically with switchable toggles -->
                </div>
            </div>
        </div>

        <!-- Pagination Bar with Rows-Per-Page in Footer -->
        <div class="uom-pagination-bar" id="uomPaginationBar">
            <div class="uom-pagination-info" id="uomPaginationInfo">
                Showing <strong>1</strong> to <strong>{{ count($initialCategories) }}</strong> of <strong>{{ count($initialCategories) }}</strong> categories
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 6px; font-size: 0.74rem; color: var(--uom-text-muted);">
                    <span>Show</span>
                    <select id="uomPageSize" class="uom-form-select" style="width: auto; padding: 3px 8px; font-size: 0.75rem; border-radius: 6px;" onchange="changePageSize(this.value)">
                        <option value="5">5</option>
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                    <span>rows</span>
                </div>
                <div class="uom-pagination-controls" id="uomPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

</div>

<!-- 5. Slide-out Drawer: Add / Edit UOM -->
<div class="uom-drawer-overlay" id="uomDrawerOverlay" onclick="closeUomDrawer()"></div>
<div class="uom-drawer" id="uomDrawer">
    <div class="uom-drawer-header">
        <div class="uom-drawer-title">
            <i class="ph ph-scales" style="color: var(--uom-primary);"></i>
            <span id="uomDrawerTitle">Add Unit of Measurement</span>
        </div>
        <button type="button" class="uom-drawer-close" onclick="closeUomDrawer()"><i class="ph ph-x"></i></button>
    </div>
    <form id="uomForm" onsubmit="handleSaveUom(event)">
        <input type="hidden" id="formUomId">
        <div class="uom-drawer-body">

            <div class="uom-form-row-2">
                <div class="uom-form-group">
                    <label for="formUomCode">UoM Short Code <span class="req">*</span></label>
                    <input type="text" id="formUomCode" class="uom-form-input" required placeholder="e.g. PCS, BOX-10, KG" style="text-transform: uppercase;" oninput="updateDrawerFormulaPreview()">
                </div>
                <div class="uom-form-group">
                    <label for="formUomCategory">Category <span class="req">*</span></label>
                    <div style="display: flex; gap: 6px;">
                        <select id="formUomCategory" class="uom-form-select" required onchange="handleDrawerCategoryChange()">
                            @foreach($initialCategories as $cat)
                                <option value="{{ $cat->category_code }}" data-base="{{ $cat->base_uom_code }}">{{ $cat->category_code }} ({{ $cat->category_name }})</option>
                            @endforeach
                        </select>
                        <button type="button" class="uom-btn uom-btn-secondary" style="padding: 0 8px;" onclick="openNewCategoryModal()" title="Add New Category">
                            <i class="ph ph-plus"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="uom-form-group">
                <label for="formUomName">Descriptive Measurement Name <span class="req">*</span></label>
                <input type="text" id="formUomName" class="uom-form-input" required placeholder="e.g. Pieces / Each, Box of 10 Cans, Kilogram">
            </div>

            <!-- Reference / Base Checkbox -->
            <div style="background: #f8fafc; border: 1px solid var(--uom-border-subtle); border-radius: 8px; padding: 10px 14px; display: flex; align-items: flex-start; gap: 10px;">
                <input type="checkbox" id="formIsBase" style="margin-top: 3px;" onchange="handleIsBaseToggle(this.checked)">
                <div>
                    <label for="formIsBase" style="font-weight: 700; cursor: pointer; color: var(--uom-text-strong);">Is Category Reference (Base) Unit?</label>
                    <div style="font-size: 0.72rem; color: var(--uom-text-muted);">
                        The reference unit is the foundational 1.0 multiplier for this category (e.g. Gram for Weight, Piece for Count). All other units convert relative to it.
                    </div>
                </div>
            </div>

            <!-- Ratio / Multiplier Section -->
            <div id="uomRatioSection">
                <div class="uom-form-row-2">
                    <div class="uom-form-group">
                        <label for="formBaseRatio">Measurement Quantity / Ratio <span class="req">*</span></label>
                        <input type="number" step="any" min="0.000001" id="formBaseRatio" class="uom-form-input" value="1.0" required oninput="updateDrawerFormulaPreview()">
                    </div>
                    <div class="uom-form-group">
                        <label for="formPrecision">Rounding Precision</label>
                        <input type="number" step="0.0001" min="0.0001" id="formPrecision" class="uom-form-input" value="0.0100">
                    </div>
                </div>

                <div class="uom-ratio-preview-box" id="uomFormulaPreviewBox">
                    <i class="ph ph-calculator" style="font-size: 18px; color: var(--uom-primary);"></i>
                    <div>
                        <div style="font-size: 0.7rem; color: var(--uom-text-muted);">Conversion Formula Preview:</div>
                        <div class="uom-ratio-formula" id="uomFormulaPreviewText">1 BOX-10 = 10 PCS</div>
                    </div>
                </div>
            </div>

            <!-- Active in Dropdowns Toggle -->
            <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 8px; border-top: 1px solid #f1f5f9;">
                <div>
                    <div style="font-weight: 700; font-size: 0.8rem; color: var(--uom-text-strong);">Active in Dropdowns</div>
                    <div style="font-size: 0.72rem; color: var(--uom-text-muted);">Deactivating hides this unit from new selections without breaking historical records.</div>
                </div>
                <label class="uom-switch">
                    <input type="checkbox" id="formIsActive" checked>
                    <span class="uom-slider"></span>
                </label>
            </div>

        </div>
        <div class="uom-drawer-footer">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="closeUomDrawer()">Cancel</button>
            <button type="submit" class="uom-btn uom-btn-primary" id="uomSubmitBtn">
                <i class="ph ph-check"></i> <span id="uomSubmitBtnText">Save Measurement</span>
            </button>
        </div>
    </form>
</div>

<!-- 6. Modal: Create New Category -->
<div class="uom-drawer-overlay" id="uomCategoryModalOverlay" onclick="closeCategoryModal()"></div>
<div class="uom-modal" id="uomCategoryModal">
    <div class="uom-drawer-header">
        <div class="uom-drawer-title">
            <i class="ph ph-folder-plus" style="color: var(--uom-primary);"></i>
            <span>Create Measurement Category</span>
        </div>
        <button type="button" class="uom-drawer-close" onclick="closeCategoryModal()"><i class="ph ph-x"></i></button>
    </div>
    <form id="categoryForm" onsubmit="handleSaveCategory(event)">
        <div class="uom-drawer-body">
            <div class="uom-form-group">
                <label for="catCode">Category Code <span class="req">*</span></label>
                <input type="text" id="catCode" class="uom-form-input" required placeholder="e.g. CONTAINER, PACKAGING, AREA" style="text-transform: uppercase;">
            </div>
            <div class="uom-form-group">
                <label for="catName">Category Descriptive Name <span class="req">*</span></label>
                <input type="text" id="catName" class="uom-form-input" required placeholder="e.g. Shipping & Storage Containers">
            </div>
            <div class="uom-form-group">
                <label for="catBase">Default Base Unit Short Code</label>
                <input type="text" id="catBase" class="uom-form-input" placeholder="e.g. CONTAINER (Leave blank to set on first unit)">
            </div>
            <div class="uom-form-group">
                <label for="catDesc">Category Description</label>
                <textarea id="catDesc" class="uom-form-textarea" rows="2" placeholder="Explain the physical dimension or operational purpose of this category..."></textarea>
            </div>
        </div>
        <div class="uom-drawer-footer">
            <button type="button" class="uom-btn uom-btn-secondary" onclick="closeCategoryModal()">Cancel</button>
            <button type="submit" class="uom-btn uom-btn-primary" id="catSubmitBtn">
                <i class="ph ph-check"></i> <span>Create Category</span>
            </button>
        </div>
    </form>
</div>

<!-- Soft Toast Alert -->
<div class="uom-toast" id="uomToast">
    <i class="ph ph-check-circle" id="uomToastIcon"></i>
    <span id="uomToastText">Operation successful</span>
</div>
@endsection

@push('scripts')
<script>
/**
 * ==========================================================================
 * UOM CATEGORY DIRECTORY CONTROLLER WITH EXPANDABLE MEASUREMENT SUBTABLES
 * Strictly modeled after RFQ Directory & Status Tracker Table Architecture
 * ==========================================================================
 */
let UOM_DATA = @json($initialUnits);
let UOM_CATEGORIES = @json($initialCategories);

// Category Column Definitions (Modular Stacked Headers)
// Start Column (Category Info) and End Column (Actions) are permanently LOCKED!
const CATEGORY_COLUMNS = [
    {
        id: 'category_info',
        label: 'Category Info',
        subLabel: 'Code & Name',
        default: true,
        lockVisible: true, // LOCKED START COLUMN
        defaultWidth: '220px',
        align: 'left',
        topPurpose: 'Primary measurement category code',
        bottomPurpose: 'Descriptive name and physical dimension'
    },
    {
        id: 'base_uom',
        label: 'Reference Base Unit',
        subLabel: '1.0 Reference',
        default: true,
        lockVisible: false,
        defaultWidth: '170px',
        align: 'left',
        topPurpose: 'Base unit with 1.0 quantity multiplier',
        bottomPurpose: 'Standard reference dimension'
    },
    {
        id: 'units_count',
        label: 'Measurements',
        subLabel: 'Active / Total Count',
        default: true,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topPurpose: 'Total number of units configured in this category',
        bottomPurpose: 'Active units available in transaction dropdowns'
    },
    {
        id: 'category_desc',
        label: 'Description & Scope',
        subLabel: 'Operational Purpose',
        default: true,
        lockVisible: false,
        defaultWidth: '240px',
        align: 'left',
        topPurpose: 'Detailed explanation of category use-cases',
        bottomPurpose: 'Culinary or procurement dimension'
    },
    {
        id: 'status',
        label: 'Category Status',
        subLabel: 'Active / Archived',
        default: true,
        lockVisible: false,
        defaultWidth: '130px',
        align: 'center',
        topPurpose: 'Category availability in ERP menus',
        bottomPurpose: 'Status switch toggle'
    },
    {
        id: 'audit_trail',
        label: 'Audit Trail',
        subLabel: 'Updated At / UUID',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topPurpose: 'Timestamp of last modification',
        bottomPurpose: 'Category unique identifier'
    }
];

// Active State
let searchQuery = '';
let currentPage = 1;
let pageSize = 10;
let visibleColumnIds = [];
let expandedCategoryCodes = new Set(['COUNT']); // Default COUNT expanded for rich first impression

// Initialize LocalStorage Column Configuration
function initColumnConfig() {
    const saved = localStorage.getItem('rms_uom_category_cols_v2');
    if (saved) {
        try {
            visibleColumnIds = JSON.parse(saved);
        } catch(e) {
            visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
        }
    } else {
        visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
    }
    // Guarantee locked start column is first
    if (!visibleColumnIds.includes('category_info')) {
        visibleColumnIds.unshift('category_info');
    }
}

function saveColumnConfig() {
    localStorage.setItem('rms_uom_category_cols_v2', JSON.stringify(visibleColumnIds));
    updateSettingsGearCounter();
    renderTableHeader();
    renderCategoryDirectory();
}

function resetColumnDefaults() {
    visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.default).map(c => c.id);
    saveColumnConfig();
    populateSettingsSwitchList();
    showToast("Columns reset to default view.", "success");
}

function toggleAllColumns(selectAll) {
    if (selectAll) {
        visibleColumnIds = CATEGORY_COLUMNS.map(c => c.id);
    } else {
        visibleColumnIds = CATEGORY_COLUMNS.filter(c => c.lockVisible).map(c => c.id);
    }
    saveColumnConfig();
    populateSettingsSwitchList();
}

function updateSettingsGearCounter() {
    const counterEl = document.getElementById('uomColActiveCounter');
    if (counterEl) {
        // Count includes actions column
        counterEl.textContent = `${visibleColumnIds.length + 1} of ${CATEGORY_COLUMNS.length + 1} visible`;
    }
}

// --------------------------------------------------------------------------
// RENDER TABLE HEADERS (MODULAR STACKED FORMAT & SETTINGS GEAR)
// --------------------------------------------------------------------------
function renderTableHeader() {
    const tr = document.getElementById('uomTheadRow');
    if (!tr) return;
    tr.innerHTML = '';

    CATEGORY_COLUMNS.forEach(col => {
        if (!visibleColumnIds.includes(col.id)) return;

        const th = document.createElement('th');
        th.className = 'uom-th-stacked';
        th.style.width = col.defaultWidth || 'auto';
        th.style.textAlign = col.align || 'left';
        th.title = `${col.topPurpose} — ${col.bottomPurpose}`;

        th.innerHTML = `
            <div class="uom-th-content" style="align-items: ${col.align === 'center' ? 'center' : (col.align === 'right' ? 'flex-end' : 'flex-start')};">
                <span class="uom-th-title">${col.label}</span>
                <span class="uom-th-sub">${col.subLabel}</span>
            </div>
        `;
        tr.appendChild(th);
    });

    // End Column: Actions with Settings Gear (PERMANENTLY LOCKED)
    const actionTh = document.createElement('th');
    actionTh.className = 'uom-th-stacked';
    actionTh.style.width = '110px';
    actionTh.style.textAlign = 'center';
    actionTh.innerHTML = `
        <div class="uom-header-action-wrapper" style="justify-content: center;">
            <div class="uom-th-content" style="align-items: center;">
                <span class="uom-th-title">Actions</span>
                <span class="uom-th-sub">Manage</span>
            </div>
            <button type="button" class="uom-th-settings-btn" id="uomSettingsGearBtn" title="Customize Visible Table Columns" onclick="toggleSettingsDropdown(event)">
                <i class="ph ph-gear-six"></i>
            </button>
        </div>
    `;
    tr.appendChild(actionTh);
}

// --------------------------------------------------------------------------
// SETTINGS GEAR DROPDOWN (SWITCHABLE LIST)
// --------------------------------------------------------------------------
function toggleSettingsDropdown(event) {
    if (event) {
        event.stopPropagation();
        event.preventDefault();
    }
    const dropdown = document.getElementById('uomTableSettingsDropdown');
    const gearBtn = document.getElementById('uomSettingsGearBtn');
    if (!dropdown || !gearBtn) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        gearBtn.classList.remove('is-active');
    } else {
        const rect = gearBtn.getBoundingClientRect();
        dropdown.style.top = `${rect.bottom + 6}px`;
        dropdown.style.left = `${Math.max(10, rect.right - 350)}px`;
        dropdown.classList.add('is-active');
        gearBtn.classList.add('is-active');
        populateSettingsSwitchList();
    }
}

document.addEventListener('click', function(e) {
    const dropdown = document.getElementById('uomTableSettingsDropdown');
    const gearBtn = document.getElementById('uomSettingsGearBtn');
    if (dropdown && dropdown.classList.contains('is-active')) {
        if (!dropdown.contains(e.target) && (!gearBtn || !gearBtn.contains(e.target))) {
            dropdown.classList.remove('is-active');
            if (gearBtn) gearBtn.classList.remove('is-active');
        }
    }
});

function populateSettingsSwitchList(filterText = '') {
    const list = document.getElementById('uomSettingsCheckboxesList');
    if (!list) return;
    list.innerHTML = '';

    const filter = (filterText || '').toLowerCase().trim();

    CATEGORY_COLUMNS.forEach(col => {
        if (filter && !col.label.toLowerCase().includes(filter) && !col.subLabel.toLowerCase().includes(filter)) {
            return;
        }

        const isChecked = visibleColumnIds.includes(col.id);
        const item = document.createElement('div');
        item.className = 'uom-settings-col-item';

        item.innerHTML = `
            <div class="uom-col-item-info">
                <span class="uom-col-item-title">${col.label}</span>
                <span class="uom-col-item-subtitle">${col.subLabel}</span>
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                ${col.lockVisible 
                    ? '<span style="font-size: 0.67rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Locked</span>' 
                    : `
                        <label class="uom-switch" title="Toggle column visibility">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} onchange="handleColumnSwitch('${col.id}', this.checked)">
                            <span class="uom-slider"></span>
                        </label>
                    `}
            </div>
        `;
        list.appendChild(item);
    });

    // Actions Header Item (Locked End Column)
    if (!filter || 'actions'.includes(filter)) {
        const actionItem = document.createElement('div');
        actionItem.className = 'uom-settings-col-item';
        actionItem.innerHTML = `
            <div class="uom-col-item-info">
                <span class="uom-col-item-title">Actions Column</span>
                <span class="uom-col-item-subtitle">Manage & Settings Gear</span>
            </div>
            <span style="font-size: 0.67rem; color: #94a3b8; font-weight: 700; text-transform: uppercase;">Locked</span>
        `;
        list.appendChild(actionItem);
    }

    updateSettingsGearCounter();
}

function filterColumnSettingsList(val) {
    populateSettingsSwitchList(val);
}

function handleColumnSwitch(colId, checked) {
    if (checked) {
        if (!visibleColumnIds.includes(colId)) visibleColumnIds.push(colId);
    } else {
        visibleColumnIds = visibleColumnIds.filter(id => id !== colId);
    }
    saveColumnConfig();
}

// --------------------------------------------------------------------------
// EXPANSION CONTROLLER
// --------------------------------------------------------------------------
function toggleCategoryExpansion(catCode, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    if (expandedCategoryCodes.has(catCode)) {
        expandedCategoryCodes.delete(catCode);
    } else {
        expandedCategoryCodes.add(catCode);
    }
    renderCategoryDirectory();
}

// --------------------------------------------------------------------------
// RENDER TABLE ROWS (CATEGORY PRIMARY ROWS WITH EXPANDABLE SUBTABLES)
// --------------------------------------------------------------------------
function getFilteredCategories() {
    let list = [...UOM_CATEGORIES];

    if (searchQuery) {
        const q = searchQuery.toLowerCase();
        list = list.filter(cat => {
            const matchesCat = (cat.category_code && cat.category_code.toLowerCase().includes(q)) ||
                               (cat.category_name && cat.category_name.toLowerCase().includes(q)) ||
                               (cat.category_description && cat.category_description.toLowerCase().includes(q));
            // Or if any unit under this category matches
            const matchingUnits = UOM_DATA.filter(u => u.uom_category === cat.category_code && (
                (u.uom_code && u.uom_code.toLowerCase().includes(q)) ||
                (u.uom_name && u.uom_name.toLowerCase().includes(q))
            ));
            return matchesCat || matchingUnits.length > 0;
        });
    }

    return list;
}

function renderCategoryDirectory() {
    const tbody = document.getElementById('uomTbody');
    if (!tbody) return;
    tbody.innerHTML = '';

    const filtered = getFilteredCategories();
    const total = filtered.length;

    // Pagination slice
    const startIdx = (currentPage - 1) * pageSize;
    const endIdx = Math.min(startIdx + pageSize, total);
    const paginated = filtered.slice(startIdx, endIdx);

    const visibleColsCount = visibleColumnIds.length + 1; // +1 for Actions

    if (paginated.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleColsCount}" style="text-align: center; padding: 48px 16px; color: var(--uom-text-muted);">
                    <i class="ph ph-folder" style="font-size: 34px; opacity: 0.35; display: block; margin-bottom: 8px;"></i>
                    <strong style="color: var(--uom-text-strong);">No measurement categories match your query.</strong>
                    <div style="font-size: 0.78rem; margin-top: 4px;">Click "New Category" above to create one.</div>
                </td>
            </tr>
        `;
        renderDirectoryPagination(0, 0, 0);
        return;
    }

    paginated.forEach(cat => {
        const isExpanded = expandedCategoryCodes.has(cat.category_code);
        const units = UOM_DATA.filter(u => u.uom_category === cat.category_code);
        const activeUnitsCount = units.filter(u => u.uom_is_active).length;
        const baseUnit = units.find(u => u.is_base_unit) || units[0] || null;
        const catBadgeClass = getCategoryBadgeClass(cat.category_code);

        // Render Master Category Row
        const tr = document.createElement('tr');
        let rowCells = '';

        visibleColumnIds.forEach(colId => {
            switch(colId) {
                case 'category_info':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div style="display: flex; align-items: center;">
                                <button type="button" class="uom-row-expand-btn ${isExpanded ? 'is-expanded' : ''}" 
                                    onclick="toggleCategoryExpansion('${escapeHtml(cat.category_code)}', event)" 
                                    title="${isExpanded ? 'Collapse measurements' : 'Expand ' + units.length + ' measurements'}">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                                <div>
                                    <div class="uom-cell-top" style="display: flex; align-items: center; gap: 6px;">
                                        <span class="uom-badge ${catBadgeClass}" style="${!cat.category_is_active ? 'opacity: 0.6; filter: grayscale(0.6);' : ''}">${escapeHtml(cat.category_code)}</span>
                                        <span style="font-size: 0.8rem; font-weight: 700; ${!cat.category_is_active ? 'color: var(--uom-text-muted);' : ''}">${escapeHtml(cat.category_name)}</span>
                                        ${!cat.category_is_active ? '<span class="uom-badge" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.62rem; padding: 1px 5px;"><i class="ph ph-archive"></i> ARCHIVED</span>' : ''}
                                    </div>
                                    <div class="uom-cell-bottom" style="margin-left: 2px;">
                                        ${escapeHtml(cat.category_description || 'Standard Measurement Dimension')}
                                    </div>
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'base_uom':
                    rowCells += `
                        <td class="uom-td-stacked">
                            ${baseUnit ? `
                                <div class="uom-cell-top" style="display: flex; align-items: center; gap: 5px;">
                                    <span style="font-family: monospace; font-weight: 800; color: var(--uom-text-strong);">${escapeHtml(baseUnit.uom_code)}</span>
                                    <span class="uom-badge uom-badge-base"><i class="ph ph-star-fill"></i> BASE</span>
                                </div>
                                <div class="uom-cell-bottom">${escapeHtml(baseUnit.uom_name)}</div>
                            ` : `<span style="color: var(--uom-text-subtle);">None Configured</span>`}
                        </td>
                    `;
                    break;

                case 'units_count':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="display: flex; align-items: center; gap: 6px;">
                                <span style="font-weight: 800; font-size: 0.86rem; color: var(--uom-primary-dark);">${units.length} Units</span>
                            </div>
                            <div class="uom-cell-bottom">
                                <span class="uom-badge uom-badge-active" style="padding: 1px 6px; font-size: 0.66rem;">${activeUnitsCount} Active</span>
                            </div>
                        </td>
                    `;
                    break;

                case 'category_desc':
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="font-weight: 500; font-size: 0.77rem; color: var(--uom-text-medium);">
                                ${escapeHtml(cat.category_description || 'No special operational constraints.')}
                            </div>
                            <div class="uom-cell-bottom" style="font-size: 0.69rem;">Standard Ratio Calculation Basis</div>
                        </td>
                    `;
                    break;

                case 'status':
                    rowCells += `
                        <td class="uom-td-stacked" style="text-align: center;">
                            <label class="uom-switch" title="Toggle category status (Deactivating cascades to all units)">
                                <input type="checkbox" ${cat.category_is_active ? 'checked' : ''} onchange="toggleCategoryActive('${escapeHtml(cat.category_id || cat.category_code)}')">
                                <span class="uom-slider"></span>
                            </label>
                        </td>
                    `;
                    break;

                case 'audit_trail':
                    const updatedStr = cat.category_updated_at ? new Date(cat.category_updated_at).toLocaleDateString() : 'System';
                    rowCells += `
                        <td class="uom-td-stacked">
                            <div class="uom-cell-top" style="font-size: 0.74rem;">${updatedStr}</div>
                            <div class="uom-cell-bottom" style="font-family: monospace; font-size: 0.65rem;" title="${cat.category_id}">ID: ${cat.category_id ? cat.category_id.substring(0, 8) : 'seed'}...</div>
                        </td>
                    `;
                    break;
            }
        });

        // Actions Column
        rowCells += `
            <td class="uom-td-stacked" style="text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <button type="button" class="uom-btn uom-btn-primary" style="padding: 4px 8px; font-size: 11px;" onclick="openNewUomDrawerForCategory('${escapeHtml(cat.category_code)}')" title="Add Measurement to ${cat.category_code}">
                        <i class="ph ph-plus"></i> <span>Add Unit</span>
                    </button>
                </div>
            </td>
        `;

        tr.innerHTML = rowCells;
        tbody.appendChild(tr);

        // Render Multi-Line Accordion Subtable (Expanded Measurements)
        if (isExpanded) {
            const expTr = document.createElement('tr');
            expTr.className = 'uom-line-expansion-row';
            expTr.innerHTML = `
                <td colspan="${visibleColsCount}" style="padding: 0;">
                    <div class="uom-subtable-container">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                            <div style="font-weight: 700; font-size: 0.80rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                <i class="ph ph-scales" style="color: var(--uom-primary);"></i>
                                <span>Measurement Units in <strong>${escapeHtml(cat.category_code)}</strong> (${units.length} registered)</span>
                            </div>
                            <button type="button" class="uom-btn uom-btn-secondary" style="padding: 3px 8px; font-size: 11px;" onclick="openNewUomDrawerForCategory('${escapeHtml(cat.category_code)}')">
                                <i class="ph ph-plus-circle"></i> <span>Add Unit to ${escapeHtml(cat.category_code)}</span>
                            </button>
                        </div>

                        ${units.length === 0 ? `
                            <div style="padding: 16px; text-align: center; color: var(--uom-text-muted); font-size: 0.77rem; background: #ffffff; border-radius: 8px; border: 1px dashed #e2e8f0;">
                                No units configured yet for ${escapeHtml(cat.category_code)}. Click "+ Add Unit" to define the reference unit.
                            </div>
                        ` : `
                            <table class="uom-subtable">
                                <thead>
                                    <tr>
                                        <th style="width: 120px;">UoM Code</th>
                                        <th>Descriptive Name</th>
                                        <th style="width: 130px;">Classification</th>
                                        <th style="width: 220px;">Quantity / Conversion Formula</th>
                                        <th style="width: 110px; text-align: right;">Multiplier</th>
                                        <th style="width: 90px; text-align: center;">Precision</th>
                                        <th style="width: 90px; text-align: center;">Dropdown</th>
                                        <th style="width: 80px; text-align: center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${units.map((uom, idx) => {
                                        const refCode = cat.base_uom_code || (baseUnit ? baseUnit.uom_code : 'Base');
                                        const formula = uom.is_base_unit 
                                            ? `1 ${uom.uom_code} (Category Reference)` 
                                            : `1 ${uom.uom_code} = ${formatNumber(uom.base_ratio)} ${refCode}`;

                                        return `
                                            <tr style="${!uom.uom_is_active ? 'opacity: 0.62; background: #fafafa;' : ''}">
                                                <td style="font-family: monospace; font-weight: 800; color: var(--uom-text-strong);">
                                                    <div style="display: flex; align-items: center; gap: 5px;">
                                                        <span style="${!uom.uom_is_active ? 'text-decoration: line-through; color: #94a3b8;' : ''}">${escapeHtml(uom.uom_code)}</span>
                                                        ${uom.is_base_unit ? '<span class="uom-badge uom-badge-base"><i class="ph ph-star-fill"></i> BASE</span>' : ''}
                                                        ${!uom.uom_is_active ? '<span class="uom-badge" style="background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; font-size: 0.60rem; padding: 0 4px;">ARCHIVED</span>' : ''}
                                                    </div>
                                                </td>
                                                <td style="font-weight: 600; color: var(--uom-text-strong);">${escapeHtml(uom.uom_name)}</td>
                                                <td><span style="font-size: 0.72rem; color: #64748b;">${escapeHtml(uom.uom_type || 'STANDARD')}</span></td>
                                                <td style="color: var(--uom-primary-dark); font-weight: 700;">${formula}</td>
                                                <td style="text-align: right; font-family: monospace; font-weight: 700; color: #0f172a;">${formatMultiplier(uom.base_ratio)}x</td>
                                                <td style="text-align: center; font-size: 0.72rem; color: #64748b;">${uom.rounding_precision || '0.0100'}</td>
                                                <td style="text-align: center;">
                                                    <label class="uom-switch" title="Toggle active status">
                                                        <input type="checkbox" ${uom.uom_is_active ? 'checked' : ''} onchange="toggleUomActive('${uom.uom_id}')">
                                                        <span class="uom-slider"></span>
                                                    </label>
                                                </td>
                                                <td style="text-align: center;">
                                                    <button type="button" class="uom-btn uom-btn-secondary" style="padding: 2px 6px; font-size: 11px;" onclick="openEditUomDrawer('${uom.uom_id}')" title="Edit Unit Properties">
                                                        <i class="ph ph-pencil-simple"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                            </table>
                        `}
                    </div>
                </td>
            `;
            tbody.appendChild(expTr);
        }
    });

    renderDirectoryPagination(startIdx + 1, endIdx, total);
}

function getCategoryBadgeClass(catCode) {
    switch(catCode) {
        case 'COUNT': return 'uom-badge-cat-count';
        case 'WEIGHT': return 'uom-badge-cat-weight';
        case 'VOLUME': return 'uom-badge-cat-volume';
        case 'PORTION': return 'uom-badge-cat-portion';
        case 'LENGTH': return 'uom-badge-cat-length';
        case 'AREA': return 'uom-badge-cat-area';
        case 'TIME': return 'uom-badge-cat-time';
        case 'OVERHEAD': return 'uom-badge-cat-overhead';
        default: return 'uom-badge-cat-custom';
    }
}

function formatNumber(num) {
    const n = Number(num);
    if (isNaN(n)) return '0';
    if (n % 1 === 0) return String(n);
    if (n < 0.001) return n.toFixed(6).replace(/\.?0+$/, '');
    return n.toFixed(4).replace(/\.?0+$/, '');
}

function formatMultiplier(num) {
    const n = Number(num);
    if (isNaN(n)) return '1';
    if (n % 1 === 0) return String(n);
    if (n < 0.001) return n.toFixed(6).replace(/\.?0+$/, '');
    return n.toFixed(4).replace(/\.?0+$/, '');
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// --------------------------------------------------------------------------
// FOOTER PAGINATION
// --------------------------------------------------------------------------
function renderDirectoryPagination(start, end, total) {
    const info = document.getElementById('uomPaginationInfo');
    const container = document.getElementById('uomPaginationButtons');
    if (!info || !container) return;

    if (total === 0) {
        info.innerHTML = 'Showing <strong>0</strong> to <strong>0</strong> of <strong>0</strong> categories';
        container.innerHTML = '';
        return;
    }

    info.innerHTML = `Showing <strong>${start}</strong> to <strong>${end}</strong> of <strong>${total}</strong> categories`;

    const totalPages = Math.ceil(total / pageSize);
    let buttonsHtml = '';

    buttonsHtml += `<button type="button" class="uom-page-btn" ${currentPage === 1 ? 'disabled' : ''} onclick="goToPage(${currentPage - 1})"><i class="ph ph-caret-left"></i></button>`;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= currentPage - 1 && p <= currentPage + 1)) {
            buttonsHtml += `<button type="button" class="uom-page-btn ${p === currentPage ? 'is-active' : ''}" onclick="goToPage(${p})">${p}</button>`;
        } else if (p === currentPage - 2 || p === currentPage + 2) {
            buttonsHtml += `<span style="padding: 0 4px; color: #94a3b8;">...</span>`;
        }
    }

    buttonsHtml += `<button type="button" class="uom-page-btn" ${currentPage === totalPages ? 'disabled' : ''} onclick="goToPage(${currentPage + 1})"><i class="ph ph-caret-right"></i></button>`;

    container.innerHTML = buttonsHtml;
}

function goToPage(p) {
    currentPage = p;
    renderCategoryDirectory();
}

function changePageSize(sz) {
    pageSize = parseInt(sz) || 10;
    currentPage = 1;
    renderCategoryDirectory();
}

function handleUomSearch(q) {
    searchQuery = q.trim();
    currentPage = 1;
    renderCategoryDirectory();
}

// --------------------------------------------------------------------------
// SLIDE-OUT DRAWER: ADD / EDIT UOM
// --------------------------------------------------------------------------
function openNewUomDrawer() {
    openNewUomDrawerForCategory('');
}

function openNewUomDrawerForCategory(catCode) {
    document.getElementById('uomDrawerTitle').textContent = 'Add Unit of Measurement';
    document.getElementById('formUomId').value = '';
    document.getElementById('formUomCode').value = '';
    document.getElementById('formUomCode').readOnly = false;
    document.getElementById('formUomName').value = '';
    document.getElementById('formIsBase').checked = false;
    document.getElementById('formBaseRatio').value = '1.0';
    document.getElementById('formPrecision').value = '0.0100';
    document.getElementById('formIsActive').checked = true;
    document.getElementById('uomSubmitBtnText').textContent = 'Save Measurement';

    if (catCode) {
        document.getElementById('formUomCategory').value = catCode;
    }

    handleIsBaseToggle(false);
    updateDrawerFormulaPreview();

    document.getElementById('uomDrawerOverlay').classList.add('is-open');
    document.getElementById('uomDrawer').classList.add('is-open');
    setTimeout(() => document.getElementById('formUomCode').focus(), 150);
}

function openEditUomDrawer(id) {
    const uom = UOM_DATA.find(u => u.uom_id === id);
    if (!uom) return;

    document.getElementById('uomDrawerTitle').textContent = `Edit Measurement: ${uom.uom_code}`;
    document.getElementById('formUomId').value = uom.uom_id;
    document.getElementById('formUomCode').value = uom.uom_code;
    document.getElementById('formUomCode').readOnly = false;
    document.getElementById('formUomCategory').value = uom.uom_category;
    document.getElementById('formUomName').value = uom.uom_name;
    document.getElementById('formIsBase').checked = !!uom.is_base_unit;
    document.getElementById('formBaseRatio').value = uom.base_ratio;
    document.getElementById('formPrecision').value = uom.rounding_precision || '0.0100';
    document.getElementById('formIsActive').checked = !!uom.uom_is_active;
    document.getElementById('uomSubmitBtnText').textContent = 'Update Measurement';

    handleIsBaseToggle(!!uom.is_base_unit);
    updateDrawerFormulaPreview();

    document.getElementById('uomDrawerOverlay').classList.add('is-open');
    document.getElementById('uomDrawer').classList.add('is-open');
}

function closeUomDrawer() {
    document.getElementById('uomDrawerOverlay').classList.remove('is-open');
    document.getElementById('uomDrawer').classList.remove('is-open');
}

function handleIsBaseToggle(isBase) {
    const ratioInput = document.getElementById('formBaseRatio');
    if (isBase) {
        ratioInput.value = '1.0';
        ratioInput.readOnly = true;
    } else {
        ratioInput.readOnly = false;
    }
    updateDrawerFormulaPreview();
}

function handleDrawerCategoryChange() {
    updateDrawerFormulaPreview();
}

function updateDrawerFormulaPreview() {
    const code = (document.getElementById('formUomCode')?.value || 'UNIT').toUpperCase().trim();
    const catCode = document.getElementById('formUomCategory')?.value || '';
    const ratio = parseFloat(document.getElementById('formBaseRatio')?.value || 1);
    const isBase = document.getElementById('formIsBase')?.checked;

    const catObj = UOM_CATEGORIES.find(c => c.category_code === catCode);
    const baseCode = catObj ? (catObj.base_uom_code || 'Base') : 'Base';

    const textEl = document.getElementById('uomFormulaPreviewText');
    if (!textEl) return;

    if (isBase) {
        textEl.textContent = `1 ${code} = Reference (Base Unit for ${catCode})`;
    } else {
        textEl.textContent = `1 ${code} = ${ratio} ${baseCode}`;
    }
}

async function handleSaveUom(e) {
    e.preventDefault();
    const btn = document.getElementById('uomSubmitBtn');
    btn.disabled = true;

    const id = document.getElementById('formUomId').value;
    const catVal = document.getElementById('formUomCategory').value;
    const payload = {
        uom_code: document.getElementById('formUomCode').value.toUpperCase().trim(),
        uom_name: document.getElementById('formUomName').value.trim(),
        uom_category: catVal,
        base_ratio: parseFloat(document.getElementById('formBaseRatio').value) || 1.0,
        is_base_unit: document.getElementById('formIsBase').checked,
        rounding_precision: parseFloat(document.getElementById('formPrecision').value) || 0.0100,
        uom_is_active: document.getElementById('formIsActive').checked,
        _token: '{{ csrf_token() }}'
    };

    const isEdit = !!id;
    const url = isEdit ? `{{ url('/inventory/api/uoms') }}/${id}` : `{{ route('inventory.api.uoms.store') }}`;
    const method = isEdit ? 'PUT' : 'POST';

    try {
        const resp = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();
        if (data.success) {
            showToast(data.message || 'Saved successfully!', 'success');
            closeUomDrawer();
            expandedCategoryCodes.add(catVal); // Auto-expand category where item was added
            await reloadDataFromServer();
        } else {
            showToast(data.message || 'Failed to save UoM.', 'danger');
        }
    } catch(err) {
        showToast('Network error while saving UoM.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

async function toggleUomActive(id) {
    const uom = UOM_DATA.find(u => u.uom_id === id);
    if (!uom) return;

    // 1. Optimistic UI update: flip in-memory status immediately
    const prevStatus = uom.uom_is_active;
    uom.uom_is_active = !prevStatus;

    // Immediately update KPI counters
    const kpiActive = document.getElementById('kpiActiveUnits');
    if (kpiActive) kpiActive.textContent = UOM_DATA.filter(u => u.uom_is_active).length;

    // Immediately re-render without requiring page reload
    renderCategoryDirectory();

    try {
        const resp = await fetch(`{{ url('/inventory/api/uoms') }}/${id}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await resp.json();
        if (data.success) {
            uom.uom_is_active = data.is_active;
            showToast(data.message, 'success');
            // Background sync with ?all=1 to ensure persisted state
            await reloadDataFromServer();
        } else {
            // Rollback optimistic update
            uom.uom_is_active = prevStatus;
            renderCategoryDirectory();
            showToast(data.message || 'Failed to update status.', 'danger');
        }
    } catch(err) {
        // Rollback optimistic update
        uom.uom_is_active = prevStatus;
        renderCategoryDirectory();
        showToast('Network error while toggling status.', 'danger');
    }
}

async function toggleCategoryActive(idOrCode) {
    const cat = UOM_CATEGORIES.find(c => c.category_id === idOrCode || c.category_code === idOrCode);
    if (!cat) return;

    // 1. Optimistic UI update: flip in-memory category state immediately
    const prevCatStatus = cat.category_is_active;
    const newCatStatus = !prevCatStatus;
    cat.category_is_active = newCatStatus;

    // Snapshot child unit statuses for possible rollback
    const childUnits = UOM_DATA.filter(u => u.uom_category === cat.category_code);
    const prevUnitStatuses = new Map();
    childUnits.forEach(u => {
        prevUnitStatuses.set(u.uom_id, u.uom_is_active);
        if (!newCatStatus) {
            // When turning off category, deactivate all child UOMs under it!
            u.uom_is_active = false;
        }
    });

    // Immediately update KPI counters
    const kpiActive = document.getElementById('kpiActiveUnits');
    if (kpiActive) kpiActive.textContent = UOM_DATA.filter(u => u.uom_is_active).length;

    // Immediately re-render table and open subtables without page reload
    renderCategoryDirectory();

    try {
        const targetId = cat.category_id || cat.category_code;
        const resp = await fetch(`{{ url('/inventory/api/uom-categories') }}/${targetId}/toggle-active`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });
        const data = await resp.json();
        if (data.success) {
            cat.category_is_active = data.is_active;
            showToast(data.message, 'success');
            // Background sync with ?all=1
            await reloadDataFromServer();
        } else {
            // Rollback optimistic state
            cat.category_is_active = prevCatStatus;
            childUnits.forEach(u => {
                if (prevUnitStatuses.has(u.uom_id)) {
                    u.uom_is_active = prevUnitStatuses.get(u.uom_id);
                }
            });
            renderCategoryDirectory();
            showToast(data.message || 'Failed to update category status.', 'danger');
        }
    } catch(err) {
        // Rollback optimistic state
        cat.category_is_active = prevCatStatus;
        childUnits.forEach(u => {
            if (prevUnitStatuses.has(u.uom_id)) {
                u.uom_is_active = prevUnitStatuses.get(u.uom_id);
            }
        });
        renderCategoryDirectory();
        showToast('Network error while toggling category status.', 'danger');
    }
}

// --------------------------------------------------------------------------
// MODAL: CREATE NEW CATEGORY
// --------------------------------------------------------------------------
function openNewCategoryModal() {
    document.getElementById('catCode').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catBase').value = '';
    document.getElementById('catDesc').value = '';
    document.getElementById('uomCategoryModalOverlay').classList.add('is-open');
    document.getElementById('uomCategoryModal').classList.add('is-open');
    setTimeout(() => document.getElementById('catCode').focus(), 150);
}

function closeCategoryModal() {
    document.getElementById('uomCategoryModalOverlay').classList.remove('is-open');
    document.getElementById('uomCategoryModal').classList.remove('is-open');
}

async function handleSaveCategory(e) {
    e.preventDefault();
    const btn = document.getElementById('catSubmitBtn');
    btn.disabled = true;

    const payload = {
        category_code: document.getElementById('catCode').value.toUpperCase().trim(),
        category_name: document.getElementById('catName').value.trim(),
        base_uom_code: document.getElementById('catBase').value.toUpperCase().trim(),
        category_description: document.getElementById('catDesc').value.trim(),
        _token: '{{ csrf_token() }}'
    };

    try {
        const resp = await fetch(`{{ route('inventory.api.uom-categories.store') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload)
        });

        const data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            closeCategoryModal();
            expandedCategoryCodes.add(payload.category_code); // Auto-expand new category
            await reloadDataFromServer();

            // Select newly created category in drawer if open
            const formCatSelect = document.getElementById('formUomCategory');
            if (formCatSelect) {
                formCatSelect.value = payload.category_code;
                updateDrawerFormulaPreview();
            }
        } else {
            showToast(data.message || 'Failed to create category.', 'danger');
        }
    } catch(err) {
        showToast('Network error while creating category.', 'danger');
    } finally {
        btn.disabled = false;
    }
}

// --------------------------------------------------------------------------
// REFRESH DATA FROM BACKEND API
// --------------------------------------------------------------------------
async function reloadDataFromServer() {
    try {
        const resp = await fetch(`{{ route('inventory.api.uoms') }}?all=1`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await resp.json();
        if (data.success) {
            UOM_DATA = data.units;
            UOM_CATEGORIES = data.categories;

            // Update KPI counters
            document.getElementById('kpiTotalUnits').textContent = UOM_DATA.length;
            document.getElementById('kpiActiveUnits').textContent = UOM_DATA.filter(u => u.uom_is_active).length;
            document.getElementById('kpiTotalCategories').textContent = UOM_CATEGORIES.length;
            document.getElementById('kpiBaseUnits').textContent = UOM_DATA.filter(u => u.is_base_unit).length;

            // Update Category Select in Drawer
            const formCat = document.getElementById('formUomCategory');
            if (formCat) {
                const curVal = formCat.value;
                formCat.innerHTML = UOM_CATEGORIES.map(c => 
                    `<option value="${c.category_code}" data-base="${c.base_uom_code || ''}">${c.category_code} (${c.category_name})</option>`
                ).join('');
                if (curVal) formCat.value = curVal;
            }

            // Invalidate UomRegistry cache and storage so all app dropdowns immediately reflect the active-only state
            if (window.UomRegistry) {
                window.UomRegistry.invalidateCache();
                window.UomRegistry.load(true);
            }
            try {
                sessionStorage.removeItem('rms_uom_registry_cache_v3');
                sessionStorage.removeItem('rms_uom_registry_cache_v2');
                sessionStorage.removeItem('rms_uom_registry_cache_v1');
            } catch(e) {}
            window.dispatchEvent(new CustomEvent('rms:uom-registry-invalidated'));

            renderCategoryDirectory();
        }
    } catch(e) {
        console.error('Error reloading UOM data', e);
    }
}

// --------------------------------------------------------------------------
// TOAST NOTIFICATIONS
// --------------------------------------------------------------------------
let toastTimeout;
function showToast(msg, type = 'success') {
    const toast = document.getElementById('uomToast');
    const text = document.getElementById('uomToastText');
    const icon = document.getElementById('uomToastIcon');
    if (!toast) return;

    clearTimeout(toastTimeout);
    toast.className = `uom-toast ${type} show`;
    text.textContent = msg;
    icon.className = type === 'success' ? 'ph ph-check-circle' : 'ph ph-warning-circle';

    toastTimeout = setTimeout(() => {
        toast.classList.remove('show');
    }, 3200);
}

// Initial Boot
document.addEventListener('DOMContentLoaded', function() {
    initColumnConfig();
    renderTableHeader();
    renderCategoryDirectory();
});
</script>
@endpush

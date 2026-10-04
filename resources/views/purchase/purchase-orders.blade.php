@extends('layouts.app')

@section('title', 'Purchase Orders (PO) - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   PURCHASE ORDER (PO) ENTERPRISE WORKSPACE TOKENS
   RMS Industrial Procurement & Glassmorphic Financial Ergonomics
   ========================================================================== */
:root {
    --po-primary: #0284c7;
    --po-primary-dark: #0369a1;
    --po-primary-light: #38bdf8;
    --po-primary-glow: rgba(2, 132, 199, 0.22);
    --po-primary-subtle: rgba(2, 132, 199, 0.08);
    --po-primary-gradient: linear-gradient(135deg, #0284c7 0%, #0d9488 100%);

    --po-teal: #0d9488;
    --po-teal-dark: #0f766e;
    --po-teal-subtle: rgba(13, 148, 136, 0.12);

    --po-success: #10b981;
    --po-success-dark: #059669;
    --po-success-subtle: rgba(16, 185, 129, 0.12);

    --po-warning: #f59e0b;
    --po-warning-subtle: rgba(245, 158, 11, 0.12);

    --po-danger: #ef4444;
    --po-danger-subtle: rgba(239, 68, 68, 0.12);

    --po-purple: #8b5cf6;
    --po-purple-subtle: rgba(139, 92, 246, 0.12);

    --po-surface: #ffffff;
    --po-surface-card: rgba(255, 255, 255, 0.96);
    --po-surface-subtle: #f8fafc;
    --po-border-subtle: #e2e8f0;
    --po-border-focus: #38bdf8;

    --po-text-strong: #0f172a;
    --po-text-medium: #334155;
    --po-text-muted: #64748b;
    --po-text-subtle: #94a3b8;

    --po-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05);
    --po-shadow-md: 0 4px 16px -2px rgba(15, 23, 42, 0.06);
    --po-shadow-lg: 0 14px 34px -4px rgba(15, 23, 42, 0.09);
    --po-shadow-xl: 0 24px 50px -6px rgba(15, 23, 42, 0.16);

    --po-radius-sm: 6px;
    --po-radius-md: 10px;
    --po-radius-lg: 14px;
    --po-radius-xl: 18px;
}

/* Page Container */
.po-workspace-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
    padding-bottom: 50px;
}

/* Nav Tabs Bar (HR Standard) */
.po-nav-tabs-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 12px;
    padding: 5px 8px;
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    flex-wrap: wrap;
    margin-bottom: 2px;
}
.po-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 9px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    background: transparent;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    white-space: nowrap;
    position: relative;
    line-height: 1.2;
}
.po-tab-btn i {
    font-size: 16px;
    color: #94a3b8;
    transition: color 0.2s ease, transform 0.2s ease;
}
.po-tab-btn:hover:not(.active) {
    color: #9333ea;
    background: rgba(168, 85, 247, 0.08);
}
.po-tab-btn:hover:not(.active) i {
    color: #9333ea;
    transform: scale(1.1);
}
.po-tab-btn.active {
    background: #ffffff;
    color: #9333ea;
    border-color: rgba(168, 85, 247, 0.28);
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.14), 0 1px 3px rgba(0, 0, 0, 0.04);
}
.po-tab-btn.active i {
    background: linear-gradient(135deg, #ec4899, #a855f7);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
}
.po-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 16%;
    right: 16%;
    height: 3px;
    background: linear-gradient(90deg, #ec4899, #a855f7);
    border-radius: 3px 3px 0 0;
}
.po-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 11px;
    font-weight: 700;
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
    transition: all 0.2s ease;
}
.po-tab-btn.active .po-tab-count {
    background: rgba(168, 85, 247, 0.18);
    color: #7c3aed;
}
.po-tab-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
}
.po-tab-btn.active .po-tab-badge {
    background: rgba(168, 85, 247, 0.18);
    color: #7c3aed;
}
.po-tab-pane {
    display: flex;
    flex-direction: column;
    gap: 14px;
    width: 100%;
}
#pane-tab-po-builder.po-tab-pane {
    gap: 4px;
}

/* KPI Cluster Grid (Compacted HR Metric Cards) */
.po-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}
.po-kpi-card {
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 8px 14px;
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.06), 0 1px 3px rgba(0, 0, 0, 0.02), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    gap: 4px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}
.po-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(168, 85, 247, 0.12), 0 2px 6px rgba(0, 0, 0, 0.04);
    border-color: rgba(168, 85, 247, 0.4);
}
.po-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.po-kpi-label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--po-text-muted);
}
.po-kpi-icon {
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
}
.po-kpi-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.1;
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
}
.po-kpi-sub {
    font-size: 0.72rem;
    color: var(--po-text-muted);
}

/* PO Originating RFQ Badge */
.po-rfq-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 8px;
    border-radius: 6px;
    font-family: monospace;
    font-size: 11px;
    font-weight: 700;
    color: #9333ea;
    background: rgba(168, 85, 247, 0.10);
    border: 1px solid rgba(168, 85, 247, 0.25);
    cursor: pointer;
    transition: all 0.15s ease;
    text-decoration: none;
}
.po-rfq-pill:hover {
    background: rgba(168, 85, 247, 0.20);
    border-color: #9333ea;
    transform: translateY(-1px);
}

/* Status Filter Pills Bar - Compact HR Theme */
.po-filter-pills-bar {
    display: flex;
    align-items: center;
    gap: 5px;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.po-filter-pills-bar::-webkit-scrollbar {
    display: none;
}
.po-filter-pill {
    padding: 3.5px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    color: #64748b;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    line-height: 1.2;
}
.po-filter-pill:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.po-filter-pill.active {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.18));
    color: #9333ea;
    border-color: rgba(168, 85, 247, 0.35);
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(168, 85, 247, 0.08);
}

/* Header Column Resizer Handle */
.po-col-resizer {
    position: absolute;
    top: 0;
    right: 0;
    width: 6px;
    bottom: 0;
    cursor: col-resize;
    user-select: none;
    z-index: 10;
}
.po-col-resizer:hover, .po-col-resizer.is-resizing {
    background: var(--po-primary, #9333ea);
}

/* Column Configuration Dropdown */
.po-col-dropdown {
    position: absolute;
    right: 0;
    top: calc(100% + 4px);
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.15), 0 8px 10px -6px rgba(15, 23, 42, 0.08);
    width: 270px;
    z-index: 1050;
    padding: 0;
    overflow: hidden;
}
.po-col-checklist {
    max-height: 280px;
    overflow-y: auto;
    padding: 6px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.po-col-check-item, .inv-col-item-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 5px 8px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.15s ease;
    user-select: none;
}
.po-col-check-item:hover, .inv-col-item-row:hover {
    background: #f8fafc;
}

/* Quick Action Links inside Dropdown */
.inv-col-quick-link {
    background: none;
    border: none;
    font-size: 0.76rem;
    font-weight: 600;
    color: #9333ea;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 5px;
    transition: background 0.12s ease;
}
.inv-col-quick-link:hover {
    background: rgba(168, 85, 247, 0.10);
}
.inv-col-quick-link.is-reset {
    color: #64748b;
}
.inv-col-quick-link.is-reset:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.inv-header-action-wrapper {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    position: relative;
}

/* Industrial Workspace Asymmetric Grid for PO Builder */
.po-builder-workspace-grid {
    display: grid;
    grid-template-columns: minmax(360px, 4.2fr) minmax(480px, 5.8fr);
    gap: 10px;
    align-items: stretch;
}
@media (max-width: 1100px) {
    .po-builder-workspace-grid {
        grid-template-columns: 1fr;
    }
}
.po-builder-left-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.po-builder-right-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
    height: 100%;
    min-width: 0;
}
.po-builder-right-col .po-items-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
}
.po-builder-right-col .po-table-responsive {
    flex: 1 1 auto;
    max-height: calc(100vh - 290px);
    min-height: 260px;
    overflow-y: auto;
}

/* Filter Pills Bar */
.po-filter-pills-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.po-filter-pills-bar::-webkit-scrollbar {
    display: none;
}
.po-filter-pill {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--po-text-muted);
    background: #f8fafc;
    border: 1px solid var(--po-border-subtle);
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}
.po-filter-pill:hover {
    background: #f1f5f9;
    color: var(--po-text-strong);
}
.po-filter-pill.active {
    background: var(--po-primary);
    color: #ffffff;
    border-color: var(--po-primary);
}

/* Status Badges */
.po-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.74rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    white-space: nowrap;
}
.po-status-badge.draft {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #cbd5e1;
}
.po-status-badge.approved {
    background: var(--po-teal-subtle);
    color: var(--po-teal-dark);
    border: 1px solid rgba(13, 148, 136, 0.3);
}
.po-status-badge.received {
    background: #ecfdf5;
    color: #047857;
    border: 1px solid #6ee7b7;
}
.po-status-badge.cancelled {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fca5a5;
}

/* Payment Status Badges */
.pay-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    white-space: nowrap;
}
.pay-badge.paid {
    background: #ecfdf5;
    color: #059669;
    border: 1px solid #a7f3d0;
}
.pay-badge.partial {
    background: #fffbeb;
    color: #b45309;
    border: 1px solid #fde68a;
}
.pay-badge.unpaid {
    background: #fef2f2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

/* PO Type Badge */
.po-type-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 7px;
    border-radius: 5px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.po-type-badge.vendor {
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.po-type-badge.wet-market {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #fde68a;
}

/* Compact PO Command Bar (Zero Dead Whitespace) */
.po-command-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
    background: transparent;
    border: none;
    border-radius: 0;
    padding: 0;
    box-shadow: none;
    margin: 0 0 2px 0;
}
.po-command-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.po-actions-group {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.po-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: var(--po-radius-md);
    font-size: 0.86rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.16s ease;
    border: 1px solid transparent;
    text-decoration: none;
    font-family: inherit;
    line-height: 1.2;
}
.po-btn-outline {
    background: #ffffff;
    border-color: var(--po-border-subtle);
    color: var(--po-text-medium);
}
.po-btn-outline:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--po-text-strong);
}
.po-btn-teal {
    background: var(--po-teal);
    color: #ffffff;
    box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
}
.po-btn-teal:hover {
    background: var(--po-teal-dark);
    transform: translateY(-1px);
}
.po-btn-primary {
    background: var(--po-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.32);
}
.po-btn-primary:hover {
    filter: brightness(1.06);
    transform: translateY(-1px);
}
.po-hotkey-badge {
    background: rgba(255, 255, 255, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.35);
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-left: 2px;
}
.po-btn-outline .po-hotkey-badge {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #64748b;
}

/* PO Type Mode Toggle Pill Switch */
.po-mode-toggle-card {
    background: #f8fafc;
    border: 1.5px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    padding: 12px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
}
.po-mode-pills {
    display: flex;
    background: #e2e8f0;
    padding: 4px;
    border-radius: 12px;
    gap: 4px;
}
.po-mode-btn {
    border: none;
    background: transparent;
    padding: 8px 18px;
    border-radius: 9px;
    font-size: 0.85rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.15s ease;
    display: flex;
    align-items: center;
    gap: 8px;
    font-family: inherit;
}
.po-mode-btn.active {
    background: #ffffff;
    color: var(--po-text-strong);
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
}
.po-mode-btn.active.is-market {
    color: #b45309;
}

/* Top Details Grid */
.po-top-grid {
    display: grid;
    grid-template-columns: 1.25fr 1fr;
    gap: 20px;
}
@media (max-width: 1024px) {
    .po-top-grid {
        grid-template-columns: 1fr;
    }
}

.po-panel-card {
    background: var(--po-surface);
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    box-shadow: var(--po-shadow-sm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.po-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--po-border-subtle);
}
.po-panel-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--po-text-strong);
    letter-spacing: 0.01em;
}
.po-panel-title i {
    font-size: 16px;
    color: var(--po-primary);
}
.po-panel-body {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Space-Saving Form Controls & Merged Inputs */
.po-form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.po-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.po-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--po-text-medium);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.po-label-badge {
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--po-primary-dark);
    background: var(--po-primary-subtle);
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: none;
}
.po-input, .po-select, .po-textarea {
    width: 100%;
    padding: 6px 10px;
    border: 1.5px solid var(--po-border-subtle);
    border-radius: var(--po-radius-md);
    font-size: 0.82rem;
    color: var(--po-text-strong);
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.po-input:focus, .po-select:focus, .po-textarea:focus {
    outline: none;
    border-color: var(--po-primary);
    box-shadow: 0 0 0 3px var(--po-primary-glow);
}
.po-input[readonly] {
    background: #f8fafc;
    color: #475569;
    border-style: dashed;
}

/* Searchable Target Vendor Combobox */
.po-vendor-combobox {
    position: relative;
    width: 100%;
}
.po-vendor-combo-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    border: 1.5px solid var(--po-border-subtle);
    border-radius: 7px;
    background: #ffffff;
    cursor: pointer;
    font-size: 0.82rem;
    color: var(--po-text-strong);
    transition: all 0.15s ease;
    user-select: none;
    text-align: left;
}
.po-vendor-combo-trigger:hover {
    border-color: #cbd5e1;
    background: #fafafa;
}
.po-vendor-combo-trigger.is-active,
.po-vendor-combo-trigger:focus {
    outline: none;
    border-color: var(--po-primary);
    box-shadow: 0 0 0 3px var(--po-primary-glow);
}
.po-vendor-combo-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    z-index: 1050;
    background: #ffffff;
    border: 1px solid rgba(226, 232, 240, 0.95);
    border-radius: 9px;
    box-shadow: 0 12px 28px rgba(15, 23, 42, 0.15), 0 4px 10px rgba(0, 0, 0, 0.05);
    display: none;
    flex-direction: column;
    overflow: hidden;
}
.po-vendor-combo-dropdown.is-active {
    display: flex;
}
.po-vendor-combo-search-wrap {
    padding: 7px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
    position: relative;
}
.po-vendor-combo-search-wrap i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}
.po-vendor-combo-search-input {
    width: 100%;
    padding: 5px 8px 5px 28px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    box-sizing: border-box;
    outline: none;
}
.po-vendor-combo-search-input:focus {
    border-color: var(--po-primary);
    box-shadow: 0 0 0 2px var(--po-primary-glow);
}
.po-vendor-combo-list {
    max-height: 220px;
    overflow-y: auto;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.po-vendor-combo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 9px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.79rem;
    transition: background 0.1s ease;
}
.po-vendor-combo-item:hover,
.po-vendor-combo-item.is-selected {
    background: #e0f2fe;
}
.po-vendor-combo-item.is-selected {
    font-weight: 700;
    color: var(--po-primary-dark);
}
.po-vendor-combo-empty {
    padding: 14px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.78rem;
}

/* Compact Active Vendor Info Banner */
.po-vendor-pill-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, rgba(240, 249, 255, 0.75), rgba(248, 250, 252, 0.85));
    border: 1px solid rgba(186, 230, 253, 0.65);
    border-radius: 8px;
    padding: 5px 9px;
}
.po-vendor-avatar {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: linear-gradient(135deg, #0284c7, #0d9488);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.78rem;
    flex-shrink: 0;
}
.po-vendor-summary {
    flex: 1;
    min-width: 0;
}
.po-vendor-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 0.81rem;
    color: var(--po-text-strong);
}
.po-vendor-detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    color: var(--po-text-muted);
    margin-top: 1px;
}

/* Items Panel Toolbar & Summary Bar */
.po-items-panel {
    background: var(--po-surface);
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    box-shadow: var(--po-shadow-sm);
    display: flex;
    flex-direction: column;
}
.po-items-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: #ffffff;
    border-bottom: 1px solid var(--po-border-subtle);
    flex-wrap: wrap;
    gap: 8px;
}
.po-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.po-items-counter-pill {
    background: var(--po-primary-subtle);
    color: var(--po-primary-dark);
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
}
.po-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}
.po-summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 16px;
    background: #f8fafc;
    border-top: 1px solid var(--po-border-subtle);
    border-radius: 0 0 var(--po-radius-lg) var(--po-radius-lg);
    flex-wrap: wrap;
    gap: 16px;
}
.po-metrics-cluster {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: wrap;
}
.po-metric-unit {
    display: flex;
    flex-direction: column;
}
.po-metric-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--po-text-muted);
    text-transform: uppercase;
}
.po-metric-val {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--po-text-strong);
    font-feature-settings: "tnum";
    font-variant-numeric: tabular-nums;
}
.po-metric-val.highlight {
    color: var(--po-primary);
}
.po-dual-inputs {
    display: flex;
    gap: 8px;
    align-items: center;
}
.po-dual-inputs .po-input {
    flex: 1;
    min-width: 0;
}

/* Financial Payment Details Panel */
.po-payment-panel {
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border: 1.5px solid #cbd5e1;
    border-radius: var(--po-radius-lg);
    box-shadow: var(--po-shadow-sm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.po-payment-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    background: #f1f5f9;
    border-bottom: 1px solid #cbd5e1;
}

/* Table Scaffolding */
.po-table-responsive {
    width: 100%;
    overflow-x: auto;
    position: relative;
    min-height: 240px;
}
.po-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.85rem;
    color: var(--po-text-strong);
}
.po-table th {
    background: #f8fafc;
    color: var(--po-text-medium);
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 12px 14px;
    border-bottom: 2px solid var(--po-border-subtle);
    text-align: left;
    white-space: nowrap;
}
.po-table th.th-num, .po-table td.td-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.po-table th.th-center, .po-table td.td-center {
    text-align: center;
}
.po-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    background: #ffffff;
}
.po-table tbody tr:hover td {
    background: #f8fafc;
}
.po-table td input, .po-table td select {
    width: 100%;
    padding: 6px 9px;
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-sm);
    font-size: 0.84rem;
    background: #ffffff;
    font-family: inherit;
    box-sizing: border-box;
}

/* Modals */
.po-modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.6);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
    box-sizing: border-box;
}
.po-modal-backdrop.is-open {
    display: flex;
}
.po-modal-card {
    background: #ffffff;
    border-radius: var(--po-radius-xl);
    box-shadow: var(--po-shadow-xl);
    width: 100%;
    max-width: 900px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.po-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--po-border-subtle);
    background: #f8fafc;
}
.po-modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}
.po-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--po-border-subtle);
    background: #f8fafc;
}

/* Printable PO Document */
.po-doc-paper {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 32px 36px;
    color: #1e293b;
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
.po-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #0f172a;
    padding-bottom: 18px;
    margin-bottom: 22px;
}
.po-doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.po-doc-table th {
    background: #0f172a;
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    padding: 9px 12px;
    border: 1px solid #0f172a;
}
.po-doc-table td {
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    font-size: 0.82rem;
}
.po-doc-table td.num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* ==========================================================================
   PROCUREMENT AUDIT TRAIL SYSTEM STYLES
   ========================================================================== */
.audit-overview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.audit-metric-card {
    background: #ffffff;
    border: 1px solid var(--po-border-subtle);
    border-radius: 10px;
    padding: 16px;
    box-shadow: var(--po-shadow-sm);
    display: flex;
    align-items: center;
    gap: 14px;
}
.audit-metric-icon-box {
    width: 44px;
    height: 44px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}
.audit-metric-val {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--po-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}
.audit-metric-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--po-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-top: 2px;
}
.audit-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    background: #ffffff;
    border: 1px solid var(--po-border-subtle);
    border-radius: 10px;
    padding: 12px 18px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}
.audit-filter-pills {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.audit-filter-pill {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.76rem;
    font-weight: 700;
    cursor: pointer;
    background: #f1f5f9;
    color: #475569;
    border: 1px solid transparent;
    transition: all 0.15s ease;
}
.audit-filter-pill:hover {
    background: #e2e8f0;
    color: #0f172a;
}
.audit-filter-pill.active {
    background: #0f172a;
    color: #ffffff;
}
.audit-timeline-container {
    position: relative;
    padding-left: 28px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.audit-timeline-container::before {
    content: '';
    position: absolute;
    top: 10px;
    bottom: 10px;
    left: 11px;
    width: 2px;
    background: #e2e8f0;
}
.audit-timeline-item {
    position: relative;
    background: #ffffff;
    border: 1px solid var(--po-border-subtle);
    border-radius: 10px;
    padding: 16px 20px;
    box-shadow: var(--po-shadow-sm);
    transition: all 0.15s ease;
}
.audit-timeline-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
}
.audit-timeline-node-icon {
    position: absolute;
    left: -28px;
    top: 18px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid #64748b;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transform: translateX(-50%);
    z-index: 2;
}
.audit-timeline-node-icon.is-created { border-color: #2563eb; color: #2563eb; }
.audit-timeline-node-icon.is-approved { border-color: #10b981; color: #10b981; }
.audit-timeline-node-icon.is-edited { border-color: #f59e0b; color: #d97706; }
.audit-timeline-node-icon.is-po { border-color: #8b5cf6; color: #7c3aed; }
.audit-timeline-node-icon.is-email { border-color: #06b6d4; color: #0891b2; }
.audit-timeline-node-icon.is-payment { border-color: #10b981; color: #059669; }
.audit-timeline-node-icon.is-revoked, .audit-timeline-node-icon.is-deleted { border-color: #ef4444; color: #dc2626; }
.audit-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.audit-badge.badge-created { background: #eff6ff; color: #1d4ed8; }
.audit-badge.badge-approved { background: #ecfdf5; color: #047857; }
.audit-badge.badge-edited { background: #fef3c7; color: #b45309; }
.audit-badge.badge-po { background: #f5f3ff; color: #6d28d9; }
.audit-badge.badge-email { background: #ecfeff; color: #0e7490; }
.audit-badge.badge-payment { background: #f0fdf4; color: #15803d; }
.audit-badge.badge-revoked, .audit-badge.badge-deleted { background: #fef2f2; color: #b91c1c; }
.audit-diff-table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
    font-size: 0.78rem;
    background: #f8fafc;
    border-radius: 6px;
    overflow: hidden;
}
.audit-diff-table th, .audit-diff-table td {
    padding: 6px 12px;
    text-align: left;
    border-bottom: 1px solid #e2e8f0;
}
.audit-diff-table th {
    font-weight: 700;
    color: var(--po-text-muted);
    width: 160px;
}
.audit-diff-table td {
    color: var(--po-text-strong);
    font-weight: 600;
}

/* Media Print */
@media print {
    body * { visibility: hidden; }
    #poDocumentPaper, #poDocumentPaper * { visibility: visible; }
    #poDocumentPaper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
        box-shadow: none;
        border: none;
    }
    .po-modal-footer, .po-modal-header { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="po-workspace-container" id="poAppWorkspace">

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="poToastContainer"></div>

    <!-- Top Action Header / Breadcrumb (HR Operations Standard - Clean Identity) -->
    <div class="hr-parent-title" style="margin-bottom: 2px;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8); flex-shrink: 0;">
                <i class="ph ph-receipt"></i>
            </div>
            <div>
                <h1 style="font-size: 1.25rem; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.2;">Purchase Orders (PO)</h1>
                <p style="font-size: 0.8rem; color: #64748b; margin: 2px 0 0 0;">Official procurement commitments, receiving dock tracking & vendor settlement</p>
            </div>
        </div>
    </div>

    <!-- PO Navigation Tabs Bar -->
    <nav class="po-nav-tabs-bar" id="poTabsBar">
        <button type="button" class="po-tab-btn active" id="tabBtnPoList" data-tab="tab-po-list" onclick="switchPoTab('tab-po-list')">
            <i class="ph ph-receipt"></i>
            <span>PO Directory & Status Tracker</span>
            <span class="po-tab-count" id="tabPoCount">0</span>
        </button>
        <button type="button" class="po-tab-btn" id="tabBtnPoBuilder" data-tab="tab-po-builder" onclick="switchPoTab('tab-po-builder')">
            <i class="ph ph-plus-circle"></i>
            <span id="tabPoBuilderTitle">Purchase Order Builder</span>
            <span class="po-tab-badge" id="tabPoBuilderBadge">New PO</span>
        </button>
    </nav>

    <!-- ====================================================================
         TAB 1: PO DIRECTORY & STATUS TRACKER
         ==================================================================== -->
    <div class="po-tab-pane active" id="pane-tab-po-list">
        <!-- KPI Metrics Grid -->
        <div class="po-kpi-grid">
            <div class="po-kpi-card" onclick="filterPoStatus('all')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Total Purchase Orders</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #eff6ff, #dbeafe); color: #2563eb;"><i class="ph ph-files"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoTotal">0</div>
                <div class="po-kpi-sub">Total procurement commitments</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Approved / Issued')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Pending Delivery</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #fff7ed, #ffedd5); color: #ea580c;"><i class="ph ph-truck"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPending">0</div>
                <div class="po-kpi-sub">Issued orders awaiting receiving dock</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Fully Received')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Fully Received</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); color: #059669;"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoReceived">0</div>
                <div class="po-kpi-sub">Inventoried and stocked in commissary</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoPayment('Paid in Full / Cash Out')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Settled / Paid</span>
                    <div class="po-kpi-icon" style="background: linear-gradient(135deg, #f5f3ff, #ede9fe); color: #7c3aed;"><i class="ph ph-currency-dollar"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPaid">0</div>
                <div class="po-kpi-sub">Financial cash-out / settled invoices</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="po-panel-card">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid var(--po-border-subtle); flex-wrap: nowrap; gap: 10px; overflow-x: auto; scrollbar-width: none;">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; flex-wrap: nowrap; min-width: 0;">
                    <div style="position: relative; flex: 1; min-width: 220px; max-width: 360px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px;"></i>
                        <input type="text" class="po-input" id="poSearchInput" oninput="handlePoSearch(this.value)" placeholder="Search PO #, vendor, RFQ ref, stall..." style="padding-left: 34px; height: 32px; font-size: 0.82rem;">
                    </div>
                    <div class="po-filter-pills-bar" id="poStatusFilterBar">
                        <button type="button" class="po-filter-pill active" data-filter="all" onclick="filterPoStatus('all', this)">All (<span id="countPoAll">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Standard" onclick="filterPoType('vendor', this)">Vendor Supplier</button>
                        <button type="button" class="po-filter-pill" data-filter="WetMarket" onclick="filterPoType('wet_market', this)">Wet Market Cash Run</button>
                        <button type="button" class="po-filter-pill" data-filter="Pending" onclick="filterPoStatus('Approved / Issued', this)">Pending Delivery (<span id="countPoPending">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Received" onclick="filterPoStatus('Fully Received', this)">Received (<span id="countPoReceived">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Paid" onclick="filterPoPayment('Paid in Full / Cash Out', this)">Paid (<span id="countPoPaid">0</span>)</button>
                    </div>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <!-- Column Visibility Selector Dropdown -->
                    <div style="position: relative;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnPoColConfig" onclick="togglePoColumnConfigDropdown(event)" style="padding: 4px 10px; font-size: 11.5px; height: 32px;" title="Customize visible table columns">
                            <i class="ph ph-columns"></i> Columns <i class="ph ph-caret-down" style="font-size: 10px;"></i>
                        </button>
                        <div class="po-col-dropdown" id="poColConfigDropdown" style="display: none;" onclick="event.stopPropagation()">
                            <div style="padding: 8px 12px; border-bottom: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 6px;">
                                <div style="display: flex; align-items: center; justify-content: space-between;">
                                    <span style="font-weight: 700; font-size: 12px; color: #0f172a; display: flex; align-items: center; gap: 5px;">
                                        <i class="ph ph-funnel" style="color: #9333ea;"></i> Column Visibility
                                    </span>
                                    <button type="button" class="inv-col-quick-link is-reset" onclick="resetPoColumnWidths()" title="Reset all column widths to defaults" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-arrow-counter-clockwise"></i> Reset Widths
                                    </button>
                                </div>
                                <div class="inv-col-dropdown-quick-links" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <button type="button" class="inv-col-quick-link" onclick="showAllPoColumns()" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-check-square"></i> Show All
                                    </button>
                                    <button type="button" class="inv-col-quick-link" onclick="resetPoColumnDefaults()" style="font-size: 11px; padding: 2px 6px;">
                                        <i class="ph ph-columns"></i> Defaults
                                    </button>
                                </div>
                            </div>
                            <div class="po-col-checklist" id="poColChecklist">
                                <!-- Populated dynamically -->
                            </div>
                            <div style="padding: 6px 12px; border-top: 1px solid #f1f5f9; background: #fafafa; font-size: 11px; color: #64748b; display: flex; align-items: center; justify-content: space-between;">
                                <span id="poColActiveCounter">0 visible</span>
                                <span>Drag headers to resize</span>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openImportRfqModal()" title="Convert Awarded RFQ to PO" style="padding: 4px 10px; font-size: 11.5px; height: 32px;">
                        <i class="ph ph-link"></i> Import RFQ
                    </button>
                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="startNewPoFromDirectory()" style="padding: 4px 12px; font-size: 11.5px; height: 32px;">
                        <i class="ph ph-plus-circle"></i> + Create PO
                    </button>
                </div>
            </div>

            <!-- PO Directory Table -->
            <div class="po-table-responsive" style="overflow-x: auto;">
                <table class="po-table" id="poDirectoryTable">
                    <thead id="poDirectoryThead">
                        <tr id="poDirectoryTheadRow">
                            <!-- Populated dynamically via renderPoTableHeader -->
                        </tr>
                    </thead>
                    <tbody id="poDirectoryTbody">
                        <!-- Rendered dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- Table Pagination Bar -->
            <div class="po-table-pagination-bar" style="display: flex; align-items: center; justify-content: space-between; padding: 10px 16px; border-top: 1px solid var(--po-border-subtle); flex-wrap: wrap; gap: 10px; font-size: 0.8rem; color: #64748b;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span>Showing <strong id="poPaginationStart" style="color: #0f172a;">0</strong> to <strong id="poPaginationEnd" style="color: #0f172a;">0</strong> of <strong id="poPaginationTotal" style="color: #0f172a;">0</strong> purchase orders</span>
                    <span>•</span>
                    <label for="poPageSizeSelect" style="display: inline-flex; align-items: center; gap: 4px; font-size: 0.78rem;">
                        <span>Per page:</span>
                        <select class="po-select" id="poPageSizeSelect" onchange="changePoPageSize(this.value)" style="height: 26px; padding: 2px 8px; font-size: 0.78rem; width: auto;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </label>
                </div>
                <div class="po-pagination-controls" id="poPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                    <!-- Rendered by JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: CREATE NEW PURCHASE ORDER (PO BUILDER)
         ==================================================================== -->
    <div class="po-tab-pane" id="pane-tab-po-builder" style="display: none;">

        <!-- Command Bar -->
        <div class="po-command-bar">
            <div class="po-command-left">
                <span style="font-size: 0.72rem; font-weight: 800; color: #64748b; letter-spacing: 0.08em; text-transform: uppercase; display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-sliders-horizontal" style="color: var(--po-primary); font-size: 14px;"></i> PO Workspace
                </span>
            </div>

            <div class="po-actions-group" style="display: flex; align-items: center; gap: 6px;">
                <button type="button" class="po-btn po-btn-outline" onclick="switchPoTab('tab-po-list')" title="Back to PO Directory" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-arrow-left"></i> Directory
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="resetPoForm()" title="Clear form & start new PO" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
                <button type="button" class="po-btn po-btn-outline" id="btnTogglePoAudit" onclick="openPoAuditDrawer()" title="View Procurement Audit Trail & Activity Log" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-clock-counter-clockwise"></i> Audit Trail
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="openImportRfqModal()" title="Import lines from Awarded RFQ" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-link"></i> Import Awarded RFQ
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="openPoDocumentPreview()" title="Open formal printable PO preview (F8)" style="padding: 4px 10px; font-size: 11.5px;">
                    <i class="ph ph-eye"></i> Preview <span class="po-hotkey-badge">F8</span>
                </button>
                <button type="button" class="po-btn po-btn-primary" onclick="issuePurchaseOrderSubmit()" title="Approve & save purchase order (F10)" style="padding: 4px 12px; font-size: 11.5px; background: linear-gradient(135deg, #0284c7, #0d9488);">
                    <i class="ph ph-check-circle"></i> Issue Purchase Order <span class="po-hotkey-badge" style="background: rgba(255,255,255,0.25);">F10</span>
                </button>
            </div>
        </div>

        <!-- Workspace Grid: Asymmetric 4.2fr Left (Cards 1 & 2) / 5.8fr Right (Card 3 Items Panel & Settlement) -->
        <div class="po-builder-workspace-grid">

            <!-- Left Column: Reference Header + Vendor Profile + PO Terms -->
            <div class="po-builder-left-col">

                <!-- Tender Reference & Identity Header Block (Prominent Placement with Description) -->
                <div class="po-panel-card po-tender-ref-card" style="border-left: 4px solid var(--po-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65)); padding: 10px 14px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20)); border: 1px solid rgba(168, 85, 247, 0.32); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 3px 10px rgba(168, 85, 247, 0.15);">
                                <i class="ph ph-receipt"></i>
                            </div>
                            <div>
                                <div style="font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Purchase Order Reference Number</div>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span id="poRefDisplay" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: var(--po-primary-dark); letter-spacing: -0.01em;">PO-2026-0105</span>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copyPoReference()" title="Copy Reference to Clipboard" style="padding: 2px 6px; font-size: 11px; height: 22px;">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                    <span id="poLinkedRfqPill" style="display: none; font-size: 11px; padding: 2px 7px; background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 5px; font-family: monospace; font-weight: 700;"></span>
                                </div>
                            </div>
                        </div>
                        <div>
                            <span class="status-pill draft" id="poStatusBadge" style="font-size: 11px; padding: 3px 9px;">
                                <i class="ph ph-dot"></i> Draft PO
                            </span>
                        </div>
                    </div>

                    <!-- PO Purpose / Description Field -->
                    <div class="po-field" style="margin-top: 2px;">
                        <label class="po-label" for="poOrderTitle" style="font-size: 0.70rem; color: #475569; margin-bottom: 3px;">
                            <span>Order Purpose / Title Description</span>
                            <span class="po-label-badge" style="font-size: 0.62rem;">Project Reference</span>
                        </label>
                        <input type="text" class="po-input" id="poOrderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" style="font-size: 0.84rem; padding: 5px 10px; font-weight: 500;" oninput="handlePoTitleChange(this.value)">
                    </div>
                </div>

                <!-- Card 1: Vendor Selection & Company Profile Details -->
                <div class="po-panel-card">
                    <div class="po-panel-header">
                        <div class="po-panel-title">
                            <i class="ph ph-buildings"></i>
                            <span>Vendor Selection & Partner Profile</span>
                        </div>
                        <span class="po-label-badge" id="poVendorCatalogBadge">Catalog Integrated</span>
                    </div>
                    <div class="po-panel-body">
                        <div class="po-field">
                            <label class="po-label" for="poVendorSelect">
                                <span>Select Target Vendor *</span>
                                <span class="po-label-badge">Searchable Masterlist</span>
                            </label>
                            <!-- Hidden select for form bindings & backwards-compatibility -->
                            <select class="po-select" id="poVendorSelect" style="display: none;" onchange="handlePoVendorSelect(this.value)">
                                <option value="">-- Choose Approved Vendor from Masterlist --</option>
                            </select>

                            <!-- Custom Searchable Combobox -->
                            <div class="po-vendor-combobox" id="poVendorCombobox">
                                <button type="button" class="po-vendor-combo-trigger" id="poVendorComboTrigger" onclick="togglePoVendorCombobox(event)">
                                    <span id="poVendorComboTriggerText" style="color: #64748b;">-- Choose Approved Vendor from Masterlist --</span>
                                    <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8;"></i>
                                </button>

                                <div class="po-vendor-combo-dropdown" id="poVendorComboDropdown">
                                    <div class="po-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                        <i class="ph ph-magnifying-glass"></i>
                                        <input type="text" class="po-vendor-combo-search-input" id="poVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterPoVendorCombobox(this.value)" autocomplete="off">
                                    </div>
                                    <div class="po-vendor-combo-list" id="poVendorComboList">
                                        <!-- Populated dynamically -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Active Vendor Summary Badge Box -->
                        <div class="po-vendor-pill-box" id="poVendorSummaryBox" style="display: none;">
                            <div class="po-vendor-avatar" id="poVendorAvatarText">VM</div>
                            <div class="po-vendor-summary">
                                <div class="po-vendor-name-row">
                                    <span id="poVendorTradeName">--</span>
                                    <span class="po-label-badge" id="poVendorCategoryTag">Category</span>
                                </div>
                                <div class="po-vendor-detail-row">
                                    <span><i class="ph ph-identification-card"></i> TIN: <strong id="poVendorTinDisplay">--</strong></span>
                                    <span>•</span>
                                    <span><i class="ph ph-map-pin"></i> <span id="poVendorLocationDisplay">--</span></span>
                                </div>
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poContactPerson">Contact Person</label>
                                <input type="text" class="po-input" id="poContactPerson" placeholder="Contact Person" readonly title="Primary Contact Person">
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poContactTitle">Designation</label>
                                <input type="text" class="po-input" id="poContactTitle" placeholder="Designation" readonly title="Contact Designation / Title">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poContactEmail">
                                    <span>Recipient Email *</span>
                                </label>
                                <input type="email" class="po-input" id="poContactEmail" placeholder="vendor@example.com" title="Vendor Recipient Email" required>
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poContactPhone">Phone / Mobile</label>
                                <input type="text" class="po-input" id="poContactPhone" placeholder="Direct Phone" title="Contact Direct Phone">
                            </div>
                        </div>

                        <div class="po-field">
                            <label class="po-label" for="poFullAddress">Physical / Billing Address</label>
                            <input type="text" class="po-input" id="poFullAddress" placeholder="Street, Building, City, ZIP">
                        </div>
                    </div>
                </div>

                <!-- Card 2: Procurement Terms & Delivery Schedules -->
                <div class="po-panel-card">
                    <div class="po-panel-header">
                        <div class="po-panel-title">
                            <i class="ph ph-calendar-check"></i>
                            <span>PO Terms & Delivery Schedule</span>
                        </div>
                        <span class="po-label-badge">Auto Lead-Time Sync</span>
                    </div>
                    <div class="po-panel-body">
                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poNumberInput">PO Reference #</label>
                                <input type="text" class="po-input" id="poNumberInput" value="PO-2026-0105" readonly style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poOrderDate">Date Issued</label>
                                <input type="date" class="po-input" id="poOrderDate">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poPaymentTerms">
                                    <span>Payment Terms</span>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openPaymentSettlementModal()" title="Open Financial Tracking & Payment Settlement" style="font-size: 11px; padding: 2px 7px; height: 22px; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="ph ph-bank" style="color: var(--po-primary);"></i> Financial Settlement
                                    </button>
                                </label>
                                <div style="display: flex; gap: 6px; align-items: center;">
                                    <select class="po-select" id="poPaymentTerms" style="flex: 1;" onchange="handlePaymentTermsChange(this.value)">
                                        <option value="Net 30 Days">Net 30 Days (Standard)</option>
                                        <option value="Net 15 Days">Net 15 Days</option>
                                        <option value="Net 7 Days">Net 7 Days</option>
                                        <option value="COD">Cash on Delivery (COD)</option>
                                        <option value="Advance Payment">100% Advance Payment</option>
                                        <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                                    </select>
                                    <button type="button" class="hr-btn hr-btn-secondary" onclick="openPaymentSettlementModal()" title="View and calculate payments filtered by this PO reference" style="padding: 5px 9px; font-size: 11.5px; height: 32px; display: inline-flex; align-items: center; gap: 5px; white-space: nowrap;">
                                        <span id="poSummaryPayBadge" class="pay-badge unpaid" style="font-size: 10px; padding: 2px 6px;">Unpaid</span>
                                    </button>
                                </div>
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poExpectedDelivery">
                                    <span>Expected Delivery *</span>
                                    <span class="po-label-badge" style="color: #0369a1;">Lead Time</span>
                                </label>
                                <input type="date" class="po-input" id="poExpectedDelivery">
                            </div>
                        </div>

                        <div class="po-form-row">
                            <div class="po-field">
                                <label class="po-label" for="poDeliveryLocation">Destination Facility</label>
                                <select class="po-select" id="poDeliveryLocation">
                                    <option value="Central Commissary - Receiving Dock A">Central Commissary - Dock A</option>
                                    <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                                    <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                                    <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                                    <option value="Central Warehouse - Cold Storage">Central Warehouse - Cold Storage</option>
                                </select>
                            </div>
                            <div class="po-field">
                                <label class="po-label" for="poPaymentMethod">Payment Method</label>
                                <select class="po-select" id="poPaymentMethod">
                                    <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15 Days)</option>
                                    <option value="Company Check">Company Check</option>
                                    <option value="Bank Transfer / Electronic">Bank Transfer / Electronic</option>
                                    <option value="Cash / Petty Cash">Cash / Petty Cash</option>
                                    <option value="GCash / Maya">GCash / Maya</option>
                                </select>
                            </div>
                        </div>

                        <div class="po-field">
                            <label class="po-label" for="poSpecialNotes">Purchase Order Instructions / Notes</label>
                            <textarea class="po-textarea" id="poSpecialNotes" placeholder="e.g. Inspect temperature upon receiving dock. Meat cuts must be vacuum sealed and weighing ticket attached."></textarea>
                        </div>
                    </div>
                </div>

            </div><!-- /.po-builder-left-col -->

            <!-- Right Column: Card 3 Line Items Panel & Card 4 Financial Settlement -->
            <div class="po-builder-right-col">

                <!-- Purchase Order Line Items & Specifications -->
                <div class="po-items-panel">
                    <div class="po-items-toolbar">
                        <div class="po-toolbar-left">
                            <div style="font-weight: 800; font-size: 0.95rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 8px;">
                                <i class="ph ph-list-numbers" style="color: var(--po-primary); font-size: 18px;"></i>
                                <span>Purchase Order Line Items & Specifications</span>
                            </div>
                            <span class="po-items-counter-pill" id="poItemCountBadge">0 Line Items</span>
                        </div>

                        <div class="po-toolbar-right">
                            <button type="button" class="po-btn po-btn-outline" onclick="openItemMasterQuickPicker()" title="Quick add items from Item Master (F2)" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-magnifying-glass"></i> Select Master <span class="po-hotkey-badge">F2</span>
                            </button>
                            <button type="button" class="po-btn po-btn-teal" onclick="addNewBlankPoItemRow()" title="Add custom non-catalog item specification" style="padding: 5px 10px; font-size: 11.5px;">
                                <i class="ph ph-plus-circle"></i> Add Custom Row
                            </button>
                            <button type="button" class="po-btn po-btn-outline" style="color: var(--po-danger); padding: 5px 8px; font-size: 11.5px;" onclick="clearAllPoItems()" title="Remove all items from table">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Table Container with Horizontal & Vertical Scroll -->
                    <div class="po-table-responsive" id="poTableContainer">
                        <table class="po-table" id="poItemsTable">
                            <thead>
                                <tr>
                                    <th style="width: 42px; text-align: center;">#</th>
                                    <th style="min-width: 220px;">Item Name & Specifications *</th>
                                    <th style="width: 90px; text-align: center;">UOM / Pack</th>
                                    <th style="width: 105px; text-align: right;">Ordered Qty *</th>
                                    <th style="width: 120px; text-align: right;">Unit Price (₱) *</th>
                                    <th style="width: 130px; text-align: right;">Line Total</th>
                                    <th style="width: 60px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="poItemsTbody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Items Table Footer Metric Summary -->
                    <div class="po-summary-bar">
                        <div class="po-metrics-cluster">
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Lines</span>
                                <span class="po-metric-val" id="summaryPoLines">0</span>
                            </div>
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Total Units</span>
                                <span class="po-metric-val" id="summaryPoUnits">0.00</span>
                            </div>
                            <div class="po-metric-unit">
                                <span class="po-metric-label">Grand Total</span>
                                <span class="po-metric-val highlight" id="summaryPoGross">₱0.00</span>
                            </div>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <div style="font-size: 0.74rem; color: var(--po-text-muted);">
                                <i class="ph ph-info"></i> All procurement totals reflect agreed contracted pricing.
                            </div>
                        </div>
                    </div>
                </div><!-- /.po-items-panel -->

            </div><!-- /.po-builder-right-col -->

        </div><!-- /.po-builder-workspace-grid -->

    </div><!-- /#pane-tab-po-builder -->

</div>

<!-- ==========================================================================
     DRAWER: PURCHASE ORDER AUDIT TRAIL RIGHT DRAWER
     ========================================================================== -->
<div id="poAuditDrawerOverlay" class="hr-drawer-overlay" onclick="closePoAuditDrawer()"></div>
<div id="poAuditDrawer" class="hr-drawer" style="max-width: 620px; width: 100%;">
    <div class="hr-drawer-header">
        <button type="button" class="hr-drawer-close" onclick="closePoAuditDrawer()" title="Close Drawer">
            <i class="ph ph-x"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                <i class="ph ph-clock-counter-clockwise"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Purchase Order Audit Trail</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Financial & fulfillment logs • <span id="tabPoAuditCount" style="font-weight: 700; color: #9333ea;">0</span> events
                </div>
            </div>
        </div>
    </div>
    <div class="hr-drawer-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto;">
        <!-- Target Scope Indicator Bar: Restricts index to Current PO / RFQ for process optimization -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: rgba(147, 51, 234, 0.05); border: 1px solid rgba(147, 51, 234, 0.16); border-radius: 8px; padding: 6px 12px; font-size: 11.5px;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span style="color: #64748b; font-weight: 600;">Active PO Index:</span>
                <span id="poAuditTargetRefBadge" style="font-family: monospace; font-weight: 800; color: #9333ea; background: #ffffff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(147, 51, 234, 0.25);">PO-2026-0105</span>
                <span id="poAuditTargetRfqBadge" style="font-family: monospace; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(2, 132, 199, 0.25); display: none;"></span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <button type="button" class="audit-filter-pill active" id="btnPoAuditScopeCurrent" onclick="setPoAuditScope('CURRENT')" title="Limit processing strictly to active PO / RFQ" style="padding: 2px 8px; font-size: 10.5px;">Current PO Only</button>
                <button type="button" class="audit-filter-pill" id="btnPoAuditScopeAll" onclick="setPoAuditScope('ALL')" title="Expand index to all historical records" style="padding: 2px 8px; font-size: 10.5px;">All Records</button>
            </div>
        </div>

        <!-- Audit KPI Summary Cards -->
        <div class="audit-overview-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: rgba(168, 85, 247, 0.12); color: #9333ea; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTotal" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Total PO Events</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #fff7ed; color: #ea580c; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-storefront"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiWetMarket" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Wet Market Runs</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfdf5; color: #059669; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-currency-circle-dollar"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiPayments" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Settlements</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #f5f3ff; color: #7c3aed; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTransfers" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Transfers & Issues</div>
                </div>
            </div>
        </div>

        <!-- Audit Toolbar & Filter Bar -->
        <div class="audit-toolbar" style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="position: relative; width: 100%;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--po-text-muted); font-size: 15px;"></i>
                <input type="text" class="po-input" id="poAuditSearchInput" placeholder="Search PO #, supplier/stall, payment ref..." style="padding-left: 32px; height: 32px; font-size: 0.80rem;" oninput="onPoAuditSearchChange(this.value)">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" class="audit-filter-pill active" data-module="ALL" onclick="filterPoAuditByModule('ALL', this)" style="padding: 2px 8px; font-size: 11px;">All</button>
                    <button type="button" class="audit-filter-pill" data-module="PO" onclick="filterPoAuditByModule('PO', this)" style="padding: 2px 8px; font-size: 11px;">PO Only</button>
                    <button type="button" class="audit-filter-pill" data-module="RFQ" onclick="filterPoAuditByModule('RFQ', this)" style="padding: 2px 8px; font-size: 11px;">RFQ Only</button>
                </div>
                <div class="audit-filter-pills" id="poAuditActionFilterPills" style="display: flex; gap: 4px; flex-wrap: wrap;">
                    <button type="button" class="audit-filter-pill active" data-action="ALL" onclick="filterPoAuditByAction('ALL', this)" style="padding: 2px 7px; font-size: 10.5px;">All</button>
                    <button type="button" class="audit-filter-pill" data-action="PAYMENT_RECORDED" onclick="filterPoAuditByAction('PAYMENT_RECORDED', this)" style="padding: 2px 7px; font-size: 10.5px;">Paid</button>
                    <button type="button" class="audit-filter-pill" data-action="WET_MARKET_CREATED" onclick="filterPoAuditByAction('WET_MARKET_CREATED', this)" style="padding: 2px 7px; font-size: 10.5px;">Market</button>
                    <button type="button" class="audit-filter-pill" data-action="PO_TRANSFERRED" onclick="filterPoAuditByAction('PO_TRANSFERRED', this)" style="padding: 2px 7px; font-size: 10.5px;">RFQ Transfers</button>
                </div>
            </div>
        </div>

        <!-- Chronological Timeline Container -->
        <div class="audit-timeline-container" id="poAuditTimeline">
            <!-- Dynamically populated via renderPoAuditTrail -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: LINKED RFQ QUICK DETAILS INSPECTOR
     ========================================================================== -->
<div class="po-modal-backdrop" id="poLinkedRfqModal" role="dialog" aria-modal="true" aria-labelledby="linkedRfqModalTitle">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a; display: flex; align-items: center; gap: 10px;" id="linkedRfqModalTitle">
                <i class="ph ph-link" style="color: #9333ea;"></i>
                <span>Originating Tender & Quotation Approval Details</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeLinkedRfqModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" id="linkedRfqModalBody" style="max-height: 70vh; overflow-y: auto; padding: 18px;">
            <!-- Dynamically populated via openLinkedRfqModal -->
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeLinkedRfqModal()">Close Inspector</button>
            <a href="{{ route('purchase.request-quotations') }}" class="po-btn po-btn-primary" target="_blank" style="text-decoration: none;">
                <i class="ph ph-arrow-square-out"></i> Open in RFQ Module
            </a>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: IMPORT FROM AWARDED RFQ LIST
     ========================================================================== -->
<div class="po-modal-backdrop" id="poImportRfqModal" role="dialog" aria-modal="true" aria-labelledby="importRfqModalTitle">
    <div class="po-modal-card" style="max-width: 820px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.1rem; color: #0f172a; display: flex; align-items: center; gap: 10px;" id="importRfqModalTitle">
                <i class="ph ph-link" style="color: #9333ea;"></i>
                <span>Import Approved / Awarded RFQ into Purchase Order</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeImportRfqModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" style="max-height: 68vh; overflow-y: auto; padding: 16px;">
            <div style="font-size: 12.5px; color: #64748b; margin-bottom: 12px;">
                Select any approved quotation tender to automatically populate vendor profile, logistics terms, and awarded item specifications into the Purchase Order Builder:
            </div>
            <div id="importRfqListContainer" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Populated dynamically via openImportRfqModal -->
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeImportRfqModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL: FINANCIAL TRACKING & PAYMENT SETTLEMENT
     ========================================================================== -->
<div class="po-modal-backdrop" id="poPaymentSettlementModal" role="dialog" aria-modal="true" aria-labelledby="payModalTitle">
    <div class="po-modal-card" style="max-width: 880px; width: 100%;">
        <div class="po-modal-header" style="background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 9px; background: linear-gradient(135deg, #0284c7, #0d9488); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 3px 10px rgba(2, 132, 199, 0.25);">
                    <i class="ph ph-bank"></i>
                </div>
                <div>
                    <div style="font-weight: 800; font-size: 1.12rem; color: #0f172a;" id="payModalTitle">Financial Tracking & Payment Settlement</div>
                    <div style="font-size: 0.76rem; color: #64748b;">Disbursement schedule, installments & trade credit tracking by reference</div>
                </div>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePaymentSettlementModal()" aria-label="Close Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div class="po-modal-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; max-height: 75vh; overflow-y: auto;">
            <!-- Reference Filter & Context Bar -->
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px;">
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <span style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase;">Active Target Reference:</span>
                    <span id="payModalActiveRef" style="font-family: monospace; font-size: 1.05rem; font-weight: 800; color: var(--po-primary-dark); background: var(--po-primary-subtle); padding: 2px 8px; border-radius: 6px; border: 1px solid rgba(2, 132, 199, 0.25);">PO-2026-0105</span>
                    <span id="payModalLinkedRfqBadge" style="display: none; font-size: 11px; padding: 2px 7px; background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25); border-radius: 5px; font-family: monospace; font-weight: 700;"></span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <label for="payModalRefSelect" style="font-size: 0.76rem; color: #64748b; font-weight: 600;">Filter by Reference:</label>
                    <select id="payModalRefSelect" class="po-select" style="max-width: 200px; padding: 4px 8px; font-size: 12px; height: 30px;" onchange="filterPaymentsByReference(this.value)">
                        <!-- Populated dynamically -->
                    </select>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="resetPaymentViewToActivePo()" title="View Current Active PO" style="height: 30px; font-size: 11.5px; padding: 0 8px;">
                        <i class="ph ph-arrow-counter-clockwise"></i> Active PO
                    </button>
                </div>
            </div>

            <!-- Financial Calculation KPI Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Commitment (Gross)</div>
                    <div id="payModalGross" style="font-size: 1.25rem; font-weight: 800; color: #0f172a; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #94a3b8;">Contracted item lines</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Amount Paid</div>
                    <div id="payModalPaid" style="font-size: 1.25rem; font-weight: 800; color: #059669; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #059669;">Recorded disbursements</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04);">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Remaining Balance Due</div>
                    <div id="payModalBalance" style="font-size: 1.25rem; font-weight: 800; color: #dc2626; margin-top: 3px;">₱0.00</div>
                    <div style="font-size: 0.68rem; color: #dc2626;">Net unsettled liability</div>
                </div>
                <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; padding: 10px 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); display: flex; flex-direction: column; justify-content: space-between;">
                    <div style="font-size: 0.72rem; font-weight: 700; color: #64748b; text-transform: uppercase;">Settlement Status</div>
                    <div>
                        <span id="payModalStatusBadge" class="pay-badge unpaid" style="font-size: 12px; padding: 3px 10px;">Unpaid / Credit</span>
                    </div>
                    <div id="payModalPercentPaid" style="font-size: 0.68rem; color: #64748b;">0% settled</div>
                </div>
            </div>

            <!-- Payments Ledger History Table -->
            <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 14px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <div style="font-weight: 800; font-size: 0.86rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-list-dashes" style="color: var(--po-primary);"></i>
                        <span>Disbursement History for <span id="payLedgerRefLabel" style="font-family: monospace;">--</span></span>
                    </div>
                    <span id="payLedgerCountBadge" style="font-size: 11px; background: #e2e8f0; color: #475569; padding: 1px 7px; border-radius: 8px; font-weight: 700;">0 Entries</span>
                </div>
                <div style="max-height: 180px; overflow-y: auto;">
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left; color: #475569;">
                                <th style="padding: 7px 10px; width: 40px; text-align: center;">#</th>
                                <th style="padding: 7px 10px; width: 95px;">Date</th>
                                <th style="padding: 7px 10px; width: 130px;">Method</th>
                                <th style="padding: 7px 10px;">Reference / OR #</th>
                                <th style="padding: 7px 10px; width: 140px;">Fund Source</th>
                                <th style="padding: 7px 10px; text-align: right; width: 110px;">Amount (₱)</th>
                                <th style="padding: 7px 10px; text-align: center; width: 60px;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="payModalLedgerTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Record New Payment Disbursement Card -->
            <div style="background: #ffffff; border: 1.5px solid var(--po-primary); border-radius: 10px; padding: 12px 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                    <div style="font-weight: 800; font-size: 0.88rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-plus-circle" style="color: var(--po-primary);"></i>
                        <span>Record Payment Disbursement against Reference</span>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="fillFullRemainingBalance()" style="font-size: 11px; padding: 2px 8px; height: 24px; color: #059669; border-color: #a7f3d0; background: #ecfdf5;">
                        <i class="ph ph-lightning"></i> Pay Full Balance
                    </button>
                </div>

                <div class="po-form-row">
                    <div class="po-field">
                        <label class="po-label" for="newPayAmount">Amount to Disburse (₱) *</label>
                        <input type="number" step="0.01" min="0.01" class="po-input" id="newPayAmount" placeholder="0.00" style="font-weight: 700; font-size: 0.95rem;">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayMethod">Disbursement Method *</label>
                        <select class="po-select" id="newPayMethod">
                            <option value="Cash / Petty Cash">Cash / Petty Cash (Direct Run)</option>
                            <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15 Days Invoice)</option>
                            <option value="Company Check">Company Check (Spot / Post-Dated)</option>
                            <option value="Bank Transfer / Electronic">Bank Transfer / Electronic (PESONet / InstaPay)</option>
                            <option value="GCash / Maya">GCash / Maya (Mobile e-Wallet)</option>
                            <option value="Corporate Credit Card">Corporate Credit Card</option>
                        </select>
                    </div>
                </div>

                <div class="po-form-row" style="margin-top: 6px;">
                    <div class="po-field">
                        <label class="po-label" for="newPayRef">OR # / Check # / Receipt Ref</label>
                        <input type="text" class="po-input" id="newPayRef" placeholder="e.g. Cash Slip #4912, Check #00412, GCash 88214">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayDate">Payment Date</label>
                        <input type="date" class="po-input" id="newPayDate">
                    </div>
                </div>

                <div class="po-form-row" style="margin-top: 6px;">
                    <div class="po-field">
                        <label class="po-label" for="newPayFundSource">Disbursing Fund Source</label>
                        <select class="po-select" id="newPayFundSource">
                            <option value="Main Commissary Checking Acct">Main Commissary Checking Account</option>
                            <option value="Branch Petty Cash Fund">Branch Petty Cash Fund</option>
                            <option value="Purchaser Cash Advance">Purchaser Cash Advance Fund</option>
                            <option value="Corporate Card Account">Corporate Card Account</option>
                        </select>
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="newPayRemarks">Financial Remarks</label>
                        <input type="text" class="po-input" id="newPayRemarks" placeholder="e.g. 50% initial downpayment for batch production">
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 10px;">
                    <button type="button" class="hr-btn hr-btn-primary" onclick="addPaymentDisbursement()" style="padding: 6px 16px; font-size: 12px; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="ph ph-plus-circle"></i> Add Disbursement Entry
                    </button>
                </div>
            </div>
        </div>

        <div class="po-modal-footer" style="padding: 10px 16px; display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border-top: 1px solid #e2e8f0;">
            <div style="font-size: 11.5px; color: #64748b;">
                <i class="ph ph-info"></i> Recorded disbursements are automatically synchronized with the active Purchase Order.
            </div>
            <button type="button" class="po-btn po-btn-primary" onclick="closePaymentSettlementModal()" style="padding: 6px 18px; font-size: 12px;">
                Done
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: FORMAL PRINTABLE PURCHASE ORDER PREVIEW
     ========================================================================== -->
<div class="po-modal-backdrop" id="poDocumentModal" role="dialog" aria-modal="true" aria-labelledby="poModalTitle">
    <div class="po-modal-card">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.15rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 10px;" id="poModalTitle">
                <i class="ph ph-receipt" style="color: var(--po-primary);"></i>
                <span>Formal Purchase Order Document Preview</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePoDocumentPreview()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body">
            <div class="po-doc-paper" id="poDocumentPaper">
                <div class="po-doc-header">
                    <div>
                        <div style="font-size: 1.4rem; font-weight: 900; color: #0f172a;">RESTAURANT MANAGEMENT SYSTEM</div>
                        <div style="font-size: 0.84rem; color: #64748b; margin-top: 3px;">Central Commissary & Logistics Purchasing Division</div>
                        <div style="font-size: 0.8rem; color: #64748b;">Lopez Center, Pasig City • VAT Reg. TIN: 009-887-124-000</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 1.4rem; font-weight: 900; color: var(--po-primary-dark); text-transform: uppercase;">PURCHASE ORDER</div>
                        <div style="font-size: 0.85rem; margin-top: 4px;">PO #: <strong id="docPoNumber">PO-2026-0105</strong></div>
                        <div style="font-size: 0.85rem;">Date Issued: <strong id="docPoDate">--</strong></div>
                        <div style="font-size: 0.85rem;">Delivery Due: <strong id="docPoDeliveryDue" style="color: var(--po-teal-dark);">--</strong></div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
                        <div style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                            VENDOR / SOURCING PARTNER
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a;" id="docPoVendor">--</div>
                        <div style="font-size: 0.82rem; color: #475569; margin-top: 4px;" id="docPoVendorDetails">
                            Attn: -- • Phone: --<br>
                            Location: --
                        </div>
                    </div>

                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px;">
                        <div style="font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px;">
                            SHIP-TO & FINANCIAL SETTLEMENT
                        </div>
                        <div style="font-size: 1.05rem; font-weight: 800; color: #0f172a;" id="docPoDeliveryDest">Central Commissary</div>
                        <div style="font-size: 0.82rem; color: #475569; margin-top: 4px;">
                            <strong>Payment Status:</strong> <span id="docPoPaymentStatus">Unpaid</span><br>
                            <strong>Method:</strong> <span id="docPoPaymentMethod">Cash / Credit</span> | <strong>Ref:</strong> <span id="docPoPaymentRef">N/A</span>
                        </div>
                    </div>
                </div>

                <table class="po-doc-table">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">#</th>
                            <th style="width: 100px;">SKU</th>
                            <th>Description & Specifications</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 90px; text-align: right;">Quantity</th>
                            <th style="width: 120px; text-align: right;">Unit Price (₱)</th>
                            <th style="width: 130px; text-align: right;">Amount (₱)</th>
                        </tr>
                    </thead>
                    <tbody id="docPoItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>

                <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                    <div style="width: 280px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px;">
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px;">
                            <span>Gross Total:</span>
                            <strong id="docPoGross">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; color: #059669;">
                            <span>Amount Paid:</span>
                            <strong id="docPoPaid">₱0.00</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; font-size: 1rem; font-weight: 900; border-top: 1.5px solid #cbd5e1; padding-top: 6px;">
                            <span>Balance Due:</span>
                            <strong style="color: #dc2626;" id="docPoBalance">₱0.00</strong>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 32px; padding-top: 16px; border-top: 1px solid #cbd5e1;">
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">PREPARED BY:</div>
                        <div style="font-weight: 800; margin-top: 20px;">{{ auth()->user()->name ?? 'Procurement Officer' }}</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Procurement Specialist</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">FINANCIAL APPROVAL:</div>
                        <div style="font-weight: 800; margin-top: 20px;" id="docPoApprover">System Administrator</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Finance / General Manager</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: #64748b;">RECEIVED / VENDOR CONFORME:</div>
                        <div style="font-style: italic; color: #94a3b8; margin-top: 20px;">Signature & Date Stamp</div>
                        <div style="font-size: 0.74rem; color: #64748b;">Receiving Staff / Market Stallholder</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="window.print()">
                <i class="ph ph-printer"></i> Print / Download PDF
            </button>
            <button type="button" class="po-btn po-btn-teal" onclick="closePoDocumentPreview()">
                Done
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: ITEM MASTER QUICK PICKER FOR PO
     ========================================================================== -->
<div class="po-modal-backdrop" id="poItemMasterModal" role="dialog" aria-modal="true">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div style="font-weight: 800; font-size: 1.15rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 10px;">
                <i class="ph ph-database" style="color: var(--po-primary);"></i>
                <span>Item Master Quick Catalog</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closeItemMasterQuickPicker()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body">
            <div style="margin-bottom: 14px;">
                <input type="text" class="po-input" id="poItemMasterSearch" oninput="filterPoItemMaster(this.value)" placeholder="Search SKU, item name, specs (e.g. Ribeye, Butter, Packaging)...">
            </div>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--po-border-subtle); border-radius: 8px;">
                <table class="po-table">
                    <thead>
                        <tr>
                            <th style="width: 100px;">SKU</th>
                            <th>Item Name & Description</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 110px; text-align: right;">Benchmark</th>
                            <th style="width: 90px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="poItemMasterTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closeItemMasterQuickPicker()">Close</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: SINGLE-ITEM PURCHASE ORDER AUDIT TRAIL MODAL
     ========================================================================== -->
<div class="po-modal-backdrop" id="poSingleAuditModal" role="dialog" aria-modal="true" aria-labelledby="singlePoAuditModalTitle">
    <div class="po-modal-card" style="max-width: 760px;">
        <div class="po-modal-header">
            <div class="po-modal-title" id="singlePoAuditModalTitle">
                <i class="ph ph-clock-counter-clockwise" style="color: var(--po-primary);"></i>
                <span id="singlePoAuditHeaderTitle">Purchase Order Audit Trail</span>
            </div>
            <button type="button" class="inv-table-filter-btn" onclick="closePoSingleAuditModal()" aria-label="Close Audit Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="po-modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong id="singlePoAuditRefTitle" style="font-size: 1rem; color: var(--po-text-strong);">PO-2026-XXXX</strong>
                    <div id="singlePoAuditSubTitle" style="font-size: 0.8rem; color: var(--po-text-muted);">Procurement Activity & Financial Settlement Trail</div>
                </div>
                <span class="audit-badge badge-payment" id="singlePoAuditStatusBadge">Financial Audit</span>
            </div>

            <!-- Single Item Chronological Timeline -->
            <div class="audit-timeline-container" id="singlePoAuditTimelineContainer">
                <!-- Dynamically rendered -->
            </div>
        </div>
        <div class="po-modal-footer">
            <button type="button" class="po-btn po-btn-outline" onclick="closePoSingleAuditModal()">Close Audit Log</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
/**
 * ==========================================================================
 * PURCHASE ORDERS (PO) ARCHITECTURAL CORE
 * Seamless Support: Standard Vendor POs + Direct Wet Market Cash Purchases,
 * Integrated Financial Tracking & Settlement, LocalStorage Persistence & Red-Green Verification
 * ==========================================================================
 */

// Seeded Item Master Dictionary
const PO_ITEM_MASTER = [
    { sku: 'RAW-301', name: 'Barista Whole Fresh Milk', specs: '100% Pure Cow Fresh Chilled Milk', unit: 'Liter', defaultCost: 85.00, category: 'Raw Ingredients' },
    { sku: 'RAW-302', name: 'Arabica Espresso Beans (Single Origin)', specs: 'Medium-Dark Roast, Highland Benguet Arabica', unit: 'Kg', defaultCost: 550.00, category: 'Raw Ingredients' },
    { sku: 'RAW-303', name: 'French Butter Blocks Unsalted', specs: '82% Butterfat Culinary Grade', unit: 'Pc', defaultCost: 120.00, category: 'Raw Ingredients' },
    { sku: 'RAW-304', name: 'Cheddar Melt Shredded Cheese', specs: 'High-Melt Blend for Burgers & Pasta', unit: 'Kg', defaultCost: 320.00, category: 'Raw Ingredients' },
    { sku: 'RAW-305', name: 'Culinary Whipping Cream 35%', specs: 'UHT Animal Fat Whipping Cream', unit: 'Liter', defaultCost: 180.00, category: 'Raw Ingredients' },
    { sku: 'RAW-306', name: 'All-Purpose Wheat Flour', specs: 'Unbleached Baking Wheat Flour', unit: 'Kg', defaultCost: 39.20, category: 'Raw Ingredients' },
    { sku: 'RAW-307', name: 'Farm Fresh Table Eggs XL', specs: 'Grade A Farm Fresh Eggs Tray of 30', unit: 'Pc', defaultCost: 7.50, category: 'Raw Ingredients' },
    { sku: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', unit: 'Kg', defaultCost: 840.00, category: 'Raw Ingredients' },
    { sku: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', unit: 'Kg', defaultCost: 340.00, category: 'Raw Ingredients' },
    { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', unit: 'Pc', defaultCost: 4.50, category: 'Packaging' },
    { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', unit: 'Pc', defaultCost: 8.20, category: 'Packaging' }
];

// Seeded PO Directory populated with realistic vendor orders and wet market runs
const SEEDED_PURCHASE_ORDERS = [
    {
        poNumber: 'PO-2026-0101',
        poType: 'vendor',
        rfqReference: 'RFQ-2026-0038',
        vendorId: 'VND-SAN-002',
        vendorName: 'San Miguel Pure Foods Company Inc.',
        vendorTradeName: 'San Miguel Foods',
        vendorContactPerson: 'Patricia Lim',
        vendorPhone: '+63 917 882 1044',
        vendorEmail: 'orders.foodservice@sanmiguel.com.ph',
        vendorAddress: '40 San Miguel Ave, Mandaluyong City',
        orderDate: '2026-09-25',
        expectedDelivery: '2026-10-02',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 34600.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: 'Trade invoice terms net 30 days.',
        approvalNotes: 'Approved as lowest compliant bidder for Q4 meat supply.',
        approvedBy: 'Procurement Director',
        status: 'Approved / Issued',
        items: [
            { sku: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 25.0, unitPrice: 840.00 },
            { sku: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 40.0, unitPrice: 340.00 }
        ]
    },
    {
        poNumber: 'PO-2026-0102',
        poType: 'wet_market',
        rfqReference: 'None (Direct Market Purchase)',
        vendorId: 'WET-MKT-001',
        vendorName: 'Balintawak Wet Market - Stall #14 (Aling Nena Meats)',
        vendorTradeName: 'Aling Nena Wet Market Meats',
        vendorContactPerson: 'Elena Bautista (Stall Owner)',
        vendorPhone: '+63 922 411 9044',
        vendorEmail: '',
        vendorAddress: 'Balintawak Public Market, EDSA, Quezon City',
        orderDate: '2026-09-28',
        expectedDelivery: '2026-09-28',
        deliveryLocation: 'Branch 1 - Makati Flagship',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'Cash / Petty Cash',
        amountPaid: 8500.00,
        balanceDue: 0.00,
        paymentReference: 'Cash Slip #4102',
        paymentDate: '2026-09-28',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: 'Immediate cash payment at Balintawak market run for urgent weekend catering beef.',
        approvalNotes: 'Emergency market purchase approved by Head Chef.',
        approvedBy: 'Executive Chef Marco',
        status: 'Fully Received',
        items: [
            { sku: 'WET-BEEF', name: 'Fresh Beef Brisket Slab', specs: 'Wet Market Fresh local Batangas beef cut', category: 'Raw Ingredients', unit: 'Kg', quantity: 20.0, unitPrice: 380.00 },
            { sku: 'WET-BONE', name: 'Beef Marrow Bones', specs: 'Fresh soup bone knuckles', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, unitPrice: 90.00 }
        ]
    },
    {
        poNumber: 'PO-2026-0103',
        poType: 'vendor',
        rfqReference: 'RFQ-2026-0040',
        vendorId: 'VND-ECO-004',
        vendorName: 'EcoPack Solutions Philippines Corp.',
        vendorTradeName: 'EcoPack Packaging',
        vendorContactPerson: 'Grace Villanueva',
        vendorPhone: '+63 920 918 2234',
        vendorEmail: 'sales@ecopack.ph',
        vendorAddress: '14 Industrial Ave, Valenzuela City',
        orderDate: '2026-09-29',
        expectedDelivery: '2026-10-04',
        deliveryLocation: 'Central Warehouse - Dry Storage',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'Bank Transfer / Electronic',
        amountPaid: 17200.00,
        balanceDue: 0.00,
        paymentReference: 'InstaPay Ref 99041285',
        paymentDate: '2026-09-29',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: 'Advance bank transfer paid per terms.',
        approvalNotes: 'Approved for quarterly takeout cup inventory.',
        approvedBy: 'Procurement Specialist',
        status: 'Approved / Issued',
        items: [
            { sku: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', category: 'Packaging', unit: 'Pc', quantity: 2000.0, unitPrice: 4.50 },
            { sku: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', category: 'Packaging', unit: 'Pc', quantity: 1000.0, unitPrice: 8.20 }
        ]
    },
    {
        poNumber: 'PO-2026-0104',
        poType: 'wet_market',
        rfqReference: 'None (Direct Market Purchase)',
        vendorId: 'WET-MKT-002',
        vendorName: "Farmer's Market Cubao - Seafood Section Stall 8",
        vendorTradeName: "Farmer's Market Seafood",
        vendorContactPerson: 'Mang Rogelio',
        vendorPhone: '+63 919 443 1290',
        vendorEmail: '',
        vendorAddress: "Farmer's Market, Araneta City, Cubao, Quezon City",
        orderDate: '2026-09-30',
        expectedDelivery: '2026-09-30',
        deliveryLocation: 'Branch 2 - BGC Bistro',
        paymentStatus: 'Paid in Full / Cash Out',
        paymentMethod: 'GCash / Maya',
        amountPaid: 12400.00,
        balanceDue: 0.00,
        paymentReference: 'GCash Ref 882019451',
        paymentDate: '2026-09-30',
        fundSource: 'Purchaser Cash Advance',
        paymentRemarks: 'Morning market run for fresh tiger prawns and lapu-lapu.',
        approvalNotes: 'Direct payment via purchaser GCash advance.',
        approvedBy: 'Branch Manager',
        status: 'Fully Received',
        items: [
            { sku: 'WET-SEAFOOD-1', name: 'Live Black Tiger Prawns', specs: '20-25 count/kg fresh seafood catch', category: 'Raw Ingredients', unit: 'Kg', quantity: 12.0, unitPrice: 650.00 },
            { sku: 'WET-SEAFOOD-2', name: 'Fresh White Squid Whole', specs: 'Medium fresh local calamares squid', category: 'Raw Ingredients', unit: 'Kg', quantity: 10.0, unitPrice: 460.00 }
        ]
    }
];

// Master Application Store for PO
window.PoStore = {
    purchaseOrders: [],
    vendors: [],
    itemMaster: [],
    activePo: {
        poNumber: 'PO-2026-0105',
        poType: 'vendor', // 'vendor' or 'wet_market'
        title: '',
        rfqReference: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorContactPerson: '',
        vendorPhone: '',
        vendorEmail: '',
        vendorAddress: '',
        orderDate: '',
        expectedDelivery: '',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        specialNotes: '',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 0.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: '',
        approvalNotes: '',
        approvedBy: 'Procurement Specialist',
        status: 'Draft PO',
        items: []
    }
};

let activePoStatusFilter = 'all';
let activePoTypeFilter = 'all';
let poSearchTerm = '';
let poCurrentPage = 1;
let poPageSize = 10;

document.addEventListener('DOMContentLoaded', () => {
    initPoStore();
    initPoColumns();
    initPoDates();
    renderPoVendorDropdown();
    renderPoItemsTable();
    renderPoDirectory();

    // Hotkey listener (F2: Item Master, F8: Document Preview, F10: Issue PO)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            openItemMasterQuickPicker();
        } else if (e.key === 'F8') {
            e.preventDefault();
            openPoDocumentPreview();
        } else if (e.key === 'F10') {
            e.preventDefault();
            issuePurchaseOrderSubmit();
        } else if (e.key === 'Escape') {
            closeAllPoModals();
        }
    });

    // Close column dropdown on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('poColConfigDropdown');
        const btn = document.getElementById('btnPoColConfig');
        const actionBtn = document.getElementById('btnPoActionColFilter');
        if (dropdown && dropdown.style.display !== 'none' && !dropdown.contains(e.target) && (!btn || !btn.contains(e.target)) && (!actionBtn || !actionBtn.contains(e.target))) {
            dropdown.style.display = 'none';
        }
    });
});

function initPoStore() {
    const serverPos = @json($initialPurchaseOrders ?? []);
    const serverVendors = @json($initialVendors ?? []);
    const serverItems = @json($initialItems ?? []);

    // 1. Load Purchase Orders
    if (Array.isArray(serverPos) && serverPos.length > 0) {
        window.PoStore.purchaseOrders = serverPos.map(p => ({
            id: p.id,
            poNumber: p.po_number,
            poType: p.po_type || 'vendor',
            rfqReference: p.rfq_reference || '',
            vendorId: p.vendor_id || '',
            vendorName: p.vendor_name,
            vendorTradeName: p.vendor_trade_name || p.vendor_name,
            vendorContactPerson: p.vendor_contact_person || '',
            vendorPhone: p.vendor_phone || '',
            vendorEmail: p.vendor_email || '',
            vendorAddress: p.vendor_address || '',
            orderDate: p.order_date ? p.order_date.split('T')[0] : '',
            expectedDelivery: p.expected_delivery ? p.expected_delivery.split('T')[0] : '',
            deliveryLocation: p.delivery_location || 'Central Commissary - Receiving Dock A',
            specialNotes: p.special_notes || '',
            paymentStatus: p.payment_status || 'Unpaid / Credit',
            paymentMethod: p.payment_method || 'Trade Credit (Net 30/15)',
            amountPaid: parseFloat(p.amount_paid || 0),
            balanceDue: parseFloat(p.balance_due || 0),
            paymentReference: p.payment_reference || '',
            paymentDate: p.payment_date || '',
            fundSource: p.fund_source || '',
            paymentRemarks: p.payment_remarks || '',
            approvalNotes: p.approval_notes || '',
            approvedBy: p.approved_by || 'Procurement Specialist',
            status: p.status || 'Approved / Issued',
            items: (p.items || []).map(it => ({
                id: it.id,
                sku: it.sku,
                name: it.item_name,
                specs: it.specifications || '',
                category: it.category || 'Raw Ingredients',
                unit: it.unit || 'Kg',
                quantity: parseFloat(it.quantity_ordered),
                unitPrice: parseFloat(it.unit_price)
            }))
        }));
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
    } else {
        const storedPos = localStorage.getItem('rms_purchase_orders');
        if (storedPos) {
            try {
                window.PoStore.purchaseOrders = JSON.parse(storedPos);
            } catch (e) {
                window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS));
            }
        } else {
            window.PoStore.purchaseOrders = JSON.parse(JSON.stringify(SEEDED_PURCHASE_ORDERS));
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    // 2. Automatically sync any newly approved / awarded RFQs into PO list
    syncApprovedRfqsToPoDirectory();

    // 3. Load Vendors
    if (Array.isArray(serverVendors) && serverVendors.length > 0) {
        window.PoStore.vendors = serverVendors.map(v => ({
            id: v.vendor_code || ('VEN-' + v.id),
            vendorCode: v.vendor_code,
            name: v.legal_name,
            legalName: v.legal_name,
            tradeName: v.trade_name || v.legal_name,
            contactPerson: v.contact_person || '',
            phone: v.phone || '',
            email: v.email || '',
            address: v.address || '',
            paymentTerms: v.payment_terms || 'Net 30 Days'
        }));
    } else {
        const storedVendors = localStorage.getItem('rms_vendor_database_v6');
        if (storedVendors) {
            try {
                window.PoStore.vendors = JSON.parse(storedVendors);
            } catch (e) {
                window.PoStore.vendors = [];
            }
        }
    }

    // 4. Load Item Master
    if (Array.isArray(serverItems) && serverItems.length > 0) {
        window.PoStore.itemMaster = serverItems.map(p => ({
            sku: p.sku || 'SKU-' + p.id,
            name: p.name || 'Unnamed Product',
            specs: p.description || p.category_name || '',
            unit: p.unit || 'Unit',
            defaultCost: parseFloat(p.unit_cost || 0),
            category: p.category_name || 'Raw Ingredients'
        }));
    } else {
        const storedProducts = localStorage.getItem('rms_inventory_products');
        if (storedProducts) {
            try {
                const parsed = JSON.parse(storedProducts);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    window.PoStore.itemMaster = parsed.map(p => ({
                        sku: p.sku || 'SKU-' + p.id,
                        name: p.product || p.name || 'Unnamed Product',
                        specs: p.description || p.specs || '',
                        unit: p.uom || 'Unit',
                        defaultCost: parseFloat(p.cost_price || p.cost || 0),
                        category: p.category || 'Raw Ingredients'
                    }));
                } else {
                    window.PoStore.itemMaster = PO_ITEM_MASTER;
                }
            } catch (e) {
                window.PoStore.itemMaster = PO_ITEM_MASTER;
            }
        } else {
            window.PoStore.itemMaster = PO_ITEM_MASTER;
        }
    }

    // Next PO Number
    const count = window.PoStore.purchaseOrders.length;
    const poNum = `PO-2026-${String(count + 105).padStart(4, '0')}`;
    window.PoStore.activePo.poNumber = poNum;
    document.getElementById('poNumberInput').value = poNum;
    document.getElementById('poRefDisplay').textContent = poNum;
}

function initPoDates() {
    const today = new Date().toISOString().split('T')[0];
    const delDate = new Date();
    delDate.setDate(delDate.getDate() + 3);
    const delDateStr = delDate.toISOString().split('T')[0];

    document.getElementById('poOrderDate').value = today;
    document.getElementById('poExpectedDelivery').value = delDateStr;
    document.getElementById('poPaymentDate').value = today;

    window.PoStore.activePo.orderDate = today;
    window.PoStore.activePo.expectedDelivery = delDateStr;
    window.PoStore.activePo.paymentDate = today;
}

let poVendorComboSearchTerm = '';

function renderPoVendorDropdown() {
    const select = document.getElementById('poVendorSelect');
    const vendors = window.PoStore.vendors || [];

    if (select) {
        select.innerHTML = `
            <option value="">-- Choose Approved Vendor from Masterlist --</option>
            ${vendors.map(v => `
                <option value="${v.id || v.code}">[${v.code || v.id}] ${v.legalName || v.tradeName} (${v.category || 'General'})</option>
            `).join('')}
        `;
    }

    renderPoVendorComboboxList();
}

function renderPoVendorComboboxList() {
    const listEl = document.getElementById('poVendorComboList');
    if (!listEl) return;

    const vendors = window.PoStore.vendors || [];
    const q = (poVendorComboSearchTerm || '').toLowerCase().trim();

    const filtered = vendors.filter(v => {
        if (!q) return true;
        const code = (v.code || v.id || '').toLowerCase();
        const trade = (v.tradeName || '').toLowerCase();
        const legal = (v.legalName || '').toLowerCase();
        const cat = (v.category || '').toLowerCase();
        const contact = (v.contacts && v.contacts[0] ? v.contacts[0].name : '').toLowerCase();
        return code.includes(q) || trade.includes(q) || legal.includes(q) || cat.includes(q) || contact.includes(q);
    });

    const activeId = document.getElementById('poVendorSelect') ? document.getElementById('poVendorSelect').value : '';

    if (filtered.length === 0) {
        listEl.innerHTML = `<div class="po-vendor-combo-empty">No suppliers match "${escapeHtml(poVendorComboSearchTerm)}"</div>`;
        return;
    }

    let itemsHtml = '';
    if (activeId) {
        itemsHtml += `
            <div class="po-vendor-combo-item" onclick="selectPoVendorFromCombo('')" style="color: #ef4444; border-bottom: 1px dashed #f1f5f9;">
                <span style="display: flex; align-items: center; gap: 6px;">
                    <i class="ph ph-x-circle"></i> Clear Selected Vendor
                </span>
            </div>
        `;
    }

    itemsHtml += filtered.map(v => {
        const vId = v.id || v.code;
        const isSelected = vId === activeId;
        return `
            <div class="po-vendor-combo-item ${isSelected ? 'is-selected' : ''}" onclick="selectPoVendorFromCombo('${vId}')">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    <div style="width: 22px; height: 22px; border-radius: 5px; background: linear-gradient(135deg, #0284c7, #0d9488); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        ${(v.tradeName || v.legalName || 'V').substring(0, 1).toUpperCase()}
                    </div>
                    <div style="min-width: 0; line-height: 1.25;">
                        <div style="font-weight: 600; color: #1e293b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            ${escapeHtml(v.tradeName || v.legalName)}
                        </div>
                        <div style="font-size: 0.72rem; color: #64748b;">
                            <span style="font-family: monospace; font-weight: 700;">${escapeHtml(v.code || v.id)}</span> • ${escapeHtml(v.category || 'General')}
                        </div>
                    </div>
                </div>
                ${isSelected ? '<i class="ph ph-check" style="color: var(--po-primary); font-size: 14px; flex-shrink: 0;"></i>' : ''}
            </div>
        `;
    }).join('');

    listEl.innerHTML = itemsHtml;
}

function togglePoVendorCombobox(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (!dropdown || !trigger) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        trigger.classList.remove('is-active');
    } else {
        dropdown.classList.add('is-active');
        trigger.classList.add('is-active');
        const input = document.getElementById('poVendorSearchInput');
        if (input) {
            input.value = '';
            poVendorComboSearchTerm = '';
            renderPoVendorComboboxList();
            setTimeout(() => input.focus(), 50);
        }
    }
}

function filterPoVendorCombobox(query) {
    poVendorComboSearchTerm = query || '';
    renderPoVendorComboboxList();
}

function selectPoVendorFromCombo(vendorId) {
    const select = document.getElementById('poVendorSelect');
    if (select) {
        select.value = vendorId;
    }
    handlePoVendorSelect(vendorId);

    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (dropdown) dropdown.classList.remove('is-active');
    if (trigger) trigger.classList.remove('is-active');
}

function updatePoVendorSummaryCard(vendor) {
    const summaryBox = document.getElementById('poVendorSummaryBox');
    const triggerText = document.getElementById('poVendorComboTriggerText');

    if (!vendor) {
        if (summaryBox) summaryBox.style.display = 'none';
        if (triggerText) {
            triggerText.textContent = '-- Choose Approved Vendor from Masterlist --';
            triggerText.style.color = '#64748b';
            triggerText.style.fontWeight = 'normal';
        }
        return;
    }

    if (triggerText) {
        triggerText.innerHTML = `<strong>[${vendor.code || vendor.id}]</strong> ${escapeHtml(vendor.tradeName || vendor.legalName)}`;
        triggerText.style.color = '#0f172a';
        triggerText.style.fontWeight = '600';
    }

    if (summaryBox) {
        summaryBox.style.display = 'flex';
        const tradeName = vendor.tradeName || vendor.legalName || 'Supplier Partner';
        document.getElementById('poVendorTradeName').textContent = tradeName;
        document.getElementById('poVendorCategoryTag').textContent = vendor.category || 'General';
        document.getElementById('poVendorTinDisplay').textContent = vendor.tin || '000-000-000-000';
        document.getElementById('poVendorLocationDisplay').textContent = vendor.address || vendor.city || 'Metro Manila, Philippines';
        document.getElementById('poVendorAvatarText').textContent = tradeName.substring(0, 2).toUpperCase();
    }
}

function switchPoTab(tabId) {
    if (tabId === 'tab-po-audit') {
        openPoAuditDrawer();
        return;
    }

    const btnList = document.getElementById('tabBtnPoList');
    const btnBuilder = document.getElementById('tabBtnPoBuilder');
    const paneList = document.getElementById('pane-tab-po-list');
    const paneBuilder = document.getElementById('pane-tab-po-builder');

    [btnList, btnBuilder].forEach(b => b && b.classList.remove('active'));
    [paneList, paneBuilder].forEach(p => p && (p.style.display = 'none'));

    const pos = window.PoStore.purchaseOrders || [];
    const countBadge = document.getElementById('tabPoCount');
    if (countBadge) countBadge.textContent = pos.length;

    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge && typeof getProcurementAuditTrail === 'function') {
        const { poNum, rfqNum } = (typeof getCurrentPoAuditTarget === 'function') ? getCurrentPoAuditTarget() : {};
        const logs = getProcurementAuditTrail();
        if (poNum) {
            const scoped = logs.filter(l => l.refNumber === poNum || (rfqNum && l.refNumber === rfqNum));
            auditCountBadge.textContent = scoped.length;
        } else {
            auditCountBadge.textContent = logs.filter(l => l.module === 'PO').length;
        }
    }

    if (tabId === 'tab-po-list') {
        if (btnList) btnList.classList.add('active');
        if (paneList) paneList.style.display = 'flex';
        renderPoDirectory();
    } else if (tabId === 'tab-po-builder') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) paneBuilder.style.display = 'flex';
    }
}

/**
 * PO Sourcing Mode: Standard Vendor vs Direct Wet Market Cash Purchase
 */
function setPoMode(mode) {
    window.PoStore.activePo.poType = mode;
    const btnVendor = document.getElementById('btnModeVendor');
    const btnMarket = document.getElementById('btnModeMarket');
    const secVendor = document.getElementById('sectionVendorSource');
    const secMarket = document.getElementById('sectionMarketSource');
    const titleEl = document.getElementById('sourceCardTitle');
    const badgeEl = document.getElementById('sourceTypeBadge');
    const iconEl = document.getElementById('sourceCardIcon');

    if (mode === 'wet_market') {
        btnMarket.classList.add('active');
        btnVendor.classList.remove('active');
        secMarket.style.display = 'block';
        secVendor.style.display = 'none';
        titleEl.textContent = 'Wet Market Stall & Purchaser Sourcing';
        badgeEl.textContent = 'Wet Market Cash Run';
        badgeEl.style.background = '#fef3c7';
        badgeEl.style.color = '#92400e';
        iconEl.className = 'ph ph-basket';

        // Auto default to Cash / Petty Cash and Paid in Full
        document.getElementById('poPaymentStatus').value = 'Paid in Full / Cash Out';
        document.getElementById('poPaymentMethod').value = 'Cash / Petty Cash';
        document.getElementById('poFundSource').value = 'Branch Petty Cash Fund';
        handlePaymentStatusChange('Paid in Full / Cash Out');
        showToast('Switched to Direct Wet Market Cash Purchase mode', 'success');
    } else {
        btnVendor.classList.add('active');
        btnMarket.classList.remove('active');
        secVendor.style.display = 'block';
        secMarket.style.display = 'none';
        titleEl.textContent = 'Vendor & Partner Profile';
        badgeEl.textContent = 'Approved Vendor';
        badgeEl.style.background = 'var(--po-primary-subtle)';
        badgeEl.style.color = 'var(--po-primary-dark)';
        iconEl.className = 'ph ph-buildings';

        document.getElementById('poPaymentStatus').value = 'Unpaid / Credit';
        document.getElementById('poPaymentMethod').value = 'Trade Credit (Net 30/15)';
        handlePaymentStatusChange('Unpaid / Credit');
    }
}

function handlePoVendorSelect(vendorId) {
    if (!vendorId) {
        document.getElementById('poContactPerson').value = '';
        document.getElementById('poContactTitle').value = '';
        document.getElementById('poContactPhone').value = '';
        document.getElementById('poContactEmail').value = '';
        document.getElementById('poFullAddress').value = '';
        window.PoStore.activePo.vendorId = '';
        window.PoStore.activePo.vendorName = '';
        window.PoStore.activePo.vendorTradeName = '';
        updatePoVendorSummaryCard(null);
        renderPoVendorComboboxList();
        return;
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    document.getElementById('poContactPerson').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].name : '';
    document.getElementById('poContactTitle').value = (vendor.contacts && vendor.contacts[0]) ? (vendor.contacts[0].title || vendor.contacts[0].designation || 'Account Representative') : 'Account Manager';
    document.getElementById('poContactPhone').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].phone : '';
    document.getElementById('poContactEmail').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].email : (vendor.email || '');
    document.getElementById('poFullAddress').value = vendor.address || '';

    window.PoStore.activePo.vendorId = vendor.id || vendor.code;
    window.PoStore.activePo.vendorName = vendor.legalName || vendor.tradeName;
    window.PoStore.activePo.vendorTradeName = vendor.tradeName || vendor.legalName;

    updatePoVendorSummaryCard(vendor);
    renderPoVendorComboboxList();
    showToast(`✓ Selected vendor ${vendor.tradeName || vendor.legalName}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * FINANCIAL PAYMENT CALCULATIONS & STATUS HANDLERS
 * --------------------------------------------------------------------------
 */
function handlePaymentStatusChange(status) {
    const badge = document.getElementById('paymentStatusBadge');
    const gross = calculatePoGrossTotal();

    if (status === 'Paid in Full / Cash Out') {
        badge.className = 'pay-badge paid';
        badge.textContent = 'Paid in Full';
        document.getElementById('poAmountPaid').value = gross.toFixed(2);
    } else if (status === 'Partial Payment') {
        badge.className = 'pay-badge partial';
        badge.textContent = 'Partial Payment';
        document.getElementById('poAmountPaid').value = (gross / 2).toFixed(2);
    } else {
        badge.className = 'pay-badge unpaid';
        badge.textContent = 'Unpaid / Credit';
        document.getElementById('poAmountPaid').value = '0.00';
    }
    calculateBalanceDue();
}

function calculateBalanceDue() {
    const gross = calculatePoGrossTotal();
    const paid = parseFloat(document.getElementById('poAmountPaid').value) || 0;
    const balance = Math.max(0, gross - paid);
    document.getElementById('poBalanceDue').value = `₱${formatMoney(balance)}`;

    const badge = document.getElementById('paymentStatusBadge');
    if (paid >= gross && gross > 0) {
        badge.className = 'pay-badge paid';
        badge.textContent = 'Paid in Full';
    } else if (paid > 0 && paid < gross) {
        badge.className = 'pay-badge partial';
        badge.textContent = 'Partial Payment';
    } else {
        badge.className = 'pay-badge unpaid';
        badge.textContent = 'Unpaid / Credit';
    }
}

function calculatePoGrossTotal() {
    const items = window.PoStore.activePo.items || [];
    return items.reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
}

/**
 * --------------------------------------------------------------------------
 * LINE ITEMS MANAGEMENT
 * --------------------------------------------------------------------------
 */
function renderPoItemsTable() {
    const tbody = document.getElementById('poItemsTbody');
    const items = window.PoStore.activePo.items;
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" style="text-align: center; padding: 42px; color: var(--po-text-muted);">
                    <i class="ph ph-shopping-bag-open" style="font-size: 38px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--po-text-strong);">No Line Items in Purchase Order</div>
                    <div style="font-size: 0.8rem; margin-top: 4px;">Click <strong>"Select Master" (F2)</strong> or <strong>"Add Custom Row"</strong> to input items.</div>
                </td>
            </tr>
        `;
        updatePoTotals();
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        const lineTotal = (parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0);
        return `
            <tr>
                <td class="td-center" style="font-weight: 700; color: var(--po-text-muted);">${idx + 1}</td>
                <td>
                    <input type="text" value="${escapeHtml(it.name || '')}" placeholder="Item Description / Cut / Brand" onchange="updatePoItemField(${idx}, 'name', this.value)" required style="font-weight: 600;">
                </td>
                <td class="td-center">
                    <select onchange="updatePoItemField(${idx}, 'unit', this.value)" style="text-align: center;">
                        <option value="Kg" ${it.unit === 'Kg' ? 'selected' : ''}>Kg</option>
                        <option value="Liter" ${it.unit === 'Liter' ? 'selected' : ''}>Liter</option>
                        <option value="Pc" ${it.unit === 'Pc' ? 'selected' : ''}>Pc</option>
                        <option value="Tray" ${it.unit === 'Tray' ? 'selected' : ''}>Tray</option>
                        <option value="Box" ${it.unit === 'Box' ? 'selected' : ''}>Box</option>
                        <option value="Pack" ${it.unit === 'Pack' ? 'selected' : ''}>Pack</option>
                        <option value="Sack" ${it.unit === 'Sack' ? 'selected' : ''}>Sack</option>
                    </select>
                </td>
                <td class="td-num">
                    <input type="number" step="0.01" min="0.01" value="${it.quantity || 1}" onchange="updatePoItemField(${idx}, 'quantity', this.value)" style="text-align: right; font-weight: 700;">
                </td>
                <td class="td-num">
                    <input type="number" step="0.01" min="0" value="${it.unitPrice !== undefined ? it.unitPrice : 0}" onchange="updatePoItemField(${idx}, 'unitPrice', this.value)" style="text-align: right; font-weight: 700;">
                </td>
                <td class="td-num" style="font-weight: 800; color: var(--po-text-strong);">
                    ₱${formatMoney(lineTotal)}
                </td>
                <td class="td-center">
                    <button type="button" class="inv-table-filter-btn" style="color: var(--po-danger);" onclick="deletePoItemRow(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');

    updatePoTotals();
}

function updatePoItemField(idx, field, val) {
    const item = window.PoStore.activePo.items[idx];
    if (!item) return;

    if (field === 'quantity' || field === 'unitPrice') {
        item[field] = parseFloat(val) || 0;
    } else {
        item[field] = val;
    }

    renderPoItemsTable();
}

function addNewBlankPoItemRow() {
    window.PoStore.activePo.items.push({
        sku: 'WET-MKT',
        name: '',
        specs: '',
        category: 'Raw Ingredients',
        unit: 'Kg',
        quantity: 1.00,
        unitPrice: 0.00
    });
    renderPoItemsTable();
}

function deletePoItemRow(idx) {
    window.PoStore.activePo.items.splice(idx, 1);
    renderPoItemsTable();
}

function clearAllPoItems() {
    if (confirm('Clear all items from this Purchase Order?')) {
        window.PoStore.activePo.items = [];
        renderPoItemsTable();
    }
}

function updatePoTotals() {
    const items = window.PoStore.activePo.items;
    const totalLines = items.length;
    let totalUnits = 0;
    let totalGross = 0;

    items.forEach(it => {
        const q = parseFloat(it.quantity) || 0;
        const p = parseFloat(it.unitPrice) || 0;
        totalUnits += q;
        totalGross += (q * p);
    });

    document.getElementById('summaryPoLines').textContent = totalLines;
    document.getElementById('summaryPoUnits').textContent = totalUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryPoGross').textContent = `₱${formatMoney(totalGross)}`;
    document.getElementById('poItemCountBadge').textContent = `${totalLines} Line Item${totalLines === 1 ? '' : 's'}`;

    calculateBalanceDue();
    updatePoSummaryPayBadge();
}

/**
 * --------------------------------------------------------------------------
 * ITEM MASTER QUICK PICKER
 * --------------------------------------------------------------------------
 */
function openItemMasterQuickPicker() {
    const modal = document.getElementById('poItemMasterModal');
    if (!modal) return;
    renderPoItemMasterRows(window.PoStore.itemMaster);
    modal.classList.add('is-open');
}

function closeItemMasterQuickPicker() {
    const modal = document.getElementById('poItemMasterModal');
    if (modal) modal.classList.remove('is-open');
}

function filterPoItemMaster(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderPoItemMasterRows(window.PoStore.itemMaster);
        return;
    }
    const filtered = window.PoStore.itemMaster.filter(it => 
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.name && it.name.toLowerCase().includes(q)) ||
        (it.specs && it.specs.toLowerCase().includes(q))
    );
    renderPoItemMasterRows(filtered);
}

function renderPoItemMasterRows(items) {
    const tbody = document.getElementById('poItemMasterTbody');
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 20px; color: #94a3b8;">No products found</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(it => `
        <tr>
            <td style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">${escapeHtml(it.sku)}</td>
            <td>
                <div style="font-weight: 700;">${escapeHtml(it.name)}</div>
                <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(it.specs || '')}</div>
            </td>
            <td><span class="po-label-badge">${escapeHtml(it.category || 'Raw')}</span></td>
            <td style="text-align: center;">${escapeHtml(it.unit || 'Kg')}</td>
            <td style="text-align: right; font-weight: 700;">₱${formatMoney(it.defaultCost || 0)}</td>
            <td style="text-align: center;">
                <button type="button" class="po-btn po-btn-teal" style="padding: 4px 10px; font-size: 0.74rem;" onclick="addMasterItemToPoDirect('${it.sku}')">
                    + Add
                </button>
            </td>
        </tr>
    `).join('');
}

function addMasterItemToPoDirect(sku) {
    const master = window.PoStore.itemMaster.find(it => it.sku === sku);
    if (!master) return;

    window.PoStore.activePo.items.push({
        sku: master.sku,
        name: master.name,
        specs: master.specs || '',
        category: master.category || 'Raw Ingredients',
        unit: master.unit || 'Kg',
        quantity: 10.00,
        unitPrice: parseFloat(master.defaultCost) || 0
    });

    renderPoItemsTable();
    showToast(`✓ Added [${master.sku}] ${master.name}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * PO ISSUANCE & DIRECTORY TRACKING
 * --------------------------------------------------------------------------
 */
function issuePurchaseOrderSubmit() {
    const po = window.PoStore.activePo;
    const isMarket = po.poType === 'wet_market';

    const sourceName = isMarket ? 
        document.getElementById('poMarketStallName').value.trim() : 
        (po.vendorTradeName || po.vendorName);

    if (!sourceName) {
        showToast(isMarket ? 'Please enter the wet market stall name' : 'Please select an approved vendor', 'error');
        return;
    }

    if (po.items.length === 0) {
        showToast('Please add at least one line item to the purchase order', 'error');
        return;
    }

    // Collect inputs
    po.vendorName = sourceName;
    po.vendorTradeName = sourceName;
    po.vendorContactPerson = document.getElementById('poContactPerson').value.trim();
    po.vendorPhone = document.getElementById('poContactPhone').value.trim();
    po.vendorEmail = document.getElementById('poContactEmail').value.trim();
    po.vendorAddress = document.getElementById('poFullAddress').value.trim() || document.getElementById('poSourceLocation').value.trim();
    po.orderDate = document.getElementById('poOrderDate').value;
    po.expectedDelivery = document.getElementById('poExpectedDelivery').value;
    po.rfqReference = document.getElementById('poRfqReference').value.trim() || 'Direct Market Purchase';
    po.deliveryLocation = document.getElementById('poDeliveryLocation').value;
    po.specialNotes = document.getElementById('poSpecialNotes').value.trim();

    // Financial payment collection
    po.paymentStatus = document.getElementById('poPaymentStatus').value;
    po.paymentMethod = document.getElementById('poPaymentMethod').value;
    po.amountPaid = parseFloat(document.getElementById('poAmountPaid').value) || 0;
    po.balanceDue = Math.max(0, calculatePoGrossTotal() - po.amountPaid);
    po.paymentReference = document.getElementById('poPaymentRef').value.trim();
    po.paymentDate = document.getElementById('poPaymentDate').value;
    po.fundSource = document.getElementById('poFundSource').value;
    po.paymentRemarks = document.getElementById('poPaymentRemarks').value.trim();
    po.status = (po.paymentStatus === 'Paid in Full / Cash Out') ? 'Fully Received' : 'Approved / Issued';

    // Save to list
    const existingIdx = window.PoStore.purchaseOrders.findIndex(p => p.poNumber === po.poNumber);
    const grossTotal = calculatePoGrossTotal();

    if (existingIdx >= 0) {
        window.PoStore.purchaseOrders[existingIdx] = JSON.parse(JSON.stringify(po));
        logProcurementAudit(
            'PO',
            po.poNumber,
            'EDITED',
            'Purchase Order Updated',
            `Updated line items, payment terms, or delivery specs for ${po.vendorTradeName || po.vendorName}.`,
            [
                { label: 'Supplier / Stall', value: po.vendorTradeName || po.vendorName },
                { label: 'Line Items', value: `${(po.items || []).length} items` },
                { label: 'Grand Total', value: `₱${formatMoney(grossTotal)}` }
            ]
        );
    } else {
        window.PoStore.purchaseOrders.unshift(JSON.parse(JSON.stringify(po)));
        if (po.poType === 'wet_market') {
            logProcurementAudit(
                'PO',
                po.poNumber,
                'WET_MARKET_CREATED',
                'Direct Wet Market Cash Run Issued',
                `Custom PO created for wet market run at ${po.vendorName} without RFQ. Total: ₱${formatMoney(grossTotal)}.`,
                [
                    { label: 'Market Stall', value: po.vendorName },
                    { label: 'Designated Purchaser', value: po.vendorContactPerson || 'Kitchen Staff' },
                    { label: 'Line Items', value: `${(po.items || []).length} fresh goods` },
                    { label: 'Order Total', value: `₱${formatMoney(grossTotal)}` }
                ]
            );
        } else {
            logProcurementAudit(
                'PO',
                po.poNumber,
                'CREATED',
                'Purchase Order Created',
                `Standard vendor PO issued for ${po.vendorTradeName || po.vendorName}. Total: ₱${formatMoney(grossTotal)}.`,
                [
                    { label: 'Vendor Partner', value: po.vendorTradeName || po.vendorName },
                    { label: 'Line Items', value: `${(po.items || []).length} items` },
                    { label: 'Order Total', value: `₱${formatMoney(grossTotal)}` }
                ]
            );
        }
    }

    // Financial payment audit tracking if payment details recorded
    if (po.amountPaid > 0 || po.paymentStatus !== 'Unpaid / Credit') {
        logProcurementAudit(
            'PO',
            po.poNumber,
            'PAYMENT_RECORDED',
            'Payment Settlement Recorded',
            `Financial settlement recorded: ${po.paymentStatus}. Amount Paid: ₱${formatMoney(po.amountPaid)} via ${po.paymentMethod}.`,
            [
                { label: 'Payment Status', value: po.paymentStatus },
                { label: 'Amount Paid', value: `₱${formatMoney(po.amountPaid)}` },
                { label: 'Balance Due', value: `₱${formatMoney(po.balanceDue)}` },
                { label: 'Fund Source', value: po.fundSource },
                { label: 'Payment Method', value: po.paymentMethod },
                ...(po.paymentReference ? [{ label: 'Official Receipt #', value: po.paymentReference }] : [])
            ]
        );
    }

    localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

    // Asynchronously synchronize issued PO to database backend
    fetch('{{ route("purchase.api.orders.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
        },
        body: JSON.stringify({
            po_number: po.poNumber,
            po_type: po.poType,
            rfq_reference: po.rfqReference,
            vendor_id: po.vendorId,
            vendor_name: po.vendorName,
            vendor_trade_name: po.vendorTradeName,
            vendor_contact_person: po.vendorContactPerson,
            vendor_phone: po.vendorPhone,
            vendor_email: po.vendorEmail,
            vendor_address: po.vendorAddress,
            order_date: po.orderDate,
            expected_delivery: po.expectedDelivery,
            delivery_location: po.deliveryLocation,
            special_notes: po.specialNotes,
            payment_status: po.paymentStatus,
            payment_method: po.paymentMethod,
            amount_paid: po.amountPaid,
            balance_due: po.balanceDue,
            payment_reference: po.paymentReference,
            payment_date: po.paymentDate,
            fund_source: po.fundSource,
            payment_remarks: po.paymentRemarks,
            approval_notes: po.approvalNotes,
            approved_by: po.approvedBy,
            status: po.status,
            items: (po.items || []).map(i => ({
                sku: i.sku,
                name: i.name,
                specs: i.specs,
                category: i.category,
                unit: i.unit,
                quantity: i.quantity,
                unitPrice: i.unitPrice
            }))
        })
    }).then(r => r.json()).then(res => {
        if (res.success) {
            console.log('PO database synchronization confirmed:', res.data?.po?.po_number);
        }
    }).catch(e => console.warn('PO DB sync note:', e));

    showToast(`✓ Purchase Order ${po.poNumber} successfully issued!`, 'success');
    switchPoTab('tab-po-list');
}

/**
 * --------------------------------------------------------------------------
 * PO DIRECTORY COLUMN CONFIGURATION & PAGINATION SYSTEM
 * --------------------------------------------------------------------------
 */
const PO_DIRECTORY_COLUMNS = [
    { id: 'poNumber', label: 'PO Reference', default: true, lockVisible: true, defaultWidth: '135px', align: 'left' },
    { id: 'type', label: 'Sourcing Type', default: true, lockVisible: false, defaultWidth: '120px', align: 'center' },
    { id: 'vendor', label: 'Supplier / Stall', default: true, lockVisible: true, defaultWidth: '220px', align: 'left' },
    { id: 'orderDate', label: 'Date Issued', default: true, lockVisible: false, defaultWidth: '105px', align: 'left' },
    { id: 'expectedDelivery', label: 'Expected Delivery', default: true, lockVisible: false, defaultWidth: '130px', align: 'left' },
    { id: 'itemsCount', label: 'Line Items', default: true, lockVisible: false, defaultWidth: '85px', align: 'center' },
    { id: 'grossTotal', label: 'Total Amount', default: true, lockVisible: false, defaultWidth: '125px', align: 'right' },
    { id: 'paymentStatus', label: 'Payment Status', default: true, lockVisible: false, defaultWidth: '130px', align: 'center' },
    { id: 'status', label: 'Order Status', default: true, lockVisible: false, defaultWidth: '140px', align: 'center' },
    { id: 'actions', label: 'Actions', default: true, lockVisible: true, defaultWidth: '130px', align: 'center' }
];

let activePoColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_cols');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return PO_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
})();

let savedPoColWidths = (() => {
    try {
        const saved = localStorage.getItem('rms_po_directory_col_widths');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return {};
})();

function getPoColWidth(col) {
    return savedPoColWidths[col.id] || col.defaultWidth || '120px';
}

function initPoColumns() {
    renderPoTableHeader();
    renderPoColumnChecklist();
}

function renderPoTableHeader() {
    const theadRow = document.getElementById('poDirectoryTheadRow');
    if (!theadRow) return;

    let thHtml = '';
    PO_DIRECTORY_COLUMNS.forEach(col => {
        if (!activePoColIds.includes(col.id)) return;
        const w = getPoColWidth(col);
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');

        if (col.id === 'actions') {
            thHtml += `
                <th class="${alignClass}" data-col-id="actions" style="width: ${w}; position: relative; user-select: none;">
                    <div class="inv-header-action-wrapper">
                        <span>Action</span>
                        <button type="button" class="inv-table-filter-btn" id="btnPoActionColFilter" onclick="togglePoColumnConfigDropdown(event)" title="Column Display Filter" style="width: 22px; height: 22px; font-size: 11px; border-radius: 5px; color: #9333ea; background: rgba(168, 85, 247, 0.10); border: 1px solid rgba(168, 85, 247, 0.25);">
                            <i class="ph ph-funnel"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            thHtml += `
                <th class="${alignClass}" data-col-id="${col.id}" style="width: ${w}; position: relative; user-select: none;">
                    <span>${col.label}</span>
                    <div class="po-col-resizer" onmousedown="initPoColResize(event, '${col.id}')"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = thHtml;
    renderPoColumnChecklist();
}

function renderPoColumnChecklist() {
    const listEl = document.getElementById('poColChecklist');
    if (!listEl) return;

    listEl.innerHTML = PO_DIRECTORY_COLUMNS.map(col => {
        const isChecked = activePoColIds.includes(col.id);
        const isDisabled = col.lockVisible ? 'disabled' : '';
        return `
            <label class="inv-col-item-row" title="${col.label}" style="display: flex; align-items: center; justify-content: space-between; padding: 5px 8px; font-size: 12px; border-radius: 6px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                <span style="display: flex; align-items: center; gap: 7px; color: ${col.lockVisible ? '#94a3b8' : '#1e293b'}; font-weight: ${isChecked ? '600' : '400'};">
                    <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="togglePoColumnVisibility('${col.id}', this.checked)" style="accent-color: #9333ea; width: 14px; height: 14px; cursor: ${col.lockVisible ? 'default' : 'pointer'};">
                    ${col.label}
                </span>
                ${col.lockVisible ? '<span style="font-size: 10px; color: #94a3b8; font-weight: 600;">(Locked)</span>' : ''}
            </label>
        `;
    }).join('');

    const counter = document.getElementById('poColActiveCounter');
    if (counter) {
        counter.textContent = `${activePoColIds.length} of ${PO_DIRECTORY_COLUMNS.length} visible`;
    }
}

function togglePoColumnConfigDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('poColConfigDropdown');
    const btn = document.getElementById('btnPoColConfig');
    if (!dropdown) return;

    const isVisible = dropdown.style.display !== 'none';
    dropdown.style.display = isVisible ? 'none' : 'block';
    if (btn) btn.classList.toggle('active', !isVisible);
}

function togglePoColumnVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activePoColIds.includes(colId)) {
            activePoColIds.push(colId);
        }
    } else {
        activePoColIds = activePoColIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_po_directory_cols', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
}

function showAllPoColumns() {
    activePoColIds = PO_DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_po_directory_cols', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ All columns visible', 'success');
}

function resetPoColumnDefaults() {
    activePoColIds = PO_DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    localStorage.setItem('rms_po_directory_cols', JSON.stringify(activePoColIds));
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ Reset to default columns', 'success');
}

function resetPoColumns() {
    resetPoColumnDefaults();
}

function resetPoColumnWidths() {
    savedPoColWidths = {};
    localStorage.removeItem('rms_po_directory_col_widths');
    renderPoTableHeader();
    renderPoDirectory();
    showToast('✓ Column widths reset to defaults', 'success');
}

// Column Header Resizer Logic
let poResizingColId = null;
let poStartX = 0;
let poStartW = 0;

function initPoColResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    poResizingColId = colId;
    poStartX = e.pageX;

    const thEl = document.querySelector(`#poDirectoryTheadRow th[data-col-id="${colId}"]`);
    poStartW = thEl ? thEl.offsetWidth : 120;

    document.addEventListener('mousemove', handlePoColMouseMove);
    document.addEventListener('mouseup', handlePoColMouseUp);
}

function handlePoColMouseMove(e) {
    if (!poResizingColId) return;
    const diff = e.pageX - poStartX;
    const newWidth = Math.max(60, poStartW + diff);
    savedPoColWidths[poResizingColId] = `${newWidth}px`;

    const thEl = document.querySelector(`#poDirectoryTheadRow th[data-col-id="${poResizingColId}"]`);
    if (thEl) {
        thEl.style.width = `${newWidth}px`;
    }
}

function handlePoColMouseUp() {
    if (!poResizingColId) return;
    localStorage.setItem('rms_po_directory_col_widths', JSON.stringify(savedPoColWidths));
    poResizingColId = null;
    document.removeEventListener('mousemove', handlePoColMouseMove);
    document.removeEventListener('mouseup', handlePoColMouseUp);
}

function syncPoFilterPillActive(filterVal) {
    const bar = document.getElementById('poStatusFilterBar');
    if (!bar) return;
    bar.querySelectorAll('.po-filter-pill').forEach(p => {
        const val = p.getAttribute('data-filter');
        if (val === filterVal) {
            p.classList.add('active');
        } else {
            p.classList.remove('active');
        }
    });
}

function renderPoDirectory() {
    const tbody = document.getElementById('poDirectoryTbody');
    const pos = window.PoStore.purchaseOrders || [];
    if (!tbody) return;

    // Filter
    const filtered = pos.filter(p => {
        const matchesStatus = (activePoStatusFilter === 'all') || (p.status === activePoStatusFilter);
        const matchesType = (activePoTypeFilter === 'all') || (p.poType === activePoTypeFilter);
        const q = (poSearchTerm || '').toLowerCase().trim();
        const matchesSearch = !q || 
            (p.poNumber && p.poNumber.toLowerCase().includes(q)) ||
            (p.vendorName && p.vendorName.toLowerCase().includes(q)) ||
            (p.rfqReference && p.rfqReference.toLowerCase().includes(q)) ||
            (p.vendorContactPerson && p.vendorContactPerson.toLowerCase().includes(q));
        return matchesStatus && matchesType && matchesSearch;
    });

    // KPI Counts
    const total = pos.length;
    const pending = pos.filter(p => p.status === 'Approved / Issued').length;
    const received = pos.filter(p => p.status === 'Fully Received').length;
    const paid = pos.filter(p => p.paymentStatus === 'Paid in Full / Cash Out').length;

    document.getElementById('kpiPoTotal').textContent = total;
    document.getElementById('kpiPoPending').textContent = pending;
    document.getElementById('kpiPoReceived').textContent = received;
    document.getElementById('kpiPoPaid').textContent = paid;
    document.getElementById('tabPoCount').textContent = total;

    document.getElementById('countPoAll').textContent = total;
    document.getElementById('countPoPending').textContent = pending;
    document.getElementById('countPoReceived').textContent = received;
    document.getElementById('countPoPaid').textContent = paid;

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${activePoColIds.length || 10}" style="text-align: center; padding: 36px; color: var(--po-text-muted);">No Purchase Orders found matching the filter criteria.</td></tr>`;
        document.getElementById('poPaginationStart').textContent = '0';
        document.getElementById('poPaginationEnd').textContent = '0';
        document.getElementById('poPaginationTotal').textContent = '0';
        renderPoPaginationControls(0);
        return;
    }

    // Pagination Calculation
    const totalFiltered = filtered.length;
    const totalPages = Math.max(1, Math.ceil(totalFiltered / poPageSize));
    if (poCurrentPage > totalPages) poCurrentPage = totalPages;
    if (poCurrentPage < 1) poCurrentPage = 1;

    const startIndex = (poCurrentPage - 1) * poPageSize;
    const endIndex = Math.min(startIndex + poPageSize, totalFiltered);
    const pageItems = filtered.slice(startIndex, endIndex);

    document.getElementById('poPaginationStart').textContent = (startIndex + 1).toString();
    document.getElementById('poPaginationEnd').textContent = endIndex.toString();
    document.getElementById('poPaginationTotal').textContent = totalFiltered.toString();
    renderPoPaginationControls(totalPages);

    tbody.innerHTML = pageItems.map(p => {
        const gross = (p.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        const isMarket = p.poType === 'wet_market';
        let payBadgeClass = 'unpaid';
        if (p.paymentStatus === 'Paid in Full / Cash Out') payBadgeClass = 'paid';
        else if (p.paymentStatus === 'Partial Payment') payBadgeClass = 'partial';

        let poStatusClass = 'draft';
        if (p.status === 'Approved / Issued') poStatusClass = 'approved';
        else if (p.status === 'Fully Received') poStatusClass = 'received';

        let cellsHtml = '';
        PO_DIRECTORY_COLUMNS.forEach(col => {
            if (!activePoColIds.includes(col.id)) return;

            if (col.id === 'poNumber') {
                cellsHtml += `
                    <td style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">
                        <a href="javascript:void(0)" onclick="editPoFromDirectory('${p.poNumber}')" style="color: inherit; text-decoration: underline;">
                            ${escapeHtml(p.poNumber)}
                        </a>
                    </td>
                `;
            } else if (col.id === 'type') {
                cellsHtml += `
                    <td class="td-center">
                        <span class="po-type-badge ${isMarket ? 'wet-market' : 'vendor'}">
                            ${isMarket ? '<i class="ph ph-basket"></i> Wet Market' : '<i class="ph ph-buildings"></i> Vendor'}
                        </span>
                    </td>
                `;
            } else if (col.id === 'vendor') {
                cellsHtml += `
                    <td>
                        <div style="font-weight: 700; color: var(--po-text-strong);">${escapeHtml(p.vendorTradeName || p.vendorName || 'Market Stall')}</div>
                        <div style="font-size: 0.74rem; color: var(--po-text-muted);">
                            ${escapeHtml(p.vendorContactPerson || 'Purchaser')} • ${escapeHtml(p.vendorPhone || '')}
                        </div>
                    </td>
                `;
            } else if (col.id === 'orderDate') {
                cellsHtml += `<td>${formatDateDisplay(p.orderDate)}</td>`;
            } else if (col.id === 'expectedDelivery') {
                cellsHtml += `<td><span style="font-weight: 600; color: var(--po-teal-dark);">${formatDateDisplay(p.expectedDelivery)}</span></td>`;
            } else if (col.id === 'itemsCount') {
                cellsHtml += `<td class="td-center"><span class="po-tab-count">${(p.items || []).length}</span></td>`;
            } else if (col.id === 'grossTotal') {
                cellsHtml += `<td class="td-num" style="font-weight: 800;">₱${formatMoney(gross)}</td>`;
            } else if (col.id === 'paymentStatus') {
                cellsHtml += `
                    <td class="td-center">
                        <span class="pay-badge ${payBadgeClass}" style="cursor: pointer;" onclick="openPaymentSettlementModal('${p.poNumber}')" title="View Financial Settlement">
                            ${escapeHtml(p.paymentStatus || 'Unpaid')}
                        </span>
                    </td>
                `;
            } else if (col.id === 'status') {
                cellsHtml += `
                    <td class="td-center">
                        <span class="po-status-badge ${poStatusClass}">
                            <i class="ph ph-dot"></i> ${escapeHtml(p.status)}
                        </span>
                    </td>
                `;
            } else if (col.id === 'actions') {
                cellsHtml += `
                    <td class="td-center">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                            <button type="button" class="inv-table-filter-btn" onclick="openPaymentSettlementModal('${p.poNumber}')" title="Financial Settlement & Payments" style="color: #0284c7;">
                                <i class="ph ph-bank"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="openPoSingleAuditModal('${p.poNumber}')" title="View Audit Trail for this PO">
                                <i class="ph ph-clock-counter-clockwise"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="previewPoFromDirectory('${p.poNumber}')" title="Preview Printable PO">
                                <i class="ph ph-eye"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" onclick="editPoFromDirectory('${p.poNumber}')" title="Edit Purchase Order">
                                <i class="ph ph-pencil-simple"></i>
                            </button>
                            <button type="button" class="inv-table-filter-btn" style="color: var(--po-danger);" onclick="deletePo('${p.poNumber}')" title="Delete PO">
                                <i class="ph ph-trash"></i>
                            </button>
                        </div>
                    </td>
                `;
            }
        });

        return `<tr>${cellsHtml}</tr>`;
    }).join('');
}

function renderPoPaginationControls(totalPages) {
    const container = document.getElementById('poPaginationButtons');
    if (!container) return;

    if (totalPages <= 1) {
        container.innerHTML = '';
        return;
    }

    let html = `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 2px 8px; height: 26px; font-size: 11px;" ${poCurrentPage === 1 ? 'disabled' : ''} onclick="changePoPage(${poCurrentPage - 1})">
            <i class="ph ph-caret-left"></i> Prev
        </button>
    `;

    for (let p = 1; p <= totalPages; p++) {
        if (p === 1 || p === totalPages || (p >= poCurrentPage - 1 && p <= poCurrentPage + 1)) {
            const isActive = (p === poCurrentPage);
            html += `
                <button type="button" class="hr-btn hr-btn-sm ${isActive ? 'hr-btn-primary' : 'hr-btn-secondary'}" style="padding: 2px 8px; height: 26px; font-size: 11px; min-width: 26px;" onclick="changePoPage(${p})">
                    ${p}
                </button>
            `;
        } else if (p === poCurrentPage - 2 || p === poCurrentPage + 2) {
            html += `<span style="padding: 0 4px; color: #94a3b8; font-size: 11px;">...</span>`;
        }
    }

    html += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 2px 8px; height: 26px; font-size: 11px;" ${poCurrentPage === totalPages ? 'disabled' : ''} onclick="changePoPage(${poCurrentPage + 1})">
            Next <i class="ph ph-caret-right"></i>
        </button>
    `;

    container.innerHTML = html;
}

function changePoPage(page) {
    poCurrentPage = page;
    renderPoDirectory();
}

function changePoPageSize(size) {
    poPageSize = parseInt(size, 10) || 10;
    poCurrentPage = 1;
    renderPoDirectory();
}

function filterPoStatus(status, btn) {
    activePoStatusFilter = status;
    activePoTypeFilter = 'all';
    poCurrentPage = 1;
    if (btn) {
        syncPoFilterPillActive(btn.getAttribute('data-filter') || 'all');
    } else {
        syncPoFilterPillActive(status === 'all' ? 'all' : (status === 'Approved / Issued' ? 'Pending' : 'Received'));
    }
    renderPoDirectory();
}

function filterPoType(type, btn) {
    activePoTypeFilter = type;
    poCurrentPage = 1;
    if (btn) {
        syncPoFilterPillActive(btn.getAttribute('data-filter'));
    } else {
        syncPoFilterPillActive(type === 'vendor' ? 'Standard' : 'WetMarket');
    }
    renderPoDirectory();
}

function filterPoPayment(payStatus, btn) {
    activePoStatusFilter = 'all';
    activePoTypeFilter = 'all';
    poCurrentPage = 1;
    if (btn) {
        syncPoFilterPillActive(btn.getAttribute('data-filter'));
    } else {
        syncPoFilterPillActive('Paid');
    }
    renderPoDirectory();
}

function handlePoSearch(q) {
    poSearchTerm = q;
    poCurrentPage = 1;
    renderPoDirectory();
}

function startNewPoFromDirectory() {
    resetPoForm();
    switchPoTab('tab-po-builder');
}

function editPoFromDirectory(poNumber) {
    const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;

    window.PoStore.activePo = JSON.parse(JSON.stringify(po));

    // Update Form
    document.getElementById('poNumberInput').value = po.poNumber;
    document.getElementById('poRefDisplay').textContent = po.poNumber;
    if (document.getElementById('poOrderTitle')) {
        document.getElementById('poOrderTitle').value = po.orderTitle || po.purpose || '';
    }

    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) {
        if (po.rfqReference) {
            rfqBadge.style.display = 'inline-flex';
            rfqBadge.textContent = `Tender: ${po.rfqReference}`;
        } else {
            rfqBadge.style.display = 'none';
        }
    }

    setPoMode(po.poType || 'vendor');

    if (po.poType === 'wet_market') {
        document.getElementById('poMarketStallName').value = po.vendorName || '';
    } else {
        document.getElementById('poVendorSelect').value = po.vendorId || '';
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === po.vendorId || v.code === po.vendorId)) || (po.vendorName ? {
        code: po.vendorId || 'VND',
        tradeName: po.vendorTradeName || po.vendorName,
        legalName: po.vendorName,
        category: 'Vendor',
        address: po.vendorAddress
    } : null);
    updatePoVendorSummaryCard(vendor);

    document.getElementById('poContactPerson').value = po.vendorContactPerson || '';
    document.getElementById('poContactPhone').value = po.vendorPhone || '';
    document.getElementById('poContactEmail').value = po.vendorEmail || '';
    document.getElementById('poFullAddress').value = po.vendorAddress || '';
    document.getElementById('poOrderDate').value = po.orderDate || '';
    document.getElementById('poExpectedDelivery').value = po.expectedDelivery || '';
    document.getElementById('poRfqReference').value = po.rfqReference || '';
    document.getElementById('poDeliveryLocation').value = po.deliveryLocation || '';
    document.getElementById('poSpecialNotes').value = po.specialNotes || '';

    // Payment fields
    document.getElementById('poPaymentStatus').value = po.paymentStatus || 'Unpaid / Credit';
    document.getElementById('poPaymentMethod').value = po.paymentMethod || 'Cash / Petty Cash';
    document.getElementById('poAmountPaid').value = parseFloat(po.amountPaid || 0).toFixed(2);
    document.getElementById('poPaymentRef').value = po.paymentReference || '';
    document.getElementById('poPaymentDate').value = po.paymentDate || '';
    document.getElementById('poFundSource').value = po.fundSource || 'Branch Petty Cash Fund';
    document.getElementById('poPaymentRemarks').value = po.paymentRemarks || '';

    renderPoItemsTable();
    handlePaymentStatusChange(po.paymentStatus || 'Unpaid / Credit');

    document.getElementById('tabPoBuilderBadge').textContent = `Editing ${po.poNumber}`;
    switchPoTab('tab-po-builder');
    showToast(`Loaded ${po.poNumber} into Purchase Order Builder`, 'success');
}

function previewPoFromDirectory(poNumber) {
    const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
    if (!po) return;
    window.PoStore.activePo = JSON.parse(JSON.stringify(po));
    openPoDocumentPreview();
}

function deletePo(poNumber) {
    if (confirm(`Are you sure you want to delete purchase order ${poNumber}?`)) {
        const deletedPo = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === poNumber);
        window.PoStore.purchaseOrders = window.PoStore.purchaseOrders.filter(p => p.poNumber !== poNumber);
        localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));

        logProcurementAudit(
            'PO',
            poNumber,
            'DELETED',
            'Purchase Order Cancelled / Deleted',
            `Purchase Order ${poNumber} was archived and removed from registry.`,
            [{ label: 'Supplier / Stall', value: deletedPo ? (deletedPo.vendorTradeName || deletedPo.vendorName) : 'N/A' }]
        );

        renderPoDirectory();
        showToast(`Purchase Order ${poNumber} deleted`, 'success');
    }
}

function resetPoForm() {
    const count = (window.PoStore.purchaseOrders || []).length;
    const poNum = `PO-2026-${String(count + 105).padStart(4, '0')}`;
    window.PoStore.activePo = {
        poNumber: poNum,
        poType: 'vendor',
        rfqReference: '',
        orderTitle: '',
        purpose: '',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorContactPerson: '',
        vendorPhone: '',
        vendorEmail: '',
        vendorAddress: '',
        orderDate: new Date().toISOString().split('T')[0],
        expectedDelivery: '',
        deliveryLocation: 'Central Commissary - Receiving Dock A',
        specialNotes: '',
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0.00,
        balanceDue: 0.00,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Branch Petty Cash Fund',
        paymentRemarks: '',
        approvalNotes: '',
        approvedBy: 'Procurement Specialist',
        status: 'Draft PO',
        items: []
    };

    document.getElementById('poNumberInput').value = poNum;
    document.getElementById('poRefDisplay').textContent = poNum;
    if (document.getElementById('poOrderTitle')) document.getElementById('poOrderTitle').value = '';
    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) { rfqBadge.style.display = 'none'; rfqBadge.textContent = ''; }
    document.getElementById('poVendorSelect').value = '';
    updatePoVendorSummaryCard(null);
    renderPoVendorComboboxList();

    document.getElementById('poMarketStallName').value = '';
    document.getElementById('poContactPerson').value = '';
    document.getElementById('poContactPhone').value = '';
    document.getElementById('poContactEmail').value = '';
    document.getElementById('poFullAddress').value = '';
    document.getElementById('poRfqReference').value = '';
    document.getElementById('poPaymentStatus').value = 'Unpaid / Credit';
    document.getElementById('poAmountPaid').value = '0.00';
    document.getElementById('poPaymentRef').value = '';
    document.getElementById('tabPoBuilderBadge').textContent = 'New PO';

    setPoMode('vendor');
    initPoDates();
    renderPoItemsTable();
    showToast('PO Form Reset', 'success');
}

/**
 * --------------------------------------------------------------------------
 * FORMAL PRINTABLE PO DOCUMENT PREVIEW
 * --------------------------------------------------------------------------
 */
function openPoDocumentPreview() {
    const po = window.PoStore.activePo;
    const isMarket = po.poType === 'wet_market';
    const gross = calculatePoGrossTotal();
    const paid = parseFloat(document.getElementById('poAmountPaid').value) || (po.amountPaid || 0);
    const balance = Math.max(0, gross - paid);

    document.getElementById('docPoNumber').textContent = po.poNumber;
    document.getElementById('docPoDate').textContent = formatDateDisplay(document.getElementById('poOrderDate').value || po.orderDate);
    document.getElementById('docPoDeliveryDue').textContent = formatDateDisplay(document.getElementById('poExpectedDelivery').value || po.expectedDelivery);

    const vendorTitle = isMarket ? 
        (document.getElementById('poMarketStallName').value || po.vendorName || 'Wet Market Stall') : 
        (po.vendorTradeName || po.vendorName || 'Vendor Partner');

    document.getElementById('docPoVendor').textContent = vendorTitle;
    document.getElementById('docPoVendorDetails').innerHTML = `
        <strong>Attn:</strong> ${escapeHtml(document.getElementById('poContactPerson').value || po.vendorContactPerson || 'Representative')}<br>
        <strong>Phone:</strong> ${escapeHtml(document.getElementById('poContactPhone').value || po.vendorPhone || 'N/A')}<br>
        <strong>Address / Market:</strong> ${escapeHtml(document.getElementById('poFullAddress').value || po.vendorAddress || 'Metro Manila')}
    `;

    document.getElementById('docPoDeliveryDest').textContent = document.getElementById('poDeliveryLocation').value || po.deliveryLocation;
    document.getElementById('docPoPaymentStatus').textContent = document.getElementById('poPaymentStatus').value || po.paymentStatus;
    document.getElementById('docPoPaymentMethod').textContent = document.getElementById('poPaymentMethod').value || po.paymentMethod;
    document.getElementById('docPoPaymentRef').textContent = document.getElementById('poPaymentRef').value || po.paymentReference || 'N/A';

    // Populate table
    const tbody = document.getElementById('docPoItemsTbody');
    if (tbody) {
        if (po.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 20px; color: #94a3b8;">No items in purchase order</td></tr>`;
        } else {
            tbody.innerHTML = po.items.map((it, idx) => {
                const total = (parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0);
                return `
                    <tr>
                        <td style="text-align: center; font-weight: 700;">${idx + 1}</td>
                        <td style="font-family: monospace; font-weight: 700;">${escapeHtml(it.sku || '--')}</td>
                        <td>
                            <div style="font-weight: 700;">${escapeHtml(it.name || 'Custom Product')}</div>
                            ${it.specs ? `<div style="font-size: 0.76rem; color: #475569;">${escapeHtml(it.specs)}</div>` : ''}
                        </td>
                        <td style="text-align: center;">${escapeHtml(it.unit || 'Kg')}</td>
                        <td class="num" style="font-weight: 700;">${parseFloat(it.quantity || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                        <td class="num">₱${formatMoney(it.unitPrice || 0)}</td>
                        <td class="num" style="font-weight: 800;">₱${formatMoney(total)}</td>
                    </tr>
                `;
            }).join('');
        }
    }

    document.getElementById('docPoGross').textContent = `₱${formatMoney(gross)}`;
    document.getElementById('docPoPaid').textContent = `₱${formatMoney(paid)}`;
    document.getElementById('docPoBalance').textContent = `₱${formatMoney(balance)}`;

    const modal = document.getElementById('poDocumentModal');
    if (modal) modal.classList.add('is-open');
}

function closePoDocumentPreview() {
    const modal = document.getElementById('poDocumentModal');
    if (modal) modal.classList.remove('is-open');
}

function closeAllPoModals() {
    closePoDocumentPreview();
    closeItemMasterQuickPicker();
}

/**
 * --------------------------------------------------------------------------
 * UTILITIES
 * --------------------------------------------------------------------------
 */
function formatMoney(amount) {
    const val = Number(amount) || 0;
    return val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatDateDisplay(isoDate) {
    if (!isoDate) return '--';
    try {
        const d = new Date(isoDate);
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    } catch (e) {
        return isoDate;
    }
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function showToast(message, type = 'success') {
    const container = document.getElementById('poToastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `rfq-toast is-${type}`;
    toast.innerHTML = `
        <i class="ph ${type === 'success' ? 'ph-check-circle' : 'ph-warning-circle'}" style="font-size: 20px;"></i>
        <span>${escapeHtml(message)}</span>
    `;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(10px)';
        toast.style.transition = 'all 0.25s ease';
        setTimeout(() => toast.remove(), 250);
    }, 3200);
}
/**
 * --------------------------------------------------------------------------
 * PROCUREMENT AUDIT TRAIL ENGINE FOR PURCHASE ORDERS
 * --------------------------------------------------------------------------
 */
let activePoAuditActionFilter = 'ALL';
let activePoAuditModuleFilter = 'ALL';
let poAuditSearchTerm = '';

function getProcurementAuditTrail() {
    let logs = [];
    try {
        const stored = localStorage.getItem('rms_procurement_audit_trail');
        if (stored) logs = JSON.parse(stored);
    } catch (e) {
        logs = [];
    }

    if (!logs || logs.length === 0) {
        logs = seedProcurementAuditTrail();
        localStorage.setItem('rms_procurement_audit_trail', JSON.stringify(logs));
    }
    return logs;
}

function seedProcurementAuditTrail() {
    return [
        {
            id: 'AUD-2026-1006',
            timestamp: new Date(Date.now() - 3600000 * 2).toISOString(),
            formattedDate: 'Sep 30, 2026 • 10:45 AM',
            module: 'PO',
            refNumber: 'PO-2026-0103',
            action: 'PAYMENT_RECORDED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Payment Settlement Recorded',
            description: 'Disbursed ₱18,750.00 cash payment for direct wet market procurement run.',
            details: [
                { label: 'Payment Status', value: 'Paid in Full / Cash Out' },
                { label: 'Disbursement Method', value: 'Cash / Petty Cash' },
                { label: 'Fund Source', value: 'Branch Petty Cash Box' },
                { label: 'Official Receipt #', value: 'OR-BKT-88412' }
            ]
        },
        {
            id: 'AUD-2026-1005',
            timestamp: new Date(Date.now() - 3600000 * 4).toISOString(),
            formattedDate: 'Sep 30, 2026 • 08:30 AM',
            module: 'PO',
            refNumber: 'PO-2026-0103',
            action: 'WET_MARKET_CREATED',
            actor: 'Roberto Cruz',
            role: 'Store Supervisor',
            title: 'Direct Wet Market Cash Run Issued',
            description: 'Custom PO created for wet market run at Balintawak Wholesale Market Stall #14 without RFQ.',
            details: [
                { label: 'Market Stall', value: 'Aling Nena Fresh Produce (Stall #14)' },
                { label: 'Purchaser', value: 'Roberto Cruz (Kitchen Lead)' },
                { label: 'Order Total', value: '₱18,750.00' }
            ]
        },
        {
            id: 'AUD-2026-1004',
            timestamp: new Date(Date.now() - 3600000 * 18).toISOString(),
            formattedDate: 'Sep 29, 2026 • 04:15 PM',
            module: 'PO',
            refNumber: 'PO-2026-0101',
            action: 'PO_TRANSFERRED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Purchase Order Issued from RFQ',
            description: 'Issued formal PO-2026-0101 transferred from approved quotation RFQ-2026-0041.',
            details: [
                { label: 'Supplier', value: 'Golden Acre Produce Corp.' },
                { label: 'Awarded Total', value: '₱48,600.00' },
                { label: 'Payment Terms', value: 'Net 15 Days' }
            ]
        },
        {
            id: 'AUD-2026-1003',
            timestamp: new Date(Date.now() - 3600000 * 19).toISOString(),
            formattedDate: 'Sep 29, 2026 • 03:50 PM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0041',
            action: 'APPROVED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Manager Approval Granted',
            description: 'RFQ-2026-0041 approved after bidding review. Authorized for immediate purchase order issuance.',
            details: [
                { label: 'Supplier', value: 'Golden Acre Produce Corp.' },
                { label: 'Evaluation', value: 'Lowest compliant tender meeting commissary freshness standards.' }
            ]
        }
    ];
}

function logProcurementAudit(module, refNumber, action, title, description, details = [], metadata = {}) {
    const logs = getProcurementAuditTrail();
    const now = new Date();
    const formattedDate = now.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) + 
        ' • ' + now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

    const entry = {
        id: `AUD-${now.getFullYear()}-${String(logs.length + 1001)}`,
        timestamp: now.toISOString(),
        formattedDate: formattedDate,
        module: module,
        refNumber: refNumber,
        action: action,
        actor: '{{ auth()->user()->name ?? "Dorothy Diaz" }}',
        role: 'Procurement Manager',
        title: title,
        description: description,
        details: details,
        metadata: metadata
    };

    logs.unshift(entry);
    localStorage.setItem('rms_procurement_audit_trail', JSON.stringify(logs));

    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge) {
        auditCountBadge.textContent = logs.filter(l => l.module === 'PO' || l.action === 'PO_TRANSFERRED').length || logs.length;
    }

    return entry;
}

let poAuditScopeMode = 'CURRENT';

function getCurrentPoAuditTarget() {
    const active = window.PoStore.activePo || {};
    const poNum = (active.poNumber || document.getElementById('poRefDisplay')?.textContent || '').trim();
    const rfqNum = (active.rfqReference || document.getElementById('poRfqReference')?.value || '').trim();
    return { poNum, rfqNum };
}

function openPoAuditDrawer() {
    const drawer = document.getElementById('poAuditDrawer');
    const overlay = document.getElementById('poAuditDrawerOverlay');
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    renderPoAuditTrail();
}

function closePoAuditDrawer() {
    const drawer = document.getElementById('poAuditDrawer');
    const overlay = document.getElementById('poAuditDrawerOverlay');
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

function setPoAuditScope(mode) {
    poAuditScopeMode = mode;
    const btnCurrent = document.getElementById('btnPoAuditScopeCurrent');
    const btnAll = document.getElementById('btnPoAuditScopeAll');
    if (btnCurrent) btnCurrent.classList.toggle('active', mode === 'CURRENT');
    if (btnAll) btnAll.classList.toggle('active', mode === 'ALL');
    renderPoAuditTrail();
}

function renderPoAuditTrail() {
    const container = document.getElementById('poAuditTimeline');
    if (!container) return;

    const allLogs = getProcurementAuditTrail();
    const { poNum, rfqNum } = getCurrentPoAuditTarget();

    // Update target badges in drawer
    const badgePo = document.getElementById('poAuditTargetRefBadge');
    if (badgePo) badgePo.textContent = poNum || 'Current PO';

    const badgeRfq = document.getElementById('poAuditTargetRfqBadge');
    if (badgeRfq) {
        if (rfqNum && rfqNum !== 'None' && rfqNum !== 'Direct Market Purchase') {
            badgeRfq.textContent = rfqNum;
            badgeRfq.style.display = 'inline-block';
        } else {
            badgeRfq.style.display = 'none';
        }
    }

    // Process optimization: Index ONLY active PO / RFQ when in CURRENT mode
    let targetLogs = allLogs;
    if (poAuditScopeMode === 'CURRENT' && (poNum || rfqNum)) {
        targetLogs = allLogs.filter(log => {
            const ref = (log.refNumber || '').trim();
            if (poNum && (ref === poNum || (log.description && log.description.includes(poNum)))) {
                return true;
            }
            if (rfqNum && (ref === rfqNum || (log.description && log.description.includes(rfqNum)))) {
                return true;
            }
            return false;
        });

        // Ensure newly drafted PO has baseline entry
        if (targetLogs.length === 0 && poNum) {
            const active = window.PoStore.activePo || {};
            targetLogs = [{
                id: `AUD-INIT-${poNum}`,
                timestamp: new Date().toISOString(),
                formattedDate: 'Just now',
                module: 'PO',
                refNumber: poNum,
                action: 'CREATED',
                actor: '{{ auth()->user()->name ?? "Dorothy Diaz" }}',
                role: 'Procurement Specialist',
                title: 'Purchase Order Initialized',
                description: `Draft order ${poNum} initialized for ${active.vendorTradeName || active.vendorName || 'selected supplier'}.`,
                details: [
                    { label: 'Order Ref', value: poNum },
                    { label: 'Sourcing Type', value: active.poType === 'wet_market' ? 'Direct Wet Market' : 'Contracted Vendor' },
                    { label: 'Originating RFQ', value: active.rfqReference || 'None (Direct Order)' }
                ]
            }];
        }
    }

    // Update scoped event counter in drawer header
    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = targetLogs.length;

    const q = (poAuditSearchTerm || '').toLowerCase().trim();

    const filtered = targetLogs.filter(log => {
        const matchesModule = (activePoAuditModuleFilter === 'ALL') || (log.module === activePoAuditModuleFilter);
        const matchesAction = (activePoAuditActionFilter === 'ALL') || (log.action === activePoAuditActionFilter);
        const matchesSearch = !q ||
            (log.refNumber && log.refNumber.toLowerCase().includes(q)) ||
            (log.title && log.title.toLowerCase().includes(q)) ||
            (log.description && log.description.toLowerCase().includes(q)) ||
            (log.actor && log.actor.toLowerCase().includes(q));

        return matchesModule && matchesAction && matchesSearch;
    });

    // Update KPI numbers calculated ONLY from the target set
    const kpiTotal = document.getElementById('poAuditKpiTotal');
    const kpiWetMarket = document.getElementById('poAuditKpiWetMarket');
    const kpiPayments = document.getElementById('poAuditKpiPayments');
    const kpiTransfers = document.getElementById('poAuditKpiTransfers');

    if (kpiTotal) kpiTotal.textContent = targetLogs.length;
    if (kpiWetMarket) kpiWetMarket.textContent = targetLogs.filter(l => l.action === 'WET_MARKET_CREATED').length;
    if (kpiPayments) kpiPayments.textContent = targetLogs.filter(l => l.action === 'PAYMENT_RECORDED').length;
    if (kpiTransfers) kpiTransfers.textContent = targetLogs.filter(l => l.action === 'PO_TRANSFERRED' || l.action === 'CREATED').length;

    if (filtered.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px solid var(--po-border-subtle); border-radius: 10px; color: var(--po-text-muted);">
                <i class="ph ph-clock-countdown" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 600; color: #475569; margin-bottom: 4px;">No Activity Records Found</div>
                <div style="font-size: 12px;">No logged activities match the current filter criteria for ${poNum || 'this order'}.</div>
            </div>
        `;
        return;
    }

    container.innerHTML = filtered.map(log => {
        let nodeClass = 'is-created';
        let nodeIcon = 'ph-plus-circle';
        let badgeClass = 'badge-created';

        if (log.action === 'APPROVED') {
            nodeClass = 'is-approved';
            nodeIcon = 'ph-seal-check';
            badgeClass = 'badge-approved';
        } else if (log.action === 'EDITED') {
            nodeClass = 'is-edited';
            nodeIcon = 'ph-pencil-simple';
            badgeClass = 'badge-edited';
        } else if (log.action === 'PO_TRANSFERRED' || log.action === 'WET_MARKET_CREATED') {
            nodeClass = 'is-po';
            nodeIcon = 'ph-receipt';
            badgeClass = 'badge-po';
        } else if (log.action === 'EMAIL_SENT') {
            nodeClass = 'is-email';
            nodeIcon = 'ph-paper-plane-tilt';
            badgeClass = 'badge-email';
        } else if (log.action === 'PAYMENT_RECORDED') {
            nodeClass = 'is-payment';
            nodeIcon = 'ph-currency-circle-dollar';
            badgeClass = 'badge-payment';
        } else if (log.action === 'APPROVAL_REVOKED' || log.action === 'DELETED') {
            nodeClass = 'is-revoked';
            nodeIcon = 'ph-arrow-u-up-left';
            badgeClass = 'badge-revoked';
        }

        const detailsHtml = (log.details && log.details.length > 0) ? `
            <table class="audit-diff-table">
                <tbody>
                    ${log.details.map(d => `
                        <tr>
                            <th>${escapeHtml(d.label)}</th>
                            <td>${escapeHtml(d.value)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        ` : '';

        return `
            <div class="audit-timeline-item">
                <div class="audit-timeline-node-icon ${nodeClass}">
                    <i class="ph ${nodeIcon}"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 8px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="audit-badge ${badgeClass}">${escapeHtml(log.action.replace('_', ' '))}</span>
                        <span style="font-weight: 800; font-size: 0.94rem; color: var(--po-text-strong);">${escapeHtml(log.title)}</span>
                        <span style="font-family: monospace; font-size: 0.78rem; font-weight: 700; color: var(--po-primary); background: #eff6ff; padding: 2px 6px; border-radius: 4px;">
                            ${escapeHtml(log.refNumber)}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: var(--po-text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-clock"></i> ${escapeHtml(log.formattedDate)}
                    </div>
                </div>
                <div style="font-size: 0.84rem; color: #334155; line-height: 1.45; margin-bottom: 6px;">
                    ${escapeHtml(log.description)}
                </div>
                ${detailsHtml}
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 0.75rem; color: var(--po-text-muted);">
                    <div style="width: 20px; height: 20px; border-radius: 50%; background: #0f172a; color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 10px; font-weight: 800;">
                        ${(log.actor || 'U').charAt(0)}
                    </div>
                    <span><strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})</span>
                    <span>•</span>
                    <span style="font-family: monospace;">Ref: ${escapeHtml(log.id)}</span>
                </div>
            </div>
        `;
    }).join('');
}

function onPoAuditSearchChange(query) {
    poAuditSearchTerm = query;
    renderPoAuditTrail();
}

function filterPoAuditByModule(mod, btn) {
    activePoAuditModuleFilter = mod;
    const parent = btn.parentElement;
    parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderPoAuditTrail();
}

function filterPoAuditByAction(action, btn) {
    activePoAuditActionFilter = action;
    const parent = document.getElementById('poAuditActionFilterPills');
    if (parent) {
        parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    }
    btn.classList.add('active');
    renderPoAuditTrail();
}

function openPoSingleAuditModal(poNumber) {
    const modal = document.getElementById('poSingleAuditModal');
    if (!modal) return;

    document.getElementById('singlePoAuditHeaderTitle').textContent = `Purchase Order Audit Trail • ${poNumber}`;
    document.getElementById('singlePoAuditRefTitle').textContent = poNumber;
    document.getElementById('singlePoAuditSubTitle').textContent = `Activity, fulfillment changes, and payment disbursement trail for ${poNumber}`;

    const container = document.getElementById('singlePoAuditTimelineContainer');
    const allLogs = getProcurementAuditTrail();
    const itemLogs = allLogs.filter(l => l.refNumber === poNumber);

    if (itemLogs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px; color: var(--po-text-muted);">
                <i class="ph ph-clock" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                No audit events recorded yet for this Purchase Order.
            </div>
        `;
    } else {
        container.innerHTML = itemLogs.map(log => `
            <div class="audit-timeline-item" style="padding: 12px 16px;">
                <div class="audit-timeline-node-icon is-created" style="top: 14px;">
                    <i class="ph ph-check"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <strong style="font-size: 0.88rem; color: var(--po-text-strong);">${escapeHtml(log.title)}</strong>
                    <span style="font-size: 0.75rem; color: var(--po-text-muted);">${escapeHtml(log.formattedDate)}</span>
                </div>
                <div style="font-size: 0.82rem; color: #334155; margin-bottom: 4px;">
                    ${escapeHtml(log.description)}
                </div>
                ${(log.details && log.details.length > 0) ? `
                    <div style="font-size: 0.76rem; background: #f8fafc; padding: 6px 10px; border-radius: 6px; margin-top: 6px;">
                        ${log.details.map(d => `<div><strong>${escapeHtml(d.label)}:</strong> ${escapeHtml(d.value)}</div>`).join('')}
                    </div>
                ` : ''}
                <div style="font-size: 0.72rem; color: var(--po-text-muted); margin-top: 6px;">
                    Logged by <strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})
                </div>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closePoSingleAuditModal() {
    const modal = document.getElementById('poSingleAuditModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * PO REFERENCE & TITLE UTILITIES
 * --------------------------------------------------------------------------
 */
function copyPoReference() {
    const ref = document.getElementById('poRefDisplay') ? document.getElementById('poRefDisplay').textContent : '';
    if (!ref) return;
    navigator.clipboard.writeText(ref).then(() => {
        showToast(`Copied ${ref} to clipboard`, 'success');
    }).catch(() => {
        showToast(`Reference: ${ref}`, 'success');
    });
}

function handlePoTitleChange(val) {
    window.PoStore.activePo.orderTitle = val;
    window.PoStore.activePo.purpose = val;
}

/**
 * --------------------------------------------------------------------------
 * IMPORT AWARDED RFQ MODAL UTILITIES
 * --------------------------------------------------------------------------
 */
function openImportRfqModal() {
    const modal = document.getElementById('poImportRfqModal');
    const container = document.getElementById('importRfqListContainer');
    if (!modal || !container) return;

    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const approvedRfqs = rfqs.filter(r => (r.status === 'Awarded' || r.isApproved === true));

    if (approvedRfqs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px 16px; color: #64748b;">
                <i class="ph ph-files" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 700; color: #0f172a;">No Approved RFQs Available</div>
                <div style="font-size: 12px; margin-top: 4px;">Only tenders with <strong>Awarded / Approved</strong> status can be converted into Purchase Orders.</div>
            </div>
        `;
    } else {
        container.innerHTML = approvedRfqs.map(r => `
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 14px; background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 10px; transition: all 0.15s ease;">
                <div style="display: flex; flex-direction: column; gap: 3px; min-width: 0;">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-family: monospace; font-weight: 800; color: #9333ea; font-size: 13px;">${escapeHtml(r.rfqNumber)}</span>
                        <span class="status-pill approved" style="font-size: 10.5px; padding: 2px 7px;"><i class="ph ph-check-circle"></i> Awarded Tender</span>
                    </div>
                    <div style="font-weight: 700; color: #0f172a; font-size: 13px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        ${escapeHtml(r.vendorName || r.vendorTradeName || 'Contracted Supplier')}
                    </div>
                    <div style="font-size: 11.5px; color: #64748b;">
                        ${escapeHtml(r.projectTitle || r.purpose || 'General Procurement')} • Issued: ${formatDateDisplay(r.dateIssued)} • Items: ${(r.items || []).length} lines
                    </div>
                </div>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="importApprovedRfqToBuilder('${escapeHtml(r.rfqNumber)}')" style="flex-shrink: 0; padding: 6px 14px; font-size: 12px;">
                    <i class="ph ph-arrow-down-left"></i> Import to Builder
                </button>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closeImportRfqModal() {
    const modal = document.getElementById('poImportRfqModal');
    if (modal) modal.classList.remove('is-open');
}

function importApprovedRfqToBuilder(rfqNum) {
    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const rfq = rfqs.find(r => r.rfqNumber === rfqNum);
    if (!rfq) {
        showToast('RFQ record not found', 'danger');
        return;
    }

    closeImportRfqModal();

    // Populate Active PO
    const count = (window.PoStore.purchaseOrders || []).length;
    const nextPoNum = rfq.poReference || `PO-2026-${String(count + 105).padStart(4, '0')}`;

    window.PoStore.activePo.poNumber = nextPoNum;
    window.PoStore.activePo.rfqReference = rfq.rfqNumber;
    window.PoStore.activePo.orderTitle = rfq.projectTitle || rfq.purpose || `Procurement via ${rfq.rfqNumber}`;
    window.PoStore.activePo.vendorId = rfq.vendorId || '';
    window.PoStore.activePo.vendorName = rfq.vendorName || '';
    window.PoStore.activePo.vendorTradeName = rfq.vendorTradeName || rfq.vendorName || '';
    window.PoStore.activePo.vendorContactPerson = rfq.vendorContactPerson || '';
    window.PoStore.activePo.vendorPhone = rfq.vendorPhone || '';
    window.PoStore.activePo.vendorEmail = rfq.vendorEmail || '';
    window.PoStore.activePo.vendorAddress = rfq.vendorAddress || '';
    window.PoStore.activePo.deliveryLocation = rfq.deliveryLocation || 'Central Commissary - Receiving Dock A';
    window.PoStore.activePo.paymentTerms = rfq.paymentTerms || 'Net 30 Days';
    window.PoStore.activePo.expectedDelivery = rfq.expectedDelivery || '';
    window.PoStore.activePo.specialNotes = `Imported from Tender Evaluation ${rfq.rfqNumber}. ${rfq.specialInstructions || ''}`;
    window.PoStore.activePo.status = 'Draft PO';

    // Map items
    window.PoStore.activePo.items = (rfq.items || []).map(it => ({
        sku: it.sku || 'CAT-DIR',
        name: it.name || it.itemDescription || 'Awarded Specification Item',
        specs: it.specs || '',
        category: it.category || 'Raw Ingredients',
        unit: it.unit || it.uom || 'Kg',
        quantity: parseFloat(it.quantity) || 1,
        unitPrice: parseFloat(it.quotedUnitPrice || it.unitPrice || it.budgetPrice || 0)
    }));

    // Update DOM inputs
    document.getElementById('poNumberInput').value = nextPoNum;
    document.getElementById('poRefDisplay').textContent = nextPoNum;
    document.getElementById('poOrderTitle').value = window.PoStore.activePo.orderTitle;
    document.getElementById('poVendorSelect').value = rfq.vendorId || '';
    document.getElementById('poContactPerson').value = window.PoStore.activePo.vendorContactPerson;
    document.getElementById('poContactPhone').value = window.PoStore.activePo.vendorPhone;
    document.getElementById('poContactEmail').value = window.PoStore.activePo.vendorEmail;
    document.getElementById('poFullAddress').value = window.PoStore.activePo.vendorAddress;
    document.getElementById('poPaymentTerms').value = window.PoStore.activePo.paymentTerms;
    if (document.getElementById('poExpectedDelivery')) {
        document.getElementById('poExpectedDelivery').value = window.PoStore.activePo.expectedDelivery;
    }
    document.getElementById('poDeliveryLocation').value = window.PoStore.activePo.deliveryLocation;
    document.getElementById('poSpecialNotes').value = window.PoStore.activePo.specialNotes;

    const rfqBadge = document.getElementById('poLinkedRfqPill');
    if (rfqBadge) {
        rfqBadge.style.display = 'inline-flex';
        rfqBadge.textContent = `Tender: ${rfq.rfqNumber}`;
    }

    const vendor = window.PoStore.vendors.find(v => (v.id === rfq.vendorId || v.code === rfq.vendorId)) || {
        code: rfq.vendorId || 'VND-AWD',
        tradeName: rfq.vendorTradeName || rfq.vendorName,
        legalName: rfq.vendorName,
        category: 'Awarded Partner',
        address: rfq.vendorAddress
    };
    updatePoVendorSummaryCard(vendor);

    renderPoItemsTable();
    switchPoTab('tab-po-builder');
    showToast(`✓ Imported tender ${rfq.rfqNumber} with ${(rfq.items || []).length} items into PO Builder!`, 'success');
}

function openLinkedRfqModal(rfqNum) {
    const modal = document.getElementById('poLinkedRfqModal');
    if (!modal) return;

    let rfqs = [];
    try {
        rfqs = JSON.parse(localStorage.getItem('rms_rfq_directory') || '[]');
    } catch(e) { rfqs = []; }

    const rfq = rfqs.find(r => r.rfqNumber === rfqNum);
    const content = document.getElementById('linkedRfqDetailContent');
    const title = document.getElementById('linkedRfqModalRef');

    if (title) title.textContent = rfqNum;

    if (!rfq) {
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 24px; color: #64748b;">
                    <i class="ph ph-warning-circle" style="font-size: 32px; color: #f59e0b; display: block; margin-bottom: 6px;"></i>
                    <div style="font-weight: 700; color: #0f172a;">Originating RFQ Archive Not Found</div>
                    <div style="font-size: 12px; margin-top: 4px;">The reference <strong>${escapeHtml(rfqNum)}</strong> is stored in history, but its complete raw tender dataset was cleared from local memory.</div>
                </div>
            `;
        }
    } else if (content) {
        content.innerHTML = `
            <div style="display: flex; flex-direction: column; gap: 12px;">
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b;">Selected Vendor</div>
                        <div style="font-size: 14px; font-weight: 800; color: #0f172a;">${escapeHtml(rfq.vendorTradeName || rfq.vendorName)}</div>
                        <div style="font-size: 12px; color: #64748b;">${escapeHtml(rfq.vendorAddress || 'Metro Manila')}</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="status-pill approved" style="font-size: 11px; padding: 3px 9px;"><i class="ph ph-check-circle"></i> Awarded Tender</span>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">Issued: ${formatDateDisplay(rfq.dateIssued)}</div>
                    </div>
                </div>
                <div>
                    <div style="font-size: 12px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Quotation Line Items (${(rfq.items || []).length})</div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 12px;">
                        <thead>
                            <tr style="background: #f1f5f9; text-align: left;">
                                <th style="padding: 6px 8px;">Item Description</th>
                                <th style="padding: 6px 8px; text-align: center;">UOM</th>
                                <th style="padding: 6px 8px; text-align: right;">Quantity</th>
                                <th style="padding: 6px 8px; text-align: right;">Quoted Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${(rfq.items || []).map(it => `
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 6px 8px; font-weight: 600;">${escapeHtml(it.name || it.itemDescription)}</td>
                                    <td style="padding: 6px 8px; text-align: center;">${escapeHtml(it.unit || it.uom || 'Kg')}</td>
                                    <td style="padding: 6px 8px; text-align: right;">${parseFloat(it.quantity || 0).toFixed(2)}</td>
                                    <td style="padding: 6px 8px; text-align: right; font-weight: 700; color: #047857;">₱${formatMoney(it.quotedUnitPrice || it.unitPrice || 0)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    }

    modal.classList.add('is-open');
}

function closeLinkedRfqModal() {
    const modal = document.getElementById('poLinkedRfqModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * FINANCIAL TRACKING & PAYMENT SETTLEMENT (FILTERED BY REFERENCE)
 * --------------------------------------------------------------------------
 */
let currentSettlementRef = '';

function getStoredPayments() {
    try {
        return JSON.parse(localStorage.getItem('rms_po_payments') || '[]');
    } catch(e) {
        return [];
    }
}

function saveStoredPayments(payments) {
    try {
        localStorage.setItem('rms_po_payments', JSON.stringify(payments));
    } catch(e) {
        console.error('Error saving payments:', e);
    }
}

function handlePaymentTermsChange(val) {
    window.PoStore.activePo.paymentTerms = val;
    showToast(`Payment terms set to ${val}`, 'success');
}

function updatePoSummaryPayBadge() {
    const badge = document.getElementById('poSummaryPayBadge');
    if (!badge) return;

    const po = window.PoStore.activePo;
    const gross = calculatePoGrossTotal();
    const payments = getStoredPayments().filter(p => p.poNumber === po.poNumber);
    const paid = payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);

    if (paid >= gross && gross > 0) {
        badge.className = 'pay-badge paid';
        badge.textContent = 'Paid in Full';
        window.PoStore.activePo.paymentStatus = 'Paid in Full / Cash Out';
    } else if (paid > 0) {
        badge.className = 'pay-badge partial';
        badge.textContent = `Partial (${formatMoney(paid)})`;
        window.PoStore.activePo.paymentStatus = 'Partial Payment';
    } else {
        badge.className = 'pay-badge unpaid';
        badge.textContent = 'Unpaid';
        window.PoStore.activePo.paymentStatus = 'Unpaid / Credit';
    }

    window.PoStore.activePo.amountPaid = paid;
    window.PoStore.activePo.balanceDue = Math.max(0, gross - paid);
}

function openPaymentSettlementModal(targetRef) {
    const modal = document.getElementById('poPaymentSettlementModal');
    if (!modal) return;

    currentSettlementRef = targetRef || (window.PoStore.activePo.poNumber || 'PO-2026-0105');

    // Populate References Dropdown
    const select = document.getElementById('payModalRefSelect');
    if (select) {
        const pos = window.PoStore.purchaseOrders || [];
        const refSet = new Set();
        if (window.PoStore.activePo.poNumber) refSet.add(window.PoStore.activePo.poNumber);
        pos.forEach(p => { if (p.poNumber) refSet.add(p.poNumber); });

        select.innerHTML = Array.from(refSet).map(ref => `
            <option value="${ref}" ${ref === currentSettlementRef ? 'selected' : ''}>${ref}</option>
        `).join('');
    }

    // Default payment date to today
    const dateInput = document.getElementById('newPayDate');
    if (dateInput && !dateInput.value) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }

    renderPaymentSettlementData();
    modal.classList.add('is-open');
}

function closePaymentSettlementModal() {
    const modal = document.getElementById('poPaymentSettlementModal');
    if (modal) modal.classList.remove('is-open');
}

function filterPaymentsByReference(ref) {
    if (!ref) return;
    currentSettlementRef = ref;
    renderPaymentSettlementData();
}

function resetPaymentViewToActivePo() {
    currentSettlementRef = window.PoStore.activePo.poNumber || 'PO-2026-0105';
    const select = document.getElementById('payModalRefSelect');
    if (select) select.value = currentSettlementRef;
    renderPaymentSettlementData();
}

function renderPaymentSettlementData() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const isActivePo = (ref === window.PoStore.activePo.poNumber);

    // Resolve Gross and Linked RFQ
    let gross = 0;
    let linkedRfq = '';
    let vendorName = '';

    if (isActivePo) {
        gross = calculatePoGrossTotal();
        linkedRfq = window.PoStore.activePo.rfqReference || '';
        vendorName = window.PoStore.activePo.vendorTradeName || window.PoStore.activePo.vendorName || '';
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            linkedRfq = po.rfqReference || '';
            vendorName = po.vendorTradeName || po.vendorName || '';
        }
    }

    // Filter payments strictly by currentSettlementRef
    const allPayments = getStoredPayments();
    const payments = allPayments.filter(p => p.poNumber === ref);
    const totalPaid = payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
    const balanceDue = Math.max(0, gross - totalPaid);

    // DOM Updates
    document.getElementById('payModalActiveRef').textContent = ref;
    document.getElementById('payLedgerRefLabel').textContent = ref;
    document.getElementById('payModalGross').textContent = `₱${formatMoney(gross)}`;
    document.getElementById('payModalPaid').textContent = `₱${formatMoney(totalPaid)}`;
    document.getElementById('payModalBalance').textContent = `₱${formatMoney(balanceDue)}`;
    document.getElementById('payLedgerCountBadge').textContent = `${payments.length} Entr${payments.length === 1 ? 'y' : 'ies'}`;

    const pct = gross > 0 ? ((totalPaid / gross) * 100) : (totalPaid > 0 ? 100 : 0);
    document.getElementById('payModalPercentPaid').textContent = `${pct.toFixed(1)}% settled${vendorName ? ' • ' + vendorName : ''}`;

    const statusBadge = document.getElementById('payModalStatusBadge');
    if (totalPaid >= gross && gross > 0) {
        statusBadge.className = 'pay-badge paid';
        statusBadge.textContent = 'Paid in Full';
    } else if (totalPaid > 0) {
        statusBadge.className = 'pay-badge partial';
        statusBadge.textContent = 'Partial Payment';
    } else {
        statusBadge.className = 'pay-badge unpaid';
        statusBadge.textContent = 'Unpaid / Credit';
    }

    // Linked RFQ Badge
    const linkedBadge = document.getElementById('payModalLinkedRfqBadge');
    if (linkedBadge) {
        if (linkedRfq) {
            linkedBadge.style.display = 'inline-flex';
            linkedBadge.textContent = `Tender: ${linkedRfq}`;
        } else {
            linkedBadge.style.display = 'none';
        }
    }

    // Render Ledger Table
    const tbody = document.getElementById('payModalLedgerTbody');
    if (tbody) {
        if (payments.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" style="text-align: center; padding: 28px 14px; color: #94a3b8;">
                        <i class="ph ph-receipt" style="font-size: 28px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                        <div style="font-weight: 700; color: #475569;">No Payment Disbursements Recorded</div>
                        <div style="font-size: 11.5px; margin-top: 2px;">Record an advance, downpayment, or full payment for reference <strong>${escapeHtml(ref)}</strong> using the form below.</div>
                    </td>
                </tr>
            `;
        } else {
            tbody.innerHTML = payments.map((p, idx) => `
                <tr style="border-bottom: 1px solid #f1f5f9;">
                    <td style="padding: 7px 10px; text-align: center; color: #64748b; font-weight: 700;">${idx + 1}</td>
                    <td style="padding: 7px 10px; font-weight: 600; color: #1e293b;">${formatDateDisplay(p.paymentDate)}</td>
                    <td style="padding: 7px 10px; color: #475569;"><span class="po-label-badge">${escapeHtml(p.method || 'Cash')}</span></td>
                    <td style="padding: 7px 10px; font-family: monospace; font-weight: 700; color: #0284c7;">${escapeHtml(p.reference || '--')}</td>
                    <td style="padding: 7px 10px; color: #475569; font-size: 11px;">${escapeHtml(p.fundSource || 'Petty Cash')}</td>
                    <td style="padding: 7px 10px; text-align: right; font-weight: 800; color: #059669;">₱${formatMoney(p.amount)}</td>
                    <td style="padding: 7px 10px; text-align: center;">
                        <button type="button" class="inv-table-filter-btn" onclick="deletePaymentDisbursement('${p.id}')" title="Delete payment entry" style="color: #ef4444; width: 24px; height: 24px; font-size: 13px;">
                            <i class="ph ph-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    }

    // Synchronize to Active PO if editing current active PO
    if (isActivePo) {
        updatePoSummaryPayBadge();
    }
}

function fillFullRemainingBalance() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const isActivePo = (ref === window.PoStore.activePo.poNumber);

    let gross = 0;
    if (isActivePo) {
        gross = calculatePoGrossTotal();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        }
    }

    const allPayments = getStoredPayments();
    const payments = allPayments.filter(p => p.poNumber === ref);
    const totalPaid = payments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
    const balanceDue = Math.max(0, gross - totalPaid);

    const amountInput = document.getElementById('newPayAmount');
    if (amountInput) {
        amountInput.value = balanceDue.toFixed(2);
        amountInput.focus();
    }
}

function addPaymentDisbursement() {
    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    const amountVal = parseFloat(document.getElementById('newPayAmount').value) || 0;

    if (amountVal <= 0) {
        showToast('Please enter a valid disbursement amount greater than 0', 'danger');
        return;
    }

    const method = document.getElementById('newPayMethod').value;
    const paymentRef = document.getElementById('newPayRef').value.trim() || `DISB-${Date.now().toString().slice(-4)}`;
    const paymentDate = document.getElementById('newPayDate').value || new Date().toISOString().split('T')[0];
    const fundSource = document.getElementById('newPayFundSource').value;
    const remarks = document.getElementById('newPayRemarks').value.trim();

    const paymentEntry = {
        id: `PAY-${Date.now()}-${Math.floor(Math.random() * 1000)}`,
        poNumber: ref,
        amount: amountVal,
        method: method,
        reference: paymentRef,
        paymentDate: paymentDate,
        fundSource: fundSource,
        remarks: remarks,
        recordedAt: new Date().toISOString(),
        actor: '{{ auth()->user()->name ?? "Procurement Specialist" }}'
    };

    const payments = getStoredPayments();
    payments.push(paymentEntry);
    saveStoredPayments(payments);

    // Audit log
    logProcurementAudit(
        'PO',
        ref,
        'PAYMENT_RECORDED',
        'Payment Disbursement Added',
        `Disbursed ₱${formatMoney(amountVal)} via ${method} for ${ref} (${paymentRef}).`,
        [
            { label: 'Amount Paid', value: `₱${formatMoney(amountVal)}` },
            { label: 'Method', value: method },
            { label: 'Receipt / Check #', value: paymentRef },
            { label: 'Fund Source', value: fundSource }
        ]
    );

    // Update in-memory PO state
    if (ref === window.PoStore.activePo.poNumber) {
        updatePoSummaryPayBadge();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            const poPayments = payments.filter(p => p.poNumber === ref);
            const totalPaid = poPayments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
            const gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            po.amountPaid = totalPaid;
            po.balanceDue = Math.max(0, gross - totalPaid);
            po.paymentStatus = (totalPaid >= gross && gross > 0) ? 'Paid in Full / Cash Out' : (totalPaid > 0 ? 'Partial Payment' : 'Unpaid / Credit');
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    // Reset inputs
    document.getElementById('newPayAmount').value = '';
    document.getElementById('newPayRef').value = '';
    document.getElementById('newPayRemarks').value = '';

    renderPaymentSettlementData();
    renderPoDirectory();
    showToast(`✓ Recorded ₱${formatMoney(amountVal)} disbursement for ${ref}`, 'success');
}

function deletePaymentDisbursement(id) {
    if (!confirm('Remove this payment disbursement entry?')) return;

    let payments = getStoredPayments();
    const deleted = payments.find(p => p.id === id);
    payments = payments.filter(p => p.id !== id);
    saveStoredPayments(payments);

    if (deleted) {
        logProcurementAudit(
            'PO',
            deleted.poNumber,
            'PAYMENT_REMOVED',
            'Payment Disbursement Voided',
            `Voided payment disbursement ₱${formatMoney(deleted.amount)} (${deleted.reference}) for ${deleted.poNumber}.`,
            [{ label: 'Voided Amount', value: `₱${formatMoney(deleted.amount)}` }]
        );
    }

    const ref = currentSettlementRef || window.PoStore.activePo.poNumber;
    if (ref === window.PoStore.activePo.poNumber) {
        updatePoSummaryPayBadge();
    } else {
        const po = (window.PoStore.purchaseOrders || []).find(p => p.poNumber === ref);
        if (po) {
            const poPayments = payments.filter(p => p.poNumber === ref);
            const totalPaid = poPayments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
            const gross = (po.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
            po.amountPaid = totalPaid;
            po.balanceDue = Math.max(0, gross - totalPaid);
            po.paymentStatus = (totalPaid >= gross && gross > 0) ? 'Paid in Full / Cash Out' : (totalPaid > 0 ? 'Partial Payment' : 'Unpaid / Credit');
            localStorage.setItem('rms_purchase_orders', JSON.stringify(window.PoStore.purchaseOrders));
        }
    }

    renderPaymentSettlementData();
    renderPoDirectory();
    showToast('Payment disbursement entry removed', 'success');
}

// Global click handler to close vendor combobox dropdown
window.addEventListener('click', (e) => {
    const combobox = document.getElementById('poVendorCombobox');
    const dropdown = document.getElementById('poVendorComboDropdown');
    const trigger = document.getElementById('poVendorComboTrigger');
    if (combobox && !combobox.contains(e.target)) {
        if (dropdown) dropdown.classList.remove('is-active');
        if (trigger) trigger.classList.remove('is-active');
    }
});
</script>
@endpush

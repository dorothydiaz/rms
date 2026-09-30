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
    gap: 20px;
    width: 100%;
    padding-bottom: 50px;
}

/* Nav Tabs Bar */
.po-nav-tabs-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: #ffffff;
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    padding: 8px 12px;
    box-shadow: var(--po-shadow-sm);
    flex-wrap: wrap;
}
.po-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 10px 20px;
    border-radius: var(--po-radius-md);
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--po-text-muted);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.16s ease;
    font-family: inherit;
}
.po-tab-btn:hover {
    color: var(--po-text-strong);
    background: #f8fafc;
}
.po-tab-btn.active {
    background: var(--po-primary-gradient);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(2, 132, 199, 0.28);
}
.po-tab-count {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 800;
    background: var(--po-primary-subtle);
    color: var(--po-primary-dark);
}
.po-tab-btn.active .po-tab-count {
    background: rgba(255, 255, 255, 0.24);
    color: #ffffff;
}
.po-tab-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
}
.po-tab-btn.active .po-tab-badge {
    background: rgba(255, 255, 255, 0.24);
    color: #ffffff;
}
.po-tab-pane {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
}

/* KPI Cluster Grid */
.po-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 16px;
}
.po-kpi-card {
    background: #ffffff;
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    padding: 16px 20px;
    box-shadow: var(--po-shadow-sm);
    display: flex;
    flex-direction: column;
    gap: 6px;
    cursor: pointer;
    transition: all 0.16s ease;
}
.po-kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--po-shadow-md);
    border-color: var(--po-primary-light);
}
.po-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.po-kpi-label {
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--po-text-muted);
}
.po-kpi-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    background: var(--po-primary-subtle);
    color: var(--po-primary-dark);
}
.po-kpi-icon.pending {
    background: #fffbeb;
    color: #d97706;
}
.po-kpi-icon.received {
    background: #ecfdf5;
    color: #059669;
}
.po-kpi-icon.paid {
    background: #eff6ff;
    color: #1d4ed8;
}
.po-kpi-val {
    font-size: 1.55rem;
    font-weight: 800;
    color: var(--po-text-strong);
    font-variant-numeric: tabular-nums;
    line-height: 1.1;
}
.po-kpi-sub {
    font-size: 0.75rem;
    color: var(--po-text-muted);
}

/* Filter Pills Bar */
.po-filter-pills-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
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

/* Command Bar */
.po-command-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    background: var(--po-surface);
    border: 1px solid var(--po-border-subtle);
    border-radius: var(--po-radius-lg);
    padding: 18px 22px;
    box-shadow: var(--po-shadow-sm);
}
.po-title-group {
    display: flex;
    align-items: center;
    gap: 14px;
}
.po-icon-badge {
    width: 46px;
    height: 46px;
    border-radius: 12px;
    background: var(--po-primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    font-size: 24px;
    box-shadow: 0 6px 16px rgba(2, 132, 199, 0.28);
}
.po-title-text h1 {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--po-text-strong);
    margin: 0;
    letter-spacing: -0.01em;
}
.po-title-meta {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-top: 3px;
    font-size: 0.82rem;
    color: var(--po-text-muted);
}
.po-actions-group {
    display: flex;
    align-items: center;
    gap: 10px;
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
    padding: 14px 20px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--po-border-subtle);
}
.po-panel-title {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.92rem;
    font-weight: 700;
    color: var(--po-text-strong);
    letter-spacing: 0.01em;
}
.po-panel-title i {
    font-size: 18px;
    color: var(--po-primary);
}
.po-panel-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 16px;
}

/* Form Controls */
.po-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 14px;
}
.po-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.po-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--po-text-medium);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.po-label-badge {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--po-primary-dark);
    background: var(--po-primary-subtle);
    padding: 1px 6px;
    border-radius: 4px;
    text-transform: none;
}
.po-input, .po-select, .po-textarea {
    width: 100%;
    padding: 9px 12px;
    border: 1.5px solid var(--po-border-subtle);
    border-radius: var(--po-radius-md);
    font-size: 0.88rem;
    color: var(--po-text-strong);
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.po-input:focus, .po-select:focus, .po-textarea:focus {
    outline: none;
    border-color: var(--po-border-focus);
    box-shadow: 0 0 0 3px var(--po-primary-glow);
}
.po-input[readonly] {
    background: #f8fafc;
    color: #475569;
    border-style: dashed;
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

    <!-- PO Navigation Tabs Bar -->
    <nav class="po-nav-tabs-bar" id="poTabsBar">
        <button type="button" class="po-tab-btn active" id="tabBtnPoList" data-tab="tab-po-list" onclick="switchPoTab('tab-po-list')">
            <i class="ph ph-receipt"></i>
            <span>PO Directory & Status Tracker</span>
            <span class="po-tab-count" id="tabPoCount">0</span>
        </button>
        <button type="button" class="po-tab-btn" id="tabBtnPoBuilder" data-tab="tab-po-builder" onclick="switchPoTab('tab-po-builder')">
            <i class="ph ph-plus-circle"></i>
            <span id="tabPoBuilderTitle">Create New Purchase Order</span>
            <span class="po-tab-badge" id="tabPoBuilderBadge">New PO</span>
        </button>
        <button type="button" class="po-tab-btn" id="tabBtnPoAudit" data-tab="tab-po-audit" onclick="switchPoTab('tab-po-audit')">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>PO Audit Trail & Financial Logs</span>
            <span class="po-tab-count" id="tabPoAuditCount">0</span>
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
                    <div class="po-kpi-icon"><i class="ph ph-files"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoTotal">0</div>
                <div class="po-kpi-sub">Total procurement commitments</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Approved / Issued')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Pending Delivery</span>
                    <div class="po-kpi-icon pending"><i class="ph ph-truck"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPending">0</div>
                <div class="po-kpi-sub">Issued orders awaiting receiving dock</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoStatus('Fully Received')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Fully Received</span>
                    <div class="po-kpi-icon received"><i class="ph ph-check-circle"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoReceived">0</div>
                <div class="po-kpi-sub">Inventoried and stocked in commissary</div>
            </div>
            <div class="po-kpi-card" onclick="filterPoPayment('Paid in Full / Cash Out')">
                <div class="po-kpi-header">
                    <span class="po-kpi-label">Settled / Paid</span>
                    <div class="po-kpi-icon paid"><i class="ph ph-currency-dollar"></i></div>
                </div>
                <div class="po-kpi-val" id="kpiPoPaid">0</div>
                <div class="po-kpi-sub">Financial cash-out / settled invoices</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="po-panel-card">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--po-border-subtle); flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 10px; flex: 1; flex-wrap: wrap;">
                    <div style="position: relative; flex: 1; min-width: 240px; max-width: 380px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
                        <input type="text" class="po-input" id="poSearchInput" oninput="handlePoSearch(this.value)" placeholder="Search PO #, vendor, wet market stall..." style="padding-left: 36px;">
                    </div>
                    <div class="po-filter-pills-bar" id="poStatusFilterBar">
                        <button type="button" class="po-filter-pill active" data-filter="all" onclick="filterPoStatus('all')">All (<span id="countPoAll">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Standard" onclick="filterPoType('vendor')">Vendor Supplier</button>
                        <button type="button" class="po-filter-pill" data-filter="WetMarket" onclick="filterPoType('wet_market')">Wet Market Cash Run</button>
                        <button type="button" class="po-filter-pill" data-filter="Pending" onclick="filterPoStatus('Approved / Issued')">Pending Delivery (<span id="countPoPending">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Received" onclick="filterPoStatus('Fully Received')">Received (<span id="countPoReceived">0</span>)</button>
                        <button type="button" class="po-filter-pill" data-filter="Paid" onclick="filterPoPayment('Paid in Full / Cash Out')">Paid (<span id="countPoPaid">0</span>)</button>
                    </div>
                </div>
                <div>
                    <button type="button" class="po-btn po-btn-primary" onclick="startNewPoFromDirectory()">
                        <i class="ph ph-plus-circle"></i> + Create Purchase Order
                    </button>
                </div>
            </div>

            <!-- PO Directory Table -->
            <div class="po-table-responsive">
                <table class="po-table" id="poDirectoryTable">
                    <thead>
                        <tr>
                            <th style="width: 130px;">PO Reference</th>
                            <th style="width: 110px;">Type</th>
                            <th style="min-width: 220px;">Vendor / Market Source</th>
                            <th style="width: 110px;">Order Date</th>
                            <th style="width: 110px;">Delivery Due</th>
                            <th style="width: 80px; text-align: center;">Items</th>
                            <th style="width: 130px; text-align: right;">Total Amount</th>
                            <th style="width: 130px; text-align: center;">Payment Status</th>
                            <th style="width: 140px; text-align: center;">PO Status</th>
                            <th style="width: 150px; text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="poDirectoryTbody">
                        <!-- Rendered dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: CREATE NEW PURCHASE ORDER (PO BUILDER)
         ==================================================================== -->
    <div class="po-tab-pane" id="pane-tab-po-builder" style="display: none;">

        <!-- Command Bar -->
        <div class="po-command-bar">
            <div class="po-title-group">
                <div class="po-icon-badge">
                    <i class="ph ph-receipt"></i>
                </div>
                <div class="po-title-text">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <h1 id="poBuilderTitle">Purchase Order Generator</h1>
                        <span class="po-status-badge approved" id="poHeaderStatusBadge">
                            <i class="ph ph-dot"></i> Draft PO
                        </span>
                    </div>
                    <div class="po-title-meta">
                        <span>PO Ref: <strong id="poRefDisplay">PO-2026-0105</strong></span>
                        <span>•</span>
                        <span>Purchaser: <strong>{{ auth()->user()->name ?? 'Procurement Officer' }}</strong></span>
                        <span>•</span>
                        <span id="poSaveIndicator">Auto-saved to Local Storage</span>
                    </div>
                </div>
            </div>

            <div class="po-actions-group">
                <button type="button" class="po-btn po-btn-outline" onclick="switchPoTab('tab-po-list')" title="Back to PO Tracker">
                    <i class="ph ph-arrow-left"></i> PO Directory
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="resetPoForm()" title="Clear form">
                    <i class="ph ph-arrow-counter-clockwise"></i> Reset
                </button>
                <button type="button" class="po-btn po-btn-outline" onclick="openPoDocumentPreview()" title="Open formal printable PO preview (F8)">
                    <i class="ph ph-eye"></i> Preview Document <span class="po-hotkey-badge">F8</span>
                </button>
                <button type="button" class="po-btn po-btn-primary" onclick="issuePurchaseOrderSubmit()" title="Approve & save purchase order (F10)">
                    <i class="ph ph-check-circle"></i> Issue Purchase Order <span class="po-hotkey-badge" style="background: rgba(255,255,255,0.25);">F10</span>
                </button>
            </div>
        </div>

        <!-- PO Type Mode Selector: Standard Vendor vs Direct Wet Market -->
        <div class="po-mode-toggle-card">
            <div>
                <div style="font-weight: 800; font-size: 0.95rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-path" style="color: var(--po-primary); font-size: 18px;"></i>
                    <span>Select Procurement Sourcing Method</span>
                </div>
                <div style="font-size: 0.8rem; color: var(--po-text-muted); margin-top: 2px;">
                    Choose whether buying from formal contracted vendors or conducting direct cash purchases in wet markets.
                </div>
            </div>
            <div class="po-mode-pills">
                <button type="button" class="po-mode-btn active" id="btnModeVendor" onclick="setPoMode('vendor')">
                    <i class="ph ph-buildings"></i> Standard Vendor PO
                </button>
                <button type="button" class="po-mode-btn is-market" id="btnModeMarket" onclick="setPoMode('wet_market')">
                    <i class="ph ph-basket"></i> Direct / Wet Market Cash Purchase
                </button>
            </div>
        </div>

        <!-- Top Grid: Source / Vendor & Order Parameters -->
        <div class="po-top-grid">

            <!-- Card 1: Vendor Partner or Wet Market Stall Profile -->
            <div class="po-panel-card">
                <div class="po-panel-header">
                    <div class="po-panel-title">
                        <i class="ph ph-storefront" id="sourceCardIcon"></i>
                        <span id="sourceCardTitle">Vendor & Partner Profile</span>
                    </div>
                    <span class="po-label-badge" id="sourceTypeBadge">Approved Vendor</span>
                </div>
                <div class="po-panel-body">
                    <!-- Standard Vendor Mode Inputs -->
                    <div id="sectionVendorSource">
                        <div class="po-field" style="margin-bottom: 14px;">
                            <label class="po-label" for="poVendorSelect">Select Contracted Supplier *</label>
                            <select class="po-select" id="poVendorSelect" onchange="handlePoVendorSelect(this.value)">
                                <option value="">-- Choose Approved Vendor from Masterlist --</option>
                            </select>
                        </div>
                    </div>

                    <!-- Wet Market Cash Sourcing Inputs -->
                    <div id="sectionMarketSource" style="display: none;">
                        <div class="po-field" style="margin-bottom: 14px;">
                            <label class="po-label" for="poMarketStallName">
                                <span>Wet Market Name & Stall # *</span>
                                <span class="po-label-badge">Direct Market Run</span>
                            </label>
                            <input type="text" class="po-input" id="poMarketStallName" placeholder="e.g. Balintawak Wet Market - Stall 14 (Aling Nena Meats)">
                        </div>
                    </div>

                    <div class="po-form-row">
                        <div class="po-field">
                            <label class="po-label" for="poContactPerson">Contact Person / Stall Vendor</label>
                            <input type="text" class="po-input" id="poContactPerson" placeholder="Representative Name">
                        </div>
                        <div class="po-field">
                            <label class="po-label" for="poContactPhone">Phone / Mobile</label>
                            <input type="text" class="po-input" id="poContactPhone" placeholder="+63 9XX XXX XXXX">
                        </div>
                    </div>

                    <div class="po-form-row">
                        <div class="po-field">
                            <label class="po-label" for="poContactEmail">Vendor Email (Optional for Market)</label>
                            <input type="email" class="po-input" id="poContactEmail" placeholder="vendor@example.com">
                        </div>
                        <div class="po-field">
                            <label class="po-label" for="poSourceLocation">Location / City</label>
                            <input type="text" class="po-input" id="poSourceLocation" placeholder="Market Location / Warehouse City">
                        </div>
                    </div>

                    <div class="po-field">
                        <label class="po-label" for="poFullAddress">Complete Physical Address</label>
                        <input type="text" class="po-input" id="poFullAddress" placeholder="Street, Market Hall, Building, City">
                    </div>
                </div>
            </div>

            <!-- Card 2: PO Logistics & Order Schedule -->
            <div class="po-panel-card">
                <div class="po-panel-header">
                    <div class="po-panel-title">
                        <i class="ph ph-calendar-check"></i>
                        <span>Order Schedule & Delivery Destination</span>
                    </div>
                    <span class="po-label-badge">Audit Tracked</span>
                </div>
                <div class="po-panel-body">
                    <div class="po-form-row">
                        <div class="po-field">
                            <label class="po-label" for="poNumberInput">PO Reference #</label>
                            <input type="text" class="po-input" id="poNumberInput" value="PO-2026-0105" readonly>
                        </div>
                        <div class="po-field">
                            <label class="po-label" for="poOrderDate">Purchase Date</label>
                            <input type="date" class="po-input" id="poOrderDate">
                        </div>
                    </div>

                    <div class="po-form-row">
                        <div class="po-field">
                            <label class="po-label" for="poExpectedDelivery">Expected Delivery / Market Run Date *</label>
                            <input type="date" class="po-input" id="poExpectedDelivery">
                        </div>
                        <div class="po-field">
                            <label class="po-label" for="poRfqReference">Linked RFQ Reference</label>
                            <input type="text" class="po-input" id="poRfqReference" placeholder="e.g. RFQ-2026-0038 (Optional)">
                        </div>
                    </div>

                    <div class="po-field">
                        <label class="po-label" for="poDeliveryLocation">Destination Kitchen / Commissary Dock</label>
                        <select class="po-select" id="poDeliveryLocation">
                            <option value="Central Commissary - Receiving Dock A">Central Commissary - Receiving Dock A</option>
                            <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                            <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                            <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                            <option value="Central Warehouse - Cold Storage">Central Warehouse - Cold Storage</option>
                        </select>
                    </div>

                    <div class="po-field">
                        <label class="po-label" for="poSpecialNotes">Delivery & Quality Inspection Instructions</label>
                        <textarea class="po-textarea" id="poSpecialNotes" placeholder="e.g. Inspect temperature upon receiving dock. Meat cuts must be vacuum sealed and weighing ticket attached."></textarea>
                    </div>
                </div>
            </div>

        </div>

        <!-- Line Items Table (Item Master + Custom Wet Market Line Entry) -->
        <div class="po-panel-card">
            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid var(--po-border-subtle); flex-wrap: wrap; gap: 12px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-weight: 800; font-size: 1.02rem; color: var(--po-text-strong); display: flex; align-items: center; gap: 8px;">
                        <i class="ph ph-shopping-cart" style="color: var(--po-primary); font-size: 20px;"></i>
                        <span>Purchase Order Items & Pricing</span>
                    </div>
                    <span class="po-tab-count" id="poItemCountBadge">0 Line Items</span>
                </div>
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button type="button" class="po-btn po-btn-outline" onclick="openItemMasterQuickPicker()" title="Select item from Masterlist (F2)">
                        <i class="ph ph-magnifying-glass"></i> Select from Master <span class="po-hotkey-badge">F2</span>
                    </button>
                    <button type="button" class="po-btn po-btn-teal" onclick="addNewBlankPoItemRow()" title="Add custom market item row">
                        <i class="ph ph-plus-circle"></i> Add Custom Market Line
                    </button>
                    <button type="button" class="po-btn po-btn-outline" style="color: var(--po-danger);" onclick="clearAllPoItems()" title="Clear table">
                        <i class="ph ph-trash"></i> Clear Items
                    </button>
                </div>
            </div>

            <div class="po-table-responsive">
                <table class="po-table" id="poItemsTable">
                    <thead>
                        <tr>
                            <th style="width: 45px; text-align: center;">#</th>
                            <th style="width: 120px;">SKU / Code</th>
                            <th style="min-width: 220px;">Item Description & Specifications *</th>
                            <th style="width: 140px;">Category</th>
                            <th style="width: 100px; text-align: center;">UOM</th>
                            <th style="width: 110px; text-align: right;">Quantity *</th>
                            <th style="width: 130px; text-align: right;">Unit Price (₱) *</th>
                            <th style="width: 140px; text-align: right;">Line Total (₱)</th>
                            <th style="width: 80px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="poItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; background: #f8fafc; border-top: 1px solid var(--po-border-subtle); flex-wrap: wrap; gap: 16px;">
                <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: var(--po-text-muted); text-transform: uppercase;">Total Lines</div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: var(--po-text-strong);" id="summaryPoLines">0</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: var(--po-text-muted); text-transform: uppercase;">Total Quantity Units</div>
                        <div style="font-size: 1.15rem; font-weight: 800; color: var(--po-text-strong);" id="summaryPoUnits">0.00</div>
                    </div>
                    <div>
                        <div style="font-size: 0.74rem; font-weight: 700; color: var(--po-text-muted); text-transform: uppercase;">Gross PO Amount</div>
                        <div style="font-size: 1.35rem; font-weight: 900; color: var(--po-teal);" id="summaryPoGross">₱0.00</div>
                    </div>
                </div>
                <div style="font-size: 0.8rem; color: var(--po-text-muted);">
                    <i class="ph ph-shield-check"></i> Pricing reflects contracted quotation or verified wet market cash disbursement.
                </div>
            </div>
        </div>

        <!-- Card 4: Financial Tracking & Payment Settlement Section (User Explicit Requirement) -->
        <div class="po-payment-panel">
            <div class="po-payment-header">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="ph ph-bank" style="color: var(--po-primary); font-size: 22px;"></i>
                    <div>
                        <div style="font-weight: 800; font-size: 0.96rem; color: var(--po-text-strong);">Financial Tracking & Payment Settlement</div>
                        <div style="font-size: 0.78rem; color: var(--po-text-muted);">Record cash-out, trade credit, check issuance, or petty cash advances upon PO creation</div>
                    </div>
                </div>
                <span class="pay-badge unpaid" id="paymentStatusBadge">Unpaid / Credit</span>
            </div>
            <div class="po-panel-body">
                <div class="po-form-row">
                    <div class="po-field">
                        <label class="po-label" for="poPaymentStatus">Payment Status Upon Creation *</label>
                        <select class="po-select" id="poPaymentStatus" onchange="handlePaymentStatusChange(this.value)">
                            <option value="Unpaid / Credit">Unpaid / Trade Credit (Net 30/15 Days)</option>
                            <option value="Paid in Full / Cash Out">Paid in Full / Cash Out (Immediate Settlement)</option>
                            <option value="Partial Payment">Partial Payment (Downpayment / Advance)</option>
                        </select>
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="poPaymentMethod">Payment Disbursement Method *</label>
                        <select class="po-select" id="poPaymentMethod">
                            <option value="Cash / Petty Cash">Cash / Petty Cash (Wet Market Run)</option>
                            <option value="Trade Credit (Net 30/15)">Trade Credit (Net 30/15 Days Invoice)</option>
                            <option value="Company Check">Company Check (Post-Dated or Spot)</option>
                            <option value="Bank Transfer / Electronic">Bank Transfer / Electronic (InstaPay / PESONet)</option>
                            <option value="GCash / Maya">GCash / Maya (Mobile e-Wallet)</option>
                            <option value="Corporate Credit Card">Corporate Credit Card</option>
                        </select>
                    </div>
                </div>

                <div class="po-form-row">
                    <div class="po-field">
                        <label class="po-label" for="poAmountPaid">Amount Disbursed / Paid (₱) *</label>
                        <input type="number" step="0.01" min="0" class="po-input" id="poAmountPaid" value="0.00" oninput="calculateBalanceDue()">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="poBalanceDue">Remaining Balance Due (₱)</label>
                        <input type="text" class="po-input" id="poBalanceDue" value="₱0.00" readonly style="font-weight: 800; color: #dc2626;">
                    </div>
                </div>

                <div class="po-form-row">
                    <div class="po-field">
                        <label class="po-label" for="poPaymentRef">OR # / Check # / Receipt Ref</label>
                        <input type="text" class="po-input" id="poPaymentRef" placeholder="e.g. Cash Slip #4912, Check #00412, GCash Ref 88214">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="poPaymentDate">Payment Date</label>
                        <input type="date" class="po-input" id="poPaymentDate">
                    </div>
                    <div class="po-field">
                        <label class="po-label" for="poFundSource">Disbursing Account / Fund Source</label>
                        <select class="po-select" id="poFundSource">
                            <option value="Branch Petty Cash Fund">Branch Petty Cash Fund</option>
                            <option value="Main Commissary Checking Acct">Main Commissary Checking Account</option>
                            <option value="Purchaser Cash Advance">Purchaser Cash Advance Fund</option>
                            <option value="Corporate Card Account">Corporate Card Account</option>
                        </select>
                    </div>
                </div>

                <div class="po-field">
                    <label class="po-label" for="poPaymentRemarks">Financial Remarks & Accounting Notes</label>
                    <textarea class="po-textarea" id="poPaymentRemarks" placeholder="e.g. Cash disbursed from Makati Branch petty cash for wet market meat procurement. Official vendor ticket attached."></textarea>
                </div>
            </div>
        </div>

    </div><!-- /#pane-tab-po-builder -->

    <!-- ====================================================================
         TAB 3: PO AUDIT TRAIL & FINANCIAL LOGS
         ==================================================================== -->
    <div class="po-tab-pane" id="pane-tab-po-audit" style="display: none; flex-direction: column; gap: 20px;">
        <!-- Audit KPI Summary Cards -->
        <div class="audit-overview-grid">
            <div class="audit-metric-card">
                <div class="audit-metric-icon-box" style="background: #eff6ff; color: #2563eb;">
                    <i class="ph ph-receipt"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTotal">0</div>
                    <div class="audit-metric-label">Total PO Events</div>
                </div>
            </div>
            <div class="audit-metric-card">
                <div class="audit-metric-icon-box" style="background: #fff7ed; color: #ea580c;">
                    <i class="ph ph-storefront"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiWetMarket">0</div>
                    <div class="audit-metric-label">Wet Market Cash Runs</div>
                </div>
            </div>
            <div class="audit-metric-card">
                <div class="audit-metric-icon-box" style="background: #ecfdf5; color: #059669;">
                    <i class="ph ph-currency-circle-dollar"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiPayments">0</div>
                    <div class="audit-metric-label">Payment Settlements</div>
                </div>
            </div>
            <div class="audit-metric-card">
                <div class="audit-metric-icon-box" style="background: #f5f3ff; color: #7c3aed;">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="poAuditKpiTransfers">0</div>
                    <div class="audit-metric-label">Transfers & Issues</div>
                </div>
            </div>
        </div>

        <!-- Audit Toolbar & Filter Bar -->
        <div class="audit-toolbar">
            <div style="display: flex; align-items: center; gap: 12px; flex: 1; min-width: 260px; flex-wrap: wrap;">
                <div style="position: relative; flex: 1; max-width: 360px;">
                    <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--po-text-muted); font-size: 16px;"></i>
                    <input type="text" class="po-input" id="poAuditSearchInput" placeholder="Search PO #, supplier/stall, payment ref, or actor..." style="padding-left: 38px; height: 38px; font-size: 0.85rem;" oninput="onPoAuditSearchChange(this.value)">
                </div>
                <div style="display: flex; align-items: center; gap: 6px;">
                    <button type="button" class="audit-filter-pill active" data-module="ALL" onclick="filterPoAuditByModule('ALL', this)">All Modules</button>
                    <button type="button" class="audit-filter-pill" data-module="PO" onclick="filterPoAuditByModule('PO', this)">PO Only</button>
                    <button type="button" class="audit-filter-pill" data-module="RFQ" onclick="filterPoAuditByModule('RFQ', this)">RFQ Only</button>
                </div>
            </div>
            <div class="audit-filter-pills" id="poAuditActionFilterPills">
                <button type="button" class="audit-filter-pill active" data-action="ALL" onclick="filterPoAuditByAction('ALL', this)">All PO Logs</button>
                <button type="button" class="audit-filter-pill" data-action="PAYMENT_RECORDED" onclick="filterPoAuditByAction('PAYMENT_RECORDED', this)">Payments</button>
                <button type="button" class="audit-filter-pill" data-action="WET_MARKET_CREATED" onclick="filterPoAuditByAction('WET_MARKET_CREATED', this)">Wet Market</button>
                <button type="button" class="audit-filter-pill" data-action="CREATED" onclick="filterPoAuditByAction('CREATED', this)">PO Created</button>
                <button type="button" class="audit-filter-pill" data-action="PO_TRANSFERRED" onclick="filterPoAuditByAction('PO_TRANSFERRED', this)">RFQ Transfers</button>
                <button type="button" class="audit-filter-pill" data-action="EDITED" onclick="filterPoAuditByAction('EDITED', this)">Edits</button>
            </div>
        </div>

        <!-- Chronological Timeline Container -->
        <div class="audit-timeline-container" id="poAuditTimeline">
            <!-- Dynamically populated via renderPoAuditTrail -->
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

document.addEventListener('DOMContentLoaded', () => {
    initPoStore();
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
});

function initPoStore() {
    // 1. Load Purchase Orders
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

    // 2. Load Vendors
    const storedVendors = localStorage.getItem('rms_vendor_database_v6');
    if (storedVendors) {
        try {
            window.PoStore.vendors = JSON.parse(storedVendors);
        } catch (e) {
            window.PoStore.vendors = [];
        }
    }

    // 3. Load Item Master
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

function renderPoVendorDropdown() {
    const select = document.getElementById('poVendorSelect');
    if (!select) return;

    const vendors = window.PoStore.vendors;
    if (vendors.length === 0) {
        select.innerHTML = `
            <option value="">-- No Approved Vendors Found in Cache --</option>
            <option value="VND-SAN-002">San Miguel Pure Foods Company Inc. (Raw Ingredients)</option>
            <option value="VND-ROB-003">Universal Robina Corporation (Raw Ingredients)</option>
            <option value="VND-ECO-004">EcoPack Solutions Philippines Corp. (Packaging)</option>
        `;
        return;
    }

    select.innerHTML = `
        <option value="">-- Choose Approved Vendor from Masterlist --</option>
        ${vendors.map(v => `
            <option value="${v.id || v.code}">[${v.code || v.id}] ${v.legalName || v.tradeName} (${v.category || 'General'})</option>
        `).join('')}
    `;
}

/**
 * Switch Tab: PO Directory vs PO Builder
 */
function switchPoTab(tabId) {
    const btnList = document.getElementById('tabBtnPoList');
    const btnBuilder = document.getElementById('tabBtnPoBuilder');
    const btnAudit = document.getElementById('tabBtnPoAudit');
    const paneList = document.getElementById('pane-tab-po-list');
    const paneBuilder = document.getElementById('pane-tab-po-builder');
    const paneAudit = document.getElementById('pane-tab-po-audit');

    [btnList, btnBuilder, btnAudit].forEach(b => b && b.classList.remove('active'));
    [paneList, paneBuilder, paneAudit].forEach(p => p && (p.style.display = 'none'));

    const pos = window.PoStore.purchaseOrders || [];
    const countBadge = document.getElementById('tabPoCount');
    if (countBadge) countBadge.textContent = pos.length;

    const auditCountBadge = document.getElementById('tabPoAuditCount');
    if (auditCountBadge && typeof getProcurementAuditTrail === 'function') {
        const logs = getProcurementAuditTrail();
        auditCountBadge.textContent = logs.filter(l => l.module === 'PO' || l.action === 'PO_TRANSFERRED').length || logs.length;
    }

    if (tabId === 'tab-po-list') {
        if (btnList) btnList.classList.add('active');
        if (paneList) paneList.style.display = 'flex';
        renderPoDirectory();
    } else if (tabId === 'tab-po-builder') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) paneBuilder.style.display = 'flex';
    } else if (tabId === 'tab-po-audit') {
        if (btnAudit) btnAudit.classList.add('active');
        if (paneAudit) paneAudit.style.display = 'flex';
        renderPoAuditTrail();
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
    if (!vendorId) return;
    const vendor = window.PoStore.vendors.find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    document.getElementById('poContactPerson').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].name : '';
    document.getElementById('poContactPhone').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].phone : '';
    document.getElementById('poContactEmail').value = (vendor.contacts && vendor.contacts[0]) ? vendor.contacts[0].email : (vendor.email || '');
    document.getElementById('poSourceLocation').value = vendor.location || 'Metro Manila';
    document.getElementById('poFullAddress').value = vendor.address || '';

    window.PoStore.activePo.vendorId = vendor.id || vendor.code;
    window.PoStore.activePo.vendorName = vendor.legalName || vendor.tradeName;
    window.PoStore.activePo.vendorTradeName = vendor.tradeName || vendor.legalName;
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
                <td colspan="9" style="text-align: center; padding: 42px; color: var(--po-text-muted);">
                    <i class="ph ph-shopping-bag-open" style="font-size: 38px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                    <div style="font-weight: 700; color: var(--po-text-strong);">No Line Items in Purchase Order</div>
                    <div style="font-size: 0.8rem; margin-top: 4px;">Click <strong>"Select from Master" (F2)</strong> or <strong>"Add Custom Market Line"</strong> to input items.</div>
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
                    <input type="text" value="${escapeHtml(it.sku || '')}" placeholder="SKU / Tag" onchange="updatePoItemField(${idx}, 'sku', this.value)" style="font-family: monospace; font-weight: 600;">
                </td>
                <td>
                    <input type="text" value="${escapeHtml(it.name || '')}" placeholder="Item Description / Cut / Brand" onchange="updatePoItemField(${idx}, 'name', this.value)" required style="font-weight: 600;">
                </td>
                <td>
                    <select onchange="updatePoItemField(${idx}, 'category', this.value)">
                        <option value="Raw Ingredients" ${it.category === 'Raw Ingredients' ? 'selected' : ''}>Raw Ingredients</option>
                        <option value="Packaging" ${it.category === 'Packaging' ? 'selected' : ''}>Packaging</option>
                        <option value="Beverage" ${it.category === 'Beverage' ? 'selected' : ''}>Beverage</option>
                        <option value="Services" ${it.category === 'Services' ? 'selected' : ''}>Services</option>
                        <option value="General" ${it.category === 'General' ? 'selected' : ''}>General</option>
                    </select>
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

    showToast(`✓ Purchase Order ${po.poNumber} successfully issued!`, 'success');
    switchPoTab('tab-po-list');
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
        tbody.innerHTML = `<tr><td colspan="10" style="text-align: center; padding: 36px; color: var(--po-text-muted);">No Purchase Orders found matching the filter criteria.</td></tr>`;
        return;
    }

    tbody.innerHTML = filtered.map(p => {
        const gross = (p.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.unitPrice) || 0)), 0);
        const isMarket = p.poType === 'wet_market';
        let payBadgeClass = 'unpaid';
        if (p.paymentStatus === 'Paid in Full / Cash Out') payBadgeClass = 'paid';
        else if (p.paymentStatus === 'Partial Payment') payBadgeClass = 'partial';

        let poStatusClass = 'draft';
        if (p.status === 'Approved / Issued') poStatusClass = 'approved';
        else if (p.status === 'Fully Received') poStatusClass = 'received';

        return `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--po-primary-dark);">
                    <a href="javascript:void(0)" onclick="editPoFromDirectory('${p.poNumber}')" style="color: inherit; text-decoration: underline;">
                        ${escapeHtml(p.poNumber)}
                    </a>
                </td>
                <td>
                    <span class="po-type-badge ${isMarket ? 'wet-market' : 'vendor'}">
                        ${isMarket ? '<i class="ph ph-basket"></i> Wet Market' : '<i class="ph ph-buildings"></i> Vendor'}
                    </span>
                </td>
                <td>
                    <div style="font-weight: 700; color: var(--po-text-strong);">${escapeHtml(p.vendorTradeName || p.vendorName || 'Market Stall')}</div>
                    <div style="font-size: 0.74rem; color: var(--po-text-muted);">
                        ${escapeHtml(p.vendorContactPerson || 'Purchaser')} • ${escapeHtml(p.vendorPhone || '')}
                    </div>
                </td>
                <td>${formatDateDisplay(p.orderDate)}</td>
                <td><span style="font-weight: 600; color: var(--po-teal-dark);">${formatDateDisplay(p.expectedDelivery)}</span></td>
                <td class="td-center"><span class="po-tab-count">${(p.items || []).length}</span></td>
                <td class="td-num" style="font-weight: 800;">₱${formatMoney(gross)}</td>
                <td class="td-center">
                    <span class="pay-badge ${payBadgeClass}">
                        ${escapeHtml(p.paymentStatus || 'Unpaid')}
                    </span>
                </td>
                <td class="td-center">
                    <span class="po-status-badge ${poStatusClass}">
                        <i class="ph ph-dot"></i> ${escapeHtml(p.status)}
                    </span>
                </td>
                <td class="td-center">
                    <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
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
            </tr>
        `;
    }).join('');
}

function filterPoStatus(status) {
    activePoStatusFilter = status;
    activePoTypeFilter = 'all';
    renderPoDirectory();
}

function filterPoType(type) {
    activePoTypeFilter = type;
    renderPoDirectory();
}

function filterPoPayment(payStatus) {
    activePoStatusFilter = 'all';
    activePoTypeFilter = 'all';
    renderPoDirectory();
}

function handlePoSearch(q) {
    poSearchTerm = q;
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
    setPoMode(po.poType || 'vendor');

    if (po.poType === 'wet_market') {
        document.getElementById('poMarketStallName').value = po.vendorName || '';
    } else {
        document.getElementById('poVendorSelect').value = po.vendorId || '';
    }

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
    document.getElementById('poVendorSelect').value = '';
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

function renderPoAuditTrail() {
    const container = document.getElementById('poAuditTimeline');
    if (!container) return;

    const allLogs = getProcurementAuditTrail();
    const q = (poAuditSearchTerm || '').toLowerCase().trim();

    const filtered = allLogs.filter(log => {
        const matchesModule = (activePoAuditModuleFilter === 'ALL') || (log.module === activePoAuditModuleFilter);
        const matchesAction = (activePoAuditActionFilter === 'ALL') || (log.action === activePoAuditActionFilter);
        const matchesSearch = !q ||
            (log.refNumber && log.refNumber.toLowerCase().includes(q)) ||
            (log.title && log.title.toLowerCase().includes(q)) ||
            (log.description && log.description.toLowerCase().includes(q)) ||
            (log.actor && log.actor.toLowerCase().includes(q));

        return matchesModule && matchesAction && matchesSearch;
    });

    // Update KPI numbers
    const kpiTotal = document.getElementById('poAuditKpiTotal');
    const kpiWetMarket = document.getElementById('poAuditKpiWetMarket');
    const kpiPayments = document.getElementById('poAuditKpiPayments');
    const kpiTransfers = document.getElementById('poAuditKpiTransfers');

    if (kpiTotal) kpiTotal.textContent = allLogs.filter(l => l.module === 'PO').length || allLogs.length;
    if (kpiWetMarket) kpiWetMarket.textContent = allLogs.filter(l => l.action === 'WET_MARKET_CREATED').length;
    if (kpiPayments) kpiPayments.textContent = allLogs.filter(l => l.action === 'PAYMENT_RECORDED').length;
    if (kpiTransfers) kpiTransfers.textContent = allLogs.filter(l => l.action === 'PO_TRANSFERRED' || l.action === 'CREATED').length;

    if (filtered.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 48px; background: #ffffff; border: 1px solid var(--po-border-subtle); border-radius: 10px; color: var(--po-text-muted);">
                <i class="ph ph-clock-countdown" style="font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                No Purchase Order audit activity records found matching selected filters.
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
</script>
@endpush

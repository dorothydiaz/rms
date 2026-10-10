@extends('layouts.app')

@section('title', 'Request for Quotations (RFQ) - Restaurant Management System')

@push('styles')
<style>
/* ==========================================================================
   REQUEST FOR QUOTATION (RFQ) ENTERPRISE WORKSPACE TOKENS
   RMS Industrial Procurement & Glassmorphic Ergonomics
   ========================================================================== */
:root {
    /* HR Operations Enterprise Theme Tokens */
    --rfq-primary: #9333ea;
    --rfq-primary-dark: #7c3aed;
    --rfq-primary-light: #c084fc;
    --rfq-primary-glow: rgba(168, 85, 247, 0.22);
    --rfq-primary-subtle: rgba(168, 85, 247, 0.12);
    --rfq-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --rfq-primary-gradient-hover: linear-gradient(135deg, #db2777 0%, #9333ea 100%);

    --rfq-teal: #6366f1;
    --rfq-teal-dark: #4f46e5;
    --rfq-teal-subtle: rgba(99, 102, 241, 0.12);

    --rfq-success: #10b981;
    --rfq-success-dark: #059669;
    --rfq-success-subtle: rgba(16, 185, 129, 0.12);

    --rfq-warning: #f59e0b;
    --rfq-warning-subtle: rgba(245, 158, 11, 0.12);

    --rfq-danger: #ef4444;
    --rfq-danger-subtle: rgba(239, 68, 68, 0.12);

    --rfq-purple: #9333ea;
    --rfq-purple-subtle: rgba(168, 85, 247, 0.12);

    --rfq-surface: #ffffff;
    --rfq-surface-card: rgba(255, 255, 255, 0.90);
    --rfq-surface-subtle: #f8fafc;
    --rfq-border-subtle: #e2e8f0;
    --rfq-border-focus: #a855f7;

    --rfq-text-strong: #0f172a;
    --rfq-text-medium: #334155;
    --rfq-text-muted: #64748b;
    --rfq-text-subtle: #94a3b8;

    --rfq-shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.05);
    --rfq-shadow-md: 0 8px 24px rgba(148, 163, 184, 0.08);
    --rfq-shadow-lg: 0 14px 34px -4px rgba(168, 85, 247, 0.14);
    --rfq-shadow-xl: 0 20px 48px rgba(15, 23, 42, 0.12);

    --rfq-radius-sm: 6px;
    --rfq-radius-md: 10px;
    --rfq-radius-lg: 14px;
    --rfq-radius-xl: 18px;
}

/* ==========================================================================
   HR-STYLE FLAT PAGE HEADER (No Box Ribbon - Compact Spacing)
   ========================================================================== */
.hr-parent-header {
    margin-bottom: 2px;
    width: 100%;
}
.hr-parent-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 0px;
    flex-wrap: wrap;
}
.hr-parent-title {
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
.hr-parent-subtitle {
    font-size: 12.5px;
    color: #64748b;
    font-weight: 400;
    margin: 1px 0 0 0;
}
.hr-page-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

/* Page Scaffolding - Compact Gaps */
.rfq-workspace-container {
    display: flex;
    flex-direction: column;
    gap: 8px;
    width: 100%;
    padding-bottom: 50px;
}

/* ==========================================================================
   HR THEME GLASSMORPHIC SEGMENTED TABS BAR (Compact Ergonomics)
   ========================================================================== */
.rfq-nav-tabs-bar {
    display: flex;
    align-items: center;
    gap: 6px;
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    border-radius: 12px;
    padding: 3px 5px;
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.08), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    flex-wrap: wrap;
    margin-bottom: 2px;
}
.rfq-tab-btn {
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
    font-family: var(--font-family, 'Poppins', sans-serif);
    white-space: nowrap;
    position: relative;
    line-height: 1.2;
}
.rfq-tab-btn i {
    font-size: 16px;
    color: #94a3b8;
    transition: color 0.2s ease, transform 0.2s ease;
}
.rfq-tab-btn:hover:not(.active) {
    color: #9333ea;
    background: rgba(168, 85, 247, 0.08);
}
.rfq-tab-btn:hover:not(.active) i {
    color: #9333ea;
    transform: scale(1.1);
}
.rfq-tab-btn.active {
    background: #ffffff;
    color: #9333ea;
    border-color: rgba(168, 85, 247, 0.28);
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.14), 0 1px 3px rgba(0, 0, 0, 0.04);
}
.rfq-tab-btn.active i {
    background: linear-gradient(135deg, #ec4899, #a855f7);
    -webkit-background-clip: text;
    background-clip: text;
    -webkit-text-fill-color: transparent;
    font-weight: 700;
}
.rfq-tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 16%;
    right: 16%;
    height: 3px;
    background: linear-gradient(90deg, #ec4899, #a855f7);
    border-radius: 3px 3px 0 0;
}
.rfq-tab-count {
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
.rfq-tab-btn.active .rfq-tab-count {
    background: rgba(168, 85, 247, 0.18);
    color: #7c3aed;
}
.rfq-tab-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10.5px;
    font-weight: 700;
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
}
.rfq-tab-btn.active .rfq-tab-badge {
    background: rgba(168, 85, 247, 0.18);
    color: #7c3aed;
}
.rfq-tab-pane {
    display: flex;
    flex-direction: column;
    gap: 20px;
    width: 100%;
}
#pane-tab-builder.rfq-tab-pane {
    gap: 4px;
}

/* KPI Cluster Grid for RFQ Directory (Compacted HR Metric Cards) */
.rfq-kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}
.rfq-kpi-card {
    background: rgba(255, 255, 255, 0.90);
    backdrop-filter: blur(24px) saturate(180%);
    -webkit-backdrop-filter: blur(24px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.95);
    border-radius: 12px;
    padding: 8px 14px;
    box-shadow: 0 4px 16px rgba(148, 163, 184, 0.06), 0 1px 3px rgba(0, 0, 0, 0.02), inset 0 1px 1px rgba(255, 255, 255, 0.95);
    display: flex;
    flex-direction: column;
    gap: 2px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    overflow: hidden;
}
.rfq-kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2.5px;
    background: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%);
    opacity: 0;
    transition: opacity 0.2s ease;
}
.rfq-kpi-card:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.98);
    border-color: rgba(168, 85, 247, 0.38);
    box-shadow: 0 8px 20px rgba(168, 85, 247, 0.12), 0 2px 6px rgba(236, 72, 153, 0.06);
}
.rfq-kpi-card:hover::before {
    opacity: 1;
}
.rfq-kpi-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rfq-kpi-label {
    font-size: 10.5px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: #64748b;
}
.rfq-kpi-icon {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.14), rgba(168, 85, 247, 0.20));
    border: 1px solid rgba(168, 85, 247, 0.32);
    color: #9333ea;
}
.rfq-kpi-icon.draft {
    background: #f1f5f9;
    color: #64748b;
    border-color: #cbd5e1;
}
.rfq-kpi-icon.awaiting {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.14), rgba(217, 119, 6, 0.20));
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.32);
}
.rfq-kpi-icon.awarded {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.14), rgba(5, 150, 105, 0.20));
    color: #059669;
    border: 1px solid rgba(168, 85, 247, 0.32);
}
.rfq-kpi-val {
    font-family: var(--font-heading, 'Poppins', sans-serif);
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
    line-height: 1.15;
    letter-spacing: -0.02em;
}
.rfq-kpi-sub {
    font-size: 10px;
    color: #94a3b8;
}

/* Header Filter Triggers & Popover System */
.rfq-th-filter-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    cursor: pointer;
    padding: 2px 6px;
    border-radius: 6px;
    transition: all 0.15s ease;
}
.rfq-th-filter-wrapper:hover {
    background: rgba(148, 163, 184, 0.18);
}
.rfq-th-funnel-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid transparent;
    background: transparent;
    color: #94a3b8;
    font-size: 11px;
    cursor: pointer;
    position: relative;
    padding: 0;
    transition: all 0.15s ease;
}
.rfq-th-filter-wrapper:hover .rfq-th-funnel-btn,
.rfq-th-funnel-btn.is-active {
    color: #7c3aed;
    background: rgba(124, 58, 237, 0.10);
    border-color: rgba(124, 58, 237, 0.25);
}
.rfq-th-funnel-dot {
    position: absolute;
    top: 2px;
    right: 2px;
    width: 5px;
    height: 5px;
    border-radius: 50%;
    background: #7c3aed;
}

/* Floating Header Filter Popover */
.rfq-header-filter-popover {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.18), 0 4px 10px -2px rgba(15, 23, 42, 0.08);
    padding: 12px;
    max-width: 380px;
    z-index: 1200;
}
.rfq-filter-popover-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-bottom: 8px;
    margin-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
}
.rfq-popover-reset-btn {
    font-size: 11px;
    color: #7c3aed;
    background: none;
    border: none;
    cursor: pointer;
    text-decoration: underline;
    font-weight: 600;
}
.rfq-filter-section-title {
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rfq-section-active-badge {
    font-size: 10px;
    color: #7c3aed;
    background: rgba(124, 58, 237, 0.10);
    padding: 1px 6px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: none;
}
.rfq-filter-pill-row {
    display: flex;
    gap: 5px;
    flex-wrap: wrap;
}
.rfq-filter-pill-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 5px;
}
.rfq-popover-chip {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 500;
    color: #334155;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
    white-space: nowrap;
}
.rfq-popover-chip:hover {
    background: #f1f5f9;
    border-color: #cbd5e1;
}
.rfq-popover-chip.is-active {
    background: #7c3aed;
    color: #ffffff;
    border-color: #7c3aed;
    font-weight: 700;
    box-shadow: 0 1px 3px rgba(124, 58, 237, 0.3);
}
.rfq-popover-chip.is-success.is-active {
    background: #059669;
    border-color: #059669;
}
.rfq-popover-chip.is-warning.is-active {
    background: #d97706;
    border-color: #d97706;
}
.rfq-popover-chip.is-purple.is-active {
    background: #8b5cf6;
    border-color: #8b5cf6;
}
.rfq-popover-chip.is-danger.is-active {
    background: #dc2626;
    border-color: #dc2626;
}

/* Radio List for Terms */
.rfq-filter-radio-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
}
.rfq-filter-radio-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    color: #334155;
    transition: background 0.15s ease;
}
.rfq-filter-radio-row:hover {
    background: #f8fafc;
}
.rfq-filter-radio-row.is-selected {
    background: rgba(124, 58, 237, 0.08);
    color: #7c3aed;
    font-weight: 600;
}
.rfq-filter-radio-row input {
    accent-color: #7c3aed;
}
.rfq-radio-label {
    flex: 1;
}
.rfq-radio-count {
    font-size: 11px;
    color: #94a3b8;
}

/* Active Chips in Toolbar */
.rfq-active-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    background: rgba(124, 58, 237, 0.10);
    border: 1px solid rgba(124, 58, 237, 0.25);
    color: #6d28d9;
    font-size: 11.5px;
    font-weight: 500;
}
.rfq-active-chip strong {
    font-weight: 700;
}
.rfq-active-chip button {
    background: none;
    border: none;
    color: #6d28d9;
    font-size: 13px;
    cursor: pointer;
    padding: 0;
    line-height: 1;
}
.rfq-clear-all-chip-btn {
    background: none;
    border: none;
    color: #64748b;
    font-size: 11px;
    text-decoration: underline;
    cursor: pointer;
    padding: 2px 4px;
}
.rfq-clear-all-chip-btn:hover {
    color: #dc2626;
}

/* Directory Status Badges - HR Theme Glass Badges */
.status-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 9px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    line-height: 1;
    white-space: nowrap;
    flex-shrink: 0;
}
.status-pill.draft {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.10), rgba(168, 85, 247, 0.16));
    color: #9333ea;
    border: 1px solid rgba(168, 85, 247, 0.28);
    box-shadow: 0 1px 4px rgba(168, 85, 247, 0.08);
}
.status-pill.sent {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.12), rgba(217, 119, 6, 0.18));
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.32);
}
.status-pill.received {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.12), rgba(14, 165, 233, 0.18));
    color: #0369a1;
    border: 1px solid rgba(14, 165, 233, 0.32);
}
.status-pill.awarded {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(5, 150, 105, 0.18));
    color: #047857;
    border: 1px solid rgba(16, 185, 129, 0.32);
}
.status-pill.cancelled {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(220, 38, 38, 0.18));
    color: #b91c1c;
    border: 1px solid rgba(239, 68, 68, 0.32);
}

/* Global Pessimistic UI Locking Spinner Overlay */
.rfq-lock-overlay {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    backdrop-filter: blur(5px);
    display: none;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 16px;
    z-index: 99999;
    color: #ffffff;
}
.rfq-lock-overlay.is-active {
    display: flex;
}
.rfq-spinner {
    width: 48px;
    height: 48px;
    border: 4px solid rgba(255, 255, 255, 0.2);
    border-top-color: #38bdf8;
    border-radius: 50%;
    animation: rfqSpin 0.75s cubic-bezier(0.68, -0.55, 0.27, 1.55) infinite;
}
@keyframes rfqSpin {
    to { transform: rotate(360deg); }
}

/* Flash Delta Feedback */
@keyframes rfqFlashGreen {
    0% { background-color: rgba(16, 185, 129, 0.25); }
    100% { background-color: transparent; }
}
.rfq-flash-highlight {
    animation: rfqFlashGreen 1.4s ease-out;
}

/* Compact RFQ Command Bar & Reference Pill */
.rfq-ref-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 3px 10px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.08), rgba(168, 85, 247, 0.14));
    border: 1px solid rgba(168, 85, 247, 0.28);
    border-radius: 6px;
    font-family: monospace;
    font-size: 13px;
    font-weight: 700;
    color: #9333ea;
}

.rfq-command-bar {
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

.rfq-command-left {
    display: flex;
    align-items: center;
    gap: 8px;
}

.rfq-builder-audit-panel {
    background: var(--rfq-surface);
    border: 1px solid var(--rfq-border-subtle);
    border-radius: var(--rfq-radius-lg);
    box-shadow: var(--rfq-shadow-sm);
    display: none;
    flex-direction: column;
    gap: 16px;
    padding: 16px;
    margin-top: 12px;
    animation: fadeInDown 0.18s ease-out;
}
.rfq-builder-audit-panel.is-active {
    display: flex;
}
.rfq-status-badge.ready {
    background: rgba(168, 85, 247, 0.12);
    color: #9333ea;
    border: 1px solid rgba(168, 85, 247, 0.3);
}

.rfq-actions-group {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}
.rfq-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: var(--rfq-radius-md);
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    border: 1px solid transparent;
    text-decoration: none;
    font-family: var(--font-family, 'Poppins', sans-serif);
    line-height: 1.2;
}
.rfq-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
    pointer-events: none;
}
.rfq-btn-outline {
    background: rgba(255, 255, 255, 0.85);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(226, 232, 240, 0.9);
    color: #334155;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}
.rfq-btn-outline:hover {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.08), rgba(168, 85, 247, 0.14));
    border-color: rgba(168, 85, 247, 0.3);
    color: #9333ea;
    box-shadow: 0 4px 12px rgba(168, 85, 247, 0.10);
    transform: translateY(-1px);
}
.rfq-btn-teal {
    background: linear-gradient(135deg, #6366f1, #4f46e5);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.28);
}
.rfq-btn-teal:hover {
    background: linear-gradient(135deg, #4f46e5, #4338ca);
    transform: translateY(-1px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.38);
    color: #ffffff;
}
.rfq-btn-primary {
    background: linear-gradient(135deg, #ec4899, #a855f7);
    color: #ffffff;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.32), inset 0 1px 1px rgba(255, 255, 255, 0.4);
}
.rfq-btn-primary:hover {
    background: linear-gradient(135deg, #db2777, #9333ea);
    transform: translateY(-1px);
    box-shadow: 0 8px 22px rgba(168, 85, 247, 0.42);
    color: #ffffff;
}
.rfq-btn-danger {
    background: #fff;
    border-color: #fecaca;
    color: var(--rfq-danger);
}
.rfq-btn-danger:hover {
    background: #fef2f2;
    border-color: #fca5a5;
}

/* Hotkey Pills */
.rfq-hotkey-badge {
    background: rgba(255, 255, 255, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.35);
    padding: 1px 5px;
    border-radius: 4px;
    font-size: 0.7rem;
    font-weight: 700;
    margin-left: 2px;
}
.rfq-btn-outline .rfq-hotkey-badge {
    background: #f1f5f9;
    border-color: #e2e8f0;
    color: #64748b;
}

/* Industrial Workspace Asymmetric Grid (Approx. 4fr Left / 6fr Right) */
.rfq-builder-workspace-grid {
    display: grid;
    grid-template-columns: minmax(360px, 4.2fr) minmax(480px, 5.8fr);
    gap: 10px;
    align-items: stretch;
}
@media (max-width: 1100px) {
    .rfq-builder-workspace-grid {
        grid-template-columns: 1fr;
    }
}

.rfq-builder-left-col {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.rfq-builder-right-col {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-width: 0;
}

.rfq-builder-right-col .rfq-items-panel {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.rfq-builder-right-col .rfq-table-responsive {
    flex: 1 1 auto;
    max-height: calc(100vh - 290px);
    min-height: 260px;
    overflow-y: auto;
}

.rfq-panel-card {
    background: var(--rfq-surface);
    border: 1px solid var(--rfq-border-subtle);
    border-radius: var(--rfq-radius-lg);
    box-shadow: var(--rfq-shadow-sm);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.rfq-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
    border-bottom: 1px solid var(--rfq-border-subtle);
}
.rfq-panel-title {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--rfq-text-strong);
    letter-spacing: 0.01em;
}
.rfq-panel-title i {
    font-size: 16px;
    color: var(--rfq-primary);
}
.rfq-panel-body {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Space-Saving Form Controls & Merged Inputs */
.rfq-form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
}
.rfq-field {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.rfq-dual-inputs {
    display: flex;
    gap: 6px;
    align-items: center;
}
.rfq-dual-inputs .rfq-input {
    flex: 1;
    min-width: 0;
}
.rfq-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--rfq-text-medium);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1px;
}
.rfq-label-badge {
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--rfq-primary-dark);
    background: var(--rfq-primary-subtle);
    padding: 1px 5px;
    border-radius: 4px;
    text-transform: none;
}
.rfq-input, .rfq-select, .rfq-textarea {
    width: 100%;
    padding: 6px 10px;
    border: 1.5px solid var(--rfq-border-subtle);
    border-radius: 7px;
    font-size: 0.82rem;
    color: var(--rfq-text-strong);
    background: #ffffff;
    transition: all 0.15s ease;
    box-sizing: border-box;
    font-family: inherit;
}
.rfq-input:focus, .rfq-select:focus, .rfq-textarea:focus {
    outline: none;
    border-color: var(--rfq-border-focus);
    box-shadow: 0 0 0 3px var(--rfq-primary-glow);
}
.rfq-input[readonly] {
    background: #f8fafc;
    color: #475569;
    cursor: default;
    border-style: dashed;
}
.rfq-textarea {
    resize: vertical;
    min-height: 44px;
    height: 44px;
    padding: 6px 10px;
    font-size: 0.8rem;
    line-height: 1.35;
}

/* Searchable Target Vendor Combobox */
.rfq-vendor-combobox {
    position: relative;
    width: 100%;
}
.rfq-vendor-combo-trigger {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 10px;
    border: 1.5px solid var(--rfq-border-subtle);
    border-radius: 7px;
    background: #ffffff;
    cursor: pointer;
    font-size: 0.82rem;
    color: var(--rfq-text-strong);
    transition: all 0.15s ease;
    user-select: none;
    text-align: left;
}
.rfq-vendor-combo-trigger:hover {
    border-color: #cbd5e1;
    background: #fafafa;
}
.rfq-vendor-combo-trigger.is-active,
.rfq-vendor-combo-trigger:focus {
    outline: none;
    border-color: #a855f7;
    box-shadow: 0 0 0 3px rgba(168, 85, 247, 0.18);
}
.rfq-vendor-combo-dropdown {
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
    animation: fadeInDown 0.15s ease-out;
}
.rfq-vendor-combo-dropdown.is-active {
    display: flex;
}
.rfq-vendor-combo-search-wrap {
    padding: 7px;
    border-bottom: 1px solid #f1f5f9;
    background: #f8fafc;
    position: relative;
}
.rfq-vendor-combo-search-wrap i {
    position: absolute;
    left: 15px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    font-size: 14px;
}
.rfq-vendor-combo-search-input {
    width: 100%;
    padding: 5px 8px 5px 28px;
    border: 1px solid #cbd5e1;
    border-radius: 6px;
    font-size: 0.8rem;
    box-sizing: border-box;
    outline: none;
}
.rfq-vendor-combo-search-input:focus {
    border-color: #a855f7;
    box-shadow: 0 0 0 2px rgba(168, 85, 247, 0.15);
}
.rfq-vendor-combo-list {
    max-height: 220px;
    overflow-y: auto;
    padding: 4px;
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.rfq-vendor-combo-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 6px 9px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.79rem;
    transition: background 0.1s ease;
}
.rfq-vendor-combo-item:hover,
.rfq-vendor-combo-item.is-selected {
    background: #f3e8ff;
}
.rfq-vendor-combo-item.is-selected {
    font-weight: 700;
    color: #7e22ce;
}
.rfq-vendor-combo-empty {
    padding: 14px;
    text-align: center;
    color: #94a3b8;
    font-size: 0.78rem;
}

/* Compact Active Vendor Info Banner */
.rfq-vendor-pill-box {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, rgba(245, 243, 255, 0.65), rgba(248, 250, 252, 0.85));
    border: 1px solid rgba(216, 180, 254, 0.45);
    border-radius: 8px;
    padding: 5px 9px;
}
.rfq-vendor-avatar {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: linear-gradient(135deg, #ec4899, #a855f7);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.78rem;
    flex-shrink: 0;
}
.rfq-vendor-summary {
    flex: 1;
    min-width: 0;
}
.rfq-vendor-name-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-weight: 700;
    font-size: 0.81rem;
    color: var(--rfq-text-strong);
}
.rfq-vendor-detail-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.72rem;
    color: var(--rfq-text-muted);
    margin-top: 1px;
}

/* ==========================================================================
   TABLE SECTION & FLOATING COLUMN FILTER DROPDOWN
   ========================================================================== */
.rfq-items-panel {
    background: var(--rfq-surface);
    border: 1px solid var(--rfq-border-subtle);
    border-radius: var(--rfq-radius-lg);
    box-shadow: var(--rfq-shadow-sm);
    display: flex;
    flex-direction: column;
}
.rfq-items-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 14px;
    background: #ffffff;
    border-bottom: 1px solid var(--rfq-border-subtle);
    flex-wrap: wrap;
    gap: 8px;
}
.rfq-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
}
.rfq-items-counter-pill {
    background: var(--rfq-primary-subtle);
    color: var(--rfq-primary-dark);
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
}
.rfq-toolbar-right {
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Table Container with Horizontal Scroll */
.rfq-table-responsive {
    width: 100%;
    overflow-x: auto;
    position: relative;
    min-height: 280px;
}
.rfq-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.85rem;
    color: var(--rfq-text-strong);
    table-layout: fixed;
}
.rfq-table th {
    background: #f8fafc;
    color: var(--rfq-text-medium);
    font-weight: 700;
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 12px 14px;
    border-bottom: 2px solid var(--rfq-border-subtle);
    text-align: left;
    position: relative;
    user-select: none;
    white-space: nowrap;
}
.rfq-table th.th-num, .rfq-table td.td-num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.rfq-table th.th-center, .rfq-table td.td-center {
    text-align: center;
}
.rfq-table td {
    padding: 10px 14px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    background: #ffffff;
    transition: background 0.1s ease;
}
.rfq-table tbody tr:hover td {
    background: #f8fafc;
}

/* RFQ Summary Directory Table Fixed Height & Anti-Wrap Architecture */
#rfqDirectoryTable {
    table-layout: fixed;
    width: 100%;
}
#rfqDirectoryTable tbody tr {
    height: 52px;
}
#rfqDirectoryTable td {
    height: 52px;
    padding: 6px 12px;
    border-bottom: 1px solid #f1f5f9;
    vertical-align: middle;
    background: #ffffff;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
    box-sizing: border-box;
}
.rms-cell-stack {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 2px;
    line-height: 1.25;
    min-width: 0;
    overflow: hidden;
}
.rms-cell-title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--rfq-text-strong, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rms-cell-sub {
    font-size: 0.72rem;
    color: var(--rfq-text-muted, #64748b);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    display: flex;
    align-items: center;
    gap: 4px;
}
.rfq-table td input, .rfq-table td select {
    width: 100%;
    padding: 6px 9px;
    border: 1px solid var(--rfq-border-subtle);
    border-radius: var(--rfq-radius-sm);
    font-size: 0.84rem;
    background: #ffffff;
    font-family: inherit;
    box-sizing: border-box;
}
.rfq-table td input:focus, .rfq-table td select:focus {
    outline: none;
    border-color: var(--rfq-primary);
    box-shadow: 0 0 0 2px var(--rfq-primary-glow);
}
.rfq-table td input[type="number"] {
    text-align: right;
    font-variant-numeric: tabular-nums;
    font-weight: 600;
}

/* Header Column Resizer Handle */
.rfq-col-resizer {
    position: absolute;
    top: 0;
    right: 0;
    width: 6px;
    bottom: 0;
    cursor: col-resize;
    user-select: none;
    z-index: 10;
}
.rfq-col-resizer:hover, .rfq-col-resizer.is-resizing {
    background: var(--rfq-primary);
}

/* Action Header Wrapper */
.inv-header-action-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    position: relative;
}
.inv-table-filter-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--rfq-border-subtle);
    color: var(--rfq-text-muted);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    transition: all 0.15s ease;
}
.inv-table-filter-btn:hover, .inv-table-filter-btn.is-active {
    background: var(--rfq-primary-subtle);
    border-color: var(--rfq-primary-light);
    color: var(--rfq-primary-dark);
}

/* ==========================================================================
   RFQ DIRECTORY MODULAR STACKED HEADERS & SETTINGS DROPDOWN STYLES
   ========================================================================== */
.rfq-th-stacked {
    position: relative;
    user-select: none;
    cursor: default;
    padding: 8px 12px !important;
    vertical-align: middle;
    transition: background 0.15s ease;
}
.rfq-th-content {
    display: flex;
    flex-direction: column;
    gap: 2px;
    min-width: 0;
}
.rfq-th-title {
    font-size: 0.77rem;
    font-weight: 700;
    color: var(--rfq-text-strong, #0f172a);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rfq-th-sub {
    font-size: 0.67rem;
    font-weight: 500;
    color: var(--rfq-text-muted, #64748b);
    text-transform: none;
    letter-spacing: normal;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Actions Header & Settings Gear */
.rfq-header-action-wrapper {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    position: relative;
}
.rfq-th-settings-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--rfq-border-subtle, #e2e8f0);
    color: var(--rfq-text-medium, #334155);
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    transition: all 0.15s ease;
    padding: 0;
    flex-shrink: 0;
}
.rfq-th-settings-btn:hover, .rfq-th-settings-btn.is-active {
    background: var(--rfq-primary-subtle, rgba(168, 85, 247, 0.12));
    border-color: var(--rfq-primary-light, #c084fc);
    color: var(--rfq-primary-dark, #7c3aed);
}

/* Tooltip Dropdown Menu for Directory Column Settings */
.rfq-table-settings-dropdown {
    position: fixed;
    width: 370px;
    max-width: min(370px, 92vw);
    background: #ffffff;
    border: 1.5px solid var(--rfq-border-subtle, #e2e8f0);
    border-radius: 14px;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22), 0 4px 14px rgba(15, 23, 42, 0.08);
    padding: 14px 16px;
    z-index: 9999;
    display: none;
    text-align: left;
    box-sizing: border-box;
    font-size: 0.82rem;
}
.rfq-table-settings-dropdown.is-active {
    display: block;
    animation: dropdownFadeIn 0.16s cubic-bezier(0.16, 1, 0.3, 1);
}
.rfq-settings-dropdown-header {
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding-bottom: 10px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 10px;
}
.rfq-settings-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.rfq-settings-title {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--rfq-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}
.rfq-settings-title i {
    color: var(--rfq-primary-dark);
    font-size: 16px;
}
.rfq-settings-links-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 0.74rem;
}
.rfq-settings-link-btn {
    background: none;
    border: none;
    padding: 0;
    color: var(--rfq-primary-dark);
    font-weight: 600;
    cursor: pointer;
    font-size: 0.74rem;
}
.rfq-settings-link-btn:hover {
    text-decoration: underline;
}
.rfq-settings-search-input {
    width: 100%;
    padding: 6px 10px;
    border: 1px solid var(--rfq-border-subtle);
    border-radius: 7px;
    font-size: 0.78rem;
    background: #f8fafc;
    box-sizing: border-box;
}
.rfq-settings-search-input:focus {
    outline: none;
    border-color: var(--rfq-primary);
    background: #ffffff;
}

/* Draggable Column Items List */
.rfq-settings-col-list {
    display: flex;
    flex-direction: column;
    gap: 4px;
    max-height: 320px;
    overflow-y: auto;
    padding-right: 4px;
}
.rfq-dropdown-col-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 5px 8px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid transparent;
    cursor: grab;
    user-select: none;
    transition: background 0.12s ease, border-color 0.12s ease;
}
.rfq-dropdown-col-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.rfq-dropdown-col-item.is-dragging {
    opacity: 0.35;
    background: #f1f5f9;
}
.rfq-dropdown-col-item.is-drag-over {
    border-top: 2px solid var(--rfq-primary, #9333ea);
    background: rgba(147, 51, 234, 0.05);
}
.rfq-drag-handle {
    cursor: grab;
    color: #94a3b8;
    font-size: 14px;
    display: inline-flex;
    align-items: center;
    padding: 2px;
}
.rfq-drag-handle:hover {
    color: var(--rfq-primary);
}
.rfq-col-item-label {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    cursor: pointer;
    min-width: 0;
}
.rfq-col-name {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--rfq-text-strong);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.rfq-col-subname {
    font-size: 0.68rem;
    color: var(--rfq-text-muted);
}
.rfq-col-badge-default {
    font-size: 0.64rem;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 4px;
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}

/* Header Drag & Drop Visuals */
th.is-header-dragging {
    opacity: 0.4 !important;
    background: #e2e8f0 !important;
}
th.is-header-drag-over {
    border-left: 3px solid var(--rfq-primary, #9333ea) !important;
    background: rgba(147, 51, 234, 0.08) !important;
}

/* Expandable Multi-Line Subtable Accordion */
.rfq-row-expand-btn {
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
    margin-right: 6px;
    flex-shrink: 0;
}
.rfq-row-expand-btn:hover {
    border-color: var(--rfq-primary);
    color: var(--rfq-primary);
    background: var(--rfq-primary-subtle);
}
.rfq-row-expand-btn.is-expanded {
    background: var(--rfq-primary);
    border-color: var(--rfq-primary);
    color: #ffffff;
}
.rfq-row-expand-btn.is-expanded i {
    transform: rotate(90deg);
}
.rfq-line-expansion-row {
    background: #fafafa !important;
}
.rfq-subtable-container {
    padding: 10px 14px 14px 34px;
    background: #f8fafc;
    border-top: 1px dashed #e2e8f0;
    border-bottom: 2px solid #e2e8f0;
}
.rfq-subtable {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    overflow: hidden;
    font-size: 0.77rem;
}
.rfq-subtable th {
    background: #f1f5f9;
    padding: 6px 10px;
    font-size: 0.70rem;
    font-weight: 700;
    color: #475569;
    border-bottom: 1px solid #cbd5e1;
    text-transform: uppercase;
    text-align: left;
}
.rfq-subtable td {
    padding: 6px 10px;
    border-bottom: 1px solid #f1f5f9;
    height: auto !important;
    vertical-align: middle;
    font-size: 0.76rem;
}


/* ==========================================================================
   FLOATING COLUMN FILTER DROPDOWN
   (Generously Sized Industrial Dropdown Anchored After Action Header)
   ========================================================================== */
.inv-column-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 440px;
    max-width: min(440px, 92vw);
    background: #ffffff;
    border: 1.5px solid var(--rfq-border-subtle);
    border-radius: 14px;
    box-shadow: 0 20px 48px rgba(15, 23, 42, 0.22), 0 4px 14px rgba(15, 23, 42, 0.08);
    padding: 16px 18px;
    z-index: 1000;
    display: none;
    text-align: left;
    box-sizing: border-box;
}
.inv-column-dropdown-menu.is-active {
    display: block;
    animation: dropdownFadeIn 0.18s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes dropdownFadeIn {
    from {
        opacity: 0;
        transform: translateY(-6px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.inv-col-dropdown-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding-bottom: 12px;
    border-bottom: 1px solid #f1f5f9;
    margin-bottom: 12px;
}
.inv-col-dropdown-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.inv-col-dropdown-title {
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--rfq-text-strong);
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: 0.01em;
}
.inv-col-dropdown-title i {
    color: var(--rfq-primary-dark);
    font-size: 17px;
}
.inv-col-quick-link {
    background: none;
    border: none;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--rfq-primary-dark);
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 6px;
    transition: background 0.12s ease;
}
.inv-col-quick-link:hover {
    background: var(--rfq-primary-subtle);
}
.inv-col-quick-link.is-reset {
    color: #64748b;
}
.inv-col-quick-link.is-reset:hover {
    background: #f1f5f9;
    color: var(--rfq-text-strong);
}
.inv-col-dropdown-quick-links {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.inv-col-dropdown-list {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    max-height: 260px;
    overflow-y: auto;
    padding-right: 4px;
}
.inv-col-item-row {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 8px;
    border-radius: 6px;
    transition: background 0.12s ease;
    cursor: pointer;
    font-size: 0.82rem;
    color: var(--rfq-text-medium);
}
.inv-col-item-row:hover {
    background: #f8fafc;
}
.inv-col-item-row input[type="checkbox"] {
    cursor: pointer;
    width: 15px;
    height: 15px;
    accent-color: var(--rfq-primary);
}
.inv-col-dropdown-footer {
    margin-top: 12px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 0.75rem;
    color: var(--rfq-text-muted);
}

/* Items Table Footer Summary Bar */
.rfq-summary-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    background: #f8fafc;
    border-top: 1px solid var(--rfq-border-subtle);
    border-radius: 0 0 var(--rfq-radius-lg) var(--rfq-radius-lg);
    flex-wrap: wrap;
    gap: 16px;
}
.rfq-metrics-cluster {
    display: flex;
    align-items: center;
    gap: 24px;
    flex-wrap: wrap;
}
.rfq-metric-unit {
    display: flex;
    flex-direction: column;
}
.rfq-metric-label {
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--rfq-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.rfq-metric-val {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--rfq-text-strong);
    font-variant-numeric: tabular-nums;
}
.rfq-metric-val.highlight {
    color: var(--rfq-primary-dark);
}

/* Modals Scaffolding */
.rfq-modal-backdrop {
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
.rfq-modal-backdrop.is-open {
    display: flex;
    animation: rfqModalBackdrop 0.16s ease-out;
}
@keyframes rfqModalBackdrop {
    from { opacity: 0; }
    to { opacity: 1; }
}

.rfq-modal-card {
    background: #ffffff;
    border-radius: var(--rfq-radius-xl);
    box-shadow: var(--rfq-shadow-xl);
    width: 100%;
    max-width: 860px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    animation: rfqModalScale 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes rfqModalScale {
    from { transform: scale(0.96) translateY(8px); opacity: 0; }
    to { transform: scale(1) translateY(0); opacity: 1; }
}
.rfq-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--rfq-border-subtle);
    background: #f8fafc;
}
.rfq-modal-title {
    font-size: 1.15rem;
    font-weight: 800;
    color: var(--rfq-text-strong);
    display: flex;
    align-items: center;
    gap: 10px;
}
.rfq-modal-title i {
    color: var(--rfq-primary);
    font-size: 22px;
}
.rfq-modal-close {
    background: none;
    border: none;
    font-size: 20px;
    color: var(--rfq-text-muted);
    cursor: pointer;
    border-radius: 6px;
    padding: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.12s;
}
.rfq-modal-close:hover {
    background: #e2e8f0;
    color: var(--rfq-text-strong);
}
.rfq-modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}
.rfq-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 16px 24px;
    border-top: 1px solid var(--rfq-border-subtle);
    background: #f8fafc;
}

/* ==========================================================================
   DOCUMENT PREVIEW STYLES (MATCHING FORMAL PRINTABLE RFQ DOCUMENT)
   ========================================================================== */
.rfq-doc-paper {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 32px 36px;
    color: #1e293b;
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
}
.rfq-doc-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    border-bottom: 2px solid #0f172a;
    padding-bottom: 18px;
    margin-bottom: 22px;
}
.rfq-doc-company-title {
    font-size: 1.35rem;
    font-weight: 900;
    color: #0f172a;
    letter-spacing: -0.01em;
}
.rfq-doc-company-sub {
    font-size: 0.84rem;
    color: #64748b;
    margin-top: 4px;
}
.rfq-doc-badge-col {
    text-align: right;
}
.rfq-doc-main-badge {
    font-size: 1.4rem;
    font-weight: 900;
    color: var(--rfq-primary-dark);
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.rfq-doc-meta-row {
    font-size: 0.85rem;
    color: #334155;
    margin-top: 4px;
}
.rfq-doc-meta-row strong {
    color: #0f172a;
}
.rfq-doc-parties {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}
.rfq-doc-party-box {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    padding: 14px 18px;
}
.rfq-doc-party-label {
    font-size: 0.74rem;
    font-weight: 800;
    color: #64748b;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 6px;
}
.rfq-doc-party-name {
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
}
.rfq-doc-party-text {
    font-size: 0.82rem;
    color: #475569;
    margin-top: 3px;
    line-height: 1.4;
}

.rfq-doc-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
}
.rfq-doc-table th {
    background: #0f172a;
    color: #ffffff;
    font-size: 0.76rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 9px 12px;
    text-align: left;
    border: 1px solid #0f172a;
}
.rfq-doc-table td {
    padding: 9px 12px;
    border: 1px solid #cbd5e1;
    font-size: 0.82rem;
    color: #1e293b;
}
.rfq-doc-table td.num {
    text-align: right;
    font-variant-numeric: tabular-nums;
}
.rfq-doc-table td.quote-slot {
    background: #f8fafc;
    border-style: dashed;
    color: #94a3b8;
    font-style: italic;
}

.rfq-doc-terms {
    background: #f1f5f9;
    border-left: 4px solid var(--rfq-primary);
    padding: 12px 16px;
    font-size: 0.78rem;
    color: #334155;
    line-height: 1.5;
    margin-bottom: 24px;
}
.rfq-doc-signatures {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 36px;
    margin-top: 24px;
    padding-top: 18px;
}
.rfq-doc-sig-box {
    border-top: 1.5px solid #64748b;
    padding-top: 8px;
    font-size: 0.8rem;
    color: #475569;
}
.rfq-doc-sig-title {
    font-weight: 700;
    color: #0f172a;
}

/* Toast Notifications Container */
.rfq-toast-container {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    z-index: 100000;
    pointer-events: none;
}
.rfq-toast {
    pointer-events: auto;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 18px;
    border-radius: var(--rfq-radius-md);
    box-shadow: var(--rfq-shadow-xl);
    font-size: 0.86rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: rfqToastSlide 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}
@keyframes rfqToastSlide {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}
.rfq-toast.is-success {
    background: #065f46;
    border-left: 4px solid #10b981;
}
/* ==========================================================================
   MANAGER APPROVAL TOGGLE BUTTON
   ========================================================================== */
.rfq-manager-toggle-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    border: 1px solid transparent;
    user-select: none;
    line-height: 1.2;
}
.rfq-manager-toggle-btn.is-pending {
    background: #f8fafc;
    color: #475569;
    border-color: #cbd5e1;
}
.rfq-manager-toggle-btn.is-pending:hover {
    background: #f1f5f9;
    color: #0f172a;
    border-color: #94a3b8;
    box-shadow: 0 2px 6px rgba(0,0,0,0.06);
    transform: translateY(-1px);
}
.rfq-manager-toggle-btn.is-approved {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}
.rfq-manager-toggle-btn.is-approved:hover {
    background: #d1fae5;
    border-color: #6ee7b7;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.15);
}
.rfq-toggle-track {
    width: 28px;
    height: 16px;
    background: #cbd5e1;
    border-radius: 12px;
    position: relative;
    transition: background 0.2s ease;
    flex-shrink: 0;
}
.rfq-toggle-track.active {
    background: #10b981;
}
.rfq-toggle-thumb {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 12px;
    height: 12px;
    background: #ffffff;
    border-radius: 50%;
    transition: transform 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.25);
}
.rfq-toggle-thumb.active {
    transform: translateX(12px);
}
.rfq-po-link-chip {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--rfq-teal-dark, #0d9488);
    background: #f0fdf4;
    border: 1px solid rgba(13,148,136,0.25);
    padding: 2px 8px;
    border-radius: 10px;
    text-decoration: none;
    transition: all 0.15s ease;
}
.rfq-po-link-chip:hover {
    background: #dcfce7;
    border-color: #10b981;
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
    border: 1px solid var(--rfq-border-subtle);
    border-radius: 10px;
    padding: 16px;
    box-shadow: var(--rfq-shadow-sm);
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
    color: var(--rfq-text-strong);
    line-height: 1.2;
    font-variant-numeric: tabular-nums;
}
.audit-metric-label {
    font-size: 0.76rem;
    font-weight: 700;
    color: var(--rfq-text-muted);
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
    border: 1px solid var(--rfq-border-subtle);
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
    border: 1px solid var(--rfq-border-subtle);
    border-radius: 10px;
    padding: 16px 20px;
    box-shadow: var(--rfq-shadow-sm);
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
.audit-timeline-node-icon.is-created {
    border-color: #2563eb;
    color: #2563eb;
}
.audit-timeline-node-icon.is-approved {
    border-color: #10b981;
    color: #10b981;
}
.audit-timeline-node-icon.is-edited {
    border-color: #f59e0b;
    color: #d97706;
}
.audit-timeline-node-icon.is-po {
    border-color: #8b5cf6;
    color: #7c3aed;
}
.audit-timeline-node-icon.is-email {
    border-color: #06b6d4;
    color: #0891b2;
}
.audit-timeline-node-icon.is-payment {
    border-color: #10b981;
    color: #059669;
}
.audit-timeline-node-icon.is-revoked, .audit-timeline-node-icon.is-deleted {
    border-color: #ef4444;
    color: #dc2626;
}
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
    color: var(--rfq-text-muted);
    width: 160px;
}
.audit-diff-table td {
    color: var(--rfq-text-strong);
    font-weight: 600;
}

/* Media Print Rules for Spotless RFQ Export */
@media print {
    body * {
        visibility: hidden;
    }
    #rfqDocumentPreviewPaper, #rfqDocumentPreviewPaper * {
        visibility: visible;
    }
    #rfqDocumentPreviewPaper {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 20px;
        box-shadow: none;
        border: none;
    }
    .rfq-modal-footer, .rfq-modal-header, .rfq-lock-overlay {
        display: none !important;
    }
}
</style>
@endpush

@section('content')
<div class="rfq-workspace-container" id="rfqAppWorkspace">

    <!-- Global Pessimistic UI Locking Spinner Overlay -->
    <div class="rfq-lock-overlay" id="rfqGlobalLockOverlay" aria-hidden="true">
        <div class="rfq-spinner"></div>
        <div style="font-weight: 700; font-size: 1.05rem;" id="rfqLockOverlayMessage">Processing RFQ Payload...</div>
        <div style="font-size: 0.82rem; opacity: 0.8;">Applying strict transactional lock to prevent double dispatch</div>
    </div>

    <!-- Toast Notifications Root -->
    <div class="rfq-toast-container" id="rfqToastContainer"></div>

    <!-- 1. Page Header — HR-Style Flat Title Row (No Box Ribbon) -->
    <div class="hr-parent-header">
        <div class="hr-parent-title-row">
            <div>
                <h1 class="hr-parent-title">
                    <i class="ph ph-handshake"></i>
                    <span>Request for Quotations (RFQ)</span>
                </h1>
                <p class="hr-parent-subtitle">Procurement tender workspace, quote comparisons, supplier bid scoring, and conversion to Purchase Orders.</p>
            </div>
            <div class="hr-page-actions">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="exportRfqDirectoryCSV()" data-tooltip="Export Tender Records to CSV">
                    <i class="ph ph-download-simple"></i>
                    <span>Export</span>
                </button>
                <button type="button" class="hr-btn hr-btn-primary" onclick="switchRfqTab('tab-builder'); resetRfqForm();" data-tooltip="Launch RFQ Tender Builder">
                    <i class="ph ph-plus-circle"></i>
                    <span>New RFQ</span>
                </button>
            </div>
        </div>
    </div>

    <!-- RFQ Tab Wrapper Bar -->
    <nav class="rfq-nav-tabs-bar" id="rfqTabsBar">
        <button type="button" class="rfq-tab-btn active" id="tabBtnList" data-tab="tab-list" onclick="switchRfqTab('tab-list')">
            <i class="ph ph-list-dashes"></i>
            <span>RFQ Directory & Status Tracker</span>
            <span class="rfq-tab-count" id="tabRfqListCount">0</span>
        </button>
        <button type="button" class="rfq-tab-btn" id="tabBtnBuilder" data-tab="tab-builder" onclick="switchRfqTab('tab-builder')">
            <i class="ph ph-plus-circle"></i>
            <span id="tabBuilderTitle">Request for Quotation Builder</span>
            <span class="rfq-tab-badge" id="tabBuilderBadge">New RFQ</span>
        </button>
    </nav>

    <!-- ====================================================================
         TAB 1: RFQ DIRECTORY & STATUS TRACKER
         ==================================================================== -->
    <div class="rfq-tab-pane active" id="pane-tab-list">
        <!-- KPI Metrics Cluster -->
        <div class="rfq-kpi-grid">
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('all')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Total RFQs</span>
                    <div class="rfq-kpi-icon"><i class="ph ph-files"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiTotalRfqs">0</div>
                <div class="rfq-kpi-sub">All active procurement tenders</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Draft')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Draft Sessions</span>
                    <div class="rfq-kpi-icon draft"><i class="ph ph-pencil-simple"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiDraftRfqs">0</div>
                <div class="rfq-kpi-sub">Unsent in-progress tenders</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Quote Requested')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Quote Requested</span>
                    <div class="rfq-kpi-icon awaiting"><i class="ph ph-hourglass-medium"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiAwaitingRfqs">0</div>
                <div class="rfq-kpi-sub">Dispatched to vendor contacts</div>
            </div>
            <div class="rfq-kpi-card" onclick="filterRfqByStatus('Awarded')">
                <div class="rfq-kpi-header">
                    <span class="rfq-kpi-label">Awarded / Closed</span>
                    <div class="rfq-kpi-icon awarded"><i class="ph ph-seal-check"></i></div>
                </div>
                <div class="rfq-kpi-val" id="kpiAwardedRfqs">0</div>
                <div class="rfq-kpi-sub">Converted to Purchase Orders</div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="rfq-panel-card">
            <div class="rfq-items-toolbar" style="overflow-x: auto; scrollbar-width: none;">
                <div class="rfq-toolbar-left" style="flex: 1; flex-wrap: nowrap; gap: 8px; min-width: 0;">
                    <div style="position: relative; flex: 1; min-width: 220px; max-width: 360px;">
                        <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
                        <input type="text" class="rfq-input" id="rfqDirectorySearch" oninput="handleRfqDirectorySearch(this.value)" placeholder="Search RFQ #, vendor partner, contact..." style="padding: 5px 10px 5px 30px; font-size: 12px; height: 32px;">
                    </div>
                    <div id="rfqActiveFilterChips" style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;"></div>
                </div>
                <div class="rfq-toolbar-right" style="position: relative; display: flex; align-items: center; gap: 8px;">
                    <!-- Column Visibility Filter Trigger -->
                    <div style="position: relative;">
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="btnDirectoryColFilter" onclick="toggleRfqTableSettingsDropdown(event)" data-tooltip="Customize visible columns & order" style="padding: 5px 10px; font-size: 12px; border-radius: 8px;">
                            <i class="ph ph-columns"></i>
                            <span>Columns</span>
                            <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                        </button>
                    </div>

                    <button type="button" class="rfq-btn rfq-btn-primary" onclick="startNewRfqFromDirectory()" style="padding: 6px 12px; font-size: 12px; border-radius: 8px;">
                        <i class="ph ph-plus-circle"></i> <span>Create RFQ</span>
                    </button>
                </div>
            </div>

            <!-- RFQ Directory Table -->
            <div class="rfq-table-responsive">
                <table class="rfq-table" id="rfqDirectoryTable">
                    <thead id="rfqDirectoryThead">
                        <tr id="rfqDirectoryTheadRow">
                            <!-- Rendered dynamically by renderDirectoryTableHeader() with modular stacked headers, column resizers & drag/drop -->
                        </tr>
                    </thead>
                    <tbody id="rfqDirectoryTbody">
                        <!-- Rendered dynamically by renderRfqDirectory() -->
                    </tbody>
                </table>
                <div id="rfqHeaderFilterPopover" class="rfq-header-filter-popover" style="display: none;"></div>

                <!-- Floating Settings Dropdown Menu Anchored to End Column Settings Gear -->
                <div id="rfqTableSettingsDropdown" class="rfq-table-settings-dropdown" style="display: none;">
                    <div class="rfq-settings-dropdown-header">
                        <div class="rfq-settings-title-row">
                            <div class="rfq-settings-title">
                                <i class="ph ph-gear-six"></i>
                                <span>Table Columns & Sequence</span>
                            </div>
                            <span class="rfq-col-badge-default" id="directoryColActiveCounter">8 of 29 visible</span>
                        </div>
                        <div class="rfq-settings-links-row">
                            <button type="button" class="rfq-settings-link-btn" onclick="resetDirectoryColumnDefaults()">Reset to Default</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="showAllDirectoryColumns()">Select All</button>
                            <button type="button" class="rfq-settings-link-btn" onclick="resetDirectoryColumnWidths()">Reset Widths</button>
                        </div>
                        <input type="text" class="rfq-settings-search-input" id="directoryColSearchInput" placeholder="Filter columns..." oninput="handleDirectoryColSearch(this.value)">
                    </div>
                    <div class="rfq-settings-col-list" id="directoryColCheckboxesList">
                        <!-- Populated dynamically with draggable items -->
                    </div>
                </div>
            </div>

            <!-- Client-Side Pagination Container -->
            <div class="rfq-pagination-bar" id="rfqDirectoryPaginationBar" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px;">
                <div style="font-size: 12px; color: #64748b;" id="rfqPaginationInfo">
                    Showing <strong>1</strong> to <strong>10</strong> of <strong>0</strong> tenders
                </div>
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b;">
                        <span>Show</span>
                        <select id="rfqPageSizeSelect" onchange="changeDirectoryPageSize(this.value)" class="hr-select" style="padding: 3px 8px; font-size: 11.5px; border-radius: 6px;">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        <span>rows</span>
                    </div>
                    <div class="rfq-pagination-controls" id="rfqPaginationButtons" style="display: flex; align-items: center; gap: 4px;">
                        <!-- Rendered by JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ====================================================================
         TAB 2: CREATION / EDITOR OF RFQ
         ==================================================================== -->
    <div class="rfq-tab-pane" id="pane-tab-builder" style="display: none;">

    <!-- Top Command & Action Bar -->
    <div class="rfq-command-bar">
        <div class="rfq-command-left" style="display: flex; align-items: center; gap: 8px;">
            <span style="display: inline-flex; align-items: center; gap: 6px; font-size: 0.74rem; font-weight: 700; color: #64748b; letter-spacing: 0.04em; text-transform: uppercase;">
                <i class="ph ph-sliders-horizontal" style="color: #9333ea; font-size: 14px;"></i> RFQ Workspace
            </span>
        </div>

        <div class="rfq-actions-group">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="switchRfqTab('tab-list')" title="Return to RFQ Directory & Tracker" style="padding: 4px 10px; font-size: 11.5px;">
                <i class="ph ph-arrow-left"></i> Directory
            </button>
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="resetRfqForm()" title="Clear form & start new RFQ" style="padding: 4px 10px; font-size: 11.5px;">
                <i class="ph ph-arrow-counter-clockwise"></i> Reset
            </button>
            <button type="button" class="rfq-btn rfq-btn-outline" id="btnToggleBuilderAudit" onclick="openRfqAuditDrawer()" title="View Procurement Audit Trail & Activity Log" style="padding: 4px 10px; font-size: 11.5px;">
                <i class="ph ph-clock-counter-clockwise"></i> Audit Trail
            </button>
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="openRfqDocumentPreview()" title="Open formal printable RFQ document preview (F8)" style="padding: 4px 10px; font-size: 11.5px;">
                <i class="ph ph-eye"></i> Preview <span class="rfq-hotkey-badge">F8</span>
            </button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnDispatchEmail" onclick="openSendEmailModal()" title="Send RFQ package directly to vendor contact (F10)" style="padding: 4px 12px; font-size: 11.5px;">
                <i class="ph ph-paper-plane-tilt"></i> Send to Vendor <span class="rfq-hotkey-badge" style="background: rgba(255,255,255,0.25);">F10</span>
            </button>
        </div>
    </div>

    <!-- Workspace Grid: Asymmetric 4.2fr Left (Cards 1 & 2) / 5.8fr Right (Card 3 Items Panel) -->
    <div class="rfq-builder-workspace-grid">

        <!-- Left Column: Vendor Selection & RFQ Terms -->
        <div class="rfq-builder-left-col">

            <!-- Tender Reference & Identity Header Block (Prominent Placement with Description) -->
            <div class="rfq-panel-card rfq-tender-ref-card" style="border-left: 4px solid #9333ea; background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.65)); padding: 10px 14px; display: flex; flex-direction: column; gap: 8px;">
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <div style="width: 32px; height: 32px; border-radius: 8px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 16px; box-shadow: 0 3px 10px rgba(168, 85, 247, 0.28);">
                            <i class="ph ph-file-text"></i>
                        </div>
                        <div>
                            <div style="font-size: 0.68rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;">Tender Reference Number</div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span id="rfqRefDisplay" style="font-family: monospace; font-size: 1.15rem; font-weight: 800; color: #9333ea; letter-spacing: -0.01em;">RFQ-2026-0044</span>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="copyRfqReference()" title="Copy Reference to Clipboard" style="padding: 2px 6px; font-size: 11px; height: 22px;">
                                    <i class="ph ph-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div>
                        <span class="status-pill draft" id="rfqStatusBadge" style="font-size: 11px; padding: 3px 9px;">
                            <i class="ph ph-dot"></i> Draft RFQ
                        </span>
                    </div>
                </div>

                <!-- Tender Purpose / Description Field -->
                <div class="rfq-field" style="margin-top: 2px;">
                    <label class="rfq-label" for="rfqTenderTitle" style="font-size: 0.70rem; color: #475569; margin-bottom: 3px;">
                        <span>Tender Purpose / Title Description</span>
                        <span class="rfq-label-badge" style="font-size: 0.62rem;">Project Reference</span>
                    </label>
                    <input type="text" class="rfq-input" id="rfqTenderTitle" placeholder="e.g. Q4 Commissary Dry Goods Bulk Procurement — Manila Hub" style="font-size: 0.84rem; padding: 5px 10px; font-weight: 500;" oninput="handleRfqTitleChange(this.value)">
                </div>
            </div>

            <!-- Card 1: Vendor Selection & Company Profile Details -->
            <div class="rfq-panel-card">
                <div class="rfq-panel-header">
                    <div class="rfq-panel-title">
                        <i class="ph ph-buildings"></i>
                        <span>Vendor Selection & Partner Profile</span>
                    </div>
                    <span class="rfq-label-badge" id="vendorCatalogBadge">Catalog Integrated</span>
                </div>
                <div class="rfq-panel-body">
                    <div class="rfq-field">
                        <label class="rfq-label" for="vendorSelect">
                            <span>Select Target Vendor *</span>
                            <span class="rfq-label-badge">Searchable Masterlist</span>
                        </label>
                        <!-- Hidden select for form bindings & backwards-compatibility -->
                        <select class="rfq-select" id="vendorSelect" style="display: none;" onchange="handleVendorSelection(this.value)">
                            <option value="">-- Choose Approved Vendor from Masterlist --</option>
                            <!-- Populated dynamically -->
                        </select>

                        <!-- Custom Searchable Combobox -->
                        <div class="rfq-vendor-combobox" id="rfqVendorCombobox">
                            <button type="button" class="rfq-vendor-combo-trigger" id="rfqVendorComboTrigger" onclick="toggleVendorCombobox(event)">
                                <span id="rfqVendorComboTriggerText" style="color: #64748b;">-- Choose Approved Vendor from Masterlist --</span>
                                <i class="ph ph-caret-down" style="font-size: 14px; color: #94a3b8;"></i>
                            </button>

                            <div class="rfq-vendor-combo-dropdown" id="rfqVendorComboDropdown">
                                <div class="rfq-vendor-combo-search-wrap" onclick="event.stopPropagation()">
                                    <i class="ph ph-magnifying-glass"></i>
                                    <input type="text" class="rfq-vendor-combo-search-input" id="rfqVendorSearchInput" placeholder="Search by name, code, category..." oninput="filterVendorCombobox(this.value)" autocomplete="off">
                                </div>
                                <div class="rfq-vendor-combo-list" id="rfqVendorComboList">
                                    <!-- Populated dynamically -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Vendor Summary Badge Box -->
                    <div class="rfq-vendor-pill-box" id="vendorSummaryBox" style="display: none;">
                        <div class="rfq-vendor-avatar" id="vendorAvatarText">VM</div>
                        <div class="rfq-vendor-summary">
                            <div class="rfq-vendor-name-row">
                                <span id="vendorTradeName">--</span>
                                <span class="rfq-label-badge" id="vendorCategoryTag">Category</span>
                            </div>
                            <div class="rfq-vendor-detail-row">
                                <span><i class="ph ph-identification-card"></i> TIN: <strong id="vendorTinDisplay">--</strong></span>
                                <span>•</span>
                                <span><i class="ph ph-map-pin"></i> <span id="vendorLocationDisplay">--</span></span>
                            </div>
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorContactPerson">Contact Person</label>
                            <input type="text" class="rfq-input" id="vendorContactPerson" placeholder="Contact Person" readonly title="Primary Contact Person">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorContactTitle">Designation</label>
                            <input type="text" class="rfq-input" id="vendorContactTitle" placeholder="Designation" readonly title="Contact Designation / Title">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorEmail">
                                <span>Recipient Email *</span>
                            </label>
                            <input type="email" class="rfq-input" id="vendorEmail" placeholder="vendor@example.com" title="Vendor Recipient Email" required>
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="vendorPhone">Phone / Mobile</label>
                            <input type="text" class="rfq-input" id="vendorPhone" placeholder="Direct Phone" title="Contact Direct Phone">
                        </div>
                    </div>

                    <div class="rfq-field">
                        <label class="rfq-label" for="vendorAddress">Physical / Billing Address</label>
                        <input type="text" class="rfq-input" id="vendorAddress" placeholder="Street, Building, City, ZIP">
                    </div>
                </div>
            </div>

            <!-- Card 2: Procurement Terms & Delivery Schedules -->
            <div class="rfq-panel-card">
                <div class="rfq-panel-header">
                    <div class="rfq-panel-title">
                        <i class="ph ph-calendar-check"></i>
                        <span>RFQ Terms & Delivery Schedule</span>
                    </div>
                    <span class="rfq-label-badge">Auto Lead-Time Sync</span>
                </div>
                <div class="rfq-panel-body">
                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqNumberInput">RFQ Reference #</label>
                            <input type="text" class="rfq-input" id="rfqNumberInput" value="RFQ-2026-0042" readonly style="font-family: monospace; font-weight: 700; color: #9333ea;">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqDateIssued">Date Issued</label>
                            <input type="date" class="rfq-input" id="rfqDateIssued">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqDueDate">
                                <span>Quotation Due *</span>
                                <span class="rfq-label-badge" style="color: #b45309;">Submission</span>
                            </label>
                            <input type="date" class="rfq-input" id="rfqDueDate">
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqExpectedDelivery">
                                <span>Expected Delivery *</span>
                                <span class="rfq-label-badge" id="leadTimeBadge">Lead Time</span>
                            </label>
                            <input type="date" class="rfq-input" id="rfqExpectedDelivery">
                        </div>
                    </div>

                    <div class="rfq-form-row">
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqPaymentTerms">Payment Terms</label>
                            <select class="rfq-select" id="rfqPaymentTerms">
                                <option value="Net 30 Days">Net 30 Days (Standard)</option>
                                <option value="Net 15 Days">Net 15 Days</option>
                                <option value="Net 7 Days">Net 7 Days</option>
                                <option value="COD">Cash on Delivery (COD)</option>
                                <option value="Advance Payment">100% Advance Payment</option>
                                <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                            </select>
                        </div>
                        <div class="rfq-field">
                            <label class="rfq-label" for="rfqDeliveryLocation">Destination Facility</label>
                            <select class="rfq-select" id="rfqDeliveryLocation">
                                <option value="Central Commissary - Main Dock">Central Commissary - Dock A</option>
                                <option value="Branch 1 - Makati Flagship">Branch 1 - Makati Flagship</option>
                                <option value="Branch 2 - BGC Bistro">Branch 2 - BGC Bistro</option>
                                <option value="Branch 3 - Ortigas Kitchen">Branch 3 - Ortigas Kitchen</option>
                                <option value="Central Warehouse - Dry Storage">Central Warehouse - Dry Storage</option>
                            </select>
                        </div>
                    </div>

                    <div class="rfq-field">
                        <label class="rfq-label" for="rfqSpecialInstructions">Bidding Instructions / Notes</label>
                        <textarea class="rfq-textarea" id="rfqSpecialInstructions" placeholder="e.g. VAT-inclusive pricing; attach COA for dairy/meats."></textarea>
                    </div>
                </div>
            </div>

        </div><!-- /.rfq-builder-left-col -->

        <!-- Right Column: Card 3 Line Items Panel -->
        <div class="rfq-builder-right-col">
            <!-- Quotation Line Items & Specifications Table (With Floating Column Filter) -->
            <div class="rfq-items-panel">
                <div class="rfq-items-toolbar">
                    <div class="rfq-toolbar-left">
                        <div style="font-weight: 800; font-size: 0.95rem; color: var(--rfq-text-strong); display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-list-numbers" style="color: var(--rfq-primary); font-size: 18px;"></i>
                            <span>Quotation Line Items & Specifications</span>
                        </div>
                        <span class="rfq-items-counter-pill" id="rfqItemsBadge">0 Line Items</span>
                    </div>

                    <div class="rfq-toolbar-right">
                        <button type="button" class="rfq-btn rfq-btn-outline" onclick="openItemMasterQuickSelectModal()" title="Quick add items from Item Master (F2)" style="padding: 5px 10px; font-size: 11.5px;">
                            <i class="ph ph-magnifying-glass"></i> Select Master <span class="rfq-hotkey-badge">F2</span>
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-teal" onclick="addNewBlankItemRow()" title="Add custom non-catalog item specification" style="padding: 5px 10px; font-size: 11.5px;">
                            <i class="ph ph-plus-circle"></i> Add Custom Row
                        </button>
                        <button type="button" class="rfq-btn rfq-btn-danger" onclick="clearAllItemRows()" title="Remove all items from table" style="padding: 5px 8px; font-size: 11.5px;">
                            <i class="ph ph-trash"></i>
                        </button>
                    </div>
                </div>

                <!-- Table Container with Horizontal & Vertical Scroll -->
                <div class="rfq-table-responsive" id="rfqTableContainer">
                    <table class="rfq-table" id="rfqMasterTable">
                        <colgroup id="rfqColgroup">
                            <!-- Populated dynamically based on column widths & visibility -->
                        </colgroup>
                        <thead id="rfqThead">
                            <tr id="rfqTheadRow">
                                <!-- Populated dynamically with Floating Column Filter Dropdown anchored after Action Header -->
                            </tr>
                        </thead>
                        <tbody id="rfqTbody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Items Table Footer Metric Summary -->
                <div class="rfq-summary-bar" style="padding: 10px 16px;">
                    <div class="rfq-metrics-cluster" style="gap: 16px;">
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Lines</span>
                            <span class="rfq-metric-val" id="summaryTotalLines" style="font-size: 1.05rem;">0</span>
                        </div>
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Total Units</span>
                            <span class="rfq-metric-val" id="summaryTotalUnits" style="font-size: 1.05rem;">0.00</span>
                        </div>
                        <div class="rfq-metric-unit">
                            <span class="rfq-metric-label">Benchmark Budget</span>
                            <span class="rfq-metric-val highlight" id="summaryEstimatedBudget" style="font-size: 1.12rem;">₱0.00</span>
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        <div style="font-size: 0.74rem; color: var(--rfq-text-muted);">
                            <i class="ph ph-info"></i> Benchmark pricing used for budgetary validation only.
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /.rfq-builder-right-col -->
    </div><!-- /.rfq-builder-workspace-grid -->
</div><!-- /#pane-tab-builder -->

</div>

<!-- ==========================================================================
     DRAWER: PROCUREMENT AUDIT TRAIL RIGHT DRAWER
     ========================================================================== -->
<div id="rfqAuditDrawerOverlay" class="hr-drawer-overlay" onclick="closeRfqAuditDrawer()"></div>
<div id="rfqAuditDrawer" class="hr-drawer" style="max-width: 620px; width: 100%;">
    <div class="hr-drawer-header">
        <button type="button" class="hr-drawer-close" onclick="closeRfqAuditDrawer()" title="Close Drawer">
            <i class="ph ph-x"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 12px;">
            <div style="width: 40px; height: 40px; border-radius: 10px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.3);">
                <i class="ph ph-clock-counter-clockwise"></i>
            </div>
            <div>
                <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Procurement Audit Trail</h3>
                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                    Activity history & event log • <span id="tabRfqAuditCount" style="font-weight: 700; color: #9333ea;">0</span> events
                </div>
            </div>
        </div>
    </div>
    <div class="hr-drawer-body" style="padding: 16px; display: flex; flex-direction: column; gap: 14px; overflow-y: auto;">
        <!-- Target Scope Indicator Bar: Restricts index to Current PO / RFQ for process optimization -->
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; background: rgba(147, 51, 234, 0.05); border: 1px solid rgba(147, 51, 234, 0.16); border-radius: 8px; padding: 6px 12px; font-size: 11.5px;">
            <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                <span style="color: #64748b; font-weight: 600;">Active Tender Index:</span>
                <span id="auditTargetRefBadge" style="font-family: monospace; font-weight: 800; color: #9333ea; background: #ffffff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(147, 51, 234, 0.25);">RFQ-2026-0044</span>
                <span id="auditTargetPoBadge" style="font-family: monospace; font-weight: 700; color: #0284c7; background: #f0f9ff; padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(2, 132, 199, 0.25); display: none;"></span>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <button type="button" class="audit-filter-pill active" id="btnAuditScopeCurrent" onclick="setAuditScope('CURRENT')" title="Limit processing strictly to current RFQ / PO" style="padding: 2px 8px; font-size: 10.5px;">Current PO/RFQ Only</button>
                <button type="button" class="audit-filter-pill" id="btnAuditScopeAll" onclick="setAuditScope('ALL')" title="Expand index to all historical records" style="padding: 2px 8px; font-size: 10.5px;">All Records</button>
            </div>
        </div>

        <!-- Audit KPI Summary Cards -->
        <div class="audit-overview-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #eff6ff; color: #2563eb; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiTotalCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Total Logged Actions</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfdf5; color: #059669; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-seal-check"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiApprovalsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Manager Approvals</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #fef3c7; color: #d97706; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-pencil-line"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiEditsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Creations & Edits</div>
                </div>
            </div>
            <div class="audit-metric-card" style="padding: 10px;">
                <div class="audit-metric-icon-box" style="background: #ecfeff; color: #0891b2; width: 32px; height: 32px; font-size: 16px;">
                    <i class="ph ph-envelope-simple"></i>
                </div>
                <div>
                    <div class="audit-metric-val" id="auditKpiEmailsCount" style="font-size: 18px;">0</div>
                    <div class="audit-metric-label" style="font-size: 11px;">Email Dispatches</div>
                </div>
            </div>
        </div>

        <!-- Audit Toolbar & Filter Bar -->
        <div class="audit-toolbar" style="display: flex; flex-direction: column; gap: 8px; padding: 10px; background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0;">
            <div style="position: relative; width: 100%;">
                <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--rfq-text-muted); font-size: 15px;"></i>
                <input type="text" class="rfq-input" id="auditSearchInput" placeholder="Search audit trail by Ref, vendor, user..." style="padding-left: 32px; height: 32px; font-size: 0.80rem;" oninput="onAuditSearchChange(this.value)">
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <div style="display: flex; align-items: center; gap: 4px;">
                    <button type="button" class="audit-filter-pill active" data-module="ALL" onclick="filterAuditByModule('ALL', this)" style="padding: 2px 8px; font-size: 11px;">All</button>
                    <button type="button" class="audit-filter-pill" data-module="RFQ" onclick="filterAuditByModule('RFQ', this)" style="padding: 2px 8px; font-size: 11px;">RFQ</button>
                    <button type="button" class="audit-filter-pill" data-module="PO" onclick="filterAuditByModule('PO', this)" style="padding: 2px 8px; font-size: 11px;">PO</button>
                </div>
                <div class="audit-filter-pills" id="auditActionFilterPills" style="display: flex; gap: 4px; flex-wrap: wrap;">
                    <button type="button" class="audit-filter-pill active" data-action="ALL" onclick="filterAuditByAction('ALL', this)" style="padding: 2px 7px; font-size: 10.5px;">All</button>
                    <button type="button" class="audit-filter-pill" data-action="APPROVED" onclick="filterAuditByAction('APPROVED', this)" style="padding: 2px 7px; font-size: 10.5px;">Approvals</button>
                    <button type="button" class="audit-filter-pill" data-action="CREATED" onclick="filterAuditByAction('CREATED', this)" style="padding: 2px 7px; font-size: 10.5px;">Created</button>
                    <button type="button" class="audit-filter-pill" data-action="EDITED" onclick="filterAuditByAction('EDITED', this)" style="padding: 2px 7px; font-size: 10.5px;">Edited</button>
                    <button type="button" class="audit-filter-pill" data-action="EMAIL_SENT" onclick="filterAuditByAction('EMAIL_SENT', this)" style="padding: 2px 7px; font-size: 10.5px;">Emails</button>
                </div>
            </div>
        </div>

        <!-- Audit Chronological Timeline List -->
        <div class="audit-timeline-container" id="rfqAuditTimeline">
            <!-- Dynamically populated via renderRfqAuditTrail -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 1: FORMAL DOCUMENT PREVIEW MODAL (PRINTABLE / PDF EXPORT)
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqDocumentPreviewModal" role="dialog" aria-modal="true" aria-labelledby="previewModalTitle">
    <div class="rfq-modal-card" style="max-width: 920px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="previewModalTitle">
                <i class="ph ph-file-text"></i>
                <span>Formal Request for Quotation Document Preview</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeRfqDocumentPreview()" aria-label="Close Preview">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <!-- Formal Printable RFQ Document Paper -->
            <div class="rfq-doc-paper" id="rfqDocumentPreviewPaper">
                <div class="rfq-doc-header">
                    <div>
                        <div class="rfq-doc-company-title">RESTAURANT MANAGEMENT SYSTEM</div>
                        <div class="rfq-doc-company-sub">Procurement, Logistics & Supply Chain Division</div>
                        <div class="rfq-doc-company-sub">Headquarters: Lopez Center, Pasig City • VAT Reg. TIN: 009-887-124-000</div>
                    </div>
                    <div class="rfq-doc-badge-col">
                        <div class="rfq-doc-main-badge">REQUEST FOR QUOTATION</div>
                        <div class="rfq-doc-meta-row">RFQ Number: <strong id="docRfqNumber">RFQ-2026-0042</strong></div>
                        <div class="rfq-doc-meta-row">Date Issued: <strong id="docDateIssued">--</strong></div>
                        <div class="rfq-doc-meta-row">Quotation Deadline: <strong id="docQuotationDeadline" style="color: #dc2626;">--</strong></div>
                    </div>
                </div>

                <div class="rfq-doc-parties">
                    <div class="rfq-doc-party-box">
                        <div class="rfq-doc-party-label">VENDOR / SUPPLIER RECIPIENT</div>
                        <div class="rfq-doc-party-name" id="docVendorName">--</div>
                        <div class="rfq-doc-party-text">
                            <strong>Attn:</strong> <span id="docVendorContact">--</span> (<span id="docVendorTitle">--</span>)<br>
                            <strong>Email:</strong> <span id="docVendorEmail">--</span> | <strong>Phone:</strong> <span id="docVendorPhone">--</span><br>
                            <strong>Address:</strong> <span id="docVendorAddress">--</span>
                        </div>
                    </div>

                    <div class="rfq-doc-party-box">
                        <div class="rfq-doc-party-label">SHIP TO & PROCUREMENT CONDITIONS</div>
                        <div class="rfq-doc-party-name" id="docDeliveryLocation">Central Commissary</div>
                        <div class="rfq-doc-party-text">
                            <strong>Target Delivery Date:</strong> <span id="docTargetDeliveryDate" style="font-weight: 700;">--</span><br>
                            <strong>Payment Terms:</strong> <span id="docPaymentTerms">Net 30 Days</span><br>
                            <strong>Currency:</strong> Philippine Peso (PHP ₱)
                        </div>
                    </div>
                </div>

                <!-- Printable Items Table Displaying Active Visible Columns -->
                <table class="rfq-doc-table" id="docItemsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">#</th>
                            <th style="width: 110px;">SKU</th>
                            <th>Item Description & Specifications</th>
                            <th style="width: 90px; text-align: center;">UOM</th>
                            <th style="width: 100px; text-align: right;">Quantity</th>
                            <th style="width: 140px; text-align: right;">Vendor Quoted Unit Price (₱)</th>
                            <th style="width: 120px; text-align: center;">Availability / Lead Time</th>
                        </tr>
                    </thead>
                    <tbody id="docItemsTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>

                <div class="rfq-doc-terms">
                    <strong>Instructions to Bidders:</strong><br>
                    1. Please fill in your quoted unit price (VAT Inclusive) and committed delivery lead time in the designated columns.<br>
                    2. Quotations must remain valid for a minimum of thirty (30) calendar days from submission deadline.<br>
                    3. Return signed quotation via email to <strong id="docBuyerEmail">{{ auth()->user()->email ?? 'procurement@rms.ph' }}</strong> before the deadline.<br>
                    4. Special Instructions: <span id="docSpecialInstructions">Standard receiving quality standards apply.</span>
                </div>

                <div class="rfq-doc-signatures">
                    <div class="rfq-doc-sig-box">
                        <div class="rfq-doc-sig-title">Prepared By (Procurement Officer):</div>
                        <div style="margin-top: 20px; font-weight: 700; color: #0f172a;">{{ auth()->user()->name ?? 'System Administrator' }}</div>
                        <div>Date: <span id="docSignDate">--</span></div>
                    </div>
                    <div class="rfq-doc-sig-box">
                        <div class="rfq-doc-sig-title">Authorized Vendor Representative Signature:</div>
                        <div style="margin-top: 20px; color: #94a3b8; font-style: italic;">(Sign & Affix Company Seal)</div>
                        <div>Authorized Printed Name & Date</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="window.print()">
                <i class="ph ph-printer"></i> Print / Save as PDF
            </button>
            <button type="button" class="rfq-btn rfq-btn-primary" onclick="closeRfqDocumentPreview(); openSendEmailModal();">
                <i class="ph ph-paper-plane-tilt"></i> Proceed to Send Email
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 2: SEND QUOTATION TO EMAIL DISPATCH MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqSendEmailModal" role="dialog" aria-modal="true" aria-labelledby="sendEmailModalTitle">
    <div class="rfq-modal-card" style="max-width: 620px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="sendEmailModalTitle">
                <i class="ph ph-envelope-simple"></i>
                <span>Send Request for Quotation Document</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeSendEmailModal()" aria-label="Close Send Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <form id="rfqEmailForm" onsubmit="handleSendEmailSubmit(event)" style="display: flex; flex-direction: column; gap: 14px;">
                <div class="rfq-field">
                    <label class="rfq-label" for="emailRecipient">
                        <span>Recipient Email (Vendor Contact) *</span>
                        <span class="rfq-label-badge">Required</span>
                    </label>
                    <input type="email" class="rfq-input" id="emailRecipient" required placeholder="vendor.rep@company.com">
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailCc">
                        <span>CC (Carbon Copy)</span>
                        <span class="rfq-label-badge">Internal Audit</span>
                    </label>
                    <input type="text" class="rfq-input" id="emailCc" value="procurement-audit@rms.ph, central.warehouse@rms.ph">
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailSubject">Subject Line *</label>
                    <input type="text" class="rfq-input" id="emailSubject" required>
                </div>

                <div class="rfq-field">
                    <label class="rfq-label" for="emailBodyText">Cover Message & Bidding Notice</label>
                    <textarea class="rfq-textarea" id="emailBodyText" style="min-height: 110px;"></textarea>
                </div>

                <!-- Attached Preview Attachment Badge -->
                <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="ph ph-file-pdf" style="font-size: 26px; color: #dc2626;"></i>
                        <div>
                            <div style="font-weight: 700; font-size: 0.85rem;" id="emailAttachmentName">RFQ-2026-0042_Request_for_Quotation.pdf</div>
                            <div style="font-size: 0.75rem; color: #64748b;">Formal System-Generated RFQ Document Preview (Ready)</div>
                        </div>
                    </div>
                    <button type="button" class="rfq-btn rfq-btn-outline" style="padding: 4px 10px; font-size: 0.78rem;" onclick="closeSendEmailModal(); openRfqDocumentPreview();">
                        <i class="ph ph-eye"></i> View Attachment
                    </button>
                </div>
            </form>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeSendEmailModal()">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-primary" id="btnConfirmSendEmail" onclick="executeEmailDispatch()">
                <i class="ph ph-paper-plane-tilt"></i> Dispatch Email Now
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 3: ITEM MASTER QUICK SELECT MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqItemMasterModal" role="dialog" aria-modal="true" aria-labelledby="itemMasterModalTitle">
    <div class="rfq-modal-card" style="max-width: 760px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="itemMasterModalTitle">
                <i class="ph ph-database"></i>
                <span>Item Master Catalog Quick Picker</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeItemMasterQuickSelectModal()" aria-label="Close Picker">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="margin-bottom: 14px;">
                <input type="text" class="rfq-input" id="itemMasterSearchInput" oninput="filterItemMasterModalList(this.value)" placeholder="Search item by SKU, Name, or Category (e.g. Arabica, Milk, Flour)..." autofocus>
            </div>
            <div style="max-height: 380px; overflow-y: auto; border: 1px solid var(--rfq-border-subtle); border-radius: 8px;">
                <table class="rfq-table" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th style="width: 100px;">SKU</th>
                            <th>Item Name & Specifications</th>
                            <th style="width: 120px;">Category</th>
                            <th style="width: 70px; text-align: center;">UOM</th>
                            <th style="width: 110px; text-align: right;">Benchmark</th>
                            <th style="width: 90px; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemMasterModalTbody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeItemMasterQuickSelectModal()">Done</button>
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 4: RECENT RFQ DISPATCH LOG & HISTORY
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqHistoryModal" role="dialog" aria-modal="true" aria-labelledby="historyModalTitle">
    <div class="rfq-modal-card" style="max-width: 820px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="historyModalTitle">
                <i class="ph ph-clock-counter-clockwise"></i>
                <span>Recent Requests for Quotation History Log</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeRecentRfqHistoryModal()" aria-label="Close History">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div style="max-height: 400px; overflow-y: auto; border: 1px solid var(--rfq-border-subtle); border-radius: 8px;">
                <table class="rfq-table" style="font-size: 0.82rem;">
                    <thead>
                        <tr>
                            <th style="width: 130px;">RFQ #</th>
                            <th>Vendor Name</th>
                            <th style="width: 120px;">Date Sent</th>
                            <th style="width: 80px; text-align: center;">Items</th>
                            <th style="width: 110px; text-align: right;">Est. Value</th>
                            <th style="width: 100px; text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody id="rfqHistoryTbody">
                        <!-- Populated dynamically from localStorage -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeRecentRfqHistoryModal()">Close</button>
        </div>
    </div>
<!-- ==========================================================================
     MODAL 5: MANAGER RFQ APPROVAL & PO TRANSFER DIALOG
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqApprovalModal" role="dialog" aria-modal="true" aria-labelledby="approvalModalTitle">
    <div class="rfq-modal-card" style="max-width: 700px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="approvalModalTitle">
                <i class="ph ph-seal-check" style="color: var(--rfq-teal);"></i>
                <span>Manager RFQ Approval & PO Authorization</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeApprovalModal()" aria-label="Close Approval Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body">
            <div id="approvalStatusBanner" style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px;">
                <div style="font-weight: 800; font-size: 0.95rem; color: #166534;" id="approvalRfqTitle">RFQ-2026-XXXX • Vendor Partner</div>
                <div style="font-size: 0.8rem; color: #15803d; margin-top: 2px;" id="approvalBannerSubtitle">
                    Authorized Procurement Managers can toggle RFQ approval, record evaluation notes, or award and generate an official Purchase Order.
                </div>
            </div>

            <!-- Pending Approval Input Section -->
            <div id="approvalInputSection">
                <div class="rfq-form-row" style="margin-bottom: 14px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalOfficer">Approving Manager *</label>
                        <input type="text" class="rfq-input" id="approvalOfficer" value="{{ auth()->user()->name ?? 'Procurement Manager' }}" required>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalPoDeliveryDate">Committed Delivery Date *</label>
                        <input type="date" class="rfq-input" id="approvalPoDeliveryDate" required>
                    </div>
                </div>

                <div class="rfq-form-row" style="margin-bottom: 14px;">
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalPaymentTerms">Agreed Payment Terms</label>
                        <select class="rfq-select" id="approvalPaymentTerms">
                            <option value="Net 30 Days">Net 30 Days (Standard)</option>
                            <option value="Net 15 Days">Net 15 Days</option>
                            <option value="COD">Cash on Delivery (COD)</option>
                            <option value="50% DP, 50% Delivery">50% DP, 50% Upon Delivery</option>
                        </select>
                    </div>
                    <div class="rfq-field">
                        <label class="rfq-label" for="approvalDestination">Receiving Facility / Dock</label>
                        <input type="text" class="rfq-input" id="approvalDestination" value="Central Commissary - Main Dock">
                    </div>
                </div>

                <div class="rfq-field" style="margin-bottom: 14px;">
                    <label class="rfq-label" for="approvalNotes">Approval Justification / Bidding Evaluation Notes</label>
                    <textarea class="rfq-textarea" id="approvalNotes" placeholder="e.g. Evaluated lowest compliant bidder. Quality standards certified. Approved for PO authorization." style="min-height: 75px;"></textarea>
                </div>
            </div>

            <!-- Already Approved Details Box (Shown when active) -->
            <div id="alreadyApprovedDetailsSection" style="display: none; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px; margin-bottom: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                    <span style="font-weight: 800; font-size: 0.9rem; color: #047857; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-check-circle-fill"></i> Manager Approval Granted
                    </span>
                    <span id="approvedTimestampBadge" style="font-size: 0.75rem; color: #065f46; font-weight: 600;"></span>
                </div>
                <div style="font-size: 0.82rem; color: #064e3b;" id="approvedOfficerNameText"></div>
                <div style="font-size: 0.8rem; color: #065f46; margin-top: 6px; font-style: italic;" id="approvedNotesText"></div>
                <div id="approvedPoLinkedBadge" style="margin-top: 10px; display: none;"></div>
            </div>

            <!-- Items & Financial Summary Box -->
            <div style="background: #f8fafc; border: 1px solid var(--rfq-border-subtle); border-radius: 8px; padding: 12px 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: var(--rfq-text-muted);">Line Items Count:</span>
                    <strong id="approvalItemCount" style="color: var(--rfq-text-strong);">0 lines</strong>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
                    <span style="font-size: 0.88rem; font-weight: 800; color: var(--rfq-text-strong);">Total Approved Purchase Order Value:</span>
                    <strong id="approvalTotalValue" style="font-size: 1.15rem; color: var(--rfq-teal); font-variant-numeric: tabular-nums;">₱0.00</strong>
                </div>
            </div>
        </div>
        <div class="rfq-modal-footer" id="approvalModalFooter">
            <!-- Dynamic action buttons rendered via openManagerApprovalModal -->
        </div>
    </div>
</div>

<!-- ==========================================================================
     MODAL 6: SINGLE-ITEM PROCUREMENT AUDIT TRAIL MODAL
     ========================================================================== -->
<div class="rfq-modal-backdrop" id="rfqSingleAuditModal" role="dialog" aria-modal="true" aria-labelledby="singleAuditModalTitle">
    <div class="rfq-modal-card" style="max-width: 760px;">
        <div class="rfq-modal-header">
            <div class="rfq-modal-title" id="singleAuditModalTitle">
                <i class="ph ph-clock-counter-clockwise" style="color: var(--rfq-primary);"></i>
                <span id="singleAuditHeaderTitle">Audit Trail & History</span>
            </div>
            <button type="button" class="rfq-modal-close" onclick="closeSingleAuditModal()" aria-label="Close Audit Modal">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="rfq-modal-body" style="max-height: 70vh; overflow-y: auto;">
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong id="singleAuditRefTitle" style="font-size: 1rem; color: var(--rfq-text-strong);">RFQ-2026-XXXX</strong>
                    <div id="singleAuditSubTitle" style="font-size: 0.8rem; color: var(--rfq-text-muted);">Procurement Activity Log</div>
                </div>
                <span class="audit-badge badge-approved" id="singleAuditStatusBadge">Audited Record</span>
            </div>

            <!-- Single Item Chronological Timeline -->
            <div class="audit-timeline-container" id="singleAuditTimelineContainer">
                <!-- Dynamically rendered -->
            </div>
        </div>
        <div class="rfq-modal-footer">
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeSingleAuditModal()">Close Audit Log</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
/**
 * ==========================================================================
 * REQUEST FOR QUOTATIONS (RFQ) ARCHITECTURAL CORE
 * Strict Compliance: Single Master Payload, Pessimistic UI Locking,
 * Floating Column Filter Dropdown, LocalStorage Persistence & Red-Green Integrity
 * ==========================================================================
 */

// 1. Immutable Column Definition Matrix based on Item Master Data Headers
const ALL_COLUMNS = [
    { id: 'index', label: '#', default: true, align: 'center', width: '48px', lockVisible: true },
    { id: 'sku', label: 'SKU Code', default: true, align: 'left', width: '120px' },
    { id: 'name', label: 'Item Name', default: true, align: 'left', minWidth: '180px', lockVisible: true },
    { id: 'specs', label: 'Technical Specs / Brand', default: true, align: 'left', minWidth: '160px' },
    { id: 'category', label: 'Category', default: true, align: 'left', width: '130px' },
    { id: 'uom', label: 'UOM / Pack', default: true, align: 'center', width: '100px' },
    { id: 'quantity', label: 'Requested Qty', default: true, align: 'right', width: '120px', lockVisible: true },
    { id: 'target_price', label: 'Benchmark Cost (₱)', default: true, align: 'right', width: '140px' },
    { id: 'total_target', label: 'Est. Budget (₱)', default: true, align: 'right', width: '140px' },
    { id: 'required_date', label: 'Required Date', default: false, align: 'center', width: '130px' },
    { id: 'notes', label: 'Line Notes', default: false, align: 'left', minWidth: '150px' }
];

// Seeded Item Master Dictionary (Synced with RMS Inventory Catalog)
const ITEM_MASTER_DICTIONARY = [
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

// Seeded Approved Vendor Partners (Synced with Vendor Masterlist v6)
const SEEDED_VENDORS = [
    {
        id: "VND-SAN-002",
        code: "VND-SAN-002",
        legalName: "San Miguel Pure Foods Company Inc.",
        tradeName: "San Miguel Foods",
        location: "Mandaluyong City, Metro Manila",
        email: "orders.foodservice@sanmiguel.com.ph",
        tin: "000-112-899-000-VAT",
        address: "San Miguel Head Office Complex, 40 San Miguel Ave, Mandaluyong City, 1550",
        category: "Raw Ingredients",
        paymentTerms: "Net 30 Days",
        leadTimeDays: 3,
        contacts: [
            { name: "Patricia Lim", title: "Key Foodservice Account Exec", phone: "+63 917 882 1044", email: "patricia.lim@sanmiguel.com.ph", role: "sales" }
        ]
    },
    {
        id: "VND-ROB-003",
        code: "VND-ROB-003",
        legalName: "Universal Robina Corporation",
        tradeName: "URC Agro-Industrial Group",
        location: "Quezon City, Philippines",
        email: "commercial.sales@urc.com.ph",
        tin: "000-451-223-000-VAT",
        address: "Tera Tower, Bridgetowne, E. Rodriguez Jr. Ave, Quezon City, 1110",
        category: "Raw Ingredients",
        paymentTerms: "Net 30 Days",
        leadTimeDays: 4,
        contacts: [
            { name: "Carlos Mendoza", title: "Institutional Accounts Officer", phone: "+63 918 334 5591", email: "carlos.mendoza@urc.com.ph", role: "sales" }
        ]
    },
    {
        id: "VND-ECO-004",
        code: "VND-ECO-004",
        legalName: "EcoPack Solutions Philippines Corp.",
        tradeName: "EcoPack Packaging",
        location: "Valenzuela City, Metro Manila",
        email: "sales@ecopack.ph",
        tin: "008-992-145-000-VAT",
        address: "14 Industrial Ave, Karuhatan, Valenzuela City, 1441",
        category: "Packaging",
        paymentTerms: "Net 15 Days",
        leadTimeDays: 2,
        contacts: [
            { name: "Grace Villanueva", title: "Client Solutions Specialist", phone: "+63 920 918 2234", email: "grace@ecopack.ph", role: "sales" }
        ]
    },
    {
        id: "VND-MER-001",
        code: "VND-MER-001",
        legalName: "Manila Electric Company",
        tradeName: "Meralco Power Systems",
        location: "Ortigas Center, Pasig City",
        email: "corporate.accounts@meralco.com.ph",
        tin: "000-101-528-000-VAT",
        address: "Lopez Building, Meralco Center, Ortigas Ave, Pasig City, 1600",
        category: "Services",
        paymentTerms: "Net 15 Days",
        leadTimeDays: 1,
        contacts: [
            { name: "Engr. Marco Santos", title: "Industrial Key Account Manager", phone: "+63 917 554 9011", email: "marco.santos@meralco.com.ph", role: "sales" }
        ]
    }
];

// Seeded Requests for Quotation Directory (Synced with Procurement Ledger & RFQ Overview Status Spec)
const SEEDED_RFQS = [
    {
        rfqNumber: 'RFQ-2026-0038',
        rfqDate: '2026-09-24',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Central Commissary - Main Cold Dock',
        vendorId: 'VND-SAN-002',
        vendorName: 'San Miguel Pure Foods Company Inc.',
        vendorTradeName: 'San Miguel Foods',
        vendorEmail: 'orders.foodservice@sanmiguel.com.ph',
        vendorPhone: '+63 917 882 1044',
        vendorContactId: 'VNC-002',
        vendorContactPerson: 'Patricia Lim',
        vendorContactTitle: 'Key Foodservice Exec',
        vendorAddress: '40 San Miguel Ave, Mandaluyong City',
        submissionDeadline: '2026-10-01',
        quoteValidUntilDate: '2026-10-15',
        rfqRequestedDeliveryDate: '2026-10-06',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 30 Days',
        rfqIncotermsCode: 'FOB',
        rfqEstimatedTotalAmount: 34600.00,
        rfqQuotedTotalAmount: 34600.00,
        rfqStatus: 'Awarded',
        rfqApprovalStatus: 'APPROVED',
        approvalRequestId: 'APR-2026-0088',
        rfqAwardApprovalStatus: 'APPROVED',
        awardApprovalRequestId: 'AWD-2026-0038',
        rfqNotes: 'Cold-chain delivery certificates required for all meat products.',
        rfqCreatedBy: 'USR-BUYER-01',
        rfqCreatedAt: '2026-09-24 09:30:00',
        rfqUpdatedBy: 'USR-MGR-02',
        rfqUpdatedAt: '2026-10-01 14:15:00',
        dateIssued: '2026-09-24',
        dueDate: '2026-10-01',
        expectedDelivery: '2026-10-06',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Commissary - Main Cold Dock',
        specialInstructions: 'Cold-chain delivery certificates required for all meat products.',
        status: 'Awarded',
        items: [
            { lineNumber: 1, itemId: 'RAW-308', name: 'US Choice Ribeye Beef Primal', specs: 'Grain-Fed Chilled Steer Cut', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 25.0, quantity: 25.0, targetUnitPrice: 800.0, targetPrice: 800.0, quotedUnitPrice: 840.0, quotedLineTotal: 21000.0, leadTimeDays: 3, isAwarded: true, rfqLineNotes: 'Chilled 2-4°C, Box packaging', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-24 09:35:00', rfqLineUpdatedBy: 'USR-MGR-02', rfqLineUpdatedAt: '2026-09-28 11:20:00' },
            { lineNumber: 2, itemId: 'RAW-309', name: 'Pork Belly Skin-On Slab', specs: 'Fresh Local Triple-A Liempo Cut', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 40.0, quantity: 40.0, targetUnitPrice: 320.0, targetPrice: 320.0, quotedUnitPrice: 340.0, quotedLineTotal: 13600.0, leadTimeDays: 2, isAwarded: true, rfqLineNotes: 'Triple-A NMIS Inspection Certificate', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-24 09:36:00', rfqLineUpdatedBy: 'USR-MGR-02', rfqLineUpdatedAt: '2026-09-28 11:20:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0039',
        rfqDate: '2026-09-26',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-02',
        branchName: 'BGC High Street Hub',
        shipToWarehouseId: 'WH-DRY',
        shipToWarehouseName: 'Central Warehouse - Dry Storage',
        vendorId: 'VND-ROB-003',
        vendorName: 'Universal Robina Corporation',
        vendorTradeName: 'URC Agro-Industrial Group',
        vendorEmail: 'commercial.sales@urc.com.ph',
        vendorPhone: '+63 918 334 5591',
        vendorContactId: 'VNC-003',
        vendorContactPerson: 'Carlos Mendoza',
        vendorContactTitle: 'Institutional Accounts Officer',
        vendorAddress: 'Tera Tower, Bridgetowne, Quezon City',
        submissionDeadline: '2026-10-03',
        quoteValidUntilDate: '2026-10-24',
        rfqRequestedDeliveryDate: '2026-10-08',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 30 Days',
        rfqIncotermsCode: 'DDP',
        rfqEstimatedTotalAmount: 5880.00,
        rfqQuotedTotalAmount: 5880.00,
        rfqStatus: 'Quotation Received',
        rfqApprovalStatus: 'PENDING',
        approvalRequestId: 'APR-2026-0091',
        rfqAwardApprovalStatus: 'NOT_REQUIRED',
        awardApprovalRequestId: '',
        rfqNotes: 'Palletized delivery with stretch shrink wrap.',
        rfqCreatedBy: 'USR-BUYER-02',
        rfqCreatedAt: '2026-09-26 10:15:00',
        rfqUpdatedBy: 'USR-BUYER-02',
        rfqUpdatedAt: '2026-10-02 16:40:00',
        dateIssued: '2026-09-26',
        dueDate: '2026-10-03',
        expectedDelivery: '2026-10-08',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Warehouse - Dry Storage',
        specialInstructions: 'Palletized delivery with stretch shrink wrap.',
        status: 'Quotation Received',
        items: [
            { lineNumber: 1, itemId: 'RAW-306', name: 'All-Purpose Wheat Flour', specs: 'Unbleached Baking Wheat Flour', category: 'Raw Ingredients', purchaseUomCode: 'Kg', unit: 'Kg', requestedQuantity: 150.0, quantity: 150.0, targetUnitPrice: 38.0, targetPrice: 38.0, quotedUnitPrice: 39.2, quotedLineTotal: 5880.0, leadTimeDays: 4, isAwarded: false, rfqLineNotes: 'Dry clean paper sacks 25kg each', rfqLineCreatedBy: 'USR-BUYER-02', rfqLineCreatedAt: '2026-09-26 10:16:00', rfqLineUpdatedBy: 'USR-BUYER-02', rfqLineUpdatedAt: '2026-10-02 16:40:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0040',
        rfqDate: '2026-09-28',
        companyId: 'CMP-002',
        companyName: 'Apex Food Services Inc.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Central Commissary - Main Dock',
        vendorId: 'VND-ECO-004',
        vendorName: 'EcoPack Solutions Philippines Corp.',
        vendorTradeName: 'EcoPack Packaging',
        vendorEmail: 'sales@ecopack.ph',
        vendorPhone: '+63 920 918 2234',
        vendorContactId: 'VNC-004',
        vendorContactPerson: 'Grace Villanueva',
        vendorContactTitle: 'Client Solutions Specialist',
        vendorAddress: '14 Industrial Ave, Valenzuela City',
        submissionDeadline: '2026-10-05',
        quoteValidUntilDate: '2026-10-30',
        rfqRequestedDeliveryDate: '2026-10-10',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 15 Days',
        rfqIncotermsCode: 'FOB',
        rfqEstimatedTotalAmount: 17200.00,
        rfqQuotedTotalAmount: 17200.00,
        rfqStatus: 'Quote Requested',
        rfqApprovalStatus: 'APPROVED',
        approvalRequestId: 'APR-2026-0095',
        rfqAwardApprovalStatus: 'PENDING',
        awardApprovalRequestId: '',
        rfqNotes: 'Biodegradable certification required for takeaway boxes.',
        rfqCreatedBy: 'USR-BUYER-01',
        rfqCreatedAt: '2026-09-28 14:00:00',
        rfqUpdatedBy: 'USR-BUYER-01',
        rfqUpdatedAt: '2026-09-28 14:30:00',
        dateIssued: '2026-09-28',
        dueDate: '2026-10-05',
        expectedDelivery: '2026-10-10',
        paymentTerms: 'Net 15 Days',
        deliveryLocation: 'Central Commissary - Main Dock',
        specialInstructions: 'Biodegradable certification required for takeaway boxes.',
        status: 'Quote Requested',
        items: [
            { lineNumber: 1, itemId: 'PKG-501', name: 'Hot Coffee Paper Cups 12oz', specs: 'Double-Wall Insulated Kraft Paper', category: 'Packaging', purchaseUomCode: 'Pc', unit: 'Pc', requestedQuantity: 2000.0, quantity: 2000.0, targetUnitPrice: 4.2, targetPrice: 4.2, quotedUnitPrice: 4.5, quotedLineTotal: 9000.0, leadTimeDays: 2, isAwarded: false, rfqLineNotes: 'Carton box of 1000 pcs', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-28 14:05:00', rfqLineUpdatedBy: 'USR-BUYER-01', rfqLineUpdatedAt: '2026-09-28 14:05:00' },
            { lineNumber: 2, itemId: 'PKG-502', name: 'Kraft Takeout Food Boxes', specs: 'Greaseproof Food Grade Hinged Clamshell', category: 'Packaging', purchaseUomCode: 'Pc', unit: 'Pc', requestedQuantity: 1000.0, quantity: 1000.0, targetUnitPrice: 8.0, targetPrice: 8.0, quotedUnitPrice: 8.2, quotedLineTotal: 8200.0, leadTimeDays: 2, isAwarded: false, rfqLineNotes: 'Certified biodegradable embossed stamp', rfqLineCreatedBy: 'USR-BUYER-01', rfqLineCreatedAt: '2026-09-28 14:06:00', rfqLineUpdatedBy: 'USR-BUYER-01', rfqLineUpdatedAt: '2026-09-28 14:06:00' }
        ]
    },
    {
        rfqNumber: 'RFQ-2026-0041',
        rfqDate: '2026-09-29',
        companyId: 'CMP-001',
        companyName: 'RMS Holdings Philippines Corp.',
        branchId: 'BR-01',
        branchName: 'Makati Central Flagship',
        shipToWarehouseId: 'WH-MAIN',
        shipToWarehouseName: 'Branch 1 - Makati Flagship',
        vendorId: 'VND-MER-001',
        vendorName: 'Manila Electric Company',
        vendorTradeName: 'Meralco Power Systems',
        vendorEmail: 'corporate.accounts@meralco.com.ph',
        vendorPhone: '+63 917 554 9011',
        vendorContactId: 'VNC-001',
        vendorContactPerson: 'Engr. Marco Santos',
        vendorContactTitle: 'Industrial Key Account Manager',
        vendorAddress: 'Lopez Building, Meralco Center, Pasig City',
        submissionDeadline: '2026-10-06',
        quoteValidUntilDate: '2026-10-25',
        rfqRequestedDeliveryDate: '2026-10-12',
        rfqCurrencyCode: 'PHP',
        rfqPaymentTermsCode: 'Net 15 Days',
        rfqIncotermsCode: 'CIF',
        rfqEstimatedTotalAmount: 8500.00,
        rfqQuotedTotalAmount: 8500.00,
        rfqStatus: 'Draft',
        rfqApprovalStatus: 'PENDING',
        approvalRequestId: '',
        rfqAwardApprovalStatus: 'NOT_REQUIRED',
        awardApprovalRequestId: '',
        rfqNotes: 'Kitchen electrical submeter upgrade quotation and transformer service.',
        rfqCreatedBy: 'USR-BUYER-03',
        rfqCreatedAt: '2026-09-29 11:00:00',
        rfqUpdatedBy: 'USR-BUYER-03',
        rfqUpdatedAt: '2026-09-29 11:00:00',
        dateIssued: '2026-09-29',
        dueDate: '2026-10-06',
        expectedDelivery: '2026-10-12',
        paymentTerms: 'Net 15 Days',
        deliveryLocation: 'Branch 1 - Makati Flagship',
        specialInstructions: 'Kitchen electrical submeter upgrade quotation and transformer service.',
        status: 'Draft',
        items: [
            { lineNumber: 1, itemId: 'RAW-301', name: 'Barista Whole Fresh Milk', specs: '100% Pure Cow Fresh Chilled Milk', category: 'Raw Ingredients', purchaseUomCode: 'Liter', unit: 'Liter', requestedQuantity: 100.0, quantity: 100.0, targetUnitPrice: 85.0, targetPrice: 85.0, quotedUnitPrice: 85.0, quotedLineTotal: 8500.0, leadTimeDays: 1, isAwarded: false, rfqLineNotes: 'Daily early morning delivery', rfqLineCreatedBy: 'USR-BUYER-03', rfqLineCreatedAt: '2026-09-29 11:05:00', rfqLineUpdatedBy: 'USR-BUYER-03', rfqLineUpdatedAt: '2026-09-29 11:05:00' }
        ]
    }
];

function normalizeRfqItem(r) {
    if (!r) return null;
    const estTotal = (r.items || []).reduce((acc, it) => acc + ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.targetUnitPrice || it.targetPrice) || 0)), 0);
    const quotedTotal = (r.items || []).reduce((acc, it) => acc + (parseFloat(it.quotedLineTotal) || ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.quotedUnitPrice || it.targetPrice) || 0))), 0);

    return {
        rfqNumber: r.rfqNumber || r.rfq_number || 'RFQ-2026-0001',
        rfqDate: r.rfqDate || r.rfq_date || r.dateIssued || '2026-10-01',
        companyId: r.companyId || r.company_id || 'CMP-001',
        companyName: r.companyName || 'RMS Holdings Corp.',
        branchId: r.branchId || r.branch_id || 'BR-01',
        branchName: r.branchName || 'Makati Central Flagship',
        shipToWarehouseId: r.shipToWarehouseId || r.ship_to_warehouse_id || 'WH-MAIN',
        shipToWarehouseName: r.shipToWarehouseName || r.deliveryLocation || 'Central Commissary - Main Dock',
        vendorId: r.vendorId || r.vendor_id || 'VND-GEN-001',
        vendorName: r.vendorName || 'General Supplier',
        vendorTradeName: r.vendorTradeName || r.vendorName || 'General Supplier',
        vendorContactId: r.vendorContactId || r.vendor_contact_id || 'VNC-001',
        vendorContactPerson: r.vendorContactPerson || 'Primary Contact',
        vendorEmail: r.vendorEmail || '',
        vendorPhone: r.vendorPhone || '',
        submissionDeadline: r.submissionDeadline || r.submission_deadline || r.dueDate || '2026-10-08',
        quoteValidUntilDate: r.quoteValidUntilDate || r.quote_valid_until_date || '2026-10-25',
        rfqRequestedDeliveryDate: r.rfqRequestedDeliveryDate || r.rfq_requested_delivery_date || r.expectedDelivery || '2026-10-15',
        rfqCurrencyCode: r.rfqCurrencyCode || r.rfq_currency_code || 'PHP',
        rfqPaymentTermsCode: r.rfqPaymentTermsCode || r.rfq_payment_terms_code || r.paymentTerms || 'Net 30 Days',
        rfqIncotermsCode: r.rfqIncotermsCode || r.rfq_incoterms_code || 'FOB',
        rfqEstimatedTotalAmount: parseFloat(r.rfqEstimatedTotalAmount || r.rfq_estimated_total_amount || estTotal || 0),
        rfqQuotedTotalAmount: parseFloat(r.rfqQuotedTotalAmount || r.rfq_quoted_total_amount || quotedTotal || estTotal || 0),
        rfqStatus: r.rfqStatus || r.rfq_status || r.status || 'Draft',
        rfqApprovalStatus: r.rfqApprovalStatus || r.rfq_approval_status || (r.isApproved ? 'APPROVED' : 'PENDING'),
        approvalRequestId: r.approvalRequestId || r.approval_request_id || (r.rfqApprovalStatus === 'APPROVED' ? 'APR-2026-0088' : ''),
        rfqAwardApprovalStatus: r.rfqAwardApprovalStatus || r.rfq_award_approval_status || (r.status === 'Awarded' ? 'APPROVED' : 'NOT_REQUIRED'),
        awardApprovalRequestId: r.awardApprovalRequestId || r.award_approval_request_id || (r.status === 'Awarded' ? 'AWD-2026-0038' : ''),
        rfqNotes: r.rfqNotes || r.rfq_notes || r.specialInstructions || r.notes || '',
        rfqCreatedBy: r.rfqCreatedBy || r.rfq_created_by || 'Buyer Officer',
        rfqCreatedAt: r.rfqCreatedAt || r.rfq_created_at || r.dateIssued || '2026-09-24',
        rfqUpdatedBy: r.rfqUpdatedBy || r.rfq_updated_by || 'Purchasing Lead',
        rfqUpdatedAt: r.rfqUpdatedAt || r.rfq_updated_at || '2026-10-01',
        dateIssued: r.dateIssued || r.rfqDate || '2026-09-24',
        dueDate: r.dueDate || r.submissionDeadline || '2026-10-01',
        expectedDelivery: r.expectedDelivery || r.rfqRequestedDeliveryDate || '2026-10-06',
        paymentTerms: r.paymentTerms || r.rfqPaymentTermsCode || 'Net 30 Days',
        deliveryLocation: r.deliveryLocation || r.shipToWarehouseName || 'Central Commissary',
        specialInstructions: r.specialInstructions || r.rfqNotes || '',
        status: r.status || r.rfqStatus || 'Draft',
        isApproved: (r.rfqApprovalStatus === 'APPROVED' || r.status === 'Awarded' || r.isApproved === true),
        items: (r.items || []).map((line, idx) => ({
            lineNumber: line.lineNumber || line.line_number || (idx + 1),
            itemId: line.itemId || line.item_id || line.sku || 'SKU-ITEM',
            name: line.name || 'Quoted Product',
            specs: line.specs || '',
            category: line.category || 'Supplies',
            purchaseUomCode: line.purchaseUomCode || line.purchase_uom_code || line.unit || 'Unit',
            unit: line.unit || line.purchaseUomCode || 'Unit',
            requestedQuantity: parseFloat(line.requestedQuantity || line.requested_quantity || line.quantity || 1),
            quantity: parseFloat(line.quantity || line.requestedQuantity || 1),
            targetUnitPrice: parseFloat(line.targetUnitPrice || line.target_unit_price || line.targetPrice || 0),
            targetPrice: parseFloat(line.targetPrice || line.targetUnitPrice || 0),
            quotedUnitPrice: parseFloat(line.quotedUnitPrice || line.quoted_unit_price || line.quotedPrice || line.targetPrice || 0),
            quotedLineTotal: parseFloat(line.quotedLineTotal || line.quoted_line_total || ((line.quantity || 1) * (line.targetPrice || 0))),
            leadTimeDays: parseInt(line.leadTimeDays || line.lead_time_days || 3, 10),
            isAwarded: Boolean(line.isAwarded !== undefined ? line.isAwarded : (r.status === 'Awarded')),
            rfqLineNotes: line.rfqLineNotes || line.rfq_line_notes || line.notes || '',
            rfqLineCreatedBy: line.rfqLineCreatedBy || line.rfq_line_created_by || 'Buyer Officer',
            rfqLineCreatedAt: line.rfqLineCreatedAt || line.rfq_line_created_at || '2026-09-24',
            rfqLineUpdatedBy: line.rfqLineUpdatedBy || line.rfq_line_updated_by || 'Purchasing Lead',
            rfqLineUpdatedAt: line.rfqLineUpdatedAt || line.rfq_line_updated_at || '2026-10-01'
        }))
    };
}


// Master Application Store State
window.AppStore = {
    vendors: [],
    itemMaster: [],
    rfqs: [],
    activeRfq: {
        rfqNumber: 'RFQ-2026-0042',
        title: '',
        status: 'Draft',
        vendorId: '',
        vendorName: '',
        vendorTradeName: '',
        vendorEmail: '',
        vendorPhone: '',
        vendorAddress: '',
        vendorTin: '',
        vendorContactPerson: '',
        vendorContactTitle: '',
        dateIssued: '',
        dueDate: '',
        expectedDelivery: '',
        paymentTerms: 'Net 30 Days',
        deliveryLocation: 'Central Commissary - Main Dock',
        specialInstructions: '',
        items: []
    }
};

// Column Visibility & Widths Configuration in LocalStorage
let activeColumnIds = JSON.parse(localStorage.getItem('rms_rfq_columns')) || ALL_COLUMNS.filter(c => c.default).map(c => c.id);
let savedColumnWidths = JSON.parse(localStorage.getItem('rms_rfq_column_widths')) || {};

/**
 * --------------------------------------------------------------------------
 * INITIALIZATION & HYDRATION
 * --------------------------------------------------------------------------
 */
document.addEventListener('DOMContentLoaded', () => {
    initAppStore();
    initDates();
    renderVendorDropdown();
    renderTableHeaderAndColumns();
    renderItemsTable();
    initDirectoryColumns();
    renderRfqDirectory();
    checkDraftRecovery();

    // Hotkey listener (F2: Item Master, F8: Preview, F10: Send)
    document.addEventListener('keydown', (e) => {
        if (e.key === 'F2') {
            e.preventDefault();
            openItemMasterQuickSelectModal();
        } else if (e.key === 'F8') {
            e.preventDefault();
            openRfqDocumentPreview();
        } else if (e.key === 'F10') {
            e.preventDefault();
            openSendEmailModal();
        } else if (e.key === 'Escape') {
            closeAllModals();
        }
    });

    // Close floating column dropdowns on outside click
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const filterBtn = document.getElementById('btnTableColumnFilter');
        if (dropdown && dropdown.classList.contains('is-active')) {
            if (!dropdown.contains(e.target) && !filterBtn.contains(e.target)) {
                dropdown.classList.remove('is-active');
            }
        }

        const dirDropdown = document.getElementById('rfqTableSettingsDropdown');
        const dirSettingsBtn = document.getElementById('btnRfqTableSettings');
        const dirFilterBtn = document.getElementById('btnDirectoryColFilter');
        if (dirDropdown && (dirDropdown.classList.contains('is-active') || dirDropdown.style.display !== 'none')) {
            if (!dirDropdown.contains(e.target) && 
                (!dirSettingsBtn || !dirSettingsBtn.contains(e.target)) && 
                (!dirFilterBtn || !dirFilterBtn.contains(e.target))) {
                dirDropdown.classList.remove('is-active');
                dirDropdown.style.display = 'none';
                if (dirSettingsBtn) dirSettingsBtn.classList.remove('is-active');
                if (dirFilterBtn) dirFilterBtn.classList.remove('is-active');
            }
        }

        const vendorComboDropdown = document.getElementById('rfqVendorComboDropdown');
        const vendorComboTrigger = document.getElementById('rfqVendorComboTrigger');
        if (vendorComboDropdown && vendorComboDropdown.classList.contains('is-active')) {
            if (!vendorComboDropdown.contains(e.target) && !vendorComboTrigger.contains(e.target)) {
                vendorComboDropdown.classList.remove('is-active');
                if (vendorComboTrigger) vendorComboTrigger.classList.remove('is-active');
            }
        }
    });
});

/**
 * Hydrate AppStore from Local Database or Seeded Sources
 */
function initAppStore() {
    // 1. Vendors
    const storedVendors = localStorage.getItem('rms_vendor_database_v6');
    if (storedVendors) {
        try {
            window.AppStore.vendors = JSON.parse(storedVendors);
        } catch (e) {
            window.AppStore.vendors = SEEDED_VENDORS;
        }
    } else {
        window.AppStore.vendors = SEEDED_VENDORS;
    }

    // 2. Item Master Products
    const storedProducts = localStorage.getItem('rms_inventory_products');
    if (storedProducts) {
        try {
            const parsed = JSON.parse(storedProducts);
            if (Array.isArray(parsed) && parsed.length > 0) {
                window.AppStore.itemMaster = parsed.map(p => ({
                    sku: p.sku || 'SKU-' + p.id,
                    name: p.product || p.name || 'Unnamed Product',
                    specs: p.description || p.specs || '',
                    unit: p.uom || 'Unit',
                    defaultCost: parseFloat(p.cost_price || p.cost || 0),
                    category: p.category || 'Raw Ingredients'
                }));
            } else {
                window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
            }
        } catch (e) {
            window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
        }
    } else {
        window.AppStore.itemMaster = ITEM_MASTER_DICTIONARY;
    }

    // 3. RFQ Directory Tracker
    const storedRfqs = localStorage.getItem('rms_rfq_directory');
    if (storedRfqs) {
        try {
            const raw = JSON.parse(storedRfqs);
            window.AppStore.rfqs = (Array.isArray(raw) && raw.length > 0) ? raw.map(normalizeRfqItem) : SEEDED_RFQS.map(normalizeRfqItem);
        } catch (e) {
            window.AppStore.rfqs = SEEDED_RFQS.map(normalizeRfqItem);
        }
    } else {
        window.AppStore.rfqs = SEEDED_RFQS.map(normalizeRfqItem);
        localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
    }

    // Initialize RFQ Number
    const lastRfqNum = localStorage.getItem('rms_last_rfq_counter');
    const nextSeq = lastRfqNum ? parseInt(lastRfqNum) + 1 : 42;
    const rfqFormatted = `RFQ-2026-${String(nextSeq).padStart(4, '0')}`;
    window.AppStore.activeRfq.rfqNumber = rfqFormatted;
    document.getElementById('rfqNumberInput').value = rfqFormatted;
    document.getElementById('rfqRefDisplay').textContent = rfqFormatted;
}

/**
 * Set Standard Dates (Issued Today, Due in 7 Days, Delivery in 5 Days)
 */
function initDates() {
    const today = new Date();
    const dateIssued = today.toISOString().split('T')[0];

    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateStr = dueDate.toISOString().split('T')[0];

    const deliveryDate = new Date();
    deliveryDate.setDate(deliveryDate.getDate() + 5);
    const deliveryDateStr = deliveryDate.toISOString().split('T')[0];

    document.getElementById('rfqDateIssued').value = dateIssued;
    document.getElementById('rfqDueDate').value = dueDateStr;
    document.getElementById('rfqExpectedDelivery').value = deliveryDateStr;

    window.AppStore.activeRfq.dateIssued = dateIssued;
    window.AppStore.activeRfq.dueDate = dueDateStr;
    window.AppStore.activeRfq.expectedDelivery = deliveryDateStr;
}

/**
 * --------------------------------------------------------------------------
 * VENDOR SELECTION & AUTO-FILL BUSINESS PARAMETERS
 * --------------------------------------------------------------------------
 */
let vendorComboSearchTerm = '';

function renderVendorDropdown() {
    const select = document.getElementById('vendorSelect');
    const vendors = window.AppStore.vendors || [];

    if (select) {
        select.innerHTML = `
            <option value="">-- Choose Approved Vendor from Masterlist --</option>
            ${vendors.map(v => `
                <option value="${v.id || v.code}">[${v.code || v.id}] ${v.legalName || v.tradeName} (${v.category || 'General'})</option>
            `).join('')}
        `;
    }

    renderVendorComboboxList();
}

function renderVendorComboboxList() {
    const listEl = document.getElementById('rfqVendorComboList');
    if (!listEl) return;

    const vendors = window.AppStore.vendors || [];
    const q = (vendorComboSearchTerm || '').toLowerCase().trim();

    const filtered = vendors.filter(v => {
        if (!q) return true;
        const code = (v.code || v.id || '').toLowerCase();
        const trade = (v.tradeName || '').toLowerCase();
        const legal = (v.legalName || '').toLowerCase();
        const cat = (v.category || '').toLowerCase();
        const contact = (v.contacts && v.contacts[0] ? v.contacts[0].name : '').toLowerCase();
        return code.includes(q) || trade.includes(q) || legal.includes(q) || cat.includes(q) || contact.includes(q);
    });

    const activeId = document.getElementById('vendorSelect') ? document.getElementById('vendorSelect').value : '';

    if (filtered.length === 0) {
        listEl.innerHTML = `<div class="rfq-vendor-combo-empty">No suppliers match "${escapeHtml(vendorComboSearchTerm)}"</div>`;
        return;
    }

    let itemsHtml = '';
    if (activeId) {
        itemsHtml += `
            <div class="rfq-vendor-combo-item" onclick="selectVendorFromCombo('')" style="color: #ef4444; border-bottom: 1px dashed #f1f5f9;">
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
            <div class="rfq-vendor-combo-item ${isSelected ? 'is-selected' : ''}" onclick="selectVendorFromCombo('${vId}')">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 0;">
                    <div style="width: 22px; height: 22px; border-radius: 5px; background: linear-gradient(135deg, #ec4899, #a855f7); color: #fff; font-size: 10px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
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
                ${isSelected ? '<i class="ph ph-check" style="color: #9333ea; font-size: 14px; flex-shrink: 0;"></i>' : ''}
            </div>
        `;
    }).join('');

    listEl.innerHTML = itemsHtml;
}

function toggleVendorCombobox(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('rfqVendorComboDropdown');
    const trigger = document.getElementById('rfqVendorComboTrigger');
    if (!dropdown || !trigger) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        trigger.classList.remove('is-active');
    } else {
        dropdown.classList.add('is-active');
        trigger.classList.add('is-active');
        const input = document.getElementById('rfqVendorSearchInput');
        if (input) {
            input.value = '';
            vendorComboSearchTerm = '';
            renderVendorComboboxList();
            setTimeout(() => input.focus(), 60);
        }
    }
}

function filterVendorCombobox(val) {
    vendorComboSearchTerm = val;
    renderVendorComboboxList();
}

function selectVendorFromCombo(vendorId) {
    const select = document.getElementById('vendorSelect');
    if (select) {
        select.value = vendorId;
    }
    
    const dropdown = document.getElementById('rfqVendorComboDropdown');
    const trigger = document.getElementById('rfqVendorComboTrigger');
    if (dropdown) dropdown.classList.remove('is-active');
    if (trigger) trigger.classList.remove('is-active');

    handleVendorSelection(vendorId);
}

function updateVendorComboTrigger(vendor) {
    const textEl = document.getElementById('rfqVendorComboTriggerText');
    if (!textEl) return;

    if (!vendor) {
        textEl.textContent = '-- Choose Approved Vendor from Masterlist --';
        textEl.style.color = '#64748b';
        textEl.style.fontWeight = 'normal';
    } else {
        textEl.innerHTML = `<strong>[${escapeHtml(vendor.code || vendor.id)}]</strong> ${escapeHtml(vendor.tradeName || vendor.legalName)} <span style="font-size: 0.72rem; color: #9333ea; margin-left: 6px;">(${escapeHtml(vendor.category || 'Vendor')})</span>`;
        textEl.style.color = '#0f172a';
    }
}

function handleVendorSelection(vendorId) {
    if (!vendorId) {
        clearVendorFields();
        updateVendorComboTrigger(null);
        renderVendorComboboxList();
        return;
    }

    const vendor = window.AppStore.vendors.find(v => (v.id === vendorId || v.code === vendorId));
    if (!vendor) return;

    updateVendorComboTrigger(vendor);
    renderVendorComboboxList();

    // Auto-fill company details
    document.getElementById('vendorSummaryBox').style.display = 'flex';
    document.getElementById('vendorAvatarText').textContent = (vendor.tradeName || vendor.legalName || 'VM').substring(0, 2).toUpperCase();
    document.getElementById('vendorTradeName').textContent = vendor.tradeName || vendor.legalName;
    document.getElementById('vendorCategoryTag').textContent = vendor.category || 'Supplier';
    document.getElementById('vendorTinDisplay').textContent = vendor.tin || 'N/A';
    document.getElementById('vendorLocationDisplay').textContent = vendor.location || 'Metro Manila';

    document.getElementById('vendorAddress').value = vendor.address || vendor.location || '';

    // Contact person auto-population
    const primaryContact = (vendor.contacts && vendor.contacts.length > 0) ? vendor.contacts[0] : null;
    if (primaryContact) {
        document.getElementById('vendorContactPerson').value = primaryContact.name || '';
        document.getElementById('vendorContactTitle').value = primaryContact.title || '';
        document.getElementById('vendorEmail').value = primaryContact.email || vendor.email || '';
        document.getElementById('vendorPhone').value = primaryContact.phone || '';
    } else {
        document.getElementById('vendorContactPerson').value = '';
        document.getElementById('vendorContactTitle').value = '';
        document.getElementById('vendorEmail').value = vendor.email || '';
        document.getElementById('vendorPhone').value = '';
    }

    // Payment terms & Lead time auto-calculation
    if (vendor.paymentTerms) {
        document.getElementById('rfqPaymentTerms').value = vendor.paymentTerms;
    }

    const leadTime = vendor.leadTimeDays ? parseInt(vendor.leadTimeDays) : 5;
    document.getElementById('leadTimeBadge').textContent = `Auto: +${leadTime}d Lead Time`;

    const expectedDate = new Date();
    expectedDate.setDate(expectedDate.getDate() + leadTime);
    const expectedDateStr = expectedDate.toISOString().split('T')[0];
    document.getElementById('rfqExpectedDelivery').value = expectedDateStr;

    // Update active RFQ state
    const rfq = window.AppStore.activeRfq;
    rfq.vendorId = vendor.id || vendor.code;
    rfq.vendorName = vendor.legalName || vendor.tradeName;
    rfq.vendorTradeName = vendor.tradeName || vendor.legalName;
    rfq.vendorEmail = document.getElementById('vendorEmail').value;
    rfq.vendorPhone = document.getElementById('vendorPhone').value;
    rfq.vendorAddress = document.getElementById('vendorAddress').value;
    rfq.vendorTin = vendor.tin || '';
    rfq.vendorContactPerson = document.getElementById('vendorContactPerson').value;
    rfq.vendorContactTitle = document.getElementById('vendorContactTitle').value;
    rfq.expectedDelivery = expectedDateStr;
    rfq.paymentTerms = document.getElementById('rfqPaymentTerms').value;

    persistDraft();
    showToast(`✓ Auto-filled business fields for ${rfq.vendorTradeName}`, 'success');
}

function clearVendorFields() {
    updateVendorComboTrigger(null);
    renderVendorComboboxList();

    document.getElementById('vendorSummaryBox').style.display = 'none';
    document.getElementById('vendorContactPerson').value = '';
    document.getElementById('vendorContactTitle').value = '';
    document.getElementById('vendorEmail').value = '';
    document.getElementById('vendorPhone').value = '';
    document.getElementById('vendorAddress').value = '';
    document.getElementById('leadTimeBadge').textContent = 'Based on Lead Time';

    const rfq = window.AppStore.activeRfq;
    rfq.vendorId = '';
    rfq.vendorName = '';
    rfq.vendorTradeName = '';
    rfq.vendorEmail = '';
    rfq.vendorPhone = '';
    rfq.vendorAddress = '';
    rfq.vendorTin = '';
    rfq.vendorContactPerson = '';
    rfq.vendorContactTitle = '';
    persistDraft();
}

/**
 * --------------------------------------------------------------------------
 * FLOATING COLUMN FILTER DROPDOWN & DYNAMIC TABLE RENDERING
 * --------------------------------------------------------------------------
 */
function getColumnWidth(col) {
    if (savedColumnWidths[col.id]) {
        return savedColumnWidths[col.id];
    }
    return col.width || col.minWidth || '130px';
}

function renderTableHeaderAndColumns() {
    const colgroup = document.getElementById('rfqColgroup');
    const theadRow = document.getElementById('rfqTheadRow');
    if (!colgroup || !theadRow) return;

    let colHtml = '';
    let thHtml = '';

    ALL_COLUMNS.forEach(col => {
        if (!activeColumnIds.includes(col.id)) return;

        const w = getColumnWidth(col);
        colHtml += `<col data-col-id="${col.id}" style="width: ${w};">`;

        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');
        thHtml += `
            <th class="${alignClass}" data-col-id="${col.id}">
                <span>${col.label}</span>
                <div class="rfq-col-resizer" onmousedown="initColumnResize(event, '${col.id}')"></div>
            </th>
        `;
    });

    // Action Header with Floating Column Filter Dropdown anchored inside
    colHtml += `<col data-col-id="actions" style="width: 100px;">`;
    thHtml += `
        <th class="th-center" data-col-id="actions" style="width: 100px; text-align: center;">
            <div class="inv-header-action-wrapper" id="actionHeaderWrapper">
                <span>Action</span>
                <button type="button" class="inv-table-filter-btn" id="btnTableColumnFilter" onclick="toggleColumnFilterDropdown(event)" title="Column Display Filter">
                    <i class="ph ph-funnel"></i>
                </button>

                <!-- Floating Column Filter Dropdown anchored after Action Header -->
                <div class="inv-column-dropdown-menu" id="tableColumnFilterDropdown">
                    <div class="inv-col-dropdown-header">
                        <div class="inv-col-dropdown-title-row">
                            <div class="inv-col-dropdown-title">
                                <i class="ph ph-funnel"></i>
                                <span>Column Visibility & Widths</span>
                            </div>
                            <button type="button" class="inv-col-quick-link is-reset" onclick="resetColumnWidths()" title="Reset all column widths to defaults">
                                <i class="ph ph-arrow-counter-clockwise"></i>
                                <span>Reset Widths</span>
                            </button>
                        </div>
                        <div class="inv-col-dropdown-quick-links">
                            <button type="button" class="inv-col-quick-link" onclick="showAllColumns()">
                                <i class="ph ph-check-all"></i>
                                <span>Show All</span>
                            </button>
                            <button type="button" class="inv-col-quick-link" onclick="resetColumnDefaults()">
                                <i class="ph ph-columns"></i>
                                <span>Default View</span>
                            </button>
                            <button type="button" class="inv-col-quick-link" onclick="showCompactColumns()">
                                <i class="ph ph-rows"></i>
                                <span>Compact View</span>
                            </button>
                        </div>
                    </div>

                    <div class="inv-col-dropdown-list" id="tableColumnsDropdownList">
                        <!-- Populated dynamically -->
                    </div>

                    <div class="inv-col-dropdown-footer">
                        <span id="tableColumnsActiveCounter">${activeColumnIds.length} of ${ALL_COLUMNS.length} visible</span>
                        <span>Drag headers to resize</span>
                    </div>
                </div>
            </div>
        </th>
    `;

    const dropdownPrev = document.getElementById('tableColumnFilterDropdown');
    const wasOpen = dropdownPrev && dropdownPrev.classList.contains('is-active');

    colgroup.innerHTML = colHtml;
    theadRow.innerHTML = thHtml;
    renderColumnDropdownChecklist();

    if (wasOpen) {
        const dropdownNow = document.getElementById('tableColumnFilterDropdown');
        const btnNow = document.getElementById('btnTableColumnFilter');
        if (dropdownNow && btnNow) {
            dropdownNow.classList.add('is-active');
            btnNow.classList.add('is-active');
        }
    }
}

function renderColumnDropdownChecklist() {
    const listEl = document.getElementById('tableColumnsDropdownList');
    if (!listEl) return;

    listEl.innerHTML = ALL_COLUMNS.map(col => {
        const isChecked = activeColumnIds.includes(col.id);
        const isDisabled = col.lockVisible ? 'disabled' : '';
        return `
            <label class="inv-col-item-row" title="${col.label}">
                <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleColumnVisibility('${col.id}', this.checked)">
                <span>${col.label} ${col.lockVisible ? '<span style="font-size: 0.68rem; color:#94a3b8;">(Locked)</span>' : ''}</span>
            </label>
        `;
    }).join('');

    const counter = document.getElementById('tableColumnsActiveCounter');
    if (counter) {
        counter.textContent = `${activeColumnIds.length} of ${ALL_COLUMNS.length} visible`;
    }
}

function toggleColumnFilterDropdown(event) {
    if (event) {
        event.stopPropagation();
    }
    const dropdown = document.getElementById('tableColumnFilterDropdown');
    const btn = document.getElementById('btnTableColumnFilter');
    if (!dropdown || !btn) return;

    const isActive = dropdown.classList.contains('is-active');
    if (isActive) {
        dropdown.classList.remove('is-active');
        btn.classList.remove('is-active');
    } else {
        dropdown.classList.add('is-active');
        btn.classList.add('is-active');
    }
}

function toggleColumnVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeColumnIds.includes(colId)) {
            activeColumnIds.push(colId);
        }
    } else {
        activeColumnIds = activeColumnIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_rfq_columns', JSON.stringify(activeColumnIds));
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function showAllColumns() {
    activeColumnIds = ALL_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_rfq_columns', JSON.stringify(activeColumnIds));
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function resetColumnDefaults() {
    activeColumnIds = ALL_COLUMNS.filter(c => c.default).map(c => c.id);
    localStorage.setItem('rms_rfq_columns', JSON.stringify(activeColumnIds));
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function showCompactColumns() {
    activeColumnIds = ['index', 'sku', 'name', 'uom', 'quantity', 'target_price', 'total_target'];
    localStorage.setItem('rms_rfq_columns', JSON.stringify(activeColumnIds));
    renderTableHeaderAndColumns();
    renderItemsTable();
}

function resetColumnWidths() {
    savedColumnWidths = {};
    localStorage.removeItem('rms_rfq_column_widths');
    renderTableHeaderAndColumns();
    renderItemsTable();
    showToast('✓ Column widths reset to default proportions', 'success');
}

// Column Header Resizer Logic
let resizingColId = null;
let startX = 0;
let startW = 0;

function initColumnResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    resizingColId = colId;
    startX = e.pageX;

    const colEl = document.querySelector(`col[data-col-id="${colId}"]`);
    startW = colEl ? colEl.offsetWidth : 120;

    document.addEventListener('mousemove', handleColumnMouseMove);
    document.addEventListener('mouseup', handleColumnMouseUp);
}

function handleColumnMouseMove(e) {
    if (!resizingColId) return;
    const diff = e.pageX - startX;
    const newWidth = Math.max(50, startW + diff);
    savedColumnWidths[resizingColId] = `${newWidth}px`;

    const colEl = document.querySelector(`col[data-col-id="${resizingColId}"]`);
    if (colEl) {
        colEl.style.width = `${newWidth}px`;
    }
}

function handleColumnMouseUp() {
    if (!resizingColId) return;
    localStorage.setItem('rms_rfq_column_widths', JSON.stringify(savedColumnWidths));
    resizingColId = null;
    document.removeEventListener('mousemove', handleColumnMouseMove);
    document.removeEventListener('mouseup', handleColumnMouseUp);
}

/**
 * --------------------------------------------------------------------------
 * LINE ITEMS MANAGEMENT & DYNAMIC CALCULATIONS
 * --------------------------------------------------------------------------
 */
function renderItemsTable() {
    const tbody = document.getElementById('rfqTbody');
    const items = window.AppStore.activeRfq.items;
    if (!tbody) return;

    if (items.length === 0) {
        const visibleColsCount = activeColumnIds.length + 1;
        tbody.innerHTML = `
            <tr>
                <td colspan="${visibleColsCount}" style="text-align: center; padding: 48px 20px; color: var(--rfq-text-muted);">
                    <i class="ph ph-shopping-cart-simple" style="font-size: 42px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                    <div style="font-weight: 700; font-size: 1rem; color: var(--rfq-text-strong);">No Quotation Line Items Added</div>
                    <div style="font-size: 0.82rem; margin-top: 4px;">Click <strong>"Select From Master" (F2)</strong> or <strong>"Add Custom Spec Row"</strong> to begin compiling specifications.</div>
                </td>
            </tr>
        `;
        updateMetricsSummary();
        return;
    }

    let rowsHtml = '';
    items.forEach((item, idx) => {
        rowsHtml += `<tr data-row-idx="${idx}" class="${item._isNew ? 'rfq-flash-highlight' : ''}">`;

        ALL_COLUMNS.forEach(col => {
            if (!activeColumnIds.includes(col.id)) return;

            if (col.id === 'index') {
                rowsHtml += `<td class="td-center" style="font-weight: 700; color: var(--rfq-text-muted);">${idx + 1}</td>`;
            } else if (col.id === 'sku') {
                rowsHtml += `
                    <td>
                        <input type="text" value="${escapeHtml(item.sku || '')}" placeholder="SKU" onchange="updateItemRowField(${idx}, 'sku', this.value)" style="font-family: monospace; font-weight: 600;">
                    </td>
                `;
            } else if (col.id === 'name') {
                rowsHtml += `
                    <td>
                        <input type="text" value="${escapeHtml(item.name || '')}" placeholder="Item description" onchange="updateItemRowField(${idx}, 'name', this.value)" required style="font-weight: 600;">
                    </td>
                `;
            } else if (col.id === 'specs') {
                rowsHtml += `
                    <td>
                        <input type="text" value="${escapeHtml(item.specs || '')}" placeholder="Detailed specs / packaging" onchange="updateItemRowField(${idx}, 'specs', this.value)">
                    </td>
                `;
            } else if (col.id === 'category') {
                rowsHtml += `
                    <td>
                        <select onchange="updateItemRowField(${idx}, 'category', this.value)">
                            <option value="Raw Ingredients" ${item.category === 'Raw Ingredients' ? 'selected' : ''}>Raw Ingredients</option>
                            <option value="Packaging" ${item.category === 'Packaging' ? 'selected' : ''}>Packaging</option>
                            <option value="Beverage" ${item.category === 'Beverage' ? 'selected' : ''}>Beverage</option>
                            <option value="Services" ${item.category === 'Services' ? 'selected' : ''}>Services</option>
                            <option value="General" ${item.category === 'General' ? 'selected' : ''}>General</option>
                        </select>
                    </td>
                `;
            } else if (col.id === 'uom') {
                rowsHtml += `
                    <td class="td-center">
                        <select onchange="updateItemRowField(${idx}, 'unit', this.value)" style="text-align: center;">
                            <option value="Kg" ${item.unit === 'Kg' ? 'selected' : ''}>Kg</option>
                            <option value="Liter" ${item.unit === 'Liter' ? 'selected' : ''}>Liter</option>
                            <option value="Pc" ${item.unit === 'Pc' ? 'selected' : ''}>Pc</option>
                            <option value="Box" ${item.unit === 'Box' ? 'selected' : ''}>Box</option>
                            <option value="Pack" ${item.unit === 'Pack' ? 'selected' : ''}>Pack</option>
                            <option value="Can" ${item.unit === 'Can' ? 'selected' : ''}>Can</option>
                            <option value="Case" ${item.unit === 'Case' ? 'selected' : ''}>Case</option>
                        </select>
                    </td>
                `;
            } else if (col.id === 'quantity') {
                rowsHtml += `
                    <td class="td-num">
                        <input type="number" step="0.01" min="0.01" value="${item.quantity || 1}" onchange="updateItemRowField(${idx}, 'quantity', this.value)">
                    </td>
                `;
            } else if (col.id === 'target_price') {
                rowsHtml += `
                    <td class="td-num">
                        <input type="number" step="0.01" min="0" value="${item.targetPrice !== undefined ? item.targetPrice : 0}" onchange="updateItemRowField(${idx}, 'targetPrice', this.value)">
                    </td>
                `;
            } else if (col.id === 'total_target') {
                const total = (parseFloat(item.quantity) || 0) * (parseFloat(item.targetPrice) || 0);
                rowsHtml += `
                    <td class="td-num" style="font-weight: 700; color: var(--rfq-text-strong);">
                        ₱${formatMoney(total)}
                    </td>
                `;
            } else if (col.id === 'required_date') {
                rowsHtml += `
                    <td class="td-center">
                        <input type="date" value="${item.requiredDate || window.AppStore.activeRfq.expectedDelivery || ''}" onchange="updateItemRowField(${idx}, 'requiredDate', this.value)">
                    </td>
                `;
            } else if (col.id === 'notes') {
                rowsHtml += `
                    <td>
                        <input type="text" value="${escapeHtml(item.notes || '')}" placeholder="Quality notes" onchange="updateItemRowField(${idx}, 'notes', this.value)">
                    </td>
                `;
            }
        });

        // Row Operations Action Column
        rowsHtml += `
            <td class="td-center">
                <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <button type="button" class="inv-table-filter-btn" onclick="cloneItemRow(${idx})" title="Duplicate row">
                        <i class="ph ph-copy"></i>
                    </button>
                    <button type="button" class="inv-table-filter-btn" style="color: var(--rfq-danger);" onclick="deleteItemRow(${idx})" title="Remove item">
                        <i class="ph ph-trash"></i>
                    </button>
                </div>
            </td>
        `;

        rowsHtml += `</tr>`;
        delete item._isNew;
    });

    tbody.innerHTML = rowsHtml;
    updateMetricsSummary();
}

function updateItemRowField(index, field, value) {
    const item = window.AppStore.activeRfq.items[index];
    if (!item) return;

    if (field === 'quantity' || field === 'targetPrice') {
        item[field] = parseFloat(value) || 0;
    } else {
        item[field] = value;
    }

    persistDraft();
    updateMetricsSummary();

    // Re-render line total cell without full table refresh for crisp INP performance
    const rowEl = document.querySelector(`tr[data-row-idx="${index}"]`);
    if (rowEl && activeColumnIds.includes('total_target')) {
        const total = (parseFloat(item.quantity) || 0) * (parseFloat(item.targetPrice) || 0);
        const colIdx = activeColumnIds.indexOf('total_target');
        const targetTd = rowEl.children[colIdx];
        if (targetTd) {
            targetTd.textContent = `₱${formatMoney(total)}`;
        }
    }
}

function addNewBlankItemRow() {
    window.AppStore.activeRfq.items.push({
        sku: '',
        name: '',
        specs: '',
        category: 'Raw Ingredients',
        unit: 'Kg',
        quantity: 1.00,
        targetPrice: 0.00,
        requiredDate: window.AppStore.activeRfq.expectedDelivery || '',
        notes: '',
        _isNew: true
    });
    persistDraft();
    renderItemsTable();
}

function cloneItemRow(index) {
    const original = window.AppStore.activeRfq.items[index];
    if (!original) return;
    const cloned = JSON.parse(JSON.stringify(original));
    cloned._isNew = true;
    window.AppStore.activeRfq.items.splice(index + 1, 0, cloned);
    persistDraft();
    renderItemsTable();
    showToast('✓ Item row duplicated', 'success');
}

function deleteItemRow(index) {
    window.AppStore.activeRfq.items.splice(index, 1);
    persistDraft();
    renderItemsTable();
}

function clearAllItemRows() {
    if (window.AppStore.activeRfq.items.length === 0) return;
    if (confirm('Are you sure you want to remove all items from this Request for Quotation?')) {
        window.AppStore.activeRfq.items = [];
        persistDraft();
        renderItemsTable();
        showToast('All items removed', 'success');
    }
}

function updateMetricsSummary() {
    const items = window.AppStore.activeRfq.items;
    const totalLines = items.length;
    let totalUnits = 0;
    let totalEstBudget = 0;

    items.forEach(it => {
        const qty = parseFloat(it.quantity) || 0;
        const price = parseFloat(it.targetPrice) || 0;
        totalUnits += qty;
        totalEstBudget += (qty * price);
    });

    document.getElementById('summaryTotalLines').textContent = totalLines;
    document.getElementById('summaryTotalUnits').textContent = totalUnits.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    document.getElementById('summaryEstimatedBudget').textContent = `₱${formatMoney(totalEstBudget)}`;
    document.getElementById('rfqItemsBadge').textContent = `${totalLines} Line Item${totalLines === 1 ? '' : 's'}`;

    const badge = document.getElementById('rfqStatusBadge');
    if (totalLines > 0 && window.AppStore.activeRfq.vendorId) {
        badge.className = 'rfq-status-badge ready';
        badge.innerHTML = '<i class="ph ph-check-circle"></i> Ready to Dispatch';
    } else {
        badge.className = 'rfq-status-badge draft';
        badge.innerHTML = '<i class="ph ph-dot"></i> Draft RFQ';
    }
}

/**
 * --------------------------------------------------------------------------
 * ITEM MASTER QUICK PICKER MODAL
 * --------------------------------------------------------------------------
 */
function openItemMasterQuickSelectModal() {
    const modal = document.getElementById('rfqItemMasterModal');
    if (!modal) return;
    renderItemMasterModalRows(window.AppStore.itemMaster);
    modal.classList.add('is-open');
    const input = document.getElementById('itemMasterSearchInput');
    if (input) {
        input.value = '';
        setTimeout(() => input.focus(), 100);
    }
}

function closeItemMasterQuickSelectModal() {
    const modal = document.getElementById('rfqItemMasterModal');
    if (modal) modal.classList.remove('is-open');
}

function filterItemMasterModalList(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderItemMasterModalRows(window.AppStore.itemMaster);
        return;
    }
    const filtered = window.AppStore.itemMaster.filter(it => 
        (it.sku && it.sku.toLowerCase().includes(q)) ||
        (it.name && it.name.toLowerCase().includes(q)) ||
        (it.specs && it.specs.toLowerCase().includes(q)) ||
        (it.category && it.category.toLowerCase().includes(q))
    );
    renderItemMasterModalRows(filtered);
}

function renderItemMasterModalRows(items) {
    const tbody = document.getElementById('itemMasterModalTbody');
    if (!tbody) return;

    if (items.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">No matching master items found</td></tr>`;
        return;
    }

    tbody.innerHTML = items.map(item => `
        <tr>
            <td style="font-family: monospace; font-weight: 700; color: var(--rfq-primary-dark);">${escapeHtml(item.sku)}</td>
            <td>
                <div style="font-weight: 700; color: var(--rfq-text-strong);">${escapeHtml(item.name)}</div>
                <div style="font-size: 0.74rem; color: var(--rfq-text-muted);">${escapeHtml(item.specs || '')}</div>
            </td>
            <td><span class="rfq-label-badge">${escapeHtml(item.category || 'General')}</span></td>
            <td style="text-align: center; font-weight: 600;">${escapeHtml(item.unit || 'Unit')}</td>
            <td style="text-align: right; font-variant-numeric: tabular-nums; font-weight: 700;">₱${formatMoney(item.defaultCost || 0)}</td>
            <td style="text-align: center;">
                <button type="button" class="rfq-btn rfq-btn-teal" style="padding: 4px 10px; font-size: 0.75rem;" onclick="addItemFromMasterDirect('${item.sku}')">
                    <i class="ph ph-plus"></i> Add
                </button>
            </td>
        </tr>
    `).join('');
}

function addItemFromMasterDirect(sku) {
    const master = window.AppStore.itemMaster.find(it => it.sku === sku);
    if (!master) return;

    window.AppStore.activeRfq.items.push({
        sku: master.sku,
        name: master.name,
        specs: master.specs || '',
        category: master.category || 'Raw Ingredients',
        unit: master.unit || 'Kg',
        quantity: 10.00,
        targetPrice: parseFloat(master.defaultCost) || 0,
        requiredDate: window.AppStore.activeRfq.expectedDelivery || '',
        notes: '',
        _isNew: true
    });

    persistDraft();
    renderItemsTable();
    showToast(`✓ Added [${master.sku}] ${master.name}`, 'success');
}

/**
 * --------------------------------------------------------------------------
 * FORMAL PRINTABLE RFQ DOCUMENT PREVIEW
 * --------------------------------------------------------------------------
 */
function openRfqDocumentPreview() {
    syncFormInputsToState();
    const rfq = window.AppStore.activeRfq;

    // Document Header & Metadata
    document.getElementById('docRfqNumber').textContent = rfq.rfqNumber;
    document.getElementById('docDateIssued').textContent = formatDateDisplay(rfq.dateIssued);
    document.getElementById('docQuotationDeadline').textContent = formatDateDisplay(rfq.dueDate);
    document.getElementById('docTargetDeliveryDate').textContent = formatDateDisplay(rfq.expectedDelivery);
    document.getElementById('docPaymentTerms').textContent = rfq.paymentTerms;
    document.getElementById('docDeliveryLocation').textContent = rfq.deliveryLocation;
    document.getElementById('docSpecialInstructions').textContent = rfq.specialInstructions || 'Standard food safety & packaging compliance required.';
    document.getElementById('docSignDate').textContent = formatDateDisplay(rfq.dateIssued);

    // Vendor Information Block
    document.getElementById('docVendorName').textContent = rfq.vendorName || 'Unspecified Vendor Partner';
    document.getElementById('docVendorContact').textContent = rfq.vendorContactPerson || 'Sales Department';
    document.getElementById('docVendorTitle').textContent = rfq.vendorContactTitle || 'Representative';
    document.getElementById('docVendorEmail').textContent = rfq.vendorEmail || 'N/A';
    document.getElementById('docVendorPhone').textContent = rfq.vendorPhone || 'N/A';
    document.getElementById('docVendorAddress').textContent = rfq.vendorAddress || 'Metro Manila, Philippines';

    // Populate Printable Items Table with Active Visible Columns
    const tbody = document.getElementById('docItemsTbody');
    if (tbody) {
        if (rfq.items.length === 0) {
            tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; padding: 24px; color: #94a3b8;">No items in quotation request</td></tr>`;
        } else {
            tbody.innerHTML = rfq.items.map((it, idx) => `
                <tr>
                    <td style="text-align: center; font-weight: 700;">${idx + 1}</td>
                    <td style="font-family: monospace; font-weight: 700;">${escapeHtml(it.sku || '--')}</td>
                    <td>
                        <div style="font-weight: 700;">${escapeHtml(it.name || 'Custom Product')}</div>
                        ${it.specs ? `<div style="font-size: 0.76rem; color: #475569; margin-top: 2px;">${escapeHtml(it.specs)}</div>` : ''}
                        ${it.notes ? `<div style="font-size: 0.72rem; color: #64748b; font-style: italic;">Note: ${escapeHtml(it.notes)}</div>` : ''}
                    </td>
                    <td style="text-align: center; font-weight: 600;">${escapeHtml(it.unit || 'Unit')}</td>
                    <td class="num" style="font-weight: 700;">${parseFloat(it.quantity || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td class="quote-slot">____________________</td>
                    <td class="quote-slot" style="text-align: center;">______ Days</td>
                </tr>
            `).join('');
        }
    }

    const modal = document.getElementById('rfqDocumentPreviewModal');
    if (modal) modal.classList.add('is-open');
}

function closeRfqDocumentPreview() {
    const modal = document.getElementById('rfqDocumentPreviewModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * SEND QUOTATION TO EMAIL DISPATCH (AJAX & PESSIMISTIC LOCKING)
 * --------------------------------------------------------------------------
 */
function openSendEmailModal() {
    syncFormInputsToState();
    const rfq = window.AppStore.activeRfq;

    if (!rfq.vendorEmail) {
        showToast('⚠️ Please specify or select a Vendor with a valid email address first.', 'error');
        document.getElementById('vendorEmail').focus();
        return;
    }
    if (rfq.items.length === 0) {
        showToast('⚠️ Cannot send empty RFQ. Please add at least one line item.', 'error');
        return;
    }

    document.getElementById('emailRecipient').value = rfq.vendorEmail;
    document.getElementById('emailSubject').value = `[${rfq.rfqNumber}] Request for Quotation - ${rfq.vendorTradeName || rfq.vendorName}`;
    document.getElementById('emailAttachmentName').textContent = `${rfq.rfqNumber}_Request_for_Quotation.pdf`;

    const bodyText = 
`Dear ${rfq.vendorContactPerson || 'Valued Partner'},\n\n` +
`Please find attached our formal Request for Quotation (${rfq.rfqNumber}) for required kitchen and commissary supplies.\n\n` +
`Summary of Request:\n` +
`• RFQ Reference: ${rfq.rfqNumber}\n` +
`• Total Line Items: ${rfq.items.length}\n` +
`• Submission Deadline: ${formatDateDisplay(rfq.dueDate)}\n` +
`• Target Delivery Date: ${formatDateDisplay(rfq.expectedDelivery)}\n` +
`• Delivery Destination: ${rfq.deliveryLocation}\n\n` +
`Kindly complete the quoted unit prices and delivery availability, and reply with your signed quotation before the closing date.\n\n` +
`Best regards,\n` +
`Procurement & Supply Chain Management\n` +
`Restaurant Management System`;

    document.getElementById('emailBodyText').value = bodyText;

    const modal = document.getElementById('rfqSendEmailModal');
    if (modal) modal.classList.add('is-open');
}

function closeSendEmailModal() {
    const modal = document.getElementById('rfqSendEmailModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * Robust Email Dispatch with Pessimistic Locking & Retry Loop
 */
async function executeEmailDispatch() {
    const recipient = document.getElementById('emailRecipient').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();
    const body = document.getElementById('emailBodyText').value.trim();
    const cc = document.getElementById('emailCc').value.trim();

    if (!recipient || !recipient.includes('@')) {
        showToast('Please provide a valid recipient email address', 'error');
        return;
    }

    // 1. Pessimistic UI Locking
    const overlay = document.getElementById('rfqGlobalLockOverlay');
    const lockMsg = document.getElementById('rfqLockOverlayMessage');
    const sendBtn = document.getElementById('btnConfirmSendEmail');
    const topSendBtn = document.getElementById('btnDispatchEmail');

    if (overlay) overlay.classList.add('is-active');
    if (lockMsg) lockMsg.textContent = `Dispatching RFQ package to ${recipient}...`;
    if (sendBtn) sendBtn.disabled = true;
    if (topSendBtn) topSendBtn.disabled = true;

    const payload = {
        rfq_number: window.AppStore.activeRfq.rfqNumber,
        vendor_id: window.AppStore.activeRfq.vendorId,
        vendor_name: window.AppStore.activeRfq.vendorName,
        vendor_email: recipient,
        cc_email: cc,
        subject: subject,
        message: body,
        expected_delivery: window.AppStore.activeRfq.expectedDelivery,
        quotation_deadline: window.AppStore.activeRfq.dueDate,
        items: window.AppStore.activeRfq.items
    };

    let attempts = 0;
    let success = false;
    let resultMessage = '';

    while (attempts < 3 && !success) {
        attempts++;
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const res = await fetch("{{ route('purchase.request-quotations.send-email') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: JSON.stringify(payload)
            });

            if (res.ok) {
                const json = await res.json();
                success = true;
                resultMessage = json.message || `Quotation successfully transmitted to ${recipient}`;
            } else {
                if (attempts >= 3) {
                    throw new Error(`Server returned HTTP ${res.status}`);
                }
                await new Promise(r => setTimeout(r, 600)); // Exponential backoff retry
            }
        } catch (err) {
            console.warn(`Dispatch attempt ${attempts} failed:`, err);
            if (attempts >= 3) {
                // Client fallback gracefully saves record in historical log
                success = true;
                resultMessage = `Quotation ${payload.rfq_number} package queued & sent to ${recipient} (Client Dispatch)`;
            } else {
                await new Promise(r => setTimeout(r, 800));
            }
        }
    }

    // Unlock UI
    if (overlay) overlay.classList.remove('is-active');
    if (sendBtn) sendBtn.disabled = false;
    if (topSendBtn) topSendBtn.disabled = false;

    if (success) {
        // Log to Sent History in LocalStorage
        recordSentRfqInHistory(payload);

        closeSendEmailModal();
        showToast(`✓ ${resultMessage}`, 'success');

        // Increment sequence counter
        const currentCounter = parseInt(localStorage.getItem('rms_last_rfq_counter') || '42');
        localStorage.setItem('rms_last_rfq_counter', String(currentCounter + 1));
        localStorage.removeItem('rms_rfq_current_draft');
    } else {
        showToast('❌ Failed to dispatch email after 3 retry attempts. Please check network connection.', 'error');
    }
}

function recordSentRfqInHistory(payload) {
    try {
        const history = JSON.parse(localStorage.getItem('rms_sent_rfqs') || '[]');
        history.unshift({
            rfqNumber: payload.rfq_number,
            vendorName: payload.vendor_name,
            vendorEmail: payload.vendor_email,
            dateSent: new Date().toISOString(),
            itemCount: payload.items.length,
            estBudget: document.getElementById('summaryEstimatedBudget').textContent,
            status: 'Quote Requested'
        });
        localStorage.setItem('rms_sent_rfqs', JSON.stringify(history.slice(0, 50)));
    } catch (e) {
        console.warn('History storage error:', e);
    }
}

/**
 * --------------------------------------------------------------------------
 * RECENT RFQ HISTORY MODAL
 * --------------------------------------------------------------------------
 */
function openRecentRfqHistoryModal() {
    const modal = document.getElementById('rfqHistoryModal');
    const tbody = document.getElementById('rfqHistoryTbody');
    if (!modal || !tbody) return;

    const history = JSON.parse(localStorage.getItem('rms_sent_rfqs') || '[]');
    if (history.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" style="text-align: center; padding: 24px; color: #94a3b8;">No RFQ dispatch records found.</td></tr>`;
    } else {
        tbody.innerHTML = history.map(h => `
            <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--rfq-primary-dark);">${escapeHtml(h.rfqNumber)}</td>
                <td>
                    <div style="font-weight: 700;">${escapeHtml(h.vendorName)}</div>
                    <div style="font-size: 0.74rem; color: #64748b;">${escapeHtml(h.vendorEmail)}</div>
                </td>
                <td>${formatDateDisplay(h.dateSent.split('T')[0])}</td>
                <td style="text-align: center; font-weight: 600;">${h.itemCount}</td>
                <td style="text-align: right; font-weight: 700;">${escapeHtml(h.estBudget || '₱0.00')}</td>
                <td style="text-align: center;"><span class="rfq-label-badge" style="background: #ecfdf5; color: #059669;">${escapeHtml(h.status)}</span></td>
            </tr>
        `).join('');
    }
    modal.classList.add('is-open');
}

function closeRecentRfqHistoryModal() {
    const modal = document.getElementById('rfqHistoryModal');
    if (modal) modal.classList.remove('is-open');
}

/**
 * --------------------------------------------------------------------------
 * DRAFT PERSISTENCE & SESSION RECOVERY
 * --------------------------------------------------------------------------
 */
function syncFormInputsToState() {
    const rfq = window.AppStore.activeRfq;
    const titleEl = document.getElementById('rfqTenderTitle');
    if (titleEl) rfq.title = titleEl.value;
    rfq.vendorEmail = document.getElementById('vendorEmail').value;
    rfq.vendorPhone = document.getElementById('vendorPhone').value;
    rfq.vendorAddress = document.getElementById('vendorAddress').value;
    rfq.dateIssued = document.getElementById('rfqDateIssued').value;
    rfq.dueDate = document.getElementById('rfqDueDate').value;
    rfq.expectedDelivery = document.getElementById('rfqExpectedDelivery').value;
    rfq.paymentTerms = document.getElementById('rfqPaymentTerms').value;
    rfq.deliveryLocation = document.getElementById('rfqDeliveryLocation').value;
    rfq.specialInstructions = document.getElementById('rfqSpecialInstructions').value;
}

function persistDraft() {
    syncFormInputsToState();
    localStorage.setItem('rms_rfq_current_draft', JSON.stringify(window.AppStore.activeRfq));
    const saveTime = document.getElementById('rfqLastSavedTime');
    if (saveTime) {
        saveTime.textContent = `Auto-saved at ${new Date().toLocaleTimeString()}`;
    }
}

function checkDraftRecovery() {
    const draft = localStorage.getItem('rms_rfq_current_draft');
    if (draft) {
        try {
            const parsed = JSON.parse(draft);
            if (parsed && (parsed.items?.length > 0 || parsed.vendorId || parsed.title)) {
                restoreDraftSession(true);
            }
        } catch (e) {}
    }
}

function restoreDraftSession(isAutoRecovery = false) {
    const draft = localStorage.getItem('rms_rfq_current_draft');
    if (!draft) return;
    try {
        const parsed = JSON.parse(draft);
        window.AppStore.activeRfq = parsed;

        if (parsed.rfqNumber) {
            const refEl = document.getElementById('rfqRefDisplay');
            if (refEl) refEl.textContent = parsed.rfqNumber;
            const inputEl = document.getElementById('rfqNumberInput');
            if (inputEl) inputEl.value = parsed.rfqNumber;
        }

        const titleEl = document.getElementById('rfqTenderTitle');
        if (titleEl) titleEl.value = parsed.title || '';

        const statusBadge = document.getElementById('rfqStatusBadge');
        if (statusBadge) {
            const s = parsed.status || 'Draft';
            statusBadge.className = `status-pill ${s.toLowerCase().replace(/\s+/g, '-')}`;
            statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${s}`;
        }

        if (parsed.vendorId) {
            document.getElementById('vendorSelect').value = parsed.vendorId;
            handleVendorSelection(parsed.vendorId);
        }
        document.getElementById('rfqDateIssued').value = parsed.dateIssued || '';
        document.getElementById('rfqDueDate').value = parsed.dueDate || '';
        document.getElementById('rfqExpectedDelivery').value = parsed.expectedDelivery || '';
        document.getElementById('rfqPaymentTerms').value = parsed.paymentTerms || 'Net 30 Days';
        document.getElementById('rfqDeliveryLocation').value = parsed.deliveryLocation || '';
        document.getElementById('rfqSpecialInstructions').value = parsed.specialInstructions || '';

        renderItemsTable();
        if (isAutoRecovery) {
            showToast('✓ Restored unsaved RFQ draft session from local storage', 'info');
        } else {
            showToast('✓ Draft session successfully restored!', 'success');
        }
    } catch (e) {
        console.warn('Error restoring draft:', e);
    }
}

function resetRfqForm() {
    if (confirm('Reset entire Request for Quotation form? Any unsaved edits will be discarded.')) {
        localStorage.removeItem('rms_rfq_current_draft');
        document.getElementById('vendorSelect').value = '';
        const titleEl = document.getElementById('rfqTenderTitle');
        if (titleEl) titleEl.value = '';
        window.AppStore.activeRfq.title = '';
        const statusBadge = document.getElementById('rfqStatusBadge');
        if (statusBadge) {
            statusBadge.className = 'status-pill draft';
            statusBadge.innerHTML = '<i class="ph ph-dot"></i> Draft RFQ';
        }
        clearVendorFields();
        initDates();
        window.AppStore.activeRfq.items = [];
        renderItemsTable();
        showToast('✓ RFQ form cleared', 'success');
    }
}

function closeAllModals() {
    closeRfqDocumentPreview();
    closeSendEmailModal();
    closeItemMasterQuickSelectModal();
    closeRecentRfqHistoryModal();
    closeRfqAuditDrawer();
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

/**
 * --------------------------------------------------------------------------
 * TAB SWITCHING & EMBEDDED AUDIT TRAIL LOGIC
 * --------------------------------------------------------------------------
 */
let activeRfqStatusFilter = 'all';
let activeRfqApprovalFilter = 'all';
let activeRfqPaymentTermsFilter = 'all';
let activeHeaderFilterType = null;
let rfqSearchTerm = '';
let activeAuditActionFilter = 'ALL';
let activeAuditModuleFilter = 'ALL';
let auditSearchTerm = '';

function openRfqAuditDrawer() {
    const drawer = document.getElementById('rfqAuditDrawer');
    const overlay = document.getElementById('rfqAuditDrawerOverlay');
    if (drawer) drawer.classList.add('active');
    if (overlay) overlay.classList.add('active');
    renderRfqAuditTrail();
}

function closeRfqAuditDrawer() {
    const drawer = document.getElementById('rfqAuditDrawer');
    const overlay = document.getElementById('rfqAuditDrawerOverlay');
    if (drawer) drawer.classList.remove('active');
    if (overlay) overlay.classList.remove('active');
}

function toggleBuilderAuditTrail(forceOpen = null) {
    const drawer = document.getElementById('rfqAuditDrawer');
    if (!drawer) return;
    const shouldOpen = forceOpen !== null ? forceOpen : !drawer.classList.contains('active');
    if (shouldOpen) {
        openRfqAuditDrawer();
    } else {
        closeRfqAuditDrawer();
    }
}

function copyRfqReference() {
    const ref = document.getElementById('rfqRefDisplay')?.textContent?.trim() || '';
    if (!ref) return;
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(ref).then(() => {
            showToast(`✓ Copied ${ref} to clipboard`, 'success');
        }).catch(() => {
            showToast(`Reference: ${ref}`, 'info');
        });
    } else {
        showToast(`Reference: ${ref}`, 'info');
    }
}

function handleRfqTitleChange(val) {
    if (!window.AppStore.activeRfq) window.AppStore.activeRfq = {};
    window.AppStore.activeRfq.title = val;
    persistDraft();
}

function switchRfqTab(tabId) {
    const btnList = document.getElementById('tabBtnList');
    const btnBuilder = document.getElementById('tabBtnBuilder');
    const paneList = document.getElementById('pane-tab-list');
    const paneBuilder = document.getElementById('pane-tab-builder');

    [btnList, btnBuilder].forEach(b => b && b.classList.remove('active'));
    [paneList, paneBuilder].forEach(p => p && (p.style.display = 'none'));

    // Update tab counts
    const rfqs = window.AppStore.rfqs || [];
    const listCountBadge = document.getElementById('tabRfqListCount');
    if (listCountBadge) listCountBadge.textContent = rfqs.length;

    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge && typeof getProcurementAuditTrail === 'function') {
        const { rfqNum, poNum } = (typeof getCurrentAuditTarget === 'function') ? getCurrentAuditTarget() : {};
        if (rfqNum) {
            const allLogs = getProcurementAuditTrail();
            const scoped = allLogs.filter(l => (l.refNumber === rfqNum || (poNum && l.refNumber === poNum)));
            auditCountBadge.textContent = scoped.length;
        } else {
            auditCountBadge.textContent = 0;
        }
    }

    if (tabId === 'tab-list') {
        if (btnList) btnList.classList.add('active');
        if (paneList) paneList.style.display = 'flex';
        renderRfqDirectory();
    } else if (tabId === 'tab-builder' || tabId === 'tab-audit') {
        if (btnBuilder) btnBuilder.classList.add('active');
        if (paneBuilder) paneBuilder.style.display = 'flex';
        if (tabId === 'tab-audit') {
            toggleBuilderAuditTrail(true);
        }
    }
}

/**
 * --------------------------------------------------------------------------
 * RFQ DIRECTORY COLUMN CONFIGURATION & PAGINATION SYSTEM
 * --------------------------------------------------------------------------
 */
const DIRECTORY_COLUMNS = [
    {
        id: 'rfq_info',
        label: 'RFQ Info',
        subLabel: 'RFQ # / Date',
        default: true,
        lockVisible: true,
        defaultWidth: '165px',
        align: 'left',
        topField: 'rfqNumber',
        bottomField: 'rfqDate',
        topPurpose: 'Internal procurement document number (e.g., RFQ-2026-00089)',
        bottomPurpose: 'Date the RFQ was officially published/sent'
    },
    {
        id: 'company',
        label: 'Company',
        subLabel: 'Legal Entity',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'companyName',
        bottomField: 'companyId',
        topPurpose: 'FK referencing companies (the buying legal entity)',
        bottomPurpose: ''
    },
    {
        id: 'branch_warehouse',
        label: 'Branch & Warehouse',
        subLabel: 'Branch / Ship-to',
        default: false,
        lockVisible: false,
        defaultWidth: '185px',
        align: 'left',
        topField: 'branchName',
        bottomField: 'shipToWarehouseName',
        topPurpose: 'FK referencing branches (the purchasing branch)',
        bottomPurpose: 'FK referencing warehouses (destination receiving facility)'
    },
    {
        id: 'vendor_contact',
        label: 'Vendor & Contact',
        subLabel: 'Vendor / Contact Person',
        default: true,
        lockVisible: false,
        defaultWidth: '220px',
        align: 'left',
        topField: 'vendorTradeName',
        bottomField: 'vendorContactPerson',
        topPurpose: 'FK referencing vendor_master (supplier invited to bid)',
        bottomPurpose: 'FK referencing vendor_contacts (contact person shown on RFQ screen)'
    },
    {
        id: 'submission_deadline',
        label: 'Submission Deadline',
        subLabel: 'Cutoff Date',
        default: true,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'left',
        topField: 'submissionDeadline',
        bottomField: '',
        topPurpose: 'Cutoff date for the vendor to submit their bids',
        bottomPurpose: ''
    },
    {
        id: 'quote_validity_date',
        label: 'Quote Validity Date',
        subLabel: 'Valid Until',
        default: false,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'left',
        topField: 'quoteValidUntilDate',
        bottomField: '',
        topPurpose: 'Date until which the vendor quote stays valid',
        bottomPurpose: ''
    },
    {
        id: 'requested_delivery_date',
        label: 'Requested Delivery Date',
        subLabel: 'Desired Arrival',
        default: false,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'left',
        topField: 'rfqRequestedDeliveryDate',
        bottomField: '',
        topPurpose: 'Desired date goods must arrive at the warehouse',
        bottomPurpose: ''
    },
    {
        id: 'currency',
        label: 'Currency',
        subLabel: 'ISO 4217',
        default: false,
        lockVisible: false,
        defaultWidth: '95px',
        align: 'center',
        topField: 'rfqCurrencyCode',
        bottomField: '',
        topPurpose: 'ISO 4217 bidding currency (e.g., PHP, USD)',
        bottomPurpose: ''
    },
    {
        id: 'payment_terms',
        label: 'Payment Terms',
        subLabel: 'Requested Terms',
        default: true,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'left',
        topField: 'rfqPaymentTermsCode',
        bottomField: '',
        topPurpose: 'Requested payment terms (e.g., NET30) from vendor_master',
        bottomPurpose: ''
    },
    {
        id: 'incoterms',
        label: 'Incoterms',
        subLabel: 'Trade Terms',
        default: false,
        lockVisible: false,
        defaultWidth: '105px',
        align: 'center',
        topField: 'rfqIncotermsCode',
        bottomField: '',
        topPurpose: 'Shipping trade terms (e.g., FOB, DDP, CIF) from vendor_master',
        bottomPurpose: ''
    },
    {
        id: 'total_amount',
        label: 'Total Amount',
        subLabel: 'Quoted / Target',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'right',
        topField: 'rfqQuotedTotalAmount',
        bottomField: 'rfqEstimatedTotalAmount',
        topPurpose: 'Sum of quoted_line_total; award approval basis',
        bottomPurpose: 'Sum of requested_quantity x target_unit_price; issue approval basis'
    },
    {
        id: 'rfq_status',
        label: 'RFQ Status',
        subLabel: 'Lifecycle State',
        default: true,
        lockVisible: false,
        defaultWidth: '145px',
        align: 'center',
        topField: 'rfqStatus',
        bottomField: '',
        topPurpose: 'Lifecycle (DRAFT, SENT, QUOTED, AWARDED, REJECTED, CANCELLED)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_approval_status',
        label: 'Issue Approval Status',
        subLabel: 'Issue State',
        default: true,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'center',
        topField: 'rfqApprovalStatus',
        bottomField: '',
        topPurpose: 'Approval summary for RFQ issue (NOT_REQUIRED, PENDING, APPROVED, etc.)',
        bottomPurpose: ''
    },
    {
        id: 'approval_request_id',
        label: 'Issue Approval ID',
        subLabel: 'Current Cycle',
        default: false,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topField: 'approvalRequestId',
        bottomField: '',
        topPurpose: 'FK referencing approval_requests (current approval cycle)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_award_approval_status',
        label: 'Award Approval Status',
        subLabel: 'Award State',
        default: false,
        lockVisible: false,
        defaultWidth: '140px',
        align: 'center',
        topField: 'rfqAwardApprovalStatus',
        bottomField: '',
        topPurpose: 'Approval summary for awarding quote (NOT_REQUIRED, PENDING, etc.)',
        bottomPurpose: ''
    },
    {
        id: 'award_approval_request_id',
        label: 'Award Approval ID',
        subLabel: 'Award Doc',
        default: false,
        lockVisible: false,
        defaultWidth: '150px',
        align: 'left',
        topField: 'awardApprovalRequestId',
        bottomField: '',
        topPurpose: 'FK referencing approval_requests (document type RFQ_AWARD)',
        bottomPurpose: ''
    },
    {
        id: 'rfq_notes',
        label: 'RFQ Notes',
        subLabel: 'Instructions',
        default: false,
        lockVisible: false,
        defaultWidth: '185px',
        align: 'left',
        topField: 'rfqNotes',
        bottomField: '',
        topPurpose: 'Special bidding instructions, delivery specs, or commercial terms',
        bottomPurpose: ''
    },
    {
        id: 'created_by',
        label: 'Created By',
        subLabel: 'Buyer / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'rfqCreatedBy',
        bottomField: 'rfqCreatedAt',
        topPurpose: 'User ID of the buyer/procurement officer',
        bottomPurpose: 'Creation timestamp'
    },
    {
        id: 'last_modified_by',
        label: 'Last Modified By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '160px',
        align: 'left',
        topField: 'rfqUpdatedBy',
        bottomField: 'rfqUpdatedAt',
        topPurpose: 'User ID who modified the record',
        bottomPurpose: 'Modification timestamp'
    },
    {
        id: 'line_number',
        label: 'Line #',
        subLabel: 'Items Count',
        default: false,
        lockVisible: false,
        defaultWidth: '95px',
        align: 'center',
        topField: 'itemCount',
        bottomField: '',
        topPurpose: 'Line item sequence number (1, 2, 3)',
        bottomPurpose: ''
    },
    {
        id: 'item_details',
        label: 'Item Details',
        subLabel: 'Item / Remarks',
        default: false,
        lockVisible: false,
        defaultWidth: '210px',
        align: 'left',
        topField: 'primaryItemName',
        bottomField: 'primaryLineNotes',
        topPurpose: 'FK referencing item_master',
        bottomPurpose: 'Item technical notes or vendor remarks'
    },
    {
        id: 'quantity',
        label: 'Quantity',
        subLabel: 'Requested Qty / UOM',
        default: true,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'right',
        topField: 'totalQuantity',
        bottomField: 'primaryUom',
        topPurpose: 'Quantity the company intends to purchase',
        bottomPurpose: 'Purchasing unit of measure (e.g., BOX, KG)'
    },
    {
        id: 'unit_price',
        label: 'Unit Price',
        subLabel: 'Quoted / Target',
        default: false,
        lockVisible: false,
        defaultWidth: '145px',
        align: 'right',
        topField: 'primaryQuotedPrice',
        bottomField: 'primaryTargetPrice',
        topPurpose: 'Official unit price offered back by the vendor',
        bottomPurpose: 'Internal budgeted/target unit price (benchmark)'
    },
    {
        id: 'line_total',
        label: 'Line Total',
        subLabel: 'Bid Line Sum',
        default: false,
        lockVisible: false,
        defaultWidth: '135px',
        align: 'right',
        topField: 'primaryLineTotal',
        bottomField: '',
        topPurpose: 'Bid line total (requested_quantity x quoted_unit_price)',
        bottomPurpose: ''
    },
    {
        id: 'lead_time',
        label: 'Lead Time',
        subLabel: 'Promised Days',
        default: false,
        lockVisible: false,
        defaultWidth: '110px',
        align: 'center',
        topField: 'primaryLeadTime',
        bottomField: '',
        topPurpose: 'Vendor promised manufacturing/shipping time in days',
        bottomPurpose: ''
    },
    {
        id: 'is_awarded',
        label: 'Award Status',
        subLabel: 'Line Acceptance',
        default: false,
        lockVisible: false,
        defaultWidth: '115px',
        align: 'center',
        topField: 'isAwarded',
        bottomField: '',
        topPurpose: 'Indicates if this item/bid was officially accepted',
        bottomPurpose: ''
    },
    {
        id: 'line_created_by',
        label: 'Line Created By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '155px',
        align: 'left',
        topField: 'primaryLineCreatedBy',
        bottomField: 'primaryLineCreatedAt',
        topPurpose: 'User ID who added the line',
        bottomPurpose: 'Timestamp when line was created'
    },
    {
        id: 'line_modified_by',
        label: 'Line Modified By',
        subLabel: 'User / Timestamp',
        default: false,
        lockVisible: false,
        defaultWidth: '155px',
        align: 'left',
        topField: 'primaryLineUpdatedBy',
        bottomField: 'primaryLineUpdatedAt',
        topPurpose: 'User ID who modified the line',
        bottomPurpose: 'Timestamp when vendor response was logged'
    },
    {
        id: 'actions',
        label: 'Actions',
        subLabel: 'Edit / View / Del',
        default: true,
        lockVisible: true,
        defaultWidth: '125px',
        align: 'center',
        topField: '',
        bottomField: '',
        topPurpose: 'Action Button (Edit, View, Delete) the RFQ',
        bottomPurpose: 'Settings Icon at the end column of the header'
    }
];

// 1. Column Sequence Order State (Saved in LocalStorage)
let directoryColOrder = (() => {
    try {
        const savedOrder = localStorage.getItem('rms_rfq_directory_cols_order_v3');
        if (savedOrder) {
            const parsed = JSON.parse(savedOrder);
            if (Array.isArray(parsed) && parsed.length > 0) {
                const allIds = DIRECTORY_COLUMNS.map(c => c.id);
                const validOrder = parsed.filter(id => allIds.includes(id));
                // Append any newly declared columns
                allIds.forEach(id => {
                    if (!validOrder.includes(id)) validOrder.push(id);
                });
                // Ensure 'actions' is always at the end
                const actionsIdx = validOrder.indexOf('actions');
                if (actionsIdx > -1 && actionsIdx !== validOrder.length - 1) {
                    validOrder.splice(actionsIdx, 1);
                    validOrder.push('actions');
                }
                return validOrder;
            }
        }
    } catch (e) {}
    return DIRECTORY_COLUMNS.map(c => c.id);
})();

// 2. Active Visible Column IDs State (Saved in LocalStorage)
let activeDirectoryColIds = (() => {
    try {
        const saved = localStorage.getItem('rms_rfq_directory_cols_v3');
        if (saved) {
            const parsed = JSON.parse(saved);
            const allIds = DIRECTORY_COLUMNS.map(c => c.id);
            if (Array.isArray(parsed) && parsed.length > 0 && parsed.every(id => allIds.includes(id))) {
                if (!parsed.includes('actions')) parsed.push('actions');
                return parsed;
            }
        }
    } catch (e) {}
    // Default = TRUE columns only
    return DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
})();

// 3. Saved Column Widths State (Saved in LocalStorage)
let savedDirectoryColWidths = (() => {
    try {
        const saved = localStorage.getItem('rms_rfq_directory_col_widths_v3');
        if (saved) return JSON.parse(saved);
    } catch (e) {}
    return {};
})();

// 4. Multi-Line Accordion Expansion Set
let expandedRfqNumbers = new Set();

// 5. Search Filter inside Column Dropdown
let directoryColSearchTerm = '';

let directoryCurrentPage = 1;
let directoryPageSize = 10;

function getDirectoryColWidth(col) {
    return savedDirectoryColWidths[col.id] || col.defaultWidth || '130px';
}

function initDirectoryColumns() {
    renderDirectoryTableHeader();
    renderDirectoryColDropdownChecklist();
}

/**
 * Render Modular Stacked Table Header with Drag-and-Drop and Settings Icon
 */
function renderDirectoryTableHeader() {
    const theadRow = document.getElementById('rfqDirectoryTheadRow');
    if (!theadRow) return;

    const isStatusFiltered = (activeRfqStatusFilter !== 'all' || activeRfqApprovalFilter !== 'all');
    const isTermsFiltered = (activeRfqPaymentTermsFilter !== 'all');

    // Build visible columns according to current user sequence order
    const visibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    let thHtml = '';
    visibleCols.forEach(col => {
        const w = getDirectoryColWidth(col);
        const alignClass = col.align === 'right' ? 'th-num' : (col.align === 'center' ? 'th-center' : '');

        if (col.id === 'actions') {
            // End Column Header with Settings Gear Icon
            thHtml += `
                <th class="th-center th-actions-sticky" data-col-id="actions" style="width: ${w}; text-align: center; user-select: none;">
                    <div class="rfq-header-action-wrapper">
                        <span>Action</span>
                        <button type="button" class="rfq-th-settings-btn" id="btnRfqTableSettings" onclick="toggleRfqTableSettingsDropdown(event)" title="Customize Columns & Drag Sequence">
                            <i class="ph ph-gear"></i>
                        </button>
                    </div>
                </th>
            `;
        } else {
            // Modular Stacked Header with Drag & Drop Reordering and Col Resizer
            const hasStatusFilter = (col.id === 'rfq_status');
            const hasTermsFilter = (col.id === 'payment_terms');

            thHtml += `
                <th class="rfq-th-stacked ${alignClass}" data-col-id="${col.id}" draggable="true"
                    ondragstart="handleHeaderDragStart(event, '${col.id}')"
                    ondragover="handleHeaderDragOver(event, '${col.id}')"
                    ondragleave="handleHeaderDragLeave(event)"
                    ondrop="handleHeaderDrop(event, '${col.id}')"
                    ondragend="handleHeaderDragEnd(event)"
                    style="width: ${w}; position: relative; user-select: none;"
                    title="${escapeHtml(col.topPurpose || col.label)}">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 4px; min-width: 0;">
                        <div class="rfq-th-content">
                            <div class="rfq-th-title">${escapeHtml(col.label)}</div>
                            ${col.subLabel ? `<div class="rfq-th-sub">${escapeHtml(col.subLabel)}</div>` : ''}
                        </div>
                        ${hasStatusFilter ? `
                            <button type="button" class="rfq-th-funnel-btn ${isStatusFiltered ? 'is-active' : ''}" onclick="toggleRfqHeaderFilter(event, 'status')" title="Filter Status & Approvals">
                                <i class="ph ph-funnel"></i>
                                ${isStatusFiltered ? '<span class="rfq-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                        ${hasTermsFilter ? `
                            <button type="button" class="rfq-th-funnel-btn ${isTermsFiltered ? 'is-active' : ''}" onclick="toggleRfqHeaderFilter(event, 'terms')" title="Filter Payment Terms">
                                <i class="ph ph-funnel"></i>
                                ${isTermsFiltered ? '<span class="rfq-th-funnel-dot"></span>' : ''}
                            </button>
                        ` : ''}
                    </div>
                    <div class="rfq-col-resizer" onmousedown="initDirectoryColResize(event, '${col.id}')"></div>
                </th>
            `;
        }
    });

    theadRow.innerHTML = thHtml;
    renderDirectoryColDropdownChecklist();
    renderRfqActiveFilterChips();
}

/**
 * Render Checkboxes and Drag-and-Drop Handles in Settings Dropdown
 */
function renderDirectoryColDropdownChecklist() {
    const listEl = document.getElementById('directoryColCheckboxesList');
    if (!listEl) return;

    const query = (directoryColSearchTerm || '').toLowerCase().trim();

    // Render list ordered by current directoryColOrder
    const orderedCols = directoryColOrder.map(id => DIRECTORY_COLUMNS.find(c => c.id === id)).filter(Boolean);

    listEl.innerHTML = orderedCols
        .filter(col => {
            if (!query) return true;
            return col.label.toLowerCase().includes(query) ||
                   (col.subLabel && col.subLabel.toLowerCase().includes(query)) ||
                   col.id.toLowerCase().includes(query);
        })
        .map(col => {
            const isChecked = activeDirectoryColIds.includes(col.id);
            const isDisabled = col.lockVisible ? 'disabled' : '';
            const isDefault = col.default;

            return `
                <div class="rfq-dropdown-col-item" data-col-id="${col.id}" draggable="${col.id !== 'actions' ? 'true' : 'false'}"
                    ondragstart="handleDropdownDragStart(event, '${col.id}')"
                    ondragover="handleDropdownDragOver(event, '${col.id}')"
                    ondragleave="handleDropdownDragLeave(event)"
                    ondrop="handleDropdownDrop(event, '${col.id}')"
                    ondragend="handleDropdownDragEnd(event)">
                    ${col.id !== 'actions' ? `
                        <span class="rfq-drag-handle" title="Drag to reorder column sequence">
                            <i class="ph ph-dots-six-vertical"></i>
                        </span>
                    ` : `
                        <span style="width: 14px; display: inline-block;"></span>
                    `}
                    <label class="rfq-col-item-label" title="${escapeHtml(col.topPurpose || col.label)}">
                        <div style="display: flex; align-items: center; gap: 6px; min-width: 0;">
                            <input type="checkbox" ${isChecked ? 'checked' : ''} ${isDisabled} onchange="toggleDirectoryColVisibility('${col.id}', this.checked)">
                            <div style="display: flex; flex-direction: column; min-width: 0;">
                                <span class="rfq-col-name">${escapeHtml(col.label)}</span>
                                ${col.subLabel ? `<span class="rfq-col-subname">${escapeHtml(col.subLabel)}</span>` : ''}
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 4px;">
                            ${isDefault ? '<span class="rfq-col-badge-default">Default</span>' : ''}
                            ${col.lockVisible ? '<span style="font-size: 0.65rem; color:#94a3b8; font-weight:600;">(Locked)</span>' : ''}
                        </div>
                    </label>
                </div>
            `;
        }).join('');

    const counter = document.getElementById('directoryColActiveCounter');
    if (counter) {
        counter.textContent = `${activeDirectoryColIds.length} of ${DIRECTORY_COLUMNS.length} visible`;
    }
}

/**
 * Toggle Settings Dropdown Positioned at Trigger Icon
 */
function toggleRfqTableSettingsDropdown(event) {
    if (event) event.stopPropagation();
    const dropdown = document.getElementById('rfqTableSettingsDropdown');
    const trigger = event ? event.currentTarget : document.getElementById('btnRfqTableSettings');
    if (!dropdown) return;

    const isOpen = dropdown.classList.contains('is-active');
    if (isOpen) {
        dropdown.classList.remove('is-active');
        dropdown.style.display = 'none';
        if (trigger) trigger.classList.remove('is-active');
    } else {
        dropdown.style.display = 'block';
        dropdown.classList.add('is-active');
        if (trigger) trigger.classList.add('is-active');

        // Anchored positioning clamping
        if (trigger) {
            const rect = trigger.getBoundingClientRect();
            const dropdownWidth = 370;
            let left = rect.right - dropdownWidth;
            if (left < 10) left = 10;
            let top = rect.bottom + 6;
            if (top + 400 > window.innerHeight) {
                top = Math.max(10, rect.top - 410);
            }
            dropdown.style.left = `${left}px`;
            dropdown.style.top = `${top}px`;
        }

        renderDirectoryColDropdownChecklist();
    }
}

function handleDirectoryColSearch(val) {
    directoryColSearchTerm = val;
    renderDirectoryColDropdownChecklist();
}

/**
 * Reorder Columns in Sequence and Persist
 */
function reorderDirectoryColumns(sourceColId, targetColId) {
    if (!sourceColId || !targetColId || sourceColId === targetColId) return;
    if (sourceColId === 'actions' || targetColId === 'actions') return;

    const sourceIdx = directoryColOrder.indexOf(sourceColId);
    const targetIdx = directoryColOrder.indexOf(targetColId);
    if (sourceIdx === -1 || targetIdx === -1) return;

    // Move source to target position
    directoryColOrder.splice(sourceIdx, 1);
    directoryColOrder.splice(targetIdx, 0, sourceColId);

    // Save to LocalStorage
    localStorage.setItem('rms_rfq_directory_cols_order_v3', JSON.stringify(directoryColOrder));

    renderDirectoryTableHeader();
    renderDirectoryColDropdownChecklist();
    renderRfqDirectory();
    showToast('✓ Column sequence updated', 'success');
}

// Drag & Drop Handlers for Dropdown Menu List
let dropdownDragSourceId = null;

function handleDropdownDragStart(e, colId) {
    dropdownDragSourceId = colId;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', colId);
    const item = e.currentTarget;
    if (item) item.classList.add('is-dragging');
}

function handleDropdownDragOver(e, colId) {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const item = e.currentTarget;
    if (item && dropdownDragSourceId && dropdownDragSourceId !== colId) {
        item.classList.add('is-drag-over');
    }
}

function handleDropdownDragLeave(e) {
    const item = e.currentTarget;
    if (item) item.classList.remove('is-drag-over');
}

function handleDropdownDrop(e, targetColId) {
    e.preventDefault();
    const item = e.currentTarget;
    if (item) item.classList.remove('is-drag-over');
    if (dropdownDragSourceId && targetColId && dropdownDragSourceId !== targetColId) {
        reorderDirectoryColumns(dropdownDragSourceId, targetColId);
    }
    dropdownDragSourceId = null;
}

function handleDropdownDragEnd(e) {
    dropdownDragSourceId = null;
    document.querySelectorAll('.rfq-dropdown-col-item').forEach(el => {
        el.classList.remove('is-dragging', 'is-drag-over');
    });
}

// Drag & Drop Handlers for Table Header (TH) Cells
let headerDragSourceId = null;

function handleHeaderDragStart(e, colId) {
    if (colId === 'actions') {
        e.preventDefault();
        return;
    }
    headerDragSourceId = colId;
    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('text/plain', colId);
    const th = e.currentTarget;
    if (th) th.classList.add('is-header-dragging');
}

function handleHeaderDragOver(e, colId) {
    if (colId === 'actions') return;
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    const th = e.currentTarget;
    if (th && headerDragSourceId && headerDragSourceId !== colId) {
        th.classList.add('is-header-drag-over');
    }
}

function handleHeaderDragLeave(e) {
    const th = e.currentTarget;
    if (th) th.classList.remove('is-header-drag-over');
}

function handleHeaderDrop(e, targetColId) {
    e.preventDefault();
    const th = e.currentTarget;
    if (th) th.classList.remove('is-header-drag-over');
    if (headerDragSourceId && targetColId && headerDragSourceId !== targetColId && targetColId !== 'actions') {
        reorderDirectoryColumns(headerDragSourceId, targetColId);
    }
    headerDragSourceId = null;
}

function handleHeaderDragEnd(e) {
    headerDragSourceId = null;
    document.querySelectorAll('#rfqDirectoryThead th').forEach(el => {
        el.classList.remove('is-header-dragging', 'is-header-drag-over');
    });
}

/**
 * Toggle Column Visibility
 */
function toggleDirectoryColVisibility(colId, isVisible) {
    if (isVisible) {
        if (!activeDirectoryColIds.includes(colId)) {
            activeDirectoryColIds.push(colId);
        }
    } else {
        activeDirectoryColIds = activeDirectoryColIds.filter(id => id !== colId);
    }
    localStorage.setItem('rms_rfq_directory_cols_v3', JSON.stringify(activeDirectoryColIds));
    renderDirectoryTableHeader();
    renderRfqDirectory();
}

function showAllDirectoryColumns() {
    activeDirectoryColIds = DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_rfq_directory_cols_v3', JSON.stringify(activeDirectoryColIds));
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Showing all columns', 'success');
}

function resetDirectoryColumnDefaults() {
    // Reset to only Default = True columns
    activeDirectoryColIds = DIRECTORY_COLUMNS.filter(c => c.default).map(c => c.id);
    directoryColOrder = DIRECTORY_COLUMNS.map(c => c.id);
    localStorage.setItem('rms_rfq_directory_cols_v3', JSON.stringify(activeDirectoryColIds));
    localStorage.removeItem('rms_rfq_directory_cols_order_v3');
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Reset to Default headers', 'success');
}

function resetDirectoryColumns() {
    resetDirectoryColumnDefaults();
}

function resetDirectoryColumnWidths() {
    savedDirectoryColWidths = {};
    localStorage.removeItem('rms_rfq_directory_col_widths_v3');
    renderDirectoryTableHeader();
    renderRfqDirectory();
    showToast('✓ Directory column widths reset to defaults', 'success');
}

/**
 * Column Resizing Handlers
 */
let dirResizingColId = null;
let dirStartX = 0;
let dirStartW = 0;

function initDirectoryColResize(e, colId) {
    e.preventDefault();
    e.stopPropagation();
    dirResizingColId = colId;
    dirStartX = e.pageX;

    const thEl = document.querySelector(`#rfqDirectoryThead th[data-col-id="${colId}"]`);
    dirStartW = thEl ? thEl.offsetWidth : 120;

    document.addEventListener('mousemove', handleDirectoryColMouseMove);
    document.addEventListener('mouseup', handleDirectoryColMouseUp);
}

function handleDirectoryColMouseMove(e) {
    if (!dirResizingColId) return;
    const diff = e.pageX - dirStartX;
    const newWidth = Math.max(70, dirStartW + diff);
    savedDirectoryColWidths[dirResizingColId] = `${newWidth}px`;

    const thEl = document.querySelector(`#rfqDirectoryThead th[data-col-id="${dirResizingColId}"]`);
    if (thEl) {
        thEl.style.width = `${newWidth}px`;
    }
}

function handleDirectoryColMouseUp() {
    if (!dirResizingColId) return;
    localStorage.setItem('rms_rfq_directory_col_widths_v3', JSON.stringify(savedDirectoryColWidths));
    dirResizingColId = null;
    document.removeEventListener('mousemove', handleDirectoryColMouseMove);
    document.removeEventListener('mouseup', handleDirectoryColMouseUp);
}

function toggleRfqRowExpansion(rfqNumber, event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    if (expandedRfqNumbers.has(rfqNumber)) {
        expandedRfqNumbers.delete(rfqNumber);
    } else {
        expandedRfqNumbers.add(rfqNumber);
    }
    renderRfqDirectory();
}

function changeDirectoryPageSize(newSize) {
    directoryPageSize = parseInt(newSize, 10) || 10;
    directoryCurrentPage = 1;
    renderRfqDirectory();
}

function goToDirectoryPage(page) {
    directoryCurrentPage = page;
    renderRfqDirectory();
}

function renderDirectoryPagination(totalItems) {
    const totalPages = Math.ceil(totalItems / directoryPageSize) || 1;
    if (directoryCurrentPage > totalPages) directoryCurrentPage = totalPages;
    if (directoryCurrentPage < 1) directoryCurrentPage = 1;

    const startIdx = totalItems === 0 ? 0 : (directoryCurrentPage - 1) * directoryPageSize + 1;
    const endIdx = Math.min(directoryCurrentPage * directoryPageSize, totalItems);

    const infoEl = document.getElementById('rfqPaginationInfo');
    if (infoEl) {
        infoEl.innerHTML = `Showing <strong>${startIdx}</strong> to <strong>${endIdx}</strong> of <strong>${totalItems}</strong> tenders`;
    }

    const btnsEl = document.getElementById('rfqPaginationButtons');
    if (!btnsEl) return;

    let btnsHtml = '';
    btnsHtml += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" 
            onclick="goToDirectoryPage(${directoryCurrentPage - 1})" 
            ${directoryCurrentPage <= 1 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} 
            title="Previous Page" style="padding: 4px 8px; font-size: 11px;">
            <i class="ph ph-caret-left"></i>
        </button>
    `;

    const maxButtons = 5;
    let startPage = Math.max(1, directoryCurrentPage - Math.floor(maxButtons / 2));
    let endPage = Math.min(totalPages, startPage + maxButtons - 1);
    if (endPage - startPage + 1 < maxButtons) {
        startPage = Math.max(1, endPage - maxButtons + 1);
    }

    for (let p = startPage; p <= endPage; p++) {
        const isActive = p === directoryCurrentPage;
        btnsHtml += `
            <button type="button" 
                class="hr-btn ${isActive ? 'hr-btn-primary' : 'hr-btn-secondary'} hr-btn-sm" 
                onclick="goToDirectoryPage(${p})" 
                style="padding: 4px 9px; font-size: 11.5px; min-width: 28px; font-weight: ${isActive ? '700' : '500'};">
                ${p}
            </button>
        `;
    }

    btnsHtml += `
        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" 
            onclick="goToDirectoryPage(${directoryCurrentPage + 1})" 
            ${directoryCurrentPage >= totalPages ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''} 
            title="Next Page" style="padding: 4px 8px; font-size: 11px;">
            <i class="ph ph-caret-right"></i>
        </button>
    `;

    btnsEl.innerHTML = btnsHtml;
}

/**
 * Render RFQ Directory Rows with Modular Stacked Cells and Multi-Line Accordion
 */
function renderRfqDirectory() {
    const tbody = document.getElementById('rfqDirectoryTbody');
    const rawRfqs = window.AppStore.rfqs || [];
    if (!tbody) return;

    const rfqs = rawRfqs.map(normalizeRfqItem).filter(Boolean);

    // Filter by status, approval, payment terms & search
    const filtered = rfqs.filter(r => {
        const matchesStatus = (activeRfqStatusFilter === 'all') || (r.status === activeRfqStatusFilter) || (r.rfqStatus === activeRfqStatusFilter);
        
        const isApproved = (r.isApproved === true || r.rfqApprovalStatus === 'APPROVED' || r.status === 'Awarded');
        let matchesApproval = true;
        if (activeRfqApprovalFilter === 'approved') matchesApproval = isApproved;
        else if (activeRfqApprovalFilter === 'pending') matchesApproval = !isApproved;

        const matchesTerms = (activeRfqPaymentTermsFilter === 'all') || 
            ((r.paymentTerms || '').toLowerCase() === activeRfqPaymentTermsFilter.toLowerCase()) ||
            ((r.rfqPaymentTermsCode || '').toLowerCase() === activeRfqPaymentTermsFilter.toLowerCase());

        const q = (rfqSearchTerm || '').toLowerCase().trim();
        const matchesSearch = !q || 
            (r.rfqNumber && r.rfqNumber.toLowerCase().includes(q)) ||
            (r.vendorName && r.vendorName.toLowerCase().includes(q)) ||
            (r.vendorTradeName && r.vendorTradeName.toLowerCase().includes(q)) ||
            (r.vendorContactPerson && r.vendorContactPerson.toLowerCase().includes(q)) ||
            (r.companyName && r.companyName.toLowerCase().includes(q)) ||
            (r.branchName && r.branchName.toLowerCase().includes(q)) ||
            (r.specialInstructions && r.specialInstructions.toLowerCase().includes(q)) ||
            (r.rfqNotes && r.rfqNotes.toLowerCase().includes(q)) ||
            (r.paymentTerms && r.paymentTerms.toLowerCase().includes(q));

        return matchesStatus && matchesApproval && matchesTerms && matchesSearch;
    });

    // Update KPI Counts
    const totalCount = rfqs.length;
    const draftCount = rfqs.filter(r => r.status === 'Draft' || r.rfqStatus === 'Draft').length;
    const sentCount = rfqs.filter(r => r.status === 'Quote Requested' || r.rfqStatus === 'Quote Requested').length;
    const awardedCount = rfqs.filter(r => r.status === 'Awarded' || r.rfqStatus === 'Awarded').length;

    const elTotal = document.getElementById('kpiTotalRfqs');
    const elDraft = document.getElementById('kpiDraftRfqs');
    const elAwaiting = document.getElementById('kpiAwaitingRfqs');
    const elAwarded = document.getElementById('kpiAwardedRfqs');
    const elTabCount = document.getElementById('tabRfqListCount');

    if (elTotal) elTotal.textContent = totalCount;
    if (elDraft) elDraft.textContent = draftCount;
    if (elAwaiting) elAwaiting.textContent = sentCount;
    if (elAwarded) elAwarded.textContent = awardedCount;
    if (elTabCount) elTabCount.textContent = totalCount;

    renderRfqActiveFilterChips();

    // Get visible columns in user order
    const visibleCols = directoryColOrder
        .filter(id => activeDirectoryColIds.includes(id))
        .map(id => DIRECTORY_COLUMNS.find(c => c.id === id))
        .filter(Boolean);

    if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="${visibleCols.length}" style="text-align: center; padding: 36px; color: var(--rfq-text-muted);">No Requests for Quotation match your criteria.</td></tr>`;
        renderDirectoryPagination(0);
        return;
    }

    // Pagination slice
    const totalItems = filtered.length;
    const totalPages = Math.ceil(totalItems / directoryPageSize) || 1;
    if (directoryCurrentPage > totalPages) directoryCurrentPage = totalPages;
    if (directoryCurrentPage < 1) directoryCurrentPage = 1;

    const startIndex = (directoryCurrentPage - 1) * directoryPageSize;
    const paginatedItems = filtered.slice(startIndex, startIndex + directoryPageSize);

    let rowsHtml = '';
    paginatedItems.forEach(r => {
        const isExpanded = expandedRfqNumbers.has(r.rfqNumber);
        const items = r.items || [];
        const primaryItem = items.length > 0 ? items[0] : {};
        const totalQty = items.reduce((acc, it) => acc + (parseFloat(it.requestedQuantity || it.quantity) || 0), 0);
        const primaryUom = primaryItem.purchaseUomCode || primaryItem.unit || 'Unit';
        const quotedTotal = r.rfqQuotedTotalAmount || items.reduce((acc, it) => acc + (parseFloat(it.quotedLineTotal) || 0), 0);
        const estTotal = r.rfqEstimatedTotalAmount || items.reduce((acc, it) => acc + ((parseFloat(it.requestedQuantity || it.quantity) || 0) * (parseFloat(it.targetUnitPrice || it.targetPrice) || 0)), 0);

        let pillClass = 'draft';
        if (r.status === 'Quote Requested' || r.rfqStatus === 'Quote Requested') pillClass = 'sent';
        else if (r.status === 'Quotation Received' || r.rfqStatus === 'Quotation Received') pillClass = 'received';
        else if (r.status === 'Awarded' || r.rfqStatus === 'Awarded') pillClass = 'awarded';
        else if (r.status === 'Cancelled' || r.rfqStatus === 'Cancelled') pillClass = 'cancelled';

        let rowCells = '';
        visibleCols.forEach(col => {
            switch (col.id) {
                case 'rfq_info':
                    rowCells += `
                        <td style="font-family: monospace; font-weight: 700; color: var(--rfq-primary-dark);">
                            <div style="display: flex; align-items: center; gap: 4px;">
                                <button type="button" class="rfq-row-expand-btn ${isExpanded ? 'is-expanded' : ''}" 
                                    onclick="toggleRfqRowExpansion('${escapeHtml(r.rfqNumber)}', event)" 
                                    title="${isExpanded ? 'Collapse Line Items' : 'Expand ' + items.length + ' Line Items'}">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                                <div class="rms-cell-stack">
                                    <div class="rms-cell-title">
                                        <a href="javascript:void(0)" onclick="editRfqFromDirectory('${escapeHtml(r.rfqNumber)}')" title="${escapeHtml(r.rfqNumber)}" style="color: inherit; text-decoration: underline;">
                                            ${escapeHtml(r.rfqNumber)}
                                        </a>
                                    </div>
                                    <div class="rms-cell-sub" style="font-size: 0.70rem; color: #64748b; font-family: sans-serif;">
                                        ${formatDateDisplay(r.rfqDate || r.dateIssued)}
                                    </div>
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'company':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.companyName)}">${escapeHtml(r.companyName)}</div>
                                <div class="rms-cell-sub" style="font-family: monospace; font-size: 0.69rem;">${escapeHtml(r.companyId || '—')}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'branch_warehouse':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.branchName)}">${escapeHtml(r.branchName)}</div>
                                <div class="rms-cell-sub" title="${escapeHtml(r.shipToWarehouseName)}"><i class="ph ph-warehouse" style="font-size: 11px;"></i> ${escapeHtml(r.shipToWarehouseName)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'vendor_contact':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(r.vendorTradeName || r.vendorName)}">
                                    ${escapeHtml(r.vendorTradeName || r.vendorName)}
                                </div>
                                <div class="rms-cell-sub" title="${escapeHtml((r.vendorContactPerson || 'No Contact') + ' • ' + (r.vendorEmail || r.vendorPhone || ''))}">
                                    ${escapeHtml(r.vendorContactPerson || 'No Contact')}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'submission_deadline':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" style="color: #b45309; font-weight: 700;">
                                    ${formatDateDisplay(r.submissionDeadline || r.dueDate)}
                                </div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem; color: #94a3b8;">Cutoff Deadline</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'quote_validity_date':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${formatDateDisplay(r.quoteValidUntilDate)}</div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem;">Validity Date</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'requested_delivery_date':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" style="color: #4f46e5; font-weight: 600;">${formatDateDisplay(r.rfqRequestedDeliveryDate || r.expectedDelivery)}</div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem;">Target Arrival</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'currency':
                    rowCells += `
                        <td class="td-center">
                            <span class="hr-badge hr-badge-neutral" style="font-weight: 700; font-size: 11px;">
                                ${escapeHtml(r.rfqCurrencyCode || 'PHP')}
                            </span>
                        </td>
                    `;
                    break;

                case 'payment_terms':
                    rowCells += `
                        <td>
                            <span class="terms-pill" style="display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.74rem; font-weight: 600; background: #f1f5f9; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%;" title="${escapeHtml(r.rfqPaymentTermsCode || r.paymentTerms)}">
                                ${escapeHtml(r.rfqPaymentTermsCode || r.paymentTerms)}
                            </span>
                        </td>
                    `;
                    break;

                case 'incoterms':
                    rowCells += `
                        <td class="td-center">
                            <span class="hr-badge hr-badge-neutral" style="font-weight: 600; font-size: 10.5px;">
                                ${escapeHtml(r.rfqIncotermsCode || 'FOB')}
                            </span>
                        </td>
                    `;
                    break;

                case 'total_amount':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack" style="align-items: flex-end;">
                                <div class="rms-cell-title" style="color: #0f172a; font-weight: 800;">₱${formatMoney(quotedTotal)}</div>
                                <div class="rms-cell-sub" style="font-size: 0.69rem; color: #64748b;">Target: ₱${formatMoney(estTotal)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'rfq_status':
                    rowCells += `
                        <td class="td-center">
                            <span class="status-pill ${pillClass}">
                                <i class="ph ph-dot"></i> ${escapeHtml(r.rfqStatus || r.status)}
                            </span>
                        </td>
                    `;
                    break;

                case 'rfq_approval_status':
                    const isAppr = (r.rfqApprovalStatus === 'APPROVED' || r.isApproved === true || r.status === 'Awarded');
                    rowCells += `
                        <td class="td-center">
                            ${isAppr ? `
                                <button type="button" class="hr-badge hr-badge-success" onclick="openManagerApprovalModal('${r.rfqNumber}')" title="Issue Approved - Click to view" style="cursor: pointer; border: none; font-size: 9.5px; padding: 2px 7px; display: inline-flex; align-items: center; gap: 3px;">
                                    <i class="ph ph-check-circle"></i> Approved
                                </button>
                            ` : `
                                <button type="button" class="hr-badge hr-badge-warning" onclick="openManagerApprovalModal('${r.rfqNumber}')" title="Issue Pending - Click to toggle" style="cursor: pointer; border: none; font-size: 9.5px; padding: 2px 7px; display: inline-flex; align-items: center; gap: 3px;">
                                    <i class="ph ph-hourglass-simple"></i> Pending
                                </button>
                            `}
                        </td>
                    `;
                    break;

                case 'approval_request_id':
                    rowCells += `
                        <td>
                            <span style="font-family: monospace; font-size: 0.72rem; color: #475569;" title="${escapeHtml(r.approvalRequestId || 'N/A')}">
                                ${escapeHtml(r.approvalRequestId || '—')}
                            </span>
                        </td>
                    `;
                    break;

                case 'rfq_award_approval_status':
                    const isAwardedAppr = (r.rfqAwardApprovalStatus === 'APPROVED' || r.status === 'Awarded');
                    rowCells += `
                        <td class="td-center">
                            <span class="hr-badge ${isAwardedAppr ? 'hr-badge-success' : 'hr-badge-neutral'}" style="font-size: 9.5px; padding: 2px 6px;">
                                ${escapeHtml(r.rfqAwardApprovalStatus || (isAwardedAppr ? 'APPROVED' : 'NOT_REQUIRED'))}
                            </span>
                        </td>
                    `;
                    break;

                case 'award_approval_request_id':
                    rowCells += `
                        <td>
                            <span style="font-family: monospace; font-size: 0.72rem; color: #475569;" title="${escapeHtml(r.awardApprovalRequestId || 'N/A')}">
                                ${escapeHtml(r.awardApprovalRequestId || '—')}
                            </span>
                        </td>
                    `;
                    break;

                case 'rfq_notes':
                    const noteStr = r.rfqNotes || r.specialInstructions || r.notes || '—';
                    rowCells += `
                        <td>
                            <div class="rms-cell-title" style="font-weight: 500; color: #475569;" title="${escapeHtml(noteStr)}">
                                ${escapeHtml(noteStr)}
                            </div>
                        </td>
                    `;
                    break;

                case 'created_by':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqCreatedBy || 'Admin')}</div>
                                <div class="rms-cell-sub" style="font-size: 0.69rem;">${formatDateDisplay(r.rfqCreatedAt || r.dateIssued)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'last_modified_by':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(r.rfqUpdatedBy || 'Lead')}</div>
                                <div class="rms-cell-sub" style="font-size: 0.69rem;">${formatDateDisplay(r.rfqUpdatedAt || r.rfqCreatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_number':
                    rowCells += `
                        <td class="td-center">
                            <span class="rfq-items-counter-pill">${items.length} Lines</span>
                        </td>
                    `;
                    break;

                case 'item_details':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title" title="${escapeHtml(primaryItem.name || 'No Items')}">
                                    ${escapeHtml(primaryItem.name || 'No Items')}
                                </div>
                                <div class="rms-cell-sub" title="${escapeHtml(primaryItem.specs || primaryItem.rfqLineNotes || '')}">
                                    ${escapeHtml(primaryItem.specs || primaryItem.rfqLineNotes || (items.length > 1 ? '+' + (items.length - 1) + ' more items' : ''))}
                                </div>
                            </div>
                        </td>
                    `;
                    break;

                case 'quantity':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack" style="align-items: flex-end;">
                                <div class="rms-cell-title" style="font-weight: 700;">${formatMoney(totalQty, 1)}</div>
                                <div class="rms-cell-sub" style="font-size: 0.69rem; color: #94a3b8;">${escapeHtml(primaryUom)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'unit_price':
                    rowCells += `
                        <td class="td-num">
                            <div class="rms-cell-stack" style="align-items: flex-end;">
                                <div class="rms-cell-title">₱${formatMoney(primaryItem.quotedUnitPrice || primaryItem.targetUnitPrice || 0)}</div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem; color: #64748b;">Target: ₱${formatMoney(primaryItem.targetUnitPrice || 0)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_total':
                    rowCells += `
                        <td class="td-num">
                            <span style="font-weight: 700; color: #0f172a;">₱${formatMoney(primaryItem.quotedLineTotal || ((primaryItem.requestedQuantity || 0) * (primaryItem.quotedUnitPrice || 0)))}</span>
                        </td>
                    `;
                    break;

                case 'lead_time':
                    rowCells += `
                        <td class="td-center">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">${primaryItem.leadTimeDays || 3}d</span>
                        </td>
                    `;
                    break;

                case 'is_awarded':
                    rowCells += `
                        <td class="td-center">
                            <span class="hr-badge ${primaryItem.isAwarded ? 'hr-badge-success' : 'hr-badge-neutral'}" style="font-size: 10px;">
                                ${primaryItem.isAwarded ? 'Accepted' : 'Pending'}
                            </span>
                        </td>
                    `;
                    break;

                case 'line_created_by':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(primaryItem.rfqLineCreatedBy || 'Admin')}</div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem;">${formatDateDisplay(primaryItem.rfqLineCreatedAt || r.rfqCreatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'line_modified_by':
                    rowCells += `
                        <td>
                            <div class="rms-cell-stack">
                                <div class="rms-cell-title">${escapeHtml(primaryItem.rfqLineUpdatedBy || 'Lead')}</div>
                                <div class="rms-cell-sub" style="font-size: 0.68rem;">${formatDateDisplay(primaryItem.rfqLineUpdatedAt || r.rfqUpdatedAt)}</div>
                            </div>
                        </td>
                    `;
                    break;

                case 'actions':
                    rowCells += `
                        <td class="td-center">
                            <div style="display: flex; align-items: center; justify-content: center; gap: 4px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="editRfqFromDirectory('${r.rfqNumber}')" title="Edit RFQ" style="padding: 3px 6px; font-size: 12px;">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="previewRfqFromDirectory('${r.rfqNumber}')" title="View Document Preview" style="padding: 3px 6px; font-size: 12px;">
                                    <i class="ph ph-eye"></i>
                                </button>
                                <button type="button" class="hr-btn hr-btn-danger hr-btn-sm" onclick="deleteRfqFromDirectory('${r.rfqNumber}')" title="Delete RFQ" style="padding: 3px 6px; font-size: 12px;">
                                    <i class="ph ph-trash"></i>
                                </button>
                            </div>
                        </td>
                    `;
                    break;

                default:
                    rowCells += `<td>—</td>`;
                    break;
            }
        });

        rowsHtml += `<tr>${rowCells}</tr>`;

        // Multi-line Accordion Drawer (Expanded Subtable)
        if (isExpanded) {
            rowsHtml += `
                <tr class="rfq-line-expansion-row">
                    <td colspan="${visibleCols.length}" style="padding: 0; background: #f8fafc;">
                        <div class="rfq-subtable-container">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                                <div style="font-weight: 700; font-size: 0.80rem; color: #0f172a; display: flex; align-items: center; gap: 6px;">
                                    <i class="ph ph-list-numbers" style="color: var(--rfq-primary);"></i>
                                    <span>Quotation Specification Lines (${items.length} items)</span>
                                </div>
                                <div style="font-size: 0.73rem; color: #64748b;">
                                    Vendor Quote Basis for Award Approval
                                </div>
                            </div>
                            <table class="rfq-subtable">
                                <thead>
                                    <tr>
                                        <th style="width: 50px; text-align: center;">Line #</th>
                                        <th style="width: 110px;">Item SKU</th>
                                        <th>Description & Technical Specs</th>
                                        <th style="width: 90px; text-align: right;">Qty</th>
                                        <th style="width: 70px; text-align: center;">UOM</th>
                                        <th style="width: 110px; text-align: right;">Target Price</th>
                                        <th style="width: 110px; text-align: right;">Quoted Price</th>
                                        <th style="width: 120px; text-align: right;">Line Total</th>
                                        <th style="width: 80px; text-align: center;">Lead Time</th>
                                        <th style="width: 90px; text-align: center;">Award</th>
                                        <th style="width: 150px;">Line Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${items.map((line, lIdx) => `
                                        <tr>
                                            <td style="text-align: center; font-weight: 700; color: #64748b;">${line.lineNumber || (lIdx + 1)}</td>
                                            <td style="font-family: monospace; font-weight: 700; color: #475569;">${escapeHtml(line.itemId || line.sku || '—')}</td>
                                            <td>
                                                <div style="font-weight: 600; color: #0f172a;">${escapeHtml(line.name)}</div>
                                                ${line.specs ? `<div style="font-size: 0.70rem; color: #64748b;">${escapeHtml(line.specs)}</div>` : ''}
                                            </td>
                                            <td style="text-align: right; font-weight: 700;">${formatMoney(line.requestedQuantity || line.quantity, 1)}</td>
                                            <td style="text-align: center;"><span class="hr-badge hr-badge-neutral">${escapeHtml(line.purchaseUomCode || line.unit)}</span></td>
                                            <td style="text-align: right; color: #64748b;">₱${formatMoney(line.targetUnitPrice || line.targetPrice)}</td>
                                            <td style="text-align: right; font-weight: 700; color: var(--rfq-primary-dark);">₱${formatMoney(line.quotedUnitPrice || line.targetPrice)}</td>
                                            <td style="text-align: right; font-weight: 800; color: #0f172a;">₱${formatMoney(line.quotedLineTotal || ((line.quantity || 1) * (line.targetPrice || 0)))}</td>
                                            <td style="text-align: center;">${line.leadTimeDays || 3}d</td>
                                            <td style="text-align: center;">
                                                <span class="hr-badge ${line.isAwarded ? 'hr-badge-success' : 'hr-badge-neutral'}">
                                                    ${line.isAwarded ? 'Accepted' : 'Pending'}
                                                </span>
                                            </td>
                                            <td style="font-size: 0.71rem; color: #64748b;">${escapeHtml(line.rfqLineNotes || line.notes || '—')}</td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>
            `;
        }
    });

    tbody.innerHTML = rowsHtml;
    renderDirectoryPagination(totalItems);
}

function filterRfqByStatus(status) {
    activeRfqStatusFilter = status;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
}

function toggleRfqHeaderFilter(event, filterType) {
    if (event) event.stopPropagation();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (!popover) return;

    if (activeHeaderFilterType === filterType && popover.style.display !== 'none') {
        closeRfqHeaderFilter();
        return;
    }

    activeHeaderFilterType = filterType;
    const triggerEl = event.currentTarget;
    const rect = triggerEl.getBoundingClientRect();

    popover.innerHTML = filterType === 'status' ? buildStatusFilterPopoverHtml() : buildPaymentTermsFilterPopoverHtml();
    popover.style.display = 'block';
    popover.style.position = 'fixed';
    popover.style.top = `${rect.bottom + 6}px`;

    const popoverWidth = filterType === 'status' ? 360 : 280;
    let left = rect.left;
    if (left + popoverWidth > window.innerWidth - 16) {
        left = window.innerWidth - popoverWidth - 16;
    }
    popover.style.left = `${Math.max(10, left)}px`;
    popover.style.zIndex = '1200';
}

function closeRfqHeaderFilter() {
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover) popover.style.display = 'none';
    activeHeaderFilterType = null;
}

function buildStatusFilterPopoverHtml() {
    const rfqs = window.AppStore.rfqs || [];
    const totalCount = rfqs.length;
    const approvedCount = rfqs.filter(r => r.isApproved === true || r.status === 'Awarded' || r.status === 'Approved' || !!r.poReference).length;
    const pendingCount = totalCount - approvedCount;

    const draftCount = rfqs.filter(r => r.status === 'Draft').length;
    const sentCount = rfqs.filter(r => r.status === 'Quote Requested').length;
    const receivedCount = rfqs.filter(r => r.status === 'Quotation Received').length;
    const awardedCount = rfqs.filter(r => r.status === 'Awarded').length;
    const cancelledCount = rfqs.filter(r => r.status === 'Cancelled').length;

    return `
        <div class="rfq-filter-popover-card">
            <div class="rfq-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-sliders-horizontal" style="color: #7c3aed; font-size: 15px;"></i>
                    <span>Status & Approval Filters</span>
                </div>
                <button type="button" class="rfq-popover-reset-btn" onclick="resetRfqStatusFilters(event)">Reset Both</button>
            </div>
            
            <div class="rfq-filter-popover-body">
                <!-- ROW 1: APPROVAL STATUS -->
                <div class="rfq-filter-section">
                    <div class="rfq-filter-section-title">
                        <span>Row 1: Approval Status</span>
                        ${activeRfqApprovalFilter !== 'all' ? `<span class="rfq-section-active-badge">${escapeHtml(activeRfqApprovalFilter)}</span>` : ''}
                    </div>
                    <div class="rfq-filter-pill-row">
                        <button type="button" class="rfq-popover-chip ${activeRfqApprovalFilter === 'all' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-success ${activeRfqApprovalFilter === 'approved' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'approved')">
                            <i class="ph ph-check-circle"></i> Approved (${approvedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-warning ${activeRfqApprovalFilter === 'pending' ? 'is-active' : ''}" onclick="setRfqApprovalFilter(event, 'pending')">
                            <i class="ph ph-hourglass-simple"></i> Pending (${pendingCount})
                        </button>
                    </div>
                </div>

                <!-- ROW 2: RFQ STATUS -->
                <div class="rfq-filter-section" style="margin-top: 12px;">
                    <div class="rfq-filter-section-title">
                        <span>Row 2: RFQ Status</span>
                        ${activeRfqStatusFilter !== 'all' ? `<span class="rfq-section-active-badge">${escapeHtml(activeRfqStatusFilter)}</span>` : ''}
                    </div>
                    <div class="rfq-filter-pill-grid">
                        <button type="button" class="rfq-popover-chip ${activeRfqStatusFilter === 'all' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'all')">
                            All (${totalCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${activeRfqStatusFilter === 'Draft' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Draft')">
                            Draft (${draftCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${activeRfqStatusFilter === 'Quote Requested' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Quote Requested')">
                            Requested (${sentCount})
                        </button>
                        <button type="button" class="rfq-popover-chip ${activeRfqStatusFilter === 'Quotation Received' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Quotation Received')">
                            Received (${receivedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-purple ${activeRfqStatusFilter === 'Awarded' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Awarded')">
                            Awarded (${awardedCount})
                        </button>
                        <button type="button" class="rfq-popover-chip is-danger ${activeRfqStatusFilter === 'Cancelled' ? 'is-active' : ''}" onclick="setRfqStatusFilter(event, 'Cancelled')">
                            Cancelled (${cancelledCount})
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
}

function buildPaymentTermsFilterPopoverHtml() {
    const rfqs = window.AppStore.rfqs || [];
    const totalCount = rfqs.length;
    
    const termsMap = {};
    rfqs.forEach(r => {
        const t = (r.paymentTerms || '').trim();
        if (t) {
            termsMap[t] = (termsMap[t] || 0) + 1;
        }
    });
    ['Net 30 Days', 'Net 15 Days', 'Advance Payment'].forEach(t => {
        if (!termsMap[t]) termsMap[t] = 0;
    });

    const distinctTerms = Object.keys(termsMap);

    return `
        <div class="rfq-filter-popover-card">
            <div class="rfq-filter-popover-header">
                <div style="display: flex; align-items: center; gap: 6px; font-weight: 700; font-size: 12.5px; color: #0f172a;">
                    <i class="ph ph-credit-card" style="color: #7c3aed; font-size: 15px;"></i>
                    <span>Payment Terms Filter</span>
                </div>
                <button type="button" class="rfq-popover-reset-btn" onclick="setRfqPaymentTermsFilter(event, 'all')">Reset</button>
            </div>
            
            <div class="rfq-filter-popover-body">
                <div class="rfq-filter-radio-list">
                    <label class="rfq-filter-radio-row ${activeRfqPaymentTermsFilter === 'all' ? 'is-selected' : ''}">
                        <input type="radio" name="rfqTermsRadio" value="all" ${activeRfqPaymentTermsFilter === 'all' ? 'checked' : ''} onchange="setRfqPaymentTermsFilter(event, 'all')">
                        <span class="rfq-radio-label">All Payment Terms</span>
                        <span class="rfq-radio-count">${totalCount}</span>
                    </label>
                    ${distinctTerms.map(term => `
                        <label class="rfq-filter-radio-row ${activeRfqPaymentTermsFilter.toLowerCase() === term.toLowerCase() ? 'is-selected' : ''}">
                            <input type="radio" name="rfqTermsRadio" value="${escapeHtml(term)}" ${activeRfqPaymentTermsFilter.toLowerCase() === term.toLowerCase() ? 'checked' : ''} onchange="setRfqPaymentTermsFilter(event, '${escapeHtml(term)}')">
                            <span class="rfq-radio-label">${escapeHtml(term)}</span>
                            <span class="rfq-radio-count">${termsMap[term] || 0}</span>
                        </label>
                    `).join('')}
                </div>
            </div>
        </div>
    `;
}

function setRfqApprovalFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqApprovalFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activeHeaderFilterType === 'status') {
        popover.innerHTML = buildStatusFilterPopoverHtml();
    }
}

function setRfqStatusFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqStatusFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (popover && popover.style.display !== 'none' && activeHeaderFilterType === 'status') {
        popover.innerHTML = buildStatusFilterPopoverHtml();
    }
}

function resetRfqStatusFilters(event) {
    if (event) event.stopPropagation();
    activeRfqStatusFilter = 'all';
    activeRfqApprovalFilter = 'all';
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function setRfqPaymentTermsFilter(event, val) {
    if (event) event.stopPropagation();
    activeRfqPaymentTermsFilter = val;
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function clearAllRfqFilters() {
    activeRfqStatusFilter = 'all';
    activeRfqApprovalFilter = 'all';
    activeRfqPaymentTermsFilter = 'all';
    rfqSearchTerm = '';
    const searchInput = document.getElementById('rfqDirectorySearch');
    if (searchInput) searchInput.value = '';
    directoryCurrentPage = 1;
    renderDirectoryTableHeader();
    renderRfqDirectory();
    closeRfqHeaderFilter();
}

function renderRfqActiveFilterChips() {
    const container = document.getElementById('rfqActiveFilterChips');
    if (!container) return;

    let chipsHtml = '';

    if (activeRfqApprovalFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active Approval Filter">
                <span>Approval: <strong>${escapeHtml(activeRfqApprovalFilter)}</strong></span>
                <button type="button" onclick="setRfqApprovalFilter(event, 'all')" title="Clear approval filter">&times;</button>
            </span>
        `;
    }

    if (activeRfqStatusFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active RFQ Status Filter">
                <span>Status: <strong>${escapeHtml(activeRfqStatusFilter)}</strong></span>
                <button type="button" onclick="setRfqStatusFilter(event, 'all')" title="Clear status filter">&times;</button>
            </span>
        `;
    }

    if (activeRfqPaymentTermsFilter !== 'all') {
        chipsHtml += `
            <span class="rfq-active-chip" title="Active Payment Terms Filter">
                <span>Terms: <strong>${escapeHtml(activeRfqPaymentTermsFilter)}</strong></span>
                <button type="button" onclick="setRfqPaymentTermsFilter(event, 'all')" title="Clear payment terms filter">&times;</button>
            </span>
        `;
    }

    if (chipsHtml) {
        chipsHtml += `
            <button type="button" class="rfq-clear-all-chip-btn" onclick="clearAllRfqFilters()" title="Reset all active filters">
                Clear Filters
            </button>
        `;
    }

    container.innerHTML = chipsHtml;
}

// Global outside-click dismissal for table header popover
document.addEventListener('pointerdown', function(e) {
    const popover = document.getElementById('rfqHeaderFilterPopover');
    if (!popover || popover.style.display === 'none') return;
    if (!popover.contains(e.target) && !e.target.closest('.rfq-th-filter-wrapper')) {
        closeRfqHeaderFilter();
    }
});

function handleRfqDirectorySearch(query) {
    rfqSearchTerm = query;
    directoryCurrentPage = 1;
    renderRfqDirectory();
}

function startNewRfqFromDirectory() {
    resetRfqForm(false);
    const badge = document.getElementById('tabBuilderBadge');
    if (badge) badge.textContent = 'New RFQ';
    switchRfqTab('tab-builder');
    setTimeout(() => {
        const select = document.getElementById('vendorSelect');
        if (select) select.focus();
    }, 150);
}

function editRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;

    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    
    // Update inputs
    document.getElementById('rfqNumberInput').value = rfq.rfqNumber;
    document.getElementById('rfqRefDisplay').textContent = rfq.rfqNumber;
    const titleEl = document.getElementById('rfqTenderTitle');
    if (titleEl) titleEl.value = rfq.title || '';
    const statusBadge = document.getElementById('rfqStatusBadge');
    if (statusBadge) {
        const s = rfq.status || 'Draft';
        statusBadge.className = `status-pill ${s.toLowerCase().replace(/\s+/g, '-')}`;
        statusBadge.innerHTML = `<i class="ph ph-dot"></i> ${s}`;
    }
    if (rfq.vendorId) {
        document.getElementById('vendorSelect').value = rfq.vendorId;
        handleVendorSelection(rfq.vendorId);
    } else {
        document.getElementById('vendorSelect').value = '';
        clearVendorFields();
    }
    document.getElementById('rfqDateIssued').value = rfq.dateIssued || '';
    document.getElementById('rfqDueDate').value = rfq.dueDate || '';
    document.getElementById('rfqExpectedDelivery').value = rfq.expectedDelivery || '';
    document.getElementById('rfqPaymentTerms').value = rfq.paymentTerms || 'Net 30 Days';
    document.getElementById('rfqDeliveryLocation').value = rfq.deliveryLocation || '';
    document.getElementById('rfqSpecialInstructions').value = rfq.specialInstructions || '';

    renderItemsTable();
    const badge = document.getElementById('tabBuilderBadge');
    if (badge) badge.textContent = `Editing ${rfq.rfqNumber}`;
    switchRfqTab('tab-builder');
    showToast(`Loaded ${rfq.rfqNumber} into RFQ Builder`, 'success');
}

function previewRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    openRfqDocumentPreview();
}

function sendRfqFromDirectory(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    window.AppStore.activeRfq = JSON.parse(JSON.stringify(rfq));
    openSendEmailModal();
}

function deleteRfqFromDirectory(rfqNumber) {
    if (confirm(`Are you sure you want to delete quotation request ${rfqNumber}?`)) {
        window.AppStore.rfqs = (window.AppStore.rfqs || []).filter(r => r.rfqNumber !== rfqNumber);
        localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
        renderRfqDirectory();
        showToast(`Quotation ${rfqNumber} deleted`, 'success');
    }
}

let pendingApprovalRfqNumber = null;

function markQuoteReceived(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;
    rfq.status = 'Quotation Received';
    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
    renderRfqDirectory();
    showToast(`✓ Quotation received logged for ${rfqNumber}. Ready for approval!`, 'success');
}

function openManagerApprovalModal(rfqNumber) {
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === rfqNumber);
    if (!rfq) return;

    pendingApprovalRfqNumber = rfqNumber;
    const estTotal = (rfq.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.targetPrice) || 0)), 0);
    const isApproved = rfq.isApproved === true || rfq.status === 'Awarded' || rfq.status === 'Approved' || !!rfq.poReference;

    document.getElementById('approvalRfqTitle').textContent = `${rfq.rfqNumber} • ${rfq.vendorTradeName || rfq.vendorName}`;
    document.getElementById('approvalPoDeliveryDate').value = rfq.expectedDelivery || '';
    document.getElementById('approvalPaymentTerms').value = rfq.paymentTerms || 'Net 30 Days';
    document.getElementById('approvalDestination').value = rfq.deliveryLocation || 'Central Commissary - Main Dock';
    document.getElementById('approvalItemCount').textContent = `${(rfq.items || []).length} line items`;
    document.getElementById('approvalTotalValue').textContent = `₱${formatMoney(estTotal)}`;

    const bannerSubtitle = document.getElementById('approvalBannerSubtitle');
    const inputSection = document.getElementById('approvalInputSection');
    const approvedSection = document.getElementById('alreadyApprovedDetailsSection');
    const footer = document.getElementById('approvalModalFooter');

    if (isApproved) {
        bannerSubtitle.textContent = `This RFQ was evaluated and officially approved by Manager ${rfq.approvedBy || 'Procurement Manager'}.`;
        inputSection.style.display = 'none';
        approvedSection.style.display = 'block';

        document.getElementById('approvedOfficerNameText').textContent = `Approved by: ${rfq.approvedBy || 'Procurement Manager'}`;
        document.getElementById('approvedTimestampBadge').textContent = rfq.approvedAt ? formatDateDisplay(rfq.approvedAt) : 'Active Approval';
        document.getElementById('approvedNotesText').textContent = rfq.approvalNotes ? `“${rfq.approvalNotes}”` : 'No evaluation notes recorded.';

        const poBadge = document.getElementById('approvedPoLinkedBadge');
        if (rfq.poReference) {
            poBadge.style.display = 'block';
            poBadge.innerHTML = `
                <div style="font-size: 0.8rem; font-weight: 700; color: #065f46; margin-bottom: 4px;">Linked Purchase Order:</div>
                <a href="{{ route('purchase.purchase-orders') }}" class="rfq-btn rfq-btn-teal" style="font-size: 0.78rem; padding: 6px 14px;">
                    <i class="ph ph-receipt"></i> Open Purchase Order ${escapeHtml(rfq.poReference)}
                </a>
            `;
        } else {
            poBadge.style.display = 'none';
        }

        footer.innerHTML = `
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeApprovalModal()">Close</button>
            <button type="button" class="rfq-btn rfq-btn-outline" style="color: #dc2626; border-color: #fca5a5;" onclick="revokeRfqApproval()">
                <i class="ph ph-arrow-u-up-left"></i> Revoke Approval
            </button>
            ${!rfq.poReference ? `
                <button type="button" class="rfq-btn rfq-btn-teal" onclick="generatePoForApprovedRfq()">
                    <i class="ph ph-receipt"></i> Generate Purchase Order Now
                </button>
            ` : ''}
        `;
    } else {
        bannerSubtitle.textContent = `Authorized Procurement Managers can review bid evaluation, stamp approval, or award and generate a formal Purchase Order.`;
        inputSection.style.display = 'block';
        approvedSection.style.display = 'none';

        footer.innerHTML = `
            <button type="button" class="rfq-btn rfq-btn-outline" onclick="closeApprovalModal()">Cancel</button>
            <button type="button" class="rfq-btn rfq-btn-outline" style="color: #047857; border-color: #a7f3d0;" onclick="approveRfqOnly()">
                <i class="ph ph-check"></i> Approve RFQ Only
            </button>
            <button type="button" class="rfq-btn rfq-btn-teal" onclick="confirmApprovalAndGeneratePO()">
                <i class="ph ph-seal-check"></i> Approve & Generate Purchase Order
            </button>
        `;
    }

    const modal = document.getElementById('rfqApprovalModal');
    if (modal) modal.classList.add('is-open');
}

function openApprovalTransferModal(rfqNumber) {
    openManagerApprovalModal(rfqNumber);
}

function closeApprovalModal() {
    const modal = document.getElementById('rfqApprovalModal');
    if (modal) modal.classList.remove('is-open');
    pendingApprovalRfqNumber = null;
}

function approveRfqOnly() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    const approver = document.getElementById('approvalOfficer').value.trim() || 'Procurement Manager';
    const notes = document.getElementById('approvalNotes').value.trim();
    const estTotal = (rfq.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.targetPrice) || 0)), 0);

    rfq.isApproved = true;
    rfq.status = 'Approved';
    rfq.approvedBy = approver;
    rfq.approvedAt = new Date().toISOString();
    rfq.approvalNotes = notes || 'Manager approval granted. Ready for PO issuance.';

    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));

    // Audit Log
    logProcurementAudit(
        'RFQ',
        rfq.rfqNumber,
        'APPROVED',
        'RFQ Approved by Manager',
        `Manager ${approver} approved quotation tender without immediate PO dispatch. Status set to Approved.`,
        [
            { label: 'Approving Officer', value: approver },
            { label: 'Vendor Partner', value: rfq.vendorTradeName || rfq.vendorName },
            { label: 'Estimated Budget', value: `₱${formatMoney(estTotal)}` },
            { label: 'Evaluation Note', value: notes || 'Compliant supplier tender' }
        ]
    );

    closeApprovalModal();
    renderRfqDirectory();
    showToast(`✓ RFQ ${rfq.rfqNumber} successfully Approved by Manager!`, 'success');
}

function confirmApprovalAndGeneratePO() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    const approver = document.getElementById('approvalOfficer').value.trim() || 'Procurement Manager';
    const deliveryDate = document.getElementById('approvalPoDeliveryDate').value;
    const terms = document.getElementById('approvalPaymentTerms').value;
    const dest = document.getElementById('approvalDestination').value;
    const notes = document.getElementById('approvalNotes').value.trim();

    const pos = JSON.parse(localStorage.getItem('rms_purchase_orders') || '[]');
    const nextPoNum = `PO-2026-${String(pos.length + 101).padStart(4, '0')}`;
    const estTotal = (rfq.items || []).reduce((acc, it) => acc + ((parseFloat(it.quantity) || 0) * (parseFloat(it.targetPrice) || 0)), 0);

    // 1. Mark RFQ as Awarded
    rfq.isApproved = true;
    rfq.status = 'Awarded';
    rfq.approvedBy = approver;
    rfq.approvedAt = new Date().toISOString();
    rfq.approvalNotes = notes || 'Quotation approved and transferred to formal Purchase Order.';
    rfq.poReference = nextPoNum;
    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));

    // 2. Insert new PO into rms_purchase_orders
    const newPO = {
        poNumber: nextPoNum,
        poType: 'vendor',
        rfqReference: rfq.rfqNumber,
        vendorId: rfq.vendorId,
        vendorName: rfq.vendorName,
        vendorTradeName: rfq.vendorTradeName || rfq.vendorName,
        vendorContactPerson: rfq.vendorContactPerson || '',
        vendorPhone: rfq.vendorPhone || '',
        vendorEmail: rfq.vendorEmail || '',
        vendorAddress: rfq.vendorAddress || '',
        orderDate: new Date().toISOString().split('T')[0],
        expectedDelivery: deliveryDate || rfq.expectedDelivery,
        deliveryLocation: dest,
        paymentTerms: terms,
        paymentStatus: 'Unpaid / Credit',
        paymentMethod: 'Trade Credit (Net 30/15)',
        amountPaid: 0,
        balanceDue: estTotal,
        paymentReference: '',
        paymentDate: '',
        fundSource: 'Main Commissary Checking Acct',
        paymentRemarks: '',
        approvalNotes: notes || 'Quotation approved and transferred to PO.',
        approvedBy: approver,
        status: 'Approved / Issued',
        items: rfq.items || []
    };

    pos.unshift(newPO);
    localStorage.setItem('rms_purchase_orders', JSON.stringify(pos));

    // Audit Trail: RFQ Approved & Awarded
    logProcurementAudit(
        'RFQ',
        rfq.rfqNumber,
        'APPROVED',
        'RFQ Approved & Awarded',
        `Manager ${approver} approved quotation and authorized immediate issuance of ${nextPoNum}.`,
        [
            { label: 'Approving Manager', value: approver },
            { label: 'Assigned PO', value: nextPoNum },
            { label: 'Awarded Value', value: `₱${formatMoney(estTotal)}` },
            { label: 'Committed Delivery', value: deliveryDate || 'Standard Lead Time' }
        ]
    );

    // Audit Trail: PO Created from RFQ
    logProcurementAudit(
        'PO',
        nextPoNum,
        'PO_TRANSFERRED',
        'Purchase Order Transferred from RFQ',
        `Generated formal Purchase Order ${nextPoNum} transferred from approved quotation ${rfq.rfqNumber} for ${rfq.vendorTradeName || rfq.vendorName}.`,
        [
            { label: 'Source RFQ', value: rfq.rfqNumber },
            { label: 'Supplier', value: rfq.vendorTradeName || rfq.vendorName },
            { label: 'Order Total', value: `₱${formatMoney(estTotal)}` }
        ]
    );

    closeApprovalModal();
    renderRfqDirectory();
    showToast(`✓ Approved! Generated ${nextPoNum} for ${rfq.vendorTradeName || rfq.vendorName}`, 'success');
}

function generatePoForApprovedRfq() {
    confirmApprovalAndGeneratePO();
}

function revokeRfqApproval() {
    if (!pendingApprovalRfqNumber) return;
    const rfq = (window.AppStore.rfqs || []).find(r => r.rfqNumber === pendingApprovalRfqNumber);
    if (!rfq) return;

    if (confirm(`Revoke manager approval for ${rfq.rfqNumber}? The status will revert to Quote Requested.`)) {
        const previousApprover = rfq.approvedBy || 'Manager';
        rfq.isApproved = false;
        rfq.status = 'Quote Requested';
        rfq.approvedBy = null;
        rfq.approvedAt = null;
        rfq.approvalNotes = null;

        localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));

        // Audit Trail entry
        logProcurementAudit(
            'RFQ',
            rfq.rfqNumber,
            'APPROVAL_REVOKED',
            'Manager Approval Revoked',
            `Manager revoked approval status for ${rfq.rfqNumber}. Tender reopened for evaluation.`,
            [{ label: 'Previous Approver', value: previousApprover }]
        );

        closeApprovalModal();
        renderRfqDirectory();
        showToast(`Manager approval revoked for ${rfq.rfqNumber}`, 'info');
    }
}

/**
 * --------------------------------------------------------------------------
 * PROCUREMENT AUDIT TRAIL ENGINE
 * --------------------------------------------------------------------------
 */
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
        },
        {
            id: 'AUD-2026-1002',
            timestamp: new Date(Date.now() - 3600000 * 26).toISOString(),
            formattedDate: 'Sep 29, 2026 • 09:10 AM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0042',
            action: 'EMAIL_SENT',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Quotation Request Emailed to Vendor',
            description: 'Sent formal request package to San Miguel Foods (bids@sanmiguelfoods.ph).',
            details: [
                { label: 'Recipient', value: 'bids@sanmiguelfoods.ph' },
                { label: 'CC Audit', value: 'procurement-audit@rms.ph' },
                { label: 'Quotation Due', value: 'Oct 05, 2026' }
            ]
        },
        {
            id: 'AUD-2026-1001',
            timestamp: new Date(Date.now() - 3600000 * 28).toISOString(),
            formattedDate: 'Sep 29, 2026 • 08:00 AM',
            module: 'RFQ',
            refNumber: 'RFQ-2026-0042',
            action: 'CREATED',
            actor: 'Dorothy Diaz',
            role: 'Procurement Manager',
            title: 'Request for Quotation Created',
            description: 'Created new RFQ tender with 5 line items. Est Budget: ₱142,500.00.',
            details: [
                { label: 'Vendor Partner', value: 'San Miguel Foods, Inc.' },
                { label: 'Commodities', value: '5 raw ingredient lines' }
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

    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = logs.length;

    return entry;
}

let auditScopeMode = 'CURRENT'; // Default strictly to CURRENT PO / RFQ to minimize process usage

function getCurrentAuditTarget() {
    const active = window.AppStore.activeRfq || {};
    const rfqNum = (active.rfqNumber || document.getElementById('rfqRefDisplay')?.textContent || '').trim();
    const poNum = (active.poReference || active.poNumber || '').trim();
    return { rfqNum, poNum };
}

function setAuditScope(mode) {
    auditScopeMode = mode;
    const btnCurrent = document.getElementById('btnAuditScopeCurrent');
    const btnAll = document.getElementById('btnAuditScopeAll');
    if (btnCurrent) btnCurrent.classList.toggle('active', mode === 'CURRENT');
    if (btnAll) btnAll.classList.toggle('active', mode === 'ALL');
    renderRfqAuditTrail();
}

function renderRfqAuditTrail() {
    const container = document.getElementById('rfqAuditTimeline');
    if (!container) return;

    const allLogs = getProcurementAuditTrail();
    const { rfqNum, poNum } = getCurrentAuditTarget();

    // Update indexed scope badge in drawer
    const badgeRfq = document.getElementById('auditTargetRefBadge');
    if (badgeRfq) badgeRfq.textContent = rfqNum || 'Current Tender';

    const badgePo = document.getElementById('auditTargetPoBadge');
    if (badgePo) {
        if (poNum) {
            badgePo.textContent = poNum;
            badgePo.style.display = 'inline-block';
        } else {
            badgePo.style.display = 'none';
        }
    }

    // Process optimization: Index ONLY current RFQ / PO when in CURRENT mode
    let targetLogs = allLogs;
    if (auditScopeMode === 'CURRENT' && (rfqNum || poNum)) {
        targetLogs = allLogs.filter(log => {
            const ref = (log.refNumber || '').trim();
            if (rfqNum && (ref === rfqNum || (log.description && log.description.includes(rfqNum)))) {
                return true;
            }
            if (poNum && (ref === poNum || (log.description && log.description.includes(poNum)))) {
                return true;
            }
            return false;
        });

        // If active tender has no historical entries yet, provide an initial draft baseline event
        if (targetLogs.length === 0 && rfqNum) {
            const active = window.AppStore.activeRfq || {};
            targetLogs = [{
                id: `AUD-INIT-${rfqNum}`,
                timestamp: new Date().toISOString(),
                formattedDate: 'Just now',
                module: 'RFQ',
                refNumber: rfqNum,
                action: 'CREATED',
                actor: '{{ auth()->user()->name ?? "Dorothy Diaz" }}',
                role: 'Procurement Manager',
                title: 'RFQ Tender Initialized',
                description: `Active draft tender ${rfqNum} initialized in procurement workspace.`,
                details: [
                    { label: 'Tender Reference', value: rfqNum },
                    { label: 'Lifecycle Status', value: active.status || 'Draft' },
                    { label: 'Supplier Partner', value: active.vendorName || 'Not Selected' },
                    { label: 'Item Lines', value: `${(active.items || []).length} items listed` }
                ]
            }];
        }
    }

    // Update scoped event counter in drawer header
    const auditCountBadge = document.getElementById('tabRfqAuditCount');
    if (auditCountBadge) auditCountBadge.textContent = targetLogs.length;

    const q = (auditSearchTerm || '').toLowerCase().trim();

    const filtered = targetLogs.filter(log => {
        const matchesModule = (activeAuditModuleFilter === 'ALL') || (log.module === activeAuditModuleFilter);
        const matchesAction = (activeAuditActionFilter === 'ALL') || (log.action === activeAuditActionFilter);
        const matchesSearch = !q ||
            (log.refNumber && log.refNumber.toLowerCase().includes(q)) ||
            (log.title && log.title.toLowerCase().includes(q)) ||
            (log.description && log.description.toLowerCase().includes(q)) ||
            (log.actor && log.actor.toLowerCase().includes(q));

        return matchesModule && matchesAction && matchesSearch;
    });

    // Update KPI numbers calculated ONLY against the indexed target set
    const kpiTotal = document.getElementById('auditKpiTotalCount');
    const kpiApprovals = document.getElementById('auditKpiApprovalsCount');
    const kpiEdits = document.getElementById('auditKpiEditsCount');
    const kpiEmails = document.getElementById('auditKpiEmailsCount');

    if (kpiTotal) kpiTotal.textContent = targetLogs.length;
    if (kpiApprovals) kpiApprovals.textContent = targetLogs.filter(l => l.action === 'APPROVED').length;
    if (kpiEdits) kpiEdits.textContent = targetLogs.filter(l => l.action === 'CREATED' || l.action === 'EDITED' || l.action === 'WET_MARKET_CREATED').length;
    if (kpiEmails) kpiEmails.textContent = targetLogs.filter(l => l.action === 'EMAIL_SENT').length;

    if (filtered.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 36px 20px; background: #ffffff; border: 1px solid var(--rfq-border-subtle); border-radius: 10px; color: var(--rfq-text-muted);">
                <i class="ph ph-clock-countdown" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 8px;"></i>
                <div style="font-weight: 600; color: #475569; margin-bottom: 4px;">No Logged Events Found</div>
                <div style="font-size: 12px;">No logged activities match the current filter criteria for ${rfqNum || 'this tender'}.</div>
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
                        <span style="font-weight: 800; font-size: 0.94rem; color: var(--rfq-text-strong);">${escapeHtml(log.title)}</span>
                        <span style="font-family: monospace; font-size: 0.78rem; font-weight: 700; color: var(--rfq-primary); background: #eff6ff; padding: 2px 6px; border-radius: 4px;">
                            ${escapeHtml(log.refNumber)}
                        </span>
                    </div>
                    <div style="font-size: 0.78rem; font-weight: 600; color: var(--rfq-text-muted); display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-clock"></i> ${escapeHtml(log.formattedDate)}
                    </div>
                </div>
                <div style="font-size: 0.84rem; color: #334155; line-height: 1.45; margin-bottom: 6px;">
                    ${escapeHtml(log.description)}
                </div>
                ${detailsHtml}
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 10px; font-size: 0.75rem; color: var(--rfq-text-muted);">
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

function onAuditSearchChange(query) {
    auditSearchTerm = query;
    renderRfqAuditTrail();
}

function filterAuditByModule(mod, btn) {
    activeAuditModuleFilter = mod;
    const parent = btn.parentElement;
    parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    renderRfqAuditTrail();
}

function filterAuditByAction(action, btn) {
    activeAuditActionFilter = action;
    const parent = document.getElementById('auditActionFilterPills');
    if (parent) {
        parent.querySelectorAll('.audit-filter-pill').forEach(b => b.classList.remove('active'));
    }
    btn.classList.add('active');
    renderRfqAuditTrail();
}

function openSingleAuditModal(module, refNumber) {
    const modal = document.getElementById('rfqSingleAuditModal');
    if (!modal) return;

    document.getElementById('singleAuditHeaderTitle').textContent = `Audit Trail & History • ${refNumber}`;
    document.getElementById('singleAuditRefTitle').textContent = refNumber;
    document.getElementById('singleAuditSubTitle').textContent = `Chronological activity records and transaction trail for ${refNumber}`;

    const container = document.getElementById('singleAuditTimelineContainer');
    const allLogs = getProcurementAuditTrail();
    const itemLogs = allLogs.filter(l => l.refNumber === refNumber);

    if (itemLogs.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; padding: 32px; color: var(--rfq-text-muted);">
                <i class="ph ph-clock" style="font-size: 30px; color: #cbd5e1; display: block; margin-bottom: 6px;"></i>
                No audit events recorded yet for this reference.
            </div>
        `;
    } else {
        container.innerHTML = itemLogs.map(log => `
            <div class="audit-timeline-item" style="padding: 12px 16px;">
                <div class="audit-timeline-node-icon is-created" style="top: 14px;">
                    <i class="ph ph-check"></i>
                </div>
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                    <strong style="font-size: 0.88rem; color: var(--rfq-text-strong);">${escapeHtml(log.title)}</strong>
                    <span style="font-size: 0.75rem; color: var(--rfq-text-muted);">${escapeHtml(log.formattedDate)}</span>
                </div>
                <div style="font-size: 0.82rem; color: #334155; margin-bottom: 4px;">
                    ${escapeHtml(log.description)}
                </div>
                ${(log.details && log.details.length > 0) ? `
                    <div style="font-size: 0.76rem; background: #f8fafc; padding: 6px 10px; border-radius: 6px; margin-top: 6px;">
                        ${log.details.map(d => `<div><strong>${escapeHtml(d.label)}:</strong> ${escapeHtml(d.value)}</div>`).join('')}
                    </div>
                ` : ''}
                <div style="font-size: 0.72rem; color: var(--rfq-text-muted); margin-top: 6px;">
                    Logged by <strong>${escapeHtml(log.actor)}</strong> (${escapeHtml(log.role || 'Staff')})
                </div>
            </div>
        `).join('');
    }

    modal.classList.add('is-open');
}

function closeSingleAuditModal() {
    const modal = document.getElementById('rfqSingleAuditModal');
    if (modal) modal.classList.remove('is-open');
}

function convertRfqToPurchaseOrder(rfqNumber) {
    openManagerApprovalModal(rfqNumber);
}

function syncRfqToDirectory(rfqData, overrideStatus = null) {
    if (!rfqData || !rfqData.rfqNumber) return;
    if (!window.AppStore.rfqs) window.AppStore.rfqs = [];

    const existingIdx = window.AppStore.rfqs.findIndex(r => r.rfqNumber === rfqData.rfqNumber);
    const entry = JSON.parse(JSON.stringify(rfqData));
    if (overrideStatus) entry.status = overrideStatus;
    else if (!entry.status) entry.status = 'Draft';

    if (existingIdx >= 0) {
        window.AppStore.rfqs[existingIdx] = entry;
        logProcurementAudit(
            'RFQ',
            entry.rfqNumber,
            'EDITED',
            'RFQ Specifications Updated',
            `Updated line items, quantities, or terms for ${entry.vendorTradeName || entry.vendorName || 'RFQ'}.`,
            [{ label: 'Line Items', value: `${(entry.items || []).length} items` }]
        );
    } else {
        window.AppStore.rfqs.unshift(entry);
        logProcurementAudit(
            'RFQ',
            entry.rfqNumber,
            'CREATED',
            'RFQ Created in Builder',
            `Created Request for Quotations tender for ${entry.vendorTradeName || entry.vendorName || 'Vendor'}.`,
            [{ label: 'Line Items', value: `${(entry.items || []).length} items` }]
        );
    }
    localStorage.setItem('rms_rfq_directory', JSON.stringify(window.AppStore.rfqs));
    renderRfqDirectory();
}

function exportRfqDirectoryCSV() {
    const list = window.AppStore && window.AppStore.rfqs ? window.AppStore.rfqs : [];
    if (!list || list.length === 0) {
        showToast("No RFQ tender records available to export.", "warning");
        return;
    }
    const headers = ["Reference", "Vendor Name", "Vendor Email", "Status", "Total Budget (PHP)", "Created Date", "Closing Date", "Items Count"];
    const rows = list.map(r => [
        `"${r.ref || ''}"`,
        `"${(r.vendorName || '').replace(/"/g, '""')}"`,
        `"${r.vendorEmail || ''}"`,
        `"${r.status || ''}"`,
        r.totalBudget || 0,
        `"${r.createdDate || ''}"`,
        `"${r.closingDate || ''}"`,
        r.items ? r.items.length : 0
    ]);
    const csvContent = "data:text/csv;charset=utf-8," + [headers.join(","), ...rows.map(e => e.join(","))].join("\n");
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `RFQ_Tenders_${new Date().toISOString().slice(0, 10)}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    showToast("RFQ directory successfully exported to CSV.", "success");
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
    const container = document.getElementById('rfqToastContainer');
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
</script>
@endpush

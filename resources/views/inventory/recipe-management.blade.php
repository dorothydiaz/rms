@extends('layouts.app')

@section('title', 'Bill of Materials (BOM) - Restaurant Management System')

@push('styles')
<style>
/* CSS Variables & Glassmorphic Tokens */
:root {
    --bom-primary: #a855f7;
    --bom-primary-dark: #9333ea;
    --bom-primary-light: #c084fc;
    --bom-primary-glow: rgba(168, 85, 247, 0.22);
    --bom-primary-gradient: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
    --bom-success: #10b981;
    --bom-success-subtle: rgba(16, 185, 129, 0.12);
    --bom-warning: #f59e0b;
    --bom-warning-subtle: rgba(245, 158, 11, 0.12);
    --bom-danger: #ef4444;
    --bom-danger-subtle: rgba(239, 68, 68, 0.12);
    --bom-text-strong: #0f172a;
    --bom-text-medium: #334155;
    --bom-text-muted: #64748b;
    --bom-text-subtle: #94a3b8;
    --bom-border-subtle: #e2e8f0;
    --bom-glass-bg: rgba(255, 255, 255, 0.88);
    --bom-glass-border: rgba(255, 255, 255, 0.6);
    --bom-drawer-width: 580px;
}

.bom-page-container {
    display: flex;
    flex-direction: column;
    gap: 12px;
    padding-bottom: 40px;
}

/* Header — HR-style flat title row (no box ribbon) */
.bom-parent-header {
    margin-bottom: 0;
}

.bom-parent-title-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-bottom: 0;
    flex-wrap: wrap;
}

.bom-parent-title {
    font-family: var(--font-heading, 'League Spartan', sans-serif);
    font-size: 23px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
    letter-spacing: -0.02em;
    margin: 0;
}

.bom-parent-title i {
    width: 40px;
    height: 40px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.14), rgba(124, 58, 237, 0.20));
    border: 1px solid rgba(168, 85, 247, 0.32);
    color: #9333ea;
    font-size: 21px;
    box-shadow: 0 4px 14px rgba(168, 85, 247, 0.12), inset 0 1px 1px rgba(255, 255, 255, 0.8);
    flex-shrink: 0;
}

.bom-parent-subtitle {
    font-size: 13px;
    color: #64748b;
    font-weight: 400;
    margin: 3px 0 0 0;
}

.bom-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.bom-action-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--bom-primary-gradient);
    color: #ffffff;
    padding: 10px 18px;
    border-radius: 10px;
    font-size: 0.84rem;
    font-weight: 600;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px var(--bom-primary-glow);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    text-decoration: none;
}

.bom-action-btn-primary:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 6px 18px rgba(168, 85, 247, 0.35);
}

.bom-action-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #ffffff;
    color: var(--bom-text-medium);
    padding: 9px 15px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    border: 1px solid var(--bom-border-subtle);
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    text-decoration: none;
}

.bom-action-btn-secondary:hover {
    background: #f8fafc;
    color: var(--bom-text-strong);
    border-color: #cbd5e1;
}

/* Metric Stats Row */
.bom-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 14px;
}

.bom-metric-card {
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
}

.bom-metric-info {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.bom-metric-label {
    font-size: 0.74rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bom-text-muted);
}

.bom-metric-value {
    font-size: 1.35rem;
    font-weight: 800;
    color: var(--bom-text-strong);
    letter-spacing: -0.02em;
    font-variant-numeric: tabular-nums;
}

.bom-metric-sub {
    font-size: 0.72rem;
    font-weight: 500;
    color: var(--bom-success);
    display: flex;
    align-items: center;
    gap: 4px;
}

.bom-metric-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    background: #f8fafc;
    color: var(--bom-primary-dark);
}

/* Filter Bar */
.bom-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: nowrap;
    overflow-x: auto;
    scrollbar-width: none;
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 12px;
    padding: 10px 16px;
}

.bom-search-wrapper {
    position: relative;
    width: 340px;
    max-width: 100%;
}

.bom-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--bom-text-muted);
    font-size: 16px;
}

.bom-search-input {
    width: 100%;
    border-radius: 8px;
    border: 1.5px solid var(--bom-border-subtle);
    background: #f8fafc;
    padding: 8px 12px 8px 36px;
    font-size: 0.82rem;
    color: var(--bom-text-strong);
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
}

.bom-search-input:focus {
    border-color: var(--bom-primary);
    background: #ffffff;
    box-shadow: 0 0 0 3px var(--bom-primary-glow);
}

.bom-filter-category-select {
    padding: 8px 12px;
    border-radius: 8px;
    border: 1.5px solid var(--bom-border-subtle);
    background: #ffffff;
    font-size: 0.82rem;
    color: var(--bom-text-medium);
    font-family: inherit;
    outline: none;
    cursor: pointer;
}

/* Master BOM Card Grid / Table */
.bom-master-card {
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
}

.bom-table-container {
    width: 100%;
    overflow-x: auto;
}

.bom-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
    text-align: left;
}

.bom-table th {
    background: #f8fafc;
    color: var(--bom-text-muted);
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 16px;
    border-bottom: 1.5px solid var(--bom-border-subtle);
    white-space: nowrap;
}

.bom-table th.th-right,
.bom-table td.td-right {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.bom-row {
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.15s ease;
}

.bom-row:hover {
    background: rgba(248, 250, 252, 0.9);
}

.bom-row td {
    padding: 14px 16px;
    vertical-align: middle;
    color: var(--bom-text-medium);
}

.bom-product-title-group {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bom-product-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    background: rgba(168, 85, 247, 0.1);
    color: var(--bom-primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    flex-shrink: 0;
}

.bom-product-name {
    font-weight: 700;
    color: var(--bom-text-strong);
    font-size: 0.86rem;
}

.bom-product-sku {
    font-family: monospace;
    font-size: 0.72rem;
    color: var(--bom-text-muted);
}

/* Profitability Status Pills */
.bom-profit-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 20px;
    font-size: 0.72rem;
    font-weight: 700;
}

.bom-profit-pill.profitable {
    background: var(--bom-success-subtle);
    color: var(--bom-success);
    border: 1px solid rgba(16, 185, 129, 0.25);
}

.bom-profit-pill.low-margin {
    background: var(--bom-warning-subtle);
    color: var(--bom-warning);
    border: 1px solid rgba(245, 158, 11, 0.25);
}

.bom-profit-pill.unprofitable {
    background: var(--bom-danger-subtle);
    color: var(--bom-danger);
    border: 1px solid rgba(239, 68, 68, 0.25);
}

.bom-profit-pill.is-pending {
    background: #fef3c7;
    color: #b45309;
    border: 1px solid rgba(217, 119, 6, 0.3);
}

.bom-expand-btn {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    color: var(--bom-text-medium);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.2s ease;
}

.bom-expand-btn:hover {
    background: #f1f5f9;
    color: var(--bom-primary-dark);
}

.bom-expand-btn.is-expanded {
    transform: rotate(180deg);
}

/* Nested Raw Materials Breakdown */
.bom-breakdown-row {
    background: #fcfcfd;
}

.bom-breakdown-row.is-hidden {
    display: none;
}

.bom-breakdown-box {
    padding: 16px 20px 20px 58px;
    border-top: 1px dashed var(--bom-border-subtle);
    border-bottom: 1.5px solid var(--bom-border-subtle);
}

.bom-breakdown-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.bom-breakdown-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--bom-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}

.bom-subtable {
    width: 100%;
    border-collapse: collapse;
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 8px;
    overflow: hidden;
    font-size: 0.76rem;
}

.bom-subtable th {
    background: #f8fafc;
    color: var(--bom-text-muted);
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 8px 12px;
    border-bottom: 1px solid var(--bom-border-subtle);
}

.bom-subtable td {
    padding: 8px 12px;
    color: var(--bom-text-medium);
    border-bottom: 1px solid #f1f5f9;
}

.bom-subtable tr:last-child td {
    border-bottom: none;
}

.bom-subtable tfoot td {
    background: #f8fafc;
    font-weight: 700;
    color: var(--bom-text-strong);
    border-top: 1.5px solid var(--bom-border-subtle);
}

/* Cost Contribution Progress Bar */
.bom-cost-bar-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
}

.bom-cost-bar {
    height: 6px;
    background: #e2e8f0;
    border-radius: 3px;
    flex: 1;
    overflow: hidden;
}

.bom-cost-fill {
    height: 100%;
    background: var(--bom-primary-gradient);
    border-radius: 3px;
}

/* Slide-out Drawer */
.bom-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
}

.bom-drawer-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
}

.bom-drawer-container {
    position: fixed;
    top: 0;
    right: 0;
    width: min(var(--bom-drawer-width), 95vw);
    height: 100vh;
    background: #ffffff;
    box-shadow: -10px 0 40px rgba(15, 23, 42, 0.18);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 1px solid var(--bom-border-subtle);
}

.bom-drawer-container.is-open {
    transform: translateX(0);
}

.bom-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--bom-border-subtle);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.bom-drawer-title-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.bom-drawer-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--bom-text-strong);
    margin: 0;
}

.bom-drawer-subtitle {
    font-size: 0.74rem;
    color: var(--bom-text-muted);
    margin: 2px 0 0 0;
}

.bom-drawer-close-btn {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: 1px solid var(--bom-border-subtle);
    background: #ffffff;
    color: var(--bom-text-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 16px;
    transition: all 0.15s ease;
}

.bom-drawer-close-btn:hover {
    background: #fee2e2;
    color: var(--bom-danger);
    border-color: #fca5a5;
}

.bom-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    background: #fcfcfd;
}

.bom-drawer-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid var(--bom-border-subtle);
}

/* Drawer Form Elements */
.bom-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.bom-form-group label {
    font-size: 0.75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    color: var(--bom-text-medium);
}

.bom-form-input,
.bom-form-select,
.bom-form-textarea {
    width: 100%;
    border-radius: 9px;
    border: 1.5px solid var(--bom-border-subtle);
    background: #ffffff;
    padding: 8px 12px;
    font-size: 0.84rem;
    color: var(--bom-text-strong);
    font-family: inherit;
    outline: none;
    transition: all 0.15s ease;
    box-sizing: border-box;
}

.bom-form-input:focus,
.bom-form-select:focus,
.bom-form-textarea:focus {
    border-color: var(--bom-primary);
    box-shadow: 0 0 0 3px var(--bom-primary-glow);
}

.bom-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

/* Inventory Movement & BOM Behavior Checkbox Cards */
.bom-checkbox-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 9px;
    background: #f8fafc;
    border: 1px solid var(--bom-border-subtle);
    cursor: pointer;
    transition: all 0.15s ease;
}

.bom-checkbox-card:hover {
    background: #f1f5f9;
}

.bom-checkbox-card input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: var(--bom-primary);
    cursor: pointer;
}

.bom-checkbox-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--bom-text-strong);
    margin: 0;
}

.bom-checkbox-desc {
    font-size: 0.72rem;
    color: var(--bom-text-muted);
    margin: 0;
}

/* Mode Switcher Tabs */
.bom-mode-switcher {
    display: flex;
    background: #f1f5f9;
    padding: 3px;
    border-radius: 10px;
    border: 1px solid var(--bom-border-subtle);
}

.bom-mode-btn {
    flex: 1;
    padding: 8px 12px;
    font-size: 0.78rem;
    font-weight: 600;
    border: none;
    border-radius: 8px;
    background: transparent;
    color: var(--bom-text-muted);
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    text-align: center;
}

.bom-mode-btn.active {
    background: #ffffff;
    color: var(--bom-primary-dark);
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
}

/* Ingredients Builder Repeater */
.bom-builder-card {
    background: #ffffff;
    border: 1.5px solid var(--bom-border-subtle);
    border-radius: 12px;
    overflow: hidden;
}

.bom-builder-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 14px;
    background: #f8fafc;
    border-bottom: 1px solid var(--bom-border-subtle);
}

.bom-builder-items-list {
    padding: 10px 14px;
    display: flex;
    flex-direction: column;
    gap: 8px;
    max-height: 240px;
    overflow-y: auto;
}

.bom-builder-cols-header {
    display: grid;
    grid-template-columns: 2.2fr 1.1fr 1fr 1fr 28px;
    gap: 8px;
    padding: 0 4px 6px;
    font-size: 0.67rem;
    font-weight: 700;
    color: var(--bom-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    border-bottom: 1px solid var(--bom-border-subtle);
}

.bom-builder-item-row {
    display: grid;
    grid-template-columns: 2.2fr 1.1fr 1fr 1fr 28px;
    gap: 8px;
    align-items: center;
    padding: 6px 8px;
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 8px;
}

.bom-item-delete-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    color: var(--bom-danger);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 13px;
    transition: all 0.15s ease;
}

.bom-item-delete-btn:hover {
    background: #fee2e2;
    border-color: #fca5a5;
}

/* Profitability Radar Widget */
.bom-radar-card {
    background: #ffffff;
    border: 1.5px solid var(--bom-border-subtle);
    border-radius: 12px;
    padding: 14px 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.bom-radar-summary-grid {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    gap: 8px;
    text-align: center;
}

.bom-radar-stat-box {
    background: #f8fafc;
    padding: 8px 6px;
    border-radius: 8px;
    border: 1px solid #f1f5f9;
}

.bom-radar-stat-label {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--bom-text-muted);
}

.bom-radar-stat-val {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--bom-text-strong);
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
}

.bom-radar-banner {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 700;
}

.bom-radar-banner.is-profit {
    background: var(--bom-success-subtle);
    color: var(--bom-success);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.bom-radar-banner.is-warning {
    background: var(--bom-warning-subtle);
    color: var(--bom-warning);
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.bom-radar-banner.is-deficit {
    background: var(--bom-danger-subtle);
    color: var(--bom-danger);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.bom-radar-banner.is-neutral {
    background: #f8fafc;
    color: var(--bom-text-muted);
    border: 1px solid #e2e8f0;
}

/* BOM Drawer Tab Navigation */
.bom-drawer-nav {
    display: flex;
    background: #f8fafc;
    border-bottom: 1px solid var(--bom-border-subtle);
    padding: 0 24px;
    gap: 8px;
}

.bom-drawer-tab-btn {
    display: flex;
    align-items: center;
    gap: 7px;
    padding: 12px 14px;
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    color: var(--bom-text-muted);
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.bom-drawer-tab-btn:hover {
    color: var(--bom-text-strong);
}

.bom-drawer-tab-btn.active {
    color: var(--bom-primary);
    border-bottom-color: var(--bom-primary);
    font-weight: 700;
}

/* =========================================================================
   AUDIT LOGS & TIMELINE SYSTEM (BOM)
   ========================================================================= */
.audit-overview-card {
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 12px;
    padding: 14px 16px;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

.audit-metric-box {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.audit-metric-label {
    font-size: 0.68rem;
    font-weight: 600;
    color: var(--bom-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    gap: 5px;
}

.audit-metric-val {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--bom-text-strong);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
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
    top: 6px;
    bottom: 6px;
    left: 11px;
    width: 2px;
    background: #e2e8f0;
}

.audit-timeline-item {
    position: relative;
    background: #ffffff;
    border: 1px solid var(--bom-border-subtle);
    border-radius: 10px;
    padding: 14px 16px;
    transition: all 0.15s ease;
}

.audit-timeline-item:hover {
    border-color: #cbd5e1;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.audit-timeline-node-icon {
    position: absolute;
    left: -28px;
    top: 14px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background: #ffffff;
    border: 2px solid var(--bom-primary);
    color: var(--bom-primary);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transform: translateX(-50%);
    z-index: 2;
}

.audit-timeline-node-icon.is-created {
    border-color: var(--bom-success);
    color: var(--bom-success);
}

.audit-timeline-node-icon.is-price {
    border-color: #3b82f6;
    color: #2563eb;
}

.audit-timeline-node-icon.is-bom {
    border-color: #8b5cf6;
    color: #7c3aed;
}

.audit-timeline-node-icon.is-status {
    border-color: #f59e0b;
    color: #d97706;
}

.audit-item-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 8px;
    flex-wrap: wrap;
}

.audit-user-profile {
    display: flex;
    align-items: center;
    gap: 9px;
}

.audit-user-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #ede9fe;
    color: #6d28d9;
    font-weight: 700;
    font-size: 0.72rem;
    display: flex;
    align-items: center;
    justify-content: center;
}

.audit-user-name {
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--bom-text-strong);
    line-height: 1.2;
}

.audit-user-role {
    font-size: 0.70rem;
    color: var(--bom-text-muted);
}

.audit-badge {
    display: inline-flex;
    align-items: center;
    padding: 2px 7px;
    border-radius: 12px;
    font-size: 0.66rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.audit-badge.badge-created {
    background: #dcfce7;
    color: #15803d;
}

.audit-badge.badge-updated {
    background: #f1f5f9;
    color: #475569;
}

.audit-badge.badge-price {
    background: #dbeafe;
    color: #1e40af;
}

.audit-badge.badge-bom {
    background: #ede9fe;
    color: #6b21a8;
}

.audit-summary-text {
    font-size: 0.79rem;
    font-weight: 600;
    color: var(--bom-text-strong);
    margin-bottom: 8px;
}

.audit-diff-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.74rem;
    margin-top: 6px;
    border: 1px solid #f1f5f9;
    border-radius: 6px;
    overflow: hidden;
}

.audit-diff-table th {
    background: #f8fafc;
    padding: 6px 10px;
    text-align: left;
    font-size: 0.67rem;
    font-weight: 600;
    color: var(--bom-text-muted);
    border-bottom: 1px solid #e2e8f0;
}

.audit-diff-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #f1f5f9;
}

.audit-diff-table tr:last-child td {
    border-bottom: none;
}

.diff-val-old {
    color: #ef4444;
    text-decoration: line-through;
    background: #fef2f2;
    padding: 2px 5px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.72rem;
}

.diff-val-new {
    color: #15803d;
    font-weight: 600;
    background: #f0fdf4;
    padding: 2px 5px;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.72rem;
}
</style>
@endpush

@section('content')
<div class="bom-page-container">

    <!-- 1. Page Header — HR-style flat title row -->
    <div class="bom-parent-header">
        <div class="bom-parent-title-row">
            <div>
                <h1 class="bom-parent-title">
                    <i class="ph ph-cooking-pot"></i>
                    <span>Bill of Materials (BOM) & Recipe Management</span>
                </h1>
                <p class="bom-parent-subtitle">Configure product recipes, raw ingredient deductions, live component costing, and profit margin analysis.</p>
            </div>
            <div class="bom-header-actions">
                <a href="{{ route('inventory.product-categories') }}" class="bom-action-btn-secondary" title="View Item Master Catalog">
                    <i class="ph ph-package"></i>
                    <span>Item Master</span>
                </a>
                <button type="button" class="bom-action-btn-primary" onclick="openBomDrawer('new')" title="Configure new recipe or product (F2)">
                    <i class="ph ph-plus-circle"></i>
                    <span>New BOM Recipe</span>
                </button>
            </div>
        </div>
    </div>

    <!-- 2. KPI Metrics Bar -->
    <section class="bom-metrics-grid">
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Active BOM Recipes</span>
                <span class="bom-metric-value" id="statActiveBoms">0</span>
                <span class="bom-metric-sub"><i class="ph ph-check-circle"></i> Connected to Item Master</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-book-open"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Average Raw Cost</span>
                <span class="bom-metric-value" id="statAvgCost">₱0.00</span>
                <span class="bom-metric-sub" style="color: var(--bom-text-muted);">Per batch / serving</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-scales"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Average Profit Margin</span>
                <span class="bom-metric-value" id="statAvgMargin" style="color: var(--bom-success);">0.0%</span>
                <span class="bom-metric-sub" id="statMarginHealth">Healthy Margins</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-trend-up"></i></div>
        </div>
        <div class="bom-metric-card">
            <div class="bom-metric-info">
                <span class="bom-metric-label">Profitability Status</span>
                <span class="bom-metric-value" id="statProfitableCount" style="color: var(--bom-success);">0 Gaining</span>
                <span class="bom-metric-sub" id="statDeficitCount" style="color: var(--bom-text-muted);">0 At Deficit</span>
            </div>
            <div class="bom-metric-icon"><i class="ph ph-shield-check"></i></div>
        </div>
    </section>

    <!-- 3. Filter & Search Bar -->
    <section class="bom-filter-bar">
        <div class="bom-search-wrapper">
            <i class="ph ph-magnifying-glass bom-search-icon"></i>
            <input type="text" id="bomSearchInput" class="bom-search-input" placeholder="Search recipe name, SKU, raw ingredient..." autocomplete="off">
        </div>
        <div style="display: flex; align-items: center; gap: 10px;">
            <select id="bomCategoryFilter" class="bom-filter-category-select" onchange="handleBomCategoryFilter(this.value)">
                <option value="ALL">All Categories</option>
            </select>
            <button type="button" class="bom-action-btn-secondary" onclick="toggleAllBomBreakdowns()" style="font-size: 0.78rem; padding: 7px 12px;">
                <i class="ph ph-arrows-out-line-vertical" id="iconExpandAllBoms"></i>
                <span id="textExpandAllBoms">Expand All</span>
            </button>
        </div>
    </section>

    <!-- 4. BOM Master Grid Table with Expandable Raw Materials Breakdown -->
    <div class="bom-master-card">
        <div class="bom-table-container">
            <table class="bom-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Finished Product</th>
                        <th>Category</th>
                        <th class="th-right">Total BOM Cost</th>
                        <th class="th-right">Selling Price</th>
                        <th class="th-right">Gross Profit (PHP)</th>
                        <th class="th-right">Margin (%)</th>
                        <th style="width: 140px;">Profitability Status</th>
                        <th class="th-right" style="width: 90px;">Action</th>
                    </tr>
                </thead>
                <tbody id="bomMasterTbody">
                    <!-- Populated dynamically via JS -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 5. Slide-Out Detail Drawer: Create / Edit Product BOM -->
<div class="bom-drawer-backdrop" id="bomDrawerBackdrop" onclick="closeBomDrawer()"></div>
<aside class="bom-drawer-container" id="bomDrawer">
    <header class="bom-drawer-header">
        <div class="bom-drawer-title-box">
            <div class="bom-header-icon" style="width: 38px; height: 38px; font-size: 20px;">
                <i class="ph ph-cooking-pot"></i>
            </div>
            <div>
                <h3 class="bom-drawer-title" id="drawerBomTitle">Configure Bill of Materials</h3>
                <p class="bom-drawer-subtitle" id="drawerBomSubtitle">Define raw materials, component quantities & pricing</p>
            </div>
        </div>
        <button type="button" class="bom-drawer-close-btn" onclick="closeBomDrawer()">
            <i class="ph ph-x"></i>
        </button>
    </header>

    <!-- BOM Drawer Tab Navigation -->
    <nav class="bom-drawer-nav" id="bomDrawerNav">
        <button type="button" class="bom-drawer-tab-btn active" data-tab="tab-bom-builder" onclick="switchBomDrawerTab('tab-bom-builder')">
            <i class="ph ph-cooking-pot"></i>
            <span>Recipe Builder</span>
        </button>
        <button type="button" class="bom-drawer-tab-btn" data-tab="tab-bom-audit" onclick="switchBomDrawerTab('tab-bom-audit')">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Audit Logs</span>
        </button>
    </nav>

    <div class="bom-drawer-body">
        
        <!-- Tab 1: Recipe Builder Pane -->
        <div class="bom-tab-pane active" id="pane-tab-bom-builder">
            <!-- Mode Switcher: Link Existing vs Create New Product -->
            <div class="bom-mode-switcher" id="bomModeSwitcher">
            <button type="button" class="bom-mode-btn active" id="btnModeExisting" onclick="switchBomDrawerMode('existing')">
                <i class="ph ph-link"></i>
                <span>Existing Item Master</span>
            </button>
            <button type="button" class="bom-mode-btn" id="btnModeNew" onclick="switchBomDrawerMode('new_product')">
                <i class="ph ph-plus-circle"></i>
                <span>Create New Product</span>
            </button>
        </div>

        <form id="bomForm" onsubmit="handleSaveBom(event)">
            <input type="hidden" id="bomProductId" value="">

            <!-- Existing Item Selector Section -->
            <div id="sectionExistingProduct" class="bom-form-group">
                <label for="selectExistingItem">Select Finished Product *</label>
                <select id="selectExistingItem" class="bom-form-select" onchange="handleSelectExistingProduct(this.value)">
                    <!-- Options populated from window.AppStore.products -->
                </select>
            </div>

            <!-- New Product Fields Section -->
            <div id="sectionNewProduct" style="display: none; flex-direction: column; gap: 12px;">
                <div class="bom-form-row-2">
                    <div class="bom-form-group">
                        <label for="newProdSku">Product SKU *</label>
                        <input type="text" id="newProdSku" class="bom-form-input" placeholder="e.g. BEV-901">
                    </div>
                    <div class="bom-form-group">
                        <label for="newProdCategory">Category *</label>
                        <select id="newProdCategory" class="bom-form-select">
                            <!-- Populated from categories -->
                        </select>
                    </div>
                </div>
                <div class="bom-form-group">
                    <label for="newProdName">Product Name *</label>
                    <input type="text" id="newProdName" class="bom-form-input" placeholder="e.g. Pistachio Matcha Cold Foam">
                </div>
                <div class="bom-form-row-2">
                    <div class="bom-form-group">
                        <label for="newProdUom">Unit of Measure (UOM) *</label>
                        <select id="newProdUom" class="bom-form-select">
                            <option value="Cup">Cup</option>
                            <option value="Plate">Plate</option>
                            <option value="Slice">Slice</option>
                            <option value="Piece">Piece</option>
                            <option value="Bottle">Bottle</option>
                            <option value="Serving">Serving</option>
                        </select>
                    </div>
                    <div class="bom-form-group">
                        <label for="newProdPackSize">Pack Size / Spec</label>
                        <input type="text" id="newProdPackSize" class="bom-form-input" placeholder="e.g. 16 oz">
                    </div>
                </div>
            </div>

            <!-- Selling Price Input -->
            <div class="bom-form-row-2" style="margin-top: 12px;">
                <div class="bom-form-group">
                    <label for="bomSellingPrice">Selling Price (PHP) *</label>
                    <input type="number" step="0.01" min="0" id="bomSellingPrice" class="bom-form-input" required placeholder="0.00" oninput="recalculateDrawerBomFinancials()">
                </div>
                <div class="bom-form-group">
                    <label for="bomBatchYield">Batch Yield / Output Qty *</label>
                    <input type="number" step="any" min="1" id="bomBatchYield" class="bom-form-input" value="1" required oninput="recalculateDrawerBomFinancials()">
                </div>
            </div>

            <!-- Inventory Movement & BOM Behavior Settings -->
            <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 12px;">
                <label class="bom-checkbox-card">
                    <input type="checkbox" id="bomTrackPhysicalStock" checked>
                    <div>
                        <div class="bom-checkbox-title">Track Physical Stock</div>
                        <div class="bom-checkbox-desc">Maintains on-hand balance, low-stock alerts, and shows in Stocks Overview for counting/receiving.</div>
                    </div>
                </label>

                <label class="bom-checkbox-card">
                    <input type="checkbox" id="bomExplodeBomOnSale" checked>
                    <div>
                        <div class="bom-checkbox-title">Explode Recipe / BOM on Sale</div>
                        <div class="bom-checkbox-desc">When sold on POS, deducts sub-ingredients from Item Master instead of deducting this item directly.</div>
                    </div>
                </label>
            </div>

            <!-- Raw Materials / Ingredients Builder Repeater -->
            <div class="bom-builder-card" style="margin-top: 14px;">
                <div class="bom-builder-header">
                    <div>
                        <span style="font-size: 0.78rem; font-weight: 700; color: var(--bom-text-strong);">Raw Materials & Ingredients</span>
                        <div style="font-size: 0.68rem; color: var(--bom-text-muted);">Pulls live unit costs from Item Master (customizable per recipe unit)</div>
                    </div>
                    <button type="button" class="bom-action-btn-secondary" onclick="addBomIngredientRow()" style="font-size: 0.72rem; padding: 4px 8px;">
                        <i class="ph ph-plus"></i>
                        <span>Add Material</span>
                    </button>
                </div>
                <div class="bom-builder-items-list">
                    <div class="bom-builder-cols-header">
                        <span>Component Item</span>
                        <span>Qty & UOM</span>
                        <span style="text-align: right;">Unit Cost</span>
                        <span style="text-align: right;">Subtotal</span>
                        <span></span>
                    </div>
                    <div id="bomIngredientRowsContainer" style="display: flex; flex-direction: column; gap: 8px;">
                        <!-- Dynamic ingredient rows -->
                    </div>
                </div>
            </div>

            <!-- Profitability Radar Widget -->
            <div class="bom-radar-card" style="margin-top: 14px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.76rem; font-weight: 700; color: var(--bom-text-strong);">Financial & Profitability Radar</span>
                    <span id="radarHealthTag" class="bom-profit-pill profitable">Gaining Profit</span>
                </div>
                <div class="bom-radar-summary-grid">
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Total Raw Cost</div>
                        <div class="bom-radar-stat-val" id="radarTotalCost">₱0.00</div>
                    </div>
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Gross Profit</div>
                        <div class="bom-radar-stat-val" id="radarGrossProfit">₱0.00</div>
                    </div>
                    <div class="bom-radar-stat-box">
                        <div class="bom-radar-stat-label">Profit Margin</div>
                        <div class="bom-radar-stat-val" id="radarProfitMargin">0.0%</div>
                    </div>
                </div>
                <div id="radarBanner" class="bom-radar-banner is-profit">
                    <i id="radarBannerIcon" class="ph ph-trend-up"></i>
                    <span id="radarBannerText">Healthy Margin: You are gaining profit on every unit sold.</span>
                </div>
            </div>

            <div class="bom-drawer-footer" style="padding: 16px 0 0 0; margin-top: 14px; border-top: 1px solid var(--bom-border-subtle);">
                <button type="button" class="bom-action-btn-secondary" onclick="closeBomDrawer()">Cancel</button>
                <button type="submit" class="bom-action-btn-primary">
                    <i class="ph ph-check"></i>
                    <span id="btnSaveBomText">Save BOM & Product</span>
                </button>
            </div>
        </form>
        </div>

        <!-- Tab 2: Audit Logs Pane -->
        <div class="bom-tab-pane" id="pane-tab-bom-audit" style="display: none; padding-top: 4px;">
            <div id="bomAuditLogsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                <!-- Populated dynamically via renderBomAuditLogs -->
            </div>
            <div style="margin-top: 20px; padding-top: 14px; border-top: 1px solid var(--bom-border-subtle); display: flex; justify-content: flex-end;">
                <button type="button" class="bom-action-btn-secondary" onclick="closeBomDrawer()">Close</button>
            </div>
        </div>
    </div>
</aside>

<!-- Toast -->
<div id="bomToast" style="position: fixed; bottom: 24px; right: 24px; background: #0f172a; color: #fff; padding: 12px 18px; border-radius: 10px; font-size: 0.82rem; font-weight: 500; display: flex; align-items: center; gap: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.2); transform: translateY(100px); opacity: 0; transition: all 0.25s ease; z-index: 2000;">
    <i class="ph ph-check-circle" style="color: #10b981; font-size: 18px;"></i>
    <span id="bomToastMsg">Action completed!</span>
</div>

@push('scripts')
<script>
(function() {
    'use strict';

    // =========================================================================
    // STATE STORE & ITEM MASTER SYNCHRONIZATION
    // =========================================================================
    const DEFAULT_CATEGORIES = [
        'Beverages', 'Main Course', 'Pastries & Desserts', 'Raw Ingredients',
        'Appetizers & Starters', 'Side Dishes', 'Packaging & Disposables', 'Syrups & Flavors'
    ];

    const INITIAL_BOMS = [
        {
            productId: 1, // Signature Spanish Latte
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans', qty: 18, unit: 'g', unitCost: 1.20 },
                { rawSku: 'RAW-MILK-COND', name: 'Sweetened Condensed Milk', qty: 30, unit: 'ml', unitCost: 0.45 },
                { rawSku: 'RAW-MILK-FRESH', name: 'Barista Whole Fresh Milk', qty: 220, unit: 'ml', unitCost: 0.12 },
                { rawSku: 'PKG-501', name: 'Hot Cup 16oz & Lid', qty: 1, unit: 'pc', unitCost: 3.50 }
            ]
        },
        {
            productId: 2, // Iced Americano Grande
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans', qty: 20, unit: 'g', unitCost: 1.20 },
                { rawSku: 'RAW-WATER-ICE', name: 'Purified Water & Ice', qty: 350, unit: 'ml', unitCost: 0.01 },
                { rawSku: 'PKG-501', name: 'Clear Cold Cup 20oz & Straw', qty: 1, unit: 'pc', unitCost: 4.20 }
            ]
        },
        {
            productId: 4, // Truffle Mushroom Pasta
            yield: 1,
            ingredients: [
                { rawSku: 'RAW-PASTA-FETT', name: 'Fettuccine Dried Pasta', qty: 120, unit: 'g', unitCost: 0.25 },
                { rawSku: 'RAW-MUSH-SHIIT', name: 'Shiitake & Cremini Blend', qty: 80, unit: 'g', unitCost: 0.55 },
                { rawSku: 'RAW-TRUFF-OIL', name: 'White Truffle Infused Cream', qty: 60, unit: 'ml', unitCost: 0.85 },
                { rawSku: 'RAW-CHEESE-PARM', name: 'Parmigiano Reggiano', qty: 25, unit: 'g', unitCost: 1.10 }
            ]
        }
    ];

    const CATEGORY_PALETTES = {
        purple: { label: 'Purple / Violet', bg: '#faf5ff', text: '#7e22ce', border: '#e9d5ff', dot: '#a855f7' },
        amber: { label: 'Amber / Gold', bg: '#fffbeb', text: '#b45309', border: '#fde68a', dot: '#f59e0b' },
        rose: { label: 'Rose / Pink', bg: '#fdf2f8', text: '#be185d', border: '#fbcfe8', dot: '#ec4899' },
        emerald: { label: 'Emerald / Green', bg: '#f0fdf4', text: '#15803d', border: '#bbf7d0', dot: '#10b981' },
        blue: { label: 'Ocean / Blue', bg: '#eff6ff', text: '#1d4ed8', border: '#bfdbfe', dot: '#3b82f6' },
        orange: { label: 'Tangerine / Orange', bg: '#fff7ed', text: '#c2410c', border: '#fed7aa', dot: '#f97316' },
        teal: { label: 'Teal / Cyan', bg: '#f0fdfa', text: '#0f766e', border: '#99f6e4', dot: '#14b8a6' },
        indigo: { label: 'Indigo / Navy', bg: '#eef2ff', text: '#4338ca', border: '#c7d2fe', dot: '#6366f1' },
        slate: { label: 'Slate / Neutral', bg: '#f8fafc', text: '#475569', border: '#cbd5e1', dot: '#64748b' }
    };

    const DEFAULT_CATEGORY_META = {
        'Beverages': { icon: 'ph-coffee', color: 'purple' },
        'Main Course': { icon: 'ph-fork-knife', color: 'amber' },
        'Pastries & Desserts': { icon: 'ph-cookie', color: 'rose' },
        'Raw Ingredients': { icon: 'ph-plant', color: 'emerald' },
        'Packaging & Disposables': { icon: 'ph-box', color: 'blue' },
        'Syrups & Flavors': { icon: 'ph-drop', color: 'orange' },
        'Uncategorized': { icon: 'ph-tag', color: 'slate' }
    };

    function getCategoryMeta(cat) {
        if (window.AppStore && window.AppStore.categoryMeta && window.AppStore.categoryMeta[cat]) {
            return window.AppStore.categoryMeta[cat];
        }
        if (DEFAULT_CATEGORY_META[cat]) {
            return DEFAULT_CATEGORY_META[cat];
        }
        return { icon: 'ph-tag', color: 'purple' };
    }

    function getCategoryPalette(colorKey) {
        return CATEGORY_PALETTES[colorKey] || CATEGORY_PALETTES.purple;
    }

    window.AppStore = {
        categories: JSON.parse(localStorage.getItem('rms_product_categories')) || DEFAULT_CATEGORIES,
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        products: JSON.parse(localStorage.getItem('rms_inventory_products')) || [],
        boms: JSON.parse(localStorage.getItem('rms_boms')) || INITIAL_BOMS,
        searchQuery: '',
        categoryFilter: 'ALL',
        expandedProductIds: new Set([1]) // First expanded by default
    };

    // If products array is empty, fetch fallback from Item Master seed
    if (!window.AppStore.products || window.AppStore.products.length === 0) {
        window.AppStore.products = [
            { id: 1, sku: 'BEV-001', name: 'Signature Spanish Latte', category: 'Beverages', unit: 'Cup', packSize: '16 oz', costPrice: 45.00, sellingPrice: 150.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 2, sku: 'BEV-002', name: 'Iced Americano Grande', category: 'Beverages', unit: 'Cup', packSize: '20 oz', costPrice: 28.00, sellingPrice: 120.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 3, sku: 'BEV-003', name: 'Matcha Green Tea Fusion', category: 'Beverages', unit: 'Cup', packSize: '16 oz', costPrice: 52.00, sellingPrice: 175.00, hasBom: false, canBeSold: true, canBePurchased: false },
            { id: 4, sku: 'MNC-101', name: 'Truffle Mushroom Pasta', category: 'Main Course', unit: 'Plate', packSize: '320g', costPrice: 115.00, sellingPrice: 380.00, hasBom: true, canBeSold: true, canBePurchased: false },
            { id: 10, sku: 'RAW-COFFEE-01', name: 'Espresso Roast Beans (1kg)', category: 'Raw Ingredients', unit: 'Kg', packSize: '1000g', costPrice: 650.00, sellingPrice: 0.00, hasBom: false, canBeSold: false, canBePurchased: true },
            { id: 11, sku: 'PKG-501', name: 'Kraft Takeout Box (Medium)', category: 'Packaging & Disposables', unit: 'Piece', packSize: 'Pack of 50', costPrice: 8.50, sellingPrice: 15.00, hasBom: false, canBeSold: false, canBePurchased: true }
        ];
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
    }

    // Save initial BOMs if not set
    if (!localStorage.getItem('rms_boms')) {
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
    }

    // Auto-sync: Guarantee any product in Item Master with hasBom === true is registered into BOM catalog
    let bomsModified = false;
    window.AppStore.products.forEach(p => {
        if (p.hasBom) {
            const hasBomEntry = window.AppStore.boms.some(b => b.productId === p.id);
            if (!hasBomEntry) {
                window.AppStore.boms.push({
                    productId: p.id,
                    yield: 1,
                    ingredients: []
                });
                bomsModified = true;
            }
        }
    });
    if (bomsModified) {
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
    }

    // DOM Caches
    const bomMasterTbody = document.getElementById('bomMasterTbody');
    const bomSearchInput = document.getElementById('bomSearchInput');
    const bomCategoryFilter = document.getElementById('bomCategoryFilter');
    const bomDrawer = document.getElementById('bomDrawer');
    const bomDrawerBackdrop = document.getElementById('bomDrawerBackdrop');
    const bomToast = document.getElementById('bomToast');
    const bomToastMsg = document.getElementById('bomToastMsg');

    // Drawer inputs
    const bomProductId = document.getElementById('bomProductId');
    const selectExistingItem = document.getElementById('selectExistingItem');
    const newProdSku = document.getElementById('newProdSku');
    const newProdName = document.getElementById('newProdName');
    const newProdCategory = document.getElementById('newProdCategory');
    const newProdUom = document.getElementById('newProdUom');
    const newProdPackSize = document.getElementById('newProdPackSize');
    const bomSellingPrice = document.getElementById('bomSellingPrice');
    const bomBatchYield = document.getElementById('bomBatchYield');
    const bomIngredientRowsContainer = document.getElementById('bomIngredientRowsContainer');

    // Drawer Mode: 'existing' vs 'new_product'
    let currentDrawerMode = 'existing';

    function showToast(msg) {
        if (!bomToast) return;
        bomToastMsg.textContent = msg;
        bomToast.style.transform = 'translateY(0)';
        bomToast.style.opacity = '1';
        setTimeout(() => {
            bomToast.style.transform = 'translateY(100px)';
            bomToast.style.opacity = '0';
        }, 3200);
    }

    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    // Calculate BOM recipe total cost
    function calculateBomCost(bom) {
        if (!bom || !bom.ingredients) return 0;
        const total = bom.ingredients.reduce((acc, ing) => acc + (Number(ing.qty || 0) * Number(ing.unitCost || 0)), 0);
        const yieldVal = Number(bom.yield) || 1;
        return yieldVal > 0 ? (total / yieldVal) : total;
    }

    // Auto-seed baseline audit logs for existing products if not present
    window.AppStore.products.forEach(p => {
        if (!p.auditLogs || p.auditLogs.length === 0) {
            p.createdAt = p.createdAt || 'Sep 15, 2026 • 09:30 AM';
            p.createdBy = p.createdBy || 'Dorothy Diaz (System Admin)';
            p.auditLogs = [
                {
                    id: 'LOG-INIT-' + p.id,
                    formattedDate: p.createdAt,
                    user: { name: 'Dorothy Diaz', email: 'dorothy@dorothydiaz.internal', role: 'System Administrator', avatar: 'DD' },
                    actionType: 'CREATED',
                    summary: `Initial catalog record created for "${p.name}"`,
                    changes: [
                        { field: 'SKU Code', oldValue: '—', newValue: p.sku },
                        { field: 'Product Name', oldValue: '—', newValue: p.name },
                        { field: 'Selling Price', oldValue: '—', newValue: formatPHP(p.sellingPrice) },
                        { field: 'Cost Price', oldValue: '—', newValue: formatPHP(p.costPrice) },
                        { field: 'Has BOM', oldValue: '—', newValue: p.hasBom ? 'Yes (Recipe Active)' : 'No' }
                    ]
                }
            ];
        }
    });

    function createAuditEntry(user, actionType, summary, changes = []) {
        const now = new Date();
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const month = months[now.getMonth()];
        const day = String(now.getDate()).padStart(2, '0');
        const year = now.getFullYear();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? hours : 12;
        const formattedHours = String(hours).padStart(2, '0');
        const formattedDate = `${month} ${day}, ${year} • ${formattedHours}:${minutes} ${ampm}`;

        return {
            id: 'LOG-' + Date.now() + '-' + Math.floor(Math.random() * 1000),
            timestamp: now.toISOString(),
            formattedDate: formattedDate,
            user: {
                name: user?.name || 'John Abiguero',
                email: user?.email || 'jabiguero@dorothydiaz.internal',
                role: user?.role || 'Lead Inventory Admin',
                avatar: (user?.name || 'JA').split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase()
            },
            actionType: actionType || 'UPDATED',
            summary: summary || 'Recipe updated',
            changes: changes || []
        };
    }

    window.switchBomDrawerTab = function(tabId) {
        document.querySelectorAll('.bom-drawer-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.bom-tab-pane').forEach(pane => {
            pane.style.display = (pane.id === 'pane-' + tabId) ? 'block' : 'none';
        });

        if (tabId === 'tab-bom-audit') {
            const pId = Number(bomProductId.value || selectExistingItem.value);
            const product = window.AppStore.products.find(p => p.id === pId);
            const bom = window.AppStore.boms.find(b => b.productId === pId);
            renderBomAuditLogs(product, bom);
        }
    };

    function renderBomAuditLogs(product, bom) {
        const container = document.getElementById('bomAuditLogsContainer');
        if (!container) return;

        if (!product || currentDrawerMode === 'new_product') {
            container.innerHTML = `
                <div class="audit-overview-card" style="grid-template-columns: 1fr; text-align: center; padding: 28px 16px;">
                    <div style="color: var(--bom-text-muted); display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <i class="ph ph-sparkle" style="font-size: 2rem; color: var(--bom-primary);"></i>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--bom-text-strong);">New BOM Recipe Draft</h4>
                        <p style="margin: 0; font-size: 0.8rem; max-width: 320px; line-height: 1.4;">
                            Audit trail will automatically initialize with recipe creation timestamp and user identity upon saving.
                        </p>
                    </div>
                </div>
            `;
            return;
        }

        const logs = product.auditLogs || [];
        const createdAt = product.createdAt || 'Sep 15, 2026 • 09:30 AM';
        const createdBy = product.createdBy || 'Dorothy Diaz (System Admin)';
        const lastModified = logs.length > 0 ? logs[0].formattedDate : createdAt;

        let overviewHtml = `
            <div class="audit-overview-card">
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-calendar-plus"></i> Creation Date</span>
                    <span class="audit-metric-val" title="${createdAt}">${createdAt}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-user-circle"></i> Registered By</span>
                    <span class="audit-metric-val" title="${createdBy}">${createdBy}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-clock-counter-clockwise"></i> Total Revisions</span>
                    <span class="audit-metric-val">${logs.length} ${logs.length === 1 ? 'Entry' : 'Entries'}</span>
                </div>
                <div class="audit-metric-box">
                    <span class="audit-metric-label"><i class="ph ph-arrows-clockwise"></i> Last Modified</span>
                    <span class="audit-metric-val" title="${lastModified}">${lastModified}</span>
                </div>
            </div>
        `;

        if (logs.length === 0) {
            container.innerHTML = overviewHtml + `
                <div style="text-align: center; padding: 24px; color: var(--bom-text-muted); font-size: 0.82rem;">
                    No revision history recorded yet for this recipe.
                </div>
            `;
            return;
        }

        let timelineHtml = '<div class="audit-timeline-container">';

        logs.forEach(log => {
            let iconClass = '';
            let iconGlyph = 'ph-clock-counter-clockwise';
            let badgeClass = 'badge-updated';
            let badgeText = log.actionType || 'UPDATED';

            if (log.actionType === 'CREATED') {
                iconClass = 'is-created';
                iconGlyph = 'ph-plus-circle';
                badgeClass = 'badge-created';
                badgeText = 'RECIPE CREATED';
            } else if (log.actionType === 'PRICE_CHANGE') {
                iconClass = 'is-price';
                iconGlyph = 'ph-currency-circle-dollar';
                badgeClass = 'badge-price';
                badgeText = 'PRICE ADJUSTED';
            } else if (log.actionType === 'BOM_CHANGE' || log.actionType === 'BOM_MODIFIED') {
                iconClass = 'is-bom';
                iconGlyph = 'ph-cooking-pot';
                badgeClass = 'badge-bom';
                badgeText = 'RECIPE MODIFIED';
            }

            const userName = log.user?.name || 'Dorothy Diaz';
            const userRole = log.user?.role || 'Staff Member';
            const userEmail = log.user?.email || '';
            const userAvatar = log.user?.avatar || userName.substring(0, 2).toUpperCase();

            let diffRows = '';
            if (log.changes && log.changes.length > 0) {
                diffRows = `
                    <table class="audit-diff-table">
                        <thead>
                            <tr>
                                <th style="width: 32%;">Property / Field</th>
                                <th style="width: 34%;">Previous Value</th>
                                <th style="width: 34%;">New Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${log.changes.map(ch => `
                                <tr>
                                    <td style="font-weight: 600; color: var(--bom-text-strong);">${ch.field}</td>
                                    <td><span class="diff-val-old">${ch.oldValue || '—'}</span></td>
                                    <td><span class="diff-val-new">${ch.newValue || '—'}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                `;
            }

            timelineHtml += `
                <div class="audit-timeline-item">
                    <div class="audit-timeline-node-icon ${iconClass}">
                        <i class="ph ${iconGlyph}"></i>
                    </div>
                    <div class="audit-item-header">
                        <div class="audit-user-profile">
                            <div class="audit-user-avatar" title="${userEmail}">${userAvatar}</div>
                            <div>
                                <div class="audit-user-name">${userName}</div>
                                <div class="audit-user-role">${userRole}</div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <span class="audit-badge ${badgeClass}">${badgeText}</span>
                            <span style="font-size: 0.72rem; color: var(--bom-text-muted); font-weight: 500;">
                                <i class="ph ph-clock" style="vertical-align: middle;"></i> ${log.formattedDate}
                            </span>
                        </div>
                    </div>
                    <div class="audit-summary-text">${log.summary || 'Recipe modified'}</div>
                    ${diffRows}
                </div>
            `;
        });

        timelineHtml += '</div>';
        container.innerHTML = overviewHtml + timelineHtml;
    }

    // =========================================================================
    // RENDERING: BOM MASTER GRID & KPI STATS
    // =========================================================================
    function renderBomView() {
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const catFilter = window.AppStore.categoryFilter;

        // Reconcile any newly flagged hasBom products from Item Master into BOM state
        window.AppStore.products.forEach(p => {
            if (p.hasBom && !window.AppStore.boms.some(b => b.productId === p.id)) {
                window.AppStore.boms.push({
                    productId: p.id,
                    yield: 1,
                    ingredients: []
                });
            }
        });

        // Build list of products that have a BOM configured or flagged in Item Master
        const items = [];
        window.AppStore.boms.forEach(bom => {
            const product = window.AppStore.products.find(p => p.id === bom.productId);
            if (!product) return;
            // If product hasBom is explicitly set to false and has no ingredients, omit from BOM view
            if (product.hasBom === false && (!bom.ingredients || bom.ingredients.length === 0)) return;

            // Apply category filter
            if (catFilter !== 'ALL' && product.category !== catFilter) return;

            // Search filter
            if (query) {
                const matchName = product.name.toLowerCase().includes(query);
                const matchSku = product.sku.toLowerCase().includes(query);
                const matchCat = (product.category || '').toLowerCase().includes(query);
                const matchIng = (bom.ingredients || []).some(ing => (ing.name || '').toLowerCase().includes(query) || (ing.rawSku || '').toLowerCase().includes(query));
                if (!matchName && !matchSku && !matchCat && !matchIng) return;
            }

            const hasIngredients = bom.ingredients && bom.ingredients.length > 0;
            const totalCost = hasIngredients ? calculateBomCost(bom) : 0;
            const selling = Number(product.sellingPrice) || 0;
            const profit = hasIngredients ? (selling - totalCost) : 0;
            const marginPct = (hasIngredients && selling > 0) ? ((profit / selling) * 100) : 0;

            items.push({
                product,
                bom,
                hasIngredients,
                totalCost,
                selling,
                profit,
                marginPct
            });
        });

        // Update KPIs
        const totalBoms = items.length;
        const configuredItems = items.filter(i => i.hasIngredients);
        const pendingCount = totalBoms - configuredItems.length;

        document.getElementById('statActiveBoms').textContent = totalBoms;

        if (configuredItems.length > 0) {
            const avgCost = configuredItems.reduce((acc, i) => acc + i.totalCost, 0) / configuredItems.length;
            const avgMargin = configuredItems.reduce((acc, i) => acc + i.marginPct, 0) / configuredItems.length;
            const profitableCount = configuredItems.filter(i => i.profit > 0).length;
            const deficitCount = configuredItems.length - profitableCount;

            document.getElementById('statAvgCost').textContent = formatPHP(avgCost);
            document.getElementById('statAvgMargin').textContent = `${avgMargin.toFixed(1)}%`;
            document.getElementById('statProfitableCount').textContent = `${profitableCount} Gaining`;
            document.getElementById('statDeficitCount').textContent = pendingCount > 0 ? `${pendingCount} Pending` : `${deficitCount} At Deficit`;

            const healthEl = document.getElementById('statMarginHealth');
            if (avgMargin >= 30) {
                healthEl.textContent = 'High Profit Margin';
                healthEl.style.color = 'var(--bom-success)';
            } else if (avgMargin > 0) {
                healthEl.textContent = 'Moderate Margin';
                healthEl.style.color = 'var(--bom-warning)';
            } else {
                healthEl.textContent = 'Critical Margin Alert';
                healthEl.style.color = 'var(--bom-danger)';
            }
        } else {
            document.getElementById('statAvgCost').textContent = '₱0.00';
            document.getElementById('statAvgMargin').textContent = '0.0%';
            document.getElementById('statProfitableCount').textContent = '0 Gaining';
            document.getElementById('statDeficitCount').textContent = `${pendingCount} Pending`;
        }

        // Render Table Rows
        if (items.length === 0) {
            bomMasterTbody.innerHTML = `
                <tr>
                    <td colspan="9" style="text-align: center; padding: 48px 24px; color: var(--bom-text-muted);">
                        <i class="ph ph-cooking-pot" style="font-size: 38px; color: var(--bom-text-subtle); margin-bottom: 8px; display: inline-block;"></i>
                        <div style="font-weight: 700; color: var(--bom-text-strong); font-size: 0.95rem;">No Bill of Materials recipes found</div>
                        <div style="font-size: 0.8rem; margin-top: 4px;">Items flagged with "Has Bill of Materials" in Item Master or created here will automatically appear.</div>
                    </td>
                </tr>
            `;
            return;
        }

        let html = '';
        items.forEach(({ product, bom, hasIngredients, totalCost, selling, profit, marginPct }) => {
            const isExpanded = window.AppStore.expandedProductIds.has(product.id);

            // Determine status
            let statusPillClass = 'profitable';
            let statusText = `Gaining Profit (+${marginPct.toFixed(1)}%)`;
            let statusIcon = 'ph-trend-up';

            if (!hasIngredients) {
                statusPillClass = 'is-pending';
                statusText = 'Recipe Pending';
                statusIcon = 'ph-clock-countdown';
            } else if (marginPct <= 0) {
                statusPillClass = 'unprofitable';
                statusText = `Loss Deficit (${marginPct.toFixed(1)}%)`;
                statusIcon = 'ph-trend-down';
            } else if (marginPct < 20) {
                statusPillClass = 'low-margin';
                statusText = `Low Margin (+${marginPct.toFixed(1)}%)`;
                statusIcon = 'ph-trend-up';
            }

            // 1. Parent Row
            html += `
                <tr class="bom-row" onclick="toggleBomBreakdown(${product.id})">
                    <td style="text-align: center;">
                        <button type="button" class="bom-expand-btn ${isExpanded ? 'is-expanded' : ''}" onclick="event.stopPropagation(); toggleBomBreakdown(${product.id})">
                            <i class="ph ph-caret-down"></i>
                        </button>
                    </td>
                    <td>
                        <div class="bom-product-title-group">
                            <div class="bom-product-icon"><i class="ph ph-cube"></i></div>
                            <div>
                                <div class="bom-product-name">${product.name}</div>
                                <div class="bom-product-sku">SKU: ${product.sku} &bull; Yield: ${bom.yield || 1} ${product.unit || 'unit'}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 5px; padding: 3px 8px; border-radius: 6px; font-size: 0.72rem; font-weight: 700; background: ${getCategoryPalette(getCategoryMeta(product.category).color).bg}; color: ${getCategoryPalette(getCategoryMeta(product.category).color).text}; border: 1px solid ${getCategoryPalette(getCategoryMeta(product.category).color).border};">
                            <i class="ph ${getCategoryMeta(product.category).icon}"></i>
                            <span>${product.category}</span>
                        </span>
                    </td>
                    <td class="td-right" style="font-weight: 700; color: var(--bom-text-strong);">
                        ${hasIngredients ? formatPHP(totalCost) : '<span style="color:var(--bom-text-muted); font-size:0.74rem;">Pending Setup</span>'}
                    </td>
                    <td class="td-right" style="font-weight: 700;">${formatPHP(selling)}</td>
                    <td class="td-right" style="font-weight: 700; color: ${hasIngredients ? (profit >= 0 ? 'var(--bom-success)' : 'var(--bom-danger)') : 'var(--bom-text-muted)'};">
                        ${hasIngredients ? ((profit >= 0 ? '+' : '') + formatPHP(profit)) : '<span style="color:var(--bom-text-muted);">—</span>'}
                    </td>
                    <td class="td-right" style="font-weight: 800; color: ${hasIngredients ? (marginPct >= 0 ? 'var(--bom-success)' : 'var(--bom-danger)') : 'var(--bom-text-muted)'};">
                        ${hasIngredients ? marginPct.toFixed(1) + '%' : '<span style="color:var(--bom-text-muted);">—</span>'}
                    </td>
                    <td>
                        <span class="bom-profit-pill ${statusPillClass}">
                            <i class="ph ${statusIcon}"></i>
                            <span>${statusText}</span>
                        </span>
                    </td>
                    <td class="td-right">
                        <button type="button" class="${hasIngredients ? 'bom-action-btn-secondary' : 'bom-action-btn-primary'}" onclick="event.stopPropagation(); openBomDrawer(${product.id})" style="padding: 5px 12px; font-size: 0.74rem;">
                            <i class="ph ${hasIngredients ? 'ph-pencil-simple' : 'ph-plus-circle'}"></i>
                            <span>${hasIngredients ? 'Edit BOM' : 'Set Recipe'}</span>
                        </button>
                    </td>
                </tr>
            `;

            // 2. Nested Raw Materials Breakdown Row
            const ingredients = bom.ingredients || [];
            html += `
                <tr class="bom-breakdown-row ${isExpanded ? '' : 'is-hidden'}" id="breakdown-row-${product.id}">
                    <td colspan="9" style="padding: 0;">
                        <div class="bom-breakdown-box">
            `;

            if (!hasIngredients) {
                html += `
                            <div style="text-align: center; padding: 26px 16px;">
                                <div style="font-size: 2rem; color: #d97706; margin-bottom: 8px;"><i class="ph ph-receipt"></i></div>
                                <div style="font-weight: 700; color: var(--bom-text-strong); font-size: 0.88rem;">Recipe Not Configured Yet</div>
                                <div style="font-size: 0.76rem; color: var(--bom-text-muted); max-width: 440px; margin: 4px auto 14px;">
                                    "${product.name}" is marked as having a Bill of Materials in Item Master. Define its component raw materials and automated inventory deductions.
                                </div>
                                <button type="button" class="bom-action-btn-primary" onclick="openBomDrawer(${product.id})" style="font-size: 0.76rem; margin: 0 auto; display: inline-flex;">
                                    <i class="ph ph-plus-circle"></i>
                                    <span>Configure Bill of Materials</span>
                                </button>
                            </div>
                `;
            } else {
                html += `
                            <div class="bom-breakdown-header">
                                <span class="bom-breakdown-title">
                                    <i class="ph ph-list-dashes" style="color: var(--bom-primary-dark);"></i>
                                    <span>Raw Materials Recipe Breakdown (${ingredients.length} ${ingredients.length === 1 ? 'component' : 'components'})</span>
                                </span>
                                <span style="font-size: 0.72rem; color: var(--bom-text-muted);">
                                    Batch Cost: <strong>${formatPHP(totalCost * (bom.yield || 1))}</strong> (Yield: ${bom.yield || 1})
                                </span>
                            </div>
                            <table class="bom-subtable">
                                <thead>
                                    <tr>
                                        <th>Component Material</th>
                                        <th>Material SKU</th>
                                        <th class="th-right">Recipe Quantity</th>
                                        <th class="th-right">Item Master Cost</th>
                                        <th class="th-right">Extended Cost</th>
                                        <th style="width: 160px;">Cost Contribution %</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${ingredients.map(ing => {
                                        const extCost = (Number(ing.qty) || 0) * (Number(ing.unitCost) || 0);
                                        const batchCost = totalCost * (bom.yield || 1);
                                        const pct = batchCost > 0 ? ((extCost / batchCost) * 100).toFixed(1) : 0;
                                        return `
                                            <tr>
                                                <td style="font-weight: 600; color: var(--bom-text-strong);">${ing.name}</td>
                                                <td><span style="font-family: monospace; font-size: 0.72rem; color: var(--bom-text-muted);">${ing.rawSku || '—'}</span></td>
                                                <td class="td-right" style="font-weight: 600;">${ing.qty} ${ing.unit}</td>
                                                <td class="td-right">${formatPHP(ing.unitCost)}</td>
                                                <td class="td-right" style="font-weight: 700; color: var(--bom-text-strong);">${formatPHP(extCost)}</td>
                                                <td>
                                                    <div class="bom-cost-bar-wrap">
                                                        <div class="bom-cost-bar">
                                                            <div class="bom-cost-fill" style="width: ${Math.min(100, pct)}%;"></div>
                                                        </div>
                                                        <span style="font-size: 0.68rem; font-weight: 600; min-width: 38px; text-align: right;">${pct}%</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        `;
                                    }).join('')}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4">Total Recipe Raw Material Cost</td>
                                        <td class="td-right">${formatPHP(totalCost * (bom.yield || 1))}</td>
                                        <td><strong>100% of Materials</strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                `;
            }

            html += `
                        </div>
                    </td>
                </tr>
            `;
        });

        bomMasterTbody.innerHTML = html;
    }

    window.toggleBomBreakdown = function(productId) {
        if (window.AppStore.expandedProductIds.has(productId)) {
            window.AppStore.expandedProductIds.delete(productId);
        } else {
            window.AppStore.expandedProductIds.add(productId);
        }
        renderBomView();
    };

    window.toggleAllBomBreakdowns = function() {
        const anyCollapsed = window.AppStore.boms.some(b => !window.AppStore.expandedProductIds.has(b.productId));
        if (anyCollapsed) {
            window.AppStore.boms.forEach(b => window.AppStore.expandedProductIds.add(b.productId));
            document.getElementById('textExpandAllBoms').textContent = 'Collapse All';
            document.getElementById('iconExpandAllBoms').className = 'ph ph-arrows-in-line-vertical';
        } else {
            window.AppStore.expandedProductIds.clear();
            document.getElementById('textExpandAllBoms').textContent = 'Expand All';
            document.getElementById('iconExpandAllBoms').className = 'ph ph-arrows-out-line-vertical';
        }
        renderBomView();
    };

    window.handleBomCategoryFilter = function(val) {
        window.AppStore.categoryFilter = val;
        renderBomView();
    };

    bomSearchInput.addEventListener('input', function(e) {
        window.AppStore.searchQuery = e.target.value;
        renderBomView();
    });

    // Populate category dropdowns
    function syncCategoryDropdowns() {
        const cats = window.AppStore.categories;
        bomCategoryFilter.innerHTML = '<option value="ALL">All Categories</option>' + cats.map(c => `<option value="${c}">${c}</option>`).join('');
        newProdCategory.innerHTML = cats.map(c => `<option value="${c}">${c}</option>`).join('');
    }

    // =========================================================================
    // SLIDE-OUT DRAWER: CREATE / EDIT BOM CONTROLLER
    // =========================================================================
    window.switchBomDrawerMode = function(mode) {
        currentDrawerMode = mode;
        const btnExisting = document.getElementById('btnModeExisting');
        const btnNew = document.getElementById('btnModeNew');
        const secExisting = document.getElementById('sectionExistingProduct');
        const secNew = document.getElementById('sectionNewProduct');

        if (mode === 'existing') {
            btnExisting.classList.add('active');
            btnNew.classList.remove('active');
            secExisting.style.display = 'flex';
            secNew.style.display = 'none';
            handleSelectExistingProduct(selectExistingItem.value);
        } else {
            btnExisting.classList.remove('active');
            btnNew.classList.add('active');
            secExisting.style.display = 'none';
            secNew.style.display = 'flex';
            bomProductId.value = '';
            newProdSku.value = 'PRD-' + Math.floor(1000 + Math.random() * 9000);
            newProdName.value = '';
            newProdName.focus();
        }
        recalculateDrawerBomFinancials();
    };

    window.handleSelectExistingProduct = function(prodId) {
        const prod = window.AppStore.products.find(p => p.id === Number(prodId));
        if (!prod) return;
        bomProductId.value = prod.id;
        bomSellingPrice.value = Number(prod.sellingPrice || 0).toFixed(2);

        document.getElementById('bomTrackPhysicalStock').checked = prod.trackPhysicalStock !== undefined ? prod.trackPhysicalStock : (prod.trackStock !== false);
        document.getElementById('bomExplodeBomOnSale').checked = prod.explodeBomOnSale !== undefined ? prod.explodeBomOnSale : (prod.hasBom !== false);

        // If this product already has a configured BOM recipe, sync its yield and ingredients
        const existingBom = window.AppStore.boms.find(b => b.productId === prod.id);
        if (existingBom) {
            bomBatchYield.value = existingBom.yield || 1;
            bomIngredientRowsContainer.innerHTML = '';
            if (existingBom.ingredients && existingBom.ingredients.length > 0) {
                existingBom.ingredients.forEach(ing => addBomIngredientRow(ing));
            } else {
                addBomIngredientRow();
            }
        }
        recalculateDrawerBomFinancials();
    };

    window.openBomDrawer = function(idOrMode) {
        syncCategoryDropdowns();
        populateExistingItemsDropdown();
        switchBomDrawerTab('tab-bom-builder');

        if (idOrMode === 'new') {
            document.getElementById('drawerBomTitle').textContent = 'Create BOM & Recipe';
            document.getElementById('drawerBomSubtitle').textContent = 'Set component raw materials and automated inventory deductions';
            document.getElementById('btnSaveBomText').textContent = 'Save BOM & Product';

            // Reset inputs
            bomProductId.value = '';
            bomSellingPrice.value = '';
            bomBatchYield.value = 1;
            document.getElementById('bomTrackPhysicalStock').checked = true;
            document.getElementById('bomExplodeBomOnSale').checked = true;
            bomIngredientRowsContainer.innerHTML = '';

            // Add 2 initial ingredient rows
            addBomIngredientRow();
            addBomIngredientRow();

            switchBomDrawerMode('existing');
        } else {
            const product = window.AppStore.products.find(p => p.id === Number(idOrMode));
            if (!product) return;

            document.getElementById('drawerBomTitle').textContent = `Edit BOM: ${product.name}`;
            document.getElementById('drawerBomSubtitle').textContent = `SKU: ${product.sku} • Category: ${product.category}`;
            document.getElementById('btnSaveBomText').textContent = 'Update BOM';

            bomProductId.value = product.id;
            selectExistingItem.value = product.id;
            bomSellingPrice.value = Number(product.sellingPrice || 0).toFixed(2);
            document.getElementById('bomTrackPhysicalStock').checked = product.trackPhysicalStock !== undefined ? product.trackPhysicalStock : (product.trackStock !== false);
            document.getElementById('bomExplodeBomOnSale').checked = product.explodeBomOnSale !== undefined ? product.explodeBomOnSale : (product.hasBom !== false);

            const bom = window.AppStore.boms.find(b => b.productId === product.id);
            bomBatchYield.value = (bom && bom.yield) ? bom.yield : 1;

            bomIngredientRowsContainer.innerHTML = '';
            if (bom && bom.ingredients && bom.ingredients.length > 0) {
                bom.ingredients.forEach(ing => {
                    addBomIngredientRow(ing);
                });
            } else {
                addBomIngredientRow();
            }

            switchBomDrawerMode('existing');
            document.getElementById('bomModeSwitcher').style.display = 'none'; // Lock mode on edit
        }

        recalculateDrawerBomFinancials();
        bomDrawerBackdrop.classList.add('is-open');
        bomDrawer.classList.add('is-open');
    };

    window.closeBomDrawer = function() {
        bomDrawerBackdrop.classList.remove('is-open');
        bomDrawer.classList.remove('is-open');
        document.getElementById('bomModeSwitcher').style.display = 'flex';
        switchBomDrawerTab('tab-bom-builder');
    };

    function populateExistingItemsDropdown() {
        const prods = window.AppStore.products;
        selectExistingItem.innerHTML = prods.map(p => `
            <option value="${p.id}">
                ${p.name} (${p.sku}) &bull; Category: ${p.category} &bull; Price: ${formatPHP(p.sellingPrice)}
            </option>
        `).join('');
    }

    // Dynamic Raw Material / Ingredient Row in Drawer
    window.addBomIngredientRow = function(data) {
        const rowId = 'row-' + Date.now() + '-' + Math.floor(Math.random() * 1000);
        const products = window.AppStore.products || [];

        const rowEl = document.createElement('div');
        rowEl.className = 'bom-builder-item-row';
        rowEl.id = rowId;

        // Check if data matches an existing item in Item Master
        const matchedProd = data ? products.find(p => p.sku === data.rawSku || p.name === data.name) : null;
        let extraOption = '';
        if (data && !matchedProd) {
            extraOption = `<option value="custom" data-cost="${data.unitCost || 0}" data-unit="${data.unit || 'unit'}" data-sku="${data.rawSku || 'RAW'}" selected>
                ${data.name} (${data.rawSku || 'RAW'})
            </option>`;
        }

        const opts = products.map(p => {
            const isSelected = matchedProd && matchedProd.id === p.id;
            return `<option value="${p.id}" data-cost="${p.costPrice}" data-unit="${p.unit || 'pc'}" data-sku="${p.sku}" ${isSelected ? 'selected' : ''}>
                ${p.name} (${p.sku}) &bull; Stock Cost: ${formatPHP(p.costPrice)}
            </option>`;
        }).join('');

        const defaultQty = data ? (data.qty !== undefined ? data.qty : 1) : 1;
        const initialUnit = data ? data.unit : (matchedProd ? (matchedProd.unit || 'pc') : (products[0]?.unit || 'pc'));

        let defaultUnitCost = 0;
        if (data && data.unitCost !== undefined) {
            defaultUnitCost = Number(data.unitCost);
        } else if (matchedProd) {
            defaultUnitCost = Number(matchedProd.costPrice || 0);
        } else if (products[0]) {
            defaultUnitCost = Number(products[0].costPrice || 0);
        }

        rowEl.innerHTML = `
            <div>
                <select class="bom-form-select ing-product-select" style="padding: 6px 8px; font-size: 0.78rem;" onchange="handleIngredientChange('${rowId}', true)">
                    ${extraOption}
                    ${opts}
                </select>
            </div>
            <div style="display: flex; align-items: center; gap: 4px;">
                <input type="number" step="any" min="0.0001" class="bom-form-input ing-qty-input" style="padding: 6px 6px; font-size: 0.78rem;" value="${defaultQty}" placeholder="Qty" oninput="recalculateDrawerBomFinancials()">
                <span class="ing-unit-badge" style="font-size: 0.70rem; color: var(--bom-text-muted); font-weight: 600; min-width: 22px;">${initialUnit}</span>
            </div>
            <div>
                <input type="number" step="any" min="0" class="bom-form-input ing-cost-input" style="padding: 6px 6px; font-size: 0.78rem; text-align: right;" value="${defaultUnitCost}" placeholder="0.00" oninput="recalculateDrawerBomFinancials()">
            </div>
            <div style="text-align: right;">
                <span class="ing-subtotal-val" style="font-size: 0.76rem; font-weight: 700; color: var(--bom-text-strong); font-variant-numeric: tabular-nums;">${formatPHP(defaultQty * defaultUnitCost)}</span>
            </div>
            <div>
                <button type="button" class="bom-item-delete-btn" onclick="removeBomIngredientRow('${rowId}')" title="Remove ingredient">
                    <i class="ph ph-trash"></i>
                </button>
            </div>
        `;

        bomIngredientRowsContainer.appendChild(rowEl);
        handleIngredientChange(rowId, false);
    };

    window.removeBomIngredientRow = function(rowId) {
        const el = document.getElementById(rowId);
        if (el) {
            el.remove();
            recalculateDrawerBomFinancials();
        }
    };

    window.handleIngredientChange = function(rowId, userChanged = false) {
        const row = document.getElementById(rowId);
        if (!row) return;
        const sel = row.querySelector('.ing-product-select');
        const unitBadge = row.querySelector('.ing-unit-badge');
        const costInput = row.querySelector('.ing-cost-input');
        const opt = sel ? sel.selectedOptions[0] : null;
        if (opt) {
            if (unitBadge) {
                unitBadge.textContent = opt.getAttribute('data-unit') || 'pc';
            }
            if (costInput && userChanged) {
                costInput.value = parseFloat(opt.getAttribute('data-cost')) || 0;
            }
        }
        recalculateDrawerBomFinancials();
    };

    // Live Financial Radar Recalculation
    window.recalculateDrawerBomFinancials = function() {
        const rows = bomIngredientRowsContainer.querySelectorAll('.bom-builder-item-row');
        let totalRawCost = 0;

        rows.forEach(row => {
            const qtyInput = row.querySelector('.ing-qty-input');
            const costInput = row.querySelector('.ing-cost-input');
            const subtotalEl = row.querySelector('.ing-subtotal-val');

            const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
            const unitCost = parseFloat(costInput ? costInput.value : 0) || 0;
            const extCost = qty * unitCost;

            if (subtotalEl) {
                subtotalEl.textContent = formatPHP(extCost);
            }
            totalRawCost += extCost;
        });

        const yieldVal = Math.max(0.0001, parseFloat(bomBatchYield.value) || 1);
        const unitRecipeCost = totalRawCost / yieldVal;

        // Parse selling price safely without NaN spikes
        const rawSelling = (bomSellingPrice.value !== undefined && bomSellingPrice.value !== null) ? String(bomSellingPrice.value).trim() : '';
        const sellingPrice = (rawSelling === '') ? 0 : (parseFloat(rawSelling) || 0);

        const profit = sellingPrice - unitRecipeCost;

        // Stable Margin % calculation
        let marginPct = 0;
        if (sellingPrice > 0) {
            marginPct = (profit / sellingPrice) * 100;
        } else if (unitRecipeCost > 0) {
            marginPct = -100;
        } else {
            marginPct = 0;
        }

        const totalCostEl = document.getElementById('radarTotalCost');
        const grossProfitEl = document.getElementById('radarGrossProfit');
        const profitMarginEl = document.getElementById('radarProfitMargin');
        const banner = document.getElementById('radarBanner');
        const bannerIcon = document.getElementById('radarBannerIcon');
        const bannerText = document.getElementById('radarBannerText');
        const healthTag = document.getElementById('radarHealthTag');

        if (totalCostEl) totalCostEl.textContent = formatPHP(unitRecipeCost);
        if (grossProfitEl) grossProfitEl.textContent = (profit >= 0 ? '+' : '') + formatPHP(profit);
        if (profitMarginEl) profitMarginEl.textContent = `${marginPct >= 0 ? '+' : ''}${marginPct.toFixed(1)}%`;

        if (rawSelling === '' || sellingPrice === 0) {
            // Neutral state while typing or empty
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-text-muted)';
            if (profitMarginEl) {
                profitMarginEl.style.color = 'var(--bom-text-muted)';
                profitMarginEl.textContent = '0.0%';
            }
            if (healthTag) {
                healthTag.className = 'bom-profit-pill';
                healthTag.style.background = '#e2e8f0';
                healthTag.style.color = '#475569';
                healthTag.textContent = 'Enter Price';
            }
            if (banner) banner.className = 'bom-radar-banner is-neutral';
            if (bannerIcon) bannerIcon.className = 'ph ph-info';
            if (bannerText) bannerText.textContent = `Set Selling Price (PHP) above. Total raw recipe cost is ${formatPHP(unitRecipeCost)} per unit.`;
        } else if (profit >= 0 && marginPct >= 20) {
            // Gaining Profit
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-success)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-success)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill profitable';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'Gaining Profit';
            }
            if (banner) banner.className = 'bom-radar-banner is-profit';
            if (bannerIcon) bannerIcon.className = 'ph ph-trend-up';
            if (bannerText) bannerText.textContent = `Gaining Profit: You earn ${formatPHP(profit)} (${marginPct.toFixed(1)}%) per unit sold!`;
        } else if (profit >= 0 && marginPct < 20) {
            // Low Margin
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-warning)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-warning)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill low-margin';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'Low Margin';
            }
            if (banner) banner.className = 'bom-radar-banner is-warning';
            if (bannerIcon) bannerIcon.className = 'ph ph-warning';
            if (bannerText) bannerText.textContent = `Low Margin Warning: Profit is only ${formatPHP(profit)} (${marginPct.toFixed(1)}%). Review component costs or pricing.`;
        } else {
            // Deficit
            if (grossProfitEl) grossProfitEl.style.color = 'var(--bom-danger)';
            if (profitMarginEl) profitMarginEl.style.color = 'var(--bom-danger)';
            if (healthTag) {
                healthTag.className = 'bom-profit-pill unprofitable';
                healthTag.style.background = '';
                healthTag.style.color = '';
                healthTag.textContent = 'At Deficit';
            }
            if (banner) banner.className = 'bom-radar-banner is-deficit';
            if (bannerIcon) bannerIcon.className = 'ph ph-warning-circle';
            if (bannerText) bannerText.textContent = `Deficit Warning: Raw material cost (${formatPHP(unitRecipeCost)}) exceeds selling price (${formatPHP(sellingPrice)}) by ${formatPHP(Math.abs(profit))}!`;
        }
    };

    // =========================================================================
    // SAVE BOM & COMMITTED ITEM MASTER MUTATION
    // =========================================================================
    window.handleSaveBom = function(e) {
        e.preventDefault();

        const rows = bomIngredientRowsContainer.querySelectorAll('.bom-builder-item-row');
        if (rows.length === 0) {
            alert('Please add at least one raw material / ingredient to the recipe.');
            return;
        }

        const ingredients = [];
        let totalRawCost = 0;

        rows.forEach(row => {
            const sel = row.querySelector('.ing-product-select');
            const qtyInput = row.querySelector('.ing-qty-input');
            const costInput = row.querySelector('.ing-cost-input');
            const opt = sel ? sel.selectedOptions[0] : null;
            if (opt && qtyInput) {
                const pId = Number(opt.value);
                const p = window.AppStore.products.find(item => item.id === pId);
                const qty = parseFloat(qtyInput.value) || 1;
                const unitCost = parseFloat(costInput ? costInput.value : opt.getAttribute('data-cost')) || 0;
                const unit = opt.getAttribute('data-unit') || 'pc';

                ingredients.push({
                    rawSku: opt.getAttribute('data-sku') || (p ? p.sku : 'RAW'),
                    name: p ? p.name : (opt.text.split('(')[0].trim() || 'Ingredient'),
                    qty: qty,
                    unit: unit,
                    unitCost: unitCost
                });
                totalRawCost += (qty * unitCost);
            }
        });

        const yieldVal = Math.max(0.0001, parseFloat(bomBatchYield.value) || 1);
        const unitBomCost = totalRawCost / yieldVal;
        const sellingPrice = parseFloat(bomSellingPrice.value) || 0;
        const trackPhysicalStock = document.getElementById('bomTrackPhysicalStock').checked;
        const explodeBomOnSale = document.getElementById('bomExplodeBomOnSale').checked;

        let targetProduct = null;

        const currentUser = {
            name: 'John Abiguero',
            email: 'jabiguero@dorothydiaz.internal',
            role: 'Lead Inventory Admin'
        };

        if (currentDrawerMode === 'new_product') {
            // Create New Product directly into Item Master
            const newSku = newProdSku.value.trim() || ('PRD-' + Math.floor(1000 + Math.random() * 9000));
            const newName = newProdName.value.trim();
            if (!newName) {
                alert('Please enter a product name.');
                return;
            }

            targetProduct = {
                id: Date.now(),
                sku: newSku,
                name: newName,
                category: newProdCategory.value || 'Beverages',
                subcategory: 'House Recipe',
                unit: newProdUom.value || 'Piece',
                packSize: newProdPackSize.value.trim() || '1 serving',
                costPrice: unitBomCost,
                sellingPrice: sellingPrice,
                taxRate: '12% VAT',
                supplier: 'In-House Production (BOM)',
                allergens: [],
                isActive: true,
                trackPhysicalStock: trackPhysicalStock,
                trackStock: trackPhysicalStock,
                explodeBomOnSale: explodeBomOnSale,
                hasBom: explodeBomOnSale,
                canBeSold: true,
                canBePurchased: false,
                reorderPoint: 10,
                targetStock: 50,
                branches: [
                    { name: 'Power Mac Center - Main HQ', onHand: 0, reserved: 0, reorder: 10 }
                ],
                ledger: [
                    { ref: 'BOM-INIT', type: 'Recipe Created', qty: '+0', balance: 0 }
                ]
            };

            const initialLog = createAuditEntry(currentUser, 'CREATED', `Initial BOM recipe and catalog registration for "${targetProduct.name}"`, [
                { field: 'Product Name', oldValue: '—', newValue: targetProduct.name },
                { field: 'SKU Code', oldValue: '—', newValue: targetProduct.sku },
                { field: 'Category', oldValue: '—', newValue: targetProduct.category },
                { field: 'Batch Yield', oldValue: '—', newValue: `${yieldVal} ${targetProduct.unit}` },
                { field: 'Calculated Unit Cost', oldValue: '—', newValue: formatPHP(unitBomCost) },
                { field: 'Selling Price', oldValue: '—', newValue: formatPHP(sellingPrice) },
                { field: 'Track Physical Stock', oldValue: '—', newValue: targetProduct.trackPhysicalStock ? 'Yes (Counted in Stocks Overview)' : 'No (Non-Physical / Service)' },
                { field: 'Explode Recipe on Sale', oldValue: '—', newValue: targetProduct.explodeBomOnSale ? 'Yes (Deducts Sub-ingredients)' : 'No (Deducts Item Directly)' },
                { field: 'Recipe Ingredients', oldValue: '—', newValue: `${ingredients.length} items (${ingredients.map(i => i.name).join(', ')})` }
            ]);
            targetProduct.createdAt = initialLog.formattedDate;
            targetProduct.createdBy = `${currentUser.name} (${currentUser.role})`;
            targetProduct.auditLogs = [initialLog];

            window.AppStore.products.unshift(targetProduct);
            showToast(`Product "${targetProduct.name}" and BOM created!`);
        } else {
            // Update Existing Product
            const pId = Number(bomProductId.value || selectExistingItem.value);
            targetProduct = window.AppStore.products.find(p => p.id === pId);
            if (targetProduct) {
                const existingBom = window.AppStore.boms.find(b => b.productId === targetProduct.id);
                const changes = [];
                const oldPrice = Number(targetProduct.sellingPrice || 0);
                const oldCost = Number(targetProduct.costPrice || 0);
                const oldYield = existingBom ? Number(existingBom.yield || 1) : 1;
                const oldIngredients = (existingBom && existingBom.ingredients) ? existingBom.ingredients : [];

                if (Math.abs(oldPrice - sellingPrice) > 0.009) {
                    changes.push({ field: 'Selling Price', oldValue: formatPHP(oldPrice), newValue: formatPHP(sellingPrice) });
                }
                if (Math.abs(oldCost - unitBomCost) > 0.009) {
                    changes.push({ field: 'Calculated Unit Cost', oldValue: formatPHP(oldCost), newValue: formatPHP(unitBomCost) });
                }
                if (Math.abs(oldYield - yieldVal) > 0.009) {
                    changes.push({ field: 'Batch Yield', oldValue: `${oldYield} ${targetProduct.unit || 'unit(s)'}`, newValue: `${yieldVal} ${targetProduct.unit || 'unit(s)'}` });
                }
                if ((targetProduct.trackPhysicalStock ?? targetProduct.trackStock) !== trackPhysicalStock) {
                    changes.push({
                        field: 'Track Physical Stock',
                        oldValue: (targetProduct.trackPhysicalStock ?? targetProduct.trackStock) ? 'Yes' : 'No',
                        newValue: trackPhysicalStock ? 'Yes' : 'No'
                    });
                }
                if ((targetProduct.explodeBomOnSale ?? targetProduct.hasBom) !== explodeBomOnSale) {
                    changes.push({
                        field: 'Explode Recipe on Sale',
                        oldValue: (targetProduct.explodeBomOnSale ?? targetProduct.hasBom) ? 'Yes' : 'No',
                        newValue: explodeBomOnSale ? 'Yes' : 'No'
                    });
                }
                
                const oldIngNames = oldIngredients.map(i => `${i.name} (${i.qty} ${i.unit})`).join('; ');
                const newIngNames = ingredients.map(i => `${i.name} (${i.qty} ${i.unit})`).join('; ');
                if (oldIngNames !== newIngNames) {
                    changes.push({
                        field: 'Recipe Ingredients',
                        oldValue: oldIngNames || 'None (Unconfigured)',
                        newValue: newIngNames
                    });
                }

                if (changes.length > 0) {
                    let actionType = 'BOM_MODIFIED';
                    if (changes.some(c => c.field === 'Selling Price')) actionType = 'PRICE_CHANGE';
                    const log = createAuditEntry(currentUser, actionType, `Updated recipe BOM (${changes.length} propert${changes.length === 1 ? 'y' : 'ies'} altered)`, changes);
                    targetProduct.auditLogs = [log, ...(targetProduct.auditLogs || [])];
                }

                targetProduct.trackPhysicalStock = trackPhysicalStock;
                targetProduct.trackStock = trackPhysicalStock;
                targetProduct.explodeBomOnSale = explodeBomOnSale;
                targetProduct.hasBom = explodeBomOnSale;
                targetProduct.canBeSold = true;
                targetProduct.costPrice = unitBomCost;
                targetProduct.sellingPrice = sellingPrice;
            }
            showToast(`BOM recipe for "${targetProduct ? targetProduct.name : 'product'}" saved!`);
        }

        // Commit BOM record
        const bomPayload = {
            productId: targetProduct.id,
            yield: yieldVal,
            ingredients: ingredients
        };

        const existingBomIdx = window.AppStore.boms.findIndex(b => b.productId === targetProduct.id);
        if (existingBomIdx !== -1) {
            window.AppStore.boms[existingBomIdx] = bomPayload;
        } else {
            window.AppStore.boms.unshift(bomPayload);
        }

        // Persist to localStorage for Item Master and BOM reactivity
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.setItem('rms_boms', JSON.stringify(window.AppStore.boms));
        localStorage.removeItem('rms_stocks_ledger');

        // Re-render
        window.AppStore.expandedProductIds.add(targetProduct.id);
        renderBomView();
        closeBomDrawer();
    };

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && bomDrawer.classList.contains('is-open')) {
            closeBomDrawer();
        }
        if (e.key === 'F2') {
            e.preventDefault();
            openBomDrawer('new');
        }
    });

    // Initialize View
    syncCategoryDropdowns();
    renderBomView();

})();
</script>
@endpush
@endsection

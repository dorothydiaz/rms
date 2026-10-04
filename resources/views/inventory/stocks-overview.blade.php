@extends('layouts.app')

@section('title', 'Stocks Overview - Inventory Operations')

@push('styles')
<style>
/* ==========================================================================
   STOCKS OVERVIEW DESIGN SYSTEM TOKENS & INDUSTRIAL STYLING
   RMS Glassmorphism & High-Density Modern Ergonomics (WCAG 2.1 AA)
   ========================================================================== */
:root {
    --stk-surface-canvas: #f8fafc;
    --stk-surface-card: rgba(255, 255, 255, 0.94);
    --stk-surface-hover: rgba(255, 255, 255, 0.99);
    --stk-glass-border: rgba(226, 232, 240, 0.9);
    --stk-glass-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.05), 0 4px 12px -2px rgba(15, 23, 42, 0.03);

    --stk-primary: #a855f7;
    --stk-primary-dark: #9333ea;
    --stk-primary-light: #f3e8ff;
    --stk-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --stk-primary-glow: rgba(168, 85, 247, 0.25);

    --stk-text-strong: #0f172a;
    --stk-text-medium: #334155;
    --stk-text-muted: #64748b;
    --stk-text-subtle: #94a3b8;

    --stk-border-subtle: #e2e8f0;
    --stk-border-focus: #c084fc;

    --stk-success: #10b981;
    --stk-success-dark: #059669;
    --stk-success-subtle: #ecfdf5;

    --stk-info: #3b82f6;
    --stk-info-dark: #2563eb;
    --stk-info-subtle: #eff6ff;

    --stk-warning: #f59e0b;
    --stk-warning-dark: #d97706;
    --stk-warning-subtle: #fffbeb;

    --stk-danger: #ef4444;
    --stk-danger-dark: #dc2626;
    --stk-danger-subtle: #fef2f2;

    --stk-drawer-width: min(620px, 94vw);
}

/* Page Layout Container */
.stk-page-container {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding: 24px 28px;
    background-color: var(--stk-surface-canvas);
    min-height: calc(100vh - 72px);
    box-sizing: border-box;
    font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* Header Section */
.stk-header-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    padding: 18px 24px;
    background: var(--stk-surface-card);
    border: 1px solid var(--stk-glass-border);
    border-radius: 16px;
    box-shadow: var(--stk-glass-shadow);
    backdrop-filter: blur(12px);
}

.stk-header-title-box {
    display: flex;
    align-items: center;
    gap: 14px;
}

.stk-header-icon {
    width: 48px;
    height: 48px;
    border-radius: 14px;
    background: var(--stk-primary-gradient);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    box-shadow: 0 8px 18px var(--stk-primary-glow);
    flex-shrink: 0;
}

.stk-header-title-box h1 {
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--stk-text-strong);
    margin: 0;
    letter-spacing: -0.01em;
}

.stk-header-title-box p {
    font-size: 0.8125rem;
    color: var(--stk-text-muted);
    margin: 3px 0 0 0;
    line-height: 1.4;
}

.stk-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Action Buttons */
.stk-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 18px;
    background: var(--stk-primary-gradient);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    box-shadow: 0 4px 12px var(--stk-primary-glow);
    transition: all 0.2s ease;
}

.stk-btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 16px var(--stk-primary-glow);
}

.stk-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 8px 15px;
    background: #ffffff;
    color: var(--stk-text-medium);
    border: 1px solid var(--stk-border-subtle);
    border-radius: 10px;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
}

.stk-btn-secondary:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--stk-text-strong);
}

/* KPI Summary Metrics Grid */
.stk-metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
}

.stk-metric-card {
    background: var(--stk-surface-card);
    border: 1px solid var(--stk-glass-border);
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: var(--stk-glass-shadow);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stk-metric-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -5px rgba(15, 23, 42, 0.08);
}

.stk-metric-icon-wrap {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    flex-shrink: 0;
}

.stk-metric-icon-wrap.is-onhand {
    background: #ede9fe;
    color: var(--stk-primary-dark);
}

.stk-metric-icon-wrap.is-in {
    background: var(--stk-success-subtle);
    color: var(--stk-success-dark);
}

.stk-metric-icon-wrap.is-out {
    background: var(--stk-info-subtle);
    color: var(--stk-info-dark);
}

.stk-metric-icon-wrap.is-transit {
    background: #fef3c7;
    color: #b45309;
}

.stk-metric-icon-wrap.is-alert {
    background: var(--stk-danger-subtle);
    color: var(--stk-danger-dark);
}

.stk-metric-data {
    display: flex;
    flex-direction: column;
    gap: 2px;
    overflow: hidden;
}

.stk-metric-label {
    font-size: 0.69rem;
    font-weight: 600;
    color: var(--stk-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.stk-metric-value {
    font-size: 1.25rem;
    font-weight: 800;
    color: var(--stk-text-strong);
    line-height: 1.1;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.stk-metric-subtext {
    font-size: 0.72rem;
    color: var(--stk-text-muted);
    font-weight: 500;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ==========================================================================
   FILTER RIBBON & SINGLE DATE RANGE PICKER
   ========================================================================== */
.stk-filter-ribbon-card {
    background: var(--stk-surface-card);
    border: 1px solid var(--stk-glass-border);
    border-radius: 16px;
    padding: 16px 20px;
    box-shadow: var(--stk-glass-shadow);
    display: flex;
    flex-direction: column;
    gap: 14px;
    position: relative;
    z-index: 20;
}

.stk-filter-ribbon-row {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

/* Search Box */
.stk-search-box {
    position: relative;
    flex: 1 1 260px;
    min-width: 220px;
}

.stk-search-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 17px;
    color: var(--stk-text-subtle);
    pointer-events: none;
}

.stk-search-input {
    width: 100%;
    height: 42px;
    padding: 0 74px 0 40px;
    background: #ffffff;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 11px;
    font-size: 0.8125rem;
    color: var(--stk-text-strong);
    box-sizing: border-box;
    transition: all 0.2s ease;
}

.stk-search-input:focus {
    outline: none;
    border-color: var(--stk-primary);
    box-shadow: 0 0 0 3px var(--stk-primary-glow);
}

.stk-search-shortcut {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.68rem;
    color: var(--stk-text-subtle);
    background: #f1f5f9;
    padding: 2px 6px;
    border-radius: 5px;
    font-family: monospace;
    pointer-events: none;
}

/* --------------------------------------------------------------------------
   UNIFIED SINGLE DATE RANGE PICKER COMPONENT
   -------------------------------------------------------------------------- */
.stk-daterange-picker-wrapper {
    position: relative;
    display: inline-block;
}

.stk-daterange-trigger {
    display: flex;
    align-items: center;
    gap: 10px;
    height: 42px;
    padding: 0 14px;
    background: #ffffff;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 11px;
    cursor: pointer;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.stk-daterange-trigger:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.stk-daterange-trigger.is-active {
    border-color: var(--stk-primary);
    box-shadow: 0 0 0 3px var(--stk-primary-glow);
    background: #ffffff;
}

.stk-daterange-trigger-icon {
    font-size: 18px;
    color: var(--stk-primary);
    display: flex;
    align-items: center;
}

.stk-daterange-trigger-content {
    display: flex;
    flex-direction: column;
    text-align: left;
    line-height: 1.15;
}

.stk-daterange-tag {
    font-size: 0.62rem;
    font-weight: 700;
    color: var(--stk-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.stk-daterange-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--stk-text-strong);
    white-space: nowrap;
}

.stk-daterange-caret {
    font-size: 13px;
    color: var(--stk-text-muted);
    transition: transform 0.2s ease;
}

.stk-daterange-trigger.is-active .stk-daterange-caret {
    transform: rotate(180deg);
}

/* Popover Modal / Dropdown */
.stk-daterange-popover {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 480px;
    max-width: 95vw;
    background: #ffffff;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 14px;
    box-shadow: 0 16px 36px -4px rgba(15, 23, 42, 0.16), 0 4px 12px rgba(15, 23, 42, 0.06);
    z-index: 1000;
    display: none;
    flex-direction: column;
    animation: stkPopoverSlide 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}

@keyframes stkPopoverSlide {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.stk-daterange-popover.is-open {
    display: flex;
}

.stk-popover-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 14px 14px 0 0;
}

.stk-popover-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--stk-text-strong);
}

.stk-popover-close-btn {
    border: none;
    background: transparent;
    color: var(--stk-text-muted);
    font-size: 14px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 4px;
    border-radius: 6px;
}

.stk-popover-close-btn:hover {
    background: #e2e8f0;
    color: var(--stk-text-strong);
}

.stk-popover-body {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 0;
}

.stk-popover-presets {
    display: flex;
    flex-direction: column;
    gap: 2px;
    padding: 12px;
    background: #f8fafc;
    border-right: 1px solid #e2e8f0;
}

.stk-popover-col-header {
    font-size: 0.65rem;
    font-weight: 700;
    color: var(--stk-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    margin-bottom: 6px;
    padding: 0 4px;
}

.stk-popover-preset-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border: none;
    background: transparent;
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--stk-text-medium);
    cursor: pointer;
    text-align: left;
    transition: all 0.15s ease;
}

.stk-popover-preset-btn:hover {
    background: #ede9fe;
    color: var(--stk-primary-dark);
}

.stk-popover-preset-btn.active {
    background: var(--stk-primary);
    color: #ffffff;
    font-weight: 700;
}

.stk-preset-shortcut {
    font-size: 0.65rem;
    opacity: 0.75;
    font-family: monospace;
}

.stk-popover-custom {
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 14px;
}

.stk-dual-input-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stk-popover-field {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stk-popover-field label {
    font-size: 0.68rem;
    font-weight: 700;
    color: var(--stk-text-muted);
    text-transform: uppercase;
}

.stk-input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.stk-input-with-icon i {
    position: absolute;
    left: 10px;
    font-size: 14px;
    color: var(--stk-text-subtle);
    pointer-events: none;
}

.stk-popover-date-input {
    width: 100%;
    height: 38px;
    padding: 0 8px 0 32px;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--stk-text-strong);
    font-family: inherit;
    box-sizing: border-box;
}

.stk-popover-date-input:focus {
    outline: none;
    border-color: var(--stk-primary);
    box-shadow: 0 0 0 2px var(--stk-primary-glow);
}

.stk-dual-arrow {
    font-size: 16px;
    color: var(--stk-text-subtle);
    margin-top: 18px;
}

.stk-popover-footer-actions {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 4px;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
}

/* Select Dropdowns */
.stk-select-dropdown {
    height: 42px;
    padding: 0 32px 0 12px;
    background: #ffffff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 256 256'%3E%3Cpath fill='%2364748b' d='m213.66 101.66l-80 80a8 8 0 0 1-11.32 0l-80-80a8 8 0 0 1 11.32-11.32L128 164.69l74.34-74.35a8 8 0 0 1 11.32 11.32'/%3E%3C/svg%3E") no-repeat right 10px center;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 11px;
    font-size: 0.79rem;
    font-weight: 600;
    color: var(--stk-text-strong);
    appearance: none;
    cursor: pointer;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
}

.stk-select-dropdown:focus {
    border-color: var(--stk-primary);
    box-shadow: 0 0 0 3px var(--stk-primary-glow);
}

/* Active Chips & Summary Info */
.stk-active-chips-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 10px;
    padding-top: 12px;
    border-top: 1px solid #f1f5f9;
}

.stk-chips-list {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.stk-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 4px 10px;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    font-size: 0.71rem;
    color: var(--stk-text-medium);
    font-weight: 500;
}

.stk-chip strong {
    color: var(--stk-text-strong);
}

.stk-chip-remove {
    border: none;
    background: transparent;
    cursor: pointer;
    color: var(--stk-text-muted);
    font-size: 11px;
    display: flex;
    align-items: center;
    padding: 0;
}

.stk-chip-remove:hover {
    color: var(--stk-danger);
}

.stk-results-counter {
    font-size: 0.75rem;
    color: var(--stk-text-muted);
    font-weight: 500;
}

.stk-results-counter strong {
    color: var(--stk-text-strong);
}

/* ==========================================================================
   ADVANCED 10-COLUMN DATA TABLE DESIGN
   High-Contrast Visual Hierarchy & Spatial Placement
   ========================================================================== */
.stk-table-card {
    background: #ffffff;
    border: 1px solid var(--stk-glass-border);
    border-radius: 16px;
    box-shadow: var(--stk-glass-shadow);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.stk-table-responsive-wrapper {
    width: 100%;
    overflow-x: auto;
    position: relative;
    max-height: 65vh;
}

/* Sleek industrial custom scrollbar */
.stk-table-responsive-wrapper::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}
.stk-table-responsive-wrapper::-webkit-scrollbar-track {
    background: #f8fafc;
}
.stk-table-responsive-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.stk-table-responsive-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.stk-table {
    width: 100%;
    min-width: 980px;
    border-collapse: separate;
    border-spacing: 0;
    text-align: left;
    font-size: 0.8125rem;
    table-layout: auto;
}

/* Glassmorphic Sticky Header */
.stk-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
    background: rgba(248, 250, 252, 0.97);
    backdrop-filter: blur(8px);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
}

.stk-table th {
    padding: 10px 12px;
    border-bottom: 2px solid #cbd5e1;
    border-right: 1px solid #e2e8f0;
    font-weight: 700;
    color: var(--stk-text-strong);
    font-size: 0.70rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    vertical-align: middle;
    white-space: nowrap;
    background: transparent;
}

.stk-table th:last-child {
    border-right: none;
}

.stk-th-header-box {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.stk-th-title {
    font-size: 0.74rem;
    font-weight: 800;
    color: var(--stk-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}

.stk-th-sub {
    font-size: 0.65rem;
    font-weight: 500;
    color: var(--stk-text-muted);
    text-transform: none;
    letter-spacing: 0;
}

/* Table Body Rows & Micro-Interactions */
.stk-table tbody tr {
    cursor: pointer;
    transition: all 0.15s ease-in-out;
}

.stk-table tbody tr:nth-child(even) {
    background: #fbfcfe;
}

.stk-table tbody tr:hover {
    background: #faf5ff;
}

.stk-table tbody tr:hover td:first-child {
    box-shadow: inset 4px 0 0 var(--stk-primary);
}

.stk-table td {
    padding: 10px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
    border-right: 1px solid #f8fafc;
}

.stk-table td:last-child {
    border-right: none;
}

/* ==========================================================================
   ADVANCED TABLE CELL DESIGN ELEMENTS
   ========================================================================== */
.stk-cell-stack {
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.stk-val-tabular {
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.01em;
}

/* Col 1: Item / SKU with Icon Avatar */
.stk-item-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.stk-item-icon-box {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    flex-shrink: 0;
    background: #f1f5f9;
    color: var(--stk-text-medium);
    border: 1px solid #e2e8f0;
}

.stk-item-icon-box.cat-beverages {
    background: #ede9fe;
    color: #6d28d9;
    border-color: #ddd6fe;
}

.stk-item-icon-box.cat-main-course {
    background: #fef3c7;
    color: #b45309;
    border-color: #fde68a;
}

.stk-item-icon-box.cat-pastries-desserts {
    background: #fce7f3;
    color: #be185d;
    border-color: #fbcfe8;
}

.stk-item-icon-box.cat-raw-ingredients {
    background: #ecfdf5;
    color: #047857;
    border-color: #a7f3d0;
}

.stk-item-icon-box.cat-packaging-disposables {
    background: #eff6ff;
    color: #1d4ed8;
    border-color: #bfdbfe;
}

.stk-item-icon-box.cat-syrups-flavors {
    background: #fff7ed;
    color: #c2410c;
    border-color: #ffedd5;
}

.stk-sku-tag-wrap {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 1px;
}

.stk-sku-pill {
    font-family: monospace;
    font-size: 0.68rem;
    font-weight: 700;
    background: #f1f5f9;
    color: #475569;
    padding: 1px 5px;
    border-radius: 4px;
    border: 1px solid #e2e8f0;
}

.stk-barcode-sub {
    font-size: 0.67rem;
    color: var(--stk-text-subtle);
    font-family: monospace;
}

/* Col 2: Category Badge Pill */
.stk-category-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 9px;
    border-radius: 8px;
    font-size: 0.72rem;
    font-weight: 700;
    width: fit-content;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    color: var(--stk-text-strong);
}

.stk-category-pill.cat-beverages {
    background: #faf5ff;
    color: #7e22ce;
    border-color: #e9d5ff;
}

.stk-category-pill.cat-main-course {
    background: #fffbeb;
    color: #b45309;
    border-color: #fde68a;
}

.stk-category-pill.cat-pastries-desserts {
    background: #fdf2f8;
    color: #be185d;
    border-color: #fbcfe8;
}

.stk-category-pill.cat-raw-ingredients {
    background: #f0fdf4;
    color: #15803d;
    border-color: #bbf7d0;
}

.stk-category-pill.cat-packaging-disposables {
    background: #eff6ff;
    color: #1e40af;
    border-color: #bfdbfe;
}

.stk-category-pill.cat-syrups-flavors {
    background: #fff7ed;
    color: #c2410c;
    border-color: #fed7aa;
}

.stk-subcat-bullet {
    color: var(--stk-primary);
    font-weight: 700;
}

/* Col 3: Beginning UOM Pill */
.stk-uom-pill {
    font-size: 0.69rem;
    color: var(--stk-text-muted);
    font-weight: 500;
}

/* Col 4, 5, 6, 7: Color-Coded Movement Badge Cards */
.stk-badge-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 6px;
    font-size: 0.8125rem;
    font-weight: 800;
    width: fit-content;
    font-variant-numeric: tabular-nums;
}

.stk-badge-pill.pill-inflow {
    background: #dcfce7;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.stk-badge-pill.pill-outflow {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}

.stk-badge-pill.pill-waste {
    background: #fee2e2;
    color: #b91c1c;
    border: 1px solid #fecaca;
}

.stk-badge-pill.pill-neutral {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

/* Col 8: Physical On-Hand with Glowing Status Pill */
.stk-onhand-row {
    display: flex;
    align-items: center;
    gap: 8px;
}

.stk-onhand-number {
    font-size: 0.90rem;
    font-weight: 800;
    color: var(--stk-text-strong);
    font-variant-numeric: tabular-nums;
}

.stk-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 2px 7px;
    border-radius: 12px;
    font-size: 0.65rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.02em;
}

.stk-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
}

.stk-status-pill.is-healthy {
    background: #dcfce7;
    color: #15803d;
}
.stk-status-pill.is-healthy .stk-status-dot {
    background: #16a34a;
    box-shadow: 0 0 6px #16a34a;
}

.stk-status-pill.is-low {
    background: #fef3c7;
    color: #b45309;
}
.stk-status-pill.is-low .stk-status-dot {
    background: #d97706;
    box-shadow: 0 0 6px #d97706;
}

.stk-status-pill.is-out {
    background: #fee2e2;
    color: #b91c1c;
}
.stk-status-pill.is-out .stk-status-dot {
    background: #dc2626;
    box-shadow: 0 0 6px #dc2626;
}

/* Col 9: In-Transit Pipeline Dual Capsules (Both in 1 Column) */
.stk-pipeline-capsule-wrap {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.stk-pipe-capsule {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 6px;
    width: fit-content;
    font-variant-numeric: tabular-nums;
}

.stk-pipe-capsule.in {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
}

.stk-pipe-capsule.out {
    background: #fdf2f8;
    color: #be185d;
    border: 1px solid #fbcfe8;
}

.stk-pipe-capsule.none {
    color: var(--stk-text-subtle);
    font-weight: 500;
    padding: 2px 4px;
}

/* Col 10: Projected Total Highlighted Cell */
.stk-projected-cell {
    background: linear-gradient(135deg, rgba(250, 245, 255, 0.6) 0%, rgba(243, 232, 255, 0.4) 100%);
    border-left: 1px dashed #d8b4fe !important;
}

.stk-projected-number {
    font-size: 0.95rem;
    font-weight: 800;
    color: var(--stk-primary-dark);
    font-variant-numeric: tabular-nums;
}

.stk-projected-number small {
    font-size: 0.70rem;
    font-weight: 600;
    color: #7c3aed;
}

/* Table Footer Summary Totals - Light-Themed Industrial Elegance */
.stk-table tfoot {
    position: sticky;
    bottom: 0;
    z-index: 10;
    background: #f8fafc;
    box-shadow: 0 -4px 14px rgba(15, 23, 42, 0.05);
}

.stk-table tfoot td {
    padding: 10px 12px;
    font-size: 0.80rem;
    font-weight: 700;
    color: var(--stk-text-strong);
    background: #f8fafc;
    border-top: 2px solid #cbd5e1;
    border-bottom: none;
    border-right: 1px solid #e2e8f0;
}

.stk-table tfoot td:last-child {
    border-right: none;
}

.stk-tfoot-title {
    font-size: 0.8125rem;
    font-weight: 800;
    color: var(--stk-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}

.stk-tfoot-sub {
    font-size: 0.68rem;
    color: var(--stk-text-muted);
    font-weight: 500;
}

/* Table Bottom Controls (Pagination & Page Size) */
.stk-table-footer-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    padding: 14px 20px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
}

.stk-pagination-group {
    display: flex;
    align-items: center;
    gap: 6px;
}

.stk-page-btn {
    min-width: 32px;
    height: 32px;
    padding: 0 6px;
    border: 1px solid var(--stk-border-subtle);
    background: #ffffff;
    color: var(--stk-text-medium);
    border-radius: 8px;
    font-size: 0.76rem;
    font-weight: 600;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s ease;
}

.stk-page-btn:hover:not(:disabled) {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: var(--stk-text-strong);
}

.stk-page-btn.active {
    background: var(--stk-primary-gradient);
    border-color: transparent;
    color: #ffffff;
    box-shadow: 0 2px 8px var(--stk-primary-glow);
}

.stk-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

/* ==========================================================================
   SLIDE-OUT ITEM DETAIL DRAWER (AUDIT & BREAKDOWN)
   ========================================================================== */
.stk-drawer-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.4);
    backdrop-filter: blur(4px);
    z-index: 998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.25s ease;
}

.stk-drawer-backdrop.is-open {
    opacity: 1;
    visibility: visible;
}

.stk-drawer-container {
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    width: var(--stk-drawer-width);
    background: #ffffff;
    box-shadow: -10px 0 35px rgba(0, 0, 0, 0.15);
    z-index: 999;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.3s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}

.stk-drawer-container.is-open {
    transform: translateX(0);
}

.stk-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid var(--stk-border-subtle);
    background: #f8fafc;
}

.stk-drawer-title-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.stk-drawer-icon {
    width: 42px;
    height: 42px;
    border-radius: 11px;
    background: var(--stk-primary-gradient);
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
}

.stk-drawer-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--stk-text-strong);
    margin: 0;
}

.stk-drawer-subtitle {
    font-size: 0.75rem;
    color: var(--stk-text-muted);
    margin: 2px 0 0 0;
}

.stk-drawer-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--stk-border-subtle);
    background: #ffffff;
    color: var(--stk-text-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.15s ease;
}

.stk-drawer-close-btn:hover {
    background: #fee2e2;
    color: #dc2626;
    border-color: #fca5a5;
}

.stk-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    display: flex;
    flex-direction: column;
    gap: 18px;
}

/* Formula Mathematical Card */
.stk-formula-card {
    background: linear-gradient(135deg, #fdf4ff 0%, #fae8ff 100%);
    border: 1px solid #f0abfc;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.stk-formula-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--stk-primary-dark);
    text-transform: uppercase;
    letter-spacing: 0.04em;
    display: flex;
    align-items: center;
    gap: 6px;
}

.stk-formula-eq {
    font-family: monospace;
    font-size: 0.78rem;
    color: #581c87;
    background: rgba(255, 255, 255, 0.75);
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid #e9d5ff;
    line-height: 1.6;
}

/* Quick Action Operations Buttons in Drawer */
.stk-quick-actions-bar {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;
}

.stk-quick-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 4px;
    padding: 10px 6px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.71rem;
    font-weight: 600;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.stk-quick-btn.btn-stock-in {
    background: var(--stk-success-subtle);
    color: var(--stk-success-dark);
    border-color: #a7f3d0;
}

.stk-quick-btn.btn-stock-out {
    background: var(--stk-info-subtle);
    color: var(--stk-info-dark);
    border-color: #bfdbfe;
}

.stk-quick-btn.btn-waste {
    background: var(--stk-danger-subtle);
    color: var(--stk-danger-dark);
    border-color: #fecaca;
}

.stk-quick-btn.btn-adjust {
    background: var(--stk-warning-subtle);
    color: var(--stk-warning-dark);
    border-color: #fde68a;
}

.stk-quick-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

/* Movement History Timeline */
.stk-timeline-wrap {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.stk-timeline-item {
    background: #ffffff;
    border: 1px solid var(--stk-border-subtle);
    border-radius: 10px;
    padding: 12px 14px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    transition: border-color 0.15s ease;
}

.stk-timeline-item:hover {
    border-color: #cbd5e1;
}

/* Empty State */
.stk-empty-state {
    padding: 48px 24px;
    text-align: center;
    color: var(--stk-text-muted);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.stk-empty-state i {
    font-size: 40px;
    color: var(--stk-text-subtle);
}

/* Print Reporting Styles */
@media print {
    body {
        background: #ffffff !important;
    }
    .stk-header-actions,
    .stk-filter-ribbon-card,
    .stk-table-footer-controls,
    .stk-drawer-container,
    .stk-drawer-backdrop {
        display: none !important;
    }
    .stk-page-container {
        padding: 0 !important;
    }
    .stk-table {
        min-width: 100% !important;
    }
}
</style>
@endpush

@section('content')
<div class="stk-page-container">

    <!-- 1. Page Header Section -->
    <header class="stk-header-bar">
        <div class="stk-header-title-box">
            <div class="stk-header-icon">
                <i class="ph ph-squares-four"></i>
            </div>
            <div>
                <h1>Stocks Overview</h1>
                <p>Real-time inventory ledger tracking beginning balances, operational movements, cycle count audits, in-transit shipments, and projected availability.</p>
            </div>
        </div>
        <div class="stk-header-actions">
            <!-- Sync / Refresh -->
            <button type="button" class="stk-btn-secondary" onclick="refreshStocksData()" title="Recompute Live Inventory Movements">
                <i class="ph ph-arrows-clockwise"></i>
                <span>Refresh</span>
            </button>
            <!-- Print Report -->
            <button type="button" class="stk-btn-secondary" onclick="window.print()" title="Print Stocks Overview Report">
                <i class="ph ph-printer"></i>
                <span>Print</span>
            </button>
            <!-- Export CSV -->
            <button type="button" class="stk-btn-primary" onclick="exportStocksCSV()" title="Export 10-Column Ledger to CSV">
                <i class="ph ph-file-arrow-down"></i>
                <span>Export CSV</span>
            </button>
        </div>
    </header>

    <!-- 2. KPI Summary Bar (5 Stat Cards) -->
    <section class="stk-metrics-grid">
        <!-- Metric 1: Physical On-Hand -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-onhand">
                <i class="ph ph-package"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Physical On-Hand</span>
                <span class="stk-metric-value" id="kpiTotalOnHand">0 units</span>
                <span class="stk-metric-subtext" id="kpiOnHandValuation">Valuation: ₱0.00</span>
            </div>
        </div>

        <!-- Metric 2: Stock In Flow -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-in">
                <i class="ph ph-arrow-circle-down-right"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Period Inflow (+)</span>
                <span class="stk-metric-value" id="kpiTotalStockIn">+0 units</span>
                <span class="stk-metric-subtext" id="kpiStockInBreakdown">Purchases & Transfers In</span>
            </div>
        </div>

        <!-- Metric 3: Stock Out Flow -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-out">
                <i class="ph ph-arrow-circle-up-right"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Period Outflow (-)</span>
                <span class="stk-metric-value" id="kpiTotalStockOut">-0 units</span>
                <span class="stk-metric-subtext" id="kpiStockOutBreakdown">POS Sales & Recipe Usage</span>
            </div>
        </div>

        <!-- Metric 4: In-Transit Pipeline -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-transit">
                <i class="ph ph-truck"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">In-Transit Pipeline</span>
                <span class="stk-metric-value" id="kpiTotalInTransit">0 net</span>
                <span class="stk-metric-subtext" id="kpiInTransitBreakdown">Incoming vs Outgoing</span>
            </div>
        </div>

        <!-- Metric 5: Low Stock & Reorder Alerts -->
        <div class="stk-metric-card">
            <div class="stk-metric-icon-wrap is-alert">
                <i class="ph ph-warning-circle"></i>
            </div>
            <div class="stk-metric-data">
                <span class="stk-metric-label">Stock Status Alerts</span>
                <span class="stk-metric-value" id="kpiLowStockCount">0 Items</span>
                <span class="stk-metric-subtext" id="kpiLowStockSub">At or below reorder threshold</span>
            </div>
        </div>
    </section>

    <!-- 3. Redesigned Filter Ribbon with Single Date Range Picker -->
    <section class="stk-filter-ribbon-card">
        <div class="stk-filter-ribbon-row">
            <!-- Search Bar -->
            <div class="stk-search-box">
                <i class="ph ph-magnifying-glass stk-search-icon"></i>
                <input type="text" id="stkSearchInput" class="stk-search-input" placeholder="Search item name, SKU code, or barcode..." autocomplete="off">
                <span class="stk-search-shortcut">/</span>
            </div>

            <!-- Redesigned Unified Single Date Range Picker Component -->
            <div class="stk-daterange-picker-wrapper" id="stkDateRangePickerWrap">
                <button type="button" class="stk-daterange-trigger" id="stkDateRangeTrigger" onclick="toggleDateRangePopover(event)" aria-haspopup="true" aria-expanded="false" title="Click to modify operational date window">
                    <div class="stk-daterange-trigger-icon">
                        <i class="ph ph-calendar"></i>
                    </div>
                    <div class="stk-daterange-trigger-content">
                        <span class="stk-daterange-tag">Operational Period</span>
                        <span class="stk-daterange-label" id="stkDateRangeDisplay">This Month (Sep 01 - Sep 29, 2026)</span>
                    </div>
                    <i class="ph ph-caret-down stk-daterange-caret" id="stkDateRangeCaret"></i>
                </button>

                <!-- Popover Dropdown -->
                <div class="stk-daterange-popover" id="stkDateRangePopover">
                    <div class="stk-popover-header">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i class="ph ph-calendar-blank" style="color: var(--stk-primary); font-size: 16px;"></i>
                            <span class="stk-popover-title">Select Date Interval</span>
                        </div>
                        <button type="button" class="stk-popover-close-btn" onclick="closeDateRangePopover()" title="Close (Esc)">
                            <i class="ph ph-x"></i>
                        </button>
                    </div>
                    <div class="stk-popover-body">
                        <!-- Left: Presets List -->
                        <div class="stk-popover-presets">
                            <span class="stk-popover-col-header">Quick Presets</span>
                            <button type="button" class="stk-popover-preset-btn" data-preset="today" onclick="selectDatePreset('today')">
                                <span>Today</span>
                                <span class="stk-preset-shortcut">1D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="yesterday" onclick="selectDatePreset('yesterday')">
                                <span>Yesterday</span>
                                <span class="stk-preset-shortcut">Yday</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="last_7_days" onclick="selectDatePreset('last_7_days')">
                                <span>Last 7 Days</span>
                                <span class="stk-preset-shortcut">7D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn active" data-preset="this_month" onclick="selectDatePreset('this_month')">
                                <span>This Month</span>
                                <span class="stk-preset-shortcut">MTD</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="last_30_days" onclick="selectDatePreset('last_30_days')">
                                <span>Last 30 Days</span>
                                <span class="stk-preset-shortcut">30D</span>
                            </button>
                            <button type="button" class="stk-popover-preset-btn" data-preset="this_quarter" onclick="selectDatePreset('this_quarter')">
                                <span>This Quarter</span>
                                <span class="stk-preset-shortcut">Q3</span>
                            </button>
                        </div>

                        <!-- Right: Custom Date Range Form -->
                        <div class="stk-popover-custom">
                            <span class="stk-popover-col-header">Custom Range Interval</span>
                            <div class="stk-dual-input-row">
                                <div class="stk-popover-field">
                                    <label for="stkStartDate">Start Date</label>
                                    <div class="stk-input-with-icon">
                                        <i class="ph ph-calendar-plus"></i>
                                        <input type="date" id="stkStartDate" class="stk-popover-date-input" onchange="handleManualDateChange()">
                                    </div>
                                </div>
                                <div class="stk-dual-arrow">
                                    <i class="ph ph-arrow-right"></i>
                                </div>
                                <div class="stk-popover-field">
                                    <label for="stkEndDate">End Date</label>
                                    <div class="stk-input-with-icon">
                                        <i class="ph ph-calendar-check"></i>
                                        <input type="date" id="stkEndDate" class="stk-popover-date-input" onchange="handleManualDateChange()">
                                    </div>
                                </div>
                            </div>

                            <div class="stk-popover-footer-actions">
                                <button type="button" class="stk-btn-secondary" onclick="resetToThisMonth()" style="padding: 6px 12px; font-size: 0.74rem;">Reset to Month</button>
                                <button type="button" class="stk-btn-primary" onclick="applyCustomDateRange()" style="padding: 6px 16px; font-size: 0.74rem;">
                                    <i class="ph ph-check"></i> Apply Range
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Selector -->
            <select id="stkCategoryFilter" class="stk-select-dropdown" onchange="handleCategoryFilter(this.value)">
                <option value="ALL">All Categories</option>
            </select>

            <!-- Branch Selector -->
            <select id="stkBranchFilter" class="stk-select-dropdown" onchange="handleBranchFilter(this.value)">
                <option value="ALL">All Branches</option>
                <option value="Power Mac Center - Main HQ">Power Mac Center - Main HQ</option>
                <option value="Makati Greenbelt Kiosk">Makati Greenbelt Kiosk</option>
                <option value="BGC High Street Branch">BGC High Street Branch</option>
            </select>

            <!-- Stock Status Selector -->
            <select id="stkStatusFilter" class="stk-select-dropdown" onchange="handleStatusFilter(this.value)">
                <option value="ALL">All Stock Statuses</option>
                <option value="LOW_STOCK">Low Stock (Reorder Needed)</option>
                <option value="OUT_OF_STOCK">Out of Stock (Zero Units)</option>
                <option value="HEALTHY">Healthy Stock Levels</option>
                <option value="HAS_TRANSIT">Has Active In-Transit</option>
            </select>

            <!-- Reset Filters -->
            <button type="button" class="stk-btn-secondary" onclick="resetAllFilters()" title="Reset All Filters">
                <i class="ph ph-arrow-counter-clockwise"></i>
                <span>Reset</span>
            </button>
        </div>

        <!-- Filter Chips & Row Counter -->
        <div class="stk-active-chips-bar">
            <div class="stk-chips-list" id="activeFilterChips">
                <!-- Dynamically generated active filter tags -->
            </div>
            <div class="stk-results-counter">
                Showing <strong id="visibleRowCount">0</strong> of <strong id="totalRowCount">0</strong> items
            </div>
        </div>
    </section>

    <!-- 4. Advanced 10-Column Data Table -->
    <section class="stk-table-card">
        <div class="stk-table-responsive-wrapper">
            <table class="stk-table" id="stocksOverviewTable">
                <thead>
                    <tr>
                        <!-- Col 1: Item / SKU -->
                        <th style="width: 19%; min-width: 170px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-package" style="color: var(--stk-primary);"></i> Item / SKU</span>
                                <span class="stk-th-sub">Item Name &bull; SKU / Barcode</span>
                            </div>
                        </th>

                        <!-- Col 2: Category -->
                        <th style="width: 11%; min-width: 105px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-tag" style="color: #64748b;"></i> Category</span>
                                <span class="stk-th-sub">Category &bull; Sub-category</span>
                            </div>
                        </th>

                        <!-- Col 3: Beginning -->
                        <th style="width: 7%; min-width: 70px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-hourglass-high" style="color: #64748b;"></i> Beginning</span>
                                <span class="stk-th-sub">Starting Qty &bull; UOM</span>
                            </div>
                        </th>

                        <!-- Col 4: Stock In (+) -->
                        <th style="width: 9%; min-width: 85px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-success-dark);"><i class="ph ph-arrow-circle-down-right"></i> Stock In (+)</span>
                                <span class="stk-th-sub">Received &bull; Purchases / Transfers</span>
                            </div>
                        </th>

                        <!-- Col 5: Stock Out (-) -->
                        <th style="width: 9%; min-width: 85px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-info-dark);"><i class="ph ph-arrow-circle-up-right"></i> Stock Out (-)</span>
                                <span class="stk-th-sub">Sales &bull; POS / Recipe Usage</span>
                            </div>
                        </th>

                        <!-- Col 6: Waste / Loss (-) -->
                        <th style="width: 8%; min-width: 80px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-danger-dark);"><i class="ph ph-trash"></i> Waste / Loss (-)</span>
                                <span class="stk-th-sub">Spoilage &bull; Damaged / Expired</span>
                            </div>
                        </th>

                        <!-- Col 7: Adjustments (±) -->
                        <th style="width: 8%; min-width: 80px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-scales" style="color: #d97706;"></i> Adjustments (&plusmn;)</span>
                                <span class="stk-th-sub">Audit Count &bull; Variance</span>
                            </div>
                        </th>

                        <!-- Col 8: Physical On-Hand -->
                        <th style="width: 10%; min-width: 95px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-warehouse" style="color: var(--stk-text-strong);"></i> Physical On-Hand</span>
                                <span class="stk-th-sub">Ending Stock &bull; Shelf Stock</span>
                            </div>
                        </th>

                        <!-- Col 9: In-Transit Pipeline (Both in 1 column!) -->
                        <th style="width: 9%; min-width: 95px;">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title"><i class="ph ph-truck" style="color: #2563eb;"></i> In-Transit Pipeline</span>
                                <span class="stk-th-sub">Incoming (+) &bull; Outgoing (-)</span>
                            </div>
                        </th>

                        <!-- Col 10: Projected Total -->
                        <th style="width: 10%; min-width: 95px;" class="stk-projected-cell">
                            <div class="stk-th-header-box">
                                <span class="stk-th-title" style="color: var(--stk-primary-dark);"><i class="ph ph-sparkle"></i> Projected Total</span>
                                <span class="stk-th-sub">Available &bull; Net Expected</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody id="stocksTableTbody">
                    <!-- Populated dynamically via JS renderTable() -->
                </tbody>
                <tfoot id="stocksTableTfoot">
                    <tr>
                        <td colspan="2">
                            <div class="stk-tfoot-title"><i class="ph ph-chart-line-up" style="color: var(--stk-primary); font-size: 15px;"></i> Portfolio Grand Totals</div>
                            <div class="stk-tfoot-sub">Active Filtered Inventory Records</div>
                        </td>
                        <td id="footBegQty" class="stk-val-tabular" style="font-weight: 700; color: var(--stk-text-strong);">0</td>
                        <td id="footStockIn" class="stk-val-tabular" style="font-weight: 700; color: #15803d;">+0</td>
                        <td id="footStockOut" class="stk-val-tabular" style="font-weight: 700; color: #1d4ed8;">-0</td>
                        <td id="footWaste" class="stk-val-tabular" style="font-weight: 700; color: #b91c1c;">0</td>
                        <td id="footAdj" class="stk-val-tabular" style="font-weight: 700; color: #d97706;">0</td>
                        <td id="footOnHand" class="stk-val-tabular" style="font-weight: 800; color: var(--stk-text-strong);">0</td>
                        <td id="footInTransit" class="stk-val-tabular" style="font-weight: 700; color: #2563eb;">Net 0</td>
                        <td id="footProjected" class="stk-val-tabular" style="font-weight: 800; color: #7c3aed;">0</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Table Pagination and Page Size Controls -->
        <div class="stk-table-footer-controls">
            <div style="display: flex; align-items: center; gap: 10px;">
                <label for="stkPageSizeSelect" style="font-size: 0.75rem; color: var(--stk-text-muted); font-weight: 500;">Rows per page:</label>
                <select id="stkPageSizeSelect" class="stk-select-dropdown" style="height: 32px; padding: 0 28px 0 10px; font-size: 0.75rem;" onchange="handlePageSizeChange(this.value)">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="ALL">All Items</option>
                </select>
            </div>
            <div class="stk-pagination-group" id="stkPaginationGroup">
                <!-- Dynamically generated pagination buttons -->
            </div>
        </div>
    </section>

</div>

<!-- 5. Slide-Out Detail Drawer for Row Audit Breakdown -->
<div class="stk-drawer-backdrop" id="stkDrawerBackdrop" onclick="closeItemDetailDrawer()"></div>
<aside class="stk-drawer-container" id="stkDetailDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerItemTitle">
    <header class="stk-drawer-header">
        <div class="stk-drawer-title-box">
            <div class="stk-drawer-icon" id="drawerItemIcon">
                <i class="ph ph-package"></i>
            </div>
            <div>
                <h3 class="stk-drawer-title" id="drawerItemTitle">Item Stock Breakdown</h3>
                <p class="stk-drawer-subtitle" id="drawerItemSubtitle">SKU: --- &bull; Category: ---</p>
            </div>
        </div>
        <button type="button" class="stk-drawer-close-btn" onclick="closeItemDetailDrawer()" title="Close Drawer (Esc)">
            <i class="ph ph-x"></i>
        </button>
    </header>

    <div class="stk-drawer-body">
        
        <!-- Formula Decomposition Box -->
        <div class="stk-formula-card">
            <div class="stk-formula-title">
                <i class="ph ph-calculator"></i>
                <span>Inventory Ledger Identity</span>
            </div>
            <div class="stk-formula-eq" id="drawerFormulaEquation">
                Beg (0) + In (0) - Out (0) - Waste (0) ± Adj (0) = <strong>Physical On-Hand (0)</strong><br>
                On-Hand (0) + Incoming (0) - Outgoing (0) = <strong>Projected Total (0)</strong>
            </div>
        </div>

        <!-- Quick Operational Actions -->
        <div>
            <span style="font-size: 0.72rem; font-weight: 700; color: var(--stk-text-muted); text-transform: uppercase; letter-spacing: 0.04em;">Operational Quick Actions</span>
            <div class="stk-quick-actions-bar" style="margin-top: 8px;">
                <a href="{{ route('inventory.stock-in') }}" class="stk-quick-btn btn-stock-in" title="Receive New Stock In">
                    <i class="ph ph-arrow-down-left" style="font-size: 16px;"></i>
                    <span>Stock In</span>
                </a>
                <a href="{{ route('inventory.stock-out') }}" class="stk-quick-btn btn-stock-out" title="Dispatch / Transfer Out">
                    <i class="ph ph-arrow-up-right" style="font-size: 16px;"></i>
                    <span>Stock Out</span>
                </a>
                <a href="{{ route('inventory.waste-expiry') }}" class="stk-quick-btn btn-waste" title="Log Spoilage / Damage">
                    <i class="ph ph-trash" style="font-size: 16px;"></i>
                    <span>Log Waste</span>
                </a>
                <a href="{{ route('inventory.stock-adjustment') }}" class="stk-quick-btn btn-adjust" title="Cycle Count Adjustment">
                    <i class="ph ph-scales" style="font-size: 16px;"></i>
                    <span>Audit Count</span>
                </a>
            </div>
        </div>

        <!-- Detailed Ledger Breakdown -->
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <span style="font-size: 0.78rem; font-weight: 700; color: var(--stk-text-strong);">Period Movement History</span>
                <span id="drawerPeriodLabel" style="font-size: 0.70rem; color: var(--stk-text-muted); font-weight: 500;">This Month</span>
            </div>
            <div class="stk-timeline-wrap" id="drawerMovementList">
                <!-- Dynamically populated movement ledger items -->
            </div>
        </div>

    </div>
</aside>
@endsection

@push('scripts')
<script>
(function() {
    'use strict';

    // =========================================================================
    // DEFAULT DATA & FALLBACK REPOSITORY SEED
    // =========================================================================
    const DEFAULT_CATEGORIES = [
        'Beverages',
        'Food & Snacks',
        'Raw Ingredients',
        'Packaging & Disposables',
        'Syrups & Flavors',
        'Pastries & Desserts',
        'Main Course'
    ];

    const FALLBACK_PRODUCTS = [
        { id: 1, sku: 'BEV-001', barcode: '4800019283710', name: 'Signature Spanish Latte', category: 'Beverages', subcategory: 'Espresso Specialty', unit: 'Cup', packSize: '16 oz', costPrice: 45.00, sellingPrice: 150.00, reorderPoint: 20, targetStock: 80, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 2, sku: 'BEV-002', barcode: '4800028391028', name: 'Iced Americano Grande', category: 'Beverages', subcategory: 'Iced Coffee', unit: 'Cup', packSize: '20 oz', costPrice: 28.00, sellingPrice: 120.00, reorderPoint: 15, targetStock: 60, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 3, sku: 'BEV-003', barcode: '4800039281039', name: 'Matcha Green Tea Fusion', category: 'Beverages', subcategory: 'Tea Lattes', unit: 'Cup', packSize: '16 oz', costPrice: 52.00, sellingPrice: 175.00, reorderPoint: 15, targetStock: 50, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 4, sku: 'MNC-101', barcode: '4800049281048', name: 'Truffle Mushroom Pasta', category: 'Main Course', subcategory: 'Artisan Pastas', unit: 'Plate', packSize: '320g Plate', costPrice: 115.00, sellingPrice: 380.00, reorderPoint: 10, targetStock: 40, hasBom: true, explodeBomOnSale: true, trackPhysicalStock: true },
        { id: 7, sku: 'PST-202', barcode: '4800037190029', name: 'French Butter Croissant', category: 'Pastries & Desserts', subcategory: 'Viennoiserie', unit: 'Piece', packSize: '85g Piece', costPrice: 32.00, sellingPrice: 110.00, reorderPoint: 20, targetStock: 60, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 10, sku: 'RAW-COFFEE-01', barcode: '4800058291028', name: 'Espresso Roast Beans (1kg)', category: 'Raw Ingredients', subcategory: 'Coffee Beans', unit: 'Kg', packSize: '1000g Bag', costPrice: 650.00, sellingPrice: 0.00, reorderPoint: 10, targetStock: 50, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 11, sku: 'PKG-501', barcode: '4800067192039', name: 'Kraft Takeout Box (Medium)', category: 'Packaging & Disposables', subcategory: 'Takeout Containers', unit: 'Piece', packSize: 'Pack of 50', costPrice: 8.50, sellingPrice: 15.00, reorderPoint: 100, targetStock: 500, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true },
        { id: 12, sku: 'SYR-601', barcode: '4800078291039', name: 'Caramel Macchiato Artisan Syrup', category: 'Syrups & Flavors', subcategory: 'Flavor Syrups', unit: 'Bottle', packSize: '750ml Bottle', costPrice: 290.00, sellingPrice: 420.00, reorderPoint: 8, targetStock: 25, hasBom: false, explodeBomOnSale: false, trackPhysicalStock: true }
    ];

    // =========================================================================
    // STATE STORE INITIALIZATION
    // =========================================================================
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth();
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1);
    
    function formatDateInput(d) {
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    const CATEGORY_PALETTES = {
        purple: {
            label: 'Purple / Violet',
            bg: '#faf5ff',
            text: '#7e22ce',
            border: '#e9d5ff',
            dot: '#a855f7'
        },
        amber: {
            label: 'Amber / Gold',
            bg: '#fffbeb',
            text: '#b45309',
            border: '#fde68a',
            dot: '#f59e0b'
        },
        rose: {
            label: 'Rose / Pink',
            bg: '#fdf2f8',
            text: '#be185d',
            border: '#fbcfe8',
            dot: '#ec4899'
        },
        emerald: {
            label: 'Emerald / Green',
            bg: '#f0fdf4',
            text: '#15803d',
            border: '#bbf7d0',
            dot: '#10b981'
        },
        blue: {
            label: 'Ocean / Blue',
            bg: '#eff6ff',
            text: '#1d4ed8',
            border: '#bfdbfe',
            dot: '#3b82f6'
        },
        orange: {
            label: 'Tangerine / Orange',
            bg: '#fff7ed',
            text: '#c2410c',
            border: '#fed7aa',
            dot: '#f97316'
        },
        teal: {
            label: 'Teal / Cyan',
            bg: '#f0fdfa',
            text: '#0f766e',
            border: '#99f6e4',
            dot: '#14b8a6'
        },
        indigo: {
            label: 'Indigo / Navy',
            bg: '#eef2ff',
            text: '#4338ca',
            border: '#c7d2fe',
            dot: '#6366f1'
        },
        slate: {
            label: 'Slate / Neutral',
            bg: '#f8fafc',
            text: '#475569',
            border: '#cbd5e1',
            dot: '#64748b'
        }
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

    const serverProducts = @json($initialProducts ?? []);
    const serverCategories = @json($initialCategories ?? []);
    const serverLedger = @json($initialLedger ?? []);

    let initialProductsList = FALLBACK_PRODUCTS;
    if (serverProducts && serverProducts.length > 0) {
        initialProductsList = serverProducts.map(p => ({
            id: p.id,
            sku: p.sku,
            barcode: p.barcode || ('4800' + p.id),
            name: p.name,
            category: p.category,
            subcategory: p.category,
            unit: p.uom || 'Unit',
            packSize: '1 ' + (p.uom || 'Unit'),
            costPrice: parseFloat(p.cost_price || 0),
            sellingPrice: parseFloat(p.selling_price || 0),
            reorderPoint: parseFloat(p.min_stock || 10),
            targetStock: parseFloat(p.max_stock || 50),
            currentStock: parseFloat(p.current_stock || 0),
            current_stock: parseFloat(p.current_stock || 0),
            trackPhysicalStock: true,
            explodeBomOnSale: false
        }));
    } else {
        const stored = localStorage.getItem('rms_inventory_products');
        if (stored) {
            try { initialProductsList = JSON.parse(stored); } catch(e) {}
        }
    }

    let initialCategoriesList = DEFAULT_CATEGORIES;
    if (serverCategories && serverCategories.length > 0) {
        initialCategoriesList = serverCategories.map(c => c.name);
    } else {
        const storedCat = localStorage.getItem('rms_product_categories');
        if (storedCat) {
            try { initialCategoriesList = JSON.parse(storedCat); } catch(e) {}
        }
    }

    window.AppStore = {
        products: initialProductsList,
        categories: initialCategoriesList,
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        stocksLedger: null,
        serverLedger: serverLedger || [],
        
        // Filter States
        searchQuery: '',
        datePreset: 'this_month',
        startDate: formatDateInput(firstDayOfMonth),
        endDate: formatDateInput(now),
        category: 'ALL',
        branch: 'ALL',
        status: 'ALL',
        
        // Pagination
        currentPage: 1,
        pageSize: 25,
        selectedProductId: null
    };

    localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
    localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));

    // =========================================================================
    // MODULAR CATEGORY & ICON HELPERS
    // =========================================================================
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

    function getCatSlug(cat) {
        if (!cat) return 'general';
        return cat.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
    }

    function getItemIcon(cat) {
        return getCategoryMeta(cat).icon;
    }

    // =========================================================================
    // SYNTHETIC REALISTIC OPERATIONS MOVEMENTS GENERATOR
    // =========================================================================
    function getOrGenerateStocksData() {
        if (window.AppStore.stocksLedger && Array.isArray(window.AppStore.stocksLedger) && window.AppStore.stocksLedger.length > 0) {
            return window.AppStore.stocksLedger;
        }

        const ledgerItems = window.AppStore.serverLedger || [];

        const seeded = window.AppStore.products.map((p, idx) => {
            const seed = (p.id * 17 + idx * 3) % 100;
            const isHighVolume = p.category === 'Beverages' || p.category === 'Packaging & Disposables';
            
            // True live on-hand physical stock directly from Database InventoryItem model!
            const onHand = parseFloat(p.current_stock !== undefined ? p.current_stock : (p.currentStock !== undefined ? p.currentStock : (15 + (seed % 30))));
            const shelfStock = Math.floor(onHand * 0.7);
            const backroomStock = onHand - shelfStock;

            // Find real ledger records from Database StockLedger table for this SKU
            const matchingLedger = ledgerItems.filter(l => l.sku === p.sku);
            let movements = [];

            if (matchingLedger.length > 0) {
                movements = matchingLedger.map(l => {
                    const dStr = l.created_at ? String(l.created_at).slice(0, 10) : '';
                    const change = parseFloat(l.quantity_change) || 0;
                    let typeLabel = 'Stock Ledger Movement';
                    if (l.transaction_type === 'PURCHASE_RECEIPT') typeLabel = 'Stock In (Purchase Order)';
                    else if (l.transaction_type === 'DIRECT_RECEIVING') typeLabel = 'Stock In (Direct Receiving)';
                    else if (l.transaction_type === 'STOCK_IN') typeLabel = 'Stock In (Inbound)';
                    else if (l.transaction_type === 'STOCK_OUT') typeLabel = 'Stock Out (POS/Usage)';

                    return {
                        date: formatShortDate(dStr) || 'Recent',
                        ref: l.reference_no,
                        type: typeLabel,
                        qty: (change >= 0 ? `+${change}` : `${change}`),
                        balance: parseFloat(l.after_quantity) || onHand,
                        note: l.notes || `Ledger Voucher #${l.reference_no}`
                    };
                });
            } else {
                movements = [
                    { date: 'Recent', ref: 'BEG-BAL', type: 'Beginning Balance', qty: `+${onHand}`, balance: onHand, note: 'Opening verified inventory ledger' }
                ];
            }

            const totalReceivedFromLedger = matchingLedger.reduce((sum, l) => sum + (parseFloat(l.quantity_change) || 0), 0);

            return {
                id: p.id,
                sku: p.sku,
                barcode: p.barcode || ('4800' + Math.floor(100000000 + Math.random() * 900000000)),
                name: p.name,
                category: p.category,
                subcategory: p.subcategory || p.category || 'Standard Catalog',
                unit: p.unit || p.uom || 'Piece',
                packSize: p.packSize || ('1 ' + (p.unit || p.uom || 'pc')),
                costPrice: Number(p.costPrice || p.cost_price || 0),
                sellingPrice: Number(p.sellingPrice || p.selling_price || 0),
                reorderPoint: Number(p.reorderPoint || p.min_stock || 10),
                targetStock: Number(p.targetStock || p.max_stock || 50),
                trackPhysicalStock: p.trackPhysicalStock !== undefined ? p.trackPhysicalStock : true,
                explodeBomOnSale: p.explodeBomOnSale !== undefined ? p.explodeBomOnSale : false,
                
                begQty: onHand,
                stockIn: {
                    total: totalReceivedFromLedger > 0 ? totalReceivedFromLedger : (isHighVolume ? 40 : 15),
                    po: totalReceivedFromLedger > 0 ? totalReceivedFromLedger : (isHighVolume ? 30 : 10),
                    transfer: 0
                },
                stockOut: {
                    total: 0,
                    pos: 0,
                    recipe: 0
                },
                waste: {
                    total: 0,
                    damaged: 0,
                    expired: 0
                },
                adjustment: {
                    qty: 0,
                    reason: 'Physical Count Matched',
                    variancePct: '0.0%'
                },
                onHand: onHand,
                shelfStock: shelfStock,
                backroomStock: backroomStock,
                inTransit: {
                    incoming: 0,
                    outgoing: 0
                },
                projectedTotal: onHand,
                movements: movements
            };
        });

        window.AppStore.stocksLedger = seeded;
        localStorage.setItem('rms_stocks_ledger', JSON.stringify(seeded));
        return seeded;
    }

    // =========================================================================
    // FORMATTING HELPERS
    // =========================================================================
    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    function formatShortDate(dStr) {
        if (!dStr) return '';
        const parts = dStr.split('-');
        if (parts.length < 3) return dStr;
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const m = months[parseInt(parts[1], 10) - 1] || parts[1];
        return `${m} ${parts[2]}`;
    }

    function updateDateRangeDisplayLabel() {
        const displayEl = document.getElementById('stkDateRangeDisplay');
        if (!displayEl) return;

        const presetTitles = {
            today: 'Today',
            yesterday: 'Yesterday',
            last_7_days: 'Last 7 Days',
            this_month: 'This Month',
            last_30_days: 'Last 30 Days',
            this_quarter: 'This Quarter',
            custom: 'Custom Range'
        };

        const title = presetTitles[window.AppStore.datePreset] || 'Custom Range';
        const sFormatted = formatShortDate(window.AppStore.startDate);
        const eFormatted = formatShortDate(window.AppStore.endDate);

        displayEl.textContent = `${title} (${sFormatted} - ${eFormatted}, ${currentYear})`;
    }

    // =========================================================================
    // FILTERING ENGINE
    // =========================================================================
    function getFilteredStocks() {
        const all = getOrGenerateStocksData();
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const cat = window.AppStore.category;
        const status = window.AppStore.status;

        return all.filter(item => {
            // Must track physical stock to be shown in Stocks Overview for counting/receiving
            if (item.trackPhysicalStock === false || item.trackStock === false) {
                return false;
            }

            // Search Query
            if (query) {
                const matchName = item.name.toLowerCase().includes(query);
                const matchSku = item.sku.toLowerCase().includes(query);
                const matchBarcode = (item.barcode || '').toLowerCase().includes(query);
                const matchCat = item.category.toLowerCase().includes(query);
                if (!matchName && !matchSku && !matchBarcode && !matchCat) return false;
            }

            // Category Filter
            if (cat !== 'ALL' && item.category !== cat) {
                return false;
            }

            // Stock Status Filter
            if (status === 'LOW_STOCK') {
                if (item.onHand > item.reorderPoint || item.onHand <= 0) return false;
            } else if (status === 'OUT_OF_STOCK') {
                if (item.onHand > 0) return false;
            } else if (status === 'HEALTHY') {
                if (item.onHand <= item.reorderPoint) return false;
            } else if (status === 'HAS_TRANSIT') {
                if (item.inTransit.incoming === 0 && item.inTransit.outgoing === 0) return false;
            }

            return true;
        });
    }

    // =========================================================================
    // RENDERING: KPI METRICS BAR
    // =========================================================================
    function renderKpis(data) {
        let totalOnHand = 0;
        let totalValuation = 0;
        let totalIn = 0;
        let totalOut = 0;
        let totalIncTransit = 0;
        let totalOutTransit = 0;
        let lowStockCount = 0;

        data.forEach(item => {
            totalOnHand += item.onHand;
            totalValuation += (item.onHand * item.costPrice);
            totalIn += item.stockIn.total;
            totalOut += (item.stockOut.total + item.waste.total);
            totalIncTransit += item.inTransit.incoming;
            totalOutTransit += item.inTransit.outgoing;
            if (item.onHand <= item.reorderPoint) {
                lowStockCount++;
            }
        });

        const netTransit = totalIncTransit - totalOutTransit;

        document.getElementById('kpiTotalOnHand').textContent = `${totalOnHand.toLocaleString()} units`;
        document.getElementById('kpiOnHandValuation').textContent = `Valuation: ${formatPHP(totalValuation)}`;

        document.getElementById('kpiTotalStockIn').textContent = `+${totalIn.toLocaleString()} units`;
        document.getElementById('kpiTotalStockOut').textContent = `-${totalOut.toLocaleString()} units`;

        document.getElementById('kpiTotalInTransit').textContent = `${netTransit >= 0 ? '+' : ''}${netTransit.toLocaleString()} net`;
        document.getElementById('kpiInTransitBreakdown').textContent = `+${totalIncTransit} In • -${totalOutTransit} Out`;

        document.getElementById('kpiLowStockCount').textContent = `${lowStockCount} Items`;
        document.getElementById('kpiLowStockSub').textContent = lowStockCount > 0 ? 'Requires Purchase Order / Transfer' : 'All items above safety stock';
    }

    // =========================================================================
    // RENDERING: 10-COLUMN DATA TABLE WITH ADVANCED DESIGNS
    // =========================================================================
    function renderTable() {
        const filtered = getFilteredStocks();
        const tbody = document.getElementById('stocksTableTbody');
        const counterEl = document.getElementById('visibleRowCount');
        const totalEl = document.getElementById('totalRowCount');

        renderKpis(filtered);
        renderFilterChips(filtered.length);
        updateDateRangeDisplayLabel();

        const physicalCatalogCount = window.AppStore.products.filter(p => p.trackPhysicalStock !== false && p.trackStock !== false).length;
        if (counterEl) counterEl.textContent = filtered.length;
        if (totalEl) totalEl.textContent = physicalCatalogCount || window.AppStore.products.length;

        if (filtered.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="10">
                        <div class="stk-empty-state">
                            <i class="ph ph-magnifying-glass"></i>
                            <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--stk-text-strong);">No Inventory Items Found</h4>
                            <p style="margin: 0; font-size: 0.8rem; max-width: 380px;">No stocks match your active search or filter criteria. Try resetting the filters or modifying date intervals.</p>
                            <button type="button" class="stk-btn-secondary" onclick="resetAllFilters()" style="margin-top: 8px;">
                                <i class="ph ph-arrow-counter-clockwise"></i> Clear Filters
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            updateTableTotals([]);
            renderPagination(0);
            return;
        }

        const pageSize = window.AppStore.pageSize === 'ALL' ? filtered.length : Number(window.AppStore.pageSize);
        const totalPages = Math.ceil(filtered.length / pageSize) || 1;
        if (window.AppStore.currentPage > totalPages) {
            window.AppStore.currentPage = 1;
        }

        const startIndex = (window.AppStore.currentPage - 1) * pageSize;
        const pageItems = window.AppStore.pageSize === 'ALL' ? filtered : filtered.slice(startIndex, startIndex + pageSize);

        let rowsHtml = '';
        pageItems.forEach(item => {
            const catMeta = getCategoryMeta(item.category);
            const catPal = getCategoryPalette(catMeta.color);
            const iconGlyph = catMeta.icon || 'ph-tag';

            // Status tag logic
            let statusTagClass = 'is-healthy';
            let statusTagText = 'Healthy';
            if (item.onHand <= 0) {
                statusTagClass = 'is-out';
                statusTagText = 'Out of Stock';
            } else if (item.onHand <= item.reorderPoint) {
                statusTagClass = 'is-low';
                statusTagText = 'Low Stock';
            }

            // Adjustments formatting
            let adjSign = '';
            if (item.adjustment.qty > 0) adjSign = '+';

            // In-transit display (Both in 1 column!)
            const incText = item.inTransit.incoming > 0 
                ? `<span class="stk-pipe-capsule in"><i class="ph ph-arrow-down-left"></i> +${item.inTransit.incoming} Incoming</span>` 
                : `<span class="stk-pipe-capsule none">&mdash; No Incoming</span>`;
            
            const outText = item.inTransit.outgoing > 0 
                ? `<span class="stk-pipe-capsule out"><i class="ph ph-arrow-up-right"></i> -${item.inTransit.outgoing} Outgoing</span>` 
                : `<span class="stk-pipe-capsule none">&mdash; No Outgoing</span>`;

            const netTransit = item.inTransit.incoming - item.inTransit.outgoing;

            rowsHtml += `
                <tr onclick="openItemDetailDrawer(${item.id})">
                    <!-- Col 1: Item / SKU (Avatar Icon + Item Name + SKU Chip) -->
                    <td>
                        <div class="stk-item-cell">
                            <div class="stk-item-icon-box" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border};">
                                <i class="ph ${iconGlyph}"></i>
                            </div>
                            <div class="stk-cell-stack">
                                <span class="stk-primary-text font-bold" style="font-weight: 700;">${item.name}</span>
                                <div class="stk-sku-tag-wrap">
                                    <span class="stk-sku-pill">${item.sku}</span>
                                    <span class="stk-barcode-sub">${item.barcode}</span>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- Col 2: Category (Pill + Subcategory Breadcrumb) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-category-pill" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border}; display: inline-flex; align-items: center; gap: 5px;">
                                <i class="ph ${iconGlyph}" style="font-size: 11px;"></i>
                                <span>${item.category}</span>
                            </span>
                            <span class="stk-sub-text"><span class="stk-subcat-bullet">&rsaquo;</span> ${item.subcategory}</span>
                        </div>
                    </td>

                    <!-- Col 3: Beginning -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-primary-text stk-val-tabular font-bold">${item.begQty.toLocaleString()}</span>
                            <span class="stk-uom-pill">${item.unit} (${item.packSize})</span>
                        </div>
                    </td>

                    <!-- Col 4: Stock In (+) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill pill-inflow">+${item.stockIn.total.toLocaleString()}</span>
                            <span class="stk-sub-text">PO: ${item.stockIn.po} &bull; Trf: ${item.stockIn.transfer}</span>
                        </div>
                    </td>

                    <!-- Col 5: Stock Out (-) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill pill-outflow">-${item.stockOut.total.toLocaleString()}</span>
                            <span class="stk-sub-text">POS: ${item.stockOut.pos} &bull; Recipe: ${item.stockOut.recipe}</span>
                        </div>
                    </td>

                    <!-- Col 6: Waste / Loss (-) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill ${item.waste.total > 0 ? 'pill-waste' : 'pill-neutral'}">${item.waste.total > 0 ? '-' + item.waste.total.toLocaleString() : '0'}</span>
                            <span class="stk-sub-text">Dmg: ${item.waste.damaged} &bull; Exp: ${item.waste.expired}</span>
                        </div>
                    </td>

                    <!-- Col 7: Adjustments (±) -->
                    <td>
                        <div class="stk-cell-stack">
                            <span class="stk-badge-pill ${item.adjustment.qty > 0 ? 'pill-inflow' : (item.adjustment.qty < 0 ? 'pill-waste' : 'pill-neutral')}">${adjSign}${item.adjustment.qty.toLocaleString()}</span>
                            <span class="stk-sub-text">${item.adjustment.reason} &bull; ${item.adjustment.variancePct}</span>
                        </div>
                    </td>

                    <!-- Col 8: Physical On-Hand -->
                    <td>
                        <div class="stk-cell-stack">
                            <div class="stk-onhand-row">
                                <span class="stk-onhand-number">${item.onHand.toLocaleString()}</span>
                                <span class="stk-status-pill ${statusTagClass}">
                                    <span class="stk-status-dot"></span>
                                    <span>${statusTagText}</span>
                                </span>
                            </div>
                            <span class="stk-sub-text">Shelf: ${item.shelfStock} &bull; Back: ${item.backroomStock}</span>
                        </div>
                    </td>

                    <!-- Col 9: In-Transit Pipeline (Both in 1 column!) -->
                    <td>
                        <div class="stk-pipeline-capsule-wrap">
                            ${incText}
                            ${outText}
                        </div>
                    </td>

                    <!-- Col 10: Projected Total (Purple Highlighted Cell) -->
                    <td class="stk-projected-cell">
                        <div class="stk-cell-stack">
                            <span class="stk-projected-number">${item.projectedTotal.toLocaleString()} <small>${item.unit}</small></span>
                            <span class="stk-sub-text">On-Hand (${item.onHand}) ${netTransit >= 0 ? '+' : ''}${netTransit} Transit</span>
                        </div>
                    </td>
                </tr>
            `;
        });

        tbody.innerHTML = rowsHtml;
        updateTableTotals(filtered);
        renderPagination(filtered.length);
    }

    // =========================================================================
    // TABLE TOTALS SUMMARY (TFOOT)
    // =========================================================================
    function updateTableTotals(data) {
        let beg = 0, inQty = 0, outQty = 0, wasteQty = 0, adjQty = 0, onHand = 0, inTransitInc = 0, inTransitOut = 0, proj = 0;

        data.forEach(d => {
            beg += d.begQty;
            inQty += d.stockIn.total;
            outQty += d.stockOut.total;
            wasteQty += d.waste.total;
            adjQty += d.adjustment.qty;
            onHand += d.onHand;
            inTransitInc += d.inTransit.incoming;
            inTransitOut += d.inTransit.outgoing;
            proj += d.projectedTotal;
        });

        const netTransit = inTransitInc - inTransitOut;

        document.getElementById('footBegQty').textContent = beg.toLocaleString();
        document.getElementById('footStockIn').textContent = `+${inQty.toLocaleString()}`;
        document.getElementById('footStockOut').textContent = `-${outQty.toLocaleString()}`;
        document.getElementById('footWaste').textContent = wasteQty > 0 ? `-${wasteQty.toLocaleString()}` : '0';
        document.getElementById('footAdj').textContent = (adjQty > 0 ? `+${adjQty.toLocaleString()}` : adjQty.toLocaleString());
        document.getElementById('footOnHand').textContent = onHand.toLocaleString();
        document.getElementById('footInTransit').textContent = `${netTransit >= 0 ? '+' : ''}${netTransit.toLocaleString()} net`;
        document.getElementById('footProjected').textContent = proj.toLocaleString();
    }

    // =========================================================================
    // PAGINATION CONTROLS
    // =========================================================================
    function renderPagination(totalCount) {
        const group = document.getElementById('stkPaginationGroup');
        if (!group) return;

        if (window.AppStore.pageSize === 'ALL' || totalCount === 0) {
            group.innerHTML = '';
            return;
        }

        const pageSize = Number(window.AppStore.pageSize);
        const totalPages = Math.ceil(totalCount / pageSize);
        const current = window.AppStore.currentPage;

        let html = `
            <button type="button" class="stk-page-btn" onclick="goToPage(${current - 1})" ${current <= 1 ? 'disabled' : ''} title="Previous Page">
                <i class="ph ph-caret-left"></i>
            </button>
        `;

        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= current - 1 && i <= current + 1)) {
                html += `
                    <button type="button" class="stk-page-btn ${i === current ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>
                `;
            } else if (i === current - 2 || i === current + 2) {
                html += `<span style="padding: 0 4px; color: var(--stk-text-subtle);">&bull;&bull;&bull;</span>`;
            }
        }

        html += `
            <button type="button" class="stk-page-btn" onclick="goToPage(${current + 1})" ${current >= totalPages ? 'disabled' : ''} title="Next Page">
                <i class="ph ph-caret-right"></i>
            </button>
        `;

        group.innerHTML = html;
    }

    window.goToPage = function(p) {
        window.AppStore.currentPage = p;
        renderTable();
    };

    window.handlePageSizeChange = function(sz) {
        window.AppStore.pageSize = sz;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    // =========================================================================
    // ACTIVE FILTER CHIPS
    // =========================================================================
    function renderFilterChips(matchCount) {
        const container = document.getElementById('activeFilterChips');
        if (!container) return;

        let chips = [];

        // Date Period Chip
        chips.push(`
            <span class="stk-chip">
                <span>Period: <strong>${formatShortDate(window.AppStore.startDate)} &rarr; ${formatShortDate(window.AppStore.endDate)}, ${currentYear}</strong></span>
            </span>
        `);

        // Search Chip
        if (window.AppStore.searchQuery) {
            chips.push(`
                <span class="stk-chip">
                    <span>Search: <strong>"${window.AppStore.searchQuery}"</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="clearSearchFilter()"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Category Chip
        if (window.AppStore.category !== 'ALL') {
            chips.push(`
                <span class="stk-chip">
                    <span>Category: <strong>${window.AppStore.category}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleCategoryFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Branch Chip
        if (window.AppStore.branch !== 'ALL') {
            chips.push(`
                <span class="stk-chip">
                    <span>Branch: <strong>${window.AppStore.branch}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleBranchFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        // Status Chip
        if (window.AppStore.status !== 'ALL') {
            const labels = {
                LOW_STOCK: 'Low Stock Alerts',
                OUT_OF_STOCK: 'Out of Stock',
                HEALTHY: 'Healthy Levels',
                HAS_TRANSIT: 'Active In-Transit'
            };
            chips.push(`
                <span class="stk-chip">
                    <span>Status: <strong>${labels[window.AppStore.status]}</strong></span>
                    <button type="button" class="stk-chip-remove" onclick="handleStatusFilter('ALL')"><i class="ph ph-x"></i></button>
                </span>
            `);
        }

        container.innerHTML = chips.join('');
    }

    // =========================================================================
    // UNIFIED SINGLE DATE RANGE PICKER CONTROLS
    // =========================================================================
    window.toggleDateRangePopover = function(e) {
        if (e) e.stopPropagation();
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        const isOpen = popover.classList.contains('is-open');

        if (isOpen) {
            closeDateRangePopover();
        } else {
            popover.classList.add('is-open');
            trigger.classList.add('is-active');
            trigger.setAttribute('aria-expanded', 'true');
        }
    };

    window.closeDateRangePopover = function() {
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        if (popover) popover.classList.remove('is-open');
        if (trigger) {
            trigger.classList.remove('is-active');
            trigger.setAttribute('aria-expanded', 'false');
        }
    };

    window.selectDatePreset = function(preset) {
        document.querySelectorAll('.stk-popover-preset-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-preset') === preset);
        });

        window.AppStore.datePreset = preset;
        const today = new Date();
        let s = new Date();
        let e = new Date();

        if (preset === 'today') {
            s = today;
            e = today;
        } else if (preset === 'yesterday') {
            s = new Date(today);
            s.setDate(today.getDate() - 1);
            e = new Date(s);
        } else if (preset === 'last_7_days') {
            s = new Date(today);
            s.setDate(today.getDate() - 6);
            e = today;
        } else if (preset === 'this_month') {
            s = new Date(today.getFullYear(), today.getMonth(), 1);
            e = today;
        } else if (preset === 'last_30_days') {
            s = new Date(today);
            s.setDate(today.getDate() - 29);
            e = today;
        } else if (preset === 'this_quarter') {
            const currentQuarter = Math.floor(today.getMonth() / 3);
            s = new Date(today.getFullYear(), currentQuarter * 3, 1);
            e = today;
        }

        window.AppStore.startDate = formatDateInput(s);
        window.AppStore.endDate = formatDateInput(e);

        document.getElementById('stkStartDate').value = window.AppStore.startDate;
        document.getElementById('stkEndDate').value = window.AppStore.endDate;

        window.AppStore.currentPage = 1;
        closeDateRangePopover();
        renderTable();
    };

    window.handleManualDateChange = function() {
        const startVal = document.getElementById('stkStartDate').value;
        const endVal = document.getElementById('stkEndDate').value;
        if (startVal && endVal) {
            window.AppStore.startDate = startVal;
            window.AppStore.endDate = endVal;
            window.AppStore.datePreset = 'custom';
            document.querySelectorAll('.stk-popover-preset-btn').forEach(btn => {
                btn.classList.remove('active');
            });
        }
    };

    window.applyCustomDateRange = function() {
        const startVal = document.getElementById('stkStartDate').value;
        const endVal = document.getElementById('stkEndDate').value;
        if (!startVal || !endVal) {
            alert('Please select both a valid start and end date.');
            return;
        }
        if (startVal > endVal) {
            alert('Start date cannot be after end date.');
            return;
        }
        window.AppStore.startDate = startVal;
        window.AppStore.endDate = endVal;
        window.AppStore.datePreset = 'custom';
        window.AppStore.currentPage = 1;
        closeDateRangePopover();
        renderTable();
    };

    window.resetToThisMonth = function() {
        selectDatePreset('this_month');
    };

    // Close popover when clicking anywhere outside
    document.addEventListener('click', function(e) {
        const popover = document.getElementById('stkDateRangePopover');
        const trigger = document.getElementById('stkDateRangeTrigger');
        if (popover && popover.classList.contains('is-open')) {
            if (!popover.contains(e.target) && !trigger.contains(e.target)) {
                closeDateRangePopover();
            }
        }
    });

    // =========================================================================
    // OTHER FILTER EVENT HANDLERS
    // =========================================================================
    window.handleCategoryFilter = function(cat) {
        window.AppStore.category = cat;
        document.getElementById('stkCategoryFilter').value = cat;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.handleBranchFilter = function(b) {
        window.AppStore.branch = b;
        document.getElementById('stkBranchFilter').value = b;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.handleStatusFilter = function(st) {
        window.AppStore.status = st;
        document.getElementById('stkStatusFilter').value = st;
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.clearSearchFilter = function() {
        window.AppStore.searchQuery = '';
        document.getElementById('stkSearchInput').value = '';
        window.AppStore.currentPage = 1;
        renderTable();
    };

    window.resetAllFilters = function() {
        window.AppStore.searchQuery = '';
        document.getElementById('stkSearchInput').value = '';
        window.AppStore.category = 'ALL';
        document.getElementById('stkCategoryFilter').value = 'ALL';
        window.AppStore.branch = 'ALL';
        document.getElementById('stkBranchFilter').value = 'ALL';
        window.AppStore.status = 'ALL';
        document.getElementById('stkStatusFilter').value = 'ALL';
        selectDatePreset('this_month');
    };

    window.refreshStocksData = async function() {
        const refreshBtn = document.querySelector('button[onclick="refreshStocksData()"]');
        let origHtml = '';
        if (refreshBtn) {
            origHtml = refreshBtn.innerHTML;
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = `<svg class="w-4 h-4 animate-spin text-primary" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Syncing...`;
        }

        try {
            const resp = await fetch('{{ route("inventory.api.stocks-overview-data") }}', {
                headers: { 'Accept': 'application/json' }
            });
            if (resp.ok) {
                const json = await resp.json();
                const data = json.data || json;
                if (data.products && Array.isArray(data.products)) {
                    window.AppStore.products = data.products;
                }
                if (data.categories && Array.isArray(data.categories)) {
                    window.AppStore.categories = data.categories;
                }
                const incomingLedger = data.stocksLedger || data.serverLedger;
                if (incomingLedger && Array.isArray(incomingLedger)) {
                    window.AppStore.serverLedger = incomingLedger;
                }
            }
        } catch (err) {
            console.warn('Live API sync notice:', err);
        } finally {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = origHtml;
            }
        }

        localStorage.removeItem('rms_stocks_ledger');
        window.AppStore.stocksLedger = null;
        getOrGenerateStocksData();
        renderTable();
        showNotification('Inventory ledger data re-synchronized from database!');
    };

    // =========================================================================
    // SLIDE-OUT DETAIL DRAWER (AUDIT & BREAKDOWN)
    // =========================================================================
    window.openItemDetailDrawer = function(productId) {
        const items = getOrGenerateStocksData();
        const item = items.find(i => i.id === productId);
        if (!item) return;

        window.AppStore.selectedProductId = productId;

        document.getElementById('drawerItemTitle').textContent = item.name;
        document.getElementById('drawerItemSubtitle').textContent = `SKU: ${item.sku} • Category: ${item.category} • UOM: ${item.unit}`;

        const eqEl = document.getElementById('drawerFormulaEquation');
        if (eqEl) {
            const adjSign = item.adjustment.qty >= 0 ? `+ ${item.adjustment.qty}` : `- ${Math.abs(item.adjustment.qty)}`;
            eqEl.innerHTML = `
                Beg (${item.begQty}) + In (${item.stockIn.total}) - Out (${item.stockOut.total}) - Waste (${item.waste.total}) ${adjSign} = <strong>Physical On-Hand (${item.onHand} ${item.unit})</strong><br>
                On-Hand (${item.onHand}) + Incoming (${item.inTransit.incoming}) - Outgoing (${item.inTransit.outgoing}) = <strong>Projected Total (${item.projectedTotal} ${item.unit})</strong>
            `;
        }

        const listEl = document.getElementById('drawerMovementList');
        if (listEl) {
            const movements = item.movements || [];
            if (movements.length === 0) {
                listEl.innerHTML = `<div style="text-align: center; padding: 20px; color: var(--stk-text-muted); font-size: 0.8rem;">No movement entries logged for this period.</div>`;
            } else {
                listEl.innerHTML = movements.map(m => `
                    <div class="stk-timeline-item">
                        <div>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span style="font-size: 0.8125rem; font-weight: 700; color: var(--stk-text-strong);">${m.type}</span>
                                <span style="font-size: 0.67rem; background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: var(--stk-text-muted);">${m.ref}</span>
                            </div>
                            <div style="font-size: 0.70rem; color: var(--stk-text-muted); margin-top: 3px;">${m.date} &bull; ${m.note}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.85rem; font-weight: 800; color: ${m.qty.startsWith('+') ? '#15803d' : '#b91c1c'};">${m.qty} ${item.unit}</div>
                            <div style="font-size: 0.68rem; color: var(--stk-text-muted);">Bal: <strong>${m.balance}</strong></div>
                        </div>
                    </div>
                `).join('');
            }
        }

        document.getElementById('drawerPeriodLabel').textContent = `${formatShortDate(window.AppStore.startDate)} to ${formatShortDate(window.AppStore.endDate)}, ${currentYear}`;

        document.getElementById('stkDrawerBackdrop').classList.add('is-open');
        document.getElementById('stkDetailDrawer').classList.add('is-open');
    };

    window.closeItemDetailDrawer = function() {
        document.getElementById('stkDrawerBackdrop').classList.remove('is-open');
        document.getElementById('stkDetailDrawer').classList.remove('is-open');
        window.AppStore.selectedProductId = null;
    };

    // =========================================================================
    // EXPORT TO CSV
    // =========================================================================
    window.exportStocksCSV = function() {
        const data = getFilteredStocks();
        if (data.length === 0) {
            alert('No records available to export.');
            return;
        }

        const headers = [
            'Item Name',
            'SKU',
            'Barcode',
            'Category',
            'Sub-category',
            'Beginning Qty',
            'Unit of Measure',
            'Stock In (Received)',
            'Stock In (POs)',
            'Stock In (Transfers)',
            'Stock Out (Sales & Consumption)',
            'Stock Out (POS)',
            'Stock Out (Recipe)',
            'Waste & Spoilage',
            'Waste (Damaged)',
            'Waste (Expired)',
            'Audit Adjustment Qty',
            'Adjustment Reason',
            'Physical On-Hand',
            'Shelf Stock',
            'Backroom Stock',
            'In-Transit Incoming (+)',
            'In-Transit Outgoing (-)',
            'Projected Total Stock'
        ];

        function escapeCSV(val) {
            if (val === null || val === undefined) return '""';
            let str = String(val).replace(/"/g, '""');
            return `"${str}"`;
        }

        let csv = headers.join(',') + '\r\n';
        data.forEach(item => {
            const row = [
                escapeCSV(item.name),
                escapeCSV(item.sku),
                escapeCSV(item.barcode),
                escapeCSV(item.category),
                escapeCSV(item.subcategory),
                item.begQty,
                escapeCSV(item.unit),
                item.stockIn.total,
                item.stockIn.po,
                item.stockIn.transfer,
                item.stockOut.total,
                item.stockOut.pos,
                item.stockOut.recipe,
                item.waste.total,
                item.waste.damaged,
                item.waste.expired,
                item.adjustment.qty,
                escapeCSV(item.adjustment.reason),
                item.onHand,
                item.shelfStock,
                item.backroomStock,
                item.inTransit.incoming,
                item.inTransit.outgoing,
                item.projectedTotal
            ];
            csv += row.join(',') + '\r\n';
        });

        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        const filename = `RMS_Stocks_Overview_${window.AppStore.startDate}_to_${window.AppStore.endDate}.csv`;
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    };

    // =========================================================================
    // INITIALIZATION & EVENT LISTENERS
    // =========================================================================
    function syncCategoryDropdown() {
        const sel = document.getElementById('stkCategoryFilter');
        if (!sel) return;
        const categories = window.AppStore.categories || DEFAULT_CATEGORIES;
        
        let opts = '<option value="ALL">All Categories</option>';
        categories.forEach(cat => {
            opts += `<option value="${cat}">${cat}</option>`;
        });
        sel.innerHTML = opts;
    }

    function initEventListeners() {
        const searchInput = document.getElementById('stkSearchInput');
        if (searchInput) {
            let debounceTimer = null;
            searchInput.addEventListener('input', function(e) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    window.AppStore.searchQuery = e.target.value.trim();
                    window.AppStore.currentPage = 1;
                    renderTable();
                }, 150);
            });
        }

        // Keyboard Shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === '/' && document.activeElement !== searchInput && !['INPUT', 'TEXTAREA'].includes(document.activeElement.tagName)) {
                e.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
            if (e.key === 'Escape') {
                const popover = document.getElementById('stkDateRangePopover');
                if (popover && popover.classList.contains('is-open')) {
                    closeDateRangePopover();
                    return;
                }
                const drawer = document.getElementById('stkDetailDrawer');
                if (drawer && drawer.classList.contains('is-open')) {
                    closeItemDetailDrawer();
                }
            }
        });
    }

    function showNotification(msg) {
        const el = document.createElement('div');
        el.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#0f172a;color:#ffffff;padding:12px 20px;border-radius:10px;font-size:0.8125rem;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:0 10px 25px rgba(0,0,0,0.2);z-index:2000;transition:all 0.25s ease;';
        el.innerHTML = `<i class="ph ph-check-circle" style="color:#10b981;font-size:18px;"></i> <span>${msg}</span>`;
        document.body.appendChild(el);
        setTimeout(() => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            setTimeout(() => document.body.removeChild(el), 300);
        }, 3000);
    }

    // Run Initial Load
    syncCategoryDropdown();
    document.getElementById('stkStartDate').value = window.AppStore.startDate;
    document.getElementById('stkEndDate').value = window.AppStore.endDate;
    getOrGenerateStocksData();
    renderTable();
    initEventListeners();

})();
</script>
@endpush

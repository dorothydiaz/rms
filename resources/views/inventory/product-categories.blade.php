@extends('layouts.app')

@section('title', 'Item Master - Inventory Operations')

@push('styles')
<style>
/* ==========================================================================
   PRODUCT MANAGEMENT & SLIDE-OUT DRAWER DESIGN SYSTEM TOKENS
   RMS Glassmorphism & Industrial Ergonomics (WCAG 2.1 AA)
   ========================================================================== */
:root {
    --inv-surface-canvas: #f8fafc;
    --inv-surface-card: rgba(255, 255, 255, 0.88);
    --inv-surface-card-hover: rgba(255, 255, 255, 0.98);
    --inv-glass-bg: rgba(255, 255, 255, 0.82);
    --inv-glass-border: rgba(226, 232, 240, 0.85);
    --inv-glass-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06), 0 4px 12px -2px rgba(15, 23, 42, 0.03);
    
    --inv-primary: #a855f7;
    --inv-primary-dark: #9333ea;
    --inv-primary-gradient: linear-gradient(135deg, #ec4899 0%, #a855f7 100%);
    --inv-primary-glow: rgba(168, 85, 247, 0.28);
    
    --inv-text-strong: #0f172a;
    --inv-text-medium: #334155;
    --inv-text-muted: #64748b;
    --inv-text-subtle: #94a3b8;
    
    --inv-border-subtle: #e2e8f0;
    --inv-border-focus: #c084fc;
    
    --inv-success: #10b981;
    --inv-success-subtle: rgba(16, 185, 129, 0.12);
    --inv-warning: #f59e0b;
    --inv-warning-subtle: rgba(245, 158, 11, 0.12);
    --inv-danger: #ef4444;
    --inv-danger-subtle: rgba(239, 68, 68, 0.12);
    
    --inv-drawer-width: min(580px, 94vw);
}

/* Page Layout Container */
.inv-page-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
    width: 100%;
    position: relative;
    padding-bottom: 40px;
}

/* 1. Header Section */
.inv-header-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    background: var(--inv-glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--inv-glass-border);
    border-radius: 16px;
    padding: 16px 22px;
    box-shadow: var(--inv-glass-shadow);
}

.inv-header-title-box {
    display: flex;
    align-items: center;
    gap: 14px;
}

.inv-header-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.16));
    border: 1px solid rgba(168, 85, 247, 0.25);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--inv-primary-dark);
    font-size: 22px;
}

.inv-header-title-box h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--inv-text-strong);
    margin: 0;
    letter-spacing: -0.01em;
}

.inv-header-title-box p {
    font-size: 0.8125rem;
    color: var(--inv-text-muted);
    margin: 2px 0 0 0;
}

.inv-header-actions {
    display: flex;
    align-items: center;
    gap: 10px;
    flex-wrap: wrap;
}

/* Buttons in Header */
.inv-action-btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: rgba(255, 255, 255, 0.9);
    color: var(--inv-text-medium);
    font-size: 0.83rem;
    font-weight: 600;
    padding: 9px 15px;
    border-radius: 11px;
    border: 1.5px solid var(--inv-border-subtle);
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
    transition: all 0.2s ease;
    font-family: inherit;
    text-decoration: none;
}

.inv-action-btn-secondary:hover {
    background: #ffffff;
    color: var(--inv-primary-dark);
    border-color: rgba(168, 85, 247, 0.4);
    transform: translateY(-1.5px);
    box-shadow: 0 4px 12px rgba(168, 85, 247, 0.15);
}

.inv-action-btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: var(--inv-primary-gradient);
    color: #ffffff !important;
    font-size: 0.84rem;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 11px;
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 14px var(--inv-primary-glow);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    text-decoration: none;
}

.inv-action-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(168, 85, 247, 0.4);
}

.inv-action-btn-primary:active {
    transform: translateY(0);
}

.inv-hotkey-badge {
    background: rgba(255, 255, 255, 0.22);
    border: 1px solid rgba(255, 255, 255, 0.4);
    padding: 2px 6px;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    font-variant-numeric: tabular-nums;
}

/* ==========================================================================
   FILTER BAR SECTION (WITH SMOOTH HORIZONTAL SCROLL)
   ========================================================================== */
.inv-filter-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: var(--inv-glass-bg);
    backdrop-filter: blur(12px);
    border: 1px solid var(--inv-glass-border);
    border-radius: 14px;
    padding: 12px 18px;
    box-shadow: 0 4px 16px rgba(15, 23, 42, 0.03);
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    white-space: nowrap;
}

/* Custom Scrollbar for horizontal scrolling zones */
.inv-filter-bar::-webkit-scrollbar,
.inv-table-responsive::-webkit-scrollbar,
.inv-filter-pills::-webkit-scrollbar {
    height: 6px;
    background: rgba(241, 245, 249, 0.6);
    border-radius: 4px;
}

.inv-filter-bar::-webkit-scrollbar-thumb,
.inv-table-responsive::-webkit-scrollbar-thumb,
.inv-filter-pills::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.inv-filter-bar::-webkit-scrollbar-thumb:hover,
.inv-table-responsive::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.inv-filter-left {
    display: flex;
    align-items: center;
    gap: 12px;
    flex: 1;
    min-width: 480px;
}

.inv-search-wrapper {
    position: relative;
    width: 280px;
    flex-shrink: 0;
}

.inv-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--inv-text-muted);
    font-size: 16px;
    pointer-events: none;
}

.inv-search-input {
    width: 100%;
    height: 38px;
    padding: 0 38px 0 36px;
    border-radius: 10px;
    border: 1.5px solid var(--inv-border-subtle);
    background: rgba(255, 255, 255, 0.9);
    font-size: 0.83rem;
    color: var(--inv-text-strong);
    font-family: inherit;
    transition: all 0.2s ease;
    outline: none;
}

.inv-search-input:focus {
    border-color: var(--inv-primary);
    box-shadow: 0 0 0 3.5px var(--inv-primary-glow);
    background: #ffffff;
}

.inv-search-kbd {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: var(--inv-text-muted);
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 5px;
    border-radius: 5px;
    pointer-events: none;
}

.inv-filter-pills-carousel {
    display: flex;
    align-items: center;
    gap: 4px;
    position: relative;
    max-width: 480px; /* Constrains view to ~4 category pills visible at once */
}

.inv-filter-pills-nav {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 26px;
    height: 26px;
    border-radius: 7px;
    background: #ffffff;
    border: 1px solid var(--inv-border-subtle);
    color: var(--inv-text-muted);
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.inv-filter-pills-nav:hover {
    background: #f8fafc;
    color: var(--inv-primary-dark);
    border-color: var(--inv-border-focus);
}

.inv-filter-pills {
    display: flex;
    align-items: center;
    gap: 6px;
    overflow-x: auto;
    padding: 2px 2px;
    flex-wrap: nowrap;
    -webkit-overflow-scrolling: touch;
    scroll-behavior: smooth;
    max-width: 420px; /* Limits visible width to exactly ~4 pills at a time */
}

.inv-filter-pill {
    padding: 6px 13px;
    border-radius: 8px;
    font-size: 0.78rem;
    font-weight: 500;
    color: var(--inv-text-medium);
    background: rgba(241, 245, 249, 0.85);
    border: 1px solid rgba(226, 232, 240, 0.8);
    cursor: pointer;
    transition: all 0.15s ease;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.inv-filter-pill:hover {
    background: #e2e8f0;
    color: var(--inv-text-strong);
}

.inv-filter-pill.active {
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.12), rgba(168, 85, 247, 0.14));
    border-color: rgba(168, 85, 247, 0.4);
    color: var(--inv-primary-dark);
    font-weight: 600;
    box-shadow: 0 2px 6px rgba(168, 85, 247, 0.1);
}

.inv-pill-count {
    font-size: 0.68rem;
    background: rgba(0, 0, 0, 0.06);
    padding: 1px 5px;
    border-radius: 10px;
    font-variant-numeric: tabular-nums;
}

.inv-filter-right {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-shrink: 0;
}

.inv-filter-stats {
    font-size: 0.8rem;
    color: var(--inv-text-muted);
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ==========================================================================
   DROPDOWN MENU FOR COLUMN FILTER TRIGGERED BY FILTER ICON AFTER ACTION
   ========================================================================== */
.inv-header-action-wrapper {
    display: inline-flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    position: relative;
}

/* Filter Icon Trigger Button */
.inv-table-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    border-radius: 7px;
    background: rgba(255, 255, 255, 0.9);
    border: 1.5px solid var(--inv-border-subtle);
    color: var(--inv-text-medium);
    font-size: 14px;
    cursor: pointer;
    transition: all 0.15s ease;
    box-shadow: 0 1px 3px rgba(15, 23, 42, 0.05);
}

.inv-table-filter-btn:hover,
.inv-table-filter-btn.is-active {
    background: #ffffff;
    border-color: var(--inv-primary);
    color: var(--inv-primary-dark);
    box-shadow: 0 0 0 3px var(--inv-primary-glow);
}

/* Floating Column Filter Dropdown - Sized generously for industrial visibility */
.inv-column-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    width: 440px; /* Generous industrial width to fit all quick actions and headers */
    max-width: min(440px, 92vw);
    background: #ffffff;
    border: 1.5px solid var(--inv-border-subtle);
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
    font-size: 0.84rem;
    font-weight: 700;
    color: var(--inv-text-strong);
    display: flex;
    align-items: center;
    gap: 7px;
    letter-spacing: 0.02em;
}

.inv-col-dropdown-title i {
    color: var(--inv-primary-dark);
    font-size: 17px;
}

.inv-col-dropdown-quick-links {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 6px;
}

.inv-col-quick-link {
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    color: var(--inv-text-medium);
    font-size: 0.72rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.inv-col-quick-link:hover {
    background: #ffffff;
    color: var(--inv-primary-dark);
    border-color: var(--inv-primary);
    box-shadow: 0 1px 4px rgba(168, 85, 247, 0.15);
}

.inv-col-quick-link.is-reset {
    color: #e11d48;
    background: #fff1f2;
    border-color: #fecdd3;
}

.inv-col-quick-link.is-reset:hover {
    background: #ffe4e6;
    border-color: #fda4af;
    color: #be123c;
}

.inv-col-dropdown-list {
    max-height: 360px; /* Generous height allowing easy viewing and scrolling */
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 5px;
    padding-right: 6px;
    scroll-behavior: smooth;
}

.inv-col-dropdown-list::-webkit-scrollbar {
    width: 5px;
}

.inv-col-dropdown-list::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.inv-col-dropdown-list::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

.inv-col-dropdown-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    border-radius: 9px;
    font-size: 0.82rem;
    color: var(--inv-text-medium);
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1.5px solid transparent;
    user-select: none;
}

.inv-col-dropdown-item:hover {
    background: #f8fafc;
    border-color: #e2e8f0;
    transform: translateX(2px);
}

.inv-col-dropdown-item.is-checked {
    background: rgba(168, 85, 247, 0.08);
    border-color: rgba(168, 85, 247, 0.22);
    color: var(--inv-text-strong);
    font-weight: 600;
}

.inv-col-checkbox-label {
    display: flex;
    align-items: center;
    gap: 9px;
    user-select: none;
    flex: 1;
    min-width: 0; /* Prevents flex children from overflowing parent */
}

.inv-col-checkbox-label span:not(.inv-col-checkbox-custom) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}

.inv-col-checkbox-custom {
    width: 16px;
    height: 16px;
    border-radius: 5px;
    border: 1.5px solid #94a3b8;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.inv-col-dropdown-item.is-checked .inv-col-checkbox-custom {
    background: var(--inv-primary);
    border-color: var(--inv-primary);
    color: #ffffff;
}

.inv-col-status-badge {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 7px;
    border-radius: 4px;
    flex-shrink: 0;
    margin-left: 8px;
}

.inv-col-dropdown-item.is-checked .inv-col-status-badge {
    color: var(--inv-primary-dark);
    background: rgba(168, 85, 247, 0.12);
}

.inv-col-dropdown-item:not(.is-checked) .inv-col-status-badge {
    color: var(--inv-text-subtle);
    background: #f1f5f9;
}

.inv-col-dropdown-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 10px;
    border-top: 1px solid #f1f5f9;
    margin-top: 8px;
    font-size: 0.72rem;
    color: var(--inv-text-muted);
}

/* ==========================================================================
   MASTER DATA GRID CONTAINER (HORIZONTAL SCROLL ENABLED)
   ========================================================================== */
.inv-grid-card {
    background: var(--inv-glass-bg);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid var(--inv-glass-border);
    border-radius: 16px;
    box-shadow: var(--inv-glass-shadow);
    overflow: visible; /* Allows column filter dropdown to float gracefully */
    position: relative;
}

.inv-table-responsive {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
    border-radius: 16px;
}

.inv-master-table {
    width: 100%;
    table-layout: fixed; /* Enforces strict immutable column widths; never shifts when categories collapse */
    border-collapse: separate;
    border-spacing: 0;
    font-size: 0.82rem;
    text-align: left;
    min-width: 1350px; /* Guarantees smooth horizontal scroll for extensive columns */
}

.inv-master-table thead th {
    background: rgba(248, 250, 252, 0.98);
    color: var(--inv-text-muted);
    font-weight: 600;
    font-size: 0.72rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 12px 14px;
    border-bottom: 1.5px solid var(--inv-border-subtle);
    white-space: nowrap;
    position: sticky;
    top: 0;
    z-index: 10;
    user-select: none;
}

/* Interactive Column Resizer Handle */
.inv-col-resizer {
    position: absolute;
    top: 0;
    right: 0;
    width: 7px;
    height: 100%;
    cursor: col-resize;
    user-select: none;
    z-index: 15;
    display: flex;
    align-items: center;
    justify-content: center;
}

.inv-col-resizer::after {
    content: '';
    display: block;
    width: 2px;
    height: 16px;
    border-radius: 1px;
    background: transparent;
    transition: all 0.15s ease;
}

.inv-col-resizer:hover::after,
.inv-col-resizer.is-resizing::after {
    background: var(--inv-primary);
    height: 100%;
    width: 3px;
    box-shadow: 0 0 8px var(--inv-primary-glow);
}

body.is-column-resizing {
    cursor: col-resize !important;
    user-select: none !important;
}

body.is-column-resizing * {
    user-select: none !important;
}

.inv-master-table thead th.th-right {
    text-align: right;
}

.inv-master-table thead th.th-center {
    text-align: center;
}

/* Sticky Action Column Header */
.inv-master-table thead th.th-action {
    position: sticky;
    right: 0;
    background: rgba(248, 250, 252, 0.98);
    box-shadow: -4px 0 10px rgba(0, 0, 0, 0.03);
    z-index: 20;
}

/* ACCORDION CATEGORY GROUP HEADER ROWS */
.inv-group-row {
    background: linear-gradient(90deg, #f1f5f9 0%, #f8fafc 100%);
    border-top: 1.5px solid #e2e8f0;
    border-bottom: 1px solid #e2e8f0;
    cursor: pointer;
    user-select: none;
    transition: background 0.15s ease;
}

.inv-group-row:hover {
    background: linear-gradient(90deg, #e8eef5 0%, #f1f5f9 100%);
}

.inv-group-header-cell {
    padding: 10px 16px;
    font-weight: 700;
    color: var(--inv-text-strong);
    font-size: 0.83rem;
    letter-spacing: -0.01em;
}

.inv-group-header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.inv-group-left-box {
    display: flex;
    align-items: center;
    gap: 10px;
}

.inv-accordion-chevron {
    width: 24px;
    height: 24px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--inv-text-medium);
    font-size: 13px;
    transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.inv-group-row.collapsed .inv-accordion-chevron {
    transform: rotate(-90deg);
}

.inv-group-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--inv-primary);
    box-shadow: 0 0 8px var(--inv-primary-glow);
}

.inv-group-title {
    font-weight: 700;
    color: var(--inv-text-strong);
}

.inv-group-count-badge {
    background: #e2e8f0;
    color: var(--inv-text-medium);
    font-size: 0.71rem;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 10px;
    font-variant-numeric: tabular-nums;
}

.inv-group-quick-add-btn {
    font-size: 0.72rem;
    font-weight: 600;
    color: var(--inv-primary-dark);
    background: rgba(168, 85, 247, 0.1);
    border: 1px solid rgba(168, 85, 247, 0.25);
    padding: 3px 8px;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.inv-group-quick-add-btn:hover {
    background: var(--inv-primary);
    color: #ffffff;
}

/* Linear Nested Item Rows */
.inv-item-row {
    background: rgba(255, 255, 255, 0.65);
    transition: all 0.15s ease-in-out;
    border-bottom: 1px solid rgba(241, 245, 249, 0.9);
    cursor: pointer;
}

.inv-item-row:hover {
    background: rgba(248, 250, 252, 0.95);
    box-shadow: inset 3px 0 0 var(--inv-primary);
}

.inv-item-row.is-selected {
    background: rgba(243, 232, 255, 0.45);
    box-shadow: inset 3px 0 0 var(--inv-primary-dark);
}

.inv-item-row.is-hidden {
    display: none !important;
}

.inv-item-row td {
    padding: 12px 14px;
    color: var(--inv-text-medium);
    vertical-align: middle;
    white-space: nowrap;
}

.inv-item-row td.td-right {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

/* Sticky Action Column Body */
.inv-item-row td.td-action {
    position: sticky;
    right: 0;
    background: inherit;
    box-shadow: -4px 0 10px rgba(0, 0, 0, 0.02);
    z-index: 5;
}

.inv-item-row:hover td.td-action {
    background: rgba(248, 250, 252, 0.98);
}

.inv-sku-badge {
    font-family: monospace;
    font-size: 0.75rem;
    font-weight: 600;
    background: #f1f5f9;
    color: #334155;
    padding: 3px 6px;
    border-radius: 5px;
    border: 1px solid #e2e8f0;
}

.inv-prod-title-group {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.inv-barcode-tag {
    font-family: monospace;
    font-size: 0.72rem;
    color: var(--inv-text-muted);
    display: flex;
    align-items: center;
    gap: 4px;
}

.inv-product-name {
    font-weight: 600;
    color: var(--inv-text-strong);
    font-size: 0.84rem;
}

.inv-desc-cell {
    max-width: 220px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    color: var(--inv-text-muted);
    font-size: 0.76rem;
}

.inv-badge-subcat {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.72rem;
    font-weight: 500;
    padding: 2px 7px;
    border-radius: 6px;
}

.inv-badge-allergen {
    display: inline-block;
    background: rgba(245, 158, 11, 0.1);
    color: #b45309;
    border: 1px solid rgba(245, 158, 11, 0.25);
    font-size: 0.68rem;
    font-weight: 600;
    padding: 1px 6px;
    border-radius: 4px;
    margin-right: 3px;
}

.inv-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.71rem;
    font-weight: 600;
}

.inv-status-pill.active {
    background: var(--inv-success-subtle);
    color: #065f46;
}

.inv-status-pill.inactive {
    background: rgba(148, 163, 184, 0.16);
    color: #475569;
}

.inv-status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: currentColor;
}

.inv-view-btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--inv-primary-dark);
    background: rgba(168, 85, 247, 0.08);
    border: 1px solid rgba(168, 85, 247, 0.2);
    padding: 5px 11px;
    border-radius: 8px;
    transition: all 0.15s ease;
    cursor: pointer;
    text-decoration: none;
}

.inv-view-btn:hover {
    background: var(--inv-primary);
    color: #ffffff;
    box-shadow: 0 3px 8px var(--inv-primary-glow);
    border-color: var(--inv-primary);
}

/* Empty State */
.inv-empty-state {
    padding: 48px 24px;
    text-align: center;
    color: var(--inv-text-muted);
}

.inv-empty-icon {
    font-size: 44px;
    color: var(--inv-text-subtle);
    margin-bottom: 12px;
}

/* ==========================================================================
   2. THE FOREGROUND LAYER (SLIDE-OUT DETAIL DRAWER)
   ========================================================================== */
.inv-drawer-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.35);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    z-index: 1000;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.inv-drawer-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
}

.inv-drawer-container {
    position: fixed;
    top: 0;
    right: 0;
    width: var(--inv-drawer-width);
    height: 100vh;
    background: #ffffff;
    box-shadow: -10px 0 40px rgba(15, 23, 42, 0.18);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    transform: translateX(100%);
    transition: transform 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    border-left: 1px solid var(--inv-border-subtle);
}

.inv-drawer-container.is-open {
    transform: translateX(0);
}

/* Panel Header */
.inv-drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 18px 24px;
    border-bottom: 1px solid var(--inv-border-subtle);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    flex-shrink: 0;
}

.inv-drawer-title-box {
    display: flex;
    align-items: center;
    gap: 12px;
}

.inv-drawer-header-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    background: rgba(168, 85, 247, 0.1);
    color: var(--inv-primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.inv-drawer-title {
    font-size: 1.05rem;
    font-weight: 700;
    color: var(--inv-text-strong);
    margin: 0;
    line-height: 1.3;
}

.inv-drawer-subtitle {
    font-size: 0.75rem;
    color: var(--inv-text-muted);
    margin: 0;
}

.inv-drawer-close-btn {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: 1px solid var(--inv-border-subtle);
    background: #ffffff;
    color: var(--inv-text-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.inv-drawer-close-btn:hover {
    background: #f1f5f9;
    color: var(--inv-danger);
    border-color: #cbd5e1;
}

/* Primary Horizontal Navigation Tabs */
.inv-drawer-nav {
    display: flex;
    align-items: center;
    padding: 0 24px;
    border-bottom: 1px solid var(--inv-border-subtle);
    background: #ffffff;
    overflow-x: auto;
    flex-shrink: 0;
}

.inv-drawer-tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 12px 14px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--inv-text-muted);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.15s ease;
    font-family: inherit;
}

.inv-drawer-tab-btn:hover {
    color: var(--inv-text-strong);
}

.inv-drawer-tab-btn.active {
    color: var(--inv-primary-dark);
    border-bottom-color: var(--inv-primary);
}

/* Scrollable Drawer Body */
.inv-drawer-body {
    flex: 1;
    overflow-y: auto;
    padding: 22px 24px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    background: #fcfcfd;
}

/* Form Content (Vertical Stack) */
.inv-form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.inv-form-group label {
    font-size: 0.77rem;
    font-weight: 600;
    color: var(--inv-text-medium);
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.inv-form-input,
.inv-form-textarea,
.inv-form-select {
    width: 100%;
    border-radius: 9px;
    border: 1.5px solid var(--inv-border-subtle);
    background: #ffffff;
    padding: 9px 12px;
    font-size: 0.84rem;
    color: var(--inv-text-strong);
    font-family: inherit;
    transition: all 0.15s ease;
    outline: none;
    box-sizing: border-box;
}

.inv-form-input:focus,
.inv-form-textarea:focus,
.inv-form-select:focus {
    border-color: var(--inv-primary);
    box-shadow: 0 0 0 3px var(--inv-primary-glow);
}

.inv-form-row-2 {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.inv-form-textarea {
    resize: vertical;
    min-height: 72px;
}

/* Checkbox with Associated Label */
.inv-checkbox-card {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 14px;
    border-radius: 9px;
    background: #f8fafc;
    border: 1px solid var(--inv-border-subtle);
    cursor: pointer;
    transition: all 0.15s ease;
}

.inv-checkbox-card:hover {
    background: #f1f5f9;
}

.inv-checkbox-card input[type="checkbox"] {
    width: 17px;
    height: 17px;
    accent-color: var(--inv-primary);
    cursor: pointer;
}

.inv-checkbox-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--inv-text-strong);
    margin: 0;
}

.inv-checkbox-desc {
    font-size: 0.72rem;
    color: var(--inv-text-muted);
    margin: 0;
}

/* Nested Content Section (Secondary Tabs + Sub-Table) */
.inv-nested-section {
    background: #ffffff;
    border: 1.5px solid var(--inv-border-subtle);
    border-radius: 12px;
    overflow: hidden;
    margin-top: 6px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
}

.inv-subtabs-nav {
    display: flex;
    align-items: center;
    background: #f8fafc;
    border-bottom: 1px solid var(--inv-border-subtle);
    padding: 0 10px;
}

.inv-subtab-btn {
    padding: 9px 12px;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--inv-text-muted);
    background: transparent;
    border: none;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}

.inv-subtab-btn:hover {
    color: var(--inv-text-strong);
}

.inv-subtab-btn.active {
    color: var(--inv-primary-dark);
    border-bottom-color: var(--inv-primary);
    background: rgba(255, 255, 255, 0.9);
}

.inv-subtable-wrapper {
    max-height: 220px;
    overflow-y: auto;
}

.inv-subtable {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.78rem;
    text-align: left;
}

.inv-subtable th {
    background: #f8fafc;
    color: var(--inv-text-muted);
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 8px 12px;
    border-bottom: 1px solid var(--inv-border-subtle);
    position: sticky;
    top: 0;
}

.inv-subtable td {
    padding: 8px 12px;
    color: var(--inv-text-medium);
    border-bottom: 1px solid #f1f5f9;
}

.inv-subtable td.td-right,
.inv-subtable th.th-right {
    text-align: right;
    font-variant-numeric: tabular-nums;
}

.inv-stock-status-pill {
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 0.68rem;
    font-weight: 600;
}

.inv-stock-status-pill.in-stock {
    background: var(--inv-success-subtle);
    color: var(--inv-success);
}

.inv-stock-status-pill.low-stock {
    background: var(--inv-warning-subtle);
    color: var(--inv-warning);
}

/* Panel Footer (Fixed Bottom Section with 2 Right-Aligned Action Buttons) */
.inv-drawer-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid var(--inv-border-subtle);
    flex-shrink: 0;
}

.inv-btn-secondary {
    padding: 9px 16px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    color: var(--inv-text-medium);
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
}

.inv-btn-secondary:hover {
    background: #e2e8f0;
    color: var(--inv-text-strong);
}

.inv-btn-primary {
    padding: 9px 18px;
    border-radius: 10px;
    font-size: 0.82rem;
    font-weight: 600;
    color: #ffffff;
    background: var(--inv-primary-gradient);
    border: none;
    cursor: pointer;
    box-shadow: 0 3px 10px var(--inv-primary-glow);
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    font-family: inherit;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.inv-btn-primary:hover {
    transform: translateY(-1.5px);
    box-shadow: 0 5px 16px rgba(168, 85, 247, 0.38);
}

/* Modal Dialog for Category Creation */
.inv-modal-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(4px);
    z-index: 1100;
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
}

.inv-modal-backdrop.is-open {
    opacity: 1;
    pointer-events: auto;
}

.inv-modal-card {
    background: #ffffff;
    border-radius: 16px;
    width: min(520px, 94vw);
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.2);
    border: 1px solid var(--inv-border-subtle);
    overflow: hidden;
    transform: scale(0.95);
    transition: transform 0.2s ease;
}

.inv-modal-card.inv-modal-lg {
    width: min(680px, 94vw);
}

.inv-modal-backdrop.is-open .inv-modal-card {
    transform: scale(1);
}

.inv-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 20px;
    border-bottom: 1px solid var(--inv-border-subtle);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

.inv-modal-title {
    font-size: 1rem;
    font-weight: 700;
    color: var(--inv-text-strong);
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0;
}

.inv-modal-body {
    padding: 20px;
    display: flex;
    flex-direction: column;
    gap: 14px;
    max-height: 75vh;
    overflow-y: auto;
}

.inv-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 8px;
    padding: 14px 20px;
    background: #f8fafc;
    border-top: 1px solid var(--inv-border-subtle);
}

/* Category Modal Autocomplete & Duplicate Match Dropdown */
.inv-cat-input-wrapper {
    position: relative;
    width: 100%;
}

.inv-cat-autocomplete-dropdown {
    position: absolute;
    top: calc(100% + 4px);
    left: 0;
    right: 0;
    background: #ffffff;
    border: 1px solid var(--inv-border-subtle);
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(15, 23, 42, 0.12);
    z-index: 1200;
    max-height: 180px;
    overflow-y: auto;
    display: none;
}

.inv-cat-autocomplete-dropdown.is-open {
    display: block;
}

.inv-cat-dropdown-header {
    padding: 6px 12px;
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--inv-text-muted);
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
}

.inv-cat-dropdown-item {
    padding: 8px 12px;
    font-size: 0.8rem;
    color: var(--inv-text-strong);
    display: flex;
    align-items: center;
    justify-content: space-between;
    cursor: pointer;
    transition: background 0.15s ease;
}

.inv-cat-dropdown-item:hover {
    background: rgba(168, 85, 247, 0.08);
    color: var(--inv-primary-dark);
}

.inv-cat-duplicate-warning {
    display: none;
    align-items: center;
    gap: 6px;
    margin-top: 6px;
    padding: 6px 10px;
    background: rgba(239, 68, 68, 0.08);
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 7px;
    color: var(--inv-danger);
    font-size: 0.74rem;
    font-weight: 500;
}

.inv-cat-duplicate-warning.is-visible {
    display: flex;
}

/* Accordion Header Category Management Action Buttons */
.inv-group-actions-box {
    display: flex;
    align-items: center;
    gap: 6px;
    opacity: 0.9;
    transition: opacity 0.2s ease;
}

.inv-group-row:hover .inv-group-actions-box {
    opacity: 1;
}

.inv-group-action-btn {
    width: 26px;
    height: 26px;
    border-radius: 6px;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: var(--inv-text-medium);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.inv-group-action-btn:hover {
    background: var(--inv-primary-glow);
    color: var(--inv-primary-dark);
    border-color: var(--inv-primary);
    transform: scale(1.05);
}

.inv-group-action-btn.is-delete:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--inv-danger);
    border-color: rgba(239, 68, 68, 0.4);
}

/* Category Modal Icon & Color Customizer */
.inv-cat-icon-grid {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 8px;
}

.inv-cat-icon-btn {
    height: 42px;
    border-radius: 9px;
    border: 1.5px solid var(--inv-border-subtle);
    background: #f8fafc;
    color: var(--inv-text-medium);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 19px;
    cursor: pointer;
    transition: all 0.15s ease;
}

.inv-cat-icon-btn:hover {
    background: #ffffff;
    border-color: var(--inv-border-focus);
    color: var(--inv-primary-dark);
    transform: translateY(-1px);
}

.inv-cat-icon-btn.is-active {
    background: #f3e8ff;
    border-color: var(--inv-primary);
    color: var(--inv-primary-dark);
    box-shadow: 0 0 0 3px var(--inv-primary-glow);
}

.inv-cat-color-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.inv-cat-color-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 7px 10px;
    border-radius: 8px;
    border: 1.5px solid var(--inv-border-subtle);
    background: #ffffff;
    cursor: pointer;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--inv-text-strong);
    transition: all 0.15s ease;
}

.inv-cat-color-chip:hover {
    border-color: #cbd5e1;
    background: #f8fafc;
}

.inv-cat-color-chip.is-active {
    border-color: var(--inv-primary);
    background: #faf5ff;
    box-shadow: 0 0 0 2px var(--inv-primary-glow);
}

.inv-cat-color-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
}

.inv-cat-preview-box {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 16px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px dashed #cbd5e1;
}

.inv-cat-preview-pill {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 5px 12px;
    border-radius: 8px;
    font-size: 0.8125rem;
    font-weight: 700;
    border: 1px solid;
    transition: all 0.2s ease;
}

/* Category Avatar in Group Accordion Header */
.inv-cat-avatar-icon {
    width: 26px;
    height: 26px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

/* Category Badge Pill inside table column */
.inv-category-pill {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 0.72rem;
    font-weight: 700;
    width: fit-content;
    white-space: nowrap;
}

/* Custom Category Dropdown in Drawer */
.inv-custom-select-group {
    display: flex;
    gap: 6px;
    align-items: stretch;
}

.inv-btn-quick-create {
    padding: 0 12px;
    background: rgba(168, 85, 247, 0.1);
    border: 1.5px solid rgba(168, 85, 247, 0.3);
    color: var(--inv-primary-dark);
    border-radius: 9px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    transition: all 0.15s ease;
}

.inv-btn-quick-create:hover {
    background: var(--inv-primary);
    color: #ffffff;
    border-color: var(--inv-primary);
}

/* Custom Scrollbar for Sub-tables */
.inv-subtable-wrapper::-webkit-scrollbar {
    width: 6px;
    height: 6px;
}
.inv-subtable-wrapper::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}
.inv-subtable-wrapper::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}
.inv-subtable-wrapper::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Sub-table Pagination Footer */
.inv-subtable-pagination {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    background: #f8fafc;
    border-top: 1px solid var(--inv-border-subtle);
    font-size: 0.72rem;
    color: var(--inv-text-muted);
}

.inv-subtable-pagination-nav {
    display: flex;
    align-items: center;
    gap: 6px;
}

.inv-subtable-page-btn {
    width: 24px;
    height: 24px;
    border-radius: 5px;
    border: 1px solid var(--inv-border-subtle);
    background: #ffffff;
    color: var(--inv-text-medium);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 11px;
    transition: all 0.15s ease;
}

.inv-subtable-page-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
    background: #f8fafc;
}

.inv-subtable-page-btn:not(:disabled):hover {
    border-color: var(--inv-primary);
    color: var(--inv-primary-dark);
}

/* Price History Column Chart Card & Supplier Comparison */
.inv-pricing-chart-card {
    background: #ffffff;
    border: 1.5px solid var(--inv-border-subtle);
    border-radius: 12px;
    padding: 14px 16px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
}

.inv-pricing-chart-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 10px;
}

.inv-pricing-chart-title {
    font-size: 0.78rem;
    font-weight: 700;
    color: var(--inv-text-strong);
    display: flex;
    align-items: center;
    gap: 6px;
}

.inv-pricing-chart-badge {
    font-size: 0.68rem;
    font-weight: 600;
    padding: 2px 7px;
    background: rgba(168, 85, 247, 0.1);
    color: var(--inv-primary-dark);
    border-radius: 12px;
}

.inv-pricing-chart-canvas-wrapper {
    position: relative;
    height: 165px;
    width: 100%;
}

.inv-supplier-list-card {
    background: #ffffff;
    border: 1.5px solid var(--inv-border-subtle);
    border-radius: 12px;
    overflow: hidden;
}

.inv-supplier-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 9px 12px;
    background: #f8fafc;
    border-bottom: 1px solid var(--inv-border-subtle);
    font-size: 0.74rem;
    font-weight: 700;
    color: var(--inv-text-strong);
}

.inv-supplier-list-body {
    max-height: 140px;
    overflow-y: auto;
}

.inv-supplier-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 0.76rem;
    transition: background 0.15s ease;
}

.inv-supplier-item:last-child {
    border-bottom: none;
}

.inv-supplier-item.is-lowest {
    background: rgba(16, 185, 129, 0.05);
}

.inv-supplier-name {
    font-weight: 600;
    color: var(--inv-text-strong);
}

.inv-supplier-updated {
    font-size: 0.68rem;
    color: var(--inv-text-muted);
}

.inv-supplier-price-box {
    text-align: right;
}

.inv-supplier-price {
    font-weight: 700;
    color: var(--inv-text-strong);
}

.inv-supplier-item.is-lowest .inv-supplier-price {
    color: var(--inv-success);
}

.inv-supplier-best-badge {
    font-size: 0.62rem;
    font-weight: 700;
    padding: 1px 5px;
    border-radius: 4px;
    background: var(--inv-success-subtle);
    color: var(--inv-success);
    margin-left: 4px;
}


/* Import Modal Dropzone & Preview Styles */
.inv-dropzone {
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 28px 20px;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.inv-dropzone:hover, .inv-dropzone.is-dragover {
    border-color: var(--inv-primary);
    background: rgba(168, 85, 247, 0.05);
}

.inv-dropzone-icon {
    font-size: 38px;
    color: var(--inv-primary-dark);
}

.inv-dropzone-text {
    font-size: 0.86rem;
    font-weight: 600;
    color: var(--inv-text-strong);
}

.inv-dropzone-hint {
    font-size: 0.74rem;
    color: var(--inv-text-muted);
}

.inv-import-preview-wrapper {
    margin-top: 8px;
    border: 1px solid var(--inv-border-subtle);
    border-radius: 8px;
    overflow: hidden;
}

.inv-import-preview-summary {
    background: #f8fafc;
    padding: 8px 12px;
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--inv-text-strong);
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid var(--inv-border-subtle);
}

.inv-import-preview-table-box {
    max-height: 180px;
    overflow-y: auto;
    overflow-x: auto;
}

.inv-import-preview-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.74rem;
}

.inv-import-preview-table th {
    background: #f1f5f9;
    padding: 6px 10px;
    text-align: left;
    font-weight: 600;
    color: var(--inv-text-medium);
    border-bottom: 1px solid #e2e8f0;
    white-space: nowrap;
}

.inv-import-preview-table td {
    padding: 6px 10px;
    border-bottom: 1px solid #f1f5f9;
    color: var(--inv-text-strong);
    white-space: nowrap;
}

/* Soft Toast Component */
.inv-toast {
    position: fixed;
    bottom: 24px;
    right: 24px;
    background: #0f172a;
    color: #ffffff;
    padding: 12px 20px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    font-size: 0.83rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    z-index: 2000;
    transform: translateY(100px);
    opacity: 0;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.inv-toast.is-visible {
    transform: translateY(0);
    opacity: 1;
}

.inv-toast-icon {
    color: var(--inv-success);
    font-size: 18px;
}

/* Media Queries */
@media (max-width: 900px) {
    :root {
        --inv-drawer-width: 100vw;
    }
    .inv-header-bar {
        flex-direction: column;
        align-items: stretch;
    }
    .inv-header-actions {
        justify-content: flex-start;
    }
    .inv-form-row-2 {
        grid-template-columns: 1fr;
/* =========================================================================
   AUDIT LOGS & TIMELINE SYSTEM
   ========================================================================= */
.audit-overview-card {
    background: #ffffff;
    border: 1px solid var(--inv-border-subtle);
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
    color: var(--inv-text-muted);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    display: flex;
    align-items: center;
    gap: 5px;
}

.audit-metric-val {
    font-size: 0.85rem;
    font-weight: 700;
    color: var(--inv-text-strong);
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
    border: 1px solid var(--inv-border-subtle);
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
    border: 2px solid var(--inv-primary);
    color: var(--inv-primary-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    transform: translateX(-50%);
    z-index: 2;
}

.audit-timeline-node-icon.is-created {
    border-color: var(--inv-success);
    color: var(--inv-success);
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
    color: var(--inv-text-strong);
    line-height: 1.2;
}

.audit-user-role {
    font-size: 0.70rem;
    color: var(--inv-text-muted);
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
    color: var(--inv-text-strong);
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
    color: var(--inv-text-muted);
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
<div class="inv-page-container">

    <!-- 1. The Background Layer (Master List) -->
    <!-- Header Section: Full-width horizontal bar with page title on the left and primary buttons on the right -->
    <header class="inv-header-bar">
        <div class="inv-header-title-box">
            <div class="inv-header-icon">
                <i class="ph ph-package"></i>
            </div>
            <div>
                <h1>Item Master</h1>
                <p>Centralized catalog for managing raw materials, packaging, suppliers, units of measure (UOM), and stock pricing.</p>
            </div>
        </div>
        <div class="inv-header-actions">
            <!-- Button to Export Products to CSV -->
            <button type="button" class="inv-action-btn-secondary" onclick="exportProductsCSV()" title="Export Product Catalog to CSV">
                <i class="ph ph-file-arrow-down"></i>
                <span>Export</span>
            </button>

            <!-- Button to Import Products from CSV -->
            <button type="button" class="inv-action-btn-secondary" onclick="openImportModal()" title="Import Products from CSV">
                <i class="ph ph-file-arrow-up"></i>
                <span>Import</span>
            </button>

            <!-- Button to Add New Category -->
            <button type="button" class="inv-action-btn-secondary" onclick="openNewCategoryModal()" title="Create New Category Group">
                <i class="ph ph-folder-plus"></i>
                <span>Add Category</span>
            </button>

            <!-- Button for Add Products -->
            <button type="button" class="inv-action-btn-primary" id="btnOpenNewProduct" onclick="openProductDrawer('new')" title="Add New Product (F2)">
                <i class="ph ph-plus-circle"></i>
                <span>Add Product</span>
                <span class="inv-hotkey-badge">F2</span>
            </button>
        </div>
    </header>

    <!-- Filter Bar: Search input field, all categories horizontal scroll pills, and global collapse/expand -->
    <section class="inv-filter-bar">
        <div class="inv-filter-left">
            <div class="inv-search-wrapper" style="width: 360px;">
                <i class="ph ph-magnifying-glass inv-search-icon"></i>
                <input type="text" id="invMasterSearch" class="inv-search-input" placeholder="Search SKU, name, barcode, supplier..." autocomplete="off">
                <kbd class="inv-search-kbd">/</kbd>
            </div>
        </div>

        <div class="inv-filter-right">
            <!-- Accordion Expand / Collapse All Toggle -->
            <button type="button" class="inv-action-btn-secondary" id="btnToggleAllAccordion" onclick="toggleAllAccordions()" style="padding: 6px 12px; font-size: 0.77rem;">
                <i class="ph ph-arrows-in-line-vertical" id="iconToggleAll"></i>
                <span id="textToggleAll">Collapse All</span>
            </button>

            <div class="inv-filter-stats">
                <span id="filteredItemCount">12 Products</span>
            </div>
        </div>
    </section>

    <!-- Grouped Data Grid: Multi-column table partitioned by Accordion Group Headers with horizontal scroll -->
    <div class="inv-grid-card">
        <div class="inv-table-responsive" id="masterTableResponsiveContainer">
            <table class="inv-master-table" id="masterProductTable">
                <colgroup id="masterTableColgroup">
                    <!-- Populated dynamically with col widths matching active columns + Action column -->
                </colgroup>
                <thead>
                    <tr id="masterTableTheadRow">
                        <!-- Populated dynamically with active columns + Action column with Filter Icon trigger -->
                    </tr>
                </thead>
                <tbody id="masterProductTbody">
                    <!-- Populated dynamically via JS with Accordion Category Headers & Nested Product Rows -->
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- 2. The Foreground Layer (Slide-out Detail Drawer) -->
<div class="inv-drawer-backdrop" id="drawerBackdrop" onclick="closeProductDrawer()"></div>

<aside class="inv-drawer-container" id="productDetailDrawer" role="dialog" aria-modal="true" aria-labelledby="drawerTitle">
    <!-- Panel Header: Title on left, Close (X) icon on right -->
    <div class="inv-drawer-header">
        <div class="inv-drawer-title-box">
            <div class="inv-drawer-header-icon" id="drawerIcon">
                <i class="ph ph-tag"></i>
            </div>
            <div>
                <h2 class="inv-drawer-title" id="drawerTitle">Edit Product</h2>
                <p class="inv-drawer-subtitle" id="drawerSubtitle">SKU: PRD-1001 &bull; Active Item</p>
            </div>
        </div>
        <button type="button" class="inv-drawer-close-btn" onclick="closeProductDrawer()" title="Close Drawer (Esc)">
            <i class="ph ph-x"></i>
        </button>
    </div>

    <!-- Primary Navigation: Horizontal Tab Bar -->
    <nav class="inv-drawer-nav" id="drawerPrimaryNav">
        <button type="button" class="inv-drawer-tab-btn active" data-tab="tab-general">
            <i class="ph ph-info"></i>
            <span>General Info</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-inventory">
            <i class="ph ph-warehouse"></i>
            <span>Inventory & Units</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-pricing">
            <i class="ph ph-currency-circle-dollar"></i>
            <span>Pricing & Suppliers</span>
        </button>
        <button type="button" class="inv-drawer-tab-btn" data-tab="tab-audit">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Audit Logs</span>
        </button>
    </nav>

    <!-- Form Content (Vertical Stack) -->
    <div class="inv-drawer-body">
        <form id="productForm" onsubmit="saveProductChanges(event)">
            <input type="hidden" id="formProductId" value="">

            <!-- Tab 1: General Info -->
            <div class="drawer-tab-pane active" id="pane-tab-general">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formSku">SKU Code *</label>
                            <input type="text" id="formSku" class="inv-form-input" required placeholder="e.g. BEV-001">
                        </div>
                        <div class="inv-form-group">
                            <label for="formBarcode">Barcode (UPC / EAN)</label>
                            <input type="text" id="formBarcode" class="inv-form-input" placeholder="e.g. 4800123456789">
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formName">Product Name *</label>
                        <input type="text" id="formName" class="inv-form-input" required placeholder="e.g. Signature Spanish Latte">
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formCategory">Category *</label>
                            <div class="inv-custom-select-group">
                                <select id="formCategory" class="inv-form-select" required onchange="handleDrawerCategoryChange(this.value)">
                                    <!-- Categories populated dynamically -->
                                </select>
                                <button type="button" class="inv-btn-quick-create" onclick="openEditCategoryModal(document.getElementById('formCategory').value)" title="Edit selected category visuals and name" style="padding: 0 8px;">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <button type="button" class="inv-btn-quick-create" onclick="openNewCategoryModal()" title="Add new product category">
                                    <i class="ph ph-plus"></i>
                                    <span>New</span>
                                </button>
                            </div>
                        </div>
                        <div class="inv-form-group">
                            <label for="formSubcategory">Sub-category</label>
                            <div style="display: flex; gap: 6px;">
                                <select id="formSubcategorySelect" class="inv-form-select" onchange="handleSubcategorySelectChange(this.value)" style="flex: 1;">
                                    <!-- Dynamically populated with unique existing subcategories -->
                                </select>
                                <input type="text" id="formSubcategory" class="inv-form-input" placeholder="Custom subcategory..." style="flex: 1; display: none;">
                                <button type="button" class="inv-btn-secondary" id="btnToggleCustomSubcat" onclick="toggleCustomSubcategoryInput()" style="padding: 0 10px; font-size: 0.76rem;" title="Toggle new/existing subcategory">
                                    <i class="ph ph-pencil-simple-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Switched place: Pack Size / Spec FIRST (numbers only), then UOM -->
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formPackSize">Pack Size / Spec (Numbers Only) *</label>
                            <input type="number" step="any" min="0" id="formPackSize" class="inv-form-input" required placeholder="e.g. 16, 500, 1" onkeypress="return /[0-9.]/.test(event.key)">
                        </div>
                        <div class="inv-form-group">
                            <label for="formUnit">Unit of Measure (UOM) *</label>
                            <select id="formUnit" class="inv-form-select" required>
                                <option value="Cup">Cup</option>
                                <option value="Plate">Plate</option>
                                <option value="Slice">Slice</option>
                                <option value="Piece">Piece</option>
                                <option value="Bottle">Bottle</option>
                                <option value="Can">Can</option>
                                <option value="Box">Box</option>
                                <option value="Pack">Pack</option>
                                <option value="Kg">Kilogram (Kg)</option>
                                <option value="Liter">Liter (L)</option>
                            </select>
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formDescription">Product Description</label>
                        <textarea id="formDescription" class="inv-form-textarea" placeholder="Detailed product specifications, notes, or serving instructions..."></textarea>
                    </div>

                    <div class="inv-form-group">
                        <label for="formAllergens">Allergen / Dietary Tags (comma separated)</label>
                        <input type="text" id="formAllergens" class="inv-form-input" placeholder="e.g. Dairy, Gluten Free, Vegan, Nut Free">
                    </div>

                    <!-- Product Operational Flags & BOM Rules -->
                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formCanBeSold" checked>
                            <div>
                                <div class="inv-checkbox-title">Can be Sold (POS / Sales Menu)</div>
                                <div class="inv-checkbox-desc">Makes this item available for ordering on POS and online catalog.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formCanBePurchased" checked>
                            <div>
                                <div class="inv-checkbox-title">Can be Purchased (Procurement)</div>
                                <div class="inv-checkbox-desc">Allows this item to be ordered from suppliers and added to POs.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formTrackStock" checked>
                            <div>
                                <div class="inv-checkbox-title">Track Physical Stock</div>
                                <div class="inv-checkbox-desc">Maintains on-hand balance, low-stock alerts, and shows in Stocks Overview for counting/receiving.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formHasBom">
                            <div>
                                <div class="inv-checkbox-title">Explode Recipe / BOM on Sale</div>
                                <div class="inv-checkbox-desc">When sold on POS, deducts sub-ingredients from Item Master instead of deducting this item directly.</div>
                            </div>
                        </label>

                        <label class="inv-checkbox-card">
                            <input type="checkbox" id="formIsActive" checked>
                            <div>
                                <div class="inv-checkbox-title">Active Product Status</div>
                                <div class="inv-checkbox-desc">Visible across active inventory catalog and reporting.</div>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Inventory & Units (Includes Nested Content Section stretched with pagination) -->
            <div class="drawer-tab-pane" id="pane-tab-inventory" style="display: none; height: 100%;">
                <div style="display: flex; flex-direction: column; gap: 16px; height: 100%;">
                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formReorderPoint">Global Reorder Point</label>
                            <input type="number" id="formReorderPoint" class="inv-form-input" min="0" value="15">
                        </div>
                        <div class="inv-form-group">
                            <label for="formTargetStock">Target Stock Level</label>
                            <input type="number" id="formTargetStock" class="inv-form-input" min="0" value="50">
                        </div>
                    </div>

                    <!-- Nested Content Section: Secondary Tabs + Sub-Table stretched -->
                    <div class="inv-nested-section" style="display: flex; flex-direction: column; flex: 1; min-height: 380px;">
                        <div class="inv-subtabs-nav" id="drawerSubtabsNav">
                            <button type="button" class="inv-subtab-btn active" data-subtab="sub-branch">Branch Stock Levels</button>
                            <button type="button" class="inv-subtab-btn" data-subtab="sub-movements">Recent Ledger Log</button>
                        </div>

                        <!-- Sub-table: Branch Stock Levels with pagination -->
                        <div class="inv-subtable-wrapper" id="subpane-sub-branch" style="flex: 1; min-height: 290px; max-height: 360px; overflow-y: auto;">
                            <table class="inv-subtable">
                                <thead>
                                    <tr>
                                        <th>Branch Location</th>
                                        <th class="th-right">On Hand</th>
                                        <th class="th-right">Reserved</th>
                                        <th class="th-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody id="branchStockSubtableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Branch stock pagination -->
                        <div class="inv-subtable-pagination" id="branchStockPagination">
                            <span id="branchStockPageInfo">Page 1 of 1</span>
                            <div class="inv-subtable-pagination-nav">
                                <button type="button" class="inv-subtable-page-btn" id="btnBranchPrev" onclick="navigateBranchPage(-1)">
                                    <i class="ph ph-caret-left"></i>
                                </button>
                                <button type="button" class="inv-subtable-page-btn" id="btnBranchNext" onclick="navigateBranchPage(1)">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Sub-table: Recent Ledger Log with pagination -->
                        <div class="inv-subtable-wrapper" id="subpane-sub-movements" style="display: none; flex: 1; min-height: 290px; max-height: 360px; overflow-y: auto;">
                            <table class="inv-subtable">
                                <thead>
                                    <tr>
                                        <th>Date & Ref</th>
                                        <th>Type</th>
                                        <th class="th-right">Qty</th>
                                        <th class="th-right">Balance</th>
                                    </tr>
                                </thead>
                                <tbody id="ledgerLogSubtableBody">
                                    <!-- Populated dynamically via JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Ledger log pagination -->
                        <div class="inv-subtable-pagination" id="ledgerLogPagination" style="display: none;">
                            <span id="ledgerLogPageInfo">Page 1 of 1</span>
                            <div class="inv-subtable-pagination-nav">
                                <button type="button" class="inv-subtable-page-btn" id="btnLedgerPrev" onclick="navigateLedgerPage(-1)">
                                    <i class="ph ph-caret-left"></i>
                                </button>
                                <button type="button" class="inv-subtable-page-btn" id="btnLedgerNext" onclick="navigateLedgerPage(1)">
                                    <i class="ph ph-caret-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Pricing & Suppliers (Column Chart for last 3 mos + Cost Price auto-calculation + Supplier Prices list) -->
            <div class="drawer-tab-pane" id="pane-tab-pricing" style="display: none;">
                <div style="display: flex; flex-direction: column; gap: 16px;">
                    
                    <!-- Column Chart: Last 3 Months Price History -->
                    <div class="inv-pricing-chart-card">
                        <div class="inv-pricing-chart-header">
                            <div class="inv-pricing-chart-title">
                                <i class="ph ph-chart-bar" style="color: var(--inv-primary-dark); font-size: 16px;"></i>
                                <span>Price Trend History (Last 3 Months)</span>
                            </div>
                            <span class="inv-pricing-chart-badge" id="chartMonthsBadge">Jul - Sep 2026</span>
                        </div>
                        <div class="inv-pricing-chart-canvas-wrapper">
                            <canvas id="priceHistoryChart"></canvas>
                        </div>
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <label for="formCostPrice">Cost Price (PHP) *</label>
                                <span style="font-size: 0.68rem; color: var(--inv-primary-dark); font-weight: 600;" title="Lowest supplier price + 5% markup">Lowest + 5%</span>
                            </div>
                            <input type="number" step="0.01" id="formCostPrice" class="inv-form-input" required placeholder="0.00">
                        </div>
                        <div class="inv-form-group">
                            <label for="formSellingPrice">Selling Price (PHP) *</label>
                            <input type="number" step="0.01" id="formSellingPrice" class="inv-form-input" required placeholder="0.00">
                        </div>
                    </div>

                    <div class="inv-form-row-2">
                        <div class="inv-form-group">
                            <label for="formProfitMargin">Margin / Markup %</label>
                            <input type="text" id="formProfitMargin" class="inv-form-input" readonly style="background: #f8fafc; font-weight: 700; color: var(--inv-success);">
                        </div>
                        <div class="inv-form-group">
                            <label for="formTaxRate">Tax Rate</label>
                            <select id="formTaxRate" class="inv-form-select">
                                <option value="12% VAT">12% VAT Inclusive</option>
                                <option value="0% Exempt">0% VAT Exempt</option>
                                <option value="Zero-Rated">Zero-Rated</option>
                            </select>
                        </div>
                    </div>

                    <div class="inv-form-group">
                        <label for="formSupplier">Primary Supplier</label>
                        <input type="text" id="formSupplier" class="inv-form-input" placeholder="e.g. Power Mac Roasters, Golden Grains, Fresh Dairy Corp">
                    </div>

                    <!-- Supplier Quotes & Recent Price Updates List -->
                    <div class="inv-supplier-list-card">
                        <div class="inv-supplier-list-header">
                            <span>Supplier Price Benchmarks & Updates</span>
                            <span style="font-size: 0.68rem; color: var(--inv-text-muted);">Recent Quotes</span>
                        </div>
                        <div class="inv-supplier-list-body" id="supplierPricesListBody">
                            <!-- Dynamically populated with supplier price rows and recent update dates -->
                        </div>
                    </div>

                </div>
            </div>

            <!-- Tab 4: Audit Logs -->
            <div class="drawer-tab-pane" id="pane-tab-audit" style="display: none;">
                <div id="productAuditLogsContainer" style="display: flex; flex-direction: column; gap: 14px;">
                    <!-- Dynamically populated by renderProductAuditLogs -->
                </div>
            </div>
        </form>
    </div>

    <!-- Panel Footer: Fixed bottom section containing two right-aligned action buttons -->
    <footer class="inv-drawer-footer">
        <button type="button" class="inv-btn-secondary" onclick="closeProductDrawer()">Cancel</button>
        <button type="button" class="inv-btn-primary" onclick="triggerSaveForm()">
            <i class="ph ph-floppy-disk"></i>
            <span id="btnSaveText">Save Changes</span>
        </button>
    </footer>
</aside>

<!-- Modal: Add / Edit Product Category -->
<div class="inv-modal-backdrop" id="newCategoryModal">
    <div class="inv-modal-card">
        <div class="inv-modal-header">
            <h3 class="inv-modal-title">
                <i class="ph ph-folder-plus" id="modalCategoryHeaderIcon" style="color: var(--inv-primary-dark); font-size: 20px;"></i>
                <span id="modalCategoryTitle">Add Product Category</span>
            </h3>
            <button type="button" class="inv-drawer-close-btn" onclick="closeNewCategoryModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <form onsubmit="handleCreateCategory(event)">
            <input type="hidden" id="modalCategoryOriginalName" value="">
            <div class="inv-modal-body">
                <div class="inv-form-group">
                    <label for="modalCategoryName">Category Name *</label>
                    <div class="inv-cat-input-wrapper">
                        <input type="text" id="modalCategoryName" class="inv-form-input" required autocomplete="off" placeholder="e.g. Appetizers, Bottled Drinks, Packaging" oninput="handleCategoryInputCheck(this.value)">
                        
                        <!-- Live Search / Existing Categories Dropdown -->
                        <div class="inv-cat-autocomplete-dropdown" id="catAutocompleteDropdown">
                            <div class="inv-cat-dropdown-header">Existing Categories Matching</div>
                            <div id="catAutocompleteList"></div>
                        </div>
                    </div>
                    <!-- Duplicate warning banner -->
                    <div class="inv-cat-duplicate-warning" id="catDuplicateWarning">
                        <i class="ph ph-warning-circle" style="font-size: 16px;"></i>
                        <span id="catDuplicateMsg">A category with this name already exists!</span>
                    </div>
                </div>
                <div class="inv-form-group">
                    <label for="modalCategoryDesc">Category Info / Description</label>
                    <textarea id="modalCategoryDesc" class="inv-form-textarea" style="min-height: 60px;" placeholder="e.g. Cold bar ingredients, syrups, and beverage packaging"></textarea>
                </div>

                <!-- Category Icon Selector -->
                <div class="inv-form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label>Category Icon *</label>
                        <span id="selectedIconLabel" style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 600;">Coffee / Drink</span>
                    </div>
                    <input type="hidden" id="modalCategoryIcon" value="ph-tag">
                    <div class="inv-cat-icon-grid" id="catIconGrid">
                        <!-- Populated dynamically via JS renderCategoryIconGrid() -->
                    </div>
                </div>

                <!-- Category Color Palette Selector -->
                <div class="inv-form-group">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label>Category Color Palette *</label>
                        <span id="selectedColorLabel" style="font-size: 0.70rem; color: var(--inv-primary-dark); font-weight: 600;">Purple / Violet</span>
                    </div>
                    <input type="hidden" id="modalCategoryColor" value="purple">
                    <div class="inv-cat-color-grid" id="catColorGrid">
                        <!-- Populated dynamically via JS renderCategoryColorGrid() -->
                    </div>
                </div>

                <!-- Live Badge Preview -->
                <div class="inv-form-group">
                    <label>Badge Preview</label>
                    <div class="inv-cat-preview-box">
                        <span style="font-size: 0.74rem; color: var(--inv-text-muted);">Preview in catalog & stocks:</span>
                        <div class="inv-cat-preview-pill" id="catLivePreviewBadge">
                            <i id="previewBadgeIcon" class="ph ph-tag"></i>
                            <span id="previewBadgeText">New Category</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="inv-modal-footer">
                <button type="button" class="inv-btn-secondary" onclick="closeNewCategoryModal()">Cancel</button>
                <button type="submit" class="inv-btn-primary" id="btnSaveCategory">
                    <i class="ph ph-check"></i>
                    <span id="btnSaveCategoryText">Save Category</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Import Products from CSV -->
<div class="inv-modal-backdrop" id="importProductsModal">
    <div class="inv-modal-card inv-modal-lg">
        <div class="inv-modal-header">
            <h3 class="inv-modal-title">
                <i class="ph ph-file-arrow-up" style="color: var(--inv-primary-dark); font-size: 20px;"></i>
                <span>Import Products Catalog</span>
            </h3>
            <button type="button" class="inv-drawer-close-btn" onclick="closeImportModal()">
                <i class="ph ph-x"></i>
            </button>
        </div>
        <div class="inv-modal-body">
            <div class="inv-dropzone" id="importDropzone" onclick="document.getElementById('importFileInput').click()">
                <i class="ph ph-cloud-arrow-up inv-dropzone-icon"></i>
                <div class="inv-dropzone-text">Click or drag & drop a CSV file here</div>
                <div class="inv-dropzone-hint">Supports columns: SKU, Name, Barcode, Category, Subcategory, UOM, Cost, Selling Price, Status</div>
                <input type="file" id="importFileInput" accept=".csv,text/csv" style="display:none;" onchange="handleImportFileSelect(event)">
            </div>

            <!-- Preview box populated once file is selected -->
            <div id="importPreviewWrapper" class="inv-import-preview-wrapper" style="display: none;">
                <div class="inv-import-preview-summary">
                    <span id="importPreviewCount">0 products parsed</span>
                    <button type="button" class="inv-col-quick-link" onclick="downloadSampleCSV()">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Sample CSV</span>
                    </button>
                </div>
                <div class="inv-import-preview-table-box">
                    <table class="inv-import-preview-table" id="importPreviewTable">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Selling Price</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="importPreviewTbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="inv-modal-footer">
            <button type="button" class="inv-btn-secondary" onclick="closeImportModal()">Cancel</button>
            <button type="button" class="inv-col-quick-link" onclick="downloadSampleCSV()" style="margin-right: auto;">
                <i class="ph ph-file-text"></i>
                <span>Sample Template</span>
            </button>
            <button type="button" class="inv-btn-primary" id="btnConfirmImport" onclick="confirmImportProducts()" disabled>
                <i class="ph ph-check"></i>
                <span>Import Products</span>
            </button>
        </div>
    </div>
</div>

<!-- Soft Toast Notification -->
<div class="inv-toast" id="invToast">
    <i class="ph ph-check-circle inv-toast-icon"></i>
    <span id="invToastMsg">Changes saved successfully</span>
</div>

@push('scripts')
<script>
/**
 * Product Management Module Interactive Controller
 * Column Filter Triggered by Filter Icon in Action Header, Horizontal Scroll, Full Category Listing
 */
(function() {
    'use strict';

    // 1. Column Definition Matrix - All aligned to Left for crisp visual scanning
    const ALL_COLUMNS = [
        { id: 'sku', label: 'SKU', default: true, align: 'left', width: '120px' },
        { id: 'product', label: 'Barcode / Product Name', default: true, align: 'left', minWidth: '220px' },
        { id: 'description', label: 'Product Description', default: true, align: 'left', minWidth: '180px' },
        { id: 'category', label: 'Category', default: true, align: 'left', width: '130px' },
        { id: 'subcategory', label: 'Sub-category', default: true, align: 'left', width: '130px' },
        { id: 'uom', label: 'Unit of Measure (UOM) / Pack Size', default: true, align: 'left', width: '160px' },
        { id: 'cost_price', label: 'Cost Price', default: true, align: 'left', width: '120px' },
        { id: 'selling_price', label: 'Selling Price', default: true, align: 'left', width: '120px' },
        { id: 'margin', label: 'Margin / Markup %', default: true, align: 'left', width: '130px' },
        { id: 'tax_rate', label: 'Tax Rate', default: true, align: 'left', width: '110px' },
        { id: 'supplier', label: 'Primary Supplier', default: true, align: 'left', minWidth: '160px' },
        { id: 'allergens', label: 'Allergen / Dietary Tags', default: true, align: 'left', minWidth: '160px' },
        { id: 'status', label: 'Status (Active/Inactive)', default: true, align: 'left', width: '140px' }
    ];

    // Saved Column Visibility Settings
    let activeColumnIds = JSON.parse(localStorage.getItem('rms_product_columns')) || ALL_COLUMNS.map(c => c.id);

    // Saved Modular Column Widths Settings (Persisted in localStorage)
    let savedColumnWidths = JSON.parse(localStorage.getItem('rms_product_column_widths')) || {};

    // Helper: Retrieve active or saved width for any column
    function getColumnWidth(col) {
        if (savedColumnWidths[col.id]) {
            return savedColumnWidths[col.id];
        }
        return col.width || col.minWidth || '140px';
    }

    // Modular Visual Category Palette Dictionary
    const CATEGORY_PALETTES = {
        purple:  { label: 'Purple / Violet', bg: '#faf5ff', text: '#7e22ce', border: '#e9d5ff', dot: '#a855f7' },
        amber:   { label: 'Amber / Gold',    bg: '#fffbeb', text: '#b45309', border: '#fde68a', dot: '#f59e0b' },
        rose:    { label: 'Pink / Rose',     bg: '#fdf2f8', text: '#be185d', border: '#fbcfe8', dot: '#ec4899' },
        emerald: { label: 'Emerald / Green', bg: '#f0fdf4', text: '#15803d', border: '#bbf7d0', dot: '#10b981' },
        blue:    { label: 'Blue / Sky',      bg: '#eff6ff', text: '#1d4ed8', border: '#bfdbfe', dot: '#3b82f6' },
        orange:  { label: 'Orange / Coral',  bg: '#fff7ed', text: '#c2410c', border: '#fed7aa', dot: '#f97316' },
        teal:    { label: 'Teal / Cyan',     bg: '#f0fdfa', text: '#0f766e', border: '#99f6e4', dot: '#14b8a6' },
        indigo:  { label: 'Indigo / Navy',   bg: '#eef2ff', text: '#4338ca', border: '#c7d2fe', dot: '#6366f1' },
        slate:   { label: 'Slate / Neutral', bg: '#f8fafc', text: '#475569', border: '#cbd5e1', dot: '#64748b' }
    };

    const CATEGORY_ICONS = [
        { id: 'ph-coffee', label: 'Coffee / Drink' },
        { id: 'ph-fork-knife', label: 'Fork & Knife' },
        { id: 'ph-cookie', label: 'Cookie / Pastry' },
        { id: 'ph-plant', label: 'Plant / Raw' },
        { id: 'ph-box', label: 'Box / Packaging' },
        { id: 'ph-drop', label: 'Drop / Syrup' },
        { id: 'ph-wine', label: 'Wine / Bar' },
        { id: 'ph-pizza', label: 'Pizza / Snack' },
        { id: 'ph-package', label: 'Package / Goods' },
        { id: 'ph-t-shirt', label: 'Merchandise' },
        { id: 'ph-wrench', label: 'Tools / Hardware' },
        { id: 'ph-tag', label: 'Tag / General' }
    ];

    const DEFAULT_CATEGORY_META = {
        'Beverages': { icon: 'ph-coffee', color: 'purple' },
        'Main Course': { icon: 'ph-fork-knife', color: 'amber' },
        'Pastries & Desserts': { icon: 'ph-cookie', color: 'rose' },
        'Raw Ingredients': { icon: 'ph-plant', color: 'emerald' },
        'Appetizers & Starters': { icon: 'ph-pizza', color: 'orange' },
        'Side Dishes': { icon: 'ph-fork-knife', color: 'amber' },
        'Packaging & Disposables': { icon: 'ph-box', color: 'blue' },
        'Syrups & Flavors': { icon: 'ph-drop', color: 'orange' },
        'Uncategorized': { icon: 'ph-tag', color: 'slate' }
    };

    function getCategoryMeta(catName) {
        if (window.AppStore && window.AppStore.categoryMeta && window.AppStore.categoryMeta[catName]) {
            return window.AppStore.categoryMeta[catName];
        }
        if (DEFAULT_CATEGORY_META[catName]) {
            return DEFAULT_CATEGORY_META[catName];
        }
        return { icon: 'ph-tag', color: 'purple' };
    }

    function getCategoryPalette(colorKey) {
        return CATEGORY_PALETTES[colorKey] || CATEGORY_PALETTES.purple;
    }

    // Initial Mock Catalog Data - Includes multiple categories to showcase horizontal scrolling
    const INITIAL_CATEGORIES = [
        'Beverages', 
        'Main Course', 
        'Pastries & Desserts', 
        'Raw Ingredients', 
        'Appetizers & Starters', 
        'Side Dishes', 
        'Packaging & Disposables', 
        'Syrups & Flavors'
    ];

    const INITIAL_PRODUCTS = [
        {
            id: 1,
            sku: 'BEV-001',
            name: 'Signature Spanish Latte',
            barcode: '4800019283711',
            description: 'Espresso with sweetened condensed milk and silky textured steamed milk.',
            category: 'Beverages',
            subcategory: 'Espresso Based',
            unit: 'Cup',
            packSize: '16 oz Regular',
            costPrice: 45.00,
            sellingPrice: 150.00,
            taxRate: '12% VAT',
            supplier: 'Power Mac Roasters',
            allergens: ['Dairy'],
            isActive: true,
            trackStock: true,
            reorderPoint: 20,
            targetStock: 80,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 42, reserved: 2, reorder: 20 },
                { name: 'Makati Greenbelt Kiosk', onHand: 18, reserved: 0, reorder: 15 },
                { name: 'BGC High Street Branch', onHand: 35, reserved: 4, reorder: 20 }
            ],
            ledger: [
                { ref: 'PO-2026-0041', type: 'Stock In', qty: '+50', balance: 95 },
                { ref: 'POS-TX-9902', type: 'Sale', qty: '-2', balance: 93 }
            ]
        },
        {
            id: 2,
            sku: 'BEV-002',
            name: 'Iced Americano Grande',
            barcode: '4800019283728',
            description: 'Rich double espresso shot over cold filtered water and ice cubes.',
            category: 'Beverages',
            subcategory: 'Iced Coffee',
            unit: 'Cup',
            packSize: '20 oz Grande',
            costPrice: 28.00,
            sellingPrice: 120.00,
            taxRate: '12% VAT',
            supplier: 'Power Mac Roasters',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 60,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 55, reserved: 0, reorder: 15 },
                { name: 'Makati Greenbelt Kiosk', onHand: 24, reserved: 1, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0038', type: 'Stock In', qty: '+40', balance: 119 }
            ]
        },
        {
            id: 3,
            sku: 'BEV-003',
            name: 'Matcha Green Tea Fusion',
            barcode: '4800019283735',
            description: 'Ceremonial grade Uji matcha whisked with fresh oat milk.',
            category: 'Beverages',
            subcategory: 'Tea & Non-Coffee',
            unit: 'Cup',
            packSize: '16 oz',
            costPrice: 52.00,
            sellingPrice: 175.00,
            taxRate: '12% VAT',
            supplier: 'Kyoto Tea Imports',
            allergens: ['Oat'],
            isActive: true,
            trackStock: true,
            reorderPoint: 12,
            targetStock: 45,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 11, reserved: 0, reorder: 12 }
            ],
            ledger: [
                { ref: 'PO-2026-0030', type: 'Stock In', qty: '+25', balance: 34 }
            ]
        },
        {
            id: 4,
            sku: 'MNC-101',
            name: 'Truffle Mushroom Pasta',
            barcode: '4800028471920',
            description: 'Fettuccine in white truffle cream, shiitake and cremini mushrooms, Parmigiano Reggiano.',
            category: 'Main Course',
            subcategory: 'Pasta & Noodles',
            unit: 'Plate',
            packSize: '320g Serving',
            costPrice: 115.00,
            sellingPrice: 380.00,
            taxRate: '12% VAT',
            supplier: 'Gourmet Italia Supply',
            allergens: ['Dairy', 'Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 40,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 28, reserved: 3, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0028', type: 'Stock In', qty: '+30', balance: 50 }
            ]
        },
        {
            id: 5,
            sku: 'MNC-102',
            name: 'Crispy Pork Belly Bowl',
            barcode: '4800028471937',
            description: 'Slow-roasted crispy pork belly with garlic scallion rice and spiced vinegar glaze.',
            category: 'Main Course',
            subcategory: 'Rice Bowls',
            unit: 'Plate',
            packSize: '400g Bowl',
            costPrice: 95.00,
            sellingPrice: 295.00,
            taxRate: '12% VAT',
            supplier: 'Prime Meat Traders',
            allergens: ['Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 50,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 34, reserved: 0, reorder: 15 }
            ],
            ledger: [
                { ref: 'PO-2026-0021', type: 'Stock In', qty: '+40', balance: 53 }
            ]
        },
        {
            id: 6,
            sku: 'PST-201',
            name: 'Burnt Basque Cheesecake',
            barcode: '4800037190012',
            description: 'Creamy caramelized custard interior with deeply browned exterior crust.',
            category: 'Pastries & Desserts',
            subcategory: 'Cakes & Slices',
            unit: 'Slice',
            packSize: '150g Slice',
            costPrice: 55.00,
            sellingPrice: 195.00,
            taxRate: '12% VAT',
            supplier: 'Artisan Bakehouse Co.',
            allergens: ['Dairy', 'Eggs'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 35,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 14, reserved: 1, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0035', type: 'Stock In', qty: '+15', balance: 33 }
            ]
        },
        {
            id: 7,
            sku: 'PST-202',
            name: 'French Butter Croissant',
            barcode: '4800037190029',
            description: 'Traditional laminated dough baked fresh daily with Lescure French butter.',
            category: 'Pastries & Desserts',
            subcategory: 'Viennoiserie',
            unit: 'Piece',
            packSize: '85g Piece',
            costPrice: 32.00,
            sellingPrice: 110.00,
            taxRate: '12% VAT',
            supplier: 'Artisan Bakehouse Co.',
            allergens: ['Dairy', 'Gluten'],
            isActive: true,
            trackStock: true,
            reorderPoint: 20,
            targetStock: 60,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 38, reserved: 0, reorder: 20 }
            ],
            ledger: [
                { ref: 'PO-2026-0040', type: 'Stock In', qty: '+50', balance: 85 }
            ]
        },
        {
            id: 8,
            sku: 'RAW-301',
            name: 'Barista Whole Fresh Milk',
            barcode: '4800049102911',
            description: 'High-protein fresh cow milk formulated for microfoam and latte art.',
            category: 'Raw Ingredients',
            subcategory: 'Dairy & Liquids',
            unit: 'Liter',
            packSize: '1 Liter Tetra',
            costPrice: 85.00,
            sellingPrice: 120.00,
            taxRate: '0% Exempt',
            supplier: 'Fresh Dairy Corp',
            allergens: ['Dairy'],
            isActive: true,
            trackStock: true,
            reorderPoint: 40,
            targetStock: 150,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 88, reserved: 0, reorder: 40 }
            ],
            ledger: [
                { ref: 'PO-2026-0043', type: 'Stock In', qty: '+100', balance: 185 }
            ]
        },
        {
            id: 9,
            sku: 'RAW-302',
            name: 'Arabica Espresso Beans (Single Origin)',
            barcode: '4800049102928',
            description: 'Benguet & Mt. Apo arabica blend, tasting notes of dark cocoa and molasses.',
            category: 'Raw Ingredients',
            subcategory: 'Coffee Beans',
            unit: 'Kg',
            packSize: '1 Kg Bag',
            costPrice: 550.00,
            sellingPrice: 750.00,
            taxRate: '0% Exempt',
            supplier: 'Highland Growers Collective',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 15,
            targetStock: 50,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 24, reserved: 0, reorder: 15 }
            ],
            ledger: [
                { ref: 'PO-2026-0039', type: 'Stock In', qty: '+30', balance: 51 }
            ]
        },
        {
            id: 10,
            sku: 'APP-401',
            name: 'Crispy Calamari Fritti',
            barcode: '4800051283720',
            description: 'Tender squid rings lightly dusted in seasoned flour with caper garlic aioli.',
            category: 'Appetizers & Starters',
            subcategory: 'Deep Fried',
            unit: 'Plate',
            packSize: '250g Serving',
            costPrice: 120.00,
            sellingPrice: 280.00,
            taxRate: '12% VAT',
            supplier: 'Pacific Seafood Logistics',
            allergens: ['Gluten', 'Seafood'],
            isActive: true,
            trackStock: true,
            reorderPoint: 10,
            targetStock: 30,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 16, reserved: 0, reorder: 10 }
            ],
            ledger: [
                { ref: 'PO-2026-0014', type: 'Stock In', qty: '+20', balance: 20 }
            ]
        },
        {
            id: 11,
            sku: 'PKG-501',
            name: 'Kraft Takeout Box (Medium)',
            barcode: '4800067192834',
            description: 'Eco-friendly biodegradable paperboard container with grease-resistant lining.',
            category: 'Packaging & Disposables',
            subcategory: 'Boxes & Containers',
            unit: 'Piece',
            packSize: 'Pack of 50',
            costPrice: 8.50,
            sellingPrice: 15.00,
            taxRate: '12% VAT',
            supplier: 'GreenPack Solutions',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 100,
            targetStock: 500,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 350, reserved: 0, reorder: 100 }
            ],
            ledger: [
                { ref: 'PO-2026-0044', type: 'Stock In', qty: '+300', balance: 350 }
            ]
        },
        {
            id: 12,
            sku: 'SYR-601',
            name: 'Caramel Macchiato Artisan Syrup',
            barcode: '4800078291039',
            description: 'Slow-cooked golden butter caramel syrup with roasted sugar undertones (750ml).',
            category: 'Syrups & Flavors',
            subcategory: 'Flavor Syrups',
            unit: 'Bottle',
            packSize: '750ml Bottle',
            costPrice: 290.00,
            sellingPrice: 420.00,
            taxRate: '12% VAT',
            supplier: 'Monin Specialty Distributors',
            allergens: [],
            isActive: true,
            trackStock: true,
            reorderPoint: 8,
            targetStock: 25,
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 14, reserved: 0, reorder: 8 }
            ],
            ledger: [
                { ref: 'PO-2026-0037', type: 'Stock In', qty: '+15', balance: 22 }
            ]
        }
    ];

    // Reactive State Store
    window.AppStore = {
        categories: JSON.parse(localStorage.getItem('rms_product_categories')) || INITIAL_CATEGORIES,
        categoryInfo: JSON.parse(localStorage.getItem('rms_category_info')) || {},
        categoryMeta: Object.assign({}, DEFAULT_CATEGORY_META, JSON.parse(localStorage.getItem('rms_category_meta')) || {}),
        products: JSON.parse(localStorage.getItem('rms_inventory_products')) || INITIAL_PRODUCTS,
        activeCategory: 'ALL',
        searchQuery: '',
        selectedProductId: null,
        collapsedCategories: {},
        branchPage: 1,
        branchPageSize: 4,
        ledgerPage: 1,
        ledgerPageSize: 4,
        activePriceChart: null
    };

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
                        { field: 'Category', oldValue: '—', newValue: p.category },
                        { field: 'Cost Price', oldValue: '—', newValue: formatPHP(p.costPrice) },
                        { field: 'Selling Price', oldValue: '—', newValue: formatPHP(p.sellingPrice) },
                        { field: 'Has BOM', oldValue: '—', newValue: p.hasBom ? 'Yes (Recipe Active)' : 'No' }
                    ]
                }
            ];
        }
    });

    // DOM Caches
    const masterColgroup = document.getElementById('masterTableColgroup');
    const masterTheadRow = document.getElementById('masterTableTheadRow');
    const masterTbody = document.getElementById('masterProductTbody');
    const searchInput = document.getElementById('invMasterSearch');
    const filteredCountEl = document.getElementById('filteredItemCount');
    const newCategoryModal = document.getElementById('newCategoryModal');

    // Drawer Elements
    const drawerBackdrop = document.getElementById('drawerBackdrop');
    const drawerContainer = document.getElementById('productDetailDrawer');
    const drawerTitle = document.getElementById('drawerTitle');
    const drawerSubtitle = document.getElementById('drawerSubtitle');
    const drawerIcon = document.getElementById('drawerIcon');
    const btnSaveText = document.getElementById('btnSaveText');
    const toastEl = document.getElementById('invToast');
    const toastMsgEl = document.getElementById('invToastMsg');

    // Drawer Inputs
    const formId = document.getElementById('formProductId');
    const formSku = document.getElementById('formSku');
    const formBarcode = document.getElementById('formBarcode');
    const formName = document.getElementById('formName');
    const formCategory = document.getElementById('formCategory');
    const formSubcategory = document.getElementById('formSubcategory');
    const formSubcategorySelect = document.getElementById('formSubcategorySelect');
    const formUnit = document.getElementById('formUnit');
    const formPackSize = document.getElementById('formPackSize');
    const formDescription = document.getElementById('formDescription');
    const formAllergens = document.getElementById('formAllergens');
    const formCanBeSold = document.getElementById('formCanBeSold');
    const formCanBePurchased = document.getElementById('formCanBePurchased');
    const formHasBom = document.getElementById('formHasBom');
    const formIsActive = document.getElementById('formIsActive');
    const formTrackStock = document.getElementById('formTrackStock');
    const formReorderPoint = document.getElementById('formReorderPoint');
    const formTargetStock = document.getElementById('formTargetStock');
    const formCostPrice = document.getElementById('formCostPrice');
    const formSellingPrice = document.getElementById('formSellingPrice');
    const formProfitMargin = document.getElementById('formProfitMargin');
    const formTaxRate = document.getElementById('formTaxRate');
    const formSupplier = document.getElementById('formSupplier');
    const branchSubtableBody = document.getElementById('branchStockSubtableBody');
    const ledgerSubtableBody = document.getElementById('ledgerLogSubtableBody');
    const supplierPricesListBody = document.getElementById('supplierPricesListBody');

    // Helper: Currency Formatter
    function formatPHP(amount) {
        const num = Number(amount);
        if (isNaN(num)) return '₱0.00';
        const isNeg = num < 0;
        const formatted = Math.abs(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return isNeg ? `-₱${formatted}` : `₱${formatted}`;
    }

    // Helper: Toast
    function showToast(message) {
        if (!toastEl) return;
        toastMsgEl.textContent = message;
        toastEl.classList.add('is-visible');
        setTimeout(() => toastEl.classList.remove('is-visible'), 3200);
    }

    // Calculation: Profit Margin & Markup
    function updateMarginCalculation() {
        const cost = parseFloat(formCostPrice.value) || 0;
        const selling = parseFloat(formSellingPrice.value) || 0;
        if (selling <= 0) {
            formProfitMargin.value = '0.0%';
            return;
        }
        const profit = selling - cost;
        const marginPct = (profit / selling) * 100;
        const markupPct = cost > 0 ? ((profit / cost) * 100) : 0;
        formProfitMargin.value = `${marginPct.toFixed(1)}% margin (${markupPct.toFixed(1)}% markup)`;
    }

    formCostPrice.addEventListener('input', updateMarginCalculation);
    formSellingPrice.addEventListener('input', updateMarginCalculation);

    // =========================================================================
    // CATEGORY & SUBCATEGORY DROPDOWNS SYNCHRONIZATION
    // =========================================================================
    function syncCategoryControls() {
        if (formCategory) {
            const currentCat = formCategory.value;
            formCategory.innerHTML = window.AppStore.categories.map(c => `<option value="${c}">${c}</option>`).join('');
            if (window.AppStore.categories.includes(currentCat)) {
                formCategory.value = currentCat;
            }
        }
        syncSubcategoryDropdown(formCategory ? formCategory.value : null);
    }

    function syncSubcategoryDropdown(categoryFilter) {
        if (!formSubcategorySelect) return;
        const subcatSet = new Set();
        window.AppStore.products.forEach(p => {
            if (!categoryFilter || p.category === categoryFilter) {
                if (p.subcategory && p.subcategory.trim()) {
                    subcatSet.add(p.subcategory.trim());
                }
            }
        });

        // Add defaults if set is small
        if (subcatSet.size === 0) {
            subcatSet.add('Standard');
            subcatSet.add('Specialty');
        }

        const currentVal = formSubcategory ? formSubcategory.value : '';
        let optsHtml = Array.from(subcatSet).map(s => `<option value="${s}">${s}</option>`).join('');
        optsHtml += `<option value="__custom__">+ Enter Custom Subcategory...</option>`;
        formSubcategorySelect.innerHTML = optsHtml;

        if (currentVal && subcatSet.has(currentVal)) {
            formSubcategorySelect.value = currentVal;
            if (formSubcategory) formSubcategory.style.display = 'none';
            formSubcategorySelect.style.display = 'block';
        }
    }

    window.handleDrawerCategoryChange = function(selectedCategory) {
        syncSubcategoryDropdown(selectedCategory);
    };

    window.handleSubcategorySelectChange = function(val) {
        if (val === '__custom__') {
            formSubcategorySelect.style.display = 'none';
            formSubcategory.style.display = 'block';
            formSubcategory.value = '';
            formSubcategory.focus();
        } else {
            formSubcategory.value = val;
            formSubcategory.style.display = 'none';
            formSubcategorySelect.style.display = 'block';
        }
    };

    window.toggleCustomSubcategoryInput = function() {
        if (formSubcategory.style.display === 'none') {
            formSubcategorySelect.style.display = 'none';
            formSubcategory.style.display = 'block';
            formSubcategory.focus();
        } else {
            formSubcategory.style.display = 'none';
            formSubcategorySelect.style.display = 'block';
            if (formSubcategorySelect.value !== '__custom__') {
                formSubcategory.value = formSubcategorySelect.value;
            }
        }
    };

    // =========================================================================
    // COLUMN FILTER DROPDOWN CONTROLLER (TRIGGERED BY FILTER ICON IN ACTION HEADER)
    // =========================================================================
    window.toggleColumnFilterDropdown = function(e) {
        if (e) e.stopPropagation();
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const triggerBtn = document.getElementById('btnTableColumnFilter');
        if (!dropdown) return;

        const isOpen = dropdown.classList.contains('is-active');
        if (isOpen) {
            dropdown.classList.remove('is-active');
            if (triggerBtn) triggerBtn.classList.remove('is-active');
        } else {
            dropdown.classList.add('is-active');
            if (triggerBtn) triggerBtn.classList.add('is-active');
        }
    };

    function renderColumnDropdownList() {
        const listEl = document.getElementById('tableColumnsDropdownList');
        const counterEl = document.getElementById('tableColumnsActiveCounter');
        if (!listEl) return;

        let listHtml = '';
        ALL_COLUMNS.forEach(col => {
            const isChecked = activeColumnIds.includes(col.id);
            listHtml += `
                <div class="inv-col-dropdown-item ${isChecked ? 'is-checked' : ''}" onclick="window.handleColumnToggle('${col.id}', ${!isChecked})">
                    <label class="inv-col-checkbox-label">
                        <span class="inv-col-checkbox-custom">
                            <i class="ph ph-check" style="${isChecked ? '' : 'display:none;'}"></i>
                        </span>
                        <span title="${col.label}">${col.label}</span>
                    </label>
                    <span class="inv-col-status-badge">
                        ${isChecked ? 'Shown' : 'Hidden'}
                    </span>
                </div>
            `;
        });

        listEl.innerHTML = listHtml;
        if (counterEl) {
            counterEl.textContent = `${activeColumnIds.length} of ${ALL_COLUMNS.length} visible`;
        }
    }

    window.handleColumnToggle = function(colId, isChecked) {
        if (isChecked) {
            if (!activeColumnIds.includes(colId)) activeColumnIds.push(colId);
        } else {
            if (activeColumnIds.length <= 2) {
                showToast('At least two columns must remain visible.');
                return;
            }
            activeColumnIds = activeColumnIds.filter(id => id !== colId);
        }

        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
    };

    window.showAllColumns = function() {
        activeColumnIds = ALL_COLUMNS.map(c => c.id);
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('All columns visible');
    };

    window.resetColumnDefaults = function() {
        activeColumnIds = ALL_COLUMNS.map(c => c.id);
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('Columns reset to default');
    };

    window.showCompactColumns = function() {
        activeColumnIds = ['sku', 'product', 'category', 'selling_price', 'status'];
        localStorage.setItem('rms_product_columns', JSON.stringify(activeColumnIds));
        renderTableHeaders();
        renderMasterList();
        showToast('Compact View: 5 essential columns shown');
    };

    // Reset Modular Column Widths to system defaults
    window.resetColumnWidths = function() {
        savedColumnWidths = {};
        localStorage.removeItem('rms_product_column_widths');
        renderTableHeaders();
        renderMasterList();
        showToast('Column widths restored to defaults');
    };

    // =========================================================================
    // INTERACTIVE MODULAR COLUMN RESIZING CONTROLLER (DRAG & PERSIST)
    // =========================================================================
    window.initColumnResize = function(e, colId) {
        if (e.button !== 0) return; // Only primary mouse button
        e.preventDefault();
        e.stopPropagation();

        const resizer = e.currentTarget;
        const th = resizer.closest('th');
        if (!th) return;

        const startX = e.pageX;
        const startWidth = th.offsetWidth;
        const colDef = ALL_COLUMNS.find(c => c.id === colId);
        const minW = parseInt(colDef?.minWidth || '80', 10);
        const maxW = 600;

        resizer.classList.add('is-resizing');
        document.body.classList.add('is-column-resizing');

        const colEl = document.getElementById(`col-${colId}`);

        function onMouseMove(moveEvent) {
            moveEvent.preventDefault();
            const deltaX = moveEvent.pageX - startX;
            const newWidth = Math.max(minW, Math.min(maxW, startWidth + deltaX));
            const widthPx = `${newWidth}px`;

            // Live instant update on <col> tag and <th> element
            if (colEl) {
                colEl.style.width = widthPx;
            }
            th.style.width = widthPx;
            th.style.minWidth = widthPx;
        }

        function onMouseUp(upEvent) {
            document.removeEventListener('mousemove', onMouseMove);
            document.removeEventListener('mouseup', onMouseUp);

            resizer.classList.remove('is-resizing');
            document.body.classList.remove('is-column-resizing');

            const deltaX = upEvent.pageX - startX;
            const finalWidth = Math.max(minW, Math.min(maxW, startWidth + deltaX));
            const finalWidthPx = `${finalWidth}px`;

            // Save to state and localStorage
            savedColumnWidths[colId] = finalWidthPx;
            localStorage.setItem('rms_product_column_widths', JSON.stringify(savedColumnWidths));

            // Re-render so all <td> in tbody get updated synchronously
            renderTableHeaders();
            renderMasterList();

            showToast(`Column "${colDef ? colDef.label : colId}" width saved: ${finalWidthPx}`);
        }

        document.addEventListener('mousemove', onMouseMove);
        document.addEventListener('mouseup', onMouseUp);
    };

    // Close Dropdown on outside click
    document.addEventListener('click', function(e) {
        const dropdown = document.getElementById('tableColumnFilterDropdown');
        const triggerBtn = document.getElementById('btnTableColumnFilter');
        if (dropdown && dropdown.classList.contains('is-active')) {
            const wrapper = document.getElementById('actionHeaderWrapper');
            if (wrapper && !wrapper.contains(e.target)) {
                dropdown.classList.remove('is-active');
                if (triggerBtn) triggerBtn.classList.remove('is-active');
            }
        }
    });

    // =========================================================================
    // DYNAMIC TABLE HEADERS & ROWS RENDERING (WITH MODULAR COLUMN RESIZING)
    // =========================================================================
    function renderTableHeaders() {
        let thHtml = '';
        let colHtml = '';

        ALL_COLUMNS.forEach(col => {
            if (!activeColumnIds.includes(col.id)) return;
            const alignClass = col.align === 'right' ? 'th-right' : (col.align === 'center' ? 'th-center' : '');
            const colWidth = getColumnWidth(col);
            const style = `width: ${colWidth}; min-width: ${col.minWidth || '80px'};`;
            
            colHtml += `<col id="col-${col.id}" style="width: ${colWidth};">`;
            thHtml += `
                <th class="${alignClass}" data-col-id="${col.id}" style="${style}">
                    <span class="th-content-label">${col.label}</span>
                    <div class="inv-col-resizer" onmousedown="initColumnResize(event, '${col.id}')" title="Drag to resize column"></div>
                </th>
            `;
        });

        // Action column with Column Filter Icon trigger positioned directly after Action text
        const actionColWidth = savedColumnWidths['action'] || '130px';
        colHtml += `<col id="col-action" style="width: ${actionColWidth};">`;
        thHtml += `
            <th class="th-right th-action" data-col-id="action" style="width: ${actionColWidth};">
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

        if (masterColgroup) {
            masterColgroup.innerHTML = colHtml;
        }
        masterTheadRow.innerHTML = thHtml;
        renderColumnDropdownList();

        if (wasOpen) {
            const dropdownNow = document.getElementById('tableColumnFilterDropdown');
            const triggerBtn = document.getElementById('btnTableColumnFilter');
            if (dropdownNow) dropdownNow.classList.add('is-active');
            if (triggerBtn) triggerBtn.classList.add('is-active');
        }
    }

    function renderMasterList() {
        const query = (window.AppStore.searchQuery || '').trim().toLowerCase();
        const activeCat = window.AppStore.activeCategory;

        const filtered = window.AppStore.products.filter(item => {
            const matchesCat = (activeCat === 'ALL') || (item.category === activeCat);
            if (!matchesCat) return false;

            if (!query) return true;
            return (
                item.name.toLowerCase().includes(query) ||
                item.sku.toLowerCase().includes(query) ||
                (item.barcode && item.barcode.toLowerCase().includes(query)) ||
                (item.subcategory && item.subcategory.toLowerCase().includes(query)) ||
                (item.supplier && item.supplier.toLowerCase().includes(query)) ||
                (item.unit && item.unit.toLowerCase().includes(query)) ||
                (item.allergens && item.allergens.some(a => a.toLowerCase().includes(query)))
            );
        });

        filteredCountEl.textContent = `${filtered.length} Product${filtered.length === 1 ? '' : 's'}`;

        const totalVisibleCols = activeColumnIds.length + 1; // + 1 for action column

        if (filtered.length === 0) {
            masterTbody.innerHTML = `
                <tr>
                    <td colspan="${totalVisibleCols}">
                        <div class="inv-empty-state">
                            <i class="ph ph-package-x inv-empty-icon"></i>
                            <div style="font-weight: 600; font-size: 0.95rem; color: var(--inv-text-strong); margin-bottom: 4px;">No products match your criteria</div>
                            <div>Try adjusting your search terms or category selection.</div>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }

        const grouped = {};
        window.AppStore.categories.forEach(cat => { grouped[cat] = []; });
        filtered.forEach(prod => {
            if (!grouped[prod.category]) grouped[prod.category] = [];
            grouped[prod.category].push(prod);
        });

        let html = '';

        Object.keys(grouped).forEach(categoryName => {
            const items = grouped[categoryName];
            if (activeCat !== 'ALL' && activeCat !== categoryName) return;
            if (items.length === 0 && query) return;

            const isCollapsed = !!window.AppStore.collapsedCategories[categoryName];

            // 1. Accordion Group Header Row
            const isUncategorized = (categoryName === 'Uncategorized');
            const catMeta = getCategoryMeta(categoryName);
            const catPal = getCategoryPalette(catMeta.color);

            html += `
                <tr class="inv-group-row ${isCollapsed ? 'collapsed' : ''}" onclick="toggleAccordionCategory('${categoryName}')">
                    <td colspan="${totalVisibleCols}" class="inv-group-header-cell">
                        <div class="inv-group-header-content">
                            <div class="inv-group-left-box">
                                <div class="inv-accordion-chevron">
                                    <i class="ph ph-caret-down"></i>
                                </div>
                                <div class="inv-cat-avatar-icon" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border};">
                                    <i class="ph ${catMeta.icon}"></i>
                                </div>
                                <span class="inv-group-title">${categoryName}</span>
                                <span class="inv-group-count-badge" style="background: ${catPal.bg}; color: ${catPal.text}; border: 1px solid ${catPal.border};">${items.length} ${items.length === 1 ? 'item' : 'items'}</span>
                            </div>
                            <div class="inv-group-actions-box" onclick="event.stopPropagation()">
                                <button type="button" class="inv-group-action-btn" title="Edit Category Details & Visuals" onclick="openEditCategoryModal('${categoryName}')">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                ${!isUncategorized ? `
                                <button type="button" class="inv-group-action-btn is-delete" title="Delete Category & Move items to Uncategorized" onclick="confirmDeleteCategory('${categoryName}', ${items.length})">
                                    <i class="ph ph-trash"></i>
                                </button>
                                ` : ''}
                            </div>
                        </div>
                    </td>
                </tr>
            `;

            // 2. Standard linear rows nested under each group header
            items.forEach(item => {
                const isSelected = (window.AppStore.selectedProductId === item.id);
                const cost = Number(item.costPrice) || 0;
                const selling = Number(item.sellingPrice) || 0;
                const profit = selling - cost;
                const marginPct = selling > 0 ? ((profit / selling) * 100).toFixed(1) : '0.0';

                html += `
                    <tr class="inv-item-row ${isCollapsed ? 'is-hidden' : ''} ${isSelected ? 'is-selected' : ''}" data-category-group="${categoryName}" onclick="openProductDrawer(${item.id})">
                `;

                ALL_COLUMNS.forEach(col => {
                    if (!activeColumnIds.includes(col.id)) return;
                    const colId = col.id;
                    const colWidth = getColumnWidth(col);
                    const style = `width: ${colWidth}; min-width: ${col.minWidth || '80px'};`;
                    switch (colId) {
                        case 'sku':
                            html += `<td style="${style}"><span class="inv-sku-badge">${item.sku}</span></td>`;
                            break;
                        case 'product':
                            html += `
                                <td style="${style}">
                                    <div class="inv-prod-title-group">
                                        <span class="inv-product-name">${item.name}</span>
                                        <span class="inv-barcode-tag"><i class="ph ph-barcode"></i> ${item.barcode || '—'}</span>
                                    </div>
                                </td>
                            `;
                            break;
                        case 'description':
                            html += `
                                <td style="${style}">
                                    <div class="inv-desc-cell" title="${item.description || ''}">
                                        ${item.description || '—'}
                                    </div>
                                </td>
                            `;
                            break;
                        case 'category':
                            const itemCatMeta = getCategoryMeta(item.category);
                            const itemCatPal = getCategoryPalette(itemCatMeta.color);
                            html += `
                                <td style="${style}">
                                    <span class="inv-category-pill" style="background: ${itemCatPal.bg}; color: ${itemCatPal.text}; border: 1px solid ${itemCatPal.border};">
                                        <i class="ph ${itemCatMeta.icon}"></i>
                                        <span>${item.category}</span>
                                    </span>
                                </td>
                            `;
                            break;
                        case 'subcategory':
                            html += `<td style="${style}"><span class="inv-badge-subcat">${item.subcategory || 'Standard'}</span></td>`;
                            break;
                        case 'uom':
                            html += `
                                <td style="${style}">
                                    <span style="font-weight: 600;">${item.unit}</span>
                                    <span style="color: var(--inv-text-muted); font-size: 0.72rem;">(${item.packSize || '1 pc'})</span>
                                </td>
                            `;
                            break;
                        case 'cost_price':
                            html += `<td style="${style}"><span style="color: var(--inv-text-muted); font-variant-numeric: tabular-nums;">${formatPHP(item.costPrice)}</span></td>`;
                            break;
                        case 'selling_price':
                            html += `<td style="${style}"><span style="font-weight: 700; color: var(--inv-text-strong); font-variant-numeric: tabular-nums;">${formatPHP(item.sellingPrice)}</span></td>`;
                            break;
                        case 'margin':
                            html += `<td style="${style}"><span style="font-weight: 600; color: var(--inv-success); font-variant-numeric: tabular-nums;">${marginPct}%</span></td>`;
                            break;
                        case 'tax_rate':
                            html += `<td style="${style}"><span style="font-size: 0.76rem;">${item.taxRate || '12% VAT'}</span></td>`;
                            break;
                        case 'supplier':
                            html += `<td style="${style}"><span style="font-size: 0.77rem; color: var(--inv-text-medium);">${item.supplier || '—'}</span></td>`;
                            break;
                        case 'allergens':
                            html += `
                                <td style="${style}">
                                    ${(item.allergens && item.allergens.length > 0) ? item.allergens.map(a => `<span class="inv-badge-allergen">${a}</span>`).join('') : '<span style="color:var(--inv-text-subtle);">None</span>'}
                                </td>
                            `;
                            break;
                        case 'status':
                            html += `
                                <td style="${style}; text-align: left;">
                                    <span class="inv-status-pill ${item.isActive ? 'active' : 'inactive'}">
                                        <span class="inv-status-dot"></span>
                                        ${item.isActive ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                            `;
                            break;
                    }
                });

                html += `
                        <td class="td-right td-action">
                            <button type="button" class="inv-view-btn" onclick="event.stopPropagation(); openProductDrawer(${item.id});">
                                <i class="ph ph-arrow-square-out"></i>
                                <span>View</span>
                            </button>
                        </td>
                    </tr>
                `;
            });
        });

        masterTbody.innerHTML = html;
    }

    window.toggleAccordionCategory = function(categoryName) {
        window.AppStore.collapsedCategories[categoryName] = !window.AppStore.collapsedCategories[categoryName];
        renderMasterList();
    };

    window.toggleAllAccordions = function() {
        const anyExpanded = window.AppStore.categories.some(c => !window.AppStore.collapsedCategories[c]);
        window.AppStore.categories.forEach(c => {
            window.AppStore.collapsedCategories[c] = anyExpanded;
        });

        document.getElementById('textToggleAll').textContent = anyExpanded ? 'Expand All' : 'Collapse All';
        document.getElementById('iconToggleAll').className = anyExpanded ? 'ph ph-arrows-out-line-vertical' : 'ph ph-arrows-in-line-vertical';
        renderMasterList();
    };

    // =========================================================================
    // NEW / EDIT CATEGORY MODAL LOGIC & VISUAL CUSTOMIZATION
    // =========================================================================
    function renderCategoryIconGrid(selectedIcon) {
        const grid = document.getElementById('catIconGrid');
        if (!grid) return;
        grid.innerHTML = CATEGORY_ICONS.map(item => {
            const isActive = (item.id === selectedIcon);
            return `
                <button type="button" 
                    class="inv-cat-icon-btn ${isActive ? 'is-active' : ''}" 
                    title="${item.label}" 
                    onclick="handleSelectCategoryIcon('${item.id}')">
                    <i class="ph ${item.id}"></i>
                </button>
            `;
        }).join('');
        
        const iconObj = CATEGORY_ICONS.find(i => i.id === selectedIcon) || { label: 'General / Tag' };
        const labelEl = document.getElementById('selectedIconLabel');
        if (labelEl) labelEl.textContent = iconObj.label;
    }

    function renderCategoryColorGrid(selectedColor) {
        const grid = document.getElementById('catColorGrid');
        if (!grid) return;
        grid.innerHTML = Object.entries(CATEGORY_PALETTES).map(([key, pal]) => {
            const isActive = (key === selectedColor);
            return `
                <div class="inv-cat-color-chip ${isActive ? 'is-active' : ''}" onclick="handleSelectCategoryColor('${key}')">
                    <span class="inv-cat-color-dot" style="background: ${pal.dot};"></span>
                    <span>${pal.label.split(' / ')[0]}</span>
                </div>
            `;
        }).join('');

        const palObj = CATEGORY_PALETTES[selectedColor] || CATEGORY_PALETTES.purple;
        const labelEl = document.getElementById('selectedColorLabel');
        if (labelEl) labelEl.textContent = palObj.label;
    }

    function updateCategoryLivePreview() {
        const iconInput = document.getElementById('modalCategoryIcon');
        const colorInput = document.getElementById('modalCategoryColor');
        const nameInput = document.getElementById('modalCategoryName');
        const previewBadge = document.getElementById('catLivePreviewBadge');
        const previewIcon = document.getElementById('previewBadgeIcon');
        const previewText = document.getElementById('previewBadgeText');

        if (!previewBadge || !iconInput || !colorInput) return;

        const icon = iconInput.value || 'ph-tag';
        const colorKey = colorInput.value || 'purple';
        const pal = getCategoryPalette(colorKey);
        const catName = (nameInput && nameInput.value.trim()) ? nameInput.value.trim() : 'New Category';

        previewBadge.style.background = pal.bg;
        previewBadge.style.color = pal.text;
        previewBadge.style.borderColor = pal.border;

        if (previewIcon) previewIcon.className = `ph ${icon}`;
        if (previewText) previewText.textContent = catName;
    }

    window.handleSelectCategoryIcon = function(iconId) {
        const input = document.getElementById('modalCategoryIcon');
        if (input) input.value = iconId;
        renderCategoryIconGrid(iconId);
        updateCategoryLivePreview();
    };

    window.handleSelectCategoryColor = function(colorKey) {
        const input = document.getElementById('modalCategoryColor');
        if (input) input.value = colorKey;
        renderCategoryColorGrid(colorKey);
        updateCategoryLivePreview();
    };

    window.openNewCategoryModal = function() {
        document.getElementById('modalCategoryOriginalName').value = '';
        document.getElementById('modalCategoryTitle').textContent = 'Add Product Category';
        document.getElementById('btnSaveCategoryText').textContent = 'Create Category';
        document.getElementById('modalCategoryHeaderIcon').className = 'ph ph-folder-plus';
        document.getElementById('modalCategoryName').value = '';
        document.getElementById('modalCategoryDesc').value = '';

        // Initialize default icon and color
        const defaultIcon = 'ph-tag';
        const defaultColor = 'purple';
        document.getElementById('modalCategoryIcon').value = defaultIcon;
        document.getElementById('modalCategoryColor').value = defaultColor;
        renderCategoryIconGrid(defaultIcon);
        renderCategoryColorGrid(defaultColor);
        updateCategoryLivePreview();

        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
        newCategoryModal.classList.add('is-open');
        setTimeout(() => document.getElementById('modalCategoryName').focus(), 150);
    };

    window.openEditCategoryModal = function(catName) {
        if (!catName) {
            catName = window.AppStore.categories[0] || 'Beverages';
        }
        document.getElementById('modalCategoryOriginalName').value = catName;
        document.getElementById('modalCategoryTitle').textContent = `Edit Category: ${catName}`;
        document.getElementById('btnSaveCategoryText').textContent = 'Update Category';
        document.getElementById('modalCategoryHeaderIcon').className = 'ph ph-pencil-simple';
        document.getElementById('modalCategoryName').value = catName;
        document.getElementById('modalCategoryDesc').value = window.AppStore.categoryInfo[catName] || '';

        // Hydrate existing metadata
        const meta = getCategoryMeta(catName);
        document.getElementById('modalCategoryIcon').value = meta.icon;
        document.getElementById('modalCategoryColor').value = meta.color;
        renderCategoryIconGrid(meta.icon);
        renderCategoryColorGrid(meta.color);
        updateCategoryLivePreview();

        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
        newCategoryModal.classList.add('is-open');
        setTimeout(() => document.getElementById('modalCategoryName').focus(), 150);
    };

    window.closeNewCategoryModal = function() {
        newCategoryModal.classList.remove('is-open');
        document.getElementById('modalCategoryOriginalName').value = '';
        document.getElementById('modalCategoryName').value = '';
        document.getElementById('modalCategoryDesc').value = '';
        hideCategoryDuplicateWarning();
        hideCategoryAutocomplete();
    };

    function hideCategoryDuplicateWarning() {
        const warn = document.getElementById('catDuplicateWarning');
        if (warn) warn.classList.remove('is-visible');
    }

    function hideCategoryAutocomplete() {
        const drop = document.getElementById('catAutocompleteDropdown');
        if (drop) drop.classList.remove('is-open');
    }

    window.handleCategoryInputCheck = function(typedVal) {
        updateCategoryLivePreview();
        const originalName = document.getElementById('modalCategoryOriginalName').value;
        const val = typedVal.trim().toLowerCase();
        const warn = document.getElementById('catDuplicateWarning');
        const msg = document.getElementById('catDuplicateMsg');
        const drop = document.getElementById('catAutocompleteDropdown');
        const list = document.getElementById('catAutocompleteList');

        if (!val) {
            hideCategoryDuplicateWarning();
            hideCategoryAutocomplete();
            return;
        }

        // Search matching categories
        const matches = window.AppStore.categories.filter(c => c.toLowerCase().includes(val));
        const exactMatch = window.AppStore.categories.find(c => c.toLowerCase() === val);

        if (exactMatch && exactMatch.toLowerCase() !== originalName.toLowerCase()) {
            warn.classList.add('is-visible');
            msg.textContent = `A category named "${exactMatch}" already exists!`;
        } else {
            hideCategoryDuplicateWarning();
        }

        if (matches.length > 0) {
            list.innerHTML = matches.map(c => `
                <div class="inv-cat-dropdown-item" onclick="selectExistingCategory('${c}')">
                    <span>${c}</span>
                    <i class="ph ph-arrow-up-left" style="font-size: 13px; color: var(--inv-text-muted);"></i>
                </div>
            `).join('');
            drop.classList.add('is-open');
        } else {
            hideCategoryAutocomplete();
        }
    };

    window.selectExistingCategory = function(name) {
        document.getElementById('modalCategoryName').value = name;
        hideCategoryAutocomplete();
        handleCategoryInputCheck(name);
    };

    window.handleCreateCategory = function(e) {
        e.preventDefault();
        const catName = document.getElementById('modalCategoryName').value.trim();
        const catDesc = document.getElementById('modalCategoryDesc').value.trim();
        const catIcon = document.getElementById('modalCategoryIcon').value.trim() || 'ph-tag';
        const catColor = document.getElementById('modalCategoryColor').value.trim() || 'purple';
        const originalName = document.getElementById('modalCategoryOriginalName').value;

        if (!catName) return;

        // Check duplicate
        const isDuplicate = window.AppStore.categories.some(c => 
            c.toLowerCase() === catName.toLowerCase() && c.toLowerCase() !== originalName.toLowerCase()
        );

        if (isDuplicate) {
            showToast(`Category "${catName}" already exists!`);
            const warn = document.getElementById('catDuplicateWarning');
            if (warn) warn.classList.add('is-visible');
            return;
        }

        if (originalName) {
            // Edit Mode
            const idx = window.AppStore.categories.indexOf(originalName);
            if (idx !== -1) {
                window.AppStore.categories[idx] = catName;
            }
            // Migrate products with the old category name
            window.AppStore.products.forEach(p => {
                if (p.category === originalName) {
                    p.category = catName;
                }
            });
            // Update description and meta stores
            if (originalName !== catName) {
                delete window.AppStore.categoryInfo[originalName];
                delete window.AppStore.categoryMeta[originalName];
            }
            window.AppStore.categoryInfo[catName] = catDesc;
            window.AppStore.categoryMeta[catName] = { icon: catIcon, color: catColor };

            localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));
            localStorage.setItem('rms_category_info', JSON.stringify(window.AppStore.categoryInfo));
            localStorage.setItem('rms_category_meta', JSON.stringify(window.AppStore.categoryMeta));
            localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
            localStorage.removeItem('rms_stocks_ledger'); // Invalidate cached stocks ledger so stocks overview picks up new category/meta

            showToast(`Category "${catName}" updated successfully!`);
        } else {
            // Add Mode
            window.AppStore.categories.push(catName);
            window.AppStore.categoryInfo[catName] = catDesc;
            window.AppStore.categoryMeta[catName] = { icon: catIcon, color: catColor };

            localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));
            localStorage.setItem('rms_category_info', JSON.stringify(window.AppStore.categoryInfo));
            localStorage.setItem('rms_category_meta', JSON.stringify(window.AppStore.categoryMeta));
            localStorage.removeItem('rms_stocks_ledger'); // Invalidate cached stocks ledger

            showToast(`Category "${catName}" created successfully!`);
        }

        syncCategoryControls();
        renderMasterList();
        closeNewCategoryModal();
    };

    window.confirmDeleteCategory = function(catName, itemCount) {
        if (catName === 'Uncategorized') {
            showToast('The "Uncategorized" section cannot be deleted.');
            return;
        }

        const confirmText = itemCount > 0 
            ? `Are you sure you want to delete category "${catName}"? ${itemCount} ${itemCount === 1 ? 'item' : 'items'} under it will be moved to the "Uncategorized" section.`
            : `Are you sure you want to delete category "${catName}"?`;

        if (!confirm(confirmText)) return;

        // Ensure 'Uncategorized' exists in categories
        if (!window.AppStore.categories.includes('Uncategorized')) {
            window.AppStore.categories.push('Uncategorized');
        }

        // Reassign products
        window.AppStore.products.forEach(p => {
            if (p.category === catName) {
                p.category = 'Uncategorized';
            }
        });

        // Remove category from array
        window.AppStore.categories = window.AppStore.categories.filter(c => c !== catName);
        delete window.AppStore.categoryInfo[catName];
        delete window.AppStore.categoryMeta[catName];

        localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));
        localStorage.setItem('rms_category_info', JSON.stringify(window.AppStore.categoryInfo));
        localStorage.setItem('rms_category_meta', JSON.stringify(window.AppStore.categoryMeta));
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.removeItem('rms_stocks_ledger');

        syncCategoryControls();
        renderMasterList();
        showToast(`Category "${catName}" deleted. Items moved to Uncategorized.`);
    };

    searchInput.addEventListener('input', function(e) {
        window.AppStore.searchQuery = e.target.value;
        renderMasterList();
    });

    // =========================================================================
    // SLIDE-OUT DETAIL DRAWER: PAGINATION & SUBTABLE CONTROLLERS
    // =========================================================================
    let currentDrawerBranches = [];
    let currentDrawerLedgers = [];

    function renderBranchSubtablePage() {
        const page = window.AppStore.branchPage;
        const size = window.AppStore.branchPageSize;
        const total = currentDrawerBranches.length;
        const totalPages = Math.max(1, Math.ceil(total / size));
        const start = (page - 1) * size;
        const pageItems = currentDrawerBranches.slice(start, start + size);

        document.getElementById('branchStockPageInfo').textContent = `Page ${page} of ${totalPages} (${total} total)`;
        document.getElementById('btnBranchPrev').disabled = (page <= 1);
        document.getElementById('btnBranchNext').disabled = (page >= totalPages);

        if (pageItems.length > 0) {
            branchSubtableBody.innerHTML = pageItems.map(b => {
                const isLow = b.onHand <= b.reorder;
                return `
                    <tr>
                        <td><strong>${b.name}</strong></td>
                        <td class="td-right" style="font-weight: 600;">${b.onHand}</td>
                        <td class="td-right" style="color: var(--inv-text-muted);">${b.reserved || 0}</td>
                        <td class="td-right">
                            <span class="inv-stock-status-pill ${isLow ? 'low-stock' : 'in-stock'}">
                                ${isLow ? 'Low Stock' : 'Optimal'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');
        } else {
            branchSubtableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 16px; color: var(--inv-text-muted);">No branch inventory mapped.</td></tr>`;
        }
    }

    window.navigateBranchPage = function(delta) {
        const totalPages = Math.max(1, Math.ceil(currentDrawerBranches.length / window.AppStore.branchPageSize));
        const newPage = window.AppStore.branchPage + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            window.AppStore.branchPage = newPage;
            renderBranchSubtablePage();
        }
    };

    function renderLedgerSubtablePage() {
        const page = window.AppStore.ledgerPage;
        const size = window.AppStore.ledgerPageSize;
        const total = currentDrawerLedgers.length;
        const totalPages = Math.max(1, Math.ceil(total / size));
        const start = (page - 1) * size;
        const pageItems = currentDrawerLedgers.slice(start, start + size);

        document.getElementById('ledgerLogPageInfo').textContent = `Page ${page} of ${totalPages} (${total} total)`;
        document.getElementById('btnLedgerPrev').disabled = (page <= 1);
        document.getElementById('btnLedgerNext').disabled = (page >= totalPages);

        if (pageItems.length > 0) {
            ledgerSubtableBody.innerHTML = pageItems.map(l => `
                <tr>
                    <td><strong>${l.ref}</strong></td>
                    <td>${l.type}</td>
                    <td class="td-right" style="font-weight: 600; color: ${String(l.qty).startsWith('+') ? 'var(--inv-success)' : 'var(--inv-danger)'};">${l.qty}</td>
                    <td class="td-right" style="font-weight: 700;">${l.balance}</td>
                </tr>
            `).join('');
        } else {
            ledgerSubtableBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 16px; color: var(--inv-text-muted);">No recent ledger activity recorded.</td></tr>`;
        }
    }

    window.navigateLedgerPage = function(delta) {
        const totalPages = Math.max(1, Math.ceil(currentDrawerLedgers.length / window.AppStore.ledgerPageSize));
        const newPage = window.AppStore.ledgerPage + delta;
        if (newPage >= 1 && newPage <= totalPages) {
            window.AppStore.ledgerPage = newPage;
            renderLedgerSubtablePage();
        }
    };

    // =========================================================================
    // PRICING TAB: COLUMN CHART & SUPPLIER BENCHMARKS (LAST 3 MONTHS)
    // =========================================================================
    function getMonthLabels3Mos() {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const d = new Date();
        const res = [];
        for (let i = 2; i >= 0; i--) {
            const pastDate = new Date(d.getFullYear(), d.getMonth() - i, 1);
            res.push(`${monthNames[pastDate.getMonth()]} ${pastDate.getFullYear()}`);
        }
        return res;
    }

    function renderPriceHistoryChart(baseCost, baseSelling) {
        const canvas = document.getElementById('priceHistoryChart');
        if (!canvas) return;

        if (window.AppStore.activePriceChart) {
            window.AppStore.activePriceChart.destroy();
            window.AppStore.activePriceChart = null;
        }

        const months = getMonthLabels3Mos();
        document.getElementById('chartMonthsBadge').textContent = `${months[0]} - ${months[2]}`;

        // Compute realistic monthly trend points for past 3 months
        const cVal = Number(baseCost) || 50;
        const sVal = Number(baseSelling) || (cVal * 1.6);

        const costData = [
            Math.round(cVal * 0.94 * 100) / 100,
            Math.round(cVal * 0.97 * 100) / 100,
            Math.round(cVal * 1.00 * 100) / 100
        ];
        const sellingData = [
            Math.round(sVal * 0.98 * 100) / 100,
            Math.round(sVal * 1.00 * 100) / 100,
            Math.round(sVal * 1.00 * 100) / 100
        ];

        const ctx = canvas.getContext('2d');
        window.AppStore.activePriceChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Cost Price (₱)',
                        data: costData,
                        backgroundColor: 'rgba(168, 85, 247, 0.75)',
                        borderColor: '#a855f7',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    },
                    {
                        label: 'Selling Price (₱)',
                        data: sellingData,
                        backgroundColor: 'rgba(59, 130, 246, 0.65)',
                        borderColor: '#3b82f6',
                        borderWidth: 1.5,
                        borderRadius: 6,
                        barPercentage: 0.65,
                        categoryPercentage: 0.75
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            font: { family: 'Poppins', size: 11, weight: '600' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.dataset.label}: ₱${Number(context.raw).toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) { return '₱' + value; },
                            font: { size: 10 }
                        },
                        grid: { color: 'rgba(226, 232, 240, 0.6)' }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 11, weight: '500' } }
                    }
                }
            }
        });
    }

    function renderSupplierPriceSection(product, isNew) {
        let suppliers = [];
        const baseCost = Number(product?.costPrice) || 50;

        if (product && product.supplierBenchmarks && product.supplierBenchmarks.length > 0) {
            suppliers = product.supplierBenchmarks;
        } else {
            // Generate standard mock supplier quotes based on product context
            const primarySup = (product && product.supplier) ? product.supplier : 'Direct Import Supply Corp';
            suppliers = [
                { name: primarySup, price: Math.round(baseCost * 100) / 100, updated: '2 days ago' },
                { name: 'Apex Premier Distro', price: Math.round(baseCost * 1.08 * 100) / 100, updated: '1 week ago' },
                { name: 'Global Goods Wholesale Hub', price: Math.round(baseCost * 0.95 * 100) / 100, updated: 'Yesterday' }
            ];
        }

        // Find lowest affordable price
        const minPrice = Math.min(...suppliers.map(s => Number(s.price)));

        // Automatically fill Cost Price = Lowest Price + 5% Margin if new or user requests
        const recommendedCost = (minPrice * 1.05).toFixed(2);
        if (isNew || !formCostPrice.value || Number(formCostPrice.value) === 0) {
            formCostPrice.value = recommendedCost;
            updateMarginCalculation();
        }

        supplierPricesListBody.innerHTML = suppliers.map(s => {
            const isLowest = (Number(s.price) === minPrice);
            return `
                <div class="inv-supplier-item ${isLowest ? 'is-lowest' : ''}">
                    <div>
                        <div class="inv-supplier-name">
                            ${s.name}
                            ${isLowest ? '<span class="inv-supplier-best-badge">Most Affordable</span>' : ''}
                        </div>
                        <div class="inv-supplier-updated">Updated: ${s.updated}</div>
                    </div>
                    <div class="inv-supplier-price-box">
                        <div class="inv-supplier-price">${formatPHP(s.price)}</div>
                        ${isLowest ? '<div style="font-size: 0.64rem; color: var(--inv-success); font-weight: 600;">+5% = ₱' + (s.price * 1.05).toFixed(2) + '</div>' : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    // =========================================================================
    // SLIDE-OUT DETAIL DRAWER LOGIC
    // =========================================================================
    window.openProductDrawer = function(idOrMode, defaultCategory) {
        switchPrimaryTab('tab-general');
        switchSubTab('sub-branch');

        window.AppStore.branchPage = 1;
        window.AppStore.ledgerPage = 1;

        if (idOrMode === 'new') {
            window.AppStore.selectedProductId = null;
            drawerTitle.textContent = 'Add New Product';
            drawerSubtitle.textContent = 'Create master catalog record with barcodes & pricing';
            drawerIcon.innerHTML = '<i class="ph ph-plus-circle"></i>';
            btnSaveText.textContent = 'Create Product';

            formId.value = '';
            formSku.value = 'PRD-' + Math.floor(1000 + Math.random() * 9000);
            formBarcode.value = '4800' + Math.floor(100000000 + Math.random() * 900000000);
            formName.value = '';
            
            const defCat = defaultCategory || (window.AppStore.categories[0] || 'Beverages');
            formCategory.value = defCat;
            syncSubcategoryDropdown(defCat);
            if (formSubcategorySelect && formSubcategorySelect.options.length > 0) {
                formSubcategory.value = formSubcategorySelect.options[0].value;
            } else {
                formSubcategory.value = 'Standard';
            }

            formUnit.value = 'Piece';
            formPackSize.value = '1';
            formDescription.value = '';
            formAllergens.value = '';
            formCanBeSold.checked = true;
            formCanBePurchased.checked = true;
            formHasBom.checked = false;
            formIsActive.checked = true;
            formTrackStock.checked = true;
            formReorderPoint.value = 15;
            formTargetStock.value = 50;
            formSellingPrice.value = '';
            formTaxRate.value = '12% VAT';
            formSupplier.value = '';
            formProfitMargin.value = '0.0%';

            currentDrawerBranches = [
                { name: 'Power Mac Center - Main HQ', onHand: 0, reserved: 0, reorder: 15 }
            ];
            currentDrawerLedgers = [];

            renderBranchSubtablePage();
            renderLedgerSubtablePage();
            renderSupplierPriceSection(null, true);
            renderPriceHistoryChart(parseFloat(formCostPrice.value) || 45, 120);

        } else {
            const product = window.AppStore.products.find(p => p.id === Number(idOrMode));
            if (!product) return;

            window.AppStore.selectedProductId = product.id;
            drawerTitle.textContent = product.name;
            drawerSubtitle.textContent = `SKU: ${product.sku} • Category: ${product.category}`;
            drawerIcon.innerHTML = '<i class="ph ph-tag"></i>';
            btnSaveText.textContent = 'Save Changes';

            formId.value = product.id;
            formSku.value = product.sku;
            formBarcode.value = product.barcode || '';
            formName.value = product.name;
            formCategory.value = product.category;
            
            syncSubcategoryDropdown(product.category);
            formSubcategory.value = product.subcategory || 'Standard';
            if (formSubcategorySelect) {
                const hasOpt = Array.from(formSubcategorySelect.options).some(o => o.value === product.subcategory);
                if (hasOpt) {
                    formSubcategorySelect.value = product.subcategory;
                    formSubcategory.style.display = 'none';
                    formSubcategorySelect.style.display = 'block';
                } else {
                    formSubcategorySelect.style.display = 'none';
                    formSubcategory.style.display = 'block';
                }
            }

            formUnit.value = product.unit;
            // Extract numeric value from pack size if string had units
            const numericPackSize = parseFloat(String(product.packSize || '1').replace(/[^0-9.]/g, '')) || 1;
            formPackSize.value = numericPackSize;

            formDescription.value = product.description || '';
            formAllergens.value = (product.allergens || []).join(', ');
            formCanBeSold.checked = product.canBeSold !== false;
            formCanBePurchased.checked = product.canBePurchased !== false;
            formHasBom.checked = product.explodeBomOnSale !== undefined ? product.explodeBomOnSale : (product.hasBom === true);
            formIsActive.checked = product.isActive !== false;
            formTrackStock.checked = product.trackPhysicalStock !== undefined ? product.trackPhysicalStock : (product.trackStock !== false);
            formReorderPoint.value = product.reorderPoint || 10;
            formTargetStock.value = product.targetStock || 50;
            formCostPrice.value = Number(product.costPrice).toFixed(2);
            formSellingPrice.value = Number(product.sellingPrice).toFixed(2);
            formTaxRate.value = product.taxRate || '12% VAT';
            formSupplier.value = product.supplier || '';
            updateMarginCalculation();

            currentDrawerBranches = product.branches || [
                { name: 'Power Mac Center - Main HQ', onHand: 24, reserved: 0, reorder: product.reorderPoint || 15 }
            ];
            currentDrawerLedgers = product.ledger || [
                { ref: 'PO-2026-0041', type: 'Stock In', qty: '+50', balance: 95 }
            ];

            renderBranchSubtablePage();
            renderLedgerSubtablePage();
            renderSupplierPriceSection(product, false);
            renderPriceHistoryChart(product.costPrice, product.sellingPrice);
        }

        drawerBackdrop.classList.add('is-open');
        drawerContainer.classList.add('is-open');

        setTimeout(() => {
            formName.focus();
            formName.select();
        }, 180);

        renderMasterList();
    };

    window.closeProductDrawer = function() {
        drawerBackdrop.classList.remove('is-open');
        drawerContainer.classList.remove('is-open');
        window.AppStore.selectedProductId = null;
        if (window.AppStore.activePriceChart) {
            window.AppStore.activePriceChart.destroy();
            window.AppStore.activePriceChart = null;
        }
        renderMasterList();
        setTimeout(() => searchInput.focus(), 150);
    };

    function switchPrimaryTab(tabId) {
        document.querySelectorAll('.inv-drawer-tab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
        });
        document.querySelectorAll('.drawer-tab-pane').forEach(pane => {
            pane.style.display = (pane.id === 'pane-' + tabId) ? 'block' : 'none';
        });

        // Trigger chart render if pricing tab opened
        if (tabId === 'tab-pricing') {
            setTimeout(() => {
                const cost = parseFloat(formCostPrice.value) || 50;
                const selling = parseFloat(formSellingPrice.value) || (cost * 1.5);
                renderPriceHistoryChart(cost, selling);
            }, 50);
        }

        if (tabId === 'tab-audit') {
            const prod = window.AppStore.products.find(p => p.id === window.AppStore.selectedProductId);
            renderProductAuditLogs(prod);
        }
    }

    document.querySelectorAll('.inv-drawer-tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchPrimaryTab(this.getAttribute('data-tab'));
        });
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
            summary: summary || 'Product record updated',
            changes: changes || []
        };
    }

    function renderProductAuditLogs(product) {
        const container = document.getElementById('productAuditLogsContainer');
        if (!container) return;

        if (!product) {
            container.innerHTML = `
                <div class="audit-overview-card" style="grid-template-columns: 1fr; text-align: center; padding: 28px 16px;">
                    <div style="color: var(--inv-text-muted); display: flex; flex-direction: column; align-items: center; gap: 8px;">
                        <i class="ph ph-sparkle" style="font-size: 2rem; color: var(--inv-primary);"></i>
                        <h4 style="margin: 0; font-size: 0.95rem; font-weight: 700; color: var(--inv-text-strong);">New Item Draft</h4>
                        <p style="margin: 0; font-size: 0.8rem; max-width: 320px; line-height: 1.4;">
                            Audit trail will automatically initialize with registration timestamp and user identity upon clicking "Create Product".
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
                <div style="text-align: center; padding: 24px; color: var(--inv-text-muted); font-size: 0.82rem;">
                    No revision history recorded yet for this item.
                </div>
            `;
            return;
        }

        let timelineHtml = '<div class="audit-timeline-container">';

        logs.forEach((log) => {
            let iconClass = '';
            let iconGlyph = 'ph-clock-counter-clockwise';
            let badgeClass = 'badge-updated';
            let badgeText = log.actionType || 'UPDATED';

            if (log.actionType === 'CREATED') {
                iconClass = 'is-created';
                iconGlyph = 'ph-plus-circle';
                badgeClass = 'badge-created';
                badgeText = 'CREATED';
            } else if (log.actionType === 'PRICE_CHANGE') {
                iconClass = 'is-price';
                iconGlyph = 'ph-currency-circle-dollar';
                badgeClass = 'badge-price';
                badgeText = 'PRICE ADJUSTED';
            } else if (log.actionType === 'BOM_CHANGE') {
                iconClass = 'is-bom';
                iconGlyph = 'ph-tree-structure';
                badgeClass = 'badge-bom';
                badgeText = 'BOM UPDATED';
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
                                    <td style="font-weight: 600; color: var(--inv-text-strong);">${ch.field}</td>
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
                            <span style="font-size: 0.72rem; color: var(--inv-text-muted); font-weight: 500;">
                                <i class="ph ph-clock" style="vertical-align: middle;"></i> ${log.formattedDate}
                            </span>
                        </div>
                    </div>
                    <div class="audit-summary-text">${log.summary || 'Item modified'}</div>
                    ${diffRows}
                </div>
            `;
        });

        timelineHtml += '</div>';
        container.innerHTML = overviewHtml + timelineHtml;
    }

    function switchSubTab(subtabId) {
        document.querySelectorAll('.inv-subtab-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-subtab') === subtabId);
        });
        document.querySelectorAll('.inv-subtable-wrapper').forEach(wrapper => {
            wrapper.style.display = (wrapper.id === 'subpane-' + subtabId) ? 'block' : 'none';
        });

        const branchPagination = document.getElementById('branchStockPagination');
        const ledgerPagination = document.getElementById('ledgerLogPagination');
        if (branchPagination) branchPagination.style.display = (subtabId === 'sub-branch') ? 'flex' : 'none';
        if (ledgerPagination) ledgerPagination.style.display = (subtabId === 'sub-movements') ? 'flex' : 'none';
    }

    document.querySelectorAll('.inv-subtab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            switchSubTab(this.getAttribute('data-subtab'));
        });
    });

    window.triggerSaveForm = function() {
        const form = document.getElementById('productForm');
        if (form.reportValidity()) {
            saveProductChanges();
        }
    };

    function saveProductChanges(e) {
        if (e) e.preventDefault();

        const idVal = formId.value;
        const isNew = !idVal;
        const newId = isNew ? Date.now() : Number(idVal);

        const allergenArray = formAllergens.value.split(',').map(s => s.trim()).filter(Boolean);

        const updatedRecord = {
            id: newId,
            sku: formSku.value.trim(),
            barcode: formBarcode.value.trim(),
            name: formName.value.trim(),
            category: formCategory.value,
            subcategory: formSubcategory.value.trim() || 'Standard',
            unit: formUnit.value,
            packSize: formPackSize.value.trim() || '1',
            description: formDescription.value.trim(),
            allergens: allergenArray,
            canBeSold: formCanBeSold.checked,
            canBePurchased: formCanBePurchased.checked,
            trackPhysicalStock: formTrackStock.checked,
            trackStock: formTrackStock.checked,
            explodeBomOnSale: formHasBom.checked,
            hasBom: formHasBom.checked,
            isActive: formIsActive.checked,
            reorderPoint: Number(formReorderPoint.value) || 0,
            targetStock: Number(formTargetStock.value) || 0,
            costPrice: parseFloat(formCostPrice.value) || 0,
            sellingPrice: parseFloat(formSellingPrice.value) || 0,
            taxRate: formTaxRate.value,
            supplier: formSupplier.value.trim() || 'Direct Supply',
            branches: [
                { name: 'Power Mac Center - Main HQ', onHand: 42, reserved: 2, reorder: Number(formReorderPoint.value) || 20 },
                { name: 'Makati Greenbelt Kiosk', onHand: 18, reserved: 0, reorder: 15 },
                { name: 'BGC High Street Branch', onHand: 35, reserved: 4, reorder: 20 }
            ],
            ledger: [
                { ref: 'ADJ-' + Math.floor(1000 + Math.random() * 9000), type: 'Catalog Update', qty: '+0', balance: 95 }
            ]
        };

        const currentUser = {
            name: 'John Abiguero',
            email: 'jabiguero@dorothydiaz.internal',
            role: 'Lead Inventory Admin'
        };

        if (isNew) {
            const initialLog = createAuditEntry(currentUser, 'CREATED', `Initial catalog registration for "${updatedRecord.name}"`, [
                { field: 'SKU Code', oldValue: '—', newValue: updatedRecord.sku },
                { field: 'Product Name', oldValue: '—', newValue: updatedRecord.name },
                { field: 'Category', oldValue: '—', newValue: updatedRecord.category },
                { field: 'Unit of Measure', oldValue: '—', newValue: `${updatedRecord.unit} (${updatedRecord.packSize})` },
                { field: 'Cost Price', oldValue: '—', newValue: formatPHP(updatedRecord.costPrice) },
                { field: 'Selling Price', oldValue: '—', newValue: formatPHP(updatedRecord.sellingPrice) },
                { field: 'Track Physical Stock', oldValue: '—', newValue: updatedRecord.trackPhysicalStock ? 'Yes (Counted in Stocks Overview)' : 'No (Non-Physical / Service)' },
                { field: 'Explode Recipe on Sale', oldValue: '—', newValue: updatedRecord.explodeBomOnSale ? 'Yes (Deducts Sub-ingredients)' : 'No (Deducts Item Directly)' }
            ]);
            updatedRecord.createdAt = initialLog.formattedDate;
            updatedRecord.createdBy = `${currentUser.name} (${currentUser.role})`;
            updatedRecord.auditLogs = [initialLog];
            window.AppStore.products.unshift(updatedRecord);
            showToast(`Product "${updatedRecord.name}" successfully created!`);
        } else {
            const index = window.AppStore.products.findIndex(p => p.id === newId);
            if (index !== -1) {
                const existingProd = window.AppStore.products[index];
                updatedRecord.branches = existingProd.branches || updatedRecord.branches;
                updatedRecord.ledger = existingProd.ledger || updatedRecord.ledger;
                updatedRecord.createdAt = existingProd.createdAt || 'Sep 15, 2026 • 09:30 AM';
                updatedRecord.createdBy = existingProd.createdBy || 'Dorothy Diaz (System Admin)';

                const changes = [];
                if (existingProd.name !== updatedRecord.name) changes.push({ field: 'Product Name', oldValue: existingProd.name, newValue: updatedRecord.name });
                if (existingProd.sku !== updatedRecord.sku) changes.push({ field: 'SKU Code', oldValue: existingProd.sku, newValue: updatedRecord.sku });
                if (existingProd.category !== updatedRecord.category) changes.push({ field: 'Category', oldValue: existingProd.category, newValue: updatedRecord.category });
                if (existingProd.subcategory !== updatedRecord.subcategory) changes.push({ field: 'Subcategory', oldValue: existingProd.subcategory || 'Standard', newValue: updatedRecord.subcategory || 'Standard' });
                if (existingProd.unit !== updatedRecord.unit) changes.push({ field: 'Unit of Measure', oldValue: existingProd.unit, newValue: updatedRecord.unit });
                if (String(existingProd.packSize) !== String(updatedRecord.packSize)) changes.push({ field: 'Pack Size', oldValue: String(existingProd.packSize), newValue: String(updatedRecord.packSize) });
                if (Number(existingProd.costPrice) !== Number(updatedRecord.costPrice)) changes.push({ field: 'Cost Price', oldValue: formatPHP(existingProd.costPrice), newValue: formatPHP(updatedRecord.costPrice) });
                if (Number(existingProd.sellingPrice) !== Number(updatedRecord.sellingPrice)) changes.push({ field: 'Selling Price', oldValue: formatPHP(existingProd.sellingPrice), newValue: formatPHP(updatedRecord.sellingPrice) });
                if (existingProd.canBeSold !== updatedRecord.canBeSold) changes.push({ field: 'Can be Sold', oldValue: existingProd.canBeSold ? 'Yes' : 'No', newValue: updatedRecord.canBeSold ? 'Yes' : 'No' });
                if (existingProd.canBePurchased !== updatedRecord.canBePurchased) changes.push({ field: 'Can be Purchased', oldValue: existingProd.canBePurchased ? 'Yes' : 'No', newValue: updatedRecord.canBePurchased ? 'Yes' : 'No' });
                if ((existingProd.trackPhysicalStock ?? existingProd.trackStock) !== updatedRecord.trackPhysicalStock) changes.push({ field: 'Track Physical Stock', oldValue: (existingProd.trackPhysicalStock ?? existingProd.trackStock) ? 'Yes' : 'No', newValue: updatedRecord.trackPhysicalStock ? 'Yes' : 'No' });
                if ((existingProd.explodeBomOnSale ?? existingProd.hasBom) !== updatedRecord.explodeBomOnSale) changes.push({ field: 'Explode Recipe on Sale', oldValue: (existingProd.explodeBomOnSale ?? existingProd.hasBom) ? 'Yes' : 'No', newValue: updatedRecord.explodeBomOnSale ? 'Yes' : 'No' });
                if (existingProd.isActive !== updatedRecord.isActive) changes.push({ field: 'Status', oldValue: existingProd.isActive ? 'Active' : 'Inactive', newValue: updatedRecord.isActive ? 'Active' : 'Inactive' });

                if (changes.length > 0) {
                    let actionType = 'UPDATED';
                    if (changes.some(c => c.field === 'Selling Price' || c.field === 'Cost Price')) actionType = 'PRICE_CHANGE';
                    if (changes.some(c => c.field === 'Explode Recipe on Sale' || c.field === 'Has BOM')) actionType = 'BOM_CHANGE';

                    const log = createAuditEntry(currentUser, actionType, `Updated ${changes.length} product propert${changes.length === 1 ? 'y' : 'ies'}`, changes);
                    updatedRecord.auditLogs = [log, ...(existingProd.auditLogs || [])];
                } else {
                    updatedRecord.auditLogs = existingProd.auditLogs || [];
                }

                window.AppStore.products[index] = updatedRecord;
            }
            showToast(`Product "${updatedRecord.name}" successfully updated!`);
        }

        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));
        localStorage.removeItem('rms_stocks_ledger');

        // Auto-sync BOM catalog entry with rms_boms
        try {
            let boms = JSON.parse(localStorage.getItem('rms_boms')) || [];
            const existingBomIdx = boms.findIndex(b => b.productId === newId);
            if (updatedRecord.hasBom) {
                if (existingBomIdx === -1) {
                    boms.unshift({
                        productId: newId,
                        yield: 1,
                        ingredients: []
                    });
                    localStorage.setItem('rms_boms', JSON.stringify(boms));
                }
            } else {
                if (existingBomIdx !== -1 && (!boms[existingBomIdx].ingredients || boms[existingBomIdx].ingredients.length === 0)) {
                    boms.splice(existingBomIdx, 1);
                    localStorage.setItem('rms_boms', JSON.stringify(boms));
                }
            }
        } catch (e) {
            console.warn('BOM storage sync:', e);
        }

        closeProductDrawer();
    }

    // =========================================================================
    // IMPORT & EXPORT CSV CAPABILITIES
    // =========================================================================
    let importedParsedProducts = [];

    // Helper: Escape CSV fields properly
    function escapeCSV(val) {
        if (val === null || val === undefined) return '""';
        let str = String(val).replace(/"/g, '""');
        return `"${str}"`;
    }

    // Export current products to CSV
    window.exportProductsCSV = function() {
        const products = window.AppStore.products;
        if (!products || products.length === 0) {
            showToast('No products available to export.');
            return;
        }

        const headers = [
            'SKU', 'Product Name', 'Barcode', 'Product Description',
            'Category', 'Sub-category', 'UOM / Pack Size', 'Cost Price',
            'Selling Price', 'Tax Rate', 'Primary Supplier', 'Allergen Tags', 'Status'
        ];

        const rows = products.map(p => [
            escapeCSV(p.sku),
            escapeCSV(p.name),
            escapeCSV(p.barcode || ''),
            escapeCSV(p.description || ''),
            escapeCSV(p.category || ''),
            escapeCSV(p.subcategory || ''),
            escapeCSV(p.unit || ''),
            escapeCSV(p.costPrice || 0),
            escapeCSV(p.sellingPrice || 0),
            escapeCSV(p.taxRate || 0),
            escapeCSV(p.supplier || ''),
            escapeCSV((p.allergens || []).join('; ')),
            escapeCSV(p.status || 'Active')
        ]);

        const csvContent = '\uFEFF' + [headers.join(','), ...rows.map(r => r.join(','))].join('\r\n');
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        const now = new Date();
        const dateStr = now.toISOString().slice(0, 10);
        link.setAttribute('href', url);
        link.setAttribute('download', `RMS_Product_Catalog_${dateStr}.csv`);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);

        showToast(`Successfully exported ${products.length} products to CSV`);
    };

    const importModal = document.getElementById('importProductsModal');
    const importFileInput = document.getElementById('importFileInput');
    const importDropzone = document.getElementById('importDropzone');
    const importPreviewWrapper = document.getElementById('importPreviewWrapper');
    const importPreviewCount = document.getElementById('importPreviewCount');
    const importPreviewTbody = document.getElementById('importPreviewTbody');
    const btnConfirmImport = document.getElementById('btnConfirmImport');

    window.openImportModal = function() {
        importedParsedProducts = [];
        if (importFileInput) importFileInput.value = '';
        if (importPreviewWrapper) importPreviewWrapper.style.display = 'none';
        if (importPreviewTbody) importPreviewTbody.innerHTML = '';
        if (btnConfirmImport) btnConfirmImport.disabled = true;
        if (importModal) importModal.classList.add('is-open');
    };

    window.closeImportModal = function() {
        if (importModal) importModal.classList.remove('is-open');
    };

    // Drag and drop event listeners for dropzone
    if (importDropzone) {
        ['dragenter', 'dragover'].forEach(eventName => {
            importDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                importDropzone.classList.add('is-dragover');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            importDropzone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
                importDropzone.classList.remove('is-dragover');
            });
        });

        importDropzone.addEventListener('drop', (e) => {
            const files = e.dataTransfer.files;
            if (files && files.length > 0) {
                parseCSVFile(files[0]);
            }
        });
    }

    window.handleImportFileSelect = function(e) {
        const file = e.target.files && e.target.files[0];
        if (file) {
            parseCSVFile(file);
        }
    };

    function parseCSVLine(line) {
        const result = [];
        let cur = '';
        let inQuotes = false;
        for (let i = 0; i < line.length; i++) {
            const char = line[i];
            const nextChar = line[i + 1];
            if (char === '"') {
                if (inQuotes && nextChar === '"') {
                    cur += '"';
                    i++;
                } else {
                    inQuotes = !inQuotes;
                }
            } else if (char === ',' && !inQuotes) {
                result.push(cur.trim());
                cur = '';
            } else {
                cur += char;
            }
        }
        result.push(cur.trim());
        return result;
    }

    function parseCSVFile(file) {
        const reader = new FileReader();
        reader.onload = function(evt) {
            const text = evt.target.result;
            const lines = text.split(/\r?\n/).filter(line => line.trim().length > 0);
            if (lines.length < 2) {
                showToast('The selected CSV file does not contain header and row data.');
                return;
            }

            const headerRow = parseCSVLine(lines[0]).map(h => h.toLowerCase().replace(/[^a-z0-9]/g, ''));
            const skuIdx = headerRow.findIndex(h => h.includes('sku'));
            const nameIdx = headerRow.findIndex(h => h.includes('name') || h.includes('product'));
            const barcodeIdx = headerRow.findIndex(h => h.includes('barcode'));
            const descIdx = headerRow.findIndex(h => h.includes('desc'));
            const catIdx = headerRow.findIndex(h => h.includes('cat') && !h.includes('sub'));
            const subcatIdx = headerRow.findIndex(h => h.includes('subcat'));
            const uomIdx = headerRow.findIndex(h => h.includes('uom') || h.includes('unit') || h.includes('pack'));
            const costIdx = headerRow.findIndex(h => h.includes('cost'));
            const priceIdx = headerRow.findIndex(h => h.includes('sell') || h.includes('price'));
            const taxIdx = headerRow.findIndex(h => h.includes('tax'));
            const supIdx = headerRow.findIndex(h => h.includes('supp'));
            const allergenIdx = headerRow.findIndex(h => h.includes('allergen'));
            const statusIdx = headerRow.findIndex(h => h.includes('stat'));

            importedParsedProducts = [];

            for (let i = 1; i < lines.length; i++) {
                const cols = parseCSVLine(lines[i]);
                if (cols.length === 0 || cols.every(c => c === '')) continue;

                const name = nameIdx !== -1 && cols[nameIdx] ? cols[nameIdx] : `Imported Item ${i}`;
                const sku = skuIdx !== -1 && cols[skuIdx] ? cols[skuIdx] : `IMP-${1000 + i}`;
                const category = catIdx !== -1 && cols[catIdx] ? cols[catIdx] : (window.AppStore.categories[0] || 'Beverages');
                const costPrice = costIdx !== -1 ? (parseFloat(cols[costIdx].replace(/[^0-9.-]+/g, '')) || 0) : 0;
                const sellingPrice = priceIdx !== -1 ? (parseFloat(cols[priceIdx].replace(/[^0-9.-]+/g, '')) || 0) : 0;
                const barcode = barcodeIdx !== -1 ? cols[barcodeIdx] : '';
                const description = descIdx !== -1 ? cols[descIdx] : '';
                const subcategory = subcatIdx !== -1 ? cols[subcatIdx] : '';
                const unit = uomIdx !== -1 ? cols[uomIdx] : 'Piece';
                const taxRate = taxIdx !== -1 ? (parseFloat(cols[taxIdx].replace(/[^0-9.-]+/g, '')) || 12) : 12;
                const supplier = supIdx !== -1 ? cols[supIdx] : 'Standard Supplier';
                const allergens = allergenIdx !== -1 && cols[allergenIdx] ? cols[allergenIdx].split(/;|,\s*/).map(a => a.trim()).filter(Boolean) : [];
                const status = (statusIdx !== -1 && cols[statusIdx]) ? cols[statusIdx] : 'Active';

                importedParsedProducts.push({
                    id: Date.now() + i,
                    sku: sku,
                    name: name,
                    barcode: barcode,
                    description: description,
                    category: category,
                    subcategory: subcategory,
                    unit: unit,
                    costPrice: costPrice,
                    sellingPrice: sellingPrice,
                    taxRate: taxRate,
                    supplier: supplier,
                    allergens: allergens,
                    status: (status.toLowerCase().includes('inact') ? 'Inactive' : 'Active'),
                    stock: 0
                });
            }

            if (importedParsedProducts.length === 0) {
                showToast('No valid product rows found in the CSV file.');
                return;
            }

            // Populate preview
            importPreviewCount.textContent = `${importedParsedProducts.length} product(s) ready to import`;
            let previewRows = '';
            importedParsedProducts.slice(0, 10).forEach(p => {
                previewRows += `
                    <tr>
                        <td><strong>${p.sku}</strong></td>
                        <td>${p.name}</td>
                        <td><span class="inv-category-pill" style="font-size: 0.68rem; padding: 2px 7px;">${p.category}</span></td>
                        <td>$${p.sellingPrice.toFixed(2)}</td>
                        <td><span class="inv-status-pill ${p.status === 'Active' ? 'is-active' : 'is-inactive'}" style="font-size:0.68rem;">${p.status}</span></td>
                    </tr>
                `;
            });
            if (importedParsedProducts.length > 10) {
                previewRows += `<tr><td colspan="5" style="text-align:center; color: var(--inv-text-muted); font-style:italic;">...and ${importedParsedProducts.length - 10} more rows</td></tr>`;
            }
            importPreviewTbody.innerHTML = previewRows;
            importPreviewWrapper.style.display = 'block';
            btnConfirmImport.disabled = false;

            showToast(`${importedParsedProducts.length} products parsed from CSV.`);
        };
        reader.readAsText(file);
    }

    window.confirmImportProducts = function() {
        if (!importedParsedProducts || importedParsedProducts.length === 0) {
            showToast('No products ready for import.');
            return;
        }

        // Upsert into categories if new categories were discovered
        importedParsedProducts.forEach(p => {
            if (p.category && !window.AppStore.categories.includes(p.category)) {
                window.AppStore.categories.push(p.category);
            }
        });
        localStorage.setItem('rms_product_categories', JSON.stringify(window.AppStore.categories));

        // Merge or replace by SKU
        const existingProducts = [...window.AppStore.products];
        let addedCount = 0;
        let updatedCount = 0;

        importedParsedProducts.forEach(item => {
            const existingIdx = existingProducts.findIndex(p => p.sku.toLowerCase() === item.sku.toLowerCase());
            if (existingIdx !== -1) {
                existingProducts[existingIdx] = { ...existingProducts[existingIdx], ...item, id: existingProducts[existingIdx].id };
                updatedCount++;
            } else {
                existingProducts.push(item);
                addedCount++;
            }
        });

        window.AppStore.products = existingProducts;
        localStorage.setItem('rms_inventory_products', JSON.stringify(window.AppStore.products));

        syncCategoryControls();
        renderTableHeaders();
        renderMasterList();
        closeImportModal();

        showToast(`Import finished: ${addedCount} added, ${updatedCount} updated.`);
    };

    window.downloadSampleCSV = function() {
        const sampleHeaders = 'SKU,Product Name,Barcode,Product Description,Category,Sub-category,UOM / Pack Size,Cost Price,Selling Price,Tax Rate,Primary Supplier,Allergen Tags,Status\r\n';
        const sampleRows = 'SAMPLE-001,"Signature Roasted Coffee","4800019283999","Premium dark roasted whole bean coffee blend","Beverages","Hot Beverages","Bag 1kg",12.50,24.00,12,"Bean Roasters Inc.","None","Active"\r\nSAMPLE-002,"Matcha Crepe Slice","4800019283888","Multilayer green tea crepe cake with mascarpone","Pastries & Desserts","Cakes","Slice",3.20,7.50,12,"Artisan Bakery Co.","Dairy; Gluten","Active"\r\n';
        const blob = new Blob(['\uFEFF' + sampleHeaders + sampleRows], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = 'RMS_Product_Catalog_Sample.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        URL.revokeObjectURL(url);
    };

    // Close Category Autocomplete on outside click
    document.addEventListener('click', function(e) {
        const drop = document.getElementById('catAutocompleteDropdown');
        const inputWrap = document.querySelector('.inv-cat-input-wrapper');
        if (drop && drop.classList.contains('is-open')) {
            if (inputWrap && !inputWrap.contains(e.target)) {
                drop.classList.remove('is-open');
            }
        }
    });

    // Keyboard Shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const filterDropdown = document.getElementById('tableColumnFilterDropdown');
            if (filterDropdown && filterDropdown.classList.contains('is-active')) {
                filterDropdown.classList.remove('is-active');
                const triggerBtn = document.getElementById('btnTableColumnFilter');
                if (triggerBtn) triggerBtn.classList.remove('is-active');
                return;
            }
            if (importModal && importModal.classList.contains('is-open')) {
                closeImportModal();
                return;
            }
            if (newCategoryModal.classList.contains('is-open')) {
                closeNewCategoryModal();
                return;
            }
            if (drawerContainer.classList.contains('is-open')) {
                e.preventDefault();
                closeProductDrawer();
                return;
            }
        }

        if (e.key === 'F2') {
            e.preventDefault();
            window.openProductDrawer('new');
            return;
        }

        if (e.key === '/' && document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            e.preventDefault();
            searchInput.focus();
            searchInput.select();
        }
    });

    // Initialize View
    syncCategoryControls();
    renderTableHeaders();
    renderMasterList();

})();
</script>
@endpush
@endsection

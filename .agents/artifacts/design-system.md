# RMS Enterprise Design System & Token Index (HR Operations Reference Theme)

> **Source of Truth Module**: HR Operations (`resources/views/hr/*`, `assets/css/styles.css`, `assets/css/theme-tokens.css`)  
> **Status**: Frozen / Production Standard  
> **Target Consumers**: Purchasing, Inventory, Vendor Management, POS, Reporting, and Administration Modules.

---

## 1. Visual Identity & Design Pillars

The Restaurant Management System (RMS) HR Module features a **Modern Glassmorphic Lavender / Vibrant Mesh** aesthetic characterized by:
1. **Light Ambient Lilac Canvas**: `#faf7fd` paired with fluid, multi-stop radial gradient meshes.
2. **Glassmorphism at 90% Opacity**: Crisp, milky glass cards with `backdrop-filter: blur(24px) saturate(180%)`, inset micro-borders (`rgba(255, 255, 255, 0.95)`), and soft layered slate elevation shadows.
3. **Primary Pink-Purple Gradient Accents**: `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` used for primary calls-to-action, active indicators, and category highlights.
4. **Information Density & Industrial Ergonomics**: 11-13px typography scale with high data density, sticky sortable table headers, compact stat cards, and pure CSS instant tooltips.

---

## 2. Color Palette & Token Index

### 2.1 Brand & Action Accents

| Token Name | Hex / Value | Semantic Role / Usage |
|---|---|---|
| `--brand-primary` | `#9333ea` | Primary purple accent, active links, focus outlines |
| `--brand-primary-hover` | `#7c3aed` | Button & interactive element hover states |
| `--brand-accent-pink` | `#ec4899` | Primary gradient stop 1, prominent badges |
| `--brand-accent-deep-pink` | `#db2777` | Primary gradient hover stop 1 |
| `--brand-accent-indigo` | `#6366f1` | Secondary accents, metric cards |
| `--gradient-primary` | `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` | Primary action buttons (`.hr-btn-primary`, `.erp-btn-primary`) |
| `--gradient-primary-hover` | `linear-gradient(135deg, #db2777 0%, #9333ea 100%)` | Hover state for primary buttons |
| `--gradient-accent-strip` | `linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%)` | 3.5px top highlight bar on metric cards & active tabs |

### 2.2 Neutral & Surface Hierarchy

| Token Name | CSS Value | Visual Appearance / Role |
|---|---|---|
| `--canvas-bg` | `#faf7fd` | Global page background (Soft Lilac / Off-white) |
| `--surface-pure-white` | `#ffffff` | Right slide-over modals, form dropdown options |
| `--surface-glass-90` | `rgba(255, 255, 255, 0.90)` | Table cards, metric cards, filter bars (24px blur) |
| `--surface-glass-85` | `rgba(255, 255, 255, 0.85)` | Tab navigation containers (16px blur) |
| `--surface-glass-65` | `rgba(255, 255, 255, 0.65)` | Left sidebar rail, secondary action buttons |
| `--surface-subtle-gray` | `#f8fafc` | Table `<thead>` background, subtle input fills |
| `--surface-subtle-hover` | `rgba(245, 243, 255, 0.65)` | Table row `<tr>` hover highlight |

### 2.3 Semantic Status Palette (Badges, Alerts, KPI Metrics)

| State | Solid Hex | Badge Gradient Background | Badge Border | Text Color |
|---|---|---|---|---|
| **Success** (Active, Present, Approved) | `#10b981` | `linear-gradient(135deg, rgba(16,185,129,0.12), rgba(5,150,105,0.18))` | `rgba(16, 185, 129, 0.32)` | `#047857` |
| **Warning** (Pending, Review, Probationary) | `#f59e0b` | `linear-gradient(135deg, rgba(245,158,11,0.12), rgba(217,119,6,0.18))` | `rgba(245, 158, 11, 0.32)` | `#b45309` |
| **Danger** (Rejected, Absent, Terminated) | `#ef4444` | `linear-gradient(135deg, rgba(239,68,68,0.12), rgba(220,38,38,0.18))` | `rgba(239, 68, 68, 0.32)` | `#b91c1c` |
| **Info / Sky** (Enrolled, Scheduled) | `#0284c7` | `linear-gradient(135deg, rgba(59,130,246,0.12), rgba(14,165,233,0.18))` | `rgba(14, 165, 233, 0.32)` | `#0369a1` |
| **Purple** (Manager, Full-Time, Masterlist) | `#9333ea` | `linear-gradient(135deg, rgba(236,72,153,0.12), rgba(168,85,247,0.18))` | `rgba(168, 85, 247, 0.32)` | `#9333ea` |
| **Neutral** (Archived, Rest Day, Inactive) | `#334155` | `linear-gradient(135deg, rgba(241,245,249,0.95), rgba(226,232,240,0.85))` | `rgba(203, 213, 225, 0.8)` | `#334155` |

---

## 3. Typography Scale & Hierarchy

All fonts loaded from Google Fonts:
- **Body & Controls**: `'Poppins', sans-serif` (Weights: 300, 400, 500, 600, 700)
- **Display & Headings**: `'League Spartan', sans-serif` (Weights: 300..900)
- **KPI Metrics & Accents**: `'Outfit', sans-serif` (Weights: 500, 600, 700, 800)

| UI Element | Font Size | Weight | Line Height | Color | Letter Spacing |
|---|---|---|---|---|---|
| **KPI Metric Value** | `26px` (compact: `20px`) | `800` | `1.2` | `#0f172a` | `-0.02em` |
| **Page Title** | `22px` | `650` / `800` | `1.25` | `#0f172a` | `-0.02em` |
| **Modal / Drawer Title** | `17px` | `700` | `1.3` | `#0f172a` | `-0.015em` |
| **Table / Section Title**| `15px` | `600` | `1.3` | `#0f172a` | `-0.01em` |
| **Body / Input / Select**| `13px` | `400` - `500` | `1.4` | `#0f172a` / `#334155` | `0` |
| **Button Text** | `13px` (sm: `11.5px`) | `600` | `1.0` | `#ffffff` / `#334155` | `0` |
| **KPI Metric Label** | `12px` (compact: `11.5px`) | `600` | `1.2` | `#64748b` | `0` |
| **Table Column TH** | `11.5px` | `600` | `1.0` | `#475569` | `0.05em` (UPPERCASE) |
| **Badges & Tooltips** | `11px` | `700` | `1.0` | Variable | `0.02em` |
| **Subtext / Timestamps** | `11px` | `400` / `500` | `1.2` | `#94a3b8` | `0` |

---

## 4. Tooltip Specification (Pure CSS Caret-Anchored System)

The RMS Tooltip is an accessible, instant micro-interaction rendered via the `[data-tooltip]` attribute.

### 4.1 CSS Specification

```css
[data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 7px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    background: rgba(15, 23, 42, 0.94);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #ffffff;
    font-family: 'Poppins', sans-serif;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.02em;
    padding: 4.5px 9px;
    border-radius: 6px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3), 0 4px 8px -2px rgba(0, 0, 0, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.15);
    z-index: 100000;
}

/* Caret Arrow */
[data-tooltip]::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%) translateY(4px);
    border-width: 5px 4.5px 0 4.5px;
    border-style: solid;
    border-color: rgba(15, 23, 42, 0.94) transparent transparent transparent;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transition: opacity 0.18s cubic-bezier(0.16, 1, 0.3, 1), transform 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 100001;
}

[data-tooltip]:hover::after,
[data-tooltip]:hover::before {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(0);
}
```

### 4.2 Designer Usage Example

```html
<!-- Top Tooltip (Default) -->
<button class="hr-btn hr-btn-secondary" data-tooltip="Export attendance records to CSV">
    <i class="ph ph-download-simple"></i>
    <span>Export</span>
</button>

<!-- Right Tooltip (Sidebar & Rail Items) -->
<a href="#" class="rail-item" data-tooltip="Employee Masterlist" data-tooltip-pos="right">
    <i class="ph ph-users"></i>
</a>
```

---

## 5. Master Component Blueprints (Copy-Paste for Other Modules)

### 5.1 KPI / Metric Cards

#### Standard 4-Column Layout
```html
<div class="hr-metrics-grid">
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">1,428</span>
            <span class="hr-metric-label">Total Invoices</span>
        </div>
        <div class="hr-metric-icon purple">
            <i class="ph ph-receipt"></i>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">$84,210.00</span>
            <span class="hr-metric-label">Paid Volume</span>
        </div>
        <div class="hr-metric-icon emerald">
            <i class="ph ph-currency-circle-dollar"></i>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">14</span>
            <span class="hr-metric-label">Pending Approval</span>
        </div>
        <div class="hr-metric-icon amber">
            <i class="ph ph-clock-countdown"></i>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">3</span>
            <span class="hr-metric-label">Disputed / Overdue</span>
        </div>
        <div class="hr-metric-icon rose">
            <i class="ph ph-warning-circle"></i>
        </div>
    </div>
</div>
```

#### Compact 5-Column Summary Row (as seen in Employees View)
```html
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-bottom: 12px;">
    <div class="hr-stat-card">
        <div class="hr-stat-icon-wrap" style="background: rgba(168, 85, 247, 0.15); color: #9333ea;">
            <i class="ph ph-users"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Active Vendors</span>
            <div class="hr-stat-value">64</div>
            <span class="hr-stat-sub">Verified Suppliers</span>
        </div>
    </div>
</div>
```

### 5.2 Filter Bar & Form Inputs
```html
<div class="hr-filter-bar">
    <form class="hr-filter-form" method="GET">
        <!-- Search Input -->
        <input type="text" name="search" class="hr-input" placeholder="Search by name, code or SKU..." style="min-width: 260px;">
        
        <!-- Glassmorphic Dropdown -->
        <select name="status" class="hr-select">
            <option value="">All Statuses</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
        </select>
        
        <!-- Date Filter -->
        <input type="date" name="from_date" class="hr-input">
        
        <!-- Filter Buttons -->
        <button type="submit" class="hr-btn hr-btn-secondary" data-tooltip="Apply Filter Criteria">
            <i class="ph ph-funnel"></i>
            <span>Filter</span>
        </button>
        <a href="?" class="hr-btn hr-btn-secondary" data-tooltip="Reset all filters">
            <i class="ph ph-arrow-counter-clockwise"></i>
        </a>
    </form>
</div>
```

### 5.3 Glassmorphic Data Table
```html
<div class="hr-table-card">
    <div class="hr-table-header">
        <div class="hr-table-title">
            <i class="ph ph-table" style="color: #7c3aed;"></i>
            <span>Master Listing</span>
            <span class="hr-badge hr-badge-purple">250 Total</span>
        </div>
        <div class="hr-table-actions" style="display: flex; gap: 8px;">
            <button class="hr-btn hr-btn-secondary hr-btn-sm" data-tooltip="Print View">
                <i class="ph ph-printer"></i>
            </button>
        </div>
    </div>
    
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th class="sortable">Code <i class="ph ph-caret-up-down sort-icon"></i></th>
                    <th class="sortable">Item Name <i class="ph ph-caret-up-down sort-icon"></i></th>
                    <th>Category</th>
                    <th>Status</th>
                    <th style="text-align: right;">Unit Price</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>#VND-0014</strong></td>
                    <td>Organic Arabica Beans 1kg</td>
                    <td><span class="hr-badge hr-badge-blue">Raw Material</span></td>
                    <td><span class="hr-badge hr-badge-success">In Stock</span></td>
                    <td style="text-align: right; font-weight: 600;">$24.50</td>
                    <td style="text-align: right;">
                        <button class="hr-btn hr-btn-secondary hr-btn-sm" data-tooltip="Edit Item">
                            <i class="ph ph-pencil-simple"></i>
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
```

### 5.4 Glass Navigation Tabs Bar
```html
<div class="hr-profile-nav-wrap">
    <div class="hr-profile-tabs">
        <button class="hr-tab-btn active">
            <i class="ph ph-user"></i>
            <span>Overview</span>
        </button>
        <button class="hr-tab-btn">
            <i class="ph ph-receipt"></i>
            <span>Transactions</span>
            <span class="hr-badge hr-badge-purple" style="font-size: 10px; padding: 2px 6px;">12</span>
        </button>
        <button class="hr-tab-btn">
            <i class="ph ph-clock-counter-clockwise"></i>
            <span>Audit History</span>
        </button>
    </div>
</div>
```

### 5.5 Right Slide-Over Modal Drawer
```html
<div class="hr-modal-overlay" id="createItemModal" onclick="if(event.target === this) closeModal('createItemModal')">
    <div class="hr-modal" role="dialog" aria-modal="true" style="max-width: 620px;">
        <div class="hr-modal-header">
            <h3 class="hr-modal-title">
                <i class="ph ph-plus-circle"></i>
                <span>Create New Record</span>
            </h3>
            <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="closeModal('createItemModal')" data-tooltip="Close Drawer (Esc)">
                <i class="ph ph-x"></i>
            </button>
        </div>
        
        <form method="POST" action="/save" style="display: flex; flex-direction: column; flex: 1 1 0%; overflow: hidden;">
            <div class="hr-modal-body" style="padding: 24px 28px; overflow-y: auto; flex: 1 1 0%;">
                <!-- Form Fields Here -->
                <div style="margin-bottom: 16px;">
                    <label style="display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 6px;">
                        Record Title <span style="color: #ef4444;">*</span>
                    </label>
                    <input type="text" name="title" class="hr-input" style="width: 100%;" required>
                </div>
            </div>
            
            <div class="hr-modal-footer" style="padding: 16px 28px; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 10px; background: #ffffff;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('createItemModal')">
                    Cancel
                </button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-floppy-disk"></i>
                    <span>Save Changes</span>
                </button>
            </div>
        </form>
    </div>
</div>
```

---

## 6. Sizing, Radii, and Spacing Matrix

| Dimension | CSS Variable | Value | Implementation Context |
|---|---|---|---|
| **Border Radius Pill** | `--radius-pill` | `9999px` | Role pills, status badges |
| **Border Radius Badge**| `--radius-badge`| `20px` | Glassmorphic badges (`.hr-badge`) |
| **Border Radius Card** | `--radius-card` | `18px` | Main containers, table cards |
| **Border Radius Modal**| `--radius-modal`| `20px 0 0 20px`| Slide-over right drawer |
| **Border Radius Metric**| `--radius-metric`| `16px` | Metric stat cards, filter bar |
| **Border Radius Control**| `--radius-input` | `10px` | Inputs, selects, buttons |
| **Border Radius Tooltip**| `--radius-tooltip`| `6px` | Dark caret tooltips |
| **Table Max Height** | - | `560px` | Sticky scroll container |
| **Touch Target Min** | - | `44px` / `38px` | Buttons, navigation anchors |
| **Grid Gap Standard**| - | `16px` / `24px` | Metric grids, page section layouts |

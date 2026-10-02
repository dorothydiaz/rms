# RMS Enterprise Design System & Token Index (HR Module Reference Theme)

This document is the official theme index extracted from the **HR Operations Module**. Use these exact tokens, variables, classes, and component patterns when designing or modernizing other modules across RMS (e.g., Purchasing, Inventory, Vendor Bills, Point of Sale, and Reporting).

---

## Quick Reference Links
- CSS Variables & Tokens: [`assets/css/theme-tokens.css`](file:///c:/Users/JABIGUERO/Documents/GitHub/rms/assets/css/theme-tokens.css)
- Master Stylesheet: [`assets/css/styles.css`](file:///c:/Users/JABIGUERO/Documents/GitHub/rms/assets/css/styles.css)
- App Shell Layout: [`resources/views/layouts/app.blade.php`](file:///c:/Users/JABIGUERO/Documents/GitHub/rms/resources/views/layouts/app.blade.php)

---

## 1. Core Color Swatches

### 1.1 Brand & Action Colors
- **Primary Gradient**: `linear-gradient(135deg, #ec4899 0%, #a855f7 100%)` (Pink 500 to Purple 500)
- **Primary Gradient Hover**: `linear-gradient(135deg, #db2777 0%, #9333ea 100%)`
- **Accent Purple**: `#9333ea` / `#7c3aed`
- **Accent Pink**: `#ec4899` / `#db2777`
- **Indigo Accent**: `#6366f1`
- **Top Card Accent Strip**: `linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%)` (3.5px height)

### 1.2 Neutral & Surface Spectrum
- **Canvas / Background**: `#faf7fd` (Lilac-tinted light canvas)
- **Glass Card Surface**: `rgba(255, 255, 255, 0.90)` + `backdrop-filter: blur(24px) saturate(180%)`
- **Slide-over Modal Surface**: `#ffffff` (Pure white for readability)
- **Table Sticky Header**: `#f8fafc`
- **Table Row Hover**: `rgba(245, 243, 255, 0.65)` (Subtle violet glow)
- **Primary Text**: `#0f172a` (Slate 900)
- **Secondary Text**: `#334155` (Slate 700) / `#475569` (Slate 600)
- **Muted Text / Labels**: `#64748b` (Slate 500)
- **Hints / Placeholders**: `#94a3b8` (Slate 400)
- **Subtle Borders**: `#e2e8f0` / `rgba(226, 232, 240, 0.85)`

### 1.3 Semantic Status Palette

| State | Status Text | Solid Hex | Badge Gradient Background | Badge Border |
|---|---|---|---|---|
| **Success** | Active / Completed / Present | `#047857` | `linear-gradient(135deg, rgba(16,185,129,0.12), rgba(5,150,105,0.18))` | `rgba(16, 185, 129, 0.32)` |
| **Warning** | Pending / Review / Tardiness | `#b45309` | `linear-gradient(135deg, rgba(245,158,11,0.12), rgba(217,119,6,0.18))` | `rgba(245, 158, 11, 0.32)` |
| **Danger** | Rejected / Absent / Failed | `#b91c1c` | `linear-gradient(135deg, rgba(239,68,68,0.12), rgba(220,38,38,0.18))` | `rgba(239, 68, 68, 0.32)` |
| **Info / Sky** | Scheduled / In Progress | `#0369a1` | `linear-gradient(135deg, rgba(59,130,246,0.12), rgba(14,165,233,0.18))` | `rgba(14, 165, 233, 0.32)` |
| **Neutral** | Regular / Rest Day / Recorded | `#334155` | `linear-gradient(135deg, rgba(241,245,249,0.95), rgba(226,232,240,0.85))` | `rgba(203, 213, 225, 0.8)` |
| **Purple** | Full-Time / Masterlist Count | `#9333ea` | `linear-gradient(135deg, rgba(236,72,153,0.12), rgba(168,85,247,0.18))` | `rgba(168, 85, 247, 0.32)` |

---

## 2. Typography Specification

### 2.1 Font Families
- **Body & Controls**: `'Poppins', sans-serif`
- **Headings & Display**: `'League Spartan', sans-serif` (or `'Poppins', sans-serif`)
- **Metrics & Accents**: `'Outfit', sans-serif`

### 2.2 Sizing Scale
- **Display / Major KPI**: `26px` (compact: `20px`), weight `800`, letter-spacing `-0.02em`
- **Page Title**: `22px`, weight `650` - `800`, letter-spacing `-0.02em`
- **Modal Drawer Title**: `17px`, weight `700`, letter-spacing `-0.015em`
- **Card / Table Title**: `15px`, weight `600`, letter-spacing `-0.01em`
- **Body Text / Form Inputs**: `13px`, weight `400` / `500`
- **Button Text**: `13px` (small: `11.5px`), weight `600`
- **Metric Label**: `12px` (compact: `11.5px`), weight `600`, color `#64748b`
- **Table Column Headers (`th`)**: `11.5px`, weight `600`, uppercase, tracking `0.05em`, color `#475569`
- **Status Badges & Tooltips**: `11px`, weight `700`, tracking `0.02em`
- **Pills / Micro Tags**: `10.5px`, weight `750`

---

## 3. Tooltip System (`[data-tooltip]`)

RMS uses a lightweight, CSS-only tooltip system. Simply attach `data-tooltip="..."` to any element.

### Default Top Tooltip:
```html
<button class="hr-btn hr-btn-secondary" data-tooltip="Export vendor listing to CSV">
    <i class="ph ph-download-simple"></i>
    <span>Export</span>
</button>
```

### Right-Anchored Tooltip (for Rails / Icon Buttons):
```html
<a href="/vendors" class="rail-item" data-tooltip="Vendors Masterlist" data-tooltip-pos="right">
    <i class="ph ph-buildings"></i>
</a>
```

### Tooltip Visual Specs:
- **Background**: `rgba(15, 23, 42, 0.94)` (Dark Slate) with `backdrop-filter: blur(12px)`
- **Color**: `#ffffff`
- **Font Size**: `11px`, weight `700`, letter-spacing `0.02em`
- **Padding**: `4.5px 9px`, border-radius `6px`
- **Border**: `1px solid rgba(255, 255, 255, 0.15)`
- **Caret**: Solid triangular CSS arrow attached to anchor edge.

---

## 4. UI Components Quick Reference

### 4.1 Buttons
- **Primary**: `.hr-btn.hr-btn-primary` (Gradient Pink-Purple, white text, glowing shadow)
- **Secondary**: `.hr-btn.hr-btn-secondary` (Glass surface 65%, Slate text, hover violet tint)
- **Success**: `.hr-btn.hr-btn-success` (Emerald gradient, white text)
- **Danger**: `.hr-btn.hr-btn-danger` (Rose gradient, white text)
- **Small Size**: `.hr-btn-sm` (Padding: `6px 10px`, font size: `11.5px`)

### 4.2 Containers & Elevation
- **Main Glass Card**: `.hr-card` (Radius: `18px`, padding: `22px`, glass 90%)
- **Table Card**: `.hr-table-card` (Radius: `18px`, padding: `0`, with `.hr-table-header` and `.hr-table-wrapper`)
- **Metric Cards Grid**: `.hr-metrics-grid` with `.hr-metric-card`
- **Filter Bar**: `.hr-filter-bar` with `.hr-filter-form`
- **Form Controls**: `.hr-input`, `.hr-select`, `.hr-textarea` (Focus ring: `0 0 0 3.5px rgba(168, 85, 247, 0.18)`)
- **Slide-over Modal**: `.hr-modal-overlay` with `.hr-modal` (Slide from right, max-width `620px` to `760px`)

---

## 5. CSS Class Mapping Table

| Component Type | HR Original Class | Universal Theme Alias |
|---|---|---|
| Card Container | `.hr-card` | `.erp-card` |
| Table Container | `.hr-table-card` | `.erp-table-card` |
| Primary Button | `.hr-btn-primary` | `.erp-btn-primary` |
| Secondary Button| `.hr-btn-secondary`| `.erp-btn-secondary` |
| Filter Bar | `.hr-filter-bar` | `.erp-filter-bar` |
| Form Input | `.hr-input` | `.erp-input` |
| Dropdown Select | `.hr-select` | `.erp-select` |
| Table Root | `.hr-table` | `.erp-table` |
| Metric Stat Card| `.hr-metric-card` | `.erp-metric-card` |
| Status Badge | `.hr-badge` | `.erp-badge` |
| Right Modal | `.hr-modal` | `.erp-modal` |
| Tooltip | `[data-tooltip]` | `[data-erp-tooltip]` |

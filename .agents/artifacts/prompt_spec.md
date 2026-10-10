# Prompt Specification: Inventory Module - Product Management

## 1. Core Intent
Build the **Inventory Module: Product Management (`product-categories.blade.php`)** adhering strictly to the user's architectural reference and theme:
1. **Background Layer (Master List):**
   - **Header Section:** Full-width horizontal bar with page title ("Product Management") on the left and primary action button ("+ Add Product", hotkey F2) on the right.
   - **Filter Bar:** Secondary horizontal row directly above table containing search input field (`Fuse.js` / fuzzy filter, barcode quick focus), category filter, and quick view toggles.
   - **Grouped Data Grid:** Multi-column table partitioned by horizontal "Group Headers" (by Category, e.g., "Beverages", "Main Course", "Pastries & Desserts", "Raw Ingredients") spanning the entire width.
   - **Columns:** 5 standard data columns (Product / SKU, Barcode, Unit / Spec, Cost Price, Selling Price) followed by a right-aligned action column with a "View" link/button.
   - **Row Alignment:** Standard linear rows nested under each group header.
2. **Foreground Layer (Slide-out Detail Drawer):**
   - **Container:** Vertical drawer panel anchored to the right edge of the screen, overlaying ~35-40% of the screen with glassmorphic backdrop.
   - **Panel Header:** Top bar with title on left and "Close (✕)" icon / Esc hotkey on right.
   - **Primary Navigation:** Horizontal tab bar immediately below panel header (e.g. "General Info", "Inventory & Units", "Pricing & Tax", "Suppliers").
   - **Form Content (Vertical Stack):** Stacked input fields: Single-line text inputs (SKU, Product Name, Barcode), multi-line textarea (Description), checkbox with label ("Active / Available for Sale", "Track Stock"), and dropdown selectors (Category, Unit of Measure, Tax Group).
   - **Nested Content Section:** Secondary horizontal tab bar in the middle of the panel ("Stock Levels per Branch", "Recent Movements", "Recipe Components"), with nested data sub-table directly below.
   - **Panel Footer:** Fixed bottom section with two right-aligned action buttons ("Cancel" and "Save Changes").

## 2. Explicit Constraints
- **Framework & Stack:** Laravel 12 on PHP 8.2+, Blade Templating, Vanilla CSS adhering to `assets/css/styles.css` RMS glassmorphism, Phosphor Icons (`ph ph-*`), and high-performance client JS.
- **Theme Consistency:** Use RMS Design System tokens (`--font-family: 'Poppins'`, glass borders, soft shadows, `#a855f7` / `#ec4899` gradient accents, `#0f172a` strong text, `#64748b` muted text).
- **Industrial Ergonomics (Modern UX Designer):** 
  - Fitts's Law CTAs, keyboard shortcuts (`F2` to Add Product, `Esc` to Close Drawer, `/` to focus search).
  - Smooth 200ms spring drawer transition, pessimistic UI locking simulation, and soft toast notifications.
  - Zero Cumulative Layout Shift (CLS) and responsive horizontal overflow wrapping for data grids.

## 3. Non-Goals
- Complex multi-stage database migrations requiring external server connection if local DB is not currently running migrations. Provide rich mock/live fallback data model so the view works instantly in the browser and controller.
- Modifying unrelated HR or Sales modules.

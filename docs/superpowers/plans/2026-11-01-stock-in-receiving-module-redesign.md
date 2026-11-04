# Stock In / Receiving (GRN) Module Redesign & PO Integration Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:subagent-driven-development` (recommended) or `superpowers:executing-plans` to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Re-architect the Stock In / Receiving (GRN) module into an industrial, PO-connected physical intake engine that strips all financial cost clutter from the receiving floor, connects seamlessly to configured Warehouses and Movement Types with intelligent defaults, synchronizes Warehouse selection with RFQ and PO dropdowns, and handles partial shipments with backorder management.

**Architecture:** 
- **Frontend:** Dual-Mode ERP Workspace (Intake Queue / Masterlist vs. Stock Intake Builder) matching the Purchase Order design system, equipped with a 3-stage intake ribbon, barcode-ready item table (purely physical quantities, custom UOMs, batch/lot, and target bins—zero financial cost clutter), and a live printable GRN Document Mockup.
- **Backend & Domain Orchestration:** Thin controller / Action workflow utilizing `DB::transaction()` and pessimistic locking (`lockForUpdate()`) to update Purchase Order line fulfillments, resolve warehouse-scoped Movement Types, generate immutable append-only `StockLedger` entries (`SYS_LEDGER`), and support Backorder vs. Short-Close policies.
- **Cross-Module Linkage:** Active Warehouses dynamically hydrate dropdowns across RFQ, PO, and Stock In. Selecting a Warehouse automatically defaults the appropriate Inbound Movement Type and scopes storage bin destinations.

**Tech Stack:** Laravel 11 (PHP 8.3), Blade Components, Custom UOM Registry (`<x-uom-dropdown>` / `UomRegistry`), Vanilla ES6 JS (`AppStore` pattern), Modern CSS Grid/Flexbox with OKLCH tokens, Vite.

**Spec / Requirements:**
- Connect Stock In directly to Purchase Orders (auto-populate intake lines, track received quantities, handle backorders).
- Remove all cost/financial figures (PHP currency, taxes, prices, vendor settlement) from the front-end receiving view; focus strictly on physical inventory, UOMs, quantities, and inspection.
- Connect Stock In to configured Warehouses (`WH-MAIN`, `WH-COLD`, `WH-BGC`) with an explicit field for Movement Type that auto-defaults based on warehouse selection.
- Connect active Warehouses dynamically to RFQ and PO delivery location dropdowns.
- Provide a clean, modern UI following the PO workspace aesthetic with incredible features and industrial ergonomics.

---

## Global Constraints & User Rules
1. **Pillar 1 User Rule 1 (Master Ledger):** All physical inventory receipts must generate append-only immutable records in `inventory_ledger` with `before_quantity` and `after_quantity`.
2. **Pillar 3 User Rule 9 & 11 (Locking & Deferred Writes):** Use `DB::transaction()` with pessimistic locking (`lockForUpdate()`) on items and purchase orders. Zero partial writes if validation fails.
3. **Pillar 4 User Rule 14 & 15 (Sanitization & Identity):** Scrub all incoming quantities through strict numeric parsers. Discard client user payloads; natively stamp `auth()->user()->name` / `auth()->user()->email`.
4. **Standardized Custom UOM Component:** All unit inputs must strictly utilize `<x-uom-dropdown>` / `UomRegistry`. Plain native `<select>` tags for UOM are strictly prohibited.
5. **No External ERP Naming:** Do not mention or reference external commercial software (`Odoo`, etc.) anywhere in files or comments.
6. **No Backdrop-Filter Blur:** Retain high rendering performance on industrial floor tablets by avoiding expensive blur filters.

---

## 🎨 Modern UX Layout Audit & Executive Scorecard

### Scorecard
- **Spatial Layout & Dual-Mode Flow:** `10 / 10` (Seamless toggle between Incoming Orders Queue and Intake Sheet Builder).
- **Information Architecture & Density:** `10 / 10` (Stripped of financial noise; 100% focused on physical verification, variances, UOMs, and bins).
- **Industrial Ergonomics & Scanner Readiness:** `10 / 10` (Prominent barcode scan field, one-click "+ Receive All" and "- Reset", sticky headers, hotkeys).
- **Responsive & Modal Polish:** `10 / 10` (Live GRN Document Mockup, SweetAlert Backorder prompt, responsive grid).

### Visual Layout Blueprint
```
+--------------------------------------------------------------------------------------------------+
| TITLE: Stock In / Receiving (GRN)                                  [Stocks Overview] [Item Master] |
| Subtitle: Purchase Order Intake, Barcode Verification, Backlog Tracking & Physical Putaway       |
+--------------------------------------------------------------------------------------------------+
| KPI BAR: [12 Units Pending Intake] | [840 Units Received Today] | [3 Active Backorders] | [3 Hubs] |
+--------------------------------------------------------------------------------------------------+
| TABS: [Mode A: Incoming Orders Queue (4 Ready)]  |  [Mode B: Stock Intake Sheet / GRN Builder]   |
+--------------------------------------------------------------------------------------------------+
| MODE B: INTAKE BUILDER                                                                           |
| +----------------------------------------------------------------------------------------------+ |
| | STAGE RIBBON: (1) Intake Specification  -->  (2) Verification & QC  -->  (3) Putaway Commit | |
| +----------------------------------------------------------------------------------------------+ |
| | HEADER GRID (4-Col):                                                                         | |
| | [Target PO #: PO-2026-0109 v] [Vendor: ABC Logistics] [Warehouse: WH-MAIN v]               | |
| | [Movement Type: WH-MAIN-IN (2-Step Staged) v] [Delivery Slip #: DS-9921] [Carrier: Lalamove] | |
| +----------------------------------------------------------------------------------------------+ |
| | LINE ITEMS TABLE & FULFILLMENT SPECS (Cost-Free Floor Mode):                                 | |
| | # | SKU / Item Description | UOM | Ordered | Prev Recv | Receive Now | Balance | Bin / Loc | |
| | 1 | RAW-COFFEE-01 Arabica  | KG  |  100.00 |     20.00 |      80.00  |    0.00 | BIN-A1-01 | |
| | 2 | SYR-VAN-01 Vanilla     | BTL |   50.00 |      0.00 |      30.00  |   20.00 | BIN-A1-02 | |
| +----------------------------------------------------------------------------------------------+ |
| | FOOTER:                                                 | SIDEBAR:                           | |
| | Total Ordered: 150.00 | Recv Now: 110.00 | Backlog: 20  | LIVE GRN DOCUMENT MOCKUP           | |
| | Fulfillment Rate: [========== 73.3% ==========]         | (Live printable Goods Receipt Note | |
| | [Clear All]  [+ Receive All Remaining]                  | with barcodes, receiver stamp, &   | |
| |                                   [Confirm Receipt & Putaway (F10)] | destination warehouse) | |
+--------------------------------------------------------------------------------------------------+
```

---

## Data Invariant & Boundary Matrix

| Entity / Field | Type | Allowed Bounds / Values | Nullable | DB Constraint | Fail-State Action |
|---|---|---|---|---|---|
| `po_number` | string | Valid issued PO or null (Bypass PO) | Yes | `purchase_order_header.po_number` | Reject if non-existent when provided |
| `warehouse_id` | string | `WH-MAIN`, `WH-COLD`, `WH-BGC`, etc. | No | In active warehouses list | Fallback to `WH-MAIN` |
| `movement_type_id` | string | Valid Movement Type code | No | In configured movement types | Auto-default to warehouse primary |
| `received_qty` | decimal | $\ge 0.00$, $\le \text{Ordered Qty} \times 1.2$ | No | Non-negative numeric | Flash error if negative or absurd over-receipt |
| `backorder_policy` | enum | `'CREATE_BACKORDER'`, `'SHORT_CLOSE'` | No | Handled on partial receipts | Trigger modal prompt to supervisor |
| `storage_location` | string | Physical area barcode / name | No | Exists in warehouse areas | Fallback to warehouse default dock/zone |
| `batch_lot_no` | string | Alphanumeric (e.g. `LOT-2026-X`) | Yes | Max 100 chars | Allowed blank for non-perishables |
| `expiry_date` | date | Valid YYYY-MM-DD | Yes | Nullable date | Required if item has expiry tracking |

---

## Detailed Implementation Tasks

### Task 1: Cross-Module Warehouse & Movement Type Data Contract
**Files to Modify:**
- `app/Http/Controllers/PurchaseController.php`
- `resources/views/purchase/purchase-orders.blade.php`
- `resources/views/purchase/request-quotations.blade.php`
- `app/Http/Controllers/InventoryController.php`

- [ ] **Step 1.1: Inject active warehouses into PurchaseController**
  - In `PurchaseController::purchaseOrders()`, fetch active warehouses from `InventoryController` warehouse cache or defaults (`WH-MAIN`, `WH-COLD`, `WH-BGC`).
  - Pass `'initialWarehouses' => $warehouses` to `purchase.purchase-orders` and `purchase.request-quotations`.
- [ ] **Step 1.2: Update RFQ and PO Blade templates with dynamic Warehouse dropdowns**
  - In `resources/views/purchase/request-quotations.blade.php`, replace hardcoded `<select id="rfqDeliveryLocation">` and `<select id="approvalDestination">` with `@foreach($initialWarehouses as $wh)` options.
  - In `resources/views/purchase/purchase-orders.blade.php`, replace hardcoded `<select id="poDeliveryLocation">` with `@foreach($initialWarehouses as $wh)` options.
- [ ] **Step 1.3: Update InventoryController::stockIn() hydration payload**
  - Ensure `stockIn()` passes `initialWarehouses`, `initialStorageAreas`, `initialMovementTypes`, `initialPurchaseOrders`, and `initialProducts`.

---

### Task 2: Stock In Frontend Redesign (Cost-Free Physical Operations)
**Files to Modify:**
- `resources/views/inventory/stock-in.blade.php`

- [ ] **Step 2.1: Implement Header & Metric Summary Bar**
  - Page title: `Stock In / Receiving (GRN)`. Subtitle: `Purchase Order Intake, Barcode Verification, Backlog Tracking & Physical Putaway`.
  - 4 KPI cards:
    1. *Pending Intake POs* (Count of approved/ready POs).
    2. *Units Received Today* (Sum of physical intake today).
    3. *Active Backorders* (Count of partially received orders).
    4. *Receiving Facilities* (List of active warehouse hubs).
- [ ] **Step 2.2: Implement Mode Tabs & Queue Table (Mode A: Incoming Orders Queue)**
  - Tab 1: `Incoming Orders Queue` (Filter by Warehouse, Vendor, Status; Table showing PO #, Order Date, Vendor, Delivery Facility, Total Ordered Units, Fulfilled Units, Backlog Units, Fulfillment Status, and Action `[Receive Stock]`).
  - **Zero financial costs or PHP prices in this table.**
- [ ] **Step 2.3: Implement Mode B: Stock Intake Sheet / GRN Builder**
  - 3-Stage Progress Ribbon: `1. Intake Prep` $\rightarrow$ `2. Barcode & Quality Check` $\rightarrow$ `3. Putaway & Commit`.
  - Header Grid:
    - Target Purchase Order selector (`PO-2026-XXXX` or `Bypass PO / Direct Intake`).
    - Vendor Partner (badge/text).
    - Delivery Warehouse dropdown (`WH-MAIN`, `WH-COLD`, `WH-BGC`).
    - **Movement Type field**: Dropdown defaulting to the selected warehouse's primary inbound movement type (e.g. `WH-MAIN-IN (2-Step Staged)`), with a visual badge displaying its route sequence.
    - Delivery Slip # & Carrier input.
    - Receiving Date & Time (read-only timestamp).
- [ ] **Step 2.4: Implement Physical Fulfillment Line Items Table**
  - Columns: `#`, `SKU / Item Description`, `Base UOM` (using standard `<x-uom-dropdown>` or badge), `Ordered Qty`, `Previously Received`, `Receive Now Qty` (interactive number input with quick buttons `[+ All]` and `[- 0]`), `Remaining Balance`, `Target Bin / Area` (dropdown populated by selected warehouse's storage areas), `Batch/Lot #`, `Expiry Date`, `Action`.
  - Fast Toolbar: `[+ Receive All Remaining]`, `[Reset Quantities]`, `[Add Ad-Hoc Item]`.
- [ ] **Step 2.5: Implement Stock Fulfillment Summary & Live GRN Document Mockup**
  - Replace payment/settlement breakdown with **Physical Fulfillment Bar**:
    - Total Units Expected, Units Verified Now, Backorder Remainder, Progress Bar %.
  - Right panel: **Live GRN Document Mockup** (interactive preview showing official Goods Receipt Note with GRN barcode, PO ref, warehouse destination, and line items with lot numbers).

---

### Task 3: CSS & Industrial Ergonomics Optimization
**Files to Modify:**
- `resources/css/modules/stock-in.css`

- [ ] **Step 3.1: Modern Design System Harmony**
  - Match the purple/neutral palette established in Purchase Orders and Warehouse Configuration.
  - Apply clean borders, soft shadows (`0 1px 3px rgba(0,0,0,0.03)`), no expensive `backdrop-filter: blur()`.
- [ ] **Step 3.2: Table Scrolling & Visual Hierarchy**
  - Sticky table headers (`thead th { position: sticky; top: 0; background: #fafbfc; z-index: 10; }`).
  - `overflow-x: auto` container preventing page layout blowouts.
  - Flash animations for barcode verification (`@keyframes scanFlashGreen`).
- [ ] **Step 3.3: Live Document Mockup Styling**
  - Authentic warehouse document styling (paper shadow, barcode font/render, tabular alignment, signed inspector stamp area).

---

### Task 4: Frontend State & Barcode Verification Engine
**Files to Modify:**
- `resources/js/modules/stock-in.js`

- [ ] **Step 4.1: State Management & PO Ingestion**
  - Initialize `window.StockInStore` with `purchaseOrders`, `warehouses`, `storageAreas`, `movementTypes`, `currentReceipt`.
  - Function `loadPoIntoIntake(poNumber)`:
    - Copies PO line items into intake rows.
    - Automatically sets `warehouse_id` from PO's `delivery_location`.
    - Automatically sets default `movement_type_id` for that warehouse.
    - Filters `Target Bin` dropdown options to the selected warehouse's storage areas.
- [ ] **Step 4.2: Warehouse & Movement Type Reactive Binding**
  - On warehouse change:
    - Filter movement types to those matching `warehouse_id` and kind `Inbound Receipt`.
    - Default to the warehouse's primary movement type.
    - Update Route preview pill.
    - Refresh Target Bin dropdowns in table rows.
- [ ] **Step 4.3: Physical Fulfillment Calculation & Live Mockup Sync**
  - Function `calculateFulfillment()`:
    - Computes Total Expected, Received Now, and Balance.
    - Updates progress bar and live GRN document mockup immediately.
- [ ] **Step 4.4: Partial Shipment & Backorder Workflow**
  - Function `validateAndSubmitReceipt()`:
    - Checks if any item has `Receive Now < Ordered Qty - Previously Received`.
    - If partial, triggers SweetAlert2 modal:
      *"Partial Receipt Detected: You are receiving less than the ordered quantity. Would you like to create a Backorder for the remaining units, or Short-Close the order?"*
      - Option A: **Create Backorder** (`backorder_policy: 'CREATE_BACKORDER'`).
      - Option B: **Short-Close Order** (`backorder_policy: 'SHORT_CLOSE'`).
    - Wraps API submission with full pessimistic UI locking (disables button, shows loading state).

---

### Task 5: Backend Atomic Receiving & Backorder Orchestration
**Files to Modify:**
- `app/Http/Controllers/InventoryController.php`

- [ ] **Step 5.1: Validate Input Payload**
  - Validate `po_number`, `warehouse_id`, `movement_type_id`, `delivery_slip`, `carrier`, `backorder_policy`.
  - Validate `items.*.sku`, `items.*.received_qty`, `items.*.storage_location`, `items.*.lot_number`, `items.*.expiry_date`.
  - Note: Remove financial validation requirements (`unit_cost`, `amount_paid`, etc.) from floor receiving.
- [ ] **Step 5.2: Atomic PO Fulfillment & Backorder Status Transition**
  - Lock PO and PO Items with `lockForUpdate()`.
  - Update each PO line item's `received_quantity += $receivedQty`.
  - If `backorder_policy === 'SHORT_CLOSE'`, set unfulfilled remainder items to `SHORT_CLOSED`.
  - Evaluate overall PO status:
    - If all items fulfilled: set PO status to `Fully Received`.
    - If partial and backorder created: set PO status to `Partially Received`.
    - If partial and short-closed: set PO status to `Short-Closed / Completed`.
- [ ] **Step 5.3: Master Ledger (`SYS_LEDGER`) & Balance Updates**
  - For each received line:
    - Lock `InventoryItem` with `lockForUpdate()`.
    - Record immutable entry in `StockLedger`:
      - `transaction_uuid`: `(string) Str::uuid()`
      - `transaction_type`: `'PURCHASE_RECEIPT'`
      - `before_quantity`: `$item->current_stock`
      - `quantity_change`: `+$baseQty`
      - `after_quantity`: `$item->current_stock + $baseQty`
      - `storage_location`: `$validated['storage_location']`
      - `batch_lot_no`, `expiry_date`
      - `performed_by`: `auth()->user()->name`
    - Update `InventoryItem::current_stock += $baseQty`.
- [ ] **Step 5.4: GoodsReceipt Header & Lines Creation**
  - Store `GoodsReceipt` with `grn_number`, `warehouse_id`, `movement_type_id`, `status: 'COMPLETED'`.

---

### Task 6: Verification & Quality Assurance Gate
- [ ] **Step 6.1: Syntax & Compilation**
  - Run `php -l app/Http/Controllers/InventoryController.php`.
  - Run `php -l app/Http/Controllers/PurchaseController.php`.
- [ ] **Step 6.2: Blade & Tinker Verification**
  - Run `php artisan tinker` tests to render `inventory.stock-in`, `purchase.purchase-orders`, and `purchase.request-quotations`.
- [ ] **Step 6.3: Production Asset Build**
  - Run `npm run build` to verify Vite asset bundling.
- [ ] **Step 6.4: Defect Vector Testing**
  - Verify over-receipt prevention.
  - Verify zero-cost display across the entire Stock In screen.
  - Verify warehouse change automatically updates movement type default and storage bin options.
  - Verify Backorder vs Short-Close workflow updates PO status accurately.

---

## Plan Review Focus
1. **Financial Leaks:** Ensure zero price, cost, currency (`₱`), or payment fields appear anywhere on the receiving floor screen.
2. **Backorder State Integrity:** Verify that short-closing an order prevents false pending demand, while creating a backorder keeps the PO open in the queue with the exact remaining quantity.
3. **Warehouse Defaulting:** Ensure selecting a warehouse in PO automatically propagates to Stock In, and changing warehouse in Stock In immediately updates the Movement Type and bin destinations.
4. **Race Conditions:** Verify that pessimistic locks (`lockForUpdate()`) prevent two floor operators from scanning and over-fulfilling the same PO item simultaneously.
5. **Ledger Immutability:** Verify that all receipts write strictly to `inventory_ledger` with verifiable before and after stock levels.


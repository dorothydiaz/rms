# Translator Prompt Specification: Purchase Order Redesign (RFQ Design & Lifecycle Continuation)

## 1. Core Intent
Revise the Purchase Order module (`purchase-orders.blade.php`, `purchase-orders.js`, `purchase-orders.css`, `PurchaseController.php`) to match the redesigned Request for Quotations (RFQ) UI/UX architecture and continue the procurement lifecycle logic seamlessly from RFQ Stage 4 (`Approved / Awarded`) into the Purchase Order 4-Stage Pipeline (`Draft` -> `Approval` -> `Issued` -> `Status / Fulfilled`) and downstream Stock-In (`GoodsReceipt`) & Financial Settlement.

## 2. Explicit Constraints
1. **Tracker (Tab 1 - PO Directory):**
   - Flat HR-style page header and 2-tab navigation bar (`Purchase Order Tracker` + `Purchase Order Builder`).
   - 5 KPI summary cards (`Total Purchase Orders`, `Draft & Pending Approval`, `Issued / In-Transit`, `Partially / Fully Received`, `Unpaid / Balance Due`).
   - Multi-selection checkbox toolbar (`#poBatchActionBar`) supporting **Batch Delete (Drafts)**, **Batch Archive**, and **Batch Export CSV**.
   - 19-Column Stacked Top/Bottom Row Matrix (`PO_DIRECTORY_COLUMNS`) with draggable column reordering, resizable widths, header filter popovers, and a floating Gear Column Settings menu (`#poTableSettingsDropdown`).
2. **Builder (Tab 2 - PO Builder):**
   - **Zone 1 (Lifecycle Bar):** Left-side dynamic Stage Action Buttons (`#poStageActionsGroup`) + Right-side 4-Chevron Pipeline Progress Bar (`#poLifecycleProgressBar`: `Draft` -> `Approval` -> `Issued` -> `Status`) + Stage Subtitle & Context Ribbon (`#poStageBanner`).
   - **Zone 2 (100% Width Top Identity & Operational Banner Card `#poTopBannerCard`):**
     - Identity & Status Ribbon Strip: Monospace `PO-YYYY-XXXX` display, Copy button, interactive Linked RFQ pill (`#poLinkedRfqPill`), PO Lifecycle Status badge (`#poStatusBadge`), Payment Status badge (`#poPayStatusHeaderBadge`), and Direct Link to Vendor Masterlist (`#poVendorDirectLink`).
     - 2-Row Operational Parameters Grid: Searchable Vendor Combobox (`#poVendorCombobox`) + `>` Vendor Masterlist redirect button (`#poVendorRedirectBtn`), Order Purpose / Title (`#poOrderTitle`), Ship-To Warehouse / Destination (`#poDeliveryLocation`), Payment Terms (`#poPaymentTerms`), Order Date (`#poOrderDate`), and Expected Delivery Date (`#poExpectedDelivery`).
   - **Zone 3 (70% Left / 30% Right Split Body `#poBuilderSplitBody`):**
     - **Left 70% (`#poItemsPanelCol`):** Stage-Progressive Line Items Table (`PO_ALL_COLUMNS` across *Base Order*, *RFQ Benchmark & Variance*, and *Receiving & Fulfillment* categories) with floating Gear Column Customizer (`#btnPoBuilderSettings` / `#poBuilderSettingsDropdown`), Custom UOM Dropdown Component (`window.UomRegistry` / `<x-uom-dropdown>`), Item Master Quick Picker (`F2`), Add Custom Row, Clear All, and Footer Summary Metrics (`Lines`, `Total Units`, `Gross Order Total`).
     - **Right 30% (`#poMockupPanelCol`):** Sticky Live Purchase Order Mockup (`#poLiveMockupSheet`) with 3-button single-row header (`Audit Trail`, `Preview [F8]`, `Send to Vendor [F10]`), Page Counter badge, Live Sync indicator, and real-time formal PO document rendering.
3. **End-to-End Continuation Logic (RFQ -> PO -> GRN):**
   - Clicking **"Create PO"** on an Approved/Awarded RFQ converts/imports the RFQ's vendor, terms, awarded quantities (`quoted_quantity` -> `quantity`), and awarded prices (`quoted_unit_price` -> `unit_price`), and deep-links/opens the newly created PO directly in the PO Builder (`?po=PO-YYYY-XXXX`).

## 3. Excluded Scope (Implicit Non-Goals)
- Modifying Inventory Stock-In (`GoodsReceipt`) database tables or breaking existing GRN creation APIs.
- Using unstyled native `<select>` tags for UOM selection (strictly prohibited per Rule 6).

## 4. Target Tech Stack
- **Backend:** Laravel 11 / PHP 8.2 (`PurchaseController`, `PurchaseOrder`, `PurchaseOrderItem`, `RfqHeader`, `RfqLine`).
- **Frontend:** Blade Templates, Vanilla ES6+ JavaScript (`purchase-orders.js`, `request-quotations.js`, `UomRegistry`), Scoped CSS (`purchase-orders.css` with OKLCH/HSL tokens and dark-mode support).
- **Testing:** PHPUnit Feature Tests (`tests/Feature/PurchaseOrderLifecycleTest.php`).

# RFQ to Purchase Order Lifecycle Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Redesign, revise, and solidify the Request for Quotations (RFQ) to Purchase Order (PO) procurement pipeline with a 4-stage visual Progress Bar (`Draft` > `Submitted` > `Approval` > `Status`), customisable table headers via a gear-icon checklist dropdown, dynamic stage-based cell locking, live variance & budget calculation, and atomic PO generation.

**Architecture:** A robust Finite State Machine (FSM) implemented in Laravel and JavaScript that enforces immutable state transitions (`DRAFT` ➔ `SUBMITTED` ➔ `APPROVAL` ➔ `APPROVED` / `REFUSED` / `REVISED`), paired with a dynamic table rendering engine in `request-quotations.blade.php`. Cell editability is strictly dictated by the active stage (Draft edits base items, Submitted locks all, Approval unlocks vendor quote inputs for approvers, Status locks all and enables PO creation). Table column visibility is configurable through a gear-icon floating popover with LocalStorage persistence.

**Tech Stack:** Laravel 11 (PHP 8.2+), Eloquent ORM, Blade, Vanilla CSS with Glassmorphic Industrial Tokens, Modern ES6+ JavaScript, FontAwesome/Phosphor Icons (`ph ph-gear`, `ph ph-check-circle`, etc.), and `<x-uom-dropdown>` / `UomRegistry`.

**Spec:** User prompt requirements, `Request Notes/RFQ Overview Status.txt`, `.agents/rules/uom_dropdown_standard.md`, and Architectural Blueprint PILLAR 1-4.

---

## Global Constraints

- **Single Master Payload & Hydration:** Single state tree managed in `window.AppStore` with bi-directional synchronization to SQLite/MySQL database and LocalStorage draft recovery.
- **Custom UOM Standard:** Unit of Measure dropdowns must strictly adhere to `.agents/rules/uom_dropdown_standard.md` (`UomRegistry` / `<x-uom-dropdown>`). No unstyled native selects.
- **Stage Lock Immutability:** Field inputs and action triggers must be disabled at the DOM level and validated on the backend. When in `SUBMITTED` or `APPROVED`/`REFUSED`, zero edits are permitted.
- **Zero Division Errors:** All variance percentage formulas `((quoted - target) / target) * 100` must gracefully guard against `target == 0` or empty values.
- **Audit Trail Compliance:** Every stage change, quotation edit, approval decision, revision feedback, and PO conversion must log a structured event to `procurement_audit_trail`.

---

## Review Focus

1. **Division-by-Zero in Variance Calculations:** When `target_unit_price` is 0.00, calculation must return `+100%` or `"N/A"` without crashing or producing `Infinity%` / `NaN%`.
2. **Unauthorized State Mutation in Submitted/Approved State:** Bypassing front-end disabled attributes via browser DevTools console must be rejected by backend controller validation.
3. **Approver Partial Quantity & Minimum Order Handling:** `quoted_quantity` can differ from `requested_quantity` (e.g., vendor MOQ 120 vs requested 100); auto-calculated line totals must use `quoted_quantity * quoted_unit_price` without corrupting original `requested_quantity`.
4. **Revision Round-Trip Integrity:** When Approver selects "Revision" (returns to Draft), all previous vendor inputs must be preserved as historical reference while re-enabling buyer base request editing.
5. **Atomic Purchase Order Generation:** PO generation must run inside a `DB::transaction()` to prevent partial inserts or duplicate PO numbers under concurrent clicks.

---

## Adversarial Engineering Collective (AEC) Review Summary

Prior to task execution, the AEC 8-persona council conducted an exhaustive adversarial review of this specification:

### Persona 0: [The Librarian] (Context Cartography & State Routing)
- **Resolved Symbols:**
  - `resources/views/purchase/request-quotations.blade.php`: Line 2488 (`pane-tab-builder`), Line 3276 (`ALL_COLUMNS`), Line 4071 (`renderTableHeaderAndColumns`), Line 4287 (`renderItemsTable`), Line 7012 (`confirmApprovalAndGeneratePO`).
  - `database/migrations/2026_10_11_000002_create_revised_enterprise_supporting_tables.php`: Line 249 (`rfq_header`), Line 280 (`rfq_lines`).
  - `app/Http/Controllers/PurchaseController.php`: Line 26 (`sendQuotationEmail`), Line 116 (`apiCreatePurchaseOrder`).
- **Memory Check:** Recalled project rule `.agents/rules/uom_dropdown_standard.md` mandating `UomRegistry` for all item table UOM selectors.

### Persona 1: [Senior BA] (Boundary Invariant & State Machine)
- **FSM Transitions:**
  - `DRAFT` ➔ `SUBMITTED` (Buyer submits tender for quotations; triggers lock).
  - `SUBMITTED` ➔ `APPROVAL` (Vendor bid received; approver enters vendor quotation).
  - `APPROVAL` ➔ `APPROVED` (Approver accepts terms; unlocks PO conversion).
  - `APPROVAL` ➔ `REFUSED` (Approver rejects terms; RFQ locked as Refused).
  - `APPROVAL` ➔ `DRAFT` (Approver requests revision; returns to Draft with feedback notes).
  - `APPROVED` ➔ `CONVERTED_TO_PO` (PO generated; permanent reference linked).
- **Data Invariant Matrix:**
  | Field | Type | Min | Max | Editable In |
  |---|---|---|---|---|
  | `item_id` | String/UUID | Required | - | Draft |
  | `item_name` | String | 1 char | 255 chars | Draft |
  | `purchase_uom_code` | String | In Catalog | 20 chars | Draft |
  | `requested_quantity` | Decimal | 0.01 | 999,999.99 | Draft |
  | `target_unit_price` | Decimal | 0.00 | 99,999,999.99 | Draft |
  | `target_line_total` | Decimal | 0.00 | Auto-calculated | Read-Only |
  | `rfq_line_notes` | Text | Nullable | 1,000 chars | Draft |
  | `quoted_quantity` | Decimal | 0.01 | 999,999.99 | Approval |
  | `quoted_unit_price` | Decimal | 0.00 | 99,999,999.99 | Approval |
  | `quoted_line_total` | Decimal | 0.00 | Auto-calculated | Read-Only |
  | `lead_time_days` | Integer | 0 | 365 | Approval |
  | `quantity_variance` | Decimal | Auto | `quoted_qty - req_qty` | Read-Only |
  | `price_variance_amount` | Decimal | Auto | `quoted_price - target_price` | Read-Only |
  | `price_variance_pct` | Decimal | Auto | `(diff / target) * 100` | Read-Only |
  | `is_price_within_budget`| Boolean | Auto | `quoted <= target` (tolerance) | Read-Only |

### Persona 2: [Backend Dev 1] (Logic Master & FSM Core)
- Enforces strict transition validation: An RFQ in `SUBMITTED` state cannot directly transition to `APPROVED` without passing through `APPROVAL` quotation validation.
- Prevents mutation of base buyer specifications once in `SUBMITTED` or `APPROVAL` states.

### Persona 3: [Backend Dev 2] (Pragmatic Builder)
- Single-database ACID transactions via `DB::transaction()`.
- Idempotency guard: Prevent double-issuing Purchase Orders for the same RFQ reference.
- Clean JSON API endpoints in `PurchaseController`.

### Persona 4: [Frontend Dev 1] (Visual Craft)
- Modern glassmorphic Progress Bar with 4 distinct color signatures:
  - `Draft`: Electric Violet (`#9333ea`)
  - `Submitted`: High-Visibility Royal Blue (`#2563eb`)
  - `Approval`: Warm Amber / Honey (`#d97706`)
  - `Status`: Emerald Green (`#059669`) if Approved, Crimson Red (`#dc2626`) if Refused.
- Pulse dot micro-animation on the current active stage.

### Persona 5: [Frontend Dev 2] (Ergonomic Strategist & Accessibility)
- Table header gear icon dropdown matches the proven pattern in RFQ Directory:
  - Anchored on header, searchable, categorized by column group (Base, Vendor Response, Comparison).
  - Quick action buttons: "Default View", "Show All", "Reset Widths".
  - Persistent via LocalStorage (`rms_rfq_builder_cols_v4`).
- Disabled input states use high-contrast read-only styling with explicit `readonly` / `disabled` attributes and `aria-disabled="true"`.
- Tabular figures (`font-variant-numeric: tabular-nums`) for currency and variance comparisons.

### Persona 6: [Lead QA] (Chaos Engineer)
- Identified 4 break points:
  1. Empty `target_unit_price` leading to division by zero: Handled via `(target > 0 ? ((quoted - target) / target) * 100 : 0)`.
  2. Submitting empty line items: Form validation must require `>= 1` item.
  3. Negative vendor quote entries: Inputs must enforce `min="0"`.
  4. Approver rejecting RFQ without reason: Reason modal required on Refusal.

### Persona 7: [Auditor] (Systems, Security & Audit Trail)
- Verification that user actions in RFQ Builder match server session and record unalterable audit events with actor name, timestamp, and delta payload.
- Final sign-off: Plan approved for phased TDD implementation.

---

## Task Decomposition

### Task 1: Database Migration & Eloquent Models for RFQ
**Files:**
- Create: `database/migrations/2026_10_11_000004_enhance_rfq_tables_with_lifecycle_fields.php`
- Create: `app/Models/Purchase/RfqHeader.php`
- Create: `app/Models/Purchase/RfqLine.php`
- Test: `tests/Feature/RfqModelDatabaseTest.php`

- [ ] **Step 1.1:** Write the failing test in `tests/Feature/RfqModelDatabaseTest.php` verifying `RfqHeader` and `RfqLine` models exist, relationships connect (`$rfq->lines`), and `quoted_quantity`, `rejection_reason`, and `rfq_status` columns exist.
- [ ] **Step 1.2:** Run `php artisan test --filter=RfqModelDatabaseTest` to confirm it fails.
- [ ] **Step 1.3:** Create migration `2026_10_11_000004_enhance_rfq_tables_with_lifecycle_fields.php` adding `quoted_quantity` (decimal 18,4 nullable), `rejection_reason` (text nullable), and `revision_notes` (text nullable) to `rfq_lines` / `rfq_header` if missing.
- [ ] **Step 1.4:** Create `app/Models/Purchase/RfqHeader.php` and `app/Models/Purchase/RfqLine.php` with proper fillables, UUID handling, and relationships.
- [ ] **Step 1.5:** Run migration `php artisan migrate` and re-run test `php artisan test --filter=RfqModelDatabaseTest` until green.
- [ ] **Step 1.6:** Commit migration and models.

---

### Task 2: Backend FSM Controller Endpoints & Validation Rules
**Files:**
- Modify: `app/Http/Controllers/PurchaseController.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/RfqLifecycleApiTest.php`

- [ ] **Step 2.1:** Write failing feature test in `tests/Feature/RfqLifecycleApiTest.php`:
  - `POST /purchase/api/rfq/save-draft`: saves draft items.
  - `POST /purchase/api/rfq/transition-status`: enforces valid FSM transitions (`DRAFT` ➔ `SUBMITTED` ➔ `APPROVAL` ➔ `APPROVED` / `REFUSED` / `REVISED`).
  - Rejects edits to line items when RFQ is in `SUBMITTED` or `APPROVED` status with HTTP 422.
  - `POST /purchase/api/rfq/convert-po`: transactionally generates a Purchase Order from an Approved RFQ.
- [ ] **Step 2.2:** Run `php artisan test --filter=RfqLifecycleApiTest` to confirm test failure.
- [ ] **Step 2.3:** Add routes in `routes/web.php` under the `purchase.` prefix.
- [ ] **Step 2.4:** Implement `apiSaveRfqDraft`, `apiTransitionRfqStatus`, and `apiConvertRfqToPo` in `app/Http/Controllers/PurchaseController.php` with strict validation and `DB::transaction()`.
- [ ] **Step 2.5:** Run `php artisan test --filter=RfqLifecycleApiTest` until all tests pass.
- [ ] **Step 2.6:** Commit backend API endpoints.

---

### Task 3: RFQ Builder 4-Stage Progress Bar Component (Draft > Submitted > Approval > Status)
**Files:**
- Modify: `resources/views/purchase/request-quotations.blade.php` (CSS styles & HTML in `pane-tab-builder`)
- Test: `tests/Feature/RfqBuilderProgressBarTest.php`

- [ ] **Step 3.1:** Write failing test in `tests/Feature/RfqBuilderProgressBarTest.php` asserting that the RFQ Builder tab renders the progress bar container (`#rfqLifecycleProgressBar`), the four distinct steps (`step-draft`, `step-submitted`, `step-approval`, `step-status`), step status badges, and stage instruction banners.
- [ ] **Step 3.2:** Run test to confirm failure.
- [ ] **Step 3.3:** Add CSS tokens and styling for `.rfq-stepper-container`, `.rfq-step-item`, `.rfq-step-track`, `.rfq-step-indicator`, `.rfq-step-label`, and the four color-coded states (`is-active.stage-draft`, `is-active.stage-submitted`, `is-active.stage-approval`, `is-active.stage-approved`, `is-active.stage-refused`).
- [ ] **Step 3.4:** Add the HTML progress bar component inside `pane-tab-builder` directly above the Tender Reference card.
- [ ] **Step 3.5:** Implement JavaScript helper `updateLifecycleProgressBar(status)` that dynamically sets active classes, step icons, colors, and contextual instruction banner.
- [ ] **Step 3.6:** Run `php artisan test --filter=RfqBuilderProgressBarTest` until green.
- [ ] **Step 3.7:** Commit progress bar implementation.

---

### Task 4: Customizable Headers System with Gear Icon Checkbox Dropdown
**Files:**
- Modify: `resources/views/purchase/request-quotations.blade.php` (JS column definition matrix & HTML header gear popover)
- Test: `tests/Feature/RfqBuilderCustomHeadersTest.php`

- [ ] **Step 4.1:** Write failing test in `tests/Feature/RfqBuilderCustomHeadersTest.php` verifying the table header defines:
  - Base Item Request Fields: `item_id`, `item_name`, `purchase_uom_code`, `requested_quantity`, `target_unit_price`, `target_line_total`, `rfq_line_notes`.
  - Vendor Response Inputs: `quoted_quantity`, `quoted_unit_price`, `quoted_line_total`, `lead_time_days`.
  - Validation & Comparison Fields: `quantity_variance`, `price_variance_amount`, `price_variance_percentage`, `is_price_within_budget`.
  - Gear button `#btnRfqBuilderSettings` with popover `#rfqBuilderSettingsDropdown`.
- [ ] **Step 4.2:** Run test to confirm failure.
- [ ] **Step 4.3:** Redefine `ALL_COLUMNS` matrix in `request-quotations.blade.php` with the full 14 fields grouped into `base`, `vendor`, and `comparison` categories with labels, default visibility, alignment, and widths.
- [ ] **Step 4.4:** Build the Gear Icon dropdown menu HTML and styling matching the RFQ Directory pattern (search bar, group chips, select all, default view, reset widths, checkbox rows).
- [ ] **Step 4.5:** Implement JavaScript functions: `renderBuilderTableHeader()`, `toggleRfqBuilderSettingsDropdown()`, `toggleBuilderColumnVisibility(colId, isChecked)`, `resetBuilderColumnDefaults()`, `showAllBuilderColumns()`, with persistence in `localStorage.getItem('rms_rfq_builder_cols_v4')`.
- [ ] **Step 4.6:** Run test until green.
- [ ] **Step 4.7:** Commit customizable header feature.

---

### Task 5: Dynamic Stage-Based Cell Locking & Validation Logic
**Files:**
- Modify: `resources/views/purchase/request-quotations.blade.php` (`renderItemsTable`, input handlers)
- Test: `tests/Feature/RfqBuilderCellLockingTest.php`

- [ ] **Step 5.1:** Write failing test in `tests/Feature/RfqBuilderCellLockingTest.php` asserting that when status is:
  - `Draft`: Base fields are editable; vendor and comparison fields are disabled or locked.
  - `Submitted`: All inputs in table and header have `disabled` or `readonly` attributes; action buttons (Add Row, Delete) are disabled.
  - `Approval`: Base fields are `readonly`; vendor response inputs (`quoted_quantity`, `quoted_unit_price`, `lead_time_days`) are editable; comparison fields display auto-calculated variance badges.
  - `Approved` / `Refused`: All inputs are locked; "Generate PO" button is visible for Approved.
- [ ] **Step 5.2:** Run test to confirm failure.
- [ ] **Step 5.3:** Refactor `renderItemsTable()` in JavaScript to inspect `currentRfqStage = window.AppStore.activeRfq.rfqStatus`:
  - Enforce `disabled` / `readonly` dynamically on each table cell based on current stage.
  - Calculate `quantity_variance` = `quoted_qty - req_qty` with color formatting (+ green or - amber).
  - Calculate `price_variance_amount` = `quoted_price - target_price`.
  - Calculate `price_variance_percentage` safely with `target > 0` guard.
  - Render `is_price_within_budget` pill (Within Budget / Over Budget).
  - Standardize UOM dropdown using `UomRegistry` / `<x-uom-dropdown>` per rule.
- [ ] **Step 5.4:** Implement Stage Action controls:
  - In Draft: "Submit RFQ to Vendor".
  - In Submitted: "Move to Approval (Input Vendor Bid)".
  - In Approval: "Approve Quotation", "Refuse Quotation" (with reason prompt), "Return for Revision" (returns to Draft).
  - In Approved: "Generate Purchase Order".
- [ ] **Step 5.5:** Run test until green.
- [ ] **Step 5.6:** Commit stage-based cell locking engine.

---

### Task 6: Purchase Order Generation Integration & Verification
**Files:**
- Modify: `resources/views/purchase/request-quotations.blade.php`
- Modify: `app/Http/Controllers/PurchaseController.php`
- Test: `tests/Feature/RfqToPoIntegrationTest.php`

- [ ] **Step 6.1:** Write failing integration test verifying that an approved RFQ converts into a formal `PurchaseOrder` in database table `purchase_orders` / `purchase_order_header`, mapping quoted quantities, prices, vendor, payment terms, and logging to audit trail.
- [ ] **Step 6.2:** Run test to confirm failure.
- [ ] **Step 6.3:** Wire the "Generate Purchase Order" modal in `request-quotations.blade.php` to call `POST /purchase/api/rfq/convert-po`.
- [ ] **Step 6.4:** Ensure generated PO number is linked to RFQ record in `po_reference` and transitions RFQ status to `Awarded` / `Converted to PO`.
- [ ] **Step 6.5:** Run full test suite: `php artisan test --filter=Rfq` to confirm all unit and integration tests pass.
- [ ] **Step 6.6:** Commit PO generation and integration changes.

---

### Task 7: Final Regression Audit & Visual Polish
**Files:**
- Modify: `resources/views/purchase/request-quotations.blade.php`
- Test: Run all procurement test suites (`tests/Feature/Rfq*`)

- [ ] **Step 7.1:** Execute full regression tests: `php artisan test`.
- [ ] **Step 7.2:** Verify keyboard shortcuts (F2 for Master Select, F8 for Document Preview, F10 for Vendor Send).
- [ ] **Step 7.3:** Review visual hierarchy: Tabular figure alignment, glassmorphic card borders, mobile horizontal table container scrolling.
- [ ] **Step 7.4:** Final commit and verification sign-off.

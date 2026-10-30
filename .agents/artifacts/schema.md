# Contract & Schema Specification: Purchase Order Redesign

## 1. Database & Eloquent Models
- **Header Table:** `purchase_order_header` (`App\Models\Purchase\PurchaseOrder`)
  - Primary Key: `id`
  - Unique Business Key: `po_number` (`PO-YYYY-XXXX`)
  - Fields: `po_type`, `rfq_reference`, `vendor_id`, `vendor_name`, `vendor_trade_name`, `vendor_contact_person`, `vendor_phone`, `vendor_email`, `vendor_address`, `order_date`, `expected_delivery`, `delivery_location`, `special_notes`, `payment_status`, `payment_method`, `amount_paid`, `balance_due`, `payment_reference`, `payment_date`, `fund_source`, `payment_remarks`, `approval_notes`, `approved_by`, `status`, `subtotal`, `tax_amount`, `gross_total`, `created_by_user`.
- **Lines Table:** `purchase_order_lines` (`App\Models\Purchase\PurchaseOrderItem`)
  - Foreign Key: `purchase_order_id`
  - Fields: `sku`, `item_name`, `specs`, `category`, `uom`, `quantity`, `unit_price`, `total_amount`, `received_quantity`, `status`.

## 2. Backend JSON API Endpoints (`PurchaseController`)
1. `GET /purchase/api/orders` (`purchase.api.orders`)
   - Returns `{ success: true, data: { purchaseOrders: PurchaseOrder[], vendors: ProcurementVendor[] } }`.
2. `POST /purchase/api/orders` (`purchase.api.orders.create`)
   - Idempotently creates or updates a `PurchaseOrder` (`updateOrCreate` by `po_number`) and syncs `PurchaseOrderItem` lines inside a `DB::transaction`.
3. `POST /purchase/api/orders/save-draft` (`purchase.api.orders.save-draft`)
   - Saves or updates a PO in `Draft` (`DRAFT` / `Draft PO`) state and returns `{ success: true, data: PurchaseOrder }`.
4. `POST /purchase/api/orders/transition-status` (`purchase.api.orders.transition-status`)
   - Validates and transitions a PO across lifecycle stages:
     - `Draft` (`DRAFT` / `Draft PO`)
     - `Approval` (`PENDING_APPROVAL` / `Pending Approval`)
     - `Issued` (`ISSUED` / `Approved / Issued` / `Partially Received`)
     - `Approved` / `Fulfilled` (`FULLY_RECEIVED` / `Fully Received` / `Closed`)
     - `Refused` / `Cancelled` (`CANCELLED` / `Refused` / `Cancelled`)
5. `POST /purchase/api/orders/batch-delete` (`purchase.api.orders.batch-delete`)
   - Deletes selected Draft POs (`po_numbers: string[]`) and rejects deletion of Issued/Received POs.
6. `POST /purchase/api/orders/batch-archive` (`purchase.api.orders.batch-archive`)
   - Archives selected POs (`po_numbers: string[]`).
7. `POST /purchase/api/rfq/convert-po` (`purchase.api.rfq.convert-po`)
   - Atomically converts an `APPROVED` RFQ into a `PurchaseOrder` (copying vendor metadata, `quoted_quantity` -> `quantity`, `quoted_unit_price` -> `unit_price`, `purchase_uom_code` -> `uom`), marks the RFQ as `AWARDED`, links `purchase_order_id`, and returns `po_number` + `redirect_url` (`/purchase/purchase-orders?po=PO-YYYY-XXXX`).

## 3. Frontend State & Column Contracts (`purchase-orders.js`)
- **`PO_ALL_COLUMNS` (Builder Line Items Table - 16 Columns across 3 Categories):**
  1. *Base Order:* `index`, `sku`, `item_name`, `uom`, `quantity`, `unit_price`, `line_total`, `specs`
  2. *RFQ Benchmark & Variance:* `rfq_quoted_qty`, `rfq_target_price`, `price_variance_amount`, `price_variance_pct`, `is_within_budget`
  3. *Receiving & Fulfillment:* `received_quantity`, `remaining_quantity`, `fulfillment_status`
- **Stage Default Columns:**
  - `PO_DRAFT_STAGE_DEFAULT_COLUMNS`: `['index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total']`
  - `PO_APPROVAL_STAGE_DEFAULT_COLUMNS`: `['index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total', 'rfq_target_price', 'price_variance_pct', 'is_within_budget']`
  - `PO_ISSUED_STAGE_DEFAULT_COLUMNS`: `['index', 'sku', 'item_name', 'uom', 'quantity', 'unit_price', 'line_total', 'received_quantity', 'remaining_quantity', 'fulfillment_status']`
- **`PO_DIRECTORY_COLUMNS` (Tracker Directory Table - 19 Stacked Columns):**
  - `po_info`, `source_rfq`, `vendor_contact`, `branch_warehouse`, `delivery_schedule`, `order_valuation`, `payment_terms`, `fulfillment_progress`, `po_status`, `company`, `currency_incoterms`, `amount_paid`, `payment_reference`, `items_summary`, `po_notes`, `approval_details`, `created_by`, `last_modified_by` (+ sticky `actions` column).

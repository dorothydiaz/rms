# Enterprise ERP Schema & Naming Standard (Software_Logics_Revised)

## Core Architectural Invariants
1. **Header / Line Naming Convention**:
   - Master/detail transactional entities must use the `*_header` and `*_lines` pattern (e.g., `bom_header`/`bom_lines`, `stock_in_header`/`stock_in_lines`, `stock_out_header`/`stock_out_lines`, `purchase_order_header`/`purchase_order_lines`, `vendor_bill_header`/`vendor_bill_lines`).

2. **Entity-Prefixed Audit Columns**:
   - To avoid column collisions across SQL joins, audit columns and common status attributes must be prefixed with the entity name:
     - `<entity>_is_active` (e.g. `category_is_active`, `bom_is_active`)
     - `<entity>_created_at`, `<entity>_created_by`
     - `<entity>_updated_at`, `<entity>_updated_by`

3. **Master Ledger System (`inventory_ledger`)**:
   - Stock movements must post immutable, append-only records to `inventory_ledger`.
   - Real-time stock balances are stored in `stock_balances` and broken down by `lot_id` and `location_id`.

4. **Universal Approval Matrix**:
   - Document workflows (`VENDOR`, `RFQ`, `RFQ_AWARD`, `PURCHASE_ORDER`, `STOCK_IN`, `VENDOR_BILL`, `STOCK_OUT`, `STOCK_TRANSFER`, `WORK_ORDER`, `STOCK_ADJUSTMENT`, `STOCK_WASTE`) are governed by `approval_workflows`, `approval_workflow_steps`, `approval_requests`, and `approval_actions`.
   - Postings to `inventory_ledger` are prohibited unless the document approval status is `NOT_REQUIRED` or `APPROVED`.

# Product Requirements Document (PRD): Purchase Order Redesign & RFQ Continuation

## 1. Executive Summary
Revise the Purchase Order module to match the enterprise-grade Request for Quotations (RFQ) workspace design and continue the procurement lifecycle from RFQ Stage 4 (`Approved / Awarded`) into the 4-stage Purchase Order lifecycle (`Draft` -> `Approval` -> `Issued` -> `Status / Fulfilled`) and downstream Stock-In (`GoodsReceipt`) & Financial Settlement.

## 2. Gherkin Acceptance Criteria

```gherkin
Feature: Purchase Order Redesign & RFQ Lifecycle Continuation

  Scenario: Render Redesigned PO Tracker & Builder Workspace
    Given an authenticated procurement user navigates to "/purchase/purchase-orders"
    When the page loads
    Then the PO Tracker (Tab 1) displays 5 KPI summary cards, a multi-selection batch action toolbar, and a stacked 19-column customizable directory table
    And the PO Builder (Tab 2) displays the 4-stage Chevron Lifecycle Stepper ("Draft", "Approval", "Issued", "Status"), the 100%-width Top Operational Banner Card with Vendor Combobox and ">" Masterlist redirect, and the 70% Line Items / 30% Sticky Live PO Mockup split body.

  Scenario: Save and Update Purchase Order Draft
    Given an authenticated user is in the PO Builder in "Draft" stage
    When the user saves a PO draft via "POST /purchase/api/orders/save-draft" with valid line items and UOM codes
    Then the system persists the PO in "purchase_order_header" with status "Draft PO" and its lines in "purchase_order_lines"
    And returns HTTP 200 with the saved PO data.

  Scenario: Transition Purchase Order Across 4-Stage Lifecycle FSM
    Given an existing Purchase Order in "Draft PO" status
    When the user transitions the PO to "Approval" ("Pending Approval"), then "Issued" ("Approved / Issued"), and then "Fulfilled" ("Fully Received") via "POST /purchase/api/orders/transition-status"
    Then each valid transition updates the PO status in "purchase_order_header" and returns HTTP 200
    And attempting an invalid jump (e.g., directly from "Draft PO" to "Fully Received") returns HTTP 422.

  Scenario: Continuation from Approved RFQ to Purchase Order Builder
    Given an RFQ in "APPROVED" status with quoted quantities and quoted unit prices
    When the user converts the RFQ to a PO via "POST /purchase/api/rfq/convert-po"
    Then a new Purchase Order is atomically created with "rfq_reference" linked to the RFQ number, carrying over vendor details, quoted quantities, quoted prices, and UOM codes
    And the response includes "redirect_url" pointing to "/purchase/purchase-orders?po={po_number}" so the PO opens directly in the PO Builder.

  Scenario: Batch Delete and Batch Archive Purchase Orders
    Given multiple Purchase Orders in the Tracker
    When the user executes "POST /purchase/api/orders/batch-delete" on Draft POs
    Then the Draft POs are removed from "purchase_order_header"
    And Issued or Received POs are protected from deletion.
```

## 3. Feature Slicing
- **MVP (Current Sprint):**
  - Full 19-column Stacked PO Tracker with 5 KPI cards, Batch Action Toolbar (Delete, Archive, Export CSV), Header Filter Popovers, and Draggable Column Gear Configurator.
  - Full 3-Zone PO Builder matching the RFQ Builder (Lifecycle Bar + 4-Chevron Stepper + Context Ribbon, 100% Top Identity & Operational Grid with Vendor Combobox + `>` redirect, 70% Stage-Progressive Line Items Table with `<x-uom-dropdown>` / `UomRegistry` + 30% Sticky Live PO Document Mockup).
  - Seamless RFQ Stage 4 ("Create PO") continuation and deep-linking (`?po=PO-YYYY-XXXX`), plus Backend API endpoints (`save-draft`, `transition-status`, `batch-delete`, `batch-archive`).
- **Phase 2 (Future Enhancement):**
  - Automated vendor portal self-service acknowledgment webhook.

const fs = require('fs');
const vm = require('vm');
const path = require('path');

const targetFile = path.resolve('resources/views/inventory/stock-in.blade.php');
console.log('Validating Stock In Blade View (Tabs Wrapper & RFQ Builder):', targetFile);

if (!fs.existsSync(targetFile)) {
    console.error('[FAIL] File does not exist:', targetFile);
    process.exit(1);
}

const raw = fs.readFileSync(targetFile, 'utf8');

// 1. Extract and validate JavaScript syntax
const scriptMatches = raw.match(/<script[\s\S]*?>([\s\S]*?)<\/script>/gi);
if (!scriptMatches || scriptMatches.length === 0) {
    console.error('[FAIL] No script tags found in file');
    process.exit(1);
}

scriptMatches.forEach((sTag, idx) => {
    const code = sTag.replace(/<script[\s\S]*?>/i, '').replace(/<\/script>/i, '');
    try {
        new vm.Script(code);
        console.log(`[PASS] Syntax OK for <script> block ${idx + 1}`);
    } catch (e) {
        console.error(`[FAIL] Syntax Error in <script> block ${idx + 1}:`, e.message);
        process.exit(1);
    }
});

// 2. Validate Required Tab Structures and Components
const requiredPatterns = [
    // Primary Tabs Wrapper
    ['Tabs-Wrapper Container', 'class="tabs-wrapper"'],
    ['Tab 1: List of the PO', 'id="tabBtnPoList"'],
    ['Tab 2: Pending PO', 'id="tabBtnPendingPo"'],
    ['Tab 3: Create new PO to received', 'id="tabBtnCreatePo"'],

    // Panes
    ['Pane 1: PO Master List', 'id="tab-po-list"'],
    ['Pane 2: Pending PO Backlogs', 'id="tab-pending-po"'],
    ['Pane 3: RFQ Builder Workspace', 'id="tab-create-po"'],

    // RFQ Builder Layout Reused Elements
    ['Asymmetric Workspace Grid', 'rfq-builder-workspace-grid'],
    ['Builder Left Column', 'rfq-builder-left-col'],
    ['Builder Right Column', 'rfq-builder-right-col'],
    ['Vendor Selection Pill Box', 'rfq-vendor-pill-box'],
    ['Direct Receiving Ref Display', 'directRecRefDisplay'],
    ['Product Catalog Quick Picker', 'directCatalogPicker'],
    ['Direct Items Table Body', 'directItemsTableBody'],
    ['Landed Cost Panel', 'directLandedCostPanel'],

    // Cross-Module Data Store Bindings
    ['Purchase Orders Connection', 'rms_purchase_orders'],
    ['Inventory Products Connection', 'rms_inventory_products'],
    ['Stocks Ledger Movement Connection', 'rms_stocks_ledger'],
    ['Vendors Master Connection', 'rms_vendors'],
    ['Payments Settlement Connection', 'rms_po_payments'],

    // Inbound Inspection Drawer & Tolerance
    ['Inbound Inspection Drawer', 'id="grnInspectionDrawer"'],
    ['Supervisor Override Modal', 'id="supervisorOverrideModal"']
];

let checkFailCount = 0;
requiredPatterns.forEach(([name, pattern]) => {
    if (raw.includes(pattern)) {
        console.log(`[PASS] ${name}`);
    } else {
        console.error(`[FAIL] Missing pattern for: ${name} (${pattern})`);
        checkFailCount++;
    }
});

if (checkFailCount === 0) {
    console.log('\n======================================================');
    console.log('>>> ALL TABS & BUILDER VALIDATION CHECKS PASSED (100%) <<<');
    console.log('======================================================');
    process.exit(0);
} else {
    console.error(`\n[ERROR] ${checkFailCount} checks failed.`);
    process.exit(1);
}

const fs = require('fs');
const vm = require('vm');
const path = require('path');

const targetFile = path.resolve('resources/views/inventory/stock-in.blade.php');
console.log('Validating Stock In Blade View:', targetFile);

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

let scriptPassCount = 0;
scriptMatches.forEach((sTag, idx) => {
    const code = sTag.replace(/<script[\s\S]*?>/i, '').replace(/<\/script>/i, '');
    try {
        new vm.Script(code);
        console.log(`[PASS] Syntax OK for <script> block ${idx + 1}`);
        scriptPassCount++;
    } catch (e) {
        console.error(`[FAIL] Syntax Error in <script> block ${idx + 1}:`, e.message);
        process.exit(1);
    }
});

// 2. Validate Key Functional Elements
const requiredPatterns = [
    // Theme & Layout
    ['Universal HR Accent Strip', 'grn-accent-strip'],
    ['Universal HR Gradient', 'linear-gradient(135deg, #ec4899 0%, #a855f7 100%)'],
    ['Canvas Background', '#faf7fd'],
    ['Phosphor Icons', 'ph-tray-arrow-down'],

    // Ingestion Logic
    ['PO-Linked & Ad-Hoc Switcher', 'setIngestionMode'],
    ['PO Selection Handler', 'onPoSelected'],
    ['Ad-Hoc Custom Inflow', 'onAdHocVendorSelected'],

    // Inspection & Quantity Triad
    ['Delivered vs Accepted vs Rejected Triad', 'onAcceptedQtyChanged'],
    ['Rejection Reason Codes', 'Damaged in Transit'],
    ['Over-Receiving Tolerance Engine', 'checkOverReceivingTolerance'],
    ['Supervisor Override PIN', 'verifySupervisorOverride'],
    ['Partial Receiving Short-Close', 'toggleShortCloseLine'],

    // Traceability & Storage
    ['Lot / Batch & Expiration', 'calculateShelfLifeIndicator'],
    ['UOM Conversion Multiplier', 'updateLinePurchasingUnit'],
    ['Two-Step Put-Away Staging', 'quickPutAwayItem'],
    ['Landed Cost Allocation', 'recalculateLandedCosts'],

    // Financial Clearing & Settlement
    ['Asset Valuation Journal Clearing', 'glDebitAmount'],
    ['Pre-Paid Advance Reconciliation', 'checkAdvancePaymentForPo'],
    ['Immediate Settlement / COD', 'IMMEDIATE_COD'],
    ['Vendor Bills Integration', 'rms_vendor_bills'],
    ['Payments Integration', 'rms_po_payments'],
    ['Stocks Ledger Integration', 'rms_stocks_ledger'],
    ['Print GRN Slip', 'openGrnPrintModal']
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
    console.log('\n===========================================');
    console.log('>>> ALL VALIDATION CHECKS PASSED (100%) <<<');
    console.log('===========================================');
    process.exit(0);
} else {
    console.error(`\n[ERROR] ${checkFailCount} checks failed.`);
    process.exit(1);
}

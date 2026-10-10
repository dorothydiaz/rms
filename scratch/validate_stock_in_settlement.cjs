const fs = require('fs');
const path = require('path');

const ROOT = 'c:\\Users\\JABIGUERO\\Documents\\GitHub\\rms';

let passed = 0;
let failed = 0;

function assertCheck(name, cond, details) {
    if (cond) {
        console.log(`[PASS] ${name}`);
        passed++;
    } else {
        console.error(`[FAIL] ${name} - ${details || ''}`);
        failed++;
    }
}

// Check stock-in.blade.php
const stockIn = fs.readFileSync(path.join(ROOT, 'resources/views/inventory/stock-in.blade.php'), 'utf8');

assertCheck('KPI Grid Removed from stock-in', !stockIn.includes('<div class="grn-kpi-grid">'), 'grn-kpi-grid still exists');
assertCheck('directPaymentAmount input present', stockIn.includes('id="directPaymentAmount"'));
assertCheck('fillDirectFullBalance button/func present', stockIn.includes('fillDirectFullBalance()'));
assertCheck('handleDirectPaymentAmountChange present', stockIn.includes('handleDirectPaymentAmountChange('));
assertCheck('directSettlementGrossDisplay present', stockIn.includes('id="directSettlementGrossDisplay"'));
assertCheck('directSettlementPaidDisplay present', stockIn.includes('id="directSettlementPaidDisplay"'));
assertCheck('directSettlementBalanceDisplay present', stockIn.includes('id="directSettlementBalanceDisplay"'));
assertCheck('directPaymentStatusBadge present', stockIn.includes('id="directPaymentStatusBadge"'));
assertCheck('updateHeaderKpis null-safe', stockIn.includes("const elTotal = document.getElementById('kpiTotalPos');"));
assertCheck('amount_paid sent in payload', stockIn.includes('amount_paid: amountPaidVal'));

// Check purchase-orders.blade.php
const po = fs.readFileSync(path.join(ROOT, 'resources/views/purchase/purchase-orders.blade.php'), 'utf8');
assertCheck('poAmountPaid input present in PO builder', po.includes('id="poAmountPaid"'));
assertCheck('handlePoInlineAmountPaid present', po.includes('handlePoInlineAmountPaid('));
assertCheck('poPaymentStatus null-safe accessor', po.includes("document.getElementById('poPaymentStatus')?.value"));
assertCheck('poSummaryPayBadge synced with inline amount', po.includes("const inlinePaid = document.getElementById('poAmountPaid')"));

console.log(`\nValidation complete: ${passed} passed, ${failed} failed.`);
if (failed > 0) process.exit(1);

const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');

function assert(condition, message) {
    if (!condition) {
        console.error(`[FAIL] ${message}`);
        process.exitCode = 1;
    } else {
        console.log(`[PASS] ${message}`);
    }
}

// 1. styles.css check
const stylesCss = fs.readFileSync(path.join(projectRoot, 'public/assets/css/styles.css'), 'utf8');
assert(stylesCss.includes('.content-area > .trf-workspace'), 'styles.css contains .content-area > .trf-workspace normalization');
assert(stylesCss.includes('.content-area > .stk-page-container'), 'styles.css contains .content-area > .stk-page-container normalization');

// 2. internal-transfer.blade.php
const trfBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/internal-transfer.blade.php'), 'utf8');
assert(trfBlade.includes('.trf-workspace {\n    gap: 8px !important;\n    padding: 0 !important;'), 'internal-transfer has zero padding override');
assert(!trfBlade.includes('style="margin-top: 16px;"'), 'internal-transfer has no margin-top: 16px card strip');

// 3. production.blade.php
const prdBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/production.blade.php'), 'utf8');
assert(prdBlade.includes('.prd-workspace {\n    gap: 8px !important;\n    padding: 0 !important;'), 'production has zero padding override');

// 4. stock-out.blade.php
const outBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/stock-out.blade.php'), 'utf8');
assert(outBlade.includes('.out-workspace {\n    gap: 8px !important;\n    padding: 0 !important;'), 'stock-out has zero padding override');
assert(!outBlade.includes('style="margin-top: 16px;"'), 'stock-out has no margin-top: 16px card strip');

// 5. waste-expiry.blade.php
const wstBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/waste-expiry.blade.php'), 'utf8');
assert(wstBlade.includes('.wst-workspace {\n    gap: 8px !important;\n    padding: 0 !important;'), 'waste-expiry has zero padding override');

// 6. stock-in.blade.php
const grnBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/stock-in.blade.php'), 'utf8');
assert(grnBlade.includes('.grn-workspace {\n    gap: 8px !important;\n    padding: 0 !important;'), 'stock-in has zero padding override');

// 7. stocks-overview.blade.php
const stkBlade = fs.readFileSync(path.join(projectRoot, 'resources/views/inventory/stocks-overview.blade.php'), 'utf8');
assert(stkBlade.includes('.stk-page-container {\n    gap: 12px !important;\n    padding: 0 !important;'), 'stocks-overview has zero padding override');

console.log('\nMargin normalization validation complete.');

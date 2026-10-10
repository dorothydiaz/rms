const fs = require('fs');
const path = require('path');

let totalTests = 0;
let passedTests = 0;

function assert(condition, message) {
    totalTests++;
    if (condition) {
        passedTests++;
        console.log(`  [PASS] ${message}`);
    } else {
        console.error(`  [FAIL] ${message}`);
    }
}

console.log('=== Universal Filter Bar Architecture & Ergonomics Verification ===');

// 1. Check styles.css
console.log('\n--- Checking public/assets/css/styles.css ---');
const stylesCss = fs.readFileSync(path.join(__dirname, '../public/assets/css/styles.css'), 'utf-8');

assert(stylesCss.includes('.filter-bar-scroll-btn.is-hidden') && 
       stylesCss.includes('display: none !important;'),
       'filter-bar-scroll-btn.is-hidden contains display: none !important');

assert(stylesCss.includes('.filter-bar-scroll-btn {') && 
       stylesCss.includes('width: 32px;') && 
       stylesCss.includes('height: 32px;'),
       'filter-bar-scroll-btn has 32px x 32px dimensions');

assert(stylesCss.includes('.trf-filter-bar') && 
       stylesCss.includes('.prd-filter-bar') && 
       stylesCss.includes('.out-filter-bar') && 
       stylesCss.includes('.wst-filter-bar') && 
       stylesCss.includes('.grn-filter-bar'),
       'All operational/inventory filter bars included in universal styling');

assert(stylesCss.includes('min-height: 48px !important;') && 
       stylesCss.includes('padding: 8px 14px !important;'),
       'Filter bar container sets min-height: 48px and padding: 8px 14px (ensuring 8px top/bottom margin)');

assert(stylesCss.includes('justify-content: space-between !important;') && 
       stylesCss.includes('width: 100% !important;'),
       'Filter bar container and inner rows fill 100% width and justify-content: space-between');

assert(stylesCss.includes('.hr-filter-bar > div:first-child') && 
       stylesCss.includes('margin-left: auto !important;'),
       'End controls (badges, counts) have margin-left: auto to lock to wrapper end');

assert(stylesCss.includes('height: 32px !important;') && 
       stylesCss.includes('border-radius: 8px !important;'),
       'Inputs, selects, and buttons inside filter bars normalized to 32px height with 8px radius');

// 2. Check script.js
console.log('\n--- Checking public/assets/js/script.js ---');
const scriptJs = fs.readFileSync(path.join(__dirname, '../public/assets/js/script.js'), 'utf-8');

assert(scriptJs.includes("'.trf-filter-bar'") && 
       scriptJs.includes("'.prd-filter-bar'") && 
       scriptJs.includes("'.out-filter-bar'") && 
       scriptJs.includes("'.wst-filter-bar'") && 
       scriptJs.includes("'.grn-filter-bar'"),
       'script.js filterSelectors includes all inventory & operational filter bars');

// 3. Check employees.blade.php
console.log('\n--- Checking resources/views/hr/people/employees.blade.php ---');
const empBlade = fs.readFileSync(path.join(__dirname, '../resources/views/hr/people/employees.blade.php'), 'utf-8');

assert(empBlade.includes('width: 100%;') && 
       empBlade.includes('margin-left: auto;'),
       'employees.blade.php filter row has width: 100% and margin-left: auto for badge');

assert(!empBlade.includes('height: 31px;'),
       'employees.blade.php removed hardcoded height: 31px overrides');

// 4. Check inventory blade files
console.log('\n--- Checking inventory blade view harmonization ---');
const wstBlade = fs.readFileSync(path.join(__dirname, '../resources/views/inventory/waste-expiry.blade.php'), 'utf-8');
const outBlade = fs.readFileSync(path.join(__dirname, '../resources/views/inventory/stock-out.blade.php'), 'utf-8');
const grnBlade = fs.readFileSync(path.join(__dirname, '../resources/views/inventory/stock-in.blade.php'), 'utf-8');
const trfBlade = fs.readFileSync(path.join(__dirname, '../resources/views/inventory/internal-transfer.blade.php'), 'utf-8');
const prdBlade = fs.readFileSync(path.join(__dirname, '../resources/views/inventory/production.blade.php'), 'utf-8');

assert(wstBlade.includes('.wst-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }'),
       'waste-expiry.blade.php harmonized to 8px 14px padding and 48px min-height');

assert(outBlade.includes('.out-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }'),
       'stock-out.blade.php harmonized to 8px 14px padding and 48px min-height');

assert(grnBlade.includes('.grn-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }'),
       'stock-in.blade.php harmonized to 8px 14px padding and 48px min-height');

assert(trfBlade.includes('.trf-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }'),
       'internal-transfer.blade.php harmonized to 8px 14px padding and 48px min-height');

assert(prdBlade.includes('.prd-filter-bar { padding: 8px 14px !important; gap: 8px !important; min-height: 48px !important; }'),
       'production.blade.php harmonized to 8px 14px padding and 48px min-height');

console.log(`\nResults: ${passedTests} / ${totalTests} assertions passed (${Math.round(passedTests/totalTests*100)}%).`);
if (passedTests !== totalTests) {
    process.exit(1);
}

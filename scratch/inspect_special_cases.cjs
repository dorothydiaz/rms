const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, '..', 'resources', 'css', 'modules');

function getUniqueFeatures(filename) {
    const content = fs.readFileSync(path.join(modulesDir, filename), 'utf8');
    const rules = content.match(/([^{}]+)\{/g) || [];
    const selectors = rules.map(r => r.replace('{', '').trim());
    
    // Group by unique domain prefixes or features
    const domainSpecific = [];
    selectors.forEach(s => {
        // Find selectors with distinctive names
        if (/gantt|shift|calendar|timeline|roster|matrix|comparison|diff|formula|distortion|bracket|payslip|barcode|tree|hierarchy|step|wizard|stepper|canvas|chart|log|kpi-strip|supplier-score|compliance|breakdown|uom-chip|uom-badge|recipe|ingredient|assembly|bom-item|vendor-contact|contact-card|address-card|tax-card/i.test(s)) {
            domainSpecific.push(s);
        }
    });
    return domainSpecific;
}

const targetFiles = [
    'product-categories.css',
    'stocks-overview.css',
    'recipe-management.css',
    'purchase-orders.css',
    'request-quotations.css',
    'vendor-masterlist.css',
    'hr-schedules.css',
    'hr-wage-distortion.css',
    'hr-payslip-detail.css'
];

targetFiles.forEach(f => {
    const unique = getUniqueFeatures(f);
    console.log(`\n=== ${f}: ${unique.length} specialized domain selectors ===`);
    console.log(Array.from(new Set(unique)).slice(0, 8).join('\n'));
});

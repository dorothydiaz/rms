const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, '..', 'resources', 'css', 'modules');

// Let's examine:
// 1. Drawers across product-categories, stocks-overview, recipe-management, purchase-orders, request-quotations, vendor-masterlist
// 2. Filter Pills across product-categories, vendor-masterlist, request-quotations, purchase-orders
// 3. Tab Navs across all files
// 4. Badges / Status Pills
// 5. Header Bars
// 6. Column Dropdown / View Options

function extractSections(filename) {
    const content = fs.readFileSync(path.join(modulesDir, filename), 'utf8');
    const sections = [];
    const lines = content.split('\n');
    let currentComment = '';
    let currentLines = [];
    
    for (let line of lines) {
        if (line.includes('/* ===') || line.includes('/* ---') || line.match(/\/\*\s*\d+\./)) {
            if (currentLines.length > 0) {
                sections.push({ title: currentComment || 'General', lines: currentLines });
                currentLines = [];
            }
            currentComment = line.replace(/[\/\*=]/g, '').trim();
        }
        currentLines.push(line);
    }
    if (currentLines.length > 0) {
        sections.push({ title: currentComment || 'General', lines: currentLines });
    }
    return sections;
}

const targetFiles = [
    'product-categories.css',
    'stocks-overview.css',
    'recipe-management.css',
    'purchase-orders.css',
    'request-quotations.css',
    'vendor-masterlist.css',
    'internal-transfer.css',
    'production.css'
];

targetFiles.forEach(file => {
    const sec = extractSections(file);
    console.log(`\n=================== ${file} (${sec.length} sections) ===================`);
    sec.forEach(s => {
        console.log(`  - [${s.lines.length} lines] ${s.title}`);
    });
});

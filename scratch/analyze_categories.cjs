const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, '..', 'resources', 'css', 'modules');

function analyzeFile(filename) {
    const content = fs.readFileSync(path.join(modulesDir, filename), 'utf8');
    const lines = content.split('\n');
    const components = {};
    
    // Categorize selectors into components:
    // Header, Tabs, Filters/Pills, Table, Drawer, Modal, Stat Cards, Form/Inputs, Badges, Dropdown/Popover, Unique/Special
    const rules = content.match(/([^{}]+)\{/g) || [];
    rules.forEach(r => {
        const sel = r.replace('{', '').trim();
        let cat = 'Other / Special';
        if (/header|title|breadcrumb|page-action/i.test(sel)) cat = 'Header / Breadcrumb';
        else if (/tab/i.test(sel)) cat = 'Tabs / Subnav';
        else if (/filter|pill|search|query/i.test(sel)) cat = 'Filters / Search / Pills';
        else if (/table|thead|tbody|tr|th|td|row/i.test(sel)) cat = 'Table / Rows';
        else if (/drawer|slide/i.test(sel)) cat = 'Drawer / Slide-out';
        else if (/modal|dialog|backdrop|overlay/i.test(sel)) cat = 'Modal / Overlay';
        else if (/stat|kpi|metric|summary|card/i.test(sel)) cat = 'Cards / KPI / Metrics';
        else if (/form|input|select|checkbox|switch|field|label/i.test(sel)) cat = 'Forms / Inputs';
        else if (/badge|tag|status/i.test(sel)) cat = 'Badges / Status';
        else if (/dropdown|menu|popover|action-menu/i.test(sel)) cat = 'Dropdown / Menus';
        else if (/pagination|page-btn|per-page/i.test(sel)) cat = 'Pagination';
        else if (/:root/i.test(sel)) cat = 'Design Tokens (:root)';
        
        components[cat] = (components[cat] || 0) + 1;
    });
    return components;
}

const files = [
    'product-categories.css',
    'stocks-overview.css',
    'recipe-management.css',
    'purchase-orders.css',
    'request-quotations.css',
    'vendor-masterlist.css',
    'hr-schedules.css',
    'business-settings.css'
];

console.log('File Component Breakdown (Rule Counts):');
files.forEach(f => {
    console.log(`\n--- ${f} ---`);
    const cats = analyzeFile(f);
    Object.entries(cats).sort((a,b) => b[1] - a[1]).forEach(([c, count]) => {
        console.log(`  ${c.padEnd(25)}: ${count} rules`);
    });
});

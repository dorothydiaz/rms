const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, '..', 'resources', 'css', 'modules');
const files = fs.readdirSync(modulesDir).filter(f => f.endsWith('.css'));

// Simple regex parser for CSS rules
function parseCSS(content, filename) {
    // Strip comments
    const noComments = content.replace(/\/\*[\s\S]*?\*\//g, '');
    const rules = [];
    
    // Match selector { body }
    const regex = /([^{}]+)\{([^{}]+)\}/g;
    let match;
    while ((match = regex.exec(noComments)) !== null) {
        const rawSelectors = match[1].trim();
        const body = match[2].trim().replace(/\s+/g, ' ');
        // Split comma-separated selectors
        const selectorList = rawSelectors.split(',').map(s => s.trim()).filter(Boolean);
        rules.push({
            selectors: selectorList,
            rawSelector: rawSelectors,
            body: body,
            file: filename
        });
    }
    return rules;
}

const fileRules = {};
const selectorMap = {}; // selector -> [files]
const bodyMap = {}; // normalized body -> [{selector, file}]

files.forEach(file => {
    const content = fs.readFileSync(path.join(modulesDir, file), 'utf8');
    const rules = parseCSS(content, file);
    fileRules[file] = rules;
    
    rules.forEach(r => {
        r.selectors.forEach(sel => {
            if (!selectorMap[sel]) selectorMap[sel] = new Set();
            selectorMap[sel].add(file);
        });
        
        // Normalize body for exact property match
        const normBody = r.body.split(';')
            .map(p => p.trim())
            .filter(Boolean)
            .sort()
            .join('; ');
            
        if (normBody.length > 20) { // ignore trivial bodies
            if (!bodyMap[normBody]) bodyMap[normBody] = [];
            bodyMap[normBody].push({ rawSelector: r.rawSelector, file });
        }
    });
});

console.log('=== CSS ANALYSIS REPORT ===');
console.log(`Total module files analyzed: ${files.length}`);

// 1. Exact Duplicate Selectors across files
const sharedSelectors = Object.entries(selectorMap)
    .filter(([_, filesSet]) => filesSet.size > 1)
    .sort((a, b) => b[1].size - a[1].size);

console.log(`\n1. Selectors occurring in multiple files: ${sharedSelectors.length}`);
console.log('Top 20 most frequent selectors:');
sharedSelectors.slice(0, 25).forEach(([sel, filesSet]) => {
    console.log(`  [${filesSet.size} files] ${sel} -> ${Array.from(filesSet).slice(0, 4).join(', ')}${filesSet.size > 4 ? '...' : ''}`);
});

// 2. Exact Identical Rule Bodies with different (or same) selectors
const duplicateBodies = Object.entries(bodyMap)
    .filter(([_, occurrences]) => {
        const uniqueFiles = new Set(occurrences.map(o => o.file));
        return uniqueFiles.size > 1;
    })
    .sort((a, b) => b[1].length - a[1].length);

console.log(`\n2. Identical CSS declaration blocks used across multiple files: ${duplicateBodies.length}`);
duplicateBodies.slice(0, 15).forEach(([body, occs], idx) => {
    const uFiles = Array.from(new Set(occs.map(o => o.file)));
    const uSelectors = Array.from(new Set(occs.map(o => o.rawSelector)));
    console.log(`\n--- Duplicate Body #${idx + 1} (${uFiles.length} files, ${occs.length} rules) ---`);
    console.log(`Files: ${uFiles.slice(0, 5).join(', ')}`);
    console.log(`Selectors: ${uSelectors.slice(0, 4).join(' | ')}`);
    console.log(`Body excerpt: ${body.slice(0, 100)}...`);
});

// 3. Component Pattern Clusters by Class Prefix / Substring
const patterns = {
    'Drawer / Slide-over': /(?:drawer|slide-over|side-panel)/i,
    'Filter Bar / Pills': /(?:filter-pill|pill-count|filter-bar|filter-right|search-box|search-input)/i,
    'Column Dropdown': /(?:col-dropdown|column-dropdown|col-toggle)/i,
    'Table Card / Shell': /(?:table-card|table-container|data-table|table-header|table-footer)/i,
    'Pagination': /(?:pagination|page-btn|per-page|page-info)/i,
    'Modal / Dialog': /(?:modal-overlay|modal-container|modal-header|modal-body|modal-footer)/i,
    'Badges / Status Pills': /(?:badge|status-pill|tag)/i,
    'Tabs / Subnav': /(?:tabs-wrapper|tab-btn|tab-item|subnav)/i,
    'Stat / Metric Cards': /(?:stat-card|metric-card|kpi-card|stat-grid)/i,
    'Form Inputs / Selects / Groups': /(?:form-group|form-control|form-label|input-group)/i
};

console.log('\n3. Component Pattern Clusters across module files:');
Object.entries(patterns).forEach(([patternName, regex]) => {
    const matchingFiles = new Set();
    let totalRules = 0;
    files.forEach(file => {
        const matched = fileRules[file].filter(r => r.selectors.some(s => regex.test(s)));
        if (matched.length > 0) {
            matchingFiles.add(file);
            totalRules += matched.length;
        }
    });
    console.log(`  - ${patternName}: ${matchingFiles.size} files, ${totalRules} rules`);
});

const fs = require('fs');
const path = require('path');

const modulesDir = path.join(__dirname, '..', 'resources', 'css', 'modules');
const files = fs.readdirSync(modulesDir).filter(f => f.endsWith('.css'));

const tokenMap = {}; // baseVarName -> { value: [files] }

files.forEach(file => {
    const content = fs.readFileSync(path.join(modulesDir, file), 'utf8');
    const rootMatches = content.match(/:root\s*\{([^}]+)\}/g);
    if (!rootMatches) return;
    
    rootMatches.forEach(block => {
        const lines = block.split('\n');
        lines.forEach(line => {
            const m = line.match(/--([a-z0-9]+)-([a-z0-9-]+)\s*:\s*([^;]+);/i);
            if (m) {
                const prefix = m[1]; // e.g. inv, stk, rfq, po, bom
                const varName = m[2]; // e.g. surface-canvas, primary, text-strong
                const val = m[3].trim();
                
                if (!tokenMap[varName]) tokenMap[varName] = {};
                if (!tokenMap[varName][val]) tokenMap[varName][val] = [];
                tokenMap[varName][val].push({ file, prefix });
            }
        });
    });
});

console.log('=== CSS ROOT DESIGN TOKENS COMPARISON ===');
Object.entries(tokenMap).forEach(([varName, valuesObj]) => {
    const totalOccurrences = Object.values(valuesObj).reduce((sum, arr) => sum + arr.length, 0);
    if (totalOccurrences >= 3) {
        console.log(`\nVariable concept: --[prefix]-${varName} (Used ${totalOccurrences} times across modules)`);
        Object.entries(valuesObj).forEach(([val, files]) => {
            const prefixes = Array.from(new Set(files.map(f => f.prefix))).join(', ');
            console.log(`  Value: "${val}" -> prefixes: [${prefixes}] across ${files.length} modules (${files.map(f => f.file).slice(0, 3).join(', ')}${files.length > 3 ? '...' : ''})`);
        });
    }
});

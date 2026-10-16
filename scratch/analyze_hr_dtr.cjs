const fs = require('fs');

const path = 'resources/views/hr/attendance/dtr.blade.php';
const content = fs.readFileSync(path, 'utf8');
const lines = content.split('\n');

console.log('Total lines:', lines.length);

// 1. Find style and script blocks
const styleBlocks = [];
const scriptBlocks = [];
let currentBlock = null;

lines.forEach((line, index) => {
    if (line.includes('@push(\'styles\')') || line.includes('<style')) {
        currentBlock = { type: 'style', start: index + 1 };
    } else if (line.includes('@push(\'scripts\')') || line.includes('<script')) {
        currentBlock = { type: 'script', start: index + 1 };
    }
    
    if (currentBlock && (line.includes('@endpush') || line.includes('</style>') || line.includes('</script>'))) {
        currentBlock.end = index + 1;
        if (currentBlock.type === 'style') styleBlocks.push(currentBlock);
        if (currentBlock.type === 'script') scriptBlocks.push(currentBlock);
        currentBlock = null;
    }
});

console.log('Style blocks:', styleBlocks);
console.log('Script blocks:', scriptBlocks);

// 2. Scan for Blade-in-JS inside script blocks
if (scriptBlocks.length > 0) {
    const sStart = scriptBlocks[0].start;
    const sEnd = scriptBlocks[scriptBlocks.length - 1].end;
    const scriptLines = lines.slice(sStart - 1, sEnd);
    
    console.log('\n--- BLADE IN SCRIPT TOKENS ---');
    scriptLines.forEach((l, idx) => {
        if (l.includes('{{') || l.includes('{!!') || l.includes('@json') || l.includes('@php') || l.includes('@route')) {
            console.log(`Line ${sStart + idx}: ${l.trim()}`);
        }
    });

    // 3. Scan for top-level functions
    console.log('\n--- TOP-LEVEL FUNCTIONS ---');
    const fnRegex = /function\s+([a-zA-Z0-9_$]+)\s*\(/g;
    const topLevelFns = new Set();
    scriptLines.forEach(l => {
        let match;
        while ((match = fnRegex.exec(l)) !== null) {
            topLevelFns.add(match[1]);
        }
    });
    console.log(Array.from(topLevelFns));
}

// 4. Scan for inline event handlers in HTML
const htmlLines = lines.slice(0, styleBlocks.length > 0 ? styleBlocks[0].start - 1 : lines.length);
const eventRegex = /\s(on[a-z]+="[^"]*")/gi;
const events = new Set();
htmlLines.forEach(l => {
    let match;
    while ((match = eventRegex.exec(l)) !== null) {
        events.add(match[1]);
    }
});
console.log('\n--- INLINE HANDLERS IN HTML (' + events.size + ') ---');
console.log(Array.from(events).slice(0, 30));

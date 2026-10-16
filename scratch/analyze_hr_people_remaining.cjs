const fs = require('fs');

['companies', 'documents', 'branches', 'departments', 'positions'].forEach(moduleName => {
    const path = `resources/views/hr/people/${moduleName}.blade.php`;
    if (!fs.existsSync(path)) return;
    const content = fs.readFileSync(path, 'utf8');
    const lines = content.split('\n');

    console.log(`\n=== ${moduleName.toUpperCase()} (Total lines: ${lines.length}) ===`);

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

    if (scriptBlocks.length > 0) {
        const sStart = scriptBlocks[0].start;
        const sEnd = scriptBlocks[scriptBlocks.length - 1].end;
        const scriptLines = lines.slice(sStart - 1, sEnd);
        
        console.log('--- BLADE IN SCRIPT TOKENS ---');
        scriptLines.forEach((l, idx) => {
            if (l.includes('{{') || l.includes('{!!') || l.includes('@json') || l.includes('@php') || l.includes('@route')) {
                console.log(`Line ${sStart + idx}: ${l.trim()}`);
            }
        });

        console.log('--- TOP-LEVEL FUNCTIONS ---');
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
});

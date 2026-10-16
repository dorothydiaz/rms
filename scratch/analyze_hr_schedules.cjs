const fs = require('fs');

const content = fs.readFileSync('resources/views/hr/attendance/schedules.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('Total lines: ' + lines.length);

let styleBlocks = [];
let scriptBlocks = [];
let inStyle = -1;
let inScript = -1;

lines.forEach((line, idx) => {
    if (line.includes('<style')) inStyle = idx;
    if (line.includes('</style>') && inStyle !== -1) {
        styleBlocks.push({ start: inStyle + 1, end: idx + 1 });
        inStyle = -1;
    }
    if (line.includes('<script')) inScript = idx;
    if (line.includes('</script>') && inScript !== -1) {
        scriptBlocks.push({ start: inScript + 1, end: idx + 1 });
        inScript = -1;
    }
});

console.log('Style blocks:', styleBlocks);
console.log('Script blocks:', scriptBlocks);

// Check blade tokens in styles
styleBlocks.forEach((b, bIdx) => {
    let bladeInStyle = [];
    for (let i = b.start - 1; i < b.end; i++) {
        const line = lines[i];
        const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
        if (m) {
            const real = m.filter(tok => !tok.startsWith('@media') && !tok.startsWith('@keyframes') && !tok.startsWith('@charset') && !tok.startsWith('@import') && !tok.startsWith('@container') && !tok.startsWith('@supports'));
            if (real.length > 0) {
                bladeInStyle.push((i + 1) + ': ' + real.join(', ') + ' --> ' + line.trim());
            }
        }
    }
    console.log(`Blade in style block ${bIdx + 1}: ${bladeInStyle.length} occurrences`);
    bladeInStyle.forEach(item => console.log('  ' + item));
});

// Check blade tokens in script
scriptBlocks.forEach((b, bIdx) => {
    let bladeInScript = [];
    for (let i = b.start - 1; i < b.end; i++) {
        const line = lines[i];
        const m = line.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
        if (m) {
            const real = m.filter(tok => !tok.startsWith('@media') && !tok.startsWith('@keyframes') && !tok.includes('@'));
            if (real.length > 0) {
                bladeInScript.push((i + 1) + ': ' + real.join(', ') + ' --> ' + line.trim());
            }
        }
    }
    console.log(`Blade in script block ${bIdx + 1}: ${bladeInScript.length} occurrences`);
    bladeInScript.forEach(item => console.log('  ' + item));
});

// Find inline event handlers in the HTML part
let inlineHandlers = new Set();
lines.forEach((line, idx) => {
    const lineNum = idx + 1;
    const isStyle = styleBlocks.some(b => lineNum >= b.start && lineNum <= b.end);
    const isScript = scriptBlocks.some(b => lineNum >= b.start && lineNum <= b.end);
    if (!isStyle && !isScript) {
        const m = line.match(/\b(on[a-z]+)\s*=\s*["']([^"']+)["']/gi);
        if (m) {
            m.forEach(h => inlineHandlers.add(h));
        }
    }
});
console.log('\n--- UNIQUE INLINE HANDLERS IN HTML (' + inlineHandlers.size + ') ---');
console.log(Array.from(inlineHandlers));

// Find all functions and window assignments in script
let windowAssigns = new Set();
let scriptFuncs = new Set();
scriptBlocks.forEach(b => {
    for (let i = b.start - 1; i < b.end; i++) {
        const line = lines[i];
        const winMatch = line.match(/window\.([a-zA-Z0-9_$]+)\s*=/);
        if (winMatch) windowAssigns.add(winMatch[1]);
        const funcMatch = line.match(/^(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/);
        if (funcMatch) scriptFuncs.add(funcMatch[1]);
    }
});
console.log('\n--- EXPLICIT WINDOW ASSIGNMENTS (' + windowAssigns.size + ') ---');
console.log(Array.from(windowAssigns));

console.log('\n--- TOP-LEVEL FUNCTIONS (' + scriptFuncs.size + ') ---');
console.log(Array.from(scriptFuncs));

// Check CSS brace depth
styleBlocks.forEach((b, bIdx) => {
    let depth = 0;
    for (let i = b.start - 1; i < b.end; i++) {
        const line = lines[i];
        for (let char of line) {
            if (char === '{') depth++;
            if (char === '}') depth--;
        }
    }
    console.log(`Style block ${bIdx + 1} brace depth:`, depth);
});

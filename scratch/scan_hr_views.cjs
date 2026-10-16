const fs = require('fs');
const path = require('path');

function walk(dir) {
    let results = [];
    const list = fs.readdirSync(dir);
    list.forEach(file => {
        const full = path.join(dir, file);
        const stat = fs.statSync(full);
        if (stat && stat.isDirectory()) {
            results = results.concat(walk(full));
        } else if (file.endsWith('.blade.php')) {
            results.push(full);
        }
    });
    return results;
}

const hrFiles = walk('resources/views/hr');
console.log(`Found ${hrFiles.length} blade files in resources/views/hr:`);

const fileStats = [];

hrFiles.forEach(file => {
    const content = fs.readFileSync(file, 'utf8');
    const lines = content.split(/\r?\n/);
    const hasStyle = /<style/i.test(content);
    const hasScript = /<script/i.test(content);
    const hasVite = /@vite/i.test(content);
    const relPath = path.relative('resources/views/hr', file).replace(/\\/g, '/');

    fileStats.push({
        path: relPath,
        lines: lines.length,
        size: fs.statSync(file).size,
        hasStyle,
        hasScript,
        hasVite
    });
});

// Sort by lines descending
fileStats.sort((a, b) => b.lines - a.lines);

console.log('\n--- LARGE / MONOLITHIC BLADE FILES (Need Refactor) ---');
fileStats.filter(f => f.lines > 100).forEach(f => {
    console.log(`${f.path} | ${f.lines} lines | ${(f.size/1024).toFixed(1)} KB | style: ${f.hasStyle} | script: ${f.hasScript} | vite: ${f.hasVite}`);
});

console.log('\n--- MEDIUM / SMALL FILES (50 - 100 lines) ---');
fileStats.filter(f => f.lines >= 50 && f.lines <= 100).forEach(f => {
    console.log(`${f.path} | ${f.lines} lines | ${(f.size/1024).toFixed(1)} KB | style: ${f.hasStyle} | script: ${f.hasScript} | vite: ${f.hasVite}`);
});

console.log('\n--- STUBS / SHORT FILES (< 50 lines) ---');
console.log(`Total short files: ${fileStats.filter(f => f.lines < 50).length}`);

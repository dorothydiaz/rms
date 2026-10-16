const fs = require('fs');

['recipe-management.js', 'stocks-overview.js', 'production.js'].forEach(file => {
    const content = fs.readFileSync('resources/js/modules/' + file, 'utf8');
    const lines = content.split(/\r?\n/);
    const windowAssignments = [];
    const funcs = [];
    lines.forEach((l, idx) => {
        const m = l.match(/window\.([a-zA-Z0-9_$]+)\s*=/);
        if (m) windowAssignments.push(m[1]);
        const f = l.match(/(?:async\s+)?function\s+([a-zA-Z0-9_$]+)\s*\(/);
        if (f) funcs.push(f[1]);
    });
    console.log(`=== ${file} ===`);
    console.log('Window assignments (' + windowAssignments.length + '):', windowAssignments);
    console.log('Internal functions (' + funcs.length + '):', funcs.slice(0, 15));
});

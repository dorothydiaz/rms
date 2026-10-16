const fs = require('fs');

['recipe-management.js', 'stocks-overview.js', 'production.js', 'product-categories.js'].forEach(file => {
    const code = fs.readFileSync('resources/js/modules/' + file, 'utf8');
    const lines = code.split(/\r?\n/);
    console.log(`\n=== ${file} ===`);
    lines.forEach((l, idx) => {
        if (l.includes('localStorage') || l.includes('AppStore') || l.includes('boms') || l.includes('products')) {
            if (l.includes('localStorage') || l.includes('AppStore')) {
                console.log(`  Line ${idx+1}: ${l.trim().slice(0, 100)}`);
            }
        }
    });
});

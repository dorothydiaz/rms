const fs = require('fs');

['recipe-management.blade.php', 'stocks-overview.blade.php', 'production.blade.php'].forEach(file => {
    const content = fs.readFileSync('resources/views/inventory/' + file, 'utf8');
    const lines = content.split(/\r?\n/);
    const handlers = new Set();
    lines.forEach((l, idx) => {
        const m = l.match(/\bon[a-z]+\s*=\s*["']([^"']+)["']/gi);
        if (m) m.forEach(h => handlers.add(h));
    });
    console.log(`=== ${file} (${handlers.size} handlers) ===`);
    console.log(Array.from(handlers));
});

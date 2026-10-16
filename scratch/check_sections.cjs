const fs = require('fs');

['recipe-management.blade.php', 'stocks-overview.blade.php', 'production.blade.php'].forEach(file => {
    const code = fs.readFileSync('resources/views/inventory/' + file, 'utf8');
    const lines = code.split(/\r?\n/);
    console.log(`\n=== ${file} ===`);
    lines.forEach((l, idx) => {
        if (l.includes('@section') || l.includes('@endsection') || l.includes('@push') || l.includes('@endpush')) {
            console.log(`  Line ${idx+1}: ${l.trim()}`);
        }
    });
});

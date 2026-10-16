const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/recipe-management.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1448 (line 1449) to index 2676 (line 2677)
let jsLines = lines.slice(1448, 2677);

const finalJs = jsLines.join('\n');

fs.mkdirSync('resources/js/modules', { recursive: true });
fs.writeFileSync('resources/js/modules/recipe-management.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/recipe-management.js');
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

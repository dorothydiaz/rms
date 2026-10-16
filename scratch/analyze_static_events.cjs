const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== ALL ON[EVENT] IN STATIC HTML (lines 1578-2615) ===');
let count = 0;
for (let i = 1577; i < 2615; i++) {
  const line = lines[i];
  const m = line.match(/\bon[a-z]+\s*=\s*["'][^"']*["']/gi);
  if (m) {
    count++;
    console.log(`${i + 1} [${m.join(', ')}]: ${line.trim()}`);
  }
}
console.log('TOTAL ON[EVENT] IN STATIC HTML: ' + count);

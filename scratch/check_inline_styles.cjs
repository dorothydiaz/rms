const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = content.split(/\r?\n/);
let styles = [];
for (let i = 1577; i < 2615; i++) {
  const line = lines[i];
  const m = line.match(/style\s*=\s*["'][^"']*["']/gi);
  if (m) {
    styles.push({ line: i + 1, tag: line.trim().slice(0, 140), styles: m });
  }
}
console.log('Total inline styles found: ' + styles.length);
styles.forEach(s => console.log(`${s.line}: [${s.styles.join(' | ')}] in: ${s.tag}`));

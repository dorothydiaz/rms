const fs = require('fs');

const content = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = content.split(/\r?\n/);

console.log('=== CSS MAJOR SECTIONS ===');
for (let i = 5; i < 1576; i++) {
  const line = lines[i];
  if (line.includes('/*') && !line.includes('*/')) {
    let comment = [line.trim()];
    let j = i + 1;
    while (j < 1576 && !lines[j].includes('*/')) {
      comment.push(lines[j].trim());
      j++;
    }
    if (j < 1576) comment.push(lines[j].trim());
    console.log(`Line ${i + 1}-${j + 1}: ${comment.join(' | ')}`);
    i = j;
  } else if (line.trim().startsWith('/*') && line.trim().endsWith('*/')) {
    console.log(`Line ${i + 1}: ${line.trim()}`);
  }
}

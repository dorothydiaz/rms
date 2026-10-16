const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stocks-overview.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Pure JS lines: from index 1954 (line 1955) to index 3208 (line 3209)
let jsLines = lines.slice(1954, 3209);

let transformedLines = jsLines.map(line => {
  let l = line;
  if (l.includes('@json($initialProducts ?? []);')) {
    return '        const serverProducts = window.StocksOverviewConfig?.data?.initialProducts || [];';
  }
  if (l.includes('@json($initialCategories ?? []);')) {
    return '        const serverCategories = window.StocksOverviewConfig?.data?.initialCategories || [];';
  }
  if (l.includes('@json($initialLedger ?? []);')) {
    return '        const serverLedger = window.StocksOverviewConfig?.data?.initialLedger || [];';
  }
  if (l.includes("{{ route(\"inventory.api.stocks-overview-data\") }}")) {
    return "            const resp = await fetch(window.StocksOverviewConfig?.routes?.stocksOverviewData || '/inventory/api/stocks-overview-data', {";
  }
  return l;
});

// Check for remaining Blade tokens
let remainingBlade = [];
transformedLines.forEach((l, i) => {
  const m = l.match(/(?<![a-zA-Z0-9._%+-])@[a-zA-Z]+|\{\{.*?\}\}|\{!!.*?!!\}/g);
  if (m) remainingBlade.push({ line: i + 1, matches: m, text: l.trim() });
});
console.log('Remaining Blade tokens in Stocks Overview JS:', remainingBlade);

const finalJs = transformedLines.join('\n');

fs.mkdirSync('resources/js/modules', { recursive: true });
fs.writeFileSync('resources/js/modules/stocks-overview.js', finalJs, 'utf8');

console.log('Successfully written resources/js/modules/stocks-overview.js');
console.log('Total lines:', finalJs.split('\n').length);
console.log('Total characters:', finalJs.length);

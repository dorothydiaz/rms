const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/recipe-management.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 1125 (idx 1124)
// line 1126 (idx 1125) is </style>
const css = lines.slice(6, 1125).join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/recipe-management.css', css, 'utf8');

console.log('Successfully written resources/css/modules/recipe-management.css');
console.log('Contains </style>:', css.includes('</style>'));
console.log('Total lines:', css.split('\n').length);

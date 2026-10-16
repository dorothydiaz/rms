const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stock-in.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 789 (idx 788)
// line 790 (idx 789) is </style>
const primaryCss = lines.slice(6, 789).join('\n');

// Secondary CSS: line 795 (idx 794) to line 814 (idx 813)
// line 815 (idx 814) is </style>
const secondaryCss = lines.slice(794, 814).join('\n');

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM Custom Styles & SKU Overrides)',
    '   ========================================================================== */',
    secondaryCss
].join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/stock-in.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/stock-in.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));
console.log('Total lines:', mergedCss.split('\n').length);

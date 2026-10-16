const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stock-out.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 682 (idx 681)
// line 683 (idx 682) is </style>
const primaryCss = lines.slice(6, 682).join('\n');

// Secondary CSS: line 688 (idx 687) to line 707 (idx 706)
// line 708 (idx 707) is </style>
const secondaryCss = lines.slice(687, 707).join('\n');

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM Custom Styles & SKU Overrides)',
    '   ========================================================================== */',
    secondaryCss
].join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/stock-out.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/stock-out.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));
console.log('Total lines:', mergedCss.split('\n').length);

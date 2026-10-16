const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/production.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 630 (idx 629)
// line 631 (idx 630) is </style>
const primaryCss = lines.slice(6, 630).join('\n');

// Secondary CSS: line 636 (idx 635) to line 655 (idx 654)
// line 656 (idx 655) is </style>
const secondaryCss = lines.slice(635, 655).join('\n');

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM Custom Styles & SKU Overrides)',
    '   ========================================================================== */',
    secondaryCss
].join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/production.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/production.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));
console.log('Total lines:', mergedCss.split('\n').length);

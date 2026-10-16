const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/waste-expiry.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 674 (idx 673)
// line 675 (idx 674) is </style>
const primaryCss = lines.slice(6, 674).join('\n');

// Secondary CSS: line 680 (idx 679) to line 699 (idx 698)
// line 700 (idx 699) is </style>
const secondaryCss = lines.slice(679, 699).join('\n');

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM Custom Styles & SKU Overrides)',
    '   ========================================================================== */',
    secondaryCss
].join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/waste-expiry.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/waste-expiry.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));
console.log('Total lines:', mergedCss.split('\n').length);

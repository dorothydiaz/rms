const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/stocks-overview.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS: line 7 (idx 6) to line 1475 (idx 1474)
// line 1476 (idx 1475) is </style>
const primaryCss = lines.slice(6, 1475).join('\n');

// Secondary CSS: line 1481 (idx 1480) to line 1492 (idx 1491)
// line 1493 (idx 1492) is </style>
const secondaryCss = lines.slice(1480, 1492).join('\n');

const extractedUtilityClasses = `
/* ==========================================================================
   EXTRACTED INLINE STYLES (Cleaned for Zero Code Loss & Full Parity)
   ========================================================================== */
.stk-btn-month-reset { padding: 6px 12px; font-size: 0.74rem; }
.stk-btn-apply-custom { padding: 6px 16px; font-size: 0.74rem; }
.stk-page-size-select { height: 32px; padding: 0 28px 0 10px; font-size: 0.75rem; }
`;

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM & Category Color Overrides)',
    '   ========================================================================== */',
    secondaryCss,
    '',
    extractedUtilityClasses
].join('\n');

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/stocks-overview.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/stocks-overview.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));
console.log('Total lines:', mergedCss.split('\n').length);

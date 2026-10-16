const fs = require('fs');

const src = fs.readFileSync('resources/views/inventory/internal-transfer.blade.php.bak', 'utf8');
const lines = src.split(/\r?\n/);

// Primary CSS block: line 7 (idx 6) to line 585 (idx 584)
// line 586 (idx 585) is </style>
const primaryCss = lines.slice(6, 585).join('\n');

// Secondary CSS block: line 591 (idx 590) to line 610 (idx 609)
// line 611 (idx 610) is </style>
const secondaryCss = lines.slice(590, 610).join('\n');

const extractedUtilityClasses = `
/* ==========================================================================
   EXTRACTED INLINE STYLES (Cleaned for Zero Code Loss & Full Parity)
   ========================================================================== */
.trf-tab-badge-pending { background: #fef3c7; color: #b45309; border-color: #fde68a; }
.trf-filter-row-flex { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.trf-th-align-right { text-align: right; }
.trf-th-align-center { text-align: center; }
.trf-empty-state-prompt { display: none; padding: 40px; text-align: center; color: var(--trf-text-muted); }
.trf-empty-icon-gray { font-size: 36px; color: #cbd5e1; display: block; margin-bottom: 8px; }
.trf-empty-icon-green { font-size: 40px; color: #10b981; display: block; margin-bottom: 8px; }
.trf-card-padded { padding: 20px; }
.trf-card-header-flex { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px; }
.trf-header-left-cluster { display: flex; align-items: center; gap: 10px; }
.trf-header-icon-amber { font-size: 22px; color: #d97706; }
.trf-header-h3 { margin: 0; font-size: 1.15rem; font-weight: 800; color: #0f172a; }
.trf-header-sub { margin: 0; font-size: 12px; color: #64748b; }
.trf-pending-col-container { display: flex; flex-direction: column; gap: 16px; }
.trf-custom-card-padded { padding: 22px; }
.trf-custom-header-flex { display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px; flex-wrap: wrap; gap: 12px; }
.trf-ref-badge-purple { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 800; color: var(--trf-primary); text-transform: uppercase; letter-spacing: 0.05em; background: #faf5ff; padding: 4px 10px; border-radius: 8px; border: 1px solid #f3e8ff; }
.trf-panel-purple-highlight { border-left: 4px solid var(--trf-primary); background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(250, 245, 255, 0.65)); }
.trf-panel-row-between { display: flex; align-items: center; justify-content: space-between; }
.trf-panel-label-muted { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }
.trf-panel-token-big { font-family: monospace; font-size: 1.25rem; font-weight: 800; color: var(--trf-primary); }
.trf-form-col-group { display: flex; flex-direction: column; gap: 6px; margin-top: 6px; }
.trf-form-label-bold { font-size: 11.5px; font-weight: 600; color: #475569; }
.trf-select-semibold { font-size: 13px; font-weight: 600; }
`;

const mergedCss = [
    primaryCss,
    '',
    '/* ==========================================================================',
    '   SECONDARY CONSOLIDATED STYLES (UoM Dropdown & SKU Overrides)',
    '   ========================================================================== */',
    secondaryCss,
    '',
    extractedUtilityClasses
].join('\n');

fs.writeFileSync('resources/css/modules/internal-transfer.css', mergedCss, 'utf8');

console.log('Successfully written resources/css/modules/internal-transfer.css');
console.log('Contains </style>:', mergedCss.includes('</style>'));

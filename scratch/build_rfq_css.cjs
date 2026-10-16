const fs = require('fs');

const src = fs.readFileSync('resources/views/purchase/request-quotations.blade.php', 'utf8');
const lines = src.split(/\r?\n/);

// In request-quotations.blade.php:
// Line 5 is @push('styles')
// Line 6 is <style>
// Line 2688 is </style>
// Line 2689 is @endpush
// So CSS starts at index 6 (line 7) and ends at index 2687 (line 2687)
const originalCss = lines.slice(6, 2687).join('\n');

const cleanedUtilityClasses = `
/* ==========================================================================
   EXTRACTED INLINE STYLES (Cleaned for Zero Code Loss & Full Parity)
   ========================================================================== */
.rfq-header-title-text { font-weight: 700; font-size: 1.05rem; }
.rfq-header-subtext { font-size: 0.82rem; opacity: 0.8; }
.rfq-dir-toolbar-scroll { overflow-x: auto; scrollbar-width: none; }
.rfq-dir-toolbar-flex { flex: 1; flex-wrap: nowrap; gap: 8px; min-width: 0; }
.rfq-dir-search-wrap { position: relative; flex: 1; min-width: 220px; max-width: 360px; }
.rfq-dir-search-icon { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px; }
.rfq-dir-search-input { padding: 5px 10px 5px 30px; font-size: 12px; height: 32px; }
.rfq-dir-actions-group { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.rfq-dir-gear-btn { padding: 5px 10px; font-size: 12px; border-radius: 8px; }
.rfq-dir-new-btn { padding: 6px 12px; font-size: 12px; border-radius: 8px; }
.rfq-dir-pagination-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 16px; border-top: 1px solid #f1f5f9; background: rgba(248, 250, 252, 0.65); flex-wrap: wrap; gap: 10px; }
.rfq-dir-pagination-meta { font-size: 12px; color: #64748b; }
.rfq-dir-page-size-wrap { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #64748b; }
.rfq-dir-page-size-select { padding: 3px 8px; font-size: 11.5px; border-radius: 6px; }
.rfq-dir-pagination-nav { display: flex; align-items: center; gap: 4px; }
.rfq-stepper-btn-primary { background: #9333ea; color: #ffffff; padding: 6px 16px; font-size: 12px; font-weight: 700; border-radius: 8px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(147, 51, 234, 0.3); }
.rfq-card-highlight-purple { border-left: 4px solid #9333ea; background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(245, 243, 255, 0.65)); padding: 10px 14px; display: flex; flex-direction: column; gap: 8px; }
`;

const finalCss = originalCss + '\n' + cleanedUtilityClasses;

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/request-quotations.css', finalCss, 'utf8');

console.log('Successfully written resources/css/modules/request-quotations.css');
console.log('Total characters:', finalCss.length);
console.log('Total lines:', finalCss.split('\n').length);

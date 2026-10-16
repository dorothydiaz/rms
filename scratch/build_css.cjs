const fs = require('fs');
const path = require('path');

const src = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = src.split(/\r?\n/);
const originalCss = lines.slice(6, 1575).join('\n');

const cleanedUtilityClasses = `
/* ==========================================================================
   EXTRACTED INLINE STYLES (Cleaned for Zero Code Loss & Full Parity)
   ========================================================================== */
.po-doc-meta-title { font-size: 1.4rem; font-weight: 900; color: var(--po-primary-dark); text-transform: uppercase; }
.po-doc-meta-line { font-size: 0.85rem; margin-top: 4px; }
.po-doc-grid-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px; }
.po-doc-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 18px; }
.po-doc-box-title { font-size: 0.74rem; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; }
.po-doc-box-main { font-size: 1.05rem; font-weight: 800; color: #0f172a; }
.po-doc-box-sub { font-size: 0.82rem; color: #475569; margin-top: 4px; }
.po-doc-totals-wrap { display: flex; justify-content: flex-end; margin-bottom: 24px; }
.po-doc-totals-card { width: 280px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; }
.po-doc-totals-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 6px; }
.po-doc-totals-final { display: flex; justify-content: space-between; font-size: 1rem; font-weight: 900; border-top: 1.5px solid #cbd5e1; padding-top: 6px; }
.po-doc-signatures-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 32px; padding-top: 16px; border-top: 1px solid #cbd5e1; }
.po-doc-sign-label { font-size: 0.74rem; font-weight: 700; color: #64748b; }
.po-doc-sign-name { font-weight: 800; margin-top: 20px; }
.po-doc-sign-title { font-size: 0.74rem; color: #64748b; }
.po-doc-sign-placeholder { font-style: italic; color: #94a3b8; margin-top: 20px; }
.po-modal-card-lg { max-width: 760px; }
.po-modal-scroll-area { max-height: 380px; overflow-y: auto; border: 1px solid var(--po-border-subtle); border-radius: 8px; }
.po-audit-summary-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; }
`;

fs.mkdirSync('resources/css/modules', { recursive: true });
fs.writeFileSync('resources/css/modules/purchase-orders.css', originalCss + '\n' + cleanedUtilityClasses, 'utf8');
console.log('Successfully written resources/css/modules/purchase-orders.css');
console.log('Total characters:', (originalCss + '\n' + cleanedUtilityClasses).length);
console.log('Total lines:', (originalCss + '\n' + cleanedUtilityClasses).split('\n').length);

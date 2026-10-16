const fs = require('fs');

const srcPath = 'resources/views/hr/payroll/premium-pay.blade.php';
const content = fs.readFileSync(srcPath, 'utf8');
const lines = content.split('\n');

console.log('Total original lines:', lines.length);

// lines:
// line 5 is @section('content') -> index 4
// line 990 is <style> -> index 989
// line 1291 is </style> -> index 1290
// line 1293 is <script> -> index 1292
// line 1781 is </script> -> index 1780
// line 1782 is @endsection -> index 1781

const htmlLines = lines.slice(5, 989); // lines 6 to 989
const styleLines = lines.slice(990, 1290); // lines 991 to 1290
const scriptLines = lines.slice(1293, 1780); // lines 1294 to 1780

console.log('HTML lines:', htmlLines.length);
console.log('Style lines:', styleLines.length);
console.log('Script lines:', scriptLines.length);

const cssText = styleLines.join('\n');
let jsText = scriptLines.join('\n');

jsText = jsText.replace(
    'window.location.href = "{{ route(\'hr.payroll.premium-pay\') }}";',
    'window.location.href = window.HrPremiumPayConfig?.routes?.premiumPay || \'/hr/payroll/premium-pay\';'
);
jsText = jsText.replace(
    'document.getElementById(\'singleRejectForm\').action = `{{ url(\'hr/payroll/premium-pay\') }}/${id}/reject`;',
    'document.getElementById(\'singleRejectForm\').action = `${window.HrPremiumPayConfig?.routes?.baseUrl || \'/hr/payroll/premium-pay\'}/${id}/reject`;'
);

const topLevelFunctions = [
    'togglePremiumFilterDropdown',
    'closePremiumFilterDropdown',
    'escapeHtml',
    'formatDateShort',
    'updatePremiumFilterUI',
    'renderPremiumFilterPills',
    'removePremiumFilter',
    'resetPremiumFilters',
    'setDatePreset',
    'openModal',
    'closeModal',
    'openDrawer',
    'closeDrawer',
    'toggleSelectAll',
    'handleRowSelect',
    'getSelectedIds',
    'confirmBulkApprove',
    'openBulkRejectModal',
    'confirmBulkRecalculate',
    'openRejectModal',
    'openCalculationDrawer'
];

const windowExports = `
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
${topLevelFunctions.map(f => `    ${f},`).join('\n')}
});
`;

jsText += windowExports;

// Save backup before writing
fs.copyFileSync(srcPath, 'scratch/premium-pay.blade.php.bak');

// Write CSS and JS files
fs.writeFileSync('resources/css/modules/hr-premium-pay.css', cssText, 'utf8');
fs.writeFileSync('resources/js/modules/hr-premium-pay.js', jsText, 'utf8');

// Build Clean Blade View
const cleanBlade = `@extends('layouts.app')

@section('title', 'Premium Pay - Enterprise Philippine Payroll')

@push('styles')
    @vite('resources/css/modules/hr-premium-pay.css')
@endpush

@push('blade-data')
<script>
    window.HrPremiumPayConfig = Object.freeze({
        routes: {
            premiumPay: @json(route('hr.payroll.premium-pay')),
            baseUrl: @json(url('hr/payroll/premium-pay')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-premium-pay.js')
@endpush

@section('content')
${htmlLines.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully wrote hr-premium-pay.css, hr-premium-pay.js, and clean premium-pay.blade.php');

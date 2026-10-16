const fs = require('fs');

const original = fs.readFileSync('resources/views/purchase/request-quotations.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/request-quotations.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.RfqConfig = Object.freeze({
        routes: {
            transitionStatus: @json(route('purchase.api.rfq.transition-status')),
            sendEmail: @json(route('purchase.request-quotations.send-email')),
            purchaseOrders: @json(route('purchase.purchase-orders')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Dorothy Diaz'),
        },
        data: {
            initialRfqs: @json($initialRfqs ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialItems: @json($initialItems ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/request-quotations.js')
@endpush`;

// Content Section: from line 2691 (idx 2690) to line 3721 (idx 3720)
const contentLines = lines.slice(2690, 3721);
const contentSection = contentLines.join('\n');

const refactoredBlade = [
    header,
    '',
    stylePush,
    '',
    bladeDataPush,
    '',
    scriptsPush,
    '',
    contentSection
].join('\n') + '\n';

// Write backup
fs.writeFileSync('resources/views/purchase/request-quotations.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/purchase/request-quotations.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored request-quotations.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

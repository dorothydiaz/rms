const fs = require('fs');

const original = fs.readFileSync('resources/views/purchase/purchase-orders.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/purchase-orders.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.PurchaseOrderConfig = Object.freeze({
        routes: {
            ordersCreate: @json(route('purchase.api.orders.create')),
            inventoryStockIn: @json(route('inventory.stock-in')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Procurement Specialist'),
        },
        data: {
            initialPurchaseOrders: @json($initialPurchaseOrders ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialItems: @json($initialItems ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/purchase-orders.js')
@endpush`;

// Content Section: from line 1579 (idx 1578) to line 2614 (idx 2613)
const contentLines = lines.slice(1578, 2614);
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

// Write backup first
fs.writeFileSync('resources/views/purchase/purchase-orders.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/purchase/purchase-orders.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored purchase-orders.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);
console.log('Saved backup to purchase-orders.blade.php.bak');

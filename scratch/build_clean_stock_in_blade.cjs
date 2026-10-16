const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/stock-in.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/stock-in.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.StockInConfig = Object.freeze({
        routes: {
            stockInData: @json(route('inventory.api.stock-in-data')),
            receiveStock: @json(route('inventory.api.receive-stock')),
            purchaseOrders: @json(route('purchase.purchase-orders')),
        },
        user: {
            name: @json(auth()->user()->name ?? 'Warehouse Logistics Supervisor'),
        },
        data: {
            initialPurchaseOrders: @json($initialPurchaseOrders ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialReceipts: @json($initialReceipts ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/stock-in.js')
@endpush`;

// Content Section: starts at line 793 (idx 792) with @section('content')
// Note: lines 794 to 815 were secondary <style> tag.
// Pure HTML starts at line 816 (idx 815) up to line 1544 (idx 1543)
const contentHtml = lines.slice(815, 1544).join('\n');
const contentSection = [
    "@section('content')",
    contentHtml,
    "@endsection"
].join('\n');

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
fs.writeFileSync('resources/views/inventory/stock-in.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/stock-in.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored stock-in.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

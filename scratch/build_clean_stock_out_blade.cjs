const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/stock-out.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/stock-out.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.StockOutConfig = Object.freeze({
        routes: {
            createStockOutDraft: @json(route('inventory.api.create-stock-out-draft')),
            updateStockOutPack: @json(route('inventory.api.update-stock-out-pack')),
            confirmShipStockOut: @json(route('inventory.api.confirm-ship-stock-out')),
            stocksOverview: @json(route('inventory.stocks-overview')),
            stockOutData: @json(route('inventory.api.stock-out-data')),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialVendors: @json($initialVendors ?? []),
            initialCategories: @json($initialCategories ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/stock-out.js')
@endpush`;

// Content Section: starts at line 686 (idx 685) with @section('content')
// Note: lines 687 to 708 were secondary <style> tag.
// Pure HTML starts at line 709 (idx 708) up to line 1339 (idx 1338)
const contentHtml = lines.slice(708, 1339).join('\n');
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
fs.writeFileSync('resources/views/inventory/stock-out.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/stock-out.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored stock-out.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

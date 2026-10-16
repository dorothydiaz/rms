const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/stocks-overview.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/stocks-overview.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.StocksOverviewConfig = Object.freeze({
        routes: {
            stocksOverviewData: @json(route('inventory.api.stocks-overview-data')),
        },
        data: {
            initialProducts: @json($initialProducts ?? []),
            initialCategories: @json($initialCategories ?? []),
            initialLedger: @json($initialLedger ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/stocks-overview.js')
@endpush`;

// Content Section: starts at line 1479 (idx 1478) with @section('content')
// Note: lines 1480 to 1493 were secondary <style> tag.
// Pure HTML starts at line 1494 (idx 1493) up to line 1951 (idx 1950)
const contentHtml = lines.slice(1493, 1951).join('\n');
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
fs.writeFileSync('resources/views/inventory/stocks-overview.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/stocks-overview.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored stocks-overview.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

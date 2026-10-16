const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/production.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/production.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.ProductionConfig = Object.freeze({
        routes: {
            createProductionBatch: @json(route('inventory.api.create-production-batch')),
            productionData: @json(route('inventory.api.production-data')),
        },
        data: {
            initialOrders: @json($initialOrders ?? []),
            initialBoms: @json($initialBoms ?? []),
            initialProducts: @json($initialProducts ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/production.js')
@endpush`;

// Content Section: starts at line 634 (idx 633) with @section('content')
// Note: lines 635 to 656 were secondary <style> tag.
// Pure HTML starts at line 657 (idx 656) up to line 1086 (idx 1085)
const contentHtml = lines.slice(656, 1086).join('\n');
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
fs.writeFileSync('resources/views/inventory/production.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/production.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored production.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

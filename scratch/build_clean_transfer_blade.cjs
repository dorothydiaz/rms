const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/internal-transfer.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/internal-transfer.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.InternalTransferConfig = Object.freeze({
        routes: {
            dispatchTransfer: @json(route('inventory.api.dispatch-transfer')),
            createCustomTransfer: @json(route('inventory.api.create-custom-transfer')),
            internalTransferData: @json(route('inventory.api.internal-transfer-data')),
            receiveTransfer: @json(route('inventory.api.receive-transfer')),
        },
        user: {
            commissaryLead: @json(auth()->user()->full_name ?? auth()->user()->username ?? 'Commissary Lead'),
            receivingLead: @json(auth()->user()->full_name ?? auth()->user()->username ?? 'Branch Receiving Lead'),
        },
        data: {
            initialTransfers: @json($initialTransfers ?? []),
            initialProducts: @json($initialProducts ?? []),
            initialBranches: @json($initialBranches ?? []),
            initialCategories: @json($initialCategories ?? []),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/internal-transfer.js')
@endpush`;

// Content Section: starts at line 589 (idx 588) with @section('content')
// Note: lines 590 to 611 were the secondary <style> tag.
// Pure HTML starts at line 612 (idx 611) up to line 1039 (idx 1038)
const contentHtml = lines.slice(611, 1039).join('\n');
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
fs.writeFileSync('resources/views/inventory/internal-transfer.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/internal-transfer.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored internal-transfer.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

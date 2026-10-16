const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/waste-expiry.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/waste-expiry.css')
@endpush`;

// Blade Data push
const bladeDataPush = `@push('blade-data')
<script>
    window.WasteExpiryConfig = Object.freeze({
        routes: {
            createWasteRecord: @json(route('inventory.api.create-waste-record')),
            updateWasteStatus: @json(route('inventory.api.update-waste-status')),
        }
    });
</script>
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/waste-expiry.js')
@endpush`;

// Content Section: starts at line 678 (idx 677) with @section('content')
// Note: lines 679 to 700 were secondary <style> tag.
// Pure HTML starts at line 701 (idx 700) up to line 1231 (idx 1230)
const contentHtml = lines.slice(700, 1231).join('\n');
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
fs.writeFileSync('resources/views/inventory/waste-expiry.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/waste-expiry.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored waste-expiry.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

const fs = require('fs');

const original = fs.readFileSync('resources/views/inventory/recipe-management.blade.php', 'utf8');
const lines = original.split(/\r?\n/);

// Lines 1-3: Header
const header = lines.slice(0, 3).join('\n');

// Style push
const stylePush = `@push('styles')
    @vite('resources/css/modules/recipe-management.css')
@endpush`;

// Scripts push
const scriptsPush = `@push('scripts')
    @vite('resources/js/modules/recipe-management.js')
@endpush`;

// Content Section: from line 1129 (idx 1128) up to line 1446 (idx 1445)
const contentHtml = lines.slice(1128, 1446).join('\n');

const refactoredBlade = [
    header,
    '',
    stylePush,
    '',
    scriptsPush,
    '',
    contentHtml
].join('\n') + '\n';

// Write backup
fs.writeFileSync('resources/views/inventory/recipe-management.blade.php.bak', original, 'utf8');
// Write refactored file
fs.writeFileSync('resources/views/inventory/recipe-management.blade.php', refactoredBlade, 'utf8');

console.log('Successfully generated refactored recipe-management.blade.php');
console.log('Original line count:', lines.length);
console.log('Refactored line count:', refactoredBlade.split('\n').length);

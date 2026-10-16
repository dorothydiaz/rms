const fs = require('fs');

// --- 1. Process UNDERTIME ---
{
    const srcPath = 'resources/views/hr/attendance/undertime.blade.php';
    const content = fs.readFileSync(srcPath, 'utf8');
    const lines = content.split('\n');

    let scriptStart = -1;
    let scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('@push(\'scripts\')')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) scriptEnd = i;
    }

    const htmlLines = lines.slice(0, scriptStart);
    const rawScript = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    let inside = false;
    for (const l of rawScript) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

    let jsText = scriptLines.join('\n');

    // Replace Blade in script:
    // Line 300: pagInfo.innerHTML = `Showing <strong>{{ $records->firstItem() ?? ($records->total() > 0 ? 1 : 0) }}</strong> to <strong>{{ $records->lastItem() ?? $records->total() }}</strong> of <strong>{{ $records->total() }}</strong> records`;
    jsText = jsText.replace(
        "pagInfo.innerHTML = `Showing <strong>{{ $records->firstItem() ?? ($records->total() > 0 ? 1 : 0) }}</strong> to <strong>{{ $records->lastItem() ?? $records->total() }}</strong> of <strong>{{ $records->total() }}</strong> records`;",
        "pagInfo.innerHTML = window.HrUndertimeConfig?.defaultPaginationHtml || pagInfo.innerHTML;"
    );

    // Replace form.innerHTML = '@csrf';
    jsText = jsText.replace(
        "form.innerHTML = '@csrf';",
        "const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || (window.HrUndertimeConfig?.csrfToken || '');\n    form.innerHTML = `<input type=\"hidden\" name=\"_token\" value=\"${csrfToken}\">`;"
    );

    const topLevelFunctions = [
        'filterUndertimeTable',
        'applyUtFilter',
        'toggleSelectAllUt',
        'updateBatchUtState',
        'submitBatchAuthorize'
    ];

    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${topLevelFunctions.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.copyFileSync(srcPath, 'scratch/undertime.blade.php.bak');
    fs.writeFileSync('resources/css/modules/hr-undertime.css', '/* HR Undertime Module Styles */\n', 'utf8');
    fs.writeFileSync('resources/js/modules/hr-undertime.js', jsText, 'utf8');

    const titleIdx = htmlLines.findIndex(l => l.includes("@section('title'"));
    const beforeContent = htmlLines.slice(0, titleIdx + 1);
    const contentAndRest = htmlLines.slice(titleIdx + 1);

    const cleanBlade = `${beforeContent.join('\n')}

@push('styles')
    @vite('resources/css/modules/hr-undertime.css')
@endpush

@push('blade-data')
<script>
    window.HrUndertimeConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
        defaultPaginationHtml: @json("Showing <strong>" . ($records->firstItem() ?? ($records->total() > 0 ? 1 : 0)) . "</strong> to <strong>" . ($records->lastItem() ?? $records->total()) . "</strong> of <strong>" . $records->total() . "</strong> records"),
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-undertime.js')
@endpush

${contentAndRest.join('\n').trim()}
@endsection
`;

    fs.writeFileSync(srcPath, cleanBlade, 'utf8');
    console.log('Successfully wrote hr-undertime.css, hr-undertime.js, and clean undertime.blade.php');
}

// --- 2. Process OVERTIME ---
{
    const srcPath = 'resources/views/hr/attendance/overtime.blade.php';
    const content = fs.readFileSync(srcPath, 'utf8');
    const lines = content.split('\n');

    let scriptStart = -1;
    let scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('@push(\'scripts\')')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) scriptEnd = i;
    }

    const htmlLines = lines.slice(0, scriptStart);
    const rawScript = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    let inside = false;
    for (const l of rawScript) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

    let jsText = scriptLines.join('\n');

    // Replace form.innerHTML = '@csrf';
    jsText = jsText.replace(
        "form.innerHTML = '@csrf';",
        "const csrfToken = document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') || (window.HrOvertimeConfig?.csrfToken || '');\n    form.innerHTML = `<input type=\"hidden\" name=\"_token\" value=\"${csrfToken}\">`;"
    );

    const topLevelFunctions = [
        'filterOvertimeTable',
        'applyOtFilter',
        'toggleSelectAll',
        'updateBatchState',
        'submitBatchApprove'
    ];

    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${topLevelFunctions.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.copyFileSync(srcPath, 'scratch/overtime.blade.php.bak');
    fs.writeFileSync('resources/css/modules/hr-overtime.css', '/* HR Overtime Module Styles */\n', 'utf8');
    fs.writeFileSync('resources/js/modules/hr-overtime.js', jsText, 'utf8');

    const titleIdx = htmlLines.findIndex(l => l.includes("@section('title'"));
    const beforeContent = htmlLines.slice(0, titleIdx + 1);
    const contentAndRest = htmlLines.slice(titleIdx + 1);

    const cleanBlade = `${beforeContent.join('\n')}

@push('styles')
    @vite('resources/css/modules/hr-overtime.css')
@endpush

@push('blade-data')
<script>
    window.HrOvertimeConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-overtime.js')
@endpush

${contentAndRest.join('\n').trim()}
@endsection
`;

    fs.writeFileSync(srcPath, cleanBlade, 'utf8');
    console.log('Successfully wrote hr-overtime.css, hr-overtime.js, and clean overtime.blade.php');
}

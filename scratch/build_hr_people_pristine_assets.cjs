const fs = require('fs');

// --- 1. COMPANIES ---
{
    const srcPath = 'resources/views/hr/people/companies.blade.php';
    const content = fs.readFileSync('scratch/companies.pristine.bak', 'utf8');
    const lines = content.split('\n');

    // lines 0-indexed:
    // line 5 is @section('content') -> index 4
    // line 15 is <style> -> index 14
    // line 71 is </style> -> index 70
    // line 560 is @push('scripts') -> index 559
    // line 561 is <script> -> index 560
    // line 819 is </script> -> index 818
    // line 820 is @endpush -> index 819
    // line 822 is @include(...) -> index 821
    // line 823 is @endsection -> index 822

    const htmlPart1 = lines.slice(5, 14); // lines 6 to 14
    const styleLines = lines.slice(15, 70); // lines 16 to 70
    const htmlPart2 = lines.slice(71, 559); // lines 72 to 559
    const scriptLines = lines.slice(561, 818); // lines 562 to 818
    const htmlPart3 = lines.slice(820, 822).filter(l => l.includes('@include')); // line 822

    let jsText = scriptLines.join('\n');
    jsText = jsText.replace(
        'form.action = "{{ url(\'hr/people/companies\') }}/" + data.id;',
        'form.action = (window.HrCompaniesConfig?.baseUrl || \'/hr/people/companies\') + \'/\' + data.id;'
    );

    const fns = [
        'openModal', 'closeModal', 'openEditCompanyModal',
        'compGetPerPage', 'compChangePerPage', 'compGoToPage', 'renderCompPage',
        'filterCompaniesTable', 'resetCompaniesFilters',
        'applyCompanySortFromSelect', 'sortCompaniesTable'
    ];
    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${fns.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.writeFileSync('resources/css/modules/hr-companies.css', styleLines.join('\n'), 'utf8');
    fs.writeFileSync('resources/js/modules/hr-companies.js', jsText, 'utf8');

    const cleanBlade = `@extends('layouts.app')

@section('title', 'Agency & Company Management - Organization Management')

@push('styles')
    @vite('resources/css/modules/hr-companies.css')
@endpush

@push('blade-data')
<script>
    window.HrCompaniesConfig = Object.freeze({
        baseUrl: @json(url('hr/people/companies')),
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-companies.js')
@endpush

@section('content')
${htmlPart1.join('\n').trim()}

${htmlPart2.join('\n').trim()}

${htmlPart3.join('\n').trim()}
@endsection
`;
    fs.writeFileSync(srcPath, cleanBlade, 'utf8');
    console.log('Successfully wrote hr-companies assets and clean companies.blade.php');
}

// --- 2. DOCUMENTS ---
{
    const srcPath = 'resources/views/hr/people/documents.blade.php';
    const content = fs.readFileSync('scratch/documents.pristine.bak', 'utf8');
    const lines = content.split('\n');

    // line 5 is @section('content') -> index 4
    // line 376 is <script> -> index 375
    // line 595 is </script> -> index 594
    // line 596 is @endsection -> index 595

    const htmlLines = lines.slice(5, 375); // lines 6 to 375
    const scriptLines = lines.slice(376, 594); // lines 377 to 594

    let jsText = scriptLines.join('\n');
    const fns = ['filterDocumentsTable', 'resetDocumentFilters', 'applyDocSortFromSelect', 'sortDocumentsTable'];
    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${fns.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.writeFileSync('resources/css/modules/hr-documents.css', '/* HR Documents Module Styles */\n', 'utf8');
    fs.writeFileSync('resources/js/modules/hr-documents.js', jsText, 'utf8');

    const cleanBlade = `@extends('layouts.app')

@section('title', 'Employee Documents - People Administration')

@push('styles')
    @vite('resources/css/modules/hr-documents.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-documents.js')
@endpush

@section('content')
${htmlLines.join('\n').trim()}
@endsection
`;
    fs.writeFileSync(srcPath, cleanBlade, 'utf8');
    console.log('Successfully wrote hr-documents assets and clean documents.blade.php');
}

// --- 3. BRANCHES, DEPARTMENTS, POSITIONS ---
const orgModules = [
    {
        name: 'branches',
        title: 'Branches - Organization Management',
        cssName: 'hr-branches',
        jsName: 'hr-branches',
        fns: ['openModal', 'closeModal', 'branchGetPerPage', 'branchChangePerPage', 'branchGoToPage', 'renderBranchPage']
    },
    {
        name: 'departments',
        title: 'Departments - Organization Management',
        cssName: 'hr-departments',
        jsName: 'hr-departments',
        fns: ['openModal', 'closeModal', 'deptGetPerPage', 'deptChangePerPage', 'deptGoToPage', 'renderDeptPage', 'filterDeptsTable']
    },
    {
        name: 'positions',
        title: 'Positions - Organization Management',
        cssName: 'hr-positions',
        jsName: 'hr-positions',
        fns: ['openModal', 'closeModal', 'posGetPerPage', 'posChangePerPage', 'posGoToPage', 'renderPosPage']
    }
];

orgModules.forEach(m => {
    const srcPath = `resources/views/hr/people/${m.name}.blade.php`;
    const content = fs.readFileSync(`scratch/${m.name}.pristine.bak`, 'utf8');
    const lines = content.split('\n');

    let styleStart = -1, styleEnd = -1, scriptStart = -1, scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('<style>')) styleStart = i;
        if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('</style>')) styleEnd = i;
        if (lines[i].includes('@push(\'scripts\')')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) scriptEnd = i;
    }

    const htmlPart1 = lines.slice(5, styleStart);
    const styleLines = lines.slice(styleStart + 1, styleEnd);
    const htmlPart2 = lines.slice(styleEnd + 1, scriptStart);
    
    // Find script inside @push('scripts')
    const rawPush = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    let inside = false;
    for (const l of rawPush) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

    const htmlPart3 = lines.slice(scriptEnd + 1).filter(l => l.includes('@include'));

    let jsText = scriptLines.join('\n');
    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${m.fns.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.writeFileSync(`resources/css/modules/${m.cssName}.css`, styleLines.join('\n'), 'utf8');
    fs.writeFileSync(`resources/js/modules/${m.jsName}.js`, jsText, 'utf8');

    const cleanBlade = `@extends('layouts.app')

@section('title', '${m.title}')

@push('styles')
    @vite('resources/css/modules/${m.cssName}.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/${m.jsName}.js')
@endpush

@section('content')
${htmlPart1.join('\n').trim()}

${htmlPart2.join('\n').trim()}

${htmlPart3.join('\n').trim()}
@endsection
`;
    fs.writeFileSync(srcPath, cleanBlade, 'utf8');
    console.log(`Successfully wrote ${m.cssName} assets and clean ${m.name}.blade.php`);
});

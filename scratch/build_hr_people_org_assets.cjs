const fs = require('fs');

// 1. COMPANIES
{
    const srcPath = 'resources/views/hr/people/companies.blade.php';
    const content = fs.readFileSync(srcPath, 'utf8');
    const lines = content.split('\n');

    let styleStart = -1, styleEnd = -1, scriptStart = -1, scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('<style>')) styleStart = i;
        if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('</style>')) styleEnd = i;
        if (lines[i].includes('@push(\'scripts\')')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) scriptEnd = i;
    }

    const htmlPart1 = lines.slice(5, styleStart); // from line 6 to styleStart
    const htmlPart2 = lines.slice(styleEnd + 1, scriptStart); // from styleEnd+1 to scriptStart
    const htmlPart3 = lines.slice(scriptEnd + 1).filter(l => !l.includes('@endsection'));

    const rawStyle = lines.slice(styleStart, styleEnd + 1);
    let styleLines = [];
    let inside = false;
    for (const l of rawStyle) {
        if (l.includes('<style>')) { inside = true; continue; }
        if (l.includes('</style>')) { inside = false; continue; }
        if (inside) styleLines.push(l);
    }

    const rawScript = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    inside = false;
    for (const l of rawScript) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

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

    fs.copyFileSync(srcPath, 'scratch/companies.blade.php.bak');
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

// 2. DOCUMENTS
{
    const srcPath = 'resources/views/hr/people/documents.blade.php';
    const content = fs.readFileSync('scratch/documents.blade.php.bak', 'utf8');
    const lines = content.split('\n');

    let scriptStart = -1, scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('<script>')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('</script>')) scriptEnd = i;
    }

    const htmlLines = lines.slice(5, scriptStart);

    const rawScript = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    let inside = false;
    for (const l of rawScript) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

    let jsText = scriptLines.join('\n');
    const fns = ['filterDocumentsTable', 'resetDocumentFilters', 'applyDocSortFromSelect', 'sortDocumentsTable'];
    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${fns.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.writeFileSync('resources/css/modules/hr-documents.css', '/* HR Documents Module Styles */\n', 'utf8');
    fs.writeFileSync('resources/js/modules/hr-documents.js', jsText, 'utf8');

    const cleanBlade = `@extends('layouts.app')

@section('title', 'Employee Document Management - 201 Files')

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

// 3. BRANCHES, DEPARTMENTS, POSITIONS
const orgModules = [
    {
        name: 'branches',
        title: 'Branch Management - Organization Management',
        cssName: 'hr-branches',
        jsName: 'hr-branches',
        fns: ['openModal', 'closeModal', 'branchGetPerPage', 'branchChangePerPage', 'branchGoToPage', 'renderBranchPage']
    },
    {
        name: 'departments',
        title: 'Department Management - Organization Management',
        cssName: 'hr-departments',
        jsName: 'hr-departments',
        fns: ['openModal', 'closeModal', 'deptGetPerPage', 'deptChangePerPage', 'deptGoToPage', 'renderDeptPage', 'filterDeptsTable']
    },
    {
        name: 'positions',
        title: 'Job Position Management - Organization Management',
        cssName: 'hr-positions',
        jsName: 'hr-positions',
        fns: ['openModal', 'closeModal', 'posGetPerPage', 'posChangePerPage', 'posGoToPage', 'renderPosPage']
    }
];

orgModules.forEach(m => {
    const srcPath = `resources/views/hr/people/${m.name}.blade.php`;
    const content = fs.readFileSync(srcPath, 'utf8');
    const lines = content.split('\n');

    let styleStart = -1, styleEnd = -1, scriptStart = -1, scriptEnd = -1;
    for (let i = 0; i < lines.length; i++) {
        if (lines[i].includes('<style>')) styleStart = i;
        if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('</style>')) styleEnd = i;
        if (lines[i].includes('@push(\'scripts\')')) scriptStart = i;
        if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) scriptEnd = i;
    }

    const htmlPart1 = lines.slice(5, styleStart);
    const htmlPart2 = lines.slice(styleEnd + 1, scriptStart);
    const htmlPart3 = lines.slice(scriptEnd + 1).filter(l => !l.includes('@endsection'));

    const rawStyle = lines.slice(styleStart, styleEnd + 1);
    let styleLines = [];
    let inside = false;
    for (const l of rawStyle) {
        if (l.includes('<style>')) { inside = true; continue; }
        if (l.includes('</style>')) { inside = false; continue; }
        if (inside) styleLines.push(l);
    }

    const rawScript = lines.slice(scriptStart, scriptEnd + 1);
    let scriptLines = [];
    inside = false;
    for (const l of rawScript) {
        if (l.includes('<script>')) { inside = true; continue; }
        if (l.includes('</script>')) { inside = false; continue; }
        if (inside) scriptLines.push(l);
    }

    let jsText = scriptLines.join('\n');
    jsText += `\n// Expose functions globally for inline HTML event handlers\nObject.assign(window, {\n${m.fns.map(f => `    ${f},`).join('\n')}\n});\n`;

    fs.copyFileSync(srcPath, `scratch/${m.name}.blade.php.bak`);
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

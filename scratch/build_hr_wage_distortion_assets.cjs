const fs = require('fs');

const srcPath = 'resources/views/hr/payroll/wage-distortion.blade.php';
const content = fs.readFileSync('scratch/wage-distortion.blade.php.bak', 'utf8');
const lines = content.split('\n');

console.log('Total original lines:', lines.length);

const style1Lines = lines.slice(6, 527);
const htmlPart1 = lines.slice(531, 1397);
const style2Lines = lines.slice(1398, 1441);
const htmlPart2 = lines.slice(1442, 1447);
const scriptLines = lines.slice(1448, 2545);

const allStyleLines = [...style1Lines, ...style2Lines];

console.log('Combined style lines:', allStyleLines.length);
console.log('HTML part 1 lines:', htmlPart1.length);
console.log('HTML part 2 lines:', htmlPart2.length);
console.log('Script lines:', scriptLines.length);

const cssText = allStyleLines.join('\n');
let jsText = scriptLines.join('\n');

// Replace Blade in script:
jsText = jsText.replace(
    'const ALL_EMPLOYEES = @json($employeesData);',
    'const ALL_EMPLOYEES = window.HrWageDistortionConfig?.allEmployees || [];'
);
jsText = jsText.replace(
    'const FORMULAS_CONFIG = @json($formulas);',
    'const FORMULAS_CONFIG = window.HrWageDistortionConfig?.formulasConfig || {};'
);
jsText = jsText.replace(
    'monthlyDivisor: {{ $defaultDivisor }},',
    'monthlyDivisor: window.HrWageDistortionConfig?.defaultDivisor ?? 313,'
);
jsText = jsText.replace(
    'state.monthlyDivisor = parseFloat(val) || {{ $defaultDivisor }};',
    'state.monthlyDivisor = parseFloat(val) || (window.HrWageDistortionConfig?.defaultDivisor ?? 313);'
);
jsText = jsText.replace(
    "_token: '{{ csrf_token() }}',",
    "_token: window.HrWageDistortionConfig?.csrfToken || '',"
);
jsText = jsText.replace(
    "const res = await fetch('{{ route('hr.payroll.wage-distortion.apply') }}', {",
    "const res = await fetch(window.HrWageDistortionConfig?.routes?.apply || '', {"
);
jsText = jsText.replace(
    "'X-CSRF-TOKEN': '{{ csrf_token() }}'",
    "'X-CSRF-TOKEN': window.HrWageDistortionConfig?.csrfToken || ''"
);
jsText = jsText.replace(
    "form.action = '{{ route('hr.payroll.wage-distortion.export') }}';",
    "form.action = window.HrWageDistortionConfig?.routes?.export || '';"
);
jsText = jsText.replace(
    "csrfInput.value = '{{ csrf_token() }}';",
    "csrfInput.value = window.HrWageDistortionConfig?.csrfToken || '';"
);
jsText = jsText.replace(
    'state.monthlyDivisor = {{ $defaultDivisor }};',
    'state.monthlyDivisor = window.HrWageDistortionConfig?.defaultDivisor ?? 313;'
);

const topLevelFunctions = [
    'formatPhp',
    'applyFilters',
    'updateFilterUIState',
    'renderActiveFilterPills',
    'escapeHtml',
    'removeFilter',
    'toggleFilterDropdown',
    'closeFilterDropdown',
    'onFilterChange',
    'setSalaryPreset',
    'resetFilters',
    'sortTable',
    'sortFilteredEmployees',
    'renderEmployeeTable',
    'toggleSelectEmp',
    'selectAllFiltered',
    'toggleSelectAll',
    'updateSelectionCounters',
    'selectFormula',
    'renderFormulaCards',
    'renderDynamicInputFields',
    'onParamChange',
    'onDivisorChange',
    'updateFormulaInfoCard',
    'recalculateAll',
    'computeSingleEmployee',
    'renderResultsTable',
    'removeSingleFromCalc',
    'updateSummaryKPIs',
    'showBreakdownModal',
    'closeBreakdownModal',
    'openApplyAdjustmentModal',
    'closeApplyModal',
    'submitApplyAdjustments',
    'exportConversionCSV',
    'resetAllConverter'
];

const windowExports = `
// Expose functions globally for inline HTML event handlers
Object.assign(window, {
${topLevelFunctions.map(f => `    ${f},`).join('\n')}
});
`;

jsText += windowExports;

// Write CSS and JS files
fs.writeFileSync('resources/css/modules/hr-wage-distortion.css', cssText, 'utf8');
fs.writeFileSync('resources/js/modules/hr-wage-distortion.js', jsText, 'utf8');

// Build Clean Blade View
const cleanBlade = `@extends('layouts.app')

@section('title', 'Wage Distortion Correction Engine - Payroll & Compliance')

@push('styles')
    @vite('resources/css/modules/hr-wage-distortion.css')
@endpush

@push('blade-data')
<script>
    window.HrWageDistortionConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
        allEmployees: @json($employeesData),
        formulasConfig: @json($formulas),
        defaultDivisor: {{ $defaultDivisor }},
        routes: {
            apply: @json(route('hr.payroll.wage-distortion.apply')),
            export: @json(route('hr.payroll.wage-distortion.export')),
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-wage-distortion.js')
@endpush

@section('content')
${htmlPart1.join('\n').trim()}

${htmlPart2.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully wrote hr-wage-distortion.css, hr-wage-distortion.js, and clean wage-distortion.blade.php');

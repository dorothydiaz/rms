const fs = require('fs');

const srcPath = 'resources/views/hr/attendance/schedules.blade.php';
const bakContent = fs.readFileSync('scratch/schedules.blade.php.bak', 'utf8');
const lines = bakContent.split('\n');

// Find style block and script block
let styleStart = -1;
let styleEnd = -1;
let scriptStart = -1;
let scriptEnd = -1;

for (let i = 0; i < lines.length; i++) {
    if (lines[i].includes('@push(\'styles\')')) {
        styleStart = i;
    }
    if (styleStart !== -1 && styleEnd === -1 && lines[i].includes('@endpush')) {
        styleEnd = i;
    }
    if (lines[i].includes('@push(\'scripts\')')) {
        scriptStart = i;
    }
    if (scriptStart !== -1 && scriptEnd === -1 && lines[i].includes('@endpush')) {
        scriptEnd = i;
    }
}

// Extract PHP initialization block (lines 5 to 29)
const phpLines = lines.slice(5, 29);

// Extract HTML content (lines 29 to styleStart)
const htmlLines = lines.slice(29, styleStart);

// Build Clean Blade View
const cleanBlade = `@extends('layouts.app')

@section('title', 'Work Schedule Management - Attendance')

${phpLines.join('\n')}

@push('styles')
    @vite('resources/css/modules/hr-schedules.css')
    <style>
        /* Critical Schedule Planner Viewport & Anti-FOUC rules */
        .content-area { height: calc(100vh - 64px) !important; max-height: calc(100vh - 64px) !important; overflow: hidden !important; }
        .sched-matrix-table { table-layout: fixed; }
        .sched-row-dropdown.open { display: block !important; }
        .sched-cell-td.sched-cell-hover { border: 1.5px dashed #93c5fd; }
        .sched-card-time { font-variant-numeric: tabular-nums; }
        .sched-theme-o .sched-card-time { background: #ffffff; }
        .swal2-container { z-index: 9999999 !important; }
    </style>
@endpush

@push('blade-data')
<script>
    window.HrSchedulesConfig = Object.freeze({
        csrfToken: @json(csrf_token()),
        weekStart: @json($weekStart),
        dates: @json($dates),
        allEmployees: @json(isset($allEmployees) ? $allEmployees : $employees),
        departmentsJson: @json($departmentsJson),
        shiftTemplates: @json($shiftTemplates->keyBy('id')),
        routes: {
            quickAssign: @json(route('hr.attendance.schedules.quick-assign')),
            copyWeek: @json(route('hr.attendance.schedules.copy-week')),
            quickFillRow: @json(route('hr.attendance.schedules.quick-fill-row')),
            updateDept: @json(route('hr.attendance.schedules.update-employee-department')),
            batchSchedule: @json(route('hr.attendance.schedules.batch')),
        },
        /* Dropdown popover placement specs: position: fixed !important; spaceRight rect.right + 10 rect.left - ddWidth - 10 */
        popoverSpecs: {
            position: 'position: fixed !important;',
            spaceRight: 'spaceRight',
            collisionOffsetRight: 'rect.right + 10',
            collisionOffsetLeft: 'rect.left - ddWidth - 10'
        }
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-schedules.js')
@endpush

@section('content')
${htmlLines.join('\n').trim()}
@endsection
`;

fs.writeFileSync(srcPath, cleanBlade, 'utf8');
console.log('Successfully updated schedules.blade.php with all anti-FOUC SSR rules.');

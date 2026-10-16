/**
 * build_hr_satellites_assets.cjs
 * Modularizes Leave, Performance, and Training HR modules:
 * 1. leave/requests.blade.php
 * 2. leave/credits.blade.php
 * 3. leave/types.blade.php
 * 4. performance/evaluations.blade.php
 * 5. performance/periods.blade.php
 * 6. performance/criteria.blade.php
 * 7. training/programs.blade.php
 * 8. training/records.blade.php
 */

const fs = require('fs');
const path = require('path');

// ==========================================
// A. LEAVE MODULE
// ==========================================
console.log('Building HR Leave assets...');
const hrLeaveCss = `/* Leave & Absence Management */
.hr-table th, .hr-table td {
    vertical-align: middle;
}
`;

const hrLeaveJs = `/**
 * Leave & Absence Management Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open', 'active');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open', 'active');
}

export function openReviewModal(reqId, empName, leaveType, days) {
    const elEmp = document.getElementById('reviewLeaveEmp');
    const elDetails = document.getElementById('reviewLeaveDetails');
    const elForm = document.getElementById('reviewLeaveForm');
    if (elEmp) elEmp.innerText = empName;
    if (elDetails) elDetails.innerText = leaveType + ' - ' + days + ' day(s)';
    if (elForm) elForm.action = '/hr/leave/requests/' + reqId + '/review';
    openModal('reviewLeaveModal');
}

export function openAdjustModal(empId, typeId, empName, typeName, beg, earned, used, encashed) {
    const year = window.HrLeaveCreditsConfig?.year || '';
    const elEmpId = document.getElementById('adjEmpId');
    const elTypeId = document.getElementById('adjTypeId');
    const elEmpName = document.getElementById('adjEmpName');
    const elTypeName = document.getElementById('adjTypeName');
    const elBeg = document.getElementById('adjBeg');
    const elEarned = document.getElementById('adjEarned');
    const elUsed = document.getElementById('adjUsed');
    const elEncashed = document.getElementById('adjEncashed');

    if (elEmpId) elEmpId.value = empId;
    if (elTypeId) elTypeId.value = typeId;
    if (elEmpName) elEmpName.innerText = empName;
    if (elTypeName) elTypeName.innerText = typeName + (year ? ' (Year ' + year + ')' : '');
    if (elBeg) elBeg.value = beg;
    if (elEarned) elEarned.value = earned;
    if (elUsed) elUsed.value = used;
    if (elEncashed) elEncashed.value = encashed;
    openModal('adjustModal');
}

Object.assign(window, {
    openModal,
    closeModal,
    openReviewModal,
    openAdjustModal,
});
`;

fs.writeFileSync('resources/css/modules/hr-leave.css', hrLeaveCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-leave.js', hrLeaveJs, 'utf8');

// 1. leave/requests.blade.php
const reqLines = fs.readFileSync('resources/views/hr/leave/requests.blade.php', 'utf8').split('\n');
const reqHtml = reqLines.slice(5, 172).join('\n');
const newReqBlade = `@extends('layouts.app')

@section('title', 'Leave Requests - Leave & Absence Management')

@push('styles')
    @vite(['resources/css/modules/hr-leave.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-leave.js'])
@endpush

@section('content')
${reqHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/leave/requests.blade.php', newReqBlade, 'utf8');

// 2. leave/credits.blade.php
const credLines = fs.readFileSync('resources/views/hr/leave/credits.blade.php', 'utf8').split('\n');
const credHtml = credLines.slice(5, 116).join('\n');
const newCredBlade = `@extends('layouts.app')

@section('title', 'Leave Credits - Leave & Absence Management')

@push('blade-data')
<script>
    window.HrLeaveCreditsConfig = Object.freeze({
        year: @json($year)
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/hr-leave.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-leave.js'])
@endpush

@section('content')
${credHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/leave/credits.blade.php', newCredBlade, 'utf8');

// 3. leave/types.blade.php
const typesLines = fs.readFileSync('resources/views/hr/leave/types.blade.php', 'utf8').split('\n');
const typesHtml = typesLines.slice(5, 98).join('\n');
const newTypesBlade = `@extends('layouts.app')

@section('title', 'Leave Types - Leave & Absence Management')

@push('styles')
    @vite(['resources/css/modules/hr-leave.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-leave.js'])
@endpush

@section('content')
${typesHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/leave/types.blade.php', newTypesBlade, 'utf8');
console.log('Saved hr-leave assets and 3 leave views.');


// ==========================================
// B. PERFORMANCE MODULE
// ==========================================
console.log('Building HR Performance assets...');
const hrPerfCss = `/* Performance Management */
.hr-table th, .hr-table td {
    vertical-align: middle;
}
`;

const hrPerfJs = `/**
 * Performance Management Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open', 'active');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open', 'active');
}

Object.assign(window, {
    openModal,
    closeModal,
});
`;

fs.writeFileSync('resources/css/modules/hr-performance.css', hrPerfCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-performance.js', hrPerfJs, 'utf8');

// 4. performance/evaluations.blade.php
const evalLines = fs.readFileSync('resources/views/hr/performance/evaluations.blade.php', 'utf8').split('\n');
const evalHtml = evalLines.slice(5, 166).join('\n');
const newEvalBlade = `@extends('layouts.app')

@section('title', 'Staff Evaluations - Performance Management')

@push('styles')
    @vite(['resources/css/modules/hr-performance.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-performance.js'])
@endpush

@section('content')
${evalHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/performance/evaluations.blade.php', newEvalBlade, 'utf8');

// 5. performance/periods.blade.php
const perfPeriodsLines = fs.readFileSync('resources/views/hr/performance/periods.blade.php', 'utf8').split('\n');
const perfPeriodsHtml = perfPeriodsLines.slice(5, 88).join('\n');
const newPerfPeriodsBlade = `@extends('layouts.app')

@section('title', 'Evaluation Periods - Performance Management')

@push('styles')
    @vite(['resources/css/modules/hr-performance.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-performance.js'])
@endpush

@section('content')
${perfPeriodsHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/performance/periods.blade.php', newPerfPeriodsBlade, 'utf8');

// 6. performance/criteria.blade.php
const critLines = fs.readFileSync('resources/views/hr/performance/criteria.blade.php', 'utf8').split('\n');
const critHtml = critLines.slice(5, 80).join('\n');
const newCritBlade = `@extends('layouts.app')

@section('title', 'Evaluation Criteria - Performance Management')

@push('styles')
    @vite(['resources/css/modules/hr-performance.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-performance.js'])
@endpush

@section('content')
${critHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/performance/criteria.blade.php', newCritBlade, 'utf8');
console.log('Saved hr-performance assets and 3 performance views.');


// ==========================================
// C. TRAINING MODULE
// ==========================================
console.log('Building HR Training assets...');
const hrTrainCss = `/* Training & Development */
.hr-table th, .hr-table td {
    vertical-align: middle;
}
`;

const hrTrainJs = `/**
 * Training & Development Module
 */

export function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.add('open', 'active');
}

export function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.classList.remove('open', 'active');
}

Object.assign(window, {
    openModal,
    closeModal,
});
`;

fs.writeFileSync('resources/css/modules/hr-training.css', hrTrainCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-training.js', hrTrainJs, 'utf8');

// 7. training/programs.blade.php
const progLines = fs.readFileSync('resources/views/hr/training/programs.blade.php', 'utf8').split('\n');
const progHtml = progLines.slice(5, 170).join('\n');
const newProgBlade = `@extends('layouts.app')

@section('title', 'Training Programs - Restaurant Development')

@push('styles')
    @vite(['resources/css/modules/hr-training.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-training.js'])
@endpush

@section('content')
${progHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/training/programs.blade.php', newProgBlade, 'utf8');

// 8. training/records.blade.php
const recLines = fs.readFileSync('resources/views/hr/training/records.blade.php', 'utf8').split('\n');
const recHtml = recLines.slice(5, 153).join('\n');
const newRecBlade = `@extends('layouts.app')

@section('title', 'Training Records - HR Operations')

@push('styles')
    @vite(['resources/css/modules/hr-training.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-training.js'])
@endpush

@section('content')
${recHtml}
@endsection
`;
fs.writeFileSync('resources/views/hr/training/records.blade.php', newRecBlade, 'utf8');
console.log('Saved hr-training assets and 2 training views.');

console.log('ALL 8 SATELLITE HR VIEWS COMPLETED SUCCESSFULLY!');

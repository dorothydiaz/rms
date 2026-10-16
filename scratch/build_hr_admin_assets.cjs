/**
 * build_hr_admin_assets.cjs
 * Modularizes HR Admin & Dashboard views:
 * 1. hr/admin/roles.blade.php
 * 2. hr/dashboard.blade.php
 * 3. hr/admin/users.blade.php
 * 4. hr/admin/audit-logs.blade.php
 */

const fs = require('fs');
const path = require('path');

// ==========================================
// 1. admin/roles.blade.php
// ==========================================
console.log('Processing admin/roles.blade.php...');
const rolesPath = 'resources/views/hr/admin/roles.blade.php';
const rolesRaw = fs.readFileSync(rolesPath, 'utf8');
const rolesLines = rolesRaw.split('\n');

// Style is lines 611 to 662 (0-indexed: 610 to 662)
const rolesCss = `/* Roles & Permissions Management */
${rolesLines.slice(610, 662).join('\n').trim()}
`;

// Script is lines 666 to 1129 (0-indexed: 665 to 1129)
let rolesJsRaw = rolesLines.slice(665, 1129).join('\n');

// Replace rolePermissionsMap block:
// const rolePermissionsMap = { ... };
const permMapRegex = /const rolePermissionsMap = \{[\s\S]*?\};/;
rolesJsRaw = rolesJsRaw.replace(permMapRegex, 'const rolePermissionsMap = window.HrRolesConfig?.rolePermissionsMap || {};');

const rolesJs = `/**
 * Roles & Permissions Management Module
 */

${rolesJsRaw}

// Global window exposure
Object.assign(window, {
    openModal,
    closeModal,
    switchSubTab,
    toggleRoleCardAll,
    toggleNewRolePerms,
    autoGenerateSlug,
    filterEmployeeTable,
    resetEmployeeFilters,
    openEmployeePermissionsFromBtn,
    openEmployeePermissionsModal,
    updateModalCounts,
    checkAllModalPermissions,
    applyRoleDefaultsToModal,
    filterModalPermissions,
    applyRolesSortFromSelect,
    sortEmployeesTable,
});
`;

// HTML content is lines 6 to 608 (0-indexed: 5 to 608)
const rolesHtml = rolesLines.slice(5, 608).join('\n');

const newRolesBlade = `@extends('layouts.app')

@section('title', 'Roles & Permissions - Administration')

@push('blade-data')
<script>
    window.HrRolesConfig = Object.freeze({
        rolePermissionsMap: {
            @foreach($roles as $r)
                "{{ $r->slug }}": @json($r->permissions->pluck('id')->toArray()),
            @endforeach
        }
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/hr-roles.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-roles.js'])
@endpush

@section('content')
${rolesHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-roles.css', rolesCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-roles.js', rolesJs, 'utf8');
fs.writeFileSync(rolesPath, newRolesBlade, 'utf8');
console.log('Saved admin/roles.blade.php, hr-roles.css, hr-roles.js');


// ==========================================
// 2. dashboard.blade.php
// ==========================================
console.log('Processing dashboard.blade.php...');
const dashboardPath = 'resources/views/hr/dashboard.blade.php';
const dashboardRaw = fs.readFileSync(dashboardPath, 'utf8');
const dashboardLines = dashboardRaw.split('\n');

const dashboardCss = `/* HR Dashboard Metrics & Charts */
.hr-stat-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.hr-stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.06);
}
`;

// Script is lines 620 to 854 (0-indexed: 619 to 854)
let dashboardJsRaw = dashboardLines.slice(619, 854).join('\n');

// Replace blade json_encode with window.HrDashboardConfig:
dashboardJsRaw = dashboardJsRaw
    .replace('{!! json_encode($past7Days) !!}', '(window.HrDashboardConfig?.past7Days || [])')
    .replace('{!! json_encode($attSummaryPresent) !!}', '(window.HrDashboardConfig?.attSummaryPresent || [])')
    .replace('{!! json_encode($attSummaryLate) !!}', '(window.HrDashboardConfig?.attSummaryLate || [])')
    .replace('{!! json_encode($attSummaryAbsent) !!}', '(window.HrDashboardConfig?.attSummaryAbsent || [])')
    .replace("{!! json_encode(array_column($deptBreakdown, 'name')) !!}", '(window.HrDashboardConfig?.deptLabels || [])')
    .replace("{!! json_encode(array_column($deptBreakdown, 'count')) !!}", '(window.HrDashboardConfig?.deptCounts || [])')
    .replace("{!! json_encode(array_column($deptBreakdown, 'color')) !!}", '(window.HrDashboardConfig?.deptColors || [])')
    .replace('{!! json_encode($payrollSummary) !!}', '(window.HrDashboardConfig?.payrollData || [])');

const dashboardJs = `/**
 * HR Dashboard Analytics & Visualizations Module
 */

${dashboardJsRaw}
`;

// HTML content is lines 6 to 615 (0-indexed: 5 to 615)
const dashboardHtml = dashboardLines.slice(5, 615).join('\n');

const newDashboardBlade = `@extends('layouts.app')

@section('title', 'HR Dashboard - Restaurant Management System')

@push('blade-data')
<script>
    window.HrDashboardConfig = Object.freeze({
        past7Days: @json($past7Days),
        attSummaryPresent: @json($attSummaryPresent),
        attSummaryLate: @json($attSummaryLate),
        attSummaryAbsent: @json($attSummaryAbsent),
        deptLabels: @json(array_column($deptBreakdown, 'name')),
        deptCounts: @json(array_column($deptBreakdown, 'count')),
        deptColors: @json(array_column($deptBreakdown, 'color')),
        payrollData: @json($payrollSummary)
    });
</script>
@endpush

@push('styles')
    @vite(['resources/css/modules/hr-dashboard.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-dashboard.js'])
@endpush

@section('content')
${dashboardHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-dashboard.css', dashboardCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-dashboard.js', dashboardJs, 'utf8');
fs.writeFileSync(dashboardPath, newDashboardBlade, 'utf8');
console.log('Saved dashboard.blade.php, hr-dashboard.css, hr-dashboard.js');


// ==========================================
// 3. admin/users.blade.php
// ==========================================
console.log('Processing admin/users.blade.php...');
const usersPath = 'resources/views/hr/admin/users.blade.php';
const usersRaw = fs.readFileSync(usersPath, 'utf8');
const usersLines = usersRaw.split('\n');

const usersCss = `/* User Accounts Administration */
.users-table th, .users-table td {
    vertical-align: middle;
}
`;

// Script is lines 309 to 380 (0-indexed: 308 to 380)
const usersJsRaw = usersLines.slice(308, 380).join('\n');

const usersJs = `/**
 * User Accounts Administration Module
 */

${usersJsRaw}

Object.assign(window, {
    openModal,
    closeModal,
    usersGetPerPage,
    usersChangePerPage,
    usersGoToPage,
    renderUsersPage,
    refreshUsersPage,
    editUser,
    resetPassword,
});
`;

// HTML content is lines 6 to 305 (0-indexed: 5 to 305)
const usersHtml = usersLines.slice(5, 305).join('\n');

const newUsersBlade = `@extends('layouts.app')

@section('title', 'User Accounts - Administration')

@push('styles')
    @vite(['resources/css/modules/hr-users.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-users.js'])
@endpush

@section('content')
${usersHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-users.css', usersCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-users.js', usersJs, 'utf8');
fs.writeFileSync(usersPath, newUsersBlade, 'utf8');
console.log('Saved admin/users.blade.php, hr-users.css, hr-users.js');


// ==========================================
// 4. admin/audit-logs.blade.php
// ==========================================
console.log('Processing admin/audit-logs.blade.php...');
const auditLogsPath = 'resources/views/hr/admin/audit-logs.blade.php';
const auditLogsRaw = fs.readFileSync(auditLogsPath, 'utf8');
const auditLogsLines = auditLogsRaw.split('\n');

const auditLogsCss = `/* Audit Logs & Security Monitoring */
.logs-table th, .logs-table td {
    vertical-align: middle;
}
`;

// Script is lines 128 to 177 (0-indexed: 127 to 177)
const auditLogsJsRaw = auditLogsLines.slice(127, 177).join('\n');

const auditLogsJs = `/**
 * Audit Logs & Security Monitoring Module
 */

${auditLogsJsRaw}

Object.assign(window, {
    logsGetPerPage,
    logsChangePerPage,
    logsGoToPage,
    renderLogsPage,
    refreshLogsPage,
});
`;

// HTML content is lines 6 to 124 (0-indexed: 5 to 124)
const auditLogsHtml = auditLogsLines.slice(5, 124).join('\n');

const newAuditLogsBlade = `@extends('layouts.app')

@section('title', 'Audit Logs - System Security')

@push('styles')
    @vite(['resources/css/modules/hr-audit-logs.css'])
@endpush

@push('scripts')
    @vite(['resources/js/modules/hr-audit-logs.js'])
@endpush

@section('content')
${auditLogsHtml}
@endsection
`;

fs.writeFileSync('resources/css/modules/hr-audit-logs.css', auditLogsCss, 'utf8');
fs.writeFileSync('resources/js/modules/hr-audit-logs.js', auditLogsJs, 'utf8');
fs.writeFileSync(auditLogsPath, newAuditLogsBlade, 'utf8');
console.log('Saved admin/audit-logs.blade.php, hr-audit-logs.css, hr-audit-logs.js');

console.log('ALL 4 HR ADMIN & DASHBOARD MODULES EXTRACTED SUCCESSFULLY!');

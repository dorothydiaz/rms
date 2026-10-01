@props([
    'parent' => null,
    'actions' => null,
])

@php
    $routeName = request()->route() ? request()->route()->getName() : '';

    // Auto-detect parent if not explicitly passed
    if (!$parent) {
        if (request()->routeIs('hr.people.*', 'hr.employee')) $parent = 'employee-management';
        elseif (request()->routeIs('hr.admin.*', 'hr.users-auth')) $parent = 'system-administration';
        elseif (request()->routeIs('hr.attendance.*', 'hr.attendance-checkin', 'hr.attendance-schedule')) $parent = 'time-attendance';
        elseif (request()->routeIs('hr.leave.*', 'hr.employee-leave')) $parent = 'leave-absences';
        elseif (request()->routeIs('hr.payroll.*')) $parent = 'payroll';
        elseif (request()->routeIs('hr.recruitment.*')) $parent = 'talent-acquisition';
        elseif (request()->routeIs('hr.performance.*')) $parent = 'performance-management';
        elseif (request()->routeIs('hr.training.*')) $parent = 'learning-development';
    }

    $parentsConfig = [
        'employee-management' => [
            'title' => 'Employee Management',
            'subtitle' => 'Manage employee records, employment information, 201 files, and workforce details.',
            'icon' => 'ph-users',
            'tabs' => [
                ['name' => 'Employee Management', 'route' => 'hr.people.employees', 'icon' => 'ph-users', 'active' => ['hr.people.employees*', 'hr.employee']],
                [
                    'name' => 'Organization',
                    'route' => 'hr.people.departments',
                    'icon' => 'ph-tree-structure',
                    'active' => ['hr.people.departments*', 'hr.people.positions*', 'hr.people.branches*', 'hr.people.companies*', 'hr.people.organization*'],
                    'subtabs' => [
                        ['name' => 'Departments', 'route' => 'hr.people.departments', 'icon' => 'ph-tree-structure', 'active' => ['hr.people.departments*']],
                        ['name' => 'Positions', 'route' => 'hr.people.positions', 'icon' => 'ph-identification-card', 'active' => ['hr.people.positions*']],
                        ['name' => 'Branches', 'route' => 'hr.people.branches', 'icon' => 'ph-storefront', 'active' => ['hr.people.branches*']],
                        ['name' => 'Company / Agency', 'route' => 'hr.people.companies', 'icon' => 'ph-buildings', 'active' => ['hr.people.companies*']],
                    ],
                ],
            ],
        ],
        'system-administration' => [
            'title' => 'System Administration',
            'subtitle' => 'User account access, system roles, granular permissions, settings, and audit logs',
            'icon' => 'ph-shield-check',
            'tabs' => [
                ['name' => 'User Management', 'route' => 'hr.admin.users', 'icon' => 'ph-users-three', 'active' => ['hr.admin.users*', 'hr.users-auth']],
                ['name' => 'Role & Permission', 'route' => 'hr.admin.roles', 'icon' => 'ph-key', 'active' => ['hr.admin.roles*']],
                ['name' => 'System Settings', 'route' => 'hr.admin.settings', 'icon' => 'ph-gear', 'active' => ['hr.admin.settings*']],
                ['name' => 'Audit Logs', 'route' => 'hr.admin.audit-logs', 'icon' => 'ph-clipboard-text', 'active' => ['hr.admin.audit-logs*', 'hr.admin.audit_logs*']],
            ],
        ],
        'time-attendance' => [
            'title' => 'Time & Attendance',
            'subtitle' => 'Track staff timekeeping logs, schedules, overtime, and manual time entries',
            'icon' => 'ph-clock',
            'tabs' => [
                ['name' => 'Time-Keeping', 'route' => 'hr.attendance.timekeeping', 'icon' => 'ph-fingerprint', 'active' => ['hr.attendance.timekeeping*', 'hr.attendance-checkin']],
                ['name' => 'Schedules', 'route' => 'hr.attendance.schedules', 'icon' => 'ph-calendar', 'active' => ['hr.attendance.schedules*', 'hr.attendance-schedule']],
                ['name' => 'Overtime', 'route' => 'hr.attendance.overtime', 'icon' => 'ph-clock-countdown', 'active' => ['hr.attendance.overtime*']],
                ['name' => 'Undertime', 'route' => 'hr.attendance.undertime', 'icon' => 'ph-timer', 'active' => ['hr.attendance.undertime*']],
                ['name' => 'Manual Time Entries', 'route' => 'hr.attendance.corrections', 'icon' => 'ph-pencil-line', 'active' => ['hr.attendance.corrections*', 'hr.attendance.manual-entries*']],
            ],
        ],
        'leave-absences' => [
            'title' => 'Leave & Absences',
            'subtitle' => 'Review employee leave requests, configure leave categories, and monitor leave credits',
            'icon' => 'ph-calendar-blank',
            'tabs' => [
                ['name' => 'Leave Requests', 'route' => 'hr.leave.requests', 'icon' => 'ph-calendar-plus', 'active' => ['hr.leave.requests*', 'hr.employee-leave']],
                ['name' => 'Leave Types', 'route' => 'hr.leave.types', 'icon' => 'ph-list-checks', 'active' => ['hr.leave.types*']],
                ['name' => 'Leave Credits', 'route' => 'hr.leave.credits', 'icon' => 'ph-coins', 'active' => ['hr.leave.credits*']],
            ],
        ],
        'payroll' => [
            'title' => 'Payroll',
            'subtitle' => 'Process Philippine payroll cut-offs, generate registers, review payslips, and statutory rules',
            'icon' => 'ph-wallet',
            'tabs' => [
                ['name' => 'Payroll Periods', 'route' => 'hr.payroll.periods', 'icon' => 'ph-calendar-dots', 'active' => ['hr.payroll.periods*']],
                ['name' => 'Process Payroll', 'route' => 'hr.payroll.process', 'icon' => 'ph-calculator', 'active' => ['hr.payroll.process*']],
                ['name' => 'Payroll Register', 'route' => 'hr.payroll.register', 'icon' => 'ph-receipt', 'active' => ['hr.payroll.register*']],
                ['name' => 'Payslips', 'route' => 'hr.payroll.payslips', 'icon' => 'ph-file-text', 'active' => ['hr.payroll.payslips*']],
                ['name' => 'Statutory Rules', 'route' => 'hr.payroll.statutory-rules', 'icon' => 'ph-sliders-horizontal', 'active' => ['hr.payroll.statutory-rules*']],
            ],
        ],
        'talent-acquisition' => [
            'title' => 'Talent Acquisition',
            'subtitle' => 'Manage job vacancies, track candidate applications, and schedule interview evaluations',
            'icon' => 'ph-user-plus',
            'tabs' => [
                ['name' => 'Job Vacancies', 'route' => 'hr.recruitment.vacancies', 'icon' => 'ph-briefcase', 'active' => ['hr.recruitment.vacancies*']],
                ['name' => 'Applicants', 'route' => 'hr.recruitment.applicants', 'icon' => 'ph-user-focus', 'active' => ['hr.recruitment.applicants*']],
                ['name' => 'Interviews', 'route' => 'hr.recruitment.interviews', 'icon' => 'ph-chat-circle-dots', 'active' => ['hr.recruitment.interviews*']],
            ],
        ],
        'performance-management' => [
            'title' => 'Performance Management',
            'subtitle' => 'Conduct staff appraisals, configure evaluation criteria, and monitor evaluation periods',
            'icon' => 'ph-star',
            'tabs' => [
                ['name' => 'Evaluation Periods', 'route' => 'hr.performance.periods', 'icon' => 'ph-calendar-check', 'active' => ['hr.performance.periods*']],
                ['name' => 'Criteria', 'route' => 'hr.performance.criteria', 'icon' => 'ph-list-numbers', 'active' => ['hr.performance.criteria*']],
                ['name' => 'Evaluations', 'route' => 'hr.performance.evaluations', 'icon' => 'ph-chart-line-up', 'active' => ['hr.performance.evaluations*']],
            ],
        ],
        'learning-development' => [
            'title' => 'Learning & Development',
            'subtitle' => 'Manage staff training programs, skill certifications, and employee training records',
            'icon' => 'ph-graduation-cap',
            'tabs' => [
                ['name' => 'Training Programs', 'route' => 'hr.training.programs', 'icon' => 'ph-books', 'active' => ['hr.training.programs*']],
                ['name' => 'Training Records', 'route' => 'hr.training.records', 'icon' => 'ph-certificate', 'active' => ['hr.training.records*']],
            ],
        ],
    ];

    $cfg = $parentsConfig[$parent] ?? null;
@endphp

@if($cfg)
<div class="hr-parent-header">
    <div class="hr-parent-title-row">
        <div>
            <h1 class="hr-parent-title">
                <i class="ph {{ $cfg['icon'] }}"></i>
                <span>{{ $cfg['title'] }}</span>
            </h1>
            <p class="hr-parent-subtitle">{{ $cfg['subtitle'] }}</p>
        </div>
        @if(isset($actions))
            <div class="hr-page-actions">
                {{ $actions }}
            </div>
        @endif
    </div>

    <!-- Tab Navigation -->
    <div class="hr-tabs-wrapper">
        @foreach($cfg['tabs'] as $tab)
            @php
                $isActive = false;
                foreach ((array)$tab['active'] as $pattern) {
                    if (request()->routeIs($pattern)) {
                        $isActive = true;
                        break;
                    }
                }
                $hasSubtabs = !empty($tab['subtabs']);
            @endphp

            @if($hasSubtabs)
                <div class="hr-tab-dropdown-wrap" onmouseenter="openTabDropdown(this)" onmouseleave="closeTabDropdown(this)">
                    <div class="hr-tab-item hr-tab-dropdown-trigger {{ $isActive ? 'active' : '' }}">
                        <a href="{{ route($tab['route']) }}" class="hr-tab-link">
                            <i class="ph {{ $tab['icon'] }}"></i>
                            <span>{{ $tab['name'] }}</span>
                        </a>
                        <button type="button" class="hr-tab-caret-btn" onclick="toggleHrTabDropdown(event, this)" aria-label="Toggle {{ $tab['name'] }} options" title="Switch organization section">
                            <i class="ph ph-caret-down hr-tab-caret"></i>
                        </button>
                    </div>

                    <div class="hr-tab-dropdown-menu">
                        @foreach($tab['subtabs'] as $subtab)
                            @php
                                $isSubActive = false;
                                foreach ((array)$subtab['active'] as $pattern) {
                                    if (request()->routeIs($pattern)) {
                                        $isSubActive = true;
                                        break;
                                    }
                                }
                            @endphp
                            <a href="{{ route($subtab['route']) }}" class="hr-tab-dropdown-item {{ $isSubActive ? 'active' : '' }}">
                                <span class="hr-tab-dropdown-icon-box">
                                    <i class="ph {{ $subtab['icon'] }}"></i>
                                </span>
                                <span class="hr-tab-dropdown-label">{{ $subtab['name'] }}</span>
                                @if($isSubActive)
                                    <i class="ph ph-check hr-tab-dropdown-check"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <a href="{{ route($tab['route']) }}" class="hr-tab-item {{ $isActive ? 'active' : '' }}">
                    <i class="ph {{ $tab['icon'] }}"></i>
                    <span>{{ $tab['name'] }}</span>
                </a>
            @endif
        @endforeach
    </div>
</div>

<script>
let hrTabDropdownTimer = null;
function openTabDropdown(wrap) {
    if (hrTabDropdownTimer) clearTimeout(hrTabDropdownTimer);
    if (!wrap) return;
    document.querySelectorAll('.hr-tab-dropdown-wrap.open').forEach(w => {
        if (w !== wrap) w.classList.remove('open');
    });
    wrap.classList.add('open');
}
function closeTabDropdown(wrap) {
    if (hrTabDropdownTimer) clearTimeout(hrTabDropdownTimer);
    hrTabDropdownTimer = setTimeout(() => {
        if (wrap) wrap.classList.remove('open');
    }, 150);
}
function toggleHrTabDropdown(e, btn) {
    e.preventDefault();
    e.stopPropagation();
    const wrap = btn.closest('.hr-tab-dropdown-wrap');
    if (!wrap) return;
    const isOpen = wrap.classList.contains('open');
    document.querySelectorAll('.hr-tab-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
    if (!isOpen) {
        wrap.classList.add('open');
    }
}
document.addEventListener('click', function(e) {
    if (!e.target.closest('.hr-tab-dropdown-wrap')) {
        document.querySelectorAll('.hr-tab-dropdown-wrap.open').forEach(w => w.classList.remove('open'));
    }
});
</script>
@endif

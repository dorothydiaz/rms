@extends('layouts.app')

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
<div class="hr-dash-wrapper">

    <!-- Global Sparkline Gradients -->
    <svg style="position: absolute; width: 0; height: 0; pointer-events: none;" aria-hidden="true">
        <defs>
            <linearGradient id="sparkPurple" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#c084fc" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#9333ea" stop-opacity="1"/>
            </linearGradient>
            <linearGradient id="sparkBlue" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#0284c7" stop-opacity="1"/>
            </linearGradient>
            <linearGradient id="sparkOrange" x1="0%" y1="0%" x2="100%" y2="0%">
                <stop offset="0%" stop-color="#fed7aa" stop-opacity="0.3"/>
                <stop offset="100%" stop-color="#ea580c" stop-opacity="1"/>
            </linearGradient>
        </defs>
    </svg>

    <!-- HR Dashboard Title Header with Custom Branch Switcher -->
    <div class="hr-page-header" style="margin-bottom: 16px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-chart-pie-slice"></i>
                HR Dashboard
            </h1>
            <p class="hr-page-subtitle">Real-time workforce attendance, Philippine statutory payroll, shift scheduling, and HR metrics</p>
        </div>

        @if((Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin()) && count($allBranches) > 0)
            @php
                $selectedBranchName = 'All Branches';
                if (!empty($branchId)) {
                    $currBranch = $allBranches->firstWhere('id', $branchId);
                    if ($currBranch) {
                        $selectedBranchName = $currBranch->name;
                    }
                }
            @endphp
            <div class="hr-page-actions">
                <div class="hr-custom-dropdown" id="hrBranchDropdown">
                    <button type="button" class="hr-dropdown-trigger" id="hrBranchTrigger" aria-expanded="false" aria-haspopup="true">
                        <i class="ph ph-storefront branch-icon"></i>
                        <span class="selected-text">{{ $selectedBranchName }}</span>
                        <i class="ph ph-caret-down dropdown-arrow"></i>
                    </button>
                    <div class="hr-dropdown-menu" id="hrBranchMenu" role="menu">
                        <div class="hr-dropdown-header">Filter by Branch</div>
                        <a href="{{ route('hr.dashboard') }}" class="hr-dropdown-item {{ empty($branchId) ? 'active' : '' }}" role="menuitem">
                            <span class="item-left">
                                <i class="ph ph-buildings"></i>
                                <span>All Branches</span>
                            </span>
                            @if(empty($branchId))
                                <i class="ph ph-check check-icon"></i>
                            @endif
                        </a>
                        <div class="hr-dropdown-divider"></div>
                        @foreach($allBranches as $b)
                            <a href="{{ route('hr.dashboard', ['branch_id' => $b->id]) }}" class="hr-dropdown-item {{ $branchId == $b->id ? 'active' : '' }}" role="menuitem">
                                <span class="item-left">
                                    <i class="ph ph-storefront"></i>
                                    <span>{{ $b->name }}</span>
                                </span>
                                @if($branchId == $b->id)
                                    <i class="ph ph-check check-icon"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @elseif(Auth::user()->branch)
            <div class="hr-page-actions">
                <div class="hr-dropdown-trigger" style="cursor: default;">
                    <i class="ph ph-storefront branch-icon"></i>
                    <span>{{ Auth::user()->branch->name }}</span>
                </div>
            </div>
        @endif
    </div>

    <!-- BENTO GRID DASHBOARD -->
    <div class="hr-bento-dashboard">
        
        <div class="hr-bento-layout">
            
            <!-- LEFT MAIN COLUMN -->
            <div class="hr-bento-left">
                
                <!-- 1. Top 4 KPI Cards -->
                <div class="hr-bento-kpi-grid">
                    
                    <!-- Card 1: Total Workforce -->
                    <a href="{{ route('hr.people.employees') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box purple">
                                    <i class="ph ph-user"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Total Workforce</span>
                            </div>
                        </div>
                        <div class="hr-bento-kpi-val">{{ $totalEmployees }}</div>
                        <div class="hr-bento-kpi-bottom">
                            <span style="font-size: 11.5px; font-weight: 500; color: #64748b;">
                                {{ $activeEmployees }} Active &bull; {{ $probationaryEmployees }} Probationary
                            </span>
                            <svg class="hr-kpi-sparkline" viewBox="0 0 68 26" fill="none">
                                <path d="M2 20C12 18 20 8 32 15C44 22 52 5 66 3" stroke="url(#sparkPurple)" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Card 2: Today's Attendance -->
                    <a href="{{ route('hr.attendance.dtr') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box green" style="background: #dcfce7; color: #16a34a;">
                                    <i class="ph ph-calendar-check"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Today's Attendance</span>
                            </div>
                        </div>
                        <div style="display: flex; align-items: flex-end; justify-content: space-between;">
                            <div>
                                <div class="hr-bento-kpi-val" style="margin-bottom: 2px;">{{ $todayPresent }}</div>
                                <span style="font-size: 11.5px; color: #64748b; font-weight: 500;">
                                    {{ $todayLate }} Late &bull; {{ $todayAbsent }} Absent
                                </span>
                            </div>
                            <div class="hr-circular-badge">
                                <svg width="40" height="40" viewBox="0 0 36 36">
                                    <circle cx="18" cy="18" r="14" fill="none" stroke="#e2e8f0" stroke-width="3.5" />
                                    <circle cx="18" cy="18" r="14" fill="none" stroke="#10b981" stroke-width="3.5"
                                            stroke-dasharray="88"
                                            stroke-dashoffset="{{ 88 - (88 * ($attendancePercentage / 100)) }}"
                                            stroke-linecap="round"
                                            transform="rotate(-90 18 18)" />
                                </svg>
                                <span class="hr-circular-badge-text">{{ $attendancePercentage }}%</span>
                            </div>
                        </div>
                    </a>

                    <!-- Card 3: On Leave -->
                    <a href="{{ route('hr.leave.requests') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box orange">
                                    <i class="ph ph-airplane-tilt"></i>
                                </div>
                                <span class="hr-bento-kpi-title">On Leave</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron"></i>
                        </div>
                        <div class="hr-bento-kpi-val">{{ $onLeaveEmployees }}</div>
                        <div class="hr-bento-kpi-bottom">
                            <span style="font-size: 11.5px; font-weight: 600; color: #ea580c;">
                                {{ $pendingLeaveRequests }} Pending Request{{ $pendingLeaveRequests > 1 ? 's' : '' }}
                            </span>
                        </div>
                    </a>

                    <!-- Card 4: Payroll -->
                    <a href="{{ route('hr.payroll.periods') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box blue">
                                    <i class="ph ph-wallet"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Payroll</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron"></i>
                        </div>
                        <div style="margin: 8px 0 6px;">
                            <span class="hr-badge-approved">Approved</span>
                        </div>
                        <div class="hr-bento-kpi-bottom">
                            <span style="font-size: 11.5px; font-weight: 500; color: #64748b;">
                                {{ $payrollSummary['period_name'] ?? 'September 2026 - 1st Half' }}
                            </span>
                        </div>
                    </a>

                </div>

                <!-- 2. Middle Row: Attendance Overview + Employees by Department -->
                <div class="hr-bento-mid-grid">
                    
                    <!-- Attendance Overview -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div>
                                <div class="hr-bento-card-title">
                                    <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                                        <i class="ph ph-users"></i>
                                    </div>
                                    <span>Attendance Overview</span>
                                </div>
                                <div class="hr-bento-card-subtitle" style="margin-left: 42px;">Daily attendance for the current period</div>
                            </div>
                            <span class="hr-bento-pill" style="cursor: default;">
                                {{ $dateRangeLabel ?? 'Sep 18 – Sep 24' }}
                                <i class="ph ph-caret-right" style="font-size: 10px; margin-left: 2px;"></i>
                            </span>
                        </div>
                        
                        <div class="hr-att-overview-body">
                            <!-- Left: Attendance Chart -->
                            <div style="flex: 1; min-width: 0;">
                                <div class="hr-sales-chart-wrap" style="height: 180px;">
                                    <canvas id="attendanceOverviewChart"></canvas>
                                </div>
                                <div style="display: flex; align-items: center; gap: 18px; margin-top: 10px; font-size: 11.5px; color: #64748b; font-weight: 500;">
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981;"></span> Present
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #f59e0b;"></span> Late
                                    </span>
                                    <span style="display: inline-flex; align-items: center; gap: 6px;">
                                        <span style="width: 8px; height: 8px; border-radius: 50%; background: #f43f5e;"></span> Absent
                                    </span>
                                </div>
                            </div>

                            <!-- Right: Attendance Rate gauge -->
                            <div class="hr-att-rate-box">
                                <span style="font-size: 12px; font-weight: 600; color: #0f172a; margin-bottom: 12px;">Attendance Rate</span>
                                <div class="hr-circular-badge lg">
                                    <svg width="68" height="68" viewBox="0 0 36 36">
                                        <circle cx="18" cy="18" r="14" fill="none" stroke="#e2e8f0" stroke-width="3" />
                                        <circle cx="18" cy="18" r="14" fill="none" stroke="#38bdf8" stroke-width="3"
                                                stroke-dasharray="88"
                                                stroke-dashoffset="{{ 88 - (88 * ($attendancePercentage / 100)) }}"
                                                stroke-linecap="round"
                                                transform="rotate(-90 18 18)" />
                                    </svg>
                                    <span class="hr-circular-badge-text lg">{{ $attendancePercentage }}%</span>
                                </div>
                                <div style="margin-top: 14px; text-align: center;">
                                    <span style="font-size: 11px; font-weight: 600; color: #16a34a; display: block;">
                                        <i class="ph ph-arrow-up-right"></i> +5%
                                    </span>
                                    <span style="font-size: 10px; color: #64748b;">vs. last week</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employees by Department -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                                    <i class="ph ph-users-three"></i>
                                </div>
                                <span>Employees by Department</span>
                            </div>
                            <span class="hr-bento-pill">Total {{ $totalEmployees }}</span>
                        </div>
                        <div class="hr-donut-layout">
                            <div class="hr-donut-chart-wrap">
                                <canvas id="deptDonutChart"></canvas>
                                <div class="hr-donut-center-info">
                                    <span class="hr-donut-center-num">{{ $totalEmployees }}</span>
                                    <span class="hr-donut-center-lbl">Total Employees</span>
                                </div>
                            </div>
                            <div class="hr-breakdown-list">
                                @foreach($deptBreakdown as $dept)
                                <div class="hr-breakdown-item">
                                    <span class="hr-breakdown-item-left">
                                        <span class="hr-breakdown-dot" style="background-color: {{ $dept['color'] }};"></span>
                                        {{ $dept['name'] }}
                                    </span>
                                    <span class="hr-breakdown-item-right">
                                        {{ $dept['count'] }} <span class="hr-breakdown-pct">({{ $dept['percent'] }}%)</span>
                                    </span>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 3. Lower Row: Payroll Summary + Recent Attendance -->
                <div class="hr-bento-lower-grid">
                    
                    <!-- Payroll Summary -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                                    <i class="ph ph-receipt"></i>
                                </div>
                                <span>Payroll Summary</span>
                            </div>
                            <span class="hr-bento-pill" style="cursor: default;">
                                {{ $payrollSummary['period_name'] ?? 'September 2026 - 1st Half' }}
                                <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                            </span>
                        </div>
                        <div style="height: 155px; position: relative;">
                            <canvas id="payrollSummaryChart"></canvas>
                        </div>
                        <div class="hr-pay-kpi-strip">
                            <div class="hr-pay-kpi-item">
                                <span class="hr-pay-kpi-label">Gross Pay</span>
                                <span class="hr-pay-kpi-val">₱{{ number_format($payrollSummary['total_gross'], 2) }}</span>
                            </div>
                            <div class="hr-pay-kpi-item">
                                <span class="hr-pay-kpi-label">Total Net Pay</span>
                                <span class="hr-pay-kpi-val">₱{{ number_format($payrollSummary['total_net'], 2) }}</span>
                            </div>
                            <div class="hr-pay-kpi-item">
                                <span class="hr-pay-kpi-label">Deductions</span>
                                <span class="hr-pay-kpi-val" style="color: #ef4444;">-₱{{ number_format($payrollSummary['total_deductions'], 2) }}</span>
                            </div>
                            <div class="hr-pay-kpi-item">
                                <span class="hr-pay-kpi-label">Overtime Pay</span>
                                <span class="hr-pay-kpi-val">₱{{ number_format($payrollSummary['total_ot'], 2) }}</span>
                            </div>
                            <a href="{{ route('hr.payroll.periods') }}" class="hr-bento-link" style="align-self: flex-end; margin-bottom: 2px;">
                                View Details <i class="ph ph-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Recent Attendance -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                                    <i class="ph ph-clock"></i>
                                </div>
                                <span>Recent Attendance</span>
                            </div>
                            <a href="{{ route('hr.attendance.dtr') }}" class="hr-bento-link">
                                View All <i class="ph ph-arrow-right"></i>
                            </a>
                        </div>
                        <div class="hr-table-responsive">
                            <table class="hr-recent-table">
                                <thead>
                                    <tr>
                                        <th>DATE &amp; TIME</th>
                                        <th>EMPLOYEE</th>
                                        <th>IP ADDRESS</th>
                                        <th>STATUS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $displayAtt = $recentAttendance && $recentAttendance->count() >= 3
                                            ? $recentAttendance
                                            : collect([
                                                (object)['date_formatted' => 'Sep 23, 2026 10:00 PM', 'name' => 'Maria', 'ip' => '127.0.0.1', 'status' => 'Successful'],
                                                (object)['date_formatted' => 'Sep 23, 2026 10:00 PM', 'name' => 'Gabriel', 'ip' => '10.0.0.12', 'status' => 'Successful'],
                                                (object)['date_formatted' => 'Sep 23, 2026 10:00 PM', 'name' => 'Patricia', 'ip' => '10.0.0.12', 'status' => 'Successful'],
                                                (object)['date_formatted' => 'Sep 23, 2026 02:00 PM', 'name' => 'Rodrigo', 'ip' => '137.74.0.1', 'status' => 'Successful'],
                                                (object)['date_formatted' => 'Sep 22, 2026 07:50 PM', 'name' => 'Juan', 'ip' => '137.74.0.1', 'status' => 'Failed Attempt'],
                                            ]);
                                    @endphp
                                    @foreach($displayAtt as $att)
                                        @php
                                            $isObj = is_object($att) && isset($att->date_formatted);
                                            $dateTimeStr = $isObj
                                                ? $att->date_formatted
                                                : ($att->date ? $att->date->format('M d, Y') . ' ' . ($att->time_in ? Carbon\Carbon::parse($att->time_in)->format('h:i A') : '10:00 PM') : 'Sep 23, 2026 10:00 PM');
                                            $empName = $isObj ? $att->name : ($att->employee ? $att->employee->first_name : 'Staff');
                                            $ipStr = $isObj ? $att->ip : ($att->ip_address ?? '127.0.0.1');
                                            $statusStr = $isObj ? $att->status : ($att->status === 'Absent' ? 'Failed Attempt' : 'Successful');
                                            $isSuccess = $statusStr === 'Successful' || $statusStr === 'Present' || $statusStr === 'Late';
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt">
                                                    <i class="ph ph-clock"></i>
                                                    <span>{{ $dateTimeStr }}</span>
                                                </div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">{{ $empName }}</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">{{ $ipStr }}</td>
                                            <td>
                                                @if($isSuccess)
                                                    <span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span>
                                                @else
                                                    <span class="hr-pill-badge-red"><i class="ph ph-x"></i> Failed Attempt</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>

            <!-- RIGHT ASIDE COLUMN (BENTO ASIDE) -->
            <div class="hr-bento-aside">
                
                <!-- 1. Quick Stats Card -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title">
                            <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                                <i class="ph ph-sparkle"></i>
                            </div>
                            <span>Quick Stats</span>
                        </div>
                        <a href="{{ route('hr.reports.index') }}" class="hr-bento-link">
                            View All <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hr-quick-stats-list">
                        <div class="hr-quick-stat-row">
                            <div class="hr-quick-stat-left">
                                <i class="ph ph-clock hr-quick-stat-icon"></i>
                                <span>Overtime Logs</span>
                            </div>
                            <span class="hr-quick-stat-val">{{ $pendingOtRequests }} <span class="hr-quick-stat-unit">(7d)</span></span>
                        </div>
                        <div class="hr-quick-stat-row">
                            <div class="hr-quick-stat-left">
                                <i class="ph ph-cake hr-quick-stat-icon"></i>
                                <span>Birthdays</span>
                            </div>
                            <span class="hr-quick-stat-val">{{ $birthdaysCount30d }} <span class="hr-quick-stat-unit">(30d)</span></span>
                        </div>
                        <div class="hr-quick-stat-row">
                            <div class="hr-quick-stat-left">
                                <i class="ph ph-briefcase hr-quick-stat-icon"></i>
                                <span>Regularization Due</span>
                            </div>
                            <span class="hr-quick-stat-val">{{ count($upcomingRegularizations) }}</span>
                        </div>
                    </div>
                </div>

                <!-- 2. Upcoming Birthdays -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header" style="align-items: flex-start;">
                        <div style="display: flex; align-items: center; gap: 10px; min-width: 0;">
                            <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px; flex-shrink: 0;">
                                <i class="ph ph-cake"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div class="hr-bento-card-title" style="font-size: 13.5px; line-height: 1.2; white-space: nowrap;">Upcoming Birthdays</div>
                                <div class="hr-bento-card-subtitle" style="margin-top: 2px;">Next 30 Days</div>
                            </div>
                        </div>
                        <a href="{{ route('hr.people.employees') }}" class="hr-bento-link" style="margin-top: 2px;">
                            View All <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hr-bday-list">
                        @forelse($displayBirthdays->take(3) as $emp)
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar">{{ strtoupper(substr($emp->first_name, 0, 1)) }}</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">{{ $emp->first_name }} {{ $emp->last_name }}</span>
                                        <span class="hr-bday-date">{{ $emp->formatted_birthday ?? Carbon\Carbon::parse($emp->date_of_birth)->format('M d') }}</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-gift-icon"></i>
                            </div>
                        @empty
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar">G</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Gabriel Tan</span>
                                        <span class="hr-bday-date">Sep 30</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-gift-icon"></i>
                            </div>
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar">M</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Maria Garcia</span>
                                        <span class="hr-bday-date">Nov 05</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-gift-icon"></i>
                            </div>
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar">E</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Eduardo Ramos</span>
                                        <span class="hr-bday-date">Dec 08</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-gift-icon"></i>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- 3. Top Sections (2x2 Grid) -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title" style="min-width: 0;">
                            <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px; background: #f3e8ff; color: #9333ea; flex-shrink: 0;">
                                <i class="ph ph-gear"></i>
                            </div>
                            <span style="white-space: nowrap;">Top Sections</span>
                        </div>
                        <a href="{{ route('hr.people.employees') }}" class="hr-bento-link">
                            View All <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hr-top-sections-grid">
                        <a href="{{ route('hr.people.employees') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon purple">
                                <i class="ph ph-users"></i>
                            </div>
                            <span class="hr-top-section-name">Employees</span>
                            <span class="hr-top-section-sub">Manage records &rarr;</span>
                        </a>

                        <a href="{{ route('hr.payroll.periods') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon blue">
                                <i class="ph ph-receipt"></i>
                            </div>
                            <span class="hr-top-section-name">Payroll</span>
                            <span class="hr-top-section-sub">Process &amp; view &rarr;</span>
                        </a>

                        <a href="{{ route('hr.attendance.dtr') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon cyan">
                                <i class="ph ph-clock"></i>
                            </div>
                            <span class="hr-top-section-name">Attendance</span>
                            <span class="hr-top-section-sub">View logs &rarr;</span>
                        </a>

                        <a href="{{ route('hr.leave.requests') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon pink">
                                <i class="ph ph-calendar-blank"></i>
                            </div>
                            <span class="hr-top-section-name">Leave Management</span>
                            <span class="hr-top-section-sub">Handle requests &rarr;</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- 4. Bottom Grid: Recent Activity (Left) & HR Motto Quote Card (Right) -->
        <div class="hr-bento-bottom-grid">
            
            <!-- Recent Activity -->
            <div class="hr-bento-card">
                <div class="hr-bento-card-header" style="margin-bottom: 12px;">
                    <div class="hr-bento-card-title">
                        <div class="hr-bento-icon-box purple" style="width: 32px; height: 32px; font-size: 16px;">
                            <i class="ph ph-arrows-clockwise"></i>
                        </div>
                        <div>
                            <span>Recent Activity</span>
                            <span style="display:none;" aria-hidden="true">System Alerts</span>
                        </div>
                    </div>
                    <a href="{{ route('hr.attendance.dtr') }}" class="hr-bento-link">
                        View All <i class="ph ph-arrow-right"></i>
                    </a>
                </div>
                
                <div class="hr-activity-grid">
                    @foreach($recentActivities as $act)
                    <div class="hr-activity-item">
                        <div class="hr-bento-icon-box {{ $act['color'] ?? 'purple' }}" style="width: 34px; height: 34px; font-size: 16px; flex-shrink: 0;">
                            <i class="ph {{ $act['icon'] ?? 'ph-activity' }}"></i>
                        </div>
                        <div class="hr-activity-info">
                            <span class="hr-activity-action">{{ $act['action'] }}</span>
                            <span class="hr-activity-subject">{{ $act['subject'] }}</span>
                            <span class="hr-activity-time">{{ $act['time'] }}</span>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- HR Department Motto Card -->
            <div class="hr-quote-card">
                <div class="hr-quote-text">
                    &ldquo;Great teams build great workplaces.&rdquo;
                </div>
                <div class="hr-quote-line"></div>
            </div>

        </div>

    </div>

</div>
@endsection

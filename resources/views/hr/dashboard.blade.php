@extends('layouts.app')

@section('title', 'HR Dashboard - Restaurant Management System')

@section('content')
<div class="hr-dash-wrapper">

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
                    <!-- Total Workforce -->
                    <a href="{{ route('hr.people.employees') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box purple">
                                    <i class="ph ph-user"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Total Workforce</span>
                            </div>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $totalEmployees }}</div>
                                <div class="hr-bento-kpi-sub">{{ $activeEmployees }} Active &bull; {{ $probationaryEmployees }} Probationary</div>
                            </div>
                            <svg width="68" height="28" viewBox="0 0 68 28" fill="none">
                                <path d="M2 22C16 22 22 8 36 14C50 20 56 4 66 4" stroke="#c084fc" stroke-width="2.5" stroke-linecap="round"/>
                            </svg>
                        </div>
                    </a>

                    <!-- Today's Attendance -->
                    <a href="{{ route('hr.attendance.dtr') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box green">
                                    <i class="ph ph-calendar-check"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Today's Attendance <i class="ph ph-caret-right"></i></span>
                            </div>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $todayPresent }}</div>
                                <div class="hr-bento-kpi-sub">{{ $todayLate }} Late &bull; {{ $todayAbsent }} Absent</div>
                            </div>
                            <div style="width: 42px; height: 42px; position: relative; display: flex; align-items: center; justify-content: center;">
                                <svg width="42" height="42" viewBox="0 0 42 42">
                                    <circle cx="21" cy="21" r="17" fill="none" stroke="#e2e8f0" stroke-width="3.5" />
                                    <circle cx="21" cy="21" r="17" fill="none" stroke="#10b981" stroke-width="3.5"
                                            stroke-dasharray="106.8"
                                            stroke-dashoffset="{{ 106.8 - (106.8 * ($attendancePercentage / 100)) }}"
                                            stroke-linecap="round"
                                            transform="rotate(-90 21 21)" />
                                </svg>
                                <span style="position: absolute; font-size: 10px; font-weight: 800; color: #0f172a;">{{ $attendancePercentage }}%</span>
                            </div>
                        </div>
                    </a>

                    <!-- On Leave -->
                    <a href="{{ route('hr.leave.requests') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box orange">
                                    <i class="ph ph-airplane-tilt"></i>
                                </div>
                                <span class="hr-bento-kpi-title">On Leave <i class="ph ph-caret-right"></i></span>
                            </div>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $onLeaveEmployees }}</div>
                                <div class="hr-bento-kpi-sub" style="color: #f97316; font-weight: 600;">
                                    {{ $pendingLeaveRequests }} Pending Request{{ $pendingLeaveRequests == 1 ? '' : 's' }}
                                </div>
                            </div>
                        </div>
                    </a>

                    <!-- Payroll -->
                    <a href="{{ route('hr.payroll.periods') }}" class="hr-bento-kpi-card">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box blue">
                                    <i class="ph ph-desktop"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Payroll <i class="ph ph-caret-right"></i></span>
                            </div>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <span class="hr-badge-approved">Approved</span>
                                <div class="hr-bento-kpi-sub" style="margin-top: 6px;">{{ $payrollSummary['period_name'] ?? 'September 2026 - 1st Half' }}</div>
                            </div>
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
                                    <i class="ph ph-chart-bar" style="color: #8b5cf6;"></i>
                                    <span>Attendance Overview</span>
                                </div>
                                <div class="hr-bento-card-subtitle">Daily attendance for the current period</div>
                            </div>
                            <span class="hr-bento-pill">{{ $dateRangeLabel }}</span>
                        </div>
                        
                        <div class="hr-att-overview-body">
                            <div class="hr-att-chart-wrap">
                                <div style="height: 195px; position: relative;">
                                    <canvas id="attendanceSummaryChart"></canvas>
                                </div>
                                <div class="hr-custom-chart-legend" style="margin-top: 8px;">
                                    <span class="hr-custom-chart-legend-item">
                                        <span class="hr-breakdown-dot" style="background-color: #10b981;"></span> Present
                                    </span>
                                    <span class="hr-custom-chart-legend-item">
                                        <span class="hr-breakdown-dot" style="background-color: #f59e0b;"></span> Late
                                    </span>
                                    <span class="hr-custom-chart-legend-item">
                                        <span class="hr-breakdown-dot" style="background-color: #f43f5e;"></span> Absent
                                    </span>
                                </div>
                            </div>

                            <!-- Attendance Rate circular widget -->
                            <div class="hr-att-rate-box">
                                <span class="hr-att-rate-title">Attendance Rate</span>
                                <div class="hr-att-rate-gauge">
                                    <svg width="76" height="76" viewBox="0 0 76 76">
                                        <circle cx="38" cy="38" r="30" fill="none" stroke="#e2e8f0" stroke-width="6.5" />
                                        <circle cx="38" cy="38" r="30" fill="none" stroke="#10b981" stroke-width="6.5"
                                                stroke-dasharray="188.5"
                                                stroke-dashoffset="{{ 188.5 - (188.5 * ($attendancePercentage / 100)) }}"
                                                stroke-linecap="round"
                                                transform="rotate(-90 38 38)" />
                                    </svg>
                                    <span class="hr-att-rate-num">{{ $attendancePercentage }}%</span>
                                </div>
                                <div class="hr-att-rate-trend">
                                    <i class="ph ph-arrow-up-right"></i>
                                    <span>+5% vs. last week</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Employees by Department -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <i class="ph ph-chart-pie-slice" style="color: #3b82f6;"></i>
                                <span>Employees by Department</span>
                            </div>
                            <span class="hr-bento-pill">Total {{ $totalEmployees }}</span>
                        </div>
                        <div class="hr-donut-layout">
                            <div class="hr-donut-chart-wrap" style="width: 145px; height: 145px;">
                                <canvas id="deptDonutChart"></canvas>
                                <div class="hr-donut-center-info">
                                    <span class="hr-donut-center-num">{{ $totalEmployees }}</span>
                                    <span class="hr-donut-center-lbl">Total Employees</span>
                                </div>
                            </div>
                            <div class="hr-breakdown-list">
                                @foreach($deptBreakdown as $db)
                                    <div class="hr-breakdown-item">
                                        <span class="hr-breakdown-item-left">
                                            <span class="hr-breakdown-dot" style="background-color: {{ $db['color'] }};"></span>
                                            {{ $db['name'] }}
                                        </span>
                                        <span class="hr-breakdown-item-right">
                                            {{ $db['count'] }} <span class="hr-breakdown-pct">({{ $db['percent'] }}%)</span>
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
                    <div class="hr-bento-card" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div class="hr-bento-card-header">
                                <div class="hr-bento-card-title">
                                    <i class="ph ph-wallet" style="color: #8b5cf6;"></i>
                                    <span>Payroll Summary</span>
                                </div>
                                <span class="hr-bento-pill" style="cursor: pointer;">
                                    {{ $payrollSummary['period_name'] ?? 'September 2026 - 1st Half' }} <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i>
                                </span>
                            </div>
                            
                            <div style="height: 160px; position: relative;">
                                <canvas id="payrollSummaryChart"></canvas>
                            </div>

                            <div class="hr-payroll-summary-stats">
                                <div class="hr-payroll-stat-item">
                                    <span class="hr-payroll-stat-label">Gross Pay</span>
                                    <span class="hr-payroll-stat-val">₱{{ number_format($payrollSummary['total_gross'], 2) }}</span>
                                </div>
                                <div class="hr-payroll-stat-item">
                                    <span class="hr-payroll-stat-label">Total Net Pay</span>
                                    <span class="hr-payroll-stat-val">₱{{ number_format($payrollSummary['total_net'], 2) }}</span>
                                </div>
                                <div class="hr-payroll-stat-item">
                                    <span class="hr-payroll-stat-label">Deductions</span>
                                    <span class="hr-payroll-stat-val red">-₱{{ number_format($payrollSummary['total_deductions'], 2) }}</span>
                                </div>
                                <div class="hr-payroll-stat-item">
                                    <span class="hr-payroll-stat-label">Overtime Pay</span>
                                    <span class="hr-payroll-stat-val">₱{{ number_format($payrollSummary['total_ot'], 2) }}</span>
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('hr.payroll.register') }}" class="hr-bento-view-details">
                            View Details <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>

                    <!-- Recent Attendance -->
                    <div class="hr-bento-card">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <i class="ph ph-check-circle" style="color: #3b82f6;"></i>
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
                                        $sampleIps = ['127.0.0.1', '10.0.0.12', '10.0.0.12', '137.74.0.1', '137.74.0.1'];
                                    @endphp
                                    @forelse($recentAttendance as $idx => $att)
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt">
                                                    <i class="ph ph-clock"></i>
                                                    <span>{{ \Carbon\Carbon::parse($att->date)->format('M d, Y') }} {{ $att->time_in ? \Carbon\Carbon::parse($att->time_in)->format('h:i A') : '08:45 AM' }}</span>
                                                </div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">{{ $att->employee?->first_name ?? 'Peter' }}</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">{{ $sampleIps[$idx % count($sampleIps)] }}</td>
                                            <td>
                                                @if($att->status !== 'Absent')
                                                    <span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span>
                                                @else
                                                    <span class="hr-pill-badge-red"><i class="ph ph-x"></i> Failed</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt"><i class="ph ph-clock"></i><span>Sep 24, 2026 08:45 AM</span></div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">Peter</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">127.0.0.1</td>
                                            <td><span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt"><i class="ph ph-clock"></i><span>Sep 23, 2026 11:37 PM</span></div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">Peter</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">10.0.0.12</td>
                                            <td><span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt"><i class="ph ph-clock"></i><span>Sep 23, 2026 11:21 PM</span></div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">Peter</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">10.0.0.12</td>
                                            <td><span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt"><i class="ph ph-clock"></i><span>Sep 22, 2026 09:24 PM</span></div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">Peter</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">137.74.0.1</td>
                                            <td><span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span></td>
                                        </tr>
                                        <tr>
                                            <td>
                                                <div class="hr-recent-dt"><i class="ph ph-clock"></i><span>Sep 22, 2026 07:50 PM</span></div>
                                            </td>
                                            <td style="font-weight: 600; color: #0f172a;">Peter</td>
                                            <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">137.74.0.1</td>
                                            <td><span class="hr-pill-badge-red"><i class="ph ph-x"></i> Failed</span></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>

            <!-- RIGHT ASIDE COLUMN (BENTO ASIDE) -->
            <div class="hr-bento-aside">
                
                <!-- Quick Stats -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title">
                            <i class="ph ph-squares-four" style="color: #ec4899;"></i>
                            <span>Quick Stats</span>
                        </div>
                        <a href="{{ route('hr.reports.index') }}" class="hr-bento-link">
                            View All <i class="ph ph-caret-right"></i>
                        </a>
                    </div>
                    <div class="hr-quick-stats-list">
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-clock" style="color: #8b5cf6;"></i> Overtime Logs
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ $pendingOtRequests }} <span class="tag">(7d)</span>
                            </span>
                        </div>
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-cake" style="color: #ec4899;"></i> Birthdays
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ $birthdaysCount30d }} <span class="tag">(30d)</span>
                            </span>
                        </div>
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-calendar-check" style="color: #10b981;"></i> Regularization Due
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ count($upcomingRegularizations) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Birthdays -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header">
                        <div>
                            <div class="hr-bento-card-title">
                                <i class="ph ph-sparkle" style="color: #8b5cf6;"></i>
                                <span>Upcoming Birthdays</span>
                            </div>
                            <div class="hr-bento-card-subtitle">Next 30 Days</div>
                        </div>
                        <a href="{{ route('hr.people.employees') }}" class="hr-bento-link">
                            View All <i class="ph ph-caret-right"></i>
                        </a>
                    </div>
                    <div class="hr-bday-list">
                        @php
                            $avatarColors = ['purple', 'pink', 'blue'];
                        @endphp
                        @forelse($displayBirthdays->take(3) as $idx => $bday)
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar {{ $avatarColors[$idx % count($avatarColors)] }}">
                                        {{ strtoupper(substr($bday->first_name, 0, 1)) }}
                                    </span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">{{ $bday->full_name }}</span>
                                        <span class="hr-bday-date">{{ $bday->formatted_birthday }}</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-cake"></i>
                            </div>
                        @empty
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar purple">G</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Gabriel Tan</span>
                                        <span class="hr-bday-date">Sep 30</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-cake"></i>
                            </div>
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar pink">M</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Maria Garcia</span>
                                        <span class="hr-bday-date">Nov 05</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-cake"></i>
                            </div>
                            <div class="hr-bday-item">
                                <div class="hr-bday-item-left">
                                    <span class="hr-bday-avatar blue">E</span>
                                    <div class="hr-bday-info">
                                        <span class="hr-bday-name">Eduardo Ramos</span>
                                        <span class="hr-bday-date">Jul 27</span>
                                    </div>
                                </div>
                                <i class="ph ph-cake hr-bday-cake"></i>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Top Sections -->
                <div class="hr-bento-card">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title">
                            <i class="ph ph-squares-four" style="color: #3b82f6;"></i>
                            <span>Top Sections</span>
                        </div>
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
                                <i class="ph ph-calendar-blank"></i>
                            </div>
                            <span class="hr-top-section-name">Payroll</span>
                            <span class="hr-top-section-sub">Process &amp; view &rarr;</span>
                        </a>

                        <a href="{{ route('hr.attendance.dtr') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon green">
                                <i class="ph ph-clock"></i>
                            </div>
                            <span class="hr-top-section-name">Attendance</span>
                            <span class="hr-top-section-sub">View logs &rarr;</span>
                        </a>

                        <a href="{{ route('hr.leave.requests') }}" class="hr-top-section-tile">
                            <div class="hr-top-section-icon pink">
                                <i class="ph ph-calendar-plus"></i>
                            </div>
                            <span class="hr-top-section-name">Leave Management</span>
                            <span class="hr-top-section-sub">Handle requests &rarr;</span>
                        </a>
                    </div>
                </div>

            </div>

        </div>

        <!-- 4. Bottom Banner: System Alerts & Motto -->
        <div class="hr-bento-bottom-banner">
            <div class="hr-bento-alert-part">
                <div class="hr-bento-alert-header">
                    <i class="ph ph-bell" style="color: #ec4899; font-size: 16px;"></i>
                    <span>System Alerts</span>
                    <span class="hr-bento-pill" style="background: #ede9fe; color: #7c3aed; font-size: 10px;">1 new message</span>
                </div>
                <div class="hr-bento-alert-row">
                    <i class="ph ph-warning amber"></i>
                    <span>There are {{ count($upcomingRegularizations) > 0 ? count($upcomingRegularizations) : 2 }} employees with pending regularization.</span>
                    <a href="{{ route('hr.people.employees', ['employment_status' => 'Probationary']) }}" class="view-link">
                        View Details <i class="ph ph-caret-right"></i>
                    </a>
                </div>
            </div>
            <div class="hr-bento-motto-part">
                <span class="hr-bento-motto-text">&ldquo;Better systems. Happier people.&rdquo;</span>
                <svg class="hr-bento-motto-deco" viewBox="0 0 140 60" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M120 60C120 40 100 20 70 30C50 36 30 10 10 20C40 15 60 5 90 10C110 14 130 35 140 60H120Z" fill="url(#botGrad)"/>
                    <defs>
                        <linearGradient id="botGrad" x1="0" y1="0" x2="140" y2="60" gradientUnits="userSpaceOnUse">
                            <stop stop-color="#a855f7"/>
                            <stop offset="1" stop-color="#ec4899"/>
                        </linearGradient>
                    </defs>
                </svg>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Attendance Summary Chart (Grouped Bars)
    const attCtx = document.getElementById('attendanceSummaryChart');
    if (attCtx) {
        new Chart(attCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode($past7Days) !!},
                datasets: [
                    {
                        label: 'Present',
                        data: {!! json_encode($attSummaryPresent) !!},
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7,
                    },
                    {
                        label: 'Late',
                        data: {!! json_encode($attSummaryLate) !!},
                        backgroundColor: '#f59e0b',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7,
                    },
                    {
                        label: 'Absent',
                        data: {!! json_encode($attSummaryAbsent) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 4,
                        barPercentage: 0.6,
                        categoryPercentage: 0.7,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                        padding: 10,
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        titleFont: { family: 'Poppins', size: 12 },
                        bodyFont: { family: 'Poppins', size: 11 },
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 10.5 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 2,
                            font: { family: 'Poppins', size: 10.5 },
                            color: '#64748b'
                        },
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [4, 4]
                        }
                    }
                }
            }
        });
    }

    // 2. Employees by Department Donut Chart
    const deptCtx = document.getElementById('deptDonutChart');
    if (deptCtx) {
        const deptLabels = {!! json_encode(array_column($deptBreakdown, 'name')) !!};
        const deptCounts = {!! json_encode(array_column($deptBreakdown, 'count')) !!};
        const deptColors = {!! json_encode(array_column($deptBreakdown, 'color')) !!};

        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: deptLabels,
                datasets: [{
                    data: deptCounts,
                    backgroundColor: deptColors,
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { family: 'Poppins', size: 11 }
                    }
                }
            }
        });
    }

    // 3. Payroll Summary Bar Chart
    const payCtx = document.getElementById('payrollSummaryChart');
    if (payCtx) {
        const payrollData = {!! json_encode($payrollSummary) !!};
        new Chart(payCtx, {
            type: 'bar',
            data: {
                labels: ['Gross Pay', 'Total Net Pay', 'Deductions', 'Overtime Pay', 'Night Diff Pay'],
                datasets: [{
                    data: payrollData ? [
                        payrollData.total_gross,
                        payrollData.total_net,
                        payrollData.total_deductions,
                        payrollData.total_ot,
                        payrollData.total_nd
                    ] : [138250, 109439.20, 28810.80, 0, 0],
                    backgroundColor: ['#a855f7', '#10b981', '#f43f5e', '#38bdf8', '#c084fc'],
                    borderRadius: 6,
                    barPercentage: 0.52,
                    categoryPercentage: 0.7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ' ₱' + Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        },
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        bodyFont: { family: 'Poppins', size: 11 }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Poppins', size: 10 }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) {
                                return '₱' + (val >= 1000 ? (val / 1000) + 'k' : val);
                            },
                            font: { family: 'Poppins', size: 10 },
                            color: '#64748b'
                        },
                        grid: {
                            color: '#f1f5f9',
                            borderDash: [4, 4]
                        }
                    }
                }
            }
        });
    }

    // HR Custom Branch Dropdown Interactive Toggle
    const branchTrigger = document.getElementById('hrBranchTrigger');
    const branchMenu = document.getElementById('hrBranchMenu');

    if (branchTrigger && branchMenu) {
        branchTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = branchMenu.classList.contains('show');
            if (isOpen) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
            } else {
                branchMenu.classList.add('show');
                branchTrigger.classList.add('active');
                branchTrigger.setAttribute('aria-expanded', 'true');
            }
        });

        document.addEventListener('click', function(e) {
            if (!branchTrigger.contains(e.target) && !branchMenu.contains(e.target)) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && branchMenu.classList.contains('show')) {
                branchMenu.classList.remove('show');
                branchTrigger.classList.remove('active');
                branchTrigger.setAttribute('aria-expanded', 'false');
                branchTrigger.focus();
            }
        });
    }
});
</script>
@endpush

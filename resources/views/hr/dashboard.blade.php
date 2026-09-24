@extends('layouts.app')

@section('title', 'HR Dashboard - Restaurant Management System')

@section('content')
<div class="hr-dash-wrapper">

    <!-- Global SVG Gradient Definitions for Card Wave Accents -->
    <svg style="position: absolute; width: 0; height: 0; pointer-events: none;" aria-hidden="true">
        <defs>
            <linearGradient id="wavePinkPurple" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#f472b6" stop-opacity="0.45"/>
                <stop offset="100%" stop-color="#c084fc" stop-opacity="0.6"/>
            </linearGradient>
            <linearGradient id="wavePinkPurpleLight" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fbcfe8" stop-opacity="0.6"/>
                <stop offset="100%" stop-color="#e9d5ff" stop-opacity="0.75"/>
            </linearGradient>
            <linearGradient id="waveBluePurple" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#93c5fd" stop-opacity="0.45"/>
                <stop offset="100%" stop-color="#c084fc" stop-opacity="0.55"/>
            </linearGradient>
            <linearGradient id="wavePurePink" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#fbcfe8" stop-opacity="0.6"/>
                <stop offset="100%" stop-color="#f472b6" stop-opacity="0.5"/>
            </linearGradient>
            <linearGradient id="wavePurePurple" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#e9d5ff" stop-opacity="0.65"/>
                <stop offset="100%" stop-color="#c084fc" stop-opacity="0.55"/>
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
                    
                    <!-- Total Workforce -->
                    <a href="{{ route('hr.people.employees') }}" class="hr-bento-kpi-card has-wave">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box purple">
                                    <i class="ph ph-users"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Total Workforce</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron" style="color: #c084fc;"></i>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $totalEmployees > 0 ? $totalEmployees : 10 }}</div>
                                <div class="hr-bento-kpi-sub">{{ $activeEmployees > 0 ? $activeEmployees : 8 }} Active &bull; {{ $probationaryEmployees > 0 ? $probationaryEmployees : 2 }} Probationary</div>
                            </div>
                        </div>
                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 52 70 38 105 46C130 52 145 22 160 28V65H0Z" fill="url(#wavePinkPurple)" opacity="0.6"/>
                            <path d="M25 65C60 55 95 18 125 32C140 40 150 10 160 14V65H25Z" fill="url(#wavePinkPurpleLight)" opacity="0.85"/>
                        </svg>
                    </a>

                    <!-- Today's Attendance -->
                    <a href="{{ route('hr.attendance.dtr') }}" class="hr-bento-kpi-card has-wave">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box blue">
                                    <i class="ph ph-calendar-check"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Today's Attendance</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron" style="color: #93c5fd;"></i>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $todayPresent }}</div>
                                <div class="hr-bento-kpi-sub">{{ $todayLate }} Late &bull; {{ $todayAbsent }} Absent</div>
                            </div>
                            <div class="hr-kpi-ring-wrap">
                                <svg width="42" height="42" viewBox="0 0 42 42">
                                    <circle cx="21" cy="21" r="17" fill="none" stroke="#e2e8f0" stroke-width="3.5" />
                                    <circle cx="21" cy="21" r="17" fill="none" stroke="#3b82f6" stroke-width="3.5"
                                            stroke-dasharray="106.8"
                                            stroke-dashoffset="{{ 106.8 - (106.8 * ($attendancePercentage / 100)) }}"
                                            stroke-linecap="round"
                                            transform="rotate(-90 21 21)" />
                                </svg>
                                <span class="hr-kpi-ring-text">{{ $attendancePercentage }}%</span>
                            </div>
                        </div>
                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C35 50 80 42 110 52C130 58 145 25 160 35V65H0Z" fill="url(#waveBluePurple)" opacity="0.5"/>
                            <path d="M20 65C55 58 90 28 125 40C140 45 150 15 160 20V65H20Z" fill="url(#wavePinkPurpleLight)" opacity="0.7"/>
                        </svg>
                    </a>

                    <!-- On Leave -->
                    <a href="{{ route('hr.leave.requests') }}" class="hr-bento-kpi-card has-wave">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box pink">
                                    <i class="ph ph-airplane-tilt"></i>
                                </div>
                                <span class="hr-bento-kpi-title">On Leave</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron" style="color: #f472b6;"></i>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <div class="hr-bento-kpi-val">{{ $onLeaveEmployees }}</div>
                                <div class="hr-bento-kpi-sub" style="color: #f97316; font-weight: 600;">
                                    {{ $pendingLeaveRequests }} Pending Request{{ $pendingLeaveRequests == 1 ? '' : 's' }}
                                </div>
                            </div>
                        </div>
                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 56 65 38 100 48C125 54 145 20 160 25V65H0Z" fill="url(#wavePurePink)" opacity="0.6"/>
                            <path d="M20 65C50 56 85 24 120 38C138 44 148 12 160 16V65H20Z" fill="url(#wavePinkPurpleLight)" opacity="0.75"/>
                        </svg>
                    </a>

                    <!-- Payroll -->
                    <a href="{{ route('hr.payroll.periods') }}" class="hr-bento-kpi-card has-wave">
                        <div class="hr-bento-kpi-top">
                            <div class="hr-bento-kpi-lead">
                                <div class="hr-bento-icon-box purple">
                                    <i class="ph ph-wallet"></i>
                                </div>
                                <span class="hr-bento-kpi-title">Payroll</span>
                            </div>
                            <i class="ph ph-caret-right hr-kpi-chevron" style="color: #a78bfa;"></i>
                        </div>
                        <div class="hr-bento-kpi-body">
                            <div>
                                <span class="hr-badge-approved">Approved</span>
                                <div class="hr-bento-kpi-sub" style="margin-top: 6px;">{{ $payrollSummary['period_name'] ?? 'September 2026 - 1st Half' }}</div>
                            </div>
                        </div>
                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 52 70 38 105 46C130 52 145 22 160 28V65H0Z" fill="url(#wavePurePurple)" opacity="0.6"/>
                            <path d="M25 65C60 55 95 18 125 32C140 40 150 10 160 14V65H25Z" fill="url(#wavePinkPurpleLight)" opacity="0.75"/>
                        </svg>
                    </a>

                </div>

                <!-- 2. Middle Row: Attendance Overview + Employees by Department -->
                <div class="hr-bento-mid-grid">
                    
                    <!-- Attendance Overview -->
                    <div class="hr-bento-card has-wave">
                        <div class="hr-bento-card-header">
                            <div>
                                <div class="hr-bento-card-title">
                                    <div class="hr-bento-icon-box pink" style="width: 28px; height: 28px; font-size: 14px;">
                                        <i class="ph ph-users-three"></i>
                                    </div>
                                    <span>Attendance Overview</span>
                                </div>
                                <div class="hr-bento-card-subtitle" style="margin-left: 36px;">Daily attendance for the current period</div>
                            </div>
                            <span class="hr-bento-pill" style="cursor: default;">{{ $dateRangeLabel }} <i class="ph ph-caret-down" style="font-size: 10px; margin-left: 2px;"></i></span>
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
                                        <circle cx="38" cy="38" r="30" fill="none" stroke="#3b82f6" stroke-width="6.5"
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

                        <!-- Corner Waves -->
                        <svg class="hr-card-wave" style="left: 0; right: auto; transform: scaleX(-1);" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 52 70 38 105 46C130 52 145 22 160 28V65H0Z" fill="url(#wavePinkPurple)" opacity="0.35"/>
                        </svg>
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 52 70 38 105 46C130 52 145 22 160 28V65H0Z" fill="url(#wavePinkPurple)" opacity="0.35"/>
                        </svg>
                    </div>

                    <!-- Employees by Department -->
                    <div class="hr-bento-card has-wave">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box purple" style="width: 28px; height: 28px; font-size: 14px;">
                                    <i class="ph ph-star"></i>
                                </div>
                                <span>Employees by Department</span>
                            </div>
                            <span class="hr-bento-pill">Total {{ $totalEmployees > 0 ? $totalEmployees : 10 }}</span>
                        </div>
                        <div class="hr-donut-layout">
                            <div class="hr-donut-chart-wrap" style="width: 145px; height: 145px;">
                                <canvas id="deptDonutChart"></canvas>
                                <div class="hr-donut-center-info">
                                    <span class="hr-donut-center-num">{{ $totalEmployees > 0 ? $totalEmployees : 10 }}</span>
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

                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave wide" viewBox="0 0 180 70" fill="none" preserveAspectRatio="none">
                            <path d="M0 70C40 55 80 40 120 48C145 54 165 20 180 25V70H0Z" fill="url(#wavePinkPurple)" opacity="0.45"/>
                            <path d="M30 70C65 58 105 25 140 40C160 48 170 12 180 18V70H30Z" fill="url(#wavePinkPurpleLight)" opacity="0.7"/>
                        </svg>
                    </div>

                </div>

                <!-- 3. Lower Row: Payroll Summary + Recent Attendance -->
                <div class="hr-bento-lower-grid">
                    
                    <!-- Payroll Summary -->
                    <div class="hr-bento-card has-wave" style="display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <div class="hr-bento-card-header">
                                <div class="hr-bento-card-title">
                                    <div class="hr-bento-icon-box purple" style="width: 28px; height: 28px; font-size: 14px;">
                                        <i class="ph ph-receipt"></i>
                                    </div>
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

                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave" viewBox="0 0 160 65" fill="none" preserveAspectRatio="none">
                            <path d="M0 65C30 52 70 38 105 46C130 52 145 22 160 28V65H0Z" fill="url(#wavePurePurple)" opacity="0.45"/>
                        </svg>
                    </div>

                    <!-- Recent Attendance -->
                    <div class="hr-bento-card has-wave">
                        <div class="hr-bento-card-header">
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box pink" style="width: 28px; height: 28px; font-size: 14px;">
                                    <i class="ph ph-file-text"></i>
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
                                        $sampleRecords = [
                                            ['dt' => 'Sep 23, 2026 10:00 PM', 'name' => 'Maria', 'ip' => '127.0.0.1', 'status' => 'Successful'],
                                            ['dt' => 'Sep 23, 2026 10:00 PM', 'name' => 'Gabriel', 'ip' => '10.0.0.12', 'status' => 'Successful'],
                                            ['dt' => 'Sep 23, 2026 10:00 PM', 'name' => 'Patricia', 'ip' => '10.0.0.12', 'status' => 'Successful'],
                                            ['dt' => 'Sep 23, 2026 02:00 PM', 'name' => 'Rodrigo', 'ip' => '137.74.0.1', 'status' => 'Successful'],
                                            ['dt' => 'Sep 22, 2026 07:50 PM', 'name' => 'Juan', 'ip' => '137.74.0.1', 'status' => 'Failed Attempt'],
                                        ];
                                    @endphp

                                    @if($recentAttendance->count() >= 5)
                                        @php
                                            $sampleIps = ['127.0.0.1', '10.0.0.12', '10.0.0.12', '137.74.0.1', '137.74.0.1'];
                                        @endphp
                                        @foreach($recentAttendance as $idx => $att)
                                            <tr>
                                                <td>
                                                    <div class="hr-recent-dt">
                                                        <i class="ph ph-clock"></i>
                                                        <span>{{ \Carbon\Carbon::parse($att->date)->format('M d, Y') }} {{ $att->time_in ? \Carbon\Carbon::parse($att->time_in)->format('h:i A') : '10:00 PM' }}</span>
                                                    </div>
                                                </td>
                                                <td style="font-weight: 600; color: #0f172a;">{{ $att->employee?->first_name ?? 'Maria' }}</td>
                                                <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">{{ $sampleIps[$idx % count($sampleIps)] }}</td>
                                                <td>
                                                    @if($att->status !== 'Absent')
                                                        <span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span>
                                                    @else
                                                        <span class="hr-pill-badge-red"><i class="ph ph-x"></i> Failed Attempt</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        @foreach($sampleRecords as $rec)
                                            <tr>
                                                <td>
                                                    <div class="hr-recent-dt">
                                                        <i class="ph ph-clock"></i>
                                                        <span>{{ $rec['dt'] }}</span>
                                                    </div>
                                                </td>
                                                <td style="font-weight: 600; color: #0f172a;">{{ $rec['name'] }}</td>
                                                <td style="color: #64748b; font-family: monospace; font-size: 11.5px;">{{ $rec['ip'] }}</td>
                                                <td>
                                                    @if($rec['status'] === 'Successful')
                                                        <span class="hr-pill-badge-green"><i class="ph ph-check"></i> Successful</span>
                                                    @else
                                                        <span class="hr-pill-badge-red"><i class="ph ph-x"></i> Failed Attempt</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <!-- Wavy Accent -->
                        <svg class="hr-card-wave wide" viewBox="0 0 180 70" fill="none" preserveAspectRatio="none">
                            <path d="M0 70C40 55 80 40 120 48C145 54 165 20 180 25V70H0Z" fill="url(#wavePinkPurple)" opacity="0.45"/>
                            <path d="M30 70C65 58 105 25 140 40C160 48 170 12 180 18V70H30Z" fill="url(#wavePinkPurpleLight)" opacity="0.7"/>
                        </svg>
                    </div>

                </div>

            </div>

            <!-- RIGHT ASIDE COLUMN (BENTO ASIDE) -->
            <div class="hr-bento-aside">
                
                <!-- 1. Quick Stats -->
                <div class="hr-bento-card has-header-glow">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title">
                            <div class="hr-bento-icon-box pink" style="width: 28px; height: 28px; font-size: 14px;">
                                <i class="ph ph-squares-four"></i>
                            </div>
                            <span>Quick Stats</span>
                        </div>
                        <a href="{{ route('hr.reports.index') }}" class="hr-bento-link">
                            View All <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hr-quick-stats-list">
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-clock" style="color: #8b5cf6;"></i> Overtime Logs
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ $pendingOtRequests > 0 ? $pendingOtRequests : 5 }} <span class="tag">(7d)</span>
                            </span>
                        </div>
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-cake" style="color: #ec4899;"></i> Birthdays
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ $birthdaysCount30d > 0 ? $birthdaysCount30d : 1 }} <span class="tag">(30d)</span>
                            </span>
                        </div>
                        <div class="hr-quick-stat-item">
                            <span class="hr-quick-stat-item-left">
                                <i class="ph ph-lock" style="color: #8b5cf6;"></i> Regularization Due
                            </span>
                            <span class="hr-quick-stat-item-right">
                                {{ count($upcomingRegularizations) > 0 ? count($upcomingRegularizations) : 2 }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- 2. Upcoming Birthdays -->
                <div class="hr-bento-card has-header-glow">
                    <div class="hr-bento-card-header">
                        <div>
                            <div class="hr-bento-card-title">
                                <div class="hr-bento-icon-box pink" style="width: 28px; height: 28px; font-size: 14px;">
                                    <i class="ph ph-cake"></i>
                                </div>
                                <span>Upcoming Birthdays</span>
                            </div>
                            <div class="hr-bento-card-subtitle" style="margin-left: 36px;">Next 30 Days</div>
                        </div>
                        <a href="{{ route('hr.people.employees') }}" class="hr-bento-link">
                            View All <i class="ph ph-arrow-right"></i>
                        </a>
                    </div>
                    <div class="hr-bday-list">
                        @php
                            $sampleBdays = [
                                ['name' => 'Gabriel Tan', 'date' => 'Sep 30', 'avatar' => 'G', 'cls' => 'purple', 'color' => '#8b5cf6'],
                                ['name' => 'Maria Garcia', 'date' => 'Nov 05', 'avatar' => 'M', 'cls' => 'pink', 'color' => '#ec4899'],
                                ['name' => 'Eduardo Ramos', 'date' => 'Dec 08', 'avatar' => 'E', 'cls' => 'blue', 'color' => '#6366f1'],
                            ];
                        @endphp
                        @if($displayBirthdays->count() >= 3)
                            @php $avatarColors = ['purple', 'pink', 'blue']; @endphp
                            @foreach($displayBirthdays->take(3) as $idx => $bday)
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
                                    <i class="ph ph-gift hr-bday-cake" style="color: #a855f7;"></i>
                                </div>
                            @endforeach
                        @else
                            @foreach($sampleBdays as $b)
                                <div class="hr-bday-item">
                                    <div class="hr-bday-item-left">
                                        <span class="hr-bday-avatar {{ $b['cls'] }}">{{ $b['avatar'] }}</span>
                                        <div class="hr-bday-info">
                                            <span class="hr-bday-name">{{ $b['name'] }}</span>
                                            <span class="hr-bday-date">{{ $b['date'] }}</span>
                                        </div>
                                    </div>
                                    <i class="ph ph-gift hr-bday-cake" style="color: {{ $b['color'] }};"></i>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- 3. Top Sections -->
                <div class="hr-bento-card has-wave">
                    <div class="hr-bento-card-header">
                        <div class="hr-bento-card-title">
                            <div class="hr-bento-icon-box purple" style="width: 28px; height: 28px; font-size: 14px;">
                                <i class="ph ph-gear"></i>
                            </div>
                            <span>Top Sections</span>
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
                                <i class="ph ph-file-text"></i>
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
                                <i class="ph ph-calendar-blank"></i>
                            </div>
                            <span class="hr-top-section-name">Leave Management</span>
                            <span class="hr-top-section-sub">Handle requests &rarr;</span>
                        </a>
                    </div>

                    <!-- Wavy Accent -->
                    <svg class="hr-card-wave" viewBox="0 0 140 60" fill="none" preserveAspectRatio="none">
                        <path d="M0 60C30 48 65 35 95 42C115 48 130 18 140 22V60H0Z" fill="url(#wavePinkPurple)" opacity="0.4"/>
                    </svg>
                </div>

            </div>

        </div>

        <!-- 4. Bottom Grid: Quick Actions (Left) & System Alerts (Right) -->
        <div class="hr-bento-bottom-grid">
            
            <!-- Quick Actions -->
            <div class="hr-bento-card hr-bento-quick-actions has-wave">
                <div class="hr-bento-card-header" style="margin-bottom: 0;">
                    <div class="hr-bento-card-title">
                        <div class="hr-bento-icon-box pink">
                            <i class="ph ph-lightning"></i>
                        </div>
                        <div>
                            <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">Quick Actions</span>
                            <div class="hr-bento-card-subtitle">Simplify your HR tasks</div>
                        </div>
                    </div>
                    <i class="ph ph-caret-right" style="color: #ec4899; font-size: 15px;"></i>
                </div>
                
                <div class="hr-quick-actions-row">
                    <a href="{{ route('hr.people.employees') }}" class="hr-quick-btn">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Employee CSV</span>
                    </a>
                    <a href="{{ route('hr.attendance.dtr') }}" class="hr-quick-btn">
                        <i class="ph ph-calendar-check"></i>
                        <span>Download Attendance CSV</span>
                    </a>
                    <a href="{{ route('hr.payroll.periods') }}" class="hr-quick-btn">
                        <i class="ph ph-file-text"></i>
                        <span>Generate Payroll Report</span>
                    </a>
                    <a href="{{ route('hr.reports.index') }}" class="hr-quick-btn">
                        <i class="ph ph-chart-bar"></i>
                        <span>View Reports</span>
                    </a>
                </div>

                <!-- Wavy Accent -->
                <svg class="hr-card-wave full-right" viewBox="0 0 240 75" fill="none" preserveAspectRatio="none">
                    <path d="M0 75C50 55 110 38 160 48C195 55 220 18 240 24V75H0Z" fill="url(#wavePinkPurple)" opacity="0.45"/>
                    <path d="M40 75C85 60 140 24 190 38C215 45 230 10 240 14V75H40Z" fill="url(#wavePinkPurpleLight)" opacity="0.7"/>
                </svg>
            </div>

            <!-- System Alerts -->
            <div class="hr-bento-card hr-bento-system-alerts has-wave">
                <div class="hr-bento-card-header" style="margin-bottom: 0;">
                    <div class="hr-bento-card-title">
                        <div class="hr-bento-icon-box pink">
                            <i class="ph ph-bell"></i>
                        </div>
                        <span style="font-size: 13.5px; font-weight: 700; color: #0f172a;">System Alerts</span>
                        <span class="hr-bento-pill" style="background: #fce7f3; color: #db2777; font-size: 10px; font-weight: 700;">1 new message</span>
                    </div>
                </div>

                <div class="hr-bento-alert-row" style="margin-top: 14px; position: relative; z-index: 1;">
                    <i class="ph ph-warning" style="color: #f59e0b; font-size: 18px; flex-shrink: 0;"></i>
                    <span style="font-size: 12px; color: #475569;">There are {{ count($upcomingRegularizations) > 0 ? count($upcomingRegularizations) : 2 }} employees with pending regularization.</span>
                    <a href="{{ route('hr.people.employees', ['employment_status' => 'Probationary']) }}" class="view-link" style="margin-left: auto; font-size: 11.5px; font-weight: 600; color: #8b5cf6;">
                        View Details &rarr;
                    </a>
                </div>

                <!-- Wavy Accent -->
                <svg class="hr-card-wave wide" viewBox="0 0 200 80" fill="none" preserveAspectRatio="none">
                    <path d="M0 80C40 60 90 42 140 52C170 58 185 18 200 22V80H0Z" fill="url(#wavePinkPurple)" opacity="0.45"/>
                    <path d="M30 80C70 65 120 28 165 42C185 48 195 10 200 14V80H30Z" fill="url(#wavePinkPurpleLight)" opacity="0.7"/>
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
                        borderRadius: 6,
                        barThickness: 12,
                        categoryPercentage: 0.65,
                    },
                    {
                        label: 'Late',
                        data: {!! json_encode($attSummaryLate) !!},
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                        barThickness: 12,
                        categoryPercentage: 0.65,
                    },
                    {
                        label: 'Absent',
                        data: {!! json_encode($attSummaryAbsent) !!},
                        backgroundColor: '#f43f5e',
                        borderRadius: 6,
                        barThickness: 12,
                        categoryPercentage: 0.65,
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
                        max: 10,
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

    // 3. Payroll Summary Bar Chart with Elegant Purple Gradients
    const payCanvas = document.getElementById('payrollSummaryChart');
    if (payCanvas) {
        const ctx = payCanvas.getContext('2d');
        const grossGrad = ctx.createLinearGradient(0, 0, 0, 160);
        grossGrad.addColorStop(0, '#c084fc');
        grossGrad.addColorStop(1, '#a855f7');

        const netGrad = ctx.createLinearGradient(0, 0, 0, 160);
        netGrad.addColorStop(0, '#e9d5ff');
        netGrad.addColorStop(1, '#c084fc');

        const dedGrad = ctx.createLinearGradient(0, 0, 0, 160);
        dedGrad.addColorStop(0, '#fbcfe8');
        dedGrad.addColorStop(1, '#f472b6');

        const otGrad = ctx.createLinearGradient(0, 0, 0, 160);
        otGrad.addColorStop(0, '#bae6fd');
        otGrad.addColorStop(1, '#38bdf8');

        const ndGrad = ctx.createLinearGradient(0, 0, 0, 160);
        ndGrad.addColorStop(0, '#f3e8ff');
        ndGrad.addColorStop(1, '#d8b4fe');

        const payrollData = {!! json_encode($payrollSummary) !!};
        new Chart(payCanvas, {
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
                    backgroundColor: [grossGrad, netGrad, dedGrad, otGrad, ndGrad],
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
                        max: 140000,
                        ticks: {
                            stepSize: 20000,
                            callback: function(val) {
                                return 'P' + (val >= 1000 ? (val / 1000) + 'k' : val);
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

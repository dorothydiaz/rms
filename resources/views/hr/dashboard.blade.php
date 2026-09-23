@extends('layouts.app')

@section('title', 'HR & People Operations Dashboard - Restaurant Management System')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-chart-pie-slice"></i>
            Restaurant HR Operations Dashboard
        </h1>
        <p class="hr-page-subtitle">Real-time workforce attendance, Philippine statutory payroll, shift scheduling, and HR metrics</p>
    </div>

    <!-- Branch Filter for Super Admin & Multi-branch HR -->
    @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
        <div class="hr-page-actions">
            <form method="GET" action="{{ route('hr.dashboard') }}" class="hr-filter-form" style="display: flex; align-items: center; gap: 8px;">
                <label for="branch_id" style="font-size: 12px; font-weight: 600; color: #475569;">Filter Branch:</label>
                <select name="branch_id" id="branch_id" class="hr-select" onchange="this.form.submit()">
                    <option value="">All Restaurant Branches</option>
                    @foreach($allBranches as $b)
                        <option value="{{ $b->id }}" {{ $branchId == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>
    @elseif(Auth::user()->branch)
        <div class="hr-badge hr-badge-purple" style="font-size: 13px; padding: 6px 14px;">
            <i class="ph ph-storefront"></i>
            <span>{{ Auth::user()->branch->name }}</span>
        </div>
    @endif
</div>

<!-- 1. KPI Metric Cards (12 Key Metrics) -->
<div class="hr-metrics-grid">
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ $totalEmployees }}</span>
            <span class="hr-metric-label">Total Workforce</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-users-three"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #059669;">{{ $activeEmployees }}</span>
            <span class="hr-metric-label">Active Employees</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-user-check"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #d97706;">{{ $probationaryEmployees }}</span>
            <span class="hr-metric-label">Probationary Staff</span>
        </div>
        <div class="hr-metric-icon amber"><i class="ph ph-hourglass-high"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #0284c7;">{{ $onLeaveEmployees }}</span>
            <span class="hr-metric-label">Employees On Leave</span>
        </div>
        <div class="hr-metric-icon sky"><i class="ph ph-calendar-blank"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #10b981;">{{ $todayPresent }}</span>
            <span class="hr-metric-label">Today's Present</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-check-circle"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #eab308;">{{ $todayLate }}</span>
            <span class="hr-metric-label">Today's Late</span>
        </div>
        <div class="hr-metric-icon amber"><i class="ph ph-timer"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #ef4444;">{{ $todayAbsent }}</span>
            <span class="hr-metric-label">Today's Absent</span>
        </div>
        <div class="hr-metric-icon rose"><i class="ph ph-user-minus"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ $pendingLeaveRequests }}</span>
            <span class="hr-metric-label">Pending Leaves</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-airplane-in-flight"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ $pendingOtRequests }}</span>
            <span class="hr-metric-label">Overtime Logs (7d)</span>
        </div>
        <div class="hr-metric-icon indigo"><i class="ph ph-trend-up"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ count($upcomingBirthdays) }}</span>
            <span class="hr-metric-label">Birthdays (30d)</span>
        </div>
        <div class="hr-metric-icon sky"><i class="ph ph-cake"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value">{{ count($upcomingRegularizations) }}</span>
            <span class="hr-metric-label">Regularization Due</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-certificate"></i></div>
    </div>

    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="font-size: 16px; font-weight: 700;">
                {{ $currentPayroll ? $currentPayroll->status : 'No Period' }}
            </span>
            <span class="hr-metric-label">Current Payroll Status</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-wallet"></i></div>
    </div>
</div>

<!-- 2. Charts Section -->
<div class="hr-charts-grid">
    <!-- Chart 1: 7-Day Attendance Trend -->
    <div class="hr-chart-card">
        <div class="hr-chart-card-header">
            <span class="hr-chart-card-title"><i class="ph ph-chart-line-up"></i> 7-Day Attendance Summary</span>
            <span class="hr-badge hr-badge-neutral">Real-Time DTR</span>
        </div>
        <div class="hr-chart-container">
            <canvas id="attendanceTrendChart"></canvas>
        </div>
    </div>

    <!-- Chart 2: Employees by Department -->
    <div class="hr-chart-card">
        <div class="hr-chart-card-header">
            <span class="hr-chart-card-title"><i class="ph ph-pie-chart"></i> Employees by Department</span>
            <span class="hr-badge hr-badge-neutral">FOH & BOH</span>
        </div>
        <div class="hr-chart-container">
            <canvas id="deptDistributionChart"></canvas>
        </div>
    </div>

    <!-- Chart 3: Employment Status -->
    <div class="hr-chart-card">
        <div class="hr-chart-card-header">
            <span class="hr-chart-card-title"><i class="ph ph-users"></i> Employees by Employment Status</span>
            <span class="hr-badge hr-badge-neutral">Tenure</span>
        </div>
        <div class="hr-chart-container">
            <canvas id="statusChart"></canvas>
        </div>
    </div>

    <!-- Chart 4: Payroll & Overtime Breakdown -->
    <div class="hr-chart-card">
        <div class="hr-chart-card-header">
            <span class="hr-chart-card-title"><i class="ph ph-currency-circle-dollar"></i> Payroll Summary</span>
            @if($currentPayroll)
                <span class="hr-badge hr-badge-purple">{{ $currentPayroll->name }}</span>
            @endif
        </div>
        <div class="hr-chart-container">
            <canvas id="payrollChart"></canvas>
        </div>
    </div>
</div>

<!-- 3. Actionable Reminders & Alerts Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Upcoming Birthdays -->
    <div class="hr-table-card" style="margin-bottom: 0;">
        <div class="hr-table-header">
            <span class="hr-table-title"><i class="ph ph-cake" style="color: #ec4899;"></i> Upcoming Birthdays</span>
            <span class="hr-badge hr-badge-neutral">Next 30 Days</span>
        </div>
        <div class="hr-table-wrapper">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Birthday</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingBirthdays as $bdayEmp)
                        <tr>
                            <td>
                                <strong>{{ $bdayEmp->full_name }}</strong><br>
                                <small style="color: #64748b;">{{ $bdayEmp->position?->name }}</small>
                            </td>
                            <td><span class="hr-badge hr-badge-neutral">{{ $bdayEmp->branch?->name }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($bdayEmp->date_of_birth)->format('M d') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No birthdays in the next 30 days.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upcoming Regularizations -->
    <div class="hr-table-card" style="margin-bottom: 0;">
        <div class="hr-table-header">
            <span class="hr-table-title"><i class="ph ph-certificate" style="color: #10b981;"></i> Probationary Regularization Due</span>
            <span class="hr-badge hr-badge-neutral">6-Month Evaluation</span>
        </div>
        <div class="hr-table-wrapper">
            <table class="hr-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Branch</th>
                        <th>Due Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($upcomingRegularizations as $regEmp)
                        <tr>
                            <td>
                                <strong>{{ $regEmp->full_name }}</strong><br>
                                <small style="color: #64748b;">{{ $regEmp->position?->name }}</small>
                            </td>
                            <td><span class="hr-badge hr-badge-neutral">{{ $regEmp->branch?->name }}</span></td>
                            <td>
                                <span class="hr-badge hr-badge-warning">
                                    {{ \Carbon\Carbon::parse($regEmp->contract_end_date)->format('M d, Y') }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8; padding: 20px;">No regularizations due in the next 45 days.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Attendance Trend Chart
    const attCtx = document.getElementById('attendanceTrendChart');
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
                    },
                    {
                        label: 'Late',
                        data: {!! json_encode($attSummaryLate) !!},
                        backgroundColor: '#f59e0b',
                        borderRadius: 6,
                    },
                    {
                        label: 'Absent',
                        data: {!! json_encode($attSummaryAbsent) !!},
                        backgroundColor: '#ef4444',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Poppins', size: 11 } } }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Department Chart
    const deptCtx = document.getElementById('deptDistributionChart');
    if (deptCtx) {
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($deptLabels) !!},
                datasets: [{
                    data: {!! json_encode($deptCounts) !!},
                    backgroundColor: ['#a855f7', '#3b82f6', '#10b981', '#f59e0b', '#ec4899', '#64748b'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Poppins', size: 11 } } }
                }
            }
        });
    }

    // 3. Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode(array_keys($statusCounts)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($statusCounts)) !!},
                    backgroundColor: ['#10b981', '#f59e0b', '#0284c7', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { family: 'Poppins', size: 11 } } }
                }
            }
        });
    }

    // 4. Payroll Summary Chart
    const payCtx = document.getElementById('payrollChart');
    if (payCtx) {
        const payrollData = {!! json_encode($payrollSummary) !!};
        new Chart(payCtx, {
            type: 'bar',
            data: {
                labels: ['Gross Pay', 'Total Net Pay', 'Deductions', 'Overtime Pay', 'Night Diff Pay'],
                datasets: [{
                    label: 'Amount (PHP)',
                    data: payrollData ? [
                        payrollData.total_gross,
                        payrollData.total_net,
                        payrollData.total_deductions,
                        payrollData.total_ot,
                        payrollData.total_nd
                    ] : [0, 0, 0, 0, 0],
                    backgroundColor: ['#a855f7', '#10b981', '#ef4444', '#f59e0b', '#6366f1'],
                    borderRadius: 6,
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
                                return ' PHP ' + Number(context.raw).toLocaleString('en-US', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(val) { return '₱' + (val / 1000) + 'k'; }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush

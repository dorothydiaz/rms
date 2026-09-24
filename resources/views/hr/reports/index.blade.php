@extends('layouts.app')

@section('title', 'Analytics Hub - Restaurant HR Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-chart-polar"></i>
            Analytics Hub
        </h1>
        <p class="hr-page-subtitle">Export employee records, attendance DTRs, Philippine payroll registers, and compliance logs in CSV format</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 30px;">
    
    <!-- 1. Employee Masterlist Report -->
    <div class="hr-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                <div class="hr-metric-icon sky">
                    <i class="ph ph-users"></i>
                </div>
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Employee Directory & Masterlist</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Complete 201-files, contact info, gov IDs, and salaries</p>
                </div>
            </div>
            <form method="GET" action="{{ route('hr.reports.export.employees') }}" style="margin-top: 16px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Filter</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">-- All Branches --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group" style="margin-top: 10px;">
                    <label class="hr-form-label">Department Filter</label>
                    <select name="department_id" class="hr-select">
                        <option value="">-- All Departments --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Employee CSV</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Attendance & DTR Report -->
    <div class="hr-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                <div class="hr-metric-icon emerald">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Attendance & DTR Time Log</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Punches, overtime, tardiness, undertime, and night diff</p>
                </div>
            </div>
            <form method="GET" action="{{ route('hr.reports.export.attendance') }}" style="margin-top: 16px;">
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Date</label>
                        <input type="date" name="start_date" class="hr-input" value="{{ date('Y-m-01') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Date</label>
                        <input type="date" name="end_date" class="hr-input" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="hr-form-group" style="margin-top: 10px;">
                    <label class="hr-form-label">Branch Filter</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">-- All Branches --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Attendance CSV</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 3. Payroll Register & Statutory Report -->
    <div class="hr-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 14px;">
                <div class="hr-metric-icon amber">
                    <i class="ph ph-money"></i>
                </div>
                <div>
                    <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin: 0;">Payroll Register & Statutory Summary</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Earnings breakdown, SSS, PhilHealth, Pag-IBIG & BIR tax</p>
                </div>
            </div>
            <form method="GET" action="{{ route('hr.reports.export.payroll') }}" style="margin-top: 16px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Pay Period *</label>
                    <select name="payroll_period_id" class="hr-select">
                        <option value="">-- All Pay Periods --</option>
                        @foreach($payrollPeriods as $pp)
                            <option value="{{ $pp->id }}">{{ $pp->name }} ({{ $pp->status }})</option>
                        @endforeach
                    </select>
                </div>
                <div style="padding: 10px 0; font-size: 12px; color: #64748b;">
                    Includes both Employee and Employer share calculations for DOLE / BIR compliance audits.
                </div>
                <div style="margin-top: 22px;">
                    <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; justify-content: center;">
                        <i class="ph ph-download-simple"></i>
                        <span>Download Payroll CSV</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>

<!-- Additional Reporting Modules -->
<div class="hr-card">
    <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
        <i class="ph ph-squares-four" style="color: #9333ea;"></i> Dedicated Analytics & Audit Ledgers
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
        <a href="{{ route('hr.leave.reports') }}" class="hr-metric-card" style="text-decoration: none; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="hr-metric-icon sky" style="width: 42px; height: 42px; font-size: 20px;">
                    <i class="ph ph-calendar-x"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; display: block; font-size: 13.5px;">Leave Utilization Report</strong>
                    <span style="font-size: 12px; color: #64748b;">Accrued vs used credits per branch</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.performance.reports') }}" class="hr-metric-card" style="text-decoration: none; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="hr-metric-icon purple" style="width: 42px; height: 42px; font-size: 20px;">
                    <i class="ph ph-chart-line-up"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; display: block; font-size: 13.5px;">Staff Performance Rankings</strong>
                    <span style="font-size: 12px; color: #64748b;">Staff evaluation leaderboard</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.training.reports') }}" class="hr-metric-card" style="text-decoration: none; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="hr-metric-icon emerald" style="width: 42px; height: 42px; font-size: 20px;">
                    <i class="ph ph-graduation-cap"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; display: block; font-size: 13.5px;">Training & Certifications</strong>
                    <span style="font-size: 12px; color: #64748b;">Completion rates and food safety</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.admin.audit-logs') }}" class="hr-metric-card" style="text-decoration: none; padding: 16px;">
            <div style="display: flex; align-items: center; gap: 14px;">
                <div class="hr-metric-icon rose" style="width: 42px; height: 42px; font-size: 20px;">
                    <i class="ph ph-shield-check"></i>
                </div>
                <div>
                    <strong style="color: #0f172a; display: block; font-size: 13.5px;">Security Audit Logs</strong>
                    <span style="font-size: 12px; color: #64748b;">User activity, approvals & changes</span>
                </div>
            </div>
        </a>
    </div>
</div>
</div>
@endsection

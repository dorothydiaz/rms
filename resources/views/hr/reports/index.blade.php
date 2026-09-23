@extends('layouts.app')

@section('title', 'Reports Center - Restaurant HR Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-file-text"></i>
            HRIS Management Reports Center
        </h1>
        <p class="hr-page-subtitle">Export employee records, attendance DTRs, Philippine payroll registers, and compliance logs in CSV format</p>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 24px; margin-bottom: 30px;">
    
    <!-- 1. Employee Masterlist Report -->
    <div class="hr-card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div class="hr-metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
                    <i class="ph ph-users"></i>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Employee Directory & Masterlist</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0;">Complete 201-files, contact info, gov IDs, and salaries</p>
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
                <div class="hr-form-group">
                    <label class="hr-form-label">Department Filter</label>
                    <select name="department_id" class="hr-select">
                        <option value="">-- All Departments --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 20px;">
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
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div class="hr-metric-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Attendance & DTR Time Log</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0;">Punches, overtime, tardiness, undertime, and night diff</p>
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
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Filter</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">-- All Branches --</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div style="margin-top: 20px;">
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
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                <div class="hr-metric-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
                    <i class="ph ph-money"></i>
                </div>
                <div>
                    <h3 style="font-size: 16px; font-weight: 700; color: #fff; margin: 0;">Payroll Register & Statutory Summary</h3>
                    <p style="font-size: 12px; color: #94a3b8; margin: 0;">Earnings breakdown, SSS, PhilHealth, Pag-IBIG & BIR tax</p>
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
                <div style="padding: 10px 0; font-size: 12px; color: #94a3b8;">
                    Includes both Employee and Employer share calculations for DOLE / BIR audits.
                </div>
                <div style="margin-top: 20px;">
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
    <h3 style="font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 16px;">
        <i class="ph ph-squares-four"></i> Dedicated Analytics & Audit Ledgers
    </h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px;">
        <a href="{{ route('hr.leave.reports') }}" class="hr-card" style="background: rgba(255, 255, 255, 0.03); text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.08); transition: 0.2s all;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="ph ph-calendar-x" style="font-size: 24px; color: #38bdf8;"></i>
                <div>
                    <strong style="color: #f1f5f9; display: block;">Leave Utilization Report</strong>
                    <span style="font-size: 12px; color: #94a3b8;">Accrued vs used credits per branch</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.performance.reports') }}" class="hr-card" style="background: rgba(255, 255, 255, 0.03); text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.08); transition: 0.2s all;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="ph ph-chart-line-up" style="font-size: 24px; color: #a855f7;"></i>
                <div>
                    <strong style="color: #f1f5f9; display: block;">Staff Performance Rankings</strong>
                    <span style="font-size: 12px; color: #94a3b8;">Staff evaluation leaderboard</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.training.reports') }}" class="hr-card" style="background: rgba(255, 255, 255, 0.03); text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.08); transition: 0.2s all;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="ph ph-graduation-cap" style="font-size: 24px; color: #10b981;"></i>
                <div>
                    <strong style="color: #f1f5f9; display: block;">Training & Certifications</strong>
                    <span style="font-size: 12px; color: #94a3b8;">Completion rates and food safety</span>
                </div>
            </div>
        </a>
        <a href="{{ route('hr.admin.audit-logs') }}" class="hr-card" style="background: rgba(255, 255, 255, 0.03); text-decoration: none; border: 1px solid rgba(255, 255, 255, 0.08); transition: 0.2s all;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <i class="ph ph-shield-check" style="font-size: 24px; color: #fb7185;"></i>
                <div>
                    <strong style="color: #f1f5f9; display: block;">Security Audit Logs</strong>
                    <span style="font-size: 12px; color: #94a3b8;">User activity, approvals & modifications</span>
                </div>
            </div>
        </a>
    </div>
</div>
@endsection

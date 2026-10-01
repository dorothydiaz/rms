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

<!-- Timekeeping, Overtime & Schedule Change Audit Ledgers -->
<div class="hr-card" style="margin-bottom: 24px; flex-shrink: 0; min-height: min-content;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-clock-counter-clockwise" style="color: #6366f1;"></i> Schedule, Overtime & Timekeeping Audit Ledgers
        </h3>
        <span style="font-size: 12px; color: #64748b;">Complete historical audit trails with manager approvals</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
        
        <!-- Change of Schedule History -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon sky" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-calendar-blank"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Change of Schedule History</h4>
                        <span style="font-size: 12px; color: #64748b;">Audit log of shift edits & default shift assignments</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Tracks who changed employee shift times, previous vs new schedules, and timestamps.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.change-of-schedule') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.change-of-schedule') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- Overtime History & Approvals -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon purple" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-clock-countdown"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Overtime History & Approvals</h4>
                        <span style="font-size: 12px; color: #64748b;">Approved, pending, and rejected overtime hours</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Full history of overtime rendered, approver identities, timestamps, and payroll status.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.overtime-history') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.overtime-history') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- Manual Time Entries History -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon emerald" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-pencil-line"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Manual Time Entries History</h4>
                        <span style="font-size: 12px; color: #64748b;">Administrative punch edits and manual entries</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Audit log of manually created time records, justifications, and administrator updates.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.manual-entries-history') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.manual-entries-history') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Attendance Analytics & Compliance Reports -->
<div class="hr-card" style="margin-bottom: 24px; flex-shrink: 0; min-height: min-content;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 10px;">
        <h3 style="font-family: var(--font-heading); font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-chart-bar" style="color: #0284c7;"></i> Attendance Compliance, Tardiness & Summaries
        </h3>
        <span style="font-size: 12px; color: #64748b;">Statutory compliance, undertime authorizations, and attendance analysis</span>
    </div>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px;">
        
        <!-- Authorized Undertime Report -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon amber" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-timer"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Authorized & Unauthorized Undertime</h4>
                        <span style="font-size: 12px; color: #64748b;">Early departure approvals & deductions</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Track employees with undertime minutes, authorized early leave vs unauthorized departures.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.authorized-undertime') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.authorized-undertime') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- Unauthorized Leave of Absences -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon rose" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-user-minus"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Unauthorized Leave of Absences</h4>
                        <span style="font-size: 12px; color: #64748b;">Unapproved absences and AWOL monitoring</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Compliance audit of unexcused absences and staff failing to file formal leave requests.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.unauthorized-absences') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.unauthorized-absences') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- All Employees with Tardiness -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon amber" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-alarm"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">All Employees with Tardiness</h4>
                        <span style="font-size: 12px; color: #64748b;">Late arrival frequency & minutes lost</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Ranked leaderboard and daily logs of employee tardiness occurrences across all branches.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.tardiness') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.tardiness') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- Attendance Summary Report -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon emerald" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-table"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Attendance Summary Report</h4>
                        <span style="font-size: 12px; color: #64748b;">Aggregated present days & total hours</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Executive consolidated table showing present days, rest days, late, overtime, and work hours.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.attendance-summary') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
                <a href="{{ route('hr.reports.export.attendance-summary') }}" class="hr-btn hr-btn-primary" style="font-size: 12px; padding: 6px 12px;" title="Download CSV">
                    <i class="ph ph-download-simple"></i>
                </a>
            </div>
        </div>

        <!-- Individual Attendance Summary -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon sky" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-user-list"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Individual Attendance Summary</h4>
                        <span style="font-size: 12px; color: #64748b;">Per-employee DTR statement & punch log</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Single employee itemized timesheet breakdown, KPI badges, and daily punch records.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.individual-attendance-summary') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
            </div>
        </div>

        <!-- Employee Attendance Profile -->
        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <div class="hr-metric-icon purple" style="width: 40px; height: 40px; font-size: 20px;">
                        <i class="ph ph-identification-card"></i>
                    </div>
                    <div>
                        <h4 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0;">Employee Attendance Profile</h4>
                        <span style="font-size: 12px; color: #64748b;">360-degree schedule & audit record</span>
                    </div>
                </div>
                <p style="font-size: 12px; color: #64748b; margin: 0 0 14px 0;">Comprehensive profile with default shift assignment, audit trail, overtime & undertime logs.</p>
            </div>
            <div style="display: flex; gap: 8px;">
                <a href="{{ route('hr.reports.employee-attendance-profile') }}" class="hr-btn hr-btn-secondary" style="flex: 1; justify-content: center; font-size: 12px; padding: 6px 12px;">
                    <i class="ph ph-eye"></i> View Report
                </a>
            </div>
        </div>

    </div>
</div>

<!-- Additional Reporting Modules -->
<div class="hr-card" style="flex-shrink: 0; min-height: min-content;">
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
@endsection

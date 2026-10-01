@extends('layouts.app')

@section('title', 'Employee Attendance Profile - Reports & Analytics')

@section('content')
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #64748b; margin-bottom: 6px;">
            <a href="{{ route('hr.reports.index') }}" style="color: #64748b; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                <i class="ph ph-chart-polar"></i> Reports & Analytics
            </a>
            <i class="ph ph-caret-right" style="font-size: 11px;"></i>
            <span style="color: #0f172a; font-weight: 600;">Employee Attendance Profile</span>
        </div>
        <h1 style="font-family: var(--font-heading); font-size: 22px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-identification-card" style="color: #6366f1;"></i> Employee Attendance Profile Report
        </h1>
        <p style="font-size: 13px; color: #64748b; margin: 3px 0 0 0;">Unified 360-degree attendance profile: shift schedules, default assignments, audit logs, overtime, and undertime history</p>
    </div>
    @if($employee)
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="{{ route('hr.reports.export.employee-attendance-profile', ['employee_id' => $employee->id]) }}" class="hr-btn hr-btn-secondary">
                <i class="ph ph-download-simple"></i>
                <span>Export Profile CSV</span>
            </a>
            <a href="{{ route('hr.attendance.schedules') }}" class="hr-btn hr-btn-primary">
                <i class="ph ph-calendar"></i>
                <span>Assign Shift</span>
            </a>
        </div>
    @endif
</div>

<!-- Multi-Filters -->
<x-report-filters 
    :action="route('hr.reports.employee-attendance-profile')"
    :branches="$branches"
    :departments="$departments"
    :companies="$companies"
    :employees="$employees"
    :showDates="false"
    :showStatus="true"
    :showSource="true"
    :showEmployeeDropdown="true"
/>

@if($employee)
    <!-- Employee 360 Card -->
    <div class="hr-card" style="margin-bottom: 24px; padding: 22px;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; gap: 18px; align-items: center;">
                <div style="width: 58px; height: 58px; border-radius: 50%; background: #e0e7ff; color: #4338ca; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px; flex-shrink: 0;">
                    {{ strtoupper(substr($employee->first_name, 0, 1) . substr($employee->last_name, 0, 1)) }}
                </div>
                <div>
                    <h2 style="font-family: var(--font-heading); font-size: 20px; font-weight: 700; color: #0f172a; margin: 0;">
                        {{ $employee->full_name }}
                    </h2>
                    <div style="display: flex; gap: 12px; font-size: 13px; color: #64748b; margin-top: 4px; flex-wrap: wrap; align-items: center;">
                        <span><i class="ph ph-identification-badge"></i> {{ $employee->employee_id }}</span>
                        <span><span class="hr-badge hr-badge-purple" style="font-size: 11px;">{{ $employee->employment_status }}</span></span>
                        <span><i class="ph ph-buildings"></i> {{ $employee->company_or_agency }}</span>
                        <span>&bull;</span>
                        <span><i class="ph ph-storefront"></i> {{ $employee->branch?->name ?? 'No Branch' }}</span>
                        <span>&bull;</span>
                        <span><i class="ph ph-tree-structure"></i> {{ $employee->department?->name ?? 'No Dept' }}</span>
                        <span>&bull;</span>
                        <span><i class="ph ph-briefcase"></i> {{ $employee->position?->name ?? 'Staff' }}</span>
                    </div>
                </div>
            </div>

            <!-- Default Shift Template Card -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 18px; display: flex; align-items: center; gap: 12px;">
                <div style="width: 36px; height: 36px; border-radius: 6px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-calendar-check"></i>
                </div>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600; color: #64748b;">Assigned Default Shift</div>
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a;">
                        {{ $employee->defaultShiftTemplate?->name ?? 'No Default Shift Assigned' }}
                    </div>
                    @if($employee->defaultShiftTemplate)
                        <div style="font-size: 11.5px; color: #64748b;">
                            {{ $employee->defaultShiftTemplate->start_time ? date('h:i A', strtotime($employee->defaultShiftTemplate->start_time)) : '' }} -
                            {{ $employee->defaultShiftTemplate->end_time ? date('h:i A', strtotime($employee->defaultShiftTemplate->end_time)) : '' }}
                            ({{ $employee->defaultShiftTemplate->hours_per_shift }} hrs)
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Section 1: Recent Attendance Records -->
    <div class="hr-card" style="margin-bottom: 24px; padding: 0; overflow: hidden;">
        <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="font-family: var(--font-heading); font-size: 15px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-clock" style="color: #0284c7;"></i> Recent Attendance Records (Last 30 Logs)
            </h3>
            <span style="font-size: 12px; color: #64748b;">{{ $recentLogs->count() }} entries</span>
        </div>
        <div class="hr-table-responsive" style="overflow-x: auto;">
            <table class="hr-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="padding: 10px 14px;">Date</th>
                        <th style="padding: 10px 14px;">Time In</th>
                        <th style="padding: 10px 14px;">Time Out</th>
                        <th style="padding: 10px 14px; text-align: right;">Total Hours</th>
                        <th style="padding: 10px 14px; text-align: right;">Late</th>
                        <th style="padding: 10px 14px; text-align: right;">Undertime</th>
                        <th style="padding: 10px 14px; text-align: right;">Overtime</th>
                        <th style="padding: 10px 14px; text-align: center;">OT Status</th>
                        <th style="padding: 10px 14px; text-align: center;">UT Status</th>
                        <th style="padding: 10px 14px; text-align: center;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentLogs as $log)
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 10px 14px; font-weight: 600; color: #0f172a;">
                                {{ $log->date ? $log->date->format('M d, Y (D)') : '—' }}
                            </td>
                            <td style="padding: 10px 14px; font-size: 12.5px;">{{ $log->time_in ? date('h:i A', strtotime($log->time_in)) : '—' }}</td>
                            <td style="padding: 10px 14px; font-size: 12.5px;">{{ $log->time_out ? date('h:i A', strtotime($log->time_out)) : '—' }}</td>
                            <td style="padding: 10px 14px; text-align: right; font-weight: 600; font-size: 12.5px;">{{ number_format($log->total_hours, 1) }}h</td>
                            <td style="padding: 10px 14px; text-align: right; font-size: 12px;">
                                @if($log->late_minutes > 0)
                                    <span style="color: #ea580c; font-weight: 600;">{{ $log->late_minutes }}m</span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: right; font-size: 12px;">
                                @if($log->undertime_minutes > 0)
                                    <span style="color: #d97706; font-weight: 600;">{{ $log->undertime_minutes }}m</span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: right; font-size: 12px;">
                                @if($log->overtime_hours > 0)
                                    <span class="hr-badge hr-badge-purple" style="font-size: 11px; padding: 2px 6px;">+{{ number_format($log->overtime_hours, 1) }}h</span>
                                @else
                                    <span style="color: #94a3b8;">0</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if($log->overtime_hours > 0)
                                    <span class="hr-badge {{ $log->overtime_status === 'Approved' ? 'hr-badge-success' : ($log->overtime_status === 'Rejected' ? 'hr-badge-danger' : 'hr-badge-warning') }}" style="font-size: 11px;">
                                        {{ $log->overtime_status }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                @if($log->undertime_minutes > 0)
                                    <span class="hr-badge {{ $log->undertime_status === 'Approved' ? 'hr-badge-success' : ($log->undertime_status === 'Rejected' ? 'hr-badge-danger' : 'hr-badge-warning') }}" style="font-size: 11px;">
                                        {{ $log->undertime_status === 'Approved' ? 'Authorized' : ($log->undertime_status === 'Rejected' ? 'Unauthorized' : 'Pending') }}
                                    </span>
                                @else
                                    <span style="color: #94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span class="hr-badge {{ $log->status === 'Present' ? 'hr-badge-success' : ($log->status === 'Absent' ? 'hr-badge-danger' : 'hr-badge-neutral') }}" style="font-size: 11px;">
                                    {{ $log->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 24px; color: #94a3b8;">No attendance records available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2 & 3: Grid of Schedule Changes & Audit Logs -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 20px;">
        
        <!-- Schedule Change History for this employee -->
        <div class="hr-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h3 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-calendar-blank" style="color: #3b82f6;"></i> Schedule Change History
                </h3>
            </div>
            <div class="hr-table-responsive" style="overflow-x: auto;">
                <table class="hr-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 9px 12px;">Date</th>
                            <th style="padding: 9px 12px;">Previous Shift</th>
                            <th style="padding: 9px 12px;">New Shift</th>
                            <th style="padding: 9px 12px;">Reason</th>
                            <th style="padding: 9px 12px;">Changed By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($changeLogs as $cl)
                            <tr style="border-bottom: 1px solid #f8fafc; font-size: 12px;">
                                <td style="padding: 9px 12px; font-weight: 600;">
                                    {{ $cl->schedule_date ? $cl->schedule_date->format('M d, Y') : 'Default' }}
                                </td>
                                <td style="padding: 9px 12px; color: #64748b;">{{ $cl->previous_time_range ?: 'None' }}</td>
                                <td style="padding: 9px 12px; font-weight: 600; color: #10b981;">{{ $cl->new_time_range ?: 'Off' }}</td>
                                <td style="padding: 9px 12px; color: #475569;">{{ $cl->reason ?: 'Updated' }}</td>
                                <td style="padding: 9px 12px; color: #64748b;">{{ $cl->changer?->name ?? 'Admin' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px; color: #94a3b8; font-size: 12px;">
                                    No schedule change logs for this employee.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Attendance Action Logs for this employee -->
        <div class="hr-card" style="padding: 0; overflow: hidden;">
            <div style="padding: 14px 18px; border-bottom: 1px solid #f1f5f9;">
                <h3 style="font-family: var(--font-heading); font-size: 14.5px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-shield-check" style="color: #10b981;"></i> Action & Approval Audit Logs
                </h3>
            </div>
            <div class="hr-table-responsive" style="overflow-x: auto;">
                <table class="hr-table" style="width: 100%;">
                    <thead>
                        <tr>
                            <th style="padding: 9px 12px;">Timestamp</th>
                            <th style="padding: 9px 12px;">Action</th>
                            <th style="padding: 9px 12px;">Performed By</th>
                            <th style="padding: 9px 12px;">Details / Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($actionLogs as $al)
                            <tr style="border-bottom: 1px solid #f8fafc; font-size: 12px;">
                                <td style="padding: 9px 12px; color: #64748b; white-space: nowrap;">
                                    {{ $al->created_at ? $al->created_at->format('M d, g:i A') : '—' }}
                                </td>
                                <td style="padding: 9px 12px;">
                                    <span class="hr-badge hr-badge-neutral" style="font-size: 11px; font-weight: 600;">
                                        {{ $al->action }}
                                    </span>
                                </td>
                                <td style="padding: 9px 12px; font-weight: 500;">
                                    {{ $al->actor?->name ?? 'System' }}
                                </td>
                                <td style="padding: 9px 12px; color: #475569; max-width: 180px;">
                                    {{ $al->remarks ?: '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align: center; padding: 20px; color: #94a3b8; font-size: 12px;">
                                    No approval or edit action logs recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
@else
    <!-- Empty State -->
    <div class="hr-card" style="text-align: center; padding: 48px 24px;">
        <div style="width: 56px; height: 56px; border-radius: 50%; background: #ede9fe; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 16px auto;">
            <i class="ph ph-identification-card"></i>
        </div>
        <h3 style="font-family: var(--font-heading); font-size: 17px; font-weight: 700; color: #0f172a; margin: 0 0 6px 0;">
            Select an Employee to View Full Attendance Profile
        </h3>
        <p style="font-size: 13.5px; color: #64748b; max-width: 500px; margin: 0 auto 20px auto;">
            Choose an employee above to inspect their default shift schedule, audit history, overtime approvals, and undertime authorization logs.
        </p>
    </div>
@endif
@endsection

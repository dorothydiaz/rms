@extends('layouts.app')

@section('title', 'Employee Self-Service Dashboard')

@section('content')
<div class="ess-dashboard-container">
    <!-- Hero / Welcome Banner -->
    <div class="card ess-hero-card" style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; padding: 28px; border-radius: 16px; margin-bottom: 24px; box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.25);">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="ess-avatar" style="width: 72px; height: 72px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 3px solid rgba(255,255,255,0.4); display: flex; align-items: center; justify-content: center; font-size: 28px; font-weight: 700;">
                    @if($employee->photo_url)
                        <img src="{{ $employee->photo_url }}" alt="{{ $employee->first_name }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;">
                    @else
                        {{ $employee->initials }}
                    @endif
                </div>
                <div>
                    <h1 style="font-size: 24px; font-weight: 800; margin: 0 0 6px 0; color: #ffffff;">Mabuhay, {{ $employee->first_name }}! 👋</h1>
                    <div style="display: flex; align-items: center; gap: 12px; font-size: 14px; opacity: 0.9; flex-wrap: wrap;">
                        <span><i class="ph ph-identification-badge"></i> {{ $employee->employee_id }}</span>
                        <span>•</span>
                        <span><i class="ph ph-briefcase"></i> {{ $employee->position?->name ?? 'Staff' }}</span>
                        <span>•</span>
                        <span><i class="ph ph-buildings"></i> {{ $employee->branch?->branch_name ?? 'Branch' }}</span>
                        <span>•</span>
                        <span><i class="ph ph-users-three"></i> Dept: {{ $employee->department?->name ?? 'General' }}</span>
                    </div>
                </div>
            </div>
            <div style="text-align: right; background: rgba(255, 255, 255, 0.12); padding: 12px 20px; border-radius: 12px; backdrop-filter: blur(8px);">
                <div style="font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.8;">Reporting Supervisor</div>
                <div style="font-size: 15px; font-weight: 700; margin-top: 2px;">
                    <i class="ph ph-user-check"></i> {{ $employee->supervisor ? $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name : 'Direct to Management' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Metrics Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 18px; margin-bottom: 24px;">
        <!-- Today Shift Card -->
        <div class="card" style="padding: 20px; border-radius: 14px; border-left: 4px solid #3b82f6;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">TODAY'S SHIFT</span>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 6px 0 2px 0; color: #1e293b;">
                        {{ $todaySchedule?->shiftTemplate?->name ?? 'Scheduled Roster' }}
                    </h3>
                    <p style="font-size: 13px; color: #64748b; margin: 0;">
                        @if($todaySchedule?->shiftTemplate)
                            {{ \Carbon\Carbon::parse($todaySchedule->shiftTemplate->start_time)->format('h:i A') }} - {{ \Carbon\Carbon::parse($todaySchedule->shiftTemplate->end_time)->format('h:i A') }}
                        @else
                            Regular Shift (08:00 AM - 05:00 PM)
                        @endif
                    </p>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-clock"></i>
                </div>
            </div>
        </div>

        <!-- Punch Status Card -->
        <div class="card" style="padding: 20px; border-radius: 14px; border-left: 4px solid #10b981;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">DTR STATUS (TODAY)</span>
                    <h3 style="font-size: 18px; font-weight: 700; margin: 6px 0 2px 0; color: #1e293b;">
                        @if($todayRecord?->time_out)
                            <span class="badge" style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 6px;">Shift Complete</span>
                        @elseif($todayRecord?->time_in)
                            <span class="badge" style="background: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 6px;">Clocked In ({{ $todayRecord->time_in }})</span>
                        @else
                            <span class="badge" style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px;">Not Clocked In</span>
                        @endif
                    </h3>
                    <p style="font-size: 13px; color: #64748b; margin: 0;">
                        {{ $todayRecord ? 'Logged via RMS Timekeeping' : 'Pending clock punch' }}
                    </p>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #ecfdf5; color: #10b981; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-fingerprint"></i>
                </div>
            </div>
        </div>

        <!-- MTD Overtime Card -->
        <div class="card" style="padding: 20px; border-radius: 14px; border-left: 4px solid #f59e0b;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">APPROVED OVERTIME (MTD)</span>
                    <h3 style="font-size: 22px; font-weight: 800; margin: 6px 0 2px 0; color: #1e293b;">
                        {{ number_format($totalOtHours, 1) }} <span style="font-size: 14px; font-weight: 500; color: #64748b;">hrs</span>
                    </h3>
                    <p style="font-size: 13px; color: #10b981; margin: 0;">
                        <i class="ph ph-check-circle"></i> DOLE Art. 87 Compliant
                    </p>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #fffbeb; color: #f59e0b; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-timer"></i>
                </div>
            </div>
        </div>

        <!-- Leave Credits Card -->
        <div class="card" style="padding: 20px; border-radius: 14px; border-left: 4px solid #8b5cf6;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <span style="font-size: 13px; font-weight: 600; color: #64748b;">AVAILABLE LEAVE BALANCE</span>
                    <h3 style="font-size: 22px; font-weight: 800; margin: 6px 0 2px 0; color: #1e293b;">
                        {{ number_format($leaveBalances->sum('remaining_days'), 1) }} <span style="font-size: 14px; font-weight: 500; color: #64748b;">days</span>
                    </h3>
                    <p style="font-size: 13px; color: #8b5cf6; margin: 0;">
                        SIL, Vacation & Sick Leave
                    </p>
                </div>
                <div style="width: 42px; height: 42px; border-radius: 10px; background: #f5f3ff; color: #8b5cf6; display: flex; align-items: center; justify-content: center; font-size: 22px;">
                    <i class="ph ph-calendar-plus"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px; background: #f8fafc; border: 1px dashed #cbd5e1;">
        <div style="font-size: 14px; font-weight: 700; color: #334155; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-lightning" style="color: #f59e0b;"></i> QUICK ACTIONS & SELF-SERVICE FILING
        </div>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
            <a href="{{ route('ess.leave') }}" class="btn btn-outline" style="background: #ffffff; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-calendar-plus" style="color: #2563eb;"></i> File Leave Request
            </a>
            <a href="{{ route('ess.requests') }}?new=ot" class="btn btn-outline" style="background: #ffffff; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-timer" style="color: #f59e0b;"></i> File Overtime
            </a>
            <a href="{{ route('ess.attendance') }}?action=dispute" class="btn btn-outline" style="background: #ffffff; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-warning-circle" style="color: #ef4444;"></i> Attendance Correction
            </a>
            <a href="{{ route('ess.requests') }}?new=ob" class="btn btn-outline" style="background: #ffffff; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-navigation-arrow" style="color: #10b981;"></i> Official Business (OB)
            </a>
            <a href="{{ route('ess.payroll') }}" class="btn btn-outline" style="background: #ffffff; display: inline-flex; align-items: center; gap: 8px;">
                <i class="ph ph-receipt" style="color: #8b5cf6;"></i> View Latest Payslip
            </a>
        </div>
    </div>

    <!-- Two Column Dashboard Layout -->
    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Column 1: Recent Requests & Transactions -->
        <div class="card" style="padding: 24px; border-radius: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="font-size: 16px; font-weight: 700; margin: 0; color: #1e293b;">
                    <i class="ph ph-clock-counter-clockwise"></i> My Recent Requests
                </h3>
                <a href="{{ route('ess.requests') }}" style="font-size: 13px; color: #2563eb; font-weight: 600; text-decoration: none;">View All Requests →</a>
            </div>

            @if($recentRequests->isEmpty())
                <div style="text-align: center; padding: 40px 20px; color: #94a3b8;">
                    <i class="ph ph-tray" style="font-size: 40px; margin-bottom: 8px; display: block;"></i>
                    No requests submitted yet. Use Quick Actions above to file a request.
                </div>
            @else
                <div style="overflow-x: auto;">
                    <table class="data-table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="border-bottom: 2px solid #f1f5f9; text-align: left; font-size: 12px; color: #64748b;">
                                <th style="padding: 10px;">Request No.</th>
                                <th style="padding: 10px;">Type</th>
                                <th style="padding: 10px;">Date</th>
                                <th style="padding: 10px;">Details</th>
                                <th style="padding: 10px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentRequests as $req)
                                <tr style="border-bottom: 1px solid #f8fafc; font-size: 13px;">
                                    <td style="padding: 12px 10px; font-weight: 600; color: #1e293b;">{{ $req->request_number }}</td>
                                    <td style="padding: 12px 10px;">
                                        <span class="badge" style="padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; background: #f1f5f9; color: #334155;">
                                            {{ str_replace('_', ' ', $req->request_type) }}
                                        </span>
                                    </td>
                                    <td style="padding: 12px 10px; color: #64748b;">{{ $req->start_date?->format('M d, Y') }}</td>
                                    <td style="padding: 12px 10px; color: #475569; max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        {{ $req->reason }}
                                    </td>
                                    <td style="padding: 12px 10px;">
                                        @if($req->status === 'APPROVED')
                                            <span style="color: #166534; background: #dcfce7; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Approved</span>
                                        @elseif($req->status === 'REJECTED')
                                            <span style="color: #991b1b; background: #fee2e2; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Rejected</span>
                                        @elseif($req->status === 'CANCELLED')
                                            <span style="color: #475569; background: #f1f5f9; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Cancelled</span>
                                        @else
                                            <span style="color: #92400e; background: #fef3c7; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 600;">Pending Approval</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <!-- Column 2: Upcoming Holidays & Announcements -->
        <div>
            <!-- Upcoming Holidays -->
            <div class="card" style="padding: 24px; border-radius: 14px; margin-bottom: 20px;">
                <h3 style="font-size: 15px; font-weight: 700; margin: 0 0 16px 0; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                    <i class="ph ph-calendar" style="color: #8b5cf6;"></i> Philippine Holidays (DOLE)
                </h3>
                @if($upcomingHolidays->isEmpty())
                    <p style="font-size: 13px; color: #94a3b8; margin: 0;">No upcoming holidays logged.</p>
                @else
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach($upcomingHolidays as $h)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #f8fafc; border-radius: 8px;">
                                <div>
                                    <div style="font-size: 13px; font-weight: 700; color: #1e293b;">{{ $h->name }}</div>
                                    <div style="font-size: 12px; color: #64748b;">{{ \Carbon\Carbon::parse($h->date)->format('F d, Y (D)') }}</div>
                                </div>
                                <span class="badge" style="font-size: 11px; padding: 3px 8px; border-radius: 4px; font-weight: 600; {{ $h->type === 'Regular' ? 'background: #fee2e2; color: #991b1b;' : 'background: #fef3c7; color: #92400e;' }}">
                                    {{ $h->type === 'Regular' ? 'Regular (200%)' : 'Special (130%)' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Latest Payslip Card -->
            @if($latestPayslip)
                <div class="card" style="padding: 24px; border-radius: 14px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <span style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">LATEST PAYSLIP</span>
                        <span class="badge" style="background: #dcfce7; color: #166534; font-size: 11px; font-weight: 700; padding: 2px 6px; border-radius: 4px;">Finalized</span>
                    </div>
                    <div style="font-size: 13px; color: #475569;">Period: {{ $latestPayslip->payrollPeriod?->start_date?->format('M d') }} - {{ $latestPayslip->payrollPeriod?->end_date?->format('M d, Y') }}</div>
                    <div style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 10px 0 14px 0;">
                        ₱{{ number_format($latestPayslip->net_pay, 2) }}
                    </div>
                    <a href="{{ route('ess.payroll.payslip', $latestPayslip->id) }}" class="btn btn-sm btn-outline" style="width: 100%; text-align: center; justify-content: center; background: #ffffff;">
                        <i class="ph ph-receipt"></i> Inspect Breakdown
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@extends('layouts.ess')

@section('title', 'Employee Dashboard')

@section('content')
<div class="ess-dashboard-container">

    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-squares-four"></i>
                <span>Employee Self-Service</span>
            </h1>
            <p class="hr-page-subtitle">Welcome back, {{ $employee->first_name }}! Real-time workforce attendance, Philippine statutory benefits, biometric logs, and self-service transactions.</p>
        </div>
        <div class="hr-page-actions">
            <a href="{{ route('ess.requests') }}" class="hr-btn hr-btn-primary">
                <i class="ph ph-plus-circle"></i>
                <span>New Request</span>
            </a>
            <a href="{{ route('ess.dtr') }}" class="hr-btn hr-btn-secondary">
                <i class="ph ph-clock"></i>
                <span>View Timesheet</span>
            </a>
        </div>
    </div>

    <!-- Top Hero Card: Employee Profile & Status -->
    <div class="ess-card">
        <div class="ess-card-accent-strip"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:20px;">
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="width:68px;height:68px;border-radius:18px;background:var(--ess-primary-gradient);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;overflow:hidden;box-shadow:0 8px 20px rgba(168,85,247,0.32);flex-shrink:0;">
                    {{ strtoupper(substr($employee->first_name ?? 'E', 0, 1) . substr($employee->last_name ?? 'M', 0, 1)) }}
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <h2 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:800;margin:0;color:var(--ess-text-main);">
                            @php
                                $hour = now()->hour;
                                $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
                            @endphp
                            {{ $greeting }}, {{ $employee->first_name }}!
                        </h2>
                        <span class="ess-badge ess-badge-success">{{ $employee->employment_status ?? 'Active Regular' }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:12px;margin-top:6px;font-size:12.5px;color:var(--ess-text-secondary);flex-wrap:wrap;">
                        <span style="background:#f3e8ff;color:#9333ea;border:1px solid rgba(168,85,247,0.3);padding:2px 8px;border-radius:6px;font-family:monospace;font-weight:700;">
                            <i class="ph ph-identification-badge"></i> {{ $employee->employee_code }}
                        </span>
                        <span>&bull;</span>
                        <span><i class="ph ph-briefcase"></i> {{ $employee->position->position_title ?? 'Employee' }}</span>
                        <span>&bull;</span>
                        <span><i class="ph ph-buildings"></i> {{ $employee->department->department_name ?? 'Operations' }}</span>
                        <span>&bull;</span>
                        <span><i class="ph ph-map-pin"></i> {{ $employee->branch->branch_name ?? 'Store Branch' }}</span>
                    </div>
                </div>
            </div>

            <!-- Right: Supervisor & Live Philippine Clock -->
            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                <!-- Live PST Clock Pill -->
                <div style="background:#ffffff;border:1px solid var(--ess-border);padding:8px 14px;border-radius:12px;display:flex;align-items:center;gap:10px;box-shadow:var(--ess-shadow-sm);">
                    <div class="hr-bento-icon-box purple" style="width:32px;height:32px;font-size:16px;">
                        <i class="ph ph-clock"></i>
                    </div>
                    <div>
                        <div style="font-size:10px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Philippine Standard Time</div>
                        <div id="liveClock" style="font-size:13px;font-weight:800;color:var(--ess-text-main);font-family:monospace;">{{ now()->timezone('Asia/Manila')->format('h:i:s A') }}</div>
                    </div>
                </div>

                <!-- Supervisor Pill -->
                <div style="background:#ffffff;border:1px solid var(--ess-border);padding:8px 14px;border-radius:12px;display:flex;align-items:center;gap:10px;box-shadow:var(--ess-shadow-sm);">
                    <div class="hr-bento-icon-box blue" style="width:32px;height:32px;font-size:16px;">
                        <i class="ph ph-user-circle-gear"></i>
                    </div>
                    <div>
                        <div style="font-size:10px;font-weight:700;text-transform:uppercase;color:var(--ess-text-muted);letter-spacing:0.04em;">Immediate Supervisor</div>
                        <div style="font-size:12.5px;font-weight:700;color:var(--ess-text-main);">
                            {{ $employee->supervisor ? ($employee->supervisor->first_name . ' ' . $employee->supervisor->last_name) : 'Management Executive' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics Grid (HR Bento Style) -->
    <div class="ess-grid-4">
        <!-- Metric 1: Today's Shift -->
        <a href="{{ route('ess.schedule') }}" class="ess-kpi-card">
            <div class="hr-bento-icon-box indigo">
                <i class="ph ph-calendar-check"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Today's Schedule</span>
                <span class="ess-kpi-val" style="font-size:16px;">
                    @if($todaySchedule && $todaySchedule->shiftTemplate)
                        {{ \Carbon\Carbon::parse($todaySchedule->shiftTemplate->start_time)->format('h:i A') }} – {{ \Carbon\Carbon::parse($todaySchedule->shiftTemplate->end_time)->format('h:i A') }}
                    @else
                        08:00 AM – 05:00 PM
                    @endif
                </span>
                <span style="font-size:11px;color:#4f46e5;font-weight:600;margin-top:4px;">Regular Working Day</span>
            </div>
            <i class="ph ph-caret-right hr-kpi-chevron" style="align-self:center;"></i>
        </a>

        <!-- Metric 2: Live Timekeeping Status -->
        <a href="{{ route('ess.dtr') }}" class="ess-kpi-card">
            <div class="hr-bento-icon-box green">
                <i class="ph ph-clock"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Biometric Status</span>
                <span class="ess-kpi-val" style="font-size:16px;">
                    {{ $todayRecord && $todayRecord->time_in ? 'In: ' . \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') : 'Not Yet In' }}
                </span>
                <span class="ess-badge {{ $todayRecord && $todayRecord->time_in ? 'ess-badge-success' : 'ess-badge-warning' }}" style="align-self:flex-start;margin-top:4px;">
                    {{ $todayRecord && $todayRecord->time_in ? 'Present & On Duty' : 'Awaiting Check-in' }}
                </span>
            </div>
            <i class="ph ph-caret-right hr-kpi-chevron" style="align-self:center;"></i>
        </a>

        <!-- Metric 3: Leave Credits -->
        <a href="{{ route('ess.leave') }}" class="ess-kpi-card">
            <div class="hr-bento-icon-box purple">
                <i class="ph ph-airplane-tilt"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Leave Credits</span>
                <span class="ess-kpi-val">{{ number_format($totalLeaveCredits > 0 ? $totalLeaveCredits : 5.0, 1) }} Days</span>
                <span style="font-size:11px;color:#9333ea;font-weight:600;margin-top:4px;">DOLE SIL & Vacation</span>
            </div>
            <i class="ph ph-caret-right hr-kpi-chevron" style="align-self:center;"></i>
        </a>

        <!-- Metric 4: Latest Payslip -->
        <a href="{{ route('ess.payroll') }}" class="ess-kpi-card">
            <div class="hr-bento-icon-box blue">
                <i class="ph ph-wallet"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Latest Net Pay</span>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span id="maskedNetPay" class="ess-kpi-val" style="font-size:20px;">₱ •••••••</span>
                    <button type="button" onclick="event.preventDefault(); toggleNetPayMask()" style="background:none;border:none;color:var(--ess-text-muted);cursor:pointer;padding:2px;" title="Show or hide net pay amount">
                        <i id="eyeIcon" class="ph ph-eye-slash" style="font-size:18px;"></i>
                    </button>
                </div>
                <span style="font-size:11px;color:var(--ess-text-muted);font-weight:500;margin-top:2px;">
                    {{ $latestPayslip->payrollPeriod?->name ?? 'Latest Cutoff Payout' }}
                </span>
            </div>
            <i class="ph ph-caret-right hr-kpi-chevron" style="align-self:center;"></i>
        </a>
    </div>

    <!-- 2 Column Section: Today's Punch Timeline & Quick Shortcuts Hub -->
    <div class="ess-grid-2">
        <!-- Punch Timeline Card -->
        <div class="ess-card">
            <div class="ess-card-header">
                <h2 class="ess-card-title">
                    <div class="hr-bento-icon-box purple" style="width:30px;height:30px;font-size:16px;">
                        <i class="ph ph-timer"></i>
                    </div>
                    <span>Today's Biometric Log</span>
                </h2>
                <span style="font-size:12px;color:var(--ess-text-muted);font-weight:600;">{{ now()->format('l, F j, Y') }}</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;text-align:center;">
                <div style="background:#f8fafc;padding:14px 10px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:10.5px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Time-In 1</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->time_in ? \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px 10px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:10.5px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Lunch Out</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->break_out ? \Carbon\Carbon::parse($todayRecord->break_out)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px 10px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:10.5px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Lunch In</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->break_in ? \Carbon\Carbon::parse($todayRecord->break_in)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px 10px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:10.5px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Time-Out 2</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->time_out ? \Carbon\Carbon::parse($todayRecord->time_out)->format('h:i A') : '—' }}
                    </div>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:12px;color:var(--ess-text-muted);">DOLE Non-destructive timekeeping record</span>
                <a href="{{ route('ess.dtr') }}" style="font-size:12.5px;font-weight:700;color:#9333ea;text-decoration:none;display:flex;align-items:center;gap:4px;">
                    <span>View DTR & Timesheet</span>
                    <i class="ph ph-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Quick Actions Shortcuts Grid -->
        <div class="ess-card">
            <div class="ess-card-header">
                <h2 class="ess-card-title">
                    <div class="hr-bento-icon-box orange" style="width:30px;height:30px;font-size:16px;">
                        <i class="ph ph-lightning"></i>
                    </div>
                    <span>Quick Self-Service Actions</span>
                </h2>
            </div>
            <div style="display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;">
                <a href="{{ route('ess.requests') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box indigo" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-timer"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">File Overtime</span>
                </a>
                <a href="{{ route('ess.leave') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box purple" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-airplane-tilt"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">Apply Leave</span>
                </a>
                <a href="{{ route('ess.dtr') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box green" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-check-circle"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">DTR Correction</span>
                </a>
                <a href="{{ route('ess.payroll') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box blue" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-receipt"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">View Payslips</span>
                </a>
                <a href="{{ route('ess.profile') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box orange" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-file-text"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">Request COE</span>
                </a>
                <a href="{{ route('ess.helpdesk') }}" style="text-decoration:none;background:#f8fafc;border:1px solid var(--ess-border);border-radius:12px;padding:12px 8px;text-align:center;color:var(--ess-text-main);transition:all 0.15s ease;" onmouseover="this.style.borderColor='rgba(168,85,247,0.4)';this.style.transform='translateY(-2px)'" onmouseout="this.style.borderColor='var(--ess-border)';this.style.transform='none'">
                    <div class="hr-bento-icon-box pink" style="margin:0 auto 6px;width:36px;height:36px;font-size:18px;">
                        <i class="ph ph-chat-circle-dots"></i>
                    </div>
                    <span style="font-size:11.5px;font-weight:700;display:block;">HR Helpdesk</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Company Announcements & Labor Advisory Notices -->
    <div class="ess-card" style="margin-bottom:22px;">
        <div class="ess-card-header">
            <h2 class="ess-card-title">
                <div class="hr-bento-icon-box orange" style="width:30px;height:30px;font-size:16px;">
                    <i class="ph ph-megaphone-simple"></i>
                </div>
                <span>Company Announcements & DOLE Labor Standards</span>
            </h2>
        </div>
        @if($announcements->isNotEmpty())
            <div style="display:flex;flex-direction:column;gap:12px;">
                @foreach($announcements as $ann)
                    <div style="padding:14px 16px;border-left:3.5px solid #9333ea;background:#f8fafc;border-radius:0 10px 10px 0;">
                        <div style="font-weight:700;font-size:13.5px;color:var(--ess-text-main);">{{ $ann->title }}</div>
                        <div style="font-size:12.5px;color:var(--ess-text-secondary);margin-top:4px;">{{ $ann->content }}</div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div style="padding:14px 16px;background:#f8fafc;border-radius:10px;border-left:3.5px solid #16a34a;">
                    <div style="font-weight:700;font-size:13px;color:#16a34a;">DOLE Statutory Notice: Regular Holiday Pay</div>
                    <div style="font-size:12px;color:var(--ess-text-secondary);margin-top:4px;">
                        Regular holidays are compensated at 200% of basic daily rate if worked, or 100% if unworked. Special non-working days are compensated at 130%.
                    </div>
                </div>
                <div style="padding:14px 16px;background:#f8fafc;border-radius:10px;border-left:3.5px solid #9333ea;">
                    <div style="font-weight:700;font-size:13px;color:#9333ea;">Night Shift Differential (NSD) Reminder</div>
                    <div style="font-size:12px;color:var(--ess-text-secondary);margin-top:4px;">
                        Pursuant to Article 86 of the Philippine Labor Code, work rendered between 10:00 PM and 6:00 AM receives an additional 10% premium.
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Recent ESS Submissions Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h2 class="ess-card-title">
                <div class="hr-bento-icon-box purple" style="width:30px;height:30px;font-size:16px;">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <span>Recent Requests & Approval Status</span>
            </h2>
            <a href="{{ route('ess.requests') }}" style="font-size:12.5px;font-weight:700;color:#9333ea;text-decoration:none;">View All Requests &rarr;</a>
        </div>
        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Type</th>
                        <th>Reason / Scope</th>
                        <th>Submitted At</th>
                        <th>Approval Step</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentRequests as $req)
                        <tr>
                            <td><strong style="color:#9333ea;font-family:monospace;">{{ $req->request_number }}</strong></td>
                            <td><span class="ess-badge ess-badge-primary">{{ str_replace('_', ' ', $req->request_type) }}</span></td>
                            <td>{{ Str::limit($req->reason, 45) }}</td>
                            <td>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                            <td>Level {{ $req->approval_step }} (Supervisor)</td>
                            <td>
                                @if($req->status === 'APPROVED')
                                    <span class="ess-badge ess-badge-success">Approved</span>
                                @elseif($req->status === 'PENDING')
                                    <span class="ess-badge ess-badge-warning">Pending Review</span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="ess-badge ess-badge-danger">Rejected</span>
                                @elseif($req->status === 'CANCELLED')
                                    <span class="ess-badge ess-badge-neutral">Cancelled</span>
                                @else
                                    <span class="ess-badge ess-badge-info">{{ $req->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:32px;color:var(--ess-text-muted);">
                                No recent requests filed. Click <strong>"New Request"</strong> in the topbar to file leave, overtime, or attendance corrections.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let isMasked = true;
    const realNetPay = '₱ {{ number_format($latestPayslip->net_pay ?? 14501.88, 2) }}';

    function toggleNetPayMask() {
        const span = document.getElementById('maskedNetPay');
        const icon = document.getElementById('eyeIcon');
        if (isMasked) {
            span.textContent = realNetPay;
            icon.className = 'ph ph-eye';
            isMasked = false;
        } else {
            span.textContent = '₱ •••••••';
            icon.className = 'ph ph-eye-slash';
            isMasked = true;
        }
    }

    // Live Clock Updater
    function updateClock() {
        const clockEl = document.getElementById('liveClock');
        if (clockEl) {
            const now = new Date();
            clockEl.textContent = now.toLocaleTimeString('en-US', { timeZone: 'Asia/Manila', hour12: true });
        }
    }
    setInterval(updateClock, 1000);
</script>
@endpush
@endsection

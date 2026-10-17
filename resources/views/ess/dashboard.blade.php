@extends('layouts.ess')

@section('title', 'Employee Dashboard')

@section('content')
<div class="ess-dashboard-container">
    <!-- Top Hero Card: Employee Profile & Status -->
    <div class="ess-card">
        <div class="ess-card-accent-strip"></div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
            <div style="display:flex;align-items:center;gap:18px;">
                <div style="width:68px;height:68px;border-radius:16px;background:var(--ess-primary-gradient);color:#fff;display:flex;align-items:center;justify-content:center;font-size:24px;font-weight:800;overflow:hidden;box-shadow:0 6px 18px rgba(168,85,247,0.3);">
                    @if(!empty($employee->photo_url))
                        <img src="{{ $employee->photo_url }}" alt="" style="width:100%;height:100%;object-fit:cover;">
                    @else
                        {{ $employee->initials ?? 'EM' }}
                    @endif
                </div>
                <div>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <h2 style="font-family:'Outfit',sans-serif;font-size:22px;font-weight:800;margin:0;color:var(--ess-text-main);">
                            {{ $employee->first_name }} {{ $employee->last_name }}
                        </h2>
                        <span class="ess-badge ess-badge-success">{{ $employee->employment_status ?? 'Active' }}</span>
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;margin-top:6px;font-size:12.5px;color:var(--ess-text-secondary);flex-wrap:wrap;">
                        <span><i class="ph ph-identification-badge" style="color:var(--ess-primary);"></i> <strong>{{ $employee->employee_id }}</strong></span>
                        <span>•</span>
                        <span><i class="ph ph-briefcase"></i> {{ $employee->position->name ?? 'Staff' }}</span>
                        <span>•</span>
                        <span><i class="ph ph-buildings"></i> {{ $employee->department->name ?? 'Department' }}</span>
                        <span>•</span>
                        <span><i class="ph ph-map-pin"></i> {{ $employee->branch->branch_name ?? 'Branch' }}</span>
                    </div>
                </div>
            </div>

            <!-- Supervisor Pill -->
            <div style="background:#f8fafc;border:1px solid var(--ess-border);padding:10px 16px;border-radius:12px;display:flex;align-items:center;gap:10px;">
                <div style="width:36px;height:36px;border-radius:50%;background:rgba(99,102,241,0.12);color:#6366f1;display:flex;align-items:center;justify-content:center;font-size:18px;">
                    <i class="ph ph-user-circle-gear"></i>
                </div>
                <div>
                    <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;color:var(--ess-text-muted);letter-spacing:0.04em;">Immediate Supervisor</div>
                    <div style="font-size:13px;font-weight:700;color:var(--ess-text-main);">
                        {{ $employee->supervisor ? ($employee->supervisor->first_name . ' ' . $employee->supervisor->last_name) : 'Management Executive' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 KPI Metrics -->
    <div class="ess-grid-4">
        <!-- Metric 1: Today's Shift -->
        <div class="ess-kpi-card">
            <div class="ess-kpi-icon" style="background:rgba(168,85,247,0.12);color:#a855f7;">
                <i class="ph ph-calendar-check"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Today's Schedule</span>
                <span class="ess-kpi-val" style="font-size:16px;">08:00 AM – 05:00 PM</span>
                <span style="font-size:11px;color:var(--ess-success);font-weight:600;margin-top:2px;">Regular Working Day</span>
            </div>
        </div>

        <!-- Metric 2: Live Timekeeping Status -->
        <div class="ess-kpi-card">
            <div class="ess-kpi-icon" style="background:rgba(16,185,129,0.12);color:#059669;">
                <i class="ph ph-clock"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Punch Status</span>
                <span class="ess-kpi-val" style="font-size:16px;">
                    {{ $todayRecord && $todayRecord->time_in ? 'Time-in: ' . \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') : 'Not Yet In' }}
                </span>
                <span class="ess-badge {{ $todayRecord && $todayRecord->time_in ? 'ess-badge-success' : 'ess-badge-warning' }}" style="align-self:flex-start;margin-top:4px;">
                    {{ $todayRecord && $todayRecord->time_in ? 'On Duty' : 'Awaiting Check-in' }}
                </span>
            </div>
        </div>

        <!-- Metric 3: Leave Credits -->
        <div class="ess-kpi-card">
            <div class="ess-kpi-icon" style="background:rgba(236,72,153,0.12);color:#ec4899;">
                <i class="ph ph-airplane-tilt"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Leave Credits</span>
                <span class="ess-kpi-val">{{ number_format($totalLeaveCredits > 0 ? $totalLeaveCredits : 5.0, 1) }} Days</span>
                <span style="font-size:11px;color:var(--ess-primary);font-weight:600;margin-top:2px;">Available to File</span>
            </div>
        </div>

        <!-- Metric 4: Latest Payslip -->
        <div class="ess-kpi-card">
            <div class="ess-kpi-icon" style="background:rgba(14,165,233,0.12);color:#0284c7;">
                <i class="ph ph-wallet"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Latest Net Pay</span>
                <div style="display:flex;align-items:center;gap:8px;">
                    <span id="maskedNetPay" class="ess-kpi-val">₱ •••••••</span>
                    <button type="button" onclick="toggleNetPayMask()" style="background:none;border:none;color:var(--ess-text-muted);cursor:pointer;padding:2px;">
                        <i id="eyeIcon" class="ph ph-eye-slash" style="font-size:18px;"></i>
                    </button>
                </div>
                <span style="font-size:11px;color:var(--ess-text-muted);font-weight:500;">Oct 1 – 15 Cutoff</span>
            </div>
        </div>
    </div>

    <!-- 2 Column Section: Today's Punch Timeline & Quick Actions -->
    <div class="ess-grid-2">
        <!-- Punch Timeline Card -->
        <div class="ess-card">
            <div class="ess-card-header">
                <h3 class="ess-card-title"><i class="ph ph-timer" style="color:var(--ess-primary);"></i> Today's Biometric Timeline</h3>
                <span style="font-size:12px;color:var(--ess-text-muted);font-weight:600;">{{ now()->format('l, F j, Y') }}</span>
            </div>
            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;text-align:center;">
                <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:11px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Time-In 1</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->time_in ? \Carbon\Carbon::parse($todayRecord->time_in)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:11px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Break Out</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->break_out ? \Carbon\Carbon::parse($todayRecord->break_out)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:11px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Break In</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->break_in ? \Carbon\Carbon::parse($todayRecord->break_in)->format('h:i A') : '—' }}
                    </div>
                </div>
                <div style="background:#f8fafc;padding:14px;border-radius:12px;border:1px solid var(--ess-border);">
                    <div style="font-size:11px;font-weight:700;color:var(--ess-text-muted);text-transform:uppercase;">Time-Out 2</div>
                    <div style="font-size:14px;font-weight:800;color:var(--ess-text-main);margin-top:4px;">
                        {{ $todayRecord && $todayRecord->time_out ? \Carbon\Carbon::parse($todayRecord->time_out)->format('h:i A') : '—' }}
                    </div>
                </div>
            </div>
            <div style="margin-top:16px;display:flex;justify-content:flex-end;">
                <a href="{{ route('ess.dtr') }}" style="font-size:12.5px;font-weight:700;color:var(--ess-primary);text-decoration:none;display:flex;align-items:center;gap:4px;">
                    <span>View Complete Monthly DTR</span>
                    <i class="ph ph-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Recent Announcements & Holiday Reminders -->
        <div class="ess-card">
            <div class="ess-card-header">
                <h3 class="ess-card-title"><i class="ph ph-megaphone-simple" style="color:#d97706;"></i> Company Announcements & DOLE Notices</h3>
            </div>
            @if($announcements->isNotEmpty())
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @foreach($announcements as $ann)
                        <div style="padding:12px 14px;border-left:3px solid var(--ess-primary);background:#f8fafc;border-radius:0 8px 8px 0;">
                            <div style="font-weight:700;font-size:13px;color:var(--ess-text-main);">{{ $ann->title }}</div>
                            <div style="font-size:12px;color:var(--ess-text-secondary);margin-top:4px;">{{ $ann->content }}</div>
                        </div>
                    @endforeach
                </div>
            @else
                <div style="padding:14px;background:#f8fafc;border-radius:10px;border-left:3px solid #059669;">
                    <div style="font-weight:700;font-size:13px;color:#059669;">DOLE Statutory Notice: Regular Holiday Pay</div>
                    <div style="font-size:12px;color:var(--ess-text-secondary);margin-top:4px;">
                        Regular holidays are compensated at 200% of basic daily rate if worked, or 100% if unworked. Special non-working days are compensated at 130%.
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Recent ESS Submissions Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h3 class="ess-card-title"><i class="ph ph-clock-counter-clockwise" style="color:var(--ess-primary);"></i> Recent Requests & Approval Status</h3>
            <a href="{{ route('ess.requests') }}" style="font-size:12.5px;font-weight:700;color:var(--ess-primary);text-decoration:none;">View All Requests &rarr;</a>
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
                            <td><strong style="color:var(--ess-primary);">{{ $req->request_number }}</strong></td>
                            <td><span class="ess-badge ess-badge-info">{{ str_replace('_', ' ', $req->request_type) }}</span></td>
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
                                @else
                                    <span class="ess-badge ess-badge-info">{{ $req->status }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:30px;color:var(--ess-text-muted);">
                                No recent requests filed. Click <strong>"New Request"</strong> above to file leave, overtime, or attendance corrections.
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
    function toggleNetPayMask() {
        const span = document.getElementById('maskedNetPay');
        const icon = document.getElementById('eyeIcon');
        if (isMasked) {
            span.textContent = '₱ 14,850.50';
            icon.className = 'ph ph-eye';
            isMasked = false;
        } else {
            span.textContent = '₱ •••••••';
            icon.className = 'ph ph-eye-slash';
            isMasked = true;
        }
    }
</script>
@endpush
@endsection

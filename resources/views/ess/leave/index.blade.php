@extends('layouts.ess')

@section('title', 'Leave Management — Statutory & Company Benefits')

@section('content')
<div class="ess-leave-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-airplane-tilt"></i>
                <span>Leave Management & DOLE Statutory Entitlements</span>
            </h1>
            <p class="hr-page-subtitle">
                Compliant with Philippine Labor Code (Art. 95 SIL), RA 11210 (Maternity), RA 8187 (Paternity), and RA 8972 (Solo Parent)
            </p>
        </div>

        <div class="hr-page-actions">
            <button type="button" class="hr-btn hr-btn-primary" onclick="openFileLeaveModal()">
                <i class="ph ph-plus-circle"></i>
                <span>File New Leave</span>
            </button>
        </div>
    </div>

    <!-- Statutory & Company Balance Cards -->
    <div class="ess-grid-4">
        <!-- SIL Card -->
        <div class="ess-kpi-card" style="border-top:3px solid #ec4899;">
            <div class="ess-kpi-info" style="width:100%;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="ess-kpi-label">Service Incentive Leave (SIL)</span>
                    @if($isEligibleForSil)
                        <span class="ess-badge ess-badge-success">DOLE Eligible</span>
                    @else
                        <span class="ess-badge ess-badge-warning">< 1 Yr Tenure</span>
                    @endif
                </div>
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px;">
                    <span class="ess-kpi-val" style="color:#ec4899;">5.0 Days</span>
                    <span style="font-size:11px;color:var(--ess-text-muted);">Art. 95 Labor Code</span>
                </div>
            </div>
        </div>

        <!-- Vacation Leave Card -->
        <div class="ess-kpi-card" style="border-top:3px solid #a855f7;">
            <div class="ess-kpi-info" style="width:100%;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="ess-kpi-label">Vacation Leave (VL)</span>
                    <span class="ess-badge ess-badge-info">Company Benefit</span>
                </div>
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px;">
                    <span class="ess-kpi-val" style="color:#a855f7;">12.0 Days</span>
                    <span style="font-size:11px;color:var(--ess-text-muted);">Annual Grant</span>
                </div>
            </div>
        </div>

        <!-- Sick Leave Card -->
        <div class="ess-kpi-card" style="border-top:3px solid #059669;">
            <div class="ess-kpi-info" style="width:100%;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="ess-kpi-label">Sick Leave (SL)</span>
                    <span class="ess-badge ess-badge-success">Paid</span>
                </div>
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px;">
                    <span class="ess-kpi-val" style="color:#059669;">10.0 Days</span>
                    <span style="font-size:11px;color:var(--ess-text-muted);">MedCert > 2 Days</span>
                </div>
            </div>
        </div>

        <!-- Solo Parent / Special Leave Card -->
        <div class="ess-kpi-card" style="border-top:3px solid #0284c7;">
            <div class="ess-kpi-info" style="width:100%;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span class="ess-kpi-label">Special Statutory</span>
                    <span class="ess-badge ess-badge-info">RA 11210 / 8972</span>
                </div>
                <div style="display:flex;align-items:baseline;justify-content:space-between;margin-top:8px;">
                    <span class="ess-kpi-val" style="color:#0284c7;">7.0 Days</span>
                    <span style="font-size:11px;color:var(--ess-text-muted);">Solo Parent / Paternity</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Leave Request History Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h3 class="ess-card-title"><i class="ph ph-clock-counter-clockwise" style="color:var(--ess-primary);"></i> My Leave Request History</h3>
        </div>

        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Inclusive Dates</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Approver</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>
                                <strong>{{ $req->leaveType->name ?? 'Leave' }}</strong>
                                <span style="font-size:11px;color:var(--ess-text-muted);display:block;">{{ $req->leaveType->code ?? '' }}</span>
                            </td>
                            <td>
                                {{ \Carbon\Carbon::parse($req->start_date)->format('M d, Y') }} &rarr; {{ \Carbon\Carbon::parse($req->end_date)->format('M d, Y') }}
                            </td>
                            <td><strong>{{ number_format((float)($req->number_of_days ?? 1), 1) }}</strong></td>
                            <td>{{ Str::limit($req->reason, 40) }}</td>
                            <td>
                                @if($req->approver)
                                    {{ $req->approver->name }}
                                @else
                                    <span style="color:var(--ess-text-muted);">Awaiting Assignment</span>
                                @endif
                            </td>
                            <td>
                                @if($req->status === 'Approved')
                                    <span class="ess-badge ess-badge-success">Approved</span>
                                @elseif($req->status === 'Pending')
                                    <span class="ess-badge ess-badge-warning">Pending Review</span>
                                @elseif($req->status === 'Rejected')
                                    <span class="ess-badge ess-badge-danger">Rejected</span>
                                @elseif($req->status === 'Cancelled')
                                    <span class="ess-badge" style="background:#f1f5f9;color:#64748b;">Cancelled</span>
                                @else
                                    <span class="ess-badge ess-badge-info">{{ $req->status }}</span>
                                @endif
                            </td>
                            <td>
                                @if($req->status === 'Pending')
                                    <form method="POST" action="{{ route('ess.leave.cancel', $req->getKey()) }}" style="margin:0;" onsubmit="return confirm('Are you sure you want to cancel this leave request?');">
                                        @csrf
                                        <button type="submit" class="ess-badge ess-badge-danger" style="cursor:pointer;border:none;">Cancel</button>
                                    </form>
                                @else
                                    <span style="font-size:11px;color:var(--ess-text-muted);">&mdash;</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--ess-text-muted);">
                                No leave requests on record. Click <strong>"File New Leave"</strong> above to file an application.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- File Leave Modal -->
<div id="fileLeaveModal" class="ess-modal-backdrop">
    <div class="ess-modal">
        <form method="POST" action="{{ route('ess.leave.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="ess-modal-header">
                <h3 class="ess-modal-title">File Leave Request</h3>
                <button type="button" class="ess-modal-close" onclick="closeFileLeaveModal()">&times;</button>
            </div>
            <div class="ess-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Leave Type *</label>
                    <select name="leave_type_id" class="ess-form-control" required>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->getKey() }}">
                                {{ $lt->name }} ({{ $lt->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div class="ess-form-group">
                        <label class="ess-form-label">Start Date *</label>
                        <input type="date" name="start_date" class="ess-form-control" value="{{ now()->addDays(1)->toDateString() }}" required>
                    </div>
                    <div class="ess-form-group">
                        <label class="ess-form-label">End Date *</label>
                        <input type="date" name="end_date" class="ess-form-control" value="{{ now()->addDays(1)->toDateString() }}" required>
                    </div>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Duration Type *</label>
                    <select name="duration_type" class="ess-form-control" required>
                        <option value="Full Day">Full Day (8 Hours)</option>
                        <option value="Half Day - Morning">Half Day - Morning (4 Hours)</option>
                        <option value="Half Day - Afternoon">Half Day - Afternoon (4 Hours)</option>
                    </select>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Reason *</label>
                    <textarea name="reason" class="ess-form-control" rows="3" placeholder="Provide clear reason for leave..." required></textarea>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Supporting Document (MedCert / Form)</label>
                    <input type="file" name="attachment" class="ess-form-control" accept=".pdf,.jpg,.jpeg,.png">
                    <small style="font-size:11px;color:var(--ess-text-muted);">Mandatory for Sick Leave exceeding 2 consecutive days.</small>
                </div>
            </div>
            <div class="ess-modal-footer">
                <button type="button" class="ess-form-control" style="width:auto;cursor:pointer;" onclick="closeFileLeaveModal()">Cancel</button>
                <button type="submit" class="ess-quick-btn">Submit Leave Request</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openFileLeaveModal() {
        document.getElementById('fileLeaveModal').classList.add('open');
    }
    function closeFileLeaveModal() {
        document.getElementById('fileLeaveModal').classList.remove('open');
    }

    // Auto open if URL has action=new
    if (window.location.search.includes('action=new')) {
        openFileLeaveModal();
    }
</script>
@endpush
@endsection

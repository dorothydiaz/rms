@extends('layouts.app')

@section('title', 'Attendance Corrections - Attendance Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-note-pencil"></i>
            Attendance Corrections & Audit Trail
        </h1>
        <p class="hr-page-subtitle">Multi-tier review workflow for biometric glitches, missed punches, and shift adjustments</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addCorrectionModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Submit Correction</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff Member</th>
                    <th>Branch</th>
                    <th>Original In/Out</th>
                    <th>Requested In/Out</th>
                    <th>Reason</th>
                    <th>Requester</th>
                    <th>Status</th>
                    <th style="text-align: right;">Review Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($corrections as $c)
                    <tr>
                        <td><strong>{{ \Carbon\Carbon::parse($c->attendanceRecord?->date)->format('M d, Y') }}</strong></td>
                        <td>
                            <strong>{{ $c->employee?->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $c->employee?->position?->name }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $c->employee?->branch?->name }}</span></td>
                        <td>
                            <span style="color: #64748b;">
                                {{ $c->original_time_in ? substr($c->original_time_in, 0, 5) : '--:--' }} &bull;
                                {{ $c->original_time_out ? substr($c->original_time_out, 0, 5) : '--:--' }}
                            </span>
                        </td>
                        <td>
                            <strong style="color: #9333ea;">
                                {{ $c->requested_time_in ? substr($c->requested_time_in, 0, 5) : 'Unchanged' }} &bull;
                                {{ $c->requested_time_out ? substr($c->requested_time_out, 0, 5) : 'Unchanged' }}
                            </strong>
                        </td>
                        <td>{{ $c->reason }}</td>
                        <td>{{ $c->requester?->full_name ?? 'Manager' }}</td>
                        <td>
                            @if($c->status === 'Approved')
                                <span class="hr-badge hr-badge-success">{{ $c->status }}</span>
                            @elseif($c->status === 'Rejected')
                                <span class="hr-badge hr-badge-danger">{{ $c->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-warning">{{ $c->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($c->status === 'Pending')
                                <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openReviewModal({{ $c->id }}, '{{ addslashes($c->employee?->full_name) }}', '{{ addslashes($c->reason) }}')">
                                    <i class="ph ph-check-circle"></i> Review
                                </button>
                            @else
                                <small style="color: #94a3b8;">Reviewed by {{ $c->reviewer?->full_name }}</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">No attendance correction requests recorded.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($corrections->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $corrections->links() }}
        </div>
    @endif
</div>

<!-- Modal: Submit Correction Request -->
<div id="addCorrectionModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil-simple"></i> Submit Attendance Correction</span>
            <button class="icon-btn" onclick="closeModal('addCorrectionModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.attendance.corrections.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" class="hr-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->position?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Attendance Date *</label>
                    <input type="date" name="date" class="hr-input" required value="{{ date('Y-m-d') }}">
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Corrected Time In</label>
                        <input type="time" name="requested_time_in" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Corrected Time Out</label>
                        <input type="time" name="requested_time_out" class="hr-input">
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reason / Justification *</label>
                    <textarea name="reason" class="hr-input" required rows="3" placeholder="Explain discrepancy, biometric failure, or supervisor authorization..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addCorrectionModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Correction -->
<div id="reviewCorrectionModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 500px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-check-square"></i> Review Attendance Correction</span>
            <button class="icon-btn" onclick="closeModal('reviewCorrectionModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="reviewForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body">
                <div style="background: rgba(168, 85, 247, 0.08); padding: 12px; border-radius: 10px; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: #6b21a8;" id="reviewEmpName">Employee</div>
                    <div style="font-size: 12px; color: #475569; margin-top: 4px;" id="reviewReason">Reason</div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Review Decision *</label>
                    <select name="action" class="hr-select" required>
                        <option value="Approved">Approve & Update Attendance Record</option>
                        <option value="Rejected">Reject Correction Request</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reviewer Notes</label>
                    <input type="text" name="reviewer_notes" class="hr-input" placeholder="Optional comments...">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('reviewCorrectionModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openReviewModal(corrId, empName, reason) {
    document.getElementById('reviewEmpName').innerText = empName;
    document.getElementById('reviewReason').innerText = 'Reason: ' + reason;
    document.getElementById('reviewForm').action = '/hr/attendance/corrections/' + corrId + '/review';
    openModal('reviewCorrectionModal');
}
</script>
@endpush
@endsection

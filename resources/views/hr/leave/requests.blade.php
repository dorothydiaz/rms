@extends('layouts.app')

@section('title', 'Leave Requests - Leave & Absence Management')

@section('content')
<x-hr-tabs parent="leave-absences">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addLeaveModal')">
            <i class="ph ph-plus-circle"></i>
            <span>File Leave Request</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Date Filed</th>
                    <th>Employee Name</th>
                    <th>Branch</th>
                    <th>Leave Type</th>
                    <th>Duration</th>
                    <th>Reason</th>
                    <th>Approver</th>
                    <th>Status</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($requests as $req)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($req->created_at)->format('M d, Y') }}</td>
                        <td>
                            <strong>{{ $req->employee?->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $req->employee?->position?->name }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $req->employee?->branch?->name }}</span></td>
                        <td><strong style="color: #9333ea;">{{ $req->leaveType?->name }}</strong></td>
                        <td>
                            <strong>{{ \Carbon\Carbon::parse($req->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($req->end_date)->format('M d, Y') }}</strong><br>
                            <small style="color: #64748b;">{{ $req->number_of_days }} day(s)</small>
                        </td>
                        <td>{{ $req->reason }}</td>
                        <td>{{ $req->approver?->full_name ?? 'Pending' }}</td>
                        <td>
                            @if($req->status === 'Approved')
                                <span class="hr-badge hr-badge-success">{{ $req->status }}</span>
                            @elseif($req->status === 'Rejected')
                                <span class="hr-badge hr-badge-danger">{{ $req->status }}</span>
                            @elseif($req->status === 'Cancelled')
                                <span class="hr-badge hr-badge-neutral">{{ $req->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-warning">{{ $req->status }}</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            @if($req->status === 'Pending')
                                <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openReviewModal({{ $req->id }}, '{{ addslashes($req->employee?->full_name) }}', '{{ addslashes($req->leaveType?->name) }}', {{ $req->number_of_days }})">
                                    <i class="ph ph-check-circle"></i> Review
                                </button>
                            @else
                                <small style="color: #94a3b8;">Processed</small>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" style="text-align: center; color: #94a3b8; padding: 30px;">No leave requests filed yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $requests->links() }}
        </div>
    @endif
</div>

<!-- Modal: File Leave Request -->
<div id="addLeaveModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> File Leave Request</span>
            <button class="icon-btn" onclick="closeModal('addLeaveModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.leave.requests.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" class="hr-select" required>
                        @foreach($employees as $e)
                            <option value="{{ $e->id }}">{{ $e->full_name }} ({{ $e->branch?->name }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Leave Type *</label>
                    <select name="leave_type_id" class="hr-select" required>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }} ({{ $lt->default_credits }} credits default)</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Date *</label>
                        <input type="date" name="start_date" id="leaveStart" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Date *</label>
                        <input type="date" name="end_date" id="leaveEnd" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Number of Days *</label>
                    <input type="number" step="0.5" name="number_of_days" class="hr-input" required value="1" min="0.5">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reason *</label>
                    <textarea name="reason" class="hr-input" required rows="3" placeholder="Provide medical or personal reason..."></textarea>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Supporting Document (Medical certificate, etc.)</label>
                    <input type="file" name="supporting_document" class="hr-input">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addLeaveModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Leave Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Review Leave Request -->
<div id="reviewLeaveModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-check-square"></i> Review Leave Application</span>
            <button class="icon-btn" onclick="closeModal('reviewLeaveModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="reviewLeaveForm" method="POST" action="">
            @csrf
            <div class="hr-modal-body">
                <div style="background: rgba(147, 51, 234, 0.08); padding: 12px; border-radius: 10px; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: #6b21a8;" id="reviewLeaveEmp">Employee</div>
                    <div style="font-size: 12px; color: #475569; margin-top: 4px;" id="reviewLeaveDetails">Details</div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Decision *</label>
                    <select name="action" class="hr-select" required>
                        <option value="Approved">Approve Leave & Deduct Credits</option>
                        <option value="Rejected">Reject Leave Application</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Rejection Reason / Comments</label>
                    <input type="text" name="rejection_reason" class="hr-input" placeholder="Required if rejected...">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('reviewLeaveModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Decision</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openReviewModal(reqId, empName, leaveType, days) {
    document.getElementById('reviewLeaveEmp').innerText = empName;
    document.getElementById('reviewLeaveDetails').innerText = leaveType + ' - ' + days + ' day(s)';
    document.getElementById('reviewLeaveForm').action = '/hr/leave/requests/' + reqId + '/review';
    openModal('reviewLeaveModal');
}
</script>
@endpush
@endsection

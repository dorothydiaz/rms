@extends('layouts.ess')

@section('title', 'My Requests Hub')

@section('content')
<div class="ess-requests-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-files"></i>
                <span>Centralized Request Tracking Hub</span>
            </h1>
            <p class="hr-page-subtitle">Track approvals for Overtime, Official Business, Undertime, DTR Corrections, and Shift Swaps</p>
        </div>

        <div class="hr-page-actions">
            <button type="button" class="hr-btn hr-btn-primary" onclick="openFileOvertimeModal()">
                <i class="ph ph-clock-afternoon"></i>
                <span>File Overtime</span>
            </button>
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openFileObModal()">
                <i class="ph ph-briefcase"></i>
                <span>File OB Pass</span>
            </button>
        </div>
    </div>

    <!-- Filter Tabs (HR Tab Style) -->
    <div class="hr-tabs-wrapper" style="margin-bottom:20px;border-radius:12px;padding:4px 6px;">
        <a href="{{ route('ess.requests', ['status' => 'ALL']) }}" class="hr-tab-item {{ $statusFilter === 'ALL' ? 'active' : '' }}">
            <i class="ph ph-tray"></i>
            <span>All Submissions ({{ $counts['ALL'] }})</span>
        </a>
        <a href="{{ route('ess.requests', ['status' => 'PENDING']) }}" class="hr-tab-item {{ $statusFilter === 'PENDING' ? 'active' : '' }}">
            <i class="ph ph-hourglass-medium"></i>
            <span>Pending Review ({{ $counts['PENDING'] }})</span>
        </a>
        <a href="{{ route('ess.requests', ['status' => 'APPROVED']) }}" class="hr-tab-item {{ $statusFilter === 'APPROVED' ? 'active' : '' }}">
            <i class="ph ph-check-circle"></i>
            <span>Approved ({{ $counts['APPROVED'] }})</span>
        </a>
        <a href="{{ route('ess.requests', ['status' => 'REJECTED']) }}" class="hr-tab-item {{ $statusFilter === 'REJECTED' ? 'active' : '' }}">
            <i class="ph ph-x-circle"></i>
            <span>Rejected ({{ $counts['REJECTED'] }})</span>
        </a>
    </div>

    <!-- Requests Master Table -->
    <div class="ess-card">
        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Request #</th>
                        <th>Type</th>
                        <th>Summary / Details</th>
                        <th>Filing Date</th>
                        <th>Workflow Level</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>
                                <strong style="color:var(--ess-primary);">{{ $req->request_number }}</strong>
                            </td>
                            <td>
                                <span class="ess-badge ess-badge-info">{{ str_replace('_', ' ', $req->request_type) }}</span>
                            </td>
                            <td>
                                <div>{{ Str::limit($req->reason, 45) }}</div>
                                @if(!empty($req->details))
                                    <div style="font-size:11px;color:var(--ess-text-muted);margin-top:2px;">
                                        @if(isset($req->details['hours']))
                                            Hours: {{ $req->details['hours'] }} hrs
                                        @elseif(isset($req->details['destination']))
                                            Dest: {{ $req->details['destination'] }}
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>{{ $req->created_at->format('M d, Y h:i A') }}</td>
                            <td>
                                <span style="font-weight:600;font-size:12px;">Level {{ $req->approval_step }}</span>
                                <div style="font-size:11px;color:var(--ess-text-muted);">
                                    {{ $req->approval_step === 1 ? 'Supervisor Review' : ($req->approval_step === 2 ? 'Manager Review' : 'HR Processing') }}
                                </div>
                            </td>
                            <td>
                                @if($req->status === 'APPROVED')
                                    <span class="ess-badge ess-badge-success">Approved</span>
                                @elseif($req->status === 'PENDING')
                                    <span class="ess-badge ess-badge-warning">Pending Review</span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="ess-badge ess-badge-danger">Rejected</span>
                                @elseif($req->status === 'CANCELLED')
                                    <span class="ess-badge" style="background:#f1f5f9;color:#64748b;">Cancelled</span>
                                @else
                                    <span class="ess-badge ess-badge-info">{{ $req->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <button type="button" class="ess-badge ess-badge-info" style="cursor:pointer;border:none;" onclick="viewTimeline('{{ $req->id }}')">
                                        <i class="ph ph-tree-structure"></i>
                                        <span>Track</span>
                                    </button>

                                    @if($req->status === 'PENDING')
                                        <form method="POST" action="{{ route('ess.requests.cancel', $req->id) }}" style="margin:0;" onsubmit="return confirm('Cancel request {{ $req->request_number }}?');">
                                            @csrf
                                            <button type="submit" class="ess-badge ess-badge-danger" style="cursor:pointer;border:none;">
                                                <i class="ph ph-x"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;padding:40px;color:var(--ess-text-muted);">
                                No requests found under this status.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($requests->hasPages())
            <div style="margin-top:16px;">
                {{ $requests->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal: File Overtime -->
<div id="fileOtModal" class="ess-modal-backdrop">
    <div class="ess-modal">
        <form method="POST" action="{{ route('ess.requests.overtime') }}">
            @csrf
            <div class="ess-modal-header">
                <h3 class="ess-modal-title">File Overtime Authorization Request</h3>
                <button type="button" class="ess-modal-close" onclick="closeFileOtModal()">&times;</button>
            </div>
            <div class="ess-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Overtime Date *</label>
                    <input type="date" name="ot_date" class="ess-form-control" value="{{ now()->toDateString() }}" required>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div class="ess-form-group">
                        <label class="ess-form-label">Start Time *</label>
                        <input type="time" name="start_time" class="ess-form-control" value="17:00" required>
                    </div>
                    <div class="ess-form-group">
                        <label class="ess-form-label">End Time *</label>
                        <input type="time" name="end_time" class="ess-form-control" value="20:00" required>
                    </div>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Total Overtime Hours *</label>
                    <input type="number" step="0.5" min="0.5" max="12" name="hours" class="ess-form-control" value="3.0" required>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Business Justification *</label>
                    <textarea name="reason" class="ess-form-control" rows="3" placeholder="Provide task details justifying required overtime..." required></textarea>
                </div>
            </div>
            <div class="ess-modal-footer">
                <button type="button" class="ess-form-control" style="width:auto;cursor:pointer;" onclick="closeFileOtModal()">Cancel</button>
                <button type="submit" class="ess-quick-btn">Submit Overtime Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: File Official Business -->
<div id="fileObModal" class="ess-modal-backdrop">
    <div class="ess-modal">
        <form method="POST" action="{{ route('ess.requests.official-business') }}">
            @csrf
            <div class="ess-modal-header">
                <h3 class="ess-modal-title">File Official Business (OB) Pass</h3>
                <button type="button" class="ess-modal-close" onclick="closeFileObModal()">&times;</button>
            </div>
            <div class="ess-modal-body">
                <div class="ess-form-group">
                    <label class="ess-form-label">Date of OB *</label>
                    <input type="date" name="ob_date" class="ess-form-control" value="{{ now()->toDateString() }}" required>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Client / Agency / Destination *</label>
                    <input type="text" name="destination" class="ess-form-control" placeholder="e.g. BIR RDO 47 / Supplier Commissary" required>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px;">
                    <div class="ess-form-group">
                        <label class="ess-form-label">Departure Time *</label>
                        <input type="time" name="start_time" class="ess-form-control" value="09:00" required>
                    </div>
                    <div class="ess-form-group">
                        <label class="ess-form-label">Estimated Return Time *</label>
                        <input type="time" name="end_time" class="ess-form-control" value="13:00" required>
                    </div>
                </div>

                <div class="ess-form-group">
                    <label class="ess-form-label">Purpose of Travel *</label>
                    <textarea name="purpose" class="ess-form-control" rows="3" placeholder="Specific business purpose..." required></textarea>
                </div>
            </div>
            <div class="ess-modal-footer">
                <button type="button" class="ess-form-control" style="width:auto;cursor:pointer;" onclick="closeFileObModal()">Cancel</button>
                <button type="submit" class="ess-quick-btn">Submit OB Pass</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Live Approval Chain Visualizer -->
<div id="timelineModal" class="ess-modal-backdrop">
    <div class="ess-modal">
        <div class="ess-modal-header">
            <h3 class="ess-modal-title" id="timelineReqNum">Approval Chain Progress</h3>
            <button type="button" class="ess-modal-close" onclick="closeTimelineModal()">&times;</button>
        </div>
        <div class="ess-modal-body" id="timelineContent">
            Loading approval steps...
        </div>
    </div>
</div>

@push('scripts')
<script>
    function openFileOtModal() { document.getElementById('fileOtModal').classList.add('open'); }
    function closeFileOtModal() { document.getElementById('fileOtModal').classList.remove('open'); }
    function openFileObModal() { document.getElementById('fileObModal').classList.add('open'); }
    function closeFileObModal() { document.getElementById('fileObModal').classList.remove('open'); }
    function closeTimelineModal() { document.getElementById('timelineModal').classList.remove('open'); }

    function viewTimeline(id) {
        document.getElementById('timelineModal').classList.add('open');
        fetch(`/ess/requests/${id}/timeline`)
            .then(res => res.json())
            .then(data => {
                document.getElementById('timelineReqNum').textContent = `Workflow: ${data.request_number} (${data.type})`;
                let html = '<div style="display:flex;flex-direction:column;gap:14px;">';
                data.steps.forEach(s => {
                    const isDone = s.status === 'Approved' || s.status === 'Finalized';
                    const isRejected = s.status === 'Rejected';
                    const color = isDone ? '#059669' : (isRejected ? '#dc2626' : '#d97706');
                    const icon = isDone ? 'ph-check-circle' : (isRejected ? 'ph-x-circle' : 'ph-clock');

                    html += `
                        <div style="display:flex;align-items:flex-start;gap:12px;padding:12px;background:#f8fafc;border-radius:10px;border-left:3px solid ${color};">
                            <i class="ph ${icon}" style="font-size:20px;color:${color};margin-top:2px;"></i>
                            <div style="flex:1;">
                                <div style="display:flex;justify-content:space-between;align-items:center;">
                                    <strong>Step ${s.step}: ${s.role}</strong>
                                    <span class="ess-badge" style="background:#fff;border:1px solid ${color};color:${color};">${s.status}</span>
                                </div>
                                <div style="font-size:11px;color:var(--ess-text-muted);margin-top:4px;">
                                    ${s.timestamp ? 'Approved at: ' + s.timestamp : 'Pending review'}
                                </div>
                                ${s.remarks ? `<div style="font-size:11px;color:var(--ess-text-secondary);margin-top:2px;">Remarks: ${s.remarks}</div>` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                document.getElementById('timelineContent').innerHTML = html;
            });
    }

    if (window.location.search.includes('type=OVERTIME')) openFileOtModal();
    if (window.location.search.includes('type=OFFICIAL_BUSINESS')) openFileObModal();
</script>
@endpush
@endsection

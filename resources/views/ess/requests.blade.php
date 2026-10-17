@extends('layouts.app')

@section('title', 'My Requests Hub')

@section('content')
<div class="ess-requests-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">Centralized My Requests Hub</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Track, review approval progress, and file workplace self-service transactions.</p>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" class="btn btn-outline" onclick="document.getElementById('otModal').style.display = 'flex'">
                <i class="ph ph-timer"></i> File Overtime
            </button>
            <button type="button" class="btn btn-primary" onclick="document.getElementById('obModal').style.display = 'flex'">
                <i class="ph ph-navigation-arrow"></i> File Official Business (OB)
            </button>
        </div>
    </div>

    <!-- Status KPI Pills -->
    <div style="display: flex; gap: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="{{ route('ess.requests') }}" class="btn btn-sm {{ !$statusFilter ? 'btn-primary' : 'btn-outline' }}">
            All Requests ({{ $stats['total'] }})
        </a>
        <a href="{{ route('ess.requests') }}?status=PENDING_APPROVAL" class="btn btn-sm {{ $statusFilter === 'PENDING_APPROVAL' ? 'btn-primary' : 'btn-outline' }}">
            Pending ({{ $stats['pending'] }})
        </a>
        <a href="{{ route('ess.requests') }}?status=APPROVED" class="btn btn-sm {{ $statusFilter === 'APPROVED' ? 'btn-primary' : 'btn-outline' }}">
            Approved ({{ $stats['approved'] }})
        </a>
        <a href="{{ route('ess.requests') }}?status=REJECTED" class="btn btn-sm {{ $statusFilter === 'REJECTED' ? 'btn-primary' : 'btn-outline' }}">
            Rejected ({{ $stats['rejected'] }})
        </a>
    </div>

    <!-- Requests Table Card -->
    <div class="card" style="padding: 24px; border-radius: 14px;">
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 12px;">
                        <th style="padding: 10px;">Request Number</th>
                        <th style="padding: 10px;">Type</th>
                        <th style="padding: 10px;">Filed Date</th>
                        <th style="padding: 10px;">Target Date</th>
                        <th style="padding: 10px;">Reason & Details</th>
                        <th style="padding: 10px;">Approver</th>
                        <th style="padding: 10px;">Status</th>
                        <th style="padding: 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 10px; font-weight: 700; color: #1e293b;">{{ $req->request_number }}</td>
                            <td style="padding: 12px 10px;">
                                <span class="badge" style="background: #f1f5f9; color: #334155; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                                    {{ str_replace('_', ' ', $req->request_type) }}
                                </span>
                            </td>
                            <td style="padding: 12px 10px; color: #64748b;">{{ $req->created_at->format('M d, Y') }}</td>
                            <td style="padding: 12px 10px; color: #1e293b; font-weight: 600;">
                                {{ $req->start_date?->format('M d, Y') }}
                            </td>
                            <td style="padding: 12px 10px; color: #475569; max-width: 250px;">
                                <div>{{ $req->reason }}</div>
                                @if($req->total_hours)
                                    <small style="color: #64748b; font-weight: 600;">Hours: {{ $req->total_hours }}h</small>
                                @endif
                            </td>
                            <td style="padding: 12px 10px; color: #64748b;">
                                {{ $req->currentApprover?->full_name ?? ($req->approver?->full_name ?? ($employee->supervisor ? $employee->supervisor->first_name . ' ' . $employee->supervisor->last_name : 'Direct to GM')) }}
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($req->status === 'APPROVED')
                                    <span class="badge" style="background: #dcfce7; color: #166534; font-weight: 700;">Approved</span>
                                @elseif($req->status === 'REJECTED')
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 700;">Rejected</span>
                                @elseif($req->status === 'CANCELLED')
                                    <span class="badge" style="background: #f1f5f9; color: #64748b;">Cancelled</span>
                                @else
                                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">Pending Approval</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                @if(in_array($req->status, ['PENDING_APPROVAL', 'SUBMITTED', 'DRAFT']))
                                    <form action="{{ route('ess.requests.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Cancel request #{{ $req->request_number }}?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline" style="color: #ef4444; border-color: #fca5a5;">
                                            Cancel
                                        </button>
                                    </form>
                                @else
                                    <span style="color: #cbd5e1;">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No requests found for the selected filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $requests->links() }}
        </div>
    </div>
</div>

<!-- Overtime Filing Modal -->
<div id="otModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 8px 0; font-size: 18px; font-weight: 700;">File Overtime Request (DOLE Art. 87)</h3>
        <p style="font-size: 13px; color: #64748b; margin: 0 0 16px 0;">Requires prior authorization or operational justification before review by your manager.</p>
        <form action="{{ route('ess.requests.overtime') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Overtime Date</label>
                <input type="date" name="date" class="form-input" style="width: 100%;" required>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Start Time</label>
                    <input type="time" name="start_time" class="form-input" style="width: 100%;" required>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">End Time</label>
                    <input type="time" name="end_time" class="form-input" style="width: 100%;" required>
                </div>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Total Hours</label>
                <input type="number" step="0.5" min="0.5" max="16" name="total_hours" placeholder="e.g. 2.5" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Operational Justification</label>
                <textarea name="reason" class="form-input" style="width: 100%;" rows="3" placeholder="Explain the operational backlog or customer demand" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('otModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Overtime Request</button>
            </div>
        </form>
    </div>
</div>

<!-- Official Business Modal -->
<div id="obModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">File Official Business (OB)</h3>
        <form action="{{ route('ess.requests.official-business') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Start Date</label>
                <input type="date" name="start_date" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Destination / Client Location</label>
                <input type="text" name="destination" placeholder="e.g. Commissary BGC / Supplier Meeting" class="form-input" style="width: 100%;" required>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Business Purpose</label>
                <textarea name="purpose" class="form-input" style="width: 100%;" rows="3" placeholder="Explain the official assignment" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('obModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit OB Request</button>
            </div>
        </form>
    </div>
</div>
@endsection

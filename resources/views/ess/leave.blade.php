@extends('layouts.app')

@section('title', 'Leave Management & Statutory Benefits')

@section('content')
<div class="ess-leave-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">Leave Management (DOLE & Company Benefits)</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Philippine statutory leave rights, company credit balances, and leave application portal.</p>
        </div>
        <button type="button" class="btn btn-primary" onclick="document.getElementById('leaveModal').style.display = 'flex'">
            <i class="ph ph-calendar-plus"></i> File Leave Application
        </button>
    </div>

    <!-- Statutory Entitlement Overview Banner (DOLE Labor Standards) -->
    <div class="card" style="padding: 20px; border-radius: 14px; margin-bottom: 24px; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #e2e8f0;">
        <div style="font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 12px; display: flex; align-items: center; gap: 8px;">
            <i class="ph ph-scales" style="color: #2563eb;"></i> PHILIPPINE STATUTORY LEAVE ELIGIBILITY STATUS
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
            <!-- SIL -->
            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 13px;">Service Incentive Leave</strong>
                    <span class="badge" style="font-size: 11px; {{ $statutoryEligibility['SIL']['eligible'] ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                        {{ $statutoryEligibility['SIL']['eligible'] ? 'Eligible (5d)' : 'Ineligible' }}
                    </span>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $statutoryEligibility['SIL']['reason'] }}</div>
            </div>

            <!-- Solo Parent -->
            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 13px;">Solo Parent (RA 11861)</strong>
                    <span class="badge" style="font-size: 11px; {{ $statutoryEligibility['SOLO_PARENT']['eligible'] ? 'background: #dcfce7; color: #166534;' : 'background: #fee2e2; color: #991b1b;' }}">
                        {{ $statutoryEligibility['SOLO_PARENT']['eligible'] ? 'Eligible (7d)' : 'Ineligible' }}
                    </span>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $statutoryEligibility['SOLO_PARENT']['reason'] }}</div>
            </div>

            <!-- Maternity / Paternity -->
            @if(strcasecmp($employee->gender ?? '', 'Female') === 0)
                <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 13px;">Maternity (RA 11210)</strong>
                        <span class="badge" style="font-size: 11px; background: #dcfce7; color: #166534;">Eligible (105d)</span>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Full pay benefit for female employees</div>
                </div>
            @else
                <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <strong style="font-size: 13px;">Paternity (RA 8187)</strong>
                        <span class="badge" style="font-size: 11px; {{ $statutoryEligibility['PATERNITY']['eligible'] ? 'background: #dcfce7; color: #166534;' : 'background: #f1f5f9; color: #64748b;' }}">
                            {{ $statutoryEligibility['PATERNITY']['eligible'] ? 'Eligible (7d)' : 'Married Male Staff' }}
                        </span>
                    </div>
                    <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $statutoryEligibility['PATERNITY']['reason'] }}</div>
                </div>
            @endif

            <!-- VAWC -->
            <div style="background: #ffffff; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <strong style="font-size: 13px;">VAWC Leave (RA 9262)</strong>
                    <span class="badge" style="font-size: 11px; {{ $statutoryEligibility['VAWC']['eligible'] ? 'background: #dcfce7; color: #166534;' : 'background: #f1f5f9; color: #64748b;' }}">
                        {{ $statutoryEligibility['VAWC']['eligible'] ? 'Applicable (10d)' : 'Female Staff' }}
                    </span>
                </div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">{{ $statutoryEligibility['VAWC']['reason'] }}</div>
            </div>
        </div>
    </div>

    <!-- Available Leave Balances Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        @forelse($balances as $bal)
            <div class="card" style="padding: 18px; border-radius: 12px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">
                    {{ $bal->leaveType?->name ?? 'Leave Balance' }}
                </div>
                <div style="font-size: 26px; font-weight: 800; color: #1e293b; margin: 8px 0 4px 0;">
                    {{ number_format($bal->remaining_days, 1) }} <span style="font-size: 13px; font-weight: 500; color: #64748b;">days</span>
                </div>
                <div style="font-size: 12px; color: #64748b; display: flex; justify-content: space-between;">
                    <span>Entitled: {{ number_format($bal->entitled_days, 1) }}</span>
                    <span>Used: {{ number_format($bal->used_days, 1) }}</span>
                </div>
            </div>
        @empty
            <div class="card" style="padding: 20px; border-radius: 12px; grid-column: 1 / -1; text-align: center; color: #94a3b8;">
                No leave balance records configured yet. Contact HR Administrator.
            </div>
        @endforelse
    </div>

    <!-- Leave Requests Table -->
    <div class="card" style="padding: 24px; border-radius: 14px;">
        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 16px 0; color: #1e293b;">
            <i class="ph ph-clock-counter-clockwise"></i> My Filed Leave Applications
        </h3>
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 12px;">
                        <th style="padding: 10px;">Filed Date</th>
                        <th style="padding: 10px;">Leave Type</th>
                        <th style="padding: 10px;">Inclusive Dates</th>
                        <th style="padding: 10px;">Duration</th>
                        <th style="padding: 10px;">Reason</th>
                        <th style="padding: 10px;">Status</th>
                        <th style="padding: 10px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($myRequests as $req)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 10px; color: #64748b;">{{ $req->created_at->format('M d, Y') }}</td>
                            <td style="padding: 12px 10px; font-weight: 700; color: #1e293b;">{{ $req->leaveType?->name }}</td>
                            <td style="padding: 12px 10px; color: #334155;">
                                {{ $req->start_date->format('M d, Y') }}
                                @if($req->end_date && !$req->start_date->isSameDay($req->end_date))
                                    - {{ $req->end_date->format('M d, Y') }}
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">{{ $req->number_of_days }} day(s) {{ $req->is_half_day ? '(Half Day)' : '' }}</td>
                            <td style="padding: 12px 10px; color: #475569; max-width: 250px;">{{ $req->reason }}</td>
                            <td style="padding: 12px 10px;">
                                @if($req->status === 'Approved')
                                    <span class="badge" style="background: #dcfce7; color: #166534; font-weight: 700;">Approved</span>
                                @elseif($req->status === 'Rejected')
                                    <span class="badge" style="background: #fee2e2; color: #991b1b; font-weight: 700;">Rejected</span>
                                @elseif($req->status === 'Cancelled')
                                    <span class="badge" style="background: #f1f5f9; color: #475569;">Cancelled</span>
                                @else
                                    <span class="badge" style="background: #fef3c7; color: #92400e; font-weight: 700;">Pending Approval</span>
                                @endif
                            </td>
                            <td style="padding: 12px 10px;">
                                @if($req->status === 'Pending')
                                    <form action="{{ route('ess.leave.cancel', $req->id) }}" method="POST" onsubmit="return confirm('Cancel this leave request?');">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline" style="color: #ef4444; border-color: #fca5a5;">
                                            Cancel
                                        </button>
                                    </form>
                                @else
                                    <span style="color: #94a3b8;">--</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No leave applications submitted yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Leave Filing Modal -->
<div id="leaveModal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="background: #ffffff; width: 480px; max-width: 90vw; padding: 24px; border-radius: 16px;">
        <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 700;">File Leave Application</h3>
        <form action="{{ route('ess.leave.store') }}" method="POST">
            @csrf
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Leave Type</label>
                <select name="leave_type_id" class="form-input" style="width: 100%;" required>
                    @foreach($leaveTypes as $t)
                        <option value="{{ $t->id }}">{{ $t->name }} ({{ $t->code }})</option>
                    @endforeach
                </select>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Start Date</label>
                    <input type="date" name="start_date" class="form-input" style="width: 100%;" required>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">End Date</label>
                    <input type="date" name="end_date" class="form-input" style="width: 100%;" required>
                </div>
            </div>
            <div style="margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_half_day" id="half_day" value="1">
                <label for="half_day" style="font-size: 13px; font-weight: 600; cursor: pointer;">Half Day Application (4 working hours)</label>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 13px; font-weight: 600; margin-bottom: 4px;">Reason for Leave</label>
                <textarea name="reason" class="form-input" style="width: 100%;" rows="3" placeholder="Provide reason or emergency circumstances" required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" class="btn btn-outline" onclick="document.getElementById('leaveModal').style.display = 'none'">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Application</button>
            </div>
        </form>
    </div>
</div>
@endsection

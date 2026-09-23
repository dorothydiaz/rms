@extends('layouts.app')

@section('title', 'Leave Credits - Leave & Absence Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-wallet"></i>
            Employee Leave Credits & Balances
        </h1>
        <p class="hr-page-subtitle">Track beginning balance, earned credits, used leaves, and encashments per calendar year</p>
    </div>
    <div class="hr-page-actions">
        <form method="GET" action="{{ route('hr.leave.credits') }}" style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Calendar Year:</label>
            <select name="year" class="hr-select" onchange="this.form.submit()">
                @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee ID</th>
                    <th>Employee Name</th>
                    <th>Branch</th>
                    <th>Leave Type</th>
                    <th>Beginning</th>
                    <th>Earned</th>
                    <th>Used</th>
                    <th>Encashed</th>
                    <th>Remaining Balance</th>
                    <th style="text-align: right;">Adjust</th>
                </tr>
            </thead>
            <tbody>
                @forelse($balances as $b)
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $b->employee?->employee_id }}</strong></td>
                        <td>
                            <strong>{{ $b->employee?->full_name }}</strong><br>
                            <small style="color: #64748b;">{{ $b->employee?->position?->name }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $b->employee?->branch?->name }}</span></td>
                        <td><strong>{{ $b->leaveType?->name }}</strong></td>
                        <td>{{ number_format($b->beginning_balance, 1) }}</td>
                        <td>+{{ number_format($b->earned, 1) }}</td>
                        <td style="color: #ef4444; font-weight: 600;">-{{ number_format($b->used, 1) }}</td>
                        <td>{{ number_format($b->encashed, 1) }}</td>
                        <td>
                            <strong style="font-size: 14px; color: #059669;">{{ number_format($b->remaining, 1) }} days</strong>
                        </td>
                        <td style="text-align: right;">
                            <button class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openAdjustModal({{ $b->employee_id }}, {{ $b->leave_type_id }}, '{{ addslashes($b->employee?->full_name) }}', '{{ addslashes($b->leaveType?->name) }}', {{ $b->beginning_balance }}, {{ $b->earned }}, {{ $b->used }}, {{ $b->encashed }})">
                                <i class="ph ph-sliders"></i> Adjust
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align: center; color: #94a3b8; padding: 30px;">No leave credit records found for year {{ $year }}.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($balances->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $balances->links() }}
        </div>
    @endif
</div>

<!-- Modal: Adjust Leave Credits -->
<div id="adjustModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 500px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-sliders"></i> Adjust Leave Balance</span>
            <button class="icon-btn" onclick="closeModal('adjustModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.leave.credits.adjust') }}">
            @csrf
            <input type="hidden" name="employee_id" id="adjEmpId" value="">
            <input type="hidden" name="leave_type_id" id="adjTypeId" value="">
            <input type="hidden" name="year" value="{{ $year }}">
            <div class="hr-modal-body">
                <div style="background: rgba(147, 51, 234, 0.08); padding: 12px; border-radius: 10px; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: #6b21a8;" id="adjEmpName">Employee</div>
                    <div style="font-size: 12px; color: #475569;" id="adjTypeName">Leave Type</div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Beginning Balance</label>
                        <input type="number" step="0.5" name="beginning_balance" id="adjBeg" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Earned Credits</label>
                        <input type="number" step="0.5" name="earned" id="adjEarned" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Used Days</label>
                        <input type="number" step="0.5" name="used" id="adjUsed" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Encashed Days</label>
                        <input type="number" step="0.5" name="encashed" id="adjEncashed" class="hr-input" required>
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('adjustModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openAdjustModal(empId, typeId, empName, typeName, beg, earned, used, encashed) {
    document.getElementById('adjEmpId').value = empId;
    document.getElementById('adjTypeId').value = typeId;
    document.getElementById('adjEmpName').innerText = empName;
    document.getElementById('adjTypeName').innerText = typeName + ' (Year {{ $year }})';
    document.getElementById('adjBeg').value = beg;
    document.getElementById('adjEarned').value = earned;
    document.getElementById('adjUsed').value = used;
    document.getElementById('adjEncashed').value = encashed;
    openModal('adjustModal');
}
</script>
@endpush
@endsection

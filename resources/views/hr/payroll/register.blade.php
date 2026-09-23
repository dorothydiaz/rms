@extends('layouts.app')

@section('title', 'Payroll Register - Philippine Payroll Engine')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-receipt"></i>
            Restaurant Payroll Register
        </h1>
        <p class="hr-page-subtitle">Master payroll sheet: Basic pay, overtime, night differential, Philippine statutory contributions, tax, and net disbursements</p>
    </div>
    <div class="hr-page-actions">
        <a href="{{ route('hr.reports.export.payroll', ['payroll_period_id' => $currentPeriod?->id]) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export Register CSV</span>
        </a>
    </div>
</div>

<!-- Summary Totals Grid -->
<div class="hr-metrics-grid" style="margin-bottom: 20px;">
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #9333ea;">₱{{ number_format($totals['gross'], 2) }}</span>
            <span class="hr-metric-label">Total Gross Payroll</span>
        </div>
        <div class="hr-metric-icon purple"><i class="ph ph-money"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #059669;">₱{{ number_format($totals['net'], 2) }}</span>
            <span class="hr-metric-label">Total Net Pay Disbursed</span>
        </div>
        <div class="hr-metric-icon emerald"><i class="ph ph-wallet"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #ef4444;">₱{{ number_format($totals['deductions'], 2) }}</span>
            <span class="hr-metric-label">Total Deductions</span>
        </div>
        <div class="hr-metric-icon rose"><i class="ph ph-arrow-circle-down"></i></div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-info">
            <span class="hr-metric-value" style="color: #6366f1;">₱{{ number_format($totals['sss'] + $totals['philhealth'] + $totals['pagibig'] + $totals['tax'], 2) }}</span>
            <span class="hr-metric-label">Statutory & BIR Total</span>
        </div>
        <div class="hr-metric-icon indigo"><i class="ph ph-bank"></i></div>
    </div>
</div>

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.payroll.register') }}" class="hr-filter-form">
        <div style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Payroll Period:</label>
            <select name="payroll_period_id" class="hr-select" onchange="this.form.submit()">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" {{ $currentPeriod && $currentPeriod->id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }} [{{ $p->status }}]
                    </option>
                @endforeach
            </select>
        </div>

        @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
            <select name="branch_id" class="hr-select" onchange="this.form.submit()">
                <option value="">All Branches</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
        @endif
    </form>
</div>

<!-- Register Data Table -->
<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Staff Name</th>
                    <th>Branch</th>
                    <th>Basic Pay</th>
                    <th>OT Pay</th>
                    <th>ND Pay</th>
                    <th>Allowances</th>
                    <th>Gross Pay</th>
                    <th>Attendance Ded</th>
                    <th>SSS</th>
                    <th>PhilHealth</th>
                    <th>Pag-IBIG</th>
                    <th>Tax</th>
                    <th>Net Pay</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <strong>{{ $rec->employee?->full_name }}</strong><br>
                            <small style="color: #9333ea; font-family: monospace;">{{ $rec->employee?->employee_id }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $rec->employee?->branch?->name }}</span></td>
                        <td>₱{{ number_format($rec->basic_pay, 2) }}</td>
                        <td>₱{{ number_format($rec->overtime_pay, 2) }}</td>
                        <td>₱{{ number_format($rec->night_diff_pay, 2) }}</td>
                        <td>₱{{ number_format($rec->allowances, 2) }}</td>
                        <td><strong style="color: #0f172a;">₱{{ number_format($rec->gross_pay, 2) }}</strong></td>
                        <td style="color: #ef4444;">-₱{{ number_format($rec->late_deduction + $rec->undertime_deduction + $rec->absence_deduction, 2) }}</td>
                        <td>₱{{ number_format($rec->sss_employee, 2) }}</td>
                        <td>₱{{ number_format($rec->philhealth_employee, 2) }}</td>
                        <td>₱{{ number_format($rec->pagibig_employee, 2) }}</td>
                        <td>₱{{ number_format($rec->withholding_tax, 2) }}</td>
                        <td>
                            <strong style="font-size: 14px; color: #059669;">₱{{ number_format($rec->net_pay, 2) }}</strong>
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; gap: 4px;">
                                <a href="{{ route('hr.payroll.payslips.show', $rec->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Payslip">
                                    <i class="ph ph-receipt"></i>
                                </a>
                                @if($currentPeriod && $currentPeriod->status !== 'Finalized')
                                    <button class="hr-btn hr-btn-primary hr-btn-sm" title="Add Audited Adjustment" onclick="openAdjModal({{ $rec->id }}, '{{ addslashes($rec->employee?->full_name) }}')">
                                        <i class="ph ph-plus-minus"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" style="text-align: center; color: #94a3b8; padding: 30px;">No payroll records generated for this period. Click "Process Payroll" to calculate.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($records->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $records->links() }}
        </div>
    @endif
</div>

<!-- Modal: Add Payroll Adjustment (Post-Review Auditable Change) -->
<div id="payrollAdjModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 500px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-minus"></i> Add Audited Payroll Adjustment</span>
            <button class="icon-btn" onclick="closeModal('payrollAdjModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.payroll.adjustments.store') }}">
            @csrf
            <input type="hidden" name="payroll_record_id" id="adjRecordId" value="">
            <div class="hr-modal-body">
                <div style="background: rgba(147, 51, 234, 0.08); padding: 12px; border-radius: 10px; margin-bottom: 8px;">
                    <div style="font-weight: 700; color: #6b21a8;" id="adjStaffName">Staff Member</div>
                    <small style="color: #64748b;">Post-review adjustment will be recorded with an audit trail.</small>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Adjustment Type *</label>
                    <select name="adjustment_type" class="hr-select" required>
                        <option value="Earning">Earning (+) e.g. Incentive, Retroactive Pay</option>
                        <option value="Deduction">Deduction (-) e.g. Uniform, Shortage, Cash Advance</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Adjustment Name / Reason *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Kitchen Performance Bonus, Cash Shortage">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Amount (PHP) *</label>
                    <input type="number" step="0.01" name="amount" class="hr-input" required placeholder="500.00" min="0.01">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Auditor Remarks</label>
                    <input type="text" name="remarks" class="hr-input" placeholder="Authorization details...">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('payrollAdjModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Apply Adjustment</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openAdjModal(recId, name) {
    document.getElementById('adjRecordId').value = recId;
    document.getElementById('adjStaffName').innerText = name;
    openModal('payrollAdjModal');
}
</script>
@endpush
@endsection

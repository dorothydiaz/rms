@extends('layouts.app')

@section('title', 'Payroll Register - Philippine Payroll Engine')

@push('styles')
<style>
/* Responsive Payroll Register Table */
.hr-payroll-table {
    width: 100% !important;
    min-width: 1020px;
    border-collapse: separate;
    border-spacing: 0;
}

.hr-payroll-table th,
.hr-payroll-table td {
    padding: 8px 6px !important;
    font-size: 12px;
    vertical-align: middle;
}

.hr-payroll-table th {
    font-size: 10.5px !important;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    background: #f8fafc !important;
    white-space: nowrap;
    border-bottom: 2px solid #e2e8f0;
}

/* Sticky Staff Name (Left Column) */
.hr-payroll-table th.py-col-name,
.hr-payroll-table td.py-col-name {
    position: sticky;
    left: 0;
    z-index: 5;
    background: #ffffff;
    width: 140px;
    min-width: 130px;
    max-width: 155px;
    box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.08);
}
.hr-payroll-table th.py-col-name {
    background: #f8fafc !important;
    z-index: 15;
}

/* Sticky Actions (Right Column) */
.hr-payroll-table th.py-col-action,
.hr-payroll-table td.py-col-action {
    position: sticky;
    right: 0;
    z-index: 5;
    background: #ffffff;
    width: 78px;
    min-width: 78px;
    text-align: right;
    box-shadow: -3px 0 6px -2px rgba(0, 0, 0, 0.08);
}
.hr-payroll-table th.py-col-action {
    background: #f8fafc !important;
    z-index: 15;
}

.hr-payroll-table tr:hover td.py-col-name,
.hr-payroll-table tr:hover td.py-col-action {
    background: #f8fafc !important;
}

/* Branch Column */
.hr-payroll-table .py-col-branch {
    width: 105px;
    min-width: 95px;
    max-width: 120px;
    white-space: nowrap;
}

/* Numeric / Currency Columns */
.hr-payroll-table .py-col-num {
    text-align: right !important;
    font-variant-numeric: tabular-nums;
    white-space: nowrap;
    font-size: 11.5px;
    padding: 8px 5px !important;
}

.hr-payroll-table th.py-col-num {
    text-align: right !important;
    padding: 8px 5px !important;
}

.hr-payroll-table .py-col-basic { width: 75px; }
.hr-payroll-table .py-col-ot { width: 55px; }
.hr-payroll-table .py-col-nd { width: 55px; }
.hr-payroll-table .py-col-allow { width: 65px; }
.hr-payroll-table .py-col-gross { width: 80px; }
.hr-payroll-table .py-col-att-ded { width: 75px; }
.hr-payroll-table .py-col-sss { width: 60px; }
.hr-payroll-table .py-col-phil { width: 62px; }
.hr-payroll-table .py-col-pagibig { width: 58px; }
.hr-payroll-table .py-col-tax { width: 58px; }
.hr-payroll-table .py-col-net { width: 85px; }
</style>
@endpush

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <a href="{{ route('hr.reports.export.payroll', ['payroll_period_id' => $currentPeriod?->id]) }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-download-simple"></i>
            <span>Export Register CSV</span>
        </a>
    </x-slot:actions>
</x-hr-tabs>

<!-- Summary Totals Grid -->
<div class="hr-metrics-grid" style="margin-bottom: 20px; width: 100%; max-width: 100%;">
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
<div class="hr-table-card" style="max-width: 100%; overflow: hidden;">
    <div class="hr-table-wrapper" style="overflow-x: auto; max-width: 100%; -webkit-overflow-scrolling: touch;">
        <table class="hr-table hr-payroll-table">
            <thead>
                <tr>
                    <th class="py-col-name">Staff Name</th>
                    <th class="py-col-branch">Branch</th>
                    <th class="py-col-num py-col-basic">Basic Pay</th>
                    <th class="py-col-num py-col-ot">OT Pay</th>
                    <th class="py-col-num py-col-nd">ND Pay</th>
                    <th class="py-col-num py-col-allow">Allowances</th>
                    <th class="py-col-num py-col-gross">Gross Pay</th>
                    <th class="py-col-num py-col-att-ded">Attendance Ded</th>
                    <th class="py-col-num py-col-sss">SSS</th>
                    <th class="py-col-num py-col-phil">PhilHealth</th>
                    <th class="py-col-num py-col-pagibig">Pag-IBIG</th>
                    <th class="py-col-num py-col-tax">Tax</th>
                    <th class="py-col-num py-col-net">Net Pay</th>
                    <th class="py-col-action">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td class="py-col-name">
                            <strong style="color: #0f172a; font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $rec->employee?->full_name }}">{{ $rec->employee?->full_name }}</strong>
                            <small style="color: #9333ea; font-family: monospace; font-size: 10.5px;">{{ $rec->employee?->employee_id }}</small>
                        </td>
                        <td class="py-col-branch">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px; padding: 2px 6px; max-width: 110px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block;" title="{{ $rec->employee?->branch?->name }}">{{ $rec->employee?->branch?->name }}</span>
                        </td>
                        <td class="py-col-num py-col-basic">₱{{ number_format($rec->basic_pay, 2) }}</td>
                        <td class="py-col-num py-col-ot">₱{{ number_format($rec->overtime_pay, 2) }}</td>
                        <td class="py-col-num py-col-nd">₱{{ number_format($rec->night_diff_pay, 2) }}</td>
                        <td class="py-col-num py-col-allow">₱{{ number_format($rec->allowances, 2) }}</td>
                        <td class="py-col-num py-col-gross"><strong style="color: #0f172a;">₱{{ number_format($rec->gross_pay, 2) }}</strong></td>
                        <td class="py-col-num py-col-att-ded" style="color: #ef4444;">-₱{{ number_format($rec->late_deduction + $rec->undertime_deduction + $rec->absence_deduction, 2) }}</td>
                        <td class="py-col-num py-col-sss">₱{{ number_format($rec->sss_employee, 2) }}</td>
                        <td class="py-col-num py-col-phil">₱{{ number_format($rec->philhealth_employee, 2) }}</td>
                        <td class="py-col-num py-col-pagibig">₱{{ number_format($rec->pagibig_employee, 2) }}</td>
                        <td class="py-col-num py-col-tax">₱{{ number_format($rec->withholding_tax, 2) }}</td>
                        <td class="py-col-num py-col-net">
                            <strong style="font-size: 12.5px; color: #059669; font-weight: 800;">₱{{ number_format($rec->net_pay, 2) }}</strong>
                        </td>
                        <td class="py-col-action">
                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 3px;">
                                <a href="{{ route('hr.payroll.payslips.show', $rec->id) }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="padding: 3px 6px; font-size: 11px;" title="View Payslip">
                                    <i class="ph ph-receipt"></i>
                                </a>
                                @if($currentPeriod && $currentPeriod->status !== 'Finalized')
                                    <button class="hr-btn hr-btn-primary hr-btn-sm" style="padding: 3px 6px; font-size: 11px;" title="Add Audited Adjustment" onclick="openAdjModal({{ $rec->id }}, '{{ addslashes($rec->employee?->full_name) }}')">
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

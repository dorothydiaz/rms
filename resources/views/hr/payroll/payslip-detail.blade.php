@extends('layouts.app')

@section('title', 'Payslip - ' . ($record->employee?->full_name ?? 'Employee'))

@push('styles')
<style>
/* Header Spacing */
.hr-page-header.no-print {
    margin-top: 0 !important;
    margin-bottom: 22px !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: flex-end !important;
}

.hr-page-header.no-print .hr-page-title {
    margin: 0 !important;
}

/* Executive Official Payslip Document - True Glassmorphism */
.payslip-card-wrapper {
    max-width: 860px;
    margin: 0 auto 40px auto;
}

.payslip-container {
    background: rgba(255, 255, 255, 0.72) !important;
    backdrop-filter: blur(24px) saturate(190%) !important;
    -webkit-backdrop-filter: blur(24px) saturate(190%) !important;
    border: 1px solid rgba(255, 255, 255, 0.85) !important;
    border-radius: 20px !important;
    padding: 36px 40px !important;
    box-shadow: 0 20px 50px -10px rgba(124, 58, 237, 0.12),
                0 4px 16px rgba(0, 0, 0, 0.03),
                inset 0 1px 2px rgba(255, 255, 255, 0.95) !important;
    position: relative;
    overflow: hidden;
}

.payslip-container::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #ec4899 0%, #a855f7 50%, #8b5cf6 100%) !important;
}

/* Header */
.payslip-brand-row {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding-bottom: 20px;
    margin-bottom: 22px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.8);
}

.payslip-brand-title {
    font-size: 19px;
    font-weight: 800;
    color: #0f172a;
    margin: 0;
    letter-spacing: -0.01em;
    text-transform: uppercase;
}

.payslip-brand-meta {
    font-size: 12.5px;
    color: #64748b;
    margin-top: 4px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.payslip-tin-badge {
    display: inline-block;
    font-size: 11px;
    font-family: monospace;
    font-weight: 600;
    color: #475569;
    background: rgba(255, 255, 255, 0.75);
    border: 1px solid rgba(226, 232, 240, 0.85);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    padding: 2.5px 8px;
    border-radius: 6px;
    margin-top: 5px;
}

.payslip-badge-official {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(135deg, rgba(236, 72, 153, 0.08) 0%, rgba(147, 51, 234, 0.12) 100%);
    border: 1px solid rgba(168, 85, 247, 0.32);
    color: #7c3aed;
    font-weight: 650;
    font-size: 11px;
    padding: 3.5px 10px;
    border-radius: 9999px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    box-shadow: 0 1px 4px rgba(124, 58, 237, 0.06);
}

.payslip-badge-official i {
    color: #9333ea;
    font-size: 13px;
}

.payslip-dates-box {
    margin-top: 8px;
    text-align: right;
    font-size: 12px;
    color: #64748b;
    line-height: 1.5;
}

.payslip-dates-box strong {
    color: #1e293b;
    font-weight: 600;
}

/* Employee Ribbon - Glassmorphic */
.payslip-emp-ribbon {
    display: grid;
    grid-template-columns: 1.2fr 0.9fr 1.3fr 1fr;
    gap: 14px;
    background: rgba(255, 255, 255, 0.60);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 14px;
    padding: 15px 18px;
    margin-bottom: 22px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02), inset 0 1px 1px rgba(255, 255, 255, 0.9);
}

.payslip-emp-item {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.payslip-emp-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #64748b;
}

.payslip-emp-val {
    font-size: 13.5px;
    font-weight: 700;
    color: #0f172a;
    line-height: 1.3;
}

.payslip-emp-id-pill {
    font-family: monospace;
    font-size: 12px;
    font-weight: 600;
    color: #475569;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(203, 213, 225, 0.8);
    padding: 2px 8px;
    border-radius: 5px;
    display: inline-block;
    width: fit-content;
}

/* Tables Section - Glassmorphic Cards */
.payslip-grid-tables {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 22px;
}

.payslip-table-card {
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 14px;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.65);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.03), inset 0 1px 1px rgba(255, 255, 255, 0.9);
}

.payslip-table-card-header {
    padding: 10px 16px;
    font-weight: 700;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    display: flex;
    align-items: center;
    gap: 6px;
    border-bottom: 1px solid rgba(226, 232, 240, 0.6);
}

.payslip-table-card-header.earnings {
    background: rgba(240, 253, 244, 0.85);
    color: #166534;
    border-bottom-color: rgba(187, 247, 208, 0.6);
}

.payslip-table-card-header.deductions {
    background: rgba(255, 241, 242, 0.85);
    color: #9f1239;
    border-bottom-color: rgba(254, 205, 211, 0.6);
}

.payslip-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 12.5px;
}

.payslip-table td {
    padding: 9.5px 16px;
    border-bottom: 1px solid rgba(241, 245, 249, 0.7);
    color: #334155;
}

.payslip-table td.amount {
    text-align: right;
    font-weight: 600;
    color: #0f172a;
    font-variant-numeric: tabular-nums;
}

.payslip-table td.amount.deduction {
    color: #e11d48;
}

.payslip-table tr:hover td {
    background: rgba(255, 255, 255, 0.5);
}

.payslip-table tfoot td {
    padding: 11px 16px;
    font-weight: 700;
    font-size: 12px;
}

.payslip-table tfoot.earnings td {
    background: rgba(240, 253, 244, 0.88);
    border-top: 1.5px solid rgba(187, 247, 208, 0.8);
    color: #15803d;
}

.payslip-table tfoot.earnings td.amount {
    font-size: 14.5px;
    font-weight: 750;
    color: #166534;
}

.payslip-table tfoot.deductions td {
    background: rgba(255, 241, 242, 0.88);
    border-top: 1.5px solid rgba(254, 205, 211, 0.8);
    color: #be123c;
}

.payslip-table tfoot.deductions td.amount {
    font-size: 14.5px;
    font-weight: 750;
    color: #9f1239;
}

/* Take Home Pay - Glassmorphic Card */
.payslip-takehome-card {
    background: linear-gradient(135deg, #7c3aed 0%, #9333ea 40%, #c026d3 75%, #db2777 100%);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border: 1px solid rgba(255, 255, 255, 0.35);
    color: #ffffff;
    border-radius: 14px;
    padding: 18px 26px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    box-shadow: 0 10px 28px -4px rgba(147, 51, 234, 0.32), 0 4px 12px rgba(219, 39, 119, 0.18), inset 0 1px 1px rgba(255, 255, 255, 0.45);
}

.payslip-takehome-label {
    font-size: 11.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #f5d0fe;
    display: flex;
    align-items: center;
    gap: 6px;
}

.payslip-takehome-sub {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.88);
    margin-top: 2px;
}

.payslip-takehome-amount {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

/* Employer Contributions - Glassmorphic */
.payslip-employer-box {
    background: rgba(255, 255, 255, 0.60);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.85);
    border-radius: 12px;
    padding: 12px 18px;
    margin-bottom: 26px;
    font-size: 11.5px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 10px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
}

.payslip-employer-title {
    font-weight: 700;
    color: #475569;
    text-transform: uppercase;
    font-size: 10.5px;
    letter-spacing: 0.05em;
    display: flex;
    align-items: center;
    gap: 5px;
}

.payslip-employer-pill {
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(226, 232, 240, 0.8);
    padding: 3.5px 11px;
    border-radius: 6px;
    font-weight: 600;
    color: #334155;
    font-variant-numeric: tabular-nums;
}

/* Signatures */
.payslip-signatures-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    margin-top: 30px;
    padding-top: 8px;
}

.payslip-sign-block {
    text-align: center;
}

.payslip-sign-line {
    border-top: 1.5px solid #94a3b8;
    width: 220px;
    margin: 0 auto 6px auto;
}

.payslip-sign-title {
    font-size: 11.5px;
    font-weight: 600;
    color: #475569;
    display: block;
}

.payslip-sign-date {
    font-size: 10.5px;
    color: #94a3b8;
    margin-top: 2px;
    display: block;
}

@media print {
    body * { visibility: hidden !important; }
    .payslip-container, .payslip-container * { visibility: visible !important; }
    .payslip-container {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        border: none !important;
        box-shadow: none !important;
        padding: 10px !important;
        background: #ffffff !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .payslip-emp-ribbon,
    .payslip-table-card,
    .payslip-employer-box {
        background: #ffffff !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .payslip-takehome-card {
        background: #f8fafc !important;
        border: 1.5px solid #0f172a !important;
        color: #0f172a !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-shadow: none !important;
    }
    .payslip-takehome-label,
    .payslip-takehome-sub,
    .payslip-takehome-amount {
        color: #0f172a !important;
        text-shadow: none !important;
    }
    .payslip-badge-official {
        background: transparent !important;
        border: 1px solid #94a3b8 !important;
        color: #475569 !important;
        box-shadow: none !important;
    }
    .payslip-badge-official i {
        color: #475569 !important;
    }
    .payslip-container::before { display: none !important; }
    .no-print { display: none !important; }
}
</style>
@endpush

@section('content')
<div class="hr-page-header no-print">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 16px;">
            <a href="{{ route('hr.payroll.payslips', ['payroll_period_id' => $record->payroll_period_id]) }}" class="hr-btn hr-btn-secondary hr-btn-sm">
                <i class="ph ph-arrow-left"></i> Back to Payslips
            </a>
            <span class="hr-badge hr-badge-neutral">{{ $record->payrollPeriod?->name }}</span>
        </div>
        <h1 class="hr-page-title">
            <i class="ph ph-receipt"></i>
            Employee Official Payslip
        </h1>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="window.print()">
            <i class="ph ph-printer"></i>
            <span>Print Payslip</span>
        </button>
    </div>
</div>

<!-- Printable Payslip Document -->
<div class="payslip-card-wrapper">
    <div class="payslip-container">
        <!-- Header -->
        <div class="payslip-brand-row">
            <div>
                <h2 class="payslip-brand-title">
                    {{ $record->employee?->branch?->company?->name ?? 'Bistro Hospitality Group Inc.' }}
                </h2>
                <div class="payslip-brand-meta">
                    <i class="ph ph-map-pin" style="color: #94a3b8;"></i>
                    <span>{{ $record->employee?->branch?->name }} &bull; {{ $record->employee?->branch?->address }}</span>
                </div>
                <div class="payslip-tin-badge">
                    TIN: {{ $record->employee?->branch?->company?->tin ?? '123-456-789-000' }}
                </div>
            </div>
            <div style="text-align: right;">
                <span class="payslip-badge-official">
                    <i class="ph ph-seal-check"></i>
                    Official Payslip
                </span>
                <div class="payslip-dates-box">
                    Cutoff: <strong>{{ \Carbon\Carbon::parse($record->payrollPeriod?->start_date)->format('M d') }} – {{ \Carbon\Carbon::parse($record->payrollPeriod?->end_date)->format('M d, Y') }}</strong><br>
                    Payout Date: <strong>{{ \Carbon\Carbon::parse($record->payrollPeriod?->payout_date)->format('M d, Y') }}</strong>
                </div>
            </div>
        </div>

        <!-- Employee Info Ribbon -->
        <div class="payslip-emp-ribbon">
            <div class="payslip-emp-item">
                <span class="payslip-emp-label">Employee Name</span>
                <span class="payslip-emp-val">{{ $record->employee?->full_name }}</span>
            </div>
            <div class="payslip-emp-item">
                <span class="payslip-emp-label">Employee ID</span>
                <span class="payslip-emp-id-pill">{{ $record->employee?->employee_id }}</span>
            </div>
            <div class="payslip-emp-item">
                <span class="payslip-emp-label">Department &amp; Position</span>
                <span class="payslip-emp-val" style="font-size: 13px;">
                    {{ $record->employee?->department?->name ?? 'Staff' }} / {{ $record->employee?->position?->name ?? 'Staff' }}
                </span>
            </div>
            <div class="payslip-emp-item">
                <span class="payslip-emp-label">Days / Hours Worked</span>
                <span class="payslip-emp-val" style="font-size: 13px;">
                    {{ $record->total_work_days }} Days ({{ number_format($record->total_hours, 2) }} hrs)
                </span>
            </div>
        </div>

        <!-- Earnings & Deductions Tables -->
        <div class="payslip-grid-tables">
            <!-- Earnings Column -->
            <div class="payslip-table-card">
                <div class="payslip-table-card-header earnings">
                    <i class="ph ph-plus-circle" style="font-size: 15px;"></i>
                    <span>Earnings</span>
                </div>
                <table class="payslip-table">
                    <tbody>
                        <tr>
                            <td>Basic Salary Pay</td>
                            <td class="amount">₱{{ number_format($record->basic_pay, 2) }}</td>
                        </tr>
                        <tr>
                            <td>
                                Overtime Pay ({{ number_format($record->overtime_hours, 2) }} hrs @ 125%)
                            </td>
                            <td class="amount">₱{{ number_format($record->overtime_pay, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Holiday Pay</td>
                            <td class="amount">₱{{ number_format($record->holiday_pay, 2) }}</td>
                        </tr>
                        <tr style="background: #faf5ff;">
                            <td>
                                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                                    <span style="font-weight: 600; color: #7c3aed;">Premium Pay</span>
                                    <button type="button" onclick="openPayslipModal('payslipPremiumPayModal')" style="background: #f3e8ff; border: 1px solid #d8b4fe; border-radius: 4px; font-size: 11px; color: #7c3aed; cursor: pointer; font-weight: 600; padding: 2px 7px;" title="View DOLE calculation breakdown">
                                        <i class="ph ph-calculator"></i> Calculation Details
                                    </button>
                                </div>
                            </td>
                            <td class="amount" style="color: #7c3aed; font-weight: 700; cursor: pointer;" onclick="openPayslipModal('payslipPremiumPayModal')" title="Click to view calculation breakdown">
                                ₱{{ number_format($record->premium_pay > 0 ? $record->premium_pay : $record->rest_day_pay, 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Night Shift Differential ({{ number_format($record->night_diff_hours, 2) }} hrs)
                            </td>
                            <td class="amount">₱{{ number_format($record->night_diff_pay, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Allowances</td>
                            <td class="amount">₱{{ number_format($record->allowances, 2) }}</td>
                        </tr>
                        @if($record->other_earnings > 0)
                            <tr>
                                <td>Bonuses &amp; Adjustments</td>
                                <td class="amount">₱{{ number_format($record->other_earnings, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot class="earnings">
                        <tr>
                            <td>GROSS EARNINGS</td>
                            <td class="amount">₱{{ number_format($record->gross_pay, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Deductions Column -->
            <div class="payslip-table-card">
                <div class="payslip-table-card-header deductions">
                    <i class="ph ph-minus-circle" style="font-size: 15px;"></i>
                    <span>Deductions</span>
                </div>
                <table class="payslip-table">
                    <tbody>
                        <tr>
                            <td>Late &amp; Undertime Deductions</td>
                            <td class="amount deduction">-₱{{ number_format($record->late_deduction + $record->undertime_deduction, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Absence Deductions</td>
                            <td class="amount deduction">-₱{{ number_format($record->absence_deduction, 2) }}</td>
                        </tr>
                        <tr>
                            <td>SSS Employee Share</td>
                            <td class="amount deduction">-₱{{ number_format($record->sss_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>PhilHealth Employee Share (2.5%)</td>
                            <td class="amount deduction">-₱{{ number_format($record->philhealth_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Pag-IBIG Employee Share</td>
                            <td class="amount deduction">-₱{{ number_format($record->pagibig_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>BIR Withholding Tax</td>
                            <td class="amount deduction">-₱{{ number_format($record->withholding_tax, 2) }}</td>
                        </tr>
                        @if($record->other_deductions > 0)
                            <tr>
                                <td>Loans &amp; Adjustments</td>
                                <td class="amount deduction">-₱{{ number_format($record->other_deductions, 2) }}</td>
                            </tr>
                        @endif
                    </tbody>
                    <tfoot class="deductions">
                        <tr>
                            <td>TOTAL DEDUCTIONS</td>
                            <td class="amount">-₱{{ number_format($record->total_deductions, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Net Pay Total Highlight Card -->
        <div class="payslip-takehome-card">
            <div>
                <div class="payslip-takehome-label">
                    <i class="ph ph-wallet" style="font-size: 15px;"></i>
                    <span>Total Net Pay (Take Home)</span>
                </div>
                <div class="payslip-takehome-sub">Philippine Pesos (PHP) &bull; Direct Deposit / Cash Disbursement</div>
            </div>
            <div class="payslip-takehome-amount">
                ₱{{ number_format($record->net_pay, 2) }}
            </div>
        </div>

        <!-- Employer Statutory Shares (Informational) -->
        <div class="payslip-employer-box">
            <span class="payslip-employer-title">
                <i class="ph ph-shield-check" style="color: #6366f1;"></i>
                Employer Contributions (Non-Deductible):
            </span>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <span class="payslip-employer-pill">SSS ER: ₱{{ number_format($record->sss_employer, 2) }}</span>
                <span class="payslip-employer-pill">PhilHealth ER: ₱{{ number_format($record->philhealth_employer, 2) }}</span>
                <span class="payslip-employer-pill">Pag-IBIG ER: ₱{{ number_format($record->pagibig_employer, 2) }}</span>
            </div>
        </div>

        <!-- Signatures -->
        <div class="payslip-signatures-grid">
            <div class="payslip-sign-block">
                <div class="payslip-sign-line"></div>
                <span class="payslip-sign-title">Prepared &amp; Approved by HR / Admin</span>
                <span class="payslip-sign-date">Date: ________________________</span>
            </div>
            <div class="payslip-sign-block">
                <div class="payslip-sign-line"></div>
                <span class="payslip-sign-title">Received &amp; Acknowledged by Employee</span>
                <span class="payslip-sign-date">Date: ________________________</span>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================== -->
<!-- MODAL: PREMIUM PAY CALCULATION DETAILS                   -->
<!-- ======================================================== -->
<div id="payslipPremiumPayModal" class="hr-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); z-index: 1050; align-items: center; justify-content: center; padding: 20px;">
    <div style="background: #ffffff; border-radius: 12px; width: 100%; max-width: 620px; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); border: 1px solid #e2e8f0;">
        <div style="padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #faf5ff 0%, #ffffff 100%);">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3e8ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                    <i class="ph ph-calculator"></i>
                </div>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Premium Pay Calculation Breakdown</h3>
                    <p style="margin: 2px 0 0; font-size: 12px; color: #64748b;">DOLE statutory rest day and special holiday compensation</p>
                </div>
            </div>
            <button type="button" onclick="closePayslipModal('payslipPremiumPayModal')" style="background: none; border: none; cursor: pointer; color: #64748b; font-size: 18px; padding: 4px;">
                <i class="ph ph-x"></i>
            </button>
        </div>

        <div style="padding: 20px 24px; display: flex; flex-direction: column; gap: 16px;">
            <!-- Summary Info -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12.5px;">
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase;">Employee:</span>
                    <div style="font-weight: 700; color: #0f172a;">{{ $record->employee->first_name ?? '' }} {{ $record->employee->last_name ?? '' }}</div>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase;">Daily Rate:</span>
                    <div style="font-weight: 700; color: #0f172a;">₱{{ number_format($record->daily_rate ?? ($record->basic_pay / 22), 2) }} (₱{{ number_format(($record->daily_rate ?? ($record->basic_pay / 22)) / 8, 2) }}/hr)</div>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase;">Payroll Period:</span>
                    <div style="font-weight: 600; color: #334155;">{{ $record->payrollPeriod->period_name ?? 'Current Period' }}</div>
                </div>
                <div>
                    <span style="color: #64748b; font-size: 11px; text-transform: uppercase;">Total Premium Pay:</span>
                    <div style="font-weight: 800; color: #7c3aed; font-size: 14px;">₱{{ number_format($record->premium_pay > 0 ? $record->premium_pay : $record->rest_day_pay, 2) }}</div>
                </div>
            </div>

            <!-- Items List -->
            @php
                $items = $record->premiumPayItems;
                $details = $record->premium_pay_details;
            @endphp

            @if($items && $items->isNotEmpty())
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($items as $idx => $pItem)
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; background: #ffffff;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <div>
                                    <strong style="color: #0f172a; font-size: 13px;">{{ \Carbon\Carbon::parse($pItem->work_date)->format('M d, Y (l)') }}</strong>
                                    <div style="font-size: 11.5px; color: #7c3aed; font-weight: 600;">
                                        {{ $pItem->work_type }} @if($pItem->holiday_name) • {{ $pItem->holiday_name }} @endif
                                    </div>
                                </div>
                                <span style="font-size: 13.5px; font-weight: 800; color: #7c3aed;">
                                    ₱{{ number_format($pItem->premium_amount, 2) }}
                                </span>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; font-size: 11.5px; background: #f8fafc; padding: 8px 10px; border-radius: 6px; margin-top: 6px;">
                                <div>
                                    <span style="color: #64748b;">Regular Hours:</span>
                                    <strong style="color: #0f172a; display: block;">{{ number_format($pItem->hours_worked - $pItem->overtime_hours, 1) }} hrs @ {{ number_format($pItem->applied_multiplier * 100, 0) }}%</strong>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Overtime:</span>
                                    <strong style="color: #b45309; display: block;">{{ number_format($pItem->overtime_hours, 1) }} hrs @ 130%</strong>
                                </div>
                                <div>
                                    <span style="color: #64748b;">Status:</span>
                                    <strong style="color: {{ $pItem->status === 'Approved' ? '#059669' : '#d97706' }}; display: block;">{{ $pItem->status }}</strong>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @elseif($details && is_array($details) && count($details) > 0)
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    @foreach($details as $dItem)
                        <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; background: #ffffff;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div>
                                    <strong style="color: #0f172a; font-size: 13px;">{{ $dItem['date'] ?? 'Work Date' }}</strong>
                                    <div style="font-size: 11.5px; color: #7c3aed; font-weight: 600;">
                                        {{ $dItem['work_type'] ?? 'Premium Work' }}
                                    </div>
                                </div>
                                <span style="font-size: 13.5px; font-weight: 800; color: #7c3aed;">
                                    ₱{{ number_format($dItem['amount'] ?? 0, 2) }}
                                </span>
                            </div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 6px;">
                                {{ $dItem['equation'] ?? ($dItem['hours'] . ' hrs @ ' . ($dItem['multiplier'] * 100) . '%') }}
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <!-- Fallback display when single lumped premium recorded -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; background: #faf5ff;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <strong style="color: #581c87; font-size: 13px;">Scheduled Rest Day Work</strong>
                            <div style="font-size: 11.5px; color: #7c3aed;">DOLE Statutory Rate: 130% daily basic equivalent</div>
                        </div>
                        <span style="font-size: 15px; font-weight: 800; color: #7c3aed;">
                            ₱{{ number_format($record->premium_pay > 0 ? $record->premium_pay : $record->rest_day_pay, 2) }}
                        </span>
                    </div>
                    <div style="margin-top: 10px; font-size: 11.5px; color: #6b21a8; background: #ffffff; padding: 8px 12px; border-radius: 6px; border: 1px solid #f0abfc;">
                        Calculation: 8.0 Regular Hours × 130% Statutory Multiplier
                    </div>
                </div>
            @endif

            <div style="font-size: 11px; color: #64748b; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px dashed #cbd5e1;">
                <i class="ph ph-shield-check" style="color: #7c3aed;"></i>
                Computed by central <strong>PremiumPayRuleEngine</strong> in compliance with DOLE Labor Code Art. 93 and official advisory guidelines.
            </div>
        </div>

        <div style="padding: 14px 24px; border-top: 1px solid #e2e8f0; background: #ffffff; display: flex; justify-content: flex-end;">
            <button type="button" onclick="closePayslipModal('payslipPremiumPayModal')" class="hr-btn hr-btn-secondary" style="height: 32px; padding: 0 14px; font-size: 12px;">
                Close
            </button>
        </div>
    </div>
</div>

<script>
function openPayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'flex';
}

function closePayslipModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.style.display = 'none';
}
</script>
@endsection

@extends('layouts.ess')

@section('title', 'Payroll & Payslips')

@section('content')
<div class="ess-payroll-container">
    <div class="ess-page-header">
        <div>
            <h1 class="ess-page-title">Payroll & Itemized Payslips</h1>
            <p class="ess-page-subtitle">DOLE Labor Advisory No. 26-20 compliant compensation statements, statutory contributions, and dispute filings.</p>
        </div>
        <div class="ess-page-actions">
            @if($latestPayslip)
            <a href="{{ route('ess.payroll.pdf', $latestPayslip->payroll_record_id) }}" target="_blank" class="ess-btn ess-btn-secondary">
                <i class="bi bi-printer"></i> Print Latest Payslip
            </a>
            @endif
        </div>
    </div>

    <!-- YTD & Statutory Summary Cards -->
    <div class="ess-stat-grid" style="margin-bottom: 2rem;">
        <div class="ess-stat-card">
            <div class="ess-stat-icon" style="background: rgba(16, 185, 129, 0.12); color: #059669;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <span class="ess-stat-label">Latest Net Pay</span>
                <h3 class="ess-stat-value" style="color: #059669;">₱{{ number_format($latestPayslip->net_pay ?? 0, 2) }}</h3>
                <span style="font-size: 0.75rem; color: #64748b;">
                    {{ $latestPayslip->payrollPeriod?->name ?? 'No cutoff finalized' }}
                </span>
            </div>
        </div>

        <div class="ess-stat-card">
            <div class="ess-stat-icon" style="background: rgba(37, 99, 235, 0.12); color: #2563eb;">
                <i class="bi bi-graph-up-arrow"></i>
            </div>
            <div>
                <span class="ess-stat-label">{{ now()->year }} YTD Gross Income</span>
                <h3 class="ess-stat-value">₱{{ number_format($ytd->total_gross ?? 0, 2) }}</h3>
                <span style="font-size: 0.75rem; color: #64748b;">Taxable + Non-taxable earnings</span>
            </div>
        </div>

        <div class="ess-stat-card">
            <div class="ess-stat-icon" style="background: rgba(139, 92, 246, 0.12); color: #7c3aed;">
                <i class="bi bi-shield-check"></i>
            </div>
            <div>
                <span class="ess-stat-label">YTD Statutory Contributions</span>
                <h3 class="ess-stat-value">₱{{ number_format($ytd->total_statutory ?? 0, 2) }}</h3>
                <span style="font-size: 0.75rem; color: #64748b;">SSS + PhilHealth + Pag-IBIG</span>
            </div>
        </div>

        <div class="ess-stat-card">
            <div class="ess-stat-icon" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                <i class="bi bi-receipt"></i>
            </div>
            <div>
                <span class="ess-stat-label">YTD Withholding Tax</span>
                <h3 class="ess-stat-value">₱{{ number_format($ytd->total_tax ?? 0, 2) }}</h3>
                <span style="font-size: 0.75rem; color: #64748b;">BIR TRAIN Law Table</span>
            </div>
        </div>
    </div>

    @if($latestPayslip)
    <!-- Latest Payslip DOLE Detailed Itemization Card -->
    <div class="ess-card" style="margin-bottom: 2rem; border-top: 4px solid #2563eb;">
        <div class="ess-card-header" style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 1rem;">
            <div>
                <h2 style="font-size: 1.15rem; font-weight: 700; color: #1e293b; margin: 0;">
                    Latest Payslip Breakdown: {{ $latestPayslip->payrollPeriod?->name }}
                </h2>
                <span style="font-size: 0.825rem; color: #64748b;">
                    Cutoff: {{ $latestPayslip->payrollPeriod?->start_date?->format('M d, Y') }} - {{ $latestPayslip->payrollPeriod?->end_date?->format('M d, Y') }} &bull; Payout Date: {{ $latestPayslip->payrollPeriod?->payout_date?->format('M d, Y') ?? 'N/A' }}
                </span>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                <button type="button" class="ess-btn ess-btn-secondary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;" onclick="openDisputeModal({{ $latestPayslip->payroll_record_id }}, '{{ $latestPayslip->payrollPeriod?->name }}')">
                    <i class="bi bi-question-circle"></i> File Dispute
                </button>
                <a href="{{ route('ess.payroll.pdf', $latestPayslip->payroll_record_id) }}" target="_blank" class="ess-btn ess-btn-primary" style="font-size: 0.85rem; padding: 0.4rem 0.8rem;">
                    <i class="bi bi-file-earmark-pdf"></i> Download PDF
                </a>
            </div>
        </div>

        <div style="padding-top: 1.25rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem;">
                <!-- Earnings Column -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 1.25rem; border: 1px solid #e2e8f0;">
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                        <span><i class="bi bi-plus-circle text-success me-1"></i> Gross Earnings</span>
                        <span class="text-success">₱{{ number_format($latestPayslip->gross_pay, 2) }}</span>
                    </h3>

                    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Basic Salary ({{ $latestPayslip->total_work_days }} days / {{ $latestPayslip->total_hours }} hrs)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->basic_pay, 2) }}</td>
                        </tr>
                        @if($latestPayslip->overtime_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Overtime Pay ({{ $latestPayslip->overtime_hours }} hrs)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->overtime_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->night_diff_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Night Shift Diff (+10%, {{ $latestPayslip->night_diff_hours }} hrs)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->night_diff_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->holiday_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Holiday Pay (DOLE Mandated)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->holiday_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->rest_day_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Rest Day Premium</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->rest_day_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->allowances > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Allowances & De Minimis</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->allowances, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->other_earnings > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Other Incentives & Adjustments</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->other_earnings, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- Deductions Column -->
                <div style="background: #f8fafc; border-radius: 10px; padding: 1.25rem; border: 1px solid #e2e8f0;">
                    <h3 style="font-size: 0.95rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding-bottom: 0.5rem;">
                        <span><i class="bi bi-dash-circle text-danger me-1"></i> Total Deductions</span>
                        <span class="text-danger">₱{{ number_format($latestPayslip->total_deductions, 2) }}</span>
                    </h3>

                    <table style="width: 100%; font-size: 0.875rem; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">SSS Contribution (Employee Share)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->sss_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">PhilHealth Contribution (Employee Share)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->philhealth_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Pag-IBIG Fund (HDMF Employee Share)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->pagibig_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Withholding Tax (TRAIN Law)</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->withholding_tax, 2) }}</td>
                        </tr>
                        @if($latestPayslip->late_deduction > 0 || $latestPayslip->undertime_deduction > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Tardiness / Undertime Deductions</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->late_deduction + $latestPayslip->undertime_deduction, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->employee_loan > 0 || $latestPayslip->salary_advance > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Company Advances & Amortization</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->employee_loan + $latestPayslip->salary_advance, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->other_deductions > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9; height: 2rem;">
                            <td style="color: #64748b;">Other Lawful Deductions</td>
                            <td style="text-align: right; font-weight: 600; color: #1e293b;">₱{{ number_format($latestPayslip->other_deductions, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>

            <!-- Net Pay Highlight Banner -->
            <div style="margin-top: 1.5rem; background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 10px; padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; color: white;">
                <div>
                    <span style="font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8;">Net Take-Home Pay</span>
                    <div style="font-size: 0.8rem; color: #cbd5e1;">Deposited directly to your nominated payroll account.</div>
                </div>
                <div style="font-size: 1.75rem; font-weight: 800; color: #34d399;">
                    ₱{{ number_format($latestPayslip->net_pay, 2) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payslip History Archive Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h2 class="ess-card-title"><i class="bi bi-clock-history"></i> Historical Payslips Archive</h2>
        </div>

        <div class="ess-table-container">
            <table class="ess-table">
                <thead>
                    <tr>
                        <th>Cutoff Period</th>
                        <th>Payout Date</th>
                        <th style="text-align: right;">Gross Pay</th>
                        <th style="text-align: right;">Statutory</th>
                        <th style="text-align: right;">Tax</th>
                        <th style="text-align: right;">Net Pay</th>
                        <th>Status</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                    <tr>
                        <td>
                            <strong style="color: #1e293b;">{{ $rec->payrollPeriod?->name ?? 'Cutoff #' . $rec->payroll_period_id }}</strong>
                            <div style="font-size: 0.75rem; color: #64748b;">
                                {{ $rec->payrollPeriod?->start_date?->format('M d') }} - {{ $rec->payrollPeriod?->end_date?->format('M d, Y') }}
                            </div>
                        </td>
                        <td>{{ $rec->payrollPeriod?->payout_date?->format('M d, Y') ?? '—' }}</td>
                        <td style="text-align: right; font-weight: 600;">₱{{ number_format($rec->gross_pay, 2) }}</td>
                        <td style="text-align: right; color: #64748b;">
                            ₱{{ number_format($rec->sss_employee + $rec->philhealth_employee + $rec->pagibig_employee, 2) }}
                        </td>
                        <td style="text-align: right; color: #64748b;">₱{{ number_format($rec->withholding_tax, 2) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #059669;">₱{{ number_format($rec->net_pay, 2) }}</td>
                        <td>
                            <span class="ess-badge {{ $rec->status === 'Finalized' ? 'ess-badge-success' : 'ess-badge-neutral' }}">
                                {{ $rec->status }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 0.35rem;">
                                <a href="{{ route('ess.payroll.pdf', $rec->payroll_record_id) }}" target="_blank" class="ess-btn ess-btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" title="Download PDF Payslip">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                </a>
                                <button type="button" class="ess-btn ess-btn-secondary" style="padding: 0.3rem 0.6rem; font-size: 0.75rem;" onclick="openDisputeModal({{ $rec->payroll_record_id }}, '{{ $rec->payrollPeriod?->name }}')" title="File Dispute / Inquiry">
                                    <i class="bi bi-question-circle"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 3rem; color: #94a3b8;">
                            <i class="bi bi-wallet2" style="font-size: 2.5rem; display: block; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                            No finalized payslip records found for your account.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div style="padding: 1rem;">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Dispute Modal -->
<div class="modal fade" id="disputeModal" tabindex="-1" aria-hidden="true" style="display: none; background: rgba(15,23,42,0.6); position: fixed; inset: 0; z-index: 1050; align-items: center; justify-content: center;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 520px; width: 100%; margin: 1.5rem auto;">
        <div class="modal-content" style="background: white; border-radius: 12px; border: none; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);">
            <form action="{{ route('ess.payroll.dispute') }}" method="POST">
                @csrf
                <input type="hidden" name="payroll_record_id" id="disputeRecordId">
                <div class="modal-header" style="padding: 1.25rem 1.5rem; border-bottom: 1px solid #f1f5f9;">
                    <h5 class="modal-title" style="font-weight: 700; color: #1e293b; font-size: 1.1rem;">
                        <i class="bi bi-shield-exclamation text-warning me-1"></i> File Payroll Dispute / Inquiry
                    </h5>
                    <button type="button" class="btn-close" onclick="closeDisputeModal()"></button>
                </div>
                <div class="modal-body" style="padding: 1.5rem;">
                    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 1rem;">
                        Selected Period: <strong id="disputePeriodName" style="color: #1e293b;"></strong>
                    </p>

                    <div class="ess-form-group" style="margin-bottom: 1rem;">
                        <label class="ess-form-label">Dispute Category <span class="text-danger">*</span></label>
                        <select name="dispute_category" class="ess-form-control" required>
                            <option value="">Select category...</option>
                            <option value="MISSING_OVERTIME">Missing / Uncredited Overtime Hours</option>
                            <option value="INCORRECT_RATE">Discrepancy in Hourly / Daily Basic Rate</option>
                            <option value="NIGHT_DIFF_ERROR">Night Shift Differential (NSD) Discrepancy</option>
                            <option value="HOLIDAY_PREMIUM">Uncredited Holiday / Rest Day Premium Pay</option>
                            <option value="STATUTORY_VARIANCE">SSS / PhilHealth / Pag-IBIG Deduction Variance</option>
                            <option value="TAX_WITHHOLDING">BIR Withholding Tax Recomputation Request</option>
                            <option value="OTHER">Other Discrepancy</option>
                        </select>
                    </div>

                    <div class="ess-form-group" style="margin-bottom: 0.5rem;">
                        <label class="ess-form-label">Detailed Explanation <span class="text-danger">*</span></label>
                        <textarea name="details" class="ess-form-control" rows="4" placeholder="Specify the exact date, missing hours, expected calculation vs. received amount..." required></textarea>
                        <small style="font-size: 0.75rem; color: #94a3b8; display: block; margin-top: 0.25rem;">
                            Your inquiry will be logged under DOLE Labor Compliance standards for immediate HR review.
                        </small>
                    </div>
                </div>
                <div class="modal-footer" style="padding: 1rem 1.5rem; border-top: 1px solid #f1f5f9; display: flex; justify-content: flex-end; gap: 0.5rem;">
                    <button type="button" class="ess-btn ess-btn-secondary" onclick="closeDisputeModal()">Cancel</button>
                    <button type="submit" class="ess-btn ess-btn-primary">Submit Dispute</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openDisputeModal(recordId, periodName) {
    document.getElementById('disputeRecordId').value = recordId;
    document.getElementById('disputePeriodName').innerText = periodName;
    const modal = document.getElementById('disputeModal');
    modal.style.display = 'flex';
}
function closeDisputeModal() {
    const modal = document.getElementById('disputeModal');
    modal.style.display = 'none';
}
window.addEventListener('click', function(e) {
    const modal = document.getElementById('disputeModal');
    if (e.target === modal) {
        modal.style.display = 'none';
    }
});
</script>
@endsection

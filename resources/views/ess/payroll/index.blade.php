@extends('layouts.ess')

@section('title', 'Payroll & Payslips')

@section('content')
<div class="ess-payroll-container">
    <!-- Standard HR Page Header -->
    <div class="hr-page-header" style="margin-bottom: 20px;">
        <div>
            <h1 class="hr-page-title">
                <i class="ph ph-receipt"></i>
                <span>Payroll & Itemized Payslips</span>
            </h1>
            <p class="hr-page-subtitle">DOLE Labor Advisory No. 26-20 compliant compensation statements, statutory contributions, and dispute filings.</p>
        </div>
        <div class="hr-page-actions">
            @if($latestPayslip)
            <a href="{{ route('ess.payroll.pdf', $latestPayslip->payroll_record_id) }}" target="_blank" class="hr-btn hr-btn-secondary">
                <i class="ph ph-printer"></i>
                <span>Print Latest Payslip</span>
            </a>
            @endif
        </div>
    </div>

    <!-- YTD & Statutory Summary Cards (HR Bento Style) -->
    <div class="ess-grid-4">
        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box green">
                <i class="ph ph-wallet"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">Latest Net Pay</span>
                <span class="ess-kpi-val" style="color: #059669;">₱{{ number_format($latestPayslip->net_pay ?? 0, 2) }}</span>
                <span style="font-size: 11px; color: #64748b; margin-top: 4px;">
                    {{ $latestPayslip->payrollPeriod?->name ?? 'No cutoff finalized' }}
                </span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box blue">
                <i class="ph ph-trend-up"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">{{ now()->year }} YTD Gross Income</span>
                <span class="ess-kpi-val">₱{{ number_format($ytd->total_gross ?? 0, 2) }}</span>
                <span style="font-size: 11px; color: #64748b; margin-top: 4px;">Taxable + Non-taxable</span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box purple">
                <i class="ph ph-shield-check"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">YTD Statutory</span>
                <span class="ess-kpi-val">₱{{ number_format($ytd->total_statutory ?? 0, 2) }}</span>
                <span style="font-size: 11px; color: #64748b; margin-top: 4px;">SSS + PhilHealth + Pag-IBIG</span>
            </div>
        </div>

        <div class="ess-kpi-card">
            <div class="hr-bento-icon-box orange">
                <i class="ph ph-receipt"></i>
            </div>
            <div class="ess-kpi-info">
                <span class="ess-kpi-label">YTD Withholding Tax</span>
                <span class="ess-kpi-val">₱{{ number_format($ytd->total_tax ?? 0, 2) }}</span>
                <span style="font-size: 11px; color: #64748b; margin-top: 4px;">BIR TRAIN Law Table</span>
            </div>
        </div>
    </div>

    @if($latestPayslip)
    <!-- Latest Payslip DOLE Detailed Itemization Card -->
    <div class="ess-card" style="margin-bottom: 22px;">
        <div class="ess-card-accent-strip"></div>
        <div class="ess-card-header" style="border-bottom: 1px solid var(--ess-border); padding-bottom: 14px;">
            <div>
                <h2 style="font-family:'Outfit',sans-serif;font-size:18px;font-weight:800;color:var(--ess-text-main);margin:0;">
                    Latest Payslip Breakdown: {{ $latestPayslip->payrollPeriod?->name }}
                </h2>
                <div style="font-size:12.5px;color:var(--ess-text-muted);margin-top:4px;">
                    Cutoff: {{ $latestPayslip->payrollPeriod?->start_date?->format('M d, Y') }} – {{ $latestPayslip->payrollPeriod?->end_date?->format('M d, Y') }} &bull; Payout Date: {{ $latestPayslip->payrollPeriod?->payout_date?->format('M d, Y') ?? 'N/A' }}
                </div>
            </div>
            <div style="display:flex;gap:8px;">
                <button type="button" class="hr-btn hr-btn-secondary" style="font-size:12px;padding:6px 12px;" onclick="openDisputeModal({{ $latestPayslip->payroll_record_id }}, '{{ $latestPayslip->payrollPeriod?->name }}')">
                    <i class="ph ph-question"></i> File Dispute
                </button>
                <a href="{{ route('ess.payroll.pdf', $latestPayslip->payroll_record_id) }}" target="_blank" class="hr-btn hr-btn-primary" style="font-size:12px;padding:6px 12px;">
                    <i class="ph ph-file-pdf"></i> Download PDF
                </a>
            </div>
        </div>

        <div style="padding-top: 18px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
                <!-- Earnings Column -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 18px; border: 1px solid var(--ess-border);">
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">
                        <span><i class="ph ph-plus-circle" style="color:#16a34a;margin-right:6px;"></i> Gross Earnings</span>
                        <span style="color:#16a34a;font-weight:800;">₱{{ number_format($latestPayslip->gross_pay, 2) }}</span>
                    </div>

                    <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Basic Monthly Salary</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600;">₱{{ number_format($latestPayslip->basic_salary, 2) }}</td>
                        </tr>
                        @if($latestPayslip->overtime_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Overtime Pay</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->overtime_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->night_differential_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Night Shift Differential (10%)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->night_differential_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->holiday_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Holiday Premium Pay</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->holiday_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->rest_day_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Rest Day Work Pay</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->rest_day_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->allowances > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Non-Taxable Allowances (De Minimis)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->allowances, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->thirteenth_month_pay > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">13th Month Pay Portion</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #16a34a;">+₱{{ number_format($latestPayslip->thirteenth_month_pay, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </div>

                <!-- Deductions Column -->
                <div style="background: #f8fafc; border-radius: 12px; padding: 18px; border: 1px solid var(--ess-border);">
                    <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; display: flex; justify-content: space-between; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">
                        <span><i class="ph ph-minus-circle" style="color:#dc2626;margin-right:6px;"></i> Total Deductions</span>
                        <span style="color:#dc2626;font-weight:800;">-₱{{ number_format($latestPayslip->total_deductions, 2) }}</span>
                    </div>

                    <table style="width: 100%; font-size: 12.5px; border-collapse: collapse;">
                        @if($latestPayslip->tardiness_deduction > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Tardiness Deduction</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->tardiness_deduction, 2) }}</td>
                        </tr>
                        @endif
                        @if($latestPayslip->undertime_deduction > 0)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Undertime Deduction</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->undertime_deduction, 2) }}</td>
                        </tr>
                        @endif
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">SSS Contribution (Employee)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->sss_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">PhilHealth Contribution (Employee)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->philhealth_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Pag-IBIG Contribution (Employee)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->pagibig_employee, 2) }}</td>
                        </tr>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 7px 0; color: #475569;">Withholding Tax (BIR)</td>
                            <td style="padding: 7px 0; text-align: right; font-weight: 600; color: #dc2626;">-₱{{ number_format($latestPayslip->withholding_tax, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Net Take-Home Pay Banner -->
            <div style="margin-top: 18px; background: linear-gradient(135deg, rgba(236,72,153,0.06), rgba(168,85,247,0.12)); border: 1px solid rgba(168,85,247,0.25); border-radius: 12px; padding: 16px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>
                    <span style="font-size: 11.5px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.05em;">Net Take-Home Pay</span>
                    <div style="font-size: 12px; color: #64748b;">Direct credit to employee payroll disbursement account</div>
                </div>
                <div style="font-family:'Outfit',sans-serif;font-size: 26px; font-weight: 800; color: #0f172a;">
                    ₱{{ number_format($latestPayslip->net_pay, 2) }}
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Payslip History Archive Table -->
    <div class="ess-card">
        <div class="ess-card-header">
            <h2 class="ess-card-title">
                <div class="hr-bento-icon-box purple" style="width:30px;height:30px;font-size:16px;">
                    <i class="ph ph-clock-counter-clockwise"></i>
                </div>
                <span>Historical Payslips Archive</span>
            </h2>
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
                            <strong style="color: #0f172a;">{{ $rec->payrollPeriod?->name ?? 'Cutoff #' . $rec->payroll_period_id }}</strong>
                            <div style="font-size: 11.5px; color: #64748b;">
                                {{ $rec->payrollPeriod?->start_date?->format('M d') }} – {{ $rec->payrollPeriod?->end_date?->format('M d, Y') }}
                            </div>
                        </td>
                        <td>{{ $rec->payrollPeriod?->payout_date?->format('M d, Y') ?? '—' }}</td>
                        <td style="text-align: right; font-weight: 600;">₱{{ number_format($rec->gross_pay, 2) }}</td>
                        <td style="text-align: right; color: #64748b;">
                            ₱{{ number_format($rec->sss_employee + $rec->philhealth_employee + $rec->pagibig_employee, 2) }}
                        </td>
                        <td style="text-align: right; color: #64748b;">₱{{ number_format($rec->withholding_tax, 2) }}</td>
                        <td style="text-align: right; font-weight: 700; color: #16a34a;">₱{{ number_format($rec->net_pay, 2) }}</td>
                        <td>
                            <span class="ess-badge {{ $rec->status === 'Finalized' ? 'ess-badge-success' : 'ess-badge-neutral' }}">
                                {{ $rec->status }}
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <div style="display: inline-flex; gap: 6px;">
                                <a href="{{ route('ess.payroll.pdf', $rec->payroll_record_id) }}" target="_blank" class="hr-btn hr-btn-secondary" style="padding: 5px 8px; font-size: 12px;" title="Download PDF Payslip">
                                    <i class="ph ph-file-pdf"></i>
                                </a>
                                <button type="button" class="hr-btn hr-btn-secondary" style="padding: 5px 8px; font-size: 12px;" onclick="openDisputeModal({{ $rec->payroll_record_id }}, '{{ $rec->payrollPeriod?->name }}')" title="File Dispute / Inquiry">
                                    <i class="ph ph-question"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 48px 16px; color: #94a3b8;">
                            <i class="ph ph-wallet" style="font-size: 40px; display: block; margin: 0 auto 10px; opacity: 0.5;"></i>
                            No finalized payslip records found for your account.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($records->hasPages())
        <div style="padding: 16px;">
            {{ $records->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Dispute Modal -->
<div id="disputeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-warning-circle"></i> File Payroll Dispute / Inquiry</span>
            <button type="button" class="icon-btn" onclick="closeModal('disputeModal')" aria-label="Close"><i class="ph ph-x"></i></button>
        </div>
        <form action="{{ route('ess.payroll.dispute') }}" method="POST">
            @csrf
            <input type="hidden" name="payroll_record_id" id="disputeRecordId">
            <div class="hr-modal-body">
                <p style="font-size: 13px; color: #64748b; margin-bottom: 8px;">
                    Selected Period: <strong id="disputePeriodName" style="color: #0f172a;"></strong>
                </p>

                <div class="ess-form-group">
                    <label class="ess-form-label">Dispute Category *</label>
                    <select name="dispute_category" class="ess-form-control" style="width:100%;" required>
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

                <div class="ess-form-group">
                    <label class="ess-form-label">Detailed Explanation *</label>
                    <textarea name="details" class="ess-form-control" style="width:100%;" rows="4" placeholder="Specify the exact date, missing hours, expected calculation vs. received amount..." required></textarea>
                    <small style="font-size: 11px; color: #94a3b8; display: block; margin-top: 5px;">
                        Your inquiry will be logged under DOLE Labor Compliance standards for immediate HR review.
                    </small>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('disputeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Dispute</button>
            </div>
        </form>
    </div>
</div>

<script>
function openDisputeModal(recordId, periodName) {
    document.getElementById('disputeRecordId').value = recordId;
    document.getElementById('disputePeriodName').innerText = periodName;
    openModal('disputeModal');
}
function closeDisputeModal() {
    closeModal('disputeModal');
}
</script>
@endsection

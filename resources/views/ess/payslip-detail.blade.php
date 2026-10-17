@extends('layouts.app')

@section('title', 'Payslip Breakdown')

@section('content')
<div class="ess-payslip-detail" style="max-width: 900px; margin: 0 auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <a href="{{ route('ess.payroll') }}" class="btn btn-outline">
            <i class="ph ph-arrow-left"></i> Back to All Payslips
        </a>
        <a href="{{ route('ess.payroll.payslip-pdf', $record->id) }}" target="_blank" class="btn btn-primary">
            <i class="ph ph-printer"></i> Print Payslip (PDF)
        </a>
    </div>

    <div class="card" style="padding: 32px; border-radius: 16px; background: #ffffff; box-shadow: 0 4px 20px rgba(0,0,0,0.06);">
        <!-- Company Header -->
        <div style="text-align: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 20px; margin-bottom: 24px;">
            <h2 style="font-size: 20px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">
                {{ $record->employee?->company?->name ?? 'BISTRO HOSPITALITY GROUP INC.' }}
            </h2>
            <div style="font-size: 13px; color: #64748b;">
                {{ $record->employee?->branch?->branch_name ?? 'Makati Flagship Branch' }}
            </div>
            <div style="display: inline-block; margin-top: 8px; font-size: 13px; font-weight: 700; color: #2563eb; background: #eff6ff; padding: 4px 14px; border-radius: 20px;">
                CONFIDENTIAL PAYSLIP (DOLE COMPLIANT)
            </div>
        </div>

        <!-- Employee & Period Meta -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; font-size: 13px; background: #f8fafc; padding: 16px; border-radius: 10px;">
            <div>
                <div><strong>Employee:</strong> {{ $record->employee?->first_name }} {{ $record->employee?->last_name }}</div>
                <div style="margin-top: 4px;"><strong>Employee ID:</strong> {{ $record->employee_id }}</div>
                <div style="margin-top: 4px;"><strong>Position:</strong> {{ $record->employee?->position?->name ?? 'Staff' }}</div>
            </div>
            <div>
                <div><strong>Period:</strong> {{ $record->payrollPeriod?->start_date?->format('M d') }} - {{ $record->payrollPeriod?->end_date?->format('M d, Y') }}</div>
                <div style="margin-top: 4px;"><strong>Payment Date:</strong> {{ $record->payrollPeriod?->payout_date?->format('M d, Y') ?? 'End of Cutoff' }}</div>
                <div style="margin-top: 4px;"><strong>Monthly Wage Base:</strong> ₱{{ number_format($record->basic_pay, 2) }}</div>
            </div>
        </div>

        <!-- Earnings and Deductions 2-Column Grid -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
            <!-- Earnings Column -->
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <h4 style="font-size: 14px; font-weight: 700; color: #166534; margin: 0 0 14px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <i class="ph ph-plus-circle"></i> Itemized Earnings
                </h4>
                <div style="font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Basic Regular Pay</span>
                        <strong>₱{{ number_format($record->basic_pay, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Overtime Pay (Art. 87)</span>
                        <strong>₱{{ number_format($record->overtime_pay, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Night Shift Differential (Art. 86)</span>
                        <strong>₱{{ number_format($record->night_diff_pay ?? 0, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Holiday & Rest Day Premiums</span>
                        <strong>₱{{ number_format($record->holiday_pay ?? 0, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Allowances & Non-Taxable Incentives</span>
                        <strong>₱{{ number_format($record->allowances ?? 0, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px; font-size: 14px; color: #166534;">
                        <strong>Total Gross Earnings</strong>
                        <strong>₱{{ number_format($record->gross_pay, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Deductions Column -->
            <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px;">
                <h4 style="font-size: 14px; font-weight: 700; color: #991b1b; margin: 0 0 14px 0; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">
                    <i class="ph ph-minus-circle"></i> Itemized Deductions
                </h4>
                <div style="font-size: 13px; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>SSS Contribution (EE Share)</span>
                        <strong>₱{{ number_format($record->sss_employee, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>PhilHealth (EE Share)</span>
                        <strong>₱{{ number_format($record->philhealth_employee, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Pag-IBIG / HDMF (EE Share)</span>
                        <strong>₱{{ number_format($record->pagibig_employee, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Withholding Income Tax (TRAIN)</span>
                        <strong>₱{{ number_format($record->withholding_tax, 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span>Tardiness & Undertime Deductions</span>
                        <strong>₱{{ number_format(($record->late_deduction ?? 0) + ($record->undertime_deduction ?? 0), 2) }}</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 8px; margin-top: 4px; font-size: 14px; color: #991b1b;">
                        <strong>Total Deductions</strong>
                        <strong>₱{{ number_format($record->total_deductions, 2) }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Pay Highlight Banner -->
        <div style="background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #ffffff; padding: 20px 24px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; opacity: 0.85;">NET TAKE HOME PAY</div>
                <div style="font-size: 12px; opacity: 0.75;">Payable to Authorized Bank Account</div>
            </div>
            <div style="font-size: 28px; font-weight: 800;">
                ₱{{ number_format($record->net_pay, 2) }}
            </div>
        </div>
    </div>
</div>
@endsection

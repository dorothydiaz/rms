@extends('layouts.app')

@section('title', 'Payslip - ' . ($record->employee?->full_name ?? 'Employee'))

@section('content')
<div class="hr-page-header no-print">
    <div>
        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
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
<div class="payslip-container">
    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 20px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">
                {{ $record->employee?->branch?->company?->name ?? 'Bistro Hospitality Group Inc.' }}
            </h2>
            <div style="font-size: 13px; color: #475569; margin-top: 2px;">
                {{ $record->employee?->branch?->name }} &bull; {{ $record->employee?->branch?->address }}
            </div>
            <div style="font-size: 12px; color: #64748b;">
                TIN: {{ $record->employee?->branch?->company?->tin ?? '123-456-789-000' }}
            </div>
        </div>
        <div style="text-align: right;">
            <span style="display: inline-block; background: #0f172a; color: #ffffff; font-weight: 700; font-size: 12px; padding: 4px 10px; border-radius: 4px; text-transform: uppercase;">
                Official Payslip
            </span>
            <div style="font-size: 12px; color: #64748b; margin-top: 6px;">
                Cutoff: <strong>{{ \Carbon\Carbon::parse($record->payrollPeriod?->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($record->payrollPeriod?->end_date)->format('M d, Y') }}</strong><br>
                Payout Date: <strong>{{ \Carbon\Carbon::parse($record->payrollPeriod?->payout_date)->format('M d, Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- Employee Info Bar -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 20px; font-size: 12.5px;">
        <div>
            <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase;">Employee Name:</span>
            <strong style="color: #0f172a; font-size: 14px;">{{ $record->employee?->full_name }}</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase;">Employee ID:</span>
            <strong style="font-family: monospace; font-size: 13px;">{{ $record->employee?->employee_id }}</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase;">Department & Position:</span>
            <strong>{{ $record->employee?->department?->name }} / {{ $record->employee?->position?->name }}</strong>
        </div>
        <div>
            <span style="color: #64748b; display: block; font-size: 11px; text-transform: uppercase;">Days / Hours Worked:</span>
            <strong>{{ $record->total_work_days }} Days ({{ number_format($record->total_hours, 2) }} hrs)</strong>
        </div>
    </div>

    <!-- Earnings & Deductions Tables -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px;">
        <!-- Earnings Column -->
        <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <div style="background: #f1f5f9; padding: 10px 14px; font-weight: 700; font-size: 13px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                Earnings
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                <tbody>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Basic Salary Pay</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->basic_pay, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">
                            Overtime Pay ({{ number_format($record->overtime_hours, 2) }} hrs @ 125%)
                        </td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->overtime_pay, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">
                            Night Shift Differential ({{ number_format($record->night_diff_hours, 2) }} hrs)
                        </td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->night_diff_pay, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Holiday & Rest Day Premiums</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->holiday_pay + $record->rest_day_pay, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Allowances</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->allowances, 2) }}</td>
                    </tr>
                    @if($record->other_earnings > 0)
                        <tr>
                            <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Bonuses & Adjustments</td>
                            <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">₱{{ number_format($record->other_earnings, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background: #faf5ff; font-weight: 700; color: #7e22ce;">
                        <td style="padding: 10px 14px; border-top: 1px solid #e9d5ff;">GROSS EARNINGS</td>
                        <td style="padding: 10px 14px; border-top: 1px solid #e9d5ff; text-align: right; font-size: 14px;">₱{{ number_format($record->gross_pay, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Deductions Column -->
        <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
            <div style="background: #f1f5f9; padding: 10px 14px; font-weight: 700; font-size: 13px; color: #0f172a; text-transform: uppercase; border-bottom: 1px solid #e2e8f0;">
                Deductions
            </div>
            <table style="width: 100%; border-collapse: collapse; font-size: 12.5px;">
                <tbody>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Late & Undertime Deductions</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600; color: #ef4444;">-₱{{ number_format($record->late_deduction + $record->undertime_deduction, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Absence Deductions</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600; color: #ef4444;">-₱{{ number_format($record->absence_deduction, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">SSS Employee Share</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">-₱{{ number_format($record->sss_employee, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">PhilHealth Employee Share (2.5%)</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">-₱{{ number_format($record->philhealth_employee, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Pag-IBIG Employee Share</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">-₱{{ number_format($record->pagibig_employee, 2) }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">BIR Withholding Tax</td>
                        <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600;">-₱{{ number_format($record->withholding_tax, 2) }}</td>
                    </tr>
                    @if($record->other_deductions > 0)
                        <tr>
                            <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9;">Loans & Adjustments</td>
                            <td style="padding: 9px 14px; border-bottom: 1px solid #f1f5f9; text-align: right; font-weight: 600; color: #ef4444;">-₱{{ number_format($record->other_deductions, 2) }}</td>
                        </tr>
                    @endif
                </tbody>
                <tfoot>
                    <tr style="background: #fff1f2; font-weight: 700; color: #e11d48;">
                        <td style="padding: 10px 14px; border-top: 1px solid #fecdd3;">TOTAL DEDUCTIONS</td>
                        <td style="padding: 10px 14px; border-top: 1px solid #fecdd3; text-align: right; font-size: 14px;">-₱{{ number_format($record->total_deductions, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Net Pay Total Highlight Card -->
    <div style="background: linear-gradient(135deg, #059669, #047857); color: #ffffff; border-radius: 10px; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <div style="font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; opacity: 0.9;">Total Net Pay (Take Home)</div>
            <div style="font-size: 13px; opacity: 0.85;">Philippine Pesos (PHP)</div>
        </div>
        <div style="font-size: 28px; font-weight: 800; letter-spacing: 0.02em;">
            ₱{{ number_format($record->net_pay, 2) }}
        </div>
    </div>

    <!-- Employer Statutory Shares (Informational) -->
    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px 18px; margin-bottom: 30px; font-size: 11.5px; color: #64748b; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
        <span><strong>Employer Contributions:</strong></span>
        <span>SSS ER: ₱{{ number_format($record->sss_employer, 2) }}</span>
        <span>PhilHealth ER: ₱{{ number_format($record->philhealth_employer, 2) }}</span>
        <span>Pag-IBIG ER: ₱{{ number_format($record->pagibig_employer, 2) }}</span>
    </div>

    <!-- Signatures -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 60px; margin-top: 40px; font-size: 12px;">
        <div style="text-align: center;">
            <div style="border-top: 1px solid #475569; width: 220px; margin: 0 auto 6px auto;"></div>
            <span style="color: #64748b;">Prepared & Approved by HR / Admin</span>
        </div>
        <div style="text-align: center;">
            <div style="border-top: 1px solid #475569; width: 220px; margin: 0 auto 6px auto;"></div>
            <span style="color: #64748b;">Received & Acknowledged by Employee</span>
        </div>
    </div>
</div>
@endsection

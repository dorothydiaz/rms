<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - {{ $employee->first_name }} {{ $employee->last_name }} ({{ $record->payrollPeriod?->period_start?->format('M d') }} - {{ $record->payrollPeriod?->period_end?->format('M d, Y') }})</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; color: #1e293b; }
        .header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 12px; }
        .header h2 { margin: 0; font-size: 15px; }
        .header p { margin: 2px 0; font-size: 10px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .meta-table td { padding: 3px; font-size: 10px; }
        .payslip-grid { display: flex; gap: 15px; margin-bottom: 15px; }
        .col { flex: 1; border: 1px solid #000; padding: 10px; }
        .col h4 { margin: 0 0 8px 0; font-size: 11px; border-bottom: 1px solid #000; padding-bottom: 4px; }
        .row { display: flex; justify-content: space-between; margin-bottom: 4px; font-size: 10px; }
        .total-row { display: flex; justify-content: space-between; border-top: 1px solid #000; margin-top: 6px; padding-top: 4px; font-weight: bold; font-size: 11px; }
        .net-banner { border: 2px solid #000; padding: 10px 15px; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: bold; margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 12px;">
        <button onclick="window.print()" style="padding: 6px 14px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Print Payslip</button>
    </div>

    <div class="header">
        <h2>{{ $employee->company?->name ?? 'BISTRO HOSPITALITY GROUP INC.' }}</h2>
        <p>{{ $employee->branch?->branch_name ?? 'Makati Flagship Branch' }}</p>
        <p><strong>OFFICIAL PAYSLIP (DOLE COMPLIANT)</strong></p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="15%"><strong>Employee Name:</strong></td>
            <td width="35%">{{ $employee->last_name }}, {{ $employee->first_name }}</td>
            <td width="15%"><strong>Employee ID:</strong></td>
            <td width="35%">{{ $employee->employee_id }}</td>
        </tr>
        <tr>
            <td><strong>Department / Position:</strong></td>
            <td>{{ $employee->department?->name }} / {{ $employee->position?->name }}</td>
            <td><strong>Payroll Period:</strong></td>
            <td>{{ $record->payrollPeriod?->start_date?->format('M d') }} - {{ $record->payrollPeriod?->end_date?->format('M d, Y') }}</td>
        </tr>
    </table>

    <div class="payslip-grid">
        <!-- Earnings -->
        <div class="col">
            <h4>EARNINGS</h4>
            <div class="row"><span>Basic Pay</span><span>₱{{ number_format($record->basic_pay, 2) }}</span></div>
            <div class="row"><span>Overtime (Art. 87)</span><span>₱{{ number_format($record->overtime_pay, 2) }}</span></div>
            <div class="row"><span>Night Differential (Art. 86)</span><span>₱{{ number_format($record->night_diff_pay ?? 0, 2) }}</span></div>
            <div class="row"><span>Holiday & Rest Day</span><span>₱{{ number_format($record->holiday_pay ?? 0, 2) }}</span></div>
            <div class="row"><span>Allowances</span><span>₱{{ number_format($record->allowances ?? 0, 2) }}</span></div>
            <div class="total-row"><span>GROSS EARNINGS</span><span>₱{{ number_format($record->gross_pay, 2) }}</span></div>
        </div>

        <!-- Deductions -->
        <div class="col">
            <h4>DEDUCTIONS</h4>
            <div class="row"><span>SSS Contribution</span><span>₱{{ number_format($record->sss_employee, 2) }}</span></div>
            <div class="row"><span>PhilHealth</span><span>₱{{ number_format($record->philhealth_employee, 2) }}</span></div>
            <div class="row"><span>Pag-IBIG (HDMF)</span><span>₱{{ number_format($record->pagibig_employee, 2) }}</span></div>
            <div class="row"><span>Withholding Tax</span><span>₱{{ number_format($record->withholding_tax, 2) }}</span></div>
            <div class="row"><span>Tardiness / Undertime</span><span>₱{{ number_format(($record->late_deduction ?? 0) + ($record->undertime_deduction ?? 0), 2) }}</span></div>
            <div class="total-row"><span>TOTAL DEDUCTIONS</span><span>₱{{ number_format($record->total_deductions, 2) }}</span></div>
        </div>
    </div>

    <div class="net-banner">
        <span>NET TAKE-HOME PAY:</span>
        <span>₱{{ number_format($record->net_pay, 2) }}</span>
    </div>

    <div style="font-size: 9px; text-align: center; color: #64748b;">
        This document is confidential and generated electronically by Stellar One ERP.
    </div>
</body>
</html>

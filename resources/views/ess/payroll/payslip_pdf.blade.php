<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip - {{ $employee->first_name }} {{ $employee->last_name }} - {{ $record->payrollPeriod?->name }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1e293b;
            background: #f8fafc;
            margin: 0;
            padding: 2rem;
            font-size: 13px;
            line-height: 1.5;
        }
        .payslip-card {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            padding: 2.5rem;
            border-radius: 8px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 1.25rem;
            margin-bottom: 1.5rem;
        }
        .company-name {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
            text-transform: uppercase;
        }
        .company-subtitle {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .payslip-title {
            font-size: 14px;
            font-weight: 700;
            color: #2563eb;
            margin-top: 0.5rem;
            text-transform: uppercase;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            background: #f1f5f9;
            padding: 1rem 1.25rem;
            border-radius: 6px;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.35rem;
        }
        .info-label {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
        }
        .info-value {
            font-weight: 600;
            color: #0f172a;
        }
        .itemization-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8fafc;
            border-bottom: 2px solid #cbd5e1;
            padding: 0.5rem;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            color: #475569;
        }
        td {
            padding: 0.45rem 0.5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .amount {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }
        .subtotal-row {
            font-weight: 700;
            background: #f8fafc;
            border-top: 1px solid #cbd5e1;
        }
        .net-pay-box {
            background: #0f172a;
            color: #ffffff;
            padding: 1.25rem 1.5rem;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1.5rem;
        }
        .net-label {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .net-amount {
            font-size: 24px;
            font-weight: 800;
            color: #34d399;
        }
        .footer-note {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px dashed #cbd5e1;
            font-size: 10px;
            color: #94a3b8;
            text-align: center;
            line-height: 1.6;
        }
        .print-btn-bar {
            max-width: 800px;
            margin: 0 auto 1rem auto;
            display: flex;
            justify-content: flex-end;
            gap: 0.5rem;
        }
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            body { background: white; padding: 0; }
            .payslip-card { border: none; box-shadow: none; padding: 0; }
            .print-btn-bar { display: none; }
        }
    </style>
</head>
<body>
    <div class="print-btn-bar">
        <button class="btn" onclick="window.print()">Print / Save as PDF</button>
        <button class="btn" style="background: #64748b;" onclick="window.close()">Close Window</button>
    </div>

    <div class="payslip-card">
        <div class="header">
            <div class="company-name">{{ $employee->company->company_name ?? 'STELLAR ONE HOLDINGS INC.' }}</div>
            <div class="company-subtitle">Modular ERP &bull; Philippine Labor Compliance Division</div>
            <div class="payslip-title">Official Itemized Pay Slip (DOLE Advisory No. 26-20)</div>
        </div>

        <div class="info-grid">
            <div>
                <div class="info-item">
                    <span class="info-label">Employee Code</span>
                    <span class="info-value">{{ $employee->employee_code }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Employee Name</span>
                    <span class="info-value">{{ $employee->last_name }}, {{ $employee->first_name }} {{ $employee->middle_name }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Department</span>
                    <span class="info-value">{{ $employee->department->department_name ?? 'Operations' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Designation</span>
                    <span class="info-value">{{ $employee->position->position_title ?? 'Staff' }}</span>
                </div>
            </div>
            <div>
                <div class="info-item">
                    <span class="info-label">Pay Period</span>
                    <span class="info-value">{{ $record->payrollPeriod?->name ?? 'Regular Cutoff' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cutoff Duration</span>
                    <span class="info-value">
                        {{ $record->payrollPeriod?->start_date?->format('m/d/Y') }} – {{ $record->payrollPeriod?->end_date?->format('m/d/Y') }}
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payout Date</span>
                    <span class="info-value">{{ $record->payrollPeriod?->payout_date?->format('m/d/Y') ?? 'Immediate' }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Payment Method</span>
                    <span class="info-value">Direct Bank Credit</span>
                </div>
            </div>
        </div>

        <div class="itemization-grid">
            <!-- Earnings -->
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Earnings / Compensation</th>
                            <th class="amount">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Pay ({{ $record->total_work_days }} Days / {{ $record->total_hours }} Hrs)</td>
                            <td class="amount">{{ number_format($record->basic_pay, 2) }}</td>
                        </tr>
                        @if($record->overtime_pay > 0)
                        <tr>
                            <td>Overtime Pay ({{ $record->overtime_hours }} Hrs)</td>
                            <td class="amount">{{ number_format($record->overtime_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->night_diff_pay > 0)
                        <tr>
                            <td>Night Differential (+10% NSD)</td>
                            <td class="amount">{{ number_format($record->night_diff_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->holiday_pay > 0)
                        <tr>
                            <td>Holiday Pay (Regular/Special)</td>
                            <td class="amount">{{ number_format($record->holiday_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->rest_day_pay > 0)
                        <tr>
                            <td>Rest Day Premium Pay</td>
                            <td class="amount">{{ number_format($record->rest_day_pay, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->allowances > 0)
                        <tr>
                            <td>Allowances & De Minimis</td>
                            <td class="amount">{{ number_format($record->allowances, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->other_earnings > 0)
                        <tr>
                            <td>Other Incentives</td>
                            <td class="amount">{{ number_format($record->other_earnings, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="subtotal-row">
                            <td>GROSS EARNINGS</td>
                            <td class="amount">₱{{ number_format($record->gross_pay, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Deductions -->
            <div>
                <table>
                    <thead>
                        <tr>
                            <th>Deductions / Contributions</th>
                            <th class="amount">Amount (PHP)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>SSS Contribution (Employee)</td>
                            <td class="amount">{{ number_format($record->sss_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>PhilHealth Contribution (Employee)</td>
                            <td class="amount">{{ number_format($record->philhealth_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Pag-IBIG Fund (Employee)</td>
                            <td class="amount">{{ number_format($record->pagibig_employee, 2) }}</td>
                        </tr>
                        <tr>
                            <td>Withholding Tax (TRAIN Law)</td>
                            <td class="amount">{{ number_format($record->withholding_tax, 2) }}</td>
                        </tr>
                        @if($record->late_deduction > 0 || $record->undertime_deduction > 0)
                        <tr>
                            <td>Tardiness / Undertime</td>
                            <td class="amount">{{ number_format($record->late_deduction + $record->undertime_deduction, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->employee_loan > 0 || $record->salary_advance > 0)
                        <tr>
                            <td>Company Loan / Advance</td>
                            <td class="amount">{{ number_format($record->employee_loan + $record->salary_advance, 2) }}</td>
                        </tr>
                        @endif
                        @if($record->other_deductions > 0)
                        <tr>
                            <td>Other Authorized Deductions</td>
                            <td class="amount">{{ number_format($record->other_deductions, 2) }}</td>
                        </tr>
                        @endif
                        <tr class="subtotal-row">
                            <td>TOTAL DEDUCTIONS</td>
                            <td class="amount">₱{{ number_format($record->total_deductions, 2) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="net-pay-box">
            <div>
                <div class="net-label">Net Take-Home Pay</div>
                <div style="font-size: 11px; opacity: 0.8;">Credited directly to employee payroll account</div>
            </div>
            <div class="net-amount">
                ₱{{ number_format($record->net_pay, 2) }}
            </div>
        </div>

        <div class="footer-note">
            This payslip is generated electronically by Stellar One Modular ERP pursuant to DOLE Labor Advisory No. 26, Series of 2020. 
            No manual signature is required. For calculation disputes or inquiries, submit an official dispute through your Employee Self-Service (ESS) Portal within 5 working days from receipt.
        </div>
    </div>
</body>
</html>

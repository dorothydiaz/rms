@extends('layouts.app')

@section('title', 'My Payslips & Compensation')

@section('content')
<div class="ess-payroll-container">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e293b; margin: 0 0 4px 0;">My Payslips & Compensation (DOLE Compliant)</h1>
            <p style="color: #64748b; font-size: 14px; margin: 0;">Confidential wage register, itemized earnings, statutory withholdings, and accrued 13th month pay.</p>
        </div>
    </div>

    <!-- YTD Compensation Overview Cards -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <!-- YTD Gross -->
        <div class="card" style="padding: 18px; border-radius: 12px; border-left: 4px solid #2563eb;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">YTD GROSS EARNINGS</span>
            <div style="font-size: 24px; font-weight: 800; color: #1e293b; margin-top: 4px;">₱{{ number_format($ytdGross, 2) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Calendar Year {{ date('Y') }}</div>
        </div>

        <!-- YTD Net -->
        <div class="card" style="padding: 18px; border-radius: 12px; border-left: 4px solid #10b981;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">YTD TAKE HOME (NET)</span>
            <div style="font-size: 24px; font-weight: 800; color: #10b981; margin-top: 4px;">₱{{ number_format($ytdNet, 2) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Credited to Payroll Account</div>
        </div>

        <!-- YTD Statutory Deductions -->
        <div class="card" style="padding: 18px; border-radius: 12px; border-left: 4px solid #f59e0b;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">YTD STATUTORY (SSS/PH/HDMF)</span>
            <div style="font-size: 24px; font-weight: 800; color: #1e293b; margin-top: 4px;">₱{{ number_format($ytdSss + $ytdPhilhealth + $ytdPagibig, 2) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Employee Mandated Share</div>
        </div>

        <!-- DOLE 13th Month Pay Accrued -->
        <div class="card" style="padding: 18px; border-radius: 12px; border-left: 4px solid #8b5cf6;">
            <span style="font-size: 12px; font-weight: 600; color: #64748b;">ACCRUED 13TH MONTH (PD 851)</span>
            <div style="font-size: 24px; font-weight: 800; color: #8b5cf6; margin-top: 4px;">₱{{ number_format($accrued13thMonth, 2) }}</div>
            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Payable by December 24</div>
        </div>
    </div>

    <!-- Payslips List Card -->
    <div class="card" style="padding: 24px; border-radius: 14px;">
        <h3 style="font-size: 16px; font-weight: 700; margin: 0 0 16px 0; color: #1e293b;">
            <i class="ph ph-receipt"></i> Official Payslip Register
        </h3>
        <div style="overflow-x: auto;">
            <table class="data-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; text-align: left; color: #475569; font-size: 12px;">
                        <th style="padding: 10px;">Payroll Period</th>
                        <th style="padding: 10px;">Payment Date</th>
                        <th style="padding: 10px;">Basic Salary</th>
                        <th style="padding: 10px;">Overtime / Premiums</th>
                        <th style="padding: 10px;">Gross Pay</th>
                        <th style="padding: 10px;">Total Deductions</th>
                        <th style="padding: 10px;">Net Take Home</th>
                        <th style="padding: 10px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $rec)
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 10px; font-weight: 700; color: #1e293b;">
                                {{ $rec->payrollPeriod?->start_date?->format('M d') }} - {{ $rec->payrollPeriod?->end_date?->format('M d, Y') }}
                            </td>
                            <td style="padding: 12px 10px; color: #64748b;">
                                {{ $rec->payrollPeriod?->payout_date?->format('M d, Y') ?? 'End of Cutoff' }}
                            </td>
                            <td style="padding: 12px 10px; color: #334155;">₱{{ number_format($rec->basic_pay, 2) }}</td>
                            <td style="padding: 12px 10px; color: #2563eb; font-weight: 600;">₱{{ number_format($rec->overtime_pay, 2) }}</td>
                            <td style="padding: 12px 10px; font-weight: 600; color: #1e293b;">₱{{ number_format($rec->gross_pay, 2) }}</td>
                            <td style="padding: 12px 10px; color: #dc2626;">-₱{{ number_format($rec->total_deductions, 2) }}</td>
                            <td style="padding: 12px 10px; font-weight: 800; color: #166534; font-size: 14px;">
                                ₱{{ number_format($rec->net_pay, 2) }}
                            </td>
                            <td style="padding: 12px 10px;">
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ route('ess.payroll.payslip', $rec->id) }}" class="btn btn-sm btn-outline" title="Inspect Breakdown">
                                        <i class="ph ph-eye"></i> Details
                                    </a>
                                    <a href="{{ route('ess.payroll.payslip-pdf', $rec->id) }}" target="_blank" class="btn btn-sm btn-outline" title="Printable PDF">
                                        <i class="ph ph-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No finalized payslips generated yet for this account.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 16px;">
            {{ $records->links() }}
        </div>
    </div>
</div>
@endsection

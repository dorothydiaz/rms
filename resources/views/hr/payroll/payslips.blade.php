@extends('layouts.app')

@section('title', 'Employee Payslips - Philippine Payroll Engine')

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <form method="GET" action="{{ route('hr.payroll.payslips') }}" style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Cutoff Period:</label>
            <select name="payroll_period_id" class="hr-select" onchange="this.form.submit()">
                @foreach($periods as $p)
                    <option value="{{ $p->id }}" {{ $currentPeriod && $currentPeriod->id == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                @endforeach
            </select>
        </form>
    </x-slot:actions>
</x-hr-tabs>

<!-- Payslips Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(310px, 1fr)); gap: 18px; margin-bottom: 24px;">
    @forelse($payslips as $slip)
        <div class="hr-metric-card" style="flex-direction: column; align-items: stretch; gap: 14px;">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <div>
                    <strong style="font-size: 15px; color: #0f172a;">{{ $slip->employee?->full_name }}</strong>
                    <div style="font-size: 12px; color: #64748b;">{{ $slip->employee?->position?->name }} &bull; {{ $slip->employee?->branch?->name }}</div>
                </div>
                <span class="hr-badge hr-badge-purple" style="font-family: monospace;">{{ $slip->employee?->employee_id }}</span>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; background: rgba(255, 255, 255, 0.7); border: 1px solid rgba(168, 85, 247, 0.12); padding: 12px; border-radius: 10px; font-size: 12px;">
                <div>
                    <span style="color: #64748b; display: block;">Gross Pay:</span>
                    <strong style="color: #0f172a; font-size: 13px;">₱{{ number_format($slip->gross_pay, 2) }}</strong>
                </div>
                <div>
                    <span style="color: #64748b; display: block;">Deductions:</span>
                    <strong style="color: #ef4444; font-size: 13px;">-₱{{ number_format($slip->total_deductions, 2) }}</strong>
                </div>
                <div style="grid-column: 1 / -1; border-top: 1px dashed rgba(168, 85, 247, 0.25); padding-top: 8px; display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-weight: 700; color: #475569;">Take Home Net Pay:</span>
                    <strong style="color: #059669; font-size: 16px;">₱{{ number_format($slip->net_pay, 2) }}</strong>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <a href="{{ route('hr.payroll.payslips.show', $slip->id) }}" class="hr-btn hr-btn-primary hr-btn-sm" style="width: 100%; justify-content: center;">
                    <i class="ph ph-printer"></i> View & Print Payslip
                </a>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; color: #94a3b8; padding: 40px; background: rgba(255,255,255,0.7); border-radius: 16px;">
            <i class="ph ph-receipt" style="font-size: 32px; display: block; margin-bottom: 8px;"></i>
            No payslips generated for this period yet.
        </div>
    @endforelse
</div>

@if($payslips->hasPages())
    <div style="padding: 14px 20px;">
        {{ $payslips->links() }}
    </div>
@endif
@endsection

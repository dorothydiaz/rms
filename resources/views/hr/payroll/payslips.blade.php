@extends('layouts.app')

@section('title', 'Employee Payslips - Philippine Payroll Engine')

@push('styles')
<style>
.hr-payslips-toolbar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.hr-payslips-search-wrap {
    position: relative;
    width: 280px;
}

.hr-payslips-search-input {
    width: 100%;
    height: 36px;
    padding: 0 12px 0 34px;
    border-radius: 8px;
    border: 1px solid #cbd5e1;
    background: #ffffff;
    font-size: 12.5px;
    color: #1e293b;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
}

.hr-payslips-search-input:focus {
    border-color: #9333ea;
    box-shadow: 0 0 0 3px rgba(147, 51, 234, 0.12);
}

.hr-payslip-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03), 0 4px 12px rgba(15, 23, 42, 0.02);
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.hr-payslip-card:hover {
    transform: translateY(-2px);
    border-color: #c4b5fd;
    box-shadow: 0 12px 28px -6px rgba(124, 58, 237, 0.12), 0 3px 8px rgba(0, 0, 0, 0.04);
}

.hr-payslip-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 10px;
}

.hr-payslip-emp-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
}

.hr-payslip-avatar {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 700;
    flex-shrink: 0;
    color: #ffffff;
    background: linear-gradient(135deg, #7c3aed 0%, #a855f7 100%);
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.2);
    object-fit: cover;
}

.hr-payslip-id-tag {
    font-family: monospace;
    font-size: 10.5px;
    font-weight: 600;
    color: #7c3aed;
    background: #faf5ff;
    border: 1px solid #ede9fe;
    padding: 2.5px 7px;
    border-radius: 6px;
    letter-spacing: 0.2px;
    white-space: nowrap;
    flex-shrink: 0;
}

.hr-payslip-finance-box {
    background: #f8fafc;
    border: 1px solid #f1f5f9;
    border-radius: 10px;
    padding: 11px 13px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.hr-payslip-finance-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.hr-payslip-finance-label {
    font-size: 9.5px;
    font-weight: 650;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: #64748b;
}

.hr-payslip-finance-val {
    font-size: 13px;
    font-weight: 600;
    color: #1e293b;
    margin-top: 2px;
    font-variant-numeric: tabular-nums;
}

.hr-payslip-net-row {
    border-top: 1px dashed #e2e8f0;
    padding-top: 9px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.hr-payslip-net-label {
    font-size: 11.5px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    gap: 5px;
}

.hr-payslip-net-val {
    font-size: 15.5px;
    font-weight: 700;
    color: #059669;
    letter-spacing: -0.01em;
    font-variant-numeric: tabular-nums;
}

.hr-payslip-btn-action {
    width: 100%;
    height: 34px;
    background: #faf5ff;
    border: 1px solid #e9d5ff;
    color: #7c3aed;
    font-size: 12px;
    font-weight: 600;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    text-decoration: none;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    box-sizing: border-box;
}

.hr-payslip-btn-action:hover {
    background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);
    color: #ffffff;
    border-color: transparent;
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.28);
    transform: translateY(-1px);
}
</style>
@endpush

@section('content')
<x-hr-tabs parent="payroll">
    <x-slot:actions>
        <form method="GET" action="{{ route('hr.payroll.payslips') }}" style="display: flex; align-items: center; gap: 8px;">
            @if(request('search'))
                <input type="hidden" name="search" value="{{ request('search') }}">
            @endif
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

<!-- Filter & Search Toolbar -->
<div class="hr-payslips-toolbar">
    <form method="GET" action="{{ route('hr.payroll.payslips') }}" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        @if($currentPeriod)
            <input type="hidden" name="payroll_period_id" value="{{ $currentPeriod->id }}">
        @endif
        <div class="hr-payslips-search-wrap">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 11px; top: 50%; transform: translateY(-50%); font-size: 13.5px; color: #94a3b8;"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search employee name or ID..." class="hr-payslips-search-input">
        </div>
        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" style="height: 36px;">
            <i class="ph ph-magnifying-glass"></i> Filter
        </button>
        @if(request('search'))
            <a href="{{ route('hr.payroll.payslips', ['payroll_period_id' => $currentPeriod?->id]) }}" class="hr-btn hr-btn-secondary hr-btn-sm" style="height: 36px;" title="Clear search">
                <i class="ph ph-x"></i> Clear
            </a>
        @endif
    </form>
    <span style="font-size: 11.5px; font-weight: 600; color: #64748b; background: #ffffff; border: 1px solid #e2e8f0; padding: 0 14px; height: 36px; border-radius: 999px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.03); box-sizing: border-box;">
        <i class="ph ph-receipt" style="color: #7c3aed;"></i>
        <span>{{ $payslips->total() }} {{ Str::plural('Payslip', $payslips->total()) }}</span>
    </span>
</div>

<!-- Payslips Cards Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 18px; margin-bottom: 24px;">
    @forelse($payslips as $slip)
        @php
            $emp = $slip->employee;
            $initials = $emp?->initials ?? 'EM';
            $photoUrl = $emp?->photo_url;
        @endphp
        <div class="hr-payslip-card">
            <!-- Header: Avatar, Name, Role, & ID Badge -->
            <div class="hr-payslip-header">
                <div class="hr-payslip-emp-info">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" alt="{{ $emp?->full_name }}" class="hr-payslip-avatar">
                    @else
                        <div class="hr-payslip-avatar">{{ $initials }}</div>
                    @endif
                    <div style="min-width: 0; flex: 1;">
                        <strong style="font-size: 14.5px; color: #0f172a; display: block; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $emp?->full_name }}">
                            {{ $emp?->full_name }}
                        </strong>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="{{ $emp?->position?->name }} • {{ $emp?->branch?->name }}">
                            {{ $emp?->position?->name ?? 'Staff' }} &bull; {{ $emp?->branch?->name ?? 'Main' }}
                        </div>
                    </div>
                </div>
                <span class="hr-payslip-id-tag">{{ $emp?->employee_id }}</span>
            </div>

            <!-- Financial Summary Box -->
            <div class="hr-payslip-finance-box">
                <div class="hr-payslip-finance-row">
                    <div>
                        <span class="hr-payslip-finance-label">Gross Pay</span>
                        <div class="hr-payslip-finance-val">₱{{ number_format($slip->gross_pay, 2) }}</div>
                    </div>
                    <div style="text-align: right;">
                        <span class="hr-payslip-finance-label">Deductions</span>
                        <div class="hr-payslip-finance-val" style="color: #ef4444;">-₱{{ number_format($slip->total_deductions, 2) }}</div>
                    </div>
                </div>
                <div class="hr-payslip-net-row">
                    <span class="hr-payslip-net-label">
                        <i class="ph ph-wallet" style="color: #059669; font-size: 14px;"></i>
                        <span>Take Home Net Pay:</span>
                    </span>
                    <strong class="hr-payslip-net-val">₱{{ number_format($slip->net_pay, 2) }}</strong>
                </div>
            </div>

            <!-- Card Action Footer -->
            <div>
                <a href="{{ route('hr.payroll.payslips.show', $slip->id) }}" class="hr-payslip-btn-action">
                    <i class="ph ph-printer"></i>
                    <span>View &amp; Print Payslip</span>
                </a>
            </div>
        </div>
    @empty
        <div style="grid-column: 1 / -1; text-align: center; color: #64748b; padding: 48px 20px; background: #ffffff; border: 1px dashed #cbd5e1; border-radius: 16px;">
            <div style="width: 48px; height: 48px; border-radius: 12px; background: #faf5ff; color: #7c3aed; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 12px;">
                <i class="ph ph-receipt"></i>
            </div>
            <div style="font-weight: 700; color: #1e293b; font-size: 15px; margin-bottom: 4px;">No Payslips Found</div>
            <p style="font-size: 12.5px; color: #64748b; margin: 0; max-width: 420px; margin: 0 auto;">
                @if(request('search'))
                    No payslips match your search term "{{ request('search') }}". Try checking the spelling or clearing your filter.
                @else
                    No payslips have been generated for this cutoff period yet. You can process payroll in the Periods &amp; Process tab.
                @endif
            </p>
        </div>
    @endforelse
</div>

@if($payslips->hasPages())
    <div style="padding: 14px 20px;">
        {{ $payslips->links() }}
    </div>
@endif
@endsection

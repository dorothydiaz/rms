@extends('layouts.app')

@section('title', 'Process Payroll - Philippine Payroll Engine')

@section('content')
<x-hr-tabs parent="payroll" />

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: start;">
    <!-- Payroll Execution Card -->
    <div class="hr-table-card">
        <div class="hr-table-header">
            <span class="hr-table-title"><i class="ph ph-gear" style="color: #9333ea;"></i> Payroll Engine Trigger</span>
        </div>
        <form method="POST" action="{{ route('hr.payroll.process.run') }}" style="padding: 24px;">
            @csrf
            <div class="hr-form-group" style="margin-bottom: 16px;">
                <label class="hr-form-label">Select Payroll Period *</label>
                <select name="payroll_period_id" class="hr-select" required onchange="window.location.href='{{ route('hr.payroll.process') }}?payroll_period_id=' + this.value">
                    @foreach($periods as $p)
                        <option value="{{ $p->id }}" {{ $period && $period->id == $p->id ? 'selected' : '' }}>
                            {{ $p->name }} ({{ \Carbon\Carbon::parse($p->start_date)->format('M d') }} - {{ \Carbon\Carbon::parse($p->end_date)->format('M d, Y') }}) [{{ $p->status }}]
                        </option>
                    @endforeach
                </select>
            </div>

            @if(Auth::user()->isSuperAdmin() || Auth::user()->isHrAdmin())
                <div class="hr-form-group" style="margin-bottom: 20px;">
                    <label class="hr-form-label">Branch Scope (Optional)</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">All Branches</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div style="background: rgba(147, 51, 234, 0.06); border: 1px solid rgba(147, 51, 234, 0.2); border-radius: 12px; padding: 16px; margin-bottom: 24px; font-size: 13px; line-height: 1.6; color: #475569;">
                <strong style="color: #6b21a8; display: block; margin-bottom: 6px;"><i class="ph ph-shield-check"></i> Automated Philippine Payroll Rules:</strong>
                &bull; Computes basic salary and daily rate based on 313/26 days restaurant divisor.<br>
                &bull; Computes 125% regular overtime and 10% night shift differential (10 PM – 6 AM).<br>
                &bull; Applies latest 2024-2026 SSS contribution schedule dynamically from database.<br>
                &bull; Computes PhilHealth 5% premium sharing (2.5% Employee / 2.5% Employer).<br>
                &bull; Applies Pag-IBIG maximum mandatory contribution.<br>
                &bull; Calculates BIR graduated tax withholding brackets under the TRAIN law.
            </div>

            @if($period && $period->status === 'Finalized')
                <div class="login-alert danger" style="margin-bottom: 0;">
                    <i class="ph ph-lock-key"></i>
                    <span>This payroll period is finalized and permanently locked. No recalculation permitted.</span>
                </div>
            @else
                <button type="submit" class="hr-btn hr-btn-primary" style="width: 100%; justify-content: center; padding: 12px; font-size: 14px;">
                    <i class="ph ph-lightning"></i>
                    <span>Execute Payroll Calculation</span>
                </button>
            @endif
        </form>
    </div>

    <!-- Period Status Card -->
    <div class="hr-table-card">
        <div class="hr-table-header">
            <span class="hr-table-title"><i class="ph ph-info" style="color: #0284c7;"></i> Period Status & Summary</span>
            @if($period)
                <span class="hr-badge {{ $period->status === 'Finalized' ? 'hr-badge-success' : 'hr-badge-warning' }}">
                    {{ $period->status }}
                </span>
            @endif
        </div>
        <div style="padding: 24px;">
            @if($period)
                <div style="display: flex; flex-direction: column; gap: 14px; font-size: 13px;">
                    <div>
                        <span style="color: #64748b;">Period Name:</span>
                        <strong style="display: block; font-size: 15px; color: #0f172a;">{{ $period->name }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Cutoff Dates:</span>
                        <strong>{{ \Carbon\Carbon::parse($period->start_date)->format('M d, Y') }} &mdash; {{ \Carbon\Carbon::parse($period->end_date)->format('M d, Y') }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Payout Date:</span>
                        <strong style="color: #059669;">{{ \Carbon\Carbon::parse($period->payout_date)->format('M d, Y') }}</strong>
                    </div>
                    <div>
                        <span style="color: #64748b;">Processed Employees:</span>
                        <strong style="color: #9333ea; font-size: 16px;">{{ $period->records->count() }} records</strong>
                    </div>
                    @if($period->records->count() > 0)
                        <div style="margin-top: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                            <a href="{{ route('hr.payroll.register', ['payroll_period_id' => $period->id]) }}" class="hr-btn hr-btn-secondary" style="width: 100%; justify-content: center;">
                                <i class="ph ph-eye"></i> View Calculated Register
                            </a>
                        </div>
                    @endif
                </div>
            @else
                <div style="color: #94a3b8; text-align: center; padding: 30px;">
                    Please select or create a payroll period to begin.
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

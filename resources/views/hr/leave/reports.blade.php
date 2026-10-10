@extends('layouts.app')

@section('title', 'Leave Utilization Reports - Leave & Absence Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-chart-bar"></i>
            Leave Utilization & Absenteeism Analytics
        </h1>
        <p class="hr-page-subtitle">Examine workforce absence rates, leave utilization trends, and credit balances</p>
    </div>
    <div class="hr-page-actions">
        <form method="GET" action="{{ route('hr.leave.reports') }}" style="display: flex; align-items: center; gap: 8px;">
            <label style="font-size: 12px; font-weight: 600; color: #475569;">Year:</label>
            <select name="year" class="hr-select" onchange="this.form.submit()">
                @for($y = date('Y') - 1; $y <= date('Y') + 1; $y++)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </form>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-header">
        <span class="hr-table-title"><i class="ph ph-chart-donut"></i> Annual Leave Utilization Rate ({{ $year }})</span>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Leave Type</th>
                    <th>Total Credits Allocated</th>
                    <th>Total Days Used</th>
                    <th>Total Days Remaining</th>
                    <th>Utilization Rate (%)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($utilizationData as $u)
                    <tr>
                        <td><strong>{{ $u['name'] }}</strong></td>
                        <td>{{ number_format($u['allocated'], 1) }} days</td>
                        <td style="color: #ef4444; font-weight: 600;">{{ number_format($u['used'], 1) }} days</td>
                        <td style="color: #059669; font-weight: 600;">{{ number_format($u['remaining'], 1) }} days</td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="flex: 1; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden; max-width: 140px;">
                                    <div style="background: linear-gradient(90deg, #a855f7, #6366f1); height: 100%; width: {{ min(100, $u['utilization_rate']) }}%;"></div>
                                </div>
                                <span style="font-size: 12px; font-weight: 700; color: #475569;">{{ $u['utilization_rate'] }}%</span>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

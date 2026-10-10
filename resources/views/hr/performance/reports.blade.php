@extends('layouts.app')

@section('title', 'Performance Reports - HR Operations')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-chart-polar"></i>
            Restaurant Performance Leaderboard & Reports
        </h1>
        <p class="hr-page-subtitle">Rankings, scoring history, and department competency distribution</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-secondary" onclick="window.print()">
            <i class="ph ph-printer"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Staff Name</th>
                    <th>Branch</th>
                    <th>Department</th>
                    <th>Review Cycle</th>
                    <th>Evaluator</th>
                    <th>Score</th>
                    <th>Rating Tier</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluations as $index => $eval)
                    <tr>
                        <td>
                            @if($index == 0)
                                <span class="hr-badge hr-badge-warning" style="font-weight: 700;">#1 🥇</span>
                            @elseif($index == 1)
                                <span class="hr-badge hr-badge-blue" style="font-weight: 700;">#2 🥈</span>
                            @elseif($index == 2)
                                <span class="hr-badge hr-badge-purple" style="font-weight: 700;">#3 🥉</span>
                            @else
                                <span style="color: #94a3b8; font-weight: 600; margin-left: 8px;">#{{ $index + 1 }}</span>
                            @endif
                        </td>
                        <td>
                            <strong>{{ $eval->employee->full_name ?? 'N/A' }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $eval->employee->employee_number ?? '' }}</div>
                        </td>
                        <td>{{ $eval->employee->branch->name ?? 'N/A' }}</td>
                        <td>{{ $eval->employee->department->name ?? 'N/A' }}</td>
                        <td>{{ $eval->period->name ?? 'N/A' }}</td>
                        <td>{{ $eval->evaluator->name ?? 'N/A' }}</td>
                        <td>
                            <strong style="color: {{ $eval->overall_score >= 4.0 ? '#10b981' : ($eval->overall_score >= 3.0 ? '#f59e0b' : '#ef4444') }}; font-size: 15px;">
                                {{ number_format($eval->overall_score, 2) }}
                            </strong>
                        </td>
                        <td>
                            @if($eval->overall_score >= 4.5)
                                <span class="hr-badge hr-badge-success">Top Performer</span>
                            @elseif($eval->overall_score >= 3.5)
                                <span class="hr-badge hr-badge-blue">Good Standing</span>
                            @elseif($eval->overall_score >= 2.5)
                                <span class="hr-badge hr-badge-warning">Needs Coaching</span>
                            @else
                                <span class="hr-badge hr-badge-danger">Underperforming</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No evaluations available for reporting yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

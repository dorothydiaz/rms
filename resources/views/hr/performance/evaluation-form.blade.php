@extends('layouts.app')

@section('title', 'Performance Evaluation Detail')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-clipboard-text"></i>
            Evaluation: {{ $evaluation->employee->full_name ?? 'N/A' }}
        </h1>
        <p class="hr-page-subtitle">
            Cycle: <strong>{{ $evaluation->period->name ?? 'N/A' }}</strong> &bull;
            Evaluator: <strong>{{ $evaluation->evaluator->name ?? 'Management' }}</strong> &bull;
            Status: <span class="hr-badge hr-badge-blue">{{ $evaluation->status }}</span>
        </p>
    </div>
    <div class="hr-page-actions">
        <a href="{{ route('hr.performance.evaluations') }}" class="hr-btn hr-btn-secondary">
            <i class="ph ph-arrow-left"></i>
            <span>Back to Evaluations</span>
        </a>
    </div>
</div>

<div class="hr-detail-grid">
    <!-- Left Column: Employee & Summary -->
    <div>
        <div class="hr-card" style="margin-bottom: 24px;">
            <h3 style="font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 16px;">
                <i class="ph ph-user"></i> Employee Information
            </h3>
            <div style="display: flex; flex-direction: column; gap: 12px; font-size: 13px;">
                <div>
                    <span style="color: #94a3b8;">Full Name:</span>
                    <strong style="color: #f1f5f9; margin-left: 8px;">{{ $evaluation->employee->full_name }}</strong>
                </div>
                <div>
                    <span style="color: #94a3b8;">Employee ID:</span>
                    <span style="color: #f1f5f9; margin-left: 8px;">{{ $evaluation->employee->employee_number }}</span>
                </div>
                <div>
                    <span style="color: #94a3b8;">Department:</span>
                    <span style="color: #f1f5f9; margin-left: 8px;">{{ $evaluation->employee->department->name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span style="color: #94a3b8;">Position:</span>
                    <span style="color: #f1f5f9; margin-left: 8px;">{{ $evaluation->employee->position->name ?? 'N/A' }}</span>
                </div>
                <div>
                    <span style="color: #94a3b8;">Employment Status:</span>
                    <span class="hr-badge hr-badge-success" style="margin-left: 8px;">{{ $evaluation->employee->employment_status }}</span>
                </div>
            </div>
        </div>

        <div class="hr-card">
            <h3 style="font-size: 16px; font-weight: 600; color: #fff; margin-bottom: 16px;">
                <i class="ph ph-gauge"></i> Score Overview
            </h3>
            <div style="text-align: center; padding: 20px 0;">
                <div style="font-size: 42px; font-weight: 800; color: {{ $evaluation->overall_score >= 4.0 ? '#10b981' : ($evaluation->overall_score >= 3.0 ? '#f59e0b' : '#ef4444') }};">
                    {{ number_format($evaluation->overall_score, 2) }}
                </div>
                <div style="color: #94a3b8; font-size: 13px; margin-top: 4px;">Out of 5.00 Maximum</div>
            </div>
            <div style="margin-top: 16px; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 16px; font-size: 13px;">
                <div style="margin-bottom: 8px;"><strong>Recommendation:</strong></div>
                <p style="color: #cbd5e1; background: rgba(15, 23, 42, 0.5); padding: 10px; border-radius: 6px;">
                    {{ $evaluation->recommendation ?: 'No specific recommendation provided.' }}
                </p>
                <div style="margin-top: 12px; margin-bottom: 8px;"><strong>Manager Comments:</strong></div>
                <p style="color: #cbd5e1; background: rgba(15, 23, 42, 0.5); padding: 10px; border-radius: 6px;">
                    {{ $evaluation->manager_comments ?: 'No comments recorded.' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Right Column: Criteria Ratings Breakdown -->
    <div>
        <div class="hr-table-card">
            <div style="padding: 16px 20px; border-bottom: 1px solid rgba(255, 255, 255, 0.08);">
                <h3 style="font-size: 16px; font-weight: 600; color: #fff; margin: 0;">
                    <i class="ph ph-list-numbers"></i> Competency Criteria Ratings
                </h3>
            </div>
            <div class="hr-table-wrapper">
                <table class="hr-table">
                    <thead>
                        <tr>
                            <th>Criterion</th>
                            <th>Description</th>
                            <th style="text-align: right;">Rating (1-5)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $ratingMap = $evaluation->ratings->keyBy('criterion_id');
                        @endphp
                        @forelse($criteria as $crit)
                            @php
                                $score = $ratingMap->get($crit->id)->rating ?? 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $crit->name }}</strong></td>
                                <td style="color: #94a3b8; font-size: 12px;">{{ $crit->description }}</td>
                                <td style="text-align: right;">
                                    <span class="hr-badge {{ $score >= 4 ? 'hr-badge-success' : ($score >= 3 ? 'hr-badge-blue' : 'hr-badge-danger') }}" style="font-size: 13px; font-weight: 700;">
                                        {{ $score > 0 ? $score . ' / 5' : 'Unrated' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; color: #94a3b8; padding: 24px;">No criteria defined.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

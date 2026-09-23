@extends('layouts.app')

@section('title', 'Training Reports - HR Operations')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-chart-donut"></i>
            Restaurant Training Analytics & Completion
        </h1>
        <p class="hr-page-subtitle">Summary of training completion rates, staff development budgets, and certification compliance</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-secondary" onclick="window.print()">
            <i class="ph ph-printer"></i>
            <span>Print Report</span>
        </button>
    </div>
</div>

<div class="hr-metrics-grid">
    <div class="hr-metric-card">
        <div class="hr-metric-icon" style="background: rgba(59, 130, 246, 0.15); color: #3b82f6;">
            <i class="ph ph-books"></i>
        </div>
        <div>
            <div class="hr-metric-value">{{ $programs->count() }}</div>
            <div class="hr-metric-label">Total Programs</div>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-icon" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">
            <i class="ph ph-users-three"></i>
        </div>
        <div>
            <div class="hr-metric-value">{{ $programs->sum('enrollments_count') }}</div>
            <div class="hr-metric-label">Total Enrollments</div>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-icon" style="background: rgba(245, 158, 11, 0.15); color: #f59e0b;">
            <i class="ph ph-clock"></i>
        </div>
        <div>
            <div class="hr-metric-value">{{ $programs->sum('duration_hours') }} hrs</div>
            <div class="hr-metric-label">Training Hours Delivered</div>
        </div>
    </div>
    <div class="hr-metric-card">
        <div class="hr-metric-icon" style="background: rgba(168, 85, 247, 0.15); color: #a855f7;">
            <i class="ph ph-currency-circle-dollar"></i>
        </div>
        <div>
            <div class="hr-metric-value">₱{{ number_format($programs->sum('cost'), 2) }}</div>
            <div class="hr-metric-label">Total Investment</div>
        </div>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Program Title</th>
                    <th>Type</th>
                    <th>Trainer</th>
                    <th>Cost</th>
                    <th>Total Enrolled</th>
                    <th>Completed</th>
                    <th>Pass Rate</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $prog)
                    @php
                        $completedCount = $prog->enrollments->where('completion_status', 'Completed')->count();
                        $rate = $prog->enrollments_count > 0 ? round(($completedCount / $prog->enrollments_count) * 100) : 0;
                    @endphp
                    <tr>
                        <td><strong>{{ $prog->name }}</strong></td>
                        <td><span class="hr-badge hr-badge-blue">{{ $prog->training_type }}</span></td>
                        <td>{{ $prog->trainer ?: 'In-house' }}</td>
                        <td>₱{{ number_format($prog->cost, 2) }}</td>
                        <td><span class="hr-badge hr-badge-purple">{{ $prog->enrollments_count }}</span></td>
                        <td><span class="hr-badge hr-badge-success">{{ $completedCount }}</span></td>
                        <td>
                            <strong style="color: {{ $rate >= 80 ? '#10b981' : ($rate >= 50 ? '#f59e0b' : '#ef4444') }};">
                                {{ $rate }}%
                            </strong>
                        </td>
                        <td>
                            <span class="hr-badge {{ $prog->status === 'Completed' ? 'hr-badge-success' : 'hr-badge-neutral' }}">
                                {{ $prog->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No training program reports recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

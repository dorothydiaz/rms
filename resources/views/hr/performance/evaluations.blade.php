@extends('layouts.app')

@section('title', 'Staff Evaluations - Performance Management')

@section('content')
<x-hr-tabs parent="performance-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('newEvaluationModal')">
            <i class="ph ph-plus-circle"></i>
            <span>New Evaluation</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.performance.evaluations') }}" style="display: flex; gap: 12px; width: 100%; align-items: center; flex-wrap: wrap;">
        <select name="performance_period_id" class="hr-select" style="max-width: 250px;">
            <option value="">-- All Review Cycles --</option>
            @foreach($periods as $per)
                <option value="{{ $per->id }}" {{ request('performance_period_id') == $per->id ? 'selected' : '' }}>{{ $per->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i> Filter
        </button>
        @if(request()->hasAny(['performance_period_id']))
            <a href="{{ route('hr.performance.evaluations') }}" class="hr-btn hr-btn-secondary">Reset</a>
        @endif
    </form>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Branch / Role</th>
                    <th>Review Cycle</th>
                    <th>Evaluator</th>
                    <th>Overall Score</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($evaluations as $eval)
                    <tr>
                        <td>
                            <strong>{{ $eval->employee->full_name ?? 'N/A' }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $eval->employee->employee_number ?? '' }}</div>
                        </td>
                        <td>
                            <div>{{ $eval->employee->branch->name ?? 'N/A' }}</div>
                            <span style="font-size: 11px; color: #94a3b8;">{{ $eval->employee->position->name ?? 'N/A' }}</span>
                        </td>
                        <td>{{ $eval->period->name ?? 'N/A' }}</td>
                        <td>{{ $eval->evaluator->name ?? 'N/A' }}</td>
                        <td>
                            <strong style="color: {{ $eval->overall_score >= 4.0 ? '#10b981' : ($eval->overall_score >= 3.0 ? '#f59e0b' : '#ef4444') }}; font-size: 15px;">
                                {{ number_format($eval->overall_score, 2) }} / 5.00
                            </strong>
                        </td>
                        <td>
                            <span class="hr-badge {{ $eval->status === 'Approved' ? 'hr-badge-success' : ($eval->status === 'Submitted' ? 'hr-badge-blue' : 'hr-badge-neutral') }}">
                                {{ $eval->status }}
                            </span>
                        </td>
                        <td>{{ $eval->created_at->format('M d, Y') }}</td>
                        <td style="text-align: right;">
                            <a href="{{ route('hr.performance.evaluations.create', $eval->id) }}" class="hr-btn hr-btn-secondary" style="padding: 4px 10px; font-size: 12px;">
                                <i class="ph ph-eye"></i> View / Edit
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No performance evaluations found. Click <strong>New Evaluation</strong> to grade an employee.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($evaluations->hasPages())
        <div style="padding: 16px;">
            {{ $evaluations->links() }}
        </div>
    @endif
</div>

<!-- Modal: New Evaluation Form -->
<div id="newEvaluationModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 700px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-star"></i> Conduct Performance Evaluation</span>
            <button class="icon-btn" onclick="closeModal('newEvaluationModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.performance.evaluations.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Review Cycle *</label>
                        <select name="performance_period_id" class="hr-select" required>
                            @foreach($periods as $per)
                                <option value="{{ $per->id }}">{{ $per->name }} ({{ $per->status }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employee to Evaluate *</label>
                        <select name="employee_id" class="hr-select" required>
                            <option value="">-- Choose Employee --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->position->name ?? 'Staff' }} - {{ $emp->branch->name ?? '' }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-top: 16px; margin-bottom: 8px; font-weight: 700; color: #1e293b; font-size: 13.5px;">
                    Restaurant Competencies & Standards Rating (1 = Poor, 5 = Excellent):
                </div>

                <div style="background: rgba(255, 255, 255, 0.65); border: 1px solid rgba(168, 85, 247, 0.22); border-radius: 12px; padding: 14px; max-height: 280px; overflow-y: auto; box-shadow: inset 0 1px 3px rgba(0,0,0,0.02);">
                    @foreach($criteria as $crit)
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid rgba(168, 85, 247, 0.1);">
                            <div>
                                <strong style="font-size: 13px; color: #0f172a;">{{ $crit->name }}</strong>
                                <div style="font-size: 11px; color: #64748b;">{{ $crit->description }}</div>
                            </div>
                            <div style="display: flex; gap: 8px; align-items: center;">
                                <select name="ratings[{{ $crit->id }}]" class="hr-select" style="width: 80px;" required>
                                    <option value="5">5 - Excellent</option>
                                    <option value="4" selected>4 - Very Good</option>
                                    <option value="3">3 - Satisfactory</option>
                                    <option value="2">2 - Needs Imp.</option>
                                    <option value="1">1 - Unsatisfactory</option>
                                </select>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hr-form-group" style="margin-top: 16px;">
                    <label class="hr-form-label">Manager / Evaluator Comments</label>
                    <textarea name="manager_comments" class="hr-textarea" rows="2" placeholder="Describe key strengths, speed, cleanliness, customer feedback, and areas of growth..."></textarea>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Recommendation (e.g. Regularization, Promotion, Refresher Training)</label>
                    <input type="text" name="recommendation" class="hr-input" placeholder="e.g. Recommend for regularization after probationary milestone">
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('newEvaluationModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Submit Evaluation</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
@endpush
@endsection

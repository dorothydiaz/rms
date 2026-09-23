@extends('layouts.app')

@section('title', 'Training Records - HR Operations')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-certificate"></i>
            Employee Training Records & Enrollments
        </h1>
        <p class="hr-page-subtitle">Track staff certifications, completion statuses, scores, and retraining requirements</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('enrollModal')">
            <i class="ph ph-user-plus"></i>
            <span>Enroll Employee</span>
        </button>
    </div>
</div>

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.training.records') }}" style="display: flex; gap: 12px; width: 100%; align-items: center; flex-wrap: wrap;">
        <select name="training_program_id" class="hr-select" style="max-width: 250px;">
            <option value="">-- All Training Programs --</option>
            @foreach($programs as $prog)
                <option value="{{ $prog->id }}" {{ request('training_program_id') == $prog->id ? 'selected' : '' }}>{{ $prog->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i> Filter
        </button>
        @if(request('training_program_id'))
            <a href="{{ route('hr.training.records') }}" class="hr-btn hr-btn-secondary">Reset</a>
        @endif
    </form>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Branch</th>
                    <th>Training Program</th>
                    <th>Enrollment Date</th>
                    <th>Status</th>
                    <th>Exam Score</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $rec)
                    <tr>
                        <td>
                            <strong>{{ $rec->employee->full_name ?? 'N/A' }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $rec->employee->employee_number ?? '' }}</div>
                        </td>
                        <td>{{ $rec->employee->branch->name ?? 'N/A' }}</td>
                        <td>
                            <strong>{{ $rec->program->name ?? 'N/A' }}</strong>
                            <div style="font-size: 11px; color: #94a3b8;">{{ $rec->program->training_type ?? '' }}</div>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($rec->enrollment_date)->format('M d, Y') }}</td>
                        <td>
                            <span class="hr-badge {{ $rec->completion_status === 'Completed' ? 'hr-badge-success' : ($rec->completion_status === 'Failed' ? 'hr-badge-danger' : ($rec->completion_status === 'In Progress' ? 'hr-badge-blue' : 'hr-badge-warning')) }}">
                                {{ $rec->completion_status }}
                            </span>
                        </td>
                        <td>
                            @if($rec->score !== null)
                                <strong style="color: {{ $rec->score >= 75 ? '#10b981' : '#ef4444' }};">
                                    {{ $rec->score }}%
                                </strong>
                            @else
                                <span style="color: #94a3b8;">—</span>
                            @endif
                        </td>
                        <td style="color: #cbd5e1; font-size: 12px;">{{ $rec->remarks ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No training records found. Click <strong>Enroll Employee</strong> to register an employee for a course.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div style="padding: 16px;">
            {{ $records->links() }}
        </div>
    @endif
</div>

<!-- Modal: Enroll Employee -->
<div id="enrollModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-certificate"></i> Enroll Employee in Training</span>
            <button class="icon-btn" onclick="closeModal('enrollModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.training.enrollments.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Training Program *</label>
                    <select name="training_program_id" class="hr-select" required>
                        @foreach($programs as $prog)
                            <option value="{{ $prog->id }}">{{ $prog->name }} ({{ $prog->training_type }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Employee *</label>
                    <select name="employee_id" class="hr-select" required>
                        <option value="">-- Choose Employee --</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}">{{ $emp->full_name }} ({{ $emp->branch->name ?? 'Branch' }} - {{ $emp->position->name ?? 'Staff' }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Enrollment Date *</label>
                        <input type="date" name="enrollment_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Status *</label>
                        <select name="completion_status" class="hr-select" required>
                            <option value="Assigned">Assigned</option>
                            <option value="Scheduled">Scheduled</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Failed">Failed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Evaluation / Exam Score (%)</label>
                    <input type="number" step="0.1" name="score" class="hr-input" placeholder="e.g. 92.5" min="0" max="100">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Remarks / Notes</label>
                    <textarea name="remarks" class="hr-textarea" rows="2" placeholder="e.g. Passed practical sanitation exam on first take"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('enrollModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Enrollment</button>
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

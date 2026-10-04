@extends('layouts.app')

@section('title', 'Training Programs - Restaurant Development')

@section('content')
<x-hr-tabs parent="learning-development">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addProgramModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Create Training</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Filters -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.training.programs') }}" class="hr-filter-form" style="display: flex; gap: 8px; width: 100%; align-items: center; flex-wrap: nowrap;">
        <select name="status" class="hr-select" style="max-width: 200px;">
            <option value="">-- All Statuses --</option>
            <option value="Scheduled" {{ request('status') === 'Scheduled' ? 'selected' : '' }}>Scheduled</option>
            <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
            <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
            <option value="Cancelled" {{ request('status') === 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i> Filter
        </button>
        @if(request('status'))
            <a href="{{ route('hr.training.programs') }}" class="hr-btn hr-btn-secondary">Reset</a>
        @endif
    </form>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Program Name</th>
                    <th>Type / Trainer</th>
                    <th>Schedule Date</th>
                    <th>Duration</th>
                    <th>Cost</th>
                    <th>Attendees</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($programs as $prog)
                    <tr>
                        <td>
                            <strong>{{ $prog->name }}</strong>
                            <div style="font-size: 11px; color: #94a3b8; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $prog->description ?: 'No syllabus provided' }}
                            </div>
                        </td>
                        <td>
                            <div><span class="hr-badge hr-badge-blue">{{ $prog->training_type }}</span></div>
                            <span style="font-size: 11px; color: #94a3b8;">{{ $prog->trainer ?: 'In-house Lead' }}</span>
                        </td>
                        <td>
                            {{ \Carbon\Carbon::parse($prog->start_date)->format('M d, Y') }}
                            @if($prog->end_date && $prog->end_date !== $prog->start_date)
                                - {{ \Carbon\Carbon::parse($prog->end_date)->format('M d, Y') }}
                            @endif
                        </td>
                        <td>{{ $prog->duration_hours }} hrs</td>
                        <td>₱{{ number_format($prog->cost, 2) }}</td>
                        <td>
                            <span class="hr-badge hr-badge-purple">{{ $prog->enrollments_count }} enrolled</span>
                        </td>
                        <td>
                            <span class="hr-badge {{ $prog->status === 'Completed' ? 'hr-badge-success' : ($prog->status === 'In Progress' ? 'hr-badge-blue' : ($prog->status === 'Scheduled' ? 'hr-badge-warning' : 'hr-badge-neutral')) }}">
                                {{ $prog->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 35px;">
                            No training programs created yet. Click <strong>Create Training</strong> to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($programs->hasPages())
        <div style="padding: 16px;">
            {{ $programs->links() }}
        </div>
    @endif
</div>

<!-- Modal: Create Program -->
<div id="addProgramModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Create Training Program</span>
            <button class="icon-btn" onclick="closeModal('addProgramModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.training.programs.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Training Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Food Safety & HACCP Certification">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Description / Syllabus</label>
                    <textarea name="description" class="hr-textarea" rows="2" placeholder="Course outline, safety protocols covered..."></textarea>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Training Type *</label>
                        <select name="training_type" class="hr-select" required>
                            <option value="Internal">Internal (In-Branch)</option>
                            <option value="External">External Workshop</option>
                            <option value="Online">Online / Webinar</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Trainer / Instructor</label>
                        <input type="text" name="trainer" class="hr-input" placeholder="e.g. Chef Marco / Red Cross">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Start Date *</label>
                        <input type="date" name="start_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">End Date</label>
                        <input type="date" name="end_date" class="hr-input" value="{{ date('Y-m-d') }}">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Duration (Hours) *</label>
                        <input type="number" name="duration_hours" class="hr-input" required value="4" min="1">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Total Cost (PHP) *</label>
                        <input type="number" step="0.01" name="cost" class="hr-input" required value="0.00" min="0">
                    </div>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Location / Room</label>
                        <input type="text" name="location" class="hr-input" placeholder="e.g. Makati Branch Kitchen / Zoom">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Initial Status *</label>
                        <select name="status" class="hr-select" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="In Progress">In Progress</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addProgramModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Program</button>
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

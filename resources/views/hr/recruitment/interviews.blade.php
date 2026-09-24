@extends('layouts.app')

@section('title', 'Interviews Schedule - Recruitment')

@section('content')
<x-hr-tabs parent="talent-acquisition">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('scheduleInterviewModal')">
            <i class="ph ph-calendar-plus"></i>
            <span>Schedule Interview</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Candidate</th>
                    <th>Position</th>
                    <th>Interview Date & Time</th>
                    <th>Interviewer</th>
                    <th>Interview Stage</th>
                    <th>Rating</th>
                    <th>Recommendation</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interviews as $int)
                    <tr>
                        <td><strong>{{ $int->applicant?->full_name }}</strong></td>
                        <td>{{ $int->applicant?->applied_position }}</td>
                        <td>
                            <strong style="color: #9333ea;">{{ \Carbon\Carbon::parse($int->interview_date)->format('M d, Y') }}</strong><br>
                            <small style="color: #64748b;">{{ \Carbon\Carbon::parse($int->interview_date)->format('h:i A') }}</small>
                        </td>
                        <td>{{ $int->interviewer_name ?? $int->interviewer?->full_name ?? 'Not assigned' }}</td>
                        <td><span class="hr-badge hr-badge-neutral">{{ $int->interview_type }}</span></td>
                        <td>
                            @if($int->rating)
                                <span style="font-weight: 700; color: #f59e0b;">★ {{ $int->rating }}/5</span>
                            @else
                                <span style="color: #94a3b8;">Pending</span>
                            @endif
                        </td>
                        <td>{{ $int->recommendation ?? 'Awaiting notes' }}</td>
                        <td style="text-align: right;">
                            <span class="hr-badge {{ $int->status === 'Completed' ? 'hr-badge-success' : 'hr-badge-warning' }}">
                                {{ $int->status }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">No interviews scheduled.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($interviews->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0;">
            {{ $interviews->links() }}
        </div>
    @endif
</div>

<!-- Modal: Schedule Interview -->
<div id="scheduleInterviewModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus"></i> Schedule Interview / Trial</span>
            <button class="icon-btn" onclick="closeModal('scheduleInterviewModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.interviews.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Candidate *</label>
                    <select name="applicant_id" class="hr-select" required>
                        @foreach($applicants as $a)
                            <option value="{{ $a->id }}">{{ $a->full_name }} ({{ $a->applied_position }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Date & Time *</label>
                    <input type="datetime-local" name="interview_date" class="hr-input" required>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Stage / Type *</label>
                    <select name="interview_type" class="hr-select" required>
                        <option value="Initial Screening">Initial Screening</option>
                        <option value="Technical / Practical">Technical / Practical Cooking Demo</option>
                        <option value="Managerial">Managerial Interview</option>
                        <option value="Final HR">Final HR Discussion</option>
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Interviewer</label>
                    <select name="interviewer_id" class="hr-select">
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->full_name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Interview Notes & Questions</label>
                    <textarea name="notes" class="hr-input" rows="3" placeholder="Focus areas..."></textarea>
                </div>
                <input type="hidden" name="status" value="Scheduled">
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('scheduleInterviewModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Schedule Interview</button>
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

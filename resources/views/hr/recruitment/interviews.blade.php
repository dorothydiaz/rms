@extends('layouts.app')

@section('title', 'Interviews Schedule - Talent Acquisition')

@section('content')
<x-hr-tabs parent="talent-acquisition">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('scheduleInterviewModal')">
            <i class="ph ph-calendar-plus"></i>
            <span>Schedule Interview</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<style>
/* Condensed Layout Margins for Maximum Table Viewport */
.hr-parent-header {
    margin-bottom: 4px !important;
}
.hr-parent-header .hr-parent-title-row {
    margin-bottom: 2px !important;
}
.hr-parent-header .hr-tabs-wrapper {
    margin-top: 4px !important;
    margin-bottom: 6px !important;
}
.hr-emp-summary-grid {
    margin-top: 0 !important;
    margin-bottom: 8px !important;
    gap: 8px !important;
}
.hr-filter-bar {
    margin-bottom: 8px !important;
    padding: 8px 12px !important;
}
</style>

<!-- 6 Interview Statistics Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-bottom: 8px;">
    <!-- Scheduled -->
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-calendar-blank"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Scheduled</span>
            <div class="hr-stat-value">{{ $interviewStats['scheduled'] }}</div>
            <span class="hr-stat-sub">Upcoming</span>
        </div>
    </div>

    <!-- Confirmed -->
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-check-square"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Confirmed</span>
            <div class="hr-stat-value">{{ $interviewStats['confirmed'] }}</div>
            <span class="hr-stat-sub">Candidate Ready</span>
        </div>
    </div>

    <!-- Completed -->
    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-seal-check"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Completed</span>
            <div class="hr-stat-value">{{ $interviewStats['completed'] }}</div>
            <span class="hr-stat-sub">Evaluated</span>
        </div>
    </div>

    <!-- Rescheduled -->
    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-arrow-counter-clockwise"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Rescheduled</span>
            <div class="hr-stat-value">{{ $interviewStats['rescheduled'] }}</div>
            <span class="hr-stat-sub">Moved Dates</span>
        </div>
    </div>

    <!-- Cancelled -->
    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-prohibit"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Cancelled</span>
            <div class="hr-stat-value">{{ $interviewStats['cancelled'] }}</div>
            <span class="hr-stat-sub">Withdrawn</span>
        </div>
    </div>

    <!-- No Show -->
    <div class="hr-stat-card hr-stat-card-neutral">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-user-minus"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">No Show</span>
            <div class="hr-stat-value">{{ $interviewStats['no_show'] }}</div>
            <span class="hr-stat-sub">Unattended</span>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="hr-filter-bar" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 6px 12px; margin-bottom: 8px; box-shadow: 0 1px 2px rgba(0,0,0,0.02);">
    <form method="GET" action="{{ route('hr.recruitment.interviews') }}" class="hr-filter-form" style="display: flex; gap: 8px; align-items: center; flex-wrap: nowrap; width: 100%;">
        <!-- Search Input -->
        <div style="flex: 1; min-width: 180px; position: relative;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 9px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 14px;"></i>
            <input type="text" name="search" class="hr-input" placeholder="Search candidate, role, venue..." value="{{ request('search') }}" style="padding-left: 28px; width: 100%;">
        </div>

        <!-- Status Filter -->
        <select name="status" class="hr-select" style="min-width: 130px;">
            <option value="">All Statuses</option>
            @foreach(['Scheduled', 'Confirmed', 'Completed', 'Rescheduled', 'Cancelled', 'No Show'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <!-- Stage Filter -->
        <select name="stage" class="hr-select" style="min-width: 130px;">
            <option value="">All Stages</option>
            @foreach(['HR Interview', 'Hiring Manager', 'Technical', 'Final'] as $stg)
                <option value="{{ $stg }}" {{ request('stage') === $stg ? 'selected' : '' }}>{{ $stg }}</option>
            @endforeach
        </select>

        <!-- Type Filter -->
        <select name="type" class="hr-select" style="min-width: 130px;">
            <option value="">All Types</option>
            @foreach(['Face-to-face', 'Video', 'Phone', 'Technical / Practical'] as $tp)
                <option value="{{ $tp }}" {{ request('type') === $tp ? 'selected' : '' }}>{{ $tp }}</option>
            @endforeach
        </select>

        <!-- Interviewer Filter -->
        <select name="interviewer_id" class="hr-select" style="min-width: 140px;">
            <option value="">All Interviewers</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('interviewer_id') == $u->id ? 'selected' : '' }}>{{ $u->full_name ?? $u->name }}</option>
            @endforeach
        </select>

        <!-- Date Filter Container -->
        <div class="hr-filter-date-wrap" style="width: 145px; position: relative;">
            <input type="date" name="date" class="hr-input" placeholder="Interview Date" value="{{ request('date') }}" style="width: 100%;">
        </div>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i>
            <span>Filter</span>
        </button>

        @if(request()->anyFilled(['search', 'status', 'stage', 'type', 'interviewer_id', 'date']))
            <a href="{{ route('hr.recruitment.interviews') }}" class="hr-btn hr-btn-ghost" title="Reset Filters">
                <i class="ph ph-x-circle"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>
</div>

<!-- Interviews Table -->
<div class="hr-table-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
    <div class="hr-table-wrapper" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Candidate</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Stage & Type</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Schedule & Venue</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Interviewer</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Scorecard</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Status</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; text-align: right;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($interviews as $int)
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                        <!-- Candidate -->
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 14px;">{{ $int->applicant?->full_name }}</div>
                            <div style="font-size: 12px; color: #9333ea; margin-top: 2px;">{{ $int->applicant?->applied_position }}</div>
                            <small style="color: #64748b;">{{ $int->applicant?->contact_number }}</small>
                        </td>

                        <!-- Stage & Type -->
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 13px;">{{ $int->interview_stage }}</div>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px; margin-top: 4px; display: inline-flex; align-items: center; gap: 4px;">
                                @if($int->interview_type === 'Video')
                                    <i class="ph ph-video-camera"></i> Video
                                @elseif($int->interview_type === 'Phone')
                                    <i class="ph ph-phone"></i> Phone
                                @elseif($int->interview_type === 'Technical / Practical')
                                    <i class="ph ph-wrench"></i> Technical / Practical
                                @else
                                    <i class="ph ph-user"></i> {{ $int->interview_type ?: 'Face-to-face' }}
                                @endif
                            </span>
                        </td>

                        <!-- Schedule & Venue -->
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                <i class="ph ph-calendar" style="color: #9333ea;"></i> {{ $int->interview_date?->format('M d, Y') }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <i class="ph ph-clock"></i> {{ $int->interview_time ?: $int->interview_date?->format('h:i A') }}
                            </div>
                            @if($int->location_or_link)
                                <small style="color: #2563eb; display: block; margin-top: 2px;" title="{{ $int->location_or_link }}">
                                    <i class="ph ph-map-pin"></i> {{ Str::limit($int->location_or_link, 30) }}
                                </small>
                            @endif
                        </td>

                        <!-- Interviewer -->
                        <td style="padding: 14px 18px;">
                            <div style="font-size: 13px; font-weight: 600; color: #1e293b;">
                                {{ $int->interviewer_name ?: ($int->interviewer?->full_name ?? 'Assigned Panel') }}
                            </div>
                            <small style="color: #94a3b8;">{{ $int->interviewer?->role ?? 'Interviewer' }}</small>
                        </td>

                        <!-- Scorecard Rating -->
                        <td style="padding: 14px 18px;">
                            @if($int->rating)
                                <div style="display: flex; align-items: center; gap: 6px;">
                                    <span style="font-weight: 700; color: #d97706; font-size: 13px;">★ {{ $int->rating }}/5</span>
                                    <span class="hr-badge {{ $int->recommendation === 'Proceed' ? 'hr-badge-success' : ($int->recommendation === 'Reject' ? 'hr-badge-danger' : 'hr-badge-warning') }}" style="font-size: 10.5px;">
                                        {{ $int->recommendation ?? 'Proceed' }}
                                    </span>
                                </div>
                                @if($int->notes)
                                    <small style="color: #64748b; display: block; margin-top: 2px;">"{{ Str::limit($int->notes, 32) }}"</small>
                                @endif
                            @else
                                <span style="color: #94a3b8; font-size: 12px;">Scorecard pending</span>
                            @endif
                        </td>

                        <!-- Status Badge -->
                        <td style="padding: 14px 18px;">
                            @if($int->status === 'Completed')
                                <span class="hr-badge hr-badge-success">{{ $int->status }}</span>
                            @elseif($int->status === 'Scheduled')
                                <span class="hr-badge hr-badge-purple">{{ $int->status }}</span>
                            @elseif($int->status === 'Confirmed')
                                <span class="hr-badge hr-badge-info">{{ $int->status }}</span>
                            @elseif($int->status === 'Rescheduled')
                                <span class="hr-badge hr-badge-warning">{{ $int->status }}</span>
                            @elseif($int->status === 'Cancelled' || $int->status === 'No Show')
                                <span class="hr-badge hr-badge-danger">{{ $int->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $int->status }}</span>
                            @endif
                        </td>

                        <!-- Actions -->
                        <td style="padding: 14px 18px; text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                @if($int->status !== 'Completed')
                                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openScorecardModal({{ $int->id }}, '{{ addslashes($int->applicant?->full_name ?? 'Candidate') }}')">
                                        <i class="ph ph-star"></i> Evaluate
                                    </button>
                                @endif

                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openEditInterviewModal({{ json_encode($int) }})" title="Edit Schedule">
                                    <i class="ph ph-pencil"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-calendar-blank" style="font-size: 38px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                            No scheduled interviews found matching criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($interviews->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
            {{ $interviews->links() }}
        </div>
    @endif
</div>

<!-- Modal: Schedule Interview -->
<div id="scheduleInterviewModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 640px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(168, 85, 247, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus" style="color: #4f46e5;"></i> Schedule Candidate Interview</span>
            <button class="icon-btn" onclick="closeModal('scheduleInterviewModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.interviews.store') }}">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Select Candidate *</label>
                    <select name="applicant_id" class="hr-select" required>
                        <option value="">Choose candidate from pipeline...</option>
                        @foreach($applicants as $app)
                            <option value="{{ $app->id }}">{{ $app->full_name }} — {{ $app->applied_position }} ({{ $app->status }})</option>
                        @endforeach
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Interview Type *</label>
                        <select name="interview_type" class="hr-select" required>
                            <option value="Face-to-face">Face-to-face (On-site)</option>
                            <option value="Video">Video Call (Google Meet / Zoom)</option>
                            <option value="Phone">Phone Screening</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Interview Stage *</label>
                        <select name="interview_stage" class="hr-select" required>
                            <option value="HR Interview">HR Interview</option>
                            <option value="Hiring Manager">Hiring Manager Interview</option>
                            <option value="Technical">Technical / Practical Test</option>
                            <option value="Final">Final Interview</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Date *</label>
                        <input type="date" name="interview_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Time</label>
                        <input type="time" name="interview_time" class="hr-input" value="10:00">
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Assigned Interviewer *</label>
                    <select name="interviewer_id" class="hr-select" required>
                        <option value="">Select Interviewer</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ Auth::id() == $u->id ? 'selected' : '' }}>{{ $u->full_name ?? $u->name }} ({{ $u->role }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Location / Meeting Link</label>
                    <input type="text" name="location_or_link" class="hr-input" placeholder="e.g. Makati Branch Office / https://meet.google.com/xyz">
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Instructions for Candidate</label>
                    <textarea name="instructions" class="hr-textarea" rows="2" placeholder="e.g. Please bring 1 government ID and copies of credentials..."></textarea>
                </div>

                <input type="hidden" name="status" value="Scheduled">
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('scheduleInterviewModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-calendar-check"></i> Schedule Interview</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Structured Scorecard Evaluation -->
<div id="scorecardModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 660px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(245, 158, 11, 0.08), rgba(168, 85, 247, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-star" style="color: #f59e0b;"></i> Structured Interview Scorecard</span>
            <button class="icon-btn" onclick="closeModal('scorecardModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="scorecardForm" method="POST">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px;">
                    Candidate: <span id="scorecardCandidateName" style="color: #9333ea;"></span>
                </div>

                <div class="hr-scorecard-grid">
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Communication (1-5)</span>
                        <select name="rating_communication" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Technical Skills (1-5)</span>
                        <select name="rating_technical" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Experience (1-5)</span>
                        <select name="rating_experience" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Problem Solving (1-5)</span>
                        <select name="rating_problem_solving" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Team Fit (1-5)</span>
                        <select name="rating_team_fit" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <div class="hr-rating-row" style="background: rgba(168, 85, 247, 0.06); border-color: rgba(168, 85, 247, 0.3);">
                        <span style="font-size: 13px; font-weight: 600; color: #9333ea;">Overall Score (1-5)</span>
                        <select name="rating_overall" class="hr-select" style="width: 80px; font-weight: 400; color: #9333ea;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Interviewer Recommendation *</label>
                    <select name="recommendation" class="hr-select" required>
                        <option value="Proceed">Proceed to Next Stage / Final Review</option>
                        <option value="Hold">Hold / Reserve</option>
                        <option value="Reject">Reject Candidate</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Interviewer Comments & Notes</label>
                    <textarea name="notes" class="hr-textarea" rows="3" placeholder="Provide qualitative assessment on answers, attitude, and culinary/service competence..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('scorecardModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Submit Scorecard</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Interview -->
<div id="editInterviewModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-pencil-simple"></i> Edit Interview Schedule & Status</span>
            <button class="icon-btn" onclick="closeModal('editInterviewModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="editInterviewForm" method="POST">
            @csrf
            @method('PUT')
            <div class="hr-modal-body" style="padding: 20px;">
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Date *</label>
                        <input type="date" name="interview_date" id="edit_int_date" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Time</label>
                        <input type="time" name="interview_time" id="edit_int_time" class="hr-input">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Interview Stage *</label>
                        <select name="interview_stage" id="edit_int_stage" class="hr-select" required>
                            <option value="HR Interview">HR Interview</option>
                            <option value="Hiring Manager">Hiring Manager</option>
                            <option value="Technical">Technical</option>
                            <option value="Final">Final</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Interview Type *</label>
                        <select name="interview_type" id="edit_int_type" class="hr-select" required>
                            <option value="Face-to-face">Face-to-face</option>
                            <option value="Video">Video</option>
                            <option value="Phone">Phone</option>
                        </select>
                    </div>
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Status *</label>
                        <select name="status" id="edit_int_status" class="hr-select" required>
                            <option value="Scheduled">Scheduled</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Completed">Completed</option>
                            <option value="Rescheduled">Rescheduled</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="No Show">No Show</option>
                        </select>
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Location / Link</label>
                    <input type="text" name="location_or_link" id="edit_int_link" class="hr-input">
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Instructions / Notes</label>
                    <textarea name="instructions" id="edit_int_instructions" class="hr-textarea" rows="2"></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editInterviewModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.add('active');
    }

    function closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.remove('active');
    }

    function openScorecardModal(interviewId, candidateName) {
        document.getElementById('scorecardCandidateName').innerText = candidateName;
        document.getElementById('scorecardForm').action = '/hr/recruitment/interviews/' + interviewId + '/evaluate';
        openModal('scorecardModal');
    }

    function openEditInterviewModal(interview) {
        document.getElementById('editInterviewForm').action = '/hr/recruitment/interviews/' + interview.id;
        document.getElementById('edit_int_date').value = interview.interview_date ? interview.interview_date.substring(0, 10) : '';
        document.getElementById('edit_int_time').value = interview.interview_time || '';
        document.getElementById('edit_int_stage').value = interview.interview_stage || 'HR Interview';
        document.getElementById('edit_int_type').value = interview.interview_type || 'Face-to-face';
        document.getElementById('edit_int_status').value = interview.status || 'Scheduled';
        document.getElementById('edit_int_link').value = interview.location_or_link || '';
        document.getElementById('edit_int_instructions').value = interview.instructions || '';
        openModal('editInterviewModal');
    }
</script>
@endpush

@endsection

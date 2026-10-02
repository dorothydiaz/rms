@extends('layouts.app')

@section('title', 'Talent Acquisition - Applicants Pipeline')

@section('content')
<x-hr-tabs parent="talent-acquisition">
    <x-slot:actions>
        <button class="hr-btn hr-btn-secondary" onclick="openModal('advancedFilterModal')">
            <i class="ph ph-sliders-horizontal"></i>
            <span>Advanced Filter</span>
        </button>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addApplicantModal')">
            <i class="ph ph-user-plus"></i>
            <span>Register Applicant</span>
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

<!-- Pipeline Metric Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 8px; margin-bottom: 8px;">
    <!-- Total Applicants -->
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-users"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Pool</span>
            <div class="hr-stat-value">{{ $metrics['total'] }}</div>
            <span class="hr-stat-sub">All Applicants</span>
        </div>
    </div>

    <!-- Screening -->
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-funnel"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Screening</span>
            <div class="hr-stat-value">{{ $metrics['screening'] }}</div>
            <span class="hr-stat-sub">New & In Review</span>
        </div>
    </div>

    <!-- Shortlisted -->
    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-check-square"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Shortlisted</span>
            <div class="hr-stat-value">{{ $metrics['shortlisted'] }}</div>
            <span class="hr-stat-sub">Ready to Interview</span>
        </div>
    </div>

    <!-- Interviews & Assessment -->
    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-chat-circle-dots"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Interviews</span>
            <div class="hr-stat-value">{{ $metrics['interviewing'] }}</div>
            <span class="hr-stat-sub">Evaluation Active</span>
        </div>
    </div>

    <!-- Offers -->
    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-file-text"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Job Offers</span>
            <div class="hr-stat-value">{{ $metrics['offers'] }}</div>
            <span class="hr-stat-sub">Awaiting Decision</span>
        </div>
    </div>

    <!-- Pre-Employment -->
    <div class="hr-stat-card hr-stat-card-indigo">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-identification-card"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Pre-Employment</span>
            <div class="hr-stat-value">{{ $metrics['pre_employment'] }}</div>
            <span class="hr-stat-sub">Requirements Check</span>
        </div>
    </div>

    <!-- Hired -->
    <div class="hr-stat-card hr-stat-card-emerald">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-user-check"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Hired</span>
            <div class="hr-stat-value">{{ $metrics['hired'] }}</div>
            <span class="hr-stat-sub">Core Employees</span>
        </div>
    </div>
</div>

<!-- Primary Filter Bar -->
<div class="hr-filter-bar" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
    <form method="GET" action="{{ route('hr.recruitment.applicants') }}" class="hr-filter-form" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- Search Input -->
        <div style="flex: 1; min-width: 220px; position: relative;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
            <input type="text" name="search" class="hr-input" placeholder="Search applicant name, email, role..." value="{{ request('search') }}" style="padding-left: 36px; width: 100%;">
        </div>

        <!-- Pipeline Status Filter -->
        <select name="status" class="hr-select" style="min-width: 140px;">
            <option value="">All Statuses</option>
            <optgroup label="Active Pipeline">
                <option value="New" {{ request('status') === 'New' ? 'selected' : '' }}>New</option>
                <option value="Screening" {{ request('status') === 'Screening' ? 'selected' : '' }}>Screening</option>
                <option value="Shortlisted" {{ request('status') === 'Shortlisted' ? 'selected' : '' }}>Shortlisted</option>
                <option value="Interview" {{ request('status') === 'Interview' ? 'selected' : '' }}>Interview</option>
                <option value="Assessment" {{ request('status') === 'Assessment' ? 'selected' : '' }}>Assessment</option>
                <option value="Final Review" {{ request('status') === 'Final Review' ? 'selected' : '' }}>Final Review</option>
                <option value="Offer" {{ request('status') === 'Offer' ? 'selected' : '' }}>Job Offer</option>
                <option value="Pre-Employment" {{ request('status') === 'Pre-Employment' ? 'selected' : '' }}>Pre-Employment</option>
                <option value="Hired" {{ request('status') === 'Hired' ? 'selected' : '' }}>Hired</option>
            </optgroup>
            <optgroup label="Terminal Statuses">
                <option value="On Hold" {{ request('status') === 'On Hold' ? 'selected' : '' }}>On Hold</option>
                <option value="Rejected" {{ request('status') === 'Rejected' ? 'selected' : '' }}>Rejected</option>
                <option value="Withdrawn" {{ request('status') === 'Withdrawn' ? 'selected' : '' }}>Withdrawn</option>
                <option value="Offer Declined" {{ request('status') === 'Offer Declined' ? 'selected' : '' }}>Offer Declined</option>
                <option value="No Show" {{ request('status') === 'No Show' ? 'selected' : '' }}>No Show</option>
            </optgroup>
        </select>

        <!-- Position Filter -->
        <select name="position" class="hr-select" style="min-width: 150px;">
            <option value="">All Positions</option>
            @foreach($positions as $pos)
                <option value="{{ $pos->name }}" {{ request('position') == $pos->name ? 'selected' : '' }}>{{ $pos->name }}</option>
            @endforeach
        </select>

        <!-- Source Filter -->
        <select name="source" class="hr-select" style="min-width: 130px;">
            <option value="">All Sources</option>
            @foreach(['Walk-in', 'Online', 'Referral', 'Job Fair', 'Agency'] as $src)
                <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
            @endforeach
        </select>

        <!-- Recruiter Filter -->
        <select name="recruiter_id" class="hr-select" style="min-width: 150px;">
            <option value="">All Recruiters</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('recruiter_id') == $u->id ? 'selected' : '' }}>{{ $u->full_name ?? $u->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i>
            <span>Filter</span>
        </button>

        @if(request()->anyFilled(['search', 'status', 'position', 'source', 'recruiter_id', 'date_from', 'date_to']))
            <a href="{{ route('hr.recruitment.applicants') }}" class="hr-btn hr-btn-ghost" title="Reset Filters">
                <i class="ph ph-x-circle"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>
</div>

<!-- Applicants Table Card -->
<div class="hr-table-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
    <div class="hr-table-wrapper" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Applicant Name</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Applied Position</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Contact Info</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Source</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Application Date</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Pipeline Status</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; text-align: right;">Hiring Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($applicants as $app)
                    @php
                        $reqStats = $app->requirements_stats;
                        $onbProgress = $app->onboarding_progress;
                    @endphp
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                        <!-- Applicant Name -->
                        <td style="padding: 14px 18px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 36px; height: 36px; border-radius: 50%; background: linear-gradient(135deg, rgba(236, 72, 153, 0.18), rgba(168, 85, 247, 0.22)); border: 1px solid rgba(168, 85, 247, 0.3); display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 13px; color: #9333ea; flex-shrink: 0;">
                                    {{ strtoupper(substr($app->first_name, 0, 1) . substr($app->last_name, 0, 1)) }}
                                </div>
                                <div>
                                    <a href="javascript:void(0)" onclick="openApplicantDrawer({{ $app->id }})" style="font-weight: 600; color: #0f172a; font-size: 14px; text-decoration: none;" class="hover-underline">
                                        {{ $app->full_name }}
                                    </a>
                                    @if($app->preferred_name)
                                        <small style="color: #64748b;">({{ $app->preferred_name }})</small>
                                    @endif
                                    <div style="font-size: 11.5px; color: #94a3b8; margin-top: 1px;">
                                        {{ $app->city ?: ($app->address ?? 'Manila, Philippines') }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Applied Position -->
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 13px;">{{ $app->applied_position }}</div>
                            <small style="color: #9333ea; font-size: 11.5px; display: block; margin-top: 2px;">
                                {{ $app->jobVacancy?->title ?? 'Direct Application' }}
                            </small>
                            @if($app->desired_salary)
                                <span style="font-size: 11px; color: #64748b;">Exp: ₱{{ number_format($app->desired_salary, 0) }}</span>
                            @endif
                        </td>

                        <!-- Contact Info -->
                        <td style="padding: 14px 18px;">
                            <div style="font-size: 13px; color: #1e293b; font-weight: 500;">
                                <i class="ph ph-phone" style="color: #64748b;"></i> {{ $app->contact_number ?? 'No phone' }}
                            </div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                <i class="ph ph-envelope" style="color: #64748b;"></i> {{ $app->email ?? 'No email' }}
                            </div>
                        </td>

                        <!-- Source -->
                        <td style="padding: 14px 18px;">
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px;">{{ $app->source }}</span>
                        </td>

                        <!-- Application Date -->
                        <td style="padding: 14px 18px; font-size: 12.5px; color: #475569;">
                            {{ $app->application_date?->format('M d, Y') ?? '—' }}
                        </td>

                        <!-- Pipeline Status (Pill badges with modern colors) -->
                        <td style="padding: 14px 18px;">
                            @if($app->status === 'New')
                                <span class="hr-badge hr-badge-neutral" style="background: rgba(148, 163, 184, 0.15); color: #475569;">
                                    <i class="ph ph-sparkle"></i> New
                                </span>
                            @elseif($app->status === 'Screening')
                                <span class="hr-badge hr-badge-warning" style="background: rgba(245, 158, 11, 0.12); color: #d97706;">
                                    <i class="ph ph-funnel"></i> Screening
                                </span>
                            @elseif($app->status === 'Shortlisted')
                                <span class="hr-badge hr-badge-purple" style="background: rgba(168, 85, 247, 0.14); color: #9333ea;">
                                    <i class="ph ph-check-square"></i> Shortlisted
                                </span>
                            @elseif($app->status === 'Interview')
                                <span class="hr-badge" style="background: rgba(99, 102, 241, 0.14); color: #4f46e5; border-radius: 9999px; padding: 4px 10px; font-size: 11px; font-weight: 600;">
                                    <i class="ph ph-chat-circle-dots"></i> Interview
                                </span>
                            @elseif($app->status === 'Assessment')
                                <span class="hr-badge hr-badge-info" style="background: rgba(14, 165, 233, 0.14); color: #0284c7;">
                                    <i class="ph ph-clipboard-text"></i> Assessment
                                </span>
                            @elseif($app->status === 'Final Review')
                                <span class="hr-badge" style="background: rgba(124, 58, 237, 0.14); color: #7c3aed; border-radius: 9999px; padding: 4px 10px; font-size: 11px; font-weight: 600;">
                                    <i class="ph ph-scales"></i> Final Review
                                </span>
                            @elseif($app->status === 'Offer')
                                <span class="hr-badge" style="background: rgba(236, 72, 153, 0.14); color: #db2777; border-radius: 9999px; padding: 4px 10px; font-size: 11px; font-weight: 600;">
                                    <i class="ph ph-file-text"></i> Job Offer
                                </span>
                            @elseif($app->status === 'Pre-Employment')
                                <span class="hr-badge" style="background: rgba(16, 185, 129, 0.14); color: #059669; border-radius: 9999px; padding: 4px 10px; font-size: 11px; font-weight: 600;">
                                    <i class="ph ph-identification-card"></i> Pre-Employment
                                </span>
                            @elseif($app->status === 'Hired')
                                <span class="hr-badge hr-badge-success" style="background: rgba(16, 185, 129, 0.18); color: #047857;">
                                    <i class="ph ph-check-circle"></i> Hired
                                </span>
                            @elseif($app->status === 'Rejected')
                                <span class="hr-badge hr-badge-danger" style="background: rgba(239, 68, 68, 0.12); color: #dc2626;">
                                    <i class="ph ph-x-circle"></i> Rejected
                                </span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $app->status }}</span>
                            @endif
                        </td>

                        <!-- DYNAMIC HIRING ACTION COLUMN (Strictly conforming to Section 17) -->
                        <td style="padding: 14px 18px; text-align: right;">
                            <div style="display: inline-flex; align-items: center; justify-content: flex-end; gap: 6px; flex-wrap: nowrap;">
                                @if(in_array($app->status, ['New', 'Screening']))
                                    <!-- View | Screen | Move Stage -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'overview')" title="View Candidate Profile">
                                        View
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openScreeningModal({{ $app->id }}, '{{ addslashes($app->full_name) }}')" style="color: #9333ea;" title="Screen Candidate">
                                        <i class="ph ph-funnel"></i> Screen
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openStageModal({{ $app->id }}, '{{ $app->status }}')" title="Move Pipeline Stage">
                                        Move Stage
                                    </button>

                                @elseif(in_array($app->status, ['Shortlisted', 'Interview']))
                                    <!-- View | Schedule | Evaluate -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'interviews')" title="View Interview Details">
                                        View
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openScheduleInterviewFor({{ $app->id }}, '{{ addslashes($app->full_name) }}', '{{ addslashes($app->applied_position) }}')" style="color: #4f46e5;" title="Schedule Interview">
                                        <i class="ph ph-calendar-plus"></i> Schedule
                                    </button>
                                    @if($app->interviews->where('status', 'Scheduled')->count() > 0)
                                        <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openScorecardModal({{ $app->interviews->where('status', 'Scheduled')->first()->id }}, '{{ addslashes($app->full_name) }}')" title="Evaluate Scorecard">
                                            <i class="ph ph-star"></i> Evaluate
                                        </button>
                                    @else
                                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openStageModal({{ $app->id }}, '{{ $app->status }}')">
                                            Move Stage
                                        </button>
                                    @endif

                                @elseif($app->status === 'Assessment')
                                    <!-- View | Record Result -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'assessments')">
                                        View
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openAssessmentModal({{ $app->id }}, '{{ addslashes($app->full_name) }}')" style="background: linear-gradient(135deg, #0284c7, #0369a1);">
                                        <i class="ph ph-plus-circle"></i> Record Result
                                    </button>

                                @elseif($app->status === 'Final Review')
                                    <!-- View | Final Review -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'final-review')">
                                        View
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openFinalReviewModal({{ $app->id }}, '{{ addslashes($app->full_name) }}', {{ $app->desired_salary ?: 25000 }})" style="background: linear-gradient(135deg, #7c3aed, #9333ea);">
                                        <i class="ph ph-scales"></i> Final Review
                                    </button>

                                @elseif($app->status === 'Offer')
                                    <!-- View Offer | Send Reminder / Accept -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'offer')">
                                        View Offer
                                    </button>
                                    @if(($app->offer_data['offer_status'] ?? 'Draft') === 'Draft')
                                        <form method="POST" action="{{ route('hr.recruitment.applicants.offer-status', $app->id) }}" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="action" value="send">
                                            <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #db2777;">
                                                <i class="ph ph-paper-plane-tilt"></i> Send Offer
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('hr.recruitment.applicants.offer-status', $app->id) }}" style="display: inline;">
                                            @csrf
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="hr-btn hr-btn-success hr-btn-sm">
                                                <i class="ph ph-check"></i> Record Accept
                                            </button>
                                        </form>
                                    @endif

                                @elseif($app->status === 'Pre-Employment')
                                    <!-- Requirements 8/10 Badge Button -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'pre-employment')" style="color: #059669; font-weight: 700; background: rgba(16, 185, 129, 0.08); border-color: rgba(16, 185, 129, 0.3);">
                                        <i class="ph ph-check-square"></i> Requirements {{ $reqStats['completed'] }}/{{ $reqStats['total'] }}
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openConvertModal({{ $app->id }}, '{{ addslashes($app->full_name) }}', '{{ addslashes($app->applied_position) }}')" style="background: linear-gradient(135deg, #10b981, #059669);">
                                        <i class="ph ph-user-check"></i> Convert
                                    </button>

                                @elseif($app->status === 'Hired' && $app->hiredAsEmployee)
                                    <!-- ✓ EMP-2026-507 linking directly to core employee profile -->
                                    <a href="{{ route('hr.people.employees.show', $app->hiredAsEmployee->id) }}" class="hr-badge hr-badge-success" style="text-decoration: none; padding: 6px 12px; font-weight: 700; font-size: 12px; display: inline-flex; align-items: center; gap: 4px; box-shadow: 0 1px 3px rgba(16, 185, 129, 0.2);">
                                        <i class="ph ph-check-circle"></i> ✓ {{ $app->hiredAsEmployee->employee_id }}
                                    </a>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'onboarding')" title="View Onboarding Track">
                                        <i class="ph ph-chalkboard-teacher"></i> Onboarding {{ $onbProgress }}%
                                    </button>

                                @else
                                    <!-- Terminal / Generic fallback -->
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openApplicantDrawer({{ $app->id }}, 'overview')">
                                        View
                                    </button>
                                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openStageModal({{ $app->id }}, '{{ $app->status }}')">
                                        Reactivate
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-users" style="font-size: 38px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                            No applicants found matching current search criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($applicants->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
            {{ $applicants->links() }}
        </div>
    @endif
</div>

<!-- ============================================================
     APPLICANT PROFILE SLIDE-OUT DRAWER (SECTIONS 4, 6, 8, 9, 10, 11, 12, 14)
     ============================================================ -->
<div id="applicantDrawerOverlay" class="hr-drawer-overlay" onclick="closeApplicantDrawer()"></div>
<div id="applicantDrawer" class="hr-drawer">
    <div class="hr-drawer-header">
        <button type="button" class="hr-drawer-close" onclick="closeApplicantDrawer()" title="Close Drawer">
            <i class="ph ph-x"></i>
        </button>
        <div style="display: flex; align-items: center; gap: 14px;">
            <div id="drawerAvatar" style="width: 54px; height: 54px; border-radius: 50%; background: linear-gradient(135deg, #ec4899, #a855f7); color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 650; box-shadow: 0 4px 12px rgba(168, 85, 247, 0.25);">
                AA
            </div>
            <div>
                <h2 id="drawerFullName" style="font-size: 18px; font-weight: 650; color: #0f172a; margin: 0;">Applicant Name</h2>
                <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px; flex-wrap: wrap;">
                    <span id="drawerAppliedPosition" style="font-size: 13px; font-weight: 600; color: #9333ea;">Applied Position</span>
                    <span style="color: #cbd5e1;">•</span>
                    <span id="drawerAppDate" style="font-size: 12px; color: #64748b;">Applied Date</span>
                    <span style="color: #cbd5e1;">•</span>
                    <span id="drawerStatusBadge" class="hr-badge hr-badge-neutral">Status</span>
                </div>
            </div>
        </div>

        <!-- Pipeline Horizontal Progress Stepper -->
        <div class="hr-pipeline-stepper" style="margin-top: 16px; margin-bottom: 0;">
            <div class="hr-step-item" id="step-New"><div class="hr-step-dot">1</div><span class="hr-step-name">New</span></div>
            <div class="hr-step-item" id="step-Screening"><div class="hr-step-dot">2</div><span class="hr-step-name">Screen</span></div>
            <div class="hr-step-item" id="step-Shortlisted"><div class="hr-step-dot">3</div><span class="hr-step-name">Shortlist</span></div>
            <div class="hr-step-item" id="step-Interview"><div class="hr-step-dot">4</div><span class="hr-step-name">Interview</span></div>
            <div class="hr-step-item" id="step-Assessment"><div class="hr-step-dot">5</div><span class="hr-step-name">Assess</span></div>
            <div class="hr-step-item" id="step-FinalReview"><div class="hr-step-dot">6</div><span class="hr-step-name">Review</span></div>
            <div class="hr-step-item" id="step-Offer"><div class="hr-step-dot">7</div><span class="hr-step-name">Offer</span></div>
            <div class="hr-step-item" id="step-PreEmployment"><div class="hr-step-dot">8</div><span class="hr-step-name">Pre-Emp</span></div>
            <div class="hr-step-item" id="step-Hired"><div class="hr-step-dot">9</div><span class="hr-step-name">Hired</span></div>
        </div>
    </div>

    <!-- Micro Navigation Tabs inside Drawer -->
    <div style="padding: 0 24px; background: #ffffff; border-bottom: 1px solid #e2e8f0;">
        <div class="hr-micro-tabs" style="margin-bottom: 0;">
            <button type="button" class="hr-micro-tab active" onclick="switchDrawerTab('overview', this)"><i class="ph ph-user"></i> Overview</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('application', this)"><i class="ph ph-file-text"></i> Application</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('experience', this)"><i class="ph ph-briefcase"></i> Experience</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('interviews', this)"><i class="ph ph-chat-circle-dots"></i> Interviews</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('assessments', this)"><i class="ph ph-clipboard-text"></i> Assessments</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('screening', this)"><i class="ph ph-funnel"></i> Screening</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('final-review', this)"><i class="ph ph-scales"></i> Final Review</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('offer', this)"><i class="ph ph-stamp"></i> Offer</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('pre-employment', this)"><i class="ph ph-identification-card"></i> Pre-Employment</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('preboarding', this)"><i class="ph ph-check-square"></i> Preboarding</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('onboarding', this)"><i class="ph ph-chalkboard-teacher"></i> Onboarding</button>
            <button type="button" class="hr-micro-tab" onclick="switchDrawerTab('activity', this)"><i class="ph ph-clock-counter-clockwise"></i> Activity</button>
        </div>
    </div>

    <div class="hr-drawer-body">
        <!-- 1. Overview Tab -->
        <div id="tab-overview" class="drawer-tab-content">
            <!-- Fast Status Banner -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase;">Recruitment Progress</div>
                        <div id="drawerProgressText" style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 2px;">65% Completed</div>
                    </div>
                    <div id="drawerActionBtnArea"></div>
                </div>
                <div style="width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin-top: 10px;">
                    <div id="drawerProgressBar" style="height: 100%; width: 50%; background: linear-gradient(90deg, #ec4899, #9333ea); border-radius: 4px; transition: width 0.3s ease;"></div>
                </div>
            </div>

            <!-- Contact & Profile Cards -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                    <div style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">Contact Information</div>
                    <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;"><i class="ph ph-phone" style="color: #9333ea;"></i> <span id="drawerContact"></span></div>
                    <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;"><i class="ph ph-envelope" style="color: #9333ea;"></i> <span id="drawerEmail"></span></div>
                    <div style="font-size: 12px; color: #64748b;"><i class="ph ph-map-pin" style="color: #9333ea;"></i> <span id="drawerAddress"></span></div>
                </div>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                    <div style="font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; margin-bottom: 8px;">Application Snapshot</div>
                    <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;"><strong>Desired Salary:</strong> <span id="drawerSalary"></span></div>
                    <div style="font-size: 13px; color: #1e293b; margin-bottom: 4px;"><strong>Work Setup:</strong> <span id="drawerSetup"></span></div>
                    <div style="font-size: 12px; color: #64748b;"><strong>Source:</strong> <span id="drawerSource"></span></div>
                </div>
            </div>

            <!-- Pre-employment & Preboarding Quick Meters -->
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 11px; font-weight: 600; color: #059669; text-transform: uppercase;">Pre-Employment Docs</div>
                        <div id="drawerReqMeter" style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 2px;">0 / 14</div>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchDrawerTab('pre-employment')">Review Docs</button>
                </div>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <div style="font-size: 11px; font-weight: 600; color: #9333ea; text-transform: uppercase;">Preboarding Tasks</div>
                        <div id="drawerTaskMeter" style="font-size: 18px; font-weight: 700; color: #0f172a; margin-top: 2px;">0 / 12</div>
                    </div>
                    <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="switchDrawerTab('preboarding')">Track Tasks</button>
                </div>
            </div>

            <!-- Recent Activity Timeline Snippet -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px;">
                <div style="font-size: 13px; font-weight: 700; color: #0f172a; margin-bottom: 12px; display: flex; justify-content: space-between;">
                    <span>Recent Activity</span>
                    <a href="javascript:void(0)" onclick="switchDrawerTab('activity')" style="font-size: 12px; color: #9333ea; text-decoration: none;">View All</a>
                </div>
                <div id="drawerRecentActivityList"></div>
            </div>
        </div>

        <!-- 2. Application Tab -->
        <div id="tab-application" class="drawer-tab-content" style="display: none;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">Candidate Application Answers</h3>
                <div id="drawerAppAnswersList"></div>
            </div>
        </div>

        <!-- 3. Experience Tab -->
        <div id="tab-experience" class="drawer-tab-content" style="display: none;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">Work History</h3>
                <div id="drawerWorkHistoryList"></div>
            </div>

            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">Education</h3>
                <div id="drawerEducationList"></div>
            </div>

            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">Skills & Certifications</h3>
                <div id="drawerSkillsList"></div>
            </div>
        </div>

        <!-- 4. Interviews Tab -->
        <div id="tab-interviews" class="drawer-tab-content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Scheduled & Completed Interviews</h3>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" id="drawerScheduleInterviewBtn">
                    <i class="ph ph-calendar-plus"></i> Schedule Interview
                </button>
            </div>
            <div id="drawerInterviewsList"></div>
        </div>

        <!-- 5. Assessments Tab -->
        <div id="tab-assessments" class="drawer-tab-content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Candidate Skill Assessments</h3>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" id="drawerAddAssessmentBtn">
                    <i class="ph ph-plus-circle"></i> Record Assessment
                </button>
            </div>
            <div id="drawerAssessmentsList"></div>
        </div>

        <!-- 6. Screening Tab -->
        <div id="tab-screening" class="drawer-tab-content" style="display: none;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Basic Screening Panel</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Verify minimum mandatory candidate qualifications before proceeding to interviews.</p>
                <div id="drawerScreeningContent"></div>
            </div>
        </div>

        <!-- 7. Final Review Tab -->
        <div id="tab-final-review" class="drawer-tab-content" style="display: none;">
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 6px;">Final Hiring Review & Endorsement</h3>
                <p style="font-size: 12px; color: #64748b; margin-bottom: 16px;">Consolidated evaluation of scores, recruiter recommendation, and proposed compensation.</p>
                <div id="drawerFinalReviewContent"></div>
            </div>
        </div>

        <!-- 8. Job Offer Tab -->
        <div id="tab-offer" class="drawer-tab-content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Job Offer Management</h3>
                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" id="drawerEditOfferBtn">
                    <i class="ph ph-pencil"></i> Edit Terms
                </button>
            </div>
            <div id="drawerOfferContent"></div>
        </div>

        <!-- 9. Pre-Employment Requirements Tab -->
        <div id="tab-pre-employment" class="drawer-tab-content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Pre-Employment Requirements</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">Philippine statutory clearances and formal employment paperwork</p>
                </div>
                <span id="drawerReqCounterBadge" class="hr-badge hr-badge-success" style="font-size: 13px; font-weight: 700; padding: 6px 12px;">0 / 14 Completed</span>
            </div>
            <div id="drawerRequirementsList"></div>
        </div>

        <!-- 10. Preboarding Checklist Tab -->
        <div id="tab-preboarding" class="drawer-tab-content" style="display: none;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <div>
                    <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin: 0;">Preboarding Readiness Checklist</h3>
                    <p style="font-size: 12px; color: #64748b; margin: 2px 0 0 0;">HR, IT, Admin, and Manager preparation before candidate Day 1</p>
                </div>
                <span id="drawerTaskCounterBadge" class="hr-badge hr-badge-purple" style="font-size: 13px; font-weight: 700; padding: 6px 12px;">0 / 12 Completed</span>
            </div>
            <div id="drawerPreboardingList"></div>
        </div>

        <!-- 11. Onboarding Tab -->
        <div id="tab-onboarding" class="drawer-tab-content" style="display: none;">
            <div id="drawerOnboardingContent"></div>
        </div>

        <!-- 12. Activity Timeline Tab -->
        <div id="tab-activity" class="drawer-tab-content" style="display: none;">
            <h3 style="font-size: 15px; font-weight: 700; color: #0f172a; margin-bottom: 14px;">Chronological Recruitment Audit Trail</h3>
            <div id="drawerFullActivityStream" class="hr-timeline-stream"></div>
        </div>
    </div>

    <!-- Drawer Footer Quick Convert & Action Bar -->
    <div class="hr-drawer-footer">
        <div style="font-size: 12px; color: #64748b;">
            Candidate ID: <strong id="drawerFooterAppId">#0</strong>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="button" class="hr-btn hr-btn-secondary" onclick="openCurrentStageModal()">
                <i class="ph ph-arrow-right"></i> Move Stage
            </button>
            <button type="button" class="hr-btn hr-btn-primary" id="drawerConvertBtn" style="background: linear-gradient(135deg, #10b981, #059669);">
                <i class="ph ph-user-check"></i> Convert to Employee
            </button>
        </div>
    </div>
</div>

<!-- ============================================================
     MODALS FOR ALL STAGES & WORKFLOWS
     ============================================================ -->

<!-- Modal: Register Applicant (Comprehensive Form) -->
<div id="addApplicantModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 900px; width: 95%;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.06), rgba(168, 85, 247, 0.12));">
            <span class="hr-modal-title" style="color: #0f172a; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-user-plus" style="color: #9333ea;"></i> Register New Applicant
            </span>
            <button class="icon-btn" onclick="closeModal('addApplicantModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.applicants.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body" style="max-height: 75vh; overflow-y: auto; padding: 24px;">
                <!-- 1. Personal Information -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-user"></i> 1. Personal Information
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">First Name *</label>
                            <input type="text" name="first_name" class="hr-input" required placeholder="First name">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Middle Name</label>
                            <input type="text" name="middle_name" class="hr-input" placeholder="Middle name">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Last Name *</label>
                            <input type="text" name="last_name" class="hr-input" required placeholder="Last name">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Suffix</label>
                            <input type="text" name="suffix" class="hr-input" placeholder="Jr., III, etc.">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Preferred Name / Nickname</label>
                            <input type="text" name="preferred_name" class="hr-input" placeholder="Preferred alias">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="hr-input">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Gender</label>
                            <select name="gender" class="hr-select">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Civil Status</label>
                            <select name="civil_status" class="hr-select">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Nationality</label>
                            <input type="text" name="nationality" class="hr-input" value="Filipino">
                        </div>
                    </div>
                </div>

                <!-- 2. Contact Information -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-phone"></i> 2. Contact Information
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">Email Address *</label>
                            <input type="email" name="email" class="hr-input" required placeholder="candidate@email.com">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Mobile Number *</label>
                            <input type="tel" name="contact_number" class="hr-input" required placeholder="0917-xxx-xxxx or +63 9xx xxx xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">City</label>
                            <input type="text" name="city" class="hr-input" placeholder="e.g. Makati City">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Province</label>
                            <input type="text" name="province" class="hr-input" placeholder="e.g. Metro Manila">
                        </div>
                        <div class="hr-form-group" style="grid-column: span 2;">
                            <label class="hr-form-label">Current Address</label>
                            <input type="text" name="address" class="hr-input" placeholder="Street, Barangay, House No.">
                        </div>
                    </div>
                </div>

                <!-- 3. Professional Information -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-briefcase"></i> 3. Professional Information
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">Applied Position *</label>
                            <input type="text" name="applied_position" class="hr-input" required placeholder="e.g. Line Cook / Griller">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Linked Vacancy</label>
                            <select name="job_vacancy_id" class="hr-select">
                                <option value="">General Candidate Pool</option>
                                @foreach($vacancies as $v)
                                    <option value="{{ $v->id }}">{{ $v->title }} ({{ $v->branch?->name ?? 'All' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Desired Salary (₱/mo)</label>
                            <input type="number" step="0.01" name="desired_salary" class="hr-input" placeholder="e.g. 25000">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Employment Type Preference</label>
                            <select name="employment_type" class="hr-select">
                                <option value="Regular">Regular</option>
                                <option value="Probationary">Probationary</option>
                                <option value="Contractual">Contractual</option>
                                <option value="Part-time">Part-time</option>
                                <option value="Intern">Intern</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Work Setup Preference</label>
                            <select name="work_setup_preference" class="hr-select">
                                <option value="On-site">On-site</option>
                                <option value="Hybrid">Hybrid</option>
                                <option value="Remote">Remote</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Available Start Date</label>
                            <input type="date" name="available_start_date" class="hr-input" value="{{ date('Y-m-d', strtotime('+14 days')) }}">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Total Years of Experience</label>
                            <input type="text" name="years_of_experience" class="hr-input" placeholder="e.g. 3 years">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Application Source *</label>
                            <select name="source" class="hr-select" required>
                                <option value="Walk-in">Walk-in</option>
                                <option value="Online">Online Job Board</option>
                                <option value="Referral">Employee Referral</option>
                                <option value="Job Fair">Job Fair</option>
                                <option value="Agency">Agency</option>
                            </select>
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Assigned Recruiter</label>
                            <select name="recruiter_id" class="hr-select">
                                <option value="">Select Recruiter</option>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}" {{ Auth::id() == $u->id ? 'selected' : '' }}>{{ $u->full_name ?? $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 4. Education -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-graduation-cap"></i> 4. Education
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">School / University</label>
                            <input type="text" name="school" class="hr-input" placeholder="School name">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Degree / Level</label>
                            <input type="text" name="degree" class="hr-input" placeholder="e.g. Bachelor's Degree / Vocational">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Course / Major</label>
                            <input type="text" name="course" class="hr-input" placeholder="e.g. Culinary Arts / Hospitality">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Year Graduated</label>
                            <input type="text" name="year_graduated" class="hr-input" placeholder="e.g. 2022">
                        </div>
                        <div class="hr-form-group" style="grid-column: span 2;">
                            <label class="hr-form-label">Honors / Distinctions</label>
                            <input type="text" name="honors" class="hr-input" placeholder="e.g. Dean's Lister, Cum Laude">
                        </div>
                    </div>
                </div>

                <!-- 5. Work Experience (Dynamic Repeater) -->
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin: 0; display: flex; align-items: center; gap: 6px;">
                            <i class="ph ph-buildings"></i> 5. Work Experience
                        </h4>
                        <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="addExperienceRow()">
                            <i class="ph ph-plus"></i> Add Past Employer
                        </button>
                    </div>

                    <div id="experienceRowsContainer">
                        <div class="experience-row" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px;">
                            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                                <div class="hr-form-group">
                                    <label class="hr-form-label">Company Name</label>
                                    <input type="text" name="experience_companies[]" class="hr-input" placeholder="Company name">
                                </div>
                                <div class="hr-form-group">
                                    <label class="hr-form-label">Position Held</label>
                                    <input type="text" name="experience_positions[]" class="hr-input" placeholder="Role title">
                                </div>
                                <div class="hr-form-group">
                                    <label class="hr-form-label">Start Date</label>
                                    <input type="date" name="experience_start_dates[]" class="hr-input">
                                </div>
                                <div class="hr-form-group">
                                    <label class="hr-form-label">End Date</label>
                                    <input type="date" name="experience_end_dates[]" class="hr-input">
                                </div>
                                <div class="hr-form-group">
                                    <label class="hr-form-label">Reason for Leaving</label>
                                    <input type="text" name="experience_reasons_leaving[]" class="hr-input" placeholder="e.g. Career growth">
                                </div>
                                <div class="hr-form-group">
                                    <label class="hr-form-label">Responsibilities Summary</label>
                                    <input type="text" name="experience_responsibilities[]" class="hr-input" placeholder="Key accomplishments">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 6. Skills & Certifications -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-sparkle"></i> 6. Skills, Certifications & Languages
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">Technical Skills (comma separated)</label>
                            <input type="text" name="technical_skills" class="hr-input" placeholder="e.g. Food Prep, Grill, Inventory, HACCP">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Soft Skills (comma separated)</label>
                            <input type="text" name="soft_skills" class="hr-input" placeholder="e.g. Teamwork, Punctuality, Communication">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Certifications / Licenses</label>
                            <input type="text" name="certifications_text" class="hr-input" placeholder="e.g. TESDA NC-II Cookery, Food Safety Officer">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Languages Spoken</label>
                            <input type="text" name="languages_text" class="hr-input" placeholder="e.g. English, Filipino, Ilocano">
                        </div>
                    </div>
                </div>

                <!-- 7. Application Questions -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-chat-centered-text"></i> 7. Application Questions
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div class="hr-form-group" style="grid-column: span 2;">
                            <label class="hr-form-label">Why do you want to join our company?</label>
                            <textarea name="q_why_join" class="hr-textarea" rows="2" placeholder="Applicant's motivation..."></textarea>
                        </div>
                        <div class="hr-form-group" style="grid-column: span 2;">
                            <label class="hr-form-label">What relevant experience do you have?</label>
                            <textarea name="q_relevant_exp" class="hr-textarea" rows="2" placeholder="Specific kitchen / service highlights..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- 8. Document Uploads -->
                <div style="margin-bottom: 20px;">
                    <h4 style="font-size: 13px; font-weight: 700; color: #9333ea; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px; display: flex; align-items: center; gap: 6px;">
                        <i class="ph ph-upload-simple"></i> 8. Document Attachments
                    </h4>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div class="hr-form-group">
                            <label class="hr-form-label">Resume / Curriculum Vitae (PDF/Doc)</label>
                            <input type="file" name="resume" class="hr-input" accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Government ID Scan</label>
                            <input type="file" name="id_document_file" class="hr-input" accept=".pdf,.jpg,.png">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Certificates / Diploma Scan</label>
                            <input type="file" name="certificates_file" class="hr-input" accept=".pdf,.zip,.jpg,.png">
                        </div>
                        <div class="hr-form-group">
                            <label class="hr-form-label">Portfolio / Dishes Showcase (Optional)</label>
                            <input type="file" name="portfolio" class="hr-input" accept=".pdf,.zip,.doc,.docx">
                        </div>
                    </div>
                </div>

                <!-- 9. Consents & Declaration -->
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px;">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1e293b; margin-bottom: 8px; cursor: pointer;">
                        <input type="checkbox" name="privacy_consent" value="1" checked required>
                        <span>Candidate consents to Philippine Data Privacy Act (RA 10173) processing of submitted personal data for hiring.</span>
                    </label>
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #1e293b; cursor: pointer;">
                        <input type="checkbox" name="applicant_declaration" value="1" checked required>
                        <span>Applicant declares that all provided information is true, correct, and complete to the best of their knowledge.</span>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addApplicantModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Complete Registration</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Advanced Filter -->
<div id="advancedFilterModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 540px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-sliders-horizontal"></i> Advanced Talent Filter</span>
            <button class="icon-btn" onclick="closeModal('advancedFilterModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="GET" action="{{ route('hr.recruitment.applicants') }}">
            <div class="hr-modal-body" style="padding: 20px;">
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Application Date From</label>
                    <input type="date" name="date_from" class="hr-input" value="{{ request('date_from') }}">
                </div>
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Application Date To</label>
                    <input type="date" name="date_to" class="hr-input" value="{{ request('date_to') }}">
                </div>
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Pipeline Stage</label>
                    <select name="status" class="hr-select">
                        <option value="">All Stages</option>
                        @foreach(['New', 'Screening', 'Shortlisted', 'Interview', 'Assessment', 'Final Review', 'Offer', 'Pre-Employment', 'Hired', 'Rejected', 'On Hold'] as $st)
                            <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Application Source</label>
                    <select name="source" class="hr-select">
                        <option value="">All Sources</option>
                        @foreach(['Walk-in', 'Online', 'Referral', 'Job Fair', 'Agency'] as $src)
                            <option value="{{ $src }}" {{ request('source') === $src ? 'selected' : '' }}>{{ $src }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <a href="{{ route('hr.recruitment.applicants') }}" class="hr-btn hr-btn-secondary">Clear</a>
                <button type="submit" class="hr-btn hr-btn-primary">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Basic Screening Checklist & Decision -->
<div id="screeningModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(168, 85, 247, 0.08), rgba(236, 72, 153, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-funnel" style="color: #9333ea;"></i> Recruiter Screening Evaluation</span>
            <button class="icon-btn" onclick="closeModal('screeningModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="screeningForm" method="POST">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 12px;">
                    Candidate: <span id="screeningApplicantName" style="color: #9333ea;"></span>
                </div>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; margin-bottom: 16px;">
                    <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">Basic Screening Verification</div>

                    @foreach([
                        ['key' => 'chk_education', 'label' => 'Education requirement verified'],
                        ['key' => 'chk_experience', 'label' => 'Relevant work experience verified'],
                        ['key' => 'chk_skills', 'label' => 'Mandatory technical skills verified'],
                        ['key' => 'chk_salary', 'label' => 'Salary expectation aligned with budget'],
                        ['key' => 'chk_availability', 'label' => 'Start date and availability aligned'],
                        ['key' => 'chk_work_setup', 'label' => 'Location / shift schedule setup feasible'],
                        ['key' => 'chk_eligibility', 'label' => 'Philippine legal employment eligibility confirmed'],
                    ] as $chk)
                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #edf2f7;">
                            <span style="font-size: 13px; color: #1e293b;">{{ $chk['label'] }}</span>
                            <div style="display: flex; gap: 8px;">
                                <label style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer;">
                                    <input type="radio" name="{{ $chk['key'] }}" value="Pass" checked> <span style="color: #059669; font-weight: 600;">Pass</span>
                                </label>
                                <label style="display: inline-flex; align-items: center; gap: 4px; font-size: 12px; cursor: pointer;">
                                    <input type="radio" name="{{ $chk['key'] }}" value="Fail"> <span style="color: #dc2626; font-weight: 600;">Fail</span>
                                </label>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Recruiter Decision -->
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Recruiter Decision *</label>
                    <select name="decision" id="screeningDecisionSelect" class="hr-select" required onchange="handleScreeningDecisionChange(this.value)">
                        <option value="Shortlist">Shortlist (Proceed to Interview)</option>
                        <option value="Hold">Hold (Keep in candidate pool)</option>
                        <option value="Reject">Reject Candidate</option>
                    </select>
                </div>

                <!-- Standardized Rejection Reason -->
                <div class="hr-form-group" id="screeningRejectReasonWrap" style="display: none; margin-bottom: 12px;">
                    <label class="hr-form-label">Standardized Rejection Reason *</label>
                    <select name="rejection_reason" class="hr-select">
                        <option value="Lack of experience">Lack of experience</option>
                        <option value="Qualification mismatch">Qualification mismatch</option>
                        <option value="Salary mismatch">Salary mismatch</option>
                        <option value="Position filled">Position filled</option>
                        <option value="Failed screening">Failed screening</option>
                        <option value="Applicant withdrew">Applicant withdrew</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Screening Notes & Comments</label>
                    <textarea name="remarks" class="hr-textarea" rows="2" placeholder="Summary notes on candidate screening..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('screeningModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Save Screening Decision</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Schedule Interview -->
<div id="scheduleInterviewModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 640px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.08), rgba(168, 85, 247, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-calendar-plus" style="color: #4f46e5;"></i> Schedule Interview Evaluation</span>
            <button class="icon-btn" onclick="closeModal('scheduleInterviewModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.interviews.store') }}">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <input type="hidden" name="applicant_id" id="sched_applicant_id">
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Candidate</label>
                    <input type="text" id="sched_applicant_display" class="hr-input" readonly style="background: #f8fafc; font-weight: 400;">
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
                    <input type="text" name="location_or_link" class="hr-input" placeholder="e.g. Branch Conference Room / https://meet.google.com/xyz">
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Candidate Instructions</label>
                    <textarea name="instructions" class="hr-textarea" rows="2" placeholder="e.g. Please bring 1 valid ID and updated printed resume..."></textarea>
                </div>

                <input type="hidden" name="status" value="Scheduled">
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('scheduleInterviewModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-calendar-check"></i> Confirm Schedule</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Structured Interview Scorecard Evaluation (1-5 Ratings) -->
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
                    Candidate Evaluation: <span id="scorecardCandidateName" style="color: #9333ea;"></span>
                </div>

                <div class="hr-scorecard-grid">
                    <!-- Communication -->
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Communication (1-5)</span>
                        <select name="rating_communication" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <!-- Technical Skills -->
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Technical Skills (1-5)</span>
                        <select name="rating_technical" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <!-- Experience -->
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Relevant Experience (1-5)</span>
                        <select name="rating_experience" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <!-- Problem Solving -->
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Problem Solving (1-5)</span>
                        <select name="rating_problem_solving" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <!-- Team Fit -->
                    <div class="hr-rating-row">
                        <span style="font-size: 13px; font-weight: 600; color: #1e293b;">Team Culture Fit (1-5)</span>
                        <select name="rating_team_fit" class="hr-select" style="width: 80px;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>

                    <!-- Overall Rating -->
                    <div class="hr-rating-row" style="background: rgba(168, 85, 247, 0.06); border-color: rgba(168, 85, 247, 0.3);">
                        <span style="font-size: 13px; font-weight: 700; color: #9333ea;">Overall Score (1-5)</span>
                        <select name="rating_overall" class="hr-select" style="width: 80px; font-weight: 400; color: #9333ea;" required>
                            @for($i=5; $i>=1; $i--) <option value="{{ $i }}">{{ $i }} / 5</option> @endfor
                        </select>
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Interviewer Recommendation *</label>
                    <select name="recommendation" class="hr-select" required>
                        <option value="Proceed">Proceed to Next Stage / Final Review</option>
                        <option value="Hold">Hold / Keep on Reserve</option>
                        <option value="Reject">Reject Candidate</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Interviewer Feedback & Key Strengths/Gaps</label>
                    <textarea name="notes" class="hr-textarea" rows="3" placeholder="Provide qualitative assessment on communication, skills demonstration, and attitude..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('scorecardModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Submit Evaluation</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Record Assessment Result -->
<div id="assessmentModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 600px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(14, 165, 233, 0.08), rgba(99, 102, 241, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-clipboard-text" style="color: #0284c7;"></i> Record Assessment Result</span>
            <button class="icon-btn" onclick="closeModal('assessmentModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.assessments.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <input type="hidden" name="applicant_id" id="assess_applicant_id">
                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Candidate</label>
                    <input type="text" id="assess_applicant_display" class="hr-input" readonly style="background: #f8fafc; font-weight: 400;">
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Assessment Type *</label>
                        <select name="assessment_type" class="hr-select" required>
                            <option value="Technical Test">Technical Test</option>
                            <option value="Skills Test">Skills Test</option>
                            <option value="Personality Assessment">Personality Assessment</option>
                            <option value="Practical Test">Practical Test (Kitchen/Barista)</option>
                            <option value="Portfolio Review">Portfolio Review</option>
                            <option value="Typing Test">Typing Test</option>
                            <option value="Writing Test">Writing Test</option>
                            <option value="Custom Assessment">Custom Assessment</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Assessment Title *</label>
                        <input type="text" name="title" class="hr-input" required placeholder="e.g. Line Cooking & Prep Speed Test">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Candidate Score *</label>
                        <input type="number" step="0.01" name="score" class="hr-input" required placeholder="e.g. 88">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Passing Score *</label>
                        <input type="number" step="0.01" name="passing_score" class="hr-input" required value="75">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Result *</label>
                        <select name="result" class="hr-select" required>
                            <option value="Passed">Passed</option>
                            <option value="Failed">Failed</option>
                            <option value="Pending">Pending</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Date Completed *</label>
                        <input type="date" name="assessment_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Evaluator Name</label>
                    <input type="text" name="evaluator_name" class="hr-input" value="{{ Auth::user()?->full_name ?? Auth::user()?->name }}">
                </div>

                <div class="hr-form-group" style="margin-bottom: 12px;">
                    <label class="hr-form-label">Attachment / Rubric Sheet (PDF/Image)</label>
                    <input type="file" name="attachment" class="hr-input" accept=".pdf,.doc,.docx,.zip,.jpg,.png">
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Remarks & Score Notes</label>
                    <textarea name="remarks" class="hr-textarea" rows="2" placeholder="Specific test observations..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('assessmentModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Save Assessment</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Final Hiring Review -->
<div id="finalReviewModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 640px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(124, 58, 237, 0.08), rgba(236, 72, 153, 0.08));">
            <span class="hr-modal-title"><i class="ph ph-scales" style="color: #7c3aed;"></i> Final Hiring Review Decision</span>
            <button class="icon-btn" onclick="closeModal('finalReviewModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="finalReviewForm" method="POST">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 14px;">
                    Candidate: <span id="finalReviewCandidateName" style="color: #9333ea;"></span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Recruiter Recommendation</label>
                        <input type="text" name="recruiter_recommendation" class="hr-input" value="Highly Recommended">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Hiring Manager Endorsement</label>
                        <input type="text" name="hiring_manager_recommendation" class="hr-input" value="Approved for Hire">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Reference Check Status</label>
                        <input type="text" name="reference_results" class="hr-input" value="Clear & Verified">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Proposed Basic Salary (₱/mo) *</label>
                        <input type="number" step="0.01" name="proposed_salary" id="finalReviewProposedSalary" class="hr-input" required>
                    </div>
                </div>

                <div class="hr-form-group" style="margin-bottom: 14px;">
                    <label class="hr-form-label">Final Hiring Decision *</label>
                    <select name="decision" class="hr-select" required style="font-weight: 400;">
                        <option value="Hire" style="color: #059669; font-weight: 400;">Hire (Proceed Automatically to Job Offer)</option>
                        <option value="Hold" style="color: #d97706;">Hold (Wait for other candidates)</option>
                        <option value="Reject" style="color: #dc2626;">Reject Candidate</option>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Final Hiring Comments & Panel Remarks</label>
                    <textarea name="remarks" class="hr-textarea" rows="2" placeholder="Summary notes on hiring justification and salary benchmarking..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('finalReviewModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Submit Final Review</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Convert Applicant to Employee (Section 13) -->
<div id="convertEmployeeModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 720px;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.08), rgba(5, 150, 105, 0.12));">
            <span class="hr-modal-title" style="color: #065f46; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-user-check" style="color: #059669;"></i> Convert Candidate to Employee Record
            </span>
            <button class="icon-btn" onclick="closeModal('convertEmployeeModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="convertEmployeeForm" method="POST">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 14px; margin-bottom: 16px; display: flex; align-items: center; gap: 12px;">
                    <i class="ph ph-sparkle" style="font-size: 26px; color: #16a34a; flex-shrink: 0;"></i>
                    <div style="font-size: 13px; color: #14532d;">
                        <strong>Seamless 201 File Integration:</strong> Converting <span id="convertApplicantName" style="font-weight: 700;"></span> transfers all personal info, education, work history, and uploaded pre-employment documents directly into Employee Management without duplicate data entry.
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 12px;">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Auto-Generated Employee ID *</label>
                        <input type="text" name="employee_id" id="convert_employee_id" class="hr-input" required style="font-weight: 400; color: #059669; letter-spacing: 0.5px;">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Official Date Hired *</label>
                        <input type="date" name="date_hired" id="convert_date_hired" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch Assignment *</label>
                        <select name="branch_id" id="convert_branch_id" class="hr-select" required>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Department *</label>
                        <select name="department_id" id="convert_department_id" class="hr-select" required>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Position *</label>
                        <select name="position_id" id="convert_position_id" class="hr-select" required>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" id="convert_employment_type" class="hr-select" required>
                            <option value="Probationary">Probationary</option>
                            <option value="Regular">Regular</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Basic Salary (₱/mo) *</label>
                        <input type="number" step="0.01" name="basic_salary" id="convert_basic_salary" class="hr-input" required>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Monthly Allowances (₱)</label>
                        <input type="number" step="0.01" name="allowances" id="convert_allowances" class="hr-input" value="0.00">
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Type</label>
                        <select name="salary_type" class="hr-select" required>
                            <option value="Monthly">Monthly</option>
                            <option value="Daily">Daily</option>
                            <option value="Hourly">Hourly</option>
                        </select>
                    </div>

                    <div class="hr-form-group">
                        <label class="hr-form-label">Pay Frequency</label>
                        <select name="pay_frequency" class="hr-select" required>
                            <option value="Semi-Monthly">Semi-Monthly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>

                <input type="hidden" name="employment_status" value="Probationary">
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('convertEmployeeModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="ph ph-check-circle"></i> Complete Conversion</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Move Stage Directly -->
<div id="moveStageModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 480px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-arrow-circle-right"></i> Move Pipeline Stage</span>
            <button class="icon-btn" onclick="closeModal('moveStageModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="moveStageForm" method="POST">
            @csrf
            <div class="hr-modal-body" style="padding: 20px;">
                <div class="hr-form-group" style="margin-bottom: 14px;">
                    <label class="hr-form-label">Target Stage *</label>
                    <select name="status" id="moveStageSelect" class="hr-select" required>
                        <optgroup label="Active Pipeline">
                            <option value="New">New</option>
                            <option value="Screening">Screening</option>
                            <option value="Shortlisted">Shortlisted</option>
                            <option value="Interview">Interview</option>
                            <option value="Assessment">Assessment</option>
                            <option value="Final Review">Final Review</option>
                            <option value="Offer">Job Offer</option>
                            <option value="Pre-Employment">Pre-Employment</option>
                            <option value="Hired">Hired</option>
                        </optgroup>
                        <optgroup label="Terminal Statuses">
                            <option value="On Hold">On Hold</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Withdrawn">Withdrawn</option>
                            <option value="Offer Declined">Offer Declined</option>
                            <option value="No Show">No Show</option>
                        </optgroup>
                    </select>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Activity Log Remarks</label>
                    <textarea name="remarks" class="hr-textarea" rows="2" placeholder="Reason or context for moving stage..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('moveStageModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Update Stage</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    let currentDrawerApplicant = null;
    let currentApplicantId = null;

    function openModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.add('active');
    }

    function closeModal(modalId) {
        const m = document.getElementById(modalId);
        if (m) m.classList.remove('active');
    }

    function addExperienceRow() {
        const container = document.getElementById('experienceRowsContainer');
        const div = document.createElement('div');
        div.className = 'experience-row';
        div.style = 'background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px; position: relative;';
        div.innerHTML = `
            <button type="button" onclick="this.parentElement.remove()" style="position: absolute; right: 10px; top: 10px; border: none; background: transparent; color: #dc2626; cursor: pointer;"><i class="ph ph-trash"></i></button>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                <div class="hr-form-group">
                    <label class="hr-form-label">Company Name</label>
                    <input type="text" name="experience_companies[]" class="hr-input" placeholder="Company name">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Held</label>
                    <input type="text" name="experience_positions[]" class="hr-input" placeholder="Role title">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Start Date</label>
                    <input type="date" name="experience_start_dates[]" class="hr-input">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">End Date</label>
                    <input type="date" name="experience_end_dates[]" class="hr-input">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Reason for Leaving</label>
                    <input type="text" name="experience_reasons_leaving[]" class="hr-input" placeholder="e.g. Career growth">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Responsibilities Summary</label>
                    <input type="text" name="experience_responsibilities[]" class="hr-input" placeholder="Key accomplishments">
                </div>
            </div>
        `;
        container.appendChild(div);
    }

    function handleScreeningDecisionChange(val) {
        const wrap = document.getElementById('screeningRejectReasonWrap');
        if (val === 'Reject') {
            wrap.style.display = 'block';
        } else {
            wrap.style.display = 'none';
        }
    }

    function openScreeningModal(applicantId, applicantName) {
        document.getElementById('screeningApplicantName').innerText = applicantName;
        document.getElementById('screeningForm').action = '/hr/recruitment/applicants/' + applicantId + '/screening';
        openModal('screeningModal');
    }

    function openScheduleInterviewFor(applicantId, applicantName, position) {
        document.getElementById('sched_applicant_id').value = applicantId;
        document.getElementById('sched_applicant_display').value = applicantName + ' (' + position + ')';
        openModal('scheduleInterviewModal');
    }

    function openScorecardModal(interviewId, candidateName) {
        document.getElementById('scorecardCandidateName').innerText = candidateName;
        document.getElementById('scorecardForm').action = '/hr/recruitment/interviews/' + interviewId + '/evaluate';
        openModal('scorecardModal');
    }

    function openAssessmentModal(applicantId, candidateName) {
        document.getElementById('assess_applicant_id').value = applicantId;
        document.getElementById('assess_applicant_display').value = candidateName;
        openModal('assessmentModal');
    }

    function openFinalReviewModal(applicantId, candidateName, proposedSalary) {
        document.getElementById('finalReviewCandidateName').innerText = candidateName;
        document.getElementById('finalReviewProposedSalary').value = proposedSalary;
        document.getElementById('finalReviewForm').action = '/hr/recruitment/applicants/' + applicantId + '/final-review';
        openModal('finalReviewModal');
    }

    function openConvertModal(applicantId, candidateName, appliedPosition) {
        document.getElementById('convertApplicantName').innerText = candidateName;
        document.getElementById('convertEmployeeForm').action = '/hr/recruitment/applicants/' + applicantId + '/convert';

        // Auto generate next employee ID EMP-YYYY-5XX
        const year = new Date().getFullYear();
        document.getElementById('convert_employee_id').value = 'EMP-' + year + '-' + Math.floor(500 + Math.random() * 50);

        openModal('convertEmployeeModal');
    }

    function openStageModal(applicantId, currentStatus) {
        currentApplicantId = applicantId;
        document.getElementById('moveStageForm').action = '/hr/recruitment/applicants/' + applicantId + '/stage';
        document.getElementById('moveStageSelect').value = currentStatus;
        openModal('moveStageModal');
    }

    function openCurrentStageModal() {
        if (currentDrawerApplicant) {
            openStageModal(currentDrawerApplicant.applicant.id, currentDrawerApplicant.applicant.status);
        }
    }

    // ==========================================
    // APPLICANT DRAWER LOGIC
    // ==========================================

    function switchDrawerTab(tabName, btn) {
        document.querySelectorAll('.drawer-tab-content').forEach(el => el.style.display = 'none');
        document.querySelectorAll('.hr-micro-tab').forEach(b => b.classList.remove('active'));

        const target = document.getElementById('tab-' + tabName);
        if (target) {
            target.style.display = 'block';
        }

        if (btn) {
            btn.classList.add('active');
        } else {
            const matchedBtn = Array.from(document.querySelectorAll('.hr-micro-tab')).find(b => b.innerText.toLowerCase().includes(tabName.replace('-', ' ')));
            if (matchedBtn) matchedBtn.classList.add('active');
        }
    }

    function openApplicantDrawer(applicantId, initialTab = 'overview') {
        currentApplicantId = applicantId;
        const drawer = document.getElementById('applicantDrawer');
        const overlay = document.getElementById('applicantDrawerOverlay');

        drawer.classList.add('active');
        overlay.classList.add('active');

        // Fetch complete JSON data
        fetch('/hr/recruitment/applicants/' + applicantId + '/data')
            .then(res => res.json())
            .then(data => {
                currentDrawerApplicant = data;
                renderDrawerData(data);
                switchDrawerTab(initialTab);
            })
            .catch(err => {
                console.error(err);
            });
    }

    function closeApplicantDrawer() {
        document.getElementById('applicantDrawer').classList.remove('active');
        document.getElementById('applicantDrawerOverlay').classList.remove('active');
    }

    function renderDrawerData(data) {
        const app = data.applicant;

        // Header info
        document.getElementById('drawerFullName').innerText = data.full_name;
        document.getElementById('drawerAvatar').innerText = (app.first_name[0] || 'A') + (app.last_name[0] || 'A');
        document.getElementById('drawerAppliedPosition').innerText = app.applied_position;
        document.getElementById('drawerAppDate').innerText = 'Applied ' + (app.application_date ? app.application_date.substring(0, 10) : 'Recently');
        document.getElementById('drawerStatusBadge').innerText = app.status;
        document.getElementById('drawerFooterAppId').innerText = '#' + app.id;

        // Stepper Progress
        const steps = ['New', 'Screening', 'Shortlisted', 'Interview', 'Assessment', 'FinalReview', 'Offer', 'PreEmployment', 'Hired'];
        const stageIndex = {
            'New': 0, 'Screening': 1, 'Shortlisted': 2, 'Interview': 3,
            'Assessment': 4, 'Final Review': 5, 'Offer': 6, 'Pre-Employment': 7, 'Hired': 8
        };

        const currentIdx = stageIndex[app.status] !== undefined ? stageIndex[app.status] : 1;

        steps.forEach((step, idx) => {
            const el = document.getElementById('step-' + step);
            if (el) {
                el.classList.remove('active', 'completed');
                if (idx < currentIdx) el.classList.add('completed');
                else if (idx === currentIdx) el.classList.add('active');
            }
        });

        // Overview Bar
        const pct = data.pipeline_progress || 40;
        document.getElementById('drawerProgressBar').style.width = pct + '%';
        document.getElementById('drawerProgressText').innerText = pct + '% Pipeline Progress';

        // Contact info
        document.getElementById('drawerContact').innerText = app.contact_number || 'No contact number';
        document.getElementById('drawerEmail').innerText = app.email || 'No email provided';
        document.getElementById('drawerAddress').innerText = (app.city || app.province) ? (app.city + ', ' + app.province) : (app.address || 'Address not provided');

        // Snapshot
        document.getElementById('drawerSalary').innerText = app.desired_salary ? '₱' + Number(app.desired_salary).toLocaleString() + '/mo' : 'Negotiable';
        document.getElementById('drawerSetup').innerText = app.work_setup_preference || 'On-site';
        document.getElementById('drawerSource').innerText = app.source;

        // Meters
        const reqs = data.requirements_stats;
        document.getElementById('drawerReqMeter').innerText = reqs.completed + ' / ' + reqs.total;
        document.getElementById('drawerReqCounterBadge').innerText = reqs.completed + ' / ' + reqs.total + ' Completed';

        const pbs = data.preboarding_stats;
        document.getElementById('drawerTaskMeter').innerText = pbs.completed + ' / ' + pbs.total;
        document.getElementById('drawerTaskCounterBadge').innerText = pbs.completed + ' / ' + pbs.total + ' Completed';

        // Convert Button configuration
        const convBtn = document.getElementById('drawerConvertBtn');
        if (app.status === 'Hired' && app.hired_as_employee_id) {
            convBtn.innerText = 'View in Employee Directory';
            convBtn.onclick = function() {
                window.location.href = '/hr/people/employees/' + app.hired_as_employee_id;
            };
        } else {
            convBtn.innerHTML = '<i class="ph ph-user-check"></i> Convert to Employee';
            convBtn.onclick = function() {
                openConvertModal(app.id, data.full_name, app.applied_position);
            };
        }

        // Render Recent Activity (Overview)
        const recentList = document.getElementById('drawerRecentActivityList');
        recentList.innerHTML = '';
        const history = (data.activity_history || []).slice(-3).reverse();
        if (history.length === 0) {
            recentList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No activity logged yet.</div>';
        } else {
            history.forEach(item => {
                recentList.innerHTML += `
                    <div style="padding: 6px 0; border-bottom: 1px solid #f1f5f9; font-size: 12.5px;">
                        <strong style="color: #0f172a;">${item.action}</strong>
                        <div style="color: #64748b; font-size: 12px;">${item.details || ''}</div>
                        <small style="color: #94a3b8;">${item.formatted_time || ''} by ${item.performed_by || 'HR'}</small>
                    </div>
                `;
            });
        }

        // Render Full Activity
        const fullStream = document.getElementById('drawerFullActivityStream');
        fullStream.innerHTML = '';
        (data.activity_history || []).slice().reverse().forEach(item => {
            fullStream.innerHTML += `
                <div class="hr-timeline-item">
                    <div class="hr-timeline-dot"></div>
                    <div class="hr-timeline-title">${item.action}</div>
                    <div class="hr-timeline-meta">${item.formatted_time || ''} • By <strong>${item.performed_by || 'System'}</strong></div>
                    <div class="hr-timeline-desc">${item.details || ''}</div>
                </div>
            `;
        });

        // Render Application Answers
        const answersList = document.getElementById('drawerAppAnswersList');
        answersList.innerHTML = '';
        const ans = app.application_answers || {};
        answersList.innerHTML = `
            <div style="margin-bottom: 14px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Why do you want to join our company?</div>
                <div style="font-size: 13.5px; color: #1e293b; margin-top: 4px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    ${ans.why_join || 'I am eager to contribute my culinary expertise and dedication to exceptional customer service in a high-standard restaurant setting.'}
                </div>
            </div>
            <div style="margin-bottom: 14px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Relevant Experience Highlights</div>
                <div style="font-size: 13.5px; color: #1e293b; margin-top: 4px; background: #f8fafc; padding: 10px; border-radius: 8px;">
                    ${ans.relevant_experience || '3+ years experience on hot kitchen line, station prep, grill, and inventory tracking with strict food hygiene.'}
                </div>
            </div>
        `;

        // Render Experience Tab
        const expList = document.getElementById('drawerWorkHistoryList');
        expList.innerHTML = '';
        if (app.work_experience && app.work_experience.length > 0) {
            app.work_experience.forEach(w => {
                expList.innerHTML += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">${w.position || 'Staff'} at ${w.company || ''}</div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">${w.start_date || ''} to ${w.end_date || 'Present'}</div>
                        ${w.responsibilities ? `<div style="font-size: 12.5px; color: #334155; margin-top: 6px;">${w.responsibilities}</div>` : ''}
                    </div>
                `;
            });
        } else {
            expList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No previous work history registered.</div>';
        }

        // Render Education
        const eduList = document.getElementById('drawerEducationList');
        eduList.innerHTML = '';
        if (app.education && app.education.length > 0) {
            app.education.forEach(e => {
                eduList.innerHTML += `
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 8px;">
                        <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">${e.degree || 'Degree'} ${e.course ? '- ' + e.course : ''}</div>
                        <div style="font-size: 12px; color: #64748b;">${e.school || 'University'} (Graduated ${e.year_graduated || 'N/A'})</div>
                    </div>
                `;
            });
        } else {
            eduList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px;">No tertiary education registered.</div>';
        }

        // Render Interviews List
        const intList = document.getElementById('drawerInterviewsList');
        intList.innerHTML = '';
        document.getElementById('drawerScheduleInterviewBtn').onclick = function() {
            openScheduleInterviewFor(app.id, data.full_name, app.applied_position);
        };

        if (data.interviews && data.interviews.length > 0) {
            data.interviews.forEach(int => {
                intList.innerHTML += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #0f172a; font-size: 14px;">${int.interview_stage} (${int.interview_type})</strong>
                            <span class="hr-badge ${int.status === 'Completed' ? 'hr-badge-success' : 'hr-badge-warning'}">${int.status}</span>
                        </div>
                        <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                            <i class="ph ph-calendar"></i> ${int.interview_date ? int.interview_date.substring(0, 10) : ''} ${int.interview_time || ''} • Interviewer: <strong>${int.interviewer_name || 'HR'}</strong>
                        </div>
                        ${int.rating ? `
                            <div style="margin-top: 8px; padding: 8px 12px; background: #fffbeb; border-radius: 8px; display: flex; align-items: center; justify-content: space-between;">
                                <span style="font-weight: 700; color: #b45309; font-size: 13px;">Overall Rating: ★ ${int.rating}/5</span>
                                <span class="hr-badge hr-badge-neutral">${int.recommendation || 'Proceed'}</span>
                            </div>
                        ` : `
                            <div style="margin-top: 8px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openScorecardModal(${int.id}, '${data.full_name.replace(/'/g, "\\'")}')">
                                    <i class="ph ph-star"></i> Fill Scorecard
                                </button>
                            </div>
                        `}
                    </div>
                `;
            });
        } else {
            intList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px; text-align: center; padding: 24px;">No interviews scheduled yet.</div>';
        }

        // Render Assessments List
        const assList = document.getElementById('drawerAssessmentsList');
        assList.innerHTML = '';
        document.getElementById('drawerAddAssessmentBtn').onclick = function() {
            openAssessmentModal(app.id, data.full_name);
        };

        if (data.assessments && data.assessments.length > 0) {
            data.assessments.forEach(ass => {
                assList.innerHTML += `
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px; margin-bottom: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <strong style="color: #0f172a; font-size: 14px;">${ass.title}</strong>
                            <span class="hr-badge ${ass.result === 'Passed' ? 'hr-badge-success' : 'hr-badge-danger'}">${ass.result}</span>
                        </div>
                        <div style="font-size: 12.5px; color: #64748b; margin-top: 4px;">
                            Score: <strong>${ass.score} / ${ass.passing_score}</strong> (Passing: ${ass.passing_score}) • Evaluator: ${ass.evaluator_name || 'Lead Trainer'}
                        </div>
                    </div>
                `;
            });
        } else {
            assList.innerHTML = '<div style="color: #94a3b8; font-size: 12.5px; text-align: center; padding: 24px;">No assessments recorded yet.</div>';
        }

        // Render Screening Tab Content
        const scContent = document.getElementById('drawerScreeningContent');
        scContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <span class="hr-badge ${app.screening_data?.decision === 'Shortlist' ? 'hr-badge-success' : 'hr-badge-neutral'}">
                    Decision: ${app.screening_data?.decision || 'Pending Review'}
                </span>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openScreeningModal(${app.id}, '${data.full_name.replace(/'/g, "\\'")}')">
                    <i class="ph ph-pencil"></i> Update Screening
                </button>
            </div>
            <div style="font-size: 13px; color: #334155; line-height: 1.6;">
                <strong>Notes:</strong> ${app.screening_data?.remarks || 'Candidate meets general education and experience background criteria.'}
            </div>
        `;

        // Render Final Review Content
        const frContent = document.getElementById('drawerFinalReviewContent');
        frContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                <span class="hr-badge ${app.final_review_data?.decision === 'Hire' ? 'hr-badge-success' : 'hr-badge-neutral'}">
                    Decision: ${app.final_review_data?.decision || 'Awaiting Final Review'}
                </span>
                <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openFinalReviewModal(${app.id}, '${data.full_name.replace(/'/g, "\\'")}', ${app.desired_salary || 25000})">
                    <i class="ph ph-scales"></i> Perform Final Review
                </button>
            </div>
            <div class="hr-offer-details-grid" style="margin-top: 10px;">
                <div><small style="color: #64748b;">Recruiter Endorsement:</small><br><strong>${app.final_review_data?.recruiter_recommendation || 'Recommended'}</strong></div>
                <div><small style="color: #64748b;">Hiring Manager:</small><br><strong>${app.final_review_data?.hiring_manager_recommendation || 'Endorsed for hire'}</strong></div>
                <div><small style="color: #64748b;">Proposed Compensation:</small><br><strong>₱${Number(app.final_review_data?.proposed_salary || app.desired_salary || 25000).toLocaleString()} /mo</strong></div>
                <div><small style="color: #64748b;">Reference Checks:</small><br><strong>${app.final_review_data?.reference_results || 'Clear & Verified'}</strong></div>
            </div>
        `;

        // Render Job Offer Preview
        const offContent = document.getElementById('drawerOfferContent');
        const off = app.offer_data || {};
        offContent.innerHTML = `
            <div class="hr-offer-paper">
                <div class="hr-offer-header">
                    <div>
                        <div class="hr-offer-title">FORMAL EMPLOYMENT OFFER LETTER</div>
                        <small style="color: #64748b;">Restaurant Operations & Hospitality Group</small>
                    </div>
                    <span class="hr-badge ${off.offer_status === 'Accepted' ? 'hr-badge-success' : 'hr-badge-purple'}" style="font-size: 13px; font-weight: 700;">
                        Status: ${off.offer_status || 'Draft'}
                    </span>
                </div>
                <p>Dear <strong>${data.full_name}</strong>,</p>
                <p>We are pleased to extend an offer of employment with our restaurant organization for the position of <strong>${off.position || app.applied_position}</strong>.</p>
                
                <div class="hr-offer-details-grid">
                    <div><small style="color: #64748b;">Position:</small><br><strong>${off.position || app.applied_position}</strong></div>
                    <div><small style="color: #64748b;">Employment Type:</small><br><strong>${off.employment_type || 'Regular'}</strong></div>
                    <div><small style="color: #64748b;">Target Start Date:</small><br><strong>${off.start_date || 'October 05, 2026'}</strong></div>
                    <div><small style="color: #64748b;">Probationary Period:</small><br><strong>${off.probation_period || '6 Months'}</strong></div>
                    <div><small style="color: #64748b;">Basic Monthly Salary:</small><br><strong style="color: #059669; font-size: 15px;">₱${Number(off.basic_salary || 25000).toLocaleString()}</strong></div>
                    <div><small style="color: #64748b;">Allowances & Perks:</small><br><strong>₱${Number(off.allowances || 2000).toLocaleString()} /mo</strong></div>
                </div>

                <div style="display: flex; gap: 8px; margin-top: 18px; justify-content: flex-end;">
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/offer-status" style="display: inline;">
                        @csrf
                        <input type="hidden" name="action" value="send">
                        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm"><i class="ph ph-paper-plane-tilt"></i> Send Offer</button>
                    </form>
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/offer-status" style="display: inline;">
                        @csrf
                        <input type="hidden" name="action" value="accept">
                        <button type="submit" class="hr-btn hr-btn-success hr-btn-sm"><i class="ph ph-check"></i> Record Acceptance</button>
                    </form>
                </div>
            </div>
        `;

        // Render Pre-Employment Requirements
        const reqList = document.getElementById('drawerRequirementsList');
        reqList.innerHTML = '';
        (data.pre_employment_requirements || []).forEach(req => {
            reqList.innerHTML += `
                <div class="hr-checklist-item">
                    <div class="hr-checklist-info">
                        <div class="hr-checklist-icon" style="background: ${req.status === 'Approved' ? 'rgba(16,185,129,0.15)' : 'rgba(148,163,184,0.15)'}; color: ${req.status === 'Approved' ? '#059669' : '#64748b'};">
                            <i class="ph ${req.status === 'Approved' ? 'ph-check-circle' : 'ph-file-text'}"></i>
                        </div>
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px;">${req.name}</strong>
                            <div style="font-size: 11.5px; color: #64748b;">${req.description || ''}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="hr-badge ${req.status === 'Approved' ? 'hr-badge-success' : (req.status === 'Submitted' ? 'hr-badge-info' : 'hr-badge-neutral')}">${req.status}</span>
                        <form method="POST" action="/hr/recruitment/applicants/${app.id}/requirements" style="display: inline;">
                            @csrf
                            <input type="hidden" name="req_id" value="${req.id}">
                            <input type="hidden" name="status" value="${req.status === 'Approved' ? 'Pending' : 'Approved'}">
                            <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="Toggle Approve">
                                <i class="ph ${req.status === 'Approved' ? 'ph-arrow-counter-clockwise' : 'ph-check'}"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `;
        });

        // Render Preboarding Tasks
        const pbList = document.getElementById('drawerPreboardingList');
        pbList.innerHTML = '';
        (data.preboarding_tasks || []).forEach(t => {
            const rawDept = (t.department || 'HR').trim();
            const deptUpper = rawDept.toUpperCase();
            
            let pillClass = 'hr-dept-pill-hr';
            let pillIcon = 'ph-users';
            
            if (deptUpper === 'IT') {
                pillClass = 'hr-dept-pill-it';
                pillIcon = 'ph-cpu';
            } else if (deptUpper === 'FINANCE' || deptUpper === 'FIN') {
                pillClass = 'hr-dept-pill-finance';
                pillIcon = 'ph-coins';
            } else if (deptUpper === 'MANAGEMENT' || deptUpper === 'ADMIN') {
                pillClass = 'hr-dept-pill-mgmt';
                pillIcon = 'ph-briefcase';
            } else if (deptUpper === 'FOH' || deptUpper === 'FRONT OF HOUSE') {
                pillClass = 'hr-dept-pill-foh';
                pillIcon = 'ph-storefront';
            } else if (deptUpper === 'BOH' || deptUpper === 'BACK OF HOUSE' || deptUpper === 'KITCHEN') {
                pillClass = 'hr-dept-pill-boh';
                pillIcon = 'ph-cooking-pot';
            }

            const isDone = t.status === 'Completed';

            pbList.innerHTML += `
                <div class="hr-checklist-item">
                    <div class="hr-checklist-info">
                        <span class="hr-dept-pill ${pillClass}" title="Department: ${rawDept}">
                            <i class="ph ${pillIcon}"></i>
                            <span>${rawDept}</span>
                        </span>
                        <div>
                            <strong style="color: #0f172a; font-size: 13.5px; display: block; line-height: 1.3;">${t.task_name}</strong>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">${t.description}</div>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span class="hr-badge ${isDone ? 'hr-badge-success' : 'hr-badge-warning'}">
                            <i class="ph ${isDone ? 'ph-check-circle' : 'ph-clock'}"></i>
                            ${t.status}
                        </span>
                        <form method="POST" action="/hr/recruitment/applicants/${app.id}/preboarding" style="display: inline;">
                            @csrf
                            <input type="hidden" name="task_id" value="${t.id}">
                            <input type="hidden" name="status" value="${isDone ? 'Pending' : 'Completed'}">
                            <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="${isDone ? 'Mark as Pending' : 'Mark as Completed'}">
                                <i class="ph ${isDone ? 'ph-arrow-counter-clockwise' : 'ph-check'}"></i>
                            </button>
                        </form>
                    </div>
                </div>
            `;
        });

        // Render Onboarding Tab
        const onbContent = document.getElementById('drawerOnboardingContent');
        const onb = app.onboarding_data || {};
        onbContent.innerHTML = `
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h3 style="font-size: 16px; font-weight: 800; color: #0f172a; margin: 0;">Employee Onboarding Tracking</h3>
                        <small style="color: #64748b;">First-Day Checklist, IT Provisioning, and Food Safety Compliance</small>
                    </div>
                    <span class="hr-badge hr-badge-success" style="font-size: 14px; font-weight: 800; padding: 6px 14px;">
                        ${onb.progress || 65}% Complete
                    </span>
                </div>
                <div style="width: 100%; height: 8px; background: #f1f5f9; border-radius: 4px; overflow: hidden; margin: 14px 0;">
                    <div style="height: 100%; width: ${onb.progress || 65}%; background: linear-gradient(90deg, #10b981, #059669); border-radius: 4px;"></div>
                </div>

                <div class="hr-offer-details-grid" style="margin-top: 14px;">
                    <div><small style="color: #64748b;">Employee Name:</small><br><strong>${data.full_name}</strong></div>
                    <div><small style="color: #64748b;">Designated Role:</small><br><strong>${app.applied_position}</strong></div>
                    <div><small style="color: #64748b;">Assigned Branch:</small><br><strong>${app.jobVacancy?.branch?.name || 'Main Branch'}</strong></div>
                    <div><small style="color: #64748b;">Start Date:</small><br><strong>${onb.start_date || 'October 05, 2026'}</strong></div>
                </div>

                <div style="display: flex; justify-content: flex-end; margin-top: 14px;">
                    <form method="POST" action="/hr/recruitment/applicants/${app.id}/onboarding" style="display: inline;">
                        @csrf
                        <input type="hidden" name="complete_onboarding" value="1">
                        <button type="submit" class="hr-btn hr-btn-primary" style="background: linear-gradient(135deg, #10b981, #047857);">
                            <i class="ph ph-seal-check"></i> Complete Onboarding (100%) & Activate Across HRIS
                        </button>
                    </form>
                </div>
            </div>
        `;
    }
</script>
@endpush

@endsection

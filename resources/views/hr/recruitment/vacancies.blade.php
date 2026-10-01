@extends('layouts.app')

@section('title', 'Job Vacancies - Talent Acquisition')

@section('content')
<x-hr-tabs parent="talent-acquisition">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addVacancyModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Post New Vacancy</span>
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

<!-- 6 Vacancy Statistics Cards -->
<div class="hr-emp-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 8px; margin-bottom: 8px;">
    <!-- Open Positions -->
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-briefcase"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Open Positions</span>
            <div class="hr-stat-value">{{ $stats['open_positions'] }}</div>
            <span class="hr-stat-sub">Active Openings</span>
        </div>
    </div>

    <!-- Total Applicants -->
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-users"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Applicants</span>
            <div class="hr-stat-value">{{ $stats['total_applicants'] }}</div>
            <span class="hr-stat-sub">Talent Pipeline</span>
        </div>
    </div>

    <!-- Shortlisted -->
    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-funnel"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Shortlisted</span>
            <div class="hr-stat-value">{{ $stats['shortlisted'] }}</div>
            <span class="hr-stat-sub">Qualified Candidates</span>
        </div>
    </div>

    <!-- Interviews -->
    <div class="hr-stat-card hr-stat-card-amber">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-chat-circle-dots"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Interviews</span>
            <div class="hr-stat-value">{{ $stats['interviews'] }}</div>
            <span class="hr-stat-sub">In Evaluation</span>
        </div>
    </div>

    <!-- Offers -->
    <div class="hr-stat-card hr-stat-card-rose">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-file-text"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Job Offers</span>
            <div class="hr-stat-value">{{ $stats['offers'] }}</div>
            <span class="hr-stat-sub">Pending / Accepted</span>
        </div>
    </div>

    <!-- Hired -->
    <div class="hr-stat-card hr-stat-card-emerald">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-user-check"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Hired Staff</span>
            <div class="hr-stat-value">{{ $stats['hired'] }}</div>
            <span class="hr-stat-sub">Converted to 201</span>
        </div>
    </div>
</div>

<!-- Search & Filter Bar -->
<div class="hr-filter-bar" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.03);">
    <form method="GET" action="{{ route('hr.recruitment.vacancies') }}" class="hr-filter-form" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 220px; position: relative;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></i>
            <input type="text" name="search" class="hr-input" placeholder="Search vacancy title, description..." value="{{ request('search') }}" style="padding-left: 36px; width: 100%;">
        </div>

        <select name="status" class="hr-select" style="min-width: 140px;">
            <option value="">All Statuses</option>
            @foreach(['Open', 'Draft', 'On Hold', 'Closed', 'Filled'] as $st)
                <option value="{{ $st }}" {{ request('status') === $st ? 'selected' : '' }}>{{ $st }}</option>
            @endforeach
        </select>

        <select name="branch_id" class="hr-select" style="min-width: 160px;">
            <option value="">All Branches</option>
            @foreach($branches as $b)
                <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
            @endforeach
        </select>

        <select name="department_id" class="hr-select" style="min-width: 160px;">
            <option value="">All Departments</option>
            @foreach($departments as $d)
                <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-funnel"></i>
            <span>Filter</span>
        </button>

        @if(request()->anyFilled(['search', 'status', 'branch_id', 'department_id']))
            <a href="{{ route('hr.recruitment.vacancies') }}" class="hr-btn hr-btn-ghost" title="Clear Filters">
                <i class="ph ph-x-circle"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>
</div>

<!-- Vacancies Table Card -->
<div class="hr-table-card" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
    <div class="hr-table-wrapper" style="overflow-x: auto;">
        <table class="hr-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; text-align: left;">
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Job Vacancy</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Organization & Setup</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Openings & Type</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Salary Range</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Recruiter / Manager</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Pipeline Stats</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase;">Status</th>
                    <th style="padding: 14px 18px; font-size: 12px; font-weight: 600; color: #475569; text-transform: uppercase; text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vacancies as $v)
                    <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s ease;">
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #0f172a; font-size: 14px;">{{ $v->title }}</div>
                            <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                Posted {{ $v->opening_date?->format('M d, Y') }}
                                @if($v->closing_date)
                                    • Deadline: {{ $v->closing_date->format('M d, Y') }}
                                @endif
                            </div>
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #1e293b; font-size: 13px;">{{ $v->branch?->name ?? 'All Branches' }}</div>
                            <div style="font-size: 12px; color: #64748b;">{{ $v->department?->name ?? 'General Operations' }}</div>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 10.5px; padding: 2px 8px; margin-top: 4px; display: inline-block;">
                                <i class="ph ph-buildings"></i> {{ $v->work_setup ?? 'On-site' }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-weight: 600; color: #9333ea; font-size: 13px;">
                                <i class="ph ph-users"></i> {{ $v->number_of_openings }} {{ Str::plural('Opening', $v->number_of_openings) }}
                            </div>
                            <span class="hr-badge hr-badge-neutral" style="font-size: 11px; margin-top: 4px; display: inline-block;">
                                {{ $v->employment_type }}
                            </span>
                        </td>
                        <td style="padding: 14px 18px;">
                            @if($v->salary_range_min || $v->salary_range_max)
                                <div style="font-weight: 600; color: #0f172a; font-size: 13px;">
                                    ₱{{ number_format($v->salary_range_min, 0) }} - ₱{{ number_format($v->salary_range_max, 0) }}
                                </div>
                                <small style="color: #94a3b8;">Monthly</small>
                            @else
                                <span style="color: #94a3b8; font-size: 12px;">Negotiable</span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <div style="font-size: 12.5px; color: #1e293b;">
                                <i class="ph ph-user" style="color: #9333ea;"></i> {{ $v->recruiter_name ?: ($v->recruiter?->full_name ?? 'HR Recruiter') }}
                            </div>
                            @if($v->hiring_manager_name || $v->hiringManager)
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                    Mgr: {{ $v->hiring_manager_name ?: $v->hiringManager?->full_name }}
                                </div>
                            @endif
                        </td>
                        <td style="padding: 14px 18px;">
                            <a href="{{ route('hr.recruitment.applicants', ['search' => $v->title]) }}" class="hr-badge hr-badge-purple" style="text-decoration: none; display: inline-flex; align-items: center; gap: 4px; padding: 4px 10px; font-weight: 600;">
                                <i class="ph ph-user-focus"></i> {{ $v->applicants_count }} Applicants
                            </a>
                        </td>
                        <td style="padding: 14px 18px;">
                            @if($v->status === 'Open')
                                <span class="hr-badge hr-badge-success" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-check-circle"></i> Open
                                </span>
                            @elseif($v->status === 'Draft')
                                <span class="hr-badge hr-badge-neutral" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-pencil-simple"></i> Draft
                                </span>
                            @elseif($v->status === 'On Hold')
                                <span class="hr-badge hr-badge-warning" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-pause-circle"></i> On Hold
                                </span>
                            @elseif($v->status === 'Closed')
                                <span class="hr-badge hr-badge-danger" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-x-circle"></i> Closed
                                </span>
                            @elseif($v->status === 'Filled')
                                <span class="hr-badge hr-badge-info" style="display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="ph ph-user-check"></i> Filled
                                </span>
                            @endif
                        </td>
                        <td style="padding: 14px 18px; text-align: right;">
                            <div style="display: inline-flex; gap: 6px; align-items: center;">
                                <!-- View Applicants -->
                                <a href="{{ route('hr.recruitment.applicants', ['search' => $v->title]) }}" class="hr-btn hr-btn-secondary hr-btn-sm" title="View Applicants">
                                    <i class="ph ph-users-three"></i>
                                </a>

                                <!-- Edit -->
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" onclick="openEditVacancyModal({{ json_encode($v) }})" title="Edit Vacancy">
                                    <i class="ph ph-pencil"></i>
                                </button>

                                <!-- State Toggle / Dropdown -->
                                @if($v->status === 'Open')
                                    <form method="POST" action="{{ route('hr.recruitment.vacancies.unpublish', $v->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="Unpublish / Draft">
                                            <i class="ph ph-eye-slash"></i>
                                        </button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('hr.recruitment.vacancies.publish', $v->id) }}" style="display: inline;">
                                        @csrf
                                        <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" style="color: #059669;" title="Publish Vacancy">
                                            <i class="ph ph-paper-plane-tilt"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Duplicate -->
                                <form method="POST" action="{{ route('hr.recruitment.vacancies.duplicate', $v->id) }}" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="hr-btn hr-btn-secondary hr-btn-sm" title="Duplicate Vacancy">
                                        <i class="ph ph-copy"></i>
                                    </button>
                                </form>

                                @if($v->status !== 'Closed')
                                    <form method="POST" action="{{ route('hr.recruitment.vacancies.close', $v->id) }}" style="display: inline;" onsubmit="return confirm('Close this vacancy?');">
                                        @csrf
                                        <button type="submit" class="hr-btn hr-btn-ghost hr-btn-sm" style="color: #dc2626;" title="Close Vacancy">
                                            <i class="ph ph-lock-key"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px;">
                            <i class="ph ph-briefcase" style="font-size: 36px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                            No job vacancies found matching current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($vacancies->hasPages())
        <div style="padding: 14px 20px; border-top: 1px solid #e2e8f0; background: #ffffff;">
            {{ $vacancies->links() }}
        </div>
    @endif
</div>

<!-- Modal: Post New Vacancy -->
<div id="addVacancyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 820px; width: 95%;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.06), rgba(168, 85, 247, 0.12));">
            <span class="hr-modal-title" style="color: #0f172a; font-weight: 650; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-briefcase" style="color: #9333ea;"></i> Post New Job Vacancy
            </span>
            <button class="icon-btn" onclick="closeModal('addVacancyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.vacancies.store') }}">
            @csrf
            <div class="hr-modal-body" style="max-height: 75vh; overflow-y: auto; padding: 24px;">
                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <!-- Job Title -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Job Title *</label>
                        <input type="text" name="title" class="hr-input" required placeholder="e.g. Executive Sous Chef, Line Cook, Senior Barista">
                    </div>

                    <!-- Department -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Department</label>
                        <select name="department_id" class="hr-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Position -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Position Classification</label>
                        <select name="position_id" class="hr-select">
                            <option value="">Select Position</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employment Type -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Intern">Intern</option>
                        </select>
                    </div>

                    <!-- Work Setup -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Setup *</label>
                        <select name="work_setup" class="hr-select" required>
                            <option value="On-site">On-site</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>

                    <!-- Location / Branch -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Location / Branch</label>
                        <select name="branch_id" class="hr-select">
                            <option value="">All Branches / Main HQ</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Number of Openings -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Number of Openings *</label>
                        <input type="number" name="number_of_openings" class="hr-input" min="1" value="1" required>
                    </div>

                    <!-- Salary Range Min -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Range Min (₱)</label>
                        <input type="number" step="0.01" name="salary_range_min" class="hr-input" placeholder="e.g. 20000">
                    </div>

                    <!-- Salary Range Max -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Range Max (₱)</label>
                        <input type="number" step="0.01" name="salary_range_max" class="hr-input" placeholder="e.g. 30000">
                    </div>

                    <!-- Opening Date -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Opening Date *</label>
                        <input type="date" name="opening_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>

                    <!-- Application Deadline -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Application Deadline</label>
                        <input type="date" name="closing_date" class="hr-input" value="{{ date('Y-m-d', strtotime('+30 days')) }}">
                    </div>

                    <!-- Recruiter -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Assigned Recruiter</label>
                        <select name="recruiter_id" class="hr-select">
                            <option value="">Select Recruiter</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ Auth::id() == $u->id ? 'selected' : '' }}>{{ $u->full_name ?? $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Hiring Manager -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Hiring Manager</label>
                        <select name="hiring_manager_id" class="hr-select">
                            <option value="">Select Hiring Manager</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->full_name ?? $u->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Vacancy Status -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Initial Vacancy Status *</label>
                        <select name="status" class="hr-select" required>
                            <option value="Open">Open (Immediately Published)</option>
                            <option value="Draft">Draft</option>
                            <option value="On Hold">On Hold</option>
                        </select>
                    </div>

                    <!-- Job Description -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Job Description</label>
                        <textarea name="job_description" class="hr-textarea" rows="3" placeholder="Provide a summary of the role, daily work environment, and core mandate..."></textarea>
                    </div>

                    <!-- Responsibilities -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Key Responsibilities</label>
                        <textarea name="responsibilities" class="hr-textarea" rows="3" placeholder="List primary duties, prep work, customer service standards, shift expectations..."></textarea>
                    </div>

                    <!-- Qualifications -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Qualifications</label>
                        <textarea name="qualifications" class="hr-textarea" rows="3" placeholder="Educational attainment, industry experience, certifications (e.g. Food Safety, TESDA NC-II)..."></textarea>
                    </div>

                    <!-- Required Skills -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Required Skills</label>
                        <input type="text" name="required_skills" class="hr-input" placeholder="e.g. Knife Skills, Line Cooking, POS Operation">
                    </div>

                    <!-- Preferred Skills -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Preferred Skills</label>
                        <input type="text" name="preferred_skills" class="hr-input" placeholder="e.g. Menu Development, Inventory Counting">
                    </div>

                    <!-- Benefits -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Benefits & Perks</label>
                        <input type="text" name="benefits" class="hr-input" placeholder="e.g. SSS, PhilHealth, Pag-IBIG, Free Staff Meals, 13th Month, Uniform Allowance">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addVacancyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Post Vacancy</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Vacancy -->
<div id="editVacancyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 820px; width: 95%;">
        <div class="hr-modal-header" style="background: linear-gradient(135deg, rgba(236, 72, 153, 0.06), rgba(168, 85, 247, 0.12));">
            <span class="hr-modal-title" style="color: #0f172a; font-weight: 650; display: flex; align-items: center; gap: 8px;">
                <i class="ph ph-pencil-simple" style="color: #9333ea;"></i> Edit Job Vacancy
            </span>
            <button class="icon-btn" onclick="closeModal('editVacancyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="editVacancyForm" method="POST">
            @csrf
            @method('PUT')
            <div class="hr-modal-body" style="max-height: 75vh; overflow-y: auto; padding: 24px;">
                <div class="hr-form-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px;">
                    <!-- Job Title -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Job Title *</label>
                        <input type="text" name="title" id="edit_title" class="hr-input" required>
                    </div>

                    <!-- Department -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Department</label>
                        <select name="department_id" id="edit_department_id" class="hr-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Position -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Position Classification</label>
                        <select name="position_id" id="edit_position_id" class="hr-select">
                            <option value="">Select Position</option>
                            @foreach($positions as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Employment Type -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" id="edit_employment_type" class="hr-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Contractual">Contractual</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Intern">Intern</option>
                        </select>
                    </div>

                    <!-- Work Setup -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Work Setup *</label>
                        <select name="work_setup" id="edit_work_setup" class="hr-select" required>
                            <option value="On-site">On-site</option>
                            <option value="Hybrid">Hybrid</option>
                            <option value="Remote">Remote</option>
                        </select>
                    </div>

                    <!-- Location / Branch -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Location / Branch</label>
                        <select name="branch_id" id="edit_branch_id" class="hr-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Number of Openings -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Number of Openings *</label>
                        <input type="number" name="number_of_openings" id="edit_number_of_openings" class="hr-input" min="1" required>
                    </div>

                    <!-- Salary Range Min -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Range Min (₱)</label>
                        <input type="number" step="0.01" name="salary_range_min" id="edit_salary_range_min" class="hr-input">
                    </div>

                    <!-- Salary Range Max -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Range Max (₱)</label>
                        <input type="number" step="0.01" name="salary_range_max" id="edit_salary_range_max" class="hr-input">
                    </div>

                    <!-- Opening Date -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Opening Date *</label>
                        <input type="date" name="opening_date" id="edit_opening_date" class="hr-input" required>
                    </div>

                    <!-- Application Deadline -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Application Deadline</label>
                        <input type="date" name="closing_date" id="edit_closing_date" class="hr-input">
                    </div>

                    <!-- Status -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Vacancy Status *</label>
                        <select name="status" id="edit_status" class="hr-select" required>
                            <option value="Open">Open</option>
                            <option value="Draft">Draft</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Closed">Closed</option>
                            <option value="Filled">Filled</option>
                        </select>
                    </div>

                    <!-- Job Description -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Job Description</label>
                        <textarea name="job_description" id="edit_job_description" class="hr-textarea" rows="3"></textarea>
                    </div>

                    <!-- Responsibilities -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Responsibilities</label>
                        <textarea name="responsibilities" id="edit_responsibilities" class="hr-textarea" rows="3"></textarea>
                    </div>

                    <!-- Qualifications -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Qualifications</label>
                        <textarea name="qualifications" id="edit_qualifications" class="hr-textarea" rows="3"></textarea>
                    </div>

                    <!-- Required Skills -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Required Skills</label>
                        <input type="text" name="required_skills" id="edit_required_skills" class="hr-input">
                    </div>

                    <!-- Preferred Skills -->
                    <div class="hr-form-group">
                        <label class="hr-form-label">Preferred Skills</label>
                        <input type="text" name="preferred_skills" id="edit_preferred_skills" class="hr-input">
                    </div>

                    <!-- Benefits -->
                    <div class="hr-form-group" style="grid-column: span 2;">
                        <label class="hr-form-label">Benefits</label>
                        <input type="text" name="benefits" id="edit_benefits" class="hr-input">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer" style="padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editVacancyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary"><i class="ph ph-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
        }
    }

    function openEditVacancyModal(vacancy) {
        document.getElementById('editVacancyForm').action = '/hr/recruitment/vacancies/' + vacancy.id;
        document.getElementById('edit_title').value = vacancy.title || '';
        document.getElementById('edit_department_id').value = vacancy.department_id || '';
        document.getElementById('edit_position_id').value = vacancy.position_id || '';
        document.getElementById('edit_employment_type').value = vacancy.employment_type || 'Regular';
        document.getElementById('edit_work_setup').value = vacancy.work_setup || 'On-site';
        document.getElementById('edit_branch_id').value = vacancy.branch_id || '';
        document.getElementById('edit_number_of_openings').value = vacancy.number_of_openings || 1;
        document.getElementById('edit_salary_range_min').value = vacancy.salary_range_min || '';
        document.getElementById('edit_salary_range_max').value = vacancy.salary_range_max || '';
        document.getElementById('edit_opening_date').value = vacancy.opening_date ? vacancy.opening_date.substring(0, 10) : '';
        document.getElementById('edit_closing_date').value = vacancy.closing_date ? vacancy.closing_date.substring(0, 10) : '';
        document.getElementById('edit_status').value = vacancy.status || 'Open';
        document.getElementById('edit_job_description').value = vacancy.job_description || '';
        document.getElementById('edit_responsibilities').value = vacancy.responsibilities || '';
        document.getElementById('edit_qualifications').value = vacancy.qualifications || '';
        document.getElementById('edit_required_skills').value = vacancy.required_skills || '';
        document.getElementById('edit_preferred_skills').value = vacancy.preferred_skills || '';
        document.getElementById('edit_benefits').value = vacancy.benefits || '';

        openModal('editVacancyModal');
    }
</script>
@endpush

@endsection

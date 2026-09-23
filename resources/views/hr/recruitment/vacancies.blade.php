@extends('layouts.app')

@section('title', 'Job Vacancies - Recruitment')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-briefcase"></i>
            Job Openings & Vacancies
        </h1>
        <p class="hr-page-subtitle">Publish and manage restaurant openings for cooks, servers, hosts, and managers</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addVacancyModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Post New Vacancy</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Branch & Dept</th>
                    <th>Openings</th>
                    <th>Employment Type</th>
                    <th>Salary Range</th>
                    <th>Opening Date</th>
                    <th>Applicants</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vacancies as $v)
                    <tr>
                        <td><strong>{{ $v->title }}</strong></td>
                        <td>
                            <div>{{ $v->branch?->name ?? 'All Branches' }}</div>
                            <small style="color: #64748b;">{{ $v->department?->name ?? 'General' }}</small>
                        </td>
                        <td><strong>{{ $v->number_of_openings }}</strong> seats</td>
                        <td>{{ $v->employment_type }}</td>
                        <td>
                            @if($v->salary_range_min || $v->salary_range_max)
                                ₱{{ number_format($v->salary_range_min, 0) }} - ₱{{ number_format($v->salary_range_max, 0) }}
                            @else
                                <span style="color: #94a3b8;">Negotiable</span>
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($v->opening_date)->format('M d, Y') }}</td>
                        <td>
                            <a href="{{ route('hr.recruitment.applicants') }}" class="hr-badge hr-badge-purple" style="text-decoration: none;">
                                {{ $v->applicants_count }} applicants
                            </a>
                        </td>
                        <td style="text-align: right;">
                            @if($v->status === 'Open')
                                <span class="hr-badge hr-badge-success">{{ $v->status }}</span>
                            @elseif($v->status === 'Filled')
                                <span class="hr-badge hr-badge-info">{{ $v->status }}</span>
                            @else
                                <span class="hr-badge hr-badge-neutral">{{ $v->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 30px;">No vacancies posted yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Post Vacancy -->
<div id="addVacancyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 700px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Post Job Vacancy</span>
            <button class="icon-btn" onclick="closeModal('addVacancyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.recruitment.vacancies.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-grid">
                    <div class="hr-form-group" style="grid-column: 1 / -1;">
                        <label class="hr-form-label">Job Title *</label>
                        <input type="text" name="title" class="hr-input" required placeholder="e.g. Griller / Line Cook">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Branch</label>
                        <select name="branch_id" class="hr-select">
                            <option value="">All Branches</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Department</label>
                        <select name="department_id" class="hr-select">
                            <option value="">Select Department</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Number of Openings *</label>
                        <input type="number" name="number_of_openings" class="hr-input" required value="1" min="1">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Employment Type *</label>
                        <select name="employment_type" class="hr-select" required>
                            <option value="Regular">Regular</option>
                            <option value="Probationary">Probationary</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Casual">Casual</option>
                            <option value="Contractual">Contractual</option>
                        </select>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Min (PHP)</label>
                        <input type="number" step="0.01" name="salary_range_min" class="hr-input" placeholder="18000">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Salary Max (PHP)</label>
                        <input type="number" step="0.01" name="salary_range_max" class="hr-input" placeholder="24000">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Opening Date *</label>
                        <input type="date" name="opening_date" class="hr-input" required value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Status *</label>
                        <select name="status" class="hr-select" required>
                            <option value="Open">Open</option>
                            <option value="Draft">Draft</option>
                            <option value="On Hold">On Hold</option>
                            <option value="Closed">Closed</option>
                            <option value="Filled">Filled</option>
                        </select>
                    </div>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Job Description</label>
                    <textarea name="job_description" class="hr-input" rows="3" placeholder="Key responsibilities..."></textarea>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Requirements & Qualifications</label>
                    <textarea name="requirements" class="hr-input" rows="3" placeholder="Experience, certifications..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addVacancyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Post Vacancy</button>
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

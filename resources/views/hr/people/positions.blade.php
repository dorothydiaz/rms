@extends('layouts.app')

@section('title', 'Positions - Organization Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-briefcase"></i>
            Restaurant Positions & Job Roles
        </h1>
        <p class="hr-page-subtitle">Configure chef, cook, server, cashier, manager, and dishwasher positions</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addPosModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Position</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Position Title</th>
                    <th>Department</th>
                    <th>Assigned Employees</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($positions as $pos)
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $pos->code }}</strong></td>
                        <td><strong>{{ $pos->name }}</strong></td>
                        <td>{{ $pos->department?->name ?? 'General' }}</td>
                        <td><span class="hr-badge hr-badge-purple">{{ $pos->employees_count }} employees</span></td>
                        <td style="text-align: right;"><span class="hr-badge hr-badge-success">Active</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">No positions created.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Position -->
<div id="addPosModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Position</span>
            <button class="icon-btn" onclick="closeModal('addPosModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.positions.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Title *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Line Cook">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Position Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. LC-01">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Department *</label>
                    <select name="department_id" class="hr-select" required>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}">{{ $d->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Job Description</label>
                    <textarea name="description" class="hr-input" rows="3" placeholder="Key responsibilities..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addPosModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Position</button>
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

@extends('layouts.app')

@section('title', 'Departments - Organization Management')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addDeptModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Department</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Department Name</th>
                    <th>Branch</th>
                    <th>Description</th>
                    <th>Total Staff</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $dept->code }}</strong></td>
                        <td><strong>{{ $dept->name }}</strong></td>
                        <td>{{ $dept->branch?->name ?? 'All Branches' }}</td>
                        <td>{{ $dept->description ?? 'No description' }}</td>
                        <td><span class="hr-badge hr-badge-purple">{{ $dept->employees_count }} employees</span></td>
                        <td style="text-align: right;">
                            <span class="hr-badge hr-badge-success">Active</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No departments found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Department -->
<div id="addDeptModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Department</span>
            <button class="icon-btn" onclick="closeModal('addDeptModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.departments.store') }}">
            @csrf
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Department Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Front of House">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Department Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. FOH">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch (Optional)</label>
                    <select name="branch_id" class="hr-select">
                        <option value="">Multi-branch (All)</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Description</label>
                    <textarea name="description" class="hr-input" rows="3" placeholder="Duties and focus..."></textarea>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addDeptModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Department</button>
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

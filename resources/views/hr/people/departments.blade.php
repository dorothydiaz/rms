@extends('layouts.app')

@section('title', 'Departments - Organization Management')

@push('styles')
    @vite('resources/css/modules/hr-departments.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-departments.js')
@endpush

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addDeptModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Department</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card hr-table-card-full">
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
            <tbody id="deptsTableBody">
                @forelse($departments as $dept)
                    <tr class="dept-row"
                        data-code="{{ strtolower($dept->code) }}"
                        data-name="{{ strtolower($dept->name) }}"
                        data-branch="{{ strtolower($dept->branch?->name ?? '') }}">
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $dept->code }}</strong></td>
                        <td><strong>{{ $dept->name }}</strong></td>
                        <td>{{ $dept->branch?->name ?? 'All Branches' }}</td>
                        <td>{{ $dept->description ?? 'No description' }}</td>
                        <td>
                            <button type="button"
                                    onclick="openMembersModal('department', {{ $dept->id }}, '{{ addslashes($dept->name) }}')"
                                    class="hr-badge hr-badge-purple hr-badge-staff-btn"
                                    title="Click to view staff members of {{ $dept->name }} in a window">
                                <i class="ph ph-users"></i>
                                <span>{{ $dept->employees_count }} {{ Str::plural('employee', $dept->employees_count) }}</span>
                                <i class="ph ph-arrow-square-out" style="font-size: 11px; opacity: 0.75;"></i>
                            </button>
                        </td>
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

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="deptPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="deptPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="deptPerPageSelect" onchange="deptChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="deptPageNav"></nav>
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

@include('hr.people.partials.members-modal')
@endsection

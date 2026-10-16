@extends('layouts.app')

@section('title', 'Branches - Organization Management')

@push('styles')
    @vite('resources/css/modules/hr-branches.css')
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-branches.js')
@endpush

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addBranchModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Branch</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Branch Code</th>
                    <th>Branch Name</th>
                    <th>Address</th>
                    <th>Contact Info</th>
                    <th>Total Staff</th>
                    <th style="text-align: right;">Status</th>
                </tr>
            </thead>
            <tbody id="branchTableBody">
                @forelse($branches as $branch)
                    <tr class="branch-row"
                        data-code="{{ strtolower($branch->code) }}"
                        data-name="{{ strtolower($branch->name) }}">
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $branch->code }}</strong></td>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td>{{ $branch->address ?? 'N/A' }}</td>
                        <td>
                            <div>{{ $branch->phone ?? 'No phone' }}</div>
                            <small style="color: #64748b;">{{ $branch->email ?? 'No email' }}</small>
                        </td>
                        <td>
                            <button type="button"
                                    onclick="openMembersModal('branch', {{ $branch->id }}, '{{ addslashes($branch->name) }}')"
                                    class="hr-badge hr-badge-purple hr-badge-staff-btn"
                                    title="Click to view staff members in {{ $branch->name }} in a window">
                                <i class="ph ph-users"></i>
                                <span>{{ $branch->employees_count }} {{ Str::plural('employee', $branch->employees_count) }}</span>
                                <i class="ph ph-arrow-square-out" style="font-size: 11px; opacity: 0.75;"></i>
                            </button>
                        </td>
                        <td style="text-align: right;">
                            <span class="hr-badge hr-badge-success">{{ $branch->is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">No branches created.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="branchPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="branchPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="branchPerPageSelect" onchange="branchChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="branchPageNav"></nav>
    </div>
</div>

<!-- Modal: Add Branch -->
<div id="addBranchModal" class="hr-modal-overlay">
    <div class="hr-modal">
        <div class="hr-modal-header">
            <span class="hr-modal-title"><i class="ph ph-plus-circle"></i> Add Restaurant Branch</span>
            <button class="icon-btn" onclick="closeModal('addBranchModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.branches.store') }}">
            @csrf
            @if($company)
                <input type="hidden" name="company_id" value="{{ $company->id }}">
            @endif
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Alabang Town Center Branch">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Branch Code *</label>
                    <input type="text" name="code" class="hr-input" required placeholder="e.g. ATC-04">
                </div>
                <div class="hr-form-group">
                    <label class="hr-form-label">Location Address</label>
                    <textarea name="address" class="hr-input" rows="2" placeholder="Full address..."></textarea>
                </div>
                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Phone Number</label>
                        <input type="tel" name="phone" class="hr-input" placeholder="+63 2 8xxx xxxx or 0917-xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email</label>
                        <input type="email" name="email" class="hr-input" placeholder="branch@restaurant.ph">
                    </div>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addBranchModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">Save Branch</button>
            </div>
        </form>
    </div>
</div>

@include('hr.people.partials.members-modal')
@endsection

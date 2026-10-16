@extends('layouts.app')

@section('title', 'Agency & Company Management - Organization Management')

@push('styles')
    @vite('resources/css/modules/hr-companies.css')
@endpush

@push('blade-data')
<script>
    window.HrCompaniesConfig = Object.freeze({
        baseUrl: @json(url('hr/people/companies')),
    });
</script>
@endpush

@push('scripts')
    @vite('resources/js/modules/hr-companies.js')
@endpush

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addCompanyModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Agency / Company</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Summary Metric Cards (Glassmorphism with Pink & Purple Left Accent Capsule) -->
<div class="hr-company-summary-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 8px; margin-bottom: 8px;">
    <!-- Total Entities -->
    <div class="hr-stat-card hr-stat-card-purple">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-buildings"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Total Entities</span>
            <div class="hr-stat-value">{{ $totalCount }}</div>
            <span class="hr-stat-sub">Registered Records</span>
        </div>
    </div>

    <!-- Direct Companies -->
    <div class="hr-stat-card hr-stat-card-blue">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-briefcase"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Direct Companies</span>
            <div class="hr-stat-value">{{ $companyCount }}</div>
            <span class="hr-stat-sub">Internal Entities</span>
        </div>
    </div>

    <!-- Staffing Agencies -->
    <div class="hr-stat-card hr-stat-card-pink">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-handshake"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Staffing Agencies</span>
            <div class="hr-stat-value">{{ $agencyCount }}</div>
            <span class="hr-stat-sub">External Partners</span>
        </div>
    </div>

    <!-- Assigned Employees -->
    <div class="hr-stat-card hr-stat-card-green">
        <div class="hr-stat-icon-wrap">
            <i class="ph ph-users-three"></i>
        </div>
        <div class="hr-stat-content">
            <span class="hr-stat-label">Assigned Employees</span>
            <div class="hr-stat-value">{{ $totalStaffCount }}</div>
            <span class="hr-stat-sub">Active Deployed</span>
        </div>
    </div>
</div>

<!-- Real-Time Filter & Search Bar (No Enter Key or Submit Button Required) -->
<div class="hr-filter-bar" style="margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: nowrap;">
    <!-- Filters & Search Controls Group -->
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: nowrap; flex: 1;">
        <!-- Instant Real-Time Search -->
        <div style="position: relative; width: 240px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 15px; pointer-events: none;"></i>
            <input type="text" id="companySearchInput" class="hr-input" placeholder="Search name, code, TIN..." oninput="filterCompaniesTable()" style="width: 100%; box-sizing: border-box; padding-left: 32px; height: 31px; font-size: 12px;">
        </div>

        <!-- Type Selector -->
        <select id="companyTypeFilter" class="hr-select" style="height: 31px; font-size: 12px; max-width: 170px;" onchange="filterCompaniesTable()">
            <option value="">All Entity Types</option>
            <option value="Company">Direct Company</option>
            <option value="Agency">Staffing Agency</option>
        </select>

        <!-- Status Selector -->
        <select id="companyStatusFilter" class="hr-select" style="height: 31px; font-size: 12px; max-width: 140px;" onchange="filterCompaniesTable()">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        <!-- Sort By Options in Table Toolbar -->
        <select id="companySortSelect" class="hr-select" style="height: 31px; font-size: 12px; max-width: 185px;" onchange="applyCompanySortFromSelect(this.value)">
            <option value="">Sort By: Default</option>
            <option value="name_asc">Name (A &rarr; Z)</option>
            <option value="name_desc">Name (Z &rarr; A)</option>
            <option value="code_asc">Code (A &rarr; Z)</option>
            <option value="code_desc">Code (Z &rarr; A)</option>
            <option value="contact_asc">Contact Person (A &rarr; Z)</option>
            <option value="tin_asc">TIN (Ascending)</option>
            <option value="staff_desc">Deployed Staff (Most)</option>
            <option value="staff_asc">Deployed Staff (Least)</option>
            <option value="status_asc">Status (Active First)</option>
        </select>

        <!-- Reset Filter Button -->
        <button type="button" class="hr-btn hr-btn-secondary" onclick="resetCompaniesFilters()" title="Reset all filters" style="height: 31px; font-size: 12px; padding: 0 12px;">
            <i class="ph ph-arrows-counter-clockwise"></i>
            <span>Reset</span>
        </button>
    </div>

    <!-- Live Counter Badge -->
    <div style="flex-shrink: 0;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 11.5px; font-weight: 600; padding: 5px 10px; border-radius: 6px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
            Showing <strong id="companyVisibleCount" style="color: #0f172a;">{{ $companies->count() }}</strong> of {{ $companies->count() }} entities
        </span>
    </div>
</div>

<!-- Table Card with Sticky Headers and Vertical Scrollbar -->
<div class="hr-table-card hr-table-card-full">
    <div class="hr-table-wrapper" id="companiesTableWrapper" style="overflow-y:auto; overflow-x:auto;">
        <table class="hr-table" id="companiesTable">
            <thead>
                <tr>
                    <th class="sortable" onclick="sortCompaniesTable(0, 'text')" title="Click to sort by Entity Code (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Entity Code</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortCompaniesTable(1, 'text')" title="Click to sort by Name & Classification (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Name & Classification</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortCompaniesTable(2, 'text')" title="Click to sort by Contact Person (A-Z / Z-A)">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Contact Person</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th>Contact Info</th>
                    <th class="sortable" onclick="sortCompaniesTable(4, 'text')" title="Click to sort by TIN">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>TIN</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortCompaniesTable(5, 'number')" title="Click to sort by Deployed Staff">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Deployed Staff</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th class="sortable" onclick="sortCompaniesTable(6, 'text')" title="Click to sort by Status">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span>Status</span>
                            <span style="display: inline-flex; align-items: center;">
                                <span class="sort-badge asc">ASC</span>
                                <span class="sort-badge desc">DESC</span>
                                <span class="sort-icon"><i class="ph ph-arrows-down-up"></i></span>
                            </span>
                        </div>
                    </th>
                    <th style="text-align: right; width: 110px;">Actions</th>
                </tr>
            </thead>
            <tbody id="companiesTableBody">
                @forelse($companies as $item)
                    <tr class="company-row"
                        data-code="{{ strtolower($item->code) }}"
                        data-name="{{ strtolower($item->name) }}"
                        data-type="{{ $item->type }}"
                        data-contact="{{ strtolower($item->contact_person ?? '') }}"
                        data-email="{{ strtolower($item->email ?? '') }}"
                        data-phone="{{ strtolower($item->phone ?? '') }}"
                        data-tin="{{ strtolower($item->tin ?? '') }}"
                        data-staff="{{ $item->employees_count }}"
                        data-status="{{ $item->is_active ? 'Active' : 'Inactive' }}">
                        <td>
                            <code style="font-weight: 600; color: #7c3aed; background: rgba(124, 58, 237, 0.08); padding: 3px 8px; border-radius: 6px; font-size: 12px;">
                                {{ $item->code }}
                            </code>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">
                                    {{ $item->name }}
                                </div>
                                @if($item->type === 'Agency')
                                    <span class="hr-badge" style="background: rgba(168, 85, 247, 0.12); color: #7e22ce; border: 1px solid rgba(168, 85, 247, 0.25);">
                                        <i class="ph ph-handshake"></i> Agency
                                    </span>
                                @else
                                    <span class="hr-badge" style="background: rgba(59, 130, 246, 0.12); color: #1d4ed8; border: 1px solid rgba(59, 130, 246, 0.25);">
                                        <i class="ph ph-buildings"></i> Company
                                    </span>
                                @endif
                            </div>
                            @if($item->notes)
                                <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">
                                    {{ Str::limit($item->notes, 45) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            <div style="font-weight: 600; color: #334155; font-size: 13px;">
                                {{ $item->contact_person ?: '—' }}
                            </div>
                        </td>
                        <td>
                            <div style="font-size: 12.5px; color: #0f172a;">{{ $item->phone ?: 'No phone' }}</div>
                            <div style="font-size: 11.5px; color: #64748b;">{{ $item->email ?: 'No email' }}</div>
                        </td>
                        <td>
                            <span style="font-size: 12px; color: #475569; font-family: monospace;">{{ $item->tin ?: 'N/A' }}</span>
                        </td>
                        <td>
                            <button type="button"
                                    onclick="openMembersModal('company', {{ $item->id }}, '{{ addslashes($item->name) }}')"
                                    class="hr-badge hr-badge-purple hr-badge-staff-btn"
                                    title="Click to view staff members assigned to {{ $item->name }} in a window">
                                <i class="ph ph-users"></i>
                                <span>{{ $item->employees_count }} {{ Str::plural('staff', $item->employees_count) }}</span>
                                <i class="ph ph-arrow-square-out" style="font-size: 11px; opacity: 0.75;"></i>
                            </button>
                        </td>
                        <td>
                            @if($item->is_active)
                                <span class="hr-badge hr-badge-success">Active</span>
                            @else
                                <span class="hr-badge hr-badge-danger">Inactive</span>
                            @endif
                        </td>
                        <td style="text-align: right;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <button type="button" class="hr-btn hr-btn-secondary hr-btn-sm" title="Edit Entity" onclick="openEditCompanyModal({{ json_encode($item) }})">
                                    <i class="ph ph-pencil-simple"></i>
                                </button>
                                <form method="POST" action="{{ route('hr.people.companies.destroy', $item->id) }}" onsubmit="return confirm('Are you sure you want to delete this {{ strtolower($item->type) }}: {{ addslashes($item->name) }}?');" style="display: inline;">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="hr-btn hr-btn-danger hr-btn-sm" title="Delete Entity">
                                        <i class="ph ph-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px 20px;">
                            <div style="width: 50px; height: 50px; border-radius: 12px; background: rgba(124, 58, 237, 0.08); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 26px; margin: 0 auto 12px;">
                                <i class="ph ph-buildings"></i>
                            </div>
                            <div style="font-size: 15px; font-weight: 700; color: #0f172a;">No Agencies or Companies Found</div>
                            <p style="font-size: 13px; color: #64748b; margin: 6px auto 16px; max-width: 440px;">
                                Add your corporate operating entities and external manpower staffing agencies to categorize your employees.
                            </p>
                            <button type="button" class="hr-btn hr-btn-primary hr-btn-sm" onclick="openModal('addCompanyModal')">
                                <i class="ph ph-plus-circle"></i> Add First Entity
                            </button>
                        </td>
                    </tr>
                @endforelse
                <tr id="noCompanyResultsRow" style="display: none;">
                    <td colspan="8" style="text-align: center; color: #94a3b8; padding: 40px;">
                        <i class="ph ph-magnifying-glass" style="font-size: 32px; display: block; margin-bottom: 8px; color: #cbd5e1;"></i>
                        No agencies or companies match your filter criteria.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Client-side Pagination Bar -->
    <div class="hr-table-footer" id="compPaginationBar" style="display:none;">
        <div class="hr-pagination-left">
            <div class="hr-pagination-info" id="compPaginationInfo"></div>
            <div class="hr-per-page-wrap">
                <span class="hr-per-page-label">Show</span>
                <select class="hr-per-page-select" id="compPerPageSelect" onchange="compChangePerPage()" aria-label="Rows per page">
                    <option value="15" selected>15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <span class="hr-per-page-label">rows</span>
            </div>
        </div>
        <nav class="hr-pagination-nav" role="navigation" aria-label="Pagination Navigation" id="compPageNav"></nav>
    </div>
</div>

<!-- Modal: Add Agency / Company -->
<div id="addCompanyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title">
                <i class="ph ph-plus-circle" style="color: #7c3aed;"></i>
                Add Agency / Company
            </span>
            <button type="button" class="icon-btn" onclick="closeModal('addCompanyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form method="POST" action="{{ route('hr.people.companies.store') }}">
            @csrf
            <div class="hr-modal-body">
                <!-- Classification Selection -->
                <div class="hr-form-group">
                    <label class="hr-form-label">Entity Classification *</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 4px;">
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff; transition: border-color 0.2s;">
                            <input type="radio" name="type" value="Company" checked style="accent-color: #2563eb;">
                            <div>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">Direct Company</div>
                                <div style="font-size: 11.5px; color: #64748b;">Principal / Operating Entity</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff; transition: border-color 0.2s;">
                            <input type="radio" name="type" value="Agency" style="accent-color: #9333ea;">
                            <div>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">Staffing Agency</div>
                                <div style="font-size: 11.5px; color: #64748b;">External Manpower Partner</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Entity Name *</label>
                    <input type="text" name="name" class="hr-input" required placeholder="e.g. Bistro Hospitality Group Inc. or ABC Staffing Services">
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Entity Code *</label>
                        <input type="text" name="code" class="hr-input" required placeholder="e.g. BHG-01 or ABC-STAFF">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">TIN (Tax ID Number)</label>
                        <input type="text" name="tin" class="hr-input" placeholder="e.g. 123-456-789-000">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Contact Person / Account Executive</label>
                    <input type="text" name="contact_person" class="hr-input" placeholder="e.g. Ms. Jessica Cruz - Account Manager">
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Phone Number</label>
                        <input type="tel" name="phone" class="hr-input" placeholder="+63 2 8xxx xxxx / 0917-xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email Address</label>
                        <input type="email" name="email" class="hr-input" placeholder="contact@agency.ph">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Office Address</label>
                    <textarea name="address" class="hr-input" rows="2" placeholder="Unit, building, street, city..."></textarea>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Notes & Engagement Details <span style="font-weight: 400; color: #64748b;">(Optional)</span></label>
                    <textarea name="notes" class="hr-input" rows="2" placeholder="e.g. Contract renewed annually every January; supplies dining room staff."></textarea>
                </div>

                <div class="hr-form-group">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: #0f172a; cursor: pointer;">
                        <input type="checkbox" name="is_active" value="1" checked style="accent-color: #7c3aed; width: 17px; height: 17px;">
                        <span>Active Partner Entity</span>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('addCompanyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-check-circle"></i> Save Entity
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Agency / Company -->
<div id="editCompanyModal" class="hr-modal-overlay">
    <div class="hr-modal" style="max-width: 620px;">
        <div class="hr-modal-header">
            <span class="hr-modal-title">
                <i class="ph ph-pencil-simple" style="color: #7c3aed;"></i>
                Edit Agency / Company
            </span>
            <button type="button" class="icon-btn" onclick="closeModal('editCompanyModal')"><i class="ph ph-x"></i></button>
        </div>
        <form id="editCompanyForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="hr-modal-body">
                <div class="hr-form-group">
                    <label class="hr-form-label">Entity Classification *</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 4px;">
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff;">
                            <input type="radio" name="type" id="edit_type_company" value="Company" style="accent-color: #2563eb;">
                            <div>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">Direct Company</div>
                                <div style="font-size: 11.5px; color: #64748b;">Principal / Operating Entity</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff;">
                            <input type="radio" name="type" id="edit_type_agency" value="Agency" style="accent-color: #9333ea;">
                            <div>
                                <div style="font-weight: 600; color: #0f172a; font-size: 13.5px;">Staffing Agency</div>
                                <div style="font-size: 11.5px; color: #64748b;">External Manpower Partner</div>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Entity Name *</label>
                    <input type="text" name="name" id="edit_name" class="hr-input" required>
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Entity Code *</label>
                        <input type="text" name="code" id="edit_code" class="hr-input" required>
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">TIN (Tax ID Number)</label>
                        <input type="text" name="tin" id="edit_tin" class="hr-input">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Contact Person / Account Executive</label>
                    <input type="text" name="contact_person" id="edit_contact_person" class="hr-input">
                </div>

                <div class="hr-form-grid">
                    <div class="hr-form-group">
                        <label class="hr-form-label">Phone Number</label>
                        <input type="tel" name="phone" id="edit_phone" class="hr-input" placeholder="+63 2 8xxx xxxx / 0917-xxx-xxxx" pattern="[+]?[\d\s\-()]{7,25}">
                    </div>
                    <div class="hr-form-group">
                        <label class="hr-form-label">Email Address</label>
                        <input type="email" name="email" id="edit_email" class="hr-input">
                    </div>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Office Address</label>
                    <textarea name="address" id="edit_address" class="hr-input" rows="2"></textarea>
                </div>

                <div class="hr-form-group">
                    <label class="hr-form-label">Notes & Engagement Details</label>
                    <textarea name="notes" id="edit_notes" class="hr-input" rows="2"></textarea>
                </div>

                <div class="hr-form-group">
                    <label style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; font-weight: 600; color: #0f172a; cursor: pointer;">
                        <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="accent-color: #7c3aed; width: 17px; height: 17px;">
                        <span>Active Partner Entity</span>
                    </label>
                </div>
            </div>
            <div class="hr-modal-footer">
                <button type="button" class="hr-btn hr-btn-secondary" onclick="closeModal('editCompanyModal')">Cancel</button>
                <button type="submit" class="hr-btn hr-btn-primary">
                    <i class="ph ph-check-circle"></i> Update Entity
                </button>
            </div>
        </form>
    </div>
</div>

@include('hr.people.partials.members-modal')
@endsection

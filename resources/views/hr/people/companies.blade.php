@extends('layouts.app')

@section('title', 'Agency & Company Management - Organization Management')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addCompanyModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Agency / Company</span>
        </button>
    </x-slot:actions>
</x-hr-tabs>

<!-- Summary Metric Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 16px; margin-bottom: 22px;">
    <div class="hr-table-card" style="margin-bottom: 0; padding: 18px 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(124, 58, 237, 0.1); color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class="ph ph-buildings"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total Entities</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-top: 2px;">{{ $totalCount }}</div>
        </div>
    </div>
    <div class="hr-table-card" style="margin-bottom: 0; padding: 18px 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(59, 130, 246, 0.1); color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class="ph ph-briefcase"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Direct Companies</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-top: 2px;">{{ $companyCount }}</div>
        </div>
    </div>
    <div class="hr-table-card" style="margin-bottom: 0; padding: 18px 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(168, 85, 247, 0.1); color: #9333ea; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class="ph ph-handshake"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Staffing Agencies</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-top: 2px;">{{ $agencyCount }}</div>
        </div>
    </div>
    <div class="hr-table-card" style="margin-bottom: 0; padding: 18px 20px; display: flex; align-items: center; gap: 16px;">
        <div style="width: 46px; height: 46px; border-radius: 12px; background: rgba(16, 185, 129, 0.1); color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            <i class="ph ph-users-three"></i>
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 700; color: #64748b; text-transform: uppercase;">Assigned Employees</div>
            <div style="font-size: 22px; font-weight: 800; color: #0f172a; line-height: 1.2; margin-top: 2px;">{{ $totalStaffCount }}</div>
        </div>
    </div>
</div>

<!-- Real-Time Filter & Search Bar (No Enter Key or Submit Button Required) -->
<div class="hr-filter-bar" style="margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
    <!-- Filters & Search Controls Group -->
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap; flex: 1;">
        <!-- Instant Real-Time Search -->
        <div style="position: relative; width: 260px; flex-shrink: 0;">
            <i class="ph ph-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; pointer-events: none;"></i>
            <input type="text" id="companySearchInput" class="hr-input" placeholder="Search name, code, TIN..." oninput="filterCompaniesTable()" style="width: 100%; box-sizing: border-box; padding-left: 36px; height: 38px;">
        </div>

        <!-- Type Selector -->
        <select id="companyTypeFilter" class="hr-select" style="height: 38px; max-width: 180px;" onchange="filterCompaniesTable()">
            <option value="">All Entity Types</option>
            <option value="Company">Direct Company</option>
            <option value="Agency">Staffing Agency</option>
        </select>

        <!-- Status Selector -->
        <select id="companyStatusFilter" class="hr-select" style="height: 38px; max-width: 150px;" onchange="filterCompaniesTable()">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>

        <!-- Sort By Options in Table Toolbar -->
        <select id="companySortSelect" class="hr-select" style="height: 38px; max-width: 195px;" onchange="applyCompanySortFromSelect(this.value)">
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
        <button type="button" class="hr-btn hr-btn-secondary" onclick="resetCompaniesFilters()" title="Reset all filters" style="height: 38px; padding: 0 14px;">
            <i class="ph ph-arrows-counter-clockwise"></i>
            <span>Reset</span>
        </button>
    </div>

    <!-- Live Counter Badge -->
    <div style="flex-shrink: 0;">
        <span class="hr-badge hr-badge-neutral" style="font-size: 12px; font-weight: 600; padding: 7px 12px; border-radius: 8px; background: #f1f5f9; color: #475569; display: inline-flex; align-items: center; gap: 4px;">
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
                            <code style="font-weight: 700; color: #7c3aed; background: rgba(124, 58, 237, 0.08); padding: 3px 8px; border-radius: 6px; font-size: 12px;">
                                {{ $item->code }}
                            </code>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">
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
                            <a href="{{ route('hr.people.employees', ['search' => $item->name]) }}" class="hr-badge hr-badge-purple" title="View assigned staff members">
                                <i class="ph ph-users"></i> {{ $item->employees_count }} staff
                            </a>
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
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Direct Company</div>
                                <div style="font-size: 11.5px; color: #64748b;">Principal / Operating Entity</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff; transition: border-color 0.2s;">
                            <input type="radio" name="type" value="Agency" style="accent-color: #9333ea;">
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Staffing Agency</div>
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
                        <input type="text" name="phone" class="hr-input" placeholder="+63 2 8xxx xxxx / 0917-xxx-xxxx">
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
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Direct Company</div>
                                <div style="font-size: 11.5px; color: #64748b;">Principal / Operating Entity</div>
                            </div>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px; padding: 12px 14px; border: 1.5px solid #cbd5e1; border-radius: 10px; cursor: pointer; background: #ffffff;">
                            <input type="radio" name="type" id="edit_type_agency" value="Agency" style="accent-color: #9333ea;">
                            <div>
                                <div style="font-weight: 700; color: #0f172a; font-size: 13.5px;">Staffing Agency</div>
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
                        <input type="text" name="phone" id="edit_phone" class="hr-input">
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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openEditCompanyModal(data) {
    var form = document.getElementById('editCompanyForm');
    form.action = "{{ url('hr/people/companies') }}/" + data.id;

    if (data.type === 'Agency') {
        document.getElementById('edit_type_agency').checked = true;
    } else {
        document.getElementById('edit_type_company').checked = true;
    }

    document.getElementById('edit_name').value = data.name || '';
    document.getElementById('edit_code').value = data.code || '';
    document.getElementById('edit_tin').value = data.tin || '';
    document.getElementById('edit_contact_person').value = data.contact_person || '';
    document.getElementById('edit_phone').value = data.phone || '';
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_address').value = data.address || '';
    document.getElementById('edit_notes').value = data.notes || '';
    document.getElementById('edit_is_active').checked = !!data.is_active;

    openModal('editCompanyModal');
}


// =========================================================================
// Client-Side Pagination State — Companies
// =========================================================================
let _compCurrentPage = 1;
let _compFilteredRows = [];

function compGetPerPage() { return parseInt(document.getElementById('compPerPageSelect')?.value || '15', 10); }
function compChangePerPage() { _compCurrentPage = 1; renderCompPage(); }
function compGoToPage(p) {
    _compCurrentPage = p;
    renderCompPage();
    const w = document.getElementById('companiesTableWrapper');
    if (w) w.scrollTop = 0;
}

function renderCompPage() {
    const pp = compGetPerPage(), total = _compFilteredRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_compCurrentPage > totalPages) _compCurrentPage = totalPages;
    const start = (_compCurrentPage - 1) * pp, end = Math.min(start + pp, total);

    document.querySelectorAll('#companiesTableBody tr.company-row').forEach(r => r.style.display = 'none');
    _compFilteredRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });


    const noResultsRow = document.getElementById('noCompanyResultsRow');
    if (noResultsRow) noResultsRow.style.display = total === 0 ? '' : 'none';

    const countElem = document.getElementById('companyVisibleCount');
    if (countElem) countElem.textContent = total;

    const bar = document.getElementById('compPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';

    const info = document.getElementById('compPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> entities`;

    const nav = document.getElementById('compPageNav');
    if (!nav) return;
    let h = _compCurrentPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="compGoToPage(${_compCurrentPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _compCurrentPage-2), pe = Math.min(totalPages, _compCurrentPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="compGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _compCurrentPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="compGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="compGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _compCurrentPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="compGoToPage(${_compCurrentPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

// =========================================================================
// Real-Time Table Filter
// =========================================================================
function filterCompaniesTable() {
    const searchVal = (document.getElementById('companySearchInput')?.value || '').toLowerCase().trim();
    const typeVal   = (document.getElementById('companyTypeFilter')?.value || '').trim();
    const statusVal = (document.getElementById('companyStatusFilter')?.value || '').trim();

    const allRows = document.querySelectorAll('#companiesTableBody tr.company-row');

    _compFilteredRows = Array.from(allRows).filter(row => {
        const code    = row.getAttribute('data-code')    || '';
        const name    = row.getAttribute('data-name')    || '';
        const type    = row.getAttribute('data-type')    || '';
        const contact = row.getAttribute('data-contact') || '';
        const email   = row.getAttribute('data-email')   || '';
        const phone   = row.getAttribute('data-phone')   || '';
        const tin     = row.getAttribute('data-tin')     || '';
        const status  = row.getAttribute('data-status')  || '';

        const matchesSearch = !searchVal ||
            code.includes(searchVal) ||
            name.includes(searchVal) ||
            contact.includes(searchVal) ||
            email.includes(searchVal) ||
            phone.includes(searchVal) ||
            tin.includes(searchVal);

        const matchesType   = !typeVal   || type === typeVal;
        const matchesStatus = !statusVal || status === statusVal;

        return matchesSearch && matchesType && matchesStatus;
    });

    _compCurrentPage = 1;
    renderCompPage();
}

document.addEventListener('DOMContentLoaded', () => { filterCompaniesTable(); });

function resetCompaniesFilters() {
    const s = document.getElementById('companySearchInput');
    if (s) s.value = '';
    const t = document.getElementById('companyTypeFilter');
    if (t) t.value = '';
    const st = document.getElementById('companyStatusFilter');
    if (st) st.value = '';
    const sel = document.getElementById('companySortSelect');
    if (sel) sel.value = '';

    // Clear header sort classes
    document.querySelectorAll('#companiesTable thead th.sortable').forEach(th => {
        th.classList.remove('sorted-asc', 'sorted-desc');
        const icon = th.querySelector('.sort-icon i');
        if (icon) icon.className = 'ph ph-arrows-down-up';
    });
    compSortCol = -1;

    filterCompaniesTable();
}

// =========================================================================
// Real-Time Column Sorting (Via Table Header Click or Toolbar Dropdown)
// =========================================================================
let compSortCol = -1;
let compSortDir = 'asc';

function applyCompanySortFromSelect(val) {
    if (!val) {
        document.querySelectorAll('#companiesTable thead th.sortable').forEach(th => {
            th.classList.remove('sorted-asc', 'sorted-desc');
            const icon = th.querySelector('.sort-icon i');
            if (icon) icon.className = 'ph ph-arrows-down-up';
        });
        compSortCol = -1;
        return;
    }

    const sortMap = {
        'name_asc': [1, 'text', 'asc'],
        'name_desc': [1, 'text', 'desc'],
        'code_asc': [0, 'text', 'asc'],
        'code_desc': [0, 'text', 'desc'],
        'contact_asc': [2, 'text', 'asc'],
        'tin_asc': [4, 'text', 'asc'],
        'staff_desc': [5, 'number', 'desc'],
        'staff_asc': [5, 'number', 'asc'],
        'status_asc': [6, 'text', 'asc'],
    };

    if (sortMap[val]) {
        sortCompaniesTable(sortMap[val][0], sortMap[val][1], sortMap[val][2]);
    }
}

function sortCompaniesTable(colIndex, dataType, forceDir = null) {
    const tableBody = document.getElementById('companiesTableBody');
    const rows = Array.from(tableBody.querySelectorAll('tr.company-row'));
    const headers = document.querySelectorAll('#companiesTable thead th.sortable');

    if (forceDir) {
        compSortDir = forceDir;
        compSortCol = colIndex;
    } else {
        if (compSortCol === colIndex) {
            compSortDir = compSortDir === 'asc' ? 'desc' : 'asc';
        } else {
            compSortCol = colIndex;
            compSortDir = 'asc';
        }
    }

    headers.forEach((th, idx) => {
        const icon = th.querySelector('.sort-icon i');
        if (idx === colIndex) {
            th.classList.remove('sorted-asc', 'sorted-desc');
            th.classList.add(compSortDir === 'asc' ? 'sorted-asc' : 'sorted-desc');
            if (icon) {
                icon.className = compSortDir === 'asc' ? 'ph ph-caret-up' : 'ph ph-caret-down';
            }
        } else {
            th.classList.remove('sorted-asc', 'sorted-desc');
            if (icon) {
                icon.className = 'ph ph-arrows-down-up';
            }
        }
    });

    // Synchronize Toolbar Sort Dropdown
    const sortSelect = document.getElementById('companySortSelect');
    if (sortSelect) {
        const keyMap = {
            '0_asc': 'code_asc', '0_desc': 'code_desc',
            '1_asc': 'name_asc', '1_desc': 'name_desc',
            '2_asc': 'contact_asc',
            '4_asc': 'tin_asc',
            '5_desc': 'staff_desc', '5_asc': 'staff_asc',
            '6_asc': 'status_asc'
        };
        sortSelect.value = keyMap[colIndex + '_' + compSortDir] || '';
    }

    rows.sort((a, b) => {
        let valA = '', valB = '';
        if (colIndex === 0) {
            valA = a.getAttribute('data-code') || '';
            valB = b.getAttribute('data-code') || '';
        } else if (colIndex === 1) {
            valA = a.getAttribute('data-name') || '';
            valB = b.getAttribute('data-name') || '';
        } else if (colIndex === 2) {
            valA = a.getAttribute('data-contact') || '';
            valB = b.getAttribute('data-contact') || '';
        } else if (colIndex === 4) {
            valA = a.getAttribute('data-tin') || '';
            valB = b.getAttribute('data-tin') || '';
        } else if (colIndex === 5) {
            const numA = parseFloat(a.getAttribute('data-staff')) || 0;
            const numB = parseFloat(b.getAttribute('data-staff')) || 0;
            return compSortDir === 'asc' ? numA - numB : numB - numA;
        } else if (colIndex === 6) {
            valA = a.getAttribute('data-status') || '';
            valB = b.getAttribute('data-status') || '';
        }

        const cmp = valA.localeCompare(valB, undefined, { numeric: true, sensitivity: 'base' });
        return compSortDir === 'asc' ? cmp : -cmp;
    });

    const noResultsRow = document.getElementById('noCompanyResultsRow');
    rows.forEach(r => tableBody.appendChild(r));
    if (noResultsRow) tableBody.appendChild(noResultsRow);

    // Re-apply filter + pagination after sort reorders DOM
    filterCompaniesTable();
}
</script>
@endpush
@endsection

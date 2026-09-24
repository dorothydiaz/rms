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

<!-- Filter Bar -->
<div class="hr-filter-bar">
    <form method="GET" action="{{ route('hr.people.companies') }}" class="hr-filter-form" style="display: flex; gap: 12px; align-items: center; width: 100%; flex-wrap: wrap;">
        <!-- Search Input -->
        <input type="text" name="search" class="hr-input" placeholder="Search by name, code, contact person, TIN..." value="{{ request('search') }}" style="flex: 1; min-width: 220px;">

        <!-- Type Selector -->
        <select name="type" class="hr-select" style="min-width: 170px;">
            <option value="">All Entity Types</option>
            <option value="Company" {{ request('type') === 'Company' ? 'selected' : '' }}>Direct Company</option>
            <option value="Agency" {{ request('type') === 'Agency' ? 'selected' : '' }}>Staffing Agency</option>
        </select>

        <button type="submit" class="hr-btn hr-btn-secondary">
            <i class="ph ph-magnifying-glass"></i>
            <span>Filter</span>
        </button>

        @if(request()->hasAny(['search', 'type']))
            <a href="{{ route('hr.people.companies') }}" class="hr-btn hr-btn-secondary" title="Clear Filters">
                <i class="ph ph-x"></i>
                <span>Reset</span>
            </a>
        @endif
    </form>
</div>

<!-- Table Card -->
<div class="hr-table-card">
    <div class="hr-table-header">
        <span class="hr-table-title">
            <i class="ph ph-buildings" style="color: #7c3aed;"></i>
            Registered Agencies & Corporate Entities
        </span>
        <span class="hr-badge hr-badge-neutral">{{ $companies->count() }} {{ Str::plural('Record', $companies->count()) }}</span>
    </div>
    <div class="hr-table-wrapper">
        <table class="hr-table">
            <thead>
                <tr>
                    <th>Entity Code</th>
                    <th>Name & Classification</th>
                    <th>Contact Person</th>
                    <th>Contact Info</th>
                    <th>TIN</th>
                    <th>Deployed Staff</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($companies as $item)
                    <tr>
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
            </tbody>
        </table>
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
</script>
@endpush
@endsection

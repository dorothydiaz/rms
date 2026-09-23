@extends('layouts.app')

@section('title', 'Branches - Organization Management')

@section('content')
<div class="hr-page-header">
    <div>
        <h1 class="hr-page-title">
            <i class="ph ph-storefront"></i>
            Restaurant Branches & Locations
        </h1>
        <p class="hr-page-subtitle">Manage multi-unit restaurant stores, locations, and scoped management</p>
    </div>
    <div class="hr-page-actions">
        <button class="hr-btn hr-btn-primary" onclick="openModal('addBranchModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Branch</span>
        </button>
    </div>
</div>

<div class="hr-table-card">
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
            <tbody>
                @forelse($branches as $branch)
                    <tr>
                        <td><strong style="color: #9333ea; font-family: monospace;">{{ $branch->code }}</strong></td>
                        <td><strong>{{ $branch->name }}</strong></td>
                        <td>{{ $branch->address ?? 'N/A' }}</td>
                        <td>
                            <div>{{ $branch->phone ?? 'No phone' }}</div>
                            <small style="color: #64748b;">{{ $branch->email ?? 'No email' }}</small>
                        </td>
                        <td><span class="hr-badge hr-badge-purple">{{ $branch->employees_count }} employees</span></td>
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
                        <input type="text" name="phone" class="hr-input" placeholder="+63 2 8xxx xxxx">
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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
</script>
@endpush
@endsection

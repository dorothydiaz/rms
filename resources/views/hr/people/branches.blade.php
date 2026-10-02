@extends('layouts.app')

@section('title', 'Branches - Organization Management')

@section('content')
<x-hr-tabs parent="employee-management">
    <x-slot:actions>
        <button class="hr-btn hr-btn-primary" onclick="openModal('addBranchModal')">
            <i class="ph ph-plus-circle"></i>
            <span>Add Branch</span>
        </button>
</x-hr-tabs>

<style>
.hr-badge-staff-btn {
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.18s ease;
    font-family: inherit;
    font-size: 11.5px;
    font-weight: 600;
}
.hr-badge-staff-btn:hover {
    background: #7c3aed !important;
    color: #ffffff !important;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.35);
    transform: translateY(-1px);
}
</style>

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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Branches
// =========================================================================
let _branchPage = 1, _branchRows = [];
function branchGetPerPage() { return parseInt(document.getElementById('branchPerPageSelect')?.value || '15', 10); }
function branchChangePerPage() { _branchPage = 1; renderBranchPage(); }
function branchGoToPage(p) { _branchPage = p; renderBranchPage(); const w = document.getElementById('branchTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderBranchPage() {
    const pp = branchGetPerPage(), total = _branchRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_branchPage > totalPages) _branchPage = totalPages;
    const start = (_branchPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#branchTableBody tr.branch-row').forEach(r => r.style.display = 'none');
    _branchRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('branchPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('branchPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> branches`;
    const nav = document.getElementById('branchPageNav');
    if (!nav) return;
    let h = _branchPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="branchGoToPage(${_branchPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _branchPage-2), pe = Math.min(totalPages, _branchPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="branchGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _branchPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="branchGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="branchGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _branchPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="branchGoToPage(${_branchPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

window.refreshBranchPage = function() {
    _branchRows = Array.from(document.querySelectorAll('#branchTableBody tr.branch-row'));
    renderBranchPage();
};

document.addEventListener('DOMContentLoaded', () => {
    window.refreshBranchPage();
});
document.addEventListener('rmsTableSorted', () => {
    window.refreshBranchPage();
});
</script>
@endpush

@include('hr.people.partials.members-modal')
@endsection

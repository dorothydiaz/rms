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

@push('scripts')
<script>
function openModal(id) { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// =========================================================================
// Client-Side Pagination — Departments
// =========================================================================
let _deptPage = 1, _deptRows = [];
function deptGetPerPage() { return parseInt(document.getElementById('deptPerPageSelect')?.value || '15', 10); }
function deptChangePerPage() { _deptPage = 1; renderDeptPage(); }
function deptGoToPage(p) { _deptPage = p; renderDeptPage(); const w = document.getElementById('deptsTableBody')?.closest('.hr-table-wrapper'); if(w) w.scrollTop = 0; }

function renderDeptPage() {
    const pp = deptGetPerPage(), total = _deptRows.length;
    const totalPages = Math.max(1, Math.ceil(total / pp));
    if (_deptPage > totalPages) _deptPage = totalPages;
    const start = (_deptPage - 1) * pp, end = Math.min(start + pp, total);
    document.querySelectorAll('#deptsTableBody tr.dept-row').forEach(r => r.style.display = 'none');
    _deptRows.forEach((r, i) => { r.style.display = (i >= start && i < end) ? '' : 'none'; });
    const bar = document.getElementById('deptPaginationBar');
    if (bar) bar.style.display = total > 0 ? 'flex' : 'none';
    const info = document.getElementById('deptPaginationInfo');
    if (info) info.innerHTML = `Showing <strong>${total === 0 ? 0 : start + 1}</strong> to <strong>${end}</strong> of <strong>${total}</strong> departments`;
    const nav = document.getElementById('deptPageNav');
    if (!nav) return;
    let h = _deptPage === 1
        ? `<span class="hr-page-btn disabled"><i class="ph ph-caret-left"></i><span>Prev</span></span>`
        : `<span class="hr-page-btn" onclick="deptGoToPage(${_deptPage-1})" style="cursor:pointer"><i class="ph ph-caret-left"></i><span>Prev</span></span>`;
    h += `<div class="hr-page-numbers">`;
    let ps = Math.max(1, _deptPage-2), pe = Math.min(totalPages, _deptPage+2);
    if (ps > 1) { h += `<span class="hr-page-num" onclick="deptGoToPage(1)" style="cursor:pointer">1</span>`; if (ps > 2) h += `<span class="hr-page-num dots">…</span>`; }
    for (let p = ps; p <= pe; p++) h += p === _deptPage ? `<span class="hr-page-num active">${p}</span>` : `<span class="hr-page-num" onclick="deptGoToPage(${p})" style="cursor:pointer">${p}</span>`;
    if (pe < totalPages) { if (pe < totalPages-1) h += `<span class="hr-page-num dots">…</span>`; h += `<span class="hr-page-num" onclick="deptGoToPage(${totalPages})" style="cursor:pointer">${totalPages}</span>`; }
    h += `</div>`;
    h += _deptPage >= totalPages
        ? `<span class="hr-page-btn disabled"><span>Next</span><i class="ph ph-caret-right"></i></span>`
        : `<span class="hr-page-btn" onclick="deptGoToPage(${_deptPage+1})" style="cursor:pointer"><span>Next</span><i class="ph ph-caret-right"></i></span>`;
    nav.innerHTML = h;
}

function filterDeptsTable() {
    _deptRows = Array.from(document.querySelectorAll('#deptsTableBody tr.dept-row'));
    _deptPage = 1;
    renderDeptPage();
}
window.filterDeptsTable = filterDeptsTable;

document.addEventListener('DOMContentLoaded', () => { filterDeptsTable(); });
document.addEventListener('rmsTableSorted', () => { filterDeptsTable(); });
</script>
@endpush
@endsection
